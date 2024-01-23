<?php
namespace Bang;

class Cache {
	public
		$dir,
		$ext,
		$refresh = 1800,
		$cached = [];

	function __construct(string $dir = null, int $refresh = null, string $ext = '.cache') {
		if (is_null($dir)) {
			$dir = sys_get_temp_dir();
		}
		if (!is_null($refresh)) {
			$this->refresh($refresh);
		}
		$this->dir($dir);
		$this->ext = $ext;
	}
	function refresh(int $refresh) {
		$this->refresh = $refresh;
	}
	function dir(string $dir) {
		$this->dir = realpath($dir);
	}

	private function loadInfo(string $id) {
		$fileInfo = $this->fileInfo($id);
		if (file_exists($fileInfo)) {
			return json_decode(
				file_get_contents($fileInfo)
			);
		}
	}

	private function saveInfo(string $id, $extension) {
		return file_put_contents(
			$this->fileInfo($id),
			json_encode(
				(object) [
					'id' => $id,
					'time' => time(),
					'ext' => $extension,
				],
				JSON_UNESCAPED_SLASHES)
			);
	}

	private function fileInfo(string $id) {
		return $this->dir.'/'.$id.'.cache.json';
	}

	private function file(string $id) {
		return $this->dir.'/'.$id.$this->ext;
	}

	function id():string {
		return sha1(serialize(func_get_args()));
	}

	function check(string $id) {
		#$id = $this->id($id);
		$this->cached[$id] = 'check';
		return $this->isFresh(
			$this->file($id)
		);
	}

	private function time(string $file) {
		if (!file_exists($file)) return 0;
		return filemtime($file);
	}

	private function isFresh(string $file):bool {
		return (
			($this->time($file) + $this->refresh) > time()
		);
	}

	function load(string $id) {
		#$id = $this->id($id);
		if ($this->check($id)) {
			$this->cached[$id] = 'read';
			return file_get_contents(
				$this->file($id)
			);
		}
	}

	function save(string $id, string $data) {
		#$id = $this->id($id);
		$this->cached[$id] = 'write';
		return file_put_contents(
			$this->file($id),
			$data
		);
		
	}

	function __debugInfo() {
		return [
			'cached' => $this->cached
		];
	}

	function __toString() {
		return json_encode($this->__debugInfo(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
}
