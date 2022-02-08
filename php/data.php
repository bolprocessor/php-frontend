<?php
require_once("_basic_tasks.php");
require_once("_settings.php");
require_once("_musicxml.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "data.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);

require_once("_header.php");
echo "<p>Current directory = <a href=\"index.php?path=".urlencode($current_directory)."\">".$dir."</a></p>";
echo link_to_help();

$test_musicxml = FALSE;

echo "<div style=\"float:right; background-color:white; padding-right:6px; padding-left:6px;\">";
$csound_is_responsive = check_csound();
echo "</div>";
echo "<h3>Data file “".$filename."”</h3>";
save_settings("last_name",$filename); 

$temp_folder = str_replace(' ','_',$filename)."_".session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
$music_xml_file = $temp_dir.$temp_folder.SLASH."temp.musicxml";
$more_data = ''; $dynamic_control = array();
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
		if(isset($_POST['first_scale'])) $first_scale = $_POST['first_scale'];
		else {
			$first_scale = '';
			if(is_integer($pos1=strpos($save_content,"_scale("))) {
				$pos2 = strpos($save_content,")",$pos1);
				$first_scale = substr($save_content,$pos1,$pos2 - $pos1 + 1);
				}
			}
		if($more_data <> '') $save_content = $more_data."\n\n".$save_content;
		$handle = fopen($this_file,"w");
		$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
		do $save_content = str_replace("  ",' ',$save_content,$count);
		while($count > 0);
		fwrite($handle,$file_header."\n");
		fwrite($handle,$save_content);
		fclose($handle);
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
			$partwise = $timewise = $attributes = $attributes_key = $changed_attributes = $found_trill = $found_mordent = $found_turn = $found_fermata = $found_arpeggio = FALSE;
			$add_section = TRUE;
			$instrument_name = $midi_channel = $select_part = $duration_part = $divisions = $repeat_section = array();
			$ignore_dynamics = isset($_POST['ignore_dynamics']);
			if(isset($_POST['tempo_option'])) $tempo_option = $_POST['tempo_option'];
			else $tempo_option = "allbutscore";
			$list_corrections = isset($_POST['list_corrections']);
			$trace_ornamentations = isset($_POST['trace_ornamentations']);
			echo "<input type=\"hidden\" name=\"tempo_option\" value=\"".$tempo_option."\">";
			$ignore_channels = isset($_POST['ignore_channels']);
			$ignore_trills = isset($_POST['ignore_trills']);
			$ignore_fermata = isset($_POST['ignore_fermata']);
			$ignore_mordents = isset($_POST['ignore_mordents']);
			$ignore_turns = isset($_POST['ignore_turns']);
			$ignore_arpeggios = isset($_POST['ignore_arpeggios']);
			$section = 0; // This variable is used for repetitions, see forward/backward
			$repeat_section[$section] = 1; // By default, don't repeat
			$part = '';
			$i_measure = -1; $i_part = 0;
			$reading_measure = FALSE;
			$message_top = "<input type=\"checkbox\" id=\"parent1\" style=\"box-shadow: -2px -2px Gold\"> <b>Check all</b><br />";
			$message_options = ''; $first = TRUE;
			$sum_metronome = $number_metronome = $metronome_max = $metronome_min = 0;
			$change_metronome_average = $change_metronome_min = $change_metronome_max = 0;
			$current_metronome_average = $current_metronome_min = $current_metronome_max = 0;
			$error_change_metronome = '';
			if(isset($_POST['change_metronome_min']))
				$change_metronome_min = intval($_POST['change_metronome_min']);
			if(isset($_POST['change_metronome_max']))
				$change_metronome_max = intval($_POST['change_metronome_max']);
			if(isset($_POST['change_metronome_average']))
				$change_metronome_average = intval($_POST['change_metronome_average']);
			if(isset($_POST['current_metronome_min']))
				$current_metronome_min = intval($_POST['current_metronome_min']);
			if(isset($_POST['current_metronome_max']))
				$current_metronome_max = intval($_POST['current_metronome_max']);
			if(isset($_POST['current_metronome_average']))
				$current_metronome_average = intval($_POST['current_metronome_average']);
			if($change_metronome_min < 1 AND ($change_metronome_max > 0 OR $change_metronome_average > 0))
				$error_change_metronome .= "<font color=\"red\">ERROR changing metronome = minimum value should be positive</font><br />";
			if(($change_metronome_min >= $change_metronome_max OR $change_metronome_average <= $change_metronome_min OR $change_metronome_average >= $change_metronome_max) AND ($change_metronome_max > 0 OR $change_metronome_average > 0))
				$error_change_metronome .= "<font color=\"red\">ERROR changing metronome: values are not compatible</font><br />";
			if($error_change_metronome <> '') $reload_musicxml = FALSE;
			$file = fopen($music_xml_file,"r");
			$print_info = FALSE;
			$beat_unit = "quarter";
			$fifths = $mode = array();
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
					$message_options .= "<input type=\"checkbox\" class=\"child1\" name=\"".$part_selection."\"";
					if($select_part[$score_part] OR (!$reload_musicxml AND $first)) {
						$message_options .= " checked";
						$first = FALSE;
						}
					$message_options .= "> Score part ‘".$score_part."’ instrument = <i>".$instrument_name[$score_part]."</i>";
					if(isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
						$message_options .= " — MIDI channel ".$midi_channel[$score_part];
						}
					$message_options .= "<br />";
					if(isset($_POST['dynamic_control_'.$score_part]))
						$dynamic_control[$score_part] = $_POST['dynamic_control_'.$score_part];
					$message_options .= "&nbsp;&nbsp;<input type=\"radio\" name=\"dynamic_control_".$score_part."\" value=\"volume\"";
					if(!isset($dynamic_control[$score_part]) OR $dynamic_control[$score_part] == "volume")
						$message_options .= " checked";
					$message_options .= ">&nbsp;Interpret dynamics as volume<br />";
					$message_options .= "&nbsp;&nbsp;<input type=\"radio\" name=\"dynamic_control_".$score_part."\" value=\"velocity\"";
					if(isset($dynamic_control[$score_part]) AND $dynamic_control[$score_part] == "velocity")
						$message_options .= " checked";
					$message_options .= ">&nbsp;Interpret dynamics as velocity<br />";
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
					$fifths[$part] = 0;
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
		//	echo str_replace('<','@',$line)."<br />";
				if(is_integer($pos=strpos($line,"<beat-unit>"))) {
					$beat_unit = trim(preg_replace("/<beat-unit>(.+)<\/beat-unit>/u","$1",$line));
					}
				$metronome = 0;
				if(is_integer($pos=strpos($line,"<sound tempo"))) {
					$metronome = round(trim(preg_replace("/.+tempo=\"([^\"]+)\"\/>/u","$1",$line)));
					}
				if(($tempo_option == "all" OR $tempo_option == "score" OR $tempo_option == "allbutmeasures") AND is_integer($pos=strpos($line,"<per-minute>"))) {
					$per_minute = trim(preg_replace("/<per\-minute>([^<]+)<\/per\-minute>/u","$1",$line));
					$beat_divide = beat_divide($beat_unit);
					$metronome = round(($per_minute * $beat_divide['p']) / $beat_divide['q']);
					}
				if($metronome > 0) {
					$sum_metronome += $metronome;
					$number_metronome++;
					if($metronome > $metronome_max) $metronome_max = $metronome;
					if($metronome < $metronome_min OR $metronome_min == 0) $metronome_min = $metronome;
					}
					
				if(is_integer($pos=strpos($line,"</attributes>"))) {
					$attributes = FALSE;
				/*	if($changed_attributes) {
						if(isset($divisions[$part]) AND $divisions[$part] > 0) {
							$message_options .= "Part ‘".$part."’ divisions = ".$divisions[$part];
							}
						if(isset($fifths[$part]) AND $fifths[$part] <> 0) {
							$message_options .= ", fifths = ".$fifths[$part];
							}
						if(isset($mode[$part]) AND $mode[$part] <> '') {
							$message_options .= ", mode = ".$mode[$part];
							}
						$message_options .= "<br />";
						} */
					continue;
					}
				if(is_integer($pos=strpos($line,"<trill-mark"))) $found_trill = TRUE;
				if(is_integer($pos=strpos($line,"<inverted-mordent"))) $found_mordent = TRUE;
				if(is_integer($pos=strpos($line,"<mordent"))) $found_mordent = TRUE;
				if(is_integer($pos=strpos($line,"<turn"))) $found_turn = TRUE;
				if(is_integer($pos=strpos($line,"<fermata"))) $found_fermata = TRUE;
				if(is_integer($pos=strpos($line,"<arpeggiate"))) $found_arpeggio = TRUE;
				if(is_integer($pos=strpos($line,"<measure "))) {
					$reading_measure = TRUE;
				//	if(!is_integer($pos=strpos($line,"implicit=\"yes\""))) { // Not recommended
						$i_measure = trim(preg_replace("/.*number=\"([^\"]+)\".*/u","$1",$line));
						if($test_musicxml)
							echo "• Part ".$part." measure #".$i_measure."<br />";
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
						if($test_musicxml) echo "• Section ".$section." repeat ".$repeat_section[$section]." time(s)<br />";
						}
					continue;
					}
					
				if($reading_measure) {
					$this_score[$section][$i_measure][$part][] = $line;
					}
				}
			fclose($file);
			$i_section =  0;
			foreach($this_score as $section => $the_section) {
				if(count($the_section) > 0) $i_section++;
				if(isset($repeat_start_measure[$section]) AND isset($repeat_end_measure[$section])) {
					echo "Section #".$i_section." is repeated from measure ".$repeat_start_measure[$section]." to ".$repeat_end_measure[$section]."<br /><br />";
					}
				}
			unset($the_section);
			if($number_metronome > 0)
				$metronome_average = round($sum_metronome / $number_metronome);
			else $metronome_average = 0;
			$list_settings = '';
			switch($tempo_option) {
				case "ignore":
					$list_settings .= "// Discard all metronome markers\n"; break;
				case "score":
					$list_settings .= "// Only metronome markers of printed score\n"; break;
				case "allbutscore":
					$list_settings .= "// Metronome markers except the ones of printed score\n"; break;
				case "allbutmeasures":
					$list_settings .= "// Metronome markers except inside measures\n"; break;
				case "all":
					$list_settings .= "// All metronome markers\n"; break;
				}
			if($ignore_channels) $list_settings .= "// Ignoring MIDI channels\n";
			if($ignore_dynamics) $list_settings .= "// Ignoring dynamics\n";
			if($found_trill)
				if($ignore_trills) $list_settings .= "// Ignoring trills\n";
				else $list_settings .= "// Including trills\n";
			if($found_fermata)
				if($ignore_fermata) $list_settings .= "// Ignoring fermata\n";
				else $list_settings .= "// Including fermata\n";
			if($found_mordent)
				if($ignore_mordents) $list_settings .= "// Ignoring mordents\n";
				else $list_settings .= "// Including mordents\n";
			if($found_turn)
				if($ignore_turns) $list_settings .= "// Ignoring turns\n";
				else $list_settings .= "// Including turns\n";
			if($found_arpeggio)
				if($ignore_arpeggios) $list_settings .= "// Ignoring arpeggios\n";
				else $list_settings .= "// Including arpeggios\n";
			if($list_settings <> '') $list_settings .= "\n";
			$convert_score = convert_musicxml($this_score,$repeat_section,$divisions,$fifths,$mode,$midi_channel,$dynamic_control,$select_part,$ignore_dynamics,$tempo_option,$ignore_channels,$ignore_fermata,$ignore_mordents,$ignore_turns,$ignore_trills,$ignore_arpeggios,$reload_musicxml,$test_musicxml,$change_metronome_average,$change_metronome_min,$change_metronome_max,$current_metronome_average,$current_metronome_min,$current_metronome_max,$list_corrections,$trace_ornamentations);
			$data .= $convert_score['data'];
			$report = $convert_score['report'];
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
			$data = str_replace("- -","--",$data); // Simplify - - --> -- (repeated for unknown reason)
			if($reload_musicxml) {
				$more_data = "// MusicXML file ‘".$upload_filename."’ converted\n";
				if($subtitle_part <> '') $more_data .= $subtitle_part."\n";
				}
			if($reload_musicxml) $more_data .= $list_settings;
			$more_data .= $declarations;
			if(isset($_POST['delete_current'])) $_POST['thistext'] = '';
			$data = $first_scale.$data;
			$more_data .= "\n".$data;
			echo "<h3><font color=\"red\">Converting MusicXML file:</font> <font color=\"blue\">".$upload_filename."</font></h3>";
			
			$window_name = $upload_filename;
			$link_preview = "preview_musicxml.php";
			$link_preview .= "?music_xml_file=".urlencode($music_xml_file)."&title=".urlencode($upload_filename);
			echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"PREVIEW MusicXML file\" title=\"\"> <b>(simplified)</b><br /><br />";

			if($report <> '') {
				echo $report;
				echo "_______________________________________<br />";
				}
			if($i_part > 1) echo $message_top;
			else if(!$reload_musicxml) $ignore_channels = TRUE;
			echo $message_options;
			if($reload_musicxml) {
				$current_metronome_min = $convert_score['metronome_min'];
				$current_metronome_max = $convert_score['metronome_max'];
				$current_metronome_average = $convert_score['metronome_average'];
				}
			else {
				$current_metronome_min = $metronome_min;
				$current_metronome_max = $metronome_max;
				$current_metronome_average = $metronome_average;
				}
			echo "<input type=\"hidden\" name=\"first_scale\" value=\"".$first_scale."\">";
			echo "<input type=\"hidden\" name=\"current_metronome_min\" value=\"".$current_metronome_min."\">";
			echo "<input type=\"hidden\" name=\"current_metronome_max\" value=\"".$current_metronome_max."\">";
			echo "<input type=\"hidden\" name=\"current_metronome_average\" value=\"".$current_metronome_average."\">";
			
			if($error_change_metronome <> '') echo $error_change_metronome;
			if($metronome_average > 0 AND $tempo_option <> "ignore") {
				echo "<br /><table cellpadding=\"8px;\">";
				echo "<tr style=\"background-color:white;\"><td><b>Metronome</b></td><td>current:</td><td>set it to…</td></tr>";
				echo "<tr style=\"background-color:white;\"><td><i>Average</i></td><td>".$current_metronome_average." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_average\" size=\"6\" value=\"";
				if($change_metronome_average > 0) echo $change_metronome_average;
				echo "\"> bpm (approx)</td></tr>";
				echo "<tr style=\"background-color:white;\"><td><i>Minimum</i></td><td>".$current_metronome_min." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_min\" size=\"6\" value=\"";
				if($change_metronome_min > 0) echo $change_metronome_min;
				echo "\"> bpm</td></tr>";
				echo "<tr style=\"background-color:white;\"><td><i>Maximum</i></td><td>".$current_metronome_max." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_max\" size=\"6\" value=\"";
				if($change_metronome_max > 0) echo $change_metronome_max;
				echo "\"> bpm</td></tr>";
				echo "</table><br />";
				}
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"ignore\"";
			if($tempo_option == "ignore") echo " checked";
			echo ">&nbsp;Discard all metronome markers<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"score\"";
			if($tempo_option == "score") echo " checked";
			echo ">&nbsp;Interpret only metronome markers of printed score<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"allbutmeasures\"";
			if($tempo_option == "allbutmeasures") echo " checked";
			echo ">&nbsp;Interpret metronome markers except inside measures<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"allbutscore\"";
			if($tempo_option == "allbutscore") echo " checked";
			echo ">&nbsp;Interpret metronome markers except the ones of printed score<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"all\"";
			if($tempo_option == "all") echo " checked";
			echo ">&nbsp;Interpret all metronome markers<br />";
			echo "_______________________________________<br />";
			if($found_trill) {
				echo "<input type=\"checkbox\" name=\"ignore_trills\"";
				if($ignore_trills) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore trills (some have been found in this score)";
				$link_preview .= "&filter=trill";
				$window_name = $upload_filename."_trill";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=400,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_trills = FALSE;
			if($found_mordent) {
				echo "<input type=\"checkbox\" name=\"ignore_mordents\"";
				if($ignore_mordents) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore mordents (some have been found in this score)";
				$link_preview .= "&filter=mordent";
				$window_name = $upload_filename."_mordent";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=400,left=150'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_mordents = FALSE;
			if($found_turn) {
				echo "<input type=\"checkbox\" name=\"ignore_turns\"";
				if($ignore_turns) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore turns (some have been found in this score)";
				$link_preview .= "&filter=turn";
				$window_name = $upload_filename."_turn";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=400,left=100'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_turns = FALSE;
			if($found_fermata) {
				echo "<input type=\"checkbox\" name=\"ignore_fermata\"";
				if($ignore_fermata) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore fermata (some have been found in this score)";
				$link_preview .= "&filter=fermata";
				$window_name = $upload_filename."_fermata";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=400,left=50'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_fermata = FALSE;
			if($found_arpeggio) {
				echo "<input type=\"checkbox\" name=\"ignore_arpeggios\"";
				if($ignore_arpeggios) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore arpeggios (some have been found in this score)";
				$link_preview .= "&filter=arpeggio";
				$window_name = $upload_filename."_arpeggio";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=800,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_arpeggios = FALSE;
			if($found_trill OR $found_arpeggio OR $found_mordent OR $found_turn OR $found_fermata) echo "_______________________________________<br />";

			echo "<input type=\"checkbox\" name=\"ignore_dynamics\"";
			if($ignore_dynamics) echo " checked";
			echo ">&nbsp;Ignore dynamics (volume/velocity)<br />";
			echo "<input type=\"checkbox\" name=\"ignore_channels\"";
			if($ignore_channels) echo " checked";
			echo ">&nbsp;Ignore MIDI channels<br />";
			echo "_________________<br />";
			echo "<input type=\"checkbox\" name=\"list_corrections\"";
			if($list_corrections) echo " checked";
			echo ">&nbsp;List automatic corrections of this score<br />";
			echo "<input type=\"checkbox\" name=\"trace_ornamentations\"";
			if($list_corrections) echo " checked";
			echo ">&nbsp;Trace ornamentations and arpeggios<br />";
			echo "_________________<br />";
			echo "<input type=\"checkbox\" name=\"delete_current\">&nbsp;Delete current data<br />";
			echo "_________________<br />";
			echo "<input type=\"hidden\" name=\"upload_filename\" value=\"".$upload_filename."\">";
			echo "<font color=\"red\">➡</font> You can select parts and <input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"select_parts\" value=\"CONVERT\">&nbsp;or&nbsp;<input style=\"background-color:azure;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"cancel\" value=\"QUIT IMPORTING\">";
			$new_convention = 0; // English note convention
			$need_to_save = TRUE;
			}
		}
	}
unset($_FILES['music_xml_import']);

if(isset($_POST['explode'])) {
	$content = decode_tags($_POST['thistext']);
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
	$content = decode_tags($_POST['thistext']);
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
	$newcontent = preg_replace("/\s*_chan\([^\)]+\)\s*/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_ins'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/\s*_ins\([^\)]+\)\s*/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_tempo'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/\s*_tempo\([^\)]+\)\s*/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/\s*_volume\([^\)]+\)\s*/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['volume_velocity'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\(([^\)]+)\)/u","_vel($1)",$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['velocity_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_vel\(([^\)]+)\)/u","_volume($1)",$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_velocity'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/\s*_vel\([^\)]+\)\s*/u",'',$newcontent);
	$_POST['thistext'] = $newcontent;
	$need_to_save = TRUE;
	}

$change_velocity_average = $change_volume_average = 64;
$change_velocity_max = $change_volume_max = 127;
if(isset($_POST['change_velocity_average'])) $change_velocity_average = abs(intval($_POST['change_velocity_average']));
if(isset($_POST['change_velocity_max'])) $change_velocity_max = abs(intval($_POST['change_velocity_max']));
if(isset($_POST['change_volume_average'])) $change_volume_average = abs(intval($_POST['change_volume_average']));
if(isset($_POST['change_volume_max'])) $change_volume_max = abs(intval($_POST['change_volume_max']));

if(isset($_POST['apply_velocity_change'])) {
	if(isset($_POST['change_velocity_average']) AND $change_velocity_average > 0 AND isset($_POST['change_velocity_max']) AND $change_velocity_max > 0 AND $change_velocity_average < 128 AND $change_velocity_max < 128 AND $change_velocity_average <= $change_velocity_max) {
		$velocity_average = $_POST['velocity_average'];
		$velocity_max = $_POST['max_velocity'];
		$velocity_min = $change_velocity_min = 0;
		$quad = TRUE;
		$quadratic_mapping = quadratic_mapping($velocity_min,$velocity_average,$velocity_max,$change_velocity_min,$change_velocity_average,$change_velocity_max);
		$a = $quadratic_mapping['a'];
		$b = $quadratic_mapping['b'];
		$c = $quadratic_mapping['c'];
		$y_prime1 = $quadratic_mapping['y_prime1'];
		$y_prime3 = $quadratic_mapping['y_prime3'];
		if($y_prime1 < 0 OR $y_prime3 < 0) { // Quadratic regression is not monotonous
			$quad = FALSE;
			$a1 = $change_velocity_average / $velocity_average;
			if($velocity_max > $velocity_average) $a2 = ($change_velocity_max - $change_velocity_average) / ($velocity_max - $velocity_average);
			else $a2 = 0;
			$b2 = $change_velocity_average;
			}
		$content = @file_get_contents($this_file,TRUE);
		$extract_data = extract_data(TRUE,$content);
		$data = $extract_data['content'];
		$pos1 = 0; $done = array(); $said = FALSE;
		while(is_integer($pos1=strpos($data,"_vel(",$pos1))) {
			if(!is_integer($pos2=strpos($data,")",$pos1 + 4))) break;
			$this_value = substr($data,$pos1 + 5,$pos2 - $pos1 - 5);
			if($quad) $new_value = round($a * $this_value * $this_value + $b * $this_value + $c);
			else {
				if($this_value <= $velocity_average) $new_value = round($a1 * $this_value);
				else $new_value = round($a2 * ($this_value - $velocity_average) + $b2);
				}
			$new_control = "_vel(".$new_value.")";
			if(!$said) {
				if($quad) echo "<p><b>Velocity changed using quadratic mapping:</b></p>";
				else echo "<p><b>Velocity changed using linear mapping (because quadratic is not monotonous):</b></p>";
				}
			$said = TRUE;
			if(!isset($done[$new_value])) echo "_vel(".$this_value.") --> _vel(".$new_value.")<br />";
			$done[$new_value] = TRUE;
			$d1 = substr($data,0,$pos1);
			$d2 = substr($data,$pos2 +  1,strlen($data) - $pos2 - 1);
			$data = $d1.$new_control.$d2;
			$pos1 = $pos2 + strlen($new_control) + 2;
			}
		$_POST['thistext'] = $data;
		$need_to_save = TRUE;
		}
	else echo "<p><font color=\"red\">➡ Modified values of velocity “".$_POST['change_velocity_average']."” and “".$_POST['change_velocity_max']."” are missing or out of range!</font></p>";
	}

if(isset($_POST['apply_volume_change'])) {
	if(isset($_POST['change_volume_average']) AND $change_volume_average > 0 AND isset($_POST['change_volume_max']) AND $change_volume_max > 0 AND $change_volume_average < 128 AND $change_volume_max < 128 AND $change_volume_average <= $change_volume_max) {
		$volume_average = $_POST['volume_average'];
		$volume_max = $_POST['volume_max'];
		$volume_min = $change_volume_min = 0;
		$quad = TRUE;
		$quadratic_mapping = quadratic_mapping($volume_min,$volume_average,$volume_max,$change_volume_min,$change_volume_average,$change_volume_max);
		$a = $quadratic_mapping['a'];
		$b = $quadratic_mapping['b'];
		$c = $quadratic_mapping['c'];
		$y_prime1 = $quadratic_mapping['y_prime1'];
		$y_prime3 = $quadratic_mapping['y_prime3'];
		if($y_prime1 < 0 OR $y_prime3 < 0) { // Quadratic regression is not monotonous
			$quad = FALSE;
			$a1 = $change_volume_average / $volume_average;
			if($volume_max > $volume_average) $a2 = ($change_volume_max - $change_volume_average) / ($volume_max - $volume_average);
			else $a2 = 0;
			$b2 = $change_volume_average;
			}
		$content = @file_get_contents($this_file,TRUE);
		$extract_data = extract_data(TRUE,$content);
		$data = $extract_data['content'];
		$pos1 = 0; $done = array(); $said = FALSE;
		while(is_integer($pos1=strpos($data,"_volume(",$pos1))) {
			if(!is_integer($pos2=strpos($data,")",$pos1 + 7))) break;
			$this_value = substr($data,$pos1 + 8,$pos2 - $pos1 - 8);
			if($quad) $new_value= round($a * $this_value * $this_value + $b * $this_value + $c);
			else {
				if($this_value <= $volume_average) $new_value = round($a1 * $this_value);
				else $new_value = round($a2 * ($this_value - $volume_average) + $b2);
				}
			$new_control = "_volume(".$new_value.")";
			if(!$said) {
				if($quad) echo "<p><b>Volume changed using quadratic mapping:</b></p>";
				else echo "<p><b>Volume changed using linear mapping (because quadratic is not monotonous):</b></p>";
				}
			$said = TRUE;
			if(!isset($done[$new_value])) echo "_volume(".$this_value.") --> _volume(".$new_value.")<br />";
			$done[$new_value] = TRUE;
			$d1 = substr($data,0,$pos1);
			$d2 = substr($data,$pos2 +  1,strlen($data) - $pos2 - 1);
			$data = $d1.$new_control.$d2;
			$pos1 = $pos2 + strlen($new_control) + 2;
			}
		$_POST['thistext'] = $data;
		$need_to_save = TRUE;
		}
	else echo "<p><font color=\"red\">➡ Modified values of volume “".$_POST['change_volume_average']."” and “".$_POST['change_volume_max']."” are missing or out of range!</font></p>";
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
/*	if(isset($_POST['first_scale'])) $first_scale = $_POST['first_scale'];
	else $first_scale = ''; */
	$content = $_POST['thistext'];
//	echo $content."<br />";
	if($more_data <> '') $content = $more_data."\n\n".$content;
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	do $content = str_replace("  ",' ',$content,$count);
	while($count > 0);
	fwrite($handle,$file_header."\n");
//	if($first_scale <> '') fwrite($handle,$first_scale);
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

if(isset($_POST['modify_velocity'])) {
	echo "<hr>";
	echo "<h3>Modify velocities:</h3>";
	echo "<i>Values will be quadratically interpolated</i><br />";
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$data = $extract_data['content'];
	$velocity_average = search_value("average",$data,"_vel");
	$max_velocity = search_value("max",$data,"_vel");
	echo "<input type=\"hidden\" name=\"velocity_average\" value=\"".$velocity_average."\">";
	echo "<input type=\"hidden\" name=\"max_velocity\" value=\"".$max_velocity."\">";
	echo "<table style=\"background-color:cornsilk; border-spacing:6px;\">";
	echo "<tr><td></td><td style=\"text-align:center;\"><b>Current value</b></td><td style=\"text-align:center;\"><b>Replace with<br />(0 … 127)</b></td></tr>";
	echo "<tr><td>Average</td>";
	echo "<td style=\"text-align:center;\">".$velocity_average."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_velocity_average\" size=\"6\" value=\"".$change_velocity_average."\"></td>";
	echo "</tr>";
	echo "<tr><td>Max</td>";
	echo "<td style=\"text-align:center;\">".$max_velocity."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_velocity_max\" size=\"6\" value=\"".$change_velocity_max."\"></td>";
	echo "</tr>";
	echo "<tr><td style=\"text-align:center;\"><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td></td><td style=\"text-align:center;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_velocity_change\" value=\"APPLY\"></td></tr>";
	echo "</table>";
	echo "<hr>";
	$hide = TRUE;
	}

if(isset($_POST['modify_volume'])) {
	echo "<hr>";
	echo "<h3>Modify volumes:</h3>";
	echo "<i>Values will be quadratically interpolated</i><br />";
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$data = $extract_data['content'];
	$volume_average = search_value("average",$data,"_volume");
	$volume_max = search_value("max",$data,"_volume");
	echo "<input type=\"hidden\" name=\"volume_average\" value=\"".$volume_average."\">";
	echo "<input type=\"hidden\" name=\"volume_max\" value=\"".$volume_max."\">";
	echo "<table style=\"background-color:cornsilk; border-spacing:6px;\">";
	echo "<tr><td></td><td style=\"text-align:center;\"><b>Current value</b></td><td style=\"text-align:center;\"><b>Replace with<br />(0 … 127)</b></td></tr>";
	echo "<tr><td>Average</td>";
	echo "<td style=\"text-align:center;\">".$volume_average."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_volume_average\" size=\"6\" value=\"".$change_volume_average."\"></td>";
	echo "</tr>";
	echo "<tr><td>Max</td>";
	echo "<td style=\"text-align:center;\">".$volume_max."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_volume_max\" size=\"6\" value=\"".$change_volume_max."\"></td>";
	echo "</tr>";
	echo "<tr><td style=\"text-align:center;\"><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td></td><td style=\"text-align:center;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_volume_change\" value=\"APPLY\"></td></tr>";
	echo "</table>";
	echo "<hr>";
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
	$found_velocity = substr_count($content,"_vel(");
	if($found_chan > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_chan\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _chan()\">&nbsp;";
	if($found_ins > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_ins\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _ins()\">&nbsp;";
	if($found_tempo > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_tempo\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _tempo()\">&nbsp;";
	if($found_volume > 0) {
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_volume\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _volume()\">&nbsp;";
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"volume_velocity\" formaction=\"".$url_this_page."#topedit\" value=\"volume -> velocity\">&nbsp;";
		}
	if($found_velocity > 0) {
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_velocity\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _vel()\">&nbsp;";
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"velocity_volume\" formaction=\"".$url_this_page."#topedit\" value=\"velocity -> volume\">&nbsp;";
		}
	if($found_volume > 0) {
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"modify_volume\" formaction=\"".$url_this_page."#topchanges\" value=\"Modify _volume()\">&nbsp;";
		}
	if($found_velocity > 0) {
		echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"modify_velocity\" formaction=\"".$url_this_page."#topchanges\" value=\"Modify _vel()\">&nbsp;";
		}
	if($found_chan > 0  OR $found_ins > 0) echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"manage_instructions\" formaction=\"".$url_this_page."#topchanges\" value=\"MANAGE _chan() AND _ins()\">&nbsp;";
	echo "<input type=\"hidden\" name=\"change_velocity_average\" value=\"".$change_velocity_average."\">";
	echo "<input type=\"hidden\" name=\"change_velocity_max\" value=\"".$change_velocity_max."\">";
	echo "<input type=\"hidden\" name=\"change_volume_average\" value=\"".$change_volume_average."\">";
	echo "<input type=\"hidden\" name=\"change_volume_max\" value=\"".$change_volume_max."\">";
	}
echo "</form>";
echo "</td>";
$window_name = window_name($filename);
if(!$hide) {
	echo "<td style=\"background-color:cornsilk;\">";
	echo "<table style=\"background-color:Gold;\">";
	$table = explode(chr(10),$content);
	$imax = count($table);
	if($imax > 0 AND substr_count($content,'{') > 0) {
		$window_name_grammar = $window_name."_grammar";
		$link_grammar = "produce.php?data=".urlencode($this_file);
		$link_grammar = $link_grammar."&instruction=create_grammar";

		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"thistext\" value=\"".recode_tags($content)."\">";
		echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
		echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
		echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
		echo "<div style=\"float:right;\"><input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_grammar."','".$window_name_grammar."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"create_grammar\" title=\"Create grammar using items on this page\" value=\"CREATE GRAMMAR\"></div>";
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
		if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"[")) AND $pos == 0)
			$title_this = preg_replace("/\[([^\]]+)\].*/u",'$1',$line);
		else $title_this = '';
		$line = preg_replace("/\[[^\]]*\]/u",'',$line);
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
		$tie = $n = $brackets = $total_ties = 0;
		if(is_integer($pos=strpos($line_recoded,"{"))) {
			$initial_controls = trim(substr($line_recoded,0,$pos));
			}
		$line_chunked = ''; $first = TRUE; $chunk_number = 1; $start_chunk = "[chunk 1] ";
		for($k = $level = 0; $k < strlen($line_recoded); $k++) {
			$line_chunked .= $start_chunk;
			$start_chunk = '';
			$c = $line_recoded[$k];
			if($k < (strlen($line_recoded) - 1) AND ctype_alnum($c) AND $line_recoded[$k+1] == '&') {
				$tie++; $total_ties++;
				}
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
				if($level == 0) {
					$n++;
					if(($tie <= 0 AND $n > $minchunk_size) OR $n > $maxchunk_size) {
						if(abs($tie) > 0) {
							$tie_mssg =  "• <font color=\"red\">".abs($tie)." unbound tie(s) in chunk #".$chunk_number;
							$tie_error = TRUE;
							}
						$line_chunked .= "\n";
						$tie = $n = 0;
						$start_chunk = "[chunk ".(++$chunk_number)."] ";
						if($k < (strlen($line_recoded) - 1)) $chunked = TRUE;
						}
					}
				}
			}
	//	$chunked = TRUE;
		if($chunked) {
			if($tie_mssg == '' AND $total_ties > 0) $tie_mssg = "<font color=\"blue\">";
			if($total_ties > 0) $tie_mssg .=  " <i>total ".$total_ties." tied notes</i></font><br />";
			else $tie_mssg .=  "</font><br />";
			$data_chunked = $temp_dir.$temp_folder.SLASH.$j."-chunked.bpda";
			$handle = fopen($data_chunked,"w");
			fwrite($handle,$line_chunked."\n");
			fclose($handle);
			}
		else {
			$data_chunked = '';
			if($tie_mssg <> '') $tie_mssg .=  "</font><br />";
			}
		echo "<tr><td>".$j."</td><td>";
	//	$link_options .= "&item=".$j;
		$link_options_play = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file)."&format=".$file_format."&item=".$j."&title=".urlencode($filename);
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
			if($chunked) echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play_chunked."','".$window_name_play."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Play polymetric expression in chunks (no graphics)\" value=\"PLAY safe (".$chunk_number." chunks)\">&nbsp;";
		//	if($brackets > 0)
			echo "&nbsp;<input style=\"background-color:azure;\" onclick=\"window.open('".$link_expand."','".$window_name_expland."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Expand this polymetric expression\" value=\"EXPAND\">&nbsp;";
			}
		if($tie_mssg <> '' AND $error_mssg == '') echo "<br />";
		if($tie_mssg <> '') echo $tie_mssg;
		if($error_mssg <> '') echo $error_mssg;
		$line_recoded = recode_tags($line_recoded);
		$length = strlen($line_recoded);
		if($length > 400)
			$line_show = substr($line_recoded,0,90)."<br />&nbsp;... ... ...<br />".substr($line_recoded,-90,90);
		else $line_show = $line_recoded;
		echo "<small>";
		if($title_this <> '') $line_show = "<b><font color=\"AquaMarine\">[".$title_this."]</font></b> ".$line_show;
		echo $line_show;
		echo "</small></td></tr>";
		}
	echo "</table>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";

?>
