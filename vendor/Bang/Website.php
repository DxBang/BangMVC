<?php
namespace Bang;
use Bang\Bang;
use Bang\Core;
use Bang\Security\CSP;

final class Website extends Bang {
	function __construct() {
		try {
			if (($config = include_once(SITE_PRIVATE.'/website.php')) == false) {
				throw new \Exception('Cannot locate Website config file', 1);
			}
			if (is_array($config)) {
				$config = Format::object($config);
			}
			Config::install($config);
			if (!$config->routes) {
				throw new \Exception('Security flaw! Missing routes', 2);
			}
			Config::set('routes', $config->routes);
			self::images($config->view->images);
			$config->view->bangImages = new WebImages($config->view->images);
			self::styles($config->view->styles);
			$config->view->bangStyles = new WebStyles($config->view->styles);
			self::scripts($config->view->scripts);
			$config->view->bangScripts = new WebScripts($config->view->scripts);
			self::fonts($config->view->fonts);
			$config->view->bangFonts = new WebFonts($config->view->fonts);
			$config->view->bangMeta = new WebMeta($config->view->meta);
			$config->view->bangPreconnect = null; #new WebPreX($config->view);
			$config->view->bangMenu = new Menu($config->menus);
			parent::__construct($config);
		} catch (Exception $e) {
			Core::exception($e);
		}
	}
	private static function scripts(array &$scripts = null) {
		if (is_null($scripts)) return;
	}
	private static function styles(array &$styles = null) {
		if (is_null($styles)) return;
	}
	private static function images(array &$images = null) {
		if (is_null($images)) return;
		#self::http2($images, 'image', 4);
	}
	private static function fonts(array &$fonts = null) {
		if (is_null($fonts)) return;
		#self::http2($fonts, 'font');
	}
	private static function http2(array $files, string $as, int $limit = 10) {
		$i = 0;
		foreach ($files as $file) {
			$file = explode("\t", $file)[0];
			HTTP2::add($file, $as, 'preload');
			$i++;
			if ($i > $limit) return;
		}
	}
}

