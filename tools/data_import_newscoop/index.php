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

//$newsfeed = new NewsMLFeed('http://172.16.0.1/newnodis/example.xml');
//$newsfeed = new NewsMLFeed('http://172.16.0.1/newnodis/wp-newsml.xml');
//$newsfeed = new NewsMLFeed('http://newscoop_imp/nodis/wp-newsml.xml');
$newsfeed = new NewsMLFeed('http://newscoop_imp/nodis/example.xml');

$nf = null;
$nf_img = null;

$msg_count = $newsfeed->count();
$msg_count_news = $newsfeed->countNews();
echo "count: $msg_count_news / $msg_count\n\n";
for ($mind = 0; $mind < $msg_count; $mind++) {
    $nf_aux = $newsfeed->next();

    if ($nf_aux->isNews()) {
        $deps = $nf_aux->getDependencies();
        if (!empty($deps)) {
            var_dump($deps);
            echo "\n";
        }
        $nf = $nf_aux;
    }
    else {
        $nf_img = $nf_aux;
    }

}

$ret = $newsfeed->getAttributes($subjects);
echo json_encode($subjects);
echo "\n\n\n";

$msg_ind = count($nf->itemSet->newsItem) - 1;

if (!$nf) {
    echo "\nno _nf_ here\n";
    echo "\n</pre>\n";
    exit(0);
}

echo "\n\n\n";
echo "news id:   " . (string) $nf->getNewsID() . "\n";
echo "slugline:  " . (string) $nf->getSlugLine() . "\n";
echo "headline:  " . (string) $nf->getHeadLine() . "\n";
echo "link:      " . (string) $nf->getLink() . "\n";
echo "creator:   " . (string) $nf->getCreator() . "\n";
echo "service:   " . (string) $nf->getService() . "\n";
echo "copyright: " . (string) $nf->getCopyright() . "\n";
echo "title:     " . (string) $nf->getTitle() . "\n";

echo "\n\n\n";

$attributes = null;
$nf->getAttributes($attributes);
var_dump($attributes);

echo "\n\n\n";
foreach ($nf->getContentTexts() as $one_text) {
    echo "text:\n";
    echo htmlspecialchars((string) $one_text);
}

if ($nf_img) {
    $images = $nf_img->getContentImages();
    var_dump($images);
}

echo "\n</pre>\n";

echo '
</body>
</html>
';

