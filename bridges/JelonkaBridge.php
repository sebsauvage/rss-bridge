<?php
class JelonkaBridge extends BridgeAbstract {

	const LIMIT = 10;
    const MAINTAINER = 'bechem';
	const NAME = 'JelonkaBridge';
	const URI = 'https://www.jelonka.com/';
	const DESCRIPTION = 'Newest from Jelonka.com';
    const CACHE_TIMEOUT = 300; //
	const PARAMETERS = array(

		'channel' => array(
			'channel' => array(
				'type' => 'list',
				'name' => 'Channel',
				'values' => array(
					'News' => 'wiadomosci',
					'Sport' => 'sport'
				)
			)
		)
	);

	public function collectData(){
        $this->feedUri = self::URI . $this->getInput('channel');
        $html = getSimpleHTMLDOM($this->feedUri)
            or returnServerError('Could not request jelonka.com');
        foreach ($html->find('.grid3el') as $element) {
            if($count++ < self::LIMIT) {
                $elementUrl = self::URI . $element->find('.lsp', 0)->getAttribute('href');

                $elementHtml = getSimpleHTMLDOM($elementUrl)
                    or returnServerError('Could not request ' . $elementUrl);

                $content = $elementHtml->find('.artykul', 0)->plaintext;
                $item['author'] = $elementHtml->find('.art_autor', 0)->plaintext;
                $item['title'] = $element->find('.lst', 0)->plaintext;
                $item['content'] = $content;
                $item['uri'] = $elementUrl;

                $this->items[] = $item;
            }
            else{
                break;
            }
        }
	}
}
