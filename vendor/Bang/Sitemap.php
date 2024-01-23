<?php
namespace Bang;
use Bang\Security\CSP;

class Sitemap {
	function __construct() {
		header('Content-Type: application/xml; charset=UTF-8');
	}
	static function url(string $url, string $lastmod, float $priority, string $changefreq = 'weekly', bool $end = false) {
		$lastmod = new \Datetime($lastmod);
		return '<url>'.PHP_EOL
			.'	<loc>'.self::encode($url).'</loc>'.PHP_EOL
			.'	<lastmod>'.$lastmod->format('c').'</lastmod>'.PHP_EOL
			.'	<priority>'.number_format($priority, 3).'</priority>'.PHP_EOL
			.'	<changefreq>'.$changefreq.'</changefreq>'
			.(
				$end
				? self::endUrl()
				: ''
			)
			.PHP_EOL;
	}
	static function endUrl() {
		return '</url>';
	}
	static function image(string $image, string $title, string $caption) {
		return '<image:image>'
				.'<image:loc>'.self::encode($image).'</image:loc>'
				.'<image:title>'.self::encode($title).'</image:title>'
				.'<image:caption>'.self::encode($caption).'</image:caption>'
			.'</image:image>'
			.PHP_EOL;
	}
	static function video(string $video, string $title, string $caption) {
		return '<video:video>'
				.'<video:loc>'.self::encode($video).'</video:loc>'
				.'<video:title>'.self::encode($title).'</video:title>'
				.'<video:caption>'.self::encode($caption).'</video:caption>'
			.'</video:video>'
			.PHP_EOL;
	}
	static function item(string $url, string $lastmod, float $priority=1.0, string $changefreq='weekly',
		string $image = null, string $caption = null, string $title = null) {
		return '<url>'.PHP_EOL
			.'<loc>'.self::encode($url).'</loc>'.PHP_EOL
			.'<changefreq>'.$changefreq.'</changefreq>'.PHP_EOL
			.'<priority>'.$priority.'</priority>'.PHP_EOL
			.'<lastmod>'.$lastmod.'</lastmod>'.PHP_EOL
			.(
				$image
				? '<image:image>'.PHP_EOL
					.'<image:loc>'.self::encode($image).'</image:loc>'
					.'<image:title>'.self::encode($title).'</image:title>'
					.'<image:caption>'.self::encode($caption).'</image:caption>'
				.'</image:image>'
				: ''
			)
			.'</url>'.PHP_EOL;
	}
	static function encode(string $string) {
		return htmlentities($string, ENT_XML1, 'UTF-8');
	}
	static function start(string $stylesheet = null) {
		$nonce = '';
		if (Config::has('nonce')) {
			$nonce = CSP::nonceTag(true);
			if ($stylesheet) {
				header("Content-Security-Policy: script-src 'self' 'unsafe-inline'", true);
			}
		}
		return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
			.(
				$stylesheet
				? '<?xml-stylesheet type="text/xsl" href="'.$stylesheet.'"'.$nonce.'?>'.PHP_EOL
				: ''
			)
			.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
				.' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"'
				.' xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">'.PHP_EOL;
	}
	static function end(bool $echo = false) {
		return '</urlset>';
	}
}