<?php
namespace Bang\Gamer;
use Bang\Chain\HTTP;

class Xbox extends HTTP {
	protected
		$token,
		$stats,
		$limit,
		$gamers = [],
		$gamerID,
		$gamerTag,
		$browser,
		$downloadDir,
		$defaultDownloadDir = '/tmp';
	const
		IMAGES_URL = '/screenshots',
		VIDEOS_URL = '/game-clips',
		API_URL = 'https://xapi.us/v2';

	public function __construct(string $downloadDir = null, int $limit = 2) {
		if ($downloadDir) {
			$this->defaultDownloadDir = $downloadDir;
			$this->downloadDir();
		}
		if ($limit) {
			$this->limit = $limit;
		}
		$this->browser = new HTTP;
		$this->browser->pretty();
		$this->reset();
		parent::__construct();
	}
	function reset(bool $completely = false):object {
		parent::reset($completely);
		$this->url(self::API_URL);
		return $this;
	}
	public function gamer(int $gamerID = null, string $gamerTag) {
		$this->reset();
		if (is_null($gamerID)) {
			$gamerID = (int) $this->get('/xuid/'.rawurlencode($gamerTag))->data();
		}
	#	if ($this->get('/gamertag/'.$gamerID)->data() != $gamerTag) {
	#		throw new Exception('Mismatch: gamerID & gamerTag', 1);
	#		exit;
	#	}
		$this->gamerID($gamerID);
		$this->gamerTag($gamerTag);
		echo $gamerID.':'.$gamerTag.PHP_EOL;
	}

