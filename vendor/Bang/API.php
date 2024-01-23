<?php
namespace Bang;

final class API extends Bang {
	function __construct() {
		try {
			if (($config = require_once(SITE_PRIVATE.'/api.php')) == false) {
				throw new \Exception('Cannot locate API config file', 1);
			}
			if (is_array($config)) {
				$config = json_decode(json_encode($config), false);
			}
			if (!$config->routes) {
				throw new \Exception('Security flaw! Missing routes', 2);
			}
			Core::set('routes', $config->routes);
			parent::__construct($config);
		}  catch (\Exception $e) {
			Core::exception($e);
		}
	}
}
