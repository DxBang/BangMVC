<?php
namespace Bang;

class Date {
	const
		SPLIT = '([\s\-\.\:\/])',
		TSPLIT = '([\s\-\.\:\/T])?',
		YEAR = '(?<year>[1-2][0-9]{3})',
		MONTH = '(?<month>[0-1]?[0-9])',
		DAY = '(?<day>[0-3]?[0-9])',
		HOUR = '(?<hour>[0-2]?[0-9])',
		MINUTE = '(?<minute>[0-5]?[0-9])',
		SECOND = '(?<second>[0-5]?[0-9])?',
		MSECOND = '(?<msecond>\.[0-9]{1,4})?',
		TIMEZONE = '(?<timezone>([+\-][0-1]?[0-9]([:\.][0-5][0-9])?)|Z|[a-zA-Z]{3})?',
		MERI = '(?<meri>[apAP][mM])?';

	static function Ymd($date) {
		if (preg_match('/^'
			.'(?<date>'.self::YEAR.self::SPLIT.self::MONTH.self::SPLIT.self::DAY.')'
			.'$/', $date, $m))
			return self::clean($m);
	}
	static function YmdHis(string $date) {
		if (preg_match('/^'
			.'(?<date>'.self::YEAR.self::SPLIT.self::MONTH.self::SPLIT.self::DAY.')'
			.self::TSPLIT
			.'(?<time>'.self::HOUR.self::SPLIT.self::MINUTE.self::SPLIT.self::SECOND.')?'
			.'$/', $date, $m))
			return self::clean($m);
		return self::dmYHis($date);
	}
	static function YmdHisX(string $date) {
		if (preg_match('/^'
			.'(?<date>'.self::YEAR.self::SPLIT.self::MONTH.self::SPLIT.self::DAY.')'
			.self::TSPLIT
			.'(?<time>'.self::HOUR.self::SPLIT.self::MINUTE.self::SPLIT.self::SECOND.')?'
			.self::SPLIT
			.self::MERI
			.self::SPLIT
			.self::TIMEZONE
			.'$/', $date, $m))
			return self::clean($m);
		return self::dmYHisX($date);
	}
	static function dmYHisX(string $date) {
		if (preg_match('/^'
			.'(?<date>'.self::DAY.self::SPLIT.self::MONTH.self::SPLIT.self::YEAR.')'
			.self::TSPLIT
			.'(?<time>'.self::HOUR.self::SPLIT.self::MINUTE.self::SPLIT.self::SECOND.')?'
			.self::SPLIT
			.self::MERI
			.self::SPLIT
			.self::TIMEZONE
			.'$/', $date, $m))
			return self::clean($m);
	}
	static function dmYHis(string $date) {
		if (preg_match('/^'
			.'(?<date>'.self::DAY.self::SPLIT.self::MONTH.self::SPLIT.self::YEAR.')'
			.self::TSPLIT
			.'(?<time>'.self::HOUR.self::SPLIT.self::MINUTE.self::SPLIT.self::SECOND.')?'
			.'$/', $date, $m))
			return self::clean($m);
	}
	static function dmY(string $date) {
		if (preg_match('/^'
			.'(?<date>'.self::DAY.self::SPLIT.self::MONTH.self::SPLIT.self::YEAR.')'
			.'$/', $date, $m))
			return self::clean($m);
	}
	static function parse(string $date, string $cc = null) {
		if (!empty($cc)) {
			switch ($cc) {
				case 'ro':
					return self::YmdHis($date);
				break;
				case 'fi': # d.m.yyyy 10:00:00
					return self::dmYHis($date);
				break;
				default:
					return self::guess($date);
				break;
			}
		}
		if ($guess = self::guess($date)) return $guess;
	}
	static function guess(string $date) {
		$len = strlen($date);
		if ($len >= 6 && $len <= 10) {
			if ($m = self::Ymd($date))
				return $m;
			if ($m = self::dmY($date))
				return $m;			
		}
		if ($len >= 12 && $len <= 19) {
			if ($m = self::YmdHis($date))
				return $m;
		}
		if ($len >= 14 && $len <= 28) {
			if ($m = self::YmdHisX($date))
				return $m;
		}
		return self::force($date);
	}
	static function force(string $date) {
		return self::YmdHisX(date('c'), strtotime($date));
	}
	static function clean($m) {
		$r = [];
		foreach ($m as $k => &$v) {
			if (is_int($k)) unset($m[$k]);
			if (empty($v)) unset($m[$k]);
			switch ($k) {
				case 'meri':
				case 'timezone':
					$v = strtoupper($v);
				break;
				default:
				break;
			}
		}
		if (!empty($m['date'])) {
			$m['date'] = $m['year'].'-'.$m['month'].'-'.$m['day'];
			$m['datetime'] = $m['date'];
		}
		if (!empty($m['time'])) {
			$m['time'] = $m['hour'].':'.$m['minute']
				.($m['second'] ? ':'.$m['second'] : '');
		}
		if (!empty($m['date']) && !empty($m['time'])) {
			$m['datetime'] .= ' '.$m['time'];
			if (!empty($m['meri'])) {
				$m['datetime'] .= ' '.$m['meri'];
			}
			if (!empty($m['timezone'])) {
				$m['datetime'] .= ' '.$m['timezone'];
			}
		}
		if (!empty($m['datetime'])) {
			$d = new \DateTime($m['datetime']);
			$m['iso'] = $d->format('c');
			$m['iso_date'] = $d->format('Y-m-d');
			$m['iso_time'] = $d->format('H:i:s');
			$m['iso_zone'] = $d->format('Z');
			$m['iso_timezone'] = $d->format('e');
			$d->setTimezone(new \DateTimeZone('Zulu'));
			$m['zulu'] = $d->format('c');
		}
		return (object) $m;
	}
	static function validate($date, $format = 'Y-m-d H:i:s') {
		$d = \DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
}
