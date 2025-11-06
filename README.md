# ETFS App Starter Kit

A very-opinionated, very-batteries-included Symfony application starter kit from the Enterprise Tooling for Symfony project.


## Current status

This is a work-in-progress technical preview — it's ready to use, but the developer experience is not yet streamlined.


### Scratchbook

    echo "ETFS_PROJECT_NAME=foobar" >> .env
    docker compose up --build
    mise run in-app-container mise trust
    mise run in-app-container mise install
    mise run console doctrine:database:create
    mise run console doctrine:migrations:migrate --no-interaction
    mise run frontend


## Background

This is a project from [the DX·Tooling initiative](https://dx-tooling.org/).
