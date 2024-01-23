<?php
namespace Bang;

class Exception extends \Exception {
	function __construct(string $message, int $code = 0, \Exception $previous = null) {
		parent::__construct($message, $code, $previous);
		$debug = Core::get('debug') ?? false;
		$exeption = $previous ?? $this;
		if (Core::isWebsite()) {
			echo '<dl class="debug error exception">'
					.'<dt>Code</dt>'
					.'<dd>'.$exeption->getCode().'</dd>'
					.'<dt>Message</dt>'
					.'<dd>'.$exeption->getMessage().'</dd>'
					.(
						$debug
						? '<dt>File</dt><dd>'.$exeption->getFile().'</dd>'
							.'<dt>Line</dt><dd>'.$exeption->getLine().'</dd>'
							.'<dt>Trace</dt><dd><pre>'.$exeption->getTraceAsString().'</pre></dd>'
						: ''
					)
				.'</dl>';
			return;
			#exit;
		}
		elseif (Core::isAPI()) {
			Core::json([
				'success' => false,
				'errno' => $exeption->getCode() ? $exeption->getCode() : 0,
				'error' => $exeption->getMessage(),
				'type' => 'exception',
				'debug' => (
					$debug
					? [
						'file' => $debug ? $exeption->getFile() : null,
						'line' => $debug ? $exeption->getLine() : null,
						'trace' => $debug ? $exeption->getTrace() : null,
					]
					: null
				)
			]);
			exit;
		}
		elseif (Core::isCLI()) {
			echo 'EXC: '.str_pad($this->getCode(), 6, ' ', STR_PAD_LEFT).' : '.$this->getMessage()
				.($debug ? PHP_EOL.str_pad(' -> '.$this->getLine(), 19, ' ', STR_PAD_LEFT).' : '.$this->getFile() : '').PHP_EOL;
		}
	}
}
