<?php
namespace Bang;
/*
https://www.php.net/manual/en/function.openssl-encrypt.php
*/

class Crypto {
	protected
		$iv,
		$key,
		$cipher;
	static
		$ciphers = [
			'auto' => [],
			'aes-128-cbc' => ['128-cbc'],
			'aes-256-cbc' => ['chain', 'cbc', '256-cbc'],
			'aes-128-cfb' => ['128-cfb'],
			'aes-256-cfb' => ['feedback', 'cfb', '256-cfb'],
		];
	function __construct(string $key, string $iv = null, string $cipher = null) {
		$this->key($key);
	}
	function key(string $key = null) {
		if (!empty($key))
			$this->key = $key;
		return $this->key;
	}
	function iv(string $iv = null) {
		if (!empty($iv))
			$this->iv = $iv;
		return $this->iv;
	}
	function cipher(string $cipher = null) {
		if (!empty($cipher))
			foreach ($this->ciphers as $k => $v) {
				if (($cipher == $k) || (in_array($cipher, $v))) {
					$this->cipher = $cipher;
					return $this->cipher;
				}
			}
		return $this->cipher;
	}


}


class CryptoFeedback {

}

class Crypto2 {
	protected
		$iv,
		$key,
		$cipher;
	static
		$ciphers = [
			'aes-128-cbc',
			'aes-256-cbc'
		];

	public function __construct(string $key, string $iv = null, string $cipher = null) {
		if ($key) $this->key($key);
		if ($iv) $this->iv($iv);
		if ($cipher) return $this->cipher($cipher);
		return $this->cipher('aes-256-cbc');
	}
	public function reset() {
		$this->iv
			= $this->key
			= null;
		return $this->cipher('aes-256-cbc');
	}
	public function cipher(string $cipher = null) {
		if (!empty($cipher)) {
			$this->cipher = $cipher;
		}
		return $this->cipher;
	}
	public function key(string $key = null) {
		if (!empty($key)) {
			$this->key = $key;
		}
		return $this->key;
	}
	public function iv(string $iv = null) {
		if (!empty($iv)) {
			$this->iv = $iv;
		}
		return $this->iv;
	}
	public function cipherLength(string $cipher = null) {
		$cipher = $cipher ?? $this->cipher();
		return openssl_cipher_iv_length($cipher);
	}
	public function randomBytes(int $length = 16) {
		return openssl_random_pseudo_bytes($length);
	}
	public function cipherRandomBytes(string $cipher = null) {
		$cipher = $cipher ?? $this->cipher();
		#if (!$cipher) $cipher = $this->cipher;
		return $this->randomBytes($this->cipherLength($cipher));
	}
	public function encrypt(string $plaintext):string {
		try {
			$key = $this->key();
			$cipher = $this->cipher();
			$iv = $this->cipherRandomBytes($cipher);
			$ciphered = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$hmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			return base64_encode($iv.$hmac.$ciphered);
		} catch (Exception $e) {
			exit('encrypt error: '.$e->getCode().': '.$e->getMessage());
		}
	}
	public function decrypt(string $encrypted):string {
		try {
			$key = $this->key();
			$cipher = $this->cipher();
			$c = base64_decode($encrypted);
			$ivlen = openssl_cipher_iv_length($cipher);
			$iv = substr($c, 0, $ivlen);
			$hmac = substr($c, $ivlen, $sha2len=32);
			$ciphered = substr($c, $ivlen+$sha2len);
			$calcmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			if (hash_equals($hmac, $calcmac)) {
				return openssl_decrypt($ciphered, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			}
			throw new Exception('failed to decrypt', 1);
		} catch (Exception $e) {
			exit('decrypt error: '.$e->getCode().': '.$e->getMessage());
		}
	}
}