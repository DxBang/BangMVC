<?php
namespace Bang;

class xMeta {
	protected
		$images;
	function __construct(object $meta) {
		return $this->config($meta);
	}
	function config(object $meta) {
		foreach ($meta as $k => &$v) {
			switch ($k) {
				case 'title':
				break;
				case 'meta':
					$v['url'] = Core::URL();
					$meta['meta'] = $this->parse($v);
				break;
				case 'styles':
				case 'scripts':
				case 'images':
				case 'fonts':
				break;
				case 'serviceWorker':
				#	if (Core::protocol() == 'https') {
						$meta['serviceWorker'] = '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("'.$v.'")})}</script>';
						break;
				#	}
					$meta['serviceWorker'] = null;
				break;
			}
		}
	}

	private function twitter(object $a) {
		$r1 = '';
		$r2 = '';
		if (is_array($a['twitter'])) {
			$a['twitter']['url'] = $a['url'];
			$a['twitter']['title'] = $a['title'];
			$a['twitter']['description'] = $a['description'];
			if (isset($a['images']) && is_array($a['images'])) {
				if (count($a['images']) >= 4) {
					$a['twitter']['card'] = 'summary';
				}
				$i = 0;
				foreach ($a['images'] as $k => &$v) {
					$v = explode("\t", $v)[0];
					$r2 .= (Core::isURL($v)
						? '<meta property="twitter:image'.$i.'" content="'.$v.'"/>'
						: '<meta property="twitter:image'.$i.'" content="'.Core::site().$v.'"/>').PHP_EOL;
					$i++;
				}
			}
			foreach ($a['twitter'] as $k => &$v) {
				if ($v) {
					$v = explode("\t", $v)[0];
					$r1 .= '<meta property="twitter:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
				}
			}
			return strlen($r1.$r2) ? trim($r1.$r2).PHP_EOL : null;
		}
	}

	private function opengraph(object $a) {
		$r1 = '';
		$r2 = '';
		if (is_array($a['opengraph'])) {
			$a['opengraph']['url'] = $a['url'];
			$a['opengraph']['title'] = $a['title'];
			$a['opengraph']['description'] = $a['description'];
			if (isset($a['images']) && is_array($a['images'])) {
				if (count($a['images']) >= 4) {
					$a['opengraph']['type'] = 'article';
				}
				foreach ($a['images'] as $k => &$v) {
					$file = explode("\t", $v);
					$r2 .= (Core::isURL($file[0])
						? '<meta property="og:image" content="'.$file[0].'"/>'
						: '<meta property="og:image" content="'.Core::site().$file[0].'"/>').PHP_EOL;
					if (!empty($file[1]) && $size = explode('x', $file[1])) {
						$r2 .= '<meta property="og:image:width" content="'.$size[0].'"/>'.PHP_EOL
							.'<meta property="og:image:height" content="'.$size[1].'"/>'.PHP_EOL;
					}
				}
			}
			foreach ($a['opengraph'] as $k => &$v) {
				if ($v) {
					$r1 .= '<meta property="og:'.$k.'" content="'.$v.'"/>'.PHP_EOL;
				}
			}
			return strlen($r1.$r2) ? trim($r1.$r2).PHP_EOL : null;
		}
	}

	private function facebook(object $a) {
		$r = '';
		if (is_array($a['facebook'])) {
			foreach ($a['facebook'] as $k => &$v) {
				if ($v) {
					$r .= '<meta property="fb:'.$k.'" content="'.$v.'">'.PHP_EOL;
				}
			}
			return strlen($r) ? trim($r).PHP_EOL : null;
		}
	}

	function parse(object $a) {
		$meta = '<meta charset="'.Core::get('charset').'"/>'.PHP_EOL;
		$link = '';
		foreach ($a as $k => &$v) {
			switch ($k) {
				case 'url':
					$link .= '<link rel="canonical" href="'.$v.'"/>'.PHP_EOL;
				break;
				case 'author':
				case 'publisher':
				case 'manifest':
					$link .= '<link rel="'.$k.'" href="'.$v.'"/>'.PHP_EOL;
				break;
				case 'sitemap':
					$link .= '<link rel="sitemap" href="'.$v.'" type="application/xml"/>'.PHP_EOL;
				break;
				case 'themeColor':
					$meta .= '<meta name="theme-color" content="'.$v.'"/>'.PHP_EOL
						.'<meta name="msapplication-navbutton-color" content="'.$v.'"/>'.PHP_EOL
						.'<meta name="apple-mobile-web-app-status-bar-style" content="'.$v.'"/>'.PHP_EOL;
				break;
				case 'keywords':
					$meta .= '<meta name="keywords" content="'.implode(',', $v).'"/>'.PHP_EOL;
				break;
				case 'images':
					# skip there as they are created from WebImages();
					$link .= $this->images($a);
					# $link .= $this->images;
				break;
				case 'facebook':
					$meta .= $this->facebook($a);
				break;
				case 'opengraph':
					$meta .= $this->opengraph($a);
				break;
				case 'twitter':
					$meta .= $this->twitter($a);
				break;
				default:
					$meta .= '<meta name="'.$k.'" content="'.htmlentities($v).'"/>'.PHP_EOL;
				break;
			}
		}
		return $meta.$link;
	}
	private function images(object $a) {
		$r = '';
		if (is_array($a['images'])) {
			foreach ($a['images'] as $k => &$v) {
				if ($v) {
					$file = explode("\t", $v)[0];
					$r .= '<link rel="image_src" type="'.MIME::destination($file).'" href="'.(Core::isURL($file) ? $file : Core::site().$file).'"/>'.PHP_EOL;
				}
			}
			return strlen($r) ? trim($r).PHP_EOL : null;
		}
	}
}
