<?php
require_once("_basic_tasks.php");
require_once("_settings.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
$url_this_page = "grammar.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$here = $filename = end($table);
// $grammar_file = "..".SLASH.$file;
$grammar_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$grammar_file);

if($test) echo "grammar_file = ".$grammar_file."<br />";

if($output_folder == '') $output_folder = "my_output";
$output_file = "out.sco";
$file_format = "csound";
if(isset($_POST['output_file'])) $output_file = $_POST['output_file'];
if(isset($_POST['file_format'])) $file_format = $_POST['file_format'];

$expression = '';
if(isset($_POST['expression'])) $expression = trim($_POST['expression']);

require_once("_header.php");

echo "<p><small>Current directory = ".$dir;

if(isset($_POST['savegrammar']) OR isset($_POST['compilegrammar'])) {
	if(isset($_POST['savegrammar'])) echo "<span id=\"timespan\" style=\"color:red;\">&nbsp;…&nbsp;Saved “".$filename."” file…</span>";
	$content = $_POST['thisgrammar'];
	$output_file = $_POST['output_file'];
	$output_file = fix_new_name($output_file);
	$file_format = $_POST['file_format'];
	$show_production = $_POST['show_production'];
	$trace_production = $_POST['trace_production'];
	$produce_all_items = $_POST['produce_all_items'];
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['note_convention'])) $note_convention = $_POST['note_convention'];
	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
	else $random_seed = 0;
	if($file_format == "data") {
		$output_file = trim(str_replace(".bpda",'',$output_file));
		if($output_file == '') $output_file = "out";
		$output_file .= ".bpda";
		}
	if($file_format == "csound") {
		$table = explode('.',$output_file);
		$extension = $table[count($table) - 1];
		if($extension <> "sco") {
			$output_file = trim(str_replace(".sco",'',$output_file));
			if($output_file == '') $output_file = "out";
			$output_file .= ".sco";
			}
		}
	if($file_format == "midi") {
		$table = explode('.',$output_file);
		$extension = $table[count($table) - 1];
		if($extension <> "mid") {
			$output_file = trim(str_replace(".mid",'',$output_file));
			if($output_file == '') $output_file = "out";
			$output_file .= ".mid";
			}
		}
	if($file_format == '') $output_file = '';
	$handle = fopen($grammar_file,"w");
	$content = recode_entities($content);
	$file_header = $top_header."\n// Grammar file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle);
	}
echo "</small></p>";

if(isset($_POST['change_output_folder'])) {
	$output_folder = trim($_POST['output_folder']);
	$output_folder = str_replace('+','_',$output_folder);
	$output_folder = trim(str_replace(SLASH,' ',$output_folder));
	$output_folder = str_replace(' ',SLASH,$output_folder);
	$output = $bp_application_path.SLASH.$output_folder;
	do $output = str_replace(SLASH.SLASH,SLASH,$output,$count);
	while($count > 0);
//	echo $output."<br />";
	if(!file_exists($output)) {
		echo "<p><font color=\"red\">Created folder:</font><font color=\"blue\"> ".$output."</font><br />";
		mkdir($output);
		}
	$handle = fopen("_settings.php","w");
	fwrite($handle,"<?php\n");
	$line = "§output_folder = \"".$output_folder."\";\n";
	$line = str_replace('§','$',$line);
	fwrite($handle,$line);
	$line = "§>\n";
	$line = str_replace('§','?',$line);
	fwrite($handle,$line);
	fclose($handle);
	}
else {
	$output = $bp_application_path.SLASH.$output_folder;
	do $output = str_replace(SLASH.SLASH,SLASH,$output,$count);
	while($count > 0);
	if(!file_exists($output)) {
		echo "<p><font color=\"red\">Created folder:</font><font color=\"blue\"> ".$output."</font><br />";
		mkdir($output);
		}
	}

echo link_to_help();

echo "<h3>Grammar file “".$filename."”</h3>";

