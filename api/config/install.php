<?php
// Run from the command line: php api/config/install.php

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

require_once(__DIR__ . '/../version.php');

if (file_exists(__DIR__ . '/dbc.php') == FALSE) {
    fwrite(STDERR, "Missing config/dbc.php - copy dbc.example.php or run install.sh.\n");
    exit(1);
}

require_once(__DIR__ . '/../app/Database.php');

$tables = array("
    CREATE TABLE IF NOT EXISTS scores (
        sid INT NOT NULL AUTO_INCREMENT,
        game VARCHAR(50) NOT NULL,
        pid VARCHAR(50) NOT NULL,
        score INT NOT NULL,
        level VARCHAR(32) DEFAULT NULL,
        seed INT DEFAULT 0,
        time INT DEFAULT 0,
        ts TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        data JSON DEFAULT NULL,
        PRIMARY KEY(sid)
    )", "
    CREATE TABLE IF NOT EXISTS replays (
        sid INT NOT NULL,
        replay MEDIUMTEXT NOT NULL,
        data JSON DEFAULT NULL,
        PRIMARY KEY(sid),
        FOREIGN KEY (sid) REFERENCES scores(sid) ON DELETE CASCADE
    )", "
    CREATE TABLE IF NOT EXISTS players (
        pid VARCHAR(100) NOT NULL,
        pname VARCHAR(100) NOT NULL,
        email VARCHAR(100) DEFAULT NULL,
        password VARCHAR(100) DEFAULT NULL,
        data JSON DEFAULT NULL,
        PRIMARY KEY(pid)
    )", "
    CREATE TABLE IF NOT EXISTS meta (
        k VARCHAR(50) NOT NULL,
        v VARCHAR(255) NOT NULL,
        PRIMARY KEY(k)
    )"
);

foreach ($tables as $sql) {
    Database::execute($sql);
}

Database::upsert('meta', array('k' => 'version', 'v' => CCSCORES_VERSION), array('k'));

echo "ccscores " . CCSCORES_VERSION . " installed.\n";
