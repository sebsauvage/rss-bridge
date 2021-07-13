<?php
class ProxyBridge extends FeedExpander {
	const MAINTAINER = 'dhuschde';
	const NAME = 'RSS Feed Reader and Proxy';
	const URI = 'https://github.com/RSS-Bridge/rss-bridge';
	const DESCRIPTION = 'You can use this to read Feeds, cache them for one hour or translate between outputs';
	const PARAMETERS = array(
			array(
				'feed' => array(
					'name' => 'Feed URL',
					'type' => 'text',
					'required' => true,
					'exampleValue' => 'https://example.com/feed'
				)
			)
		);

	public function collectData(){
		$feed = $this->getInput('feed'); $this->collectExpandableDatas($feed);
	}

	public function getIcon() {
		$feed = $this->getInput('feed'); $feedicon = 'https://www.google.com/s2/favicons?domain=' . $feed; return $feedicon;
	}
}
