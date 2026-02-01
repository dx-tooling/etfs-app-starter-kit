# ETFS App Starter Kit

[![CI](https://img.shields.io/github/actions/workflow/status/dx-tooling/etfs-app-starter-kit/ci.yml?branch=main)](https://github.com/dx-tooling/etfs-app-starter-kit/actions/workflows/ci.yml)

A very-opinionated, very-batteries-included Symfony application starter kit from the Enterprise Tooling for Symfony project.


## Vision

Build serious business applications on top of Symfony with:

- **Enforced Architecture** — Vertical slice architecture with strict boundaries, optimized for mid- to large-sized, functionally complex applications while minimizing the risk of brittle legacy code
- **Comprehensive Testing** — Multi-layer test setup (unit, integration, application, architecture) with PHPStan Level 10 for maximum type safety
- **Outstanding DX** — Streamlined tooling via Mise, sane defaults, and useful boilerplates for a low-frustration, productive development experience


## Prerequisites

- macOS (Apple Silicon or Intel)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) — configured with VirtioFS and Docker VMM for optimal performance
- [Mise](https://mise.jdx.dev/) — install via `brew install mise` or see [mise.jdx.dev](https://mise.jdx.dev/getting-started.html)


## Quick Start

```bash
# Clone the repository
git clone git@github.com:dx-tooling/etfs-app-starter-kit.git
cd etfs-app-starter-kit

# Trust mise configuration
mise trust

# Configure your project name (optional, but recommended)
echo "ETFS_PROJECT_NAME=my-project" > .env.local

# Bootstrap everything
mise run setup
```

The setup command will:
1. Check Docker Desktop performance settings
2. Build and start Docker containers
3. Install PHP dependencies via Composer
4. Install Node.js dependencies
5. Create and migrate the database
6. Build frontend assets
7. Run quality checks and tests
8. Open the application in your browser


## Available Commands

All commands are run via Mise:

| Command | Description |
|---------|-------------|
| `mise run setup` | Bootstrap complete development environment |
| `mise run quality` | Run all quality tools (PHPStan, ESLint, Prettier, PHP-CS-Fixer) |
| `mise run tests` | Run all test suites |
| `mise run tests:frontend` | Run frontend tests (supports `--watch` and `--coverage`) |
| `mise run frontend` | Build frontend assets (Tailwind, TypeScript, AssetMapper) |
| `mise run console <cmd>` | Run Symfony console commands |
| `mise run composer <cmd>` | Run Composer commands |
| `mise run npm <cmd>` | Run npm commands |
| `mise run db` | Connect to the database |
| `mise run browser` | Open the application in your browser |
| `mise run in-app-container <cmd>` | Run any command inside the app container |


## Tech Stack

### Backend
- **PHP 8.4** with strict typing
- **Symfony 7.4** framework
- **Doctrine ORM** with MariaDB
- **PHPStan Level 10** for static analysis
- **Pest** for testing (with architecture tests)

### Frontend
- **TypeScript** for type-safe JavaScript
- **Stimulus.js** for modest, HTML-first interactivity
- **Tailwind CSS** for styling
- **Symfony AssetMapper** (no webpack/vite bundler)
- **Vitest** for frontend testing

### Infrastructure
- **Docker Compose** for local development
- **Mise** for tool management and task running
- **Nginx** as web server
- **MariaDB** as database


## Project Structure

```
├── .cursor/rules/      # AI-assisted development guidelines
├── .mise/tasks/        # Mise task definitions
├── assets/             # Frontend assets (TypeScript, CSS, Stimulus controllers)
├── config/             # Symfony configuration
├── docker/             # Docker configuration (Dockerfile, nginx, php.ini)
├── docs/               # Project documentation
│   ├── archbook.md     # Architecture documentation
│   ├── devbook.md      # Development guidelines
│   ├── frontendbook.md # Frontend development guide
│   └── ...
├── migrations/         # Doctrine migrations
├── src/                # Application source code (vertical slices)
├── tests/              # Test suites
│   ├── Application/    # End-to-end application tests
│   ├── Architecture/   # Architecture constraint tests
│   ├── Integration/    # Integration tests
│   ├── Unit/           # Unit tests
│   └── frontend/       # Frontend tests
└── public/             # Web root
```


## Configuration

### Environment Variables

The project uses Symfony's standard `.env` file hierarchy:

- `.env` — Default values (committed)
- `.env.local` — Local overrides (not committed)
- `.env.test` — Test environment defaults

Key variables:
- `ETFS_PROJECT_NAME` — Used for Docker container/volume naming (set in `.env.local` to avoid conflicts)
- `DATABASE_*` — Database connection settings
- `APP_ENV` — Application environment (`dev`, `test`, `prod`)


## Documentation

Detailed documentation is available in the `docs/` folder:

- **[archbook.md](docs/archbook.md)** — Architecture decisions and patterns
- **[devbook.md](docs/devbook.md)** — Development workflow and conventions
- **[frontendbook.md](docs/frontendbook.md)** — Frontend development guide (Stimulus, TypeScript)
- **[techbook.md](docs/techbook.md)** — Technical specifications
- **[runbook.md](docs/runbook.md)** — Operations and deployment


## AI-Assisted Development

The `.cursor/rules/` directory contains guidelines for AI-assisted development in Cursor IDE, covering:

- Architecture boundaries and vertical slices
- PHP code standards and type safety
- DTO patterns and data flow
- Frontend development with Stimulus
- Database and Doctrine conventions
- Development workflow


## Background

This is a project from [the DX·Tooling initiative](https://dx-tooling.org/).

The starter kit is built on top of:
- [etfs-shared-bundle](https://github.com/dx-tooling/etfs-shared-bundle) — Core infrastructure and Mise tasks
- [etfs-webui-bundle](https://github.com/dx-tooling/etfs-webui-bundle) — Web UI components and Twig templates
