<?php

/**
 * simple data-flow example
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
     * Makes the data import: parses data from $p_inputFileName, and uses p_newsmlHolder for the formatting
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     * @return bool
     */
    public function makeImport($p_newsmlHolder, $p_inputFileName) {
        return false;
    }

} // class CMSImporterPlugin


/**
 * Holder of data of one newsItem
 */
class NewsMLNewsItem {
    // Note that we have some data as cdata, and embedded cdata shall be included correctly, see link
    // http://stackoverflow.com/questions/223652/is-there-a-way-to-escape-a-cdata-end-token-in-xml

    // the data that are required for newsml
    private $required_data = array(
        "copyright_info" => null,
        "date_created" => null,
        "creator_literal" => null,
        "creator_name" => null,
        "slug_line" => null,
        "head_line" => null,
        "item_link" => null,
        "content" => null,
    );

    // specifiers like categories, tags, ...
    private $subjects = array();

    // setter methods

    /**
     * Setting the copyright data
     * @param string $p_copyrightInfo copyright holder
     * @return bool
     */
    public function setCopyright($p_copyrightInfo) {
        $this->required_data["copyright_info"] = str_replace(array("\""), array("&#34;"), $p_copyrightInfo); // shall not escape "..."
        return true;
    } // fn setCopyright

    /**
     * Setting the date-time data
     * @param string $p_date
     * @param string $p_time
     * @param string $p_zone
     * @return bool
     *
     * the xml date-time format is according to the link
     * http://www.w3schools.com/Schema/schema_dtypes_date.asp
     */
    public function setCreated($p_date = null, $p_time = null, $p_zone = null) {
        $date_time = "";

        if ($p_date) {
            $p_date = (string) $p_date;
            if (!preg_match("/^[\d]{4}-[\d]{2}-[\d]{2}$/", $p_date)) {
                return false;
            }
        }
        if ($p_time) {
            $p_time = (string) $p_time;
            if (!preg_match("/^[\d]{2}(:[\d]{2}(:[\d]{2}(\.[\d]+)?)?)?$/", $p_time)) {
                return false;
            }
        }
        if ($p_zone) {
            $p_zone = (string) $p_zone;
            $zone_correct = false;
            if ("Z" == $p_zone) {
                $zone_correct = true;
            }
            if (preg_match("/^[+-]{1}[\d]{2}(:[\d]{2})?$/", $p_zone)) {
                $zone_correct = true;
            }
            if (!$zone_correct) {
                return false;
            }
        }

        if (!$p_date) {
            $date_time = gmdate("Y-m-d\TH:i:s\Z");
        }
        elseif (!$p_time) {
            $date_time = $p_date . "T00:00:00Z";
        }
        else if (!$p_zone) {
            $date_time = $p_date . "T" . $p_time . "Z";
        }
        else {
            $date_time = $p_date . "T" . $p_time . $p_zone;
        }

        $this->required_data["date_created"] = $date_time;

        return true;
    } // fn setCreated

    /**
     * Setting the creator data
     * @param string $p_literal
     * @param string $p_name
     * @return bool
     */
    public function setCreator($p_literal, $p_name) {
        $this->required_data["creator_literal"] = str_replace(array("\""), array("&#34;"), $p_literal); // shall not escape "..."
        //$this->required_data["creator_name"] = str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_name); // shall not have cdata
        $this->required_data["creator_name"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_name); // shall not have cdata
        return true;
    } // fn setCreator

    /**
     * Setting the slugline
     * @param string $p_slugline
     * @return bool
     */
    public function setSlugline($p_slugLine) {
        $this->required_data["slug_line"] = str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&#34;"), $p_slugLine); // shall not have <tag>, shall not escape "..."
        return true;
    } // fn setSlugline

    /**
     * Setting the headline
     * @param string $p_headline
     * @return bool
     */
    public function setHeadline($p_headLine) {
        //$this->required_data["head_line"] = str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_headLine); // shall not have cdata seps
        $this->required_data["head_line"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_headLine); // shall not have cdata seps
        return true;
    } // fn setHeadline

    /**
     * Setting the link
     * @param string $p_link
     * @return bool
     */
    public function setLink($p_linkUrl) {
        $this->required_data["item_link"] = str_replace(array("\""), array("&#34;"), $p_linkUrl); // shall not escape "..."
        return true;
    } // fn setLink

