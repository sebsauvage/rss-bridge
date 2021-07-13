<?php
class InstagramBridge extends BridgeAbstract {

	// const MAINTAINER = 'pauder';
	const NAME = 'Instagram Bridge';
	const URI = 'https://www.instagram.com/';
	const DESCRIPTION = 'Returns the newest images';
	const CACHE_TIMEOUT = 900; // 15min

	const PARAMETERS = array(
		'Username' => array(
			'u' => array(
				'name' => 'username',
				'required' => true
			)
		),
		'Hashtag' => array(
			'h' => array(
				'name' => 'hashtag',
				'required' => true
			)
		),
		'Location' => array(
			'l' => array(
				'name' => 'location',
				'required' => true
			)
		),
		'global' => array(
			'media_type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'All' => 'all',
					'Video' => 'video',
					'Picture' => 'picture',
					'Multiple' => 'multiple',
				),
				'defaultValue' => 'all'
			),
			'direct_links' => array(
				'name' => 'Use direct media links',
				'type' => 'checkbox',
			)
		)

	);

	const USER_QUERY_HASH = '58b6785bea111c67129decbe6a448951';
	const TAG_QUERY_HASH = '9b498c08113f1e09617a1703c22b2f32';
	const SHORTCODE_QUERY_HASH = '865589822932d1b43dfe312121dd353a';


	const BIBLIOGRAM_ACCEPTED = false;
	/* 	Video/image from Instagram now can't display due to SameOrigin policy.
	*		This flag will allow to use Bibliogram to process image/video from Instagram
	*		Value : {true|false}
	*		Default: false
	*/
	const BIBLIOGRAM_URL = 'https://bibliogram.art/';
	/* You can change to your perferred one.
	*	List of instance: https://git.sr.ht/~cadence/bibliogram-docs/tree/master/docs/Instances.md
	*/
	const BIBLIOGRAM_IMAGE_PROXY = self::BIBLIOGRAM_URL . 'imageproxy?url=';
	const BIBLIOGRAM_VIDEO_PROXY = self::BIBLIOGRAM_URL . 'videoproxy?url=';

	protected function getImageURL($image_url) {
		if(self::BIBLIOGRAM_ACCEPTED == true) {
			$url = urlencode($image_url);
			$proxy_url = self::BIBLIOGRAM_IMAGE_PROXY . $url;
			return $proxy_url;
		}
		return $image_url;
	}

	protected function getVideoURL($video_url) {
		if(self::BIBLIOGRAM_ACCEPTED == true) {
			$url = urlencode($video_url);
			$proxy_url = self::BIBLIOGRAM_VIDEO_PROXY . $url;
			return $proxy_url;
		}
		return $video_url;
	}

	protected function getInstagramUserId($username) {

		if(is_numeric($username)) return $username;

		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey(array($username));
		$key = $cache->loadData();

		if($key == null) {
				$data = getContents(self::URI . 'web/search/topsearch/?query=' . $username);

				foreach(json_decode($data)->users as $user) {
					if(strtolower($user->user->username) === strtolower($username)) {
						$key = $user->user->pk;
					}
				}
				if($key == null) {
					returnServerError('Unable to find username in search result.');
				}
				$cache->saveData($key);
		}
		return $key;

	}

	public function collectData(){
		$directLink = !is_null($this->getInput('direct_links')) && $this->getInput('direct_links');
		$query_type = '';

		$data = $this->getInstagramJSON($this->getURI());

		if($data == null) {
			returnServerError('Unable to get data from Instagram');
		}

		if(!is_null($this->getInput('u'))) {
			// $userMedia = $data->data->user->edge_owner_to_timeline_media->edges;
			$userMedia = $data->entry_data->ProfilePage[0]->graphql->user->edge_owner_to_timeline_media->edges;
		} elseif(!is_null($this->getInput('h'))) {
			$userMedia = $data->data->hashtag->edge_hashtag_to_media->edges;
		} elseif(!is_null($this->getInput('l'))) {
			$userMedia = $data->entry_data->LocationsPage[0]->graphql->location->edge_location_to_media->edges;
		}

		foreach($userMedia as $media) {
			$media = $media->node;

			switch($this->getInput('media_type')) {
				case 'all': break;
				case 'video':
					if($media->__typename != 'GraphVideo' || !$media->is_video) continue 2;
					break;
				case 'picture':
					if($media->__typename != 'GraphImage') continue 2;
					break;
				case 'multiple':
					if($media->__typename != 'GraphSidecar') continue 2;
					break;
				default: break;
			}

			$item = array();
			$item['uri'] = self::URI . 'p/' . $media->shortcode . '/';

			if (isset($media->owner->username)) {
				$item['author'] = $media->owner->username;
			}

			$textContent = $this->getTextContent($media);

			$item['title'] = ($media->is_video ? '▶ ' : '') . $textContent;
			$titleLinePos = strpos(wordwrap($item['title'], 120), "\n");
			if ($titleLinePos != false) {
				$item['title'] = substr($item['title'], 0, $titleLinePos) . '...';
			}

			if($directLink) {
				$mediaURI = $this->getImageURL($media->display_url);
			} else {
				$mediaURI = self::URI . 'p/' . $media->shortcode . '/media?size=l';
			}

			if(isset($media->__typename)) {
				switch($media->__typename) {
					case 'GraphSidecar':
						$data = $this->getInstagramSidecarData($item['uri'], $item['title'], $media, $textContent);
						$item['content'] = $data[0];
						$item['enclosures'] = $data[1];
						break;
					case 'GraphImage':
						$item['content'] = '<a href="' . htmlentities($item['uri']) . '" target="_blank">';
						$item['content'] .= '<img src="' . htmlentities($mediaURI) . '" alt="' . $item['title'] . '" />';
						$item['content'] .= '</a><br><br>' . nl2br(htmlentities($textContent));
						$item['enclosures'] = array($mediaURI);
						break;
					case 'GraphVideo':
						$data = $this->getInstagramVideoData($item['uri'], $mediaURI, $media, $textContent);
						$item['content'] = $data[0];
						if($directLink) {
							$item['enclosures'] = $data[1];
						} else {
							$item['enclosures'] = array($mediaURI);
						}
						$item['thumbnail'] = $mediaURI;
						break;
					default: break;
				}
			} else {
				$item['content'] = '<a href="' . htmlentities($item['uri']) . '" target="_blank">';
				$item['content'] .= '<img src="' . htmlentities($mediaURI) . '" alt="' . $item['title'] . '" />';
				$item['content'] .= '</a><br><br>' . nl2br(htmlentities($textContent));
				$item['enclosures'] = array($mediaURI);
			}
			$item['timestamp'] = $media->taken_at_timestamp;

			$this->items[] = $item;
		}
	}

	// returns Sidecar(a post which has multiple media)'s contents and enclosures
	protected function getInstagramSidecarData($uri, $postTitle, $mediaInfo, $textContent) {
		$enclosures = array();
		$content = '';
		foreach($mediaInfo->edge_sidecar_to_children->edges as $singleMedia) {
			$singleMedia = $singleMedia->node;
			if($singleMedia->is_video) {
				$video_proxy_url = $this->getVideoURL($singleMedia->video_url);
				if(in_array($video_proxy_url, $enclosures)) continue; // check if not added yet
				$content .= '<video controls><source src="' . $video_proxy_url . '" type="video/mp4"></video><br>';
				array_push($enclosures, $video_proxy_url);
			} else {
				$image_proxy_url = $this->getImageURL($singleMedia->display_url);
				if(in_array($image_proxy_url, $enclosures)) continue; // check if not added yet
				$content .= '<a href="' . $image_proxy_url . '" target="_blank">';
				$content .= '<img src="' . $image_proxy_url . '" alt="' . $postTitle . '" />';
				$content .= '</a><br>';
				array_push($enclosures, $image_proxy_url);
			}
		}
		$content .= '<br>' . nl2br(htmlentities($textContent));

		return array($content, $enclosures);
	}

	// returns Video post's contents and enclosures
	protected function getInstagramVideoData($uri, $mediaURI, $mediaInfo, $textContent) {
		$video_proxy_url = $this->getVideoURL($mediaInfo->video_url);
		$content = '<video controls>';
		$content .= '<source src="' . $video_proxy_url . '" poster="' . $mediaURI . '" type="video/mp4">';
		$content .= '<img src="' . $mediaURI . '" alt="">';
		$content .= '</video><br>';
		$content .= '<br>' . nl2br(htmlentities($textContent));

		return array($content, array($video_proxy_url));
	}

	protected function getTextContent($media) {
		$textContent = '(no text)';
		//Process the first element, that isn't in the node graph
		if (count($media->edge_media_to_caption->edges) > 0) {
			$textContent = trim($media->edge_media_to_caption->edges[0]->node->text);
		}
		return $textContent;
	}

	protected function getSinglePostData($uri) {
		$shortcode = explode('/', $uri)[4];
		$data = getContents(self::URI .
					'graphql/query/?query_hash=' .
					self::SHORTCODE_QUERY_HASH .
					'&variables={"shortcode"%3A"' .
					$shortcode .
					'"}');

		return json_decode($data)->data->shortcode_media;
	}

	protected function getInstagramJSON($uri) {

		// if(!is_null($this->getInput('u'))) {

		// 	$userId = $this->getInstagramUserId($this->getInput('u'));

		// 	$data = getContents(self::URI .
		// 						'graphql/query/?query_hash=' .
		// 						 self::USER_QUERY_HASH .
		// 						 '&variables={"id"%3A"' .
		// 						$userId .
		// 						'"%2C"first"%3A10}');
		// 	return json_decode($data);

		// } else
		if(!is_null($this->getInput('h'))) {
			$data = getContents(self::URI .
					'graphql/query/?query_hash=' .
					 self::TAG_QUERY_HASH .
					 '&variables={"tag_name"%3A"' .
					$this->getInput('h') .
					'"%2C"first"%3A10}');

			return json_decode($data);

		} else {
			$header = array('User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0');
			$html = getContents($uri, $header)
				or returnServerError('Could not request Instagram.');
			$scriptRegex = '/window\._sharedData = (.*);<\/script>/';

			preg_match($scriptRegex, $html, $matches, PREG_OFFSET_CAPTURE, 0);

			return json_decode($matches[1][0]);

		}

	}

	public function getName(){
		if(!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Instagram Bridge';
		}

		return parent::getName();
	}

	public function getURI(){
		if(!is_null($this->getInput('u'))) {
			return self::URI . urlencode($this->getInput('u')) . '/feed';
		} elseif(!is_null($this->getInput('h'))) {
			return self::URI . 'explore/tags/' . urlencode($this->getInput('h'));
		} elseif(!is_null($this->getInput('l'))) {
			return self::URI . 'explore/locations/' . urlencode($this->getInput('l'));
		}
		return parent::getURI();
	}
}
