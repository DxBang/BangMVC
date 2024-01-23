<?php
namespace Bang\Security;
use Bang\Core;
use Bang\Network\URL;

class AccessControl {
	static
		$secure;
	static function allowSubDomain(bool $secure = true) {
		header('Access-Control-Allow-Origin: '.($secure ? 'https://' : 'http://').self::allowedOrigin(
			'*.'.Core::domain(),
			self::originHost()
		), true);
	}
	static function allowDomain(bool $secure = true) {
		header('Access-Control-Allow-Origin: '.($secure ? 'https://' : 'http://').self::allowedOrigin(
			Core::domain(),
			self::originHost()
		), true);
	}

	static function isSecured(string $url) {
		return preg_match('/^https:\/\//', $url);
	}
	static function methods(string $methods = 'GET, POST') {
		header('Access-Control-Allow-Methods: '.strtoupper($methods), true);
	}
	static function originHost() {
		if ($origin = self::origin()) {
			self::$secure = self::isSecured($origin);
			return URL::parse($origin)->host;
		}
	}
	static function origin() {
		return !empty($_SERVER['HTTP_ORIGIN'])
			? $_SERVER['HTTP_ORIGIN']
			: (
				!empty($_SERVER['HTTP_REFERER'])
				? $_SERVER['HTTP_REFERER']
				: (
					!empty($_SERVER['HTTP_HOST'])
					? $_SERVER['HTTP_HOST']
					: null
				)
			);
	}
	static function allowedOrigins(array $allowed, string $origin = null) {
		if (is_null($origin)) {
			$origin = self::originHost();
		}
		$r = 'none';
		foreach ($allowed as $allow) {
			$r = self::allowedOrigin($allow, $origin);
			if ($r != 'none') {
				break;
			}
		}
		if ($r != 'none' && $r != '*') {
			$r = (self::$secure ? 'https://' : 'http://').$r;
		}
		return $r;
	}
	static function allowedOrigin(string $allowed, string $origin = null) {
		if ($allowed == '*') return '*';
		if (strpos($allowed, '*') !== false) {
			$allowed = str_replace('*', '(.*)', $allowed);
		}
		$allowed = '/^'.$allowed.'$/';
		return self::regExAllowedOrigin($allowed, $origin);
	}
	static function regExAllowedOrigin(string $regex, string $origin = null) {
		if (preg_match($regex, $origin)) {
			return $origin;
		}
		return 'none';
	}
}
