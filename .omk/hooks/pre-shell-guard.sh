#!/usr/bin/env bash
# PreShellUse Guard — blocks dangerous commands
set -e

# Close security gate if jq/python3 is missing (deny by default)
if command -v python3 &>/dev/null; then
  PY=python3
elif command -v python &>/dev/null; then
  PY=python
else
  echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"python3 not installed — pre-shell-guard cannot validate commands"}}'
  exit 0
fi

INPUT=$(cat)
COMMAND=$(echo "$INPUT" | $PY -c 'import sys,json; d=json.load(sys.stdin); print(d.get("tool_input",{}).get("command",""))')
ARGS=$(echo "$INPUT" | $PY -c 'import sys,json; d=json.load(sys.stdin); print(d.get("tool_input",{}).get("args",""))')

FULL="$COMMAND $ARGS"

# Block list
BLOCKED=(
  "rm -rf /"
  "rm -rf ~"
  "sudo"
  "git push --force"
  "git push -f"
  "git clean -fdx"
  "chmod -R 777"
  "docker system prune"
  "kubectl delete"
  "aws s3 rm --recursive"
  "curl | bash"
  "curl | sh"
  "wget | bash"
  "wget | sh"
  "mkfs"
  "dd if="
  "> /dev/"
  ":(){ :|:& };:"
)

for pattern in "${BLOCKED[@]}"; do
  if [[ "$FULL" == *"$pattern"* ]]; then
    echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"Potentially destructive command blocked by pre-shell-guard"}}'
    exit 0
  fi
done

# Release/deploy guard. These commands are not destructive like rm -rf, but
# they can publish external state. Require explicit opt-in plus fresh evidence.
RELEASE_GUARDED=(
  "git push"
  "npm publish"
  "pnpm publish"
  "yarn npm publish"
  "gh release create"
  "gh workflow run"
  "npm version"
)

for pattern in "${RELEASE_GUARDED[@]}"; do
  if [[ "$FULL" == *"$pattern"* ]] && [[ "${OMK_ALLOW_RELEASE:-0}" != "1" ]]; then
    echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"deny","permissionDecisionReason":"Release/deploy command blocked by OMK release guard. Re-run with OMK_ALLOW_RELEASE=1 only after an explicit user request and fresh verification evidence."}}'
    exit 0
  fi
done

echo '{"hookSpecificOutput":{"hookEventName":"PreToolUse","permissionDecision":"allow"}}'
