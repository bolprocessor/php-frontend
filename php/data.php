<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "data.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
echo "<p>Current directory = ".$dir."</p>";
echo link_to_help();

echo "<h3>Data file “".$filename."”</h3>";

if(isset($_POST['playitem']) OR isset($_POST['expanditem'])) {
	$i = $_POST['i'];
	$line = $_POST['line'];
	$line = str_replace('•'," . ",$line);
	$line_recoded = recode_tags($line);
	echo "<p>Playing item: <font color=\"blue\">".$line_recoded."</font></p>";
	$data = $temp_dir."temp_".session_id()."outdata.bpda";
	$result_textfile = $temp_dir."temp_".session_id()."result.txt";
	if(file_exists($result_textfile)) unlink($result_textfile);
	$handle = fopen($data,"w");
	$file_header = $top_header."\n// Data saved as \"outdata.bpda\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$line."\n");
	fclose($handle);
	$alphabet = $settings = $objects = $csound = '';
	if($_POST['alphabet_file'] <> '') $alphabet = $dir.$_POST['alphabet_file'];
	if($_POST['settings_file'] <> '') $settings = $dir.$_POST['settings_file'];
	if($_POST['objects_file'] <> '') $objects = $dir.$_POST['objects_file'];
	if($_POST['csound_file'] <> '') $csound = $dir.$_POST['csound_file'];
	$application_path = $bp_application_path;
	if(isset($_POST['playitem'])) $command = $application_path."bp play";
	if(isset($_POST['expanditem'])) $command = $application_path."bp expand-item";
	$command .= " -da ".$data;
	if($alphabet <> '') $command .= " -ho \"".$alphabet."\"";
	if(isset($_POST['playitem']) AND $objects <> '') $command .= " -mi \"".$objects."\"";
	if(isset($_POST['playitem']) AND $csound <> '') $command .= " -cs \"".$csound."\"";
	if($settings <> '') $command .= " -se \"".$settings."\"";
//	if(isset($_POST['playitem'])) $command .= " -d --rtmidi ";
	if(isset($_POST['playitem'])) $command .= " -d --csoundout \"".$result_textfile."\"";
//	if(isset($_POST['playitem'])) $command .= " -d --midiout ".$temp_dir."temp_".session_id()."check_play.mid";
	if(isset($_POST['expanditem'])) $command .= " -d -o ".$result_textfile;
	$command .= " --traceout ".$tracefile;
	
	echo "<p style=\"color:red;\">".$command."</p>";
	$no_error = FALSE;
	$o = send_to_console($command);
	$n_messages = count($o);
	if($n_messages > 0) {
		for($i=0; $i < $n_messages; $i++) {
			$mssg[$i] = $o[$i];
			$mssg[$i] = clean_up_encoding(TRUE,TRUE,$mssg[$i]);
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
	
	if(file_exists($result_textfile))  {
		echo "<p><font color=\"red\">➡ </font>Result:<br />";
		$content = @file_get_contents($result_textfile,TRUE);
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

if(isset($_FILES['music_xml_import']) AND $_FILES['music_xml_import']['tmp_name'] <> '') {
	$upload_filename = $_FILES['music_xml_import']['name'];
	if($_FILES["music_xml_import"]["size"] > MAXFILESIZE) {
		echo "<h3><font color=\"red\">Uploading failed:</font> <font color=\"blue\">".$upload_filename."</font> <font color=\"red\">is larger than ".MAXFILESIZE." bytes</font></h3>";
		}
	else {
		$tmpFile = $_FILES['music_xml_import']['tmp_name'];
/*		move_uploaded_file($tmpFile,$music_xml_file) or die('Problem uploading this MusicXML file');
		@chmod($music_xml_file,0666); */
		$table = explode('.',$upload_filename);
		$extension = end($table);
		if($extension <> "musicxml" and $extension <> "xml") {
			echo "<h4><font color=\"red\">Uploading failed:</font> <font color=\"blue\">".$upload_filename."</font> <font color=\"red\">does not have the extension of a MusicXML file!</font></h4>";
			}
		else {
			$message = '';
		//	$xml = @file_get_contents($tmpFile,TRUE);
			$data = '';
			$partwise = $timewise = $note_on = $pitch = $backup = $attributes = $attributes_key = $changed_attributes = $forward = $time_modification = FALSE;
			$actual_notes = $normal_notes = $alter = $duration_measure = 0;
			$part = $measure = $step = -1;
			$s = array();
			$file = fopen($tmpFile,"r");
			$score_part = '';
			$instrument_name = $midi_channel = $divisions = $fifths = $mode = $duration_part = array();
			while(!feof($file)) {
				$line = fgets($file);
				if(is_integer($pos=strpos($line,"<score-partwise")) AND $pos == 0) $partwise = TRUE;
				if(is_integer($pos=strpos($line,"<score-timewise")) AND $pos == 0) $timewise = TRUE;
				if(is_integer($pos=strpos($line,"<score-part "))) {
					$score_part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
					}
				if(is_integer($pos=strpos($line,"</score-part>"))) {
					$message .= "Score part ‘".$score_part."’ instrument = <i>".$instrument_name[$score_part]."</i>";
					if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '')
						$message .= " — MIDI channel ".$midi_channel[$score_part];
					$message .= "<br />";
					$score_part = '';
					}
				if($score_part <> '' AND is_integer($pos=strpos($line,"<instrument-name>"))) {
					$instrument_name[$score_part] = trim(preg_replace("/<instrument\-name>(.+)<\/instrument\-name>/u","$1",$line));
					}
				if($score_part <> '' AND is_integer($pos=strpos($line,"<midi-channel>"))) {
					$midi_channel[$score_part] = trim(preg_replace("/<midi\-channel>([0-9]+)<\/midi\-channel>/u","$1",$line));
					}
				if($partwise AND is_integer($pos=strpos($line,"<part "))) {
					$measure = -1;
					$part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
					}
				if(is_integer($pos=strpos($line,"<attributes>"))) {
					$attributes = TRUE;
					$changed_attributes = FALSE;
					}
				if($attributes AND is_integer($pos=strpos($line,"<divisions>"))) {
					$divisions[$part] = trim(preg_replace("/<divisions>([0-9]+)<\/divisions>/u","$1",$line));
					$changed_attributes = TRUE;
					}
				if($attributes AND is_integer($pos=strpos($line,"<key>"))) {
					$attributes_key =  TRUE;
					}
				if($attributes_key AND is_integer($pos=strpos($line,"<fifths>"))) {
					$fifths[$part] = trim(preg_replace("/<fifths>(.+)<\/fifths>/u","$1",$line));
					$changed_attributes = TRUE;
					}
				if($attributes_key AND is_integer($pos=strpos($line,"<mode>"))) {
					$mode[$part] = trim(preg_replace("/<mode>(.+)<\/mode>/u","$1",$line));
					$changed_attributes = TRUE;
					}
				if($attributes AND is_integer($pos=strpos($line,"</key>"))) {
					$attributes_key =  FALSE;
					}
				if(is_integer($pos=strpos($line,"</attributes>"))) {
					$attributes = FALSE;
					if($changed_attributes AND !is_integer($pos=strpos($part,"_@"))) {
						if(isset($divisions[$part]) AND $divisions[$part] > 0) {
							$message .= "Part ‘".$part."’ divisions = ".$divisions[$part];
							}
						if(isset($fifths[$part]) AND $fifths[$part] <> 0) {
							$message .= ", fifths = ".$fifths[$part];
							}
						if(isset($mode[$part]) AND $mode[$part] <> '') {
							$message .= ", mode = ".$mode[$part];
							}
						$message .= "<br />";
						}
					}
				if(is_integer($pos=strpos($line,"<measure "))) {
					$step = -1; $level = 0; $duration_measure = 0;
					$measure = trim(preg_replace("/.*number=\"([0-9]+)\".*/u","$1",$line));
			//		echo "measure = ".$measure."<br />";
					}
				if(is_integer($pos=strpos($line,"</measure>"))) {
					$duration_part[$part] = $duration_measure;
					$duration_measure = 0;
			//		echo "duration_measure = ".$duration_measure."<br /><br />";
					}
				if(is_integer($pos=strpos($line,"<backup>"))) {
					$backup = TRUE;
					if(isset($midi_channel[$part]) AND $midi_channel[$part] > 0)
						$chan = $midi_channel[$part];
					else $chan = 0;
					if(isset($divisions[$part]) AND $divisions[$part] > 0)
						$div = $divisions[$part];
					else $div = 0;
					$duration_part[$part] = $duration_measure;
			/*		if(isset($duration_part[$part]) AND $duration_part[$part] > 0)
						$dp = $duration_part[$part];
					else $dp = 0; */
					$part .= "_@";
					$midi_channel[$part] = $chan;
					$divisions[$part] = $div;
				//	$duration_part[$part] = $dp;
					$duration_measure = 0;
					}
				if($backup AND is_integer($pos=strpos($line,"<duration>"))) {
					$duration_part[$part] = trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line));
					}
				if(is_integer($pos=strpos($line,"</backup>"))) {
					$backup = FALSE;
					}
				if(is_integer($pos=strpos($line,"<forward>"))) {
					$step++;
					$forward = TRUE;
					}
				if(is_integer($pos=strpos($line,"</forward>"))) {
					$forward = FALSE;
					$s[$measure][$part][$step]['note'] = "rest";
					$s[$measure][$part][$step]['duration'] = $note_duration;
					if($level == 0) $duration_measure += $note_duration;
					$s[$measure][$part][$step]['level'] = $level;
					$s[$measure][$part][$step]['alter'] = 0;
					$s[$measure][$part][$step]['octave'] = 0;
					}
				if(is_integer($pos=strpos($line,"<note "))) {
					if(!$partwise) {
						$message .= "<font color=\"red\">➡ </font> Could not convert this file because it is not in ‘partwise’ format<br />";
						break;
						}
					$step++;
					$note_on = TRUE;
					$is_chord = $rest = FALSE;
					}
				if($note_on AND is_integer($pos=strpos($line,"<chord/>"))) {
					$level++;
					$is_chord = TRUE;
					}
				if($note_on AND is_integer($pos=strpos($line,"<rest/>"))) {
					$rest = TRUE;
					$is_chord = FALSE;
					}
				if($note_on AND is_integer($pos=strpos($line,"<grace/>"))) {
					$note_duration = 0;
					}
				if(($note_on OR $forward) AND is_integer($pos=strpos($line,"<duration>"))) {
					$note_duration = trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line));
					}
				if($note_on AND is_integer($pos=strpos($line,"<time-modification>"))) {
					$time_modification = TRUE;
					}
				if($time_modification AND is_integer($pos=strpos($line,"<actual-notes>"))) {
					$actual_notes = trim(preg_replace("/<actual\-notes>([0-9]+)<\/actual\-notes>/u","$1",$line));
					}
				if($time_modification AND is_integer($pos=strpos($line,"<normal-notes>"))) {
					$normal_notes = trim(preg_replace("/<normal\-notes>([0-9]+)<\/normal\-notes>/u","$1",$line));
					}
				if($note_on AND is_integer($pos=strpos($line,"</time-modification>"))) {
					$time_modification = FALSE;
					}
				if($note_on AND is_integer($pos=strpos($line,"<pitch>"))) {
					$pitch = TRUE;
					if(!$is_chord) $level = 0;
					$alter = 0;
					}
				if($note_on AND is_integer($pos=strpos($line,"</pitch>"))) {
					$pitch = FALSE;
					}
				if($pitch AND is_integer($pos=strpos($line,"<step>"))) {
					$this_note = trim(preg_replace("/<step>(.+)<\/step>/u","$1",$line));
					}
				if($pitch AND is_integer($pos=strpos($line,"<octave>"))) {
					$this_octave = trim(preg_replace("/<octave>(.+)<\/octave>/u","$1",$line));
					}
				if($pitch AND is_integer($pos=strpos($line,"<alter>"))) {
					$alter = trim(preg_replace("/<alter>(.+)<\/alter>/u","$1",$line));
					}
				if($note_on AND is_integer($pos=strpos($line,"</note>"))) {
					if($rest) $s[$measure][$part][$step]['note'] = "rest";
					else $s[$measure][$part][$step]['note'] = $this_note;
					$s[$measure][$part][$step]['octave'] = $this_octave;
					$s[$measure][$part][$step]['duration'] = $note_duration;
					if($level == 0) $duration_measure += $note_duration;
					$s[$measure][$part][$step]['level'] = $level;
					$s[$measure][$part][$step]['actual-notes'] = $actual_notes;
					$s[$measure][$part][$step]['normal-notes'] = $normal_notes;
					$s[$measure][$part][$step]['alter'] = $alter;
					$note_on = $rest = FALSE;
					$actual_notes = $normal_notes = 0;
					}
				}
		/*	echo "<pre>";
			print_r($s);
			echo "</pre>"; */
			fclose($file);
			foreach($s as $i_measure => $the_measure) {
				$data .= "{";
				$newpart = TRUE;
				foreach($the_measure as $score_part => $the_part) {
					if(!$newpart) $data .= ",";
					if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] > 0)
					$data .= "_chan(".$midi_channel[$score_part].")";
					if(isset($duration_part[$score_part]))
						$num = $duration_part[$score_part];
					else $num = 0;
					$num_this_part = $num;
					$den = $divisions[$score_part];
					if($num > 0) {
						$gcd = gcd($num,$den);
						$num = $num / $gcd;
						$den = $den / $gcd;
						}
					if($num == 1 AND $den == 1) $fraction = "-";
					else $fraction = $num."/".$den;
					if(is_integer($pos=strpos($score_part,"_@"))) {
						$num = $num_this_part - $duration_part[$score_part];
					//	$data .= " gap(".$num_this_part."-".$duration_part[$score_part]."=".$num.") ";
						if($num > 0) {
							$gcd = gcd($num,$den);
							$num = $num / $gcd;
							$den = $den / $gcd;
							if($num == 1 AND $den == 1) $fraction = "-";
							$data .= " @".$fraction." ";
							}
						}
					$data .= "{".$fraction.",";
					$old_level = 0;
					$stream = ''; $chord = FALSE;
					foreach($the_part as $i_event => $the_event) {
						$level = $the_event['level'];
						$num  = $the_event['duration'];
						$den = $divisions[$score_part];
						if($num > 0) {
							$gcd = gcd($num,$den);
							$num = $num / $gcd;
							$den = $den / $gcd;
							}
						else $num = 1; // Grace note
						if($num == 1 AND $den == 1) $fraction = "-";
						else $fraction = $num."/".$den;
						$alter = $the_event['alter'];
						$the_note = $the_event['note'];
						$octave = $the_event['octave'];
						if($alter <> 0 AND $the_event['note'] <> "rest") {
							if($alter == 1) $the_note .= "#";
							if($alter == -1) $the_note .= "b";
							}
						if($the_event['note'] == "rest") {
							if($old_level > 0) for($i = 0; $i < $old_level; $i++) $stream .= "}";
							$stream .= " ".$fraction." ";
							if($old_level > 0) for($i = 0; $i < $old_level; $i++) $stream .= "{";
							}
						else {
							if($level > 0) {
								$stream .= ","; $chord = TRUE;
								}
							else {
								if($old_level > 0) $stream .= "}{";
								}
					//		$stream .= $i_level."_".$the_event." ";
					//		$stream .= $score_part."_".$the_event." ";
					//		$stream .= $i_measure."_".$the_event." ";
							if($the_event['actual-notes'] > 0) $stream .= "_tempo(".$the_event['actual-notes'];
							if($the_event['normal-notes'] > 0) $stream .= "/".$the_event['normal-notes'].")";
							$stream .= "{".$fraction.",".$the_note.$octave."}";
					//		$stream .= $the_event['note']."(".$the_event['duration'].")";
					//		$stream .= " ";
							}
						$old_level = $level;
						}
					if($chord) $stream = "{".$stream."}";
					$data .= $stream;
					$newpart = FALSE;
					$data .= "}";
					}
				$data .= "}";
				}
			$data = preg_replace("/\s+/u"," ",$data);
			$data = str_replace(" }","}",$data);
			$data = str_replace("} ","}",$data);
			$data = str_replace(" {","{",$data);
			$data = str_replace("{ ","{",$data);
			do $data = str_replace("{}",'',$data,$count);
			while($count > 0);
			$data = str_replace(" ,",",",$data);
			$data = "// MusicXML file ‘".$upload_filename."’ converted\n\n".$data;
			echo "<h3 id=\"timespan2\"><font color=\"red\">Converted MusicXML file:</font> <font color=\"blue\">".$upload_filename."</font></h3>";
			if($message <> '') echo $message;
			$_POST['savethisfile'] = TRUE;
			$_POST['thistext'] = $data;
			}
		}
	}
