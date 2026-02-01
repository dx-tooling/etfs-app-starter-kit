#!/usr/bin/env bash
#MISE description="Run a command in the app container"

# Source .env and .env.local for Docker Compose variable interpolation
# This allows ETFS_PROJECT_NAME to be set in .env.local without modifying .env
if [ -f .env ]; then
    set -a
    source .env
    set +a
fi
if [ -f .env.local ]; then
    set -a
    source .env.local
    set +a
fi

/usr/bin/env docker compose exec -ti app "$@"
