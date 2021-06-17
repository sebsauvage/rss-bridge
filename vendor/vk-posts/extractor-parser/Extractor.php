<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "classes/GenericExtractor.php";
require_once "classes/PostExtractor.php";
require_once "classes/PartExtractor.php";
require_once "Utilities.php";

class Extractor extends GenericExtractor {
	private $postExtractor;
	private $predownloadedUrls = array();

	const defaultOptions = array(
		'extractComments' => true,
		'postsAmount' => 20,
		'baseTimestamp' => null
	);

	public function __construct($getDoms, $log, $options = array()) {
		parent::__construct($getDoms, $log, $options);

		$predownloadedGetDoms = function($urls, $context) {
			$cacheHits = array();
			foreach ($urls as $pos => $url) {
				if(array_key_exists($url, $this->predownloadedUrls)) {
					$cacheHits[$url] = $this->predownloadedUrls[$url];
					unset($urls[$pos]);
				}
			}
			$cacheMisses = ($this->getDoms)($urls, 'part');
			foreach ($cacheMisses as $url => $dom) {
				$this->predownloadedUrls[$url] = $dom;
			}
			return array_merge($cacheHits, $cacheMisses);
		};

		$predownload = function($urls) {
			$doms = ($this->getDoms)($urls, 'part');
			foreach ($doms as $url => $dom) {
				$this->predownloadedUrls[$url] = $dom;
			}
		};

		foreach(self::defaultOptions as $name => $value) {
			if(!isset($options[$name])) {
				$this->options[$name] = self::defaultOptions[$name];
			} else {
				$this->options[$name] = $options[$name];
			}
		}

		$this->postExtractor = new PostExtractor($predownloadedGetDoms, $log, $this->options, $predownload);
	}

	public function getPostsFromSource($sourceId) {
		$posts = array();
		$urls = array();

		$sourceUrl = 'https://vk.com/' . $sourceId;
		$sourceDom = $this->getDom($sourceUrl, 'source');

		if($sourceDom === null) {
			throw new \Exception("Failed to get source dom from this url: $sourceUrl");
		}

		if(has($sourceDom, '.profile_deleted_text')) {
			throw new \Exception("User page was deleted: $sourceId");
		}
		
		if(has($sourceDom, '.group_info_private')) {
			throw new \Exception("This group is private: $sourceId");
		}

		$postsProcessed = 0;
		foreach($sourceDom->find('.post') as $postElem) {
			if($postsProcessed >= $this->options['postsAmount']) {
				break;
			} else {
				$postsProcessed++;
			}

			$postId = substr($postElem->getAttribute('id'), 4);
			if(preg_match('/-?\d+_\d+/', $postId)) {
				$urls[] = getPostUrlFromId($postId);
			} else {
				$this->log("Failed to extract post: failed to extract post\'s id");
				$posts[] = null;
			}
		}

		$postsDoms = $this->getDoms($urls, 'post');

		$this->predownloadUrls($postsDoms);

		foreach($postsDoms as $url => $postDom) {
			if($postDom === null) {
				$this->log("Failed to extract post: failed to get post dom from this url: $url");
				$posts[] = null;
				continue;
			}

			$this->postExtractor->setDom($postDom);

			try {
				$posts[] = $this->postExtractor->extractPost();
			} catch(\Exception $e) {
				$this->log('Failed to extract post: ' . $e->getMessage());
				$posts[] = null;
			}
		}

		return $posts;
	}

	private function predownloadUrls($doms) {
		$urls = array();
		foreach($doms as $dom) {
			$this->postExtractor->setDom($dom);
			$urls = array_merge($urls, $this->postExtractor->getNeededUrls());
		}
		$newPredownloadedUrls = $this->getDoms($urls, 'part');
		$this->predownloadedUrls = array_merge($newPredownloadedUrls, $this->predownloadedUrls);
	}

   	public function getPostById($postId) {
		$url = getPostUrlFromId($postId);

		$postDom = $this->getDom($url, 'post');
		if($postDom === null) {
			throw new \Exception('Failed to get post dom from this url: ' . $url);
		}

		$this->postExtractor->setDom($postDom);

		return $this->postExtractor->extractPost();
	}
}
?>
