<?php
require_once('Shimmie2Bridge.php');

class Rule34pahealBridge extends Shimmie2Bridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Rule34paheal';
	const URI = 'https://rule34.paheal.net/';
	const DESCRIPTION = 'Returns images from given page';

	const PATHTODATA = '.shm-thumb';

	protected function getItemFromElement($element){
		$item = array();
		$item['uri'] = rtrim($this->getURI(), '/') . $element->find('.shm-thumb-link', 0)->href;
		$item['id'] = (int)preg_replace('/[^0-9]/', '', $element->getAttribute(static::IDATTRIBUTE));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('a', 1)->href;
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
