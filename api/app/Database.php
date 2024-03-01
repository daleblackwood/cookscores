<?php
// BlackwoodAPI by Dale Blackwood

require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/../config/dbc.php');

class Database
{
	static public $dbh = null;
	static public $result = null;
	static public $error = null;

	static function open() {
		if (self::$dbh != null)
			return;

		$dbc = getDbConfig();
		$connStr = sprintf("mysql:host=%s;port=%s;dbname=%s;", 
			$dbc['HOST'], 
			$dbc['PORT'], 
			$dbc['DB']
		);
		try {
			self::$dbh = new PDO($connStr, $dbc['USER'], $dbc['PASS'], array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			));
			if (Utils::isDebug()) {
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}
		catch (Exception $e) {
			throw new Exception("OPEN: " . $e->getMessage(), 500);
		}
	}

	static function sqlInsertString($data) {
		$tokens = Utils::buildTokenArray($data);
		$keyStr = implode(',', $tokens);
		$valStr = ':' . implode(',:', $tokens);
		$str = "($keyStr) VALUES ($valStr)";
		return $str;
	}

	static function sqlUpdateString($data, $ignore) {
		$tokens = Utils::buildTokenArray($data, $ignore);
		$pairs = array();
		for ($i=0; $i<sizeof($tokens); $i++) {
			$token = $tokens[$i];
			array_push($pairs, "$token=:$token");
		}
		$str = implode(',', $pairs);
		return $str;
	}

	static function execute($sql, $data = null) {
		self::$error = null;
		self::$result = null;

		$logSQL = $sql;

		self::open();
		$sth = self::$dbh->prepare($sql);
		$sth->setFetchMode(PDO::FETCH_ASSOC);

		if (isset($data)) {
			foreach ($data as $key => $value) {
				$bindKey = ':' . $key;
				$logValue = is_numeric($value) ? $value : "'$value'";

				$sth->bindValue($bindKey, $value);
				$logSQL = str_replace($bindKey, $logValue, $logSQL);
			}
		}

		error_log($logSQL);

		$execution = FALSE;
		$error = null;
		try {
			$execution = $sth->execute();
		}
		catch (Exception $e) {
			$error = $e->getMessage();
			error_log("Query error: " . $logSQL);
		}

		if ($error != null) {
			throw new Exception("DB ERROR: " . $error, 500);
		}

		if ($execution == FALSE) {
			throw new Exception("DB ERROR.", 500);
		}

		return $sth;
	}

	static function insert($table, $data, $returnKey=null) {
		$insertStr = self::sqlInsertString($data);
		$sql = "INSERT INTO $table $insertStr";
		self::execute($sql, $data);
		$result = self::fetchOne("SELECT LAST_INSERT_ID() AS id")["id"];
		return $result;
	}

	static function upsert($table, $data, $conflictKeys) {
		$insertStr = self::sqlInsertString($data);
		$updateStr = self::sqlUpdateString($data, $conflictKeys);
		$conflictStr = implode(',', $conflictKeys);
		$sql = "INSERT INTO $table $insertStr" .
			" ON DUPLICATE KEY UPDATE $updateStr";
		self::execute($sql, $data);
		$result = self::fetchOne("SELECT LAST_INSERT_ID() AS id")["id"];
		return $result;
	}

	static function fetchOne($sql, $data = null, $method = null) {
		$sth = self::execute($sql, $data);
		if ($sth == null) {
			return self::fail("No result.");
		}
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		return self::succeed($result);
	}

	static function fetchAll($sql, $data = null, $method = null) {
		Utils::beginOpInfo();
		$sth = self::execute($sql, $data);
		if ($sth == null) {
			return self::fail("No results.");
		}
		$result = $sth->fetchAll();
		Utils::logOpInfo("Fetch all");
		return self::succeed($result);
	}

	static function fetchCount($sql, $data = null) {
		$result = self::fetchOne($sql, $data, PDO::FETCH_NUM);
		return $result[0];
	}

	static function fail($error) {
		Utils::log($error);
		error_log("DB ERROR: $error");
		self::$error = $error;
		self::$result = null;
		return null;
	}

	static function succeed($data) {
		self::$error = null;
		self::$result = $data;
		return $data;
	}
}

?>
