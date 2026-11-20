<?php
require_once("_basic_tasks.php");
$url_this_page = "produce.php";
$this_title = urldecode($_GET['title'] ?? '');

$learn = $anal = FALSE;
$failed_parsing = $failed_template = $success_parsing = 0;
$trace_link = $output = $dir = '';
$trace_production = $show_production = FALSE;
$file_format = '';
$trace_csound = '';
$csound_report = array(); // Errors reported by Csound, see csound_errors_report() (2026-11-16)
$csound_orchestra_used = '';

echo "<head>";
echo midi_player_script('');
echo "<script src=\"darkmode.js\"></script>";
echo "<link rel=\"stylesheet\" href=\"bp-light.css\" />\n";
echo "</head>";

echo "<body>\n";
set_time_limit(0);
$time_begin = time();
if(windows_system() AND isset($_GET['keepalive'])) {
	echo " "; // Send a space to keep connection alive
	flush();
	}

$application_path = $bp_application_path;
if(!file_exists($bp_application_path.$console)) {
	echo "<p style=\"text-align:center; width:90%;\">The console application (file “bp”) is not working, or missing, or misplaced…</p>";
	if(file_exists($application_path."source")) {
		echo "<p style=\"text-align:center; width:90%;\">The source files of BP3 have been found. You can (re)compile the console.<br />";
		if(!check_gcc())
			if(windows_system()) echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding.<br />You first need to <a class=\"linkdotted\" target=\"_blank\" href=\"https://bolprocessor.org/install-mingw/\">install and set up MinGW</a>.";
			else echo "👉&nbsp;&nbsp;However, ‘gcc’ is not responding. You will get it with <a class=\"linkdotted\" target=\"_blanl\" href=\"https://apps.apple.com/us/app/xcode/id497799835\">Xcode</a>.";
		else echo "<br /><br /><big>👉&nbsp;&nbsp;<a href=\"".$application_path."php/compile.php?return=produce.php&keepalive=1\">Run the compiler</a></big>";
		echo "</p>";
		}
	else
		echo "<p style=\"text-align:center; width:90%;\">Source files (the “source” folder) have not been found.<br />Visit <a target=\"_blank\" class=\"linkdotted\"  href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/install/</a> and follow instructions!</p>";
	die();
	}
$bad_image = FALSE;

if(isset($_POST['ignore'])) {
	if(isset($_POST['running_trace']) AND file_exists($_POST['running_trace'])) {
		@unlink($_POST['running_trace']);
		echo "<p>Now you can run this project.</p>";
		}
	echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;<a href=\"\" onclick=\"window.close();\">Close this page</a></big></p><p>You can also click PANIC to stop concurrent processes…</p>";
	echo "</div>";
	die();
	}

$startup = $_GET['startup'] ?? '';
$instruction = $_GET['instruction'] ?? '';
if($instruction == '') die_with_close_link("ERROR: No instruction has been sent");

$here = isset($_GET['here']) ? urldecode($_GET['here']) : '???';
$csound_file = $_GET['csound_file'] ?? '';
$score_file = $_GET['score'] ?? '';
$midi_file = $_GET['midifile'] ?? '';
$eventlist_file = $_GET['eventlistfile'] ?? '';
$tonality_file = $_GET['tonality_file'] ?? '';
$item = $_GET['item'] ?? 0;

$trace_notes_file = $temp_dir."trace_notes_txt";
@unlink($trace_notes_file);
$check_command_line = FALSE;
$result_file = $tracefile = '';
$project_fullname = '';
if($instruction == "help") {
	$command = $application_path.$console." --help";
	}
