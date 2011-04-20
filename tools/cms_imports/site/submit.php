<?php

/*

  This is supposed to be run as a web page - the target of the upload form.

  Work flow:

    1) Takes / checks data sent from a form
    2) Saves the submitted file into the input directory
    3) Fills job info into the database
    4) Starts the converter.
    5) Writes an info.

*/

$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');

function take_form_params($p_formInfo, $p_knownFormats, @$p_formValues = null) {
    if (!is_array($p_formValues)) {
        return false;
    }

    $email_field = $p_formInfo['email'];
    $file_field = $p_formInfo['file'];
    $format_field = $p_formInfo['format'];

    // checking email, format
    $f_email = "";
    $f_format = "";
    if (!array_key_exists($email_field, $_REQUEST)) {
        return false;
    }
    if (!array_key_exists($format_field, $_REQUEST)) {
        return false;
    }
    $f_email = (string) $_REQUEST[$p_emailField];
    $f_format = (string) strtolower($_REQUEST[$p_formatField]);

    if (!preg_match("/^[a-zA-Z0-9_\.+%=:-]+@[a-zA-Z0-9_\.-]+\.[a-zA-Z0-9_-]+$/", $f_email)) {
        return false;
    }

    if (!in_array($f_format, $p_knownFormats)) {
        return false;
    }

    // checking file
    if (!array_key_exists($file_field, $_FILES)) {
        return false;
    }

    $file_orig_name = $_FILES[$file_field]['name'];
    $file_tmp_name = $_FILES[$file_field]['tmp_name'];

    $p_formValues['email'] = $f_email;
    $p_formValues['format'] = $f_format;
    $p_formValues['file_orig'] = $file_orig_name;
    $p_formValues['file_tmp'] = $file_orig_name;

    return true;
}

function save_uploaded_file($p_paths, $formValues, &$p_fileInfo = null) {
    if (!is_array($p_fileInfo)) {
        return false;
    }

    $save_dir = $p_paths["input_dir"];

    $local_name = "" . gmdate("\DYmd\THis\Z") . uniqid();
    $local_path = $save_dir . $local_name;

    $orig_name = $formValues['file_orig'];
    $tmp_name = $formValues['file_tmp'];

    try {
        $res = move_uploaded_file($tmp_name, $local_path);
        if (!$res) {
            return false;
        }
    }
    catch (Exception $exc) {
        return false;
    }

    $p_fileInfo["orig_name"] = $orig_name;
    $p_fileInfo["local_name"] = $local_name;

    return true;
}

function set_import_info($p_dbAccess, $formValues, $p_fileInfo) {

    $f_email = $formValues["email"];
    $f_format = $formValues["format"];
    $f_file = $p_fileInfo["local_name"];
    $f_orig = $p_fileInfo["orig_name"];

    $reqStr = "INSERT INTO ConvRequests (state, email, format, file, orig) VALUES ('init', :email, :format, :file, :orig)";

    $db_user = $p_dbAccess['host'];
    $db_host = $p_dbAccess['user'];
    $db_pwd = $p_dbAccess['pwd'];
    $db_name = $p_dbAccess['dbname'];

    try {
        $dbh = new PDO(
            "mysql:host=$db_host;dbname=$db_name", 
            "$db_user",
            "$db_pwd",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );

        $sth = $dbh->prepare($reqStr);
        $sth->bindValue(':email', (string) $f_email, PDO::PARAM_STR);
        $sth->bindValue(':format', (string) $f_format, PDO::PARAM_STR);
        $sth->bindValue(':file', (string) $f_file, PDO::PARAM_STR);
        $sth->bindValue(':orig', (string) $f_orig, PDO::PARAM_STR);

        $res = $sth->execute();
        if (!$res) {
            return false;
        }

        $sth = null;
    }
    catch (Exception $exc) {
        return false;
    }

    return true;

}

function start_converter($p_runtimeInfo) {

    $script_shell = $p_runtimeInfo["shell"];
    $script_name = $p_runtimeInfo["script"];
    $conf_dir = $p_runtimeInfo["conf_dir"];
    $log_file = $p_runtimeInfo["log_file"];

    $worker = 1;

    try {
        passthru("$script_shell $script_name $conf_dir $worker >> $log_file 2>&1 &");
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
}

$formInfo = array(
    'email' => 'useremail',
    'file' => 'cmsfile',
    'format' => 'cmsformat',
);

$knownFormats = array();
foreach ($converter_plugins as $one_plug) {
    $knownFormats[] = $one_plug;
}

$formValues = array();
$fileInfo = array();

$res = take_form_params($formInfo, $knownFormats, $formValues);

if ($res) {
    $res = save_uploaded_file($converter_paths, $formValues, $fileInfo);
}

if ($res) {
    $res = set_import_info($converter_db_access, $formValues, $fileInfo);
}

if ($res) {
    $res = start_converter($converter_runtime);
}

if ($res) {
    echo '
<html>
<head>
<title>Newscoop CMS conversion</title>
</head>
<body>
<div style="width:90%,margin-left:auto,margin-right:auto">
<p>
The CMS file was submitted for Newscoop NewsML conversion.
You will be informed by email when the conversion is finished.
</p>
<p>
Welcome to the <a href="http://www.sourcefabric.org/">Sourcefabric</a> site.
</p>
</div>
</body>
</html>
';
    exit(0);
}

echo '
<html>
<head>
<title>Newscoop CMS conversion</title>
</head>
<body>
<div style="width:90%,margin-left:auto,margin-right:auto">
<p>
Unfortunately, the CMS file could not be processed.
</p>
<p>
<a href="http://www.sourcefabric.org/">Sourcefabric</a>
</p>
</div>
</body>
</html>
';

?>
