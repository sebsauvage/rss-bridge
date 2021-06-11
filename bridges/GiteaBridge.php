<?php
/**
 * Gitea is a fork of Gogs which may diverge in the future.
 * https://docs.gitea.io/en-us/
 */
require_once 'GogsBridge.php';

class GiteaBridge extends GogsBridge {

	const NAME = 'Gitea';
	const URI = 'https://gitea.io';
	const DESCRIPTION = 'Returns the latest issues, commits or releases';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 300; // 5 minutes

	const GITEA_PARAMETERS = array(
		'Tags' => array(),
	);

	public function getParameters() {
		return array_merge(parent::getParameters(), static::GITEA_PARAMETERS);
	}

	public function getURI() {
		switch($this->queriedContext) {
			case 'Tags': {
				return $this->getInput('host')
				. '/' . $this->getInput('user')
				. '/' . $this->getInput('project')
				. '/tags/';
			} break;
			default: return parent::getURI();
		}
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request ' . $this->getURI());
		$html = defaultLinkTo($html, $this->getURI());

		$this->title = $html->find('[property="og:title"]', 0)->content;

		switch($this->queriedContext) {
			case 'Commits': {
				$this->collectCommitsData($html);
			} break;
			case 'Issues': {
				$this->collectIssuesData($html);
			} break;
			case 'Single issue': {
				$this->collectSingleIssueData($html);
			} break;
			case 'Releases': {
				$this->collectReleasesData($html);
			} break;
			case 'Tags': {
				$this->collectTagsData($html);
			} break;
		}
	}

	protected function collectReleasesData($html) {
		$releases = $html->find('#release-list > li')
			or returnServerError('Unable to find releases');

		foreach($releases as $release) {
			$this->items[] = array(
				'uri' => $release->find('h4 a', 0)->href,
				'title' => 'Release ' . $release->find('h4 a', 0)->plaintext,
			);
		}
	}

	protected function collectTagsData($html) {
		$tags = $html->find('table#tags-table > tbody > tr')
			or returnServerError('Unable to find tags');

		foreach($tags as $tag) {
			$this->items[] = array(
				'uri' => $tag->find('a', 0)->href,
				'title' => 'Tag ' . $tag->find('.release-tag-name > a', 0)->plaintext,
			);
		}
	}
}
