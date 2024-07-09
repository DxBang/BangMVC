<?php
namespace Bang;

final class Core {
	static
		$pdo,
		$user,
		$cache,
		$minify,
		$config,
		$visitor;
	protected static
		$url,
		$urn,
		$uri,
		$domain,
		$host,
		$sub,
		$query,
		$route,
		$filter,
		$marks = [],
		$instance,
		$instances = [];

	function __construct(&$config) {
		self::mark('Bang\Core->__construct()');
		if (self::$instance) {
			die('Only ONE Core instance is allowed');
		}
		self::$instance = true;
		if (!self::isCLI())
			header('Server: Bang!', true);
		if (is_array($config)) {
			$config = Format::object($config);
		}
		self::$config = new Config($config);
		unset($config);
		self::session_start();
		if (($c = Config::get('useCache')) === true) {
			self::$cache = new Cache();
		}
		if ($c = Config::get('headers')) {
			self::headers($c);
		}
		self::_setupURL();
		self::mark('Bang\Core->__construct()/done');
	}
	static private function session_start() {
		self::mark('Bang\Core->session_start()');
		if (self::isCLI()) return;
		$config = Config::get('session');
		if (is_null($config)) return;
		session_start((array) $config);
		self::$visitor = new Visitor();
		if (isset($_COOKIE[session_name()])) {
			if (!isset($_SESSION['expire'])) {
				$_SESSION['expire'] = time() + ($config->cookie_lifetime / 2);
			}
			else if ($_SESSION['expire'] <= time()) {
				self::session_renew();
			}
		}
	}
	static function session_renew(object $config = null) {
		if (is_null($config)) {
			$config = Config::get('session');
			if (is_null($config)) return;
		}
		session_regenerate_id(true);
		$_SESSION['expire'] = time() + ($config->cookie_lifetime / 2);
	}
	static function nonce() {
		return \Bang\Security\CSP::nonce();
		#return Config::has('nonce') ? Config::get('nonce') : Config::set('nonce', self::keygen(128));
	}
	static function nonceTag(bool $space = false) {
		return \Bang\Security\CSP::nonceTag($space);
		#return ($space ? ' ' : '').'nonce="'.Config::get('nonce').'"';
	}
	static function sha256(string $file) {
		throw new Exception('Core::sha256 file is deprecated');
		if (!file_exists($file)) return;
		return 'sha256-'.hash_file(
			'sha256',
			$file,
			true
		);
	}
	static function config(object &$config = null) {
		return Config::install($config);
	}
	static function set(string $k, $v, $a = null) {
		return Config::set($k, $v, $a);
	}
	static function get(string $k, $a = null) {
		return Config::get($k, $a);
	}
	static function unset(string $k, $a = null) {
		return Config::unset($k, $a);
	}
	static function has(string $k, $a = null) {
		return Config::has($k, $a);
	}
	static function isset(string $k, $a = null) {
		return self::has($k, $a);
	}
	static function mark(string $mark) {
		if (empty(self::$marks) && !empty($_SERVER['REQUEST_TIME_FLOAT'])) {
			self::$marks[] = (object) [
				'mark' => 'pre',
				'microtime' => (float) $_SERVER['REQUEST_TIME_FLOAT'],
				'runtime' => (float) 0,
				'spent' => (float) 0,
				'usage_int' => (int) !empty($_SERVER['MEMORY_GET_USAGE']) ? $_SERVER['MEMORY_GET_USAGE'] : 0,
				'peak_usage_int' => (int) !empty($_SERVER['MEMORY_GET_PEAK_USAGE']) ? $_SERVER['MEMORY_GET_PEAK_USAGE'] : 0,
				'usage' => (string) '',
				'peak_usage' => (string) '',
				'usage_increased' => (string) '',
			];
		}
		if (defined('BANG_DEV_MARKS')) {
			self::$marks[] = (object) [
				'mark' => $mark,
				'microtime' => (float) microtime(1),
				'runtime' => (float) 0,
				'spent' => (float) 0,
				'usage_int' => (int) memory_get_usage(),
				'peak_usage_int' => (int) memory_get_peak_usage(),
				'usage' => (string) '',
				'peak_usage' => (string) '',
				'usage_increased' => (string) '',
			];
		}
	}
	static function marks() {
		if (!defined('BANG_DEV_MARKS') && !Config::get('debug')) return;
		$prev = (object) [
			'microtime' => (float) $_SERVER['REQUEST_TIME_FLOAT'],
			'usage_int' => (int) 0,
			'peak_usage_int' => (int) 0,
		];
		foreach (self::$marks as &$mark) {
			$mark->runtime = (float) $mark->microtime - self::$marks[0]->microtime;
			$mark->spent = (float) $mark->microtime - $prev->microtime;
			$mark->usage = Format::humanDataSize($mark->usage_int);
			$mark->peak_usage = Format::humanDataSize($mark->peak_usage_int);
			$mark->usage_increased = Format::humanDataSize($mark->usage_int - $prev->usage_int);
			$prev = $mark;
		}
		return self::$marks;
	}
	static function keygen(int $length=6) {
		$chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
		$max = count($chars) - 1;
		$r = $chars[rand(0, $max - 10)];
		for($i=strlen($r); $i<$length; $i++) {
			$r .= $chars[rand(0,$max)];
		}
		return $r;
	}
	static function headers(object $a, bool $overwrite = true) {
		if (php_sapi_name() == 'cli') return;
		foreach ($a as $k => $v) {
			header($k.': '.$v, $overwrite);
		}
	}
	static function protocol():string {
		return isset($_SERVER['HTTPS']) ? 'https' : 'http';
	}
	static function SSL():bool {
		return (self::protocol() == 'https');
	}
	static function host():string {
		return $_SERVER['HTTP_HOST'];
	}
	static function isLocalhost():bool {
		return preg_match('/localhost$/', self::host());
	}
	static function domain():string {
		if ($_SERVER['HTTP_HOST'] == 'localhost') self::$domain = $_SERVER['HTTP_HOST'];
		if (self::$domain) return self::$domain;
		$e = explode('.', $_SERVER['HTTP_HOST']);
		$l = count($e);
		if ($l == 2) {
			self::$domain = $_SERVER['HTTP_HOST'];
		}
		else if ($l >= 3) {
			/* expand this later to support co.uk etc */
			self::$domain = implode('.', array_slice($e, -2));
		}
		if ($e[0] == 'www') {
			array_shift($e);
			header('Location: '.self::protocol().'://'.implode('.', $e).$_SERVER['REQUEST_URI'], true, 301);
			exit;
		}
		return self::$domain;
	}
	static function sub() {
		return self::$sub;
	}
	static function subDomain():string {
		return preg_replace('/\.?'.addcslashes(self::domain(), '.').'$/', '', self::host());
	}
	static function cookieDomain():string {
		return '.'.self::domain();
	}
	static function site():string {
		return self::protocol().'://'.self::host();
	}
	static function mainSite():string {
		return self::protocol().'://'.self::domain();
	}
	static function URL():string {
		return self::site().self::URN();
	}
	static function URN():string {
		return self::$urn;
	}
	static function URI(int $level = null) {
		if (!is_null($level)) {
			if ($level == -1) {
				return self::$uri;
			}
			return isset(self::$uri[$level]) ? self::$uri[$level] : null;
		}
		return self::$urn;
	}
	static function isURL(string $url) {
		return Validate::url($url);
	#	return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED);
	}
	static function isHost(string $url) {
		if (!self::isURL($url)) return;

	}
	static function isDomain(string $url) {
		if (!self::isURL($url)) return;

	}
	private static function _setupURL() {
		self::$urn = isset($_SERVER['REQUEST_URI'])
			? '/'.trim(explode('?', rawurldecode($_SERVER['REQUEST_URI']), 2)[0], "\x00..\x20\/")
			: '/';
		self::$uri = preg_split('/\//', trim(self::$urn, '/'));
		if (count($_GET)) {
			self::$query = $_GET;
		}
		if ($_SERVER['HTTP_HOST'] == 'localhost') {
			self::$host
				= self::$domain
				= $_SERVER['HTTP_HOST'];
		}
		else if (empty(self::$config->domains)) {

		}
		else {
			self::$host = $_SERVER['HTTP_HOST'];
			foreach (self::$config->domains as $domain) {
				if (substr(self::$host, strlen($domain) * -1) == $domain) {
					self::$domain = $domain;
					break;
				}
			}
			preg_match('/((?<sub>.+)\.)'.addcslashes(self::$domain, '.-_').'$/', self::$host, $m);
			self::$sub = $m['sub'] ?? null;
		}
	}
	static function parseURL(string $url, int $component = -1) {
		$r = parse_url($url, $component);
		if (empty($r)) return false;
		$r['host'] = $r['host'] ? Format::toLower($r['host']) : '';
		#$r['idn'] = idn_to_utf8($r['host']);
		$r['domain'] = str_replace('www.', '', $r['host']);
		$r['url'] = $r['scheme'].'://'.$r['host'];
		$r['domain_path'] = $r['domain'];
		if (isset($r['path']) && strlen($r['path']) > 1) {
			$r['domain_path'] .= $r['path'];
			$r['url'] .= $r['path'];
			$r['full'] = $r['url'];
			if (isset($r['query'])) {
				$r['full'] .= '?'.$r['query'];
			}
		}
		return (object) $r;
	}

