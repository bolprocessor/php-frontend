<?php
$filename = "Compilation";
require_once("_basic_tasks.php");
echo "<head>";
echo "<script src=\"darkmode.js\"></script>";
echo "</head>";
echo "<body>";

set_time_limit(0);
$user_os = getOS();
// ob_start();
if(windows_system()) {
    if(isset($_GET['keepalive'])) {
        echo " "; // Send a space to keep connection alive
        flush();
        }
    }

echo "<p style=\"text-align:center; width:90%;\">System = ".$user_os."</p>";
if(!file_exists($bp_application_path."source")) {
	echo "<p>The ‘source’ folder is missing or misplaced. Follow instructions on page bolprocessor.org/check-bp3/ and check your installation!</p>";
	echo "<p><a href=\"index.php\">Return to Bol Processor home page</a></p>";
	die(); 
	}
echo "<p style=\"text-align:center; width:90%;\">Wait before closing this page…</p>";
if(file_exists($bp_application_path.$console)) @unlink($bp_application_path.$console);
$command_show = $command = "make clean && make 2>bp_compile_result.txt";
if(windows_system()) {
	$command = "mingw32-make clean && mingw32-make 2>bp_compile_result.txt";
	$command = "cmd /c ".$command;
	$command = $command_show = escapeshellcmd($command);
	$command = preg_replace("'(?<!^) '","^ ",$command);
	}
echo "<p id=\"refresh\" style=\"text-align:center; background-color:yellow; width:90%;\"><big>----------- Compiling BP3 as ‘<span class=\"green-text\">".$console."</span>’. It will take a minute or two. -----------</big></p>";
echo "<p style=\"text-align:center; width:90%;\">Running: <span class=\"green-text\">".$command_show."</span></p>";

echo "<link rel=\"stylesheet\" href=\"bp-light.css?v=".time()."\" />\n";
// The "v=" forces this stylesheet to replace the previous one
echo str_repeat(' ',10240);  // send extra spaces to fill browser buffer
if(ob_get_level() > 0) ob_flush();
flush();
$old_dir = getcwd();
chdir('..');
$return_var = 0;
// $this_file = __DIR__ ."bp_compile_result.txt";
$this_file = "bp_compile_result.txt";
$output = [];
if(file_exists($this_file)) @unlink($this_file);

if(!file_exists("Makefile")) echo "<p>ERROR: \"Makefile\" is missing!</p>";
$last_line = exec($command,$output,$return_var);

// chdir($old_dir);
echo "<link rel=\"stylesheet\" href=\"bp.css?v=".time()."\" />\n";
// The "v=" forces this stylesheet to replace the previous one
echo "<link rel=\"stylesheet\" href=\"skin".$skin.".css\" />\n";
echo "<div style=\"padding: 1em; border-radius: 1em;\">";
if(file_exists($this_file)) {
	@chmod($this_file,0666);
	$this_size = filesize($this_file);
	if($this_size  < 2) $return_var = 0;
	if($this_size  > 10) $return_var = 1;
	}
// else $return_var = 0;

/* echo "return_var = ".$return_var."<br />";
echo "last_line = ".$last_line."<br />";
echo "output = ";
print_r($output); */

if($return_var <> 0) {
	echo "<p style=\"text-align:center;  width:90%;\">Compilation failed… Check the “source/BP3” folder!</p>";
	if(file_exists($this_file)) {
		$content = trim(@file_get_contents($this_file));
		echo "<p style=\"color:red; text-align:center; width:90%;\"><big>Compilation Errors:</big></p>";
		echo "<pre style=\"color:red; text-align:left; width:90%; margin:auto; white-space:pre-wrap; word-wrap:break-word; overflow-wrap:break-word;\">".htmlspecialchars($content)."</pre>";
		}
	}
else {
	echo "<p style=\"text-align:center;  width:90%;\"><big>😀&nbsp;&nbsp;Compilation of ‘<span class=\"green-text\">".$console."</span>’ worked!&nbsp;&nbsp;😀</big></p>";
	}
echo "<script>";
echo "var element = document.getElementById('refresh');
    if (element) {
    element.style.display = 'none';
	}";
echo "</script>";
if($return_var <> 0) {
	if(mac_system()) {
		echo "<div class=\"edit\" style=\"padding:12px; width:90%; margin: auto;\"><p style=\"text-align:center; width: 90%;\">Since this compilation failed<br />you may need to install <a target=\"_blank\" class=\"linkdotted\" href=\"https://www.cnet.com/tech/computing/install-command-line-developer-tools-in-os-x/\">command line developer tools in OS X</a>.</p><p style=\"text-align:center; width: 90%;\">Recent versions of MacOS do it automatically and no further adjustment is required.</p><p style=\"text-align:center; width: 90%;\">Send a message to <a href=\"mailto:contact@bolprocessor.org\">contact@bolprocessor.org</a> in case of trouble</p></div>";
		}
	else {
		echo "<div class=\"edit\" style=\"padding:12px; width:90%; margin: auto;\"><p style=\"text-align:center; width: 90%;\">Since this compilation failed (because the “make” command did not work)<br />please check compiling instructions on the page: ";
		if(windows_system()) {
			echo "<a target=\"_blank\" class=\"linkdotted\" href=\"https://bolprocessor.org/quick-install-windows/#compile-the-bp-exe-console-if-necessary\">Compile ‘".$console."’ and check its operation</a></p>";
			}
		else { // Linux
			echo "<a target=\"_blank\" class=\"linkdotted\" href=\"https://bolprocessor.org/quick-install-linux/#compile-the-bp3-console\">Compile ‘".$console."’ and check its operation</a></p>";
			}
		echo "<p style=\"text-align:center; width: 90%;\">Send a message to <a href=\"mailto:contact@bolprocessor.org\">contact@bolprocessor.org</a> in case of trouble</p></div>";
		}
	}
sleep(3);
// echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;<a href=\"#\" onclick=\"if (window.opener) { window.close(); } return false;\">Click to close this page</a></big></p>";
echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;<a href=\"#\" onclick=\"window.close(); return false;\">Click to close this page</a></big></p>";
echo "</div>";
echo "</body>";
?>