<?php
require_once('GithubIssueBridge.php');
class GitHubPullRequestBridge extends GithubIssueBridge {
	const MAINTAINER = 'Yaman Qalieh';
	const NAME = 'GitHub Pull Request';
	const DESCRIPTION = 'Returns the pull request or comments of a pull request of a GitHub project';

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
		'Project Pull Requests' => array(
			'c' => array(
				'name' => 'Show Pull Request Comments',
				'type' => 'checkbox'
			)
		),
		'Pull Request comments' => array(
			'i' => array(
				'name' => 'Pull Request number',
				'type' => 'number',
				'required' => true
			)
		)
	);

	const BRIDGE_OPTIONS = array(0 => 'Project Pull Requests', 1 => 'Pull Request comments');
	const URL_PATH = 'pull';
	const SEARCH_QUERY_PATH = 'pulls';
	const SEARCH_QUERY = '?q=is%3Apr+sort%3Acreated-desc';
}
