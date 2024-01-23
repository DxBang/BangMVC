<?php
namespace Bang;

class BrowserAgent {
	private
		$os,
		$tags,
		$bits,
		$agent,
		$mozilla,
		$browser;
	private static
		$_browser = [
			'chrome' => '/(?<name>Chrome)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'edge' => [
				'/(?<name>Edge)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>Edg)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/'
			],
			'firefox' => '/(?<name>Firefox)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'ie' => [
				'/(?<name>MSIE).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>Trident)\/[0-9]\.[0-9].*rv\:(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			],
			'opera' => [
				'/(?<name>Opera)\/[0-9]+\.[0-9].*Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>OPR)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			],
			'safari'	=> '/Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))(\.[0-9]+)?\s(?<name>Safari)\//',
		],
		$_mobile = [
			'chrome'	=> '/(?<name>Chrome)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)).*Mobile/',
			'ie'		=> '/(?<name>IEMobile).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'mobile'	=> '/.(?<name>Mobile|i?Phone|iPad|Tablet)./',
			'safari'	=> '/Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))(\.[0-9]+)?.Mobile(\/[0-9a-z]+)?\s(?<name>Safari)\//',
			'ucbrowser'	=> '/(?<name>UCBrowser)(\/)?(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)).*Mobile/',
		],
		$_os = [
			'windows'	=> '/(?<name>Windows(\sNT)?)\s(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
		],
		$_app = [

		],
		$_bits = [
			'x64'			=> '/WOW64|x64/',
		],
		$_bot = [
			'bing'			=> '/(?<name>bingbot)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
			'duckduckgo'	=> '/(?<name>DuckDuckBot)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
			'facebook'		=> '/(?<name>facebookexternalhit)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
			'google'		=> '/(?<name>Googlebot(\-[A-Za-z]+)?)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
			'tumblr'		=> '/(?<name>Tumblr)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
			'twitter'		=> '/(?<name>TwitterBot)\/(?<version>(?<major>[0-9]{1,})\.(?<minor>[0-9]{1,}))/',
		],
		$_cmd = [

		];
	function __construct(string $agent = null) {
		if ($agent)
			$this->detect($agent);
	}
	function reset() {
		$this->os
		= $this->bits
		= $this->agent
		= $this->mozilla
		= $this->browser
		= null;
		$this->tags = (object) [
			'browser' => [],
			'mobile' => [],
			'os' => [],
			'bot' => [],
			'app' => [],
			'cmd' => [],
		];
	}
	function parse(string $agent = null) {
		return $this->detect($agent);
	}
	function detect(string $agent = null) {
		$this->reset();
		if (is_null($agent)) return;
		$this->agent = $agent;
		if ($this->detectMozilla($agent)) {
			$this->detectBrowser($agent);
			$this->detectMobile($agent);
			$this->detectOS($agent);
		}
		$this->detectBot($agent);
	}
	private function detectMozilla(string $agent = null) {
		if (is_null($agent)) return;
		if (preg_match('/^(?<name>mozilla)\/(?<version>(?<major>[0-9]+)\.?(?<minor>[0-9]+)?)/i', $agent, $m)) {
			$this->mozilla = (object) [
				'name' => $m['name'],
				'version' => $m['version'],
				'major' => (int) $m['major'],
				'minor' => (int) $m['minor'] ?? 0,
			];
			return true;
		}
	}
	private function detectBrowser(string $agent = null) {
		if (is_null($agent)) return;
		foreach (self::$_browser as $tag => $regex) {
			if ($m = $this->regexAgent($agent, $regex)) {
				$this->tags->browser[] = $tag;
				$this->browser = (object) [
					'name' => $m['name'] ?? null,
					'version' => $m['version'] ?? null,
					'major' => (int) $m['major'] ?? 0,
					'minor' => (int) $m['minor'] ?? 0,
				];
				break;
			}
		}
	}
	function detectMobile(string $agent = null) {
		if (is_null($agent)) return;
		foreach (self::$_mobile as $tag => $regex) {
			if ($m = $this->regexAgent($agent, $regex)) {
				$this->tags->mobile[] = $tag;
				break;
			}
		}
	}
	private function detectBot(string $agent = null) {
		if (is_null($agent)) return;
		foreach (self::$_bot as $tag => $regex) {
			if ($this->regexAgent($agent, $regex)) {
				$this->tags->bot[] = $tag;
				break;
			}
		}
	}
	private function detectOS(string $agent = null) {
		if (is_null($agent)) return;
		foreach (self::$_os as $tag => $regex) {
			if ($this->regexAgent($agent, $regex)) {
				$this->tags->os[] = $tag;
				break;
			}
		}
	}
	private function regexAgent(string $agent, $regex) {
		if (is_null($regex)) return;
		if (is_array($regex)) {
			foreach ($regex as $re) {
				if ($m = $this->regexAgent($agent, $re)) {
					return $m;
				}
			}
			return -1;
		}
		if (preg_match($regex, $agent, $m)) {
			return $m;
		}
	}



	
	function isMobile() {
		return !empty($this->tags->mobile);
	}
	function isDesktop() {
		return !$this->isMobile();
	}
	function isChrome() {

	}
	function isEdge() {

	}
	function isFirefox() {

	}
	function isOpera() {

	}
	function isInternetExplorer() {

	}
	function isSafari() {

	}
	function isWindows() {

	}
	function isLinux() {

	}
	function isMac() {

	}
	function isBot() {
		return !empty($this->tags->bot);
	}
	function isSearchEngine() {}
	function isSocialNetwork() {}
	function isUnknown() {}

	function __toString() {
		return substr($this->agent, 0, 16).' -> '
			.json_encode($this->__debugInfo(), JSON_UNESCAPED_SLASHES);
	}
	function __debugInfo():array {
		return [
			'os' => $this->os,
			'tags' => $this->tags,
			'bits' => $this->bits,
			'mozilla' => $this->mozilla,
			'browser' => $this->browser,

			'isDesktop' => $this->isDesktop(),
			'isMobile' => $this->isMobile(),
			'isBot' => $this->isBot(),
			'isSearchEngine' => $this->isSearchEngine(),
			'isSocialNetwork' => $this->isSocialNetwork(),
			'isChrome' => $this->isChrome(),
			'isFirefox' => $this->isFirefox(),
			'isOpera' => $this->isOpera(),
			'isSafari' => $this->isSafari(),
			'isEdge' => $this->isEdge(),
			'isInternetExplorer' => $this->isInternetExplorer(),
			'isUnknown' => $this->isUnknown(),
		];
	}
}