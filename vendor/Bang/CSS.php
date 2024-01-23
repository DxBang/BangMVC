<?php
namespace Bang;

class CSS {
	static
		$data;

	static function read(string $css) {

	}
	static function blocks(string $css) {
		preg_match('/(?<block>[a-z\.,#])+({^})(.+)(})/', $css, $m);
	}

}