	static function isAPI():bool {
		return isset(self::$config->isAPI) ? self::$config->isAPI : false;
	}
	static function isWebsite():bool {
		return isset(self::$config->isWebsite) ? self::$config->isWebsite : false;
	}
	static function isSpecial():bool {
		return isset(self::$config->isSpecial) ? self::$config->isSpecial : false;
	}
	static function isConsole():bool {
		return isset(self::$config->isConsole) ? self::$config->isConsole : false;
	}
	static function isCLI():bool {
		return (php_sapi_name() === 'cli') ? true : false;
	}
	static function IPRestrictions(string $ip, array $ips) {

	}
	static function isIPAllowed($ip, $ips) {
		return true; # DEVELOP
	}
	static function redirect(string $url, int $httpCode = 302) {
		header('Location: '.$url, true, $httpCode);
		exit;
	}
	static function refresh(int $seconds, string $url = null) {
		if (is_null($url)) {
			$url = self::URN();
		}
		header('Refresh: '.$seconds.'; url='.$url, true);
	}
	static function routes() {
		return Config::get('routes');
	}
	static function routeGroupAccess(array $groups = null) {
		if (empty($groups)) return true;
		/*
		echo 'empty: '.empty($groups).PHP_EOL;
		echo '0 in groups: '.(in_array(0, $groups, true)).PHP_EOL;
		echo '-2: '.(!Visitor::$flag->isMember() && in_array(-1, $groups, true)).PHP_EOL;
		echo '-1: '.(Visitor::$flag->isMember() && in_array(-2, $groups, true)).PHP_EOL;
		echo 'isMember: '.(Visitor::$flag->isMember() && in_array('member', $groups, true)).PHP_EOL;
		echo 'isCreator: '.(Visitor::$flag->isCreator() && in_array('creator', $groups, true)).PHP_EOL;
		echo 'isArtist: '.(Visitor::$flag->isArtist() && in_array('artist', $groups, true)).PHP_EOL;
		echo 'isFan: '.(Visitor::$flag->isFan() && in_array('fan', $groups, true)).PHP_EOL;
		echo 'isCustomer: '.(Visitor::$flag->isCustomer() && in_array('customer', $groups, true)).PHP_EOL;
		echo 'isClient: '.(Visitor::$flag->isClient() && in_array('client', $groups, true)).PHP_EOL;
		echo 'isSpecial: '.(Visitor::$flag->isSpecial() && in_array('special', $groups)).PHP_EOL;
		echo 'isVIP: '.(Visitor::$flag->isVIP() && in_array('vip', $groups, true)).PHP_EOL;
		echo 'isPOI: '.(Visitor::$flag->isPOI() && in_array('poi', $groups, true)).PHP_EOL;
		echo 'isOwner: '.(Visitor::$flag->isOwner() && in_array('owner', $groups, true)).PHP_EOL;
		echo 'isModerator: '.(Visitor::$flag->isModerator() && in_array('moderator', $groups, true)).PHP_EOL;
		echo 'isAdministrator: '.(Visitor::$flag->isAdministrator() && in_array('administrator', $groups, true)).PHP_EOL;
		echo 'isBot: '.(Visitor::$flag->isBot() && in_array('bot', $groups, true)).PHP_EOL;
		echo 'isBanned: '.(Visitor::$flag->isBanned() && in_array('banned', $groups, true)).PHP_EOL;
		echo 'id: '.(Visitor::$group->id() && in_array(Visitor::$group->id(), $groups, true)).PHP_EOL;
		*/
		return (empty($groups)
			|| (in_array(0, $groups, true)) # any user
			|| (!Visitor::$flag->isMember() && in_array(-1, $groups, true)) # only for users NOT logged in (e.g. /login)
			|| (Visitor::$flag->isMember() && in_array(-2, $groups, true)) # only for users logged in (e.g. /logout)
			|| (Visitor::$flag->isMember() && in_array('member', $groups, true))
			|| (Visitor::$flag->isCreator() && in_array('creator', $groups, true))
			|| (Visitor::$flag->isArtist() && in_array('artist', $groups, true))
			|| (Visitor::$flag->isActor() && in_array('actor', $groups, true))
			|| (Visitor::$flag->isWriter() && in_array('writer', $groups, true))
			|| (Visitor::$flag->isEditor() && in_array('editor', $groups, true))
			|| (Visitor::$flag->isFan() && in_array('fan', $groups, true))
			|| (Visitor::$flag->isCustomer() && in_array('customer', $groups, true))
			|| (Visitor::$flag->isClient() && in_array('client', $groups, true))
			|| (Visitor::$flag->isSpecial() && in_array('special', $groups))
			|| (Visitor::$flag->isVIP() && in_array('vip', $groups, true))
			|| (Visitor::$flag->isPOI() && in_array('poi', $groups, true))
			|| (Visitor::$flag->isStaff() && in_array('staff', $groups, true))
			|| (Visitor::$flag->isModerator() && in_array('moderator', $groups, true))
			|| (Visitor::$flag->isAdministrator() && in_array('administrator', $groups, true))
			|| (Visitor::$flag->isOwner() && in_array('owner', $groups, true))
			|| (Visitor::$flag->isGuest() && in_array('guest', $groups, true))
			|| (Visitor::$flag->isBot() && in_array('bot', $groups, true))
			|| (Visitor::$flag->isSuspended() && in_array('suspended', $groups, true))
			|| (Visitor::$flag->isBanned() && in_array('banned', $groups, true))
			|| (Visitor::$group->id() && in_array(Visitor::$group->id(), $groups, true)) # specific user groups
		);
	}
	static function route(string $urn = null) {
		self::mark('Bang\Core->route()');
		if (is_null($urn)) {
			$urn = self::$urn;
		}
		foreach (Config::get('routes') as $regex => $route) {
			if (preg_match($regex, $urn)) {
				if (self::routeGroupAccess($route->groups)) {
					if (!empty($route->ips)) {
						if (!self::isIPAllowed(Visitor::$ip, $route->ips)) {
							return false;
						}
					}
					self::$route = $route;
					self::set('route', $route);
					return $route;
				}
			}
		}
	}
	static function filter(object $route = null) {
		if (!$route) {
			$route = self::$route;
		}
		$f = 0;
		if (is_countable($_GET)) {
			$f += self::_filtering($_GET, $route->get ?? (object) []);
		}
		if (is_countable($_POST) && !empty($route->post)) {
			$f += self::_filtering($_POST, $route->post);
		}
		if (is_countable($_FILES) && !empty($route->files)) {
			$f += self::_filtering($_FILES, $route->files);
		}
		return (
			$f == (
				(is_countable($_GET) ? count($_GET) : 0) +
				(is_countable($_POST) ? count($_POST) : 0) +
				(is_countable($_FILES) ? count($_FILES) : 0)
			)
		);
	}

