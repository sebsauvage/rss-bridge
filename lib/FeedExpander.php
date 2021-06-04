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
 * An abstract class for bridges that need to transform existing RSS or Atom
 * feeds.
 *
 * This class extends {@see BridgeAbstract} with functions to extract contents
 * from existing RSS or Atom feeds. Bridges that need to transform existing feeds
 * should inherit from this class instead of {@see BridgeAbstract}.
 *
 * Bridges that extend this class don't need to concern themselves with getting
 * contents from existing feeds, but can focus on adding additional contents
 * (i.e. by downloading additional data), filtering or just transforming a feed
 * into another format.
 *
 * @link http://www.rssboard.org/rss-0-9-1 RSS 0.91 Specification
 * @link http://web.resource.org/rss/1.0/spec RDF Site Summary (RSS) 1.0
 * @link http://www.rssboard.org/rss-specification RSS 2.0 Specification
 * @link https://tools.ietf.org/html/rfc4287 The Atom Syndication Format
 *
 * @todo The parsing functions should all be private. This class is complicated
 * enough without having to consider children overriding functions.
 */
abstract class FeedExpander extends BridgeAbstract {

	/** Indicates an RSS 1.0 feed */
	const FEED_TYPE_RSS_1_0 = 'RSS_1_0';

	/** Indicates an RSS 2.0 feed */
	const FEED_TYPE_RSS_2_0 = 'RSS_2_0';

	/** Indicates an Atom 1.0 feed */
	const FEED_TYPE_ATOM_1_0 = 'ATOM_1_0';

	/**
	 * Holds the title of the current feed
	 *
	 * @var string
	 */
	private $title;

	/**
	 * Holds the URI of the feed
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Holds the icon of the feed
	 *
	 */
	private $icon;

	/**
	 * Holds the feed type during internal operations.
	 *
	 * @var string
	 */
	private $feedType;

	/**
	 * Collects data from an existing feed.
	 *
	 * Children should call this function in {@see BridgeInterface::collectData()}
	 * to extract a feed.
	 *
	 * @param string $url URL to the feed.
	 * @param int $maxItems Maximum number of items to collect from the feed
	 * (`-1`: no limit).
	 * @return self
	 */
	public function collectExpandableDatas($url, $maxItems = -1){
		if(empty($url)) {
			returnServerError('There is no $url for this RSS expander');
		}

		Debug::log('Loading from ' . $url);

		/* Notice we do not use cache here on purpose:
		 * we want a fresh view of the RSS stream each time
		 */
		$content = getContents($url)
			or returnServerError('Could not request ' . $url);
		$rssContent = simplexml_load_string(trim($content));

		Debug::log('Detecting feed format/version');
		switch(true) {
		case isset($rssContent->item[0]):
			Debug::log('Detected RSS 1.0 format');
			$this->feedType = self::FEED_TYPE_RSS_1_0;
			break;
		case isset($rssContent->channel[0]):
			Debug::log('Detected RSS 0.9x or 2.0 format');
			$this->feedType = self::FEED_TYPE_RSS_2_0;
			break;
		case isset($rssContent->entry[0]):
			Debug::log('Detected ATOM format');
			$this->feedType = self::FEED_TYPE_ATOM_1_0;
			break;
		default:
			Debug::log('Unknown feed format/version');
			returnServerError('The feed format is unknown!');
			break;
		}

		Debug::log('Calling function "collect_' . $this->feedType . '_data"');
		$this->{'collect_' . $this->feedType . '_data'}($rssContent, $maxItems);

		return $this;
	}

