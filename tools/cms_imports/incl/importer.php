<?php

/*

  This is supposed to be run by webscript and/or cron:
  ./importer.php conig_dir worker_id

  Work flow:

    1) takes locks so that it is the only running worker (of the id)
    2) cycles over jobs available:
        a) takes the first free job from database, assignes it to itself
        b) proceeds the conversion according to the taken data
        c) sends notification to the user/owner and to the admin
        d) sets that job as processed in the database
    3) releases its lock

*/


$conf_dir = "";
if (3 > count($argv)) {
    exit(1);
}

$conf_dir = $argv[1];
require_once($conf_dir .'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');

$worker = (int) $argv[2];
if (0 >= $worker) {
    exit(1);
}

set_time_limit(0);

// $p_lockInfo: path, (files: $p_worker)
// $p_worker - int
function take_lock(&$p_lockInfo, $p_worker) {

    $lock_file_path = $p_lockInfo["path"]; . "$p_worker";

    $lfh = null;

    try {
        $lfh = fopen($lock_file_path, "r+");
    }
    catch (Exception $exc) {
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
}

// $p_lockInfo: files: $p_worker
// $p_worker - int
function release_lock(&$p_lockInfo, $p_worker) {
    if (!array_key_exists("files", $p_lockInfo)) {
        return false;
    }

    $lfh = $p_lockInfo["files"][$p_worker];

    try {
        flock($lfh, LOCK_UN);
        fclose($lfh);
    }
    catch (Exception $exc) {
        return false;
    }

    $p_lockInfo["files"][$p_worker] = null;

    return true;
}

// $p_dbAccess: host, user, pwd, dbname
// $p_worker - int
// $p_jobInfo: (id, email, format, file, orig)
function take_conv_info($p_dbAccess, $p_worker, &$p_jobInfo) {
    if (!is_array($p_jobInfo)) {
        return false;
    }

    $reqStrUpd = "UPDATE ConvRequests SET worker = :worker, state = 'work' WHERE id = (SELECT id FROM ConvRequests WHERE worker = 0 ORDER BY id ASC LIMIT 1)";
    $reqStrSel = "SELECT id, email, format, file, orig FROM ConvRequests WHERE worker = :worker ORDER BY id ASC LIMIT 1";

    $got_info = false;
    $conv_id = 0;
    $conv_email = "";
    $conv_format = "";
    $conv_file = "";
    $conv_orig = "";

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

    return true;
}

// $p_dbAccess: host, user, pwd, dbname
// $p_worker - int
// $p_jobInfo: id
function update_conv_info($p_dbAccess, $p_worker, &$p_jobInfo) {

    $reqStrUpd = "UPDATE ConvRequests SET state = 'done', worker = -1 WHERE id = :id";

    $db_user = $p_dbAccess['host'];
    $db_host = $p_dbAccess['user'];
    $db_pwd = $p_dbAccess['pwd'];
    $db_name = $p_dbAccess['dbname'];

    $job_id = $p_jobInfo["id"];

    try {
        $dbh = new PDO(
            "mysql:host=$db_host;dbname=$db_name", 
            "$db_user",
            "$db_pwd",
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
        $sthUpd = $dbh->prepare($reqStrUpd);

        $sthUpd->bindValue(':id', (string) $job_id, PDO::PARAM_INT);
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
}

// $p_pathsInfo: output_dir, input_dir
// $p_runtimeInfo: incl_dir, plug_dir
// $p_pluginsInfo: $format: required_files, class_name
// $p_jobInfo: format, name
function run_conversion($p_pathsInfo, $p_runtimeInfo, $p_pluginsInfo, $p_jobInfo) {
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
    $local_name = $p_jobInfo["name"];

    $path_input = $dir_input . $local_name;
    $path_output = $dir_output . $local_name;

    try {
        if ($req_files) {
            foreach ($req_files as $one_req) {
                require_once($plug_dir . $format . $one_req);
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

    try {
        unlink($path_input);
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
}

// $p_adminInfo: email
// $p_jobInfo: (id, email, format, file, orig)
function send_notifications($p_adminInfo, $p_jobInfo) {
    $to_user = $p_jobInfo["email"];
    $subject_user = "Newscoop conversion processed";
    $message_user = '
Your file \'' . $p_jobInfo["orig"] . '\' has been converted into NewsML.
It is available at ' . $p_adminInfo["download"] . '?newsml=' . $p_jobInfo["id"] . '
';

    $to_admin = $p_adminInfo["email_to"];
    $subject_admin = "CMS conversion processed";
    $message_admin = '
conversion:
job/db id:  ' . $p_jobInfo["id"] . '
user email: ' . $p_jobInfo["email"] . '
cms format: ' . $p_jobInfo["format"] . '
original:   ' . $p_jobInfo["orig"] . '
converted:  ' . $p_jobInfo["file"] . '
';

    mail($to_user, $subject_user, $message_user);
    mail($to_admin, $subject_admin, $message_admin);

}

$res = take_lock($converter_locks, $worker);
if (!$res) {
    exit(1);
}

$job_info = array();

$converted = 0;

while($res) {

    if ($res) {
        $res = take_conv_info($converter_db_access, $worker, $job_info);
    }

    if ($res) {
        $converted += 1;
    }

    if ($res) {
        $res = run_conversion($converter_paths, $converter_runtime, $converter_plugins, $job_info);
    }

    if ($res) {
        $res = send_notifications($converter_admin, $job_info);
    }

    if ($res) {
        $res = update_conv_info($converter_db_access, $worker, $job_info);
    }

}

release_lock($converter_locks, $worker);

$exit_val = 1;
if ($converted) {
    $exit_val = 0;
}

exit ($exit_val);

?>
