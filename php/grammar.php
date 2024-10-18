<?php
require_once("_basic_tasks.php");
set_time_limit(0);

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
$url_this_page = "grammar.php?file=".urlencode($file);
save_settings("last_grammar_page",$url_this_page);
$table = explode(SLASH,$file);
$here = $filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
$current_directory = str_replace(SLASH,'/',$current_directory);
save_settings("last_grammar_directory",$current_directory);
$textarea_rows = 20;
$save_warning = '';

if($test) echo "grammar_file = ".$this_file."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
	
$default_output_name = str_replace("-gr.",'',$filename);
$default_output_name = str_replace(".bpgr",'',$default_output_name);
$output_file = $default_output_name;
$file_format = $default_output_format;
if(isset($grammar_file_format[$filename])) $file_format = $grammar_file_format[$filename]; // From _settings.php
if(isset($_POST['output_file'])) {
	$output_file = $_POST['output_file'];
	$output_file = fix_new_name($output_file);
	}

$output_file = add_proper_extension($file_format,$output_file);

$project_name = preg_replace("/\.[a-z]+$/u",'',$output_file);
$result_file = $bp_application_path.$output_folder.SLASH.$project_name."-result.html";

$expression = '';
if(isset($_POST['expression'])) $expression = trim($_POST['expression']);
if(isset($_POST['new_convention']))
	$new_convention = $_POST['new_convention'];
else $new_convention = '';

if(isset($_POST['reload'])) {
	@unlink($refresh_file);
    header("Location: ".$url_this_page);
    exit();	
	}

require_once("_header.php");
display_console_state();

$temp_midi_ressources = $temp_dir."trace_".my_session_id()."_".$filename."_";

$url = "index.php?path=".urlencode($current_directory);
echo "<p>Workspace = <input style=\"background-color:azure;\" name=\"workspace\" type=\"submit\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\">";
// echo "&nbsp;&nbsp;session_id = ".my_session_id();

$hide = $need_to_save = FALSE;
$no_save_midiresources = FALSE;

