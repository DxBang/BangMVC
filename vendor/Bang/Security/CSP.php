<?php
namespace Bang\Security;
use Bang\Config;
use Bang\Core;

class CSP {
	static
		$data;
	function __construct(object $headers = null) {
		
	}
	static function nonce(bool $nonce = false):string {
		if ($nonce) {
			return '\'nonce-'
				.self::nonce(false)
				.'\'';
		}
		return Config::has('nonce') ? Config::get('nonce') : Config::set('nonce', Core::keygen(128));
	}
	static function nonceTag(bool $space = false):string {
		return ($space ? ' ' : '').'nonce="'.Config::get('nonce').'"';
	}
	static function sha256(string $file) {
		return self::hash($file, 'sha256');
	}
	static function sha512(string $file) {
		return self::hash($file, 'sha512');
	}
	static function hash(string $file, string $algo = 'sha256') {
		if (!file_exists($file)) return;
		return '\''.$algo.'-'.base64_encode(
			hash_file(
				$algo,
				$file,
				true
			)
		).'\'';
	}
}
