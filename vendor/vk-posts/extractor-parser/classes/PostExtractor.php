<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once "GenericExtractor.php";
require_once "CommentExtractor.php";
require_once "PartExtractor.php";

class PostExtractor extends GenericExtractor {
	private $postDom;

	private $postBody;

	private $partExtractor;
	private $commentExtractor;

	private $post;

	public function __construct($getDoms, $log, $options, $predownload) {
		parent::__construct($getDoms, $log, $options);

		$this->predownload = $predownload;

		$postLog = function($message) {
			$this->log($message);
		};

		$this->partExtractor = new PartExtractor($getDoms, $postLog, $this->options);

		$this->commentExtractor = new CommentExtractor($getDoms, $postLog, $this->options);
	}

	private function predownload($urls) {
		($this->predownload)($urls);
	}

	protected function log($message) {
		$enrichedMessage = array();

		if(is_string($message)) {
			$enrichedMessage['text'] = $message;
		} else {
			$enrichedMessage = $message;
		}

		if(isset($this->post['id'])) {
			$enrichedMessage['postId'] = $this->post['id'];
		} else {
			$enrichedMessage['postId'] = null;
		}

		($this->inheritedLog)($enrichedMessage);
	}

	public function setDom($postDom) {
		assertc(has($postDom, '.wall_post_cont', $contElem), 'setDom() failed to find .wall_post_cont');
		
		$this->postDom = $postDom;

		$this->postBody = cleanUrls($contElem);

		$this->post = array();
	}

	public function getNeededUrls() {
		$urls = array();
		
		$hasRepost = has($this->postDom, '.copy_quote', $repostElem);
		if($hasRepost) {
			$repostUrl = $this->extractPostRepostUrl($repostElem);
			$repostIsComment = preg_match('/wall(-?\d+_\d+).+reply=\d+/', $repostUrl, $matches);
			if(!$repostIsComment) {
				$urls[] = $repostUrl;
			}
		}

		$needsMobileDom = has($this->postDom, '.page_media_place');
		if($needsMobileDom) {
			$urls[] = getMobilePostUrlFromId($this->extractPostId());
		}

		foreach($this->postDom->find('.page_post_thumb_video') as $videoElem) {
			if(preg_match('!video(-?\d+_\d+)(\?list=\w+)?!', $videoElem->getAttribute('href'), $matches)) {
				$urls[] = 'https://m.vk.com/' . $matches[0];
			}
		}

		return $urls;
	}

	public function extractPost() {
		$this->post['id'] = $this->extractPostId();

		$this->post['author'] = $this->extractPostAuthor();
		$this->post['source'] = $this->extractPostSource();
		$this->post['origin'] = $this->extractPostOrigin();

		$this->post['timestamp'] = $this->extractPostTimestamp();

		$this->post['tags'] = $this->extractPostTags();

		$this->post['text'] = $this->extractPostText();

		$this->post['images'] = $this->extractPostImages();
		$this->post['videos'] = $this->extractPostVideos();
		$this->post['audios'] = $this->extractPostAudios();
		$this->post['files'] = $this->extractPostFiles();

		$this->post['map'] = $this->extractPostMap();
		$this->post['pool'] = $this->extractPostPool();
		$this->post['poster'] = $this->extractPostPoster();
		$this->post['article'] = $this->extractPostArticle();
		$this->post['expandedLink'] = $this->extractPostExpandedLink();

		$this->post['url'] = $this->extractPostUrl();

		if($this->options['extractComments']) {
			$this->post['comments'] = $this->extractPostComments();
		} else {
			$this->post['comments'] = null;
		}

		$this->post['repost'] = $this->extractPostRepost();

		return $this->post;
	}

	private function extractPostAuthor() {
		$authorIsSpecified = has($this->postDom, '.wall_post_cont .wall_signed_by', $authorElem);
		if($authorIsSpecified) {
			return $authorElem->text();
		} else {
			return $this->extractPostSource();
		}
	}

