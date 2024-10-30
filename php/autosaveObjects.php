<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		$result = SaveObjectPrototypes(FALSE,$dir,$filename,$temp_folder,FALSE);
		if($result <> "skipped") echo "&nbsp;&nbsp;&nbsp;<span class=\"red-text\">".date('H\hi')."</span> ➡ <span class=\"red-text\">Autosaved all prototypes in</span> “<span class=\"green-text\">".$filename."</span>”";
		}
	}
?>
