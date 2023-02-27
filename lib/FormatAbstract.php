<?php

abstract class FormatAbstract
{
    public const ITUNES_NS = 'http://www.itunes.com/dtds/podcast-1.0.dtd';

    const MIME_TYPE = 'text/plain';

    protected string $charset = 'UTF-8';
	protected array $events = [];
    protected array $items = [];
    protected int $lastModified;
    protected array $extraInfos = [];

    abstract public function stringify();

    public function getMimeType(): string
    {
        return static::MIME_TYPE;
    }

    public function setCharset(string $charset)
    {
        $this->charset = $charset;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setLastModified(int $lastModified)
    {
        $this->lastModified = $lastModified;
    }

    /**
     * @param FeedItem[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return FeedItem[] The items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function setExtraInfos(array $infos = [])
    {
        $extras = [
            'name',
            'uri',
            'icon',
            'donationUri',
        ];
        foreach ($extras as $extra) {
            if (!isset($infos[$extra])) {
                $infos[$extra] = '';
            }
        }
        $this->extraInfos = $infos;
    }

    public function getExtraInfos(): array
    {
        if (!$this->extraInfos) {
            $this->setExtraInfos();
        }
        return $this->extraInfos;
    }

	/**
	 * {@inheritdoc}
	 *
	 * @param array $events {@inheritdoc}
	 */
	public function setEvents(array $events){
		$this->events = $events;

		return $this;
	}

	/** {@inheritdoc} */
	public function getEvents(){
		if(!is_array($this->events))
			throw new \LogicException('Feed the ' . get_class($this) . ' with "setEvents" method before !');

		return $this->events;
	}

	/**
	 * Sanitize HTML while leaving it functional.
	 *
	 * Keeps HTML as-is (with clickable hyperlinks) while reducing annoying and
	 * potentially dangerous things.
	 *
	 * @param string $html The HTML content
	 * @return string The sanitized HTML content
	 *
	 * @todo This belongs into `html.php`
	 * @todo Maybe switch to http://htmlpurifier.org/
	 * @todo Maybe switch to http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/index.php
	 */
	protected function sanitizeHtml($html)
	{
		$html = str_replace('<script', '<&zwnj;script', $html); // Disable scripts, but leave them visible.
		$html = str_replace('<iframe', '<&zwnj;iframe', $html);
		$html = str_replace('<link', '<&zwnj;link', $html);
		// We leave alone object and embed so that videos can play in RSS readers.
		return $html;
	}

	/**
	 * Trim each element of an array
	 *
	 * This function applies `trim()` to all elements in the array, if the element
	 * is a valid string.
	 *
	 * @param array $elements The array to trim
	 * @return array The trimmed array
	 *
	 * @todo This is a utility function that doesn't belong here, find a new home.
	 */
	protected function array_trim($elements){
		foreach($elements as $key => $value) {
			if(is_string($value))
				$elements[$key] = trim($value);
		}
		return $elements;
	}
}