    /**
     * Setting the article content by itself
     * @param string $p_text
     * @return bool
     */
    public function setContent($p_text) {
        $this->required_data["content"] = str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_text); // shall not have cdata seps
        return true;
    } // fn setContent

    /**
     * Setting the article content by itself
     * @param string $p_qcode
     * @param string $p_name
     * @return bool
     */
    public function setSubject($p_qcode, $p_name) {
        $this->subjects[] = array("qcode" => str_replace(array("\""), array("&#34;"), $p_qcode), // shall not escape "..."
                                  //"name" => str_replace(array("[", "]"), array("&#91;", "&#93;"), $p_name), // shall not have cdata seps
                                  "name" => str_replace(array("]]>"), array("]]]]><![CDATA[>"), $p_name), // shall not have cdata seps
                                 );
        return true;
    } // fn setSubject

    // checker methods

    /**
     * Checks whether all required data are set
     * @return bool
     */
    public function isFilled() {
        foreach ($this->required_data as $one_info) {
            if (is_null($one_info)) {
                return false;
            }
        }

        return true;

    } // fn isFilled

    // getter methods

    /**
     * Gets item date
     * @return string
     */
    public function getDate() {
        return $this->required_data["date_created"];
    } // fn getDate

    /**
     * Gets item headline
     * @return string
     */
    public function getHeadline() {
        return $this->required_data["head_line"];
    } // fn getHeadline

    /**
     * Gets item slugline
     * @return string
     */
    public function getSlugline() {
        return $this->required_data["slug_line"];
    } // fn getSlugline

    /**
     * Gets item copyright
     * @return string
     */
    public function getCopyright() {
        return $this->required_data["copyright_info"];
    } // fn getCopyright

    /**
     * Gets item creator
     * @return string
     */
    public function getCreator($p_part) {
        switch ($p_part) {
            case "literal":
                return $this->required_data["creator_literal"];
            case "name":
                return $this->required_data["creator_name"];
            default:
                return "";
        }
    } // fn getCreator

    /**
     * Gets item link
     * @return string
     */
    public function getLink() {
        return $this->required_data["item_link"];
    } // getLink

    /**
     * Gets item subjects (array of categories, tags, ...)
     * @return array
     */
    public function getSubjects() {
        return $this->subjects;
    } // fn getSubjects

    /**
     * Gets item content
     * @return string
     */
    public function getContent() {
        return $this->required_data["content"];
    } // fn getContent

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

    /**
     * Constructor
     *
     * @param string p_outputFileName output file name
     */
    public function __construct($p_outputFileName) {
        $this->outputName = $p_outputFileName;
    } // fn NewsMLCreator

    /**
     * Sets an error state
     *
     * @param string $p_message
     * @return void
     */
    public function setError($p_message) {
        $this->state_correct = false;
        $this->error_message = $p_message;
    } // fn setError

    /**
     * Creates a new newsItem
     *
     * @return mixed
     */
    public function createItem() {
        return new NewsMLNewsItem();
    } // fn createItem

    /**
     * Adds a filled newsItem to the itemSet
     *
     * @param NewsMLNewsItem $p_newsItem a filled newsItem
     * @return bool
     */
    public function appendItem(NewsMLNewsItem $p_newsItem) {
        if (!$p_newsItem->isFilled()) {
            return false;
        }

        $this->itemSet[] = $p_newsItem;

        return true;
    } // fn appendItem

    /**
     * Final operation for outputting
     *
     * @return bool
     */
    public function serializeSet() {
        $out_file = fopen($this->outputName, "w");
        if (!$out_file) {
            return false;
        }

        if (!$this->state_correct) {
            fwrite($out_file, $this->error_message);
            fclose($out_file);
            return true;
        }

        $curdate = gmdate("Y-m-d\TH:i:s\Z");

        $newsml_content = '';

        // newsMessage beginning according to Ch16, IPTC-G2-Implementation_Guide
        $newsml_content .= '<?xml version="1.0" encoding="UTF-8" ?>
<newsMessage xmlns="http://iptc.org/std/nar/2006-10-01/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
        ../XSD/NewsML-G2/2.7/specification/NAR_1.8-spec-NewsMessage-Power.xsd">
    <header>
        <sent>' . $curdate . '</sent>
        <sender>sourcefabric.org</sender>
        <transmitId>sourcefabric.org:' . $curdate . '</transmitId>
    </header>
    <packageItem>
';

        foreach ($this->itemSet as $one_item) {
            $newsml_content .= '        <itemRef residref="urn:newsml:sourcefabric.org:' . $one_item->getDate() . ':' . $one_item->getSlugline() . '" />' . "\n";
        }

        $newsml_content .= '    </packageItem>
    <itemSet>';

        foreach ($this->itemSet as $one_item) {
            $newsml_content .= '
        <newsItem guid="urn:newsml:sourcefabric.org:' . $one_item->getDate() . ':' . $one_item->getSlugline() . '"
            xmlns="http://iptc.org/std/nar/2006-10-01/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
                ../specification/NAR_1.8-spec-All-Core.xsd"
            standard="NewsML-G2"
            standardversion="2.7"
            version="1"
            xml:lang="en-US"
            >
            <catalogRef href="http://www.iptc.org/std/catalog/catalog.IPTC-G2-Standards_13.xml" />
            <catalogRef href="http://www.sourcefabric.org/odis/catalogs/nmlcodes.xml" />
            <rightsInfo>
                <copyrightHolder literal="' . $one_item->getCopyright() . '" />
            </rightsInfo>
            <itemMeta>
                <itemClass qcode="ninat:text" />
                <provider literal="Newscoop Online Data Import Service" />
                <versionCreated>' . $one_item->getDate() . '</versionCreated>
                <pubStatus qcode="stat:usable" />
                <service qcode="svc:WPI">
                    <name>Word Press Import</name>
                </service>
            </itemMeta>
            <contentMeta>
                <contentCreated>' . $one_item->getDate() . '</contentCreated>
                <contentModified></contentModified>
                <creator literal="' . $one_item->getCreator("literal") . '">
                    <name><![CDATA[' . $one_item->getCreator("name") . ']]></name>
                </creator>
                <language tag="en-US" />';
            foreach ($one_item->getSubjects() as $one_subjects) {
            $newsml_content .= '
                <subject qcode="' . $one_subjects["qcode"] . '">
                    <name><![CDATA[' . $one_subjects["name"] . ']]></name>
                </subject>';
            }
            $newsml_content .= '
                <link rel="itemrel:original" href="' . $one_item->getLink() . '" />
                <slugline>' . $one_item->getSlugline() . '</slugline>
                <headline><![CDATA[' . $one_item->getHeadline() . ']]></headline>
            </contentMeta>
            <contentSet>
                <inlineXML contenttype="application/xhtml+xml; charset=UTF-8">
<![CDATA[
' . $one_item->getContent() . '
]]>
                </inlineXML>
            </contentSet>
        </newsItem>';

        }

        $newsml_content .= '
    </itemSet>
</newsMessage>
';

        fwrite($out_file, $newsml_content);
        fclose($out_file);

        return false;
    } // fn serializeSet

} // class NewsMLCreator


?>
