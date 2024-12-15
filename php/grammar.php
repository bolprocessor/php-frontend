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
// echo "skin = ".$skin."<br />";

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

echo "<p>";
$url = "index.php?path=".urlencode($current_directory);
echo "&nbsp;Workspace = <input title=\"List this workspace\" class=\"edit\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\">";

$hide = $need_to_save = FALSE;
$no_save_midiresources = FALSE;

if(isset($_POST['use_convention'])) {
	$new_convention = use_convention($this_file);
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	echo "<div class=\"warning\">👉 Current note convention for this grammar will now be <span class=\"red-text\">‘".ucfirst(note_convention(intval($new_convention)))."’</span>. If necessary, change it in the settings file.</div>";
	}

if(isset($_POST['delete_chan'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_chan\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_ins'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_ins\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_part'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_part\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_tempo'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_tempo\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['apply_changes_instructions'])) {
	$content = @file_get_contents($this_file,TRUE);
	$newcontent = apply_changes_instructions($content);
	$_POST['thistext'] = str_replace("@&",'',$newcontent);
	$need_to_save = TRUE;
	$no_save_midiresources = TRUE;
	}

$refresh_file = $temp_dir."trace_".my_session_id()."_".$filename."_midiport_refresh";
if(isset($_POST['savemidiport'])) {
	save_midiressources($filename,TRUE);
	$save_warning = "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;…&nbsp;Saved “".$filename."_midiport” file…</span>";
	@unlink($refresh_file);
	}

if(isset($_POST['new_file_format'])) {
	$file_format = $_POST['new_file_format'];
	if($file_format == "rtmidi") read_midiressources($filename);
	$no_save_midiresources = TRUE;
	}
save_settings2("grammar_file_format",$filename,$file_format); // To _settings.php
if($need_to_save OR isset($_POST['savethisfile']) OR isset($_POST['compilegrammar'])) {
	if(isset($_POST['savethisfile'])) $save_warning = "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;…&nbsp;Saved “".$filename."” file…</span>";
	$content = $_POST['thistext'];
	if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
	else $alphabet_file = '';
	if(isset($_POST['note_convention'])) $note_convention = $_POST['note_convention'];
//	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
//	else $random_seed = 0;
	$content = recode_entities($content);
	$content = preg_replace("/ +/u",' ',$content);
	save($this_file,$filename,$top_header,$content);
	if($file_format == "rtmidi" AND !$no_save_midiresources) {
		if(file_exists($refresh_file)) {
			read_midiressources($filename);
			store_midiressources($filename);
			@unlink($refresh_file);
			}
		else save_midiressources($filename,FALSE);
		}
	}
else read_midiressources($filename);


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
	echo "<p><span class=\"red-text\">Created folder:</span><span class=\"green-text\"> ".$output."</span><br />";
	if(!mkdir($output,0775, true))
		error_log("Failed to create directory '{$temp_dir}' with error: " . error_get_last()['message']);
	else
		chmod($output,0775); // Double-check permissions
	}
echo link_to_help();

echo "<h2>Grammar project “".$filename."”</h2>";
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
			$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir_tonality_resources.$tonality_file." not found.</span><br />";
			$error = TRUE;
			}
		else $command .= " -to ".$dir_tonality_resources.$tonality_file;
		}
	$tracefile = $temp_dir."trace_".my_session_id()."_".$filename.".txt";
	$command .= " --traceout ".$tracefile;
	echo "<span id=\"timespan\" style=\"color:red; background-color:white; padding:6px; border-radius:6px;\">".$command."</span>";
	$o = send_to_console($command);
	$trace_link = '';
	if(file_exists($dir.$tracefile)) {
		$trace_content = file_get_contents($dir.$tracefile);
		if($trace_content !== false && strlen($trace_content) > 10)
			$trace_link = clean_up_file_to_html($dir.$tracefile);
		}
	if($trace_link <> '') echo "<p><big>👉 <span class=\"red-text\">Errors found! Open the </span> <a onclick=\"window.open('".nice_url($trace_link)."','trace','width=800,height=800'); return false;\" href=\"".nice_url($trace_link)."\">trace file</a>!</big></p>";
	//	}
	else echo "<p><span class=\"red-text\">➡</span> <span class=\"green-text\">No error.</span></p>";
	@unlink($dir.$tracefile);
	reformat_grammar(FALSE,$this_file);
	}
