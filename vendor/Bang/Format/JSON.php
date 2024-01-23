<?php
namespace Bang\Format;

class JSON {
	const
		PRETTY = 1;

	function __construct(bool $sendHeader = false) {
		if ($sendHeader) {
			header('Content-Type: application/json; Charset=UTF-8');
		}
	}

	static function decode(string $s) {
		return json_decode($s);
	}
	static function encode($a, bool $pretty = false, int $indent=0):string {
		$t = strtolower(gettype($a));
		$up = ($indent+1);
		$down = ($indent-1);
		switch ($t) {
			case 'array':
				$i = 0;
				foreach (array_keys($a) as $k => $v) {
					if (is_string($v) || (is_int($v) && $v > $k)) {
						return self::encode((object) $a, $pretty, $indent);
					}
				}
				$r = '[';
				foreach (array_values($a) as $k => $v) {
					if ($k)
						$r .= ',';
					$r .= self::encode($v, $pretty, $up);
				}
				return $r.']';
			case 'object':
				$i = 0;
				$r = ($indent ? PHP_EOL : '').'{';
				foreach ($a as $k => $v) {
					if ($i)
						$r .= ',';
					$r .= '"'.$k.'":'.self::encode($v, $pretty, $indent);
					$i++;
				}
				return $r.'}';
			case 'integer':
				return $a;
			case 'double':
				$w = (int) floor($a);
				$f = (float) $a - $w;
				if ($f === (float) 0)
					return number_format($a, 1, '.', '');
				$f = explode('.', (string) $f)[1];
				return number_format($a, strlen($f), '.', '');
			case 'string':
				return json_encode($a, JSON_UNESCAPED_SLASHES);
			case 'null':
				return 'null';
			case 'boolean':
				return $a ? 'true' : 'false';
			default:
				throw new \Exception('unknown type "'.$t.'" for '.json_encode($a));
		}
	}
}