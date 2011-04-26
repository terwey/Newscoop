<?php

$__converter_base = dirname(dirname(__FILE__)) . "/";

$converter_paths = array(
    "input_dir" => $__converter_base . "inputs/",
    "output_dir" => $__converter_base . "outputs/",
);

$converter_runtime = array(
    "shell" => "/usr/bin/php",
    "script" => $__converter_base . "bin/importer.php",
    "conf_dir" => $__converter_base . "conf/",
    "incl_dir" => $__converter_base . "incl/",
    "plug_dir" => $__converter_base . "plugins/",
    "log_file" => $__converter_base . "logs/convert.log",
);

$converter_plugins = array(
    "wxr" => array(
        "class_name" => "WordPressImporter",
        "required_files" => array(
            "WordPressParsers.php",
            "WordPressImporter.php",
        ),
    ),
);

$converter_locks = array(
    "path" => "$__converter_base" . "locks/lock",
    "files" => array(),
);

?>