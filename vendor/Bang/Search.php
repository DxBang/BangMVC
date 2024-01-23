<?php
namespace Bang;

class Search {
	public static
		$queryStringToXRules = [
			'country' => '/(cc|country)\:(?<country>[^\s]*)/',
			'ipv4' => '/(ip|ip4|ipv4)\:(?<ipv4>[^\s]*)/',
			'ipv6' => '/(ip6|ipv6)\:(?<ipv6>[^\s]*)/',
			'mac' => '/(mac)\:(?<mac>[^\s]*)/',
			'url' => '/(site|url)\:(?<url>[^\s]*)/',
			'host' => '/(domain|host)\:(?<host>[^\s]*)/',
			'link' => '/(href|link)\:(?<link>[^\s]*)/',
		];

	public static function queryStringToObject(string $search, array $rules = null) {
		return self::queryStringToX($search, $rules, true);
	}
	public static function queryStringToArray(string $search, array $rules = null) {
		return self::queryStringToX($search, $rules, false);
	}

	private static function queryStringToX(string $search, array $rules = null, bool $asObject = false) {
		if (is_null($rules))
			$rules = self::$queryStringToXRules;
		$r = $asObject ? (object) [] : [];
		foreach ($rules as $k => $v) {
			if (preg_match($v, $search, $m)) {
				if (isset($m[$k])) {
					if ($asObject)
						$r->{$k} = strlen($m[$k]) ? preg_split('/[,;]/', $m[$k]) : null;
					else
						$r[$k] = strlen($m[$k]) ? preg_split('/[,;]/', $m[$k]) : null;
					$search = preg_replace($v, '', $search);
				}
			}
		}
		if ($asObject)
			$r->search = trim($search);
		else
			$r['search'] = trim($search);
		return $r;
	}
	public static function fulltextBoolean(string $query, bool $slug = false):string {
		$a = preg_split('/[\s+]/', trim($query));
		$a = array_unique($a);
		$f = $l = $r = [];
		foreach ($a as $v) {
			if (empty($v)) continue;
			switch ($v[0]) {
				case '+':
					$f[] = substr($v, 1);
					$r[] = $v.'*';
				break;
				case '-':
				case '~':
					$l[] = $v;
				break;
				default:
					$f[] = $v;
					$r[] = '+'.$v;
				break;
			}
		}
		if ($slug)
			return '+'.implode('', $f).'*';
		return implode(' ', $r).($l ? ' '.implode(' ', $l) : '');
	}
	public static function fulltextNatural(string $query, bool $slug = false):string {
		$a = preg_split('/[\s+]/', trim($query));
		$a = array_unique($a);
		$f = $l = $r = [];
		foreach ($a as $v) {
			if (empty($v)) continue;
			switch ($v[0]) {
				case '+':
					$f[] = $v = substr($v,1);
				break;
				case '-':
				case '~':
				break;
				default:
					$f[] = $r[] = $v;
				break;
			}
		}
		if (count($r) > 1) {
			if ($slug) {
				return implode('', $f).','.implode('', array_reverse($f)).','.implode(',', $r);	
			}
			return implode('', $f).','.implode(',', $r);
		}
		return implode('', $f);
	}
	public static function cleanString(string $string) {
		$a = preg_split('/[\s+]/', trim($string));
		$r = [];
		foreach ($a as $v) {
			if (empty($v)) continue;
			$r[] = Format::zeroPunctuation($v);
		}
		return Format::zeroPunctuation(implode(' ', $r));
	}
	public static function likeString(string $string, bool $after = true, bool $before = false, bool $words = false) {
		if (empty($string)) return;
		if ($words) {
			$a = explode(' ', $string);
			foreach ($a as &$v) {
				$v = self::likeString($v, $after, $before);
			}
			return $a;
		}
		return ($before ? '%' : '')
			.self::cleanString($string)
			.($after ? '%' : '');
	}
	public static function likeSlug(string $string, bool $after = true, bool $before = false, bool $words = false) {
		if (empty($string)) return;
		return ($before ? '%' : '')
			.Format::slug($string, true)
			.($after ? '%' : '');
	}

}
