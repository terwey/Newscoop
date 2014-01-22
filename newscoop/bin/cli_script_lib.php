<?php

function camp_is_readable($p_fileName)
{
    if (!is_readable($p_fileName)) {
        echo "\nThis script requires access to the file $p_fileName.\n";
        echo "Please run this script as a user with appropriate privileges.\n";
        echo "Most often this user is 'root'.\n\n";
        return false;
    }
    return true;
} // fn camp_is_readable


/**
 * Execute a command in the shell.
 *
 * @param string $p_cmd
 * @param string $p_errMsg - default empty
 * @param boolean $p_printOutput - default true
 * @param boolean $p_silent - default false
 */
function camp_exec_command($p_cmd, $p_errMsg = "",
                           $p_printOutput = true, $p_silent = false)
{
    $p_cmd .= " 2> /dev/null";
    @exec($p_cmd, $output, $result);
    if ($result != 0) {
        if ($p_silent) {
            exit(1);
        }
        if (!$p_printOutput) {
            $output = array();
        }
        if ($p_errMsg != "") {
            $my_output[] = $p_errMsg;
            $output = array_merge($my_output, $output);
        }
        camp_exit_with_error($output);
    }
} // fn camp_exec_command


/**
 * So that it also works on windows in the future...
 *
 * @return string
 */
function camp_readline()
{
    if (!isset($GLOBALS['stdin'])) {
        $GLOBALS['stdin'] = fopen("php://stdin", "r");
    }
    $in = fgets($GLOBALS['stdin'], 4094); // Maximum windows buffer size
    return $in;
} // fn camp_readline


/**
 * Create a directory.  If this fails, print out the given error
 * message or a default one.
 *
 * @param string $p_dirName
 * @param string $p_msg
 * @return void
 */
function camp_create_dir($p_dirName, $p_msg = "")
{
    if ($p_msg == "") {
        $p_msg = "Unable to create directory $p_dirName.";
    }
    if (!is_dir($p_dirName) && !mkdir($p_dirName)) {
        camp_exit_with_error($p_msg);
    }
} // fn camp_create_dir


/**
 * @return boolean
 */
function camp_is_empty_dir($p_dirName)
{
    $cnt = 0;
    if(is_dir($p_dirName) ){
        $files = opendir($p_dirName);
        while ($file = @readdir($files)) {
            $cnt++;
            if ($cnt > 2) {
                return false;
            }
        }
        return true;
    }

    return false;
} // fn camp_is_empty_dir


/**
 *
 */
function camp_read_files($p_startDir = '.')
{
    $files = array();
    if (is_dir($p_startDir)) {
        $fh = opendir($p_startDir);
        while(($file = readdir($fh)) !== false) {
            // loop through the files, skipping . and .., and
            // recursing if necessary
            if (strcmp($file, '.') == 0 || strcmp($file, '..') == 0) {
                continue;
            }
            $filePath = $p_startDir . '/' . $file;
            if (is_dir($filePath)) {
                $files = array_merge($files, camp_read_files($filePath));
            } else {
                array_push($files, $filePath);
            }
        }
        closedir($fh);
    } else {
        // false if the function was called with an invalid
        // non-directory argument
        $files = false;
    }

    return $files;
} // fn camp_read_files


/**
 * Remove the specified directory and everything underneath it.
 *
 * @param string $p_dirName
 * @param string $p_msg
 * @param array $p_skip
 *      Array of files or directories to preserve
 * @return void
 */
