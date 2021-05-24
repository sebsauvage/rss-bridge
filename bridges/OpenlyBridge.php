<?php
class OpenlyBridge extends BridgeAbstract {
	const NAME = 'Openly Bridge';
	const URI = 'https://www.openlynews.com/';
	const DESCRIPTION = 'Returns news articles';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'All News' => array(),
		'All Opinion' => array(),
		'By Region' => array(
			'region' => array(
				'name' => 'Region',
				'type' => 'list',
				'values' => array(
					'Africa' => 'africa',
					'Asia Pacific' => 'asia-pacific',
					'Europe' => 'europe',
					'Latin America' => 'latin-america',
					'Middle Easta' => 'middle-east',
					'North America' => 'north-america'
				)
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'News' => 'news',
					'Opinion' => 'people'
				),
				'defaultValue' => 'news'
			)
		),
		'By Tag' => array(
			'tag' => array(
				'name' => 'Tag',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'lgbt-law',
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'News' => 'news',
					'Opinion' => 'people'
				),
				'defaultValue' => 'news'
			)
		),
		'By Author' => array(
			'profileId' => array(
				'name' => 'Profile ID',
				'type' => 'text',
				'required' => true,
				'exampleValue' => '003D000002WZGYRIA5',
			)
		)
	);

	const CACHE_TIMEOUT = 900; // 15 mins
	const ARTICLE_CACHE_TIMEOUT = 3600; // 1 hour

	private $feedTitle = '';
	private $itemLimit = 1;

	private $profileRegexUrl = '/openlynews\.com\/profile\/\?id=([a-zA-Z0-9]+)/';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->profileRegexUrl, $url, $matches) > 0) {
			$params['context'] = 'By Author';
			$params['profileId'] = $matches[1];
			return $params;
		}

		return null;
	}
	
	public function collectData() {

		if ($this->queriedContext === 'By Author') { // Get profile page
			$html = getSimpleHTMLDOM($this->getURI())
				or returnServerError('Could not request: ' . $this->getURI());

			$html = defaultLinkTo($html, $this->getURI());

			if ($html->find('h1', 0)) {
				$this->feedTitle = $html->find('h1', 0)->plaintext;
			}
	
		} else { // Get ajax page
			$html = getSimpleHTMLDOM($this->getAjaxURI())
				or returnServerError('Could not request: ' . $this->getAjaxURI());

			$html = defaultLinkTo($html, $this->getURI());

			if ($html->find('h2.title-v4', 0)) {
				$html->find('span.tooltiptext', 0)->innertext = '';
				$this->feedTitle = $html->find('a.tooltipitem', 0)->plaintext;
			}
		}

		foreach($html->find('div.item') as $div) {
			$this->items[] = $this->getArticle($div->find('a', 0)->href);

			if (count($this->items) >= $this->itemLimit) {
				break;
			}
		}
	}

	public function getURI() {
		switch ($this->queriedContext) {
			case 'All News':
				return self::URI . 'news';
				break;
			case 'All Opinion':
				return self::URI . 'people';
				break;
			case 'By Tag':
				return self::URI . $this->getInput('content') . '/?theme=' . $this->getInput('tag');
			case 'By Region':
				return self::URI . $this->getInput('content') . '/?region=' . $this->getInput('region');
				break;
			case 'By Author':
				return self::URI . 'profile/?id=' . $this->getInput('profileId');
				break;
			default:
				return parent::getURI();
		}
	}

	public function getName() {
		switch ($this->queriedContext) {
			case 'All News':
				return 'News - Openly';
				break;
			case 'All Opinion':
				return 'Opinion - Openly';
				break;
			case 'by Tag':
				if (empty($this->feedTitle)) {
					$this->feedTitle = $this->getInput('tag');
				}

				if ($this->getInput('content') === 'people') {
					return $this->feedTitle . ' - Opinion - Openly';
				}

				return $this->feedTitle . ' - Openly';
				break;
			case 'By Region':
				if (empty($this->feedTitle)) {
					$this->feedTitle = $this->getInput('region');
				}

				if ($this->getInput('content') === 'people') {
					return $this->feedTitle . ' - Opinion - Openly';
				}

				return $this->feedTitle . ' - Openly';
				break;
				break;
			case 'By Author':
				if ($this->feedTitle) {
					return $this->feedTitle . ' - Author - Openly';
				}

				return $this->getInput('profileId') . ' - Author - Openly';
				break;
			default:
				return parent::getName();
		}
	}

	private function getAjaxURI() {
		$part = '/ajax.html?';

		switch ($this->queriedContext) {
			case 'All News':
				return self::URI . 'news' . $part;
				break;
			case 'All Opinion':
				return self::URI . 'people' . $part;
				break;
			case 'By Tag':
				return self::URI . $this->getInput('content') . $part . 'theme=' . $this->getInput('tag');
				break;
			case 'By Region':
				return self::URI . $this->getInput('content') . $part . 'region=' . $this->getInput('region');
				break;
		}
	}

	private function getArticle($url) {
		$article = getSimpleHTMLDOMCached($url, self::ARTICLE_CACHE_TIMEOUT)
			or returnServerError('Could not request: ' . $url);

		$article = defaultLinkTo($article, $this->getURI());

		$article->find('span.lead-text', 0)->outertext = ''; // Remove lead text

		$item = array();
		$item['title'] = $article->find('h1', 0)->plaintext;
		$item['uri'] = $url;
		$item['content'] = $article->find('div.body-text', 0);
		$item['enclosures'][] = $article->find('meta[name="twitter:image"]', 0)->content;
		$item['timestamp'] = $article->find('div.meta.small', 0)->plaintext;

		if ($article->find('div.meta a', 0)) {
			$item['author'] = $article->find('div.meta a', 0)->plaintext;
		}

		foreach($article->find('div.themes li') as $li) {
			$item['categories'][] = $li->plaintext;
		}

		return $item;
	}	
}
