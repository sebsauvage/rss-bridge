<?php //todo : one array to merge sort, date, duration ;)
class RumbleBridge extends BridgeAbstract {

	const MAINTAINER = 'sudwebdesign';
	const NAME = 'RumbleBridge';
	const URI = 'https://rumble.com/';
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = 'Returns the newest videos by username/channel/category or search newest videos/channels';

	const PARAMETERS = array(
		'By username' => array(
			'u' => array(
				'name' => 'Username',
				'exampleValue' => 'test',
				'required' => true
			),
			'sort' => array(
				'type' => 'list',
				'name' => 'Sort by',
				'values' => array(
					'Most recent' => '',
					'Shares' => 'shares',
					'Views' => 'views',
					'Virality' => 'virality',
				)
			),
			'date' => array(
				'type' => 'list',
				'name' => 'Date',
				'values' => array(
					'All Time' => '',
					'Last year' => 'this-year',
					'Last month' => 'this-month',
					'Last week' => 'this-week',
					'Today' => 'today'
				)
			),
			'duration' => array(
				'type' => 'list',
				'name' => 'Duration',
				'values' => array(
					'All' => '',
					'Short' => 'short',
					'Long' => 'long'
				)
			)
		),

		'By channel id' => array(
			'c' => array(
				'name' => 'Channel id',
				'exampleValue' => 'c-123456',
				'required' => true
			),
			'sort' => array(
				'type' => 'list',
				'name' => 'Sort by',
				'values' => array(
					'Most recent' => '',
					'Shares' => 'shares',
					'Views' => 'views',
					'Virality' => 'virality',
				)
			),
			'date' => array(
				'type' => 'list',
				'name' => 'Date',
				'values' => array(
					'All Time' => '',
					'Last year' => 'this-year',
					'Last month' => 'this-month',
					'Last week' => 'this-week',
					'Today' => 'today'
				)
			),
			'duration' => array(
				'type' => 'list',
				'name' => 'Duration',
				'values' => array(
					'All' => '',
					'Short' => 'short',
					'Long' => 'long'
				)
			)
		),

		'By search' => array(
			's' => array(
				'name' => 'Keywords',
				'exampleValue' => 'key words',
				'required' => true
			),
			'what' => array(
				'type' => 'list',
				'name' => 'What',
				'values' => array(
					'Videos' => 'video',
					'Channels' => 'channel'
				)
			),
			'sort' => array(
				'type' => 'list',
				'name' => 'Sort by',
				'values' => array(
					'Most recent' => '',
					'Shares' => 'shares',
					'Views' => 'views',
					'Virality' => 'virality',
				)
			),
			'date' => array(
				'type' => 'list',
				'name' => 'Date',
				'values' => array(
					'All Time' => '',
					'Last year' => 'this-year',
					'Last month' => 'this-month',
					'Last week' => 'this-week',
					'Today' => 'today'
				)
			),
			'duration' => array(
				'type' => 'list',
				'name' => 'Duration',
				'values' => array(
					'All' => '',
					'Short' => 'short',
					'Long' => 'long'
				)
			)
		),

		'By category' => array(
			'cat' => array(
				'type' => 'list',
				'name' => 'Category',
				'values' => array(
					'News' => 'category/news',
					'Viral' => 'category/viral',
					'Podcasts' => 'category/podcasts',
					'Battle leaderboard' => 'battle-leaderboard',
					'Entertainment' => 'category/entertainment',
					'Sports' => 'category/sports',
					'Science' => 'category/science',
					'Technology' => 'category/technology',
					'Vlogs' => 'category/vlogs'
				)
			)
		)
	);

	public function collectData() {
		$url = '';
		$params = '';
		if($this->getInput('u')) { /* User mode */
			$url = 'user/' . $this->getInput('u');
			$params = '?';
		} elseif($this->getInput('c')) { /* Channel mode */
			$url = 'c/' . $this->getInput('c');
			$params = '?';
		} elseif($this->getInput('s')) { /* Search mode */
			$url = 'search/' . $this->getInput('what') . '?q=' . urlencode($this->getInput('s'));
			$params = '&';
		} elseif($this->getInput('cat')) { /* Category mode */
			$url = $this->getInput('cat');
		}

		if(!empty($params)) {
			$query = array();
			if($this->getInput('sort'))
				$query[] = 'sort=' . $this->getInput('sort');
			if($this->getInput('date'))
				$query[] = 'date=' . $this->getInput('date');
			if($this->getInput('duration'))
				$query[] = 'duration=' . $this->getInput('duration');

			if(!empty($query))
				$url .= $params . implode('&', $query);
		}

		$html = getSimpleHTMLDOM(self::URI . $url)
			or returnServerError('Could not request rumble.com.');

		$this->feedName = $html->find('h1', 0)->plaintext;

		foreach($html->find('li.video-listing-entry') as $element) {
			$item = array();
			$item['uri'] = rtrim(self::URI, '/') . $element->find('a', 0)->href;
			$item['title'] = $element->find('h3', 0)->plaintext;
			$item['timestamp'] = $element->find('time', 0)->datetime;# 2021-03-31T16:07:08-04:00
			# <span class="video-item--duration" data-value="20:28"></span>
			$vTime = $element->find('span[class="video-item--duration"]', 0)->attr['data-value'];
			$thumbnailUri = $element->find('img', 0)->src;
			$item['content'] = '<img src="' . $thumbnailUri . '" /><br />' . $vTime;
			$item['author'] = $element->find('address', 0)->plaintext;
			$this->items[] = $item;
		}
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - Rumble';
		}

		return parent::getName();
	}
}
