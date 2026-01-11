<?php

class SalaButBridge extends BridgeAbstract {
	const NAME = 'Sala But';
	const URI = 'https://www.salabut.es/agenda-conciertos/';
	const DESCRIPTION = 'Concerts in Sala But, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($title, $date_text, $link) : ?Event {
		$title = trim($title);
		if (empty($title)) return null;

		if (!preg_match('/(\d{1,2})\s+(\w+)\s+(\d{4})/', trim($date_text), $m)) {
			return null;
		}

		$datetime_str = $m[1] . ' ' . $this->translateSpanishMonth($m[2]) . ' ' . $m[3];
		$start = strtotime($datetime_str);
		if ($start === false || $start <= 0) return null;

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $link;
		$event->fill = true;
		$event->start = $start;
		$event->end = $start;

		return $event;
	}

	private function translateSpanishMonth($month) {
		$translations = [
			'ENE' => 'Jan', 'FEB' => 'Feb', 'MAR' => 'Mar', 'ABR' => 'Apr',
			'MAY' => 'May', 'JUN' => 'Jun', 'JUL' => 'Jul', 'AGO' => 'Aug',
			'SEP' => 'Sep', 'OCT' => 'Oct', 'NOV' => 'Nov', 'DIC' => 'Dec'
		];
		return $translations[strtoupper($month)] ?? $month;
	}

	private function getEventItems() : array {
		$events = [];
		$seen_uids = [];
		$html = getSimpleHTMLDOM(self::URI);

		foreach ($html->find('div[data-element_type=container]') as $container) {
			$title_spans = $container->find('span.elementor-heading-title');
			$button = $container->find('a.elementor-button-link', 0);

			if (count($title_spans) >= 2 && $button && preg_match('/\d{1,2}\s+\w{3}\s+\d{4}/', $title_spans[1]->plaintext)) {
				$event = $this->eventToItem(
					trim($title_spans[0]->plaintext),
					trim($title_spans[1]->plaintext),
					$button->href
				);
				if ($event && !isset($seen_uids[$event->uid])) {
					$seen_uids[$event->uid] = true;
					$events[] = $event;
				}
			}
		}

		return array_reverse($events);
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