else {
	$grammar_path = urldecode($_GET['grammar'] ?? '');
	if(isset($_GET['grammar'])) $project_fullname = $grammar_path;
	if(isset($_GET['startstring'])) {
		$data_path = urldecode($_GET['startstring']);
		$instruction = "produce";
		}
	else {
		$data_path = urldecode($_GET['data'] ?? '');
		if(isset($_GET['data'])) $project_fullname = $data_path;
		}
	if($instruction <> "enter_notes" AND $grammar_path == '' AND $data_path == '')
		die_with_close_link("Link to data and/or grammar is missing");
	if($instruction == "create_grammar") {
		echo create_grammar($data_path);
		die();
		}
	$settings_path = urldecode($_GET['settings'] ?? '');
	$objects_path = $_GET['objects'] ?? '';
	$weights_path = $_GET['weights'] ?? '';
	$note_convention = $_GET['note_convention'] ?? '';
	$alphabet_path = urldecode($_GET['alphabet'] ?? '');
	if($instruction == "enter_notes") $file_format = "rtmidi";
	else $file_format = $_GET['format'] ?? '';
	$mode = $_GET['mode'] ?? '';
	$output = urldecode($_GET['output'] ?? '');
	if($instruction == "analyze" AND $grammar_path <> '') {
		$learn = ($mode == "LEARN");
		$anal = ($mode == "ANAL");
		}
	$show_production = isset($_GET['show_production']);
	$trace_production = isset($_GET['trace_production']);
	$check_command_line = isset($_GET['test']);

	$training_set_folder = '';
	if($instruction == "create_set") {
		$training_set_folder = $midi_file;
		if(!file_exists($training_set_folder)) mkdir($training_set_folder,0777,true);
		copy($data_path,$training_set_folder.SLASH."all_units.txt");
		}

	if(in_array($instruction,["produce","produce-all","play","play-all","create_set","expand"])) {
		echo "<script>";
		echo "var sometext = \"<span id=warning><i>Process might take more than ".$max_sleep_time_after_bp_command." seconds.<br />To reduce computation time, you can increase </i>‘<b>Quantization</b>’<i> in the settings</span>\";";
		echo "document.body.innerHTML = sometext";
		echo "</script>";
		}
	$grammar_name = $data_name = '';
	if($grammar_path <> '') {
		$table = explode(SLASH,$grammar_path);
		$grammar_name = end($table);
		$dir = str_replace($grammar_name,'',$grammar_path);
		}
	if($data_path <> '') {
		$table = explode(SLASH,$data_path);
		$data_name = end($table);
		$dir = str_replace($data_name,'',$here);
		}

	$csound_orchestra = $_GET['csound_orchestra'] ?? '';
	if($csound_orchestra <> '' AND file_exists($dir.$csound_orchestra)) {
		move_to_csound_resources($dir,$csound_orchestra);
		sleep(1);
		}
	// 2026-11-18 Without a '-cs' file, Csound output uses the default orchestra "0-default.orc", whose instruments
	// are described by CSOUND_DEFAULT_RESOURCE: it supplies the parameters of the piano and flute (velocity, modulation, panoramic)
	if($csound_file == '' AND ($file_format == "csound" OR $file_format == "csoundrt")
			AND ($csound_orchestra == '' OR $csound_orchestra == "0-default.orc")
			AND file_exists(csound_resource_path(CSOUND_DEFAULT_RESOURCE)))
		$csound_file = CSOUND_DEFAULT_RESOURCE;

	$grammar_name = str_replace(" ","_",$grammar_name);
	$data_name = str_replace(" ","_",$data_name);
	$project_name = preg_replace("/\.[a-z]+$/u",'',$output);
	if(in_array($instruction,["play","play-all","analyze","create_set","templates"]))
		$result_file = $project_name."_".$instruction."-result.html";
	else $result_file = $project_name."-result.html";
	$result_file = str_replace(SLASH,'/',$result_file);

	// Reduce the project path to the name of the project, e.g. "-gr.Mozart"
	if($instruction == "create_set" OR $instruction == "enter_notes") $project_fullname = $this_title;
	$project_fullname = str_replace($temp_dir,'',$project_fullname);
	$project_fullname = str_replace(SLASH,'/',$project_fullname);
	$project_fullname = preg_replace("/\/[0-9]+(-chunk)?\.bpda/u",'',$project_fullname);
	$project_fullname = str_replace("_".my_session_id()."_temp",'',$project_fullname);
	$table = explode('/',$project_fullname);
	$project_fullname = end($table);

	$tracefile = $trace_link = str_replace(SLASH,'/',$temp_dir."trace_".my_session_id()."_".$project_fullname.".txt");
	$midiport = str_replace(".txt","_midiport",$tracefile);
	if($file_format == "rtmidi" AND !file_exists($midiport)) {
		$saved_midiport = $dir_midi_resources.$project_fullname."_midiport";
		if(!file_exists($saved_midiport))
			die_with_close_link("<p style=\"text-align:center;\"><big>You first need to SAVE MIDI ports</big></p>");
		copy($saved_midiport,$midiport);
		}
	// Real-time Csound uses the MIDI ports for interaction if they have been saved (2026-11-17)
	$csoundrt_midi = FALSE;
	if($file_format == "csoundrt") {
		$saved_midiport = $dir_midi_resources.$project_fullname."_midiport";
		if(!file_exists($midiport) AND file_exists($saved_midiport)) copy($saved_midiport,$midiport);
		$csoundrt_midi = file_exists($midiport);
		}
	@unlink($project_name.".mid");
	@unlink($project_name.".html");
	foreach(["_saved_grammar","_saved_alphabet","_saved_settings","_sync_grammar"] as $saved_file)
		@unlink($temp_dir.$tracelive_folder.SLASH.$saved_file);
	if($output <> '') @unlink($output);

	$command = $application_path.$console." ".$instruction;
	$input_files = ["-se" => $settings_path, "-da" => $data_path, "-gr" => $grammar_path, "-al" => $alphabet_path, "-wg" => $weights_path, "-so" => $objects_path];
	foreach($input_files as $option => $path)
		if($path <> '') $command .= " ".$option." ".quote_if_spaces($path);
	if($note_convention <> '') $command .= " --".$note_convention;
	if($csound_file <> '') $command .= " -cs ".csound_resource_path($csound_file);
	if($tonality_file <> '') $command .= " -to ".tonality_resource_path($tonality_file);
	if($startup <> '') $command .= " --start ".$startup;
	$plays_or_produces = in_array($instruction,["produce","produce-all","play","play-all","expand","create_set","enter_notes"]);
	if($plays_or_produces) {
		switch($file_format) {
			case "data": // "-o" is added below
				break;
			case "midi":
				$command .= " --midiout ".$midi_file;
				break;
			case "csound":
				$command .= " --csoundout ".$score_file;
				break;
			case "csoundrt": // Real-time Csound, see _csoundrt.php
				$command .= " --csoundrt ".CSOUND_RT_PORT;
				if($csoundrt_midi) {
					$command .= " --rtmidi"; // MIDI ports for interaction, e.g. _script(wait for C3 channel 1)
					$command .= " --csoundrt-delay ".csoundrt_midi_delay(csoundrt_buffers()); // Silent MIDI notes come with the sound
					}
				break;
			case "eventlist":
				$command .= " --eventlistout ".$eventlist_file;
				break;
			default:
				$command .= " --rtmidi"; // We use the default destination
				break;
			}
		}
	if($instruction == "produce-all" OR $instruction == "templates" OR ($plays_or_produces AND $file_format == "data"))
		$command .= " -o ".quote_if_spaces($output);
	$command .= " --traceout ".$tracefile;
	if($show_production) $command .= " --show-production";
	if($trace_production) $command .= " --trace-production";
	if(isset($_GET['random_seed'])) $command .= " --seed ".$_GET['random_seed'];
	}

$trace_prefix = $temp_dir_abs."trace_".my_session_id()."_".$project_fullname;
// Example: trace_dnh20dhdkjj9nd1ehr9kj360ec_-gr.Mozart.txt
$stopfile = str_replace(SLASH,'/',$trace_prefix."_stop");
$donefile = $trace_prefix."_done"; // Created by the console to tell its job is over
$pausefile = str_replace(SLASH,'/',$pausefile);
$continuefile = str_replace(SLASH,'/',$continuefile);
@unlink($pausefile); @unlink($continuefile);

