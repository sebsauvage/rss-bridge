<?php
namespace d7sd6u\VKPostsFormatterHTML5;

class Formatter {
	private $post;
	private $options;

	const defaultOptions = array(
		'descendingCommentThreshold' => 0,
		'hardDescendingCommentThreshold' => false,
		'descendingCommentThresholdOffset' => 0,
		'dontAddDeletedAmount' => false,
		'topCommentThreshold' => 0,
		'branchCommentThreshold' => 0,
		'dontConvertEmoji' => false,
		'wrapImagesInLinks' => false,
		'wrapArticleThumbnailsInLinks' => true
	);

	public function __construct($options = array()) {
		foreach(self::defaultOptions as $name => $value) {
			if(!isset($options[$name])) {
				$this->options[$name] = self::defaultOptions[$name];
			} else {
				$this->options[$name] = $options[$name];
			}
		}
	}

	public function setPost($post) {
		$this->post = $post;
	}

	public function formatContent() {
		if($this->options['dontConvertEmoji']) {
			$content = $this->post['text']['emojis'];
		} else {
			$content = $this->post['text']['html'];
		}
		if(!empty($content)) {
			$content .= '<br/><br/>';
		}

		$pieces = array();

		$pieces[] = $this->formatImages($this->post['images']);
		$pieces[] = $this->formatVideos($this->post['videos']);
		$pieces[] = $this->formatFiles($this->post['files']);
		$pieces[] = $this->formatAudios($this->post['audios']);
		$pieces[] = $this->formatPool();
		$pieces[] = $this->formatArticle();
		$pieces[] = $this->formatMap();
		$pieces[] = $this->formatPoster();
		$pieces[] = $this->formatExpandedLink();
		$pieces[] = $this->formatRepost();
		$pieces[] = $this->formatOrigin();

		$content .= implode('<br/><br/><br/>', array_filter($pieces));

		return $content;
	}

	public function formatComments() {
		$content = '<br/>';

		if($this->post['comments'] === null) {
			$content .= '<br/>Comments are disabled or their extraction failed';
			return $content;
		}

		$commentsAmount = count($this->post['comments']);
		foreach($this->post['comments'] as $comment) {
			if(isset($comment['replies'])) {
				$commentsAmount += count($comment['replies']);
			}
		}

		if($commentsAmount === 0) {
			$content .= 'No comments.';
		} else {
			$content .= "<details><summary>$commentsAmount+ comments:</summary><br/>";
		}

		$firstIteration = true;
		foreach($this->post['comments'] as $comment) {
			$threshold = $this->options['descendingCommentThreshold'];
			$hard = $this->options['hardDescendingCommentThreshold'];
			$offset = $this->options['descendingCommentThresholdOffset'];
			$addAmount = !$this->options['dontAddDeletedAmount'];

			$separator = '<br/><hr/>';

			// if no descending comment was found, then $i <= $lastValidComment should be always false
			// therefore $lastValidComment by default equals to -1
			$lastValidComment = -1;
			$topCommentLikes = $comment['likes'];
			for($i = 0; $i < count($comment['replies']); $i++) {
				if($comment['replies'][$i]['likes'] > $topCommentLikes) {
					$topCommentLikes = $comment['replies'][$i]['likes'];
				}
				if($comment['replies'][$i]['likes'] >= $threshold) {
					$lastValidComment = $i;
				}
			}
			if($comment['likes'] >= $this->options['topCommentThreshold']
			&& $topCommentLikes >= $this->options['branchCommentThreshold']) {
				if($firstIteration) {
					$firstIteration = false;
				} else {
					$content .= $separator;
				}

				$content .= "<br/><i id='$comment[id]'>Comment: </i><br/>";

				$content .= $this->formatComment($comment, $this->post['id']);

				$deletedAmount = 0;
				for($i = 0; $i < count($comment['replies']); $i++) {
					$reply = $comment['replies'][$i];
					if(!$hard && $i <= ($lastValidComment + $offset) || $hard && $reply['likes'] >= $threshold) {
						// add notice about comments filtered after last valid reply
						if($addAmount && $hard && $deletedAmount !== 0) {
							$content .= "<hr/><i>$deletedAmount repl";
							if($deletedAmount > 1) {
								$content .= 'ies';
							} else {
								$content .= 'y';
							}
							$content .= ' was filtered out';
							$deletedAmount = 0;
						}
						$content .= $separator;
						$content .= "<br/><i><a id='$reply[id]' href='#$reply[replyId]'>Reply: </a></i><br/>";
						$content .= $this->formatComment($reply, $this->post['id']);
					}
					if($addAmount && $hard && $reply['likes'] < $threshold) {
						$deletedAmount++;
					}
				}
				// add notice about comments filtered after last valid reply
				$deletedAmount = count($comment['replies']) - $lastValidComment - $offset - 1;
				if($addAmount && $deletedAmount >= 1) {
					$content .= $separator;
					$content .= "<i>$deletedAmount repl";
					if($deletedAmount > 1) {
						$content .= 'ies';
					} else {
						$content .= 'y';
					}
					$content .= ' was filtered out';
				}
			} elseif($addAmount) {
				// add notice about filtered branch
				if($firstIteration) {
					$firstIteration = false;
				} else {
					$content .= $separator;
				}

				$deletedAmount = 1 + count($comment['replies']);
				$content .= "<i>Branch with $deletedAmount comment";
				if($deletedAmount > 1) {
					$content .= 's';
				}
				$content .= ' was filtered out</i>';
			}
		}

		$content .= '</details>';

		return $content;
	}

