<?php
class Event {
	protected $uid = null;
	protected $stamp = null;
	protected $start = null;
	protected $end = null;
	protected $summary = null;
	protected $desc = null;
	protected $location = null;
	protected $fill = null;

	/**
	 * Get unique id
	 *
	 * Use {@see FeedItem::setUid()} to set the unique id.
	 *
	 * @param string The unique id.
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Set unique id.
	 *
	 * Use {@see FeedItem::getUid()} to get the unique id.
	 *
	 * @param string $uid A string that uniquely identifies the current item
	 * @return self
	 */
	public function setUid($uid) {
		$this->uid = null; // Clear previous data

		if(!is_string($uid)) {
			Debug::log('Unique id must be a string!');
		} elseif (preg_match('/^[a-f0-9]{40}$/', $uid)) {
			// keep id if it already is a SHA-1 hash
			$this->uid = $uid;
		} else {
			$this->uid = sha1($uid);
		}

		return $this;
	}

	/**
	 * Get current stamp.
	 *
	 * Use {@see FeedItem::setStamp()} to set the stamp.
	 *
	 * @return string|null The current stamp or null if it hasn't been set.
	 */
	public function getStamp() {
		return $this->stamp;
	}

	/**
	 * Set stamp.
	 *
	 * Use {@see FeedItem::getStamp()} to get the stamp.
	 *
	 * @param int $stamp The stamp
	 * @return self
	 */
	public function setStamp($stamp) {
		$this->stamp = self::validStamp($stamp);
		return $this;
	}

	/**
	 * Get current from.
	 *
	 * Use {@see FeedItem::setStart()} to set the from.
	 *
	 * @return int|null The current from or null if it hasn't been set.
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * Set from of first release.
	 *
	 * _Note_: The from should represent the number of seconds since
	 * January 1 1970 00:00:00 GMT (Unix time).
	 *
	 * _Remarks_: If the provided from is a string (not numeric), this
	 * function automatically attempts to parse the string using
	 * [strtotime](http://php.net/manual/en/function.strtotime.php)
	 *
	 * @link http://php.net/manual/en/function.strtotime.php strtotime (PHP)
	 * @link https://en.wikipedia.org/wiki/Unix_time Unix time (Wikipedia)
	 *
	 * @param string|int $start A from of when the item was first released
	 * @return self
	 */
	public function setStart($start) {
		$this->start = self::validStamp($start); // Clear previous data
		return $this;
	}

	/**
	 * Get the current end name.
	 *
	 * Use {@see FeedItem::setEnd()} to set the end.
	 *
	 * @return string|null The end or null if it hasn't been set.
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * Set the end name.
	 *
	 * Use {@see FeedItem::getEnd()} to get the end.
	 *
	 * @param string $end The end name.
	 * @return self
	 */
	public function setEnd($end) {
		$this->end = self::validStamp($end);
		return $this;
	}

	/**
	 * Get item summary.
	 *
	 * Use {@see FeedItem::setSummary()} to set the item summary.
	 *
	 * @return string|null The item summary or null if it hasn't been set.
	 */
	public function getSummary() {
		return $this->summary;
	}

	/**
	 * Set item summary.
	 *
	 * Note: This function casts objects of type simple_html_dom and
	 * simple_html_dom_node to string.
	 *
	 * Use {@see FeedItem::getSummary()} to get the current item summary.
	 *
	 * @param string|object $summary The item summary as text or simple_html_dom
	 * object.
	 * @return self
	 */
	public function setSummary($summary) {
		$this->summary = null; // Clear previous data

		if(!is_string($summary)) {
			Debug::log('Summary must be a string!');
		} else {
			$this->summary = trim($summary);
		}

		return $this;
	}

	/**
	 * Get item desc.
	 *
	 * Use {@see FeedItem::setDesc()} to set feed desc.
	 *
	 * @return array Desc as array of enclosure Uids.
	 */
	public function getDesc() {
		return $this->desc;
	}

	/**
	 * Set item desc.
	 *
	 * Use {@see FeedItem::getDesc()} to get the current item desc.
	 *
	 * @param array $desc Array of desc, where each element links to
	 * one enclosure.
	 * @return self
	 */
	public function setDesc($desc) {
		$this->desc = array(); // Clear previous data

		if(!is_string($desc)) {
			Debug::log('Desc must be a string!');
		} else {
			$this->desc = trim($desc);
		}

		return $this;
	}

	/**
	 * Get item location.
	 *
	 * Use {@see FeedItem::setLocation()} to set item location.
	 *
	 * @param array The item location.
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Set item location.
	 *
	 * Use {@see FeedItem::getLocation()} to get the current item location.
	 *
	 * @param array $location Array of location, where each element defines
	 * a single category name.
	 * @return self
	 */
	public function setLocation($location) {
		$this->location = array(); // Clear previous data

		if(!is_string($location)) {
			Debug::log('Location must be a string!');
		} else {
			$this->location = trim($location);
		}

		return $this;
	}

	public function getFill() {
		return $this->fill;
	}

	public function setFill($fill) {
		$this->fill = false; // Clear previous data

		if(!is_bool($fill)) {
			Debug::log('Fill must be a bool!');
		} else {
			$this->fill = $fill;
		}

		return $this;
	}

	/**
	 * Transform current object to array
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'uid' => $this->uid,
			'stamp' => $this->stamp,
			'start' => $this->start,
			'end' => $this->end,
			'summary' => $this->summary,
			'desc' => $this->desc,
			'location' => $this->location,
			'fill' => $this->fill,
		);
	}
	function __set($name, $value) {
		switch($name) {
		case 'uid': $this->setUid($value); break;
		case 'stamp': $this->setStamp($value); break;
		case 'start': $this->setStart($value); break;
		case 'end': $this->setEnd($value); break;
		case 'summary': $this->setSummary($value); break;
		case 'desc': $this->setDesc($value); break;
		case 'location': $this->setLocation($value); break;
		case 'fill': $this->setFill($value); break;
		}
	}
	function __get($name) {
		switch($name) {
		case 'uid': return $this->getUid();
		case 'stamp': return $this->getStamp();
		case 'start': return $this->getStart();
		case 'end': return $this->getEnd();
		case 'summary': return $this->getSummary();
		case 'desc': return $this->getDesc();
		case 'location': return $this->getLocation();
		case 'fill': return $this->getFill();
		default:
		return null;
		}
	}

	public static function validStamp($stamp) {
		if(!is_numeric($stamp)
			&& !$stamp = strtotime($stamp)) {
			Debug::log('Unable to parse from!');
		}

		if($stamp <= 0) {
			Debug::log('Start must be greater than zero!');
		} else {
			return $stamp;
		}

		return null;
	}
}
