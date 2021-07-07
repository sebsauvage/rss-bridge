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
 * An abstract class for bridges
 *
 * This class implements {@see BridgeInterface} with most common functions in
 * order to reduce code duplication. Bridges should inherit from this class
 * instead of implementing the interface manually.
 *
 * @todo Move constants to the interface (this is supported by PHP)
 * @todo Change visibility of constants to protected
 * @todo Return `self` on more functions to allow chaining
 * @todo Add specification for PARAMETERS ()
 * @todo Add specification for $items
 */
abstract class BridgeAbstract implements BridgeInterface {

	/**
	 * Name of the bridge
	 *
	 * Use {@see BridgeAbstract::getName()} to read this parameter
	 */
	const NAME = 'Unnamed bridge';

	/**
	 * URI to the site the bridge is intended to be used for.
	 *
	 * Use {@see BridgeAbstract::getURI()} to read this parameter
	 */
	const URI = '';

	/**
	 * Donation URI to the site the bridge is intended to be used for.
	 *
	 * Use {@see BridgeAbstract::getDonationURI()} to read this parameter
	 */
	const DONATION_URI = '';

	/**
	 * A brief description of what the bridge can do
	 *
	 * Use {@see BridgeAbstract::getDescription()} to read this parameter
	 */
	const DESCRIPTION = 'No description provided';

	/**
	 * The name of the maintainer. Multiple maintainers can be separated by comma
	 *
	 * Use {@see BridgeAbstract::getMaintainer()} to read this parameter
	 */
	const MAINTAINER = 'No maintainer';

	/**
	 * The default cache timeout for the bridge
	 *
	 * Use {@see BridgeAbstract::getCacheTimeout()} to read this parameter
	 */
	const CACHE_TIMEOUT = 3600;

	/**
	 * Configuration for the bridge
	 *
	 * Use {@see BridgeAbstract::getConfiguration()} to read this parameter
	 */
	const CONFIGURATION = array();

	/**
	 * Parameters for the bridge
	 *
	 * Use {@see BridgeAbstract::getParameters()} to read this parameter
	 */
	const PARAMETERS = array();

	/**
	 * Test cases for detectParameters for the bridge
	 */
	const TEST_DETECT_PARAMETERS = array();

	/**
	 * Holds the list of items collected by the bridge
	 *
	 * Items must be collected by {@see BridgeInterface::collectData()}
	 *
	 * Use {@see BridgeAbstract::getItems()} to access items.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Holds the list of input parameters used by the bridge
	 *
	 * Do not access this parameter directly!
	 * Use {@see BridgeAbstract::setInputs()} and {@see BridgeAbstract::getInput()} instead!
	 *
	 * @var array
	 */
	protected $inputs = array();

	/**
	 * Holds the name of the queried context
	 *
	 * @var string
	 */
	protected $queriedContext = '';

	/** {@inheritdoc} */
	public function getItems(){
		return $this->items;
	}

