<?php
namespace Bang;

class Error extends \Exception {
	function __construct(string $message, int $code = 999, \Error $previous = null) {
		parent::__construct($message, $code, $previous);
		$debug = Core::get('debug');
		Core::status($code);
		$error = $previous ?? $this;
		if (Core::isWebsite()) {
			if (file_exists(SITE_PRIVATE.'/views/error.php')) {
				require_once SITE_PRIVATE.'/views/error.php';
				exit;
			}
			echo '<dl class="debug error">'
					.'<dt>Code</dt>'
					.'<dd>'.$error->getCode().'</dd>'
					.'<dt>Message</dt>'
					.'<dd>'.$error->getMessage().'</dd>'
					.(
						$debug
						? '<dt>File</dt><dd>'.$error->getFile().'</dd>'
							.'<dt>Line</dt><dd>'.$error->getLine().'</dd>'
							.'<dt>Trace</dt><dd><pre>'.$error->getTraceAsString().'</pre></dd>'
						: ''
					)
				.'</dl>';
			exit;
		}
		if (Core::isAPI()) {
			Core::json([
				'success' => false,
				'errno' => $error->getCode() ? $error->getCode() : 999,
				'error' => $error->getMessage(),
				'type' => 'error',
				'debug' => (
					$debug
					? [
						'file' => $debug ? $error->getFile() : null,
						'line' => $debug ? $error->getLine() : null,
						'trace' => $debug ? $error->getTrace() : null,
					]
					: null
				),
			]);
			exit;
		}
		elseif (Core::isCLI()) {
			echo 'ERR: '.str_pad($this->getCode(), 6, ' ', STR_PAD_LEFT).' : '.$this->getMessage()
				.($debug ? PHP_EOL.str_pad(' -> '.$this->getLine(), 19, ' ', STR_PAD_LEFT).' : '.$this->getFile() : '').PHP_EOL;
		}
		exit;
	}
}
