<?php
class ZLibraryBridge extends BridgeAbstract {
	const MAINTAINER = 'DevonHess';
	const NAME = 'Z-Library';
	const URI = 'https://z-lib.org/';
	const CACHE_TIMEOUT = 900; // 15m
	const DESCRIPTION = 'Returns books and articles from the Z-Library project';
	const PARAMETERS = array(
		'global' => array(
			'host' => array(
				'name' => 'Type',
				'type' => 'list',
				'defaultValue' => 'b-ok.cc',
				'values' => array(
					'Books' => 'b-ok.cc',
					'Articles' => 'booksc.xyz'
				)
			),
			'yearFrom' => array(
				'name' => 'Year from',
				'type' => 'number'
			),
			'yearTo' => array(
				'name' => 'Year to',
				'type' => 'number'
			),
			'language' => array(
				'name' => 'Language',
				'type' => 'list',
				'title' => 'Only works for books',
				'defaultValue' => 'english',
				'values' => array(
					'Any Language' => '',
					'Afrikaans' => 'afrikaans',
					'Albanian' => 'albanian',
					'Arabic' => 'arabic',
					'Armenian' => 'armenian',
					'Azerbaijani' => 'azerbaijani',
					'Bashkir' => 'bashkir',
					'Belarusian' => 'belarusian',
					'Bengali' => 'bengali',
					'Berber' => 'berber',
					'Bulgarian' => 'bulgarian',
					'Catalan' => 'catalan',
					'Chinese' => 'chinese',
					'Crimean Tatar' => 'crimean',
					'Croatian' => 'croatian',
					'Czech' => 'czech',
					'Danish' => 'danish',
					'Dutch' => 'dutch',
					'English' => 'english',
					'Esperanto' => 'esperanto',
					'Finnish' => 'finnish',
					'French' => 'french',
					'Georgian' => 'georgian',
					'German' => 'german',
					'Greek' => 'greek',
					'Hebrew' => 'hebrew',
					'Hindi' => 'hindi',
					'Hungarian' => 'hungarian',
					'Icelandic' => 'icelandic',
					'Indigenous' => 'indigenous',
					'Indonesian' => 'indonesian',
					'Italian' => 'italian',
					'Japanese' => 'japanese',
					'Kazakh' => 'kazakh',
					'Kirghiz' => 'kirghiz',
					'Korean' => 'korean',
					'Latin' => 'latin',
					'Latvian' => 'latvian',
					'Lithuanian' => 'lithuanian',
					'Malayalam' => 'malayalam',
					'Marathi' => 'marathi',
					'Mongolian' => 'mongolian',
					'Nepali' => 'nepali',
					'Norwegian' => 'norwegian',
					'Odia' => 'odia',
					'Persian' => 'persian',
					'Polish' => 'polish',
					'Portuguese' => 'portuguese',
					'Romanian' => 'romanian',
					'Russian' => 'russian',
					'Sanskrit' => 'sanskrit',
					'Serbian' => 'serbian',
					'Sinhala' => 'sinhala',
					'Slovak' => 'slovak',
					'Slovenian' => 'slovenian',
					'Somali' => 'somali',
					'Spanish' => 'spanish',
					'Swahili' => 'swahili',
					'Swedish' => 'swedish',
					'Tajik' => 'tajik',
					'Tamil' => 'tamil',
					'Tatar' => 'tatar',
					'Turkish' => 'turkish',
					'Ukrainian' => 'ukrainian',
					'Urdu' => 'urdu',
					'Uzbek' => 'uzbek',
					'Vietnamese' => 'vietnamese'
				)
			),
			'extension' => array(
				'name' => 'Extension',
				'type' => 'list',
				'title' => 'Only works for books',
				'defaultValue' => 'Any Extention',
				'values' => array(
					'Any Extention' => '',
					'pdf' => 'pdf',
					'epub' => 'epub',
					'djvu' => 'djvu',
					'fb2' => 'fb2',
					'txt' => 'txt',
					'rar' => 'rar',
					'mobi' => 'mobi',
					'lit' => 'lit',
					'doc' => 'doc',
					'rtf' => 'rtf',
					'azw3' => 'azw3'
				)
			),
			'order' => array(
				'name' => 'Sort order',
				'type' => 'list',
				'defaultValue' => 'date',
				'values' => array(
					'Most Popular' => '',
					'Best Match' => 'bestmatch',
					'Recently added' => 'date',
					'By Title (A-Z)' => 'titleA',
					'By Title (Z-A)' => 'title',
					'By Year' => 'year',
					'File Size ↓' => 'filesize',
					'File Size ↑' => 'filesizeA'
				)
			)
		),
		'By filter' => array(),
		'By search' => array(
			'search' => array(
				'name' => 'Search',
				'required' => true
			),
			'exact' => array(
				'name' => 'Exact matching',
				'type' => 'checkbox'
			)
		)
	);
	private $name;
	private $uri;