function camp_remove_dir($p_dirName, $p_msg = "", $p_skip = array())
{
    $p_dirName = str_replace('//', '/', $p_dirName);
    $dirBaseName = trim($p_dirName, '/');
    if ($p_dirName == "/" || $dirBaseName == ''
    || $dirBaseName == '.' || $dirBaseName == '..'
    || (strpos($dirBaseName, '/') === false && $p_dirName[0] == '/')) {
        camp_exit_with_error("camp_remove_dir: Bad directory name '$p_dirName'.");
    }
    if (empty($p_msg)) {
        $p_msg = "Unable to remove directory '$p_dirName'";
    }

    $removeDir = true;
    if (strrpos($p_dirName, '*') == (strlen($p_dirName) - 1)) {
        $p_dirName = substr($p_dirName, 0, strlen($p_dirName) - 1);
        $removeDir = false;
    }
    $p_dirName = rtrim($p_dirName, '/');

    $dirContent = scandir($p_dirName);
    if ($dirContent === false) {
        camp_exit_with_error("Unable to read the content of the directory '$p_dirName'.");
    }
    foreach ($dirContent as $file) {
        if (in_array($file, $p_skip)) {
                continue;
        }
        if ($file == '.' || $file == '..') {
            continue;
        }
        $filePath = "$p_dirName/$file";
        if (is_dir($filePath)) {
            camp_remove_dir($filePath);
            continue;
        }
        if (!unlink($filePath)) {
            camp_exit_with_error("Unable to delete the file '$filePath'.");
        }
    }
    if ($removeDir) {
        rmdir($p_dirName);
    }
} // fn camp_remove_dir


/**
 * Recursively copy the given directory or file to the given
 * destination.
 *
 * @param string $p_src
 * @param string $p_dest
 * @param string $p_msg
 * @return void
 */
function camp_copy_files($p_src, $p_dest, $p_msg = "")
{
    if ($p_msg == "") {
        $p_msg = "Unable to copy file/dir $p_src to $p_dest.";
    }
    $command = "cp -R $p_src $p_dest";
    camp_exec_command($command, $p_msg);
} // fn camp_copy_files


/**
 * Rename the given file so it has a time stamp embedded in its name.
 * If there is an error, a message will be placed in the $p_output
 * variable.
 *
 * @param string $p_filePath
 * @param string $p_output
 * @return boolean
 */
function camp_backup_file($p_filePath, &$p_output)
{
    if (!is_file($p_filePath)) {
        $p_output = "File $p_filePath does not exist.";
        return 1;
    }
    $dir_name = dirname($p_filePath);
    if (!($file_stat = @stat($p_filePath))) {
        $p_output = "Unable to read file $p_filePath data.";
        return 1;
    }
    $file_name = basename($p_filePath);
    $extension = pathinfo($p_filePath, PATHINFO_EXTENSION);
    $change_time = strftime("%Y-%m-%d-%H", $file_stat['ctime']);
    $new_name = "$base_name-$change_time$extension";

    if (is_file("$dir_name/$new_name")) {
        return 0;
    }

    if (!rename($p_filePath, "$dir_name/$new_name")) {
        $p_output = "Unable to rename file $p_filePath";
        return 1;
    }
    return 0;
} // fn camp_backup_file


/**
 * Tar the given source file/dir into the given destination directory and
 * give it the name $p_fileName.  If there is an error, return an error
 * message in the $p_output variable.
 *
 * @param mixed $p_sourceFile
 * @param string $p_destDir
 * @param string $p_fileName
 * @param string $p_output
 * @return int
 */
function camp_archive_file($p_sourceFile, $p_destDir, $p_fileName, &$p_output)
{
	$output_file_name = "$p_destDir/$p_fileName.tar.gz";
    $fileStr = escapeshellarg(basename($p_sourceFile));
    $source_dir = dirname($p_sourceFile);
    $currentDir = getcwd();
    chdir($source_dir);
    $cmd = "tar czf " . escapeshellarg($output_file_name) . " $fileStr 2>&1 >/dev/null";
    @exec($cmd, $p_output, $result);
	// remove false tar.gz file if partially created
	if ($result) {
		// catch problems for if the file is not there
		try {
			@unlink($output_file_name);
		}
		catch (Exception $exc) {}
	}
    chdir($currentDir);
    return $result;
} // fn camp_archive_file


/**
 * Dump the given database into the file $p_destFile.  If there is an
 * error, it will be returned in $p_output.
 *
 * @param string $p_dbName
 * @param string $p_destFile
 * @param string $p_output
 * @return int
 */
