<?php
/*
    The data read / parsing is done by WordPress importer
    http://wordpress.org/extend/plugins/wordpress-importer/
    licensed under GPL2+, thus it should be ok to use it

    the data parser is from file
    http://svn.wp-plugins.org/wordpress-importer/trunk/parsers.php
    it is named as WordPressParsers.php herein

    the data importer goes along
    http://svn.wp-plugins.org/wordpress-importer/trunk/wordpress-importer.php
*/

require_once('WordPressParsers.php');

class WordPressImporter extends CMSImporterPlugin {

    /**
     * Makes the import from parsed data (by WXR_Parser) via the NewsMLCreator object
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     */
    public function makeImport($p_newsmlHolder, $p_inputFileName) {

        $parser = new WXR_Parser();
        $import_data = $parser->parse($p_inputFileName);

        $file_processed = true;
        if (!$import_data) {
            p_newsmlHolder->setError("file processing errors");
            $file_processed = false;
        }
        if (!$import_data["correct"]) {
            p_newsmlHolder->setError($import_data["errormsg"]);
            $file_processed = false;
        }

        if (!$file_processed) {
            $p_newsmlHolder->serializeSet();
            return false;
        }

        $copyright_info = "" . $import_data["title"] . " - " . $import_data["link"];

        foreach ($import_data["posts"] as $one_post) {
            $item_holder = $p_newsmlHolder->createItem();
            $item_holder->setCreated();
            $item_holder->setCopyright($copyright_info);

            $author_name = $one_post["post_author"];
            if (array_key_exists($one_post["post_author"], $import_data["authors"])) {
                $author_name = $import_data["authors"][$one_post["post_author"]]["author_display_name"];
            }

            $item_holder->setCreator($one_post["post_author"], $author_name);
            $item_holder->setHeadline($one_post["post_title"]);
            $item_holder->setSlugline($one_post["post_name"]);
            $item_holder->setLink($one_post["guid"]);
            $item_holder->setContent($one_post["post_content"]);
            foreach ($one_post["terms"] as $one_term) {
                if ("category" == $one_term["domain"]) {
                    $item_holder->setSubject("WPCat:" . $one_term["slug"], htmlspecialchars($one_term["name"]));
                }
                if ("post_tag" == $one_term["domain"]) {
                    $item_holder->setSubject("WPTag:" . $one_term["slug"], htmlspecialchars($one_term["name"]));
                }
            }

            $p_newsmlHolder->appendItem($item_holder);
        }

        $p_newsmlHolder->serializeSet();
        return true;
    } // fn makeImport

} // class WordPressImporter

?>
