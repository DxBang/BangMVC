<?php
namespace Bang;

abstract class Bitwise {
	protected
		$value = 0,
		$store;
	function __construct(&$value = 0) {
		$this->value($value);
		$this->store = &$value;
	}
	function value(int $value = null) {
		if (is_null($value)) {
			return $this->value;
		}
		$this->value = (int) $value;
		return $this->value;
	}
	function flags(int $value = null) {
		return $this->value($value);
	}
	function __set(string $k, $v) {
		return $this->set((int) $k, $v);
	}
	function __get(string $k) {
		return $this->get((int) $k);
	}
	function __isset(string $k) {
		return $this->has((int) $k);
	}
	function __unset(string $k) {
		return $this->rem((int) $k);
	}
	function set(int $k, $v) {
		if ($v) {
			$this->value |= $k;
		}
		else {
			$this->value &= ~$k;
		}
		$this->store = $this->value;
	}
	function has(int $k) {
		return (($this->value & $k) == $k);
	}
	function get(int $k) {
		return (($this->value & $k) == $k);
	}
	function rem(int $k) {
		return $this->__set($k, false);
	}
	function reset() {
		$this->value = 0;
	}

	function array(bool $lowercase = false):array {
		$ref = new \ReflectionClass(get_called_class());
		$const = $ref->getConstants();
		$r = [];
		foreach ($const as $k => $v) {
			if ($lowercase) {
				$r[strtolower($k)] = $this->has($v);
			}
			else {
				$r[$k] = $this->has($v);
			}
		}
		return $r;
	}
	function object(bool $lowercase = false):object {
		return (object) $this->array($lowercase);
	}
	function json(bool $lowercase = false):string {
		return json_encode(
			$this->object($lowercase),
			JSON_UNESCAPED_SLASHES
		);
	}

	function __debugInfo() {
		return $this->array();
	}
	function __toString() {
		return json_encode(
			$this->object(),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);
	}
}