$link = "test-image.html";
echo "<p style=\"border:2px solid gray; background-color:azure; width:13em;  padding:2px; text-align:center; border-radius: 6px;\"><a onclick=\"window.open('".$link."','CANVAS test','width=500,height=500,left=200'); return false;\" href=\"".$link."\">TEST IMAGE</a></p>";

	
if(isset($_POST['compilegrammar'])) {
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
	else $settings_file = '';
	echo "<p id=\"timespan\">Compiling ‘".$filename."’</p>";
	$application_path = $bp_application_path;
	$command = $application_path."bp compile";
	$thisgrammar = $dir.$filename;
	if(is_integer(strpos($thisgrammar,' ')))
		$thisgrammar = '"'.$thisgrammar.'"';
	$command .= " -gr ".$thisgrammar;
	if($settings_file <> '') {
		if(!file_exists($dir.$settings_file)) {
			echo "<p style=\"color:red;\">WARNING: ".$dir.$settings_file." not found.</p>";
			}
		else $command .= " -se ".$dir.$settings_file;
		}
	$thisalphabet = $alphabet_file;
	if(is_integer(strpos($thisalphabet,' ')))
		$thisalphabet = '"'.$thisalphabet.'"';
	if($alphabet_file <> '') {
		if(!file_exists($dir.$alphabet_file)) {
			echo "<p style=\"color:red;\">WARNING: ".$dir.$alphabet_file." not found.</p>";
			}
		else $command .= " -ho ".$dir.$alphabet_file;
		}
	$command .= " --traceout ".$tracefile;
	echo "<p style=\"color:red;\"><small>".$command."</small></p>";
	$no_error = FALSE;
	$o = send_to_console($command);
	$n_messages = count($o);
//	chdir($olddir);
	if($n_messages > 0) {
		for($i=0; $i < $n_messages; $i++) {
			$mssg = $o[$i];
			$mssg = clean_up_encoding(TRUE,$mssg);
			if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
			}
		}
	if(!$no_error) {
		$trace_link = clean_up_file($dir.$tracefile);
		if($trace_link == '') echo "<p><font color=\"red\">Errors found, but no trace file has been created.</font></p>";
		else echo "<p><font color=\"red\">Errors found! Open the </font> <a onclick=\"window.open('".$trace_link."','trace','width=800,height=800'); return false;\" href=\"".$trace_link."\">trace file</a>!</p>";
		}
	else echo "<p><font color=\"red\">➡</font> <font color=\"blue\">No error.</font></p>";
	// Now reformat the grammar
	reformat_grammar(FALSE,$grammar_file);
	}
else {
	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
	else $random_seed = 0;
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
	echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
	echo "<input type=\"hidden\" name=\"random_seed\" value=\"".$random_seed."\">";
	echo "Location of output files: <font color=\"blue\">".$bp_application_path."</font>";
	echo "<input type=\"text\" name=\"output_folder\" size=\"25\" value=\"".$output_folder."\">";
	echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"change_output_folder\" value=\"SAVE THIS LOCATION\"><br />➡ global setting for all projects in this session.<br /><i>Folder will be created if necessary…</i>";
	echo "</form>";
	}

if($test) echo "grammar_file = ".$grammar_file."<br />";

$content = @file_get_contents($grammar_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$metronome = 0;
$time_structure = $objects_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$alphabet_file = $extract_data['alphabet'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$interaction_file = $extract_data['interaction'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];
$metronome = $extract_data['metronome'];
$time_structure = $extract_data['time_structure'];
$templates = $extract_data['templates'];

if($settings_file <> '') $show_production = get_setting("show_production",$settings_file);
else $show_production = 0;
if($settings_file <> '') $trace_production = get_setting("trace_production",$settings_file);
else $trace_production = 0;

if($settings_file <> '') $note_convention = get_setting("note_convention",$settings_file);
else $note_convention = 0;

/* echo "show_production = ".$show_production."<br />";
echo "trace_production = ".$trace_production."<br />"; */
if($settings_file <> '') $produce_all_items = get_setting("produce_all_items",$settings_file);
else $produce_all_items = 0;
if($settings_file <> '') $random_seed = get_setting("random_seed",$settings_file);
else $random_seed = 0;

if($test) echo "url_this_page = ".$url_this_page."<br />";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<table cellpadding=\"8px;\"><tr style=\"background-color:white;\">";
echo "<td><p>Name of output file (with proper extension):<br /><input type=\"text\" name=\"output_file\" size=\"25\" value=\"".$output_file."\">&nbsp;";
echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"savegrammar\" value=\"SAVE\"></p>";
echo "</td>";
echo "<td><p style=\"text-align:left;\">";
if($test) echo "file_format = ".$file_format."<br />";
echo "<input type=\"radio\" name=\"file_format\" value=\"\"";
if($file_format == "") echo " checked";
echo ">No file (real-time MIDI)";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"data\"";
if($file_format == "data") echo " checked";
echo ">BP data file";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"midi\"";
if($file_format == "midi") echo " checked";
echo ">MIDI file";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"csound\"";
if($file_format == "csound") echo " checked";
echo ">CSOUND file";
echo "</p></td>";
echo "<td style=\"text-align:right; vertical-align:middle;\" rowspan=\"2\">";
echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"savegrammar\" value=\"SAVE ‘".$filename."’\"><br /><br />";
echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"compilegrammar\" value=\"COMPILE GRAMMAR\"><br /><br />";

$error = FALSE;
if($templates) {
	$action = "templates";
	$link = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
//	$link .= "&trace_production=1";
	$link .= "&here=".urlencode($here);
//	echo "link = ".$link."<br />";
	$window_name = window_name($filename);
	echo "<input style=\"color:DarkBlue; background-color:azure;\" onclick=\"window.open('".$link."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"CHECK TEMPLATES\"><br /><br />";
	}
	
if($produce_all_items > 0) $action = "produce-all";
else $action = "produce";
$link = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		echo "<font color=\"red\"><small>WARNING: ".$dir.$alphabet_file." not found.<small></font><br />";
		$error = TRUE;
		}
	else $link .= "&alphabet=".urlencode($alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		echo "<font color=\"red\"><small>WARNING: ".$dir.$settings_file." not found.<small></font><br />";
		$error = TRUE;
		}
	else $link .= "&settings_file=".urlencode($settings_file);
	}
