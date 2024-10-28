<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "timebase.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
save_settings("last_directory",$current_directory);

require_once("_header.php");
display_darklight();

echo "<script type='text/javascript' src='https://www.midijs.net/lib/midi.js'></script>";
echo "<p>";
echo "&nbsp;&nbsp;Workspace = <a href=\"index.php?path=".urlencode($current_directory)."\">".$current_directory."</a></p>";
echo link_to_help();

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
// echo "temp_folder = ".$temp_folder."<br />";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}

echo "<h2>Time base file “".$filename."”</h2>";
save_settings("last_name",$filename); 

$midi_import_file = $temp_dir.$temp_folder.SLASH."imported_codes.mid";
$midi_import_mf2t = $temp_dir.$temp_folder.SLASH."imported_mf2t.txt";
$upload_filename = '';
if(isset($_FILES['mid_upload']) AND $_FILES['mid_upload']['tmp_name'] <> '') {
	$upload_filename = $_FILES['mid_upload']['name'];
	if($_FILES["mid_upload"]["size"] > MAXFILESIZE) {
		echo "<h3><font color=\"red\">Uploading failed:</font> <span class=\"green-text\">".$upload_filename."</span> <font color=\"red\">is larger than ".MAXFILESIZE." bytes</font></h3>";
		}
	else {
		$tmpFile = $_FILES['mid_upload']['tmp_name'];
		@unlink($midi_import_mf2t);
	//	copyemz($tmpFile,$midi_import_file) or die('Problem uploading this MIDI file');
		move_uploaded_file($tmpFile,$midi_import_file) or die('Problem uploading this MIDI file');
		@chmod($midi_import_file,0666);
		$table = explode('.',$upload_filename);
		$extension = end($table);
		if($extension <> "mid" and $extension <> "midi") {
			echo "<h4><font color=\"red\">Uploading failed:</font> <span class=\"green-text\">".$upload_filename."</span> <font color=\"red\">is not a MIDI file!</font></h4>";
			@unlink($midi_import_file);
			}
		else {
			$_POST['upload_filename'] = $upload_filename;
		//	echo "midi_import_file = ".$midi_import_file."<br />";
			$MIDIfiletype = MIDIfiletype($midi_import_file);
			if($MIDIfiletype < 0) {
				echo "<p><font color=\"red\">File </font>“<span class=\"green-text\">".$upload_filename."” <font color=\"red\">is unreadable as a MIDI file.</span></p>";
				$upload_filename = $_POST['upload_filename'] = '';
				}
		//	echo "MIDI filetype = ".$MIDIfiletype."<br />";
			else if($MIDIfiletype > 1) {
				echo "<h4><font color=\"red\">MIDI file </font>“<span class=\"green-text\">".$upload_filename."</span>” <font color=\"red\"> of </font><span class=\"green-text\">type ".$MIDIfiletype." </span><font color=\"red\">is not accepted. Only types 0 and 1 are compliant.</font></h4>";
				$upload_filename = $_POST['upload_filename'] = '';
				}
			else {
				echo "<h3 id=\"timespan\"><font color=\"red\">Converting MIDI file:</font> <span class=\"green-text\">".$upload_filename."</span></h3>";
				echo "MIDI filetype = ".$MIDIfiletype."<br />";
				$midi = new Midi();
				$midi_text_bytes = convert_mf2t_to_bytes(FALSE,$midi_import_mf2t,$midi,$midi_import_file);
				$division = $_POST['division'] = $midi_text_bytes[0];
				$tempo = $_POST['tempo'] = $midi_text_bytes[1];
				$timesig = $_POST['timesig'] = "0 TimeSig ".$midi_text_bytes[2]." ".$midi_text_bytes[3]." ".$midi_text_bytes[4];
				$message = fix_mf2t_file($midi_import_mf2t,"imported_",0);
			//	if($message <> '') echo $message."<br />";
				}
			}
		}
	}

