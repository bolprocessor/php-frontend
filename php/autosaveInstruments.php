<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		$result = SaveCsoundInstruments(FALSE,$dir,$filename,$temp_folder);
		if($result == "locked") {
			echo "<font color=\"red\"> ➡ CANNOT autosave: please</font> <a href=\"csound.php?file=".urlencode($csound_resources.SLASH.$filename)."\">RELOAD</a> this page!";
			}
		else echo "<font color=\"red\">".date('H\hi - s \s\e\c\o\n\d\s')."</font> ➡ <font color=\"red\">Autosaved Csound resources in</font> “<font color=\"blue\">".$filename."</font>”";
		}
	}
?>
