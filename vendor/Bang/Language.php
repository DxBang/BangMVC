<?php
namespace Bang;

class Language /* extends Overload */ {
	public
		$data;
	
	function  __construct(object $language = null) {
		$this->data = (object) ['id' => 0];
		if (!empty($language)) {
			$this->data = $language;
		}
	}

	function chain(object $language) {
		$this->data = $this->add($language);
		return $this;
	}

	function __get(string $k) {
		return $this->get($k);
	}

	function get(string $k) {
		return $this->data->{$k} ?? null;
	}

	function add(object $language) {
		$r = $this->data;
		foreach ($language as $k => $v) {
			switch (gettype($v)) {
				case 'string':
				case 'integer':
					$r->{$k} = $v;
				break;
				case 'array':
					$r->{$k} = $r->{$k} ?? [];
					$r->{$k} = $this->_array($r->{$k}, $v);
				break;
				case 'object':
					$r->{$k} = $r->{$k} ?? (object) [];
					$r->{$k} = $this->_object($r->{$k}, $v);
				break;
				default:
					echo $k.' is '.gettype($v).PHP_EOL;
			}
		}
		return $r;
	}

	private function _array(array $a, array $r) {
		foreach ($a as $k => $v) {
			$a[$k] = $v;
		}
		return $r;
	}

	private function _object(object $a, object $r) {
		foreach ($r as $k => $v) {
			switch (gettype($v)) {
				case 'string':
				case 'integer':
					$a->{$k} = $v;
				break;
				case 'array':
					$a->{$k} = $a->{$k} ?? [];
					$a->{$k} = $this->_array($a->{$k}, $v);
				break;
				case 'object':
					$a->{$k} = $a->{$k} ?? (object) [];
					$a->{$k} = $this->_object($a->{$k}, $v);
				break;
			}
		}
		return $a;
	}

	function __debugInfo() {
		return (array) $this->data;
	}

	function __toString() {
		return json_encode($this->__debugInfo(), JSON_ENCODE_SETTINGS);
	}
}
