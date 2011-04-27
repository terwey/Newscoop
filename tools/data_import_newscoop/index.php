<?php
namespace NewsML;

header("Content-Type: text/html");
echo '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
</head>
<body>
';

echo "\n<pre>\n";

include('NewsMLFeed.php');

//$feed = new Feed('http://feeds.feedburner.com/linuxtoday/linux?format=xml');
//$newsfeed = new NewsMLFeed('http://172.16.0.1/nodis/wp-newsml.xml');
//$newsfeed = new NewsMLFeed('http://newscoop_imp/nodis/wp-newsml.xml');
$newsfeed = new NewsMLFeed('http://newscoop_imp/nodis/D20110427T180924Z4db85bd438456');


//var_dump($newsfeed->next()); exit;

$nf = $newsfeed->next();

$subjects = NewsMLFeed::GetSubjects($nf);
echo json_encode($subjects);
echo "\n\n\n";
var_dump($subjects);
//exit(0);

//echo count($nf->itemSet->newsItem);
//$msg_ind = 26;
//$msg_ind = 519;
$msg_ind = count($nf->itemSet->newsItem) - 1;

/*
echo "\n\n\n";
foreach (get_object_vars($nf->itemSet->newsItem[$msg_ind]) as $key => $val) {
    echo "$key\n";
}
@attributes
catalogRef
rightsInfo
itemMeta
contentMeta
contentSet
*/


/*
echo "\n\n\n";
foreach (get_object_vars($nf->itemSet->newsItem[$msg_ind]->contentMeta) as $key => $val) {
    echo "$key\n";
}
contentCreated
contentModified
creator
language
subject
link
slugline
headline
*/

/*
echo "\n\n\n";
foreach ($nf->itemSet->newsItem[$msg_ind]->contentMeta->subject as $key => $val) {
    $attrs = explode(":", (string) $val->attributes());
    
    $attrs_cms = $attrs[0];
    $attrs_sec_arr = explode("/", $attrs[1]);
    $attrs_sec_str = implode(" -> ", $attrs_sec_arr);

    echo $attrs_cms . " => " . $attrs_sec_str . " => ";
    echo (string) $val->name . "\n";
}
*/

echo "\n\n\n";
echo "slugline: " . (string) $nf->itemSet->newsItem[$msg_ind]->contentMeta->slugline . "\n";
echo "headline: " . (string) $nf->itemSet->newsItem[$msg_ind]->contentMeta->headline . "\n";

echo "\n\n\n";
echo htmlspecialchars((string) $nf->itemSet->newsItem[$msg_ind]->contentSet->inlineXML);

//Get items with next() or current()
// echo $newsfeed->next()->guid;

echo "\n</pre>\n";

echo '
</body>
</html>
';

