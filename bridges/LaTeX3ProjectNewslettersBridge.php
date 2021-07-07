<?php
class LaTeX3ProjectNewslettersBridge extends BridgeAbstract {

	const MAINTAINER = 'µKöff';
	const NAME = 'LaTeX3 Project Newsletters';
	const URI = 'https://www.latex-project.org';
	const DESCRIPTION = 'Newsletters by the LaTeX3 project team covering topics of interest in the area of'
		. 'LaTeX3/expl3 development. They appear in irregular intervals and are not necessarily tied to individual'
		. 'releases of the software (as the LaTeX3 kernel code is updated rather often).';

	public function collectData(){
		$html = getSimpleHTMLDOM(static::URI . '/news/latex3-news/') or returnServerError('No contents received!');
		$newsContainer = $html->find('article tbody', 0);

		foreach($newsContainer->find('tr') as $row) {
			$this->items[] = $this->collectArticle($row);
		}
	}

	private function collectArticle($element) {
		$item = array();
		$item['uri'] = static::URI . $element->find('td', 1)->find('a', 0)->href;
		$item['title'] = $element->find('td', 1)->find('a', 0)->plaintext;
		$item['timestamp'] = DateTime::createFromFormat('Y/m/d', $element->find('td', 0)->plaintext)->getTimestamp();
		$item['content'] = $element->find('td', 2)->plaintext;
		$item['author'] = 'LaTeX3 Project';
		return $item;
	}

	public function getIcon(){
		return self::URI . '/favicon.ico';
	}
}
