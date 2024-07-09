<?php
namespace Bang;

class Menu {
	private static
		$sub = false,
		$name,
		$format,
		$menus,
		$dirs,
		$schema,
		$dropId = 0;

	function __construct(object $menus = null) {
		if (is_null($menus)) return;
		try {
			self::$dirs = Core::URI(-1);
			self::$format = (object) [
				'drop' => 'drop',
				'active_class' => 'act',
				'has_sub_class' => 'has',
				'input_checkbox_class' => 'gone',
				'main_class' => 'main',
				'main_label' => '<i class="on material-icons">menu</i><i class="off material-icons">close</i>',
				'main_label_class' => 'main',
				'nav_class' => 'menu clear',
				'nav_id' => 'menu',
				'sub_class' => 'sub',
				'sub_label' => '<i class="on material-icons">add</i><i class="off material-icons">remove</i>',
				'sub_label_class' => 'sub',
			];
			self::$schema = Core::get('schema');
			if (!Core::get('routes')) {
				throw new Exception('missing routes', 1);
			}
			if (!isset($menus)) {
				throw new Exception('cannot read menus', 2);
			}
			if (!is_object($menus)) {
				throw new Exception('menus isn\'t object', 3);
			}
			if (isset(self::$menus)) {
				throw new Exception('menus has already been assigned', 4);
			}
			self::$menus = $menus;
		} catch (\Exception $e) {
			Core::exception($e);
		}
	}
	static function schema(string $item) {
		if (!self::$schema) return;
		switch ($item) {
			case 'nav':
				return ' itemscope itemtype="http://www.schema.org/SiteNavigationElement"';
			break;
			case 'url':
				return ' itemprop="url"';
			break;
			case 'name':
				return ' itemprop="name"';
			break;
			default:
			break;
		}
	}
	static function build($menus, int $depth = 0) {
		$menus = Format::object($menus);
		$r = '';
		foreach ($menus as $uri => &$menu) {
			if ($uri === '0') {
				foreach ($menu as $k => $v) {
					self::$format->{$k} = $v;
					if ($k == 'drop') {
						self::$dropId = 0;
					}
				}
				continue;
			}
			if (Core::route($uri)) {
				$r .= self::_menuItem($uri, $menu, $depth);
			}
		}
		$dropId = self::_dropId();
		return !empty($r) ? '<nav'
			.(!empty(self::$format->nav_class) ? ' class="'.self::$format->nav_class.'"' : '')
			.' id="'.self::$format->nav_id.'"'.self::schema('nav').' aria-label="navigation">'
			.'<input type="checkbox"'
			.(!empty(self::$format->input_checkbox_class) ? ' class="'.self::$format->input_checkbox_class.'"' : '')

			.' id="'.$dropId.'">'
			.'<label for="'.$dropId.'"'
			.(!empty(self::$format->main_label_class) ? ' class="'.self::$format->main_label_class.'"' : '')
			.'>'
				.self::$format->main_label
			.'</label>'
			.'<ul'
			.(!empty(self::$format->main_class) ? ' class="'.self::$format->main_class.'"' : '')
			.'>'.$r.'</ul></nav>' : '';
	}
	private static function _menuBlock(object $menus, int $depth = 0) {
		$r = '';
		foreach ($menus as $uri => &$menu) {
			if (Core::route($uri)) {
				$r .= self::_menuItem($uri, $menu, $depth);
			}
		}
		return ($r) ? '<ul'
			.(!empty(self::$format->sub_class) ? ' class="'.self::$format->sub_class.'"' : '')
			.'>'.$r.'</ul>' : '';
		return ($r) ? '<ul class="'.self::$format->sub_class.'">'.$r.'</ul>' : '';
	}
	private static function _menuActived(string $uri) {
		if ($uri == Core::URI(0)) {
			return 1;
		}
		$_uri = explode('/', trim($uri, '/'));
		$c = 0;
		foreach (self::$dirs as $k => $v) {
			if (isset($_uri[$k]) && ($_uri[$k] == $v)) {
				$c++;
			}
		}
		if ($c == count($_uri)) {
			return $c;
		}
	}
	private static function _dropId() {
		$dropId = self::$dropId;
		self::$dropId++;
		return self::$format->drop.'-'.$dropId;
	}
	private static function _menuItem(string $uri, array $menu, int $depth) {
		$classes = [];
		$name = $menu[0];
		$nextDepth = $depth+1;
		$sub = (!empty($menu[1]) && (is_object($menu[1]))) ? self::_menuBlock($menu[1], $nextDepth) : '';
		if (isset($menu[2])) {
			$classes[] = $menu[2];
		}
		if ($active = self::_menuActived($uri)) {
			if ($active >= 2) {
				if (self::$sub) {
					self::$sub .= ', '.$menu[0];
				}
				self::$sub = $menu[0];
			}
			if ($active == 1) {
				self::$name = $menu[0];
			}
			$classes[] = self::$format->active_class;
		}
		$name = '<span'.self::schema('name').'>'.($name).'</span>';
		$drop = '';
		if (strlen($sub)) {
			$sub = $sub;
			$classes[] = (self::$format->has_sub_class ?? 'has');
			$dropId = self::_dropId();
			$drop = '<input type="checkbox" class="'.self::$format->input_checkbox_class.'" id="'.$dropId.'"/>'
				.'<label for="'.$dropId.'" class="'.self::$format->sub_label_class.'">'
					.self::$format->sub_label
				.'</label>';
		}
		$target = [];
		$rel = [];
		if (!empty($menu[3])) {
			$target[] = $menu[3];
		}
		if (preg_match('/[http|ftp](s)?:\/\//', $uri)) {
			$target[] = '_blank';
			$rel[] = 'noopener';
		}
		if (!empty($menu[4])) {
			$rel[] = $menu[4];
		}
		$target = implode(' ', array_unique($target));
		$rel = implode(' ', array_unique($rel));
		$classes = implode(' ', array_unique($classes));
		if ($name && $uri) {
			return '<li'
				.($classes ? ' class="'.$classes.'"' : '')
				.'><a href="'.$uri.'"'
				.($target ? ' target="'.$target.'"' : '')
				.($rel ? ' rel="'.$rel.'"' : '')
				.self::schema('url').'>'.$name.'</a>'
				.$drop.$sub.'</li>';
		}
	}
	static function menu() {
		return self::$menu;
	}
	function __toString() {
		return self::build(self::$menus);
		return self::$menu;
	}
}

/*
FontAwesome
<i class="on fa fa-bars"></i><i class="off fa fa-times"></i>
<i class="on fa fa-plus"></i><i class="off fa fa-minus"></i>

Google Material
<i class="on material-icons">menu</i><i class="off material-icons">close</i>
<i class="on material-icons">add</i><i class="off material-icons">remove</i>

	'menus' => [
		[
			'nav_class' => 'menu',
			'main_class' => 'main',
			'main_label' => '<i class="on material-icons">menu</i><i class="off material-icons">close</i>',
			'sub_class' => 'sub',
			'sub_label' => '<i class="on material-icons">add</i><i class="off material-icons">remove</i>',
			'has_sub_class' => 'has',
			'active_class' => 'active',
			'input_checkbox_class' => 'gone',
		],
		'/' => ['root', null],
		...
*/