if(isset($_POST['changestatus'])) {
	$new_track = isset($_POST['addtrack']);
//	echo "<p id=\"timespan\" style=\"color:red;\">Saved all data…</p>";
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Time base file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	$maxticks = $_POST['maxticks'];
	if($new_track) {
		$i_cycle = $maxticks;
		$_POST['TickKey_'.$i_cycle] = 60;
		$_POST['TickChannel_'.$i_cycle] = 1;
		$_POST['TickVelocity_'.$i_cycle] = 64;
		$_POST['TickCycle_'.$i_cycle] = 4;
		$_POST['Ptick_'.$i_cycle] = 1;
		$_POST['Qtick_'.$i_cycle] = 1;
		$_POST['TickDuration_'.$i_cycle] = 50;
		$maxticks++;
		}
	$maxbeats = $_POST['maxbeats'];
	fwrite($handle,$maxticks."\n");
	fwrite($handle,$maxbeats."\n");
	for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
		fwrite($handle,"1\n"); // Obsolete variable
		fwrite($handle,"7\n"); // Obsolete variable
		$key = intval($_POST['TickKey_'.$i_cycle]);
		if($key < 0) $key = 0;
		if($key > 127) $key = 127;
		fwrite($handle,$key."\n");
		$channel = intval($_POST['TickChannel_'.$i_cycle]);
		if($channel < 1) $channel = 1;
		if($channel > 16) $channel = 16;
		fwrite($handle,$channel."\n");
		$velocity = intval($_POST['TickVelocity_'.$i_cycle]);
		if($velocity < 0) $velocity = 0;
		if($velocity > 127) $velocity = 127;
		fwrite($handle,$velocity."\n");
		$TickCycle[$i_cycle] = intval($_POST['TickCycle_'.$i_cycle]);
		if($TickCycle[$i_cycle] > 40) $TickCycle[$i_cycle] = 40;
		if($TickCycle[$i_cycle] < 1) $TickCycle[$i_cycle] = 1;
		fwrite($handle,$TickCycle[$i_cycle]."\n");
		$p_tick = intval($_POST['Ptick_'.$i_cycle]);
		$q_tick = intval($_POST['Qtick_'.$i_cycle]);
		$gcd = gcd($p_tick,$q_tick);
		$p_tick = $p_tick / $gcd;
		$q_tick = $q_tick / $gcd;
		fwrite($handle,$p_tick."\n");
		fwrite($handle,$q_tick."\n");
		fwrite($handle,intval($_POST['TickDuration_'.$i_cycle])."\n");
		for($i = 0; $i < $maxbeats; $i++) {
			if(isset($_POST['ThisTick_'.$i_cycle.'_'.$i])) fwrite($handle,"1\n");
			else fwrite($handle,"0\n");
			}
		}
	fwrite($handle,"DATA: ".recode_tags($_POST['comment'])."\n");
	fclose($handle);
	}
