<?php
namespace Bang\Security;

use Bang\Format;
use Bang\Network\URL;
use Bang\Config;

/*
date_c
	expr: current_timestamp()
date_m
	onupdate: current_timestamp()
*/

class CipherOld {
	public static
		$cryptFeedbackIV = 'Whatever Plaintext or '.(0x00).' even binary',
		$cryptFeedbackKey = 'Whatever Plaintext or '.(0x00).' even binary',
		$cryptFeedbackCipher = 'aes-256-cfb',
		$cryptFeedbackBlock = 32,
		$cryptFeedbackPad = '`',
		$cryptChainCipher = 'aes-256-cbc',
		$cryptChainKey;
	public static function slug(string $string):string {
		return Format::slug(
			$string, true
		);
	}
	public static function linkable(string $url) {
		$url = trim($url);
		if (empty($url)) return '';
		try {
			if (preg_match('/^(http|ftp)s?:\/\//', $url)) {
				return URL::parse($url)->full;
			}
			return URL::parse('http://'.$url)->full;
		} catch (\Exception $e) {
			return false;
		}
	}
	public static function cryptKey(string $key) {
		if (strlen($key) < 8) throw new \Exception('key is too short', 1);
		self::$cryptChainKey = $key;
	}
	private static function _cryption() {
		if (!empty(self::$cryptChainKey)) {
			return self::$cryptChainKey;
		}
		if (!empty(constant('ENCRYPTION_KEY'))) {
			self::cryptKey(constant('ENCRYPTION_KEY'));
		}
		if (Config::has('encryption_key')) {
			self::cryptKey((string) Config::get('encryption_key'));
		}
		if (!function_exists('openssl_encrypt')) {
			throw new \Exception('missing openssl_encrypt feature', 1);
		}
		if (!function_exists('openssl_decrypt')) {
			throw new \Exception('missing openssl_decrypt feature', 2);
		}
		if (!in_array(self::$cryptChainCipher, openssl_get_cipher_methods())) {
			throw new \Exception('cannot find cipher for encryption/decryption', 3);
		}
		if (empty(self::$cryptChainKey)) {
			throw new \Exception('missing encryption key', 4);
		}
		return self::$cryptChainKey;
	}

