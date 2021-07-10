<?php

class UnsplashBridge extends BridgeAbstract
{
	const MAINTAINER = 'nel50n, langfingaz';
	const NAME = 'Unsplash Bridge';
	const URI = 'https://unsplash.com/';
	const CACHE_TIMEOUT = 43200; // 12h
	const DESCRIPTION = 'Returns the latest photos from Unsplash';

	const PARAMETERS = array(array(
		'u' => array(
			'name' => 'Filter by username (optional)',
			'exampleValue' => 'SomeUser',
			'type' => 'text',
			'defaultValue' => 'unsplash'
		),
		'm' => array(
			'name' => 'Max number of photos',
			'type' => 'number',
			'defaultValue' => 20,
			'required' => true
		),
		'prev_q' => array(
			'name' => 'Preview quality',
			'type' => 'list',
			'values' => array(
				'full' => 'full',
				'regular' => 'regular',
				'small' => 'small',
				'thumb' => 'thumb',
			),
			'defaultValue' => 'regular',
			'required' => true
		),
		'w' => array(
			'name' => 'Max download width (optional)',
			'exampleValue' => 1920,
			'type' => 'number',
			'defaultValue' => 1920,
		),
		'jpg_q' => array(
			'name' => 'Max JPEG quality (optional)',
			'exampleValue' => 75,
			'type' => 'number',
			'defaultValue' => 75,
		)
	));

	public function collectData()
	{
		$filteredUser = $this->getInput('u');
		$width = $this->getInput('w');
		$max = $this->getInput('m');
		$previewQuality = $this->getInput('prev_q');
		$jpgQuality = $this->getInput('jpg_q');

		$url = 'https://unsplash.com/napi';
		if (strlen($filteredUser) > 0) $url .= '/users/' . $filteredUser;
		$url .= '/photos?page=1&per_page=' . $max;

		$api_response = getContents($url)
		or returnServerError('Could not request Unsplash API.');
		$json = json_decode($api_response, true);

		foreach ($json as $json_item) {
			$item = array();

			// Get image URI
			$uri = $json_item['urls']['raw'] . '&fm=jpg';
			if ($jpgQuality > 0) $uri .= '&q=' . $jpgQuality;
			if ($width > 0) $uri .= '&w=' . $width . '&fit=max';
			$uri .= '.jpg'; // only for format hint
			$item['uri'] = $uri;

			// Get title from description
			if (is_null($json_item['description'])) {
				$item['title'] = 'Unsplash picture from ' . $json_item['user']['name'];
			} else {
				$item['title'] = $json_item['description'];
			}

			$item['timestamp'] = $json_item['created_at'];

			$content = 'User: <a href="'
				. $json_item['user']['links']['html']
				. '">@'
				. $json_item['user']['username']
				. '</a>';
			if (!is_null($json_item['location']['name'])) {
				$content .= ' | Location: ' . $json_item['location']['name'];
			}
			$content .= ' | Image on <a href="'
				. $json_item['links']['html']
				. '">Unsplash</a><br><a href="'
				. $uri
				. '"><img src="'
				. $json_item['urls'][$previewQuality]
				. '" alt="Image from '
				. $filteredUser
				. '" /></a>';
			$item['content'] = $content;

			$this->items[] = $item;
		}
	}

	public function getName()
	{
		$filteredUser = $this->getInput('u');
		if (strlen($filteredUser) > 0) {
			return $filteredUser . ' - ' . self::NAME;
		} else {
			return self::NAME;
		}
	}
}
