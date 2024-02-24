<?php
namespace Bang;

abstract class Bang {
	private
		$controller,
		$viewer,
		$model;
	protected static
		$instance;

	function __construct(object $config) {
		Core::mark('Bang\Bang->__construct()');
		if (self::$instance) {
			die('only one bang instance is allowed');
		}
		self::$instance = true;
		try {
			Core::$config::install($config);
			if (!Core::route()) {
				throw new Error('bad route', 404);
			}
			if (!Core::filter()) {
				throw new Error('bad filter', 403);
			}
			#if (isset($config->detectBrowser) && $config->detectBrowser) {
			#	if (!Visitor::$browser->detect($_SERVER['HTTP_USER_AGENT'])) {
			#		throw new Exception('cannot detect browser', 403);
			#	}
			#}
			if (isset($config->useCache) && $config->useCache) {
				if (!Core::$cache) {
					Core::$cache = new Cache();
				}
			}
			if (isset($config->useMinify) && isset($config->minify)) {
				if (!Core::$minify) {
					Core::$minify = new Minify($config->useMinify);
				}
				#print_r($config->minify);
				if (is_object($config->minify) && !empty($config->minify)) {
					Core::$minify::sections($config->minify);
				}
			}
			if (isset($config->headers)) {
				Core::headers($config->headers, true);
			}
			if (!$this->loadController()) {
				throw new Error('bad route, missing controller', 412);
			}
			if (!$this->runController()) {
				throw new Error('bad route, missing controller method', 412);
			}
		} catch (\Error $e) {
			if (!headers_sent())
				header('Content-Type: text/plain');
			echo 'Error: '.$e->getCode().': '.$e->getMessage().PHP_EOL;
			echo $e->getFile().' ('.$e->getLine().')'.PHP_EOL;
			print_r($e->getTrace());
		} catch (\Exception $e) {
			if (!headers_sent())
				header('Content-Type: text/plain');
			echo 'Exception: '.$e->getCode().': '.$e->getMessage().PHP_EOL;
			echo $e->getFile().' ('.$e->getLine().')'.PHP_EOL;
			print_r($e->getTrace());
		}
	}
	function __destruct() {
		if (Core::isCLI())
			echo PHP_EOL;
	}
	static function isCLI() {
		return (php_sapi_name() === 'cli');
	}

	private static function className(string $name) {
		return preg_replace('/[^a-z]+/', '', strtolower($name));
	}
	private function loadController() {
		if ($this->controller) {
			throw new \Exception('controller is already loaded!', 11010);
		}
		$controllers = '/controllers';
		if (Core::isAPI()) {
			$controllers .= '/api';
		}
		if (Core::isSpecial()) {
			$controllers .= '/'.Core::URI(0);
		}
		if (Core::isAPI() && Core::isWebsite()) {
			$className = (strlen(Core::URI(0)) ? self::className(Core::URI(0)) : 'index');
			$file = $controllers.'/'.strtolower($className).'.php';
		}
		elseif (Core::isAPI() || Core::isSpecial()) {
			$className = (strlen(Core::URI(1)) ? self::className(Core::URI(1)) : 'index');
			$file = $controllers.'/'.strtolower($className).'.php';
		}
		else {
			$className = (strlen(Core::URI(0)) ? self::className(Core::URI(0)) : 'index');
			$file = $controllers.'/'.strtolower($className).'.php';
		}
		if (!file_exists(constant('SITE_PRIVATE').$file)) {
			throw new Error('bad route, missing controller: '.$file, 412);
		}
		Core::mark('Bang\Bang::loadController('.$file.')');
		require constant('SITE_PRIVATE').$file;
		$controllerName = $className.'Controller';
		$this->controller = new $controllerName($className);
		if (!$this->controller->model)
			$this->controller->_model($className);
		if (!$this->controller->view)
			$this->controller->_view();
		return true;
	}

	private function runController() {
		$dirs = Core::URI(-1);
		if (count($dirs)) {
			$depth = 0;
			if (Core::isAPI() && !Core::isWebsite()) {
				$depth++;
			}
			$slice = 0;
			$methods = array_slice($dirs, $depth, 5);
			$found = false;
			$method = false;
			$depthMethod = '';
			$foundMethod = '';
			foreach ($methods as $k => $v) {
				$depthMethod .= Format::method($v);
				if (method_exists($this->controller, $depthMethod)) {
					$foundMethod = $depthMethod;
					$slice = $k;
					$found = true;
				}
				else if (method_exists($this->controller, $v)) {
					$method = $v;
					$slice = $k;
					$found = true;
				}
			}
			$params = array_slice($dirs, $depth+$slice+1);
			$method = $foundMethod ?: $method ?: 'index';
			if (!$found && !method_exists($this->controller, $method)) {
				throw new Exception('controller method not found: '.$method, 412);
			}
			call_user_func_array(
				[
					$this->controller,
					$method
				],
				$params
			);
			return true;
		}
	}
	function __debugInfo() {
		return [
			'controller' => $this->controller,
			'viewer' => $this->viewer,
			'model' => $this->model,
		];
	}
	function __toString() {
		return json_encode($this->__debugInfo(), JSON_ENCODE_SETTINGS);
	}
}
