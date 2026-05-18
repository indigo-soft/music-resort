#!/bin/sh
# commitlint.sh — wrapper for commitlint via pnpm store.
#
# pnpm's default linker creates text redirect files in node_modules/@scope/pkg,
# so Node cannot resolve them as directories. This script reads the real path
# from the pnpm-generated shim in node_modules/.bin/commitlint and calls
# cli.js directly from the .pnpm store with the correct NODE_PATH.

ROOT="$(git rev-parse --show-toplevel)"
SHIM="$ROOT/node_modules/.bin/commitlint"

if [ ! -f "$SHIM" ]; then
    echo "❌ commitlint shim not found. Run: pnpm install && pnpm run init" >&2
    exit 1
fi

# Extract the cli package dir from the shim's NODE_PATH
# Shim contains a line like:
#   export NODE_PATH=".../@commitlint/cli/node_modules:..."
CLI_DIR=$(grep -o '[^"]*@commitlint/cli/node_modules' "$SHIM" | head -1 | sed 's|/node_modules$||')

if [ -z "$CLI_DIR" ]; then
    echo "❌ Cannot extract commitlint path from pnpm shim" >&2
    exit 1
fi

CLI_JS="$CLI_DIR/cli.js"
STORE_DIR=$(dirname "$CLI_DIR")

if [ ! -f "$CLI_JS" ]; then
    echo "❌ cli.js not found at: $CLI_JS" >&2
    exit 1
fi

export NODE_PATH="$CLI_DIR/node_modules:$STORE_DIR:$ROOT/node_modules/.pnpm/node_modules${NODE_PATH:+:$NODE_PATH}"

exec node "$CLI_JS" --edit "$1" --verbose
