<?php
namespace Bang\Network;

class Proxy {
	protected
		$host,
		$port,
		$buffer,
		$fp;
	function open(string $host, int $port, int $buffer = 1024) {
		$this->host = $host;
		$this->port = $port;
		$this->buffer = $buffer;
		$this->fp = fsockopen($host, $port);
		return $this->connected();
	}
	function send(string $string) {
		echo 'send: '.$string.PHP_EOL;
		fputs($this->fp, $string);
		$data = '';
		while (!feof($this->fp)) {
			$data .= fgets($this->fp, $this->buffer);
		}
		return $data;
	}
	function close() {
		fclose($this->fp);
	}
	function host() {
		return $this->host;
	}
	function port() {
		return $this->port;
	}
	function connected() {
		return is_resource($this->fp);
	}
}