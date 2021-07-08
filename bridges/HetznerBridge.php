<?php
class HetznerBridge extends BridgeAbstract {

	const MAINTAINER = 'jlelse';
	const NAME = 'Hetzner News';
	const URI = 'https://www.hetzner.com/news/';
	const DESCRIPTION = 'Get news from Hetzner';
	const CACHE_TIMEOUT = 21600; // 6h

	const PARAMETERS = array();

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request Hetzner News: ' . self::URI);
		foreach(array_slice($html->find('ul.list-unstyled.list-card li'), 0, 5) as $element) {
			$url = 'https://www.hetzner.com' . $element->find('a', 0)->href;
			$title = $element->find('h4', 0)->plaintext;
			$date = strtotime($element->find('p small', 0)->plaintext);

			$contenthtml = getSimpleHTMLDOM($url) or returnServerError('Could not request Hetzner News: ' . $url);
			$content = $contenthtml->find('ul.list-unstyled.list-card li p');
			array_shift($content);
			array_shift($content);

			$item = array();
			$item['uri'] = $url;
			$item['title'] = $title;
			$item['author'] = 'Hetzner';
			$item['timestamp'] = $date;
			$item['content'] = implode('', $content);
			$this->items[] = $item;
		}
	}
}
