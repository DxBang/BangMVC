<?php
namespace Bang\Server;

class WebSocket {
	protected
		$run,
		$pid,
		$socket,
		$clients = [];

	function __construct(string $address = null, int $port = 8000) {
		if (!extension_loaded('sockets')) {
			exit('missing sockets extension');
		}
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!empty($address)) {
			return $this->create($address, $port);
		}
	}
	function create(string $address, int $port = 8000) {
		$this->run = tempnam(sys_get_temp_dir(), 'websocket');
		$this->bind($address, $port)
			->connect($address, $port);
		return $this;
	}
	function bind(string $address, int $port = 8000) {
		socket_bind($this->socket, $address, $port);
		return $this;
	}
	function connect(string $address, int $port = 8000) {
		socket_connect($this->socket, $address, $port);
		return $this;
	}
	function listen() {}
	function accept() {

	}
	function send(string $send) { }
	function write() {}
	function error() {}
	function debug() {
		return [
			'socket' => $this->socket,
			'clients' => $this->clients,
		];
	}
	function run() {
		while ($this->run) {
			if (!$this->checkServer()) { $this->run = false; }
		}
	}
	function shutdown() {
		if (!empty($this->run) && file_exists($this->run)) {
			return unlink($this->run);
		}
	}
	function checkServer() {
		return (file_exists($this->pid));
	}
	function __destroy() {
		return $this->close();
	}
	function close() {
		return socket_close($this->socket);
	}
	function log(string $log) {}



}
