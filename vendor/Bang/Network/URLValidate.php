<?php

namespace Bang\Network;

class URLValidate {
	private static
		$URLFlags;
	const
		WEB				= URLFlags::WEB,
		WWW				= URLFlags::WWW,
		ENCRYPTED		= URLFlags::ENCRYPTED,
		SECURE			= URLFlags::SECURE,
		HTTPS			= URLFlags::HTTPS,
		DOMAIN			= URLFlags::DOMAIN,
		TLD				= URLFlags::TLD,
		SUBDOMAIN		= URLFlags::SUBDOMAIN,
		SUB				= URLFlags::SUB,
		PATH			= URLFlags::PATH;

	private static function _init() {
		if (is_null(self::$URLFlags)) self::$URLFlags = new URLFlags();
	}
	static function validate($url) {
		self::_init();
		return self::$URLFlags->validate($url);
	}
	static function web(bool $b = true) {
		self::_init();
		self::$URLFlags->set(URLFlags::WEB, $b);
	}
	static function isWeb() {
		self::_init();
		return self::$URLFlags->has(URLFlags::WEB);
	}
	static function encrypted(bool $b = true) {
		self::_init();
		self::$URLFlags->set(URLFlags::ENCRYPTED, $b);
	}
	static function isEncrypted() {
		self::_init();
		return self::$URLFlags->has(URLFlags::ENCRYPTED);
	}
	static function domain(bool $b = true) {
		self::_init();
		self::$URLFlags->set(URLFlags::DOMAIN, $b);
	}
	static function isDomain() {
		self::_init();
		return self::$URLFlags->has(URLFlags::DOMAIN);
	}
	static function tld(bool $b = true) {
		self::_init();
		self::$URLFlags->set(URLFlags::TLD, $b);
	}
	static function isTLD() {
		self::_init();
		return self::$URLFlags->has(URLFlags::TLD);
	}
	static function path(bool $b = true) {
		self::_init();
		self::$URLFlags->set(URLFlags::PATH, $b);
	}
	static function isPath() {
		self::_init();
		return self::$URLFlags->has(URLFlags::PATH);
	}
	static function isWebEncrypted() {
		self::_init();
		return self::$URLFlags->has(URLFlags::WEB)
		&& self::$URLFlags->has(URLFlags::ENCRYPTED);
	}
	static function isDomainTLD() {
		self::_init();
		return self::$URLFlags->has(URLFlags::DOMAIN)
		&& self::$URLFlags->has(URLFlags::TLD);
	}
	static function isWebEncryptedDomainTLD() {
		self::_init();
		return self::$URLFlags->isWebEncrypted()
		&& self::$URLFlags->isDomainTLD();
	}
}
