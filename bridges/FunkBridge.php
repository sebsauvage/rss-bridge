<?php
class FunkBridge extends BridgeAbstract {

	const MAINTAINER = 'µKöff';
	const NAME = 'Funk';
	const URI = 'https://www.funk.net/';
	const DESCRIPTION = 'Videos per channel of German public video-on-demand service Funk';

	const PARAMETERS = array(
		'Channel' => array(
			'channel' => array(
				'name' => 'Slug',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'game-two-856'
			),
			'max' => array(
				'name' => 'Maximum',
				'type' => 'number',
				'defaultValue' => '-1'
			)
		)
	);

	public function collectData(){
		switch($this->queriedContext) {
			case 'Channel':
				$url = static::URI . 'data/videos/byChannelAlias/' . $this->getInput('channel') . '/';
				if(!empty($this->getInput('max')) && $this->getInput('max') >= 0) {
					$url .= '?size=' . $this->getInput('max');
				}

				$jsonString = getContents($url) or returnServerError('No contents received!');
				$json = json_decode($jsonString, true);

				foreach($json['list'] as $element) {
					$this->items[] = $this->collectArticle($element);
				}
				break;
			default:
				returnServerError('Unknown context!');
		}
	}

	private function collectArticle($element) {
		$item = array();
		$item['uri'] = static::URI . 'channel/' . $element['channelAlias'] . '/' . $element['alias'];
		$item['title'] = $element['title'];
		$item['timestamp'] = $element['publicationDate'];
		$item['author'] = str_replace('-' . $element['channelId'], '', $element['channelAlias']);
		$item['content'] = $element['shortDescription'];
		$item['enclosures'] = array(
			'https://www.funk.net/api/v4.0/thumbnails/' . $element['imageLandscape']
		);
		$item['uid'] = $element['entityId'];
		return $item;
	}

	public function detectParameters($url) {
		$regex = '/^https?:\/\/(?:www\.)?funk\.net\/channel\/([^\/]+).*$/';
		if(preg_match($regex, $url, $urlMatches) > 0) {
			return array(
				'channel' => $urlMatches[1]
			);
		} else {
			return null;
		}
	}

	public function getIcon() {
		return 'https://www.funk.net/img/favicons/favicon-192x192.png';
	}

	public function getName(){
		switch($this->queriedContext) {
			case 'Channel':
				if(!empty($this->getInput('channel'))) {
					return $this->getInput('channel');
				}
				break;
		}
		return parent::getName();
	}
}
