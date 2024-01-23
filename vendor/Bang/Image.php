<?php
namespace Bang;

class Image {
	protected
		$_file,
		$_mime,
		$_image,
		$_width,
		$_height,
		$_ratio,
		$_colors,
		$_mosaic = array('color' => null, 'colors' => array()),
		$_support = array(),
		$_quality = array('quality' => 80, 'compression' => 9),
		$_logo = array();

	public function __construct($file = null) {
		if ($file) {
			$this->load($file);
		}
		$gdinfo = gd_info();
		$this->_support = array(
			'jpg' => isset($gdinfo['JPEG Support']) ? $gdinfo['JPEG Support'] : null,
			'gif' => isset($gdinfo['GIF Create Support']) ? $gdinfo['GIF Create Support'] : null,
			'png' => isset($gdinfo['PNG Support']) ? $gdinfo['PNG Support'] : null,
			'webp' => isset($gdinfo['WebP Support']) ? $gdinfo['WebP Support'] : null,
			'antialias' => function_exists('imageantialias'),
			'alpha' => function_exists('imagealphablending')
		);
	}

	public function __destruct() {
		$this->reset(true);
	}

	public function quality($quality) {
		$r = &$this->_quality;
		if (is_int($quality)) {
			if ($quality >= 10 && $quality <= 100) {
				$r['quality'] = $quality;
				$r['compression'] = (int) floor($quality * .1 * -1 + 9);
			}
			elseif ($quality <= 9 && $quality >= 0) {
				$r['compression'] = $quality;
				$r['quality'] = $quality * 10;
			}
		}
		elseif (is_string($quality)) {
			switch(strtolower($quality)) {
				case 'lowest':
				case 'smallest':
					$r['quality'] = 20;
					$r['compression'] = 9;
				break;
				case 'higher':
				case 'bigger':
					$r['quality'] = 80;
					$r['compression'] = 7;
				break;
				case 'highest':
				case 'biggest':
					$r['quality'] = 90;
					$r['compression'] = 1;
				break;
				case 'web':
				case 'best':
					$r['quality'] = 85;
					$r['compression'] = 8;
				break;
				case 'raw':
					$r['quality'] = 100;
					$r['compression'] = 0;
				break;
				default:
					$r['quality'] = 80;
					$r['compression'] = 9;
				break;
			}
		}
		return $r;
	}

	function setQuality($keyword) {
		$this->_quality = $this->quality($keyword);
	}

