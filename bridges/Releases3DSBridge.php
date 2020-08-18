<?php
class Releases3DSBridge extends BridgeAbstract {

	const MAINTAINER = 'ORelio';
	const NAME = '3DS Scene Releases';
	const URI = 'http://www.3dsdb.com/';
	const URI_SWITCH = 'http://www.nswdb.com/';
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = 'Returns the newest scene releases for Nintendo 3DS or Switch.';
	const PARAMETERS = array(
		array(
			'console' => array(
				'name' => 'Console',
				'type' => 'list',
				'values' => array(
					'3DS' => '3ds',
					'Switch' => 'switch'
				)
			)
		)
	);

	public function collectData(){

		$baseUrl = self::URI;
		if ($this->getInput('console') == 'switch')
			$baseUrl = self::URI_SWITCH;
		$dataUrl = $baseUrl . 'xml.php';
		$xml = getContents($dataUrl)
			or returnServerError('Could not request URL: ' . $dataUrl);
		$limit = 0;

		foreach(array_reverse(explode('<release>', $xml)) as $element) {
			if($limit >= 5) {
				break;
			}

			if(strpos($element, '</release>') === false) {
				continue;
			}

			$releasename = extractFromDelimiters($element, '<releasename>', '</releasename>');
			if(empty($releasename)) {
				continue;
			}

			$id = extractFromDelimiters($element, '<id>', '</id>');
			$name = extractFromDelimiters($element, '<name>', '</name>');
			$publisher = extractFromDelimiters($element, '<publisher>', '</publisher>');
			$region = extractFromDelimiters($element, '<region>', '</region>');
			$group = extractFromDelimiters($element, '<group>', '</group>');
			$imagesize = extractFromDelimiters($element, '<imagesize>', '</imagesize>');
			$serial = extractFromDelimiters($element, '<serial>', '</serial>');
			$titleid = extractFromDelimiters($element, '<titleid>', '</titleid>');
			$imgcrc = extractFromDelimiters($element, '<imgcrc>', '</imgcrc>');
			$filename = extractFromDelimiters($element, '<filename>', '</filename>');
			$trimmedsize = extractFromDelimiters($element, '<trimmedsize>', '</trimmedsize>');
			$firmware = extractFromDelimiters($element, '<firmware>', '</firmware>');
			$type = extractFromDelimiters($element, '<type>', '</type>');
			$card = extractFromDelimiters($element, '<card>', '</card>');

			//Retrieve cover art and short desc from IGN?
			$ignResult = false;
			$ignDescription = '';
			$ignLink = '';
			$ignDate = time();
			$ignCoverArt = '';

			$ignSearchUrl = 'https://www.ign.com/search?q=' . urlencode($name);
			if($ignResult = getSimpleHTMLDOMCached($ignSearchUrl)) {
				$ignCoverArt = $ignResult->find('div.search-item-media', 0);
				$ignDesc = $ignResult->find('div.search-item-description', 0);
				$ignLink = $ignResult->find('div.search-item-sub-title', 0);
				$ignDate = $ignResult->find('span.publish-date', 0);
				if (is_object($ignCoverArt))
					$ignCoverArt = $ignCoverArt->find('img', 0);
				if (is_object($ignLink))
					$ignLink = $ignLink->find('a', 1);
				if (is_object($ignDate))
					$ignDate = strtotime(trim($ignDate->plaintext));
				if (is_object($ignCoverArt) && is_object($ignDesc) && is_object($ignLink)) {
					$ignDescription = '<div><img src="'
					. $ignCoverArt->src
					. '" /></div><div>'
					. $ignDesc->plaintext
					. ' <a href="'
					. $ignLink->href
					. '">More at IGN</a></div>';
				}
			}

			//Main section : Release description from 3DS database
			$releaseDescription = '<h3>Release Details</h3><b>Release ID: </b>' . $id
			. '<br /><b>Game Name: </b>' . $name
			. '<br /><b>Publisher: </b>' . $publisher
			. '<br /><b>Region: </b>' . $region
			. '<br /><b>Group: </b>' . $group
			. '<br /><b>Image size: </b>' . (intval($imagesize) / 8)
			. 'MB<br /><b>Serial: </b>' . $serial
			. '<br /><b>Title ID: </b>' . $titleid
			. '<br /><b>Image CRC: </b>' . $imgcrc
			. '<br /><b>File Name: </b>' . $filename
			. '<br /><b>Release Name: </b>' . $releasename
			. '<br /><b>Trimmed size: </b>' . intval(intval($trimmedsize) / 1048576)
			. 'MB<br /><b>Firmware: </b>' . $firmware
			. '<br /><b>Type: </b>' . $this->typeToString($type)
			. '<br /><b>Card: </b>' . $this->cardToString($card)
			. '<br />';

			//Build search links section to facilitate release search using search engines
			$releaseNameEncoded = urlencode(str_replace(' ', '+', $releasename));
			$searchLinkGoogle = 'https://google.com/?q=' . $releaseNameEncoded;
			$searchLinkDuckDuckGo = 'https://duckduckgo.com/?q=' . $releaseNameEncoded;
			$searchLinkQwant = 'https://lite.qwant.com/?q=' . $releaseNameEncoded . '&t=web';
			$releaseSearchLinks = '<h3>Search this release</h3><ul><li><a href="'
			. $searchLinkGoogle
			. '">Search using Google</a></li><li><a href="'
			. $searchLinkDuckDuckGo
			. '">Search using DuckDuckGo</a></li><li><a href="'
			. $searchLinkQwant
			. '">Search using Qwant</a></li></ul>';

			//Build and add final item with the above three sections
			$item = array();
			$item['title'] = $name;
			$item['author'] = $publisher;
			$item['timestamp'] = $ignDate;
			$item['enclosures'] = array($ignCoverArt);
			$item['uri'] = empty($ignLink) ? $searchLinkDuckDuckGo : $ignLink;
			$item['content'] = $ignDescription . $releaseDescription . $releaseSearchLinks;
			$this->items[] = $item;
			$limit++;
		}
	}

	private function typeToString($type){
		switch($type) {
			case 1: return '3DS Game';
			case 4: return 'eShop';
			default: return '??? (' . $type . ')';
		}
	}

	private function cardToString($card){
		switch($card) {
			case 1: return 'Regular (CARD1)';
			case 2: return 'NAND (CARD2)';
			default: return '??? (' . $card . ')';
		}
	}
}
