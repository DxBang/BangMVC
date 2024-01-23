<?php
namespace Bang\Auth\Discord;
use Bang\Auth\Discord;
use Bang\Core;

class Bot {
	protected
		$id,
		$http,
		$storage = '/tmp',
		$scope = 'bot identify guilds guilds.join',
		$guildID,
		$urlAPI = 'https://discord.com/api/v9',
		$uriAuthorize = '/oauth2/authorize',
		$uriToken = '/oauth2/token';
	
	public function __construct(object $oauth) {
		if (empty($oauth->botToken)) throw new \Error('Missing botToken');
		$this->http = new \Bang\Chain\HTTP();
		$this->http->userAgent('DiscordBot ('.Core::mainSite().', '.constant('BANG_VERSION').')');
		$this->botToken = $oauth->botToken;
		$this->storage = $oauth->storage ?? $this->storage;
		$this->guildID = $oauth->guildID;
	}

	public function botToken() {
		return $this->botToken;
	}
	protected function get(string $api) {
		#echo 'get('.$api.')'.PHP_EOL;
		if (empty($this->botToken())) throw new \Exception('missing access token', 401);
		$j = $this->http
			->reset()
			->url($this->urlAPI)
			->headers(
				[
					'Authorization: Bot '.$this->botToken(),
				]
			)
			->get($api)
			->json();
		if (isset($j->code) & !empty($j->message)) {
			throw new \Error($j->message.' ('.$api.')', 401);
		}
		return $j;
	}
	protected function post(string $api, object $data) {
		#echo 'post('.$api.')'.PHP_EOL;
		if (empty($this->botToken())) throw new \Exception('missing access token', 401);
		$j = $this->http
			->reset()
			->url($this->urlAPI)
			->headers(
				[
					'Authorization: Bot '.$this->botToken(),
				]
			)
			->post($api, $data)
			->json();
		if (isset($j->code) & !empty($j->message)) {
			throw new \Error($j->message.' ('.$api.')', 401);
		}
		return $j;
	}



	public function getGuildMember(int $memberID) {
		if (empty($this->guildID)) return;
		if (is_array($this->guildID)) {
			$r = [];
			foreach ($this->guildID as $guildID) {
				$r[] = $this->get('/guilds/'.$guildID.'/members/'.$memberID);
			}
			return $r;
		}
		return $this->get('/guilds/'.$this->guildID.'/members/'.$memberID);
	}

	public function getGuildRoles() {
		if (empty($this->guildID)) return;
		if (is_array($this->guildID)) {
			$r = [];
			foreach ($this->guildID as $guildID) {
				$r[] = $this->get('/guilds/'.$guildID.'/roles');
			}
			return $r;
		}
		return $this->get('/guilds/'.$this->guildID.'/roles');
	}
	
	public function getUser() {
		$j = $this->get('/oauth2/applications/@me');
		#$this->save('getUser', $j);
		$this->id = $j->id;
		return $j;
	}
}