	public static function encrypt(string $plaintext) {
		try {
			$key = self::_cryption();
			$ivlen = openssl_cipher_iv_length($cipher=self::$cryptChainCipher);
			$iv = openssl_random_pseudo_bytes($ivlen);
			$ciphered = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$hmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			return base64_encode($iv.$hmac.$ciphered);
		} catch (\Exception $e) {
			die('encrypt error : '.$e->getMessage());
		}
	}
	public static function decrypt(string $encrypted) {
		try {
			$key = self::_cryption();
			$c = base64_decode($encrypted);
			$ivlen = openssl_cipher_iv_length($cipher=self::$cryptChainCipher);
			$iv = substr($c, 0, $ivlen);
			$hmac = substr($c, $ivlen, $sha2len=32);
			$ciphered = substr($c, $ivlen+$sha2len);
			$decrypted = openssl_decrypt($ciphered, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
			$calcmac = hash_hmac('sha256', $ciphered, $key, $as_binary=true);
			if (hash_equals($hmac, $calcmac)) {
				return $decrypted;
			}
		} catch (\Exception $e) {
			die('decrypt error : '.$e->getMessage());
		}
	}
	public static function feedbackKey(string $iv = null) {
		if (is_string($iv)) {
			self::$cryptFeedbackKey = $iv;
		}
		if (!empty(self::$cryptFeedbackKey)) {
			return substr(hash('sha256', self::$cryptFeedbackKey), 0, 32);
		}
		if (!empty(self::$cryptChainKey)) {
			return substr(hash('sha256', self::$cryptChainKey), 0, 32);
		}
		throw new \Exception('cryptFeedbackKey/cryptChainKey is not set', 1);
	}
	public static function feedbackIV(string $iv = null) {
		if (is_string($iv)) {
			self::$cryptFeedbackIV = $iv;
		}
		if (empty(self::$cryptFeedbackIV)) {
			throw new \Exception('cryptFeedbackIV is not set', 1);
		}
		return substr(hash('sha256', self::$cryptFeedbackIV), 0, 16);
	}
	public static function feedbackEncrypt(string $plaintext = null) {
		if (empty($plaintext)) return '';
		try {
			$padded = $plaintext.str_repeat(self::$cryptFeedbackPad, (self::$cryptFeedbackBlock - strlen($plaintext) % self::$cryptFeedbackBlock));
			$encrypted = openssl_encrypt($padded, self::$cryptFeedbackCipher, self::feedbackKey(), OPENSSL_RAW_DATA, self::feedbackIV());
			if (empty($encrypted)) throw new \Exception('failed to encrypt', 1);
			return base64_encode($encrypted);
		} catch (\Exception $e) {
			die('Feedbackencrypt error : '.$e->getMessage());
		}
	}
	public static function feedbackDecrypt(string $encrypted = null) {
		if (empty($encrypted)) return '';
		try {
			$decrypted = base64_decode($encrypted);
			$decrypted_secret = openssl_decrypt($decrypted, self::$cryptFeedbackCipher, self::feedbackKey(), OPENSSL_RAW_DATA, self::feedbackIV());
			if (empty($decrypted_secret)) throw new \Exception('failed to decrypt', 1);
			return rtrim($decrypted_secret, self::$cryptFeedbackPad);
		} catch (\Exception $e) {
			die('Feedbackdecrypt error : '.$e->getMessage());
		}
	}
	public static function encryptDowngrade($encrypted) {
		try {
			return self::feedbackEncrypt(
				self::decrypt($encrypted)
			);
		} catch (\Exception $e) {
			die('encryptdowngrade error : '.$e->getMessage());
		}
	}

	public static function parseGroupConcat(object $object, string $prefix_ids, string $prefix_slugs, string $prefix_names, string $prefix_actives = null, string $prefix_comments = null, string $prefix_versions = null) {
		$ids = explode(',', $object->$prefix_ids);
		$slugs = explode(',', $object->$prefix_slugs);
		$names = explode(',', $object->$prefix_names);
		$actives = (!empty($prefix_actives) && isset($object->$prefix_actives))
			? explode(',', $object->$prefix_actives) : null;
		$comments = (!empty($prefix_comments) && isset($object->$prefix_comments))
			? explode(',', $object->$prefix_comments) : null;
		$versions = (!empty($prefix_versions) && isset($object->$prefix_versions))
			? explode(',', $object->$prefix_versions) : null;
		$r = [];
		$i = 0;
		$known = [];
		foreach ($ids as $k => $v) {
			if (isset($known[$v])) continue;
			$r[$i] = (object) [
				'id' => (int) $ids[$k],
				'slug' => (string) $slugs[$k],
				'name' => (string) $names[$k],
			];
			if (!empty($actives)) {
				$r[$i]->active = (int) $actives[$k];
			}
			if (!empty($comments)) {
				$r[$i]->comment = (string) $comments[$k];
			}
			if (!empty($versions)) {
				$r[$i]->version = (int) $versions[$k];
			}
			$known[$v] = true;
			$i++;
		}
		return $r;
	}
	public static function parseGroup(object $object, string $prefix_id, string $prefix_slug, string $prefix_name, string $prefix_active = null, string $prefix_comment = null, string $prefix_version = null) {
		$r = (object) [
			'id' => (int) $object->$prefix_id,
			'slug' => $object->$prefix_slug,
			'name' => $object->$prefix_name,
		];
		if ($prefix_active && isset($object->$prefix_active))
			$r->active = (int) $object->$prefix_active;
		if ($prefix_comment && isset($object->$prefix_comment))
			$r->comment = $object->$prefix_comment;
		if ($prefix_version && isset($object->$prefix_version))
			$r->version = (int) $object->$prefix_version;
		return $r;
	}
}
