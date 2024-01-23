<?php
namespace Bang;

class MIME {
	private static
		$_mimes = [
			'css' => 'text/css',
			'flv' => 'video/x-flv',
			'htm' => 'text/html',
			'html' => 'text/html',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'ogx' => 'application/ogg',
			'php' => 'text/html',
			'swf' => 'application/x-shockwave-flash',
			'txt' => 'text/plain',
			'xml' => 'application/xml',

			# images
			'bmp' => 'image/bmp',
			'gif' => 'image/gif',
			'ico' => 'image/x-icon',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff',
			'webp' => 'image/webp',

			# fonts
			'otf' => 'font/otf',
			'ttf' => '	font/ttf',
			'woff' => 'font/woff',
			'woff2' => 'font/woff2',

			# archives
			'cab' => 'application/vnd.ms-cab-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'rar' => 'application/x-rar-compressed',
			'tar' => 'application/x-tar',
			'zip' => 'application/zip',

			# audio/video
			'avi' => 'video/x-msvideo',
			'mov' => 'video/quicktime',
			'mp3' => 'audio/mpeg',
			'mp4' => 'video/mp4',
			'mpeg' => 'video/mpeg',
			'mpg' => 'video/mpeg',
			'oga' => 'audio/ogg',
			'ogv' => 'video/ogg',
			'qt' => 'video/quicktime',
			'weba' => 'audio/webm',
			'webm' => 'video/webm',

			# adobe
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'pdf' => 'application/pdf',
			'ps' => 'application/postscript',
			'psd' => 'image/vnd.adobe.photoshop',

			# docs
			'doc' => 'application/msword',
			'docx' => 'application/msword',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ppt' => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		];
	static function extension(string $extension) {
		return isset(self::$_mimes[$extension]) ? self::$_mimes[$extension] : 'application/octet-stream';
	}
	static function destination(string $destination) {
		return self::extension(
			strtolower(pathinfo(parse_url($destination, PHP_URL_PATH), PATHINFO_EXTENSION))
		);
	}
}

