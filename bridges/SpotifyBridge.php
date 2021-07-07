<?php
class SpotifyBridge extends BridgeAbstract {
	const NAME = 'Spotify';
	const URI = 'https://spotify.com/';
	const DESCRIPTION = 'Fetches the latest ten albums from one or more artists';
	const MAINTAINER = 'Paroleen';
	const CACHE_TIMEOUT = 3600;
	const PARAMETERS = array( array(
		'clientid' => array(
			'name' => 'Client ID',
			'type' => 'text',
			'required' => true
		),
		'clientsecret' => array(
			'name' => 'Client secret',
			'type' => 'text',
			'required' => true
		),
		'spotifyuri' => array(
			'name' => 'Spotify URIs',
			'type' => 'text',
			'required' => true,
			'exampleValue' => 'spotify:artist:4lianjyuR1tqf6oUX8kjrZ [,spotify:artist:3JsMj0DEzyWc0VDlHuy9Bx]',
		),
		'albumtype' => array(
			'name' => 'Album type',
			'type' => 'text',
			'required' => false,
			'exampleValue' => 'album,single,appears_on,compilation',
			'defaultValue' => 'album,single'
		),
		'country' => array(
			'name' => 'Country',
			'type' => 'text',
			'required' => false,
			'exampleValue' => 'US',
			'defaultValue' => 'US'
		)
	));

	const TOKENURI = 'https://accounts.spotify.com/api/token';
	const APIURI = 'https://api.spotify.com/v1/';

	private $uri = '';
	private $name = '';
	private $token = '';
	private $artists = array();
	private $albums = array();

	public function getURI() {
		if(empty($this->uri))
			$this->getArtist();

		return $this->uri;
	}

	public function getName() {
		if(empty($this->name))
			$this->getArtist();

		return $this->name;
	}

	public function getIcon() {
		return 'https://www.scdn.co/i/_global/favicon.png';
	}

	private function getId($artist) {
		return explode(':', $artist)[2];
	}

	private function getDate($album_date) {
		if(strlen($album_date) == 4)
			$album_date .= '-01-01';
		elseif(strlen($album_date) == 7)
			$album_date .= '-01';

		return DateTime::createFromFormat('Y-m-d', $album_date)->getTimestamp();
	}

	private function getAlbumType() {
		return $this->getInput('albumtype');
	}

	private function getCountry() {
		return $this->getInput('country');
	}

	private function getToken() {
		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey(array('token'));

		if($cache->getTime()) {
			$time = (new DateTime)->getTimestamp() - $cache->getTime();
			Debug::log('Token time: ' . $time);
		}

		if($cache->getTime() == false || $time >= 3600) {
			Debug::log('Fetching token from Spotify');
			$this->fetchToken();
			$cache->saveData($this->token);
		} else {
			Debug::log('Loading token from cache');
			$this->token = $cache->loadData();
		}

		Debug::log('Token: ' . $this->token);
	}

	private function getArtist() {
		if(!is_null($this->getInput('spotifyuri')) && strpos($this->getInput('spotifyuri'), ',') === false) {
			$artist = $this->fetchContent(self::APIURI . 'artists/'
				. $this->getId($this->artists[0]));
			$this->uri = $artist['external_urls']['spotify'];
			$this->name = $artist['name'] . ' - Spotify';
		} else {
			$this->uri = parent::getURI();
			$this->name = parent::getName();
		}
	}

	private function getAllArtists() {
		Debug::log('Parsing all artists');
		$this->artists = explode(',', $this->getInput('spotifyuri'));
	}

	private function getAllAlbums() {
		$this->albums = array();

		$this->getAllArtists();

		Debug::log('Fetching all albums');
		foreach($this->artists as $artist) {
			$fetch = true;
			$offset = 0;

			while($fetch) {
				$partial_albums = $this->fetchContent(self::APIURI . 'artists/'
					. $this->getId($artist)
					. '/albums?limit=50&include_groups='
					. $this->getAlbumType()
					. '&country='
					. $this->getCountry()
					. '&offset='
					. $offset);

				if(!empty($partial_albums['items']))
					$this->albums = array_merge($this->albums,
						$partial_albums['items']);
				else
					$fetch = false;

				$offset += 50;
			}
		}
	}

	private function fetchToken() {
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, self::TOKENURI);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic '
			. base64_encode($this->getInput('clientid')
			. ':'
			. $this->getInput('clientsecret'))));

		$json = curl_exec($curl);
		$json = json_decode($json)->access_token;
		curl_close($curl);

		$this->token = $json;
	}

	private function fetchContent($url) {
		$this->getToken();
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '
			. $this->token));

		Debug::log('Fetching content from ' . $url);
		$json = curl_exec($curl);
		$json = json_decode($json, true);
		curl_close($curl);

		return $json;
	}

	private function sortAlbums() {
		Debug::log('Sorting albums');
		usort($this->albums, function($album1, $album2) {
			if($this->getDate($album1['release_date']) < $this->getDate($album2['release_date']))
				return 1;
			else
				return -1;
		});
	}

	public function collectData() {
		$offset = 0;

		$this->getAllAlbums();
		$this->sortAlbums();

		Debug::log('Building RSS feed');
		foreach($this->albums as $album) {
			$item = array();
			$item['title'] = $album['name'];
			$item['uri'] = $album['external_urls']['spotify'];

			$item['timestamp'] = $this->getDate($album['release_date']);
			$item['author'] = $album['artists'][0]['name'];
			$item['categories'] = array($album['album_type']);

			$item['content'] = '<img style="width: 256px" src="'
				. $album['images'][0]['url']
				. '">';

			if($album['total_tracks'] > 1)
				$item['content'] .= '<p>Total tracks: '
					. $album['total_tracks']
					. '</p>';

			$this->items[] = $item;

			if(count($this->items) >= 10)
				break;
		}
	}
}
