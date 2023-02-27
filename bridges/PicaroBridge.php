<?php

class PicaroBridge extends BridgeAbstract {
	const NAME = 'Sala Pícaro';
	const URI = 'https://sala.picarotoledo.com/agenda/';
	const DESCRIPTION = 'Concerts in Sala Pícaro, Toledo (Spain)';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array(); // Can be omitted!
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node) : Event {
		$event_date  = $node->find('.mec-start-date-label', 0);
		$event_time  = $node->find('.mec-start-time', 0);
		$event_title = $node->find('.mec-event-title', 0);

		$date_text = $event_date->innertext;
		$time  = $event_time->innertext;
		$title = $event_title->find('a', 0)->innertext;
		$link  = $event_title->find('a', 0)->href;

		// Translate Spanish months
		$translations = [
			'enero' => 'january', 'febrero' => 'february', 'marzo' => 'march', 'abril' => 'april',
			'mayo' => 'may', 'junio' => 'june', 'julio' => 'july', 'agosto' => 'august',
			'septiembre' => 'september', 'octubre' => 'october', 'noviembre' => 'november', 'diciembre' => 'december'
		];
		$date_text = str_replace(array_keys($translations), array_values($translations), $date_text);

		// Determine year - if month has passed, use next year
		$month_num = date('n', strtotime($date_text));
		$year = ($month_num < date('n')) ? date('Y') + 1 : date('Y');
		$date = $date_text . ' ' . $year;

		$event = new \Event();
		$event->timezone = "Europe/Madrid";
		$event->uid = $link;
		$event->stamp = time();
		$event->start = strtotime($date) + strtotime($time, 0);
		$event->end = $event->start + 3600*2;
		$event->summary = $title;
		$event->fill = false;

		return $event;
	}

	private function getEventItems() : array {
		$page = 0;
		$events = array();
		$html = getSimpleHTMLDOM(self::URI);
		foreach ($html->find('.mec-event-article') as $node) {
			$event = $this->eventToItem($node);
			$events[] = $event;
		}
		return $events;
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
