<?php
namespace Bang\Network;

class DNS {
	const
		IP = DNS_A | DNS_AAAA,
		IPV4 = DNS_A,
		IPV6 = DNS_AAAA,
		A = DNS_A,
		A6 = DNS_A6,
		AAAA = DNS_AAAA,
		CNAME = DNS_CNAME,
		HINFO = DNS_HINFO,
		MX = DNS_MX,
		NAPTR = DNS_NAPTR,
		NS = DNS_NS,
		PTR = DNS_PTR,
		SOA = DNS_SOA,
		SRV = DNS_SRV,
		TXT = DNS_TXT,
		ANY = DNS_A | DNS_AAAA | DNS_A6 | DNS_CNAME | DNS_MX | DNS_NS,
		ALL = DNS_A | DNS_AAAA | DNS_A6 | DNS_CNAME | DNS_MX | DNS_NS | DNS_SOA | DNS_TXT | DNS_SRV | DNS_HINFO | DNS_PTR | DNS_NAPTR,
		CAA = DNS_CAA;
	static public
		$auth = [],
		$addtl = [];
	static function records(string $host, int $type = self::IPV4) {
		$records = dns_get_record(
			$host,
			$type
		);
		if (!$records) {
			throw new \Exception('cannot capture dns record on host: "'.$host.'"');
		}
		return self::parse(
			$records
		);
	}
	static function raw(string $host, int $id) {
		return dns_get_record(
			$host,
			$id,
			self::$auth,
			self::$addtl,
			true
		);
	}
	static private function parse($records) {
		$r = (object) [];
		foreach ($records as $dns) {
			switch(strtolower($dns['type'])) {
				case 'a':
					$r->a[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['ip'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
					];
				break;
				case 'aaaa':
					$r->aaaa[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['ipv6'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
					];
				break;
				case 'cname':
					$r->cname[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['target'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
					];
				break;
				case 'soa':
					$soa = '@'.$dns['rname'];
					if (preg_match('/^(?<name>[a-z0-9\-\.]+)\.(?<domain>[^co][a-z][a-z0-9\-]+\.(?<tld>(co\.)?[a-z]{2,}))$/', $dns['rname'], $m)) {
						$soa = $m['name'].'@'.$m['domain'];
					}
					$r->soa[] = (object) [
						#'host' => $dns['host'],
						'value' => $soa,
						'class' => $dns['class'],
						'mname' => $dns['mname'],
						'rname' => $dns['rname'],
						'serial' => (int) $dns['serial'],
						'refresh' => (int) $dns['refresh'],
						'expire' => (int) $dns['expire'],
						'minimum-ttl' => (int) $dns['minimum-ttl'],
						'ttl' => (int) $dns['ttl'],
					];
				break;
				case 'mx':
					$r->mx[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['target'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
						'pri' => (int) $dns['pri'],
					];
				break;
				case 'ns':
					$r->ns[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['target'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
					];
				break;
				case 'txt':
					$r->txt[] = (object) [
						#'host' => $dns['host'],
						'value' => $dns['txt'],
						'class' => $dns['class'],
						'ttl' => (int) $dns['ttl'],
						'entries' => $dns['entries'],
					];
				break;
				default:
					$r->unknown[] = (object) $dns;
					print_r($dns);
			}
		}
		return $r;
	}
	static function ns(string $host) {
		return self::records($host, self::NS);
	}
	static function mx(string $host) {
		return self::records($host, self::MX);
	}
	static function cname(string $host) {
		return self::records($host, self::CNAME);
	}
	static function ip(string $host) {
		return self::records($host, self::IP);
	}
	static function ipv4(string $host) {
		return self::records($host, self::IPV4);
	}
	static function ipv6(string $host) {
		return self::records($host, self::IPV6);
	}
	static function soa(string $host) {
		return self::records($host, self::SOA);
	}
	static function txt(string $host) {
		return self::records($host, self::TXT);
	}
	static function srv(string $host) {
		return self::records($host, self::SRV);
	}
	static function all(string $host) {
		return self::records($host, self::ALL);
	}
	static function any(string $host) {
		return self::records($host, self::ANY);
	}
}
