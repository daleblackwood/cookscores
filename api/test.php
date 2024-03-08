<?php
// End-to-end API tests. Local only: php api/test.php [base_url]

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

$base = rtrim(isset($argv[1]) ? $argv[1] : 'http://localhost/api/', '/') . '/';
$host = trim(parse_url($base, PHP_URL_HOST), '[]');
if (in_array($host, array('localhost', '127.0.0.1', '::1')) == FALSE) {
    fwrite(STDERR, "Refusing to run against non-local host: $host\n");
    exit(1);
}

$failed = 0;

function check($name, $ok) {
    global $failed;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . "\n";
    if ($ok == FALSE) {
        $failed++;
    }
}

function api($method, $query = array(), $body = null) {
    global $base;
    $url = $base . 'index.php?' . http_build_query(array('m' => $method) + $query);
    $context = stream_context_create(array('http' => array(
        'method' => $body === null ? 'GET' : 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $body === null ? '' : json_encode($body),
        'ignore_errors' => true,
    )));
    $stream = fopen($url, 'r', false, $context);
    $headers = stream_get_meta_data($stream)['wrapper_data'];
    $response = stream_get_contents($stream);
    fclose($stream);
    preg_match('#^HTTP/\S+ (\d+)#', $headers[0], $match);
    return array((int)$match[1], json_decode($response, true));
}

$game = 'test' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 10);
$pid = 'p' . bin2hex(random_bytes(8));

list($code, $res) = api('time');
check('time returns version', $code == 200 && isset($res['version']));

list($code) = api('score', array(), array('game' => $game, 'pid' => $pid));
check('score without score is 400', $code == 400);

list($code) = api('score', array(), array('game' => $game, 'pid' => $pid, 'score' => 'abc'));
check('non-numeric score is 400', $code == 400);

list($code, $top) = api('score', array(), array(
    'game' => $game, 'pid' => $pid, 'score' => 100, 'level' => '1',
    'replay' => 'replay-data', 'pname' => 'tester'
));
check('score is accepted', $code == 200 && isset($top['sid']));
check('top score is position 1', $top['position'] == 1);

list($code, $second) = api('score', array(), array(
    'game' => $game, 'pid' => 'other' . $pid, 'score' => 50, 'level' => '1'
));
check('lower score is position 2', $second['position'] == 2);

list($code, $board) = api('scoreboard', array('game' => $game, 'level' => '1'));
check('scoreboard lists both scores', $code == 200 && count($board) == 2);
check('scoreboard is in order with names', $board[0]['sid'] == $top['sid'] && $board[0]['pname'] == 'TESTER');
check('scoreboard hides pid', array_key_exists('pid', $board[0]) == FALSE);

list($code, $replay) = api('replay', array('game' => $game, 'scoreId' => $top['sid']));
check('replay is returned', $code == 200 && $replay['replay'] == 'replay-data');

list($code) = api('replay', array('game' => $game, 'scoreId' => 0));
check('missing replay is 404', $code == 404);

list($code) = api('name', array('game' => $game, 'pid' => $pid, 'pname' => 'renamed'));
list($code, $board) = api('scoreboard', array('game' => $game, 'level' => '1'));
check('name is updated', $board[0]['pname'] == 'RENAMED');

list($code, $stats) = api('stats', array('game' => $game, 'playerId' => $pid));
check('stats returns best score', $code == 200 && $stats[0]['best'] == 100);

list($code) = api('nosuchmethod');
check('unknown method is 404', $code == 404);

echo $failed == 0 ? "All tests passed.\n" : "$failed test(s) failed.\n";
exit($failed == 0 ? 0 : 1);