else {
/*	if(isset($_POST['random_seed'])) $random_seed = $_POST['random_seed'];
	else $random_seed = 0; */
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
	// echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
//	echo "<input type=\"hidden\" name=\"random_seed\" value=\"".$random_seed."\">";
	echo "Location of output files: <span class=\"green-text\">".$bp_application_path."</span>";
	echo "<input type=\"text\" name=\"output_folder\" size=\"15\" value=\"".$output_folder."\">";
	echo "&nbsp;<input class=\"save\" type=\"submit\" onclick=\"clearsave();\" name=\"change_output_folder\" value=\"SAVE THIS LOCATION\"><br />➡ global setting for all projects in this session<br /><i>Folder will be created if necessary…</i>";
	echo "</form>";
	}

if($test) echo "grammar_file = ".$this_file."<br />";

$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$metronome = 0;
$nature_of_time = $objects_file = $csound_file = $tonality_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
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
	convert_to_json($dir,$settings_file);
	$content_json = @file_get_contents($dir.$settings_file,TRUE);
	$settings = json_decode($content_json,TRUE);
	$show_production = $settings['Display_production']['value'];
	$trace_production = $settings['Trace_production']['value'];
	$note_convention = $settings['Note_convention']['value'];
	$non_stop_improvize = $settings['Non-stop_improvize']['value'];
	$max_items = $settings['Max_items_produced']['value'];
	$p_clock = $settings['Pclock']['value'];
	$q_clock = $settings['Qclock']['value'];
	$max_time_computing = $settings['Max_computation_time']['value'];
	$produce_all_items = $settings['Produce_all_items']['value'];
	$diapason = $settings['A4_frequency_(diapason)']['value'];
	$C4key = $settings['C4_(middle_C)_key_number']['value'];
	$time_resolution = $settings['Time_resolution']['value'];
	$quantization = $settings['Quantization']['value'];
	$quantize = $settings['Quantize']['value'];
	$nature_of_time_settings = $settings['Striated_time']['value'];
/*	$show_production = get_setting("show_production",$settings_file);
	$trace_production = get_setting("trace_production",$settings_file);
	$note_convention = get_setting("note_convention",$settings_file);
	$non_stop_improvize = get_setting("non_stop_improvize",$settings_file);
	$max_items = get_setting("max_items",$settings_file);
	$p_clock = get_setting("p_clock",$settings_file);
	$q_clock = get_setting("q_clock",$settings_file);
	$max_time_computing = get_setting("max_time_computing",$settings_file);
	$produce_all_items = get_setting("produce_all_items",$settings_file);
	$diapason = get_setting("diapason",$settings_file);
	$C4key = get_setting("C4key",$settings_file);
	$csound_default_orchestra = get_setting("csound_default_orchestra",$settings_file);
	$time_resolution = get_setting("time_resolution",$settings_file);
	$quantization = get_setting("quantization",$settings_file);
	$quantize = get_setting("quantize",$settings_file);
	$nature_of_time_settings = get_setting("nature_of_time",$settings_file); */
	}

if($test) echo "url_this_page = ".$url_this_page."<br />";

$csound_is_responsive = FALSE;
echo "<div style=\"float:right; padding:6px; background-color:transparent;\">";
$csound_is_responsive = check_csound();
link_to_tonality();
echo "</div>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<table cellpadding=\"8px;\" id=\"topmidiports\" class=\"thinborder\"><tr>";
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
echo "<td style=\"vertical-align:middle;\">";
if($file_format == '') {
	$file_format = "rtmidi";
	save_settings2("grammar_file_format",$filename,$file_format);
	}
