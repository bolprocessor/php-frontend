<?php
// session_start(); => Don't use it here, because it would delay the file creation while the console is running.
// Creating files in the "temp" folder
// This script is called by the createFile() Javascript
// It is helpful to create a "_stop" file when the STOP button is clicked

// set_time_limit(0);
flush();
$path_to_file = $_GET['path_to_file'] ?? '';
if(!empty($path_to_file)) {
	file_put_contents($path_to_file,"ok");
	chmod($path_to_file,0666);
//	exec('sync'); // This makes it easier for the console to find the file, see the stop() function in ConsoleMain.c
	}
die();
?>