function camp_backup_database($p_dbName, $p_destFile, &$p_output,
                              $p_customParams = array(), $p_omitGeoNames = true)
{
    global $Campsite;

    $user = $Campsite['DATABASE_USER'];
    $password = $Campsite['DATABASE_PASSWORD'];
    $cmd = "%s --add-drop-table -c -Q --extended-insert --user=$user --host="
        . $Campsite['DATABASE_SERVER_ADDRESS']
        . " --port=" . $Campsite['DATABASE_SERVER_PORT'];
    if ($password != "") {
        $cmd .= " --password=$password";
    }
	if ($p_omitGeoNames) {
        $cmd .= ' --ignore-table=' . $p_dbName . '.CityNames --ignore-table=' . $p_dbName . '.CityLocations';
	}
    $cmd .= ' ' . implode(' ', $p_customParams);
    $cmd .= " $p_dbName > $p_destFile";
    $p_output = array();
    @exec( sprintf( $cmd, 'mysqldump' ), $p_output, $result);
    if( $result !== 0 ) //one more try with full path for mother russia
    {
        $newCmd = exec( 'which mysqldump' );
        if( trim( $newCmd ) != '' ) {
            @exec( sprintf( $cmd, $newCmd ), $p_output, $result );
        }
    }
    switch( $result )
    {
        case 1	:
        case 2	: $error = "General error"; break;
        case 126 :	$error = "Command invoked cannot be executed. Permission problem or command is not an executable"; break;
        case 127 : $error = "'mysqldump' Command not found. Possible problem with \$PATH or a typo"; break;
        case 128 : $error =	"Invalid argument to exit"; break;
        default : $error = false;
    }
    $additionalFile = $Campsite['CAMPSITE_DIR'] . '/bin/mysql-dump-ext.sql';
    if (file_exists($additionalFile)) {
        @exec('cat ' . $additionalFile . '>>' .$p_destFile, $p_output, $result);
    }

    if( !$error ) {
        return $result;
    }
    else {
       return $error;
    }
} // fn camp_backup_database


/**
 * Print out the given message and exit the program with an error code.
 *
 * @param string $p_errorStr
 * @return void
 */
function camp_exit_with_error($p_errorStr)
{
    if (function_exists('__exit_cleanup')) {
        __exit_cleanup();
    }
    if (is_array($p_errorStr)) {
        $p_errorStr = implode("\n", $p_errorStr);
    }
    echo "\nERROR!\n$p_errorStr\n";
    exit(1);
} // fn camp_exit_with_error


/**
 * Connect to the MySQL database.
 *
 * @param string $p_dbName
 * @return void
 */
function camp_connect_to_database($p_dbName = "")
{
    global $Campsite;

    $db_user = $Campsite['DATABASE_USER'];
    $db_password = $Campsite['DATABASE_PASSWORD'];
    $res = mysql_connect($Campsite['DATABASE_SERVER_ADDRESS'] . ":"
        . $Campsite['DATABASE_SERVER_PORT'], $db_user, $db_password);
    if (!$res) {
        camp_exit_with_error("Unable to connect to the database server.");
    }

    if ($p_dbName != "" && !mysql_select_db($p_dbName)) {
        camp_exit_with_error("Unable to select database '$p_dbName'.");
    }
    mysql_query("SET NAMES 'utf8'");
} // fn camp_connect_to_database


/**
 * Return TRUE if the database contains no data.
 *
 * @param string $db_name
 * @return boolean
 */
function camp_is_empty_database($p_dbName)
{
    if (!mysql_select_db($p_dbName)) {
        camp_exit_with_error("camp_is_empty_database: can't select the database");
    }
    if (!($res = mysql_query("show tables"))) {
        camp_exit_with_error("camp_is_empty_database: can't read tables");
    }
    return (mysql_num_rows($res) == 0);
} // fn camp_is_empty_database


/**
 * Drop all tables in the given database.
 *
 * @param string $p_dbName
 * @return void
 */
