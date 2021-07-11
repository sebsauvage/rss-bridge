<?php
class WebfailBridge extends BridgeAbstract {
	const MAINTAINER = 'logmanoriginal';
	const URI = 'https://webfail.com';
	const NAME = 'Webfail';
	const DESCRIPTION = 'Returns the latest fails';
	const PARAMETERS = array(
		'By content type' => array(
			'language' => array(
				'name' => 'Language',
				'type' => 'list',
				'title' => 'Select your language',
				'values' => array(
					'English' => 'en',
					'German' => 'de'
				),
				'defaultValue' => 'English'
			),
			'type' => array(
				'name' => 'Type',
				'type' => 'list',
				'title' => 'Select your content type',
				'values' => array(
					'None' => '/',
					'Facebook' => '/ffdts',
					'Images' => '/images',
					'Videos' => '/videos',
					'Gifs' => '/gifs'
				),
				'defaultValue' => 'None'
			)
		)
	);

	public function getURI(){
		if(is_null($this->getInput('language')))
			return parent::getURI();

		// e.g.: https://en.webfail.com
		return 'https://' . $this->getInput('language') . '.webfail.com';
	}

	public function collectData(){

		ini_set('user_agent', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0');

		$html = getSimpleHTMLDOM($this->getURI() . $this->getInput('type'));

		$type = array_search($this->getInput('type'),
			self::PARAMETERS[$this->queriedContext]['type']['values']);

		switch(strtolower($type)) {
		case 'facebook':
		case 'videos':
			$this->extractNews($html, $type);
			break;
		case 'none':
		case 'images':
		case 'gifs':
			$this->extractArticle($html);
			break;
		default: returnClientError('Unknown type: ' . $type);
		}
	}

	private function extractNews($html, $type){
		$news = $html->find('#main', 0)->find('a.wf-list-news');
		foreach($news as $element) {
			$item = array();
			$item['title'] = $this->fixTitle($element->find('div.wf-news-title', 0)->innertext);
			$item['uri'] = $this->getURI() . $element->href;

			$img = $element->find('img.wf-image', 0)->src;
			// Load high resolution image for 'facebook'
			switch(strtolower($type)) {
			case 'facebook':
				$img = $this->getImageHiResUri($item['uri']);
				break;
			default:
			}

			$description = '';
			if(!is_null($element->find('div.wf-news-description', 0))) {
				$description = $element->find('div.wf-news-description', 0)->innertext;
			}

			$infoElement = $element->find('div.wf-small', 0);
			if (!is_null($infoElement)) {
				if (preg_match('/(\d{2}\.\d{2}\.\d{4})/m', $infoElement->innertext, $matches) === 1 && count($matches) == 2) {
					$dt = DateTime::createFromFormat('!d.m.Y', $matches[1]);
					if ($dt !== false) {
						$item['timestamp'] = $dt->getTimestamp();
					}
				}
			}

			$item['content'] = '<p>'
			. $description
			. '</p><br><a href="'
			. $item['uri']
			. '"><img src="'
			. $img
			. '"></a>';

			$this->items[] = $item;
		}
	}

	private function extractArticle($html){
		$articles = $html->find('article');
		foreach($articles as $article) {
			$item = array();
			$item['title'] = $this->fixTitle($article->find('a', 1)->innertext);

			// Images, videos and gifs are provided in their own unique way
			if(!is_null($article->find('img.wf-image', 0))) { // Image type
				$item['uri'] = $this->getURI() . $article->find('a', 2)->href;
				$item['content'] = '<a href="'
				. $item['uri']
				. '"><img src="'
				. $article->find('img.wf-image', 0)->src
				. '"></a>';
			} elseif(!is_null($article->find('div.wf-video', 0))) { // Video type
				$videoId = $this->getVideoId($article->find('div.wf-play', 0)->onclick);
				$item['uri'] = 'https://youtube.com/watch?v=' . $videoId;
				$item['content'] = '<a href="'
				. $item['uri']
				. '"><img src="http://img.youtube.com/vi/'
				. $videoId
				. '/0.jpg"></a>';
			} elseif(!is_null($article->find('video[id*=gif-]', 0))) { // Gif type
				$item['uri'] = $this->getURI() . $article->find('a', 2)->href;
				$item['content'] = '<video controls src="'
				. $article->find('video[id*=gif-]', 0)->src
				. '" poster="'
				. $article->find('video[id*=gif-]', 0)->poster
				. '"></video>';
			}

			$this->items[] = $item;
		}
	}

	private function fixTitle($title){
		// This fixes titles that include umlauts (in German language)
		return html_entity_decode($title, ENT_QUOTES | ENT_HTML401, 'UTF-8');
	}

	private function getVideoId($onclick){
		return substr($onclick, 21, 11);
	}

	private function getImageHiResUri($url){
		// https://de.webfail.com/ef524fae509?tag=ffdt
		// http://cdn.webfail.com/upl/img/ef524fae509/post2.jpg
		$id = substr($url, strrpos($url, '/') + 1, strlen($url) - strrpos($url, '?') + 2);
		return 'http://cdn.webfail.com/upl/img/' . $id . '/post2.jpg';
	}
}
