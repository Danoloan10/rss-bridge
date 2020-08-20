<?php

class RivieraBridge extends BridgeAbstract {
	const NAME = 'Sala La Riviera';
	const URI = 'https://salariviera.com/conciertossalariviera/';
	const DESCRIPTION = 'Concerts in Sala La Riviera, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array(); // Can be omitted!
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node) : Event {
		$event_title = $node->find('.entry-title', 0);
		$title = $event_title->find('a', 0)->plaintext;
		$link = $event_title->find('a', 0)->href;

		$date = trim($node->find('.ecs-eventDate', 0)->plaintext);
		$time = str_replace(' ', '', $node->find('.ecs-eventTime .decm_time', 0)->plaintext);

		$date = str_replace('de ', '', $date);
		$date = $this->translateSpanishMonth($date);

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->summary = $title;

		if (!preg_match('/PM/', $time)) {
			$event->fill = true;
			$event->start = strtotime($date);
			$event->end = strtotime($date);
		} else {
			$event->fill = false;
			$event->start = strtotime($date . ' ' . $time);
			$event->end = $event->start + 7200;
		}

		return $event;
	}

	private function translateSpanishMonth($date) {
		$translations = [
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
		$html = getSimpleHTMLDOM(self::URI);

		foreach ($html->find('.ecs-event') as $node) {
			$events[] = $this->eventToItem($node);
		}

		return array_reverse($events);
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
