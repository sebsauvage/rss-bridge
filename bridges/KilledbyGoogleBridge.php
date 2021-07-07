<?php
class KilledbyGoogleBridge extends BridgeAbstract {
	const NAME = 'Killed by Google Bridge';
	const URI = 'https://killedbygoogle.com';
	const DESCRIPTION = 'Returns list of recently discontinued Google services, products, devices, and apps.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array();

	const CACHE_TIMEOUT = 3600;

	public function collectData() {

		$json = getContents(self::URI . '/graveyard.json')
			or returnServerError('Could not request: ' . self::URI . '/graveyard.json');

		$this->handleJson($json);
		$this->orderItems();
		$this->limitItems();
	}

	/**
	 * Handle JSON
	 */
	private function handleJson($json) {

		$graveyard = json_decode($json, true);

		foreach($graveyard as $tombstone) {
			$item = array();

			$openDate = new DateTime($tombstone['dateOpen']);
			$closeDate = new DateTime($tombstone['dateClose']);
			$currentDate = new DateTime();

			$yearOpened = $openDate->format('Y');
			$yearClosed = $closeDate->format('Y');

			if ($closeDate > $currentDate) {
				continue;
			}

			$item['title'] = $tombstone['name'] . ' (' . $yearOpened . ' - ' . $yearClosed . ')';
			$item['uid'] = $tombstone['slug'];
			$item['timestamp'] = strtotime($tombstone['dateClose']);

			$item['content'] = <<<EOD
<p>{$tombstone['description']}</p><p><a href="{$tombstone['link']}">{$tombstone['link']}</p>
EOD;

			$item['enclosures'][] = self::URI . '/assets/tombstone.svg';

			$this->items[] = $item;
		}
	}

	/**
	 * Order items by timestamp
	 */
	private function orderItems() {

		$sort = array();

		foreach ($this->items as $key => $item) {
			$sort[$key] = $item['timestamp'];
		}

		array_multisort($sort, SORT_DESC, $this->items);
		$this->items = array_slice($this->items, 0, 15);
	}

	/**
	 * Limit items to 15
	 */
	private function limitItems() {
		$this->items = array_slice($this->items, 0, 15);
	}
}
