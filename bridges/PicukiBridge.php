<?php
class PicukiBridge extends BridgeAbstract
{
	const MAINTAINER = 'marcus-at-localhost';
	const NAME = 'Picuki Bridge';
	const URI = 'https://www.picuki.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns Picuki posts by user and by hashtag';

	const PARAMETERS = array(
		'Username' => array(
			'u' => array(
				'name' => 'username',
				'required' => true,
			),
		),
		'Hashtag' => array(
			'h' => array(
				'name' => 'hashtag',
				'required' => true,
			),
		)
	);

	public function getURI()
	{
		if (!is_null($this->getInput('u'))) {
			return urljoin(self::URI, '/profile/' . $this->getInput('u'));
		}

		if (!is_null($this->getInput('h'))) {
			return urljoin(self::URI, '/tag/' . trim($this->getInput('h'), '#'));
		}

		return parent::getURI();
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI());

		foreach ($html->find('.box-photos .box-photo') as $element) {

				// check if item is an ad.
			if (in_array('adv', explode(' ', $element->class))) {
				continue;
			}

			$item = array();

			$date = date_create();
			$relative_date = str_replace(' ago', '', $element->find('.time', 0)->plaintext);
			date_sub($date, date_interval_create_from_date_string($relative_date));
			$item['timestamp'] = date_format($date, 'r');

			$item['uri'] = urljoin(self::URI, $element->find('a', 0)->href);

			$item['title'] = $element->find('.photo-description', 0)->plaintext;

			$is_video = (bool) $element->find('.video-icon', 0);
			$item['content'] = ($is_video) ? '(video) ' : '';
			$item['content'] .= str_replace(
				'src="',
				'src="' . trim(self::URI, '/'),
				$element->find('.photo', 0)->outertext
			);

			$item['enclosures'] = array(
					// just add `.jpg` extension to get the correct mime type. All Instagram posts are JPG
					urljoin(self::URI, $element->find('.post-image', 0)->src . '.jpg')
				);

			$item['thumbnail'] = urljoin(self::URI, $element->find('.post-image', 0)->src);

			$this->items[] = $item;
		}
	}

	public function getName()
	{
		if (!is_null($this->getInput('u'))) {
			return $this->getInput('u') . ' - Picuki Bridge';
		}

		if (!is_null($this->getInput('h'))) {
			return $this->getInput('h') . ' - Picuki Bridge';
		}

		return parent::getName();
	}
}
