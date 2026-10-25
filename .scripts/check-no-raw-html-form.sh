#!/usr/bin/env bash
# REQ-TWIG-005 — fail when bundle or demo Twig uses raw <form> / <input> / <textarea> / <select>.
# Every page/partial form renders through Symfony Form views (form_start / child loop / form_end); the only
# exceptions are the form-theme files listed in ALLOWLIST below.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# Form-theme exceptions (REQ-TWIG-005 "Form themes"): these files implement Symfony form-theme widget blocks, so
# they emit <input> by design.
#   - _locale_tabs.html.twig: rendered by the `locale_tabs` theme block (locale_tabs_theme.html.twig); writes the
#     hidden locale child of each translation entry and marks its view rendered.
#   - nowo_special_fields_form_theme.html.twig: demo form theme overriding the `icon_selector_widget` block.
ALLOWLIST=(
  src/Resources/views/form/_locale_tabs.html.twig
  demo/symfony8/templates/form_demo/nowo_special_fields_form_theme.html.twig
)

paths=()
[[ -d src/Resources/views ]] && paths+=(src/Resources/views)
for dir in demo/*/templates; do
  [[ -d "$dir" ]] && paths+=("$dir")
done
if [[ ${#paths[@]} -eq 0 ]]; then
  echo "check-no-raw-html-form: no Twig directories — OK"
  exit 0
fi

matches="$(grep -RInE '<(form|input|textarea|select)[[:space:]>]' "${paths[@]}" --include='*.twig' || true)"
for allowed in "${ALLOWLIST[@]}"; do
  matches="$(printf '%s\n' "$matches" | grep -v -F "${allowed}:" || true)"
done
matches="$(printf '%s' "$matches" | sed '/^$/d')"
if [[ -n "$matches" ]]; then
  echo "ERROR: raw <form> / <input> / <textarea> / <select> in Twig (REQ-TWIG-005):" >&2
  echo "$matches" >&2
  exit 1
fi

echo "check-no-raw-html-form: OK"
