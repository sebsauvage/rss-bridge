<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "GenericExtractor.php";

class PartExtractor extends GenericExtractor {
	public function extractTimestamp($stampElem) {
		try {
			// if post is less then few hours old, then .rel-date element has "time" attribute, which contains valid Unix timestamp:
			if($stampElem->getAttribute('time') != '') {
				assertc(preg_match('/\d+/', $stampElem->getAttribute('time')), 'In extractTimestamp() "time" attribute is malformed');
				return $stampElem->getAttribute('time');
			} else {
				// clean timestamp from all strange chars
				$stampRaw = preg_replace('/[^\w:]/u', ' ', $stampElem->text());
				// post timestamp is in one of the following formats:
				// "today<or yesterday> at <time(with meridian)>", "<day of month> <short month name> at <time>", "<day of month> <short month name> <year>", "<day of month> <short month name>, <year> at <time>"
				// but strtotime() understands those:
				// "today<or yesterday> <time(with meridian)>", "<time> <day of month> <short month name>", "<day of month> <short month name> <year>", "<time> <day of month> <short month name>, <year>"
				// ...therefore transforming timestamp to satisfy:
				$stampRaw = preg_replace('/ at/u', '', $stampRaw);
				// finds time, prepends it to the timestamp and removes it from the end, if timestamp is not in "today|yesterday <time>" format
				if(preg_match('/\d?\d:\d\d (am|pm)/u', $stampRaw, $matches) && !preg_match('/(today|yesterday)/u', $stampRaw)) {
					$stampRaw = $matches[0] . ' ' . preg_replace('/\d?\d:\d\d (am|pm)/u', '', $stampRaw);
				}

				assertc(strtotime($stampRaw, $this->options['baseTimestamp']) !== false,
					'Incorrectly parsed time: "' . $stampElem->text() . '" in timestamp: "' . $stampRaw . '"');

				return strtotime($stampRaw, $this->options['baseTimestamp']);
			}
		} catch(\Exception $e) {
			$this->log($e->getMessage());
			return null;
		}
	}

	public function extractImages($body) {
		$images = array();

		$images = array_merge($images, $this->extractNormalImages($body));

		$images = array_merge($images, $this->extractGifs($body));

		$images = array_merge($images, $this->extractStickers($body));

		$images = array_merge($images, $this->extractBigImages($body));

		return $images;
	}

	private function extractNormalImages($body) {
		$images = array();

		foreach($body->find('.image_cover') as $imageElem) {
			if(strpos($imageElem->getAttribute('class'), 'page_post_thumb_video') === false) {
				$images[] = $this->extractImage($imageElem);
			}
		}

		return $images;
	}

	private function extractGifs($body) {
		$images = array();

		// matches elements with Page.showGif onclick handler (gif containers in posts with single gif)
		// ...and elements with Page.showGifBox onclick handler (gif containers in posts with other media content)

		foreach($body->find('a[onclick*=showGif]') as $gifElem) {
			$image = array();

			if(hasAttr($gifElem, 'href')) {
				$imageId = getFileIdFromUrl($gifElem->getAttribute('href'));
				$image['thumb'] = $image['original'] = getFileDirectUrlById($imageId);
			} else {
				$this->log('In extractGifs() $gifElem\'s "href" attribute is empty or doesn\'t exist');
				$image['thumb'] = $image['original'] = null;
			}

			$images[] = $image;
		}

		return $images;
	}

	private function extractStickers($body) {
		$images = array();

		foreach($body->find('.sticker_img') as $stickerElem) {
			if(hasAttr($stickerElem, 'src')) {
				$sticker = $stickerElem->getAttribute('src');
			} else {
				$this->log('In extractStickers() $stickerElem doesn\'t have "src" attribute');
				$sticker = null;
			}

			$images[] = array(
				'thumb' => $sticker,
				'original' => $sticker
			);
		}

		return $images;
	}

	private function extractBigImages($body) {
		$images = array();

		foreach($body->find('a[href*=doc]') as $bigImageElem) {
			// images imported as files have empty 'onclick' attribute
			if($bigImageElem->getAttribute('onclick') == ' ') {
				$image = array();

				try {
					$image['thumb'] = extractBackgroundImage($bigImageElem);
				} catch(\Exception $e) {
					$this->log('In extractBigImages() ' . $e->getMessage());
					$image['thumb'] = null;
				}

				try {
					assertc(hasAttr($bigImageElem, 'href'), 'In extractImages() $bigImageElem\'s "href" attribute is empty or doesn\'t exist');
					$imageId = getFileIdFromUrl($bigImageElem->getAttribute('href'));
					$image['original'] = getFileDirectUrlById($imageId);
				} catch(\Exception $e) {
					$this->log('In extractBigImages() ' . $e->getMessage());
					$image['original'] = null;
				}

				$images[] = $image;
			}
		}

		return $images;
	}