if(isset($_POST['use_convention'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$old_convention = $_POST['old_convention'];
	$change_octave = 0;
	if($old_convention == 1 AND $new_convention <> 1) $change_octave = +1;
	if($old_convention <> '' AND $old_convention <> 1 AND $new_convention == 1) $change_octave = -1;
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	mb_internal_encoding("UTF-8");  // Set internal character encoding to UTF-8
	$newcontent = mb_ereg_replace("\n","<br>", $newcontent);
	for($i = 0; $i < 12; $i++) {
		$new_note = $_POST['new_note_'.$i];
		for($octave = 15; $octave >= 0; $octave--) {
			$new_octave = $octave + $change_octave;
			if($new_octave < 0) $new_octave = "00";
			if($new_convention <> 0) $newcontent = mb_ereg_replace($Englishnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 0) $newcontent = mb_ereg_replace($AltEnglishnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = mb_ereg_replace($Frenchnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = mb_ereg_replace($AltFrenchnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = mb_ereg_replace($Indiannote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = mb_ereg_replace($AltIndiannote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			}
		}
	$newcontent = mb_ereg_replace("<br>","\n",$newcontent);
	$newcontent = mb_ereg_replace("@",'',$newcontent);
	// This '@' is required to avoid confusion between "re" in Indian and Italian/Spanish/French conventions
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	echo "<div style=\"background-color:white; padding: 1em; border-radius: 6px;\"><p>👉 Current note convention for this grammar should now be <font color=\"red\">‘".ucfirst(note_convention(intval($new_convention)))."’</font>. You need to change it in the settings file.</p></div>";
	}

if(isset($_POST['delete_chan'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_chan\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_ins'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_ins\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_tempo'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_tempo\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['apply_changes_instructions'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$imax = $_POST['chan_max'];
	for($i = 0; $i < $imax; $i++) {
		$argument = $_POST['argument_chan_'.$i];
		$option = $_POST['replace_chan_option_'.$i];
		switch($option) {
			case "chan":
				$new_argument = "@".$_POST['replace_chan_as_chan_'.$i];
				$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@".$_POST['replace_chan_as_ins_'.$i];
				$newcontent = str_replace("_chan(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "chan_ins":
				$new_argument_chan = "@".$_POST['replace_chan_as_chan1_'.$i];
				$new_argument_ins = "@".$_POST['replace_chan_as_ins1_'.$i];
				$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument_chan.") _ins(".$new_argument_ins.")",$newcontent);
			break;
			case "delete":
				$newcontent = str_replace("_chan(".$argument.")",'',$newcontent);
			break;
			}
		}
	$jmax = $_POST['ins_max'];
	for($j = 0; $j < $jmax; $j++) {
		$argument = $_POST['argument_ins_'.$j];
		$option = $_POST['replace_ins_option_'.$j];
		switch($option) {
			case "chan":
				$new_argument = "@".$_POST['replace_ins_as_chan_'.$j];
				$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@".$_POST['replace_ins_as_ins_'.$j];
				$newcontent = str_replace("_chan(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "chan_ins":
				$new_argument_chan = "@".$_POST['replace_ins_as_chan1_'.$j];
				$new_argument_ins = "@".$_POST['replace_ins_as_ins1_'.$j];
				$newcontent = str_replace("_ins(".$argument.")","_chan(".$new_argument_chan.") _ins(".$new_argument_ins.")",$newcontent);
			break;
			case "delete":
				$newcontent = str_replace("_ins(".$argument.")",'',$newcontent);
			break;
			}
		}
	$_POST['thistext'] = str_replace("@",'',$newcontent);
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

$refresh_file = $temp_dir."trace_".my_session_id()."_".$filename."_midiport_refresh";
if(isset($_POST['savemidiport'])) {
	save_midiressources($filename);
	$save_warning = "<span id=\"timespan\" style=\"color:red; float:right; background-color:white;\">&nbsp;…&nbsp;Saved “".$filename."_midiport” file…</span>";
	@unlink($refresh_file);
	}

if($need_to_save OR isset($_POST['savethisfile']) OR isset($_POST['compilegrammar'])) {
	if(isset($_POST['savethisfile'])) $save_warning = "<span id=\"timespan\" style=\"color:red; float:right; background-color:white;\">&nbsp;…&nbsp;Saved “".$filename."” file…</span>";
	$content = $_POST['thistext'];
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['note_convention'])) $note_convention = $_POST['note_convention'];
	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
	else $random_seed = 0;
	$content = recode_entities($content);
	$content = preg_replace("/ +/u",' ',$content);
	save($this_file,$filename,$top_header,$content);
	if($file_format == "rtmidi" AND !$no_save_midiresources) {
		if(file_exists($refresh_file)) {
			read_midiressources($filename);
			@unlink($refresh_file);
			}
		else save_midiressources($filename);
		}
	}
else read_midiressources($filename);


if(isset($_POST['file_format'])) $file_format = $_POST['file_format'];
save_settings2("grammar_file_format",$filename,$file_format); // To _settings.php
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
if($file_format == '') $output_file = $default_output_name;

if(isset($_POST['show_production']))
	$show_production = $_POST['show_production'];
if(isset($_POST['trace_production']))
	$trace_production = $_POST['trace_production'];
if(isset($_POST['produce_all_items']))
	$produce_all_items = $_POST['produce_all_items'];

$output_folder = set_output_folder($output_folder);
$output = $bp_application_path.SLASH.$output_folder;
do $output = str_replace(SLASH.SLASH,SLASH,$output,$count);
while($count > 0);
if(!file_exists($output)) {
	echo "<p><font color=\"red\">Created folder:</font><font color=\"blue\"> ".$output."</font><br />";
	if(!mkdir($output,0775, true))
		error_log("Failed to create directory '{$temp_dir}' with error: " . error_get_last()['message']);
	else
		chmod($output,0775); // Double-check permissions
	}
echo link_to_help();

echo "<h3>Grammar file “".$filename."”</h3>";
save_settings("last_grammar_name",$filename);

/*
$link = "test-image.html";
echo "<div style=\"float:right;\"><p style=\"border:2px solid gray; background-color:azure; width:17em; padding:2px; text-align:center; border-radius: 6px;\">
<a onmouseover=\"popupWindow = window.open('".$link."','CANVAS_test','width=500,height=500,left=200'); return false;\"
   onmouseout=\"popupWindow.close();\"
   href=\"".$link."\">Test image to verify that your<br />environment supports CANVAS</a><br />(You may need to authorize pop-ups)</p></div>";
*/

if(isset($_POST['compilegrammar'])) {
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
	else $settings_file = '';
	if(isset($_POST['csound_file'])) $csound_file = $_POST['csound_file'];
	else $csound_file = '';
	if(isset($_POST['tonality_file'])) $tonality_file = $_POST['tonality_file'];
	else $tonality_file = '';
	$application_path = $bp_application_path;
	$command = $application_path."bp compile";
	$thistext = $dir.$filename;
	if(is_integer(strpos($thistext,' ')))
		$thistext = '"'.$thistext.'"';
	$command .= " -gr ".$thistext;
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
		else $command .= " -al ".$dir.$alphabet_file; // "-al" replaced "-ho" 2024-06-12
		}
	if($csound_file <> '') {
		if(!file_exists($dir_csound_resources.$csound_file)) {
			echo "<p style=\"color:red;\">WARNING: ".$dir_csound_resources.$csound_file." not found.</p>";
			}
		else $command .= " -cs ".$dir_csound_resources.$csound_file;
		}
	if($tonality_file <> '') {
		if(!file_exists($dir_tonality_resources.$tonality_file)) {
			$error_mssg .= "<font color=\"red\">WARNING: ".$dir_tonality_resources.$tonality_file." not found.</font><br />";
			$error = TRUE;
			}
		else $command .= " -to ".$dir_tonality_resources.$tonality_file;
		}
	$tracefile = $temp_dir."trace_".my_session_id()."_".$filename.".txt";
	$command .= " --traceout ".$tracefile;
	echo "<p style=\"color:red;\" id=\"timespan\"><small>".$command."</small></p>";
//	$no_error = FALSE;
	$o = send_to_console($command);
/*	$n_messages = count($o);
	if($n_messages > 0) {
		for($i=0; $i < $n_messages; $i++) {
			$mssg = $o[$i];
			$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
			if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
			}
		} */
//	if(!$no_error) {
	$trace_link = '';
	if(file_exists($dir.$tracefile)) {
		$trace_content = file_get_contents($dir.$tracefile);
		if($trace_content !== false && strlen($trace_content) > 10)
			$trace_link = clean_up_file_to_html($dir.$tracefile);
		}
	if($trace_link <> '') echo "<p><big>👉 <font color=\"red\">Errors found! Open the </font> <a onclick=\"window.open('".$trace_link."','trace','width=800,height=800'); return false;\" href=\"".$trace_link."\">trace file</a>!</big></p>";
	//	}
	else echo "<p><font color=\"red\">➡</font> <font color=\"blue\">No error.</font></p>";
	@unlink($dir.$tracefile);
	// Now reformat the grammar
	reformat_grammar(FALSE,$this_file);
	}
else {
	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
	else $random_seed = 0;
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
	echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
	echo "<input type=\"hidden\" name=\"random_seed\" value=\"".$random_seed."\">";
	echo "Location of output files: <font color=\"blue\">".$bp_application_path."</font>";
	echo "<input type=\"text\" name=\"output_folder\" size=\"15\" value=\"".$output_folder."\">";
	echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"clearsave();\" name=\"change_output_folder\" value=\"SAVE THIS LOCATION\"><br />➡ global setting for all projects in this session<br /><i>Folder will be created if necessary…</i>";
	echo "</form>";
	}

if($test) echo "grammar_file = ".$this_file."<br />";

$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$metronome = 0;
$nature_of_time = $objects_file = $csound_file = $tonality_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$alphabet_file = $extract_data['alphabet'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$tonality_file = $extract_data['tonality'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$interaction_file = $extract_data['interaction'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];
$metronome = $metronome_in_grammar = $extract_data['metronome'];
$time_structure = $extract_data['time_structure'];
if($time_structure == "striated") $nature_of_time = STRIATED;
if($time_structure == "smooth") $nature_of_time = SMOOTH;
$templates = $extract_data['templates'];
$found_elsewhere = FALSE;
if($alphabet_file <> '' AND $objects_file == '') {
	$objects_file = get_name_mi_file($dir.$alphabet_file);
	if($objects_file <> '') $found_elsewhere = TRUE;
	}
$found_orchestra_in_instruments = FALSE;
if($csound_file <> '') {
	if(file_exists($dir.$csound_file))
		rename($dir.$csound_file,$dir_csound_resources.$csound_file);
	$csound_orchestra = get_orchestra_filename($dir_csound_resources.$csound_file);
	if($csound_orchestra <> '') $found_orchestra_in_instruments = TRUE;
	}
else $csound_orchestra = '';
$show_production = $trace_production = $note_convention = $non_stop_improvize = $p_clock = $q_clock = $striated_time = $max_time_computing = $produce_all_items = $random_seed = $quantization = $time_resolution = 0;
$csound_default_orchestra = '';
$diapason = 440; $C4key = 60;
$dir_base = str_replace($bp_application_path,'',$dir);
$found_orchestra_in_settings = $quantize = FALSE;
if($settings_file <> '' AND file_exists($dir.$settings_file)) {
	$show_production = get_setting("show_production",$settings_file);
	$trace_production = get_setting("trace_production",$settings_file);
	$note_convention = get_setting("note_convention",$settings_file);
	$non_stop_improvize = get_setting("non_stop_improvize",$settings_file);
	$max_items = get_setting("max_items",$settings_file);
	$p_clock = get_setting("p_clock",$settings_file);
	$q_clock = get_setting("q_clock",$settings_file);
	$max_time_computing = get_setting("max_time_computing",$settings_file);
	$produce_all_items = get_setting("produce_all_items",$settings_file);
//	if($produce_all_items) $show_production = TRUE;
	$random_seed = get_setting("random_seed",$settings_file);
	$diapason = get_setting("diapason",$settings_file);
	$C4key = get_setting("C4key",$settings_file);
	$csound_default_orchestra = get_setting("csound_default_orchestra",$settings_file);
	$time_resolution = get_setting("time_resolution",$settings_file);
	$quantization = get_setting("quantization",$settings_file);
	$quantize = get_setting("quantize",$settings_file);
	$nature_of_time_settings = get_setting("nature_of_time",$settings_file);
	if($csound_default_orchestra <> '') $found_orchestra_in_settings = TRUE;
	}

if($test) echo "url_this_page = ".$url_this_page."<br />";

$csound_is_responsive = FALSE;
echo "<div style=\"float:right; background-color:white; padding:6px; border-radius: 12px;\">";
$csound_is_responsive = check_csound();
link_to_tonality();
echo "</div>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<table cellpadding=\"8px;\" style=\"background-color:white; border-radius: 15px; border: 1px solid black;\"><tr style=\"\">";
echo "<td style=\"white-space:nowrap;\">";
if($file_format <> "rtmidi") {
	for($i = 0; $i < $NumberMIDIoutputs; $i++) {
		echo "<input type=\"hidden\" name=\"MIDIoutput_".$i."\" value=\"".$MIDIoutput[$i]."\">";
		echo "<input type=\"hidden\" name=\"MIDIoutputname_".$i."\" value=\"".$MIDIoutputname[$i]."\">";
		}
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		echo "<input type=\"hidden\" name=\"MIDIinput_".$i."\" value=\"".$MIDIinput[$i]."\">";
		echo "<input type=\"hidden\" name=\"MIDIinputname_".$i."\" value=\"".$MIDIinputname[$i]."\">";
		}
	echo "<p>Name of output file (with proper extension):<br />";
	echo "<input type=\"text\" name=\"output_file\" size=\"25\" value=\"".$output_file."\"></p>";
	}
else {
	echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
	display_midi_ports($filename);
	}
read_midiressources($filename);
// if($file_format == "rtmidi") echo " 👉 Delete the name if you change a number!";
echo "</td>";
echo "<td>";
if($file_format == '') {
	$file_format = "rtmidi";
	save_settings2("grammar_file_format",$filename,$file_format);
	}
echo "<input type=\"radio\" name=\"file_format\" value=\"rtmidi\"";
if($file_format == "rtmidi") echo " checked";
echo ">Real-time MIDI";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"data\"";
if($file_format == "data") echo " checked";
echo ">BP data file";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"midi\"";
if($file_format == "midi") echo " checked";
echo ">MIDI file";
if(file_exists("csound_version.txt")) {
	echo "<br /><input type=\"radio\" name=\"file_format\" value=\"csound\"";
	if($file_format == "csound") echo " checked";
	echo ">CSOUND file";
	}
echo "<br />&nbsp;&nbsp;&nbsp;";
if($file_format == "rtmidi") echo "<input id=\"refresh\" style=\"background-color:yellow; display:none;\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."\" name=\"reload\" value=\"REFRESH\">&nbsp;";
echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."\" name=\"savethisfile\" value=\"SAVE format\">";
echo "</td>";
echo "<td id=\"hideshow\" style=\"text-align:right; vertical-align:middle;\" rowspan=\"2\">";
echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
echo "<input type=\"hidden\" name=\"tonality_file\" value=\"".$tonality_file."\">";
echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"><br /><br />";

$error = FALSE;
if($templates) {
	$action = "templates";
	$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
//	$link_produce .= "&trace_production=1";
	$link_produce .= "&here=".urlencode($here);
	$window_name = window_name($filename);
	echo "<input style=\"color:DarkBlue; background-color:azure;\" onclick=\"if(checksaved()) window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"CHECK TEMPLATES\"><br /><br />";
	}
	
if($produce_all_items > 0) $action = "produce-all";
else $action = "produce";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
$error_mssg = '';
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$error_mssg .= "<font color=\"red\">WARNING: ".$dir.$alphabet_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&alphabet=".urlencode($dir.$alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$url_settings = "settings.php?file=".urlencode($dir_base.$settings_file);
		$error_mssg .= "<font color=\"red\">WARNING: ".$dir_base.$settings_file." not found.</font>";
		$error_mssg .= "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_settings."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"CREATE IT\"><br />";
		$error = TRUE;
		}
	else $link_produce .= "&settings=".urlencode($dir.$settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$error_mssg .= "<font color=\"red\">WARNING: ".$dir.$objects_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&objects=".urlencode($dir.$objects_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir_csound_resources.$csound_file)) {
		$error_mssg .= "<font color=\"red\">WARNING: ".$dir_csound_resources.$csound_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&csound_file=".urlencode($csound_file);
	}
if($tonality_file <> '') {
	if(!file_exists($dir_tonality_resources.$tonality_file)) {
		$error_mssg .= "<font color=\"red\">WARNING: ".$dir_tonality_resources.$tonality_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_produce .= "&tonality_file=".urlencode($tonality_file);
	}

if($csound_orchestra == '') $csound_orchestra = $csound_default_orchestra;
if($csound_orchestra <> '') {
	if(file_exists($dir.$csound_orchestra)) {
		rename($dir.$csound_orchestra,$dir_csound_resources.$csound_orchestra);
		sleep(1);
		}
	check_function_tables($dir,$csound_file);
	if(file_exists($dir_csound_resources.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
	}
$url_settings = "settings.php?file=".urlencode($dir_base.$settings_file);
if($file_format == "csound") {
	$cs = $output_file;
	$output_file = str_replace(".sco",'',$output_file);
	$link_produce .= "&score=".urlencode($output.SLASH.$cs);
	}
if($file_format == "midi") {
	$midi_file = $output_file;
	$output_file = str_replace(".mid",'',$output_file);
	$link_produce .= "&midifile=".urlencode($output.SLASH.$midi_file);
	}
if(($file_format == "rtmidi" OR $file_format == "csound" OR $file_format == "midi") AND $action == "produce-all") $output_file .= ".bpda";
if($error) echo $error_mssg;
if($test) echo "output = ".$output."<br />";
if($test) echo "output_file = ".$output_file."<br />";
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
$link_produce .= "&here=".urlencode($here);
$window_name = window_name($filename);
echo "<b>then…</b>";
// echo "<p>@@@ ".$link_produce."</p>";
if($file_format == "rtmidi" AND file_exists($refresh_file)) $refresh_instruction = "document.getElementById('refresh').style.display = 'inline';";
else $refresh_instruction = '';
echo "&nbsp;<input onclick=\"event.preventDefault(); if(checksaved()) {".$refresh_instruction." window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)\"";
if($error) echo " disabled style=\"background-color:azure; box-shadow: none;\"";
else echo " style=\"color:DarkBlue; background-color:Aquamarine;\"";
echo ">";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<br /><div style=\"background-color:white; padding:1em; width:690px; border-radius: 15px;\">";
if($settings_file <> '' AND file_exists($dir.$settings_file)) echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_settings."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$settings_file."’\">";
if($settings_file == '' OR !file_exists($dir.$settings_file)) {
	$time_resolution = 10; //  10 milliseconds by default
	if($metronome > 0) {
		$p_clock = intval($metronome * 10000);
		$q_clock = 600000;
		$gcd = gcd($p_clock,$q_clock);
		$p_clock = $p_clock / $gcd;
		$q_clock = $q_clock / $gcd;
		if(intval($metronome) == $metronome) $metronome = intval($metronome);
		else $metronome = sprintf("%.3f",$metronome);
		echo "<p>⏱ Time base: <font color=\"red\">".$p_clock."</font> ticks in <font color=\"red\">".$q_clock."</font> seconds (metronome = <font color=\"red\">".$metronome."</font> beats/mn)<br />";
		if(!is_numeric($nature_of_time)) $nature_of_time = STRIATED;
		}
	else {
		$metronome =  60;
		$p_clock = $q_clock = 1;
		if($time_structure <> '')
			echo "<p>⏱ Metronome (time base) is not properly specified. It will be set to <font color=\"red\">60</font> beats per minute. Time structure is <font color=\"red\">".$time_structure."</font> as indicated in data.</p>";
		else {
			$nature_of_time = STRIATED;
			echo "<p>⏱ Metronome (time base) and structure of time are neither specified in grammar nor set up by a ‘-se’ file.<br />Therefore metronome will be set to <font color=\"red\">60</font> beats per minute and time structure to <font color=\"red\">STRIATED</font>.</p>";
			}
		}
	echo "•&nbsp;Time resolution = <font color=\"red\">".$time_resolution."</font> milliseconds (by default)<br />";
	echo "•&nbsp;No quantization<br />";
	}
else {
	if($p_clock > 0 AND $q_clock > 0) {
		$metronome_settings = 60 * $q_clock / $p_clock;
		}
	else $metronome_settings = 0;
	if($metronome > 0 AND $metronome <> $metronome_settings) {
		echo "➡&nbsp;Conflict: metronome is ".$metronome_settings." beats/mn as per <font color=\"blue\">‘".$settings_file."’</font> and ".$metronome." beats/mn in grammar. We'll use <font color=\"red\">".$metronome."</font> beats/mn<br />";
		}
	if(!is_numeric($metronome)) $metronome = $metronome_settings;
	if($metronome <> intval($metronome)) $metronome = sprintf("%.3f",$metronome);
	if(($nature_of_time_settings <> $nature_of_time) AND (is_numeric($nature_of_time))) {
		echo "➡&nbsp;Conflict: time structure is ".nature_of_time($nature_of_time_settings)." as per <font color=\"blue\">‘".$settings_file."’</font> and ".nature_of_time($nature_of_time)." in grammar. We'll use ".nature_of_time($nature_of_time)."<br />";
		}
	if(!is_numeric($nature_of_time)) $nature_of_time = $nature_of_time_settings;
	if($metronome > 0. AND $nature_of_time == STRIATED) {
		echo "⏱ Metronome = <font color=\"red\">".$metronome."</font> beats/mn<br />";
		}
	echo "•&nbsp;Time resolution = <font color=\"red\">".$time_resolution."</font> milliseconds as per <font color=\"blue\">‘".$settings_file."’</font><br />";
	if($quantize) {
		echo "•&nbsp;Quantization = <font color=\"red\">".$quantization."</font> milliseconds as per <font color=\"blue\">‘".$settings_file."’</font>";
		if($time_resolution > $quantization) echo "&nbsp;<font color=\"red\">➡</font>&nbsp;may be raised to <font color=\"red\">".$time_resolution."</font>&nbsp;ms…";
		echo "<br />";
		}
	else echo "•&nbsp;No quantization<br />";
	}
if($nature_of_time == STRIATED) echo "•&nbsp;Time is <font color=\"red\">".nature_of_time($nature_of_time)."</font><br />";
else echo "•&nbsp;Time is <font color=\"red\">".nature_of_time($nature_of_time)."</font> (no fixed tempo)<br />";
if($non_stop_improvize > 0) {
	echo "• <font color=\"red\">Non-stop improvize</font> as set by <font color=\"blue\">‘".$settings_file."’</font>";
	if($file_format <> "rtmidi") echo ": <i>only ".$max_items." variations will be produced</i>";
	echo "<br />";
	}
if($diapason <> 440) echo "• <font color=\"red\">Diapason</font> (A4 frequency) = <font color=\"red\">".$diapason."</font> Hz as set by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($C4key <> 60) {
	echo "• <font color=\"red\">C4 key number</font> = <font color=\"red\">".$C4key."</font> as set by <font color=\"blue\">‘".$settings_file."’</font>";
	if($file_format == "csound") echo " ➡ this has no incidence on Csound scores";
	echo "<br />";
	}
if($found_elsewhere AND $objects_file <> '') echo "• Sound-object prototype file = <font color=\"blue\">‘".$objects_file."’</font> found in <font color=\"blue\">‘".$alphabet_file."’</font><br />";
if($note_convention <> '') echo "• Note convention is <font color=\"red\">‘".ucfirst(note_convention(intval($note_convention)))."’</font> as per <font color=\"blue\">‘".$settings_file."’</font><br />";
else echo "• Note convention is <font color=\"red\">‘English’</font> by default<br />";
if($produce_all_items == 1) {
	echo "• Produce all items has been set ON by <font color=\"blue\">‘".$settings_file."’</font>";
	if($file_format <> "rtmidi") echo ": <i>only ".$max_items." variations will be produced</i>";
	echo "<br />";
	}
else if($show_production == 1) echo "• Show production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($trace_production == 1) echo "• Trace production has been set ON by <font color=\"blue\">‘".$settings_file."’</font><br />";
if($settings_file <> '' AND file_exists($dir.$settings_file) AND isset($random_seed) AND $non_stop_improvize > 0) {
	if($random_seed > 0)
		echo "• Random seed has been set to <font color=\"red\">".$random_seed."</font> by <font color=\"blue\">‘".$settings_file."’</font> ➡ Series will be repeated.<br />";
	else
		echo "• Random seed is ‘no seed’ as per <font color=\"blue\">‘".$settings_file."’</font> ➡ Series will vary.<br />";
	}
if($max_time_computing > 0) {
	echo "• Max console computation time has been set to <font color=\"red\">".$max_time_computing."</font> seconds by <font color=\"blue\">‘".$settings_file."’</font>";
	if($max_time_computing < 30) echo "&nbsp;<font color=\"red\">➡</font>&nbsp;probably too small!";
	echo "<br />";
	}
if($file_format == "csound") {
	if($csound_orchestra <> '' AND file_exists($dir.$csound_orchestra)) {
		rename($dir.$csound_orchestra,$dir_csound_resources.$csound_orchestra);
		sleep(1);
		}
	if($csound_default_orchestra <> '' AND file_exists($dir.$csound_default_orchestra)) {
		rename($dir.$csound_default_orchestra,$dir_csound_resources.$csound_default_orchestra);
		sleep(1);
		}
	check_function_tables($dir,$csound_file);
	if($csound_is_responsive) {
		if($found_orchestra_in_instruments AND file_exists($dir_csound_resources.$csound_orchestra)) {
			echo "• <font color=\"red\">Csound scores</font> will be produced and converted to sound files (including scales) using orchestra ‘<font color=\"blue\">".$csound_orchestra."</font>’ as specified in <font color=\"blue\">‘".$csound_file."’</font>";
			if($found_orchestra_in_settings AND file_exists($dir_csound_resources.$csound_default_orchestra) AND $csound_orchestra <> $csound_default_orchestra) echo "<br />&nbsp;&nbsp;<font color=\"red\">➡</font> Orchestra ‘<font color=\"blue\">".$csound_default_orchestra."</font>’ specified in <font color=\"blue\">‘".$settings_file."’</font> will be ignored</font>";
			}
		else if($found_orchestra_in_instruments AND !$found_orchestra_in_settings AND $csound_orchestra <> '') {
			echo "<font color=\"red\">➡</font> Csound scores will be produced, yet conversion to sound files will not be possible because orchestra ‘<font color=\"blue\">".$csound_orchestra."</font>’ specified in ‘<font color=\"blue\">".$csound_file."</font>’ was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		else if($csound_default_orchestra <> '' AND file_exists($dir_csound_resources.$csound_default_orchestra)) {
			$csound_orchestra = $csound_default_orchestra;
			echo "• <font color=\"red\">Csound scores</font> will be produced and converted to sound files (including scales) using orchestra ‘<font color=\"blue\">".$csound_default_orchestra."</font>’</font>";
			}
		else if($csound_default_orchestra <> '') {
			echo "<font color=\"red\">➡</font> Csound scores will be produced yet not converted to sound files by orchestra ‘<font color=\"blue\">".$csound_default_orchestra."</font>’ as specified in ‘<font color=\"blue\">".$settings_file."</font>’ because this file was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		else if(file_exists($dir_csound_resources."0-default.orc")) {
			$csound_orchestra = "0-default.orc";
			echo "• <font color=\"red\">Csound scores</font> will be produced and converted to sound files using default orchestra file ‘<font color=\"blue\">".$csound_orchestra."</font>’";
			}
		else {
			echo "<font color=\"red\">➡</font> Csound scores will be produced yet not converted to sound files</font> because default orchestra file ‘<font color=\"blue\">0-default.orc</font>’ was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		if(file_exists($dir_csound_resources.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
		}
	else echo "<font color=\"red\">➡</font> Csound scores will be produced yet not converted to sound files because Csound is not installed or not responding";
	echo "<br />";
	}
echo "</div>";

$content = show_instruments_and_scales($dir,$objects_file,$content,$url_this_page,$filename,$file_format);
	
echo "<input type=\"hidden\" name=\"produce_all_items\" value=\"".$produce_all_items."\">";
echo "<input type=\"hidden\" name=\"show_production\" value=\"".$show_production."\">";
echo "<input type=\"hidden\" name=\"trace_production\" value=\"".$trace_production."\">";
echo "<input type=\"hidden\" name=\"metronome\" value=\"".$metronome."\">";
echo "<input type=\"hidden\" name=\"time_structure\" value=\"".$time_structure."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";

echo "<span  id=\"topedit\">&nbsp;</span>";

echo $save_warning;
echo "<button style=\"background-color:yellow; border-radius: 6px; font-size:large;\" onclick=\"togglesearch(); return false;\">SEARCH & REPLACE</button><p></p>";

find_replace_form();
echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" id=\"saveButton\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".$filename."’\">";
if((file_exists($output.SLASH.$default_output_name.".wav") OR file_exists($output.SLASH.$default_output_name.".mid") OR file_exists($output.SLASH.$default_output_name.".html") OR file_exists($output.SLASH.$default_output_name.".sco")) AND file_exists($result_file)) {
	echo "&nbsp;&nbsp;&nbsp;<input style=\"color:DarkBlue; background-color:azure; font-size:large;\" onclick=\"window.open('".$result_file."','result','width=800,height=600,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"Show latests results\">";
	}
echo "&nbsp;<input style=\"background-color:azure; font-size:large;\" type=\"submit\" onclick=\"clearsave();\" name=\"compilegrammar\" value=\"COMPILE GRAMMAR\">";

echo "&nbsp;<input onclick=\"event.preventDefault(); if(checksaved()) {".$refresh_instruction." window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)";
if($error) {
	echo " - disabled because of missing files\"";
	echo " disabled style=\"background-color:azure; box-shadow: none; font-size:large;\"";
	}
else echo "\" style=\"color:DarkBlue; background-color:Aquamarine; font-size:large;\"";
echo ">";
echo "</p>";

$content = do_replace($content);

if($error) echo "<p>".$error_mssg."</p>";
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax + 5;
echo "<textarea name=\"thistext\" onchange=\"tellsave()\" rows=\"".$textarea_rows."\" style=\"width:90%;\">".$content."</textarea>";

// echo "<div style=\"float:right; margin-right:100px;\">";
echo "<p style=\"float:right; margin-right:100px;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
// echo "</div>";
echo "<div>";
echo "<input onclick=\"event.preventDefault(); if(checksaved()) {".$refresh_instruction." window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)";
if($error) {
	echo " - disabled because of missing files\"";
	echo " disabled style=\"background-color:azure; box-shadow: none; font-size:large;\"";
	}
else echo "\" style=\"color:DarkBlue; background-color:Aquamarine; font-size:large;\"";
// echo " title=\"Don't forget to save!\"";
echo ">";
$link_test = $link_produce."&test";
$display_command_title = "DisplayCommand".$filename;
echo "&nbsp;<input style=\"color:DarkBlue; background-color:Azure; font-size:large;\" onclick=\"window.open('".$link_test."','".$display_command_title."','width=1000,height=200,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"Display command line\">";
echo "</div>";
echo "</form>";
display_more_buttons(FALSE,$content,$url_this_page,$dir,'',$objects_file,$csound_file,$tonality_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

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
$action = "play";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
if($alphabet_file <> '') $link_produce .= "&alphabet=".urlencode($dir.$alphabet_file);
if($settings_file <> '' AND file_exists($dir.$settings_file)) $link_produce .= "&settings=".urlencode($dir.$settings_file);
if($objects_file <> '') $link_produce .= "&objects=".urlencode($dir.$objects_file);
if($csound_file <> '') $link_produce .= "&csound_file=".urlencode($csound_file);
if(file_exists($dir_csound_resources.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
$link_produce .= "&here=".urlencode($here);
$window_name = window_name($filename);
if(count($variable) > 0) {
	echo "<h3>Variables (click to use as startup string):</h3>";
	ksort($variable);
	$j = 0;
	foreach($variable as $var => $val) {
		$data = $temp_dir.$temp_folder.SLASH.$j.".bpda";
		$j++;
		$handle = fopen($data,"w");
		fwrite($handle,$var."\n");
		fclose($handle);
		$link_play_variable = $link_produce;
		$link_play_variable .= "&data=".urlencode($data);
		echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\"  onclick=\"if(checksaved()) window.open('".$link_play_variable."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" value=\"".$var."\"> ";
		}
	}
	
$data_expression = $temp_dir.$temp_folder.SLASH."startup.bpda";
if($expression == '') {
	$expression = @file_get_contents($data_expression,TRUE);
	}
$recoded_expression = recode_tags($expression);
$link_play_expression = $link_produce;
$link_play_expression .= "&data=".urlencode($data_expression);
$window_name .= "_startup";
echo "<hr>";

echo "<p id=\"expression\">Use the following (polymetric) expression as startup:<br />";
echo "";
echo "<textarea name=\"expression\" rows=\"5\" style=\"width:700px;\">".$recoded_expression."</textarea>";
echo "<br />";
$error_mssg = '';
$n1 = substr_count($expression,'{');
$n2 = substr_count($expression,'}');
if($n1 > $n2) $error_mssg .= "<font color=\"red\">This expression contains ".($n1-$n2)." extra ‘{'</font>";
if($n2 > $n1) $error_mssg .= "<font color=\"red\">This expression contains ".($n2-$n1)." extra ‘}'</font>";
if($error_mssg <> '') echo "<p>".$error_mssg."</p>";
if(isset($_POST['saveexpression'])) {
	if($expression == '') {
		echo "<p id=\"timespan\"><font color=\"red\">➡ Cannot play empty expression…</font></p>";
		}
	else {
		$expression = recode_entities($expression);
		echo "<p id=\"timespan\"><font color=\"red\">➡ Saving:</font> <font color=\"blue\"><big>".$recoded_expression."</big></font></p>";
	//	$result_file = $output.SLASH.$output_file;
		$handle = fopen($data_expression,"w");
		fwrite($handle,$expression."\n");
		fclose($handle);
		}
	}
echo "<input  type=\"submit\" onclick=\"clearsave();\" name=\"saveexpression\" style=\"background-color:azure;\" value=\"SAVE THIS EXPRESSION\">&nbsp;then&nbsp;<input onclick=\"window.open('".$link_play_expression."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" value=\"PRODUCE ITEM\"";
if(!file_exists($data_expression)) echo " disabled style=\"background-color:azure; box-shadow: none;\"";
else echo " style=\"color:DarkBlue; background-color:Aquamarine;\"";
echo ">";
echo "<span id=\"topchanges\"></span>";

if(isset($_POST['change_convention']) AND isset($_POST['new_convention'])) {
	$new_convention = $_POST['new_convention'];
	echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
	echo "<input type=\"hidden\" name=\"old_convention\" value=\"".$note_convention."\">";
	switch($new_convention) {
		case '0':
			$standard_note = $Englishnote;
			$alt_note = $AltEnglishnote;
			break;
		case '1':
			$standard_note = $Frenchnote;
			$alt_note = $AltFrenchnote;
			break;
		case '2':
			$standard_note = $Indiannote;
			$alt_note = $AltIndiannote;
			break;
		}
	echo "<table style=\"background-color:white;\">";
	echo "<tr>";
	for($i = 0; $i < 12; $i++) {
		echo "<td>";
		echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\" checked><br /><b><font color=\"red\">".$standard_note[$i];
		echo "</font></b></td>";
		}
	echo "</tr>";
	echo "<tr>";
	for($i = 0; $i < 12; $i++) {
		echo "<td>";
		if($alt_note[$i] <> $standard_note[$i]) {
			echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$alt_note[$i]."\"><br /><b><font color=\"red\">".$alt_note[$i];
			echo "</font></b>";
			}
		echo "</td>";
		}
	echo "</tr>";
	echo "</table>";
	echo "&nbsp;<input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
	echo "&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"use_convention\" value=\"USE THIS CONVENTION\">";
	$hide = TRUE;
	}
	
if(isset($_POST['manage_instructions'])) {
	echo "<hr>";
	$list_of_arguments_chan = list_of_arguments($content,"_chan(");
	$list_of_arguments_ins = list_of_arguments($content,"_ins(");
//	for($i = 0; $i < count($list_of_arguments_ins); $i++) echo "“".$list_of_arguments_ins[$i]."”<br />";
	echo "<table style=\"background-color:cornsilk; border-spacing:6px;\">";
	echo "<tr><td><b>Instruction</b></td><td style=\"text-align:center;\"><b>Replace with…</b></td><td><b>Instruction</b></td><td style=\"text-align:center;\"><b>Replace with…</b></td></tr>";
	$imax = count($list_of_arguments_chan);
	echo "<input type=\"hidden\" name=\"chan_max\" value=\"".$imax."\">";
	echo "<tr>";
	for($i = $col = 0; $i < $imax; $i++) {
		echo "<td style=\"vertical-align:middle;\"><font color=\"MediumTurquoise\"><b>_chan(".$list_of_arguments_chan[$i].")</b></font></td>";
		echo "<input type=\"hidden\" name=\"argument_chan_".$i."\" value=\"".$list_of_arguments_chan[$i]."\">";
		echo "<td style=\"vertical-align:middle; padding:2px; background-color:white;\">";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"chan\"";
		echo " checked";
		echo "> _chan(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_chan_".$i."\" size=\"4\" value=\"".$list_of_arguments_chan[$i]."\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"ins\">";
		echo "_ins(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_ins_".$i."\" size=\"6\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"chan_ins\">";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_chan1_".$i."\" size=\"6\" value=\"\">)&nbsp;";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_ins1_".$i."\" size=\"6\" value=\"\">)<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"delete\"> <i>delete _chan(".$list_of_arguments_chan[$i].")</i>";
		echo "</td>";
		$col++;
		if($col == 2) {
			echo "</tr><tr>";
			$col = 0;
			}
		}
	echo "</tr>";
	$jmax = count($list_of_arguments_ins);
	echo "<input type=\"hidden\" name=\"ins_max\" value=\"".$jmax."\">";
	echo "<tr>";
	for($j = $col = 0; $j < $jmax; $j++) {
		echo "<td style=\"vertical-align:middle;\"><font color=\"MediumTurquoise\"><b>_ins(".$list_of_arguments_ins[$j].")</b></font></td>";
		echo "<input type=\"hidden\" name=\"argument_ins_".$j."\" value=\"".$list_of_arguments_ins[$j]."\">";
		echo "<td style=\"vertical-align:middle; padding:2px;; background-color:white;\">";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"chan\"> _chan(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_chan_".$j."\" size=\"4\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"ins\" checked>";
		echo "_ins(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_ins_".$j."\" size=\"6\" value=\"".$list_of_arguments_ins[$j]."\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"chan_ins\">";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_chan1_".$j."\" size=\"6\" value=\"\">)&nbsp;";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_ins1_".$j."\" size=\"6\" value=\"\">)<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"delete\"> <i>delete ins(".$list_of_arguments_ins[$j].")</i>";
		echo "</td>";
		$col++;
		if($col == 2) {
			echo "</tr><tr>";
			$col = 0;
			}
		}
	echo "</tr>";
	echo "<tr><td></td><td></td><td><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"if(checksaved()) {this.form.target='_self'; return true;} else return false;\" name=\"apply_changes_instructions\" formaction=\"".$url_this_page."#topedition\" value=\"APPLY THESE CHANGES\"></td></tr>";
	echo "</table>";
	$hide = TRUE;
	}
if(!$hide) {
	echo "<table style=\"background-color:white; border-radius: 15px;\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"if(checksaved()) {this.form.target='_self';return true;} else return false;\" name=\"change_convention\" formaction=\"".$url_this_page."#topchanges\" value=\"APPLY NOTE CONVENTION to this data\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "</td>";
	echo "</tr><tr><td colspan=2>";
	if($note_convention <> '') {
		echo "<p>Current note convention for this grammar is:<br /><font color=\"red\">‘".ucfirst(note_convention(intval($note_convention)))."’</font> as per <font color=\"blue\">‘".$settings_file."’</font><br />You will need to change it after applying a different convention.</p>";
		}
	echo "</td></tr></table>";
	echo "<hr>";
	$found_chan = substr_count($content,"_chan(");
	$found_ins = substr_count($content,"_ins(");
	$found_tempo = substr_count($content,"_tempo(");
	$found_volume = substr_count($content,"_volume(");
	if($found_chan > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_chan\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _chan()\">&nbsp;";
	if($found_ins > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_ins\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _ins()\">&nbsp;";
	if($found_tempo > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_tempo\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _tempo()\">&nbsp;";
	if($found_volume > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_volume\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _volume()\">&nbsp;";
	if($found_chan > 0  OR $found_ins > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"manage_instructions\" formaction=\"".$url_this_page."#topchanges\" value=\"MANAGE _chan() AND _ins()\">&nbsp;";
	}
echo "</form>";
echo "<script>\n";
echo "window.onload = function() {
    toggleAllDisplays($NumberMIDIinputs); settogglesearch(); settogglescales();
	};\n";
echo "</script>\n";
echo "</body>";
echo "</html>";

function save($this_file,$filename,$top_header,$save_content) {
	if(trim($save_content) == '') return;
    if(file_exists($this_file)) {
        $backup_file = $this_file."_bak";
        if(!copy($this_file, $backup_file))
            echo "<p>👉 <font color=\"red\">Failed to create backup of the file.</p>";
		}
	$handle = @fopen($this_file, "w");
	if($handle) {
		$file_header = $top_header . "\n// Grammar saved as \"" . $filename . "\". Date: " . gmdate('Y-m-d H:i:s');
		fwrite($handle, $file_header . "\n");
		fwrite($handle, $save_content);
		fclose($handle);
		}
	else echo "<div style=\"background-color:white; padding: 1em; border-radius: 6px;\"><p>👉 <font color=\"red\"><b>WARNING</b>: Some files have been imported and cannot be modified.</font></p><p><b>Linux user?</b> Open your terminal and type: <font color=\"blue\">sudo /opt/lampp/htdocs/bolprocessor/change_permissions.sh</font><br />(Your password will be required...)</p></div>";
	return;
	}
?>
