<?php

class NineGagBridge extends BridgeAbstract {
	const NAME = '9gag Bridge';
	const URI = 'https://9gag.com/';
	const DESCRIPTION = 'Returns latest quotes from 9gag.';
	const MAINTAINER = 'ZeNairolf';
	const CACHE_TIMEOUT = 3600;
	const PARAMETERS = array(
		'Popular' => array(
			'd' => array(
				'name' => 'Section',
				'type' => 'list',
				'values' => array(
					'Hot' => 'hot',
					'Trending' => 'trending',
					'Fresh' => 'fresh',
				),
			),
			'video' => array(
				'name' => 'Filter Video',
				'type' => 'list',
				'values' => array(
					'NotFiltred' => 'none',
					'VideoFiltred' => 'without',
					'VideoOnly' => 'only',
				),
			),
			'p' => array(
				'name' => 'Pages',
				'type' => 'number',
				'defaultValue' => 3,
			),
		),
		'Sections' => array(
			'g' => array(
				'name' => 'Section',
				'type' => 'list',
				'values' => array(
					'Among Us' => 'among-us',
					'Animals' => 'animals',
					'Anime & Manga' => 'anime-manga',
					'Anime Waifu' => 'animewaifu',
					'Anime Wallpaper' => 'animewallpaper',
					'Apex Legends' => 'apexlegends',
					'Ask 9GAG' => 'ask9gag',
					'Awesome' => 'awesome',
					'Car' => 'car',
					'Comic & Webtoon' => 'comic-webtoon',
					'Coronavirus ' => 'coronavirus',
					'Cosplay' => 'cosplay',
					'Countryballs' => 'countryballs',
					'Cozy & Comfy' => 'home-living',
					'Crappy Design' => 'crappydesign',
					'Cryptocurrency ' => 'cryptocurrency',
					'Cyberpunk 2077' => 'cyberpunk2077',
					'Dark Humor' => 'darkhumor',
					'Drawing, DIY & Crafts' => 'drawing-diy-crafts',
					'Fashion & Beauty' => 'rate-my-outfit',
					'Food & Drinks' => 'food-drinks',
					'Football' => 'football',
					'Fortnite' => 'fortnite',
					'Funny' => 'funny',
					'Game of Thrones' => 'got',
					'Gaming' => 'gaming',
					'GIF' => 'gif',
					'Girl' => 'girl',
					'Girl Celebrity' => 'girlcelebrity',
					'Guy' => 'guy',
					'History' => 'history',
					'Horror' => 'horror',
					'K-Pop' => 'kpop',
					'Latest News' => 'timely',
					'League of Legends' => 'leagueoflegends',
					'LEGO' => 'lego',
					'Marvel & DC' => 'superhero',
					'Meme' => 'meme',
					'Movie & TV' => 'movie-tv',
					'Music' => 'music',
					'NBA' => 'basketball',
					'Overwatch' => 'overwatch',
					'PC Master Race' => 'pcmr',
					'Pokémon' => 'pokemon',
					'Politics ' => 'politics',
					'PUBG' => 'pubg',
					'Random ' => 'random',
					'Relationship' => 'relationship',
					'Satisfying' => 'satisfying',
					'Savage' => 'savage',
					'Science & Tech' => 'science-tech',
					'Sport ' => 'sport',
					'Star Wars' => 'starwars',
					'Teens Can Relate' => 'school',
					'Travel & Photography' => 'travel-photography',
					'Video' => 'video',
					'Wallpaper' => 'wallpaper',
					'Warhammer' => 'warhammer',
					'Wholesome' => 'wholesome',
					'WTF' => 'wtf',					
				),
			),
			't' => array(
				'name' => 'Type',
				'type' => 'list',
				'values' => array(
					'Hot' => 'hot',
					'Fresh' => 'fresh',
				),
			),
			'video' => array(
				'name' => 'Filter Video',
				'type' => 'list',
				'values' => array(
					'NotFiltred' => 'none',
					'VideoFiltred' => 'without',
					'VideoOnly' => 'only',
				),
			),
			'p' => array(
				'name' => 'Pages',
				'type' => 'number',
				'defaultValue' => 3,
			),
		),
	);

	const MIN_NBR_PAGE = 1;
	const MAX_NBR_PAGE = 6;

	protected $p = null;

	public function collectData() {
		$url = sprintf(
			'%sv1/group-posts/group/%s/type/%s?',
			self::URI,
			$this->getGroup(),
			$this->getType()
		);
		$cursor = 'c=10';
		$posts = array();
		for ($i = 0; $i < $this->getPages(); ++$i) {
			$content = getContents($url . $cursor);
			$json = json_decode($content, true);
			$posts = array_merge($posts, $json['data']['posts']);
			$cursor = $json['data']['nextCursor'];
		}

		foreach ($posts as $post) {
			$AvoidElement = false;
			switch ($this->getInput('video')) {
				case 'without':
					if ($post['type'] === 'Animated') {
						$AvoidElement = true;
					}
					break;
				case 'only':
					echo $post['type'];
					if ($post['type'] !== 'Animated') {
						$AvoidElement = true;
					}
					break;
				case 'none': default:
					break;
			}

			if (!$AvoidElement) {
				$item['uri'] = preg_replace('/^http:/i', 'https:', $post['url']);
				$item['title'] = $post['title'];
				$item['content'] = self::getContent($post);
				$item['categories'] = self::getCategories($post);
				$item['timestamp'] = self::getTimestamp($post);

				$this->items[] = $item;
			}
		}
	}

