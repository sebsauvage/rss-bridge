<?php
class GooglePlayStoreBridge extends BridgeAbstract {
	const MAINTAINER = 'Yaman Qalieh';
	const NAME = 'Google Play Store';
	const URI = 'https://play.google.com/store/apps';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns the most recent version of an app with its changelog';

	const PARAMETERS = array(array(
		'id' => array(
			'name' => 'Application ID',
			'exampleValue' => 'com.ichi2.anki',
			'required' => true
		)
	));

	const INFORMATION_MAP = array(
		'Updated' => 'timestamp',
		'Current Version' => 'title',
		'Offered By' => 'author'
	);

	public function collectData() {
		$appuri = static::URI . '/details?id=' . $this->getInput('id');
		$html = getSimpleHTMLDOM($appuri)
			  or returnClientError('App not found.');

		$item['uri'] = $appuri;
		$item['content'] = $html->find('div[itemprop=description]', 1)->innertext;

		// Find other fields from Additional Information section
		foreach($html->find('.hAyfc') as $info) {
			$index = self::INFORMATION_MAP[$info->first_child()->plaintext];
			$item[$index] = $info->children(1)->plaintext;
		}

		$this->items[] = $item;
	}
}
