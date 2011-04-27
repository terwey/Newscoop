<?php

// is used for email notices
$converter_admin = array(
    "email_from" => "admin@example.net",
    "email_to" => "admin@example.net",
    // url of the download script (shall be the main importer page, i.e. that of site/index.php)
    "download" => "http://www.sourcefabric.org/newscoop/import/",
);

$converter_cron = array(
    // what is the max age (days) of files before deleting it, 0 for keeping
    "dbclean" => 30,
    // what is the max age (days) of dbinfo before deleting it, 0 for keeping
    "fsclean" => 30,
);

?>