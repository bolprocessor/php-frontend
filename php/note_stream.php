<?php
// session_start();
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

$bp_application_path = "../";
$temp_dir = $bp_application_path."temp_bolprocessor";
$file = $temp_dir."/trace_notes_txt";

$pos = 0;

while (true) {
    clearstatcache();
    if (file_exists($file)) {
        $fp = fopen($file, "r");
        if ($fp) {
            fseek($fp, $pos);
            while (($line = fgets($fp)) !== false) {
                $note = trim($line);
                if ($note !== '') {
                    echo "data: ".str_replace(["\n","\r"],"",$note)."\n\n";
                    @ob_flush();
                    @flush();
                    }
                }
            $pos = ftell($fp);
            fclose($fp);
            }
        }
    usleep(100000); // 0.1 sec
    }