	public function extractArticle($body) {
		$article = array();
		$hasArticle = has($body, '.article_snippet');
		if($hasArticle) {
			$article['title'] = $this->extractArticleTitle($body);
			$article['author'] = $this->extractArticleAuthor($body);
			$article['url'] = $this->extractArticleUrl($body);
			$article['image'] = $this->extractArticleImage($body);
		}
		return $article;
	}

	private function extractArticleTitle($body) {
		if(has($body, '.article_snippet__title', $titleElem)) {
			return $titleElem->text();
		} else {
			$this->log('Failed to extract article title');
		}
	}

	private function extractArticleAuthor($body) {
		if(has($body, '.article_snippet__author', $authorElem)) {
			return $authorElem->text();
		} else {
			$this->log('Failed to extract article author');
		}
	}

	private function extractArticleUrl($body) {
		if(hasAttr($body, 'href', '.article_snippet', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract article url');
		}
	}

	private function extractArticleImage($body) {
		try {
			assertc(has($body, '.article_snippet__image', $imageElem), 'Failed to extract article image');
			return extractBackgroundImage($imageElem);
		} catch(\Exception $e) {
			$this->log($e->getMessage());
		}
	}

	public function extractVideos($body) {
		$videos = array();

		foreach($body->find('.page_post_thumb_video') as $videoElem) {
			$video = array();

			$video['id'] = $this->extractVideoId($videoElem);
			$video['nativeUrl'] = $this->extractVideoNativeUrl($videoElem);
			$video['title'] = $this->extractVideoTitle($videoElem);
			$video['image'] = $this->extractVideoImage($videoElem);

			$sources = $this->extractVideoSources($video['nativeUrl']);

			$video['urls'] = $sources['urls'];
			$video['iframe'] = $sources['iframe'];

			$videos[] = $video;
		}
		return $videos;
	}

	private function extractVideoId($videoElem) {
		preg_match('!video(-?\d+_\d+)(\?list=\w+)?!', $videoElem->getAttribute('href'), $matches);
		if(isset($matches[1])) {
			return $matches[1];
		} else {
			$this->log('Failed to extract video id');
		}
	}

	private function extractVideoNativeUrl($videoElem) {
		preg_match('!video(-?\d+_\d+)(\?list=\w+)?!', $videoElem->getAttribute('href'), $matches);
		if(isset($matches[0])) {
			return 'https://m.vk.com/' . $matches[0];
		} else {
			$this->log('Failed to extract video native url');
		}
	}

	private function extractVideoTitle($videoElem) {
		// using aria-label allows extracting title even for private videos
		preg_match('/(.*) is/u', $videoElem->getAttribute('aria-label'), $matches);
		if(isset($matches[1])) {
			return $matches[1];
		} else {
			$this->log('Failed to extract video title');
		}
	}

	private function extractVideoImage($videoElem) {
		try {
			return extractBackgroundImage($videoElem);
		} catch(\Exception $e) {
			$this->log('Failed to extract video preview');
		}
	}

	private function extractVideoSources($videoUrl) {
		$sources = array(
			'urls' => null,
			'iframe' => null
		);

		if($videoUrl !== null) {
			$videoDom = $this->getDom($videoUrl, 'part');

			$sources['urls'] = $this->extractVideoUrls($videoDom);
			$sources['iframe'] = $this->extractVideoIframe($videoDom);
		}

		if($sources['urls'] === null && $sources['iframe'] === null) {
			$this->log('Failed to extract video sources');
		}

		return $sources;
	}

	private function extractVideoUrls($videoDom) {
		$sourceElems = $videoDom->find('source');
		if(count($sourceElems) > 0) {
			$urls = array();

			foreach($sourceElems as $sourceElem) {
				if(hasAttr($sourceElem, 'src', false, $srcAttr)) {
					$source = $srcAttr;

					if(!preg_match('/video_hls\.php/', $source)) {
						$urls[] = $source;
					}
				} else {
					$this->log('In extractVideos() source\'s "src" attribute is empty or doesn\'t exist');
					$urls[] = null;
				}
			}

			if(!empty($urls)) {
				return $urls;
			} else {
				$this->log('Failed to extract video direct urls');
			}
		}
	}

	private function extractVideoIframe($videoDom) {
		$isIframe = has($videoDom, '.VideoPage__video iframe', $iframeElem);
		if($isIframe) {
			$url = 'https:' . $iframeElem->getAttribute('src');
			if(preg_match('#^https://+?#', $url)) {
				return $url;
			} else {
				$this->log('extractVideoIframe() failed to extract valid iframe url');
			}
		}
	}

	public function extractAudios($body) {
		$audios = array();
		foreach($body->find('.audio_row') as $audioElem) {
			$audio = array();
			$audio['title'] = $this->extractAudioTitle($audioElem);
			$audio['nativeUrl'] = $this->extractAudioNativeUrl($audioElem);
			$audio['url'] = $this->extractAudioUrl($audioElem);
			$audio['duration'] = $this->extractAudioDuration($audioElem);
			$audios[] = $audio;
		}
		return $audios;
	}

	private function extractAudioTitle($audioElem) {
		if(check($audioElem, '.audio_row__performer_title a', $text)) {
			return $text;
		} else {
			$this->log('Failed to extract audio title');
		}
	}

	private function extractAudioNativeUrl($audioElem) {
		return null;
	}

	private function extractAudioUrl($audioElem) {
		return null;
	}

	private function extractAudioDuration($audioElem) {
		return null;
	}

	public function extractText($textElemFound) {
		$text = array(
			'plaintext' => '',
			'html' => '',
			'emojis' => ''
		);

		if(count($textElemFound) === 0) {
			return $text;
		} else {
			$textElem = $textElemFound[0];
		}

		$text['emojis'] = $textElem->innerHtml();

		// find img elements, which should be emojis, and replace them with unicode replacements hidden in img's "alt" attribute
		$text['html'] = preg_replace('!<img.*?alt=("|\')(.*?)("|\').*?>!', '\2', $text['emojis']);

		$plaintext = $text['html'];
		$plaintext = preg_replace('!</?a.*?>!', '', $plaintext);
		$plaintext = html_entity_decode($plaintext, ENT_QUOTES | ENT_HTML5);
		$text['plaintext'] = preg_replace('/<br\/?>/u', "\n", $plaintext);


		return $text;
	}

	public function extractFiles($body) {
		$files = array();

		foreach($body->find('a.page_doc_title') as $fileElem) {
			$file = array();
			$file['title'] = $this->extractFileTitle($fileElem);
			$file['nativeUrl'] = $this->extractFileNativeUrl($fileElem);
			$file['id'] = $this->extractFileId($file['nativeUrl']);
			$file['url'] = $this->extractFileUrl($file['id']);
			$files[] = $file;
		}

		return $files;
	}

	private function extractFileTitle($fileElem) {
		if(!empty($fileElem->text())) {
			return $fileElem->text();
		} else {
			$this->log('Failed to extract file name');
		}
	}

	private function extractFileNativeUrl($fileElem) {
		if(hasAttr($fileElem, 'href', false, $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract file native url');
		}
	}

	private function extractFileId($fileNativeUrl) {
		try {
			return getFileIdFromUrl($fileNativeUrl);
		} catch(\Exception $e) {
			$this->log('In extractFileId(): ' . $e->getMessage());
		}
	}

	private function extractFileUrl($fileId) {
		if($fileId !== null) {
			return getFileDirectUrlById($fileId);
		} else {
			$this->log('Failed to extract file url');
		}
	}

	private function extractImage($elem) {
		try {
			assertc(hasAttr($elem, 'onclick', false, $attr), 'extractImage() failed to find "onclick" attribute');

			$image = array();

			if(!preg_match('/sign=|quality=|size=/', $attr)) {
				$extension = '.jpg';
			} else {
				$extension = '';
			}

			preg_match('/return showPhoto\(.+?({.*})/', $attr, $matches);
			assertc(isset($matches[1]), 'extractImage() failed to extract data json');
			$arg = htmlspecialchars_decode($matches[1]);
			$data = json_decode($arg, true)['temp'];

			assertc(!empty($data['x']), 'In extractImage() data["x"] is empty');
			// by default expect image small enough that it does not have separate thumbnail
			$image['original'] = $image['thumb'] = $data['base'] . $data['x'];
			// ...but if that's not the case
			foreach(array('y_', 'z_', 'w_') as $key) {
				if (!isset($data[$key])) continue;
				if (!isset($data[$key][0])) continue;
				if (substr($data[$key][0], 0, 4) === 'http') {
					$base = '';
				} else {
					$base = $data['base'];
				}
				$image['original'] = $base . $data[$key][0] . $extension;
			}
			return $image;
		} catch(\Exception $e) {
			$this->log($e->getMessage());
			return null;
		}
	}
}

?>
