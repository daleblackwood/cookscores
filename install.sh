#!/bin/bash
# Installs ccscores: writes api/config/dbc.php (if missing) and creates the database tables.
# Non-interactive: set DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASS beforehand.
set -e
cd "$(dirname "$0")"

CONF=api/config/dbc.php

if ! php -m | grep -qi pdo_mysql; then
    echo "PHP with the pdo_mysql extension is required." >&2
    exit 1
fi

ask() {
    if [ -z "${!1}" ]; then
        read -r -p "$2 [$3]: " value
        printf -v "$1" '%s' "${value:-$3}"
    fi
}

if [ ! -f "$CONF" ]; then
    ask DB_HOST "Database host" localhost
    ask DB_PORT "Database port" 3306
    ask DB_NAME "Database name" ccscores
    ask DB_USER "Database user" root
    if [ -z "$DB_PASS" ]; then
        read -r -s -p "Database password: " DB_PASS
        echo
    fi
    export DB_HOST DB_PORT DB_NAME DB_USER DB_PASS
    php -r '
        $c = array();
        foreach (array("HOST" => "DB_HOST", "PORT" => "DB_PORT", "DB" => "DB_NAME", "USER" => "DB_USER", "PASS" => "DB_PASS") as $k => $env) {
            $c[$k] = getenv($env);
        }
        echo "<?php\nfunction getDbConfig() {\n    return " . var_export($c, true) . ";\n}\n";
    ' > "$CONF"
    echo "Wrote $CONF"
fi

php api/config/install.php
