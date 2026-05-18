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

  if ! pnpm install --frozen-lockfile --ignore-scripts &>/dev/null; then
    log_error "pnpm-lock.yaml is out of sync with package.json. Run: pnpm install"
    exit 1
  fi

  log_success "pnpm-lock.yaml is in sync. Project version: $pj_version"
}
