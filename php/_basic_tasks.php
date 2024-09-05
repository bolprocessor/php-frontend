<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', 0);
require('midi.class.php');
// Source: https://github.com/robbie-cao/midi-class-php

define('MAXFILESIZE',50000000);
define('SLASH',DIRECTORY_SEPARATOR);
define('STRIATED',1);
define('SMOOTH',0);
// ini_set("auto_detect_line_endings",true);

$test = FALSE;
// $test = TRUE;

$permissions = 0777;

$file_path = '_settings.php';
if (!file_exists($file_path)) {
    // Create the file and write the PHP tags
    $file_content = "<?php\n\n?>";
    // Write the content to the file
    if (file_put_contents($file_path, $file_content) == false) {
         echo "Failed to create the file '_settings.php'."; die();
        }
    else chmod($file_path,$permissions);
    }

require_once("_settings.php");
$bp_application_path = "..".SLASH;
$url_this_page = "_basic_tasks.php";
$absolute_application_path = str_replace("php".SLASH.$url_this_page,'',realpath(__FILE__));

if(isset($_POST['csound_path_change'])) {
	$csound_path = trim($_POST['csound_path']);
	save_settings("csound_path",$csound_path);
	$csound_name = trim($_POST['csound_name']);
	if($csound_name <> '') {
		save_settings("csound_name",$csound_name);
		}
	}

if(windows_system()) {
    $console = "bp.exe";
    if (!isset($csound_name) || $csound_name == '') $csound_name = "csound.exe";
  //  $programFiles = getenv("ProgramFiles"); This is only valid in English!
    $programFiles = findCsoundPath($csound_name);
    $programFiles = str_replace("\Csound6_x64\bin",'',$programFiles);
 //   echo "programFiles = ".$programFiles."<br />";
    if (!isset($csound_path) || $csound_path == '') {
        if(file_exists($programFiles."\\Csound6_x64\\bin\\csound.exe")) {
            $csound_path = "\\Csound6_x64\\bin";
            $csound_name = "csound.exe";
            }
        else $csound_path = "";
        }
    }
else {
    $programFiles = '';
	if(linux_system()) {
		$console = "bp3";
		if(!isset($csound_name) OR $csound_name == '') $csound_name = "csound";
		if(!isset($csound_path) OR $csound_path == '') {
			if(file_exists("/usr/bin/csound")) {
				$csound_path = SLASH."usr".SLASH."bin";
				}
			else $csound_path = "";
			}
		}
	else { // MacOS
		$console = "bp";
		if(!isset($csound_name) OR $csound_name == '') $csound_name = "csound";
		if(!isset($csound_path)) $csound_path = SLASH."usr".SLASH."local".SLASH."bin";
		}
    }
if($csound_name <> '') save_settings("csound_name",$csound_name);
save_settings("csound_path",$csound_path);

if(!isset($csound_resources) OR $csound_resources == '') $csound_resources = "csound_resources";
save_settings("csound_resources",$csound_resources);
if(!isset($tonality_resources) OR $tonality_resources == '') $tonality_resources = "tonality_resources";
save_settings("tonality_resources",$tonality_resources);
if(!isset($midi_resources) OR $midi_resources == '') $midi_resources = "midi_resources";
save_settings("midi_resources",$midi_resources);
if(!isset($trash_folder) OR $trash_folder == '') $trash_folder = "trash_bolprocessor";
save_settings("trash_folder",$trash_folder);
$max_sleep_time_after_bp_command = 240; // seconds. Maximum time waiting for the 'done.txt' file
$default_output_format = "midi";

if(!isset($output_folder) OR $output_folder == '') $output_folder = "my_output";

$maxchunk_size = 400; // Max number of measures contained in a chunk
$minchunk_size = 10; // Min number of measures contained in a chunk
// $minchunk_size = 1; // Use this value to check the chunking to single measures

$max_term_in_fraction = 32768; // Used to simplify fractions when importing MusicXML scores

$number_fields_csound_instrument = 67; // Never change this!
$number_midi_parameters_csound_instrument = 6; // Never change this!

$oldmask = umask(0);
$temp_dir = $bp_application_path."temp_bolprocessor";
if(!file_exists($temp_dir)) {
	if (!mkdir($temp_dir, $permissions, true))
        error_log("Failed to create directory '{$temp_dir}' with error: " . error_get_last()['message']);
	else
        chmod($temp_dir, $permissions); // Double-check permissions
	}
$temp_dir .= SLASH;
if(!file_exists($temp_dir."messages")) mkdir($temp_dir."messages",$permissions,true);
umask($oldmask);
$panicfile = $temp_dir."messages".SLASH."_panic";

$MIDIinput = $MIDIoutput = array();
$MIDIinput[0] = -1;
$MIDIoutput[0] = 0;
$MIDIinputname = $MIDIoutputname = $MIDIoutputcomment = $MIDIinputcomment = array();
$MIDIinputname[0] = $MIDIoutputname[0] = $MIDIoutputcomment[0] = $MIDIinputcomment[0] = '';
$NumberMIDIinputs = $NumberMIDIoutputs = 1;
if(isset($_POST['NumberMIDIinputs'])) $NumberMIDIinputs = $_POST['NumberMIDIinputs'];
if(isset($_POST['NumberMIDIoutputs'])) $NumberMIDIoutputs = $_POST['NumberMIDIoutputs'];
$NoteOffFilter = $NoteOnFilter = $KeyPressureFilter = $ControlTypeFilter = $ProgramChangeFilter = $ChannelPressureFilter = $PitchBendFilter = $SystemExclusiveFilter = $TimeCodeFilter = $SongPositionFilter = $SongSelectFilter = $TuneRequestFilter = $EndSysExFilter = $TimingClockFilter = $StartFilter = $ContinueFilter = $ActiveSensingFilter = $SystemResetFilter = array();

if(!file_exists($bp_application_path.$csound_resources)) {
	mkdir($bp_application_path.$csound_resources);
	chmod($bp_application_path.$csound_resources,$permissions);
	}
$dir_csound_resources = $bp_application_path.$csound_resources.SLASH;

if(!file_exists($bp_application_path.$tonality_resources)) {
	mkdir($bp_application_path.$tonality_resources);
	chmod($bp_application_path.$tonality_resources,$permissions);
	}
$dir_tonality_resources = $bp_application_path.$tonality_resources.SLASH;

if(!file_exists($bp_application_path.$midi_resources)) {
	mkdir($bp_application_path.$midi_resources);
	chmod($bp_application_path.$midi_resources,$permissions);
	}
$dir_midi_resources = $bp_application_path.$midi_resources.SLASH;

if(!file_exists($bp_application_path.$tonality_resources.SLASH."scale_images")) {
	mkdir($bp_application_path.$tonality_resources.SLASH."scale_images");
    chmod($bp_application_path.$tonality_resources.SLASH."scale_images",$permissions);
	}
$dir_scale_images = $bp_application_path.$tonality_resources.SLASH."scale_images".SLASH;

if(!file_exists($bp_application_path.$trash_folder)) {
	mkdir($bp_application_path.$trash_folder);
    chmod($bp_application_path.$trash_folder,$permissions);
	}
$dir_trash_folder = $bp_application_path.$trash_folder.SLASH;

// Delete old temp directories and trace files
$dircontent = scandir($temp_dir);
$now = time();
$yesterday = $now - (24 * 3600);
foreach($dircontent as $thisfile) {
	if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
	$time_saved = filemtime($temp_dir.$thisfile);
	if($time_saved < $yesterday) $old = TRUE;
	else $old = FALSE;
	if(is_dir($temp_dir.$thisfile)) {
		$table = explode('_',$thisfile);
		$extension = end($table);
		if($extension == "temp" AND count($table) > 2) {
			$id = $table[count($table) - 2];
			if($old) {
				if($id <> my_session_id()) {
					my_rmdir($temp_dir.$thisfile);
					continue;
					}
				}
			}
		}
	$table = explode(".",$thisfile);
	$extension = end($table);
	if($old) {
		$table = explode('_',$thisfile);
		$prefix = $table[0];
		if($prefix == "trace" OR $prefix == "temp") {
			$id = $table[1];
	//		echo $thisfile." id = ".$id." session = ".my_session_id()."<br />";
			if($id <> my_session_id()) {
	//		if(($extension == "txt" OR $extension == "html" OR $extension == "bpda") AND $id <> my_session_id()) {
				unlink($temp_dir.$thisfile);
				continue;
				}
			}
		}
	}

if(isset($_GET['path'])) $path = urldecode($_GET['path']);
else $path = '';

$text_help_file = $bp_application_path."BP2_help.txt"; // Name should be updated to BP3

if($test) {
	echo "<small>";
	echo "operating system = ".getOS()."<br />";
	echo "path = ".$path."<br />";
	echo "bp_application_path = ".$bp_application_path."<br />";
	echo "temp_dir = ".$temp_dir."<br />";
	echo "text_help_file = ".$text_help_file."<br />";
	echo "</small><hr>";
	}

$html_help_file = "BP2_help.html";
$help = compile_help($text_help_file,$html_help_file);
$top_header = "// Bol Processor BP3";

$KeyString = "key#";
$Englishnote = array("C","C#","D","D#","E","F","F#","G","G#","A","A#","B","C");
$Frenchnote = array("do","do#","re","re#","mi","fa","fa#","sol","sol#","la","la#","si","do");
$Indiannote = array("sa","rek","re","gak","ga","ma","ma#","pa","dhak","dha","nik","ni","sa");
$AltEnglishnote = array("B#","Db","D","Eb","Fb","E#","Gb","G","Ab","A","Bb","Cb","B#");
$AltFrenchnote = array("si#","reb","re","mib","fab","mi#","solb","sol","lab","la","sib","dob","si#");
$AltIndiannote = array("ni#","sa#","re","re#","mak","ga#","pak","pa","pa#","dha","dha#","sak","ni#");

// Create a list of fractions eligible for frequency ratios in just intonation
// Numerator or denominator may be a power of 3 multiplied by 5 multiplied by a power of 2
$i_ratio = 0;
$x_three = 1;
for($i = 0; $i < 7; $i++) {
	$x_five = 1;
	for($j = 0;  $j < 2; $j++) {
		$num = $x_three * $x_five;
		if($j == 0) $serie = "p"; // Pythagorean
		else $serie = "h"; // Harmonic
		$den = 1;
		$the_ratio = $num / $den;
		if($num == 1 AND $den == 1) continue;
		while($the_ratio < 0.5) {
			$num = 2 * $num;
			$the_ratio = $num / $den;
			}
		while($the_ratio > 2) {
			$den = 2 * $den;
			$the_ratio = $num / $den;
			}
		$p_fract[$i_ratio] = $num;
		$q_fract[$i_ratio] = $den;
		$serie_fract[$i_ratio] = $serie;
		$ratio_fract[$i_ratio++] = $the_ratio;
		//echo $num."/".$den." = ".$the_ratio." (1)<br />";
		if($the_ratio < 1) $num = 2 * $num;
		if($the_ratio > 1) $den = 2 * $den;
		$the_ratio = $num / $den;
		$p_fract[$i_ratio] = $num;
		$q_fract[$i_ratio] = $den;
		$serie_fract[$i_ratio] = $serie;
		$ratio_fract[$i_ratio++] = $the_ratio;
		//echo $num."/".$den." = ".$the_ratio." (2)<br />";
		$num = 1;
		$den = $x_three * $x_five;
		$the_ratio = $num / $den;
		while($the_ratio < 0.5) {
			$num = 2 * $num;
			$the_ratio = $num / $den;
			}
		while($the_ratio > 2) {
			$den = 2 * $den;
			$the_ratio = $num / $den;
			}
		$p_fract[$i_ratio] = $num;
		$q_fract[$i_ratio] = $den;
		$serie_fract[$i_ratio] = $serie;
		$ratio_fract[$i_ratio++] = $the_ratio;
		//echo $num."/".$den." = ".$the_ratio." (3)<br />";
		if($the_ratio < 1) $num = 2 * $num;
		if($the_ratio > 1) $den = 2 * $den;
		$the_ratio = $num / $den;
		$p_fract[$i_ratio] = $num;
		$q_fract[$i_ratio] = $den;
		$serie_fract[$i_ratio] = $serie;
		$ratio_fract[$i_ratio++] = $the_ratio;
		//echo $num."/".$den." = ".$the_ratio." (4)<br />";
		$x_five = $x_five * 5;
		}
	$x_three = $x_three * 3;
	}

// --------- FUNCTIONS ------------

/* function findExecutable($exeName) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($paths as $path) {
        $fullPath = $path.DIRECTORY_SEPARATOR;
        if (file_exists($fullPath.$exeName) && is_executable($fullPath.$exeName)) {
            return rtrim($fullPath, '/\\');
			}
		}
	return false;
	} */

function findCsoundPath($exeName) {
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    foreach ($paths as $path) {
        $fullPath = $path.DIRECTORY_SEPARATOR;
        if (file_exists($fullPath.$exeName) && is_executable($fullPath.$exeName)) {
            return rtrim($path, '/\\');
			}
		}
	return false;
    }
	

