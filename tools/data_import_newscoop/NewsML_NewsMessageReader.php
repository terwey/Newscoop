<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

namespace NewsML;

require_once('IFeedCommon.php');
require_once('IFeedReader.php');
require_once('NewsML_NewsItemReader.php');

/**
 * NewsML NewsMessage Reader class
 */
class NewsML_NewsMessageReader implements IFeedCommon, IFeedReader
{
    /** @var SimpleXMLElement */
    private $root;

    /**
     * Constructor, requires xml message.
     */
    public function __construct(\SimpleXMLElement $root)
    {
        $this->root = $root;
    }

    /**
     * Returns count of all messages, including thos which are just linked media.
     *
     * @return int
     */
    public function count()
    {
        if ((!$this->root) || (!$this->root->itemSet) || (!$this->root->itemSet->packageItem) || (!$this->root->itemSet->packageItem->itemRef)) {
            return NULL;
        }

        return count($this->root->itemSet->packageItem->itemRef);
    }

    /**
     * Returns count of real messages, i.e. it omits items of linked media.
     *
     * @return int
     */
    public function countNews()
    {
        if ((!$this->root) || (!$this->root->itemSet) || (!$this->root->itemSet->packageItem) || (!$this->root->itemSet->packageItem->itemRef)) {
            return NULL;
        }

        $item_count = 0;
        foreach ($this->root->itemSet->packageItem->itemRef as $one_guid) {
            $attrs = $one_guid->attributes();
            if (false === strpos($attrs['residref'], '$')) { // newsItems with '$' at their guids are taken as assets, i.e. images, ...
                $item_count += 1;
            }
        }

        return $item_count; // just the real news items are counted here
    }

    /**
     * Provides specified reader of specified news item.
     *
     * @return mixed
     */
    public function item($index)
    {
        $root = $this->root;
        if ((!$root) || (!$root->itemSet) || (!$root->itemSet->newsItem)) {
            return NULL;
        }

        if (empty($root->itemSet->newsItem[$index])) {
            return NULL;
        }

        if (NewsML_NewsItemReader::CanRead($root->itemSet->newsItem[$index])) {
            return new NewsML_NewsItemReader($root->itemSet->newsItem[$index]);
        }

        return null;
    }

    /**
     * Provides header of the message.
     *
     * @return mixed
     */
    public function getHeader()
    {
        return $this->root->header;
    }

    public static function CanRead(\SimpleXMLElement $root)
    {
        return $root->getName() == 'newsMessage';
    }

    /**
     * Subjects of the given message feed.
     *
     * @return mixed
     */
    public function getAttributes(&$p_subjectsHolder, $p_sort = true)
    {
        $node = $this->root;

        // can we load subjects from somewhere
        if (!$node) {
            return false;
        }
        $news_items = false;
        try {
            $news_items = $node->itemSet->newsItem;
        }
        catch (Exception $exc) {
            $news_items = false;
        }
        if (!$news_items) {
            return false;
        }

        $known_conns = array("path" => "tree", "item" => "plain"); // 'path' subjs form a tree, 'item' subjs form a plain structure
        // holder of the results
        $subjects_holder = &$p_subjectsHolder;
        if (empty($subjects_holder)) {
            $subjects_holder = array("tree" => array(), "plain" => array());
        }

        $news_count = $this->count();
        for ($nind = 0; $nind < $news_count; $nind++) {
            $one_news = $this->item($nind);
            $one_news->getAttributes($subjects_holder, false);
        }

        if ($p_sort) {
            foreach ($subjects_holder["plain"] as $subj_name => $subj_vals) {
                ksort($subjects_holder["plain"][$subj_name]["nodes"], SORT_STRING);
            }
        }

        return true;
    } // fn getAttributeSet


} // class NewsML_NewsMessageReader
