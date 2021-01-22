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
		'Pull Requests' => array(),
		'Releases' => array(),
		'global' => array(
			'repo' => array(
				'name' => 'Repository',
				'type' => 'text',
				'exampleValue' => 'username/repo',
				'title' => 'username and repository from URL. codeberg.org/username/repo e.g: codeberg.org/Codeberg/Design',
				'required' => true,
			)
		)
	);

	const CACHE_TIMEOUT = 3600;

	private $feedName = '';
	private $defaultBranch = 'main';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request: ' . $this->getURI());

		$html = defaultLinkTo($html, self::URI);

		switch($this->queriedContext) {
			case 'Commits':
				$this->extractCommits($html);
				break;
			case 'Issues':
				$this->extractIssues($html);
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
					return $this->getInput('repo') . ' Commits';
				}

				return $this->getInput('repo') . ' Commits (' . $this->getBranch() . ' branch) - ' . self::NAME;
			case 'Issues':
				return $this->getInput('repo') . ' Issues - ' . self::NAME;
			case 'Pull Requests':
				return $this->getInput('repo') . ' Pull Requests - ' . self::NAME;
			case 'Releases':
				return $this->getInput('repo') . ' Releases - ' . self::NAME;
			default: return parent::getName();
		}
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'Commits':
				return self::URI . $this->getInput('repo') . '/commits/branch/' . $this->getBranch();
			case 'Issues':
				return self::URI . $this->getInput('repo') . '/issues';
			case 'Pull Requests':
				return self::URI . $this->getInput('repo') . '/pulls';
			case 'Releases':
				return self::URI . $this->getInput('repo') . '/releases';
			default: return parent::getURI();
		}
	}

	private function getBranch() {
		if ($this->getInput('branch')) {
			return $this->getInput('branch');
		}

		return $this->defaultBranch;
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

			foreach ($li->find('a.ui.label') as $label) {
				$item['categories'][] = $label->plaintext;
			}

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

			foreach ($li->find('a.ui.label') as $label) {
				$item['categories'][] = $label->plaintext;
			}

			$this->items[] = $item;
		}
	}

	private function extractReleases($html) {
		$ul = $html->find('ul#release-list', 0);

		foreach ($ul->find('li') as $li) {
			$item = array();

			if (!$li->find('h3', 0)) {
				continue;
			}

			$item['title'] = $li->find('h3', 0)->plaintext;
			$item['uri'] = $li->find('h3', 0)->find('a', 0)->href;
			$item['content'] = $li->find('div.markdown', 0);
			$item['timestamp'] = $li->find('span.time', 0)->find('span', 0)->title;
			$item['author'] = $li->find('span.author', 0)->find('a', 0)->plaintext;

			$this->items[] = $item;
		}
	}
}
