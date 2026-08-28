#!/usr/bin/env bash
set -o pipefail

input=$(cat)

if [ "$(printf '%s' "$input" | jq -r '.stop_hook_active // false')" = "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:?}" || exit 0

formatted=()
errors=()

collect() {
  {
    git diff --name-only HEAD -- "$1"
    git ls-files --others --exclude-standard -- "$1"
  } 2>/dev/null | sort -u | grep -E "$2" | while IFS= read -r file; do
    [ -f "$file" ] && printf '%s\n' "$file"
  done
}

read_into() {
  local name=$1 line
  while IFS= read -r line; do
    [ -n "$line" ] && eval "$name+=(\"\$line\")"
  done
}

hash_files() {
  local file
  for file in "$@"; do
    [ -f "$file" ] && shasum "$file"
  done
}

web_files=()
admin_files=()
api_files=()
read_into web_files < <(collect apps/web '\.(ts|vue)$')
read_into admin_files < <(collect apps/admin '\.(ts|vue)$')
read_into api_files < <(collect apps/api '\.php$')

if [ ${#web_files[@]} -eq 0 ] && [ ${#admin_files[@]} -eq 0 ] && [ ${#api_files[@]} -eq 0 ]; then
  exit 0
fi

diff_formatted() {
  local before=$1
  shift
  read_into formatted < <(diff <(printf '%s\n' "$before") <(hash_files "$@") | sed -n 's/^> [0-9a-f]*[[:space:]]*//p')
}

run_front() {
  local app=$1 out before rel=() file
  shift
  for file in "$@"; do rel+=("${file#apps/$app/}"); done

  before=$(hash_files "$@")
  if out=$(mise exec -- pnpm exec prettier --write --log-level warn "$@" 2>&1); then
    diff_formatted "$before" "$@"
  else
    errors+=("[prettier: $app]
$out")
  fi

  if ! out=$(cd "apps/$app" && mise exec -- pnpm exec eslint --no-warn-ignored "${rel[@]}" 2>&1); then
    errors+=("[eslint: $app]
$out")
  fi
}

run_api() {
  local out before rel=() file
  for file in "$@"; do rel+=("${file#apps/api/}"); done

  if ! docker compose ps --services --status running 2>/dev/null | grep -qx api; then
    errors+=("[api] api コンテナが起動していないため pint・phpstan をスキップしました。")
    return
  fi

  before=$(hash_files "$@")
  if out=$(docker compose exec -T api ./vendor/bin/pint "${rel[@]}" 2>&1); then
    diff_formatted "$before" "$@"
  else
    errors+=("[pint]
$out")
  fi

  if ! out=$(docker compose exec -T api composer run lint 2>&1); then
    errors+=("[phpstan]
$out")
  fi
}

[ ${#web_files[@]} -gt 0 ] && run_front web "${web_files[@]}"
[ ${#admin_files[@]} -gt 0 ] && run_front admin "${admin_files[@]}"
[ ${#api_files[@]} -gt 0 ] && run_api "${api_files[@]}"

if [ ${#errors[@]} -gt 0 ]; then
  {
    printf 'apps 配下の変更に対する lint・format の結果:\n'
    [ ${#formatted[@]} -gt 0 ] && printf '整形済み: %s\n' "${formatted[*]}"
    printf '%s\n' "${errors[@]}"
  } >&2
  exit 2
fi

if [ ${#formatted[@]} -gt 0 ]; then
  jq -nc --arg files "${formatted[*]}" '{systemMessage: ("整形しました: " + $files)}'
fi

exit 0
