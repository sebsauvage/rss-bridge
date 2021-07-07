<?php
class GNOMEShellExtensionsBridge extends BridgeAbstract {
	const NAME = 'GNOME Shell-Extensions Bridge';
	const URI = 'https://extensions.gnome.org';
	const DESCRIPTION = 'Gnome Shell-Extensions sorted by category';
	const MAINTAINER = 'free-bots';
	const PARAMETERS = array( array(
			'pageCount' => array(
				'name' => 'max pages',
				'title' => 'Maximal count of pages to be fetched',
				'exampleValue' => '1',
				'type' => 'number',
				'required' => true
			),
			'sort' => array(
				'name' => 'sorted by',
				'type' => 'list',
				'defaultValue' => 'recent',
				'values' => array(
					'Name' => 'name',
					'Recent' => 'recent',
					'Downloads' => 'downloads',
					'Popularity' => 'popularity'
				)
			)
		)
	);
	const CACHE_TIMEOUT = 3600;

	public function getIcon() {
		return 'https://extensions.gnome.org/static/images/favicon.b73b0c0e30d2.png';
	}

	public function getName() {
		$sort = $this->getInput('sort');
		if ($sort !== null) {
			return $this->getInput('sort') . ' - GNOME Shell-Extensions';
		}
		return parent::getName();
	}

	public function collectData() {
		$totalPages = 0;
		$lastNumPages = -1;

		$maxPages = intval($this->getInput('pageCount'), 10);
		for($i = 1; $i <= $maxPages; $i++) {

			// skip if the user enters more pages as available
			if ($lastNumPages !== -1 && $lastNumPages <= $totalPages) {
				continue;
			}

			$queryUrl = $this->createQueryUrl($i);
			$page = json_decode(getContents($queryUrl)) or returnServerError('Could not load extensions');

			$lastNumPages = $page->numpages;
			$extensions = $page->extensions;

			foreach($extensions as $extension) {
				$item = array();

				$item['title'] = $extension->name;
				$item['author'] = $extension->creator;
				$item['content'] = $this->createContent($extension);
				$item['uid'] = $extension->uuid;
				$item['uri'] = $extension->link;

				$this->items[] = $item;
			}

			$totalPages++;
		}
	}

	private function createContent($extension) {
		$icon = $extension->icon;
		$screenshot = $extension->screenshot;
		$description = $extension->description;
		$uri = $extension->link;

		$content = '<a href="' . $this->createUri($uri) . '" >';
		if ($icon !== null) {
			$content .= $this->createImage($this->createUri($icon), 'extension-icon', 50);
		}

		if ($screenshot !== null) {
			$content .= $this->createImage($this->createUri($screenshot), 'extension-screenshot', 300);
		}

		$content .= '<p style="text-align:center;">' . nl2br($description) . '</p>';

		$content .= '</a>';

		return $content;
	}

	private function createImage($uri, $alt, $maxSize) {
		return <<<EOD
                <img 
                    src="{$uri}" 
                    alt="{$alt}"
                    style="display: block;
                        margin-left: auto;
                        margin-right: auto;
                        object-fit: contain;
                        max-height:{$maxSize}px;
                        max-width:{$maxSize}px;"
                    />
                <br>
EOD;
	}

	private function createUri($rawUri) {
		return self::URI . str_replace('\\', '', $rawUri);
	}

	private function createQueryUrl($pageNumber) {
		return 'https://extensions.gnome.org/extension-query/?sort='
			. $this->getInput('sort')
			. '&page='
			. $pageNumber
			. '&shell_version=all';
	}
}
