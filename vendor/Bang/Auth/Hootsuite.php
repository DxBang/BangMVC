<?php
namespace Bang\Auth;

use Bang\Format;
use Bang\Visitor;
use Bang\Chain\HTTP;

class Hootsuite {
	public
		$id,
		$http,
		$clientID,
		$clientSecret,
		$redirectURL,
		$refreshStorage = 3600,
		$storage = '/tmp',
		$scope = 'offline',
		$state,
		$urlAuthorize = 'https://platform.hootsuite.com/oauth2/auth',
		$urlToken = 'https://platform.hootsuite.com/oauth2/token';

	public function __construct(object $oauth) {
		$this->id = Visitor::get('id', 'user') ?? 1;
		$this->http = new HTTP();
		$this->clientID = $oauth->clientID;
		$this->clientSecret = $oauth->clientSecret;
		$this->redirectURL = $oauth->redirectURL;
		$this->storage = $oauth->storage ?? $this->storage;
		$this->scope = $oauth->scope ?? $this->scope;
		$this->setState($oauth->state ?? false);
	}
	public function json($data) {
		return json_decode($data, false);
	}
	public function isStateless():bool {
		if (is_bool($this->state) && $this->state === false) return true;
		return false;
	}
	public function setState($state = null) {
		if (is_bool($state)) {
			$this->state = (bool) $state;
		}
		elseif (is_string($state)) {
			$this->state = $state;
		}
	}
	public function state() {
		if ($this->isStateless()) return null;
		if (is_string($this->state)) return $this->state;
		$state = Visitor::get('hootsuite_login_state');
		#$state = 'this-is-a-dumb-state';
		if (empty($state)) {
			$state = md5(microtime().':'.rand(1, 1000000000));
			#$state = $this->state;
			Visitor::set('hootsuite_login_state', $state);
		}
		return $state;
	}
	public function verifyState(string $state) {
		if ($this->state() == $state) {
			Visitor::unset('hootsuite_login_state');
			return true;
		}
	}
	public function auth(string $code) {
		return $this->authorize($code);
	}
	public function authorize(string $code) {
		$auth = $this->http->url(
				$this->urlToken
			)
			->headers(
				[
					'Content-Type: application/x-www-form-urlencoded'
				],
			)
			->post(
				[
					'client_id' => $this->clientID,
					'client_secret' => $this->clientSecret,
					'grant_type' => 'authorization_code',
					'scope' => $this->scope,
					'code' => $code,
					'redirect_uri' => $this->redirectURL,
				]
			)
			->ok()
			->json();
		if (!$auth || $auth->error) {
			print_r($auth);
			throw new \Error('cannot login', 401);
		}
		Visitor::set(
			'hootsuite_login',
			(array) $auth,
		);
		return true;
	}
	public function accessToken() {
		return Visitor::get('access_token', 'hootsuite_login');
	}
	public function refreshToken() {
		return Visitor::get('refresh_token', 'hootsuite_login');
	}
	public function authorizeURL() {
		return $this->urlAuthorize.
			'?'.
			http_build_query(
				[
					'client_id' => $this->clientID,
					'response_type' => 'code',
					'response_mode' => 'query',
					'scope' => $this->scope,
					'state' => $this->state(),
					'redirect_uri' => $this->redirectURL,
				]
			);
	}
	private function api(string $api) {
		if (empty($this->accessToken())) throw new \Exception('missing access token', 401);
		$api = $this->http
			->reset()
			->url('https://platform.hootsuite.com/v1')
			->headers(
				[
					'Authorization: Bearer '.$this->accessToken(),
				]
			)
			->get($api)
			->json();
		if ($api->error) {
			throw new \Error('cannot fetch api', 401);
		}
		return $api;
	}
	public function getUser() {
		/*
		if ($j = $this->load('getUser')) {
			return $j;
		}
		*/
		$j = $this->api('/me');
		$this->save('getUser', $j);
		return $j;
	}
	public function getUserGroups() {
		/*
		if ($j = $this->load('getUser')) {
			return $j;
		}
		*/
		$j = $this->api('/me/memberOf');
		$r = [];
		foreach($j->value as $k => $v) {
			if (empty($v->displayName)) continue;
			$slug = Format::slug($v->displayName);
			$r[$slug] = $v->displayName;
		}
		asort($r);
		$this->save('getUserGroups', $j);
		return (object) $r;
	}
	private function storageFile(string $section) {
		return $this->storage.'/'.$this->id.'_'.$section.'.json';
	}
	public function save(string $section, $data) {
		return file_put_contents($this->storageFile($section), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
	public function load(string $section) {
		if ($this->stored($section))
		return $this->json(file_get_contents($this->storageFile($section)));
	}
	public function stored(string $section) {
		if (!$this->id) return;
		$storageFile = $this->storageFile($section);
		return (file_exists($storageFile) && filemtime($storageFile) > time() - $this->refreshStorage);
	}
	public function __debugInfo() {
		return [
			'id' => $this->id,
			'state' => $this->state(),
			'isStateless' => $this->isStateless(),
		];
	}
	public function __toString() {
		return $this->id;
	}
}
