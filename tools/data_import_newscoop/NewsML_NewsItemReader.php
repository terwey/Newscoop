<?php
/**
 * @package Newscoop
 *
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

namespace NewsML;

require_once('IFeedReader.php');
require_once('IFeedNews.php');

/**
 * NewsML NewsItem Reader class
 */
class NewsML_NewsItemReader implements IFeedCommon, IFeedReader, IFeedNews
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
     * Returns 1 since this is a single message.
     *
     * @return int
     */
    public function count()
    {
        return 1;
    }

    /**
     * Returns 1 (if the message is a real news) or 0 (for a linked media).
     *
     * @return int
     */
    public function countNews()
    {
        if ($this->isNews()) {
            return 1;
        }
        return 0;
    }

    /**
     * Provides itself, i.e. reader of its news.
     *
     * @return mixed
     */
    public function item($index)
    {
        if (0 !== $index) {
            return null;
        }

        return $this;

    }

    /**
     * Whether objects of this class can be used for reading the given message.
     *
     * @return boolean
     */
    public static function CanRead(\SimpleXMLElement $root)
    {
        return $root->getName() == 'newsItem';
    }

    /**
     * Subjects of the given message feed.
     *
     * @return mixed
     */
    public function getAttributes(&$p_subjectsHolder, $p_sort = true)
    {
        if ((!$this->root->contentMeta) || (!$this->root->contentMeta->subject)) {
            return false;
        }

        $known_conns = array('path' => 'tree', 'item' => 'plain'); // 'path' subjs form a tree, 'item' subjs form a plain structure
        // holder of the results
        $subjects_holder = &$p_subjectsHolder;
        if (empty($subjects_holder)) {
            $subjects_holder = array('tree' => array(), 'plain' => array());
        }

        // walking over subjects of one message
        foreach ($this->root->contentMeta->subject as $one_subj) {
            // what is type of this subject
            $attrs = $one_subj->attributes();
            $attrs_qcode = $attrs['qcode'];
            if (empty($attrs_qcode)) {
                continue;
            }

            $attrs_arr = explode(":", (string) $attrs_qcode);
            if (2 != count($attrs_arr)) {
                continue;
            }

            $attrs_con = strtolower($attrs_arr[0]); // connection type of the type
            // what is the subject connectivity type
            $conn_type = 'plain';
            if (array_key_exists($attrs_con, $known_conns)) {
                $conn_type = $known_conns[$attrs_con];
            }

            $attrs_dsc = $attrs_arr[1];
            $attrs_cms = '__default'; // (default) cms type of the subject
            $attrs_lit = $attrs_dsc; // slug name of the subject (if w/o name)
            $attrs_dsc_arr = explode('//', (string) $attrs_dsc);
            if (2 == count($attrs_dsc_arr)) {
                $attrs_cms = $attrs_dsc_arr[0]; // cms type of the subject
                $attrs_lit = $attrs_dsc_arr[1]; // slug name of the subject
            }

            $one_name = (string) $one_subj->name;

            // setting the subjects holder
            if (!array_key_exists($attrs_cms, $subjects_holder[$conn_type])) {
                $subjects_holder[$conn_type][$attrs_cms] = array('nodes' => array());
            }

            $subj_nodes = &$subjects_holder[$conn_type][$attrs_cms]['nodes'];

            // plain subjects
            if ('plain' == $conn_type) {
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
            $attrs_sec_arr = explode('/', $attrs_lit);
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
                    if (!$tree_part[$one_cat]['name']) {
                        $tree_part[$one_cat]['name'] = $cur_name;
                    }

                    $tree_part = &$tree_part[$one_cat]['nodes'];
                    continue;
                }
                $tree_part[$one_cat] = array('name' => $cur_name, 'nodes' => array());
                ksort($tree_part, SORT_STRING);
                $tree_part = &$tree_part[$one_cat]['nodes'];
            }
        }

        return true;
    }

    /**
     * Provides slugline info of the message.
     *
     * @return string
     */
    public function getSlugLine() {
        if ((!$this->root) || (!$this->root->contentMeta)) {
            return null;
        }

        return $this->root->contentMeta->slugline;
    }

    /**
     * Provides headline info of the message.
     *
     * @return string
     */
    public function getHeadLine() {
        if ((!$this->root) || (!$this->root->contentMeta)) {
            return null;
        }

        return $this->root->contentMeta->headline;
    }

    /**
     * Provides texts (if any) from the content of the news.
     *
     * @return mixed
     */
    public function getContentTexts() {
        if ((!$this->root) || (!$this->root->contentSet) || (!$this->root->contentSet->inlineXML)) {
            return null;
        }

        $loc_texts = array();

        $loc_content = $this->root->contentSet->inlineXML;
        foreach ($loc_content as $one_local) {
            $loc_texts[] = (string) $one_local;
        }

        return $loc_texts;
    }

    /**
     * Provides original link of the message.
     *
     * @return string
     */
    public function getLink() {
        if ((!$this->root) || (!$this->root->contentMeta) || (!$this->root->contentMeta->link)) {
            return null;
        }

        $links = $this->root->contentMeta->link;
        if (!is_array($links)) {
            $links = array($links);
        }
        foreach ($links as $one_link) {
            $attrs = $one_link->attributes();
            if ("itemrel:original" != strtolower((string) $attrs['rel'])) {
                continue;
            }
            return (string) $attrs['href'];
        }

        return null;
    }

    /**
     * Provides creator info of the message.
     *
     * @return string
     */
    public function getCreator() {
        if ((!$this->root) || (!$this->root->contentMeta) || (!$this->root->contentMeta->creator)) {
            return null;
        }

        return $this->root->contentMeta->creator->name;
    }

    /**
     * Provides service info of the message.
     *
     * @return string
     */
    public function getService() {
        if ((!$this->root) || (!$this->root->itemMeta) || (!$this->root->itemMeta->service)) {
            return null;
        }

        return $this->root->itemMeta->service->name;
    }

    /**
     * Provides rights info of the message.
     *
     * @return string
     */
    public function getCopyright() {
        if ((!$this->root) || (!$this->root->rightsInfo) || (!$this->root->rightsInfo->copyrightHolder)) {
            return null;
        }

        $copyright = $this->root->rightsInfo->copyrightHolder;
        $attrs = $copyright->attributes();

        return $attrs['literal'];
    }

    /**
     * Provides GUID of the message.
     *
     * @return string
     */
    public function getNewsID() {
        if (!$this->root) {
            return null;
        }

        $attrs = $this->root->attributes();
        if (empty($attrs)) {
            return null;
        }

        return (string) $attrs->guid;
    }

    /**
     * Provides title of the message.
     *
     * @return string
     */
    public function getTitle() { // non-empty usually just for images
        if ((!$this->root) || (!$this->root->itemMeta) || (!$this->root->itemMeta->title)) {
            return null;
        }

        return (string) $this->root->itemMeta->title;
    }

    /**
     * Provides IDs of items that this message requires.
     *
     * @return mixed
     */
    public function getDependencies() {
        if ((!$this->root) || (!$this->root->contentMeta) || (!$this->root->contentMeta->link)) {
            return null;
        }

        $dependencies = array();

        foreach ($this->root->contentMeta->link as $one_link) {
            $attrs = $one_link->attributes();
            if (strtolower("itemrel:dependsOn") == strtolower((string) $attrs['rel'])) {
                $residref = (string) $attrs['residref'];
                if (!empty($residref)) {
                    $dependencies[] = $residref;
                }
            }
        }

        return $dependencies;
    }

    /**
     * Provides images (if any) from the content of the news.
     * It does not take linked images; use getDependencies and their methods for that.
     *
     * @return mixed
     */
    public function getContentImages() {
        if ((!$this->root) || (!$this->root->contentSet) || (!$this->root->contentSet->remoteContent)) {
            return null;
        }

        $rem_images = array();

        $rem_content = $this->root->contentSet->remoteContent;
        foreach ($rem_content as $one_remote) {
            $attrs = $one_remote->attributes();
            $one_cont_type = (string) $attrs['contenttype'];
            if ("image/" != strtolower(substr($one_cont_type, 0, strlen("image/")))) {
                continue;
            }
            $one_image = array(
                "type" => $one_cont_type,
                "href" => (string) $attrs['href'],
                "width" => (string) $attrs['width'],
                "height" => (string) $attrs['height'],
                "version" => (string) $attrs['version'],
            );
            $rem_images[] = $one_image;
        }

        return $rem_images;
    }

    /**
     * Is it a real message, or just a dependency content (e.g. an image of a news).
     *
     * @return boolean
     */
    public function isNews() {
        $guid = $this->getNewsID();
        if (!$guid) {
            return false;
        }

        if (false === strpos($guid, '$')) {
            return true;
        }

        return false;
    }

} // class NewsML_NewsItemReader
