<?php

/*

  This is supposed to be run by webscript and/or cron:
  ./importer.php conig_dir worker_id

  Work flow:

    1) takes locks so that it is the only running worker (of the id)
    2) cycles over jobs available:
        a) takes the first free job from database, assignes it to itself
        b) proceeds the conversion according to the taken data
        c) sets that job as processed in the database
        d) sends notification to the user/owner and to the admin
    3) releases its lock

*/

// we need 2 parametrers: conf directory and worker id
$conf_dir = "";
if (3 > count($argv)) {
    exit(1);
}

$conf_dir = $argv[1];
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');
require_once($conf_dir . 'converter_loc.php');

$worker = (int) $argv[2];
if (0 >= $worker) {
    exit(1);
}

set_time_limit(0);

/**
 * Takes the lock so that this is the only working process (of the worker id)
 *
 * @param mixed $p_lockInfo: path, (files: $p_worker)
 * @param int $p_worker - int
 * @return boolean
 */
function take_lock(&$p_lockInfo, $p_worker) {

    $lock_file_path = $p_lockInfo["path"] . "$p_worker";

    $lfh = null;

    try {
        $lfh = fopen($lock_file_path, "a+");
    }
    catch (Exception $exc) {
        return false;
    }
    if (!$lfh) {
        return false;
    }

    $res = null;

    try {
        $res = flock($lfh, LOCK_EX | LOCK_NB);
    }
    catch (Exception $exc) {
        fclose($lfh);
        return false;
    }

    if (!$res) {
        fclose($lfh);
        return false;
    }

    if (!array_key_exists("files", $p_lockInfo)) {
        $p_lockInfo["files"] = array();
    }

    $p_lockInfo["files"][$p_worker] = $lfh;

    return true;
} // fn take_lock

/**
 * Free the previously got lock
 *
 * @param mixed $p_lockInfo: files: $p_worker
 * @param int $p_worker - int
 * @return boolean
 */
function release_lock(&$p_lockInfo, $p_worker) {
    if (!array_key_exists("files", $p_lockInfo)) {
        return false;
    }

    $lfh = $p_lockInfo["files"][$p_worker];
    if (null === $lfh) {
        return true;
    }

    try {
        flock($lfh, LOCK_UN);
        fclose($lfh);
    }
    catch (Exception $exc) {
        return false;
    }

    $p_lockInfo["files"][$p_worker] = null;

    return true;
} // release_lock

/**
 * Choose a job request
 *
 * @param mixed $p_dbAccess: host, user, pwd, dbname
 * @param int $p_worker - int
 * @param mixed $p_jobInfo: (id, email, format, file, orig)
 * @return boolean
 */
function take_conv_info($p_dbAccess, $p_worker, &$p_jobInfo) {
    if (!is_array($p_jobInfo)) {
        return false;
    }

    $reqStrUpd = "UPDATE ConvRequests SET worker = :worker, state = 'work' WHERE id = (SELECT id FROM (SELECT id FROM ConvRequests WHERE worker = 0 AND state = 'init' ORDER BY id ASC LIMIT 1) AS conv_alias)";
    $reqStrSel = "SELECT id, email, format, file, orig FROM ConvRequests WHERE worker = :worker ORDER BY id ASC LIMIT 1";

    $got_info = false;
    $conv_id = 0;
    $conv_email = "";
    $conv_format = "";
    $conv_file = "";
    $conv_orig = "";

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
        $sthSel = $dbh->prepare($reqStrSel);
        $sthUpd = $dbh->prepare($reqStrUpd);

        $runs = 0;
        $runs_max = 2;
        while (!$got_info) {
            $runs += 1;
            if ($runs > $runs_max) {
                break;
            }

            $sthSel->bindValue(':worker', (string) $p_worker, PDO::PARAM_INT);
            $res = $sthSel->execute();
            if (!$res) {
                return false;
            }
            while ($row = $sthSel->fetch(PDO::FETCH_ASSOC)) {
                $conv_id = $row["id"];
                $conv_email = $row["email"];
                $conv_format = $row["format"];
                $conv_file = $row["file"];
                $conv_orig = $row["orig"];
                $got_info = true;
            }

            if ($got_info) {
                break;
            }

            $sthUpd->bindValue(':worker', (string) $p_worker, PDO::PARAM_INT);
            $res = $sthUpd->execute();
            if (!$res) {
                return false;
            }
        }

        $sthUpd = null;
        $sthSel = null;

    }
    catch (Exception $exc) {
        return false;
    }

    if (!$got_info) {
        return false;
    }

    $p_jobInfo["id"] = $conv_id;
    $p_jobInfo["email"] = $conv_email;
    $p_jobInfo["format"] = $conv_format;
    $p_jobInfo["file"] = $conv_file;
    $p_jobInfo["orig"] = $conv_orig;
    $p_jobInfo["state"] = 'work';

    return true;
} // take_conv_info

