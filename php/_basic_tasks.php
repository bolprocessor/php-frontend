<?php
// Comments "// note N" (in this file, data.php and _musicxml.php) refer to the numbered notes of the technical description of this algorithm: https://bolprocessor.org/tech-analysis-of-musicxml-php

// 2026-11-19 Numbers are written in their shortest form (1.122, not 1.1220000000000001) whatever the php.ini settings. Windows installations may set serialize_precision = 17, which json_encode() and var_export() follow, and precision = 17, which echo and string conversions follow - by Claude
ini_set('serialize_precision','-1');
ini_set('precision','14');

/* if(strtoupper(substr(PHP_OS,0,3)) === 'WIN' && isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'localhost') {
	// This avoids slowing down access on Windows machines
    header(
        'Location: http://127.0.0.1' . $_SERVER['REQUEST_URI'],
        true,
        302
    	);
    exit;	
	} */
// 2026-11-22 The session is started before "<!DOCTYPE html>" is sent, otherwise session_start() fails when output_buffering is off, as in the standalone application (PHP Desktop) - by Claude
session_start();
echo "<!DOCTYPE html>\n";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// error_reporting(0);
// error_reporting(E_ALL & ~E_NOTICE);
// ini_set('output_buffering','off');
// ini_set('zlib.output_compression', 0);
// ini_set("pcre.jit", "0"); 2025-02-11
require('midi.class.php'); // Required for prototype.php and timebase.php
// Source: https://github.com/robbie-cao/midi-class-php

define('MAXFILESIZE',50000000);
define('SLASH',DIRECTORY_SEPARATOR);
define('STRIATED',1);
define('SMOOTH',0);
define('MAXPARTS',30);
define('MB_CONVERT_OK',function_exists('mb_convert_encoding'));
define('MULTIBYTE_INTERNAL_OK', function_exists('mb_internal_encoding'));
define('MULTIBYTE_EREG_OK', function_exists('mb_ereg_replace'));
define('GD_AVAILABLE',function_exists('gd_info'));
$section_headers = array("RND","ORD","LIN","SUB","SUB1","TEM","POSLONG","LEFT","RIGHT","INIT:","TIMEPATTERNS:","DATA:","COMMENTS:");

$architecture = php_uname("m");

$i_am_designer = FALSE;
$test = FALSE;
// $test = TRUE;

$permissions = 0775;
$today_date = date("Y-m-d");
$reason_not_true =  '';
$true_bp_grammar = FALSE;
$grammar_has_structures = FALSE;

$file_path = "_settings.php";
if(!file_exists($file_path)) {
    // Create the file and write the PHP tags
    $file_content = "<?php\n\n?>";
    // Write the content to the file
    if(my_file_put_contents($file_path, $file_content) == FALSE) {
        echo "Failed to create the file '_settings.php'.";
		die();
        }
    else chmod($file_path,$permissions);
    }

$skin = 1; // Blue by default
$midi_player = "SpessaSynth"; // Off-line player in php/spessasynth/
// $midi_player = "MIDIjs";
// $midi_player = "html-midi-player";
$minimised_token = "MINIMISED";

require_once("_settings.php");
$midi_players = array("SpessaSynth","MIDIjs","html-midi-player");
if(!in_array($midi_player,$midi_players)) $midi_player = "SpessaSynth";
$bp_application_path = "..".SLASH;
$url_this_page = "_basic_tasks.php";
$absolute_application_path = str_replace("php".SLASH.$url_this_page,'',realpath(__FILE__));

if(isset($_POST['csound_path_change'])) {
	$new_name = trim($_POST['csound_name'] ?? '');
	if($new_name <> '') $csound_name = $new_name;
	$csound_path = rtrim(trim($_POST['csound_path'] ?? ''),"/\\");
	// Accept the full path of the program, e.g. "/Applications/Csound/csound"
	if(isset($csound_name) AND basename($csound_path) == $csound_name AND is_file($csound_path))
		$csound_path = dirname($csound_path);
	save_settings("csound_path",$csound_path);
	if(isset($csound_name) AND $csound_name <> '') save_settings("csound_name",$csound_name);
	}

// Csound: the Bol Processor requires CSOUND_MIN_VERSION or later, see check_csound() (2026-11-14)
define("CSOUND_MIN_VERSION","7.0");
// Csound resource file describing the instruments of "0-default.orc", attached by produce.php when a project names no '-cs' file (2026-11-18)
define("CSOUND_DEFAULT_RESOURCE","-cs.0-default");
// Real-time Csound: functions starting and stopping the Csound server (2026-11-16)
require_once("_csoundrt.php");
// Real-time SuperCollider: functions starting the server and loading the SynthDefs (2026-11-20)
require_once("_scrt.php");
// Real-time OSC (Max, Pd...): destination and project panel (2026-11-21)
require_once("_oscrt.php");
// $csound_path is the absolute path of the folder containing Csound on all systems
// $programFiles is kept empty for compatibility (it used to be the "Program Files" folder on Windows)
$programFiles = '';
if(!isset($csound_path)) $csound_path = '';
if(windows_system()) {
	$console = "bp.exe";
	if(!isset($csound_name) OR $csound_name == '') $csound_name = "csound.exe";
	$csound_path = str_replace('/',SLASH,$csound_path);
	// Older settings gave the path relative to "Program Files", e.g. "\Csound6_x64\bin"
	if($csound_path <> '' AND !preg_match('/^[A-Za-z]:/',$csound_path))
		$csound_path = windows_program_files().SLASH.ltrim($csound_path,SLASH);
	}
else if(linux_system()) {
	$console = "bp3";
	if(!isset($csound_name) OR $csound_name == '') $csound_name = "csound";
	}
else { // MacOS
	$console = "bp";
	if(!isset($csound_name) OR $csound_name == '') $csound_name = "csound";
	}
if($csound_path == '' OR !file_exists($csound_path.SLASH.$csound_name)) {
	// Csound is not where it was: look in its usual locations, Csound 7 first
	foreach(csound_candidate_dirs() as $dir) {
		if(file_exists($dir.SLASH.$csound_name)) {
			$csound_path = $dir;
			break;
			}
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
$max_sleep_time_after_bp_command = 300; // seconds. Maximum time waiting for the 'done.txt' file
$default_output_format = "midi";

if(!isset($output_folder) OR $output_folder == '') $output_folder = "my_output";

$maxchunk_size = 400; // Max number of measures contained in a chunk
$minchunk_size = 15; // Min number of measures contained in a chunk
// $minchunk_size = 1; // Use this value to check the chunking to single measures

$max_term_in_fraction = 32768; // Used to simplify fractions when importing MusicXML scores

$bad_settings = FALSE;

$oldmask = umask(0);
$temp_dir = $bp_application_path."temp_bolprocessor";
if(!file_exists($temp_dir)) {
	if (!mkdir($temp_dir, $permissions, true))
        error_log("Failed to create directory '{$temp_dir}' with error: " . error_get_last()['message']);
	else
        chmod($temp_dir, $permissions); // Double-check permissions
	}
$temp_dir .= SLASH;
@unlink($temp_dir."trace_notes_txt");
if(!file_exists($temp_dir."messages")) mkdir($temp_dir."messages",$permissions,true);
umask($oldmask);
$panicfile = $temp_dir."messages".SLASH."_panic";
$pausefile = $temp_dir."messages".SLASH."_pause";
$continuefile = $temp_dir."messages".SLASH."_continue";
$temp_dir_abs = $temp_dir;
if(windows_system()) $temp_dir_abs = str_replace("..".SLASH,$absolute_application_path,$temp_dir);
watchdog_start();

$MIDIinput = $MIDIoutput = array();
$MIDIinput[0] = -1;
$MIDIoutput[0] = 0;
$MIDIinputname = $MIDIoutputname = $MIDIoutputcomment = $MIDIinputcomment = array();
$MIDIinputname[0] = $MIDIoutputname[0] = $MIDIoutputcomment[0] = $MIDIinputcomment[0] = '';
$NumberMIDIoutputs = 1;
$NumberMIDIinputs = 0;
if(isset($_POST['NumberMIDIinputs'])) $NumberMIDIinputs = $_POST['NumberMIDIinputs'];
if(isset($_POST['NumberMIDIoutputs'])) $NumberMIDIoutputs = $_POST['NumberMIDIoutputs'];
$NoteOffFilter = $NoteOnFilter = $KeyPressureFilter = $ControlTypeFilter = $ProgramChangeFilter = $ChannelPressureFilter = $PitchBendFilter = $SystemExclusiveFilter = $TimeCodeFilter = $SongPositionFilter = $SongSelectFilter = $TuneRequestFilter = $EndSysExFilter = $TimingClockFilter = $StartFilter = $ContinueFilter = $ActiveSensingFilter = $SystemResetFilter = array();

if(!file_exists($bp_application_path.$csound_resources)) {
	mkdir($bp_application_path.$csound_resources);
	chmod($bp_application_path.$csound_resources,$permissions);
	}
$dir_csound_resources = $bp_application_path.$csound_resources.SLASH;
// 2026-11-19 The installation and update scripts replace the content of "csound_resources". Files created or changed by the user are kept in "csound_resources_private", which no script modifies. Files of "csound_resources" are "fixed": only the designer can change them ($i_am_designer). See csound_resource_dir() - by Claude
// 2026-11-20 These folders were called "my_csound_resources" and "my_tonality_resources" before
rename_private_resource_folder("my_csound_resources","csound_resources_private");
rename_private_resource_folder("my_tonality_resources","tonality_resources_private");
$csound_resources_private = "csound_resources_private";
if(!file_exists($bp_application_path.$csound_resources_private)) {
	mkdir($bp_application_path.$csound_resources_private);
	chmod($bp_application_path.$csound_resources_private,$permissions);
	}
$dir_csound_resources_private = $bp_application_path.$csound_resources_private.SLASH;

if(!file_exists($bp_application_path.$tonality_resources)) {
	mkdir($bp_application_path.$tonality_resources);
	chmod($bp_application_path.$tonality_resources,$permissions);
	}
$dir_tonality_resources = $bp_application_path.$tonality_resources.SLASH;
// 2026-11-19 Same as "csound_resources_private" for tonality resources ('-to' files) - by Claude
$tonality_resources_private = "tonality_resources_private";
if(!file_exists($bp_application_path.$tonality_resources_private)) {
	mkdir($bp_application_path.$tonality_resources_private);
	chmod($bp_application_path.$tonality_resources_private,$permissions);
	}
$dir_tonality_resources_private = $bp_application_path.$tonality_resources_private.SLASH;
// 2026-11-20 SuperCollider resources: '-sc' files (format of '-cs' files) and SynthDef files '*.scd', fixed ones in "supercollider_resources", the user's in "supercollider_resources_private" (see _scrt.php) - by Claude
$supercollider_resources = "supercollider_resources";
$supercollider_resources_private = "supercollider_resources_private";
foreach(array($supercollider_resources,$supercollider_resources_private) as $sc_folder) {
	if(!file_exists($bp_application_path.$sc_folder)) {
		mkdir($bp_application_path.$sc_folder);
		chmod($bp_application_path.$sc_folder,$permissions);
		}
	}
$dir_supercollider_resources = $bp_application_path.$supercollider_resources.SLASH;
$dir_supercollider_resources_private = $bp_application_path.$supercollider_resources_private.SLASH;
// Kind of the resources handled by the pages of '-cs' files (csound.php, csorchestra.php...): "supercollider" when they handle a '-sc' file (supercollider.php, scsynthdef.php)
if(!isset($cs_kind)) $cs_kind = "csound";
rename_new_fixed_resources(); // 2026-11-19

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

$tracelive_folder = "trace_".my_session_id()."_live";
if(!file_exists($temp_dir.$tracelive_folder)) {
	mkdir($temp_dir.$tracelive_folder,0777,TRUE);
	chmod($temp_dir.$tracelive_folder,0777);
	}

// Delete old temp directories and trace files
$dircontent = scandir($temp_dir);
$now = time();
$yesterday = $now - (24 * 3600);
foreach($dircontent as $thisfile) {
	if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
	if(str_ends_with($thisfile,"_stop")) {
		unlink($temp_dir.$thisfile); continue;
		}
	$time_saved = filemtime($temp_dir.$thisfile);
	if($time_saved < $yesterday) $old = TRUE;
	else $old = FALSE;
	if(is_dir($temp_dir.$thisfile)) {
		$table = explode('_',$thisfile);
		$extension = end($table);
		if(($extension == "temp" AND count($table) > 2) OR ($extension == "live" AND count($table) > 1)) {
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
				@unlink($temp_dir.$thisfile);
				continue;
				}
			}
		}
	}

// 2026-11-18 Delete files older than 7 days in the output folder chosen in the settings
if(output_folder_cleanable($output_folder))
	delete_old_files($bp_application_path.$output_folder,$now - (7 * 24 * 3600));

if(isset($_GET['path'])) $path = urldecode($_GET['path']);
else $path = '';

$text_help_file = $bp_application_path."BP3_help.txt";

if($test) {
	echo "<small>";
	echo "operating system = ".getOS()."<br />";
	echo "path = ".$path."<br />";
	echo "bp_application_path = ".$bp_application_path."<br />";
	echo "temp_dir = ".$temp_dir."<br />";
	echo "text_help_file = ".$text_help_file."<br />";
	echo "</small><hr>";
	}

$html_help_file = "BP_help.html";
$help = compile_help($text_help_file,$html_help_file);
$top_header = "// Bol Processor BP3";

// 2026-11-12 Csound resources in the former line format are converted to JSON (originals copied to the trash as NAME_old_format)
convert_csound_resources_to_json($dir_csound_resources,$dir_trash_folder);
convert_csound_resources_to_json($dir_csound_resources_private,$dir_trash_folder);
// 2026-11-12 Tonality resources in the former line format are converted to JSON (originals copied to the trash as NAME_old_format)
convert_tonality_resources_to_json($dir_tonality_resources,$dir_trash_folder);
convert_tonality_resources_to_json($dir_tonality_resources_private,$dir_trash_folder);

$KeyString = "key#";
$Englishnote = array("C","C#","D","D#","E","F","F#","G","G#","A","A#","B","C");
$Frenchnote = array("do","do#","re","re#","mi","fa","fa#","sol","sol#","la","la#","si","do");
$Indiannote = array("sa","rek","re","gak","ga","ma","ma#","pa","dhak","dha","nik","ni","sa");
$AltEnglishnote = array("B#","Db","D","Eb","Fb","E#","Gb","G","Ab","A","Bb","Cb","B#");
$AltFrenchnote = array("si#","reb","re","mib","fab","mi#","solb","sol","lab","la","sib","dob","si#");
$AltIndiannote = array("ni#","sa#","re","re#","mak","ga#","pak","pa","pa#","dha","dha#","sak","ni#");

// Create a list of fractions eligible for frequency ratios in just intonation
// Numerator or denominator may be a power of 3 multiplied by a power of 5 multiplied by a power of 2
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

if(!file_exists('latest_version.cache') OR filemtime('latest_version.cache') < (time() - 259200)) {
	$latest = getLatestTaggedVersion(); // This shouldn't be called more than once in 3 days
	if($latest !== false) file_put_contents('latest_version.cache',$latest);
	}

// ------ SCRIPTS ------

echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>\n";
echo "<script>";
echo "let currentRequest = null;\n";
echo "function createFile(pathToFile) {
	if (typeof noteEventSource !== 'undefined' && noteEventSource) {
			noteEventSource.close();
			noteEventSource = null;
		}
    currentRequest = $.ajax({
        url: '_createfile.php',
        cache: false,
        data: {
            path_to_file: pathToFile
        	},
		error: function(xhr,status,error) {
			alert('AJAX error: ' + status);
			},
        complete: function() {
            currentRequest = null;
        	}
    	});
	}\n";
echo "</script>";

echo "<script>\n";
echo "$(document).ready(function() {
  $(\"#parent1\").click(function() {
    $(\".child1\").prop(\"checked\", this.checked);
  });

  $('.child1').click(function() {
    if ($('.child1:checked').length == $('.child1').length) {
      $('#parent1').prop('checked', true);
    } else {
      $('#parent1').prop('checked', false);
    }
  });
});";
echo "</script>\n";

echo "<script>\n";
echo "function hide_textarea() {
    var x = document.getElementById('textArea');
    x.className='hidden';
    }\n";
echo "function settoggledisplay_input(i) {
		var x = document.getElementById('showhide_input' + i);
		var y = document.getElementById(\"hideshow\");
    if(x) {
      x.className='hidden'; }
    if(y) {
      y.className='unhidden'; }
    }\n";
echo "function settoggledisplay_output(i) {
    var z = document.getElementById('showhide_output' + i);
    if(z) {
      z.className='hidden'; }
      }\n";
echo "function toggleAllDisplays(imax) {
      for (var i = 0; i <= imax; i++) {
          settoggledisplay_input(i);
          settoggledisplay_output(i);
      }
    }\n";
echo "function toggledisplay_input(i) {
	    var x = document.getElementById('showhide_input' + i);
      var y = document.getElementById(\"hideshow\");
	    if(x) {
	      x.className=(x.className=='hidden')?'unhidden':'hidden'; }
      if(y) {
        y.className=(y.className=='hidden')?'unhidden':'hidden'; }
	  }\n";
