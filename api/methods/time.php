<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../version.php');

function run_time() {
    return array("time" => Utils::getTime(), "version" => CCSCORES_VERSION);
}
?>
