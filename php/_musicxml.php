<?php
function convert_musicxml($the_score,$repeat_section,$divisions,$midi_channel,$dynamic_control,$select_part,$ignore_dynamics,$tempo_option,$ignore_channels,$ignore_trills,$ignore_fermata,$ignore_arpeggios,$reload_musicxml,$test_musicxml,$change_metronome_average,$change_metronome_min,$change_metronome_max,$current_metronome_average,$current_metronome_min,$current_metronome_max,$list_corrections) {
	global $max_term_in_fraction;
	$grace_ratio = 2;
	// MakeMusic Finale dynamics https://en.wikipedia.org/wiki/Dynamics_(music)
	$dynamic_sign_to_volume = array("pppp" => 10, "ppp" => 23, "pp" => 36, "p" => 49, "mp" => 62, "mf" => 75, "f" => 88, "ff" => 101, "fff" => 114, "ffff" => 127);
	$dynamic_sign_to_tempo = array("Largo" => 50, "Lento" => 60, "Adagio" => 70, "Andante" => 88, "Moderato" => 100, "Allegretto" => 114, "Allegro" => 136, "Vivace" => 140, "Presto" => 170, "Prestissimo" => 190);
	$data =  $report = $old_measure_label = '';
	$measure_label = array();
	$sum_metronome = $number_metronome = $metronome_max = $metronome_min = 0;
	$said_tempo = FALSE;
	if(($current_metronome_min * $current_metronome_average * $current_metronome_max * $change_metronome_min * $change_metronome_average * $change_metronome_max) > 0) {
		$change_metronome = $quad = TRUE;
		// Quadratic mapping:
		$quad = TRUE;
		$quadratic_mapping = quadratic_mapping($current_metronome_min,$current_metronome_average,$current_metronome_max,$change_metronome_min,$change_metronome_average,$change_metronome_max);
		$a = $quadratic_mapping['a'];
		$b = $quadratic_mapping['b'];
		$c = $quadratic_mapping['c'];
		$y_prime1 = $quadratic_mapping['y_prime1'];
		$y_prime3 = $quadratic_mapping['y_prime3'];
		if($y_prime1 < 0 OR $y_prime3 < 0) { // Quadratic regression is not monotonous
			$quad = FALSE;
			$a1 = ($change_metronome_average - $change_metronome_min) / ($current_metronome_average - $current_metronome_min);
			$b1 = $change_metronome_min;
			$a2 = ($change_metronome_max - $change_metronome_average) / ($current_metronome_max - $current_metronome_average);
			$b2 = $change_metronome_average;
			}
		}
	else $change_metronome = FALSE;
		
	$sum_tempo_measure = $number_tempo_measure = $sum_volume_part = $number_volume_part = $default_tempo = $default_volume = $old_volume = $implicit = array();
	// $p_last_metronome = 0; $q_last_metronome = 1;
	$old_tempo = ''; $current_tempo = 1;
	$found_tempo_variation = FALSE;
	$fermata_show = FALSE; // Only for test
	ksort($the_score);
	foreach($the_score as $section => $the_section) {
		if($reload_musicxml) $report .= "<br /><b>== Section ".$section." ==</b><br />";
		$sum_tempo_measure[$section] = $number_tempo_measure[$section] = array();
		$sum_volume_part[$section] = $number_volume_part[$section] = array();
		$default_volume[$section] = array();
		$implicit[$section] = array();
		if(!isset($default_tempo[$section - 1])) $default_tempo[$section] = $current_tempo;
		else $default_tempo[$section] = $default_tempo[$section - 1];
		for($i_repeat = 1; $i_repeat <= $repeat_section[$section]; $i_repeat++) {
			$tie_type_start = $tie_type_stop = FALSE;
			if($test_musicxml) echo "Repetition ".$i_repeat." section ".$section."<br />";
			$p_fermata_date = $q_fermata_date = $p_fermata_duration = $q_fermata_duration = $p_fermata_total_duration = $q_fermata_total_duration = $p_date = $q_date = array();
		//	ksort($the_section); Never do this because $i_measure may not be an integer
		//  Beware that there are empty sessions
			foreach($the_section as $i_measure => $the_measure) {
				if($i_measure < 0) continue;
				if($reload_musicxml) $report .= "<br />";
				$physical_time = $max_physical_time = 0.;
				$sum_tempo_measure[$section][$i_measure] = 0;
				$number_tempo_measure[$section][$i_measure] = 0;
				$measure_label[$i_measure] = $i_measure;
				$implicit[$section][$i_measure] = FALSE;
				if(!is_integer($i_measure)) {
					$implicit[$section][$i_measure] = TRUE;
					$measure_label[$i_measure] = $old_measure_label."-".$measure_label[$i_measure];
					$report .= "• Implicit measure #".$i_measure." is labelled <font color=\"MediumTurquoise\">“".$measure_label[$i_measure]."”</font><br />";
					}
				else $old_measure_label = $measure_label[$i_measure];
				if($test_musicxml)
					echo "<font color = red>• Measure ".$measure_label[$i_measure]."</font><br />";
			//	ksort($the_measure);
				$curr_event = $convert_measure = $p_fermata_total_duration[$i_measure] = $q_fermata_total_duration[$i_measure] = $p_fermata_date[$i_measure] = $q_fermata_date[$i_measure] = $p_fermata_duration[$i_measure] = $q_fermata_duration[$i_measure] = array();
				$data .= "{";
				$number_parts = 0;
				$i_field_of_measure = 0; // Index of field irrespective of parts
				$i_new_tempo = -1;
				$p_next_date_new_tempo = $q_next_date_new_tempo = -1;
				$value_new_tempo = $p_date_new_tempo = $q_date_new_tempo = $p_field_duration = $q_field_duration = $empty_field = array();
				$p_date[$i_measure] = $q_date[$i_measure] = array();
				ksort($the_measure);
				foreach($the_measure as $score_part => $the_part) {
					if(!$reload_musicxml OR !$select_part[$score_part]) {
						if($test_musicxml AND $reload_musicxml) echo "Score part ".$score_part." not selected in section ".$section."<br />";
						continue;
						}
					$number_parts++;
					$i_field_of_part = $i_field_of_part2 = 0;
					$i_fermata = 0;
					$p_date[$i_measure][$score_part] = 0; $q_date[$i_measure][$score_part] = 1;
					$p_fermata_total_duration[$i_measure][$score_part] = 0;
					$q_fermata_total_duration[$i_measure][$score_part] = 1;
					
					$simplify = simplify($default_tempo[$section],$max_term_in_fraction);
					$n = $simplify['p'] / $simplify['q'];
					$current_period = 1 / $n;
					if($reload_musicxml) $report .= "• Measure #".$i_measure." part [".$score_part."] starts with current period = ".round($current_period,2)."s, current tempo = ".$current_tempo.", default tempo = ".$default_tempo[$section]." (metronome = ".(60 * $n).")<br />";
				
					$p_fermata_date[$i_measure][$score_part] = $q_fermata_date[$i_measure][$score_part] = $p_fermata_duration[$i_measure][$score_part] = $q_fermata_duration[$i_measure][$score_part] = array();
					$sum_volume_part[$section][$score_part] = $number_volume_part[$section][$score_part] = 0;
					if(!isset($old_volume[$score_part])) $old_volume[$score_part] = 64;
					if($test_musicxml) echo "• Measure ".$i_measure." part ".$score_part."<br />";
					ksort($the_part);
					$this_note = $some_words = '';
					$note_on = $is_chord = $rest = $pitch = $unpitched = $time_modification = $forward = $backup = $chord_in_process = $dynamics = FALSE;
					$alter = $level = 0;
					$this_octave = -1;
					$curr_event[$score_part] = $convert_measure = array();
					$j = 0;
					$curr_event[$score_part][$j]['type'] = "seq";
					$curr_event[$score_part][$j]['fermata'] = FALSE;
					
					foreach($the_part as $i_line => $line) {
						if($test_musicxml)
							echo "<small>".$j." ".recode_tags($line)."</small><br />";
						if(is_integer($pos=strpos($line,"<note ")) OR is_integer($pos=strpos($line,"<note>"))) {
							$note_on = TRUE;
							$rest = $fermata = FALSE;
							$is_chord = FALSE;
							}
						if($note_on AND is_integer($pos=strpos($line,"</note>"))) {
							if($j > 0 AND $is_chord) { // $j = 0 is very unlikely but make sure!
								// This is the first note tagged ‘chord’ which means the preceding one belongs to the same chord
								$curr_event[$score_part][$j-1]['type'] = "chord";
								$chord_in_process = TRUE;
								}
							if(!$is_chord AND $chord_in_process) {
								$chord_in_process = FALSE;
								$p_this_dur = $curr_event[$score_part][$j]['p_dur'];
								$q_this_dur = $curr_event[$score_part][$j]['q_dur'];
								$curr_event[$score_part][$j]['type'] = "seq"; // Create null event to  mark end of chord;
								$curr_event[$score_part][$j]['note'] = '';
								$curr_event[$score_part][$j]['p_dur'] = 0;
								$curr_event[$score_part][$j]['q_dur'] = 1;
								$j++;
								$curr_event[$score_part][$j]['type'] = "seq";
								$curr_event[$score_part][$j]['note'] = $this_note;
								if($alter <> 0) {
									if($alter == 2) $curr_event[$score_part][$j]['note'] .= "##";
									if($alter == 1) $curr_event[$score_part][$j]['note'] .= "#";
									if($alter == -1) $curr_event[$score_part][$j]['note'] .= "b";
									if($alter == -2) $curr_event[$score_part][$j]['note'] .= "bb";
									}
								$curr_event[$score_part][$j]['p_dur'] = $p_this_dur;
								$curr_event[$score_part][$j]['q_dur'] = $q_this_dur;
								}
				//			if($fermata AND $i_field_of_part2 == 0 AND !$rest AND $curr_event[$score_part][$j]['p_dur'] > 0) {
							if($fermata AND $i_field_of_part2 == 0 AND $curr_event[$score_part][$j]['p_dur'] > 0) {
								if($fermata_show) $this_note .= "fermata";
								$add = add($p_fermata_total_duration[$i_measure][$score_part],$q_fermata_total_duration[$i_measure][$score_part],$curr_event[$score_part][$j]['p_dur'],$curr_event[$score_part][$j]['q_dur']);
								$p_fermata_total_duration[$i_measure][$score_part] = $add['p'];
								$q_fermata_total_duration[$i_measure][$score_part] = $add['q'];
								$curr_event[$score_part][$j]['p_dur'] += $curr_event[$score_part][$j]['p_dur'];
								$curr_event[$score_part][$j]['fermata'] = TRUE;
								$p_fermata_date[$i_measure][$score_part][$i_fermata] = $p_date[$i_measure][$score_part];
								$q_fermata_date[$i_measure][$score_part][$i_fermata] = $q_date[$i_measure][$score_part];
								$p_fermata_duration[$i_measure][$score_part][$i_fermata] = $curr_event[$score_part][$j]['p_dur'] / 2;
								$q_fermata_duration[$i_measure][$score_part][$i_fermata] = $curr_event[$score_part][$j]['q_dur'];
								//	echo "•• Measure #".$i_measure." part ".$score_part." a note is fermata #".$i_fermata." at date = ".$p_fermata_date[$i_measure][$score_part][$i_fermata]."/".$q_fermata_date[$i_measure][$score_part][$i_fermata]."<br />";
								$i_fermata++;
								}
							if($rest) {
								$curr_event[$score_part][$j]['note'] = "-";
								if($fermata AND $fermata_show) $curr_event[$score_part][$j]['note'] .= "fermata";
								}
							else {
								if(!$is_chord AND !isset($curr_event[$score_part][$j]['type'])) $curr_event[$score_part][$j]['type'] = 'seq'; // Added by BB 2021-03-01
								$curr_event[$score_part][$j]['note'] = $this_note;
								if($alter <> 0) {
									if($alter == 2) $curr_event[$score_part][$j]['note'] .= "##";
									if($alter == 1) $curr_event[$score_part][$j]['note'] .= "#";
									if($alter == -1) $curr_event[$score_part][$j]['note'] .= "b";
									if($alter == -2) $curr_event[$score_part][$j]['note'] .= "bb";
									}
								if($this_octave >= 0) $curr_event[$score_part][$j]['note'] .= $this_octave;
								if($tie_type_start) $curr_event[$score_part][$j]['note'] .= "&";
								if($tie_type_stop) $curr_event[$score_part][$j]['note'] = "&".$curr_event[$score_part][$j]['note'];
								if($test_musicxml)
									echo $curr_event[$score_part][$j]['note']." ".$curr_event[$score_part][$j]['type']." j = ".$j."<br />";
								}
							if(!$is_chord) {
								$add = add($p_date[$i_measure][$score_part],$q_date[$i_measure][$score_part],$curr_event[$score_part][$j]['p_dur'],$curr_event[$score_part][$j]['q_dur']);
								$p_date[$i_measure][$score_part] = $add['p'];
								$q_date[$i_measure][$score_part] = $add['q'];
								}
							$note_on = $rest = $fermata = $is_chord = FALSE;
							$tie_type_start = $tie_type_stop = FALSE;
							$j++;
							$curr_event[$score_part][$j]['type'] = "seq";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$curr_event[$score_part][$j]['fermata'] = FALSE;
							}
						if($note_on AND is_integer($pos=strpos($line,"<unpitched>"))) {
							$unpitched = TRUE; // Drum stroke
							$alter = 0;
							}
						if($unpitched AND is_integer($pos=strpos($line,"<display-step>"))) {
							$this_note = trim(preg_replace("/<display-step>(.+)<\/display\-step>/u","$1",$line));
							}
						if($unpitched AND is_integer($pos=strpos($line,"<display-octave>"))) {
							$this_octave = trim(preg_replace("/<display-octave>(.+)<\/display\-octave>/u","$1",$line));
							}
						if($note_on AND is_integer($pos=strpos($line,"</unpitched>"))) {
							$unpitched = FALSE;
							$alter = 0;
							}
						if($note_on AND is_integer($pos=strpos($line,"<pitch>"))) {
							$pitch = TRUE;
							$alter = 0;
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
						if($note_on AND is_integer($pos=strpos($line,"</pitch>"))) {
							$pitch = FALSE;
							}
						if($note_on AND (is_integer($pos=strpos($line,"<rest ")) OR is_integer($pos=strpos($line,"<rest/>")) OR is_integer($pos=strpos($line,"<rest>")))) {
							$rest = TRUE;
							$is_chord = FALSE;
							$curr_event[$score_part][$j]['type'] = "seq";
							$this_octave = -1;
							}
						if(!$ignore_fermata AND is_integer($pos=strpos($line,"<fermata"))) {
							$fermata = TRUE;
							}
						if($note_on AND !$ignore_trills AND is_integer($pos=strpos($line,"<trill-mark"))) {
							$curr_event[$score_part][$j]['trill'] = TRUE;
							}
						if($note_on AND is_integer($pos=strpos($line,"<duration>"))) {
							$duration = round(trim(preg_replace("/<duration>([^<]+)<\/duration>/u","$1",$line)));
							$curr_event[$score_part][$j]['p_dur'] = $duration;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if($note_on AND (is_integer($pos=strpos($line,"grace/>")) OR is_integer($pos=strpos($line,"<grace ")))) {
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if($note_on AND is_integer($pos=strpos($line,"<chord/>"))) {
							$is_chord = TRUE;
							$curr_event[$score_part][$j]['type'] = "chord";
							}
						if($note_on AND is_integer($pos=strpos($line,"<tie type"))) {
							$tie_type = trim(preg_replace("/.+type=\"([^\"]+)\"\/>/u","$1",$line));
							if($tie_type == "start") $tie_type_start = TRUE;
							if($tie_type == "stop") $tie_type_stop = TRUE;
							}
							
						if(($tempo_option == "all" OR $tempo_option == "allbutmeasures") AND is_integer($pos=strpos($line,"<sound tempo"))) {
							$tempo = round(trim(preg_replace("/.+tempo=\"([^\"]+)\"\/>/u","$1",$line)));
							// echo $score_part." mm = ".$tempo." at measure #".$i_measure."<br />";
							
							if($change_metronome) {
								if(!$said_tempo) {
									if($quad) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
									else $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
									$said_tempo = TRUE;
									}
								if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
								else {
									if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
									else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
									}
								$report .= "Metronome changed from ".$tempo." (non-printed) to ".$tempo_changed."<br />";
								$tempo = $tempo_changed;
								}
							
							$sum_tempo_measure[$section][$i_measure] += $tempo;
							$number_tempo_measure[$section][$i_measure]++;
							$sum_metronome += $tempo;
							$number_metronome++;
							if($tempo > $metronome_max) $metronome_max = $tempo;
							if($tempo < $metronome_min OR $metronome_min == 0) $metronome_min = $tempo;
							if($tempo_option <> "allbutmeasures") {
								if(!$found_tempo_variation)
									echo "<p><font color=\"red\">➡</font> Including tempo assignments inside measures</p>";
								$found_tempo_variation = TRUE;
								$curr_event[$score_part][$j]['type'] = "mm";
								$curr_event[$score_part][$j]['value'] = $tempo;
								$curr_event[$score_part][$j]['p_dur'] = 0;
								$curr_event[$score_part][$j]['q_dur'] = 1;
								$is_chord = FALSE;
								$j++;
								}
							continue;
							// Note that tempo values will be used in all subsequent parts
							// This may create a problem if they don't appear in the first selected part
							}
							
						if(($tempo_option == "all" OR $tempo_option == "score" OR $tempo_option == "allbutmeasures") AND is_integer($pos=strpos($line,"<per-minute>"))) {
							$tempo = round(trim(preg_replace("/<per\-minute>([^<]+)<\/per\-minute>/u","$1",$line)));
						//	echo $score_part." tempo = ".$tempo." bpm at measure ".$i_measure." (on the printed score)<br />";
							if($change_metronome) {
								if(!$said_tempo) {
									if($quad) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
									else $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
									$said_tempo = TRUE;
									}
								if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
								else {
									if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
									else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
									}
								$report .= "Metronome changed from ".$tempo." (printed score) to ".$tempo_changed."<br />";
								$tempo = $tempo_changed;
								}
							$sum_tempo_measure[$section][$i_measure] += $tempo;
							$number_tempo_measure[$section][$i_measure]++;
							$sum_metronome += $tempo;
							$number_metronome++;
							if($tempo > $metronome_max) $metronome_max = $tempo;
							if($tempo < $metronome_min OR $metronome_min == 0) $metronome_min = $tempo;
							
							$curr_event[$score_part][$j]['type'] = "mm"; // Added by BB 2021-03-14
							$curr_event[$score_part][$j]['value'] = $tempo;
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$is_chord = FALSE;
							$j++;
							continue;
							// Note that tempo values will be used in all subsequent parts
							// This may create a problem if they don't appear in the first selected part
							}
						
						if(!$ignore_dynamics AND is_integer($pos=strpos($line,"<sound dynamics"))) {
							$volume = trim(preg_replace("/.+dynamics=\"([^\"]+)\"\/>/u","$1",$line));
						//	echo $score_part." volume = ".$volume." at measure ".$i_measure."<br />";
							$sum_volume_part[$section][$score_part] += $volume;
							$number_volume_part[$section][$score_part]++;
							// This may cancel the value estimated from the graphic sign previously encountered
							}
						
						if(!$ignore_dynamics AND is_integer($pos=strpos($line,"<dynamics "))) {
							$dynamics = TRUE; continue;
							}
						if(is_integer($pos=strpos($line,"</dynamics>"))) $dynamics = FALSE;
						if($dynamics) {
							$sign = trim(preg_replace("/<([^\/]+)\/>/u","$1",$line));
							if(isset($dynamic_sign_to_volume[$sign])) {
								// This estimation replaces a missing value of volume
								$volume = $dynamic_sign_to_volume[$sign];
								$sum_volume_part[$section][$score_part] += $volume;
								$number_volume_part[$section][$score_part]++;
								}
							}
						
						if($tempo_option <> "ignore" AND is_integer($pos=strpos($line,"<words"))) {
							$some_words = trim(preg_replace("/.*<words[^>]*>([^<]+)\s*<\/.+/u","$1",$line));
							if(strlen($some_words) > 2) {
							//	echo "<br />line = “".recode_tags($line)."”<br />";
								foreach($dynamic_sign_to_tempo as $dynamic_sign => $tempo) {
									if(is_integer(stripos($some_words,$dynamic_sign))) {
							//			echo "Words = “".recode_tags($some_words)."” at measure ".$i_measure." => ".$dynamic_sign." = ".$tempo."<br />";
										if($change_metronome) {
											if(!$said_tempo) {
												if($quad) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
												else $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
												$said_tempo = TRUE;
												}
											if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
											else {
												if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
												else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
												}
											$report .= "Metronome changed from ".$tempo." (".$some_words.") to ".$tempo_changed."<br />";
											$tempo = $tempo_changed;
											}
									//	$sum_tempo_measure[$section][$i_measure] += $tempo;
									//	$number_tempo_measure[$section][$i_measure]++;
										
										$curr_event[$score_part][$j]['type'] = "mm"; // Added by BB 2021-03-14
										$curr_event[$score_part][$j]['value'] = round($tempo);
										$curr_event[$score_part][$j]['p_dur'] = 0;
										$curr_event[$score_part][$j]['q_dur'] = 1;
										$curr_event[$score_part][$j]['word'] = $dynamic_sign;
										$is_chord = FALSE;
										$j++;
										break;
										}
									}
								}
							$some_words = '';
							continue;
							}
						if(is_integer($pos=strpos($line,"<backup>"))) {
							$backup = TRUE;
							$is_chord = FALSE;
							$i_field_of_part2++;
							$p_date[$i_measure][$score_part] = 0;
							$q_date[$i_measure][$score_part] = 1;
							}
						if($backup AND is_integer($pos=strpos($line,"<duration>"))) {
							$duration = intval(trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line)));
							$curr_event[$score_part][$j]['type'] = "back";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = $duration;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$j++;
							$curr_event[$score_part][$j]['type'] = "seq";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if(is_integer($pos=strpos($line,"</backup>"))) {
							$backup = FALSE;
							}
						if(is_integer($pos=strpos($line,"<forward>"))) {
							$forward = TRUE;
							$is_chord = FALSE;
							}
						if(($forward) AND is_integer($pos=strpos($line,"<duration>"))) {
							$duration = trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line));
							$curr_event[$score_part][$j]['note'] = '-';
							$curr_event[$score_part][$j]['p_dur'] = $duration;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$curr_event[$score_part][$j]['forward'] = TRUE;
							$j++;
							$curr_event[$score_part][$j]['type'] = "seq";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if(is_integer($pos=strpos($line,"</forward>"))) {
							$forward = FALSE;
							}
						if($note_on AND is_integer($pos=strpos($line,"<arpeggiate "))) {
							$curr_event[$score_part][$j]['arpeggiate'] = TRUE;
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
					// The following are not required
					//		$curr_event[$score_part][$j]['p_dur'] = $curr_event[$score_part][$j]['p_dur'] * $normal_notes;
					//		$curr_event[$score_part][$j]['q_dur'] = $curr_event[$score_part][$j]['q_dur'] * $actual_notes;
							}
						}
					unset($line);
					
					// Now let us transfer events of this measure to $data
					$convert_measure[$score_part] = '';
					if(count($the_measure) > 1) $convert_measure[$score_part] .= "{";
					$is_chord = FALSE;
					$stream = ''; $stream_units = 0;
					$p_time_measure = $p_time_field = $p_stream_duration = $new_tempo = $i_fermata = 0;
					$p_old_duration = -1; $q_old_duration = 1;
					$q_time_measure = $q_time_field = $q_old_duration = $q_stream_duration = 1;
					
					// Find date of next fermata
					$p_date_next_fermata = -1;
					if(count($p_fermata_date[$i_measure][$score_part]) > 0) {
						$i_fermata = 0;
						$p_date_next_fermata = $p_fermata_date[$i_measure][$score_part][$i_fermata];
						$q_date_next_fermata = $q_fermata_date[$i_measure][$score_part][$i_fermata];
						}
					$empty_field[0] = TRUE;
					$last_i_new_tempo[0] = -1;
					$p_last_next_date_new_tempo[0] = 0; $q_last_next_date_new_tempo[0] = 1;
					
					ksort($curr_event[$score_part]);
					foreach($curr_event[$score_part] as $j => $the_event) {
						if($the_event['type'] == "mm") {
							$new_tempo = $the_event['value'];
							$fraction = $new_tempo."/60";
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							if(isset($the_event['word'])) {
								$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." dynamic sign “<font color=\"blue\">".$the_event['word']."</font>” metronome set to ".$new_tempo."<br />";
								$current_period = 60 / $new_tempo;
								$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
								}
							if($i_field_of_measure == 0) {
								// We will store new tempo values appearing in the first field (upper line)
								$i_new_tempo++;
								$value_new_tempo[$i_new_tempo] = $new_tempo;
								$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
								$p_date_this_tempo = $add['p'];
								$q_date_this_tempo = $add['q'];
								$p_date_new_tempo[$i_new_tempo] = $p_date_this_tempo;
								$q_date_new_tempo[$i_new_tempo] = $q_date_this_tempo;
								if($p_time_field == 0) {
									$current_period = 60 / $new_tempo;
									$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
									}
								}
							}
						if($i_field_of_measure > 0) {
							// Find date of next new tempo 
							if($i_new_tempo == 0 AND isset($p_date_new_tempo[$i_new_tempo])) {
								$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
								$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
								}
							if($p_next_date_new_tempo >= 0) {
								$add = add($p_time_field,$q_time_field,-$p_next_date_new_tempo,$q_next_date_new_tempo);
								// $report .= "i_new_tempo = ".$i_new_tempo." time_field = ".$p_time_field."/".$q_time_field."  - next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." = ".$add['p']."/".$add['q']."<br />";
								if(($add['p'] * $add['q']) >= 0 OR ($add['q'] <> 0 AND abs($add['p'] / $add['q'] / $divisions[$score_part]) < 0.05)) { // Here we eliminate rounding errors
									$new_tempo = $value_new_tempo[$i_new_tempo];
									// $report .= "measure #".$i_measure." field #".($i_field_of_part + 1)." : new_tempo = ".$new_tempo." i_new_tempo = ".$i_new_tempo." time_field = ".$p_time_field."/".$q_time_field."<br />";
									$i_new_tempo++;
									if(isset($p_date_new_tempo[$i_new_tempo])) {
										$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
										$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
										$last_i_new_tempo[$i_field_of_part] = $i_new_tempo;
										$p_last_next_date_new_tempo[$i_field_of_part] = $p_next_date_new_tempo;
										$q_last_next_date_new_tempo[$i_field_of_part] = $q_next_date_new_tempo;
										}
									else {
										$p_next_date_new_tempo = $q_next_date_new_tempo = $last_i_new_tempo[$i_field_of_part] = -1;
										}
									}
								}
							}
						$fraction_date = $p_time_field."/".($q_time_field * $divisions[$score_part]);
						$simplify_date = simplify($fraction_date,$max_term_in_fraction);
						$fraction_date = $simplify_date['fraction'];
								
						if($p_time_field == 0 AND $i_field_of_part == 0 AND $stream_units > 0 AND $p_stream_duration == 0 AND $new_tempo > 0) {
							// Grace notes starting measure (e.g. measure #1 of Beethoven-fugue-b-flat-major) 
							$convert_measure[$score_part] .= "||".$new_tempo."||";
							$current_period = 60 / $new_tempo;
							$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
							
							$fraction = $new_tempo."/60";
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
								
							$new_tempo = 0;
							}
						if($the_event['type'] == "back" OR ($p_stream_duration > 0 AND $new_tempo > 0)) {
							if($is_chord) {
								$convert_measure[$score_part] .= "}";
								$is_chord = FALSE;
								}
							if($i_field_of_part > 0 AND $new_tempo > 0) {
								$convert_measure[$score_part] .= "||".$new_tempo."||";
								$current_period = 60 / $new_tempo;
								$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
								$new_tempo = 0;
								}
							if($test_musicxml)
								echo " time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." the_event[dur] = ".$the_event['p_dur']."/".$the_event['q_dur'].", divisions = ".$divisions[$score_part]." stream = “".$stream."”<br />";
							if($stream_units > 0) {
						//		$report .= "Stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
								$physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
								$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
								$p_time_field = $add['p']; $q_time_field = $add['q'];
								$fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
								$simplify = simplify($fraction,$max_term_in_fraction);
								$fraction = $simplify['fraction'];
								if($simplify['q'] > 0) $n = $simplify['p'] / $simplify['q'];
								else $n = 0;
								if($stream_units <> $n) {
									if((trim($stream) == "-" OR trim($stream) == "{-}") AND is_int($fraction) AND $fraction < 4) {
										// Short sequence of '-'
										$stream = '';
										for($i = 0; $i < $fraction; $i++) $stream .= "- ";
										$stream_units = $n = $fraction;
										}
									else {
										$convert_measure[$score_part] .= "{";
										$convert_measure[$score_part] .= $fraction.",";
										}
									}
								$convert_measure[$score_part] .= $stream;
								if($stream_units <> $n) $convert_measure[$score_part] .= "}";
								if($new_tempo > 0) {
									$convert_measure[$score_part] .= "||".$new_tempo."||";
									$current_period = 60 / $new_tempo;
									$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
									if($p_time_field == 0 AND $i_field_of_part == 0) {
										$fraction = $new_tempo."/60";
										$simplify = simplify($fraction,$max_term_in_fraction);
										$fraction = $simplify['fraction'];
										$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
										}
									$new_tempo = 0;
									}
								$stream = ''; $stream_units = 0; $p_stream_duration = 0; $q_stream_duration = 1;
								}
							else {
								if($new_tempo > 0) {
									$stream .= "||".$new_tempo."||";
									$current_period = 60 / $new_tempo;
									$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
									if($p_time_field == 0 AND $i_field_of_part == 0) {
										$fraction = $new_tempo."/60";
										$simplify = simplify($fraction,$max_term_in_fraction);
										$fraction = $simplify['fraction'];
										$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
										}
									$new_tempo = 0;
									}
								}
							if($the_event['type'] == "back") {
								$convert_measure[$score_part] .= "§".$i_field_of_part."§,";
								$p_field_duration[$i_field_of_part] = $p_time_field;
								$q_field_duration[$i_field_of_part] = $q_time_field * $divisions[$score_part];
								$report .= "+ measure #".$i_measure." field #".($i_field_of_part + 1)." : physical time = ".round($physical_time,2)."s";
								if($empty_field[$i_field_of_part]) $report .= " (only silence)";
								$report .= "<br />";
								if(!$empty_field[$i_field_of_part] AND $physical_time > $max_physical_time)
									$max_physical_time = $physical_time;
								$physical_time = 0.;
								$i_field_of_part++; $i_field_of_measure++;
								$empty_field[$i_field_of_part] = TRUE;
								$new_tempo = 0;
								$i_new_tempo = 0;
								$last_i_new_tempo[$i_field_of_part] = -1;
								if(count($p_fermata_date[$i_measure][$score_part]) > 0) {
									$i_fermata = 0;
									$p_date_next_fermata = $p_fermata_date[$i_measure][$score_part][$i_fermata];
									$q_date_next_fermata = $q_fermata_date[$i_measure][$score_part][$i_fermata];
									}
								// Find whether there is time for a rest
								// Fermata of previous field have not been counted in the backup value. We'll add them
								$p_duration = $the_event['p_dur']; $q_duration = $the_event['q_dur'];
								$add = add($p_duration,$q_duration,$p_fermata_total_duration[$i_measure][$score_part],$q_fermata_total_duration[$i_measure][$score_part]);
								$p_duration = $add['p'];
								$q_duration = $add['q'];
								$add = add($p_time_field,$q_time_field,(-$p_duration),$q_duration);
								$p_rest = $add['p'];
								$q_rest = $add['q'];
								$gcd = gcd($p_rest,$q_rest * $divisions[$score_part]);
								$p_rest_duration = $p_rest / $gcd;
								$q_rest_duration = $q_rest * $divisions[$score_part] / $gcd;
								if($test_musicxml)
									echo "back measure #".$i_measure." field #".($i_field_of_part + 1)." : duration = ".$p_time_field."/".$q_time_field." - ".$p_duration."/".$q_duration." = ".$p_rest."/".$q_rest.", fermata_duration (field #".($i_field_of_part - 1).") = ".$p_fermata_total_duration[$i_measure][$score_part]."/".$q_fermata_total_duration[$i_measure][$score_part].", divisions = ".$divisions[$score_part]."<br />";
								$add = add($p_time_measure,$q_time_measure,(-$p_time_field),$q_time_field);
								if(($add['p'] * $add['q']) < 0) {
									$p_time_measure = $p_time_field;
									$q_time_measure = $q_time_field;
									}
								$p_time_field = 0; $q_time_field = 1;
								$p_old_duration = -1; $q_old_duration = 1;
								if(($p_rest * $q_rest) > 0 AND ($p_rest/$q_rest) > ($divisions[$score_part]/100)) {
									// Here we could also use ‘_rest’ and let the algorithm figure out the duration
									$stream = "-"; $stream_units = 1;
									$p_stream_duration = $p_rest;
									$q_stream_duration = $q_rest;
									if($test_musicxml)
										echo "Inserting rest after backup duration ".$p_rest_duration."/".$q_rest_duration." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
									}
								else {
									$fraction = abs($p_rest_duration)."/".abs($q_rest_duration);
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
									if(($p_rest * $q_rest) < 0) {
										$report .= "<font color=\"red\">➡ </font> Error in measure ".$i_measure." part ".$score_part." field #".($i_field_of_part + 1).", ‘backup’ rest = -".$fraction." beat(s) <font color=\"MediumTurquoise\">(fixed)</font><br />";
										}
									else if($p_rest > 0 AND ($p_rest/$q_rest) <= ($divisions[$score_part]/100)) {
										$report .= "<font color=\"MediumTurquoise\">• </font> Rounding part ".$score_part." measure ".$i_measure." field #".($i_field_of_part + 1).", neglecting ‘backup’ rest = ".$fraction."<br />";
										}
									$p_rest = 0; $q_rest = 1;
									$stream = ''; $stream_units = 0;
									$p_time_field = $p_stream_duration = $p_rest;
									$q_time_field = $q_stream_duration = $q_rest;
									}
								}
							}
						if(!$is_chord AND $the_event['type'] == "chord")
							$starting_chord = TRUE;
							
						if(isset($the_event['fermata']) AND $the_event['fermata'] AND $the_event['note'] <> '') {
							$fraction = $p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".($q_fermata_duration[$i_measure][$score_part][$i_fermata] * $divisions[$score_part]);
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							$physical_time += $current_period * ($p_fermata_duration[$i_measure][$score_part][$i_fermata] / $q_fermata_duration[$i_measure][$score_part][$i_fermata] / $divisions[$score_part]);
							// $report .= "physical_time1 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
					//		if($i_field_of_part == 0) $physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
							$fraction_date = $add['p']."/".($add['q'] * $divisions[$score_part]);
							$simplify_date = simplify($fraction_date,$max_term_in_fraction);
							$fraction_date = $simplify_date['fraction'];
							$report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." note ‘".$the_event['note']."’ at date ".$fraction_date." increased by ".$fraction ." beat(s) as per fermata #".($i_fermata + 1)."<br />";
							}
						if($i_field_of_part > 0 AND $p_date_next_fermata >= 0 AND ($starting_chord OR $the_event['type'] == "seq") AND $the_event['note'] <> '') {
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
							$add = add($add['p'],$add['q'],-$p_date_next_fermata,$q_date_next_fermata);
							if(($add['p'] * $add['q']) >= 0) {
								$the_event['fermata'] = TRUE;
								// Fermata applied to note or rest
								$physical_time += $current_period * ($p_fermata_duration[$i_measure][$score_part][$i_fermata] / $q_fermata_duration[$i_measure][$score_part][$i_fermata] / $divisions[$score_part]);
								// $report .= "physical_time2 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
								$add = add($the_event['p_dur'],$the_event['q_dur'],$p_fermata_duration[$i_measure][$score_part][$i_fermata],$q_fermata_duration[$i_measure][$score_part][$i_fermata]);
								$the_event['p_dur'] = $add['p'];
								$the_event['q_dur'] = $add['q'];
								$fraction = $p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".($q_fermata_duration[$i_measure][$score_part][$i_fermata] * $divisions[$score_part]);
								$simplify = simplify($fraction,$max_term_in_fraction);
								$fraction = $simplify['fraction'];
								$fraction_date = $p_date_next_fermata."/".($q_date_next_fermata * $divisions[$score_part]);
								$simplify_date = simplify($fraction_date,$max_term_in_fraction);
								$fraction_date = $simplify_date['fraction'];
								$report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." note ‘".$the_event['note']."’ at date ".$fraction_date." increased by ".$fraction." beat(s) to insert fermata #".($i_fermata + 1)."<br />";
								}
							else if($the_event['note'] == "-") {
								$add = add($add['p'],$add['q'],$the_event['p_dur'],$the_event['q_dur']);
								if(($add['p'] * $add['q']) >= 0) {
									$the_event['fermata'] = TRUE;
									// Fermata occuring inside a rest
									$physical_time += $current_period * ($p_fermata_duration[$i_measure][$score_part][$i_fermata] / $q_fermata_duration[$i_measure][$score_part][$i_fermata] / $divisions[$score_part]);
									// $report .= "physical_time3 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
									$add = add($the_event['p_dur'],$the_event['q_dur'],$p_fermata_duration[$i_measure][$score_part][$i_fermata],$q_fermata_duration[$i_measure][$score_part][$i_fermata]);
									$fraction = $p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".($q_fermata_duration[$i_measure][$score_part][$i_fermata] * $divisions[$score_part]);
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
									$fraction_date = $p_date_next_fermata."/".($q_date_next_fermata * $divisions[$score_part]);
									$simplify_date = simplify($fraction_date,$max_term_in_fraction);
									$fraction_date = $simplify_date['fraction'];
									$report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." silence at date ".$fraction_date." increased by ".$fraction." to insert fermata #".($i_fermata + 1)."<br />";
									$the_event['p_dur'] = $add['p'];
									$the_event['q_dur'] = $add['q'];
									}
								}
							}
						if(isset($the_event['fermata']) AND $the_event['fermata']) {
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
							$i_fermata++;
							if(isset($p_fermata_date[$i_measure][$score_part][$i_fermata])) {
								$p_date_next_fermata = $p_fermata_date[$i_measure][$score_part][$i_fermata];
								$q_date_next_fermata = $q_fermata_date[$i_measure][$score_part][$i_fermata];
							//	$report .= "Next fermata date (#".$i_fermata.") = ".$p_date_next_fermata."/".$q_date_next_fermata."<br />";
								}
							else $p_date_next_fermata = -1;
							}	
						$starting_chord = FALSE;
							
						if(!$is_chord AND $the_event['type'] == "chord") {
							if($new_tempo > 0) {
								$convert_measure[$score_part] .= "||".$new_tempo."||";
								$current_period = 60 / $new_tempo;
								/* if($fraction_date <> "0")*/ $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
								if($p_time_field == 0 AND $i_field_of_part == 0) {
									$fraction = $new_tempo."/60";
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
									$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
									}
								$new_tempo = 0;
								}
							$p_duration = $the_event['p_dur']; $q_duration = $the_event['q_dur'];
							if($stream_units > 0) {
								if($test_musicxml)
									echo $the_event['note']." time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream_units = ".$stream_units." closing stream = “".$stream."”<br />";
								if($p_stream_duration == 0) { // Grace notes
						//		$report .= "Grace stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
									$this_grace_ratio = $grace_ratio;
									if($stream_units < 2) $this_grace_ratio = 4 * $grace_ratio;
									$p_grace = $stream_units * $divisions[$score_part];
									$q_grace = $this_grace_ratio;
									if(($p_duration/$q_duration) < (2 * $p_grace/$q_grace)) {
										$p_grace = $p_duration;
										$q_grace = $this_grace_ratio * $q_duration;
										}
									$add2 = add($p_duration,$q_duration,-$p_grace,$q_grace);
									$p_duration = $add2['p']; $q_duration = $add2['q'];
									$add = add($p_time_field,$q_time_field,$p_grace,$q_grace);
									$p_time_field = $add['p']; $q_time_field = $add['q'];
									/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_grace / $q_grace / $divisions[$score_part]);
									$fraction = $p_grace."/".($q_grace * $divisions[$score_part]);
									}
								else {
									$fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
									$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
									$p_time_field = $add['p']; $q_time_field = $add['q'];
									/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
									}
								
								$simplify = simplify($fraction,$max_term_in_fraction);
								$fraction = $simplify['fraction'];
								$convert_measure[$score_part] .= "{".$fraction.",".$stream."}";
								$stream = ''; $stream_units = 0; $p_stream_duration = 0; $q_stream_duration = 1;
								}
							if($test_musicxml)
								echo "measure #".$i_measure." note ".$the_event['note']." (in chord) field #".($i_field_of_part + 1)." time_field = ".$p_time_field."/".$q_time_field." event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']." divisions[score_part] = ".$divisions[$score_part]."<br />";
							if($the_event['p_dur'] > 0 AND is_integer($pos=strpos($convert_measure[$score_part],"FRACTION_0"))) {
								// Fixing duration of grace notes in preceding chord
								$p_grace = $p_duration;
								$q_grace = $q_duration * 2 * $grace_ratio;
								
								$fraction2 = $p_grace."/".($q_grace * $divisions[$score_part]);
								$simplify = simplify($fraction2,$max_term_in_fraction);
								$fraction2 = $simplify['fraction'];
								$convert_measure[$score_part] = str_replace("FRACTION_0",$fraction2,$convert_measure[$score_part]);
								}
							if(!$ignore_arpeggios AND isset($the_event['arpeggiate'])) {
								$convert_measure[$score_part] .= "arpeggiate";
								$report .= "<font color=\"blue\">Arpeggio</font> measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)."<br />";
								}
							$physical_time += $current_period * ($p_duration / $q_duration / $divisions[$score_part]);
							$add = add($p_time_field,$q_time_field,$p_duration,$q_duration);
							$p_time_field = $add['p']; $q_time_field = $add['q'];
							$fraction = $p_duration."/".($q_duration * $divisions[$score_part]);
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							$convert_measure[$score_part] .= "{";
							if($fraction <> "1") {
								if($fraction == 0) { // Grace notes in chord
								//	$report .= "FRACTION_0<br />";
									$fraction = "FRACTION_0";
									}
								$convert_measure[$score_part] .= $fraction.",";
								}
							// No risk because there is exactly 1 note in each field of a chord
							$is_chord = TRUE;
							}
						if($is_chord AND ($the_event['type'] == "seq" OR $the_event['type'] == "mm")) {
							$convert_measure[$score_part] .= "} ";
							$is_chord = FALSE;
							}
						if($the_event['type'] == "mm") continue;
						if(!isset($the_event['note']) OR $the_event['note'] == '') {
							$is_chord = FALSE;
							continue;
							}
								
						if(!$is_chord) {
						//	$report .= "•• measure #".$i_measure." note ".$the_event['note']." field #".($i_field_of_part + 1)." time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream_units = ".$stream_units." event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']."<br />";
					//		$add = add($p_time_field,$q_time_field,$the_event['p_dur'],$the_event['q_dur']);
							$p_duration = $the_event['p_dur']; $q_duration = $the_event['q_dur'];
							$fraction = $the_event['p_dur']."/".($divisions[$score_part] * $the_event['q_dur']);
							if(($p_duration * $q_old_duration) == ($p_old_duration * $q_duration)) {
								$stream .= $the_event['note']." ";
								if($the_event['note'] <> "-" AND $the_event['note'] <> '') $empty_field[$i_field_of_part] = FALSE;
								$stream_units++;
								$add = add($p_stream_duration,$q_stream_duration,$p_duration,$q_duration);
								$p_stream_duration = $add['p']; $q_stream_duration = $add['q'];
								}
							else {
								if($stream_units > 0) {
								//	$report .= "Stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
									if($p_stream_duration == 0) { // Grace notes
								//		$report .= "Grace stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
										$this_grace_ratio = $grace_ratio;
										if($stream_units < 2) $this_grace_ratio = 4 * $grace_ratio;
										$p_grace = $stream_units * $divisions[$score_part];
										$q_grace = $this_grace_ratio;
										if(($p_duration/$q_duration) < (2 * $p_grace/$q_grace)) {
											$p_grace = $p_duration;
											$q_grace = $this_grace_ratio * $q_duration;
											}
										$add2 = add($p_duration,$q_duration,-$p_grace,$q_grace);
										$p_duration = $add2['p']; $q_duration = $add2['q'];
										$add = add($p_time_field,$q_time_field,$p_grace,$q_grace);
										/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_grace/$q_grace / $divisions[$score_part]);
										$fraction = $p_grace."/".($q_grace * $divisions[$score_part]);
										}
									else {
										$fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
										$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
										/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_stream_duration/$q_stream_duration / $divisions[$score_part]);
										}
									$p_time_field = $add['p']; $q_time_field = $add['q'];
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
									if($simplify['q'] > 0) $n = $simplify['p'] / $simplify['q'];
									else $n = 0;
									if($stream_units <> $n) {
										if((trim($stream) == "-" OR trim($stream) == "{-}") AND is_int($fraction) AND $fraction < 4) {
											// Short sequence of '-'
											$stream = '';
											for($i = 0; $i < $fraction; $i++) $stream .= "- ";
											$stream_units = $n = $fraction;
											}
										else {
											$convert_measure[$score_part] .= "{";
											$convert_measure[$score_part] .= $fraction.",";
											}
										}
									$convert_measure[$score_part] .= $stream;
									if($stream_units <> $n) $convert_measure[$score_part] .= "}";
									
									if($new_tempo > 0) { // maybe useless
										$convert_measure[$score_part] .= "||".$new_tempo."||";
										$current_period = 60 / $new_tempo;
										$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." at date ".$fraction_date." beat(s)<br />";
										if($p_time_field == 0 AND $i_field_of_part == 0) {
											$fraction = $new_tempo."/60";
											$simplify = simplify($fraction,$max_term_in_fraction);
											$fraction = $simplify['fraction'];
											$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
											}
										$new_tempo = 0;
										}
									}
								$fraction_date = $p_time_field."/".($q_time_field * $divisions[$score_part]);
								$simplify_date = simplify($fraction_date,$max_term_in_fraction);
								$fraction_date = $simplify_date['fraction'];
								
								// Check whether changes of tempo occur during a rest
								while(TRUE) {
									if($i_field_of_part > 0 AND $new_tempo == 0 AND $p_next_date_new_tempo > 0 AND $the_event['note'] == "-") {
										$add = add($p_time_field,$q_time_field,$the_event['p_dur'],$the_event['q_dur']);
										$add = add($add['p'],$add['q'],-$p_next_date_new_tempo,$q_next_date_new_tempo);
									//	$report .= "time_field = ".$p_time_field."/".$q_time_field." event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']." next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo."<br />";
										if(($add['p'] * $add['q']) >= 0 AND isset($value_new_tempo[$i_new_tempo]) AND $value_new_tempo[$i_new_tempo] > 0) {
											
											$add = add($p_next_date_new_tempo,$q_next_date_new_tempo,-$p_time_field,$q_time_field);
											$p_initial_part = $add['p'];
											$q_initial_part = $add['q'];
										//	$report .= " initial_part = ".$p_initial_part."/".$q_initial_part."<br />";
										//	if(($p_initial_part / $q_initial_part) > 0.05) {
											if(($p_initial_part / $q_initial_part / $divisions[$score_part]) >= 0) {
												// Create initial part of rest still at the old tempo
												$fraction = $p_initial_part."/".($q_initial_part * $divisions[$score_part]);
												$simplify = simplify($fraction,$max_term_in_fraction);
												$fraction = $simplify['fraction'];
												if($fraction <> "0")
													$convert_measure[$score_part] .= " ".$fraction." ";
												// Reduce duration of rest by the initial part
											//	$report .= "event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']." initial_part = ".$p_initial_part."/".$q_initial_part."<br />";
												$add = add($the_event['p_dur'],$the_event['q_dur'],-$p_initial_part,$q_initial_part);
												$p_duration = $the_event['p_dur'] = $add['p'];
												$q_duration = $the_event['q_dur'] = $add['q'];
												// Update time of field
												$add = add($p_time_field,$q_time_field,$p_initial_part,$q_initial_part);
												$p_time_field = $add['p'];
												$q_time_field = $add['q'];
												/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_initial_part/$q_initial_part / $divisions[$score_part]);
												// Update new_tempo
												$new_tempo = $value_new_tempo[$i_new_tempo];
												$i_new_tempo++;
												if(isset($p_date_new_tempo[$i_new_tempo])) {
													$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
													$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
													$last_i_new_tempo[$i_field_of_part] = $i_new_tempo;
													$p_last_next_date_new_tempo[$i_field_of_part] = $p_next_date_new_tempo;
													$q_last_next_date_new_tempo[$i_field_of_part] = $q_next_date_new_tempo;
												//	$report .= "next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." last_next_date_new_tempo = ".$p_last_next_date_new_tempo[$i_field_of_part]."/".$q_last_next_date_new_tempo[$i_field_of_part]."<br />";
													}
												else {
													$p_next_date_new_tempo = $q_next_date_new_tempo = $last_i_new_tempo[$i_field_of_part] = -1;
													}
											//	$report .= "new_tempo = ".$new_tempo." next tempo[".$i_new_tempo."] = ".$value_new_tempo[$i_new_tempo]."<br />";
												if($new_tempo <> round(60 / $current_period)) { // Added by BB 2021-03-16
													$convert_measure[$score_part] .= " ||".$new_tempo."|| ";
													$current_period = 60 / $new_tempo; // Added by BB 2021-03-16
													$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to ".$new_tempo." during rest starting date ".$fraction_date." beat(s)<br />";
													}
												$new_tempo = 0;
												}
											else break;
											}
										else break;
										}
									else break;
									}
								$stream = $the_event['note']." ";
							//	if($p_duration > 0) {
									$stream_units = 1;
								//	}
								if($the_event['note'] <> "-" AND $the_event['note'] <> '') $empty_field[$i_field_of_part] = FALSE;
								$p_old_duration = $p_stream_duration = $p_duration;
								$q_old_duration = $q_stream_duration = $q_duration;
								}
							}
						else {
							$convert_measure[$score_part] .= $the_event['note'];
							if($the_event['note'] <> "-" AND $the_event['note'] <> '') $empty_field[$i_field_of_part] = FALSE;
							$convert_measure[$score_part] .= ",";
							}
						}
					unset($the_event);
					
					$convert_measure[$score_part] = process_arpeggiate($convert_measure[$score_part],$divisions[$score_part]);
					if($stream_units > 0) {
						if($test_musicxml)
							echo "Before last field of measure ".$i_measure.", time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream = “".$stream."”<br />";
						$fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
						$simplify = simplify($fraction,$max_term_in_fraction);
						$fraction = $simplify['fraction'];
						if($simplify['q'] > 0) $n = $simplify['p'] / $simplify['q'];
						else $n = 0;
						if($stream_units <> $n) {
					//		$report .= "Grace stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
							if((trim($stream) == "-" OR trim($stream) == "{-}") AND is_int($fraction) AND $fraction < 4) {
								// Short sequence of '-'
								$stream = '';
								for($i = 0; $i < $fraction; $i++) $stream .= "- ";
								$stream_units = $n = $fraction;
								}
							else {
								$convert_measure[$score_part] .= "{";
								$convert_measure[$score_part] .= $fraction.",";
								}
							}
						if($new_tempo > 0) {
							$stream = "||".$new_tempo."||".$stream;
							$new_tempo = 0;
							}
						$convert_measure[$score_part] .= $stream;
						if($stream_units <> $n) $convert_measure[$score_part] .= "}";
						$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
						$p_time_field = $add['p']; $q_time_field = $add['q'];
						/* if($i_field_of_part == 0) */ $physical_time += $current_period * ($p_stream_duration/$q_stream_duration / $divisions[$score_part]);
						$stream = ''; $stream_units = 0; $p_stream_duration = 0; $q_stream_duration = 1;
						}
								
					$convert_measure[$score_part] .= "§".$i_field_of_part."§,";
					$p_field_duration[$i_field_of_part] = $p_time_field;
					$q_field_duration[$i_field_of_part] = $q_time_field * $divisions[$score_part];
					$i_field_of_part++;
					$i_field_of_measure++;
					$empty_field[$i_field_of_part] = TRUE;
					$i_new_tempo = 0;
					$last_i_new_tempo[$i_field_of_part] = -1;
					if(count($the_measure) > 1) $convert_measure[$score_part] .= "}";
					$add = add($p_time_measure,$q_time_measure,(-$p_time_field),$q_time_field);
					if(($add['p'] * $add['q']) < 0) {
						$p_time_measure = $p_time_field;
						$q_time_measure = $q_time_field;
						}
					if($number_tempo_measure[$section][$i_measure] > 0) {
						$metronome_this_measure = round($sum_tempo_measure[$section][$i_measure] / $number_tempo_measure[$section][$i_measure]);
						$fraction = $metronome_this_measure."/60";
						$simplify = simplify($fraction,$max_term_in_fraction);
						$fraction = $simplify['fraction'];
						// if($fraction <> "1") $data .= " _tempo(".$fraction.")";
						if($old_tempo == '') $old_tempo = $fraction;
					//	$current_tempo = $default_tempo[$section] = $fraction;
						$current_tempo = $fraction;
						$report .= "number_tempo_measure = ".$number_tempo_measure[$section][$i_measure]." tempo_this_measure = ".$metronome_this_measure." current_tempo = ".$current_tempo."<br />";
						}
					else if(isset($default_tempo[$section])) {
						// We must repeat tempo on each measure to play it separately
							$simplify = simplify($default_tempo[$section],$max_term_in_fraction);
							$metronome_this_measure = round($simplify['p'] * 60 / $simplify['q']);
							if($default_tempo[$section] <> "1") {
								$current_tempo = $default_tempo[$section];
								}
							}
						else {
							if($old_tempo == '') $old_tempo = 1;
							$simplify = simplify($old_tempo,$max_term_in_fraction);
							$metronome_this_measure = round($simplify['p'] * 60 / $simplify['q']);
							$current_tempo = $old_tempo;
							}
					$data .= "_tempo(TEMPO_THIS_MEASURE)";
					
					if($number_volume_part[$section][$score_part] > 0) {
						$volume = round($sum_volume_part[$section][$score_part] / $number_volume_part[$section][$score_part]);
						if($volume > 127) $volume = 127;
						if($dynamic_control[$score_part] == "volume") $data .= " _volume(".$volume.")";
						else $data .= " _vel(".$volume.")"; 
						if(!isset($default_volume[$section][$score_part]))
							$default_volume[$section][$score_part] = $volume;
						if(!isset($old_volume[$score_part])) $old_volume[$score_part] = $volume;
						}
					else if(isset($default_volume[$section][$score_part])) {
						// We must repeat volume on each measure to play it separately
							if($dynamic_control[$score_part] == "volume") $data .= " _volume(".$default_volume[$section][$score_part].")";
							else $data .= " _vel(".$default_volume[$section][$score_part].")";
							}
						else {
							if(!isset($old_volume[$score_part])) $old_volume[$score_part] = 64;
							if($dynamic_control[$score_part] == "volume") $data .= " _volume(".$old_volume[$score_part].")";
							else $data .= " _vel(".$old_volume[$score_part].")";
							}
							
					if($test_musicxml)
						echo "End measure ".$i_measure." time_measure = ".$p_time_field."/".$q_time_field." tempo_this_measure = ".$sum_tempo_measure[$section][$i_measure]."/".$number_tempo_measure[$section][$i_measure]." default_tempo[section] = ".$default_tempo[$section]." implicit = ".(1 * $implicit[$section][$i_measure])." old_tempo = ".$old_tempo."<br /><br />";
												
					if(!$ignore_channels AND isset($midi_channel[$score_part])) $data .= " _chan(".$midi_channel[$score_part].")";
					$convert_measure[$score_part] = fix_alterations($convert_measure[$score_part]);
					$fraction = $p_time_measure."/".($q_time_measure * $divisions[$score_part]);
					$simplify = simplify($fraction,$max_term_in_fraction);
					$fraction = $simplify['fraction'];
					$data .= "{".$fraction;
					$data .= ",".$convert_measure[$score_part];
					$data .= "}";
					$data .= ","; // This may be erased later if not followed with new field
					
					// Now we will normalize durations of fields
					$p_duration_this_measure = 0;
					$q_duration_this_measure = 1;
					for($j = 0; $j < $i_field_of_part; $j++) {
						// Calculate duration of the structure = max duration of one of its fields
						// (It includes fermata duration)
						if($empty_field[$j]) continue; // Only silence doesn't count
						$add = add($p_duration_this_measure,$q_duration_this_measure,-$p_field_duration[$j],$q_field_duration[$j]);
						if(($add['p'] * $add['q']) < 0) {
							$p_duration_this_measure = $p_field_duration[$j];
							$q_duration_this_measure = $q_field_duration[$j];
							}
						}
					
					// Add missing rests to fill up gaps
					for($j = 0; $j < $i_field_of_part; $j++) {
						$add = add($p_duration_this_measure,$q_duration_this_measure,-$p_field_duration[$j],$q_field_duration[$j]);
						$p_gap = $add['p'];
						$q_gap = $add['q'];
						$p_duration = $p_gap * $divisions[$score_part];
						$q_duration = $q_gap;
						$stream = '';
						if(($p_gap * $q_gap) > 0) {
							// Check whether changes of tempo occur during this additional rest
							$p_time_field = $p_field_duration[$j] * $divisions[$score_part];
							$q_time_field = $q_field_duration[$j];
							$i_new_tempo = $last_i_new_tempo[$j];
							while($i_new_tempo > 0) {
								$p_next_date_new_tempo = $p_last_next_date_new_tempo[$j];
								$q_next_date_new_tempo = $q_last_next_date_new_tempo[$j];
								$new_tempo = $value_new_tempo[$i_new_tempo];
								$fraction_date = $p_time_field."/".($q_time_field * $divisions[$score_part]);
								$simplify_date = simplify($fraction_date,$max_term_in_fraction);
								$fraction_date = $simplify_date['fraction'];
							//	$report .= "i_new_tempo = ".$i_new_tempo." field ".($j + 1)." new_tempo = ".$new_tempo." next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." date = ".$fraction_date." time_field = ".($p_time_field/$q_time_field)." duration = ".$p_duration."/".$q_duration."<br />";
								if($j > 0 AND $p_next_date_new_tempo > 0) {
									$add = add($p_time_field,$q_time_field,$p_duration,$q_duration);
									$add = add($add['p'],$add['q'],-$p_next_date_new_tempo,$q_next_date_new_tempo);
									if(($add['p'] * $add['q']) >= 0) {
										// $report .= "remaining ".$add['p']."/".$add['q']."<br />";
										$add = add($p_next_date_new_tempo,$q_next_date_new_tempo,-$p_time_field,$q_time_field);
										$p_initial_part = $add['p'];
										$q_initial_part = $add['q'];
										// $report .= "initial_part ".$p_initial_part."/".$q_initial_part."<br />";
										if($p_initial_part >= 0 OR (abs($p_initial_part / $q_initial_part / $divisions[$score_part]) < 0.05)) {
											// Create initial part of additional rest still at the old tempo
											$fraction = $p_initial_part."/".($q_initial_part * $divisions[$score_part]);
											$simplify = simplify($fraction,$max_term_in_fraction);
											$fraction = $simplify['fraction'];
											$stream .= " ".$fraction." ";
											// Reduce duration of rest by the initial part
											$add = add($p_gap,$q_gap,-$p_initial_part,$q_initial_part);
											$p_duration = $p_gap = $add['p'];
											$q_duration = $q_gap = $add['q'];
											// Update time of field
											$add = add($p_time_field,$q_time_field,$p_initial_part,$q_initial_part);
											$p_time_field = $add['p'];
											$q_time_field = $add['q'];
											/* if($j == 0) */ $physical_time += $current_period * ($p_initial_part/$q_initial_part / $divisions[$score_part]);
											// Update new_tempo
											$new_tempo = $value_new_tempo[$i_new_tempo];
											$i_new_tempo++;
											if(isset($p_date_new_tempo[$i_new_tempo])) {
												$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
												$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
												$last_i_new_tempo[$j] = $i_new_tempo;
												}
											else $i_new_tempo = -1;
											$stream .= " ||".$new_tempo."|| ";
											$report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($j + 1)." metronome set to ".$new_tempo." during additional rest starting date ".$fraction_date." beat(s)<br />";
											$new_tempo = 0;
											}
										else break;
										}
									else break;
									}
								else break;
								}
							$fraction = $p_gap."/".$q_gap;
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							$data = str_replace("§".$j."§","§".$j."§ ".$stream.$fraction,$data);
							$p_field_duration[$j] = $p_duration_this_measure;
							$q_field_duration[$j] = $q_duration_this_measure;
							if($list_corrections)
								$report .= "<font color=\"MediumTurquoise\">+rest </font> Measure #".$i_measure." part ".$score_part." field #".($j + 1)." added rest = ".$fraction." beat(s)<br />";
							}
						}
					
					$report .= "+ measure #".$i_measure." field #".$i_field_of_part." : physical time = ".round($physical_time,2)."s";
					if($empty_field[$i_field_of_part - 1]) $report .= " (only silence)";
					$report .= "<br />";
					if(!$empty_field[$i_field_of_part - 1] AND $physical_time > $max_physical_time)
						$max_physical_time = $physical_time;
					
					if($max_physical_time > 0.) $final_metronome = round(60 * $p_time_measure / ($q_time_measure * $divisions[$score_part]) / $max_physical_time);
					else $final_metronome = $metronome_this_measure;
				//	$report .= "Measure ".$i_measure." number_tempo_measure = ".$number_tempo_measure[$section][$i_measure]." sum_tempo_measure = ".$sum_tempo_measure[$section][$i_measure]." tempo_this_measure = ".$metronome_this_measure."<br />";
					if($metronome_this_measure == 0 OR abs(($metronome_this_measure - $final_metronome) / $metronome_this_measure) > 0.4) $warning = TRUE;
					else $warning = FALSE;
					if($warning) $report .= "<font color=\"red\">";
					$report .= "➡ Measure #".$i_measure." part [".$score_part."] physical time = ".round($max_physical_time,2)."s, average metronome = ".$metronome_this_measure.", final metronome = <font color=\"blue\">".$final_metronome."</font><br />";
					if($warning) $report .= "</font>";
					if($final_metronome > 0) $metronome_this_measure = $final_metronome;
					$physical_time = $max_physical_time = 0.;
					$fraction = $metronome_this_measure."/60";
					$simplify = simplify($fraction,$max_term_in_fraction);
					$fraction = $simplify['fraction'];
					$current_tempo = $fraction;
					if($fraction <> "1") $data = str_replace("TEMPO_THIS_MEASURE",$fraction,$data);
					else $data = str_replace("_tempo(TEMPO_THIS_MEASURE)",'',$data);
					
					// Calculate relative tempo() values inside this measure
					$start_search = 0;
					$p_metronome_this_measure = $metronome_this_measure;
					$q_metronome_this_measure = 1;
				/*	$p_last_metronome = $p_metronome_this_measure;
					$q_last_metronome = $q_metronome_this_measure; */
					while(is_integer($pos1=strpos($data,"||",$start_search))) {
						if(!is_integer($pos2=strpos($data,"||",$pos1 + 3))) break;
						// Metronome value found on the non-printable score
						$this_metronome = substr($data,$pos1 + 2,$pos2 - $pos1 - 2);
						$p_this_tempo = $this_metronome * $q_metronome_this_measure;
						$q_this_tempo = $p_metronome_this_measure;
						$fraction = $p_this_tempo."/".$q_this_tempo;
						$simplify = simplify($fraction,$max_term_in_fraction);
						$fraction = $simplify['fraction'];
						$d1 = substr($data,0,$pos1);
						$d2 = substr($data,$pos1,strlen($data) - $pos1);
						if($fraction <> "1") $this_tempo = "_tempo(".$fraction.") ";
						else $this_tempo = '';
						$data = $d1.$this_tempo.$d2;
						$start_search = $pos2 + strlen($this_tempo) + 2;
						}
					$data = preg_replace("/\|\|[0-9]+\|\|/u",'',$data); // Tempo markers
					}
				$data = preg_replace("/§[0-9]+§/u",'',$data); // Field markers
				$data .= "} ";
				unset($the_part);
				$new_tempo = 0;
				}
			unset($the_measure);
			}
		}
	unset($the_section);
	$convert_score['data'] = $data;
	$convert_score['metronome_min'] = $metronome_min;
	$convert_score['metronome_max'] = $metronome_max;
	if($number_metronome > 0)
		$metronome_average = round($sum_metronome / $number_metronome);
	else $metronome_average = 0;
	$convert_score['metronome_average'] = $metronome_average;
	if($list_corrections) $convert_score['report'] = $report;
	else $convert_score['report'] = '';
	return $convert_score;
	}
	

