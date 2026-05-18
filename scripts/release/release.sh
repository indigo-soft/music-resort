#!/usr/bin/env bash
# release.sh — project release script.
#
# Usage (direct):      bash scripts/release/release.sh [--type=major|minor|patch] [--dry]
# Usage (via npm):     npm run release:dry
#                      npm run release:patch
#                      npm run release:minor
#                      npm run release:major
#
# NOTE: Use `npm run` instead of `pnpm run` to avoid pnpm's ELIFECYCLE
#       noise on non-zero exit codes.
#
# NOTE: release-it and @release-it/conventional-changelog must be installed globally:
#       npm install -g release-it @release-it/conventional-changelog
#       This is required because pnpm's isolated linker creates text redirect files
#       instead of real symlinks on this WSL2 setup, preventing local node resolution.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
LIBS_DIR="$SCRIPT_DIR/../libs"

# Load shared libraries
# shellcheck source=../libs/colors.sh
source "$LIBS_DIR/colors.sh"
# shellcheck source=../libs/env.sh
source "$LIBS_DIR/env.sh"
# shellcheck source=checks.sh
source "$SCRIPT_DIR/checks.sh"

# -----------------------------
#  Argument parsing
# -----------------------------
RELEASE_TYPE=""
DRY_RUN=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --type=*)
      RELEASE_TYPE="${1#*=}"
      shift
      ;;
    --type)
      if [[ $# -lt 2 || -z "${2:-}" ]]; then
        log_error "--type requires a value: major, minor or patch."
        exit 1
      fi
      RELEASE_TYPE="$2"
      shift 2
      ;;
    --dry)
      DRY_RUN=true
      shift
      ;;
    --help|-h)
      log_text "Usage: $0 [--type=major|minor|patch] [--dry]"
      log_text ""
      log_text "  --type=TYPE   Release type: major, minor or patch"
      log_text "  --dry         Dry run: show what would be done without making changes"
      log_text "  --help        Show this help message"
      exit 0
      ;;
    *)
      log_error "Unknown argument: $1"
      log_text "  Usage: $0 [--type=major|minor|patch] [--dry]"
      exit 1
      ;;
  esac
done

# -----------------------------
#  Validate RELEASE_TYPE
# -----------------------------
if [ -n "$RELEASE_TYPE" ]; then
  case "$RELEASE_TYPE" in
    major|minor|patch) ;;
    *)
      log_error "Invalid release type: '$RELEASE_TYPE'. Allowed values: major, minor, patch."
      exit 1
      ;;
  esac
fi

# -----------------------------
#  Load .env for all modes
# -----------------------------
load_env "$ROOT_DIR"

# -----------------------------
#  Pre-release checks
# -----------------------------
check_dependencies
check_clean
check_branch
check_pushed
check_lockfile
check_changelog

# -----------------------------
#  Run release
# -----------------------------
log_info "🚀 Starting release..."

if [ "$DRY_RUN" = true ]; then
  log_info "🔎 Dry-run mode — no changes will be made to the repository."
  if [ -n "$RELEASE_TYPE" ]; then
    release-it --dry-run --increment "$RELEASE_TYPE"
  else
    release-it --dry-run
  fi
  exit 0
fi

if [ -n "$RELEASE_TYPE" ]; then
  release-it --increment "$RELEASE_TYPE"
else
  release-it
fi

log_success "Release completed successfully! 🎉"
