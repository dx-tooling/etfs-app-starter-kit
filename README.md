# ETFS App Starter Kit

A very-opinionated, very-batteries-included Symfony application starter kit from the Enterprise Tooling for Symfony project.


## Current status

This is a work-in-progress technical preview — it's ready to use, but the developer experience is not yet streamlined.


# Project vision

The idea of this project is as follows: If you want to build serious business applications on top of Symfony, you need to

- go to the Apple Store and by a macOS device,
- install `mise-en-place`, `Docker Desktop`, and an IDE of your choice,
- clone this repository and follow the instructions,

and you are good to go.


### Scratchbook

    echo "ETFS_PROJECT_NAME=foobar" >> .env
    docker compose up --build -d
    docker compose exec -ti app composer install
    mise run in-app-container mise trust
    mise run in-app-container mise install
    mise run npm install --no-save
    mise run console doctrine:database:create
    mise run console doctrine:migrations:migrate --no-interaction
    mise run frontend
    mise run quality
    mise run tests


## Background

This is a project from [the DX·Tooling initiative](https://dx-tooling.org/).
