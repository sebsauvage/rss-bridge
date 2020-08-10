<?php
class HaerringersSpottschauBridge extends BridgeAbstract {
	const MAINTAINER = 'mibe';
	const NAME = 'Härringers Spottschau Bridge';
	const URI = 'https://spottschau.com/';
	const CACHE_TIMEOUT = 86400; // 24h
	const DESCRIPTION = 'Returns the latest strip from the "Härringers Spottschau" comic.';

	public function collectData()
	{
		$html = getSimpleHTMLDOMCached(self::URI, self::CACHE_TIMEOUT)
			or returnServerError('Could not request ' . self::URI);

		$strip = $html->find('div.strip > a > img', 0)
			or returnServerError('Could not find the proper HTML element of the strip.');

		$imgurl = self::URI . $strip->src;

		$this->items[] = array(
			'uri' => self::URI,
			'title' => 'Strip der Woche',
			'content' => '<img src="' . $imgurl . '">',
			'enclosures' => array($imgurl),
			'author' => 'Christoph Härringer',
			'uid' => $imgurl,
			);
	}
}
