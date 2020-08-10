<?php
class MondeDiploBridge extends BridgeAbstract {

	const MAINTAINER = 'Pitchoule';
	const NAME = 'Monde Diplomatique';
	const URI = 'https://www.monde-diplomatique.fr';
	const CACHE_TIMEOUT = 21600;  // 6h
	const DESCRIPTION = 'Returns the newest articles.';
	const PARAMETERS = array(
		array(
			'PHPSESSID' => array(
				'name' => 'Cookie PHPSESSID',
				'type' => 'text',
				'title' => 'Value of the session cookie `PHPSESSID`'
			),
			'lmd_a_m' => array(
				'name' => 'Cookie lmd_a_m',
				'type' => 'text',
				'title' => 'Value of the session cookie `lmd_a_m`'
			),
			'spip_session' => array(
				'name' => 'Cookie spip_session',
				'type' => 'text',
				'title' => 'Value of the session cookie `spip_session`'
			),
		)
	);

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request: ' . self::URI);
		$html = defaultLinkTo($html, self::URI);

		foreach($html->find('div.unarticle') as $article) {
			$element = $article->parent();
			$this->items[] = $this->parseURL($element->href);
		}
	}

	protected function parseURL($url) {
		// Set authentication cookie
		$opt = array();
		$opt[CURLOPT_COOKIE] = 'PHPSESSID=' . $this->getInput('PHPSESSID') . '; ';
		$opt[CURLOPT_COOKIE] .= 'lmd_a_m=' . $this->getInput('lmd_a_m') . '; ';
		$opt[CURLOPT_COOKIE] .= 'spip_session=' . $this->getInput('spip_session') . '; ';

		// Get the page
		$page = getSimpleHTMLDOM($url, array(), $opt)
			or returnServerError('Could not request: ' . $link);
		$page = defaultLinkTo($page, self::URI);

		// Extract the article data
		$item = array();
		$item['uri'] = $page->find('meta[property="og:url"]', 0)->content;
		$item['title'] = $page->find('meta[property="og:title"]', 0)->content;
		$item['author'] = $page->find('span.auteurs', 0)->plaintext;
		$item['content'] = $page->find('div.contenu-principal', 0);
		$item['timestamp'] = strtotime($page->find('meta[property="article:published_time"]', 0)->content);
		$item['categories'] = array();
		foreach($page->find('div.tags a.mots') as $tag) {
			$item['categories'][] = $tag->plaintext;
		}
		$item['uid'] = $item['uri'];

		return $item;
	}
}
