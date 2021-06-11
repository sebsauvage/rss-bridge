<?php
class HeadHunterBridge extends FeedExpander {

	const NAME = 'HeadHunter';
	const DESCRIPTION = 'Расширяет ленту путем добавления полного описания вакансии';
	const MAINTAINER = 'em92';
	const URI = 'https://hh.ru';

	const PARAMETERS = array(
		array('url' => array(
			'name' => 'Ссылка на ленту',
			'exampleValue' => 'https://ufa.hh.ru/search/vacancy/rss?area=99&clusters=true&enable_snippets=true&text=php',
			'required' => true,
		)),
	);

	protected function parseItem($newItem) {
		$item = parent::parseItem($newItem);

		$html = getSimpleHTMLDOMCached($item['uri']);
		$html = defaultLinkTo($html, $item['uri']);

		$content = '';
		$skills = array();

		// get skilltags
		foreach($html->find('.bloko-tag-list span') as $skill) {
			$skills[] = $skill->innertext;
		}

		// add salary to content
		$content .= $html->find('.vacancy-salary', 0)->outertext;

		// prepare employer content and add it
		$employer = $html->find('.vacancy-company-wrapper', 0);
		$item['author'] = $employer->find('.vacancy-company-name span', 0)->innertext;
		$content .= $employer->outertext;

		// clean up description and add it
		$description = $html->find('.vacancy-description', 0);
		$unwanted_element_selectors = array(
			'script',
			'style',
			'.tmpl_hh_head',
			'.tmpl_hh_slider',
			'.tmplt_hh-slider',
			// TODO: remove parent of .bloko-tag-list (skills elements)
		);
		foreach($unwanted_element_selectors as $unwanted_element_selector) {
			foreach($description->find($unwanted_element_selector) as $el) {
				$el->outertext = '';
			}
		}
		$content .= $description->outertext;

		# vacancy on the same uri can be republished
		# so adding timestamp (publication date) to url to show it again in feed
		$item['uid'] = $item['uri'] . '#' . strval($item['timestamp']);
		$item['content'] = $content;
		$item['categories'] = $skills;

		return $item;

	}

	public function collectData(){
		// TODO: check if host ends with .hh.ru
		// TODO: check .hh.ru/search/vacancy/rss
		// TODO: also accept .hh.ru/search/vacancy?area=99&clusters=true&enable_snippets=true&text=php
		// TODO: that link must be retunred in getURI
		$this->collectExpandableDatas($this->getInput('url'));
	}
}