if($test) echo "output = ".$output."<br />";
if($test) echo "output_file = ".$output_file."<br />";
$link .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link .= "&show_production=1";
if($trace_production > 0)
	$link .= "&trace_production=1";
$link .= "&random_seed=".$random_seed;
$link .= "&here=".urlencode($here);
$window_name = window_name($filename);
echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)\"";
if($error) echo " disabled";
echo ">";
echo "</td></tr>";
echo "<tr><td colspan=\"2\"><p style=\"text-align:center;\">➡ <i>You can change above settings, then save the grammar…</i></p></td></tr>";
echo "</table>";

if($settings_file == '') {
	if($metronome > 0) {
		$p = intval($metronome * 10000);
		$q = 600000;
		$gcd = gcd($p,$q);
		$p = $p / $gcd;
		$q = $q / $gcd;
		if(intval($metronome) == $metronome)
			$metronome = intval($metronome);
		else $metronome = sprintf("%.4f",$metronome);
		echo "<p style=\"color:blue;\">⏱ Time base: ".$p." ticks in ".$q." seconds (metronome = ".$metronome." beats per minute)<br />";
		if($time_structure == '') $time_structure = "striated";
		echo "⏱ Time structure: ".$time_structure."</p>";
		}
	else {
		$metronome = 60;
		if($time_structure <> '')
			echo "<p style=\"color:blue;\">⏱ Metronome (time base) is not properly specified. It will be set to 60 beats per minute and time structure will be ".$time_structure.".</p>";
		else
			echo "<p style=\"color:blue;\">⏱ Metronome (time base) and structure of time are neither specified nor set up by a ‘-se’ file.<br />Therefore they will be set to 60 beats per minute and striated.</p>";
		$time_structure = "striated";
		}
	}
else {
	if($metronome > 0)
		echo "<font color=\"red\">⏱ Metronome and structure of time indicated in this grammar will be ignored as they are set up by ‘".$settings_file."’</font><br />";
	else
		echo "<font color=\"blue\">⏱ Metronome (time base) and structure of time will be fixed by ‘".$settings_file."’</font><br />";
	$metronome = 0;
	$time_structure = '';
//	echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
	}
if($note_convention <> '') echo "• Note convention = <font color=\"blue\">".intval($note_convention)."</font> found in <font color=\"blue\">‘".$settings_file."’</font><br />";
if($produce_all_items == 1) echo "• Produce all items has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($show_production == 1) echo "• Show production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($trace_production == 1) echo "• Trace production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if(isset($random_seed)) echo "• Ransom seed has been set to ".$random_seed." by <font color=\"blue\">‘".$settings_file."’</font><br />";
echo "</p>";

echo "<input type=\"hidden\" name=\"produce_all_items\" value=\"".$produce_all_items."\">";
echo "<input type=\"hidden\" name=\"show_production\" value=\"".$show_production."\">";
echo "<input type=\"hidden\" name=\"trace_production\" value=\"".$trace_production."\">";
echo "<input type=\"hidden\" name=\"metronome\" value=\"".$metronome."\">";
echo "<input type=\"hidden\" name=\"time_structure\" value=\"".$time_structure."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";

echo "<textarea name=\"thisgrammar\" rows=\"25\" style=\"width:700px; background-color:Cornsilk;\">".$content."</textarea>";
echo "</form>";

display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

