<?php

class ReutersUKBridge extends BridgeAbstract {

	const MAINTAINER = 'JonJump';
	const NAME = 'Reuters UK';
	const URI = 'https://uk.reuters.com';
	const CACHE_TIMEOUT = 3600; //1h
	const DESCRIPTION = 'returns Reuters news stories';

	const PARAMETERS = array(array(
		'interests' => array(
			'name' => 'interests',
			'type' => 'text',
			'title' => 'comma separated locations on the reuters website to get stories',
			'exampleValue' => 'news/uk, politics, business/economy',
			'defaultValue' => 'news/uk, politics, business/economy',
		),
	));

	private $stories;

	private function addStory($content) {
		$uri = $this->makeUri($this->getStoryUri($content));
		if (array_key_exists($uri, $this->stories)) {
			return;
		}

		$this->stories[$uri] = array(
			'uri' => $uri,        // URI to reach the subject ("https://...")
			'title' => $this->getTitle($content),      // Title of the item
			'timestamp' => $this->getTimestamp($content),  // Timestamp of the item in numeric or text format (compatible for strtotime())
			'author' => $this->getAuthor($content),     // Name of the author for this item
			'content' => $this->getContent($content)    // Content in HTML format
		);
	}

	private function makeUri($loc) {
		return $this-> getURI() . '/' . trim($loc, '/ ');
	}

	private function getStoryUri($content){
		return $content->find('a', 0)->getAttribute('href');
	}

	private function getAuthor($content){
		return null;  // author not available from headline listsings - save a page retrieve and set NULL
	}

	private function getContent($content) {
		return join('\n', $content->find('p'));
	}

	private function getTitle($content) {
		$tag = $content->find('h3.story-title', 0);
		if ($tag == null) {
			return null;
		}
		return trim($tag->innertext, ' ');
	}

	private function getTimestamp($content) {
		$tag = $content->find('span.timestamp', 0);
		if ($tag == null) {
			return null;
		}
		return strtotime($tag->innertext);
	}

	private function sortItems() {
		usort($this->items, function ($item1, $item2) {
			return $item1['timestamp'] < $item2['timestamp'] ? -1 : ($item1['timestamp'] == $item2['timestamp'] ? 0 : 1);
		});
	}

	private function getInterestStories($uri){
		$html = getSimpleHTMLDOM($uri)
			or returnServerError('Could not request' . $uri . '.');

		foreach($html->find('div.story-content') as $content) {
			$this->addStory($content);
		}
	}

	public function collectData() {
		$this->stories = array();
		foreach(explode(',', $this->getInput('interests')) as $interest) {
			$uri = $this->makeUri($interest);
			$this->getInterestStories($uri);
		}
		$this->items = array_values($this->stories);
		$this->sortItems();
	}
}
