<?php
namespace Bang\Chain;

use Bang\Format;

class DOM {
	public
		$document,
		$script,
		$style,
		$anchor,
		$meta,
		$link,
		$font,
		$xpath;
	function __construct(bool $use_internal_errors = true) {
		libxml_use_internal_errors($use_internal_errors);
		$this->document = new \DOMDocument('1.0', 'UTF-8');
		return $this;
	}
	function load(string $html, int $options = 0):object {
		$this->document->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
		$this->xpath = new \DOMXpath($this->document);
		return $this;
	}
	function loadHTML(string $html, int $options = 0):object {
		return $this->load($html, $options);
	}
	function loadFile(string $htmlfile, int $options = 0):object {
		return $this->load(file_get_contents($htmlfile), $options);
	}
	function loadHTMLFile(string $htmlfile, int $options = 0):object {
		return $this->loadFile($htmlfile, $options);
	}
	function loadFileHTML(string $htmlfile, int $options = 0):object {
		return $this->loadHTMLFile($htmlfile, $options);
	}
	function loadXML(string $xml, int $options = 0):object {
		$this->document->loadXML(mb_convert_encoding($xml, 'HTML-ENTITIES', 'UTF-8'), $options);
		$this->xpath = new \DOMXpath($this->document);
		return $this;
	}
	function loadXMLFile(string $xmlfile, int $options = 0):object {
		return $this->loadXML(file_get_contents($xmlfile), $options);
	}
	function loadFileXML(string $xmlfile, int $options = 0):object {
		return $this->loadXMLFile($xmlfile, $options);
	}
	function reset():object {
		$this->xpath = null;
		return $this;
	}
	function textOnly(bool $ascii = false):string {
		$document = clone $this->document;
		$xpath = new \DOMXpath($document);
		foreach ($xpath->query('//style') as $v) {
			$v->textContent = null;
		}
		foreach ($xpath->query('//script') as $v) {
			$v->textContent = null;
		}
		$text = trim(preg_replace('/\s+/', ' ', $document->textContent));
		if ($document->encoding != 'UTF-8') {
			$detected = mb_detect_encoding($text,
				[
					'UTF-8',
					!empty($document->encoding) ? $document->encoding : 'auto'
				], true);
			if ($detected != 'UTF-8') {
				$text = mb_convert_encoding(
					$text,
					'UTF-8',
					$detected
				);
			}
		}
		return trim(preg_replace('/\s+/u', ' ', $text));
	}
	function title():string {
		$title = $this->xpath->query('//title');
		if ($title->length === 0) return '[untitled]';
		return Format::string($title[0]->textContent);
	}
	function anchors():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//a') as $v) {
			#print_r($v);
			$key = Format::string($v->textContent);
			if (empty(trim($key))) {
				$tag = '';
				foreach ($v->childNodes as $k => $n) {
					$tag .= '['.$n->tagName.']';
					if ($n->tagName == 'img') {
						$key .= $n->getAttribute('alt')
							? Format::string($n->getAttribute('alt'))
							: Format::string($n->getAttribute('title'));
					}
				}
				if (empty($key)) $key = $tag;
			}
			if (empty($key)) $key = '[anchor]';
			$value = $v->getAttribute('href')
				? trim($v->getAttribute('href'))
				: trim($v->getAttribute('name'));
			if (empty($value)) continue;
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			#print_r($r[$i]);
			$i++;
		}
		return $r;
	}
	function images():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//img') as $v) {
			$key = $v->getAttribute('alt')
				? Format::string($v->getAttribute('alt'))
				: Format::string($v->getAttribute('title'));
			if (empty($key)) $key = '[img]';
			$value = trim($v->getAttribute('src'));
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			$i++;
		}
		return $r;
	}
	function scripts():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//script') as $v) {
			$key = Format::string($v->getAttribute('type'));
			$value = Format::string($v->getAttribute('src'));
			if (empty($key)) $key = '[script]';
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			$i++;
		}
		return $r;
	}
	function styles():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//style') as $v) {
			$key = Format::string($v->getAttribute('type'));
			$value = Format::string($v->nodeValue);
			if (empty($key)) $key = '[style]';
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			$i++;
		}
		return $r;
	}
	function links():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//head/link') as $v) {
			$key = Format::string($v->getAttribute('rel'));
			$value = Format::string($v->getAttribute('href'));
			if (empty($key)) $key = '[link]';
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			$i++;
		}
		return $r;
	}
	function meta():array {
		$r = [];
		$i = 0;
		foreach ($this->xpath->query('//head/meta') as $v) {
			$key = $v->getAttribute('name')
				? Format::string($v->getAttribute('name'))
				: Format::string($v->getAttribute('property'));
			if (empty($key)) $key = '[meta]';
			$value = trim($v->getAttribute('content'));
			$r[$i] = (object) array_merge([
				'key' => $key,
				'value' => $value,
			], self::attributes($v, true));
			$i++;
		}
		return $r;
	}
	static function attributes(object $element, bool $asArray = false) {
		$r = [];
		foreach ($element->attributes as $k => $v) {
			$r[strtolower(trim($k))] = trim($v->value);
		}
		if ($asArray) return $r;
		return (object) $r;
	}
}

