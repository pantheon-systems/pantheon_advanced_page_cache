#!/bin/bash
set -euo pipefail
IFS=$'\n\t'
###
# Given a site name, ensure we can create a new multidev, or delete the oldest one.
###
SITE_NAME="${1:-}"
if [[ -z "${SITE_NAME}" ]];then
    echo "Missing Site Name"
    exit 1
fi

MAX_CDE_COUNT="$(terminus site:info "${SITE_NAME}" --field='Max Multidevs')"
echo "Max Multidev Count: ${MAX_CDE_COUNT}"

DOMAINS="$(terminus env:list "${SITE_NAME}" --format=string --fields=ID,Created)"
# Filter out dev, test, or live
FILTERED_DOMAINS=$(echo "$DOMAINS" | grep -vE '\b(dev|test|live)\b')

# Count current environments
CDE_COUNT="$(echo "$FILTERED_DOMAINS" | wc -l)"
# remove whitespace to make the arithmetic work
CDE_COUNT="${CDE_COUNT//[[:blank:]]/}"

echo "There are currently ${CDE_COUNT}/${MAX_CDE_COUNT} multidevs"

if [[ "${CDE_COUNT}" -lt "${MAX_CDE_COUNT}" ]]; then
  echo "There are enough multidevs"
  exit
fi

echo "There are not enough multidevs, deleting the oldest one."

# Sort the list by the timestamps
SORTED_DOMAINS=$(echo "$FILTERED_DOMAINS" | sort -n -k2)
# Extract the top name from the sorted list
ENV_TO_REMOVE=$(echo "$SORTED_DOMAINS" | head -n 1 | cut -f1)
terminus multidev:delete --delete-branch "${SITE_NAME}.${ENV_TO_REMOVE}" --yes
