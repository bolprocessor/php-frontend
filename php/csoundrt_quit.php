<?php
// Quit the real-time Csound server (button QUIT CSOUND on project pages), see _csoundrt.php
session_write_close();
require_once("_csoundrt.php");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
echo json_encode(["stopped" => csoundrt_quit()]);
?>
