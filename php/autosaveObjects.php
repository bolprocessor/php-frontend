<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		$result = SaveObjectPrototypes(FALSE,$dir,$filename,$temp_folder,FALSE);
		if($result === "missing") {
			echo "<span class=\"red-text\"> ➡ CANNOT autosave: the temporary data of this page was deleted. Please close this page and</span> <a href=\"objects.php?file=".urlencode(str_replace($bp_application_path,'',$dir).$filename)."\">RELOAD</a> it!";
			}
		else if($result !== "skipped") echo "&nbsp;&nbsp;&nbsp;<span class=\"red-text\">".date('H\hi')."</span> ➡ <span class=\"red-text\">Autosaved all prototypes in</span> “<span class=\"green-text\">".$filename."</span>”";
		}
	}
?>
