<?php
namespace Bang;

class Menu {
	static
		$menu;
	private static
		$sub = false,
		$name,
		$core,
		$dropId = 1;

	function __construct(array &$config) {
		try {
			if (!Core::get('routes')) {
				throw new Exception('Missing ROUTES', 1);
			}
			if (!isset($config['menus'])) {
				throw new Exception('Cannot read menus', 2);
			}
			if (!is_array($config['menus'])) {
				throw new Exception('Menus aren\'t array', 3);
			}
			if (isset($config['view']['menu'])) {
				throw new Exception('Menus has already been created', 4);
			}
			$config['view']['menu'] = self::_menuBlock($config['menus'], 'main menu clear');
		} catch (\Exception $e) {
			Core::exception($e);
		}
		return true;
	}

	private static function _menuBlock(array &$menus, string $class, int $depth = 0) {
		$r = '';
		foreach ($menus as $uri => &$menu) {
			if (Core::route($uri)) {
				$r .= self::_menuItem($uri, $menu, $depth);
			}
		}
		return ($r) ? '<ul class="'.$class.'">'.$r.'</ul>' : '';
		
	}
	private static function _menuActived(string $uri) {
		if ($uri == Core::URI(0)) {
			return 1;
		}
		$_dirs = Core::URI(-1);

		$_uri = explode('/', trim($uri, '/'));
		$c = 0;
		foreach ($_uri as $k => $v) {
			if (isset($_dirs[$k]) && ($_dirs[$k] == $v)) {
				$c++;
			}
		}
		if ($c == count($_uri)) {
			return 2;
		}
	}
	private static function _dropId() {
		$dropId = self::$dropId;
		self::$dropId++;
		return $dropId;
	}
	private static function _menuItem(string $uri, array $menu, int $depth) {
		$classes = [];
		$name = $menu[0];
		$nextDepth = $depth+1;
		$sub = (isset($menu[1]) && is_array($menu[1])) ? self::_menuBlock($menu[1], 'sub', $nextDepth) : '';
		if (isset($menu[2])) {
			$classes[] = $menu[2];
		}
		if ($active = self::_menuActived($uri)) {
			if ($active == 2) {
				if (self::$sub) {
					self::$sub .= ', '.$menu[0];
				}
				self::$sub = $menu[0];
			}
			if ($active == 1) {
				self::$name = $menu[0];
			}
			$classes[] = 'active';
		}
		$name = '<span>'.($name).'</span>';
		$drop = '';
		if (strlen($sub)) {
			$sub = $sub;
			$classes[] = 'has';
			$dropId = self::_dropId();
			$drop = '<input type="checkbox" class="gone" id="drop-'.$dropId.'"/>'
				.'<label for="drop-'.$dropId.'">+</label>';
		}
		$target = '';
		if (isset($menu[3]) && strlen($menu[3])) {
			$target = ' target="'.$menu[3].'" rel="noopener"';
		}
		else if (preg_match('/[http|ftp](s)?:\/\//', $uri)) {
			$target = ' target="_blank" rel="noopener"';
		}
		$classes = implode(' ', array_unique($classes));
		if ($name && $uri) {
			return '<li'
				.($classes ? ' class="'.$classes.'"' : '')
				.'><a href="'.$uri.'"'.$target.'>'.$name.'</a>'.$drop.$sub.'</li>';
		}
	}
	static function menu() {
		return self::$menu;
	}
	function __toString() {
		return self::$menu;
	}
}
