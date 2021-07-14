<?php
/**
* RssBridgeYoutube
* Returns the newest videos
* WARNING: to parse big playlists (over ~90 videos), you need to edit simple_html_dom.php:
* change: define('MAX_FILE_SIZE', 600000);
* into:   define('MAX_FILE_SIZE', 900000);  (or more)
*/
class YoutubeBridge extends BridgeAbstract {

	const NAME = 'YouTube Bridge';
	const URI = 'https://www.youtube.com/';
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = 'Returns the 10 newest videos by username/channel/playlist or search';
	const MAINTAINER = 'em92';

	const PARAMETERS = array(
		'By username' => array(
			'u' => array(
				'name' => 'username',
				'exampleValue' => 'test',
				'required' => true
			)
		),
		'By channel id' => array(
			'c' => array(
				'name' => 'channel id',
				'exampleValue' => '15',
				'required' => true
			)
		),
		'By playlist Id' => array(
			'p' => array(
				'name' => 'playlist id',
				'exampleValue' => '15'
			)
		),
		'Search result' => array(
			's' => array(
				'name' => 'search keyword',
				'exampleValue' => 'test'
			),
			'pa' => array(
				'name' => 'page',
				'type' => 'number',
				'title' => 'This option is not work anymore, as YouTube will always return the same page',
				'exampleValue' => 1
			)
		),
		'global' => array(
			'duration_min' => array(
				'name' => 'min. duration (minutes)',
				'type' => 'number',
				'title' => 'Minimum duration for the video in minutes',
				'exampleValue' => 5
			),
			'duration_max' => array(
				'name' => 'max. duration (minutes)',
				'type' => 'number',
				'title' => 'Maximum duration for the video in minutes',
				'exampleValue' => 10
			)
		)
	);

	private $feedName = '';
	private $feeduri = '';
	private $channel_name = '';

	private function ytBridgeQueryVideoInfo($vid, &$author, &$desc, &$time){
		$html = $this->ytGetSimpleHTMLDOM(self::URI . "watch?v=$vid", true);

		// Skip unavailable videos
		if(strpos($html->innertext, 'IS_UNAVAILABLE_PAGE') !== false) {
			return;
		}

		$elAuthor = $html->find('span[itemprop=author] > link[itemprop=name]', 0);
		if (!is_null($elAuthor)) {
			$author = $elAuthor->getAttribute('content');
		}

		$elDatePublished = $html->find('meta[itemprop=datePublished]', 0);
		if(!is_null($elDatePublished))
			$time = strtotime($elDatePublished->getAttribute('content'));

		$jsonData = $this->getJSONData($html);
		$jsonData = $jsonData->contents->twoColumnWatchNextResults->results->results->contents;

		$videoSecondaryInfo = null;
		foreach($jsonData as $item) {
			if (isset($item->videoSecondaryInfoRenderer)) {
				$videoSecondaryInfo = $item->videoSecondaryInfoRenderer;
				break;
			}
		}
		if (!$videoSecondaryInfo) {
			returnServerError('Could not find videoSecondaryInfoRenderer. Error at: ' . $vid);
		}

		if(isset($videoSecondaryInfo->description)) {
			foreach($videoSecondaryInfo->description->runs as $description) {
				if(isset($description->navigationEndpoint)) {
					$metadata = $description->navigationEndpoint->commandMetadata->webCommandMetadata;
					$web_type = $metadata->webPageType;
					$url = $metadata->url;
					$text = '';
					switch ($web_type) {
						case 'WEB_PAGE_TYPE_UNKNOWN':
							$url_components = parse_url($url);
							if(isset($url_components['query']) && strpos($url_components['query'], '&q=') !== false) {
								parse_str($url_components['query'], $params);
								$url = urldecode($params['q']);
							}
							$text = $url;
							break;
						case 'WEB_PAGE_TYPE_WATCH':
						case 'WEB_PAGE_TYPE_BROWSE':
							$url = 'https://www.youtube.com' . $url;
							$text = $description->text;
							break;
					}
					$desc .= "<a href=\"$url\" target=\"_blank\">$text</a>";
				} else {
					$desc .= nl2br($description->text);
				}
			}
		}
	}

