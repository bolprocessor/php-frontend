<?php
$note = trim($_GET['note'] ?? '');
if ($note === '') $note = "???";
if ($note === '') {
    http_response_code(400);
    exit("Bad request\n");
	}
$bp_application_path = "../";
$temp_dir = $bp_application_path."temp_bolprocessor/";
$file = $temp_dir."trace_notes_txt";
file_put_contents($file, $note."\n",FILE_APPEND | LOCK_EX);
chmod($file, 0666);
