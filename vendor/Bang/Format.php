<?php
namespace Bang;

class Format {
	private static
		$jsonArray,
		$accents = [
			'á' => 'a', 'Á' => 'A',
			'à' => 'a', 'À' => 'A',
			'â' => 'a', 'Â' => 'A',
			'ã' => 'a', 'Ã' => 'A',
			'ā' => 'a', 'Ā' => 'A',
			'å' => 'aa', 'Å' => 'Aa',
			'ä' => 'ae', 'Ä' => 'Ae',
			'æ' => 'ae', 'Æ' => 'Ae',
			'ć' => 'c', 'Ć' => 'C',
			'č' => 'c', 'Č' => 'C',
			'ç' => 'c', 'Ç' => 'C',
			'ď' => 'd', 'Ď' => 'D',
			'đ' => 'd', 'Đ' => 'D',
			'ð' => 'd', 'Ð' => 'D',
			'é' => 'e', 'É' => 'E',
			'è' => 'e', 'È' => 'E',
			'ê' => 'e', 'Ê' => 'E',
			'ë' => 'e', 'Ë' => 'E',
			'ė' => 'e', 'Ė' => 'E',
			'ę' => 'e', 'Ę' => 'E',
			'ē' => 'e', 'Ē' => 'E',
			'ƒ' => 'f',
			'ģ' => 'g', 'Ģ' => 'G',
			'í' => 'i', 'Í' => 'I',
			'ì' => 'i', 'Ì' => 'I',
			'î' => 'i', 'Î' => 'I',
			'ï' => 'i', 'Ï' => 'I',
			'į' => 'i', 'Į' => 'I',
			'ī' => 'i', 'Ī' => 'I',
			'ı' => 'i', 'İ' => 'I',
			'ķ' => 'k', 'Ķ' => 'K',
			'ļ' => 'l', 'Ļ' => 'L',
			'ł' => 'l', 'Ł' => 'L',
			'ń' => 'n', 'Ń' => 'N',
			'ň' => 'n', 'Ň' => 'N',
			'ñ' => 'n', 'Ñ' => 'N',
			'ņ' => 'n', 'Ņ' => 'N',
			'ó' => 'o', 'Ó' => 'O',
			'ò' => 'o', 'Ò' => 'O',
			'ô' => 'o', 'Ô' => 'O',
			'ö' => 'o', 'Ö' => 'O',
			'õ' => 'o', 'Õ' => 'O',
			'ö' => 'oe', 'Ö' => 'Oe',
			'ø' => 'oe', 'Ø' => 'Oe',
			'œ' => 'oe', 'Œ' => 'Oe',
			'ŕ' => 'r', 'Ŕ' => 'R',
			'ř' => 'r', 'Ř' => 'R',
			'ś' => 's', 'Ś' => 'S',
			'š' => 's', 'Š' => 'S',
			'ş' => 's', 'Ş' => 'S',
			'ß' => 'ss',
			'ť' => 't', 'Ť' => 'T',
			'ŧ' => 't', 'Ŧ' => 'T',
			'ú' => 'u', 'Ú' => 'U',
			'ù' => 'u', 'Ù' => 'U',
			'û' => 'u', 'Û' => 'U',
			'ü' => 'u', 'Ü' => 'U',
			'ų' => 'u', 'Ų' => 'U',
			'ū' => 'u', 'Ū' => 'U',
			'ý' => 'y', 'Ý' => 'Y',
			'ŷ' => 'y', 'Ŷ' => 'Y',
			'ÿ' => 'y', 'Ÿ' => 'Y',
			'ź' => 'z', 'Ź' => 'Z',
			'ž' => 'z', 'Ž' => 'Z',
			'þ' => 'b', 'Þ' => 'B',
			'µ' => 'u',
			#'а' => 'a', 'А' => 'A',
			#'б' => 'b', 'Б' => 'B',
			#'в' => 'v', hell no!
		];
	static function object($any) {
		return json_decode(json_encode($any), false);
	}
	static function jsonArrayBegin() {
		self::$jsonArray = 0;
		header('Content-Type: application/json; charset=UTF-8');
		echo '['.PHP_EOL;
	}
	static function jsonArrayEnd() {
		echo PHP_EOL.']';
	}
	static function jsonArrayItem($any, bool $forceSeparator = false) {
		if (self::$jsonArray || $forceSeparator) echo ','.PHP_EOL;
		echo self::json($any);
		self::$jsonArray++;
	}
	static function json($any, bool $sendHeader = false) {
		if (!headers_sent() && $sendHeader) {
			header('Content-Type: application/json; charset='.Core::get('charset'));
		}
		return json_encode($any, JSON_UNESCAPED_SLASHES);
	}
	static function jsonPretty($any, bool $sendHeader = false) {
		if (!headers_sent() && $sendHeader) {
			header('Content-Type: application/json; charset='.Core::get('charset'));
		}
		return json_encode($any, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
	static function bool($bool):bool {
		return Validate::bool($bool) ? (bool) $bool : (bool) false;
	}
	static function float($float):float {
		return Validate::float($float) ? (float) $float : (float) 0.0;
	}
	static function int($int):int {
		return Validate::int($int) ? (int) $int : (int) 0;
	}
	static function number(float $number, $decimals = 2, bool $dynamic = false):string {
		$n = explode('.', number_format($number, $decimals, '.', ','));
		if (!$dynamic) return (string) $n;
	}
	static function string($string):string {
		return Validate::string($string) ? (string) trim(preg_replace('/\s+/u', ' ', $string)) : (string) '';
	}
	static function decontract($text) {
		return preg_replace('/[\'"`´]+/', '', # \-\(\){}\[\]\*\?!
			preg_replace('/[_]+/', ' ', self::decode($text))
		);
	}
	static function accent($text):string {
		return strtr($text, self::$accents);
	}
	static function date(string $date):object {
		return new \DateTime($date ?? 'now', new \DateTimeZone(Core::get('timezone')));
		#return $d->format(\DateTime::ATOM);
	}
	static function time(string $time):object {
		$r = (object) [
			'time' => '00:00',
			'hour' => 0,
			'min' => 0,
			'sec' => 0,
			'mil' => 0,
		];
		if (preg_match('/^(?<time>(?<hour>[0-1]?[0-9]|2[0-3]):(?<min>[0-5][0-9]))(:(?<sec>[0-5][0-9]))?([:\.](?<mil>[0-9]+))?$/', $time, $m)) {
			$r->time = $m['time'];
			$r->hour = (int) $m['hour'];
			$r->min = (int) $m['min'];
			$r->sec = (int) $m['sec'] ?? 0;
			$r->mil = (int) $m['mil'] ?? 0;
		}
		return $r;
	}
	static function singularPlural(int $i, string $singular, string $plural):string {
		return ($i == 1) ? $singular : $plural;
	}
	static function age(string $date, string $format = '%y'):string {
		$dateSince = new \DateTime($date);
		$dateNow = new \DateTime('Now');
		$interval = $dateSince->diff($dateNow);
		return $interval->format($format);
	}
	static function since(string $date):string {
		$dateSince = new \DateTime($date);
		$dateNow = new \DateTime('Now');
		$interval = $dateSince->diff($dateNow);
		$format = '%y '.self::singularPlural($interval->format('%y'), 'year', 'years')
			.', %m '.self::singularPlural($interval->format('%m'), 'month', 'months')
			.' &amp; %d '.self::singularPlural($interval->format('%d'), 'day', 'days');
		return $interval->format($format);
	}
	static function strip(string $text, $allow = ['p','br']):string {
		return strip_tags($text, $allow);
	}
	static function decode($text) {
		return html_entity_decode(urldecode($text), ENT_HTML5, 'UTF-8');
	}
	static function encode($text) {
		return urlencode(htmlentities($text, ENT_HTML5, 'UTF-8'));
	}
	static function badPunctuation($text) {
		return preg_replace('/\s+([,\.])/', '$1', $text);
	}
	static function ascii($text) {
		return trim(
			preg_replace(
				'/[^\x20-\x7E]/u',
				' ',
				self::accent($text)
			),
		' ');
	}
	static function nonPrintable($text) {
		return preg_replace('/[[:^print:]]/', '', $text);
	}
	static function text($text, bool $forceHtml = true, bool $allowLines = false) {
		if (!is_string($text)) return;
		if ($forceHtml) {
			$text = trim($text);
			if ($allowLines) {
				return nl2br(self::html($text));
			}
			return self::html($text);
		}
		return $text;
	}
	static function textOnly(string $text) {
		$text = self::strip($text, [
			'script', 'style',
			#'title',
			#'h1', 'h2', 'h3', 'h4', 'h5',
			#'div', 'p', 'br'
		]);
		$text = preg_replace('#<(script|style)(.*?)>(.*?)</(script|style)>#is', '', $text);
		#$text = preg_replace('#<(h[1-6]|p|div|title)(.*?)>(.*?)</(h[1-6]|p|div|title)>#is', "$3\n", $text);
		#$text = preg_replace('#<br(.*?)>#is', "\n", $text);
		$text = preg_split('/[\n]+/', $text);
		foreach ($text as $k => &$v) {
			$v = trim($v);
			if (empty($v)) {
				unset($text[$k]);
				continue;
			}
			$v = preg_replace('/[\s]+/', ' ', $v);
		}
		return implode("\n", $text);
	}
	static function html(string $html) {
		return htmlspecialchars($html, ENT_COMPAT | ENT_HTML5, Core::get('charset'));
	}
	static function unitext(string $text, string $font = 'math bold') {
		#print_r($text);
		$text = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
		switch ($font) {
			case 'math bold':
				$lower_plus = 120205;
				$upper_plus = 120211;
				$lower_lang = 120205;
				$upper_lang = 120211;
			break;
			case 'sup':
			case 'super':

			break;
			case 'sub':

			break;

			default:
				$plus = 0;
			break;
		}
		$r = '';
		foreach ($text as $v) {
			$uniord = self::uniord($v);
			echo $v.':';
			print_r($uniord);
			switch (count($uniord)) {
				case 1:
					if ($uniord[0] == 32) {
						$r .= ' ';
					}
					else if ($uniord[0] >= 65 && $uniord[0] <= 95) {
						$r .= self::unichr($uniord[0] + $upper_plus);
					}
					else if ($uniord[0] >= 97 && $uniord[0] <= 122) {
						$r .= self::unichr($uniord[0] + $lower_plus);
					}
					else {
						$r .= '('.$v.')';
					}
				break;
				case 2:
					$r .= '{'.$v.'}';
				break;
				default:
					$r .= '['.$v.']';
				break;
			}

		}
		return $r;
	}
	static function unichr($unicode) {
		if (is_array($unicode)) {

		}
		if (is_int($unicode)) {
			return mb_convert_encoding('&#'.intval($unicode).';', 'UTF-8', 'HTML-ENTITIES');
		}
	}
	static function uniord(string $unicode) {
		$r = [];
		for ($i=0; $i<strlen($unicode); $i++) {
			$r[] = ord(substr($unicode, $i));
		}
		return $r;
	}
	static function username(string $username):string {
		return preg_replace('/[^A-Za-z0-9\-]+/S', '-', trim(self::accent($username)));
	}
	static function name(string $name):string {
		return trim($name);
	}
	static function noSpace(string $string):string {
		return preg_replace('/[\s]/', '', $string);
	}
	static function cleanSpaces(string $string, string $replace = ' '):string {
		return preg_replace('/\s+/', $replace, $string);
	}
	static function zeroPunctuation(string $string):string {
		return self::cleanSpaces(preg_replace('/[\,\.\;]/', '', $string));
	}
	static function alphaNumeric(string $string, bool $noSpaces = false):string {
		if ($noSpaces) return preg_replace('/[^\w\-]/', '', $string);
		return preg_replace('/[^\w\s\-]/', '', $string);
	}
	static function slug(string $slug, bool $noSpaces = false):string {
		return self::filename(
			$slug,
			$noSpaces ? '' : '-'
		);
	}
	static function filename(string $slug, string $space = '-') {
		return self::cleanSpaces(
			self::alphaNumeric(
				trim(
					preg_replace(
						'/[^a-z0-9\-]+/S',
						' ',
						self::toLower(
							self::accent(
								preg_replace(
									'/[ ]+/',
									' ',
									self::decontract(
										$slug
									)
								)
							)
						)
					)
				),
				false
			),
			$space
		);
	}
	static function tag(string $tag, bool $encode = false):string {
		$tag = self::cleanSpaces(
			trim(
				preg_replace(
					'/[^a-z0-9\s]+/S',
					' ',
					self::toLower(
						self::accent($tag)
					)
				),
			' ')
		);
		if ($encode) {
			return urlencode($tag);
		}
		return $tag;
	}
	static function method(string $method):string {
		return preg_replace('/[^a-z0-9]+/S', '', self::toLower($method));
	}
	static function host(string $host):string {
		$host = self::toLower($host);
		if (Validate::host($host)) {
			return $host;
		}
		return '';
	}
	static function domain(string $domain):string {
		$domain = self::toLower($domain);
		if (Validate::domain($domain)) {
			return $domain;
		}
		return '';
	}
	static function ip(string $ip):string {
		$ip = self::toLower($ip);
		if (Validate::ip($ip)) {
			return $ip;
		}
		return '';
	}
	static function ipv4(string $ipv4):string {
		$ipv4 = self::toLower($ipv4);
		if (Validate::ipv4($ipv4)) {
			return $ipv4;
		}
		return '';
	}
	static function ipv6(string $ipv6):string {
		$ipv6 = self::toLower($ipv6);
		if (Validate::ipv6($ipv6)) {
			return $ipv6;
		}
		return '';
	}
	static function mac(string $mac):string {
		$mac = self::toLower(trim($mac));
		if (Validate::mac($mac)) {
			return $mac;
		}
		return '';
	}
	static function url(string $url):string {
		$url = self::toLower($url);
		if (Validate::url($url)) {
			return $url;
		}
		return '';
	}
	static function email(string $email):string {
		$email = self::toLower($email);
		if (Validate::email($email)) {
			return $email;
		}
		return '';
	}
	static function toLower(string $string):string {
		return strtolower(trim($string));
	}
	static function toUpper(string $string):string {
		return strtoupper(trim($string));
	}
	static function toTitle(string $string):string {
		return ucfirst(strtoupper(trim($string)));
	}
	static function humanDataSize(int $bytes):string {
		if ($bytes <= 0) return '0 b';
		$units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'];
		return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), 2).' '.$units[$i];
	}
	static function humanIntegerTimeAgo(int $integer):string {
		return self::humanDateTimeAgo('@'.$integer);
	}
	static function humanFloatTimeAgo(float $float):string {
		return self::humanDateTimeAgo('@'.round($float));
	}
	static function humanTimeAgo(string $datetime):string {
		return self::humanDateTimeAgo($datetime);
	}
	static function humanDateTimeAgo($datetime):string {
		$ago = self::dateTimeAgo($datetime);
		$units = [
			'year',
			'month',
			'week',
			'day',
			'hour',
			'minute',
			'second',
		];
		$r = [];
		foreach ($units as $v) {
			if ($ago->$v) {
				$r[] = $ago->$v.' '.$v.($ago->$v > 1 ? 's' :'');
			}
		}
		return empty($r) ? 'just now' : implode(', ', $r).' '.($ago->ago ? 'ago' : 'ahead');
	}
	static function dateTimeAgo($datetime):object {
		$now = new \DateTime('now', new \DateTimeZone(Core::get('timezone')));
		if (is_string($datetime)) {
			$datetime = new \DateTime($datetime, new \DateTimeZone(Core::get('timezone')));
		}
		$diff = (object) $now->diff($datetime);
		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;
		$units = [
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
			'f' => 'fraction',
		];
		$r = [
			'ago' => $diff->invert ? true : false,
			'days' => $diff->days,
			#'debug' => $diff,
		];
		foreach ($units as $k => $v) {
			if (isset($diff->$k)) {
				$r[$v] = $diff->$k;
			}
		}
		return (object) $r;
	}
	static function indefiniteArticle(string $word) {
		if (preg_match('/^[aeiou]|ut[th]|euler|hour(?!i)|heir|honest|hono/i', $word))
			return "an {$word}";
		return "a $word";
	}
	static function shorten(string $string, string $regexp = '/\s/', array $ignore = ['a','an','and','of','the']):string {
		$split = preg_split($regexp, $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$r = '';
		foreach($split as $word) {
			if (empty(trim($word))) continue;
			if (in_array(strtolower($word), $ignore)) continue;
			$word = ucfirst($word);
			preg_match_all('/[A-Z+]/', $word, $caps);
			foreach ($caps[0] as $cap) {
				$r .= $cap;
			}
		}
		return $r;
	}
	static function splitOn(string $needle, array $haystack = []) {
		return preg_split('/('.implode('|', $haystack).')/', $needle, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	}
}