	public function getName() {
		if ($this->getInput('d')) {
			$name = sprintf('%s - %s', '9GAG', $this->getParameterKey('d'));
		} elseif ($this->getInput('g')) {
			$name = sprintf('%s - %s', '9GAG', $this->getParameterKey('g'));
			if ($this->getInput('t')) {
				$name = sprintf('%s [%s]', $name, $this->getParameterKey('t'));
			}
		}
		if (!empty($name)) {
			return $name;
		}

		return self::NAME;
	}

	public function getURI() {
		$uri = $this->getInput('g');
		if ($uri === 'default') {
			$uri = $this->getInput('t');
		}

		return self::URI . $uri;
	}

	protected function getGroup() {
		if ($this->getInput('d')) {
			return 'default';
		}

		return $this->getInput('g');
	}

	protected function getType() {
		if ($this->getInput('d')) {
			return $this->getInput('d');
		}

		return $this->getInput('t');
	}

	protected function getPages() {
		if ($this->p === null) {
			$value = (int) $this->getInput('p');
			$value = ($value < self::MIN_NBR_PAGE) ? self::MIN_NBR_PAGE : $value;
			$value = ($value > self::MAX_NBR_PAGE) ? self::MAX_NBR_PAGE : $value;

			$this->p = $value;
		}

		return $this->p;
	}

	protected function getParameterKey($input = '') {
		$params = $this->getParameters();
		$tab = 'Sections';
		if ($input === 'd') {
			$tab = 'Popular';
		}
		if (!isset($params[$tab][$input])) {
			return '';
		}

		return array_search(
			$this->getInput($input),
			$params[$tab][$input]['values']
		);
	}

	protected static function getContent($post) {
		if ($post['type'] === 'Animated') {
			$content = self::getAnimated($post);
		} elseif ($post['type'] === 'Article') {
			$content = self::getArticle($post);
		} else {
			$content = self::getPhoto($post);
		}

		return $content;
	}

	protected static function getPhoto($post) {
		$image = $post['images']['image460'];
		$photo = '<picture>';
		$photo .= sprintf(
			'<source srcset="%s" type="image/webp">',
			$image['webpUrl']
		);
		$photo .= sprintf(
			'<img src="%s" alt="%s" %s>',
			$image['url'],
			$post['title'],
			'width="500"'
		 );
		$photo .= '</picture>';

		return $photo;
	}

	protected static function getAnimated($post) {
		$poster = $post['images']['image460']['url'];
		$sources = $post['images'];
		$video = sprintf(
			'<video poster="%s" %s>',
			$poster,
			'preload="auto" loop controls style="min-height: 300px" width="500"'
		);
		$video .= sprintf(
			'<source src="%s" type="video/webm">',
			$sources['image460sv']['vp9Url']
		);
		$video .= sprintf(
			'<source src="%s" type="video/mp4">',
			$sources['image460sv']['h265Url']
		);
		$video .= sprintf(
			'<source src="%s" type="video/mp4">',
			$sources['image460svwm']['url']
		);
		$video .= '</video>';

		return $video;
	}

	protected static function getArticle($post) {
		$blocks = $post['article']['blocks'];
		$medias = $post['article']['medias'];
		$contents = array();
		foreach ($blocks as $block) {
			if ('Media' === $block['type']) {
				$mediaId = $block['mediaId'];
				$contents[] = self::getContent($medias[$mediaId]);
			} elseif ('RichText' === $block['type']) {
				$contents[] = self::getRichText($block['content']);
			}
		}

		$content = join('</div><div>', $contents);
		$content = sprintf(
			'<%1$s>%2$s</%1$s>',
			'div',
			$content
		);

		return $content;
	}

	protected static function getRichText($text = '') {
		$text = trim($text);

		if (preg_match('/^>\s(?<text>.*)/', $text, $matches)) {
			$text = sprintf(
				'<%1$s>%2$s</%1$s>',
				'blockquote',
				$matches['text']
			);
		} else {
			$text = sprintf(
				'<%1$s>%2$s</%1$s>',
				'p',
				$text
			);
		}

		return $text;
	}

	protected static function getCategories($post) {
		$params = self::PARAMETERS;
		$sections = $params['Sections']['g']['values'];

		if(isset($post['sections'])) {
			$postSections = $post['sections'];
		} elseif (isset($post['postSection'])) {
			$postSections = array($post['postSection']);
		} else {
			$postSections = array();
		}

		foreach ($postSections as $key => $section) {
			$postSections[$key] = array_search($section, $sections);
		}

		return $postSections;
	}

	protected static function getTimestamp($post) {
		$url = $post['images']['image460']['url'];
		$headers = get_headers($url, true);
		$date = $headers['Date'];
		$time = strtotime($date);

		return $time;
	}
}
