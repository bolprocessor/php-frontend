<?php
require_once("_basic_tasks.php");

$test = FALSE;
if(isset($_GET['save'])) {
	$dir = $_POST['dir'];
	$filename = $_POST['filename'];
	$temp_folder = $_POST['temp_folder'];
	if(!$test) {
		SaveCsoundInstruments(FALSE,$dir,$filename,$temp_folder);
		echo "<font color=\"red\">".date('H\hi - s \s\e\c\o\n\d\s')."</font> ➡ <font color=\"red\">Autosaved all instruments in</font> “<font color=\"blue\">".$filename."</font>”";
		}
	}
?>
