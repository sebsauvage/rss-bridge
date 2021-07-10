<?PHP
class XDABridge extends BridgeAbstract
{
	const NAME        = 'XDA';
	const URI         = 'https://forum.xda-developers.com';
	const DESCRIPTION = 'Returns the latest posts from a forum thread';
	const MAINTAINER  = 'brincowale';
	const PARAMETERS = array(array(
		'id' => array(
			'name' => 'Thread ID',
			'type' => 'number',
			'required' => true,
			'title' => 'Insert thread ID',
			'exampleValue' => '2011153'
		)
	));
	const CACHE_TIMEOUT = 300;

	public function collectData()
	{
		$htmlLastPage = getSimpleHTMLDOM(self::URI . '/showthread.php?t=' . $this->getInput('id') . '&page=9999')
			or returnServerError('No contents received!');
		$title = $htmlLastPage->find('h1', 0)->innertext;

		$prevLastURL = self::URI . $htmlLastPage->find('div.pagenav > a.pagenav-pagelink', -1)->getAttribute('href');
		$htmlPrevLastPage = getSimpleHTMLDOM($prevLastURL) or returnServerError('No contents received!');

		$this->extractPosts($htmlPrevLastPage, $title);
		$this->extractPosts($htmlLastPage, $title);
		$this->items = array_reverse($this->items);
	}

	private function extractPosts($html, $title)
	{
		foreach ($html->find('div[id=posts] > div[id^=edit]') as $post) {
			$item = array();
			$item['title'] = $title;
			$postID = str_replace('postcount', '', $post->find('a[id^=postcount]', 0)->getAttribute('id'));
			$item['uri'] = self::URI . '/showpost.php?p=' . $postID;
			$item['content'] = $post->find('div[id^=post_message_]', 0)->innertext;
			$item['author'] = $post->find('a[id^=postmenu_]', 0)->innertext;
			$item['timestamp'] = $post->find('span.time', 0)->innertext;
			$this->items[] = $item;
		}
	}
}
