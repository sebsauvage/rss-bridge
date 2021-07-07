<?php

class SummitsOnTheAirBridge extends BridgeAbstract {
	const MAINTAINER = 's0lesurviv0r';
	const NAME = 'Summits On The Air Spots';
	const URI = 'https://api2.sota.org.uk/api/spots/';
	const CACHE_TIMEOUT = 60; // 1m
	const DESCRIPTION = 'Summits On The Air Activator Spots';

	const PARAMETERS = array(
		'Count' => array(
			'c' => array(
				'name' => 'count',
				'required' => true,
				'defaultValue' => 10
			)
		)
	);

	public function collectData()
	{
		$header = array('Content-type:application/json');
		$opts = array(CURLOPT_HTTPGET => 1);
		$json = getContents($this->getURI() . $this->getInput('c'), $header, $opts);

		$spots = json_decode($json, true);

		foreach ($spots as $spot) {
			$summit = $spot['associationCode'] . '/' . $spot['summitCode'];

			$title = $spot['activatorCallsign'] . ' @ ' . $summit . ' ' .
				$spot['frequency'] . ' MHz';

			$content = <<<EOL
			<a href="http://summits.sota.org.uk/summit/{$summit}">
			{$summit}, {$spot['summitDetails']}</a><br />
			Frequency: {$spot['frequency']} MHz<br />
			Mode: {$spot['mode']}<br />
			Comments: {$spot['comments']}
			EOL;

			$this->items[] = array(
				'uri' => 'https://sotawatch.sota.org.uk/en/',
				'title' => $title,
				'content' => $content,
				'timestamp' => $spot['timeStamp']
			);
		}
	}
}
