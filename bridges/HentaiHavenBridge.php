<?php
class HentaiHavenBridge extends BridgeAbstract {

	const MAINTAINER = 'albirew';
	const NAME = 'Hentai Haven';
	const URI = 'https://hentaihaven.co/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns releases from Hentai Haven';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request Hentai Haven.');

		foreach($html->find('div.zoe-grid') as $element) {
			$item = array();
			$item['uri'] = $element->find('div.brick-content h3 a', 0)->href;
			$thumbnailUri = $element->find('a.thumbnail-image img', 0)->getAttribute('data-src');
			$item['title'] = mb_convert_encoding(
				trim($element->find('div.brick-content h3 a', 0)->innertext),
				'UTF-8',
				'HTML-ENTITIES'
			);

			$item['tags'] = $element->find('div.oFlyout_bg div.oFlyout div.flyoutContent span.tags', 0)->plaintext;
			$item['content'] = 'Tags: '
			. $item['tags']
			. '<br><br><a href="'
			. $item['uri']
			. '"><img width="300" height="169" src="'
			. $thumbnailUri
			. '" /></a><br>'
			. $element->find('div.oFlyout_bg div.oFlyout div.flyoutContent p.description', 0)->innertext;

			$this->items[] = $item;
		}
	}
}