function extract_data($compact,$content) {
	$said = FALSE;
	$content = trim($content);
	$content = str_replace(chr(13).chr(10),chr(10),$content);
	$content = str_replace(chr(13),chr(10),$content);
	$content = str_replace(chr(9),' ',$content); // Remove tabulations
	$content = clean_up_encoding(TRUE,TRUE,$content);
	$content = decode_entities($content);
	if($compact) {
		do $content = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$content,$count);
		while($count > 0);
		}
	$table = explode(chr(10),$content);
	$table_out = $extract_data = array();
	$start = $header = TRUE;
	$extract_data['grammar'] = $extract_data['metronome'] = $extract_data['time_structure'] = $extract_data['headers'] = $extract_data['alphabet'] = $extract_data['objects'] = $extract_data['csound'] = $extract_data['tonality'] = $extract_data['settings'] = $extract_data['data'] = $extract_data['orchestra'] = $extract_data['timebase'] = $extract_data['interaction'] = $extract_data['midisetup'] = $extract_data['timebase'] = $extract_data['keyboard'] = $extract_data['glossary'] = $extract_data['cstables'] = '';
	$extract_data['templates'] = FALSE;
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		$line = preg_replace("/\s/u",' ',$line);
		if($i == 0)
			$line = preg_replace("/.*(\/\/.*)/u","$1",$line); // Cleaning up old versions
		if($header AND is_integer($pos=strpos($line,"//")) AND $pos == 0) {
	//		echo $i." “".$table[$i]."”<br />";
			if($i > 1) $table_out[] = $line;
			else {
				if($extract_data['headers'] <> '') $extract_data['headers'] .= "<br />";
				$extract_data['headers'] .= $line;
				}
			if($i > 1 OR $line == '') $header = FALSE;
			continue;
			}
		if(($new_name = new_name($line)) <> $line) {
			$line = $new_name; // Convert old prefixes/prefixes to new ones
	/*		if(!$said) {
				echo "line = ".$line."<br />";
				$said = TRUE;
				echo "<script type='text/javascript'>";
				echo "tellsave();";
				echo "</script>";
				} */
			}
		$table_out[] = $line;
		if(is_integer($pos=strpos($line,"TEMPLATES:")) AND $pos == 0) {
			$extract_data['templates'] = TRUE;
			}
		if(is_integer($pos=strpos($line,"_mm")) AND $pos == 0) {
			$metronome = preg_replace("/.*_mm\(([^\)]+)\).*/u","$1",$line);
			$extract_data['metronome'] = $metronome;
			$time_structure = preg_replace("/.+\)\s+_(.+)$/u","$1",$line);
			if($time_structure == "striated" OR $time_structure == "smooth")
				$extract_data['time_structure'] = $time_structure;
			}
		if(is_integer($pos=strpos($line,"-gr")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['grammar'] = fix_file_name($line,"ho");
		else if(is_integer($pos=strpos($line,"-ho")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['alphabet'] = fix_file_name($line,"ho");
		else if(is_integer($pos=strpos($line,"-al")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['alphabet'] = fix_file_name($line,"al");
		else if(is_integer($pos=strpos($line,"-so")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['objects'] = fix_file_name($line,"so");
		else if(is_integer($pos=strpos($line,"-mi")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['objects'] = fix_file_name($line,"mi");
		else if(is_integer($pos=strpos($line,"-cs")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['csound'] = fix_file_name($line,"cs");
		else if(is_integer($pos=strpos($line,"-to")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['tonality'] = fix_file_name($line,"to");
		else if(is_integer($pos=strpos($line,"-se")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['settings'] = fix_file_name($line,"se");
		else if(is_integer($pos=strpos($line,"-da")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['data'] = fix_file_name($line,"da");
		else if(is_integer($pos=strpos($line,"-or")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['orchestra'] = fix_file_name($line,"or");
		else if(is_integer($pos=strpos($line,"-tb")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['timebase'] = fix_file_name($line,"tb");
		else if(is_integer($pos=strpos($line,"-in")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['interaction'] = fix_file_name($line,"in");
		else if(is_integer($pos=strpos($line,"-md")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['midisetup'] = fix_file_name($line,"md");
		else if(is_integer($pos=strpos($line,"-tb")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['timebase'] = fix_file_name($line,"tb");
		else if(is_integer($pos=strpos($line,"-kb")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['keyboard'] = fix_file_name($line,"kb");
		else if(is_integer($pos=strpos($line,"-gl")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['glossary'] = fix_file_name($line,"gl");
		else if($line <> '') $start = FALSE;
		}
	$extract_data['content'] = implode(chr(10),$table_out);
	return $extract_data;
	}

function fix_file_name($line,$type) {
	// Detect for instance: "-se.:somefile.bpse"
	$goodline = $line;
	if(is_integer($pos=strpos($line,"<"))) return '';
	if(is_integer($pos=strpos($line,":")) AND $pos == 4) {
		$line = substr($line,5,strlen($line) - 5);
		$extension = "bp".$type;
		$goodline = str_replace(".".$extension,'',$line);
		$goodline = str_replace(".","_",$goodline);
		$goodline .= ".".$extension;
		if($goodline <> $line)
			echo "<p style=\"color:red;\">ERROR: incorrect file name ‘-".$type.".:".$line."’, it should be ‘-".$type.".:".$goodline."’</p>";
		}
	return $goodline;
	}

function window_name($text) {
	$text = str_replace('-','_',$text);
	$text = str_replace(' ','_',$text);
	$text = str_replace('"','_',$text);
	$text = str_replace("'",'_',$text);
	return $text;
	}
	
function display_more_buttons($error,$content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$tonality_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file) {
	global $bp_application_path, $csound_resources, $tonality_resources, $output_file, $file_format, $test;
	$page_type = str_replace(".php",'',$url_this_page);
	$page_type = preg_replace("/\.php.*/u",'',$url_this_page);
	
	$dir = str_replace($bp_application_path,'',$dir);
	if($test) echo "dir = ".$dir."<br />";
	if($content <> '') {
		if($page_type == "grammar" OR $page_type == "alphabet" OR $page_type == "glossary" OR $page_type == "interaction") {
			if(isset($_POST['show_help_entries'])) {
				$entries = display_help_entries($content);
				echo $entries."<br />";
				}
			else {
				echo "<div style=\"float:right; margin-top:36px;\">";
				echo "<form method=\"post\" id=\"thisone\" action=\"".$url_this_page."#help_entries\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
				echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
				echo "<button style=\"background-color:azure;\" type=\"submit\" onmouseover=\"checksaved();\" name=\"show_help_entries\">SHOW HELP ENTRIES</button>";
				echo "</form></div>";
				}
			}
		}
	echo "<table style=\"padding:0px; background-color:white; border-spacing: 2px;\" cellpadding=\"0px;\"><tr>";
	if($error) {
		echo "<td style=\"vertical-align:middle;\"><big><font color=\"red\" class=\"blinking\">➡</font></big></td>";
		}
	if($alphabet_file <> '') {
		$url_this_page = "alphabet.php?file=".urlencode($dir.$alphabet_file);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$alphabet_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$alphabet_file."’\">";
		echo "</td>";
		}
	if($grammar_file <> '') {
		$url_this_page = "grammar.php?file=".urlencode($dir.$grammar_file);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"openobjects\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$grammar_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($objects_file <> '') {
		$url_this_page = "objects.php?file=".urlencode($dir.$objects_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$objects_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($csound_file <> '') {
		$url_this_page = "csound.php?file=".urlencode($csound_resources.SLASH.$csound_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$csound_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($tonality_file <> '') {
		$url_this_page = "tonality.php?file=".urlencode($tonality_resources.SLASH.$tonality_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$tonality_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($settings_file <> '') {
		$url_this_page = "settings.php?file=".urlencode($dir.$settings_file);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$settings_file."','width=1000,height=800,left=100'); return false;\" value=\"EDIT ‘".$settings_file."’\">";
		echo "</td>";
		}
	if($orchestra_file <> '') {
		$url_this_page = "orchestra.php?file=".urlencode($dir.$orchestra_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$orchestra_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$orchestra_file."’\">";
		echo "</td>";
		}
	if($interaction_file <> '') {
		$url_this_page = "interaction.php?file=".urlencode($dir.$interaction_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$interaction_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$interaction_file."’\">";
		echo "</td>";
		}
	if($midisetup_file <> '') {
		$url_this_page = "midisetup.php?file=".urlencode($dir.$midisetup_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$midisetup_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$midisetup_file."’\">";
		echo "</td>";
		}
	if($timebase_file <> '') {
		$url_this_page = "timebase.php?file=".urlencode($dir.$timebase_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$timebase_file."','width=1000,height=900,left=50'); return false;\" value=\"EDIT ‘".$timebase_file."’\">";
		echo "</td>";
		}
	if($keyboard_file <> '') {
		$url_this_page = "keyboard.php?file=".urlencode($dir.$keyboard_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$keyboard_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$keyboard_file."’\">";
		echo "</td>";
		}
	if($glossary_file <> '') {
		$url_this_page = "glossary.php?file=".urlencode($dir.$glossary_file);
		echo "<td>";
		echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$glossary_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$glossary_file."’\">";
		echo "</td>";
		}
	echo "</tr></table>";
	return;
	}

function ask_create_new_file($url_this_page,$filename) {
	echo "File ‘".$filename."’ not found. Do you wish to create a new one under that name?";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"createfile\" value=\"YES\">";
	echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"dontcreate\" value=\"NO\"></p>";
	echo "</form>";
	die();
	}

function try_create_new_file($file,$filename) {
	if(isset($_POST['dontcreate'])) {
		echo "<p style=\"color:red;\" class=\"blinking\">No file created. You can close this tab…</p>";
		die();
		}
	if(isset($_POST['createfile'])) {
		echo "<p style=\"color:red;\" class=\"blinking\">Creating ‘".$filename."’. Don’t forget to SAVE it!</p>";
		$handle = fopen($file,"w");
		fclose($handle);
		}
	}

function compile_help($text_help_file,$html_help_file) {
	global $filename;
//	echo "text_help_file = ".$text_help_file."<br />";
//	echo "html_help_file = ".$html_help_file."<br />";
	$help = array();
	$help[0] = '';
	$no_entry = array("ON","OFF","vel");
    if(isset( $filename) AND $filename == "Compilation") return '';
	if(!file_exists($text_help_file)) {
		echo "<p style=\"color:MediumTurquoise;\">Warning: “BP2_help.html” has not yet been reconstructed.</p>";
		return '';
		}
	$content = @file_get_contents($text_help_file,TRUE);
	if($content) {
		$file_header = "<!DOCTYPE HTML>\n";
		$file_header .= "<html lang=\"en\">";
		$file_header .= "<head>";
		$file_header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
		$file_header .= "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
		$file_header .= "<script>\n";
		$file_header .= "function unhide(divID) {
	    var x = document.getElementById(divID);
	    if(x) {
	      x.className=(x.className=='hidden')?'unhidden':'hidden'; }
	  }\n";
		$file_header .= "</script>\n";
		$file_header .= "</head>";
		$file_header .= "<body>\n";
		$content = str_replace("<","&lt;",$content);
		$content = str_replace(">","&gt;",$content);
		$content = str_replace(chr(10),"<br />",$content);
		$content = str_replace("  ","&nbsp;&nbsp;",$content); // Remove tabulations
		$table = explode("###",$content);
		$handle = fopen($html_help_file,"w");
		$file_header .= "<p style=\"color:MediumTurquoise;\">".$table[0]."</p>";
		$im = count($table);
		for($i = 1; $i < $im; $i++) {
			$table2 = explode("<br />",$table[$i]);
			$thetitle = trim($table2[0]);
			if($thetitle == "END OF BP2 help") {
			//	$im--;
				break;
				}
			$title[$i] = $thetitle;
			$item[$i] = '';
			for($j = 1; $j < count($table2); $j++) {
				$item_line = make_links_clickable($table2[$j]);
				$item[$i] .= $item_line."<br />";
				}
			}
		fwrite($handle,$file_header."\n");
		$table_of_contents = "<table style=\"border-spacing: 2px;\" cellpadding=\"2px;\"><tr>";
		$col = 1;
		for($i = 1; $i < $im; $i++) {
			if($col > 2) {
				$col = 1;
				$table_of_contents .= "</tr><tr>";
				}
			if(isset($title[$i]) AND $title[$i] <> '') {
				$table_of_contents .= "<td><small><a href=\"#".$i."\">".$title[$i]."</a></small></td>";
				$col++;
				$token = preg_replace("/\s?\[.*$/u",'',$title[$i]);
				$token = preg_replace("/\s?\(.*$/u",'',$token);
		//		$token = preg_replace("/\s?:.*$/u",'',$token);
				if(!in_array($token,$no_entry))
					$help[$i] = $title[$i];
				else $help[$i] = '';
				}
			}
		$table_of_contents .= "</tr></table>";
		$table_header = "<h4 id=\"toc\" style=\"color:red;\">►&nbsp; Table of contents <a  href=\"javascript:unhide('up');unhide('up2');unhide('down');\"><span id=\"down\" class=\"unhidden\">[Show list…]</span></a>&nbsp;<a  href=\"javascript:unhide('up');unhide('up2');unhide('down');\"><span id=\"up2\" class=\"hidden\">[Hide list…]</span></a></h4>";
		$table_header  .= "<div id=\"up\" class=\"hidden\">";
		$table_header  .= $table_of_contents;
		$table_header  .= "<p style=\"text-align:center;\">[<a class=\"triangle\" href=\"javascript:unhide('up');unhide('up2');unhide('down');\">Hide list…</a>]</p></div>";
		fwrite($handle,$table_header."\n");
		for($i = 1; $i < $im; $i++) {
			if(!isset($title[$i])) continue;
			fwrite($handle,"<h4 style=\"color:MediumTurquoise;\" id=\"".$i."\"><a href=\"#toc\">⇪</a> ".$title[$i]."</h4>\n");
			fwrite($handle,$item[$i]."\n");
			}
		fwrite($handle,"</body>");
		fclose($handle);
		}
	return $help;
	}

function link_to_help() {
	global $html_help_file;
	$console_link = "produce.php?instruction=help";
	$link = "<p>➡ Display <a href=\"".$console_link."\" onclick=\"window.open('".$console_link."','help','width=800,height=800,left=200'); return false;\">console's</a> instructions or the <a onclick=\"window.open('".$html_help_file."','Help','width=800,height=500'); return false;\" href=\"".$html_help_file."\">complete help file</a></p>";
	return $link;
	}

function display_help_entries($content) {
	$table = explode("\n",$content);
	$ignore = FALSE;
	$entries = "<br /><table id=\"help_entries\" style=\"border-spacing: 2px;\"><tr><td style=\"padding:1em; background-color:azure;\">";
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		$last_one = FALSE;
		if(is_integer(strpos($line,"COMMENT:")) OR is_integer(strpos($line,"DATA:"))) $last_one = TRUE;
		$line = preg_replace("/\/\/.*$/u",'',$line);
		if(strlen($line) < 2) continue;
		if(!$ignore) $line = add_help_links($line);
		if($last_one) $ignore = TRUE;
		$entries .= $line."<br />";
		}
	$entries .= "</td></tr></table>";
	return $entries;
	}

function add_help_links($line) {
	global $help, $html_help_file;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) return $line;
	$done = array();
	for($i = count($help) - 1; $i > 0; $i--) {
		if(!isset($help[$i])) continue;
		$token = preg_replace("/\s?\[.*$/u",'',$help[$i]);
		$token = preg_replace("/\s?\(.*$/u",'',$token);
		$token = preg_replace("/\s?«.*$/u",'',$token);
		$token = preg_replace("/\s?:.*$/u",'',$token);
		$token_length = strlen($token);
		if($token_length < 2) continue;
		$start = 0;
		$start_max = strlen($line) - $token_length;
		do {
			if(is_integer($pos=strpos($line,$token,$start))) {
				if(isset($done[$pos])) $start = $pos + strlen($token);
				else {
					$pos1 = $pos;
					$pos2 = $pos + strlen($token);
					$l1 = substr($line,0,$pos1);
					$l2 = substr($line,$pos1,strlen($token));
					$l3 = substr($line,$pos2,strlen($line) - $pos2);
				//	$insert = "<a onclick=\"window.open('".$html_help_file."#".$i."','show_".$i."','width=800,height=300'); return false;\" href=\"".$html_help_file."#".$i."\">";
					$insert = "<a onclick=\"window.open('".$html_help_file."#".$i."','show_help','width=800,height=300'); return false;\" href=\"".$html_help_file."#".$i."\">";
					$line = $l1.$insert.$l2."</a>".$l3;
					$posdone = $pos1 + strlen($insert);
					$done[$posdone] = TRUE;
					// We should not insert another help link if a shorter token has been found at the same position
					// For instance: “_velcont _vel”
					break;
					}
				}
			else break;
			}
		while($start <= $start_max);
		}
	return $line;
	}

function gcd($a,$b) {
	if($a < 0) $a = - $a;
	if($b < 0) $b = - $b;
    return $b ? gcd($b,fmod($a,$b)) : $a; // Use fmod() to avoid overflow
	}
	
function lcm($a,$b) {
	global $max_term_in_fraction;
	if(($a * $b) == 0) return 0;
	if($a >= $max_term_in_fraction OR $b >= $max_term_in_fraction) return $max_term_in_fraction;
	$gcd = gcd($a,$b);
	$aa = $a / $gcd;
	$bb = $b / $gcd;
	return ($aa * $bb * $gcd);
	}

function gcd_array($array,$a = 0) {
    $b = array_pop($array);
    return($b === null) ? (int)$a : gcd_array($array, gcd($a,$b));
	}

function clean_up_encoding($create_bullets,$convert,$text) {
	if($convert) $text = mb_convert_encoding($text, "UTF-8", mb_detect_encoding($text, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	$text = str_replace("¥","•",$text);
	$text = str_replace("Ô","‘",$text);
	$text = str_replace("Õ","’",$text);
	$text = str_replace("Ò","“",$text);
	$text = str_replace("Ó","”",$text);
	$text = str_replace("É","…",$text);
	$text = str_replace("Â","¬",$text);
	$text = str_replace("¤","•",$text);
	$text = str_replace("â¢","•",$text);
	if($create_bullets) $text = preg_replace("/\s\\.$/u"," •",$text);
	if($create_bullets) $text = preg_replace("/\s\\.([^0-9])/u"," •$1",$text);
	$text = str_replace("²","≤",$text);
	$text = str_replace("³","≥",$text);
	return $text;
	}

function recode_tags($text) {
	$text = str_replace("<","&lt;",$text);
	$text = str_replace(">","&gt;",$text);
	$text = str_replace('"',"&quot;",$text);
	return $text;
	}

function decode_tags($text) {
	$text = str_replace("&lt;","<",$text);
	$text = str_replace("&gt;",">",$text);
	$text = str_replace("&quot;",'"',$text);
	return $text;
	}

function recode_entities($text) {
	$text = preg_replace("/\s*•$/u"," .",$text);
//	$text = preg_replace("/\s*•[ ]*/u"," . ",$text);
	$text = preg_replace("/\s*•\s/u"," . ",$text);
	$text = str_replace(" … "," _rest ",$text);
	$text = preg_replace("/\s*…\s*,/u"," _rest,",$text);
	$text = preg_replace("/{\s*…\s*/u","{_rest ",$text);
	$text = preg_replace("/\s*…\s*}/u"," _rest}",$text);
	$text = preg_replace("/,\s*…\s*/u",", _rest ",$text);
	return $text;
	}

function decode_entities($text) {
	$text = str_replace("_rest","…",$text);
	return $text;
	}

function clean_up_file_to_html($file) {
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$file_html = str_replace(".txt",".html",$file);
	$text = @file_get_contents($file,TRUE);
	$text = str_replace(chr(13).chr(10),chr(10),$text);
	$text = str_replace(chr(13),chr(10),$text);
	$text = str_replace(chr(9),' ',$text);
	$text = trim($text);
	$text = recode_tags($text);
	$text = clean_up_encoding(FALSE,TRUE,$text);
	$text = decode_entities($text);
	$text = str_replace("¬",'',$text);
	do $text = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$text,$count);
	while($count > 0);
	$text = str_replace(chr(10),"<br />",$text);
	$handle = fopen($file_html,"w");
	$header = "<head>\n";
	$header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />\n";
	$header .= "</head><body>\n";
	fwrite($handle,$header."\n");
	fwrite($handle,$text."\n");
	fwrite($handle,"</body>\n");
	fclose($handle);
	return $file_html;
	}

function clean_up_file($file) { // NOT USED
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$text = @file_get_contents($file,TRUE);
	$text = str_replace(chr(13).chr(10),chr(10),$text);
	$text = str_replace(chr(13),chr(10),$text);
	$text = str_replace(chr(9),' ',$text);
	$text = trim($text);
	$text = recode_tags($text);
	$text = clean_up_encoding(FALSE,TRUE,$text);
	do $text = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$text,$count);
	while($count > 0);
	$handle = fopen($file,"w");
	fwrite($handle,$text."\n");
	fclose($handle);
	return $file;
	}

function get_setting($parameter,$settings_file) {
	global $dir;
	$bp_parameter_names = @file_get_contents("settings_names.txt",TRUE);
	if($bp_parameter_names == FALSE) return "error reading settings_names.txt";
	$table = explode(chr(10),$bp_parameter_names);
	$imax = count($table);
	$imax_parameters = 0;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == "-- end --") break;
		$imax_parameters++;
		$table2 = explode(chr(9),$line);
		$r = str_replace(chr(9),".",$line);
		if(count($table2) < 3) echo "ERR: ".$table2[0]."<br />";
		$parameter_name[$i] = $table2[0];
		$parameter_unit[$i] = $table2[1];
		$parameter_edit[$i] = $table2[2];
		if(count($table2) > 3 AND $table2[3] > 0)
			$parameter_yesno[$i] = TRUE;
		else $parameter_yesno[$i] = FALSE;
		}
	$content = @file_get_contents($dir.$settings_file,TRUE);
	if($content == FALSE) return "error reading ".$dir.$settings_file;
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$i = -1;
	if($parameter == "note_convention") $i = 47;
	if($parameter == "nature_of_time") $i = 6;
	if($parameter == "show_production") $i = 14;
	if($parameter == "trace_production") $i = 17;
	if($parameter == "produce_all_items") $i = 13;
	if($parameter == "random_seed") $i = 45;
	if($parameter == "non_stop_improvize") $i = 10;
	if($parameter == "p_clock") $i = 7;
	if($parameter == "q_clock") $i = 8;
	if($parameter == "max_time_computing") $i = 44;
	if($parameter == "diapason") $i = 63;
	if($parameter == "C4key") $i = 62;
	if($parameter == "csound_default_orchestra") $i = 1;
	if($parameter == "quantization") $i = 2;
	if($parameter == "quantize") $i = 5;
	if($parameter == "time_resolution") $i = 3;
	if($i <> -1) return $table[$i];
	else return '';
	}

function note_convention($i) {
	switch($i) {
		case 0: $c = "english"; break;
		case 1: $c = "italian/Spanish/French"; break;
		case 2: $c = "indian"; break;
		case 3: $c = "keys"; break;
		case 4: $c = "custom"; break;
		}
	return $c;
	}

function my_rmdir($src) {
    $dir = opendir($src);
    while(FALSE !== ($file = readdir($dir))) {
        if(($file <> '.' ) && ($file <> '..')) {
            $full = $src.SLASH.$file;
            if(is_dir($full)) my_rmdir($full);
            else unlink($full);
            }
        }
    closedir($dir);
    rmdir($src);
    return;
	}

function SaveObjectPrototypes($verbose,$dir,$filename,$temp_folder,$force) {
	global $top_header,$test,$temp_dir,$csound_resources;
	$file_lock = $filename."_lock";
	$time_start = time();
	$time_end = $time_start + 3;
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
	if(!$force AND !file_exists($file_changed)) return "skipped";
	@unlink($file_changed);
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) unlink($file_lock);
		sleep(1);
		}
	$handle = fopen($dir.$file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	$handle = fopen($dir.$filename,"w");
	$file_header = $top_header."\n// Object prototypes file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	$PrototypeTickKey = $_POST['PrototypeTickKey'];
	fwrite($handle,$PrototypeTickKey."\n");
	$PrototypeTickChannel = $_POST['PrototypeTickChannel'];
	fwrite($handle,$PrototypeTickChannel."\n");
	$PrototypeTickVelocity = $_POST['PrototypeTickVelocity'];
	fwrite($handle,$PrototypeTickVelocity."\n");
	$CsoundInstruments_filename = trim($_POST['CsoundInstruments_filename']);
	if(!is_integer($pos=strpos($CsoundInstruments_filename,$csound_resources)) AND $CsoundInstruments_filename <> '')
		$CsoundInstruments_filename = $csound_resources.SLASH.$CsoundInstruments_filename;
	fwrite($handle,$CsoundInstruments_filename."\n");
	$maxsounds = $_POST['maxsounds'];
	fwrite($handle,$maxsounds."\n");
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		$table = explode(".",$thisfile);
		$extension = end($table);
		if($extension <> "txt") continue;
		$object_label = str_replace(".".$extension,'',$thisfile);
		if($verbose) echo $object_label." ";
		$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile,TRUE);
		$extract_data = extract_data(TRUE,$content);
		$headers = $extract_data['headers'];
		if(!is_integer($pos=strpos($headers,"//"))) continue;
		$content = $extract_data['content'];
		$table = explode(chr(10),$content);
		$line = "<HTML>".$object_label."</HTML>";
		fwrite($handle,$line."\n");
		$object_foldername = clean_folder_name($object_label);
		$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
		$midi_bytes = $save_codes_dir."/midibytes.txt";
		$comment_this_prototype = '';
		for($i = 1; $i < count($table); $i++) {
			if($i > 10 AND trim($table[$i]) == '') break;
			$line = $table[$i];
			if(is_integer($pos=strpos($line,"<HTML>"))) {
				$comment_this_prototype = $line;
				$comment_this_prototype = str_replace("<HTML>",'',$comment_this_prototype);
				$comment_this_prototype = trim(str_replace("</HTML>",'',$comment_this_prototype));
				}
			else fwrite($handle,$line."\n");
			}
		fwrite($handle,"_beginCsoundScore_\n");
		$csound_file = $save_codes_dir."/csound.txt";
		$csound_score = @file_get_contents($csound_file,TRUE);
		$table2 = explode(chr(10),$csound_score);
		$csound_score = "<HTML>";
		for($k = 0; $k < count($table2); $k++) {
			$line = trim($table2[$k]);
			if($line <> '') $csound_score .= $line."<BR>";
			}
		$csound_score .= "</HTML>";
		
		fwrite($handle,$csound_score."\n");
		fwrite($handle,"_endCsoundScore_\n");
		// We fetch MIDI codes from a separate "midibytes.txt" file
		$all_bytes = @file_get_contents($midi_bytes,TRUE);
		$table_bytes = explode(chr(10),$all_bytes);
		$found = FALSE;
		for($j = 0; $j < count($table_bytes); $j++) {
			$byte = trim($table_bytes[$j]);
			if($byte <> '') {
				fwrite($handle,$byte."\n");
				$found = TRUE;
				}
			}
		if(!$found) fwrite($handle,"0\n"); // This is required because "midibytes.txt" might be empty when there is no MIDI code. The first number in the prototypes file is the number of MIDI codes that follow.
		$comment_this_prototype = "<HTML>".$comment_this_prototype."</HTML>";
		fwrite($handle,$comment_this_prototype."\n");
		}
	fwrite($handle,"DATA:\n");
	$comment_on_file = $_POST['comment_on_file'];
	$comment_on_file = recode_tags($comment_on_file);
	fwrite($handle,"<HTML>".$comment_on_file."</HTML>\n");
	fwrite($handle,"_endSoundObjectFile_\n");
	fclose($handle);
	if($verbose) echo "</font></p><hr>";
//	sleep(1);
	unlink($dir.$file_lock);
	return "saved";
	}

function SaveCsoundInstruments($verbose,$dir,$filename,$temp_folder,$force) {
	global $top_header, $test, $temp_dir;
//	$verbose = TRUE;
	if($verbose) echo "dir = ".$dir."<br />";
	if($verbose) echo "filename = ".$filename."<br />";
	if($verbose) echo "temp_folder = ".$temp_folder."<br />";
	$file_lock2 = $dir.$filename."_lock2";
	if(file_exists($file_lock2)) return "locked";
	$file_lock = $filename."_lock";
	$folder_scales = $temp_dir.$temp_folder.SLASH."scales";
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
	if($verbose) echo "file_changed = ".$file_changed."<br />";
	if(!$force AND !file_exists($file_changed)) return "skipped";
	@unlink($file_changed);
	$time_start = time();
	$time_end = $time_start + 3;
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) unlink($file_lock);
		sleep(1);
		}
	$handle = fopen($dir.$file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	unlink($dir.$filename);
	$handle = fopen($dir.$filename,"w");
	$file_header = $top_header."\n// Csound resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	$number_channels = $_POST['number_channels'];
	fwrite($handle,$number_channels."\n");
	for($ch = 0; $ch < $number_channels; $ch++) {
		$arg = "whichCsoundInstrument_".$ch;
		$whichCsoundInstrument = convert_empty($_POST[$arg]);
		fwrite($handle,$whichCsoundInstrument."\n");
		}
	$CsoundOrchestraName = $_POST['CsoundOrchestraName'];
	$warn_not_empty = FALSE;
	if($CsoundOrchestraName == '') {
		$CsoundOrchestraName = "0-default.orc";
		$warn_not_empty = TRUE;
		}
	fwrite($handle,$CsoundOrchestraName."\n");
	$number_instruments = $_POST['number_instruments'];
	fwrite($handle,$number_instruments."\n");
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		$table = explode(".",$thisfile);
		$extension = end($table);
		if($extension <> "txt") continue;
		$instrument_label = str_replace(".".$extension,'',$thisfile);
		if($verbose) echo $instrument_label." ";
		$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile,TRUE);
		$extract_data = extract_data(FALSE,$content);
		$headers = $extract_data['headers'];
		if(!is_integer($pos=strpos($headers,"//"))) continue;
		$content = $extract_data['content'];
		$table = explode(chr(10),$content);
		fwrite($handle,$instrument_label."\n");
		for($i = 1; $i < count($table); $i++) {
			$line = $table[$i];
			fwrite($handle,$line."\n");
			}
		$number_param = 0;
		$instrument_folder_name = str_replace(' ','_',$instrument_label);
		$instrument_folder_name = str_replace('-','_',$instrument_folder_name);
		$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$instrument_folder_name;
		if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
		$dir_instrument = scandir($folder_this_instrument);
		foreach($dir_instrument as $thisparameter) {
			if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store") continue;
			$table = explode(".",$thisparameter);
			$extension = end($table);
			if($extension <> "txt") continue;
			$number_param++;
			}
		fwrite($handle,$number_param."\n");
		foreach($dir_instrument as $thisparameter) {
			if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store") continue;
			$table = explode(".",$thisparameter);
			$extension = end($table);
			if($extension <> "txt") continue;
			$content_parameter = file_get_contents($folder_this_instrument.SLASH.$thisparameter,TRUE);
			$table = explode(chr(10),$content_parameter);
			for($i = 0; $i < count($table); $i++) {
				$line = trim($table[$i]);
				if($line <> '' OR $i < 2) fwrite($handle,$line."\n");
				}
			}
		}
	$begin_tables = $_POST['begin_tables'];
	fwrite($handle,$begin_tables."\n");
	if($verbose) echo "<br />".$begin_tables."<br />";
	if(isset($_POST['cstables'])) $cstables = $_POST['cstables'];
	else {
		if($verbose) echo "Used 'the_tables'<br />";
		$cstables = $_POST['the_tables']; // We need a different POST used by autosave
		}
	$table = explode(chr(10),$cstables);
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if($verbose) echo $line."<br />";
		if($line == '') continue;
		fwrite($handle,$line."\n");
		}

// 2024-08-10 We no longer store scales here as they are stored in '-to' files
/*	$dir_scale = scandir($folder_scales); 
	foreach($dir_scale as $this_scale) {
		if($this_scale == '.' OR $this_scale == ".." OR $this_scale == ".DS_Store") continue;
		$table = explode(".",$this_scale);
		$extension = end($table);
		if($extension <> "txt") continue;
		$content_scale = file_get_contents($folder_scales.SLASH.$this_scale,TRUE);
		$table = explode(chr(10),$content_scale);
		for($i = 0; $i < count($table); $i++) {
			$line = trim($table[$i]);
			if($line <> '') fwrite($handle,$line."\n");
			}
		} */

	fwrite($handle,"_end tables\n");
	$tonality_filename = $_POST['tonality_filename'];
	fwrite($handle,$tonality_filename."\n");
	fclose($handle);
	unlink($dir.$file_lock);
	return $warn_not_empty;
	}
	
function reformat_grammar($verbose,$this_file) {
	if(!file_exists($this_file)) return;
	$content = @file_get_contents($this_file,TRUE);
	$new_content = $content;
	$i_gram = $irul = 1;
	$section_headers = array("RND","ORD","LIN","SUB","SUB1","TEM","POSLONG","LEFT","RIGHT","INIT:","TIMEPATTERNS:","DATA:","COMMENTS:");
	$table = explode(chr(10),$new_content);
	$ignore_all = FALSE;
	$i_line_max = count($table);
	for($i_line = 0; $i_line < $i_line_max; $i_line++) {
		$line = trim($table[$i_line]);
		$line_no_brackets = preg_replace("/\s*?\[.*\]/u",'',$line);
		$ignore = FALSE;
		if($line_no_brackets == '') $ignore = TRUE;
		if(!is_integer(strpos($line,"-->")) AND !is_integer(strpos($line,"<->")) AND !is_integer(strpos($line,"<--"))) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"--")) AND $pos == 0) {
			$i_gram++; $irul = 1;
			$ignore = TRUE;
			}
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"_")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"[")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=stripos($line,"gram#")) AND $pos == 0) {
			$ignore = TRUE;
			$line = preg_replace("/^GRAM#/u","gram#",$line);
			$line = preg_replace("/^gram#([0-9]+)\[([0-9]+)\]/u","gram#".$i_gram."[".$irul."]",$line);
			$irul++;
			}
		if(in_array($line_no_brackets,$section_headers)) $ignore = TRUE;
		if($line_no_brackets == "TIMEPATTERNS:") {
			if($verbose) echo $line."<br />";
			$i_line++;
			do {
				$line = trim($table[$i_line]);
				$table[$i_line] = $line;
				if($verbose) echo $line."<br />";
				$i_line++;
				}
			while(!is_integer($pos=strpos($line,"--")) AND $i_line < $i_line_max);
			continue;
			}
		if($line_no_brackets == "DATA:" OR $line_no_brackets == "COMMENTS:") $ignore_all = TRUE;
		if(!$ignore AND !$ignore_all) {
			$line = "gram#".$i_gram."[".$irul."] ".$line;
			$irul++;
			}
		if($verbose) echo $line."<br />";
		$table[$i_line] = $line;
		}
	$new_content = implode(chr(10),$table);
	// $this_file = "-gr._test";
	$handle = fopen($this_file,"w");
	fwrite($handle,$new_content);
	fclose($handle);
	return;
	}

function SaveTonality($verbose,$dir,$filename,$temp_folder,$force) {
	global $top_header, $test, $temp_dir;
	$verbose = FALSE;
	if($verbose) echo "<br />dir = ".$dir."<br />";
	if($verbose) echo "filename = ".$filename."<br />";
	if($verbose) echo "temp_folder = ".$temp_folder."<br />";
	$file_lock3 = $dir.$filename."_lock3";
//	if($verbose) echo "<br />file_lock3 = ".$file_lock3."<br />";
	if(file_exists($file_lock3)) return "locked";
	$file_lock = $dir.$filename."_lock";
	$folder_scales = $temp_dir.$temp_folder.SLASH."scales";
//	echo "temp_folder = ".$temp_folder."<br />";
//	echo "folder_scales = ".$folder_scales."<br />";
	$file_changed = $folder_scales.SLASH."_changed";
	if($verbose) echo "file_changed = ".$file_changed."<br />";
	if(!$force AND !file_exists($file_changed)) return "skipped";
	@unlink($file_changed);
	$time_start = time();
	$time_end = $time_start + 3;
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) @unlink($file_lock);
		sleep(1);
		}
	$handle_lock = fopen($file_lock,"w");
	fwrite($handle_lock,"lock\n");
	if($handle_lock) fclose($handle_lock);
	@unlink($dir.$filename);
	$handle = fopen($dir.$filename,"w");
	$file_header = $top_header."\n// Tonality resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	if(isset($_POST['begin_tables'])) $begin_tables = $_POST['begin_tables'];
	else $begin_tables = "_begin tables";
	fwrite($handle,$begin_tables."\n");
	if($verbose) echo "<br />".$begin_tables."<br />";
	$dir_scale = scandir($folder_scales);
	foreach($dir_scale as $this_scale) {
		if($this_scale == '.' OR $this_scale == ".." OR $this_scale == ".DS_Store") continue;
		$table = explode(".",$this_scale);
		$extension = end($table);
		if($extension <> "txt") continue;
		$content_scale = file_get_contents($folder_scales.SLASH.$this_scale,TRUE);
		$table = explode(chr(10),$content_scale);
		for($i = 0; $i < count($table); $i++) {
			$line = trim($table[$i]);
			if($line <> '') fwrite($handle,$line."\n");
			}
		}
	fwrite($handle,"_end tables\n");
	fclose($handle);
	unlink($file_lock);
	return "saved";
	}

function clean_folder_name($name) {
	// It shouldn't create trouble when part of PHP, Javascript or command-line arguments
	$name = str_replace('_','-',$name);
	$name = str_replace(' ','-',$name);
	$name = str_replace("'",'-',$name);
	$name = str_replace('"','-',$name);
	return $name;
	}

function convert_mf2t_to_bytes($verbose,$midi_import,$midi,$midi_file) {
//	$verbose = TRUE;
	// midi_file contains the code in MIDI format
	$midi->importMid($midi_file);
	$midi_bytes = array();
	$jcode = 5;
	$tt = 0; // We ask for absolute time stamps
	$old_tempo = $tempo = 1000000; // Default value
	$text = $midi->getTxt($tt);
	$handle = fopen($midi_import,"w");
	$table = explode(chr(10),$text);
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(" ",$line);
		if(isset($table2[3]) AND $table2[0] == "MFile") {
			$old_division = intval($table2[3]);
			}
		if(isset($table2[2]) AND $table2[1] == "Tempo" AND $table2[0] == "0") {
			$tempo = intval($table2[2]);
			break;
			}
		}
	$division = $tempo / 1000; 
	$alpha = $division / $old_division;
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
	//	echo $line."<br />";
		$table2 = explode(" ",$line);
		if(isset($table2[3]) AND $table2[0] == "MFile") {
			$table2[3] = $division;
			$line = implode(' ',$table2);
			fwrite($handle,$line."\n");
			continue;
			}
		if(isset($table2[2]) AND $table2[1] == "Tempo" AND $table2[0] == "0") {
			$table2[2] = $tempo;
			$line = implode(' ',$table2);
			fwrite($handle,$line."\n");
			continue;
			}
		if(count($table2) > 3 OR (isset($table2[2]) AND ($table2[2] == "TrkEnd" OR $table2[1] == "PrCh"))) {
			$time = round($table2[0] * $alpha);
			$table2[0] = $time;
			$line = implode(' ',$table2);
			}
		fwrite($handle,$line."\n");
		if(count($table2) < 4) continue;
		$chan = str_replace("ch=",'',$table2[2]);
		$code[0] = $code[1] = $code[2] = $code[3] = -1;
		if(isset($table2[4]) AND $table2[1] == "TimeSig" AND $table2[0] == "0") {
			$midi_bytes[2] = $table2[2];
			$midi_bytes[3] = $table2[3];
			$midi_bytes[4] = $table2[4];
			}
		else if(isset($table2[1]) AND $table2[1] == "ChPr") {
			$val = str_replace("v=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Channel pressure ".$val."<br />";
			$code[0] = 208 + $chan - 1;
			$code[1] = $val;
			}
		else if(isset($table2[1]) AND $table2[1] == "Pb") {
			$val = str_replace("v=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Pitchbend ".$val."<br />";
			$code[0] = 224 + $chan - 1;
			$code[1] = $val % 256;
			$code[2] = ($val - $code[1]) / 256;
			}
		else if(isset($table2[1]) AND $table2[1] == "PrCh") {
			$prog = str_replace("p=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Prog change ".$prog."<br />";
			$code[0] = 192 + $chan - 1;
			$code[1] = $prog;
			}
		else if(isset($table2[1]) AND $table2[1] == "On") {
			$key = str_replace("n=",'',$table2[3]);
			$vel = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") NoteOn ".$key." ".$vel."<br />";
			$code[0] = 144 + $chan - 1;
			$code[1] = $key;
			$code[2] = $vel;
			}
		else if(isset($table2[1]) AND $table2[1] == "Off") {
			$key = str_replace("n=",'',$table2[3]);
			$vel = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") NoteOff key ".$key." ".$vel."<br />";
			$code[0] = 128 + $chan - 1;
			$code[1] = $key;
			$code[2] = $vel;
			}
		else if(isset($table2[1]) AND $table2[1] == "Par") {
			$ctrl = str_replace("c=",'',$table2[3]);
			$val = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") Parameter ctrl ".$ctrl." ".$val."<br />";
			$code[0] = 176 + $chan - 1;
			$code[1] = $ctrl;
			$code[2] = $val;
			}
		else if(isset($table2[1]) AND $table2[1] == "PoPr") {
			$key = str_replace("n=",'',$table2[3]);
			$val = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") Poly pressure key ".$key." ".$val."<br />";
			$code[0] = 160 + $chan - 1;
			$code[1] = $key;
			$code[2] = $val;
			}
		$time_signature = 256 * $time;
		for($j = 0; $j < 4; $j++) {
			if($code[$j] >= 0) {
				$byte = $time_signature + $code[$j];
				$midi_bytes[$jcode++] = $byte;
				}
			}
		}
	$midi_bytes[0] = $division;
	$midi_bytes[1] = $tempo;
	fclose($handle);
//	echo "ok3"; die();
	return $midi_bytes;
	}

function fix_mf2t_file($file,$tracknames) {
	$said = $bad = FALSE;
	$header = "<br /><span style=\"color:red;\">Fixed imported MIDI file:</span><ul>";
	$message = '';
	$said = $content = FALSE;
	if(file_exists($file)) $content = @file_get_contents($file,TRUE);
	if(!$content) {
		$message .= "<br /><font color=\"red\">Cannot find or open:</font> <font color=\"blue\">".$file."</font>";
		return $message;
		}
	$handle = fopen($file,"w");
	$table = explode(chr(10),$content);
	$i0 = 0;
	if(!is_integer(strpos($content," Tempo "))) {
		$i0 = 1;
		$line = trim($table[0]);
		$table2 = explode(' ',$line);
		$new_track_number = intval($table2[2]) + 1;
		$table2[2] = $new_track_number;
		$newline = implode(' ',$table2);
		fwrite($handle,$newline."\n");
		if(!$said) $message .= $header;
		$said = TRUE;
		$bad = TRUE;
		$message .= "<li>Adding header:<ul><li>".$newline."</li>";
		$line = "MTrk\n0 Meta TrkName \"header\"\n0 TimeSig 1/4 24 8\n0 Tempo 1000000\n0 KeySig 0 major\n0 Meta TrkEnd\nTrkEnd";
		fwrite($handle,$line."\n");
		$message .= "<li>".str_replace("\n","<br />",$line)."</li></ul></li>";
		}
	$track_nr = 1;
	for($i = $i0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if($line == "TrkEnd") {
			$line2 = trim($table[$i - 1]);
			if(!is_integer(strpos($line2,"TrkEnd"))) {
				if(!$said) $message .= $header;
				$said = TRUE;
				$bad = TRUE;
				$table2 = explode(' ',$line2);
				$time = intval($table2[0]);
				$newline = $time." Meta TrkEnd";
				fwrite($handle,$newline."\n");
				$message .= "<li>Added: ".$newline."</li>";
				}
			}
		fwrite($handle,$line."\n");
		if($line == "MTrk") {
			$line2 = trim($table[$i + 1]);
			$track_nr++;
			if(!is_integer(strpos($line2,"TrkName"))) {
				if(!$said) $message .= $header;
				$said = TRUE;
				$bad = TRUE;
				$newline = "0 Meta TrkName \"".$tracknames.$track_nr."\"";
				fwrite($handle,$newline."\n");
				$message .= "<li>Added: ".$newline."</li>";
				}
			}
		}
	if($bad) $message .= "</ul>";
	fclose($handle);
	return $message;
	}

function fix_number_bytes($midi_bytes) {
	$content = @file_get_contents($midi_bytes,TRUE);
	if($content) {
		$table = explode(chr(10),$content);
		$newtable = array();
		for($i = $j = 0; $i < count($table); $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			$newtable[$j++] = $line;
			}
		$newtable[0] = $j - 1;
		$content = implode(chr(10),$newtable);
		$handle = fopen($midi_bytes,"w");
		fwrite($handle,$content);
		fclose($handle);
		}
	return;
	}
	
function duration_of_midifile($mf2t_content) {
	$duration = 0;
	$table = explode(chr(10),$mf2t_content);
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(' ',$line);
		if(count($table2) < 5) continue;
		if($table2[1] == "TimeSig" OR $table2[1] == "Tempo") continue;
		$time = intval($table2[0]);
		if($time <> $table2[0]) continue;
		if($time > $duration) $duration = $time;
		}
	return $duration;
	}
	
function mf2t_no_header($mf2t_content) {
	$result = array();
	$table = explode(chr(10),$mf2t_content);
	$found_MTrk = 0;
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(' ',$line);
		if($table2[0] == "MTrk") {
			$found_MTrk++;
			}
		if($found_MTrk > 1) {
			$result[] = $line;
		//	echo $line."<br />";
			}
		else {
			if(count($table2) > 1) $x = $table2[1];
			else $x = '';
			if($x == "Par" OR $x == "On" OR $x == "Off" OR $x == "ChPr" OR $x == "PrCh") {
				$found_MTrk++;
				$result[] = "MTrk";
				$result[] = $line;
				}
			}
		}
	return $result;
	}

function metronome($p,$q) {
	$mm = round(($p * 60) / $q, 3);
	return $mm;
	}
	
function rcopy($src,$dst) {
	if(file_exists($dst)) my_rmdir($dst);
	if(is_dir($src)) {
		mkdir($dst);
		$files = scandir($src);
		foreach($files as $file)
			if($file <> "." AND $file <> "..") rcopy("$src/$file","$dst/$file");
		}
	else if(file_exists($src)) copy($src,$dst);
	return;
	}

function store($handle,$varname,$var) {
	$line = "$".$varname." = \"".$var."\";\n";
	// echo $varname."<br />";
	fwrite($handle,$line);
	return;
	}

function store2($handle,$varname,$index,$var) {
	$line = "$".$varname."[".$index."] = \"".$var."\";\n";
	// echo $varname."<br />";
	fwrite($handle,$line);
	return;
	}

function good_name($type,$filename,$name_mode) {
	$filename = fix_new_name($filename);
	$filename = trim($filename);
//	echo "filename = ".$filename."<br />";
	$filename = str_replace("-".$type.".",'',$filename);
	$filename = str_replace(".bp".$type,'',$filename);
//	$filename = str_replace(" ","_",$filename);
	if($name_mode == "extension")
		$filename .= ".bp".$type;
	else
		$filename = "-".$type.".".$filename;
	return $filename;
	}

function fix_new_name($name) {
	$name = str_replace('+','_',$name);
	$name = str_replace(' ','_',$name);
	$name = str_replace(SLASH,'_',$name);
	$name = str_replace('"',"'",$name);
	return $name;
	}

function type_of_file($thisfile) {
	$table = explode(".",$thisfile);
	$prefix = $table[0];
//	if(strlen($prefix) <> 3 OR (!is_integer($pos=strpos($prefix,"-") AND !is_integer($pos=strpos($prefix,"+")))) OR $pos <> 0)
//		$prefix = '';
	if(strlen($prefix) <> 3 OR ($prefix[0] <> '-' AND $prefix[0] <> '+')) $prefix = '';
	$extension = end($table);
	if($prefix.".".$extension == $thisfile) $extension = '';
	switch($prefix) {
		case '-gr':
			$type = "grammar"; break;
		case '-da':
			$type = "data"; break;
		case '-ho':
		case '-al':
			$type = "alphabet"; break;
		case '-se':
			$type = "settings"; break;
		case '-cs':
			$type = "csound"; break;
		case '-mi':
		case '-so':
			$type = "objects"; break;
		case '-or':
			$type = "orchestra"; break;
		case '-in':
			$type = "interaction"; break;
		case '-md':
			$type = "midisetup"; break;
		case '-tb':
			$type = "timebase"; break;
		case '-kb':
			$type = "keyboard"; break;
		case '-gl':
			$type = "glossary"; break;
		case '-to':
			$type = "tonality"; break;
		case '-sc':
		case '+sc':
			$type = "script"; break;
		default:
			$type = ''; break;
		}
	$found = FALSE;
	switch($extension) {
		case "bpgr": $type = "grammar"; $found = TRUE; break;
		case "bpda": $type = "data"; $found = TRUE; break;
		case "bpal": $type = "alphabet"; $found = TRUE; break;
		case "bpho": $type = "alphabet"; $found = TRUE; break;
		case "bpse": $type = "settings"; $found = TRUE; break;
		case "bpcs": $type = "csound"; $found = TRUE; break;
		case "bpso": $type = "objects"; $found = TRUE; break;
		case "bpmi": $type = "objects"; $found = TRUE; break;
		case "bpor": $type = "orchestra"; $found = TRUE; break;
		case "bpin": $type = "interaction"; $found = TRUE; break;
		case "bpmd": $type = "midisetup"; $found = TRUE; break;
		case "bptb": $type = "timebase"; $found = TRUE; break;
		case "bpkb": $type = "keyboard"; $found = TRUE; break;
		case "bpgl": $type = "glossary"; $found = TRUE; break;
		case "bptu": $type = "tonality"; $found = TRUE; break;
		case "bpsc": $type = "script"; $found = TRUE; break;
		case "bpto": $type = "tonality"; $found = TRUE; break;
		case "orc": $type = "csorchestra"; $found = TRUE; break;
	/*	case "sco": $type = "csorchestra"; $found = TRUE; break;
		case "aiff": $type = "csorchestra"; $found = TRUE; break;
		case "wav": $type = "csorchestra"; $found = TRUE; break; */
		case "png": $type = "image"; $found = TRUE; break;
		}
	if($found) $name_mode = "extension";
	else $name_mode = "prefix";
	$type_of_file['type'] = $type;
	$type_of_file['name_mode'] = $name_mode;
	$type_of_file['prefix'] = $prefix;
	$type_of_file['extension'] = $extension;
	return $type_of_file;
	} 

function change_occurrences_name_in_files($dir,$old_name,$new_name) {
	set_time_limit(1000);
	$dircontent = scandir($dir);
	foreach($dircontent as $thisfile) {
		if($thisfile[0] == '.' OR $thisfile[0] == '_') continue;
		if(is_dir($dir.SLASH.$thisfile)) continue;
		$type_of_file = type_of_file($thisfile);
		$type = $type_of_file['type'];
		if($type <> "grammar" AND $type <> "data" AND $type <> "alphabet" AND $type <> "objects")
			continue;
		$content = file_get_contents($dir.SLASH.$thisfile,TRUE);
		$table = explode(chr(10),$content);
		$imax = count($table);
		$found = FALSE;
		$new_table = array();
		for($i = 0; $i < $imax; $i++) {
			$line = $table[$i];
			if(is_integer(strpos($line,$old_name))) {
				if($i > 1) echo "• Found ‘<font color=\"blue\">".$old_name."</font>’ in ‘<font color=\"blue\">".$thisfile."</font>’ and changed it to ‘<font color=\"blue\">".$new_name."</font>’<br />";
				$line = str_replace($old_name,$new_name,$line);
				$found = TRUE;
				}
			$new_table[$i] = $line;
			}
		if(!$found) continue;
		$handle = fopen($dir.SLASH.$thisfile,"w");
		for($i = 0; $i < $imax; $i++) {
			$line = $new_table[$i];
			fwrite($handle,$line."\n");
			}
		fclose($handle);
		}
	return;
	}

function find_dependencies($dir,$name) {
	set_time_limit(1000);
	$old_name = old_name($name);
	$dependencies = array();
	$dircontent = scandir($dir);
	foreach($dircontent as $thisfile) {
		if($thisfile[0] == '.' OR $thisfile[0] == '_') continue;
		if($thisfile == $name) continue;
		if(is_dir($dir.SLASH.$thisfile)) continue;
		$type_of_file = type_of_file($thisfile);
		$type = $type_of_file['type'];
		if($type <> "grammar" AND $type <> "data" AND $type <> "alphabet" AND $type <> "objects")
			continue;
		$content = file_get_contents($dir.SLASH.$thisfile,TRUE);
		$table = explode(chr(10),$content);
		$imax = count($table);
		for($i = 0; $i < $imax; $i++) {
			$line = trim(str_replace('/','',$table[$i]));
			if($line == $name OR $line == $old_name) {
				$dependencies[] = $thisfile;
				break;
				}
			}
		}
	return $dependencies;
	}

function old_name($name) {
	$table = explode('.',$name);
	$oldprefix = $oldsuffix = '';
	switch(trim($table[0])) {
		case "-al":
			$oldprefix = "-ho";
			break;
		case "-so":
			$oldprefix = "-mi";
			break;
		}
	if($oldprefix <> '') {
		$table[0] = $oldprefix;
		$old_name = implode('.',$table);
		return $old_name;
		}
	$n = count($table);
	if($n > 1) {
		switch(trim($table[$n - 1])) {
			case "bpal":
				$oldsuffix = "bpho";
				break;
			case "bpso";
				$oldsuffix = "bpmi";
				break;
			}
		if($oldsuffix <> '') {
			$table[$n - 1] = $oldsuffix;
			$old_name = implode('.',$table);
			return $old_name;
			}
		}
	return $name;
	}

function new_name($name) {
	$table = explode('.',$name);
	$newprefix = $newsuffix = '';
	switch(trim($table[0])) {
		case "-ho":
			$newprefix = "-al";
			break;
		case "-mi":
			$newprefix = "-so";
			break;
		}
	if($newprefix <> '') {
		$table[0] = $newprefix;
		$new_name = implode('.',$table);
		return $new_name;
		}
	$n = count($table);
	if($n > 1) {
		switch(trim($table[$n - 1])) {
			case "bpho":
				$newsuffix = "bpal";
				break;
			case "bpmi";
				$newsuffix = "bpso";
				break;
			}
		if($newsuffix <> '') {
			$table[$n - 1] = $newsuffix;
			$new_name = implode('.',$table);
			return $new_name;
			}
		}
	return $name;
	}

function MIDIparameter_argument($i,$parameter,$StartIndex,$EndIndex,$TableIndex,$param_value,$IsLogX,$IsLogY,$GEN) {
	$r = "<table>";
	$r .= "<tr>";
	$r .= "<td>";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "start";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "end";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "table";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td style=\"padding: 5px;\">";
	$r .= "<font color=\"red\">".$parameter."</font> continuous arguments";
	$r .= "</td>";
	$r .= "<td>";
	$x = $StartIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"StartIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $EndIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"EndIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $TableIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"TableIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "</table><br />";
	$r .= "<table>";
	$r .= "<tr>";
	$r .= "<td colspan=\"6\">";
	$r .= "▷ <font color=\"red\">".$parameter."</font> mapping of variations";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td rowspan = \"2\" style=\"padding:4px; vertical-align:middle; text-align:center;\"><small>MIDI<br /><font color=\"red\">▼</font><br />Csound</small></td>";
	$r .= "<td style=\"padding: 5px;\">";
	$x = $param_value[0];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_0_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[1];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_1_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[2];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_2_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "<input type=\"checkbox\" name=\"IsLogX_".$i."\"";
    if($IsLogX > 0) $r .= " checked";
    $r .= ">log";
	$r .= "</td>";
	$r .= "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
	$r .= "GEN ";
	$x = intval($GEN);
	if($x < 10) $x = "0".$x;
	$r .= "<input type=\"text\" name=\"GEN_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td style=\"padding: 5px;\">";
	$x = $param_value[3];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_3_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[4];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_4_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[5];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_5_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "<input type=\"checkbox\" name=\"IsLogY_".$i."\"";
    if($IsLogY > 0) $r .= " checked";
    $r .= ">log";
	$r .= "</td>";
	$r .= "</table>";
	return $r;
	}

function max_argument($argmax_file) {
	include($argmax_file);
//	echo "argmax_file = ".$argmax_file."<br />";
	$max = 0;
	if(isset($last_argument)) {
		foreach($last_argument as $value) {
			if($value > $max) $max = $value;
			}
		}
	return $max;
	}
	
function set_argmax_argument($argmax_file,$name,$arg) {
	include($argmax_file);
	$text = "<xxxphp\n";
	$found = FALSE;
	if(isset($last_argument)) {
		foreach($last_argument as $key => $value) {
		//	echo "<br />key = ".$key."<br />";
			if($key == $name) {
				$value = $arg;
				$found = TRUE;
				}
			$text .= "yyylast_argument[\"".$key."\"] = ".$value.";\n";
			}
		}
	if(!$found) $text .= "yyylast_argument[\"".$name."\"] = ".$arg.";\n";
	$text .= "xxx>\n";
	$text = str_replace("xxx","?",$text);
	$text = str_replace("yyy","$",$text);
	$handle = fopen($argmax_file,"w");
	fwrite($handle,$text);
	fclose($handle);
	return;
	}

function convert_empty($value) {
	if(trim($value) == '') $value = -1;
	return($value);
	}
	
function convert2_empty($value) {
	if(trim($value) == '') $value = 2147483647; // 2^31 - 1 (Mersenne)
	return($value);
	}

function octave($convention,$key) {
	switch($convention) {
		case "English":
		case "Indian":
			$octave = intdiv($key,12) - 1;
			break;
		case "Italian/Spanish/French":
		case "French":
			$octave = intdiv($key,12) - 2;
			break;
		}
	return $octave;
	}

function key_to_note($convention,$key) {
	$name["English"] = array("C","Db","D","Eb","E","F","F#","G","Ab","A","Bb","B");
	$name["Italian/Spanish/French"] = array("do","reb","re","mib","mi","fa","fa#","sol","lab","la","sib","si");
	$name["French"] = array("do","reb","re","mib","mi","fa","fa#","sol","lab","la","sib","si");
	$name["Indian"] = array("sa","rek","re","gak","ga","ma","ma#","pa","dhak","dha","nik","ni");
	$octave = octave($convention,$key);
	$class = $key - (12 * intdiv($key,12));
//	echo $key." ".$octave." ".$class."<br />";
	return $name[$convention][$class].$octave;
	}

function polymetric_expression($mute,$TickKey,$TickCycle,$TickChannel,$TickVelocity,$Ptick,$Qtick,$TickDuration,$ThisTick,$p_clock,$q_clock) {
	$period = $q_clock / $p_clock;
	$p = "{";
	$imax = count($TickKey);
	$lcm = 1;
	for($i = 0; $i < $imax; $i++) {
		if($mute[$i]) continue;
		$x = gcd($TickCycle[$i] * $Qtick[$i],$Ptick[$i]);
		$y = ($TickCycle[$i] * $Qtick[$i]) / $x;
		$lcm = ($lcm * $y) / gcd($lcm,$y);
		}
	$first = TRUE;
	for($i = 0; $i < $imax; $i++) {
		if($mute[$i]) continue;
		$repeat = ($lcm * $Ptick[$i]) / ($TickCycle[$i] * $Qtick[$i]);
		if(($repeat) > 50) return "Expression is too complex!";
		$tick_period = (1000 * $period * $Qtick[$i]) / $Ptick[$i];
		$staccato = intval(100 * ($tick_period - $TickDuration[$i]) / $tick_period);
		if(!$first) $p .= ", ";
		else {
			$gcd = gcd($Ptick[$i],$Qtick[$i]);
			$pmin = $Ptick[$i] / $gcd;
			$qmin = $Qtick[$i] / $gcd;
			if($qmin > 1)
				$p .= "_tempo(".$pmin."/".$qmin.") ";
			else
				if($pmin > 1) $p .= "_tempo(".$pmin.") ";
			}
		$p .= "_chan(".$TickChannel[$i].") ";
		$p .= "_vel(".$TickVelocity[$i].") ";
		$p .= "_staccato(".$staccato.") ";
		$first = FALSE;
		for($r = 0; $r < $repeat; $r++) {
			for($j = 0; $j < $TickCycle[$i]; $j++) {
				if($ThisTick[$i][$j]) $p .= key_to_note("English",$TickKey[$i])." ";
				else $p .= "- ";
				}
			}
		}
	$p .=  "}";
	return $p;
	}

function is_variable($note_convention,$word) {
	$word = str_replace(":",'',$word);
	if($word == '') return $word;
//	echo "«".$word."»<br />";
	if($word == "S") return ''; // We take only non-startup variables
	if($word == "RND") return '';
	if($word == "ORD") return '';
	if($word == "LIN") return '';
	if($word == "SUB") return '';
	if($word == "SUB1") return '';
	if($word == "DATA") return '';
	if($word == "LEFT") return '';
	if($word == "RIGHT") return '';
	if($word == "TEMPLATES") return '';
	if($word == "COMMENT") return '';
	if($word == '') return $word;
	$word = str_replace(')','',$word);
	$word = str_replace('(','',$word);
	if($word == '') return $word;
	if($word[0] == '|' AND $word[count($word) - 1] == '|') {
		$word = str_replace('|','',$word);
		return $word;
		}
	if(!ctype_upper($word[0])) return '';
	$word = str_replace('_',' ',$word);
	$word = trim($word);
	$test = preg_replace("/.*•.*/u",'',$word);
	if($test == '') return '';
	$table = explode(' ',$word);
	if(count($table) > 1) {
		$word = $table[0];
	//	echo "“".$word."”<br />";
		}
	if($note_convention == 0) { // English convention
		$test = preg_replace("/[A-G]#?b?[0-9]/u",'',$word);
		if($test == '') return '';
		}
	return trim($word);
	}

function get_instruction($line) {
	$instruction =  preg_replace("/\s?\".*\"/u",'',$line);
	$instruction =  preg_replace("/\-\-.+$/u",'',$instruction);
	$instruction = str_replace(" ON",'',$instruction);
	$instruction = str_replace(" OFF",'',$instruction);
	$instruction =  preg_replace("/\s+=.+$/u",'',$instruction);
	$instruction =  preg_replace("/\s+[0-9]+$/u",'',$instruction);
	$instruction =  preg_replace("/Type\s.+$/u","Type",$instruction);
	$instruction =  preg_replace("/Activate\s.+$/u","Activate",$instruction);
	$instruction =  preg_replace("/Wait\sfor\s.+$/u","Wait+for",$instruction);
	$instruction =  preg_replace("/AE\s.+$/u","AE",$instruction);
	$instruction =  preg_replace("/Default\sbuffer\ssize\s.+$/u","Default buffer size",$instruction);
	$instruction =  preg_replace("/Display\sitems\s.+$/u","Display items",$instruction);
	$instruction =  preg_replace("/Display\stime\ssetting\s.+$/u","Display time setting",$instruction);
	$instruction =  preg_replace("/Expand\sselection\s?.*$/u","Expand selection",$instruction);
	$instruction =  preg_replace("/Graphic\sColor\s?.*$/u","Graphic Color",$instruction);
	$instruction =  preg_replace("/Graphic\sscale\s?.*$/u","Graphic scale",$instruction);
	$instruction =  preg_replace("/Hide\swindow\s?.*$/u","Hide window",$instruction);
	$instruction =  preg_replace("/Ignore\sconstraints\s?.*$/u","Ignore constraints",$instruction);
	$instruction =  preg_replace("/Load\ssettings\s?.*$/u","Load settings",$instruction);
	$instruction =  preg_replace("/Load\sgrammar\s?.*$/u","Load grammar",$instruction);
	$instruction =  preg_replace("/Maximum\sproduction\stime\s?.*$/u","Maximum production time",$instruction);
	$instruction =  preg_replace("/MIDI\sfile\s?.*$/u","MIDI file",$instruction);
	$instruction =  preg_replace("/MIDI\ssound\s?.*$/u","MIDI sound",$instruction);
	$instruction =  preg_replace("/Number\sstreaks\s?.*$/u","Number streaks",$instruction);
	$instruction =  preg_replace("/Object\sprototypes\s?.*$/u","Object prototypes",$instruction);
	$instruction =  preg_replace("/Open\sfile\s?.*$/u","Open file",$instruction);
	$instruction =  preg_replace("/Play\sselection\s?.*$/u","Play+selection",$instruction);
	$instruction =  preg_replace("/Play\s.+$/u","Play",$instruction);
	$instruction =  preg_replace("/Produce\sall\sitems\s?.*$/u","Produce all items",$instruction);
	$instruction =  preg_replace("/Produce\sand\splay\s.*$/u","Produce and play",$instruction);
	$instruction =  preg_replace("/Produce\stemplates$/u","Produce templates",$instruction);
	$instruction =  preg_replace("/Quantization\s.*$/u","Quantization",$instruction);
	$instruction =  preg_replace("/Reset\ssession\stime$/u","Reset session time",$instruction);
	$instruction =  preg_replace("/Reset\ssession\stime$/u","Reset session time",$instruction);
	$instruction =  preg_replace("/Select\sall\sin\swindow\s.*$/u","Select all in window",$instruction);
	$instruction =  preg_replace("/Set\soutput\sCsound\sfile\s.*$/u","Set output Csound file",$instruction);
	$instruction =  preg_replace("/Set\soutput\sMIDI\sfile\s.*$/u","Set output MIDI file",$instruction);
	$instruction =  preg_replace("/Set\srandom\sseed\s.*$/u","Set random seed",$instruction);
	$instruction =  preg_replace("/Set\sselection\s.*$/u","Set selection",$instruction);
	$instruction =  preg_replace("/Set\sVref\s.+$/u","Set Vref",$instruction);
	$instruction =  preg_replace("/Smooth\stime$/u","Smooth time",$instruction);
	$instruction =  preg_replace("/Striated\stime$/u","Striated time",$instruction);
	$instruction =  preg_replace("/Start\sstring\s.+$/u","Start string",$instruction);
	$instruction =  preg_replace("/Synchronize\sstart.*$/u","Synchronize start",$instruction);
	$instruction =  preg_replace("/Tell\s.*$/u","Tell",$instruction);
	$instruction =  preg_replace("/Tempo\s.*$/u","Tempo",$instruction);
	$instruction =  preg_replace("/Text\sColor\s.*$/u","Text Color",$instruction);
	$instruction =  preg_replace("/Tick\scycle\s.*$/u","Tick cycle",$instruction);
	$instruction =  preg_replace("/Time\sbase$/u","Time base",$instruction);
	$instruction =  preg_replace("/Time\sticks\s.*$/u","Time ticks",$instruction);
	$instruction =  preg_replace("/Time\sresolution\s.*$/u","Time resolution",$instruction);
	$instruction =  preg_replace("/Time\ssetting\sstep\s.*$/u","Time setting step",$instruction);
	$instruction =  preg_replace("/Use\sbuffer\slimit\s.*$/u","Use buffer limit",$instruction);
	$instruction =  preg_replace("/Use\seach\substitution.*$/u","Use each substitution",$instruction);
	$instruction =  preg_replace("/Wait\s.*$/u","Wait",$instruction);
	$instruction =  preg_replace("/Csound\sscore.*$/u","Csound score",$instruction);
	$instruction =  preg_replace("/Csound\strace.*$/u","Csound trace",$instruction);
	$instruction =  preg_replace("/Cyclic\splay.*$/u","Cyclic play",$instruction);
	$instruction =  preg_replace("/Freeze\swindows.*$/u","Freeze windows",$instruction);
	$instruction =  preg_replace("/Run\sscript\s.*$/u","Run script",$instruction);
	return $instruction;
	}

function getOS() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform  = "Unknown OS Platform";
    $os_array     = array(
         '/windows nt 10/i'      =>  'Windows 10',
         '/windows nt 6.3/i'     =>  'Windows 8.1',
         '/windows nt 6.2/i'     =>  'Windows 8',
         '/windows nt 6.1/i'     =>  'Windows 7',
         '/windows nt 6.0/i'     =>  'Windows Vista',
         '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
         '/windows nt 5.1/i'     =>  'Windows XP',
         '/windows xp/i'         =>  'Windows XP',
         '/windows nt 5.0/i'     =>  'Windows 2000',
         '/windows me/i'         =>  'Windows ME',
         '/win98/i'              =>  'Windows 98',
         '/win95/i'              =>  'Windows 95',
         '/win16/i'              =>  'Windows 3.11',
         '/macintosh|mac os x/i' =>  'Mac OS X',
         '/mac_powerpc/i'        =>  'Mac OS 9',
         '/linux/i'              =>  'Linux',
         '/ubuntu/i'             =>  'Ubuntu',
         '/iphone/i'             =>  'iPhone',
         '/ipod/i'               =>  'iPod',
         '/ipad/i'               =>  'iPad',
         '/android/i'            =>  'Android',
         '/blackberry/i'         =>  'BlackBerry',
         '/webos/i'              =>  'Mobile'
          );
    foreach($os_array as $regex => $value)
        if(preg_match($regex,$user_agent))
            $os_platform = $value;
    return $os_platform;
	}

function linux_system() {
	$os_platform = getOS();
	if((is_integer(strpos($os_platform,"Linux")) OR is_integer(strpos($os_platform,"Ubuntu")))) return TRUE;
	}

function mac_system() {
	$os_platform = getOS();
	if(is_integer(strpos($os_platform,"Mac OS"))) return TRUE;
	}

function windows_system() {
	$os_platform = getOS();
	if(PHP_OS == "WINNT" OR is_integer(strpos($os_platform,"Windows"))) return TRUE;
	return FALSE;
	}
	
function send_to_console($command) {
	global $test,$pid,$bp_application_path,$console;
	$table = array();
//	$command .= " > /dev/null 2>&1"; // This makes it possible to get the pid.
//	$command .= " 2>&1"; // Redirect stderr to stdout to capture all output
	if(windows_system()) {
		$command = windows_command($command);
//		echo "<small>Windows: exec = <font color=\"red\">".str_replace('^','',$command)."</font></small><br />";
		}
//	echo "command = ".$command."<br />";
	exec($command,$table,$return_var);
//	echo "Return status: ".$return_var."\n";
    $pid = 0;
 /*   if(!windows_system()) {
        $command = "pgrep -f ".$bp_application_path.$console;
        $pid = exec($command);
        } */
	$_SESSION['pid'] = $pid;
/*	$reply = shell_exec($command);
	echo $reply;
	return $table; */
	// system($command,$o);
	// return $o;
//	passthru($command,$o);
	return $table;
	}

function windows_command($command) {
    global $console, $absolute_application_path;
  //  echo "cmd1 = ".$command."<br />";
	$command = str_replace("..".SLASH,$absolute_application_path,$command);
	$command = str_replace("../",$absolute_application_path,$command);
 //   echo "cmd2 = ".$command."<br />";
	$command = "cmd /c ".$command;
	$command = escapeshellcmd($command);
	$command = preg_replace("'(?<!^) '","^ ",$command);
	return $command;
	}

function get_orchestra_filename($csound_file) {
	$csound_orchestra = '';
	$content = @file_get_contents($csound_file,TRUE);
	if($content != FALSE) {
		$extract_data = extract_data(FALSE,$content);
		$content = $extract_data['content'];
		$content_no_br = str_replace("<br>",chr(10),$content);
		$table = explode(chr(10),$content_no_br);
		$imax_file = count($table);
		$number_channels = $table[0];
		$csound_orchestra = $table[$number_channels + 1];
		}
	return $csound_orchestra;
	}

function get_name_mi_file($this_file) {
	$objects_file = '';
	$content = @file_get_contents($this_file,TRUE);
	if($content != FALSE) {
		$extract_data = extract_data(TRUE,$content);
		$objects_file = $extract_data['objects'];
		}
	return $objects_file;
	}

function MIDIfiletype($file) {
//	return 1;
	$test = TRUE;
	$MIDIfiletype = -1;	
	$fp = fopen($file,'rb');
	if(!$fp) return $MIDIfiletype;
	$i = 0;
	while(!feof($fp)) {
	    // Read the file in chunks of 16 bytes
	    $data = fread($fp,16);
	    $arr = unpack("C*",$data);
	    foreach($arr as $key => $value) {
	 	  	if($i == 9) {
	    		$MIDIfiletype = intval($value); // break;
	    		}
	    	if($test) {
		 		if($value > 63) $value = chr($value);
			  //  echo $i.") ".$key." = " .$value." = ". ord($value)."<br />";
		    	}
			$i++; 
	    	}
	    if($MIDIfiletype  >= 0) break;
		}
	fclose($fp);
	return $MIDIfiletype;
	}

function copyemz($file1,$file2){
	$contentx = @file_get_contents($file1);
	$openedfile = fopen($file2, "w");
	fwrite($openedfile, $contentx);
	fclose($openedfile);
	if($contentx === FALSE) {
		$status = false;
		}
	else $status = true;
	return $status;
    }
    
function save_settings($variable,$value) {
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
	if($content <> FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer($pos=strpos($line,"$".$variable))) {
				$line = preg_replace("/=\s?\".*\"\s?;/u","= \"".$value."\";",$line);
				$found = TRUE;
				}
			$new_table[$i] = $line;
			}
		if(!$found) {
			$line = "§".$variable." = \"".$value."\";";
			$line = str_replace('§','$',$line);
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	else echo "<p><font color=\"red\">File ‘_settings.php’ could nor be opened!</p>";
 	return;
 	}

function save_settings2($variable,$index,$value) {
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
	if(!is_integer($index)) $index = '"'.$index.'"';
	if($content <> FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer(strpos($line,"$".$variable)) AND is_integer(strpos($line,"[".$index."]"))) {
				$line = preg_replace("/=\s?\".*\"\s?;/u","= \"".$value."\";",$line);
				$found = TRUE;
				}
			$new_table[$i] = $line;
			}
		if(!$found) {
			$line = "§".$variable."[".$index."] = \"".$value."\";";
			$line = str_replace('§','$',$line);
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	else echo "<p><font color=\"red\">File ‘_settings.php’ could nor be opened!</p>";
	return;
	}

function save_settings3($variable,$index1,$index2,$value) {
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
	if(!is_integer($index1)) $index1 = '"'.$index1.'"';
	if(!is_integer($index2)) $index2 = '"'.$index2.'"';
	if($content <> FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer(strpos($line,"$".$variable)) AND is_integer(($pos1=strpos($line,"[".$index1."]"))) AND is_integer(($pos2=strpos($line,"[".$index2."]"))) AND $pos2 > $pos1) {
				$line = preg_replace("/=\s?\".*\"\s?;/u","= \"".$value."\";",$line);
				$found = TRUE;
				}
			$new_table[$i] = $line;
			}
		if(!$found) {
			$line = "§".$variable."[".$index1."][".$index2."] = \"".$value."\";";
			$line = str_replace('§','$',$line);
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	else echo "<p><font color=\"red\">File ‘_settings.php’ could nor be opened!</p>";
	return;
	}
 	
 function delete_settings($file) {
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
 	if($content != FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if(strlen($line) == 0) continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer($pos=strpos($line,"[\"".$file."\"]"))) continue;
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
 		}
 	return;
 	}

function delete_settings_entry($entry) {
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
 	if($content != FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if(strlen($line) == 0) continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer($pos=strpos($line,$entry.' ')) AND $pos == 1) continue;
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
 		}
 	return;
 	}

function display_console_state() {
	global $bp_application_path, $absolute_application_path, $panicfile, $filename, $url_this_page, $console;
	 echo "<div style=\"display: flex; align-items: center; float: right; background-color: white; padding: 6px; border-radius: 6px;\">";
	 echo "<img src=\"pict/BP3-logo.png\" style=\"width: 100px;\"/>";  // Corrected CSS for width
	 echo "<span style=\"margin-left: 6px;\">";
	 $output = check_console();
	 if($output <> '') {
		echo "Bol Processor ‘<font color=\"blue\"><b>".$console."</b></font>’ console is responding<br />Version ".$output;
		$panicfile = str_replace(SLASH,'/',$panicfile);
		if(isset($filename) AND $filename <> "Compilation" AND $filename <> "Produce" AND  $filename <> "Bol Processor") echo "<div style=\"text-align:right;\"><button type=\"button\" class=\"bouton\" style=\"font-size: small;\" onclick=\"createFile('".$panicfile."');\">PANIC!</button></div>\n";
		}
	 else {
		echo "Bol Processor is not yet installed or not responding&nbsp;😣<br />";
		if(check_installation()) {
			$link = "compile.php";
			echo "Source files of BP3 have been found. You can (re)compile it.<br />";
			if(!check_gcc()) if(windows_system()) echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding.<br />You first need to <a target=\"_blank\" href=\"https://bolprocessor.org/install-mingw/\">install and set up MinGW</a>.";
				else echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding. You need to install<br />the <a target=\"_blank\" href=\"https://www.cnet.com/tech/computing/install-command-line-developer-tools-in-os-x/\">command line developer tools</a> or <a target=\"_blanl\" href=\"https://developer.apple.com/support/xcode/\">Xcode</a>.";
			else echo "👉&nbsp;&nbsp;<a onclick=\"window.open('".$link."','trace','width=800,height=800'); return false;\"  href=\"".$link."\">Click to run the compiler</a>, then <a href=\"".$url_this_page."\">reload this page</a>.";
			}
		else
			echo "Some files are missing or misplaced.<br />👉&nbsp;&nbsp;Visit <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a><br />and follow instructions!";
	 	}
	 echo "</span>";
	 echo "</div>";
	 return;
	}

function check_installation() {
	global $bp_application_path;
	$source = $bp_application_path."source/BP3/ConsoleMain.c";
	if(!file_exists(file_link($source))) return FALSE;
	$make = $bp_application_path."Makefile"; // case-sensitive in Linux!
	if(!file_exists(file_link($make))) {
		echo "No ‘makefile’?<br />";
		return FALSE;
		}
	return TRUE;
	}

function file_link($link) {
	if(windows_system()) $link = str_replace('/',SLASH,$link);
	return $link;
	}

function check_console() {
	global $bp_application_path, $console;
	$command = $bp_application_path.$console." --short-version";
	if(windows_system()) $command = windows_command($command);
	exec($command,$table,$status);
	if($status == 0) $output = $table[0];
	else $output = '';
	return($output);
	}

function check_gcc() {
	global $bp_application_path;
	$command = "gcc --version";
	if(windows_system()) $command = windows_command($command);
	exec($command,$table,$status);
	if($status == 0) return TRUE;
	else return FALSE;
	}

function link_to_tonality() {
	echo "<p><font color=\"red\">➡</font>&nbsp;<a target=\"_blank\" href=\"index.php?path=tonality_resources\">TONALITY resource folder</a></p>";
	echo "<hr style=\"border: 8px solid GhostWhite;\">";
	}

function check_csound() {
    global $csound_path,$csound_name, $csound_resources, $path, $url_this_page, $file_format,$programFiles;
	$this_file = "csound_version.txt";
    @unlink($this_file);
	$command = "\"".$programFiles.SLASH.$csound_path.SLASH.$csound_name."\" --version 2>csound_version.txt";
	if(file_exists($programFiles.SLASH.$csound_path.SLASH.$csound_name)) {
        send_to_console($command);
		}
    if(!file_exists($this_file)) {
		echo "&nbsp;&nbsp;&nbsp;<small><font color=\"red\">".$csound_path.SLASH.$csound_name."</font></small>";
		if(isset($file_format)) echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
		echo "<p><img src=\"pict/logo_csound.jpg\" width=\"90px;\" style=\"vertical-align:middle;\" />&nbsp;is not responding<br />or its path (<font color= \"blue\">".$csound_path."</font>) is incorrect<br /><br />";
		echo "Name: <font color=\"green\">path_to_csound".SLASH." </font><input type=\"text\" name=\"csound_name\" size=\"14\" style=\"background-color:CornSilk;\" value=\"".$csound_name."\"><br />";
		echo "Path: <input type=\"text\" name=\"csound_path\" size=\"30\" style=\"background-color:CornSilk;text-align:right;\" value=\"".$csound_path."\"><font color=\"green\">".SLASH.$csound_name."</font>";
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\"  name=\"csound_path_change\" value=\"TRY\">";
		echo "<br />";
		if(linux_system()) {
			echo "<br /><font color=\"red\">➡</font>&nbsp;Read instructions in the <a target=\"_blank\" href=\"https://bolprocessor.org/misc/linux-scripts.zip\">script folder</a> to install Csound<br />";
			}
		else {
			echo "<br /><font color=\"red\">➡</font>&nbsp;<a target=\"_blank\" href=\"https://csound.com/download.html\">Follow this link</a> to install Csound<br />";
			}
	    $result = FALSE;
		}
	else {
		$version = '';
		if(file_exists($this_file)) {
			$content = @file_get_contents($this_file,TRUE);
			$table = explode(chr(10),$content);
			$imax = count($table);
			for($i = 0; $i < $imax; $i++) {
				$line = trim($table[$i]);
				if(!is_integer(strpos($line,"Csound version "))) continue;
				$version = "(".preg_replace("/.+version\s(.+)\(.+/u",'$1',$line).")&nbsp;";
				break;
				}
			}
		echo "<p style=\"vertical-align:middle;\"><img src=\"pict/logo_csound.jpg\" width=\"90px;\" style=\"vertical-align:middle;\" />".$version."is responding<br />";
		$result = TRUE;
		}
	if($path <> $csound_resources) echo "<font color=\"red\">➡</font>&nbsp;<a target=\"_blank\" href=\"index.php?path=csound_resources\">CSOUND resource folder</a>";
	echo "</p>";
	return $result;
	}

function is_connected() {
	$connected = @fsockopen("www.midijs.net",80);
	if($connected) {
	fclose($connected);
	return TRUE;
	}
	else return FALSE;
	}

function relocate_function_table($dir,$line) {
	// Look for mention of external file in a ‘f1’ statement of Csound function table
	global $dir_csound_resources;
	$line = preg_replace("/\s+/u"," ",$line);
	$clean_line = preg_replace("/f\s([0-9])/u","f$1",$line);
	$table = explode(' ',$clean_line);
	if(count($table) < 5) return $clean_line;
	$p3 = abs(intval($table[3]));
	if($p3 <> 1 AND $p3 <> 23 AND $p3 <> 28 AND $p3 <> 49)
		// Doc: https://www.csounds.com/manual/html/ScoreGenRef.html
		return $clean_line;
	$filename = str_replace('"','',$table[4],$count);
	if($count > 0 AND file_exists($dir.$filename))
		rename($dir.$filename,$dir_csound_resources.$filename);
	return $clean_line;
	}

function check_function_tables($dir,$csound_file) {
	if($csound_file == '') return;
	$content = @file_get_contents($dir.$csound_file,TRUE);
	if($content == '') return;
	$table = explode(chr(10),$content);
	$imax = count($table);
	$found = FALSE;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		if($line == "_begin tables") {
			$found = TRUE; continue;
			}
		if($found) relocate_function_table($dir,$line);
		}
	return;
	}
	
function html_to_text($text,$type) {
	if($type == "textarea") $return = "\n";
	else $return = "<br />";
	$text = preg_replace("/<\/?html>/u",'',$text);
	$text = preg_replace("/<br\s?\/?>/u","§§§",$text);
	$text = str_replace('<','',$text);
	$text = str_replace('>','',$text);
	$text = str_replace("§§§",$return,$text);
	return $text;
	}

function popup_link($image_name,$text,$image_width,$image_height,$left_margin,$link) {
	$link = str_replace("#","_",$link);
	$popup_link = "<a onclick=\"window.open('".$link."','".$image_name."','toolbar=no,scrollbars=yes,resizable=yes,width=".$image_width.",height=".$image_height.",left=".$left_margin."'); return false;\" href=\"".$link."\">".$text."</a>";
	return $popup_link;
	}

function add_instruction($instruction,$content) {
	// Add an instruction to a grammar or data
	$instruction = trim($instruction);
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == $instruction) return $content;
		}
	$new_table = array();
	$done = FALSE;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$new_table[] = $line;
		if($line == '') continue;
		if((!is_integer($pos=strpos($line,"//")) OR $pos > 0) AND !$done) {
			$new_table[] = $instruction;
			$done = TRUE;
			}
		}
	$content = implode(chr(10),$new_table);
	return $content;
	}

function get_tonality_file($csound_instruments_file) {
	$tonality_filename = '';
	if(!file_exists($csound_instruments_file)) return $tonality_filename;
	$content = @file_get_contents($csound_instruments_file,TRUE);
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line <> "_end tables") continue;
		if($i < ($imax - 1)) {
			$line = trim($table[$i + 1]);
			if((is_integer($pos=strpos($line,"-to.")) AND $pos == 0) OR (is_integer(strpos($line,".bpto"))))
				$tonality_filename = $line;
			else $tonality_filename = '';
			}
		else $tonality_filename = '';

		}
	return $tonality_filename;
	}

function get_csound_file($objects_file) {
	$csound_file = '';
	if(!file_exists($objects_file)) return $csound_file;
	$content = @file_get_contents($objects_file,TRUE);
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if((is_integer(strpos($line,"-cs."))) OR (is_integer(strpos($line,".bpto")))) {
			$csound_file= $line; break;
			}
		}
	return $csound_file;
	}

function show_instruments_and_scales($dir,$objects_file,$content,$url_this_page,$filename,$file_format) {
	global $dir_csound_resources,$dir_tonality_resources,$csound_file,$tonality_file;
	if($objects_file <> '') {
		$new_csound_file = get_csound_file($dir.$objects_file);
		$new_csound_file = str_replace("csound_resources/",'',$new_csound_file);
		if($new_csound_file <> '' AND $new_csound_file <> $csound_file) {
			echo "<p></p><div style=\"background-color:cornsilk; padding:1em; width:500px; box-shadow: 3px 3px 5px 6px gold; border-radius: 6px;\">";
			if($csound_file == '') {
				$content = add_instruction($new_csound_file,$content);
				$csound_file = $new_csound_file;
				echo "👉&nbsp;&nbsp;Found mention of <font color=\"blue\">‘".$csound_file."’</font> in sound-object file  <br /><font color=\"blue\">‘".$objects_file."’</font>. This indication has been added to the project.<br /><font color=\"red\">You need to</font> <input style=\"background-color:yellow; font-size:large;\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".$filename."’\">";
				}
			else {
				echo "👉&nbsp;&nbsp;<font color=\"red\">WARNING:</font> File <font color=\"blue\">‘".$objects_file."’</font> indicates that the Csound instruments file <font color=\"blue\">‘".$new_csound_file."’</font> should be associated.<br />This project selects <font color=\"blue\">‘".$csound_file."’</font> instead, <font color=\"red\">which we will use.</font><br />➡ Your can edit <font color=\"blue\">‘".$objects_file."’</font> to solve the inconsistency.";
				}
			echo "</div>";
			}
		}
	if($csound_file <> '') {
		$tonality_file_in_csound = get_tonality_file($dir_csound_resources.$csound_file);
		if($tonality_file_in_csound <> '' AND $tonality_file_in_csound <> $tonality_file) {
			echo "<p></p><div style=\"background-color:cornsilk; padding:1em; width:500px; box-shadow: 3px 3px 5px 6px gold; border-radius: 6px;\">";
			if($tonality_file == '') {
				$content = add_instruction($tonality_file_in_csound,$content);
				$tonality_file = $tonality_file_in_csound;
				echo "👉&nbsp;&nbsp;File <font color=\"blue\">‘".$csound_file."’</font> indicates that tonality file <font color=\"blue\">‘".$tonality_file_in_csound."’</font><br />should be associated. This indication has been added to the project.<br /><font color=\"red\">You need to</font> <input style=\"background-color:yellow; font-size:large;\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".$filename."’\">";
				}
			else {
				echo "👉&nbsp;&nbsp;<font color=\"red\">WARNING:</font> File <font color=\"blue\">‘".$csound_file."’</font> indicates that tonality file<br /><font color=\"blue\">‘".$tonality_file_in_csound."’</font> should be associated.<br />This project selects <font color=\"blue\">‘".$tonality_file."’</font> instead, <font color=\"red\">which we will use.</font><br />Your can edit <font color=\"blue\">‘".$csound_file."’</font> to solve the inconsistency.";
				}
			echo "</div>";
			}
		}
	if($tonality_file <> '') {
		$list_of_tonal_scales = list_of_tonal_scales($dir_tonality_resources.$tonality_file);
		if(($max_scales = count($list_of_tonal_scales)) > 0) {
			if($max_scales > 1)  {
				$i = 0;
				echo "<p style=\"margin-bottom:0px;\">Tonality resource file <font color=\"blue\">‘".$tonality_file."’</font> contains definitions of tonal scales&nbsp;<font color=\"red\">➡</font>&nbsp;<button style=\"background-color:aquamarine; border-radius: 6px; font-size:large;\" onclick=\"togglescales(); return false;\">Show/hide tonal scales</button>";
				echo "<div id=\"scales\"  style=\"border-radius: 15px; padding:6px;\"><br />";
				}
			else {
				echo "<p style=\"margin-bottom:0px;\">Tonal resource file <font color=\"blue\">‘".$tonality_file."’</font> contains the definition of tonal scale:";
				echo "<div>";
				}
			echo "<ul style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_scale = 1; $i_scale <= $max_scales; $i_scale++)
				echo "<li>".$list_of_tonal_scales[$i_scale - 1]."</li>";
			if($max_scales > 1) echo "</ul><br />These scales may be called in “_scale(name of scale, blockkey)” instructions (with blockey = 0 by default)";
			else echo "</ul>This scale may be called in a “_scale(name of scale, blockkey)” instruction (with blockey = 0 by default)<br />➡ Use “_scale(0,0)” to force equal-tempered";
			echo "</div>";
			echo "</p>";
			}
		}
	else if($csound_file <> '') {
		echo "<p>Csound instruments have been loaded but scales cannot be used because no tonality file ‘-to’ has been found.<br />";
		echo "➡ Instruction “_scale()” will be ignored</p>";
		}
	if($csound_file == '' AND $file_format == "csound") {
		echo "<p>Csound instruments have not been loaded (no ‘-cs’ file) although the file format is CSOUND.<br />";
		echo "➡ Instruction “_ins()” will be ignored</p>";
		}
	if($csound_file <> '') {
		$list_of_instruments = list_of_instruments($dir_csound_resources.$csound_file);
		$list = $list_of_instruments['list'];
		$list_index = $list_of_instruments['index'];
		if(($max_instr = count($list)) > 0) {
			echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of instrument(s):";
			echo "<ol style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_instr = 0; $i_instr < $max_instr; $i_instr++) {
				echo "<li><b>_ins(</b><font color=\"MediumTurquoise\">".$list[$i_instr]."</font><b>)</b>";
				echo " = <b>_ins(".$list_index[$i_instr].")</b>";
				$param_list = $list_of_instruments['param'][$i_instr];
				if(count($param_list) > 0) {
					echo " ➡ parameter(s) ";
					for($i_param = 0; $i_param < count($param_list); $i_param++) {
						echo " “<font color=\"MediumTurquoise\">".$param_list[$i_param]."</font>”";
						}
					}
				echo "</li>";
				}
			echo "</ol>";
			echo "</p>";
			}
		else if($file_format == "csound") echo "<p>Csound resource file <font color=\"blue\">‘".$csound_file."’</font> does not contain definitions of instrument(s). The default instrument <font color=\"blue\">‘0-default.orc’</font> will be used.</p>";
		}
	return $content;
	}

function list_of_tonal_scales($tonality_file) {
	global $dir_scale_images;
	$list = array();
	if(!file_exists($tonality_file)) return $list;
	$content = @file_get_contents($tonality_file,TRUE);
	$table = explode(chr(10),$content);
	$imax = count($table);
	$found = FALSE;
	for($i = $k = 0, $j = -1; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		if($line == "_begin tables") $found = TRUE;
		if($line == "_end tables") break;
		if($found) {
			if($line[0] == "\"") {
				$name_of_file = str_replace('"','',$line);
				$list[++$j] = "<font color=\"MediumTurquoise\">".$name_of_file."</font>";
				}
			if($line[0] == "/")
				$list[$j] .= " = <font color=\"darkmagenta\"><b>".str_replace("/",'',$line)."</b></font>";
			if($line[0] == "|") {
				$list[$j] .= " <font color=\"black\">baseoctave = ".str_replace("|",'',$line)."</font>";
				$clean_name_of_file = str_replace("#","_",$name_of_file);
				$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
				$dir_image = $dir_scale_images.$clean_name_of_file.".png";
				if(file_exists($dir_image)) {
					$k++; if($k > 10) $k = 0;
					$clean_name_of_file = str_replace("#","_",$name_of_file);
					$list[$j] .= " <font color=\"red\">➡</font>&nbsp;".popup_link($clean_name_of_file,"image",500,410,(100 * $k),$dir_image);
					}
				}
			}
		}
	return $list;
	}

function list_of_instruments($csound_instruments_file) {
	global $number_fields_csound_instrument, $number_midi_parameters_csound_instrument;
	$list_of_instruments['list'] = $list_of_instruments['index'] = $list_of_instruments['param'] = array();
//	echo "csound_instruments_file = ".$csound_instruments_file."<br />";
	if(!file_exists($csound_instruments_file)) return $list_of_instruments;
	$content = @file_get_contents($csound_instruments_file,TRUE);
	$extract_data = extract_data(FALSE,$content);
	$content = $extract_data['content'];
	$content_no_br = str_replace("<br>",chr(10),$content);
	$table = explode(chr(10),$content_no_br);
	$imax_file = count($table);
//	echo "imax_file = ".$imax_file."<br />";
	$number_channels = $table[0];
	$i = $number_channels;
	$CsoundOrchestraName = preg_replace("/<\/?html>/u",'',$table[++$i]);
	$number_instruments = $table[++$i];
	for($j = 0; $j < $number_instruments; $j++) {
		$CsoundInstrumentName[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
		$list_of_instruments['list'][$j] = str_replace(' ','_',$CsoundInstrumentName[$j]);
	//	echo "<br />CsoundInstrumentName = ".$CsoundInstrumentName[$j]."<br />";
		$InstrumentComment[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
		$argmax[$j] = $table[++$i];
		$list_of_instruments['index'][$j] = $table[++$i];
		$i += ($number_fields_csound_instrument - 1);
		$list_of_instruments['param'][$j] = array();
		$Instrument_ipmax = $table[++$i];
	//	echo $i." Instrument_ipmax = ".$Instrument_ipmax."<br />";
		for($ip = 0; $ip < $Instrument_ipmax; $ip++) {
			$Instrument_paramlist_name = preg_replace("/<\/?html>/u",'',$table[++$i]);
		//	echo $i." Instrument_paramlist_name = ".$Instrument_paramlist_name."<br />";
			$list_of_instruments['param'][$j][] = $Instrument_paramlist_name;
			$i += ($number_midi_parameters_csound_instrument + 1);
			}
		}
	return $list_of_instruments;
	}

function check_duplicate_name($dir,$file) {
	$dircontent = scandir($dir);
	foreach($dircontent as $some_file) {
		if($some_file == $file) return TRUE;
	//	echo $some_file."<br />".$file."<br />";
		}
	return FALSE;
	}

function modulo($a, $b) {
	$c = $a % $b;
	if($c < 0) $c +=  $b;
	return $c;
	}

function create_fraction($ratio) {
	$p = 10000 * $ratio;
	$q = 10000;
	$gcd = gcd($p,$q);
	$result['p'] = $p / $gcd;
	$result['q'] = $q / $gcd;
	return $result;
	}
	
function simplify_fraction_eliminate_schisma($p,$q) {
	if(($p * $q) <> 0) {
		$gcd = gcd($p,$q);
		$p = $p / $gcd;
		$q = $q / $gcd;
		if($p == 2048 AND $q == 2025) { // 1.0113
			$p = 81; // 1.0125
			$q = 80;
			}
		if($p == 20480 AND $q == 19683) { // 1.0405
			$p = 50; // 1.04166 = REb-1
			$q = 48;
			}
		if($p == 135 AND $q == 128) { // 1.05468
			$p = 256; // 1.0535 = R1
			$q = 243;
			}
		if($p == 2187 AND $q == 2048) { // 1.0678
			$p = 16; // 1.0666 = R2
			$q = 15;
			} 
		if($p == 65536 AND $q == 59049) { // 1.1098
			$p = 10; // 1.111 = R3
			$q = 9;
			} 
		if($p == 4096 AND $q == 3645) { // 1.1237
			$p = 9; // 1.125 = R4
			$q = 8;
			}
		if($p == 729 AND $q == 640) { // 1.139
			$p = 256; // 1.138 RE+1
			$q = 225;
			}
		if($p == 2560 AND $q == 2187) { // 1.170
			$p = 75; // 1.172 = MIb-1
			$q = 64;
			} 
		if($p == 1215 AND $q == 1024) { // 1.1865
			$p = 32; // 1.1851 = G1
			$q = 27;
			} 
		if($p == 19683 AND $q == 16384) { // 1.2013 
			$p = 6; // 1.2 = G2
			$q = 5;
			}
		if($p == 8192 AND $q == 6561) { // 1.248 
			$p = 5; // 1.25 = G3
			$q = 4;
			}
		if($p == 512 AND $q == 405) { // 1.264
			$p = 81; // 1.126 = G4
			$q = 64;
			}
		if($p == 6561 AND $q == 5120) { // 1.281
			$p = 32; // 1.28 = MI+1
			$q = 25;
			}
		if($p == 177147 AND $q == 131072) { // 1.351
			$p = 27; // 1.35 = M2
			$q = 20;
			} 
		if($p == 81920 AND $q == 59049) { // 1.387
			$p = 325; // 1.388 = P1 - 1 syntonic comma
			$q = 234;
			} 
		if($p == 325 AND $q == 234) { // 1.3888
			$p = 25; // 1.388 = P1 - 1 syntonic comma
			$q = 18;
			}
		 if($p == 1024 AND $q == 729) { // 1.404 
			$p = 45; // 1.406 = P1
			$q = 32;
			}
		if($p == 729 AND $q == 512) { // 1.4238
			$p = 64; // 1.422 = M4
			$q = 45;
			}
		if($p == 59049 AND $q == 40960) { // 1.441
			$p = 36; // 1.440 = M4 + 1 syntonic comma
			$q = 25;
			}
		if($p == 262144 AND $q == 177147) { // 1.4798
			$p = 40; // 1.481 = P3
			$q = 27;
			}
		if($p == 16384 AND $q == 10935) { // 1.498
			$p = 3; // 1.5 = P4
			$q = 2;
			}
		if($p == 10240 AND $q == 6561) { // 1.56073
			$p = 25; // 1.5625 = LAb-1
			$q = 16;
			}
		if($p == 6561 AND $q == 4096) { // 1.6018
			$p = 8; // 1.6 = D2
			$q = 5;
			}
		if($p == 32768 AND $q == 19683) { // 1.664
			$p = 5; // 1.666 = D3
			$q = 3;
			}
		if($p == 2187 AND $q == 1280) { // 1.708
			$p = 128; // 1.706 = LA+1
			$q = 75;
			}
		if($p == 2048 AND $q == 1215) { // 1.7679
			$p = 27; // 1.6875 = D4
			$q = 16;
			}
		if($p == 1280 AND $q == 729) { // 1.756
			$p = 225; // 1.758 = SIb-1
			$q = 128;
			}
		if($p == 59049 AND $q == 32768) { // 1.802
			$p = 9; // 1.8 = N2
			$q = 5;
			}
		if($p == 4096 AND $q == 2187) { // 1.8728
			$p = 15; // 1.875 = N3
			$q = 8;
			}
		if($p == 256 AND $q == 135) { // 1.8962
			$p = 243; // 1.8984 = N4
			$q = 128;
			}
		if($p == 19683 AND $q == 10240) { // 1.922
			$p = 48; // 1.92 = SI+1
			$q = 25;
			}
		}
	$result['p'] = $p;
	$result['q'] = $q;
	return $result;
	}

function update_series($p,$q,$series) {
	if(($p * $q) <> 0) {
		while(modulo($p,2) == 0) $p = $p / 2;
		while(modulo($q,2) == 0) $q = $q / 2;
		while(modulo($p,3) == 0) $p = $p / 3;
		while(modulo($q,3) == 0) $q = $q / 3;
		if($p == 1 AND $q == 1) {
			$series = "p";
			}
		else {
			while(modulo($p,5) == 0) $p = $p / 5;
			while(modulo($q,5) == 0) $q = $q / 5;
			if($p == 1 AND $q == 1) {
				$series = "h";
				}
			}
		}
	return $series;
	}
	
function get_fraction($number,$serie) {
	global $p_fract,$q_fract,$ratio_fract,$serie_fract;
	$imax = count($ratio_fract);
	$fraction['found'] = FALSE;
	$fraction['p'] = $fraction['q'] = 0;
	if($number == 0) return $fraction;
	for($i = 0; $i < $imax; $i++) {
		if($serie <> '' AND $serie_fract[$i] <> $serie) continue;
		$dif = abs($number - $ratio_fract[$i]) / $number;
		if($dif < 0.001) {
			$fraction['p'] = $p_fract[$i];
			$fraction['q'] = $q_fract[$i];
			$fraction['found'] = TRUE;
			return $fraction;
			} 
		}
	return $fraction;
	}

function cents($ratio) {
	if($ratio < 0) return '';
	$cents = 1200 * log($ratio) / log(2);
	return $cents;
	}

function assign_default_keys($name,$basekey,$numgrades_fullscale) {
	$found = FALSE;
	if(count($name) > 0) {
		for($j = $kk = 0; $j < count($name); $j++) {
			$this_note = $name[$j];
			if($this_note == '' OR $this_note == '•') $key[$j] = 0;
			else {
				$k = note_position($this_note);
				if($j == (count($name) - 1)) $k = 12;
				if($k >= 0) {
					$key[$j] = $basekey + $k;
					$found = TRUE;
					}
				else $key[$j] = $basekey + $kk;
				$kk++;
				}
			}
		}
	if(!$found) for($k = 0; $k <= $numgrades_fullscale; $k++) {
		$key[$k] = $basekey + $k;
		}
	return $key;
	}

function note_position($this_note) {
	global $Indiannote,$AltIndiannote,$Englishnote,$AltEnglishnote,$Frenchnote,$AltFrenchnote;
	if(($kfound = array_search($this_note,$Indiannote)) !== FALSE) $k = $kfound;
	else if(($kfound = array_search($this_note,$AltIndiannote)) !== FALSE) $k = $kfound;
	else if(($kfound = array_search($this_note,$Englishnote)) !== FALSE) $k = $kfound;
	else if(($kfound = array_search($this_note,$AltEnglishnote)) !== FALSE) $k = $kfound;
	else if(($kfound = array_search($this_note,$Frenchnote)) !== FALSE) $k = $kfound;
	else if(($kfound = array_search($this_note,$AltFrenchnote)) !== FALSE) $k = $kfound;
	else $k = -1;
	return $k;
	}
	
function list_of_good_positions($interval,$p_comma,$q_comma,$syntonic_comma) {
	$list = array();
	$cents_interval = cents($interval);
	if(($p_comma * $q_comma) <> 0) $comma = cents($p_comma/$q_comma);
	else $comma = $syntonic_comma;
	if($comma < 1) {
		echo "<p style=\"color:red;\">ERROR: comma < 1 </p>";
		return $list ;
		}
	// echo $cents_interval." ".$comma;
	$i = 0;
	$list[$i++] = 0;
	$fraction = 1; $i0 = $i;
	while(TRUE) {
		$fraction = $fraction * 3 / 2;
		$position = cents($fraction);
		while($position < 0) $position += $cents_interval;
		while($position > $cents_interval) $position -= $cents_interval;
		$list[$i++] = $position;
	//	echo $i.") ".round($position)."<br >";
		$dif1 = abs($position - $comma);
		if($dif1 < ($comma / 2) OR ($i - $i0) >= 6) break;
		$dif2 = abs($position - $cents_interval + $comma);
		if($dif2 < ($comma / 2)) break;
		}
	echo "<br />";
	$fraction = 1; $i0 = $i;
	while(TRUE) {
		$fraction = $fraction * 2 / 3;
		$position = cents($fraction);
		while($position < 0) $position += $cents_interval;
		while($position > $cents_interval) $position -= $cents_interval;
		$list[$i++] = $position;
	//	echo $i.") ".round($position)."<br >";
		$dif1 = abs($position - $comma);
		if($dif1 < ($comma / 2) OR ($i - $i0) >= 6) break;
		$dif2 = abs($position - $cents_interval + $comma);
		if($dif2 < ($comma / 2)) break;
		}
	echo "<br />";
	$list[$i++] = $cents_interval;
	
	$i0 = $i;
	for($j = 0; $j < $i0; $j++) {
		$more = $list[$j] + $comma;
		if($more < $cents_interval) $list[$i++] = $more;
		$less = $list[$j] - $comma;
		if($less > 0) $list[$i++] = $less;
		}
	return $list;
	}
	
function merge_names($name1,$name2) {
	$table1 = explode("=",$name1);
	$table2 = explode("=",$name2);
	$table_merge = array();
	for($i = 0; $i < count($table1); $i++)
		if($table1[$i] <> '' AND $table1[$i] <> '•' AND !in_array($table1[$i],$table_merge)) $table_merge[] = $table1[$i];
	for($i = 0; $i < count($table2); $i++)
		if($table2[$i] <> '' AND $table2[$i] <> '•' AND !in_array($table2[$i],$table_merge)) $table_merge[] = $table2[$i];
	$result = implode("=",$table_merge);
	if($result == '') $result = '•';
	return $result;
	}

function simplify($fraction,$max_term) {
	$fraction = trim($fraction);
	$simplify['fraction'] = $fraction;
	$simplify['p'] = $fraction;
	$simplify['q'] = 1;
	$simplify['done'] = FALSE;
	if($max_term <= 0) return $simplify;
	if(!is_integer($pos=strpos($fraction,"/")) OR $pos == 0) {
		return $simplify;
		}
	$table = explode("/",$fraction);
	if(count($table) <> 2) return $simplify;
	$num = $table[0];
	$den = $table[1];
	$simplify['p'] = $num;
	$simplify['q'] = $den;
	if($num ==  0) {
		$simplify['fraction'] = "0";
		return $simplify;
		}
	if($den ==  0) return $simplify;
	else {
		$gcd = gcd($num,$den);
		$num = $num / $gcd;
		$den = $den / $gcd;
		$simplify['p'] = $num;
		$simplify['q'] = $den;
		if($den <> 1) $simplify['fraction'] = $num."/".$den;
		else $simplify['fraction'] = $num;
		$simplify['done'] = TRUE;
		}
	$the_max = 0;
	if($num > $max_term) $the_max = $num;
	if($den > $num AND $den > $max_term) $the_max = $den;
	if($the_max == 0) return $simplify;
	$ratio = $max_term / $the_max;
	$num = round($num * $ratio);
	$den = round($den * $ratio);
	if(($num * $den) ==  0) $fraction = '';
	else {
		$gcd = gcd($num,$den);
		$num = $num / $gcd;
		$den = $den / $gcd;
		if($den <> 1) $simplify['fraction'] = $num."/".$den;
		else $simplify['fraction'] = $num;
		}
	$simplify['p'] = $num;
	$simplify['q'] = $den;
	$simplify['fraction'] = $fraction;
	$simplify['done'] = TRUE;
	return $simplify;
	}

function add($p1,$q1,$p2,$q2) {
	$q3 = $q1 * $q2;
	$p3 = ($p1 * $q2) + ($p2 * $q1);
	if($p3 <> 0) {
		$gcd = gcd($p3,$q3);
		$p3 = $p3 / $gcd;
		$q3 = $q3 / $gcd;
		}
	$add['p'] = $p3;
	$add['q'] = $q3;
	return $add;
	}

function list_of_arguments($text,$instruction) {
	$list = array();
	$pos = 0;
	while(TRUE) {
		$newpos = strpos($text,$instruction,$pos);
		if($newpos === FALSE) break;
		$pos = $newpos + strlen($instruction);
		$pos2 = strpos($text,")",$pos);
		if($pos2 === FALSE) continue;
		$arg = substr($text,$pos,$pos2 - $pos);
		if(!in_array($arg,$list)) $list[] = $arg;
		}
	sort($list);
	return $list;
	}

function search_value($type,$data,$operator) {
	$pos1 = 0;
	$l = strlen($operator) + 1;
	$min = 127; $max = 0; $total = 0; $n = 0;
	while(is_integer($pos1=strpos($data,$operator."(",$pos1))) {
		if(!is_integer($pos2=strpos($data,")",$pos1 + $l))) break;
		$n++;
		$this_value = substr($data,$pos1 + $l,$pos2 - $pos1 - $l);
	//	echo $this_value."<br />";
		if($this_value < $min) $min = $this_value;
		if($this_value > $max) $max = $this_value;
		$total += $this_value;
		$pos1 = $pos2;
		}
//	echo $min." ".$max."<br />";
	if($type == "median") return round(($max + $min) / 2);
	if($type == "average") return round($total / $n);
	if($type == "min") return $min;
	if($type == "max") return $max;
	return $this_value;
	}

function quadratic_mapping($x1,$x2,$x3,$y1,$y2,$y3) {
	if((($x1*$x1 - $x2*$x2) * ($x1 - $x3) - ($x1*$x1 - $x3*$x3) * ($x1 - $x2)) == 0 OR $x1 == $x2) {
		$result['a'] = $result['b'] = $result['c'] = 0;
		$result['y_prime1'] = $result['y_prime3'] = -1;
		return $result;
		}
	$a = (($x1 - $x3) * ($y1 - $y2) - ($x1 - $x2) * ($y1 - $y3)) / (($x1*$x1 - $x2*$x2) * ($x1 - $x3) - ($x1*$x1 - $x3*$x3) * ($x1 - $x2));
	$b = (($y1 - $y2) - $a * ($x1*$x1 - $x2*$x2)) / ($x1 - $x2);
	$c = $y1 - ($a * $x1 * $x1) - $b * $x1;
	$y_prime1 = 2 * $a * $x1 + $b;
	$y_prime2 = 2 * $a * $x2 + $b;
	$y_prime3 = 2 * $a * $x3 + $b;
	$result['a'] = $a;
	$result['b'] = $b;
	$result['c'] = $c;
	$result['y_prime1'] = $y_prime1; // First derived is required to check that funnction is monotonous
	$result['y_prime2'] = $y_prime2;
	$result['y_prime3'] = $y_prime3;
	return $result;
	}

function create_grammar($data_path) {
	$grammar = '';
	$content = @file_get_contents($data_path,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content );
	$imax = count($table);
	$i_variable = 1; $first = TRUE;
	$all_variables = '';
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		$line = preg_replace("/\[[^\]]*\]/u",'',$line);
		$line = preg_replace("/^i[0-9].*/u",'',$line); // Csound note statement
		$line = preg_replace("/^f[0-9].*/u",'',$line); // Csound table statement
		$line = preg_replace("/^t[ ].*/u",'',$line); // Csound tempo statement
		$line = preg_replace("/^s\s*$/u",'',$line); // Csound "s" statement
		$line = preg_replace("/^e\s*$/u",'',$line); // Csound "e" statement
		if($line == '') continue;
		if(!is_integer($pos=strpos($line,"-")) OR $pos > 0) {
			$line = recode_entities($line);
			$new_item = TRUE;
			}
		else $new_item = FALSE;
		if($new_item) {
			if($first) $grammar .= "<br />S --> ALL_VARIABLES<br /><br />";
			$first = FALSE;
			$index = sprintf('%03d',$i_variable);
			$line = "M".$index." --> ".recode_tags($line);
			$all_variables .= "M".$index." ";
			$i_variable++;
			}
		else if(!$first) continue;
		$grammar .= $line."<br />";
		}
	$grammar = str_replace("ALL_VARIABLES",$all_variables,$grammar);
	return $grammar;
	}

function nature_of_time($value) {
	if($value == SMOOTH) return "SMOOTH";
	if($value == STRIATED) return "STRIATED";
	return '';
	}

function get_legato($c,$line,$pos) {
	if($c <> '_') return -1;
	if(is_integer($pos1=strpos($line,"_legato(",$pos)) AND $pos1 == $pos) {
		$pos2 = strpos($line,")",$pos1);
		$legato_value = substr($line,$pos1 + 8,$pos2 - $pos1 - 8);
		return $legato_value;
		}
	else return -1;
	}

function date_sort($a, $b) {
	if ($a['start'] == $b['start'])
		return 0; // They are equal
	return ($a['start'] < $b['start']) ? -1 : 1;
	}
	
function score_sort($a, $b) {
	if ($a['score'] == $b['score'])
		return 0; // They are equal
	return ($a['score'] < $b['score']) ? -1 : 1;
	}

function hidden_directory($name) {
	switch($name) {
		case "csound_resources":
		case "docs-developer":
		case "docs-release":
		case "icons":
		case "pictures":
		case "portmidi":
		case "php":
		case "source":
		case "midi_resources":
//		case "tonality_resources":
		case "temp_bolprocessor":
			return TRUE;
		}
	return FALSE;
	}

function add_proper_extension($format,$filename) {
	$filename = str_replace(".mid",'',$filename);
	$filename = str_replace(".sco",'',$filename);
	$filename = str_replace(".bpda",'',$filename);
	switch($format) {
		case "midi": $output_file = $filename.".mid"; break;
		case "csound": $output_file = $filename.".sco"; break;
		case "data": $output_file = $filename.".bpda"; break;
		case "rtmidi": $output_file = $filename; break;
		default: $output_file = $filename; break;
		}
	return $output_file;
	}

function emptydirectory($dir) {
	// This will delete the content, not the directory itself
	if(!is_dir($dir)) return false;
	deleteDirectory($dir,0);
	return true;
	}

function deleteDirectory($dir,$level) {
	$items = new DirectoryIterator($dir);
	foreach($items as $item) {
		// Skip the . and .. directories
		if ($item->isDot()) continue;
		if ($item->isDir()) 
			deleteDirectory($item->getPathname(),$level + 1);
		else 
			unlink($item->getPathname());
		}
	unset($items);
	if($level > 0) rmdir($dir);
	return;
	}

function find_replace_form() {
	global $url_this_page;
	echo "<div id=\"search\">";
	echo "<label for=\"find\">Search for: </label>";
	echo "<input type=\"text\" name=\"find\" style=\"background-color:white;\" id=\"find\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<label for=\"regex\">Check for regular expression:</label>";
	echo "<input type=\"checkbox\" name=\"regex\" id=\"regex\">";
	echo "</p>";
	echo "<p>";
	echo "<label for=\"replace\">and replace it with: </label>";
	echo "<input type=\"text\" name=\"replace\" style=\"background-color:white;\" id=\"replace\">&nbsp;&nbsp;&nbsp;<button class=\"bouton\" type=\"submit\" formaction=\"".$url_this_page."#replace\" name=\"action\" value=\"replace\" onclick=\"clearsave()\">Search and Replace (all)</button>";
	echo "</div>";
	return;
	}

function do_replace($content) {
	if(isset($_POST['replace'])) {
		$text = $_POST['thistext'] ?? '';
		$find = $_POST['find'] ?? '';
		$replace = $_POST['replace'] ?? '';
		$useRegex = isset($_POST['regex']);
		if($find == '') return $content;;
		if(!$useRegex) {
			// Standard replace (case-sensitive)
			$content = str_replace($find,$replace,$text);
			echo "<p>Text = “<font color=\"blue\">".$find."</font>” replaced by “<font color=\"blue\">".$replace."</font>”&nbsp;<font color=\"red\"> ➡ Don't forget to save!</font></p>";
			}
		else {
			// Replace using regex
			$pattern = '/'.$find.'/';
			$content = preg_replace($pattern,$replace,$text);
			echo "<p>Pattern = <font color=\"blue\">".$pattern."</font> should be replaced by <font color=\"blue\">".$replace."</font><font color=\"red\"> ➡ Don't forget to save!</font></p>";
			}
		}
	return $content;
	}

function set_output_folder($output_folder) {
	global $bp_application_path;
	if(isset($_POST['change_output_folder'])) {
		$output_folder = trim($_POST['output_folder']);
		$output_folder = str_replace('+','_',$output_folder);
		$output_folder = trim(str_replace(SLASH,' ',$output_folder));
		$output_folder = str_replace(' ',SLASH,$output_folder);
		if(!ok_output_location($output_folder,TRUE)) $output_folder = "my_output";
		save_settings("output_folder",$output_folder);
		}
	return $output_folder;
	}

function ok_output_location($folder,$talk) {
	global $output_folder;
	$result = TRUE;
	if(hidden_directory($folder)) $result = FALSE;
	if($folder == "csound_resources") $result = FALSE;
	if($folder == "midi_resources") $result = FALSE;
	if($folder == "resources") $result = FALSE;
	if($folder == "trash_bolprocessor") $result = FALSE;
	if($folder == "scripts") $result = FALSE;
	if(is_integer($pos=strpos($folder,"BP2-")) AND $pos == 0) $result = FALSE;
	if($folder == $output_folder AND !$talk) $result = FALSE;
	if(!$result AND $talk) echo "<p><font color=\"red\">ERROR:</font> Folder “<font color=\"blue\">".$folder."</font>” cannot be used for output files.</p>";
	return $result;
	}


function read_midiport($thefile) {
	global $MIDIinput,$MIDIoutput,$MIDIinputname,$MIDIinputcomment,$MIDIoutputname,$MIDIoutputcomment; // These are tables!
	global $MIDIacceptFilter, $MIDIpassFilter, $MIDIoutFilter, $MIDIchannelFilter; // These are tables!
	global $NumberMIDIinputs, $NumberMIDIoutputs;
	$result['found'] = false;
	if(file_exists($thefile)) {
		$NumberMIDIinputs = $NumberMIDIoutputs = 0;
		$file = fopen($thefile,'r');
		$foundin = $foundout = 0;
		if($file) {
			while(!feof($file)) {
				$line = fgets($file);
				$table = explode("\t",$line);
				if(count($table) < 3) continue;
				if(trim($table[0]) == "MIDIinput") {
					$NumberMIDIinputs++;
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						$MIDIinput[$i] = trim($table[2]);
						if(count($table) > 3) $MIDIinputname[$i] = trim($table[3]);
						if(count($table) > 4) $MIDIinputcomment[$i] = trim($table[4]);
						}
					}
				else if(trim($table[0]) == "MIDIoutput") {
					$NumberMIDIoutputs++;
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						$MIDIoutput[$i] = trim($table[2]);
						if(count($table) > 3) $MIDIoutputname[$i] = trim($table[3]);
						if(count($table) > 4) $MIDIoutputcomment[$i] = trim($table[4]);
						}
					}
				else if(trim($table[0]) == "MIDIacceptFilter") {
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						if(count($table) > 2) $MIDIacceptFilter[$i] = trim($table[2]);
						$foundin++;
						}
					}
				else if(trim($table[0]) == "MIDIpassFilter") {
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						if(count($table) > 2) $MIDIpassFilter[$i] = trim($table[2]);
						$foundin++;
						}
					}
				else if(trim($table[0]) == "MIDIoutFilter") {
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						if(count($table) > 2) $MIDIoutFilter[$i] = trim($table[2]);
						$foundout++;
						}
					}
				else if(trim($table[0]) == "MIDIchannelFilter") {
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						if(count($table) > 2) $MIDIchannelFilter[$i] = trim($table[2]);
						$foundout++;
						}
					}
				}
			fclose($file);
			if($NumberMIDIoutputs == 0) $NumberMIDIoutputs = 1;
			if($foundin > 1) for($i = 0; $i < $NumberMIDIinputs; $i++) convert_midi_input_filter_to_params($i);
			if($foundout > 0) for($i = 0; $i < $NumberMIDIoutputs; $i++) {
				convert_midi_output_filter_to_params($i);
				convert_midi_channel_filter_to_params($i);
				}
			$result['found'] = true;
			}
		}
	return $result;
	}

function read_midiressources() {
	global $filename, $temp_midi_ressources, $MIDIacceptFilter, $MIDIpassFilter;
	global $dir_midi_resources,$MIDIinput,$MIDIoutput,$MIDIinputname,$MIDIoutputname,$MIDIinputcomment,$MIDIoutputcomment,$NumberMIDIinputs;
	
	// First try to read  in the "temp" folder
	$result = read_midiport($temp_midi_ressources."midiport");
	// Then try the "midi_ressources" folder, which is permanent
	if(!$result['found'])
		$result = read_midiport($dir_midi_resources.$filename."_midiport");
	if(!$result['found']) {
		$NumberMIDIinputs = 0;
		$MIDIinput[0] = 1;
		$MIDIoutput[0] = 0;
		$MIDIinputname[0] = $MIDIoutputname[0] = $MIDIoutputcomment[0] = $MIDIinputcomment[0] = '';
		// In this case, no MIDI filters have been set. The form will set undefined parameters to value '1'.
		}
	return $result;
	}

function convert_midi_input_filter_to_params($i) {
	global $NoteOffFilter_in, $NoteOnFilter_in, $KeyPressureFilter_in, $ControlChangeFilter_in, $ProgramChangeFilter_in, $ChannelPressureFilter_in, $PitchBendFilter_in, $SystemExclusiveFilter_in, $TimeCodeFilter_in, $SongPositionFilter_in, $SongSelectFilter_in, $TuneRequestFilter_in, $EndSysExFilter_in, $TimingClockFilter_in, $StartFilter_in, $ContinueFilter_in, $ActiveSensingFilter_in, $SystemResetFilter_in;
	global $MIDIacceptFilter, $MIDIpassFilter;

	if(!isset($MIDIacceptFilter[$i]) || !isset($MIDIpassFilter[$i])) return;
	$midiacceptfilter = $MIDIacceptFilter[$i];
	$midipassfilter = $MIDIpassFilter[$i];
	if(strlen($midiacceptfilter) != 18 OR strlen($midipassfilter) != 18) return;

	$midiacceptfilter = $midiacceptfilter | $midipassfilter; // Any event allowed to go out should be allowed to get in
	$MIDIacceptFilter[$i] = $midiacceptfilter;
	$MIDIpassFilter[$i] = $midipassfilter;

	$NoteOffFilter_in[$i] = $midiacceptfilter[0] + $midipassfilter[0];
	$NoteOnFilter_in[$i] = $midiacceptfilter[1] + $midipassfilter[1];
	$KeyPressureFilter_in[$i] = $midiacceptfilter[2] + $midipassfilter[2];
	$ControlChangeFilter_in[$i] = $midiacceptfilter[3] + $midipassfilter[3];
	$ProgramChangeFilter_in[$i] = $midiacceptfilter[4] + $midipassfilter[4];
	$ChannelPressureFilter_in[$i] = $midiacceptfilter[5] + $midipassfilter[5];
	$PitchBendFilter_in[$i] = $midiacceptfilter[6] + $midipassfilter[6];
	$SystemExclusiveFilter_in[$i] = $midiacceptfilter[7] + $midipassfilter[7];
	$TimeCodeFilter_in[$i] = $midiacceptfilter[8] + $midipassfilter[8];
	$SongPositionFilter_in[$i] = $midiacceptfilter[9] + $midipassfilter[9];
	$SongSelectFilter_in[$i] = $midiacceptfilter[10] + $midipassfilter[10];
	$TuneRequestFilter_in[$i] = $midiacceptfilter[11] + $midipassfilter[11];
	$EndSysExFilter_in[$i] = $midiacceptfilter[12] + $midipassfilter[12];
	$TimingClockFilter_in[$i] = $midiacceptfilter[13] + $midipassfilter[13];
	$StartFilter_in[$i] = $midiacceptfilter[14] + $midipassfilter[14];
	$ContinueFilter_in[$i] = $midiacceptfilter[15] + $midipassfilter[15];
	$ActiveSensingFilter_in[$i] = $midiacceptfilter[16] + $midipassfilter[16];
	$SystemResetFilter_in[$i] = $midiacceptfilter[17] + $midipassfilter[17];
	return;
	}

function convert_midi_output_filter_to_params($i) {
	global $NoteOffFilter_out, $NoteOnFilter_out, $KeyPressureFilter_out, $ControlChangeFilter_out, $ProgramChangeFilter_out, $ChannelPressureFilter_out, $PitchBendFilter_out, $SystemExclusiveFilter_out, $TimeCodeFilter_out, $SongPositionFilter_out, $SongSelectFilter_out, $TuneRequestFilter_out, $EndSysExFilter_out, $TimingClockFilter_out, $StartFilter_out, $ContinueFilter_out, $ActiveSensingFilter_out, $SystemResetFilter_out;
	global $MIDIoutFilter;
	if(!isset($MIDIoutFilter[$i])) return;
	$midioutfilter = $MIDIoutFilter[$i];
	if(strlen($midioutfilter) != 18 OR strlen($midioutfilter) != 18) return;
	$NoteOffFilter_out[$i] = $midioutfilter[0];
	$NoteOnFilter_out[$i] = $midioutfilter[1];
	$KeyPressureFilter_out[$i] = $midioutfilter[2];
	$ControlChangeFilter_out[$i] = $midioutfilter[3];
	$ProgramChangeFilter_out[$i] = $midioutfilter[4];
	$ChannelPressureFilter_out[$i] = $midioutfilter[5];
	$PitchBendFilter_out[$i] = $midioutfilter[6];
	$SystemExclusiveFilter_out[$i] = $midioutfilter[7];
	$TimeCodeFilter_out[$i] = $midioutfilter[8];
	$SongPositionFilter_out[$i] = $midioutfilter[9];
	$SongSelectFilter_out[$i] = $midioutfilter[10];
	$TuneRequestFilter_out[$i] = $midioutfilter[11];
	$EndSysExFilter_out[$i] = $midioutfilter[12];
	$TimingClockFilter_out[$i] = $midioutfilter[13];
	$StartFilter_out[$i] = $midioutfilter[14];
	$ContinueFilter_out[$i] = $midioutfilter[15];
	$ActiveSensingFilter_out[$i] = $midioutfilter[16];
	$SystemResetFilter_out[$i] = $midioutfilter[17];
	return;
	}

function convert_midi_channel_filter_to_params($i) {
	global $channel_pass, $MIDIchannelFilter;
	for($channel = 1; $channel <= 16; $channel++) {
		$channel_pass[$i][$channel] = $MIDIchannelFilter[$i][$channel - 1];
		}
	return;	
	}

function get_parameter($param) {
	if(isset($_POST[$param])) {
		$variable = trim($_POST[$param]);
		return $variable;
		}
	return 0;
	}

function save_midiressources($filename) {
	global $MIDIinput, $MIDIoutput, $MIDIinputname, $MIDIoutputname, $MIDIinputcomment, $MIDIoutputcomment; // These are tables!
	global $dir_midi_resources, $temp_midi_ressources, $NumberMIDIinputs, $NumberMIDIoutputs, $MIDIchannelFilter;
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		if(isset($_POST["MIDIinput_".$i])) $MIDIinput[$i] = trim($_POST["MIDIinput_".$i]);
		if(isset($_POST["MIDIinputname_".$i])) $MIDIinputname[$i] = trim($_POST["MIDIinputname_".$i]);
		if(isset($_POST["MIDIinputcomment_".$i])) $MIDIinputcomment[$i] = trim($_POST["MIDIinputcomment_".$i]);
		}
	for($i = 0; $i < $NumberMIDIoutputs; $i++) {
		if(isset($_POST["MIDIoutput_".$i])) $MIDIoutput[$i] = trim($_POST["MIDIoutput_".$i]);
		if(isset($_POST["MIDIoutputname_".$i])) $MIDIoutputname[$i] = trim($_POST["MIDIoutputname_".$i]);
		if(isset($_POST["MIDIoutputcomment_".$i])) $MIDIoutputcomment[$i] = trim($_POST["MIDIoutputcomment_".$i]);
		$MIDIchannelFilter[$i] =  '';
		for($channel = 1; $channel <= 16; $channel++) {
			$varName = "channel_out_".$i."_".$channel;
			$MIDIchannelFilter[$i]  .= isset($_POST[$varName]) ? '1' : '0';
			}
//		echo "<br />channelFilters[".$i."] = ".$MIDIchannelFilter[$i] ."<br />";
		}
	$acceptFilters = $passFilters = array();
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		$NoteOffFilter_in = get_parameter("NoteOffFilter_in_".$i);
		$NoteOnFilter_in = get_parameter("NoteOnFilter_in_".$i);
		$KeyPressureFilter_in = get_parameter("KeyPressureFilter_in_".$i);
		$ControlChangeFilter_in = get_parameter("ControlChangeFilter_in_".$i);
		$ProgramChangeFilter_in = get_parameter("ProgramChangeFilter_in_".$i);
		$ChannelPressureFilter_in = get_parameter("ChannelPressureFilter_in_".$i);
		$PitchBendFilter_in = get_parameter("PitchBendFilter_in_".$i);
		$SystemExclusiveFilter_in = get_parameter("SystemExclusiveFilter_in_".$i);
		$TimeCodeFilter_in = get_parameter("TimeCodeFilter_in_".$i);
		$SongPositionFilter_in = get_parameter("SongPositionFilter_in_".$i);
		$SongSelectFilter_in = get_parameter("SongSelectFilter_in_".$i);
		$TuneRequestFilter_in = get_parameter("TuneRequestFilter_in_".$i);
		$EndSysExFilter_in = get_parameter("EndSysExFilter_in_".$i);
		$TimingClockFilter_in = get_parameter("TimingClockFilter_in_".$i);
		$StartFilter_in = get_parameter("StartFilter_in_".$i);
		$ContinueFilter_in = get_parameter("ContinueFilter_in_".$i);
		$ActiveSensingFilter_in = get_parameter("ActiveSensingFilter_in_".$i);
		$SystemResetFilter_in = get_parameter("SystemResetFilter_in_".$i);
		$sumsArray = [
			$NoteOffFilter_in, $NoteOnFilter_in, $KeyPressureFilter_in, $ControlChangeFilter_in,
			$ProgramChangeFilter_in, $ChannelPressureFilter_in, $PitchBendFilter_in, $SystemExclusiveFilter_in,
			$TimeCodeFilter_in, $SongPositionFilter_in, $SongSelectFilter_in, $TuneRequestFilter_in,
			$EndSysExFilter_in, $TimingClockFilter_in, $StartFilter_in, $ContinueFilter_in,
			$ActiveSensingFilter_in, $SystemResetFilter_in
			];
		$acceptFilters[$i] = $passFilters[$i] = '';
		foreach ($sumsArray as $index => $sum) {
			if ($sum == 0) {
				$acceptFilters[$i] .= '0';
				$passFilters[$i] .= '0';
			} elseif ($sum == 1) {
				$acceptFilters[$i] .= '1';  // Assume input is 1 by default
				$passFilters[$i] .= '0';
			} elseif ($sum == 2) {
				$acceptFilters[$i] .= '1';
				$passFilters[$i] .= '1';
				}
			}
		// Pad the binary strings to ensure they are 18 digits long
		$acceptFilters[$i] = str_pad($acceptFilters[$i], 18, '0', STR_PAD_LEFT);
		$passFilters[$i] = str_pad($passFilters[$i], 18, '0', STR_PAD_LEFT);
		}
	$outFilters = array();
	for($i = 0; $i < $NumberMIDIoutputs; $i++) {
		$NoteOffFilter_out = get_parameter("NoteOffFilter_out_".$i);
		$NoteOnFilter_out = get_parameter("NoteOnFilter_out_".$i);
		$KeyPressureFilter_out = get_parameter("KeyPressureFilter_out_".$i);
		$ControlChangeFilter_out = get_parameter("ControlChangeFilter_out_".$i);
		$ProgramChangeFilter_out = get_parameter("ProgramChangeFilter_out_".$i);
		$ChannelPressureFilter_out = get_parameter("ChannelPressureFilter_out_".$i);
		$PitchBendFilter_out = get_parameter("PitchBendFilter_out_".$i);
		$SystemExclusiveFilter_out = get_parameter("SystemExclusiveFilter_out_".$i);
		$TimeCodeFilter_out = get_parameter("TimeCodeFilter_out_".$i);
		$SongPositionFilter_out = get_parameter("SongPositionFilter_out_".$i);
		$SongSelectFilter_out = get_parameter("SongSelectFilter_out_".$i);
		$TuneRequestFilter_out = get_parameter("TuneRequestFilter_out_".$i);
		$EndSysExFilter_out = get_parameter("EndSysExFilter_out_".$i);
		$TimingClockFilter_out = get_parameter("TimingClockFilter_out_".$i);
		$StartFilter_out = get_parameter("StartFilter_out_".$i);
		$ContinueFilter_out = get_parameter("ContinueFilter_out_".$i);
		$ActiveSensingFilter_out = get_parameter("ActiveSensingFilter_out_".$i);
		$SystemResetFilter_out = get_parameter("SystemResetFilter_out_".$i);
		$sumsArray = [
			$NoteOffFilter_out, $NoteOnFilter_out, $KeyPressureFilter_out, $ControlChangeFilter_out,
			$ProgramChangeFilter_out, $ChannelPressureFilter_out, $PitchBendFilter_out, $SystemExclusiveFilter_out,
			$TimeCodeFilter_out, $SongPositionFilter_out, $SongSelectFilter_out, $TuneRequestFilter_out,
			$EndSysExFilter_out, $TimingClockFilter_out, $StartFilter_out, $ContinueFilter_out,
			$ActiveSensingFilter_out, $SystemResetFilter_out
			];
		$outFilters[$i] = '';
		foreach ($sumsArray as $index => $sum) {
			if($sum == 0) $outFilters[$i] .= '0';
			elseif ($sum == 1) $outFilters[$i] .= '1';  // Assume value is 1 by default
			}
		// Pad the binary strings to ensure they are 18 digits long
		$outFilters[$i] = str_pad($outFilters[$i], 18, '0', STR_PAD_LEFT);
		if($NoteOffFilter_out <> $NoteOnFilter_out)
			echo "<p id=\"refresh\" style=\"color:red;\">👉  Warning: NoteOn and NoteOff should have the same status in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
		if($SystemExclusiveFilter_out <> $EndSysExFilter_out)
			echo "<p id=\"refresh\" style=\"color:red;\">👉  Warning: SystemExclusive and EndSysEx should have the same status in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
	/*		if($StartFilter_out <> $ContinueFilter_out)
				echo "<p id=\"refresh\" style=\"color:red;\">👉  Warning: Start and Continue should have the same status in the filter of MIDI output ".$MIDIoutput[$i]."</p>"; */
		}
	save_midiport($temp_midi_ressources."midiport",$acceptFilters,$passFilters,$outFilters);
	save_midiport($dir_midi_resources.$filename."_midiport",$acceptFilters,$passFilters,$outFilters);
	return;
	}

function save_midiport($thisfilename,$acceptFilters,$passFilters,$outFilters) {
	global $MIDIinput, $MIDIoutput, $MIDIinputname, $MIDIinputcomment, $MIDIoutputname, $MIDIoutputcomment; // These are tables!
	global $NumberMIDIinputs, $NumberMIDIoutputs, $MIDIchannelFilter;
	$file = fopen($thisfilename,'w');
	if($file) {
		for($i = 0; $i < $NumberMIDIoutputs; $i++) {
			if($MIDIoutput[$i] == '' AND $MIDIoutputname[$i] == '' AND $MIDIoutputcomment[$i] == '') continue;
			if($MIDIoutput[$i] == '') $MIDIoutput[$i] = 0;
			if($MIDIoutputname[$i] == '') $MIDIoutputname[$i] = "new output";
			if($MIDIoutputcomment[$i] == '') $MIDIoutputcomment[$i] = "void";
			fwrite($file,"MIDIoutput\t".$i."\t".$MIDIoutput[$i]."\t".$MIDIoutputname[$i]."\t".$MIDIoutputcomment[$i]."\n");
			if(isset($outFilters[$i])) fwrite($file,"MIDIoutFilter\t".$i."\t".$outFilters[$i]."\n");
			if(isset($MIDIchannelFilter[$i][$i])) fwrite($file,"MIDIchannelFilter\t".$i."\t".$MIDIchannelFilter[$i]."\n");
			}
		for($i = 0; $i < $NumberMIDIinputs; $i++) {
			if($MIDIinput[$i] == '' AND $MIDIinputname[$i] == '' AND $MIDIinputcomment[$i] == '') continue;
			if($MIDIinput[$i] == '') $MIDIinput[$i] = -1;
			if($MIDIinputname[$i] == '') $MIDIinputname[$i] = "new intput";
			if($MIDIinputcomment[$i] == '') $MIDIinputcomment[$i] = "void";
			fwrite($file,"MIDIinput\t".$i."\t".$MIDIinput[$i]."\t".$MIDIinputname[$i]."\t".$MIDIinputcomment[$i]."\n");
			if(isset($acceptFilters[$i])) fwrite($file,"MIDIacceptFilter\t".$i."\t".$acceptFilters[$i]."\n");
			if(isset($passFilters[$i])) fwrite($file,"MIDIpassFilter\t".$i."\t".$passFilters[$i]."\n");
			}
		fclose($file);
		chmod($thisfilename,0777);
		return true;
		}
	return false;
	}

function filter_form_input($i) {
	global $NoteOffFilter_in, $NoteOnFilter_in, $KeyPressureFilter_in, $ControlChangeFilter_in, $ProgramChangeFilter_in, $ChannelPressureFilter_in, $PitchBendFilter_in, $SystemExclusiveFilter_in, $TimeCodeFilter_in, $SongPositionFilter_in, $SongSelectFilter_in, $TuneRequestFilter_in, $EndSysExFilter_in, $TimingClockFilter_in, $StartFilter_in, $ContinueFilter_in, $ActiveSensingFilter_in, $SystemResetFilter_in, $MIDIinput;
	echo "<div id=\"showhide_input".$i."\"  style=\"background-color: Snow; width:300px;\">";
	echo "<p style=\"margin-left:12px;\"><b>Filter for MIDI input ".$MIDIinput[$i]."</b></p>";
	echo "<p style=\"margin-left:12px;\">0 = reject<br />1 = treat<br />2 = treat + pass</p>";
	echo "<table class=\"no-border-spacing\">";
	echo "<tr>";
	echo "<td>";
	echo "</td>";
	echo "<td style=\"white-space:nowrap;\">";
	echo "<b>&nbsp;0&nbsp;-&nbsp;1&nbsp;-&nbsp;2</b>";
	echo "</td>";
	echo "</tr>";
	filter_buttons_in($i,"NoteOff");
	filter_buttons_in($i,"NoteOn");
	filter_buttons_in($i,"KeyPressure");
	filter_buttons_in($i,"ControlChange");
	filter_buttons_in($i,"ProgramChange");
	filter_buttons_in($i,"ChannelPressure");
	filter_buttons_in($i,"PitchBend");
	filter_buttons_in($i,"SystemExclusive");
	filter_buttons_in($i,"TimeCode");
	filter_buttons_in($i,"SongPosition");
	filter_buttons_in($i,"SongSelect");
	filter_buttons_in($i,"TuneRequest");
	filter_buttons_in($i,"EndSysEx");
	filter_buttons_in($i,"TimingClock");
	filter_buttons_in($i,"Start");
	filter_buttons_in($i,"Continue");
	filter_buttons_in($i,"ActiveSensing");
	filter_buttons_in($i,"SystemReset");
	echo "</table>";
	filter_input_explanation();
	echo "</div>";
	}


function filter_form_output($i) {
	global $NoteOffFilter_out, $NoteOnFilter_out, $KeyPressureFilter_out, $ControlChangeFilter_out, $ProgramChangeFilter_out, $ChannelPressureFilter_out, $PitchBendFilter_out, $SystemExclusiveFilter_out, $TimeCodeFilter_out, $SongPositionFilter_out, $SongSelectFilter_out, $TuneRequestFilter_out, $EndSysExFilter_out, $TimingClockFilter_out, $StartFilter_out, $ContinueFilter_out, $ActiveSensingFilter_out, $SystemResetFilter_out;
	global $url_this_page, $MIDIoutput;
	echo "<div id=\"showhide_output".$i."\"  style=\"background-color: Snow; width:300px;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"savemidiport\" value=\"SAVE MIDI ports\">";
	echo "<p style=\"margin-left:12px;\"><b>Filter for MIDI output ".$MIDIoutput[$i]."</b></p>";
	echo "<table style=\"background-color:azure;\">";
	echo "<tr>";
	echo "<td>";
	echo "<table class=\"no-border-spacing\">";
	echo "<tr>";
	echo "<td>";
	echo "</td>";
	echo "<td style=\"white-space:nowrap;\">";
	echo "<p><b>&nbsp;off&nbsp;&nbsp;on</b></p>";
	echo "</td>";
	echo "</tr>";
	filter_buttons_out($i,"NoteOff");
	filter_buttons_out($i,"NoteOn");
	filter_buttons_out($i,"KeyPressure");
	filter_buttons_out($i,"ControlChange");
	filter_buttons_out($i,"ProgramChange");
	filter_buttons_out($i,"ChannelPressure");
	filter_buttons_out($i,"PitchBend");
	filter_buttons_out($i,"SystemExclusive");
	filter_buttons_out($i,"TimeCode");
	filter_buttons_out($i,"SongPosition");
	filter_buttons_out($i,"SongSelect");
	filter_buttons_out($i,"TuneRequest");
	filter_buttons_out($i,"EndSysEx");
	filter_buttons_out($i,"TimingClock");
	filter_buttons_out($i,"Start");
	filter_buttons_out($i,"Continue");
	filter_buttons_out($i,"ActiveSensing");
	filter_buttons_out($i,"SystemReset");
	echo "<tr><td>&nbsp;</td><td></td></tr>";
	echo "</table>";
	echo "</td>";
	echo "<td style=\"white-space:nowrap;\">";
	echo "<p>Channels</p>";
	for($channel = 1; $channel <=  16; $channel++) {
		filter_channel($i,$channel);
		}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	filter_output_explanation();
	echo "</div>";
	}

function filter_channel($i,$channel) {
	global $channel_pass;
	$the_post = "channel_out_".$i."_".$channel;
	echo "<br /><input type=\"checkbox\" name=\"".$the_post."\" value=\"1\"";
	if(!isset($channel_pass[$i][$channel]) OR $channel_pass[$i][$channel]) echo " checked";
	echo " />";
	echo $channel;
	}

function filter_buttons_out($i,$param) {
	$tablename = $param."Filter_out";
	global $$tablename;
	if(!isset($$tablename[$i])) $$tablename[$i] = 1;
	echo "<tr>";
	echo "<td style=\"font-size:small;\">";
	echo $param;
	echo "</td>";
	echo "<td style=\"font-size:small; white-space:nowrap;\">";
	echo "<input onchange=\"tellsave()\" type=\"radio\" name=\"".$tablename."_".$i."\" value=\"0\"";
	if($$tablename[$i] == "0") echo " checked";
	echo ">";
	echo "<input onchange=\"tellsave()\" type=\"radio\" name=\"".$tablename."_".$i."\" value=\"1\"";
	if($$tablename[$i] == "1") echo " checked";
	echo ">";
	echo "</td>";
	echo "</tr>";
	return;
	}

function filter_buttons_in($i,$param) {
	$tablename = $param."Filter_in";
	global $$tablename;
	if(!isset($$tablename[$i])) $$tablename[$i] = 1;
	echo "<tr>";
	echo "<td style=\"font-size:small;\">";
	echo $param;
	echo "</td>";
	echo "<td style=\"font-size:small; white-space:nowrap;\">";
	echo "<input onchange=\"tellsave()\" type=\"radio\" name=\"".$tablename."_".$i."\" value=\"0\"";
	if($$tablename[$i] == "0") echo " checked";
	echo ">";
	echo "<input onchange=\"tellsave()\" type=\"radio\" name=\"".$tablename."_".$i."\" value=\"1\"";
	if($$tablename[$i] == "1") echo " checked";
	echo ">";
	echo "<input onchange=\"tellsave()\" type=\"radio\" name=\"".$tablename."_".$i."\" value=\"2\"";
	if($$tablename[$i] == "2") echo " checked";
	echo ">";
	echo "</td>";
	echo "</tr>";
	return;
	}

function filter_input_explanation() {
	global $file_format;
	if($file_format <> "rtmidi") return;
	echo "<p>MIDI input filters are used to process incoming events, for example from a connected<br />piano keyboard or other MIDI devices — including another instance of the BP3.</p>
	<ul>
	<li>By default (option ‘0’), events are ‘rejected’ and nothing happens.</li>
	<li>If an event is accepted (option ‘1’), it can trigger a script command<br />declared in the score.</li>
	<li>Accepted events can also be routed to the current MIDI output (option ‘3’).</li>
	<li>Some settings might be changed by the console for consistency.</li>
	</ul>";
	}

function filter_output_explanation() {
	global $file_format;
	if($file_format <> "rtmidi") return;
	echo "<p>The event filter specifies which MIDI events can be sent to this output.</p>";
	echo "<p>The channel filter specifies which MIDI channels can be sent to this output.</p>";
	}

function display_midi_ports($filename) {
	global $MIDIoutput, $MIDIoutputname, $MIDIoutputcomment, $MIDIinput, $MIDIinputname, $MIDIinputcomment, $NumberMIDIinputs, $NumberMIDIoutputs, $url_this_page;
	$midiport = read_midiressources($filename);
	if(isset($_POST['create_input'])) {
		if($NumberMIDIinputs > 31) {
			echo "<p id=\"timespan2\" style=\"color:red;\">You can't have more than 32 inputs!</p>";
			}
		else {
		//	echo "<p id=\"timespan2\" style=\"color:red;\">Created an input!</p>";
			$MIDIinput[$NumberMIDIinputs] = -1;
			$MIDIinputname[$NumberMIDIinputs] = "new input";
			$MIDIinputcomment[$NumberMIDIinputs] = "";
			$NumberMIDIinputs++;
			}
		}
	if(isset($_POST['create_output'])) {
		if($NumberMIDIoutputs > 31) {
			echo "<p id=\"timespan2\" style=\"color:red;\">You can't have more than 32 inputs!</p>";
			}
		else {
		//	echo "<p id=\"timespan2\" style=\"color:red;\">Created an output!</p>";
			$MIDIoutput[$NumberMIDIoutputs] = -1;
			$MIDIoutputname[$NumberMIDIoutputs] = "new output";
			$MIDIoutputcomment[$NumberMIDIoutputs] = "";
			$NumberMIDIoutputs++;
			for($i = 0; $i < $NumberMIDIoutputs; $i++) {
				for($channel = 1; $channel <= 16; $channel++) $channel_pass[$i][$channel] = 1;
				}
			}
		}
	echo "<input type=\"hidden\" name=\"NumberMIDIinputs\" value=\"".$NumberMIDIinputs."\">";
	echo "<input type=\"hidden\" name=\"NumberMIDIoutputs\" value=\"".$NumberMIDIoutputs."\">";
	$text_over_number = "Port number";
	if(linux_system()) $text_over_number = "Client number";
	$text_over_name = "Name of device";
	$text_over_comment = "Your comment";
	for($i = 0; $i < $NumberMIDIoutputs; $i++) {
		if($MIDIoutput[$i] == -1) $value = '';
		else $value = $MIDIoutput[$i];
		if(!isset($MIDIoutputcomment[$i]) OR $MIDIoutputcomment[$i] == "void") $comment = '';
		else $comment = $MIDIoutputcomment[$i];
		echo "MIDI output&nbsp;&nbsp;<input type=\"text\" title=\"".$text_over_number."\" onchange=\"tellsave()\" name=\"MIDIoutput_".$i."\" size=\"2\" value=\"".$value."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_name."\" style=\"margin-bottom:6px;\" onchange=\"tellsave()\" name=\"MIDIoutputname_".$i."\" size=\"15\" value=\"".$MIDIoutputname[$i]."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_comment."\" name=\"MIDIoutputcomment_".$i."\" size=\"15\" value=\"".$comment."\">";
		echo "&nbsp;<button type=\"button\" onclick=\"clearFields('MIDIoutput_".$i."','MIDIoutputname_".$i."','MIDIoutputcomment_".$i."')\">Delete</button>";
		echo "&nbsp;<button style=\"background-color:azure; border-radius: 6px;\" onclick=\"toggledisplay_output(".$i."); return false;\">FILTER</button>";
		filter_form_output($i);
		echo "<br />";
		}
	echo "<input style=\"float:right; color:DarkBlue; backgroundsave_-color:yellow;\" type=\"submit\" name=\"create_output\" value=\"Add an output\"><br />";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"savemidiport\" value=\"SAVE MIDI ports\">";
	echo str_replace(' ',"&nbsp;"," 👉 Delete name if changing number")."<br /><br />";
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		if($MIDIinput[$i] == -1) $value = '';
		else $value = $MIDIinput[$i];
		if(!isset($MIDIinputcomment[$i]) OR $MIDIinputcomment[$i] == "void") $comment = '';
		else $comment = $MIDIinputcomment[$i];
		echo "MIDI input&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"text\" title=\"".$text_over_number."\" onchange=\"tellsave()\" name=\"MIDIinput_".$i."\" size=\"2\" value=\"".$value."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_name."\" style=\"margin-bottom:6px;\" onchange=\"tellsave()\" name=\"MIDIinputname_".$i."\" size=\"15\" value=\"".$MIDIinputname[$i]."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_comment."\" name=\"MIDIinputcomment_".$i."\" size=\"15\" value=\"".$comment."\">";
		echo "&nbsp;<button type=\"button\" onclick=\"clearFields('MIDIinput_".$i."', 'MIDIinputname_".$i."', 'MIDIinputcomment_".$i."')\">Delete</button>";
		echo "&nbsp;<button style=\"background-color:azure; border-radius: 6px;\" onclick=\"toggledisplay_input(".$i."); return false;\">FILTER</button>";
		filter_form_input($i);
		echo "<br />";
		}
	echo "<input style=\"float:right; color:DarkBlue; backgroundsave_-color:yellow;\" type=\"submit\" name=\"create_input\" value=\"Add an input\">";
	return;
	}

