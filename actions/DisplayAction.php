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

class DisplayAction extends ActionAbstract {
	private function get_return_code($error) {
		$returnCode = $error->getCode();
		if ($returnCode === 301 || $returnCode === 302) {
			# Don't pass redirect codes to the exterior
			$returnCode = 508;
		}
		return $returnCode;
	}

	public function execute() {
		$bridge = array_key_exists('bridge', $this->userData) ? $this->userData['bridge'] : null;
		$lngErr = 'You must specify a format!';
		$format = array_key_exists('format', $this->userData) ? $this->userData['format'] : returnClientError($lngErr);

		$bridgeFac = new \BridgeFactory();
		$bridgeFac->setWorkingDir(PATH_LIB_BRIDGES);

		// whitelist control
		if(!$bridgeFac->isWhitelisted($bridge)) {
			throw new \Exception('This bridge is not whitelisted', 401);
			die;
		}

		// Data retrieval
		$bridge = $bridgeFac->create($bridge);

		$noproxy = array_key_exists('_noproxy', $this->userData)
			&& filter_var($this->userData['_noproxy'], FILTER_VALIDATE_BOOLEAN);

		if(defined('PROXY_URL') && PROXY_BYBRIDGE && $noproxy) {
			define('NOPROXY', true);
		}

		// Cache timeout
		$cache_timeout = -1;
		if(array_key_exists('_cache_timeout', $this->userData)) {

			if(!CUSTOM_CACHE_TIMEOUT) {
				unset($this->userData['_cache_timeout']);
				$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($this->userData);
				header('Location: ' . $uri, true, 301);
				die();
			}

			$cache_timeout = filter_var($this->userData['_cache_timeout'], FILTER_VALIDATE_INT);

		} else {
			$cache_timeout = $bridge->getCacheTimeout();
		}

		// Remove parameters that don't concern bridges
		$bridge_params = array_diff_key(
			$this->userData,
			array_fill_keys(
				array(
					'action',
					'bridge',
					'format',
					'_noproxy',
					'_cache_timeout',
					'_error_time'
				), '')
		);

		// Remove parameters that don't concern caches
		$cache_params = array_diff_key(
			$this->userData,
			array_fill_keys(
				array(
					'action',
					'format',
					'_noproxy',
					'_cache_timeout',
					'_error_time'
				), '')
		);

		// Initialize cache
		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope('');
		$cache->purgeCache(86400); // 24 hours
		$cache->setKey($cache_params);

		$items = array();
		$infos = array();
		$mtime = $cache->getTime();

		if($mtime !== false
		&& (time() - $cache_timeout < $mtime)
		&& !Debug::isEnabled()) { // Load cached data

			// Send "Not Modified" response if client supports it
			// Implementation based on https://stackoverflow.com/a/10847262
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				$stime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

				if($mtime <= $stime) { // Cached data is older or same
					header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $mtime) . 'GMT', true, 304);
					die();
				}
			}

			$cached = $cache->loadData();

			if(isset($cached['items']) && isset($cached['extraInfos'])) {
				foreach($cached['items'] as $item) {
					$items[] = new \FeedItem($item);
				}

				$infos = $cached['extraInfos'];
			}

		} else { // Collect new data

			try {
				$bridge->setDatas($bridge_params);
				$bridge->loadConfiguration();
				$bridge->collectData();

				$items = $bridge->getItems();

				// Transform "legacy" items to FeedItems if necessary.
				// Remove this code when support for "legacy" items ends!
				if(isset($items[0]) && is_array($items[0])) {
					$feedItems = array();

					foreach($items as $item) {
						$feedItems[] = new \FeedItem($item);
					}

					$items = $feedItems;
				}

				$infos = array(
					'name' => $bridge->getName(),
					'uri'  => $bridge->getURI(),
					'donationUri'  => $bridge->getDonationURI(),
					'icon' => $bridge->getIcon()
				);
			} catch(Error $e) {
				error_log($e);

				if(logBridgeError($bridge::NAME, $e->getCode()) >= Configuration::getConfig('error', 'report_limit')) {
					if(Configuration::getConfig('error', 'output') === 'feed') {
						$item = new \FeedItem();

						// Create "new" error message every 24 hours
						$this->userData['_error_time'] = urlencode((int)(time() / 86400));

						// Error 0 is a special case (i.e. "trying to get property of non-object")
						if($e->getCode() === 0) {
							$item->setTitle(
								'Bridge encountered an unexpected situation! ('
								. $this->userData['_error_time']
								. ')'
							);
						} else {
							$item->setTitle(
								'Bridge returned error '
								. $e->getCode()
								. '! ('
								. $this->userData['_error_time']
								. ')'
							);
						}

						$item->setURI(
							(isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '')
							. '?'
							. http_build_query($this->userData)
						);

						$item->setTimestamp(time());
						$item->setContent(buildBridgeException($e, $bridge));

						$items[] = $item;
					} elseif(Configuration::getConfig('error', 'output') === 'http') {
						header('Content-Type: text/html', true, $this->get_return_code($e));
						die(buildTransformException($e, $bridge));
					}
				}
			} catch(Exception $e) {
				error_log($e);

				if(logBridgeError($bridge::NAME, $e->getCode()) >= Configuration::getConfig('error', 'report_limit')) {
					if(Configuration::getConfig('error', 'output') === 'feed') {
						$item = new \FeedItem();

						// Create "new" error message every 24 hours
						$this->userData['_error_time'] = urlencode((int)(time() / 86400));

						$item->setURI(
							(isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '')
							. '?'
							. http_build_query($this->userData)
						);

						$item->setTitle(
							'Bridge returned error '
							. $e->getCode()
							. '! ('
							. $this->userData['_error_time']
							. ')'
						);
						$item->setTimestamp(time());
						$item->setContent(buildBridgeException($e, $bridge));

						$items[] = $item;
					} elseif(Configuration::getConfig('error', 'output') === 'http') {
						header('Content-Type: text/html', true, $this->get_return_code($e));
						die(buildTransformException($e, $bridge));
					}
				}
			}

			// Store data in cache
			$cache->saveData(array(
				'items' => array_map(function($i){ return $i->toArray(); }, $items),
				'extraInfos' => $infos
			));

		}

		// Data transformation
		try {
			$formatFac = new FormatFactory();
			$formatFac->setWorkingDir(PATH_LIB_FORMATS);
			$format = $formatFac->create($format);
			$format->setItems($items);
			$format->setExtraInfos($infos);
			$format->setLastModified($cache->getTime());
			$format->display();
		} catch(Error $e) {
			error_log($e);
			header('Content-Type: text/html', true, $e->getCode());
			die(buildTransformException($e, $bridge));
		} catch(Exception $e) {
			error_log($e);
			header('Content-Type: text/html', true, $e->getCode());
			die(buildTransformException($e, $bridge));
		}
	}
}
