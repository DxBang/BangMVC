<?php
namespace Bang;
Core::mark('Bang\Visitor.php');
class Visitor {
	public static
		$ip,
		$geo,
		$browser,
		$data,
		$user,
		$flag,
		$group;
	function __construct() {
		self::$ip = self::ip();
		if (Core::get('browser', 'visitor')) {
			self::$browser = new Browser();
		}
		if (Core::get('geo', 'visitor')) {
			self::$geo = new Geo();
		}
		self::$user = new User();
		self::$flag = &self::$user->flag;
		self::$group = &self::$user->group;
		self::$data = (object) [];
		self::init();
		if (self::$flag->isBanned()) {
			header('X-Banned: true', true, 401);
			die('you are banned...');
		}
		if (self::$flag->isSuspended()) {
			header('X-Suspended: true', true, 401);
			die('you are suspended...');
		}
	}
	static function session() {
		return Format::object($_SESSION);
	}
	static function sessionId() {
		return session_id();
	}

	private static function init() {
		self::fromSession();
		self::timezone();
	}
	static function login(int $id, string $username, string $name = null, int $flags = null, array $group = null, string $token = null) {
		return self::set('user', [
			'id' => $id,
			'username' => $username,
			'name' => $name,
			'flags' => $flags,
			'group' => $group,
			'token' => $token,
		]);
		#return self::toSession($id, $username, $name, $flags, $group, $token);
	}
	static function logout() {
		self::reset();
		Core::session_renew();
		//self::fromSession();
	}

	static function setTimezone(string $timezone) {
		$timezone = new \DateTimeZone($timezone);
		$timezone = $timezone->getName();
		if (!$timezone) {
			$timezone = 'UTC';
		}
		self::set('timezone', $timezone);
		return self::timezone();
	}
	static function getTimezone() {
		return self::get('timezone') ?? 'UTC';
	}
	static function timezone() {
		return date_default_timezone_set(self::getTimezone());
	}

	static function getUserGroup() {
		return self::get('group', 'user');
	}
	static function fromSession() {
		if (isset($_SESSION['user']) && count($_SESSION['user'])) {
			foreach ($_SESSION['user'] as $k => $v) {
				switch ($k) {
					case 'group':
						if (!empty($v['id']) && !empty($v['name'])) {
							self::$user->group->set($v['id'], $v['name']);
						}
					break;
					case 'flags':
					#	self::$user->flags = $v;
						self::$user->flag->flags($v);
					break;
					default:
						if (is_array($v)) {
							foreach ($v as $vk => $vv) {
								self::$user->set($vk, $vv, $k);
							}
						}
						else {
							self::$user->set($k, $v);
						}
					break;
				}
			}
		}
	}

	static function toSession(int $id, string $username, string $name, int $flags, array $group, string $token = null) {
		return self::setUser($id, $username, $name, $flags, $group, $token);
		/*
		$this->setUser($id, $username, $name);
		$this->setFlags($flags);
		$this->setGroup($group['id'], $group['name']);
		$this->setToken($token);
		*/
	}

