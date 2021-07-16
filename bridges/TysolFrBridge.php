<?php
class TysolFrBridge extends BridgeAbstract {

	const MAINTAINER = 'sudwebdesign';
	const NAME = 'TysolFr';
	const URI = 'https://www.tysol.fr';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns results from Tysol.fr.';

	const SHOW_ACTU = 'l101-Actualites';
	const SHOW_OPIN = 'l102-Opinions';

	const PARAMETERS = array( array(
		'body' => array(
			'name' => 'Chapô seul',
			'type' => 'list',
			'values' => array(
				'Oui' => true,
				'Non' => false
			),
			'defaultValue' => true
		),
		'show' => array(
			'name' => 'what',
			'type' => 'list',
			'values' => array(
				'Actualites' => self::SHOW_ACTU,
				'Opinions' => self::SHOW_OPIN
			),
			'defaultValue' => self::SHOW_ACTU
		)
	));

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI . '/' . $this->getInput('show'))
			or returnServerError('Could not request Tysol.fr.');

		foreach($html->find('div.col-12') as $element) {
			$item = array();
			$item['uri'] = self::URI . $element->find('a', 0)->href;
			$item['title'] = $element->find('a', 1)->plaintext;
			$item['author'] = self::NAME;
			$body = '';
			if(!$this->getInput('body')) { /* todo remove script tags */
				$html2 = getSimpleHTMLDOM($item['uri']);
				$html2 = defaultLinkTo($html2, self::URI);
				$item['timestamp'] = strtotime($html2->find('div.article_date', 0)->plaintext);
				$body = $html2->find('div.body', 0)->innertext;
				$body .= $html2->find('div.article_footer', 0)->innertext;
				$author = explode(':', $html2->find('div.article_footer li', 0)->innertext);
				$item['author'] = trim($author[1]);
			}

			$thumbnailUri = $element->find('img', 0)->src;
			$item['content'] = '<img src="' . $thumbnailUri . '" /><br /><p style="text-align:justify; margin-bottom:11px">'
				. $element->find('span.news_lead', 0)->plaintext . '</p>' . $body;
			$this->items[] = $item;
		}
	}
}
