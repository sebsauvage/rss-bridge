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
 * A generator class for the home page of RSS-Bridge.
 *
 * This class generates the HTML content for displaying all bridges on the home
 * page of RSS-Bridge.
 *
 * @todo Return error if a caller creates an object of this class.
 */
final class BridgeList {
	/**
	 * Get the document head
	 *
	 * @return string The document head
	 */
	private static function getHead() {

		$google_analytics = GoogleAnalytics::buildGlobalSiteTag();

		return <<<EOD
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="RSS-Bridge" />
	<title>RSS-Bridge</title>
	<link href="static/style.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="static/favicon.png">
	<script src="static/search.js"></script>
	<script src="static/select.js"></script>
	<noscript>
		<style>
			.searchbar {
				display: none;
			}
		</style>
	</noscript>
	{$google_analytics}
</head>
EOD;
	}

	/**
	 * Get the document body for all bridge cards
	 *
	 * @param bool $showInactive Inactive bridges are visible on the home page if
	 * enabled.
	 * @param int $totalBridges (ref) Returns the total number of bridges.
	 * @param int $totalActiveBridges (ref) Returns the number of active bridges.
	 * @return string The document body for all bridge cards.
	 */
	private static function getBridges($showInactive, &$totalBridges, &$totalActiveBridges) {

		$body = '';
		$totalActiveBridges = 0;
		$inactiveBridges = '';

		$bridgeFac = new \BridgeFactory();
		$bridgeFac->setWorkingDir(PATH_LIB_BRIDGES);
		$bridgeList = $bridgeFac->getBridgeNames();

		$formatFac = new FormatFactory();
		$formatFac->setWorkingDir(PATH_LIB_FORMATS);
		$formats = $formatFac->getFormatNames();

		$totalBridges = count($bridgeList);

		foreach($bridgeList as $bridgeName) {

			if($bridgeFac->isWhitelisted($bridgeName)) {

				$body .= BridgeCard::displayBridgeCard($bridgeName, $formats);
				$totalActiveBridges++;

			} elseif($showInactive) {

				// inactive bridges
				$inactiveBridges .= BridgeCard::displayBridgeCard($bridgeName, $formats, false) . PHP_EOL;

			}

		}

		$body .= $inactiveBridges;

		return $body;
	}

	/**
	 * Get the document header
	 *
	 * @return string The document header
	 */
	private static function getHeader() {
		$warning = '';

		if(Debug::isEnabled()) {
			if(!Debug::isSecure()) {
				$warning .= <<<EOD
<section class="critical-warning">Warning : Debug mode is active from any location,
 make sure only you can access RSS-Bridge.</section>
EOD;
			} else {
				$warning .= <<<EOD
<section class="warning">Warning : Debug mode is active from your IP address,
 your requests will bypass the cache.</section>
EOD;
			}
		}

		return <<<EOD
<header>
	<div class="logo"></div>
	{$warning}
</header>
EOD;
	}

	/**
	 * Get the searchbar
	 *
	 * @return string The searchbar
	 */
	private static function getSearchbar() {
		$query = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);

		return <<<EOD
<section class="searchbar">
	<h3>Search</h3>
	<input type="text" name="searchfield"
		id="searchfield" placeholder="Insert URL or bridge name"
		onchange="search()" onkeyup="search()" value="{$query}">
</section>
EOD;
	}

	/**
	 * Get the document footer
	 *
	 * @param int $totalBridges The total number of bridges, shown in the footer
	 * @param int $totalActiveBridges The total number of active bridges, shown
	 * in the footer.
	 * @param bool $showInactive Sets the 'Show active'/'Show inactive' text in
	 * the footer.
	 * @return string The document footer
	 */
	private static function getFooter($totalBridges, $totalActiveBridges, $showInactive) {
		$version = Configuration::getVersion();

		$email = Configuration::getConfig('admin', 'email');
		$admininfo = '';
		if (!empty($email)) {
			$admininfo = <<<EOD
<br />
<span>
   You may email the administrator of this RSS-Bridge instance
   at <a href="mailto:{$email}">{$email}</a>
</span>
EOD;
		}

		$inactive = '';

		if($totalActiveBridges !== $totalBridges) {

			if(!$showInactive) {
				$inactive = '<a href="?show_inactive=1"><button class="small">Show inactive bridges</button></a><br>';
			} else {
				$inactive = '<a href="?show_inactive=0"><button class="small">Hide inactive bridges</button></a><br>';
			}

		}

		return <<<EOD
<section class="footer">
	<a href="https://github.com/rss-bridge/rss-bridge">RSS-Bridge ~ Public Domain</a><br>
	<p class="version">{$version}</p>
	{$totalActiveBridges}/{$totalBridges} active bridges.<br>
	{$inactive}
	{$admininfo}
</section>
EOD;
	}

	/**
	 * Create the entire home page
	 *
	 * @param bool $showInactive Inactive bridges are displayed on the home page,
	 * if enabled.
	 * @return string The home page
	 */
	static function create($showInactive = true) {

		$totalBridges = 0;
		$totalActiveBridges = 0;

		return '<!DOCTYPE html><html lang="en">'
		. BridgeList::getHead()
		. '<body onload="search()">'
		. BridgeList::getHeader()
		. BridgeList::getSearchbar()
		. BridgeList::getBridges($showInactive, $totalBridges, $totalActiveBridges)
		. BridgeList::getFooter($totalBridges, $totalActiveBridges, $showInactive)
		. '</body></html>';

	}
}