show_file_format_choice("grammar",$file_format,$url_this_page,$filename);
echo "</td>";
echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
echo "<input type=\"hidden\" name=\"tonality_file\" value=\"".$tonality_file."\">";
$error = FALSE;
if($produce_all_items > 0) $action = "produce-all";
else $action = "produce";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
$error_mssg = '';
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir.$alphabet_file." not found.</span><br />";
		$error = TRUE;
		}
	else $link_produce .= "&alphabet=".urlencode($dir.$alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$url_settings = "settings.php?file=".urlencode($dir_base.$settings_file);
		$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir_base.$settings_file." not found.</span>";
		$error_mssg .= "&nbsp;<input class=\"save\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".nice_url($url_settings)."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"CREATE IT\"><br />";
		$error = TRUE;
		}
	else $link_produce .= "&settings=".urlencode($dir.$settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir.$objects_file." not found.</span><br />";
		$error = TRUE;
		}
	else $link_produce .= "&objects=".urlencode($dir.$objects_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir_csound_resources.$csound_file)) {
		$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir_csound_resources.$csound_file." not found.</span><br />";
		$error = TRUE;
		}
	else $link_produce .= "&csound_file=".urlencode($csound_file);
	}
if($tonality_file <> '') {
	if(!file_exists($dir_tonality_resources.$tonality_file)) {
		$error_mssg .= "<span class=\"red-text\">WARNING: ".$dir_tonality_resources.$tonality_file." not found.</span><br />";
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
/* if($file_format == "rtmidi" AND file_exists($refresh_file)) $refresh_instruction = "document.getElementById('refresh').style.display = 'inline';";
else $refresh_instruction = ''; */
$window_name = window_name($filename);
if($test) echo "output = ".$output."<br />";
if($test) echo "output_file = ".$output_file."<br />";
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
$link_produce .= "&here=".urlencode($here);
if($error) echo $error_mssg;
echo "</tr>";
echo "</table>";
echo "<br />";
if($templates) {
	$action = "templates";
	$link_produce_templates = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
//	$link_produce_templates .= "&trace_production=1";
	$link_produce_templates .= "&here=".urlencode($here);
	$window_name = window_name($filename);
	echo "<input class=\"edit\" onclick=\"if(checksaved()) window.open('".$link_produce_templates."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" value=\"CHECK TEMPLATES\"><br /><br />";
	}
echo "<div style=\"padding:1em; width:690px;\" class=\"thinborder2\">";
if($settings_file <> '' AND file_exists($dir.$settings_file)) echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".nice_url($url_settings)."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$settings_file."’\">";
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
		echo "<p>⏱ Time base: <span class=\"red-text\">".$p_clock."</span> ticks in <span class=\"red-text\">".$q_clock."</span> seconds (metronome = <span class=\"red-text\">".$metronome."</span> beats/mn)<br />";
		if(!is_numeric($nature_of_time)) $nature_of_time = STRIATED;
		}
	else {
		$metronome =  60;
		$p_clock = $q_clock = 1;
		if($time_structure <> '')
			echo "<p>⏱ Metronome (time base) is not properly specified. It will be set to <span class=\"red-text\">60</span> beats per minute. Time structure is <span class=\"red-text\">".$time_structure."</span> as indicated in data.</p>";
		else {
			$nature_of_time = STRIATED;
			echo "<p>⏱ Metronome (time base) and structure of time are neither specified in grammar nor set up by a ‘-se’ file.<br />Therefore metronome will be set to <span class=\"red-text\">60</span> beats per minute and time structure to <span class=\"red-text\">STRIATED</span>.</p>";
			}
		}
	echo "•&nbsp;Time resolution = <span class=\"red-text\">".$time_resolution."</span> milliseconds (by default)<br />";
	echo "•&nbsp;No quantization<br />";
	}