try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(trim($content) == '') $content = @file_get_contents("timebase_template",TRUE);
$extract_data = extract_data(TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$j = 0;
$table = explode(chr(10),$content);
$maxticks = $table[$j++];
// echo "maxticks = ".$maxticks."<br />";
$maxbeats = $table[$j++];
// echo "maxbeats = ".$maxbeats."<br />";

$p_clock = 1;
$q_clock = 1;
if(isset($_POST['p_clock'])) $p_clock = round($_POST['p_clock']);
if(isset($_POST['q_clock'])) $q_clock = round($_POST['q_clock']);
$g = gcd($p_clock,$q_clock);
$p_clock = $p_clock / $g;
$q_clock = $q_clock / $g;
$metronome = metronome($p_clock,$q_clock);
if(isset($_POST['division']) AND $_POST['division'] > 0) $division = $_POST['division'];
else $division = 1000;
if(isset($_POST['tempo']) AND $_POST['tempo'] > 0) $tempo = $_POST['tempo'];
else $tempo = 1000000;
if(isset($_POST['timesig']) AND $_POST['timesig'] <> '') $timesig = $_POST['timesig'];
else $timesig = "0 TimeSig 4/4 24 8";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"changestatus\" value=\"1\">";
echo "<input type=\"hidden\" name=\"maxticks\" value=\"".$maxticks."\">";
echo "<input type=\"hidden\" name=\"maxbeats\" value=\"".$maxbeats."\">";
echo "<input class=\"save\" type=\"submit\" name=\"savealldata\" value=\"SAVE ALL DATA\">";
echo "<p>&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"p_clock\" size=\"8\" value=\"".$p_clock."\"> beats in <input type=\"text\" name=\"q_clock\" size=\"8\" value=\"".$q_clock."\"> sec. ➡ mm = ".$metronome." beats/mn</p>";
if(file_exists($midi_import_mf2t)) {
	$new_metronome = metronome(1000000,$tempo);
	if($new_metronome <> $metronome) {
		$new_p_clock = round(1000 * $new_metronome);
		$new_q_clock = 60000;
		$g = gcd($new_p_clock,$new_q_clock);
		$new_p_clock = $new_p_clock / $g;
		$new_q_clock = $new_q_clock / $g;
		echo "<p><font color=\"red\">➡</font> MIDIfile suggests <font color=\"red\">".$new_p_clock." beats</font> in <font color=\"red\">".$new_q_clock." sec.</font> ➡ mm = <font color=\"red\">".$new_metronome." beats/mn</font></p>";
		}
	}

echo "<table class=\"thicktable\">";
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	$mute[$i_cycle] = FALSE;
	if(isset($_POST['mute_'.$i_cycle])) $mute[$i_cycle] = $_POST['mute_'.$i_cycle];
	if(isset($_POST['inactivate_'.$i_cycle])) $mute[$i_cycle] = TRUE;
	if(isset($_POST['activate_'.$i_cycle])) $mute[$i_cycle] = FALSE;
	echo "<input type=\"hidden\" name=\"mute_".$i_cycle."\" value=\"".$mute[$i_cycle]."\">";
	$j += 2;
	$TickKey[$i_cycle] = $table[$j++];
	$TickChannel[$i_cycle] = $table[$j++];
	$TickVelocity[$i_cycle] = $table[$j++];
	$TickCycle[$i_cycle] = $table[$j++];
	$Ptick[$i_cycle] = $table[$j++];
	$Qtick[$i_cycle] = $table[$j++];
	$TickDuration[$i_cycle] = $table[$j++];
	for($i = 0; $i < $maxbeats; $i++) $ThisTick[$i_cycle][$i] = $table[$j++];
	echo "<tr>";
	echo "<td style=\"text-align: center; vertical-align: middle; background-color: gold; font-size: x-large; color:red;\" rowspan = \"2\"><b>";
	if($mute[$i_cycle]) echo "(<font color=\"white\">".($i_cycle + 1)."</font>)";
	else echo ($i_cycle + 1);
	echo "</b><br />";
	if(!$mute[$i_cycle])
		echo "<input class=\"save\" type=\"submit\" name=\"inactivate_".$i_cycle."\" value=\"MUTE\">";
	else
		echo "<input class=\"save\" type=\"submit\" name=\"activate_".$i_cycle."\" value=\"ACTIVATE\">";
	echo "</td>";
	echo "<td style=\"padding:6px;\">Cycle of <input type=\"text\" name=\"TickCycle_".$i_cycle."\" size=\"3\" value=\"".$TickCycle[$i_cycle]."\"> beat(s) [max 40]</td>";
	echo "<td colspan=\"2\" style=\"text-align: right;\">Speed ratio <input type=\"text\" name=\"Ptick_".$i_cycle."\" size=\"3\" value=\"".$Ptick[$i_cycle]."\">&nbsp;/&nbsp;<input type=\"text\" name=\"Qtick_".$i_cycle."\" size=\"3\" value=\"".$Qtick[$i_cycle]."\"></td>";
	echo "</tr>";
	echo "<tr>";
	$key = $TickKey[$i_cycle];
	echo "<td style=\"padding:6px; text-align:center;\">key = <input type=\"text\" name=\"TickKey_".$i_cycle."\" size=\"3\" value=\"".$TickKey[$i_cycle]."\">&nbsp;&nbsp;<span style=\"color:#007BFF;;\">".key_to_note('English',$key)." / ".key_to_note('French',$key)." / ".key_to_note('Indian',$key)."</span></td>";
	echo "<td colspan=\"2\" style=\"padding:6px; text-align:center;\">channel = <input type=\"text\" name=\"TickChannel_".$i_cycle."\" size=\"3\" value=\"".$TickChannel[$i_cycle]."\"> velocity = <input type=\"text\" name=\"TickVelocity_".$i_cycle."\" size=\"3\" value=\"".$TickVelocity[$i_cycle]."\"> duration = <input type=\"text\" name=\"TickDuration_".$i_cycle."\" size=\"3\" value=\"".$TickDuration[$i_cycle]."\"> ms</td>";
	echo "</tr>";
	echo "<tr><td colspan=\"4\">";
	for($i = 0; $i < $maxbeats; $i++) {
		echo "<input type=\"checkbox\" name=\"ThisTick_".$i_cycle."_".$i."\"";
		if($ThisTick[$i_cycle][$i] == 1) echo " checked";
		if($i >= $TickCycle[$i_cycle]) echo " disabled";
		echo ">&nbsp;";
		}
	echo "</td></tr>";
	echo "<tr><td colspan=\"4\" style=\"background-color:gold;\">";
	echo "</td></tr>";
	}

