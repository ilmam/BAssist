#!/bin/sh
# Runs the project's delivery checks (guardrails A and B, see docs/delivery-guardrails.md).
#
#   sh scripts/spine-checks.sh fast    before every commit (git hook): quick checks only
#   sh scripts/spine-checks.sh full    in CI: the quick checks plus the slow ones
#
# The checks are listed in .spine-checks at the project root, one per line:
#
#   fast: <command>     runs in both tiers
#   full: <command>     runs only in the full tier
#   # comment           ignored
#
# Every check runs even if an earlier one fails, so one run shows everything that is wrong.
# Exit status: 0 all passed, 1 a check failed, 2 the setup is wrong (no file, no checks).
# POSIX sh and nothing else, so it behaves the same in a git hook and in any CI system.

tier="${1:-fast}"
case "$tier" in
    fast | full) ;;
    *)
        echo "usage: sh scripts/spine-checks.sh [fast|full]" >&2
        exit 2
        ;;
esac

root="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$root" || exit 2

config="${SPINE_CHECKS_FILE:-.spine-checks}"
if [ ! -f "$config" ]; then
    echo "error: $config not found; list the project's checks there (see docs/delivery-guardrails.md)" >&2
    exit 2
fi

ran=0
failed=0

while IFS= read -r line || [ -n "$line" ]; do
    line="${line%"$(printf '\r')"}"
    case "$line" in
        '' | '#'*) continue ;;
    esac

    name="${line%%:*}"
    command="${line#*:}"
    command="${command# }"

    case "$tier:$name" in
        fast:fast | full:fast | full:full) ;;
        *) continue ;;
    esac

    ran=$((ran + 1))
    echo "==> [$name] $command"
    if ! sh -c "$command"; then
        echo "FAILED: $command"
        failed=$((failed + 1))
    fi
done < "$config"

if [ "$ran" -eq 0 ]; then
    echo "error: no $tier checks found in $config" >&2
    exit 2
fi

if [ "$failed" -gt 0 ]; then
    echo "$failed of $ran $tier check(s) failed"
    exit 1
fi

echo "all $ran $tier check(s) passed"
