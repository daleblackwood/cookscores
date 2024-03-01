<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../app/Database.php');
require_once(__DIR__ . '/replay.php');
require_once(__DIR__ . '/name.php');

function run_score() {
    $data = Utils::getJsonBody();
    $sid = add_score($data);
    $position = get_score_entry(Utils::urlSafe($data['game']), $sid);
    return $position;
}

function add_score($data) {
    if (isset($data) == FALSE) {
        throw new ApiError('No data', 401);
    }
    $scoreKeys = array('game', 'pid', 'score');
    foreach ($scoreKeys as $key) {
        if (isset($data[$key]) == FALSE) {
            throw new ApiError('Requires '.$key, 400);
        }
    }
    $game = Utils::urlSafe($data['game']);
    $scoreData = Utils::copyKeys($data, $scoreKeys);
    $scoreData['game'] = $game;
    foreach (array('level', 'seed', 'time') as $key) {
        if (isset($data[$key])) {
            $scoreData[$key] = $data[$key];
        }
    }
    /*
    if (isset($data['data'])) {
        $scoreData['data'] = json_encode($data['data']);
    }
    */
    $insertId = Database::insert('scores', $scoreData);
    if (isset($data['replay'])) {
        add_replay($game, $insertId, $data['replay']);
    }
    if (Utils::getString($data, 'pname')) {
        set_name($game, $data['pid'], $data['pname']);
    }
    return $insertId;
}

function get_score_entry($game, $sid) {
    $result = Database::fetchOne("
        SELECT scores.*, pname, (
            SELECT COUNT(*) + 1 FROM scores higher
            WHERE higher.game = scores.game
            AND higher.level <=> scores.level
            AND (higher.score > scores.score
                OR (higher.score = scores.score AND higher.sid < scores.sid))
        ) AS position
        FROM scores
        LEFT JOIN players ON scores.pid = players.pid
        WHERE scores.sid = :sid
        AND scores.game = :game
    ", array(
        'sid' => $sid,
        'game' => $game
    ));
    return $result;
}

?>
