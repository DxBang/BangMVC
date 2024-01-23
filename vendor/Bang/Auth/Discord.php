<?php
namespace Bang\Auth;
use Bang\Core;
use Bang\Visitor;
use Bang\Chain\HTTP;

class Discord {
	protected
		$id,
		$http,
		$clientID,
		$clientSecret,
		$redirectURL,
		$refreshStorage = 3600,
		$storage = '/tmp',
		$scope = 'identify guilds',
		$state,
		$sessionPrefix = 'discord_login',
		$urlAPI = 'https://discord.com/api/v9',
		$urlAuthorize = 'https://discord.com/api/v9/oauth2/authorize',
		$urlToken = 'https://discord.com/api/v9/oauth2/token';

	public function __construct(object $oauth) {
		$this->id = Visitor::get('id', 'user') ?? 0;
		$this->http = new HTTP();
		$this->http->userAgent('DiscordBot ('.Core::mainSite().', '.constant('BANG_VERSION').')');
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
		return false;
	}
	public function state() {
		if ($this->isStateless()) return null;
		if (is_string($this->state)) return $this->state;
		if ($this->state === true) {
			$this->state = Visitor::get($this->sessionPrefix.'_state');
			if (preg_match('/^[0-9a-f]{32}$/', $this->state)) {
				return true;
			}
			$this->state = (string) md5(microtime().':'.rand(PHP_INT_MIN, PHP_INT_MAX));
			Visitor::set($this->sessionPrefix.'_state', $this->state);
			return true;
		}
		return null;
	}
	public function verifyState(string $state = null) {
		if ($this->isStateless()) return true;
		if ($this->state() == $state) {
			Visitor::unset($this->sessionPrefix.'_state');
			return true;
		}
		return false;
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
				]
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
		print_r($auth);
		if (empty($auth)) {
			throw new \Error('cannot login', 401);
		}
		if (!empty($auth->message)) {
			throw new \Error('authorize error: '.$auth->message, 401);
		}
		Visitor::set(
			$this->sessionPrefix,
			(array) $auth,
		);
		return true;
	}
	public function accessToken() {
		return Visitor::get('access_token', $this->sessionPrefix);
	}
	public function refreshToken() {
		return Visitor::get('refresh_token', $this->sessionPrefix);
	}
	public function authorizeURL() {
		return $this->urlAuthorize
			.'?'
			.http_build_query(
				[
					'client_id' => $this->clientID,
					'response_type' => 'code',
					'scope' => $this->scope,
					'state' => $this->state(),
					'redirect_uri' => $this->redirectURL,
				]
			);
	}
	protected function get(string $api) {
		#echo 'get('.$api.')'.PHP_EOL;
		if (empty($this->accessToken())) throw new \Exception('missing access token', 401);
		$api = $this->http
			->reset()
			->url($this->urlAPI)
			->headers(
				[
					'Authorization: Bearer '.$this->accessToken(),
				]
			)
			->get($api)
			->json();
		if (!empty($api->error)) {
			throw new \Error('api error: '.$api->error, 401);
		}
		return $api;
	}
	protected function post(string $api, object $data) {
		#echo 'post('.$api.')'.PHP_EOL;
		if (empty($this->accessToken())) throw new \Exception('missing access token', 401);
		$api = $this->http
			->reset()
			->url($this->urlAPI)
			->headers(
				[
					'Authorization: Bearer '.$this->accessToken(),
				]
			)
			->post($api, $data)
			->json();
		if (!empty($api->error)) {
			throw new \Error('api error: '.$api->error, 401);
		}
		return $api;
	}
	private function api(string $api) {
		return $this->get($api);
	}
	public function getUser() {
		$j = $this->get('/users/@me');
		$this->save('getUser', $j);
		return $j;
	}
	public function getUserGuilds() {
		$j = $this->get('/users/@me/guilds');
		$this->save('getUserGroups', $j);
		return $j;
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
			'http' => $this->http,
			#'clientID' => $this->clientID,
			#'clientSecret' => $this->clientSecret,
			'redirectURL' => $this->redirectURL,
			'refreshStorage' => $this->refreshStorage,
			'storage' => $this->storage,
			'scope' => $this->scope,
			'state' => $this->state,
			'urlAPI' => $this->urlAPI,
			'urlAuthorize' => $this->urlAuthorize,
			'urlToken' => $this->urlToken,
			'authorizeURL' => $this->authorizeURL(),
			'isStateless' => $this->isStateless(),
		];
	}
	public function __toString() {
		return $this->id;
	}
}