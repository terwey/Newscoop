<?php
/**
    The data read / parsing is done by WordPress importer
    http://wordpress.org/extend/plugins/wordpress-importer/
    licensed under GPL2+, thus it should be ok to use it

    the data parser is from file
    http://svn.wp-plugins.org/wordpress-importer/trunk/parsers.php
    it is named as WordPressParsers.php herein

    the data importer goes along
    http://svn.wp-plugins.org/wordpress-importer/trunk/wordpress-importer.php

    Note that this does not run on some WXR files since WordPress creates non-valid XML if 'CDATA' part is at an article content (e.g. at javascript), see
    http://drupal.org/node/1055310
 */

require_once('WordPressParsers.php');

/**
 * Imports data from WordPress WXR file into NewsML file
 */
class WordPressImporter extends CMSImporterPlugin {

    /**
     * Makes the import from parsed data (by WXR_Parser) via the NewsMLCreator object
     *
     * @param NewsMLCreator $p_newsmlHolder the NewsML formatter
     * @param string $p_inputFileName input file name
     * @return bool
     */
    public function makeImport($p_newsmlHolder, $p_inputFileName) {

        $parser = new WXR_Parser();
        $import_data = $parser->parse($p_inputFileName);

        $file_processed = true;
        if (!$import_data) {
            $p_newsmlHolder->setError("file processing errors");
            $file_processed = false;
        }
        if (!$import_data["correct"]) {
            $p_newsmlHolder->setError($import_data["errormsg"]);
            $file_processed = false;
        }

        if (!$file_processed) {
            $p_newsmlHolder->serializeSet();
            return false;
        }

        $copyright_info = "" . $import_data["title"] . " - " . $import_data["link"];

        $categories_by_slug = $import_data["categories_by_slug"];

        foreach ($import_data["posts"] as $one_post) {
            $item_holder = $p_newsmlHolder->createItem();
            $item_holder->setCreated(); // can be set explicitely like ("1234-56-78", "11:22:33.000", "+01:00")
            $item_holder->setCopyright($copyright_info);

            $author_name = $one_post["post_author"];
            if (array_key_exists($one_post["post_author"], $import_data["authors"])) {
                $author_name = $import_data["authors"][$one_post["post_author"]]["author_display_name"];
            }

            $item_holder->setCreator($one_post["post_author"], $author_name);
            $item_holder->setHeadline($one_post["post_title"]);
            $item_holder->setSlugline($one_post["post_name"]);
            $item_holder->setLink($one_post["guid"]);
            //$item_holder->setContent($one_post["post_content"]);

            $item_pictures = array();
            $item_videos = array();

            $content_type = "text";
            $content_text = $one_post["post_content"];
            //echo trim($one_post["guid"]);
            $text_empty = false;
            if ("" == trim($content_text)) {
                $text_empty = true;
            }

            if (array_key_exists('attachment_url', $one_post)) {
                $item_pictures[] = $one_post['attachment_url'];
            }

            if ($text_empty) {
                //echo trim($one_post["guid"]);
                $guid_link = trim($one_post["guid"]);
                if (preg_match("/^(?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)$/i", $guid_link)) {
                //if (preg_match("/^http(?:s)?\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)$/i", trim($one_post["guid"]))) {
                    //$content_type = "picture";
                    if (!in_array($guid_link, $item_pictures)) {
                        $item_pictures[] = $guid_link;
                    }
                    //var_dump($item_pictures);
                }
            }

            if (!$text_empty) {
                //if (preg_match("/<img(?:[^<>]+)http(?:s)?\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)(?:[^<>]*)>/i", $content_text)) {
                //if (preg_match_all("/<img(?:[^<>]+)src=\"(http(?:s)?\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg))\"(?:[^<>]*)>/i", $content_text, $img_matches, PREG_SET_ORDER)) {
                if (preg_match_all("/<img(?:[^<>]+)src=\"((?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+)\"(?:[^<>]*)>/i", $content_text, $img_matches, PREG_PATTERN_ORDER)) {
                    //$content_type = "composite";
                    if (is_array($img_matches[1])) {
                        foreach ($img_matches[1] as $one_img) {
                            if (!in_array($one_img, $item_pictures)) {
                                $item_pictures[] = $one_img;
                            }
                        }
                        //echo "array of strings\n";
                    }
                    //else {
                    //    $item_pictures[] = $img_matches[0];
                    //    echo "just a string\n";
                    //}
                    //var_dump($img_matches);
                }
            }
            //var_dump($item_pictures);

            if (array_key_exists('postmeta', $one_post)) {
                $post_metas = $one_post['postmeta'];
                if (is_array($post_metas)) {
                    foreach ($post_metas as $one_pm) {
                        $pm_value = trim($one_pm['value']);
                        if (preg_match("/^(?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)$/i", $pm_value)) {
                            if (!in_array($pm_value, $item_pictures)) {
                                $item_pictures[] = $pm_value;
                                //echo $pm_value . "\n";
                            }
                        }
                    }
                }
            }

            $set_content_text = false;
            $content_text_trimmed = trim($content_text);
            if (!empty($content_text_trimmed)) {
                $set_content_text = true;
            }
            if (empty($item_pictures) && empty($item_videos)) {
                $set_content_text = true;
            }
            if ($set_content_text) {
                $item_holder->setContent("text", $contnet_text);
            }

            if (!empty($item_pictures)) {
                $item_holder->setContent("images", $item_pictures);
            }
            if (!empty($item_videos)) {
                $item_holder->setContent("videos", $item_videos);
            }

            $subjects = $one_post["terms"];
            if (!$subjects) {
                $subjects = array();
            }
            foreach ($subjects as $one_term) {
                // note that categories are hierarchical
                if ("category" == $one_term["domain"]) {
                    $cat_path = $cat_slug_run = $cat_slug = $one_term["slug"];
                    $cat_slug_used = array(
                        $cat_slug_run => true,
                    );
                    $cat_name_arr = array($one_term["name"]);
                    while (true) {
                        // if no info on the category, no path to it
                        if (!array_key_exists($cat_slug_run, $categories_by_slug)) {
                            break;
                        }
                        // taking the parent of the current running category
                        $cat_slug_run = $categories_by_slug[$cat_slug_run]["category_parent"];
                        // not to cycle if some wrong data at the document
                        if ((!$cat_slug_run) || (array_key_exists($cat_slug_run, $cat_slug_used))) {
                            break;
                        }
                        $cat_path = $cat_slug_run . "/" . $cat_path;
                        $cat_slug_used[] = $cat_slug_run;
                        $cat_name_arr[] = $categories_by_slug[$cat_slug_run]["cat_name"];
                    }
                    $cat_name_arr = array_reverse($cat_name_arr);
                    $item_holder->setSubject("Path:Category#" . $cat_path, json_encode($cat_name_arr));
                }
                if ("post_tag" == $one_term["domain"]) {
                    $item_holder->setSubject("Item:Tag#" . $one_term["slug"], $one_term["name"]);
                }
            }

            $p_newsmlHolder->appendItem($item_holder);
        }

        $p_newsmlHolder->serializeSet();
        return true;
    } // fn makeImport

} // class WordPressImporter

?>
