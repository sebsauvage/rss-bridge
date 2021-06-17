<?php
namespace d7sd6u\VKPostsExtractorParser;

require_once dirname(__DIR__) . "/Utilities.php";

abstract class GenericExtractor {
	protected $getDoms;
	protected $inheritedLog;
	protected $options;

	protected function getDoms($urls, $context) {
		return ($this->getDoms)($urls, $context);
	}

	protected function getDom($url, $context) {
		return array_values(($this->getDoms)(array($url), $context))[0];
	}

	protected function log($message) {
		if(is_string($message)) {
			$message = array(
				'text' => $message
			);
		}

		($this->inheritedLog)($message);
	}

	public function __construct($getDoms, $log, $options) {
		$this->getDoms = $getDoms;
		$this->inheritedLog = $log;
		$this->options = $options;
	}
}