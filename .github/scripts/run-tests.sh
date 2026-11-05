#!/bin/bash
#
# @file Install verification for Pantheon Advanced Page Cache.
# Verifies module is enabled and functional on a Multidev environment.
#
# Requires: TERMINUS_SITE, MULTIDEV_ENV environment variables.

set -eo pipefail

# ── Preflight ────────────────────────────────────────────────────────
if [[ -z "$TERMINUS_SITE" || -z "$MULTIDEV_ENV" ]]; then
  echo "::error::TERMINUS_SITE and MULTIDEV_ENV must be set."
  exit 1
fi

SITE_ENV="${TERMINUS_SITE}.${MULTIDEV_ENV}"

# ── Cache clear ──────────────────────────────────────────────────────
echo "::group::Cache clear"
terminus drush "$SITE_ENV" -- cr
echo "::endgroup::"

# ── Verify module enabled ───────────────────────────────────────────
echo "::group::Verify module enabled"
OUTPUT=$(terminus drush "$SITE_ENV" -- pm:list --status=enabled --type=module --format=list)
if echo "$OUTPUT" | grep -q "pantheon_advanced_page_cache"; then
  echo "::notice::pantheon_advanced_page_cache is enabled"
else
  echo "::error::pantheon_advanced_page_cache is NOT enabled"
  echo "$OUTPUT"
  exit 1
fi
echo "::endgroup::"

# ── Smoke test ───────────────────────────────────────────────────────
echo "::group::Smoke test - cache clear"
terminus drush "$SITE_ENV" -- cr
echo "::endgroup::"

# ── Enable another module and check requirements ───────────────────
echo "::group::Enable big_pipe and check requirements"
terminus drush "$SITE_ENV" -- pm:enable big_pipe -y
REQUIREMENTS=$(terminus drush "$SITE_ENV" -- core:requirements --severity=2 --field=title)
terminus drush "$SITE_ENV" -- pm:uninstall big_pipe -y
if echo "$REQUIREMENTS" | grep -q "Big Pipe incompatibility"; then
  echo "::notice::Big Pipe incompatibility requirement reported"
else
  echo "::error::Big Pipe incompatibility requirement NOT reported"
  echo "$REQUIREMENTS"
  exit 1
fi
echo "::endgroup::"

echo "::notice::All checks passed"