	private function extractPostSource() {
		if(has($this->postDom, '.post_author .author', $sourceElem)) {
			return $sourceElem->text();
		} else {
			$this->log('Failed to extract post\'s source');
			return null;
		}
	}

	private function extractPostOrigin() {
		$origin = array();
		$hasOrigin = has($this->postDom, '.Post__copyrightLink', $originElem);
		if($hasOrigin) {
			$origin['name'] = $this->extractPostOriginName($originElem);
			$origin['link'] = $this->extractPostOriginLink($originElem);
		}
		return $origin;
	}

	private function extractPostOriginName($originElem) {
		if(preg_match('/^Source: (.*)$/', $originElem->text(), $matches)) {
			return $matches[1];
		} else {
			$this->log('Failed to extract post\'s origin name');
		}
	}

	private function extractPostOriginLink($originElem) {
		if(hasAttr($originElem, 'href', false, $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('extractOrigin() failed to extract origin\'s link');
		}
	}

	private function extractPostTimestamp() {
		if(has($this->postDom, '.post_header .rel_date', $timestampElem)) {
			return $this->partExtractor->extractTimestamp($timestampElem);
		} else {
			$this->log('Failed to find timestamp element');
		}
	}

	private function extractPostTags() {
		$categories = array();
		$links = $this->postBody->find('.wall_post_text > a');
		foreach($links as $link) {
			// if link is valid tag: #<tag's word chars(classic word char and all unicode letters)>@<optional group name's chars>
			if(preg_match('/^#([\w\pL]+)(@[\w\pL]+)?$/u', $link->text(), $matches)) {
				$categories[] = $matches[1]; // then take real tag
			}
		}
		return $categories;
	}

	private function extractPostText() {
		return $this->partExtractor->extractText($this->postBody->find('.wall_post_text'));
	}

	private function extractPostImages() {
		return $this->partExtractor->extractImages($this->postBody);
	}

	private function extractPostVideos() {
		return $this->partExtractor->extractVideos($this->postBody);
	}

	private function extractPostAudios() {
		return $this->partExtractor->extractAudios($this->postBody);
	}

	private function extractPostFiles() {
		return $this->partExtractor->extractFiles($this->postBody);
	}

	private function extractPostMap() {
		$map = array();
		if(has($this->postBody, '.page_media_place')) {
			// if post has other media other than map, than map will be hidden, but on mobile version that is not the case
			// ...also desktop version doesn't have link to map, therefore extracting map from mobile version
			$mobilePostDom = $this->getDom(getMobilePostUrlFromId($this->extractPostId()));

			$isNormalMap = has($mobilePostDom, '.medias_map', $mapElem);
			if($isNormalMap) {
				$map['text'] = $this->extractPostMapText($mapElem);
				$map['image'] = $this->extractPostMapImage($mapElem);
				$map['url'] = $this->extractPostMapUrl($mapElem);
			} else {
				// sometimes map is shrinked down even on mobile
				$isShrinkedMap = has($mobilePostDom, '.medias_link[href*="https://maps.google.com"]', $mapElem);
				if($isShrinkedMap) {
					$map['text'] = $this->extractPostShrinkedMapTitle($mapElem);
					$map['image'] = null;
					$map['url'] = $this->extractPostShrinkedMapUrl($mapElem);
				} else {
					$this->log('Failed to extract map');
					return null;
				}
			}
		}
		return $map;
	}

	private function extractPostMapTitle($mapElem) {
		if(has($mapElem, '.medias_map_first_line', $firstElem) && has($mapElem, '.medias_map_second_line', $secondElem)) {
			$firstLine = $firstElem->text();
			$secondLine = $secondElem->text();

			return (empty($firstLine) ? 'Unknown' : $firstLine) . ', ' . (empty($secondLine) ? 'Unknown' : $secondLine);
		} else {
			$this->log('Failed to extract map info');
		}
	}

	private function extractPostMapImage($mapElem) {
		if(has($mapElem, '.medias_map_img', $imageElem)) {
			try {
				$rawImage = 'https:' . extractBackgroundImage($imageElem);
			} catch (\Exception $e) {
				$this->log('Failed to extract background image in extractPostMapImage(): ' . $e->getMessage());
				return null;
			}

			$rawImage = preg_replace('/&key=.*$/', '', $rawImage); // map provider refuses to give image by url with "key" parameter
			return preg_replace('/&size=\d+,\d+/', '&size=450,450', $rawImage); // take square image of better resolution
		} else {
			$this->log('Failed to extract map image');
		}
	}

	private function extractPostMapUrl($mapElem) {
		if(hasAttr($mapElem, 'href', '.medias_map_fill', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract map url');
		}
	}

	private function extractPostShrinkedMapTitle($mapElem) {
		if(has($mapElem, '.medias_link_title', $titleElem)) {
			return $titleElem->text();
		} else {
			$this->log('Failed to extract shrinked map title');
		}
	}

	private function extractPostShrinkedMapUrl($mapElem) {
		if(hasAttr($mapElem, 'href', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract shrinked map url');
		}
	}

	private function extractPostPool() {
		$pool = array();
		$hasPool = has($this->postBody, '.post_media_voting', $poolElem);
		if($hasPool) {
			$pool['title'] = $this->extractPostPoolTitle($poolElem);
			$pool['author'] = $this->extractPostPoolAuthor($poolElem);
			$pool['type'] = $this->extractPostPoolType($poolElem);
			$pool['options'] = $this->extractPostPoolOptions($poolElem);
			$pool['total'] = $this->extractPostPoolTotal($poolElem);
		}
		return $pool;
	}

	private function extractPostPoolTitle($poolElem) {
		if(has($poolElem, '.media_voting_question', $questionElem)) {
			return $questionElem->text();
		} else {
			$this->log('Failed to extract pool title');
		}
	}

	private function extractPostPoolAuthor($poolElem) {
		if(has($poolElem, '.media_voting_author', $authorElem)) {
			return $authorElem->text();
		} else {
			$this->log('Failed to extract pool author');
		}
	}

	private function extractPostPoolType($poolElem) {
		if(has($poolElem, '.media_voting_subtitle', $subtitleElem)) {
			return $subtitleElem->text();
		} else {
			$this->log('Failed to extract pool type');
		}
	}

	private function extractPostPoolOptions($poolElem) {
		if(has($poolElem, '._media_voting_footer_voted_text b')) {
			$options = array();

			foreach($poolElem->find('.media_voting_option_text') as $optionElem) {
				$options[] = $optionElem->firstChild()->text();
			}

			return $options;
		} else {
			$this->log('Failed to extract pool options');
		}
	}

	private function extractPostPoolTotal($poolElem) {
		$hasTotal = has($this->postBody, '._media_voting_footer_voted_text b', $totalElem);
		if($hasTotal) {
			return $totalElem->text();
		} else {
			return 0;
		}
	}

	private function extractPostPoster() {
		$poster = array();
		$hasPoster = has($this->postBody, '.poster', $posterElem);
		if($hasPoster) {
			$poster['text'] = $this->extractPostPosterText($posterElem);
			$poster['image'] = $this->extractPostPosterImage($posterElem);
		}
		return $poster;
	}

	private function extractPostPosterText($posterElem) {
		if(has($posterElem, '.poster__text', $textElem)) {
			return $textElem->text();
		} else {
			$this->log('Failed to extract poster text');
		}
	}

	private function extractPostPosterImage($posterElem) {
		if(has($posterElem, '.poster__image', $imageElem)) {
			try {
				return extractBackgroundImage($imageElem);
			} catch (\Exception $e) {
				$this->log('Failed to extract background image in extractPostPosterImage(): ' . $e->getMessage());
			}
		} else {
			$this->log('Failed to extract poster image');
		}
	}

	private function extractPostArticle() {
		return $this->partExtractor->extractArticle($this->postBody);
	}

	private function extractPostExpandedLink() {
		$link = array();

		if(has($this->postBody, '.thumbed_link', $linkElem)) {
			$link['title'] = $this->extractPostThumbedLinkTitle($linkElem);
			$link['url'] = $this->extractPostThumbedLinkUrl($linkElem);
			$link['image'] = $this->extractPostThumbedLinkImage($linkElem);
		} elseif(has($this->postBody, '.media_link', $linkElem)) {
			$link['title'] = $this->extractPostMediaLinkTitle($linkElem);
			$link['url'] = $this->extractPostMediaLinkUrl($linkElem);
			$link['image'] = $this->extractPostMediaLinkImage($linkElem);
		}

		return $link;
	}

	private function extractPostThumbedLinkTitle($linkElem) {
		if(has($linkElem, '.thumbed_link__title', $titleElem)) {
			return $titleElem->text();
		} else {
			$this->log('Failed to extract thumbed link title');
		}
	}

	private function extractPostThumbedLinkUrl($linkElem) {
		if(hasAttr($linkElem, 'href', '.thumbed_link__title', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract thumbed link url');
		}
	}

	private function extractPostThumbedLinkImage($linkElem) {
		if(has($linkElem, '.thumbed_link__thumb', $thumbElem)) {
			try {
				return extractBackgroundImage($thumbElem);
			} catch (\Exception $e) {
				$this->log('Failed to extract background image in extractPostThumbedLinkImage(): ' . $e->getMessage());
			}
		} else {
			$this->log('Failed to extract thumbed link image');
		}
	}

	private function extractPostMediaLinkTitle($linkElem) {
		if(has($linkElem, '.media_link__title', $titleElem)) {
			return $titleElem->text();
		} else {
			$this->log('Failed to extract media link title');
		}
	}

	private function extractPostMediaLinkUrl($linkElem) {
		if(hasAttr($linkElem, 'href', '.media_link__media', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract media link url');
		}
	}

	private function extractPostMediaLinkImage($linkElem) {
		if(hasAttr($linkElem, 'src', '.media_link__photo', $srcAttr)) {
			return $srcAttr;
		} else {
			$this->log('Failed to extract media link image');
		}
	}

	private function extractPostId() {
		if(has($this->postDom, '.post', $postElem)) {
			$postId = substr($postElem->getAttribute('id'), 4);

			if(preg_match('/^-?\d+_\d+$/', $postId)) {
				return $postId;
			} else {
				throw new \Exception('extractPostId() failed to extract post id');
				//$this->log('extractPostId() failed to extract post id');
			}
		} else {
			throw new \Exception('extractPostId() failed to find post element');
			//$this->log('extractPostId() failed to find post element');
		}
	}

	private function extractPostUrl() {
		$postId = $this->extractPostId();
		if($postId !== null) {
			return getPostUrlFromId($postId);
		} else {
			$this->log('Failed to extract post url');
		}
	}

	private function extractPostComments() {
		$comments = array();

		if(!has($this->postDom, '.replies_list', $commentsElem)) {
			$this->log('Failed to extract comments');
			return null;
		}

		foreach($commentsElem->children() as $branchPart) {
			// if it is branch root
			if(strpos($branchPart->getAttribute('class'), 'reply') !== false) {
				if(isset($comment)) {
					$comments[] = $comment;
				}
				$this->commentExtractor->setComment($branchPart);
				$comment = $this->commentExtractor->extractComment();
				$comment['replies'] = array();
			// else this is branch remainder
			} elseif(strpos($branchPart->getAttribute('class'), 'replies_wrap_deep') !== false) {
				$comment['replies'] = $this->extractPostCommentsBranchRemainder($branchPart);
			}
		}

		if(isset($comment)) {
			$comments[] = $comment; // force push last comment branch, since there is no next branch root during processing of which this branch will be added
		}

		return $comments;
	}

	private function extractPostCommentsBranchRemainder($remainderElem) {
		$remainder = array();

		foreach($remainderElem->find('.reply') as $commentElem) {
			$this->commentExtractor->setComment($commentElem);
			$remainder[] = $this->commentExtractor->extractComment();
		}

		if(!empty($remainder)) {
			return $remainder;
		} else {
			$this->log('Failed to extract comment branch remainder');
		}
	}

	private function extractPostRepost() {
		$repost = array();
		$hasRepost = has($this->postDom, '.copy_quote', $repostElem);
		if($hasRepost) {
			$repostId = $this->extractPostRepostId($repostElem);
			$repostUrl = $this->extractPostRepostUrl($repostElem);

			if($repostId === null || $repostUrl === null) {
				return null;
			}

			$repostIsComment = preg_match('/wall(-?\d+_\d+).+reply=\d+/', $repostUrl, $matches);
			if($repostIsComment) {
				$repost = $this->extractPostRepostedComment();
			} else {
				$repostCleanUrl = getPostUrlFromId($repostId);
				$repostDom = $this->getDom($repostCleanUrl, 'post');
				$repostExtractor = new PostExtractor($this->getDoms, $this->inheritedLog, $this->options, $this->predownload);
				$repostExtractor->setDom($repostDom);
				$this->predownload($repostExtractor->getNeededUrls());
				$repost = $repostExtractor->extractPost();
			}
		}
		return $repost;
	}

	private function extractPostRepostId($repostElem) {
		if(hasAttr($repostElem, 'data-post-id', '.copy_author', $dataAttr)) {
			return $dataAttr;
		} else {
			$this->log('Failed to extract repost id');
		}
	}

	private function extractPostRepostUrl($repostElem) {
		if(hasAttr($this->postDom, 'href', '.copy_post_date .published_by_date', $hrefAttr)) {
			return 'https://vk.com' . $hrefAttr;
		} else {
			$this->log('Failed to extract repost url');
		}
	}

	private function extractPostRepostedComment() {
		$repostElem = $this->postDom->first('.copy_quote');

		$repost = array();

		$repost['id'] = $this->extractPostRepostedCommentId($repostElem);

		$repost['author'] = $this->extractPostRepostedCommentAuthor($repostElem);
		$repost['source'] = $repost['author'];
		$repost['origin'] = null;

		$repost['timestamp'] = $this->extractPostRepostedCommentTimestamp($repostElem);

		$repost['tags'] = array();

		$repost['text'] = $this->partExtractor->extractText($repostElem->find('.wall_post_text'));

		$repost['images'] = $this->partExtractor->extractImages($repostElem);
		$repost['files'] = $this->partExtractor->extractFiles($repostElem);
		$repost['audios'] = $this->partExtractor->extractAudios($repostElem);
		$repost['videos'] = $this->partExtractor->extractVideos($repostElem);

		$repost['map'] = array();
		$repost['pool'] = array();
		$repost['poster'] = array();
		$repost['article'] = array();
		$repost['expandedLink'] = array();

		$repost['url'] = $this->extractPostRepostedCommentUrl($repostElem);

		$repost['comments'] = array();

		$repost['repost'] = null;

		return $repost;
	}

	private function extractPostRepostedCommentId($repostElem) {
		if(hasAttr($repostElem, 'data-post-id', '.copy_author', $dataAttr)) {
			return $dataAttr;
		} else {
			$this->log('Failed to extract reposted comment id');
		}
	}

	private function extractPostRepostedCommentAuthor($repostElem) {
		if(has($repostElem, '.copy_author', $authorElem)) {
			return $authorElem->text();
		} else {
			$this->log('Failed to extract reposted comment author');
		}
	}

	private function extractPostRepostedCommentTimestamp($repostElem) {
		if(has($repostElem, '.copy_post_date .published_by_date', $timestampElem)) {
			return $this->partExtractor->extractTimestamp($timestampElem);
		} else {
			$this->log('Failed to extract reposted comment timestamp');
		}
	}

	private function extractPostRepostedCommentUrl($repostElem) {
		if(hasAttr($repostElem, 'href', '.copy_post_date .published_by_date', $hrefAttr)) {
			return $hrefAttr;
		} else {
			$this->log('Failed to extract reposted comment url');
		}
	}
}
