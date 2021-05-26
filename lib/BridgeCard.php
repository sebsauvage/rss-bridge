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
 * A generator class for a single bridge card on the home page of RSS-Bridge.
 *
 * This class generates the HTML content for a single bridge card for the home
 * page of RSS-Bridge.
 *
 * @todo Return error if a caller creates an object of this class.
 */
final class BridgeCard {
	/**
	 * Get the form header for a bridge card
	 *
	 * @param string $bridgeName The bridge name
	 * @param bool $isHttps If disabled, adds a warning to the form
	 * @return string The form header
	 */
	private static function getFormHeader($bridgeName, $isHttps = false, $parameterName = '') {
		$form = <<<EOD
			<form method="GET" action="?">
				<input type="hidden" name="action" value="display" />
				<input type="hidden" name="bridge" value="{$bridgeName}" />
EOD;

		if(!empty($parameterName)) {
			$form .= <<<EOD
				<input type="hidden" name="context" value="{$parameterName}" />
EOD;
		}

		if(!$isHttps) {
			$form .= '<div class="secure-warning">Warning :
This bridge is not fetching its content through a secure connection</div>';
		}

		return $form;
	}

	/**
	 * Get the form body for a bridge
	 *
	 * @param string $bridgeName The bridge name
	 * @param array $formats A list of supported formats
	 * @param bool $isActive Indicates if a bridge is enabled or not
	 * @param bool $isHttps Indicates if a bridge uses HTTPS or not
	 * @param string $parameterName Sets the bridge context for the current form
	 * @param array $parameters The bridge parameters
	 * @return string The form body
	 */
	private static function getForm($bridgeName,
	$formats,
	$isActive = false,
	$isHttps = false,
	$parameterName = '',
	$parameters = array()) {
		$form = self::getFormHeader($bridgeName, $isHttps, $parameterName);

		if(count($parameters) > 0) {

			$form .= '<div class="parameters">';

			foreach($parameters as $id => $inputEntry) {
				if(!isset($inputEntry['exampleValue']))
					$inputEntry['exampleValue'] = '';

				if(!isset($inputEntry['defaultValue']))
					$inputEntry['defaultValue'] = '';

				$idArg = 'arg-'
					. urlencode($bridgeName)
					. '-'
					. urlencode($parameterName)
					. '-'
					. urlencode($id);

				$form .= '<label for="'
					. $idArg
					. '">'
					. filter_var($inputEntry['name'], FILTER_SANITIZE_STRING)
					. '</label>'
					. PHP_EOL;

				if(!isset($inputEntry['type']) || $inputEntry['type'] === 'text') {
					$form .= self::getTextInput($inputEntry, $idArg, $id);
				} elseif($inputEntry['type'] === 'number') {
					$form .= self::getNumberInput($inputEntry, $idArg, $id);
				} else if($inputEntry['type'] === 'list') {
					$form .= self::getListInput($inputEntry, $idArg, $id);
				} elseif($inputEntry['type'] === 'checkbox') {
					$form .= self::getCheckboxInput($inputEntry, $idArg, $id);
				}

				if(isset($inputEntry['title']))
					$form .= '<i class="info" title="' . filter_var($inputEntry['title'], FILTER_SANITIZE_STRING) . '">i</i>';
				else
					$form .= '<i class="no-info"></i>';
			}

			$form .= '</div>';

		}

		if($isActive) {
			$form .= '<button type="submit" name="format" value="Html">Generate feed</button>';
		} else {
			$form .= '<span style="font-weight: bold;">Inactive</span>';
		}

		return $form . '</form>' . PHP_EOL;
	}

	/**
	 * Get input field attributes
	 *
	 * @param array $entry The current entry
	 * @return string The input field attributes
	 */
	private static function getInputAttributes($entry) {
		$retVal = '';

		if(isset($entry['required']) && $entry['required'] === true)
			$retVal .= ' required';

		if(isset($entry['pattern']))
			$retVal .= ' pattern="' . $entry['pattern'] . '"';

		return $retVal;
	}

	/**
	 * Get text input
	 *
	 * @param array $entry The current entry
	 * @param string $id The field ID
	 * @param string $name The field name
	 * @return string The text input field
	 */
	private static function getTextInput($entry, $id, $name) {
		return '<input '
		. self::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="text" value="'
		. filter_var($entry['defaultValue'], FILTER_SANITIZE_STRING)
		. '" placeholder="'
		. filter_var($entry['exampleValue'], FILTER_SANITIZE_STRING)
		. '" name="'
		. $name
		. '" />'
		. PHP_EOL;
	}

	/**
	 * Get number input
	 *
	 * @param array $entry The current entry
	 * @param string $id The field ID
	 * @param string $name The field name
	 * @return string The number input field
	 */
	private static function getNumberInput($entry, $id, $name) {
		return '<input '
		. self::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="number" value="'
		. filter_var($entry['defaultValue'], FILTER_SANITIZE_NUMBER_INT)
		. '" placeholder="'
		. filter_var($entry['exampleValue'], FILTER_SANITIZE_NUMBER_INT)
		. '" name="'
		. $name
		. '" />'
		. PHP_EOL;
	}