	/**
	 * Collect data from a RSS 1.0 compatible feed
	 *
	 * @link http://web.resource.org/rss/1.0/spec RDF Site Summary (RSS) 1.0
	 *
	 * @param string $rssContent The RSS content
	 * @param int $maxItems Maximum number of items to collect from the feed
	 * (`-1`: no limit).
	 * @return void
	 *
	 * @todo Instead of passing $maxItems to all functions, just add all items
	 * and remove excessive items later.
	 */
	protected function collect_RSS_1_0_data($rssContent, $maxItems){
		$this->load_RSS_2_0_feed_data($rssContent->channel[0]);
		foreach($rssContent->item as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	/**
	 * Collect data from a RSS 2.0 compatible feed
	 *
	 * @link http://www.rssboard.org/rss-specification RSS 2.0 Specification
	 *
	 * @param object $rssContent The RSS content
	 * @param int $maxItems Maximum number of items to collect from the feed
	 * (`-1`: no limit).
	 * @return void
	 *
	 * @todo Instead of passing $maxItems to all functions, just add all items
	 * and remove excessive items later.
	 */
	protected function collect_RSS_2_0_data($rssContent, $maxItems){
		$rssContent = $rssContent->channel[0];
		Debug::log('RSS content is ===========\n'
		. var_export($rssContent, true)
		. '===========');

		$this->load_RSS_2_0_feed_data($rssContent);
		foreach($rssContent->item as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	/**
	 * Collect data from a Atom 1.0 compatible feed
	 *
	 * @link https://tools.ietf.org/html/rfc4287  The Atom Syndication Format
	 *
	 * @param object $content The Atom content
	 * @param int $maxItems Maximum number of items to collect from the feed
	 * (`-1`: no limit).
	 * @return void
	 *
	 * @todo Instead of passing $maxItems to all functions, just add all items
	 * and remove excessive items later.
	 */
	protected function collect_ATOM_1_0_data($content, $maxItems){
		$this->load_ATOM_feed_data($content);
		foreach($content->entry as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	/**
	 * Convert RSS 2.0 time to timestamp
	 *
	 * @param object $item A feed item
	 * @return int The timestamp
	 */
	protected function RSS_2_0_time_to_timestamp($item){
		return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
	}

	/**
	 * Load RSS 2.0 feed data into RSS-Bridge
	 *
	 * @param object $rssContent The RSS content
	 * @return void
	 *
	 * @todo set title, link, description, language, and so on
	 */
	protected function load_RSS_2_0_feed_data($rssContent){
		$this->title = trim((string)$rssContent->title);
		$this->uri = trim((string)$rssContent->link);

		if (!empty($rssContent->image)) {
			$this->icon = trim((string)$rssContent->image->url);
		}
	}

	/**
	 * Load Atom feed data into RSS-Bridge
	 *
	 * @param object $content The Atom content
	 * @return void
	 */
	protected function load_ATOM_feed_data($content){
		$this->title = (string)$content->title;

		// Find best link (only one, or first of 'alternate')
		if(!isset($content->link)) {
			$this->uri = '';
		} elseif (count($content->link) === 1) {
			$this->uri = (string)$content->link[0]['href'];
		} else {
			$this->uri = '';
			foreach($content->link as $link) {
				if(strtolower($link['rel']) === 'alternate') {
					$this->uri = (string)$link['href'];
					break;
				}
			}
		}

		if(!empty($content->icon)) {
			$this->icon = (string)$content->icon;
		} elseif(!empty($content->logo)) {
			$this->icon = (string)$content->logo;
		}
	}

	/**
	 * Parse the contents of a single Atom feed item into a RSS-Bridge item for
	 * further transformation.
	 *
	 * @param object $feedItem A single feed item
	 * @return object The RSS-Bridge item
	 *
	 * @todo To reduce confusion, the RSS-Bridge item should maybe have a class
	 * of its own?
	 */
	protected function parseATOMItem($feedItem){
		// Some ATOM entries also contain RSS 2.0 fields
		$item = $this->parseRSS_2_0_Item($feedItem);

		if(isset($feedItem->id)) $item['uri'] = (string)$feedItem->id;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		if(isset($feedItem->updated)) $item['timestamp'] = strtotime((string)$feedItem->updated);
		if(isset($feedItem->author)) $item['author'] = (string)$feedItem->author->name;
		if(isset($feedItem->content)) $item['content'] = (string)$feedItem->content;

		//When "link" field is present, URL is more reliable than "id" field
		if (count($feedItem->link) === 1) {
			$item['uri'] = (string)$feedItem->link[0]['href'];
		} else {
			foreach($feedItem->link as $link) {
				if(strtolower($link['rel']) === 'alternate') {
					$item['uri'] = (string)$link['href'];
				}
				if(strtolower($link['rel']) === 'enclosure') {
					$item['enclosures'][] = (string)$link['href'];
				}
			}
		}

		return $item;
	}

	/**
	 * Parse the contents of a single RSS 0.91 feed item into a RSS-Bridge item
	 * for further transformation.
	 *
	 * @param object $feedItem A single feed item
	 * @return object The RSS-Bridge item
	 *
	 * @todo To reduce confusion, the RSS-Bridge item should maybe have a class
	 * of its own?
	 */
	protected function parseRSS_0_9_1_Item($feedItem){
		$item = array();
		if(isset($feedItem->link)) $item['uri'] = (string)$feedItem->link;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		// rss 0.91 doesn't support timestamps
		// rss 0.91 doesn't support authors
		// rss 0.91 doesn't support enclosures
		if(isset($feedItem->description)) $item['content'] = (string)$feedItem->description;
		return $item;
	}

	/**
	 * Parse the contents of a single RSS 1.0 feed item into a RSS-Bridge item
	 * for further transformation.
	 *
	 * @param object $feedItem A single feed item
	 * @return object The RSS-Bridge item
	 *
	 * @todo To reduce confusion, the RSS-Bridge item should maybe have a class
	 * of its own?
	 */
	protected function parseRSS_1_0_Item($feedItem){
		// 1.0 adds optional elements around the 0.91 standard
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])) {
			$dc = $feedItem->children($namespaces['dc']);
			if(isset($dc->date)) $item['timestamp'] = strtotime((string)$dc->date);
			if(isset($dc->creator)) $item['author'] = (string)$dc->creator;
		}

		return $item;
	}

	/**
	 * Parse the contents of a single RSS 2.0 feed item into a RSS-Bridge item
	 * for further transformation.
	 *
	 * @param object $feedItem A single feed item
	 * @return object The RSS-Bridge item
	 *
	 * @todo To reduce confusion, the RSS-Bridge item should maybe have a class
	 * of its own?
	 */
	protected function parseRSS_2_0_Item($feedItem){
		// Primary data is compatible to 0.91 with some additional data
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])) $dc = $feedItem->children($namespaces['dc']);
		if(isset($namespaces['media'])) $media = $feedItem->children($namespaces['media']);

		if(isset($feedItem->guid)) {
			foreach($feedItem->guid->attributes() as $attribute => $value) {
				if($attribute === 'isPermaLink'
					&& ($value === 'true' || (
							filter_var($feedItem->guid, FILTER_VALIDATE_URL)
							&& (empty($item['uri']) || !filter_var($item['uri'], FILTER_VALIDATE_URL))
						)
					)
				) {
					$item['uri'] = (string)$feedItem->guid;
					break;
				}
			}
		}

		if(isset($feedItem->pubDate)) {
			$item['timestamp'] = strtotime((string)$feedItem->pubDate);
		} elseif(isset($dc->date)) {
			$item['timestamp'] = strtotime((string)$dc->date);
		}

		if(isset($feedItem->author)) {
			$item['author'] = (string)$feedItem->author;
		} elseif (isset($feedItem->creator)) {
			$item['author'] = (string)$feedItem->creator;
		} elseif(isset($dc->creator)) {
			$item['author'] = (string)$dc->creator;
		} elseif(isset($media->credit)) {
				$item['author'] = (string)$media->credit;
		}

		if(isset($feedItem->enclosure) && !empty($feedItem->enclosure['url'])) {
			$item['enclosures'] = array((string)$feedItem->enclosure['url']);
		}

		return $item;
	}

	/**
	 * Parse the contents of a single feed item, depending on the current feed
	 * type, into a RSS-Bridge item.
	 *
	 * @param object $item The current feed item
	 * @return object A RSS-Bridge item, with (hopefully) the whole content
	 */
	protected function parseItem($item){
		switch($this->feedType) {
		case self::FEED_TYPE_RSS_1_0:
			return $this->parseRSS_1_0_Item($item);
			break;
		case self::FEED_TYPE_RSS_2_0:
			return $this->parseRSS_2_0_Item($item);
			break;
		case self::FEED_TYPE_ATOM_1_0:
			return $this->parseATOMItem($item);
			break;
		default: returnClientError('Unknown version ' . $this->getInput('version') . '!');
		}
	}

	/** {@inheritdoc} */
	public function getURI(){
		return !empty($this->uri) ? $this->uri : parent::getURI();
	}

	/** {@inheritdoc} */
	public function getName(){
		return !empty($this->title) ? $this->title : parent::getName();
	}

	/** {@inheritdoc} */
	public function getIcon(){
		return !empty($this->icon) ? $this->icon : parent::getIcon();
	}
}
