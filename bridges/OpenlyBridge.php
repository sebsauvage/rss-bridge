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
			)
		),
		'By Tag' => array(
			'tag' => array(
				'name' => 'Tag',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'lgbt-law',
			)
		),
		/*'Author' => array(
			'profileId' => array(
				'name' => 'Profile ID',
				'type' => 'text',
			)
		)*/
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $feedTitle = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getAjaxURI())
			or returnServerError('Could not request: ' . $this->getAjaxURI());

		$html = defaultLinkTo($html, $this->getURI());

		if ($html->find('h2.title-v4', 0)) {
			$html->find('span.tooltiptext', 0)->innertext = '';
			$this->feedTitle = $html->find('a.tooltipitem', 0)->plaintext;
		}

		foreach($html->find('div.item') as $div) {
			$article = getSimpleHTMLDOMCached($div->find('a', 0)->href, 3600)
				or returnServerError('Could not request: ' . $div->find('a', 0)->href);

			$html = defaultLinkTo($html, $this->getURI());

			$article->find('span.lead-text', 0)->outertext = '';

			$item = array();
			$item['title'] = $div->find('h3', 0)->plaintext;
			$item['uri'] = $div->find('a', 0)->href;
			$item['content'] = $article->find('div.body-text', 0);
			$item['enclosures'][] = $article->find('meta[name="twitter:image"]', 0)->content;
			$item['timestamp'] = $article->find('div.meta.small', 0)->plaintext;

			if ($article->find('div.meta a', 0)) {
				$item['author'] = $article->find('div.meta a', 0)->plaintext;
			}

			foreach($article->find('div.themes li') as $li) {
				$item['categories'][] = $li->plaintext;
			}

			$this->items[] = $item;

			if (count($this->items) >= 10) {
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
				return self::URI . 'news/?theme=' . $this->getInput('tag');
			case 'By Region':
				return self::URI . 'news/?region=' . $this->getInput('region');
				break;
			default:
				return parent::getURI();
		}
	}

	public function getName() {
		switch ($this->queriedContext) {
			case 'by News':
				return 'News - Openly';
				break;
			case 'By Opinion':
				return 'Opinion - Openly';
				break;
			case 'by Tag':
				if ($this->feedTitle) {
					return $this->feedTitle . ' - Openly';
				}

				return $this->getInput('tag') . ' - Openly';
				break;
			case 'By Region':
				if ($this->feedTitle) {
					return $this->feedTitle . ' - Openly';
				}

				return $this->getInput('region') . ' - Openly';
				break;
			default:
				return parent::getName();
		}
	}

	private function getAjaxURI() {
		$part = '/ajax.html?page=1';

		switch ($this->queriedContext) {
			case 'All News':
				return self::URI . 'news' . $part;
				break;
			case 'All Opinion':
				return self::URI . 'people' . $part;
				break;
			case 'By Tag':
				return self::URI . 'news' . $part . '&theme=' . $this->getInput('tag');
				break;
			case 'By Region':
				return self::URI . 'news' . $part . '&region=' . $this->getInput('region');
				break;
		}
	}
}
