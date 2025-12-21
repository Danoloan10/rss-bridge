<?php

class FunhouseBridge extends BridgeAbstract {
	const NAME = 'Funhouse Music Bar';
	const URI = 'https://www.funhousemusicbar.com/conciertos/';
	const DESCRIPTION = 'Concerts in Funhouse Music Bar, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node) : ?Event {
		$title_container = $node->find('h1.h5', 0) ?: $node->find('.h5', 0);
		if (!$title_container) return null;

		$event_title = $title_container->find('a', 0);
		if (!$event_title) return null;

		$title = trim($event_title->plaintext);
		if (empty($title)) return null;

		$date_node = $node->find('.h6.mb-2.text-muted', 0);
		if (!$date_node) return null;

		// Parse date and time (format: "23 Dic 21:00h")
		if (!preg_match('/(\d{1,2})\s+(\w+)\s+(\d{1,2}):(\d{2})h?/', trim($date_node->plaintext), $m)) {
			return null;
		}

		$month_en = $this->translateSpanishMonth($m[2]);
		$month_num = date('n', strtotime($month_en));
		$year = ($month_num < date('n') || ($month_num == date('n') && (int)$m[1] < date('j'))) ? date('Y') + 1 : date('Y');

		$datetime_str = $m[1] . ' ' . $month_en . ' ' . $year . ' ' . $m[3] . ':' . $m[4];
		$start = strtotime($datetime_str);
		if ($start === false || $start <= 0) return null;

		$event = new \Event();
		$event->uid = $event_title->href;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $event_title->href;
		$event->fill = false;
		$event->start = $start;
		$event->end = $start + 7200;

		return $event;
	}

	private function translateSpanishMonth($month) {
		$translations = [
			'Ene' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Abr' => 'Apr',
			'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Ago' => 'Aug',
			'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dic' => 'Dec'
		];
		return $translations[$month] ?? $month;
	}

	private function getEventItems() : array {
		$events = [];
		$seen_uids = [];
		$html = getSimpleHTMLDOM(self::URI);

		foreach ($html->find('.card-body') as $event_node) {
			$event = $this->eventToItem($event_node);
			if ($event && !isset($seen_uids[$event->uid])) {
				$seen_uids[$event->uid] = true;
				$events[] = $event;
			}
		}

		return empty($events) ? $events : array_reverse($events);
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
