<?php
namespace Bang;

class Hash extends Bitwise {
	protected
		$flags = 0,
		$store;
	const
		BANG		= 1,
		CRC32		= 2,
		MD2			= 4,
		MD4			= 8,
		MD5			= 16,
		SHA1		= 32,
		SHA224		= 64,
		SHA256		= 128,
		SHA384		= 256,
		SHA512		= 512;

	function BANG(string $data = null) {
		if (is_null($data)) return;
		return hash(
			'crc32b',
			$data
		)
		.':'
		.hash(
			'md5',
			$data
		);
	}
	function setBANG(bool $b = true) {
		$this->set(self::BANG, $b);
	}
	function hasBANG() {
		return $this->has(self::BANG);
	}
	function CRC32(string $data = null) {
		if (is_null($data)) return;
		return hash('crc32b', $data);
	}
	function setCRC32(bool $b = true) {
		$this->set(self::CRC32, $b);
	}
	function hasCRC32() {
		return $this->has(self::CRC32);
	}
	function MD2(string $data = null) {
		if (is_null($data)) return;
		return hash('md2', $data);
	}
	function setMD2(bool $b = true) {
		$this->set(self::MD2, $b);
	}
	function hasMD2() {
		return $this->has(self::MD2);
	}
	function MD4(string $data = null) {
		if (is_null($data)) return;
		return hash('md4', $data);
	}
	function setMD4(bool $b = true) {
		$this->set(self::MD4, $b);
	}
	function hasMD4() {
		return $this->has(self::MD4);
	}
	function MD5(string $data = null) {
		if (is_null($data)) return;
		return md5($data);
	}
	function setMD5(bool $b = true) {
		$this->set(self::MD5, $b);
	}
	function hasMD5() {
		return $this->has(self::MD5);
	}
	function SHA1(string $data = null) {
		if (is_null($data)) return;
		return SHA1($data);
	}
	function setSHA1(bool $b = true) {
		$this->set(self::SHA1, $b);
	}
	function hasSHA1() {
		return $this->has(self::SHA1);
	}
	function SHA224(string $data = null) {
		if (is_null($data)) return;
		return hash('sha224', $data);
	}
	function setSHA224(bool $b = true) {
		$this->set(self::SHA224, $b);
	}
	function hasSHA224() {
		return $this->has(self::SHA224);
	}
	function SHA256(string $data = null) {
		if (is_null($data)) return;
		return hash('sha256', $data);
	}
	function setSHA256(bool $b = true) {
		$this->set(self::SHA256, $b);
	}
	function hasSHA256() {
		return $this->has(self::SHA256);
	}
	function SHA384(string $data = null) {
		if (is_null($data)) return;
		return hash('sha384', $data);
	}
	function setSHA384(bool $b = true) {
		$this->set(self::SHA384, $b);
	}
	function hasSHA384() {
		return $this->has(self::SHA384);
	}
	function SHA512(string $data = null) {
		if (is_null($data)) return;
		return hash('sha512', $data);
	}
	function setSHA512(bool $b = true) {
		$this->set(self::SHA512, $b);
	}
	function hasSHA512() {
		return $this->has(self::SHA512);
	}
	function hash(string $data, int $flags = null) {
		$r = [];
		if (!is_null($flags)) {
			$this->flags = $flags;
		}
		if ($this->flags <= 0) return (object) $r;
		if ($this->hasBANG()) {
			$r['bang'] = $this->BANG($data);
		}
		if ($this->hasCRC32()) {
			$r['crc32'] = $this->CRC32($data);
		}
		if ($this->hasMD2()) {
			$r['md2'] = $this->MD2($data);
		}
		if ($this->hasMD4()) {
			$r['md4'] = $this->MD4($data);
		}
		if ($this->hasMD5()) {
			$r['md5'] = $this->MD5($data);
		}
		if ($this->hasSHA1()) {
			$r['sha1'] = $this->SHA1($data);
		}
		if ($this->hasSHA224()) {
			$r['sha224'] = $this->SHA224($data);
		}
		if ($this->hasSHA256()) {
			$r['sha256'] = $this->SHA256($data);
		}
		if ($this->hasSHA384()) {
			$r['sha384'] = $this->SHA384($data);
		}
		if ($this->hasSHA512()) {
			$r['sha512'] = $this->SHA512($data);
		}
		return (object) $r;
	}

	function data():object {
		return (object) [
			'flags' => $this->flags,
			'hasBANG' => $this->hasBANG(),
			'hasCRC32' => $this->hasCRC32(),
			'hasMD2' => $this->hasMD2(),
			'hasMD4' => $this->hasMD4(),
			'hasMD5' => $this->hasMD5(),
			'hasSHA1' => $this->hasSHA1(),
			'hasSHA224' => $this->hasSHA224(),
			'hasSHA256' => $this->hasSHA256(),
			'hasSHA384' => $this->hasSHA384(),
			'hasSHA512' => $this->hasSHA512(),
		];
	}
	function __debugInfo():array {
		return (array) $this->data();
	}
}

