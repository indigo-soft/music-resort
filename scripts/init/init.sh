#!/usr/bin/env bash
# init.sh — project initialization script.
#
# Usage: pnpm run init
#        bash scripts/init/init.sh
#
# What it does:
#   1. Checks Node.js is installed and meets minimum version requirement.
#   2. Checks PHP and Composer are installed.
#   3. Checks that required global npm tools are installed; installs missing ones.
#   4. Enables corepack for pnpm.
#   5. Installs Composer dependencies.
#   6. Installs pnpm local dependencies (lefthook).
#   7. Installs Lefthook git hooks.
#   8. Makes all shell scripts in scripts/ executable.
#   9. Copies .env.example to .env if .env does not exist.
#   10. Configures git commit message template.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

# shellcheck source=checks.sh
source "$SCRIPT_DIR/checks.sh"

cd "$ROOT_DIR"

check_node
check_php
check_composer
check_global_tools
check_corepack
check_pnpm
install_composer_deps
install_pnpm_deps
install_hooks
make_scripts_executable "$ROOT_DIR"
setup_env "$ROOT_DIR"
setup_git_template
print_done