class WebMeta {
	protected static
		$images,
		$data;
	function __construct(object $meta) {
		self::$data = (object) [
			'meta' => (object) [],
			'link' => (object) [],
			'social' => (object) [],
		];
		return self::addCluster($meta);
	}
	static function addCluster(object $meta) {
		if (empty($meta)) return;
		foreach ($meta as $k => $v) {
			self::add($k, $v);
		}
	}
	static function add($k, $v) {
		$k = strtolower($k);
		switch ($k) {
			case 'url':
				self::$data->link->canonical = Core::URL();
			break;
			case 'author':
			case 'publisher':
			case 'manifest':
			case 'sitemap':
			case 'search':
				self::$data->link->{$k} = $v;
			break;
			case 'themecolor':
				self::$data->meta->themecolor = $v;
			break;
			case 'keywords':
				self::$data->meta->keywords = implode(',', $v);
			break;
			case 'images':
			break;
			case 'facebook':
			case 'twitter':
			case 'opengraph':
				if (is_null($v)) break;
				self::$data->social->{$k} = $v;
				/*
				foreach ($v as $kk => $vv) {
					if (!is_null(self::$data->social->{$k})) {
						self::$data->social->{$k} = (object) [];
					}
					self::$data->social->{$k}->{$kk} = $vv;
				}
				*/
			break;
			case 'preconnect':
			case 'prefetch':
			case 'preload':
				#self
			break;
			default:
				self::$data->meta->{$k} = $v;
			break;
		}
	}
	static function clear() {
		self::$data = (object) [];
	}
	static function images() {
		self::$images = Config::get('bangImages', 'view')->data();
	}
	function __toString() {
		$r = '';
		$meta = '';
		$link = '';
		foreach (self::$data->link as $k => $v) {
			if (is_string($v) && empty($v)) continue;
			if (is_array($v) && empty($v)) continue;
			if (is_null($v)) continue;
			switch ($k) {
				case 'search':
					$e = explode('	', $v, 2);
					$e[1] = $e[1] ?? 'Search';
					$link .= '<link rel="'.$k.'" href="'.$e[0].'" title="'.$e[1].'" type="application/opensearchdescription+xml"/>'.PHP_EOL;
				break;
				case 'sitemap':
					$link .= '<link rel="'.$k.'" href="'.$v.'" type="application/xml"/>'.PHP_EOL;
				break;
				default:
					$link .= '<link rel="'.$k.'" href="'.$v.'"/>'.PHP_EOL;
			}
		}
		foreach (self::$data->meta as $k => $v) {
			switch ($k) {
				case 'themecolor':
					$meta .= '<meta name="theme-color" content="'.$v.'"/>'.PHP_EOL
						.'<meta name="msapplication-navbutton-color" content="'.$v.'"/>'.PHP_EOL
						.'<meta name="apple-mobile-web-app-status-bar-style" content="'.$v.'"/>'.PHP_EOL;
				break;
				case 'fullscreen':
					$meta .= '<meta name="mobile-web-app-capable" content="yes"/>'.PHP_EOL
						.'<meta name="apple-mobile-web-app-capable" content="yes"/>'.PHP_EOL;
				break;
				default:
					$meta .= '<meta name="'.$k.'" content="'.$v.'"/>'.PHP_EOL;
			}
		}
		self::images();
		$r .= self::twitter();
		$r .= self::facebook();
		$r .= self::opengraph();
		return $meta.$link.$r;
	}
	private static function twitter() {
		if (empty(self::$data->social->twitter)) return;
		$r1 = $r2 = '';
		$a = self::$data->social->twitter;
		$a->url = $a->url ?? self::$data->link->canonical;
		#$a->title = $a->title ?? self::$data->meta->title;
		#$a->description = $a->description ?? self::$data->meta->description;
		if (!empty(self::$images)) {
			if (count(self::$images) == 1) {
				$a->card = 'summary_large_image';
				$file = key(self::$images);
				$features = self::$images[$file];
				$r2 .= '<meta name="twitter:image" content="'.(Core::isURL($file) ? $file : Core::site().$file).'"/>'.PHP_EOL;
				if (is_array($features)) {
					foreach ($features as $k => $v) {
						if (empty($v)) continue;
						$r2 .= '<meta name="twitter:image:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
					}
				}
			}
			else if (count(self::$images) >= 2) {
				$a->card = 'summary';
				$i = 0;
				foreach (self::$images as $file => $features) {
					$r2 .= '<meta name="twitter:image'.$i.'" content="'.(Core::isURL($file) ? $file : Core::site().$file).'"/>'.PHP_EOL;
					$i++;
					if (!empty($features->default)) {
						$size = explode('x', $features->default);
						$features->width = $size[0];
						$features->height = $size[1];
						unset($features->default);
					}
					if (is_array($features)) '<!-- features is array -->';
					if (is_object($features)) '<!-- features is object -->';
					if (is_array($features)) {
						foreach ($features as $k => $v) {
							if (empty($v)) continue;
							$r2 .= '<meta name="twitter:image'.$i.':'.$k.'" content="'.$v.'"/>'.PHP_EOL;
						}
					}
				}
			}
		}
		foreach ($a as $k => &$v) {
			$r1 .= '<meta name="twitter:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
		}
		return !empty($r1.$r2) ? trim($r1.$r2).PHP_EOL : null;
	}
	private static function facebook() {
		if (!isset(self::$data->social->facebook)) return;
		$r = '';
		foreach (self::$data->social->facebook as $k => &$v) {
			if (empty($v)) continue;
			$r .= '<meta property="fb:'.$k.'" content="'.$v.'">'.PHP_EOL;
		}
		return strlen($r) ? trim($r).PHP_EOL : null;
	}
	private static function opengraph() {
		if (!isset(self::$data->social->opengraph)) return;
		$r1 = $r2 = '';
		$a = (object) self::$data->social->opengraph;
		$a->url = $a->url ?? self::$data->link->canonical;
		$a->title = $a->title ?? self::$data->meta->title;
		$a->description = $a->description ?? self::$data->meta->description;
		if (!empty(self::$images)) {
			if (count(self::$images) >= 2) {
				$a->type = 'article';
			}
			foreach (self::$images as $file => $features) {
				$r2 .= '<meta property="og:image" content="'.(Core::isURL($file) ? $file : Core::site().$file).'"/>'.PHP_EOL;
				if (isset($features->default)) {
					$size = explode('x', $features->default);
					$features->width = $size[0];
					$features->height = $size[1];
					unset($features->default);
				}
				if (!is_null($features)) {
					foreach ($features as $k => $v) {
						if (empty($v)) continue;
						$r2 .= '<meta property="og:image:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
					}
				}
			}
		}
		foreach ($a as $k => &$v) {
			$r1 .= '<meta property="og:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
		}
		return !empty($r1.$r2) ? trim($r1.$r2).PHP_EOL : null;
	}
}

abstract class Stacking {
	protected
		$push = true,
		$as = 'default',
		$data = [];
	function __construct(array $files = null) {
		if (empty($files)) return;
		return $this->addCluster($files);
	}
	function addCluster(array $files) {
		if (empty($files)) return;
		foreach ($files as $file) {
			if (empty($file)) return;
			if (is_string($file)) {
				$file = explode("\t", $file);
				$this->add($file[0], ['default' => ($file[1] ?? null)]);
				continue;
			}
			if (is_array($file)) {
				$f = array_shift($file);
				$this->add($f, $file);
			}
		}
	}
	function add(string $file, array $features = null, bool $push = false) {
		#$push = false;
		if ($this->push && !Validate::url($file)) {
			#self::fileTimeQuery($file);
			$push = true;
			#if (preg_match('\.css', $file)) {
			$file = Core::site().$file;
			#}
		}

		if ($this->push && $push) {
			#echo '$file: '.$file.' '.$push.PHP_EOL;
			HTTP2::add($file, $this->as);
		}
		return $this->data[$file] = !empty($features) ? (object) $features : null;
	}
	function clear() {
		$this->data = [];
	}
	private static function fileTimeQuery(string &$file) {
		if (!preg_match('/^https?:\/\//', $file)) {
			$e = explode('?', $file);
			if (file_exists(SITE_PUBLIC.$e[0])) {
				$file = $e[0].'?'.filemtime(SITE_PUBLIC.$e[0]);
			}
		}
	}
	function data() {
		return $this->data;
	}
}

