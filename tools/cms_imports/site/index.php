<?php

$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');

// was this a request for the converted file
if (array_key_exists("newsml", $_REQUEST)) {
    $correct = true;
    $file_path = "";

    $file_id = $_REQUEST["newsml"];
    if (!preg_match("/^[a-zA-Z0-9]+[a-zA-Z0-9_\.-]*$/", $file_id)) {
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
            //header("Content-Disposition: attachment; filename=\"$file_id.xml\"");
            header("Content-Disposition: attachment; filename=\"newscoop-$file_id.xml\"");
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
<link type="text/css" rel="stylesheet" href="styles/import.css" media="all">
</head>
<body>
<div class="no_file_page">
<div class="no_file_info">
The requested NewsML file was not found.
</div>
<div class="sourcefabric_link">
<a href="http://www.sourcefabric.org/">Sourcefabric</a>
</div>
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
<link type="text/css" rel="stylesheet" href="styles/import.css" media="all">
</head>
<body>
<div class="submit_form_page">
<div class="submit_form_info">
Import into Newscoop NewsML
</div>
<div class="newsml_import_form">
<form enctype="multipart/form-data" action="' . $converter_admin["upload"] . '" method="POST">
<input type="hidden" name="cmsformat" value="wxr" />
<input type="hidden" name="MAX_FILE_SIZE" value="30000000" />

<div class="newsml_import_form_field newsml_import_form_email">
<input type="text" class="input_user_email" name="useremail" value="email contact" size="40" onFocus="if (\'email contact\' == this.value) {this.value=\'\';};" title="Download information will be sent to that email." />
</div>
<div class="newsml_import_form_field">
Source CMS type
<select name="cmsformat">
    <option value="wxr">WordPress WXR
</select>
</div>
<div class="newsml_import_form_field">
<input type="file" name="cmsfile" />
</div>
<div class="newsml_import_form_field newsml_import_form_submit">
<input type="submit" value="Send File" />
</div>

</form>
</div>
<div class="sourcefabric_link">
<a href="http://www.sourcefabric.org/">Sourcefabric</a>
</div>

</div>
</body>
</html>
';

?>
