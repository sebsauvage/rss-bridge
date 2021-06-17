<?php

use d7sd6u\VKPostsExtractorParser\Extractor;
use d7sd6u\VKPostsTitleGenerator\Generator as TitleGenerator;
use d7sd6u\VKPostsFormatterHTML5\Formatter;
use DiDom\Document;

class VkAlternativeBridge extends BridgeAbstract {
	const NAME = 'Alternative VK.com bridge';
	const URI = 'https://vk.com/';
	const DESCRIPTION = 'Alternative VK bridge that doesn\'t use VK api.
	                     Extracts text, comments, images, files, videos, posters,
	                     pools, reposts, maps, expanded links and links
	                     to articles from last 10 posts by a group or a user, but not audios.
	                     Caches posts content for one hour.';
	const MAINTAINER = 'd7sd6u';
	const PARAMETERS = array(
		array(
			'u' => array( // this parameter name is called "u" for compatibility with old VkBridge urls
				'name' => 'Group or user id',
				'required' => true,
				'title' => 'User or group ID is a string after the VK domain in a direct link
to this user or group. For example, if the link is https://vk.com/durov, then the ID is "durov" (without quotes).',
				'exampleValue' => 'durov'
			),
			'postAmount' => array(
				'name' => 'Number of posts to download and parse (maximum 10 posts)',
				'title' => 'Use this to speed up feed generating',
				'defaultValue' => 5,
				'type' => 'number'
			), // again, this parameter and last option are named this way for compatibility
			'hide_reposts' => array(
				'name' => 'Remove reposts',
				'title' => 'Check "Repost-only", if you don\'t want repost-only posts,
or check "All", if you don\'t want posts with reposts at all.',
				'type' => 'list',
				'values' => array(
					'None' => 'none',
					'Repost-only' => 'only',
					'All' => 'on'
				)
			),
			'dontConvertEmoji' => array(
				'name' => 'Don\'t convert emojis',
				'title' => 'Check this if you don\'t want to convert vk\'s own emojis to unicode emojis.',
				'type' => 'checkbox'
			),
			'inlineComments' => array(
				'name' => 'Inline comments',
				'title' => 'Check this, if you want to inline comments from posts.',
				'type' => 'checkbox'
			),
			'topCommentThreshold' => array(
				'name' => 'Top comment threshold',
				'title' => 'Remove branches with root comments with less then this number of likes.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'branchCommentThreshold' => array(
				'name' => 'Branch threshold',
				'title' => 'Remove branches that have no comments that have more than this number of likes. Usually all you need is
to set this parameter and "Descending comment threshold" parameter to one value to your liking.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'descendingCommentThreshold' => array(
				'name' => 'Descending comment threshold',
				'title' => 'Remove descending comments with less than this number of likes.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'hardDescendingCommentThreshold' => array(
				'name' => 'Hard descending comment threshold',
				'title' => 'Check this, if you don\'t want to keep all comments between valid descending comments.
Checking this may break flow of the conversation in comments.',
				'type' => 'checkbox',
			),
			'descendingCommentThresholdOffset' => array(
				'name' => 'Descending comment threshold offset',
				'title' => 'Show this number of comments after last valid descending comment.',
				'defaultValue' => 0,
				'type' => 'number'
			),
			'dontAddDeletedAmount' => array(
				'name' => 'Don\'t add placeholders with amount of deleted comments',
				'title' => 'Check this to not display placeholders with the number
of deleted comments in the place of those comments.',
				'type' => 'checkbox',
			),
			'showErrors' => array(
				'name' => 'Show errors',
				'title' => 'Check "Errors-only", if you want only errors reports,
or check "Both", if you want content AND errors reports.',
				'type' => 'list',
				'values' => array(
					'Content-only' => 'content',
					'Errors-only' => 'errors',
					'Both' => 'both'
				)
			),
			'wrapImagesInLinks' => array(
				'name' => 'Wrap images in links to originals',
				'title' => 'Check this, if you want to open images originals 
from feed item by tapping\clicking on thumbnail.',
				'type' => 'checkbox'
			),
			'dontWrapArticleThumbnailsInLinks' => array(
				'name' => 'Don\'t wrap article thumbnail in link to article',
				'title' => 'Check this, if you don\'t want to open article 
from feed item by tapping\clicking on thumbnail.',
				'type' => 'checkbox'
			),
		)
	);
	const CACHE_TIMEOUT = 2700; // 45 min
	const POST_CACHE_TIMEOUT = 3600; // 60 min

	private $logs = array();
	private $sourceName;

	public function collectData() {
		$showErrors = $this->getInput('showErrors');

		try {
			$posts = $this->getPosts();
		} catch(\Exception $e) {
			returnServerError($e->getMessage());
		}

		$this->sourceName = $posts[0]['source'];

		foreach($posts as $post) {
			if(is_array($post)) {
				$item = $this->getItem($post);

				if($item !== 'Removed' && $showErrors !== 'errors') {
					$this->items[] = $item;
				}
			}
		}

		if(($showErrors === 'errors' || $showErrors === 'both') && count($this->logs) !== 0) {
			$this->items[] = $this->getErrorsItem();
		}
	}

	public function getName() {
		if($this->sourceName !== null) {
			return $this->sourceName;
		} else {
			return self::NAME;
		}
	}

	public function getURI() {
		$sourceId = $this->getInput('u');
		if($sourceId != '') {
			return "https://vk.com/$sourceId";
		} else {
			return self::URI;
		}
	}

	private function getPosts() {
		$sourceId = $this->getInput('u');

		$extractorOptions = $this->getExtractorOptions();

		$getDoms = function($urls, $context) {
			return $this->parallelDownload($urls);
		};

		$log = function($message) {
			$this->log($message);
		};

		$extractor = new Extractor($getDoms, $log, $extractorOptions);

		$posts = $extractor->getPostsFromSource($sourceId);

		return $posts;
	}

	private function parallelDownload($urls) {
		$doms = array();

		$handles = array();

		$multiHandle = curl_multi_init();

		foreach($urls as $url) {
			$handle = curl_init($url);

			curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);

			curl_setopt($handle, CURLOPT_HTTPHEADER, array('Accept-language: en'));

			curl_setopt($handle, CURLOPT_USERAGENT, ini_get('user_agent'));
			curl_setopt($handle, CURLOPT_ENCODING, '');
			curl_setopt($handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

			$handles[$url] = $handle;
			curl_multi_add_handle($multiHandle, $handle);
		}

		do {
			$status = curl_multi_exec($multiHandle, $active);
			if ($active) {
				curl_multi_select($multiHandle);
			}
		} while ($active && $status == CURLM_OK);

		foreach($handles as $url => $handle) {
			$str = curl_multi_getcontent($handle);

			if(!mb_detect_encoding($str, 'UTF-8', true)) {
				$str = iconv('windows-1251', 'utf-8', $str);
			}

			try {
				$dom = new Document($str);
				$doms[$url] = $dom;
			} catch(\Exception $e) {
				$doms[$url] = null;
			}

			curl_multi_remove_handle($multiHandle, $handle);
		}

		curl_multi_close($multiHandle);

		return $doms;
	}

	private function log($message) {
		$this->logs[] = $message;
	}

	private function getItem($post) {
		if(!empty($post['repost'])) {
			$type = $this->getInput('hide_reposts');
			if($type === 'on' || $type === 'only' && $this->postIsEmpty($post)) {
				return 'Removed';
			}
		}

		$item = array();

		$item['uri'] = $this->getItemURI($post);
		$item['title'] = $this->getItemTitle($post);
		$item['timestamp'] = $this->getItemTimestamp($post);
		$item['author'] = $this->getItemAuthor($post);
		$item['content'] = $this->getItemContent($post);
		$item['enclosures'] = $this->getItemEnclosures($post);
		$item['categories'] = $this->getItemCategories($post);
		$item['uid'] = $this->getItemUID($post);

		return $item;
	}

	private function getItemURI($post) {
		return $post['url'];
	}

	private function getItemTitle($post) {
		$titleGeneratorOptions = $this->getTitleGeneratorOptions();

		$titleGenerator = new TitleGenerator($titleGeneratorOptions);
		$titleGenerator->setPost($post);

		return $titleGenerator->generateTitle();
	}

	private function getItemTimestamp($post) {
		// prepend '@' to Unix timestamp so that strtotime() can use it, because item[timestamp] must be valid strtotime() input
		// see https://github.com/RSS-Bridge/rss-bridge/wiki/The-collectData-function#item-parameters
		return '@' . $post['timestamp'];
	}

	private function getItemAuthor($post) {
		return $post['author'];
	}

	private function getItemContent($post) {
		$formatterOptions = $this->getFormatterOptions();

		$formatter = new Formatter($formatterOptions);
		$formatter->setPost($post);

		$content = $formatter->formatContent() . $formatter->formatComments();

		if($this->getInput('showErrors') === 'both') {
			$content .= $this->formatPostExtractingErrors($post['id']);
		}

		return $content;
	}

	private function getItemEnclosures($post) {
		$enclosures = array();

		foreach($post['images'] as $image) {
			$enclosures[] = $image['original'];
		}

		foreach($post['videos'] as $video) {
			// highest quality video
			if(isset($video['urls'][0])) {
				$enclosures[] = $video['urls'][0];
			}
		}

		foreach($post['files'] as $file) {
			$enclosures[] = $file['url'];
		}

		foreach($post['audios'] as $audio) {
			$enclosures[] = $audio['url'];
		}

		if(!empty($post['poster'])) {
			$enclosures[] = $post['poster']['image'];
		}

		if(!empty($post['article'])) {
			$enclosures[] = $post['article']['image'];
		}

		if(!empty($post['map'])) {
			$enclosures[] = $post['map']['image'];
		}

		if(!empty($post['expandedLink'])) {
			$enclosures[] = $post['expandedLink']['image'];
		}

		if(!empty($post['repost'])) {
			$enclosures = array_merge($enclosures, $this->getItemEnclosures($post['repost']));
		}

		return $enclosures;
	}

	private function getItemCategories($post) {
		return $post['tags'];
	}

	private function getItemUID($post) {
		return $post['id'];
	}

	private function formatPostExtractingErrors($postId) {
		$content = '';

		if($postId === null) {
			$content .= 'Failed to extract post\'s id';
		} else {
			foreach($this->logs as $pos => $message) {
				if(isset($message['postId']) && $message['postId'] === $postId) {
					$content .= $this->formatError($message) . '<br/>';
					unset($this->logs[$pos]);
				}
			}
		}

		if($content !== '') {
			$content = '<hr/>' . $content;
		}

		return $content;
	}

	private function formatError($message) {
		$content = $message['text'];

		if(isset($message['postId'])) {
			if($message['postId'] !== null) {
				$content .= " at post <a href='https://vk.com/wall$message[postId]'>$message[postId]</a>";
			} else {
				$content .= ' at unknown post';
			}
		}

		if(isset($message['commentId'])) {
			if($message['commentId'] !== null) {
				$content .= " at comment <a href='https://vk.com/wall$message[commentId]'>$message[commentId]</a>";
			} else {
				$content .= ' at unknown comment';
			}
		}

		return $content;
	}

	private function getErrorsItem() {
		$item = array();

		$item['uri'] = '';

		$errorsAmount = count($this->logs);
		if($errorsAmount > 1) {
			$item['title'] = "$errorsAmount errors were found";
		} else {
			$item['title'] = '1 error was found';
		}

		$item['timestamp'] = '@' . time();
		$item['author'] = 'RSS-Bridge';

		$item['content'] = '';
		foreach ($this->logs as $message) {
			$item['content'] .= $this->formatError($message) . '<br/>';
		}

		$item['enclosures'] = array();
		$item['categories'] = array();
		$item['uid'] = time();

		return $item;
	}

	private function getTitleGeneratorOptions() {
		return array(

		);
	}

	private function getFormatterOptions() {
		return array(
			'topCommentThreshold' => $this->getInput('topCommentThreshold'),
			'branchCommentThreshold' => $this->getInput('branchCommentThreshold'),
			'descendingCommentThreshold' => $this->getInput('descendingCommentThreshold'),
			'hardDescendingCommentThreshold' => $this->getInput('hardDescendingCommentThreshold'),
			'descendingCommentThresholdOffset' => $this->getInput('descendingCommentThresholdOffset'),
			'dontAddDeletedAmount' => $this->getInput('dontAddDeletedAmount'),
			'dontConvertEmoji' => $this->getInput('dontConvertEmoji'),
			'wrapImagesInLinks' => $this->getInput('wrapImagesInLinks'),
			'wrapArticleThumbnailsInLinks' => !$this->getInput('dontWrapArticleThumbnailsInLinks'),
		);
	}

	private function getExtractorOptions() {
		return array(
			'extractComments' => $this->getInput('inlineComments'),
			'postsAmount' => $this->getInput('postAmount')
		);
	}
}
