<?php
session_write_close();
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache, no-transform");
header("Connection: keep-alive");
header("X-Accel-Buffering: no");

$pos = 0;
flush();
while(ob_get_level() > 0) {
    ob_end_flush();
    }
ob_implicit_flush(true);
$temp_dir = $_GET['temp_dir'] ?? '';
if($temp_dir === '') die();
$file = rtrim($temp_dir, "/\\")."/trace_notes_txt";
clearstatcache(false,$file);
while(true) {
    if (file_exists($file)) {
        $fp = fopen($file, "r");
        if ($fp) {
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