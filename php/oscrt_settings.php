<?php
// Destination of real-time OSC for this machine (2026-11-21), set with the form of project pages (see oscrt_format_notes() in _oscrt.php).
// Stored in _settings.php ($oscrt_host, $oscrt_port). POST 'host' and 'port'. Returns the current values as JSON.
ob_start(); // _basic_tasks.php must not add anything to the JSON answer
require_once("_basic_tasks.php");
ob_end_clean();
session_write_close();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$host = trim((string)($_POST['host'] ?? ''));
$port = trim((string)($_POST['port'] ?? ''));
if($host == '') $host = OSC_RT_DEFAULT_HOST;
$error = oscrt_valid($host,$port);
if($error == '') {
	save_settings("oscrt_host",$host);
	save_settings("oscrt_port",intval($port));
	$oscrt_host = $host;
	$oscrt_port = intval($port);
	}
$d = oscrt_destination();
echo json_encode(["error" => $error,"host" => $d['host'],"port" => $d['port']]);
?>
