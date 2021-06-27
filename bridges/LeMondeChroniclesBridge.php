<?php

/**
 * A dedicated bridge allowing one to read LeMonde chronicles.
 * Typical example is https://www.lemonde.fr/le-sexe-selon-maia/.
 * Notice we take only freely accessible texts
 * @author nicolas-delsaux
 *
 */
class LeMondeChroniclesBridge extends BridgeAbstract
{
	const ENGLISH_MONTHS = array('January', 'February', 'March', 'April', 'May', 'June',
								'July', 'August', 'September', 'October', 'November', 'December');
	const FRENCH_MONTHS = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin',
								'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');

	const MAINTAINER = 'Riduidel';

	const NAME = 'Le Monde chronicles';

	// URI is no more valid, since we can address the whole gq galaxy
	const URI = 'https://www.lemonde.fr';

	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'This bridge allows extraction of a chronicle from the LeMonde website.';

	const DEFAULT_DOMAIN = 'www.lemonde.fr';

	const PARAMETERS = array( array(
		'page' => array(
			'name' => 'Initial page to load',
			'required' => true,
			'exampleValue' => 'le-sexe-selon-maia'
		),
	));

	const REPLACED_ATTRIBUTES = array(
		'href' => 'href',
		'src' => 'src',
		'data-original' => 'src'
	);

	const POSSIBLE_TITLES = array(
		'h2',
		'h3'
	);

	private function getDomain() {
		$domain = self::DEFAULT_DOMAIN;
		if (strpos($domain, '://') === false)
			$domain = 'https://' . $domain;
		return $domain;
	}

	public function getURI()
	{
		return $this->getDomain() . '/' . $this->getInput('page');
	}

	private function findTitleOf($link) {
		foreach (self::POSSIBLE_TITLES as $tag) {
			$title = $link->parent()->find($tag, 0);
			if($title !== null) {
				if($title->plaintext !== null) {
					return $title->plaintext;
				}
			}
		}
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request ' . $this->getURI());

		// Since GQ don't want simple class scrapping, let's do it the hard way and ... discover content !
		foreach ($html->find('div.thread') as $element) {
			if($element->find('span.icon__premium', 0))
				continue;
			$link = $element->find('a.teaser__link', 0);
			$uri = $link->href;

			$item = array();
			$author = $element->find('a.article__author-link', 0);
			if($author !== null) {
				$item['author'] = $author->plaintext;
				$item['title'] = $link->plaintext;
				$item['uri'] = $uri;
				$item['content'] = $this->loadFullArticle($item['uri']);
				$date = $element->find('span.meta__date', 0);
				$sentences = explode('-', $date->plaintext);
				// Now get the first sentence and parse date in
				$published = trim($sentences[0]);
				$published = trim(substr($published, strlen('Publié le ')));
				$published = strtolower($published);
				$published = str_replace(self::FRENCH_MONTHS, self::ENGLISH_MONTHS, $published );

				$date_details = date_parse_from_format('d M Y \à H\hi*', $published);
				if($date_details) {
					$date_object = new DateTime();
					$date_object->setDate($date_details['year'], $date_details['month'], $date_details['day']);
					$date_object->setTime($date_details['hour'], $date_details['minute']);
					$item['timestamp'] = $date_object->getTimestamp();
				}
				$this->items[] = $item;
			}
		}
	}

	/**
	 * Loads the full article and returns the contents
	 * @param $uri The article URI
	 * @return The article content
	 */
	private function loadFullArticle($uri){
		$html = getSimpleHTMLDOMCached($uri);
		$content = $html->find('article.article__content', 0);
//		$content = $this->replaceUriInHtmlElement($content);
		$returned = '';
		// Now it is time to rebuild content, as things may not be so good
		foreach($content->children() as $child) {
			switch($child->tag) {
				case 'figure':
					# We just remove the caption
					$caption = $child->find('figcaption', 0);
					if($caption)
						$caption->outertext = '';
				case 'img':
					// Find img, and if img has a data-src tag replace value of src with that data
					foreach($child->find('img') as $img) {
						if($img->getAttribute('data-src')) {
							$img->setAttribute('src', $img->getAttribute('data-src'));
						}
					}
				case 'p':
					# The author is already processed in header, so don't use it
					if(strstr($child->getAttribute('class'), 'article__fact--false')) {
						break;
					}
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
				case 'h7':
					$returned = $returned . $child->outertext;
					break;
			}
		}
		return $returned;
	}

	/**
	 * Replaces all relative URIs with absolute ones
	 * @param $element A simplehtmldom element
	 * @return The $element->innertext with all URIs replaced
	 */
	private function replaceUriInHtmlElement($element){
		$returned = $element->innertext;
		foreach (self::REPLACED_ATTRIBUTES as $initial => $final) {
			$returned = str_replace($initial . '="/', $final . '="' . self::URI . '/', $returned);
		}
		return $returned;
	}
}
