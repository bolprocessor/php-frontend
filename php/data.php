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
			$data = '';
			$subtitle_part = '';
			$max_measure = $extend_last_measure = $number_measures = 0;
			$partwise = $timewise = $attributes = $attributes_key = $changed_attributes = $found_trill = $found_mordent = $found_turn = $found_fermata = $found_arpeggio = $found_breath = $found_slur = FALSE;
			$add_section = $include_breaths = $include_measures = $include_slurs = $include_parts = TRUE;
			$accept_signs = FALSE;
			$instrument_name = $midi_channel = $select_part = $duration_part = $divisions = $repeat_section = $rndtime = $rndvel = $part_label = $apply_rndtime = $apply_rndvel = $found_pedal = $accept_pedal = $switch_controler = $switch_channel = array();
			$ignore_dynamics = isset($_POST['ignore_dynamics']);
			if(isset($_POST['tempo_option'])) $tempo_option = $_POST['tempo_option'];
			else $tempo_option = "all";
			$list_corrections = isset($_POST['list_corrections']);
			$trace_tempo = isset($_POST['trace_tempo']);
			$trace_ornamentations = isset($_POST['trace_ornamentations']);
			echo "<input type=\"hidden\" name=\"tempo_option\" value=\"".$tempo_option."\">";
			$ignore_channels = isset($_POST['ignore_channels']);
			if($reload_musicxml) $include_breaths = isset($_POST['include_breaths']);
			if($reload_musicxml) $include_measures = isset($_POST['include_measures']);
			if($reload_musicxml) $include_parts = isset($_POST['include_parts']);
			if($reload_musicxml) $accept_signs = isset($_POST['accept_signs']);
			if($reload_musicxml) $include_slurs = isset($_POST['include_slurs']);
			if(isset($_POST['number_parts'])) $number_parts = $_POST['number_parts'];
			else $number_parts = 0;
			if($reload_musicxml) {
				for($i = 0;  $i < $number_parts; $i++) {
					$index = "apply_rndtime_".$i;
					$apply_rndtime[$i] = isset($_POST[$index]);
					$index = "rndtime_".$i;
					$rndtime[$i] = round(abs(intval($_POST[$index])));
					}
				}
			if($reload_musicxml) {
				for($i = 0;  $i < $number_parts; $i++) {
					$index = "apply_rndvel_".$i;
					$apply_rndvel[$i] = isset($_POST[$index]);
					$index = "rndvel_".$i;
					$rndvel[$i] = round(abs(intval($_POST[$index])));
					if($rndvel[$i] > 127) $rndvel[$i] = 127;
					}
				}
			$ignore_trills = isset($_POST['ignore_trills']);
			$ignore_fermata = isset($_POST['ignore_fermata']);
			$ignore_mordents = isset($_POST['ignore_mordents']);
			$chromatic_trills = isset($_POST['chromatic_trills']);
			$chromatic_mordents = isset($_POST['chromatic_mordents']);
			$chromatic_turns = isset($_POST['chromatic_turns']);
			$ignore_turns = isset($_POST['ignore_turns']);
			$ignore_arpeggios = isset($_POST['ignore_arpeggios']);
			$p_breath_length = 1; $q_breath_length = 6;
			$breath_tag = "🌱";
			$slur_length = 20; // Percentage in _legato()
			if(isset($_POST['p_breath_length'])) $p_breath_length = round(abs(intval($_POST['p_breath_length'])));
			if(isset($_POST['q_breath_length'])) $q_breath_length = round(abs(intval($_POST['q_breath_length'])));
			if(isset($_POST['breath_tag'])) $breath_tag = trim($_POST['breath_tag']);
			if(isset($_POST['slur_length'])) $slur_length = round(abs(intval($_POST['slur_length'])));
			if(isset($_POST['extend_last_measure'])) $extend_last_measure = round(abs(intval($_POST['extend_last_measure'])));
			if($p_breath_length == 0) $p_breath_length = 1;
			if($q_breath_length == 0) $q_breath_length = 1;
			$breath_length['p'] = $p_breath_length;
			$breath_length['q'] = $q_breath_length;
			$section = 0; // This variable is used for repetitions, see forward/backward
			$repeat_section[$section] = 1; // By default, don't repeat
			$part = ''; $i_part = 0;
			$i_measure = -1;
			$reading_measure = FALSE;
			$trace_measures = isset($_POST['trace_measures']);
			$measures['min'] = $measures['max'] = 0;
			if($trace_measures) {
				$measures['min'] = round(abs(intval($_POST['measure_min'])));
				$measures['max'] = round(abs(intval($_POST['measure_max'])));
				if($measures['max'] >= $measures['min']) $list_corrections = $trace_tempo = FALSE;
				else $trace_measures = FALSE;
				}
			$message_top = "<input type=\"checkbox\" id=\"parent1\" style=\"box-shadow: -2px -2px Gold\"> <b>Check all</b><br />";
			$message_options = ''; $first = TRUE;
			$sum_metronome = $number_metronome = $metronome_max = $metronome_min = 0;
			$metronome = 60;
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
					$part_label[$i_part - 1] = $score_part;
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
					$message_options .= "&nbsp;&nbsp;<input type=\"radio\" name=\"dynamic_control_".$score_part."\" value=\"velocity\"";
					if(!isset($dynamic_control[$score_part]) OR $dynamic_control[$score_part] == "velocity")
						$message_options .= " checked";
					$message_options .= ">&nbsp;Interpret dynamics as velocity<br />";
					$message_options .= "&nbsp;&nbsp;<input type=\"radio\" name=\"dynamic_control_".$score_part."\" value=\"volume\"";
					if(isset($dynamic_control[$score_part]) AND $dynamic_control[$score_part] == "volume")
						$message_options .= " checked";
					$message_options .= ">&nbsp;Interpret dynamics as volume<br />";
					if($select_part[$score_part] OR !$reload_musicxml) {
					//	echo "Converting score part = ‘".$score_part."’<br />";
						$subtitle_part .= "// Score part ‘".$score_part."’: instrument = ".$instrument_name[$score_part];
						if(!$ignore_channels AND isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
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
			//		echo "@ divisions = ".$divisions[$part]."<br />";
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
				if(is_integer($pos=strpos($line,"<beat-unit>"))) {
					$beat_unit = trim(preg_replace("/<beat-unit>(.+)<\/beat-unit>/u","$1",$line));
					}
				if(is_integer($pos=strpos($line,"<pedal"))) {
					$found_pedal[$i_part - 1] = TRUE;
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
					continue;
					}
				if(is_integer($pos=strpos($line,"<trill-mark"))) $found_trill = TRUE;
				if(is_integer($pos=strpos($line,"<inverted-mordent"))) $found_mordent = TRUE;
				if(is_integer($pos=strpos($line,"<mordent"))) $found_mordent = TRUE;
				if(is_integer($pos=strpos($line,"<turn"))) $found_turn = TRUE;
				if(is_integer($pos=strpos($line,"<fermata"))) $found_fermata = TRUE;
				if(is_integer($pos=strpos($line,"<arpeggiate"))) $found_arpeggio = TRUE;
				if(is_integer($pos=strpos($line,"<breath-mark")))$found_breath = TRUE;
				if(is_integer($pos=strpos($line,"<slur")))$found_slur = TRUE;
				if(is_integer($pos=strpos($line,"<measure "))) {
					$reading_measure = TRUE;
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
					}
				if($reading_measure AND is_integer($pos=strpos($line,"</measure>"))) {
					$reading_measure = FALSE;
					$number_measures++;
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
					echo "• Section #".$i_section." is repeated from measure ".$repeat_start_measure[$section]." to ".$repeat_end_measure[$section]."<br />";
					}
				}
			unset($the_section);
			if($number_metronome > 0)
				$metronome_average = round($sum_metronome / $number_metronome);
			else $metronome_average = 0;
			$list_settings = '';
			$number_parts = $i_part;
			if($reload_musicxml) {
				switch($tempo_option) {
					case "ignore":
						$list_settings .= "// Discarding all metronome markers\n"; break;
					case "score":
						$list_settings .= "// Reading only metronome markers of printed score\n"; break;
					case "allbutscore":
						$list_settings .= "// Reading metronome markers except on the printed score\n"; break;
					case "allbutmeasures":
						$list_settings .= "// Reading metronome markers except inside measures\n"; break;
					case "all":
						$list_settings .= "// Reading all metronome markers\n"; break;
					}
				if($accept_signs) $list_settings .= "// Reading tempo signs: “allegro” etc.\n";
				if($extend_last_measure > 0) $list_settings .= "// Extended duration of last measure by ".$extend_last_measure." %\n";
				for($i = 0; $i < $number_parts; $i++) {
					if($apply_rndtime[$i])
						$list_settings .= "// Time randomisation = ".$rndtime[$i]." ms in part ‘".$part_label[$i]."’\n";
					if($apply_rndvel[$i])
						$list_settings .= "// Velocity randomisation = ".$rndvel[$i]." in part ‘".$part_label[$i]."’\n";
					}
				if($ignore_channels) $list_settings .= "// Ignoring MIDI channels\n";
				if($ignore_dynamics) $list_settings .= "// Ignoring dynamics\n";
				if($found_breath AND !$include_breaths) $list_settings .= "// Ignoring breaths\n";
				if($found_trill)
					if($ignore_trills) $list_settings .= "// Ignoring trills\n";
					else {
						$list_settings .= "// Including trills";
						if($chromatic_trills) $list_settings .= " (chromatic)";
						$list_settings .= "\n";
						}
				if($found_fermata)
					if($ignore_fermata) $list_settings .= "// Ignoring fermata\n";
					else $list_settings .= "// Including fermata\n";
				if($found_mordent)
					if($ignore_mordents) $list_settings .= "// Ignoring mordents\n";
					else {
						$list_settings .= "// Including mordents";
						if($chromatic_mordents) $list_settings .= " (chromatic)";
						$list_settings .= "\n";
						}
				if($found_turn)
					if($ignore_turns) $list_settings .= "// Ignoring turns\n";
					else {
						$list_settings .= "// Including turns";
						if($chromatic_turns) $list_settings .= " (chromatic)";
						$list_settings .= "\n";
						}
				if($found_slur)
					if($include_slurs) {
						$list_settings .= "// Interpreting slurs as _legato(".$slur_length.")\n";
						}
					else $list_settings .= "// Ignoring slurs\n";
				if($found_arpeggio)
					if($ignore_arpeggios) $list_settings .= "// Ignoring arpeggios\n";
					else $list_settings .= "// Interpreting arpeggios\n";
				if($found_breath)
					if($include_breaths) {
						$list_settings .= "// Interpreting breaths as ".$p_breath_length."/".$q_breath_length." beat";
						if($breath_tag <> '') $list_settings .= " with tag [".$breath_tag."]";
						$list_settings .= "\n";
						}
					else $list_settings .= "// Ignoring breaths\n";
				if($list_settings <> '') $list_settings .= "\n";
				}
			for($i = 0; $i < $number_parts; $i++) {
				$index = "accept_pedal_".$i;
				if(isset($_POST[$index])) $accept_pedal[$i] = $_POST[$index];
				else $accept_pedal[$i] = FALSE;
				}
			$convert_score = convert_musicxml($this_score,$repeat_section,$divisions,$fifths,$mode,$midi_channel,$dynamic_control,$select_part,$ignore_dynamics,$tempo_option,$ignore_channels,$include_breaths,$include_slurs,$include_measures,$ignore_fermata,$ignore_mordents,$chromatic_mordents,$ignore_turns,$chromatic_turns,$ignore_trills,$chromatic_trills,$ignore_arpeggios,$reload_musicxml,$test_musicxml,$change_metronome_average,$change_metronome_min,$change_metronome_max,$current_metronome_average,$current_metronome_min,$current_metronome_max,$list_corrections,$trace_tempo,$trace_ornamentations,$breath_length,$breath_tag,$trace_measures,$measures,$accept_signs,$include_parts,$number_parts,$apply_rndtime,$rndtime,$apply_rndvel,$rndvel,$extend_last_measure,$number_measures,$accept_pedal);
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
			$data = str_replace("}[","} [",$data);
			$data = str_replace("]{","] {",$data);
			$data = str_replace(" 0 "," ",$data); // Added by BB 2022-02-12
			$data = str_replace(",1 ",", - ",$data); // Added by BB 2022-02-12
			$data = str_replace("- -","--",$data); // Simplify - - --> -- (repeated for unknown reason)
			$data = str_replace("_legato_"," _legato(".$slur_length.") ",$data);
			$data = str_replace("_nolegato_"," _legato(0) ",$data);

			if($reload_musicxml) {
				$more_data = "\n// MusicXML file ‘".$upload_filename."’ converted\n";
				// The first "\n" is necessary to create an empty line separating headers. See extract_data()
				if($subtitle_part <> '') $more_data .= $subtitle_part."\n";
				}
			if($reload_musicxml) $more_data .= $list_settings;
			$more_data .= $declarations;
			if(isset($_POST['delete_current'])) $_POST['thistext'] = '';
			$data = $first_scale.$data;
			$more_data .= "\n".$data;

			echo "<h3><font color=\"red\">Importing MusicXML file:</font> <font color=\"blue\">".$upload_filename."</font></h3>";
			echo "<div style=\"background-color:white; width:75%; padding:1em; box-shadow: -5px 5px 5px 0px gold;\">";
			$window_name = $upload_filename;
			$link_preview = "preview_musicxml.php?music_xml_file=".urlencode($music_xml_file)."&title=".urlencode($upload_filename);
			echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=600,height=800,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"PREVIEW MusicXML file\" title=\"\"> <b>(simplified)</b><br /><br />";

			if($report <> '') {
				echo $report;
				echo "<hr>";
				}
			if($number_parts > 1) echo $message_top;
			else if(!$reload_musicxml) $ignore_channels = TRUE;
			echo $message_options;
			echo "<input type=\"checkbox\" name=\"ignore_channels\"";
			if($ignore_channels) echo " checked";
			echo ">&nbsp;Ignore MIDI channels<br />";
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
			else echo "<hr>";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"ignore\"";
			if($tempo_option == "ignore") echo " checked";
			echo ">&nbsp;Discard all metronome markers<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"score\"";
			if($tempo_option == "score") echo " checked";
			echo ">&nbsp;Interpret only <i>prescriptive</i> metronome markers (printed score)<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"allbutmeasures\"";
			if($tempo_option == "allbutmeasures") echo " checked";
			echo ">&nbsp;Interpret metronome markers except inside measures<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"allbutscore\"";
			if($tempo_option == "allbutscore") echo " checked";
			echo ">&nbsp;Interpret metronome markers except the <i>prescriptive</i> ones of printed score<br />";
			echo "<input type=\"radio\" name=\"tempo_option\" value=\"all\"";
			if($tempo_option == "all") echo " checked";
			echo ">&nbsp;Interpret all metronome markers<br />";
			echo "<input type=\"checkbox\" name=\"accept_signs\"";
			if($accept_signs) echo " checked";
			echo ">&nbsp;Interpret tempo signs such as “allegro”<br />";
			echo "<hr>";
			$found_pedal_command = FALSE;
			for($i = 0;  $i < $number_parts; $i++) {
				if(isset($found_pedal[$i])) {
					$index1 = "accept_pedal_".$i;
					$index2 = "switch_controler_".$i;
					$index3 = "switch_channel_".$i;
					if(!$reload_musicxml) {
						$accept_pedal[$i] = TRUE;
						$switch_controler[$i] = 64;
						$switch_channel[$i] = $midi_channel[$part_label[$i]];
						}
					else {
						$accept_pedal[$i] = isset($_POST[$index1]);
						$switch_controler[$i] = intval($_POST[$index2]);
						if($switch_controler[$i] < 64 OR $switch_controler[$i] > 95) $switch_controler[$i] .= "???";
						$switch_channel[$i] = intval($_POST[$index3]);
						if($switch_channel[$i] < 1 OR $switch_channel[$i] > 16) $switch_channel[$i] .= "???";
						}
					echo "<input type=\"checkbox\" name=\"".$index1."\"";
					if($accept_pedal[$i]) echo " checked";
					echo ">&nbsp;<font color=\"red\">➡</font> Interpret ‘pedal’ commands in part ‘".$part_label[$i]."’:";
					echo "&nbsp;controler #<input type=\"text\" style=\"border:none; text-align:center;\" name=\"".$index2."\" size=\"5\" value=\"".$switch_controler[$i]."\"> (64 to 95) on MIDI channel <input type=\"text\" style=\"border:none; text-align:center;\" name=\"".$index3."\" size=\"5\" value=\"".$switch_channel[$i]."\"> (1 to 16)";
					$this_link_preview = $link_preview."&filter=pedal";
					$window_name = $upload_filename."_trill";
					if(!$found_pedal_command) echo "&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">";
					echo "<br />";
					$found_pedal_command = TRUE;
					}
				else $accept_pedal[$i] = FALSE;
				}
			if($found_trill) {
				echo "<input type=\"checkbox\" name=\"ignore_trills\"";
				if($ignore_trills) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore trills (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=trill";
				$window_name = $upload_filename."_trill";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_trills\"";
				if($chromatic_trills) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_trills = FALSE;
			if($found_mordent) {
				echo "<input type=\"checkbox\" name=\"ignore_mordents\"";
				if($ignore_mordents) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore mordents (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=mordent";
				$window_name = $upload_filename."_mordent";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=150'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_mordents\"";
				if($chromatic_mordents) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_mordents = FALSE;
			if($found_turn) {
				echo "<input type=\"checkbox\" name=\"ignore_turns\"";
				if($ignore_turns) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore turns (some have been found in this score)";
				$link_preview .= "&filter=turn";
				$window_name = $upload_filename."_turn";
				echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_preview."','".$window_name."','width=600,height=400,left=100'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_turns\"";
				if($chromatic_turns) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_turns = FALSE;
			if($found_fermata) {
				echo "<input type=\"checkbox\" name=\"ignore_fermata\"";
				if($ignore_fermata) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore fermata (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=fermata";
				$window_name = $upload_filename."_fermata";
				echo "&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=50'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_fermata = FALSE;
			if($found_arpeggio) {
				echo "<input type=\"checkbox\" name=\"ignore_arpeggios\"";
				if($ignore_arpeggios) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Ignore arpeggios (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=arpeggio";
				$window_name = $upload_filename."_arpeggio";
				echo "&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_arpeggios = FALSE;
			if($found_slur) {
				echo "<input type=\"checkbox\" name=\"include_slurs\"";
				if($include_slurs) echo " checked";
				echo ">&nbsp;<font color=\"red\">➡</font> Interpret slurs (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=slur";
				$window_name = $upload_filename."_slur";
				echo "&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				echo "&nbsp;&nbsp;&nbsp;… as extra duration (legato) = <input type=\"text\" style=\"border:none; text-align:center;\" name=\"slur_length\" size=\"2\" value=\"".$slur_length."\">&nbsp;%<br />";
				}
			else $include_slurs = FALSE;

			if($found_pedal_command OR $found_trill OR $found_arpeggio OR $found_mordent OR $found_turn OR $found_fermata OR $found_slur) echo "<hr>";
			echo "Extend duration of last measure by <input type=\"text\" style=\"border:none; text-align:center;\" name=\"extend_last_measure\" size=\"3\" value=\"".$extend_last_measure."\">% (100 % recommended)<br />";
			echo "<hr>";
			echo "Apply time randomization:<br />";
			for($i = 0;  $i < $number_parts; $i++) {
				$index = "apply_rndtime_".$i;
				if(!isset($apply_rndtime[$i])) $apply_rndtime[$i] = FALSE;
				echo "<input type=\"checkbox\" name=\"apply_rndtime_".$i."\"";
				if($apply_rndtime[$i]) echo " checked";
				$index = "rndtime_".$i;
				if(!isset($rndtime[$i])) $rndtime[$i] = 20;
				echo ">&nbsp;<input type=\"text\" style=\"border:none; text-align:center;\" name=\"rndtime_".$i."\" size=\"3\" value=\"".$rndtime[$i]."\"> milliseconds";
				if($number_parts > 1) echo " in part ‘".$part_label[$i]."’";
				echo "<br />";
				}
			echo "Apply velocity randomization:<br />";
			for($i = 0;  $i < $number_parts; $i++) {
				$index = "apply_rndvel_".$i;
				if(!isset($apply_rndvel[$i])) $apply_rndvel[$i] = FALSE;
				echo "<input type=\"checkbox\" name=\"apply_rndvel_".$i."\"";
				if($apply_rndvel[$i]) echo " checked";
				$index = "rndvel_".$i;
				if(!isset($rndvel[$i])) $rndvel[$i] = 10;
				echo ">&nbsp;<input type=\"text\" style=\"border:none; text-align:center;\" name=\"rndvel_".$i."\" size=\"3\" value=\"".$rndvel[$i]."\"> (in range 0 to 127)";
				if($number_parts > 1) echo " in part ‘".$part_label[$i]."’";
				echo "<br />";
				}
			echo "<hr>";
			echo "<input type=\"checkbox\" name=\"ignore_dynamics\"";
			if($ignore_dynamics) echo " checked";
			echo ">&nbsp;Ignore dynamics (volume/velocity)<br />";
			if($found_breath) {
				echo "<input type=\"checkbox\" name=\"include_breaths\"";
				if($include_breaths) echo " checked";
				echo ">&nbsp;Include breaths (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=breath";
				$window_name = $upload_filename."_breath";
				echo "&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				echo "&nbsp;&nbsp;&nbsp;… with breath length = <input type=\"text\" style=\"border:none; text-align:center;\" name=\"p_breath_length\" size=\"2\" value=\"".$p_breath_length."\">/<input type=\"text\" style=\"border:none; text-align:center;\" name=\"q_breath_length\" size=\"2\" value=\"".$q_breath_length."\"> beat(s)<br />";
				echo "&nbsp;&nbsp;&nbsp;… and breath tag = <input type=\"text\" style=\"border:none; text-align:center;\" name=\"breath_tag\" size=\"4\" value=\"".$breath_tag."\"><br />";
				}
			else $include_breaths = FALSE;
			echo "<input type=\"checkbox\" name=\"include_measures\"";
			if($include_measures) echo " checked";
			echo ">&nbsp;Insert measure numbers [—n—]<br />";
			if($number_parts > 1) {
				echo "<input type=\"checkbox\" name=\"include_parts\"";
				if($include_parts) echo " checked";
				echo ">&nbsp;Insert labels of score parts<br />";
				}
			else $include_parts = FALSE;
			echo "<hr>";
			echo "<input type=\"checkbox\" name=\"trace_ornamentations\"";
			if($trace_ornamentations) echo " checked";
			echo ">&nbsp;Trace ornamentations and arpeggios<br />";
			echo "<input type=\"checkbox\" name=\"list_corrections\"";
			if($list_corrections) echo " checked";
			echo ">&nbsp;Trace all corrections of this score<br />";
			echo "<input type=\"checkbox\" name=\"trace_tempo\"";
			if($trace_tempo) echo " checked";
			echo ">&nbsp;Trace tempo variations of this score<br />";
			if($measures['min'] == 0) $min = '';
			else $min = $measures['min'];
			if($measures['max'] == 0) $max = '';
			else $max = $measures['max'];
			echo "<input type=\"checkbox\" name=\"trace_measures\"";
			if($trace_measures) echo " checked";
			echo ">&nbsp;Trace measures <input type=\"text\" style=\"border:none; text-align:center;\" name=\"measure_min\" size=\"4\" value=\"".$min."\"> to <input type=\"text\" style=\"border:none; text-align:center;\" name=\"measure_max\" size=\"4\" value=\"".$max."\"><br />";
			echo "<hr>";
			echo "<input type=\"checkbox\" name=\"delete_current\">&nbsp;Delete current data<br />";
			echo "<input type=\"hidden\" name=\"upload_filename\" value=\"".$upload_filename."\">";
			echo "<input type=\"hidden\" name=\"number_parts\" value=\"".$number_parts."\">";
			echo "<font color=\"red\">➡</font> Now, select parts and <input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"select_parts\" value=\"CONVERT\">&nbsp;or&nbsp;<input style=\"background-color:azure;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"cancel\" value=\"QUIT IMPORTING\">";
			echo "</div>";
			if($found_pedal_command AND $reload_musicxml) {
				for($i = 0; $i < $number_parts; $i++) {
					$more_data = str_replace("_switch_on_part(".($i + 1).")"," _switchon(".$switch_controler[$i].",".$switch_channel[$i].") ",$more_data);
					$more_data = str_replace("_switch_off_part(".($i + 1).")"," _switchoff(".$switch_controler[$i].",".$switch_channel[$i].") ",$more_data);
					}
				}
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
		if(is_integer($pos1=strpos($line,"{"))) {
			if(is_integer($pos2=strpos($line,"[")) AND $pos2 < $pos1) $pos = $pos2;
			else $pos = $pos1;
			if($initial_controls == '') $initial_controls = trim(substr($line,0,$pos));
			$start_line = $i;
			}
		$line = str_replace($initial_controls,'',$line);
		$newline = $line;
		if(substr_count($line,'{') > 0) {
			$newline = '';
			$level_bracket = 0; $first = TRUE;
			for($j = 0; $j < strlen($line); $j++) {
				$c = $line[$j];
				if($j < (strlen($line) - 1) AND ctype_alnum($c) AND $line[$j+1] == '&') $tie++;
				if($j < (strlen($line) - 1) AND $c == '&' AND ctype_alnum($line[$j+1])) $tie--;
				if($c == '{') {
					if($item == 1 AND $level_bracket == 0) $newline .= "[item ".($item++)."] ".$initial_controls." ";
				//	if($level_bracket == 0 AND !$first) $newline .= $initial_controls." ";
					$first = FALSE;
					$level_bracket++;
					}
				$newline .= $c;
				if($c == '}') {
					$level_bracket--;
					if($level_bracket == 0 /* AND $tie >= 0 */) {
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
	echo "<p id=\"timespan\" style=\"color:red; float:right;\">Saved ‘".$filename."’file…</p>";
	if(isset($_POST['thistext'])) $content = $_POST['thistext'];
	else $content = '';
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

$metronome = 0;
$nature_of_time = $time_structure = $objects_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$alphabet_file = $extract_data['alphabet'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$interaction_file = $extract_data['interaction'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];
$metronome = $extract_data['metronome'];
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
echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
echo "<input type=\"hidden\" name=\"csound_file\" value=\"".$csound_file."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
echo "<input type=\"hidden\" name=\"grammar_file\" value=\"".$grammar_file."\">";
echo "<input type=\"hidden\" name=\"objects_file\" value=\"".$objects_file."\">";
echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
$show_production = $trace_production = $non_stop_improvize = $p_clock = $q_clock = $striated_time = $max_time_computing = $produce_all_items = $random_seed = $quantization = $time_resolution = 0;
$note_convention = '';
$csound_default_orchestra = '';
$diapason = 440; $C4key = 60;
$found_orchestra_in_settings = $quantize = FALSE;
$dir_base = str_replace($bp_application_path,'',$dir);
$url_settings = "settings.php?file=".urlencode($dir_base.$settings_file);
if($settings_file <> '' AND file_exists($dir.$settings_file)) {
	$show_production = get_setting("show_production",$settings_file);
	$trace_production = get_setting("trace_production",$settings_file);
	$note_convention = get_setting("note_convention",$settings_file);
	$non_stop_improvize = get_setting("non_stop_improvize",$settings_file);
	$p_clock = get_setting("p_clock",$settings_file);
	$q_clock = get_setting("q_clock",$settings_file);
	$max_time_computing = get_setting("max_time_computing",$settings_file);
	$produce_all_items = get_setting("produce_all_items",$settings_file);
	$random_seed = get_setting("random_seed",$settings_file);
	$diapason = get_setting("diapason",$settings_file);
	$C4key = get_setting("C4key",$settings_file);
	$csound_default_orchestra = get_setting("csound_default_orchestra",$settings_file);
	$time_resolution = get_setting("time_resolution",$settings_file);
	$quantization = get_setting("quantization",$settings_file);
	$quantize = get_setting("quantize",$settings_file);
	$nature_of_time_settings = get_setting("nature_of_time",$settings_file);
	if($csound_default_orchestra <> '') $found_orchestra_in_settings = TRUE;
	}
if($quantization == 0) $quantize = FALSE;
echo "<div style=\"background-color:white; padding:1em; width:690px; border-radius: 15px;\">";
if($settings_file == '' OR !file_exists($dir.$settings_file)) {
	$time_resolution = 10; //  10 milliseconds by default
	$metronome =  60;
	$p_clock = $q_clock = 1;
	$nature_of_time = STRIATED;
	if($time_structure <> '')
		echo "⏱ Metronome (time base) is not specified by a ‘-se’ file. It will be set to <font color=\"red\">60</font> beats per minute. Time structure may be changed in data.<br />";
	else
		echo "⏱ Metronome (time base) is not specified by a ‘-se’ file. It will be set to <font color=\"red\">60</font> beats per minute.<br />";
	echo "•&nbsp;Time resolution = <font color=\"red\">".$time_resolution."</font> milliseconds (by default)<br />";
	echo "•&nbsp;No quantization<br />";
	}
else {
	if($p_clock > 0 AND $q_clock > 0) {
		$metronome_settings = 60 * $q_clock / $p_clock;
		}
	else $metronome_settings = 0;
	if($metronome > 0 AND $metronome <> $metronome_settings) {
		echo "➡&nbsp;Metronome = <font color=\"red\">".$metronome_settings."</font> beats/mn as per <font color=\"blue\">‘".$settings_file."’</font> but it may be changed in data.<br />";
		}
	if($metronome_settings > 0) $metronome = $metronome_settings;
	if($metronome <> intval($metronome)) $metronome = sprintf("%.3f",$metronome);
	$nature_of_time = $nature_of_time_settings;
	if($metronome > 0.) {
		echo "⏱ Metronome = <font color=\"red\">".$metronome."</font> beats/mn by default as per <font color=\"blue\">‘".$settings_file."’</font><br />";
		}
	echo "•&nbsp;Time resolution = <font color=\"red\">".$time_resolution."</font> milliseconds as per <font color=\"blue\">‘".$settings_file."’</font><br />";
	if($quantize) {
		echo "•&nbsp;Quantization = <font color=\"red\">".$quantization."</font> milliseconds as per <font color=\"blue\">‘".$settings_file."’</font>";
		if($time_resolution > $quantization) echo "&nbsp;<font color=\"red\">➡</font>&nbsp;may be raised to <font color=\"red\">".$time_resolution."</font>&nbsp;ms…";
		echo "<br />";
		}
	else echo "•&nbsp;No quantization<br />";
	}
echo "•&nbsp;Time structure is <font color=\"red\">".nature_of_time($nature_of_time)."</font> by default but it may be changed in data<br />";
if($max_time_computing > 0) {
	echo "• Max computation time has been set to <font color=\"red\">".$max_time_computing."</font> seconds by <font color=\"blue\">‘".$settings_file."’</font>";
	if($max_time_computing < 30) echo "&nbsp;<font color=\"red\">➡</font>&nbsp;probably too small!";
	echo "<br />";
	}
if($note_convention <> '') echo "• Note convention is <font color=\"red\">‘".ucfirst(note_convention(intval($note_convention)))."’</font> as per <font color=\"blue\">‘".$settings_file."’</font>";
else echo "• Note convention is <font color=\"red\">‘English’</font> by default";
if($settings_file <> '' AND file_exists($dir.$settings_file)) echo "<input style=\"background-color:yellow;float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".$url_settings."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".$settings_file."’\">";
echo "</div>";

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
				echo "<p style=\"margin-bottom:0px;\">Csound resource file <font color=\"blue\">‘".$csound_file."’</font> contains definitions of tonal scales";
				echo "&nbsp;<font color=\"red\">➡</font>&nbsp;<button style=\"background-color:aquamarine; border-radius: 6px; font-size:large;\" onclick=\"toggledisplay(); return false;\">Show/hide tonal scales</button>";
				echo "<div id=\"showhide\" style=\"border-radius: 15px; padding:6px;\"><br />";
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
else echo "<br />";

echo "<div style=\"float:right; background-color:white; padding-right:6px; padding-left:6px;\">";
$csound_is_responsive = check_csound();
echo "</div>";
echo "<table id=\"topedit\" style=\"background-color:white; border-radius: 15px; border: 1px solid black;\" cellpadding=\"8px;\"><tr style=\"\">";
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

if(intval($note_convention) <> intval($new_convention) AND $new_convention <> '')
	echo "<p><font color=\"red\">➡</font> WARNING: Note convention should be set to <font color=\"red\">‘".ucfirst(note_convention(intval($new_convention)))."’</font> in the <font color=\"blue\">‘".$settings_file."’</font> settings file</p>";

echo "<table style=\"background-color:GhostWhite;\" border=\"0\"><tr>";
echo "<td style=\"background-color:cornsilk;\">";

echo "<div style=\"float:right; vertical-align:middle;\">Import MusicXML file: <input style=\"color:red;\" type=\"file\" name=\"music_xml_import\">&nbsp;<input type=\"submit\" style=\"background-color:AquaMarine;\" value=\"← IMPORT\"></div>";

echo "<div style=\"text-align:left;\"><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></div>";

echo "<br /><textarea name=\"thistext\" rows=\"40\" style=\"width:700px;\">".$content."</textarea>";

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
echo "<hr>";
echo "<h2 id=\"tonalanalysis\" style=\"text-align:center;\">Tonal analysis</h2>";
$tonal_analysis_possible = !($note_convention > 2);
if(!$tonal_analysis_possible) echo "<p><font color=\"red\">➡ Tonal analysis is only possible with names of notes in English, Italian/Spanish/French or Indian conventions.</font></p>";
if(isset($_POST['analyze_tonal'])) {
	echo "<p style=\"text-align:center;\"><i>Ignoring channels, instruments, periods, sound-objects and random performance controls</i></p>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input class=\"shadow\" style=\"float:right; font-size:large;\" type=\"submit\" value=\"HIDE ANALYSIS\">";
	echo "</form><br />";
	echo "<div style=\"background-color:white; padding-left:1em;\"><hr>";
	$test_tonal = FALSE;
	$test_intervals = FALSE;
	$min_duration = 500; // milliseconds for harmonic evaluation
	$max_gap = 300; // milliseconds for melodic evaluation
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i_line = $i_item = 0; $i_line < $imax; $i_line++) {
		$error_mssg = '';
		$line = trim($table[$i_line]);
		if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
		$segment = create_chunks($line,$i_item,$temp_dir,$temp_folder,1,0,"slice");;
		if($segment['error'] == "break") break;
		if($segment['error'] == "continue") continue;
		$tonal_scale = $segment['tonal_scale'];
		$i_item++;
	//	if($i_item <> 2) continue;
		echo "<p><b>Item #".$i_item."</b> — note convention is ‘<font color=\"red\">".ucfirst(note_convention(intval($note_convention)))."</font>’</p>";
		if($tonal_scale <> '') echo "<p>Checking against tonal scale ‘<font color=\"blue\">".$tonal_scale."</font>’ defined in the <a target=\"_blank\" href=\"index.php?path=csound_resources\">Csound resource</a> folder</p>";
		$tie_mssg = $segment['tie_mssg'];
		$data_chunked = $segment['data_chunked']; 
		$content_slice = @file_get_contents($data_chunked,TRUE);
		$table_slice = explode("[slice]",$content_slice);
		$i_slice_max = count($table_slice);
		if($i_slice_max == 0) continue;
		if($i_slice_max > 2) echo "<p>➡ Item has been sliced to speed up calculations</p>";
		$p_tempo = $q_tempo = $q_abs_time = 1;
		$level = $i_token = $p_abs_time = 0;
		$poly = array(); $i_poly = 0;
		$max_poly = 0;
		$current_legato = $i_layer = array();
		$i_layer[0] = $current_legato[0] = 0;
		$p_loc_time = 0; $q_loc_time = 1;
		for($i_slice = 0; $i_slice < $i_slice_max; $i_slice++) {
			$slice = trim($table_slice[$i_slice]);
			$slice = str_replace("_scale(".$tonal_scale.",0)",'',$slice);
			if($slice == '') continue;
			$slice = preg_replace("/\s*_vel\([^\)]+\)\s*/u",'',$slice);
			$slice = preg_replace("/\s*_volume\([^\)]+\)\s*/u",'',$slice);
			$slice = preg_replace("/\s*_chan\([^\)]+\)\s*/u",'',$slice);
			$slice = preg_replace("/\s*_ins\([^\)]+\)\s*/u",'',$slice);
			$slice = preg_replace("/\s*_rnd[^\(]+\([^\)]+\)\s*/u",'',$slice);
			$slice = str_replace("{"," { ",$slice);
			$slice = str_replace("}"," } ",$slice);
			$slice = str_replace(","," , ",$slice);
			$slice = str_replace("-"," - ",$slice);
			do $slice = str_replace("  "," ",$slice,$count);
			while($count > 0);
			// Now we build a phase diagram of this slice
			$mode = "harmonic";
			$slice_test = $slice;
		//	$slice_test = str_replace("_legato(0)",'',$slice);
		//	$slice_test = str_replace("_legato(20)",'',$slice_test);
		//	$slice_test = str_replace("_tempo(13/15)",'',$slice_test);
			echo $slice_test."<br /><br />";
			$result = list_events($slice,$poly,$max_poly,$level,$i_token,$p_tempo,$q_tempo,$p_abs_time,$q_abs_time,$i_layer,$current_legato);
			if($result['error'] <> '') {
				echo "<br />".$result['error'];
				break;
				}
			$i_token = 0;
			$poly = $result['poly'];
			$p_tempo = $result['p_tempo'];
			$q_tempo = $result['q_tempo'];
			$max_poly = $result['max_poly'];
			$p_abs_time = $result['p_abs_time'];
			$q_abs_time = $result['q_abs_time'];
			$current_legato = $result['current_legato'];
			$level = $result['level'];
			$i_layer = $result['i_layer'];
			}
		$make_event_table = make_event_table($poly);
		$table_events = $make_event_table['table'];
		$lcm = $make_event_table['lcm'];
		if($test_tonal) {
			echo "<br />";
			for($i_event = 0; $i_event < count($table_events); $i_event++) {
				$start = round($table_events[$i_event]['start'] / $lcm, 3);
				$duration = round(($table_events[$i_event]['end'] - $table_events[$i_event]['start']) / $lcm, 3);
				echo $table_events[$i_event]['token']." at ".$start." dur = ".$duration."<br />";
				}
			echo "<br />";
			}
		$matching_list = array();
		echo "<center><table style=\"background-color:Gold;\">";
		echo "<tr><th>Melodic intervals</th><th>Harmonic intervals</th></tr>";
		echo "<tr><td>";
		$mode = "melodic";
		$match_notes = match_notes($table_events,$mode,$min_duration,$max_gap,$test_intervals,$lcm);
		$matching_notes = $match_notes['matching_notes'];
		$number_match = $match_notes['max_match'];
		usort($matching_notes,"score_sort");
		if($number_match > 0) {
			echo "Number occurrences:<br />";
			$max_score = $matching_notes[0]['score'];
			for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
				if($max_score > 0)
					$matching_notes[$i_match]['percent'] = round($matching_notes[$i_match]['score'] * 100 / $max_score);
				else $matching_notes[$i_match]['percent'] = 0;
				echo $matching_notes[$i_match][0]." ▹
				 ".$matching_notes[$i_match][1]." (".$matching_notes[$i_match]['score']." times) ".$matching_notes[$i_match]['percent']."%<br />";
				}
			}
		$matching_list[$i_item][$mode] = $matching_notes;
		echo "</td><td>";
		$mode = "harmonic";
		$match_notes = match_notes($table_events,$mode,$min_duration,$max_gap,$test_intervals,$lcm);
		$matching_notes = $match_notes['matching_notes'];
		$number_match = $match_notes['max_match'];
		usort($matching_notes,"score_sort");
		if($number_match > 0) {
			echo "Seconds:<br />";
			$max_score = $matching_notes[0]['score'];
			for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
				if($max_score > 0)
					$matching_notes[$i_match]['percent'] = round($matching_notes[$i_match]['score'] * 100 / $max_score);
				else $matching_notes[$i_match]['percent'] = 0;
				echo $matching_notes[$i_match][0]." ≈ ".$matching_notes[$i_match][1]." (".round($matching_notes[$i_match]['score']/$lcm,2)." s) ".$matching_notes[$i_match]['percent']."%<br />";
				}
			}
		$matching_list[$i_item][$mode] = $matching_notes;
		echo "</td></tr></table></center><br />";

		$mode = "harmonic";
		$result = show_relations_on_image($i_item,$matching_list,$mode,$tonal_scale,$note_convention);
		$mode = "melodic";
		$result = show_relations_on_image($i_item,$matching_list,$mode,$tonal_scale,$note_convention);
		$scalename = $result['scalename'];
		$resource_name = $result['resource_name'];
		if($scalename == '' OR $resource_name == '')
			echo "<div style=\"padding:12px; text-align:center;\">No tonal scale specified.<br />Images display equal-tempered scale.</div><br />";
		else 
			echo "<div style=\"padding:12px; text-align:center;\">Tonal scale ‘<font color=\"blue\">".$scalename."</font>’ was found in<br />in a temporary folder of ‘<font color=\"blue\">".$resource_name."</font>’.</div>";
		echo "<hr>";
		}
	echo "</div>";
	}
else {
	if($csound_file <> '') echo "<p>➡ It would be wise to <a target=\"_blank\" href=\"csound.php?file=".urlencode($csound_resources.SLASH.$csound_file)."\">open</a> the ‘<font color=\"blue\">".$csound_file."</font>’ Csound resource file to use its tonal scale definitions.</p>";
	echo "</form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE INTERVALS\"";
	if(!$tonal_analysis_possible) echo " disabled";
	echo ">";
	echo " ➡ melodic and harmonic tonal intervals of (all) item(s)</p>";
	echo "<hr>";
	}
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
		echo "<input type=\"submit\" style=\"background-color:AquaMarine;\" formaction=\"".$url_this_page."#topedit\" name=\"explode\" value=\"EXPLODE\">&nbsp;<font color=\"red\">➡ </font><i>split</i> {…}&nbsp;<i>expressions (measures)</i>";
		echo "</td></tr>";
		if($imax > 0) {
			echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
			echo "<input type=\"submit\" style=\"background-color:AquaMarine;\" formaction=\"".$url_this_page."#topedit\" name=\"implode\" value=\"IMPLODE\">&nbsp;<font color=\"red\">➡ </font><i>merge</i> {…}&nbsp;<i>expressions (measures)</i>";
			echo "</td></tr>";
			}
		echo "</form>";
		}
	for($i = $i_item = 0; $i < $imax; $i++) {
		$error_mssg = '';
		$line = trim($table[$i]);
		if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
		$segment = create_chunks($line,$i_item,$temp_dir,$temp_folder,$minchunk_size,$maxchunk_size,"chunk");
		if($segment['error'] == "break") break;
		if($segment['error'] == "continue") continue;
		$i_item++;
		$tie_mssg = $segment['tie_mssg'];
		$data = $segment['data'];
		$data_chunked = $segment['data_chunked'];
		$chunked = $segment['chunked'];
		$chunk_number = $segment['chunk_number'];
		$line_recoded = $segment['line_recoded'];
		$title_this = $segment['title_this'];
		echo "<tr><td>".$i_item."</td><td>";
		$link_options_play = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file)."&format=".$file_format."&item=".$i_item."&title=".urlencode($filename);
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
		$window_name_expand = $window_name."_expand";
		$window_name_chunked = $window_name."_chunked";
	//	echo "<small>".urldecode($link_play)."</small><br />";
	//	echo "<small>".urldecode($link_expand)."</small><br />";
	//	echo "<small>".urldecode($link_play_chunked)."</small><br />";
		$n1 = substr_count($line_recoded,'{');
		$n2 = substr_count($line_recoded,'}');
		if($n1 > $n2) $error_mssg .= "• <font color=\"red\">This score contains ".($n1-$n2)." extra ‘{'</font><br />";
		if($n2 > $n1) $error_mssg .= "• <font color=\"red\">This score contains ".($n2-$n1)." extra ‘}'</font><br />";
		if($error_mssg == '') {
			echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play."','".$window_name_play."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"produce\" title=\"Play this polymetric expression\" value=\"PLAY\">&nbsp;";
			if($chunked) echo "<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link_play_chunked."','".$window_name_chunked."','width=800,height=800,left=150,toolbar=yes'); return false;\" type=\"submit\" name=\"produce\" title=\"Play polymetric expression in chunks (no graphics)\" value=\"PLAY safe (".$chunk_number." chunks)\">&nbsp;";
			echo "&nbsp;<input style=\"background-color:azure;\" onclick=\"window.open('".$link_expand."','".$window_name_expand."','width=800,height=800,left=100'); return false;\" type=\"submit\" name=\"produce\" title=\"Expand this polymetric expression\" value=\"EXPAND\">&nbsp;";
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
		echo "<font color=\"blue\">".$line_show."</font>";
		echo "</small></td></tr>";
		}
	echo "</table>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "</body></html>";

function create_chunks($line,$i_item,$temp_dir,$temp_folder,$minchunk_size,$maxchunk_size,$label) {
	$test_legato = FALSE;
	$current_legato = array();
	$i_layer = array();
	$current_legato[0] = $i_layer[0] = $layer = $level_bracket = 0;
	// $layer is the index of the line setting events on the phase diagram
	// $level_bracket is the level of polymetric expression (or "measure")
	// Here we trace whether legato() instructions have been reset before the end of each "measure",
	// i.e. polymetric expression at the lowest $level_bracket
	// We also trace note ties which have not been completely bound at the end of the measure
	// Both conditions prohibit chunking the item at the end of the measure
	$tie_mssg = '';
	$segment['error'] = $tonal_scale = $initial_tempo = '';
	if(is_integer($pos=strpos($line,"[")) AND $pos == 0)
		$title_this = preg_replace("/\[([^\]]+)\].*/u",'$1',$line);
	else $title_this = '';
	$line = preg_replace("/\[[^\]]*\]/u",'',$line);
	$line = preg_replace("/^i[0-9].*/u",'',$line); // Csound note statement
	$line = preg_replace("/^f[0-9].*/u",'',$line); // Csound table statement
	$line = preg_replace("/^t[ ].*/u",'',$line); // Csound tempo statement
	$line = preg_replace("/^s\s*$/u",'',$line); // Csound "s" statement
	$line = preg_replace("/^e\s*$/u",'',$line); // Csound "e" statement
	if($line == '') $segment['error'] = "continue";
	if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) $segment['error'] = "break";
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) $segment['error'] = "continue";
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) $segment['error'] = "continue";
	if($segment['error'] <> '') return $segment;
	$line_recoded = recode_entities($line);
	$data = $temp_dir.$temp_folder.SLASH.$i_item.".bpda";
	$handle = fopen($data,"w");
	fwrite($handle,$line_recoded."\n");
	fclose($handle);
	$initial_controls = '';
	$chunked = FALSE;
	$tie = $n = $brackets = $total_ties = 0;
	if(is_integer($pos=strpos($line_recoded,"{"))) {
		$initial_controls = trim(substr($line_recoded,0,$pos));
		if($label <> "chunk") {
			// Pick up specified tonal scale if any
			$scale = preg_replace("/\s*_scale\(([^\,]+)[^\)]+\).+/u","$1",$line_recoded);
			if($scale <> $line_recoded) $tonal_scale = $scale;
			// Pick up initial tempo if any
			$tempo = preg_replace("/\s*_tempo\(([^\)]+)\).*/u","$1",$initial_controls);
			if($tempo <> $initial_controls) $initial_tempo = "_tempo(".$tempo.")";
			$initial_controls = '';
			}
		}
	$line_chunked = ''; $first = TRUE; $chunk_number = 1;
	$start_chunk = "[".$label;
	if($label == "chunk") $start_chunk .= " 1";
	$start_chunk .= "] ";
	$test_legato = FALSE;
	for($k = 0; $k < strlen($line_recoded); $k++) {
		$line_chunked .= $start_chunk;
		$start_chunk = '';
		$c = $line_recoded[$k];
		if($k < (strlen($line_recoded) - 1) AND ctype_alnum($c) AND $line_recoded[$k+1] == '&') {
			$tie++; $total_ties++;
			}
		if($k < (strlen($line_recoded) - 1) AND $c == '&' AND ctype_alnum($line_recoded[$k+1])) $tie--;
		if($c == '.' AND $k > 0 AND $line_recoded[$k-1]) $brackets++;
		$get_legato = get_legato($c,$line_recoded,$k);
		if($get_legato >= 0) {
			$current_legato[$layer] = $get_legato;
			if($test_legato) echo "_legato(".$current_legato[$layer].") layer ".$layer." level ".$level_bracket."<br />";
			}
		if($c == '{') {
			if($level_bracket == 0 AND !$first) $line_chunked .= $initial_controls;
			$first = FALSE;
			$line_chunked .= $c;
			$brackets++;
			$i_layer[$level_bracket] = $layer;
			$level_bracket++;
			continue;
			}
		if($c == ',') {
			$layer++;
			if(!isset($current_legato[$layer])) $current_legato[$layer] = 0;
			}
		$line_chunked .= $c;
		if($c == '}') {
			$level_bracket--;
			$layer = $i_layer[$level_bracket];
			if($level_bracket == 0) {
				$n++;
				$ok_legato = TRUE;
				foreach($current_legato as $thisfield => $the_legato) {
					if($test_legato) echo "(".$thisfield." -> ".$the_legato.")";
					if($the_legato > 0) $ok_legato = FALSE;
					}
				if(($ok_legato AND $tie <= 0 AND $n >= $minchunk_size) OR ($maxchunk_size > 0 AND $n > $maxchunk_size)) {
					$current_legato = $i_layer = array();
					$current_legato[0] = $i_layer[0] = $layer = 0;
					if(abs($tie) > 0)
						$tie_mssg .=  "• <font color=\"red\">".abs($tie)." unbound tie(s) in chunk #".$chunk_number."</font><br />";
					if(!$ok_legato)
						$tie_mssg .=  "• <font color=\"red\">legato(s) may be truncated after chunk #".$chunk_number."</font><br />";
					$line_chunked .= "\n";
					$tie = $n = 0;
					if($test_legato) echo " => ".$label." #".$chunk_number;
					$start_chunk = "[".$label;
					if($label == "chunk") $start_chunk .= " ".(++$chunk_number);
					$start_chunk .= "] ";
					if($k < (strlen($line_recoded) - 1) OR $label == "slice") $chunked = TRUE;
					}
				if($test_legato) echo "<br />";
				}
			}
		}
	if($chunked) {
		if($total_ties > 0) $tie_mssg .=  " <i>total ".$total_ties." tied notes</i><br /><font color=\"blue\">";
		$data_chunked = $temp_dir.$temp_folder.SLASH.$i_item."-".$label.".bpda";
		$handle = fopen($data_chunked,"w");
		fwrite($handle,$line_chunked."\n");
		fclose($handle);
		}
	else $data_chunked = '';
	$segment['data'] = $data;
	$segment['line_recoded'] = $line_recoded;
	$segment['tie_mssg'] = $tie_mssg;
	$segment['chunked'] = $chunked;
	$segment['chunk_number'] = $chunk_number;
	$segment['data_chunked'] = $data_chunked;
	$segment['title_this'] = $title_this;
	$segment['tonal_scale'] = $tonal_scale;
	$segment['initial_tempo'] = $initial_tempo;
	return $segment;
	}

function list_events($slice,$poly,$max_poly,$level_init,$i_token_init,$p_tempo,$q_tempo,$p_abs_time_init,$q_abs_time_init,$i_layer,$current_legato) {
	global $max_term_in_fraction;
	global $Englishnote,$Frenchnote,$Indiannote,$AltEnglishnote,$AltFrenchnote,$AltIndiannote;
	$test_fraction = FALSE;
	$test_float = FALSE;
	$test_legato = FALSE;
	$level = $level_init;
	$p_tempo_deft = $p_tempo;
	$q_tempo_deft = $q_tempo;
	$result['p_tempo'] = $p_tempo;
	$result['q_tempo'] = $q_tempo;
	$p_abs_time = $p_abs_time_init;
	$q_abs_time = $q_abs_time_init;
	$max_poly++;
	$i_poly = $max_poly;
	$p_poly_start_date = $p_abs_time;
	$q_poly_start_date = $q_abs_time;
	$poly[$i_poly]['token'] = array();
	$p_loc_time = $result['p_duration'] = $p_poly_duration = 0;
	$q_loc_time = $result['q_duration'] = $q_poly_duration = 1;
	$p_number_beats = $q_number_beats = array();
	$j_token = 0;
	$i_field = 0;
	$layer = $i_layer[$level];
	$p_number_beats[$i_field] = 0; $q_number_beats[$i_field] = 1;
	$result['error'] = '';
	$tokens = explode(" ",$slice);
	$max_tokens = count($tokens);
	for($i_token = $i_token_init; $i_token < $max_tokens; $i_token++) {
		$token = trim($tokens[$i_token]);
		if($token == '') continue;
	//	echo $token." ";
		if(is_integer($pos1=strpos($token,"_legato("))) {
			$pos2 = strpos($token,")",$pos1);
			$legato_value = substr($token,$pos1 + 8,$pos2 - $pos1 - 8);
			$current_legato[$layer] = $legato_value;
			if($test_legato) echo "_legato(".$current_legato[$layer].") layer ".$layer." level ".$level."<br />";
			}
		$p_rest = 0; $q_rest = 1;
		$table = explode("/",$token);
		if(intval($table[0]) > 0) {
			$p_rest = intval($table[0]);
			if(count($table) > 1) $q_rest = intval($table[1]);
			$simplify = simplify($p_rest * $q_tempo."/".$q_rest * $p_tempo,$max_term_in_fraction);
			$p_rest = $simplify['p'];
			$q_rest = $simplify['q'];
			$poly[$i_poly]['token'][$j_token] = "-";
			$poly[$i_poly]['field'][$j_token] = $i_field;
			$poly[$i_poly]['p_dur'][$j_token] = $p_rest;
			$poly[$i_poly]['q_dur'][$j_token] = $q_rest;
			$poly[$i_poly]['p_start'][$j_token] = $p_abs_time;
			$poly[$i_poly]['q_start'][$j_token] = $q_abs_time;
			$poly[$i_poly]['legato'][$j_token] = 100;
			$j_token++;
			$add = add($p_abs_time,$q_abs_time,$p_rest,$q_rest);
			$p_abs_time = $add['p'];
			$q_abs_time = $add['q'];
			$add = add($p_loc_time,$q_loc_time,$p_rest,$q_rest);
			$p_loc_time = $add['p'];
			$q_loc_time = $add['q'];
			$add = add($p_number_beats[$i_field],$q_number_beats[$i_field],$p_rest,$q_rest);
			$p_number_beats[$i_field] = $add['p'];
			$q_number_beats[$i_field] = $add['q'];
			continue;
			}
		if($token == "," OR $token == "}") {
			if($i_field == 0) {
				$result['p_duration'] = $p_poly_duration = $p_loc_time;
				$result['q_duration'] = $q_poly_duration = $q_loc_time;
				}
			if(isset($poly[$i_poly]['field']) AND $p_number_beats[$i_field] > 0) {
				// Let us recalculate note dates and durations in this field
				for($j = 0; $j < count($poly[$i_poly]['token']); $j++) {
					if($poly[$i_poly]['field'][$j] == $i_field) {
						// Note start date
						$add = add($poly[$i_poly]['p_start'][$j],$poly[$i_poly]['q_start'][$j],-$p_poly_start_date,$q_poly_start_date);
						$p_relative_date = $add['p'];
						$q_relative_date = $add['q'];
						$simplify = simplify(($p_relative_date * $p_poly_duration * $q_number_beats[$i_field])."/".($q_relative_date * $q_poly_duration * $p_number_beats[$i_field]),$max_term_in_fraction);
						$p_relative_date = $simplify['p'];
						$q_relative_date = $simplify['q'];
						$add = add($p_relative_date,$q_relative_date,$p_poly_start_date,$q_poly_start_date);
						$poly[$i_poly]['p_start'][$j] = $add['p'];
						$poly[$i_poly]['q_start'][$j] = $add['q'];
						// Note duration
						$simplify = simplify(($poly[$i_poly]['p_dur'][$j] * $poly[$i_poly]['legato'][$j] * $p_poly_duration * $q_number_beats[$i_field])."/".($poly[$i_poly]['q_dur'][$j] * 100 * $q_poly_duration * $p_number_beats[$i_field]),$max_term_in_fraction);
						$poly[$i_poly]['p_dur'][$j] = $simplify['p'];
						$poly[$i_poly]['q_dur'][$j] = $simplify['q'];
						// Note end date
						$add = add($poly[$i_poly]['p_start'][$j],$poly[$i_poly]['q_start'][$j],$poly[$i_poly]['p_dur'][$j],$poly[$i_poly]['q_dur'][$j]);
						$poly[$i_poly]['p_end'][$j] = $add['p'];
						$poly[$i_poly]['q_end'][$j] = $add['q'];

						if($test_fraction) echo $poly[$i_poly]['token'][$j]." date = ".$poly[$i_poly]['p_start'][$j]."/".$poly[$i_poly]['q_start'][$j]." ➡ ".$poly[$i_poly]['p_end'][$j]."/".$poly[$i_poly]['q_end'][$j]."; i_field = ".$i_field.", poly[".$i_poly."][dur] = ".$p_poly_duration."/".$q_poly_duration.", nr_beats = ".$p_number_beats[$i_field]."/".$q_number_beats[$i_field]." ➡ dur = ".$simplify['p']."/".$simplify['q']." (".$poly[$i_poly]['legato'][$j]."%)<br />";

						if($test_float) echo $poly[$i_poly]['token'][$j]." dates = ".round($poly[$i_poly]['p_start'][$j]/$poly[$i_poly]['q_start'][$j],2)." ➡ ".round($poly[$i_poly]['p_end'][$j]/$poly[$i_poly]['q_end'][$j],2)."; i_field = ".$i_field.", poly[".$i_poly."][dur] = ".round($p_poly_duration/$q_poly_duration,2).", nr_beats = ".$p_number_beats[$i_field]."/".$q_number_beats[$i_field]." ➡ dur = ".round($simplify['p']/$simplify['q'],3)." (".$poly[$i_poly]['legato'][$j]."%)<br />";
						}
					}
				}
			}
		if($token == ",") {
			$i_field++;
			$p_abs_time = $p_abs_time_init;
			$q_abs_time = $q_abs_time_init;
			$p_loc_time = 0; $q_loc_time = 1;
			$p_number_beats[$i_field] = 0;
			$q_number_beats[$i_field] = 1;
			$layer++;
			if(!isset($current_legato[$layer])) $current_legato[$layer] = 0;
			continue;
			}
		if($token == "}" OR $i_token >= ($max_tokens - 1)) {
			$result['i_token'] = $i_token;
			$result['poly'] = $poly;
			$result['max_poly'] = $max_poly;
			$result['i_layer'] = $i_layer;
			$result['level'] = $level;
			$result['current_legato'] = $current_legato;
			$result['p_abs_time'] = $p_abs_time;
			$result['q_abs_time'] = $q_abs_time;
			$layer = $i_layer[$level];
			return $result;
			}
		if($token == "{") {
			$i_layer[$level + 1] = $layer;
			$result2 = list_events($slice,$poly,$max_poly,($level + 1),($i_token + 1),$p_tempo,$q_tempo,$p_abs_time,$q_abs_time,$i_layer,$current_legato);
			$error = $result2['error'];
			if($error <> '') {
				$result['error'] = $result2['error'];
				return $result;
				}
			$i_token = $result2['i_token'];
			$p_tempo = $result2['p_tempo'];
			$q_tempo = $result2['q_tempo'];
			$poly = $result2['poly'];
			$max_poly = $result2['max_poly'];
			$add = add($p_loc_time,$q_loc_time,$result2['p_duration'],$result2['q_duration']);
			$p_loc_time = $add['p'];
			$q_loc_time = $add['q'];
			$add = add($p_abs_time,$q_abs_time,$result2['p_duration'],$result2['q_duration']);
			$p_abs_time = $add['p'];
			$q_abs_time = $add['q'];
			$add = add($p_number_beats[$i_field],$q_number_beats[$i_field],$result2['p_duration'],$result2['q_duration']);
			$p_number_beats[$i_field] = $add['p'];
			$q_number_beats[$i_field] = $add['q'];
			continue;
			}
		$newtempo = preg_replace("/_tempo\(([^\)]+)\)/u","$1",$token);
		if($newtempo <> $token) {
			$table = explode("/",$newtempo);
			$p_newtempo = $table[0]; $q_newtempo = 1;
			if(count($table) > 1) $q_newtempo = $table[1];
		//	echo " @newtempo = ".$p_newtempo."/".$q_newtempo."@@ ";
			$p_tempo = $p_newtempo;
			$q_tempo = $q_newtempo;
			$simplify = simplify($p_tempo * $p_tempo_deft."/".$q_tempo * $q_tempo_deft,$max_term_in_fraction);
			$p_tempo = $simplify['p'];
			$q_tempo = $simplify['q'];
		//	echo " @newtempo = ".$p_tempo."/".$q_tempo."@@ ";
			continue;
			}
		// Find simple note
		$i_next = $i_duration = 1;
		while(isset($tokens[$i_token + $i_next])
			AND ($more_duration=substr_count($tokens[$i_token + $i_next],'_')) > 0) {
			$next_token = str_replace("_",'',$tokens[$i_token + $i_next]);
			if($next_token <> '') break;
			$i_duration += $more_duration;
			$i_next++;
			}
		if(is_integer($pos=strpos($token,"_")) AND $pos == 0) continue;
		$tie_before = $tie_after = FALSE;
		$n_ties = substr_count($token,"&");
		if($n_ties == 2) $tie_before = $tie_after = TRUE;
		else if(is_integer($pos=strpos($token,"&")) AND $pos == 0) $tie_before = TRUE;
		else if($n_ties == 1) $tie_after = TRUE;
		$token = str_replace("&",'',$token);
		$i_token += ($i_next - 1);
		$i_duration += substr_count($token,"_");
		$token = str_replace("_",'',$token);
		$octave = intval(preg_replace("/[a-z A-Z #]+([0-9]+)/u","$1",$token));
		$poly[$i_poly]['token'][$j_token] = $token;
		$poly[$i_poly]['field'][$j_token] = $i_field;
		$poly[$i_poly]['p_dur'][$j_token] = $q_tempo * $i_duration;
		$poly[$i_poly]['q_dur'][$j_token] = $p_tempo;
		$poly[$i_poly]['p_start'][$j_token] = $p_abs_time;
		$poly[$i_poly]['q_start'][$j_token] = $q_abs_time;
		$poly[$i_poly]['legato'][$j_token] = 100 + $current_legato[$layer];
		// Calculate end date which not be revised for notes outside polymetric expressions
		$p_temp_duration = $poly[$i_poly]['p_dur'][$j_token] * $poly[$i_poly]['legato'][$j_token];
		$q_temp_duration = $poly[$i_poly]['q_dur'][$j_token] * 100;
		$add = add($poly[$i_poly]['p_start'][$j_token],$poly[$i_poly]['q_start'][$j_token],$p_temp_duration,$q_temp_duration);
		$poly[$i_poly]['p_end'][$j_token] = $add['p'];
		$poly[$i_poly]['q_end'][$j_token] = $add['q'];
		$j_token++;
		$token = str_replace($octave,'',$token);
		for($grade = 0; $grade < 12; $grade++) {
			if($token == $Englishnote[$grade]) break;
			if($token == $AltEnglishnote[$grade]) break;
			if($token == $Frenchnote[$grade]) break;
			if($token == $AltFrenchnote[$grade]) break;
			if($token == $Indiannote[$grade]) break;
			if($token == $AltIndiannote[$grade]) break;
			}
		if($token <> '-' AND ($octave == 0 OR $grade > 11)) {
			$result['error'] = "Unknown token: ".$tokens[$i_token];
			return $result;
			}
		$add = add($p_abs_time,$q_abs_time,$q_tempo * $i_duration,$p_tempo);
		$p_abs_time = $add['p'];
		$q_abs_time = $add['q'];
		$add = add($p_loc_time,$q_loc_time,$q_tempo * $i_duration,$p_tempo);
		$p_loc_time = $add['p'];
		$q_loc_time = $add['q'];
		$add = add($p_number_beats[$i_field],$q_number_beats[$i_field],$q_tempo * $i_duration,$p_tempo);
		$p_number_beats[$i_field] = $add['p'];
		$q_number_beats[$i_field] = $add['q'];
		}
	$result['poly'] = $poly;
	$result['max_poly'] = $max_poly;
	$result['i_token'] = $i_token;
	$result['i_layer'] = $i_layer;
	$result['level'] = $level;
	$result['current_legato'] = $current_legato;
	$result['p_abs_time'] = $p_abs_time;
	$result['q_abs_time'] = $q_abs_time;
	return $result;
	}

function make_event_table($poly) {
	// All start/end dates will become integers to facilitate comparisons
	global $max_term_in_fraction;
	$lcm = 1;
	$too_big = FALSE;
	foreach($poly as $i_poly => $this_poly) {
		for($j_token = 0; $j_token < count($this_poly['token']); $j_token++) {
			$lcm = lcm($lcm,$this_poly['q_start'][$j_token]);
			$lcm = lcm($lcm,$this_poly['q_end'][$j_token]);
			if($lcm > $max_term_in_fraction) {
				$too_big = TRUE;
				$lcm = $max_term_in_fraction;
				break;
				}
			}
		if($too_big) break;
		}
	/* echo "lcm = ".$lcm;
	if($too_big) echo " (too  big value)";
	echo "<br />"; */
	$i = 0; $table = array();
	foreach($poly as $i_poly => $this_poly) {
		for($j_token = 0; $j_token < count($this_poly['token']); $j_token++) {
			$start = round(($poly[$i_poly]['p_start'][$j_token] * $lcm) / $poly[$i_poly]['q_start'][$j_token]);
			$end = round(($poly[$i_poly]['p_end'][$j_token] * $lcm) / $poly[$i_poly]['q_end'][$j_token]);
			if($poly[$i_poly]['token'][$j_token] == "-") continue;
			$table[$i]['token'] = $poly[$i_poly]['token'][$j_token];
			$table[$i]['start'] = $start;
			$table[$i]['end'] = $end;
			$i++;
			}
		}
	usort($table,"date_sort");
	$result['table'] = $table;
	$result['lcm'] = $lcm;
	return $result;
	}

function match_notes($table_events,$mode,$min_duration,$max_gap,$test_intervals,$lcm) {
	$matching_notes = $match = array();
	$i_match = 0;
	if($test_intervals) echo "Dates in seconds:<br />";
	for($i_event = 0; $i_event < (count($table_events) - 1); $i_event++) {
		$start1 = $table_events[$i_event]['start'];
		$end1 = $table_events[$i_event]['end'];
		for($j_event = ($i_event + 1); $j_event < count($table_events); $j_event++) {
			$found = FALSE;
			$start2 = $table_events[$j_event]['start'];
			$end2 = $table_events[$j_event]['end'];
			if(matching_intervals($start1,$end1,$start2,($min_duration * $lcm / 1000),($max_gap * $lcm / 1000),$end2,$mode,$lcm)) {
				$token1 = preg_replace("/([a-z A-Z #]+)[0-9]*/u","$1",$table_events[$i_event]['token']);
				$token2 = preg_replace("/([a-z A-Z #]+)[0-9]*/u","$1",$table_events[$j_event]['token']);
				if($token1 == $token2) continue;
				if(!isset($match[$token1][$token2]) AND !isset($match[$token2][$token1])) {
					$match[$token1][$token2] = $found = TRUE;
					$matching_notes[$i_match][0] = $token1;
					$matching_notes[$i_match][1] = $token2;
					if($mode == "melodic") $matching_notes[$i_match]['score'] = 1;
					else $matching_notes[$i_match]['score'] = $end1 - $start1;
					$i_match++;
					}
				else {
					for($j_match = 0; $j_match < $i_match; $j_match++) {
						if(($matching_notes[$j_match][0] == $token1) AND ($matching_notes[$j_match][1] == $token2)) {
							if($mode == "melodic") $matching_notes[$j_match]['score']++;
							else $matching_notes[$j_match]['score'] += $end1 - $start1;
							$match[$token1][$token2] = $found = TRUE;
							}
						if(($matching_notes[$j_match][0] == $token2) AND ($matching_notes[$j_match][1] == $token1)) {
							if($mode == "melodic") $matching_notes[$j_match]['score']++;
							else $matching_notes[$j_match]['score'] += $end2 - $start2;
							$match[$token1][$token2] = $found = TRUE;
							}
						if($found) break;
						}
					}
				}
			if($found AND $test_intervals) {
				if($mode == "harmonic")
					echo $token1." [".round($start1/$lcm,1)." ↔︎ ".round($end1/$lcm,1)."] ≈ ".$token2." [".round($start2/$lcm,1)." ↔︎ ".round($end2/$lcm,1)."]<br />";
				else
					echo $token1." [".round($start1/$lcm,1)." ↔︎ ".round($end1/$lcm,1)."] ▹ ".$token2." [".round($start2/$lcm,1)." ↔︎ ".round($end2/$lcm,1)."]<br />";				
				}
			}
		}
	if($test_intervals) echo "<hr>";
	$result['matching_notes'] = $matching_notes;
	$result['max_match'] = $i_match;
	return $result;
	}

function matching_intervals($start1,$end1,$start2,$min_dur,$max_gap,$end2,$mode,$lcm) {
	// Because of the sorting of events, $start2 >= $start1
	$duration1 = $end1 - $start1;
	$duration2 = $end2 - $start2;
	$overlap = $end1 - $start2;
	$smallest_duration = $duration1;
	if($duration2 < $duration1) $smallest_duration = $duration2;
	if($mode == "harmonic") {
		if($smallest_duration < $min_dur) return FALSE;
		if($start1 + ($duration1 / 2.) < $start2) return FALSE;
		if($overlap < (0.25 * $smallest_duration)) return FALSE;
		// Here we discard slurs (generally 20% when importing MusicXML files)
		}
	else { // "melodic"
		if($start2 > ($end1 + $max_gap)) return FALSE;
		if($start1 + ($duration1 / 2.) >= $start2) return FALSE;
		if($overlap >= (0.25 * $smallest_duration)) return FALSE;
		}
	return TRUE;
	}

function show_relations_on_image($i_item,$matching_list,$mode,$scalename,$note_convention) {
	global $dir_scale_images,$temp_dir,$temp_folder,$dir_scale_images;
	global $Englishnote,$Frenchnote,$Indiannote;

	$save_codes_dir = $temp_dir.$temp_folder.SLASH.$scalename."_codes_".$mode."_".$i_item.SLASH;
//	echo "save_codes_dir = ".$save_codes_dir."<br />";
	if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
	$matching_notes = $matching_list[$i_item][$mode];
	$width_max = 8;
	for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
		if($matching_notes[$i_match]['percent'] < 6) $width[$i_match] = 0;
		else $width[$i_match] = 6 + round((($matching_notes[$i_match]['percent'] * $width_max) / 100));
		$position[$i_match][0] = note_position($matching_notes[$i_match][0]);
		$position[$i_match][1] = note_position($matching_notes[$i_match][1]);
		// We'll use note names of the score:
		$note_name[$position[$i_match][0]] = $matching_notes[$i_match][0];
		$note_name[$position[$i_match][1]] = $matching_notes[$i_match][1];
		}
	$found = FALSE;
	if($scalename <> '') {
		$dircontent = scandir($temp_dir);
		foreach($dircontent as $resource_file) {
			if(!is_dir($temp_dir.$resource_file)) continue;
	//		echo $resource_file."<br />";
			if(is_integer($pos=strpos($resource_file,"-cs")) AND $pos == 0) {
				$scale_textfile = $temp_dir.$resource_file.SLASH."scales".SLASH.$scalename.".txt";
			//	echo $scale_textfile." ???<br />";
				if(file_exists($scale_textfile)) {
					$found = TRUE;
					break;
					}
				}
			}
		if(!$found) {
			if($mode == "harmonic") {
				echo "<p style=\"text-align:center;\"><font color=\"red\">Definition of tonal scale</font> ‘<font color=\"blue\">".$scalename."</font>’ <font color=\"red\">was not found.</font><br />";
				echo "You need to open a <a target=\"_blank\" href=\"index.php?path=csound_resources\">Csound resource</a> containing a scale with exactly the same name.</p><br />";
				}
			$resource_file = '';
			}
		}
	else $resource_file = '';
	if(!$found) {
		$scale_textfile = "equal_tempered.txt";
		$scalename = "equal-tempered";
		}
	$found = FALSE;
	$content = @file_get_contents($scale_textfile,TRUE);
	$table = explode(chr(10),$content);
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if(is_integer($pos=strpos($line,"f")) AND $pos == 0) {
		//	echo $line."<br />";
			$table2 = explode(" ",$line);
			if(count($table2) < 21) {
				echo "<font color=\"red\">Definition of tonal scale</font> ‘<font color=\"blue\">".$scalename."</font>’ <font color=\"red\">is not compliant.</font><br />";
				echo "➡ Check ‘<font color=\"blue\">".$scale_textfile."</font>’ in the opened Csound resource.<br />";
				break;
				}
			$numgrades = $table2[4];
			if($numgrades <> 12) break;
			else {
				for($grade = 0; $grade < 13; $grade++) {
					$ratio[$grade] = $table2[8 + $grade];
				//	echo $ratio[$grade]." ";
					}
				$found = TRUE;
				}
			}
		if(is_integer($pos=strpos($line,"[")) AND $pos == 0) {
			$line = str_replace("[",'',$line);
			$line = str_replace("]",'',$line);
			$table2 = explode(" ",$line);
			for($grade = 0; $grade < 13; $grade++) {
				$p[$grade] = $table2[0 + (2 * $grade)];
				$q[$grade] = $table2[1 + (2 * $grade)];
				}
			}
		if(is_integer($pos=strpos($line,"/")) AND $pos == 0) {
			$line = str_replace("/",'',$line);
			$table2 = explode(" ",$line);
			for($grade = 0; $grade < 13; $grade++) {
				if(!isset($note_name[$grade])) {
					$this_note = $table2[$grade];
					$this_position = note_position($this_note);
					if($note_convention == 0) $this_note = $Englishnote[$this_position];
					else if($note_convention == 1) $this_note = $Frenchnote[$this_position];
					else $this_note = $Indiannote[$this_position];
					$note_name[$grade] = $this_note;
					}
				}
			}
		}
	if($found) {
		$image_height = 820;
		$image_width = 800;
		$handle = fopen($save_codes_dir.SLASH."image.php","w");
		$content = "<?php\n§filename = \"".$scalename."\";\n";
		$content .= "§image_height = \"".$image_height."\";\n";
		$content .= "§image_width = \"".$image_width."\";\n";
		$content .= "§interval_cents = \"1200\";\n";
		$content .= "§syntonic_comma = \"21.506289596715\";\n";
		$content .= "§p_comma = \"81\";\n";
		$content .= "§q_comma = \"80\";\n";
		$content .= "§numgrades_fullscale = \"12\";\n";
		for($grade = 0; $grade < 13; $grade++) {
			$content .= "§ratio[".$grade."] = \"".$ratio[$grade]."\";\n";
			$content .= "§series[".$grade."] = \"\";\n";
			$content .= "§name[".$grade."] = \"".$note_name[$grade]."\";\n";
			$content .= "§cents[".$grade."] = \"".cents($ratio[$grade])."\";\n";
			$content .= "§p[".$grade."] = \"".$p[$grade]."\";\n";
			$content .= "§q[".$grade."] = \"".$q[$grade]."\";\n";
			}
		$harmonic_third = cents(5/4);
		$pythagorean_third = cents(81/64);
		$wolf_fifth = cents(40/27);
		$perfect_fifth = cents(3/2);
		$content .= "§harmonic_third = \"".$harmonic_third."\";\n";
		$content .= "§pythagorean_third = \"".$pythagorean_third."\";\n";
		$content .= "§wolf_fifth = \"".$wolf_fifth."\";\n";
		$content .= "§perfect_fifth = \"".$perfect_fifth."\";\n";
		for($j = 0; $j < 12; $j++) {
			for($k = 0; $k < 12; $k++) {
				if($j == $k) continue;
				$pos = cents($ratio[$k] / $ratio[$j]);
				if($pos < 0) $pos += 1200;
				$dist = $pos - $harmonic_third;
				if(abs($dist) < 10) $content .= "§harmthird[".$j."] = \"".$k."\";\n";
				}
			}
		for($j = 0; $j < 12; $j++) {
			for($k = 0; $k < 12; $k++) {
				if($j == $k) continue;
				$pos = cents($ratio[$k] / $ratio[$j]);
				if($pos < 0) $pos += 1200;
				$dist = $pos - $pythagorean_third;
				if(abs($dist) < 10) $content .= "§pyththird[".$j."] = \"".$k."\";\n";
				}
			}
		for($j = 0; $j < 12; $j++) {
			for($k = 0; $k < 12; $k++) {
				if($j == $k) continue;
				$pos = cents($ratio[$k] / $ratio[$j]);
				if($pos < 0) $pos += 1200;
				$dist = $pos - $perfect_fifth;
				if(abs($dist) < 10) $content .= "§fifth[".$j."] = \"".$k."\";\n";
				}
			}
		for($j = 0; $j < 12; $j++) {
			for($k = 0; $k < 12; $k++) {
				if($j == $k) continue;
				$pos = cents($ratio[$k] / $ratio[$j]);
				if($pos < 0) $pos += 1200;
				$dist = $pos - $wolf_fifth;
				if(abs($dist) < 10) $content .= "§wolffifth[".$j."] = \"".$k."\";\n";
				}
			}
		// Create yellow links between matching notes
		for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
			$w = $width[$i_match];
			$j = $position[$i_match][0];
			$k = $position[$i_match][1];
		//	echo $j." with ".$k." width = ".$w."<br />";
			$content .= "§hilitej[".$i_match."] = \"".$j."\";\n";
			$content .= "§hilitek[".$i_match."] = \"".$k."\";\n";
			$content .= "§hilitewidth[".$i_match."] = \"".$w."\";\n";
			}
		$content = str_replace('§','$',$content);
		fwrite($handle,$content);
		$line = "§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		$image_name = $i_item."_".clean_folder_name($scalename)."_image_".$mode;
		$image_name_full = $image_name."_full";
		$image_name_reduced = $image_name."_reduced";
		$image_name_only = $image_name."_only";
		//echo "image_name = ".$image_name."<br />";
		$link = "scale_image.php?save_codes_dir=".urlencode($save_codes_dir)."&dir_scale_images=".urlencode($save_codes_dir);
		$link_full = $link."&csound_source=".urlencode('item #'.$i_item.' ('.$mode.')');
		$link_reduced = $link_full."&no_marks=1&no_intervals=1&no_cents=1";
		$link_only = $link."&no_hilite=1";
		if($mode == "harmonic") {
			$side = "right"; $left_position = 100;
			}
		else {
			$side = "left"; $left_position = 0; // Doesn't seem to work!
			}
		echo "<div class=\"shadow\" style=\"border:2px solid gray; background-color:azure; width:15em;  padding:8px; text-align:center; border-radius: 6px; float:".$side.";\">SHOW IMAGE (".$mode.")<br />";
		if($scalename <> '') echo "‘".$scalename."’<br />";
		echo "<a onclick=\"window.open('".$link_full."','".$image_name_full."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_full."\">full</a>";
		echo "&nbsp;-&nbsp;<a onclick=\"window.open('".$link_only."','".$image_name_only."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_only."\">only scale</a>";
		echo "&nbsp;-&nbsp;<a onclick=\"window.open('".$link_reduced."','".$image_name_reduced."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_reduced."\">only links</a></div>";
		}
	$result['scalename'] = $scalename;
	$table = explode('_',$resource_file);
	$resource_name = $table[0];
	$result['resource_name'] = $resource_name;
	return $result;
	}
?>
