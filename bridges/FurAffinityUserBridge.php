<?php
class FurAffinityUserBridge extends BridgeAbstract {
	const NAME = 'FurAffinity User Gallery';
	const URI = 'https://www.furaffinity.net';
	const MAINTAINER = 'CyberJacob';
	const DESCRIPTION = 'N.B. This bridge now requires login cookies in place of a username and password.' .
		' Please log into FA from a browser, and use the browser\'s developer tools function to get these.';
	const PARAMETERS = array(
		array(
			'searchUsername' => array(
				'name' => 'Search Username',
				'type' => 'text',
				'required' => true,
				'title' => 'Username to fetch the gallery for'
			),
			'aCookie' => array(
				'name' => 'Login cookie \'a\'',
				'type' => 'text',
				'required' => true
			),
			'bCookie' => array(
				'name' => 'Login cookie \'b\'',
				'type' => 'text',
				'required' => true
			)
		)
	);

	public function collectData() {
		$opt = array(CURLOPT_COOKIE => 'b=' . $this->getInput('bCookie') . '; a=' . $this->getInput('aCookie'));

		$url = self::URI . '/gallery/' . $this->getInput('searchUsername');

		$html = getSimpleHTMLDOM($url, array(), $opt)
			or returnServerError('Could not load the user\'s gallery page.');

		$submissions = $html->find('section[id=gallery-gallery]', 0)->find('figure');
		foreach($submissions as $submission) {
			$item = array();
			$item['title'] = $submission->find('figcaption', 0)->find('a', 0)->plaintext;

			$thumbnail = $submission->find('a', 0);
			$thumbnail->href = self::URI . $thumbnail->href;

			$item['content'] = $submission->find('a', 0);

			$this->items[] = $item;
		}
	}

	public function getName() {
		return self::NAME . ' for ' . $this->getInput('searchUsername');
	}

	public function getURI() {
		return self::URI . '/user/' . $this->getInput('searchUsername');
	}
}
