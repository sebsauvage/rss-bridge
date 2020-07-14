<?php
class YahooAuctionJapanBridge extends BridgeAbstract {

	const MAINTAINER = '8642335';
	const NAME = 'Yahoo Auction Japan';
	const URI = 'https://auctions.yahoo.co.jp/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns results from Yahoo Auction Japan.';

	const PARAMETERS = array( array(
		'qa' => array(
			'name' => 'Keyword (AND)',
			'title' => 'Find items with all of these keywords. Can be used with "Keyword (OR)" and "Keyword (NOT)".'
		),
		'qo' => array(
			'name' => 'Keyword (OR)',
			'title' => 'Find items with any of these keywords. Can be used with "Keyword (AND)" and "Keyword (NOT)".'
		),
		'qe' => array(
			'name' => 'Keyword (NOT)',
			'title' => 'Find items without any of these keywords. Can be used with "Keyword (AND)" and "Keyword (OR)".'
		),
		'method' => array(
			'name' => 'Search Method',
			'type' => 'list',
			'values' => array(
				'Title' => '0',
				'Title and Description' => '2',
				'Ambiguous' => '1'
			),
			'defaultValue' => '2',
			'title' => '"Ambiguous": Search item titles, and return results that match partial word.'
		)
	));

	public function collectData(){
		if (!($this->getInput('qa')) && !($this->getInput('qo'))) {
			returnClientError('Please enter keyword!');
		}

		$url = self::URI
			. 'search/search?va=' . urlencode($this->getInput('qa'))
			. '&vo=' . urlencode($this->getInput('qo'))
			. '&ve=' . urlencode($this->getInput('qe'))
			. '&ngrm=' . $this->getInput('method')
			. '&n=100&s1=new&o1=d';

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Could not request Yahoo Auction Japan at ' . $url);

		foreach($html->find('li.Product') as $element) {
			$item = array();
			$item['uri'] = $element->find('a.Product__imageLink', 0)->href;
			$item['title'] = $element->find('a.Product__titleLink', 0)->innertext;

			// Content
			$image = $element->find('img', 0);
			$price = $element->find('span.Product__priceValue', 0);
			$price = ($price) ? $price->innertext : '';
			$item['content'] = '<img src="' . $image->getAttribute('src') . '" /><br />' . $price;
			$this->items[] = $item;
		}
	}
}
