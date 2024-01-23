<?php
namespace Bang;
Core::mark('Bang\User.php');

class User {
	public
		$flag,
		$group;
	protected
		$flags,
		$data = [
			'id' => 0,
			'name' => null,
			'username' => null
		];

	function __construct() {
		$this->data = (object) $this->data;
		$this->flag = new UserFlag();
		$this->group = new UserGroup();
	}
	function set($k, $v, $a = null) {
		if (!is_null($a)) {
			if (is_array($v)) {
				foreach ($v as $vk => $vv) {
					$this->data->{$a}->{$vk} = $vv;
				}
				return;
			}
			$this->data->{$a}->{$k} = $v;
			return;
		}
		if (is_array($v)) {
			foreach ($v as $vk => $vv) {
				$this->data->{$k}->{$vk} = $vv;
			}
			return;
		}
		$this->data->{$k} = $v;
	}
	function get($k, $a = null) {
		if ($a) {
			if (isset($this->data->{$a}->{$k})) {
				return $this->data->{$a}->{$k};
			}
			return;
		}
		if (isset($this->data->{$k})) {
			return $this->data->{$k};
		}
	}
	function has($k, $a = null) {
		if ($a) {
			if (isset($this->data->{$a}->{$k})) {
				return true;
			}
			return;
		}
		if (isset($this->data->$k)) {
			return true;
		}
	}
	function unset($k, $a = null) {
		if ($a) {
			unset($this->data->{$a}->{$k});
		}
		unset($this->data->{$k});
	}
	function setUser(int $id, string $username, string $name = null) {
		$this->set('id', $id);
		$this->set('username', Format::username($username));
		$this->set('name', $name);
	}
	function getUser() {
		return $this->data;
	}
	function id() {
		return $this->get('id');
	}
	function username() {
		return $this->get('username');
	}
	function name() {
		return $this->get('name');
	}
	function setFlags(int $flags) {
		return $this->flag->flags($flags);
	}
	function getFlags() {
		return $this->flag->flags();
	}

	function date(string $date, string $time = null) {
		return Format::date($date, $time);
	}
	function time(string $time) {
		return Format::time($time);
	}
	function setToken(string $token = null) {
		$this->set('token', $token);
	}
	function getToken() {
		return $this->get('token');
	}
	function setGroup(int $id,  string $name) {
		$this->group->set($id, $name);
	}
	function getGroup() {
		return $_SESSION['user']['group'];
	}
	function fromSession() {
		throw new \Exception('user Visitor::get instead', 1);
		/*
		if (isset($_SESSION['user']) && count($_SESSION['user'])) {
			foreach ($_SESSION['user'] as $k => $v) {
				switch ($k) {
					case 'group':
						if (!empty($v['id']) && !empty($v['name'])) {
							$this->group->set($v['id'], $v['name']);
						}
					break;
					case 'flags':
						$this->flags = $v;
						$this->flag->flags($v);
					break;
					default:
						if (is_array($v)) {
							foreach ($v as $vk => $vv) {
								$this->set($vk, $vv, $k);
							}
						}
						else {
							$this->set($k, $v);
						}
					break;
				}
			}
		}
		*/
	}
	function login() {
		throw new \Exception('user Visitor::login instead', 1);
	}
	function logout() {
		throw new \Exception('user Visitor::logout instead', 1);
	}
	function toSession() {
		throw new \Exception('user Visitor::setUser instead', 1);
		/*
		$this->setUser($id, $username, $name);
		$this->setFlags($flags);
		$this->setGroup($group['id'], $group['name']);
		$this->setToken($token);
		*/
	}
	function reset() {
		$this->data = (object) [
			'id' => 0,
			'name' => null,
			'username' => null
		];
		$this->flag->reset();
		$this->group->reset();
	}
	function data() {
		return $this->data;
	}
	function __debugInfo():array {
		return (array) [
			'data' => $this->data
		];
	}
	function __toString():string {
		return json_encode($this->__debugInfo());
	}
}
