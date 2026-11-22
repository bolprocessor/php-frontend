<?php
// 2026-11-22 Loaded every minute by watchdog.sh to check that Apache and PHP answer.
// It does not include _basic_tasks.php, so that it answers quickly.
header("Cache-Control: no-store");
echo "ok";
?>
