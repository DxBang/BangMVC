<?php
namespace Bang;

class LogAnalyzer {
	const
		REMOTE_IP = 1,
		IDENT = 2,
		AUTH = 3,
		DATETIME = 4,
		METHOD = 5,
		PATH = 6,
		PROTOCOL = 7,
		CODE = 8,
		SIZE = 9,
		REFERER = 10,
		USERAGENT = 11,
		GZIP = 12,
		JSON_OUTPUT = JSON_UNESCAPED_SLASHES;

	protected
		$files = 0,
		$lines = 0,
		$parses = 0,
		$limit = 100,
		$entries = [],
		$matches = [];

	function __construct(int $column = 0, string $match = null) {
		if (!is_null($column) && !is_null($match)) {
			$this->match($column, $match);
		}
	}
	function limit(int $limit = 100) {
		$this->limit = $limit;
		return $this;
	}
	function match(int $column = 0, string $match = null) {
		$this->matches[$column] = $match;
		return $this;
	}
	function check(array $entry) {
		$f = 0;
		foreach($this->matches as $k => $regex) {
			if (preg_match($regex, $entry[$k])) {
				$f++;
			}
		}
		return (count($this->matches) === $f);
	}
	function file(string $file) {
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		switch ($ext) {
			case 'log':
				$this->fileLOG($file);
			break;
			case 'gz':
				$this->fileGZ($file);
			break;
		}
		return $this;
	}
	function fileGZ($file) {
		if (!file_exists($file)) throw new Exception('missing GZ file: '.$file, 1);
		$fp = @gzopen($file, "r");
		if ($fp) {
			$this->files++;
			$done = false;
			while (($line = gzgets($fp, 4096)) !== false) {
				if ((count($this->entries) >= $this->limit)) {
					$done = true;
					break;	
				}
				if (($p = $this->parse(trim($line))) && $this->check($p)) {
					$this->entries[] = $p;
					continue;
				}
			}
			gzclose($fp);
		}
	}
	private function fileLOG($file) {
		if (!file_exists($file)) throw new Exception('missing log file: '.$file, 1);
		$fp = @fopen($file, "r");
		if ($fp) {
			$this->files++;
			$done = false;
			while (($line = fgets($fp, 4096)) !== false) {
				if ((count($this->entries) >= $this->limit)) {
					$done = true;
					break;	
				}
				if (($p = $this->parse(trim($line))) && $this->check($p)) {
					$this->entries[] = $p;
					continue;
				}
			}
			fclose($fp);
		}
	}
	private function readLine($line) {

	}
	function parse(string $line) {
		/*
		185.62.189.177 - - [23/Oct/2018:20:30:48 +0000] "GET /xmlrpc.php HTTP/1.1" 301 162 "-" "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"



		kasino-live
		104.227.248.18 - - [06/Mar/2019:06:29:34 +0000] "HEAD /tftpphone/$MA.cfg HTTP/1.1" 404 0 "-" "-"
		104.227.248.18 - - [06/Mar/2019:06:31:12 +0000] "HEAD /tftpphone/000000000000.cfg HTTP/1.1" 404 0 "-" "-"
		217.74.215.130 - - [06/Mar/2019:06:32:37 +0000] "GET /wp-content/uploads/2019/02/fillin-mac-1.jpg HTTP/1.1" 304 0 "http://new-kasinohai.com/kaikki-nettikasinot/" "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.119 Safari/537.36"
		217.74.215.130 - - [06/Mar/2019:06:32:49 +0000] "GET /wp-content/uploads/2019/02/hai6-1-300x300.jpg HTTP/1.1" 304 0 "http://new-kasinohai.com/kaikki-nettikasinot/" "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.119 Safari/537.36"
		*/
		$this->lines++;
		preg_match(
			'/^(?<remote_ip>\S+) (?<ident>\S+) (?<auth>\S+) \[(?<datetime>([^:]+):(\d+:\d+:\d+) ([^\]]+))\] \"(?<method>\S+) (?<path>.*?) (?<protocol>\S+)\" (?<code>\S+) (?<size>\S+) \"(?<referer>.*?)\" \"(?<agent>.*?)\"\s?(\"(?<gzip>.*?)\")?$/',
			$line,
			$m
		);
		/*
		if (!$m) {
			preg_match(
				'/^(?<remote_ip>\S+) (?<ident>\S+) (?<auth>\S+) \[(?<datetime>([^:]+):(\d+:\d+:\d+) ([^\]]+))\] \"(?<method>\S+) (?<path>.*?) (?<protocol>\S+)\" (?<code>\S+) (?<size>\S+) \"(?<referer>.*?)\" \"(?<agent>.*?)\"$/',
				$line,
				$m
			);
		}
		*/
		if (!$m) return;
		$this->parses++;
		$datetime = gmdate('Y-m-d H:i:s O e', strtotime($m['datetime']));
		$unixtime = strtotime($datetime);
		return [
			$unixtime,
			$m['remote_ip'],
			($m['ident'] != '-' ? $m['ident'] : null),
			($m['auth'] != '-' ? $m['auth'] : null),
			$datetime,
			$m['method'],
			$m['path'],
			$m['protocol'],
			(int) $m['code'],
			(int) $m['size'],
			($m['referer'] != '-' ? $m['referer'] : null),
			$m['agent'],
			isset($m['gzip']) ? $m['gzip'] : null,
		];
	}
	function entries() {
		return $this->entries;
	}
	function matches() {
		return $this->matches;
	}
	function json() {
		header('Content-Type: application/json');
		return json_encode(
			$this->__debugInfo(),
			self::JSON_OUTPUT
		);
	}
	function __debugInfo() {
		return (object) [
			'meta' => (object) [
				'files' => $this->files,
				'lines' => $this->lines,
				'parses' => $this->parses,
			],
			'matches' => $this->matches,
			'entries' => $this->entries,
		];
	}
	function __toString() {
		return json_encode(
			$this->__debugInfo(),
			self::JSON_OUTPUT
		);
	}
	function sortByDate() {
		usort($this->entries, function($a, $b) {
			if ($a[0] === $b[0]) return 0;
			return ($a[0] > $b[0]) ? 1 : 0;
		});
		return $this;
	}
}