	public function detectParameters($url) {
		$params = array();

		$regex = '/https:\/\/(b-ok\.cc|booksc\.xyz)\/s\/(?:(.+)\/)?\?';
		$regex .= '(?:&?e=([^&]*))?(?:&?yearFrom=([^&]*))?';
		$regex .= '(?:&?yearTo=([^&]*))?(?:&?language=([^&]*))?';
		$regex .= '(?:&?extension=([^&]*))?(?:&?order=([^&]*))?/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['host'] = urldecode($matches[1]);
			$params['search'] = urldecode($matches[2]);
			$params['exact'] = urldecode($matches[3]);
			$params['yearFrom'] = urldecode($matches[4]);
			$params['yearTo'] = urldecode($matches[5]);
			$params['language'] = urldecode($matches[6]);
			$params['extension'] = urldecode($matches[7]);
			$params['order'] = urldecode($matches[8]);
			return $params;
		}

		$regex = '/https:\/\/(b-ok\.cc|booksc\.xyz)\//';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['host'] = urldecode($matches[1]);
			return $params;
		}

		$regex = '/https:\/\/z-lib\.org\//';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['host'] = 'b-ok.cc';
			return $params;
		}

		return null;
	}

	public function getName() {
		if($this->name) {
			return $this->name;
		} else {
			return self::NAME;
		}
	}

	public function getURI() {
		if($this->uri) {
			return $this->uri;
		} else {
			return self::URI;
		}
	}

	public function collectData() {
		$host = $this->getInput('host');
		$search = $this->getInput('search');
		$exact = $this->getInput('exact');
		$yearFrom = $this->getInput('yearFrom');
		$yearTo = $this->getInput('yearTo');
		$language = $this->getInput('language');
		$extension = $this->getInput('extension');
		$order = $this->getInput('order');

		$root = 'https://' . $host .
			'/s/' . ($search ? $search . '/' : '') .
			'?e=' . $exact .
			'&yearFrom=' . $yearFrom .
			'&yearTo=' . ($yearTo ? $yearTo : '9999') .
			'&language=' . $language .
			'&extension=' . $extension .
			'&order=' . $order;

		$html = getSimpleHTMLDOM($root)
			or returnServerError('Could not request ' . self::URI);

		$this->name = 'Z-Library' . ($search ? ': ' . $search : '');
		$this->uri = $root;

		foreach($html->find('.resItemBox') as $item) {
			$uri = 'https://' . $host .
				$item->find('h3 > a', 0)->href;
			$title = $item->find('h3 > a', 0)->innertext;
			$author = strip_tags($item->find('.authors',
				0)->innertext);
			$timestamp = '';

			if($host == 'b-ok.cc') {
				$image = str_replace('100', '',
					$item->find('.cover',
					0)->getAttribute('data-src'));
			} else {
				$image = '';
			}

			$content = defaultLinkTo(($image ? '<a href="' . $uri .
				'"><img src="' . $image .
				'" style="max-Width: 299px"></a><br>' : '') .
				$item->find('h3 > a', 0)->outertext . '<br>' .
				$item->find('.authors', 0)->innertext . '<br>',
				'https://' . $host);

			foreach($item->find('.bookDetailsBox > div') as
				$detail) {
				$content .= '<br>' .
					$detail->find('.property_label',
					0)->innertext . ' ' .
					defaultLinkTo($detail->find(
						'.property_value',
						0)->innertext, 'https://' .
						$host);
			}

			$item = array();
			$item['uri'] = $uri;
			$item['title'] = $title;
			$item['author'] = $author;
			$item['timestamp'] = $timestamp;
			$item['content'] = $content;
			$this->items[] = $item;
		}
	}
}
