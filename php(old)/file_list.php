<?php
require_once("_basic_tasks.php");
$url_this_page = "file_list.php?".$_SERVER["QUERY_STRING"];
require_once("_header.php");

if(isset($_GET['dir'])) $dir = urldecode($_GET['dir']);
else $dir = '';
if(isset($_GET['extension'])) $extension = urldecode($_GET['extension']);
else $extension = '';

if($dir <> '') {
	$dircontent = scandir($dir);
	foreach($dircontent as $file) {
		if($file[0] == '.') continue;
		$table = explode('.',$file);
		$ext = end($table);
		if($extension == '' OR $ext == $extension)
			echo $file."<br />";
		}
	}
?>