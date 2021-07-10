<?php
class FeedMergeBridge extends FeedExpander {
	const MAINTAINER = 'AntoineTurmel';
	const NAME = 'FeedMerge';
	const URI = 'https://github.com/RSS-Bridge/rss-bridge';
	const DESCRIPTION = 'Merge 2 feeds';
	const PARAMETERS = array(
			array(
				'feed1' => array(
					'name' => 'Feed URL 1',
					'type' => 'text',
					'required' => true,
					'title' => 'Insert your feed URL 1',
					'exampleValue' => 'https://myfeed1.com'
				),
				'feed2' => array(
					'name' => 'Feed URL 2',
					'type' => 'text',
					'required' => true,
					'title' => 'Insert your feed URL2',
					'exampleValue' => 'https://myfeed2.com'
				)
			)
		);
		public $feedname = '';

		private function feedName($f){
			$this->$feedname = $this->$feedname . ' / ' . $f;
		}

	protected function parseItem($item){
		$item = parent::parseItem($item);
		return $item;
	}

	public function collectData(){
		$this->collectExpandableDatas($this->getInput('feed1'));
		$this->feedName(parent::getName());
		$this->collectExpandableDatas($this->getInput('feed2'));
		$this->feedName(parent::getName());
	}

	public function getURI() {
		return self::URI;
	}

	public function getIcon() {
		$feedicon = 'https://cdn.jsdelivr.net/npm/famfamfam-silk@1.0.0/dist/png/folder_feed.png';
		return $feedicon;
	}

	public function getName() {
		if(isset($feedname)) {
			return 'FeedMerge ' . $this->$feedname;
		} else {
			return 'FeedMerge';
		}
	}
}