	private function ytBridgeAddItem($vid, $title, $author, $desc, $time){
		$item = array();
		$item['id'] = $vid;
		$item['title'] = $title;
		$item['author'] = $author;
		$item['timestamp'] = $time;
		$item['uri'] = self::URI . 'watch?v=' . $vid;
		$thumbnailUri = str_replace('/www.', '/img.', self::URI) . 'vi/' . $vid . '/0.jpg';
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br />' . $desc;
		$this->items[] = $item;
	}

	private function ytBridgeParseXmlFeed($xml) {
		foreach($xml->find('entry') as $element) {
			$title = $this->ytBridgeFixTitle($element->find('title', 0)->plaintext);
			$author = $element->find('name', 0)->plaintext;
			$desc = $element->find('media:description', 0)->innertext;

			// Make sure the description is easy on the eye :)
			$desc = htmlspecialchars($desc);
			$desc = nl2br($desc);
			$desc = preg_replace('/(http[s]{0,1}\:\/\/[a-zA-Z0-9.\/\?\&=\-_]{4,})/ims',
				'<a href="$1" target="_blank">$1</a> ',
				$desc);

			$vid = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
			$time = strtotime($element->find('published', 0)->plaintext);
			if(strpos($vid, 'googleads') === false)
				$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
		}
		$this->feedName = $this->ytBridgeFixTitle($xml->find('feed > title', 0)->plaintext);  // feedName will be used by getName()
	}

	private function ytBridgeFixTitle($title) {
		// convert both &#1234; and &quot; to UTF-8
		return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
	}

	private function ytGetSimpleHTMLDOM($url, $cached = false){
		$header = array(
			'Accept-Language: en-US'
		);
		$opts = array();
		$lowercase = true;
		$forceTagsClosed = true;
		$target_charset = DEFAULT_TARGET_CHARSET;
		$stripRN = false;
		$defaultBRText = DEFAULT_BR_TEXT;
		$defaultSpanText = DEFAULT_SPAN_TEXT;
		if ($cached) {
			return getSimpleHTMLDOMCached($url,
				86400,
				$header,
				$opts,
				$lowercase,
				$forceTagsClosed,
				$target_charset,
				$stripRN,
				$defaultBRText,
				$defaultSpanText);
		}
		return getSimpleHTMLDOM($url,
			$header,
			$opts,
			$lowercase,
			$forceTagsClosed,
			$target_charset,
			$stripRN,
			$defaultBRText,
			$defaultSpanText);
	}

	private function getJSONData($html) {
		$scriptRegex = '/var ytInitialData = (.*?);<\/script>/';
		preg_match($scriptRegex, $html, $matches) or returnServerError('Could not find ytInitialData');
		return json_decode($matches[1]);
	}

