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

if ('post' != strtolower($_SERVER['REQUEST_METHOD'])) {
    header("Location: index.php");
    exit(0);
}

$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');

function take_form_params($p_formInfo, $p_knownFormats, &$p_formValues = null) {
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
        $p_formValues["message"] = "email addressed not provided";
        return false;
    }
    if (!array_key_exists($format_field, $_REQUEST)) {
        $p_formValues["message"] = "file format not provided";
        return false;
    }

    $f_email = (string) $_REQUEST[$email_field];
    if (!$f_email) {
        $p_formValues["message"] = "email addressed not provided";
        return false;
    }

    $f_format = (string) strtolower($_REQUEST[$format_field]);
    if (!$f_format) {
        $p_formValues["message"] = "file format not provided";
        return false;
    }

    //if (!preg_match("/^[a-zA-Z0-9_\.+%=:-]+@[a-zA-Z0-9_\.-]+\.[a-zA-Z0-9_-]+$/", $f_email)) {}
    if (!preg_match("/^[a-zA-Z0-9_\.+%=:-]+@[a-zA-Z0-9_\.-]+$/", $f_email)) {
        $p_formValues["message"] = "email addressed not valid";
        return false;
    }

    if (!array_key_exists($f_format, $p_knownFormats)) {
        $p_formValues["message"] = "file format not known";
        return false;
    }

    // checking file
    if (!array_key_exists($file_field, $_FILES)) {
        $p_formValues["message"] = "cms file not provided";
        return false;
    }

    $file_orig_name = $_FILES[$file_field]['name'];
    $file_tmp_name = $_FILES[$file_field]['tmp_name'];
    if (empty($file_orig_name)) {
        $p_formValues["message"] = "cms file not provided";
        return false;
    }

    $p_formValues['email'] = $f_email;
    $p_formValues['format'] = $f_format;
    $p_formValues['file_orig'] = $file_orig_name;
    $p_formValues['file_tmp'] = $file_tmp_name;

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

    $db_host = $p_dbAccess['host'];
    $db_user = $p_dbAccess['user'];
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
        var_dump($exc);
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
foreach ($converter_plugins as $one_plug => $one_plug_info) {
    $knownFormats[$one_plug] = true;
}

$formValues = array(
    'message' => "",
);
$fileInfo = array();
$got_params = true;

$res = take_form_params($formInfo, $knownFormats, $formValues);
if (!$res) {
    $got_params = false;
    // echo "wrong params";
}

if ($res) {
    $res = save_uploaded_file($converter_paths, $formValues, $fileInfo);
    // if (!$res) {echo "can not save file";}
}

if ($res) {
    $res = set_import_info($converter_db_access, $formValues, $fileInfo);
    // if (!$res) {echo "can not set db";}
}

if ($res) {
    $res = start_converter($converter_runtime);
    // if (!$res) {echo "can not start job";}
}

if ($res) {
    echo '
<html>
<head>
<title>Newscoop CMS conversion</title>
<link type="text/css" rel="stylesheet" href="styles/import.css" media="all">
</head>
<body>
<div class="conversion_submit_done">

<div class="conversion_submit_done_info">
The CMS file was submitted for Newscoop NewsML conversion.
You will be informed by email when the conversion is finished.
</div>

<div class="import_back_link">
<a href="index.php">back</a>
</div>

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
<link type="text/css" rel="stylesheet" href="styles/import.css" media="all">
</head>
<body>
<div class="conversion_submit_failed">

<div class="conversion_submit_failed_info">
Unfortunately, the CMS file could not be processed.
</div>
';

if ((!$got_params) && ($formValues["message"])) {
    echo '
<div class="conversion_submit_failed_reason">
' . $formValues["message"] . '
</div>
';
}

echo '
<div class="import_back_link">
<a href="index.php">back</a>
</div>

</div>
</body>
</html>
';

?>
