<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

namespace NewsML;

include('NewsML_NewsMessageReader.php');

/**
 * Reader for NewsML feeds
 */
class NewsMLFeed
{
    /** @var string */
    private $url;

    /** @var IFeedReader */
    private $reader;

    /** @var stdClass Object */
    private $current;

    /** @var array */
    private $remaining;

    /** @var integer */
    public $cacheTime = 3600;


    /**
     * Create reader object.
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
        $this->reset();
    }

    /**
     * Reset current item to first RSS item.
     */
    public function reset()
    {
        $this->current = -1;
        $this->remaining = NULL;
    }

    /**
     * Get the next item in the feed.
     *
     * @return stdClass Object representing the item. Will return NULL when the list is exhausted.
     */
    public function next()
    {
        if ($this->current < $this->count()) {
            $this->current++;
            $next = $this->getReader()->item($this->current);
            return $next;
        }
    }

    /**
     * Get the current item in the feed.
     *
     * @return stdClass Object representing the item. Will return NULL when the list is exhausted.
     */
    public function current()
    {
        return $this->getReader()->item(max(0, $this->current));
    }

    /**
     * Get random item from the feed. Will not return an item more than once.
     *
     * @return stdClass Object representing the item. Will return NULL when the list is exhausted.
     */
    public function random()
    {
        if ($this->remaining === NULL) {
            $this->remaining = array();
            for ($i = 0; $i < $this->count(); $i++) {
                $this->remaining[] = $i;
            }
        }

        if (count($this->remaining)) {
            $picked = array_rand($this->remaining);
            $index = $this->remaining[$picked];
            unset($this->remaining[$picked]);
            return $this->getReader()->item($index);
        }
    }

    /**
     * Get X items from feed. Will advance pointer.
     *
     * @param int $count
     * @return array of stdClass
     */
    public function find($count)
    {
        $items = array();

        while ($item = $this->next()) {
            $items[] = $item;
            if (count($items) >= $count) {
                break;
            }
        }

        return $items;
    }

    /**
     * Get the number of items in the feed.
     *
     * @return int
     */
    public function count()
    {
        return $this->getReader()->count();
    }
 
    /**
     * Get FeedReader object for the feed.
     *
     * @return FeedReader
     */
    private function getReader()
    {
        if (!$this->reader) {
            $xml = $this->getXML();
            if (NewsML_NewsMessageReader::canRead($xml)) {
                $this->reader = new NewsML_NewsMessageReader($xml);
            } else if (RSSReader::canRead($xml)) {
                $this->reader = new RSSReader($xml);
            } else if (AtomReader::canRead($xml)) {
                $this->reader = new AtomReader($xml);
            } else {
                $this->reader = new NullReader($xml);
            }
        }
        return $this->reader;
    }

    /**
     * Get XML element for the feed.
     *
     * @return SimpleXMLElement
     */
    private function getXML()
    {
        if ($xml = $this->getCacheXML()) {
            return $xml;
        } else if ($xml = $this->getURLXML()) {
            return $xml;
        } else {
            return new SimpleXMLElement('');
        }
	}

    /**
     * Get XML element for the feed from cache.
     *
     * @return SimpleXMLElement or NULL if cache doesn't exist.
     */
    private function getCacheXML()
    {
        //Store URL data in local cache.
        $cacheFilename = $this->getCacheFilename();
        if (file_exists($cacheFilename) && (time() - filemtime($cacheFilename)) < $this->cacheTime) {
            if ($data = @file_get_contents($cacheFilename)) {
                return new \SimpleXMLElement($data);
            }
        }
    }
 
    /**
     * Get XML element from the feed from the live URL.
     * Will cache XML data to disk.
     *
     * @return SimpleXMLElement or NULL if URL is unreachable.
     */
    private function getURLXML()
    {
        if ($data = @file_get_contents($this->url)) {
            try {
                $xml = new \SimpleXMLElement($data);
                file_put_contents($this->getCacheFilename(), $data);
                return $xml;
            } catch (Exception $e) {
                return NULL;
            }
        }
    }

    /**
     * Name of the cache file for current URL.
     *
     * @return string
     */
    private function getCacheFilename()
    {
        return sys_get_temp_dir() . '/' . md5($this->url) . '.feed.cache';
    }
}