	static function has($k, $a = null) {
		$s = &$_SESSION;
		if (!is_null($a)) {
			if (!isset($_SESSION[$a])) return;
			$s = &$_SESSION[$a];
		}
		return isset($s[$k]);
		/*
		if ($a) {
			if (isset(self::$data->{$a}->{$k})) {
				return true;
			}
			if (isset($_SESSION[$a][$k])) {
				return true;
			}
			return;
		}
		if (isset(self::$data->$k)) {
			return true;
		}
		if (isset($_SESSION[$k])) {
			return true;
		}
		*/
	}
	static function set($k, $v, $a = null) {
		$s = &$_SESSION;
		if (!is_null($a)) {
			if (!isset($_SESSION[$a])) {
				$_SESSION[$a] = [];
			}
			$s = &$_SESSION[$a];
		}
		$s[$k] = $v;
		return;
	}
	static function get($k, $a = null) {
		$s = &$_SESSION;
		if (!is_null($a)) {
			if (!isset($_SESSION[$a])) return;
			$s = &$_SESSION[$a];
		}
		if (is_object($s)) {
			return isset($s->{$k}) ? $s->{$k} : null;
		}
		return isset($s[$k]) ? $s[$k] : null;
	}
	static function unset($k, $a = null) {
		$s = &$_SESSION;
		if (!is_null($a)) {
			if (!isset($_SESSION[$a])) return;
			$s = &$_SESSION[$a];
		}
		unset($s[$k]);
	}
	static function setFlag(int $flag) {

	}
	static function setFlags(int $flags) {
		self::set('flags', $flags, 'user');
		self::$flag->flags($flags);
	}
	static function setGroup(int $id, string $name) {
		self::set('group', [
			'id' => $id,
			'name' => $name,
		], 'user');
		self::$group->set($id, $name);
	}
	static function getGroup() {
		return self::get('group', 'user');
	}
	static function unsetGroup() {
		return self::unset('group', 'user');
	}
	static function id() {
		return self::get('id', 'user');
	}
	static function setId(int $id) {
		return self::set('id', $id, 'user');
	}
	static function getId() {
		return self::get('id', 'user');
	}
	static function unsetId() {
		return self::unset('id', 'user');
	}
	static function username() {
		return self::get('username', 'user');
	}
	static function setUsername(string $username) {
		return self::set('username', $username, 'user');
	}
	static function getUsername() {
		return self::get('username', 'user');
	}
	static function unsetUsername() {
		return self::unset('username', 'user');
	}
	static function name() {
		return self::getName();
	}
	static function setName(string $name) {
		return self::set('name', $name, 'user');
	}
	static function getName() {
		return self::get('name', 'user');
	}
	static function unsetName() {
		return self::unset('name', 'user');
	}
	static function token() {
		$token = self::get('token', 'user');
		if (!empty($token)) return $token;
		return self::genToken();
	}
	static function setToken(string $token) {
		return self::set('token', $token, 'user');
	}
	static function getToken() {
		return self::get('token', 'user');
	}
	static function unsetToken() {
		return self::unset('token', 'user');
	}
	static function genToken() {
		$token = Core::keygen(128);
		self::setToken(
			$token
		);
		return $token;
	}
	static function verifyToken() {
		if (empty($_SERVER['HTTP_TOKEN'])) throw new \Exception('Missing Token');
		if ($_SERVER['HTTP_TOKEN'] !== self::getToken()) throw new \Exception('Incorrect Token');
		return true;
	}

	static function setUser(int $id, string $username, string $name, int $flags, array $group, string $token = null) {
		self::set('user', [
			'id' => $id,
			'username' => $username,
			'name' => $name,
		]);
		if ($token) self::setToken($token);
		self::setFlag($flags);
		self::setGroup($group['id'] ?? 0, $group['name'] ?? 'Guest');
	}
	static function resetUser() {
		return self::unsetUser();
	}
	static function unsetUser() {
		return self::unset('user');
	}
	static function reset() {
		self::unsetUser();
		self::unsetToken();
		self::unsetGroup();
		foreach($_SESSION as $k => $v) {
			self::unset($k);
		}
	}
	static function ip() {
		if (php_sapi_name() == 'cli') return '127.0.0.1';
		if (($_SERVER['REMOTE_ADDR'] == '::1') || ($_SERVER['REMOTE_ADDR'] == '127.0.0.1')) return '127.0.0.1';
		foreach ([
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'X_REAL_IP',
			'REMOTE_ADDR'
			] as $key) {
			if (isset($_SERVER[$key])) {
				foreach (explode(',', $_SERVER[$key]) as $ip) {
					$ip = trim($ip);
					if (filter_var($ip, FILTER_VALIDATE_IP)) {
						return $ip;
					}
				}
			}
		}
	}
	static function code() {
		throw new Exception('use Visitor::countryCode() instead', 1);
	}
	static function countryCode() {
		return self::country()->id;
		if (!empty($_SESSION['visitor'][self::$ip]['id'])) return $_SESSION['visitor'][self::$ip]['id'];
		self::country();
		return $_SESSION['visitor'][self::$ip]['id'];
	}
	static function countryName() {
		return self::country()->name;
	}
	static function country():object {
		if (!empty($_SESSION['visitor'][self::$ip]['country'])) {
			return (object) $_SESSION['visitor'][self::$ip]['country'];
		}
		$geo = Geo::country(self::$ip);
		if ($geo) {
			$_SESSION['visitor'][self::$ip]['country'] = $geo;
		}
		return (object) $_SESSION['visitor'][self::$ip]['country'];
	}
	static function city(string $ip = null):object {
		if (!empty($_SESSION['visitor'][self::$ip]['city'])) {
			return (object) $_SESSION['visitor'][self::$ip]['city'];
		}
		$geo = Geo::city(self::$ip);
		if ($geo) {
			$_SESSION['visitor'][self::$ip]['city'] = $geo;
		}
		return (object) $_SESSION['visitor'][self::$ip]['country'];
	}
	static function debug() {
		echo json_encode(self::data());
	}
	static function data() {
		return (object) [
			'user' => self::$user,
			'flag' => self::$flag,
			'group' => self::$group,
			'ip' => self::$ip,
			'browser' => self::$browser,
			'countryCode' => self::countryCode(),
			'country' => self::country(),
			'city' => self::city(),
		];
	}
}