	public function gamerID(int $gamerID) {
		$this->gamerID = $gamerID;
		$this->url(self::API_URL.'/'.$gamerID);
	}
	public function gamerTag(string $gamerTag) {
		$this->gamerTag = $gamerTag;
		$this->gamers[$gamerTag] = (object) ['images' => [], 'videos' => []];
		$this->downloadDir = $this->defaultDownloadDir;
		$this->downloadDir();
		$this->stats = (object) [
			'httpCode' => null,
			'foundImages' => 0,
			'newImages' => 0,
			'foundVideos' => 0,
			'newVideos' => 0,
		];
	}
	public function downloadDir(string $downloadDir = null) {
		$this->downloadDir = isset($downloadDir) ? $downloadDir : $this->downloadDir;
		if (strlen($this->gamerTag)) {
			$gamerDir = $this->downloadDir.'/'.$this->gamerTag;
			if ((file_exists($gamerDir)) && (is_dir($gamerDir)) && (is_writable($gamerDir))) {
				$this->downloadDir = $gamerDir;
			}
			else if ((file_exists($this->downloadDir)) && (is_dir($this->downloadDir)) && (is_writable($this->downloadDir))) {
				if (mkdir($gamerDir, 0777)) {
					$this->downloadDir = $gamerDir;
				}
			}
			else {
				$this->downloadDir = false;
			}
		}
	}
	private function gameDir($id, $name = null) {
		$dir = $this->downloadDir.'/'.$id;
		if (!file_exists($dir)) {
			mkdir($dir, 0777);
		}
		if ((is_dir($dir)) && (is_writable($dir))) {
			if (is_string($name)) {
				if ((!file_exists($dir.'/.info')) || (filesize($dir.'/.info') == 0)) {
					file_put_contents($dir.'/.info', $name);
				}
			}
			return $dir;
		}
	}
	public function downloadImages() {
		$this->listImages();
		if (empty($this->gamers[$this->gamerTag]->images)) return;
		foreach ($this->gamers[$this->gamerTag]->images as $item) {
			if ($gameDir = $this->gameDir($item->gameId, $item->gameName)) {
				$image = $gameDir.'/'.$item->id.'.png';
				if (!file_exists($image)) {
					$item->class = 'new';
					echo 'download: '.$image.PHP_EOL;
					$this->browser
						->url($item->imageURL)
						->downloadAs($image)
						->get();
					$this->stats->newImages++;
					file_put_contents($gameDir.'/'.$item->id.'.json', json_encode($item, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
					continue;
				}
				echo 'image already downloaded: '.$image.PHP_EOL;
			}
		}
		echo 'done downloading images...'.PHP_EOL;
	}
	public function listImages() {
		$token = null;
		$done = false;
		$i = 0;
		while (!$done) {
			$i++;
			echo 'listImages: page '.$i.' of '.$this->limit.PHP_EOL;
			flush();
			$items = $this->get(self::IMAGES_URL, $token)->json();
			$response = $this->response(true);
			if (isset($items->success) && $items->success === false) {
				echo 'failed'.PHP_EOL;
				var_dump($items);
				$done = true;
				exit;
			}
			if (empty($response->{'x-continuation-token'}) || ($this->limit && $i >= $this->limit)) {
				$done = true;
				echo 'done'.PHP_EOL;
			}
			else {
				$token = ['continuationToken' => $response->{'x-continuation-token'}];
			}
			if (empty($items[0])) {
				$done = true;
				echo 'absolute done'.PHP_EOL;
				continue;
			}
			foreach ($items as $item) {
				$this->stats->foundImages++;
				$this->gamers[$this->gamerTag]->images[$item->screenshotId] = (object) [
					'id' => $item->screenshotId,
					'name' => $item->screenshotName,
					'imageURL' => $item->screenshotUris[0]->uri,
					'gameId' => $item->titleId,
					'gameName' => $item->titleName,
					'class' => '',
					'datePublished' => $item->datePublished,
					'dateCreated' => $item->dateTaken,
				];
			}
		}
	}

	public function downloadVideos() {
		echo 'downloadVideos'.PHP_EOL;
		$this->listVideos();
		if (empty($this->gamers[$this->gamerTag]->videos)) return;
		echo 'downloading videos...'.PHP_EOL;
		foreach ($this->gamers[$this->gamerTag]->videos as $item) {
			if ($gameDir = $this->gameDir($item->gameId, $item->gameName)) {
				$image = $gameDir.'/'.$item->id.'.png';
				$video = $gameDir.'/'.$item->id.'.mp4';
				if (!file_exists($image)) {
					$item->class = 'new';
					echo 'download: '.$image.PHP_EOL;
					$this->browser
						->url($item->imageURL)
						->downloadAs($image)
						->get();
				}
				if (!file_exists($video)) {
					$item->class = 'new';
					echo 'download: '.$video.PHP_EOL;
					$this->browser
						->url($item->videoURL)
						->downloadAs($video)
						->get();
					file_put_contents($gameDir.'/'.$item->id.'.json', json_encode($item, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
					continue;
				}
				echo 'video already downloaded: '.$video.PHP_EOL;
			}
		}
		echo 'done downloading videos...'.PHP_EOL;
	}
	public function listVideos() {
		$token = null;
		$done = false;
		$i = 0;
		while (!$done) {
			$i++;
			echo 'listVideos: '.$i.' of '.$this->limit.PHP_EOL;
			flush();
			$items = $this->get(self::VIDEOS_URL, $token)->json();
			$response = $this->response(true);
			if (isset($items->success) && $items->success === false) {
				echo 'failed'.PHP_EOL;
				var_dump($items);
				$done = true;
				exit;
			}
			if (empty($response->{'x-continuation-token'}) || ($this->limit && $i >= $this->limit)) {
				$done = true;
				echo 'done'.PHP_EOL;
			}
			else {
				$token = ['continuationToken' => $response->{'x-continuation-token'}];
			}
			if (empty($items)) {
				$done = true;
				echo 'absolute done'.PHP_EOL;
				continue;
			}
			foreach ($items as $item) {
				$this->stats->foundVideos++;
				$this->gamers[$this->gamerTag]->videos[$item->gameClipId] = (object) [
					'id' => $item->gameClipId,
					'clipName' => $item->clipName,
					'videoURL' => $item->gameClipUris[0]->uri,
					'imageURL' => $item->thumbnails[1]->uri,
					'gameId' => $item->titleId,
					'gameName' => $item->titleName,
					'datePublished' => $item->datePublished,
					'dateCreated' => $item->dateRecorded,
					'class' => '',
				];
			}
		}
		echo 'done listVideos'.PHP_EOL;
	}
	public function stats() {
		return $this->stats;
	}
	public function gamers() {
		return $this->gamers;
	}
}
