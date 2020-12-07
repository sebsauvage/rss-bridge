<?php
class YouTubeAPIBridge extends BridgeAbstract {
	const NAME = 'YouTube API Bridge';
	const URI = 'https://github.com/fulmeek';
	const DESCRIPTION = 'Keep track of your favorite channel';
	const MAINTAINER = 'fulmeek';
	const PARAMETERS = array(
		'User Channel' => array(
			'channel' => array(
				'name' => 'Channel ID',
				'type' => 'text',
				'required' => true
			),
			'type' => array(
				'name' => 'Type of feed',
				'type' => 'list',
				'values' => array(
					'Latest Videos' => 'videos',
					'Channel Activities' => 'activities'
				),
				'defaultValue' => 'videos'
			)
		),
		'Video Playlist' => array(
			'playlist' => array(
				'name' => 'Playlist ID',
				'type' => 'text',
				'required' => true
			)
		)
	);

	const API_ENDPOINT = 'https://www.googleapis.com/youtube/v3/';
	const API_BASELINK = 'https://www.youtube.com/';
	const ACTIVITY_SEPARATOR = ': ';

	private $apiKey;

	private $author;
	private $title;
	private $link;
	private $description;

	public function collectData() {
		$this->getApiConfig();
		if (empty($this->apiKey)) {
			returnServerError('API key required, please see configuration');
		}

		switch($this->queriedContext) {
			case 'User Channel':
				$this->collectChannelData();
				break;
			case 'Video Playlist':
				$this->collectPlaylistData();
				break;
			default:
				returnClientError('Unknown context');
		}
	}

	public function getName(){
		if (!empty($this->title)) {
			return $this->title . ' - YouTube';
		}
		return self::NAME;
	}

	public function getURI(){
		return (!empty($this->link)) ? $this->link : self::URI;
	}

	public function getIcon(){
		return self::API_BASELINK . '/favicon.ico';
	}

	public function getDescription(){
		$this->getApiConfig();

		$description = (!empty($this->description)) ? $this->description : self::DESCRIPTION;

		if (empty($this->apiKey)) {
			$description .= '<p>- YouTube Data API v3 key required, please see configuration. -</p>';
		}

		return $description;
	}

	private function getApiConfig() {
		$this->apiKey = Configuration::getConfig(get_called_class(), 'api_key');
	}

	private function collectChannelData() {
		$channelID = $this->getInput('channel');

		if (substr($channelID, 0, 2) != 'UC') {
			returnClientError('Please provide a channel ID');
		}

		$playlist = null;

		$data = json_decode(getContents(self::API_ENDPOINT . 'channels?part=snippet,contentDetails&id=' . $channelID
			. '&key=' . $this->apiKey));
		if (empty($data)) {
			returnServerError('Unable to fetch data');
		}

		if (!empty($data->items)) {
			foreach ($data->items as $idx => $item) {
				if ($item->kind != 'youtube#channel') {
					continue;
				}
				$this->author = (!empty($item->snippet->title)) ? $item->snippet->title : $channelID;
				$this->title = $this->author;
				$this->link = self::API_BASELINK . 'channel/' . $channelID;
				if (isset($item->contentDetails->relatedPlaylists->uploads)) {
					$playlist = $item->contentDetails->relatedPlaylists->uploads;
				}
				if (!empty($item->snippet->description)) {
					$this->description = $this->hypertextize($this->htmlize($item->snippet->description));
				}
			}
		}

		switch ($this->getInput('type')) {
			case 'videos':
				$this->collectVideos($playlist);
				break;
			case 'activities':
				$this->collectActivities($channelID);
				break;
			default:
				returnClientError('Unknown type');
		}
	}

	private function collectPlaylistData() {
		$playlistID = $this->getInput('playlist');

		if (substr($playlistID, 0, 2) != 'PL') {
			returnClientError('Please provide a playlist ID');
		}

		$data = json_decode(getContents(self::API_ENDPOINT . 'playlists?part=snippet&id=' . $playlistID
			. '&key=' . $this->apiKey));
		if (empty($data)) {
			returnServerError('Unable to fetch data');
		}

		if (!empty($data->items)) {
			foreach ($data->items as $idx => $item) {
				if ($item->kind != 'youtube#playlist') {
					continue;
				}
				if (!empty($item->snippet->title)) {
					$this->title = $item->snippet->title;
				}
				if (!empty($item->snippet->channelTitle)) {
					$this->author = $item->snippet->channelTitle;
				}
				if (!empty($item->snippet->description)) {
					$this->description = $this->hypertextize($this->htmlize($item->snippet->description));
				}
				$this->link = self::API_BASELINK . 'playlist?list=' . $playlistID;
			}
		}

		$this->collectVideos($playlistID);
	}

