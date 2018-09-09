#!/bin/sh

BASEDIR=$(dirname "$0")
SQL_BACKUP_NAME="mysql.databases"

# REFERENCE: https://gist.github.com/spalladino/6d981f7b33f6e0afe6bb
dumpSql () {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"
    else
        echo "Generating .sql backup from CONTAINER: $1"
        echo "Destination: ${BASEDIR}"
        docker exec -it $1 sh -c 'exec mysqldump --all-databases -u root -p"$MYSQL_ROOT_PASSWORD" > /backup/'${SQL_BACKUP_NAME}'.sql'
    fi
}

# REFERENCE: https://loomchild.net/2017/03/26/backup-restore-docker-named-volumes/
# REFERENCE 2: https://gist.github.com/spalladino/6d981f7b33f6e0afe6bb
dumpFolder () {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"
    else
        echo "Generating .tar.gz backup of: /var/lib/mysql from CONTAINER: $1"
        docker exec -it $1 sh -c 'tar -czvf /backup/'${SQL_BACKUP_NAME}'.tar.gz /var/lib/mysql'
    fi
}

restoreSql() {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"

    else
        echo "Importing .sql backup of: /var/lib/mysql from CONTAINER: $1"
        docker exec -it $1 sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" < /backup/'${SQL_BACKUP_NAME}'.sql'
    fi
}

restoreFolder() {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"
    else
        echo "Importing .tar.gz backup to: /var/lib/mysql of CONTAINER: $1"
        echo "Source: ${BASEDIR}/mysql.databases.sqp"
        docker exec -it $1 sh -c 'tar -zxvf /backup/'${SQL_BACKUP_NAME}'.tar.gz -C /var/lib/mysql'
    fi
}

$*