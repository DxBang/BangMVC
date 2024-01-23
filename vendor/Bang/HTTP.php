<?php
namespace Bang;

class HTTP {
	protected
		$stats = [],
		$headers = [],
		$url,
		$curl,
		$data,
		$info = [],
		$file;

	function __construct() {
		$this->init();
	}
	function __destruct() {
		$this->close();
	}
	function headers($headers = null) {
		if (is_array($headers)) {
			$this->headers = $headers;
		}
		elseif (is_string($headers)) {
			$this->headers = explode("\n", str_replace("\r", "", trim($headers)));
		}
		elseif (is_null($headers)) {
			$this->headers = [];
		}
	}
	function init() {
		if (is_null($this->curl)) {
			$this->curl = curl_init();
		}
	}
	function close() {
		if (is_object($this->file)) {
			fclose($this->file);
			$this->file = null;
		}
		if (!is_null($this->curl)) {
			curl_close($this->curl);
			$this->curl = null;
		}
	}
	function reset() {
		$this->close();
		$this->init();
	}
	private function _execute(string $method, string $url, string $refer = null, array $post = null, string $downloadAs = null, $redirected = 0) {
		if (parse_url($url, PHP_URL_SCHEME)) {
			$url = explode('#', $url, 2);
			$this->url = $url[0];
			$this->init();
			curl_setopt($this->curl, CURLOPT_URL, $this->url);
			curl_setopt($this->curl, CURLOPT_REFERER, $refer ?? false);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($this->curl, CURLOPT_DNS_CACHE_TIMEOUT, 60);
			curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 600);
			curl_setopt($this->curl, CURLOPT_TIMEOUT, 6000);
			curl_setopt($this->curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($this->curl, CURLOPT_ENCODING, $_SERVER['HTTP_ENCODING'] ?? 'deflate, gzip');
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
			curl_setopt($this->curl, CURLOPT_CAINFO, BANG_DATA.'/cacert.pem');
			if ($method == 'POST' && $post) {
				curl_setopt($this->curl, CURLOPT_POST, 1);
				curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($post));
			}
			if ($downloadAs) {
				$this->file = fopen($downloadAs, 'w'); 
				curl_setopt($this->curl, CURLOPT_FILE, $this->file);
			}
			else {
				curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
			}
			$this->data = curl_exec($this->curl);
			$this->info = curl_getinfo($this->curl);
			if ($downloadAs) {
				fclose($this->file);
			}
			$this->close();
			
			if (($this->info['http_code'] == 301) || ($this->info['http_code'] == 302)) {
				return $this->get($this->info['redirect_url'], $this->url, $downloadAs, $redirected+1);
			}
			else {
				$this->stats['httpCode'] = $this->info['http_code'];
				if (is_string($downloadAs)) {
					return ($this->info['http_code']>=200 && $this->info['http_code']<300) ? $this->info['download_content_length'] : false;	
				}
				return ($this->info['http_code']>=200 && $this->info['http_code']<300) ? $this->data : false;
			}
		}
		else {
			die("BAD URL: $url\nRefer: $refer\nDownloadAs: $downloadAs\nRedirected: $redirected\n");
		}
	}

	function get(string $url, string $refer = null, string $downloadAs = null, $redirected = 0) {
		return $this->_execute('GET', $url, $refer, null, $downloadAs, $redirected);
	}
	function post(string $url, string $refer = null, array $post = null, string $downloadAs = null, $redirected = 0) {
		return $this->_execute('POST', $url, $refer, $post, $downloadAs, $redirected);	
	}
	function data() {
		return $this->data;
	}
}