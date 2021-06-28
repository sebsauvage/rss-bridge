<?php
class GithubIssueBridge extends BridgeAbstract {

	const MAINTAINER = 'Pierre Mazière';
	const NAME = 'Github Issue';
	const URI = 'https://github.com/';
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = 'Returns the issues or comments of an issue of a github project';

	const PARAMETERS = array(
		'global' => array(
			'u' => array(
				'name' => 'User name',
				'required' => true
			),
			'p' => array(
				'name' => 'Project name',
				'required' => true
			)
		),
		'Project Issues' => array(
			'c' => array(
				'name' => 'Show Issues Comments',
				'type' => 'checkbox'
			),
			'q' => array(
				'name' => 'Search Query',
				'defaultValue' => '?q=is%3Aissue+sort%3Aupdated-desc',
				'required' => false
			)
		),
		'Issue comments' => array(
			'i' => array(
				'name' => 'Issue number',
				'type' => 'number',
				'required' => true
			)
		)
	);

	// Allows generalization with GithubPullRequestBridge
	const BRIDGE_OPTIONS = array(0 => 'Project Issues', 1 => 'Issue comments');
	const URL_PATH = 'issues';
	const SEARCH_QUERY_PATH = 'issues';

	public function getName(){
		$name = $this->getInput('u') . '/' . $this->getInput('p');
		switch($this->queriedContext) {
		case static::BRIDGE_OPTIONS[0]: // Project Issues
			$prefix = static::NAME . 's for ';
			if($this->getInput('c')) {
				$prefix = static::NAME . 's comments for ';
			}
			$name = $prefix . $name;
			break;
		case static::BRIDGE_OPTIONS[1]: // Issue comments
			$name = static::NAME . ' ' . $name . ' #' . $this->getInput('i');
			break;
		default: return parent::getName();
		}
		return $name;
	}

	public function getURI() {
		if(null !== $this->getInput('u') && null !== $this->getInput('p')) {
			$uri = static::URI . $this->getInput('u') . '/'
				 . $this->getInput('p') . '/';
			if($this->queriedContext === static::BRIDGE_OPTIONS[1]) {
				$uri .= static::URL_PATH . '/' . $this->getInput('i');
			} else {
				$uri .= static::SEARCH_QUERY_PATH . $this->getInput('q');
			}
			return $uri;
		}

		return parent::getURI();
	}

	private function buildGitHubIssueCommentUri($issue_number, $comment_id) {
		// https://github.com/<user>/<project>/issues/<issue-number>#<id>
		return static::URI
		. $this->getInput('u')
		. '/'
		. $this->getInput('p')
		. '/' . static::URL_PATH . '/'
		. $issue_number
		. '#'
		. $comment_id;
	}