if($instruction <> "help") {
	// Check that the same project is not already running in the same sesssion
	$running_trace = $trace_prefix.".txt";
	if(!windows_system() AND file_exists($running_trace)) {
		echo "<form  id=\"topchanges\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"color:red;\">Project “".$project_fullname."” is already running in the same session.</p>";
		echo "<input type=\"hidden\" name=\"running_trace\" value=\"".$running_trace."\">";
		echo "<input style=\"color:DarkBlue; background-color:yellow;\" onclick=\"window.close();\" type=\"submit\" value=\"WAIT UNTIL IT IS OVER\">";
		echo "&nbsp;&nbsp;<input class=\"produce\" type=\"submit\" name=\"ignore\" value=\"IGNORE\">";
		echo "</form>";
		die();
		}
	// Delete old images, to be replaced with the current ones, and make sure deletion is complete before launching the command
	foreach(scandir($temp_dir_abs) as $thisfile) {
		if(trace_image_suffix($thisfile,$project_fullname) === FALSE) continue;
		if(@unlink($temp_dir_abs.$thisfile)) wait_while_exists($temp_dir_abs.$thisfile,5);
		}
	echo "<script>
	window.addEventListener('pagehide', function () {
		navigator.sendBeacon(
			'_createfile.php?path_to_file=' +
			encodeURIComponent(".json_encode($stopfile).")
			);
		navigator.sendBeacon(
			'_deletefile.php?path_to_file=' +
			encodeURIComponent(".json_encode($trace_notes_file).")
			);
		navigator.sendBeacon(
			'_deletefile.php?path_to_file=' +
			encodeURIComponent(".json_encode($running_trace).")
			);
		});
	</script>";
	}

if($check_command_line) {
	echo "<p><i>Run this command in the “php” folder:</i></p>";
	echo "<p><span class=\"red-text\">➡</span> ".$command."</p>";
	die();
	}
// send_to_console() converts the command for Windows, so it is only converted here for display
if(windows_system()) $command_show = str_replace('^','',windows_command($command));
else $command_show = $command;
$command_show = str_replace(["[","]"],["\[","\]"],$command_show);
echo "<p><small><b><span class=\"red-text\">BP3 (for geeks) ➡</span></b> ".$command_show."</small></p>\n";

if($instruction <> "help") {
	@unlink($tracefile);
	if(file_exists($dir_midi_resources."midiport_".$project_fullname))
		// We need to use midisetup of this project
		copy($dir_midi_resources."midiport_".$project_fullname, $dir_midi_resources."last_midiport");
	if($csound_file <> '' AND !wait_while_exists(csound_resource_path($csound_file)."_lock",5))
		echo "<p id=\"cswait\"><span class=\"red-text\">Maximum time (5 seconds) spent waiting for the Csound resource file to be unlocked:</span> <span class=\"green-text\">".csound_resource_path($csound_file)."</span></p>";
	if($objects_path <> '' AND !wait_while_exists($objects_path."_lock",5))
		echo "<p id=\"miwait\"><span class=\"red-text\">Maximum time (5 seconds) spent waiting for the sound-object prototypes file to be unlocked:</span> <span class=\"green-text\">".$objects_path."</span></p>";

	echo "<script>
	function setButtonState(button, disabled) {
		button.disabled = disabled;
		if(disabled) {
			button.classList.add('disabled');
			}
		else {
			button.classList.remove('disabled');
			}
		}
	function stopProcess() {
		createFile(".json_encode($stopfile).");
		setButtonState(document.getElementById('stopBtn'), true);
		}
	function pauseProcess() {
		createFile(".json_encode($pausefile).");
		setButtonState(document.getElementById('pauseBtn'), true);
		setButtonState(document.getElementById('continueBtn'), false);
		}
	function continueProcess() {
		createFile(".json_encode($continuefile).");
		setButtonState(document.getElementById('pauseBtn'), false);
		setButtonState(document.getElementById('continueBtn'), true);
		}
	</script>";

	echo "<p id=\"wait\" style=\"text-align:center; background-color:yellow; color:black;\"><br /><big><b><span class=\"blinking\" style=\"animation-duration:2.4s;\">… Bol Processor console is working …</span></b></big><br />(Don't close this window!)<br /><br />";

	if($instruction == "templates") echo "👉 Creating/updating templates<br /><br />";
	if($instruction == "enter_notes") echo "👉 Entering notes from the MIDI input device.<br /><br />Notes are displayed at the insertion point of your grammar or data.<br />The note convention is chosen in the settings file.<br /><br />👉 You can close this window when done.<br /><br />If it doesn't work, click STOP, then SHOW process, and check connected devices<br />&nbsp;&nbsp;to update the MIDI input.<br /><br />Read <a target=\"_blank\" href=\"https://bolprocessor.org/enter-notes/\">https://bolprocessor.org/enter-notes/</a> for details.<br /><br />";
	echo "<button id=\"stopBtn\" type=\"button\" class=\"produce\" onclick=\"stopProcess();\">Click to STOP</button>";
	if(($file_format == "rtmidi" OR $file_format == "csoundrt") AND $instruction <> "enter_notes") {
		echo "&nbsp;<button id=\"pauseBtn\" type=\"button\" class=\"produce\" onclick=\"pauseProcess();\">Pause</button>";
		echo "&nbsp;<button id=\"continueBtn\" type=\"button\" class=\"produce disabled\" disabled onclick=\"continueProcess();\">Continue</button>";
		// Display the date of the performance, written by the console to the "_time" file every 200 ms
		// The console removes from the trace path the characters not accepted by remove_spaces()
		$timefile = preg_replace('/[^A-Za-z0-9():_.,-]/','',"trace_".my_session_id()."_".$project_fullname."_time");
		@unlink($temp_dir.$timefile);
		echo "<br /><br /><big><b>⏱ <span id=\"perfTime\" style=\"font-family:monospace;\">0:00.0</span><span id=\"perfEnd\" style=\"font-family:monospace;\"></span></b></big>";
		echo "<script>
		(function() {
			const url = 'time_poll.php?file=' + encodeURIComponent(".json_encode($timefile).");
			const display = document.getElementById('perfTime');
			const displayEnd = document.getElementById('perfEnd');
			function format(ms) {
				const tenths = Math.floor(ms / 100);
				const minutes = Math.floor(tenths / 600);
				const seconds = Math.floor((tenths % 600) / 10);
				return minutes + ':' + (seconds < 10 ? '0' : '') + seconds + '.' + (tenths % 10);
				}
			async function pollTime() {
				const box = document.getElementById('wait');
				if(box && box.style.display == 'none') return; // The console has finished
				try {
					const response = await fetch(url, {cache: 'no-store', credentials: 'same-origin'});
					if(response.ok) {
						const result = await response.json();
						if(typeof result.ms === 'number') display.textContent = format(result.ms);
						// Expected end of the performance (of the current item if there are several)
						if(typeof result.end === 'number' && result.end > 0) displayEnd.textContent = ' / ' + format(result.end);
						}
					}
				catch(error) {}
				window.setTimeout(pollTime, 200);
				}
			pollTime();
			})();
		</script>";
		}
	echo "<br /><br /></p>\n";
	}
