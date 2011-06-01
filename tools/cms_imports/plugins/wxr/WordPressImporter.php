<?php
/**
    Data parsing is done according to WordPress importer
    http://wordpress.org/extend/plugins/wordpress-importer/
    licensed under GPL2+, thus it should be ok to use it

    Note that this does not run on some WXR files since WordPress creates non-valid XML if 'CDATA' part is at an article content (e.g. at javascript), see
    http://drupal.org/node/1055310

    One WP post results in:
    *) one remote image message for image-only posts
    *) one text message for a text post w/o links from that text
    *) one text message, plus remote image messages for text post with img links
        - those image messages are linked from the text mesage

 */

require_once('WordPressParsers.php');

/**
 * Imports data from WordPress WXR file into NewsML file
 */
class WordPressImporter extends CMSImporterPlugin {

    private function slugify($p_text) {
        $p_text = strip_tags($p_text);
        $p_text = preg_replace('~[^\\pL\d]+~u', '-', $p_text);
        $p_text = trim($p_text, '-');
        if (function_exists('iconv'))
        {
            $p_text = iconv('utf-8', 'us-ascii//TRANSLIT', $p_text);
        } else {
            $p_text = preg_replace('~[^a-zA-Z\d]+~', '-', $p_text);
            $p_text = trim($p_text, '-');
        }
        $p_text = strtolower($p_text);
        $p_text = preg_replace('~[^-\w]+~', '', $p_text);
        if (empty($p_text)) {
            return "";
        }
        return $p_text;
    }

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
            return "image/svg+xml";
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
        // this function is not used now, since no image downloading is done herein
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
            $img_spec["colors"] = "" . $image_info["channels"] . "channels;" . $image_info["bits"] . "bits";
            unlink($img_path);

