#!/bin/bash
set -e
export TERMINUS_ENV=$CIRCLE_BUILD_NUM

SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
CIRCLE_DIR="$(dirname -- "${SCRIPT_DIR}")"
PROJECT_DIR="$(dirname -- "${CIRCLE_DIR}")"

if [ "$TERMINUS_BASE_ENV" = "dev" ]; then
  export TERMINUS_BASE_ENV=master
fi

# Bring the code down to Circle so that modules can be added via composer.
git clone $(terminus connection:info ${TERMINUS_SITE}.dev --field=git_url) --branch $TERMINUS_BASE_ENV "$HOME/drupal-site"
cd "$HOME/drupal-site"

git checkout -b $TERMINUS_ENV

# requiring other modules below was throwing an error if this dependency was not updated first.
# I think because the composer.lock file for the site has dev-master as the version for this
# dependency. But the CI process calling this file runs against a different branch name thanks to the
# git clone command above.
composer update "pantheon-upstreams/upstream-configuration"

# Composer require views_custom_cache_tag
composer -- require "drupal/views_custom_cache_tag:1.x-dev"

# Add this project, but not via Composer
mkdir -p web/modules/circle/pantheon_advanced_page_cache
cp -R "$PROJECT_DIR/*" web/modules/circle/pantheon_advanced_page_cache

# Make a git commit
git add .
git commit -m 'Result of build step'
