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
 * Implements functions specific to Google Analytics
 *
 * @link	https://analytics.google.com/analytics/web/ Google Analytics
 */
class GoogleAnalytics {
	/**
	 * Checks whether the provided ID is a valid Google Analytics ID in the format
	 * UA-000000000-0 (number of digits can vary).
	 *
	 * @return bool True if valid otherwise false.
	 */
	private static function isValidId($id) {
		return (bool) preg_match('/^UA\-\d{4,10}(\-\d{1,4})?$/i', $id);
	}

	/**
	 * Builds a global site tag.
	 *
	 * @link https://developers.google.com/analytics/devguides/collection/gtagjs gtag.js
	 *
	 * @return string The global site tag or null if the ID is not set.
	 */
	public static function buildGlobalSiteTag() {

		$id = Configuration::getConfig('Google Analytics', 'id');

		if (!self::isValidId($id)) return null;

		return <<<EOD
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){
	dataLayer.push(arguments);
}
gtag('js', new Date());
gtag('config', '$id');
</script>
EOD;

	}
}