echo str_repeat(' ', 10240);  // send extra spaces to fill browser buffer, useful for standalone application
if(ob_get_level() > 0) ob_flush();
flush();

// Display the data being played
if(isset($data_path) AND $data_path <> '' AND ($content = @file_get_contents($data_path)) <> FALSE) {
	if($instruction == "play" OR $instruction == "produce") echo "<p><b>Playing";
	if($instruction == "play-all") echo "<p><b>Playing chunks";
	if($instruction == "expand") echo "<p><b>Expanding";
	if($instruction == "analyze" AND $learn) echo "<p><b>Analysing and learning weights from all items in <span class=\"green-text\">‘".$data_name."’</span>";
	else if($instruction == "analyze" AND $anal) echo "<p><b>Analysing all items in <span class=\"green-text\">‘".$data_name."’</span>";
	else if($instruction == "analyze") echo "<p><b>Analysing";
	if($instruction == "create_set") echo "<p><b>Creating AI training set</b>";
	else if(!$learn AND !$anal) {
		echo ($item <> 0) ? " #".$item : ":";
		echo "</b></p>";
		echo "<p style=\"overflow-wrap:break-word;\"><b>";
		$k = 0;
		foreach(explode(chr(10),$content) as $line) {
			if($k > 800) {
				echo "… … …<br />";
				break;
				}
			$line = recode_tags(trim($line));
			if($line == '') continue;
			if(strlen($line) > 200) $line = substr($line,0,50)."<br />&nbsp;&nbsp;... ... ...<br />".substr($line,-100,100);
			if($line[0] == '[') $line = "<span class=\"turquoise-text\">".$line;
			$line = preg_replace("/\] /","]</span><br /><br />",$line,1);
			echo "<span class=\"green-text\">".$line."</span><br />";
			$k += strlen($line);
			}
		echo "</b></p>";
		}
	}
@unlink($stopfile);
@unlink($panicfile); @unlink($pausefile); @unlink($continuefile);
session_abort();
while(file_exists($stopfile)) { // Probably useless because the file will be deleted when starting the console, see remove(StopfileName) in ConsoleMain.c
	usleep(200000); // 0.2 sec
	}
// Real-time Csound: start the Csound server, or check that it is running with the orchestra of this project (2026-11-16)
if($file_format == "csoundrt" AND $instruction <> "help") {
	$this_orchestra = ($csound_orchestra <> '') ? $csound_orchestra : "0-default.orc";
	$csoundrt_message = '';
	if(!csound_ready())
		$csoundrt_message = "Csound ".CSOUND_MIN_VERSION." or later was not found. Reload the project page to check the installation of Csound.";
	else if(!file_exists(csound_resource_path($this_orchestra)))
		$csoundrt_message = "The orchestra file was not found: ".csound_resource_path($this_orchestra);
	else {
		// Linux: the library path is set for Csound, then restored for the console
		$saved_library_path = getenv("LD_LIBRARY_PATH");
		if(!windows_system()) csound_environment($csound_path);
		$csoundrt_ok = csoundrt_start($csound_path.SLASH.$csound_name,csound_resource_path($this_orchestra),$csoundrt_status);
		if(!windows_system()) {
			if($saved_library_path === FALSE) putenv("LD_LIBRARY_PATH");
			else putenv("LD_LIBRARY_PATH=".$saved_library_path);
			}
		if($csoundrt_ok) {
			$csound_orchestra_used = $this_orchestra;
			$csoundrt_log_offset = csoundrt_log_size(); // Csound's messages during this performance will be checked
			if($csoundrt_status <> "running") echo "<p><small><b><span class=\"red-text\">Csound ➡</span></b> The real-time Csound server has been ".$csoundrt_status." with orchestra <span class=\"green-text\">".$this_orchestra."</span> on UDP port ".CSOUND_RT_PORT."</small></p>\n";
			csoundrt_begin_performance();
			}
		else {
			$csoundrt_message = $csoundrt_status;
			$tail = csoundrt_log_tail();
			if($tail <> '') $csoundrt_message .= "<br />Csound said:<br /><small><span class=\"green-text\">".nl2br(htmlspecialchars($tail))."</span></small>";
			}
		}
	if($csoundrt_message <> '') {
		echo "<script>[\"warning\",\"wait\"].forEach(function(id) { var el = document.getElementById(id); if(el) el.style.display = \"none\"; });</script>";
		echo "<p><span class=\"red-text\">➡ Real-time Csound could not start:</span> ".$csoundrt_message."</p>";
		die();
		}
	if(ob_get_level() > 0) ob_flush();
	flush();
	}
// $command = "ASAN_OPTIONS=detect_leaks=0 ".$command; // This is for debugging
$o = send_to_console($command);
if($file_format == "csoundrt") {
	csoundrt_end_performance(); // The server will quit if it is not used for a while
	usleep(300000); // Let Csound write its last messages
	$csound_report = csound_errors_report(csound_errors_summary(csoundrt_log_since($csoundrt_log_offset)),$csound_file,$csound_orchestra_used);
	}
