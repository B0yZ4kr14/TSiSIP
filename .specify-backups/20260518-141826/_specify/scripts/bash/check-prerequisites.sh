#!/usr/bin/env bash
set -euo pipefail

REQUIRE_TASKS=false
INCLUDE_TASKS=false
OUTPUT_JSON=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --require-tasks) REQUIRE_TASKS=true; shift ;;
    --include-tasks) INCLUDE_TASKS=true; shift ;;
    --json) OUTPUT_JSON=true; shift ;;
    *) shift ;;
  esac
done

FEATURE_DIR=""
if [[ -f .specify/feature.json ]]; then
  FEATURE_DIR=$(jq -r '.feature_directory // empty' .specify/feature.json 2>/dev/null || true)
fi

AVAILABLE_DOCS=()
if [[ -n "$FEATURE_DIR" && -d "$FEATURE_DIR" ]]; then
  [[ -f "$FEATURE_DIR/spec.md" ]] && AVAILABLE_DOCS+=("spec.md")
  [[ -f "$FEATURE_DIR/plan.md" ]] && AVAILABLE_DOCS+=("plan.md")
  [[ -f "$FEATURE_DIR/tasks.md" ]] && AVAILABLE_DOCS+=("tasks.md")
fi

MISSING=()
[[ " ${AVAILABLE_DOCS[*]} " != *" spec.md "* ]] && MISSING+=("spec.md")
[[ " ${AVAILABLE_DOCS[*]} " != *" plan.md "* ]] && MISSING+=("plan.md")
if [[ "$REQUIRE_TASKS" == true ]]; then
  [[ " ${AVAILABLE_DOCS[*]} " != *" tasks.md "* ]] && MISSING+=("tasks.md")
fi

if [[ "$OUTPUT_JSON" == true ]]; then
  docs_json=$(printf '%s\n' "${AVAILABLE_DOCS[@]}" | jq -R . | jq -s .)
  missing_json=$(printf '%s\n' "${MISSING[@]}" | jq -R . | jq -s .)
  jq -n \
    --arg feature_dir "$FEATURE_DIR" \
    --argjson available_docs "$docs_json" \
    --argjson missing "$missing_json" \
    '{feature_directory: $feature_dir, available_docs: $available_docs, missing: $missing, ok: ($missing | length == 0)}'
else
  echo "Feature directory: ${FEATURE_DIR:-<not set>}"
  echo "Available docs: ${AVAILABLE_DOCS[*]:-<none>}"
  if [[ ${#MISSING[@]} -gt 0 ]]; then
    echo "Missing docs: ${MISSING[*]}"
    exit 1
  fi
fi
