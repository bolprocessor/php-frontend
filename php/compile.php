<?php
set_time_limit(0);
$filename = "Compilation";
require_once("_basic_tasks.php");
$user_os = getOS();
echo "<p style=\"text-align:center; width:90%;\">System = ".$user_os."</p>";
if(!file_exists("../source")) {
	echo "<p>The ‘source’ folder is missing or misplaced. Follow instructions on page bolprocessor.org/check-bp3/ and check your installation!</p>";
	echo "<p><a href=\"index.php\">Return to Bol Processor home page</a></p>";
	die(); 
	}
echo "<p style=\"text-align:center; width:90%;\">Wait before closing this page!</p>";
if(file_exists("../".$console)) @unlink("../".$console);
$this_file = "bp_compile_result.txt";
if(file_exists($this_file)) @unlink($this_file);
$command_show = $command = "make clean && make 2>bp_compile_result.txt";
if(windows_system()) {
	$command = "mingw32-make clean && mingw32-make 2>bp_compile_result.txt";
	$command = "cmd /c ".$command;
	$command = $command_show = escapeshellcmd($command);
	$command = preg_replace("'(?<!^) '","^ ",$command);
	}
echo "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
echo "<p id=\"refresh\" style=\"text-align:center; width:90%;\"><big>----------- Compiling BP3 as ‘<span class=\"blue-text\">".$console."</span>’. <font color=\"green\">It will take a minute or two.</font> -----------</big></p>"; // "refresh" is the id used for flashing. This is why we read "bp.css"
echo "<p style=\"text-align:center; width:90%;\">Running: <font color=\"red\">".$command_show."</font></p>";

require_once("_header.php");
display_darklight();

echo str_repeat(' ', 1024);  // send extra spaces to fill browser buffer
ob_flush();
flush();
sleep(1);
chdir('..');
$last_line = exec($command,$output,$return_var);
chdir("php");
if(file_exists($this_file)) chmod($this_file,0777);
echo "<div style=\"background-color:white; padding: 1em; border-radius: 1em;\">";
if($return_var <> 0) {
	echo "<p style=\"text-align:center;  width:90%;\">Compilation failed… Check the “source/BP3” folder!</p>";
	if(file_exists($this_file)) {
		$content = trim(@file_get_contents($this_file,TRUE));
		echo "<p style=\"color:red; text-align:center; width:90%;\"><big>Compilation Errors:</big></p>";
		echo "<pre style=\"color:red; text-align:left; width:90%; margin:auto;\">".htmlspecialchars($content)."</pre>";
		}
	}
else {
	echo "<p style=\"text-align:center;  width:90%;\"><big>😀&nbsp;&nbsp;<font color=\"green\">Compilation of</font> ‘<span class=\"blue-text\">".$console."</span>’ <font color=\"green\">worked!</font>&nbsp;&nbsp;😀</big></p>";
	}
// echo "<p style=\"text-align:center;  width:90%;\">------ End of compilation ------</p>";
echo "<script>";
echo "var element = document.getElementById('refresh');
    if (element) {
    element.style.display = 'none';
	}";
echo "</script>";
if($return_var <> 0) {
	if(mac_system()) {
		echo "<div style=\"background-color:azure; padding:12px; width:90%; margin: auto;\"><p style=\"text-align:center; width: 90%;\">Since this compilation failed<br />you may need to install <a target=\"_blank\" href=\"https://www.cnet.com/tech/computing/install-command-line-developer-tools-in-os-x/\">command line developer tools in OS X</a>.</p><p style=\"text-align:center; width: 90%;\">Recent versions of MacOS do it automatically and no further adjustment is required.</p><p style=\"text-align:center; width: 90%;\">Send a message to <a href=\"mailto:contact@bolprocessor.org\">contact@bolprocessor.org</a> in case of trouble</p></div>";
		}
	else {
		echo "<div style=\"background-color:azure; padding:12px; width:90%; margin: auto;\"><p style=\"text-align:center; width: 90%;\">Since this compilation failed (because the “make” command did not work)<br />please check compiling instructions on the page: <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#compile-bp-and-check-its-operation\">Compile ‘".$console."’ and check its operation</a></p><p style=\"text-align:center; width: 90%;\">Send a message to <a href=\"mailto:contact@bolprocessor.org\">contact@bolprocessor.org</a> in case of trouble</p></div>";
		}
	}
echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;<a href=\"\" onclick=\"window.close();\">Now close this page</a></big></p>";
echo "</div>";
?>