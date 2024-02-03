<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../app/Database.php');
require_once(__DIR__ . '/replay.php');

function run_stats() {
    $game = Utils::urlSafe(Utils::getVar('game', ''));
    $pid = Utils::getVar('playerId', '');
    $result = get_stats($game, $pid);
    return $result;
}

function get_stats($game, $pid) {
    if (Utils::isString($game) == FALSE) {
        throw new ApiError('No game', 404);
    }
    if (Utils::isString($pid) == FALSE) {
        throw new ApiError('No player', 404);
    }

    return Database::fetchAll('
        SELECT level, MAX(score) AS best, COUNT(*) AS plays
        FROM scores
        WHERE game = :game
        AND pid = :pid
        GROUP BY level
    ', array('game' => $game, 'pid' => $pid));
}
?>