<?php
namespace Bang\Chain;

class HTTP {
	const
		MAX_REDIRECT = 3,
		BAD_URL = 9001,
		MISSING_URL = 9002,
		MISSING_METHOD = 9003,
		TOO_MANY_REDIRECTIONS = 9004,
		EXECUTE_ERROR = 9005;
	private
		$url,
		$uri,
		$purl,
		$data,
		$response,
		$referer,
		$follow = true,
		$request = [],
		$cookies = [],
		$cookieFile,
		$userAgent,
		$pretty,
		$file,
		$fileSize,
		$modified,
		$downloadAs;
	protected
		$curl,
		$_file, /* file handler */
		$info = [], /* curl info */
		$auth,
		$connectionTimeOut = 10,
		$timeOut = 600,
		$method,
		$send, /* post */
		$asXML,
		$asHTML,
		$asJSON,
		$asString,
		$verifyCert,
		$skipCert,
		$certInfo,
		$code,
		$status,
		$contentType,
		$caFile = '/etc/ssl/certs/ca-certificates.crt',
		$enCoding,
		$SSLVersion,
		$SSLCipher,
		$TLS13Cipher,
		$options,
		$proxy,
		$proxyAuth,
		$proxyType,
		$verbose = false,
		$redirected = 0;

	function __construct() {
		$this->userAgent($_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 Bang/3.0');
		$this->enCoding($_SERVER['HTTP_ENCODING'] ?? 'deflate, gzip');
		return $this->init();
	}
	function __destruct() {
		return $this->close(true);
	}
	private function init() {
		if ($this->curl == null) {
			$this->curl = curl_init();
		}
		return $this;
	}
	function close(bool $completely = false):object {
		if (is_resource($this->_file)) {
			fclose($this->_file);
			$this->_file = null;
		}
		if (!is_null($this->curl)) {
			if ($completely) {
				curl_close($this->curl);
				$this->curl = null;
			}
			else {
				curl_reset($this->curl);
			}
		}
		return $this;
	}
	function clean():object {
		$this->data
			= null;
		$this->send
			= [];
		return $this;
	}
	function reset(bool $completely = false):object {
		$this->close($completely);
		$this->init();
		$this->clean();
		$this->url
			= $this->uri
			= $this->referer
			= $this->downloadAs
			= $this->method
			= $this->code
			= $this->contentType
			= $this->enCoding
			= $this->redirected
			= $this->certInfo
			= $this->auth
			= $this->pretty
			= $this->asXML
			= $this->asHTML
			= $this->asJSON
			= $this->asString
			= $this->response
			= $this->file
			= $this->fileSize
			= null;
		$this->request
			= $this->info
			= $this->cookies
			= [];
		return $this;
	}
	function verbose(bool $verbose = true):object {
		$this->verbose = $verbose;
		return $this;
	}
	function url(string $url):object {
		$this->clean();
		$this->_url($url);
		return $this;
	}
	function uri(string $uri):object {
		$this->uri = $uri;
		return $this;
	}
	private function parseUrl():object {
		$this->purl = (object) parse_url($this->fullUrl());
		return $this->purl;
	}
	private function _url(string $url) {
		$this->url = $this->_sanitizeUrl($url);
	}
	private function fullUrl():string {
		return $this->url.(!empty($this->uri) ? $this->uri : '');
	}
	function getUrl():string {
		return $this->fullUrl();
	}
	function referer(string $referer = null):object {
		$this->_referer($referer);
		return $this;
	}
	private function _referer(string $referer = null) {
		$this->referer = $this->_sanitizeURL($referer);
	}
	function getReferer() {
		return $this->referer;
	}
	private function _sanitizeURL(string $url = null) {
		if (is_null($url)) return;
		try {
			if (!parse_url($url, PHP_URL_SCHEME)) {
				print_r($url);
				throw new \Exception('Bad URL: '.$url, self::BAD_URL);
			}
			$url = explode('#', $url, 2);
			return $url[0];
		} catch (\Exception $e) {
			throw new \Exception('Error in sanitizing ['.$url.']', 1, $e);
		}
	}
	function connectionTimeOut(int $connectionTimeOut):object {
		$this->connectionTimeOut = $connectionTimeOut;
		return $this;
	}
	function timeOut(int $timeOut):object {
		$this->timeOut = $timeOut;
		return $this;
	}
	function skipCert():object {
		$this->skipCert = true;
		return $this;
	}
	function verify():object {
		return $this->verifyCert();
	}
	function verifyCert():object {
		$this->verifyCert = true;
		return $this;
	}
	function certInfo():object {
		$this->certInfo = true;
		return $this;
	}
	function pretty():object {
		$this->pretty = true;
		return $this;
	}
	function follow(bool $follow = true):object {
		$this->follow = $follow;
		return $this;
	}
	function downloadAs(string $file = null):object {
		$this->downloadAs = $file;
		return $this;
	}
	private function fixDownload(object $curl_info) {
		if (empty($curl_info->header_size)) return;
		if ($this->downloadAs && file_exists($this->downloadAs) && is_readable($this->downloadAs)) {
			$file = realpath($this->downloadAs);
			$temp = $file.'.tmp';
			$rh = fopen($file, 'r');
			if (!$rh) {
				throw new \Bang\Exception('cannot read the file, i mean wtf?', 1);
			}
			$headers = trim(fread($rh, $curl_info->header_size));
			$wh = fopen($temp, 'w+');
			if (!$rh) {
				throw new \Bang\Exception('cannot write the file, i mean wtf?', 2);
			}
			while (!feof($rh)) {
				fwrite($wh, fread($rh, 1024));
			}
			fclose($rh);
			fclose($wh);
			if (!unlink($file)) {
				throw new \Bang\Exception('cannot delete the file, i mean wtf?', 3);
			}
			if (!rename($temp, $file)) {
				throw new \Bang\Exception('cannot rename the tempfile, i mean wtf?', 4);
			}
			return $headers;
		}
	}
	function headers($headers = null, bool $append = false):object {
		return $this->request($headers, $append);
	}
	function request($request = null, bool $append = false):object {
		if (is_array($request)) {
			if ($append) {
				$this->request = array_merge($this->request, $request);
			}
			else {
				$this->request = $request;
			}
		}
		elseif (is_string($request)) {
			$this->request = explode("\n", str_replace("\r", '', trim($request)));
		}
		elseif (is_null($request)) {
			$this->request = [];
		}
		foreach ($this->request as $request) {
			$e = explode(':', $request, 2);
			if (strtolower($e[0]) == 'content-type') {
				switch (trim($e[1])) {
					case 'application/json':
						$this->asJSON(true);
					break;
					case 'text/xml':
						$this->asXML(true);
					break;
					case 'text/html':
						$this->asHTML(true);
					break;
					case 'text/plain':
						$this->asString(true);
					break;
				}
			}
		}
		return $this;
	}
	function userAgent(string $userAgent = null):object {
		$this->userAgent = $userAgent;
		return $this;
	}
	function enCoding(string $enCoding = null):object {
		$this->enCoding = $enCoding;
		return $this;
	}
	function login(string $username, string $password):object {
		return $this->auth($username.':'.$password);
	}
	function auth(string $auth):object {
		$this->auth = $auth;
		return $this;
	}
	function isSecured():bool {
		if (empty($this->purl->scheme))
			$this->parseURL();
		if (empty($this->purl->scheme)) return false;
		switch (strtolower($this->purl->scheme)) {
			case 'https':
			case 'ftps':
				return true;
			break;
		}
		return false;
	}
	function SSLVersion(int $version) {
		$this->SSLVersion = $version;
		return $this;
	}
	function SSLCipher(string $cipher) {
		$this->SSLCipher = $cipher;
		return $this;
	}
	
	function proxy(string $proxy = null):object {
		$this->proxy = $proxy;
		return $this;
	}
	function proxyAuth(string $proxyAuth = null):object {
		$this->proxyAuth = $proxyAuth;
		return $this;
	}
	function proxyType(string $proxyType = null):object {
		if (is_null($proxyType)) {
			$this->proxyType = null;
			return $this;
		}
		switch (strtolower($proxyType)) {
			case 'http':
				$this->proxyType = CURLPROXY_HTTP;
			break;
			case 'socks4':
				$this->proxyType = CURLPROXY_SOCKS4;
			break;
			case 'socks4a':
				$this->proxyType = CURLPROXY_SOCKS4A;
			break;
			case 'socks5':
				$this->proxyType = CURLPROXY_SOCKS5;
			break;
			case 'sockshost':
			case 'socks5host':
				$this->proxyType = CURLPROXY_SOCKS5_HOSTNAME;
			break;
			default:
				$this->proxyType = null;
		}
		return $this;
	}
	function queryString($data):string {
		$r = '';
		$i = 0;
		foreach ($data as $k => $v) {
			if ($i) $r .= '&';
			switch (strtolower(gettype($v))) {
				case 'null':
					$r .= urlencode($k);
				break;
				case 'array':
				case 'object':
					$ai = 0;
					foreach ($v as $ak => $av) {
						if ($ai) $r .= '&';
						$r .= urlencode($k.'['.$ak.']').'='.urlencode($av);
						$ai++;
					}
				break;
				default:
					$r .= urlencode($k).'='.urlencode($v);
			}
			$i++;
		}
		return $r;
	}
	private function _options():array {
		$r = [
			CURLOPT_URL => $this->fullURL(),
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_DNS_CACHE_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => $this->connectionTimeOut ?? $this->timeOut / 10,
			CURLOPT_TIMEOUT => $this->timeOut,
			CURLOPT_HEADER => 1,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_COOKIESESSION => true,
			CURLINFO_HEADER_OUT => true,
		];
		if (!empty($this->referer)) {
			$r[CURLOPT_REFERER] = $this->referer;
		}
		if (!empty($this->userAgent)) {
			$r[CURLOPT_USERAGENT] = $this->userAgent;
		}
		if (!empty($this->enCoding)) {
			$r[CURLOPT_ENCODING] = $this->enCoding;
		}
		if (!empty($this->request)) {
			$r[CURLOPT_HTTPHEADER] = $this->request;
		}
		switch ($this->method) {
			case 'GET':
				if (!empty($this->send)) {
					$p = (object) parse_url($r[CURLOPT_URL]);
					if (empty($p->query)) {
						$r[CURLOPT_URL] .= '?'.$this->queryString($this->send);
					}
					else {
						parse_str($p->query, $get);
						if (is_string($this->send)) {
							$send = parse_str($this->send, $send);
							$r[CURLOPT_URL] = explode('?', $r[CURLOPT_URL])[0].'?'.$this->queryString(
								array_merge(
									$get,
									$send,
								)
							);
						}
						else if (is_array($this->send) || is_object($this->send)) {
							$r[CURLOPT_URL] = explode('?', $r[CURLOPT_URL])[0].'?'.$this->queryString(
								array_merge(
									$get,
									(array) $this->send,
								)
							);
						}
					}
				}
			break;
			case 'TOUCH':
				$r[CURLOPT_CUSTOMREQUEST] = 'GET';
				$r[CURLOPT_NOBODY] = 1;
			break;
			case 'HEAD':
				$r[CURLOPT_CUSTOMREQUEST] = 'HEAD';
				$r[CURLOPT_NOBODY] = 1;
			break;
			case 'POST':
			case 'PATCH':
			case 'PUT':
				if ($this->method == 'POST') {
					$r[CURLOPT_POST] = 1;
				}
				else {
					$r[CURLOPT_CUSTOMREQUEST] = $this->method;
				}
				if ($this->file) {
					$r[CURLOPT_UPLOAD] = 1;
					$r[CURLOPT_INFILE] = $this->file;
					$r[CURLOPT_INFILESIZE] = $this->fileSize;
				}
				$r[CURLOPT_POSTFIELDS] = $this->asJSON
					? json_encode($this->send, JSON_UNESCAPED_SLASHES)
					: ($this->asString ? $this->send : $this->queryString($this->send));
			break;
			case 'DELETE':
				$r[CURLOPT_CUSTOMREQUEST] = 'DELETE';
				$r[CURLOPT_POSTFIELDS] = $this->queryString($this->send);
			break;
			case 'OPTIONS':
			break;
			case 'CONNECT':
			break;
		}
		if ($this->auth) {
			$r[CURLOPT_USERPWD] = $this->auth;
			$r[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
		}
		if ($this->SSLVersion) {
			$r[CURLOPT_SSLVERSION] = $this->SSLVersion;
		}
		if ($this->SSLCipher) {
			$r[CURLOPT_SSL_CIPHER_LIST] = $this->SSLCipher;
		}
		if ($this->TLS13Cipher) {
			$r[CURLOPT_TLS13_CIPHERS] = $this->TLS13Cipher;
		}
		if (empty($this->caFile) && $this->isSecured()) {
			if (defined('BANG_DATA')) {
				$this->_caFile(BANG_DATA.'/cacert.pem');
			}
		}
		if ($this->caFile) {
			$r[CURLOPT_CAINFO] = $this->caFile; 
		}
		if ($this->proxy) {
			$r[CURLOPT_PROXY] = $this->proxy;
		}
		if ($this->proxyAuth) {
			$r[CURLOPT_PROXYAUTH] = $this->proxyAuth;
		}
		if ($this->proxyType) {
			$r[CURLOPT_PROXYTYPE] = $this->proxyType;
		}
		if ($this->certInfo) {
			$r[CURLOPT_CERTINFO] = 1;
		}
		if ($this->skipCert) {
			$r[CURLOPT_SSL_VERIFYPEER] = 0;
			$r[CURLOPT_SSL_VERIFYHOST] = 0;
			$r[CURLOPT_SSL_VERIFYSTATUS] = 0;
		}
		if ($this->verifyCert) {
			$r[CURLOPT_CERTINFO] = 1;
			$r[CURLOPT_SSL_VERIFYHOST] = 2;
			$r[CURLOPT_SSL_VERIFYSTATUS] = 1;
		}
		if ($this->verbose) {
			$r[CURLOPT_VERBOSE] = 1;
		}
		if ($this->cookies) {
			$r[CURLOPT_COOKIE] = $this->cookies;
		}
		if ($this->cookieFile) {
			$r[CURLOPT_COOKIESESSION] = true;
			$r[CURLOPT_COOKIEJAR]
				= $r[CURLOPT_COOKIEFILE]
				= $this->cookieFile;
		}
		if ($this->downloadAs) {
			$this->_file = fopen($this->downloadAs, 'w'); 
		#	$r[CURLOPT_HEADER] = 1;
		#	$r[CURLOPT_BINARYTRANSFER] = 1;
			$r[CURLOPT_FILE] = $this->_file;
		}
		else {
			$r[CURLOPT_RETURNTRANSFER] = 1;
		}
		$this->options = $r;
		return $r;
	}
	private function _execute() {
		try {
			if (empty($this->url)) {
				throw new \Exception('missing url', self::MISSING_URL);
			}
			if (empty($this->method)) {
				throw new \Exception('missing method', self::MISSING_METHOD);
			}
			if ($this->redirected >= self::MAX_REDIRECT) {
				throw new \Exception('too many redirections: '.$this->redirected, self::TOO_MANY_REDIRECTIONS);
			}
			$this->init();
			curl_setopt_array($this->curl, $this->_options());
			$this->data = curl_exec($this->curl);
			$this->info = (object) curl_getinfo($this->curl);
			if (curl_errno($this->curl)) {
				$this->code = (int) curl_errno($this->curl);
				$this->status = (string) curl_error($this->curl);
				#exit;
			}
			else {
				$this->code = (int) $this->info->http_code;
				$this->status = (string) $this->errorCode($this->code);
			}
			#$this->code = (int) (curl_errno($this->curl) ?? $this->info->http_code);
			$this->close();
			$this->contentType = $this->info->content_type;

			if ($this->downloadAs) {
				$this->response = $this->fixDownload($this->info);
			}
			else {
				$this->response = substr($this->data, 0, $this->info->header_size);
				$this->data = substr($this->data, $this->info->header_size);
			}
			
			if ($this->follow && (in_array($this->info->http_code, [301, 302, 303, 307, 308]))) {
				$this->redirected++;
				$this->_referer($this->url);
				$this->_url($this->info->redirect_url);
				return $this->_execute();
			}
			else {
				if ($this->downloadAs) {
					return ($this->info->http_code >= 200 && $this->info->http_code < 300)
						? $this->info->download_content_length
						: false;	
				}
				return ($this->info->http_code >= 200 && $this->info->http_code < 300)
					? $this->data
					: false;
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
		}
		return;
	}
	function cookies(array $cookies = null):object {
		$this->cookies = $cookies;
		return $this;
	}
	function cookieFile(string $cookieFile):object {
		$this->cookieFile = $cookieFile;
		return $this;
	}
	private function _caFile(string $caFile) {
		$this->caFile = realpath($caFile);
	}
	function caFile(string $caFile):object {
		$this->_caFile($caFile);
		return $this;
	}
	function file(string $file):object {
		if (!file_exists($file)) throw new \Exception('Cannot location file: '.$file);
		if (!is_readable($file)) throw new \Exception('Cannot read file: '.$file);
		$this->file = $file;
		$this->fileSize = filesize($file);
		return $this;
	}
	function send($data = null):object {
		$this->send = $data;
		return $this;
	}
	function done():object {
		$this->send = null;
		return $this;
	}
	private function _setSendData($URIorData, $data = null) {
		if (is_null($data)) {
			if ($this->asString || is_array($URIorData) || is_object($URIorData)) {
				return $this->send($URIorData);
			}
			$this->uri = $URIorData;
			return;
		}
		$this->uri = $URIorData;
		$this->send($data);
		return;
	}
	function isModified(\DateTime $date, string $URI = null):bool {
		return !$this->isLastModified($date, $URI);
	}
	function isLastModified(\DateTime $date, string $URI = null):bool {
		$this->head($URI, true);
		$h = $this->response(2);
		$last = new \DateTime($h->{'last-modified'} ?? 'now');
		return ($date == $last);
	}
	function ifModifiedSince(\DateTime $date, $URIorData = null, $data = null):object {
		$date->setTimezone(new \DateTimeZone('GMT'));
		$this->request('If-Modified-Since: '.$date->format('D, d M Y H:i:s T'), true);
		return $this;
	}
	function head(string $URI = null, bool $real = true):object {
		$this->method = $real ? 'HEAD' : 'TOUCH';
		$this->redirected = 0;
		$this->_setSendData($URI, null);
		$this->_execute();
		return $this;
	}
	function get($URIorData = null, $data = null):object {
		$this->method = 'GET';
		$this->redirected = 0;
		$this->_setSendData($URIorData, $data);
		$this->_execute();
		return $this;
	}
	function touch($URIorData = null, $data = null):object {
		$this->method = 'TOUCH';
		$this->redirected = 0;
		$this->_setSendData($URIorData, $data);
		$this->_execute();
		return $this;
	}
	function post($URIorData = null, $data = null):object {
		$this->method = 'POST';
		$this->redirected = 0;
		$this->_setSendData($URIorData, $data);
		$this->_execute();
		return $this;
	}
	function put($URIorData = null, $data = null):object {
		$this->method = 'PUT';
		$this->redirected = 0;
		$this->_setSendData($URIorData, $data);
		$this->_execute();
		return $this;
	}
	function patch($URIorData = null, $data = null):object {
		$this->method = 'PATCH';
		$this->redirected = 0;
		$this->_setSendData($URIorData, $data);
		$this->_execute();
		return $this;
	}
	function connect():object {
		$this->method = 'CONNECT';
		$this->redirected = 0;
		$this->_execute();
		return $this;
	}
	function options():object {
		$this->method = 'OPTIONS';
		$this->redirected = 0;
		$this->_execute();
		return $this;
	}
	function delete():object {
		$this->method = 'DELETE';
		$this->redirected = 0;
		$this->_execute();
		return $this;
	}
	function upload(string $file):object {
		$this->method = 'UPLOAD';
		$this->redirected = 0;
		$this->send = $file;
		$this->file = $file;
		$this->_execute();
		return $this;
	}
	function asXML(bool $toggle = true):object {
		$this->asXML = $toggle;
		return $this;
	}
	function asHTML(bool $toggle = true):object {
		$this->asHTML = $toggle;
		return $this;
	}
	function asJSON(bool $toggle = true):object {
		$this->asJSON = $toggle;
		return $this;
	}
	function asString(bool $toggle = true):object {
		$this->asString = $toggle;
		return $this;
	}
	function throwOnHTTPError() {
		return $this->ok();
	}
	function ok() {
		if ($this->codeArea() == 2) {
			return $this;
		}
		if ($this->modified && $this->code() == 304) {
			return $this;
		}
		throw new \Exception(
			$this->status(),
			$this->code()
		);
	}
	function contentType() {
		return $this->contentType;
	}
	function type():object {
		if (empty($this->contentType)) return (object) [];
		$e = array_filter(preg_split('/\s?;\s?/', $this->contentType, 2));
		$r['contentType'] = trim($e[0]);
		if (count($e) == 2) {
			$a = preg_split('/\s?;\s?/', $e[1]);
			foreach ($a as $v) {
				$v = preg_split('/\s?=\s?/', $v);
				if (!empty($v))
					$r[strtolower($v[0])] = trim(strtolower($v[1])) ?? null;
			}
		}
		return (object) $r;
	}
	function detectType() {
		return new FileTypeDetect($this->data());
	}
	function code() {
		return (int) $this->code;
	}
	function codeArea() {
		if (strlen($this->code) == 3)
			return (int) substr($this->code, 0, 1);
		return 0;
	}
	function status(bool $lower = false) {
		if ($lower)
			return (string) strtolower($this->status);
		return (string) $this->status;
	}
	function header(int $parse = 1) {
		return $this->response($parse);
	}
	function response(int $parse = 0) {
		if ($parse == 0) return $this->response;
		$responses = explode("\n", $this->response);
		$r = [];
		foreach ($responses as $k => $response) {
			if (!trim($response)) continue;
			$e = preg_split('/\:|\s/', trim($response), 2);
			if ($parse > 1) {
				$e[0] = strtolower($e[0]);
			}
			if (isset($r[$e[0]])) {
				if (!is_array($r[$e[0]])) {
					$r[$e[0]] = [$r[$e[0]]];
				}
				$r[$e[0]][] = trim($e[1]);
			}
			else {
				$r[$e[0]] = trim($e[1]);
			}
		}
		return (object) $r;
	}
	function errorCode(int $code) {
		switch ($code) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 102: $text = 'Processing'; break; #WebDAV
			case 103: $text = 'Early Hints'; break;

			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 207: $text = 'Multi-Status'; break; #WebDAV
			case 208: $text = 'Already Reported'; break; #WebDAV
			case 226: $text = 'IM Used'; break;

			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 306: $text = 'Unused'; break;
			case 307: $text = 'Temporary Redirect'; break;
			case 308: $text = 'Permanent Redirect'; break;

			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 416: $text = 'Range Not Satisfiable'; break;
			case 417: $text = 'Expectation Failed'; break;
			case 418: $text = 'I\'m a teapot'; break;
			case 421: $text = 'Misdirected Request'; break;
			case 422: $text = 'Unprocessable Entity'; break; #WebDAV
			case 423: $text = 'Locked'; break; #WebDAV
			case 424: $text = 'Failed Dependency'; break; #WebDAV
			case 425: $text = 'Too Early'; break;
			case 426: $text = 'Upgrade Required'; break;
			case 428: $text = 'Precondition Required'; break;
			case 429: $text = 'Too Many Requests'; break;
			case 431: $text = 'Request Header Fields Too Large'; break;
			case 451: $text = 'Unavailable For Legal Reasons'; break;

			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version Not Supported'; break;
			case 506: $text = 'Variant Also Negotiates'; break;
			case 507: $text = 'Insufficient Storage'; break; #WebDAV
			case 508: $text = 'Loop Detected'; break;
			case 510: $text = 'Not Extended'; break;
			case 511: $text = 'Network Authentication Required'; break;
			default:
				$text = 'Unknown error code: '.$code;
			break;
		}
		return $text;
	}
	function hasData() {
		return $this->dataLength() ? true : false;
	}
	function dataLength() {
		if (!empty($this->data)) return strlen($this->data);
		if (!empty($this->downloadAs)) return filesize($this->downloadAs);
	}
	function data():string {
		if ($this->downloadAs) {
			return file_get_contents($this->downloadAs);
		}
		return (string) $this->data;
	}
	function html():string {
		return $this->data();
	}
	function dom():object {
		switch($this->type()->contentType) {
			case 'text/html':
				$dom = new DOM();
				$dom->loadHTML($this->data());
				return $dom;
			break;
			case 'text/xml':
				$dom = new DOM();
				$dom->loadXML($this->data());
				return $dom;
			break;
		}
	}
	function json(bool $asArray = false, int $depth = 512) {
		return json_decode($this->data(), $asArray, $depth, JSON_THROW_ON_ERROR);
	}
	function info():object {
		return (object) $this->info;
	}
	function curlOptions() {
		$a = [
			CURLINFO_HEADER_OUT => 'Output Headers',
			CURLOPT_CAINFO => 'CA Information',
			CURLOPT_CERTINFO => 'Cert Information',
			CURLOPT_CONNECTTIMEOUT => 'Connection Timeout',
			CURLOPT_COOKIE => 'Cookie',
			CURLOPT_COOKIEFILE => 'Cookie File',
			CURLOPT_COOKIEJAR => 'Cookie Jar',
			CURLOPT_COOKIESESSION => 'Cookie Session',
			CURLOPT_CUSTOMREQUEST => 'Custom Request',
			CURLOPT_DNS_CACHE_TIMEOUT => 'DNS Cache Timeout',
			CURLOPT_ENCODING => 'Encoding',
			CURLOPT_FILE => 'File',
			CURLOPT_FOLLOWLOCATION => 'Follow Location',
			CURLOPT_HEADER => 'Send Headers',
			CURLOPT_HTTPAUTH => 'HTTP Auth',
			CURLOPT_HTTPHEADER => 'HTTP Headers',
			CURLOPT_INFILE => 'Input File',
			CURLOPT_INFILESIZE => 'Input Filesize',
			CURLOPT_NOBODY => 'No Body',
			CURLOPT_POST => 'Request as Post',
			CURLOPT_POSTFIELDS => 'Post Fields',
			CURLOPT_PROXY => 'Proxy',
			CURLOPT_PROXYAUTH => 'Proxy Auth',
			CURLOPT_PROXYTYPE => 'Proxy Type',
			CURLOPT_PUT => 'Request as Put',
			CURLOPT_REFERER => 'Referer URL',
			CURLOPT_RETURNTRANSFER => 'Return Transfer',
			CURLOPT_SSL_VERIFYHOST => 'SSL Verify Host',
			CURLOPT_SSL_VERIFYPEER => 'SSL Verify Peer',
			CURLOPT_SSL_VERIFYSTATUS => 'SSL Verify Status',
			CURLOPT_SSLVERSION => 'SSL Version',
			CURLOPT_SSL_CIPHER_LIST => 'SSL Cipher',
			CURLOPT_TLS13_CIPHERS => 'TLS 1.3 Cipher',
			CURLOPT_TIMEOUT => 'Timeout',
			CURLOPT_UPLOAD => 'Upload',
			CURLOPT_URL => 'Request URL',
			CURLOPT_USERAGENT => 'User-Agent',
			CURLOPT_USERPWD => 'User & Password',
			CURLOPT_VERBOSE => 'Verbose',
		];
		$r = [];
		if (is_array($this->options)) {
			foreach ($this->options as $k => $v) {
				$r[$a[$k] ?? $k] = $v;
			}
		}
		return $r;
	}
	function debug():object {
		print_r($this->__debugInfo());
		return $this;
	}
	function __debugInfo():array {
		return [
			'method' => $this->method,
			'auth' => $this->auth,
			'caFile' => $this->caFile,
			'cookieFile' => $this->cookieFile,
			'url' => $this->fullURL(),
			'uri' => $this->uri,
			'purl' => $this->purl,
			'downloadAs' => $this->downloadAs,
			'send' => $this->send,
			'referer' => $this->referer,
			'redirected' => $this->redirected,
			'request' => $this->request,
			'userAgent' => $this->userAgent,
			'contentType' => $this->contentType,
			'enCoding' => $this->enCoding,
			'info' => $this->info,
			'code' => $this->code(),
			'codeArea' => $this->codeArea(),
			'status' => $this->status(),
			#'options' => $this->options,
			'curlOptions' => $this->curlOptions(),
			'response' => $this->response,
			// 'data' => $this->data,
		];
	}
	function __toString():string {
		return json_encode($this->__debugInfo(), $this->pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : JSON_UNESCAPED_SLASHES);
	}
}