echo "<hr style=\"border: 1px solid black;\">";
session_reset();

echo "<link rel=\"stylesheet\" href=\"bp.css?v=".time()."\" />\n";
// The "v=" forces this stylesheet to replace the previous one
echo "<link rel=\"stylesheet\" href=\"skin".$skin.".css\" />\n";

$n_messages = count($o);
$no_error = FALSE;
foreach($o as $mssg) {
	if(str_starts_with($mssg,"Errors: 0")) {
		$no_error = TRUE;
		break;
		}
	}

if($instruction == "help") {
	foreach($o as $mssg) echo clean_up_encoding(FALSE,TRUE,$mssg)."<br />";
	die();
	}

$time_end = time() + $max_sleep_time_after_bp_command;
while(!file_exists($donefile)) {
	if(time() > $time_end) {
		echo "<p><span class=\"red-text\">Maximum time (".$max_sleep_time_after_bp_command." seconds) spent waiting for the 'done.txt' file… The process is incomplete!</span></p>";
		break;
		}
	sleep(1);
	}
sleep(1);
@unlink($donefile);
$content_trace = '';
if(file_exists($tracefile)) {
	$content_trace = file_get_contents($tracefile,TRUE);
	clean_up_file_to_html($tracefile); // Creates the HTML file of $trace_link
	}
$trace_link = str_replace(".txt",".html",$trace_link);
$output = str_replace(SLASH,'/',$output);

echo '<script>
["warning","wait","miwait","cswait"].forEach(function(id) {
  var el = document.getElementById(id);
  if (el) el.style.display = "none";
});
</script>';

if(!$no_error AND strlen($content_trace) > 4) {
	echo "<p class=\"attention\"><big><span class=\"red-text\"><b>Errors found… </b></span> ";
	echo "Check the <a class=\"linkdotted\" onclick=\"window.open('".nice_url($trace_link)."','errors','width=800,height=500,left=400'); return false;\" href=\"".$trace_link."\">error trace</a> file!</big></p>";
	}
echo "<p>";

if($output <> '') {
	if($file_format == "csound") $output_link = $download_link = nice_url($score_file);
	else if($file_format == "midi") $output_link = $download_link = '';
	else if($file_format == "eventlist") {
		$output_link = "view_csv.php?file=".urlencode($eventlist_file);
		$download_link = $eventlist_file;
		$output_dir = dirname($eventlist_file);
		}
	else $output_link = $download_link = str_replace(SLASH,'/',clean_up_file_to_html($output));
	if($output_link <> '') echo "<span class=\"red-text\">➡</span> Read the <a class=\"linkdotted\" onclick=\"window.open('".$output_link."','".rand(10000,99999)."','width=800,height=400,left=300'); return false;\" href=\"".$output_link."\">output file</a> (or <a class=\"linkdotted\" href=\"".$download_link."\" download>download it</a>)<br />";
	if($file_format == "eventlist") {
		if($objects_path <> '') {
			$objects_json = "proto-".basename(str_replace("-so.",'',$objects_path)).".json";
			echo "<span class=\"red-text\">➡</span> Download the <a class=\"linkdotted\" href=\"".$output_dir."/".$objects_json."\" download>".$objects_json."</a> sound-object protoypes file<br />";
			}
		foreach(files_created_since($output_dir.SLASH."instr-*.json",$time_begin) as $ins_file) {
			$ins_name = basename($ins_file);
			echo "<span class=\"red-text\">➡</span> Download the <a class=\"linkdotted\" href=\"".$output_dir."/".$ins_name."\" download>".$ins_name."</a> Csound instrument file<br />";
			}
		foreach(files_created_since($output_dir.SLASH."*.scl",$time_begin) as $scl_file) {
			$scl_name = basename($scl_file);
			$kbm_name = str_replace(".scl",".kbm",$scl_name);
			echo "<span class=\"red-text\">➡</span> Download the <a class=\"linkdotted\" href=\"".$output_dir."/".$scl_name."\" download>".$scl_name."</a> and <a class=\"linkdotted\" href=\"".$output_dir."/".$kbm_name."\" download>".$kbm_name."</a> scale files<br />";
			}
		}
	}
if(file_exists($trace_link) AND strlen($content_trace) > 20)
	echo "<span class=\"red-text\">➡</span> Read the <a class=\"linkdotted\" onclick=\"window.open('".nice_url($trace_link)."','trace','width=800,height=600,left=400'); return false;\" href=\"".$trace_link."\">trace file</a> (or <a class=\"linkdotted\" href=\"".nice_url($trace_link)."\" download>download it</a>)";
echo "</p>";

if($file_format == "midi" AND $instruction <> "create_set" AND file_exists($midi_file) AND filesize($midi_file) > 59) {
	if($midi_player <> "SpessaSynth" AND !is_connected()) {
		echo "<p style=\"color:red;\">➡ Cannot find the MIDI file player “cdn.jsdelivr.net”… Are you connected to the Internet?</p>";
		echo "<p><a href=\"".nice_url($midi_file)."\" download>Download the MIDI file</a></p>";
		}
	else echo midifile_player(nice_url($midi_file),'',25,0);
	}

if($instruction == "create_set" AND $training_set_folder <> '') {
	check_training_folder($training_set_folder);
	$number_zip = zip_this_folder($training_set_folder);
	if($number_zip > 0)
		echo "<hr><p><big>👉 Download <a href=\"".$training_set_folder.".zip\">zipped AI training set</a> (".(($number_zip - 1) / 4)." samples)</big></p><hr>";
	}

