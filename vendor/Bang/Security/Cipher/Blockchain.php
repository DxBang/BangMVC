<?php
namespace Bang\Security\Cipher;

class Blockchain extends \Bang\Security\Cipher {
	private static
		$cipher = 'aes-256-cbc',
		$key;

	public function __construct(object $init = null) {
		if (is_null($init)) return;
		foreach ($init as $k => $v) {
			switch ($k) {
				case 'key':
					self::key($v);
				break;
			}
		}
	}
	public function __debugInfo() {
		return [
			'cipher' => self::$cipher,
			'key length' => strlen(self::$key),
		];
	}

	public static function key(string $key = null) {
		if (!empty($key)) {
			self::$key = $key;
		}
		return self::$key;
	}
	public static function encrypt(string $input, string $key = null) {
		if (empty($input)) return '';
		try {
			if (!empty($key)) {
				self::key($key);
			}
			if (empty(self::$key)) throw new \Exception('missing cipher key', 1);
			$key = self::key();
			$ivlen = openssl_cipher_iv_length($cipher=self::$cipher);
			$iv = openssl_random_pseudo_bytes($ivlen);
			$ciphered = openssl_encrypt($input, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$hmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			return base64_encode($iv.$hmac.$ciphered);
		} catch (\Exception $e) {
			die('encrypt error : '.$e->getMessage());
		}
	}
	public static function decrypt(string $input, string $key = null) {
		if (empty($input)) return '';
		try {
			if (!empty($key)) {
				self::key($key);
			}
			if (empty(self::$key)) throw new \Exception('missing cipher key', 1);
			$c = base64_decode($input);
			$key = self::key();
			$ivlen = openssl_cipher_iv_length($cipher=self::$cipher);
			$iv = substr($c, 0, $ivlen);
			$hmac = substr($c, $ivlen, $sha2len=32);
			$ciphered = substr($c, $ivlen+$sha2len);
			$decrypted = openssl_decrypt($ciphered, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$calcmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			if (!hash_equals($hmac, $calcmac)) {
				throw new \Exception('incorrect hash check', 2);
			}
			return $decrypted;
		} catch (\Exception $e) {
			die('decrypt error : '.$e->getMessage());
		}
	}
}
