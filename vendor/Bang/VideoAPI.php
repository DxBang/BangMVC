<?php
namespace Bang;

final class VideoAPI extends Bang {
	public $video;
	function __construct() {
		try {
			if (($config = include_once(SITE_PRIVATE.'/videoapi.php')) == false) {
				throw new \Exception('Cannot locate VideoAPI config file', 1);
			}
			if (is_array($config)) {
				$config = json_decode(json_encode($config), false);
			}
			if (!$config->routes) {
				throw new \Exception('Security flaw! Missing routes', 2);
			}
			#$this->video = new \Bang\Video();
			Core::set('routes', $config->routes);
			parent::__construct($config);
		}  catch (\Exception $e) {
			Core::exception($e);
		}
	}
}
