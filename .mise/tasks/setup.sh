#!/usr/bin/env bash
#MISE description="Bootstrap local development environment"
#MISE depends=["check-docker-performance"]

set -e

# Source .env and .env.local for Docker Compose variable interpolation
# Docker Compose only reads .env by default, but we want to support .env.local
# for local overrides (e.g., ETFS_PROJECT_NAME) without modifying version-controlled files
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

docker compose up --build -d

/usr/bin/env docker compose exec -T app composer install
mise run in-app-container mise trust
mise run in-app-container mise install
mise run npm install --no-save
mise run console doctrine:database:create --if-not-exists
mise run console doctrine:migrations:migrate --no-interaction
mise run frontend
mise run quality
mise run tests
mise run browser
