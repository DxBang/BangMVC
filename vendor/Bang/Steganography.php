<?php
namespace Bang;

class Steganography extends Image {
	/*
		using the method from https://thedebuggers.com/image-steganography-hiding-text-using-php/
	*/
	function encrypt(string $loadImageFile, string $message, string $saveImageFile) {
		$this->load($loadImageFile);
		$binary = $this->binary($message);
		$length = strlen($binary);
		for ($i=0; $i<$length; $i++){
			$rgb = imagecolorat($this->_image, $i, $i);
			$r = ($rgb >>16) & 0xFF;
			$g = ($rgb >>8) & 0xFF;
			$b = $rgb & 0xFF;

			$newR = $r;
			$newG = $g;
			$newB = $this->binary($b);
			$newB[strlen($newB)-1] = $binary[$i];
			$newB = $this->string($newB);

			$new_color = imagecolorallocate($this->_image, $newR, $newG, $newB);
			imagesetpixel($this->_image, $i, $i, $new_color);
		}
		return $this->save($saveImageFile);
	}
	function decrypt(string $loadImageFile, int $length = 40) {
		$this->load($loadImageFile);
		$length = $length * 8;
		$message = '';
		for ($i=0; $i<$length; $i++) {
			$rgb = imagecolorat($this->_image, $i, $i);
			$r = ($rgb >>16) & 0xFF;
			$g = ($rgb >>8) & 0xFF;
			$b = $rgb & 0xFF;
			$blue = $this->binary($b);
			$message .= $blue[strlen($blue)-1];
		}
		return $this->string($message);
	}
	function binary(string $string) {
		$length = strlen($string);
		$result = '';
		while ($length--) {
			$result = str_pad(decbin(ord($string[$length])), 8, '0', STR_PAD_LEFT).$result;
		}
		return $result;
	}
	function string(string $binary) {
		return implode(array_map('chr', array_map('bindec', str_split($binary, 8))));
	}
}