/**
 * Update info on the processed job request
 *
 * @param mixed $p_dbAccess: host, user, pwd, dbname
 * @param int $p_worker - int
 * @param mixed $p_jobInfo: id
 * @return boolean
 */
function update_conv_info($p_dbAccess, $p_worker, &$p_jobInfo) {

    $reqStrUpd = "UPDATE ConvRequests SET state = :state, worker = :worker WHERE id = :id";

    $db_host = $p_dbAccess['host'];
    $db_user = $p_dbAccess['user'];
    $db_pwd = $p_dbAccess['pwd'];
    $db_name = $p_dbAccess['dbname'];

    $job_id = $p_jobInfo["id"];
    $job_state = $p_jobInfo["state"];
    if (!$job_state) {
        $job_state = "failed";
    }

    try {
        $dbh = new PDO(
            "mysql:host=$db_host;dbname=$db_name", 
            "$db_user",
            "$db_pwd",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
        $sthUpd = $dbh->prepare($reqStrUpd);

        $sthUpd->bindValue(':state', (string) $job_state, PDO::PARAM_STR);
        $sthUpd->bindValue(':worker', (-1 * (int) $p_worker), PDO::PARAM_INT);
        $sthUpd->bindValue(':id', (int) $job_id, PDO::PARAM_INT);
        $res = $sthUpd->execute();
        if (!$res) {
            return false;
        }

        $sthUpd = null;
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
} // fn update_conv_info

/**
 * Do the conversion itself
 *
 * @param mixed $p_pathsInfo: output_dir, input_dir
 * @param mixed $p_runtimeInfo: incl_dir, plug_dir
 * @param mixed $p_pluginsInfo: $format: required_files, class_name
 * @param mixed $p_jobInfo: format, name, (state)
 * @return boolean
 */
function run_conversion($p_pathsInfo, $p_runtimeInfo, $p_pluginsInfo, &$p_jobInfo) {
    $p_jobInfo["state"] = 'failed';
    $format = $p_jobInfo["format"];

    if (!array_key_exists($format, $p_pluginsInfo)) {
        return false;
    }

    $incl_dir = $p_runtimeInfo["incl_dir"];
    $plug_dir = $p_runtimeInfo["plug_dir"];

    require_once($incl_dir . 'NewsMLCreator.php');

    $plugin_info = $p_pluginsInfo[$format];
    $req_files = $plugin_info["required_files"]; // WordPressParsers.php, WordPressImporter.php
    $class_name = $plugin_info["class_name"]; // WordPressImporter

    $dir_output = $p_pathsInfo["output_dir"];
    $dir_input = $p_pathsInfo["input_dir"];
    $local_name = $p_jobInfo["file"];

    $path_input = $dir_input . $local_name;
    $path_output = $dir_output . $local_name;

    try {
        if ($req_files) {
            foreach ($req_files as $one_req) {
                require_once($plug_dir . $format . "/" . $one_req);
            }
        }
    }
    catch (Exception $exc) {
        return false;
    }

    try {
        $newsmler = new NewsMLCreator($path_output);
        if (!$newsmler) {
            return false;
        }

        $importer = new $class_name();
        if (!$importer) {
            return false;
        }

        $res = $importer->makeImport($newsmler, $path_input);
        if (!$res) {
            return false;
        }

    }
    catch (Exception $exc) {
        return false;
    }

    $p_jobInfo["state"] = 'done';
    return true;
} // run_conversion

/**
 * Clean the uploaded file
 *
 * @param mixed $p_pathsInfo: input_dir
 * @param mixed $p_jobInfo: file
 * @return boolean
 */
function remove_original($p_pathsInfo, $p_jobInfo) {
    $dir_input = $p_pathsInfo["input_dir"];
    $local_name = $p_jobInfo["file"];

    $path_input = $dir_input . $local_name;

    try {
        unlink($path_input);
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
} // remove_original

/**
 * Make email notices on the conversion
 *
 * @param mixed $p_pathsInfo: output_dir
 * @param mixed $p_adminInfo: email
 * @param mixed $p_jobInfo: id, email, format, file, orig
 * @return boolean
 */
function send_notifications($p_pathsInfo, $p_adminInfo, $p_jobInfo) {
    $cur_date = gmdate("Y-m-d H:i:s");

    $processed = false;
    $conv_state = "failed";
    if ('done' == $p_jobInfo['state']) {
        $processed = true;
        $conv_state = "done";
    }



    $to_user = $p_jobInfo["email"];
    $subject_user = "Newscoop conversion processed";
    $message_user = '';

    $file_link = $p_adminInfo["download"] . '?newsml=' . $p_jobInfo["file"];

    if ($processed) {
        $message_user .= '
The file \'' . $p_jobInfo["orig"] . '\' has been converted into NewsML.
It is available at ' . $file_link . '
';
    }
    else {
        $message_user .= '
The file \'' . $p_jobInfo["orig"] . '\' could not be converted into NewsML.
';

        $dir_output = $p_pathsInfo["output_dir"];
        $local_name = $p_jobInfo["file"];
        $path_output = $dir_output . $local_name;
        if (file_exists($path_output) && filesize($path_output)) {
            $message_user .= '
Error information may be found at ' . $file_link . '
';
        }

    }

    $to_admin = $p_adminInfo["email_to"];
    $mail_headers = 'From: ' . $p_adminInfo["email_from"]; // . "\r\n"

    $subject_admin = "";
    if ($processed) {
        $subject_admin = "CMS conversion processed";
    }
    else {
        $subject_admin = "CMS conversion failed";
    }
    $message_admin = '
Conversion
job/db id:  ' . $p_jobInfo["id"] . '
user email: ' . $p_jobInfo["email"] . '
cms format: ' . $p_jobInfo["format"] . '
original:   ' . $p_jobInfo["orig"] . '
local:      ' . $p_jobInfo["file"] . '
state:      ' . $conv_state . '
datetime:   ' . $cur_date . '
';

    if (!mail($to_user, $subject_user, $message_user, $mail_headers)) {
        echo "[$cur_date] job id: " . $p_jobInfo["id"] . ", can not send email to $to_user\n";
    }
    if(!mail($to_admin, $subject_admin, $message_admin, $mail_headers)) {
        echo "[$cur_date] job id: " . $p_jobInfo["id"] . ", can not send email to $to_admin\n";
    }

    return true;
} // fn send_notifications

/**
 * Assure that the file lock was released
 *
 * @return void
 */
function do_at_exit() {
    global $converter_locks, $worker;
    release_lock($converter_locks, $worker);
} // fn do_at_exit

register_shutdown_function('do_at_exit');

// assure we are the only running worker (of the worker id)
$locked = take_lock($converter_locks, $worker);
if (!$locked) {
    exit(1);
}

$converted = 0;

while(true) {
    $job_info = array();

    // choose a job request
    $taken = take_conv_info($converter_db_access, $worker, $job_info);
    if (!$taken) {
        break;
    }

    // run the conversion itself
    $res = run_conversion($converter_paths, $converter_runtime, $converter_plugins, $job_info);
    if ($res) {
        $converted += 1;
    }

    // update the request info
    update_conv_info($converter_db_access, $worker, $job_info);

    // email notices, remove uploaded file
    send_notifications($converter_paths, $converter_admin, $job_info);
    remove_original($converter_paths, $job_info);

}

// allow to run next workers
release_lock($converter_locks, $worker);

$exit_val = 1;
if ($converted) {
    $exit_val = 0;
}

exit ($exit_val);

?>