	private static function _filtering(array &$inputs, object $filters) {
		self::_unfiltered($inputs, $filters);
		$f = 0;
		foreach ($filters as $k => $filter) {
			$filter = explode(',', $filter);
			if (isset($filter[0]) && isset($inputs[$k])) {
				switch ($filter[0]) {
					case 'float':
						$inputs[$k] = (float) filter_var(
							$inputs[$k],
							FILTER_VALIDATE_FLOAT,
							[
								'options' => [
									'min_range' => (float) !empty($filter[1]) ? $filter[1] : 0,
									'max_range' => (float) !empty($filter[2]) ? $filter[2] : PHP_INT_MAX,
									'default' => (float) !empty($filter[3]) ? $filter[3] : 1.0,
									'decimal' => '.',
								],
								'flags' => FILTER_FLAG_ALLOW_FRACTION
							]
						);
						$f++;
					break;
					case 'int':
						$inputs[$k] = (int) round(filter_var(
							$inputs[$k],
							FILTER_VALIDATE_INT,
							[
								'options' => [
									'min_range' => (int) !empty($filter[1]) ? $filter[1] : 0,
									'max_range' => (int) !empty($filter[2]) ? $filter[2] : PHP_INT_MAX,
									'default' => (int) !empty($filter[3]) ? $filter[3] : 1,
								],
								'flags' => FILTER_FLAG_ALLOW_FRACTION
							]
						));
						$f++;
					break;
					case 'ascii':
						if (!preg_match('/^[0-9a-zA-Z\-_\.,\s]$/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'utf8':
					case 'unicode':
						$inputs[$k] = (string) $inputs[$k];
						/*
						$inputs[$k] = (string) trim(filter_var(
							preg_replace('/[\t\n\r\s]+/S', ' ', $inputs[$k]),
							FILTER_UNSAFE_RAW,
							FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
						));
						*/
						$f++;
					break;
					case 'string':
						$inputs[$k] = (string) trim(filter_var(
							preg_replace('/[\t\n\r\s]+/S', ' ', $inputs[$k]),
							FILTER_UNSAFE_RAW,
							FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK
						));
						$f++;
					break;
					case 'text':
						$inputs[$k] = (string) trim(strip_tags($inputs[$k]));
						$f++;
					break;
					case 'html':
						$inputs[$k] = (string) trim(filter_var(
							strip_tags($inputs[$k], isset($filter[3]) ? $filter[3] : '<b><i><u><p><strong><em><a><h1><h2><h3><h4><sup><sub><ul><ol><li>'),
							FILTER_UNSAFE_RAW,
							FILTER_FLAG_STRIP_BACKTICK
						));
						$f++;
					break;
					case 'hash':
						if (!preg_match('/^[0-9a-fA-F]{8,}$/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'sha1':
						if (!preg_match('/^[0-9a-fA-F]{40}$/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'md5':
						if (!preg_match('/^[0-9a-fA-F]{32}$/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'email':
						$inputs[$k] = (string) filter_var($inputs[$k], FILTER_SANITIZE_EMAIL);
						if (!filter_var($inputs[$k], FILTER_VALIDATE_EMAIL)) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'url':
						$inputs[$k] = (string) filter_var($inputs[$k], FILTER_SANITIZE_URL);
						if (!filter_var($inputs[$k], FILTER_VALIDATE_URL)) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'username':
						if (!preg_match('/'.self::get('username','regex').'/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'password':
						if (!preg_match('/'.self::get('password','regex').'/', $inputs[$k])) {
							$inputs[$k] = false;
						}
						$f++;
					break;
					case 'blob':
						$f++;
					break;
					case 'file':
						$f++;
					break;
					case 'array':
						if (is_array($inputs[$k])) {
							$f++;
						}
					break;
					case 'sarray':
					case 'csvarray':
						if (preg_match('/^\[(?<data>.+)\]$/', trim($inputs[$k]), $m)) {
							$inputs[$k] = str_getcsv($m['data']);
						}
						if (is_array($inputs[$k])) {
							$f++;
						}
					break;
					case 'csv':
						$inputs[$k] = str_getcsv($inputs[$k]);
						if (is_array($inputs[$k])) {
							$f++;
						}
					break;
					case 'json':
						if (preg_match('/^\{(.*)\}$/', trim($inputs[$k]), $m)) {
							$inputs[$k] = json_decode($inputs[$k]);
							if (is_object($inputs[$k])) {
								$f++;
							}
						}
						if (preg_match('/^\[(.*)\]$/', trim($inputs[$k]), $m)) {
							$inputs[$k] = json_decode($inputs[$k], true);
							if (is_array($inputs[$k])) {
								$f++;
							}
						}
					break;
					case 'jsonobject':
						if (preg_match('/^\{(.*)\}$/', trim($inputs[$k]), $m)) {
							$inputs[$k] = json_decode($inputs[$k]);
							if (is_object($inputs[$k])) {
								$f++;
							}
						}
					break;
					case 'jsonarray':
						if (preg_match('/^\[(.*)\]$/', trim($inputs[$k]), $m)) {
							$inputs[$k] = json_decode($inputs[$k], true);
							if (is_array($inputs[$k])) {
								$f++;
							}
						}
					break;
					case 'date':
						$f++;
					break;
					case 'time':
						$f++;
					break;
					case 'empty':
					case 'null':
						if (empty($inputs[$k])) {
							$f++;
						}
					break;
					default:
						$inputs[$k] = filter_var(
							$inputs[$k],
							FILTER_SANITIZE_ENCODED
						);
						$f++;
					break;
				}
			}
		}
		return $f;
	}
	private static function _unfiltered(array &$inputs, object $filters) {
		foreach ($inputs as $k => &$v) {
			if (!isset($filters->{$k})) {
				unset($inputs[$k]);
			}
		}
	}
	static function safeFilename(string $filename, bool $forceLower = true) {
		if ($forceLower) {
			$filename = strtolower($filename);
		}
		$filename = preg_replace('/[^\d\w\.\-\_\s\~\[\]\(\)]/', '', $filename);
		$filename = preg_replace('/[\s]+/', '-', $filename);
		$filename = preg_replace('/[\-\-]+/', '-', $filename);
		return $filename;
	}
	static function safePath(string $path, bool $forceLower = true) {
		$path = preg_replace('/([\\\\])+/', '/', $path);
		$path = explode('/', $path);
		foreach ($path as &$v) {
			self::safeFilename($v, $forceLower);
		}
		$path = implode('/', $path);
		return $path;
	}
	static function makeDir(string $dir, $mode = 0777) {
		return mkdir($dir, $mode, true);
	}
	static function dateFormat(string $date, string $time = null) {
		return Format::date($date, $time);
	}
	static function timeFormat(string $time) {
		return Format::time($time);
	}
	static function status(int $code) {
		http_response_code($code);
	}
	static function error(string $message = 'undefined error', int $code = 999, $debug = null) {
		self::status($code);
		if ($code >= 400) {
			if (self::isAPI()) {
				require_once(SITE_PRIVATE.'/views/error_api.php');
				die();
			}
			require_once(SITE_PRIVATE.'/views/error.php');
			die();
		}
	}
	static function exception(\Exception $e, bool $critical = false) {
		if (self::isWebsite()) {
			echo '<dl class="debug error">'
					.'<dt>code</dt>'
					.'<dt>'.$e->getCode().'</dt>'
					.'<dt>message</dt>'
					.'<dd>'.$e->getMessage().'</dd>'
					.(
						self::get('debug')
						? '<dt>file</dt><dd>'.$e->getFile().'</dd>'
							.'<dt>line</dt><dd>'.$e->getLine().'</dd>'
							.'<dt>trace</dt><dd>'.$e->getTraceAsString().'</dd>'
						: ''
					)
				.'</dl>';
			if ($critical) exit;
			return;
		}
		self::json([
			'success' => false,
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
			'errno' => $e->getCode(),
			'error' => $e->getMessage(),
			'debug' => (self::get('debug') ? [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTrace(),
			] : null)
		]);
		exit;
	}
	static function json($data) {
		if (php_sapi_name() == 'cli' && !self::isWebsite()) {
			header('Content-Type: application/json; charset='.Core::get('charset'));
		}
		echo json_encode($data, self::$config->jsonEncode);
	}
	static function debug() {
		echo 'debug';
		$config = self::$config::debug();
		$config->pdo
			= $config->auth
			= 'hidden';
		return [
			'url' => self::$url,
			'urn' => self::$urn,
			'uri' => self::$uri,
			'route' => self::$route,
			'query' => self::$query,
			'filter' => self::$filter,
			'user' => self::$user,
			'config' => $config,
			'pdo' => self::$pdo,
		];
	}
	function __debugInfo() {
		return self::debug();
	}

	function __toString() {
		return json_encode($this->__debugInfo(), JSON_ENCODE_SETTINGS);
	}
	function stats():object {
		return (object) [
			'memory' => memory_get_peak_usage(true),
			'files' => get_included_files()
		];
	}
}
