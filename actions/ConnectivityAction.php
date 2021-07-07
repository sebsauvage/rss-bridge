<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

/**
 * Checks if the website for a given bridge is reachable.
 *
 * **Remarks**
 * - This action is only available in debug mode.
 * - Returns the bridge status as Json-formatted string.
 * - Returns an error if the bridge is not whitelisted.
 * - Returns a responsive web page that automatically checks all whitelisted
 * bridges (using JavaScript) if no bridge is specified.
 */
class ConnectivityAction extends ActionAbstract {
	public function execute() {

		if(!Debug::isEnabled()) {
			returnError('This action is only available in debug mode!');
		}

		if(!isset($this->userData['bridge'])) {
			$this->returnEntryPage();
			return;
		}

		$bridgeName = $this->userData['bridge'];

		$this->reportBridgeConnectivity($bridgeName);

	}

	/**
	 * Generates a report about the bridge connectivity status and sends it back
	 * to the user.
	 *
	 * The report is generated as Json-formatted string in the format
	 * {
	 *   "bridge": "<bridge-name>",
	 *   "successful": true/false
	 * }
	 *
	 * @param string $bridgeName Name of the bridge to generate the report for
	 * @return void
	 */
	private function reportBridgeConnectivity($bridgeName) {

		$bridgeFac = new \BridgeFactory();
		$bridgeFac->setWorkingDir(PATH_LIB_BRIDGES);

		if(!$bridgeFac->isWhitelisted($bridgeName)) {
			header('Content-Type: text/html');
			returnServerError('Bridge is not whitelisted!');
		}

		header('Content-Type: text/json');

		$retVal = array(
			'bridge' => $bridgeName,
			'successful' => false,
			'http_code' => 200,
		);

		$bridge = $bridgeFac->create($bridgeName);

		if($bridge === false) {
			echo json_encode($retVal);
			return;
		}

		$curl_opts = array(
			CURLOPT_CONNECTTIMEOUT => 5
		);

		try {
			$reply = getContents($bridge::URI, array(), $curl_opts, true);

			if($reply) {
				$retVal['successful'] = true;
				if (isset($reply['header'])) {
					if (strpos($reply['header'], 'HTTP/1.1 301 Moved Permanently') !== false) {
						$retVal['http_code'] = 301;
					}
				}
			}
		} catch(Exception $e) {
			$retVal['successful'] = false;
		}

		echo json_encode($retVal);

	}

	private function returnEntryPage() {
		$google_analytics = GoogleAnalytics::buildGlobalSiteTag();
		echo <<<EOD
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" href="static/bootstrap.min.css">
		<link
			rel="stylesheet"
			href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
			integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/"
			crossorigin="anonymous">
		<link rel="stylesheet" href="static/connectivity.css">
		<script src="static/connectivity.js" type="text/javascript"></script>
		{$google_analytics}
	</head>
	<body>
		<div id="main-content" class="container">
			<div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<div id="status-message" class="sticky-top alert alert-primary alert-dismissible fade show" role="alert">
				<i id="status-icon" class="fas fa-sync"></i>
				<span>...</span>
				<button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="stopConnectivityChecks()">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<input type="text" class="form-control" id="search" onkeyup="search()" placeholder="Search for bridge..">
		</div>
	</body>
</html>
EOD;
	}
}