	private function parseJSONListing($jsonData) {
		$duration_min = $this->getInput('duration_min') ?: -1;
		$duration_min = $duration_min * 60;

		$duration_max = $this->getInput('duration_max') ?: INF;
		$duration_max = $duration_max * 60;

		if($duration_max < $duration_min) {
			returnClientError('Max duration must be greater than min duration!');
		}

		foreach($jsonData as $item) {
			$wrapper = null;
			if(isset($item->gridVideoRenderer)) {
				$wrapper = $item->gridVideoRenderer;
			} elseif(isset($item->videoRenderer)) {
				$wrapper = $item->videoRenderer;
			} elseif(isset($item->playlistVideoRenderer)) {
				$wrapper = $item->playlistVideoRenderer;
			} else
				continue;

			$vid = $wrapper->videoId;
			$title = $wrapper->title->runs[0]->text;
			$accessibilityData = $wrapper->title->accessibility->accessibilityData->label;
			if(isset($wrapper->ownerText)) {
				$this->channel_name = $wrapper->ownerText->runs[0]->text;
			} elseif(isset($wrapper->shortBylineText)) {
				$this->channel_name = $wrapper->shortBylineText->runs[0]->text;
			}

			$author = '';
			$desc = '';
			$time = '';

			// The duration comes in one of the formats:
			// hh:mm:ss / mm:ss / m:ss
			// 01:03:30 / 15:06 / 1:24
			$durationText = 0;
			if(isset($wrapper->lengthText)) {
				$durationText = $wrapper->lengthText;
			} else {
				foreach($wrapper->thumbnailOverlays as $overlay) {
					if(isset($overlay->thumbnailOverlayTimeStatusRenderer)) {
						$durationText = $overlay->thumbnailOverlayTimeStatusRenderer->text;
						break;
					}
				}
			}

			if(isset($durationText->simpleText)) {
				$durationText = trim($durationText->simpleText);
			}
			if(preg_match('/([\d]{1,2}):([\d]{1,2})\:([\d]{2})/', $durationText)) {
				$durationText = preg_replace('/([\d]{1,2}):([\d]{1,2})\:([\d]{2})/', '$1:$2:$3', $durationText);
			} else {
				$durationText = preg_replace('/([\d]{1,2})\:([\d]{2})/', '00:$1:$2', $durationText);
			}
			sscanf($durationText, '%d:%d:%d', $hours, $minutes, $seconds);
			$duration = $hours * 3600 + $minutes * 60 + $seconds;
			if($duration < $duration_min || $duration > $duration_max) {
				continue;
			}

			$this->ytBridgeQueryVideoInfo($vid, $author, $desc, $time);
			$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
		}
	}

