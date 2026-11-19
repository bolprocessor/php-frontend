<?php
require_once("_basic_tasks.php");
$url_this_page = "file_list.php?".$_SERVER["QUERY_STRING"];
require_once("_header.php");
display_darklight();

if(isset($_GET['dir'])) $dir = urldecode($_GET['dir']);
else $dir = '';
if(isset($_GET['extension'])) $extension = urldecode($_GET['extension']);
else $extension = '';

if($dir <> '') {
	// 2026-11-19 Both Csound (or tonality) resource folders are listed together - by Claude
	if(is_csound_resource_folder($dir)) $dircontent = array_keys(csound_resource_files());
	else if(is_tonality_resource_folder($dir)) $dircontent = array_keys(resource_files("tonality"));
	else $dircontent = scandir($dir);
	foreach($dircontent as $file) {
		if($file[0] == '.') continue;
		$table = explode('.',$file);
		$ext = end($table);
		if($extension == '' OR $ext == $extension)
			echo $file."<br />";
		}
	}
?>