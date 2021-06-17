<?php
namespace d7sd6u\VKPostsTitleGenerator;

class Generator {
	private $options;
	private $post;

	const defaultOptions = array();

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

	public function generateTitle() {
			$isEmpty = $this->postIsEmpty($this->post);
			$hasRepost = !empty($this->post['repost']);
			$hasPool = !empty($this->post['pool']);
			$hasText = !empty($this->post['text']['plaintext']);
			$hasArticle = !empty($this->post['article']);
			$hasPoster = !empty($this->post['poster']);
			$extras = array(
				array('type' => 'images', 'amount' => count($this->post['images'])),
				array('type' => 'videos', 'amount' => count($this->post['videos'])),
				array('type' => 'files', 'amount' => count($this->post['files'])),
				array('type' => 'audios', 'amount' => count($this->post['audios'])),
				array('type' => 'expandedLinks', 'amount' => !empty($this->post['expandedLink'])),
				array('type' => 'map', 'amount' => !empty($this->post['map'])),
			);

			if($isEmpty && $hasRepost) {
				$title = $this->post['source'] . ' reposted ' . $this->post['repost']['source'];
			} elseif($hasPoster) {
				$title = $this->post['poster']['text'];
			} elseif($hasText) {
				$text = $this->post['text']['plaintext'];
				$text = preg_replace('/#([\w\pL]+)(@[\w\pL]+)?/u', '', $text); // remove hashtags
				$text = str_replace(array('\r\n', '\n', '\r'), ' ', $text); // remove newlines
				if(mb_strlen($text) > 4) {
					$title = mb_strimwidth($text, 0, 60, '...');
				} else {
					$title = $this->post['source'] . ' posted short message';
				}
			} elseif($hasArticle) {
				$title = $this->post['source'] . ' posted article "';
				$title .= $this->post['article']['title'];
				$title .= '"';
			} elseif($hasPool) {
				$title = $this->post['source'] . ' posted pool "';
				$title .= $this->post['pool']['title'];
				$title .= '"';
			} elseif(count($this->post['videos']) === 1 && count($this->post['images']) === 0) {
				$title = $this->post['source'] . ' posted video "';
				$title .= $this->post['videos'][0]['title'];
				$title .= '"';
			} else {
				$title = $this->post['source'] . ' posted ';

				// traverse through extras and remove nonexistent ones
				$extrasInitialSize = count($extras);
				for($pos = 0; $pos < $extrasInitialSize; $pos++) {
					// also will remove nonexistent maps and expanded links, which have bool 'false' in 'amount'
					if($extras[$pos]['amount'] == 0) {
						unset($extras[$pos]);
					}
				}
				$extras = array_values($extras);
				if(empty($extras)) {
					$title .= 'unknown message';
				} else {
					for($pos = 0; $pos < count($extras); $pos++) {
						switch($extras[$pos]['type']) {
							case 'videos':
								$title .= 'video';
								break;
							case 'images':
								$title .= 'image';
								break;
							case 'files':
								$title .= 'file';
								break;
							case 'audios':
								$title .= 'audio';
								break;
							case 'expandedLinks':
								$title .= 'link';
								break;
							case 'map':
								$title .= 'map';
								break;
						}
						if($extras[$pos]['amount'] > 1) {
							$title .= 's';
						}
						// if not penultimate, than add a comma, else if not the last add 'and'
						if($pos === count($extras) - 2) {
							$title .= ' and ';
						} elseif($pos != count($extras) - 1) {
							$title .= ', ';
						}
					}
				}
			}

			return $title;
	}

	private function postIsEmpty($post) {
		$isEmpty = true;
		$contents = array('content', 'images', 'files', 'audios', 'videos', 'map',
			'pool', 'poster', 'article', 'expandedLinks');
		foreach($contents as $part) {
			$isEmpty = $isEmpty && empty($post[$part]);
		}
		return $isEmpty;
	}

}

?>