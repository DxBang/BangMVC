<?php
namespace Bang\Security\Cipher;

class Feedback extends \Bang\Security\Cipher {
	private static
		$cipher = 'aes-256-cfb',
		$iv,
		$key,
		$block = 32,
		$pad = '`';

	public function __construct(object $init = null) {
		if (is_null($init)) return;
		foreach ($init as $k => $v) {
			switch ($k) {
				case 'key':
					self::key($v);
				break;
				case 'iv':
					self::iv($v);
				break;
			}
		}
	}
	public function __debugInfo() {
		return [
			'cipher' => self::$cipher,
			'key length' => strlen(self::$key),
			'iv length' => strlen(self::$iv),
		];
	}

	public static function key(string $key = null) {
		if (!empty($key)) {
			self::$key = $key;
		}
		if (empty(self::$key)) {
			throw new \Exception('key is not set', 1);
		}
		return substr(hash('sha256', self::$key), 0, 32);
	}
	public static function iv(string $iv = null) {
		if (!empty($iv)) {
			self::$iv = $iv;
		}
		if (empty(self::$iv)) {
			throw new \Exception('iv is not set', 1);
		}
		return substr(hash('sha256', self::$iv), 0, 16);
	}
	public static function encrypt(string $input = null, string $key = null, string $iv = null) {
		if (empty($input)) return '';
		try {
			if (!empty($key)) {
				self::key($key);
			}
			if (!empty($iv)) {
				self::iv($iv);
			}
			$padded = $input.str_repeat(self::$pad, (self::$block - strlen($input) % self::$block));
			$encrypted = openssl_encrypt($padded, self::$cipher, self::key(), OPENSSL_RAW_DATA, self::iv());
			if (empty($encrypted)) throw new \Exception('failed to encrypt', 1);
			return base64_encode($encrypted);
		} catch (\Exception $e) {
			die('Feedback::encrypt error : '.$e->getMessage());
		}
	}
	public static function decrypt(string $input = null, string $key = null, string $iv = null) {
		if (empty($input)) return '';
		try {
			if (!empty($key)) {
				self::key($key);
			}
			if (!empty($iv)) {
				self::iv($iv);
			}
			$decrypted = base64_decode($input);
			$decrypted_secret = openssl_decrypt($decrypted, self::$cipher, self::key(), OPENSSL_RAW_DATA, self::iv());
			if (empty($decrypted_secret)) throw new \Exception('failed to decrypt', 1);
			return rtrim($decrypted_secret, self::$pad);
		} catch (\Exception $e) {
			die('Feedback::decrypt error : '.$e->getMessage());
		}
	}
}
