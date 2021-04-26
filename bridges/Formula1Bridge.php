<?php
class Formula1Bridge extends BridgeAbstract {
	const NAME = 'Formula1 Bridge';
	const URI = 'https://formula1.com/';
	const DESCRIPTION = 'Returns latest official Formula 1 news';
	const MAINTAINER = 'AxorPL';

	const ERR_QUERY = 'Unable to query: %s';

	const API_KEY = 'qPgPPRJyGCIPxFT3el4MF7thXHyJCzAP';
	const API_URL = 'https://api.formula1.com/v1/editorial/articles?limit=%u';

	const ARTICLE_AUTHOR = 'Formula 1';
	const ARTICLE_HTML = '<p>%s</p><a href="%s" target="_blank"><img src="%s" alt="%s" title="%s"></a>';
	const ARTICLE_URL = 'https://formula1.com/en/latest/article.%s.%s.html';

	const LIMIT_MIN = 1;
	const LIMIT_DEFAULT = 10;
	const LIMIT_MAX = 100;

	const PARAMETERS = array(
		array(
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'required' => false,
				'title' => 'Number of articles to return',
				'exampleValue' => self::LIMIT_DEFAULT,
				'defaultValue' => self::LIMIT_DEFAULT
			)
		)
	);

	public function collectData() {
		$limit = $this->getInput('limit') ?: self::LIMIT_DEFAULT;
		$limit = min(self::LIMIT_MAX, max(self::LIMIT_MIN, $limit));
		$url = sprintf(self::API_URL, $limit);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('apikey: ' . self::API_KEY));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_URL, sprintf(self::API_URL, $limit));
		$response = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
		if($code !== 200) {
			returnServerError(curl_error($curl));
		}
		curl_close($curl);

		$json = json_decode($response)
			or returnServerError(sprintf(self::ERR_QUERY, $url));
		if($json->error) {
			returnServerError($json->message);
		}
		$list = $json->items;

		foreach($list as $article) {
			$item = array();
			$item['uri'] = sprintf(self::ARTICLE_URL, $article->slug, $article->id);
			$item['title'] = $article->title;
			$item['timestamp'] = $article->updatedAt;
			$item['author'] = self::ARTICLE_AUTHOR;
			$item['enclosures'] = array($article->thumbnail->image->url);
			$item['uid'] = $article->id;
			$item['content'] = sprintf(
				self::ARTICLE_HTML,
				$article->metaDescription,
				$item['title'],
				$item['enclosures'][0],
				$article->thumbnail->image->title,
				$article->thumbnail->image->title
			);
			$this->items[] = $item;
		}
	}
}
