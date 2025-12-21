<?php

class WurlitzerBridge extends BridgeAbstract {
	const NAME = 'Wurlitzer Ballroom';
	const URI = 'https://www.wurlitzerballroom.com/?page_id=5';
	const DESCRIPTION = 'Concerts in Wurlitzer Ballroom, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node) : ?Event {
		$event_title = $node->find('.mec-event-title a', 0);
		if (!$event_title) {
			return null;
		}

		$title = trim($event_title->plaintext);
		$link = $event_title->href;

		$date_node = $node->find('.mec-event-date .mec-start-date-label', 0);
		if (!$date_node) {
			return null;
		}

		$date_text = trim($date_node->plaintext);
		$date_text = $this->translateSpanishMonth($date_text);

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $link;

		// Check for time
		$time_node = $node->find('.mec-event-time', 0);
		$time_text = $time_node ? trim($time_node->plaintext) : '';

		if (!empty($time_text) && preg_match('/(\d{1,2}):(\d{2})/', $time_text, $matches)) {
			$event->fill = false;
			$datetime_str = $date_text . ' ' . $matches[1] . ':' . $matches[2];
			$event->start = strtotime($datetime_str);
			if ($event->start === false || $event->start <= 0) return null;
			$event->end = $event->start + 7200; // 2 hours
		} else {
			$event->fill = true;
			$event->start = strtotime($date_text);
			if ($event->start === false || $event->start <= 0) return null;
			$event->end = strtotime($date_text);
		}

		return $event;
	}

	private function translateSpanishMonth($date) {
		$translations = [
			'Ene' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Abr' => 'Apr',
			'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Ago' => 'Aug',
			'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dic' => 'Dec'
		];
		return str_replace(array_keys($translations), array_values($translations), $date);
	}

	private function getEventItems() : array {
		$events = [];
		$seen_uids = [];
		$html = getSimpleHTMLDOM(self::URI);

		$articles = $html->find('.mec-event-article');

		// Find the "Proximos Conciertos" section
		foreach ($articles as $event_node) {
			$event = $this->eventToItem($event_node);
            $seen_uids[$event->uid] = true;
            $events[] = $event;
		}

		return empty($events) ? $events : array_reverse($events);
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
