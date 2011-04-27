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

    /**
     * Subjects of the given message feed node.
     *
     * @return mixed
     */
    public static function GetSubjects($node)
    {
        // can we load subjects from somewhere
        if (!$node) {
            return NULL;
        }
        $news_items = null;
        try {
            $news_items = $node->itemSet->newsItem;
        }
        catch (Exception $exc) {
            $news_items = null;
        }
        if (!$news_items) {
            return null;
        }

        // holder of the results
        $subjects_holder = array();
        $known_conns = array("path" => "tree", "item" => "plain"); // 'path' subjs form a tree, 'item' subjs form a plain structure
        // TODO: what if the same subject type has various connectivity types

        // walking over news messages
        foreach ($news_items as $one_news) {
            if (!$one_news->contentMeta->subject) {
                continue;
            }

            // walking over subjects of one message
            foreach ($one_news->contentMeta->subject as $one_subj) {
                // what is type of this subject
                $attrs = explode(":", (string) $one_subj->attributes());
                if (3 != count($attrs)) {
                    continue;
                }

                $attrs_cms = $attrs[0]; // cms type of the subject
                $attrs_con = $attrs[1]; // connection type of the type
                $attrs_lit = $attrs[2]; // slug name of the subject

                $one_name = (string) $one_subj->name;

                // what is the subject connectivity type
                $conn_type = "plain";
                if (array_key_exists($attrs_con, $known_conns)) {
                    $conn_type = $known_conns[$attrs_con];
                }

                // setting the subjects holder
                if (!array_key_exists($attrs_cms, $subjects_holder)) {
                    $subjects_holder[$attrs_cms] = array("type" => $conn_type, "nodes" => array());
                }

                $subj_nodes = &$subjects_holder[$attrs_cms]["nodes"];

                // plain subjects
                if ("plain" == $conn_type) {
                    if (!array_key_exists($attrs_lit, $subj_nodes)) {
                        $subj_nodes[$attrs_lit] = $one_name;
                    }
                    continue;
                }

                // tree subjects
                // trying to take cat (outer) names from json
                $one_name_arr = null;
                try {
                    $one_name_arr = json_decode($one_name);
                }
                catch (Exception $exc) {
                    $one_name_arr = null;
                }

                // taking literal (inner) names
                $attrs_sec_arr = explode("/", $attrs_lit);
                $attrs_sec_names = array();

                // do inner and outer names fit together
                $path_len = count($attrs_sec_arr);
                $paths_fit = false;
                if (is_array($one_name_arr) && (count($one_name_arr) == $path_len)) {
                    $paths_fit = true;
                }

                // put outer names along inner names of the current path
                for ($ind = 0; $ind < $path_len; $ind++) {
                    $one_name_part = null;
                    if ($paths_fit && is_string($one_name_arr[$ind])) {
                        $one_name_part = $one_name_arr[$ind];
                    }
                    $attrs_sec_names[] = $one_name_part;
                }

                if (!$paths_fit) {
                    $attrs_sec_names[$path_len - 1] = $one_name;
                }

                // put the current path names into the tree of overall categories
                $tree_depth = -1;
                $tree_part = &$subj_nodes;
                foreach ($attrs_sec_arr as $one_cat) {
                    $tree_depth += 1;
                    $cur_name = $attrs_sec_names[$tree_depth];

                    if (array_key_exists($one_cat, $tree_part)) {
                        if (!$tree_part[$one_cat]["name"]) {
                            $tree_part[$one_cat]["name"] = $cur_name;
                        }

                        $tree_part = &$tree_part[$one_cat]["nodes"];
                        continue;
                    }
                    $tree_part[$one_cat] = array("name" => $cur_name, "nodes" => array());
                    ksort($tree_part, SORT_STRING);
                    $tree_part = &$tree_part[$one_cat]["nodes"];
                }
            }
        }

        foreach ($subjects_holder as $subj_name => $subj_vals) {
            if ("plain" == $subj_vals["type"]) {
                ksort($subjects_holder[$subj_name]["nodes"], SORT_STRING);
            }
        }

        return $subjects_holder;
    } // fn GetSubjects

}
