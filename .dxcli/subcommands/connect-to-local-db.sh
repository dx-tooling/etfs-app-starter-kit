#!/usr/bin/env bash
#@metadata-start
#@name db
#@description Connect to the local database
#@source-repo git@github.com:dx-tooling/dxcli-commands-app.git
#@source-commit-id 7268a9702176717f63f6c694d6a59e646a4ef499
#@metadata-end

# >>> BEGIN SETUP - always keep this section in your scripts!
set -e
set -u
set -o pipefail

SOURCE=${BASH_SOURCE[0]}
while [ -L "$SOURCE" ]; do
    DIR=$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )
    SOURCE=$(readlink "$SOURCE")
    [[ $SOURCE != /* ]] && SOURCE=$DIR/$SOURCE
done

SCRIPT_FOLDER=$( cd -P "$( dirname "$SOURCE" )" >/dev/null 2>&1 && pwd )
if [ -z "$SCRIPT_FOLDER" ]; then
    echo "Failed to determine script location" >&2
    exit 1
fi

PROJECT_ROOT=$( cd "$SCRIPT_FOLDER/../.." >/dev/null 2>&1 && pwd )
if [ -z "$PROJECT_ROOT" ]; then
    echo "Failed to determine dxcli root" >&2
    exit 1
fi

source "$PROJECT_ROOT/.dxcli/shared.sh"
# <<< END SETUP - from now on, use $PROJECT_ROOT to get the full path to your project's root folder.


source "$PROJECT_ROOT/.dxcli/subcommands/_read_envs.sh"

mysql -h"${DATABASE_HOST}" -u"${DATABASE_USER}" -p"${DATABASE_PASSWORD}" "${DATABASE_DB}" "$@"
