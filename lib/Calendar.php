<?php
class Calendar {
	/** @var array $calendar the calendar array */
	protected $calendar = array();

	/** @var string $header the header of the calendar */
	protected $header =
		"BEGIN:VCALENDAR\r\n" .
		"PRODID:-//php/ics\r\n" . 
		"VERSION:2.0\r\n" . 
		"METHOD:PUBLISH\r\n";

	/** @var string $footer the footer of the calendar */
	protected $footer = 'END:VCALENDAR';

	/** @var string $string ics string */
	protected $string = '';

	/**
	 * __construct
	 * @param array $calendar the calendar as an array
	 */
	public function __construct(array $calendar) {
		$this->calendar = $calendar;
		$this->string = $this->header;
		foreach($this->calendar as $event) {
			$this->string .= self::generateEventString($event);
		}
	}

	/**
	 * getString
	 * @return string ics string
	 */
	public function getString() {
		return $this->string . $this->footer;
	}

	public function addEvent(array $event) {
		$calendar[] = $event;
		$this->string .= self::generateEventString($event);
	}

	public function toArray() {
		return $this->calendar;
	}

	/**
	 *
	 * generateEventString
	 * @param array $event
	 * @return string event as ics string 
	 */
	public static function generateEventString(Event $event) {
		$ret = "BEGIN:VEVENT\r\n";
		$eventParts = array();

		// set uid
		if($event->uid != null) {
			$eventParts['UID'] = md5($event->uid . "@" . $_SERVER['SERVER_NAME']);
		}

		// set creation date
		if($event->stamp != null) {
			$eventParts['DTSTAMP'] = gmstrftime("%Y%m%dT%H%M00Z", $event->stamp);
		}
		elseif($event->start != null) {
			$eventParts['DTSTAMP'] = gmstrftime("%Y%m%dT%H%M00Z", $event->start);
		}

		$format = $event->fill ? "%Y%m%d" : "%Y%m%dT%H%M00Z";

		// set start time of the event
		if($event->start != null) {
			$eventParts['DTSTART'] = gmstrftime($format, $event->start);
		}

		// set end time of the event
		if($event->end != null) {
			$eventParts['DTEND'] = gmstrftime($format, $event->end);
		}

		// set summary
		if($event->summary != null) {
			$eventParts['SUMMARY'] = self::cleanString($event->summary);
		}

		// set description
		if($event->desc != null) {
			$eventParts['DESCRIPTION'] = self::cleanString($event->desc);
		}

		// set location
		if($event->location != null) {
			$eventParts['LOCATION'] = self::cleanString($event->location);
		}

		// check if all needed values are set if not throw exception
		if(!isset($eventParts['UID']) ||
			!isset($eventParts['DTSTAMP']) ||
			!isset($eventParts['DTSTART']) ||
			!isset($eventParts['DTEND']) ||
			!isset($eventParts['SUMMARY']))
		{
			throw new Exception(implode(', ', $eventParts));
		}

		// add event parts to return string
		foreach($eventParts as $strKey => $strValue) {
			$ret .= $strKey . ":" . $strValue . "\r\n";
		}

		// add end to return string
		$ret .= "END:VEVENT" . "\r\n";

		// return event string
		return($ret);
	}

	/**
	 * cleanString
	 * @param string $strDirtyString the dirty input string
	 * @return string cleaned string 
	 */
	public static function cleanString($dirty) {
		$bad = array('<br />', '<br/>', '<br>', "\r\n", "\r", "\n", "\t", '"');
		$good = array('\n', '\n', '\n', '', '', '', ' ', '\"');
		return(trim(str_replace($bad, $good, strip_tags($dirty, '<br>'))));
	}
}
