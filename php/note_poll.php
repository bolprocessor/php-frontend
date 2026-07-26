<?php
session_write_close();

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$temp_dir = $_GET['temp_dir'] ?? '';
$pos = max(0, (int)($_GET['pos'] ?? 0));

if ($temp_dir === '') {
    echo json_encode(["pos" => 0, "notes" => []]);
    exit;
}

$file = rtrim($temp_dir, "/\\") . "/trace_notes_txt";
$notes = [];

clearstatcache(true, $file);

if (is_file($file)) {
    $size = filesize($file);

    /* A new recording may have replaced a previous, longer file. */
    if ($pos > $size) {
        $pos = 0;
    }

    $fp = fopen($file, "r");

    if ($fp !== false) {
        fseek($fp, $pos);

        while (($line = fgets($fp)) !== false) {
            $note = trim($line);

            if ($note !== '') {
                $notes[] = $note;
            }
        }

        $pos = ftell($fp);
        fclose($fp);
    }
}

echo json_encode([
    "pos" => $pos,
    "notes" => $notes
]);