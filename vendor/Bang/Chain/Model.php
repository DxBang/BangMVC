<?php
namespace Bang\Chain;
use \Bang\Core;

class Model {
	protected
		$pdo,
		$statement;

	function __construct(string $configFile = 'db.json') {
		/*
		format:
		{
			"dsn":"mysql:host={HOST};dbname={DBNAME};charset=UTF8",
			"username":"{USERNAME}",
			"password":"{PASSWORD}",
			"prefix":"",
			"fetch":5,
			"persistent":true
		}
		*/
		if (!file_exists($configFile)) throw new \Exception('missing config file: '.$configFile, 1);
		if (!$conf = json_decode(file_get_contents($configFile), false)) throw new \Exception('error processing config file as json', 2);
		if (empty($conf->dsn) || empty($conf->username) || empty($conf->password)) throw new \Exception('missing required config values', 3);
		$this->connect(
			$conf->dsn,
			$conf->username,
			$conf->password,
			[
				\PDO::ATTR_ERRMODE				=> \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE	=> $conf->fetch ?? \PDO::FETCH_OBJ,
				\PDO::ATTR_EMULATE_PREPARES		=> false,
				\PDO::ATTR_PERSISTENT			=> $conf->persistent ?? true,
			]
		);
	}
	private function connect(string $dsn, string $user, string $password, array $options) {
		$this->pdo = new \PDO($dsn, $user, $password, $options);
		if (!$this->pdo) throw new \Exception('failed to start pdo', 1);
	}
	function get(string $query, array $data = null) {
		$this->query($query)
			->data($data);
		if (isset($data['limit']) && $data['limit'] === 1) return $this->fetch();
		return $this->statement;
	}

	function add(string $query, array $data, string $id = null) {
		return $this->update($query, $data, $id);
	}
	function set(string $query, array $data) {
		return $this->update($query, $data);
	}
	function rem(string $query, array $data) {
		return $this->update($query, $data);
	}
	function update(string $query, array $data, string $id = null) {
		try {
			$this
				->query($query)
				->data($data);
			if (!empty($id))
				return $this
					->insertId($id);
			return $this
				->statement
				->rowCount();
		}
		catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	function query(string $query) {
		$this->statement = $this->pdo->prepare($query);
		return $this;
	}
	function data(array $data = null) {
		$this->statement->execute($data);
		return $this;
	}
	function fetch() {
		return $this->statement->fetch();
	}
	function fetchAll() {
		return $this->statement->fetchAll();
	}
	function insertId(string $name) {
		return $this->pdo->lastInsertId($name);
	}

    function debug() {
		return $this->statement->debugDumpParams();
    }
}
