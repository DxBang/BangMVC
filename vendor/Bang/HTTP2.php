<?php
namespace Bang;

class HTTP2 {
	private
		static $data = [];
	static function add(string $file, string $as, string $rel = 'preload') {
		#if (preg_match('/^https?:\/\//', $file)) return;
		$file = explode("\t", $file)[0];
	#	$rel = 'preload;time='.microtime(1);
		self::$data[$as][$file] = $rel;
	}
	static function clear() {
		self::$data = [];
	}
	static function push() {
		if (empty(self::$data)) return;
		if (headers_sent()) return;
		foreach (self::$data as $as => $items) {
			foreach ($items as $file => $rel) {
				header('Link: <'.$file.'>;rel='.$rel.';as='.$as, false);
			}
		}
		return true;
	}
	static function addCluster(array $files, string $as, string $rel = 'preload') {
		foreach ($files as $file) {
			self::add($file, $as, $rel);
		}
	}
}
