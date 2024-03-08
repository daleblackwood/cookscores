#!/bin/bash
# Usage: run.sh [test|serve]
set -e

mysqld_safe > /dev/null 2>&1 &
until mysqladmin ping --silent 2> /dev/null; do sleep 1; done

mysql -uroot -e "
    CREATE DATABASE IF NOT EXISTS ccscores;
    CREATE USER IF NOT EXISTS 'ccscores'@'%' IDENTIFIED BY 'ccscores';
    GRANT ALL ON ccscores.* TO 'ccscores'@'%';"

DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=ccscores DB_USER=ccscores DB_PASS=ccscores \
    /opt/ccscores/install.sh

if [ "$1" = "serve" ]; then
    exec apache2-foreground
fi

apache2ctl start
php /opt/ccscores/api/test.php http://localhost/api/
