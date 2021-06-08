<?php

/* This is a mashup of FlickrExploreBridge by sebsauvage and FlickrTagBridge
 * by erwang, providing the functionality of both in one.
 */
class FlickrBridge extends BridgeAbstract {

	const MAINTAINER = 'logmanoriginal';
	const NAME = 'Flickr Bridge';
	const URI = 'https://www.flickr.com/';
	const CACHE_TIMEOUT = 21600; // 6 hours
	const DESCRIPTION = 'Returns images from Flickr';

	const PARAMETERS = array(
		'Explore' => array(),
		'By keyword' => array(
			'q' => array(
				'name' => 'Keyword',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert keyword',
				'exampleValue' => 'bird'
			),
			'media' => array(
				'name' => 'Media',
				'type' => 'list',
				'values' => array(
					'All (Photos & videos)' => 'all',
					'Photos' => 'photos',
					'Videos' => 'videos',
				),
				'defaultValue' => 'all',
			),
			'sort' => array(
				'name' => 'Sort By',
				'type' => 'list',
				'values' => array(
					'Relevance' => 'relevance',
					'Date uploaded' => 'date-posted-desc',
					'Date taken' => 'date-taken-desc',
					'Interesting' => 'interestingness-desc',
				),
				'defaultValue' => 'relevance',
			)
		),
		'By username' => array(
			'u' => array(
				'name' => 'Username',
				'type' => 'text',
				'required' => true,
				'title' => 'Insert username (as shown in the address bar)',
				'exampleValue' => 'flickr'
			),
			'content' => array(
				'name' => 'Content',
				'type' => 'list',
				'values' => array(
					'Uploads' => 'uploads',
					'Favorites' => 'faves',
				),
				'defaultValue' => 'uploads',
			),
			'media' => array(
				'name' => 'Media',
				'type' => 'list',
				'values' => array(
					'All (Photos & videos)' => 'all',
					'Photos' => 'photos',
					'Videos' => 'videos',
				),
				'defaultValue' => 'all',
			),
			'sort' => array(
				'name' => 'Sort By',
				'type' => 'list',
				'values' => array(
					'Relevance' => 'relevance',
					'Date uploaded' => 'date-posted-desc',
					'Date taken' => 'date-taken-desc',
					'Interesting' => 'interestingness-desc',
				),
				'defaultValue' => 'date-posted-desc',
			)
		)
	);

	private $username = '';

	public function collectData() {

		switch($this->queriedContext) {

		case 'Explore':
			$filter = 'photo-lite-models';
			$html = getSimpleHTMLDOM($this->getURI())
				or returnServerError('Could not request Flickr.');
			break;

		case 'By keyword':
			$filter = 'photo-lite-models';
			$html = getSimpleHTMLDOM($this->getURI())
				or returnServerError('No results for this query.');
			break;

		case 'By username':
			//$filter = 'photo-models';
			$filter = 'photo-lite-models';
			$html = getSimpleHTMLDOM($this->getURI())
				or returnServerError('Requested username can\'t be found.');

				$this->username = $this->getInput('u');

				if ($html->find('span.search-pill-name', 0)) {
					$this->username = $html->find('span.search-pill-name', 0)->plaintext;
				}
			break;

		default:
			returnClientError('Invalid context: ' . $this->queriedContext);

		}

		$model_json = $this->extractJsonModel($html);
		$photo_models = $this->getPhotoModels($model_json, $filter);

		foreach($photo_models as $model) {
			$item = array();

			/* Author name depends on scope. On a keyword search the
			* author is part of the picture data. On a username search
			* the author is part of the owner data.
			*/
			if(array_key_exists('username', $model)) {
				$item['author'] = urldecode($model['username']);
			} elseif (array_key_exists('owner', reset($model_json)[0])) {
				$item['author'] = urldecode(reset($model_json)[0]['owner']['username']);
			}

			$item['title'] = urldecode((array_key_exists('title', $model) ? $model['title'] : 'Untitled'));
			$item['uri'] = self::URI . 'photo.gne?id=' . $model['id'];

			$description = (array_key_exists('description', $model) ? $model['description'] : '');

			$item['content'] = '<a href="'
			. $item['uri']
			. '"><img src="'
			. $this->extractContentImage($model)
			. '" style="max-width: 640px; max-height: 480px;"/></a><br><p>'
			. urldecode($description)
			. '</p>';

			$item['enclosures'] = $this->extractEnclosures($model);

			$this->items[] = $item;

		}

	}

	public function getURI() {

		switch($this->queriedContext) {
			case 'Explore':
				return self::URI . 'explore';
				break;
			case 'By keyword':
				return self::URI . 'search/?q=' . urlencode($this->getInput('q'))
					. '&sort=' . $this->getInput('sort') . '&media=' . $this->getInput('media');
				break;
			case 'By username':
				$uri = self::URI . 'search/?user_id=' . urlencode($this->getInput('u'))
					. '&sort=date-posted-desc&media=' . $this->getInput('media');

				if ($this->getInput('content') === 'faves') {
					return $uri . '&faves=1';
				}

				return $uri;
				break;

			default:
				return parent::getURI();
		}
	}

	public function getName() {

		switch($this->queriedContext) {
			case 'Explore':
				return 'Explore - ' . self::NAME;
				break;
			case 'By keyword':
				return $this->getInput('q') . ' - keyword - ' . self::NAME;
				break;
			case 'By username':

				if ($this->getInput('content') === 'faves') {
					return $this->username . ' - favorites - ' . self::NAME;
				}

				return $this->username . ' - ' . self::NAME;
				break;

			default:
				return parent::getName();
		}

		return parent::getName();
	}

	private function extractJsonModel($html) {

		// Find SCRIPT containing JSON data
		$model = $html->find('.modelExport', 0);
		$model_text = $model->innertext;

		// Find start and end of JSON data
		$start = strpos($model_text, 'modelExport:') + strlen('modelExport:');
		$end = strpos($model_text, 'auth:') - strlen('auth:');

		// Extract JSON data, remove trailing comma
		$model_text = trim(substr($model_text, $start, $end - $start));
		$model_text = substr($model_text, 0, strlen($model_text) - 1);

		return json_decode($model_text, true);

	}

	private function getPhotoModels($json, $filter) {

		// The JSON model contains a "legend" array, where each element contains
		// the path to an element in the "main" object
		$photo_models = array();

		foreach($json['legend'] as $legend) {

			$photo_model = $json['main'];

			foreach($legend as $element) { // Traverse tree
				$photo_model = $photo_model[$element];
			}

			// We are only interested in content
			if($photo_model['_flickrModelRegistry'] === $filter) {
				$photo_models[] = $photo_model;
			}

		}

		return $photo_models;

	}

	private function extractEnclosures($model) {

		$areas = array();

		foreach($model['sizes'] as $size) {
			$areas[$size['width'] * $size['height']] = $size['url'];
		}

		return array($this->fixURL(max($areas)));

	}

	private function extractContentImage($model) {

		$areas = array();
		$limit = 320 * 240;

		foreach($model['sizes'] as $size) {

			$image_area = $size['width'] * $size['height'];

			if($image_area >= $limit) {
				$areas[$image_area] = $size['url'];
			}

		}

		return $this->fixURL(min($areas));

	}

	private function fixURL($url) {

		// For some reason the image URLs don't include the protocol (https)
		if(strpos($url, '//') === 0) {
			$url = 'https:' . $url;
		}

		return $url;

	}
}
