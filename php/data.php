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
$link_edit = "data.php";

$objects_file = $csound_file = $alphabet_file = $grammar_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = $csound_default_orchestra = '';

if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
if(isset($_POST['grammar_file'])) $grammar_file = $_POST['grammar_file'];
if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
if(isset($_POST['csound_file'])) $csound_file = $_POST['csound_file'];
if(isset($_POST['objects_file'])) $objects_file = $_POST['objects_file'];

if(isset($_POST['new_convention']))
	$new_convention = $_POST['new_convention'];
else $new_convention = '';

if(isset($_POST['select_parts'])) {
	$upload_filename = $_POST['upload_filename'];
	$reload_musicxml = TRUE;
	}
else $reload_musicxml = FALSE;

$need_to_save = $error = FALSE;
$error_mssg = '';

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
			$score_part = '';
			$data = $subtitle_part = '';
			$max_measure = 0;
			$partwise = $timewise = $attributes = $attributes_key = $changed_attributes = FALSE;
			$add_section = TRUE;
			$instrument_name = $midi_channel = $select_part = $duration_part = $divisions = $repeat_section = array();
			$ignore_dynamics = isset($_POST['ignore_dynamics']);
			$ignore_tempo = isset($_POST['ignore_tempo']);
			$ignore_channels = isset($_POST['ignore_channels']);
			$section = 0; // This variable is used for repetitions, see forward/backward
			$repeat_section[$section] = 1; // By default, don't repeat
			$part = '';
			$i_measure = -1; $i_part = 0;
			$reading_measure = FALSE;
			$message_top = "_________________<br /><input type=\"checkbox\" id=\"parent1\" style=\"box-shadow: -2px -2px Gold\"> <b>Check all</b><br />";
			$message = ''; $first = TRUE;
			$file = fopen($music_xml_file,"r");
			while(!feof($file)) {
				$line = fgets($file);
				if(is_integer($pos=strpos($line,"<score-partwise")) AND $pos == 0) {
					$partwise = TRUE;
					continue;
					}
				if(is_integer($pos=strpos($line,"<score-timewise")) AND $pos == 0) {
					$timewise = TRUE;
					continue;
					}
				if(is_integer($pos=strpos($line,"<score-part "))) {
					$score_part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
					continue;
					}
				if(is_integer($pos=strpos($line,"</score-part>"))) {
					$i_part++;
					$part_selection = "select_part_".$score_part;
					if($reload_musicxml)
						$select_part[$score_part] = isset($_POST[$part_selection]);
					else
						$select_part[$score_part] = FALSE;
					$message .= "<input type=\"checkbox\" class=\"child1\" name=\"".$part_selection."\"";
					if($select_part[$score_part] OR (!$reload_musicxml AND $first)) {
						$message .= " checked";
						$first = FALSE;
					//	echo "<p>Score part ‘".$score_part."’ has been selected</p>";
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
					continue;
					}
				if($score_part <> '' AND is_integer($pos=strpos($line,"<instrument-name>"))) {
					$instrument_name[$score_part] = trim(preg_replace("/<instrument\-name>(.+)<\/instrument\-name>/u","$1",$line));
					continue;
					}
				if($score_part <> '' AND is_integer($pos=strpos($line,"<midi-channel>"))) {
					$midi_channel[$score_part] = trim(preg_replace("/<midi\-channel>([0-9]+)<\/midi\-channel>/u","$1",$line));
					continue;
					}
				if($partwise AND is_integer($pos=strpos($line,"<part "))) {
					$i_measure = -1;
					$part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
					continue;
					}
				if(is_integer($pos=strpos($line,"<attributes>"))) {
					$attributes = TRUE;
					$changed_attributes = FALSE;
					continue;
					}
				if($attributes AND is_integer($pos=strpos($line,"<divisions>"))) {
					$divisions[$part] = trim(preg_replace("/<divisions>([0-9]+)<\/divisions>/u","$1",$line));
					$changed_attributes = TRUE;
					continue;
					}
				if($attributes AND is_integer($pos=strpos($line,"<key>"))) {
					$attributes_key =  TRUE;
					continue;
					}
				if($attributes_key AND is_integer($pos=strpos($line,"<fifths>"))) {
					$fifths[$part] = trim(preg_replace("/<fifths>(.+)<\/fifths>/u","$1",$line));
					$changed_attributes = TRUE;
					continue;
					}
				if($attributes_key AND is_integer($pos=strpos($line,"<mode>"))) {
					$mode[$part] = trim(preg_replace("/<mode>(.+)<\/mode>/u","$1",$line));
					$changed_attributes = TRUE;
					continue;
					}
				if($attributes AND is_integer($pos=strpos($line,"</key>"))) {
					$attributes_key =  FALSE;
					continue;
					}
				if(is_integer($pos=strpos($line,"</attributes>"))) {
					$attributes = FALSE;
				/*	if($changed_attributes) {
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
						} */
					continue;
					}
				if(is_integer($pos=strpos($line,"<measure "))) {
					$reading_measure = TRUE;
				//	if(!is_integer($pos=strpos($line,"implicit=\"yes\""))) { // Not recommended
						$i_measure = trim(preg_replace("/.*number=\"([^\"]+)\".*/u","$1",$line));
						if($test_musicxml)
							echo "Part ".$part." measure #".$i_measure."<br />";
						if($add_section) {
							$section++;
							$this_score[$section][$i_measure] = array();
							$this_score[$section][$i_measure][$part] = array();
							$repeat_section[$section] = 1;
							}
						$add_section = FALSE;
				//		}
					}
				if($reading_measure AND is_integer($pos=strpos($line,"</measure>"))) {
					$reading_measure = FALSE;
					}
				if($reading_measure AND is_integer($pos=strpos($line,"<repeat "))) {
					$repeat_direction = trim(preg_replace("/.+direction=\"([^\"]+)\"\/>/u","$1",$line));
					if($test_musicxml) echo "repeat direction = “".$repeat_direction."” section ".$section."<br />";
					if($repeat_direction == "forward") {
						$section++;
						$this_score[$section][$i_measure] = array();
						$this_score[$section][$i_measure][$part] = array();
						$repeat_section[$section] = 1;
						$repeat_start_measure[$section] = $i_measure;
						$add_section = FALSE;
						}
					if($repeat_direction == "backward") {
						$repeat_section[$section] = 2;
						$add_section = TRUE;
						$repeat_end_measure[$section] = $i_measure;
					//	echo "• Section ".$section." repeat ".$repeat_section[$section]." time(s)<br />";
						}
					continue;
					}
				if($reading_measure AND is_integer($pos=strpos($line,"<note>"))) {
					}
					
				if($reading_measure) {
					$this_score[$section][$i_measure][$part][] = $line;
					}
				}
			fclose($file); $i_section =  0;
			foreach($this_score as $section => $the_section) {
				if(count($the_section) > 0) $i_section++;
				if(isset($repeat_start_measure[$section]) AND isset($repeat_end_measure[$section])) {
					echo "Section #".$i_section." is repeated from measure ".$repeat_start_measure[$section]." to ".$repeat_end_measure[$section]."<br />";
					}
				}
			unset($the_section);
			$convert_score = convert_musicxml($this_score,$repeat_section,$divisions,$midi_channel,$select_part,$ignore_dynamics,$ignore_tempo,$ignore_channels,$reload_musicxml,$test_musicxml);
			$data .= $convert_score['data'];
			$message .= $convert_score['error'];
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
			$data = preg_replace("/{0\/?[0-9]*}/u",'',$data); // Empty measure created by repetition, need to check why… Fixed by BB 2021-02-20
			$data = preg_replace("/{_tempo[^\)]+\)\s?_chan[^\)]+\)\s?}/u",'',$data); // Empty measure created by repetition, need to check why…
			if($reload_musicxml) {
				$more_data = "// MusicXML file ‘".$upload_filename."’ converted\n";
				if($subtitle_part <> '') $more_data .= $subtitle_part."\n";
				}
			$more_data .= $declarations;
			if(isset($_POST['delete_current'])) $_POST['thistext'] = '';
			$more_data .= "\n".$data;
			echo "<h3><font color=\"red\">Converting MusicXML file:</font> <font color=\"blue\">".$upload_filename."</font></h3>";
			if($i_part > 1) echo $message_top;
			if($message <> '') {
				echo $message;
				echo "_______________________________________<br />";
				echo "<input type=\"checkbox\" name=\"ignore_dynamics\">&nbsp;Ignore dynamics (volume)<br />";
				echo "<input type=\"checkbox\" name=\"ignore_tempo\">&nbsp;Ignore tempo<br />";
				echo "<input type=\"checkbox\" name=\"ignore_channels\">&nbsp;Ignore MIDI channels<br />";
				echo "_________________<br />";
				echo "<input type=\"checkbox\" name=\"delete_current\">&nbsp;Delete current data<br />";
				echo "_________________<br />";
				echo "<input type=\"hidden\" name=\"upload_filename\" value=\"".$upload_filename."\">";
				echo "<font color=\"red\">➡</font> You can select parts and <input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"select_parts\" value=\"CONVERT\">&nbsp;or&nbsp;<input style=\"background-color:azure;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"cancel\" value=\"QUIT IMPORTING\">";
				}
			$new_convention = 0; // English note convention
	//		$_POST['savethisfile'] = TRUE;
			$need_to_save = TRUE;
			}
		}
	}
