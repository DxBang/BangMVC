<?php
namespace Bang\Social;
use Bang\Chain\HTTP;

class Twitter extends HTTP {
	private
		$_access_token,
		$_access_token_secret,
		$_consumer_key,
		$_consumer_secret;
	function __construct($access_token, $access_token_secret) {
		$this->_access_token = $access_token;
		$this->_access_token_secret = $access_token_secret;
	}
	function consumer($key, $secret) {

	}

	
}