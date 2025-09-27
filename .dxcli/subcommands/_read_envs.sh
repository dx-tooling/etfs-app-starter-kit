#!/usr/bin/env bash
#@metadata-start
#@source-repo git@github.com:dx-tooling/dxcli-commands-app.git
#@source-commit-id 7268a9702176717f63f6c694d6a59e646a4ef499
#@metadata-end
set -e

SCRIPT_FOLDER="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Check if APP_ENV is set before using it
if [[ -n "${APP_ENV:-}" ]]; then
    ENV="$APP_ENV"
else
    ENV="dev"
fi

[ -f "${SCRIPT_FOLDER}/../../.env" ] && source "${SCRIPT_FOLDER}/../../.env" || true
[ -f "${SCRIPT_FOLDER}/../../.env.local" ] && source "${SCRIPT_FOLDER}/../../.env.local" || true
[ -f "${SCRIPT_FOLDER}/../../.env.${ENV}" ] && source "${SCRIPT_FOLDER}/../../.env.${ENV}" || true
[ -f "${SCRIPT_FOLDER}/../../.env.${ENV}.local" ] && source "${SCRIPT_FOLDER}/../../.env.${ENV}.local" || true