// Display links to images if any
if($instruction <> "templates") {
	echo "<table style=\"background-color:snow; color:black; padding:0px;\"><tr>";
	$images_per_row = 6;
	$column = 0;
	foreach(scandir($temp_dir_abs) as $thisfile) {
		$suffix = trace_image_suffix($thisfile,$project_fullname);
		if($suffix === FALSE) continue;
		echo "<td style=\"border-radius: 6px; border: 4px solid Gold; background-color:azure; color:black;vertical-align:middle; text-align: center; padding:8px; margin:0px;\">";
		// $suffix is "<number>-<time>.html" or "<number>.html"
		$table_number = explode('-',str_replace(".html",'',$suffix));
		$number = $table_number[0];
		$image_time = isset($table_number[1]) ? $table_number[1]."s" : '';
		$WidthMax = $HeightMax = 0;
		foreach(explode(chr(10),(string)@file_get_contents($temp_dir_abs.$thisfile)) as $line) {
			if(str_contains($line,"canvas.width")) $WidthMax = intval(explode("=",$line)[2]) + 10;
			if(str_contains($line,"canvas.height")) $HeightMax = intval(explode("=",$line)[2]);
			}
		$link = $temp_dir.$thisfile;
		$left = 10 + (100 * $column); // Windows of successive images are shifted to the right
		$window_height = ($HeightMax < 600) ? $HeightMax + 60 : 600;
		$window_width = ($WidthMax < 1200) ? $WidthMax + 20 : 1200;
		echo "<a class=\"darkblue-text\" onclick=\"window.open('".nice_url($link)."','".rand(10000,99999)."','width=".$window_width.",height=".$window_height.",left=".$left."'); return false;\" href=\"".nice_url($link)."\">Image ".$number."</a><br />".$image_time;
		if(check_image($link) <> '') {
			$bad_image = TRUE;
			echo " <span class=\"red-text\"><b>*</b></span>";
			}
		echo "&nbsp;";
		echo "</td>";
		if(++$column == $images_per_row) {
			$column = 0;
			echo "</tr><tr>";
			}
		}
	echo "</tr></table>";
	echo "<br />";
	}

// Process Csound score if possible
if($no_error AND $file_format == "csound") {
	if($csound_orchestra == '') {
		$csound_orchestra = "0-default.orc";
		echo "<p><span class=\"red-text\">➡</span> Csound orchestra file was not specified. I tried the default orchestra: <span class=\"green-text\">".csound_resource_path($csound_orchestra)."</span>.</p>";
		}
	if(!file_exists(csound_resource_path($csound_orchestra))) {
		echo "<p><span class=\"red-text\">➡</span> No orchestra file has been found here: <span class=\"green-text\">".csound_resource_path($csound_orchestra)."</span>. Csound will not create a sound file.</p>";
		}
	else {
		// We change the name of the sound file every time to force the browser to refresh the audio tag
		$sound_file_link = str_replace(".sco",'',$score_file)."@".rand(10000,99999).".wav";
		$table = explode(SLASH,$score_file);
		$csound_file_name = end($table);
		$project_name = str_replace(".sco",'',$csound_file_name);
		$dir = str_replace($csound_file_name,'',$score_file);
		// Delete the sound files of previous runs
		foreach(scandir($dir) as $thisfile) {
			$table = explode('.',$thisfile);
			if(end($table) == "wav" AND explode('@',$table[0])[0] == $project_name) @unlink($dir.$thisfile);
			}
		if(!file_exists($score_file))
			echo "<p><span class=\"red-text\">➡</span> The score file (".$score_file.") was not found by Csound.</p>";
		else if(!csound_ready())
			echo "<p><span class=\"red-text\">➡</span> Csound ".CSOUND_MIN_VERSION." or later was not found. Reload the project page to check the installation of Csound.</p>";
		else {
			sleep(1);
			$time_start = time();
			// Linux: remove XAMPP's /opt/lampp/lib so Csound uses the system libraries, or use those next to Csound
			if(!windows_system()) csound_environment($csound_path);
			$trace_csound = $temp_dir_abs."trace_csound.txt";
			// $csound_path is absolute on all systems (2026-11-14)
			$command = "\"".$csound_path.SLASH.$csound_name."\"".csound_sample_option()." --wave -o ".$sound_file_link." ".csound_resource_path($csound_orchestra)." ".$score_file." 2>".$trace_csound;
			$command_show = $command;
			if(windows_system()) {
				$command = windows_command($command);
				$command_show = str_replace('^','',$command);
				}
			echo "<p><small><b><span class=\"red-text\">Csound ➡</span></b> ".$command_show."</small></p>";
			exec($command,$result_csound,$return_var);
			$csound_orchestra_used = $csound_orchestra;
			$csound_report = csound_errors_report(csound_errors_summary(@file_get_contents($trace_csound)),$csound_file,$csound_orchestra);
			if($return_var <> 0) {
				echo "<p><span class=\"red-text\">➡</span> Csound returned error code <span class=\"red-text\">‘".$return_var."’</span>.<br /><i>Maybe you are trying to use instruments that do not match</i> <span class=\"green-text\">‘".$csound_orchestra."’</span></p>";
				}
			else {
				$time_spent = time() - $time_start;
				if($time_spent > 10)
					echo "<p><span class=\"red-text\">➡</span> Sorry for the long time (".$time_spent." seconds) waiting for Csound to complete the conversion…</p>";
				echo "<audio controls class=\"shadow\"><source src=\"".$sound_file_link."\" type=\"audio/wav\">Your browser does not support the audio tag.</audio>";
				echo "<p><a target=\"_blank\" href=\"".nice_url($sound_file_link)."\" download>Download this sound file</a> (<span class=\"green-text\">".nice_url($sound_file_link)."</span>)</p>";
				echo "<p><span class=\"red-text\">➡</span> If you hear garbage sound or silence it may be due to a mismatch between Csound score and orchestra<br />&nbsp;&nbsp;&nbsp;or some overflow in Csound…</p>";
				}
			}
		}
	}

$terminated = (file_exists($stopfile) OR file_exists($panicfile));
@unlink($pausefile); @unlink($continuefile);
@unlink($stopfile);
$time_stopped = '';
if(isset($timefile)) {
	// When the performance is stopped, the console keeps the "_time" file with the date at which it stopped
	// It contains the date and the expected end (milliseconds)
	$content = trim((string)@file_get_contents($temp_dir.$timefile));
	if($terminated AND preg_match('/^([0-9]+)(?:\s+(-?[0-9]+))?$/',$content,$match)) {
		$time_stopped = format_performance_time((int)$match[1]);
		if(isset($match[2]) AND (int)$match[2] > 0) $time_stopped .= " of ".format_performance_time((int)$match[2]);
		}
	@unlink($temp_dir.$timefile);
	}
