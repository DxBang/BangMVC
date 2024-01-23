<?php
namespace Bang\Network;

class URL {
	protected static
		$url,
		$_url,
		$origin,
		$_origin,
		$hashing;

	static function travel(string $url, string $origin = null, int $extensive_hashing = 0) {
		self::origin($origin);
		self::url($url);
		if (!empty(self::$_url->scheme)) return self::parseOrigin(self::$url, $extensive_hashing);
		if (substr(self::$url, 0, 2) == '//')
			return self::parseOrigin(self::$_origin->scheme.':'.self::$url, $extensive_hashing);
		if (substr(self::$url, 0, 1) == '/')
			return self::parseOrigin(self::$_origin->scheme.'://'.self::$_origin->host.self::$url, $extensive_hashing);
		if ((substr(self::$url, 0, 1) == '#') || (substr(self::$url, 0, 1) == '?'))
			return self::parseOrigin(self::$origin.self::$url, $extensive_hashing);
		$path = preg_replace('/\/[^\/]*$/', '', self::$_origin->path);
		$abs = self::$_origin->host.$path.'/'.self::$_url->path;
		$abs = preg_replace('/(\/\.?\/)/', '/', $abs);
		$abs = preg_replace('/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $abs);
		return self::parseOrigin(self::$_origin->scheme.'://'.$abs, $extensive_hashing);
	}
	static function parseOrigin(string $url, int $extensive_hashing = 0) {
		$url = self::parse($url, $extensive_hashing);
		$url->referer = self::$_origin;
		$url->same = (object) [
			'host' => (self::$_origin->host == $url->host) ? true : false,
			'domain' => (self::$_origin->domain == $url->domain) ? true : false,
			'scheme' => (self::$_origin->scheme == $url->scheme) ? true : false,
		];
		return $url;
	}
	static function origin(string $origin = null, int $extensive_hashing = 0) {
		if (is_object(self::$origin) && self::$origin->url == $origin) return;
		$origin = explode('#', $origin)[0];
		if (self::$origin != $origin) {
			self::$origin = $origin;
			self::$_origin = self::parse(self::$origin, $extensive_hashing);
		}
	}
	static function url(string $url, int $extensive_hashing = 0) {
		if (is_object(self::$url) && self::$url->url == $url) return;
		if (self::$url != $url) {
			self::$url = $url;
			self::$_url = self::parse(self::$url, $extensive_hashing);
		}
	}
	static function parse(string $url, int $extensive_hashing = 0) {
		$_url = (object) parse_url($url);
		$_url->validation = 0;
		$_url->url = '';
		$_url->root = '';
		$_url->full = '';
		$_url->human = '';
		$_url->fragment = $_url->fragment ?? null;
		if (!empty($_url->scheme)) {
			switch ($_url->scheme) {
				case 'javascript':
				case 'tel':
				case 'data':
				case 'mailto':
				case 'about':
					$_url->url = $_url->scheme.':';
				break;
				case 'http':
				case 'https':
				case 'ftp':
				case 'ftps':
				case 'chrome':
				case 'edge':
				case 'android-app':
					$_url->url = $_url->scheme.'://';
				break;
				default:
					throw new \Exception('failed to understand scheme: '.$url, 1);
				break;
			}
			$_url->web = preg_match('/^http(s)?$/', $_url->scheme) ? true : false;
			$_url->encrypted = preg_match('/^(http|ftp)s$/', $_url->scheme) ? true : false;
		}
		if (!empty($_url->host)) {
			$_url->host = strtolower($_url->host);
			$domain = Host::parse($_url->host);
			if (empty($domain->domain)) {
				throw new \Exception('failed to read domain from: '.$url, 1);
				return;
			}
			$_url->domain = $domain->domain;
			if (!empty($domain->ip)) {
				$_url->ip = $domain->ip;
			}
			else {
				$_url->host = $domain->host;
				$_url->sub = $domain->sub;
				$_url->idn = $domain->idn;
				$_url->intl = $domain->intl;
				$_url->tld = $domain->tld;
				$_url->name = $domain->name;
				$_url->hostname = $domain->host;				
			}
			if (!empty($_url->user)) {
				$_url->url .= $_url->user;
				if ($_url->pass) {
					$_url->url .= ':'.$_url->pass;
				}
				$_url->url .= '@';
			}
			$_url->human .= $_url->host;
		}
		if (!empty($_url->port)) {
			$_url->human .= ':'.$_url->port;
		}
		if (empty($_url->path)) {
			$_url->path = '/';
		}
		if (!empty($_url->path)) {
			$_url->human .= $_url->path;
		}
		if (!empty($_url->query)) {
			$_url->human .= '?'.$_url->query;
		}
		$_url->url .= $_url->human;
		if ($extensive_hashing) {
			if (is_int($extensive_hashing)) {
				self::hashing(
					$extensive_hashing
				);
			}
			$_url->hash = (object) [
				'url' => $_url->url ? self::$hashing->hash($_url->url) : null,
				'host' => $_url->host ? self::$hashing->hash($_url->host) : null,
				'domain' => $_url->domain ? self::$hashing->hash($_url->domain) : null,
			];
		}
		else {
			$_url->md5 = md5($_url->url);
			$_url->sha1 = sha1($_url->url);
		}
		$_url->full = $_url->url;
		if ($_url->fragment) {
			$_url->full .= '#'.$_url->fragment;
		}
		return $_url;
	}
	static function hashing(int $flags = null) {
		if (is_null(self::$hashing)) {
			self::$hashing = new \Bang\Hash($flags);
		}
		else if (!is_null($flags)) {
			self::$hashing->flags($flags);
		}
		return self::$hashing;
	}
	static function isSecured(string $url) {
		$url = self::parse($url);
		switch ($url->scheme) {
			case 'https':
			case 'ftps':
				return true;
			break;
			default:
				return false;
			break;
		}
	}
}
