<?php
namespace Bang;

class Upload {
	public
		$core,
		$errno,
		$error,
		$file,
		$ext,
		$mime,
		$hash,
		$dest,
		$fileSizeLimit = 5e+7,
		$uploadDir,
		$accept = [
			'image' => ['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif'],
			'video' => ['mp4' => 'video/mp4'],
		];
	private
		$_checked;

	function __construct(string $uploadDir = null) {
		global $core;
		$this->core = &$core;
		if ($uploadDir) {
			$this->setUploadDir($uploadDir);
		}
	}
	function setUploadDir(string $uploadDir, bool $create = false) {
		if (!file_exists($uploadDir) && $create) {
			if (file_exists(dirname($uploadDir)) && is_dir(dirname($uploadDir))) {
				$this->core->makeDir($uploadDir);
			}
		}
		if (!is_dir($uploadDir)) {
			throw new \Exception('Upload directory isn\'t a directory: '.$uploadDir, 1);
		}
		$this->uploadDir = $uploadDir;
	}


	function check(string $key, array $file) {
		$this->_checked = true;
		if (!isset($file['error']) || is_array($file['error'])) {
			throw new \Exception('Invalid parameters.');
		}
		switch ($file['error']) {
			case UPLOAD_ERR_OK:
			break;
			case UPLOAD_ERR_NO_FILE:
				throw new \Exception('No file sent.');
			break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new \Exception('Exceeded filesize limit.');
			break;
			default:
				throw new \Exception('Unknown errors.');
			break;
		}
		if ($file['size'] > $this->fileSizeLimit) {
			throw new \Exception('Exceeded filesize limit.');
		}
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		if (!$this->accept[$key]) {
			throw new \Exception('Unknown or incorrect type');
		}
		
		if (false === $this->ext = array_search(
			$finfo->file($file['tmp_name']),
			$this->accept[$key],
			true
		)) {
			throw new \Exception('Invalid file format.');
		}
		$this->mime = $this->accept[$key][$this->ext];
		if (false === $this->hash = sha1_file($file['tmp_name'])) {
			throw new \Exception('Failed to hash file.');
		}
		return $this->hash;
	}

	function moveFile(string $key, array $file) {
		if ($this->_checked) {
			$this->check($key, $file);
		}
		if ($this->hash && $this->ext) {
			$dest = sprintf($this->uploadDir.'/%s.%s', $this->hash, $this->ext);
			if (!move_uploaded_file($file['tmp_name'], $dest)) {
				throw new \RuntimeException('Failed to move uploaded file.');
			}
			if (file_exists($dest)) {
				$this->file = $file['name'];
				$this->dest = $dest;
				return true;
			}
		}
	}
	function __debugInfo() {
		return [
			'errno' => $this->errno,
			'error' => $this->error,
			'dest' => $this->dest,
			'file' => $this->file,
			'ext' => $this->ext,
			'mime' => $this->mime,
			'hash' => $this->hash,
			'fileSizeLimit' => $this->fileSizeLimit,
			'uploadDir' => $this->uploadDir,
			'accept' => $this->accept
		];
	}
}