	private function formatComment($comment) {
		$content = "<i>Author: <a href='{$comment['author']['link']}'>{$comment['author']['name']}</a></i><br/>";
		$content .= '<i>Avatar: </i>';
		$content .= "<a href='{$comment['author']['link']}'>";
		$content .= "<img src='{$comment['author']['avatar']}'/>";
		$content .= '</a><br/><br/>';

		
		if($this->options['dontConvertEmoji']) {
			$content .= $comment['text']['emojis'];
		} else {
			$content .= $comment['text']['html'];
		}

		$content .= $this->formatImages($comment['images']) . '<br/>';
		$content .= $this->formatVideos($comment['videos']) . '<br/>';
		$content .= $this->formatAudios($comment['audios']);

		$content .= "<br/><br/><i>Likes: </i>$comment[likes]";

		return $content;
	}

	private function formatImages($images) {
		$content = '';
		if(!empty($images)) {
			$pieces = array();
			foreach($images as $image) {
				$pieces[] = $this->formatImage($image);
			}
			$content .= implode('<br/><br/>', $pieces);
		}
		return $content;
	}

	private function formatImage($image) {
		if($this->options['wrapImagesInLinks']) {
			return "<a href='$image[original]'><img src='$image[thumb]'/></a>";
		} else {
			return "<img src='$image[thumb]'/>";
		}
	}

	private function formatVideos($videos) {
		$content = '';
		if(!empty($videos)) {
			$content .= '<i>Attached videos:</i><br/>';
			$pieces = array();
			foreach($videos as $video) {
				$pieces[] = $this->formatVideo($video);
			}
			$content .= implode("<br/>", $pieces);
		}
		return $content;
	}

	private function formatVideo($video) {
		$content = '';
		// TODO: handle blocked videos
		// if successfully extracted video preview, add it to fallback
		if(empty($video['image'])) {
			$videoPreview = $video['title'];
		} else {
			$videoPreview = "<img src='$video[image]'/>";
		}

		if(isset($video['iframe'])) {
			$content .= "<a href='$video[iframe]'>$videoPreview</a>";
		} elseif(!empty($video['urls'])) {
			$content .= '<video controls>';
			foreach($video['urls'] as $source) {
				$content .= "<source src='$source'/>";
			}
			if(count($video['urls']) > 1) {
				$content .= "<a href='{$video['urls'][1]}'>$videoPreview</a>";
			} else {
				// there is no reason to attach broken direct url, so attach native url instead
				$content .= "<a href='$video[nativeUrl]'>$videoPreview</a>";
			}
			$content .= '</video><br/>';
		} else {
			// if no iframe or video was found, then it is most likely private video and only link to video in post will work, not direct one
			$postId = $this->post['id'];
			$content .= "<a href='https://vk.com/post$postId?z=video$video[id]'>$videoPreview</a><br/>";
		}

		return $content;
	}

