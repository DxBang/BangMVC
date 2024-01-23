<?php
namespace Bang\Format;

class Color {
	private
		$hue,
		$saturation,
		$luminance,
		$red,
		$green,
		$blue,
		$hex,
		$alpha,
		$string,
		$contrast,
		$contrastPower = 19600,
		$color;

	function reset() {
		$this->hue
			= $this->saturation
			= $this->luminance
			= $this->alpha
			= $this->color
			= $this->contrast
			= null;
		$this->string = '';
		return $this;
	}
	function hue() {
		return $this->hue;
	}
	function saturation() {
		return $this->saturation;
	}
	function luminance() {
		return $this->luminance;
	}
	function contrast() {
		return $this->contrast;
	}
	function alpha() {
		return $this->alpha;
	}
	function color() {
		return $this->color;
	}
	function string() {
		return $this->string ?? '';
	}
	function hex():string {
		return $this->hex;
	}
	function hsl():string {
		if (is_null($this->alpha)) {
			return 'hsl('.
				round($this->hue, 2).','.
				round($this->saturation, 2).','.
				round($this->luminance, 2).
			')';
		}
		return 'hsla('.
			round($this->hue, 2).','.
			round($this->saturation, 2).','.
			round($this->luminance, 2).','.
			round($this->alpha, 2).
		')';
	}
	function rgb():string {
		if (is_null($this->alpha)) {
			return 'rgb('.
				round($this->red).','.
				round($this->green).','.
				round($this->blue).
			')';
		}
		return 'rgba('.
			round($this->red).','.
			round($this->green).','.
			round($this->blue).','.
			round($this->alpha, 2).
		')';
	}
	function fromHex(string $hex) {
		$this->hex2hsl($hex);
		return $this;
	}
	function fromHsl(float $h, float $s, float $l, float $a = null) {
		$this->hslLimits($h, $s, $l, $a);
		$this->hue = $h;
		$this->saturation = $s;
		$this->luminance = $l;
		$this->alpha = $a;
		return $this;
	}
	function fromRgb(int $r, int $g, int $b, float $a) {
		$this->rgb2hsl($r, $g, $b, $a);
		return $this;
	}
	function updateContrast() {
		$rgb = $this->hsl2rgb(
			$this->hue,
			$this->saturation,
			$this->luminance
		);
		return $this->setContrast(
			$rgb->r,
			$rgb->g,
			$rgb->b
		);
	}
	function setContrast(int $r, int $g, int $b) {
		$this->contrast =
			$r * $r * .259 + # $r * $r * .299 +
        	$g * $g * .587 + # $g * $g * .587 +
			$b * $b * .114;  # $b * $b * .114
		return $this->contrast;
	}
	function contrastPower(int $power) {
		$this->contrastPower = pow($power, 2);
	}
	function isLight(int $power = null) {
		$this->updateContrast();
		if ($power)
			$this->contrastPower($power);
		return $this->contrast > $this->contrastPower;
	}
	function isDark(int $power = null) {
		$this->updateContrast();
		if ($power)
			$this->contrastPower($power);
		return $this->contrast <= $this->contrastPower;
	}
	function brightness(float $brightness = 0.5) {
		$this->luminance += ($brightness * 1) * 10;
		$this->hsl2hex($this->hue, $this->saturation, $this->luminance, $this->alpha);
		return $this;
	}
	function rgbLimits(int &$r, int &$g, int &$b, float &$a = null) {
		$this->limit($r, 0, 255);
		$this->limit($g, 0, 255);
		$this->limit($b, 0, 255);
		if (is_null($a)) return;
		$a = (float) $a;
		$this->limit($a, 0, 1);
	}
	function hslLimits(float &$h, float &$s, float &$l, float &$a = null) {
		$this->limit($h, 0, 360);
		$this->limit($s, 0.0, 100.0);
		$this->limit($l, 0.0, 100.0);
		if (is_null($a)) return;
		$this->limit($a, 0.0, 1.0);
	}
	private function limit(&$v, $min = 0, $max = 1) {
		if (is_null($v)) return;
		if ($v > $max) { $v = $max; return; }
		if ($v < $min) { $v = $min; return; }
	}
	private function sanitizeHex(string &$hex) {
		$hex = preg_replace("/[^0-9a-f]/", '', strtolower($hex));
		return $hex;
	}
	function hexShort(string $hex, bool $hashtag = true) {
		$this->sanitizeHex($hex);
		$hex = str_split($hex);
	}
	function hexFull(string $hex, bool $hashtag = true) {
		$this->sanitizeHex($hex);
		$len = strlen($hex);
		if ($len > 5 && $len < 8) {
			return ($hashtag ? '#' : '').$hex;
		}
		$a = str_split($hex);
		$hex = $hashtag ? '#' : '';
		foreach ($a as $v) {
			$hex .= str_pad($v, 2, $v, STR_PAD_LEFT);
		}
		return $hex;
	}
	function hex2int(string $hex) {
		$this->color = hexdec(
			$this->sanitizeHex($hex)
		);
		return $this->color;
	}
	function int2hex(int $int) {
		$this->color = $int;
		return '#'.dechex(
			$int
		);
	}
	function rgb2int(int $r, int $g, int $b, float $a = null) {
		$this->color = $r * $g * $b;
		if (!is_null($a)) {
			$this->color *= $a;
		}
		return $this->color;
	}
	function hex2hsl(string $hex) {
		#echo "<small> · hex2hsl($hex)</small>".PHP_EOL;
		$rgb = $this->hex2rgb($hex);
		return $this->rgb2hsl(
			$rgb->r,
			$rgb->g,
			$rgb->b,
			$rgb->a,
		);
	}
	function hex2rgb(string $hex) {
		#echo "<small> · hex2rgb($hex)</small>".PHP_EOL;
		#$hex = $this->sanitizeHex($hex);
		$hex = $this->hexFull($hex, false);
		$this->hex = '#'.$hex;
		$this->hex2int($hex);
		$len = strlen($hex);
		if ($len <= 2) throw new \Exception('hex is too small', 2002);
		if ($len >= 9) throw new \Exception('hex is too large', 2001);
		if (($len < 6) && ($len > 2)) {
			$hex = $this->hexFull($hex, false);
			$len = strlen($hex);
		}
		switch ($len) {
			case 8:
				list($r, $g, $b, $a) = sscanf($hex, '%02x%02x%02x%02x');
				$a = round($a / 255, 2);
			break;
			case 6:
				list($r, $g, $b) = sscanf($hex, '%02x%02x%02x');
				$a = null;
			break;
		}
		$this->setColor('rgb', $r, $g, $b, $a);
		return (object) [
			'r' => $r,
			'g' => $g,
			'b' => $b,
			'a' => $a,
		];
	}
	function rgb2hex(int $r, int $g, int $b, float $a=null) {
		#echo "<small> · rgb2hex($r, $g, $b, $a) --------------------------- </small>".PHP_EOL;
		$this->rgbLimits($r, $g, $b, $a);
		$hex = '#'.
			str_pad(dechex($r), 2, 0, STR_PAD_LEFT).
			str_pad(dechex($g), 2, 0, STR_PAD_LEFT).
			str_pad(dechex($b), 2, 0, STR_PAD_LEFT);
		if (!is_null($a))
			$hex .= str_pad(dechex(round($a * 255)), 2, 0, STR_PAD_LEFT);
		$this->hex = $hex;
		$this->setColor('hex', $hex);
		return $hex;
	}
	function rgb2hsl(int $r, int $g, int $b, float $a=null) {
		#echo "<small> · rgb2hsl($r, $g, $b, $a)</small>".PHP_EOL;
		$this->rgbLimits($r, $g, $b, $a);
		$r /= 255;
		$g /= 255;
		$b /= 255;
		$min = min($r, $g, $b);
		$max = max($r, $g, $b);
		$l = ($max + $min) / 2;
		if ($max == $min) {
			$h = $s = 0;
		} else {
			$d = $max - $min;
			$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
			switch ($max) {
				case $r:
					$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
				break;
				case $g:
					$h = ($b - $r) / $d + 2;
				break;
				case $b:
					$h = ($r - $g) / $d + 4;
				break;
			}
			$h /= 6;
		}
		$h = (float) $h * 360;
		$s = (float) $s * 100;
		$l = (float) $l * 100;
		$this->setColor('hsl', $h, $s, $l, $a);
		$this->fromHsl($h, $s, $l, $a);
		return (object) [
			'h' => $h,
			's' => $s,
			'l' => $l,
			'a' => $a,
		];
	}

