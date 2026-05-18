#!/usr/bin/env bash
# colors.sh — colored output library for shell scripts.

_colors_supported() {
  [[ -t 1 ]] && [[ "${TERM:-}" != "dumb" ]] && [[ "${NO_COLOR:-}" != "1" ]]
}

if _colors_supported; then
  _RED='\033[0;31m'
  _GREEN='\033[0;32m'
  _YELLOW='\033[0;33m'
  _WHITE='\033[0;97m'
  _RESET='\033[0m'
else
  _RED=''
  _GREEN=''
  _YELLOW=''
  _WHITE=''
  _RESET=''
fi

log_error() {
  echo -e "${_RED}❌ ${1}${_RESET}" >&2
}

log_success() {
  echo -e "${_GREEN}✔ ${1}${_RESET}"
}

log_info() {
  echo -e "${_YELLOW}${1}${_RESET}"
}

log_text() {
  echo -e "${_WHITE}${1}${_RESET}"
}

log_step() {
  echo -e "${_WHITE}→ ${1}${_RESET}"
}
