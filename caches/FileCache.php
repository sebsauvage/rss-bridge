<?php
/**
* Cache with file system
*/
class FileCache implements CacheInterface {
	protected $path;
	protected $key;

	public function __construct() {
		if (!is_writable(PATH_CACHE)) {
			returnServerError(
				'RSS-Bridge does not have write permissions for '
				. PATH_CACHE . '!'
			);
		}
	}

	public function loadData(){
		if(file_exists($this->getCacheFile())) {
			return unserialize(file_get_contents($this->getCacheFile()));
		}

		return null;
	}

	public function saveData($data){
		// Notice: We use plain serialize() here to reduce memory footprint on
		// large input data.
		$writeStream = file_put_contents($this->getCacheFile(), serialize($data));

		if($writeStream === false) {
			throw new \Exception('Cannot write the cache... Do you have the right permissions ?');
		}

		return $this;
	}

	public function getTime(){
		$cacheFile = $this->getCacheFile();
		clearstatcache(false, $cacheFile);
		if(file_exists($cacheFile)) {
			$time = filemtime($cacheFile);
			return ($time !== false) ? $time : null;
		}

		return null;
	}

	public function purgeCache($seconds){
		$cachePath = $this->getPath();
		if(file_exists($cachePath)) {
			$cacheIterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($cachePath),
			RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach($cacheIterator as $cacheFile) {
				if(in_array($cacheFile->getBasename(), array('.', '..', '.gitkeep')))
					continue;
				elseif($cacheFile->isFile()) {
					if(filemtime($cacheFile->getPathname()) < time() - $seconds)
						unlink($cacheFile->getPathname());
				}
			}
		}
	}

	/**
	* Set scope
	* @return self
	*/
	public function setScope($scope){
		if(is_null($scope) || !is_string($scope)) {
			throw new \Exception('The given scope is invalid!');
		}

		$this->path = PATH_CACHE . trim($scope, " \t\n\r\0\x0B\\\/") . '/';

		return $this;
	}

	/**
	* Set key
	* @return self
	*/
	public function setKey($key){
		if (!empty($key) && is_array($key)) {
			$key = array_map('strtolower', $key);
		}
		$key = json_encode($key);

		if (!is_string($key)) {
			throw new \Exception('The given key is invalid!');
		}

		$this->key = $key;
		return $this;
	}

	/**
	* Return cache path (and create if not exist)
	* @return string Cache path
	*/
	private function getPath(){
		if(is_null($this->path)) {
			throw new \Exception('Call "setScope" first!');
		}

		if(!is_dir($this->path)) {
			if (mkdir($this->path, 0755, true) !== true) {
				throw new \Exception('Unable to create ' . $this->path);
			}
		}

		return $this->path;
	}

	/**
	* Get the file name use for cache store
	* @return string Path to the file cache
	*/
	private function getCacheFile(){
		return $this->getPath() . $this->getCacheName();
	}

	/**
	* Determines file name for store the cache
	* return string
	*/
	private function getCacheName(){
		if(is_null($this->key)) {
			throw new \Exception('Call "setKey" first!');
		}

		return hash('md5', $this->key) . '.cache';
	}
}
