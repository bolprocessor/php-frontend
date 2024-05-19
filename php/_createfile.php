<?php
// session_start(); => Don't use it here, because it would delay the file creation while the console is running.
// Creating files in the "temp" folder
// This script is called by the createFile() Javascript in "produce.php"
// It is helpful to create a "_stop" file when the STOP button is clicked
// session_write_close(); // Close the session for writing but keep it alive

set_time_limit(0);
$path_to_file = $_GET['path_to_file'] ?? '';
/* if(isset($_SESSION['pid'])) $pid = $_SESSION['pid']; // This works only if session_start() has been called.
else $pid = ''; */
if(!empty($path_to_file)) {
	$handle = fopen($path_to_file,'w');
	if(!empty($pid)) file_put_contents($path_to_file,"pid = ".$pid);
	else file_put_contents($path_to_file,"ok"); // Probably better not to create empty files
	fclose($handle);
	exec('sync'); // This makes it easier for the console to find the file, see the stop() function in ConsoleMain.c
	}
// Attempt to kill the console!, doesn't work
/* if(!empty($pid)) {
	$command = "kill -9 ".$pid;
	if(windows_system()) {
		$command = preg_replace("'(?<!^) '","^ ",$command);
		}
	exec($command);
	} */
die();
?>
