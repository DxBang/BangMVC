<?php
namespace Bang\Chain;

use Bang\Format;
use Bang\Network\URL;

class Crawler {
	protected
		$url,
		$http,
		$file,
		$temp,
		$dom;
	function __construct() {
		$this->http = new HTTP;
		$this->http
			->caFile(BANG_DATA.'/cacert.pem')
			->cookieFile(SITE_DATA.'/crawler-cookies.txt');
		$this->dom = new DOM;
		$this->temp = SITE_PRIVATE.'/data/tmp/crawler';
		if (!is_dir($this->temp)) mkdir($this->temp, 0777);
	}

	function userAgent(string $userAgent) {
		$this->http->userAgent($userAgent);
		return $this;
	}

	function crawl(string $url, string $referer = null) {
		$this->url = URL::parse($url);
		$this->file = $this->temp.'/'.Format::slug($url);
		$this->dom->load('<?xml>');
		$this->http->url($url)
			->referer($referer)
			->follow(false)
			->downloadAs($this->file)
		#	->headers(['Host: www.casinohygge.com'])
			->get();
		return $this;
	}
	function httpInfo() {
		return $this->http->info();
	}
	function httpResponse() {
		return $this->http->response(true);
	}
	function file() {
		return file_get_contents($this->file);
	}
	function read() {
		$this->dom->loadFile($this->file);
		return $this;
	}
	function absoluteURLs(array $a) {
		foreach ($a as $k => &$v) {
			if ($v->value) {
				$v->value = URL::travel($v->value, $this->url->url);
			}
		}
		return $a;
	}
	function title() {
		return $this->dom->title();
	}
	function textOnly() {
		return $this->dom->textOnly();
	}
	function meta() {
		return $this->absoluteURLs($this->dom->meta());
	}
	function links() {
		return $this->absoluteURLs($this->dom->links());
	}
	function scripts() {
		return $this->absoluteURLs($this->dom->scripts());
	}
	function anchors() {
		return $this->absoluteURLs($this->dom->anchors());
	}
	function images() {
		return $this->absoluteURLs($this->dom->images());
	}
	function styles() {
		return $this->absoluteURLs($this->dom->styles());
	}
	function parse() {
		return (object) [
			'title' => $this->title(),
			'meta' => $this->links(),
			'links' => $this->links(),
			'scripts' => $this->scripts(),
			'styles' => $this->styles(),
			'anchors' => $this->anchors(),
			'images' => $this->images(),
			'text' => $this->textOnly(),
		];
	}
}