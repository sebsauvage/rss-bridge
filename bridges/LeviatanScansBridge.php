<?php

class LeviatanScansBridge extends BridgeAbstract {

	const MAINTAINER = 'tgkenney';
	const NAME = 'Leviatan Scans';
	const URI = 'https://leviatanscans.com';
	const DESCRIPTION = 'Gets the latest chapters from the Leviatan Scans website';

	const PARAMETERS = array(
		'Options' => array(
			'comic' => array(
				'type' => 'text',
				'name' => 'Comic ID (e.g. 68254-legend-of-the-northern-blade)',
				'title' => 'This is everything after /comics/ of the URL',
			)
		)
	);

	public function collectData()
	{
		$uri = self::URI
			. '/comics/'
			. $this->getInput('comic');

		$html = getSimpleHTMLDOM($uri) or returnServerError('Could not contact Leviatan Scans');

		foreach ($html->find('div[class=list-item col-sm-3 no-border]') as $chapter) {
			$item = array();

			$element = $chapter->find('div[class=flex]', 0)->find('a[class=item-author text-color]', 0);

			$item['uri'] = $element->href;
			$item['title'] = $html->find('h5[class=text-highlight]', 0)->innertext . trim($element->innertext);
			$item['timestamp'] = time();
			$item['author'] = self::NAME;

			$this->items[] = $item;
		}
	}
}
