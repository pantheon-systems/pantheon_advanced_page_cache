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
git clone $(terminus connection:info ${TERMINUS_SITE}.dev --field=git_url) --branch $TERMINUS_BASE_ENV drupal-site
cd drupal-site

git checkout -b $TERMINUS_ENV

# requiring other modules below was throwing an error if this dependency was not updated first.
# I think because the composer.lock file for the site has dev-master as the version for this
# dependency. But the CI process calling this file runs against a different branch name thanks to the
# git clone command above.
composer update "pantheon-upstreams/upstream-configuration"

# Add views_custom_cache_tag
composer -- require "drupal/views_custom_cache_tag:1.x-dev"

# Make a copy of this project and rename it to use in a path repository
mkdir -p path-repositories/pantheon_advanced_page_cache
rsync -av --exclude='vendor' --exclude='drupal-site' "$PROJECT_DIR/"* path-repositories/pantheon_advanced_page_cache
sed -e 's#"name": "drupal/pantheon_advanced_page_cache"#"version": "dev-circle", "name": "local-path/pantheon_advanced_page_cache"#' "$PROJECT_DIR/composer.json" > path-repositories/pantheon_advanced_page_cache/composer.json

# Require via Composer, in case we need to require any dependencies in the future & etc.
composer -- config repositories.papc path path-repositories/pantheon_advanced_page_cache
composer -- require "local-path/pantheon_advanced_page_cache: dev-circle"

# Make a git commit
git add .
git commit -m 'Result of build step'