	/**
	 * Sets the input values for a given context.
	 *
	 * @param array $inputs Associative array of inputs
	 * @param string $queriedContext The context name
	 * @return void
	 */
	protected function setInputs(array $inputs, $queriedContext){
		// Import and assign all inputs to their context
		foreach($inputs as $name => $value) {
			foreach(static::PARAMETERS as $context => $set) {
				if(array_key_exists($name, static::PARAMETERS[$context])) {
					$this->inputs[$context][$name]['value'] = $value;
				}
			}
		}

		// Apply default values to missing data
		$contexts = array($queriedContext);
		if(array_key_exists('global', static::PARAMETERS)) {
			$contexts[] = 'global';
		}

		foreach($contexts as $context) {
			foreach(static::PARAMETERS[$context] as $name => $properties) {
				if(isset($this->inputs[$context][$name]['value'])) {
					continue;
				}

				$type = isset($properties['type']) ? $properties['type'] : 'text';

				switch($type) {
				case 'checkbox':
					if(!isset($properties['defaultValue'])) {
						$this->inputs[$context][$name]['value'] = false;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				case 'list':
					if(!isset($properties['defaultValue'])) {
						$firstItem = reset($properties['values']);
						if(is_array($firstItem)) {
							$firstItem = reset($firstItem);
						}
						$this->inputs[$context][$name]['value'] = $firstItem;
					} else {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				default:
					if(isset($properties['defaultValue'])) {
						$this->inputs[$context][$name]['value'] = $properties['defaultValue'];
					}
					break;
				}
			}
		}

		// Copy global parameter values to the guessed context
		if(array_key_exists('global', static::PARAMETERS)) {
			foreach(static::PARAMETERS['global'] as $name => $properties) {
				if(isset($inputs[$name])) {
					$value = $inputs[$name];
				} elseif(isset($properties['defaultValue'])) {
					$value = $properties['defaultValue'];
				} else {
					continue;
				}
				$this->inputs[$queriedContext][$name]['value'] = $value;
			}
		}

		// Only keep guessed context parameters values
		if(isset($this->inputs[$queriedContext])) {
			$this->inputs = array($queriedContext => $this->inputs[$queriedContext]);
		} else {
			$this->inputs = array();
		}
	}

	/**
	 * Set inputs for the bridge
	 *
	 * Returns errors and aborts execution if the provided input parameters are
	 * invalid.
	 *
	 * @param array List of input parameters. Each element in this list must
	 * relate to an item in {@see BridgeAbstract::PARAMETERS}
	 * @return void
	 */
	public function setDatas(array $inputs){

		if(isset($inputs['context'])) { // Context hinting (optional)
			$this->queriedContext = $inputs['context'];
			unset($inputs['context']);
		}

		if(empty(static::PARAMETERS)) {

			if(!empty($inputs)) {
				returnClientError('Invalid parameters value(s)');
			}

			return;

		}

		$validator = new ParameterValidator();

		if(!$validator->validateData($inputs, static::PARAMETERS)) {
			$parameters = array_map(
				function($i){ return $i['name']; }, // Just display parameter names
				$validator->getInvalidParameters()
			);

			returnClientError(
				'Invalid parameters value(s): '
				. implode(', ', $parameters)
			);
		}

		// Guess the context from input data
		if(empty($this->queriedContext)) {
			$this->queriedContext = $validator->getQueriedContext($inputs, static::PARAMETERS);
		}

		if(is_null($this->queriedContext)) {
			returnClientError('Required parameter(s) missing');
		} elseif($this->queriedContext === false) {
			returnClientError('Mixed context parameters');
		}

		$this->setInputs($inputs, $this->queriedContext);

	}

	/**
	 * Loads configuration for the bridge
	 *
	 * Returns errors and aborts execution if the provided configuration is
	 * invalid.
	 *
	 * @return void
	 */
	public function loadConfiguration() {
		foreach(static::CONFIGURATION as $optionName => $optionValue) {

			$configurationOption = Configuration::getConfig(get_class($this), $optionName);

			if($configurationOption !== null) {
				$this->configuration[$optionName] = $configurationOption;
				continue;
			}

			if(isset($optionValue['required']) && $optionValue['required'] === true) {
				returnServerError(
					'Missing configuration option: '
					. $optionName
				);
			} elseif(isset($optionValue['defaultValue'])) {
				$this->configuration[$optionName] = $optionValue['defaultValue'];
			}

		}
	}

	/**
	 * Returns the value for the provided input
	 *
	 * @param string $input The input name
	 * @return mixed|null The input value or null if the input is not defined
	 */
	protected function getInput($input){
		if(!isset($this->inputs[$this->queriedContext][$input]['value'])) {
			return null;
		}
		return $this->inputs[$this->queriedContext][$input]['value'];
	}

	/**
	 * Returns the value for the selected configuration
	 *
	 * @param string $input The option name
	 * @return mixed|null The option value or null if the input is not defined
	 */
	public function getOption($name){
		if(!isset($this->configuration[$name])) {
			return null;
		}
		return $this->configuration[$name];
	}

	/** {@inheritdoc} */
	public function getDescription(){
		return static::DESCRIPTION;
	}

	/** {@inheritdoc} */
	public function getMaintainer(){
		return static::MAINTAINER;
	}

	/** {@inheritdoc} */
	public function getName(){
		return static::NAME;
	}

	/** {@inheritdoc} */
	public function getIcon(){
		return static::URI . '/favicon.ico';
	}

	/** {@inheritdoc} */
	public function getConfiguration(){
		return static::CONFIGURATION;
	}

	/** {@inheritdoc} */
	public function getParameters(){
		return static::PARAMETERS;
	}

	/** {@inheritdoc} */
	public function getURI(){
		return static::URI;
	}

	/** {@inheritdoc} */
	public function getDonationURI(){
		return static::DONATION_URI;
	}

	/** {@inheritdoc} */
	public function getCacheTimeout(){
		return static::CACHE_TIMEOUT;
	}

	/** {@inheritdoc} */
	public function detectParameters($url){
		$regex = '/^(https?:\/\/)?(www\.)?(.+?)(\/)?$/';
		if(empty(static::PARAMETERS)
		&& preg_match($regex, $url, $urlMatches) > 0
		&& preg_match($regex, static::URI, $bridgeUriMatches) > 0
		&& $urlMatches[3] === $bridgeUriMatches[3]) {
			return array();
		} else {
			return null;
		}
	}

	/**
	 * Loads a cached value for the specified key
	 *
	 * @param string $key Key name
	 * @param int $duration Cache duration (optional, default: 24 hours)
	 * @return mixed Cached value or null if the key doesn't exist or has expired
	 */
	protected function loadCacheValue($key, $duration = 86400){
		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey($key);
		if($cache->getTime() < time() - $duration)
			return null;
		return $cache->loadData();
	}

	/**
	 * Stores a value to cache with the specified key
	 *
	 * @param string $key Key name
	 * @param mixed $value Value to cache
	 */
	protected function saveCacheValue($key, $value){
		$cacheFac = new CacheFactory();
		$cacheFac->setWorkingDir(PATH_LIB_CACHES);
		$cache = $cacheFac->create(Configuration::getConfig('cache', 'type'));
		$cache->setScope(get_called_class());
		$cache->setKey($key);
		$cache->saveData($value);
	}
}
