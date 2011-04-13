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
    private $the_internal_info;

    // setting the data
    public function setCopyrightHolder() {}
    public function setContentCreated() {}
    public function setCreatorLiteral() {}
    public function setCreatorName() {}
    public function setSubjectQcode() {}
    public function setSubjectName() {}
    public function setLink() {}
    public function setSlugline() {}
    public function setHeadline() {}

    public function isFilled() {}

} // class NewsMLNewsItem


/**
 * Holder and formatter of all the creted NewsML data (i.e. of newsMessage)
 */
class NewsMLCreator {

    // for the header, id, itemRef-list
    private $overall_info;

    // for the created news items
    private $itemSet = array();

    // for the result output holder
    private $outputName = "";

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
        return false;
    } // fn appendItem

    /**
     * Final operation for outputting
     */
    public function serializeSet() {
        return false;
    } // fn serializeSet

} // class NewsMLCreator


?>