	public function mime($path) {
		$this->_mime = false;
#		$headerId = microtime();
#		header('X-Path['.$headerId.']: '.$path);
		switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
			case 'png':
				$this->_mime = 'image/png';
			break;
			case 'jpg':
			case 'jpeg':
				$this->_mime = 'image/jpg';
			break;
			case 'gif':
				$this->_mime = 'image/gif';
			break;
			default:
				if (file_exists($path)) {
					$info = getimagesize($path);
				#	var_dump($info);
					$this->_mime = $info['mime'] ? $info['mime'] : false;
				}
			break;
		}
#		header('X-Mime['.$headerId.']: '.$this->_mime);
		return $this->_mime;
	}

	private function is_image($image) {
		return (
			isset($image[0]) && $this->is_gd($image[0])
			&&
			isset($image[1]) && isset($image[2])
		);
	}

	private function is_gd($resource) {
		return (
			is_resource($resource) && (get_resource_type($resource) == 'gd')
		);
	}

	public function reset($all = false) {
		$this->wipe($this->_image);
		$this->_file = null;
		$this->_mime = null;
		$this->_image = null;
		$this->_width = null;
		$this->_height = null;
		$this->_ratio = null;
		$this->_colors = null;
		$this->_mosaic['color'] = null;
		$this->_mosaic['colors'] = array();
		if ($all) {
			$this->_logo = array();
		}
	}

	public function antialias($bool) {
		if (!$this->_image) {
			throw new \Exception('There is no image loaded...');
		}
		return $this->_support['antialias'] ? imageantialias($this->_image, $bool) : false;
	}

	public function loadImage($file) {
		$mime = explode('/', $this->mime($file));
		$this->_file = $file;
		$image = null;
		switch ($mime[1]) {
			case 'png':
				$image = imagecreatefrompng($file);
				#imagealphablending($image, false);
			break;
			case 'jpg':
			case 'jpe':
			case 'jpeg':
				$image = imagecreatefromjpeg($file);
			break;
			case 'gif':
				$image = imagecreatefromgif($file);
			break;
			case 'wbmp':
				$this->_image = imagecreatefromwbmp($file);
			break;
			case 'webp':
				$image = imagecreatefromwebp($file);
				#imagealphablending($image, true);
			break;
			case 'gd':
				$image = imagecreatefromgd($file);
			break;
			case 'gd2':
				$image = imagecreatefromgd2($file);
			break;
		}
		return $image;
	}

	public function load($file) {
		$this->_image = $this->loadImage($file);
		if ($this->_image) {
			$this->_width = imagesx($this->_image);
			$this->_height = imagesy($this->_image);
			return $this->_image;
		}
	}

	public function loadLogo($file) {
		if (!file_exists($file) && !is_readable($file)) {
			throw new \Exception('Cannot locate Logo: '.$file);
		}
		$this->_logo['image'] = $this->loadImage($file);
		#imagealphablending($this->_logo['image'], false);
		$this->_logo['width'] = imagesx($this->_logo['image']);
		$this->_logo['height'] = imagesy($this->_logo['image']);
	}

	public function logoImage(&$image) {
		if (!$this->_logo) {
			throw new \Exception('There is no logo loaded...');
		}
		imagealphablending($image, true);
		return imagecopy($image, $this->_logo['image'],
			$this->_width - $this->_logo['width'], $this->_height - $this->_logo['height'],
			0, 0,
			$this->_width, $this->_height);
	}

	public function logo() {
		if (!$this->_image) {
			throw new \Exception('There is no image loaded...');
		}
		return $this->logoImage($this->_image);
	}

	public function setColor($color) {
		if (!$this->_image) {
			throw new \Exception('There is no image loaded...');
		}
		if (is_array($color)) {
			switch (count($color)) {
				case 3:
					return imagecolorallocate($this->_image, $color[0], $color[1], $color[2]);
				break;
				case 4:
					return imagecolorallocatealpha($this->_image, $color[0], $color[1], $color[2], $color[3]);
				break;
			}
		}
	}

	public function textColor($color, $stroke = null) {
		$this->textColor = $this->setColor($color);
		$this->strokeColor = $this->setColor($stroke);
	}
	public function captionImage(&$image, $text, $fontsize = 24, $fontfile = 'arial.ttf', $lineheight = 1.1, $offset = 10, $stroke = 1) {
		if (!file_exists($fontfile)) {
			throw new \Exception('Cannot locate fontfile: '.$fontfile);
		}
		$fontfile = realpath($fontfile);
		$lines = explode("\n", $text);
		$count = count($lines);
		$z = round($fontsize * $lineheight);
		$x = round($offset);
		$y = round(imagesy($image) - $offset - ($z * $count) + ($fontsize / $lineheight));

		foreach ($lines as $l => $line) {
			$m = ($z * $l + 1);
			if ($this->strokeColor) {
				for($cx = ($x - abs($stroke)); $cx <= ($x + abs($stroke)); $cx++) {
					for($cy = ($y + $m - abs($stroke)); $cy <= ($y + $m + abs($stroke)); $cy++) {
						imagettftext($image, $fontsize, 0, $cx, $cy, $this->strokeColor, $fontfile, $line);
					}
				}
			}
			imagettftext($image, $fontsize, 0, $x, $y + $m, $this->textColor, $fontfile, $line);
		}
		return $image;
	}

	public function caption($text, $fontsize = 24, $fontfile = 'arial.ttf', $lineheight = 1.1, $offset = 10, $stroke = 1) {
		return $this->captionImage($this->_image, $text, $fontsize, $fontfile, $lineheight, $offset, $stroke);
	}

	public function saveImage(&$image, $path, $quality = null, $filter = null) {
		$this->quality($quality);
		$status = false;
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		switch ($ext) {
			case 'jpg':
			case 'jpeg':
				$status = imagejpeg($image, $path, $this->_quality['quality']);
			break;
			case 'png':
				#imagealphablending($image, true);
				imagesavealpha($image, true);
				$status = imagepng($image, $path, $this->_quality['compression'], $filter);
			break;
			case 'gif':
				$status = imagegif($image, $path);
			break;
			case 'wbmp':
				$status = imagewbmp($image, $path);
			break;
			case 'webp':
				imagesavealpha($image, true);
				$status = imagewebp($image, $path, $this->_quality['quality']);
			break;
			case 'gd':
				$status = imagegd($image, $path);
			break;
			case 'gd2':
				$status = imagegd2($image, $path);
			break;
		}
		return $status;
	}

	public function save($path, $quality = null, $filter = null) {
		if (!$this->_image) {
			throw new \Exception('There is no image loaded...');
			return;
		}
		return $this->saveImage($this->_image, $path, $quality, $filter);
	}

	public function filterImage(&$image, $filter, $options = null) {
		if (!is_null($options) && !is_array($options)) {
			throw new \Exception('options needs to be array');
			return;
		}
		switch (strtolower($filter)) {
			case 'gray':
			case 'grey':
				imagefilter($image, IMG_FILTER_GRAYSCALE);
			break;
			case 'light':
			case 'bright':
				imagefilter($image, IMG_FILTER_BRIGHTNESS, (int) $options[0]);
			break;
		}
		return $image;
	}
	public function filter($filter, $options = null) {
		if (!$this->_image) {
			throw new \Exception('There is no image loaded...');
		}
		return $this->filterImage($this->_image, $filter, $options);
	}

	public function wipe(&$image = null) {
		if (is_null($image) && $this->_image) {
			imagedestroy($this->_image);
			$this->_image = null;
			$this->_file = null;
			return true;
		}
		if ($this->is_gd($image)) {
			imagedestroy($image);
			$image = null;
			return true;
		}
	}

	public function create($width, $height, $background = false) {
		$image = imagecreatetruecolor($width, $height);
		imagealphablending($image, false);
		#imagesavealpha($image, true);
		if ($background) {
			switch(strtolower($background)) {
				case 'black':
					$bg = imagecolorallocate($image, 0, 0, 0);
				break;
				default:
					$bg = imagecolorallocate($image, 255, 255, 255);
				break;
			}
			imagefill($image, 0, 0, $bg);
		}
		if ($image) {
			return $image;
		}
	}

	public function rescale($width, $height) {
		if ($this->_width && $this->_height) {
			$ratio = $this->_width / $this->_height;
			if (($this->_width <= $width) && ($this->_height <= $height)) {
				return [$this->_width, $this->_height];
			}
			if ($width / $height > $ratio) {
				$width = $height * $ratio;
			}
			else {
				$height = $width / $ratio;
			}
		}
		return [floor($width), floor($height), $ratio];
	}

	public function square($width=null, $height=null) {
		$width = (int) $width ? $width : $this->_width;
		$height = (int) $height ? $height : $this->_height;
		if ($width > $height) {
			$y = 0;
			$x = ($width - $height) / 2;
			$z = $height;
		}
		else {
			$x = 0;
			$y = ($height - $width) / 2;
			$z = $width;
		}
		return [$x, $y, $z];
	}

	public function resizeImage($image, $width=3, $height=3, $high = false) {
		$new = $this->create($width, $height);
		if ($high) {
			imagecopyresampled($new, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
		}
		else {
			imagecopyresized($new, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
		}
		return $new;
	}

	public function resize($width=3, $height=3, $high = false) {
		if ($this->_image) {
			$this->_image = $this->resizeImage($this->_image, $width, $height, $high);
			$this->_width = $width;
			$this->_height = $height;
		}
		return $this->_image;
	}

	public function cropImage($image, $width, $height, $z, $square, $high = false) {
		$new = $this->create($square, $square);
		if ($high) {
			imagecopyresampled($new, $image, 0, 0, $width, $height, $square, $square, $z, $z);
		}
		else {
			imagecopyresized($new, $image, 0, 0, $width, $height, $square, $square, $z, $z);
		}
		return $new;
	}

	public function crop($width, $height, $z, $square, $high = false) {
		if (!$this->_image) {
			throw new \Exception("There is no image loaded...");
		}
		$this->image = $this->cropImage($this->_image, $width, $height, $z, $square, $high);
		$this->width = $width;
		$this->height = $height;
	}

	public function mosaic(int $width = 4, int $height = null, bool $hex = true) {
		if (!$this->_image) {
			throw new \Exception("There is no image loaded...");
		}
		$height = (int) isset($height) ? $height : $width;
		$image = $this->resize($width, $height, false);
		$colors = array();
		for ($y = 0; $y < $height; $y++) {
			for ($x = 0; $x < $width; $x++) {
				if ($hex) {
					$colors[] = sprintf("#%06x", imagecolorat($image, $x, $y));
				}
				else {
					$colors[] = imagecolorat($image, $x, $y);
				}
			}
		}
		if (count($colors) == 1) {
			$this->_mosaic['color'] = $colors[0];
		}
		else {
			$this->_mosaic['colors'] = $colors;
		}
		return $this->_mosaic['color'];
	}

	public function source() {
		return ($this->is_gd($this->_image)) ? $this->_image : false;
	}
	public function image() {
		return $this->source();
	}
	public function width() {
		return ($this->is_gd($this->_image)) ? $this->_width : false;
	}
	public function height() {
		return ($this->is_gd($this->_image)) ? $this->_height : false;
	}
	public function mosaicColor() {
		return $this->_mosaic['color'];
	}
	public function mosaicColors() {
		return $this->_mosaic['colors'];
	}
	public function colors() {
		return $this->_colors;
	}
	public function size() {
		return array($this->_width, $this->_height);
	}
	public function support() {
		return $this->_support;
	}

	public function __debugInfo() {
		return array(
			'image' => $this->is_gd($this->_image),
			'width' => $this->_width,
			'height' => $this->_height,
			'ratio' => $this->_ratio,
			'quality' => $this->_quality,
			'file' => $this->_file,
			'mime' => $this->_mime,
			'colors' => $this->_colors,
			'mosaic' => $this->_mosaic,
			'support' => $this->_support,
		);
	}

	public function __toString() {
		return json_encode($this->__debugInfo(), (int) JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
}