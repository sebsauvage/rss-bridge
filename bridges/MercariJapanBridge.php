<?php
class MercariJapanBridge extends BridgeAbstract {

	const MAINTAINER = '8642335';
	const NAME = 'Mercari Japan';
	const URI = 'https://www.mercari.com/jp/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns results from Mercari Japan.';

	const PARAMETERS = array( array(
		'q' => array(
			'name' => 'Keyword',
			'required' => true
		),
		'pricemin' => array(
			'name' => 'Min Price',
			'type' => 'number'
		),
		'pricemax' => array(
			'name' => 'Max Price',
			'type' => 'number'
		)
	));

	public function collectData(){
		$pmin = $this->getInput('pricemin');
		$pmax = $this->getInput('pricemax');
		if ($pmin && ($pmin < 0)) {
			returnClientError('Min price must not be negative!');
		}
		if ($pmax && ($pmax < 0)) {
			returnClientError('Max price must not be negative!');
		}
		if ($pmin && $pmax) {
			if ($pmax < $pmin) {
				returnClientError('Max price must be greater than min price!');
			}
		}

		$url = self::URI
			. 'search/?keyword=' . urlencode($this->getInput('q'))
			. '&price_min=' . $this->getInput('pricemin')
			. '&price_max=' . $this->getInput('pricemax')
			. '&sort_order=created_desc&status_on_sale=1';

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request Mercari Japan at ' . $url);

		foreach($html->find('section.items-box') as $element) {
			$item = array();

			// Sanitize URL
			$origurl = $element->find('a', 0)->href;
			$productid = extractFromDelimiters($origurl, 'jp/items/', '/');

			// Link to original size image
			$imageurl = 'https://static.mercdn.net/item/detail/orig/photos/' . $productid . '_1.jpg';

			$item['uri'] = 'https://item.mercari.com/jp/' . $productid . '/';
			$item['title'] = $element->find('h3', 0)->innertext;

			// Content
			$price = $element->find('div.items-box-price', 0);
			$price = ($price) ? $price->innertext : '';
			$item['content'] = '<img src="' . $imageurl . '" /><br />' . $price;
			$this->items[] = $item;
		}
	}
}
