<?php
namespace Bang;
use MaxMind;

class Geo {
	private static
		$country,
		$city,
		$asn;
	function __construct() {

	}
	function __destruct() {
		#return self::close();
	}
	static function close() {
		if (self::$country)	self::$country->close();
		if (self::$city)	self::$city->close();
		if (self::$asn)		self::$asn->close();
	}
	private static function _loadCountry() {
		if (self::$country) return true;
		try {
			self::$country = new MaxMind\Db\Reader(BANG_DATA.'/GeoLite2-Country.mmdb');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	private static function _loadCity() {
		if (self::$city) return true;
		self::$city = new MaxMind\Db\Reader(BANG_DATA.'/GeoLite2-City.mmdb');
	}
	private static function _loadASN() {
		if (self::$asn) return true;
		self::$asn = new MaxMind\Db\Reader(BANG_DATA.'/GeoLite2-ASN.mmdb');
	}
	static function country(string $ip):object {
		self::_loadCountry();
		$geo = self::$country->get($ip);
		if ($geo) {
			$cc = $geo['country'] ?? $geo['registered_country'] ?? ['iso_code' => '-', 'names' => ['en' => '-']];
			if (empty($cc)) {
				print_r($geo);
				die;
			}
			return (object) [
				'id' => $cc['iso_code'] ?? '-',
				'name' => $cc['names']['en'] ?? '-',
				'continent' => (object) [
					'id' => $geo['continent']['code'] ?? '-',
					'name' => $geo['continent']['names']['en'] ?? '-',
				],
			];
		}
		return (object) [
			'id' => null,
			'name' => null,
			'continent' => (object) [
				'id' => null,
				'name' => null,
			],
		];
	}
	static function city(string $ip):object {
		self::_loadCity();
		$geo = self::$city->get($ip);
		if ($geo) {
			return (object) [
				'id' => null,
				'name' => $geo['city']['names']['en'] ?? '-',
				'country' => (object) [
					'id' => $geo['country']['iso_code'] ?? '-',
					'name' => $geo['country']['names']['en'] ?? '-',
					'continent' => (object) [
						'id' => $geo['continent']['code'] ?? '-',
						'name' => $geo['continent']['names']['en'] ?? '-',
					],
				],
				'location' => (object) [
					'longitude' => $geo['location']['longitude'] ?? '-',
					'latitude' => $geo['location']['latitude'] ?? '-',
					'radius' => $geo['location']['accuracy_radius'] ?? '-',
					'timezone' => $geo['location']['time_zone'] ?? '-',
				],
			];
		}
		return (object) [
			'id' => null,
			'name' => null,
			'country' => (object) [
				'id' => null,
				'name' => null,
				'continent' => (object) [
					'id' => null,
					'name' => null
				],
			],
			'location' => (object) [
				'longitude' => null,
				'latitude' => null,
				'radius' => null,
				'timezone' => null
			],
		];
	}
	static function asn(string $ip):object {
		self::_loadASN();
		$geo = self::$asn->get($ip);
		if ($geo) {
			return (object) [
				'id' => $geo['autonomous_system_number'],
				'name' => $geo['autonomous_system_organization'] ?? 'ASN #'.$geo['autonomous_system_number'], ##
			];
		}
		return (object) [
			'id' => null,
			'name' => null,
		];
	}
}