function camp_clean_database($p_dbName)
{
    $preserve_tables = array_map('strtolower', array('CityNames', 'CityLocations'));

    if (!mysql_select_db($p_dbName)) {
        camp_exit_with_error("camp_clean_database: can't select the database");
    }
    if (!($res = mysql_query("show tables"))) {
        camp_exit_with_error("Can not clean the database: can't read tables");
    }
    while ($row = mysql_fetch_row($res)) {
        $table_name = $row[0];
        if (in_array(strtolower($table_name), $preserve_tables)) {
            continue;
        }

        mysql_query("drop table `" . mysql_escape_string($table_name) . "`");
    }
} // fn camp_clean_database


/**
 * Return TRUE if the database exists.
 *
 * @param string $p_dbName
 * @return boolean
 */
function camp_database_exists($p_dbName)
{
    global $g_ado_db;
    return $g_ado_db->hasDatabase($p_dbName);
} // fn camp_database_exists

/**
 * Converts the current database to UTF-8 encoding.
 * @param $p_log_file
 * @return bool - true if success
 */
function camp_utf8_convert($p_log_file = null, &$p_skipped = array())
{
    global $Campsite;

    // Whether logging or not
    $do_log = (!empty($p_log_file)) ? true : false;
    if ($do_log) {
        if (!file_exists($p_log_file) || !is_writable($p_log_file)) {
            $do_log = false;
            return "Log file is missing or not writable!";
        }
    }

    // Sets the character set for the database
    $sql = 'ALTER DATABASE `' . $Campsite['DATABASE_NAME'] . '` CHARACTER SET utf8';
    if (!($res = mysql_query($sql))) {
        return "Unable to convert database character set to utf8.";
    }
    if ($do_log) {
        $log_text = $sql . "\n";
    }

    // Sets the character set for the client
    $sql = 'SET character_set_client = utf8';
    if (!($res = mysql_query($sql))) {
        return "Unable to convert the client character set to utf8.";
    }
    if ($do_log) {
        $log_text .= $sql . "\n";
    }

    // Deletes data from ArticleIndex and KeywordIndex tables to fix duplicate values
    $sql = 'DELETE FROM `' . $Campsite['DATABASE_NAME'] . '`.ArticleIndex';
    if (!($res = mysql_query($sql))) {
        return "Unable to remove article index data.";
    } elseif ($do_log) {
        $log_text .= $sql . "\n";
    }

    $sql = 'DELETE FROM `' . $Campsite['DATABASE_NAME'] . '`.KeywordIndex';
    if (!($res = mysql_query($sql))) {
        return "Unable to remove keyword index data.";
    } elseif ($do_log) {
        $log_text .= $sql . "\n";
    }

    $sql = 'UPDATE `' . $Campsite['DATABASE_NAME'] . "`.Articles SET IsIndexed = 'N'";
    if (!($res = mysql_query($sql))) {
        return "Unable to update article table data.";
    } elseif ($do_log) {
        $log_text .= $sql . "\n";
    }

    // Builds ALTER TABLE sql queries for all database tables
    $sql = "SELECT CONCAT('ALTER TABLE `', table_schema, '`.`', \n"
         . "  table_name, '` CONVERT TO CHARACTER SET utf8') \n"
         . "FROM information_schema.tables \n"
         . "WHERE table_schema = '" . mysql_real_escape_string($Campsite['DATABASE_NAME']) . "'";
    $sqlSentences = array();
    $res = mysql_query($sql);
    while ($row = mysql_fetch_row($res)) {
        $sqlSentences[] = $row[0];
    }

    foreach ($sqlSentences as $sentence) {
        if (!($res = mysql_query($sentence))) {
            return "Unable to convert data to UTF-8 on query:\n$sentence";
        } elseif ($do_log) {
            $log_text .= $sentence . "\n";
        }
    }

    $sql = "SELECT table_name, column_name, \n"
         . "  REPLACE(column_type, 'binary', 'char') AS column_type, \n"
         . "is_nullable, column_default \n"
         . "FROM information_schema.columns \n"
         . "WHERE table_schema = '" . mysql_real_escape_string($Campsite['DATABASE_NAME']) . "' \n"
         . "  AND data_type LIKE 'varbinary%'";
    $res = mysql_query($sql);
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
        $table_name = $row['table_name'];
        $column_name = $row['column_name'];
        $column_type = $row['column_type'];
        $is_nullable = strtolower($row['is_nullable']) != 'no';
        $nullDefinition = $is_nullable ? '' : 'NOT NULL';
        $column_default = is_null($row['column_default']) ? 'NULL' : "'" . $row['column_default'] . "'";
        $sql = "ALTER TABLE `$table_name` MODIFY `$column_name` \n"
             . "  $column_type $nullDefinition DEFAULT $column_default";
        if (!mysql_query($sql)) {
            if ($table_name == 'Articles' && $column_name == 'Name') {
                return "Unable to convert data to UTF-8 on query:\n$sql";
            }
            $p_skipped[] = $sql;
        } elseif ($do_log) {
            $log_text .= $sql . "\n";
        }
    }

    // Writes Log file
    if ($do_log) {
        if (@file_put_contents($p_log_file, $log_text) === false) {
            return "Couldn't write Log file.";
        }
    }

    return true;
} // fn camp_utf8_convert