unset($_FILES['music_xml_import']);

if(isset($_POST['explode'])) {
	$content = $_POST['thistext'];
	$content = str_replace("\r",chr(10),$content);
	do $content = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$content,$count);
	while($count > 0);
	$table = explode(chr(10),$content);
	$newtable = array();
	$imax = count($table);
	$item = 1;
	$initial_controls = ''; $tie = 0;
	for($i = $start_line = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		// if($line == '') continue;
		if(is_integer($pos=strpos($line,"{"))) {
			if($initial_controls == '') $initial_controls = trim(substr($line,0,$pos));
			$start_line = $i;
			}
		$line = str_replace($initial_controls,'',$line);
		$newline = $line;
		if(substr_count($line,'{') > 0) {
			$newline = '';
			$level = 0; $first = TRUE;
			for($j = 0; $j < strlen($line); $j++) {
				$c = $line[$j];
				if($j < (strlen($line) - 1) AND ctype_alnum($c) AND $line[$j+1] == '&') $tie++;
				if($j < (strlen($line) - 1) AND $c == '&' AND ctype_alnum($line[$j+1])) $tie--;
				if($c == '{') {
					if($item == 1 AND $level == 0) $newline .= "[item ".($item++)."] ".$initial_controls." ";
				//	if($level == 0 AND !$first) $newline .= $initial_controls." ";
					$first = FALSE;
					$level++;
					}
				$newline .= $c;
				if($c == '}') {
					$level--;
					if($level == 0 /* AND $tie >= 0 */) {
						$outside_expression = ' ';
						for($k = ($j + 1); $k < strlen($line); $k++) {
							$d = $line[$k];
							if($d == '{') break;
							$outside_expression .= $d;
							}
						$j = $k - 1;
						$newline .= "\n\n[item ".($item++)."] ".$initial_controls.$outside_expression;
						}
					}
				}
			}
	//	if($i <> $start_line) $newline = $initial_controls." ".$newline;
		$newtable[] = $newline;
		}
	$newcontent = implode("\n",$newtable);
	$newcontent = str_replace("[item ".($item-1)."] ".$initial_controls,'',$newcontent);
	$newcontent = str_replace("] \n","] ",$newcontent);
	$newcontent = str_replace("\n//","//",$newcontent);
	$_POST['thistext'] = $newcontent;
	$_POST['savethisfile'] = TRUE;
	}

