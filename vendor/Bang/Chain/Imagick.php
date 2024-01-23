<?php
namespace Bang\Chain;

class Imagick {
	public
		$version	= '0.3',
		$quality	= 90,
		$exif		= [],
		$loaded		= false,
		$saved		= false,
		$reloaded	= false;

	private
		$image,
		$clone,
		$frames,
		$path,
		$draw,
		$history;
	function __construct(string $path = null) {
		$this->history(__FUNCTION__, func_get_args());
		if (!class_exists('imagick')) {
			throw new \Bang\Exception('Missing ImageMagick!!');
		}
		$this->image = new \Imagick();
		if (!is_object($this->image)) {
			throw new \Bang\Exception('Cannot create ImageMagick!!');
		}
		if (!is_null($path)) {
			return $this->load($path);
		}
		return $this;
	}
	private function history(string $function, $args) {
		$this->history[] = [$function => $args];
	}
	function create(int $width, int $height, $background = 'none', $format = 'png') {
		$this->history(__FUNCTION__, func_get_args());
		if ($this->image->newImage($width, $height, new \ImagickPixel($background), $format)) {
			$this->path = tmpfile();
			$this->exif('Software', 'Bang '.$this->version);
			$this->exif('Comment', 'Bang '.$this->version);
			$this->clone = clone $this->image;
			$this->loaded = true;
		}
		return $this;
	}
	function load(string $path) {
		$this->history(__FUNCTION__, func_get_args());
		if (!file_exists($path)) {
			throw new \Bang\Exception('Cannot find imagefile: {'.$path.'}', 1);
		}
		if ($this->image->readImage($path)) {
			$this->path = $path;
		#	$this->clipMaster();
			$this->exif('Software', 'Bang '.$this->version);
			$this->exif('Comment', 'Bang '.$this->version);
			$this->clone = clone $this->image;
			$this->loaded = true;
		}
		return $this;
	}
	function reload() {
		$this->history(__FUNCTION__, func_get_args());
		if ($this->loaded == true) {
			$this->clear();
			$this->clearDraw();
			$this->image = clone $this->clone;
			$this->reloaded = true;
		}
		return $this;
	}
	function exif(string $key, string $value = null) {
		if (is_null($value)) {
			return $this->image->getImageProperty();
		}
		$this->image->setImageProperty('Exif:'.$key, $value);
		if (strtolower($key) == 'comment') {
			return $this->comment($value);
		}
		return $this;
	}
	function comment(string $comment) {
		$this->image->commentImage($comment);
		return $this;
	}
	function clear() {
		$this->history = [];
		$this->history(__FUNCTION__, func_get_args());
		$this->image->clear();
		$this->loaded
			= $this->reloaded
			= $this->path
			= false;
		return $this;
	}
	function save(string $path, string $format = null, bool $overwrite = false) {
		$this->history(__FUNCTION__, func_get_args());
		$_path = pathinfo($path);
		if (!file_exists($path) || $overwrite) {
			$save = false;
			switch (strtolower($_path['extension'])) {
				case 'jpg':
				case 'jpeg':
					$this->image->setFormat('JPG');
					$this->image->setImageCompression(\Imagick::COMPRESSION_JPEG);
					$save = true;
				break;
				case 'png':
					if (!is_null($format)) {
						if (strtolower($format) == 'png8') {
							$this->colorDepth(8);
						}
						$this->image->setFormat($format);
					} else {
						$this->image->setFormat('png24');
					}
					$save = true;
				break;
				case 'gif':
					$this->colorDepth(8);
				break;
			}
			if ($save) {
				$this->image->setImageCompressionQuality($this->quality);
				$this->saved = $this->image->writeImage($path);
			} else {
				$this->saved = false;
			}
		}
		return $this;
	}
	function send(string $file) {
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
	}
	function width() {
		$this->history(__FUNCTION__, func_get_args());
		return $this->image->getImageWidth();
	}
	function height() {
		$this->history(__FUNCTION__, func_get_args());
		return $this->image->getImageHeight();
	}
	function colorDepth(int $depth) {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->quantizeImage($depth * 32, \Imagick::COLORSPACE_SRGB, 0, true, true);
		$this->image->setImageDepth($depth);
		return $this;
	}
	function quality(int $quality) {
		$this->history(__FUNCTION__, func_get_args());
		$this->quality = $quality;
		return $this;
	}
	function resize(int $size) {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->resizeImage($size, $size, \Imagick::FILTER_CATROM, 0.75, true);
		return $this;
	}
	function crop(int $size) {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->cropThumbnailImage($size, $size);
		return $this;
	}
	function caption(string $text, int $offset = 10, int $gravity = \Imagick::GRAVITY_SOUTHWEST) {
		$this->history(__FUNCTION__, func_get_args());
		$this->draw->setGravity($gravity);
		$this->image->annotateImage(
			$this->draw,
			$offset,
			$offset,
			0, 
			$text);
		return $this;
	}
	function captionImage(string $text, int $offset = 10, int $gravity = \Imagick::GRAVITY_SOUTHWEST,
		string $font = null, int $fontSize = 16, string $fontColor = 'white', string $strokeColor = 'black') {
		$this->history(__FUNCTION__, func_get_args());
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
		$this->history(__FUNCTION__, func_get_args());
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
		$this->image->compositeImage($text, \Imagick::COMPOSITE_OVER, 0, 0);
		$draw->clear();
		$text->clear();
		return $this;
	}
	function draw() {
		$this->history(__FUNCTION__, func_get_args());
		if ($this->draw) {
			$this->clearDraw();
		}
		$this->draw = new \ImagickDraw();
		return $this;
	}
	function clearDraw() {
		$this->history(__FUNCTION__, func_get_args());
		if ($this->draw) {
			$this->draw->clear();
		}
	}
	function color(string $color, float $opacity = 1, bool $antiAlias = true) {
		$this->history(__FUNCTION__, func_get_args());
		return $this->fillColor($color, $opacity, $antiAlias);
	}
	function fillColor(string $color, float $opacity = 1, bool $antiAlias = true) {
		$this->history(__FUNCTION__, func_get_args());
		$this->draw->setFillColor(new \ImagickPixel($color));
		$this->draw->setFillOpacity($opacity);
		$this->draw->setTextAntialias($antiAlias);
		return $this;
	}
	function strokeColor(string $color, float $width = 1, float $opacity = 1, bool $antiAlias = true) {
		$this->history(__FUNCTION__, func_get_args());
		$this->draw->setStrokeColor(new \ImagickPixel($color));
		$this->draw->setStrokeWidth($this->pointSize($width));
		$this->draw->setStrokeOpacity($opacity);
		$this->draw->setStrokeAntialias($antiAlias);
	#	$this->draw->setStrokeLineJoin(\Imagick::LINEJOIN_ROUND);
		return $this;
	}
	function merge() {
		$this->history(__FUNCTION__, func_get_args());

	}
	function font(string $path, int $fontSize = 16) {
		$this->history(__FUNCTION__, func_get_args());
		if (!file_exists($path)) {
			throw new \Bang\Exception("Cannot find fontfile: {$path}", 1);
		}
		$this->draw->setFont(realpath($path));
		$this->fontSize($fontSize);
		return $this;
	}
	function fontSize(int $fontSize = 16) {
		$this->history(__FUNCTION__, func_get_args());
		$this->draw->setFontSize($this->pointSize($fontSize));
		return $this;
	}
	function pointSize(float $size) {
		$this->history(__FUNCTION__, func_get_args());
		return $size / 2000 * max($this->width(), $this->height());
	}
	function dpi(int $dpi = 75) {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->setImageResolution($dpi, $dpi);
		return $this;
	}
	function background(string $color = 'red') {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->setImageBackgroundColor(new \ImagickPixel($color));
		return $this;
	}
	function transparent() {
		$this->history(__FUNCTION__, func_get_args());
		#	$this->clip(false);
		$this->image->clipPathImage("#1", false);
		$this->image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_TRANSPARENT);
		return $this;
	}
	function opaque() {
		$this->history(__FUNCTION__, func_get_args());
		$this->image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
		return $this;
	}
	function blur(float $radius = 2, float $sigma = 3, int $channel = \Imagick::CHANNEL_ALL) {
		$this->image->blurImage($radius, $sigma, $channel);
		return $this;
	}
	function invert(bool $gray = false, int $channel = \Imagick::CHANNEL_ALL) {
		$this->image->negateImage($gray, $channel);
		return $this;
	}
	function encipher(string $password) {
		$this->image->encipherImage($password);
		return $this;
	}
	function decipher(string $password) {
		$this->image->decipherImage($password);
		return $this;
	}
	function enhance() {
		$this->image->enhanceImage();
		return $this;
	}
	function frames() {
		return $this->coalesce();
	}
	function coalesce() {
		$this->frames = $this->image->coalesceImages();
		return $this;
	}
	function frame($path, int $delay = 100) {
		$frame = new \Imagick();
		$frame->readImage($path);
		$frame->setImageDelay($delay);
		$this->add($frame);
		return $this;
	}
	function add(\Imagick $frame) {
		$this->image->addImage(
			$frame
		);
		return $this;
	}
	function delay(int $delay) {
		$this->image->setImageDelay($delay);
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
	function __debugInfo() {
		return [
			'version' => $this->version,
			'quality' => $this->quality,
			'exif' => $this->exif,
			'image' => $this->image,
			'clone' => $this->clone,
			'frames' => $this->frames,
			'draw' => $this->draw,
			'path' => $this->path,
			'loaded' => $this->loaded,
			'saved' => $this->saved,
			'history' => $this->history,
		];
	}
	function __toString() {
	}
}