class WebScripts extends Stacking {
	public
		$push = true,
		$as = 'script';
	function __toString() {
		if (empty($this->data)) return '';
		$r = '';
		foreach ($this->data as $file => $features) {
			if (!Core::isURL($file)) {
				$file = Core::site().$file;
			}
			$s = '';
			foreach ($features as $k => $v) {
				switch ($k) {
					case 'default':
						$s .= $v.' ';
					break;
					default:
						$s .= $k.'="'.$v.'" ';
					break;
				}
			}
			$s .= 'src="'.$file.'" ';
			if (Config::has('nonce'))
				$s .= CSP::nonceTag();
			$r .= '<script '.trim($s).'></script>'.PHP_EOL;
		}
		return $r;
	}
}

class WebStyles extends Stacking  {
	public
		$push = true,
		$as = 'style';
	function __toString() {
		if (empty($this->data)) return '';
		$r = '';
		foreach ($this->data as $file => $features) {
			if (!Core::isURL($file)) {
				$file = Core::site().$file;
			}
			$s = '';
			if (empty($features->rel)) $features->rel = 'stylesheet';
			if (empty($features->type)) $features->type = 'text/css';
			foreach ($features as $k => $v) {
				switch ($k) {
					case 'default':
						$s .= ' media="'.$v.'"';
						#$s .= ' media="print" onload="this.media=\''.$v.'\'"';
					break;
					default:
						$s .= ' '.$k.'="'.$v.'"';
					break;
				}
			}
			$s .= ' href="'.$file.'" ';
			if (Config::has('nonce'))
				$s .= CSP::nonceTag();
			$r .= '<link'.$s.'/>'.PHP_EOL;
		}
		return $r;
	}
}

class WebImages extends Stacking {
	public
		$push = false,
		$as = 'image';
	function __toString() {
		if (empty($this->data)) return '';
		$r = '';
		foreach ($this->data as $file => $features) {
			if (!Core::isURL($file)) {
				$file = Core::site().$file;
			}
			$s = '';
			if (empty($features)) $features = (object) ['rel' => 'image_src', 'type' => MIME::destination($file)];
			if (empty($features->rel)) $features->rel = 'image_src';
			if (empty($features->type)) $features->type = MIME::destination($file);
			$features->href = $file;
			foreach ($features as $k => $v) {
				switch ($k) {
					case 'default':
						#$r .= ' size="'.$v.'"';
					break;
					default:
						$s .= $k.'="'.$v.'" ';
					break;
				}
			}
			if (empty($r)) {
				$r .= '<link rel="apple-touch-icon" href="'.$features->href.'"/>'.PHP_EOL;
			}
			$r .= '<link '.trim($s).'/>'.PHP_EOL;
		}
		return $r;
	}
}

class WebFonts extends Stacking {
	public
		$push = true,
		$as = 'font';
	function __toString() {
		if (empty($this->data)) return '';
		$r = '<style';
		if (Config::has('nonce'))
			$r .= CSP::nonceTag(true);
		$r .= '>'.PHP_EOL;
		foreach ($this->data as $file => $features) {
			if (!Core::isURL($file)) {
				$file = Core::site().$file;
			}
			$r .= '@import url("'.$file.'");'.PHP_EOL;
		}
		$r .= '</style>'.PHP_EOL;
		return $r;
	}
}

class WebPreX {
	public
		$data = [];
	function __construct(object $view) {
		foreach($view as $key => $items) {
			if (!count($items)) continue;
			foreach ($items as $item) {
				$item = explode('	', $item)[0];
				switch ($key) {
					case 'scripts':
					case 'styles':
					case 'fonts':
						$item = implode('/', array_slice(explode('/', $item, 4), 0, 3));
						if (Core::isURL($item)) {
							$this->add($item, 'preconnect');
						}
					break;
				}
			}
		}
	}
	public function addArray(array $a, string $rel) {
		foreach($a as $v) {
			$this->add($v, $rel);
		}
	}
	public function add(string $url, string $rel = 'preconnect') {
		if (Core::isURL($url)) {
			$this->data[$url] = $rel;
		}
	}
	function __toString() {
		if (empty($this->data)) return '';
		return '';
		$r = '';
		foreach($this->data as $url => $rel) {
			$r .= '<link rel="'.$rel.'" href="'.$url.'"/>'.PHP_EOL;
		}
		return $r;
	}
}