	private function formatPool() {
		$content = '';
		if(!empty($this->post['pool'])) {
			$content .= "<i>Pool: </i>{$this->post['pool']['title']}<br/><br/>";
			$content .= "<i>Author: </i>{$this->post['pool']['author']}<br/>";
			$content .= "<i>Type: </i>{$this->post['pool']['type']}<br/><br/>";
			foreach($this->post['pool']['options'] as $option) {
				$content .= "<i>Option: </i>$option<br/>";
			}
			$content .= "<br/><i>Total voted: </i>{$this->post['pool']['total']}";
		}
		return $content;
	}

	private function formatFiles($files) {
		$content = '';
		if(!empty($files)) {
			$content .= '<i>Attached files:</i><br/><br/>';
			$pieces = array();
			foreach($files as $file) {
				$pieces[] = $this->formatFile($file);
			}
			$content .= implode("<br/>", $pieces);
		}
		return $content;
	}

	private function formatFile($file) {
		return "<a href='$file[url]'>$file[title]</a>";
	}

	private function formatAudios($audios) {
		$content = '';
		if(!empty($audios)) {
			$content .= '<i>Attached audios:</i><br/><br/>';
			$pieces = array();
			foreach($audios as $audio) {
				$pieces[] = $this->formatAudio($audio);
			}
			$content .= implode("<br/>", $pieces);
		}
		return $content;
	}

	private function formatAudio($audio) {
		return "Audio: <a href='$audio[url]'>$audio[title]</a>";
	}

	private function formatPoster() {
		$content = '';
		if(!empty($this->post['poster'])) {
			$content .= '<i>Poster:</i><br/><br/>';
			$content .= $this->post['poster']['text'];
			$content .= "<br/><img src='{$this->post['poster']['image']}'/>";
		}
		return $content;
	}

	private function formatMap() {
		$content = '';
		if(!empty($this->post['map'])) {
			$content .= "<i>Location: </i>{$this->post['map']['text']}<br/>";
			$content .= '<i>Map:</i><br/>';
			$content .= "<a href='{$this->post['map']['url']}'><img src='{$this->post['map']['image']}'/></a>";
		}
		return $content;
	}

	private function formatArticle() {
		$content = '';
		if(!empty($this->post['article'])) {
			$content .= '<i>Article: </i>';
			$content .= "<a href='{$this->post['article']['url']}'>{$this->post['article']['title']}</a><br/>";
			$content .= "<i>Author: </i>{$this->post['article']['author']}<br/>";
			$content .= "<i>Image: </i><br/>";
			if($this->options['wrapArticleThumbnailsInLinks']) {
				$content .= "<a href='{$this->post['article']['url']}'>";
				$content .= "<img src='{$this->post['article']['image']}'/>";
				$content .= '</a>';
			} else {
				$content .= "<img src='{$this->post['article']['image']}'/>";
			}
		}
		return $content;
	}

	private function formatExpandedLink() {
		$content = '';
		if(!empty($this->post['link'])) {
			$content .= '<i>Link: </i>';
				$content .= "<a href='{$this->post['link']['url']}'>{$this->post['link']['title']}</a><br/>";
			$content .= '<i>Image: </i><br/>';
			$content .= "<a href='{$this->post['link']['url']}'>";
			$content .= "<img src='{$this->post['link']['image']}'/>";
			$content .= '</a>';
		}
		return $content;
	}

	private function formatRepost() {
		$content = '';

		if(!empty($this->post['repost'])) {
			$content .= '<i>Repost:</i><br/><br/>';
			$content .= "<i>Source: </i><a href='{$this->post['repost']['url']}'>{$this->post['repost']['source']}</a><br/>";
			$content .= "<i>Author: </i>{$this->post['repost']['author']}<br/>";
			$content .= '<i>Timestamp: </i>';
			$content .= strftime('%c', $this->post['repost']['timestamp']);
			$content .= '<br/><br/>';

			$formatter = new Formatter($this->options);
			$formatter->setPost($this->post['repost']);

			$content .= $formatter->formatContent();
		}

		return $content;
	}

	private function formatOrigin() {
		$content = '';

		if(!empty($this->post['origin'])) {
			$content .= "<i>Source: </i><a href='{$this->post['origin']['link']}'>{$this->post['origin']['name']}</a>";
		}

		return $content;
	}
}

?>