/**
 * Sets the encoding to UTF8 for the given encoding in the SQL
 * dump file.
 * @param $p_inDumpFile - source dump file full path
 * @param $p_outDumpFile - destination dump file full path
 * @param $p_fromEncoding - encoding from which to convert to UTF8
 * @return bool - true if successful
 */
function camp_change_dump_encoding($p_inDumpFile, $p_outDumpFile,
                                   $p_fromEncoding)
{
    $inFile = fopen($p_inDumpFile, "r");
    if (!$inFile) {
        camp_exit_with_error("Unable to open the source dump file $p_inDumpFile!");
    }
    $outFile = fopen($p_outDumpFile, 'w');
    if (!$outFile) {
        camp_exit_with_error("Unable to open the destination dump file $p_outDumpFile!");
    }
    while (!feof($inFile)) {
        $line = fgets($inFile);
        $pattern = "/SET\s+NAMES\s+[']?${p_fromEncoding}[']?([^_])/i";
        $replacement = 'SET NAMES utf8${1}';
        $line = preg_replace($pattern, $replacement, $line);
        $pattern = "/CHARSET\s*=\s*[']?${p_fromEncoding}[']?([^_])/i";
        $replacement = 'CHARSET=utf8${1}';
        $line = preg_replace($pattern, $replacement, $line);
        if (fputs($outFile, $line) === false) {
            camp_exit_with_error("Unable to write the dump file $p_outDumpFile!");
        }
    }
    fclose($inFile);
    fclose($outFile);
    return true;
}


/**
 * Returns the server default character set.
 * @return string or false if error
 */
function camp_get_server_charset()
{
    $sql = "SHOW VARIABLES LIKE 'character_set_server'";
    $res = mysql_query($sql);
    $row = mysql_fetch_array($res, MYSQL_ASSOC);
    if (!$row) {
        return false;
    }
    return $row['Value'];
}


/**
 * Returns true if the given charset was valid.
 * @param $p_charset
 * @return bool
 */
function camp_valid_charset($p_charset)
{
    $sql = "SHOW CHARACTER SET LIKE '$p_charset'";
    $res = mysql_query($sql);
    $row = mysql_fetch_array($res, MYSQL_ASSOC);
    if (!$row) {
        return false;
    }
    return true;
}


/**
 * Returns an array of all the database server charsets.
 * @return array
 */
function camp_get_all_charsets()
{
    $charsets = array();
    $sql = "SHOW CHARACTER SET";
    $res = mysql_query($sql);
    while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
        $charsets[$row['Charset']] = $row['Description'];
    }
    return $charsets;
}


/**
 * Restores the Campsite database from the given dump file.
 * @param $p_sqlFile - dump file
 * @return bool - true on success
 */
