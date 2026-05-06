#!/bin/bash
#
# @file Cleanup stale CI multidev environments.
#
# Two-pass cleanup:
#   1. Prefix match: delete multidevs from same matrix combo (prior runs)
#   2. Age-based sweep: delete any CI-pattern multidev older than 72h
#
# Requires: TERMINUS_SITE environment variable.
# Arguments:
#   $1 - Prefix to match (e.g., "d10p82-")
#   $2 - Current environment name to keep (optional)

set -eo pipefail

PREFIX="$1"
CURRENT_ENV="${2:-}"
MAX_AGE_HOURS=72

# Matches CI-generated multidev names like d10p82-25, d11p83-31
# (no solr dimension, unlike search_api_pantheon)
CI_PATTERN='^d[0-9]+p[0-9]+-[0-9]+'

if [[ -z "$TERMINUS_SITE" || -z "$PREFIX" ]]; then
  echo "::error::TERMINUS_SITE and PREFIX (arg 1) must be set."
  exit 1
fi

NOW=$(date +%s)
CUTOFF=$((NOW - MAX_AGE_HOURS * 3600))

# JSON output includes creation timestamps needed for age-based cleanup
MULTIDEVS=$(terminus multidev:list "$TERMINUS_SITE" --format=json 2>/dev/null || echo "{}")

for env in $(echo "$MULTIDEVS" | jq -r 'keys[]' 2>/dev/null); do
  # Skip the current run's multidev.
  # On success, the "Delete current multidev" CI step already removed it.
  # On failure, we want it to persist for investigation.
  if [[ "$env" == "$CURRENT_ENV" ]]; then
    continue
  fi

  # Pass 1: prefix match (same matrix combo from prior runs)
  if [[ "$env" == ${PREFIX}* ]]; then
    echo "Deleting stale multidev (prefix match): $env"
    terminus multidev:delete "$TERMINUS_SITE.$env" --delete-branch --yes || true
    continue
  fi

  # Pass 2: age-based sweep (orphans from retired matrix combos)
  if [[ "$env" =~ $CI_PATTERN ]]; then
    CREATED=$(echo "$MULTIDEVS" | jq -r --arg e "$env" '.[$e].created // 0' 2>/dev/null)
    if [[ "$CREATED" -gt 0 && "$CREATED" -lt "$CUTOFF" ]]; then
      echo "Deleting old CI multidev (age > ${MAX_AGE_HOURS}h): $env"
      terminus multidev:delete "$TERMINUS_SITE.$env" --delete-branch --yes || true
    fi
  fi
done
