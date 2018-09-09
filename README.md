# Wordpress Docker Compose

Inspired by [lysz90/vuewp](https://github.com/lyzs90/vuewp) repository structure, this is a [Docker Compose](https://docs.docker.com/compose/install/) project with config files, scripts and automations to run and deploy **wordpress** project. Using this, any theme (including SPA themes) needs be to a separated repository, or a `composer` dependency.


## Features

- [Docker](https://www.docker.com/what-docker) for a deterministic and easy to set up development environment
- [Composer](https://getcomposer.org/) For install wp plugins/themes or other PHP libraries
- Custom shell scripts, similar to [Docker aliases](https://github.com/akarzim/zsh-docker-aliases) 


## Usage

- Set up a [Docker](https://www.docker.com/community-edition) and [Docker Compose](https://docs.docker.com/compose/install/) environment
- Clone this repo `git clone https://github.com/mfdeveloper/docker-images.git`
- Enter the directory `wp-docker-compose`
- Copy the script aliases from `./docker-aliases` to any folder of your **`$PATH`** environment variable
- Install the dependencies `composer install`
- Start Wordpress and MySQL `docker-compose up -d`
- Add your container ip (e.g localhost) to `.env.example` and rename to `.env` (or change the `.env` directly)
- Access the Wordpress server at this url [http://<CONTAINER_IP>:8000](http://<CONTAINER_IP>:8000) and perform the famous 5-minute install
- Enjoy it!

## Mysql backup

Into `db-backup` directory, you can backup/restore all tables from `$DB_NAME`

- **dumpSql():** Generate a `mysql.databases.sql` file with all databases from `$DB_NAME`

 > **Example:** `dumpSql wp-docker-compose_1`

 - **restoreSql():** Restore a `mysql.databases.sql` file into `$DB_NAME`

 > **Example:** `restoreSql wp-docker-compose_1`



## Credits
- Adapted from [lysz90/vuewp](https://github.com/bedakb/vuewp.git)
