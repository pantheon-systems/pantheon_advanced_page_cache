#!/bin/bash
#
# @file Behat CDN tests for Pantheon Advanced Page Cache.
# Runs the Behat suite against a Multidev environment through the GCDN.
#
# Requires: TERMINUS_SITE, MULTIDEV_ENV environment variables.

set -eo pipefail

# ── Preflight ────────────────────────────────────────────────────────
if [[ -z "$TERMINUS_SITE" || -z "$MULTIDEV_ENV" ]]; then
  echo "::error::TERMINUS_SITE and MULTIDEV_ENV must be set."
  exit 1
fi

SITE_ENV="${TERMINUS_SITE}.${MULTIDEV_ENV}"
export TERMINUS_ENV="$MULTIDEV_ENV"

# ── Bot-bypass token ─────────────────────────────────────────────────
echo "::group::Bot-bypass token"
terminus self:plugin:install pantheon-systems/terminus-gcdn-plugin
BOT_BYPASS_TOKEN=$(terminus gcdn:bot-bypass "$TERMINUS_SITE" --format=json | jq -r '.[] | select(.role=="current") | .token')
if [[ -z "$BOT_BYPASS_TOKEN" ]]; then
  echo "::error::No bot-bypass token returned for $TERMINUS_SITE."
  exit 1
fi
echo "::add-mask::$BOT_BYPASS_TOKEN"
export BOT_BYPASS_TOKEN
echo "::endgroup::"

# ── Site setup ───────────────────────────────────────────────────────
echo "::group::Site setup"
terminus drush "$SITE_ENV" -- config:set system.performance cache.page.max_age 600 -y
terminus drush "$SITE_ENV" -- role:perm:add anonymous "create page content,create article content"
sleep 15
echo "::endgroup::"

# ── Behat ────────────────────────────────────────────────────────────
export BEHAT_PARAMS="{\"extensions\":{\"Behat\\\\MinkExtension\":{\"base_url\":\"https://${MULTIDEV_ENV}-${TERMINUS_SITE}.pantheonsite.io\"}}}"
./vendor/bin/behat --config=tests/behat/behat-pantheon.yml --strict