function my_session_id() {
    $originalSessionId = session_id();
    if(empty($originalSessionId)) {
        session_start();
        $originalSessionId = session_id();
        }
    $hashedSessionId = md5($originalSessionId);
    $shortSessionId = substr($hashedSessionId,0,10);
    return $shortSessionId;
    }

function create_variables($script_variables) {
    $h_variables = fopen($script_variables,'w');
    fwrite($h_variables,"<?php\n");
    $script_status = $script_more = array();
    $content = @file_get_contents("script-instructions.txt",TRUE);
    if($content) {
        $table = explode(chr(10),$content);
        $imax = count($table);
        for($i = 0; $i < $imax; $i++) {
            $line = trim($table[$i]);
            if($line == '') continue;
            $table2 = explode(chr(9),$line);
            $instruction = $table2[0];
            $status = $table2[1];
            if(isset($table2[2])) $more = $table2[2];
            else $more = '';
            $script_status[$instruction] = $status;
            store($h_variables,"script_status[\"".$instruction."\"]",$status);
            $script_more[$instruction] = $more;
            store($h_variables,"script_more[\"".$instruction."\"]",$more);
            }
        ksort($script_status);
        ksort($script_more);
        }
    $line = "§>\n";
    $line = str_replace('§','?',$line);
    fwrite($h_variables,$line);
    fclose($h_variables);
    return;
    }

