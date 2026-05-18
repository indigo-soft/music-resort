#!/usr/bin/env bash
# env.sh — safe .env loader library for shell scripts.

load_env() {
  local root_dir="${1:-}"

  if [ -z "$root_dir" ]; then
    log_error "load_env: ROOT_DIR argument is required."
    return 1
  fi

  local env_file="$root_dir/.env"

  if [ ! -f "$env_file" ]; then
    return 0
  fi

  log_info "📦 Loading environment variables from .env..."

  local key raw_value value

  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ -z "$line" || "$line" == \#* ]] && continue

    key="${line%%=*}"
    raw_value="${line#*=}"

    [[ -z "$key" ]] && continue

    value="${raw_value%\"}"
    value="${value#\"}"
    value="${value%\'}"
    value="${value#\'}"

    export "$key=$value"
  done < "$env_file"
}
