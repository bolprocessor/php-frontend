<?php
// Creating files in the "temp" folder
// This script is called by the createFile() Javascript
// It is helpful to create a "_stop" file when the STOP button is clicked, or "_pause", "_continue" and "_panic" files as well.
flush();
$path_to_file = $_GET['path_to_file'] ?? '';
if(!empty($path_to_file)) {
	file_put_contents($path_to_file,"ok");
	chmod($path_to_file,0666);
	}
die();
?>
