<?php

require_once(__DIR__ . '/../app/Utils.php');

$ERROR = '';

function run() {
    if (file_exists(__DIR__ . '/dbc.php')) {
        installDb();
    } else {
        die("Invalid configuration.");
    }
}

function installDb() {
    require_once(__DIR__ . '/../app/Database.php');
    Database::execute("
        CREATE TABLE scores (
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
        );
        CREATE TABLE replays (
            sid INT NOT NULL,
            replay TEXT NOT NULL,
            data JSON DEFAULT NULL,
            FOREIGN KEY (sid) REFERENCES scores(sid)
        );
        CREATE TABLE players (
            pid VARCHAR(100) NOT NULL,
            pname VARCHAR(100) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            password VARCHAR(100) DEFAULT NULL,
            data JSON DEFAULT NULL,
            PRIMARY KEY(pid)
        );
    ");
}

run();

?>