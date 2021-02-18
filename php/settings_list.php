<?php
require_once("_basic_tasks.php");
require_once("_header.php");

$dir = urldecode($_GET['dir']);
$url_this_page = urldecode($_GET['thispage']);

echo $url_this_page."<br />";

$dircontent = scandir($dir);
foreach($dircontent as $file) {
	$table = explode('.',$file);
	$prefix = $table[0];
	$extension = end($table);
	if($extension == "bpse" OR $prefix == "-se")
		echo "<a target=\"_blank\" href=\"".$url_this_page."&newsettings=".urlencode($file)."\">".$file."</a><br />";
	}
?>