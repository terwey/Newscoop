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
        "date_created" => null,
        "creator_literal" => null,
        "creator_name" => null,
        "slug_line" => null,
        "head_line" => null,
        "item_link" => null,
        "content" => null,
    );

    private $subjects = array();

    // setting the data
    public function setCopyright($_copyrightInfo) {
        $this->required_data["copyright_info"] = $_copyrightInfo;
    }

    public function setCreated($p_date = null, $p_time = null, $p_zone = null) {
        $date_time = "";

        if (!$p_date) {
            $date_time = gmdate("M-d-Y\TH:i:s") . "+00:00";
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

        $this->required_data["date_created"] = $date_time;
    }

    public function setCreator($p_literal, $p_name) {
        $this->required_data["creator_literal"] = $p_literal;
        $this->required_data["creator_name"] = $p_name;
    }

    public function setSlugline($p_slugLine) {
        $this->required_data["slug_line"] = $p_slugLine;
    }

    public function setHeadline($p_headLine) {
        $this->required_data["head_line"] = $p_headLine;
    }

    public function setLink($p_linkUrl) {
        $this->required_data["item_link"] = $p_linkUrl;
    }

    public function setContent($p_text) {
        $this->required_data["content"] = $p_text;
    }

    public function setSubject($p_qcode, $p_name) {
        $this->subjects[] = array("qcode" => $p_qcode, "name" => $p_name);
    }

    public function isFilled() {
        foreach ($this->required_data as $one_part => $one_info) {
            if (is_null($one_info)) {
                #echo "$one_part is $one_info!\n";
                return false;
            }
        }

        return true;

    } // fn isFilled


    public function getDate() {
        return $this->required_data["date_created"];
    }
    public function getHeadline() {
        return $this->required_data["head_line"];
    }
    public function getSlugline() {
        return $this->required_data["slug_line"];
    }
    public function getCopyright() {
        return $this->required_data["copyright_info"];
    }
    public function getCreator($p_part) {
        switch ($p_part) {
            case "literal":
                return $this->required_data["creator_literal"];
            case "name":
                return $this->required_data["creator_name"];
            default:
                return "";
        }
    }
    public function getLink() {
        return $this->required_data["item_link"];
    }
    public function getSubjects() {
        return $this->subjects;
    }
    public function getContent() {
        return $this->required_data["content"];
    }

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

    public function NewsMLCreator($p_outputFileName) {
        $this->outputName = $p_outputFileName;
    }

    public function setError($p_message) {
        $this->state_correct = false;
        $this->error_message = $p_message;
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
            #echo "item not filled\n";
            return false;
        }

        #echo "item inserted\n";
        $this->itemSet[] = $p_newsItem;

        return true;
    } // fn appendItem

    /**
     * Final operation for outputting
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

        // TODO: to create the newsml file
        $curdate = gmdate("M-d-Y\TH:i:s") . "+00:00";

        $newsml_content = '';

        $newsml_content .= '<?xml version="1.0" encoding="UTF-8" ?>
<newsMessage xmlns="http://iptc.org/std/nar/2006-10-01/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
        ../XSD/NewsML-G2/2.7/specification/NAR_1.8-spec-NewsMessage-Power.xsd">
    <header>
        <sent>2010-10-19T11:17:00.100Z</sent>
        <sender>sourcefabric.org</sender>
        <transmitId>tag:sourcefabric.org,2011:newsml_OVE48850O-PKG</transmitId>
        <priority>4</priority>
        <origin>MMS_3</origin>
        <destination>UKI</destination>
        <channel>TVS</channel>
        <channel>TTT</channel>
        <channel>WWW</channel>
        <timestamp role="received">' . $curdate . '2010-10-18T11:17:00.000Z</timestamp>
        <timestamp role="transmitted">' . $curdate . '2010-10-19T11:17:00.100Z</timestamp>
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
        <newsItem guid="urn:newsml:sourcefabric.org:' . $one_item->getDate() . ':' . $one_item->getSlugline() . '"';
            $newsml_content .= '
            xmlns="http://iptc.org/std/nar/2006-10-01/"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://iptc.org/std/nar/2006-10-01/
                ../specification/NAR_1.8-spec-All-Core.xsd"
            standard="NewsML-G2"
            standardversion="2.7"
            version="1"
            xml:lang="en-US"
            >
            <catalogRef
                href="http://www.iptc.org/std/catalog/catalog.IPTC-G2-Standards_13.xml" />
            <catalogRef href="http://www.sourcefabric.org/odis/catalogs/nmlcodes.xml" />
            <rightsInfo>';
            $newsml_content .= '
                <copyrightHolder literal="' . $one_item->getCopyright() . '" />';

            $newsml_content .= '
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
                    <name>' . $one_item->getCreator("name") . '</name>
                </creator>
                <language tag="en-US" />';
            foreach ($one_item->getSubjects() as $one_subjects) {
            $newsml_content .= '
                <subject qcode="' . $one_subjects["qcode"] . '">
                    <name>' . $one_subjects["name"] . '</name>
                </subject>';
            }
            $newsml_content .= '
                <link rel="itemrel:original" href="' . $one_item->getLink() . '" />
                <slugline>' . $one_item->getSlugline() . '</slugline>
                <headline>' . $one_item->getHeadline() . '</headline>
            </contentMeta>
            <contentSet>
                <inlineXML contenttype="text/html; charset=UTF-8">
' . $one_item->getContent() . '
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
