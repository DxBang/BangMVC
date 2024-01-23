<?php
namespace Bang\Auth;

use Bang\Format;
use Bang\Visitor;
use Bang\Chain\HTTP;


class Microsoft {
	private
		$id,
		$http,
		$clientID,
		$clientSecret,
		$redirectURL,
		$refreshStorage = 3600,
		$storage = '/tmp',
		$scope = 'openid profile user.read GroupMember.Read.All',
		$state,
		$tenant,
		$urlAPI = 'https://graph.microsoft.com/v1.0',
		$urlAuthorize = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize',
		$urlToken = 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token';

	public function __construct(object $oauth) {
		$this->id = Visitor::get('id', 'user') ?? 0;
		$this->http = new HTTP();
		$this->tenant = $oauth->tenant;
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
		if (is_string($state)) {
			$this->state = $state;
			return true;
		}
		if ($state === true) {
			$this->state = Visitor::get('microsoft_login_state');
			if (preg_match('/^[0-9a-f]{32}$/', $this->state)) {
				return true;
			}
			$this->state = (string) md5(microtime().':'.rand(PHP_INT_MIN, PHP_INT_MAX));
			Visitor::set('microsoft_login_state', $this->state);
			return true;
		}
		return false;
	}
	public function state() {
		if ($this->isStateless()) return null;
		if (is_string($this->state)) return $this->state;
		return null;
	}
	public function verifyState(string $state) {
		if ($this->state() == $state) {
			Visitor::unset('microsoft_login_state');
			return true;
		}
	}
	public function auth(string $code) {
		return $this->authorize($code);
	}
	public function authorize(string $code) {
		$auth = $this->http->url(
				preg_replace('/{tenant}/', $this->tenant, $this->urlToken)
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
		if (!$auth || !empty($auth->error)) {
			print_r($auth);
			throw new \Error('cannot login', 401);
		}
		Visitor::set(
			'microsoft_login',
			(array) $auth,
		);
		return true;
	}
	public function accessToken() {
		return Visitor::get('access_token', 'microsoft_login');
	}
	public function refreshToken() {
		return Visitor::get('refresh_token', 'microsoft_login');
	}
	public function authorizeURL() {
		return preg_replace('/{tenant}/', $this->tenant, $this->urlAuthorize).
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
	private function _api(string $api) {
		if (empty($this->accessToken())) throw new \Exception('missing access token', 401);
		return $this->http
			->reset()
			->url($this->urlAPI)
			->headers(
				[
					'Authorization: Bearer '.$this->accessToken(),
				]
			)
			->get($api);
	}
	private function api(string $api) {
		$api = $this->_api($api)
			->json();
		if (!empty($api->error)) {
			throw new \Error('cannot fetch graph', 401);
		}
		return $api;
	}
	private function data(string $api) {
		/*
		$data = $this->_api($api);
		if ($data->info->content_type != 'application/json') {
			print_r($data);
			exit;
		}
		*/
		return $this->_api($api)->data();
	}
	public function getUser() {
		/*
		if ($j = $this->load('getUser')) {
			return $j;
		}
		*/
		$j = $this->api('/me/?$select=id,givenName,surname,displayName,jobTitle,userType,mail,officeLocation,department,skills,interests');
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
	public function getProfilePicture() {
		try {
			$j = $this->api('/me/photos');
			if (empty($j->value)) return;
			return $this->data('/me/photo/$value');
		}
		catch (\Exception $e) {
			return;
		}
		catch (\Error $e) {
			return;
		}
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