$polymetric_expression = polymetric_expression($mute,$TickKey,$TickCycle,$TickChannel,$TickVelocity,$Ptick,$Qtick,$TickDuration,$ThisTick,$p_clock,$q_clock);

$MIDIfile_exists = FALSE;
if(file_exists($midi_import_mf2t)) {
	$MIDIfile_exists = TRUE;
	$i_midifile = $i_cycle;
	$mute[$i_midifile] = FALSE;
	if(isset($_POST['mute_'.$i_midifile])) $mute[$i_midifile] = $_POST['mute_'.$i_cycle];
	if(isset($_POST['inactivate_'.$i_midifile])) $mute[$i_midifile] = TRUE;
	if(isset($_POST['activate_'.$i_midifile])) $mute[$i_midifile] = FALSE;
	echo "<input type=\"hidden\" name=\"mute_".$i_midifile."\" value=\"".$mute[$i_midifile]."\">";
	// We reconstruct the imported MIDI file so that bugs are fixed…
	$mf2t_content = @file_get_contents($midi_import_mf2t,TRUE);
	$duration_of_midifile = duration_of_midifile($mf2t_content);
	$beats_of_midifile = round(($duration_of_midifile * $p_clock / $q_clock / 1000),2);
	echo "<tr id=\"midi\">";
	echo "<td style=\"text-align: center; vertical-align: middle; background-color: gold; font-size: x-large; color:red;\" rowspan=\"2\"><b>";
	if($mute[$i_midifile]) echo "(<font color=\"white\">".($i_midifile + 1)."</font>)";
	else echo ($i_midifile + 1);
	echo "</b><br />";
	if(!$mute[$i_midifile])
		echo "<input class=\"save\" type=\"submit\" name=\"inactivate_".$i_midifile."\" value=\"MUTE\">";
	else
		echo "<input class=\"save\" type=\"submit\" name=\"activate_".$i_midifile."\" value=\"ACTIVATE\">";
	echo "</td>";
	echo "<td colspan=\"3\" style=\"padding:6px; text-align:center;\">";
	if(isset($_POST['upload_filename']) AND $_POST['upload_filename'] <> '') $upload_filename = $_POST['upload_filename'];
	echo "<input type=\"hidden\" name=\"upload_filename\" value=\"".$upload_filename."\">";
	echo "<a href=\"#midi\" onClick=\"MIDIjs.play('".$midi_import_file."');\"><img src=\"pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />Play “<span class=\"green-text\">".$upload_filename."</span>” MIDI file</a>";
	echo " (<a href=\"#midi\" onClick=\"MIDIjs.stop();\">Stop playing</a>)";
	echo "<br />Duration = ".round($duration_of_midifile / 1000, 3)." sec = ".$beats_of_midifile." beats<br />Division = <input type=\"text\" name=\"division\" size=\"5\" value=\"".$division."\">&nbsp;&nbsp;&nbsp;Tempo = <input type=\"text\" name=\"tempo\" size=\"7\" value=\"".$tempo."\"> µs ➡ show <a onclick=\"window.open('".nice_url($midi_import_mf2t)."','importedMIDIbytes','width=300,height=500,left=300'); return false;\" href=\"".nice_url($midi_import_mf2t)."\">MF2T code</a>";
	echo "</td></tr>";
	$colspan = 3;
	}
