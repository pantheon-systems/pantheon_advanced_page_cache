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

echo "::notice::All checks passed"