	private function collectVideos($playlist) {
		if (empty($playlist)) {
			// no error, but also no playlist
			return;
		}

		$data = json_decode(getContents(self::API_ENDPOINT . 'playlistItems?part=snippet&playlistId=' . $playlist
			. '&maxResults=50&key=' . $this->apiKey));
		if (empty($data)) {
			returnServerError('Unable to fetch data');
		}

		$videos = array();

		if (!empty($data->items)) {
			foreach ($data->items as $idx => $item) {
				if ($item->kind != 'youtube#playlistItem') {
					continue;
				}

				$insert = array();

				$published = (isset($item->snippet->publishedAt)) ? $item->snippet->publishedAt : '';
				$insert['timestamp'] = strtotime($published);
				$insert['author'] = (!empty($item->snippet->channelTitle)) ? $item->snippet->channelTitle : $this->author;

				$itemId = $item->id;
				if (!empty($item->snippet->resourceId->videoId)) {
					$itemId = $item->snippet->resourceId->videoId;
					$insert['uri'] = self::API_BASELINK . 'watch?v=' . $item->snippet->resourceId->videoId;
					$videos[$item->snippet->resourceId->videoId] = $idx;
				}

				$insert['content'] = '';
				if (!empty($item->snippet->thumbnails)) {
					if (!empty($insert['uri'])) {
						$insert['content'] .= '<p><a href="' . $insert['uri'] . '" target="_blank"><img src="'
							. $this->thumb($item->snippet->thumbnails)
							. '" /></a></p>';
					} else {
						$insert['content'] .= '<p><img src="' . $this->thumb($item->snippet->thumbnails) . '" /></p>';
					}
					$insert['enclosures'] = array($this->maxthumb($item->snippet->thumbnails));
				}
				if (!empty($item->snippet->description)) {
					$insert['content'] .= '<p>' . $this->hypertextize($this->htmlize($item->snippet->description)) . '</p>';
				}

				$insert['title'] = (!empty($item->snippet->title)) ? $item->snippet->title : 'Video';

				// neither $item->id nor $item->etag are reliable
				$insert['uid'] = hash('sha1', $itemId);

				$this->items[$idx] = $insert;
			}
		}

		if (!empty($videos)) {
			$data = json_decode(getContents(self::API_ENDPOINT . 'videos?part=snippet&id=' . implode(',', array_keys($videos))
				. '&key=' . $this->apiKey));
			if (empty($data)) {
				returnServerError('Unable to fetch data');
			}

			if (!empty($data->items)) {
				foreach ($data->items as $idx => $item) {
					if ($item->kind != 'youtube#video') {
						continue;
					}

					$this->items[$videos[$item->id]]['timestamp'] = strtotime($item->snippet->publishedAt);
				}
			}
		}
	}

	private function collectActivities($channelID) {
		$data = json_decode(getContents(self::API_ENDPOINT . 'activities?part=snippet,contentDetails&channelId=' . $channelID
			. '&maxResults=50&key=' . $this->apiKey));
		if (empty($data)) {
			returnServerError('Unable to fetch data');
		}

		if (!empty($data->items)) {
			$list = array();
			$channels = array();
			foreach ($data->items as $item) {
				foreach ($item->contentDetails as $i) {
					if (!empty($i->resourceId->channelId)) {
						$list[] = $i->resourceId->channelId;
						break;
					}
				}
			}
			if (count($list) > 0) {
				$subdata = json_decode(getContents(self::API_ENDPOINT . 'channels?part=snippet&id=' . implode(',', $list)
					. '&key=' . $this->apiKey));
				if (!empty($subdata->items)) {
					foreach ($subdata->items as $item) {
						if ($item->kind != 'youtube#channel') {
							continue;
						}
						$channels[$item->id] = $item->snippet;
					}
				}
			}

			foreach ($data->items as $idx => $item) {
				if ($item->kind != 'youtube#activity') {
					continue;
				}

				$insert = array();

				$published = (isset($item->snippet->publishedAt)) ? $item->snippet->publishedAt : '';
				$insert['timestamp'] = strtotime($published);
				$insert['author'] = (!empty($item->snippet->channelTitle)) ? $item->snippet->channelTitle : $this->author;

				$insert['content'] = '';
				if (!empty($item->snippet->description))
					$insert['content'] .= '<p>' . $this->hypertextize($this->htmlize($item->snippet->description)) . '</p>';

				$type = (isset($item->snippet->type)) ? $item->snippet->type : '';
				$action = ucwords(preg_replace('/([^A-Z])([A-Z])/', '$1 $2', $type));
				if (!empty($item->snippet->title)) {
					$insert['title'] = $action . self::ACTIVITY_SEPARATOR . $item->snippet->title;
				} else {
					$insert['title'] = $action;
				}

				$itemId = '';
				$thumbInserted = false;
				foreach ($item->contentDetails as $i) {
					if (!empty($i->videoId)) {
						$itemId = $i->videoId;
						$insert['uri'] = self::API_BASELINK . 'watch?v=' . $i->videoId;
						break;
					} elseif (!empty($i->resourceId->videoId)) {
						$itemId = $i->resourceId->videoId;
						$insert['uri'] = self::API_BASELINK . 'watch?v=' . $i->resourceId->videoId;
						break;
					} elseif (!empty($i->resourceId->channelId)) {
						$itemId = $i->resourceId->channelId;
						$insert['uri'] = self::API_BASELINK . 'channel/' . $i->resourceId->channelId;
						if (isset($channels[$i->resourceId->channelId])) {
							$subitem = $channels[$i->resourceId->channelId];
							$insert['title'] = $action . self::ACTIVITY_SEPARATOR . $subitem->title;
							if (isset($subitem->thumbnails)) {
								$insert['content'] = '<p><a href="' . $insert['uri'] . '" target="_blank"><img src="'
									. $this->thumb($subitem->thumbnails)
									. '" /></a></p>' . $insert['content'];
								$thumbInserted = true;
							}
						}
						break;
					}
				}

				if (!$thumbInserted && !empty($item->snippet->thumbnails)) {
					if (!empty($insert['uri'])) {
						$insert['content'] = '<p><a href="' . $insert['uri'] . '" target="_blank"><img src="'
							. $this->thumb($item->snippet->thumbnails)
							. '" /></a></p>' . $insert['content'];
					} else {
						$insert['content'] = '<p><img src="' . $this->thumb($item->snippet->thumbnails) . '" /></p>' . $insert['content'];
					}
				}

				if (isset($item->snippet->thumbnails)) {
					$insert['enclosures'] = array($this->maxthumb($item->snippet->thumbnails));
				}

				// neither $item->id nor $item->etag are reliable
				$insert['uid'] = hash('sha1', $channelID . $type . $published . $itemId);

				$this->items[] = $insert;
			}
		}
	}

