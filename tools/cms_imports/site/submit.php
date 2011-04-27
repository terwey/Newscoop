<?php

/*

  This is supposed to be run as a web page - the target of the upload form.

  Work flow:

    1) Takes / checks data sent from a form
    2) Saves the submitted file into the input directory
    3) Fills job info into the database
    4) Starts the converter.
    5) Writes an output info.

*/

// if not a post request (with file and other form data), go to the default page
if ('post' != strtolower($_SERVER['REQUEST_METHOD'])) {
    header("Location: index.php");
    exit(0);
}

// taking configurations
$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');
require_once($conf_dir . 'converter_loc.php');

/**
 * Takes and checks parameters sent via the form
 *
 * @param mixed $p_formInfo
 * @param mixed $p_knownFormats
 * @param mixed $p_formValues
 * @return boolean
 */
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
} // fn take_form_params

/**
 * Saves the uploaded file for next processing
 *
 * @param mixed $p_paths
 * @param mixed $p_formValues
 * @param mixed $p_fileInfo
 * @return boolean
 */
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
} // fn save_uploaded_file

/**
 * Saves the job information into database
 *
 * @param mixed $p_dbAccess
 * @param mixed $p_formValues
 * @param mixed $p_fileInfo
 * @return boolean
 */
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

} // fn set_import_info

/**
 * Starts the conversion process itself
 *
 * @param mixed $p_runtimeInfo
 * @return boolean
 */
function start_converter($p_runtimeInfo) {

    $script_shell = $p_runtimeInfo["shell"];
    $script_name = $p_runtimeInfo["script"];
    $conf_dir = $p_runtimeInfo["conf_dir"];
    $log_file = $p_runtimeInfo["log_dir"] . "convert_web.log";

    // we have just a one worker process for web by default
    $worker = 1;

    try {
        passthru("$script_shell $script_name $conf_dir $worker >> $log_file 2>&1 &");
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
} // start_converter

// what form data to get/check
$formInfo = array(
    'email' => 'useremail',
    'file' => 'cmsfile',
    'format' => 'cmsformat',
);

// formats that have their plugins
$knownFormats = array();
foreach ($converter_plugins as $one_plug => $one_plug_info) {
    $knownFormats[$one_plug] = true;
}

// holder variables
$formValues = array(
    'message' => "",
);
$fileInfo = array();

// try to take the sent data
$got_params = true;
$res = take_form_params($formInfo, $knownFormats, $formValues);
if (!$res) {
    $got_params = false;
}

// save the file if data correct
if ($res) {
    $res = save_uploaded_file($converter_paths, $formValues, $fileInfo);
}

// sets conversion request info into database
if ($res) {
    $res = set_import_info($converter_db_access, $formValues, $fileInfo);
}

// finally start the converter process
if ($res) {
    $res = start_converter($converter_runtime);
}

// write the output info if submit was correct
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

// write error info if something got wrong
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
