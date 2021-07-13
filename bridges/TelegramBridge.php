<?php
class TelegramBridge extends BridgeAbstract {
	const NAME = 'Telegram Bridge';
	const URI = 'https://t.me';
	const DESCRIPTION = 'Returns newest posts from a public Telegram channel';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'required' => true,
				'exampleValue' => '@telegram',
			)
		)
	);
	const TEST_DETECT_PARAMETERS = array(
		'https://t.me/s/durov' => array('username' => 'durov'),
		'https://t.me/durov' => array('username' => 'durov'),
		'http://t.me/durov' => array('username' => 'durov'),
	);

	const CACHE_TIMEOUT = 900; // 15 mins

	private $feedName = '';
	private $enclosures = array();
	private $itemTitle = '';

	private $backgroundImageRegex = "/background-image:url\('(.*)'\)/";
	private $detectParamsRegex = '/^https?:\/\/t.me\/(?:s\/)?([\w]+)$/';

	public function detectParameters($url) {
		$params = array();

		if(preg_match($this->detectParamsRegex, $url, $matches) > 0) {
			$params['username'] = $matches[1];
			return $params;
		}

		return null;
	}

	public function collectData() {

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$channelTitle = htmlspecialchars_decode(
			$html->find('div.tgme_channel_info_header_title span', 0)->plaintext,
			ENT_QUOTES
		);
		$this->feedName = $channelTitle . ' (@' . $this->processUsername() . ')';

		foreach($html->find('div.tgme_widget_message_wrap.js-widget_message_wrap') as $index => $messageDiv) {
			$this->itemTitle = '';
			$this->enclosures = array();
			$item = array();

			$item['uri'] = $this->processUri($messageDiv);
			$item['content'] = $this->processContent($messageDiv);
			$item['title'] = $this->itemTitle;
			$item['timestamp'] = $this->processDate($messageDiv);
			$item['enclosures'] = $this->enclosures;
			$author = trim($messageDiv->find('a.tgme_widget_message_owner_name', 0)->plaintext);
			$item['author'] = html_entity_decode($author, ENT_QUOTES);

			$this->items[] = $item;
		}
		$this->items = array_reverse($this->items);
	}

	public function getURI() {

		if (!is_null($this->getInput('username'))) {
			return self::URI . '/s/' . $this->processUsername();
		}

		return parent::getURI();
	}

	public function getName() {

		if (!empty($this->feedName)) {
			return $this->feedName . ' - Telegram';
		}

		return parent::getName();
	}

	private function processUsername() {

		if (substr($this->getInput('username'), 0, 1) === '@') {
			return substr($this->getInput('username'), 1);
		}

		return $this->getInput('username');
	}

	private function processUri($messageDiv) {
		return $messageDiv->find('a.tgme_widget_message_date', 0)->href;
	}

	private function processContent($messageDiv) {
		$message = '';

		if ($messageDiv->find('div.tgme_widget_message_forwarded_from', 0)) {
			$message = $messageDiv->find('div.tgme_widget_message_forwarded_from', 0)->innertext . '<br><br>';
		}

		if ($messageDiv->find('a.tgme_widget_message_reply', 0)) {
			$message .= $this->processReply($messageDiv);
		}

		if ($messageDiv->find('div.tgme_widget_message_sticker_wrap', 0)) {
			$message .= $this->processSticker($messageDiv);
		}

		if ($messageDiv->find('div.tgme_widget_message_poll', 0)) {
			$message .= $this->processPoll($messageDiv);
		}

		if ($messageDiv->find('video', 0)) {
			$message .= $this->processVideo($messageDiv);
		}

		if ($messageDiv->find('a.tgme_widget_message_photo_wrap', 0)) {
			$message .= $this->processPhoto($messageDiv);
		}

		if ($messageDiv->find('a.not_supported', 0)) {
			$message .= $this->processNotSupported($messageDiv);
		}

		if ($messageDiv->find('div.tgme_widget_message_text.js-message_text', 0)) {
			$message .= $messageDiv->find('div.tgme_widget_message_text.js-message_text', 0);

			$this->itemTitle = $this->ellipsisTitle(
				$messageDiv->find('div.tgme_widget_message_text.js-message_text', 0)->plaintext
			);
		}

		if ($messageDiv->find('div.tgme_widget_message_document', 0)) {
			$message .= $this->processAttachment($messageDiv);
		}

		if ($messageDiv->find('a.tgme_widget_message_link_preview', 0)) {
			$message .= $this->processLinkPreview($messageDiv);
		}

		return $message;
	}

	private function processReply($messageDiv) {
		$reply = $messageDiv->find('a.tgme_widget_message_reply', 0);
		$author = $reply->find('span.tgme_widget_message_author_name', 0)->plaintext;
		$text = '';

		if ($reply->find('div.tgme_widget_message_metatext', 0)) {
			$text = $reply->find('div.tgme_widget_message_metatext', 0)->innertext;
		}

		if ($reply->find('div.tgme_widget_message_text', 0)) {
			$text = $reply->find('div.tgme_widget_message_text', 0)->innertext;
		}

		return <<<EOD
<blockquote>{$author}<br>
{$text}
<a href="{$reply->href}">{$reply->href}</a></blockquote><hr>
EOD;
	}

	private function processSticker($messageDiv) {
		if (empty($this->itemTitle)) {
			$this->itemTitle = '@' . $this->processUsername() . ' posted a sticker';
		}

		$stickerDiv = $messageDiv->find('div.tgme_widget_message_sticker_wrap', 0);

		if ($stickerDiv->find('picture', 0)) {
			$stickerDiv->find('picture', 0)->find('div', 0)->style = '';
			$stickerDiv->find('picture', 0)->style = '';

			return $stickerDiv;

		} elseif (preg_match($this->backgroundImageRegex, $stickerDiv->find('i', 0)->style, $sticker)) {
			$this->enclosures[] = $sticker[1];

			return <<<EOD
				<a href="{$stickerDiv->children(0)->herf}"><img src="{$sticker[1]}"></a>
EOD;
		}
	}

	private function processPoll($messageDiv) {

		$poll = $messageDiv->find('div.tgme_widget_message_poll', 0);

		$title = $poll->find('div.tgme_widget_message_poll_question', 0)->plaintext;
		$type = $poll->find('div.tgme_widget_message_poll_type', 0)->plaintext;

		if (empty($this->itemTitle)) {
			$this->itemTitle = $title;
		}

		$pollOptions = '<ul>';

		foreach ($poll->find('div.tgme_widget_message_poll_option') as $option) {
			$pollOptions .= '<li>' . $option->children(0)->plaintext . ' - ' .
				$option->find('div.tgme_widget_message_poll_option_text', 0)->plaintext . '</li>';
		}
		$pollOptions .= '</ul>';

		return <<<EOD
			{$title}<br><small>$type</small><br>{$pollOptions}
EOD;
	}

	private function processLinkPreview($messageDiv) {

		$image = '';
		$title = '';
		$site = '';
		$description = '';

		$preview = $messageDiv->find('a.tgme_widget_message_link_preview', 0);

		if (trim($preview->innertext) === '') {
			return '';
		}

		if($preview->find('i', 0) &&
		   preg_match($this->backgroundImageRegex, $preview->find('i', 0)->style, $photo)) {

			$image = '<img src="' . $photo[1] . '"/>';
			$this->enclosures[] = $photo[1];
		}

		if ($preview->find('div.link_preview_title', 0)) {
			$title = $preview->find('div.link_preview_title', 0)->plaintext;
		}

		if ($preview->find('div.link_preview_site_name', 0)) {
			$site = $preview->find('div.link_preview_site_name', 0)->plaintext;
		}

		if ($preview->find('div.link_preview_description', 0)) {
			$description = $preview->find('div.link_preview_description', 0)->plaintext;
		}

		return <<<EOD
<blockquote><a href="{$preview->href}">$image</a><br><a href="{$preview->href}">
{$title} - {$site}</a><br>{$description}</blockquote>
EOD;
	}

	private function processVideo($messageDiv) {

		if (empty($this->itemTitle)) {
			$this->itemTitle = '@' . $this->processUsername() . ' posted a video';
		}

		if ($messageDiv->find('i.tgme_widget_message_video_thumb')) {
			preg_match($this->backgroundImageRegex, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);
		} elseif ($messageDiv->find('i.link_preview_video_thumb')) {
			preg_match($this->backgroundImageRegex, $messageDiv->find('i.link_preview_video_thumb', 0)->style, $photo);
		}

		$this->enclosures[] = $photo[1];

		return <<<EOD
<video controls="" poster="{$photo[1]}" preload="none">
	<source src="{$messageDiv->find('video', 0)->src}" type="video/mp4">
</video>
EOD;
	}

	private function processPhoto($messageDiv) {

		if (empty($this->itemTitle)) {
			$this->itemTitle = '@' . $this->processUsername() . ' posted a photo';
		}

		$photos = '';

		foreach ($messageDiv->find('a.tgme_widget_message_photo_wrap') as $photoWrap) {
			preg_match($this->backgroundImageRegex, $photoWrap->style, $photo);

			$this->enclosures[] = $photo[1];

			$photos .= <<<EOD
<a href="{$photoWrap->href}"><img src="{$photo[1]}"/></a><br>
EOD;
		}
		return $photos;
	}

	private function processNotSupported($messageDiv) {

		if (empty($this->itemTitle)) {
			$this->itemTitle = '@' . $this->processUsername() . ' posted a video';
		}

		if ($messageDiv->find('i.tgme_widget_message_video_thumb')) {
			preg_match($this->backgroundImageRegex, $messageDiv->find('i.tgme_widget_message_video_thumb', 0)->style, $photo);
		} elseif ($messageDiv->find('i.link_preview_video_thumb')) {
			preg_match($this->backgroundImageRegex, $messageDiv->find('i.link_preview_video_thumb', 0)->style, $photo);
		}

		$this->enclosures[] = $photo[1];

		return <<<EOD
<a href="{$messageDiv->find('a.not_supported', 0)->href}">
{$messageDiv->find('div.message_media_not_supported_label', 0)->innertext}<br><br>
{$messageDiv->find('span.message_media_view_in_telegram', 0)->innertext}<br><br>
<img src="{$photo[1]}"/></a>
EOD;
	}

	private function processAttachment($messageDiv) {
		$attachments = 'Attachments:';

		if (empty($this->itemTitle)) {
			$this->itemTitle = '@' . $this->processUsername() . ' posted an attachment';
		}

		foreach ($messageDiv->find('div.tgme_widget_message_document') as $document) {
			$attachments .= <<<EOD
<br>{$document->find('div.tgme_widget_message_document_title', 0)->plaintext}<br>
{$document->find('div.tgme_widget_message_document_extra', 0)->plaintext}
EOD;
		}

		return $attachments;
	}

	private function processDate($messageDiv) {

		$messageMeta = $messageDiv->find('span.tgme_widget_message_meta', 0);
		return $messageMeta->find('time', 0)->datetime;

	}

	private function ellipsisTitle($text) {

		$length = 100;

		if (strlen($text) > $length) {
			$text = explode('<br>', wordwrap($text, $length, '<br>'));
			return $text[0] . '...';
		}
		return $text;
	}
}
