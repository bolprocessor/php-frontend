<?php
require_once("_basic_tasks.php");
$url_this_page = "preview_samples.php";
// require_once("_header.php");

$application_path = $bp_application_path;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';

$content = @file_get_contents($file);
if(trim($content) == '') {
	echo "No sample in this file";
	die();
	}
$content = str_replace("\r","\n",$content);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
$table = explode(chr(10),$content);
$jmax = count($table);
for($j = 0; $j < $jmax; $j++) {
	$line = $table[$j];
	if(trim($line) == '') continue;
	echo $line."<br />";
	}
?>
