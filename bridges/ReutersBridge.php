<?php
class ReutersBridge extends BridgeAbstract
{
	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns news from Reuters';

	private $feedName = self::NAME;

	/**
	 * Wireitem types allowed in the final story output
	 */
	const ALLOWED_WIREITEM_TYPES = array(
		'story',
		'headlines'
	);

	/**
	 * Wireitem template types allowed in the final story output
	 */
	const ALLOWED_TEMPLATE_TYPES = array(
		'story',
		'headlines'
	);

	const PARAMETERS = array(
		array(
			'feed' => array(
				'name' => 'News Feed',
				'type' => 'list',
				'title' => 'Feeds from Reuters U.S/International edition',
				'values' => array(
					'Aerospace and Defense' => 'aerospace',
					'Business' => 'business',
					'China' => 'china',
					'Energy' => 'energy',
					'Entertainment' => 'chan:8ym8q8dl',
					'Environment' => 'chan:6u4f0jgs',
					'Fact Check' => 'chan:abtpk0vm',
					'Health' => 'chan:8hw7807a',
					'Lifestyle' => 'life',
					'Markets' => 'markets',
					'Politics' => 'politics',
					'Science' => 'science',
					'Special Reports' => 'special-reports',
					'Sports' => 'sports',
					'Tech' => 'tech',
					'Top News' => 'home/topnews',
					'UK' => 'chan:61leiu7j',
					'USA News' => 'us',
					'Wire' => 'wire',
					'World' => 'world',
				)
			)
		)
	);

	/**
	 * Performs an HTTP request to the Reuters API and returns decoded JSON
	 * in the form of an associative array
	 * @param string $feed_uri Parameter string to the Reuters API
	 * @return array
	 */
	private function getJson($feed_uri)
	{
		$uri = "https://wireapi.reuters.com/v8$feed_uri";
		$returned_data = getContents($uri);
		return json_decode($returned_data, true);
	}

	/**
	 * Takes in data from Reuters Wire API and
	 * creates structured data in the form of a list
	 * of story information.
	 * @param array $data JSON collected from the Reuters Wire API
	 */
	private function processData($data)
	{
		/**
		 * Gets a list of wire items which are groups of templates
		 */
		$reuters_allowed_wireitems = array_filter(
			$data, function ($wireitem) {
				return in_array(
					$wireitem['wireitem_type'],
					self::ALLOWED_WIREITEM_TYPES
				);
			}
		);

		/*
		* Gets a list of "Templates", which is data containing a story
		*/
		$reuters_wireitem_templates = array_reduce(
			$reuters_allowed_wireitems,
			function (array $carry, array $wireitem) {
				$wireitem_templates = $wireitem['templates'];
				return array_merge(
					$carry,
					array_filter(
						$wireitem_templates, function (
							array $template_data
						) {
							return in_array(
								$template_data['type'],
								self::ALLOWED_TEMPLATE_TYPES
							);
						}
					)
				);
			},
			array()
		);

		return $reuters_wireitem_templates;
	}

	private function getArticle($feed_uri)
	{
		// This will make another request to API to get full detail of article and author's name.
		$rawData = $this->getJson($feed_uri);
		$reuters_wireitems = $rawData['wireitems'];
		$processedData = $this->processData($reuters_wireitems);

		$first = reset($processedData);
		$article_content = $first['story']['body_items'];
		$authorlist = $first['story']['authors'];
		$category = $first['story']['channel']['name'];
		$image_list = $first['story']['images'];
		$published_at = $first['story']['published_at'];

		$content_detail = array(
			'content' => $this->handleArticleContent($article_content),
			'author' => $this->handleAuthorName($authorlist),
			'category' => $category,
			'images' => $this->handleImage($image_list),
			'published_at' => $published_at
		);
		return $content_detail;
	}

	private function handleImage($images) {
		$img_placeholder = '';

		foreach($images as $image) { // Add more image to article.
			$image_url = $image['url'];
			$image_caption = $image['caption'];
			$img = "<img src=\"$image_url\" alt=\"$image_caption\">";
			$img_caption = "<figcaption style=\"text-align: center;\"><i>$image_caption</i></figcaption>";
			$figure = "<figure>$img \t $img_caption</figure>";
			$img_placeholder = $img_placeholder . $figure;
		}

		return $img_placeholder;
	}

