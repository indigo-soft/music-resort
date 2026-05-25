#!/usr/bin/env bash
# checks.sh — init check and setup functions.
# Usage: source "$(dirname "${BASH_SOURCE[0]}")/checks.sh"

# shellcheck source=../libs/colors.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/../libs/colors.sh"

# Global npm packages required for the project.
# Format: "binary:package1 package2"
REQUIRED_GLOBAL_PACKAGES=(
    "commitlint:@commitlint/cli @commitlint/config-conventional"
    "release-it:release-it @release-it/conventional-changelog"
)

check_node() {
    log_info "🟢 Checking Node.js..."

    if ! command -v node &>/dev/null; then
        log_error "Node.js not found."
        log_text "  Install it via nvm: https://github.com/nvm-sh/nvm"
        log_text "  Or download from:  https://nodejs.org"
        exit 1
    fi

    local node_version
    node_version=$(node --version)
    local major
    major=$(echo "$node_version" | sed 's/v//' | cut -d. -f1)

    if [ "$major" -lt 24 ]; then
        log_error "Node.js $node_version is too old. Required: v24 or newer."
        log_text "  Upgrade via nvm: nvm install 24 && nvm use 24"
        exit 1
    fi

    log_success "  Node.js $node_version — ok"
}

check_php() {
    log_info "🐘 Checking PHP..."

    if ! command -v php &>/dev/null; then
        log_error "PHP not found. Install PHP 8.5+."
        exit 1
    fi

    local php_version
    php_version=$(php --version | head -1 | cut -d' ' -f2)
    log_success "  PHP $php_version — ok"
}

check_composer() {
    log_info "🎼 Checking Composer..."

    if ! command -v composer &>/dev/null; then
        log_error "Composer not found. Install from: https://getcomposer.org"
        exit 1
    fi

    log_success "  Composer — ok"
}

check_global_tools() {
    log_info "🔍 Checking global npm tools..."

    for entry in "${REQUIRED_GLOBAL_PACKAGES[@]}"; do
        local binary packages
        binary="${entry%%:*}"
        packages="${entry#*:}"

        if command -v "$binary" &>/dev/null; then
            log_success "  $binary — already installed ($(command -v "$binary"))"
        else
            log_info "  $binary — not found, installing: $packages"
            # shellcheck disable=SC2086
            npm install -g $packages
            log_success "  $binary — installed"
        fi
    done
}

check_corepack() {
    log_info "📦 Checking corepack..."

    if ! command -v corepack &>/dev/null; then
        log_error "corepack not found. Install it: npm install -g corepack"
        exit 1
    fi

    corepack enable pnpm &>/dev/null
    log_success "  corepack — enabled for pnpm"
}

check_pnpm() {
    if ! command -v pnpm &>/dev/null; then
        log_error "pnpm not found. Run: corepack enable pnpm"
        exit 1
    fi
}

install_composer_deps() {
    log_info "🎼 Installing Composer dependencies..."
    composer install
    log_success "Composer dependencies installed."
}

install_pnpm_deps() {
    log_info "📦 Installing local pnpm dependencies..."
    pnpm install
    log_success "pnpm dependencies installed."
}

install_hooks() {
    log_info "🪝 Installing Lefthook git hooks..."
    pnpm exec lefthook install
    log_success "Lefthook hooks installed."
}

make_scripts_executable() {
    local root_dir="${1:-$(pwd)}"
    log_info "🔧 Making scripts executable..."
    find "$root_dir/scripts" -name "*.sh" -exec chmod +x {} +
    log_success "Scripts are executable."
}

setup_env() {
    local root_dir="${1:-$(pwd)}"
    if [ -f "$root_dir/.env" ]; then
        log_success "  .env — already exists, skipping."
    else
        cp "$root_dir/.env.example" "$root_dir/.env"
        log_success "  .env — created from .env.example."
    fi
}

setup_git_template() {
    log_info "📝 Configuring git commit message template..."
    git config commit.template .gitmessage
    log_success "  Commit template → .gitmessage"
}

print_done() {
    echo ""
    log_success "✅ Project initialized successfully!"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    log_info "  ⚠️  ACTION REQUIRED"
    echo ""
    log_text "  Add GITHUB_TOKEN to .env to enable GitHub Releases:"
    log_text "  GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
}
