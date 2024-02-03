<?php
// BlackwoodAPI by Dale Blackwood

require_once(__DIR__ . '/Utils.php');

class Request 
{
	public $data = null;
	public $response = '';
	public $startTime = 0;
	public $inited = FALSE;

	function init() {
		$this->startTime = microtime(TRUE);

		ini_set('html_errors', FALSE);

		header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		$this->output = null;
		$this->inited = TRUE;
	}

	function run() {
		if ($this->inited == FALSE) {
			$this->init();
		}

		$methodName = Utils::urlSafe((Utils::getVar('m', '')));
		if (isset($methodName) == false || strlen($methodName) < 1) {
			$methodName = 'time';
		}

		$methodPath = 'methods/'.$methodName.'.php';

		@$imported = include($methodPath);

		if ($imported == false) {
			$this->fail('Method not found: '.$methodName, 404);
			return;
		}

		$result = null;
		try {
			$runMethodName = 'run_'.$methodName;
			$result = call_user_func($runMethodName);
		}
		catch (Exception $e) {
			if (get_class($e) == "ApiError") {
				$this->fail($e->getMessage(), $e->getCode());
				return;
			}
			throw $e;
		}

		$this->succeed($result);
	}

	function succeed($data = null) {
		$result = null;

		if (isset($data)) {
			$result = $data;
		}

		header('HTTP/1.0 200 OK');

		$this->end($result);
	}

	function fail($error, $errorCode = 400) {
		if (isset($this->output['result'])) {
			unset($this->output['result']);
		}

		http_response_code($errorCode);

		$result = array('error' => $error);

		$this->end($result);
	}

	function end($result) {
		$generateTime = microtime(TRUE) - $this->startTime;

		header('Content-Type: application/json');
		header('GenerateTime: '.$generateTime);
		header('Generator: BlackwoodAPI');
		header('Time: '.Utils::getTime());

		$this->data = $result;

		$jsonOptions = 0;
		if (Utils::isDebug()) {
			$jsonOptions = JSON_PRETTY_PRINT;
		}

		$this->response = json_encode($this->data, $jsonOptions);
		echo($this->response);
		die();
	}
}

?>