function camp_restore_database($p_sqlFile, $p_silent = false)
{
    global $Campsite;

     $cmd = "%s -u " . $Campsite['DATABASE_USER'] . " --host="
    . $Campsite['DATABASE_SERVER_ADDRESS'] . " --port="
    . $Campsite['DATABASE_SERVER_PORT']
    . ' --default-character-set=utf8';
    if ($Campsite['DATABASE_PASSWORD'] != "") {
        $cmd .= " --password=\"" . $Campsite['DATABASE_PASSWORD'] . "\"";
    }
    $cmd .= ' ' . $Campsite['DATABASE_NAME'] . " < $p_sqlFile";

    $cmdpath = 'mysql';
    exec( sprintf( $cmd, $cmdpath ), $o, $r );
    if( $r !== 0 )
    {
        $testcmd = exec( "which $cmdpath" );
        if( trim( $testcmd ) != '' ) {
            $cmdpath = exec( sprintf( $cmd, $testcmd ) );
        }
    }

    camp_exec_command( sprintf( $cmd, 'mysql' ), "Unable to import database. (Command: $cmd)",
                      true, $p_silent);
    return true;
}

function camp_search_db_rolls($roll_base_dir, $last_db_roll) {
    $rolls = array(); // roll_name => roll_path

    $roll_dir_names = scandir($roll_base_dir);
    if (empty($roll_dir_names)) {
        return $rolls;
    }

    $avoid_starts = array('.', '_');

    $some_top_files = false;
    foreach ($roll_dir_names as $one_rol_dir) {
        $cur_rol_path = $roll_base_dir . DIRECTORY_SEPARATOR . $one_rol_dir;
        if (is_file($cur_rol_path) && ('sql' == pathinfo($cur_rol_path, PATHINFO_EXTENSION))) {
            $some_top_files = true;
        }

        if ((!is_dir($cur_rol_path)) || (in_array(substr($one_rol_dir, 0, 1), $avoid_starts))) {
            continue;
        }

        if ((!empty($last_db_roll)) && ($one_rol_dir <= $last_db_roll)) {
            continue;
        }

        $rolls[$one_rol_dir] = $cur_rol_path;
    }

    ksort($rolls);

    if (empty($last_db_roll)) {
        if ($some_top_files) {
            $rolls = array_merge(array('.' => $roll_base_dir), $rolls);
        }
    }

    return $rolls;

} // fn camp_search_db_rolls


/**
 * Compares versions of Newscoop for upgrades
 * 3.1.0 before 3.1.x, 3.5.2 before 3.5.11
 * @param $p_version1
 * @param $p_version2
 * @return int
 */
function camp_version_compare($p_version1, $p_version2) {
    $version1 = "" . $p_version1;
    $version2 = "" . $p_version2;

    $ver1_arr = explode(".", $version1);
    $ver2_arr = explode(".", $version2);
    $ver1_len = count($ver1_arr);
    $ver2_len = count($ver2_arr);

    $ver_len = $ver1_len;
    if ($ver2_len < $ver_len) {$ver_len = $ver2_len;}

    for ($ind = 0; $ind < $ver_len; $ind++) {
        if ($ver1_arr[$ind] < $ver2_arr[$ind]) {return -1;}
        if ($ver1_arr[$ind] > $ver2_arr[$ind]) {return 1;}
    }

    if ($ver1_len < $ver2_len) {return -1;}
    if ($ver1_len > $ver2_len) {return 1;}

    return 0;
} // fn camp_version_compare

/**
 * Flushes output buffer.
 * @param $flush - boolean
 * @return void
 */
function flush_output($flush)
{
    if ($flush) {
        echo '<span></span>';
        flush();
    }
}


// auxiliary functions from CampInstallationBase.php, put here to not dor 'require' on all that files there

/**
 * Prepares commands out of sql files, for upgrades
 * @param $p_sqlFileName - name of sql file
 * @return array - the split file
 */
