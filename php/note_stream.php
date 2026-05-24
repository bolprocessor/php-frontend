<?php
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache, no-transform");
header("Connection: keep-alive");
header("X-Accel-Buffering: no");

$temp_dir = $_GET['temp_dir'] ?? '';
if($temp_dir === '') die();
$file = rtrim($temp_dir, "/\\")."/trace_notes_txt";
flush();
$pos = 0;
while (ob_get_level() > 0) {
    ob_end_flush();
    }
ob_implicit_flush(true);
while(true) {
    clearstatcache(false,$file);
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
    usleep(100000); // 0.1 sec
    }