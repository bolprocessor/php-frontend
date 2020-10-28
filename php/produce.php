<?php
require_once("_basic_tasks.php");
$url_this_page = "produce.php";
$this_title = "BP console";
require_once("_header.php");

$application_path = $bp_application_path;

if(isset($_GET['startup'])) $startup = $_GET['startup'];
else $startup = '';

if(isset($_GET['instruction'])) $instruction = $_GET['instruction'];
else $instruction = '';
if($instruction == '') {
	echo "ERROR: No instruction has been sent";
	die();
	}
if(isset($_GET['here'])) $here = urldecode($_GET['here']);
else $here = '???';
if(isset($_GET['csound_file'])) $csound_file = $_GET['csound_file'];
else $csound_file = '';
if($instruction == "help")
	$command = $application_path."bp --help";
else {
	if(isset($_GET['grammar'])) $grammar_path = urldecode($_GET['grammar']);
	else $grammar_path = '';
	if($grammar_path == '') die();
	if(isset($_GET['settings_file'])) $settings_file = $_GET['settings_file'];
	else $settings_file = '';
	if(isset($_GET['objects_file'])) $objects_file = $_GET['objects_file'];
	else $objects_file = '';
	if(isset($_GET['note_convention'])) $note_convention = $_GET['note_convention'];
	else $note_convention = '';
	if(isset($_GET['alphabet'])) $alphabet_file = urldecode($_GET['alphabet']);
	else $alphabet_file = '';
	if(isset($_GET['format'])) $file_format = $_GET['format'];
	else $file_format = '';
	if($file_format <> '' AND isset($_GET['output'])) $output = urldecode($_GET['output']);
	else $output = '';
	if(isset($_GET['show_production'])) $show_production = TRUE;
	else $show_production = FALSE;
	if(isset($_GET['trace_production'])) $trace_production = TRUE;
	else $trace_production = FALSE;
	if(isset($_GET['random_seed'])) $random_seed = $_GET['random_seed'];
	else $random_seed = 0;
	if(isset($_GET['csound_orchestra'])) $csound_orchestra = $_GET['csound_orchestra'];
	else $csound_orchestra = '';

	$table = explode('/',$grammar_path);
	$grammar_name = $table[count($table) - 1];
	$dir = str_replace($grammar_name,'',$grammar_path);

	if($output <> '') @unlink($output);
	if($tracefile <> '') @unlink($tracefile);

	$thisgrammar = $grammar_path;
	if(is_integer(strpos($thisgrammar,' ')))
		$thisgrammar = '"'.$thisgrammar.'"';
	$command = $application_path."bp ".$instruction." -gr ".$thisgrammar;

	$thisalphabet = $alphabet_file;
	if(is_integer(strpos($thisalphabet,' ')))
		$thisalphabet = '"'.$thisalphabet.'"';
	$thisalphabet = $dir.$thisalphabet;

	if($alphabet_file <> '') $command .= " -ho ".$thisalphabet;

	if($note_convention <> '') $command .= " --".$note_convention;
	if($settings_file <> '') $command .= " -se ".$dir.$settings_file;
	if($csound_file <> '') $command .= " -cs ".$dir.$csound_file;
	if($objects_file <> '') $command .= " -mi ".$dir.$objects_file;
	
	if($startup <> '') $command .= " --start ".$startup;
	if($instruction == "produce" OR $instruction == "produce-all") {
		switch($file_format) {
			case "data":
				$command .= " -d -o ".$output;
				break;
			case "midi":
				$command .= " -d --midiout ".$output;
				break;
			case "csound":
				$command .= " -d --csoundout ".$output;
				break;
			default:
				$command .= " -d --rtmidi";
				break;
			}
		}
	if($tracefile <> '') $command .= " --traceout ".$tracefile;
	if($show_production) $command .= " --show-production";
	if($trace_production) $command .= " --trace-production";
	$command .= " --seed ".$random_seed;
	}

$dircontent = scandir($temp_dir);
foreach($dircontent as $thisfile) {
	$time_saved = filemtime($temp_dir.$thisfile);
//	echo $thisfile." ➡ ".date('Y-m-d H\hi',$time_saved)."<br />";
	$table = explode('_',$thisfile);
	if($table[0] <> "trace") continue;
	if($table[1] <> session_id()) continue;
	if($table[2] == "image") unlink($temp_dir.$thisfile);
	}

echo "<p><small>command = <font color=\"red\">".$command."</font></small></p>";

$o = send_to_console($command);
$n_messages = count($o);
$no_error = FALSE;
for($i = 0; $i < $n_messages; $i++) {
	$mssg = $o[$i];
	if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
	}
echo "<hr>";

