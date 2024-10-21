<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		$result = SaveCsoundInstruments(FALSE,$dir,$filename,$temp_folder,FALSE);
		if($result == "locked") {
			echo "<font color=\"red\"> ➡ CANNOT autosave: please</font> <a href=\"csound.php?file=".urlencode($csound_resources.SLASH.$filename)."\">RELOAD</a> this page!";
			}
		else if($result <> "skipped") echo "&nbsp;&nbsp;&nbsp;<font color=\"red\">".date('H\hi')."</font> ➡ <font color=\"red\">Autosaved all instruments in</font> “<span class=\"blue-text\">".$filename."</span>”";
		}
	}
?>