function process_arpeggiate($data,$score_divisions) {
	global $max_term_in_fraction;
	$start_search = 0;
/*	$max_divisions = round($score_divisions / 8);
	if($max_divisions == 0) $max_divisions = 1; */
	$min_divisions = round($score_divisions / 20);
	if($min_divisions == 0) $min_divisions = 1;
	while(is_integer($pos1=strpos($data,"arpeggiate",$start_search))) {
		if(!is_integer($pos2=strpos($data,"}",$pos1 + 10))) break;
		$old_expression = substr($data,$pos1 + 10,$pos2 - $pos1 - 9);
		$old_length = strlen($old_expression);
		$old_expression = str_replace(' ','',$old_expression);
		$no_digit = FALSE;
		$table = explode(',',$old_expression);
		if(!ctype_digit($table[0][1])) {
			$p_duration = $q_duration = 1;
			$no_digit = TRUE;
			}
		else {
			$duration = str_replace('{','',$table[0]);
			$table2 = explode('/',$duration);
			$p_duration = $table2[0];
			if(isset($table2[1])) $q_duration = $table2[1];
			else $q_duration = 1;
			}
		$divisions = $score_divisions * $p_duration / $q_duration;
	/*	$divisions1 = round($divisions / 2);
		if($divisions1 == 0) $divisions1 = 1; */
		
		$divisions1 = $min_divisions * (count($table) - 1);
		$max_divisions = round($divisions / 2);
		if($max_divisions == 0) $max_divisions = 1;
		
		if($divisions1 > $max_divisions) $divisions1 = $max_divisions;
		$divisions2 = $divisions - $divisions1;
	//	echo $p_duration."/".$q_duration." divisions = ".$divisions." divisions1 = ".$divisions1." divisions2 = ".$divisions2." ".$old_expression."<br />";
		$new_expression1 = $new_expression2 = "{";
		$fraction = $divisions1."/".$score_divisions;
		$simplify = simplify($fraction,$max_term_in_fraction);
		$fraction = $simplify['fraction'];
		$new_expression1 .= $fraction.',';
		$fraction = $divisions2."/".$score_divisions;
		$simplify = simplify($fraction,$max_term_in_fraction);
		$fraction = $simplify['fraction'];
		$new_expression2 .= $fraction.',';
		for($i = 0; $i < (count($table) - 1); $i++) {
			if(!$no_digit AND $i == 0) continue;
			$note = str_replace('{','',$table[$i]);
			$tied = "no";
			if(is_integer($pos=strpos($note,'&'))) {
				if($pos == 0) $tied = "before";
				else $tied = "after";
				if(substr_count($note,'&') > 1) $tied = "both";
				}
			if($tied == "no") {
				$note1 =  $note."&";
				$note2 =  "&".$note;
				}
			else if($tied == "after") { 
				$note1 =  $note;
				$note2 =  "&".$note;
				}
			else if($tied == "before") { 
				$note1 =  $note."&";
				$note2 =  $note;
				}
			else if($tied == "both") { 
				$note1 =  $note;
				$note2 =  $note;
				}
			$new_expression1 .= $note1." ";
			$new_expression2 .= $note2.",";
			}
		$new_expression1 .= "}";
		$new_expression2 .= "}";
		$new_expression = str_replace(" }","}",$new_expression1.$new_expression2);
	//	echo $new_expression."<br /><br />";
		$d1 = substr($data,0,$pos1);
		$d2 = substr($data,$pos2 + 1,strlen($data) - $pos2 - 1);
		$data = $d1.$new_expression.$d2;
		$data = str_replace("arpeggiate",'',$data);
	//	$start_search = $pos2 + strlen($new_expression) - $old_length;
		$start_search = $pos1 + strlen($new_expression);
		}
//	$data = str_replace("arpeggiate",'@@@',$data);
	return $data;
	}
	
function fix_alterations($text) {
	$search = array("C##","D##","E##","F##","G##","A##","B##");
	$replace = array("D","E","F#","G","A","B","C#");
	$text = str_ireplace($search,$replace,$text);
	$search = array("Cbb","Dbb","Ebb","Fbb","Gbb","Abb","Bbb");
	$replace = array("Bb","C","D","Eb","F","G","A");
	$text = str_ireplace($search,$replace,$text);
	return $text;
	}
?>
