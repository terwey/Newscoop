<?php

/*

  This shall be run via cron, probably frequently.

  Runs jobs that were not triggered by a web submit.

*/

// taking configurations
$conf_dir = dirname(dirname(__FILE__)) . "/conf/";
require_once($conf_dir . 'converter_inf.php');

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
    $log_file = $p_runtimeInfo["log_file"];

    // we have just a one worker process by default
    $worker = 1;

    try {
        passthru("$script_shell $script_name $conf_dir $worker >> $log_file 2>&1 &");
    }
    catch (Exception $exc) {
        return false;
    }

    return true;
} // start_converter

// to start a worker
$res = start_converter($converter_runtime);

if (!$res) {
    exit(1);
}
exit(0);

?>
