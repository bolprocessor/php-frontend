<?php
// Returns the date (milliseconds) of the current real-time MIDI performance, and its expected end.
// The console writes them to "../temp_bolprocessor/trace_<session>_<project>_time" (see WriteTimeFile() in ConsoleMain.c)
// "end" is 0 or -1 when the end is unknown.
session_write_close();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$file = $_GET['file'] ?? '';
// Only accept a plain file name created by the console in the temp folder
if($file === '' OR !preg_match('/^trace_[A-Za-z0-9():_.,-]+_time$/',$file) OR strpos($file,'..') !== FALSE) {
	echo json_encode(["ms" => null, "end" => null]);
	exit;
	}
$path = "../temp_bolprocessor/".$file;
clearstatcache(true,$path);
$ms = $end = null;
if(is_file($path)) {
	$content = trim((string)@file_get_contents($path));
	if(preg_match('/^([0-9]+)(?:\s+(-?[0-9]+))?$/',$content,$match)) {
		$ms = (int)$match[1];
		if(isset($match[2])) $end = (int)$match[2];
		}
	}
echo json_encode(["ms" => $ms, "end" => $end]);