function copyDirectory($src,$dst) {
    if (!is_dir($src)) return false;
    // Create the destination directory if it doesn't exist
    if (!is_dir($dst)) mkdir($dst, 0777, true);
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if ($file != '.' && $file != '..') {
            $srcFilePath = $src . DIRECTORY_SEPARATOR . $file;
            $dstFilePath = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcFilePath))
                copyDirectory($srcFilePath, $dstFilePath);
            else copy($srcFilePath, $dstFilePath);
			}
		}

    // Close the directory
    closedir($dir);
    return true;
	}

function update_scale_with_kbm($scl_name,$scale_file,$kbm_content) {
	global $dir_scales,$need_to_save;
	if($scale_file == '') {
		$scl_name = preg_replace("/\s+/u",' ',$scl_name);
		$scl_name = str_replace("#","_",$scl_name);
		$scl_name = str_replace("/","_",$scl_name);
		$scale_file = $dir_scales.$scl_name.".txt";
		$need_to_save = TRUE;
		}
	$scale_content = file_get_contents($scale_file,TRUE);
	$table_scl = explode(chr(10),$scale_content);
	$imax = count($table_scl);
	$scale_file = "temp_scale_file.txt";
	$handle = fopen($dir_scales.$scale_file,"w");
	$new_mapping = FALSE;
	$note_names = ''; $numgrades_fullscale = 0;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table_scl[$i]);
		if(is_integer($pos=strpos($line,"/")) AND $pos == 0) {
			// Note names
			$note_names = preg_replace("/\s+/u",' ',$line);
			$note_names = str_replace('/','',$note_names);
			continue;
			}
		if(is_integer($pos=strpos($line,"f")) AND $pos == 0) {
			// The Csound line
			$line = preg_replace("/\s+/u",' ',$line);
			$table_line = explode(' ',$line);
			$numgrades_fullscale = $table_line[4];
			$basefreq = $table_line[6];
			$basenote = $table_line[7];
	//		echo "<p>".$numgrades_fullscale."  ".$basefreq."   ".$basenote."</p>";
			// Let's find these parameters in the KBM file
			$table_kbm = explode(chr(10),$kbm_content);
			$jmax = count($table_kbm);
			for($j = 0; $j < $jmax; $j++) {
				$line_kbm = trim($table_kbm[$j]);
				if(is_integer($pos=strpos($line_kbm,"!")) AND $pos == 0) {
					if(is_integer($pos=stripos($line_kbm,"! Size"))) {
						$j++;
						$line_kbm = trim($table_kbm[$j]);
						if(!ctype_digit($line_kbm) OR intval($line_kbm) < 2) {
							return "Size of map (".$line_kbm.") is not a valid integer<br />";
							}
						if(intval($line_kbm) <> $numgrades_fullscale) {
							return "Size of map (".$line_kbm.") does not match the number of grades in the SCL file (".$numgrades_fullscale.")<br />";
							}
						continue;
						}
					if(is_integer($pos=stripos($line_kbm,"! Scale degree"))) {
						$j++;
						$line_kbm = trim($table_kbm[$j]);
						if(!ctype_digit($line_kbm) OR intval($line_kbm) < 2) {
							return "Scale degree to consider as formal octave (".$line_kbm.") is not a valid integer<br />";
							}
						if(intval($line_kbm) <> $numgrades_fullscale) {
							return "Scale degree to consider as formal octave (".$line_kbm.") does not match the number of grades in the SCL file (".$numgrades_fullscale.")<br />";
							}
						continue;
						}
					if(is_integer($pos=stripos($line_kbm,"! Middle note"))) {
						$j++;
						$line_kbm = trim($table_kbm[$j]);
						if(!ctype_digit($line_kbm) OR intval($line_kbm) < 2) {
							return "Middle note (".$line_kbm.") is not a valid integer<br />";
							}
						$basenote = intval($line_kbm);
						continue;
						}
					if(is_integer($pos=stripos($line_kbm,"! Frequency to"))) {
						$j++;
						$line_kbm = trim($table_kbm[$j]);
						if(!is_numeric($line_kbm) OR $line_kbm < 2) {
							return "Frequency (".$line_kbm.") is not a valid number<br />";
							}
						$frequency_kbm = number_format($line_kbm,2);
						continue;
						}
					if(is_integer($pos=stripos($line_kbm,"! Reference note"))) {
						$j++;
						$line_kbm = trim($table_kbm[$j]);
						if(!ctype_digit($line_kbm) OR intval($line_kbm) < 2) {
							return "Reference note (".$line_kbm.") is not a valid integer<br />";
							}
						$reference_note_kbm = intval($line_kbm);
						continue;
						}
					if(is_integer($pos=stripos($line_kbm,"! Mapping"))) {
						$line_notes = "/";
						if($note_names <> '') {
							$table_notes = explode(' ',$note_names);
							for($k = 0; $k < count($table_notes); $k++) {
								$this_note[$k] = $table_notes[$k];
								}
							}
						for($k = 0; $k < $numgrades_fullscale; $k++) {
							$j++;
							if(isset($table_kbm[$j]) AND $table_kbm[$j] <> '')
 								$line_mapping = trim($table_kbm[$j]);
							else return "Empty line found in the mapping<br />";
							if(!ctype_digit($line_mapping) AND $line_mapping <> 'x') {
								return "Incorrect value (".$line_mapping.") found in the mapping<br />";
								}
							if(ctype_digit($line_mapping)) {
								$key_num = $basenote + intval($line_mapping);
								if($note_names <> '' AND $this_note[$k] <> '•') {
									$note = $this_note[$k];
									}
								else $note = "key#".$key_num;
								}
							else $note = "•";
							$line_notes .= $note." ";
						//	echo "note = ".$note."<br />";
							}
						if(isset($this_note[$k])) $line_notes .= $this_note[$k];
						else $line_notes .= "key#".($key_num + 1);
						$line_notes = $line_notes."/";
						$new_mapping = TRUE;
						fwrite($handle,$line_notes."\n");
						break;
						}
					}
				}
			// We need to interpret the frequency from the reference note in KBM for the base note in BP3
			if(isset($frequency_kbm) AND isset($reference_note_kbm)) {
				$basefreq = number_format($frequency_kbm * pow(2,($basenote - $reference_note_kbm) / 12),2);
			//	echo "<p>basefreq =  ".$basefreq."</p>";
				$table_line[6] = $basefreq;
				}
			$table_line[7] = $basenote;
			$line = implode(' ',$table_line);
			}
		fwrite($handle,$line."\n");
		}

	fclose($handle);
	$file_changed = $dir_scales."_changed";
	$handle = fopen($file_changed,"w");
	if($handle) fclose($handle);
	$kbm_error = '';
	return $kbm_error;
	}

function make_links_clickable($text) {
    $pattern = '/\b(?:https?|ftp):\/\/[a-zA-Z0-9-_.]+(?:\.[a-zA-Z0-9-_.]+)+(?:\/[^\s]*)?/';
    $text_with_links = preg_replace_callback($pattern, function($matches) {
        $url = $matches[0];
        return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
    	}, $text);
    return $text_with_links;
	}

?>
