<?php

class SimpleLoginBridge extends BridgeAbstract {
	const NAME = 'SimpleLogin Blog';
	const URI = 'https://simplelogin.io';
	const DESCRIPTION = 'Gets the latest SimpleLogin blog posts';
	const MAINTAINER = 'tblyler';
	const CACHE_TIMEOUT = 3600; // 1 hour
	const BLOG_URI = 'https://simplelogin.io/blog';

	public function collectData() {
		$html = getSimpleHTMLDOMCached(self::BLOG_URI, self::CACHE_TIMEOUT)
			or returnServerError('Unable to load posts from "' . self::BLOG_URI . '"!');
		$html = defaultLinkTo($html, self::URI);

		// loop through each blog post div
		foreach ($html->find('div.mb-5') as $post) {
			// get the children for this post
			$post_children = $post->children();

			$this->items[] = array(
				'uri'       => $post_children[0]->href,
				'title'     => $post_children[0]->children(0)->innertext,
				'content'   => $post_children[1]->innertext,
				'timestamp' => trim(strstr($post_children[2]->innertext, ' -', true)),
			);
		}
	}
}
