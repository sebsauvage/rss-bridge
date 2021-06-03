<?php
class TheCodingLoveBridge extends BridgeAbstract {

	const MAINTAINER = 'superbaillot.net';
	const NAME = 'The Coding Love';
	const URI = 'https://thecodinglove.com/';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'The Coding Love';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request The Coding Love.');

		foreach($html->find('article.blog-post') as $element) {
			$item = array();
			$temp = $element->find('h1 a', 0);

			$title = $temp->innertext;
			$url = $temp->href;

			$temp = $element->find('div.blog-post-content', 0);

			// retrieve .gif instead of static .jpg
			$images = $temp->find('p.e img');
			foreach($images as $image) {
				$img_src = str_replace('.jpg', '.gif', $image->src);
				$image->src = $img_src;
			}
			$content = $temp->innertext;

			$temp = $element->find('div.post-meta-info', 0);
			$author = $temp->find('span', 0);
			$item['author'] = $author->innertext;

			$item['content'] = trim($content);
			$item['uri'] = $url;
			$item['title'] = trim($title);

			$this->items[] = $item;
		}
	}
}