unset($_FILES['music_xml_import']);


if(isset($_POST['savethisfile'])) {
	echo "<p id=\"timespan\" style=\"color:red;\">Saved file…</p>";
	$content = $_POST['thistext'];
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	do $content = str_replace("  ",' ',$content,$count);
	while($count > 0);
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$objects_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$alphabet_file = $extract_data['alphabet'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];

echo "<table style=\"background-color:white;\"><tr>";
echo "<td id=\"topedit\">";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";

echo "<div style=\"float:right;\">Import MusicXML file: <input type=\"file\" name=\"music_xml_import\">&nbsp;<input type=\"submit\" value=\" send \"></div>";

echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";

echo "<textarea name=\"thistext\" rows=\"40\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";

display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
echo "</td><td>";
echo "<table style=\"background-color:Gold;\">";
$table = explode(chr(10),$content);
$imax = count($table);
for($i = $j = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$line = preg_replace("/\[.*\]/u",'',$line);
	$line = preg_replace("/^i[0-9].*/u",'',$line); // Csound note statement
	$line = preg_replace("/^f[0-9].*/u",'',$line); // Csound table statement
	$line = preg_replace("/^t[ ].*/u",'',$line); // Csound tempo statement
	$line = preg_replace("/^s\s*$/u",'',$line); // Csound "s" statement
	$line = preg_replace("/^e\s*$/u",'',$line); // Csound "e" statement
	if($line == '') continue;
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
	$line_recoded = recode_tags($line);
	$j++;
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
	echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
	echo "<input type=\"hidden\" name=\"objects_file\" value=\"".$objects_file."\">";
	echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
	echo "<tr id=\"".$i."\"><td>".$j."</td><td>";
	echo "<input type=\"hidden\" name=\"i\" value=\"".$i."\">";
	echo "<input type=\"hidden\" name=\"line\" value=\"".$line."\">";
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"playitem\" value=\"PLAY\"title=\"Play this polymetric expression\">&nbsp;";
	echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"expanditem\" value=\"EXP\" title=\"Expand this polymetric expression\">&nbsp;";
	echo "<input type=\"hidden\" name=\"imax\" value=\"".$imax."\">";
	echo "</form><small>";
	echo $line_recoded;
	echo "</small></td></tr>";
	}
echo "</table>";
echo "</td></tr>";
echo "</table>";
?>
