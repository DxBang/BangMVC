<?php
namespace Bang\Logs;
use Bang\Geo;
use Bang\BrowserAgent;
use Bang\File;
use Bang\JSON;


class Bots {
	public
		$parser;
	protected
		$geo,
		$agent,
		$file,
		$files = 0,
		$lines = 0,
		$skipped = 0,
		$triggered = 0,
		$entries = 0,
		$google = 0,
		$fakers = 0,
		$benchmark = [],
		$regExp = [],
		$asn = [],
		$data = [];
	function __construct() {
		$this->geo = new Geo();
		$this->agent = new BrowserAgent();
		$this->parser = new Parser();
		$this->parser->limit(10);
		$this->file = new File();
	}
	function regExp(string $regexp) {
		$this->parser->match(Parser::USERAGENT, $regexp);
		return $this;
	}
	function googleBot(bool $verify = false) {
		$this->regExp[] = '/Googlebot/';
		$this->parser->match(Parser::USERAGENT, '/Googlebot/');
		return $this;
	}
	function ASN(string $ip):object {
		if (isset($this->asn[$ip])) return $this->asn[$ip];
		$this->asn[$ip] = $this->geo::asn($ip);
		return $this->asn[$ip];
	}
	function googleASN(string $ip):bool {
		$asn = $this->ASN($ip);
		if ($asn->id == 15169) return true;
		return false;
	}
	function parseDirectory(string $directory) {
		$files = scandir($directory);
		foreach ($files as $file) {
			if ($file == '.' || $file == '..') continue;
			if (preg_match('/error/', $file)) continue;
			$t = $directory.DIRECTORY_SEPARATOR.$file;
			if (is_dir($t)) continue;
			if (is_link($t)) continue;
			$this->files++;
			$this->file->open($t);
			$this->benchmark[$file] = microtime(1);
			while ($line = $this->file->read()) {
				$this->lines++;
				$line = trim($line);
				$triggered = false;
				foreach ($this->regExp as $regexp) {
					if ($triggered) break;
					$triggered = preg_match($regexp, $line);
				}
				if (!$triggered) {
					$this->skipped++;
					continue;
				}
				$this->triggered++;
				$data = $this->parser->toObject(
					$this->parser->parse($line, true)
				);
				if (empty($data)) continue;
				$ext = pathinfo(explode('?', $data->path, 2)[0], PATHINFO_EXTENSION);
				$skip = false;
				switch ($ext) {
					case 'png':
					case 'jpg':
					case 'gif':
					case 'svg':
					case 'css':
					case 'js':
						$skip = true;
					break;
				}
				if ($skip) continue;
				if (!$this->googleASN($data->remote_ip)) {
					$this->fakers++;
					continue;
				}
				$this->google++;
				$this->entries++;
				$k = $data->path;
				if (!isset($this->data[$k])) {
					$this->data[$k] = [];
				}
				$this->agent->parse($data->agent);
				$this->data[$k][] = $data->datetime.'	'.$data->code.'	'.$data->referer.'	'.$data->agent;
			}
			$this->file->close();
			$this->benchmark[$file] = microtime(1) - $this->benchmark[$file];
		}
		return $this;
	}
	function dateFrom(string $dateTime) {
		$this->parser->dateFrom($dateTime);
		return $this;
	}
	function dateTo(string $dateTime) {
		$this->parser->dateTo($dateTime);
		return $this;
	}
	function json() {
		$json = new JSON();
		return $json::json((object) [
			'meta' => (object) [
				'benchmark' => $this->benchmark,
				'dateFrom' => $this->parser->dateFrom->format('c'),
				'dateTo' => $this->parser->dateTo->format('c'),
				'skipped' => $this->parser->skipped,
				'files' => $this->files,
				'lines' => $this->lines,
				'skipped' => $this->skipped,
				'triggered' => $this->triggered,
				'entries' => $this->entries,
				'asn' => (object) [
					'google' => $this->google,
					'fakers' => $this->fakers,
				],
			],
			'data' => (php_sapi_name() != 'cli') ? $this->data : '...cli mode',
		]);
	}

	function __debugInfo():array {
		return [
			'agent' => $this->agent,
			'parser' => $this->parser,
			// 'asn' => $this->asn,
			'benchmark' => $this->benchmark,
			'data' => $this->data,
		];
	}
}
