<?php

require_once __DIR__ . '/../../classes/BugReporter.php';

/**
 * Called for all Newscoop errors.
 *
 * If the flag $Campsite['DEBUG'] is set to false, this function will
 * return minor errors (ie notices and warnings) without having
 * processed them.  Errors with fsockopen() are returned without being
 * processed regardless of the $Campsite['DEBUG'] flag.
 *
 * @param int    $p_number The error number.
 * @param string $p_string The error message.
 * @param string $p_file The name of the file in which the error occurred.
 * @param int    $p_line The line number in which the error occurred.
 * @return void
 */
function camp_bug_handler_main($p_number, $p_string, $p_file, $p_line)
{
    global $ADMIN_DIR;
    global $ADMIN;
    global $Campsite;
    global $g_user;

    // --- Return on unimportant errors ---
    if (!$Campsite['DEBUG']) {
        switch ($p_number) {
            case E_NOTICE:
            case E_WARNING:
            case E_USER_NOTICE:
            case E_USER_WARNING:
                return;
        }
    }

    // --- Return on coverred (with @) errors ---
    if (!error_reporting()) {
        return;
    }

    // throw exception instead of raising error
    // TODO: we should catch those exceptions on BridgeController
/*    if (defined('APPLICATION_ENV') && APPLICATION_ENV == 'development') {
        require_once __DIR__ . '/../../library/Newscoop/Utils/Exception.php';
        $exception = new \Newscoop\Utils\Exception($p_string, $p_number);
        $exception->setFile($p_file);
        $exception->setLine($p_line);
        throw $exception;
    }*/

    // -- Return on generic errors ---
    if (preg_match ('/^Undefined index:/i', $p_string)){
        return;
    }
    if (preg_match ('/^Undefined variable:/i', $p_string)){
        return;
    }
    if (preg_match ('/^Undefined offset:/i', $p_string)){
        return;
    }

    // -- SimpleXMLElement errors ---
    if (preg_match('/^SimpleXMLElement/i', $p_string)) {
        return;
    }

    // -- Return on URL parse errors
    if (preg_match('/^parse_url/i', $p_string)) {
        return;
    }

    // -- Return on mysql connect errors ---
    if (preg_match ('/^mysql_connect/i', $p_string)){
    return;
    }

    // --- Return on socket errors ---
    if (preg_match ('/^fsockopen/i', $p_string)){
        return;
    }

    // --- Return on unlink errors ---
    if (preg_match ('/^unlink/i', $p_string)){
        return;
    }

    // --- Return on upload file errors ---
    if (preg_match ('/^move_uploaded_file/i', $p_string)){
        return;
    }

    // -- Return on getimagesize errors --
    if (preg_match ('/^getimagesize/i', $p_string)){
        return;
    }

    // -- Return on imagecreate* errors --
    if (preg_match ('/^imagecreate/i', $p_string)) {
        return;
    }

    // -- Return on rmdir errors --
    if (preg_match ('/^rmdir/i', $p_string)){
        return;
    }

    // -- Return on mkdir errors --
    if (preg_match ('/^mkdir/i', $p_string)){
        return;
    }

    // -- Return on fopen errors --
    if (preg_match ('/^fopen/i', $p_string)){
        return;
    }

    // -- Return on chown errors --
    if (preg_match ('/^chown/i', $p_string)){
        return;
    }

    // -- Return on chgrp errors --
    if (preg_match ('/^chgrp/i', $p_string)){
        return;
    }

    define('IN_BUG_HANDLE', TRUE);

    if (ob_get_length() !== false) { // --- Don't print out the previous screen (in which the error occurred). ---
        ob_end_clean();
    }

    if (is_object($g_user)) {
        require_once($Campsite['HTML_DIR'] . "/$ADMIN_DIR/menu.php");
    }

    // --- If reporter doesn't exist, make one ($reporter might exist
    //     already if this script is an 'include') ---
    // Remove the code name from the version number.
    $version = explode(" ", $Campsite['VERSION']);
    $version = array_shift($version);

    if (!isset($reporter)) {
        $reporter = new BugReporter($p_number, $p_string, $p_file, $p_line,
                                    'Newscoop', $version);
    }

    // --- Print results ---
    if (!function_exists('http_response_code')) {
        httpResponseCode(500);
    } else {
        http_response_code(500);
    }

    include dirname(__FILE__) . '/emailus.php';
    exit();
}


/**
 * http_response_code function is supported from PHP >= 5.4
 * This function provide same functionality for PHP < 5.4
 *
 * @param int|string $newCode Status code.
 *
 * @return int
 */
function httpResponseCode($newcode = null)
{
    $code = 200;

    if ($newcode !== null) {
        header('X-PHP-Response-Code: '.$newcode, true, $newcode);
        if (!headers_sent()) {
            $code = $newcode;
        }
    }

    return $code;
}
