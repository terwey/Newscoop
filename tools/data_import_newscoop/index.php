<?php

namespace NewsML;

include('NewsMLFeed.php');

//$feed = new Feed('http://feeds.feedburner.com/linuxtoday/linux?format=xml');
$newsfeed = new NewsMLFeed('http://172.16.0.1/nodis/wp-newsml.xml');

var_dump($newsfeed->next()); exit;

//Get items with next() or current()
echo $newsfeed->next()->guid . '<br />';

