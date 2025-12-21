<?php

class ElSolBridge extends BridgeAbstract {
	const NAME = 'Sala El Sol';
	const URI = 'https://salaelsol.com/agenda/';
	const DESCRIPTION = 'Concerts in Sala El Sol, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node, $date_text) : ?Event {
		// Find event title link
		$event_title_link = $node->find('.nombre_evento a', 0) ?: $node->find('p.nombre_evento a', 0);
		if (!$event_title_link) {
			return null;
		}

		$title = trim($event_title_link->plaintext);
		$link = $event_title_link->href;

		// Find event time
		$time_text = '';
		$event_time_node = $node->find('.cajas-eventos-new .espacio', 0)
			?: $node->find('.espacio', 0)
			?: $node->find('.fecha-superior-home', 1);

		if ($event_time_node) {
			$time_text = trim($event_time_node->plaintext);
			$time_text = preg_replace('/^.*\d{1,2}\s+\w+\s*/', '', $time_text);
		}

		// Parse date
		$date_text = str_replace('de ', '', $date_text);
		$date_text = $this->translateSpanishMonth($date_text);

		// Add year if not present
		if (!preg_match('/\d{4}/', $date_text)) {
			$date_parts = explode(' ', trim($date_text));
			$month_num = date('n', strtotime(end($date_parts)));
			$day = (int)$date_parts[count($date_parts) - 2];
			$year = ($month_num < date('n') || ($month_num == date('n') && $day < date('j'))) ? date('Y') + 1 : date('Y');
			$date_text .= ' ' . $year;
		}

		// Remove weekday from date string (e.g., "sunday 21 december 2024" -> "21 december 2024")
		$date_text = preg_replace('/^(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\s+/', '', $date_text);

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;
		$event->desc = $link;

		// Parse time and create event
		if (!empty($time_text) && preg_match('/(\d{2})[:.]\s*(\d{2})?/', $time_text, $matches)) {
			$hour = $matches[1];
			$minute = $matches[2] ?? '00';
			$event->fill = false;
			$datetime_str = $date_text . ' ' . $hour . ':' . $minute;
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

		foreach ($html->find('.contenedor-2') as $event_data) {
			// Check if this is a concert event
			$category = $event_data->find('.categoria-12', 0);
			if (!$category || strpos($category->plaintext, 'Conciertos') === false) {
				continue;
			}

			// Find parent gran-contenedor-agenda to get the date
			$parent = $event_data->parent();
			$count = 0;
			while ($parent && strpos($parent->class, 'gran-contenedor-agenda') === false && $count++ < 10) {
				$parent = $parent->parent();
			}

			$current_date = '';
			if ($parent) {
				$date_node = $parent->find('.fecha-superior', 0);
				if ($date_node) {
					$current_date = trim($date_node->plaintext);
				}
			}

			// Skip if we don't have a date
			if (empty($current_date)) {
				continue;
			}

			// Parse the event
			$event = $this->eventToItem($event_data, $current_date);
			if ($event && !isset($seen_uids[$event->uid])) {
				$seen_uids[$event->uid] = true;
				$events[] = $event;
			}
		}

		return empty($events) ? $events : array_reverse($events);
	}

	public function collectData() {
		$events = $this->getEventItems();
		$this->events = array_merge($this->items, $events);
	}
}
