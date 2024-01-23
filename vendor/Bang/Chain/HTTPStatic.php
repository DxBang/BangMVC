<?php
namespace Bang\Chain;

class HTTPStatic {
	const
		MAX_REDIRECT = 10,
		BAD_URL = 9001,
		MISSING_URL = 9002,
		MISSING_METHOD = 9003,
		TOO_MANY_REDIRECTIONS = 9004;
	private static
		$url,
		$uri,
		$purl,
		$data,
		$response,
		$referer,
		$request = [],
		$cookies = [],
		$cookieFile,
		$userAgent,
		$proxy,
		$pretty,
		$downloadAs;
	protected static
		$curl,
		$auth,
		$info = [], /* curl info */
		$method,
		$send, /* post */
		$asJSON,
		$file, /* file handler */
		$verifyCert,
		$certInfo,
		$code,
		$contentType,
		$caFile,
		$enCoding,
		$options,
		$redirected = 0;

	public function __construct() {
		$this->userAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
		$this->enCoding($_SERVER['HTTP_ENCODING'] ?? 'deflate, gzip');
		self::init();
	}
	public function __destruct() {
		self::close();
	}
	private static function init() {
		if (self::$curl == null) {
			self::$curl = curl_init();
		}
	}
	private static function close() {
		if (is_object(self::$file)) {
			fclose(self::$file);
			self::$file = null;
		}
		if (self::$curl != null) {
			curl_close(self::$curl);
			self::$curl = null;
		}
	}
	public function reset() {
		$this::close();
		$this::init();
		self::$url
			= self::$uri
			= self::$referer
			= self::$downloadAs
			= self::$data
			= self::$method
			= self::$code
			= self::$contentType
			= self::$enCoding
			= self::$redirected
			= self::$certInfo
			= self::$auth
			= self::$pretty
			= self::$asJSON
			= self::$response
			= null;
		self::$request
			= self::$info
			= self::$cookies
			= self::$send
			= [];
		return $this;
	}
	public function url(string $url) {
		self::_url($url);
		return $this;
	}
	public function uri(string $uri) {
		self::$uri = $uri;
		return $this;
	}
	private static function parseURL() {
		self::$purl = (object) parse_url(self::fullURL());
		return self::$purl;
	}
	private static function _url(string $url) {
		self::$url = self::_sanitizeURL($url);
	}
	private static function fullURL() {
		return self::$url.(!empty(self::$uri) ? self::$uri : '');
	}
	public function getURL() {
		return self::fullURL();
	}
	public function referer(string $referer = null) {
		self::_referer($referer);
		return $this;
	}
	private static function _referer(string $referer = null) {
		self::$referer = self::_sanitizeURL($referer);
	}
	private static function _sanitizeURL(string $url = null) {
		if (is_null($url)) return;
		try {
			if (!parse_url($url, PHP_URL_SCHEME)) {
				throw new \Exception('Bad URL: '.$url, self::BAD_URL);
			}
			$url = explode('#', $url, 2);
			return $url[0];
		} catch (\Exception $e) {
			throw new \Exception('Error in sanitizing', 1, $e);
		}
	}
	public function verifyCert() {
		self::$verifyCert = true;
		return $this;
	}
	public function pretty() {
		self::$pretty = true;
		return $this;
	}
	public function downloadAs(string $file = null) {
		self::$downloadAs = $file;
		return $this;
	}
	public function headers($headers = null) {
		return $this->request($headers);
	}
	public function request($request = null) {
		if (is_array($request)) {
			self::$request = $request;
		}
		elseif (is_string($request)) {
			self::$request = explode("\n", str_replace("\r", '', trim($request)));
		}
		elseif (is_null($request)) {
			self::$request = [];
		}
		foreach (self::$request as $request) {
			$e = explode(':', $request, 2);
			if ((strtolower($e[0]) == 'content-type') && (trim($e[1]) == 'application/json')) {
				self::asJSON(true);
			}
		}
		return $this;
	}
	public function userAgent(string $userAgent = null) {
		self::$userAgent = $userAgent;
		return $this;
	}
	public function enCoding(string $enCoding = null) {
		self::$enCoding = $enCoding;
		return $this;
	}
	public function login(string $username, string $password) {
		return self::auth($username.':'.$password);
	}
	public function auth(string $auth) {
		self::$auth = $auth;
		return $this;
	}
	public static function isSecured() {
		if (empty(self::$purl->scheme))
			self::parseURL();
		if (empty(self::$purl->scheme)) return;
		switch (strtolower(self::$purl->scheme)) {
			case 'https':
			case 'ftps':
				return true;
			break;
		}
	}
	public function proxy(string $proxy = null) {
		self::$proxy = $proxy;
		return $this;
	}
	private static function _options() {
		$r = [
			CURLOPT_URL => self::fullURL(),
			CURLOPT_REFERER => self::$referer,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_DNS_CACHE_TIMEOUT => 60,
			CURLOPT_CONNECTTIMEOUT => 600,
			CURLOPT_TIMEOUT => 6000,
			CURLOPT_USERAGENT => self::$userAgent,
			CURLOPT_ENCODING => self::$enCoding,
			CURLOPT_HTTPHEADER => self::$request,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_VERBOSE => 0,
			CURLOPT_HEADER => 1,
			CURLOPT_COOKIESESSION => true,
		];
		switch (self::$method) {
			case 'GET':
				if (!empty(self::$send)) {
					$r[CURLOPT_URL] .= '?'.http_build_query(self::$send);
				}
			break;
			case 'TOUCH':
				$r[CURLOPT_CUSTOMREQUEST] = 'GET';
				$r[CURLOPT_NOBODY] = 1;
			break;
			case 'HEAD':
				$r[CURLOPT_NOBODY] = 1;
			break;
			case 'POST':
				$r[CURLOPT_POST] = 1;
				$r[CURLOPT_POSTFIELDS] = self::$asJSON ? json_encode(self::$send, JSON_UNESCAPED_SLASHES) : http_build_query(self::$send);
			break;
			case 'PUT':
				$r[CURLOPT_PUT] = 1;
				$r[CURLOPT_POSTFIELDS] = http_build_query(self::$send);
			break;
			case 'DELETE':
				$r[CURLOPT_CUSTOMREQUEST] = 'DELETE';
				$r[CURLOPT_POSTFIELDS] = http_build_query(self::$send);
			break;
			case 'OPTIONS':
			case 'CONNECT':
		}
		if (self::$auth) {
			$r[CURLOPT_USERPWD] = self::$auth;
			$r[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
		}
		if (empty(self::$caFile) && self::isSecured()) {
			if (defined('BANG_DATA')) {
				self::_caFile(BANG_DATA.'/cacert.pem');
			}
		}
		if (self::$caFile) {
			$r[CURLOPT_CAINFO] = self::$caFile; 
		}
		if (self::$proxy) {
			$r[CURLOPT_PROXY] = self::$proxy;
		}
		if (self::$verifyCert) {
			$r[CURLOPT_VERBOSE] = 1;
			$r[CURLOPT_CERTINFO] = 1;
			$r[CURLOPT_SSL_VERIFYSTATUS] = 1;
		}
		if (self::$cookies) {
			$r[CURLOPT_COOKIE] = self::$cookies;
		}
		if (self::$cookieFile) {
			$r[CURLOPT_COOKIEJAR] = self::$cookieFile;
			$r[CURLOPT_COOKIEFILE] = self::$cookieFile;
		}
		if (self::$downloadAs) {
			self::$file = fopen(self::$downloadAs, 'w'); 
			$r[CURLOPT_FILE] = self::$file;
		}
		else {
			$r[CURLOPT_RETURNTRANSFER] = 1;
		}
		self::$options = $r;
		return $r;
	}
	private static function _execute() {
		try {
			if (self::$redirected > self::MAX_REDIRECT) {
				throw new \Exception('Too many redirections', self::TOO_MANY_REDIRECTIONS);
			}
			if (empty(self::$url)) {
				throw new \Exception('Missing URL', self::MISSING_URL);
			}
			if (empty(self::$method)) {
				throw new \Exception('Missing method', self::MISSING_METHOD);
			}
			self::init();
			curl_setopt_array(self::$curl, self::_options());
			self::$data = curl_exec(self::$curl);
			self::$info = (object) curl_getinfo(self::$curl);
			self::$response = substr(self::$data, 0, self::$info->header_size);
			self::$data = substr(self::$data, self::$info->header_size);
			self::close();
			if ((self::$info->http_code == 301) || (self::$info->http_code == 302)) {
				self::$redirected++;
				self::_referer(self::$url);
				self::_url(self::$info->redirect_url);
				return self::_execute();
			}
			else {
				self::$code = (int) self::$info->http_code;
				self::$contentType = self::$info->content_type;
				if (self::$downloadAs) {
					return (self::$info->http_code >= 200 && self::$info->http_code < 300) ? self::$info->download_content_length : false;	
				}
				return (self::$info->http_code >= 200 && self::$info->http_code < 300) ? self::$data : false;
			}
		} catch (\Exception $e) {
			throw new \Exception('Error in execution', 1, $e);
		}
		return;
	}
	public function cookies(array $cookies = null) {
		self::$cookies = $cookies;
		return $this;
	}
	public function cookieFile(string $cookieFile) {
		self::$cookieFile = $cookieFile;
		return $this;
	}
	private static function _caFile(string $caFile) {
		self::$caFile = realpath($caFile);
	}
	public function caFile(string $caFile) {
		self::_caFile($caFile);
		return $this;
	}
	public function send(array $data = null) {
		self::$send = $data;
		return $this;
	}
	public function done() {
		self::$send = null;
		return $this;
	}
	private function _setSendData($URIorData, $data) {
		if (is_string($URIorData)) {
			self::$uri = $URIorData;
		}
		else if (is_array($URIorData) && empty($data)) {
			self::send($URIorData);
		}
		if (!empty($data)) {
			self::send($data);
		}
	}
	public function get($URIorData = null, array $data = null) {
		self::$method = 'GET';
		self::_setSendData($URIorData, $data);
		self::_execute();
		return $this;
	}
	public function touch($URIorData = null, array $data = null) {
		self::$method = 'TOUCH';
		self::_setSendData($URIorData, $data);
		self::_execute();
		return $this;
	}
	public function post($URIorData = null, array $data = null) {
		self::$method = 'POST';
		self::_setSendData($URIorData, $data);
		self::_execute();
		return $this;
	}
	public function asJSON(bool $toggle = true) {
		self::$asJSON = $toggle;
		return $this;
	}
	public function head($URI = null, bool $real = true) {
		self::$method = $real ? 'HEAD' : 'TOUCH';
		self::_setSendData($URI, null);
		self::_execute();
		return $this;
	}
	public function connect() {
		self::$method = 'CONNECT';
		self::_execute();
		return $this;
	}
	public function put() {
		self::$method = 'PUT';
		self::_execute();
		return $this;
	}
	public function options() {
		self::$method = 'OPTIONS';
		self::_execute();
		return $this;
	}
	public function delete() {
		self::$method = 'DELETE';
		self::_execute();
		return $this;
	}
	public function upload(string $file) {
		self::$method = 'UPLOAD';
		self::$send = $file;
		self::_execute();
		return $this;
	}
	public static function code() {
		return (int) self::$code;
	}
	public static function codeArea() {
		return (int) substr(self::$code, 0, 1);
	}
	public static function header(bool $lowercase = false) {
		return self::response($lowercase);
	}
	public static function response(bool $lowercase = false) {
		$responses = explode("\n", self::$response);
		$r = [];
		foreach ($responses as $k => $response) {
			if (!trim($response)) continue;
			$e = preg_split('/\:|\s/', trim($response), 2);
			if ($lowercase) {
				$e[0] = strtolower($e[0]);
			}
			$r[$e[0]] = trim($e[1]);
		}
		return (object) $r;
	}
	public static function data() {
		return self::$data;
	}
	public static function json(bool $asArray = false, int $depth = 512) {
		return json_decode(self::data(), $asArray, $depth);
	}
	public static function info() {
		return self::$info;
	}
	public function debug() {
		print_r($this->__debugInfo());
		return $this;
	}
	public function __debugInfo() {
		return [
			'method' => self::$method,
			'auth' => self::$auth,
			'caFile' => self::$caFile,
			'url' => self::fullURL(),
			'uri' => self::$uri,
			'purl' => self::$purl,
			'send' => self::$send,
			'referer' => self::$referer,
			'redirected' => self::$redirected,
			'request' => self::$request,
			'userAgent' => self::$userAgent,
			'contentType' => self::$contentType,
			'enCoding' => self::$enCoding,
			'info' => self::$info,
			'code' => self::$code,
			'options' => self::$options,
			'response' => self::$response,
			'data' => self::$data,
		];
	}
	public function __toString() {
		return json_encode($this->__debugInfo(), self::$pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES);
	}
}