else {
	if($p_clock > 0 AND $q_clock > 0) {
		$metronome_settings = 60 * $q_clock / $p_clock;
		}
	else $metronome_settings = 0;
	if($metronome > 0 AND $metronome <> $metronome_settings) {
		echo "⚠️&nbsp;Conflict: metronome is ".$metronome_settings." beats/mn as per <span class=\"green-text\">‘".$settings_file."’</span><br />&nbsp;&nbsp;and ".$metronome." beats/mn in grammar. We'll use <span class=\"red-text\">".$metronome."</span> beats/mn<br />";
		}
	if(!is_numeric($metronome)) $metronome = $metronome_settings;
	if($metronome <> intval($metronome)) $metronome = sprintf("%.3f",$metronome);
	if(($nature_of_time_settings <> $nature_of_time) AND (is_numeric($nature_of_time))) {
		echo "⚠️&nbsp;Conflict: time structure is ".nature_of_time($nature_of_time_settings)." as per <span class=\"green-text\">‘".$settings_file."’</span><br />&nbsp;&nbsp;and ".nature_of_time($nature_of_time)." in grammar. We'll use ".nature_of_time($nature_of_time)."<br />";
		}
	if(!is_numeric($nature_of_time)) $nature_of_time = $nature_of_time_settings;
	if($metronome > 0. AND $nature_of_time == STRIATED) {
		echo "⏱ Metronome = <span class=\"red-text\">".$metronome."</span> beats/mn<br />";
		}
	echo "•&nbsp;Time resolution = <span class=\"red-text\">".$time_resolution."</span> milliseconds as per <span class=\"green-text\">‘".$settings_file."’</span><br />";
	if($quantize) {
		echo "•&nbsp;Quantization = <span class=\"red-text\">".$quantization."</span> milliseconds as per <span class=\"green-text\">‘".$settings_file."’</span>";
		if($time_resolution > $quantization) echo "&nbsp;<span class=\"red-text\">➡</span>&nbsp;may be raised to <span class=\"red-text\">".$time_resolution."</span>&nbsp;ms…";
		echo "<br />";
		}
	else echo "•&nbsp;No quantization<br />";
	}
if($nature_of_time == STRIATED) echo "•&nbsp;Time is <span class=\"red-text\">".nature_of_time($nature_of_time)."</span><br />";
else echo "•&nbsp;Time is <span class=\"red-text\">".nature_of_time($nature_of_time)."</span> (no fixed tempo)<br />";
if($non_stop_improvize > 0) {
	if($max_items == 0) $max_items = 20;
	echo "• <span class=\"red-text\">Non-stop improvize</span> as set by <span class=\"green-text\">‘".$settings_file."’</span>";
	if($file_format <> "rtmidi") echo ": <i>only ".$max_items." variations will be produced</i>";
	echo "<br />";
	}
if($diapason <> 440) echo "• <span class=\"red-text\">Diapason</span> (A4 frequency) = <span class=\"red-text\">".$diapason."</span> Hz as set by <span class=\"green-text\">‘".$settings_file."’</span><br />";
if($C4key <> 60) {
	echo "• <span class=\"red-text\">C4 key number</span> = <span class=\"red-text\">".$C4key."</span> as set by <span class=\"green-text\">‘".$settings_file."’</span>";
	if($file_format == "csound") echo " ➡ this has no incidence on Csound scores";
	echo "<br />";
	}
if($found_elsewhere AND $objects_file <> '') echo "• Sound-object prototype file = <span class=\"green-text\">‘".$objects_file."’</span> found in <span class=\"green-text\">‘".$alphabet_file."’</span><br />";
if($note_convention <> '') echo "• Note convention is <span class=\"red-text\">‘".ucfirst(note_convention(intval($note_convention)))."’</span> as per <span class=\"green-text\">‘".$settings_file."’</span><br />";
else echo "• Note convention is <span class=\"red-text\">‘English’</span> by default<br />";
if($produce_all_items == 1) {
	echo "• Produce all items has been set ON by <span class=\"green-text\">‘".$settings_file."’</span>";
	if($file_format <> "rtmidi") echo ": <i>only ".$max_items." variations will be produced</i>";
	echo "<br />";
	}