else $colspan = 4;


echo "<tr><td colspan=\"".$colspan."\" style=\"padding:6px; text-align:center;\">";
echo "Load (or replace) MIDI file (*.mid): <input type=\"file\" name=\"mid_upload\">&nbsp;<input type=\"submit\" value=\" send \">";
echo "</td></tr>";
if(isset($table[$j])) $comment = $table[$j];
else $comment = '';
$comment = trim(str_replace("DATA:",'',$comment));
$comment = str_ireplace("<HTML>",'',$comment);
$comment = str_ireplace("</HTML>",'',$comment);
echo "<tr><td colspan=\"4\" style=\"padding:6px;\">";
echo "<div style=\"float:right;\"><input class=\"save\" type=\"submit\" name=\"addtrack\" value=\"ADD ANOTHER TRACK\"></div>";

echo "Comment on this timebase:<br /><input type=\"text\" name=\"comment\" size=\"80\" value=\"".$comment."\">";
echo "</td></tr>";
echo "</table>";

$check_midi = FALSE;
// $check_midi = TRUE;

if($check_midi) echo "p_clock = ".$p_clock."<br />";
if($check_midi) echo "q_clock = ".$q_clock."<br />";

for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) {
		$duration[$i_cycle] = 0;
		continue;
		}
	$mute[$i_cycle] = TRUE;
	for($i = 0; $i < $maxbeats; $i++) {
		if($ThisTick[$i_cycle][$i] == 1) {
			$mute[$i_cycle] = FALSE;
		//	echo "<p>".$i_cycle." is not mute</p>";
			break;
			}
		}
	if($mute[$i_cycle]) {
		$duration[$i_cycle] = 0;
		continue;
		}
	$duration[$i_cycle] = 1000 * $TickCycle[$i_cycle] * $Qtick[$i_cycle] / $Ptick[$i_cycle];
	if($check_midi) echo ($i_cycle + 1).") duration = ".$duration[$i_cycle]." ms<br />";
	}
if($MIDIfile_exists AND !$mute[$i_midifile]) {
	$duration[$i_midifile] = $duration_of_midifile;
	if($check_midi) echo "MIDI file duration = ".$duration[$i_midifile]." ms<br />";
	}
$gcd = gcd_array($duration,0);
if($gcd == 0) $gcd = 1;
if($check_midi) echo "gcd = ".$gcd."<br /><br />";

// $mult = 1;
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$duration[$i_cycle] = $duration[$i_cycle] / $gcd;
//	$mult = $mult * ($duration[$i_cycle] / gcd($mult,$duration[$i_cycle]));
	if($check_midi) echo ($i_cycle+ 1)." relative duration = ".$duration[$i_cycle]."<br />";
	}
