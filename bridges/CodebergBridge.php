<?php
class CodebergBridge extends BridgeAbstract {
	const NAME = 'Codeberg Bridge';
	const URI = 'https://codeberg.org/';
	const DESCRIPTION = 'Returns commits, issues, pull requests or releases for a repository.';
	const MAINTAINER = 'VerifiedJoseph';
	const PARAMETERS = array(
		'Commits' => array(
			'branch' => array(
				'name' => 'branch',
				'type' => 'text',
				'exampleValue' => 'main',
				'required' => false,
				'title' => 'Optional, main branch is used by default.',
			),
		),
		'Issues' => array(),
		'Issue Comments' => array(
			'issueId' => array(
				'name' => 'Issue ID',
				'type' => 'text',
				'required' => true,
			)
		),
		'Pull Requests' => array(),
		'Releases' => array(),
		'global' => array(
			'username' => array(
				'name' => 'Username',
				'type' => 'text',
				'exampleValue' => 'username',
				'title' => 'Username of account that the repository belongs to.',
				'required' => true,
			),
			'repo' => array(
				'name' => 'Repository',
				'type' => 'text',
				'exampleValue' => 'repo',
				'required' => true,
			)
		)
	);

	const CACHE_TIMEOUT = 1800;

	private $defaultBranch = 'main';
	private $issueTitle = '';

	private $urlRegex = '/codeberg\.org\/([\w]+)\/([\w]+)(?:\/src\/branch\/([\w]+))?/';
	private $issuesUrlRegex = '/codeberg\.org\/([\w]+)\/([\w]+)\/issues/';
	private $pullsUrlRegex = '/codeberg\.org\/([\w]+)\/([\w]+)\/pulls/';
	private $releasesUrlRegex = '/codeberg\.org\/([\w]+)\/([\w]+)\/releases/';
	private $issueCommentsUrlRegex = '/codeberg\.org\/([\w]+)\/([\w]+)\/issues\/([0-9]+)/';

	public function detectParameters($url) {
		$params = array();

		// Issue Comments
		if(preg_match($this->issueCommentsUrlRegex, $url, $matches)) {
			$params['context'] = 'Issue Comments';
			$params['username'] = $matches[1];
			$params['repo'] = $matches[2];
			$params['issueId'] = $matches[3];

			return $params;
		}

		// Issues
		if(preg_match($this->issuesUrlRegex, $url, $matches)) {
			$params['context'] = 'Issues';
			$params['username'] = $matches[1];
			$params['repo'] = $matches[2];

			return $params;
		}

		// Pull Requests
		if(preg_match($this->pullsUrlRegex, $url, $matches)) {
			$params['context'] = 'Pull Requests';
			$params['username'] = $matches[1];
			$params['repo'] = $matches[2];

			return $params;
		}

		// Releases
		if(preg_match($this->releasesUrlRegex, $url, $matches)) {
			$params['context'] = 'Releases';
			$params['username'] = $matches[1];
			$params['repo'] = $matches[2];

			return $params;
		}

		// Commits
		if(preg_match($this->urlRegex, $url, $matches)) {
			$params['context'] = 'Commits';
			$params['username'] = $matches[1];
			$params['repo'] = $matches[2];

			if (isset($matches[3])) {
				$params['branch'] = $matches[3];
			}

			return $params;
		}

		return null;
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$html = defaultLinkTo($html, $this->getURI());

		switch($this->queriedContext) {
			case 'Commits':
				$this->extractCommits($html);
				break;
			case 'Issues':
				$this->extractIssues($html);
				break;
			case 'Issue Comments':
				$this->extractIssueComments($html);
				break;
			case 'Pull Requests':
				$this->extractPulls($html);
				break;
			case 'Releases':
				$this->extractReleases($html);
				break;
			default:
				returnClientError('Invalid context: ' . $this->queriedContext);
		}
	}

	public function getName() {
		switch($this->queriedContext) {
			case 'Commits':
				if ($this->getBranch() === $this->defaultBranch) {
					return $this->getRepo() . ' Commits';
				}

				return $this->getRepo() . ' Commits (' . $this->getBranch() . ' branch) - ' . self::NAME;
			case 'Issues':
				return $this->getRepo() . ' Issues - ' . self::NAME;
			case 'Issue Comments':
				return $this->issueTitle . ' - Issue Comments - ' . self::NAME;
			case 'Pull Requests':
				return $this->getRepo() . ' Pull Requests - ' . self::NAME;
			case 'Releases':
				return $this->getRepo() . ' Releases - ' . self::NAME;
			default:
				return parent::getName();
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'Commits':
				return self::URI . $this->getRepo() . '/commits/branch/' . $this->getBranch();
			case 'Issues':
				return self::URI . $this->getRepo() . '/issues/';
			case 'Issue Comments':
				return self::URI . $this->getRepo() . '/issues/' . $this->getInput('issueId');
			case 'Pull Requests':
				return self::URI . $this->getRepo() . '/pulls';
			case 'Releases':
				return self::URI . $this->getRepo() . '/releases';
			default:
				return parent::getURI();
		}
	}

	private function getBranch() {
		if ($this->getInput('branch')) {
			return $this->getInput('branch');
		}

		return $this->defaultBranch;
	}

	private function getRepo() {
		return $this->getInput('username') . '/' . $this->getInput('repo');
	}

