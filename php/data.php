<?php
if(isset($_POST['download_capture_file'])) {
	$capture_file_name = $_POST['capture_file_name'];
	$capture_file = $_POST['capture_file'];
	if(headers_sent()) die("Headers already sent. Cannot start file download.");
	if(file_exists($capture_file)) {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="'.basename($capture_file_name).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: '.filesize($capture_file));
		if(ob_get_level())
			ob_end_clean();  // This avoids downloading the header of this page.
		ob_clean();
    	flush();
		readfile($capture_file);
		exit;
		}
	}
require_once("_basic_tasks.php");
require_once("_musicxml.php");
require_once("_tonal_analysis.php");
set_time_limit(0);

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "data.php?file=".urlencode($file);
save_settings("last_data_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
echo "<script>
window.name = '".$filename."'
</script>";
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
$current_directory = str_replace(SLASH,'/',$current_directory);
save_settings("last_data_directory",$current_directory);

if(isset($_POST['delete_capture_file'])) {
	$capture_file = $_POST['capture_file'];
	@unlink($capture_file);
	$trashed = TRUE;
	}
else $trashed = FALSE;
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_FILES['uploaded_capture_file']) AND $_FILES['uploaded_capture_file']['error'] === UPLOAD_ERR_OK) {
		$capture_file = $_POST['capture_file'];
        $fileTmpPath = $_FILES['uploaded_capture_file']['tmp_name'];
        $fileName = $_FILES['uploaded_capture_file']['name'];
        $fileSize = $_FILES['uploaded_capture_file']['size'];
        $fileType = $_FILES['uploaded_capture_file']['type'];
    	if(move_uploaded_file($fileTmpPath,$capture_file)) {
			chmod($capture_file,$permissions);
            echo "<p>👉 File uploaded successfully: <span class=\"green-text\">".$fileName."</span></p>";
        	}
		else echo "<p class=\"red-text\">👉 Error moving the uploaded file</p>";
    	}
	else if(!$trashed AND isset($_POST['upload_capture_file'])) echo "<p class=\"red-text\">👉 No file of captured MIDI data has been chosen…</p>";
	}

if(isset($_POST['reload'])) {
    $refresh_file = $temp_dir."trace_".my_session_id()."_".$filename."_midiport_refresh";
	@unlink($refresh_file);
    header("Location: ".$url_this_page);
    exit();	
	}
require_once("_header.php");

if(isset($_POST['stop_analysis'])) unset($_POST['analyze_tonal']);

$file_format = $default_output_format;
if(isset($data_file_format[$filename])) $file_format = $data_file_format[$filename];
if(!isset($_POST['analyze_tonal'])) display_console_state();

$url = "index.php?path=".urlencode($current_directory);
echo "&nbsp;Workspace = <input title=\"List this workspace\" title=\"List this workspace\" class=\"edit\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\">";

echo link_to_help();

$test_musicxml = FALSE;
$no_chunk_real_time_midi = FALSE;
$save_warning = '';
$new_convention = '';
$this_score = array();

echo "<h2>Data project “".$filename."”</h2>";
save_settings("last_data_name",$filename);

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
$music_xml_file = $temp_dir.$temp_folder.SLASH."temp.musicxml";
$capture_file = $temp_dir."trace_".my_session_id()."_".$filename."_capture";
$more_data = ''; $dynamic_control = array();
$link_edit = "data.php";

$temp_midi_ressources = $temp_dir."trace_".my_session_id()."_".$filename."_";

$objects_file = $csound_file = $tonality_file = $alphabet_file = $grammar_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = $csound_default_orchestra = '';

if(isset($_POST['alphabet_file'])) $alphabet_file = $_POST['alphabet_file'];
if(isset($_POST['grammar_file'])) $grammar_file = $_POST['grammar_file'];
if(isset($_POST['timebase_file'])) $timebase_file = $_POST['timebase_file'];
if(isset($_POST['settings_file'])) $settings_file = $_POST['settings_file'];
if(isset($_POST['csound_file'])) $csound_file = $_POST['csound_file'];
if(isset($_POST['tonality_file'])) $tonality_file = $_POST['tonality_file'];
if(isset($_POST['objects_file'])) $objects_file = $_POST['objects_file'];

/* if(isset($_POST['new_convention']))
	$new_convention = $_POST['new_convention'];
else $new_convention = ''; */

if(isset($_POST['select_parts'])) {
	$upload_filename = $_POST['upload_filename'];
	$reload_musicxml = TRUE;
	}
else $reload_musicxml = FALSE;

