<?php
function doCommand($strCommand) {
	echo "-----------<br />".$strCommand."<br />";
    system($strCommand);
    echo "<br />----------";
	}
doCommand("make");
?>