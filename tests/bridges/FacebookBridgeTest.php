<?php

namespace bridges;

require_once __DIR__ . '/../../lib/rssbridge.php';
require_once __DIR__ . '/../../lib/Configuration.php';
require_once __DIR__ . '/../../lib/Debug.php';
require_once __DIR__ . '/../../vendor/simplehtmldom/simple_html_dom.php';
require_once __DIR__ . '/../../lib/contents.php';
require_once __DIR__ . '/../../lib/ParameterValidator.php';
require_once __DIR__ . '/../../lib/BridgeInterface.php';
require_once __DIR__ . '/../../lib/BridgeAbstract.php';
require_once __DIR__ . '/../../bridges/FacebookBridge.php';

use PHPUnit\Framework\TestCase;

\Configuration::verifyInstallation();
\Configuration::loadConfiguration();

class FacebookBridgeTest extends TestCase
{
	/**
	 * @dataProvider providerBridgeData
	 * @param $bridgeData array Data to pass to bridge
	 * @throws \Exception
	 */
	public function testCollectData($bridgeData)
	{
		$bridge = new \FacebookBridge();
		$bridge->setDatas($bridgeData);
		putenv('HTTP_ACCEPT_LANGUAGE=en-GB,en;q=0.5');
		$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:72.0) Gecko/20100101 Firefox/72.0(rss-bridge/'
			. \Configuration::$VERSION
			. ';+'
			. REPOSITORY
			. ')';
		ini_set(
			'user_agent',
			$userAgent
		);
		$bridge->collectData();

		$items = $bridge->getItems();

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	public function providerBridgeData()
	{
		return array(
			array(array(
				'context' => 'Group',
				'g' => 'legospacebuilds'
			)),
			array(array(
				'context' => 'Group',
				'g' => '743149642484225'
			)),
			array(array(
				'context' => 'Group',
				'g' => '486126991513385'
			)),
			array(array(
				'context' => 'Group',
				'g' => 'sailors.worldwide'
			)),
			array(array(
				'context' => 'User',
				'u' => 'BubbaWatsonGolf',
				'media_type' => 'all',
				'limit' => -1
			)),
			array(array(
				'context' => 'User',
				'u' => 'hipdem'
			)),
			array(array(
				'context' => 'User',
				'u' => 'DonaldTrump'
			)),
			array(array(
				'context' => 'User',
				'u' => 'mglaofficial'
			)
			));
	}

	public function testPrivateGroup()
	{
		try {
			$this->testCollectData(
				array(
					'context' => 'Group',
					'g' => 'cambridgefurs'
				));
			$this->fail('Exception should have been thrown');
		} catch (\Exception $e) {
			$this->assertStringContainsString('This group is not public', $e->getMessage());
		}
	}
}
