<?php
// Apparently it doesn't work
set_time_limit(0);
// Windows only
if (stripos(PHP_OS, 'WIN') === 0) {
    exec('taskkill /F /IM bp.exe');
    }
die();
?>