if($instruction <> "help") {
	$time_start = time();
	$time_end = $time_start + $max_sleep_time_after_bp_command;
	$donefile = $temp_dir."trace_".session_id()."_done.txt";
//	echo $donefile."<br />";
	while(TRUE) {
		if(file_exists($donefile)) break;
		if(time() > $time_end) break;
		}
//	echo "time = ".(time() - $time_start)."<br />";
	@unlink($donefile);
	$tracefile_html = clean_up_file($tracefile);
	$trace_link = $tracefile_html;
	$output_link = $output;
	
	if($test) echo "output = ".$output."<br />";
	if($test) echo "tracefile_html = ".$tracefile_html."<br />";
	if($test) echo "dir = ".$dir."<br />";
	if($test) echo "trace_link = ".$trace_link."<br />";
	if($test) echo "output_link = ".$output_link."<br />";

	if(!$no_error) {
		echo "<p><font color=\"red\">Errors found… Check the </font> <a onclick=\"window.open('".$trace_link."','errors','width=800,height=800,left=400'); return false;\" href=\"".$trace_link."\">error trace</a> file!</p>";
		}
	else {
		echo "<p>";
		if($output <> '' AND $file_format <> "midi") echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$output_link."','".$file_format."','width=800,height=800,left=300'); return false;\" href=\"".$output_link."\">output file</a><br />";
		if($trace_production OR $instruction == "templates" OR $show_production OR $trace_production) echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$trace_link."','trace','width=800,height=800,left=400'); return false;\" href=\"".$trace_link."\">trace file</a>";
		echo "</p>";
		
		if($file_format == "midi") {
			$midi_file_link = $output;
			if(file_exists($midi_file_link)) {
				echo "<p><a href=\"#midi\" onClick=\"MIDIjs.play('".$midi_file_link."');\"><img src=\"pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />Play MIDI file</a>";
				echo " (<a href=\"#midi\" onClick=\"MIDIjs.stop();\">Stop playing</a>)";
				echo "&nbsp;or <a href=\"".$midi_file_link."\">download it</a></p>";
				}
			}
		
		// Prepare images if any
		$dircontent = scandir($temp_dir);
		echo "<table style=\"background-color:inherit;\"><tr>";
		$number_images = 0;
		foreach($dircontent as $thisfile) {
			$table = explode('_',$thisfile);
			if($table[0] <> "trace") continue;
			if($table[1] <> session_id()) continue;
			if($table[2] <> "image") continue;
			if(isset($table[4])) continue;
			echo "<td style=\"background-color:white; border-radius: 6px; border: 4px solid Gold; vertical-align:middle; text-align: center; padding:8px;\>";
			$number = intval(str_replace(".html",'',$table[3]));
			$content = @file_get_contents($temp_dir.$thisfile,FALSE);
			$table2 = explode(chr(10),$content);
			$imax = count($table2);
			$table3 = array();
			$title1 = $grammar_name."_Image_".$number;
			$title2 = $grammar_name." Image ".$number;
			$WidthMax = $HeightMax = 0;
			$number_images++;
			for($i = 0; $i < $imax; $i++) {
				$line = trim($table2[$i]);
			//	echo $i." ".recode_tags($line)."<br />";
				if(is_integer($pos=strpos($line,"canvas.width"))) {
					$table4 = explode("=",$line);
					$WidthMax = round(intval($table4[2])) + 10;
				//	echo $i.") WidthMax = ".$WidthMax."<br />";
					}
				if(is_integer($pos=strpos($line,"canvas.height"))) {
					$table4 = explode("=",$line);
					$HeightMax = round(intval($table4[2]));
				//	echo $i.") HeightMax = ".$HeightMax."<br />";
					}
				}
			for($i = $j = 0; $i < $imax; $i++) {
				$line = trim($table2[$i]);
				$table3[$j] = $line;
				$j++;
				}
			$link = $temp_dir.$thisfile;
			$left = 10 + (30 * ($number - 1));
			$window_height = 600;
			if($HeightMax < $window_height) $window_height = $HeightMax + 60;
			$window_width = 1200;
			if($WidthMax < $window_width) $window_width = $WidthMax +  20;
			echo "<div style=\"border:2px solid gray; background-color:azure; width:8em;  padding:2px; text-align:center; border-radius: 6px;\"><a onclick=\"window.open('".$link."','".$title1."','width=".$window_width.",height=".$window_height.",left=".$left."'); return false;\" href=\"".$link."\">Image ".$number."</a></div>&nbsp;";
			echo "</td>";
			if(++$number_images > 4) echo "</tr><tr>";
			}
		echo "</tr></table>";
		echo "<br />";
		}
		
	// Process Csound score if possible
	if($file_format == "csound") {
		if($csound_orchestra == '' AND $file_format == "csound") {
			$csound_orchestra = "default.orc";
			echo "<p><font color=\"red\">➡</font> Csound orchestra file was not specified. We'll try the default orchestra: <font color=\"blue\">".$dir.$csound_orchestra."</font>.</p>";
			}
		if(!file_exists($dir.$csound_orchestra)) {
			echo "<p><font color=\"red\">➡</font> No orchestra file has been found here: <font color=\"blue\">".$dir.$csound_orchestra."</font>. Csound will not create a sound file.</p>";
			}
		else {
			$csound_file_link = $output;
			$sound_file_link = str_replace(".sco",".wav",$csound_file_link);
			@unlink($sound_file_link);
			$olddir = getcwd();
			chdir($dir); // Strangely, Csound won't accept "$dir.$csound_orchestra"
		//	sleep(4);
			if(file_exists($csound_file_link)) {
				$command = $csound_path."csound --wave -o ".$sound_file_link." ".$csound_orchestra." ".$csound_file_link;
				echo "<p><small>command = <font color=\"red\">".$command."</font></small></p>";
				exec($command,$result_csound);
				$n_messages_csound = count($result_csound);
				for($i=0; $i < $n_messages_csound; $i++) {
					$mssg = $result_csound[$i];
					echo $mssg."<br />";
					}
				}
		//	sleep(2);
			echo "<audio controls>";
			echo "<source src=\"".$sound_file_link."\" type=\"audio/wav\">";
			echo "Your browser does not support the audio tag.";
			echo "</audio>";
			echo "<p><a target=\"_blank\" href=\"".$sound_file_link."\">Download this sound file</a> (".$sound_file_link.")</p>";
			echo "<p><font color=\"red\">➡</font> If you don't hear sounds it may be due to mismatch between Csound score and orchestra.</p>";
			}
		}
	}

for($i=0; $i < $n_messages; $i++) {
	$mssg = $o[$i];
	$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
	echo $mssg."<br />";
	}
if($n_messages == 0) echo "No message produced…";
?>
