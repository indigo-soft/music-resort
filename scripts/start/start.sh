#!/usr/bin/env bash
# start.sh — project status wizard.
#
# Usage: pnpm start
#        bash scripts/start/start.sh
#
# Shows current project status and recommends the next step.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

# shellcheck source=checks.sh
source "$SCRIPT_DIR/checks.sh"

cd "$ROOT_DIR"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
log_info "  🎵  music-resort — project status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

init_ok=true
onboarding_ok=true

check_init_status "$ROOT_DIR"       || init_ok=false
check_onboarding_status "$ROOT_DIR" || onboarding_ok=false
check_checklist_progress "$ROOT_DIR"

print_next_step "$init_ok" "$onboarding_ok" "$ROOT_DIR"