	/**
	 * Get list input
	 *
	 * @param array $entry The current entry
	 * @param string $id The field ID
	 * @param string $name The field name
	 * @return string The list input field
	 */
	private static function getListInput($entry, $id, $name) {
		if(isset($entry['required']) && $entry['required'] === true) {
			Debug::log('The "required" attribute is not supported for lists.');
			unset($entry['required']);
		}

		$list = '<select '
		. self::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" name="'
		. $name
		. '" >';

		foreach($entry['values'] as $name => $value) {
			if(is_array($value)) {
				$list .= '<optgroup label="' . htmlentities($name) . '">';
				foreach($value as $subname => $subvalue) {
					if($entry['defaultValue'] === $subname
						|| $entry['defaultValue'] === $subvalue) {
						$list .= '<option value="'
							. $subvalue
							. '" selected>'
							. $subname
							. '</option>';
					} else {
						$list .= '<option value="'
							. $subvalue
							. '">'
							. $subname
							. '</option>';
					}
				}
				$list .= '</optgroup>';
			} else {
				if($entry['defaultValue'] === $name
					|| $entry['defaultValue'] === $value) {
					$list .= '<option value="'
						. $value
						. '" selected>'
						. $name
						. '</option>';
				} else {
					$list .= '<option value="'
						. $value
						. '">'
						. $name
						. '</option>';
				}
			}
		}

		$list .= '</select>';

		return $list;
	}

	/**
	 * Get checkbox input
	 *
	 * @param array $entry The current entry
	 * @param string $id The field ID
	 * @param string $name The field name
	 * @return string The checkbox input field
	 */
	private static function getCheckboxInput($entry, $id, $name) {
		if(isset($entry['required']) && $entry['required'] === true) {
			Debug::log('The "required" attribute is not supported for checkboxes.');
			unset($entry['required']);
		}

		return '<input '
		. self::getInputAttributes($entry)
		. ' id="'
		. $id
		. '" type="checkbox" name="'
		. $name
		. '" '
		. ($entry['defaultValue'] === 'checked' ? 'checked' : '')
		. ' />'
		. PHP_EOL;
	}

	/**
	 * Gets a single bridge card
	 *
	 * @param string $bridgeName The bridge name
	 * @param array $formats A list of formats
	 * @param bool $isActive Indicates if the bridge is active or not
	 * @return string The bridge card
	 */
	static function displayBridgeCard($bridgeName, $formats, $isActive = true){

		$bridgeFac = new \BridgeFactory();
		$bridgeFac->setWorkingDir(PATH_LIB_BRIDGES);

		$bridge = $bridgeFac->create($bridgeName);

		if($bridge == false)
			return '';

		$isHttps = strpos($bridge->getURI(), 'https') === 0;

		$uri = $bridge->getURI();
		$name = $bridge->getName();
		$icon = $bridge->getIcon();
		$description = $bridge->getDescription();
		$parameters = $bridge->getParameters();

		if(defined('PROXY_URL') && PROXY_BYBRIDGE) {
			$parameters['global']['_noproxy'] = array(
				'name' => 'Disable proxy (' . ((defined('PROXY_NAME') && PROXY_NAME) ? PROXY_NAME : PROXY_URL) . ')',
				'type' => 'checkbox'
			);
		}

		if(CUSTOM_CACHE_TIMEOUT) {
			$parameters['global']['_cache_timeout'] = array(
				'name' => 'Cache timeout in seconds',
				'type' => 'number',
				'defaultValue' => $bridge->getCacheTimeout()
			);
		}

		$card = <<<CARD
			<section id="bridge-{$bridgeName}" data-ref="{$name}">
				<h2><a href="{$uri}">{$name}</a></h2>
				<p class="description">{$description}</p>
				<input type="checkbox" class="showmore-box" id="showmore-{$bridgeName}" />
				<label class="showmore" for="showmore-{$bridgeName}">Show more</label>
CARD;

		// If we don't have any parameter for the bridge, we print a generic form to load it.
		if (count($parameters) === 0) {
			$card .= self::getForm($bridgeName, $formats, $isActive, $isHttps);

		// Display form with cache timeout and/or noproxy options (if enabled) when bridge has no parameters
		} else if (count($parameters) === 1 && array_key_exists('global', $parameters)) {
			$card .= self::getForm($bridgeName, $formats, $isActive, $isHttps, '', $parameters['global']);

		} else {

			foreach($parameters as $parameterName => $parameter) {
				if(!is_numeric($parameterName) && $parameterName === 'global')
					continue;

				if(array_key_exists('global', $parameters))
					$parameter = array_merge($parameter, $parameters['global']);

				if(!is_numeric($parameterName))
					$card .= '<h5>' . $parameterName . '</h5>' . PHP_EOL;

				$card .= self::getForm($bridgeName, $formats, $isActive, $isHttps, $parameterName, $parameter);
			}

		}

		$card .= '<label class="showless" for="showmore-' . $bridgeName . '">Show less</label>';
		$card .= '<p class="maintainer">' . $bridge->getMaintainer() . '</p>';
		$card .= '</section>';

		return $card;
	}
}
