<?php
class InternetArchiveBridge extends BridgeAbstract {
	const NAME = 'Internet Archive Bridge';
	const URI = 'https://archive.org';
	const DESCRIPTION = 'Returns newest uploads, posts and more from an account';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Account' => array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'required' => true,
				'exampleValue' => '@verifiedjoseph',
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'Uploads' => 'uploads',
					'Posts' => 'posts',
					'Reviews' => 'reviews',
					'Collections' => 'collections',
					'Web Archives' => 'web-archive',
				),
				'defaultValue' => 'uploads',
			)
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $skipClasses = array(
		'item-ia mobile-header hidden-tiles',
		'item-ia account-ia'
	);

	private $detectParamsRegex = '/https?:\/\/archive\.org\/details\/@([\w]+)(?:\?tab=([a-z-]+))?/';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->detectParamsRegex, $url, $matches) > 0) {
			$params['context'] = 'Account';
			$params['username'] = $matches[1];
			$params['content'] = 'uploads';

			if (isset($matches[2])) {
				$params['content'] = $matches[2];
			}

			return $params;
		}

		return null;
	}

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$html = defaultLinkTo($html, $this->getURI());

		if ($this->getInput('content') !== 'posts') {
			$detailsDivNumber = 0;

			foreach ($html->find('div.results > div[data-id]') as $index => $result) {
				$item = array();

				if (in_array($result->class, $this->skipClasses)) {
					continue;
				}

				switch($result->class) {
					case 'item-ia':
						switch($this->getInput('content')) {
							case 'reviews':
								$item = $this->processReview($result);
								break;
							case 'uploads':
								$item = $this->processUpload($result);
							break;
						}

						break;
					case 'item-ia url-item':
						$item = $this->processWebArchives($result);
						break;
					case 'item-ia collection-ia':
						$item = $this->processCollection($result);
						break;
				}

				if ($this->getInput('content') !== 'reviews') {
					$hiddenDetails = $this->processHiddenDetails($html, $detailsDivNumber, $item);

					$this->items[] = array_merge($item, $hiddenDetails);
				} else {

					$this->items[] = $item;

				}

				$detailsDivNumber++;
			}
		}

		if ($this->getInput('content') === 'posts') {
			$this->items = $this->processPosts($html);
		}
	}

	public function getURI() {

		if (!is_null($this->getInput('username')) && !is_null($this->getInput('content'))) {
			return self::URI . '/details/' . $this->processUsername() . '&tab=' . $this->getInput('content');
		}

		return parent::getURI();
	}

	public function getName() {

		if (!is_null($this->getInput('username')) && !is_null($this->getInput('content'))) {
			$contentValues = array_flip(self::PARAMETERS['Account']['content']['values']);

			return $contentValues[$this->getInput('content')] . ' - '
				. $this->processUsername() . ' - Internet Archive';
		}

		return parent::getName();
	}

	private function processUsername() {

		if (substr($this->getInput('username'), 0, 1) !== '@') {
			return '@' . $this->getInput('username');
		}

		return $this->getInput('username');
	}

	private function processUpload($result) {
		$item = array();

		$collection = $result->find('a.stealth', 0);
		$collectionLink = $collection->href;
		$collectionTitle = $collection->find('div.item-parent-ttl', 0)->plaintext;

		$item['title'] = trim($result->find('div.ttl', 0)->innertext);
		$item['timestamp'] = strtotime($result->find('div.hidden-tiles.pubdate.C.C3', 0)->children(0)->plaintext);
		$item['uri'] = $result->find('div.item-ttl.C.C2 > a', 0)->href;

		if ($result->find('div.by.C.C4', 0)->children(2)) {
			$item['author'] = $result->find('div.by.C.C4', 0)->children(2)->plaintext;
		}

		$item['content'] = <<<EOD
<p>Media Type: {$result->attr['data-mediatype']}<br>
Collection: <a href="{$collectionLink}">{$collectionTitle}</a></p>
EOD;

		$item['enclosures'][] = self::URI . $result->find('img.item-img', 0)->source;

		return $item;
	}

	private function processReview($result) {
		$item = array();

		$item['title'] = trim($result->find('div.ttl', 0)->innertext);
		$item['timestamp'] = strtotime($result->find('div.hidden-tiles.pubdate.C.C3', 0)->children(0)->plaintext);
		$item['uri'] = $result->find('div.review-title', 0)->children(0)->href;

		if ($result->find('div.by.C.C4', 0)->children(2)) {
			$item['author'] = $result->find('div.by.C.C4', 0)->children(2)->plaintext;
		}

		$item['content'] = <<<EOD
<p><strong>Subject: {$result->find('div.review-title', 0)->plaintext}</strong></p>
<p>{$result->find('div.hidden-lists.review' , 0)->children(1)->plaintext}</p>
EOD;

		$item['enclosures'][] = self::URI . $result->find('img.item-img', 0)->source;

		return $item;
	}

	private function processWebArchives($result) {
		$item = array();

		$item['title'] = trim($result->find('div.ttl', 0)->plaintext);
		$item['timestamp'] = strtotime($result->find('div.hidden-lists', 0)->children(0)->plaintext);
		$item['uri'] = $result->find('div.item-ttl.C.C2 > a', 0)->href;

		$item['content'] = <<<EOD
{$this->processUsername()} archived <a href="{$item['uri']}">{$result->find('div.ttl', 0)->plaintext}</a>
EOD;

		$item['enclosures'][] = $result->find('img.item-img', 0)->source;

		return $item;
	}

	private function processCollection($result) {
		$item = array();

		$title = trim($result->find('div.collection-title.C.C2', 0)->children(0)->plaintext);
		$itemCount = strtolower(trim($result->find('div.num-items.topinblock', 0)->plaintext));

		$item['title'] = $title . ' (' . $itemCount . ')';
		$item['timestamp'] = strtotime($result->find('div.hidden-tiles.pubdate.C.C3', 0)->children(0)->plaintext);
		$item['uri'] = $result->find('div.collection-title.C.C2 > a', 0)->href;

		$item['content'] = '';

		if ($result->find('img.item-img', 0)) {
			$item['enclosures'][] = self::URI . $result->find('img.item-img', 0)->source;
		}

		return $item;
	}

	private function processHiddenDetails($html, $detailsDivNumber, $item) {
		$description = '';

		if ($html->find('div.details-ia.hidden-tiles', $detailsDivNumber)) {
			$detailsDiv = $html->find('div.details-ia.hidden-tiles', $detailsDivNumber);

			if ($detailsDiv->find('div.C234', 0)->children(0)) {
				$description = $detailsDiv->find('div.C234', 0)->children(0)->plaintext;

				$detailsDiv->find('div.C234', 0)->children(0)->innertext = '';
			}

			$topics = trim($detailsDiv->find('div.C234', 0)->plaintext);

			if (!empty($topics)) {
				$topics = trim($detailsDiv->find('div.C234', 0)->plaintext);
				$topics = trim(substr($topics, 7));

				$item['categories'] = explode(',', $topics);
			}

			$item['content'] = '<p>' . $description . '</p>' . $item['content'];
		}

		return $item;
	}

	private function processPosts($html) {
		$items = array();

		foreach ($html->find('table.forumTable > tr') as $index => $tr) {
			$item = array();

			if ($index === 0) {
				continue;
			}

			$item['title'] = $tr->find('td', 0)->plaintext;
			$item['timestamp'] = strtotime($tr->find('td', 4)->children(0)->plaintext);
			$item['uri'] = $tr->find('td', 0)->children(0)->href;

			$formLink = <<<EOD
<a href="{$tr->find('td', 2)->children(0)->href}">{$tr->find('td', 2)->children(0)->plaintext}</a>
EOD;

			$postDate = $tr->find('td', 4)->children(0)->plaintext;

			$postPageHtml = getSimpleHTMLDOMCached($item['uri'], 3600)
				or returnServerError('Could not request: ' . $item['uri']);

			$postPageHtml = defaultLinkTo($postPageHtml, $this->getURI());

			$post = $postPageHtml->find('div.box.well.well-sm', 0);

			$parentLink = '';
			$replyLink = <<<EOD
<a href="{$post->find('a', 0)->href}">Reply</a>
EOD;

			if ($post->find('a', 1)->innertext = 'See parent post') {
				$parentLink = <<<EOD
<a href="{$post->find('a', 1)->href}">View parent post</a>
EOD;
			}

			$post->find('h1', 0)->outertext = '';
			$post->find('h2', 0)->outertext = '';

			$item['content'] = <<<EOD
<p>{$post->innertext}</p>{$replyLink} - {$parentLink} - Posted in {$formLink} on {$postDate}
EOD;

			$items[] = $item;

			if (count($items) >= 10) {
				break;
			}
		}

		return $items;
	}
}
