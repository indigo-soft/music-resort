#!/usr/bin/env bash
# checks.sh — pre-release check functions.
# Usage: source "$(dirname "${BASH_SOURCE[0]}")/checks.sh"

# shellcheck source=../libs/colors.sh
source "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/../libs/colors.sh"

check_dependencies() {
  local deps=("git" "node" "pnpm")
  for dep in "${deps[@]}"; do
    if ! command -v "$dep" &>/dev/null; then
      log_error "Dependency not found: $dep. Please install it before releasing."
      exit 1
    fi
  done
  log_success "All dependencies found: ${deps[*]}"
}

get_default_branch() {
  local branch

  branch=$(git symbolic-ref refs/remotes/origin/HEAD 2>/dev/null | sed 's@^refs/remotes/origin/@@')
  [ -n "$branch" ] && echo "$branch" && return 0

  branch=$(git config init.defaultBranch 2>/dev/null)
  [ -n "$branch" ] && echo "$branch" && return 0

  local candidate
  for candidate in main master trunk development; do
    if git show-ref --verify --quiet "refs/heads/$candidate"; then
      echo "$candidate" && return 0
    fi
  done

  log_error "Could not determine the default branch automatically." >&2
  log_error "Fix it by running: git remote set-head origin --auto" >&2
  exit 1
}

check_clean() {
  log_step "Checking working directory is clean..."
  if ! git diff --quiet; then
    log_error "There are unstaged changes. Please commit or discard them first."
    exit 1
  fi
  if ! git diff --cached --quiet; then
    log_error "There are staged changes. Please commit or discard them first."
    exit 1
  fi
  log_success "Working directory is clean."
}

check_branch() {
  log_step "Checking current branch..."
  local current default
  current=$(git rev-parse --abbrev-ref HEAD)
  default=$(get_default_branch)

  if [ "$current" != "$default" ]; then
    log_error "Releases must be made from the default branch ($default). Current branch: $current"
    exit 1
  fi
  log_success "Branch is correct: $current"
}

check_pushed() {
  log_step "Checking all commits are pushed..."
  if git status --porcelain --branch | grep -q "ahead"; then
    log_error "There are local commits that have not been pushed. Run: git push"
    exit 1
  fi
  log_success "All commits are pushed."
}

check_changelog() {
  log_step "Checking CHANGELOG.md..."
  if [ ! -f CHANGELOG.md ]; then
    log_error "CHANGELOG.md not found. Please create it before releasing."
    exit 1
  fi
  if [ ! -s CHANGELOG.md ]; then
    log_error "CHANGELOG.md is empty. Please add release notes before releasing."
    exit 1
  fi
  log_success "CHANGELOG.md exists and is not empty."
}

check_lockfile() {
  log_step "Checking pnpm-lock.yaml is in sync..."

  if [ ! -f pnpm-lock.yaml ]; then
    log_error "pnpm-lock.yaml not found. Run: pnpm install"
    exit 1
  fi

  if [ ! -f package.json ]; then
    log_error "package.json not found."
    exit 1
  fi

  local pj_version
  pj_version=$(node -e "process.stdout.write(require('./package.json').version)")

  if [ -z "$pj_version" ]; then
    log_error "Could not read version from package.json."
    exit 1
  fi

  log_success "pnpm-lock.yaml exists. Project version: $pj_version"
}

check_github_token() {
  log_step "Checking GITHUB_TOKEN..."

  # Check presence
  if [ -z "${GITHUB_TOKEN:-}" ]; then
    log_error "GITHUB_TOKEN is not set."
    log_text "  Add it to your .env file:"
    log_text "  GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx"
    log_text "  Get a token at: https://github.com/settings/tokens"
    exit 1
  fi

  # Check format (basic sanity check)
  if [[ ! "$GITHUB_TOKEN" =~ ^gh[ps]_[a-zA-Z0-9]{36,}$ ]] && \
     [[ ! "$GITHUB_TOKEN" =~ ^github_pat_[a-zA-Z0-9_]{82}$ ]]; then
    log_error "GITHUB_TOKEN looks invalid (unexpected format)."
    log_text "  Expected: ghp_... or ghs_... or github_pat_..."
    exit 1
  fi

  # Check validity via GitHub API
  if ! command -v curl &>/dev/null; then
    log_success "GITHUB_TOKEN is set (skipping API validation — curl not found)."
    return 0
  fi

  local http_status
  http_status=$(curl -s -o /dev/null -w "%{http_code}" \
    -H "Authorization: Bearer ${GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/user")

  case "$http_status" in
    200)
      log_success "GITHUB_TOKEN is valid and authenticated."
      ;;
    401)
      log_error "GITHUB_TOKEN is invalid or expired."
      log_text "  Generate a new token at: https://github.com/settings/tokens"
      exit 1
      ;;
    403)
      log_error "GITHUB_TOKEN does not have sufficient permissions."
      log_text "  Required scopes: repo + workflow"
      exit 1
      ;;
    *)
      log_error "Could not validate GITHUB_TOKEN (HTTP $http_status)."
      log_text "  Check your internet connection or try again later."
      exit 1
      ;;
  esac
}
