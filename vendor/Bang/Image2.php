<?php
namespace Bang;

class Image2 {
	public
		$version = '0.3',
		$img,
		$quality	= 90,
		$exif		= [],
		$loaded		= false,
		$saved		= false,
		$reloaded	= false;

	private
		$_img,
		$_path,
		$_draw;
	function __construct() {
		if (!class_exists('imagick')) {
			throw new Exception('Missing ImageMagick!!');
		}
		$this->_img = new \Imagick();
		if (!is_object($this->_img)) {
			throw new Exception('Cannot create ImageMagick!!');
		}
		return $this;
	}
	function load(string $path) {
		if (!file_exists($path)) {
			throw new Exception('Cannot find imagefile: {'.$path.'}', 1);
		}
		if ($this->_img->readImage($path)) {
			$this->_path = $path;
		#	$this->clipMaster();
		#	$this->_img->setImageProperty('exif:Software', '3DX World '.$this->version);
		#	$this->_img->setImageProperty('exif:Artist', 'SignF.dk');
			$this->img    = clone $this->_img;
			$this->loaded = true;
		}
		return $this;
	}
	function reload() {
		if ($this->loaded == true) {
			$this->clear();
			$this->clearDraw();
			$this->img      = clone $this->_img;
			$this->reloaded = true;
		}
		return $this;
	}
	function clear() {
		$this->img->clear();
		return $this;
	}
	function save(string $path, string $format = null, bool $overwrite = false) {
		$_path = pathinfo($path);
		if (!file_exists($path) || $overwrite) {
			$save = false;
			switch (strtolower($_path['extension'])) {
				case 'jpg':
				case 'jpeg':
					$this->img->setFormat('JPG');
					$this->img->setImageCompression(\Imagick::COMPRESSION_JPEG);
					$save = true;
				break;
				case 'png':
					if (!is_null($format)) {
						if ($format == 'png8') {
							$this->colorDepth(8);
						}
						$this->img->setFormat($format);
					} else {
						$this->img->setFormat('PNG24');
					}
					$save = true;
				break;
			}
			if ($save) {
				$this->img->setImageCompressionQuality($this->quality);
				$this->saved = $this->img->writeImage($path);
			} else {
				$this->saved = false;
			}
		}
		return $this;
	}
	function width() {
		return $this->img->getImageWidth();
	}
	function height() {
		return $this->img->getImageHeight();
	}
	function colorDepth(int $depth) {
		$this->img->quantizeImage($depth * 32, \Imagick::COLORSPACE_SRGB, 0, true, true);
		$this->img->setImageDepth($depth);
		return $this;
	}
	function quality(int $quality) {
		$this->quality = $quality;
		return $this;
	}
	function resize(int $size) {
		$this->img->resizeImage($size, $size, \Imagick::FILTER_CATROM, 1, true);
		return $this;
	}
	function caption(string $text, int $offset = 10, int $gravity = \Imagick::GRAVITY_SOUTHWEST) {
		$this->_draw->setGravity($gravity);
		$this->img->annotateImage(
			$this->_draw,
			$offset,
			$offset,
			0, 
    		$text);
		return $this;
	}
	function captionImage(string $text, int $offset = 10, int $gravity = \Imagick::GRAVITY_SOUTHWEST,
		string $font = null, int $fontSize = 16, string $fontColor = 'white', string $strokeColor = 'black') {
		$this->draw()
			->font($font, $fontSize)
			->fillColor($fontColor, 1)
			->strokeColor($strokeColor, 3, 0.8)
			->caption($text, $offset)
			->fillColor($fontColor, 0.8)
			->strokeColor($fontColor, 0.5, 0.6)
			->caption($text, $offset);
		return $this;
	}
	function captionImageHD(string $caption, int $offset = 10, int $gravity = \Imagick::GRAVITY_SOUTHWEST,
		string $font = null, int $fontSize = 16, string $fontColor = 'white', string $strokeColor = 'black') {
		$multiplier = 2;
		$draw = new \ImagickDraw();
		$draw->setFont($font);
		$draw->setFontSize($this->pointSize($fontSize) * $multiplier);
		$draw->setFillColor($fontColor);
		$draw->setTextAntialias(true);
		$draw->setStrokeColor($strokeColor);
		$draw->setStrokeWidth(1);
		$draw->setStrokeAntialias(true);
		$draw->setGravity($gravity);
		$text = new \Imagick();
		$text->newImage($this->width()*$multiplier, $this->height()*$multiplier, 'transparent');
		$text->annotateImage($draw, $offset*$multiplier, $offset*$multiplier, 0, $caption);
		$text->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
		$text->resizeImage($this->width(), $this->height(), \Imagick::FILTER_MITCHELL, 1, true);
		$text->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
		$this->img->compositeImage($text, \Imagick::COMPOSITE_OVER, 0, 0);
		$draw->clear();
		$text->clear();
		return $this;
	}
	function draw() {
		if ($this->_draw) {
			$this->clearDraw();
		}
		$this->_draw = new \ImagickDraw();
		return $this;
	}
	function clearDraw() {
		if ($this->_draw) {
			$this->_draw->clear();
		}
	}
	function fillColor(string $color, float $opacity = 1, bool $antiAlias = true) {
		$this->_draw->setFillColor($color);
		$this->_draw->setFillOpacity($opacity);
		$this->_draw->setTextAntialias($antiAlias);
		return $this;
	}
	function strokeColor(string $color, float $width = 1, float $opacity = 1, bool $antiAlias = true) {
		$this->_draw->setStrokeColor($color);
		$this->_draw->setStrokeWidth($this->pointSize($width));
		$this->_draw->setStrokeOpacity($opacity);
		$this->_draw->setStrokeAntialias($antiAlias);
	#	$this->_draw->setStrokeLineJoin(\Imagick::LINEJOIN_ROUND);
		return $this;
	}
	function merge() {

	}
	function font(string $path, int $fontSize = 16) {
		if (!file_exists($path)) {
			throw new \Exception("Cannot find fontfile: {$path}", 1);
		}
		$this->_draw->setFont(realpath($path));
		$this->fontSize($fontSize);
		return $this;
	}
	function fontSize(int $fontSize = 16) {
		$this->_draw->setFontSize($this->pointSize($fontSize));
		return $this;
	}
	function pointSize(float $size) {
		return $size / 2000 * max($this->width(), $this->height());
	}
	function dpi(int $dpi = 75) {
		$this->img->setImageResolution($dpi, $dpi);
		return $this;
	}
	function background(string $color = 'red') {
		$this->img->setImageBackgroundColor(new \ImagickPixel($color));
		return $this;
	}
	function transparent() {
		#	$this->clip(false);
		$this->img->clipPathImage("#1", false);
		$this->img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_TRANSPARENT);
		return $this;
	}
	function opaque() {
		$this->img->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
		return $this;
	}
	/*
	contrastImage
	brightnessContrastImage
	filter
	gammaImage
	identifyImage
	normalizeImage
	oilPaintImage
	sharpenImage
	shaveImage
	sigmoidalContrastImage
	textureImage
	thumbnailImage
	uniqueImageColors
	*/
}
