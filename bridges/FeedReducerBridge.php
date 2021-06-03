<?php
class FeedReducerBridge extends FeedExpander {

	const MAINTAINER = 'mdemoss';
	const NAME = 'Feed Reducer';
	const URI = '';
	const DESCRIPTION = 'Choose a percentage of a feed you want to see.';
	const PARAMETERS = array( array(
		'url' => array(
			'name' => 'Feed URI',
			'required' => true
		),
		'percentage' => array(
			'name' => 'percentage',
			'type' => 'number',
			'exampleValue' => 50,
			'required' => true
		)
	));
	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		if(preg_match('#^http(s?)://#i', $this->getInput('url'))) {
			$this->collectExpandableDatas($this->getInput('url'));
		} else {
			throw new Exception('URI must begin with http(s)://');
		}
	}

	public function getItems(){
		$filteredItems = array();
		$intPercentage = (int)preg_replace('/[^0-9]/', '', $this->getInput('percentage'));

		foreach ($this->items as $thisItem) {
			// The URL is included in the hash:
			//  - so you can change the output by adding a local-part to the URL
			//  - so items with the same URI in different feeds won't be correlated

			// $pseudoRandomInteger will be a 16 bit unsigned int mod 100.
			// This won't be uniformly distributed 1-100, but should be close enough.

			$pseudoRandomInteger = unpack(
				'S', // unsigned 16-bit int
				hash( 'sha256', $thisItem['uri'] . '::' . $this->getInput('url'), true )
			)[1] % 100;

			if ($pseudoRandomInteger < $intPercentage) {
				$filteredItems[] = $thisItem;
			}
		}

		return $filteredItems;
	}

	public function getName(){
		$trimmedPercentage = preg_replace('/[^0-9]/', '', $this->getInput('percentage'));
		return parent::getName() . ' [' . $trimmedPercentage . '%]';
	}
}
