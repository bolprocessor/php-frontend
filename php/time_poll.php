<?php
// Returns the date (milliseconds) of the current real-time MIDI performance.
// The console writes it to "../temp_bolprocessor/trace_<session>_<project>_time" (see WriteTimeFile() in ConsoleMain.c)
session_write_close();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$file = $_GET['file'] ?? '';
// Only accept a plain file name created by the console in the temp folder
if($file === '' OR !preg_match('/^trace_[A-Za-z0-9():_.,-]+_time$/',$file) OR strpos($file,'..') !== FALSE) {
	echo json_encode(["ms" => null]);
	exit;
	}
$path = "../temp_bolprocessor/".$file;
clearstatcache(true,$path);
$ms = null;
if(is_file($path)) {
	$content = trim((string)@file_get_contents($path));
	if($content !== '' AND ctype_digit($content)) $ms = (int)$content;
	}
echo json_encode(["ms" => $ms]);
