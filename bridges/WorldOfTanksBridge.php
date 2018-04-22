<?php
class WorldOfTanksBridge extends FeedExpander {

	const MAINTAINER = 'Riduidel';
	const NAME = 'World of Tanks';
	const URI = 'http://worldoftanks.eu/';
	const DESCRIPTION = 'News about the tank slaughter game.';

	const PARAMETERS = array( array(
		'lang' => array(
			'name' => 'Langue',
			'type' => 'list',
			'values' => array(
				'Français' => 'fr',
				'English' => 'en',
				'Español' => 'es',
				'Deutsch' => 'de',
				'Čeština' => 'cs',
				'Polski' => 'pl',
				'Türkçe' => 'tr'
			)
		)
	));

	public function collectData() {
		$this->collectExpandableDatas(sprintf('http://worldoftanks.eu/%s/rss/news/', $this->getInput('lang')));
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['content'] = $this->loadFullArticle($item['uri']);
		return $item;
	}

	/**
	 * Loads the full article and returns the contents
	 * @param $uri The article URI
	 * @return The article content
	 */
	private function loadFullArticle($uri){
		$html = getSimpleHTMLDOMCached($uri);

		$content = $html->find('article', 0);

		// Remove the scripts, please
		foreach($content->find('script') as $script) {
			$script->outertext = '';
    }

		return $content->innertext;
	}
}
