<?php
// BlackwoodAPI by Dale Blackwood

require_once(__DIR__ . '/Request.php');

class ApiError extends Exception
{
	public function __construct($msg, $code = 0, Exception $old = null) {
		parent::__construct($msg, $code, $old);
	}
	
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
}

class Utils
{   
	static public $logs = array();
	static public $mainMethod = null;
	static public $opInfo = null;

	static function buildTokenArray($data, $ignore = null) {
		$tokens = array();

		$keys = array_keys($data);
		$keyCount = sizeof($keys);

		for ($i=0; $i<$keyCount; $i++) {
			$key = $keys[$i];
			if ($ignore != null && self::isMatchOrInSet($key, $ignore)) {
				continue;
			}
			array_push($tokens, $key);
		}

		return $tokens;
	}

	static function objectProperty($object, $key, $default=null) {
		if (isset($object[$key]))
			return $object[$key];

		return $default;
	}

	static function isMatchOrInSet($value, $matchOrSet) {
		if (is_array($matchOrSet) == FALSE) {
			return $value == $matchOrSet;
		}

		$count = sizeof($matchOrSet);
		for ($i=0; $i<$count; $i++) {
			if ($matchOrSet[$i] == $value) {
				return TRUE;
			}
		}

		return FALSE;
	}
	
	static function getJson($varName) {
		$dataStr = self::getVar($varName, null);
		if ($dataStr == null) {
			return null;
		}
		$data = json_decode($dataStr, TRUE);
		return $data;
	}

	static function getJsonBody() {
		$dataStr = file_get_contents('php://input');
		if (self::isString($dataStr) == FALSE) {
			return null;
		}
		$data = json_decode($dataStr, TRUE);
		return $data;
	}

	static function getVar($key, $default=null) {
		$result = $default;
		$mode = null;

		if (isset($_POST[$key])) {
			$result = $_POST[$key];
			$mode = 'POST';
		}
		else if (isset($_GET[$key])) {
			$result = $_GET[$key];
			$mode = 'GET';
		}

		if (self::isDebug()) {
			$logValue = $result == null ? 'NULL' : $result;
			if ($mode == null) {
				self::log("Var: $key defaulted to $logValue");
			}
			else {
				self::log("Var: $key is $logValue");
			}
		}

		return $result;
	}

	static function getVars($keys, $default=null) {
		$result = array();

		for ($i=0; $i < count($keys); $i++) {
			$key = $keys[$i];
			$value = self::getVar($key, $default);

			if ($value == "" || $value == null) continue;

			$result[$key] = $value;
		}

		return $result;
	}

	static function getTime($timestamp = -1) {
		if ($timestamp < 0) {
			$timestamp = time();
		}
		return date(DATE_ATOM, $timestamp);
	}

	static function copyKeys($obj, $keys) {
		$result = array();
		foreach ($keys as $key) {
			$result[$key] = $obj[$key];
		}
		return $result;
	}

	static function requireVar($key) {
		$value = self::getVar($key, null);
		if ($value == null) {
			throw new ApiError("Required $key absent.", 400);
		}
		return $value;
	}

	static function getEnv($key) {
		if (isset($_ENV[$key]) == FALSE) {
			return "";
		}
		return $_ENV[$key];
	}

	static function getData() {
		$dataStr = file_get_contents("php://input");
		if (isset($dataStr) == false || strlen($dataStr) < 1) {
			return null;
		}
		$data = json_decode($dataStr, TRUE);
		return $data;
	}

	static function isDebug() {
		if (getenv('CCSCORES_DEBUG') === '1') {
			return TRUE;
		}
		$addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		return $addr === '127.0.0.1' || $addr === '::1';
	}

	static function urlSafe($str) {
		$res = preg_replace("/[^a-zA-Z\_]/", "", $str);
		$res = strtolower($res);
		return $res;
	}

	static function isString($str) {
		if (isset($str) == FALSE)
			return FALSE;
		
		if (strlen($str) < 1)
			return FALSE;

		return TRUE;
	}

	static function lookup($obj, $path) {
		$pathBits = explode('.', $path);
		for ($i=0; $i<sizeof($pathBits); $i++) {
			$pathBit = $pathBits[$i];
			if (isset($obj[$pathBit])) {
				$obj = $obj[$pathBit];
			} else {
				return null;
			}
		}
		return $obj;
	}

	static function getString($obj, $path) {
		$value = self::lookup($obj, $path);
		if (self::isString($value)) 
			return $value;
		return null;
	}

	static function requireVars($keys) {
		$values = self::getVars($keys, null);
		foreach ($keys as $key) {
			if (isset($values[$key]) == FALSE) {
				throw new ApiError("Required $key in set absent.", 400);
			}
		}
		return $values;
	}

	static function log($msg) {
		if (self::isDebug() == FALSE) {
			return;
		}

		array_push(self::$logs, $msg);
	}

	static function beginOpInfo() {
		self::$opInfo = array();
		self::$opInfo['startTime'] = microtime(TRUE);
		self::$opInfo['startMemory'] = memory_get_usage();
	}

	static function getOpInfo() {
		self::$opInfo['time'] = microtime(TRUE) - self::$opInfo['startTime'];
		self::$opInfo['memory'] = memory_get_usage() - self::$opInfo['startMemory'];
		return self::$opInfo;
	}

	static function logOpInfo($prefix) {
		$info = self::getOpInfo();
		$time = round($info['time'] * 10) / 10;
		$memory = round($info['memory'] / 1024 / 1024 * 10) / 10;
		self::log("OpInfo: > $prefix: time: $time s, memory: $memory mb");
	}

	static function isCacheValid($key, $maxAge) {
		$path = self::getCachePath($key);
		if (file_exists($path) == FALSE) {
			self::log("Invalid cache path $path.");
			return FALSE;
		}

		$now = time();
		$saveTime = filemtime($path);

		return $now - $saveTime < $maxAge;
	}

	static function getFromCache($key, $maxAge) {
		$path = self::getCachePath($key);

		if (self::isCacheValid($key, $maxAge) == FALSE) {
			unlink($path);
			return null;
		}

		$cacheStr = file_get_contents($path);
		$data = json_decode($cacheStr);

		$data['modified'] = filemtime($path);

		return $data;
	}

	static function storeInCache($key, $data) {
		$path = self::getCachePath($key);

		$dirPath = self::getCachePath('');
		if (file_exists($dirPath) == FALSE) {
			mkdir($dirPath);
		}

		$json = json_encode($data);

		$str = $json;
		file_put_contents($path, $str);

		self::log("Stored in cache ".$key);
	}

	static function getCachePath($key) {
		return './cache/'.$key;
	}

}

?>
