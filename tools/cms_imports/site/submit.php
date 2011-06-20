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
    header('Location: index.php');
    exit(0);
}

$base_dir = dirname(dirname(__FILE__));

// taking configurations
$conf_dir = $base_dir . '/conf/';
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');
require_once($conf_dir . 'converter_loc.php');

// taking recaptcha lib
$incl_dir = $base_dir . '/incl/';
require_once($incl_dir . 'recaptchalib.php');

/**
 * Checks whether reCaptcha was filled correctly
 *
 * @param string $p_privKey
 * @return boolean
 */
function check_recaptcha($p_privKey) {

    $client_addr = $_SERVER['REMOTE_ADDR'];
    $rec_challenge = $_POST['recaptcha_challenge_field'];
    $rec_response = $_POST['recaptcha_response_field'];

    if (empty($rec_challenge) or empty($rec_response)) {
        return false;
    }

    $resp = recaptcha_check_answer($p_privKey, $client_addr, $rec_challenge, $rec_response);
    return $resp->is_valid;
}

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

    $policy_field = $p_formInfo['policy'];
    $email_field = $p_formInfo['email'];
    $email2_field = $p_formInfo['email2'];
    $file_field = $p_formInfo['file'];
    $format_field = $p_formInfo['format'];

    // checking egreement to the privacy policy
    $f_ppolicy = '';
    if (!array_key_exists($policy_field, $_REQUEST)) {
        $p_formValues['message'] = 'privacy policy agreement was not provided';
        return false;
    }
    $f_policy = (string) trim($_REQUEST[$policy_field]);
    if ((!$f_policy) || ("off" == strtolower($f_policy))) {
        $p_formValues['message'] = 'privacy policy agreement was not provided';
        return false;
    }

    // checking email, format
    $f_email = '';
    $f_email2 = '';
    $f_format = '';
    if (!array_key_exists($email_field, $_REQUEST)) {
        $p_formValues['message'] = 'email address not provided';
        return false;
    }
    if (!array_key_exists($email2_field, $_REQUEST)) {
        $p_formValues['message'] = 'email address confirmation not provided';
        return false;
    }
    if (!array_key_exists($format_field, $_REQUEST)) {
        $p_formValues['message'] = 'file format not provided';
        return false;
    }

    $f_email = (string) trim($_REQUEST[$email_field]);
    if (!$f_email) {
        $p_formValues['message'] = 'email address not provided';
        return false;
    }
    $f_email2 = (string) trim($_REQUEST[$email2_field]);
    if (!$f_email2) {
        $p_formValues['message'] = 'email address confirmation not provided';
        return false;
    }

    $f_format = (string) strtolower($_REQUEST[$format_field]);
    if (!$f_format) {
        $p_formValues['message'] = 'file format not provided';
        return false;
    }

    if (!preg_match('/^[a-zA-Z0-9_\.+%=:-]+@[a-zA-Z0-9_\.-]+$/', $f_email)) {
        $p_formValues['message'] = 'email address not valid';
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_\.+%=:-]+@[a-zA-Z0-9_\.-]+$/', $f_email2)) {
        $p_formValues['message'] = 'email address confirmation not valid';
        return false;
    }
    if ($f_email != $f_email2) {
        $p_formValues['message'] = 'email address and its confirmation differ';
        return false;
    }

    if (!array_key_exists($f_format, $p_knownFormats)) {
        $p_formValues['message'] = 'file format not known';
        return false;
    }

    // checking file
    if (!array_key_exists($file_field, $_FILES)) {
        $p_formValues['message'] = 'cms file not provided';
        return false;
    }

    $file_orig_name = $_FILES[$file_field]['name'];
    $file_tmp_name = $_FILES[$file_field]['tmp_name'];
    if (empty($file_orig_name)) {
        $p_formValues['message'] = 'cms file not provided';
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

    $save_dir = $p_paths['input_dir'];

    $local_name = '' . gmdate('\DYmd\THis\Z') . uniqid();
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

    $p_fileInfo['orig_name'] = $orig_name;
    $p_fileInfo['local_name'] = $local_name;

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

    $f_email = $formValues['email'];
    $f_format = $formValues['format'];
    $f_file = $p_fileInfo['local_name'];
    $f_orig = $p_fileInfo['orig_name'];

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
            array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
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

    $script_shell = $p_runtimeInfo['shell'];
    $script_name = $p_runtimeInfo['script'];
    $conf_dir = $p_runtimeInfo['conf_dir'];
    $log_file = $p_runtimeInfo['log_dir'] . 'convert_web.log';

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
    'policy' => 'policy_agreement',
    'email' => 'useremail',
    'email2' => 'useremail_retype',
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

$recaptcha_private = $converter_recaptcha['private'];

// try to take the sent data
$got_params = true;
$got_recaptcha = true;

$res = true;

if ($res) {
    $res = check_recaptcha($recaptcha_private);
    if (!$res) {
        $got_recaptcha = false;
    }
}

if ($res) {
    $res = take_form_params($formInfo, $knownFormats, $formValues);
    if (!$res) {
        $got_params = false;
    }
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

    $submit_ok_html_name = $base_dir . '/html/submit_ok.html';
    $submit_ok_html_fh = fopen($submit_ok_html_name, 'r');
    $submit_ok_html_text = fread($submit_ok_html_fh, filesize($submit_ok_html_name));
    fclose($submit_ok_html_fh);

    echo $submit_ok_html_text;
    exit(0);
}

// write error info if something got wrong
$submit_error_html_name = $base_dir . '/html/submit_error.html';
$submit_error_html_fh = fopen($submit_error_html_name, 'r');
$submit_error_html_text = fread($submit_error_html_fh, filesize($submit_error_html_name));
fclose($submit_error_html_fh);

$problem_reason= '';
if (!$got_recaptcha) {
    $problem_reason = 'Incorrectly filled the reCaptcha field.';
} else {
    if ((!$got_params) && ($formValues['message'])) {
        $problem_reason = $formValues['message'];
    }
}
echo str_replace(array('%%problem_reason%%'), array($problem_reason), $submit_error_html_text);

?>
