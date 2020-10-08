<?php
require_once("php/_basic_tasks.php");
$user_os = getOS();
echo "<p>System = ".$user_os."</p>";
if(!file_exists("source")) {
	echo "<p>The ‘source’ folder is missing or misplaced. Return to <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a> and check your installation!</p>";
	die();
	}
if(file_exists("bp")) unlink("bp");
doCommand("make");
echo "<p><a href=\"php/index.php\">Return to Bol Processor home page</a></p>";

function doCommand($command) {
	echo "----------- ".$command." -----------<br />";
 	$o = send_to_console($command);
	$n_messages = count($o);
	$no_error = FALSE;
	for($i = 0; $i < $n_messages; $i++) {
		$mssg = $o[$i];
		$mssg = clean_up_encoding(TRUE,$mssg);
		echo $mssg."<br />";
		}
    echo "----------";
    return;
	}
?>