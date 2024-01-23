<?php
namespace Bang\Chain;

class FileTypeDetect {
	static protected
		$headers = [

		];

	function __construct(string $data = null) {
		if (is_null($data)) return null;
		return $this->detect($data);
	}
	function detect(string $data) {

	}
}
