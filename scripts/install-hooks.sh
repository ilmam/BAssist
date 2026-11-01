#!/bin/sh
# Points git at the project's hooks folder so the pre-commit check runs on every commit.
# Run once per clone: sh scripts/install-hooks.sh

root="$(git rev-parse --show-toplevel)" || exit 1
cd "$root" || exit 1

current="$(git config --get core.hooksPath)"
if [ -n "$current" ] && [ "$current" != "scripts/hooks" ]; then
    echo "git already takes hooks from $current; add this line to the pre-commit hook there instead:" >&2
    echo "    sh scripts/spine-checks.sh fast" >&2
    exit 1
fi

git config core.hooksPath scripts/hooks
chmod +x scripts/hooks/pre-commit 2>/dev/null

echo "git hooks now come from scripts/hooks (pre-commit runs the fast checks)"
