# Wordpress Docker Compose

Inspired by [lyzs90/vuewp](https://github.com/lyzs90/vuewp) repository structure, this is a [Docker Compose](https://docs.docker.com/compose/install/) project with config files, scripts and automations to run and deploy **[wordpress](https://wordpress.org/)** project. Using this, any theme (including SPA themes) needs be a separated repository, or a `composer` dependency.


## Features

- [Docker](https://www.docker.com/what-docker) for a deterministic and easy to set up development environment
- [Composer](https://getcomposer.org/) for install wp plugins/themes and others PHP libraries, and run **phpunit** tests. The command is installed in [wordpress-tools](http://github.com/mfdeveloper/docker-images/tree/master/wp-tools) docker image
- Custom shell scripts for [wordpress-tools](http://github.com/mfdeveloper/docker-images/tree/master/wp-tools) image commands, similar to [Docker aliases](https://github.com/akarzim/zsh-docker-aliases)
- [PHPUNIT](https://phpunit.de/) for unit/integration wordpress plugin tests
- [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer) to detect violations of a defined coding standard.
- [WP-CLI](https://wp-cli.org/) A command-line tool to **wordpress** management


## Usage

- Set up a [Docker](https://www.docker.com/community-edition) and [Docker Compose](https://docs.docker.com/compose/install/) environment
- Clone this repo `git clone https://github.com/mfdeveloper/docker-images.git`
- Copy the script aliases from `./docker-aliases` to any folder of your **`$PATH`** environment variable (e.g `~/.docker/aliases`)
- Install the dependencies `composer install`
- Start Wordpress and MySQL `docker-compose up -d wordpress db`
- Add your container ip (e.g localhost) to `.env.example` and rename to `.env` (or change the `.env` directly)
- Access the Wordpress server at this url [http://<CONTAINER_IP>:8000](http://<CONTAINER_IP>:8000) and perform the famous 5-minute install
- Enjoy it!

## Mysql backup

Into `./db-backup` directory, you can backup/restore all databases 

- **dumpSql():** Generate a `mysql.databases.sql` file with all databases

 > **Usage:** `dumpSql wordpress-docker-compose_1`

 - **restoreSql():** Restore a `mysql.databases.sql` file into mysql `db` service

 > **Usage:** `restoreSql wordpress-docker-compose_1`

 - **dumpFolder():** Generate a `mysql.databases.tar.gz` compressed file with all databases

 > **Usage:** `dumpSql wordpress-docker-compose_1`

  - **restoreFolder():** Restore a `mysql.databases.sql` file into mysql `db` service

 > **Usage:** `restoreSql wordpress-docker-compose_1`


## Plugins

### Unit test

These scripts are used to configure/run [phpunit](https://phpunit.de/) tests of one or more **wordpress plugins**.

> **PS:** For run the commands below, you need stay on this root project folder, relative to [`composer.json`](./composer.json) file:

- **installWpTests**: Run `install-wp-tests.sh` file, to create `wordpress_test` database for tests

> **Usage:** `composer installWpTests`

- **test**: Run tests of a [wordpress plugin](https://developer.wordpress.org/plugins/intro). Use `--plugin` argument to pass a plugin name

> **Usage:** `composer test -- --plugin <my-wp-plugin>`

## Credits
- Adapted from [lyzs90/vuewp](https://github.com/lyzs90/vuewp), and separated after the discussion on [lyzs90/vuewp!4](https://github.com/lyzs90/vuewp/pull/4) pull request
