<?php
class WallpaperflareBridge extends XPathAbstract {
	const NAME = 'Wallpaperflare';
	const URI = 'https://wallpaperflare.com';
	const DESCRIPTION = 'Wallpaperflare is a provider for Wallpapers on nearly every topic, especially for Anime';
	const MAINTAINER = 'dhuschde';
	const PARAMETERS = array(
		'' => array(
			'search' => array(
				'name' => 'Search',
				'required' => true
		)
	));
	const CACHE_TIMEOUT = 3600; //1 hour
	const XPATH_EXPRESSION_ITEM = './/figure';
	const XPATH_EXPRESSION_ITEM_TITLE = './/img/@title';
	const XPATH_EXPRESSION_ITEM_CONTENT = '';
	const XPATH_EXPRESSION_ITEM_URI = './/a[@itemprop="url"]/@href';
	const XPATH_EXPRESSION_ITEM_AUTHOR = '/html[1]/body[1]/main[1]/section[1]/h1[1]';
	const XPATH_EXPRESSION_ITEM_TIMESTAMP = '';
	const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@data-src';
	const XPATH_EXPRESSION_ITEM_CATEGORIES = './/figcaption[@itemprop="caption description"]';
	const SETTING_FIX_ENCODING = false;

	protected function getSourceUrl(){
		return 'https://www.wallpaperflare.com/search?wallpaper=' . $this->getInput('search');
	}

	public function getIcon() {
		return 'https://www.google.com/s2/favicons?domain=wallpaperflare.com/';
	}

	public function getName() {
		if(!is_null($this->getInput('search'))) {
		return 'Wallpaperflare - ' . $this->getInput('search');
		} else {
		return 'Wallpaperflare';
		}
	}
}