	public function collectData(){

		$xml = '';
		$html = '';
		$url_feed = '';
		$url_listing = '';
		$custom_url = '';

		if($this->getInput('u')) { /* User and Channel modes */
			$this->request = $this->getInput('u');
			$url_feed = self::URI . 'feeds/videos.xml?user=' . urlencode($this->request);
			$url_listing = self::URI . 'user/' . urlencode($this->request) . '/videos';
			$custom_url = self::URI . urlencode($this->request) . '/videos';
		} elseif($this->getInput('c')) {
			$this->request = $this->getInput('c');
			$url_feed = self::URI . 'feeds/videos.xml?channel_id=' . urlencode($this->request);
			$url_listing = self::URI . 'channel/' . urlencode($this->request) . '/videos';
		}

		if(!empty($url_feed) && !empty($url_listing)) {
			$this->feeduri = $url_listing;
			$xml = '';
			$html = '';
			try {
				if(!$this->skipFeeds()) {
					$xml = $this->ytGetSimpleHTMLDOM($url_feed);
				} else {
					$html = $this->ytGetSimpleHTMLDOM($url_listing);
					$jsonData = $this->getJSONData($html);
					// Throw an error right here if it doesn't have anything.
					// Sometimes, Youtube user page have a weird case
					// For example: NASA. When user write 'nasa' into the username and add limit for duration
					// Bridge immediately find its user page (/user/nasa) and then nothing happen.
					// Digging into the data, it appear it's another account, not from NASA itself.
					// If you use feed, it works normally cause it already raise 404 error
					if(!isset($jsonData->contents)) {
						returnServerError('');	// Throw an empty one to trigger try catch
					}
				}
			} catch(Exception $e) {
				if($custom_url) {
					$html = $this->ytGetSimpleHTMLDOM($custom_url);
					$jsonData = $this->getJSONData($html);
					$url_feed = $jsonData->metadata->channelMetadataRenderer->rssUrl;
					$xml = $this->ytGetSimpleHTMLDOM($url_feed);
					$this->feeduri = $custom_url;
				} else {
					returnServerError($e->getMessage());
				}
			}
			if(!$this->skipFeeds()) {
				return $this->ytBridgeParseXmlFeed($xml);
			}

			if(isset($jsonData->contents)) {
				$this->channel_name = $jsonData->metadata->channelMetadataRenderer->title;
				$jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[1];
				$jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0];
				$jsonData = $jsonData->itemSectionRenderer->contents[0]->gridRenderer->items;
			} else {
				returnServerError('Unable to get data from YouTube. Username/Channel: ' . $this->request);
			}

			$this->parseJSONListing($jsonData);
			$this->feedName = str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
		} elseif($this->getInput('p')) { /* playlist mode */
			// TODO: this mode makes a lot of excess video query requests.
			// To make less requests, we need to cache following dictionary "videoId -> datePublished, duration"
			// This cache will be used to find out, which videos to fetch
			// to make feed of 15 items or more, if there a lot of videos published on that date.
			$this->request = $this->getInput('p');
			$url_feed = self::URI . 'feeds/videos.xml?playlist_id=' . urlencode($this->request);
			$url_listing = self::URI . 'playlist?list=' . urlencode($this->request);
			$html = $this->ytGetSimpleHTMLDOM($url_listing)
				or returnServerError("Could not request YouTube. Tried:\n - $url_listing");
			$jsonData = $this->getJSONData($html);
			// TODO: this method returns only first 100 video items
			// if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
			$jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[0];
			$jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
			$jsonData = $jsonData->contents[0]->playlistVideoListRenderer->contents;
			$item_count = count($jsonData);
			if ($item_count <= 15 && !$this->skipFeeds() && ($xml = $this->ytGetSimpleHTMLDOM($url_feed))) {
				$this->ytBridgeParseXmlFeed($xml);
			} else {
				$this->parseJSONListing($jsonData);
			}
			$this->feedName = 'Playlist: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext); // feedName will be used by getName()
			usort($this->items, function ($item1, $item2) {
				return $item2['timestamp'] - $item1['timestamp'];
			});
		} elseif($this->getInput('s')) { /* search mode */
			$this->request = $this->getInput('s');
			$url_listing = self::URI
			. 'results?search_query='
			. urlencode($this->request)
			. '&sp=CAI%253D';

			$html = $this->ytGetSimpleHTMLDOM($url_listing)
				or returnServerError("Could not request YouTube. Tried:\n - $url_listing");

			$jsonData = $this->getJSONData($html);
			$jsonData = $jsonData->contents->twoColumnSearchResultsRenderer->primaryContents;
			$jsonData = $jsonData->sectionListRenderer->contents;
			foreach($jsonData as $data) {	// Search result includes some ads, have to filter them
				if(isset($data->itemSectionRenderer->contents[0]->videoRenderer)) {
					$jsonData = $data->itemSectionRenderer->contents;
					break;
				}
			}
			$this->parseJSONListing($jsonData);
			$this->feeduri = $url_listing;
			$this->feedName = 'Search: ' . $this->request; // feedName will be used by getName()
		} else { /* no valid mode */
			returnClientError("You must either specify either:\n - YouTube
 username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
		}
	}

	private function skipFeeds() {
		return ($this->getInput('duration_min') || $this->getInput('duration_max'));
	}

	public function getURI()
	{
		if (!is_null($this->getInput('p'))) {
			return static::URI . 'playlist?list=' . $this->getInput('p');
		} elseif($this->feeduri) {
			return $this->feeduri;
		}

		return parent::getURI();
	}

	public function getName(){
	  // Name depends on queriedContext:
		switch($this->queriedContext) {
		case 'By username':
		case 'By channel id':
		case 'By playlist Id':
		case 'Search result':
			return htmlspecialchars_decode($this->feedName) . ' - YouTube'; // We already know it's a bridge, right?
		default:
			return parent::getName();
		}
	}
}
