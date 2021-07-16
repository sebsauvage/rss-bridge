<?php
class ScmbBridge extends BridgeAbstract {

	const MAINTAINER = 'Astalaseven';
	const NAME = 'Se Coucher Moins Bête Bridge';
	const URI = 'https://secouchermoinsbete.fr';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns the newest anecdotes.';

	public function collectData(){
		$html = '';
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request Se Coucher Moins Bete.');

		foreach($html->find('article') as $article) {
			$item = array();
			$item['uri'] = self::URI . $article->find('p.summary a', 0)->href;
			$item['title'] = $article->find('header h1 a', 0)->innertext;

			// remove img "anecdote-premium-blurry.jpg" from anecdote content
			if($article->find('img.premium-needed', 0))
				$article->find('img.premium-needed', 0)->outertext = '';
			// remove text "En savoir plus" from anecdote content
			elseif($article->find('span.read-more', 0))
				$article->find('span.read-more', 0)->outertext = '';

			$content = $article->find('p.summary a', 0)->innertext;

			// remove superfluous spaces at the end
			$content = substr($content, 0, strlen($content) - 17);

			// get publication date
			$str_date = $article->find('time', 0)->datetime;
			list($date, $time) = explode(' ', $str_date);
			list($y, $m, $d) = explode('-', $date);
			list($h, $i) = explode(':', $time);
			$timestamp = mktime($h, $i, 0, $m, $d, $y);
			$item['timestamp'] = $timestamp;

			$item['content'] = $content;
			$this->items[] = $item;
		}
	}
}
