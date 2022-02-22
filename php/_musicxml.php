<?php
$notes_diesis = array("C","C#","D","D#","E","F","F#","G","G#","A","A#","B");
$notes_bemol = array("C","Db","D","Eb","E","F","Gb","G","Ab","A","Bb","B");
$standard_diatonic_scale = array(0,2,4,5,7,9,11); // These are pitch classes
$super_trace = TRUE;
$super_trace = FALSE;
$trace_breath = TRUE;
$trace_breath = FALSE;

function convert_musicxml($the_score,$repeat_section,$divisions,$fifths,$mode,$midi_channel,$dynamic_control,$select_part,$ignore_dynamics,$tempo_option,$ignore_channels,$include_breaths,$include_slurs,$include_measures,$ignore_fermata,$ignore_mordents,$chromatic_mordents,$ignore_turns,$chromatic_turns,$ignore_trills,$chromatic_trills,$ignore_arpeggios,$reload_musicxml,$test_musicxml,$change_metronome_average,$change_metronome_min,$change_metronome_max,$current_metronome_average,$current_metronome_min,$current_metronome_max,$list_corrections,$trace_tempo,$trace_ornamentations,$breath_length,$breath_tag,$trace_measures,$measures,$accept_signs,$include_parts,$number_parts,$apply_rndtime,$rndtime,$apply_rndvel,$rndvel,$extend_last_measure,$number_measures) {
	global $super_trace,$trace_breath;
	global $max_term_in_fraction;
	global $notes_diesis,$notes_bemol,$standard_diatonic_scale;
	$grace_ratio = 2;
	// MakeMusic Finale dynamics https://en.wikipedia.org/wiki/Dynamics_(music)
	$dynamic_sign_to_volume = array("pppp" => 10, "ppp" => 23, "pp" => 36, "p" => 49, "mp" => 62, "mf" => 75, "sfp" => 80, "f" => 88, "sf" => 90, "ff" => 101, "sff" => 110, "fff" => 114, "sfff" => 120, "ffff" => 127);
	$dynamic_sign_to_tempo = array("Largo" => 50, "Lento" => 60, "Adagio" => 70, "Andante" => 88, "Moderato" => 100, "Allegretto" => 114, "Allegro" => 136, "Vivace" => 140, "Presto" => 170, "Prestissimo" => 190);
	$data = $report = $old_measure_label = '';
	$measure_label = array();
	$sum_metronome = $number_metronome = $metronome_max = $metronome_min = $number_metronome_markers = 0;
	$said_tempo = FALSE;
	$list_this = $list_corrections;
	if($super_trace OR $trace_breath) $list_this = TRUE;
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
	$old_tempo = ''; $current_tempo = 1;
	$found_mordent = $found_turn = $found_trill = $found_turn = $chromatic = FALSE;
	$fermata_show = FALSE; // Only for test
	if($trace_breath AND $include_breaths AND $reload_musicxml)
		echo "Breath length = ".$breath_length['p']."/".$breath_length['q']."<br />";
	$breath_rest = $breath_trace_tag = '';
	if($trace_measures AND $reload_musicxml) echo "Tracing measures #".$measures['min']." to #".$measures['max']."<br />";
	if($include_breaths) {
		if($breath_tag <> '') $breath_trace_tag = "[".$breath_tag."] ";
		$fraction = $breath_length['p']."/".$breath_length['q'];
		$simplify = simplify($fraction,$max_term_in_fraction);
		$p = $breath_length['p'] = $simplify['p'];
		$q = $breath_length['q'] = $simplify['q'];
		if($q == 1) $breath_rest = $p;
	//	if($breath_rest == 1) $breath_rest = "- ";
		else $breath_rest = $simplify['fraction']." ";
		if($trace_breath) echo "Breath rest = ".$breath_rest."<br />";
		}
	ksort($the_score);
	if($reload_musicxml AND $number_parts == 1 AND $apply_rndtime[0]) $data .= "_rndtime(".$rndtime[0].") ";
	if($reload_musicxml AND $number_parts == 1 AND $apply_rndvel[0]) $data .= "_rndvel(".$rndvel[0].") ";
	foreach($the_score as $section => $the_section) {
		if($list_this OR $trace_tempo) $report .= "<br /><b>== Section ".$section." ==</b><br />";
		$sum_tempo_measure[$section] = $number_tempo_measure[$section] = array();
		$sum_volume_part[$section] = $number_volume_part[$section] = array();
		$default_volume[$section] = array();
		$implicit[$section] = array();
		if(!isset($default_tempo[$section - 1])) $default_tempo[$section] = $current_tempo;
		else $default_tempo[$section] = $default_tempo[$section - 1];
		if($list_this OR $trace_tempo) $report .= "Default tempo set to ".$default_tempo[$section]." in section #".$section."<br />";
		$beat_type = $beat_unit = array();
		for($i_repeat = 1; $i_repeat <= $repeat_section[$section]; $i_repeat++) {
			$tie_type_start = $tie_type_stop = FALSE;
			if($test_musicxml) echo "• Repetition ".$i_repeat."/".$repeat_section[$section]." section ".$section."<br />";
			$p_fermata_date = $q_fermata_date = $p_fermata_duration = $q_fermata_duration = $p_fermata_total_duration = $q_fermata_total_duration = $p_date = $q_date = array();
			$first_measure = TRUE;
			$final_metronome = 0;
			$score_metronome = 0;
		//	ksort($the_section); Never do this because $i_measure may not be an integer
		//  Beware that there are empty sessions
			foreach($the_section as $i_measure => $the_measure) {
				if($i_measure < 0) continue;
				if($list_this) $report .= "<br />";
				$physical_time = $max_physical_time = 0.;
				$sum_durations = 0;
				$sum_tempo_measure[$section][$i_measure] = 0;
				$number_tempo_measure[$section][$i_measure] = 0;
				$measure_label[$i_measure] = $i_measure;
				$implicit[$section][$i_measure] = FALSE;
				if(!is_integer($i_measure)) {
					$implicit[$section][$i_measure] = TRUE;
					$measure_label[$i_measure] = $old_measure_label."-".$measure_label[$i_measure];
					if($list_this) $report .= "• Implicit measure #".$i_measure." is labelled <font color=\"MediumTurquoise\">“".$measure_label[$i_measure]."”</font><br />";
					}
				else $old_measure_label = $measure_label[$i_measure];
				if($test_musicxml)
					echo "<font color = red>• Measure ".$measure_label[$i_measure]."</font><br />";
				$curr_event = $convert_measure = $p_fermata_total_duration[$i_measure] = $q_fermata_total_duration[$i_measure] = $p_fermata_date[$i_measure] = $q_fermata_date[$i_measure] = $p_fermata_duration[$i_measure] = $q_fermata_duration[$i_measure] = array();
				if($include_measures AND !$first_measure AND $reload_musicxml) $data .= " [—".$i_measure."—] ";
				if(($i_measure == $number_measures) AND ($extend_last_measure > 0)) $data .= " _legato(".$extend_last_measure.") ";
				$first_measure = FALSE;
				$data .= "{";
				$i_part = 0;
				$i_field_of_measure = 0; // Index of field irrespective of parts
				$i_new_tempo = -1;
				$p_next_date_new_tempo = $q_next_date_new_tempo = -1;
				$value_new_tempo = $p_date_new_tempo = $q_date_new_tempo = $p_field_duration = $q_field_duration = $empty_field = array(); // Check location of these on multipart scores BB 2022-02-12
				$p_date[$i_measure] = $q_date[$i_measure] = array();
				ksort($the_measure);
				foreach($the_measure as $score_part => $the_part) {
					if(!$reload_musicxml OR !$select_part[$score_part]) {
						if($test_musicxml AND $reload_musicxml) echo "Score part ".$score_part." not selected in section ".$section."<br />";
						continue;
						}
					$i_part++;
					$breath_location = array();
					$i_breath = $n_breath = 0;
					if(isset($fifths[$score_part])) $current_fifths = $fifths[$score_part];
					else $current_fifths = 0;
					$diatonic_scale = diatonic_scale($current_fifths);
					$altered_diatonic_scale = $diatonic_scale; // We'll store alterations in this measure
					$i_field_of_part = $i_field_of_part2 = 0;
					$i_fermata = 0;
					$p_date[$i_measure][$score_part] = 0; $q_date[$i_measure][$score_part] = 1;
					$p_fermata_total_duration[$i_measure][$score_part] = 0;
					$q_fermata_total_duration[$i_measure][$score_part] = 1;
					
					if($trace_measures AND is_numeric($i_measure)) {
						if($measures['min'] <= $i_measure AND $measures['max'] >= $i_measure) {
							$list_this = TRUE;
							}
						else $list_this = FALSE;
						}
					if($final_metronome > 0) $simplify = simplify($final_metronome."/60",$max_term_in_fraction);
					else $simplify = simplify($default_tempo[$section],$max_term_in_fraction);
					$n = $simplify['p'] / $simplify['q'];
					$current_period = 1 / $n;
					if($list_this OR $trace_tempo) $report .= "• Measure #".$i_measure." part [".$score_part."] starts with mm = <font color=\"blue\">".($simplify['p'] * 60 / $simplify['q'])."</font>, current period = ".round($current_period,2)."s, current tempo = ".$current_tempo.", default tempo[".$section."] = ".$default_tempo[$section]." (metronome = ".(60 * $n).")<br />";
					$p_fermata_date[$i_measure][$score_part] = $q_fermata_date[$i_measure][$score_part] = $p_fermata_duration[$i_measure][$score_part] = $q_fermata_duration[$i_measure][$score_part] = array();
					$sum_volume_part[$section][$score_part] = $number_volume_part[$section][$score_part] = 0;
					if(!isset($old_volume[$score_part])) $old_volume[$score_part] = 64;
					if($test_musicxml) echo "• Measure ".$i_measure." part ".$score_part."<br />";
					ksort($the_part);
					$this_note = $some_words = '';
					$note_on = $is_chord = $rest = $pitch = $unpitched = $time_modification = $forward = $backup = $chord_in_process = $dynamics = $upper_mordent = $lower_mordent = $trill = $turn = FALSE;
					$long_ornamentation = $slur_type = '';
					$alter = $level = 0;
					$more_duration = 0;
					$this_octave = -1;
					$curr_event[$score_part] = $convert_measure = array();
					$j = 0;
		//			$time_this_field = 0;
					$curr_event[$score_part][$j]['type'] = "seq";
					$curr_event[$score_part][$j]['fermata'] = FALSE;
					$breath_in_stream = FALSE;
					
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
								$sum_durations += ($p_this_dur / $q_this_dur);
						//		if($list_this) $report .= ">> chord p/q = ".($p_this_dur / $q_this_dur).", sum_durations = ".$sum_durations."<br />";
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
							if($fermata AND $i_field_of_part2 == 0 AND $curr_event[$score_part][$j]['p_dur'] > 0) {
								if($fermata_show) $this_note .= "fermata";
								$add = add($p_fermata_total_duration[$i_measure][$score_part],$q_fermata_total_duration[$i_measure][$score_part],$curr_event[$score_part][$j]['p_dur'],$curr_event[$score_part][$j]['q_dur']);
								$p_fermata_total_duration[$i_measure][$score_part] = $add['p'];
								$q_fermata_total_duration[$i_measure][$score_part] = $add['q'];
								$curr_event[$score_part][$j]['p_dur'] += $curr_event[$score_part][$j]['p_dur'];
								$curr_event[$score_part][$j]['fermata'] = TRUE;
								$sum_durations += ($curr_event[$score_part][$j]['p_dur'] / $curr_event[$score_part][$j]['q_dur']);
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
								if($slur_type <> '') $curr_event[$score_part][$j]['slur'] = $slur_type;

								if($upper_mordent) $curr_event[$score_part][$j]['ornament'] = "upper_mordent";
								if($lower_mordent) $curr_event[$score_part][$j]['ornament'] = "lower_mordent";
								if($upper_mordent OR $lower_mordent) $curr_event[$score_part][$j]['long'] = $long_ornamentation;
								$curr_event[$score_part][$j]['chromatic'] = $chromatic;
								if($turn) {
									$curr_event[$score_part][$j]['turn'] = TRUE;
									$curr_event[$score_part][$j]['turn-beats'] = $turn_beats;
									$curr_event[$score_part][$j]['long'] =  "yes";
									}
								if($trill) {
									$curr_event[$score_part][$j]['ornament'] = "trill";
									$curr_event[$score_part][$j]['trill-beats'] = $trill_beats;
									}
								if($test_musicxml)
									echo $curr_event[$score_part][$j]['note']." ".$curr_event[$score_part][$j]['type']." j = ".$j."<br />";
								}
							if(!$is_chord) {
								$add = add($p_date[$i_measure][$score_part],$q_date[$i_measure][$score_part],$curr_event[$score_part][$j]['p_dur'],$curr_event[$score_part][$j]['q_dur']);
								$p_date[$i_measure][$score_part] = $add['p'];
								$q_date[$i_measure][$score_part] = $add['q'];
								$sum_durations += ($curr_event[$score_part][$j]['p_dur'] / $curr_event[$score_part][$j]['q_dur']);
						//		$time_this_field += $curr_event[$score_part][$j]['p_dur'] / $curr_event[$score_part][$j]['q_dur'];
								}
							$note_on = $rest = $fermata = $is_chord = $upper_mordent = $lower_mordent = $trill = $turn = $chromatic = FALSE;
							$long_ornamentation = $slur_type = '';
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
							
						if(($tempo_option <> "score" AND $tempo_option <> "ignore") AND is_integer($pos=strpos($line,"<sound tempo"))) {
							$tempo = $score_metronome = round(trim(preg_replace("/.+tempo=\"([^\"]+)\"\/>/u","$1",$line)));
							// This tempo is the number of quarternotes per minute
							if(!isset($beat_unit[$score_part])) $beat_unit[$score_part] = 4;
							if(!isset($beat_type[$score_part])) $beat_type[$score_part] = 4;
							$beat_divide = beat_divide($beat_type[$score_part]);
						//	$tempo = round(($tempo * $beat_divide['p']) / $beat_divide['q']); 
							if($list_this OR $trace_tempo) $report .= "••• Metronome will be set to ".$tempo." <i>descriptive</i> (".$score_metronome." in HTML score, time signature ".$beat_unit[$score_part]."/".($beat_divide['q'] * 4 / $beat_divide['p']).")<br />";
							if($change_metronome) {
								if(!$said_tempo) {
									if($quad AND $list_this) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
									else if($list_this) $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
									$said_tempo = TRUE;
									}
								if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
								else {
									if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
									else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
									}
								if($list_this) $report .= "Metronome changed from ".$tempo." (non-printed) to ".$tempo_changed."<br />";
								$tempo = $tempo_changed;
								}
							$sum_tempo_measure[$section][$i_measure] += $tempo;
							$number_tempo_measure[$section][$i_measure]++;
							$sum_metronome += $tempo;
							$number_metronome++;
							$number_metronome_markers++;
							if($tempo > $metronome_max) $metronome_max = $tempo;
							if($tempo < $metronome_min OR $metronome_min == 0) $metronome_min = $tempo;
							if($tempo_option <> "allbutmeasures") {
								$curr_event[$score_part][$j]['type'] = "mm";
								$curr_event[$score_part][$j]['value'] = $tempo;
								$curr_event[$score_part][$j]['p_dur'] = 0;
								$curr_event[$score_part][$j]['q_dur'] = 1;
								$is_chord = FALSE;
								$j++;
								}
							continue;
							}
						if($include_slurs AND is_integer($pos=strpos($line,"<slur"))) {
							$slur_type = trim(preg_replace("/.+type\s*=\s*\"([a-z]+)\".*/u","$1",$line));
					//		echo "slur = ".$slur_type."<br />";
							continue;
							}
						if(is_integer($pos=strpos($line,"<beat-unit>"))) {
							if(isset($beat_unit[$score_part])) $old_beat_unit = $beat_unit[$score_part];
							else $old_beat_unit = 0;
							$beat_unit[$score_part] = trim(preg_replace("/<beat-unit>(.+)<\/beat-unit>/u","$1",$line));
							if($trace_tempo OR $list_this) $report .= "••• Beat unit = ".$beat_unit[$score_part]." (tag “beat-unit”)<br />";
							continue;
							}
						if(is_integer($pos=strpos($line,"<beats>"))) {
							if(isset($beat_unit[$score_part])) $old_beat_unit = $beat_unit[$score_part];
							else $old_beat_unit = 0;
							$beat_unit[$score_part] = trim(preg_replace("/<beats>(.+)<\/beats>/u","$1",$line));
							if($trace_tempo OR $list_this) $report .= "••• Beat unit = ".$beat_unit[$score_part]." (tag “beats”)<br />";
							continue;
							}
						if(is_integer($pos=strpos($line,"<beat-type>"))) {
							if(isset($beat_type[$score_part])) $old_beat_type = $beat_type[$score_part];
							else $old_beat_type = 0;
							$new_beat_type = trim(preg_replace("/<beat-type>(.+)<\/beat-type>/u","$1",$line));
							if($trace_tempo OR $list_this) $report .= "••• Beat type = ".$new_beat_type." score_metronome = ".$score_metronome.", preceding time signature ".$old_beat_unit."/".$old_beat_type."<br />";
						/*	if(isset($beat_type[$score_part]) AND $new_beat_type <> $beat_type[$score_part] AND $score_metronome > 0) { 
								$beat_type[$score_part] = $new_beat_type;
								if(!isset($beat_unit[$score_part])) $beat_unit[$score_part] = 4;
								$beat_divide = beat_divide($new_beat_type);
								$tempo = round(($score_metronome * $beat_divide['p']) / $beat_divide['q']);
								$curr_event[$score_part][$j]['type'] = "mm";
								$curr_event[$score_part][$j]['value'] = $tempo;
								$curr_event[$score_part][$j]['p_dur'] = 0;
								$curr_event[$score_part][$j]['q_dur'] = 1;
								$is_chord = FALSE;
								$j++;
								if($trace_tempo OR $list_this) $report .= "••• New metronome = <font color=\"blue\">".$tempo."</font> as beat-type changed to ".$new_beat_type." yielding time signature = ".$beat_unit[$score_part]."/".($beat_divide['q'] * 4 / $beat_divide['p'])." p/q = ".$beat_divide['p']."/".$beat_divide['q']."<br />";
								} */
							$beat_type[$score_part] = $new_beat_type;
							continue;
							}
						if(($tempo_option == "all" OR $tempo_option == "score" OR $tempo_option == "allbutmeasures") AND is_integer($pos=strpos($line,"<per-minute>"))) {
							$per_minute = $score_metronome = round(trim(preg_replace("/<per\-minute>([^<]+)<\/per\-minute>/u","$1",$line)));
							if(!isset($beat_unit[$score_part])) $beat_unit[$score_part] = 4;
							if(!isset($beat_type[$score_part])) $beat_type[$score_part] = 4;
							$beat_divide = beat_divide($new_beat_type);
							$tempo = round(($per_minute * $beat_divide['p']) / $beat_divide['q']);
							if($list_this OR $trace_tempo) $report .= "••• Metronome will be set to ".$tempo." <i>prescriptive</i> (".$score_metronome." in HTML score, time signature ".$beat_unit[$score_part]."/".($beat_divide['q'] * 4 / $beat_divide['p']).")<br />";
							if($change_metronome) {
								if(!$said_tempo) {
									if($quad AND $list_this) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
									else if($list_this) $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
									$said_tempo = TRUE;
									}
								if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
								else {
									if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
									else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
									}
								if($list_this) $report .= "Metronome changed from ".$tempo." (printed score) to ".$tempo_changed."<br />";
								$tempo = $tempo_changed;
								}
							$sum_tempo_measure[$section][$i_measure] += $tempo;
							$number_tempo_measure[$section][$i_measure]++;
							$sum_metronome += $tempo;
							$number_metronome++;
							$number_metronome_markers++; // Added by BB 2022-01-31
							if($tempo > $metronome_max) $metronome_max = $tempo;
							if($tempo < $metronome_min OR $metronome_min == 0) $metronome_min = $tempo;
							if($tempo_option <> "allbutmeasures") { // Added by BB 2022-01-31
								$curr_event[$score_part][$j]['type'] = "mm"; // Added by BB 2021-03-14
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
						
						if(($tempo_option == "all" OR $tempo_option == "score" OR $tempo_option == "allbutmeasures") AND $accept_signs AND is_integer($pos=strpos($line,"<words"))) {
							$some_words = trim(preg_replace("/.*<words[^>]*>([^<]+)\s*<\/.+/u","$1",$line));
							if(strlen($some_words) > 2) {
								foreach($dynamic_sign_to_tempo as $dynamic_sign => $tempo) {
									if(is_integer(stripos($some_words,$dynamic_sign))) {
										if($change_metronome) {
											if(!$said_tempo) {
												if($quad AND $list_this) $report .= "<p><b>Tempo changed using quadratic mapping</b></p>";
												else if($list_this) $report .= "<p><b>Tempo changed using linear mapping (because quadratic is not monotonous):</b></p>";
												$said_tempo = TRUE;
												}
											if($quad) $tempo_changed = round($a * $tempo * $tempo + $b * $tempo + $c);
											else {
												if($tempo < $current_metronome_average) $tempo_changed = round(($a1 * ($tempo - $current_metronome_min)) + $b1);
												else $tempo_changed = round($a2 * ($tempo - $current_metronome_average) + $b2);
												}
											if($list_this) $report .= "Metronome changed from ".$tempo." (".$some_words.") to ".$tempo_changed."<br />";
											$tempo = $tempo_changed;
											}
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
						//	$last_date = $p_date[$i_measure][$score_part] / $p_date[$i_measure][$score_part];
							$last_date = $p_date[$i_measure][$score_part] / $q_date[$i_measure][$score_part]; // Fixed by BB 2022-02-14
							$p_date[$i_measure][$score_part] = 0;
							$q_date[$i_measure][$score_part] = 1;
							$n_breath = 0;
							$more_duration = 0;
							}
						if($backup AND is_integer($pos=strpos($line,"<duration>"))) {
							$duration = intval(trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line)));
							$curr_event[$score_part][$j]['type'] = "back";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = $duration;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$sum_durations -= $duration;
							if($trace_breath) $report .= ">> sum_durations = ".$sum_durations.", last_date = ".$last_date.", duration =".$duration.", more_duration = ".$more_duration."<br />";
							$j++;
						//	$more_duration = $last_date - $duration; // $$$$
							$curr_event[$score_part][$j]['type'] = "seq";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if(is_integer($pos=strpos($line,"</backup>"))) {
							$backup = FALSE;
							}
						if($include_breaths AND is_integer($pos=strpos($line,"<breath-mark"))) {
							$curr_event[$score_part][$j]['breath_mark'] = TRUE;
							if(!isset($breath_location[$n_breath])) {
						//		$time_this_field = ($p_date[$i_measure][$score_part] / $q_date[$i_measure][$score_part]) + $duration + $more_duration;
						//		$time = $sum_durations + ($p_date[$i_measure][$score_part] / $q_date[$i_measure][$score_part]) + ($curr_event[$score_part][$j]['p_dur'] / $curr_event[$score_part][$j]['q_dur']);
								$time = $sum_durations + ($curr_event[$score_part][$j]['p_dur'] / $curr_event[$score_part][$j]['q_dur']);
								if($fermata) $time += $duration;
								$breath_location[$n_breath] = $time;
								if($trace_breath) $report .= ">>> breath #".$n_breath.", breath_location = ".$time." sum_durations = ".$sum_durations."<br />";
						//		$more_duration = 0;
								$n_breath++;
								}
							else {
								if($trace_breath) $report .= ">>> breath #".$n_breath.", breath_location = ".$time.", duplicate, ignored<br />";
								if($time <> $breath_location[$n_breath]) {
									$message = "<font color=\"red\">Error in breath location:</font> ".$time." ≠ ".$breath_location[$n_breath]." measure #".$i_measure."<br />";
									if($list_this) $report .= $message;
									echo $message;
									}
								}
							}
						if(is_integer($pos=strpos($line,"<forward>"))) {
							$forward = TRUE;
							$is_chord = FALSE;
							}
						if(($forward) AND is_integer($pos=strpos($line,"<duration>"))) {
							$duration = trim(preg_replace("/<duration>([0-9]+)<\/duration>/u","$1",$line));
							$curr_event[$score_part][$j]['type'] = "seq"; // Fixed by BB 2022-01-30
							$curr_event[$score_part][$j]['note'] = '-';
							$curr_event[$score_part][$j]['p_dur'] = $duration;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							$curr_event[$score_part][$j]['forward'] = TRUE;
							if($trace_breath) $report .= ">> forward duration = ".$duration."<br />";
					///		$more_duration += $duration;
							$j++;
							$curr_event[$score_part][$j]['type'] = "seq";
							$curr_event[$score_part][$j]['note'] = '';
							$curr_event[$score_part][$j]['p_dur'] = 0;
							$curr_event[$score_part][$j]['q_dur'] = 1;
							}
						if(is_integer($pos=strpos($line,"</forward>"))) {
							$forward = FALSE;
							}
						if($note_on AND !$ignore_arpeggios AND is_integer($pos=strpos($line,"<arpeggiate "))) {
							$curr_event[$score_part][$j]['arpeggio'] = TRUE;
							}
						if($note_on AND !$ignore_trills AND is_integer($pos=strpos($line,"<trill-mark"))) {
							$trill = $found_trill = TRUE;
							$chromatic = $chromatic_trills;
							if(is_integer($pos=strpos($line,"trill-beats")))
								$trill_beats = trim(preg_replace("/.*trill-beats\s*=\s*\"(.+)\".*>/u","$1",$line));
							else $trill_beats = 3;
							}
						if(!$ignore_mordents AND $note_on AND is_integer($pos=strpos($line,"<inverted-mordent"))) {
							$upper_mordent = $found_mordent = TRUE;
							$chromatic = $chromatic_mordents;
							if(is_integer($pos=strpos($line,"long")))
								$long_ornamentation = trim(preg_replace("/.*long\s*=\s*\"(.+)\".*>/u","$1",$line));
							else $long_ornamentation = "no";
							continue;
							}
						if(!$ignore_mordents AND $note_on AND is_integer($pos=strpos($line,"<mordent"))) {
							$lower_mordent = $found_mordent = TRUE;
							$chromatic = $chromatic_mordents;
							if(is_integer($pos=strpos($line,"long")))
								$long_ornamentation = trim(preg_replace("/.*long\s*=\s*\"(.+)\".*>/u","$1",$line));
							else $long_ornamentation = "no";
							continue;
							}
						if(!$ignore_turns AND $note_on AND is_integer($pos=strpos($line,"<turn"))) {
							$turn = $found_turn = TRUE;
							$chromatic = $chromatic_turns;
							if(is_integer($pos=strpos($line,"beats")))
								$turn_beats = trim(preg_replace("/.*beats\s*=\s*\"(.+)\".*>/u","$1",$line));
							else $turn_beats = 4;
							continue;
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
					$empty_field[0] = TRUE; $forward = FALSE;
					$breath = $found_breath = FALSE;
					$last_i_new_tempo[0] = -1;
					$p_last_next_date_new_tempo[0] = 0; $q_last_next_date_new_tempo[0] = 1;

					ksort($curr_event[$score_part]);
					foreach($curr_event[$score_part] as $j => $the_event) {
						if(!isset($the_event['type'])) { // Added by BB 2022-01-28
							if($list_this) $report .= "<font color=\"red\">Potential error:</font> the_event['type'] is not set: measure #".$i_measure." j = ".$j."<br />";
							continue;
							}
						if(isset($the_event['turn'])) {
							if($list_this) $report .= "<font color=\"blue\">turn ";
							$turn_beats = $the_event['turn-beats'];
							$long_ornamentation = $the_event['long'];
							if($list_this) $report .= "beats = ".$turn_beats."</font> ";
							if(isset($the_event['chromatic']) AND $the_event['chromatic'])
							if($list_this) $report .= "(chromatic) ";
							if($list_this) $report .= "long = \"".$long_ornamentation."\" ";
							if($list_this) $report .= "measure #".$i_measure." field #".($i_field_of_measure + 1)."<br />";
							}
						if(isset($the_event['ornament'])) {
							if($list_this) $report .= "<font color=\"blue\">".$the_event['ornament']."</font> ";
							if(isset($the_event['chromatic']) AND $the_event['chromatic'])
							if($list_this) $report .= "(chromatic) ";
							if($the_event['ornament'] == "trill") {
								$trill_beats = $the_event['trill-beats'];
								if($trill_beats <> 3 AND $list_this) $report .= "trill beats=\"".$trill_beats."\" ";
								}
							else {
								$long_ornamentation = $the_event['long'];
								if($list_this) $report .= "long = \"".$long_ornamentation."\" ";
								}
							if($list_this) $report .= "measure #".$i_measure." field #".($i_field_of_measure + 1)."<br />";
							}
						if($the_event['type'] == "mm" AND $i_field_of_measure == 0) { // Fixed by BB 2022-02-01
							$new_tempo = $the_event['value'];
							$fraction = $new_tempo."/60";
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							if(isset($the_event['word'])) {
								if($list_this OR $trace_tempo) $report .= "••• Dynamic sign “<font color=\"blue\">".$the_event['word']."</font>” setting metronome to ".$new_tempo."<br />";
							//	$current_period = 60 / $new_tempo;
								}
							$current_period = 60 / $new_tempo; // Fixed by BB 2022-02-14
							// We store new tempo values appearing in the first field (upper line)
							$i_new_tempo++;
							$value_new_tempo[$i_new_tempo] = $new_tempo;
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
							$p_date_this_tempo = $add['p'];
							$q_date_this_tempo = $add['q'];
							$p_date_new_tempo[$i_new_tempo] = $p_date_this_tempo;
							$q_date_new_tempo[$i_new_tempo] = $q_date_this_tempo;
							if($super_trace) $report .= "•••• i_new_tempo = ".$i_new_tempo.", date_this_tempo = ".$p_date_this_tempo."/".$q_date_this_tempo.", time_field = ".$p_time_field."/".$q_time_field.", stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream = “".$stream."”<br />";
							$final_metronome = $new_tempo;
							if($p_time_field == 0) {
								$convert_measure[$score_part] .= "||".$new_tempo."||"; // Added by BB 2022-02-01
								if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part [".$score_part."] field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$p_date_this_tempo/($q_date_this_tempo * $divisions[$score_part])." beat(s) [d]<br />";
								$current_period = 60 / $new_tempo;
								$new_tempo = 0;
								}
					//		$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
							$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
							continue; // Added by BB 2022-02-01
							}
						if($i_field_of_measure > 0) {
							// Find date of next new tempo 
							if($i_new_tempo == 0 AND isset($p_date_new_tempo[$i_new_tempo])) {
								$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
								$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
								if($super_trace) $report .= ">>> i_new_tempo = 0, field = ".($i_field_of_measure + 1).", next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo."<br />";
								}
							if($p_next_date_new_tempo >= 0) {
								$add = add($p_time_field,$q_time_field,-$p_next_date_new_tempo,$q_next_date_new_tempo);
								if($super_trace) $report .= "• i_new_tempo = ".$i_new_tempo.", last_i_new_tempo = ".$last_i_new_tempo[$i_field_of_part].", field #".($i_field_of_part + 1).", time_field = ".$p_time_field."/".$q_time_field.", next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo.", (time_field - next_date_new_tempo) = ".$add['p']."/".$add['q']."<br />";
								if(($add['p'] * $add['q']) >= 0 OR ($add['q'] <> 0 AND abs($add['p'] / $add['q'] / $divisions[$score_part]) < 0.05)) { // Here we eliminate rounding errors
									$new_tempo = $value_new_tempo[$i_new_tempo];
									if($list_this OR $trace_tempo) $report .= "• measure #".$i_measure." field #".($i_field_of_part + 1)." : new_tempo = ".$new_tempo.", i_new_tempo = ".$i_new_tempo.", time_field = ".$p_time_field."/".$q_time_field."<br />";
									while(isset($p_date_new_tempo[$i_new_tempo]) AND isset($p_date_new_tempo[$i_new_tempo + 1]) AND ($p_date_new_tempo[$i_new_tempo]/$q_date_new_tempo[$i_new_tempo] == $p_date_new_tempo[$i_new_tempo + 1]/$q_date_new_tempo[$i_new_tempo + 1]) AND $new_tempo == $value_new_tempo[$i_new_tempo + 1]) {
										// Discard duplicate values when importing both score and performance values
										if($list_this OR $trace_tempo) $report .= "=> Discarded duplicate tempo value ".$new_tempo." in field #".($i_field_of_part + 1)."<br />";
										$i_new_tempo++;
										}
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
							if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date." beat(s) [a]<br />";
							$final_metronome = $new_tempo;
							$fraction = $new_tempo."/60";
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
						//	$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
							$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
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
								if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date." beat(s) [b]<br />";
								$final_metronome = $new_tempo;
								$new_tempo = 0;
								}
							if($test_musicxml)
								echo " time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration.", stream_units = ".$stream_units.", the_event[dur] = ".$the_event['p_dur']."/".$the_event['q_dur'].", j = ".$j.", divisions = ".$divisions[$score_part]." stream = “".$stream."”<br />";
							if($stream_units > 0) {
								if($super_trace) $report .= "Stream = “".$stream."” measure #".$i_measure." field #".($i_field_of_part + 1).", stream_duration = ".$p_stream_duration."/".$q_stream_duration.", time_field = ".$p_time_field."/".$q_time_field."<br />";
								$physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
								$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
								$p_time_field = $add['p']; $q_time_field = $add['q'];

								if($breath_in_stream) {
									$add_breath_to_stream_duration = add_breath_to_stream_duration($p_stream_duration,$q_stream_duration,$divisions[$score_part],$breath_length,$stream,$stream_units);
									$fraction = $add_breath_to_stream_duration['fraction'];
									$stream = $add_breath_to_stream_duration['stream'];
									}
								else $fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
								$breath_in_stream = FALSE;
								
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
								// The following needs to be revised because _tempo() should be placed before the next expression. 2022-01-31
								$convert_measure[$score_part] .= $stream;
								if($stream_units <> $n) $convert_measure[$score_part] .= "}";
								if($new_tempo > 0) {
									$convert_measure[$score_part] .= "||".$new_tempo."||";
									$current_period = 60 / $new_tempo;
									$fraction_date_new_tempo = $p_time_field."/".($q_time_field * $divisions[$score_part]);
									$simplify = simplify($fraction_date_new_tempo,$max_term_in_fraction);
									$fraction_date_new_tempo = $simplify['fraction'];
									if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part [".$score_part."] field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date_new_tempo." beat(s) [c]<br />";
									$final_metronome = $new_tempo;
									if($p_time_field == 0 AND $i_field_of_part == 0) {
										$fraction = $new_tempo."/60";
										$simplify = simplify($fraction,$max_term_in_fraction);
										$fraction = $simplify['fraction'];
								//		$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
										$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
										}
									$new_tempo = 0;
									}
								$stream = ''; $stream_units = 0; $p_stream_duration = 0; $q_stream_duration = 1;
								}
							else {
								if($new_tempo > 0) {
									$stream .= "||".$new_tempo."||";
									$current_period = 60 / $new_tempo;
									if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date." beat(s) [d]<br />";
									$final_metronome = $new_tempo;
									if($p_time_field == 0 AND $i_field_of_part == 0) {
										$fraction = $new_tempo."/60";
										$simplify = simplify($fraction,$max_term_in_fraction);
										$fraction = $simplify['fraction'];
								//		$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
										$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
										}
									$new_tempo = 0;
									}
								}
							if($the_event['type'] == "back") {
								if(isset($breath_location[$i_breath])) {
									if($trace_breath) $report .= "@@@ on backup, breath_location[".$i_breath."] = ".$breath_location[$i_breath]." field = ".($i_field_of_measure + 1).", time_field = ".$p_time_field."/".$q_time_field.", stream_duration = ".$p_stream_duration."/".$q_stream_duration.", note “".$the_event['note']."”<br />";
									$add = add($p_time_field,$q_time_field,$the_event['p_dur'],$the_event['q_dur']);
									$add = add($add['p'],$add['q'],-$breath_location[$i_breath],1);
									if(($add['p'] * $add['q']) >=  0)
									$convert_measure[$score_part] .= $breath_trace_tag.$breath_rest;
									if($trace_breath) $report .= "Breath end of field<br />";
									$i_breath = 0;
									}
								$convert_measure[$score_part] .= "§".$i_field_of_part."§,";
								$p_field_duration[$i_field_of_part] = $p_time_field;
								$q_field_duration[$i_field_of_part] = $q_time_field * $divisions[$score_part];
								if($list_this) $report .= "+ measure #".$i_measure." field #".($i_field_of_part + 1)." : physical time = ".round($physical_time,2)."s";
								if($empty_field[$i_field_of_part] AND $list_this) $report .= " (only silence)";
								if($list_this) $report .= "<br />";
								if(!$empty_field[$i_field_of_part] AND $physical_time > $max_physical_time)
									$max_physical_time = $physical_time;
								$physical_time = 0.;
								$i_field_of_part++; $i_field_of_measure++;
								$empty_field[$i_field_of_part] = TRUE;
								$new_tempo = 0;
								$i_new_tempo = 0;
								$i_breath = 0;
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
										if($list_this) $report .= "<font color=\"red\">➡ </font> Error in measure ".$i_measure." part ".$score_part." field #".($i_field_of_part + 1).", ‘backup’ rest = -".$fraction." beat(s) <font color=\"MediumTurquoise\">(fixed)</font><br />";
										}
									else if($p_rest > 0 AND ($p_rest/$q_rest) <= ($divisions[$score_part]/100)) {
										if($list_this) $report .= "<font color=\"MediumTurquoise\">• </font> Rounding part ".$score_part." measure ".$i_measure." field #".($i_field_of_part + 1).", neglecting ‘backup’ rest = ".$fraction."<br />";
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
							// if($list_this) $report .= "physical_time1 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
					//		if($i_field_of_part == 0) $physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
							$fraction_date = $add['p']."/".($add['q'] * $divisions[$score_part]);
							$simplify_date = simplify($fraction_date,$max_term_in_fraction);
							$fraction_date = $simplify_date['fraction'];
							if($list_this) $report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." note ‘".$the_event['note']."’ at date ".$fraction_date." increased by ".$fraction ." beat(s) as per fermata #".($i_fermata + 1)."<br />";
							}
						if($i_field_of_part > 0 AND $p_date_next_fermata >= 0 AND ($starting_chord OR $the_event['type'] == "seq") AND $the_event['note'] <> '') {
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
							$add = add($add['p'],$add['q'],-$p_date_next_fermata,$q_date_next_fermata);
							if(($add['p'] * $add['q']) >= 0) {
								$the_event['fermata'] = TRUE;
								// Fermata applied to note or rest
								$physical_time += $current_period * ($p_fermata_duration[$i_measure][$score_part][$i_fermata] / $q_fermata_duration[$i_measure][$score_part][$i_fermata] / $divisions[$score_part]);
								// if($list_this) $report .= "physical_time2 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
								$add = add($the_event['p_dur'],$the_event['q_dur'],$p_fermata_duration[$i_measure][$score_part][$i_fermata],$q_fermata_duration[$i_measure][$score_part][$i_fermata]);
								$the_event['p_dur'] = $add['p'];
								$the_event['q_dur'] = $add['q'];
								$fraction = $p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".($q_fermata_duration[$i_measure][$score_part][$i_fermata] * $divisions[$score_part]);
								$simplify = simplify($fraction,$max_term_in_fraction);
								$fraction = $simplify['fraction'];
								$fraction_date = $p_date_next_fermata."/".($q_date_next_fermata * $divisions[$score_part]);
								$simplify_date = simplify($fraction_date,$max_term_in_fraction);
								$fraction_date = $simplify_date['fraction'];
								if($list_this) $report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." note ‘".$the_event['note']."’ at date ".$fraction_date." increased by ".$fraction." beat(s) to insert fermata #".($i_fermata + 1)."<br />";
								}
							else if($the_event['note'] == "-") {
								$add = add($add['p'],$add['q'],$the_event['p_dur'],$the_event['q_dur']);
								if(($add['p'] * $add['q']) >= 0) {
									$the_event['fermata'] = TRUE;
									// Fermata occuring inside a rest
									$physical_time += $current_period * ($p_fermata_duration[$i_measure][$score_part][$i_fermata] / $q_fermata_duration[$i_measure][$score_part][$i_fermata] / $divisions[$score_part]);
									// if($list_this) $report .= "physical_time3 = ".$physical_time." current_period = ".$current_period." fermata_duration = ".$p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".$q_fermata_duration[$i_measure][$score_part][$i_fermata]."<br />";
									$add = add($the_event['p_dur'],$the_event['q_dur'],$p_fermata_duration[$i_measure][$score_part][$i_fermata],$q_fermata_duration[$i_measure][$score_part][$i_fermata]);
									$fraction = $p_fermata_duration[$i_measure][$score_part][$i_fermata]."/".($q_fermata_duration[$i_measure][$score_part][$i_fermata] * $divisions[$score_part]);
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
									$fraction_date = $p_date_next_fermata."/".($q_date_next_fermata * $divisions[$score_part]);
									$simplify_date = simplify($fraction_date,$max_term_in_fraction);
									$fraction_date = $simplify_date['fraction'];
									if($list_this) $report .= "<font color=\"MediumTurquoise\">f+ </font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." silence at date ".$fraction_date." increased by ".$fraction." to insert fermata #".($i_fermata + 1)."<br />";
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
							//	if($list_this) $report .= "Next fermata date (#".$i_fermata.") = ".$p_date_next_fermata."/".$q_date_next_fermata."<br />";
								}
							else $p_date_next_fermata = -1;
							}	
						$starting_chord = FALSE;
							
						if(!$is_chord AND $the_event['type'] == "chord") {
							if($new_tempo > 0) {
								$convert_measure[$score_part] .= "||".$new_tempo."||";
								$current_period = 60 / $new_tempo;
								if($list_this OR $trace_tempo) $report .= "• Measure #".$i_measure." part [".$score_part."] field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date." beat(s) [e]<br />";
								$final_metronome = $new_tempo;
								if($p_time_field == 0 AND $i_field_of_part == 0) {
									$fraction = $new_tempo."/60";
									$simplify = simplify($fraction,$max_term_in_fraction);
									$fraction = $simplify['fraction'];
							//		$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
									$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
									}
								$new_tempo = 0;
								}
							$p_duration = $the_event['p_dur']; $q_duration = $the_event['q_dur'];
							if($stream_units > 0) {
								if($test_musicxml)
									echo $the_event['note']." time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream_units = ".$stream_units." closing stream = “".$stream."”<br />";
								if($p_stream_duration == 0) { // Grace notes
						//		if($list_this) $report .= "Grace stream = ".$stream." measure #".$i_measure." field #".($i_field_of_measure + 1)."<br />";
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
									$physical_time += $current_period * ($p_grace / $q_grace / $divisions[$score_part]);
									$fraction = $p_grace."/".($q_grace * $divisions[$score_part]);
									}
								else {
									if($breath_in_stream) {
										$add_breath_to_stream_duration = add_breath_to_stream_duration($p_stream_duration,$q_stream_duration,$divisions[$score_part],$breath_length,$stream,$stream_units);
										$fraction = $add_breath_to_stream_duration['fraction'];
										$stream = $add_breath_to_stream_duration['stream'];
										}
									else $fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
									$breath_in_stream = FALSE;
									$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
									$p_time_field = $add['p']; $q_time_field = $add['q'];
									$physical_time += $current_period * ($p_stream_duration / $q_stream_duration / $divisions[$score_part]);
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
							if(isset($the_event['arpeggio'])) {
								$convert_measure[$score_part] .= "arpeggio(".$i_measure.")";
								if($list_this) $report .= "<font color=\"blue\">Arpeggio</font> measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)."<br />";
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
						
						$altered_diatonic_scale = adjust_scale($diatonic_scale,$altered_diatonic_scale,$the_event['note']);
						$diatonic_scale_string = '';
						if(count($altered_diatonic_scale) > 0) {
							for($i = 0; $i < count($altered_diatonic_scale); $i++) {
								if($i > 0) $diatonic_scale_string .= ",";
								$diatonic_scale_string .= $altered_diatonic_scale[$i];
								}
							}
						$p_duration = $the_event['p_dur']; $q_duration = $the_event['q_dur'];
				//		$breath = FALSE;
						if(isset($the_event['breath_mark']) AND !$found_breath) {
							$found_breath = TRUE;
							$add_breath = add($p_duration,$q_duration,$p_stream_duration,$q_stream_duration);
							$add_breath = add($add_breath['p'],$add_breath['q'],$p_time_field,$q_time_field);
							$p_breath = $add_breath['p']; $q_breath = $add_breath['q'];
							if($list_this) $report .= "<font color=\"red\">Breath</font> measure #".$i_measure." at ".($p_breath / ($q_breath * $divisions[$score_part]))." beat(s)<br />";
							}
						if(isset($breath_location[$i_breath])) {
							$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
							$add = add($add['p'],$add['q'],-$breath_location[$i_breath],1);
							if(($add['p'] * $add['q']) >=  0) $breath = TRUE;
							if($trace_breath) $report .= "@@@ breath_location[".$i_breath."] = ".$breath_location[$i_breath]." field = ".($i_field_of_measure + 1).", time_field = ".$p_time_field."/".$q_time_field.", stream_duration = ".$p_stream_duration."/".$q_stream_duration.", p * q = ".($add['p'] * $add['q'])." note “".$the_event['note']."”<br />";
							}
						if(!$is_chord) {
							if(isset($the_event['forward'])) $forward = TRUE;
							$fraction = $the_event['p_dur']."/".($divisions[$score_part] * $the_event['q_dur']);
							if($super_trace) $report .= "•• measure #".$i_measure." note “".$the_event['note']."”, duration = ".$p_duration."/".$q_duration.", field #".($i_field_of_part + 1)." time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream_units = ".$stream_units." event_dur = ".$the_event['p_dur']."/".$the_event['q_dur'].", new_tempo = ".$new_tempo."<br />";
							if(($p_duration * $q_old_duration) == ($p_old_duration * $q_duration)) {
								if($breath) {
									if($trace_breath) $report .= "Breath in stream<br />";
								//	$stream .= $breath_trace_tag.$breath_rest;
									$stream .= $breath_trace_tag." _rest ";
									$breath = FALSE;
									$i_breath++;
									$breath_in_stream = TRUE;
									}
								$stream = add_note($stream,$i_measure,$the_event,$long_ornamentation,$diatonic_scale_string);
								if($the_event['note'] <> "-" AND $the_event['note'] <> '') $empty_field[$i_field_of_part] = FALSE;
								$stream_units++;
								$add = add($p_stream_duration,$q_stream_duration,$p_duration,$q_duration);
								$p_stream_duration = $add['p']; $q_stream_duration = $add['q'];

								}
							else {
								if($stream_units > 0) {
									if($p_stream_duration == 0 AND !$forward) { // Grace notes
										if($super_trace) $report .= "Grace stream = “".$stream."” measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
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
										$physical_time += $current_period * ($p_grace/$q_grace / $divisions[$score_part]);
										$fraction = $p_grace."/".($q_grace * $divisions[$score_part]);
										}
									else {
										if($super_trace) $report .= "Stream = “".$stream."” measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";

										if($breath_in_stream) {
											$add_breath_to_stream_duration = add_breath_to_stream_duration($p_stream_duration,$q_stream_duration,$divisions[$score_part],$breath_length,$stream,$stream_units);
											$fraction = $add_breath_to_stream_duration['fraction'];
											$stream = $add_breath_to_stream_duration['stream'];
											}
										else $fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
										$breath_in_stream = FALSE;
										
										$add = add($p_time_field,$q_time_field,$p_stream_duration,$q_stream_duration);
										$physical_time += $current_period * ($p_stream_duration/$q_stream_duration / $divisions[$score_part]);
										}
									$p_time_field = $add['p']; $q_time_field = $add['q'];
							//		if($list_this) $report .= "p_time_field/q_time_field = ".$p_time_field."/".$q_time_field."<br />";
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
									$forward = FALSE;
									if($stream_units <> $n) $convert_measure[$score_part] .= "}";
									
									if($new_tempo > 0) { // maybe useless
										$convert_measure[$score_part] .= "||".$new_tempo."||";
										$current_period = 60 / $new_tempo;
										if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> at date ".$fraction_date." beat(s) [f]<br />";
										$final_metronome = $new_tempo;
										if($p_time_field == 0 AND $i_field_of_part == 0) {
											$fraction = $new_tempo."/60";
											$simplify = simplify($fraction,$max_term_in_fraction);
											$fraction = $simplify['fraction'];
									//		$old_tempo = $current_tempo = $default_tempo[$section] = $fraction;
											$old_tempo = $current_tempo = $fraction; // Fixed by BB 2022-02-14
											}
										$new_tempo = 0;
										}
									}
								
								if($breath) {
									if($trace_breath) $report .= "Breath outside of stream<br />";
									$convert_measure[$score_part] .= $breath_trace_tag.$breath_rest;
									$breath = FALSE;
									$i_breath++;
									}
								
								$fraction_date = $p_time_field."/".($q_time_field * $divisions[$score_part]);
								$simplify_date = simplify($fraction_date,$max_term_in_fraction);
								$fraction_date = $simplify_date['fraction'];
								
								// Check whether changes of tempo occur during a rest
								while(TRUE) {
									if($i_field_of_part > 0 AND $new_tempo == 0 AND $p_next_date_new_tempo > 0 AND $the_event['note'] == "-") {
										$add = add($p_time_field,$q_time_field,$the_event['p_dur'],$the_event['q_dur']);
										$add = add($add['p'],$add['q'],-$p_next_date_new_tempo,$q_next_date_new_tempo);
									//	if($list_this) $report .= "time_field = ".$p_time_field."/".$q_time_field." event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']." next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo."<br />";
										if(($add['p'] * $add['q']) >= 0 AND isset($value_new_tempo[$i_new_tempo]) AND $value_new_tempo[$i_new_tempo] > 0) {
											$add = add($p_next_date_new_tempo,$q_next_date_new_tempo,-$p_time_field,$q_time_field);
											$p_initial_part = $add['p'];
											$q_initial_part = $add['q'];
										//	if($list_this) $report .= " initial_part = ".$p_initial_part."/".$q_initial_part."<br />";
										//	if(($p_initial_part / $q_initial_part) > 0.05) {
											if(($p_initial_part / $q_initial_part / $divisions[$score_part]) >= 0) {
												// Create initial part of rest still at the old tempo
												$fraction = $p_initial_part."/".($q_initial_part * $divisions[$score_part]);
												$simplify = simplify($fraction,$max_term_in_fraction);
												$fraction = $simplify['fraction'];
												if($fraction <> "0")
													$convert_measure[$score_part] .= " ".$fraction." ";
												// Reduce duration of rest by the initial part
											//	if($list_this) $report .= "event_dur = ".$the_event['p_dur']."/".$the_event['q_dur']." initial_part = ".$p_initial_part."/".$q_initial_part."<br />";
												$add = add($the_event['p_dur'],$the_event['q_dur'],-$p_initial_part,$q_initial_part);
												$p_duration = $the_event['p_dur'] = $add['p'];
												$q_duration = $the_event['q_dur'] = $add['q'];
												// Update time of field
												$add = add($p_time_field,$q_time_field,$p_initial_part,$q_initial_part);
												$p_time_field = $add['p'];
												$q_time_field = $add['q'];
												$physical_time += $current_period * ($p_initial_part/$q_initial_part / $divisions[$score_part]);
												// Update new_tempo
												$new_tempo = $value_new_tempo[$i_new_tempo];
												// if($list_this) $report .= " new_tempo = ".$new_tempo." for i_new_tempo = ".$i_new_tempo." [a]<br />";
												$fraction = $p_next_date_new_tempo."/".($q_next_date_new_tempo * $divisions[$score_part]);
												$simplify = simplify($fraction,$max_term_in_fraction);
												$fraction_date_new_tempo = $simplify['fraction'];
												$i_new_tempo++;
												if(isset($p_date_new_tempo[$i_new_tempo])) {
													$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
													$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
													$last_i_new_tempo[$i_field_of_part] = $i_new_tempo;
													$p_last_next_date_new_tempo[$i_field_of_part] = $p_next_date_new_tempo;
													$q_last_next_date_new_tempo[$i_field_of_part] = $q_next_date_new_tempo;
													// if($list_this) $report .= "i_field_of_part = ".$i_field_of_part." next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." last_next_date_new_tempo[i_field_of_part] = ".$p_last_next_date_new_tempo[$i_field_of_part]."/".$q_last_next_date_new_tempo[$i_field_of_part]."<br />";
													}
												else {
													$p_next_date_new_tempo = $q_next_date_new_tempo = $last_i_new_tempo[$i_field_of_part] = -1;
													}
												// if($list_this) $report .= "new_tempo = ".$new_tempo." next tempo[".($i_new_tempo - 1)."] = ".$value_new_tempo[$i_new_tempo - 1]."<br />";
												if($new_tempo <> round(60 / $current_period)) { // Added by BB 2021-03-16
													$convert_measure[$score_part] .= " ||".$new_tempo."|| ";
													$current_period = 60 / $new_tempo; // Added by BB 2021-03-16
													if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part ".$score_part." field #".($i_field_of_part + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> (".($i_new_tempo - 1)."th value) during rest at date ".$fraction_date_new_tempo."  beat(s)<br />";
													$final_metronome = $new_tempo;
													}
												$new_tempo = 0;
												}
											else break;
											}
										else break;
										}
									else break;
									}
								$stream = '';
								$stream = add_note($stream,$i_measure,$the_event,$long_ornamentation,$diatonic_scale_string);
								$stream_units = 1;
								if($the_event['note'] <> "-" AND $the_event['note'] <> '') $empty_field[$i_field_of_part] = FALSE;
								$p_old_duration = $p_stream_duration = $p_duration;
								$q_old_duration = $q_stream_duration = $q_duration;
								}
							}
						else {
							if(isset($the_event['slur'])) {
								if($the_event['slur'] == "start") $convert_measure[$score_part] .= " _legato_ ";
								else if($the_event['slur'] == "stop") $convert_measure[$score_part] .= " _nolegato_ ";
								}
							$convert_measure[$score_part] .= $the_event['note'];
							if($the_event['note'] <> "-" AND $the_event['note'] <> '') {
								$empty_field[$i_field_of_part] = FALSE;
								if(isset($breath_location[$i_breath])) {
									if($trace_breath) $report .= "@@@ in chord, breath_location[".$i_breath."] = ".$breath_location[$i_breath]." field = ".($i_field_of_measure + 1).", time_field = ".$p_time_field."/".$q_time_field.", stream_duration = ".$p_stream_duration."/".$q_stream_duration.", note “".$the_event['note']."”<br />";
									$add = add($p_time_field,$q_time_field,$the_event['p_dur'],$the_event['q_dur']);
									$add = add($add['p'],$add['q'],-$breath_location[$i_breath],1);
									if(($add['p'] * $add['q']) >=  0) {
										$convert_measure[$score_part] .= $breath_trace_tag.$breath_rest;
										if($trace_breath) $report .= "Breath in chord<br />";
										}
									}

								}
							$convert_measure[$score_part] .= ",";
							}
						}
					unset($the_event);
					
					$convert_measure[$score_part] = process_arpeggios($convert_measure[$score_part],$divisions[$score_part],$trace_ornamentations);
					if($stream_units > 0) {
						if($list_this) $report .= "Before last field of measure ".$i_measure.", time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream = “".$stream."”<br />";
						if($test_musicxml)
							echo "Before last field of measure ".$i_measure.", time_field = ".$p_time_field."/".$q_time_field." stream_duration = ".$p_stream_duration."/".$q_stream_duration." stream = “".$stream."”<br />";
						if($breath_in_stream) {
							$add_breath_to_stream_duration = add_breath_to_stream_duration($p_stream_duration,$q_stream_duration,$divisions[$score_part],$breath_length,$stream,$stream_units);
							$fraction = $add_breath_to_stream_duration['fraction'];
							$stream = $add_breath_to_stream_duration['stream'];
							}
						else $fraction = $p_stream_duration."/".($q_stream_duration * $divisions[$score_part]);
						$breath_in_stream = FALSE;
						$simplify = simplify($fraction,$max_term_in_fraction);
						$fraction = $simplify['fraction'];
						if($simplify['q'] > 0) $n = $simplify['p'] / $simplify['q'];
						else $n = 0;
						if($stream_units <> $n) {
					//		if($list_this) $report .= "Grace stream = ".$stream." measure #".$i_measure." field #".($i_field_of_part + 1)."<br />";
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
						$physical_time += $current_period * ($p_stream_duration/$q_stream_duration / $divisions[$score_part]);
						$stream = ''; $stream_units = 0; $p_stream_duration = 0; $q_stream_duration = 1;
						}
					
					if($breath) {
						if($trace_breath) $report .= "breath at end of field<br />";
						$convert_measure[$score_part] .= $breath_trace_tag.$breath_rest;
						$breath = FALSE;
						$i_breath = 0;
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
						if($old_tempo == '') $old_tempo = $fraction;
						$current_tempo = $fraction;
						// if($list_this) $report .= "number_tempo_measure = ".$number_tempo_measure[$section][$i_measure]." metronome_this_measure = ".$metronome_this_measure." current_tempo = ".$current_tempo."<br />";
						}
					else if(isset($default_tempo[$section])) {
						// We must repeat tempo on each measure to play it separately
							if($super_trace) $report .= "Default_tempo[".$section."] = ".$default_tempo[$section]."<br />";
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
					//	if(!isset($old_volume[$score_part])) $old_volume[$score_part] = $volume;
						$old_volume[$score_part] = $volume; // Fixed by BB 2022-02-15
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
					if($reload_musicxml AND $number_parts > 1 ) {						
						if($include_parts) $data .= "[".$score_part."]";
						if($apply_rndtime[$i_part - 1]) $data .= " _rndtime(".$rndtime[$i_part - 1].") ";
						if($apply_rndvel[$i_part - 1]) $data .= " _rndvel(".$rndvel[$i_part - 1].") ";
						}
					$convert_measure[$score_part] = fix_alterations($convert_measure[$score_part]);
					if($found_breath) { // Adjust the total duration of this measure
						$add = add($p_time_measure,$q_time_measure,$breath_length['p'] * $divisions[$score_part],$breath_length['q']);
						$p_time_measure = $add['p']; $q_time_measure = $add['q'];
						}
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
						// if($list_this) $report .= "field #".($j + 1)." duration_this_measure = ".$p_duration_this_measure."/".$q_duration_this_measure." field_duration = ".$p_field_duration[$j]."/".$q_field_duration[$j]." duration of gap = ".$p_duration."/".$q_duration."<br />";
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
								// if($list_this) $report .= "i_new_tempo = ".$i_new_tempo." field #".($j + 1)." new_tempo = ".$new_tempo." next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." date = ".$fraction_date." time_field = ".($p_time_field/$q_time_field)." duration = ".$p_duration."/".$q_duration.", gap = ".$p_gap."/".$q_gap." = ".$p_gap/$q_gap.", divisions = ".$divisions[$score_part]."<br />";
								if($j > 0 AND $p_next_date_new_tempo > 0) {
									$add = add($p_time_field,$q_time_field,$p_duration,$q_duration);
									$add = add($add['p'],$add['q'],-$p_next_date_new_tempo,$q_next_date_new_tempo);
									if(($add['p'] * $add['q']) >= 0) {
										// if($list_this) $report .= "remaining ".$add['p']."/".$add['q']."<br />";
										// if($list_this) $report .= "next_date_new_tempo = ".$p_next_date_new_tempo."/".$q_next_date_new_tempo." time_field = ".$p_time_field."/".$q_time_field." = ".$p_time_field/$q_time_field."<br />";
										$add = add($p_next_date_new_tempo,$q_next_date_new_tempo,-$p_time_field,$q_time_field);
										$p_initial_part = $add['p'];
										$q_initial_part = $add['q'];
										// if($list_this) $report .= "initial_part ".$p_initial_part."/".$q_initial_part."<br />";
										if($p_initial_part >= 0 OR (abs($p_initial_part / $q_initial_part / $divisions[$score_part]) < 0.05)) {
											// Create initial part of additional rest still at the old tempo
											$fraction = $p_initial_part."/".($q_initial_part * $divisions[$score_part]);
											$simplify = simplify($fraction,$max_term_in_fraction);
											$fraction = $simplify['fraction'];
											$stream .= " ".$fraction." ";
											// Reduce duration of rest by the initial part
											// if($list_this) $report .= " initial_part = ".$p_initial_part."/".$q_initial_part." gap = ".$p_gap."/".$q_gap."<br />";
											$add = add($p_gap * $divisions[$score_part],$q_gap,-$p_initial_part,$q_initial_part); // Fixed by BB 2022-01-29
											$p_duration = $p_gap = $add['p'];
											$q_duration = $add['q'];
											$q_gap = $q_duration * $divisions[$score_part]; // Fixed by BB 2022-01-29
											// Update time of field
											$add = add($p_time_field,$q_time_field,$p_initial_part,$q_initial_part);
											$p_time_field = $add['p'];
											$q_time_field = $add['q'];
											$physical_time += $current_period * ($p_initial_part/$q_initial_part / $divisions[$score_part]);
											// Update new_tempo
											$new_tempo = $value_new_tempo[$i_new_tempo];
											$i_new_tempo_mem = $i_new_tempo;
											$fraction = $p_next_date_new_tempo."/".($q_next_date_new_tempo * $divisions[$score_part]);
											$simplify = simplify($fraction,$max_term_in_fraction);
											$fraction_date_new_tempo = $simplify['fraction'];
											$i_new_tempo++;
											if(isset($p_date_new_tempo[$i_new_tempo])) {
												$p_next_date_new_tempo = $p_date_new_tempo[$i_new_tempo];
												$q_next_date_new_tempo = $q_date_new_tempo[$i_new_tempo];
												$last_i_new_tempo[$j] = $i_new_tempo;
												}
											else $i_new_tempo = -1;
											$stream .= " ||".$new_tempo."|| ";
											if($list_this OR $trace_tempo) $report .= "<font color=\"red\">mm</font> Measure #".$i_measure." part [".$score_part."] field #".($j + 1)." metronome set to <font color=\"blue\">".$new_tempo."</font> (".$i_new_tempo_mem."th value) during additional rest at date ".$fraction_date_new_tempo." beat(s)<br />";
											$final_metronome = $new_tempo;
											$new_tempo = 0;
											}
										else break;
										}
									else break;
									}
								else break;
								}
							$fraction = $fraction_mem = $p_gap."/".$q_gap;
							$simplify = simplify($fraction,$max_term_in_fraction);
							$fraction = $simplify['fraction'];
							$data = str_replace("§".$j."§","§".$j."§ ".$stream.$fraction,$data);
							$p_field_duration[$j] = $p_duration_this_measure;
							$q_field_duration[$j] = $q_duration_this_measure;
							if($list_this OR $trace_tempo) $report .= "<font color=\"MediumTurquoise\">+rest </font> Measure #".$i_measure." part ".$score_part." field #".($j + 1)." added rest = ".$fraction." beat(s) (".$fraction_mem.")<br />";
							}
						}
					
					if($list_this) $report .= "+ measure #".$i_measure." field #".$i_field_of_part." : physical time = ".round($physical_time,2)."s";
					if($empty_field[$i_field_of_part - 1] AND $list_this) $report .= " (only silence)";
					if($list_this) $report .= "<br />";
					if(!$empty_field[$i_field_of_part - 1] AND $physical_time > $max_physical_time)
						$max_physical_time = $physical_time;
					
			/*		if($max_physical_time > 0.) $final_metronome = round(60 * $p_time_measure / ($q_time_measure * $divisions[$score_part]) / $max_physical_time);
					else $final_metronome = $metronome_this_measure; */
					// if($list_this) $report .= "Measure #".$i_measure." max_physical_time = ".round($max_physical_time,2)."s, time_measure = ".$p_time_measure."/".$q_time_measure.", divisions = ".$divisions[$score_part].", number_tempo_measure = ".$number_tempo_measure[$section][$i_measure].", sum_tempo_measure = ".$sum_tempo_measure[$section][$i_measure].", tempo_this_measure = ".$metronome_this_measure."<br />"; // Suppressed by BB 2022-02-14
					if($metronome_this_measure == 0 OR abs(($metronome_this_measure - $final_metronome) / $metronome_this_measure) > 0.4) $warning = TRUE;
					else $warning = FALSE;
					$warning = FALSE; // Fixed by BB 2022-02-14
					if($warning AND $list_this) $report .= "<font color=\"red\">";
			//		if($list_this) $report .= "➡ Measure #".$i_measure." part [".$score_part."] physical time = ".round($max_physical_time,2)."s, average metronome of this measure = ".$metronome_this_measure.", final metronome = <font color=\"blue\">".$final_metronome."</font><br />";
					if($list_this OR $trace_tempo) $report .= "➡ Measure #".$i_measure." part [".$score_part."] physical time = ".round($max_physical_time,2)."s, final metronome = <font color=\"blue\">".$final_metronome."</font><br />"; // Fixed by BB 2022-02-14
					if($warning AND $list_this) $report .= "</font>";
					if($final_metronome > 0) $metronome_this_measure = $final_metronome; // Fixed by BB 2022-02-14
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
						$this_tempo = "_tempo(".$fraction.") ";
						if($fraction <> "1") $this_tempo = "_tempo(".$fraction.") ";
						else $this_tempo = '';
						$data = $d1.$this_tempo.$d2;
						$start_search = $pos2 + strlen($this_tempo) + 2;
				//		$default_tempo[$section] = $fraction; // Fixed by BB 2022-02-14
						if($super_trace) $report .= ">> metronome_this_measure = ".$metronome_this_measure.", this_metronome = ".$this_metronome.", measure #".$i_measure.", default_tempo[".$section."] = ".$default_tempo[$section]."<br />"; 
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
	if(isset($fifths[$score_part])) $current_fifths = $fifths[$score_part];
	else $current_fifths = 0;
	if($found_mordent OR $found_turn OR $found_trill OR $found_turn)
		$data = process_ornamentation($data,$current_fifths,$trace_ornamentations);
	$convert_score['data'] = $data;
	$convert_score['metronome_min'] = $metronome_min;
	$convert_score['metronome_max'] = $metronome_max;
	if($number_metronome > 0)
		$metronome_average = round($sum_metronome / $number_metronome);
	else $metronome_average = 0;
	$convert_score['metronome_average'] = $metronome_average;
	if($list_corrections OR $trace_measures OR $trace_tempo) $convert_score['report'] = $report;
	else $convert_score['report'] = '';
	if($number_metronome_markers > 0)
		echo "<p><font color=\"red\">➡</font> ".$number_metronome_markers." metronome markers have been found inside measures of this score</p>";
	else
		echo "<p><font color=\"red\">➡</font> No metronome marker has been found inside measures of this score</p>";
	return $convert_score;
	}

function add_note($stream,$i_measure,$the_event,$long_ornamentation,$diatonic_scale_string) {
	if(isset($the_event['ornament']) OR isset($the_event['turn'])) {
		if($the_event['chromatic']) $mode = "chromatic";
		else $mode = '';
		if(isset($the_event['turn'])) {
			$turn_beats = $the_event['turn-beats'];
			$turn = "turn"."=".$turn_beats;
			if(!isset($the_event['ornament'])) $the_event['ornament'] = "upper_mordent";
			}
		else $turn = '';
		if($the_event['ornament'] == "upper_mordent")
			$stream .= " ornament(".$diatonic_scale_string."_".$i_measure."_upper,".$mode.",".$turn.",".$long_ornamentation."|";
		if($the_event['ornament'] == "lower_mordent")
			$stream .= " ornament(".$diatonic_scale_string."_".$i_measure."_lower,".$mode.",".$turn.",".$long_ornamentation."|";
		if($the_event['ornament'] == "trill")
			$stream .= " ornament(".$diatonic_scale_string."_".$i_measure."_trill,".$mode.",,".$the_event['trill-beats']."|";
		}
	if(isset($the_event['slur'])) {
		if($the_event['slur'] == "start") $stream .= " _legato_ ";
		else if($the_event['slur'] == "stop") $stream .= " _nolegato_ ";
		}
	$stream .= $the_event['note'];
	if(isset($the_event['ornament'])) $stream .= ") ";
	else $stream .= " ";
	return $stream;
	}

function diatonic_scale($fifths) {
	// Construct diatonic scale with its alterations according to fifths
	global $notes_diesis,$notes_bemol,$standard_diatonic_scale;
	$diatonic_scale = array(0,2,4,5,7,9,11);
	if($fifths <> 0) {
		if($fifths > 0) $scale_pos = 3; // Start with F
		else $scale_pos = 6; // Start with B
		for($i = 0; $i < abs($fifths); $i++) {
			if($fifths > 0) { // diesis
				$diatonic_scale[$scale_pos]++;
				$scale_pos += 4;
				}
			else { // bemol
				$diatonic_scale[$scale_pos]--;
				$scale_pos += 3;
				}
			if($scale_pos > 6) $scale_pos -= 7;
			}
	/*	echo "scale = ";
		for($i = 0; $i < count($diatonic_scale); $i++) echo $notes_bemol[$diatonic_scale[$i]]." ";
		echo "<br />"; */
		}
	else $diatonic_scale = $standard_diatonic_scale;
	return $diatonic_scale;
	}

function adjust_scale($diatonic_scale,$altered_diatonic_scale,$note) {
	global $notes_diesis,$notes_bemol,$standard_diatonic_scale;
	$note_class = preg_replace("/(.+)[0-9]+/u","$1",$note);
	for($pitch_class = 0; $pitch_class < 12; $pitch_class++) {
		if($notes_diesis[$pitch_class] == $note_class) {
			$alteration = "diesis";
			$unaltered_pitch_class = $pitch_class - 1;
			break;
			}
		if($notes_bemol[$pitch_class] == $note_class) {
			$alteration = "bemol";
			$unaltered_pitch_class = $pitch_class + 1;
			break;
			}
		}
	if($pitch_class < 12) {
		for($i = 0; $i < count($altered_diatonic_scale); $i++) {
			if($altered_diatonic_scale[$i] == $pitch_class) return $altered_diatonic_scale;
			}
		for($i = 0; $i < count($diatonic_scale); $i++) {
			if($diatonic_scale[$i] == $pitch_class) {
				$altered_diatonic_scale[$i] = $diatonic_scale[$i];
				return $altered_diatonic_scale;
				}
			}
		for($i = 0; $i < count($standard_diatonic_scale); $i++) {
			if($standard_diatonic_scale[$i] == $pitch_class) {
				$altered_diatonic_scale[$i] = $standard_diatonic_scale[$i];
				return $altered_diatonic_scale;
				}
			}
		}
	return $altered_diatonic_scale;
	}

function process_ornamentation($data,$fifths,$trace_ornamentations) {
	global $notes_diesis,$notes_bemol;
	$start_search = 0;
	$tag = "ornament(";
	$tag_length = strlen($tag);
	while(is_integer($pos1=strpos($data,$tag,$start_search))) {
		if(!is_integer($pos2=strpos($data,")",$pos1 + $tag_length))) break;
		$old_expression = substr($data,$pos1 + $tag_length,$pos2 - $pos1 - $tag_length);
		$table = explode("_",$old_expression);
		$altered_diatonic_scale_string = $table[0];
		$i_measure = $table[1];
	//	$altered_diatonic_scale_string = trim(preg_replace("/(.+)\s.+/u","$1",$old_expression));
		if($trace_ornamentations) echo "Altered diatonic scale = (".$altered_diatonic_scale_string.") measure #".$i_measure."<br />";
		$diatonic_scale = explode(',',$altered_diatonic_scale_string);
	//	if($trace_ornamentations) echo $fifths." fifths, expression = “".$old_expression."”<br />";
		$note = trim(preg_replace("/.+\|(.+)/u","$1",$old_expression));
		if(is_integer(strpos($old_expression,"&"))) $link = "&";
		else $link = '';
		if(is_integer(strpos($old_expression,"upper"))) $direction = "up";
		else $direction = "down";
		$turn = is_integer(strpos($old_expression,"turn"));
		$chromatic = is_integer(strpos($old_expression,"chromatic"));
		$name_ornament = "Mordent ".$direction;
		$turn_beats = 0;
		if($turn) {
			$turn_beats = preg_replace("/.+turn=([0-9]+).+/u","$1",$old_expression);
			$name_ornament = "Turn ".$turn_beats." beats";
			}
		$trill = is_integer(strpos($old_expression,"trill"));
		if($trill) {
			$trill_beats = round(preg_replace("/.+([0-9]+)\|.+/u","$1",$old_expression));
			$direction = "up";
			$name_ornament = "Trill";
			}
		else $trill_beats = 3;
		$long = is_integer(strpos($old_expression,"yes"));
		if($long) $name_ornament .= " long";
		else $name_ornament .= " short";
		$new_expression = ornament($note,$long,$link,$diatonic_scale,$direction,$fifths,$trill,$trill_beats,$turn,$turn_beats,$chromatic,$trace_ornamentations);
		if($trace_ornamentations) {
			echo "<font color=\"blue\">".$name_ornament."</font>";
			if($chromatic) echo " (chromatic)";
			echo " = ".$new_expression."<br /><br />";
			}
		$d1 = substr($data,0,$pos1);
		$d2 = substr($data,$pos2 + 1,strlen($data) - $pos2 - 1);
		$data = $d1.$new_expression.$d2;
		$start_search = $pos1 + strlen($new_expression);
		}
	return $data;
	}

function ornament($note,$long,$link,$diatonic_scale,$direction,$fifths,$trill,$trill_beats,$turn,$turn_beats,$chromatic,$trace_ornamentations) {
	// Read https://bolprocessor.org/importing-musicxml/#ornaments
	global $notes_diesis,$notes_bemol;
	$legato = $nolegato = FALSE;
	if(is_integer(strpos($note,"_legato_"))) {
		$legato = TRUE;
		$note = trim(str_replace("_legato_",'',$note));
		}
	if(is_integer(strpos($note,"_nolegato_"))) {
		$nolegato = TRUE;
		$note = trim(str_replace("_nolegato_",'',$note));
		}
	$note = str_replace('&','',$note);
	$alt_note = $note2 = '';
	$note_class = preg_replace("/(.+)[0-9]+/u","$1",$note);
	for($pitch_class = 0; $pitch_class < 12; $pitch_class++) {
		if($notes_diesis[$pitch_class] == $note_class OR $notes_bemol[$pitch_class] == $note_class) break;
		}
	if($pitch_class > 12) {
		echo "<font color=red>➡</font> Incorrect pitch class for ".$note." in mordent ".$direction."<br />";
		$note2 = "???";
		}
	$octave = preg_replace("/.+([0-9]+)/u","$1",$note);
	if($trace_ornamentations) {
		echo "Note = ".$note;
		if($link) echo "&";
		echo " pitch_class = ".$pitch_class."<br />";
		}
	if($chromatic) {
		$lower_pitch_class = $pitch_class - 1;
		if($lower_pitch_class < 0) {
			$lower_pitch_class += 12; $octave--;
			}
		$alt_note = $notes_bemol[$lower_pitch_class];
		$note_down = $alt_note.$octave;
		if($alt_note <> '' AND $pitch_class == $lower_pitch_class) echo "<font color=red>➡</font> Error pitch class ".$pitch_class." not changed in ".$expression."<br />";
		$octave = preg_replace("/.+([0-9]+)/u","$1",$note);
		$higher_pitch_class = $pitch_class + 1;
		if($higher_pitch_class > 11) {
			$higher_pitch_class -= 12; $octave++;
			}
		$alt_note = $notes_diesis[$higher_pitch_class];
		$note_up = $alt_note.$octave;
		if($alt_note <> '' AND $pitch_class == $higher_pitch_class) echo "<font color=red>➡</font> Error pitch class ".$pitch_class." not changed in ".$expression."<br />";
		if($note2 <> "???") $note2 = $alt_note.$octave;
		}
	else {
		if($direction == "down" OR $turn) {
			$lower_pitch_class = $pitch_class - 1;
			if($lower_pitch_class < $diatonic_scale[0]) {
				$lower_pitch_class = $diatonic_scale[6]; $octave--;
				}
			if($trace_ornamentations) echo "lower_pitch_class = ".$lower_pitch_class."<br />";
			for($i = count($diatonic_scale) - 1; $i >= 0; $i--) {
				if($diatonic_scale[$i] <= $lower_pitch_class) {
					if($fifths >= 0) $alt_note = $notes_diesis[$diatonic_scale[$i]];
					else $alt_note = $notes_bemol[$diatonic_scale[$i]];
					if($turn) $note_down = $alt_note.$octave;
					break;
					}
				}
			if($note2 <> "???") $note2 = $alt_note.$octave;
			}
		if($alt_note <> '' AND $pitch_class == $diatonic_scale[$i]) echo "<font color=red>➡</font> Error pitch class ".$pitch_class." not changed in ".$expression."<br />";
		$octave = preg_replace("/.+([0-9]+)/u","$1",$note);
		if($direction == "up" OR $turn OR $trill) {
			$higher_pitch_class = $pitch_class + 1;
			if($higher_pitch_class > $diatonic_scale[6]) {
				$higher_pitch_class = 0; $octave++;
				}
			if($trace_ornamentations) echo "higher_pitch_class = ".$higher_pitch_class."<br />";
			for($i = 0; $i < count($diatonic_scale); $i++) {
				if($diatonic_scale[$i] >= $higher_pitch_class) {
					if($fifths >= 0) $alt_note = $notes_diesis[$diatonic_scale[$i]];
					else $alt_note = $notes_bemol[$diatonic_scale[$i]];
					if($turn) $note_up = $alt_note.$octave;
					break;
					}
				}
			if($note2 <> "???") $note2 = $alt_note.$octave;
			}
		if($alt_note <> '' AND $pitch_class == $diatonic_scale[$i]) echo "<font color=red>➡</font> Error pitch class ".$pitch_class." not changed in ".$expression."<br />";
		}
	if($turn) {
		// https://en.wikipedia.org/wiki/Ornament_(music)#Turn
		// https://www.w3.org/2021/06/musicxml40/musicxml-reference/elements/turn/
		// turn beats = the number of distinct notes during playback, counting the starting note but not the two-note turn. It is 4 if not specified.
		if($long) $dur = 2 + ($turn_beats - 4);
		else $dur = 1 + ($turn_beats - 4);
		if($direction == "up") {
			$expression = "{1,".$note."{".$dur.",".$note_up." ".$note." ".$note_down;
			$next_step = +1;
			}
		else {
			$expression = "{1,".$note."{".$dur.",".$note_down." ".$note." ".$note_up;
			$next_step = -1;
			}
		for($i = 4; $i < $turn_beats; $i++) {
			if($next_step > 0)
				$expression .= " ".$note." ".$note_up;
			else $expression .= " ".$note." ".$note_down;
			$next_step = - $next_step;
			}
		if($long) {
			$expression .= "} ".$note;
			}
		else $expression .= "} ".$note."_";
		$expression .= "}";
		}
	else if($trill) {
		// https://en.wikipedia.org/wiki/Trill_(music)
		// https://www.w3.org/2021/06/musicxml40/musicxml-reference/elements/trill-mark/
		// trill beats = the number of distinct notes during playback, counting the starting note but not the two-note turn. It is 3 if not specified.
		if($link == '') $trill_step = $note." ".$note2;
		else $trill_step = $note2." ".$note;
		$expression = "{1,".$trill_step;
		for($i = 0; $i < $trill_beats; $i++) $expression .= " ".$trill_step;
		$expression .= $link."}";
		}
	else { // Mordent
		// https://en.wikipedia.org/wiki/Mordent
		// https://www.w3.org/2021/06/musicxml40/musicxml-reference/elements/inverted-mordent/
		// https://www.w3.org/2021/06/musicxml40/musicxml-reference/elements/mordent/
		// https://bolprocessor.org/importing-musicxml/#mordents
		if($long)
			$expression = "{1/4,".$note2." ".$note." ".$note2."}{3/4,".$note.$link."}";
		else
			$expression = "{1/8,".$note." ".$note2."}{7/8,".$note.$link."}";
		}
	if($legato) $expression = " _legato_ ".$expression;
	if($nolegato) $expression = " _nolegato_ ".$expression;
	return $expression;
	}

function process_arpeggios($data,$score_divisions,$trace_ornamentations) {
	global $max_term_in_fraction;
//	return $data;
	$start_search = 0;
	$min_divisions = round($score_divisions / 20);
	if($min_divisions == 0) $min_divisions = 1;
	while(is_integer($pos1=strpos($data,"arpeggio",$start_search))) {
		if(!is_integer($pos2=strpos($data,"}",$pos1 + 8))) break;
		$old_expression = substr($data,$pos1 + 8,$pos2 - $pos1 - 7);
		$old_expression = str_replace(",}","}",$old_expression); // Not necessary but looks nicer when tracing
		$i_measure = preg_replace("/\(([^\)]+)\).+/u","$1",$old_expression); // Beware that i_measure might not be a number!
		// echo "old_expression = ".$old_expression." i_measure = ".$i_measure."<br />";
		$old_expression = str_replace("(".$i_measure.")",'',$old_expression);
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
		for($i = 0; $i < count($table); $i++) { // Fixed by BB 2022-02-10
			if(!$no_digit AND $i == 0) continue;
			$note = str_replace('{','',$table[$i]);
			$note = str_replace('}','',$note);
			// echo $note."<br />"; 
			$tied = "no";
			if(is_integer($pos=strpos($note,'&'))) {
				if($pos == 0) $tied = "before";
				else $tied = "after";
				if(substr_count($note,'&') > 1) $tied = "both";
				}
			$legato = is_integer(strpos($note,"_legato_"));
			$nolegato = is_integer(strpos($note,"_nolegato_"));
			$note = trim(str_replace("_legato_",'',$note));
			$note = trim(str_replace("_nolegato_",'',$note));
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
			if($legato) $new_expression1 .= "_legato_ ";
			if($nolegato) $new_expression1 .= "_nolegato_ ";
			$new_expression1 .= $note1." ";
			$new_expression2 .= $note2.",";
			}
		$new_expression1 .= "}";
		$new_expression2 .= "}";
		$new_expression = str_replace(" }","}",$new_expression1.$new_expression2);
		$new_expression = str_replace(",}","}",$new_expression);
		if($trace_ornamentations) echo "<font color=\"blue\">Arpeggio</font> = ".$new_expression." in measure #".$i_measure."<br /><br />";
		$d1 = substr($data,0,$pos1);
		$d2 = substr($data,$pos2 + 1,strlen($data) - $pos2 - 1);
		$data = $d1.$new_expression.$d2;
		$start_search = $pos1 + strlen($new_expression);
		}
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

function beat_divide($beat_unit) {
	$beat_divide['p'] = $beat_divide['q'] = 1;
	if(is_numeric($beat_unit)) {
		$beat_divide['p'] = 4;
		$beat_divide['q'] = $beat_unit;
		return $beat_divide;
		}
	$beat_unit_table = array("maxima","long","breve","whole","half","quarter","eighth","16th","32nd","64th","128th","256th","512th","1024th");
	for($i = 0; $i < 14; $i++) {
		if($beat_unit == $beat_unit_table[$i]) break;
		}
	if($i > 13) return $beat_divide;
	if($i > 4) {
		$beat_divide['q'] = pow(2,($i - 5)); // eighth -> 1/2
		$beat_divide['p'] = 1;
		}
	else {
		$beat_divide['p'] = pow(2,(5 - $i)); // half -> 2/1
		$beat_divide['q'] = 1;
		}
	return $beat_divide;
	}

function add_breath_to_stream_duration($p,$q,$div,$breath_length,$stream,$stream_units) {
	global $trace_breath,$max_term_in_fraction;
	$add = add($p,$q,($breath_length['p'] * $div),$breath_length['q']);
	$p_new = $add['p']; $q_new = $add['q'];
	$result['fraction'] = $p_new."/".($q_new * $div);
	$p_ratio = $stream_units * $q; // This is the necessary increase of breath rest
	$q_ratio = $p;
	$p_new_breath = $breath_length['p'] * $div * $p_ratio;
	$q_new_breath = $breath_length['q'] * $q_ratio;
	$fraction = $p_new_breath."/".$q_new_breath;
//	echo $stream."<br />";
//	echo $stream_units." ".$p_ratio."/".$q_ratio." ".$fraction."<br /><br />";
	$simplify = simplify($fraction,$max_term_in_fraction);
	$new_rest = $simplify['fraction'];
	$result['stream'] = str_replace("_rest",$new_rest,$stream);
	return $result;
	}
?>
