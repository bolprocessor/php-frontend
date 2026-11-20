<?php
// Buffers of the real-time Csound server for this machine (2026-11-20), set with the "settings" button of project pages.
// They are stored in _settings.php, which updates of the Bol Processor don't change. Empty values mean the defaults of this system.
// POST action=save with 'software' (-b) and 'hardware' (-B), or action=default. Always returns the current values as JSON.
ob_start(); // _basic_tasks.php must not add anything to the JSON answer
require_once("_basic_tasks.php");
ob_end_clean();
session_write_close();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$error = '';
$action = $_POST['action'] ?? '';
if($action == "save") {
	$software = trim((string)($_POST['software'] ?? ''));
	$hardware = trim((string)($_POST['hardware'] ?? ''));
	$error = csoundrt_valid_buffers($software,$hardware);
	if($error == '') {
		save_settings("csoundrt_software_buffer",intval($software));
		save_settings("csoundrt_hardware_buffer",intval($hardware));
		$csoundrt_software_buffer = intval($software);
		$csoundrt_hardware_buffer = intval($hardware);
		}
	}
else if($action == "default") {
	save_settings("csoundrt_software_buffer",'');
	save_settings("csoundrt_hardware_buffer",'');
	$csoundrt_software_buffer = $csoundrt_hardware_buffer = '';
	}
$buffers = csoundrt_buffers();
$default = csoundrt_default_buffers();
echo json_encode([
	"error" => $error,
	"software" => $buffers['software'],
	"hardware" => $buffers['hardware'],
	"user" => $buffers['user'],
	"default_software" => $default['software'],
	"default_hardware" => $default['hardware'],
	"system" => csoundrt_system(),
	"delay" => csoundrt_midi_delay($buffers)
	]);
?>
