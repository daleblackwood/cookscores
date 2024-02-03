<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../app/Database.php');
require_once(__DIR__ . '/replay.php');

function run_name() {
    $game = Utils::urlSafe(Utils::getVar('game', ''));
    $pid = Utils::getVar('pid');
    $pname = Utils::getVar('pname');
    return set_name($game, $pid, $pname);
}

function set_name($game, $pid, $pname) {
    if (Utils::isString($game) == FALSE) {
        throw new ApiError('No game', 404);
    }
    if (Utils::isString($pid) == FALSE) {
        throw new ApiError('No player id', 401);
    }
    if (Utils::isString($pname) == FALSE) {
        throw new ApiError('No player name', 401);
    }
    $pname = strtoupper($pname);
    $updated = Database::upsert(
        "players",
        array(
            "pid" => $pid,
            "pname" => $pname
        ),
        array("pid")
    );
    return $updated;
}
?>
