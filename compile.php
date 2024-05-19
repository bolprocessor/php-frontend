<?php
require_once("php/_basic_tasks.php");
$user_os = getOS();
echo "<p>System = ".$user_os."</p>";
if(!file_exists("source")) {
	echo "<p>The ‘source’ folder is missing or misplaced. Follow instructions on page <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a> and check your installation!</p>";
	echo "<p><a href=\"php/index.php\">Return to Bol Processor home page</a></p>";
	die(); 
	}
if(file_exists("bp")) unlink("bp");
$this_file = "bp_compile_result.txt";
if(file_exists($this_file)) unlink($this_file);
$command = "make clean && make 2>bp_compile_result.txt";
if(windows_system()) {
	$command = "cmd /c ".$command;
	$command = escapeshellcmd($command);
	$command = preg_replace("'(?<!^) '","^ ",$command);
	}
echo "<p style=\"text-align:center; width:50%;\">----------- compiling -----------</p>";
echo "<p style=\"color:red; text-align:center; width:50%;\">".$command."</p>";
$last_line = exec($command,$output,$return_var);
if($return_var !== 0) {
	echo "<p style=\"text-align:center;  width:50%;\">Compilation failed… Check the “source/BP3” folder!</p>";
	if(file_exists($this_file)) {
		$content = trim(@file_get_contents($this_file,TRUE));
		if($content <> '') echo $content;
		else echo "<p style=\"text-align:center;  width:50%;\">Compilation worked!</p>";
		}
	}
else
	echo "<p style=\"text-align:center;  width:50%;\">Compilation worked!</p>";
echo "<p style=\"text-align:center;  width:50%;\">------ end of compilation ------</p>";
if(mac_system()) {
	echo "<div style=\"background-color:azure; padding:6px; width:50%; text-align:center;\"><p>In case compilation failed (because the “make” command was not accepted)<br />you may need to install <a target=\"_blank\" href=\"https://www.cnet.com/tech/computing/install-command-line-developer-tools-in-os-x/\">command line developer tools in OS X</a>.</p><p>Recent versions of MacOS do it automatically and no further adjustment is required.</p></div>";
	}
else {
	echo "<div style=\"background-color:azure; padding:6px; width:50%; text-align:center;\"><p>In case compilation failed (because the “make” command did not work)<br />please check compiling instructions on the page: <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#compile-bp-and-check-its-operation\">Compile ‘bp’ and check its operation</a></p></div>";
	}
echo "<p><a href=\"php/index.php\">Return to Bol Processor home page</a></p>";
?>