<?php
// 2026-11-24 Python programs receiving real-time OSC, e.g. the robot "osc_resources/dancer.py" (see oscrt_program_line() in _oscrt.php) - by Claude
// POST 'action': "start" (with 'file', the name of the program), "stop", "check" (look for Python again), or "path" (with 'path', a Python typed by the user).
// Returns JSON: 'ok', 'message' (HTML) and 'running' (name of the program started by the Bol Processor which is running, or '').
ob_start(); // _basic_tasks.php must not add anything to the JSON answer
require_once("_basic_tasks.php");
ob_end_clean();
session_write_close();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$action = (string)($_POST['action'] ?? '');
$ok = TRUE;
$message = '';
if($action == "start") $ok = oscrt_robot_start((string)($_POST['file'] ?? ''),$message);
else if($action == "stop") {
	oscrt_robot_stop();
	$message = "Stopped";
	}
else if($action == "check") {
	$python = python_status(TRUE);
	$ok = $python['ok'];
	$message = $ok ? "Python ".htmlspecialchars($python['version'])." was found" : "Python was not found";
	}
else if($action == "path") {
	// 2026-11-24 Path of Python typed by the user, or a command such as "py": kept in _settings.php if it works
	$path = trim(str_replace('"','',(string)($_POST['path'] ?? '')));
	$exists = ($path <> '' AND (python_is_command($path) OR is_file($path)));
	$info = $exists ? python_probe($path,'') : FALSE;
	if(!$exists) {
		$ok = FALSE;
		$message = "This file was not found";
		}
	else if($info === FALSE) {
		$ok = FALSE;
		$message = python_is_command($path) ? "The command ‘".htmlspecialchars($path)."’ did not answer as Python 3" : "This program did not answer as Python 3";
		}
	else if($info['tk'] == '') {
		$ok = FALSE;
		$message = "Python ".htmlspecialchars($info['version'])." has no tkinter for the window";
		}
	else if(osc_server_mac() AND version_compare($info['tk'],"8.6","<")) {
		$ok = FALSE;
		$message = "Python ".htmlspecialchars($info['version'])." has Tk ".htmlspecialchars($info['tk']).", whose window doesn't work on recent versions of MacOS";
		}
	else {
		save_settings("python_path",$path);
		$python_path = $path;
		$python = python_status(TRUE);
		$ok = $python['ok'];
		$message = $ok ? "Python ".htmlspecialchars($python['version'])." was found" : $python['reason'];
		}
	}
else {
	$ok = FALSE;
	$message = "Unknown action";
	}
echo json_encode(["ok" => $ok,"message" => $message,"running" => oscrt_robot_running()]);
?>
