<?php
namespace Bang;

class Minify {
	protected static
		$minify = true,
		$cacheExtension = '.json',
		$minified = [];

	function __construct(bool $minify = true) {
		self::$minify = $minify;
	}

	static function sections(object $sections) {
		foreach ($sections as $k => $v) {
			if (isset($v->file) && isset($v->files)) {
				self::minifyFiles($k, $v->file, $v->files);
			}
			else if (is_array($v)) {
				foreach ($v as $vv) {
					self::minifyFiles($k, $vv->file, $vv->files);
				}
			}
		}
	}

	private static function minifyFiles(string $type, string $file, array $files) {
		$dates = file_exists($file.self::$cacheExtension)
			? json_decode(file_get_contents($file.self::$cacheExtension), 1)
			: [];
		$refresh = false;
		if (count($dates) != count($files)) {
			$refresh = true;
		}
		else {
			foreach ($files as $f) {
				if (!isset($dates[$f]) || (file_exists($f) && ($dates[$f] != filemtime($f)))) {
					$refresh = true;
					break;
				}
			}
		}
		if (!$refresh) return;
		$data = '';
		$dates = [];
		foreach ($files as $f) {
			#$basename = pathinfo($f, PATHINFO_BASENAME);
			$dates[$f] = filemtime($f);
			if ($type == 'html') {
				#$data .= '<!-- '.$basename.' '.date('Y-m-d H:i:s', $dates[$f]).' -->'.PHP_EOL;
			}
			else {
				#$data .= '/* '.$basename.' '.date('Y-m-d H:i:s', $dates[$f]).' */'.PHP_EOL;
			}
			self::$minified[$file][$f] = false;
			if (file_exists($f)) {
				self::$minified[$file][$f] = true;
				$data .= self::minify($type, file_get_contents($f)).PHP_EOL;
			}
		}
		file_put_contents($file, $data);
		file_put_contents($file.self::$cacheExtension, json_encode($dates));
	}

	static function minify(string $type, string $s) {
		if (!self::$minify) return $s;
		switch (strtolower($type)) {
			case 'css':
				return self::css($s);
			break;
			case 'js':
				return self::js($s);
			break;
			case 'html':
				return self::html($s);
			break;
			default:
				return self::comment($s);
		}
	}

	static function comment(string $s) {
		return preg_replace(
			[
				'/^\s*\/\/.+$/m',
				'/\/\*([\s\S]*?)\*\//s',
				'/(\<![\-\-\s\w\>\/]*\>)/s',
			],
			[
				'',
				'',
				'',
			],
			$s);
	}

	static function html(string $s) {
		return trim(
			preg_replace(
			[
				'/\>[^\S ]+/s',
				'/[^\S ]+\</s',				# > for color syntax
				'/(\s)+/s',
				'/(\<![\-\-\s\w\>\/]*\>)/s',
				'/^$/m',
			],
			[
				'>',
				'<',						# > for color syntax
				'\\1',
				'',
				'',
			],
			$s)
		);
	}

	static function js(string $s) {
		return preg_replace(
			[
				'/\/\*([\s\S]*?)\*\//s',
				'/[\s\n\r\t]+/s',
				'/\s?([=<>!?:&|,;\{\}\(\)\[\]])\s?/s',
				'/;(\})/s',
			],
			[
				'',
				' ',
				'$1',
				'$1',
			],
			$s
		);
		/*
		return trim(
			preg_replace(
			[
				'/^\s*\/\/.+$/m',
				'/\/\*([\s\S]*?)\*\//s',
				'/[\n\r\t]+/s',
				'/\s+([:\{\}\(\)\?!=<>(\|\|)(&&)])/s',
				'/([\s:;\{\},\(\)\?!=<>(\|\|)(&&)])\s+/s',
				# { for color syntax
				'/;(\})/s',
			],
			[
				'',
				'',
				' ',
				'$1',
				'$1',
				'$1',
			],
			$s)
		);
		*/
	}

	static function css(string $s) {
		// .5rem .75rem calc(.5rem - 2px)
		return preg_replace(
			[
				'/\/\*([\s\S]*?)\*\//s',
				'/[\s\n\r\t]+/s',
				'/\s?([:;{}])\s+/s',
				'/;(\})/s',
			],
			[
				'',
				' ',
				'$1',
				'$1',
			],
			$s
		);
		/*
		return trim(
			preg_replace(
			[
				'/\/\*([\s\S]*?)\*\//s',
				'/[\s\n\r\t]+/',
				'/\s+([\{\}\(\)])/',
				'/([\s:;\{\},\(\)])/',
				# { opening bracket
				'/\s?([{:\(@,])\s?/',
				'/;\s?(})\s?/',
				'/([;}])\s/',
			],
			[
				'',
				' ',
				'$1',
				'$1',
				#
				'$1',
				'$1',
				'$1',
			],
			$s)
		);
		*/
	}

	function __debugInfo() {
		return [
			'minified' => self::$minified
		];
	}

	function __toString() {
		return json_encode($this->__debugInfo(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
}
