#!/usr/bin/env bash
#
# Helper definitions only. Git never runs this file as a hook of its own.

set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

# Hooks fired from GUI clients inherit a bare PATH, and the `php` first on a developer's PATH is
# often an unrelated stack-bundled build too old for the formatter. So resolve an interpreter by
# version rather than by name. `git config taqbook.php <path>` pins one per clone, which survives
# where an exported environment variable would not reach a hook launched outside a terminal.
taqbook_php() {
    local candidate
    local pinned
    pinned=$(git config --get taqbook.php || true)

    for candidate in \
        "$pinned" \
        "${TAQBOOK_PHP:-}" \
        php \
        /opt/homebrew/opt/php@8.*/bin/php \
        /usr/local/opt/php@8.*/bin/php \
        /opt/homebrew/Cellar/php@8.*/*/bin/php \
        /usr/local/Cellar/php@8.*/*/bin/php \
        /opt/homebrew/bin/php \
        /usr/local/bin/php \
        /c/laragon/bin/php/php-8.*/php.exe \
        /c/xampp/php/php.exe \
        "${USERPROFILE:-}/.config/herd/bin/php.exe"; do
        [ -n "$candidate" ] || continue
        command -v "$candidate" >/dev/null 2>&1 || continue
        "$candidate" -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' >/dev/null 2>&1 || continue

        command -v "$candidate"
        return 0
    done

    echo "taqbook: no PHP 8.1+ found. Pin one for this clone:" >&2
    echo "  git config taqbook.php /path/to/php" >&2
    return 1
}

# npm ships a `node` shim in its own directory, so a PATH that reaches npm reaches node too; nothing
# to resolve by version here the way PHP needs.
taqbook_require_node() {
    local tool

    for tool in npm npx; do
        if ! command -v "$tool" >/dev/null 2>&1; then
            echo "taqbook: $tool not found on PATH; install Node to run the formatters." >&2
            return 1
        fi
    done
}
