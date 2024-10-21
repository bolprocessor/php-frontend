<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		$result = SaveObjectPrototypes(FALSE,$dir,$filename,$temp_folder,FALSE);
		if($result <> "skipped") echo "&nbsp;&nbsp;&nbsp;<font color=\"red\">".date('H\hi')."</font> ➡ <font color=\"red\">Autosaved all prototypes in</font> “<span class=\"blue-text\">".$filename."</span>”";
		}
	}
?>
