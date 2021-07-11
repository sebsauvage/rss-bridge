<?php
class FolhaDeSaoPauloBridge extends FeedExpander {
	const MAINTAINER = 'somini';
	const NAME = 'Folha de São Paulo';
	const URI = 'https://www1.folha.uol.com.br';
	const DESCRIPTION = 'Returns the newest posts from Folha de São Paulo (full text)';
	const PARAMETERS = array(
		array(
			'feed' => array(
				'name' => 'Feed sub-URL',
				'type' => 'text',
				'title' => 'Select the sub-feed (see https://www1.folha.uol.com.br/feed/)',
				'exampleValue' => 'emcimadahora/rss091.xml',
			),
			'amount' => array(
				'name' => 'Amount of items to fetch',
				'type' => 'number',
				'defaultValue' => 15,
			),
			'deep_crawl' => array(
				'name' => 'Deep Crawl',
				'description' => 'Crawl each item "deeply", that is, return the article contents',
				'type' => 'checkbox',
				'defaultValue' => true,
			),
		)
	);

	protected function parseItem($item){
		$item = parent::parseItem($item);

		if ($this->getInput('deep_crawl')) {
			$articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);
			if($articleHTMLContent) {
				foreach ($articleHTMLContent->find('div.c-news__body .is-hidden') as $toRemove) {
					$toRemove->innertext = '';
				}
				$item_content = $articleHTMLContent->find('div.c-news__body', 0);
				if ($item_content) {
					$text = $item_content->innertext;
					$text = strip_tags($text, '<p><b><a><blockquote><figure><figcaption><img><strong><em><ul><li>');
					$item['content'] = $text;
					$item['uri'] = explode('*', $item['uri'])[1];
				}
			} else {
				Debug::log('???: ' . $item['uri']);
			}
		}
		else {
			$item['uri'] = explode('*', $item['uri'])[1];
		}

		return $item;
	}

	public function collectData(){
		$feed_input = $this->getInput('feed');
		if (substr($feed_input, 0, strlen(self::URI)) === self::URI) {
			Debug::log('Input:: ' . $feed_input);
			$feed_url = $feed_input;
		} else {
			/* TODO: prepend `/` if missing */
			$feed_url = self::URI . '/' . $this->getInput('feed');
		}
		Debug::log('URL: ' . $feed_url);
		$this->collectExpandableDatas($feed_url, $this->getInput('amount'));
	}
}