else if($show_production == 1) echo "• Show production has been set ON by <span class=\"green-text\">‘".$settings_file."’</span><br />";
if($trace_production == 1) echo "• Trace production has been set ON by <span class=\"green-text\">‘".$settings_file."’</span><br />";
/* if($settings_file <> '' AND file_exists($dir.$settings_file) AND isset($random_seed) AND $non_stop_improvize > 0) {
	if($random_seed > 0)
		echo "• Random seed has been set to <span class=\"red-text\">".$random_seed."</span> by <span class=\"green-text\">‘".$settings_file."’</span> ➡ Series will be repeated.<br />";
	else
		echo "• Random seed is ‘no seed’ as per <span class=\"green-text\">‘".$settings_file."’</span> ➡ Series will vary.<br />";
	} */
if($max_time_computing > 0) {
	echo "• Max console computation time has been set to <span class=\"red-text\">".$max_time_computing."</span> seconds by <span class=\"green-text\">‘".$settings_file."’</span>";
	if($max_time_computing < 10) echo "&nbsp;<span class=\"red-text\">➡</span>&nbsp;probably too small!";
	if($max_time_computing > 3600) {
		echo "<br /><span class=\"red-text\">➡</span>&nbsp;reduced to <span class=\"red-text\">3600</span> seconds";
		$max_time_computing = 3600;
		}
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
			echo "• <span class=\"red-text\">Csound scores</span> will be produced and converted to sound files (including scales) using orchestra ‘<span class=\"green-text\">".$csound_orchestra."</span>’ as specified in <span class=\"green-text\">‘".$csound_file."’</span>";
			if($found_orchestra_in_settings AND file_exists($dir_csound_resources.$csound_default_orchestra) AND $csound_orchestra <> $csound_default_orchestra) echo "<br />&nbsp;&nbsp;<span class=\"red-text\">➡</span> Orchestra ‘<span class=\"green-text\">".$csound_default_orchestra."</span>’ specified in <span class=\"green-text\">‘".$settings_file."’</span> will be ignored</font>";
			}
		else if($found_orchestra_in_instruments AND !$found_orchestra_in_settings AND $csound_orchestra <> '') {
			echo "<span class=\"red-text\">➡</span> Csound scores will be produced, yet conversion to sound files will not be possible because orchestra ‘<span class=\"green-text\">".$csound_orchestra."</span>’ specified in ‘<span class=\"green-text\">".$csound_file."</span>’ was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		else if($csound_default_orchestra <> '' AND file_exists($dir_csound_resources.$csound_default_orchestra)) {
			$csound_orchestra = $csound_default_orchestra;
			echo "• <span class=\"red-text\">Csound scores</span> will be produced and converted to sound files (including scales) using orchestra ‘<span class=\"green-text\">".$csound_default_orchestra."</span>’</font>";
			}
		else if($csound_default_orchestra <> '') {
			echo "<span class=\"red-text\">➡</span> Csound scores will be produced yet not converted to sound files by orchestra ‘<span class=\"green-text\">".$csound_default_orchestra."</span>’ as specified in ‘<span class=\"green-text\">".$settings_file."</span>’ because this file was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		else if(file_exists($dir_csound_resources."0-default.orc")) {
			$csound_orchestra = "0-default.orc";
			echo "• <span class=\"red-text\">Csound scores</span> will be produced and converted to sound files using default orchestra file ‘<span class=\"green-text\">".$csound_orchestra."</span>’";
			}
		else {
			echo "<span class=\"red-text\">➡</span> Csound scores will be produced yet not converted to sound files</font> because default orchestra file ‘<span class=\"green-text\">0-default.orc</span>’ was not found in the Csound resources folder";
			$csound_orchestra = '';
			}
		if(file_exists($dir_csound_resources.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
		}
	else echo "<span class=\"red-text\">➡</span> Csound scores will be produced yet not converted to sound files because Csound is not installed or not responding";
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
echo "<br /><button class=\"edit big\" onclick=\"togglesearch(); return false;\">SEARCH & REPLACE</button><p></p>";

find_replace_form();
echo "<p><input class=\"save big\" type=\"submit\" id=\"saveButton\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".begin_with(20,$filename)."’\">";
if((file_exists($output.SLASH.$default_output_name.".wav") OR file_exists($output.SLASH.$default_output_name.".mid") OR file_exists($output.SLASH.$default_output_name.".html") OR file_exists($output.SLASH.$default_output_name.".sco")) AND file_exists($result_file)) {
	echo "&nbsp;&nbsp;&nbsp;<input class=\"edit\" style=\"font-size:large;\" onclick=\"window.open('".nice_url($result_file)."','result','width=800,height=600,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"Show latests results\">";
	}
echo "&nbsp;<input class=\"edit big\" type=\"submit\" onclick=\"clearsave();\" name=\"compilegrammar\" value=\"COMPILE GRAMMAR\">";
echo "&nbsp;<input onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)";
if($error) {
	echo " - disabled because of missing files\"";
	echo " class=\"edit big disabled\" style=\"box-shadow: none;\" disabled ";
	}
else echo "\" class=\"produce big\"";
echo ">";
echo "</p>";

$content = do_replace($content);

if($error) echo "<p>".$error_mssg."</p>";
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax + 5;
echo "<textarea name=\"thistext\" onchange=\"tellsave()\" rows=\"".$textarea_rows."\" style=\"width:90%;\">".$content."</textarea>";

echo "<p style=\"float:right; margin-right:100px;\"><input class=\"save big\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".begin_with(20,$filename)."’\"></p>";
echo "<div style=\"background-color:transparent;\">";
echo "<input onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$link_produce."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" value=\"PRODUCE ITEM(s)";
if($error) {
	echo " - disabled because of missing files\"";
	echo " class=\"edit big disabled\" style=\"box-shadow: none;\" disabled ";
	}
else echo "\" class=\"produce big\"";
echo ">";
$link_test = $link_produce."&test";
$display_command_title = "DisplayCommand".$filename;
echo "&nbsp;<input class=\"edit\" onclick=\"window.open('".nice_url($link_test)."','".$display_command_title."','width=1000,height=200,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"Display command line\">";
echo "</div>";
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
// echo "<form method=\"post\" action=\"".$url_this_page."#expression\" enctype=\"multipart/form-data\">";
$action = "play";
$link_produce = "produce.php?instruction=".$action."&grammar=".urlencode($this_file);
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
if($alphabet_file <> '') $link_produce .= "&alphabet=".urlencode($dir.$alphabet_file);
if($settings_file <> '' AND file_exists($dir.$settings_file)) $link_produce .= "&settings=".urlencode($dir.$settings_file);
if($objects_file <> '') $link_produce .= "&objects=".urlencode($dir.$objects_file);
if($csound_file <> '') $link_produce .= "&csound_file=".urlencode($csound_file);
if($tonality_file <> '') $link_produce .= "&tonality_file=".urlencode($tonality_file);
if(file_exists($dir_csound_resources.$csound_orchestra)) $link_produce .= "&csound_orchestra=".urlencode($csound_orchestra);
$link_produce .= "&output=".urlencode($output.SLASH.$output_file)."&format=".$file_format;
if($show_production > 0)
	$link_produce .= "&show_production=1";
if($trace_production > 0)
	$link_produce .= "&trace_production=1";
$link_produce .= "&here=".urlencode($here);
$window_name = window_name($filename);
if(count($variable) > 0) {
	echo "<p>Variables (click to use as startup string):</p>";
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
		echo "<input class=\"produce\"  onclick=\"if(checksaved()) window.open('".$link_play_variable."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" value=\"".$var."\"> ";
		}
	}

