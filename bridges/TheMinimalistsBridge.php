<?php
class TheMinimalistsBridge extends BridgeAbstract {
	const MAINTAINER = 'kovalevdo';
	const NAME = 'The Minimalists';
	const URI = 'https://www.theminimalists.com';
	const DESCRIPTION = 'Returns posts from the main page of theminimalists.com.';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not load data from ' . URI . '.');

		foreach($html->find('div.post') as $element) {
			$item = array();

			$uriElement = $element->find('a.entry-title-link', 0);
			$item['uri'] = $uriElement->href;
			$item['id'] = $item['uri'];
			$item['title'] = $uriElement->plaintext;

			$image = $element->find('a.wp-post-image-anchor img', 0);
			$content = $element->find('div.entry-content', 0)->innertext;
			$content = strip_tags($content, '<a><p><br><img><ul><ol><li><b><i>');
			$item['content'] = $image . trim($content);

			$this->items[] = $item;
		}
	}
}
