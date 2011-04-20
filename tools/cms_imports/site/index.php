<?php

$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');

// was this a request for the converted file
if (array_key_exists("newsml", $_REQUEST)) {
    $correct = true;
    $file_path = "";

    $file_id = $_REQUEST["newsml"];
    if (!preg_match("/^[a-zA-Z0-9_\.-]+$/", $file_id)) {
        $correct = false;
    }

    if ($correct) {
        $file_path = $converter_paths["output_dir"] . $file_id;
        if (!file_exists($file_path)) {
            $correct = false;
        }
    }

    try {
        if ($correct) {
            $fh = fopen($file_path, 'rb');
            header("Content-Type: text/xml");
            header("Content-Length: " . filesize($file_path));
            fpassthru($fh);
            fclose($fh);
        }
    }
    catch (Exception $exc) {
        $correct = false;
    }

    if (!$correct) {
        echo '
<html>
<head>
<title>Newscoop CMS conversion</title>
</head>
<body>
<div style="width:90%,margin-left:auto,margin-right:auto">
<p>
The requested NewsML file was not found.
</p>
<p>
<a href="http://www.sourcefabric.org/">Sourcefabric</a>
</p>
</div>
</body>
</html>
';
    }

    exit(0);
}

// a form for cms file upload

        echo '
<html>
<head>
<title>Newscoop CMS conversion</title>
</head>
<body>
<div style="width:90%,margin-left:auto,margin-right:auto">
<p>
WXR into Newscoop NewsML conversion
</p>
<p>
<form enctype="multipart/form-data" action="' . $converter_admin["upload"] . '" method="POST">
<input type="text" name="useremail" value="your email contact" size="80" />
<input type="hidden" name="cmsformat" value="wxr" />
<input type="hidden" name="MAX_FILE_SIZE" value="30000000" />
<input type="file" name="cmsfile" />
<input type="submit" value="Send File" />
</form>
</p>
<p>
<a href="http://www.sourcefabric.org/">Sourcefabric</a>
</p>
</div>
</body>
</html>
';

?>
