<?php
class ArxivBridge extends BridgeAbstract {
	const MAINTAINER = 'l1n';
	const NAME = 'arXiv Bridge';
	const URI = 'http://export.arxiv.org';
	const CACHE_TIMEOUT = 3600 * 12; // 5 minutes 3600; // 1 hour
	const DESCRIPTION = 'Converts arXiv RDF feeds to standard RSS feeds :eyeroll:';
	const PARAMETERS = array(
		'global' => array(),
		'Subject' => array(
			'class' => array(
				'name' => 'Subject (e.g. for http://arxiv.org/rss/math.QA, math.QA)',
				'title' => 'http://arxiv.org/rss/<SUBJECT>'
			)
		)
	);

	public function collectData() {
		$url = $this->getURI();
		$contents = getContents($url) or
			returnClientError('Could not retrieve RDF for ' . $this->getInput('class') . '.');
		$rdf = new SimpleXMLElement($contents);
		foreach ($rdf->item as $article) {
			$item = array();

			$item['content'] = '' . $article->description;
			$item['uri'] = '' . $article->link;
			$item['title'] = '' . $article->title;
			$this->items[] = $item;
		}
	}

	public function getURI() {
		$url = parent::getURI() . '/rss/' . $this->getInput('class');
		return $url;
	}

	public function getName(){
		return parent::getName();
	}
}
