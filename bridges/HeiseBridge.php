<?php

class HeiseBridge extends FeedExpander {
	const MAINTAINER = 'Dreckiger-Dan';
	const NAME = 'Heise Online Bridge';
	const URI = 'https://heise.de/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns the full articles instead of only the intro';
	const PARAMETERS = array(array(
		'category' => array(
			'name' => 'Category',
			'type' => 'list',
			'values' => array(
				'heise online - Alle News'
				=> 'https://www.heise.de/newsticker/heise-atom.xml',
				'heise online - Top-News'
				=> 'https://www.heise.de/newsticker/heise-top-atom.xml',
				'Telepolis'
				=> 'https://www.heise.de/tp/news-atom.xml',
				'heise Security'
				=> 'https://www.heise.de/security/rss/news-atom.xml',
				'heise Security Warnungen'
				=> 'https://www.heise.de/security/rss/alert-news-atom.xmll',
				'Make'
				=> 'https://www.heise.de/make/rss/hardware-hacks-atom.xml',
				'iX'
				=> 'https://www.heise.de/ix/rss/news-atom.xml',
				'Mac &#x26; i'
				=> 'https://www.heise.de/mac-and-i/news-atom.xml',
				'heise Developer'
				=> 'https://www.heise.de/developer/rss/news-atom.xml',
				'c&#x27;t'
				=> 'https://www.heise.de/ct/rss/artikel-atom.xml',
				'c&#x27;t Fotografie'
				=> 'https://www.heise.de/foto/rss/news-atom.xml',
				'heise Autos'
				=> 'https://www.heise.de/autos/rss/news-atom.xml',
				'Internet-Störungen'
				=> 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/'
			)
		),
		'limit' => array(
			'name' => 'Limit',
			'type' => 'number',
			'required' => false,
			'title' => 'Specify number of full articles to return',
			'defaultValue' => 5
		)
	));
	const LIMIT = 5;

	public function collectData() {
		$this->collectExpandableDatas(
			$this->getInput('category'),
			$this->getInput('limit') ?: static::LIMIT
		);
	}

	protected function parseItem($feedItem) {
		$item = parent::parseItem($feedItem);
		$uri = $item['uri'] . '&seite=all';

		$article = getSimpleHTMLDOMCached($uri)
			or returnServerError('Could not open article: ' . $uri);

		if ($article) {
			$article = defaultLinkTo($article, $uri);
			$item = $this->addArticleToItem($item, $article);
		}

		return $item;
	}

	private function addArticleToItem($item, $article) {
		if($author = $article->find('[itemprop="author"]', 0))
			$item['author'] = $author->plaintext;

		$content = $article->find('div[class*="article-content"]', 0);

		if ($content == null)
			$content = $article->find('#article_content', 0);

		foreach($content->find('p, h3, ul, table, pre, img') as $element) {
			$item['content'] .= $element;
		}

		foreach($content->find('img') as $img) {
			$item['enclosures'][] = $img->src;
		}

		return $item;
	}
}