            return $img_spec;
        }
        catch (Exception $exc) {
            return false;
        }
        return false;
    } // fn tryGetImage


    private function takeAttachedImages($p_post) {
        # we search for all the attached images, and take them as different versions of a single image
        # up to now, the only seen situation was about a single image there (with different versions/sources),
        # since the attachment_url (see below) is a single one (at most), it should be the general case

        $item_pictures = array();
        $item_pictures_usage = array();

        $version_rank = 0;

        // this probably contains real links for the attachment_url links
        if (array_key_exists('postmeta', $p_post)) {
            $post_metas = $p_post['postmeta'];
            if (is_array($post_metas)) {
                foreach ($post_metas as $one_pm) {
                    $pm_value = trim($one_pm['value']);
                    if (preg_match("/^(?:http(?:s)?|ftp(?:s)?)\:\/\/[^<>\s\"]+\.(?:jpg|jpeg|gif|png|tif|tiff|svg)$/i", $pm_value)) {
                        if (!in_array($pm_value, $item_pictures_usage)) {
                            $item_pictures_usage[] = $pm_value;
                            $version_rank += 1;
                            $img_info = array("href" => $pm_value, "type" => $this->getImageTypeByName($pm_value), "version" => $version_rank);
                            $item_pictures[] = $img_info;
                        }
                    }
                }
            }
        }

        // empty wp posts shall not contain multiplied pictures,
        // but we take them as different versions of a single image
        if (array_key_exists('attachment_url', $p_post)) {
            $pm_value = $p_post['attachment_url'];
            if (!in_array($pm_value, $item_pictures_usage)) {
                $item_pictures_usage[] = $pm_value;
                $version_rank += 1;
                $img_info = array("href" => $pm_value, "type" => $this->getImageTypeByName($pm_value), "version" => $version_rank);
                $item_pictures[] = $img_info;
            }
        }

        // the result is either the empty set or several (usually two) versions of a single image
        $return_array = array();
        if (!empty($item_pictures)) {
            $return_array[] = $item_pictures;
        }

        return $return_array;
    }

    private function takeInnerImages($p_post) {

        # here we take each image as a (single-versioned) image by itself, since they can have different attributes
        $return_array = array();

        $content_text = $p_post["post_content"];
        $text_empty = false;
        if ("" == trim($content_text)) {
            $text_empty = true;
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
                        $one_img_attrs["version"] = 1;
                        $img_attrs[] = $one_img_attrs;
                    }
                    $img_rank = -1;
                    foreach ($img_matches[1] as $one_img) {
                        $img_rank += 1;
                        $img_info = $img_attrs[$img_rank];
                        $img_info["href"] = $one_img;
                        $img_info["type"] = $this->getImageTypeByName($one_img);
                        $return_array[] = array($img_info);
                    }
                }
            }
        }

        return $return_array;
    }

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
        $categories_slugs_by_name = $import_data["categories_slugs_by_name"];

        $used_unames = array(); // for unique names

        foreach ($import_data["posts"] as $one_post) {
            # firstly, we need to take all the images links to know whether we shall create a single newsItem or several newsItems from a single post
            # single newsItem for: having a text and no image, or having a single (even when with several versions) image and no text
            # several pieces of newsItem: for having both text and image(s)

            $image_list_meta = $this->takeAttachedImages($one_post);
            $image_list_text = $this->takeInnerImages($one_post);


            // how to deal with this post
            $content_type = "text"; // just a text (no images) or nothing (text pretending then)
            $content_text = $one_post["post_content"];
            $text_empty = false;
            if ("" == trim($content_text)) {
                $text_empty = true;
            }
            if (!empty($image_list_meta)) {
                if ($text_empty) {
                    $content_type = "picture"; // this is for no text (and thus no inner images), but having an (attached) image
                }
                else {
                    $content_type = "composite"; // having both text and (usually inner) images
                }
            }
            if (!empty($image_list_text)) {
                $content_type = "composite"; // having both text (it is there when having images inside that text) and (usually inner) images
            }

            // creating the main newsItem ('text' for text/composite posts, 'picture' for (attached) image posts)
            $item_holder = $p_newsmlHolder->createItem();

            $one_date_time = gmdate("Y-m-d");
            $item_holder->setCreated($one_date_time); // have to be set explicitely ("1234-56-78", "11:22:33.000", "+01:00") if item has some asset(s)
            $item_holder->setCopyright($copyright_info);

            $author_name = $one_post["post_author"];
            if (array_key_exists($one_post["post_author"], $import_data["authors"])) {
                $author_name = $import_data["authors"][$one_post["post_author"]]["author_display_name"];
            }

            $item_holder->setCreator($one_post["post_author"], $author_name);
            $item_holder->setHeadline($one_post["post_title"]);

            $cur_slugline = $one_post["post_name"];
            if (empty($cur_slugline)) {
                $cur_slugline = $this->slugify($one_post["post_title"]);
                if (empty($cur_slugline)) {
                    $cur_slugline = "a-post";
                }
            }

            $one_uname = $cur_slugline;
            $one_uname = str_replace(array("\"", ":", "$"), array("-", "-", "-"), $one_uname);
            $one_uname_test = $one_uname;
            $one_uname_test_rank = 1;
            while (array_key_exists($one_uname_test, $used_unames)) {
                $one_uname_test_rank += 1;
                $one_uname_test = $one_uname . "-" . $one_uname_test_rank;
            }

            $one_uname = $one_uname_test;
            $item_holder->setUniqueName($one_uname);
            $used_unames[$one_uname] = true;

            $item_holder->setSlugline($cur_slugline);
            $orig_link = $one_post["guid"]; // this is a unique id that my contain old (already not valid) information
            if (array_key_exists("link", $one_post)) {
                $orig_link = $one_post["link"]; // the link info shall be the right one, if available
            }
            $item_holder->setLink($orig_link);

            if (("text" == $content_type) || ("composite" == $content_type)) {
                $item_holder->setContent("text", $content_text);
            }

            if ("picture" == $content_type) {
                $item_holder->setContent("images", $image_list_meta[0]); // the length of this array is one here, may change at next versions
            }

            $subjects = array();
            if (array_key_exists("terms", $one_post)) {
                $subjects = $one_post["terms"];
            }
            if (!$subjects) {
                $subjects = array();
            }
            foreach ($subjects as $one_term) {
                if (empty($one_term) || empty($one_term["slug"]) || empty($one_term["name"]) || empty($one_term["domain"])) {
                    continue;
                }
                $one_term_slug = str_replace(array("\"", ":", "/"), array("-", "-", "-"), $one_term["slug"]);

                // note that categories are hierarchical
                if ("category" == $one_term["domain"]) {
                    $cat_path = $cat_slug_run = $cat_slug = $one_term_slug;
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
                        $cat_name_run = $cat_slug_run; // if slug/name will not be found
                        // not to cycle if some wrong data at the document
                        if (!$cat_slug_run) {
                            break;
                        }
                        // they can have slugs or names or whatever as category parents
                        if (array_key_exists($cat_slug_run, $categories_by_slug)) {
                            $cat_name_run = $categories_by_slug[$cat_slug_run]["cat_name"];
                        } else {
                            if (array_key_exists($cat_slug_run, $categories_slugs_by_name)) {
                                $cat_slug_run = $categories_slugs_by_name[$cat_slug_run];
                            } else {
                                $cat_slug_run = $this->slugify($cat_slug_run);
                            }
                        }

                        // not to cycle if some wrong data at the document
                        if (array_key_exists($cat_slug_run, $cat_slug_used)) {
                            break;
                        }

                        $cat_path = $cat_slug_run . "/" . $cat_path;
                        $cat_slug_used[] = $cat_slug_run;
                        $cat_name_arr[] = $cat_name_run;
                    }
                    $cat_name_arr = array_reverse($cat_name_arr);
                    $item_holder->setSubject("Path:Category//" . $cat_path, json_encode($cat_name_arr));
                }
                if ("post_tag" == $one_term["domain"]) {
                    $item_holder->setSubject("Item:Tag//" . $one_term_slug, $one_term["name"]);
                }
            }

            if ("composite" == $content_type) {
                $image_rank = 0;

                foreach ($image_list_text as $one_img_set) {
                    if (empty($one_img_set)) {
                        continue;
                    }
                    $image_rank += 1;
                    $item_holder->setImageLink($image_rank);
                }
            }

            $p_newsmlHolder->appendItem($item_holder);
            $item_holder = null;

            if ("composite" == $content_type) {
                $image_rank = 0;

                // the $image_list_meta is non-zero just for 'image' type messages, this may change at future
                foreach ($image_list_text as $one_img_set) {
                    if (empty($one_img_set)) {
                        continue;
                    }
                    $image_rank += 1;

                    $item_holder = $p_newsmlHolder->createItem();
                    $ret = $item_holder->becomeAsset("image", $image_rank);
                    $item_holder->setCreated($one_date_time); // if the item is an asset, this has to be set explicitely into the owner value
                    $item_holder->setUniqueName($one_uname); // if the item is an asset, this has to be set explicitely into the owner value

                    $item_holder->setCopyright($copyright_info);

                    $item_holder->setCreator($one_post["post_author"], $author_name);
                    $item_holder->setHeadline($one_post["post_title"] . ' # image ' . $image_rank);

                    // we need to modify the guid
                    $slugline_image = $cur_slugline . '-image-' . $image_rank;
                    // already not: set the slugline to contain the image spec, since the slugline is used for message id
                    $item_holder->setSlugline($slugline_image);
                    $item_holder->setLink($orig_link);

                    $item_holder->setContent("images", $one_img_set);

                    if (array_key_exists("title", $one_img_set[0])) {
                        $img_title = $one_img_set[0]["title"];
                        //$item_holder->setSubject("Image:Title", $img_title);
                        $item_holder->setTitle($img_title);
                    }

                    $p_newsmlHolder->appendItem($item_holder);
                    $item_holder = null;
                }
            }

        }

        $p_newsmlHolder->serializeSet();
        return true;
    } // fn makeImport

} // class WordPressImporter

?>
