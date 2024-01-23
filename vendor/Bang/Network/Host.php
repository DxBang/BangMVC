<?php
namespace Bang\Network;

class Host {
	protected static
		$rules = [],
		$tldList;
	const
		TLDDIR = BANG_DATA.'/tld';

	static function parse(string $host) {
		if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
			return (object) [
				'ip' => $host,
				'host' => $host,
				'domain' => $host,
			];
		}
		if (!preg_match('/^[A-Za-z0-9\.\-_]+$/', $host)) {
			return (object) [
				'domain' => null
			];
		}
		$host = array_reverse(explode('.', $host));
		$rules = self::getDomainRules($host[0]);
		$name = '';
		$tld
			= $sub
			= [];
		foreach ($host as $h) {
			if (empty($h)) break;
			if (!empty($name)) {
				$sub[] = $h;
				continue;
			}
			if (
				isset($rules->{$h})
				&& (
					is_object($rules->{$h})
					||
					is_array($rules->{$h})
				)) {
				$tld[] = $h;
				$rules = $rules->{$h};
				continue;
			}
			if (empty($name)) {
				$name = $h;
				continue;
			}
		}
		$sub = !empty($sub) ? implode('.', array_reverse($sub)) : null;
		$tld = implode('.', array_reverse($tld));
		$puny = IDNA::parse($name.'.'.$tld);
		$host = implode('.', !empty($sub) ? [$sub, $puny->domain] : [$puny->domain]);
		return (object) [
			'name' => $name,
			'host' => $host,
			'sub' => $sub,
			'domain' => $puny->domain,
			'idn' => $puny->idn,
			'intl' => $puny->intl,
			'tld' => $tld,
			'valid' => self::validateDomainFormat($puny->domain),
		];
	}
	private static function validateDomainFormat(string $domain):bool {
		return preg_match('/^[a-z0-9]([a-z0-9-_]+)?\.[a-z]{2,24}$/', $domain) ? true : false;
	}
	private static function getDomainRules(string $tld) {
		if (!empty(self::$rules[$tld])) return (object) [$tld => self::$rules[$tld]];
		try {
			if (!file_exists(self::TLDDIR.'/.'.$tld.'.json'))
				throw new \Exception('missing TLD JSON for ('.$tld.') ('.self::TLDDIR.'/.'.$tld.'.json'.')', 1);
			self::$rules[$tld] = (object) json_decode(file_get_contents(self::TLDDIR.'/.'.$tld.'.json'), false);
			return (object) [$tld => self::$rules[$tld]];
		}
		catch (\Exception $e) {
			#echo $e->getCode().': '.$e->getMessage();
		}
		return (object) [];
	}
	private static function tldFilter(&$data) {
		foreach ($data as $k => $v) {
			if ((trim($v) == '') ||  (substr($v, 0, 2) == '//')) {
				unset($data[$k]);
			}
			if (preg_match('/[^a-z0-9\.\-]/', $v)) {
				unset($data[$k]);
			}
		}
		usort($data, function($a, $b) {
			return strlen($a)-strlen($b);
		});
	}

	static function build() {
		$datFile = self::TLDDIR.'/public_suffix_list.dat';
		if (!file_exists($datFile || filemtime($datFile) <= time() - 3600)) {
			$http = new \Bang\HTTP();
			$http->get('https://publicsuffix.org/list/public_suffix_list.dat', null, $datFile);
			if (filesize($datFile) == 0) {
				throw new \Exception('failed to get public_suffix_list.dat', 1);
				return;
			}
		}
		$data = explode("\n", file_get_contents($datFile));
		self::tldFilter($data);
		$a = [];
		foreach ($data as $tld) {
			$d = array_reverse(explode('.', $tld));
			switch (count($d)) {
				case 1:
					$a[$d[0]] = [];
				break;
				case 2:
					$a[$d[0]][$d[1]] = [];
				break;
				case 3:
					$a[$d[0]][$d[1]][$d[2]] = [];
				break;
				case 4:
					$a[$d[0]][$d[1]][$d[2]][$d[3]] = [];
				break;
				case 5:
					$a[$d[0]][$d[1]][$d[2]][$d[3]][$d[4]] = [];
				break;
				default:
					throw new \Exception('WHAT THE FUCK IS: '.$tld, 1);
			}
		}
		file_put_contents(self::TLDDIR.'/all.json', json_encode($a, JSON_UNESCAPED_SLASHES));
		foreach ($a as $tld => $data) {
			file_put_contents(self::TLDDIR.'/.'.$tld.'.json', json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
		}
		return count($a);
	}
}

class IDNA {
	protected static
		$idnRegEx = '/^(?<xn>xn--)(?<name>.+)-(?<code>[a-z0-9]+)\.(?<tld>[a-z0-9]+)$/';

	static function parse(string $domain) {
		if (self::isIDN($domain)) {
			$intl = self::encode($domain);
			return (object) [
				'domain' => $domain,
				'idn' => $domain,
				'intl' => $intl,
			];
		}
		$ascii = self::decode($domain);
		return (object) [
			'domain' => $ascii,
			'idn' => $ascii,
			'intl' => $domain,
		];
	}
	static function isIDN(string $domain) {
		return preg_match(self::$idnRegEx, $domain);
	}
	static function decode(string $domain) {
		if (function_exists('idn_to_ascii')) return idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		return $domain;
	}
	static function encode(string $domain) {
		if (function_exists('idn_to_utf8')) return idn_to_utf8($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
		return $domain;
	}
}
