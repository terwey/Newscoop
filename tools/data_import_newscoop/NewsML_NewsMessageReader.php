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

    public function __construct(\SimpleXMLElement $root)
    {
        $this->root = $root;
    }

    public function count()
    {
        if ((!$this->root) || (!$this->root->packageItem) || (!$this->root->packageItem->itemRef)) {
            return NULL;
        }

        return count($this->root->packageItem->itemRef);
    }

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

        // var_dump($node); exit;
        // return $node;
        //return (object)array(
        //    'guid' => (string) $node->attributes()->guid,
        //);
    }

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
            // echo json_encode($subjects_holder) . "\n\n";
        }

/*
        // walking over news messages
        foreach ($news_items as $one_news) {
            if ((!$one_news->contentMeta) || (!$one_news->contentMeta->subject)) {
                continue;
            }

            // walking over subjects of one message
            foreach ($one_news->contentMeta->subject as $one_subj) {
                // what is type of this subject
                $attrs = $one_subj->attributes();
                $attrs_qcode = $attrs["qcode"];
                if (empty($attrs_qcode)) {
                    continue;
                }

                $attrs_arr = explode(":", (string) $attrs_qcode);
                if (2 != count($attrs_arr)) {
                    continue;
                }

                $attrs_con = strtolower($attrs_arr[0]); // connection type of the type
                // what is the subject connectivity type
                $conn_type = "plain";
                if (array_key_exists($attrs_con, $known_conns)) {
                    $conn_type = $known_conns[$attrs_con];
                }

                $attrs_dsc = $attrs_arr[1];
                $attrs_cms = "__default"; // (default) cms type of the subject
                $attrs_lit = $attrs_dsc; // slug name of the subject (if w/o name)
                $attrs_dsc_arr = explode("#", (string) $attrs_dsc);
                if (2 == count($attrs_dsc_arr)) {
                    $attrs_cms = $attrs_dsc_arr[0]; // cms type of the subject
                    $attrs_lit = $attrs_dsc_arr[1]; // slug name of the subject
                }

                $one_name = (string) $one_subj->name;

                // setting the subjects holder
                if (!array_key_exists($attrs_cms, $subjects_holder[$conn_type])) {
                    $subjects_holder[$conn_type][$attrs_cms] = array("nodes" => array());
                }

                $subj_nodes = &$subjects_holder[$conn_type][$attrs_cms]["nodes"];

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
                    if ($p_sort) {
                        ksort($tree_part, SORT_STRING);
                    }
                    $tree_part = &$tree_part[$one_cat]["nodes"];
                }
            }
        }
*/

        if ($p_sort) {
            foreach ($subjects_holder["plain"] as $subj_name => $subj_vals) {
                ksort($subjects_holder["plain"][$subj_name]["nodes"], SORT_STRING);
            }
        }

        //return $subjects_holder;
        return true;
    } // fn getAttributeSet






}
