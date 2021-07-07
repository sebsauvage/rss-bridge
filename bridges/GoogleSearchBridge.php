<?php
/**
* Returns the 100 most recent links in results in past year, sorting by date (most recent first).
* Example:
* http://www.google.com/search?q=sebsauvage&num=100&complete=0&tbs=qdr:y,sbd:1
*    complete=0&num=100 : get 100 results
*    qdr:y : in past year
*    sbd:1 : sort by date (will only work if qdr: is specified)
*/
class GoogleSearchBridge extends BridgeAbstract {

	const MAINTAINER = 'sebsauvage';
	const NAME = 'Google search';
	const URI = 'https://www.google.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns most recent results from Google search.';

	const PARAMETERS = array(array(
		'q' => array(
			'name' => 'keyword',
			'required' => true
		)
	));

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('No results for this query.');

		$emIsRes = $html->find('div[id=res]', 0);

		if(!is_null($emIsRes)) {
			foreach($emIsRes->find('div[class=g]') as $element) {
				$item = array();

				$t = $element->find('a[href]', 0)->href;
				$item['uri'] = htmlspecialchars_decode($t);
				$item['title'] = $element->find('h3', 0)->plaintext;
				$item['content'] = $element->find('span[class=aCOpRe]', 0)->plaintext;

				if ($element->find('span[class=f]', 0)) {
					$item['timestamp'] = str_replace('— ', '', $element->find('span[class=f]', 0)->plaintext);
				}

				$this->items[] = $item;
			}
		}
	}

	public function getURI() {
		if (!is_null($this->getInput('q'))) {
			return self::URI
				. 'search?q='
				. urlencode($this->getInput('q'))
				. '&num=100&complete=0&tbs=qdr:y,sbd:1';
		}

		return parent::getURI();
	}

	public function getName(){
		if(!is_null($this->getInput('q'))) {
			return $this->getInput('q') . ' - Google search';
		}

		return parent::getName();
	}
}
