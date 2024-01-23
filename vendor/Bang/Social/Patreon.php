<?php
namespace Bang\Social;
use Bang\Chain\HTTP;

class Patreon {
	const
		TOKEN_URL = 'https://api.patreon.com/oauth2/token',
		API_URL = 'https://api.patreon.com/oauth2/api/',
		REFRESH_STORAGE = 86400;
	static
		$id,
		$user_id,
		$http,
		$expires_in,
		$expires_in_time,
		$storage = '/tmp';
	protected static
		$client_id,
		$client_secret,
		$access_token,
		$refresh_token;

	function __construct() {
		self::$http = new HTTP();
		// parent::__construct();
	}

	function auth() {}
}