	private function thumb($thumbs, $minwidth = 150) {
		$data = '';
		$sort = array();
		foreach ($thumbs as $item) {
			if (isset($item->width)) {
				$sort[$item->width] = $item->url;
			}
		}
		if (count($sort) > 0) {
			ksort($sort);
			foreach ($sort as $w => $u) {
				$data = $u;
				if ($w >= $minwidth) {
					break;
				}
			}
		} else {
			if (isset($thumbs->default)) {
				$data = $thumbs->default->url;
			}

			if ($minwidth > 120) {
				if (isset($thumbs->medium)) {
					$data = $thumbs->medium->url;
				}
			}
		}
		return $data;
	}

	private function maxthumb($thumbs) {
		$data = '';
		$sort = array();
		foreach ($thumbs as $item) {
			if (isset($item->width)) {
				$sort[$item->width] = $item->url;
			}
		}
		if (count($sort) > 0) {
			krsort($sort);
			$data = reset($sort);
		} else {
			if (isset($thumbs->maxres)) {
				$data = $thumbs->maxres->url;
			} elseif (isset($thumbs->standard)) {
				$data = $thumbs->standard->url;
			} elseif (isset($thumbs->high)) {
				$data = $thumbs->high->url;
			} elseif (isset($thumbs->medium)) {
				$data = $thumbs->medium->url;
			} elseif (isset($thumbs->default)) {
				$data = $thumbs->default->url;
			}
		}
		return $data;
	}

	private function htmlize($str) {
		if (!is_string($str)) {
			return '';
		}
		if (html_entity_decode(strip_tags($str), ENT_QUOTES) != $str) {
			return $str;
		}

		return nl2br(htmlentities($str, ENT_QUOTES, 'UTF-8'));
	}

	private function hypertextize($str) {
		$html = $str;
		$words = preg_split('/[\s,]+|\<br[ \/]*\>+/', $html);
		$replace = array();

		foreach ($words as $v) {
			$word = html_entity_decode($v, ENT_QUOTES, 'UTF-8');
			$word = trim($word, "\'\"\.\x00..\x1F\x7B..\xFF");
			$word = htmlentities($word, ENT_QUOTES, 'UTF-8');
			if ((strpos($word, '@') === false) &&
				(strpos($word, '[at]') === false) &&
				(strpos($word, '(at)') === false) &&
				preg_match('/\:\/\/|[0-9a-zA-Z]{3,}\.[a-zA-Z]{2,3}/', $word)
			) {
				$url = $word;
				if (strpos($url, '://') === false) {
					$url = 'http://' . $url;
				}
				$hash = hash('crc32', $url);
				$html = preg_replace('/' . preg_quote($word, '/') . '/', '##R' . $hash . '##', $html, 1);
				$replace['##R' . $hash . '##'] = '<a href="' . $url . '" target="_blank">' . $word . '</a>';
			}
		}

		return strtr($html, $replace);
	}
}
