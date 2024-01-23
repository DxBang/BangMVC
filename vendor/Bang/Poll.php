<?php
namespace Bang;

class Poll {
	static
		$id,
		$question = '',
		$description,
		$results = [],
		$ids = [],
		$total = 0,
		$expireDate,
		$users = [],
		$history = [],
		$showUsers = false,
		$makeHistory = false;

	function __construct(int $question_id = null, string $question = null, array $answers = null, array $votes = null, bool $history = false) {
		if ($question_id && $question) {
			$this->question($question_id, $question);
		}
		if ($answers) {
			$this->answers($answers);
		}
		if ($votes) {
			$this->votes($votes);
		}
		if ($history) {
			self::makeHistory();
		}
	}
	static function reset() {
		self::$id = '';
		self::$question = '';
		self::$description = '';
		self::$ids = [];
		self::$results = [];
		self::$total = 0;
	}
	static function makeHistory() {
		self::$makeHistory = true;
	}
	static function noHistory() {
		self::$makeHistory = false;
	}
	static function showUsers() {
		self::$showUsers = true;
	}
	static function hideUsers() {
		self::$showUsers = false;
	}
	function question(int $question_id, string $question, string $description = null) {
		self::$id = $question_id;
		self::$question = $question;
		self::$description = $description;
		return $this;
	}
	function answers(array $answers) {
		foreach ($answers as $k => $v) {
			self::answer($k, $v);
		}
		return $this;
	}
	function votes(array $votes) {
		foreach ($votes as $k => $v) {
			self::vote($k, $v);
		}
		return $this;
	}
	function percentage() {
		foreach (self::$results as $k => $v) {
			if ($v['votes']) {
				self::$results[$k]['percentage'] = (float) ($v['votes'] / (self::$total)) * 100;
			}
		}
		return $this;
	}
	function sort() {
		usort(self::$results, function ($a, $b) {
			if ($a['votes'] == $b['votes']) {
				return 0;
			}
			return ($a['votes'] < $b['votes']) ? 1 : -1;
		});
		return $this;
	}
	static function average($rounded = false) {
		if ($rounded)
			return round(array_sum(self::$history) / count(self::$history)) + 1;
		return array_sum(self::$history) / count(self::$history) + 1;
	}
	static function answer(int $answer_id, string $answer, string $description = null) {
		self::$ids[$answer_id] = count(self::$results);
		self::$results[] = [
			'id' => $answer_id,
			'answer' => $answer,
			'description' => $description,
			'votes' => 0,
			'percentage' => 0.0
		];
	}
	static function shuffleHistory() {
		shuffle(self::$history);
	}
	static function reverseHistory() {
		self::$history = array_reverse(self::$history);
	}
	static function addHistory(int $answer_id) {
		self::$history[] = $answer_id;
	}
	static function vote(int $client_id, int $answer_id) {
		self::$users[$client_id] = $answer_id;
		self::$results[self::$ids[$answer_id]]['votes']++;
		self::$total++;
		if (self::$makeHistory) {
			self::addHistory($answer_id);
		}
	}
	static function answerVotes(int $answer_id) {
		if (isset(self::$results[self::$ids[$answer_id]])) {
			return (int) self::$results[self::$ids[$answer_id]]['votes'];
		}
	}
	static function answerPercentage(int $answer_id) {
		if (isset(self::$results[self::$ids[$answer_id]])) {
			return (float) (self::$results[self::$ids[$answer_id]]['votes'] / (self::$total)) * 100;
		}
	}
	static function expiresIn(string $date = null) {
		if (is_null($date)) return '<i>never</i>';
		$expire = new \DateTime($date);
		$remain = $expire->diff(new \DateTime());
		if (!$remain->invert) return;
		$months = '';
		if ($remain->m) $months .= $remain->m . ' months, ';
		if ($remain->d) return $months.$remain->d . ' days';
		if ($remain->h) return $remain->h . ' hours';
		if ($remain->i) return $remain->i . ' minutes';
		if ($remain->s) return $remain->s . ' seconds';
	}
	static function output() {
		return array(
			'question' => self::$question,
			'description' => self::$description,
			'results' => self::$results,
			'total' => self::$total,
			'users' => self::$showUsers ? self::$users : false,
		);
	}
	function __debuginfo() {
		return self::output();
	}
	function __tostring() {
		return json_encode($this->__debugInfo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
	}
}