$data_expression = $temp_dir.$temp_folder.SLASH."startup.bpda";
$recoded_expression = '';
if(isset($_POST['saveexpression'])) {
	$expression = recode_entities($expression);
	$recoded_expression = recode_tags($expression);
	if($expression <> '') echo "<p id=\"timespan\"><span class=\"red-text\">➡ Saving:</span> <span class=\"green-text\"><big>".$recoded_expression."</big></span></p>";
	$handle = fopen($data_expression,"w");
	fwrite($handle,$recoded_expression."\n");
	fclose($handle);
	}
if($expression == '') {
	$expression = @file_get_contents($data_expression,TRUE);
	}
$recoded_expression = recode_tags($expression);
$link_play_expression = $link_produce;
$link_play_expression .= "&data=".urlencode($data_expression);
$window_name .= "_startup";
echo "<p id=\"expression\">Use the following (polymetric) expression as startup:</p>";
echo "<textarea name=\"expression\" rows=\"5\" style=\"width:700px;\">".$recoded_expression."</textarea>";
echo "<br />";
$error_mssg = '';
$n1 = substr_count($expression,'{');
$n2 = substr_count($expression,'}');
if($n1 > $n2) $error_mssg .= "<span class=\"red-text\">This expression contains ".($n1-$n2)." extra ‘{'</span>";
if($n2 > $n1) $error_mssg .= "<span class=\"red-text\">This expression contains ".($n2-$n1)." extra ‘}'</span>";
if($error_mssg <> '') echo "<p>".$error_mssg."</p>";
echo "<input  type=\"submit\" onclick=\"clearsave();\" name=\"saveexpression\" formaction=\"".$url_this_page."#expression\" class=\"save\" value=\"SAVE EXPRESSION\">&nbsp;then&nbsp;<input onclick=\"window.open('".nice_url($link_play_expression)."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" value=\"PRODUCE ITEM\"";
if(!file_exists($data_expression) OR trim($recoded_expression) == '') echo " disabled class=\"edit disabled\" style=\"box-shadow: none;\"";
else echo " class=\"produce\"";
echo "><br /><br />";
echo "<span id=\"topchanges\"></span>";

