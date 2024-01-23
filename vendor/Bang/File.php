<?php
namespace Bang;

class File {
	private
		$type,
		$fileinfo,
		$handle;
	function safeFilename(string $filename) {
		if (preg_match('/^([a-zA-Z]\:)$/', $filename)) {
			return $filename;
		}
		$filename = preg_replace('/[^\d\w\.\-\_\s\~\[\]\(\)]/', '', $filename);
		$filename = preg_replace('/[\s]+/', '-', $filename);
		$filename = preg_replace('/[\-\-]+/', '-', $filename);
		return $filename;
	}

	function safePath(string $path) {
		$path = preg_replace('/([\\\\])+/', '/', $path);
		$path = explode('/', $path);
		foreach ($path as &$v) {
			$this->safeFilename($v);
		}
		$path = implode('/', $path);
		return $path;
	}

	function makeDir(string $dir, $mode = 0777) {
		return mkdir($dir, $mode, true);
	}

	function info(string $path) {
		$p = pathinfo($this->safePath($path));
		$this->fileinfo->dir = $p['dirname'];
		$this->fileinfo->file = $p['basename'];
		$this->fileinfo->filename = $p['filename'];
		$this->fileinfo->extension = strtolower($p['extension']);
		$this->fileinfo->path = $this->fileinfo->dir.'/'.$this->fileinfo->file;
		$this->fileinfo->mime = MIME::extension($this->fileinfo->extension);
		$this->fileinfo->exists = file_exists($this->fileinfo->path);
		if ($this->fileinfo->exists) {
			$this->fileinfo->type = 'file';
			$this->fileinfo->read = is_readable($this->fileinfo->path);
			$this->fileinfo->write = is_writable($this->fileinfo->path);
			$this->fileinfo->modified = filemtime($this->fileinfo->path);
		}
		else {
			$this->fileinfo->type = 'dir';
			$this->fileinfo->read = is_readable($this->fileinfo->dir);
			$this->fileinfo->write = is_writable($this->fileinfo->dir);
			$this->fileinfo->modified = false;
		}
		return $this->fileinfo;
	}

	function save(string $data, string $path = null) {
		if (is_null($path) && !$this->fileinfo->path) {
			return false;
		}
		if (is_string($path)) {
			$this->info($path);
		}
		if ($this->fileinfo->write) {
			return file_put_contents($this->fileinfo->path, $data);
		}
	}

	function load(string $path = null) {
		if (is_null($path) && !$this->fileinfo->path && $this->fileinfo->type != 'file') {
			return false;
		}
		if (is_string($path)) {
			$this->info($path);
		}
		if ($this->fileinfo->read && $this->fileinfo->type == 'file') {
			return file_get_contents($this->fileinfo->path);
		}
	}

	function mime(string $extension) {
		$this->fileinfo->mime = MIME::extension($extension);
		return $this->fileinfo->mime;
	}

	function open(string $file) {
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if ($ext == 'gz') {
			$this->type = 'gz';
			$this->handle = @gzopen($file, "r");
			return $this;
		}
		$this->type = 'txt';
		$this->handle = @fopen($file, "r");
		return $this;
	}
	function read(int $buffer = 4096) {
		if ($this->type == 'gz')
			return gzgets($this->handle, $buffer);
		return fgets($this->handle, $buffer);
	}
	function write(string $string) {
		if ($this->type == 'gz') {
			return gzwrite($this->handle, $string);
		}
		return fwrite($this->handle, $string);
	}
	function close() {
		if ($this->type == 'gz')
			$this->type = null;
			return gzclose($this->handle);
		$this->type = null;
		return fclose($this->handle);
	}
}