if($terminated AND $instruction <> "enter_notes") {
	echo "<p class=\"attention\"><big>👉 <span style=\"color:red;\">The process has been interrupted";
	if($time_stopped <> '') echo " at</span> ⏱ ".$time_stopped;
	else echo "</span>";
	echo "</big></p>";
	}

if(file_exists($trace_prefix."_capture")) {
	echo "<p>👉 Reload the <span class=\"green-text\">".$project_fullname."</span> project to see <span class=\"red-text\">captured MIDI events</span> at the end of the data…</p>";
	}

// Write the "Show process" page
$handle = FALSE;
if($n_messages > 30000) echo "<p><span class=\"red-text\">➡</span> Too many messages produced! (".$n_messages.")</p>";
else if($result_file <> '' AND ($handle = fopen($result_file,"w"))) {
	$header = "<!DOCTYPE HTML>";
	$header .= "<html lang=\"en\">";
	$header .= "<head>\n";
	$header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />\n";
	$header .= "<link rel=\"stylesheet\" href=\"".$bp_application_path."php/bp-light.css\" />\n";
	$header .= "<title>".$result_file."</title>\n";
	$header .= midi_player_script($bp_application_path."php/")."\n";
	$header .= "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>\n";
	$header .= "<style>
		body, p {
			overflow-wrap: anywhere;
			word-break: break-word;
			}
		</style>\n";
	$header .= "</head><body>\n";
	fwrite($handle,$header."\n");
	fwrite($handle,"<h2 id=\"midi\">".$grammar_name."</h2>\n");
	fwrite($handle,"<small>Results as per ".date('Y-m-d H:i:s')."</small><br />\n");
	if(file_exists($project_name.".wav")) {
		fwrite($handle,"<p><b>Csound:</b></p><audio controls class=\"shadow\"><source src=\"".$project_name.".wav\" type=\"audio/wav\">Your browser does not support the audio tag.</audio><p>\n");
		if(file_exists($project_name.".sco"))
			fwrite($handle,"<br /><a target=\"_blank\" href=\"".$project_name.".sco\" download>Download Csound score</a> (<span class=\"green-text\">".$project_name.".sco</span>)<br />");
		fwrite($handle,"<a target=\"_blank\" href=\"".$project_name.".wav\" download>Download this sound file</a> (<span class=\"green-text\">".$project_name.".wav</span>)</p>");
		}
	if(file_exists($project_name.".mid") AND filesize($project_name.".mid") > 59)
		fwrite($handle,midifile_player(str_replace(SLASH,'/',$project_name.".mid"),'',25,0)."\n");
	if(file_exists($project_name.".html"))
		fwrite($handle,"<p><span class=\"red-text\">➡</span> Read the <a class=\"linkdotted\" onclick=\"window.open('".$project_name.".html','".$file_format."','width=800,height=700,left=300'); return false;\" href=\"".$project_name.".html\">data file</a> (or <a class=\"linkdotted\" href=\"".nice_url($project_name).".bpda\" download>download it</a>)</p>\n");
	fwrite($handle,"<p><b>Messages:</b></p>\n");
	}

// Csound: orchestra used, and errors showing that the instruments may not match the orchestra (2026-11-16)
if($csound_orchestra_used <> '') {
	$o[] = "Csound orchestra used: ".$csound_orchestra_used;
	$n_messages = count($o);
	}
if(count($csound_report) > 0) {
	// Only the first line pulses, the details are stable
	echo "<p class=\"attention\"><span class=\"red-text\">⚠️ ".$csound_report[0]."</span></p>";
	echo "<p><small>";
	for($i = 1; $i < count($csound_report); $i++) echo ($i > 1 ? "<br />" : '').$csound_report[$i];
	echo "</small></p><hr>";
	}

// Display the first and last messages, and collect the results of analysis
$weights_file_path = '';
$created_templates = FALSE;
$warnings = 0;
if($n_messages > 0) {
	$analyzing_success = $analyzing_failure = FALSE;
	for($i = 0; $i < $n_messages; $i++) {
		$mssg = trim($o[$i]);
		if($mssg == '') continue;
		$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
		$mssg = str_replace(['<','>'],["&lt;","&gt;"],$mssg);
		if(str_starts_with($mssg,"=&gt; ")) {
			$warnings++;
			$mssg = "<font color=\"red\">".str_replace("=&gt; ",'',$mssg)."</font>";
			}
		// Show URLs and paths in small blue characters
		if(preg_match("/https?:\/\//u",$mssg))
			$mssg = preg_replace("/(https?:\/\/.+)$/u","<font color=#007BFF><small>$1</small></font>",$mssg);
		else if(preg_match("/^(Csound orchestra named in this file|Csound orchestra used): /u",$mssg))
			// Orchestra names are displayed like paths (2026-11-16)
			$mssg = preg_replace("/^(Csound orchestra named in this file|Csound orchestra used): (.+)$/u","$1: <font color=#007BFF><small>$2</small></font>",$mssg);
		else
			$mssg = preg_replace(["/(C:.+)$/u","/(\.\.\/.+)$/u"],"<font color=#007BFF><small>$1</small></font>",$mssg);
		if($mssg == null OR $mssg == "(null)") continue;
		if(str_contains($mssg,"Creating new weights file:"))
			$weights_file_path = trim(str_replace("Creating new weights file:",'',$mssg));
		if(str_contains($mssg,"templates have been produced")) $created_templates = TRUE;
		if(str_contains($mssg,"Analyzing item") OR str_contains($mssg,"Analyzing new"))
			$analyzing_failure = $analyzing_success = TRUE;
		if($analyzing_failure AND str_contains($mssg,"rejected by")) {
			$failed_parsing++;
			$analyzing_failure = FALSE;
			}
		if($analyzing_success AND ($this_count = substr_count($mssg,"accepted by")) > 0) {
			$success_parsing += $this_count;
			$analyzing_success = FALSE;
			}
		$failed_template += substr_count($mssg,"matched no template");
		if($handle) fwrite($handle,$mssg."<br />\n");
		if($i == 7) echo "… … …<br />";
		if($i < 7 OR $i > ($n_messages - 4)) echo $mssg."<br />";
		}
	if($handle) {
		if($bad_image) echo "<p>(<span class=\"red-text\"><b>*</b></span>) Syntax error in image: negative argument</p>";
		echo "<p style=\"font-size:larger;\"><input class=\"save big\" onclick=\"window.open('".nice_url($result_file)."','".$grammar_name."_".rand(0,99)."_result','width=600,height=600,left=100'); return false;\" type=\"submit\" value=\"Show process (".$n_messages." lines)\">";
		if($warnings > 0) echo " <span class=\"blinking\">=> ".$warnings." warning".($warnings > 1 ? "s" : "")."</span>";
		fwrite($handle,"</body>\n");
		fclose($handle);
		chmod($result_file,$permissions);
		}
	}
