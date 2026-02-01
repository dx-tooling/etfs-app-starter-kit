# Setupbook

How to get a development environment for this application up and running.


## Prerequisites

- **macOS** (Apple Silicon or Intel)
- **Docker Desktop** — [Download here](https://www.docker.com/products/docker-desktop/)
- **Mise** — Install via `brew install mise` or see [mise.jdx.dev](https://mise.jdx.dev/getting-started.html)


## Docker Desktop Configuration

For optimal performance, ensure Docker Desktop is configured with:

1. **VirtioFS** file sharing (Settings → General → "Use VirtioFS")
2. **Docker VMM** virtualization (Settings → General → "Use Docker VMM")

The setup script will check these settings and warn you if they're not optimal.


## Quick Start

```bash
# Clone the repository
git clone git@github.com:dx-tooling/etfs-app-starter-kit.git
cd etfs-app-starter-kit

# Trust mise configuration
mise trust

# (Optional) Set a unique project name to avoid Docker conflicts
echo "ETFS_PROJECT_NAME=my-project" > .env.local

# Bootstrap everything
mise run setup
```

That's it! The setup script handles everything:

1. Checks Docker Desktop performance settings
2. Builds and starts containers (`app`, `nginx`, `mariadb`)
3. Installs PHP dependencies via Composer
4. Installs and configures Mise tools in the container
5. Installs Node.js dependencies
6. Creates the database and runs migrations
7. Builds frontend assets (Tailwind, TypeScript)
8. Runs quality checks (PHPStan, ESLint, Prettier)
9. Runs all test suites
10. Opens the application in your browser


## Manual Setup (Step by Step)

If you prefer to run steps individually:

```bash
# Trust mise
mise trust

# Set project name
echo "ETFS_PROJECT_NAME=my-project" > .env.local

# Start containers
source .env && source .env.local
docker compose up --build -d

# Install PHP dependencies
docker compose exec -ti app composer install

# Configure mise in container
mise run in-app-container mise trust
mise run in-app-container mise install

# Install Node dependencies
mise run npm install --no-save

# Setup database
mise run console doctrine:database:create --if-not-exists
mise run console doctrine:migrations:migrate --no-interaction

# Build frontend
mise run frontend

# Run quality checks
mise run quality

# Run tests
mise run tests

# Open in browser
mise run browser
```


## Verifying the Setup

After setup completes:

1. The application should open automatically in your browser
2. All quality checks should pass (PHPStan Level 10, ESLint, etc.)
3. All tests should pass (unit, integration, application, architecture)


## Troubleshooting

### "The release 'latest' does not exist"

This is a transient error from the `sensiolabs/minify-bundle` when GitHub's API is unavailable. The frontend will still work — just retry later or skip the `asset-map:compile` step.

### Container name conflicts

If you get errors about container names already in use, ensure you've set a unique `ETFS_PROJECT_NAME` in `.env.local`.

### Slow performance on macOS

Ensure Docker Desktop is using VirtioFS and Docker VMM (see Docker Desktop Configuration above). The setup script will warn you if these aren't configured.


## Stopping and Restarting

```bash
# Stop containers
docker compose down

# Restart containers
docker compose up -d

# Rebuild containers (after Dockerfile changes)
docker compose up --build -d
```


## Resetting Everything

```bash
# Stop and remove containers, volumes, and networks
docker compose down -v

# Re-run setup
mise run setup
```