echo "function toggledisplay_output(i) {
      var z = document.getElementById('showhide_output' + i);
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";
echo "function settogglesearch() {
      var z = document.getElementById(\"search\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function togglesearch() {
      var z = document.getElementById(\"search\");
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";

echo "function settoggledownload() {
      var z = document.getElementById(\"download\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function toggledownload() {
      var z = document.getElementById(\"download\");
      if(z) {
		z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";

echo "function settogglescales() {
      var z = document.getElementById(\"scales\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function togglescales() {
      var z = document.getElementById(\"scales\");
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";

echo "function settogglecreate() {
      var z = document.getElementById(\"create\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function togglecreate() {
      var z = document.getElementById(\"create\");
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";

echo "function settoggleimport() {
      var z = document.getElementById(\"import\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function toggleimport() {
      var z = document.getElementById(\"import\");
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
    }\n";
echo "</script>\n";

echo "<script>\n";
echo "function tellsave() {\n";
echo "localStorage.setItem('data','dirty');\n";
echo "}</script>\n";

echo "<script>";
echo "function clearFields(inputId, nameId, commentId) {
    document.getElementsByName(inputId)[0].value = \"\";
    document.getElementsByName(nameId)[0].value = \"\";
    document.getElementsByName(commentId)[0].value = \"\";\n";
echo "} </script>";

echo "<script>
	async function selectFolder() {
		try {
			const folderHandle = await window.showDirectoryPicker();
			const folderPath = folderHandle.name;

			document.getElementById('folderPath').value = folderPath;
			console.log(\"Selected Folder: \", folderPath);
		} catch (error) {
			console.error(\"Error selecting folder:\", error);
		}
	}";
echo "</script>";


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

function windows_program_files() {
	// The "Program Files" folder of 64-bit programs
	$folder = getenv('ProgramW6432');
	if($folder === FALSE OR $folder == '') $folder = getenv('ProgramFiles');
	if($folder === FALSE OR $folder == '') $folder = "C:\\Program Files";
	return rtrim($folder,"/\\");
	}

function csound_candidate_dirs() {
	// Folders where Csound is usually installed, in order of preference: Csound 7 first, then the folders in PATH, then Csound 6
	global $csound_name;
	if(windows_system()) {
		$dirs = array(windows_program_files()."\\Csound7\\bin");
		$old = array(windows_program_files()."\\Csound6_x64\\bin");
		}
	else if(linux_system()) {
		$dirs = array("/usr/local/bin","/usr/bin");
		$old = array();
		}
	else { // MacOS
		$dirs = array("/Applications/Csound");
		$old = array("/usr/local/bin","/opt/homebrew/bin");
		}
	$env_path = getenv('PATH');
	if($env_path !== FALSE) {
		foreach(explode(PATH_SEPARATOR,$env_path) as $dir) {
			$dir = rtrim(trim($dir),"/\\");
			if($dir <> '' AND file_exists($dir.SLASH.$csound_name)) $dirs[] = $dir;
			}
		}
	return array_values(array_unique(array_merge($dirs,$old)));
	}

function extract_data($is_grammar,$compact,$content) {
	global $true_bp_grammar,$grammar_has_structures,$thistype,$reason_not_true;
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
	if($is_grammar) $is_valid_for_parsing = FALSE;
	$nr_end_lines= 0;
	if($is_grammar) $is_true_bp = TRUE;
	$extract_data['grammar'] = $extract_data['metronome'] = $extract_data['time_structure'] = $extract_data['headers'] = $extract_data['alphabet'] = $extract_data['grammar'] = $extract_data['objects'] = $extract_data['csound'] = $extract_data['tonality'] = $extract_data['settings'] = $extract_data['data'] = $extract_data['orchestra'] = $extract_data['timebase'] = $extract_data['interaction'] = $extract_data['midisetup'] = $extract_data['timebase'] = $extract_data['keyboard'] = $extract_data['glossary'] = $extract_data['cstables'] = $extract_data['weights'] = $extract_data['data'] = '';
	$extract_data['supercollider'] = ''; // 2026-11-20 '-sc' file
	$extract_data['templates'] = $found_templates = $need_end_line = FALSE;
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
		if(($new_name = new_name($line,"instruction")) <> $line) {
			$line = $new_name; // Convert old prefixes/prefixes to new ones
			}
		$line = trim($line);
		if(str_starts_with($line,'--')) $nr_end_lines++;
		else $nr_end_lines = 0;
		if($found_templates AND str_starts_with($line,'--')) $need_end_line = FALSE;
		if($nr_end_lines > 1) {
			$nr_end_lines--;
			continue;
			}
		$table_out[] = $line;
		if(str_starts_with($line,"//") OR str_starts_with($line,"/*")) continue;
		$line = preg_replace("/ *\[.*\]/u",'',$line);
		$line = trim(preg_replace("/ *\/\/.*$/u",'',$line));
		if(is_integer($pos=strpos($line,"TEMPLATES:")) AND $pos == 0) {
			$extract_data['templates'] = $found_templates = $need_end_line = TRUE;
			}
		if($is_grammar AND !$found_templates AND structural_rule($line)) $grammar_has_structures = TRUE;
		if($is_grammar AND !$found_templates AND !is_true_bp($line)) $is_true_bp = FALSE;
		if($is_grammar AND !$found_templates AND is_valid_for_parsing($line)) $is_valid_for_parsing = TRUE;
		if(is_integer($pos=strpos($line,"_mm")) AND $pos == 0) {
			$metronome = preg_replace("/.*_mm\(([^\)]+)\).*/u","$1",$line);
			$extract_data['metronome'] = $metronome;
			$time_structure = preg_replace("/.+\)\s+_(.+)$/u","$1",$line);
			if($time_structure == "striated" OR $time_structure == "smooth")
				$extract_data['time_structure'] = $time_structure;
			}
		if(is_integer($pos=strpos($line,"_smooth")) AND $pos == 0) {
			$extract_data['time_structure'] = "smooth";
			}
		if(is_integer($pos=strpos($line,"_striated")) AND $pos == 0) {
			$extract_data['time_structure'] = "striated";
			}
		if(is_integer($pos=strpos($line,"-gr")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['grammar'] = fix_file_name($line,"ho");
		else if(is_integer($pos=strpos($line,"-ho")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['alphabet'] = fix_file_name($line,"ho");
		else if(is_integer($pos=strpos($line,"-al")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['alphabet'] = fix_file_name($line,"al");
		else if(is_integer($pos=strpos($line,"-da")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['data'] = fix_file_name($line,"da");
		else if(is_integer($pos=strpos($line,"-so")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['objects'] = fix_file_name($line,"so");
		else if(is_integer($pos=strpos($line,"-gr")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['grammar'] = fix_file_name($line,"gr");
		else if(is_integer($pos=strpos($line,"-mi")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['objects'] = fix_file_name($line,"mi");
		else if(is_integer($pos=strpos($line,"-cs")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['csound'] = fix_file_name($line,"cs");
		else if(str_starts_with($line,"-sc.") AND !is_integer(strpos($line,"<")))
			$extract_data['supercollider'] = trim($line); // 2026-11-20 SuperCollider resource
		else if(is_integer($pos=strpos($line,"-to")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['tonality'] = fix_file_name($line,"to");
		else if(is_integer($pos=strpos($line,"-se")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['settings'] = fix_file_name($line,"se");
		else if(is_integer($pos=strpos($line,"-da")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['data'] = fix_file_name($line,"da");
		else if(is_integer($pos=strpos($line,"-or")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['orchestra'] = fix_file_name($line,"or");
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
		else if(is_integer($pos=strpos($line,"-wg")) AND $pos == 0 AND !is_integer(strpos($line,"<")))
			$extract_data['weights'] = fix_file_name($line,"wg");
		else if($line <> '' AND !str_starts_with($line,'/*')) {
			$start = FALSE;
			}
		}
	if($need_end_line) $table_out[] = "-----";
	$extract_data['content'] = implode(chr(10),$table_out);
	// Below, we fix an old error of naming tempered tunings
	$extract_data['content'] = str_replace("_scale(meantone_","_scale(",$extract_data['content']);
	if($is_grammar) {
		if($is_true_bp AND $is_valid_for_parsing) {
			$true_bp_grammar = TRUE;
			}
		else if(!$is_valid_for_parsing) {
			$reason_not_true = "▶︎ It does not contain rules valid for parsing (with '<--' or '<->' derivation signs) ➡ Replace '-->' with '<->' to fix it!<br />";
			}
		}
	return $extract_data;
	}

function structural_rule($line) {
	if(str_contains($line,"(=") OR str_contains($line,"(:")) {
	//	echo "Structural rule (parenthesis): ".$line."<br />";
		return TRUE;
		}
	if(str_contains($line," +") OR str_contains($line,":") OR str_contains($line,";") OR str_contains($line," =") OR str_contains($line,"\\")) {
	//	echo "Structural rule (structure marker): ".$line."<br />";
		return TRUE;
		}
	return FALSE;
	}

function is_true_bp($line) {
	global $reason_not_true;
	if(str_ends_with($line, '->') OR str_contains($line, 'lambda') || str_contains($line, 'empty')  || str_contains($line, 'null') || str_contains($line, 'nil')) {
		$reason_not_true .= "▶︎ It has an erasing rule: ".$line."<br />";
		return FALSE;
		}
	if(str_ends_with($line, 'SUB') || str_ends_with($line, 'SUB1') || str_ends_with($line, 'POSLONG')) {
		$reason_not_true .= "▶︎ A subgrammar has a derivation mode such as 'SUB', 'SUB1', 'POSLONG'<br />";
		return FALSE;
		}
	if(str_contains($line, '{') || str_contains($line, '}')) {
		$reason_not_true .= "▶︎ This rule contains {polymetric expressions}: ".$line."<br />";
		return FALSE;
		}
	if(preg_match('/<K?\d+-\d+>/', $line)) {
		$reason_not_true .= "▶︎ This rule has a decreasing weight: ".htmlspecialchars($line)."<br />";
		return FALSE;
		}
	if(preg_match('#/.+?/#',$line)) {
		$reason_not_true .= "▶︎ This rule contains a /flag/: ".$line."<br />";
		return FALSE;
		}
	if(str_contains($line, '_destru')) {
		$reason_not_true .= "▶︎ A subgrammar has a '_destru' instruction<br />";
		return FALSE;
		}
	if(str_contains($line, '_goto')  || str_contains($line, '_failed') || str_contains($line, '_repeat') || str_contains($line, '_stop')) {
		$reason_not_true .= "▶︎ A rule has an instruction such as '_goto', '_repeat', etc.<br />";
		return FALSE;
		}
	if(str_contains($line, '_retro') || str_contains($line, '_rndseq') || str_contains($line, '_keyxpand') || str_contains($line, '_stepOn') || str_contains($line, '_stepOff')) {
		$reason_not_true .= "▶︎ A rule has performance controls such as '_retro', '_keyxpand', '_stepOn', etc.<br />";
		return FALSE;
		}
	return TRUE;
	}

function is_valid_for_parsing($line) {
	if(str_contains($line,'<--') || str_contains($line,'<->')) return TRUE;
	return FALSE;
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

// 2026-11-20 Settings, alphabet and sound-object files ("-se.NAME", "-al.NAME", "-so.NAME", or the older "-ho" and "-mi") named by a grammar or data project - by Claude
function project_attached_files($file_path) {
	$names = array();
	$content = @file_get_contents($file_path);
	if($content === FALSE) return $names;
	$types = array("-se." => "se","-al." => "al","-ho." => "ho","-so." => "so","-mi." => "mi");
	$table = explode(chr(10),str_replace(chr(13),chr(10),$content));
	foreach($table as $line) {
		$line = trim(preg_replace("/ *\/\/.*$/u",'',trim($line)));
		if(strlen($line) < 5 OR is_integer(strpos($line,"<"))) continue;
		$start = substr($line,0,4);
		if(!isset($types[$start])) continue;
		$name = fix_file_name($line,$types[$start]);
		if($name <> '' AND !in_array($name,$names)) $names[] = $name;
		}
	return $names;
	}

// 2026-11-20 When a grammar or data project is moved from $source_dir to $dest_dir, the settings, alphabet and sound-object files it names are copied there (not moved, since other projects may use them) - by Claude
function copy_project_files($moved_file_path,$source_dir,$dest_dir) {
	$source_dir = rtrim($source_dir,"/\\").SLASH;
	$dest_dir = rtrim($dest_dir,"/\\").SLASH;
	foreach(project_attached_files($moved_file_path) as $name) {
		$source_file = $source_dir.$name;
		$dest_file = $dest_dir.$name;
		if(!file_exists($source_file)) {
			if(!file_exists($dest_file)) echo "<p><span class=\"red-text\">➡ File</span> ‘<span class=\"green-text\">".$name."</span>’ was not found, it could not be copied</p>";
			continue;
			}
		if(file_exists($dest_file)) {
			if(@file_get_contents($dest_file) !== @file_get_contents($source_file))
				echo "<p><span class=\"red-text\">➡ A different file</span> ‘<span class=\"green-text\">".$name."</span>’ is already in the destination folder: it has been kept</p>";
			continue;
			}
		if(@copy($source_file,$dest_file))
			echo "<p><span class=\"red-text\">➡</span> Copied ‘<span class=\"green-text\">".$name."</span>’ to the destination folder</p>";
		else echo "<p><span class=\"red-text\">➡ Could not copy</span> ‘<span class=\"green-text\">".$name."</span>’</p>";
		}
	}

function window_name($text) {
	$text = str_replace('-','_',$text);
	$text = str_replace(' ','_',$text);
	$text = str_replace('"','_',$text);
	$text = str_replace("'",'_',$text);
	return $text;
	}
	
function display_more_buttons($error,$content,$url_source_page,$dir,$grammar_file,$objects_file,$csound_file,$tonality_file,$alphabet_file,$data_file,$weights_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file) {
	global $bp_application_path, $csound_resources, $tonality_resources, $output_file, $file_format, $current_file, $grammarWindow, $test;
	$page_type = str_replace(".php",'',$url_source_page);
	$page_type = preg_replace("/\.php.*/u",'',$url_source_page);
	
	$dir = str_replace($bp_application_path,'',$dir);
	if($test) echo "dir = ".$dir."<br />";
	echo "<table style=\"padding:0px; border-spacing:2px; background-color:transparent;\" cellpadding=\"0px;\"><tr>";
	if($error) {
		echo "<td style=\"vertical-align:middle;\"><big><span class=\"red-text blinking\">➡</span></big></td>";
		}
	if($alphabet_file <> '') {
		$url_this_page = "alphabet.php?file=".urlencode($dir.$alphabet_file);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" onclick=\"window.open('".$url_this_page."','".$alphabet_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$alphabet_file)."’\">";
		echo "</td>";
		}
	if($weights_file <> '') {
		$url_this_page = "weights.php?file=".urlencode($dir.$weights_file);
		$url_this_page .= "&grammar_file=".urlencode($current_file); 
		$url_this_page .= "&grammarWindow=".urlencode($grammarWindow);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$url_this_page."','".$weights_file."','width=800,height=800,left=100'); return false;}\" value=\"EDIT ‘".begin_with(20,$weights_file)."’\">";
		echo "</td>";
		}
	if($objects_file <> '') {
		$url_this_page = "objects.php?file=".urlencode($dir.$objects_file);
		echo "<td>";
		echo "<input class=\"edit\" type=\"submit\" formtarget=\"_blank\" formaction=\"".$url_this_page."\" value=\"EDIT ‘".begin_with(20,$objects_file)."’\">&nbsp;";
		echo "</td>";
		}
	// 2026-11-21 The EDIT buttons of the '-cs' and '-sc' files are shown only if the output format uses them (as in produce.php):
	// Csound score, real-time Csound and event list use '-cs', real-time SuperCollider uses '-sc', real-time OSC uses '-sc' if the project names one, otherwise '-cs'.
	// MIDI (real-time or file) and BP score use neither. Pages without an output format (e.g. alphabet.php) show both.
	global $sc_file; // 2026-11-20 SuperCollider resource of the project
	$show_cs = $show_sc = TRUE;
	if(isset($file_format) AND $file_format <> '') {
		$sc_named = (isset($sc_file) AND $sc_file <> '' AND file_exists(resource_path("supercollider",$sc_file)));
		$show_cs = ($file_format == "csound" OR $file_format == "csoundrt" OR $file_format == "eventlist" OR ($file_format == "osc" AND !$sc_named));
		$show_sc = ($file_format == "scrt" OR ($file_format == "osc" AND $sc_named));
		}
	if($csound_file <> '' AND $show_cs) {
		$url_this_page = "csound.php?file=".urlencode(csound_resource_folder($csound_file).SLASH.$csound_file);
		echo "<td>";
		echo "<input class=\"edit\" type=\"submit\" formtarget=\"_blank\" formaction=\"".$url_this_page."\" value=\"EDIT ‘".begin_with(20,$csound_file)."’\">&nbsp;";
		echo "</td>";
		}
	if(isset($sc_file) AND $sc_file <> '' AND $show_sc) {
		$url_this_page = "supercollider.php?file=".urlencode(resource_folder("supercollider",$sc_file).SLASH.$sc_file);
		echo "<td>";
		echo "<input class=\"edit\" type=\"submit\" formtarget=\"_blank\" formaction=\"".$url_this_page."\" value=\"EDIT ‘".begin_with(20,$sc_file)."’\">&nbsp;";
		echo "</td>";
		}
	if($tonality_file <> '') {
		$url_this_page = "tonality.php?file=".urlencode(tonality_resource_folder($tonality_file).SLASH.$tonality_file);
		echo "<td>";
		echo "<input class=\"edit\" type=\"submit\" formtarget=\"_blank\" formaction=\"".$url_this_page."\" value=\"EDIT ‘".begin_with(20,$tonality_file)."’\">&nbsp;";
		echo "</td>";
		}
	if($settings_file <> '') {
		$url_this_page = "settings.php?file=".urlencode($dir.$settings_file);
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$url_this_page."','".$settings_file."','width=800,height=800,left=100'); return false;}\" value=\"EDIT ‘".begin_with(20,$settings_file)."’\">";
		echo "</td>";
		}
	if($orchestra_file <> '') {
		$url_this_page = "orchestra.php?file=".urlencode($dir.$orchestra_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$orchestra_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$orchestra_file)."’\">";
		echo "</td>";
		}
	if($interaction_file <> '') {
		$url_this_page = "interaction.php?file=".urlencode($dir.$interaction_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$interaction_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$interaction_file)."’\">";
		echo "</td>";
		}
	if($midisetup_file <> '') {
		$url_this_page = "midisetup.php?file=".urlencode($dir.$midisetup_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$midisetup_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$midisetup_file)."’\">";
		echo "</td>";
		}
	if($timebase_file <> '') {
		$url_this_page = "timebase.php?file=".urlencode($dir.$timebase_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$timebase_file."','width=1000,height=900,left=50'); return false;\" value=\"EDIT ‘".begin_with(20,$timebase_file)."’\">";
		echo "</td>";
		}
	if($keyboard_file <> '') {
		$url_this_page = "keyboard.php?file=".urlencode($dir.$keyboard_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$keyboard_file."','width=700,height=770,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$keyboard_file)."’\">";
		echo "</td>";
		}
	if($glossary_file <> '') {
		$url_this_page = "glossary.php?file=".urlencode($dir.$glossary_file);
		echo "<td>";
		echo "<input class=\"edit\" style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_this_page."','".$glossary_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$glossary_file)."’\">";
		echo "</td>";
		}
	echo "</tr>";
	echo "</table>";

	if($content <> '') {
		if($page_type == "grammar" OR $page_type == "alphabet" OR $page_type == "glossary" OR $page_type == "interaction") {
			if(isset($_POST['show_help_entries'])) {
				$entries = display_help_entries($content);
				echo $entries."<br />";
				}
			else {
				echo "<div id=\"thisone\" style=\"text-align:left; margin-top:12px; background-color:transparent;\">";
				echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
				echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
				echo "👉 <input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" formaction=\"".$url_source_page."#help_entries\" name=\"show_help_entries\" value=\"SHOW HELP ENTRIES\">";
				echo "</div>";
				}
			}
		}
	return;
	}

function ask_create_new_file($url_this_page,$filename) {
	echo "File ‘".$filename."’ not found. Do you wish to create a new one under that name?";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"createfile\" value=\"YES\">";
	echo "&nbsp;<input class=\"save\" type=\"submit\" name=\"dontcreate\" value=\"NO\"></p>";
	echo "</form>";
	die();
	}

function try_create_new_file($file,$filename) {
	global $permissions;
	if(isset($_POST['dontcreate'])) {
		echo "<p style=\"color:red;\" class=\"blinking\">No file created. You can close this tab…</p>";
		die();
		}
	if(isset($_POST['createfile'])) {
		echo "<p style=\"color:red;\" class=\"blinking\">Creating ‘".$filename."’. Don’t forget to SAVE it!</p>";
		$handle = fopen($file,"w");
		fclose($handle);
		chmod($file,$permissions);
		}
	}

function compile_help($text_help_file,$html_help_file) {
	global $filename,$permissions;
//	echo "text_help_file = ".$text_help_file."<br />";
//	echo "html_help_file = ".$html_help_file."<br />";
	$help = array();
	$help[0] = '';
	$no_entry = array("ON","OFF","vel");
    if(isset( $filename) AND $filename == "Compilation") return '';
	if(!file_exists($text_help_file)) {
		echo "<p style=\"color:MediumTurquoise;\">Warning: “".$html_help_file."” has not yet been reconstructed.</p>";
		return '';
		}
	$content = @file_get_contents($text_help_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if($content) {
		$file_header = "<!DOCTYPE HTML>\n";
		$file_header .= "<html lang=\"en\">\n";
		$file_header .= "<head>\n";
		$file_header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
		$file_header .= "<link rel=\"stylesheet\" href=\"bp-light.css\" />\n";
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
		$file_header .= "<p style=\"color:black;\">".$table[0]."</p>";
		sort($table);
		$handle = @fopen($html_help_file,"w");
		if($handle) {
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
					$table_of_contents .= "<td><small><a style=\"color:darkblue;\" href=\"#".$i."\">".$title[$i]."</a></small></td>";
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
				fwrite($handle,"<h4 style=\"color:darkblue;\" id=\"".$i."\"><a href=\"#toc\">⇪</a> ".$title[$i]."</h4>\n");
				fwrite($handle,$item[$i]."\n");
				}
			fwrite($handle,"</body>");
			fclose($handle);
			chmod($html_help_file,$permissions);
			}
		else {
			echo "<p>➡ The BP_help file cannot be created. Maybe the disk is full, or the “php” folder is write-protected.</p>";
			die();
			}
		}
	return $help;
	}

function link_to_help() {
	global $html_help_file;
//	$console_link = "produce.php?instruction=help";
	$console_link = "produce.php?instruction=help&keepalive=1";
	$link = "<p>👉 Display <a class=\"linkdotted\" href=\"".nice_url($console_link)."\" onclick=\"window.open('".$console_link."','help','width=800,height=800,left=200'); return false;\">console's instructions</a> or the <a class=\"linkdotted\" onclick=\"window.open('".nice_url($html_help_file)."','Help','width=800,height=500'); return false;\" href=\"".nice_url($html_help_file)."\">complete BP3 help file</a></p>";
	return $link;
	}

function display_help_entries($content) {
	$table = explode("\n",$content);
	$ignore = FALSE;
	$entries = "<br /><table id=\"help_entries\" style=\"border-spacing: 2px;\"><tr><td style=\"padding:1em; background-color:azure; color:black;\">";
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
					$insert = "<a class=\"darkblue-text\" onclick=\"window.open('".nice_url($html_help_file)."#".$i."','show_help','width=800,height=300'); return false;\" href=\"".nice_url($html_help_file)."#".$i."\">";
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

// note 40
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
	$text = str_replace("¥","•",$text);
	$text = str_replace("Ô","‘",$text);
	$text = str_replace("Õ","’",$text);
	$text = str_replace("Ò","“",$text);
	$text = str_replace("Ó","”",$text);
	$text = str_replace("É","…",$text);
	$text = str_replace("Â","¬",$text);
	$text = str_replace("¤","•",$text);
	$text = str_replace("â¢","•",$text);
/*	$text = preg_replace('/-se\s+/','-se.',$text);
	$text = preg_replace('/-al\s+/','-al.',$text);
	$text = preg_replace('/-gr\s+/','-gr.',$text);
	$text = preg_replace('/-so\s+/','-so.',$text);
	$text = preg_replace('/-to\s+/','-to.',$text);
	$text = preg_replace('/-cs\s+/','-cs.',$text);
	$text = preg_replace('/-tb\s+/','-tb.',$text);
	$text = preg_replace('/-or\s+/','-or.',$text);
	$text = preg_replace('/-in\s+/','-in.',$text);
	$text = preg_replace('/-md\s+/','-md.',$text);
	$text = preg_replace('/-gl\s+/','-gl.',$text); */
	$text = str_replace('∞',"inf",$text);
	if($create_bullets) $text = preg_replace("/\s\\.$/u"," •",$text);
//	if($create_bullets) $text = preg_replace("/\s\\.([^0-9])/u"," •$1",$text);
	if ($create_bullets) {
		$text = $text ?? '';
		$text = preg_replace_callback(
			"/\s\\.$/u",
			function ($matches) {
				return " •";
			},
			$text
		);
		$text = preg_replace_callback(
			"/\s\\.([^0-9])/u",
			function ($matches) {
				return " •" . $matches[1];
				},$text);
		}
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
//	$text = preg_replace("/(\s*)•(\s*)/u",'$1 . $2', $text);
	$text = preg_replace("/(\s*)•(\s*)/u",' .$2', $text); // $2 can be a line break
	$text = str_replace(" … "," _rest ",$text);
	$text = preg_replace("/\s*…\s*,/u"," _rest,",$text);
	$text = preg_replace("/{\s*…\s*/u","{_rest ",$text);
	$text = preg_replace("/\s*…\s*}/u"," _rest}",$text);
	$text = preg_replace("/,\s*…\s*/u",", _rest ",$text);
	$text = preg_replace("/] */u","] ",$text);
	return $text;
	}

function decode_entities($text) {
	$text = str_replace("_rest","…",$text);
	return $text;
	}

function add_linefeeds_after_remarks($text) { // 2026-04-23
	$table = explode(chr(10),$text);
	$is_comment = FALSE;
	$table2 = array();
	foreach($table AS $line) {
		$line = trim($line);
		if(str_starts_with($line,"//") OR str_starts_with($line,"-")) {
			if(!$is_comment) $table2[] = '';
			$is_comment = TRUE;
			}
		else {
			if($is_comment) {
				if($line <> '') $table2[] = '';
				$is_comment = FALSE;
				}
			}
		$table2[] = $line;
		}
	$text = implode(chr(10),$table2);
	return $text;
	}

function cut_linefeeds_in_remarks($text) { // 2026-04-23
	$table = explode(chr(10),$text);
	$is_comment = FALSE;
	$comment_line = '';
	$table2 = array();
	foreach($table AS $line) {
		$line = trim($line);
		if(str_starts_with($line,"/*")) {
			if(!str_ends_with($line,"*/")) {
				$is_comment = TRUE;
				$comment_line = $line;
				continue;
				}
			else {
				$line = str_replace("/*",'',$line);
				$line = str_replace("*/",'',$line);
				$line = "// ".$line;
				}
			}
		if($is_comment AND str_ends_with($line,"*/")) {
			$line = $comment_line." ".$line;
			$is_comment = FALSE;
			$comment_line = '';
			}
		if($is_comment) {
			$comment_line .= " ".$line;
			continue;
			}
		$table2[] = $line;
		}
	$text = implode(chr(10),$table2);
	return $text;
	}

function clean_up_file_to_html($file) {
	global $permissions;
	// echo "file = ".$file."<br />";
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$file_html = str_replace(".txt",".html",$file);
	$text = @file_get_contents($file);
	if(MB_CONVERT_OK) $text = mb_convert_encoding($text,'UTF-8','UTF-8');
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
	$handle = @fopen($file_html,"w");
	if($handle) {
		$header = "<head>\n";
		$header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />\n";
		$header .= "<style>
		body, p, div {
			overflow-wrap: anywhere;
			word-break: break-word;
			}
		</style>\n";
		$header .= "</head><body>\n";
		fwrite($handle,$header."\n");
		fwrite($handle,$text."\n");
		fwrite($handle,"</body>\n");
		fclose($handle);
		chmod($file_html,$permissions);
		return $file_html;
		}
	return '';
	}

function clean_up_file($file) { // NOT USED
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$text = @file_get_contents($file);
	if(MB_CONVERT_OK) $text = mb_convert_encoding($text,'UTF-8','UTF-8');
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

function note_convention($i) {
	switch($i) {
		case 0: $c = "english"; break;
		case 1: $c = "italian/Spanish/French"; break;
		case 2: $c = "indian"; break;
		case 3: $c = "keys"; break;
		case 4: $c = "custom"; break;
		default: $c = "english";
		}
	return $c;
	}

function my_rmdir($src) {
    $dir = opendir($src);
    while(FALSE !== ($file = readdir($dir))) {
        if(($file <> '.' ) && ($file <> '..')) {
            $full = $src.SLASH.$file;
            if(is_dir($full)) my_rmdir($full);
            else @unlink($full);
            }
        }
    closedir($dir);
    @rmdir($src);
    return;
	}

function output_folder_cleanable($folder) {
	// 2026-11-18 The output folder is cleaned by delete_old_files(). Refuse folders of the program, or that may contain projects, in case one of them has been chosen as the output folder.
	$folder = trim($folder);
	if($folder == '' OR $folder == '.' OR is_integer(strpos($folder,"..")) OR $folder[0] == '/' OR $folder[0] == '\\' OR is_integer(strpos($folder,':'))) return FALSE;
	$top = preg_split("/[\/\\\\]/",$folder)[0];
	if(hidden_directory($top)) return FALSE;
	if(is_integer($pos=strpos($top,"BP2-")) AND $pos == 0) return FALSE;
	switch($top) {
		case "resources":
		case "trash_bolprocessor":
		case "scripts":
		case "ctests":
		case "data":
		case "my_data":
		case "bp_old_versions":
		case "bp_trace_versions":
		case "Csound examples":
			return FALSE;
		}
	return TRUE;
	}

function delete_old_files($folder,$limit) {
	// 2026-11-18 Deletes files modified before date $limit in $folder and its subfolders, and the subfolders left empty
	if(!is_dir($folder)) return;
	$dircontent = scandir($folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		$full = $folder.SLASH.$thisfile;
		if(is_dir($full)) {
			delete_old_files($full,$limit);
			if(count(array_diff(scandir($full),array('.','..','.DS_Store'))) == 0) {
				@unlink($full.SLASH.".DS_Store");
				@rmdir($full);
				}
			}
		else if(filemtime($full) < $limit) @unlink($full);
		}
	return;
	}

function delete_folder($folder,$all) {
	if(!file_exists($folder)) return;
	$dircontent = scandir($folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		if(is_dir($folder.SLASH.$thisfile)) my_rmdir($folder.SLASH.$thisfile);
		else unlink($folder.SLASH.$thisfile);
		}
	if($all) @rmdir($folder);
	return;
	}

function SaveObjectPrototypes($verbose,$dir,$filename,$temp_folder,$force,$page = NULL) {
	// 2026-11-12 Sound-object prototypes are saved in JSON format. Values are stored by their key, see so_data_to_json()
	// 2026-11-18 '$page' gives the values of the form of objects.php (named as in this form). By default they are read from $_POST - by Claude
	global $top_header,$test,$temp_dir,$csound_resources,$permissions;
	// 2026-11-10 Without the temporary folder (deleted as old), the file would be saved without its prototypes
	if(!is_dir($temp_dir.$temp_folder)) return "missing";
	$file_lock = $dir.$filename."_lock"; // 2026-11-10 The lock is next to the file, not in php/
	$time_start = time();
	$time_end = $time_start + 3;
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
	if(!$force AND !file_exists($file_changed)) return "skipped";
	@unlink($file_changed);
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) @unlink($file_lock);
		sleep(1);
		}
	$handle = fopen($file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	$file_header = $top_header."\n// Object prototypes file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$data = array();
	if($page === NULL) $page = $_POST;
	try {
		$data['PrototypeTickKey'] = so_line_to_value("int",$page['PrototypeTickKey'],"PrototypeTickKey");
		$data['PrototypeTickChannel'] = so_line_to_value("int",$page['PrototypeTickChannel'],"PrototypeTickChannel");
		$data['PrototypeTickVelocity'] = so_line_to_value("int",$page['PrototypeTickVelocity'],"PrototypeTickVelocity");
		$CsoundInstruments_filename = trim($page['CsoundInstruments_filename']);
		if(!is_integer($pos=strpos($CsoundInstruments_filename,$csound_resources)) AND $CsoundInstruments_filename <> '')
			$CsoundInstruments_filename = $csound_resources.SLASH.$CsoundInstruments_filename;
		$data['CsoundInstrumentsFile'] = $CsoundInstruments_filename;
		$data['PrototypesComment'] = trim($page['comment_on_file']);
		// Prototypes are read from the temporary files edited by prototype.php
		$data['Prototypes'] = array();
		$dircontent = scandir($temp_dir.$temp_folder);
		foreach($dircontent as $thisfile) {
			if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
			$table = explode(".",$thisfile);
			$extension = end($table);
			if($extension <> "txt") continue;
			$object_label = str_replace(".".$extension,'',$thisfile);
			if($verbose) echo $object_label." ";
			$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile);
			$extract_data = extract_data(FALSE,TRUE,$content);
			if(!is_integer($pos=strpos($extract_data['headers'],"//"))) continue;
			$lines = explode(chr(10),$extract_data['content']);
			$i = 1; // Line 0 is the name of the prototypes file
			$p = array("Name" => $object_label);
			so_read_prototype_lines($lines,$i,$p);
			$p["Comment"] = '';
			for(; $i < count($lines); $i++) {
				if(is_integer(stripos($lines[$i],"<HTML>"))) {
					$p["Comment"] = decode_tags(so_strip_html($lines[$i]));
					break;
					}
				}
			$save_codes_dir = $temp_dir.$temp_folder.SLASH.clean_folder_name($object_label)."_codes";
			$csound_score = @file_get_contents($save_codes_dir."/csound.txt");
			if($csound_score === FALSE) $csound_score = '';
			if(MB_CONVERT_OK) $csound_score = mb_convert_encoding($csound_score,'UTF-8','UTF-8');
			$p["Csound score"] = so_text_to_score($csound_score);
			// MIDI codes are fetched from a separate "midibytes.txt" file, which may be empty
			$all_bytes = @file_get_contents($save_codes_dir."/midibytes.txt");
			$p['MIDIcodes'] = so_text_to_codes(($all_bytes === FALSE) ? '' : $all_bytes);
			$data['Prototypes'][] = $p;
			}
		}
	catch(Exception $e) { // The file is left unchanged
		error_log("SaveObjectPrototypes(): ".$e->getMessage());
		unlink($file_lock);
		return "error";
		}
	$json = so_data_to_json($file_header,$data);
	@unlink($dir.$filename);
	file_put_contents($dir.$filename,$json);
	chmod($dir.$filename,$permissions);
	if($verbose) echo "</font></p><hr>";
	unlink($file_lock);
	return "saved";
	}

function so_temp_changed($temp_folder,$prototypes_file) {
	// 2026-11-18 Called by prototype.php when a prototype has been changed in the temporary folder of a '-so' file. The file is saved at once, with the other values (tick, Csound resource file, comment on the file) it has on disk, which prototype.php doesn't change. Previously it was saved by the autosave of objects.php within 30 seconds, and reloading objects.php before discarded the changes. If it can't be saved now, '_changed' tells the autosave to do it - by Claude
	global $temp_dir;
	$handle = fopen($temp_dir.$temp_folder.SLASH."_changed","w");
	if($handle) fclose($handle);
	$content = @file_get_contents($prototypes_file);
	if($content === FALSE OR trim($content) == '') return "unreadable";
	$values = so_read_resource($content);
	if(!is_array($values)) return "unreadable";
	$page = array();
	$page['PrototypeTickKey'] = strval(intval(cs_get($values,"PrototypeTickKey",84)));
	$page['PrototypeTickChannel'] = strval(intval(cs_get($values,"PrototypeTickChannel",1)));
	$page['PrototypeTickVelocity'] = strval(intval(cs_get($values,"PrototypeTickVelocity",64)));
	$page['CsoundInstruments_filename'] = trim(cs_get($values,"CsoundInstrumentsFile",''));
	$page['comment_on_file'] = cs_get($values,"PrototypesComment",'');
	return SaveObjectPrototypes(FALSE,dirname($prototypes_file).SLASH,basename($prototypes_file),$temp_folder,FALSE,$page);
	}

function SaveCsoundInstruments($verbose,$dir,$filename,$temp_folder,$force,$page = NULL) {
	// 2026-11-11 Csound resources are saved in JSON format. Values are stored by their key, see csound_data_to_json()
	// 2026-11-18 '$page' gives the values of the form of csound.php (orchestra, channels, parts, tables, tonality). By default they are read from $_POST - by Claude
	global $top_header, $test, $temp_dir, $permissions;
//	$verbose = TRUE;
	if($verbose) echo "dir = ".$dir."<br />";
	if($verbose) echo "filename = ".$filename."<br />";
	if($verbose) echo "temp_folder = ".$temp_folder."<br />";
	if(read_only_resource_folder($dir)) return "read-only"; // 2026-11-19 Fixed file of "csound_resources"
	$file_lock2 = $dir.$filename."_lock2";
	if(file_exists($file_lock2)) return "locked";
	// 2026-11-10 Without the temporary folder (deleted as old), the file would be saved without its instruments
	if(!is_dir($temp_dir.$temp_folder)) return "missing";
	$file_lock = $dir.$filename."_lock"; // 2026-11-10 The lock is next to the file, not in php/
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
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
	$handle = fopen($file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	$exists_name = list_of_parameter_names($temp_dir,$temp_folder);
	$file_header = $top_header."\n// ".(str_starts_with($filename,"-sc.") ? "SuperCollider" : "Csound")." resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$data = array();
	$warn_not_empty = FALSE;
	if($page === NULL) $page = csound_page_values_from_post();
	$data['orchestra'] = $page['orchestra'];
	if($data['orchestra'] == '') {
		$data['orchestra'] = "0-default.orc";
		$warn_not_empty = TRUE;
		}
	$data['channels'] = $page['channels'];
	// 2026-11-12 Assignment of parts to instruments - fixed by Claude
	$data['parts'] = $page['parts'];
	// Instruments are read from the temporary files edited by csinstrument.php and csparameter.php
	$data['instruments'] = array();
	$dircontent = scandir($temp_dir.$temp_folder);
	try {
		foreach($dircontent as $thisfile) {
			if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
			$table = explode(".",$thisfile);
			$extension = end($table);
			if($extension <> "txt") continue;
			$instrument_label = str_replace(".".$extension,'',$thisfile);
			if($verbose) echo $instrument_label." ";
			$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile);
			$extract_data = extract_data(FALSE,FALSE,$content);
			if(!is_integer(strpos($extract_data['headers'],"//"))) continue;
			$lines = explode(chr(10),$extract_data['content']);
			$i = 1; // Line 0 is the name of the Csound resource file
			$instrument = array("CsInstrumentName" => $instrument_label);
			cs_read_fields(cs_instrument_fields(),$lines,$i,$instrument);
			$instrument_folder_name = str_replace(' ','_',$instrument_label);
			$instrument_folder_name = str_replace('-','_',$instrument_folder_name);
			$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$instrument_folder_name;
			if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
			$ip = 0;
			foreach(scandir($folder_this_instrument) as $thisparameter) {
				if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store") continue;
				$thisname = str_replace(".txt",'',$thisparameter);
				if(!isset($exists_name[$thisname])) continue;
				$table = explode(".",$thisparameter);
				$extension = end($table);
				if($extension <> "txt") continue;
				$lines = explode(chr(10),file_get_contents($folder_this_instrument.SLASH.$thisparameter));
				$i = 0;
				cs_read_fields(cs_parameter_fields($ip),$lines,$i,$instrument);
				// 2026-11-18 The name of a parameter is that of its file, which RENAME changes (csinstrument.php) - fixed by Claude
				$instrument["paramlist[".$ip."].name"] = $thisname;
				$ip++;
				}
			$instrument['ipmax'] = $ip;
			$data['instruments'][] = $instrument;
			}
		}
	catch(Exception $e) { // The file is left unchanged
		error_log("SaveCsoundInstruments(): ".$e->getMessage());
		unlink($file_lock);
		return "error";
		}
	$data['tables'] = $page['tables'];
	$data['tonality'] = $page['tonality'];
	$json = csound_data_to_json($file_header,$data);
	@unlink($dir.$filename);
	file_put_contents($dir.$filename,$json);
	chmod($dir.$filename,$permissions);
	unlink($file_lock);
	return $warn_not_empty;
	}

function csound_page_values_from_post() {
	// Values of the form of csound.php, posted by its SAVE buttons or by the autosave (autosaveInstruments.js)
	$page = array();
	$page['orchestra'] = $_POST['CsoundOrchestraName'];
	$page['channels'] = array();
	$number_channels = $_POST['number_channels'];
	for($ch = 0; $ch < $number_channels; $ch++)
		$page['channels'][$ch + 1] = intval(convert_empty($_POST["whichCsoundInstrument_".$ch]));
	$page['parts'] = array();
	for($part = 1; $part <= MAXPARTS; $part++) {
		if(isset($_POST["whichCsoundInstrumentForPart_".$part])) $page['parts'][$part] = intval(convert_empty($_POST["whichCsoundInstrumentForPart_".$part]));
		else $page['parts'][$part] = -1;
		}
	if(isset($_POST['cstables'])) $cstables = $_POST['cstables'];
	else $cstables = $_POST['the_tables']; // We need a different POST used by autosave
	$page['tables'] = array();
	foreach(explode(chr(10),$cstables) as $line) {
		$line = trim($line);
		if($line <> '') $page['tables'][] = $line;
		}
	$page['tonality'] = trim($_POST['tonality_filename']);
	if($page['tonality'] == "No associated tonality file") $page['tonality'] = '';
	return $page;
	}

function csound_page_values_from_file($path) {
	// 2026-11-18 Values of the form of csound.php as they are in the '-cs' file on disk, or FALSE - by Claude
	$content = @file_get_contents($path);
	if($content === FALSE OR trim($content) == '') return FALSE;
	$json = csound_read_resource($content);
	if(!is_array($json)) return FALSE;
	$values = cs_values($json);
	$page = array();
	$page['orchestra'] = cs_get($values,"CsoundOrchestraName",'');
	$page['channels'] = array();
	for($ch = 1; $ch <= 16; $ch++) // MAXCHAN in the console
		$page['channels'][$ch] = intval(cs_get($values,"WhichCsoundInstrumentForChannel[".$ch."]",cs_get($values,"WhichCsoundInstrument[".$ch."]",-1)));
	$page['parts'] = array();
	for($part = 1; $part <= MAXPARTS; $part++)
		$page['parts'][$part] = intval(cs_get($values,"WhichCsoundInstrumentForPart[".$part."]",-1));
	$page['tables'] = array();
	for($k = 0; array_key_exists("CsoundTables[".$k."]",$values); $k++) {
		$line = trim($values["CsoundTables[".$k."]"]);
		if($line <> '') $page['tables'][] = $line;
		}
	$page['tonality'] = trim(cs_get($values,"TonalityFileName",''));
	return $page;
	}

function csound_temp_read_only($temp_folder) {
	// 2026-11-19 TRUE if the '-cs' file whose temporary folder is $temp_folder (see "_source", written by csound.php) is a fixed file that can't be changed - by Claude
	global $temp_dir;
	$source = @file($temp_dir.$temp_folder.SLASH."_source",FILE_IGNORE_NEW_LINES);
	if($source === FALSE OR count($source) < 2) return FALSE;
	return read_only_resource_folder($source[0]);
	}

function csound_temp_changed($temp_folder) {
	// 2026-11-18 Called by csinstrument.php and csparameter.php when instruments or parameters have been changed in the temporary folder of a '-cs' file. The file is saved at once, with the other values (orchestra, channels, parts, tables, tonality) it has on disk, which these pages don't change. Previously it was saved by the autosave of csound.php within 30 seconds, and reloading csound.php before discarded the changes. If it can't be saved now, '_changed' tells the autosave to do it - by Claude
	global $temp_dir;
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
	$handle = fopen($file_changed,"w");
	if($handle) fclose($handle);
	$source = @file($temp_dir.$temp_folder.SLASH."_source",FILE_IGNORE_NEW_LINES);
	if($source === FALSE OR count($source) < 2) return "unknown";
	$dir = $source[0]; $filename = $source[1];
	$page = csound_page_values_from_file($dir.$filename);
	if($page === FALSE) return "unreadable";
	$result = SaveCsoundInstruments(FALSE,$dir,$filename,$temp_folder,FALSE,$page);
	if(is_bool($result)) return "saved";
	return $result;
	}

// 2026-11-11 Csound resources ("-cs" files) are stored in JSON format.
// Labels and keys are those of the instrument exports "instr-NAME.json" made by ExportJsonCsoundInstrument() in the console.
// Values are read by their key, so that labels can be changed and new keys added.
// An instrument is handled as an array of values indexed by their keys.

function cs_controls() {
	return array("PitchBend" => "pitchbend","Volume" => "volume","Pressure" => "pressure","Modulation" => "modulation","Panoramic" => "panoramic");
	}

function cs_instrument_fields() {
	// Values of an instrument following its name, in the order of the temporary instrument files (csinstrument.php)
	// and of the former line format: array(key, default value, type)
	$no_mapping = 2147483647.;
	$fields = array();
	$fields[] = array("CsInstrumentComment",'',"text");
	$fields[] = array("iargmax",0,"int");
	$fields[] = array("CsInstrumentIndex",0,"int");
	$fields[] = array("CsDilationRatioIndex",-1,"argument");
	$fields[] = array("CsAttackVelocityIndex",-1,"argument");
	$fields[] = array("CsReleaseVelocityIndex",-1,"argument");
	$fields[] = array("CsPitchIndex",-1,"argument");
	$fields[] = array("CsPitchFormat","OPPC","pitchformat");
	$fields[] = array("pitchbendrange",-1.,"float");
	foreach(cs_controls() as $c => $word) {
		$fields[] = array("r".$c.".islogx",0,"int");
		$fields[] = array("r".$c.".islogy",0,"int");
		}
	foreach(cs_controls() as $c => $word) $fields[] = array("Cs".$c."StartIndex",-1,"argument");
	foreach(cs_controls() as $c => $word) $fields[] = array("Cs".$c."EndIndex",-1,"argument");
	foreach(cs_controls() as $c => $word) $fields[] = array($word."table",-1,"argument");
	foreach(cs_controls() as $c => $word) $fields[] = array($word."GEN",7,"int");
	for($k = 0; $k < 6; $k++)
		foreach(cs_controls() as $c => $word) $fields[] = array("Cs".$c."[".$k."]",$no_mapping,"float");
	return $fields;
	}

function cs_parameter_fields($ip) {
	// Values of additional parameter $ip, in the order of the temporary parameter files (csparameter.php)
	$p = "paramlist[".$ip."].";
	$fields = array();
	$fields[] = array($p."name",'',"name");
	$fields[] = array($p."comment",'',"text");
	$fields[] = array($p."startindex",-1,"argument");
	$fields[] = array($p."endindex",-1,"argument");
	$fields[] = array($p."table",-1,"argument");
	$fields[] = array($p."defaultvalue",0.,"float");
	$fields[] = array($p."GENtype",7,"int");
	$fields[] = array($p."combinationtype","ADD","combination");
	return $fields;
	}

function cs_new_instrument($index) {
	// 2026-11-12 Returns the values of a new instrument (formerly "instrument_template"). Other keys get the defaults of cs_instrument_fields()
	$values = array();
	foreach(cs_instrument_fields() as $field) $values[$field[0]] = $field[1];
	$new = array("CsInstrumentComment" => "(Comment on this instrument)","iargmax" => 10,"CsInstrumentIndex" => intval($index),
		"CsPitchIndex" => 4,"pitchbendrange" => 200.,"rPitchBend.islogx" => 1,"rPitchBend.islogy" => 1,
		"CsPitchBendStartIndex" => 8,"CsPitchBendEndIndex" => 9,"pitchbendtable" => 10,
		"CsVolumeStartIndex" => 5,"CsVolumeEndIndex" => 6,"volumetable" => 7,
		"CsPitchBend[0]" => 0.,"CsPitchBend[1]" => 8191.5,"CsPitchBend[2]" => 16383.,
		"CsPitchBend[3]" => -200.,"CsPitchBend[4]" => 0.,"CsPitchBend[5]" => 200.,
		"CsModulation[0]" => 0.,"CsModulation[1]" => 8192.,"CsModulation[2]" => 16383.);
	foreach(array("Volume","Pressure","Panoramic") as $c) {
		$new["Cs".$c."[0]"] = 0.; $new["Cs".$c."[1]"] = 64.; $new["Cs".$c."[2]"] = 127.;
		}
	return array_merge($values,$new);
	}

function cs_new_parameter($ip,$name) {
	// 2026-11-12 Returns the values of new parameter $ip (formerly "parameter_template")
	$p = "paramlist[".$ip."].";
	return array($p."name" => $name,$p."comment" => "(Comment on this parameter)",$p."startindex" => 11,$p."endindex" => 12,$p."table" => 13,$p."defaultvalue" => 0.,$p."GENtype" => 7,$p."combinationtype" => "ADD");
	}

function cs_new_data() {
	// 2026-11-12 Returns the values of a new Csound resource file (formerly "csound_template"), see csound_data_to_json()
	$channels = array();
	for($ch = 1; $ch <= 16; $ch++) $channels[$ch] = -1;
	$parts = array();
	for($part = 1; $part <= MAXPARTS; $part++) $parts[$part] = -1;
	return array("orchestra" => "0-default.orc","channels" => $channels,"parts" => $parts,"instruments" => array(),"tables" => array("f1 0 256 10 1 ; This table may be changed"),"tonality" => '');
	}

function cs_values_to_lines($fields,$values) {
	// Returns the lines of a temporary file (csinstrument.php, csparameter.php) for $values indexed by the keys of $fields
	$lines = array();
	foreach($fields as $field) {
		list($key,$default,$type) = $field;
		$lines[] = cs_value_to_line($type,cs_get($values,$key,$default));
		}
	return $lines;
	}

function cs_line_to_value($type,$line,$key) {
	// Converts a line of the former format to a JSON value, or throws an exception
	if($line === NULL) throw new Exception("missing value of ‘".$key."’");
	$line = trim(rtrim($line,"\r"));
	switch($type) {
		case "text": return decode_tags($line); // Text is stored in lines with recode_tags()
		case "name": return $line;
		}
	if(!is_numeric($line)) throw new Exception("‘".$key."’ should be a number, found ‘".$line."’");
	switch($type) {
		case "float": return floatval($line);
		case "pitchformat":
			$formats = array(0 => "OPPC",1 => "OPD",2 => "CPS");
			return isset($formats[intval($line)]) ? $formats[intval($line)] : intval($line);
		case "combination": return (intval($line) == 0) ? "MULT" : "ADD";
		}
	return intval($line);
	}

function cs_value_to_line($type,$value) {
	// Converts a JSON value to a line of the temporary files
	switch($type) {
		case "text": return recode_tags($value);
		case "name": return $value;
		case "float": return strval(floatval($value));
		case "pitchformat":
			$formats = array("OPPC" => 0,"OPD" => 1,"CPS" => 2);
			return isset($formats[$value]) ? $formats[$value] : intval($value);
		case "combination": return ($value === "MULT" OR $value === 0) ? 0 : 1;
		}
	return intval($value);
	}

function cs_read_fields($fields,$lines,&$i,&$values) {
	// Reads $fields from $lines starting at line $i into $values indexed by their keys
	foreach($fields as $field) {
		list($key,$default,$type) = $field;
		$line = ($i < count($lines)) ? $lines[$i] : NULL;
		$values[$key] = cs_line_to_value($type,$line,$key);
		$i++;
		}
	}

function cs_get($values,$key,$default) {
	if(!is_array($values) OR !array_key_exists($key,$values) OR $values[$key] === NULL) return $default;
	return $values[$key];
	}

function cs_values($object) {
	// Returns the values of a JSON object indexed by their "key"
	$values = array();
	if(!is_array($object)) return $values;
	foreach($object as $label => $item)
		if(is_array($item) AND isset($item['key']) AND array_key_exists('value',$item)) $values[$item['key']] = $item['value'];
	return $values;
	}

function cs_item($key,$value,$unit = '') {
	$item = array("key" => $key,"value" => $value);
	if($unit <> '') $item['unit'] = $unit;
	return $item;
	}

function csound_lines_to_data($lines) {
	// Parses a Csound resource in the former line format, as read by LoadCsoundInstruments() in the console.
	// Header lines starting with "//" are ignored. Returns an array, or throws an exception.
	$table = array();
	foreach($lines as $line) {
		$line = rtrim($line,"\r");
		if(count($table) == 0 AND (trim($line) == '' OR substr(trim($line),0,2) == "//")) continue;
		$table[] = $line;
		}
	$lines = $table;
	$i = 0;
	$data = array();
	$read = array();
	cs_read_fields(array(array("number_channels",0,"int")),$lines,$i,$read);
	$data['channels'] = array();
	for($ch = 1; $ch <= $read['number_channels']; $ch++) {
		cs_read_fields(array(array("channel",-1,"int")),$lines,$i,$read);
		$data['channels'][$ch] = $read['channel'];
		}
	cs_read_fields(array(array("CsoundOrchestraName",'',"name")),$lines,$i,$read);
	$data['orchestra'] = $read['CsoundOrchestraName'];
	cs_read_fields(array(array("number_instruments",0,"int")),$lines,$i,$read);
	$data['instruments'] = array();
	for($j = 0; $j < $read['number_instruments']; $j++) {
		$instrument = array();
		cs_read_fields(array(array("CsInstrumentName",'',"name")),$lines,$i,$instrument);
		cs_read_fields(cs_instrument_fields(),$lines,$i,$instrument);
		cs_read_fields(array(array("ipmax",0,"int")),$lines,$i,$instrument);
		for($ip = 0; $ip < $instrument['ipmax']; $ip++)
			cs_read_fields(cs_parameter_fields($ip),$lines,$i,$instrument);
		$data['instruments'][] = $instrument;
		}
	$data['tables'] = array();
	while($i < count($lines) AND trim($lines[$i]) == '') $i++;
	$line = ($i < count($lines)) ? trim($lines[$i++]) : NULL;
	if($line === "_begin tables") {
		while($i < count($lines)) {
			$line = trim($lines[$i++]);
			if($line == '') continue;
			if($line == "_end tables") break;
			$data['tables'][] = $line;
			}
		while($i < count($lines) AND trim($lines[$i]) == '') $i++;
		$line = ($i < count($lines)) ? trim($lines[$i++]) : NULL;
		}
	$data['tonality'] = '';
	if($line !== NULL AND $line <> "No associated tonality file") $data['tonality'] = $line;
	return $data;
	}

function cs_instrument_json($instrument) {
	// Returns the JSON object of an instrument given as values indexed by their keys
	$v = $instrument;
	$x = array();
	$x["Name of this instrument"] = cs_item("CsInstrumentName",cs_get($v,"CsInstrumentName",''));
	$x["Index of this instrument"] = cs_item("CsInstrumentIndex",cs_get($v,"CsInstrumentIndex",0));
	$x["Comment on this instrument"] = cs_item("CsInstrumentComment",cs_get($v,"CsInstrumentComment",''));
	$x["Number of fields"] = cs_item("iargmax",cs_get($v,"iargmax",0));
	$x["Field of dilation ratio (-1 means empty)"] = cs_item("CsDilationRatioIndex",cs_get($v,"CsDilationRatioIndex",-1));
	$x["Field of attack velocity (-1 means empty)"] = cs_item("CsAttackVelocityIndex",cs_get($v,"CsAttackVelocityIndex",-1));
	$x["Field of release velocity (-1 means empty)"] = cs_item("CsReleaseVelocityIndex",cs_get($v,"CsReleaseVelocityIndex",-1));
	$x["Field of pitch"] = cs_item("CsPitchIndex",cs_get($v,"CsPitchIndex",-1));
	$x["CsPitchFormat"] = cs_item("CsPitchFormat",cs_get($v,"CsPitchFormat","OPPC"),"enum");
	foreach(cs_controls() as $c => $word) {
		$x["Field of start ".$word] = cs_item("Cs".$c."StartIndex",cs_get($v,"Cs".$c."StartIndex",-1));
		$x["Field of end ".$word] = cs_item("Cs".$c."EndIndex",cs_get($v,"Cs".$c."EndIndex",-1));
		}
	$x["pitchbendrange"] = cs_item("pitchbendrange",floatval(cs_get($v,"pitchbendrange",-1.)),"cents");
	foreach(cs_controls() as $c => $word)
		$x["Field of ".$word." table (-1 means empty)"] = cs_item($word."table",cs_get($v,$word."table",-1));
	foreach(cs_controls() as $c => $word)
		$x[ucfirst($word)." GEN type"] = cs_item($word."GEN",cs_get($v,$word."GEN",7));
	foreach(cs_controls() as $c => $word) {
		$x["MIDI ".$word." is logarithmic"] = cs_item("r".$c.".islogx",cs_get($v,"r".$c.".islogx",0),"boolean");
		$x["Csound ".$word." is logarithmic"] = cs_item("r".$c.".islogy",cs_get($v,"r".$c.".islogy",0),"boolean");
		}
	foreach(cs_controls() as $c => $word) {
		if($c == "PitchBend") $label = "MIDI to Csound pitchbend conversion (number > 100000 means no mapping) #";
		else $label = "MIDI to Csound ".$word." mapping (number > 100000 means no mapping) #";
		for($k = 0; $k < 6; $k++)
			$x[$label.($k + 1)] = cs_item("Cs".$c."[".$k."]",floatval(cs_get($v,"Cs".$c."[".$k."]",2147483647.)));
		}
	$ipmax = intval(cs_get($v,"ipmax",0));
	$x["Number of additional parameters"] = cs_item("ipmax",$ipmax);
	for($ip = 0; $ip < $ipmax; $ip++) {
		$n = $ip + 1;
		$p = "paramlist[".$ip."].";
		$x["Name of additional parameter #".$n] = cs_item($p."name",cs_get($v,$p."name",''));
		$x["Comment on additional parameter #".$n] = cs_item($p."comment",cs_get($v,$p."comment",''));
		$x["Start index of additional parameter #".$n] = cs_item($p."startindex",cs_get($v,$p."startindex",-1));
		$x["End index of additional parameter #".$n] = cs_item($p."endindex",cs_get($v,$p."endindex",-1));
		$x["Table index of additional parameter #".$n] = cs_item($p."table",cs_get($v,$p."table",-1));
		$x["Default value of additional parameter #".$n] = cs_item($p."defaultvalue",floatval(cs_get($v,$p."defaultvalue",0.)));
		$x["GEN type of additional parameter #".$n] = cs_item($p."GENtype",cs_get($v,$p."GENtype",7));
		$x["Combination mode of additional parameter #".$n] = cs_item($p."combinationtype",cs_get($v,$p."combinationtype","ADD"),"enum");
		}
	return $x;
	}

function csound_data_to_json($file_header,$data) {
	// Returns the JSON text of a Csound resource. To add a value to the file, add it here and read it by its key in csound.php.
	$json = array();
	$json['header'] = $file_header;
	$json["Csound orchestra file"] = cs_item("CsoundOrchestraName",$data['orchestra']);
	foreach($data['channels'] as $ch => $index)
		$json["Index of the instrument associated with MIDI channel ".$ch." (-1 means none)"] = cs_item("WhichCsoundInstrumentForChannel[".$ch."]",$index); // 2026-11-12 Same name as in the console
	// 2026-11-12 Assignment of parts to instruments, which takes precedence over that of MIDI channels - fixed by Claude
	for($part = 1; $part <= MAXPARTS; $part++) {
		if(isset($data['parts'][$part])) $index = intval($data['parts'][$part]);
		else $index = -1;
		$json["Index of the instrument associated with part ".$part." (-1 means none)"] = cs_item("WhichCsoundInstrumentForPart[".$part."]",$index);
		}
	$instruments = array();
	foreach($data['instruments'] as $instrument) $instruments[] = cs_instrument_json($instrument);
	$json["Csound instruments"] = cs_item("CsInstruments",$instruments);
	foreach($data['tables'] as $k => $line)
		$json["Csound table line #".($k + 1)] = cs_item("CsoundTables[".$k."]",$line);
	$json["Associated tonality file"] = cs_item("TonalityFileName",$data['tonality']);
	return json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)."\n";
	}

function csound_is_json($content) {
	// A Csound resource in JSON format starts with '{', possibly after a byte order mark
	$content = ltrim(str_replace("\xEF\xBB\xBF",'',$content));
	return ($content <> '' AND $content[0] == '{');
	}

function csound_json_decode($content) {
	// Returns the decoded JSON of a Csound resource, or FALSE
	$json = json_decode(str_replace("\xEF\xBB\xBF",'',$content),TRUE);
	if(!is_array($json)) return FALSE;
	return $json;
	}

function convert_csound_resources_to_json($dir,$dir_trash) {
	// 2026-11-12 Converts to JSON all Csound resources ("-cs" files) of $dir that are in the former line format.
	// The original is copied to the trash as NAME_old_format. Returns the names of converted files.
	global $top_header,$permissions;
	$converted = array();
	$dircontent = @scandir($dir);
	if($dircontent === FALSE) return $converted;
	foreach($dircontent as $thisfile) {
		if(!str_starts_with($thisfile,"-cs") AND !str_ends_with($thisfile,".bpcs")) continue;
		if(preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		$path = $dir.$thisfile;
		if(!is_file($path)) continue;
		if(file_exists($path."_lock") OR file_exists($path."_lock2")) continue; // Being saved
		$handle = @fopen($path,"r");
		if(!$handle) continue;
		$start = fread($handle,64);
		fclose($handle);
		if($start === FALSE OR trim($start) == '' OR csound_is_json($start)) continue;
		$content = @file_get_contents($path);
		if($content === FALSE) continue;
		$extract_data = extract_data(FALSE,FALSE,$content);
		try {
			$data = csound_lines_to_data(explode(chr(10),$extract_data['content']));
			}
		catch(Exception $e) {
			error_log("convert_csound_resources_to_json(): ".$thisfile." could not be converted: ".$e->getMessage());
			continue;
			}
		$header = str_replace("<br />","\n",$extract_data['headers']);
		if($header == '') $header = $top_header;
		$header .= "\n// Converted to JSON format. Date: ".gmdate('Y-m-d H:i:s')." GMT";
		$json = csound_data_to_json($header,$data);
		if($dir_trash <> '' AND is_dir($dir_trash)) @copy($path,$dir_trash.$thisfile."_old_format");
		$temp_file = $dir.".converting".$thisfile; // Replaces the file in a single step
		if(@file_put_contents($temp_file,$json) === FALSE) continue;
		@chmod($temp_file,$permissions);
		if(@rename($temp_file,$path)) $converted[] = $thisfile;
		else @unlink($temp_file);
		}
	return $converted;
	}

function csound_read_resource($content) {
	// Returns the decoded JSON of a Csound resource, or an error message.
	// A file in the former line format is converted to JSON first.
	if(csound_is_json($content)) {
		$json = csound_json_decode($content);
		if($json === FALSE) return "This file is not valid JSON: ".json_last_error_msg();
		return $json;
		}
	$extract_data = extract_data(FALSE,FALSE,$content);
	$header = str_replace("<br />","\n",$extract_data['headers']);
	try {
		$data = csound_lines_to_data(explode(chr(10),$extract_data['content']));
		}
	catch(Exception $e) {
		return "This file could not be read: ".$e->getMessage();
		}
	return csound_json_decode(csound_data_to_json($header,$data));
	}

// 2026-11-12 Sound-object prototypes ("-so" files) are stored in JSON format.
// Labels and keys are those of the export "proto-NAME.json" made by ExportPrototypeAsJson() in the console.
// Values are read by their key. As in the export, a few items have no key and are read by their label:
// "Prototypes", and in each prototype "Csound score", "byte_N", "time_N" and "Comment".
// A prototype is handled as an array of values indexed by their keys. Its MIDI codes are in 'MIDIcodes',
// as numbers time * 256 + byte, as in the former line format.
// The temporary files edited by prototype.php keep the former line format, see so_temp_lines().

function so_enum_names($enum) {
	switch($enum) {
		case "type": return array(0 => "SILENT",1 => "MIDI_TYPE",4 => "CSOUND_TYPE",5 => "MIDI_CSOUND_TYPE");
		case "channel": return array(0 => "GLOBAL_CH",-1 => "LOCAL_CH");
		case "mode": return array(0 => "PERCENT",-1 => "FIXVALUE",1 => "IRRELEVANT");
		case "pivot": return array(1 => "PIVBEG",2 => "PIVEND",3 => "PIVBEGON",4 => "PIVENDOFF",5 => "PIVMIDDLE",6 => "PIVMIDDLEONOFF",7 => "SETPIVOT");
		case "rescale": return array(0 => "OK_RESCALE",1 => "NEVER_RESCALE",2 => "DILATION_RATIO");
		case "strike": return array(1 => "ALWAYS",0 => "NEVER",-1 => "DEFAULT");
		case "csound": return array(-1 => "LOCAL_CS",0 => "GLOBAL_CS");
		}
	return array();
	}

function so_prototype_fields() {
	// Values of a prototype in the order of the JSON file: key => array(label, type, default value, unit)
	// These default values are used for new prototypes and for missing keys. LoadObjectPrototypesJSON() in the console uses the same ones.
	// To add a value, add it here, in so_temp_lines() if prototype.php edits it, and in the console.
	$f = array();
	$f["Name"] = array("Name","text",'','');
	$f["Type"] = array("Object type","enum:type",1,'');
	$f["Resolution"] = array("Resolution","int",1,"ms");
	$f["DefaultChannel"] = array("Default MIDI channel","enum:channel",-1,'');
	$f["Tref"] = array("Tref","int",1000,"ms");
	$f["Quan"] = array("Quantization","number",10,"ms");
	$f["PivType"] = array("Pivot type","enum:pivot",1,'');
	$f["PivMode"] = array("Pivot mode","enum:mode",-1,'');
	$f["PivPos"] = array("Pivot position","float",0.,"ms or percent");
	$f["RescaleMode"] = array("Rescale mode","enum:rescale",0,'');
	$f["FixScale"] = array("Never rescale","bool",0,"boolean");
	$f["OkExpand"] = array("Expand","bool",1,"boolean");
	$f["OkCompress"] = array("Compress","bool",1,"boolean");
	$f["AlphaMin"] = array("Minimum ratio","float",0.,'');
	$f["AlphaMax"] = array("Maximum ratio","float",0.,'');
	$f["AlphaCtrl"] = array("Alpha control","bool",0,"boolean");
	$f["AlphaCtrlNr"] = array("Send ratio to controller number","int",-1,'');
	$f["AlphaCtrlChan"] = array("Send ratio to controller channel","int",0,'');
	$f["OkTransp"] = array("Accept transposition","bool",1,"boolean");
	$f["OkArticul"] = array("Accept articulation","bool",1,"boolean");
	$f["OkVolume"] = array("Accept volume changes","bool",1,"boolean");
	$f["OkPan"] = array("Accept panoramic changes","bool",1,"boolean");
	$f["OkMap"] = array("Accept key expand and mapping","bool",1,"boolean");
	$f["OkVelocity"] = array("Accept velocity changes","bool",1,"boolean");
	$f["OkRelocate"] = array("Relocate","bool",1,"boolean");
	$f["DelayMode"] = array("Delay mode","enum:mode",1,'');
	$f["MaxDelay"] = array("Max delay","int",0,"ms");
	$f["ForwardMode"] = array("Forward mode","enum:mode",1,'');
	$f["MaxForward"] = array("Max forward","int",0,"ms");
	$f["BreakTempo"] = array("Break tempo after this object (organum)","bool",1,"boolean");
	$f["ContBeg"] = array("Force continuity at the beginning","bool",0,"boolean");
	$f["ContBegMode"] = array("Max gap at beginning mode","enum:mode",1,'');
	$f["MaxBegGap"] = array("Max gap at beginning","int",0,"ms");
	$f["ContEnd"] = array("Force continuity at the end","bool",0,"boolean");
	$f["ContEndMode"] = array("Max gap at end mode","enum:mode",1,'');
	$f["MaxEndGap"] = array("Max gap at end (ms or percent)","int",0,"ms");
	$f["CoverBeg"] = array("Cover beginning","bool",1,"boolean");
	$f["CoverBegMode"] = array("Cover beginning mode","enum:mode",0,'');
	$f["MaxCoverBeg"] = array("Max cover at beginning","int",100,"ms or percent");
	$f["CoverEnd"] = array("Cover end","bool",1,"boolean");
	$f["CoverEndMode"] = array("Cover end mode","enum:mode",1,'');
	$f["MaxCoverEnd"] = array("Max cover at end","int",100,"ms or percent");
	$f["TruncBeg"] = array("Truncate beginning","bool",1,"boolean");
	$f["TruncBegMode"] = array("Truncate beginning mode","enum:mode",1,'');
	$f["MaxTruncBeg"] = array("Max truncate beginning","int",0,"ms or percent");
	$f["TruncEnd"] = array("Truncate end","bool",1,"boolean");
	$f["TruncEndMode"] = array("Truncate end mode","enum:mode",1,'');
	$f["MaxTruncEnd"] = array("Max truncate end","int",0,"ms or percent");
	$f["PreRollMode"] = array("Preroll mode","enum:mode",-1,'');
	$f["Preroll"] = array("Preroll value","float",0.,"ms or percent");
	$f["PostRollMode"] = array("Postroll mode","enum:mode",-1,'');
	$f["Postroll"] = array("Postroll value","float",0.,"ms or percent");
	$f["CyclicMode"] = array("Cyclic mode","enum:mode",1,'');
	$f["CyclicAfter"] = array("Cyclic after","float",0.,"ms or percent");
	$f["ForceIntegerCycles"] = array("Force integer number of cycles","bool",0,"boolean");
	$f["StrikeAgain"] = array("Strike again NoteOn's","enum:strike",-1,'');
	$f["CsoundInstrumentMode"] = array("Csound instrument mode","enum:csound",-1,'');
	$f["CsoundInstr"] = array("Csound instrument #","int",-1,'');
	$f["Tpict"] = array("Tempo of MF2T code","int",0,"ms");
	$f["division"] = array("Division of MF2T code","number",1,'');
	return $f;
	}

function so_new_prototype() {
	// Returns a new prototype with default values. Its name is given by the temporary file name, see SaveObjectPrototypes()
	$p = array();
	foreach(so_prototype_fields() as $key => $field) $p[$key] = $field[2];
	$p["Csound score"] = "<HTML></HTML>";
	$p['MIDIcodes'] = array();
	$p["Comment"] = "(Comment on this prototype)";
	return $p;
	}

function so_new_data() {
	// Returns the values of a new sound-object prototypes file, without prototypes
	return array("PrototypeTickKey" => 84,"PrototypeTickChannel" => 1,"PrototypeTickVelocity" => 64,"CsoundInstrumentsFile" => '',"PrototypesComment" => '',"Prototypes" => array());
	}

function so_bits() {
	// Positions of the values stored as digits in a single line of the former format. PivType uses positions 0 to 5 and 18
	return array("FixScale" => 7,"OkExpand" => 8,"OkCompress" => 9,"OkRelocate" => 10,"BreakTempo" => 11,"ContBeg" => 12,"ContEnd" => 13,"CoverBeg" => 14,"CoverEnd" => 15,"TruncBeg" => 16,"TruncEnd" => 17,"AlphaCtrl" => 19);
	}

function so_temp_lines() {
	// Values of a prototype in the order of the temporary files edited by prototype.php, which is that of the former line format.
	// "#bits" is the line of digits (see so_bits()), the other "#..." lines are no longer used and get a constant value.
	return array("Type","Resolution","DefaultChannel","Tref","Quan","#bits","RescaleMode","AlphaMin","AlphaMax","DelayMode","MaxDelay","ForwardMode","MaxForward","#notused","division","ContBegMode","MaxBegGap","ContEndMode","MaxEndGap","CoverBegMode","MaxCoverBeg","CoverEndMode","MaxCoverEnd","TruncBegMode","MaxTruncBeg","TruncEndMode","MaxTruncEnd","PivMode","PivPos","AlphaCtrlNr","AlphaCtrlChan","OkTransp","OkArticul","OkVolume","OkPan","OkMap","OkVelocity","Preroll","Postroll","PreRollMode","PostRollMode","CyclicMode","CyclicAfter","ForceIntegerCycles","#discardnoteoffs","StrikeAgain","CsoundInstrumentMode","CsoundInstr","Tpict","#color","#color","#color");
	}

function so_type_of($type) {
	// Returns the base type and the enum name of a type in so_prototype_fields()
	if(str_starts_with($type,"enum:")) return array("enum",substr($type,5));
	return array($type,'');
	}

function so_line_to_value($type,$line,$key) {
	// Converts a line of the former format to a JSON value, or throws an exception
	if($line === NULL) throw new Exception("missing value of ‘".$key."’");
	$line = trim(rtrim($line,"\r"));
	list($base,$enum) = so_type_of($type);
	if($base == "text") return $line;
	if(!is_numeric($line)) throw new Exception("‘".$key."’ should be a number, found ‘".$line."’");
	switch($base) {
		case "float": return floatval($line);
		case "number": return so_number("number",$line);
		case "enum":
			$names = so_enum_names($enum);
			$x = intval($line);
			if($x == -2 AND ($enum == "mode" OR $enum == "rescale")) $x = 1; // Fixing an old bug, as in the console
			return isset($names[$x]) ? $names[$x] : $x;
		}
	return intval($line);
	}

function so_number($type,$value) {
	// Returns the number of a JSON value. An enum may be a name or a number.
	list($base,$enum) = so_type_of($type);
	if($base == "enum" AND is_string($value)) {
		$numbers = array_flip(so_enum_names($enum));
		if(isset($numbers[$value])) return $numbers[$value];
		}
	switch($base) {
		case "float": return floatval($value);
		case "number": return (floatval($value) == intval($value)) ? intval($value) : floatval($value); // As in the export, e.g. Quan
		}
	return intval($value);
	}

function so_json_value($type,$value) {
	// Normalises a value before saving it in JSON
	list($base,$enum) = so_type_of($type);
	if($base == "text") return strval($value);
	$x = so_number($type,$value);
	if($base == "enum") {
		$names = so_enum_names($enum);
		if(isset($names[$x])) return $names[$x];
		}
	return $x;
	}

function so_value_to_line($type,$value) {
	// Converts a JSON value to a line of the temporary files
	list($base,$enum) = so_type_of($type);
	if($base == "text") return $value;
	$x = so_number($type,$value);
	if(is_float($x) AND round($x,4) == $x) return sprintf("%.4f",$x); // As in the former files
	return strval($x);
	}

function so_read_prototype_lines($lines,&$i,&$p) {
	// Reads the values of a prototype from $lines in the order of so_temp_lines(), starting at line $i.
	// The colour lines, which old versions don't have, are read only if they are numbers.
	$fields = so_prototype_fields();
	foreach(so_temp_lines() as $token) {
		$line = ($i < count($lines)) ? trim(rtrim($lines[$i],"\r")) : NULL;
		if($token == "#color") {
			if($line !== NULL AND is_numeric($line)) $i++;
			continue;
			}
		$i++;
		if($line === NULL) throw new Exception("missing value of ‘".$token."’");
		if($token == "#notused" OR $token == "#discardnoteoffs") continue;
		if($token == "#bits") {
			// As in the console: PivType = pivbeg + 2 * pivend + ... + 6 * pivmiddleonoff + 7 * pivspec
			$digit = array();
			for($k = 0; $k < 20; $k++) $digit[$k] = ($k < strlen($line) AND ctype_digit($line[$k])) ? intval($line[$k]) : 0;
			if(strlen($line) < 20) $digit[19] = 1; // A missing AlphaCtrl is TRUE in the console and in prototype.php
			$pivtype = 0;
			for($k = 0; $k < 6; $k++) $pivtype += ($k + 1) * $digit[$k];
			$pivtype += 7 * $digit[18];
			$p["PivType"] = so_line_to_value($fields["PivType"][1],strval($pivtype),"PivType");
			foreach(so_bits() as $key => $k) $p[$key] = $digit[$k];
			continue;
			}
		$p[$token] = so_line_to_value($fields[$token][1],$line,$token);
		}
	}

function so_prototype_to_lines($p) {
	// Returns the lines of a prototype in the order of so_temp_lines(), with default values for missing keys
	$fields = so_prototype_fields();
	$lines = array();
	foreach(so_temp_lines() as $token) {
		switch($token) {
			case "#notused": $lines[] = "1"; break;
			case "#discardnoteoffs": $lines[] = "0"; break;
			case "#color": $lines[] = "65535"; break;
			case "#bits":
				$digits = str_repeat("0",20);
				$pivtype = so_number($fields["PivType"][1],cs_get($p,"PivType",$fields["PivType"][2]));
				if($pivtype >= 1 AND $pivtype <= 6) $digits[$pivtype - 1] = "1";
				if($pivtype == 7) $digits[18] = "1";
				$rescale = so_number($fields["RescaleMode"][1],cs_get($p,"RescaleMode",$fields["RescaleMode"][2]));
				if($rescale == 0) $digits[6] = "1"; // okrescale, which is the same as RescaleMode = OK_RESCALE
				foreach(so_bits() as $key => $k)
					if(intval(cs_get($p,$key,$fields[$key][2])) <> 0) $digits[$k] = "1";
				$lines[] = $digits;
				break;
			default:
				$lines[] = so_value_to_line($fields[$token][1],cs_get($p,$token,$fields[$token][2]));
				break;
			}
		}
	return $lines;
	}

function so_strip_html($line) {
	$line = str_ireplace("<HTML>",'',$line);
	return trim(str_ireplace("</HTML>",'',$line));
	}

function so_score_to_text($score) {
	// Csound score as stored in the file ("<HTML>line<BR>line<BR></HTML>") => text of "csound.txt"
	$score = str_ireplace("<HTML>",'',$score);
	$score = str_ireplace("</HTML>",'',$score);
	return str_ireplace("<BR>","\n",$score);
	}

function so_text_to_score($text) {
	// Text of "csound.txt" => Csound score as stored in the file
	$score = "<HTML>";
	foreach(explode(chr(10),$text) as $line) {
		$line = trim($line);
		if($line <> '') $score .= $line."<BR>";
		}
	return $score."</HTML>";
	}

function so_codes_to_text($codes) {
	// MIDI codes => text of "midibytes.txt": the number of codes, then one code per line
	return count($codes)."\n".(count($codes) > 0 ? implode("\n",$codes)."\n" : '');
	}

function so_text_to_codes($text) {
	// Text of "midibytes.txt" => MIDI codes, or throws an exception
	$lines = array();
	foreach(explode(chr(10),$text) as $line) {
		$line = trim($line);
		if($line <> '') $lines[] = $line;
		}
	$codes = array();
	if(count($lines) == 0) return $codes;
	$number = so_line_to_value("int",$lines[0],"number of MIDI codes");
	for($n = 1; $n <= $number AND $n < count($lines); $n++)
		$codes[] = so_line_to_value("int",$lines[$n],"MIDI code #".$n);
	return $codes;
	}

function so_lines_to_data($lines) {
	// Parses sound-object prototypes in the former line format, as read by LoadObjectPrototypes() in the console.
	// Header lines starting with "//" are ignored. Returns an array of values indexed by keys, or throws an exception.
	$table = array();
	foreach($lines as $line) {
		$line = rtrim($line,"\r");
		if(count($table) == 0 AND (trim($line) == '' OR substr(trim($line),0,2) == "//")) continue;
		$table[] = $line;
		}
	$lines = $table;
	$i = 0;
	$data = array();
	foreach(array("PrototypeTickKey","PrototypeTickChannel","PrototypeTickVelocity") as $key)
		$data[$key] = so_line_to_value("int",($i < count($lines)) ? $lines[$i++] : NULL,$key);
	$data['CsoundInstrumentsFile'] = so_line_to_value("text",($i < count($lines)) ? $lines[$i++] : NULL,"CsoundInstrumentsFile");
	so_line_to_value("int",($i < count($lines)) ? $lines[$i++] : NULL,"maxsounds"); // Number of prototypes, not used
	$data['PrototypesComment'] = '';
	$data['Prototypes'] = array();
	while($i < count($lines)) {
		$line = trim($lines[$i++]);
		if($line == '' OR $line == "_endSoundObjectFile_" OR $line == "TABLES:") break;
		if($line == "DATA:") {
			if($i < count($lines)) $data['PrototypesComment'] = decode_tags(so_strip_html($lines[$i]));
			break;
			}
		$p = array("Name" => so_strip_html($line));
		so_read_prototype_lines($lines,$i,$p);
		$line = ($i < count($lines)) ? trim($lines[$i++]) : NULL;
		if($line !== "_beginCsoundScore_") throw new Exception("‘_beginCsoundScore_’ not found in ‘".$p['Name']."’");
		$text = '';
		while(TRUE) {
			if($i >= count($lines)) throw new Exception("‘_endCsoundScore_’ not found in ‘".$p['Name']."’");
			$line = rtrim($lines[$i++],"\r");
			if(trim($line) == "_endCsoundScore_") break;
			$text .= so_score_to_text($line)."\n";
			}
		$p["Csound score"] = so_text_to_score($text);
		$number = so_line_to_value("int",($i < count($lines)) ? $lines[$i++] : NULL,"number of MIDI codes in ‘".$p['Name']."’");
		$p['MIDIcodes'] = array();
		for($n = 1; $n <= $number; $n++)
			$p['MIDIcodes'][] = so_line_to_value("int",($i < count($lines)) ? $lines[$i++] : NULL,"MIDI code #".$n." in ‘".$p['Name']."’");
		$line = ($i < count($lines)) ? $lines[$i++] : NULL;
		if($line === NULL OR !is_integer(stripos($line,"<HTML>"))) throw new Exception("comment not found in ‘".$p['Name']."’");
		$p["Comment"] = decode_tags(so_strip_html($line));
		$data['Prototypes'][] = $p;
		}
	return $data;
	}

function so_prototype_json($p) {
	// Returns the JSON object of a prototype given as values indexed by their keys
	$x = array();
	foreach(so_prototype_fields() as $key => $field) {
		list($label,$type,$default,$unit) = $field;
		$value = so_json_value($type,cs_get($p,$key,$default));
		if(str_starts_with($type,"enum:")) $unit = is_string($value) ? "enum" : '';
		$x[$label] = cs_item($key,$value,$unit);
		}
	$x["Csound score"] = strval(cs_get($p,"Csound score","<HTML></HTML>"));
	$codes = cs_get($p,'MIDIcodes',array());
	$x["Size of MIDI code"] = cs_item("MIDIcodesize",count($codes));
	foreach(array_values($codes) as $n => $code) {
		$code = intval($code);
		$x["byte_".($n + 1)] = $code % 256;
		$x["time_".($n + 1)] = intdiv($code,256);
		}
	$x["Comment"] = strval(cs_get($p,"Comment",''));
	return $x;
	}

function so_data_to_json($file_header,$data) {
	// Returns the JSON text of sound-object prototypes. To add a value to the file, add it here and read it by its key in objects.php.
	$json = array();
	$json['header'] = $file_header;
	$json["Prototype tick key"] = cs_item("PrototypeTickKey",intval(cs_get($data,"PrototypeTickKey",84)));
	$json["Prototype tick channel"] = cs_item("PrototypeTickChannel",intval(cs_get($data,"PrototypeTickChannel",1)));
	$json["Prototype tick velocity"] = cs_item("PrototypeTickVelocity",intval(cs_get($data,"PrototypeTickVelocity",64)));
	$json["Csound resource file"] = cs_item("CsoundInstrumentsFile",strval(cs_get($data,"CsoundInstrumentsFile",'')));
	$json["Comment on this file"] = cs_item("PrototypesComment",strval(cs_get($data,"PrototypesComment",'')));
	$prototypes = array();
	foreach(cs_get($data,'Prototypes',array()) as $p) $prototypes[] = so_prototype_json($p);
	$json["Prototypes"] = $prototypes;
	return json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)."\n";
	}

function so_values($object) {
	// Returns the values of a JSON object indexed by their "key". Items without a key are indexed by their label.
	$values = array();
	if(!is_array($object)) return $values;
	foreach($object as $label => $item) {
		if(is_array($item) AND isset($item['key']) AND array_key_exists('value',$item)) $values[$item['key']] = $item['value'];
		else $values[$label] = $item;
		}
	return $values;
	}

function so_prototype_values($object) {
	// Returns the values of a prototype read in JSON, with its MIDI codes in 'MIDIcodes'
	$p = so_values($object);
	$codes = array();
	$number = intval(cs_get($p,"MIDIcodesize",0));
	for($n = 1; $n <= $number; $n++) {
		if(!isset($p["byte_".$n]) OR !isset($p["time_".$n])) break;
		$codes[] = 256 * intval($p["time_".$n]) + intval($p["byte_".$n]);
		unset($p["byte_".$n],$p["time_".$n]);
		}
	$p['MIDIcodes'] = $codes;
	if(!array_key_exists("CsoundInstrumentMode",$p) AND array_key_exists("CsoundInstr",$p))
		$p["CsoundInstrumentMode"] = 1; // As in the console export, which only gives the instrument when one is specified
	return $p;
	}

function so_read_resource($content) {
	// Returns sound-object prototypes as an array of values indexed by keys, or an error message.
	// A file in the former line format is converted.
	if(csound_is_json($content)) {
		$json = csound_json_decode($content);
		if($json === FALSE) return "This file is not valid JSON: ".json_last_error_msg();
		$data = so_values($json);
		$prototypes = array();
		if(isset($data['Prototypes']) AND is_array($data['Prototypes']))
			foreach($data['Prototypes'] as $object) $prototypes[] = so_prototype_values($object);
		$data['Prototypes'] = $prototypes;
		return $data;
		}
	$extract_data = extract_data(FALSE,FALSE,$content);
	try {
		$data = so_lines_to_data(explode(chr(10),$extract_data['content']));
		}
	catch(Exception $e) {
		return "This file could not be read: ".$e->getMessage();
		}
	$data['header'] = str_replace("<br />","\n",$extract_data['headers']);
	return $data;
	}

function convert_prototypes_to_json($path,$dir_trash) {
	// 2026-11-12 Converts to JSON a sound-object prototypes file ("-so") in the former line format.
	// The original is copied to the trash as NAME_old_format. Returns TRUE if converted, FALSE if it was not needed, or an error message.
	global $top_header,$permissions;
	$content = @file_get_contents($path);
	if($content === FALSE OR trim($content) == '' OR csound_is_json($content)) return FALSE;
	$data = so_read_resource($content);
	if(!is_array($data)) return $data;
	$header = ($data['header'] <> '') ? $data['header'] : $top_header;
	$header .= "\n// Converted to JSON format. Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$json = so_data_to_json($header,$data);
	$table = explode(SLASH,$path);
	$thisfile = end($table);
	if($dir_trash <> '' AND is_dir($dir_trash)) @copy($path,$dir_trash.$thisfile."_old_format");
	$temp_file = dirname($path).SLASH.".converting".$thisfile; // Replaces the file in a single step
	if(@file_put_contents($temp_file,$json) === FALSE) return "Could not write ".$temp_file;
	@chmod($temp_file,$permissions);
	if(!@rename($temp_file,$path)) {
		@unlink($temp_file);
		return "Could not replace ".$path;
		}
	return TRUE;
	}

// 2026-11-12 Tonality resources ("-to" files) are stored in JSON format.
// Each scale is an object of values read by their key. Values that are missing are not used, as missing lines in the former format.
// The temporary files edited by scale.php keep the former line format, see to_scale_lines().

function to_scale_fields() {
	// Values of a scale in the order of the JSON file and of the temporary scale files: key => array(label, type, unit, first character of its line)
	// A line of type "table" holds the values whose type is "table" (Csound GEN51 function table).
	$f = array();
	$f["ScaleName"] = array("Name of this scale","name",'','"');
	$f["NoteNames"] = array("Note names","words",'','/');
	$f["Comma"] = array("Comma: size in cents, then ratio","numbers","cents",'c');
	$f["KeyNumbers"] = array("Key numbers","integers",'','k');
	$f["Fractions"] = array("Ratios as fractions","fractions",'','[');
	$f["Series"] = array("Series","words",'','s');
	$f["BaseOctave"] = array("Base octave","integer",'','|');
	$f["TableNumber"] = array("Csound table number","table",'','f');
	$f["TableStart"] = array("Csound table start time","table",'','f');
	$f["TableSize"] = array("Csound table size","table",'','f');
	$f["TableGEN"] = array("Csound GEN routine","table",'','f');
	$f["NumGrades"] = array("Number of grades","table",'','f');
	$f["Interval"] = array("Interval (ratio of the period)","table",'','f');
	$f["BaseFrequency"] = array("Base frequency","table","Hz",'f');
	$f["BaseKey"] = array("Base key","table",'','f');
	$f["Ratios"] = array("Ratios","table",'','f');
	$f["ScaleComment"] = array("Comment on this scale","comment",'','<');
	return $f;
	}

function to_table_keys() {
	// Values of the GEN51 table line "f2 0 128 -51 numgrades interval basefrequency basekey ratio ratio ..."
	return array("TableNumber","TableStart","TableSize","TableGEN","NumGrades","Interval","BaseFrequency","BaseKey");
	}

function to_number($token,$key) {
	// Returns the number of a token, or throws an exception
	if(!is_numeric($token)) throw new Exception("‘".$key."’ should contain numbers, found ‘".$token."’");
	return (strpbrk($token,".eE") === FALSE) ? intval($token) : floatval($token);
	}

function to_number_string($x) {
	// Shortest text giving the same number
	if(is_int($x)) return strval($x);
	return json_encode(floatval($x),JSON_PRESERVE_ZERO_FRACTION);
	}

function to_tokens($text) {
	$text = trim($text);
	if($text == '') return array();
	return preg_split("/\s+/u",$text);
	}

function to_read_line($line,&$scale) {
	// Reads a line of the former format into $scale (values indexed by their keys), or throws an exception
	$line = trim($line);
	if($line == '') return;
	$c = $line[0];
	if($c == '"') {
		$scale["ScaleName"] = trim(str_replace('"','',$line));
		return;
		}
	if($c == '<') {
		$text = preg_replace("/^<html>/iu",'',$line);
		$text = preg_replace("/<\/html>$/iu",'',$text);
		$scale["ScaleComment"] = str_replace("<br />","\n",$text);
		return;
		}
	if($c == 'f') {
		$tokens = to_tokens(substr($line,1));
		if(count($tokens) < 9) throw new Exception("incomplete table ‘".$line."’");
		foreach(to_table_keys() as $k => $key) $scale[$key] = to_number($tokens[$k],$key);
		$scale["Ratios"] = array();
		for($k = 8; $k < count($tokens); $k++) $scale["Ratios"][] = to_number($tokens[$k],"Ratios");
		return;
		}
	$closing = array('/' => '/','c' => 'c','k' => 'k','[' => ']','s' => 's','|' => '|');
	if(!isset($closing[$c]) OR strlen($line) < 2 OR substr($line,-1) != $closing[$c])
		throw new Exception("unknown line ‘".$line."’");
	$inside = substr($line,1,-1);
	switch($c) {
		case '/': $scale["NoteNames"] = to_tokens($inside); break;
		case 's': $scale["Series"] = to_tokens($inside); break;
		case 'c':
			$scale["Comma"] = array();
			foreach(to_tokens($inside) as $t) $scale["Comma"][] = to_number($t,"Comma");
			break;
		case 'k':
			$scale["KeyNumbers"] = array();
			foreach(to_tokens($inside) as $t) $scale["KeyNumbers"][] = intval(to_number($t,"KeyNumbers"));
			break;
		case '[':
			$tokens = to_tokens($inside);
			if(count($tokens) % 2 != 0) throw new Exception("odd number of values in fractions ‘".$line."’");
			$scale["Fractions"] = array();
			for($k = 0; $k < count($tokens); $k += 2)
				$scale["Fractions"][] = array(intval(to_number($tokens[$k],"Fractions")),intval(to_number($tokens[$k + 1],"Fractions")));
			break;
		case '|': $scale["BaseOctave"] = intval(to_number($inside,"BaseOctave")); break;
		}
	}

function to_scale_lines($scale) {
	// Returns the lines of a scale in the former format (temporary scale files), in the order of to_scale_fields(). Missing values give no line.
	$lines = array();
	if(isset($scale["ScaleName"])) $lines[] = "\"".$scale["ScaleName"]."\"";
	if(isset($scale["NoteNames"])) $lines[] = "/".implode(' ',$scale["NoteNames"])."/";
	if(isset($scale["Comma"])) $lines[] = "c".implode(' ',array_map('to_number_string',$scale["Comma"]))."c";
	if(isset($scale["KeyNumbers"])) $lines[] = "k".implode(' ',array_map('intval',$scale["KeyNumbers"]))."k";
	if(isset($scale["Fractions"])) {
		$tokens = array();
		foreach($scale["Fractions"] as $fraction) { $tokens[] = intval($fraction[0]); $tokens[] = intval($fraction[1]); }
		$lines[] = "[".implode(' ',$tokens)."]";
		}
	if(isset($scale["Series"])) $lines[] = "s".implode(' ',$scale["Series"])."s";
	if(isset($scale["BaseOctave"])) $lines[] = "|".intval($scale["BaseOctave"])."|";
	if(isset($scale["Ratios"])) {
		$tokens = array();
		foreach(to_table_keys() as $key) $tokens[] = to_number_string(cs_get($scale,$key,0));
		foreach($scale["Ratios"] as $x) $tokens[] = to_number_string($x);
		$lines[] = "f".implode(' ',$tokens);
		}
	if(isset($scale["ScaleComment"])) $lines[] = "<html>".str_replace("\n","<br />",$scale["ScaleComment"])."</html>";
	return $lines;
	}

function to_lines_to_scale($lines) {
	// Reads the lines of a temporary scale file, or throws an exception
	$scale = array();
	foreach($lines as $line) to_read_line($line,$scale);
	return $scale;
	}

function to_lines_to_data($lines) {
	// Parses a tonality resource in the former line format, as read by LoadTonality() in the console. Returns an array, or throws an exception.
	// As in tonality.php, a table without a name gets the name "scale_N", and lines after a table, except its comment, belong to the next scale.
	$data = array('scales' => array());
	$found = FALSE;
	$scale = $pending = array();
	$done_table = FALSE;
	foreach($lines as $line) {
		$line = trim($line);
		if($line == '') continue;
		if(!$found) {
			if($line == "_begin tables") $found = TRUE;
			continue;
			}
		if($line == "_end tables") break;
		if($line[0] == '"') {
			if(count($scale) > 0) $data['scales'][] = $scale;
			$scale = $pending; $pending = array();
			to_read_line($line,$scale);
			$done_table = FALSE;
			continue;
			}
		if($line[0] == '<') {
			if($done_table) to_read_line($line,$scale);
			continue;
			}
		if($line[0] == 'f') {
			if($done_table) {
				$data['scales'][] = $scale;
				$scale = $pending; $pending = array();
				}
			if(!isset($scale["ScaleName"])) $scale["ScaleName"] = "scale_".(count($data['scales']) + 1);
			to_read_line($line,$scale);
			$done_table = TRUE;
			continue;
			}
		if($done_table) to_read_line($line,$pending);
		else to_read_line($line,$scale);
		}
	if(count($scale) > 0) $data['scales'][] = $scale;
	return $data;
	}

function to_scale_json($scale) {
	// Returns the JSON object of a scale given as values indexed by their keys
	$x = array();
	foreach(to_scale_fields() as $key => $field) {
		if(!array_key_exists($key,$scale)) continue;
		$x[$field[0]] = cs_item($key,$scale[$key],$field[2]);
		}
	return $x;
	}

function to_data_to_json($file_header,$data) {
	// Returns the JSON text of a tonality resource. To add a value, add it to to_scale_fields() or here, and read it by its key.
	$json = array();
	$json['header'] = $file_header;
	$scales = array();
	foreach($data['scales'] as $scale) $scales[] = to_scale_json($scale);
	$json["Tonal scales"] = cs_item("Scales",$scales);
	return json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)."\n";
	}

function to_read_resource($content) {
	// Returns a tonality resource as array('header' => ..., 'scales' => array of scales), or an error message.
	// A file in the former line format is converted.
	if(csound_is_json($content)) {
		$json = csound_json_decode($content);
		if($json === FALSE) return "This file is not valid JSON: ".json_last_error_msg();
		$data = array('header' => cs_get($json,'header',''),'scales' => array());
		$scales = cs_get(cs_values($json),"Scales",array());
		if(is_array($scales)) foreach($scales as $object) $data['scales'][] = cs_values($object);
		return $data;
		}
	$extract_data = extract_data(FALSE,FALSE,$content);
	try {
		$data = to_lines_to_data(explode(chr(10),str_replace("\r",'',$content)));
		}
	catch(Exception $e) {
		return "This file could not be read: ".$e->getMessage();
		}
	$data['header'] = str_replace("<br />","\n",$extract_data['headers']);
	return $data;
	}

function export_scala($dir_scales,$scale_name) {
	// 2026-11-18 Writes the SCALA file of a scale from its temporary file (former line format). Called by scale.php and by the "Export to SCALA" button of tonality.php, so that both exports are identical
	// All grades are exported, including unnamed ones ('•'), which are written without a name
	global $permissions;
	$content = @file_get_contents($dir_scales.to_scale_file_name($scale_name));
	if($content === FALSE) return FALSE;
	$names = $fractions = $ratios = array();
	$numgrades = 0; $comment = '';
	foreach(explode("\n",$content) as $line) {
		$line = trim($line);
		if($line == '') continue;
		switch($line[0]) {
			case '/':
				$names = explode(' ',preg_replace("/\s+/u",' ',trim(str_replace('/','',$line))));
				break;
			case '[':
				$fractions = explode(' ',preg_replace("/\s+/u",' ',trim(str_replace(array('[',']'),'',$line))));
				break;
			case 'f':
				$table = explode(' ',preg_replace("/\s+/u",' ',$line));
				$numgrades = intval($table[4]);
				$ratios = array_slice($table,8);
				break;
			case '<':
				$comment = $line;
				break;
			}
		}
	if($numgrades < 1) return FALSE;
	$text = "! ".$scale_name.".scl\n";
	$text .= "! Scala file, ref. https://www.huygens-fokker.org/scala/scl_format.html\n";
	$description = '';
	if(strlen($comment) > 5) {
		$description = html_to_text($comment,'txt');
		if(is_integer($pos = strpos($description,"<br />"))) $description = substr($description,0,$pos);
		$description = trim(str_replace("-cs.","-to.",$description));
		}
	if($description == '') $description = "This scale is called '".$scale_name."'";
	$text .= $description."\n";
	$text .= "! Created by the Bol Processor\n";
	$text .= $numgrades."\n";
	// Degree 0 (1/1) is implicit in SCALA files, so the name of the first note is given in a comment, read again by create_from_scl()
	if(isset($names[0]) AND $names[0] <> '' AND $names[0] <> "•") $text .= "! 1/1 ".$names[0]."\n";
	for($k = 1; $k <= $numgrades; $k++) {
		$name = (isset($names[$k]) AND $names[$k] <> "•") ? $names[$k] : '';
		$p = isset($fractions[2 * $k]) ? intval($fractions[2 * $k]) : 0;
		$q = isset($fractions[(2 * $k) + 1]) ? intval($fractions[(2 * $k) + 1]) : 0;
		if(($p * $q) > 0) $line = $p."/".$q;
		else {
			$ratio = isset($ratios[$k]) ? floatval($ratios[$k]) : 0.;
			if($ratio <= 0.) $ratio = pow(2,$k / $numgrades);
			$line = round(1200 * log($ratio) / log(2))." cents";
			}
		$text .= trim($line." ".$name)."\n";
		}
	$file = $dir_scales.$scale_name.".scl";
	if(@file_get_contents($file) !== $text) {
		file_put_contents($file,$text);
		@chmod($file,$permissions);
		}
	return $file;
	}

function to_scale_file_name($scale_name) {
	// Name of the temporary file of a scale, as in tonality.php
	$clean_name_of_file = str_replace("#","_",$scale_name);
	return str_replace("/","_",$clean_name_of_file).".txt";
	}

function to_add_scales_to_file($path,$scale_files) {
	// Adds the scales of temporary scale files to the tonality resource $path. Returns array(name => TRUE if added, FALSE if the name already exists), or an error message
	global $top_header,$permissions;
	if(read_only_resource_folder(dirname($path))) return "‘".basename($path)."’ is a fixed file that can't be changed"; // 2026-11-19
	$content = @file_get_contents($path);
	if($content === FALSE) return "Cannot read ".$path;
	$data = to_read_resource($content);
	if(!is_array($data)) return $data;
	$names = array();
	foreach($data['scales'] as $scale) $names[cs_get($scale,"ScaleName",'')] = TRUE;
	$result = array();
	try {
		foreach($scale_files as $scale_file) {
			$scale = to_lines_to_scale(explode(chr(10),file_get_contents($scale_file)));
			$name = cs_get($scale,"ScaleName",'');
			if(isset($names[$name])) {
				$result[$name] = FALSE;
				continue;
				}
			$data['scales'][] = $scale;
			$names[$name] = $result[$name] = TRUE;
			}
		}
	catch(Exception $e) {
		return "A scale could not be read: ".$e->getMessage();
		}
	$header = ($data['header'] <> '') ? $data['header'] : $top_header;
	file_put_contents($path,to_data_to_json($header,$data));
	@chmod($path,$permissions);
	return $result;
	}

function convert_tonality_resources_to_json($dir,$dir_trash) {
	// 2026-11-12 Converts to JSON all tonality resources ("-to" files) of $dir that are in the former line format.
	// The original is copied to the trash as NAME_old_format. Returns the names of converted files.
	global $top_header,$permissions;
	$converted = array();
	$dircontent = @scandir($dir);
	if($dircontent === FALSE) return $converted;
	foreach($dircontent as $thisfile) {
		if(!str_starts_with($thisfile,"-to") AND !str_ends_with($thisfile,".bpto")) continue;
		if(preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		$path = $dir.$thisfile;
		if(!is_file($path)) continue;
		if(file_exists($path."_lock") OR file_exists($path."_lock3")) continue; // Being saved
		$handle = @fopen($path,"r");
		if(!$handle) continue;
		$start = fread($handle,64);
		fclose($handle);
		if($start === FALSE OR trim($start) == '' OR csound_is_json($start)) continue;
		$content = @file_get_contents($path);
		if($content === FALSE) continue;
		$data = to_read_resource($content);
		if(!is_array($data)) {
			error_log("convert_tonality_resources_to_json(): ".$thisfile." could not be converted: ".$data);
			continue;
			}
		$header = ($data['header'] <> '') ? $data['header'] : $top_header;
		$header .= "\n// Converted to JSON format. Date: ".gmdate('Y-m-d H:i:s')." GMT";
		$json = to_data_to_json($header,$data);
		if($dir_trash <> '' AND is_dir($dir_trash)) @copy($path,$dir_trash.$thisfile."_old_format");
		$temp_file = $dir.".converting".$thisfile; // Replaces the file in a single step
		if(@file_put_contents($temp_file,$json) === FALSE) continue;
		@chmod($temp_file,$permissions);
		if(@rename($temp_file,$path)) $converted[] = $thisfile;
		else @unlink($temp_file);
		}
	return $converted;
	}

// 2026-11-12 Time base files ("-tb") are stored in JSON format.
// Each track is an object of values read by their key. Missing values take the defaults of tb_track_fields().

define('TB_MAXBEATS',40); // Number of beats (check boxes) of each track. A cycle is limited to this number.

function tb_track_fields() {
	// Values of a track in the order of the JSON file and of the former line format: key => array(label, default, unit, minimum, maximum)
	$f = array();
	$f["TickKey"] = array("Key",60,'',0,127);
	$f["TickChannel"] = array("MIDI channel",1,'',1,16);
	$f["TickVelocity"] = array("Velocity",64,'',0,127);
	$f["TickCycle"] = array("Cycle",4,"beats",1,TB_MAXBEATS);
	$f["Ptick"] = array("Speed ratio: numerator",1,'',1,NULL);
	$f["Qtick"] = array("Speed ratio: denominator",1,'',1,NULL);
	$f["TickDuration"] = array("Duration of a tick",50,"ms",0,NULL);
	return $f;
	}

function tb_new_track() {
	// Values of a new track (button ADD ANOTHER TRACK)
	$track = array();
	foreach(tb_track_fields() as $key => $field) $track[$key] = $field[1];
	$track["Ticks"] = array_fill(0,TB_MAXBEATS,0);
	return $track;
	}

function tb_check_track($track) {
	// Returns the values of a track with defaults for missing values, numbers within their limits, a reduced speed ratio, and TB_MAXBEATS ticks equal to 0 or 1
	$checked = array();
	foreach(tb_track_fields() as $key => $field) {
		list($label,$default,$unit,$min,$max) = $field;
		$x = cs_get($track,$key,$default);
		$x = is_numeric($x) ? intval($x) : $default;
		if($min !== NULL AND $x < $min) $x = $min;
		if($max !== NULL AND $x > $max) $x = $max;
		$checked[$key] = $x;
		}
	$g = gcd($checked["Ptick"],$checked["Qtick"]);
	if($g > 1) {
		$checked["Ptick"] = intdiv($checked["Ptick"],$g);
		$checked["Qtick"] = intdiv($checked["Qtick"],$g);
		}
	$ticks = cs_get($track,"Ticks",array());
	if(!is_array($ticks)) $ticks = array();
	$checked["Ticks"] = array();
	for($i = 0; $i < TB_MAXBEATS; $i++) // A tick is on if its value is odd, as in BP2
		$checked["Ticks"][$i] = (isset($ticks[$i]) AND is_numeric($ticks[$i]) AND intval($ticks[$i]) % 2 <> 0) ? 1 : 0;
	return $checked;
	}

function tb_lines_to_data($content) {
	// Parses a time base in the former line format, as read by LoadTimeBase() in BP2:
	// header lines, number of tracks, number of beats, then for each track: type (ignored), number of arguments (4, 6 or 7),
	// key, channel, velocity, cycle, [numerator, denominator of speed ratio], [duration], ticks; then the comment.
	// Returns array('header' => ..., 'tracks' => ..., 'comment' => ...), or throws an exception.
	$content = str_replace("\xEF\xBB\xBF",'',$content);
	$lines = explode("\n",str_replace(array("\r\n","\r"),"\n",$content));
	$header = array();
	$n = count($lines);
	$j = 0;
	while($j < $n AND !preg_match('/^-?[0-9]+$/',trim($lines[$j]))) { // Header lines, e.g. "// Bol Processor..." or "V.2.6" and "Date:..." in old files
		$line = trim($lines[$j++]);
		if($line == '') continue;
		if(!str_starts_with($line,"//")) $line = "// ".$line;
		$header[] = $line;
		}
	$next = function($what) use ($lines,$n,&$j) {
		while($j < $n AND trim($lines[$j]) == '') $j++;
		if($j >= $n) throw new Exception("missing ".$what);
		$line = trim($lines[$j++]);
		if(!preg_match('/^-?[0-9]+$/',$line)) throw new Exception("“".$line."” is not an integer (".$what.", line ".$j.")");
		return intval($line);
		};
	$maxticks = $next("number of tracks");
	$maxbeats = $next("number of beats");
	if($maxticks < 0 OR $maxticks > 100 OR $maxbeats < 0 OR $maxbeats > TB_MAXBEATS) throw new Exception("incorrect numbers of tracks (".$maxticks.") or beats (".$maxbeats.")");
	$tracks = array();
	for($i = 0; $i < $maxticks; $i++) {
		$next("type of track ".($i + 1));
		$arg = $next("number of arguments of track ".($i + 1));
		$track = array();
		foreach(array("TickKey","TickChannel","TickVelocity","TickCycle") as $key) $track[$key] = $next($key." of track ".($i + 1));
		if($arg > 4) {
			$track["Ptick"] = $next("Ptick of track ".($i + 1));
			$track["Qtick"] = $next("Qtick of track ".($i + 1));
			}
		if($arg > 6) $track["TickDuration"] = $next("TickDuration of track ".($i + 1));
		$track["Ticks"] = array();
		for($k = 0; $k < $maxbeats; $k++) $track["Ticks"][] = $next("tick ".($k + 1)." of track ".($i + 1));
		$tracks[] = tb_check_track($track);
		}
	$comment = array();
	for(; $j < $n; $j++) {
		$line = trim($lines[$j]);
		if($line <> '') $comment[] = $line;
		}
	$comment = implode(" ",$comment);
	$comment = trim(preg_replace('/^DATA:/u','',$comment));
	$comment = trim(str_ireplace(array("<HTML>","</HTML>"),'',$comment));
	$comment = decode_tags($comment);
	return array('header' => implode("\n",$header),'tracks' => $tracks,'comment' => $comment);
	}

function tb_data_to_json($file_header,$data) {
	// Returns the JSON text of a time base. To add a value, add it to tb_track_fields() or here, and read it by its key.
	$fields = tb_track_fields();
	$tracks = array();
	foreach($data['tracks'] as $track) {
		$track = tb_check_track($track);
		$x = array();
		foreach($fields as $key => $field) $x[$field[0]] = cs_item($key,$track[$key],$field[2]);
		$x["Beats with a tick (1) or without (0)"] = cs_item("Ticks",$track["Ticks"]);
		$tracks[] = $x;
		}
	$json = array();
	$json['header'] = $file_header;
	$json["Tracks"] = cs_item("Tracks",$tracks);
	$json["Comment on this time base"] = cs_item("Comment",$data['comment']);
	return json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
	}

function tb_read_resource($content) {
	// Returns a time base as array('header' => ..., 'tracks' => array of tracks, 'comment' => ...), or an error message.
	// A file in the former line format is converted.
	if(csound_is_json($content)) {
		$json = csound_json_decode($content);
		if($json === FALSE) return "This file is not valid JSON: ".json_last_error_msg();
		$values = cs_values($json);
		$data = array('header' => cs_get($json,'header',''),'tracks' => array(),'comment' => strval(cs_get($values,"Comment",'')));
		$tracks = cs_get($values,"Tracks",array());
		if(is_array($tracks)) foreach($tracks as $object) $data['tracks'][] = tb_check_track(cs_values($object));
		return $data;
		}
	try {
		return tb_lines_to_data($content);
		}
	catch(Exception $e) {
		return "This file could not be read: ".$e->getMessage();
		}
	}

function convert_timebase_to_json($path,$dir_trash) {
	// 2026-11-12 Converts to JSON a time base file ("-tb") in the former line format.
	// The original is copied to the trash as NAME_old_format. Returns TRUE if converted, FALSE if it was not needed, or an error message.
	global $top_header,$permissions;
	$content = @file_get_contents($path);
	if($content === FALSE OR trim($content) == '' OR csound_is_json($content)) return FALSE;
	$data = tb_read_resource($content);
	if(!is_array($data)) return $data;
	$header = ($data['header'] <> '') ? $data['header'] : $top_header;
	$header .= "\n// Converted to JSON format. Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$json = tb_data_to_json($header,$data);
	$table = explode(SLASH,$path);
	$thisfile = end($table);
	if($dir_trash <> '' AND is_dir($dir_trash)) @copy($path,$dir_trash.$thisfile."_old_format");
	$temp_file = dirname($path).SLASH.".converting".$thisfile; // Replaces the file in a single step
	if(@file_put_contents($temp_file,$json) === FALSE) return "Could not write ".$temp_file;
	@chmod($temp_file,$permissions);
	if(!@rename($temp_file,$path)) {
		@unlink($temp_file);
		return "Could not replace ".$path;
		}
	return TRUE;
	}

function reformat_grammar($verbose,$this_file) {
	global $temp_weights_file,$section_headers;
	if(!file_exists($this_file)) return;
	$content = @file_get_contents($this_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$new_content = $content;
	$i_gram = $i_rul = 1;
	$table = explode(chr(10),$new_content);
	$ignore_all = FALSE;
	$i_line_max = count($table);
	if(isset($temp_weights_file)) @unlink($temp_weights_file);
	for($i_line = 0; $i_line < $i_line_max; $i_line++) {
		$line = trim($table[$i_line]);
		$found_rule = FALSE;
		$line_no_brackets = preg_replace("/\s*?\[.*\]/u",'',$line);
		$ignore = FALSE;
		if($line_no_brackets == '') $ignore = TRUE;
		if(!is_integer(strpos($line,"-->")) AND !is_integer(strpos($line,"<->")) AND !is_integer(strpos($line,"<--"))) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"--")) AND $pos == 0) {
			$i_gram++; $i_rul = 1;
			$ignore = TRUE;
			}
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"_")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"[")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=stripos($line,"gram#")) AND $pos == 0) {
			$ignore = TRUE;
			$line = preg_replace("/^GRAM#/u","gram#",$line);
			$line = preg_replace("/^gram#([0-9]+)\[([0-9]+)\]/u","gram#".$i_gram."[".$i_rul."]",$line);
			$found_rule = TRUE;
			$i_rul++;
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
			$line = "gram#".$i_gram."[".$i_rul."] ".$line;
			$found_rule = TRUE;
			$i_rul++;
			}
		if($verbose) echo $line."<br />";
		if($found_rule) {
			$this_weight = get_weight($line);
		//	echo "<".$this_weight."> ".$line."<br />";
			$this_data['igram'] = $i_gram;
			$this_data['irul'] = $i_rul - 1;
			$this_data['weight'] = $this_weight;
			save_triplet($this_data,$temp_weights_file);
			}
		$table[$i_line] = $line;
		}
	$new_content = implode(chr(10),$table);
	// $this_file = "-gr._test";
	$handle = @fopen($this_file,"w");
	if($handle) {
		fwrite($handle,$new_content);
		fclose($handle);
		}
	else echo "<p>⚠️ This file cannot be modified because it is write-protected</p>";
	return;
	}

function save_triplet($data, $filename) {
    $json = json_encode($data,JSON_UNESCAPED_SLASHES);
    file_put_contents($filename,$json.PHP_EOL,FILE_APPEND);
	}

function get_weight($line) {
 //   if(preg_match('/<(inf|\d+)>/i', $line, $matches)) {
    if(preg_match('/<([^>]+)>/i', $line, $matches)) {
        if($matches[1] <> "-") return $matches[1];
		// When the derivation mode is "<->" it could be mistaken for a weight
    	}
    return 127; // default value
	}

function SaveTonality($verbose,$dir,$filename,$temp_folder,$force) {
	global $top_header, $test, $temp_dir, $permissions;
	$verbose = FALSE;
	if($verbose) echo "<br />dir = ".$dir."<br />";
	if($verbose) echo "filename = ".$filename."<br />";
	if($verbose) echo "temp_folder = ".$temp_folder."<br />";
	if(read_only_resource_folder($dir)) return "read-only"; // 2026-11-19 Fixed tonality resource
	$file_lock3 = $dir.$filename."_lock3";
//	if($verbose) echo "<br />file_lock3 = ".$file_lock3."<br />";
	if(file_exists($file_lock3)) return "locked";
	$file_lock = $dir.$filename."_lock";
	$folder_scales = $temp_dir.$temp_folder.SLASH."scales";
	// 2026-11-10 Without the temporary folder (deleted as old), the file would be saved without its scales
	if(!is_dir($folder_scales)) return "missing";
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
	// 2026-11-12 Tonality resources are saved in JSON format. Scales are read from the temporary files edited by scale.php
	$file_header = $top_header."\n// Tonality resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$data = array('scales' => array());
	try {
		$dir_scale = scandir($folder_scales);
		foreach($dir_scale as $this_scale) {
			if($this_scale == '.' OR $this_scale == ".." OR $this_scale == ".DS_Store") continue;
			$table = explode(".",$this_scale);
			$extension = end($table);
			if($extension <> "txt") continue;
			$scale = to_lines_to_scale(explode(chr(10),file_get_contents($folder_scales.SLASH.$this_scale)));
			if(count($scale) > 0) $data['scales'][] = $scale;
			}
		}
	catch(Exception $e) { // The file is left unchanged
		error_log("SaveTonality(): ".$e->getMessage());
		unlink($file_lock);
		return "error";
		}
	@unlink($dir.$filename);
	file_put_contents($dir.$filename,to_data_to_json($file_header,$data));
	chmod($dir.$filename,$permissions);
	unlink($file_lock);
	return "saved";
	}

function to_temp_changed($dir_scales) {
	// 2026-11-18 A scale changed on scale.php is saved at once to its tonality resource file. Otherwise, the change was lost if the '-to' page was reloaded before its autosave
	$handle = fopen($dir_scales."_changed","w");
	if($handle) fclose($handle);
	$temp_path = dirname($dir_scales);
	$source = @file($temp_path.SLASH."_source",FILE_IGNORE_NEW_LINES);
	if(!is_array($source) OR count($source) < 2) return "unknown";
	return SaveTonality(FALSE,$source[0],$source[1],basename($temp_path),FALSE);
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
	if(file_exists($file)) $content = @file_get_contents($file);
	if(!$content) {
		$message .= "<br /><span class=\"red-text\">Cannot find or open:</span> <span class=\"green-text\">".$file."</span>";
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
	$content = @file_get_contents($midi_bytes);
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
	global $permissions;
	if(file_exists($dst)) my_rmdir($dst);
	if(is_dir($src)) {
		mkdir($dst,$permissions,true);
		$files = scandir($src);
		foreach($files as $file) {
			$source = $src.SLASH.$file;
			$destination = $dst.SLASH.$file;
			if($file <> "." AND $file <> "..") rcopy($source,$destination);
			}
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
	$filename = fix_new_name($filename,TRUE);
	$filename = trim($filename);
	$filename = str_ireplace("-".$type.".",'',$filename);
	$filename = str_ireplace(".bp".$type,'',$filename);
	if($name_mode == "extension")
		$filename .= ".bp".$type;
	else
		$filename = "-".$type.".".$filename;
	return $filename;
	}

function fix_new_name($name,$no_slash) {
	$name = str_replace('+','_',$name);
	$name = str_replace('$','_',$name); // 2026-04-21
	$name = str_replace(' ','_',$name);
	$name = str_replace(':','_',$name);
	if($no_slash) $name = str_replace(SLASH,'_',$name);
	$name = str_replace('"',"'",$name);
	return $name;
	}

function type_of_file($thisfile) {
	$thisfile = str_replace("_bak",'',$thisfile);
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
		case '-sc': // 2026-11-20 SuperCollider resources. '-sc' was an old prefix of scripts, which are now '+sc'
			$type = "supercollider"; break;
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
		case "scd": $type = "scsynthdef"; $found = TRUE; break; // 2026-11-20 SuperCollider SynthDefs
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
		$content = file_get_contents($dir.SLASH.$thisfile);
		$table = explode(chr(10),$content);
		$imax = count($table);
		$found = FALSE;
		$new_table = array();
		for($i = 0; $i < $imax; $i++) {
			$line = $table[$i];
			if(is_integer(strpos($line,$old_name))) {
				if($i > 1) echo "• Found ‘<span class=\"green-text\">".$old_name."</span>’ in ‘<span class=\"green-text\">".$thisfile."</span>’ and changed it to ‘<span class=\"green-text\">".$new_name."</span>’<br />";
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
		$content = file_get_contents($dir.SLASH.$thisfile);
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
			case "bpso":
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

function new_name($name,$type) {
	$table = explode('.',$name);
	$newprefix = $newsuffix = '';
	switch(trim($table[0])) {
		case "-ho":
			$newprefix = "-al";
			break;
		case "-mi":
			$newprefix = "-so";
			break;
		case "-or":  // Obsolete "orchestra"
			if($type == "instruction") return '';
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
			case "bpmi":
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

function nice_url($url) {
	$url = str_replace(SLASH,'/',$url);
	return $url;
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
	$r .= "<span class=\"red-text\">".$parameter."</span> continuous fields";
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
	$r .= "</table>";
	$r .= "<table class=\"thinborder\" style=\"margin-bottom:6px;\">";
	$r .= "<tr>";
	$r .= "<td colspan=\"6\">";
	$r .= "▷ <span class=\"red-text\">".$parameter."</span> mapping of variations";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td rowspan = \"2\" style=\"padding:4px; vertical-align:middle; text-align:center;\"><small>MIDI<br /><span class=\"red-text\">▼</span><br />Csound</small></td>";
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

function max_argument($argmax_file, &$names = null) {
    include($argmax_file);
    $max = 0;
    $names = array();
    if(isset($last_argument) && is_array($last_argument)) {
        foreach($last_argument as $value) {
            if($value > $max) $max = $value;
        	}
        $names = array_slice(array_keys($last_argument),1);
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

function stale_temp_folder($temp_folder) {
	// 2026-11-10 Returns TRUE if a form sent from a page of this file ('-cs', '-so', '-to') cannot be used, because the temporary folder built when the page was displayed has been deleted as old (see "Delete old temp directories" above), or belongs to a previous session (e.g. after restarting the browser). Saving it would write an incomplete file.
	// Call it before creating the temporary folder. Forms that change the temporary folder must contain a hidden 'temp_folder' field.
	global $temp_dir;
	if(!isset($_POST['temp_folder'])) return FALSE;
	$posted_folder = $_POST['temp_folder'];
	$base = preg_replace("/_[0-9a-f]{10}_temp$/",'',$temp_folder);
	// Forms sent by pages of other files (e.g. through display_more_buttons()) are not concerned
	if(!preg_match("/^".preg_quote($base,'/')."_[0-9a-f]{10}_temp$/",$posted_folder)) return FALSE;
	if($posted_folder <> $temp_folder) return TRUE;
	return !is_dir($temp_dir.$temp_folder);
	}

function stale_page_message($filename) {
	echo "<p><span class=\"red-text\">➡ This page had been open too long and its temporary data was deleted or belongs to a previous session. Changes were NOT saved, and “".$filename."” has NOT been modified.<br />The page has been reloaded from the file. Please make your changes again.</span></p>";
	return;
	}

function stale_subpage($temp_folder) {
	// 2026-11-10 For pages opened from a '-cs', '-so' or '-to' page, which work in its temporary folder
	global $temp_dir;
	if($temp_folder <> '' AND is_dir($temp_dir.$temp_folder)) return;
	echo "<p><span class=\"red-text\">➡ The temporary data of this page has been deleted because the page it was opened from had been open too long, or belongs to a previous session.<br />Close this tab, then reload the page of the resource file and open this item again.</span></p>";
	echo "</body></html>";
	die();
	}

function list_of_parameter_names($temp_dir,$temp_folder) {
	$test = FALSE;
	$exists_name = array();
	$dirallinstruments = scandir($temp_dir.$temp_folder);
	foreach($dirallinstruments as $name_instrument) {
		$dir_this_instrument = $temp_dir.$temp_folder.SLASH.$name_instrument;
		if(!is_dir($dir_this_instrument) OR $name_instrument == '.' OR $name_instrument == "..") continue;
		if($test) echo "dir_this_instrument = ".$dir_this_instrument."<br />";
		$dircontent = scandir($dir_this_instrument);
		foreach($dircontent as $oldfile) {
			if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
			if($test) echo "oldfile = ".$oldfile."<br />";
			$table = explode(".",$oldfile);
			$extension = end($table);
			if($extension <> "old" AND $extension <> "txt") continue;
			$thisfile = str_replace(".old",'',$oldfile);
			$name = str_replace(".txt",'',$thisfile);
			$exists_name[$name] = TRUE;
			}
		}
	return $exists_name;
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
	if(is_integer(strpos($word,"=")) OR is_integer(strpos($word,":"))) return '';
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
	$word = preg_replace('/GRAM#[0-9]+/u','',$word);
	if(trim($word) == '') return $word;
	if($word[0] == '|' AND $word[strlen($word) - 1] == '|') {
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
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $os_platform = "Unknown OS Platform";
    $os_array = array(
        '/windows nt 10/i'      => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/windows nt 5.0/i'     => 'Windows 2000',
        '/windows me/i'         => 'Windows ME',
        '/win98/i'              => 'Windows 98',
        '/win95/i'              => 'Windows 95',
        '/win16/i'              => 'Windows 3.11',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/linux/i'              => 'Linux',
        '/ubuntu/i'             => 'Ubuntu',
        '/iphone/i'             => 'iPhone',
        '/ipod/i'               => 'iPod',
        '/ipad/i'               => 'iPad',
        '/android/i'            => 'Android',
        '/blackberry/i'         => 'BlackBerry',
        '/webos/i'              => 'Mobile'
    	);
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform = $value;
            break;
			}
		}
    return $os_platform;
	}

function linux_system() {
	$os_platform = getOS();
	if((is_integer(strpos($os_platform,"Linux")) OR is_integer(strpos($os_platform,"Ubuntu")))) return TRUE;
	return FALSE;
	}

function mac_system() {
	$os_platform = getOS();
	if(is_integer(strpos($os_platform,"Mac OS"))) return TRUE;
	return FALSE;
	}

function windows_system() {
	$os_platform = getOS();
	if(PHP_OS == "WINNT" OR is_integer(strpos($os_platform,"Windows"))) return TRUE;
	return FALSE;
	}

function watchdog_start() {
	// 2026-11-22 Starts watchdog.sh, which restarts Apache (MAMP on MacOS, XAMPP on Linux) if it crashed or is stuck, unless it is running.
	// It touches temp_bolprocessor/watchdog.txt at every check (every minute, plus up to 30 s of waiting), so a recent date means it is running.
	// Only when this page is served by the Apache of MAMP or XAMPP: not by the standalone application (PHP Desktop, own server and PHP) even if MAMP is installed
	if(PHP_SAPI == "cli" OR !isset($_SERVER['SERVER_PORT']) OR isset($_SERVER['PHPDESKTOP_VERSION'])) return;
	if(!isset($_SERVER['SERVER_SOFTWARE']) OR stripos($_SERVER['SERVER_SOFTWARE'],"Apache") === FALSE) return;
	if(PHP_OS_FAMILY == "Darwin" AND str_starts_with(PHP_BINARY,"/Applications/MAMP/") AND file_exists("/Applications/MAMP/Library/bin/apachectl")) $server = "mamp";
	else if(PHP_OS_FAMILY == "Linux" AND str_starts_with((string)realpath(__DIR__),"/opt/lampp/") AND file_exists("/opt/lampp/lampp")) $server = "xampp";
	else return;
	$dir = dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor";
	$beat = $dir.DIRECTORY_SEPARATOR."watchdog.txt";
	clearstatcache(TRUE,$beat);
	if(file_exists($beat) AND filemtime($beat) > time() - 150) return;
	@touch($beat); // Other pages loaded meanwhile won't start another one
	// URL of watchdog_ping.php, in the folder of this file
	$url_path = '';
	$document_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : FALSE;
	$here = realpath(__DIR__);
	if($document_root !== FALSE AND $here !== FALSE AND str_starts_with($here,$document_root))
		$url_path = str_replace(DIRECTORY_SEPARATOR,'/',substr($here,strlen($document_root)));
	else if(isset($_SERVER['SCRIPT_NAME'])) $url_path = dirname($_SERVER['SCRIPT_NAME']);
	$url = "http://localhost:".intval($_SERVER['SERVER_PORT']).rtrim($url_path,'/')."/watchdog_ping.php";
	$command = "/bin/sh ".escapeshellarg(__DIR__.DIRECTORY_SEPARATOR."watchdog.sh")." ".$server." ".escapeshellarg($url)." ".escapeshellarg($dir);
	// In a new session, so that it survives the server and its signals
	if($server == "mamp") $command = "/usr/bin/perl -MPOSIX -e 'POSIX::setsid(); exec @ARGV' ".$command;
	else $command = "setsid ".$command;
	exec("cd / ; ".$command." > /dev/null 2>&1 < /dev/null &");
	return;
	}

function linux_scripts_url() {
	// 2026-11-22 Link to the Linux scripts. The query string, different at each page load, makes the browser download the file
	// instead of reusing a copy in its cache, which may be an older version (the server ignores it).
	return "https://bolprocessor.org/misc/linux-scripts.zip?t=".time();
	}

function watchdog_notice() {
	// 2026-11-22 Linux (XAMPP): Apache belongs to root, so watchdog.sh can restart it only with the sudo rule created by watchdog_allow.sh,
	// one of the Linux scripts (linux-scripts.zip, copied to /home), run by the installation and update scripts.
	// When the rule is missing, watchdog.sh creates temp_bolprocessor/watchdog_needs_sudo (and deletes it within a minute once the rule exists).
	// Displayed in the boxes of check_csound() on project pages and folder lists.
	if(PHP_OS_FAMILY <> "Linux") return;
	if(!file_exists(dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor".DIRECTORY_SEPARATOR."watchdog_needs_sudo")) return;
	echo "<p class=\"thinborder\" style=\"font-size:small; background-color:white; color:black; padding:4px 4px 8px 4px; border-radius:6px; border: 2px solid red;\">⚠️ The <b>watchdog</b> restarting Apache if it crashes or gets stuck is not allowed to do it.<br /><b>Linux user:</b> Download the latest <a target=\"_blank\" class=\"linkdotted\" href=\"".linux_scripts_url()."\">Linux scripts</a> and copy them to the /home folder, as for the installation.<br />Then open your terminal and type:<br /><span class=\"red-text\">sudo /home/watchdog_allow.sh</span><br />(Your password will be required...)<br />The update scripts will then do it automatically.</p>";
	return;
	}


/*
function is_macos_alias($file) {
	// Check that file is an alias pointing to a folder
	if(!file_exists($file)) return FALSE;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file);
//	echo "mime = ".$mime." =>   ".$file."<br />";
	if ($mime !== 'application/octet-stream') return FALSE;
	$xattrList = shell_exec("xattr -l " . escapeshellarg($file));
    if($xattrList && strpos($xattrList,'com.apple.FinderInfo') !== false) {
        // Check if it points to a folder
        $finderInfo = shell_exec("xattr -p com.apple.FinderInfo ".escapeshellarg($file));
        if($finderInfo !== false) {
            $flags = substr($finderInfo, 8, 2); // Get the relevant alias flags
            $flagsInt = hexdec(bin2hex($flags));
            if($flagsInt & 0x8000) { // 0x8000 means "alias points to a folder"
                return true;
            	}
        	}
		}
	return FALSE;
	} */

function is_macos_alias($file) {
    // Must be a regular file
    if (!is_file($file)) {
        return false;
    }
    // Reuse finfo object for speed
    static $finfo = null;
    if ($finfo === null) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
    }
    // Fast reject of ordinary text/media files
    if ($finfo->file($file) !== 'application/octet-stream') {
        return false;
    }
    // Read FinderInfo directly
    $finderInfo = shell_exec(
        'xattr -p com.apple.FinderInfo ' .
        escapeshellarg($file) .
        ' 2>/dev/null'
    );
    if ($finderInfo === null || strlen($finderInfo) < 10) {
        return false;
    }
    // Finder flags are bytes 8-9
    $flags = unpack('n', substr($finderInfo, 8, 2))[1];
    // 0x8000 = alias flag
    if (($flags & 0x8000) === 0) {
        return false;
    }
    // Resolve alias target with AppleScript
    $script =
        'POSIX path of (original item of (POSIX file "' .
        str_replace('"', '\"', $file) .
        '" as alias))';
    $output = shell_exec(
        'osascript -e ' .
        escapeshellarg($script) .
        ' 2>/dev/null'
    );
    if ($output === null) {
        return false;
    }
    $target = trim($output);
    // True only if target exists and is a folder
    return ($target !== '' && is_dir($target));
}
	
function send_to_console($command) {
//	global $test,$pid,$bp_application_path,$console;
	$table = array();
	if(windows_system()) {
		$command = windows_command($command);
//		echo "<small>Windows: exec = <span class=\"red-text\">".str_replace('^','',$command)."</span></small><br />";
		}
//	echo "command = ".$command."<br />";
	exec($command,$table,$return_var);
//	echo "Return status: ".$return_var."\n";
    $pid = 0;
	$_SESSION['pid'] = $pid;
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
	$content = @file_get_contents($csound_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if($content != FALSE AND csound_is_json($content)) { // 2026-11-11
		$json = csound_json_decode($content);
		if($json !== FALSE) $csound_orchestra = cs_get(cs_values($json),"CsoundOrchestraName",'');
		}
	else if($content != FALSE) {
		$extract_data = extract_data(FALSE,FALSE,$content);
		$content = $extract_data['content'];
		$content_no_br = str_replace("<br>",chr(10),$content);
		$table = explode(chr(10),$content_no_br);
		$imax_file = count($table);
		$number_channels = $table[0];
		$csound_orchestra = $table[$number_channels + 1];
		}
	return $csound_orchestra;
	}

function get_name_so_file($this_file) {
	$objects_file = '';
	$content = @file_get_contents($this_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if($content != FALSE) {
		$extract_data = extract_data(FALSE,TRUE,$content);
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
	global $permissions;
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file);
	if($content <> FALSE) {
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
		$handle = @fopen($settings_file,"w");
		if($handle) {
			fwrite($handle,"<?php\n");
			fwrite($handle,$content);
			$line = "\n§>\n";
			$line = str_replace('§','?',$line);
			fwrite($handle,$line);
			fclose($handle);
		//	chmod($settings_file,$permissions);
			}
		else echo "<p>➡ This file cannot be modified because it is write-protected</p>";
		}
	else echo "<p><span class=\"red-text\">File ‘_settings.php’ could nor be opened!</span></p>";
 	return;
 	}

function save_settings2($variable,$index,$value) {
	global $permissions;
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file);
	if(!is_integer($index)) $index = '"'.$index.'"';
	if($content <> FALSE) {
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	//	chmod($settings_file,$permissions);
		}
	else echo "<p><span class=\"red-text\">File ‘_settings.php’ could nor be opened!</span></p>";
	return;
	}

function save_settings3($variable,$index1,$index2,$value) {
	global $permissions;
	$value = str_replace(SLASH,'/',$value);
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file);
	if(!is_integer($index1)) $index1 = '"'.$index1.'"';
	if(!is_integer($index2)) $index2 = '"'.$index2.'"';
	if($content <> FALSE) {
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	//	chmod($settings_file,$permissions);
		}
	else echo "<p><span class=\"red-text\">File ‘_settings.php’ could nor be opened!</span></p>";
	return;
	}
 	
 function delete_settings($file) {
	global $permissions;
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file);
 	if($content != FALSE) {
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	//	chmod($settings_file,$permissions);
 		}
 	return;
 	}

function delete_settings_entry($entry) {
	global $permissions;
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file);
 	if($content != FALSE) {
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	//	chmod($settings_file,$permissions);
 		}
 	return;
 	}

function display_darklight() {
	global $bp_application_path;
	echo "<div style=\"background-color:transparent; display:flex; align-items:center; margin-left:1em;float:right;\">";
	echo "<div title=\"Switch on/off the light\" id=\"darkModeToggle\" class=\"myswitch\"/></div>";
	echo "</div>";
	}

function display_console_state() {
	global $bp_application_path, $absolute_application_path, $panicfile, $filename, $url_this_page, $console, $file_format;
	 echo "<div style=\"display:flex; align-items:center; float:right; padding:6px; background-color:transparent;\">";
	 echo "<a target=\"_blank\" href=\"".$bp_application_path."php\"><img src=\"pict/BP3-logo.png\" style=\"width:70px; margin-bottom:12px;\"/></a>";
	 echo "<span style=\"margin-left: 1em;\">";
	 $output = check_console();
	 if($output <> '') {
		echo "Bol Processor ‘<span class=\"green-text\"><b>".$console."</b></span>’ console is responding<br />Version ".$output;
		$latest = trim(@file_get_contents('latest_version.cache'));
		if($latest !== false) {
			$version_clean = ltrim($output,'v');
			$latest_clean  = ltrim($latest,'v');
	//		echo "<p>".$latest." -> ".$latest_clean."</p>";
			if(version_compare($latest_clean,$version_clean,'>')) echo " <span class=\"red-text\">➡</span> <a class=\"linkdotted\" target=\"_blanl\" href=\"https://bolprocessor.org/install/\">upgrade to ".$latest_clean."</a>&nbsp;!";
			else echo " (latest)";
			}
		$panicfile = str_replace(SLASH,'/',$panicfile);
		if(isset($filename) AND $filename <> "Compilation" AND $filename <> "Produce" AND  $filename <> "Bol Processor" AND $url_this_page <> "index.php") {
			echo "<div style=\"display:flex; justify-content:flex-end; align-items:center; background-color:transparent;\">";
			if($file_format == "rtmidi" OR $file_format == "csoundrt" OR $file_format == "scrt" OR $file_format == "osc") echo "<button type=\"button\" class=\"produce\" style=\"font-size: small;\" title=\"Stop all sound production!\" onclick=\"createFile('".$panicfile."');\">PANIC!</button>";
			echo "&nbsp;&nbsp;";
			echo "<div title=\"Switch on/off the light\" id=\"darkModeToggle\" class=\"myswitch\"/></div>";
			echo "</div>\n";
			}
		else {
			echo "<div style=\"display:flex; justify-content:flex-end; align-items:center; cursor:pointer; background-color:transparent;\">";
			echo "<div title=\"Switch on/off the light\" id=\"darkModeToggle\" class=\"myswitch\"/></div>";
			echo "</div>\n";
			}
		}
	 else {
		echo "Bol Processor is not yet installed or not responding&nbsp;😣<br />";
		if(check_installation()) {
//			$link = "compile.php";
			$link = "compile.php?keepalive=1";
			echo "Source files of BP3 have been found. You can (re)compile it.<br />";
			if(!check_gcc())
				if(windows_system()) echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding.<br />You first need to <a class=\"linkdotted\" target=\"_blank\" href=\"https://bolprocessor.org/install-mingw/\">install and set up MinGW</a>.";
				else echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding. You will get it with <a class=\"linkdotted\" target=\"_blanl\" href=\"https://apps.apple.com/us/app/xcode/id497799835\">Xcode</a>.";
			else echo "👉&nbsp;&nbsp;<a onclick=\"window.open('".nice_url($link)."','trace','width=800,height=800'); return false;\"  href=\"".nice_url($link)."\">Click to run the compiler</a>, then <a href=\"".$url_this_page."\">reload this page</a>.";
			}
		else
			echo "Some files are missing or misplaced.<br />👉&nbsp;&nbsp;Visit <a target=\"_blank\" class=\"linkdotted\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a><br />and follow instructions!";
		display_darklight();
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
	echo "<p class=\"thinborder\" style=\"font-size:small; background-color:azure; color:black; padding:4px; border-radius:6px; line-height:1.8;\">&nbsp;🎶&nbsp;&nbsp;Using microtonality?<br />&nbsp;👉&nbsp;&nbsp;<a class=\"darkblue-text\" target=\"_blank\" href=\"index.php?path=tonality_resources\">TONALITY resource folder</a></p>";
	}

function check_csound($inside_form = FALSE) {
	// $inside_form: the caller has already opened a form, which will carry the TRY button
    global $csound_path,$csound_name, $csound_resources, $path, $url_this_page, $file_format,$programFiles;
	$own_form = FALSE;
	// "csound_version.txt" exists only if a suitable version of Csound is responding. Other pages check it.
	$this_file = "csound_version.txt";
	@unlink($this_file);
	$info = csound_probe($csound_path);
	if($info === FALSE OR !csound_version_ok($info)) {
		// Missing or too old: look for a suitable version elsewhere, e.g. Csound 7 installed next to Csound 6
		foreach(csound_candidate_dirs() as $dir) {
			if($dir == $csound_path) continue;
			$other = csound_probe($dir);
			if($other !== FALSE AND csound_version_ok($other)) {
				$csound_path = $dir;
				save_settings("csound_path",$csound_path);
				$info = $other;
				break;
				}
			}
		}
	if($info === FALSE OR !csound_version_ok($info)) {
		echo "&nbsp;&nbsp;&nbsp;<small><span class=\"red-text\">".$csound_path.SLASH.$csound_name."</span></small>";
		if(!$inside_form) {
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			$own_form = TRUE;
			}
		if(isset($file_format)) echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
		if($info === FALSE)
			echo "<p><img src=\"pict/logo_csound.png\" width=\"90px;\" style=\"vertical-align:middle;\" />&nbsp;is not responding<br />or its path (<span class=\"green-text\">".$csound_path."</span>) is incorrect<br /><br />";
		else {
			$old = ($info['version'] <> '') ? $info['version'] : "(unknown version)";
			echo "<p><img src=\"pict/logo_csound.png\" width=\"90px;\" style=\"vertical-align:middle;\" />&nbsp;<span class=\"red-text\">".$old."</span> is too old:<br />the Bol Processor requires <b>Csound ".CSOUND_MIN_VERSION."</b> or later.<br />";
			echo "There is no need to uninstall the old version.<br />Csound ".CSOUND_MIN_VERSION." will be found once installed.<br /><br />";
			}
		echo "Name: <span class=\"green-text\">path_to_csound".SLASH." </span><input type=\"text\" name=\"csound_name\" size=\"14\" style=\"background-color:CornSilk;\" value=\"".$csound_name."\"><br />";
		echo "Path: <input type=\"text\" name=\"csound_path\" size=\"30\" style=\"background-color:CornSilk;text-align:right;\" value=\"".$csound_path."\"><span class=\"green-text\">".SLASH.$csound_name."</span>";
		echo "&nbsp;<input class=\"save\" type=\"submit\"  name=\"csound_path_change\" value=\"TRY\">";
		echo "<br />";
		$latest = csound_latest_release();
		$which = ($latest !== FALSE) ? "Csound ".$latest['tag'] : "Csound";
		if(linux_system()) {
			echo "<br /><span class=\"red-text\">➡</span>&nbsp;Read instructions in the <a target=\"_blank\" class=\"linkdotted\" href=\"".linux_scripts_url()."\">script folder</a> to install ".$which."<br />";
			}
		else {
			echo "<br /><span class=\"red-text\">➡</span>&nbsp;<a target=\"_blank\" class=\"linkdotted\" href=\"".csound_download_url()."\">Follow this link</a> to install ".$which."<br />";
			}
		$result = FALSE;
		}
	else {
		@file_put_contents($this_file,$info['content']);
		$version = ($info['version'] <> '') ? "(".$info['version'].")&nbsp;" : '';
		echo "<p class=\"thinborder\" style=\"font-size:small; vertical-align:middle; background-color:azure; color:black; padding:4px 4px 8px 4px; border-radius:6px;\"><img src=\"pict/logo_csound.png\" width=\"60px;\" style=\"vertical-align:middle;\" />".$version."is responding<br />";
		$latest = csound_latest_release();
		if($latest !== FALSE AND csound_newer_release($info,$latest))
			echo "&nbsp;🆕&nbsp;&nbsp;<a class=\"darkblue-text\" target=\"_blank\" href=\"".csound_download_url()."\">Csound ".$latest['tag']." is available</a><br />";
		$result = TRUE;
		}
	if($path <> $csound_resources) echo "&nbsp;👉&nbsp;&nbsp;<a class=\"darkblue-text\" target=\"_blank\" href=\"index.php?path=csound_resources\">CSOUND resource folder</a><br />";
	echo "</p>";
	if($own_form) echo "</form>";
	return $result;
	}

function csound_environment($dir) {
	// On Linux, Csound may be a folder unpacked from "csound-linux-minimal-…zip", holding "csound", "libcsound64.so" and "plugins".
	// Also remove XAMPP's /opt/lampp/lib so that Csound uses the system libraries.
	// "csound" may be a link, e.g. /usr/local/bin/csound -> /opt/csound7/csound: the folder is that of the actual file.
	global $csound_name;
	if(!linux_system()) return;
	$real = realpath($dir."/".$csound_name);
	if($real !== FALSE) $dir = dirname($real);
	if(glob($dir."/libcsound64.so*")) putenv("LD_LIBRARY_PATH=".$dir);
	else putenv("LD_LIBRARY_PATH");
	if(is_dir($dir."/plugins")) putenv("OPCODE7DIR64=".$dir."/plugins");
	}

function csound_probe($dir) {
	// Run "csound --version" in folder $dir. Return FALSE if Csound is not responding, otherwise
	// 'version' (e.g. "7.0", or '' if not found), 'date' (build date, Unix time, or 0) and 'content' (full output)
	global $csound_name;
	if($dir == '' OR !file_exists($dir.SLASH.$csound_name)) return FALSE;
	$probe_file = "csound_probe.txt";
	@unlink($probe_file);
	csound_environment($dir);
	send_to_console("\"".$dir.SLASH.$csound_name."\" --version 2>".$probe_file);
	$content = @file_get_contents($probe_file);
	@unlink($probe_file);
	if($content === FALSE OR trim($content) == '') return FALSE;
	return csound_parse_version($content);
	}

function csound_parse_version($content) {
	// Read the output of "csound --version", e.g. "--Csound version 7.0 (double samples) Oct 21 2026"
	// The pattern is ASCII and has no 'u' flag, so that it works whatever the encoding of the output (Windows)
	$info = array('version' => '','date' => 0,'content' => $content);
	if(preg_match('/Csound version ([0-9]+(?:\.[0-9]+)*)([^\n]*)/',$content,$match)) {
		$info['version'] = $match[1];
		// Build date, on the same line, possibly followed by colour codes
		if(preg_match('/([A-Z][a-z]{2} +[0-9]{1,2} +[0-9]{4})/',$match[2],$date_match) AND ($date = strtotime($date_match[1])) !== FALSE)
			$info['date'] = $date;
		}
	return $info;
	}

function csound_ready() {
	// TRUE if "csound_version.txt", written by check_csound(), shows a suitable version of Csound.
	// The file may be older than the current interface and show Csound 6.
	$content = @file_get_contents("csound_version.txt");
	if($content === FALSE) return FALSE;
	return csound_version_ok(csound_parse_version($content));
	}

function csound_major_minor($version) {
	// "7.0.1" -> "7.0"
	$parts = explode('.',$version);
	return $parts[0].".".($parts[1] ?? "0");
	}

function csound_version_ok($info) {
	// A version that can't be read is not accepted
	if($info['version'] == '') return FALSE;
	return version_compare(csound_major_minor($info['version']),CSOUND_MIN_VERSION,'>=');
	}

function csound_latest_release() {
	// Latest release of Csound on GitHub: 'tag' (e.g. "7.0.0"), 'published' (date) and 'url', or FALSE if unknown.
	// GitHub is asked at most once a day, and once an hour when it did not answer. The answer is kept in "csound_latest.json".
	$cache_file = "csound_latest.json";
	$data = FALSE;
	$content = @file_get_contents($cache_file);
	if($content !== FALSE) $data = json_decode($content,TRUE);
	if(!is_array($data)) $data = array();
	if(isset($data['checked']) AND (time() - $data['checked']) < 86400)
		return isset($data['tag']) ? $data : FALSE;
	$context = stream_context_create(array('http' => array('timeout' => 3,'header' => "User-Agent: BolProcessor\r\nAccept: application/vnd.github+json\r\n")));
	$json = @file_get_contents("https://api.github.com/repos/csound/csound/releases/latest",FALSE,$context);
	$release = ($json !== FALSE) ? json_decode($json,TRUE) : NULL;
	if(is_array($release) AND isset($release['tag_name']) AND preg_match('/^v?([0-9]+(?:\.[0-9]+)*)$/',$release['tag_name'],$match)) {
		$data = array('checked' => time(),'tag' => $match[1],'published' => $release['published_at'] ?? '','url' => $release['html_url'] ?? '');
		}
	else $data['checked'] = time() - 86400 + 3600; // Keep the previous answer, if any, and try again in an hour
	@file_put_contents($cache_file,json_encode($data));
	return isset($data['tag']) ? $data : FALSE;
	}

function csound_newer_release($info,$latest) {
	// TRUE if the latest release of Csound is newer than the installed one
	if($info['version'] == '') return FALSE;
	$installed = csound_major_minor($info['version']);
	$last = csound_major_minor($latest['tag']);
	if(version_compare($installed,$last,'<')) return TRUE;
	if(version_compare($installed,$last,'>')) return FALSE;
	// Same major and minor numbers: "csound --version" doesn't show the patch number (6.18.1 displays "6.18"),
	// so a patch release (e.g. 7.0.1) is detected by comparing the build date with the release date
	if($info['date'] > 0 AND $latest['published'] <> '' AND ($published = strtotime($latest['published'])) !== FALSE)
		return ($published > $info['date'] + 3 * 86400);
	return FALSE;
	}

function csound_download_url() {
	return "https://csound.com/download.html";
	}

function is_connected() {
	$connected = @fsockopen("cdn.jsdelivr.net",80);
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
		move_to_csound_resources($dir,$filename);
	return $clean_line;
	}

function check_function_tables($dir,$csound_file) {
	if($csound_file == '') return;
	$content = @file_get_contents($dir.$csound_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if(trim($content) == '') return;
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
	$popup_link = "<a class=\"linkdotted\" onclick=\"window.open('".nice_url($link)."','".$image_name."','toolbar=no,scrollbars=yes,resizable=yes,width=".$image_width.",height=".$image_height.",left=".$left_margin."'); return false;\" href=\"".nice_url($link)."\">".$text."</a>";
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
	$content = @file_get_contents($csound_instruments_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if(csound_is_json($content)) { // 2026-11-11
		$json = csound_json_decode($content);
		if($json !== FALSE) $tonality_filename = cs_get(cs_values($json),"TonalityFileName",'');
		return $tonality_filename;
		}
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

function orchestra_instruments($orchestra_path) {
	// 2026-11-18 Numbered instruments of a Csound orchestra, as an array number => description.
	// The descriptions are taken from the "; Instruments:" block at the top of the file (as in "0-default.orc"),
	// in which a line starting with a number begins an instrument and the following lines continue its description.
	// Otherwise, the numbers of the "instr" lines are listed without a description.
	$list = array();
	$content = @file_get_contents($orchestra_path);
	if($content === FALSE) return $list;
	$table = explode("\n",str_replace("\r",'',$content));
	$in_block = FALSE; $current = $indent = 0;
	foreach($table as $line) {
		$line = trim($line);
		if(!$in_block) {
			if(preg_match("/^;\s*Instruments\s*:\s*$/i",$line)) $in_block = TRUE;
			continue;
			}
		if(!str_starts_with($line,";")) break;
		$raw = substr($line,1);
		$text = trim($raw);
		if($text == '') {
			if(count($list) > 0) break;
			continue;
			}
		$this_indent = strlen($raw) - strlen(ltrim($raw));
		if(preg_match("/^([0-9]+)\s+(.+)$/u",$text,$m)) {
			$current = intval($m[1]);
			$list[$current] = $m[2];
			$indent = $this_indent;
			}
		else if($current > 0 AND $this_indent > $indent) $list[$current] .= " ".$text; // Continuation, indented further than the number
		else break; // e.g. "; Effect:"
		}
	if(count($list) == 0) {
		// Without this block, only the numbers are listed, because the comments above "instr" lines are not reliable descriptions
		foreach($table as $line) {
			if(preg_match("/^\s*instr\s+([0-9]+)\b/u",$line,$m)) $list[intval($m[1])] = '';
			}
		}
	foreach($list as $i => $text) $list[$i] = trim(preg_replace("/\s+/u",' ',preg_replace("/\s*\(outputs to [^\)]*\)/iu",'',$text)));
	ksort($list);
	return $list;
	}

function show_instruments_and_scales($dir,$objects_file,$content,$url_this_page,$filename,$file_format,$orchestra = '') {
	global $dir_csound_resources,$dir_tonality_resources,$csound_file,$tonality_file;
	/* if($objects_file <> '') {
		$new_csound_file = get_csound_file($dir.$objects_file);
		$new_csound_file = str_replace("csound_resources/",'',$new_csound_file);
		if($new_csound_file <> '' AND $new_csound_file <> $csound_file) {
			echo "<p></p><div class=\"warning\">";
			if($csound_file == '') {
				$content = add_instruction($new_csound_file,$content);
				$csound_file = $new_csound_file;
				echo "👉&nbsp;&nbsp;Found mention of <class=\"darkblue-text\">‘".$csound_file."’</span> in sound-object file  <br /><class=\"darkblue-text\">‘".$objects_file."’</span>. This indication has been added to the project.<br /><span class=\"red-text\">You need to</span> <input class=\"save big\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".$filename."’\">";
				}
			else {
				echo "👉&nbsp;&nbsp;<span class=\"red-text\">WARNING:</span> File <class=\"darkblue-text\">‘".$objects_file."’</span> indicates that the Csound instruments file <class=\"darkblue-text\">‘".$new_csound_file."’</span> should be associated.<br />This project selects <class=\"darkblue-text\">‘".$csound_file."’</span> instead, <span class=\"red-text\">which we will use.</span><br />➡ Your can edit <class=\"darkblue-text\">‘".$objects_file."’</span> to solve the inconsistency.";
				}
			echo "</div>";
			}
		} */
	// 2026-11-21 The instruments of the '-cs' file are listed only when the output is a Csound score or real-time Csound
	$csound_output = ($file_format == "csound" OR $file_format == "csoundrt");
	if($csound_file <> '') {
		$tonality_file_in_csound = get_tonality_file(csound_resource_path($csound_file));
		if($tonality_file_in_csound <> '' AND $tonality_file_in_csound <> $tonality_file) {
			echo "<p></p><div class=\"warning\">";
			if($tonality_file == '') {
				$content = add_instruction($tonality_file_in_csound,$content);
				$tonality_file = $tonality_file_in_csound;
				echo "👉&nbsp;&nbsp;File <class=\"darkblue-text\">‘".$csound_file."’</span> indicates that tonality file <class=\"darkblue-text\">‘".$tonality_file_in_csound."’</span><br />should be associated. This indication has been added to the project.<br /><span class=\"red-text\">You need to</span> <input class=\"save big\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" formaction=\"".$url_this_page."\" value=\"SAVE ‘".$filename."’\">";
				}
			else {
				echo "👉&nbsp;&nbsp;<span class=\"red-text\">WARNING:</span> File <class=\"darkblue-text\">‘".$csound_file."’</span> indicates that tonality file<br /><class=\"darkblue-text\">‘".$tonality_file_in_csound."’</span> should be associated.<br />This project selects <class=\"darkblue-text\">‘".$tonality_file."’</span> instead, <span class=\"red-text\">which we will use.</span><br />Your can edit <class=\"darkblue-text\">‘".$csound_file."’</span> to solve the inconsistency.";
				}
			echo "</div>";
			}
		}
	if($tonality_file <> '') {
		$list_of_tonal_scales = list_of_tonal_scales(tonality_resource_path($tonality_file));
		if((!isset($_POST['analyze_tonal']) AND $max_scales = count($list_of_tonal_scales)) > 0) {
			if($max_scales > 1)  {
				$i = 0;
				echo "<p id=\"tonal\" style=\"margin-bottom:0px;\">Tonality resource file <span class=\"green-text\">‘".$tonality_file."’</span> contains definitions of tonal scales&nbsp;<span class=\"red-text\">➡</span>&nbsp;<button style=\"background-color:aquamarine; border-radius: 6px; font-size:large;\" onclick=\"togglescales(); return false;\">Show/hide tonal scales</button>";
				echo "<div id=\"scales\" style=\"border-radius: 15px; padding:6px;\"><br />";
				}
			else {
				echo "<p style=\"margin-bottom:0px;\">Tonal resource file <span class=\"green-text\">‘".$tonality_file."’</span> contains the definition of a tonal scale:";
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
	else if($csound_file <> '' AND $csound_output) {
		echo "<p>Csound instruments have been loaded but scales cannot be used because no tonality file ‘-to’ has been found.<br />";
		echo "➡ Instruction “_scale()” will be ignored</p>";
		}
	// 2026-11-21 The instruments are listed on the panel of the output format, see csound_instruments_details()
	return $content;
	}

function list_of_tonal_scales($tonality_file) {
	// 2026-11-12 Reads the JSON format of tonality resources, or converts the former line format
	global $dir_scale_images;
	$list = array();
	if(!file_exists($tonality_file)) return $list;
	$content = @file_get_contents($tonality_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$data = to_read_resource($content);
	if(!is_array($data)) return $list;
	$k = 0;
	foreach($data['scales'] as $j => $scale) {
		$name_of_file = cs_get($scale,"ScaleName",'');
		$list[$j] = "<span class=\"turquoise-text\">".$name_of_file."</span>";
		if(isset($scale["NoteNames"]))
			$list[$j] .= " = <span class=\"tonalnotes\"><b>".implode(' ',$scale["NoteNames"])."</b></span>";
		if(isset($scale["BaseOctave"])) {
			$list[$j] .= " baseoctave = ".$scale["BaseOctave"];
			$clean_name_of_file = str_replace("#","_",$name_of_file);
			$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
			$dir_image = $dir_scale_images.$clean_name_of_file.".png";
			if(file_exists($dir_image)) {
				$k++; if($k > 10) $k = 0;
				$list[$j] .= " <span class=\"red-text\">➡</span>&nbsp;".popup_link($clean_name_of_file,"image",500,410,(100 * $k),$dir_image);
				}
			}
		}
	return $list;
	}

function list_of_instruments($csound_instruments_file) {
	// 2026-11-11 Reads the JSON format of Csound resources, or converts the former line format
	$list_of_instruments['list'] = $list_of_instruments['index'] = $list_of_instruments['param'] = array();
	if(!file_exists($csound_instruments_file)) return $list_of_instruments;
	$content = @file_get_contents($csound_instruments_file);
	if($content === FALSE) return $list_of_instruments;
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$json = csound_read_resource($content);
	if(!is_array($json)) return $list_of_instruments; // Error message
	$instruments = cs_get(cs_values($json),"CsInstruments",array());
	if(!is_array($instruments)) return $list_of_instruments;
	$j = 0;
	foreach($instruments as $instrument_object) {
		$instrument = cs_values($instrument_object);
		$list_of_instruments['list'][$j] = str_replace(' ','_',cs_get($instrument,"CsInstrumentName",''));
		$list_of_instruments['index'][$j] = cs_get($instrument,"CsInstrumentIndex",0);
		$list_of_instruments['param'][$j] = array();
		$ipmax = intval(cs_get($instrument,"ipmax",0));
		for($ip = 0; $ip < $ipmax; $ip++)
			$list_of_instruments['param'][$j][] = cs_get($instrument,"paramlist[".$ip."].name",'');
		$j++;
		}
	return $list_of_instruments;
	}

function check_duplicate_name($dir,$file) {
	// 2026-11-18 Case is ignored: on MacOS and Windows, a file 'Abc.txt' would replace 'abc.txt'
	$dircontent = scandir($dir);
	foreach($dircontent as $some_file) {
		if(strcasecmp($some_file,$file) == 0) return TRUE;
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
	$series = '';
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

// note 40
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
	// 2026-11-13: the approximated fraction is returned as text too. The text was the original fraction while 'p' and 'q'
	// were approximated. If the approximation rounds a term to 0, the reduced exact fraction is kept - fixed by Claude
	$ratio = $max_term / $the_max;
	$num_approx = round($num * $ratio);
	$den_approx = round($den * $ratio);
	if(($num_approx * $den_approx) ==  0) return $simplify;
	$gcd = gcd($num_approx,$den_approx);
	$num = intval($num_approx / $gcd);
	$den = intval($den_approx / $gcd);
	if($den <> 1) $simplify['fraction'] = $num."/".$den;
	else $simplify['fraction'] = $num;
	$simplify['p'] = $num;
	$simplify['q'] = $den;
	$simplify['done'] = TRUE;
	return $simplify;
	}

// note 40
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

// note 9
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
	$content = @file_get_contents($data_path);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$extract_data = extract_data(FALSE,TRUE,$content);
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

function get_legato($line,$pos) {
	if(is_integer($pos1=strpos($line,"_legato(",$pos)) AND $pos1 == $pos) {
		$pos2 = strpos($line,")",$pos1);
		$legato_value = substr($line,$pos1 + 8,$pos2 - $pos1 - 8);
		return $legato_value;
		}
	else return -1;
	}

function get_switch($line,$pos) {
	$result['value'] = -1;
	if(is_integer($pos1=strpos($line,"_switchon(",$pos)) AND $pos1 == $pos) {
		$result['value'] = 127;
		$pos2 = strpos($line,",",$pos1);
		$result['control'] = substr($line,$pos1 + 10,$pos2 - $pos1 - 10);
		}
	if(is_integer($pos1=strpos($line,"_switchoff(",$pos)) AND $pos1 == $pos) {
		$result['value'] = 0;
		$pos2 = strpos($line,",",$pos1);
		$result['control'] = substr($line,$pos1 + 11,$pos2 - $pos1 - 11);
		}
	if($result['value'] >= 0) {
		$pos3 = strpos($line,")",$pos2);
		$result['channel'] = substr($line,$pos2 + 1,$pos3 - $pos2 - 1);
		}
	return $result;
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

function rename_private_resource_folder($old,$new) {
	// 2026-11-20 The folders of resources created or changed by the user were renamed "csound_resources_private" and "tonality_resources_private".
	// An old folder is given the new name, or its files are moved to the new folder if both exist: a file whose name is already
	// in the new folder stays in the old one, which is deleted when empty. The paths kept by edit pages opened before ("_source"
	// in temporary folders, see csound.php and tonality.php) are updated - by Claude
	global $bp_application_path,$temp_dir;
	$old_path = $bp_application_path.$old;
	$new_path = $bp_application_path.$new;
	if(!is_dir($old_path)) return;
	if(!file_exists($new_path)) @rename($old_path,$new_path);
	else {
		foreach(scandir($old_path) as $thisfile) {
			if($thisfile == '.' OR $thisfile == '..') continue;
			if(!file_exists($new_path.SLASH.$thisfile)) @rename($old_path.SLASH.$thisfile,$new_path.SLASH.$thisfile);
			}
		@rmdir($old_path); // Only if it is empty
		}
	foreach(glob($temp_dir."*".SLASH."_source") ?: array() as $source_file) {
		$content = @file_get_contents($source_file);
		if($content !== FALSE AND is_integer(strpos($content,$old))) @file_put_contents($source_file,str_replace($old,$new,$content));
		}
	return;
	}

function phpdesktop_style() {
	// 2026-11-22 In the standalone application (PHP Desktop), the system font of buttons and other form controls is shown as empty boxes on MacOS, so they get a font of their own. "Apple Symbols" supplies characters missing in Helvetica Neue, such as '←' - by Claude
	if(!isset($_SERVER['PHPDESKTOP_VERSION'])) return '';
	return "<style>button, input, input::file-selector-button, select, textarea, option { font-family: \"Helvetica Neue\", \"Apple Symbols\", Helvetica, Arial, sans-serif; }</style>\n";
	}

function hidden_directory($name) {
	switch($name) {
		case "macos-scripts":
		case "windows-scripts":
		case "linux-scripts":
		case "csound_resources":
		case "csound_resources_private":
		case "docs-developer":
		case "docs-release":
		case "icons":
		case "pictures":
		case "portmidi":
		case "php":
		case "www":
		case "source":
		case "midi_resources":
		case "osc_resources": // 2026-11-22
		case "supercollider_resources":
		case "supercollider_resources_private":
		case "tonality_resources":
		case "tonality_resources_private":
		case "temp_bolprocessor":
			return TRUE;
		}
	return FALSE;
	}

function add_proper_extension($format,$filename) {
	$filename = str_replace(".mid",'',$filename);
	$filename = str_replace(".sco",'',$filename);
	$filename = str_replace(".bpda",'',$filename);
	$filename = str_replace(".csv",'',$filename);
	switch($format) {
		case "midi": $output_file = $filename.".mid"; break;
		case "csound": $output_file = $filename.".sco"; break;
		case "data": $output_file = $filename.".bpda"; break;
		case "eventlist": $output_file = $filename.".csv"; break;
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
	echo "<input type=\"text\" name=\"find\" id=\"find\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	echo "<label for=\"regex\">Check for regular expression:</label>";
	echo "<input type=\"checkbox\" name=\"regex\" id=\"regex\">";
	echo "</p>";
	echo "<p>";
	echo "<label for=\"replace\">and replace it with: </label>";
	echo "<input type=\"text\" name=\"replace\" id=\"replace\">&nbsp;&nbsp;&nbsp;<button class=\"produce\" type=\"submit\" formaction=\"".$url_this_page."#saveButton\" name=\"action\" value=\"replace\" onclick=\"clearsave()\">Search and Replace (all)</button>";
	echo "&nbsp;<input class=\"edit\" type=\"submit\" name=\"cancel\" value=\"CANCEL\">";
	echo "</div>";
	return;
	}

function download_upload_project_form($dir,$thisfile,$type,$settings_file) {
	global $url_this_page;
	echo "<span id=\"download\">";
	echo "<div class=\"thinborder\" style=\"width:50%; padding:0.3em; text-align:center;\">";
	$link_file = $dir.$thisfile;
	$link_settings = $dir.$settings_file;
	echo "<input type=\"hidden\" name=\"file_link\" value=\"".$link_file."\">";
	echo "<input type=\"hidden\" name=\"type_link\" value=\"".$type."\">";
	echo "Download the <a href=\"".$link_file."\" title=\"Click to download!\" download=\"".$thisfile."\">".$thisfile."&nbsp;⬇️</a> ".$type." file";
	if($settings_file <> '') echo " or the <a href=\"".$link_settings."\" title=\"Click to download!\" download=\"".$settings_file."\">".$settings_file."&nbsp;⬇️</a> settings file";
	echo "<p>&nbsp;&nbsp;&nbsp;<input type=\"file\" onclick=\"if(!checksaved()) return false;\" name=\"uploaded_replacement\" id=\"uploaded_replacement\">";
	echo "<input class=\"save\" name=\"upload_project\" formaction=\"".$url_this_page."#downloadupload\" onclick=\"if(!checksaved()) return false;\" type=\"submit\" value=\"<-- UPLOAD TO REPLACE ".strtoupper($type)."\"></p>";
	echo "</div>";
	echo "</span>";
	return;
	}

function upload_related_form($dir,$file,$type) {
	global $bp_application_path,$url_this_page;
	$dir_base = str_replace($bp_application_path,'',$dir);
	$result = '';
	$url = $type.".php?file=".urlencode($dir_base.$file);
	$result .= "<br /><span class=\"red-text\">WARNING:</span> <span class=\"green-text\">".$dir_base.$file."</span> not found.";
	$result .= "&nbsp;<input class=\"save\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".nice_url($url)."','".$file."','width=800,height=800,left=100'); return false;\" value=\"CREATE IT\">";
	$result .= "<input type=\"hidden\" id=\"expectedFileName\" name=\"expectedFileName\" value=\"".$file."\">";
    $result .= "<br />… or select this file: <span class=\"green-text\">".$file."</span>";
	$result .= "&nbsp;&nbsp;<label class=\"custom-file-button\">Search";
    $result .= "&nbsp;&nbsp;<input type=\"file\" id=\"fileInput\" name=\"uploaded_file\" class=\"hidden-file-input\">";
	$result .= "</label>";
	$result .= "<span id=\"fileName\">(no file chosen)</span>";
    $result .= "&nbsp;and&nbsp;<input class=\"save\" name=\"submit_upload\" type=\"submit\" value=\"UPLOAD\">&nbsp;it";
	return $result;
	}

function upload_related($dir) {
	global $_FILES, $permissions;
	$result = '';
	if(isset($_POST['submit_upload']) AND $_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_FILES['uploaded_file'])) {
		$expectedFileName = $_POST['expectedFileName'];
		$uploadedFileName = $_FILES['uploaded_file']['name'];
		if($uploadedFileName !== $expectedFileName)
			$result .= "<br />👉 <span class=\"red-text\">Incorrect file name:</span> <span class=\"green-text\">".$uploadedFileName."</span>.</span> Please select <span class=\"green-text\">".$expectedFileName."</span>";
		else {
			$uploadDir = $dir; // Define your upload directory
			$uploadPath = $uploadDir.SLASH.basename($uploadedFileName);
			if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadPath)) {
				chmod($uploadPath,$permissions);
				$result .= "<br />👉 File uploaded successfully: <span class=\"green-text\">".$uploadedFileName."</span>";
				}
			else $result .= "<p>👉 <span class=\"red-text\">Error uploading file</span></p>";
			}
		}
	return $result;
	}

function footer() {
	echo "<script> // Script used for uploading attached files
	const fileInput = document.getElementById('fileInput');
	if (fileInput) {
		fileInput.addEventListener('change', function () {
			const fileNameSpan = document.getElementById('fileName');
			if (!fileNameSpan) return;
			if (this.files.length > 0) {
				fileNameSpan.innerHTML =
					'<span class=\"green-text\">' + this.files[0].name + '</span>';
			} else {
				fileNameSpan.textContent = '(no file)';
			}
		});
	}
	</script>";
	return;
	}

function footer_enter_notes() {
    global $temp_dir;
    $temp_dir_noslash = str_replace(SLASH, '/', $temp_dir);
    ?>
<script>
let notePollPosition = 0;
let notePollTimer = null;
function insertNoteAtCursor(textarea, text) {
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? textarea.value.length;
    textarea.value =
        textarea.value.substring(0, start) +
        text +
        textarea.value.substring(end);
    const pos = start + text.length;
    textarea.selectionStart = pos;
    textarea.selectionEnd = pos;
    textarea.dispatchEvent(new Event("input", {bubbles: true}));
    textarea.dispatchEvent(new Event("change", {bubbles: true}));
	}
function startNotePolling() {
    const textarea = document.getElementById("textArea");
    if (!textarea) {
        console.error("ERROR: textarea #textArea not found");
        return;
    	}
    const tempDir = <?= json_encode($temp_dir_noslash) ?>;
    async function pollNotes() {
        try {
            const url =
                "note_poll.php?temp_dir=" +
                encodeURIComponent(tempDir) +
                "&pos=" + notePollPosition;
            const response = await fetch(url, {
                cache: "no-store",
                credentials: "same-origin"
            });
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            	}
            const result = await response.json();
            if (typeof result.pos === "number") {
                notePollPosition = result.pos;
            	}
            if (Array.isArray(result.notes)) {
                for (const note of result.notes) {
                    insertNoteAtCursor(textarea, " " + note);
					}
				}
			}
        catch (error) {
            console.warn("MIDI note polling problem:", error);
        	}
        finally {
            notePollTimer = window.setTimeout(pollNotes, 300);
			}
		}
    pollNotes();
	}
if (document.readyState === "loading") {
    window.addEventListener("DOMContentLoaded", startNotePolling);
	}
else startNotePolling();
</script>
<?php
    return;
	}

function footer_keyboard_mapping($dir,$keyboard_file) {
    $mapPath = $dir.$keyboard_file;
	if($keyboard_file == '' OR !file_exists($mapPath)) return;
    $keyboardJson = file_get_contents($mapPath);
    ?>
    <script>
    (function() {
        const textarea = document.getElementById("textArea");
        if (!textarea) return; // page without textarea → do nothing
        const keyboardMap = <?= $keyboardJson ?>;
        let mappingEnabled = false;
        function insertAtCursor(textarea, text) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value =
                textarea.value.substring(0, start) +
                text +
                textarea.value.substring(end);
            const pos = start + text.length;
            textarea.selectionStart = pos;
            textarea.selectionEnd = pos;
            textarea.dispatchEvent(new Event("input"));
            if (typeof tellsave === "function") {
                tellsave();
				}
			}
        textarea.addEventListener("keydown", function (e) {
            // Toggle with ESC
            if (e.key === "Escape") {
                mappingEnabled = !mappingEnabled;
                console.log("Mapping:", mappingEnabled ? "ON" : "OFF");
                e.preventDefault();
                return;
            	}
            if (!mappingEnabled) return;
            let key = e.key;
            if (key === "Backspace" ||
                key === "Delete" ||
                key === "ArrowLeft" ||
                key === "ArrowRight" ||
                key === "ArrowUp" ||
                key === "ArrowDown") {
                return;
            	}
            if (key === "Enter") {
                e.preventDefault();
                insertAtCursor(textarea, "\n");
                return;
            	}
            if (key === " ") {
                e.preventDefault();
                insertAtCursor(textarea, " ");
                return;
            	}
            if (/^[0-9]$/.test(key)) {
                e.preventDefault();
                insertAtCursor(textarea, key);
                return;
            	}
            if (key.length === 1) {
                // Only English letters are mapped
                if (/^[a-zA-Z]$/.test(key)) {
                    const mapKey = key.toUpperCase();
                    if (keyboardMap[mapKey] !== undefined) {
                        e.preventDefault();
                        insertAtCursor(textarea, keyboardMap[mapKey]);
                        return;
                    	}
                    e.preventDefault();
                    return;
                	}
                // Other characters → normal typing
                return;
				}
			});
		})();
    </script>
    <?php
	}

function upload_project($type) {
	global $permissions,$url_this_page;
	$upload_message = '';
	if(!isset($_POST['upload_project'])) return '';
	if($_SERVER['REQUEST_METHOD'] === 'POST' AND isset($_FILES['uploaded_replacement'])) {
		$fileName = $_FILES['uploaded_replacement']['name'];
		$type_of_file = type_of_file($fileName);
		if($type == $type_of_file['type']) {
			if($_FILES['uploaded_replacement']['error'] === UPLOAD_ERR_OK) {
				$file_link = $_POST['file_link'];
				$type_link = $_POST['type_link'];
				$backup_file = $file_link."_bak";
				copy($file_link,$backup_file);
				echo "<input type=\"hidden\" name=\"file_link\" value=\"".$file_link."\">";
				$fileTmpPath = $_FILES['uploaded_replacement']['tmp_name'];
				$fileName = $_FILES['uploaded_replacement']['name'];
				$fileSize = $_FILES['uploaded_replacement']['size'];
				$fileType = $_FILES['uploaded_replacement']['type'];
				if(move_uploaded_file($fileTmpPath,$file_link)) {
					chmod($file_link,$permissions);
					$upload_message .= "<p>👉 Replacement ".$type_link." file uploaded successfully: <span class=\"green-text\">".$file_link."</span>&nbsp;<input class=\"save\" formaction=\"".$url_this_page."#downloadupload\" name=\"undo_upload_project\" type=\"submit\" value=\"<-- UNDO THIS REPLACEMENT\"></p>";
					}
				else $upload_message .= "<p class=\"red-text\">👉 Error moving the uploaded file</p>";
				}
			}
		else {
			if($fileName <> '') $upload_message .= "<p class=\"red-text\">👉 The <span class=\"green-text\">".$fileName."</span> file you selected is not of “".$type."” type</p>";
			else $upload_message .= "<p class=\"red-text\">👉 No file has been chosen…</p>";
			}
		}
	else {
		$upload_message .= "<p class=\"red-text\">👉 2No file has been chosen…</p>";
		}	
	unset($_POST['upload_project']);
	return $upload_message;
	}

function undo_upload_project() {
	$undo_message = '';
	if(isset($_POST['undo_upload_project'])) {
		$file_link = $_POST['file_link'];
		$backup_file = $file_link."_bak";
		if(!copy($backup_file,$file_link))
			$undo_message = "<p>👉 <span class=\"red-text\">Failed to find a backup of the file.</span></p>";
		else $undo_message = "<p>👉 The previous version has been restored&nbsp;😀</p>";
		}
	return $undo_message;
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
			echo "<p>Text = “<span class=\"green-text\">".$find."</span>” replaced by “<span class=\"green-text\">".$replace."</span>”&nbsp;<span class=\"red-text\"> ➡ Don't forget to save!</span></p>";
			}
		else {
			// Replace using regex
			$pattern = '/'.$find.'/';
			$content = preg_replace($pattern,$replace,$text);
			echo "<p>Pattern = <span class=\"green-text\">".$pattern."</span> should be replaced by <span class=\"green-text\">".$replace."</span><span class=\"red-text\"> ➡ Don't forget to save!</span></p>";
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
	if($folder == "ctests") $result = FALSE;
	if($folder == "csound_resources") $result = FALSE;
	if($folder == "midi_resources") $result = FALSE;
	if($folder == "tonality_resources") $result = FALSE;
	if($folder == "resources") $result = FALSE;
	if($folder == "trash_bolprocessor") $result = FALSE;
	if($folder == "docs-developer") $result = FALSE;
	if($folder == "source") $result = FALSE;
	if($folder == "php") $result = FALSE;
	if($folder == "scripts") $result = FALSE;
	if(is_integer($pos=strpos($folder,"BP2-")) AND $pos == 0) $result = FALSE;
	if($folder == $output_folder AND !$talk) $result = FALSE;
	if(!$result AND $talk) echo "<p><span class=\"red-text\">ERROR:</span> Folder “<span class=\"green-text\">".$folder."</span>” cannot be used for output files.</p>";
	return $result;
	}


function read_midiport($thefile) {
	global $MIDIinput,$MIDIoutput,$MIDIinputname,$MIDIinputcomment,$MIDIoutputname,$MIDIoutputcomment; // These are tables!
	global $MIDIacceptFilter, $MIDIpassFilter, $MIDIoutFilter, $MIDIchannelFilter, $MIDIpartFilter; // These are tables!
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
				else if(trim($table[0]) == "MIDIpartFilter") {
					if(ctype_digit($table[1])) {
						$i = trim($table[1]);
						if(count($table) > 2) $MIDIpartFilter[$i] = trim($table[2]);
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

function read_midiressources($filename) {
	global $temp_midi_ressources, $MIDIacceptFilter, $MIDIpassFilter;
	global $dir_midi_resources,$MIDIinput,$MIDIoutput,$MIDIinputname,$MIDIoutputname,$MIDIinputcomment,$MIDIoutputcomment,$NumberMIDIinputs;
	
	// First try to read  in the "temp" folder
//	echo "@@ try temp_midi_ressources<br />";
	$result = read_midiport($temp_midi_ressources."midiport");
	// Then try the "midi_ressources" folder, which is permanent
	if(!$result['found']) {
	//	echo "@@ try dir_midi_resources<br />";
		$result = read_midiport($dir_midi_resources.$filename."_midiport");
		}
	if(!$result['found']) {
	//	echo "@@ not found<br />";
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
	global $channel_pass, $part_pass, $MIDIchannelFilter, $MIDIpartFilter;
	for($channel = 1; $channel <= 16; $channel++) {
		$channel_pass[$i][$channel] = $MIDIchannelFilter[$i][$channel - 1];
		}
	for($part = 1; $part <= MAXPARTS; $part++) {
		if(isset($MIDIpartFilter[$i][$part - 1])) $part_pass[$i][$part] = $MIDIpartFilter[$i][$part - 1];
		else $part_pass[$i][$part] = 1;
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

function save_midiressources($filename,$warn) {
	global $MIDIinput, $MIDIoutput, $MIDIinputname, $MIDIoutputname, $MIDIinputcomment, $MIDIoutputcomment; // These are tables!
	global $dir_midi_resources, $temp_midi_ressources, $NumberMIDIinputs, $NumberMIDIoutputs, $MIDIchannelFilter, $MIDIpartFilter;
	global $temp_dir;
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		if(isset($_POST["MIDIinput_".$i])) $MIDIinput[$i] = trim($_POST["MIDIinput_".$i]);
		if(isset($_POST["MIDIinputname_".$i])) $MIDIinputname[$i] = trim($_POST["MIDIinputname_".$i]);
		if(isset($_POST["MIDIinputcomment_".$i])) $MIDIinputcomment[$i] = trim($_POST["MIDIinputcomment_".$i]);
		}
	for($i = 0; $i < $NumberMIDIoutputs; $i++) {
		if(isset($_POST["MIDIoutput_".$i])) $MIDIoutput[$i] = trim($_POST["MIDIoutput_".$i]);
		if(isset($_POST["MIDIoutputname_".$i])) $MIDIoutputname[$i] = trim($_POST["MIDIoutputname_".$i]);
		if(isset($_POST["MIDIoutputcomment_".$i])) $MIDIoutputcomment[$i] = trim($_POST["MIDIoutputcomment_".$i]);
		$MIDIchannelFilter[$i] = $MIDIpartFilter[$i] = '';
		for($channel = 1; $channel <= 16; $channel++) {
			$varName = "channel_out_".$i."_".$channel;
			$MIDIchannelFilter[$i]  .= isset($_POST[$varName]) ? '1' : '0';
			}
		if($MIDIchannelFilter[$i] == "0000000000000000") {
			$MIDIchannelFilter[$i] = "1000000000000000";
			if($warn) echo "<p class=\"red-text warning\">👉 Channel 1 has been set on in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
			}
		for($part = 1; $part <= MAXPARTS; $part++) {
			$varName = "part_out_".$i."_".$part;
			$MIDIpartFilter[$i]  .= isset($_POST[$varName]) ? '1' : '0';
			}
		if($MIDIpartFilter[$i] == "000000000000000000000000000000") {
			$MIDIpartFilter[$i] = "100000000000000000000000000000";
			if($warn) echo "<p class=\"red-text warning\">👉 Part 1 has been set on in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
			}
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
			if($warn) echo "<p class=\"red-text warning\">👉 NoteOn and NoteOff should have the same status in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
		if($SystemExclusiveFilter_out <> $EndSysExFilter_out)
			if($warn) echo "<p style=\"color:red;\">👉 SystemExclusive and EndSysEx should have the same status in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
		if($outFilters[$i] == "000000000000000000") {
			$outFilters[$i] = "110000000000000000";
			if($warn) echo "<p class=\"red-text warning\">👉 NoteOn and NoteOff have been set on in the filter of MIDI output ".$MIDIoutput[$i]."</p>";
			}
		}
	save_midiport($temp_midi_ressources."midiport",$acceptFilters,$passFilters,$outFilters);
	save_midiport($dir_midi_resources.$filename."_midiport",$acceptFilters,$passFilters,$outFilters);
	save_midiport($temp_dir."trace_".my_session_id()."_startup.bpda_midiport",$acceptFilters,$passFilters,$outFilters);
	return;
	}

function save_midiport($thisfilename,$acceptFilters,$passFilters,$outFilters) {
	global $MIDIinput, $MIDIoutput, $MIDIinputname, $MIDIinputcomment, $MIDIoutputname, $MIDIoutputcomment; // These are tables!
	global $NumberMIDIinputs, $NumberMIDIoutputs, $MIDIchannelFilter, $MIDIpartFilter;
	global $permissions;
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
			if(isset($MIDIpartFilter[$i][$i])) fwrite($file,"MIDIpartFilter\t".$i."\t".$MIDIpartFilter[$i]."\n");
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
		chmod($thisfilename,$permissions);
		return true;
		}
	return false;
	}

function filter_form_input($i) {
	global $NoteOffFilter_in, $NoteOnFilter_in, $KeyPressureFilter_in, $ControlChangeFilter_in, $ProgramChangeFilter_in, $ChannelPressureFilter_in, $PitchBendFilter_in, $SystemExclusiveFilter_in, $TimeCodeFilter_in, $SongPositionFilter_in, $SongSelectFilter_in, $TuneRequestFilter_in, $EndSysExFilter_in, $TimingClockFilter_in, $StartFilter_in, $ContinueFilter_in, $ActiveSensingFilter_in, $SystemResetFilter_in, $MIDIinput;
	echo "<div id=\"showhide_input".$i."\"  style=\"width:300px;\">";
	echo "<h3 style=\"margin-left:12px;\"><b>Filter for MIDI input ".$MIDIinput[$i]."</b></h3>";
	echo "<p style=\"margin-left:12px;\">0 = reject<br />1 = treat<br />2 = treat + pass</p>";
	echo "<table class=\"thinborder\">";
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
	echo "<div id=\"showhide_output".$i."\" style=\"width:300px;\">";
	echo "<input class=\"save\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topmidiports\" name=\"savemidiport\" value=\"SAVE MIDI ports\">";
	echo "<h3 style=\"margin-left:12px;\"><b>Filter for MIDI output ".$MIDIoutput[$i]."</b></h3>";
	echo "<table class=\"thinborder\">";
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
	echo "<td style=\"white-space:nowrap;\">";
	echo "<p>Parts</p>";
	for($part = 1; $part <=  MAXPARTS; $part++) {
		filter_part($i,$part);
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

function filter_part($i,$part) {
	global $part_pass;
	$the_post = "part_out_".$i."_".$part;
	echo "<br /><input type=\"checkbox\" name=\"".$the_post."\" value=\"1\"";
	if(!isset($part_pass[$i][$part]) OR $part_pass[$i][$part]) echo " checked";
	echo " />";
	echo $part;
	}

function filter_buttons_out($i,$param) {
	$tablename = $param."Filter_out";
	global $$tablename;
	if(!isset($$tablename[$i])) $$tablename[$i] = 1;
	echo "<tr>";
	echo "<td style=\"font-size:small;\">";
	if($param == "Start") $param = "Start-Stop";
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
	if($param == "Start") $param = "Start-Stop";
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
	if($file_format <> "rtmidi" AND $file_format <> "csoundrt" AND $file_format <> "scrt" AND $file_format <> "osc") return;
	echo "<p>MIDI input filters are used to process incoming events, for example<br />from a connected piano keyboard or other MIDI devices — including<br />another instance of the BP3.</p>
	<ul>
	<li>By default (option ‘0’), events are ‘rejected’ and nothing happens.</li>
	<li>If an event is accepted (option ‘1’), it can trigger a script command<br />declared in the score.</li>
	<li>Accepted events can also be routed to the current MIDI output<br />(option ‘3’).</li>
	<li>Some settings might be changed by the console for consistency.</li>
	</ul>";
	}

function filter_output_explanation() {
	global $file_format;
	if($file_format <> "rtmidi" AND $file_format <> "csoundrt" AND $file_format <> "scrt" AND $file_format <> "osc") return;
	echo "<ul>";
	echo "<li>Event filter specifies which MIDI events can be sent to this output.</li>";
	echo "<li>Channel filter specifies which MIDI channels can be sent to this output.</li>";
	echo "<li>Part filter specifies which parts of a score can be sent to this output.</li>";
	echo "</ul>";
	}

function midi_ports_width_placeholder() {
	// 2026-11-22 Invisible copy of the widest lines of display_midi_ports(), so that the column of a grammar or data page keeps the same width when the format shows no MIDI ports. Fields have no name, so nothing is submitted.
	echo "<div aria-hidden=\"true\" style=\"height:0; overflow:hidden; visibility:hidden;\">";
	echo "<span style=\"white-space:nowrap;\">MIDI output&nbsp;&nbsp;<input type=\"text\" size=\"2\" tabindex=\"-1\">";
	echo "&nbsp;<input type=\"text\" size=\"15\" tabindex=\"-1\">";
	echo "&nbsp;<input type=\"text\" size=\"15\" tabindex=\"-1\">";
	echo "&nbsp;<button type=\"button\" tabindex=\"-1\">Delete</button>";
	echo "&nbsp;<button class=\"edit\" type=\"button\" tabindex=\"-1\">FILTER</button></span><br />";
	echo "<span style=\"white-space:nowrap;\"><button class=\"save\" type=\"button\" tabindex=\"-1\">SAVE MIDI ports</button>";
	echo str_replace(' ',"&nbsp;"," 👉 Delete name if changing number")."</span>";
	echo "</div>";
	return;
	}

function display_midi_ports($filename) {
	global $MIDIoutput, $MIDIoutputname, $MIDIoutputcomment, $MIDIinput, $MIDIinputname, $MIDIinputcomment, $NumberMIDIinputs, $NumberMIDIoutputs, $url_this_page;
	$midiport = read_midiressources($filename);
	if(isset($_POST['create_input'])) {
		if($NumberMIDIinputs > 31) {
			echo "<p id=\"timespan2\" style=\"color:red;\">You can't have more than 32 inputs!</p>";
			}
		else {
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
		echo "<span style=\"white-space:nowrap;\">MIDI output&nbsp;&nbsp;<input type=\"text\" title=\"".$text_over_number."\" onchange=\"tellsave()\" name=\"MIDIoutput_".$i."\" size=\"2\" value=\"".$value."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_name."\" style=\"margin-bottom:6px;\" onchange=\"tellsave()\" name=\"MIDIoutputname_".$i."\" size=\"15\" value=\"".$MIDIoutputname[$i]."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_comment."\" name=\"MIDIoutputcomment_".$i."\" size=\"15\" value=\"".$comment."\">";
		echo "&nbsp;<button type=\"button\" onclick=\"clearFields('MIDIoutput_".$i."','MIDIoutputname_".$i."','MIDIoutputcomment_".$i."')\">Delete</button>";
		echo "&nbsp;<button class=\"edit\" onclick=\"toggledisplay_output(".$i."); return false;\">FILTER</button></span>";
		filter_form_output($i);
		echo "<br />";
		}
	echo "<input class=\"save\" style=\"float:right;\" type=\"submit\" name=\"create_output\" value=\"Add an output\"><br />";
	echo "<span style=\"white-space:nowrap;\"><input class=\"save\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topmidiports\" name=\"savemidiport\" value=\"SAVE MIDI ports\">";
	echo str_replace(' ',"&nbsp;"," 👉 Delete name if changing number")."</span><br /><br />";
	for($i = 0; $i < $NumberMIDIinputs; $i++) {
		if($MIDIinput[$i] == -1) $value = '';
		else $value = $MIDIinput[$i];
		if(!isset($MIDIinputcomment[$i]) OR $MIDIinputcomment[$i] == "void") $comment = '';
		else $comment = $MIDIinputcomment[$i];
		echo "<span style=\"white-space:nowrap;\">MIDI input&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"text\" title=\"".$text_over_number."\" onchange=\"tellsave()\" name=\"MIDIinput_".$i."\" size=\"2\" value=\"".$value."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_name."\" style=\"margin-bottom:6px;\" onchange=\"tellsave()\" name=\"MIDIinputname_".$i."\" size=\"15\" value=\"".$MIDIinputname[$i]."\">";
		echo "&nbsp;<input type=\"text\" title=\"".$text_over_comment."\" name=\"MIDIinputcomment_".$i."\" size=\"15\" value=\"".$comment."\">";
		echo "&nbsp;<button type=\"button\" onclick=\"clearFields('MIDIinput_".$i."', 'MIDIinputname_".$i."', 'MIDIinputcomment_".$i."')\">Delete</button>";
		echo "&nbsp;<button class=\"edit\" onclick=\"toggledisplay_input(".$i."); return false;\">FILTER</button></span>";
		filter_form_input($i);
		echo "<br />";
		}
	echo "<input class=\"save\" style=\"float:right;\" type=\"submit\" name=\"create_input\" value=\"Add an input\">";
	return;
	}

function enter_notes_button($filename,$NumberMIDIinputs,$dir,$settings_file) {
	if(!isset($NumberMIDIinputs) OR $NumberMIDIinputs < 1) return;
	$window_name = "capture";
	$link_capture = "produce.php?instruction=enter_notes";
	$link_capture .= "&settings=".urlencode($dir.$settings_file)."&title=".urlencode($filename)."&keepalive=1";
	echo "&nbsp;<input onclick=\"if(checksaved()) {window.open('".$link_capture."','".$window_name."','width=800,height=800,left=200'); return false;}\" type=\"button\" name=\"produce\" value=\"MIDI enter notes\" class=\"produce\">";
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
	global $permissions;
    $h_variables = fopen($script_variables,'w');
    fwrite($h_variables,"<?php\n");
    $script_status = $script_more = array();
    $content = @file_get_contents("script-instructions.txt");
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	chmod($script_variables,$permissions);
    return;
    }

function copyDirectory($src,$dst) {
    if (!is_dir($src)) return false;
    // Create the destination directory if it doesn't exist
    if (!is_dir($dst)) mkdir($dst, 0775, true);
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

function read_scale_lines($lines) {
	// 2026-11-19 Reads the temporary file of a scale (former line format): note names (by grade, the last one for the octave), key numbers, numgrades, interval, base frequency, base key and ratios
	$scale = array('names' => array(),'keys' => array(),'numgrades' => 0,'basefreq' => 0.,'basekey' => 60,'ratios' => array(),'fline' => array());
	foreach($lines as $line) {
		$line = trim($line);
		if($line == '') continue;
		if($line[0] == '/') $scale['names'] = explode(' ',preg_replace("/\s+/u",' ',trim(str_replace('/','',$line))));
		else if($line[0] == 'k' AND preg_match("/^k(.*)k$/u",$line,$m)) $scale['keys'] = explode(' ',preg_replace("/\s+/u",' ',trim($m[1])));
		else if($line[0] == 'f') {
			$table = explode(' ',preg_replace("/\s+/u",' ',$line));
			$scale['fline'] = $table;
			$scale['numgrades'] = intval($table[4]);
			$scale['basefreq'] = floatval($table[6]);
			$scale['basekey'] = intval($table[7]);
			$scale['ratios'] = array_map('floatval',array_slice($table,8));
			}
		}
	return $scale;
	}

function scale_key_mapping($scale) {
	// 2026-11-19 How BP assigns keys to grades (PrintThisNote() in the console): in a scale of up to 12 grades, consecutive keys play consecutive grades; otherwise they play the named grades, so that the pattern repeats every 'number of named grades' keys
	$numgrades = $scale['numgrades'];
	if($numgrades <= 12) return range(0,$numgrades - 1);
	$mapping = array();
	for($g = 0; $g < $numgrades; $g++)
		if(isset($scale['names'][$g]) AND $scale['names'][$g] <> "•" AND $scale['names'][$g] <> '') $mapping[] = $g;
	return $mapping;
	}

function export_kbm($dir_scales,$scale_name) {
	// 2026-11-19 Writes the KBM file of a scale in the standard format (https://www.huygens-fokker.org/scala/help.htm#mappings): each entry gives the scale degree played by consecutive keys from the middle note, and the size of the map is the number of keys after which the pattern repeats. Called by scale.php and tonality.php, as is the console's ExportKBM()
	global $permissions;
	$content = @file_get_contents($dir_scales.to_scale_file_name($scale_name));
	if($content === FALSE) return FALSE;
	$scale = read_scale_lines(explode("\n",$content));
	if($scale['numgrades'] < 1) return FALSE;
	$mapping = scale_key_mapping($scale);
	$text = "! ".$scale_name.".kbm\n";
	$text .= "! Keyboard mapping (KBM) file\n";
	$text .= "! Size of map. The pattern repeats every so many keys:\n";
	$text .= count($mapping)."\n";
	$text .= "! First MIDI note number to retune:\n0\n";
	$text .= "! Last MIDI note number to retune:\n127\n";
	$text .= "! Middle note where the first entry in the mapping is mapped to:\n".$scale['basekey']."\n";
	$text .= "! Reference note for which frequency is given:\n".$scale['basekey']."\n";
	$text .= "! Frequency to tune the above note to (floating point e.g. 440.0):\n".$scale['basefreq']."\n";
	$text .= "! Scale degree to consider as formal octave:\n".$scale['numgrades']."\n";
	$text .= "! Mapping\n";
	foreach($mapping as $degree) $text .= $degree."\n";
	$file = $dir_scales.$scale_name.".kbm";
	if(@file_get_contents($file) !== $text) {
		file_put_contents($file,$text);
		@chmod($file,$permissions);
		}
	return $file;
	}

function update_scale_with_kbm($scl_name,$scale_file,$kbm_content) {
	// 2026-11-19 Reads a KBM file in the standard format (https://www.huygens-fokker.org/scala/help.htm#mappings): after comments ('!'), the lines are the size of the map, the first and last keys to retune, the middle note, the reference note, its frequency, the scale degree to consider as formal octave, then one scale degree (or 'x' for an unmapped key) per key from the middle note. Unmapped keys at the end may be left out, and a size of 0 is a linear mapping.
	// BP assigns keys to grades by a fixed rule (see scale_key_mapping()), so a mapping is accepted only if it follows that rule: in a scale of up to 12 grades, key i plays degree i (or is unmapped, making the grade unnamed); in a larger scale, consecutive keys play rising degrees from 0, which become the named grades. Mappings exported by BP before 2026-11-19 (one entry per grade, 'x' for unnamed grades) are also accepted.
	// The scale is written to 'temp_scale_file.txt' in $dir_scales. Returns '' or an error message.
	global $dir_scales,$need_to_save;
	if($scale_file == '') {
		$scl_name = preg_replace("/\s+/u",' ',$scl_name);
		$scl_name = str_replace("#","_",$scl_name);
		$scl_name = str_replace("/","_",$scl_name);
		$scale_file = $dir_scales.$scl_name.".txt";
		$need_to_save = TRUE;
		}
	$table_scl = explode("\n",str_replace("\r",'',file_get_contents($scale_file)));
	$scale = read_scale_lines($table_scl);
	$numgrades = $scale['numgrades'];
	if($numgrades < 1 OR count($scale['ratios']) <= $numgrades) return "The scale has no valid table<br />";
	// Values of the KBM file, in order, without comments
	$values = array();
	foreach(explode("\n",str_replace("\r",'',$kbm_content)) as $line) {
		$line = trim($line);
		if($line == '' OR $line[0] == '!') continue;
		$values[] = preg_split("/\s+/u",$line)[0];
		}
	if(count($values) < 7) return "The file should contain at least 7 values (size of map, first and last keys, middle note, reference note, frequency, formal octave)<br />";
	$labels = array("Size of map","First MIDI note number","Last MIDI note number","Middle note","Reference note");
	for($i = 0; $i < 5; $i++)
		if(!preg_match("/^[0-9]+$/",$values[$i])) return $labels[$i]." (".$values[$i].") is not a valid integer<br />";
	$size = intval($values[0]);
	$middle = intval($values[3]);
	$reference = intval($values[4]);
	if($middle > 127 OR $reference > 127) return "Middle note (".$middle.") and reference note (".$reference.") should be in the range [0, 127]<br />";
	if(!is_numeric($values[5]) OR floatval($values[5]) <= 0) return "Frequency (".$values[5].") is not a valid number<br />";
	$frequency = floatval($values[5]);
	if(!preg_match("/^-?[0-9]+$/",$values[6])) return "Scale degree to consider as formal octave (".$values[6].") is not a valid integer<br />";
	$octave_degree = intval($values[6]);
	if($size == 0) $octave_degree = $numgrades; // Linear mapping
	if($octave_degree <> $numgrades) return "Scale degree to consider as formal octave (".$octave_degree.") should be the number of grades of the scale (".$numgrades."), because BP repeats the scale with its interval<br />";
	if($size == 0) {
		$size = $numgrades;
		$entries = range(0,$numgrades - 1);
		}
	else {
		$entries = array();
		for($i = 0; $i < $size; $i++) {
			$entry = isset($values[7 + $i]) ? $values[7 + $i] : 'x'; // Unmapped keys may be left out at the end
			if($entry <> 'x' AND !preg_match("/^-?[0-9]+$/",$entry)) return "Incorrect value (".$entry.") found in the mapping<br />";
			$entries[$i] = ($entry == 'x') ? 'x' : intval($entry);
			}
		}
	// Which grades are named, and the key (relative to the middle note) of each
	$mapped_grades = array();
	$per_grade = TRUE; // Key i plays degree i or is unmapped: linear mapping, possibly with gaps
	if($size <> $numgrades) $per_grade = FALSE;
	foreach($entries as $i => $entry) if($entry !== 'x' AND $entry <> $i) $per_grade = FALSE;
	if($entries[0] !== 0) return "The first entry of the mapping (".$entries[0].") should be 0, the base note of the scale<br />";
	if($per_grade) {
		foreach($entries as $i => $entry) if($entry !== 'x') $mapped_grades[$i] = $i;
		}
	else {
		if($numgrades <= 12) return "In a scale of up to 12 grades, BP plays consecutive grades on consecutive keys, so the mapping should have ".$numgrades." entries 0, 1, 2… (or 'x' for unnamed grades)<br />";
		$previous = -1;
		foreach($entries as $i => $entry) {
			if($entry === 'x') return "Key ".($middle + $i)." is unmapped. In a scale of more than 12 grades, BP plays consecutive named grades on consecutive keys, so each key of the pattern should play a degree<br />";
			if($entry <= $previous OR $entry >= $numgrades) return "Degrees of the mapping should rise from 0 to at most ".($numgrades - 1).", as BP plays consecutive named grades on consecutive keys (found ".$entry." after ".$previous.")<br />";
			$mapped_grades[$entry] = $i;
			$previous = $entry;
			}
		}
	if($numgrades > 12) { // Consecutive keys play the named grades
		ksort($mapped_grades);
		$i = 0;
		foreach($mapped_grades as $g => $key) $mapped_grades[$g] = $i++;
		}
	// Frequency of the base note (middle note) from that of the reference note
	$offset = $reference - $middle;
	$index = (($offset % $size) + $size) % $size;
	$degree = $entries[$index];
	if($degree === 'x') return "The reference note (".$reference.") is unmapped<br />";
	$degree += intdiv($offset - $index,$size) * $numgrades;
	$interval = $scale['ratios'][$numgrades];
	$grade = (($degree % $numgrades) + $numgrades) % $numgrades;
	$ratio = $scale['ratios'][$grade] * pow($interval,intdiv($degree - $grade,$numgrades));
	if($ratio <= 0) return "The reference note (".$reference.") is mapped to a grade without a ratio<br />";
	$basefreq = round($frequency / $ratio,3);
	// Note names and key numbers
	$names = $scale['names'];
	$new_names = $new_keys = array();
	for($g = 0; $g < $numgrades; $g++) {
		$old = (isset($names[$g]) AND $names[$g] <> '') ? $names[$g] : "•";
		if(!isset($mapped_grades[$g])) {
			$new_names[$g] = "•";
			$new_keys[$g] = 0;
			continue;
			}
		$key = $mapped_grades[$g];
		if($old <> "•") $new_names[$g] = $old;
		else if($per_grade AND $numgrades <= 12) $new_names[$g] = "•"; // In a small scale, a linear mapping doesn't name unnamed grades
		else $new_names[$g] = "key#".($middle + $key);
		$new_keys[$g] = $key;
		}
	$period = ($numgrades <= 12) ? $numgrades : count($mapped_grades);
	$new_names[$numgrades] = (isset($names[$numgrades]) AND $names[$numgrades] <> '' AND $names[$numgrades] <> "•") ? $names[$numgrades] : "key#".($middle + $period);
	$new_keys[$numgrades] = $period;
	$fline = $scale['fline'];
	$fline[6] = $basefreq;
	$fline[7] = $middle;
	$handle = fopen($dir_scales."temp_scale_file.txt","w");
	$done_names = $done_keys = FALSE;
	foreach($table_scl as $line) {
		$line = trim($line);
		if($line == '') continue;
		if($line[0] == '/' OR ($line[0] == 'k' AND preg_match("/^k.*k$/u",$line))) continue;
		if($line[0] == 'f') {
			fwrite($handle,"/".implode(' ',$new_names)."/\n");
			fwrite($handle,"k".implode(' ',$new_keys)."k\n");
			$line = implode(' ',$fline);
			}
		fwrite($handle,$line."\n");
		}
	fclose($handle);
	$handle = fopen($dir_scales."_changed","w");
	if($handle) fclose($handle);
	return '';
	}

function make_links_clickable($text) {
    $pattern = '/\b(?:https?|ftp):\/\/[a-zA-Z0-9-_.]+(?:\.[a-zA-Z0-9-_.]+)+(?:\/[^\s]*)?/';
    $text_with_links = preg_replace_callback($pattern, function($matches) {
        $url = $matches[0];
        return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
    	}, $text);
    return $text_with_links;
	}


function midi_player_script($path_to_php) {
	// Returns the script tag of the MIDI file player. $path_to_php leads from the page to the "php" folder
	global $midi_player;
	if($midi_player == "SpessaSynth") return "<script type=\"module\" src=\"".$path_to_php."spessasynth/bp_midi_player.js\"></script>";
	if($midi_player == "MIDIjs") return "<script type='text/javascript' src='https://www.midijs.net/lib/midi.js'></script>";
	if($midi_player == "html-midi-player") return "<script src=\"https://cdn.jsdelivr.net/combine/npm/tone@14.7.58,npm/@magenta/music@1.23.1/es6/core.js,npm/focus-visible@5,npm/html-midi-player@1.4.0\"></script>";
	return '';
	}

function midifile_player($midi_file_link, $filename, $width, $piano_roll) {
	global $bp_application_path,$midi_player;
	$text = "<table><tr>";
	$text .= "<td style=\"padding-right:2em; padding-bottom:1em;\"><p class=\"shadow\" style=\"width:".$width."em;\">";
	if($midi_player == "SpessaSynth") {
		$text .= "<p class=\"shadow\" style=\"width:25em;\"><a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"BPMidi.play('".nice_url($midi_file_link)."');\"><img src=\"".$bp_application_path."php/pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />PLAY</a>";
		$text .= " (<a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"BPMidi.pause();\">Pause</a>) ";
		$text .= " <a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"BPMidi.resume();\">Resume</a>";
		$text .= " (<a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"BPMidi.stop();\">STOP</a>)";
		$text .= "<br /><span style=\"display:flex; justify-content:space-between; margin-left:calc(1cm - 12px); padding-right:12px; position:relative; top:calc(-1.2em + 6px);\"><small class=\"bp-midi-status\"></small>".midi_player_name_link()."</span></p>";
		}
	else if($midi_player == "MIDIjs") {
		$text .= "<p class=\"shadow\" style=\"width:25em;\"><a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"MIDIjs.play('".nice_url($midi_file_link)."');\"><img src=\"".$bp_application_path."php/pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />PLAY</a>";
		$text .= " (<a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"MIDIjs.pause();\">Pause</a>) ";
		$text .= " <a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"MIDIjs.resume();\">Resume</a>";
		$text .= " (<a style=\"color:#007BFF;\" href=\"#midi\" onClick=\"MIDIjs.stop();\">STOP</a>)";
		$text .= "<br /><span style=\"display:flex; justify-content:flex-end; padding-right:12px; position:relative; top:calc(-1.2em + 6px);\">".midi_player_name_link()."</span></p>";
		}
	else if($midi_player == "html-midi-player") {
		$text .= "<midi-player src=\"".nice_url($midi_file_link)."\" sound-font=\"https://storage.googleapis.com/magentadata/js/soundfonts/sgm_plus\" visualizer=\"#myVisualizer\"></midi-player>";
		$text .= "<br /><span style=\"display:flex; justify-content:flex-end; padding-right:24px;\">".midi_player_name_link()."</span>";
		if($piano_roll) $text .= "<midi-visualizer type=\"piano-roll\" id=\"myVisualizer\"></midi-visualizer>";
		}
	$text .= "</td>";
	if($filename <> '') $text .= "<td style=\"vertical-align:middle;\">".$filename."</td>";
	if($midi_player == "html-midi-player")
		$text .= "<td style=\"text-align:right;\"><a href=\"".nice_url($midi_file_link)."\" download>Download&nbsp;MIDI&nbsp;file</a><br /><small>This player does not support<br />volume, pitchbend, pan, etc.<br /><a target=\"_blank\" href=\"https://github.com/cifkao/html-midi-player/\">Visit html-midi-player</a></small></td>";
	else if($midi_player == "MIDIjs" OR $midi_player == "SpessaSynth")
		$text .= "<td style=\"text-align:right; vertical-align:middle;\"><a href=\"".nice_url($midi_file_link)."\" download>Download&nbsp;MIDI&nbsp;file</a></td>";
	$text .= "</tr></table>";
	return $text;
	}

function midi_player_name_link() {
	// Name of the MIDI file player, linked to the page from which it can be downloaded
	global $midi_player;
	$urls = array(
		"SpessaSynth" => "https://github.com/spessasus/spessasynth_lib",
		"MIDIjs" => "https://www.midijs.net/",
		"html-midi-player" => "https://github.com/cifkao/html-midi-player/"
		);
	if(!isset($urls[$midi_player])) return '';
	return "<small><a target=\"_blank\" href=\"".$urls[$midi_player]."\">".$midi_player."</a></small>";
	}

function begin_with($n_chars,$text) {
	$text = (strlen($text) > $n_chars) ? substr($text,0,$n_chars) . "…" : $text;
	return $text;
	}

function apply_changes_instructions($content) {
	$extract_data = extract_data(FALSE,TRUE,$content);
	$newcontent = $extract_data['content'];
	$imax = $_POST['chan_max'];
	for($i = 0; $i < $imax; $i++) {
		$argument = $_POST['argument_chan_'.$i];
		$option = $_POST['replace_chan_option_'.$i];
		switch($option) {
			case "chan":
				$new_argument = "@&".$_POST['replace_chan_as_chan_'.$i];
				if($new_argument >= 0)
					$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@&".$_POST['replace_chan_as_ins_'.$i];
				if($new_argument >= 0)
					$newcontent = str_replace("_chan(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "part":
				$new_argument = "@&".$_POST['replace_chan_as_part_'.$i];
				if($new_argument >= 0)
					$newcontent = str_replace("_chan(".$argument.")","_part(".$new_argument.")",$newcontent);
			break;
			case "chan_ins":
				$new_argument_chan = "@&".$_POST['replace_chan_as_chan1_'.$i];
				$new_argument_ins = "@&".$_POST['replace_chan_as_ins1_'.$i];
				if($new_argument_chan >= 0 AND $new_argument_ins >= 0)
					$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument_chan.") _ins(".$new_argument_ins.")",$newcontent);
			break;
			case "chan_part":
				$new_argument_chan = "@&".$_POST['replace_chan_as_chan2_'.$i];
				$new_argument_part = "@&".$_POST['replace_chan_as_part1_'.$i];
				if($new_argument_chan >= 0 AND $new_argument_part >= 0)
					$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument_chan.") _part(".$new_argument_part.")",$newcontent);
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
				$new_argument = "@&".$_POST['replace_ins_as_chan_'.$j];
				if($new_argument >= 0)
					$newcontent = str_replace("_ins(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@&".$_POST['replace_ins_as_ins_'.$j];
				if($new_argument >= 0)
					$newcontent = str_replace("_ins(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "part":
				$new_argument = "@&".$_POST['replace_ins_as_part_'.$j];
				if($new_argument >= 0)
					$newcontent = str_replace("_ins(".$argument.")","_part(".$new_argument.")",$newcontent);
			break;
			case "ins_chan":
				$new_argument_ins = "@&".$_POST['replace_ins_as_ins1_'.$j];
				$new_argument_chan = "@&".$_POST['replace_ins_as_chan1_'.$j];
				if($new_argument_ins >= 0 AND $new_argument_chan >= 0)
					$newcontent = str_replace("_ins(".$argument.")","_ins(".$new_argument_ins.") _chan(".$new_argument_chan.")",$newcontent);
			break;
			case "ins_part":
				$new_argument_ins = "@&".$_POST['replace_ins_as_ins2_'.$j];
				$new_argument_part = "@&".$_POST['replace_ins_as_part1_'.$j];
				if($new_argument_ins >= 0 AND $new_argument_part >= 0)
					$newcontent = str_replace("_ins(".$argument.")","_ins(".$new_argument_ins.") _part(".$new_argument_part.")",$newcontent);
			break;
			case "delete":
				$newcontent = str_replace("_ins(".$argument.")",'',$newcontent);
			break;
			}
		}

	$kmax = $_POST['part_max'];
	for($k = 0; $k < $kmax; $k++) {
		$argument = $_POST['argument_part_'.$k];
		$option = $_POST['replace_part_option_'.$k];
		switch($option) {
			case "chan":
				$new_argument = "@&".$_POST['replace_part_as_chan_'.$k];
				if($new_argument >= 0)
					$newcontent = str_replace("_part(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@&".$_POST['replace_part_as_ins_'.$k];
				if($new_argument >= 0)
					$newcontent = str_replace("_part(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "part":
				$new_argument = "@&".$_POST['replace_part_as_part_'.$k];
				if($new_argument >= 0)
					$newcontent = str_replace("_part(".$argument.")","_part(".$new_argument.")",$newcontent);
			break;
			case "part_chan":
				$new_argument_part = "@&".$_POST['replace_part_as_part1_'.$k];
				$new_argument_chan = "@&".$_POST['replace_part_as_chan1_'.$k];
				if($new_argument_part >= 0 AND $new_argument_chan >= 0)
					$newcontent = str_replace("_part(".$argument.")","_part(".$new_argument_part.") _chan(".$new_argument_chan.")",$newcontent);
			break;
			case "part_ins":
				$new_argument_part = "@&".$_POST['replace_part_as_part2_'.$k];
				$new_argument_ins = "@&".$_POST['replace_part_as_ins1_'.$k];
				if($new_argument_part >= 0 AND $new_argument_ins >= 0)
					$newcontent = str_replace("_part(".$argument.")","_part(".$new_argument_part.") _ins(".$new_argument_ins.")",$newcontent);
			break;
			case "delete":
				$newcontent = str_replace("_part(".$argument.")",'',$newcontent);
			break;
			}
		}
	return $newcontent;
	}

function show_changes_instructions($content) {
	global $url_this_page;
	$list_of_arguments_chan = list_of_arguments($content,"_chan(");
	$list_of_arguments_ins = list_of_arguments($content,"_ins(");
	$list_of_arguments_part = list_of_arguments($content,"_part(");
	echo "<br /><table class=\"thicktable\">";
	echo "<tr><td><b>Instruction</b></td><td style=\"text-align:center;\"><b>Replace with…</b></td><td><b>Instruction</b></td><td style=\"text-align:center;\"><b>Replace with…</b></td></tr>";
	$imax = count($list_of_arguments_chan);
	echo "<input type=\"hidden\" name=\"chan_max\" value=\"".$imax."\">";
	echo "<tr>";
	for($i = $col = 0; $i < $imax; $i++) {
		echo "<td style=\"vertical-align:middle;\"><span class=\"turquoise-text\"><b>_chan(".$list_of_arguments_chan[$i].")</b></span></td>";
		echo "<input type=\"hidden\" name=\"argument_chan_".$i."\" value=\"".$list_of_arguments_chan[$i]."\">";
		echo "<td style=\"vertical-align:middle; padding:2px;\">";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"chan\"";
		echo " checked";
		echo "> _chan(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_chan_".$i."\" size=\"4\" value=\"".$list_of_arguments_chan[$i]."\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"ins\">";
		echo "_ins(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_ins_".$i."\" size=\"6\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"part\">";
		echo "_part(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_part_".$i."\" size=\"6\" value=\"\">";
		echo ")<br />";

		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"chan_ins\">";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_chan1_".$i."\" size=\"6\" value=\"\">)&nbsp;";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_ins1_".$i."\" size=\"6\" value=\"\">)<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"chan_part\">";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_chan2_".$i."\" size=\"6\" value=\"\">)&nbsp;";
		echo "_part(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_chan_as_part1_".$i."\" size=\"6\" value=\"\">)<br />";
		echo "<input type=\"radio\" name=\"replace_chan_option_".$i."\" value=\"delete\"> delete _chan(".$list_of_arguments_chan[$i].") ";
		echo "</td>";
		$col++;
		if($col == 2) {
			echo "</tr><tr>";
			$col = 0;
			}
		}
	if($col == 1) echo "<td>&nbsp;</td><td>&nbsp;</td>";
	echo "</tr>";
	$jmax = count($list_of_arguments_ins);
	echo "<input type=\"hidden\" name=\"ins_max\" value=\"".$jmax."\">";
	echo "<tr>";
	for($j = $col = 0; $j < $jmax; $j++) {
		echo "<td style=\"vertical-align:middle;\"><span class=\"turquoise-text\"><b>_ins(".$list_of_arguments_ins[$j].")</b></span></td>";
		echo "<input type=\"hidden\" name=\"argument_ins_".$j."\" value=\"".$list_of_arguments_ins[$j]."\">";
		echo "<td style=\"vertical-align:middle; padding:2px;\">";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"chan\"> _chan(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_chan_".$j."\" size=\"4\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"ins\" checked>";
		echo "_ins(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_ins_".$j."\" size=\"6\" value=\"".$list_of_arguments_ins[$j]."\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"part\"> _part(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_part_".$j."\" size=\"4\" value=\"\">";
		echo ")<br />";

		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"ins_chan\">";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_ins1_".$j."\" size=\"6\" value=\"\">)";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_chan1_".$j."\" size=\"6\" value=\"\">)&nbsp;<br />";

		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"ins_part\">";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_ins2_".$j."\" size=\"6\" value=\"\">)";
		echo "_part(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_ins_as_part1_".$j."\" size=\"6\" value=\"\">)&nbsp;<br />";
		echo "<input type=\"radio\" name=\"replace_ins_option_".$j."\" value=\"delete\"> delete _ins(".$list_of_arguments_ins[$j].")";
		echo "</td>";
		$col++;
		if($col == 2) {
			echo "</tr><tr>";
			$col = 0;
			}
		}
	if($col == 1) echo "<td>&nbsp;</td><td>&nbsp;</td>";
	echo "</tr>";
	$kmax = count($list_of_arguments_part);
	echo "<input type=\"hidden\" name=\"part_max\" value=\"".$kmax."\">";
	echo "<tr>";
	for($k = $col = 0; $k < $kmax; $k++) {
		echo "<td style=\"vertical-align:middle;\"><span class=\"turquoise-text\"><b>_part(".$list_of_arguments_part[$k].")</b></span></td>";
		echo "<input type=\"hidden\" name=\"argument_part_".$k."\" value=\"".$list_of_arguments_part[$k]."\">";
		echo "<td style=\"vertical-align:middle; padding:2px;\">";
		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"chan\"> _chan(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_chan_".$k."\" size=\"4\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"ins\" checked>";
		echo "_ins(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_ins_".$k."\" size=\"6\" value=\"\">";
		echo ")<br />";
		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"part\" checked>";
		echo "_part(";
		echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_part_".$k."\" size=\"6\" value=\"".$list_of_arguments_part[$k]."\">";
		echo ")<br />";

		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"part_chan\">";
		echo "_part(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_part1_".$k."\" size=\"6\" value=\"\">)";
		echo "_chan(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_chan1_".$k."\" size=\"6\" value=\"\">)&nbsp;<br />";
		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"part_ins\">";
		echo "_part(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_part2_".$k."\" size=\"6\" value=\"\">)";
		echo "_ins(<input type=\"text\" style=\"border:none; text-align:center;\" name=\"replace_part_as_ins1_".$k."\" size=\"6\" value=\"\">)&nbsp;<br />";
		echo "<input type=\"radio\" name=\"replace_part_option_".$k."\" value=\"delete\"> delete _part(".$list_of_arguments_part[$k].")";
		echo "</td>";
		$col++;
		if($col == 2) {
			echo "</tr><tr>";
			$col = 0;
			}
		}
	if($col == 1) echo "<td>&nbsp;</td><td>&nbsp;</td>";
	echo "</tr>";
	echo "<tr><td></td><td></td><td><input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td><input class=\"produce\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_changes_instructions\" formaction=\"".$url_this_page."#topedition\" value=\"APPLY THESE CHANGES\"></td></tr>";
	echo "</table>";
	return;
	}

function display_note_conventions($note_convention) {
	global $url_this_page, $Englishnote, $AltEnglishnote, $Frenchnote, $AltFrenchnote, $Indiannote, $AltIndiannote;
	$hide = FALSE;
	if(isset($_POST['change_convention']) AND isset($_POST['new_convention'])) {
	//	echo "<form id=\"topchanges\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		$hide = TRUE;
		if(isset($_POST['new_convention'])) $new_convention = $_POST['new_convention'];
		else $new_convention = 0;
		echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
		echo "<input type=\"hidden\" name=\"old_convention\" value=\"".$note_convention."\">";
		echo "<br />";
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
		if(isset($standard_note)) {
			echo "<table>";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\" checked><br /><b><span class=\"red-text\">".$standard_note[$i];
				echo "</span></b></td>";
				}
			echo "</tr>";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				if($alt_note[$i] <> $standard_note[$i]) {
					echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$alt_note[$i]."\"><br /><b><span class=\"red-text\">".$alt_note[$i];
					echo "</span></b>";
					}
				echo "</td>";
				}
			echo "</tr>";
			echo "</table>";
			}
		echo "&nbsp;<input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self'; return true;\" name=\"use_convention\" formaction=\"".$url_this_page."\" value=\"USE THIS CONVENTION\">";

		$hide = TRUE;
	//	echo "</form>";
		}
	return $hide;
	}

function use_convention($this_file) {
	global $Englishnote, $AltEnglishnote, $Frenchnote, $AltFrenchnote, $Indiannote, $AltIndiannote;
	$old_convention = $_POST['old_convention'];
	$new_convention = $_POST['new_convention'];
	echo "<p>old_convention = ".$old_convention."</p>";
	echo "<p>new_convention = ".$new_convention."</p>";
	$change_octave = 0;
	if($old_convention == 1 AND $new_convention <> 1) {
		$change_octave = +1;
		}
	if($old_convention <> '' AND $old_convention <> 1 AND $new_convention == 1) {
		$change_octave = -1;
		}
	$content = @file_get_contents($this_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$extract_data = extract_data(FALSE,TRUE,$content);
	$newcontent = $extract_data['content'];
	if(MULTIBYTE_INTERNAL_OK) mb_internal_encoding("UTF-8");  // Set internal character encoding to UTF-8
	$newcontent = my_mb_ereg_replace("\n","<br>", $newcontent);
	for($i = 0; $i < 12; $i++) {
		$new_note = $_POST['new_note_'.$i];
		if($new_convention <> 1) {
			$bass_octave = "00"; $new_octave = 0;
			$newcontent = my_mb_ereg_replace($Frenchnote[$i].$bass_octave,$new_note."@".$new_octave,$newcontent);
			$newcontent = my_mb_ereg_replace($AltFrenchnote[$i].$bass_octave,$new_note."@".$new_octave,$newcontent);
			}
		for($octave = 15; $octave >= 0; $octave--) {
			$new_octave = $octave + $change_octave;
			if($new_octave < 0) $new_octave = "00";
			if($new_convention <> 0) $newcontent = my_mb_ereg_replace($Englishnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 0) $newcontent = my_mb_ereg_replace($AltEnglishnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = my_mb_ereg_replace($Frenchnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = my_mb_ereg_replace($AltFrenchnote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = my_mb_ereg_replace($Indiannote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = my_mb_ereg_replace($AltIndiannote[$i].$octave,$new_note."@".$new_octave,$newcontent);
			}
		}
	$newcontent = my_mb_ereg_replace("<br>","\n",$newcontent);
	$newcontent = my_mb_ereg_replace("@",'',$newcontent);
	// This '@' is required to avoid confusion between "re" in Indian and Italian/Spanish/French conventions
	$_POST['thistext'] = $newcontent;
	return $new_convention;
	}

function my_mb_ereg_replace($a,$b,$text) {
	if(MULTIBYTE_EREG_OK) $result = mb_ereg_replace($a,$b,$text);
	else $result = str_replace($a,$b,$text);
	return $result;
	}

function select_a_skin($url_this_page,$path,$skin) {
	echo "<div class=\"thinborder\" style=\"float:right; padding:0.5em; margin-left:3em; text-align:center;\">";
	echo "<form method=\"post\" action=\"".$url_this_page."?path=".$path."\" enctype=\"multipart/form-data\">";
	echo "<p>Select a skin:</p>";
	echo "<p><input type=\"radio\" name=\"change_skin\" value=\"0\"";
	if($skin == 0) echo " checked";
	echo ">None<br />";
	echo "<input type=\"radio\" name=\"change_skin\" value=\"1\"";
	if($skin == 1) echo " checked";
	echo ">Blue<br />";
	echo "<input type=\"radio\" name=\"change_skin\" value=\"2\"";
	if($skin == 2) echo " checked";
	echo ">Red<br />";
	echo "<input type=\"radio\" name=\"change_skin\" value=\"3\"";
	if($skin == 3) echo " checked";
	echo ">Green<br />";
	echo "<input type=\"radio\" name=\"change_skin\" value=\"4\"";
	if($skin == 4) echo " checked";
	echo ">Gold</p>";
	echo "<p><input type=\"submit\" class=\"edit\"  value=\"CHANGE\"></p>";
	echo "</form>";
	echo "</div>";
	return;
	}

function select_a_midi_player($url_this_page,$midi_player) {
	// MIDI file player used in all projects, saved in "_settings.php"
	$players = array(
		"SpessaSynth" => "SpessaSynth (off-line)",
		"MIDIjs" => "MIDIjs (Internet)",
		"html-midi-player" => "html-midi-player (Internet,<br />no pitchbend, volume, pan…)"
		);
	echo "<div class=\"thinborder\" style=\"float:right; padding:0.5em; margin-left:3em; text-align:left;\">";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:center;\">MIDI file player:</p><p>";
	foreach($players as $value => $label) {
		echo "<input type=\"radio\" name=\"change_midi_player\" value=\"".$value."\"";
		if($midi_player == $value) echo " checked";
		echo ">".$label."<br />";
		}
	echo "</p>";
	echo "<p style=\"text-align:center;\"><input type=\"submit\" class=\"edit\" value=\"CHANGE\"></p>";
	echo "</form>";
	echo "</div>";
	return;
	}

function show_note_convention_form($type,$note_convention,$settings_file) {
	$this_convention = "English";
	if($note_convention <> '') $this_convention = $note_convention;
	echo "<p>Current note convention for this ".$type." is:<br /><span class=\"red-text\"><b>".ucfirst(note_convention(intval($this_convention)))."</b></span>";
	if($note_convention <> '') echo " as per <span class=\"green-text\">‘".$settings_file."’</span><br />You will need to change it after applying a different convention.</p>";
	else echo " (but the ‘settings’ file wasn't found)</p>";
	return;
	}

function show_file_format_choice($type,$file_format,$url_this_page,$filename) {
	global $temp_midi_ressources,$dir_midi_resources;
	echo "<span style=\"display:inline-block; white-space:nowrap;\">"; // 2026-11-21 The column is as wide as the longest format name
	echo "<input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"rtmidi\"";
	if($file_format == "rtmidi") echo " checked";
	echo ">Real-time MIDI";
	if($type == "grammar") {
		echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"data\"";
		if($file_format == "data") echo " checked";
		echo ">BP data file";
		}
	echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"midi\"";
	if($file_format == "midi") echo " checked";
	echo ">MIDI file";
	if(csound_ready()) {
		echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"csound\"";
		if($file_format == "csound") echo " checked";
		echo ">Csound score";
		echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"csoundrt\"";
		if($file_format == "csoundrt") echo " checked";
		echo ">Real-time Csound";
		}
	if(scrt_ready() OR $file_format == "scrt" OR $file_format == "osc") {
		echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"scrt\"";
		if($file_format == "scrt") echo " checked";
		echo ">Real-time SuperCollider";
		}
	echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"osc\"";
	if($file_format == "osc") echo " checked";
	echo ">Real-time OSC (Max, Pd…)";
	echo "<br /><input type=\"radio\" style=\"margin-bottom:0.5em;\" name=\"new_file_format\" value=\"eventlist\"";
	if($file_format == "eventlist") echo " checked";
	echo ">Event list";
	echo "</span>";
	echo "<br /><input class=\"save\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topmidiports\" name=\"savethisfile\" value=\"SAVE format\">";
	if($file_format == "csoundrt") csoundrt_format_notes();
	if($file_format == "scrt") scrt_format_notes();
	if($file_format == "osc") oscrt_format_notes();
	if($file_format == "eventlist") { // 2026-11-21
		global $csound_file;
		if(isset($csound_file) AND $csound_file <> '' AND file_exists(csound_resource_path($csound_file)))
			echo "<br /><br />Event list uses ".resource_file_link("csound",$csound_file)."<br />to write the names of the Csound instruments.";
		}
	return;
	}

function list_of_supercollider_resources() {
	// 2026-11-21 Names of the "-sc" files in the SuperCollider resources folders, except backups and locks
	$list = array();
	foreach(resource_files("supercollider") as $thisfile => $dir) {
		if(!str_starts_with($thisfile,"-sc.")) continue;
		if(str_ends_with($thisfile,"_bak") OR preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		if(!is_file($dir.$thisfile)) continue;
		$list[] = $thisfile;
		}
	return $list;
	}

function list_of_csound_resources() {
	// Names of the "-cs" files in the Csound resources folders ("csound_resources_private" and "csound_resources"), except backups and locks
	$list = array();
	foreach(csound_resource_files() as $thisfile => $dir) {
		if(!str_starts_with($thisfile,"-cs.") AND !str_ends_with($thisfile,".bpcs")) continue;
		if(str_ends_with($thisfile,"_bak") OR preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		if(!is_file($dir.$thisfile)) continue;
		$list[] = $thisfile;
		}
	return $list;
	}

// 2026-11-19 Csound resources are found in two folders: "csound_resources_private" (files created or changed by the user) and "csound_resources" (fixed files of the distribution, replaced by updates). A name found in both is that of the file in "csound_resources_private". The same applies to tonality resources ("tonality_resources_private" and "tonality_resources"). $kind is "csound" or "tonality" - by Claude

function resource_dirs($kind) {
	// Folders (with their final SLASH) and names of the fixed and user folders of resources of $kind
	global $dir_csound_resources,$dir_csound_resources_private,$csound_resources,$csound_resources_private;
	global $dir_tonality_resources,$dir_tonality_resources_private,$tonality_resources,$tonality_resources_private;
	global $dir_supercollider_resources,$dir_supercollider_resources_private,$supercollider_resources,$supercollider_resources_private;
	if($kind == "tonality") return array('fixed' => $dir_tonality_resources,'my' => $dir_tonality_resources_private,'fixed_name' => $tonality_resources,'my_name' => $tonality_resources_private);
	if($kind == "supercollider") return array('fixed' => $dir_supercollider_resources,'my' => $dir_supercollider_resources_private,'fixed_name' => $supercollider_resources,'my_name' => $supercollider_resources_private);
	return array('fixed' => $dir_csound_resources,'my' => $dir_csound_resources_private,'fixed_name' => $csound_resources,'my_name' => $csound_resources_private);
	}

function resource_dir($kind,$filename) {
	// Folder of resource $filename: the user folder if it is there, otherwise the fixed folder if it is there, otherwise the user folder where it will be created
	$d = resource_dirs($kind);
	$filename = trim((string) $filename);
	if($filename <> '' AND file_exists($d['my'].$filename)) return $d['my'];
	if($filename <> '' AND file_exists($d['fixed'].$filename)) return $d['fixed'];
	return $d['my'];
	}

function resource_path($kind,$filename) {
	return resource_dir($kind,$filename).trim((string) $filename);
	}

function resource_folder($kind,$filename) {
	// Folder of resource $filename relative to the root of the application, for the 'file' argument of pages
	$d = resource_dirs($kind);
	if(resource_dir($kind,$filename) == $d['fixed']) return $d['fixed_name'];
	return $d['my_name'];
	}

function resource_files($kind) {
	// Files of both folders of $kind, in alphabetical order: name => folder (with its final SLASH). Names starting with '.' are ignored. A name found in both folders is that of the user folder.
	$d = resource_dirs($kind);
	$files = array();
	foreach(array($d['fixed'],$d['my']) as $dir) {
		$dircontent = @scandir($dir);
		if($dircontent === FALSE) continue;
		foreach($dircontent as $thisfile) {
			if($thisfile[0] == '.') continue;
			$files[$thisfile] = $dir;
			}
		}
	uksort($files,"strnatcasecmp");
	return $files;
	}

function is_resource_folder($kind,$dir) {
	$d = resource_dirs($kind);
	return same_folder($dir,$d['fixed']) OR same_folder($dir,$d['my']);
	}

function resource_kind_of_type($type) {
	// Kind of resource of a file of type $type (see type_of_file()), or ''
	if($type == "csound" OR $type == "csorchestra") return "csound";
	if($type == "tonality") return "tonality";
	if($type == "supercollider" OR $type == "scsynthdef") return "supercollider";
	return '';
	}

function new_resource_folder($kind) {
	// Folder where a new resource is created: the user folder, or the fixed folder if the designer checked 'in_fixed'
	global $i_am_designer;
	$d = resource_dirs($kind);
	if($i_am_designer AND isset($_POST['in_fixed'])) return $d['fixed'];
	return $d['my'];
	}

function move_to_resources($kind,$dir,$filename) {
	// A resource found in folder $dir (that of a project) is moved to the user folder, replacing a file with the same name there, as it formerly replaced the one in the fixed folder. A fixed file with the same name is not changed: the moved file takes its place.
	$d = resource_dirs($kind);
	if($filename == '' OR !is_file($dir.$filename) OR is_resource_folder($kind,$dir)) return;
	rename($dir.$filename,$d['my'].$filename);
	return;
	}

// 2026-11-20 These follow $cs_kind, which is "supercollider" in the pages of '-sc' files, so that csound.php and csorchestra.php handle them too
function csound_resource_dir($filename) { global $cs_kind; return resource_dir($cs_kind,$filename); }
function csound_resource_path($filename) { global $cs_kind; return resource_path($cs_kind,$filename); }
function csound_resource_folder($filename) { global $cs_kind; return resource_folder($cs_kind,$filename); }
function csound_resource_files() { global $cs_kind; return resource_files($cs_kind); }
function is_csound_resource_folder($dir) { global $cs_kind; return is_resource_folder($cs_kind,$dir); }
function new_csound_resource_folder() { global $cs_kind; return new_resource_folder($cs_kind); }
function move_to_csound_resources($dir,$filename) { global $cs_kind; move_to_resources($cs_kind,$dir,$filename); return; }
function tonality_resource_dir($filename) { return resource_dir("tonality",$filename); }
function tonality_resource_path($filename) { return resource_path("tonality",$filename); }
function tonality_resource_folder($filename) { return resource_folder("tonality",$filename); }
function is_tonality_resource_folder($dir) { return is_resource_folder("tonality",$dir); }

function csound_resource_is_fixed($filename) {
	// TRUE if $filename is only in "csound_resources"
	global $dir_csound_resources;
	$filename = trim((string) $filename);
	return ($filename <> '' AND csound_resource_dir($filename) == $dir_csound_resources AND file_exists($dir_csound_resources.$filename));
	}

function list_of_tonality_resources() {
	// Names of the "-to" files in both tonality resource folders, except backups and locks
	$list = array();
	foreach(resource_files("tonality") as $thisfile => $dir) {
		if(!str_starts_with($thisfile,"-to.") AND !str_ends_with($thisfile,".bpto")) continue;
		if(str_ends_with($thisfile,"_bak") OR preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		if(!is_file($dir.$thisfile)) continue;
		$list[] = $thisfile;
		}
	return $list;
	}

function same_folder($dir1,$dir2) {
	$real1 = realpath($dir1);
	$real2 = realpath($dir2);
	if($real1 === FALSE OR $real2 === FALSE) return rtrim($dir1,"/\\") == rtrim($dir2,"/\\");
	return $real1 == $real2;
	}

function read_only_resource_folder($dir) {
	// TRUE if files in folder $dir can't be changed: "csound_resources", "tonality_resources" or "supercollider_resources", unless $i_am_designer
	global $dir_csound_resources,$dir_tonality_resources,$dir_supercollider_resources,$i_am_designer;
	if($i_am_designer) return FALSE;
	return same_folder($dir,$dir_csound_resources) OR same_folder($dir,$dir_tonality_resources) OR same_folder($dir,$dir_supercollider_resources);
	}

function csound_sample_option() {
	// 2026-11-19 Option of the Csound command line telling it to look for sound files (samples, e.g. "Salamander_piano/...") in "csound_resources" as well. Csound looks for them in the folder of the orchestra, which may be "csound_resources_private" - by Claude
	global $dir_csound_resources;
	$folder = realpath($dir_csound_resources);
	if($folder === FALSE) return '';
	return " \"--env:SSDIR+=".$folder."\"";
	}

function new_version_name($filename) {
	// 2026-11-19 Name given to a fixed resource whose name is that of a file of the user folder: "-cs.NAME_new", "NAME_new.orc", "-to.NAME_new", "NAME_new.bpto"... Returns '' for other files - by Claude
	if(preg_match("/^-(cs|to|sc)\./",$filename)) return $filename."_new";
	if(preg_match("/^(.+)\.(orc|bpcs|bpto|scd)$/",$filename,$m)) return $m[1]."_new.".$m[2];
	return '';
	}

function is_new_version_name($filename) {
	return (preg_match("/^-(cs|to|sc)\./",$filename) AND str_ends_with($filename,"_new")) OR preg_match("/_new\.(orc|bpcs|bpto|scd)$/",$filename);
	}

function rename_new_fixed_resources() {
	// 2026-11-19 An update of the distribution may bring to a fixed folder a file with the same name as one of the user folder, which would hide it. It is renamed with "_new" (see new_version_name()), replacing an older "_new" version, and marked with a red arrow in the list of resources - by Claude
	foreach(array("csound","tonality","supercollider") as $kind) {
		$d = resource_dirs($kind);
		$dircontent = @scandir($d['my']);
		if($dircontent === FALSE) continue;
		foreach($dircontent as $thisfile) {
			if($thisfile[0] == '.') continue;
			if(!is_file($d['fixed'].$thisfile)) continue;
			$new_name = new_version_name($thisfile);
			if($new_name == '') continue;
			@rename($d['fixed'].$thisfile,$d['fixed'].$new_name);
			}
		}
	return;
	}

function duplicate_resource($kind,$filename,$new_name) {
	// 2026-11-19 Copies resource $filename (either folder) to the user folder as $new_name, which is completed like $filename ("-cs.", ".orc", "-to.", ".bpto"...) and may not be that of a file in either folder. Returns a message - by Claude
	global $permissions;
	$d = resource_dirs($kind);
	$source = resource_path($kind,$filename);
	$type = type_of_file($filename)['type'];
	if($filename == '' OR !is_file($source) OR resource_kind_of_type($type) <> $kind)
		return "<p><span class=\"red-text\">➡ Can't duplicate</span> ‘".htmlspecialchars($filename)."’</p>";
	$new_name = trim(fix_new_name($new_name,TRUE));
	if($type == "csorchestra" OR $type == "scsynthdef") {
		$ext = ($type == "scsynthdef") ? ".scd" : ".orc";
		$new_name = preg_replace("/".preg_quote($ext,"/")."$/",'',$new_name);
		if($new_name <> '') $new_name .= $ext;
		}
	else {
		$short = ($kind == "tonality") ? "to" : (($kind == "supercollider") ? "sc" : "cs");
		$name_mode = str_ends_with($filename,".bp".$short) ? "extension" : "prefix";
		$bare = str_ireplace(array("-".$short.".",".bp".$short),'',$new_name);
		if($bare == '') $new_name = '';
		else $new_name = good_name($short,$new_name,$name_mode);
		}
	if($new_name == '' OR $new_name[0] == '.')
		return "<p><span class=\"red-text\">➡ Invalid name</span></p>";
	if(file_exists(resource_path($kind,$new_name)))
		return "<p><span class=\"red-text\">➡ Can't duplicate</span> ‘<span class=\"green-text\">".$filename."</span>’ <span class=\"red-text\">to existing</span> ‘<span class=\"green-text\">".htmlspecialchars($new_name)."</span>’</p>";
	if(!copy($source,$d['my'].$new_name))
		return "<p><span class=\"red-text\">➡ Failed to copy</span> ‘".$filename."’</p>";
	@chmod($d['my'].$new_name,$permissions);
	$link = $type.".php?file=".urlencode(resource_folder($kind,$new_name).SLASH.$new_name);
	$more = ($kind == "csound") ? private_csound_resource_independent($type,$new_name) : ''; // 2026-11-20 Orchestra and samples
	return "<p>‘<span class=\"green-text\">".$filename."</span>’ <span class=\"red-text\">➡</span> duplicated as <a target=\"_blank\" href=\"".nice_url($link)."\">".$new_name."</a> in ‘".$d['my_name']."’</p>".$more;
	}

function private_csound_resource_independent($type,$name) {
	// 2026-11-20 Called when a Csound resource has been copied to "csound_resources_private" (Duplicate, RENAME OR COPY), $type being
	// that of type_of_file(). The copy must keep working if a later distribution changes or deletes the fixed files it uses:
	// - a '-cs' file whose orchestra is fixed gets a copy of it under its own name ("-cs.Beethoven_try" -> "Beethoven_try.orc"),
	//   which it names from now on. Its tonality file is not copied: tonality resources are duplicated on their own page.
	// - the samples and other files named in the orchestra (e.g. "Iowa_strings/violin/...") are copied to "csound_resources_private"
	//   unless they are already there, see copy_orchestra_files(). Csound finds them in the folder of the orchestra.
	// Returns a message (HTML) listing the copies - by Claude
	global $permissions;
	$d = resource_dirs("csound");
	$path = $d['my'].$name;
	if(!is_file($path)) return '';
	$copied = array();
	if($type == "csorchestra") $orchestra_path = $path;
	else if($type == "csound") {
		$content = @file_get_contents($path);
		if($content === FALSE OR !csound_is_json($content)) return '';
		$base = preg_replace(array("/^-cs\\./","/\\.bpcs$/"),'',$name);
		$new_content = $content;
		$orchestra_path = '';
		if(preg_match('/"key"\s*:\s*"CsoundOrchestraName"\s*,\s*"value"\s*:\s*"([^"]*)"/',$content,$match) AND $match[1] <> '') {
			$orchestra_path = csound_resource_path($match[1]);
			if(!is_file($orchestra_path)) {
				$copied[] = "<span class=\"red-text\">orchestra ‘".htmlspecialchars($match[1])."’ was not found</span>";
				$orchestra_path = '';
				}
			else if(same_folder(dirname($orchestra_path),$d['fixed'])) {
				$new_orchestra = unique_resource_name("csound",$base,'',".orc");
				if(copy($orchestra_path,$d['my'].$new_orchestra)) {
					@chmod($d['my'].$new_orchestra,$permissions);
					$new_content = preg_replace('/("key"\s*:\s*"CsoundOrchestraName"\s*,\s*"value"\s*:\s*")[^"]*"/','${1}'.$new_orchestra.'"',$new_content,1);
					$copied[] = "orchestra ‘<span class=\"green-text\">".$match[1]."</span>’ as ‘<span class=\"green-text\">".$new_orchestra."</span>’";
					}
				}
			}
		if($new_content <> $content) file_put_contents($path,$new_content);
		}
	else return '';
	if($orchestra_path <> '') $copied = array_merge($copied,copy_orchestra_files($orchestra_path));
	if($type == "csound") {
		// Files named in the Csound tables of the '-cs' file, e.g. f1 0 256 1 "vina-wave-table.aiff" 0 4 0 in "-cs.Vina"
		$copied = array_merge($copied,copy_files_named_in(csound_table_lines($path)));
		}
	if(count($copied) == 0) return '';
	return "<p>➡ So that ‘<span class=\"green-text\">".$name."</span>’ keeps working if the distribution changes, these were copied to ‘".$d['my_name']."’:<br />• ".implode("<br />• ",$copied)."</p>";
	}

function unique_resource_name($kind,$base,$prefix,$extension) {
	// 2026-11-20 A name $prefix.$base.$extension used in neither folder of $kind, with "_2", "_3"... added to $base if necessary - by Claude
	$name = $prefix.$base.$extension;
	for($i = 2; file_exists(resource_path($kind,$name)); $i++) $name = $prefix.$base."_".$i.$extension;
	return $name;
	}

function csound_sound_extensions() {
	// 2026-11-20 Extensions of the sound files that Csound reads (libsndfile), accepted by UPLOAD ASSOCIATED FILES/FOLDERS of csound.php - by Claude
	return array("aif","aiff","aifc","wav","wave","flac","ogg","oga","mp3","caf","au","snd","w64","rf64","sd2");
	}

function csound_upload_document_extensions() {
	// 2026-11-20 Other files accepted in an uploaded folder of samples, e.g. "ReadMe.txt". Others, such as ".php", are refused,
	// since these folders are under the web server - by Claude
	return array("txt","md","pdf","rtf");
	}

function csound_table_lines($path) {
	// 2026-11-20 The Csound table lines ("f1 0 256 1 ...") of '-cs' file $path, one per line - by Claude
	$tables = '';
	$json = json_decode((string)@file_get_contents($path),TRUE);
	if(is_array($json)) {
		array_walk_recursive($json,function($value,$key) use(&$tables) {
			if(is_string($value) AND preg_match('/^\s*f\s*-?\d/',$value)) $tables .= $value."\n";
			});
		}
	return $tables;
	}

function csound_private_dependencies() {
	// 2026-11-20 Files and folders of "csound_resources_private" that are not Csound resources: samples, wave tables... copied with
	// private resources (see private_csound_resource_independent()), or placed there by the user. They are listed on top of the Csound
	// resource page, where they can be deleted to free space. For each one: 'folder' (TRUE for a folder), 'size' (bytes),
	// 'users' (private orchestras and '-cs' files naming it) and 'fixed' (TRUE if "csound_resources" has one with the same name,
	// which Csound will find instead) - by Claude
	$d = resource_dirs("csound");
	$items = array();
	$resources = array();
	foreach(@scandir($d['my']) ?: array() as $thisfile) {
		if($thisfile[0] == '.') continue;
		$thistype = type_of_file($thisfile)['type'];
		if(!is_dir($d['my'].$thisfile) AND resource_kind_of_type($thistype) <> '') {
			if($thistype == "csorchestra" OR $thistype == "csound") $resources[$thisfile] = $thistype;
			continue;
			}
		$size = 0;
		if(is_dir($d['my'].$thisfile)) {
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d['my'].$thisfile,FilesystemIterator::SKIP_DOTS)) as $file) $size += $file->getSize();
			}
		else $size = (int) @filesize($d['my'].$thisfile);
		$items[$thisfile] = array('folder' => is_dir($d['my'].$thisfile),'size' => $size,'users' => array(),'fixed' => file_exists($d['fixed'].$thisfile));
		}
	foreach($resources as $thisfile => $thistype) {
		$text = ($thistype == "csorchestra") ? (string) @file_get_contents($d['my'].$thisfile) : csound_table_lines($d['my'].$thisfile);
		if(!preg_match_all('/"([^"\n]+)"/',$text,$matches)) continue;
		foreach($matches[1] as $string) {
			$top = explode('/',str_replace("\\",'/',trim($string)))[0];
			if(isset($items[$top])) $items[$top]['users'][$thisfile] = TRUE;
			}
		}
	uksort($items,"strnatcasecmp");
	return $items;
	}

function copy_orchestra_files($orchestra_path) {
	// 2026-11-20 Copies to "csound_resources_private" the files and folders of "csound_resources" named in the orchestra by a relative path,
	// e.g. "Iowa_strings/violin/violin_pp_055_G.flac" copies the folder "Iowa_strings", unless it is already in "csound_resources_private".
	// Resource files (-cs, .orc, -to...) are not copied this way. Returns the list of copies (HTML) - by Claude
	$content = @file_get_contents($orchestra_path);
	if($content === FALSE) return array();
	return copy_files_named_in($content);
	}

function copy_files_named_in($content) {
	// 2026-11-20 Same as copy_orchestra_files() for Csound code given as text, e.g. the table lines of a '-cs' file - by Claude
	global $permissions;
	$d = resource_dirs("csound");
	if(!preg_match_all('/"([^"\n]+)"/',$content,$matches)) return array();
	$copied = array();
	foreach(array_unique($matches[1]) as $string) {
		$string = str_replace("\\",'/',trim($string));
		if($string == '' OR $string[0] == '/' OR preg_match('/^[A-Za-z]:/',$string) OR is_integer(strpos($string,".."))) continue; // Relative names only
		$top = explode('/',$string)[0];
		if($top == '' OR $top[0] == '.' OR isset($copied[$top])) continue;
		$source = $d['fixed'].$top;
		if(!file_exists($source) OR file_exists($d['my'].$top)) continue;
		if(is_file($source) AND resource_kind_of_type(type_of_file($top)['type']) <> '') continue;
		rcopy($source,$d['my'].$top);
		@chmod($d['my'].$top,$permissions); // As other files of the user, which may then be changed (Linux)
		$size = 0;
		if(is_dir($source)) {
			foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d['my'].$top,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $file) {
				@chmod($file->getPathname(),$permissions);
				if($file->isFile()) $size += $file->getSize();
				}
			$copied[$top] = (is_dir($source) ? "folder" : "file")." ‘<span class=\"green-text\">".$top."</span>’ (".round($size / 1048576)." MB)";
			}
		else $copied[$top] = "file ‘<span class=\"green-text\">".$top."</span>’";
		}
	return array_values($copied);
	}

function read_only_page_script() {
	// Makes the fields of a page read-only, and hides its buttons of class "save" or "trash", except elements of class "read-only-keep". Called on pages of fixed resources.
	echo "<script>\n";
	echo "document.addEventListener('DOMContentLoaded',function() {\n";
	echo "	document.querySelectorAll('input[type=text]:not(.read-only-keep),input:not([type]):not(.read-only-keep),input[type=number]:not(.read-only-keep),textarea:not(.read-only-keep)').forEach(function(e) { e.readOnly = true; });\n";
	echo "	document.querySelectorAll('select:not(.read-only-keep),input[type=checkbox]:not(.read-only-keep),input[type=radio]:not(.read-only-keep)').forEach(function(e) { e.disabled = true; });\n";
	echo "	document.querySelectorAll('input.save:not(.read-only-keep),button.save:not(.read-only-keep),input.trash,button.trash,.read-only-hide').forEach(function(e) { e.style.display = 'none'; });\n";
	echo "	});\n";
	echo "</script>\n";
	return;
	}

function read_only_message($filename,$what = "Csound resource",$folder = "csound_resources") {
	echo "<p><span class=\"red-text\">➡ ‘".$filename."’ is a fixed ".$what." of the distribution: it can be viewed but not changed.</span><br />To change it, make a copy under another name with its Duplicate button in the <a class=\"linkdotted\" target=\"_blank\" href=\"index.php?path=".$folder."\">resource folder</a>.</p>";
	return;
	}

function add_csound_resource_to_content($content,$csound_file,$for_osc = FALSE) {
	// Inserts the line naming a "-cs" file after the resource files ("-se.", "-al.", "x.bpse", ...) at the top of a grammar or data,
	// and removes the line of another "-cs" file found there (2026-11-18)
	// 2026-11-21 The same for a "-sc" file, replacing the line of another "-sc" file
	$sc = str_starts_with($csound_file,"-sc.");
	$remove_sc = ($sc OR $for_osc); // In real-time OSC, a '-cs' file chosen replaces the '-sc' file too, otherwise it would not be used
	$content = str_replace(chr(13).chr(10),chr(10),$content);
	$table = explode(chr(10),$content);
	$out = array();
	$insert_at = -1;
	$top = TRUE;
	foreach($table as $line) {
		$trimmed = trim($line);
		if($top) {
			if(preg_match("/^-[a-zA-Z]{2}\.\S/u",$trimmed) OR preg_match("/^\S+\.bp[a-z]{2}$/u",$trimmed)) {
				if($remove_sc AND str_starts_with($trimmed,"-sc.")) continue;
				if(!$sc AND (str_starts_with($trimmed,"-cs.") OR str_ends_with($trimmed,".bpcs"))) continue;
				$out[] = $line;
				$insert_at = count($out);
				continue;
				}
			if($trimmed <> '' AND !str_starts_with($trimmed,"//")) $top = FALSE;
			}
		$out[] = $line;
		}
	if($insert_at < 0) array_unshift($out,$csound_file);
	else array_splice($out,$insert_at,0,array($csound_file));
	return implode(chr(10),$out);
	}

function change_instruments_form($kind = "csound",$current = '') {
	// "Change instruments" button, displayed when the default orchestra is used because the project names no "-cs" file.
	// It shows the list of "-cs" files. The selected file is added on top of the grammar or data, which is saved
	// (handled by 'set_csound_file' in grammar.php and data.php). Must be called inside the form of the page.
	// 2026-11-21 With $kind = "supercollider", the same for '-sc' files, in real-time SuperCollider (scrt_panel())
	// 2026-11-21 Also displayed when the project names a file ($current, selected in the menu), which is then replaced
	// 2026-11-21 With $kind = "osc" (real-time OSC, oscrt_panel()), the menu lists the '-sc' then the '-cs' files: choosing a '-cs' file
	// also removes the '-sc' line of the project, which real-time OSC would otherwise use ('change_for_osc', see add_csound_resource_to_content())
	global $url_this_page;
	if($kind == "osc") $list = array_merge(list_of_supercollider_resources(),list_of_csound_resources());
	else $list = ($kind == "supercollider") ? list_of_supercollider_resources() : list_of_csound_resources();
	if(count($list) == 0) {
		echo "<br />";
		return;
		}
	echo " <button type=\"button\" class=\"save\" onclick=\"if(checksaved()) { var d = document.getElementById('change_instruments'); d.style.display = (d.style.display == 'none') ? 'inline' : 'none'; }\">Change instruments</button><br />";
	echo "<span id=\"change_instruments\" style=\"display:none;\">";
	if($kind == "osc") echo "<input type=\"hidden\" name=\"change_for_osc\" value=\"1\">";
	echo "<select name=\"new_csound_file\">";
	foreach($list as $thisfile) echo "<option value=\"".htmlspecialchars($thisfile)."\"".($thisfile == $current ? " selected" : '').">".htmlspecialchars($thisfile)."</option>";
	echo "</select> ";
	echo "<input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self'; return true;\" name=\"set_csound_file\" formaction=\"".$url_this_page."#topmidiports\" value=\"USE THESE INSTRUMENTS\"><br />";
	echo "</span>";
	return;
	}

function resource_file_link($kind,$name) {
	// 2026-11-21 Name of a '-cs' ($kind = "csound") or '-sc' ($kind = "supercollider") file, with a link opening its page in a new tab
	$page = ($kind == "supercollider") ? "supercollider.php" : "csound.php";
	$url = $page."?file=".urlencode(resource_folder($kind,$name).SLASH.$name);
	return "<a class=\"linkdotted\" target=\"_blank\" href=\"".$url."\">‘".htmlspecialchars($name)."’</a>";
	}

function csound_instruments_details($csound_file,$orchestra) {
	// 2026-11-21 The instruments of the '-cs' file, or of the orchestra if the project names no '-cs' file,
	// listed on the panel of Csound scores and real-time Csound and shown by the "Show/hide details" button (formerly in show_instruments_and_scales())
	$html = '';
	if($csound_file == '') {
		// 2026-11-18 No '-cs' file: _ins(n) selects instrument n of the orchestra, "0-default.orc" by default
		if($orchestra == '' OR !file_exists(csound_resource_path($orchestra))) $orchestra = "0-default.orc";
		$list = orchestra_instruments(csound_resource_path($orchestra));
		// The default orchestra is described by CSOUND_DEFAULT_RESOURCE, which produce.php attaches: its names can be used in _ins()
		$names = array();
		if($orchestra == "0-default.orc" AND file_exists(csound_resource_path(CSOUND_DEFAULT_RESOURCE))) {
			$default_instruments = list_of_instruments(csound_resource_path(CSOUND_DEFAULT_RESOURCE));
			foreach($default_instruments['list'] as $i => $name) $names[intval($default_instruments['index'][$i])] = $name;
			}
		if(count($list) > 0) {
			$html .= "Orchestra <span class=\"green-text\">‘".$orchestra."’</span> contains instrument(s)";
			if(count($names) > 0) $html .= " described in <span class=\"green-text\">‘".CSOUND_DEFAULT_RESOURCE."’</span>";
			$html .= ":<ul style=\"margin-top:0px; margin-bottom:0px\">";
			foreach($list as $number => $text) {
				$html .= "<li>";
				if(isset($names[$number])) $html .= "<b>_ins(</b><span class=\"turquoise-text\">".$names[$number]."</span><b>)</b> = ";
				$html .= "<b>_ins(".$number.")</b>";
				if($text <> '') $html .= " ➡ ".htmlspecialchars($text);
				$html .= "</li>";
				}
			$html .= "</ul>";
			$html .= "Notes without “_ins()” are played by instrument 1.<br />";
			}
		}
	if($csound_file <> '' AND file_exists(csound_resource_path($csound_file))) {
		$list_of_instruments = list_of_instruments(csound_resource_path($csound_file));
		$list = $list_of_instruments['list'];
		$list_index = $list_of_instruments['index'];
		if(($max_instr = count($list)) > 0) {
			$html .= "Instruments of ".resource_file_link("csound",$csound_file).":";
			$html .= "<ul style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_instr = 0; $i_instr < $max_instr; $i_instr++) {
				$html .= "<li><b>_ins(</b><span class=\"turquoise-text\">".$list[$i_instr]."</span><b>)</b>";
				$html .= " = <b>_ins(".$list_index[$i_instr].")</b>";
				$param_list = $list_of_instruments['param'][$i_instr];
				if(count($param_list) > 0) {
					$html .= " ➡ parameter(s) ";
					for($i_param = 0; $i_param < count($param_list); $i_param++) {
						$html .= " “<span class=\"turquoise-text\">".$param_list[$i_param]."</span>”";
						}
					}
				$html .= "</li>";
				}
			$html .= "</ul>";
			}
		else $html .= "Csound resource file <span class=\"green-text\">‘".$csound_file."’</span> does not contain definitions of instrument(s). The default instrument <span class=\"green-text\">‘0-default.orc’</span> will be used.";
		}
	return $html;
	}

function csound_orchestra_line($orchestra,$csound_file = 'none') {
	// Orchestra used for Csound scores and real-time Csound, displayed on project pages (2026-11-18)
	// "➡ edit" opens the orchestra in csorchestra.php, whose 'file' argument is relative to the root of the application
	// If $csound_file is empty (the project names no "-cs" file), the "Change instruments" button is displayed
	$default = ($orchestra == '');
	if($default) $orchestra = "0-default.orc";
	echo "Orchestra: <span class=\"green-text\">".$orchestra."</span>";
	if($default) echo " (default)";
	if(file_exists(csound_resource_path($orchestra))) {
		if(read_only_resource_folder(csound_resource_dir($orchestra))) $label = "view";
		else $label = "edit";
		echo " ➡ <a class=\"linkdotted\" target=\"_blank\" href=\"csorchestra.php?file=".urlencode(csound_resource_folder($orchestra).SLASH.$orchestra)."\">".$label."</a>";
		}
	if($csound_file == '') change_instruments_form();
	else {
		echo "<br />";
		if($csound_file <> 'none' AND file_exists(csound_resource_path($csound_file))) { // 2026-11-21
			echo "Instrument names and parameters are defined in ".resource_file_link("csound",$csound_file).".";
			change_instruments_form("csound",$csound_file);
			}
		}
	if($csound_file <> 'none') {
		// 2026-11-21 The list of instruments is shown by the "Show/hide details" button (no reload), as in real-time SuperCollider
		$details = csound_instruments_details($csound_file,$default ? '' : $orchestra);
		if($details <> '') {
			echo "<button type=\"button\" class=\"edit\" style=\"font-size:small; margin-top:4px;\" onclick=\"var b = document.getElementById('cs_details'); b.style.display = (b.style.display == 'none') ? '' : 'none';\">Show/hide details</button><br />";
			echo "<div id=\"cs_details\" style=\"display:none;\">".$details."<br /></div>";
			}
		}
	return;
	}

function csoundrt_panel($orchestra,$csound_file = 'none') {
	// Displayed on project pages instead of the name of the output file, when the output is real-time Csound (2026-11-16)
	// The description and the QUIT CSOUND button are displayed below the SAVE format button by csoundrt_format_notes()
	echo "<p>";
	csound_orchestra_line($orchestra,$csound_file);
	if(!csound_ready()) echo "<span class=\"red-text\">Csound ".CSOUND_MIN_VERSION." or later is not responding.</span><br />";
	// The status is updated by the script of csoundrt_format_notes()
	// data-orchestra is the orchestra of this project, compared with the one of the running server (2026-11-18)
	$page_orchestra = ($orchestra <> '') ? $orchestra : "0-default.orc";
	echo "<span id=\"csoundrt_status\" data-orchestra=\"".htmlspecialchars($page_orchestra)."\">";
	if(csoundrt_running()) {
		$state = csoundrt_state();
		echo "The Csound server is running";
		if(isset($state['orchestra'])) {
			$server_orchestra = basename($state['orchestra']);
			echo " with <span class=\"green-text\">".$server_orchestra."</span>";
			if($server_orchestra <> $page_orchestra) echo "<br />It will be restarted with <span class=\"green-text\">".$page_orchestra."</span> when you play.";
			}
		}
	else echo "Csound will be started when you play.";
	echo "</span><br />";
	echo "The MIDI ports below are used for interaction,<br />e.g. <i>_script(wait for C3 channel 1)</i>, once saved.";
	echo "</p>";
	return;
	}

function csoundrt_format_notes() {
	// Displayed below the SAVE format button when the output is real-time Csound
	// The QUIT CSOUND button is shown while the server is running. Since the server is started by produce.php in another window,
	// the page asks csoundrt_status.php every 2 seconds, and updates the button and the status displayed by csoundrt_panel().
	echo "<br /><br /><small>Notes are sent to Csound, which plays them on the audio output of this computer.</small>";
	// 2026-11-20 The "settings" button opens a dialog setting the buffers of the server for this machine (csoundrt_settings.php)
	$buffers = csoundrt_buffers();
	$default = csoundrt_default_buffers();
	echo "&nbsp;<button type=\"button\" class=\"edit\" style=\"font-size:small;\" title=\"Buffers of real-time Csound on this computer\" onclick=\"document.getElementById('csoundrt_dialog').showModal();\">Settings</button>";
	// The fields have no 'name', so that they are not sent with the form of the page.
	// The dialog is in a <template>, then attached to the body by the script below: a <dialog> inside the paragraph
	// of the page would close it, adding vertical space before the QUIT CSOUND button.
	echo "<template id=\"csoundrt_dialog_template\"><dialog id=\"csoundrt_dialog\" style=\"max-width:30em; padding:1em; border-radius:8px;\">";
	echo "<p><b>Real-time Csound on this computer</b></p>";
	echo "<p><small>Larger buffers prevent gaps in the sound, especially in the first seconds of a performance after a long time without Csound. They delay the sound by the time of the hardware buffer.</small></p>";
	echo "<p>";
	// Menus of powers of 2 (csoundrt_buffer_choices())
	foreach(array("software" => "Software buffer (-b): ","hardware" => "Hardware buffer (-B): ") as $which => $label) {
		echo $label."<select id=\"csoundrt_".$which."\"".($which == "hardware" ? " onchange=\"csoundrtShowDelay();\"" : '').">";
		foreach(csoundrt_buffer_choices($which) as $size) echo "<option value=\"".$size."\"".($size == $buffers[$which] ? " selected" : '').">".$size."</option>";
		echo "</select> frames";
		echo ($which == "software") ? "<br />" : " <span id=\"csoundrt_delay\"></span>";
		}
	echo "<br /><small>The hardware buffer must be at least twice the software buffer.</small></p>";
	echo "<p><small>Default values on ".csoundrt_system().": -b ".$default['software'].", -B ".$default['hardware']."<br />";
	echo "<span id=\"csoundrt_which\">".($buffers['user'] ? "Values set for this computer" : "Default values are used")."</span></small></p>";
	echo "<p id=\"csoundrt_settings_message\" class=\"red-text\"></p>";
	echo "<p style=\"text-align:center;\"><button type=\"button\" class=\"save\" onclick=\"csoundrtSaveSettings('save');\">SAVE</button>&nbsp;";
	echo "<button type=\"button\" class=\"edit\" onclick=\"csoundrtSaveSettings('default');\">DEFAULT VALUES</button>&nbsp;";
	echo "<button type=\"button\" class=\"edit\" onclick=\"document.getElementById('csoundrt_dialog').close();\">CLOSE</button></p>";
	echo "</dialog></template>";
	echo "<script>
	(function() {
		var template = document.getElementById('csoundrt_dialog_template');
		if(template && !document.getElementById('csoundrt_dialog')) document.body.appendChild(template.content.cloneNode(true));
		})();
	function csoundrtShowDelay() {
		var hardware = parseInt(document.getElementById('csoundrt_hardware').value,10);
		document.getElementById('csoundrt_delay').textContent = (hardware > 0) ? '(about ' + Math.round(1000 * hardware / 44100) + ' ms at 44100 Hz)' : '';
		}
	function csoundrtSaveSettings(action) {
		var data = new FormData();
		data.append('action',action);
		data.append('software',document.getElementById('csoundrt_software').value);
		data.append('hardware',document.getElementById('csoundrt_hardware').value);
		var message = document.getElementById('csoundrt_settings_message');
		fetch('csoundrt_settings.php',{method:'POST',body:data,cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {
			if(d.error) {
				message.textContent = d.error;
				return;
				}
			document.getElementById('csoundrt_software').value = d.software;
			document.getElementById('csoundrt_hardware').value = d.hardware;
			document.getElementById('csoundrt_which').textContent = d.user ? 'Values set for this computer' : 'Default values are used';
			csoundrtShowDelay();
			message.textContent = 'Saved. The Csound server will use these buffers from the next performance.';
			}).catch(function() {message.textContent = 'These values could not be saved.';});
		}
	csoundrtShowDelay();
	</script>";
	echo "<br /><button id=\"csoundrt_quit\" type=\"button\" class=\"edit\" style=\"font-size:small; margin-top:6px;";
	if(!csoundrt_running()) echo " display:none;";
	echo "\" title=\"Csound quits by itself after ".round(CSOUND_RT_IDLE / 60)." minutes without a performance\" onclick=\"csoundrtQuit();\">QUIT CSOUND</button>";
	echo "<script>
	function csoundrtShow(running,orchestra,text) {
		var button = document.getElementById('csoundrt_quit');
		var status = document.getElementById('csoundrt_status');
		if(button) button.style.display = running ? '' : 'none';
		if(!status) return;
		if(text) status.textContent = text;
		else if(running) {
			status.textContent = 'The Csound server is running' + (orchestra ? ' with ' : '');
			if(orchestra) {
				var name = document.createElement('span');
				name.className = 'green-text';
				name.textContent = orchestra;
				status.appendChild(name);
				// The orchestra of this project is used when the server is restarted, i.e. at the next performance
				var wanted = status.getAttribute('data-orchestra');
				if(wanted && wanted !== orchestra) {
					status.appendChild(document.createElement('br'));
					status.appendChild(document.createTextNode('It will be restarted with '));
					var next = document.createElement('span');
					next.className = 'green-text';
					next.textContent = wanted;
					status.appendChild(next);
					status.appendChild(document.createTextNode(' when you play.'));
					}
				}
			}
		else status.textContent = 'Csound will be started when you play.';
		}
	function csoundrtQuit() {
		fetch('csoundrt_quit.php',{cache:'no-store'}).then(function(r) {return r.json();}).then(function(d) {
			csoundrtShow(!d.stopped,'',d.stopped ? 'The Csound server has quit.' : 'The Csound server did not quit.');
			});
		}
	(function() {
		var last = null;
		function poll() {
			fetch('csoundrt_status.php',{cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {
				var key = d.running + '|' + d.orchestra;
				if(key !== last) {
					if(last !== null) csoundrtShow(d.running,d.orchestra,'');
					last = key;
					}
				}).catch(function() {});
			window.setTimeout(poll,2000);
			}
		poll();
		})();
	</script>";
	return;
	}

function scrt_panel($sc_file,$csound_file = '') {
	// Displayed on project pages instead of the name of the output file, when the output is real-time SuperCollider (2026-11-20)
	// The instruments are those of the '-sc' file of the project, or of SC_DEFAULT_RESOURCE which produce.php gives the console otherwise.
	// Instrument NAME is played by SynthDef NAME.
	echo "<p>";
	if(scrt_programs() === FALSE AND !scrt_ping(0.3))
		echo "<span class=\"red-text\">SuperCollider was not found on this computer.</span> Install it from <a class=\"linkdotted\" target=\"_blank\" href=\"https://supercollider.github.io/\">supercollider.github.io</a><br />";
	echo "<span id=\"scrt_status\">";
	if(scrt_running()) echo scrt_owned() ? "The SuperCollider server started by the Bol Processor is running." : "A SuperCollider server is running (started by SuperCollider): it will play.";
	else echo "The SuperCollider server will be started when you play.";
	echo "</span><br />";
	// 2026-11-21 The instruments, SynthDefs, recordings and the folder of the user's files are in a block shown by the "Details" button (no reload)
	$details = '';
	$resource = $sc_file;
	if($sc_file <> '' AND file_exists(resource_path("supercollider",$sc_file))) { // 2026-11-21
		echo "Instruments: ".resource_file_link("supercollider",$sc_file);
		change_instruments_form("supercollider",$sc_file);
		}
	if($resource == '' OR !file_exists(resource_path("supercollider",$resource))) {
		if($sc_file <> '') echo "<span class=\"red-text\">‘".htmlspecialchars($sc_file)."’ was not found in ‘supercollider_resources’.</span><br />";
		else { // 2026-11-21 As for Csound (csound_orchestra_line()): the default instruments, and the "Change instruments" button adding a '-sc' file to the project
			echo "Instruments: ".resource_file_link("supercollider",SC_DEFAULT_RESOURCE)." (default)";
			change_instruments_form("supercollider");
			}
		if($sc_file == '' AND $csound_file <> '') $details .= "<span class=\"red-text\">This project names no ‘-sc’ file:</span> ‘".htmlspecialchars($csound_file)."’ is only used by Csound.<br />To play its instruments in SuperCollider, write a ‘-sc’ file with the same instruments and add its name (e.g. ‘-sc.".htmlspecialchars(preg_replace('/^-cs\./','',$csound_file))."’) on top of the project.<br />";
		$resource = SC_DEFAULT_RESOURCE;
		}
	$names = array();
	if(file_exists(resource_path("supercollider",$resource))) {
		$instruments = list_of_instruments(resource_path("supercollider",$resource));
		foreach($instruments['list'] as $i => $name) $names[intval($instruments['index'][$i])] = $name;
		}
	ksort($names);
	$synthdefs = scrt_synthdef_names();
	$missing = 0;
	if(count($names) > 0) {
		$details .= "Instruments of ".resource_file_link("supercollider",$resource)." and their SynthDefs:";
		$details .= "<ul style=\"margin-top:0px; margin-bottom:0px\">";
		foreach($names as $number => $name) {
			$details .= "<li><b>_ins(</b><span class=\"turquoise-text\">".htmlspecialchars($name)."</span><b>)</b> = <b>_ins(".$number.")</b> ➡ ";
			if(isset($synthdefs[$name])) $details .= "SynthDef <b>".htmlspecialchars($name)."</b>".($synthdefs[$name] == "private" ? " (private)" : '');
			else {
				$details .= "<span class=\"red-text\">SynthDef ‘".htmlspecialchars($name)."’ is not defined</span>";
				$missing++;
				}
			$details .= "</li>";
			}
		$details .= "</ul>";
		}
	$sets = scrt_sample_sets(scrt_scd_of_resource($resource)); // 2026-11-20 Recordings played by its SynthDefs
	if(count($sets) > 0) $details .= "Recordings: ".htmlspecialchars(implode(", ",array_map(function($map) { return basename($map,".json"); },$sets)))." (loaded into the server the first time you play)<br />";
	$details .= "Your SynthDefs (files ‘*.scd’) and ‘-sc’ files go to folder <a class=\"linkdotted\" target=\"_blank\" href=\"index.php?path=supercollider_resources\">‘supercollider_resources_private’</a>.<br />";
	echo "<button type=\"button\" class=\"edit\" style=\"font-size:small; margin-top:4px;\" onclick=\"var b = document.getElementById('scrt_details'); b.style.display = (b.style.display == 'none') ? '' : 'none';\">Show/hide details</button>";
	// Missing SynthDefs are mentioned even when the details are hidden
	if($missing > 0) echo " <span class=\"red-text\">".$missing." SynthDef".($missing > 1 ? "s are" : " is")." not defined</span>";
	echo "<br /><div id=\"scrt_details\" style=\"display:none;\">".$details."<br /></div>";
	echo "The MIDI ports below are used for interaction,<br />e.g. <i>_script(wait for C3 channel 1)</i>, once saved.";
	echo "</p>";
	return;
	}

function scrt_format_notes() {
	// Displayed below the SAVE format button when the output is real-time SuperCollider (2026-11-20)
	// The QUIT SUPERCOLLIDER button is shown while a server started by the Bol Processor is running.
	// Since the server is started by produce.php in another window, the page asks scrt_status.php every 2 seconds.
	echo "<br /><br /><small>Notes are sent as OSC messages to the SuperCollider server, which plays each instrument with the SynthDef of the same name (e.g. ‘piano’ for <i>_ins(piano)</i>).</small><br />";
	echo "<small>Read <span class=\"green-text\">supercollider_resources/0-default.scd</span> to write your own.</small>";
	echo "<br /><button id=\"scrt_quit\" type=\"button\" class=\"edit\" style=\"font-size:small; margin-top:6px;";
	if(!scrt_owned() OR !scrt_running()) echo " display:none;";
	echo "\" title=\"The server started by the Bol Processor quits by itself after ".round(SC_RT_IDLE / 60)." minutes without a performance\" onclick=\"scrtQuit();\">QUIT SUPERCOLLIDER</button>";
	echo "<script>
	function scrtShow(running,owned,text) {
		var button = document.getElementById('scrt_quit');
		var status = document.getElementById('scrt_status');
		if(button) button.style.display = (running && owned) ? '' : 'none';
		if(!status) return;
		if(text) status.textContent = text;
		else if(running) status.textContent = owned ? 'The SuperCollider server started by the Bol Processor is running.' : 'A SuperCollider server is running (started by SuperCollider): it will play.';
		else status.textContent = 'The SuperCollider server will be started when you play.';
		}
	function scrtQuit() {
		fetch('scrt_quit.php',{cache:'no-store'}).then(function(r) {return r.json();}).then(function(d) {
			scrtShow(!d.stopped,true,d.stopped ? 'The SuperCollider server has quit.' : 'The SuperCollider server did not quit.');
			});
		}
	(function() {
		var last = null;
		function poll() {
			fetch('scrt_status.php',{cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {
				var key = d.running + '|' + d.owned;
				if(key !== last) {
					if(last !== null) scrtShow(d.running,d.owned,'');
					last = key;
					}
				}).catch(function() {});
			window.setTimeout(poll,2000);
			}
		poll();
		})();
	</script>";
	return;
	}

function areFilesDifferent($file1,$file2) {
    // Check if both files exist
    if (!file_exists($file1) || !file_exists($file2)) return TRUE;
    $content1 = file_get_contents($file1);
    $content2 = file_get_contents($file2);
    return $content1 !== $content2;
	}

function store_midiressources($filename) {
	global $temp_midi_ressources, $dir_midi_resources;
	$file1 = $temp_midi_ressources."midiport";	// The temporary file
	$file2 = $dir_midi_resources.$filename."_midiport"; // The storage
	if(areFilesDifferent($file1,$file2)) {
		copy($file1,$file2);
		}
	return;
	}

function save($this_file,$filename,$top_header,$save_content) {
	global $permissions,$current_directory;
	$the_warning = '';
	if(trim($save_content) == '') return;
    if(file_exists($this_file)) {
		// No "_bak" copy in the shared test folder
		if(!is_integer(strpos($this_file,"ctests_git"))) {
			$backup_file = fix_new_name($this_file,FALSE)."_bak";
			if(!copy($this_file,$backup_file))
				echo "<p>👉 <span class=\"red-text\">Failed to create backup of the file.</span></p>";
			else @chmod($backup_file,$permissions);
			}
		$handle = @fopen($this_file,"w");
		if($handle) {
			$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
			fwrite($handle, $file_header."\n");
			$save_content = cut_linefeeds_in_remarks($save_content); // 2026-04-23
			$save_content = add_linefeeds_after_remarks($save_content); // 2026-04-23
			$save_content = recode_entities($save_content);
			fwrite($handle, $save_content);
			fclose($handle);
			@chmod($this_file,$permissions);
			}
		else {
			$the_warning .= "<div style=\"background-color:white; color:black; padding-bottom:6px; padding-right:6px; padding-left:1em; border-radius:12px; width:50%; word-wrap: break-word; border: 2px solid red;\"><p>⚠️ <span class=\"red-text\"><b>PROBLEM:</b> This file <span class=\"red-text\">“".$filename."”</span> has been imported and cannot be modified.</span></p>";
			if(linux_system()) $the_warning .= "<p><b>Linux user:</b> Open your terminal and type: <span class=\"red-text\">sudo /opt/lampp/htdocs/bolprocessor/change_permissions.sh</span><br />(Your password will be required...)</p>";
			if(mac_system()) $the_warning .= "<p><b>Mac user:</b> In the <i>Finder info</i>, set to read-write permission the whole content of your <span class=\"red-text\">“".$current_directory."”</span> data folder</p>";
			if(windows_system()) $the_warning .= "<p><b>Windows user:</b> I can't explain this issue.</p>";
		// if(file_exists($backup_file)) $the_warning .= "<p>👉 Another solution is to work with the backup <span class=\"red-text\">“".$filename."_bak”</span> which has now been created. Click the <b>Workspace button</b> at the top of this page to access it.</p>";
			$the_warning .= "</div>";
			}
		}
	return $the_warning;
	}

function convert_to_json($dir,$settings_file) {
	// Convert an old settings file to JSON format
	global $top_header;
	if(!file_exists($dir.$settings_file)) {
		echo "<p>👉 Not found “".$dir.$settings_file."”</p>";
		return;
		}
	$content = @file_get_contents($dir.$settings_file);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$token = get_settings_tokens();
	if(count($token) == 0) return;
	if($content[0] == '{') {
		$settings = json_decode($content,TRUE);
		if(!isset($settings['Time_res'])) {
			echo "<p>👉 Converting an old JSON file</p>";
			$new_settings = array();
			foreach ($settings as $name => $item) {
				if($name == "Csound trace") $name = "Trace Csound";
				if($name == "Ignore constraints") $name = "Ignore constraints in time setting";
				if($name == "GraphicScaleP") $name = "Graphic scale P";
				if($name == "GraphicScaleQ") $name = "Graphic scale Q";
				if($name == "SamplingRate") $name = "Sampling rate";
				if(isset($token[str_replace('_',' ',$name)])) {
					$newKey = $token[str_replace('_',' ',$name)];
					if(is_array($item)) {
						$new_settings[$newKey] = [
							'name' => $name,
							'value' => $item['value'],
							'unit' => $item['unit'],
							'boolean' => $item['boolean'],
							];
						}
					else $new_settings[$newKey] = $item; // Never happens
					}
				else if($name == "header") $new_settings[$name] = $item;
				}
			$new_settings = json_encode($new_settings,JSON_PRETTY_PRINT);
			my_file_put_contents($dir.$settings_file,$new_settings);
			}
		return;
		}
	echo "<p>👉 Settings file will be converted to JSON format</p>";
	$bp_parameter_names = @file_get_contents("settings_names_old.txt");
	if($bp_parameter_names === FALSE) {
		echo "<p style=\"color:red;\">ERROR reading ‘settings_names_old.txt’</p>";
		return;
		}
	if(MB_CONVERT_OK) $bp_parameter_names = mb_convert_encoding($bp_parameter_names,'UTF-8','UTF-8');
	$table = explode(chr(10),$bp_parameter_names);
	$imax = count($table);
	$imax_parameters = 0;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$line = str_replace('"','',$line);
		if($line == "-- end --") break;
		$imax_parameters++;
		$table2 = explode(chr(9),$line);
		if(count($table2) < 3) echo "ERROR: ".$table2[0]."<br />";
		$parameter_name[$i] = $table2[0];
		$parameter_unit[$i] = $table2[1];
		$parameter_edit[$i] = $table2[2];
		if(count($table2) > 3 AND $table2[3] > 0)
			$parameter_yesno[$i] = 1;
		else $parameter_yesno[$i] = 0;
		}
	$extract_data = extract_data(FALSE,TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$imax_file = count($table);
	echo "<p class=\"green-text\">".$imax_file." parameters</p>";
	$imax = $imax_file; $start = TRUE;
	if($imax_file < $imax_parameters) $imax = $imax_parameters;
	$settings = array();
	$file_header = $top_header."\n// Settings file saved as \"".$settings_file."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
	$settings['header'] = str_replace('"',"'",$file_header);
	for($i = $j = 0; $i < $imax; $i++) {
		$value = trim($table[$i]);
		if($start AND !ctype_digit($value)) {
			echo "Skipping old header = “".$value."”<br />";
			$j++;
			continue; // Eliminate old versions of headers
			}
		$start = FALSE;
		if(!isset($parameter_edit[$i]) OR !$parameter_edit[$i]) {
			$j++;
			continue;
			}
		if(!isset($parameter_name[$i]) OR $parameter_name[$i] == '') {
			$j++;
			continue;
			}
		$name = $parameter_name[$i];
		if($name == "Csound trace") $name = "Trace Csound";
		if($name == "Ignore constraints") $name = "Ignore constraints in time setting";
		if($name == "GraphicScaleP") $name = "Graphic scale P";
		if($name == "GraphicScaleQ") $name = "Graphic scale Q";
		if($name == "SamplingRate") $name = "Sampling rate";
		if(isset($token[$name])) {
			$the_token = $token[$name];
			$settings[$the_token]['name'] = $name;
			$settings[$the_token]['value'] = $value;
			$settings[$the_token]['unit'] = $parameter_unit[$i];
			$settings[$the_token]['boolean'] = $parameter_yesno[$i];
			}
		}
	$settings['ComputeWhilePlay']['name'] = "Compute while playing";
	$settings['ComputeWhilePlay']['value'] = 1;
	$settings['ComputeWhilePlay']['unit'] = "";
	$settings['ComputeWhilePlay']['boolean'] = 1;
	$settings['MaxConsoleTime']['value'] = "60";
	$settings['MIDIsyncDelay']['value'] = "380";
	$settings['DefaultBlockKey']['value'] = "60";
	$settings['MaxItemsProduce']['value'] = "20";
	$settings['Time_res']['value'] = "10";
	$settings['Quantization']['value'] = "10";
	$settings['Quantize']['value'] = "1";
	$settings['Nature_of_time']['value'] = "1";
	$settings['DeftBufferSize']['value'] = "1000";
	$settings['NoConstraint']['value'] = "0";
	$settings['DisplayTimeSet']['value'] = "0";
	$settings['TraceTimeSet']['value'] = "0";
	$settings['TraceMIDIinteraction']['value'] = "0";
	$settings['StrikeAgainDefault']['value'] = "1";
	$settings['SamplingRate']['value'] = "50";
	$settings['C4key']['value'] = "60";
	$settings['DefaultBlockKey']['value'] = "60";
	$settings['A4freq']['value'] = "440";
	$settings['DeftVolume']['value'] = "64";
	$settings['VolumeController']['value'] = "7";
	$settings['DeftVelocity']['value'] = "64";
	$settings['DeftPanoramic']['value'] = "64";
	$settings['PanoramicController']['value'] = "10";
	$settings['B#_instead_of_C']['value'] = "0";
	$settings['Db_instead_of_C#']['value'] = "0";
	$settings['Eb_instead_of_D#']['value'] = "0";
	$settings['Fb_instead_of_E']['value'] = "0";
	$settings['E#_instead_of_F']['value'] = "0";
	$settings['Gb_instead_of_F#']['value'] = "0";
	$settings['Ab_instead_of_G#']['value'] = "0";
	$settings['Bb_instead_of_A#']['value'] = "0";
	$settings['Cb_instead_of_B']['value'] = "0";
	$settings['SplitTimeObjects']['value'] = "1";
	$settings['SplitVariables']['value'] = "0";
	$settings['CsoundTrace']['value'] = "0";
	$settings['AllowRandomize']['value'] = "1";
	$settings['Seed']['value'] = "0";
	$settings['ResetNotes']['value'] = "1";
	$settings = recursive_strval($settings);
	$jsonData = json_encode($settings,JSON_PRETTY_PRINT);
    my_file_put_contents($dir.$settings_file,$jsonData);
	return;
	}

function get_settings_tokens() {
	$token = array();
	$bp_parameter_names = @file_get_contents("settings_names.tab");
	if($bp_parameter_names === FALSE) {
		echo "<p style=\"color:red;\">ERROR reading ‘settings_names.tab’</p>";
		return $token;
		}
	if(MB_CONVERT_OK) $bp_parameter_names = mb_convert_encoding($bp_parameter_names,'UTF-8','UTF-8');
	$table = explode(chr(10),$bp_parameter_names);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$line = str_replace('"','',$line);
		if($line == "-- end --") break;
		$table2 = explode(chr(9),$line);
		if(count($table2) < 2  OR trim($table2[1]) == '') continue;
		$token[$table2[1]] = $table2[0];
	//	echo $token[$table2[1]]." => “".$table2[1]."”<br />";
		}
	return $token;
	}

function recursive_strval($data) {
    if(is_array($data)) return array_map('recursive_strval',$data);
    return strval($data);
	}

function my_file_put_contents($path,$content) {
	global $dir, $bad_settings;
	$bad_settings = FALSE;
	$result = @file_put_contents($path,$content);
	if($result === FALSE) {
		$error = error_get_last();
		echo "<p>Failed to write to the file: ".$path;
		echo "<br />Error: ".$error['message']."</p>";
		$thisfile = str_replace($dir,'',$path);
		$type_of_file = type_of_file($thisfile);
		if($type_of_file['type'] == "settings") {
			$bad_settings = TRUE;
			}
		return FALSE;
		}
	return TRUE;
	}

function zip_this_folder($folder) {
	$rootPath = realpath($folder);
	$zipFile = $folder.".zip";
	if(file_exists($zipFile)) unlink($zipFile);
	$zip = new ZipArchive();
	$n = 0;
	if($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
		// Create recursive directory iterator
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($rootPath),
			RecursiveIteratorIterator::LEAVES_ONLY);
		foreach ($files as $name => $file) {
			// Skip directories (they will be added automatically)
			if(is_integer($pos=strpos($file,".DS_Store"))) continue;
			if (!$file->isDir()) {
				// Get real and relative paths
				$filePath = $file->getRealPath();
				$relativePath = substr($filePath, strlen($rootPath) + 1);
				// Add file to zip
				$zip->addFile($filePath, $relativePath);
				$n++;
		//		echo $n." ".$file."<br />";
				}
			}
		$zip->close();
//		echo "Folder successfully zipped to $zipFile";
		}
	else {
		echo "<p>⚠️ Failed to create zip file</p>";
		return FALSE;
		}
	return $n;
	}

function replace_spaced_dashes_with_count($str) {
    return preg_replace_callback(
        '/(?:\s*-\s*)+/',       // one or more "-" possibly separated by spaces
        function($m) {
            $count = substr_count($m[0], '-');  // how many "-" in the matched chunk
            return ' ' . $count . ' ';          // keep spaces around the number
			},
			$str
		);
	}

function normalize_parentheses($text) {
    return preg_replace_callback(
        '/\(([^)]*)\)/',   // match anything inside parentheses
        function ($m) {
            $inside = $m[1];
            $inside = str_replace('-', '§', $inside); // replace -
            $inside = str_replace(',', '@', $inside); // replace ,
            $inside = preg_replace('/\s+/', '', $inside); // remove spaces
            return '(' . $inside . ')';
        },
        $text
    );
}


function simplify_fractions($line) {
    return preg_replace_callback(
        '/\b(\d+)\/(\d+)\b/',    // match numerator/denominator
        function($m) {
            $num = (int)$m[1];
            $den = (int)$m[2];
            // Compute GCD using Euclid's algorithm
            $a = $num;
            $b = $den;
            while ($b != 0) {
                $t = $b;
                $b = $a % $b;
                $a = $t;
            	}
            $gcd = $a;
            // Reduce fraction
            $num /= $gcd;
            $den /= $gcd;
            return $num.'/'.$den;
			},
		$line);
	}

function add_list_of_integers($text) {
    return preg_replace_callback(
        '/(\s)(?:\d+\s+)+\d+(\s)/',   // [space][int (space int)+][space]
        function ($matches) {
            // Extract all numbers from the matched block
            preg_match_all('/\d+/', $matches[0], $nums);

            // $matches[1] = leading space, $matches[2] = trailing space
            return $matches[1] . array_sum($nums[0]) . $matches[2];
			},
		$text);
	}

function add_list_of_ratios($text) {
    return preg_replace_callback(
        // [space] (int or a/b)+ separated by spaces [space]
        '/(\s)(?:(?:\d+\/\d+|\d+)\s+)+(?:\d+\/\d+|\d+)(\s)/',
        function ($matches) {
            // $matches[0] is the whole matched chunk including spaces
            // $matches[1] = leading space, $matches[2] = trailing space
            // Get all integer or fraction tokens
            preg_match_all('/\d+(?:\/\d+)?/', $matches[0], $nums);
            // Sum as fraction: start at 0/1
            $num = 0;  // numerator
            $den = 1;  // denominator
            foreach ($nums[0] as $tok) {
                if (strpos($tok, '/') !== false) {
                    list($n, $d) = array_map('intval', explode('/', $tok, 2));
                } else {
                    $n = (int)$tok;
                    $d = 1;
                	}
                // num/den + n/d  = (num*d + n*den) / (den*d)
                $num = $num * $d + $n * $den;
                $den = $den * $d;
            	}
            // Simplify the fraction by gcd
            $g = function ($a, $b) {
                $a = abs($a);
                $b = abs($b);
                if ($a == 0 && $b == 0) return 1;
                if ($b == 0) return $a ?: 1;
                while ($b != 0) {
                    $t = $b;
                    $b = $a % $b;
                    $a = $t;
                	}
                return $a ?: 1;
            	};
            $gcd = $g($num, $den);
            $num /= $gcd;
            $den /= $gcd;
            // If you prefer integers when possible, you could:
            // if ($den == 1) return $matches[1] . $num . $matches[2];
            // Always return a fraction num/den
            return $matches[1] . $num . '/' . $den . $matches[2];
        	},
        $text);
	}

function number_of_lines_in_file($filename) {
    $lineCount = 0;
    $handle = @fopen($filename,"r");
    if($handle) {
        while(($line = fgets($handle)) !== false) {
            // Trim removes spaces, tabs, and newlines
            if (trim($line) !== '') {
                $lineCount++;
				}
			}
        fclose($handle);
    	}
	else return 0;
    return $lineCount;
	}

function is_performance_control($text) {
	$before = preg_replace('/\(.*$/', '', $text);
	$it_is = FALSE;
	switch($before) {
		case "_articulcont":
		case "_articulfixed":
		case "_articulstep":
		case "_capture":
		case "_chan":
		case "_cont":
		case "_fixed":
		case "_ins":
		case "_keymap":
		case "_keyxpand":
		case "_legato":
		case "_mod":
		case "_modcont":
		case "_modfixed":
		case "_modrate":
		case "_modstep":
		case "_ordseq":
		case "_pan":
		case "_pancont":
		case "_pancontrol":
		case "_panfixed":
		case "_panrate":
		case "_panstep":
		case "_part":
		case "_pitchbend":
		case "_pitchcont":
		case "_pitchfixed":
		case "_pitchrange":
		case "_pitchrate":
		case "_pitchstep":
		case "_press":
		case "_prescont":
		case "_pressfixed":
		case "_pressrate":
		case "_presstep":
		case "_retro":
		case "_rndseq":
		case "_rndtime":
		case "_rndvel":
		case "_rotate":
		case "_scale":
		case "_script":
		case "_srand":
		case "_staccato":
		case "_step":
		case "_switchoff":
		case "_switchon":
		case "_tempo":
		case "_transpose":
		case "_transposecont":
		case "_transposefixed":
		case "_transposestep":
		case "_value":
		case "_vel":
		case "_velcont":
		case "_velfixed":
		case "_velstep":
		case "_volume":
		case "_volumecont":
		case "_volumefixed":
		case "_volumerate":
		case "_volumestep":
			$it_is = TRUE;
		break;
		}
	return $it_is;
	}

// note 8
function cleanup_score($data) {
	$data = preg_replace("/\s+/u"," ",$data);
	$data = str_replace(",}","}",$data);
	$data = str_replace(" }","}",$data);
	$data = str_replace("} ","}",$data);
	$data = str_replace(" {","{",$data);
	$data = str_replace("{ ","{",$data);
	$data = str_replace(", ",",",$data);
	$data = str_replace(" ,",",",$data);
	do $data = str_replace("{}",'',$data,$count);
	while($count > 0);
	$data = preg_replace("/{({[^{^}]*})}/u","$1",$data); // Simplify {{xxxx}} --> {xxxx}
	$data = preg_replace("/{([\-\s]+)}/u","$1",$data); // Simplify {---} --> ---
	$data = str_replace(" ,",",",$data);
//	$data = preg_replace("/{0\/?[0-9]*}/u",'',$data); // Empty measure created by repetition
	$data = str_replace("- -","--",$data); // Simplify - - --> --
	$data = preg_replace("/,[\-\s]+,/u",",",$data); // Suppress fields containing only rests
	$data = preg_replace("/,[\-\s]+}/u","}",$data); // Suppress fields containing only rests
	$data = preg_replace("/{([\-\s]+)}/u","$1",$data); // Simplify {---} --> ---
	$data = preg_replace("/{([0-9]+\/?[0-9]*)}/u"," $1 ",$data); // Simplify {2/3} --> 2/3
	$data = preg_replace("/,\s*([0-9]+\/?[0-9]*)\s*,/u",",",$data); // Suppress fields containing only rests
	$data = preg_replace("/,\s*([0-9]+\/?[0-9]*)\s*}/u","}",$data); // Suppress fields containing only rests
	$data = preg_replace("/{([0-9]+\/?[0-9]*),\-+}/u"," $1 ",$data); // Replace for instance "{33/8,--}" with " 33/8 " Added by BB 2021-02-23
	$data = preg_replace("/{0\/?[0-9]*[^}]*}/u",'',$data); // Empty measure created by repetition
	$data = preg_replace("/{_tempo[^\)]+\)\s*_volume[^\)]+\)\s*_chan[^\)]+\)\s*}/u",'',$data); // Empty measure at the beginning of a repetition
	$data = preg_replace("/{_tempo[^\)]+\)\s*_vel[^\)]+\)\s*_chan[^\)]+\)\s*}/u",'',$data); // Empty measure at the beginning of a repetition
	$data = str_replace(" ,",",",$data);
	$data = str_replace(" }","}",$data);
	$data = preg_replace("/}\s*[1-1]\s+/u","} - ",$data); // Added by BB 2022-02-01
	$data = str_replace("-{","- {",$data);
	$data = str_replace("}-","} -",$data);
	$data = str_replace("}[","} [",$data);
	$data = str_replace("]{","] {",$data);
	return $data;
	}

// note 8
function normalize_rests($line) {
	$line = preg_replace('/([{}\.,-])/',' $1 ',$line);
	$line = str_replace(" 0 "," ",$line);
	$line = str_replace(" - "," 1 ",$line);
	$line = simplify_fractions($line);
	$line = preg_replace('/\b(\d+)\/1\b/','$1',$line); // Replace " 3/1" with " 3 "
	$line = add_list_of_integers($line);
	$line = add_list_of_ratios($line);
	$line = str_replace(" 1 "," - ",$line);
	$line = replace_spaced_dashes_with_count($line);
	$line = preg_replace("/ +/u",' ',$line);
	$line = normalize_ampersand($line); // Normally not necessary if recently imported musicxml
	$line = trim($line);
	return $line;
	}

function normalize_ampersand(string $s): string {
	// Delete unwanted '&' created when importing a MusicXML score
	$s = str_replace("{","{ ",$s);
	$s = str_replace("}"," }",$s);
	$s = str_replace(","," , ",$s);
	$s = str_replace("."," . ",$s);
    // Delete standalone &
    $s = preg_replace('/(?<=^|\s)&(?=\s|$)/', '', $s);
    // Delete leading & on integer or ratio
    $s = preg_replace('/(?<=^|\s)&(\d+(?:\/\d+)?)(?=\s|$)/', '$1', $s);
    // Delete trailing & on integer or ratio
    $s = preg_replace('/(?<=^|\s)(\d+(?:\/\d+)?)&(?=\s|$)/', '$1', $s);
	$s = str_replace("-&","-",$s);
	$s = str_replace("&-","-",$s);
	$s = str_replace("{ ","{",$s);
	$s = str_replace(" }","}",$s);
	$s = str_replace(" ,",",",$s);
	$s = str_replace(" . ",".",$s);
	$s = preg_replace("/ +/u",' ',$s);
    return $s;
	}

function adjust_spaces_in_score($line,$delete_comments) {
	if($delete_comments) $line = preg_replace('/\[.*?\]/','',$line);
	$line = preg_replace("/ +/u",' ',$line);
	$line = str_replace("{ ","{",$line);
	$line = str_replace(" }","}",$line);
	$line = str_replace("( ","(",$line);
	$line = str_replace(" )",")",$line);
	$line = str_replace(" ,",",",$line);
	$line = str_replace(", ",",",$line);
//	$line = preg_replace('/\(([^,]+)\,\s+([^)]+?)\)/u', '($1,$2)', $line); // Remove spaces in expressions like _switchon(64, 1)
	$line = str_replace(" .",".",$line);
	$line = str_replace(". ",".",$line);
	return $line;
	}

function getLatestTaggedVersion() {
    $url = 'https://api.github.com/repos/bolprocessor/bolprocessor/releases/latest';
    if(function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'PHP', // REQUIRED by GitHub
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        }
    else { // 2026-11-22 The PHP of the standalone application (PHP Desktop) has no curl extension - by Claude
        $context = stream_context_create(array('http' => array('timeout' => 10,'header' => "User-Agent: PHP\r\n")));
        $response = @file_get_contents($url,FALSE,$context);
        }
    if ($response === false) return false;
    $data = json_decode($response, true);
    return $data['tag_name'] ?? false;
	}

?>
