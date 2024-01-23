<?php
namespace Bang\Logs;

class Parser {
	const
		REMOTE_IP = 'remote_ip',
		IDENT = 'ident',
		AUTH = 'auth',
		DATETIME = 'datetime',
		METHOD = 'method',
		PATH = 'path',
		PROTOCOL = 'protocol',
		CODE = 'code',
		SIZE = 'size',
		REFERER = 'referer',
		USERAGENT = 'agent',
		GZIP = 'gzip',
		JSON_OUTPUT = JSON_UNESCAPED_SLASHES;

	public
		$dateFrom,
		$dateTo,
		$skipped;
	protected
		$file,
		$type,
		$files = 0,
		$lines = 0,
		$parses = 0,
		$entries = 0,
		$limit = 100,
		$timeZone,
		$data = [],
		$matches = [],
		$detectors,
		$detected = [];

	function __construct(int $column = 0, string $match = null) {
		$this->skipped = (object) [
			'dateFrom' => 0,
			'dateTo' => 0,
			'match' => 0,
			'check' => 0,
		];
		$this->timeZone('Zulu');
		$this->detectors = (object) [];
		$this->detector(
			'standard',
			'/^(?<remote_ip>\S+)'.
			' (?<ident>\S+)'.
			' (?<auth>\S+)'. # [
			' \[(?<datetime>([^:]+):(\d+:\d+:\d+) ([^\]]+))\]'.
			' \"(?<method>\S+) (?<path>.*?) (?<protocol>\S+)\"'.
			' (?<code>\S+)'.
			' (?<size>\S+)'.
			' \"(?<referer>.*?)\"'.
			' \"(?<agent>.*?)\"'.
			'$/'
		);
		if (!is_null($column) && !is_null($match)) {
			$this->match($column, $match);
		}
	}
	function timeZone(string $timezone) {
		$this->timeZone = new \DateTimeZone($timezone);
	}
	function limit(int $limit = 100) {
		$this->limit = $limit;
		return $this;
	}
	function match(string $column, string $match = null) {
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
	function dateFrom(string $dateTime) {
		$this->dateFrom = new \DateTime($dateTime, $this->timeZone);
	}
	function dateTo(string $dateTime) {
		$this->dateTo = new \DateTime($dateTime, $this->timeZone);
	}
	function detector(string $name, string $regex) {
		$this->detectors->{$name} = $regex;
		$this->detected[$name] = 0;
	}
	function parse(string $line, bool $check = false, bool $asObject = false) {
		$this->lines++;
		foreach ($this->detectors as $name => $regex) {
			if (preg_match(
				$regex,
				$line,
				$entry
			)) {
				foreach ($entry as $k => $v) {
					if (is_int($k)) unset($entry[$k]);
				}
				break;
			}
		}
		if (empty($entry)) {
			$this->skipped->match++;
			return;
		}
		$this->detected[$name]++;
		$this->parses++;
		if (!empty($entry['datetime'])) {
			$datetime = new \DateTime($entry['datetime']);
			$datetime->setTimezone($this->timeZone);
			if ($this->dateFrom && ($this->dateFrom >= $datetime)) {
				$this->skipped->dateFrom++;
				return;
			};
			if ($this->dateTo && ($this->dateTo < $datetime)) {
				$this->skipped->dateTo++;
				return;
			}
			$entry['date'] = $datetime;
			$entry['datetime'] = $datetime->format('c');
			$entry['unixtime'] = $datetime->format('U');
		}
		if ($check) {
			if ($this->check($entry)) {
				$this->entries++;
				return $entry;
			}
			$this->skipped->check++;
			return false;
		}
		return $entry;
	}
	function toObject($entry) {
		if (!is_array($entry)) return false;
		return (object) $entry;
	}
	function data() {
		return $this->data;
	}
	function matches() {
		return $this->matches;
	}
	function json() {
		header('Content-Type: application/json');
		return json_encode(
			(object) $this->__debugInfo(),
			self::JSON_OUTPUT
		);
	}
	function __debugInfo() {
		return [
			'meta' => (object) [
				'files' => $this->files,
				'lines' => $this->lines,
				'parses' => $this->parses,
				'entries' => $this->entries,
			],
			'matches' => $this->matches,
			'data' => $this->data,
		];
	}
	function __toString() {
		return json_encode(
			(object) $this->__debugInfo(),
			self::JSON_OUTPUT
		);
	}
	function sortByDate() {
		usort($this->data, function($a, $b) {
			if ($a[0] === $b[0]) return 0;
			return ($a[0] > $b[0]) ? 1 : 0;
		});
		return $this;
	}
}