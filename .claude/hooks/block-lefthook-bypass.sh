#!/usr/bin/env bash
set -euo pipefail

readonly BYPASS_PATTERN='(^|[^-])--no-verify|core\.hooksPath|LEFTHOOK(_EXCLUDE)?=|git +commit([^&|;]*[[:space:]]-n([[:space:]]|$))|lefthook +uninstall'

command=$(jq -r '.tool_input.command // ""')

if printf '%s' "$command" | grep -Eq -- "$BYPASS_PATTERN"; then
  jq -nc '{
    hookSpecificOutput: {
      hookEventName: "PreToolUse",
      permissionDecision: "deny",
      permissionDecisionReason: "lefthook をバイパスする commit/push は禁止です。lint・format・test を通してから実行してください。"
    }
  }'
fi
