<?php
require_once('DanbooruBridge.php');

class Shimmie2Bridge extends DanbooruBridge {

	const NAME = 'Shimmie v2';
	const URI = 'https://shimmie.shishnet.org/v2/';
	const DESCRIPTION = 'Returns images from given page';

	const PATHTODATA = '.shm-thumb-link';
	const IDATTRIBUTE = 'data-post-id';

	protected function getFullURI(){
		return $this->getURI()
		. 'post/list/'
		. $this->getInput('t')
		. '/'
		. $this->getInput('p');
	}

	protected function getItemFromElement($element){
		$item = array();
		$item['uri'] = $this->getURI() . $element->href;
		$item['id'] = (int)preg_replace('/[^0-9]/', '', $element->getAttribute(static::IDATTRIBUTE));
		$item['timestamp'] = time();
		$thumbnailUri = $this->getURI() . $element->find('img', 0)->src;
		$item['categories'] = explode(' ', $element->getAttribute('data-tags'));
		$item['title'] = $this->getName() . ' | ' . $item['id'];
		$item['content'] = '<a href="'
		. $item['uri']
		. '"><img src="'
		. $thumbnailUri
		. '" /></a><br>Tags: '
		. $element->getAttribute('data-tags');

		return $item;
	}
}
