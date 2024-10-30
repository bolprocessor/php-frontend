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
			echo "<span class=\"red-text\"> ➡ CANNOT autosave: please</span> <a href=\"csound.php?file=".urlencode($csound_resources.SLASH.$filename)."\">RELOAD</a> this page!";
			}
		else if($result <> "skipped") echo "&nbsp;&nbsp;&nbsp;<span class=\"red-text\">".date('H\hi')."</span> ➡ <span class=\"red-text\">Autosaved all instruments in</span> “<span class=\"green-text\">".$filename."</span>”";
		}
	}
?>