if($MIDIfile_exists AND !$mute[$i_midifile]) {
	$duration[$i_midifile] = $duration[$i_midifile] / $gcd;
//	$mult = $mult * ($duration[$i_midifile] / gcd($mult,$duration[$i_midifile]));
	if($check_midi) echo "MIDI file relative duration = ".$duration[$i_midifile]."<br />";
	}
// if($check_midi) echo "mult = ".$mult."<br /><br />";

$lcm = 1;
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$x = gcd($TickCycle[$i_cycle] * $Qtick[$i_cycle],$Ptick[$i_cycle]);
	$y = ($TickCycle[$i_cycle] * $Qtick[$i_cycle]) / $x;
	$lcm = ($lcm * $y) / gcd($lcm, $y);
	}
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$i_ok = $i_cycle;
	if($MIDIfile_exists AND !$mute[$i_midifile]) {
		$repeat[$i_cycle] = ceil($duration[$i_midifile] / $duration[$i_cycle]);
		}
	else $repeat[$i_cycle] = ($lcm * $Ptick[$i_cycle]) / ($TickCycle[$i_cycle] * $Qtick[$i_cycle]);
	if($check_midi) echo ($i_cycle + 1)." repeat = ".$repeat[$i_cycle]."<br />";
	}
		
/* for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$i_ok = $i_cycle;
	if($MIDIfile_exists AND !$mute[$i_midifile]) {
		$repeat[$i_cycle] = round($duration[$i_midifile] / $duration[$i_cycle]);
		}
	else $repeat[$i_cycle] = $mult / $duration[$i_cycle];
	if($check_midi) echo ($i_cycle + 1)." repeat = ".$repeat[$i_cycle]."<br />";
	} */

if($MIDIfile_exists AND !$mute[$i_midifile]) {
	$actual_duration_combined = round($duration_of_midifile / 1000, 3);
	$actual_beats_combined = round($actual_duration_combined * $p_clock / $q_clock, 2);
	}
else {
	$actual_beats_combined = $repeat[$i_ok] * $TickCycle[$i_ok] * $Qtick[$i_ok] / $Ptick[$i_ok];
	$actual_duration_combined = $actual_beats_combined * $q_clock / $p_clock;
	}

$number_of_tracks = 1;
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$number_of_tracks++;
	}

$mf2t_no_header = array();
if($MIDIfile_exists AND !$mute[$i_midifile]) {
	$number_of_tracks++;
	$mf2t_no_header = mf2t_no_header($mf2t_content);
	}
$mf2t = $temp_dir.$temp_folder.SLASH."mf2t.txt";

$handle = fopen($mf2t,"w");
if(isset($_POST['max_repeat'])) $max_repeat = intval($_POST['max_repeat']);
else $max_repeat = 3;
if($max_repeat == 0) $max_repeat = 1;
if(isset($_POST['max_time_play'])) $max_time_play = $_POST['max_time_play'];
else $max_time_play = 60 * 2; // Not longer than 2 minutes
if(isset($_POST['end_silence'])) $end_silence = $_POST['end_silence'];
else $end_silence = 200; // ms
if($MIDIfile_exists AND !$mute[$i_midifile]) {
	$MaxTime = $duration_of_midifile;
	$max_repeat = 1;
	}