function camp_split_sql($p_sqlFileName)
{
    $sqlFile_raw = null;
    if(!($sqlFile_raw = file($p_sqlFileName))) {
        return false;
    }

    $sqlFile_arr = array();
    foreach ($sqlFile_raw as $one_row) {
        $one_row = trim($one_row);
        if (0 === strpos($one_row, "--")) {continue;}
        if (0 === strpos($one_row, "#")) {continue;}
        if ("" == $one_row) {continue;}

        $one_row_arr = explode("--", $one_row);
        $one_row = $one_row_arr[0];

        // we need to end 'system php ...' commands with semicolons
        $one_row_arr = array();
        foreach (explode(" ", $one_row) as $one_row_token) {
            $one_row_token = trim($one_row_token);
            if ("" == $one_row_token) {continue;}
            $one_row_arr[] = $one_row_token;
        }
        if (3 <= count($one_row_arr)) {
            if ("system php" == implode(" ", array_map("strtolower", array_slice($one_row_arr, 0, 2))) ) {
                if ((strlen($one_row) - 1) != strrpos($one_row, ";")) {
                    $one_row .= ";";
                }
            }
        }

        $sqlFile_arr[] = $one_row;
    }

    $sqlContent = implode("\n", $sqlFile_arr);

    $buffer = array ();
    $return = array ();
    $inString = false;

    for ($i = 0; $i < strlen($sqlContent) - 1; $i ++) {
        if ($sqlContent[$i] == ";" && !$inString) {
            $return[] = substr($sqlContent, 0, $i);
            $sqlContent = substr($sqlContent, $i +1);
            $i = 0;
        }

        if ($inString && ($sqlContent[$i] == $inString)
                && $buffer[1] != "\\") {
            $inString = false;
        } elseif (!$inString && ($sqlContent[$i] == '"'
                                 || $sqlContent[$i] == "'")
                      && (!isset ($buffer[0]) || $buffer[0] != "\\")) {
            $inString = $sqlContent[$i];
        }
        if (isset($buffer[1])) {
            $buffer[0] = $buffer[1];
        }

        $buffer[1] = $sqlContent[$i];
    }

    if (!empty($sqlContent)) {
        $return[] = $sqlContent;
    }

    return $return;
} // fn camp_split_sql

/**
 *
 */
function camp_import_dbfile($db_server, $db_username, $db_userpass, $db_database, $p_sqlFile, &$errorQueries)
{

    $db_conn = camp_connect_to_adodb($db_server, $db_username, $db_userpass, $db_database);

    $queries = camp_split_sql($p_sqlFile);
    if (!$queries) {return false;}

    $errors = 0;
    $errorQueries = array();
    foreach($queries as $query) {
        $query = trim($query);
        if (!empty($query) && ($query{0} != '#') && (0 !== strpos($query, "--"))) {

            if (0 !== strpos(strtolower($query), "system")) {
                if ($db_conn->Execute($query) === false) {
                    $errors++;
                    $errorQueries[] = $query;
                    $errorQueries[] = $db_conn->ErrorMsg();
                }
                continue;
            }

            // if it started via the system command
            $command_parts = array();
            foreach (explode(" ", $query) as $query_part) {
                $query_part = trim($query_part);
                if ("" != $query_part) {
                    $command_parts[] = $query_part;
                }
            }

            $command_script = "";
            $command_known = false;
            if (3 == count($command_parts)) {
                if ("php" == strtolower($command_parts[1])) {
                    $command_known = true;
                    $command_script = trim($command_parts[2], ";");
                }
            }
            if (!$command_known) {
                $errors++;
                $errorQueries[] = $query;
                continue;
            }

            $command_path = dirname($p_sqlFile);
            $command_path = camp_combine_paths($command_path, $command_script);

            // we had some problems on wrongly initialized db connectors for the executed php files, thus doing this (re)openning
            camp_connect_to_adodb($db_server, $db_username, $db_userpass, $db_database);

            require_once($command_path);
        }
    }

    // keep connection for plugin upgrade
    camp_connect_to_adodb($db_server, $db_username, $db_userpass, $db_database);

    return $errors;
} // fn camp_import_dbfile

/**
 * Puts together two paths, usually an absolute one (directory), plus a relative one (filename)
 * @param $p_dirFirst
 * @param $p_dirSecond
 * @return string
 */
