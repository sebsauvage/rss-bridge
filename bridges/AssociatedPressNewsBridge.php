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

	private $tagEndpoint = 'https://afs-prod.appspot.com/api/v2/feed/tag?tags=';
	private $feedName = '';

	public function collectData() {

		$json = getContents($this->getTagURI())
			or returnServerError('Could not request: ' . $this->getTagURI());

		$tagContents = json_decode($json, true);

		if (empty($tagContents['tagObjs'])) {
			returnClientError('Topic not found: ' . $this->getInput('topic'));
		}
		
		if ($this->getInput('topic') === 'apf-videos') {
			returnClientError('Video topic not currently supported');
		}
		
		$this->feedName = $tagContents['tagObjs'][0]['name'];
		
		foreach ($tagContents['cards'] as $index => $card) {
			$item = array();

			$json = getContents($card['contents'][0]['gcsUrl'])
				or returnServerError('Could not request: ' . $card['contents'][0]['gcsUrl']);

			$storyContent = json_decode($json, true);
			$html = $storyContent['storyHTML'];
			
			if (empty($storyContent['storyHTML'])) {
				$html = $storyContent['embedHTML'];
			}

			$html = defaultLinkTo($html, self::URI);
			$html = str_get_html($html);

			$this->processMediaPlaceholders($html, $storyContent);
			$this->processIframes($html);

			$item['uri'] = self::URI . $card['contents'][0]['shortId'];
			$item['title'] = $card['contents'][0]['headline'];
			$item['timestamp'] = $storyContent['published'];
			$item['author'] = $storyContent['bylines'];
			$item['content'] = $html;
			$item['enclosures'][] = 'https://storage.googleapis.com/afs-prod/media/' . $storyContent['media'][0]['id'] . '/800.jpeg';

			foreach ($storyContent['tagObjs'] as $tag) {
				$item['categories'][] = $tag['name'];
			}

			$this->items[] = $item;

			if (count($this->items) >= 20) {
				break;
			}
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
	
	private function processMediaPlaceholders($html, $storyContent) {
	
		foreach ($html->find('div.media-placeholder') as $div) {
			$key = array_search($div->id, $storyContent['mediumIds']);

			if ($storyContent['media'][$key]['type'] === 'Photo') {
				$media = $storyContent['media'][$key];

				$mediaUrl = $media['gcsBaseUrl'] . $media['imageRenderedSizes'][0] . $media['imageFileExtension'];
				$mediaCaption = $media['caption'];

				$div->innertext = <<<EOD
<figure><img loading="lazy" src="{$mediaUrl}"/><figcaption>{$mediaCaption}</figcaption></figure>
EOD;
			} else if ($storyContent['media'][$key]['type'] === 'YouTube') {
				$div->innertext = <<<EOD
					<iframe allowfullscreen="1" src="https://www.youtube.com/embed/{$storyContent['media'][$key]['externalId']}" width="560" height="315"></iframe>
EOD;
			}
		}
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