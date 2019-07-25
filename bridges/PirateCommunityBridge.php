<?php
class PirateCommunityBridge extends BridgeAbstract {
	const NAME = 'Pirate-Community Bridge';
	const URI = 'https://raymanpc.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Returns replies to topics';
	const MAINTAINER = 'Roliga';
	const PARAMETERS = array( array(
		'f' => array(
			'name' => 'Forum ID',
			'type' => 'number',
			'title' => 'Forum ID from topic URL. If URL contains f=12 then the ID is 12.'
		),
		't' => array(
			'name' => 'Topic ID',
			'type' => 'number',
			'title' => 'Topic ID from topic URL. If URL contains t=12 then the ID is 12.'
		)));

	private $feedName = '';

	public function detectParameters($url){
		$parsed_url = parse_url($url);

		if($parsed_url['host'] !== 'raymanpc.com')
			return null;

		parse_str($parsed_url['query'], $parsed_query);

		if($parsed_url['path'] === '/forum/viewtopic.php'
		&& array_key_exists('f', $parsed_query)
		&& array_key_exists('t', $parsed_query)) {
			return array(
				'f' => $parsed_query['f'],
				't' => $parsed_query['t'],
			);
		}

		return null;
	}

	public function getName() {
		if(!empty($this->feedName))
			return $this->feedName;

		return parent::getName();
	}

	public function getURI(){
		if(!is_null($this->getInput('f')) && !is_null($this->getInput('t'))) {
			return self::URI
				. 'forum/viewtopic.php?f='
				. $this->getInput('f')
				. '&t='
				. $this->getInput('t')
				. '&sd=d'; // sort posts decending by ate so first page has latest posts
		}

		return parent::getURI();
	}

	public function collectData(){
		// use decending sort order, so latest posts are on the first page
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not retrieve topic page at ' . $this->getURI());

		$this->feedName = $html->find('head title', 0)->plaintext;

		foreach($html->find('.post') as $reply) {
			$item = array();

			$item['uri'] = $this->getURI()
				. $reply->find('h3 a', 0)->getAttribute('href');

			$item['title'] = $reply->find('h3 a', 0)->plaintext;

			$author_html = $reply->find('.author', 0);
			// author_html contains the timestamp as text directly inside it,
			// so delete all other child elements
			foreach($author_html->children as $child)
				$child->outertext = '';
			// Timestamps are always in UTC+1
			$item['timestamp'] = trim($author_html->innertext) . ' +01:00';

			$item['author'] = $reply
				->find('.username, .username-coloured', 0)
				->plaintext;

			$item['content'] = defaultLinkTo($reply->find('.content', 0)->innertext,
				$this->getURI());

			$item['enclosures'] = array();
			foreach($reply->find('.attachbox img.postimage') as $img)
				$item['enclosures'][] = urljoin($this->getURI(), $img->src);

			$this->items[] = $item;
		}
	}
}