else echo "<p>No message produced…";
if($trace_csound <> '' AND file_exists($trace_csound)) {
	$trace_csound_link = str_replace(SLASH,'/',$temp_dir."trace_csound.txt");
	echo "&nbsp;<input class=\"save big\" onclick=\"window.open('".nice_url($trace_csound_link)."','".$grammar_name."_".rand(0,99)."_result','width=800,height=600,left=100'); return false;\" type=\"submit\" value=\"Show Csound trace\">";
	}
echo "</p>";

if($success_parsing > 0) echo "<p><big>✅&nbsp;&nbsp;".$success_parsing." item(s) successfully parsed</big></p>";
if($failed_parsing > 0) echo "<p><big>❌&nbsp;&nbsp;".$failed_parsing." item(s) failed in the parsing after matching a template</big></p>";
if($failed_template > 0) echo "<p><big>❌&nbsp;&nbsp;".$failed_template." item(s) matched no template</big></p>";
if($weights_file_path <> '' AND $learn) echo "<p><big>👉  New weights are saved in ".$weights_file_path."</big><br />&nbsp;&nbsp;&nbsp;&nbsp;(Click the button below the grammar)</p>";
if($created_templates) echo "<p><big>👉  Templates have been created. Click the “<span class=\"green-text\">output file</span>” link!</big></p>";

if($instruction == "enter_notes" AND $warnings == 0 AND file_exists($trace_notes_file) AND filesize($trace_notes_file) > 3) {
	@unlink($trace_notes_file);
	echo "<script>window.close();</script>";
	}
$handle = fopen($stopfile,"w");
if($handle) fclose($handle);

function die_with_close_link($message) {
	echo $message;
	echo "<p style=\"text-align:center; width:90%;\"><big>👉&nbsp;&nbsp;<a href=\"\" onclick=\"window.close();\">Close this page</a></big></p>";
	echo "</div>";
	die();
	}

function quote_if_spaces($path) {
	return str_contains($path,' ') ? '"'.$path.'"' : $path;
	}

function wait_while_exists($file,$seconds) {
	// Waits until $file no longer exists, at most $seconds. Returns FALSE on timeout
	$time_end = time() + $seconds;
	while(file_exists($file)) {
		if(time() > $time_end) return FALSE;
		usleep(100000); // 0.1 sec
		}
	return TRUE;
	}

function trace_image_suffix($thisfile,$project_fullname) {
	// If $thisfile is an image of this project in this session, e.g. "trace_<session>_<project>_image_<number>-<time>.html",
	// returns the part following "image", otherwise FALSE
	$table = explode('_',$thisfile);
	if($table[0] <> "trace" OR !isset($table[2]) OR $table[1] <> my_session_id()) return FALSE;
	if(!str_contains($thisfile,$project_fullname)) return FALSE;
	for($i = 2; $i < (count($table) - 1); $i++)
		if($table[$i] == "image") return $table[$i + 1];
	return FALSE;
	}

function files_created_since($pattern,$time) {
	// Files matching $pattern modified since $time, newest first
	$files = array_filter(glob($pattern) ?: [],fn($file) => filemtime($file) >= $time);
	usort($files,fn($a,$b) => filemtime($b) <=> filemtime($a));
	return $files;
	}

function format_performance_time($ms) {
	// Same format as the clock displayed during the performance, e.g. "1:05.4"
	$tenths = intdiv($ms,100);
	return intdiv($tenths,600).":".sprintf("%02d",intdiv($tenths % 600,10)).".".($tenths % 10);
	}

function check_image($link) {
	$result = '';
	$content = @file_get_contents($link);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	if($content) {
		$content = str_replace(chr(13).chr(10),chr(10),$content);
		$content = str_replace(chr(13),chr(10),$content);
		$pos1 = $pos2 = 0;
		if((is_integer($pos1 = strrpos($content,",-")) OR is_integer($pos2 = strrpos($content,"(-"))) AND is_integer($pos3 = strpos($content,"//"))) {
			if($pos1 > $pos3 OR $pos2 > $pos3) $result = "negative argument";
			}
		}
	return $result;
	}

function check_training_folder($folder) {
	$rootPath = realpath($folder);
	foreach(scandir($folder) as $thisfile) {
		$ext = pathinfo($thisfile,PATHINFO_EXTENSION);
		if($ext == "txt" OR $ext == "tab" OR $ext == "tsv") {
			$table = explode('.',$thisfile);
			$midifile = $rootPath.SLASH.$table[0].".mid";
			if(!file_exists($midifile) AND !str_contains($thisfile,"_units.txt")) {
				echo "<span class=\"red-text\">➡</span> Deleting invalid sample ‘<span class=\"green-text\">".$thisfile."</span>’<br />";
				unlink($rootPath.SLASH.$thisfile);
				}
			}
		}
	}

?>
