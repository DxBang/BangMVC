<?php
namespace Bang;

class Browser {
	public
		$userAgent,
		$agent;

	private static
		$regExDesktop = [
			'chrome'	=> '/(?<name>Chrome)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'edge'		=> [
				'/(?<name>Edg?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>Edg)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			],
			'firefox'	=> '/(?<name>Firefox)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'ie'		=> [
				'/(?<name>MSIE).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>Trident)\/[0-9]\.[0-9].*rv\:(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			],
			'opera'		=> [
				'/(?<name>Opera)\/[0-9]+\.[0-9].*Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>OPR)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			],
			'safari'	=> '/Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))(\.[0-9]+)?\s(?<name>Safari)\//',
		],
		$regExMobile = [
			'chrome'	=> '/(?<name>Chrome)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)).*Mobile/',
			'ie'		=> '/(?<name>IEMobile).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'mobile'	=> '/.(?<name>Mobile|i?Phone|iPad|Tablet)./',
			'safari'	=> '/Version\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))(\.[0-9]+)?.Mobile(\/[0-9a-z]+)?\s(?<name>Safari)\//',
			'ucbrowser'	=> '/(?<name>UCBrowser)(\/)?(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)).*Mobile/',
			#'phantomjs'	=> '/(?<name>PhantomJS)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'nokia'		=> '/(?<name>Nokia([0-9]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/'
		],
		$regExApp = [
			'facebook'	=> '/(?<name>FBPN\/com\.facebook\.katana)/',
			'instagram'	=> '/(?<name>Instagram).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'whatsapp'	=> '/(?<name>WhatsApp).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'dalvik'	=> '/(?<name>Dalvik).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'skype'		=> '/(?<name>SkypeUriPreview Preview).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
		],
		$regExBot = [
			'bot'			=> '/(?<name>([a-z0-9\-]+)(.)?bot)(.(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))|;)?/i',
			'spider'		=> '/(?<name>([a-z0-9\-]+)(.)?spider)(.(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))|;)?/i',
			'bing'			=> '/(?<name>bing([a-z0-9\-]+))\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/i',
			'duckduckgo'	=> '/(?<name>DuckDuck([A-Za-z\-]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'facebook'		=> '/(?<name>facebookexternal([a-z]+))(\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)))?/',
			'google'		=> '/(?<name>(\-[A-Za-z]+)?Google([A-Za-z\-]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'yandex'		=> '/(?<name>YandexBot(\-[A-Za-z]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'yandexmetrika'	=> '/(?<name>YandexMetrika(\-[A-Za-z]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'tumblr'		=> '/(?<name>Tumblr)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'baiduspider'	=> '/(?<name>Baiduspider)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'twitter'		=> '/(?<name>TwitterBot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'tineye'		=> '/(?<name>TinEye-bot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'screaming'		=> '/(?<name>Screaming Frog).SEO.Spider\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'semrush'		=> '/(?<name>semrush([a-z\-]+)?)(\/(?<version>(?<major>[0-9]+)\.?(?<minor>[0-9]+)?))?/i',
			'majestic'		=> '/(?<name>MJ(?<version>(?<major>[0-9]+)\.?(?<minor>[0-9]+)?)bot)/',
			'jetmon'		=> '/(?<name>jetmon)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'ahrefsbot'		=> '/(?<name>AhrefsBot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'discord'		=> '/(?<name>Discordbot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'adbot'			=> '/(?<name>AdBot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'nimbostratus'	=> '/(?<name>Nimbostratus-Bot)\/v?(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'slackbot'		=> '/(?<name>Slackbot([a-z\-]+)?)[\/\s]?(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/i',
			'pinterest'		=> '/(?<name>Pinterestbot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'qwantify'		=> '/(?<name>Qwantify)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'daum'			=> '/(?<name>Daum)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'bitlybot'		=> '/(?<name>bitlybot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'sentry'		=> '/(?<name>sentry)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'seznambot'		=> '/(?<name>SeznamBot)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'jigsaw'		=> '/(?<name>Jigsaw)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'wordpress'		=> '/(?<name>WordPress)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'cloudflare'	=> '/(?<name>cloudflare([a-z\-\s]+)?)(\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)))?/i',
			'zoombot-link'	=> '/ZoomBot.*(?<name>Linkbot).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'test-cert'		=> '/(?<name>Test Certificate Info)/',
			'coccocbot'		=> '/(?<name>coccocbot[a-z\-]+)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'validator'		=> '/(?<name>([A-Za-z0-9\-_]+)?Validator([A-Za-z\-\.\/]+)?)(\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)))?/',
		],
		$regExCMD = [
			'python'		=> '/(?<name>Python([a-z\-\s]+)?)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/i',
			'go'			=> [
				'/(?<name>Go(\-http\-client)?).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
				'/(?<name>Go).(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)).([a-z\s]+)?/',
			],
			#Go 1.1 package http
			'curl'			=> '/(?<name>curl)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'node'			=> '/(?<name>node\-superagent)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'java'			=> '/(?<name>Java)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'axios'			=> '/(?<name>axios)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'git'			=> '/(?<name>git)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'okhttp'		=> '/(?<name>okhttp)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			'ruby'			=> '/(?<name>^Ruby$)/',
		],
		$regExBad = [
			#'mozilla'		=> '/(?<name>Mozilla)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			#'applewebkit'	=> '/(?<name>AppleWebKit)\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+))/',
			#'bad'			=> '/(?<name>[A-Za-z0-9\-\s]+)(\/(?<version>(?<major>[0-9]+)\.(?<minor>[0-9]+)))?/',
		];

	function __construct(string $userAgent = null) {
		if (is_null($userAgent)) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
		}
		$this->agent = (object) [
			'desktop' => [],
			'mobile' => [],
			'bot' => [],
			'app' => [],
			'cmd' => [],
			'bad' => [],
		];
		$this->detect($userAgent);
	}

	function reset() {
		$this->userAgent = null;
		$this->agent = (object) [
			'desktop' => [],
			'mobile' => [],
			'bot' => [],
			'app' => [],
			'cmd' => [],
			'bad' => [],
		];
	}
	function parse(string $userAgent = null) {
		return $this->detect($userAgent);
	}
	function detect(string $userAgent = null) {
		$this->reset();
		$this->userAgent = $userAgent;
		if (!$this->isNull()) {
			foreach (self::$regExDesktop as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->desktop[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
			foreach (self::$regExMobile as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->mobile[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
			foreach (self::$regExBot as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->bot[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
			foreach (self::$regExApp as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->app[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
			foreach (self::$regExCMD as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->cmd[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
			foreach (self::$regExBad as $name => $regex) {
				if ($match = $this->match($regex)) {
					$this->agent->bad[$name] = (object) [
						'name' => isset($match['name']) ? $match['name'] : '?',
						'version' => isset($match['version']) ? $match['version'] : null,
						'major' => isset($match['major']) ? (int) $match['major'] : null,
						'minor' => isset($match['minor']) ? (int) $match['minor'] : null,
					];
				}
			}
		}
		return $this;
	}
	function match($regex) {
		if (is_null($regex)) return;
		try {
			if (is_array($regex)) {
				foreach ($regex as $r) {
					$match = $this->match($r);
					if ($match) return $match;
				}
				return;
			}
			#echo 'regex: '.$regex.PHP_EOL;
			if (preg_match($regex, $this->userAgent, $match)) {
				#print_r($match);
				return $match;
			}
		}
		catch (\Exception $e) {
			#echo $e->getCode().':'.$e->getMessage();
		}
	}
	function isNull() {
		return is_null($this->userAgent);
	}
	function isBlank() {
		return !is_null($this->userAgent) && empty($this->userAgent);
	}
	function isDesktop() {
		return count($this->agent->desktop) ? !$this->isMobile() : false;
	}
	function isMobile() {
		return count($this->agent->mobile) ? true : false;
	}
	function isBot() {
		return count($this->agent->bot) ? true : false;
	}
	function isApp() {
		return count($this->agent->app) ? true : false;
	}
	function isCMD() {
		return count($this->agent->cmd) ? true : false;
	}
	function isBad() {
		return count($this->agent->bad) ? true : false;
	}
	function isUnknown() {
		return (
			!$this->isDesktop() &&
			!$this->isMobile() &&
			!$this->isBot() &&
			!$this->isApp() &&
			!$this->isCMD() &&
			!$this->isBad()
		);
	}
	function isChrome() {
		return (
			isset($this->agent->desktop['chrome'])
			&& !isset($this->agent->desktop['edge'])
		);
	}
	function isChromium() {
		return isset($this->agent->desktop['chrome']) || isset($this->agent->mobile['chrome']);
	}
	function isEdge() {
		if (isset($this->agent->desktop['edge+'])) {
			return (
				isset($this->agent->desktop['chrome'])
				&& isset($this->agent->desktop['edge+'])
			);
		}
		return (
			isset($this->agent->desktop['edge'])
		);
	}
	function isFirefox() {
		return (
			isset($this->agent->desktop['firefox'])
		);
	}
	function isInternetExplorer() {
		return (
			isset($this->agent->desktop['ie'])
			|| isset($this->agent->desktop['ie+'])
		);
	}
	function isOpera() {
		return (
			isset($this->agent->desktop['opera'])
			|| isset($this->agent->desktop['opera+'])
		);
	}
	function isSafari() {
		return (
			isset($this->agent->desktop['safari'])
			&& !isset($this->agent->desktop['chrome'])
		);
	}
	function isBing() {
		return isset($this->agent->bot['bing']);
	}
	function isDuckDuckGo() {
		return isset($this->agent->bot['duckduckgo']);
	}
	function isFacebook() {
		return isset($this->agent->bot['facebook']);
	}
	function isGoogle() {
		return isset($this->agent->bot['google']);
	}
	function isTumblr() {
		return isset($this->agent->bot['tumblr']);
	}
	function isTwitter() {
		return isset($this->agent->bot['twitter']);
	}
	function isSearchEngine() {
		return (
			$this->isGoogle()
			|| $this->isBing()
			|| $this->isDuckDuckGo()
		);
	}
	function isSocialNetwork() {
		return (
			$this->isFacebook()
			|| $this->isTwitter()
			|| $this->isTumblr()
		);
	}
	function browser() {
		if ($this->isSearchEngine()) {
			if ($this->isGoogle()) return 'Google';
			if ($this->isBing()) return 'Bing';
			if ($this->isDuckDuckGo()) return 'DuckDuckGo';
		}
		if ($this->isSocialNetwork()) {
			if ($this->isFacebook()) return 'Facebook';
			if ($this->isTwitter()) return 'Twitter';
			if ($this->isTumblr()) return 'Tumblr';
		}
		if ($this->isMobile()) {
			if ($this->isEdge()) return 'Edge Mobile';
			if ($this->isFirefox()) return 'Firefox Mobile';
			if ($this->isSafari()) return 'Safari Mobile';
			if ($this->isOpera()) return 'Opera Mobile';
			if ($this->isChrome()) return 'Chrome Mobile';
			return 'Mobile';
		}
		if ($this->isEdge()) return 'Edge';
		if ($this->isInternetExplorer()) return 'Internet Explorer';
		if ($this->isFirefox()) return 'Firefox';
		if ($this->isSafari()) return 'Safari';
		if ($this->isOpera()) return 'Opera';
		if ($this->isChrome()) return 'Chrome';
	}
	function version() {
		if ($this->isMobile()) {
			if ($this->isEdge()) return $this->agent->mobile['edge']->version;
			if ($this->isFirefox()) return $this->agent->mobile['firefox']->version;
			if ($this->isSafari()) return $this->agent->mobile['safari']->version;
			if ($this->isOpera()) return $this->agent->mobile['opera']->version;
			if ($this->isChrome()) return $this->agent->mobile['chrome']->version;
			return '0.0';
		}
		if ($this->isDesktop()) {
			if ($this->isEdge()) return $this->agent->desktop['edge']->version;
			if ($this->isFirefox()) return $this->agent->desktop['firefox']->version;
			if ($this->isSafari()) return $this->agent->desktop['safari']->version;
			if ($this->isOpera()) return $this->agent->desktop['opera']->version;
			if ($this->isChrome()) return $this->agent->desktop['chrome']->version;
			return '0.0';
		}
		return '0.0';
	}

	function __debugInfo() {
		return [
			'browser' => $this->agent,
			'isDesktop' => $this->isDesktop(),
			'isMobile' => $this->isMobile(),
			'isSearchEngine' => $this->isSearchEngine(),
			'isSocialNetwork' => $this->isSocialNetwork(),
			'isChrome' => $this->isChrome(),
			'isChromium' => $this->isChromium(),
			'isFirefox' => $this->isFirefox(),
			'isOpera' => $this->isOpera(),
			'isSafari' => $this->isSafari(),
			'isEdge' => $this->isEdge(),
			'isInternetExplorer' => $this->isInternetExplorer(),
			'isBot' => $this->isBot(),
			'isCMD' => $this->isCMD(),
			'isBad' => $this->isBad(),
			'isUnknown' => $this->isUnknown(),
		];
	}
	function __toString() {
		return json_encode($this->__debugInfo(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	}
}