$hide = display_note_conventions($note_convention);
	
if(isset($_POST['manage_instructions'])) {
	show_changes_instructions($content);
	$hide = TRUE;
	}
if(!$hide) {
	echo "<table class=\"thinborder\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input class=\"edit\" type=\"submit\" onclick=\"if(checksaved()) {this.form.target='_self';return true;} else return false;\" name=\"change_convention\" formaction=\"".$url_this_page."#topchanges\" value=\"APPLY NOTE CONVENTION to this data\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "</td>";
	echo "</tr><tr><td colspan=2>";
	show_note_convention_form("grammar",$note_convention,$settings_file);
	echo "</td></tr></table>";
	$found_chan = substr_count($content,"_chan(");
	$found_ins = substr_count($content,"_ins(");
	$found_part = substr_count($content,"_part(");
	$found_tempo = substr_count($content,"_tempo(");
	$found_volume = substr_count($content,"_volume(");
	echo "<br />";
	if($found_chan > 0 OR $found_ins > 0 OR $found_part > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"manage_instructions\" formaction=\"".$url_this_page."#topchanges\" value=\"MANAGE _chan(), _ins(), _part()\">&nbsp;";
	if($found_chan > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_chan\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _chan()\">&nbsp;";
	if($found_ins > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_ins\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _ins()\">&nbsp;";
	if($found_part > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_part\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _part()\">&nbsp;";
	if($found_tempo > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_tempo\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _tempo()\">&nbsp;";
	if($found_volume > 0) echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_volume\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _volume()\">&nbsp;";
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
            echo "<p>👉 <span class=\"red-text\">Failed to create backup of the file.</span></p>";
		}
	$handle = @fopen($this_file, "w");
	if($handle) {
		$file_header = $top_header . "\n// Grammar saved as \"" . $filename . "\". Date: " . gmdate('Y-m-d H:i:s');
		fwrite($handle, $file_header . "\n");
		fwrite($handle, $save_content);
		fclose($handle);
		}
	else echo "<div style=\"padding: 1em; border-radius: 6px;\"><p>👉 <span class=\"red-text\"><b>WARNING</b>: Some files have been imported and cannot be modified.</span></p><p><b>Linux user?</b> Open your terminal and type: <span class=\"green-text\">sudo /opt/lampp/htdocs/bolprocessor/change_permissions.sh</span><br />(Your password will be required...)</p></div>";
	return;
	}
?>
