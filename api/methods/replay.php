<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../app/Database.php');

function run_replay() {
    $game = Utils::urlSafe(Utils::getVar('game', ''));
    $sid = Utils::getVar('scoreId');
    return get_replay($game, $sid);
}

function get_replay($game, $sid) {
    if (Utils::isString($game) == FALSE) {
        throw new ApiError('No game', 404);
    }
    if (isset($sid) == false) {
        throw new ApiError('No score id', 401);
    }
    $result = Database::fetchOne('
        SELECT replays.sid, replays.replay, scores.game, scores.pid, scores.score,
            scores.level, scores.seed, scores.time, scores.ts, scores.data
        FROM replays
        INNER JOIN scores ON scores.sid = replays.sid
        WHERE replays.sid = :sid
        AND scores.game = :game
        LIMIT 1
    ', array("sid" => $sid, "game" => $game));
    if ($result == false) {
        throw new ApiError('Replay not found', 404);
    }
    if (Utils::isString($result['data'])) {
        $result['data'] = json_decode($result['data'], true);
    }
    return $result;
}

function add_replay($game, $sid, $replay) {
    $inserted = Database::insert("replays", array(
        'sid'       => $sid,
        'replay'    => $replay
    ));
    return $inserted;
}
?>