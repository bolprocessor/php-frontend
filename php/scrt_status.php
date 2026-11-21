<?php
// State of the SuperCollider server, polled by project pages to show or hide the QUIT SUPERCOLLIDER button, see _scrt.php
session_write_close();
require_once("_csoundrt.php");
require_once("_scrt.php");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$running = scrt_running();
echo json_encode(["running" => $running, "owned" => ($running AND scrt_owned())]);
?>
