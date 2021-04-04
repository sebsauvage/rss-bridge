<?php

class TheTVDBBridge extends BridgeAbstract {

	const MAINTAINER = 'Astyan';
	const NAME = 'TheTVDB';
	const URI = 'https://thetvdb.com/';
	const APIURI = 'https://api.thetvdb.com/';
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = 'Returns latest episodes of a serie with theTVDB api. You can contribute to theTVDB.';
	const PARAMETERS = array(
		array(
			'serie_id' => array(
				'type' => 'number',
				'name' => 'ID',
				'required' => true,
			),
			'nb_episode' => array(
				'type' => 'number',
				'name' => 'Number of episodes',
				'defaultValue' => 10,
				'required' => true,
			),
		)
	);
	const APIACCOUNT = 'RSSBridge';
	const APIKEY = '76DE1887EA401C9A';
	const APIUSERKEY = 'B52869AC6005330F';

	private $feedName = '';

	private function getApiUri(){
		return self::APIURI;
	}

	private function getToken(){
		//login and get token, don't use curlJob to do less adaptations
		$login_array = array(
			'apikey' => self::APIKEY,
			'username' => self::APIACCOUNT,
			'userkey' => self::APIUSERKEY
		);

		$login_json = json_encode($login_array);
		$ch = curl_init($this->getApiUri() . 'login');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $login_json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Accept: application/json'
			)
		);

		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$result = curl_exec($ch);
		curl_close($ch);
		$token_json = (array)json_decode($result);
		if(isset($token_json['Error'])) {
			throw new Exception($token_json['Error']);
			die;
		}
		$token = $token_json['token'];
		return $token;
	}

	private function curlJob($token, $url){
		$token_header = 'Authorization: Bearer ' . $token;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: application/json',
				$token_header
			)
		);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		$result = curl_exec($ch);
		curl_close($ch);
		$result_array = (array)json_decode($result);
		if(isset($result_array['Error'])) {
			throw new Exception($result_array['Error']);
			die;
		}
		return $result_array;
	}

	private function getLatestSeasonNumber($token, $serie_id){
		// get the last season
		$url = $this->getApiUri() . 'series/' . $serie_id . '/episodes/summary';
		$summary = $this->curlJob($token, $url);
		return max($summary['data']->airedSeasons);
	}

	private function getSerieName($token, $serie_id){
		$url = $this->getApiUri() . 'series/' . $serie_id;
		$serie = $this->curlJob($token, $url);
		return $serie['data']->seriesName;
	}

	private function getSeasonEpisodes($token,
	$serie_id,
	$season,
	$seriename,
	&$episodelist,
	$nbepisodemin,
	$page = 1){
		$url = $this->getApiUri()
		. 'series/'
		. $serie_id
		. '/episodes/query?airedSeason='
		. $season
		. '?page='
		. $page;

		$episodes = $this->curlJob($token, $url);
		// we don't check the number of page because we assume there is less
		//than 100 episodes in every season
		$episodes = (array)$episodes['data'];
		$episodes = array_slice($episodes, -$nbepisodemin, $nbepisodemin);
		foreach($episodes as $episode) {
			$episodedata = array();
			$episodedata['uri'] = $this->getURI()
			. '?tab=episode&seriesid='
			. $serie_id
			. '&seasonid='
			. $episode->airedSeasonID
			. '&id='
			. $episode->id;

			// check if the absoluteNumber exist
			if(isset($episode->absoluteNumber)) {
				$episodedata['title'] = 'S'
				. $episode->airedSeason
				. 'E'
				. $episode->airedEpisodeNumber
				. '('
				. $episode->absoluteNumber
				. ') : '
				. $episode->episodeName;
			} else {
				$episodedata['title'] = 'S'
				. $episode->airedSeason
				. 'E'
				. $episode->airedEpisodeNumber
				. ' : '
				. $episode->episodeName;
			}
			$episodedata['author'] = $seriename;
			$date = DateTime::createFromFormat(
				'Y-m-d H:i:s',
				$episode->firstAired . ' 00:00:00'
			);

			$episodedata['timestamp'] = $date->getTimestamp();
			$episodedata['content'] = $episode->overview;
			$episodelist[] = $episodedata;
		}
	}

	public function getIcon() {
		return 'https://artworks.thetvdb.com/icon.png';
	}

	public function getName() {
		if (!empty($this->feedName)) {
			return $this->feedName . ' - TheTVDB';
		}

		return parent::getName();
	}

	public function collectData(){
		$serie_id = $this->getInput('serie_id');
		$nbepisode = $this->getInput('nb_episode');
		$episodelist = array();
		$token = $this->getToken();
		$maxseason = $this->getLatestSeasonNumber($token, $serie_id);
		$seriename = $this->getSerieName($token, $serie_id);
		$season = $maxseason;

		$this->feedName = $seriename;

		while(sizeof($episodelist) < $nbepisode && $season >= 1) {
			$nbepisodetmp = $nbepisode - sizeof($episodelist);
			$this->getSeasonEpisodes(
				$token,
				$serie_id,
				$season,
				$seriename,
				$episodelist,
				$nbepisodetmp
			);

			$season = $season - 1;
		}
		// add the 10 last specials episodes
		try { // catch to avoid error if empty
			$this->getSeasonEpisodes(
				$token,
				$serie_id,
				0,
				$seriename,
				$episodelist,
				$nbepisode
			);
		} catch(Exception $e) {
			unset($e);
		}
		// sort and keep the 10 last episodes, works bad with the netflix serie
		// (all episode lauch at once)
		usort(
			$episodelist,
			function ($a, $b){
				return $a['timestamp'] < $b['timestamp'];
			}
		);
		$this->items = array_slice($episodelist, 0, $nbepisode);
	}
}
