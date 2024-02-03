<?php
require_once(__DIR__ . '/../app/Database.php');

function run_test() {
    return Database::fetchAll("SELECT * FROM scores");
}
?>