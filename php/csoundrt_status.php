<?php
// State of the real-time Csound server, polled by project pages to show or hide the QUIT CSOUND button, see _csoundrt.php
session_write_close();
require_once("_csoundrt.php");
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");
$running = csoundrt_running();
$state = csoundrt_state();
$orchestra = ($running AND isset($state['orchestra'])) ? basename($state['orchestra']) : '';
echo json_encode(["running" => $running, "orchestra" => $orchestra]);
?>