	private function extractCommits($html) {
		$table = $html->find('table#commits-table', 0);
		$tbody = $table->find('tbody.commit-list', 0);

		foreach ($tbody->find('tr') as $tr) {
			$item = array();

			$message = $tr->find('td.message', 0);

			$item['title'] = $message->find('span.message-wrapper', 0)->plaintext;
			$item['uri'] = $tr->find('td.sha', 0)->find('a', 0)->href;
			$item['author'] = $tr->find('td.author', 0)->plaintext;
			$item['timestamp'] = $tr->find('td', 3)->find('span', 0)->title;

			if ($message->find('pre.commit-body', 0)) {
				$message->find('pre.commit-body', 0)->style = '';

				$item['content'] = $message->find('pre.commit-body', 0);
			} else {
				$item['content'] = '<blockquote>' . $item['title'] . '</blockquote>';
			}

			$this->items[] = $item;
		}
	}

	private function extractIssues($html) {
		$div = $html->find('div.repository', 0);

		foreach ($div->find('li.item') as $li) {
			$item = array();

			$number = $li->find('div', 0)->plaintext;

			$item['title'] = $li->find('a.title', 0)->plaintext . ' (' . $number . ')';
			$item['uri'] = $li->find('a.title', 0)->href;
			$item['timestamp'] = $li->find('p.desc', 0)->find('span', 0)->title;
			$item['author'] = $li->find('p.desc', 0)->find('a', 0)->plaintext;

			// Fetch issue page
			$issuePage = getSimpleHTMLDOMCached($item['uri'], 3600)
				or returnServerError('Could not request: ' . $item['uri']);

			$issuePage = defaultLinkTo($issuePage, self::URI);

			$item['content'] = $issuePage->find('ui.timeline', 0)->find('div.render-content.markdown', 0);

			foreach ($li->find('a.ui.label') as $label) {
				$item['categories'][] = $label->plaintext;
			}

			$this->items[] = $item;
		}
	}

	private function extractIssueComments($html) {
		$this->issueTitle = $html->find('span#issue-title', 0)->plaintext
			. ' (' . $html->find('span.index', 0)->plaintext . ')';

		foreach ($html->find('ui.timeline > div.timeline-item.comment') as $div) {
			$item = array();

			if ($div->class === 'timeline-item comment merge box') {
				continue;
			}

			$item['title'] = $this->ellipsisTitle($div->find('div.render-content.markdown', 0)->plaintext);
			$item['uri'] = $div->find('span.text.grey', 0)->find('a', 1)->href;
			$item['content'] = $div->find('div.render-content.markdown', 0);

			if ($div->find('div.dropzone-attachments', 0)) {
				$item['content'] .= $div->find('div.dropzone-attachments', 0);
			}

			$item['author'] = $div->find('a.author', 0)->innertext;
			$item['timestamp'] = $div->find('span.time-since', 0)->title;

			$this->items[] = $item;
		}
	}

	private function extractPulls($html) {
		$div = $html->find('div.repository', 0);

		foreach ($div->find('li.item') as $li) {
			$item = array();

			$number = $li->find('div', 0)->plaintext;

			$item['title'] = $li->find('a.title', 0)->plaintext . ' (' . $number . ')';
			$item['uri'] = $li->find('a.title', 0)->href;
			$item['timestamp'] = $li->find('p.desc', 0)->find('span', 0)->title;
			$item['author'] = $li->find('p.desc', 0)->find('a', 0)->plaintext;

			// Fetch pull request page
			$pullRequestPage = getSimpleHTMLDOMCached($item['uri'], 3600)
				or returnServerError('Could not request: ' . $item['uri']);

			$pullRequestPage = defaultLinkTo($pullRequestPage, self::URI);

			$item['content'] = $pullRequestPage->find('ui.timeline', 0)->find('div.render-content.markdown', 0);

			foreach ($li->find('a.ui.label') as $label) {
				$item['categories'][] = $label->plaintext;
			}

			$this->items[] = $item;
		}
	}

	private function extractReleases($html) {
		$ul = $html->find('ul#release-list', 0);

		foreach ($ul->find('li.ui.grid') as $li) {
			$item = array();

			if ($li->find('h3', 0)) { // Release
				$item['title'] = $li->find('h3', 0)->plaintext;
				$item['uri'] = $li->find('h3', 0)->find('a', 0)->href;

				$tag = $li->find('span.tag', 0)->find('a', 0);
				$commit = $li->find('span.commit', 0);
				$downloads = $this->extractDownloads($li->find('div.download', 0));

				$item['content'] = $li->find('div.markdown', 0);
				$item['content'] .= <<<HTML
<strong>Tag</strong>
<p>{$tag}</p>
<strong>Commit</strong>
<p>{$commit}</p>
{$downloads}
HTML;
				$item['timestamp'] = $li->find('span.time', 0)->find('span', 0)->title;
				$item['author'] = $li->find('span.author', 0)->find('a', 0)->plaintext;
			}

			if ($li->find('h4', 0)) { // Tag
				$item['title'] = $li->find('h4', 0)->plaintext;
				$item['uri'] = $li->find('h4', 0)->find('a', 0)->href;

				$item['content'] = <<<HTML
<strong>Commit</strong>
<p>{$li->find('div.download', 0)->find('a', 0)}</p>
HTML;

				$item['content'] .= $this->extractDownloads($li->find('div.download', 0), true);
				$item['timestamp'] = $li->find('span.time', 0)->find('span', 0)->title;
			}

			$this->items[] = $item;
		}
	}

	private function extractDownloads($html, $skipFirst = false) {
		$downloads = '';

		foreach ($html->find('a') as $index => $a) {
			if ($skipFirst === true && $index === 0) {
				continue;
			}

			$downloads .= <<<HTML
{$a}<br>
HTML;
		}

		return <<<EOD
<strong>Downloads</strong>
<p>{$downloads}</p>
EOD;
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
