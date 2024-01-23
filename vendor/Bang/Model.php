<?php
namespace Bang;

abstract class Model {
	public
		$buffered,
		$connect = false,
		$throw = false;
	protected static
		$pdo,
		$string,
		$array,
		$execute,
		$query,
		$result,
		$bound,
		$insertId,
		$count,
		$_buffered,
		$_throw;
	function __construct() {
		self::$_buffered = $this->buffered;
		self::$_throw = $this->throw;
		if ($this->connect) {
			self::connect();
		}
	}
	final static function connect() {
		if (self::isConnected()) return;
		try {
			self::$pdo = new \PDO(
				Config::get('dsn','pdo'),
				Config::get('username','pdo'),
				Config::get('password','pdo'),
				[
					\PDO::ATTR_ERRMODE						=> \PDO::ERRMODE_EXCEPTION,
					\PDO::ATTR_DEFAULT_FETCH_MODE			=> Config::get('fetch','pdo') ?? \PDO::FETCH_LAZY,
					\PDO::ATTR_EMULATE_PREPARES				=> false,
					\PDO::ATTR_PERSISTENT					=> false, #Config::get('persistent','pdo') ?? true,
					\PDO::MYSQL_ATTR_INIT_COMMAND			=> "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'",
					\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY		=> is_bool(self::$_buffered) ? self::$_buffered : Config::get('buffered','pdo') ?? true,
				]
			);
		}
		catch (\PDOException $e) {
			self::exception($e);
		}
	}
	final static function unbuffered() {
		return self::$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
	}
	final static function buffered() {
		return self::$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
	}
	final function query(string $s) {
		self::prepare($s);
		return $this;
	}
	final function parameters(array $a = null) {
		self::execute($a);
		return $this;
	}
	final function result() {
		return self::$query->fetch();
	}
	final function results() {
		return self::$query->fetchAll();
	}
	final static function isConnected() {
		return is_object(self::$pdo);
	}
	final static function close() {
		if (self::$query)
			self::$query->closeCursor();
	}
	final static function clear() {
		return self::close();
	}
	final static function prepare(string $s) {
		try {
			if (!self::isConnected()) self::connect();
			self::$query = self::$pdo->prepare($s);
			return self::$query;
		}
		catch (\PDOException $e) {
			return self::exception($e);
		}
	}
	final static function execute(array $a = null) {
		try {
			if (!self::$query) {
				throw new Exception('Bad Query');
			}
			self::autoBind($a);
			self::$execute = self::$query->execute($a);
			self::$count = self::$query->rowCount();
		}
		catch (\PDOException $e) {
			return self::exception($e);
		}
		return self::$execute;
	}
	final static function insertId(string $name = null) {
		return self::$pdo->lastInsertId($name);
	}
	final static function get(string $s, array $a = null, $fetch = null, bool $close = false) {
		self::$string = $s;
		self::$array = $a;
		try {
			$prep = self::prepare($s);
			$exec = self::execute($a);
			if (is_string($fetch)) {
				$q = self::fetch();
				if ($close) {
					self::close();
				}
				return $q->{$fetch};
			}
			if (is_array($fetch)) {
				$q = self::fetch();
				$r = (object) [];
				foreach ($fetch as $v) {
					if (isset($q->{$v})) {
						$r->{$v} = $q->{$v};
					}
				}
				if ($close) {
					self::close();
				}
				return $r;
			}
			if (is_bool($fetch) && ($fetch)) {
				return self::fetchAll();
			}
			if (is_null($fetch) && ((isset($a['limit']) && $a['limit'] === 1) || (isset($a[':limit']) && $a[':limit'] === 1))) {
				return self::fetch();
			}
		#	self::errorCheck();
			return self::$query;
		}
		catch (\PDOException $e) {
			print('PDOException @ get'.PHP_EOL);
			return self::exception($e);
		}
	}
	final static function put(string $s, array $a = null, string $i = null) {
		self::$string = $s;
		self::$array = $a;
		try {
			$prep = self::prepare($s);
			$exec = self::execute($a);
		}
		catch (\PDOException $e) {
			return self::exception($e);
		}
		if ($i) {
			return (object) [$i => self::insertId($i)];
		}
		return $exec;
	}
	final static function add(string $s, array $a = null, string $i = null) {
		return self::put($s, $a, $i);
	}
	final static function set(string $s, array $a = null) {
		return self::put($s, $a);
	}
	final static function rem(string $s, array $a = null) {
		return self::put($s, $a);
	}
	final static function autoBind(array $a = null) {
		if (is_null($a)) return;
		if (!self::$query) return;
		if (self::$bound) return;
		#var_dump('before autoBind', self::debug());
		foreach ($a as $k => $v) {
			switch (gettype($v)) {
				case 'array':
					#echo 'array: '.$k.' '.$v.PHP_EOL;
					self::bind($k, $v, \PDO::PARAM_LOB);
				break;
				case 'boolean':
					#echo 'boolean: '.$k.' '.$v.'	'.\PDO::PARAM_BOOL.PHP_EOL;
					self::bind($k, $v, \PDO::PARAM_BOOL);
				break;
				case 'string':
					#echo 'string: '.$k.' '.$v.PHP_EOL;
					self::bind($k, $v, \PDO::PARAM_STR);
				break;
				case 'integer':
					#echo 'integer: '.$k.' '.$v.PHP_EOL;
					self::bind($k, $v, \PDO::PARAM_INT);
				break;
				case 'null':
					#echo 'null: '.$k.' '.$v.PHP_EOL;
					self::bind($k, $v, \PDO::PARAM_NULL);
				break;
			}
		}
		#print_r(self::$query->debugDumpParams());
		#var_dump('after autoBind', self::debug());
	}
	final static function bind(string $k, $v, int $t) {
		#echo 'bind "'.$k.'"="'.$v.'" as '.$t.PHP_EOL;
		return self::$query->bindParam($k, $v, $t);
	#	var_dump('bind '.$k, self::debug());
	}
	final static function fetch() {
		if (self::$query) {
			try {
				return self::$query->fetch(\PDO::FETCH_OBJ);
			}
			catch (\PDOException $e) {
				self::exception($e);
			}
		}
	}
	final static function fetchAll() {
		if (self::$query) {
			try {
				return self::$query->fetchAll(\PDO::FETCH_OBJ);
			}
			catch (\PDOException $e) {
				self::exception($e);
			}
		}
	}
	final static function count() {
		return self::$count;
	}
	final static function quoted(array $array) {
		return implode(',', array_map('self::quote', $array));
	}
	final static function quote(string $string, int $type = \PDO::PARAM_STR) {
		return self::$pdo->quote($string, $type);
	}
	private static function exception(\Exception $e) {
		if (self::$_throw) {
			throw new \Exception($e->getMessage(), $e->getCode(), $e);
		}
		echo 'PDO ERROR: '.$e->getCode().' : '.$e->getMessage().PHP_EOL;
		foreach ($e->getTrace() as $k => $trace) {
			if (!empty($trace['file']) && $trace['file'] != __FILE__) {
				echo 'Trace '.$k.':'.PHP_EOL
					.'	'.($trace['file'] ?? 'unknown').' ('.($trace['line'] ?? '-').')'.PHP_EOL
					.'		'.($trace['class'] ?? 'unknown').($trace['type'] ?? '').($trace['function'] ?? 'unknown').PHP_EOL;
				#print_r($trace);
			}
		}
		if (Config::get('debug')) {
			var_dump('pdo->errorInfo', self::$pdo ? self::$pdo->errorInfo() : null);
			#var_dump('string', self::$string);
			#var_dump('array', self::$array);
			#var_dump('query', self::$query);
			var_dump('execute', self::$execute);
			var_dump('count', self::$count);
			#print_r($e);
			print_r(self::debug());
		}
		exit;
	}
	final static function serverInfo() {
		$attributes = array(
			"AUTOCOMMIT", "ERRMODE", "CASE", "CLIENT_VERSION", "CONNECTION_STATUS",
			"ORACLE_NULLS", "PERSISTENT", "PREFETCH", "SERVER_INFO", "SERVER_VERSION",
			"TIMEOUT"
		);
		echo '<dl>';
		foreach ($attributes as $val) {
			echo "<dt>PDO::ATTR_$val:</dt>\n";
			echo "<dd>".self::$pdo->getAttribute(constant("PDO::ATTR_$val")) . "</dd>\n";
		}
		echo '</dl>';
	}
	static function errorCheck() {
		if ((int) self::$query->errorCode()) {
			var_dump(self::$query->errorCode());
			var_dump(self::$query->errorInfo());
			#throw new Exception(self::$pdo->errorInfo(), self::$pdo->errorCode());
		}
		if ((int) self::$pdo->errorCode()) {
			var_dump(self::$pdo->errorCode());
			var_dump(self::$pdo->errorInfo());
			#throw new Exception(self::$pdo->errorInfo(), self::$pdo->errorCode());
		}
	}
	static function debug() {
		echo 'string: '.self::$string.PHP_EOL;
		#echo 'array: '.var_export(self::$array, true).PHP_EOL;
		echo self::$query ? self::$query->debugDumpParams() : null;
	}
	final static function file(string $file) {
		if (!file_exists($file)) return null;
		if (!is_readable($file)) return false;
		return file_get_contents($file);
	}
	final static function json(string $file, bool $asArray = false) {
		return json_decode(self::file($file), $asArray);
	}
	final static function fileLines(string $file) {
		return preg_split('/[\n|\r]+/', trim(self::file($file)));
	}
	final static function fileWords(string $file) {
		return preg_split('/\s+/', trim(self::file($file)));
	}
	function __sleep() {
		return ['dsn', 'username', 'password'];
	}
	function __wakeup()	{
		self::connect();
	}
}