$table = explode(chr(10),$content);
$imax = count($table);
$variable = array();
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$line = preg_replace("/\[.*\]/u",'',$line);
	if($line == '') continue;
	if($line == "COMMENT:") break;
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
	$table2 = explode(' ',$line);
	for($j = 0; $j < count($table2); $j++) {
		$word = $table2[$j];
		if($word == '') continue;
		$word = is_variable($note_convention,$word);
		if($word == '') continue;
		if(isset($variable[$word])) continue;
		$variable[$word] = TRUE;
		}
	}

echo "<form method=\"post\" action=\"".$url_this_page."#expression\" enctype=\"multipart/form-data\">";
$action = "produce";
$link = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
if($alphabet_file <> '') $link .= "&alphabet=".urlencode($alphabet_file);
if($settings_file <> '') $link .= "&settings_file=".urlencode($settings_file);
$link .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link .= "&show_production=1";
if($trace_production > 0)
	$link .= "&trace_production=1";
$link .= "&random_seed=".$random_seed;
$link .= "&here=".urlencode($here);
$window_name = window_name($filename);
if(count($variable) > 0) {
	echo "<h3>Variables (click to use as startup string):</h3>";
	ksort($variable);
	foreach($variable as $var => $val) {
		$thislink = $link."&startup=".$var;
		echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$thislink."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"startup_".$var."\" value=\"".$var."\"> ";
		}
	}
$recoded_expression = recode_tags($expression);
echo "<p id=\"expression\">Use this (polymetric) expression as startup&nbsp;➡&nbsp;<input type=\"text\" name=\"expression\" size=\"60\" value=\"".$recoded_expression."\">&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"playexpression\" value=\"PRODUCE ITEM…\"></p>";
if(isset($_POST['playexpression'])) {
	if($expression == '') {
		echo "<p id=\"timespan\"><font color=\"red\">➡ Cannot play empty expression…</font></p>";
		}
	else {
		echo "<p id=\"timespan\"><font color=\"red\">➡ Playing:</font> <font color=\"blue\"><big>".$recoded_expression."</big></font></p>";
		$data = $temp_dir."temp_".session_id()."outdata.bpda";
		$result_file = $output.SLASH.$output_file;
		$handle = fopen($data,"w");
		$file_header = $top_header."\n// Data saved as \"expression.bpda\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$expression."\n");
		fclose($handle);
		if(file_exists($result_file)) unlink($result_file); 
		$application_path = $bp_application_path;
		$command = $application_path."bp produce";
		$command .= " -gr ".$dir.$filename;
		$command .= " -S ".$data;
		if($settings_file <> '') $command .= " -se \"".$dir.$settings_file."\"";
		if($alphabet_file<> '') $command .= " -ho \"".$dir.$alphabet_file."\"";
		if($objects_file <> '') $command .= " -mi \"".$dir.$objects_file."\"";
		if($csound_file <> '') $command .= " -cs \"".$dir.$csound_file."\"";
		switch($file_format) {
			case "data":
				$command .= " -d -o ".$result_file;
				break;
			case "midi":
				$command .= " -d --midiout ".$result_file;
				break;
			case "csound":
				$command .= " -d --csoundout ".$result_file;
				break;
			default:
				$command .= " -d --rtmidi";
				break;
			}
		echo "<p style=\"color:red;\"><small>".$command."</small></p>";
		$no_error = FALSE;
		$o = send_to_console($command);
		$n_messages = count($o);
		if($n_messages > 0) {
			for($i=0; $i < $n_messages; $i++) {
				$mssg[$i] = $o[$i];
				$mssg[$i] = clean_up_encoding(TRUE,$mssg[$i]);
				if(is_integer($pos=strpos($mssg[$i],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
				}
			}
		$message = '';
		if(!$no_error) {
			$message .= "<p><font color=\"red\">➡ </font>This process:<br /><small>";
			for($i=0; $i < $n_messages; $i++) {
				$message .= "&nbsp;&nbsp;&nbsp;".$mssg[$i]."<br />";
				}
			$message .= "</small></p>";
			}
		echo $message;
		
		if($file_format == "data" OR $file_format == "csound")  {
			echo "<p><font color=\"red\">➡ </font>Result:<br />";
			$content = @file_get_contents($result_file,TRUE);
			$table = explode(chr(10),$content);
			$imax = count($table);
			$found = FALSE;
			for($i = 0; $i < $imax; $i++) {
				$line = trim($table[$i]);
				if($line == '') continue;
				$found = TRUE;
				$line = recode_tags($line);
				echo "&nbsp;&nbsp;&nbsp;".$line."<br />";
				}
			if(!$found) echo "&nbsp;&nbsp;&nbsp;No result…";
			echo "</p>";
			}
		}
	}

echo "</form>";
echo "</body>";
echo "</html>";
?>
