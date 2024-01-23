<?php
namespace Bang\Network;

use Bang\Date;
use Bang\Core;
use Bang\Format;
/*
update to use:
https://github.com/whois-server-list/whois-server-list/raw/master/whois-server-list.xml
*/


class Whois {
	static
		$text,
		$json,
		$whois_path,
		$servers = [];
	const
		TEMPREFRESH = 604800,
		TEMP = SITE_DATA.'/tmp',
		WHOISDIR = BANG_DATA.'/whois',
		JSONFILE =  BANG_DATA.'/whois.servers.json';

	static function lookup(object $domain, bool $json = false, string $proxyOrType = null, string $proxyTypeOrServer = 'socks5') {
		self::$text = null;
		self::$json = null;
		if (empty($domain->tld)) throw new \Exception('what is the tld?', 1);
		if (empty($domain->domain)) throw new \Exception('what is the domain?', 2);
		$jsonFile = self::TEMP.DIRECTORY_SEPARATOR.$domain->domain.'.json';
		$textFile = self::TEMP.DIRECTORY_SEPARATOR.$domain->domain.'.txt';
		if ($json && file_exists($jsonFile) && filemtime($jsonFile) >= time()-self::TEMPREFRESH) {
			self::$text = json_decode(file_get_contents($jsonFile));
		}
		if (file_exists($textFile) && filemtime($textFile) >= time()-self::TEMPREFRESH) {
			self::$text = file_get_contents($textFile);
		}
		if (empty(self::$text)) {
			$server = self::getWhoisServer($domain->tld);
			if (Core::isURL($server[0])) {
				self::$text = self::http($server[0], $domain->domain);
			}
			elseif ($proxyOrType == 'fsocket') {
				if (!empty($proxyTypeOrServer)) {
					self::$text = self::fsocket($proxyTypeOrServer, $domain->domain);
				}
				else {
					self::$text = self::fsocket($server[0], $domain->domain);
				}
			}
			elseif ($proxyOrType == 'command') {
				self::$text = self::command($server[0], $domain->domain);
			}
			else {
				self::$text = self::socket($server[0], $domain->domain, $proxyOrType, $proxyTypeOrServer);
			}
			if (empty(trim(self::$text))) return;
			file_put_contents($textFile, self::$text);
		}
		if ($json) {
			return self::json($domain->tld);
		}
		return self::text();
	}
	static function setWhoisPath(string $path) {
		if (file_exists($path) && is_executable($path)) {
			self::$whois_path = $path;
			return true;
		}
	}
	public function text() {
		return self::$text;
	}
	static function json(string $tld = null) {
		if (self::$json) return self::$json;
		$a = explode("\n", self::$text);
		$r = [
			'date_create' => null,
			'date_update' => null,
			'date_expire' => null,
			'date_delete' => null,
		];
		foreach ($a as $v) {
			$v = trim($v);
			if (empty($v)) continue;
			if (substr($v, 0, 1) == '#') continue;
			if (substr($v, 0, 1) == '%') continue;
			switch ($tld) {
				default:
					$e = preg_split('/([\.\s]+)?:([^\/\/])(\s)?/', $v, 2);
					if (count($e) == 2) {
						$r[strtolower($e[0])] = trim($e[1]);
					}
				break;
			}
		}
		foreach ($r as $k => $v) {
			switch ($k) {
				case 'created':
				case 'registered':
				case 'creation date':
				case 'registered on':
					$r['date_create'] = Date::parse($v, $tld)->iso_date;
				break;
				case 'modified':
				case 'updated date':
				case 'last-update':
				case 'last updated':
				case 'last modified':
					$r['date_update'] = Date::parse($v, $tld)->iso_date;
				break;
				case 'expires':
				case 'expiry date':
				case 'registry expiry date':
				case 'renewal date':
					$r['date_expire'] = Date::parse($v, $tld)->iso_date;
				break;
				case 'available':
				case 'delete date':
					$r['date_delete'] = Date::parse($v, $tld)->iso_date;
				break;
			}
		}
		self::$json = Format::object($r);
		return self::$json;
	}
	private static function http(string $url, string $domain) {
		$ch = curl_init($url.$domain);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$text = curl_exec($ch);
		if (curl_error($ch)) {
			throw new \Exception('error connecting to: '.$url.$domain.' via http', 1);
		} else {
			$text = strip_tags($text);
		}
		curl_close($ch);
		return $text;
	}
	private static function socket(string $server, string $domain, string $proxy = null, string $proxyType = 'socks5') {
		if (!strpos($server,':')) $server .= ':43';
		$ch = curl_init($server);
		if (!empty($proxy)) {
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
			switch ($proxyType) {
				case 'socks4':
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
				break;
				case 'socks5':
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
				break;
				case 'socks4a':
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4A);
				break;
				case 'socks5host':
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
				break;
				default:
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				break;
			}
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $domain."\r\n");
		$text = curl_exec($ch);
		if (curl_error($ch)) {
			throw new \Exception('error connecting to: '.$server.' via socket'.($proxy ? ' thru '.$proxy.' on '.$proxyType : ''), 1);
		} else {
			$text = strip_tags($text);
		}
		curl_close($ch);
		return self::encoded($text);
	}
	private static function fsocket(string $server, string $domain) {
		$fp = fsockopen($server, 43);
		if (!$fp) {
			throw new \Exception('error connecting to: '.$server.' via socket', 1);
		}
		$text = '';
		fputs($fp, $domain."\r\n");
		while (!feof($fp)) {
			$text .= fgets($fp, 1024);
		}
		return self::encoded($text);
	}
	private static function command(string $server, string $domain) {

	}
	private static function encoded(string $text) {
		$text = mb_convert_encoding($text, 'UTF-8',
			mb_detect_encoding($text, 'UTF-8, ISO-8859-1, ISO-8859-15', true)
		);
		return $text;
		return htmlspecialchars($text, ENT_COMPAT, 'UTF-8', true);
	}
	private static function getWhoisServer(string $tld) {
		$tld = array_pop(explode('.', $tld));
		if (!empty(self::$servers[$tld])) return self::$servers[$tld];
		$jsonFile = self::WHOISDIR.DIRECTORY_SEPARATOR.$tld.'.json';
		if (!file_exists($jsonFile)) throw new \Exception('missing tld file for: '.$tld, 1);
		self::$servers[$tld] = json_decode(file_get_contents($jsonFile), false);
		return self::$servers[$tld];
	}
	static function build() {
		if (!file_exists(self::JSONFILE)) throw new \Exception('cannot locate: '.self::JSONFILE, 1);
		if (!is_readable(self::JSONFILE)) throw new \Exception('cannot read: '.self::JSONFILE, 2);
		self::$servers = json_decode(file_get_contents(self::JSONFILE), false);
		foreach (self::$servers as $k => $v) {
			file_put_contents(self::WHOISDIR.DIRECTORY_SEPARATOR.$k.'.json', json_encode($v));
		}
	}
}
