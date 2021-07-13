<?php
class ScribdBridge extends BridgeAbstract {
	const NAME = 'Scribd Bridge';
	const URI = 'https://www.scribd.com';
	const DESCRIPTION = 'Returns documents uploaded by a user.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
		'profile' => array(
			'name' => 'Profile URL',
			'type' => 'text',
			'required' => true,
			'title' => 'Profile URL. Example: https://www.scribd.com/user/489040929/Michael-Honey',
			'exampleValue' => 'https://www.scribd.com/user/'
		),
	));

	const CACHE_TIMEOUT = 3600;

	private $profileUrlRegex = '/scribd\.com\/(user\/[0-9]+\/[\w-]+)\/?/';
	private $feedName = '';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$this->feedName = $html->find('div.header', 0)->plaintext;

		foreach($html->find('ul.document_cells > li') as $index => $li) {
			$item = array();

			$item['title'] = $li->find('div.under_title', 0)->plaintext;
			$item['uri'] = $li->find('a', 0)->href;
			$item['author'] = $li->find('span.uploader', 0)->plaintext;
			$item['uid'] = $li->find('a', 0)->href;

			$pageHtml = getSimpleHTMLDOMCached($item['uri'], 3600)
				or returnServerError('Could not request: ' . $item['uri']);

			$image = $pageHtml->find('meta[property="og:image"]', 0)->content;
			$description = $pageHtml->find('meta[property="og:description"]', 0)->content;

			foreach ($pageHtml->find('ul.interest_pills li') as $pills) {
				$item['categories'][] = $pills->plaintext;
			}

			$item['content'] = <<<EOD
<p>{$description}<p><p><img src="{$image}"></p>
EOD;

			$item['enclosures'][] = $image;

			$this->items[] = $item;

			if (count($this->items) >= 15) {
				break;
			}
		}
	}

	public function getName() {

		if ($this->feedName) {
			return $this->feedName . ' - Scribd';
		}

		return parent::getName();
	}

	public function getURI() {

		if (!is_null($this->getInput('profile'))) {
			preg_match($this->profileUrlRegex, $this->getInput('profile'), $user)
				or returnServerError('Could not extract user ID and name from given profile URL.');

			return self::URI . '/' . $user[1] . '/uploads';
		}

		return parent::getURI();
	}
}
