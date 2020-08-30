<?php

class RivieraBridge extends BridgeAbstract {
	const NAME = 'Sala La Riviera';
	const URI = 'https://salariviera.com/conciertos-riviera/';
	const DESCRIPTION = 'Concerts in Sala La Riviera, Madrid';
	const MAINTAINER = 'danoloan';
	const PARAMETERS = array(); // Can be omitted!
	const CACHE_TIMEOUT = 1;

	private function eventToItem($node, string $month) : Event {
		$event_date_day = $node->find('.event-date-day', 0);
		$event_title    = $node->find('.event-title', 0);
		$event_status_wrapper = $node->find('.event-status-wrapper', 0);

		$date  = $event_date_day->innertext . ' ' . $month;
		$title = $event_title->find('a', 0)->innertext;
		$link  = $event_title->find('a', 0)->href;
		//$buy   = $event_status_wrapper->innertext;
		//
		$date = str_replace("enero", "january", $date);
		$date = str_replace("febrero", "february", $date);
		$date = str_replace("marzo", "march", $date);
		$date = str_replace("abril", "april", $date);
		$date = str_replace("mayo", "may", $date);
		$date = str_replace("junio", "june", $date);
		$date = str_replace("julio", "july", $date);
		$date = str_replace("agosto", "august", $date);
		$date = str_replace("septiembre", "september", $date);
		$date = str_replace("octubre", "october", $date);
		$date = str_replace("noviembre", "november", $date);
		$date = str_replace("diciembre", "december", $date);

		$event = new \Event();
		$event->uid = $link;
		$event->stamp = time();
		$event->start = strtotime($date);
		$event->end = strtotime($date) + 86400;
		$event->summary = $title;
		$event->fill = true;

/*
		$item['titulo'] = $date . ' - ' . $title;
		//$item['content'] = $date . '<br><div style=\'font-size:150%; font-weigth:bold\'>' . $title . '</div><br>' . $buy;

 */
		return $event;
	}

	private function getEventItemsPage(int $page) {
		$events = array();

		$html = getSimpleHTMLDOM(self::URI . '/page/' . $page);
		$month = '';

		foreach ($html->find('.gdlr-list-event, .gdlr-list-by-month-header')
			as $node) {
			if ($node->class == 'gdlr-list-by-month-header') {
				$month = $node->innertext;
			} else {
				$event = $this->eventToItem($node, $month);
				$events[] = $event;
			}
		}

		return array_reverse($events);
	}

	private function getEventItems() : array {
		$page = 0;
		$events = array();
		do {
			$current = $this->getEventItemsPage($page);
			$events  = array_merge($current, $events);
			$page++;
		} while (sizeof($current) > 0);
		return $events;
	}

	public function collectData() {
		$this->events = array_merge($this->items, $this->getEventItems());
	}
}
