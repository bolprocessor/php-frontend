<?php
// Quit the SuperCollider server started by the Bol Processor (button QUIT SUPERCOLLIDER on project pages), see _scrt.php
session_write_close();
require_once("_csoundrt.php");
require_once("_scrt.php");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
echo json_encode(["stopped" => scrt_quit()]);
?>
