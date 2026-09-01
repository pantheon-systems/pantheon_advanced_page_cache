#!/bin/bash
set -euo pipefail

MULTIDEV_NAME="$1"
TERMINUS_SITE="$2"
GITHUB_ENV_FILE="${3:-${GITHUB_ENV:-}}"
GIT_REF="${4:-dev-2.x}"
PHP_VERSION="${5:-}"

# Limit multidev name to 11 characters
MULTIDEV="${MULTIDEV_NAME:0:11}"

# Check if multidev with the same name already exists, if so delete it
if terminus multidev:list "$TERMINUS_SITE" --format=list | grep -q "^$MULTIDEV$"; then
  terminus multidev:delete "$TERMINUS_SITE.$MULTIDEV" --delete-branch --yes
fi

# Create new multidev from dev environment.
# If terminus fails its own health check, verify the environment and poll it here.
if ! terminus multidev:create "$TERMINUS_SITE.dev" "$MULTIDEV"; then
  echo "multidev:create returned an error, verifying the environment directly..."

  if ! terminus multidev:list "$TERMINUS_SITE" --format=list | grep -q "^$MULTIDEV$"; then
    echo "Environment $MULTIDEV was not created."
    exit 1
  fi

  HEALTHCHECK_URL="$(terminus env:view "$TERMINUS_SITE.$MULTIDEV" --print)pantheon_healthcheck"
  echo "Polling $HEALTHCHECK_URL"
  HEALTHY=0
  for _ in $(seq 1 12); do
    if [ "$(curl -s -o /dev/null -w '%{http_code}' "$HEALTHCHECK_URL")" = "200" ]; then
      HEALTHY=1
      break
    fi
    sleep 10
  done

  if [ "$HEALTHY" -ne 1 ]; then
    echo "Environment $MULTIDEV did not respond to the health check."
    exit 1
  fi
  echo "Environment $MULTIDEV is healthy."
fi

# Get git URL
echo "Getting Pantheon git URL..."
GIT_URL=$(terminus connection:info $TERMINUS_SITE.$MULTIDEV --field=git_url)
echo "Git URL: $GIT_URL"

# Clone the Pantheon site repository
echo "Cloning repository..."
GIT_SSH_COMMAND="ssh -v -o StrictHostKeyChecking=no" git clone "$GIT_URL" pantheon-site

cd pantheon-site

# Checkout the multidev branch that was created by terminus
echo "Checking out branch $MULTIDEV..."
git checkout "$MULTIDEV"

# Allow plugins required by the module
composer config allow-plugins.drupal/core-project-message true --no-interaction
composer config allow-plugins.phpstan/extension-installer true --no-interaction
composer config allow-plugins.mglaman/composer-drupal-lenient true --no-interaction

# Add pantheon_advanced_page_cache module via composer.
# VCS repo allows installing branch builds (not just tagged releases).
composer config repositories.pantheon_advanced_page_cache '{"type": "vcs", "url": "git@github.com:pantheon-systems/pantheon_advanced_page_cache.git", "canonical": false}'
composer require pantheon-systems/pantheon_advanced_page_cache:"${GIT_REF}"

# Show where the module was installed for diagnostics.
echo "Module installed at:"
find . -path '*/pantheon_advanced_page_cache/pantheon_advanced_page_cache.info.yml' -not -path './vendor/*' | head -1

# Remove git directories from submodules.
# Pantheon's git-based deployment rejects pushes with nested .git dirs.
# Detect the module path dynamically (nested docroot vs flat).
if [ -d web/modules/contrib/pantheon_advanced_page_cache/.git ]; then
  MODULE_INSTALL_PATH="web/modules/contrib/pantheon_advanced_page_cache"
elif [ -d modules/contrib/pantheon_advanced_page_cache/.git ]; then
  MODULE_INSTALL_PATH="modules/contrib/pantheon_advanced_page_cache"
fi
if [ -n "${MODULE_INSTALL_PATH:-}" ]; then
  echo "Removing .git from $MODULE_INSTALL_PATH"
  rm -rf "$MODULE_INSTALL_PATH/.git/"
fi
rm -rf vendor/*/.git/

# Set PHP version in pantheon.yml if specified.
# Without this, tests run under the base site's default PHP, not the matrix PHP.
if [ -n "$PHP_VERSION" ]; then
  echo "Setting PHP version to ${PHP_VERSION}..."
  if [ -f pantheon.yml ]; then
    if grep -q "php_version:" pantheon.yml; then
      sed -i "s/php_version:.*/php_version: ${PHP_VERSION}/" pantheon.yml
    else
      echo "php_version: ${PHP_VERSION}" >> pantheon.yml
    fi
  else
    echo "api_version: 1" > pantheon.yml
    echo "php_version: ${PHP_VERSION}" >> pantheon.yml
  fi
fi

# Commit and push changes
git add .
git commit -m "Add pantheon_advanced_page_cache module (PHP ${PHP_VERSION:-default})"
git push --set-upstream origin "$MULTIDEV"

cd ..

# Wait for Pantheon to finish building and deploying the code
echo "Waiting for Pantheon build to complete..."
terminus workflow:wait $TERMINUS_SITE.$MULTIDEV --max=300

# Enable the module
echo "Enabling pantheon_advanced_page_cache module..."
terminus drush $TERMINUS_SITE.$MULTIDEV -- pm:enable pantheon_advanced_page_cache -y

# Save the multidev name for later steps
echo "MULTIDEV_ENV=$MULTIDEV" >> "$GITHUB_ENV_FILE"
