<?php

/*

  The default way is to present form for file uploading.
  The data are sent to the 'submit.php' that prepares and triggers a job.

  If the 'newsml' parameter is present, requested converted file is served.

*/

$base_dir = dirname(dirname(__FILE__));

// taking configurations
$conf_dir = $base_dir . '/conf/';
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');
require_once($conf_dir . 'converter_loc.php');

// taking recaptcha lib
$incl_dir = $base_dir . '/incl/';
require_once($incl_dir . 'recaptchalib.php');

// this is the handler of the form data
$upload_url = 'submit.php';

// was this a request for the converted file
if (array_key_exists('newsml', $_REQUEST)) {
    $correct = true;
    $file_path = '';

    $file_id = $_REQUEST['newsml'];
    if (!preg_match('/^[a-zA-Z0-9]+[a-zA-Z0-9_\.-]*$/', $file_id)) {
        $correct = false;
    }

    if ($correct) {
        $file_path = $converter_paths['output_dir'] . $file_id;
        if (!file_exists($file_path)) {
            $correct = false;
        }
    }

    // serving the requested file if available
    try {
        if ($correct) {
            $fh = fopen($file_path, 'rb');
            header('Content-Type: text/xml');
            header('Content-Length: ' . filesize($file_path));
            header('Content-Disposition: attachment; filename="newscoop-' . $file_id . '.xml"');
            fpassthru($fh);
            fclose($fh);
        }
    }
    catch (Exception $exc) {
        $correct = false;
    }

    // if the requested file is not available
    if (!$correct) {
        $output_html_name = $base_dir . '/html/output_not_found.html';
        $output_html_fh = fopen($output_html_name, 'r');
        $output_html_text = fread($output_html_fh, filesize($output_html_name));
        fclose($output_html_fh);

        echo $output_html_text;
    }

    exit(0);
}

// a form for cms file upload; the standard way here

$input_html_name = $base_dir . '/html/input_index.html';
$input_html_fh = fopen($input_html_name, 'r');
$input_html_text = fread($input_html_fh, filesize($input_html_name));
fclose($input_html_fh);

$recaptcha_public = $converter_recaptcha['public'];
$recaptcha_part = recaptcha_get_html($recaptcha_public);
echo str_replace(array('%%upload_url%%', '%%recaptcha_part%%'), array($upload_url, $recaptcha_part), $input_html_text);

?>
