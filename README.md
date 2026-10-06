# The v4 API

This repository holds the code for the Goteo **v4** API.

> **NOTE**: Review the trusted certificates by OpenSSL. See: https://github.com/goteo/org.goteo.api/issues/43

## Installation

This application requires [Docker](https://docs.docker.com/get-docker/) and the [Docker Compose](https://docs.docker.com/compose/install/) plugin. Additionally, if you want to use the OAuth capabilities, you need to have [Node.js](https://nodejs.org/en/download) and `npm` installed in your machine.

### 1. Clone or download this repository.

```shell
git clone https://github.com/goteo/org.goteo.api
cd org.goteo.api
```

### 2. Build the Docker containers.

```shell
docker compose up -d --build
```

#### 2.1 Configuring the containers.

> NOTE: **The following steps are optional**, unless you need to specify ports or configure the default Docker build in some way you can skip to step [2.2. Post build setup](#22-post-build-setup).

To avoid ownership issues for files generated inside the PHP container the default Compose config will try to export your user's and group's ID, or **1000** if it can't find them, to the user inside the container. If that is not your ID you can pass your actual IDs using the env vars `UID` and  `GID`.

In a similar fashion you can override the nginx container binding to the ports **:8090** (for http) and **:8091** (for https) on your host with the env vars `APP_HTTP_PORT` and `APP_HTTPS_PORT`.

- Option A. Using a custom `.env.local` file.
```dotenv
# .env.local

UID=1001
GID=1001

APP_HTTP_PORT=8080
APP_HTTPS_PORT=8443
```

Then feed your custom env vars to Compose:
```shell
docker compose --env-file .env.local up -d --build
```

- Option B. Passing the variables through the shell.
```shell
export APP_HTTP_PORT=8080 && export APP_HTTPS_PORT=8443

# Dynamic user and group id
export UID=$(id -u) && export GID=$(id -g)

# Custom user and group id
export UID=1001 && export GID=1001

docker compose up -d --build
```

#### 2.2 Post-build setup.

After the Docker containers are first built, you'll need to finish the PHP setup.

```shell
# Install composer.json dependencies
docker compose exec php composer install

# Create the database
# May throw an error if it already exists, no further action required if so
docker compose exec php bin/console doctrine:database:create

# Update the database schema
docker compose exec php bin/console doctrine:migrations:migrate

# Generate keys to sign the JWTs for OAuth
docker compose exec php bin/console league:oauth2-server:generate-keypair

# Install and generate JavaScript assets
npm install
npm run build
```

## Usage

The app should be live at [http://localhost:8090](http://localhost:8090) (or your specified ports). Keep in mind that the API address is [/v4](http://localhost:8090/v4).

You can access a real-time build of the OpenAPI spec file for the API at [/v4/docs.json](http://localhost:8090/v4/docs.json), to be used, for example, with API development suites such as Postman. This file will be up to date with most of your latest changes.

The API also builds a small ReDoc frontend to act as documentation at [/v4/docs](http://localhost:8090/v4/docs). A Swagger UI version of the docs is also available at [/v4/docs?ui=swagger_ui](http://localhost:8090/v4/docs?ui=swagger_ui).

For quick Docker access you can use the `bin/docker` shortcut to quickly `exec` anything into one of the containers. It expects the name of the Docker Compose service as first parameter, then you can pass whatever it is that you wish to exec into that container, e.g:

- Login to mysql CLI: `bin/docker mariadb mysql -u goteo -pgoteo goteo`
- Debug the symfony services: `bin/docker php bin/console debug:container`
- List app custom commands: `bin/docker php bin/console list app`

## Testing

This app uses PHPUnit.

```shell
bin/docker php bin/phpunit
```

Beware you might experience the following error:

```
RuntimeException: Error running "doctrine:database:create": Could not create database `goteo_test` for connection named default
An exception occurred while executing a query: SQLSTATE[42000]: Syntax error or access violation: 1044 Access denied for user 'goteo'@'%' to database 'goteo_test'
 in /app/vendor/zenstruck/foundry/src/Test/AbstractSchemaResetter.php:33
```

To fix it:

1. Login into the MariaDB instance as *root*.
```shell
bin/docker mariadb mysql -u root -pgoteo goteo
```

2. Grant *goteo* all privileges.
```mysql
GRANT ALL PRIVILEGES ON *.* TO 'goteo'@'%';
```

## Debugging

[Xdebug](https://xdebug.org/) is built for you in the PHP container of the Docker setup. A configuration file for VSCode is also shipped in the repo.

Common issues you might find while developing the application are:

### 1. The application gives an error on cold starts.

When you first build the application after long times or having introduced breaking-changes you might cause a [cold start](https://en.wikipedia.org/wiki/Cold_start_(computing)) which can induce out-of-memory issues. To fix it, simply warm-up the cache layer.

```shell
# Remove the old cache
docker compose exec php bin/console cache:clear
# Warm-up a new cache
docker compose exec php bin/console cache:warmup
```

This should usually fix your out-of-memory issues. If it does not then you have likely introduced memory leaks that shall be fixed. Refer to the Xdebug documentation on profiling for help.

### 2. The application breaks after pulling remote changes.

Sometimes, when updating your local repository with remote changes, you might find that the application does not work anymore. When this happens the application will complain loudly and clearly about the issue, give it a read to the error messages. Most of the time it will be one of two issues that can be solved by running one command.

```shell
# Application dependencies have changed
docker compose exec php composer install

# Database schema has changed
docker compose exec php bin/console doctrine:migrations:migrate
```
