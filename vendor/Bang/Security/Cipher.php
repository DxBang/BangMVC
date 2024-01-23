<?php
namespace Bang\Security;

class Cipher {

	# GvZC3lmXHY5k0YhO+vS/DWdf54i0o7wzIwrh5sMvI1nkdIhb6GCIlM7e0vd6VBk6jdjREFqCt/H6le07PppKcwEeo0aER4aeNqKPVlaIRVk=
	# secret
	# this is text
	# gxEAq0Zk0xnZ0gcEPmskpg==
	# aes-256-cbc-hmac-sha1

	# gxEAq0Zk0xnZ0gcEPmskpg==
	public static
		$feedback,
		$blockchain;

	public function __construct(object $init = null) {
		if (is_null($init)) return;
		foreach ($init as $k => $v) {
			switch ($k) {
				case 'blockchain':
					self::$blockchain = new Cipher\Blockchain($v);
				break;
				case 'feedback':
					self::$feedback = new Cipher\Feedback($v);
				break;
			}
		}
	}
	public function __debugInfo() {
		return [
			'blockchain' => self::$blockchain,
			'feedback' => self::$feedback,
		];
	}

	public static function demo() {
		$plaintext = 'this is text/plain';
		$feedbackKey = 'secretkey';
		$feedbackIV = 'iv';
		$feedbackEncrypted = Cipher\Feedback::encrypt($plaintext, $feedbackKey, $feedbackIV);
		$feedbackDecrypted = Cipher\Feedback::decrypt($feedbackEncrypted, $feedbackKey, $feedbackIV);
		$blockchainKey = $feedbackKey;
		$blockchainEncrypted = Cipher\Blockchain::encrypt($plaintext, $blockchainKey);
		$blockchainDecrypted = Cipher\Blockchain::decrypt($blockchainEncrypted, $blockchainKey);

		return
			[
				'Input' => $plaintext,
				'Feedback' => (object) [
					'::$key' => Cipher\Feedback::key(),
					'::$iv' => Cipher\Feedback::iv(),
					'::encrypt()' => $feedbackEncrypted,
					'::decrypt()' => $feedbackDecrypted,
				],
				'Blockchain' => (object) [
					'::$key' => Cipher\Blockchain::key(),
					'::encrypt()' => $blockchainEncrypted,
					'::decrypt()' => $blockchainDecrypted,
				],
			];
	}
}
