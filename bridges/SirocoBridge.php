<?php

class SirocoBridge extends BridgeAbstract {
	const NAME = 'Siroco';
	const URI = 'https://siroco.es/agenda/';
	const DESCRIPTION = 'Concerts in Siroco, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node, $date_text, $time_text = '') : ?Event {
		$title = trim($node->plaintext);
		$link = $node->href;

		// Skip if it's just "Info", "Tickets", "Vacio" or other non-event links
		if (in_array(strtolower($title), ['info', 'tickets', 'vacio', 'ver agenda', 'ver conciertos'])) {
			return null;
		}

		// Parse date
		$date_text = str_replace('de ', '', $date_text);
		
		// Remove weekday from date string first (in Spanish)
		$date_text = preg_replace('/^(lunes|martes|miércoles|jueves|viernes|sábado|domingo)\s+/i', '', $date_text);
		$date_text = trim($date_text);
		
		// Then translate to English
		$date_text = $this->translateSpanishMonth($date_text);

		// Add year if not present
		if (!preg_match('/\d{4}/', $date_text)) {
			// Parse day and month
			$parts = explode(' ', $date_text);
			if (count($parts) >= 2) {
				$day = (int)$parts[0];
				$month_name = $parts[1];
				$month_num = date('n', strtotime($month_name));
				
				// Determine year (if month has passed, it's next year)
				$current_month = (int)date('n');
				$current_day = (int)date('j');
				if ($month_num < $current_month || ($month_num == $current_month && $day < $current_day)) {
					$year = date('Y') + 1;
				} else {
					$year = date('Y');
				}
				
				// Reconstruct date in a format strtotime can handle
				$date_text = sprintf('%d-%02d-%02d', $year, $month_num, $day);
			}
		}

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $link;

		// Parse time and create event
		if (!empty($time_text) && preg_match('/(\d{1,2})[:.h]\s*(\d{2})?/', $time_text, $matches)) {
			$hour = $matches[1];
			$minute = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : '00';
			$event->fill = false;
			$datetime_str = $date_text . ' ' . $hour . ':' . $minute;
			$event->start = strtotime($datetime_str);
			if ($event->start === false || $event->start <= 0) return null;
			$event->end = $event->start + 7200; // 2 hours
		} else {
			// Default to all-day event
			$event->fill = true;
			$event->start = strtotime($date_text);
			if ($event->start === false || $event->start <= 0) return null;
			$event->end = strtotime($date_text);
		}

		return $event;
	}

	private function translateSpanishMonth($date) {
		$translations = [
			// Weekdays
			'lunes' => 'monday',
			'martes' => 'tuesday',
			'miércoles' => 'wednesday',
			'jueves' => 'thursday',
			'viernes' => 'friday',
			'sábado' => 'saturday',
			'domingo' => 'sunday',
			// Months
			'enero' => 'january',
			'febrero' => 'february',
			'marzo' => 'march',
			'abril' => 'april',
			'mayo' => 'may',
			'junio' => 'june',
			'julio' => 'july',
			'agosto' => 'august',
			'septiembre' => 'september',
			'octubre' => 'october',
			'noviembre' => 'november',
			'diciembre' => 'december',
		];
		return str_replace(array_keys($translations), array_values($translations), $date);
	}

	private function getEventItems() : array {
		$events = [];
		$seen_uids = [];
		$html = getSimpleHTMLDOM(self::URI);

		// Find all date containers (caja-fechas)
		$date_containers = $html->find('.caja-fechas');

		foreach ($date_containers as $date_container) {
			// Get the date from fecha-superior-publico
			$date_header = $date_container->find('.fecha-superior-publico', 0);
			if (!$date_header) {
				continue;
			}

			$current_date = trim($date_header->plaintext);
			if (empty($current_date)) {
				continue;
			}

			// Find the next sibling which should be the contenedor with event details
			$next = $date_container->next_sibling();
			while ($next && $next->tag !== 'div') {
				$next = $next->next_sibling();
			}

			if (!$next || strpos($next->class, 'contenedor') === false) {
				continue;
			}

			// Check if this is a concert event (categoria-153 is for Conciertos)
			// Only process if it's a concert (cajas-153 or categoria-153)
			$is_concert = strpos($next->class, 'cajas-153') !== false;
			if (!$is_concert) {
				$category = $next->find('.categoria-153', 0);
				if (!$category) {
					continue;
				}
			}

			// Find event title links within this contenedor
			$event_links = $next->find('.nombre_evento a');
			foreach ($event_links as $link_node) {
				// Get the time from the same container
				$time_node = $next->find('.espacio', 0);
				$time_text = $time_node ? trim($time_node->plaintext) : '';

				$event = $this->eventToItem($link_node, $current_date, $time_text);
				if ($event && !isset($seen_uids[$event->uid])) {
					$seen_uids[$event->uid] = true;
					$events[] = $event;
				}
			}
		}

		return empty($events) ? $events : array_reverse($events);
	}

	public function collectData() {
		$events = $this->getEventItems();
		$this->events = array_merge($this->items, $events);
	}
}
