<?php
namespace Bang;

final class IconAPI extends Bang {
	protected
		$image;
	const
		SIZES = [
			'128x128',
			'192x192',
		];
	function __construct() {
		print_r(Core::debug());
		try {

			$this->image = new Image;
			#Core::set('routes', $config->routes);
			#parent::__construct($config);
		}  catch (\Exception $e) {
			Core::exception($e);
		}
	}
}
