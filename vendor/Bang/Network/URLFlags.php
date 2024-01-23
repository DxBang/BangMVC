<?php

namespace Bang\Network;

class URLFlags extends \Bang\Bitwise {
	protected
		$flags = 0,
		$store;
	const
		WEB				= 1,
		WWW				= 1,
		ENCRYPTED		= 2,
		SECURE			= 2,
		HTTPS			= 2,
		DOMAIN			= 8,
		TLD				= 16,
		SUBDOMAIN		= 32,
		SUB				= 32,
		PATH			= 64;

	function validate(object $url) {
		if (!empty($url->web)) {
			$this->web(true);
		}
		if (!empty($url->encrypted)) $this->encrypted(true);
		if (!empty($url->domain)) $this->domain(true);
		if (!empty($url->tld)) $this->tld(true);
		if (!empty($url->path)) $this->path(true);
		return $this;
	}
	function web(bool $b = true) {
		$this->set(self::WEB, $b);
	}
	function isWeb() {
		return $this->has(self::WEB);
	}
	function encrypted(bool $b = true) {
		$this->set(self::ENCRYPTED, $b);
	}
	function isEncrypted() {
		return $this->has(self::ENCRYPTED);
	}
	function domain(bool $b = true) {
		$this->set(self::DOMAIN, $b);
	}
	function isDomain() {
		return $this->has(self::DOMAIN);
	}
	function tld(bool $b = true) {
		$this->set(self::TLD, $b);
	}
	function isTLD() {
		return $this->has(self::TLD);
	}
	function path(bool $b = true) {
		$this->set(self::PATH, $b);
	}
	function isPath() {
		return $this->has(self::PATH);
	}
	function isWebEncrypted() {
		return $this->has(self::WEB)
		&& $this->has(self::ENCRYPTED);
	}
	function isDomainTLD() {
		return $this->has(self::DOMAIN)
		&& $this->has(self::TLD);
	}
	function isWebEncryptedDomainTLD() {
		return $this->isWebEncrypted()
		&& $this->isDomainTLD();
	}
}