if(isset($_POST['implode'])) {
	$content = $_POST['thistext'];
	$content = str_replace("\r",chr(10),$content);
	do $content = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$content,$count);
	while($count > 0);
//	$content = preg_replace("/_scale\([^\)]+\)\s*/u",'',$content);
	do $content = str_replace("} ","}",$content,$count);
	while($count > 0);
	do $content = str_replace(" {","{",$content,$count);
	while($count > 0);
	do $content = str_replace("}\n\n","}\n",$content,$count);
	while($count > 0);
	$content = preg_replace("/\[item\s[^\]]+\]\s*/u",'',$content);
	// $content = preg_replace("/}\s{/u","} {",$content);
	$table = explode(chr(10),$content);
	$newtable = array();
	$imax = count($table);
	$initial_controls = '';
	for($i = $start_line = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$these_controls = '';
		if(is_integer($pos=strpos($line,"{"))) {
			$these_controls = trim(substr($line,0,$pos));
			if($initial_controls == '' AND $these_controls <> '') {
				$initial_controls = $these_controls;
				$start_line = $i;
				}
			}
		$line = str_replace($these_controls,'',$line);
		$these_controls = str_replace($initial_controls,'',$these_controls);
		$newtable[] = trim($these_controls." ".$line);
		}
	if($initial_controls <> '') $newtable[$start_line] = $initial_controls." ".$newtable[$start_line];
	$newcontent = implode("\n",$newtable);
	$newcontent = preg_replace("/}\s([^{]*){/u","} $1 {",$newcontent);
	$newcontent = str_replace("\n//","//",$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

if(isset($_POST['create_settings_file']) AND isset($_POST['new_settings_file']) AND $_POST['new_settings_file'] <> '') {
	$new_settings_file = trim($_POST['new_settings_file']);
	if($new_settings_file <> '') {
		$settings_file = good_name("se",$new_settings_file,'');
		$content = @file_get_contents($this_file,TRUE);
		$extract_data = extract_data(TRUE,$content);
		$newcontent = $extract_data['content'];
		$newcontent = preg_replace("/\-se\.[a-zA-Z0-9]+\s/u",'',$newcontent);
		$newcontent = preg_replace("/\-se\.:\s?[a-zA-Z0-9]+\.bpse\s/u",'',$newcontent);
		$_POST['thistext'] = $settings_file."\n\n".$newcontent;
		$need_to_save = TRUE;
		}
	}
	
if(isset($_GET['newsettings'])) {
	$settings_file = urldecode($_GET['newsettings']);
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/\-se\.[a-zA-Z0-9]+\s/u",'',$newcontent);
	$newcontent = preg_replace("/\-se\.:\s?[a-zA-Z0-9]+\.bpse\s/u",'',$newcontent);
	$_POST['thistext'] = $settings_file."\n\n".$newcontent;
	$need_to_save = TRUE;
	$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING: this is a new tag/window. Close the previous one to avoid mixing versions!</font><br />";
	$error = TRUE;
	}

if(isset($_POST['use_convention'])) {
	$old_convention = $_POST['old_convention'];
	$change_octave = 0;
	if($old_convention == 1 AND $new_convention <> 1) $change_octave = +1;
	if($old_convention <> '' AND $old_convention <> 1 AND $new_convention == 1) $change_octave = -1;
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	for($i = 0; $i < 12; $i++) {
		$new_note = $_POST['new_note_'.$i];
		for($octave = 15; $octave >= 0; $octave--) {
			$new_octave = $octave + $change_octave;
			if($new_octave < 0) $new_octave = "00";
			if($new_convention <> 0) $newcontent = str_replace($Englishnote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			if($new_convention <> 0) $newcontent = str_replace($AltEnglishnote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = str_replace($Frenchnote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			if($new_convention <> 1) $newcontent = str_replace($AltFrenchnote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = str_replace($Indiannote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			if($new_convention <> 2) $newcontent = str_replace($AltIndiannote[$i].$octave,$new_note."@&".$new_octave,$newcontent);
			}
		}
	$_POST['thistext'] = str_replace("@&",'',$newcontent);
	// This '@' is required to avoid confusion between "re" in Indian and Italian/French conventions
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_chan'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_chan\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_ins'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_ins\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_tempo'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_tempo\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\([^\)]+\)/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
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
				$new_argument = "@&".$_POST['replace_chan_as_chan_'.$i];
				$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@&".$_POST['replace_chan_as_ins_'.$i];
				$newcontent = str_replace("_chan(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "chan_ins":
				$new_argument_chan = "@&".$_POST['replace_chan_as_chan1_'.$i];
				$new_argument_ins = "@&".$_POST['replace_chan_as_ins1_'.$i];
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
				$new_argument = "@&".$_POST['replace_ins_as_chan_'.$j];
				$newcontent = str_replace("_chan(".$argument.")","_chan(".$new_argument.")",$newcontent);
			break;
			case "ins":
				$new_argument = "@&".$_POST['replace_ins_as_ins_'.$j];
				$newcontent = str_replace("_chan(".$argument.")","_ins(".$new_argument.")",$newcontent);
			break;
			case "chan_ins":
				$new_argument_chan = "@&".$_POST['replace_ins_as_chan1_'.$j];
				$new_argument_ins = "@&".$_POST['replace_ins_as_ins1_'.$j];
				$newcontent = str_replace("_ins(".$argument.")","_chan(".$new_argument_chan.") _ins(".$new_argument_ins.")",$newcontent);
			break;
			case "delete":
				$newcontent = str_replace("_ins(".$argument.")",'',$newcontent);
			break;
			}
		}
	$_POST['thistext'] = str_replace("@&",'',$newcontent);
	$need_to_save = TRUE;
	}

if($need_to_save OR isset($_POST['savethisfile'])) {
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
echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";

if($settings_file <> '')
	$note_convention = get_setting("note_convention",$settings_file);
else $note_convention = '';

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
			if($max_scales > 1) {
				echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of tonal scales&nbsp;<font color=\"red\">➡</font>&nbsp;<button style=\"background-color:aquamarine; border-radius: 6px; font-size:large;\" onclick=\"toggledisplay(); return false;\">Show/hide tonal scales</button>";
				echo "<div id=\"showhide\"><br />";
				}
			else {
				echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains the definition of tonal scale:";
				echo "<div>";
				}
			echo "<ul style=\"margin-top:0px; margin-bottom:0px\">";
			for($i_scale = 1; $i_scale <= $max_scales; $i_scale++)
				echo "<li>".$list_of_tonal_scales[$i_scale - 1]."</li>";
			if($max_scales > 1) echo "</ul><br />These scales may be called in “_scale(name of scale, blockkey)” instructions (with blockey = 0 by default)";
			else echo "</ul><br />This scale may be called in a “_scale(name of scale, blockkey)” instruction (with blockey = 0 by default)<br />➡ Use “_scale(0,0)” to force equal-tempered";
			echo "</div>";
			echo "</p>";
			}
		$list_of_instruments = list_of_instruments($dir_csound_resources.$csound_file);
		$list = $list_of_instruments['list'];
		$list_index = $list_of_instruments['index'];
		if(($max_instr = count($list)) > 0) {
			if($max_scales > 0) echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> also contains definitions of instrument(s):";
			else echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of instrument(s):";
			echo "<ul style=\"margin-top:0px; margin-bottom:0px\">";
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
			echo "</ul>";
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
echo "<input style=\"background-color:yellow;\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"savethisfile\" value=\"SAVE\"></p>";
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

$link_options = '';
if($grammar_file <> '') {
	if(!file_exists($dir.$grammar_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING:</font> <font color=\"blue\">".$grammar_file."</font> not yet created<br />";
		$error = TRUE;
		}
	else $link_options .= "&grammar=".urlencode($dir.$grammar_file);
	}
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING:</font> <font color=\"blue\">".$alphabet_file."</font> not yet created<br />";
		$error = TRUE;
		}
	else $link_options .= "&alphabet=".urlencode($dir.$alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING:</font> <font color=\"blue\">".$settings_file."</font> not yet created<br />";
		$error = TRUE;
		}
	else $link_options .= "&settings=".urlencode($dir.$settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING:</font> <font color=\"blue\">".$objects_file."</font> not yet created<br />";
		$error = TRUE;
		}
	else $link_options .= "&objects=".urlencode($dir.$objects_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir_csound_resources.$csound_file)) {
		$error_mssg .= "<font color=\"red\" class=\"blinking\">WARNING:</font> <font color=\"blue\">".$csound_file."</font> not yet created<br />";
		$error = TRUE;
		}
	else {
		$link_options .= "&csound_file=".urlencode($csound_file);
		if($file_format == "csound" AND file_exists($dir_csound_resources.$csound_orchestra)) $link_options .= "&csound_orchestra=".urlencode($csound_orchestra);
		}
	}
$link_options .= "&here=".urlencode($dir.$filename);

if($error_mssg <> '') {
	echo "<p>".$error_mssg."</p>";
//	display_more_buttons(FALSE,$content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
	}

if(intval($note_convention) <> intval($new_convention))
	echo "<p><font color=\"red\">➡</font> WARNING: Note convention should be set to <font color=\"red\">‘".ucfirst(note_convention(intval($new_convention)))."’</font> in the <font color=\"blue\">‘".$settings_file."’</font> settings file</p>";

echo "<table style=\"background-color:GhostWhite;\" border=\"0\"><tr>";
echo "<td style=\"background-color:cornsilk;\">";

echo "<div style=\"float:right; vertical-align:middle;\">Import MusicXML file: <input type=\"file\" name=\"music_xml_import\">&nbsp;<input type=\"submit\" style=\"background-color:AquaMarine;\" value=\"← IMPORT\"></div>";

echo "<div style=\"text-align:left;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></div>";

echo "<br /><textarea name=\"thistext\" rows=\"40\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";

echo "<div style=\"text-align:right;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></div>";

echo "</form>";

display_more_buttons($error,$content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

$hide = FALSE;

echo "<form  id=\"topchanges\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if(isset($_POST['change_convention']) AND isset($_POST['new_convention'])) {
	$new_convention = $_POST['new_convention'];
	echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
	echo "<input type=\"hidden\" name=\"old_convention\" value=\"".$note_convention."\">";
	echo "<hr>";
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
	echo "<tr><td></td><td></td><td><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_changes_instructions\" formaction=\"".$url_this_page."#topedition\" value=\"APPLY THESE CHANGES\"></td></tr>";
	echo "</table>";
	$hide = TRUE;
	}
if(!$hide) {
	if($settings_file == '') {
		$new_settings_file = str_replace("-da.",'',$filename);
		$new_settings_file = str_replace(".bpda",'',$new_settings_file);
		$new_settings_file = "-se.".$new_settings_file;
		echo "<p style=\"background-color:white;\"><font color=\"red\">➡</font> <input style=\"background-color:yellow; font-size:large;\" onclick=\"window.open('settings_list.php?dir=".urlencode($dir)."&thispage=".urlencode($url_this_page)."','settingsfiles','width=400,height=400,left=100'); return false;\" type=\"submit\" title=\"Display settings files\" value=\"CHOOSE\"> a settings file or <input style=\"background-color:yellow; font-size:large;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"create_settings_file\" formaction=\"".$url_this_page."\" value=\"CREATE\"> a new file named <input type=\"text\" name=\"new_settings_file\" size=\"25\" value=\"".$new_settings_file."\"></p>";
		}
	else 
		echo "<p><input style=\"background-color:yellow;\" onclick=\"window.open('settings_list.php?dir=".urlencode($dir)."&thispage=".urlencode($url_this_page)."','settingsfiles','width=400,height=400,left=100'); return false;\" type=\"submit\" title=\"Display settings files\" value=\"CHOOSE\"> a different settings file</p>";
	echo "<hr>";
	if($note_convention <> '')
		echo "<p>Current note convention for this data is <font color=\"red\">‘".ucfirst(note_convention(intval($note_convention)))."’</font> as per <font color=\"blue\">‘".$settings_file."’</font></p>";
	echo "<table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"change_convention\" formaction=\"".$url_this_page."#topchanges\" value=\"APPLY NOTE CONVENTION to this data\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
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
	if($imax > 0) {
		echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
		echo "<input type=\"submit\" style=\"background-color:AquaMarine;\" formaction=\"".$url_this_page."#topedit\" name=\"implode\" value=\"IMPLODE\">&nbsp;<font color=\"red\">➡ </font><i>merge</i> {…} <i>expressions</i>";
		echo "</td></tr>";
		}
	echo "</form>";
	}
for($i = $j = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$error_mssg = $tie_mssg = '';
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
	$initial_controls = $tie_error = '';
	$chunked = FALSE;
	$tie = $n = $brackets = 0;
	if(is_integer($pos=strpos($line_recoded,"{"))) {
		$initial_controls = trim(substr($line_recoded,0,$pos));
		}
	$line_chunked = ''; $first = TRUE; $chunk_number = 1; $start_chunk = "[chunk 1] ";
	for($k = $level = 0; $k < strlen($line_recoded); $k++) {
		$line_chunked .= $start_chunk;
		$start_chunk = '';
		$c = $line_recoded[$k];
		if($k < (strlen($line_recoded) - 1) AND ctype_alnum($c) AND $line_recoded[$k+1] == '&') $tie++;
		if($k < (strlen($line_recoded) - 1) AND $c == '&' AND ctype_alnum($line_recoded[$k+1])) $tie--;
		if($c == '.' AND $k > 0 AND $line_recoded[$k-1]) $brackets++;
		if($c == '{') {
			if($level == 0 AND !$first) $line_chunked .= $initial_controls;
			$first = FALSE;
			$line_chunked .= $c;
			$brackets++;
			$level++;
			continue;
			}
		$line_chunked .= $c;
		if($c == '}') {
			$level--; 
			if($level == 0) $n++;
			if($level == 0 AND ($tie <= 0 OR $n > $maxchunk_size)) {
				if($tie > 0) {
					$tie_mssg =  "• <font color=\"red\">".$tie." unbound ties in chunk #".$chunk_number."</font><br />";
					$tie_error = TRUE;
					}
				$line_chunked .= "\n";
				$tie = $n = 0;
				$start_chunk = "[chunk ".(++$chunk_number)."] ";
				if($k < (strlen($line_recoded) - 1)) $chunked = TRUE;
				}
			}
		}
//	$chunked = TRUE;
	if($chunked) {
		$data_chunked = $temp_dir.$temp_folder.SLASH.$j."-chunked.bpda";
		$handle = fopen($data_chunked,"w");
		fwrite($handle,$line_chunked."\n");
		fclose($handle);
		}
	else $data_chunked = '';
	echo "<tr><td>".$j."</td><td>";
//	$link_options .= "&item=".$j;
	$link_options_play = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file)."&format=".$file_format."&item=".$j;
	$link_options_chunked = $link_options_play;
	$output_file_expand = str_replace(".sco",'',$output_file);
	$output_file_expand = str_replace(".mid",'',$output_file_expand);
	$output_file_expand .= ".bpda";
	$link_options_expand = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file_expand)."&format=data";
	$link_produce = "produce.php?data=".urlencode($data);
	$link_produce_chunked = "produce.php?data=".urlencode($data_chunked);
	$link_play = $link_produce."&instruction=play";
	$link_play_chunked = $link_produce_chunked."&instruction=play-all";
	$link_play .= $link_options_play;
	$link_play_chunked .= $link_options_play;
	$link_expand = $link_produce."&instruction=expand";
	$link_expand .= $link_options_expand;
	$window_name = window_name($filename);
	$window_name_play = $window_name."_play";
	$window_name_expland = $window_name."_expland";
//	echo "<small>".urldecode($link_play)."</small><br />";
//	echo "<small>".urldecode($link_expand)."</small><br />";
//	echo "<small>".urldecode($link_play_chunked)."</small><br />";
	$n1 = substr_count($line_recoded,'{');
	$n2 = substr_count($line_recoded,'}');
	if($n1 > $n2) $error_mssg .= "• <font color=\"red\">This score contains ".($n1-$n2)." extra ‘{'</font><br />";
	if($n2 > $n1) $error_mssg .= "• <font color=\"red\">This score contains ".($n2-$n1)." extra ‘}'</font><br />";
	if($error_mssg == '') {
		echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play."','".$window_name_play."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Play this polymetric expression\" value=\"PLAY\">&nbsp;";
		if($chunked) echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play_chunked."','".$window_name_play."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Play polymetric expression in chunks (no graphics)\" value=\"PLAY safe\">&nbsp;";
		if($brackets > 0) echo "&nbsp;<input style=\"background-color:azure;\" onclick=\"window.open('".$link_expand."','".$window_name_expland."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Expand this polymetric expression\" value=\"EXPAND\">&nbsp;";
		}
	if($tie_mssg <> '' AND $error_mssg == '') echo "<br />";
	if($tie_mssg <> '') echo $tie_mssg;
	if($error_mssg <> '') echo $error_mssg;
	$length = strlen($line_recoded);
	if($length > 400)
		$line_show = substr($line_recoded,0,100)."<br />&nbsp;... ... ...<br />".substr($line_recoded,-100,100);
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
