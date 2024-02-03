<?php
require_once(__DIR__ . '/../app/Utils.php');
require_once(__DIR__ . '/../app/Database.php');
require_once(__DIR__ . '/replay.php');
require_once(__DIR__ . '/score.php');

function run_scoreboard() {
    $game = Utils::urlSafe(Utils::getVar('game'));
    $level = Utils::getVar('level');
    $options = Utils::getVars(array('sid', 'seed', 'target'));
    $result = get_scoreboard($game, $level, $options);
    return $result;
}
function get_scoreboard($game, $level, $options) {
    if (Utils::isString($game) == false) {
        throw new ApiError('No game', 404);
    }
    if (Utils::isString($level) == false) {
        throw new ApiError('No level', 404);
    }
    $target = 1;
    if (isset($options['sid'])) {
        $entry = get_score_entry($game, $options['sid']);
        if ($entry) {
            $target = $entry['position'];
        }
    }
    else if (isset($options['target'])) {
        $target = intval($options['target']);
    }
    $queryValues = array(
        'level' => $level,
        'game' => $game,
        'target' => $target
    );
    $seedClause = '';
    if (isset($options['seed'])) {
        $seed = $options['seed'];
        if (Utils::isString($seed)) {
            $seedClause = 'AND scores.seed = :seed';
            $queryValues['seed'] = $seed;
        }
    }
    $rows = Database::fetchAll("
        SELECT * FROM (
            SELECT scores.*, players.pname,
                ROW_NUMBER() OVER (ORDER BY scores.score DESC, scores.sid ASC) AS position
            FROM scores
            LEFT JOIN players ON players.pid = scores.pid
            WHERE scores.game = :game
            AND scores.level = :level
            $seedClause
        ) ranked
        ORDER BY ABS(position - :target) ASC, position ASC
        LIMIT 10
    ", $queryValues);
    usort($rows, function($a, $b) {
        return $a['position'] - $b['position'];
    });
    return parse_scoreboard_rows($rows);
}
/*
function get_scoreboard($game, $levelId, $seed, $scoreId = '') {
    if (Utils::isString($game) == FALSE) {
        throw new Exception('No game', 404);
    }
    if (Utils::isString($levelId) == FALSE) {
        throw new Exception('No level', 404);
    }
    $rows = array();
    $nearTotal = 0;
    if (Utils::isString($scoreId)) {
        $entry = get_score_entry($game, $scoreId);
        $nearTotal = $entry['position'];
    }

    $queryValues = array(
        'near_total' => $nearTotal, 
        'levelId' => $levelId,
    );
    $seedClause = '';
    if (Utils::isString($seed)) {
        $seedClause = 'AND SEED = :seed';
        $queryValues['seed'] = $seed;
    }
    $rows = Database::fetchAll('
        WITH near_scores AS (
            SELECT *, 
                ROW_NUMBER() OVER(ORDER BY scores.total DESC) AS position,
                players.pname
            FROM '.Utils::gameTable($game, 'scores').' scores
            LEFT JOIN '.Utils::gameTable($game, 'players').' players ON players.pid = scores.pid
            WHERE levelId = :levelId
            '.$seedClause.'
            ORDER BY ABS(total - :near_total) ASC
            LIMIT 10
        )
        SELECT * FROM near_scores
        ORDER BY position ASC
    ', $queryValues);
    return parse_scoreboard_rows($rows);
}
*/

function parse_scoreboard_rows($rows) {
    $result = array();
    if (isset($rows) && sizeof($rows) > 0) {
        foreach ($rows as $key => $row) {
            if (isset($row['data'])) {
                $row['data'] = json_decode($row['data'], true);
            }
            $result[$key] = $row;
        }
    }
    return $result;
}
?>
