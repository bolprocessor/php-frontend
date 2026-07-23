<?php
session_write_close();
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache, no-transform");
header("Connection: keep-alive");
header("X-Accel-Buffering: no");
set_time_limit(0);


flush();
while(ob_get_level() > 0) {
    ob_end_flush();
    }
ob_implicit_flush(true);
echo ": connected\n\n"; // Force l'envoi initial des en-têtes et limite le buffering éventuel
flush();

$temp_dir = $_GET['temp_dir'] ?? '';
if($temp_dir === '') die();
$file = rtrim($temp_dir, "/\\")."/trace_notes_txt";
$pos = 0;

$last_heartbeat = time();
while(true) {
    clearstatcache(false,$file);
    if(time() - $last_heartbeat >= 10) {
        echo ": keep-alive\n\n";   // SSE comment, invisible to JavaScript
        flush();
        $last_heartbeat = time();
        }
    if (file_exists($file)) {
        $fp = fopen($file,"r");
        if($fp) {
            fseek($fp, $pos);
            while (($line = fgets($fp)) !== false) {
                $note = trim($line);
                if ($note !== '') {
                    echo "data: ".$note."\n\n";
                    flush();
                    }
                }
            $pos = ftell($fp);
            fclose($fp);
            }
        }
    else usleep(1000000); // 1 sec
    usleep(300000); // 0.3 sec
    }