<?php
class AssociatedPressNewsBridge extends BridgeAbstract {
	const NAME = 'Associated Press News Bridge';
	const URI = 'https://apnews.com/';
	const DESCRIPTION = 'Returns newest articles by topic';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Standard Topics' => array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'list',
				'values' => array(
					'AP Top News' => 'apf-topnews',
					'Sports' => 'apf-sports',
					'Entertainment' => 'apf-entertainment',
					'Oddities' => 'apf-oddities',
					'Travel' => 'apf-Travel',
					'Technology' => 'apf-technology',
					'Lifestyle' => 'apf-lifestyle',
					'Business' => 'apf-business',
					'U.S. News' => 'apf-usnews',
					'Health' => 'apf-Health',
					'Science' => 'apf-science',
					'World News' => 'apf-WorldNews',
					'Politics' => 'apf-politics',
					'Religion' => 'apf-religion',
					'Photo Galleries' => 'PhotoGalleries',
					'Fact Checks' => 'APFactCheck',
					'Videos' => 'apf-videos',
				),
				'defaultValue' => 'apf-topnews',
			),
		),
		'Custom Topic' => array(
			'topic' => array(
				'name' => 'Topic',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'Election2020'
			),
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $detectParamRegex = '/^https?:\/\/(?:www\.)?apnews\.com\/(?:[tag|hub]+\/)?([\w-]+)$/';
	private $tagEndpoint = 'https://afs-prod.appspot.com/api/v2/feed/tag?tags=';
	private $feedName = '';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->detectParamRegex, $url, $matches) > 0) {
			$params['topic'] = $matches[1];
			$params['context'] = 'Custom Topic';
			return $params;
		}

		return null;
	}

	public function collectData() {
		switch($this->getInput('topic')) {
			case 'Podcasts':
				returnClientError('Podcasts topic feed is not supported');
				break;
			case 'PressReleases':
				returnClientError('PressReleases topic feed is not supported');
				break;
			case 'apf-videos':
				$this->collectVideoData();
				break;
			default:
				$this->collectCardData();
		}
	}

	public function getURI() {
		if (!is_null($this->getInput('topic'))) {
			return self::URI . $this->getInput('topic');
		}

		return parent::getURI();
	}

	public function getName() {
		if (!empty($this->feedName)) {
			return $this->feedName . ' - Associated Press';
		}

		return parent::getName();
	}

	private function getTagURI() {
		if (!is_null($this->getInput('topic'))) {
			return $this->tagEndpoint . $this->getInput('topic');
		}

		return parent::getURI();
	}

	private function collectCardData() {
		$json = getContents($this->getTagURI())
			or returnServerError('Could not request: ' . $this->getTagURI());

		$tagContents = json_decode($json, true);

		if (empty($tagContents['tagObjs'])) {
			returnClientError('Topic not found: ' . $this->getInput('topic'));
		}

		$this->feedName = $tagContents['tagObjs'][0]['name'];

		foreach ($tagContents['cards'] as $index => &$card) {
			$item = array();

			if ($card['cardType'] == 'Hub Peek') { // skip hub peeks
				continue;
			}

			$storyContent = $card['contents'][0];

			switch($storyContent['contentType']) {
				case 'web': // Skip link only content
					continue 2;

				case 'video':
					$html = $this->processVideo($storyContent);

					$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/'
						. $storyContent['media'][0]['id'] . '/800.jpeg';
					break;
				default:
					if (empty($storyContent['storyHTML'])) { // Skip if no storyHTML
						continue 2;
					}

					$html = defaultLinkTo($storyContent['storyHTML'], self::URI);
					$html = str_get_html($html);

					$this->processMediaPlaceholders($html, $storyContent);
					$this->processHubLinks($html, $storyContent);
					$this->processIframes($html);

					if (!is_null($storyContent['leadPhotoId'])) {
						$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/'
							. $storyContent['leadPhotoId'] . '/800.jpeg';
					}
			}

			$item['title'] = $card['contents'][0]['headline'];
			$item['uri'] = self::URI . $card['shortId'];

			if ($card['contents'][0]['localLinkUrl']) {
				$item['uri'] = $card['contents'][0]['localLinkUrl'];
			}

			$item['timestamp'] = $storyContent['published'];

			// Remove 'By' from the bylines
			if (substr($storyContent['bylines'], 0, 2) == 'By') {
				$item['author'] = ltrim($storyContent['bylines'], 'By ');
			} else {
				$item['author'] = $storyContent['bylines'];
			}

			$item['content'] = $html;

			foreach ($storyContent['tagObjs'] as $tag) {
				$item['categories'][] = $tag['name'];
			}

			$this->items[] = $item;

			if (count($this->items) >= 20) {
				break;
			}
		}
	}

	private function collectVideoData() {
		$html = getSimpleHTMLDOM('https://apnews.com/hub/videos')
			or returnServerError('Could not request: https://apnews.com/hub/videos');

		$this->feedName = 'Videos';

		foreach ($html->find('div.FeedCard.VideoFeature') as $div) {
			$item = array();

			$item['title'] = $div->find('h1', 0)->plaintext;
			$item['timestamp'] = $div->find('span.Timestamp', 0)->getAttribute('data-source');

			if ($div->find('div.YoutubeEmbed', 0)) {
				$imageUrl = $div->find('img', 1)->src;

				preg_match('/https:\/\/img\.youtube\.com\/vi\/([\w-]+)\/0\.jpg/', $imageUrl, $match);
				$url = 'https://www.youtube.com/embed/' . $match[1];

				$item['enclosures'][] = $imageUrl;
				$item['content'] = <<<EOD
<iframe width="560" height="315" src="{$url}" frameborder="0" allowfullscreen></iframe>
EOD;
			} elseif ($div->find('div.Video', 0)) {
				$item['content'] = $div->find('div.Video', 0);
				$item['enclosures'][] = $div->find('video', 0)->getAttribute('poster');
			}

			$this->items[] = $item;
		}

	}

	private function processMediaPlaceholders($html, $storyContent) {

		foreach ($html->find('div.media-placeholder') as $div) {
			$key = array_search($div->id, $storyContent['mediumIds']);

			if (!isset($storyContent['media'][$key])) {
				continue;
			}

			$media = $storyContent['media'][$key];

			if ($media['type'] === 'Photo') {
				$mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
				$mediaCaption = $media['caption'];

				$div->outertext = <<<EOD
<figure><img loading="lazy" src="{$mediaUrl}"/><figcaption>{$mediaCaption}</figcaption></figure>
EOD;
			}

			if ($media['type'] === 'YouTube') {
				$div->outertext = <<<EOD
<iframe src="https://www.youtube.com/embed/{$media['externalId']}" width="560" height="315">
</iframe>
EOD;
			}
		}
	}

	/*
		Create full coverage links (HubLinks)
	*/
	private function processHubLinks($html, $storyContent) {

		if (!empty($storyContent['richEmbeds'])) {
			foreach ($storyContent['richEmbeds'] as $embed) {

				if ($embed['type'] === 'Hub Link') {
					$url = self::URI . $embed['tag']['id'];
					$div = $html->find('div[id=' . $embed['id'] . ']', 0);

					if ($div) {
						$div->outertext = <<<EOD
<p><a href="{$url}">{$embed['calloutText']} {$embed['displayName']}</a></p>
EOD;
					}
				}
			}
		}
	}

	private function processVideo($storyContent) {
		$video = $storyContent['media'][0];

		if ($video['type'] === 'YouTube') {
			$url = 'https://www.youtube.com/embed/' . $video['externalId'];
			$html = <<<EOD
<iframe width="560" height="315" src="{$url}" frameborder="0" allowfullscreen></iframe>
EOD;
		} else {
			$html = <<<EOD
<video controls poster="https://storage.googleapis.com/afs-prod/media/{$video['id']}/800.jpeg" preload="none">
	<source src="{$video['gcsBaseUrl']} {$video['videoRenderedSizes'][0]} {$video['videoFileExtension']}" type="video/mp4">
</video>
EOD;
		}

		return $html;
	}

	// Remove datawrapper.dwcdn.net iframes and related javaScript
	private function processIframes($html) {

		foreach ($html->find('iframe') as $index => $iframe) {
			if (preg_match('/datawrapper\.dwcdn\.net/', $iframe->src)) {
				$iframe->outertext = '';

				if ($html->find('script', $index)) {
					$html->find('script', $index)->outertext = '';
				}
			}
		}
	}
}
