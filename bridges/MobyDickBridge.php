<?php

class MobyDickBridge extends BridgeAbstract {
	const NAME = 'Moby Dick Club';
	const URI = 'https://www.mobydickclub.com/programacion.php';
	const DESCRIPTION = 'Events in Moby Dick Club, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($title_node, $date_node) : ?Event {
		$event_title = $title_node->find('a', 0);
		if (!$event_title) return null;

		$title = trim($event_title->plaintext);
		if (empty($title)) return null;

		$link = 'https://www.mobydickclub.com/' . $event_title->href;

		// Parse date (format: "SÁBADO 20 de DICIEMBRE.")
		$date_text = trim($date_node->plaintext);
		if (!preg_match('/(\w+)\s+(\d{1,2})\s+de\s+(\w+)\./', $date_text, $m)) {
			return null;
		}

		$day = $m[2];
		$month = $m[3];

		$month_en = $this->translateSpanishMonth($month);
		$month_num = date('n', strtotime($month_en));
		$year = ($month_num < date('n') || ($month_num == date('n') && (int)$day < date('j'))) ? date('Y') + 1 : date('Y');

		$datetime_str = $day . ' ' . $month_en . ' ' . $year;
		$start = strtotime($datetime_str);
		if ($start === false || $start <= 0) return null;

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $link;
		$event->fill = false;
		$event->start = $start;
		$event->end = $start + 7200;

		return $event;
	}

	private function translateSpanishMonth($month) {
		$translations = [
			'ENERO' => 'january', 'FEBRERO' => 'february', 'MARZO' => 'march', 'ABRIL' => 'april',
			'MAYO' => 'may', 'JUNIO' => 'june', 'JULIO' => 'july', 'AGOSTO' => 'august',
			'SEPTIEMBRE' => 'september', 'OCTUBRE' => 'october', 'NOVIEMBRE' => 'november', 'DICIEMBRE' => 'december'
		];
		return $translations[strtoupper($month)] ?? strtolower($month);
	}

	private function getEventItems() : array {
		$events = [];
		$seen_uids = [];
		$html = getSimpleHTMLDOM(self::URI);

		// Find all event containers
		$containers = $html->find('[id=textoprog]');
		
		foreach ($containers as $container) {
			$title_node = $container->find('[id=grupotitulo]', 0);
			$date_node = $container->find('.conciertofecha', 0);
			
			if ($title_node && $date_node) {
				$event = $this->eventToItem($title_node, $date_node);
				if ($event && !isset($seen_uids[$event->uid])) {
					$seen_uids[$event->uid] = true;
					$events[] = $event;
				}
			}
		}

		return empty($events) ? $events : array_reverse($events);
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
