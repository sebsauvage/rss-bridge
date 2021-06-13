<?php
class UrlebirdBridge extends BridgeAbstract {

	const MAINTAINER = 'dotter-ak';
	const NAME = 'urlebird.com';
	const URI = 'https://urlebird.com/';
	const DESCRIPTION = 'Bridge for urlebird.com';
	const PARAMETERS = array(
		'Enter @username or #hashtag' => array(
			'query' => array(
				'name' => '@username or #hashtag',
				'type' => 'text',
				'required' => true,
				'title' => '@username or #hashtag',
				'exampleValue' => '@username or #hashtag'
			)
		)
	);

	private $title;

	public function collectData() {
		switch($this->getInput('query')[0]) {
			default:
				returnServerError('Please, enter valid username or hashtag!');
				break;
			case '@':
				$url = 'https://urlebird.com/user/' . substr($this->getInput('query'), 1) . '/';
				break;
			case '#':
				$url = 'https://urlebird.com/hash/' . substr($this->getInput('query'), 1) . '/';
				break;
		}

		$html = getSimpleHTMLDOM($url);
		$this->title = $html->find('title', 0)->innertext;
		$articles = $html->find('div.thumb');
		foreach ($articles as $article) {
			$item = array();
			$item['uri'] = $article->find('a', 2)->href;
			$article_content = getSimpleHTMLDOM($item['uri']);
			$item['author'] = $article->find('img', 0)->alt . ' (' .
				$article_content->find('a.user-video', 1)->innertext . ')';
			$item['title'] = $article_content->find('title', 0)->innertext;
			$item['enclosures'][] = $article_content->find('video', 0)->poster;
			$item['content'] = $article_content->find('video', 0)->outertext . '<br>' .
				$article_content->find('div.music', 0) . '<br>' .
				$article_content->find('div.info2', 0)->innertext .
				'<br><br><a href="' . $article_content->find('video', 0)->src . '">Video link</a>';
			$this->items[] = $item;
		}
	}

	public function getName() {
		return $this->title ?: parent::getName();
	}

	public function getIcon() {
		return 'https://urlebird.com/favicon.ico';
	}
}