$need_to_save = $error = FALSE;
$no_save_midiresources = FALSE;
$error_mssg = '';

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if($reload_musicxml OR (isset($_FILES['music_xml_import']) AND $_FILES['music_xml_import']['tmp_name'] <> '')) {
	$no_save_midiresources = TRUE;
	if(!$reload_musicxml) $upload_filename = $_FILES['music_xml_import']['name'];
	if(!$reload_musicxml AND $_FILES["music_xml_import"]["size"] > MAXFILESIZE) {
		echo "<h3><span class=\"red-text\">Uploading failed:</span> <span class=\"green-text\">".$upload_filename."</span> <span class=\"red-text\">is larger than ".MAXFILESIZE." bytes</span></h3>";
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
		$save_content = preg_replace("/ +/u",' ',$save_content);
		save($this_file,$filename,$top_header,$save_content);
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
		if($tonality_file <> '') {
			$declarations .= $tonality_file."\n";
			$content = str_replace($tonality_file,'',$content);
			}
		if($timebase_file <> '') {
			$declarations .= $timebase_file."\n";
			$content = str_replace($timebase_file,'',$content);
			}
		$_POST['thistext'] = $content;
		if(!$reload_musicxml) {
			$tmpFile = $_FILES['music_xml_import']['tmp_name'];
			move_uploaded_file($tmpFile,$music_xml_file) or die('Problem uploading this MusicXML file');
			chmod($music_xml_file,0666);
			$table = explode('.',$upload_filename);
			$extension = end($table);
			}
		if(!$reload_musicxml AND $extension <> "musicxml" AND $extension <> "xml") {
			echo "<h4><span class=\"red-text\">Uploading failed:</span> <span class=\"green-text\">".$upload_filename."</span> <span class=\"red-text\">does not have the extension of a MusicXML file!</span></h4>";
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
			$create_channels = isset($_POST['create_channels']);
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
			$list_settings = '';
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
			$repeat_start_measure[1] = 1; // Added by BB 2022-03-13
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
			if(($change_metronome_average + $change_metronome_min + $change_metronome_max) > 0) {
				if($change_metronome_average == 0) $change_metronome_average = $current_metronome_average;
				if($change_metronome_min == 0) $change_metronome_min = $current_metronome_min;
				if($change_metronome_max == 0) $change_metronome_max = $current_metronome_max;
				$list_settings .= "// Changed metronome to min ".$change_metronome_min.", average ".$change_metronome_average.", max ".$change_metronome_max."\n";
				}
			if($change_metronome_min < 1 AND ($change_metronome_max > 0 OR $change_metronome_average > 0))
				$error_change_metronome .= "<span class=\"red-text\">ERROR changing metronome = minimum value should be positive</span><br />";
			if(($change_metronome_min >= $change_metronome_max OR $change_metronome_average <= $change_metronome_min OR $change_metronome_average >= $change_metronome_max) AND ($change_metronome_max > 0 OR $change_metronome_average > 0))
				$error_change_metronome .= "<span class=\"red-text\">ERROR changing metronome: values are not compatible</span><br />";
			if($error_change_metronome <> '') $change_metronome_average = $change_metronome_min = $change_metronome_max = 0;
			$xml_content = @file_get_contents($music_xml_file);
    		$xml_content = str_replace("\r","\n",$xml_content);
    	//	$xml_content = str_replace("\n\n","\n",$xml_content);
			if(MB_CONVERT_OK) $xml_content = mb_convert_encoding($xml_content,'UTF-8','UTF-8');
			$xml_table = explode(chr(10),$xml_content);
			$print_info = FALSE;
			$new_section = FALSE;
			$beat_unit = "quarter";
			$fifths = $mode = array();
			$jmax = count($xml_table);
			if($jmax == 0) {
				echo "<p>👉 <span class=\"red-text\">ERROR: this MusicXML file is unreadable or empty</span></p>";
				goto ENDLOADXML;
				}
			for($j = 0; $j < $jmax; $j++) {
				$line = $xml_table[$j];
				if(trim($line) == '') continue;
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
						if($create_channels AND isset($midi_channel[$score_part]) AND $midi_channel[$score_part] <> '') {
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
						$new_section = TRUE; // Added by BB 2022-03-13
						}
					$add_section = FALSE;
					}
				if($reading_measure AND is_integer($pos=strpos($line,"</measure>"))) {
					$reading_measure = FALSE;
					$new_section = FALSE;
					$number_measures++;
					}
				if($reading_measure AND is_integer($pos=strpos($line,"<repeat "))) {
					$repeat_direction = trim(preg_replace("/.+direction=\"([^\"]+)\"\/>/u","$1",$line));
					if($test_musicxml) echo "repeat direction = “".$repeat_direction."” section ".$section."<br />";
					if($repeat_direction == "forward") {
						if(!$new_section) $section++;
						$new_section = FALSE;
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
			$i_section =  0;
			foreach($this_score as $section => $the_section) {
			//	echo "section = ".$section."<br />";
				if(count($the_section) > 0) $i_section++;
				if(isset($repeat_start_measure[$section]) AND isset($repeat_end_measure[$section])) {
					echo "• Section #".$i_section." is repeated from measure ".$repeat_start_measure[$section]." to ".$repeat_end_measure[$section]."<br />";
					}
				}
			unset($the_section);
			if($number_metronome > 0)
				$metronome_average = round($sum_metronome / $number_metronome);
			else $metronome_average = 0;
		//	$list_settings = '';
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
				if($create_channels) $list_settings .= "// Created MIDI channels\n";
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
			$convert_score = convert_musicxml($this_score,$repeat_section,$divisions,$fifths,$mode,$midi_channel,$dynamic_control,$select_part,$ignore_dynamics,$tempo_option,$create_channels,$include_breaths,$include_slurs,$include_measures,$ignore_fermata,$ignore_mordents,$chromatic_mordents,$ignore_turns,$chromatic_turns,$ignore_trills,$chromatic_trills,$ignore_arpeggios,$reload_musicxml,$test_musicxml,$change_metronome_average,$change_metronome_min,$change_metronome_max,$current_metronome_average,$current_metronome_min,$current_metronome_max,$list_corrections,$trace_tempo,$trace_ornamentations,$breath_length,$breath_tag,$trace_measures,$measures,$accept_signs,$include_parts,$number_parts,$apply_rndtime,$rndtime,$apply_rndvel,$rndvel,$extend_last_measure,$number_measures,$accept_pedal);
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

			echo "<h3><span class=\"red-text\">Importing MusicXML file:</span> <span class=\"green-text\">".$upload_filename."</span></h3>";
		//	echo "<div style=\"background-color:white; width:75%; padding:1em; box-shadow: -5px 5px 5px 0px gold;\">";
			echo "<div style=\"width:75%; padding:1em; box-shadow: -5px 5px 5px 0px gold;\">";
			$window_name = $upload_filename;
			$link_preview = "preview_musicxml.php?music_xml_file=".urlencode($music_xml_file)."&title=".urlencode($upload_filename);
			echo "<input class=\"produce\" onclick=\"window.open('".$link_preview."','".$window_name."','width=600,height=800,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"PREVIEW MusicXML file\" title=\"\"> <b>(simplified)</b><br /><br />";
			if($report <> '') {
				echo $report;
				echo "<hr>";
				}
			if($number_parts > 1) echo $message_top;
			else if(!$reload_musicxml) $create_channels = FALSE;
			echo $message_options;
			echo "<input type=\"checkbox\" name=\"create_channels\"";
			if($create_channels) echo " checked";
			echo ">&nbsp;Interpret parts as MIDI channels<br />";
			if($number_parts > 1) {
				echo "<input type=\"checkbox\" name=\"include_parts\"";
				if($include_parts) echo " checked";
				echo ">&nbsp;Include “_part()” instructions<br />";
				}
			else $include_parts = FALSE;
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
				echo "<tr><td><b>Metronome</b></td><td>current:</td><td>set it to…</td></tr>";
				echo "<tr><td><i>Average</i></td><td>".$current_metronome_average." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_average\" size=\"6\" value=\"";
				if($change_metronome_average > 0) echo $change_metronome_average;
				echo "\"> bpm (approx)</td></tr>";
				echo "<tr><td><i>Minimum</i></td><td>".$current_metronome_min." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_min\" size=\"6\" value=\"";
				if($change_metronome_min > 0) echo $change_metronome_min;
				echo "\"> bpm</td></tr>";
				echo "<tr><td><i>Maximum</i></td><td>".$current_metronome_max." bpm</td><td><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_metronome_max\" size=\"6\" value=\"";
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
					echo ">&nbsp;<span class=\"red-text\">➡</span> Interpret ‘pedal’ commands in part ‘".$part_label[$i]."’:";
					echo "&nbsp;controler #<input type=\"text\" style=\"border:none; text-align:center;\" name=\"".$index2."\" size=\"5\" value=\"".$switch_controler[$i]."\"> (64 to 95) on MIDI channel <input type=\"text\" style=\"border:none; text-align:center;\" name=\"".$index3."\" size=\"5\" value=\"".$switch_channel[$i]."\"> (1 to 16)";
					$this_link_preview = $link_preview."&filter=pedal";
					$window_name = $upload_filename."_trill";
					if(!$found_pedal_command) echo "&nbsp;<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">";
					echo "<br />";
					$found_pedal_command = TRUE;
					}
				else $accept_pedal[$i] = FALSE;
				}
			if($found_trill) {
				echo "<input type=\"checkbox\" name=\"ignore_trills\"";
				if($ignore_trills) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Ignore trills (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=trill";
				$window_name = $upload_filename."_trill";
				echo "<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=200'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_trills\"";
				if($chromatic_trills) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_trills = FALSE;
			if($found_mordent) {
				echo "<input type=\"checkbox\" name=\"ignore_mordents\"";
				if($ignore_mordents) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Ignore mordents (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=mordent";
				$window_name = $upload_filename."_mordent";
				echo "<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=150'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_mordents\"";
				if($chromatic_mordents) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_mordents = FALSE;
			if($found_turn) {
				echo "<input type=\"checkbox\" name=\"ignore_turns\"";
				if($ignore_turns) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Ignore turns (some have been found in this score)";
				$link_preview .= "&filter=turn";
				$window_name = $upload_filename."_turn";
				echo "<input class=\"produce\" onclick=\"window.open('".$link_preview."','".$window_name."','width=600,height=400,left=100'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\">&nbsp;";
				echo "<input type=\"checkbox\" name=\"chromatic_turns\"";
				if($chromatic_turns) echo " checked";
				echo ">&nbsp;➡&nbsp;make them chromatic<br />";
				}
			else $ignore_turns = FALSE;
			if($found_fermata) {
				echo "<input type=\"checkbox\" name=\"ignore_fermata\"";
				if($ignore_fermata) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Ignore fermata (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=fermata";
				$window_name = $upload_filename."_fermata";
				echo "&nbsp;<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=50'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_fermata = FALSE;
			if($found_arpeggio) {
				echo "<input type=\"checkbox\" name=\"ignore_arpeggios\"";
				if($ignore_arpeggios) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Ignore arpeggios (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=arpeggio";
				$window_name = $upload_filename."_arpeggio";
				echo "&nbsp;<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				}
			else $ignore_arpeggios = FALSE;
			if($found_slur) {
				echo "<input type=\"checkbox\" name=\"include_slurs\"";
				if($include_slurs) echo " checked";
				echo ">&nbsp;<span class=\"red-text\">➡</span> Interpret slurs (some have been found in this score)";
				$this_link_preview = $link_preview."&filter=slur";
				$window_name = $upload_filename."_slur";
				echo "&nbsp;<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
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
				echo ">&nbsp;<input type=\"text\" style=\"border:none; text-align:center;\" name=\"rndtime_".$i."\" size=\"3\" value=\"".$rndtime[$i]."\"> millisecond(s)";
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
				echo "&nbsp;<input class=\"produce\" onclick=\"window.open('".$this_link_preview."','".$window_name."','width=600,height=400,left=0'); return false;\" type=\"submit\" name=\"preview\" value=\"preview in file\" title=\"\"><br />";
				echo "&nbsp;&nbsp;&nbsp;… with breath length = <input type=\"text\" style=\"border:none; text-align:center;\" name=\"p_breath_length\" size=\"2\" value=\"".$p_breath_length."\">/<input type=\"text\" style=\"border:none; text-align:center;\" name=\"q_breath_length\" size=\"2\" value=\"".$q_breath_length."\"> beat(s)<br />";
				echo "&nbsp;&nbsp;&nbsp;… and breath tag = <input type=\"text\" style=\"border:none; text-align:center;\" name=\"breath_tag\" size=\"4\" value=\"".$breath_tag."\"><br />";
				}
			else $include_breaths = FALSE;
			echo "<input type=\"checkbox\" name=\"include_measures\"";
			if($include_measures) echo " checked";
			echo ">&nbsp;Insert measure numbers [—n—]<br />";
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
			echo "<span class=\"red-text\">➡</span> Now, select parts and <input class=\"produce\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"select_parts\" value=\"CONVERT\">&nbsp;or&nbsp;<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"cancel\" value=\"QUIT IMPORTING\">";
			echo "</div>";
			if($found_pedal_command AND $reload_musicxml) {
				for($i = 0; $i < $number_parts; $i++) {
					if(!isset($switch_controler[$i]) OR !isset($switch_channel[$i])) {
					//	echo "<p>Switch error ".$i."</p>";
						continue;
						}
					$more_data = str_replace("_switch_on_part(".($i + 1).")"," _switchon(".$switch_controler[$i].",".$switch_channel[$i].") ",$more_data);
					$more_data = str_replace("_switch_off_part(".($i + 1).")"," _switchoff(".$switch_controler[$i].",".$switch_channel[$i].") ",$more_data);
					}
				}
			$new_convention = 0; // English note convention
			$need_to_save = TRUE;
			}
		}
	}

ENDLOADXML:
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
	$no_save_midiresources = TRUE;
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
	$no_save_midiresources = TRUE;
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
		$no_save_midiresources = TRUE;
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
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	$error_mssg .= "<br /><span class=\"red-text blinking\">WARNING: this is a new tag/window. Close the previous one to avoid mixing versions!</span><br />";
	$error = TRUE;
	}

if(isset($_POST['use_convention'])) {
	$new_convention = use_convention($this_file);
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	echo "<div class=\"warning\">👉 Current note convention for this data will now be <span class=\"red-text\">‘".ucfirst(note_convention(intval($new_convention)))."’</span>. If necessary, change it in the settings file.</div>";
	}

if(isset($_POST['delete_chan'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_chan\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_ins'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_ins\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_part'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_part\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}

if(isset($_POST['delete_tempo'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_tempo\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['volume_velocity'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_volume\(([^\)]+)\)/u","_vel($1)",$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['velocity_volume'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_vel\(([^\)]+)\)/u","_volume($1)",$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
	$need_to_save = TRUE;
	}
	
if(isset($_POST['delete_velocity'])) {
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$newcontent = $extract_data['content'];
	$newcontent = preg_replace("/_vel\([^\)]+\)/u",' ',$newcontent);
	$_POST['thistext'] = $newcontent;
	$no_save_midiresources = TRUE;
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
	else echo "<p><span class=\"red-text\">➡ Modified values of velocity “".$_POST['change_velocity_average']."” and “".$_POST['change_velocity_max']."” are missing or out of range!</span></p>";
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
	else echo "<p><span class=\"red-text\">➡ Modified values of volume “".$_POST['change_volume_average']."” and “".$_POST['change_volume_max']."” are missing or out of range!</span></p>";
	}
	
if(isset($_POST['apply_changes_instructions'])) {
	$content = @file_get_contents($this_file,TRUE);
	$newcontent = apply_changes_instructions($content);
	$_POST['thistext'] = str_replace("@&",'',$newcontent);
	$need_to_save = TRUE;
	}

$refresh_file = $temp_dir."trace_".my_session_id()."_".$filename."_midiport_refresh";
if(isset($_POST['savemidiport'])) {
	save_midiressources($filename,TRUE);
	$save_warning = "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;…&nbsp;Saved “".$filename."_midiport” file…</span>";
	@unlink($refresh_file);
	}

if($need_to_save OR isset($_POST['savethisfile'])) {
	$save_warning = "<p id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">Saved ‘".$filename."’file…</p>";
	if(isset($_POST['thistext'])) $content = $_POST['thistext'];
	$file_format = $default_output_format;
	if(isset($data_file_format[$filename])) $file_format = $data_file_format[$filename];
//	else $content = '';
	if($more_data <> '') $content = $more_data."\n\n".$content;
	$content = preg_replace("/ +/u",' ',$content);
	save($this_file,$filename,$top_header,$content);
	if($file_format == "rtmidi" AND !$no_save_midiresources) {
		if(file_exists($refresh_file)) {
			read_midiressources($filename);
			store_midiressources($filename);
			@unlink($refresh_file);
			}
		else save_midiressources($filename,FALSE);
		}
	}
else read_midiressources($filename);

$upload_message = upload_project("data");
if($upload_message <> '') $need_to_save = FALSE;
$undo_upload_project_message = undo_upload_project();

try_create_new_file($this_file,$filename);
/* echo "this_file = ".$this_file." permissions = ".$permissions."<br >";
chmod($this_file,"0775"); 
exec("chmod 755 ".$this_file); */
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');

$metronome = 0;
$nature_of_time = $time_structure = $objects_file = $csound_file = $tonality_file = $tonality_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$alphabet_file = $extract_data['alphabet'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$tonality_file = $extract_data['tonality'];
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
	$objects_file = get_name_so_file($dir.$alphabet_file);
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
echo "<input type=\"hidden\" name=\"tonality_file\" value=\"".$tonality_file."\">";
echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
echo "<input type=\"hidden\" name=\"grammar_file\" value=\"".$grammar_file."\">";
echo "<input type=\"hidden\" name=\"objects_file\" value=\"".$objects_file."\">";
echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
$show_production = $trace_production = $non_stop_improvize = $p_clock = $q_clock = $striated_time = $max_time_computing = $produce_all_items = $random_seed = $quantization = $time_resolution = 0;
$compute_while_playing = TRUE;
$note_convention = '';
$csound_default_orchestra = '';
$diapason = 440; $C4key = 60;
$found_orchestra_in_settings = $quantize = FALSE;
$trace_capture_analysis = TRUE;
$minimum_period = 200; // milliseconds
$advance_time = 10; // seconds
$dir_base = str_replace($bp_application_path,'',$dir);
$url_settings = "settings.php?file=".urlencode($dir_base.$settings_file);
if($settings_file <> '' AND file_exists($dir.$settings_file)) {
	convert_to_json($dir,$settings_file);
	if(!$bad_settings) {
		$content_json = @file_get_contents($dir.$settings_file,TRUE);
		$settings = json_decode($content_json,TRUE);
		$show_production = $settings['DisplayProduce']['value'];
		$trace_production = $settings['TraceProduce']['value'];
		$note_convention = $settings['NoteConvention']['value'];
		$non_stop_improvize = $settings['Improvize']['value'];
		$max_items = $settings['MaxItemsProduce']['value'];
		$p_clock = $settings['Pclock']['value'];
		$q_clock = $settings['Qclock']['value'];
		$max_time_computing = $settings['MaxConsoleTime']['value'];
		$produce_all_items = $settings['AllItems']['value'];
		if(isset($settings['ComputeWhilePlay']['value'])) $compute_while_playing = $settings['ComputeWhilePlay']['value'];
		else $compute_while_playing = TRUE;
		if(trim($compute_while_playing) == '') $compute_while_playing = FALSE;
		if(isset($settings['AdvanceTime']['value'])) $advance_time = $settings['AdvanceTime']['value'];
		else $compute_while_playing = TRUE;
		$diapason = $settings['A4freq']['value'];
		$C4key = $settings['C4key']['value'];
		$time_resolution = $settings['Time_res']['value'];
		$quantization = $settings['Quantization']['value'];
		$quantize = $settings['Quantize']['value'];
		$nature_of_time_settings = $settings['Nature_of_time']['value'];
		if(isset($settings['MinPeriod']['value'])) $minimum_period = intval($settings['MinPeriod']['value']);
		if(isset($settings['TraceCaptureAnalysis']['value'])) $trace_capture_analysis = intval($settings['TraceCaptureAnalysis']['value']);
		}
	else {
		$time_resolution = 10;
		$quantization = 10;
		$quantize = TRUE;
		$nature_of_time_settings = STRIATED;
		$p_clock = $q_clock = 1;
		$non_stop_improvize = FALSE;
		}
	}
// if($quantization == 0) $quantize = FALSE;

if(!isset($_POST['analyze_tonal'])) {
	echo "<div style=\"padding:1em; width:690px;\" class=\"thinborder2\">";
	if($settings_file == '' OR !file_exists($dir.$settings_file)) {
		$time_resolution = 10; //  10 milliseconds by default
		$metronome =  60;
		$p_clock = $q_clock = 1;
		$nature_of_time = STRIATED;
		if($time_structure <> '')
			echo "⏱ Metronome (time base) is not specified by a ‘-se’ file. It will be set to <span class=\"red-text\">60</span> beats per minute. Time structure may be changed in data.<br />";
		else
			echo "⏱ Metronome (time base) is not specified by a ‘-se’ file. It will be set to <span class=\"red-text\">60</span> beats per minute.<br />";
		echo "•&nbsp;Time resolution = <span class=\"red-text\">".$time_resolution."</span> millisecond(s) (by default)<br />";
		echo "•&nbsp;No quantization<br />";
		}
	else {
		echo "<input class=\"edit\"  style=\"float:right;\" type=\"submit\" name=\"editsettings\" onclick=\"window.open('".nice_url($url_settings)."','".$settings_file."','width=800,height=800,left=100'); return false;\" value=\"EDIT ‘".begin_with(20,$settings_file)."’\">";
		if($p_clock > 0 AND $q_clock > 0) {
			$metronome_settings = 60 * $q_clock / $p_clock;
			}
		else $metronome_settings = 0;
		if($metronome > 0 AND $metronome <> $metronome_settings) {
			echo "➡&nbsp;Metronome = <span class=\"red-text\">".$metronome_settings."</span> beats/mn";
			if(!$bad_settings) echo " as per<br /><span class=\"green-text\">‘".$settings_file."’</span> but it may be changed in data.";
			echo "<br />";
			}
		if($metronome_settings > 0) $metronome = $metronome_settings;
		if($metronome <> intval($metronome)) $metronome = sprintf("%.3f",$metronome);
		$nature_of_time = $nature_of_time_settings;
		if($metronome > 0.) {
			echo "⏱ Metronome = <span class=\"red-text\">".$metronome."</span> beats/mn";
			if(!$bad_settings) echo " as per <span class=\"green-text\">‘".$settings_file."’</span>";
			echo "<br />";
			}
		if($time_resolution > 0) {
			echo "•&nbsp;Time resolution = <span class=\"red-text\">".$time_resolution."</span> millisecond(s)";
			if(!$bad_settings) echo " as per <span class=\"green-text\">‘".$settings_file."’</span>";
			echo "<br />";
			}
		if($quantize) {
			echo "•&nbsp;Quantization = <span class=\"red-text\">".$quantization."</span> millisecond(s)";
			if(!$bad_settings) echo " as per <span class=\"green-text\">‘".$settings_file."’</span>";
			if($time_resolution > $quantization) echo "&nbsp;<span class=\"red-text\">➡</span>&nbsp;may be raised to <span class=\"red-text\">".$time_resolution."</span>&nbsp;ms…";
			echo "<br />";
			}
		else echo "•&nbsp;No quantization<br />";
		}
	echo "•&nbsp;Time structure is <span class=\"red-text\">".nature_of_time($nature_of_time)."</span> by default but it may be changed in data<br />";
	/* if($max_time_computing > 0) {
		echo "• Max console computation time has been set to <span class=\"red-text\">".$max_time_computing."</span> seconds by <span class=\"green-text\">‘".$settings_file."’</span>";
		if($max_time_computing < 10) echo "&nbsp;<span class=\"red-text\">➡</span>&nbsp;probably too small!";
		if($max_time_computing > 3600) {
			echo "<br /><span class=\"red-text\">➡</span>&nbsp;reduced to <span class=\"red-text\">3600</span> seconds";
			$max_time_computing = 3600;
			}
		echo "<br />";
		} */
	if(!$compute_while_playing) {
		echo "• <span class=\"red-text\">Warning:</span> Compute while playing is <span class=\"red-text\">OFF</span> and Advance time = <span class=\"red-text\">".$advance_time."</span> seconds";
		if($advance_time < 1) echo " (<span class=\"red-text\">maybe too small</span>)";
		echo "<br />";
		}
	if($found_elsewhere AND $objects_file <> '') echo "• <span class=\"red-text\">Sound-object prototype</span> file = <span class=\"green-text\">‘".$objects_file."’</span> found in <span class=\"green-text\">‘".$alphabet_file."’</span><br />";
	if($note_convention <> '') echo "• Note convention is <span class=\"red-text\">".strtoupper(note_convention(intval($note_convention)))."’</span> as per <span class=\"green-text\">".$settings_file."’</span>";
	else {
		echo "• Note convention is <span class=\"red-text\">ENGLISH</span> by default";
		}
	echo "</div><br />";
	}

if(!isset($output_folder) OR $output_folder == '')
    $output_folder = "my_output";
$output = $bp_application_path.SLASH.$output_folder;
do $output = str_replace(SLASH.SLASH,SLASH,$output,$count);
while($count > 0);
if(!file_exists($output)) {
    echo "<p><span class=\"red-text\">Created folder:</span><span class=\"green-text\"> ".$output."</span><br />";
   	if(!mkdir($output,0775, true))
        error_log("Failed to create directory '{$temp_dir}' with error: " . error_get_last()['message']);
	else
        chmod($output,0775); // Double-check permissions
    }
$default_output_name = str_replace("-da.",'',$filename);
$default_output_name = str_replace(".bpda",'',$default_output_name);
$file_format = $default_output_format;
if(isset($data_file_format[$filename])) $file_format = $data_file_format[$filename];
if(isset($_POST['new_file_format'])) {
	$file_format = $_POST['new_file_format'];
	if($file_format == "rtmidi") read_midiressources($filename);
	$no_save_midiresources = TRUE;
//	echo "<p>@@ file_format = ".$file_format.", filename = ".$filename."</p>";
	}
save_settings2("data_file_format",$filename,$file_format); // To _settings.php
$output_file = $default_output_name;
if(isset($_POST['output_file'])) {
	$output_file = $_POST['output_file'];
	$output_file = fix_new_name($output_file);
	}
$output_file = add_proper_extension($file_format,$output_file);
// echo "<p>output_file = ".$output_file."</p>";

if(!is_connected() AND $file_format == "midi") {
	echo "<p style=\"color:red;\">➡ Cannot find the MIDI file er “midijs.net”… Are you connected to Internet?</p>";
	}

$project_name = preg_replace("/\.[a-z]+$/u",'',$output_file);
$result_file = $bp_application_path.$output_folder.SLASH.$project_name."-result.html";

$content = show_instruments_and_scales($dir,$objects_file,$content,$url_this_page,$filename,$file_format);

if(!isset($_POST['analyze_tonal'])) {
	echo "<div style=\"float:right; padding-right:6px; padding-left:6px; background-color:transparent;\">";
	$csound_is_responsive = check_csound();
	link_to_tonality();
	echo "</div>";
	}

if(!isset($_POST['analyze_tonal'])) {
	echo "<table id=\"topedit\" cellpadding=\"8px;\" class=\"thinborder\"><tr >";
	echo "<td id=\"topmidiports\" style=\"white-space:nowrap;\">";
	if($file_format <> "rtmidi") {
		for($i = 0; $i < $NumberMIDIoutputs; $i++) {
			echo "<input type=\"hidden\" name=\"MIDIoutput_".$i."\" value=\"".$MIDIoutput[$i]."\">";
			echo "<input type=\"hidden\" name=\"MIDIoutputname_".$i."\" value=\"".$MIDIoutputname[$i]."\">";
			}
		for($i = 0; $i < $NumberMIDIinputs; $i++) {
			echo "<input type=\"hidden\" name=\"MIDIinput_".$i."\" value=\"".$MIDIinput[$i]."\">";
			echo "<input type=\"hidden\" name=\"MIDIinputname_".$i."\" value=\"".$MIDIinputname[$i]."\">";
			}
		echo "<p>Name of output file (with proper extension):<br />";
		echo "<input type=\"text\" name=\"output_file\" size=\"25\" value=\"".$output_file."\">&nbsp;";
		echo "</p>";
		}
	else {
		echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
		display_midi_ports($filename);
		}
	read_midiressources($filename);
	echo "</td>";
	echo "<td id=\"topmidiports\" style=\"vertical-align:middle;\"><p style=\"text-align:left;\">";
	show_file_format_choice("data",$file_format,$url_this_page,$filename);
	echo "</p>";
	echo "</td>";
	// if($file_format == "rtmidi") filter_form();
	echo "</tr>";
	echo "</table>";
	}

$result_upload = upload_related($dir);
if($result_upload <> '') echo "<p>".$result_upload."</p>";

$link_options = ''; $upload_mssg = '';

// Only one warning generated by calls to upload_related_form() is displayed, because the 'fileInput' identifier must be unique

if($grammar_file <> '') {
	if(!file_exists($dir.$grammar_file)) {
		$upload_mssg = upload_related_form($dir,$grammar_file,"grammar");
		$error = TRUE;
		}
	else $link_options .= "&grammar=".urlencode($dir.$grammar_file);
	}
if($alphabet_file <> '') {
	if(!file_exists($dir.$alphabet_file)) {
		$upload_mssg = upload_related_form($dir,$alphabet_file,"alphabet");
		$error = TRUE;
		}
	else $link_options .= "&alphabet=".urlencode($dir.$alphabet_file);
	}
if($settings_file <> '') {
	if(!file_exists($dir.$settings_file)) {
		$upload_mssg = upload_related_form($dir,$settings_file,"settings");
		$error = TRUE;
		}
	else $link_options .= "&settings=".urlencode($dir.$settings_file);
	}
if($objects_file <> '') {
	if(!file_exists($dir.$objects_file)) {
		$upload_mssg = upload_related_form($dir,$objects_file,"objects");
		$error = TRUE;
		}
	else $link_options .= "&objects=".urlencode($dir.$objects_file);
	}
if($timebase_file <> '') {
	if(!file_exists($dir.$timebase_file)) {
		$upload_mssg = upload_related_form($dir,$timebase_file,"timebase");
		$error = TRUE;
		}
	else $link_options .= "&timebase=".urlencode($dir.$timebase_file);
	}
if($csound_file <> '') {
	if(!file_exists($dir_csound_resources.$csound_file)) {
		$upload_mssg .= "<br /><span class=\"red-text blinking\">WARNING:</span> <span class=\"green-text\">".$csound_file."</span> not found<br />";
		$error = TRUE;
		}
	else {
		$link_options .= "&csound_file=".urlencode($csound_file);
		if($file_format == "csound" AND file_exists($dir_csound_resources.$csound_orchestra)) $link_options .= "&csound_orchestra=".urlencode($csound_orchestra);
		}
	}
if($tonality_file <> '') {
	if(!file_exists($dir_tonality_resources.$tonality_file)) {
		$upload_mssg .= "<br /><span class=\"red-text\">WARNING: ".$dir_tonality_resources.$tonality_file." not found</span><br />";
		$error = TRUE;
		}
	else {
		$link_options .= "&tonality_file=".urlencode($tonality_file);
		}
	}

$link_options .= "&here=".urlencode($dir.$filename);

if($error_mssg <> '') {
	echo "<p>".$error_mssg."</p>";
	}
if($upload_mssg <> '') {
	echo "<p>".$upload_mssg."</p>";
	}

if(intval($note_convention) <> intval($new_convention) AND $new_convention <> '')
	echo "<p><span class=\"red-text\">➡</span> WARNING: Note convention should be set to <span class=\"red-text\">‘".ucfirst(note_convention(intval($new_convention)))."’</span> in the <span class=\"green-text\">‘".$settings_file."’</span> settings file</p>";

if(!isset($_POST['analyze_tonal'])) {
	echo $save_warning;
	if($file_format == "csound") echo "<p>&nbsp;</p>";
	else {
		echo "<br /><div class=\"thinborder\" style=\"width:50%; padding-left:0.5em;\">";
		echo "<input type=\"hidden\" name=\"capture_file\" value=\"".$capture_file."\">";
		if(file_exists($capture_file) AND is_capture_file($capture_file)) {
			$link_analyse = "capture_analysis.php?data=".urlencode($capture_file)."&quantization=".$quantization."&minimum_period=".$minimum_period."&trace_capture_analysis=".$trace_capture_analysis;
			$window_name = "capture_analysis";
			$capture_file_name = "capture_".$today_date.".txt";
			echo "<p>👉 A well-formed captured MIDI data file is in place: <span class=\"green-text\">".$capture_file_name."</span><br />";
			echo "<input class=\"produce\" type=\"submit\" name=\"analyse_capture\" onclick=\"event.preventDefault(); window.open('".$link_analyse."','".$window_name."','width=800,height=800,left=200'); return false;\" value=\"ANALYSE CAPTURED MIDI DATA\">";
			echo "<input type=\"hidden\" name=\"capture_file_name\" value=\"".$capture_file_name."\">";
			echo "&nbsp;<—&nbsp;<input type=\"submit\" name=\"download_capture_file\" value=\"DOWNLOAD\" class=\"save\">";
			echo "&nbsp;<—&nbsp;<input type=\"submit\" name=\"delete_capture_file\" value=\"DELETE\" class=\"trash\">";
			echo "<br />";
			}
		else {
			echo "<p><input type=\"file\" name=\"uploaded_capture_file\" id=\"uploaded_capture_file\">";
			echo "<input class=\"save\" name=\"upload_capture_file\" type=\"submit\" value=\"<-- IMPORT CAPTURED MIDI DATA\">";
			if(file_exists($capture_file)) {
				echo "&nbsp;<—&nbsp;<input type=\"submit\" name=\"delete_capture_file\" value=\"DELETE\" class=\"trash\">";
				echo "<br /><span class=\"red-text\">👉 The current file of captured MIDI data is badly formed</span>";
				}
			echo "</p>";
			}
		echo "</div>";
		}
	echo "<p id=\"downloadupload\"><button class=\"save\"\" onclick=\"toggledownload(); return false;\">DOWNLOAD / UPLOAD</button>&nbsp;<button class=\"edit\"\" onclick=\"togglesearch(); return false;\">SEARCH & REPLACE</button></p>";

	download_upload_project_form($dir,$filename,"data",$settings_file); find_replace_form();
	echo $upload_message; echo $undo_upload_project_message;

	echo "<br /><table border=\"0\" style=\"background-color:transparent;\"><tr style=\"background-color:transparent;\">";
	echo "<td style=\"background-color:transparent;\">";

	echo "<div style=\"float:right; vertical-align:middle; background-color:transparent;\">Import MusicXML: <input   onclick=\"if(!checksaved()) return false;\" type=\"file\" name=\"music_xml_import\">&nbsp;<input type=\"submit\" onclick=\"if(!checksaved()) return false;\" class=\"save\" value=\"← IMPORT\"></div>";

	echo "<div style=\"text-align:left; background-color:transparent;\"><input id=\"saveButton\" class=\"save big\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"SAVE ‘".begin_with(15,$filename)."’\"></div>";

	$content = do_replace($content);

	echo "<br /><textarea id=\"textArea\" name=\"thistext\" onchange=\"tellsave()\" rows=\"40\" style=\"width:750px;\">".$content."</textarea><br /><br />";
	echo "<div style=\"float:right; background-color:transparent;\"><input class=\"save big\" type=\"submit\" formaction=\"".$url_this_page."#textArea\"  onclick=\"clearsave();\" name=\"savethisfile\" value=\"SAVE ‘".begin_with(20,$filename)."’\"></div>";
	display_more_buttons($error,$content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$tonality_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
	}

$hide = FALSE;

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
	echo "<table class=\"thinborder\">";
	echo "<tr><td></td><td style=\"text-align:center;\"><b>Current value</b></td><td style=\"text-align:center;\"><b>Replace with<br />(0 … 127)</b></td></tr>";
	echo "<tr><td>Average</td>";
	echo "<td style=\"text-align:center;\">".$velocity_average."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_velocity_average\" size=\"6\" value=\"".$change_velocity_average."\"></td>";
	echo "</tr>";
	echo "<tr><td>Max</td>";
	echo "<td style=\"text-align:center;\">".$max_velocity."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_velocity_max\" size=\"6\" value=\"".$change_velocity_max."\"></td>";
	echo "</tr>";
	echo "<tr><td style=\"text-align:center;\"><input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td></td>";
	echo "<td style=\"text-align:center;\">";
	if($velocity_average >= 1)
		echo "<input class=\"produce\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_velocity_change\" value=\"APPLY\">";
	else echo "<p>Not applicable</p>";
	echo "</td></tr>";
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
	echo "<table class=\"thinborder\">";
	echo "<tr><td></td><td style=\"text-align:center;\"><b>Current value</b></td><td style=\"text-align:center;\"><b>Replace with<br />(0 … 127)</b></td></tr>";
	echo "<tr><td>Average</td>";
	echo "<td style=\"text-align:center;\">".$volume_average."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_volume_average\" size=\"6\" value=\"".$change_volume_average."\"></td>";
	echo "</tr>";
	echo "<tr><td>Max</td>";
	echo "<td style=\"text-align:center;\">".$volume_max."</td>";
	echo "<td style=\"text-align:center;\"><input type=\"text\" style=\"border:none; text-align:center;\" name=\"change_volume_max\" size=\"6\" value=\"".$change_volume_max."\"></td>";
	echo "</tr>";
	echo "<tr><td style=\"text-align:center;\"><input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\"></td><td></td>";
	
	// <td style=\"text-align:center;\"><input class=\"produce\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_volume_change\" value=\"APPLY\"></td></tr>";


	echo "<td style=\"text-align:center;\">";
	if($volume_average >= 1)
		echo "<input class=\"produce\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"apply_volume_change\" value=\"APPLY\">";
	else echo "<p>Not applicable</p>";
	echo "</td></tr>";

	echo "</table>";
	echo "<hr>";
	$hide = TRUE;
	}

echo "<span id=\"topchanges\"></span>";
if(isset($_POST['manage_instructions'])) {
	show_changes_instructions($content);
	$hide = TRUE;
	}
$hide = display_note_conventions($note_convention);

if(!$hide AND !isset($_POST['analyze_tonal'])) {
	if($settings_file == '') {
		$new_settings_file = str_replace("-da.",'',$filename);
		$new_settings_file = str_replace(".bpda",'',$new_settings_file);
		$new_settings_file = "-se.".$new_settings_file;
		echo "<p>&nbsp;</p><p><span class=\"red-text\">➡</span> <input class=\"save big\" onclick=\"window.open('settings_list.php?dir=".urlencode($dir)."&thispage=".urlencode($url_this_page)."','settingsfiles','width=400,height=400,left=100'); return false;\" type=\"submit\" title=\"Display settings file\" value=\"CHOOSE\"> a settings file or <input class=\"edit big\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"create_settings_file\" formaction=\"".$url_this_page."\" value=\"CREATE\"> a new file named <input type=\"text\" name=\"new_settings_file\" size=\"25\" value=\"".$new_settings_file."\"></p>";
		}
	else 
		echo "<p><input class=\"edit\" onclick=\"window.open('settings_list.php?dir=".urlencode($dir)."&thispage=".urlencode($url_this_page)."','settingsfiles','width=400,height=400,left=100'); return false;\" type=\"submit\" title=\"Display settings file\" value=\"CHOOSE\"> a different settings file</p>";
	echo "<table class=\"thinborder\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" name=\"change_convention\" formaction=\"".$url_this_page."#topchanges\" value=\"APPLY NOTE CONVENTION to this data\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"hidden\" name=\"old_convention\" value=\"".$note_convention."\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "</td>";
	echo "</tr>";
	echo "<tr><td colspan=2>";
	show_note_convention_form("data",$note_convention,$settings_file);
	echo "</td></tr></table><br />";
	$found_chan = substr_count($content,"_chan(");
	$found_ins = substr_count($content,"_ins(");
	$found_part = substr_count($content,"_part(");
	$found_tempo = substr_count($content,"_tempo(");
	$found_volume = substr_count($content,"_volume(");
	$found_velocity = substr_count($content,"_vel(");
	$found = FALSE;
	if($found_chan > 0 OR $found_ins > 0 OR $found_part > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"manage_instructions\" formaction=\"".$url_this_page."#topchanges\" value=\"MANAGE _chan(), _ins(), _part()\">&nbsp;";
		$found = TRUE;
		}
	if($found_chan > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" name=\"delete_chan\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _chan()\">&nbsp;";
		$found = TRUE;
		}
	if($found_ins > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"delete_ins\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _ins()\">&nbsp;";
		$found = TRUE;
		}
	if($found_part > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"delete_part\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _part()\">&nbsp;";
		$found = TRUE;
		}
	if($found_tempo > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"delete_tempo\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _tempo()\">&nbsp;";
		$found = TRUE;
		}
	if($found_volume > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"delete_volume\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _volume()\">&nbsp;";
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"volume_velocity\" formaction=\"".$url_this_page."#topedit\" value=\"volume -> velocity\">&nbsp;";
		$found = TRUE;
		}
	if($found_velocity > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"delete_velocity\" formaction=\"".$url_this_page."#topedit\" value=\"DELETE _vel()\">&nbsp;";
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"velocity_volume\" formaction=\"".$url_this_page."#topedit\" value=\"velocity -> volume\">&nbsp;";
		$found = TRUE;
		}
	if($found_volume > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"modify_volume\" formaction=\"".$url_this_page."#topchanges\" value=\"Modify _volume()\">&nbsp;";
		$found = TRUE;
		}
	if($found_velocity > 0) {
		echo "<input class=\"edit\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"this.form.target='_self';return true;\" name=\"modify_velocity\" formaction=\"".$url_this_page."#topchanges\" value=\"Modify _vel()\">&nbsp;";
		$found = TRUE;
		}
	echo "<input type=\"hidden\" name=\"change_velocity_average\" value=\"".$change_velocity_average."\">";
	echo "<input type=\"hidden\" name=\"change_velocity_max\" value=\"".$change_velocity_max."\">";
	echo "<input type=\"hidden\" name=\"change_volume_average\" value=\"".$change_volume_average."\">";
	echo "<input type=\"hidden\" name=\"change_volume_max\" value=\"".$change_volume_max."\">";
//	if($found) echo "<hr>";
	}
echo "</form>";
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > 0 AND (substr_count($content,'{') > 0 OR substr_count($content,"-da.") > 0  OR substr_count($content,".bpda") > 0) AND !$hide) {
	echo "<span id=\"tonalanalysis\"></span>";
	if(isset($_POST['analyze_tonal']))
		echo "<h3 style=\"text-align:center;\">Tonal analysis: “".$filename."”</h3>";
	else echo "<h3>Tonal analysis: “".$filename."”</h3>";
	$tonal_analysis_possible = !($note_convention > 2);
	if(!$tonal_analysis_possible) echo "<p><span class=\"red-text\">➡ Tonal analysis is only possible with names of notes in English, Italian/Spanish/French or Indian conventions.</span></p>";
	if(isset($_POST['analyze_tonal']) OR isset($_POST['save_tonal_settings']) OR isset($_POST['reset_tonal_settings'])) {
		tonal_analysis($content,$url_this_page,$tonality_file,$temp_dir,$temp_folder,$note_convention);
		}
	else {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p><input class=\"edit big\" style=\"margin-right:1em;\" type=\"submit\" onmouseover=\"checksaved();\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE INTERVALS\"";
		if(!$tonal_analysis_possible) echo " disabled";
		echo ">";
		echo "Melodic and harmonic tonal intervals of (all) item(s)<br /><i>ignoring channels, instruments, periods, sound-objects and random performance controls.</i></p>";
		if($tonality_file <> '') echo "<div style=\"padding:6px;\"><span class=\"red-text\">➡</span> It may be necessary to <a class=\"linkdotted\" target=\"_blank\" href=\"tonality.php?file=".urlencode($tonality_resources.SLASH.$tonality_file)."\">open</a> the ‘<span class=\"green-text\">".$tonality_file."</span>’ tonality resource file, allowing access to its tonal scale definitions.</div>";
		echo "</form>";
	//	echo "<hr>";
		}
	}
echo "</td>";
$window_name = window_name($filename);
if(!$hide AND !isset($_POST['analyze_tonal'])) {
	echo "<td style=\"background-color:transparent;\">";
	echo "<table class=\"thicktable\">";
	if($imax > 0 AND substr_count($content,'{') > 0) {
		$window_name_grammar = $window_name."_grammar";
		$link_grammar = "produce.php?data=".urlencode($this_file);
	//	$link_grammar = $link_grammar."&instruction=create_grammar";
		$link_grammar = $link_grammar."&instruction=create_grammar&keepalive=1";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"thistext\" value=\"".recode_tags($content)."\">";
		echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
		echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
		echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
		echo "<input type=\"submit\" onclick=\"clearsave();\" class=\"produce\" formaction=\"".$url_this_page."#topedit\" name=\"explode\" value=\"EXPLODE\">&nbsp;<span class=\"red-text\">➡ </span>split {…}&nbsp;expressions (measures)";
		echo "<div style=\"float:right;\"><input class=\"produce\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$link_grammar."','".$window_name_grammar."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"create_grammar\" title=\"Create grammar using items on this page\" value=\"CREATE GRAMMAR\"></div>";
		echo "</td></tr>";
		if($imax > 0) {
			echo "<tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px;\">";
			echo "<input type=\"submit\" onclick=\"clearsave();\" class=\"produce\" formaction=\"".$url_this_page."#topedit\" name=\"implode\" value=\"IMPLODE\">&nbsp;<span class=\"red-text\">➡ </span>merge {…}&nbsp;expressions (measures)";
			echo "</td></tr>";
			}
		echo "</form>";
		}
	if(file_exists($temp_dir.$temp_folder)) {
		delete_folder($temp_dir.$temp_folder,FALSE);
		}

	for($i = $i_item = 0; $i < $imax; $i++) {
		$error_mssg = '';
		$line = trim($table[$i]);
	//	echo "i = ".$i."<br />";
		if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
	//	echo "imax = ".$imax."<br />";
		if($i_item > 0)
			$training_set_folder = $bp_application_path.$output_folder.SLASH."set_".$filename."[".($i_item + 1)."]";
		else
			$training_set_folder = $bp_application_path.$output_folder.SLASH."set_".$filename;
		delete_folder($training_set_folder,TRUE);
	/*	echo "<p>training_set_folder = ".$training_set_folder."</p>";
		echo "<p>temp_dir = ".$temp_dir."</p>";
		echo "<p>temp_folder = ".$temp_folder."</p>"; */
	//	echo "<p>@@ i_item = ".$i_item." imax = ".$imax."</p>";
		// Create 'units' = shortest sequences of polymetric structures for AI training sets
		$segment = create_parts($line,$i_item,$temp_dir,$temp_folder,0,0,0,0,"units");
		if($segment['error'] == "continue") {
	//		echo "<p>continue i_item = ".$i_item."</p>";
			continue;
			}
		$data_units = $segment['data_chunked'];
		// Create 'chunks' = longer sequences of polymetric structures for real-time MIDI performance
		$segment = create_parts($line,$i_item,$temp_dir,$temp_folder,$minchunk_size,$maxchunk_size,0,0,"chunk");
		if($segment['error'] == "break") {
	//		echo "<p>break i_item = ".$i_item."</p>";
			break;
			}
		if($segment['error'] == "continue") {
	//		echo "<p>continue i_item = ".$i_item."</p>";
			continue;
			}
	//	echo "<p>@@@ i_item = ".$i_item." imax = ".$imax."</p>";
		$i_item++;
		$tie_mssg = $segment['tie_mssg'];
		$data = $segment['data'];
		$data_chunked = $segment['data_chunked'];
		$chunked = $segment['chunked'];
		if($file_format == "rtmidi" AND $no_chunk_real_time_midi) {
			$data_chunked = $chunked = FALSE;
			$tie_mssg = '';
			}
		$link_options_create_set = $link_options;
		$out[$i] = $output_file;
		if($file_format == "csound") {
			$cs = $output_file;
			$out[$i] = str_replace(".sco",'',$output_file);
			$link_options .= "&score=".urlencode($output.SLASH.$cs);
			}
		if($file_format == "midi") {
			$midi_file = $output_file;
			$out[$i]  = '';
			$link_options .= "&midifile=".urlencode($output.SLASH.$midi_file);
			}
		$chunk_number = $segment['chunk_number'];
		$line_recoded = $segment['line_recoded'];
		$title_this = $segment['title_this'];
		echo "<tr><td>".$i_item."</td><td>";
		$link_options_play = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$out[$i])."&format=".$file_format."&item=".$i_item."&title=".urlencode($filename);
		$link_options_chunked = $link_options_play;
		$output_file_expand = str_replace(".sco",'',$out[$i]);
		$output_file_expand = str_replace(".mid",'',$output_file_expand);
		$output_file_expand .= ".bpda";
		$link_options_expand = $link_options."&output=".urlencode($bp_application_path.$output_folder.SLASH.$output_file_expand)."&format=data"."&title=".urlencode($filename);
		$link_options_create_set .= "&midifile=".urlencode($training_set_folder)."&format=midi&title=".urlencode($filename);
		$link_produce = "produce.php?data=".urlencode($data)."&keepalive=1";
		$link_produce_chunked = "produce.php?data=".urlencode($data_chunked)."&keepalive=1";
		$link_produce_create_set = "produce.php?data=".urlencode($data_units)."&keepalive=1";
		$link_play = $link_produce."&instruction=play";
		$link_play_chunked = $link_produce_chunked."&instruction=play-all";
		$link_play .= $link_options_play;
		$link_play_chunked .= $link_options_play;
		$link_expand = $link_produce."&instruction=expand";
		$link_expand .= $link_options_expand;
		$link_create_set = $link_produce_create_set."&instruction=create_set";
		$link_create_set .= $link_options_create_set;
		$window_name_ = $window_name."_";
		$window_name_expand = $window_name."_expand";
		$window_name_create_set = $window_name."_create_set";
		$window_name_chunked = $window_name."_chunked";
		// echo "<small>link_play_chunked = ".urldecode($link_play_chunked)."</small><br /><br />";
		// echo "<small>link_create_set = ".urldecode($link_create_set)."</small><br />";
		$n1 = substr_count($line_recoded,'{');
		$n2 = substr_count($line_recoded,'}');
		if($n1 > $n2) $error_mssg .= "• <span class=\"red-text\">This score contains ".($n1-$n2)." extra ‘{'</span><br />";
		if($n2 > $n1) $error_mssg .= "• <span class=\"red-text\">This score contains ".($n2-$n1)." extra ‘}'</span><br />";
		if($error_mssg == '') {
			echo "<input id=\"Button\" class=\"produce\" onmouseover=\"checksaved();\" onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$link_play."','".$window_name_."','width=800,height=800,left=200'); return false;}\" type=\"submit\" name=\"produce\" title=\"Play polymetric expression\" value=\"PLAY\">&nbsp;";
			if($chunked) echo "<input class=\"produce\" onmouseover=\"checksaved();\" onclick=\"event.preventDefault(); if(checksaved()) {window.open('".$link_play_chunked."','".$window_name_chunked."','width=800,height=800,left=150,toolbar=yes'); return false;}\" type=\"submit\" name=\"produce\" title=\"Play polymetric expression in chunks (no graphics)\" value=\"PLAY safe (".$chunk_number." chunks)\">&nbsp;";
			echo "&nbsp;<input class=\"edit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$link_expand."','".$window_name_expand."','width=800,height=800,left=100'); return false;\" type=\"submit\" name=\"produce\" title=\"Expand polymetric expression\" value=\"EXPAND\">";
			if($chunked) {
				echo "<br  /><input id=\"saveButton\" class=\"save\" type=\"submit\" onclick=\"clearsave();\" formaction=\"".$url_this_page."#topedit\" name=\"savethisfile\" value=\"refresh\"> <span class=\"red-text\">➡ </span>";
				echo "<input class=\"edit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$link_create_set."','".$window_name_create_set."','width=800,height=800,left=100'); return false;\" type=\"submit\" name=\"create_set\" title=\"Create MIDI file sample set for AI training\" value=\"CREATE SET FOR AI TRAINING\">";
				}
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
		echo "<span class=\"green-text\">".$line_show."</span>";
		echo "</small></td></tr>";
		}
	echo "</table>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "<script>\n";
echo "window.onload = function() {
    toggleAllDisplays($NumberMIDIinputs); toggleAllDisplays($NumberMIDIoutputs); settogglesearch(); settoggledownload(); settogglescales();
	};\n";
echo "</script>\n";

footer();
echo "</body></html>";

function create_parts($line,$i_item,$temp_dir,$temp_folder,$minchunk_size,$maxchunk_size,$measure_min,$measure_max,$label) {
	global $permissions,$p_clock, $q_clock;
// echo "@@@ measure_min = ".$measure_min."<br />";
// echo "@@@ measure_max = ".$measure_max."<br />";
//	$minchunk_size = 0;
	$test_legato = FALSE;
	$current_legato = array();
	$i_layer = array();
	$current_legato[0] = $i_layer[0] = $layer = $level_bracket = 0;
	// $layer is the index of the line setting events on the phase diagram
	// $level_bracket is the level of polymetric expression (or "measure")
	// $label = chunk when chunking data for PLAY safe, $label = unit when chunking data for AI training set, $label = slice when slicing (in harmonic analysis)
	// In chunking, we trace whether legato() instructions have been reset before the end of each "measure",
	// i.e. polymetric expression at the lowest $level_bracket
	// We also trace note ties which have not been completely bound at the end of the measure
	// Both conditions prohibit chunking the item at the end of the measure
	$tie_mssg = '';
	$segment['error'] = $tonal_scale = $initial_tempo = '';
	if($label == "units" AND $p_clock <> $q_clock)
		$initial_tempo = " _tempo(".$q_clock."/".$p_clock.") ";
	if(is_integer($pos=strpos($line,"[")) AND $pos == 0)
		$title_this = preg_replace("/\[([^\]]+)\].*/u",'$1',$line);
	else $title_this = '';
	$initial_controls = '';
	if(is_integer($pos=strpos($line,"{"))) {
		$initial_controls = trim(substr($line,0,$pos));
		$initial_controls = preg_replace("/\[[^\]]*\]/u",'',$initial_controls);
	//	echo "@@@ initial_controls = ".$initial_controls."<br />";
		// Pick up initial tempo if any
		$tempo = preg_replace("/.*_tempo\(([^\)]+)\).*/u","$1",$initial_controls);
		if($tempo <> $initial_controls) $initial_tempo .= "_tempo(".$tempo.") ";
		//	echo "@@@ initial_tempo = ".$initial_tempo."<br />";
		if($label <> "chunk" AND $label <> "units") {
			// Pick up specified tonal scale if any
			$scale = preg_replace("/.*_scale\(([^\,]+)[^\)]+\).+/u","$1",$line);
			if($scale <> $line) $tonal_scale = $scale;
		//	echo "@@@ tonal_scale = ".$tonal_scale."<br />";
		//	echo $scale."<br />".$line."<br />";
			$initial_controls = '';
			}
		}
	$line = preg_replace("/^i[0-9].*/u",'',$line); // Csound note statement
	$line = preg_replace("/^f[0-9].*/u",'',$line); // Csound table statement
	$line = preg_replace("/^t[ ].*/u",'',$line); // Csound tempo statement
	$line = preg_replace("/^s\s*$/u",'',$line); // Csound "s" statement
	$line = preg_replace("/^e\s*$/u",'',$line); // Csound "e" statement
	$restrict_analysis = FALSE;
	// Beware that measure_min and measure_max may not be integers
	if(!($measure_min === 0) AND strlen($measure_min) > 0) {
		if(is_integer($pos=strpos($line,"[—".$measure_min."—]"))) {
			echo "<b>From measure [—".$measure_min."—]";
			$restrict_analysis = TRUE;
			$line = substr($line,$pos,strlen($line) - $pos);
			if($measure_max === 0 OR strlen($measure_max) == 0)
				echo " to the end";
			}
		}
	else $measure_min = 0;
	if(!($measure_max === 0) AND strlen($measure_max) > 0) {
		if(is_integer($pos=strpos($line,"[—".$measure_max."—]"))) {
			if(is_integer($pos2=strpos($line,"[—",$pos + 4))) {
				if($measure_min === 0) echo "<b>From start";
				echo " to measure [—".$measure_max."—]";
				$restrict_analysis = TRUE;
				$line = substr($line,0,$pos2);
				}
			}
		}
	if($restrict_analysis) echo ":</b><br /><small>".$line."</small><br /><br />";
	$line = preg_replace("/\[[^\]]*\]/u",'',$line);
	if($line == '') $segment['error'] = "continue";
	if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) $segment['error'] = "break";
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) $segment['error'] = "continue";
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) $segment['error'] = "continue";
	if($segment['error'] <> '') return $segment;
	$line_recoded = recode_entities($line);
	$data = $temp_dir.$temp_folder.SLASH.$i_item.".bpda"; 
	if($label == "chunk") {
		$hdl = fopen($data,"w");
		if(fwrite($hdl,$line_recoded."\n") === FALSE) echo "<p>⚠️ Cannot write ".$data."<br />Probably permission issue…</p>";
		fclose($hdl);
		chmod($data,$permissions);
		}
	$chunked = FALSE;
	$tie = $n = $brackets = $total_ties = 0;
	$line_chunked = ''; $first = TRUE; $chunk_number = 1;
	if($label == "units") $start_chunk = "[";
	else $start_chunk = "[".$label." ";
	if($label == "chunk" OR $label == "units") $start_chunk .= "1";
	$start_chunk .= "] ";
	if($label == "slice") $start_chunk = '';
	$test_legato = FALSE;
	$level_bracket = $n = 0;
	if($label == "units") {
		for($k = 0; $k < strlen($line_recoded); $k++) {
			$c = $line_recoded[$k];
			if($c == '{') $level_bracket++;
			if($c == '}') {
				$level_bracket--;
				if($level_bracket == 0) $n++;
				}
			}
		if($n < 3) {
	//		echo "@@@ item = ".$i_item." n = ".$n."<br />";
			$segment['data_chunked'] = '';
			$segment['chunked'] = FALSE;
			$segment['error'] = '';
			return $segment;
			}
		}
	$level_bracket = $n = 0;
	$all_lines_chunked = '';
	if($label == "units") {
	//	$number_expressions = nextShuffled(1,5);
		$number_expressions = rand(1,5);
	//	$number_expressions = 1;
		$start_chunk .= " [".$number_expressions." structures]";
		}
	else $number_expressions = 1;
//	echo "@@@ ok ".$i_item."<br />";
	for($k = 0; $k < strlen($line_recoded); $k++) {
		$line_chunked .= $start_chunk;
		if($label == "units" AND $start_chunk <> '') {
			$line_chunked .= $initial_tempo;
			}
		$start_chunk = '';
		$c = $line_recoded[$k];
	//	if($label == "units" AND $c == '&') continue; // For the time being we ignore tied notes when building AI training sample sets
		if($k < (strlen($line_recoded) - 1) AND ctype_alnum($c) AND $line_recoded[$k+1] == '&') {
			$tie++; $total_ties++;
			}
		if($k < (strlen($line_recoded) - 1) AND $c == '&' AND ctype_alnum($line_recoded[$k+1])) $tie--;
		if($c == '.' AND $k > 0 AND $line_recoded[$k-1]) $brackets++;
		if($c == '_') {
			$get_legato = get_legato($line_recoded,$k);
			if($get_legato >= 0) {
				$current_legato[$layer] = $get_legato;
				if($test_legato) echo "_legato(".$current_legato[$layer].") layer ".$layer." level ".$level_bracket."<br />";
				}
			}
		if($c == '{') {
			if($level_bracket == 0 AND !$first AND $label <> "units") $line_chunked .= $initial_controls;
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
		if(!$first OR $label <> "units") $line_chunked .= $c;
		$linebreak = "\n";
		if($c == '}') {
			$level_bracket--;
			$layer = $i_layer[$level_bracket];
			if($level_bracket == 0) {
				$n++;
				if($n >= $number_expressions) {
					$ok_legato = 1;
					foreach($current_legato as $thisfield => $the_legato) {
						if($test_legato) echo "(".$thisfield." -> ".$the_legato.")";
						if($the_legato > 0) $ok_legato = 0;
						}
				/*	if($i_item < 4) echo "@@@ ok item = ".$i_item." label = ".$label." n = ".$n."<br />";
					if($i_item == 2 AND $label == "units") {
						echo "item #".$i_item." ok_legato = ".$ok_legato." n = ".$n." minchunk_size = ".$minchunk_size." maxchunk_size = ".$maxchunk_size." tie = ".$tie."<br />";
						echo $line_chunked."<br />";
						} */
					if(($label == "units") OR (($label == "slice" OR $ok_legato) AND (($tie <= 0 AND $n >= $minchunk_size) OR ($maxchunk_size > 0 AND $n > $maxchunk_size)))) {
						$current_legato = $i_layer = array();
						$current_legato[0] = $i_layer[0] = $layer = 0;
						if($label == "chunk" AND abs($tie) > 0)
							$tie_mssg .=  "• <span class=\"red-text\">".$tie." unbound tie(s) in chunk #".$chunk_number."</span><br />";
						if($label == "chunk" AND !$ok_legato)
							$tie_mssg .=  "• <span class=\"red-text\">legato(s) may be truncated after chunk #".$chunk_number."</span><br />"; 
						$line_chunked .= $linebreak;
						$line_chunked = delete_orphan_ties($line_chunked);
				//		if($i_item == 1) echo $line_chunked."<br />";
						$all_lines_chunked .= $line_chunked;
						$line_chunked = '';
						$tie = $n = 0;
						if($test_legato) echo " => ".$label." #".$chunk_number;
						if($label == "units") {
				//			$number_expressions = nextShuffled(1,5);
							$number_expressions = rand(1,5);
				//			$number_expressions = 1;
							$start_chunk = "[";
							}
						else $start_chunk = "[".$label." ";
						if($label == "chunk" OR $label == "units") $start_chunk .= (++$chunk_number);
						$start_chunk .= "] ";
						if($label == "units") $start_chunk .= " [".$number_expressions." structures]";
						if($label == "slice") $start_chunk = '';
						if($k < (strlen($line_recoded) - 1) OR $label == "slice") $chunked = TRUE;
						}
					if($test_legato) echo "<br />";
					}
				}
			}
		}
//	$chunked = TRUE;
//	if($chunked OR $label == "units") {
//	if($i_item == 2) echo "(".$i_item.") ".$all_lines_chunked."<br />";
	if($chunked) {
		$all_lines_chunked = preg_replace("/ +/u",' ',$all_lines_chunked);
		$all_lines_chunked = str_replace("{ ","{",$all_lines_chunked);
		if($total_ties > 0) $tie_mssg .=  " <i>total ".$total_ties." tied notes</i><br />";
		if($label == "units")
			$data_chunked = $temp_dir.$temp_folder.SLASH.$i_item."_".$label.".txt";
		else
			$data_chunked = $temp_dir.$temp_folder.SLASH.$i_item."-".$label.".bpda";
		$handle = fopen($data_chunked,"w");
		fwrite($handle,$all_lines_chunked.$linebreak);
		fclose($handle);
		@chmod($data_chunked,$permissions);
		}
	else $data_chunked = '';
	$segment['data'] = $data;
	$segment['line_recoded'] = $line_recoded;
	$segment['tie_mssg'] = $tie_mssg;
	$segment['chunked'] = $chunked;
	$segment['chunk_number'] = $chunk_number - 1;
	$segment['data_chunked'] = $data_chunked;
	$segment['title_this'] = $title_this;
	$segment['tonal_scale'] = $tonal_scale;
	$segment['initial_tempo'] = $initial_tempo;
	return $segment;
	}

function is_capture_file($capture_file) {
	$file = fopen($capture_file,'r');
    if($file) {
        $line = fgets($file); // names of parameters
        $table = explode("\t",$line);
        $max_args = count($table);
        if($max_args < 5) return FALSE;
		if($table[0] <> "time") return FALSE;
		if($table[1] <> "note") return FALSE;
		 return TRUE;
		}
	else return FALSE;
	}

function save($this_file,$filename,$top_header,$save_content) {
	global $permissions;
	if(trim($save_content) == '') return;
    if(file_exists($this_file)) {
        $backup_file = $this_file."_bak";
        if(!copy($this_file,$backup_file))
            echo "<p>👉 <span class=\"red-text\">Failed to create backup of the file.</span></p>";
		else @chmod($backup_file,$permissions);
		}
	$handle = @fopen($this_file, "w");
	if($handle) {
		$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle, $file_header."\n");
		fwrite($handle, $save_content);
		fclose($handle);
		@chmod($this_file,$permissions);
		}
	else {
		echo "<div style=\"background-color:white; color:black; padding: 1em; border-radius: 6px;\"><p>👉 <span class=\"red-text\"><b>WARNING</b>: Some files have been imported and cannot be modified.</span></p><p><b>Linux user?</b> Open your terminal and type: <span class=\"green-text\">sudo /opt/lampp/htdocs/bolprocessor/change_permissions.sh</span><br />(Your password will be required...)</p>";
		echo "</div>"; 
		}
	return;
	}

function delete_orphan_ties($data) {
	// Work in progress: it still keeps erratic ties, which are difficult to predict.
//	global $i_item;
	$i_item = 0;
	if(trim($data) == '') return $data;
//	return $data;
	$len = strlen($data);
	$notechar = $note_octave = FALSE;
	$data2 = $this_note = '';
	$hit = array();
	for($i = 0; $i < $len; $i++) {
		$c = $data[$i];
		if(preg_match('/^([a-zA-Z]|#)$/',$c)) {
			$notechar = TRUE;
			$note_octave = FALSE;
			$this_note .= $c;
			$data2 .= $c;
			continue;
			}
		if($notechar AND preg_match('/^([0-9])$/',$c)) {
			$note_octave = TRUE;
			$this_note .= $c;
			$data2 .= $c;
			continue;
			}
		if($c == '&') {
			$replacement = '&';
			if($note_octave AND $this_note <> '') {
				$pos = $i;
				if($i_item == 1) echo $i." -> ".$this_note."<br />";
				do {
					$pos2 = 0;
					if(is_integer($pos2=strpos($data,$this_note,$pos)) AND $pos2 > $pos) {
						if($data[$pos2 - 1] == '&') {
							if($i_item == 1) echo "@ ".($pos2 - 1)."<br />";
							$replacement = ';';
							$hit[$pos2 - 1] = $this_note;
							// Still, there is no certainty that the time of this note is later than the time of its linked occurrence.
							break;
							}
						else $pos = $pos2;
						}
					else break;
					}
				while(TRUE);
				}
			else {
				if(isset($hit[$i])) {
					$next_note = '';
					for($j = ($i + 1); $j < $len; $j++) {
						$d = $data[$j];
						if(preg_match('/^([a-zA-Z]|#)$/',$d)) $next_note .= $d;
						else if(preg_match('/^([0-9])$/',$d)) $next_note .= $d;
						else break;
						}
					if($i_item == 1) echo "hit = ".$hit[$i].", next_note = ".$next_note."<br />";
					if($hit[$i] == $next_note) {
						if($i_item == 1) echo "@@ ".($i)."<br />";
						$replacement = '§';
						}
					}
				}
			$data2 .= $replacement;
			$notechar = $note_octave = FALSE;
			}
		else {
			$data2 .= $c;
			$notechar = $note_octave = FALSE;
			$this_note = '';
			}
		}
	$data = str_replace('&','',$data2);
	$data = str_replace(';','&',$data);
	$data = str_replace('§','&',$data);
	return $data;
	}

// The following does not work yet. It is meant to handle "save" when typing command S
// The code for capturing the key is in _header.php

/* if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);
	save('".$this_file."', $data['filename'], $data['topHeader'], $data['content']);
	}

echo "<script>\n
function save() {
    const textAreaContent = document.getElementById('textArea').value;
    const thisFile = '".$this_file."';  // Adjust this path
    const filename = '".$filename."';  // Example filename
    const topHeader = '".$top_header."';  // Example header
    fetch(thisFile, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            content: textAreaContent,
            filename: filename,
            topHeader: topHeader
        })
    })
    .then(response => response.text())
    .then(data => {
        console.log('Success:', data);
    })
    .catch((error) => {
        console.error('Error:', error);
    });
}
</script>"; */
?>
