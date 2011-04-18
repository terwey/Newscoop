<?php

// just for testing purposes

echo "<pre>\n";

    require('WordPressParsers.php');

    $filename = 'kosmoblog.wordpress.short.xml';

    if (!file_exists($filename)) {
        echo "no file";
        exit(0);
    }

    $parser = new WXR_Parser();
    $import_data = $parser->parse($filename);

    if (!$import_data) {
        echo "\n\nsomething wrong\n\n";
        var_dump(libxml_get_errors());
        exit(0);
    }

    if (!$import_data["correct"]) {
        echo "\n\nerror during file processing\n\n";
        echo $import_data["errormsg"];
        exit(0);
    }

    echo "title: " . $import_data["title"] . "\n";
    echo "link: " . $import_data["link"] . "\n";
    echo "\n";

    foreach ($import_data["posts"] as $one_post) {
        echo "headline: " . $one_post["post_title"] . "\n";
        echo "slugline: " . $one_post["post_name"] . "\n";
        echo "link: " . $one_post["guid"] . "\n";
        echo "created: " . $one_post["post_date_gmt"] . "\n";
        echo "author: " . $one_post["post_author"] . ": " . $import_data["authors"][$one_post["post_author"]]["author_display_name"] . "\n";
        foreach ($one_post["terms"] as $one_term) {
            if ("category" == $one_term["domain"]) {
                echo "WPCat:" . $one_term["slug"] . " - " . htmlspecialchars($one_term["name"]) . "\n";
            }
            if ("post_tag" == $one_term["domain"]) {
                echo "WPTag:" . $one_term["slug"] . " - " . htmlspecialchars($one_term["name"]) . "\n";
            }
        }
        if ($one_post["post_content"]) {
            echo "<i style='color:a0a0a0'>\n";
            echo htmlspecialchars($one_post["post_content"]) . "\n";
            echo "</i>\n";
        }
        echo "\n";

    }

echo "</pre>\n";
?>
