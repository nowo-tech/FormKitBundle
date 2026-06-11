#!/usr/bin/env sh
set -eu

# REQ-TEST-009 contract:
# - Makefile target `test-ts` (or equivalent TS coverage target) must run:
#   pnpm run test:coverage | tee coverage-ts.txt
#   sh ./.scripts/ts-coverage-percent.sh coverage-ts.txt
# - .gitignore must include /coverage-ts.txt

RAW_FILE="${1:-coverage-ts.txt}"

if [ ! -f "$RAW_FILE" ]; then
  echo "ERROR: coverage output file not found: $RAW_FILE" >&2
  exit 1
fi

# Strip ANSI escape sequences, parse coverage summary, and return a conservative
# global percentage: min(Statements, Branches, Functions, Lines).
VALUE="$(
  sed 's/\x1B\[[0-9;]*[A-Za-z]//g' "$RAW_FILE" \
    | awk '
      # Vitest 3 + @vitest/coverage-v8: ASCII table with "All files |  nn | ..." (no % suffix)
      $0 ~ /All files/ && $0 ~ /\|/ {
        n = split($0, p, "|")
        if (n >= 5) {
          statements = p[2] + 0
          branches = p[3] + 0
          functions = p[4] + 0
          lines = p[5] + 0
          from_vitest = 1
        }
      }
      !from_vitest && /^[[:space:]]*Statements[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); statements=$i; break }
      }
      !from_vitest && /^[[:space:]]*Branches[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); branches=$i; break }
      }
      !from_vitest && /^[[:space:]]*Functions[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); functions=$i; break }
      }
      !from_vitest && /^[[:space:]]*Lines[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); lines=$i; break }
      }
      END {
        if (from_vitest) {
          min = statements + 0
          if (branches + 0 < min) min = branches + 0
          if (functions + 0 < min) min = functions + 0
          if (lines + 0 < min) min = lines + 0
          printf "%.2f", min
          exit 0
        }
        if (statements=="" || branches=="" || functions=="" || lines=="") exit 1
        min=statements+0
        b=branches+0
        f=functions+0
        l=lines+0
        if (b < min) min=b
        if (f < min) min=f
        if (l < min) min=l
        printf "%.2f", min
      }
    ' || true
)"

if [ -z "${VALUE:-}" ]; then
  echo "ERROR: Could not extract TS coverage summary from ${RAW_FILE}" >&2
  exit 1
fi

if [ -t 1 ]; then
  RED="$(printf '\033[31m')"
  ORANGE="$(printf '\033[38;5;208m')"
  GREEN="$(printf '\033[32m')"
  RESET="$(printf '\033[0m')"
else
  RED=""
  ORANGE=""
  GREEN=""
  RESET=""
fi

COLOR="$GREEN"
if awk "BEGIN { exit !(${VALUE} < 50) }"; then
  COLOR="$RED"
elif awk "BEGIN { exit !(${VALUE} <= 85) }"; then
  COLOR="$ORANGE"
fi

printf 'Global TS coverage (min of Statements/Branches/Functions/Lines): %s%s%%%s\n' "$COLOR" "$VALUE" "$RESET"
