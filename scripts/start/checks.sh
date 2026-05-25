#!/usr/bin/env bash
# checks.sh — status check functions for the start wizard.
# Usage: source "$(dirname "${BASH_SOURCE[0]}")/checks.sh"

# shellcheck source=../libs/colors.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/../libs/colors.sh"

status_ok()   { printf "  ✅  %s\n" "$1"; }
status_warn() { printf "  ⚠️  %s\n" "$1"; }
status_fail() { printf "  ❌  %s\n" "$1"; }
status_info() { printf "  ℹ️  %s\n" "$1"; }

check_init_status() {
    local root_dir="${1:-$(pwd)}"
    local all_ok=true

    echo ""
    log_info "🔧 Initialization"

    local tools=("commitlint" "release-it" "php" "composer")
    for tool in "${tools[@]}"; do
        if command -v "$tool" &>/dev/null; then
            status_ok "$tool — installed"
        else
            status_fail "$tool — not found (run: pnpm run init)"
            all_ok=false
        fi
    done

    if [ -f "$root_dir/.git/hooks/commit-msg" ]; then
        status_ok "git hooks — installed"
    else
        status_fail "git hooks — not installed (run: pnpm run init)"
        all_ok=false
    fi

    if [ -f "$root_dir/.env" ]; then
        if grep -q "^GITHUB_TOKEN=[a-zA-Z0-9_]\{10,\}" "$root_dir/.env" 2>/dev/null; then
            status_ok ".env — exists with GITHUB_TOKEN"
        else
            status_warn ".env — exists but GITHUB_TOKEN is missing or placeholder"
            all_ok=false
        fi
    else
        status_fail ".env — not found (run: pnpm run init)"
        all_ok=false
    fi

    $all_ok && return 0 || return 1
}

check_onboarding_status() {
    local root_dir="${1:-$(pwd)}"

    echo ""
    log_info "📋 Onboarding"

    local project_file="$root_dir/docs/context/project.md"

    if [ ! -f "$project_file" ]; then
        status_fail "docs/context/project.md — not found"
        status_info "Run the onboarding prompt: docs/prompts/onboarding.md"
        return 1
    fi

    if grep -q "{project name}" "$project_file" 2>/dev/null; then
        status_warn "docs/context/project.md — exists but not filled in"
        status_info "Run the onboarding prompt: docs/prompts/onboarding.md"
        return 1
    fi

    local project_name
    project_name=$(grep "^\*\*Name:\*\*" "$project_file" | sed 's/.*\*\*Name:\*\* //' | head -1)
    status_ok "docs/context/project.md — filled in${project_name:+ (${project_name})}"
    return 0
}

check_checklist_progress() {
    local root_dir="${1:-$(pwd)}"
    local checklist="$root_dir/docs/checklists/new-project.md"

    echo ""
    log_info "📊 Checklist progress"

    if [ ! -f "$checklist" ]; then
        status_warn "docs/checklists/new-project.md — not found"
        return
    fi

    local total=0
    local done_count=0

    while IFS= read -r line; do
        if [[ "$line" =~ ^"- [" ]]; then
            total=$(( total + 1 ))
            if [[ "$line" =~ ^"- [x]" ]]; then
                done_count=$(( done_count + 1 ))
            fi
        fi
    done < "$checklist"

    local pending=$(( total - done_count ))
    status_ok "$done_count / $total items complete"

    if [ "$pending" -gt 0 ]; then
        echo ""
        log_text "  Pending items:"
        local count=0
        while IFS= read -r line; do
            log_text "    ⬜  ${line#- \[ \] }"
            count=$(( count + 1 ))
            [ "$count" -ge 5 ] && break
        done < <(grep "^\- \[ \]" "$checklist")
        if [ "$pending" -gt 5 ]; then
            log_text "    ... and $((pending - 5)) more"
        fi
    fi
}

print_next_step() {
    local init_ok="${1:-false}"
    local onboarding_ok="${2:-false}"
    local root_dir="${3:-$(pwd)}"

    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log_info "  👉  NEXT STEP"
    echo ""

    if [ "$init_ok" = "false" ]; then
        log_text "  Initialize the project:"
        log_text "  $ pnpm run init"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        return
    fi

    if [ "$onboarding_ok" = "false" ]; then
        log_text "  Run the onboarding prompt in Claude:"
        log_text "  Open docs/prompts/onboarding.md and paste it into Claude"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        return
    fi

    local checklist="$root_dir/docs/checklists/new-project.md"
    if [ -f "$checklist" ]; then
        local next_item next_prompt
        next_item=$(grep "^\- \[ \]" "$checklist" | head -1 | sed 's/- \[ \] \*\*//;s/\*\*.*//')
        next_prompt=$(grep -A1 "^\- \[ \]" "$checklist" | grep "prompt:" | head -1 | sed 's/.*prompt: //')

        if [ -n "$next_item" ]; then
            log_text "  Complete: $next_item"
            if [ -n "$next_prompt" ]; then
                log_text "  Prompt:   $next_prompt"
            fi
        else
            log_success "  All checklist items complete! 🎉"
            log_text "  Consider running: npm run release:dry"
        fi
    fi

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
}
