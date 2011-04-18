<?php

/**
 * simple data-flow example
 *
 *

    SomePlugin::makeImport($p_newsmlHolder, $p_inputFileName) {

        $item_holder = null;

        $input_file = fopen($p_inputFileName, "r");
        while(false !== ($line = fgets($input_file))) {
            switch($line) {
                "item_start":
                    if ($item_holder && $item_holder->isFilled()) {
                        $p_newsmlHolder->appendItem($item_holder);
                    }
                    $item_holder = $p_newsmlHolder->createItem();
                    break;
                "some":
                  $item_holder->setSome();
                    break;
                "another"
                    $item_holder->setAnother();
                    break;
                default:
                    break;
            }
        }
        fclose($input_file);

        if ($item_holder && $item_holder->isFilled()) {
            $p_newsmlHolder->appendItem($item_holder);
        }

        $p_newsmlHolder->serializeSet();

    } // fn makeImport

    SystemServer::importRunner($p_pluginName) {

        $newsml_holder = new NewsMLCreator();
        $plugin_instance = ImportPlugger::getPlugin($p_pluginName);

        $plugin_instance->makeImport($newsml_holder, $p_inputFileName);

    } // fn importRunner

 */


/**
 * Base class for the importer plugins
 */
class CMSImporterPlugin {

    /**
     * Makes the data import: reads data from p_newsmlHolder, parses data, and uses p_newsmlHolder for the formatting
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     */
    public function makeImport($p_newsmlHolder, $p_inputFileName) {}

} // class CMSImporterPlugin


/**
 * Holder of data of one newsItem
 */
class NewsMLNewsItem {
    // the data
    private $required_data = array(
        "copyright_info" => null,
        "date_created" = null,
        "creator_literal" = null,
        "creator_name" = null,
        "slug_line" = null,
        "head_line" = null,
        "item_link" = null,
        "content" => null,
    );

    private $subjects = array();

    // setting the data
    public function setCopyright($_copyrightInfo) {
        $this->$required_data["copyright_info"] = $_copyrightInfo;
    }

    public function setCreated($p_date = null, $p_time = null, $p_zone = null) {
        $date_time = "";

        if (!$p_date) {
            $date_time = gmdate("M-d-Y\TH:i:s" . "+00:00");
        }
        elseif (!$p_time) {
            $date_time = $p_date . "T00:00:00+00:00";
        }
        else if (!$p_zone) {
            $date_time = $p_date . "T" . $p_time . "+00:00";
        }
        else {
            $date_time = $p_date . "T" . $p_time . $p_zone;
        }

        $this->$required_data["date_created"] = $p_date;
    }

    public function setCreator($p_literal, $p_name) {
        $this->$required_data["creator_literal"] = $p_literal;
        $this->$required_data["creator_name"] = $p_name;
    }

    public function setSlugline($p_slugLine) {
        $this->$required_data["slug_line"] = $p_slugLine;
    }

    public function setHeadline($p_headLine) {
        $this->$required_data["head_line"] = $p_headLine;
    }

    public function setLink($p_linkUrl) {
        $this->$required_data["item_link"] = $p_linkUrl;
    }

    public function setContent($p_text) {
        $this->$required_data["content"] = $p_text;
    }

    public function setSubject($p_qcode, $p_name) {
        $this->$subjects[] = ("qcode" => $p_qcode, "name" => $p_name);
    }

    public function isFilled() {
        foreach ($this->$required_data as $one_info) {
            if (is_null($one_info)) {
                return false;
            }
        }

        return true;

    } // fn isFilled

} // class NewsMLNewsItem


/**
 * Holder and formatter of all the creted NewsML data (i.e. of newsMessage)
 */
class NewsMLCreator {

    private $state_correct = true;
    private $error_message = "";

    // for the header, id, itemRef-list
    private $overall_info;

    // for the created news items
    private $itemSet = array();

    // for the result output holder
    private $outputName = "";

    public function setError($p_message) {
        $this->$state_correct = false;
        $this->$error_message = $p_message;
    }

    /**
     * Creates a new newsItem
     */
    public function createItem() {
        return new NewsMLNewsItem();
    } // fn createItem

    /**
     * Adds a filled newsItem to the itemSet
     *
     * @param NewsMLNewsItem $p_newsItem a filled newsItem
     */
    public function appendItem(NewsMLNewsItem $p_newsItem) {
        if (!$p_newsItem->isFilled()) {
            return false;
        }

        $this->$itemSet[] = $p_newsItem;

        return true;
    } // fn appendItem

    /**
     * Final operation for outputting
     */
    public function serializeSet() {
        $out_file = fopen($this->$outputName, "w");
        if (!$out_file) {
            return false;
        }

        if (!$this->$state_correct) {
            fwrite($out_file, $this->$error_message);
            fclose($out_file);
            return true;
        }

        // TODO: to create the newsml file


        return false;
    } // fn serializeSet

} // class NewsMLCreator


?>
