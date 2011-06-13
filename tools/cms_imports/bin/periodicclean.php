#!/usr/bin/env php
<?php

/*

  This shall be run via cron, not frequently.

  Removes old data from database and old converted files.

*/

// taking configurations
$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_dba.php');
require_once($conf_dir . 'converter_inf.php');
require_once($conf_dir . 'converter_loc.php');

/**
 * Removes old converted files
 *
 * @param mixed $p_pathsInfo
 * @param int $p_days
 * @return boolean
 */
function remove_old_files($p_pathsInfo, $p_days) {
    $p_days = (int) $p_days;
    if (0 >= $p_days) {
        return true;
    }

    $dir_output = $p_pathsInfo["output_dir"];

    $dir_arr =  scandir($dir_output);
    if (!is_array($dir_arr)) {
        return false;
    }

    // threshold date
    $del_date = time() - (86400 * $p_days);

    // going over the output files
    foreach ($dir_arr as $one_name) {
        if ("." == $one_name[0]) {
            continue;
        }

        $one_path = $dir_output . $one_name;
        if (!is_file($one_path)) {
            continue;
        }

        $mod_time = filemtime($one_path);
        if ($mod_time < $del_date) {
            try {
                unlink($one_path);
            }
            catch (Exception $exc) {
                continue;
            }
        }

    }

    return true;
} // fn remove_old_files

/**
 * Removes old job requests info
 *
 * @param mixed $p_dbAccess
 * @param int $p_days
 * @return boolean
 */
function clean_database($p_dbAccess, $p_days) {
    $p_days = (int) $p_days;
    if (0 >= $p_days) {
        return true;
    }

    $reqStr = "DELETE FROM ConvRequests WHERE CAST(created AS DATE) < (select DATE_SUB(curdate(), INTERVAL :created DAY))";

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
        $sth->bindValue(':created', (string) $p_days, PDO::PARAM_STR);

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

} // fn clean_database

// taking the threshold interval
$age_days_fs = $converter_cron["fsclean"];
$age_days_db = $converter_cron["dbclean"];

// running the cleaning itself
$res1 = remove_old_files($converter_paths, $age_days_fs);
$res2 = clean_database($converter_db_access, $age_days_db);

if ($res1 && $res2) {
    exit(0);
}
exit(1);

?>
