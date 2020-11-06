<?php
require_once("_basic_tasks.php");
require_once("_settings.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
// $file = str_replace(" ","_",$file);
$url_this_page = "grammar.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$here = $filename = end($table);
// $grammar_file = "..".SLASH.$file;
$grammar_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$grammar_file);
$textarea_rows = 15;

if($test) echo "grammar_file = ".$grammar_file."<br />";

if(!isset($output_folder) OR $output_folder == '') $output_folder = "my_output";
$default_output_name = str_replace("-gr.",'',$filename);
$default_output_name = str_replace(".bpgr",'',$default_output_name);
$file_format = $default_output_format;
if(isset($_POST['file_format'])) $file_format = $_POST['file_format'];
switch($file_format) {
	case "data": $output_file = $default_output_name.".bpda"; break;
	case "midi": $output_file = $default_output_name.".mid"; break;
	case "csound": $output_file = $default_output_name.".sco"; break;
	default: $output_file = ''; break;
	}
if(isset($_POST['output_file'])) $output_file = $_POST['output_file'];

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
	$output_file = trim(str_replace(".bpda",'',$output_file));
	$output_file = trim(str_replace(".sco",'',$output_file));
	$output_file = trim(str_replace(".mid",'',$output_file));
	if($file_format == "data") {
		if($output_file == '') $output_file = $default_output_name;
		$output_file .= ".bpda";
		}
	if($file_format == "csound") {
		if($output_file == '') $output_file = $default_output_name;
		$output_file .= ".sco";
		}
	if($file_format == "midi") {
		if($output_file == '') $output_file = $default_output_name;
		$output_file .= ".mid";
		}
	if($file_format == '') $output_file = '';
	$handle = fopen($grammar_file,"w");
	$content = recode_entities($content);
	$file_header = $top_header."\n// Grammar file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	do $content = str_replace("  ",' ',$content,$count);
	while($count > 0);
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
	save_settings("output_folder",$output_folder);
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
echo "<p style=\"border:2px solid gray; background-color:azure; width:40em;  padding:2px; text-align:center; border-radius: 6px;\"><a onclick=\"window.open('".$link."','CANVAS test','width=500,height=500,left=200'); return false;\" href=\"".$link."\">Test this image to verify that your environment supports CANVAS</a></p>";

	
if(isset($_POST['compilegrammar'])) {
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
	else $settings_file = '';
	if(isset($_POST['csound_file'])) $csound_file = $_POST['csound_file'];
	else $csound_file = '';
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
	if($csound_file <> '') {
		if(!file_exists($dir.$csound_file)) {
			echo "<p style=\"color:red;\">WARNING: ".$dir.$csound_file." not found.</p>";
			}
		else $command .= " -cs ".$dir.$csound_file;
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
			$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
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
$metronome = $metronome_in_grammar = $extract_data['metronome'];
$time_structure = $extract_data['time_structure'];
// echo "metronome = ".$metronome." time structure = ".$time_structure."<br />";
$templates = $extract_data['templates'];
$found_elsewhere = FALSE;
if($alphabet_file <> '' AND $objects_file == '') {
	$objects_file = get_name_mi_file($dir.$alphabet_file);
	if($objects_file <> '') $found_elsewhere = TRUE;
	}

if($csound_file <> '') $csound_orchestra = get_orchestra_filename($dir.$csound_file);
else $csound_orchestra = '';
$show_production = $trace_production = $note_convention = $non_stop_improvize = $p_clock = $q_clock = $striated_time = $max_time_computing = 0;
if($settings_file <> '') {
	$show_production = get_setting("show_production",$settings_file);
	$trace_production = get_setting("trace_production",$settings_file);
	$note_convention = get_setting("note_convention",$settings_file);
	$non_stop_improvize = get_setting("non_stop_improvize",$settings_file);
	$p_clock = get_setting("p_clock",$settings_file);
	$q_clock = get_setting("q_clock",$settings_file);
	$striated_time = get_setting("striated_time",$settings_file);
	if($striated_time > 0) $time_structure = "striated";
	$max_time_computing = get_setting("max_time_computing",$settings_file);
	}

/* echo "show_production = ".$show_production."<br />";
echo "trace_production = ".$trace_production."<br />"; */
if($settings_file <> '') $produce_all_items = get_setting("produce_all_items",$settings_file);
else $produce_all_items = 0;
if($settings_file <> '') $random_seed = get_setting("random_seed",$settings_file);
else $random_seed = 0;

if($test) echo "url_this_page = ".$url_this_page."<br />";

if($file_format == "csound") {
	echo "<div>".check_csound()."</div>";
	}
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
echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"compilegrammar\" value=\"COMPILE GRAMMAR\"><br /><br />";
echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"savegrammar\" value=\"SAVE ‘".$filename."’\"><br /><br />";

$error = FALSE;
if($templates) {
	$action = "templates";
	$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
//	$link_produce .= "&trace_production=1";
	$link_produce .= "&here=".urlencode($here);
//	echo "link = ".$link_produce."<br />";
	$window_name = window_name($filename);
	echo "<input style=\"color:DarkBlue; background-color:azure;\" onclick=\"window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"CHECK TEMPLATES\"><br /><br />";
	}
	
if($produce_all_items > 0) $action = "produce-all";
else $action = "produce";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
$error_mssg = '';
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$error_mssg .= "<font color=\"red\"><small>WARNING: ".$dir.$alphabet_file." not found.</small></font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&alphabet=".urlencode($alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$error_mssg .= "<font color=\"red\"><small>WARNING: ".$dir.$settings_file." not found.</small></font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&settings_file=".urlencode($settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$error_mssg .= "<font color=\"red\"><small>WARNING: ".$dir.$objects_file." not found.</small></font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&objects_file=".urlencode($objects_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir.$csound_file)) {
		$error_mssg .= "<font color=\"red\"><small>WARNING: ".$dir.$csound_file." not found.</small></font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&csound_file=".urlencode($csound_file);
	}
if($csound_orchestra <> '') {
	if(file_exists($dir.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
	}
if($error) echo $error_mssg;
if($test) echo "output = ".$output."<br />";
if($test) echo "output_file = ".$output_file."<br />";
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
// $link_produce .= "&random_seed=".$random_seed;
$link_produce .= "&here=".urlencode($here);
$window_name = window_name($filename);
echo "<b>then…</b>";
echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)\" title=\"Don't forget to save!\"";
if($error) echo " disabled";
echo ">";
echo "</td></tr>";
echo "<tr><td colspan=\"2\"><p style=\"text-align:center;\">➡ <i>You can change above settings, then save the grammar…</i></p></td></tr>";
echo "</table>";

if($settings_file == '') {
	if($metronome > 0) {
		$p_clock = intval($metronome * 10000);
		$q_clock = 600000;
		$gcd = gcd($p_clock,$q_clock);
		$p_clock = $p_clock / $gcd;
		$q_clock = $q_clock / $gcd;
		if(intval($metronome) == $metronome)
			$metronome = intval($metronome);
		else $metronome = sprintf("%.3f",$metronome);
		echo "<p>⏱ Time base: <font color=\"red\">".$p_clock."</font> ticks in <font color=\"red\">".$q_clock."</font> seconds (metronome = <font color=\"red\">".$metronome."</font> beats/mn)<br />";
		if($time_structure == '') $time_structure = "striated";
		echo "⏱ Time structure: <font color=\"red\">".$time_structure."</font></p>";
		}
	else {
		$metronome =  60;
		$p_clock = $q_clock = 1;
		if($time_structure <> '')
			echo "<p>⏱ Metronome (time base) is not correctly specified. It will be set to <font color=\"red\">60</font> beats per minute. Time structure is <font color=\"red\">".$time_structure."</font> as indicated in this grammar.</p>";
		else
			echo "<p>⏱ Metronome (time base) and structure of time are neither specified nor set up by a ‘-se’ file.<br />Therefore they will be set to <font color=\"red\">60</font> beats per minute and <font color=\"red\">striated</font>.</p>";
		$time_structure = "striated";
		}
	}
else {
	$metronome = 0;
	if($p_clock > 0 AND $q_clock > 0) {
		$metronome = 60 * $q_clock / $p_clock;
		if($metronome <> intval($metronome)) $metronome = sprintf("%.3f",$metronome);
		}
	if($metronome > 0) {
		echo "⏱ Metronome = <font color=\"red\">".$metronome."</font> beats/mn and time structure is <font color=\"red\">".$time_structure."</font> as per <font color=\"blue\">‘".$settings_file."’</font><br />";
		if($metronome_in_grammar > 0) {
			echo "• Metronome specification <b>_mm(".$metronome_in_grammar.")</b> in grammar  is ignored because it is set by <font color=\"blue\">‘".$settings_file."’</font><br />";
			}
		}
	else
		echo "⏱ No metronome value found in <font color=\"blue\">‘".$settings_file."’</font><br />";
	}
if($non_stop_improvize > 0) echo "• <font color=\"red\">Non-stop improvize</font> as set by <font color=\"blue\">‘".$settings_file."’</font>: <i>only 10 variations will be produced, no picture</i><br />";

if($found_elsewhere AND $objects_file <> '') echo "• Sound-object prototype file = <font color=\"blue\">‘".$objects_file."’</font> found in <font color=\"blue\">‘".$alphabet_file."’</font><br />";
if($note_convention <> '') echo "• Note convention is <font color=\"blue\">‘".ucfirst(note_convention(intval($note_convention)))."’</font> as per <font color=\"blue\">‘".$settings_file."’</font><br />";
if($produce_all_items == 1) echo "• Produce all items has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($show_production == 1) echo "• Show production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($trace_production == 1) echo "• Trace production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($settings_file <> '' AND isset($random_seed)) {
	if($random_seed > 0)
		echo "• Random seed has been set to <font color=\"red\">".$random_seed."</font> by <font color=\"blue\">‘".$settings_file."’</font><br />";
	else
		echo "• Random seed is ‘no seed’ as per <font color=\"blue\">‘".$settings_file."’</font><br />";
	}
if($max_time_computing > 0) {
	echo "• Max computation time has been set to <font color=\"red\">".$max_time_computing."</font> seconds by <font color=\"blue\">‘".$settings_file."’</font><br />";
	}
if($csound_orchestra <> '') {
	echo "• Csound orchestra file ‘<font color=\"blue\">".$csound_orchestra."</font>’ is mentioned in <font color=\"blue\">‘".$csound_file."’</font>";
	if(!file_exists($dir.$csound_orchestra)) echo "<br />&nbsp;&nbsp;<font color=\"red\">➡&nbsp;yet not in the current folder.</font> Therefore <font color=\"blue\">‘default.orc’</font> will be used instead</p>";
	}
else if($file_format == "csound") {
	if(file_exists($dir."default.orc")) echo "• Csound files will be produced, which Csound will try to convert to sound files using orchestra <font color=\"blue\">‘default.orc’</font></p>";
	else echo "• <font color=\"red\">➡</font> Csound files will be produced, but Csound not be able to convert them to sound files because the default orchestra <font color=\"blue\">‘".$dir."default.orc’</font> is not found.</p>";
	}
echo "</p>";

echo "<input type=\"hidden\" name=\"produce_all_items\" value=\"".$produce_all_items."\">";
echo "<input type=\"hidden\" name=\"show_production\" value=\"".$show_production."\">";
echo "<input type=\"hidden\" name=\"trace_production\" value=\"".$trace_production."\">";
echo "<input type=\"hidden\" name=\"metronome\" value=\"".$metronome."\">";
echo "<input type=\"hidden\" name=\"time_structure\" value=\"".$time_structure."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
echo "<p id=\"topedit\"><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savegrammar\" value=\"SAVE ‘".$filename."’\"></p>";

$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax + 10;

echo "<textarea name=\"thisgrammar\" rows=\"".$textarea_rows."\" style=\"width:90%;\">".$content."</textarea>";

echo "<div style=\"float:left; padding-top:12px;\"><input style=\"color:DarkBlue; background-color:Aquamarine; font-size:large;\" onclick=\"window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)\" title=\"Don't forget to save!\"";
if($error) echo " disabled";
echo ">";
if($error) echo "<br />".$error_mssg;
echo "</div>";
echo "<p style=\"width:90%; text-align:right;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savegrammar\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</form>";

display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

$variable = array();
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$line = preg_replace("/\[.*\]/u",'',$line);
	$line = preg_replace("/,/u",' ',$line);
	$line = preg_replace("/{/u",' ',$line);
	$line = preg_replace("/}/u",' ',$line);
	$line = preg_replace("/:/u",' ',$line);
	if(trim($line) == '') continue;
	if($line == "COMMENT:") break;
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
//	echo $line."<br />";
	$table2 = explode(' ',$line);
	for($j = 0; $j < count($table2); $j++) {
		$word = trim($table2[$j]);
		if($word == '') continue;
		$word = str_replace('"',"§",$word);
		$word = is_variable($note_convention,$word);
		if($word == '') continue;
		if(isset($variable[$word])) continue;
		$variable[$word] = TRUE;
		}
	}

echo "<form method=\"post\" action=\"".$url_this_page."#expression\" enctype=\"multipart/form-data\">";
$action = "produce";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($grammar_file);
if($alphabet_file <> '') $link_produce .= "&alphabet=".urlencode($alphabet_file);
if($settings_file <> '') $link_produce .= "&settings_file=".urlencode($settings_file);
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
// $link_produce .= "&random_seed=".$random_seed;
$link_produce .= "&here=".urlencode($here);
$window_name = window_name($filename);
if(count($variable) > 0) {
	echo "<h3>Variables (click to use as startup string):</h3>";
	ksort($variable);
	foreach($variable as $var => $val) {
		$thislink = $link_produce."&startup=".$var;
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
				$mssg[$i] = clean_up_encoding(FALSE,TRUE,$mssg[$i]);
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
