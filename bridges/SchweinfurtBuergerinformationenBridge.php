<?php
class SchweinfurtBuergerinformationenBridge extends BridgeAbstract {
	const MAINTAINER = 'mibe';
	const NAME = 'Schweinfurt Bürgerinformationen';
	const URI = 'https://www.schweinfurt.de/rathaus-politik/pressestelle/buergerinformationen/index.html';
	const ARTICLE_URI = 'https://www.schweinfurt.de/rathaus-politik/pressestelle/buergerinformationen/%d.html';
	const INDEX_CACHE_TIMEOUT = 10800; // 3h
	const ARTICLE_CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns the latest news for citizens of Schweinfurt';
	const PARAMETERS = array(
		array(
			'pages' => array(
				'name' => 'Number of pages',
				'type' => 'number',
				'title' => 'Specifies the number of pages to fetch. Usually one or two are enough.',
				'exampleValue' => '1',
				'defaultValue' => '1',
			)
		)
	);

	public function getIcon()
	{
		return 'https://www.schweinfurt.de/__/images/favicon.ico';
	}

	public function collectData()
	{
		// Get number of pages to retrieve. One page is the minimum.
		$pages = $this->getInput('pages');
		if (!is_int($pages) || $pages < 1)
			$pages = 1;

		$articleIDs = array();

		for($page = 0; $page < $pages; $page++) {
			$newIDs = $this->getArticleIDsFromPage($page);
			$articleIDs = array_merge($articleIDs, $newIDs);
		}

		foreach($articleIDs as $articleID) {
			$this->items[] = $this->generateItemFromArticle($articleID);

			if (Debug::isEnabled())
				break;
		}
	}

	private function getArticleIDsFromPage($page)
	{
		$url = sprintf(self::URI . '?art_pager=%d', $page);
		$html = getSimpleHTMLDOMCached($url, self::INDEX_CACHE_TIMEOUT)
			or returnServerError('Could not retrieve ' . $url);

		$articles = $html->find('div.artikel-uebersicht');
		$articleIDs = array();

		foreach($articles as $article) {
			// The article ID is in the 'id' attribute of the div element, prefixed with 'artikel_id_'
			if (preg_match('/artikel_id_(\d+)/', $article->id, $match)) {
				$articleIDs[] = $match[1];
			} else
				returnServerError('Couldn\'t determine article ID from index page.');
		}

		return $articleIDs;
	}

	private function generateItemFromArticle($id)
	{
		$url = sprintf(self::ARTICLE_URI, $id);
		$html = getSimpleHTMLDOMCached($url, self::ARTICLE_CACHE_TIMEOUT)
			or returnServerError('Could not retrieve ' . $url);

		$div = $html->find('div#artikel-detail', 0);
		$divContent = $div->find('.c-content', 0);
		$images = $divContent->find('img');

		// Every external link has a little arrow symbol image attached to it.
		// Remove this image. This has to be done before building $content.
		foreach($images as $image)
			if ($image->class == 'imgextlink')
				$image->outertext = '';

		$title = $div->find('.c-title', 0)->innertext;
		$teaser = $div->find('.c-teaser', 0)->innertext;
		$content = $divContent->innertext;

		// The title can contain HTML entities. These can be converted back
		// to regular UTF-8 characters.
		$title = html_entity_decode($title, ENT_HTML5, 'UTF-8');

		// If there's a teaser, make it more eye-catching,
		// so that it is clear, that this is not part of the actual content.
		if (strlen(trim($teaser)) > 0)
			$content = '<i><strong>' . $teaser . '</strong></i>' . $content;

		$item = array(
			'uri' => $url,
			'title' => $title,
			'content' => $content,
			'uid' => $id,
			);

		// Let's see if there are images in the content, and if yes, attach
		// them as enclosures, but not images which are used for linking to an external site.
		foreach($images as $image)
			if ($image->class != 'imgextlink')
				$item['enclosures'][] = $image->src;

		// Get the date of the article. Example: "zuletzt geändert: 26.05.2020"
		$editDate = $div->find('div#edit', 0)->plaintext;
		$editDate = substr($editDate, strrpos($editDate, ' ') + 1);
		$editDate = DateTime::createFromFormat('d.m.Y', $editDate);

		if ($editDate !== false)
			$item['timestamp'] = $editDate->getTimestamp();

		return $item;
	}
}
