<?php
set_time_limit(0);
$path_to_file = $_GET['path_to_file'] ?? '';
if (!empty($path_to_file) && file_exists($path_to_file)) {
    unlink($path_to_file);
	}
die();
?>