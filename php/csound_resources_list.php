<?php
require_once("_basic_tasks.php");
$url_this_page = "csound_resources_list.php?".$_SERVER["QUERY_STRING"];
require_once("_header.php");

$dircontent = scandir($dir_csound_resources);
foreach($dircontent as $file) {
	$table = explode('.',$file);
	$extension = end($table);
	if($extension == "orc")
		echo $file."<br />";
	}
?>