function camp_combine_paths($p_dirFirst, $p_dirSecond)
{
    if (0 === strpos(strtolower($p_dirSecond), "/")) {
        return $p_dirSecond;
    }

    if (0 === strpos(strtolower($p_dirSecond), "./")) {
        $p_dirSecond = substr($p_dirSecond, 2);
        return $p_dirFirst . DIRECTORY_SEPARATOR . $p_dirSecond;
    }

    while (0 === strpos(strtolower($p_dirSecond), "../")) {
        $p_dirFirst = dirname($p_dirFirst);
        $p_dirSecond = substr($p_dirSecond, 3);
    }

    return $p_dirFirst . DIRECTORY_SEPARATOR . $p_dirSecond;
} // fn camp_combine_paths


/**
 * Connects to the specified DB
 * @param $db_host
 * @param $db_username
 * @param $db_userpass
 * @param $db_database
 * @return mixed
 */
function camp_connect_to_adodb($db_host, $db_username, $db_userpass, $db_database = "")
{
    if (isset($GLOBALS['g_ado_db']) && $GLOBALS['g_ado_db']->isConnected()) {
        return $GLOBALS['g_ado_db'];
    }

    $db_conn = ADONewConnection('mysql');
    $db_conn->SetFetchMode(ADODB_FETCH_ASSOC);

    @$db_conn->Connect($db_host, $db_username, $db_userpass);
    if (!$db_conn->isConnected()) {
        return null;
    }

    if ("" != $db_database) {
        $selectDb = $db_conn->SelectDB($db_database);
        if (!$selectDb) {
            return null;
        }

        $db_conn->Execute("SET NAMES 'utf8'");
    }

    $GLOBALS['g_ado_db'] = $db_conn;
    return $db_conn;
} // fn camp_connect_to_adodb

function camp_readable_size($p_bytes)
{
	$show_size = 0 + $p_bytes;
	$size_units = 'TiB';

	$unit_names = array('B', 'KiB', 'MiB', 'GiB');
	foreach ($unit_names as $cur_unit) {
		if ($show_size < 1024) {
			$size_units = $cur_unit;
			break;
		}
		$show_size = $show_size / 1024;
	}

	return number_format($show_size, 2) . ' ' . $size_units;
}

function camp_geodata_loaded($g_conn)
{
    $queryStr_loc = 'SELECT count(*) AS cnt FROM CityLocations';
    $queryStr_nam = 'SELECT count(*) AS cnt FROM CityNames';

    $got_data = true;
    foreach (array($queryStr_loc, $queryStr_nam) as $one_query) {
        $rows = $g_conn->GetAll($one_query);
        foreach ((array) $rows as $row) {
            if (0 == $row['cnt']) {
                $got_data = false;
                break;
            }
        }
    }

    return $got_data;
}

function camp_load_geodata($p_mysqlCmd, $p_dbConf)
{
    if (!file_exists($p_mysqlCmd)) {
        return false;
    }

    if ((!is_file($p_mysqlCmd)) && (!is_link($p_mysqlCmd))) {
        return false;
    }

    if (!is_executable($p_mysqlCmd)) {
        return false;
    }

    $last_wd = getcwd();
    $work_wd = $GLOBALS['g_campsiteDir'].DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'sql';
    chdir($work_wd);

    $dbAccess = $p_dbConf;

    $db_host = $dbAccess['host'];
    $db_port = $dbAccess['port'];
    $db_user = $dbAccess['user'];
    $db_pass = $dbAccess['pass'];
    $db_name = $dbAccess['name'];

    $access_params = '';
    $access_params .= ' -h ' . escapeshellarg($db_host);
    if (!empty($db_port)) {
        $access_params .= ' -P ' . escapeshellarg('' . $db_port);
    }
    $access_params .= ' -u ' . escapeshellarg($db_user);
    if (!empty($db_pass)) {
        $access_params .= ' -p' . escapeshellarg($db_pass);
    }
    $access_params .= ' -D ' . escapeshellarg($db_name);
    $cmd_string = escapeshellcmd($p_mysqlCmd) . $access_params . ' --local-infile=1 < ' . 'geonames.sql';
    $cmd_output = array();
    $cmd_retval = 0;
    exec($cmd_string, $cmd_output, $cmd_retval);

    chdir($last_wd);

    if (!empty($cmd_retval)) {
        return false;
    }

    return true;
}

?>
