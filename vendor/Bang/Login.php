<?php
namespace Bang;

class Login extends Model {
	public
		$id,
		$name,
		$group,
		$token,
		$core,
		$connect = true;

	function __construct(string $username, string $password) {
		global $core;
		$this->core = &$core;
		parent::__construct();
		return $this->login($username, $password);
	}

	function login(string $username, string $password) {
		if ($r = self::get(
			"SELECT u.*, g.name group_name FROM users u"
				." JOIN groups g USING (group_id)"
				." WHERE u.username = :username"
				." AND u.active = 1"
				." AND u.group_id > 0"
				." LIMIT :limit",
				[
					':username' => $username,
					':limit' => 1,
				]
			)) {
			if (password_verify($password, $r['password'])) {
				foreach ($r as $k => $v) {
					$l = strtolower($k);
					switch ($l) {
						case 'user_id':
							Visitor::$user->set('id', $v);
						break;
						case 'group_id':
							Visitor::$user->group->set('id', $v);
						break;
						case 'group_name':
							Visitor::$user->group->set('name', $v);
						break;
						case 'password':
							# ignore
						break;
						default:
							Visitor::$user->group->set($l, $v);
						break;
					}
				}
				Visitor::$user->fromSession();
				return $r;
			}
		}
	}
}