else $MaxTime = 1000 * $max_time_play;
fwrite($handle,"MFile 1 ".$number_of_tracks." ".$division."\n");
fwrite($handle,"MTrk\n");
fwrite($handle,"0 Meta TrkName \"header\"\n");
fwrite($handle,$timesig."\n");
fwrite($handle,"0 Tempo ".$tempo."\n");
fwrite($handle,"0 KeySig 0 major\n");
fwrite($handle,"0 Meta TrkEnd\n");
fwrite($handle,"TrkEnd\n");
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	$trk = $i_cycle + 1;
	$time = $start_time = 0;
	fwrite($handle,"MTrk\n");
	$track_name = "track_".$trk;
	fwrite($handle,"0 Meta TrkName \"".$track_name."\"\n");
	$delta_t = 1000 * $q_clock *  $Qtick[$i_cycle] / $p_clock / $Ptick[$i_cycle];
	for($r = 0; $r < ($max_repeat * $repeat[$i_cycle]); $r++) {
		if($check_midi) echo $track_name." repeat ".$r."<br />";
		if($start_time > $MaxTime) break;
		for($i = 0; $i < $maxbeats; $i++) {
			$time = $start_time + round($i * $delta_t);
			if($i >= $TickCycle[$i_cycle]) break;
			if($ThisTick[$i_cycle][$i] == 1) {
				$channel = $TickChannel[$i_cycle];
				$key = $TickKey[$i_cycle];
				$velocity = $TickVelocity[$i_cycle];
				$mf2t_line = $time." On ch=".$channel." n=".$key." v=".$velocity;
				fwrite($handle,$mf2t_line."\n");
				$time += $TickDuration[$i_cycle];
				$velocity = 0;
				$mf2t_line = $time." On ch=".$channel." n=".$key." v=".$velocity;
				fwrite($handle,$mf2t_line."\n");
				}
			}
		$start_time = $time;
		}
	$time += $end_silence;
	$mf2t_line = $time." On ch=".$channel." n=".$key." v=".$velocity;
	fwrite($handle,$mf2t_line."\n");
	fwrite($handle,$time." Meta TrkEnd\n");
	fwrite($handle,"TrkEnd\n");
	}
if(count($mf2t_no_header) > 0) {
	for($i = 0; $i < count($mf2t_no_header); $i++) {
		fwrite($handle,$mf2t_no_header[$i]."\n");
//		echo $mf2t_no_header[$i]."<br />";
		}
	}
fclose($handle);

$midi_file = $temp_dir.$temp_folder.SLASH."midicodes.mid";

$mf2t_content = @file_get_contents($mf2t,TRUE);
$midi = new Midi();
$midi->importTxt($mf2t_content);
$midi->saveMidFile($midi_file);

if(file_exists($midi_file)) {
	echo "&nbsp;<a href=\"#midi\" onClick=\"MIDIjs.play('".$midi_file."');\"><img src=\"pict/loudspeaker.png\" width=\"70px;\" style=\"vertical-align:middle;\" />Play combined MIDI file</a>";
	echo " (<a href=\"#midi\" onClick=\"MIDIjs.stop();\">Stop playing</a>)";
	if(!$MIDIfile_exists OR $mute[$i_midifile]) echo "<br />up to <input type=\"text\" name=\"max_repeat\" size=\"3\" value=\"".$max_repeat."\">&nbsp;repetitions and less than <input type=\"text\" name=\"max_time_play\" size=\"3\" value=\"".$max_time_play."\">&nbsp;sec ending with silence of <input type=\"text\" name=\"end_silence\" size=\"5\" value=\"".$end_silence."\">&nbsp;ms";
	echo "&nbsp;&nbsp;&nbsp;➡ Show <a onclick=\"window.open('".nice_url($mf2t)."','combinedMIDIbytes','width=300,height=500,left=100'); return false;\" href=\"".nice_url($mf2t)."\">MF2T code</a>";
	}
echo "<p><input class=\"save\" type=\"submit\" name=\"savealldata\" value=\"SAVE ALL DATA\"></p>";
echo "</form>";

echo "<p>Equivalent polymetric expression:<br /><small><span style=\"color:#007BFF;;\">".$polymetric_expression."</span></small></p>";

echo "<p>Actual duration of complete cycle should be ".$actual_beats_combined." beats = ".$actual_duration_combined." seconds with:</p>";
echo "<ul>";
for($i_cycle = 0; $i_cycle < $maxticks; $i_cycle++) {
	if($mute[$i_cycle]) continue;
	echo "<li>track #".($i_cycle + 1)." repeated ".$repeat[$i_cycle]." time(s)</li>";
	}
echo "</ul>";
?>
