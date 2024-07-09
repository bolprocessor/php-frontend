<?php
set_time_limit(1000);
$filename = "Compilation";
require_once("php/_basic_tasks.php");
$user_os = getOS();
echo "<p style=\"text-align:center; width:90%;\">System = ".$user_os."</p>";
if(!file_exists("source")) {
/*	echo "<p>The ‘source’ folder is missing or misplaced. Follow instructions on page <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a> and check your installation!</p>"; */
	echo "<p>The ‘source’ folder is missing or misplaced. Follow instructions on page bolprocessor.org/check-bp3/ and check your installation!</p>";
	echo "<p><a href=\"php/index.php\">Return to Bol Processor home page</a></p>";
	die(); 
	}
if(file_exists("bp")) unlink("bp");
$this_file = "bp_compile_result.txt";
if(file_exists($this_file)) unlink($this_file);
$command_show = $command = "make clean && make 2>bp_compile_result.txt";
if(windows_system()) {
	$command = "mingw32-make clean && mingw32-make 2>bp_compile_result.txt";
	$command = "cmd /c ".$command;
	$command = $command_show = escapeshellcmd($command);
	$command = preg_replace("'(?<!^) '","^ ",$command);
	}
echo "<link rel=\"stylesheet\" href=\"php/bp.css\" />\n";
echo "<p id=\"refresh\" style=\"text-align:center; width:90%;\">----------- Compiling BP3 as ‘<font color=\"blue\">".$console."</font>’. <font color=\"green\">It may take a minute or two.</font> -----------</p>"; // "refresh" is the id used for flashing. This is why we read "bp.css"
echo "<p style=\"text-align:center; width:90%;\">Running: <font color=\"red\">".$command_show."</font></p>";
require_once("php/_header.php");
echo str_repeat(' ', 1024);  // send extra spaces to fill browser buffer
ob_flush();
flush();
// session_abort();
sleep(1);
$last_line = exec($command,$output,$return_var);
if($return_var <> 0) {
	echo "<p style=\"text-align:center;  width:90%;\">Compilation failed… Check the “source/BP3” folder!</p>";
	if(file_exists($this_file)) {
		$content = trim(@file_get_contents($this_file,TRUE));
		echo "<p style=\"color:red; text-align:center; width:90%;\">Compilation Errors:</p>";
		echo "<pre style=\"color:red; text-align:left; width:90%; margin:auto;\">". htmlspecialchars($content) ."</pre>";
		}
	}
else
	echo "<p style=\"text-align:center;  width:90%;\">😀&nbsp;&nbsp;<font color=\"green\">Compilation of</font> ‘<font color=\"blue\">".$console."</font>’ <font color=\"green\">worked!</font>&nbsp;&nbsp;😀</p>";
echo "<p style=\"text-align:center;  width:90%;\">------ End of compilation ------</p>";
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
		echo "<div style=\"background-color:azure; padding:12px; width:90%; margin: auto;\"><p style=\"text-align:center; width: 90%;\">Since this compilation failed (because the “make” command did not work)<br />please check compiling instructions on the page: <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#compile-bp-and-check-its-operation\">Compile ‘".$source."’ and check its operation</a></p><p style=\"text-align:center; width: 90%;\">Send a message to <a href=\"mailto:contact@bolprocessor.org\">contact@bolprocessor.org</a> in case of trouble</p></div>";
		}
	}
/* if(isset($_GET['return'])) {
	$return_url = $_GET['return'];
	echo "<p style=\"text-align:center; width:90%;\"><big>Close this page!</big></p>";
	}
else */ echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;Close this page</big></p>";
?>