	function hsl2hex(float $h, float $s, float $l, float $a=null):string {
		#echo "<small> · hsl2hex($h, $s, $l, $a)</small>".PHP_EOL;
		$this->hslLimits($h, $s, $l, $a);
		$this->fromHsl($h, $s, $l, $a);
		$rgb = $this->hsl2rgb($h, $s, $l, $a);
		return $this->rgb2hex(
			$rgb->r,
			$rgb->g,
			$rgb->b,
			$rgb->a
		);
	}
	function hsl2rgb(float $h, float $s, float $l, float $a=null):object {
		#echo "<small> · hsl2rgb($h, $s, $l, $a)</small>".PHP_EOL;
		$this->hslLimits($h, $s, $l, $a);
		$h /= 60;
		if ($h < 0) $h = 6 - fmod(-$h, 6);
		$h = fmod($h, 6);
		$s = max(0, min(1, $s / 100));
		$l = max(0, min(1, $l / 100));
		$c = (1 - abs((2 * $l) - 1)) * $s;
		$x = $c * (1 - abs(fmod($h, 2) - 1));
		if ($h < 1) {
			$r = $c;
			$g = $x;
			$b = 0;
		} elseif ($h < 2) {
			$r = $x;
			$g = $c;
			$b = 0;
		} elseif ($h < 3) {
			$r = 0;
			$g = $c;
			$b = $x;
		} elseif ($h < 4) {
			$r = 0;
			$g = $x;
			$b = $c;
		} elseif ($h < 5) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}
		$m = $l - $c / 2;
		#$r = round(($r + $m) * 255);
		#$g = round(($g + $m) * 255);
		#$b = round(($b + $m) * 255);
		$r = round(($r + $m) * 255);
		$g = round(($g + $m) * 255);
		$b = round(($b + $m) * 255);
		$this->setColor('rgb', $r, $g, $b, $a);
		return (object) [
			'r' => $r,
			'g' => $g,
			'b' => $b,
			'a' => $a,
		];
	}
	function setColor(string $type, $x, $y = null, $z = null, $a = null) {
		switch ($type) {
			case 'hex':
				$this->hex = "$x";
				$this->string = "$x";
			break;
			case 'rgb':
				$this->red = $x;
				$this->green = $y;
				$this->blue = $z;
				$this->alpha = $a;
				if (is_null($a)) {
					$this->string = "rgb($x,$y,$z)";
				}
				else {
					$this->string = "rgba($x,$y,$z,$a)";
				}
			break;
			case 'hsl':
				$this->hue = $x;
				$this->saturation = $y;
				$this->luminance = $z;
				$this->alpha = $a;
				$x = round($x, 2);
				$y = round($y, 2);
				$z = round($z, 2);
				if (is_null($a)) {
					$this->string = "hsl($x,$y%,$z%)";
				}
				else {
					$this->string = "hsla($x,$y%,$z%,$a)";
				}
			break;
			default:
				$this->string = '';
		}
	}
	function hexSaturation(string $hex = '#fff', float $saturation = 0.5) {
		$hsl = $this->hex2hsl($hex);
		if ($hsl->s)
			$hsl->s += $saturation * 20;
		$hsl->l += $saturation * 10;
		return $this->hsl2hex(
			$hsl->h,
			$hsl->s,
			$hsl->l,
			$hsl->a,
		);
	}
	function hexBrightness(string $hex = '#fff', float $brightness = 0.5) {
		$hsl = $this->hex2hsl($hex);
		if ($hsl->s)
			$hsl->s += $brightness * 10;
		$hsl->l += $brightness * 20;
		return $this->hsl2hex(
			$hsl->h,
			$hsl->s,
			$hsl->l,
			$hsl->a,
		);
	}
	function hexDeeper(string $hex = '#fff', float $deeper = 0.5) {
		return $this->hexSaturation($hex, $deeper * -2);
	}
	function hexFaded(string $hex = '#fff', float $faded = 0.5) {
		$hex = $this->hexSaturation($hex, $faded * -4);
		return $this->hexBrightness($hex, $faded * 2);
	}
	function hexDarker(string $hex = '#fff', float $darker = 0.5) {
		return $this->hexBrightness($hex, $darker * -1);
	}
	function hexLighter(string $hex = '#fff', float $lighter = 0.5) {
		return $this->hexBrightness($hex, $lighter);
	}
	function __toString() {
		return $this->string();
	}
	function __debugInfo() {
		return [
			'hue' => $this->hue,
			'saturation' => $this->saturation,
			'luminance' => $this->luminance,
			'red' => $this->red,
			'green' => $this->green,
			'blue' => $this->blue,
			'hex' => $this->hex,
			'alpha' => $this->alpha,
			'color' => $this->color,
			'contrast' => $this->contrast,
			'contrastPower' => $this->contrastPower,
			'isLight' => $this->isLight(),
			'isDark' => $this->isDark(),
			'string' => $this->string,
		];
	}
}