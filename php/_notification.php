<?php
session_start();
$path_to_file = "../temp_bolprocessor/messages/_notification";
if(!empty($path_to_file)) {
	$handle = fopen($path_to_file,'w');
	my_file_put_contents($path_to_file,"ok"); // Probably better not to create empty files
	fclose($handle);
	chmod($path_to_file,0755);
	}
die();
?>
