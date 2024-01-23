<?php
namespace Bang;
Core::mark('Bang\UserGroup.php');

class UserGroup {
	protected
		$data = [
			'id' => 0,
			'name' => null
		];
	function __construct() {
		$this->data = (object) $this->data;
	}
	function id(int $id = null) {
		if (is_null($id))
			return $this->data->id;
		return ($this->data->id = $id);
	}
	function name(string $name = null) {
		if (is_null($name))
			return $this->data->name;
		return ($this->data->name = $name);
	}
	function set(int $id, string $name) {
		$this->id($id);
		$this->name($name);
	}
	function reset() {
		$this->data->id = 0;
		$this->data->name = null;
	}
	function data():object {
		return $this->data;
	}
}

