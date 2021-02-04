<?php
require_once("_basic_tasks.php");
require_once("_settings.php");

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

$test_musicxml = FALSE;

echo "<div style=\"float:right; background-color:white; padding-right:6px; padding-left:6px;\">";
$csound_is_responsive = check_csound();
echo "</div>";
echo "<h3>Data file “".$filename."”</h3>";

$temp_folder = str_replace(' ','_',$filename)."_".session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
$music_xml_file = $temp_dir.$temp_folder.SLASH."temp.musicxml";
$more_data = '';

$objects_file = $csound_file = $alphabet_file = $grammar_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = $csound_default_orchestra = '';

if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
if(isset($_POST['grammar_file'])) $grammar_file = $_POST['grammar_file'];
if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
if(isset($_POST['csound_file'])) $csound_file = $_POST['csound_file'];
if(isset($_POST['objects_file'])) $objects_file = $_POST['objects_file'];

if(isset($_POST['select_parts'])) {
	$upload_filename = $_POST['upload_filename'];
	$reload_musicxml = TRUE;
	}
else $reload_musicxml = FALSE;

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";

if($reload_musicxml OR (isset($_FILES['music_xml_import']) AND $_FILES['music_xml_import']['tmp_name'] <> '')) {
	if(!$reload_musicxml) $upload_filename = $_FILES['music_xml_import']['name'];
	if(!$reload_musicxml AND $_FILES["music_xml_import"]["size"] > MAXFILESIZE) {
		echo "<h3><font color=\"red\">Uploading failed:</font> <font color=\"blue\">".$upload_filename."</font> <font color=\"red\">is larger than ".MAXFILESIZE." bytes</font></h3>";
		}
	else {
		// First we save current content of window
		$save_content = $content = $_POST['thistext'];
		if(/*$reload_musicxml AND */ $more_data <> '') $save_content = $more_data."\n\n".$save_content;
		$handle = fopen($this_file,"w");
		$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
		do $save_content = str_replace("  ",' ',$save_content,$count);
		while($count > 0);
		fwrite($handle,$file_header."\n");
		fwrite($handle,$save_content);
		fclose($handle);
		
	//	$content = $_POST['thistext'];
		$content = str_replace(chr(13).chr(10),chr(10),$content);
		$content = str_replace(chr(13),chr(10),$content);
		$declarations = '';
		if($objects_file <> '') {
			$declarations .= $objects_file."\n";
			$content = str_replace($objects_file,'',$content);
			}
		if($grammar_file <> '') {
			$declarations .= $grammar_file."\n";
			$content = str_replace($grammar_file,'',$content);
			}
		if($settings_file <> '') {
			$declarations .= $settings_file."\n";
			$content = str_replace($settings_file,'',$content);
			}
		if($csound_file <> '') {
			$declarations .= $csound_file."\n";
			$content = str_replace($csound_file,'',$content);
			}
		if($objects_file <> '') {
			$declarations .= $objects_file."\n";
			$content = str_replace($objects_file,'',$content);
			}
		$_POST['thistext'] = $content;
		if(!$reload_musicxml) {
			$tmpFile = $_FILES['music_xml_import']['tmp_name'];
			move_uploaded_file($tmpFile,$music_xml_file) or die('Problem uploading this MusicXML file');
			@chmod($music_xml_file,0666);
			$table = explode('.',$upload_filename);
			$extension = end($table);
			}
		if(!$reload_musicxml AND $extension <> "musicxml" AND $extension <> "xml") {
			echo "<h4><font color=\"red\">Uploading failed:</font> <font color=\"blue\">".$upload_filename."</font> <font color=\"red\">does not have the extension of a MusicXML file!</font></h4>";
			}
		else {
			$message = '';
			$data = $score_part = $subtitle_part = '';
			$max_measure = 0;
			$partwise = $timewise = $attributes = $attributes_key = $changed_attributes = FALSE;
			$instrument_name = $midi_channel = $select_part = $duration_part = $divisions = array();
			
			$new_method = FALSE;
			$new_method = TRUE;
			
			if($new_method) {
				$this_score = array();
				$part = $measure = -1;
				$reading_measure = FALSE;
				$file = fopen($music_xml_file,"r");
				while(!feof($file)) {
					$line = fgets($file);
					if(is_integer($pos=strpos($line,"<score-partwise")) AND $pos == 0) $partwise = TRUE;
					if(is_integer($pos=strpos($line,"<score-timewise")) AND $pos == 0) $timewise = TRUE;
					if(is_integer($pos=strpos($line,"<score-part "))) {
						$score_part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
						}
					if(is_integer($pos=strpos($line,"</score-part>"))) {
						$part_selection = "select_part_".$score_part;
						$select_part[$score_part] = isset($_POST[$part_selection]);
						$message .= "<input type=\"checkbox\" name=\"".$part_selection."\"";
						if($select_part[$score_part]) {
							$message .= " checked";
							echo "Score part ‘".$score_part."’ has been selected.<br />";
							}
						$message .= "> Score part ‘".$score_part."’ instrument = <i>".$instrument_name[$score_part]."</i>";
						if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
							$message .= " — MIDI channel ".$midi_channel[$score_part];
							}
						$message .= "<br />";
						if($select_part[$score_part] OR !$reload_musicxml) {
							$subtitle_part .= "// Score part ‘".$score_part."’: instrument = ".$instrument_name[$score_part];
							if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
								$subtitle_part .= " — MIDI channel ".$midi_channel[$score_part];
								}
							$subtitle_part .= "\n";
							}
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
						if(FALSE AND $changed_attributes) {
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
						$reading_measure = TRUE;
						$measure = trim(preg_replace("/.*number=\"([0-9]+)\".*/u","$1",$line));
						$this_score[$measure][$part] = array();
						if($test_musicxml) echo "Part ".$part." measure #".$measure."<br />";
						}
					if($reading_measure AND is_integer($pos=strpos($line,"</measure>"))) {
						$reading_measure = FALSE;
						}
					if($reading_measure) {
						$this_score[$measure][$part][] = $line;
						}
					}
				fclose($file);
				$convert_score = convert_musicxml($this_score,$divisions,$midi_channel,$select_part,$reload_musicxml,$test_musicxml);
				$data .= $convert_score['data'];
				$message .= $convert_score['error'];
				}
			
			// Old method, may be deleted	
			else {
				$note_on = $pitch = $backup = $forward = $time_modification = $told_fraction1 = $told_fraction2  = $told_fraction3 = FALSE;
				$actual_notes = $normal_notes = $alter = $duration_measure = $note_duration = 0;
				$part = $measure = $step = -1;
				$s = array();
				$this_octave = $this_note = '';
				$divisions = $fifths = $mode = array();
				$file = fopen($music_xml_file,"r");
				while(!feof($file)) {
					$line = fgets($file);
					if(is_integer($pos=strpos($line,"<score-partwise")) AND $pos == 0) $partwise = TRUE;
					if(is_integer($pos=strpos($line,"<score-timewise")) AND $pos == 0) $timewise = TRUE;
					if(is_integer($pos=strpos($line,"<score-part "))) {
						$score_part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
						}
					if(is_integer($pos=strpos($line,"</score-part>"))) {
						$part_selection = "select_part_".$score_part;
						$select_part[$score_part] = isset($_POST[$part_selection]);
						$message .= "<input type=\"checkbox\" name=\"".$part_selection."\"";
						if($select_part[$score_part]) {
							$message .= " checked";
							echo "Score part ‘".$score_part."’ has been selected.<br />";
							}
						$message .= "> Score part ‘".$score_part."’ instrument = <i>".$instrument_name[$score_part]."</i>";
						if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
							$message .= " — MIDI channel ".$midi_channel[$score_part];
							}
						$message .= "<br />";
						if($select_part[$score_part] OR !$reload_musicxml) {
							$subtitle_part .= "// Score part ‘".$score_part."’: instrument = ".$instrument_name[$score_part];
							if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
								$subtitle_part .= " — MIDI channel ".$midi_channel[$score_part];
								}
							$subtitle_part .= "\n";
							}
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
						if($test_musicxml) echo "measure #".$measure."<br />";
						}
					if(is_integer($pos=strpos($line,"</measure>"))) {
						$duration_part[$part] = $duration_measure;
						if($test_musicxml) echo "duration_measure = ".$duration_measure." level = ".$level."<br /><br />";
						$duration_measure = 0;
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
						$part .= "_@";
						$midi_channel[$part] = $chan;
						$divisions[$part] = $div;
						$duration_measure = 0;
						$step = -1;
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
					if(is_integer($pos=strpos($line,"<note ")) OR is_integer($pos=strpos($line,"<note>"))) {
						if(!$partwise) {
							$message .= "<font color=\"red\">➡ </font> Could not convert this file because it is not in ‘partwise’ format<br />";
							break;
							}
						$step++;
						$note_on = TRUE;
						$is_chord = $rest = FALSE;
						$note_duration = 0;
					//	$s[$measure][$part][$step]['note'] = '';
						}
					if($note_on AND is_integer($pos=strpos($line,"<chord/>"))) {
						$level++;
						$is_chord = TRUE;
						}
					if($note_on AND (is_integer($pos=strpos($line,"<rest ")) OR is_integer($pos=strpos($line,"<rest/>")) OR is_integer($pos=strpos($line,"<rest>")))) {
						$rest = TRUE;
						$is_chord = FALSE;
						$this_octave = 0;
						if($test_musicxml) echo "rest<br />";
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
					//	if($test_musicxml) echo " ".$step." ".$this_note.$this_octave." (".$note_duration.")<br />";
						if($level == 0) $duration_measure += $note_duration;
						$s[$measure][$part][$step]['level'] = $level;
						$s[$measure][$part][$step]['actual-notes'] = $actual_notes;
						$s[$measure][$part][$step]['normal-notes'] = $normal_notes;
						$s[$measure][$part][$step]['alter'] = $alter;
						$note_on = $rest = FALSE;
						$actual_notes = $normal_notes = 0;
						}
					}
				fclose($file);
				ksort($s);
				foreach($s as $i_measure => $the_measure) {
					if($test_musicxml) echo "Measure ".$i_measure."<br />";
					if($i_measure > $max_measure) $max_measure = $i_measure;
					$data .= "{";
					$newpart = TRUE;
					ksort($the_measure);
					foreach($the_measure as $score_part => $the_part) {
						$score_part_root = str_replace("_@",'',$score_part);
						if($reload_musicxml AND !$select_part[$score_part_root]) continue;
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
						if($num == 1 AND $den == 1) $fraction = "1";
						else if($den == 1) $fraction = $num;
						else $fraction = $num."/".$den;
						if(is_integer($pos=strpos($score_part,"_@"))) {
							$num = $num_this_part - $duration_part[$score_part];
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
						ksort($the_part);
						foreach($the_part as $i_event => $the_event) {
							$level = $the_event['level'];
							$num  = $the_event['duration'];
							$den = $divisions[$score_part];
							if($num > 0) {
								$gcd = gcd($num,$den);
								$num = $num / $gcd;
								$den = $den / $gcd;
								}
							else {
								// Grace note. We allow it the minimum duration and manage to squeeze all notes in the current measure
								$num = $duration_part[$score_part];
								$den = $divisions[$score_part] * 32;
								$gcd = gcd($num,$den);
								$num = $num / $gcd;
								$den = $den / $gcd;
								if($den > 32) { // Approximation is required to avoid overflowing Polyexpand()
									$num = round(($num * 32) / $den);
									if($num == 0) $num = 1;
									$den = 32;
									}
								}
							if($num == 1 AND $den == 1) $fraction = "1";
							else if($den == 1) $fraction = $num;
							else $fraction = $num."/".$den;
							$alter = $the_event['alter'];
							$the_note = $the_event['note'];
							$octave = $the_event['octave'];
							if($alter <> 0 AND $the_event['note'] <> "rest") {
								if($alter == 1) $the_note .= "#";
								if($alter == -1) $the_note .= "b";
								}
							if($the_event['note'] == "rest") {
								if($fraction == "1") $fraction = "-";
								if($fraction == "2") $fraction = "--";
								if($fraction == "3") $fraction = "---";
								$simplify = simplify($fraction,$max_term_in_fraction);
								if($simplify['done'] AND !$told_fraction1) {
									$message .=  "<font color=\"red\">➡</font> Simplified fraction (1) ".$fraction." to ‘".$simplify['fraction']."’ (and maybe more)<br />";
									$told_fraction1 = TRUE;
									}
								$fraction = $simplify['fraction'];
								if($fraction <> '') {
									if($old_level > 0) for($i = 0; $i < $old_level; $i++) $stream .= "}";
									$stream .= " ".$fraction." ";
									if($old_level > 0) for($i = 0; $i < $old_level; $i++) $stream .= "{";
									}
								}
							else {
								if($level > 0) {
									$stream .= ","; $chord = TRUE;
									}
								else {
									if($old_level > 0) $stream .= "}{";
									}
								if($the_note == '') $the_note = "-";
								if($the_note == "-") $octave = '';
								if($the_event['actual-notes'] > 0) {
									$stream .= "_tempo(".$the_event['actual-notes'];
									if($the_event['normal-notes'] > 0) $stream .= "/".$the_event['normal-notes'];
									$stream .= ")";
									$simplify = simplify($fraction,$the_event['actual-notes']);
									if($simplify['fraction'] == '') $simplify['fraction'] = "1/".$the_event['actual-notes'];
									if($simplify['done'] AND !$told_fraction2) {
										$message .=  "<font color=\"red\">➡</font> Simplified fraction (2) ".$fraction." to ‘".$simplify['fraction']."’ (and maybe more)<br />";
										$told_fraction2 = TRUE;
										}
									}
								else {
									$simplify = simplify($fraction,$max_term_in_fraction);
									if($simplify['done'] AND !$told_fraction3) {
										$message .=  "<font color=\"red\">➡</font> Simplified fraction (3) ".$fraction." to ‘".$simplify['fraction']."’ (and maybe more)<br />";
										$told_fraction3 = TRUE;
										}
									}
								$fraction = $simplify['fraction'];
								if($fraction <> '') $stream .= "{".$fraction.",".$the_note.$octave."}";
								$the_note = $octave = '';
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
				}
				
			$data = preg_replace("/\s+/u"," ",$data);
			$data = str_replace(" }","}",$data);
			$data = str_replace(",}","}",$data);
			$data = str_replace("} ","}",$data);
			$data = str_replace(" {","{",$data);
			$data = str_replace("{ ","{",$data);
			$data = str_replace(", ",",",$data);
			$data = str_replace("- -","--",$data);
			do $data = str_replace("{}",'',$data,$count);
			while($count > 0);
			$data = str_replace(" ,",",",$data);
			if($reload_musicxml) {
				$more_data = "// MusicXML file ‘".$upload_filename."’ converted\n";
				if($subtitle_part <> '') $more_data .= $subtitle_part."\n";
				}
			$more_data .= $declarations;
			if(isset($_POST['delete_current'])) $_POST['thistext'] = '';
			$more_data .= "\n".$data;
			echo "<h3><font color=\"red\">Converting MusicXML file:</font> <font color=\"blue\">".$upload_filename."</font></h3>";
			if($message <> '') echo $message;
			echo "<input type=\"checkbox\" name=\"delete_current\">&nbsp;delete current data<br />";
			echo "<input type=\"hidden\" name=\"upload_filename\" value=\"".$upload_filename."\">";
			echo "<font color=\"red\">➡</font> You can select parts and <input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"select_parts\" value=\"CONVERT THEM\">&nbsp;<input style=\"background-color:azure;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"cancel\" value=\"QUIT IMPORTING\">";
			$_POST['savethisfile'] = TRUE;
			}
		}
	}
unset($_FILES['music_xml_import']);

if(isset($_POST['explode'])) {
	$content = $_POST['thistext'];
	$table = explode(chr(10),$content);
	$newtable = array();
	$imax = count($table);
	$item = 1;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$newline = $line;
		if(substr_count($line,'{') > 0) {
			$newline = '';
			$level = 0;
			for($j = 0; $j < strlen($line); $j++) {
				$c = $line[$j];
				if($c == '{') {
					if($item == 1 AND $level == 0) $newline .= "[item ".($item++)."] ";
					$level++;
					}
				$newline .= $c;
				if($c == '}') {
					$level--;
					if($level == 0) $newline .= "\n\n[item ".($item++)."] ";
					}
				}
			}
		$newtable[] = $newline;
		}
	$newcontent = implode("\n",$newtable);
	$newcontent = str_replace("[item ".($item-1)."]",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$_POST['savethisfile'] = TRUE;
	}

if(isset($_POST['implode'])) {
	$content = $_POST['thistext'];
	$content = str_replace("\r\n","\n",$content);
	do $content = str_replace("} ","}",$content,$count);
	while($count > 0);
	do $content = str_replace(" {","{",$content,$count);
	while($count > 0);
	do $content = str_replace("}\n\n","}\n",$content,$count);
	while($count > 0);
	$content = preg_replace("/\[item\s[0-9]+\]\s*/u",'',$content);
	$content = preg_replace("/}\s{/u","} {",$content);
	$_POST['thistext'] = $content;
	$_POST['savethisfile'] = TRUE;
	}

if(isset($_POST['savethisfile'])) {
	echo "<p id=\"timespan\" style=\"color:red;\">Saved file…</p>";
	$content = $_POST['thistext'];
	if($more_data <> '') $content = $more_data."\n\n".$content;
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
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$alphabet_file = $extract_data['alphabet'];
$grammar_file = $extract_data['grammar'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];

echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
echo "<input type=\"hidden\" name=\"grammar_file\" value=\"".$grammar_file."\">";
echo "<input type=\"hidden\" name=\"objects_file\" value=\"".$objects_file."\">";

if(!isset($output_folder) OR $output_folder == '') $output_folder = "my_output";
$default_output_name = str_replace("-da.",'',$filename);
$default_output_name = str_replace(".bpda",'',$default_output_name);
$file_format = $default_output_format;
if(isset($data_file_format[$filename])) $file_format = $data_file_format[$filename];
if(isset($_POST['file_format'])) $file_format = $_POST['file_format'];
save_settings2("data_file_format",$filename,$file_format); // To _settings.php
$output_file = $default_output_name;
if(isset($_POST['output_file'])) $output_file = $_POST['output_file'];
$output_file = str_replace(".mid",'',$output_file);
$output_file = str_replace(".sco",'',$output_file);
switch($file_format) {
	case "midi": $output_file = $output_file.".mid"; break;
	case "csound": $output_file = $output_file.".sco"; break;
	default: $output_file = ''; break;
	}
$project_name = preg_replace("/\.[a-z]+$/u",'',$output_file);
$result_file = $bp_application_path.$output_folder.SLASH.$project_name."-result.html";

if($csound_file <> '') {
	$found_orchestra_in_instruments = FALSE;
	if(file_exists($dir.$csound_file))
		rename($dir.$csound_file,$dir_csound_resources.$csound_file);
	$csound_orchestra = get_orchestra_filename($dir_csound_resources.$csound_file);
	if($csound_orchestra <> '') $found_orchestra_in_instruments = TRUE;
	else $csound_orchestra = $csound_default_orchestra;
	if($csound_orchestra <> '' AND file_exists($dir.$csound_orchestra)) {
		rename($dir.$csound_orchestra,$dir_csound_resources.$csound_orchestra);
		sleep(1);
		}
	check_function_tables($dir,$csound_file);
	if($file_format == "csound") {
		$list_of_tonal_scales = list_of_tonal_scales($dir_csound_resources.$csound_file);
		if(($max_scales = count($list_of_tonal_scales)) > 0) {
			if($max_scales > 1) echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of tonal scales:";
			else echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains the definition of tonal scale(s):";
			echo "<ul style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_scale = 1; $i_scale <= $max_scales; $i_scale++)
				echo "<li>".$list_of_tonal_scales[$i_scale - 1]."</li>";
			if($max_scales > 1) echo "</ul>These scales may be called in “_scale(name of scale, blockkey)” instructions";
			else echo "</ul>This scale may be called in a “_scale(name of scale, blockkey)” instruction<br />but it will also be used by default in replacement of the equal-tempered scale<br />➡ Use “_scale(0,0)” to force equal-tempered";
			echo "</p>";
			}
		$list_of_instruments = list_of_instruments($dir_csound_resources.$csound_file);
		$list = $list_of_instruments['list'];
		if(($max_instr = count($list)) > 0) {
			if($max_scales > 0) echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> also contains definitions of instrument(s):";
			else echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of instrument(s):";
			echo "<ol style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_instr = 0; $i_instr < $max_instr; $i_instr++) {
				echo "<li><b>_ins(</b><font color=\"MediumTurquoise\">".$list[$i_instr]."</font><b>)</b>";
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
		}
	else {
		echo "<p>Csound resources have been loaded but cannot be used because the output format is not “CSOUND”.<br />";
		echo "➡ Instructions “_scale()” and “_ins()” will be ignored</p>";
		}
	}

echo "<table id=\"topedit\" cellpadding=\"8px;\"><tr style=\"background-color:white;\">";
echo "<td><p>Name of output file (with proper extension):<br /><input type=\"text\" name=\"output_file\" size=\"25\" value=\"".$output_file."\">&nbsp;";
echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE\"></p>";
echo "</td>";
echo "<td><p style=\"text-align:left;\">";
echo "<input type=\"radio\" name=\"file_format\" value=\"\"";
if($file_format == "") echo " checked";
echo ">No file (real-time MIDI)";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"midi\"";
if($file_format == "midi") echo " checked";
echo ">MIDI file";
echo "<br /><input type=\"radio\" name=\"file_format\" value=\"csound\"";
if($file_format == "csound") echo " checked";
echo ">CSOUND file";
echo "</p></td></tr></table>";

$error_mssg = $link_options = '';
if($grammar_file <> '') {
	if(!file_exists($dir.$grammar_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: ".$dir.$grammar_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_options .= "&grammar=".urlencode($dir.$grammar_file);
	}
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: ".$dir.$alphabet_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_options .= "&alphabet=".urlencode($dir.$alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: ".$dir.$settings_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_options .= "&settings=".urlencode($dir.$settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: ".$dir.$objects_file." not found.</font><br />";
		$error = TRUE;
		}
	else $link_options .= "&objects=".urlencode($dir.$objects_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir_csound_resources.$csound_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: ".$dir_csound_resources.$csound_file." not found.</font><br />";
		$error = TRUE;
		}
	else {
		$link_options .= "&csound_file=".urlencode($csound_file);
		if($file_format == "csound" AND file_exists($dir_csound_resources.$csound_orchestra)) $link_options .= "&csound_orchestra=".urlencode($csound_orchestra);
		}
	}
$link_options .= "&here=".urlencode($dir.$filename);

if($error_mssg <> '') echo "<p>".$error_mssg."</p>";

echo "<table style=\"background-color:GhostWhite;\" border=\"0\"><tr>";
echo "<td style=\"background-color:cornsilk;\">";

echo "<div style=\"float:right; vertical-align:middle;\">Import MusicXML file: <input type=\"file\" name=\"music_xml_import\">&nbsp;<input type=\"submit\" style=\"background-color:AquaMarine;\" value=\"← IMPORT\"></div>";

echo "<div style=\"text-align:left;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></div>";

echo "<br /><textarea name=\"thistext\" rows=\"40\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";

echo "<div style=\"text-align:right;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></div>";

display_more_buttons($content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
echo "</td><td style=\"background-color:cornsilk;\">";
echo "<table style=\"background-color:Gold;\">";
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > 0 AND substr_count($content,'{') > 0) {
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"thistext\" value=\"".$content."\">";
	echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
	echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
	echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
	echo "<input type=\"submit\" style=\"background-color:AquaMarine;\" formaction=\"".$url_this_page."#topedit\" name=\"explode\" value=\"EXPLODE\">&nbsp;<font color=\"red\">➡ </font><i>break</i> {…} <i>expressions</i>";
	echo "</td></tr>";
	if($imax > 4) {
		echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
		echo "<input type=\"submit\" style=\"background-color:AquaMarine;\" formaction=\"".$url_this_page."#topedit\" name=\"implode\" value=\"IMPLODE\">&nbsp;<font color=\"red\">➡ </font><i>merge</i> {…} <i>expressions</i>";
		echo "</td></tr>";
		}
	echo "</form>";
	}
for($i = $j = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$error_mssg = '';
	if(is_integer($pos=strpos($line,"[item ")) AND $pos == 0)
		$title_this = preg_replace("/\[item\s([^\]]+)\].*/u",'$1',$line);
	else $title_this = '';
	$line = preg_replace("/\[.*\]/u",'',$line);
	$line = preg_replace("/^i[0-9].*/u",'',$line); // Csound note statement
	$line = preg_replace("/^f[0-9].*/u",'',$line); // Csound table statement
	$line = preg_replace("/^t[ ].*/u",'',$line); // Csound tempo statement
	$line = preg_replace("/^s\s*$/u",'',$line); // Csound "s" statement
	$line = preg_replace("/^e\s*$/u",'',$line); // Csound "e" statement
	if($line == '') continue;
	if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
	$line_recoded = recode_entities($line);
	$j++;
	$data = $temp_dir.$temp_folder.SLASH.$j.".bpda";
	$handle = fopen($data,"w");
	fwrite($handle,$line_recoded."\n");
	fclose($handle);
	echo "<tr><td>".$j."</td><td>";
	$link_options .= "&item=".$j;
	$link_options_play = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file)."&format=".$file_format;
	$output_file_expand = str_replace(".sco",'',$output_file);
	$output_file_expand = str_replace(".mid",'',$output_file_expand);
	$output_file_expand .= ".bpda";
	$link_options_expand = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file_expand)."&format=data";
	$link_produce = "produce.php?data=".urlencode($data);
	$link_play = $link_produce."&instruction=play";
	$link_play .= $link_options_play;
	$link_expand = $link_produce."&instruction=expand";
	$link_expand .= $link_options_expand;
	$window_name = window_name($filename);
	$window_name_play = $window_name."_play";
	$window_name_expland = $window_name."_expland";
//	echo "<small>".urldecode($link_play)."</small><br />";
//	echo "<small>".urldecode($link_expand)."</small><br />";
	echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play."','".$window_name_play."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Play this polymetric expression\" value=\"PLAY\">&nbsp;";
	echo "&nbsp;<input style=\"background-color:azure;\" onclick=\"window.open('".$link_expand."','".$window_name_expland."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Expand this polymetric expression\" value=\"EXPAND\">&nbsp;";
	$n1 = substr_count($line_recoded,'{');
	$n2 = substr_count($line_recoded,'}');
	if($n1 > $n2) $error_mssg .= "<font color=\"red\">This score contains ".($n1-$n2)." extra ‘{'</font>";
	if($n2 > $n1) $error_mssg .= "<font color=\"red\">This score contains ".($n2-$n1)." extra ‘}'</font>";
	if($error_mssg <> '') echo "<p>".$error_mssg."</p>";
	$length = strlen($line_recoded);
	if($length > 400)
		$line_show = "<br />".substr($line_recoded,0,100)."<br />&nbsp;... ... ...<br />".substr($line_recoded,-100,100);
	else $line_show = $line_recoded;
	echo "<small>";
	if($title_this <> '') $line_show = "<b>[item ".$title_this."]</b> ".$line_show;
	echo $line_show;
	echo "</small></td></tr>";
	}
echo "</table>";
echo "</td></tr>";
echo "</table>";
?>
