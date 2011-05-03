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

    private function getImageTypeByName($p_path) {
        $name_arr = explode(".", (string) $p_path);
        $name_arr_last = count($name_arr) - 1;
        if (0 >= $name_arr_last) {
            return "image/*";
        }
        $name_suffix = strtolower($name_arr[$name_arr_last]);
        if (in_array($name_suffix, array("jpg", "jpeg"))) {
            return "image/jpeg";
        }
        if (in_array($name_suffix, array("gif"))) {
            return "image/gif";
        }
        if (in_array($name_suffix, array("png"))) {
            return "image/png";
        }
        if (in_array($name_suffix, array("tif", "tiff"))) {
            return "image/tiff";
        }
        if (in_array($name_suffix, array("svg"))) {
            return "xml/svg";
        }
        return "image/*";
    } // fn getImageTypeByName

    /**
     * Auxiliary function for saving a (temporary) file
     *
     * @param string $p_fileUrl original path of the file
     * @return string
     */
    private function getCacheFilename($p_fileUrl) {
        return sys_get_temp_dir() . '/' . md5($p_fileUrl) . '.img.cache';
    } // fn getCacheFilename

    /**
     * Tries to get info an image of the provided url
     *
     * @param string $p_imageUrl path for the image
     * @return mixed
     */
    private function tryGetImage($p_imageUrl) {
        $img_spec = array("width" => 0, "height" => 0, "size" => 0, "type" => "", "colors" => "");

        try {
            $img_data = @file_get_contents($p_imageUrl);
            if (!$img_data) {
                return false;
            }
            $img_path = $this->getCacheFilename($p_imageUrl);
            file_put_contents($img_path, $img_data);
            $img_data = null;
            $image_info = @getimagesize($img_path);
            $img_spec["width"] = $image_info[0];
            $img_spec["height"] = $image_info[1];
            $img_spec["size"] = filesize($img_path);
            $img_spec["type"] = $image_info["mime"];
            $img_spec["colors"] = "channels=" . $image_info["channels"] . ";bits:" . $image_info["bits"];
            unlink($img_path);

            return $img_spec;
        }
        catch (Exception $exc) {
            return false;
        }
        return false;
    } // fn tryGetImage

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
            $orig_link = $one_post["guid"]; // this is a unique id that my contain old (already not valid) information
            if (array_key_exists("link", $one_post)) {
                $orig_link = $one_post["link"]; // the link info shall be the right one, if available
            }
            $item_holder->setLink($orig_link);

            $item_pictures = array();
            $item_pictures_usage = array();
            $item_pictures_other = array();
            //$item_videos = array();

            $content_type = "text";
            $content_text = $one_post["post_content"];
            $text_empty = false;
            if ("" == trim($content_text)) {
                $text_empty = true;
            }

            // this probably contains real links for the attachment_url links
            if (array_key_exists('postmeta', $one_post)) {
                $post_metas = $one_post['postmeta'];
                if (is_array($post_metas)) {
                    foreach ($post_metas as $one_pm) {
                        $pm_value = trim($one_pm['value']);
                        if (preg_match("/^(?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)$/i", $pm_value)) {
                            if (!in_array($pm_value, $item_pictures_usage)) {
                                $item_pictures_usage[] = $pm_value;
                                $img_info = $this->tryGetImage($pm_value);
                                if (!$img_info) {
                                    $img_info = array("href" => $pm_value, "type" => $this->getImageTypeByName($pm_value));
                                    $item_pictures_other[] = $img_info;
                                    continue;
                                }
                                $img_info["href"] = $pm_value;
                                $item_pictures[] = $img_info;
                            }
                        }
                    }
                }
            }

            // empty wp posts shall not contain multiplied pictures
            if ((!$text_empty) || (empty($item_pictures))) {
                if (array_key_exists('attachment_url', $one_post)) {
                    $pm_value = $one_post['attachment_url'];
                    if (!in_array($pm_value, $item_pictures_usage)) {
                        $item_pictures_usage[] = $pm_value;
                        $img_info = $this->tryGetImage($pm_value);
                        if (!$img_info) {
                            $img_info = array("href" => $pm_value, "type" => $this->getImageTypeByName($pm_value));
                            $item_pictures_other[] = $img_info;
                        }
                        else {
                            $img_info["href"] = $pm_value;
                            $item_pictures[] = $img_info;
                        }
                    }
                }
            }

            if ($text_empty && empty($item_pictures) && (!empty($item_pictures_other))) {
                $item_pictures[] = $item_pictures_other[0];
            }

            if (!$text_empty) {
                if (preg_match_all("/<img(?:[^<>]+)src=\"((?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+)\"(?:[^<>]*)>/i", $content_text, $img_matches, PREG_PATTERN_ORDER)) {
                    if (is_array($img_matches[1])) {
                        $img_attrs = array();
                        foreach ($img_matches[0] as $one_img) {
                            $got_title = preg_match("/title=\"([^\"]+)\"/i", $one_img, $one_img_title);
                            $got_width = preg_match("/width=\"([^\"]+)\"/i", $one_img, $one_img_width);
                            $got_height = preg_match("/height=\"([^\"]+)\"/i", $one_img, $one_img_height);
                            $got_class = preg_match("/class=\"([^\"]*)thumbnail([^\"]*)\"/i", $one_img);
                            $one_img_attrs = array();
                            if ($got_title) {
                                $one_img_attrs["title"] = $one_img_title[1];
                            }
                            if ($got_width) {
                                $one_img_attrs["width"] = $one_img_width[1];
                            }
                            if ($got_height) {
                                $one_img_attrs["height"] = $one_img_height[1];
                            }
                            if ($got_class) {
                                $one_img_attrs["class"] = "thumbnail";
                            }
                            $img_attrs[] = $one_img_attrs;
                        }
                        $img_rank = -1;
                        foreach ($img_matches[1] as $one_img) {
                            $img_rank += 1;
                            if (!in_array($one_img, $item_pictures_usage)) {
                                $img_info = $img_attrs[$img_rank];
                                $img_info["href"] = $one_img;
                                $img_info["type"] = $this->getImageTypeByName($one_img);
                                $item_pictures[] = $img_info;
                                $item_pictures_usage[] = $img_info;
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
                $item_holder->setContent("text", $content_text);
            }

            if (!empty($item_pictures)) {
                $item_holder->setContent("images", $item_pictures);
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
