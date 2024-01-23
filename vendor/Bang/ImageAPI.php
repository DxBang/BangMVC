<?php
namespace Bang;

final class ImageAPI extends Bang {
	public $image;
	function __construct() {
		try {
			if (($config = include_once(SITE_PRIVATE.'/imageapi.php')) == false) {
				throw new \Exception('Cannot locate ImageAPI config file', 1);
			}
			if (is_array($config)) {
				$config = json_decode(json_encode($config), false);
			}
			if (!$config->routes) {
				throw new \Exception('Security flaw! Missing routes', 2);
			}
			$this->image = new Image;
			Core::set('routes', $config->routes);
			parent::__construct($config);
		}  catch (\Exception $e) {
			Core::exception($e);
		}
	}
}