	private function handleAuthorName($authors) {
		$author = '';
		$counter = 0;
		foreach ($authors as $data) {
			//Formatting author's name.
			$counter++;
			$name = $data['name'];
			if ($counter == count($authors)) {
				$author = $author . $name;
			} else {
				$author = $author . "$name, ";
			}
		}

		return $author;
	}

	private function handleArticleContent($contents) {
		$description = '';
		foreach ($contents as $content) {
			$data;
			if(isset($content['content'])) {
				$data = $content['content'];
			}
			switch($content['type']) {
				case 'paragraph':
					$description = $description . "<p>$data</p>";
					break;
				case 'heading':
					$description = $description . "<h3>$data</h3>";
					break;
				case 'infographics':
					$description = $description . "<img src=\"$data\">";
					break;
				case 'inline_items':
					$item_list = $content['items'];
					$description = $description . '<p>';
					foreach ($item_list as $item) {
						if($item['type'] == 'text') {
							$description = $description . $item['content'];
						} else {
							$description = $description . $item['symbol'];
						}
					}
					$description = $description . '</p>';
					break;
				case 'p_table':
					$description = $description . $content['content'];
					break;
				case 'upstream_embed':
					$media_type = $content['media_type'];
					$cid = $content['cid'];
					$embed = '';
					switch ($media_type) {
						case 'tweet':
							$url = "https://platform.twitter.com/embed/Tweet.html?id=$cid";
							$embed .= <<<EOD
<iframe 
	src="{$url}"
	title="Twitter Tweet"
	scrolling="no" 
	frameborder="0" 
	allowtransparency="true" 
	allowfullscreen="true" 
	style="width: 550px;height: 225px;"
>
</iframe>
EOD;
							break;
						case 'instagram':
							$url = "https://instagram.com/p/$cid/media/?size=l";
							$embed .= <<<EOD
<img 
	src="{$url}"
	alt="instagram-image-$cid"
>
EOD;
							break;
						case 'youtube':
							$url = "https://www.youtube.com/embed/$cid";
							$embed .= <<<EOD
<‌iframe
	width="560" 
	height="315" 
	src="{$url}"
	frameborder="0" 
	allowfullscreen
>
</iframe>
EOD;
							break;
					}
					$description .= $embed;
					break;
			}
		}

		return $description;
	}

	public function getName() {
		return $this->feedName;
	}

	public function collectData()
	{
		$reuters_feed_name = $this->getInput('feed');

		if(strpos($reuters_feed_name, 'chan:') !== false) {
			// Now checking whether that feed has unique ID or not.
			$feed_uri = "/feed/rapp/us/wirefeed/$reuters_feed_name";
		} else {
			$feed_uri = "/feed/rapp/us/tabbar/feeds/$reuters_feed_name";
		}

		$data = $this->getJson($feed_uri);

		$reuters_wireitems = $data['wireitems'];
		$this->feedName = $data['wire_name'] . ' | Reuters';
		$processedData = $this->processData($reuters_wireitems);

		// Merge all articles from Editor's Highlight section into existing array of templates.
		$top_section = reset($processedData);
		if ($top_section['type'] == 'headlines') {
			$top_section = array_shift($processedData);
			$articles = $top_section['headlines'];
			$processedData = array_merge($articles, $processedData);
		}

		foreach ($processedData as $story) {
			$item['uid'] = $story['story']['usn'];
			$article_uri = $story['template_action']['api_path'];
			$content_detail = $this->getArticle($article_uri);
			$description = $content_detail['content'];
			$author = $content_detail['author'];
			$images = $content_detail['images'];
			$item['categories'] = array($content_detail['category']);
			$item['author'] = $author;
			if (!(bool) $description) {
				$description = $story['story']['lede']; // Just in case the content doesn't have anything.
			} else {
				$item['content'] = "$description  $images";
			}

			$item['title'] = $story['story']['hed'];
			$item['timestamp'] = $content_detail['published_at'];
			$item['uri'] = $story['template_action']['url'];
			$this->items[] = $item;
		}
	}
}