	private function extractIssueEvent($issueNbr, $title, $comment) {

		$uri = $this->buildGitHubIssueCommentUri($issueNbr, $comment->id);

		$author = $comment->find('.author, .avatar', 0);
		if ($author) {
			$author = trim($author->href, '/');
		} else {
			$author = '';
		}

		$title .= ' / '
			. trim(str_replace(
					array('octicon','-'), array(''),
					$comment->find('.octicon', 0)->getAttribute('class')
			));

		$time = $comment->find('relative-time', 0);
		if ($time === null) {
			return;
		}

		foreach($comment->find('.Details-content--hidden, .btn') as $el) {
			$el->innertext = '';
		}
		$content = $comment->plaintext;

		$item = array();
		$item['author'] = $author;
		$item['uri'] = $uri;
		$item['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$item['timestamp'] = strtotime($time->getAttribute('datetime'));
		$item['content'] = $content;
		return $item;
	}

	private function extractIssueComment($issueNbr, $title, $comment) {
		$uri = $this->buildGitHubIssueCommentUri($issueNbr, $comment->id);

		$author = $comment->find('.author', 0)->plaintext;

		$title .= ' / ' . trim(
			$comment->find('.timeline-comment-header-text', 0)->plaintext
		);

		$time = $comment->find('relative-time', 0);
		if ($time === null) {
			return;
		}

		$content = $comment->find('.comment-body', 0)->innertext;

		$item = array();
		$item['author'] = $author;
		$item['uri'] = $uri;
		$item['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
		$item['timestamp'] = strtotime($time->getAttribute('datetime'));
		$item['content'] = $content;
		return $item;
	}

	private function extractIssueComments($issue) {
		$items = array();
		$title = $issue->find('.gh-header-title', 0)->plaintext;
		$issueNbr = trim(
			substr($issue->find('.gh-header-number', 0)->plaintext, 1)
		);

		$comments = $issue->find(
			'.comment, .TimelineItem-badge'
		);

		foreach($comments as $comment) {
			if ($comment->hasClass('comment')) {
				$comment = $comment->parent;
				$item = $this->extractIssueComment($issueNbr, $title, $comment);
				if ($item !== null) {
					$items[] = $item;
				}
				continue;
			} else {
				$comment = $comment->parent;
				$item = $this->extractIssueEvent($issueNbr, $title, $comment);
				if ($item !== null) {
					$items[] = $item;
				}
			}

		}
		return $items;
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError(
				'No results for ' . static::NAME . ' ' . $this->getURI()
			);

		switch($this->queriedContext) {
		case static::BRIDGE_OPTIONS[1]: // Issue comments
			$this->items = $this->extractIssueComments($html);
			break;
		case static::BRIDGE_OPTIONS[0]: // Project Issues
			foreach($html->find('.js-active-navigation-container .js-navigation-item') as $issue) {
				$info = $issue->find('.opened-by', 0);

				preg_match('/\/([0-9]+)$/', $issue->find('a', 0)->href, $match);
				$issueNbr = $match[1];

				$item = array();
				$item['content'] = '';

				if($this->getInput('c')) {
					$uri = static::URI . $this->getInput('u')
						 . '/' . $this->getInput('p') . '/' . static::URL_PATH . '/' . $issueNbr;
					$issue = getSimpleHTMLDOMCached($uri, static::CACHE_TIMEOUT);
					if($issue) {
						$this->items = array_merge(
							$this->items,
							$this->extractIssueComments($issue)
						);
						continue;
					}
					$item['content'] = 'Can not extract comments from ' . $uri;
				}

				$item['author'] = $info->find('a', 0)->plaintext;
				$item['timestamp'] = strtotime(
					$info->find('relative-time', 0)->getAttribute('datetime')
				);
				$item['title'] = html_entity_decode(
					$issue->find('.js-navigation-open', 0)->plaintext,
					ENT_QUOTES,
					'UTF-8'
				);

				$comment_count = 0;
				if($span = $issue->find('a[aria-label*="comment"] span', 0)) {
					$comment_count = $span->plaintext;
				}

				$item['content'] .= "\n" . 'Comments: ' . $comment_count;
				$item['uri'] = self::URI
							 . trim($issue->find('.js-navigation-open', 0)->getAttribute('href'), '/');
				$this->items[] = $item;
			}
			break;
		}

		array_walk($this->items, function(&$item){
			$item['content'] = preg_replace('/\s+/', ' ', $item['content']);
			$item['content'] = str_replace(
				'href="/',
				'href="' . static::URI,
				$item['content']
			);
			$item['content'] = str_replace(
				'href="#',
				'href="' . substr($item['uri'], 0, strpos($item['uri'], '#') + 1),
				$item['content']
			);
			$item['title'] = preg_replace('/\s+/', ' ', $item['title']);
		});
	}

	public function detectParameters($url) {

		if(filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false
		|| strpos($url, self::URI) !== 0) {
			return null;
		}

		$url_components = parse_url($url);
		$path_segments = array_values(array_filter(explode('/', $url_components['path'])));

		switch(count($path_segments)) {
			case 2: { // Project issues
				list($user, $project) = $path_segments;
				$show_comments = 'off';
			} break;
			case 3: { // Project issues with issue comments
				if($path_segments[2] !== static::URL_PATH) {
					return null;
				}
				list($user, $project) = $path_segments;
				$show_comments = 'on';
			} break;
			case 4: { // Issue comments
				list($user, $project, /* issues */, $issue) = $path_segments;
			} break;
			default: {
				return null;
			}
		}

		return array(
			'u' => $user,
			'p' => $project,
			'c' => isset($show_comments) ? $show_comments : null,
			'i' => isset($issue) ? $issue : null,
		);

	}
}
