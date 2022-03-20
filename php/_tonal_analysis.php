<?php
// This code is inserted in data.php
$harmonic_third = round(cents(5/4));
$pythagorean_third = round(cents(81/64));
$wolf_fifth = round(cents(40/27));
$perfect_fifth = round(cents(3/2));
$perfect_fourth = round(cents(4/3));
$wolf_fourth = round(cents(320/243));

function tonal_analysis($content,$url_this_page,$csound_file,$temp_dir,$temp_folder,$note_convention) {
	global $max_term_in_fraction,$dir_scale_images,$csound_resources,$current_directory,$dir,$filename;
	global $p_tonal_default_up,$q_tonal_default_up,$p_tonal_default_down,$q_tonal_default_down,$p_tonal_default_harmonic,$q_tonal_default_harmonic,$weight_tonal_default_up,$weight_tonal_default_down,$weight_tonal_default_harmonic,$width_tonal_default_up,$width_tonal_default_down,$width_tonal_default_harmonic,$weight_tonal_melodic_up,$weight_tonal_melodic_down,$weight_tonal_harmonic,$max_distance_tonal,$ratio_melodic_tonal,$min_duration_tonal,$max_gap_tonal,$compare_scales_tonal;
	set_time_limit(1000);
	$test_tonal = FALSE;
	$test_intervals = TRUE;
	$display_items = FALSE;
	$max_marks = 20; $max_marks2 = $max_marks / 2;
	$position_melodic_mark = $position_harmonic_mark = $display_result = array();
	for($i_mark = 0; $i_mark < $max_marks; $i_mark++) {
		$p_default[$i_mark] = $q_default[$i_mark] = '';
		$weight_default[$i_mark] = 1;
		$width_default[$i_mark] = 10;
		}
	$p_default[0] = 3; $q_default[0] = 2; $weight_default[0] = 2; $width_default[0] = 10; // Perfect fifth
	$p_default[1] = 5; $q_default[1] = 4; $weight_default[1] = 1; $width_default[1] = 10; // HJarmonic major thirf
	$p_default[2] = 40; $q_default[2] = 27; $weight_default[2] = -2; $width_default[2] = 15; // Wolf fifth
	$p_default[3] = 320; $q_default[3] = 243; $weight_default[3] = -2; $width_default[3] = 15; // Wolf fourth
	$p_default[4] = 81; $q_default[4] = 64; $weight_default[4] = -1; $width_default[4] = 10; // Pythagorean major third
	$p_default[5] = 6; $q_default[5] = 5; $weight_default[5] = 1; $width_default[5] = 10; // Harmonic minor third
	$p_default[6] = 9; $q_default[6] = 8; $weight_default[6] = 1; $width_default[6] = 10; // Pythagorean major second
	$number_subfiles = substr_count($content,"-da.") + substr_count($content,".bpda");

	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input class=\"shadow\" style=\"float:right; font-size:large; background-color:azure;\" type=\"submit\" value=\"STOP ANALYSIS\">";
	echo "<div style=\"background-color:white; padding-left:1em;\">";
	if(isset($_POST['proceed_tonal_analysis']) AND !isset($_POST['save_tonal_settings']) AND !isset($_POST['reset_tonal_settings'])) echo "<input class=\"shadow\" style=\"background-color:yellow; font-size:large; float:left;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE AGAIN\"><br /><br />";
	else echo "<br /><p style=\"text-align:center;\">Check documentation: <a target=\"_blank\" href=\"https://bolprocessor.org/tonal-analysis/\">https://bolprocessor.org/tonal-analysis/</a></p>";
	echo "<center><table style=\"background-color:Gold;\">";
	echo "<tr><th colspan=\"6\" style=\"text-align:center;\">Intervals checked in the analysis</tr>";
	echo "<tr><td colspan=\"6\" style=\"white-space:nowrap; padding:6px; text-align:center;\">";
	echo "<i>Checking additional ratios such as the harmonic minor third (frequency ratio 6/5) may be advised</i>";
	echo "</td></tr>";
	echo "<tr><th colspan=\"2\" style=\"text-align:center;\">Ascending melodic</th><th colspan=\"2\" style=\"text-align:center;\">Descending melodic</th><th colspan=\"2\" style=\"text-align:center;\">Harmonic</th></tr>";
	echo "<tr>";
	echo "<td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	for($i_mark = 0; $i_mark < $max_marks2; $i_mark++) {
		if(isset($_POST["position_melodic_mark_up_p_".$i_mark]))
			$position_melodic_mark_up[$i_mark]['p'] = intval($_POST["position_melodic_mark_up_p_".$i_mark]);
		else if(isset($p_tonal_default_up[$current_directory][$i_mark])) $position_melodic_mark_up[$i_mark]['p'] = $p_tonal_default_up[$current_directory][$i_mark];
		else $position_melodic_mark_up[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_melodic_mark_up[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST["position_melodic_mark_up_q_".$i_mark]))
			$position_melodic_mark_up[$i_mark]['q'] = intval($_POST["position_melodic_mark_up_q_".$i_mark]);
		else if(isset($q_tonal_default_up[$current_directory][$i_mark])) $position_melodic_mark_up[$i_mark]['q'] = $q_tonal_default_up[$current_directory][$i_mark];
		else $position_melodic_mark_up[$i_mark]['q'] = $q_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_melodic_mark_up[$i_mark]['q'] = $q_default[$i_mark];
		if($position_melodic_mark_up[$i_mark]['p'] == 0)
			$position_melodic_mark_up[$i_mark]['p'] = $position_melodic_mark_up[$i_mark]['q'] = '';
		if($position_melodic_mark_up[$i_mark]['q'] == 0)
		$position_melodic_mark_up[$i_mark]['p'] = $position_melodic_mark_up[$i_mark]['q'] = '';
		echo "ratio <input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_melodic_mark_up_p_".$i_mark."\" size=\"3\" value=\"".$position_melodic_mark_up[$i_mark]['p']."\">/<input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_melodic_mark_up_q_".$i_mark."\" size=\"3\" value=\"".$position_melodic_mark_up[$i_mark]['q']."\">";
		if(isset($_POST["width_melodic_mark_up_".$i_mark]))
			$width_melodic_mark_up[$i_mark] = intval($_POST["width_melodic_mark_up_".$i_mark]);
		else if(isset($width_tonal_default_up[$current_directory][$i_mark])) $width_melodic_mark_up[$i_mark] = $width_tonal_default_up[$current_directory][$i_mark];
		else $width_melodic_mark_up[$i_mark] = $width_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $width_melodic_mark_up[$i_mark] = $width_default[$i_mark];
		echo "&nbsp;&nbsp;±<input type=\"text\" style=\"border:none; text-align:center;\" name=\"width_melodic_mark_up_".$i_mark."\" size=\"3\" value=\"".$width_melodic_mark_up[$i_mark]."\">¢";
		if(isset($_POST["weight_melodic_mark_up_".$i_mark]))
			$weight_melodic_mark_up[$i_mark] = intval($_POST["weight_melodic_mark_up_".$i_mark]);
		else if(isset($weight_tonal_default_up[$current_directory][$i_mark])) $weight_melodic_mark_up[$i_mark] = $weight_tonal_default_up[$current_directory][$i_mark];
		else $weight_melodic_mark_up[$i_mark] = $weight_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $weight_melodic_mark_up[$i_mark] = $weight_default[$i_mark];
		echo "&nbsp;&nbsp;weight <input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_melodic_mark_up_".$i_mark."\" size=\"3\" value=\"".$weight_melodic_mark_up[$i_mark]."\">";
		echo "<br />";
		}
	echo "</td>";
	echo "<td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	for($i_mark = 0; $i_mark < $max_marks2; $i_mark++) {
		if(isset($_POST["position_melodic_mark_down_p_".$i_mark]))
			$position_melodic_mark_down[$i_mark]['p'] = intval($_POST["position_melodic_mark_down_p_".$i_mark]);
		else if(isset($p_tonal_default_down[$current_directory][$i_mark])) $position_melodic_mark_down[$i_mark]['p'] = $p_tonal_default_down[$current_directory][$i_mark];
		else $position_melodic_mark_down[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_melodic_mark_down[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST["position_melodic_mark_down_q_".$i_mark]))
			$position_melodic_mark_down[$i_mark]['q'] = intval($_POST["position_melodic_mark_down_q_".$i_mark]);
		else if(isset($q_tonal_default_down[$current_directory][$i_mark])) $position_melodic_mark_down[$i_mark]['q'] = $q_tonal_default_down[$current_directory][$i_mark];
		else $position_melodic_mark_down[$i_mark]['q'] = $q_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_melodic_mark_down[$i_mark]['q'] = $q_default[$i_mark];
		if($position_melodic_mark_down[$i_mark]['p'] == 0)
			$position_melodic_mark_down[$i_mark]['p'] = $position_melodic_mark_down[$i_mark]['q'] = '';
		if($position_melodic_mark_down[$i_mark]['q'] == 0)
		$position_melodic_mark_down[$i_mark]['p'] = $position_melodic_mark_down[$i_mark]['q'] = '';
		echo "ratio <input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_melodic_mark_down_p_".$i_mark."\" size=\"3\" value=\"".$position_melodic_mark_down[$i_mark]['p']."\">/<input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_melodic_mark_down_q_".$i_mark."\" size=\"3\" value=\"".$position_melodic_mark_down[$i_mark]['q']."\">";
		if(isset($_POST["width_melodic_mark_down_".$i_mark]))
			$width_melodic_mark_down[$i_mark] = intval($_POST["width_melodic_mark_down_".$i_mark]);
		else if(isset($width_tonal_default_down[$current_directory][$i_mark])) $width_melodic_mark_down[$i_mark] = $width_tonal_default_down[$current_directory][$i_mark];
		else $width_melodic_mark_down[$i_mark] = $width_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $width_melodic_mark_down[$i_mark] = $width_default[$i_mark];
		echo "&nbsp;&nbsp;±<input type=\"text\" style=\"border:none; text-align:center;\" name=\"width_melodic_mark_down_".$i_mark."\" size=\"3\" value=\"".$width_melodic_mark_down[$i_mark]."\">¢";
		if(isset($_POST["weight_melodic_mark_down_".$i_mark]))
			$weight_melodic_mark_down[$i_mark] = intval($_POST["weight_melodic_mark_down_".$i_mark]);
		else if(isset($weight_tonal_default_down[$current_directory][$i_mark])) $weight_melodic_mark_down[$i_mark] = $weight_tonal_default_down[$current_directory][$i_mark];
		else $weight_melodic_mark_down[$i_mark] = $weight_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $weight_melodic_mark_down[$i_mark] = $weight_default[$i_mark];
		echo "&nbsp;&nbsp;weight <input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_melodic_mark_down_".$i_mark."\" size=\"3\" value=\"".$weight_melodic_mark_down[$i_mark]."\">";
		echo "<br />";
		}
	echo "</td>";
	$p_default[6] = $q_default[6] = ''; $weight_default[6] = 1;
	echo "<td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	for($i_mark = 0; $i_mark < $max_marks2; $i_mark++) {
		if(isset($_POST["position_harmonic_mark_p_".$i_mark]))
			$position_harmonic_mark[$i_mark]['p'] = intval($_POST["position_harmonic_mark_p_".$i_mark]);
		else if(isset($p_tonal_default_harmonic[$current_directory][$i_mark])) $position_harmonic_mark[$i_mark]['p'] = $p_tonal_default_harmonic[$current_directory][$i_mark];
		else $position_harmonic_mark[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_harmonic_mark[$i_mark]['p'] = $p_default[$i_mark];
		if(isset($_POST["position_harmonic_mark_q_".$i_mark]))
			$position_harmonic_mark[$i_mark]['q'] = intval($_POST["position_harmonic_mark_q_".$i_mark]);
		else if(isset($q_tonal_default_harmonic[$current_directory][$i_mark])) $position_harmonic_mark[$i_mark]['q'] = $q_tonal_default_harmonic[$current_directory][$i_mark];
		else $position_harmonic_mark[$i_mark]['q'] = $q_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $position_harmonic_mark[$i_mark]['q'] = $q_default[$i_mark];
		if($position_harmonic_mark[$i_mark]['p'] == 0)
			$position_harmonic_mark[$i_mark]['p'] = $position_harmonic_mark[$i_mark]['q'] = '';
		if($position_harmonic_mark[$i_mark]['q'] == 0)
		$position_harmonic_mark[$i_mark]['p'] = $position_harmonic_mark[$i_mark]['q'] = '';
		echo "ratio <input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_harmonic_mark_p_".$i_mark."\" size=\"3\" value=\"".$position_harmonic_mark[$i_mark]['p']."\">/<input type=\"text\" style=\"border:none; text-align:center;\" name=\"position_harmonic_mark_q_".$i_mark."\" size=\"3\" value=\"".$position_harmonic_mark[$i_mark]['q']."\">";
		if(isset($_POST["width_harmonic_mark_".$i_mark]))
			$width_harmonic_mark[$i_mark] = intval($_POST["width_harmonic_mark_".$i_mark]);
		else if(isset($width_tonal_default_harmonic[$current_directory][$i_mark])) $width_harmonic_mark[$i_mark] = $width_tonal_default_harmonic[$current_directory][$i_mark];
		else $width_harmonic_mark[$i_mark] = $width_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $width_harmonic_mark[$i_mark] = $width_default[$i_mark];
		echo "&nbsp;&nbsp;±<input type=\"text\" style=\"border:none; text-align:center;\" name=\"width_harmonic_mark_".$i_mark."\" size=\"3\" value=\"".$width_harmonic_mark[$i_mark]."\">¢";
		if(isset($_POST["weight_harmonic_mark_".$i_mark]))
			$weight_harmonic_mark[$i_mark] = intval($_POST["weight_harmonic_mark_".$i_mark]);
		else if(isset($weight_tonal_default_harmonic[$current_directory][$i_mark])) $weight_harmonic_mark[$i_mark] = $weight_tonal_default_harmonic[$current_directory][$i_mark];
		else $weight_harmonic_mark[$i_mark] = $weight_default[$i_mark];
		if(isset($_POST['reset_tonal_settings'])) $weight_harmonic_mark[$i_mark] = $weight_default[$i_mark];
		echo "&nbsp;&nbsp;weight <input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_harmonic_mark_".$i_mark."\" size=\"3\" value=\"".$weight_harmonic_mark[$i_mark]."\">";
		echo "<br />";
		}
	echo "</td></tr>";
	echo "<tr><td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	if(isset($_POST['weight_melodic_up']) AND is_numeric($_POST['weight_melodic_up']))
		$weight_melodic_up = abs(intval($_POST['weight_melodic_up']));
	else if(isset($weight_tonal_melodic_up[$current_directory])) $weight_melodic_up = $weight_tonal_melodic_up[$current_directory];
	else $weight_melodic_up = 1;
	if(isset($_POST['reset_tonal_settings'])) $weight_melodic_up = 1;
	echo "Global weight ascending intervals:<input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_melodic_up\" size=\"3\" value=\"".$weight_melodic_up."\">";
	echo "</td><td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	if(isset($_POST['weight_melodic_down']) AND is_numeric($_POST['weight_melodic_down']))
		$weight_melodic_down = abs(intval($_POST['weight_melodic_down']));
	else if(isset($weight_tonal_melodic_down[$current_directory])) $weight_melodic_down = $weight_tonal_melodic_down[$current_directory];
	else $weight_melodic_down = 1;
	if(isset($_POST['reset_tonal_settings'])) $weight_melodic_down = 1;
	echo "Global weight descending intervals:<input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_melodic_down\" size=\"3\" value=\"".$weight_melodic_down."\">";
	echo "</td><td colspan=\"2\" style=\"white-space:nowrap; padding:6px;\">";
	if(isset($_POST['weight_harmonic']) AND is_numeric($_POST['weight_harmonic']))
		$weight_harmonic = abs(intval($_POST['weight_harmonic']));
	else if(isset($weight_tonal_harmonic[$current_directory])) $weight_harmonic = $weight_tonal_harmonic[$current_directory];
	else $weight_harmonic = 2;
	if(isset($_POST['reset_tonal_settings'])) $weight_harmonic = 2;
	echo "Global weight harmonic intervals:<input type=\"text\" style=\"border:none; text-align:center;\" name=\"weight_harmonic\" size=\"3\" value=\"".$weight_harmonic."\">";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan=\"3\" style=\"white-space:nowrap; padding:6px;\">";
	if(isset($_POST['max_distance']) AND is_numeric($_POST['max_distance']))
		$max_distance = abs(intval($_POST['max_distance']));
	else if(isset($max_distance_tonal[$current_directory])) $max_distance = $max_distance_tonal[$current_directory];
	else $max_distance = 11;
	if(isset($_POST['reset_tonal_settings'])) $max_distance = 11;
	echo "Maximum size of melodic intervals:<input type=\"text\" style=\"border:none; text-align:center;\" name=\"max_distance\" size=\"3\" value=\"".$max_distance."\"> semitones";
	echo "</td>";
	echo "<td colspan=\"3\" style=\"white-space:nowrap; padding:6px;\">";
	if(isset($_POST['batch_processing']))
		$batch_processing = isset($_POST['batch_processing']);
	else $batch_processing = ($number_subfiles > 5);
	echo " Batch processing (many items or files)&nbsp;<input type=\"checkbox\" name=\"batch_processing\"";
	if($batch_processing) echo " checked";
	echo ">";
	echo "</td>";
	echo "</tr>";
	echo "<tr><td colspan=\"3\" style=\"white-space:nowrap; padding:6px; text-align:right;\">";
	if(isset($_POST['overlap'])) $ratio_melodic = intval($_POST['overlap']) / 100;
	else if(isset($ratio_melodic_tonal[$current_directory])) $ratio_melodic = $ratio_melodic_tonal[$current_directory];
	else $ratio_melodic = 0.25;
	if(isset($_POST['reset_tonal_settings'])) $ratio_melodic = 0.25;
	echo "Max overlap ratio in melodic intervals: <input type=\"text\" style=\"border:none; text-align:center;\" name=\"overlap\" size=\"3\" value=\"".(100 * $ratio_melodic)."\">%<br />";
	if(isset($_POST['min_duration'])) $min_duration = intval($_POST['min_duration']);
	else if(isset($min_duration_tonal[$current_directory])) $min_duration = $min_duration_tonal[$current_directory];
	else $min_duration = 500;
	if(isset($_POST['reset_tonal_settings'])) $min_duration = 500;
	echo "Min duration of harmonic interval: <input type=\"text\" style=\"border:none; text-align:center;\" name=\"min_duration\" size=\"4\" value=\"".$min_duration."\">ms<br />";
	if(isset($_POST['max_gap'])) $max_gap = intval($_POST['max_gap']);
	else if(isset($max_gap_tonal[$current_directory])) $max_gap = $max_gap_tonal[$current_directory];
	else $max_gap = 300;
	if(isset($_POST['reset_tonal_settings'])) $max_gap = 300;
	echo "Maximum gap in melodic interval: <input type=\"text\" style=\"border:none; text-align:center;\" name=\"max_gap\" size=\"4\" value=\"".$max_gap."\">ms<br />";
	echo "</td><td colspan=\"3\" style=\"white-space:nowrap; padding:6px; text-align:right;\">";
	$test_intervals = isset($_POST['test_intervals']);
	echo " Display all dates (may be long!)&nbsp;<input type=\"checkbox\" name=\"test_intervals\"";
	if($test_intervals) echo " checked";
	echo "><br />";
	$display_items = isset($_POST['display_items']);
	echo "Display sliced item(s)&nbsp;<input type=\"checkbox\" name=\"display_items\"";
	if($display_items) echo " checked";
	echo "><br />";
	$trace_critical_intervals = isset($_POST['trace_critical_intervals']);
	echo "Trace critical intervals&nbsp;<input type=\"checkbox\" name=\"trace_critical_intervals\"";
	if($trace_critical_intervals) echo " checked";
	echo ">";
	echo "</td></tr>";
	echo "<tr><td colspan=\"6\" style=\"white-space:nowrap; padding:4px;\">";
	if($csound_file <> '') {
		if(isset($_POST['compare_scales'])) $compare_scales = $_POST['compare_scales'];
		else if(isset($compare_scales_tonal[$current_directory])) $compare_scales = $compare_scales_tonal[$current_directory];
		else $compare_scales = 0;
		echo "<p><input type=\"radio\" name=\"compare_scales\" value=\"0\"";
		if(!$compare_scales) echo " checked";
		echo "> Analyze item(s) with their specified tonal scales<br />";
		echo "<input type=\"radio\" name=\"compare_scales\" value=\"1\"";
		if($compare_scales) echo " checked";
		echo "> Evaluate adequacy of all tuning schemes of ‘<font color=\"blue\">".$csound_file."</font>’ in terms of consonance</p>";
		}
	else {
		echo "<font color=\"red\">➡</font> You could also compare scales after declaring a ‘-cs’ resource file on top of data and open the file in the <a target=\"_blank\" href=\"csound.php?file=".urlencode($csound_resources.SLASH.$csound_file)."\">Csound resource folder</a>";
		$compare_scales = 0;
		}
	echo "</td></tr>";
	echo "<tr><td colspan=\"6\" style=\"white-space:nowrap; padding:4px;\">";
	echo "<input style=\"background-color:azure; float:left;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"\" name=\"reset_tonal_settings\" value=\"RESET SETTINGS TO DEFAULT\">";
	echo "<input style=\"background-color:azure; float:right;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"\" name=\"save_tonal_settings\" value=\"SAVE SETTINGS TO WORKSPACE “".$current_directory."”\">";
	echo "</td></tr>";
	echo "</table>";
	if(!isset($_POST['proceed_tonal_analysis']) OR  isset($_POST['save_tonal_settings']) OR isset($_POST['reset_tonal_settings'])) echo "<br /><input class=\"shadow\" style=\"background-color:yellow; font-size:large;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE ITEM(s)\">";
	echo "</center>";
	echo "<input type=\"hidden\" name=\"proceed_tonal_analysis\" value=\"ok\">";
	echo "</form>";
	// Create reverted intervals
	for($i_mark = 0; $i_mark < $max_marks2; $i_mark++) {
		$i_mark2 = $max_marks2 + $i_mark;
		$position_melodic_mark_up[$i_mark2]['p'] = $position_melodic_mark_up[$i_mark]['q'];
		$position_melodic_mark_up[$i_mark2]['q'] = $position_melodic_mark_up[$i_mark]['p'];
		$weight_melodic_mark_up[$i_mark2] = $weight_melodic_mark_up[$i_mark];
		$width_melodic_mark_up[$i_mark2] = $width_melodic_mark_up[$i_mark];
		$position_melodic_mark_down[$i_mark2]['p'] = $position_melodic_mark_down[$i_mark]['q'];
		$position_melodic_mark_down[$i_mark2]['q'] = $position_melodic_mark_down[$i_mark]['p'];
		$weight_melodic_mark_down[$i_mark2] = $weight_melodic_mark_down[$i_mark];
		$width_melodic_mark_down[$i_mark2] = $width_melodic_mark_down[$i_mark];
		$position_harmonic_mark[$i_mark2]['p'] = $position_harmonic_mark[$i_mark]['q'];
		$position_harmonic_mark[$i_mark2]['q'] = $position_harmonic_mark[$i_mark]['p'];
		$weight_harmonic_mark[$i_mark2] = $weight_harmonic_mark[$i_mark];
		$width_harmonic_mark[$i_mark2] = $width_harmonic_mark[$i_mark];
		}
	if(isset($_POST['save_tonal_settings'])) {
		// Store default values for this workspace
		for($i_mark = 0; $i_mark < $max_marks2; $i_mark++) {
			save_settings3("p_tonal_default_up",$current_directory,$i_mark,$position_melodic_mark_up[$i_mark]['p']);
			save_settings3("q_tonal_default_up",$current_directory,$i_mark,$position_melodic_mark_up[$i_mark]['q']);
			save_settings3("weight_tonal_default_up",$current_directory,$i_mark,$weight_melodic_mark_up[$i_mark]);
			save_settings3("width_tonal_default_up",$current_directory,$i_mark,$width_melodic_mark_up[$i_mark]);
			save_settings3("p_tonal_default_down",$current_directory,$i_mark,$position_melodic_mark_down[$i_mark]['p']);
			save_settings3("q_tonal_default_down",$current_directory,$i_mark,$position_melodic_mark_down[$i_mark]['q']);
			save_settings3("weight_tonal_default_down",$current_directory,$i_mark,$weight_melodic_mark_down[$i_mark]);
			save_settings3("width_tonal_default_down",$current_directory,$i_mark,$width_melodic_mark_down[$i_mark]);
			save_settings3("p_tonal_default_harmonic",$current_directory,$i_mark,$position_harmonic_mark[$i_mark]['p']);
			save_settings3("q_tonal_default_harmonic",$current_directory,$i_mark,$position_harmonic_mark[$i_mark]['q']);
			save_settings3("weight_tonal_default_harmonic",$current_directory,$i_mark,$weight_harmonic_mark[$i_mark]);
			save_settings3("width_tonal_default_harmonic",$current_directory,$i_mark,$width_harmonic_mark[$i_mark]);
			}
		save_settings2("weight_tonal_melodic_up",$current_directory,$weight_melodic_up);
		save_settings2("weight_tonal_melodic_down",$current_directory,$weight_melodic_down);
		save_settings2("weight_tonal_harmonic",$current_directory,$weight_harmonic);
		save_settings2("max_distance_tonal",$current_directory,$max_distance);
		save_settings2("ratio_melodic_tonal",$current_directory,$ratio_melodic);
		save_settings2("min_duration_tonal",$current_directory,$min_duration);
		save_settings2("max_gap_tonal",$current_directory,$max_gap);
		save_settings2("compare_scales_tonal",$current_directory,$compare_scales);
		}

	// Now we do it!
	if(isset($_POST['proceed_tonal_analysis']) AND !isset($_POST['save_tonal_settings']) AND !isset($_POST['reset_tonal_settings'])) {
		$time_start = time();
		echo "<br/>";
		$handle = NULL; $this_scale_score = array();
		$batch_filename = str_replace("-da.",'',$filename)."_batch.html";
		$batch_link = $temp_dir.$temp_folder.SLASH.$batch_filename;
		if($batch_processing AND $compare_scales) {
			$handle = fopen($batch_link,"w");
			$this_header = "<style>
			tr:nth-child(even) {
				background-color: rgba(150, 212, 212, 0.4);
			  }
			  th:nth-child(even),td:nth-child(even) {
				background-color: rgba(150, 212, 212, 0.4);
			  }
			 </style>";
			fwrite($handle,"<html>\n<header>\n".$this_header."</header>\n");
			fwrite($handle,"<body>\n");
			}
		$table = explode(chr(10),$content);
		$imax = count($table);
		$found = FALSE;
		$first_line = $first_line2 = '';
		for($i_line = $i_item = $i_batchline = 0; $i_line < $imax; $i_line++) {
			$first_line2 = '';
			$error_mssg = '';
			$item_name = '';
			$line = trim($table[$i_line]);
			if(is_integer($pos=strpos($line,"<?xml")) AND $pos == 0) break;
			if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
				if($first_line == '') $first_line = $line;
				continue;
				}
			if(is_integer($pos=strpos($line,"-")) AND $pos == 0) {
				$line2 = str_replace("-da:",'',$line);
				$line3 = str_replace(".bpda",'',$line2);
				if((is_integer($pos=strpos($line2,"-da.")) AND $pos == 0) OR (is_integer($pos=strpos($line2,".bpda")) AND !is_integer(strpos($line3,".")))) {
					// This is a data file
					$found = FALSE;
					$data_file = $dir.$line2;
					$first_line2 = '';
					if(file_exists($data_file)) {
						echo "<font color=\"red\">➡</font> Item in file “<font color=\"blue\">".$data_file."</font>”<br />";
						$table3 = explode('/',$data_file);
						$item_name = end($table3);
						$content2 = @file_get_contents($data_file,TRUE);
						$extract_data = extract_data(TRUE,$content2);
						$newcontent = $extract_data['content'];
						$table2 = explode(chr(10),$newcontent);
						$imax2 = count($table2);
						for($i_line2 = 0; $i_line2 < $imax2; $i_line2++) {
							$line2 = trim($table2[$i_line2]);
							if(is_integer($pos=strpos($line2,"<?xml")) AND $pos == 0) break;
							if(is_integer($pos=strpos($line2,"//")) AND $pos == 0) {
								if($first_line2 == '') $first_line2 = $line2;
								continue;
								}
							if(is_integer($pos=strpos($line2,"-")) AND $pos == 0) continue;
							if(!is_integer(strpos($line2,"{"))) continue;
							$line = $line2;
							$found = TRUE;
							break;
							}
						}
					}
				if(!$found) continue;
				}
			$segment = create_chunks($line,$i_item,$temp_dir,$temp_folder,1,0,"slice");;
			if($segment['error'] == "break") break;
			if($segment['error'] == "continue") continue;
			$tonal_scale = $segment['tonal_scale'];
			$i_item++;
			if($first_line <> '' AND $first_line2 == '') {
				echo "➡ <i>This item =</i> <font color=\"blue\">".str_replace("// ",'',$first_line)."</font><br />";
				}
			echo "<p><b>Item #".$i_item."</b>";
			$batch_item[$i_batchline] = $i_item;
			$batch_item_name[$i_batchline++] = $item_name;
			if($item_name <> '') echo " <font color=\"red\">".$item_name."</font>";
			if($tonal_scale <> '') echo " assigned scale <font color=\"red\">".$tonal_scale."</font>";
			if(!$compare_scales) echo " — note convention is ‘<font color=\"red\">".ucfirst(note_convention(intval($note_convention)))."</font>’";
			echo "</p>";
			if($segment['data_chunked'] == '') {
				echo "This item is in a syntax that this version of tonal analysis does not support.<br />";
				continue;
				}
			if($first_line2 <> '') {
				echo "<p style=\"\"><font color=\"blue\">".$first_line2."</font></p>";
				}
			if($tonal_scale <> '' AND !$compare_scales) echo "<p>Checking against tonal scale ‘<font color=\"blue\">".$tonal_scale."</font>’ defined in the <a target=\"_blank\" href=\"index.php?path=csound_resources\">Csound resource</a> folder</p>";
			$tie_mssg = $segment['tie_mssg'];
			$data_chunked = $segment['data_chunked']; 
			$content_slice = @file_get_contents($data_chunked,TRUE);
			$table_slice = explode("[slice]",$content_slice);
			$i_slice_max = count($table_slice);
			if($i_slice_max == 0) continue;
			if($display_items AND $i_slice_max > 2) echo "<p>➡ Item has been sliced to speed up calculations</p>";
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
				$slice_test = $slice;
				if($display_items) echo $slice_test."<br /><br />";
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
			if(!$compare_scales) {
				echo "<center><table style=\"background-color:Gold;\">";
				echo "<tr><th colspan=\"3\" style=\"text-align:center;\">Numbers are proportional to total durations</th></tr>";
				echo "<tr><td style=\"padding:6px;\">Melodic intervals (up)</td><td style=\"padding:6px;\">Melodic intervals (down)</td><td style=\"padding:6px;\">Harmonic intervals</td></tr>";
				echo "<tr><td>";
				}
			$direction = "up";
			$mode = "melodic";
			$match_notes = match_notes($table_events,$mode,$direction,$min_duration,$max_distance,$max_gap,$ratio_melodic,$test_intervals,$lcm);
			$matching_notes = $match_notes['matching_notes'];
			$number_match = $match_notes['max_match'];
			usort($matching_notes,"score_sort");
			if($number_match > 0) {
				$max_score = $matching_notes[0]['score'];
				for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
					if($max_score > 0)
						$matching_notes[$i_match]['percent'] = round($matching_notes[$i_match]['score'] * 100 / $max_score);
					else $matching_notes[$i_match]['percent'] = 0;
					if(!$compare_scales) echo $matching_notes[$i_match][0]." ▹
					".$matching_notes[$i_match][1]." (".round($matching_notes[$i_match]['score']/$lcm,1).")<br />";
					}
				}
			$matching_list[$i_item][$mode][$direction] = $matching_notes;
			if(!$compare_scales) echo "</td><td>";
			$direction = "down";
			$mode = "melodic";
			$match_notes = match_notes($table_events,$mode,$direction,$min_duration,$max_distance,$max_gap,$ratio_melodic,$test_intervals,$lcm);
			$matching_notes = $match_notes['matching_notes'];
			$number_match = $match_notes['max_match'];
			usort($matching_notes,"score_sort");
			if($number_match > 0) {
				$max_score = $matching_notes[0]['score'];
				for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
					if($max_score > 0)
						$matching_notes[$i_match]['percent'] = round($matching_notes[$i_match]['score'] * 100 / $max_score);
					else $matching_notes[$i_match]['percent'] = 0;
					if(!$compare_scales) echo $matching_notes[$i_match][0]." ▹
					".$matching_notes[$i_match][1]." (".round($matching_notes[$i_match]['score']/$lcm,1).")<br />";
					}
				}
			$matching_list[$i_item][$mode][$direction] = $matching_notes;
			if(!$compare_scales) echo "</td><td>";
			$direction = "both";
			$mode = "harmonic";
			$match_notes = match_notes($table_events,$mode,$direction,$min_duration,$max_distance,$max_gap,$ratio_melodic,$test_intervals,$lcm);
			$matching_notes = $match_notes['matching_notes'];
			$number_match = $match_notes['max_match'];
			usort($matching_notes,"score_sort");
			if($number_match > 0) {
				if(!$compare_scales) echo "Duration:<br />";
				$max_score = $matching_notes[0]['score'];
				for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
					if($max_score > 0)
						$matching_notes[$i_match]['percent'] = round($matching_notes[$i_match]['score'] * 100 / $max_score);
					else $matching_notes[$i_match]['percent'] = 0;
					if(!$compare_scales) echo $matching_notes[$i_match][0]." ≈
					".$matching_notes[$i_match][1]." (".round($matching_notes[$i_match]['score']/$lcm,1).")<br />";
					}
				}
			$matching_list[$i_item][$mode][$direction] = $matching_notes;
			if(!$compare_scales) echo "</td></tr></table></center><br />";
			if($compare_scales) {
				// Comparing scales
				$found_scale = $image_shown = FALSE;
				$grand_total = $j_batch = 0;
				$scale_known = $resource_file_known = array();
				$dircontent = scandir($temp_dir);
				foreach($dircontent as $resource_file) {
					if(!is_dir($temp_dir.$resource_file)) continue;
					if(!is_integer($pos=strpos($resource_file,$csound_file)) OR $pos > 0) continue;
					if(is_integer($pos=strpos($resource_file,"-cs")) AND $pos == 0) {
						$table_name = explode('_',$resource_file);
						$resource_name = $table_name[0];
						if(isset($resource_file_known[$resource_name])) continue;
						$resource_file_known[$resource_name] = TRUE;
						$scale_folder = $temp_dir.$resource_file.SLASH."scales".SLASH;
						$dir_scales = scandir($scale_folder);
						foreach($dir_scales as $scale_textfile) {
							if(!is_integer($pos=strpos($scale_textfile,".txt"))) continue;
							$scale_name = str_replace(".txt",'',$scale_textfile);
							if(isset($scale_known[$scale_name])) continue;
							$found_scale = TRUE;
							$good_scale = FALSE;
							$content_scale = @file_get_contents($scale_folder.$scale_textfile,TRUE);
							$table_scale = explode(chr(10),$content_scale);
							// Find real name of scale, e.g. "F#maj" in file "F_maj.txt"
							$new_name = str_replace('"','',trim($table_scale[0]));
							if($new_name <> '') $scale_name = $new_name;
							$ratio = array();
							for($i_scale_line = 0; $i_scale_line < count($table_scale); $i_scale_line++) {
								$scale_line = trim($table_scale[$i_scale_line]);
								if(is_integer($pos=strpos($scale_line,"f")) AND $pos == 0) {
									$table_scale2 = explode(" ",$scale_line);
									if(count($table_scale2) < 21) break;
									$numgrades = $table_scale2[4];
									if($numgrades <> 12) break;
									else {
										for($grade = 0; $grade < 13; $grade++) {
											if(!isset($ratio[$grade])) $ratio[$grade] = $table_scale2[8 + $grade];
											}
										$good_scale = TRUE;
										$scale_known[$scale_name] = TRUE;
										}
									}
								if(is_integer($pos=strpos($scale_line,"[")) AND $pos == 0) {
									$scale_line = str_replace("[",'',$scale_line);
									$scale_line = str_replace("]",'',$scale_line);
									$table_scale2 = explode(" ",$scale_line);
									for($grade = 0; $grade < 13; $grade++) {
										$p[$grade] = $table_scale2[0 + (2 * $grade)];
										$q[$grade] = $table_scale2[1 + (2 * $grade)];
										if($q[$grade] > 0) $ratio[$grade] = $p[$grade] / $q[$grade];
										}
									}
								if(is_integer($pos=strpos($scale_line,"/")) AND $pos == 0) {
									$scale_line = str_replace("/",'',$scale_line);
									$table_scale2 = explode(" ",$scale_line);
									for($grade = 0; $grade < 13; $grade++) {
										if(!isset($note_name[$grade])) {
											$this_note = $table_scale2[$grade];
											$this_position = note_position($this_note);
											if($this_position >= 0) {
												if($note_convention == 0) $this_note = $Englishnote[$this_position];
												else if($note_convention == 1) $this_note = $Frenchnote[$this_position];
												else $this_note = $Indiannote[$this_position];
												}
											$note_name[$grade] = $this_note;
											}
										}
									}
								}
							if(!$good_scale) continue;
							$column_name[$j_batch] = $scale_name;
							$scale_batch[$scale_name] = $j_batch;
							$j_batch++;
							$mode = "melodic";
							$direction = "up";
							$evaluate_scale = evaluate_scale($i_item,$scale_name,$mode,$direction,$ratio,$matching_list,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$weight_melodic_mark_up,$width_melodic_mark_up,$weight_melodic_mark_down,$width_melodic_mark_down,$weight_harmonic_mark,$width_harmonic_mark,$trace_critical_intervals);
							$score_melodic_up = $evaluate_scale['score'];
							$value_match[$i_item][$scale_name][$mode][$direction] = $evaluate_scale['value_match'];
							$direction = "down";
							$evaluate_scale = evaluate_scale($i_item,$scale_name,$mode,$direction,$ratio,$matching_list,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$weight_melodic_mark_up,$width_melodic_mark_up,$weight_melodic_mark_down,$width_melodic_mark_down,$weight_harmonic_mark,$width_harmonic_mark,$trace_critical_intervals);
							$score_melodic_down = $evaluate_scale['score'];
							$value_match[$i_item][$scale_name][$mode][$direction] = $evaluate_scale['value_match'];
							$mode = "harmonic";
							$direction = "both";
							$evaluate_scale = evaluate_scale($i_item,$scale_name,$mode,$direction,$ratio,$matching_list,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$weight_melodic_mark_up,$width_melodic_mark_up,$weight_melodic_mark_down,$width_melodic_mark_down,$weight_harmonic_mark,$width_harmonic_mark,$trace_critical_intervals);
							$score_harmonic = $evaluate_scale['score'];
							$value_match[$i_item][$scale_name][$mode][$direction] = $evaluate_scale['value_match'];
							$evaluate['melodic_up'][$scale_name] = round($score_melodic_up);
							$evaluate['melodic_down'][$scale_name] = round($score_melodic_down);
							$evaluate['harmonic'][$scale_name] = round($score_harmonic);
							$total_score[$scale_name] = $weight_melodic_up * $score_melodic_up + $weight_melodic_down * $score_melodic_down + $weight_harmonic * $score_harmonic;
							$grand_total += $total_score[$scale_name];
							}
						arsort($total_score);
						if($grand_total > 0) {
							$i_rank = $old_total = $i_line2 = 0;
							echo "<center><table style=\"background-color:Gold;\">";
							if(!$batch_processing) {
								echo "<tr><td colspan=\"3\" style=\"background-color:Gold;\"><div class=\"shadow\" style=\"background-color:azure;padding:6px; border-radius: 6px;\"><i>Numbers are proportional to durations<br />of intervals matching the scale</i></div></td><th>&nbsp;Melodic score (up)&nbsp;</th><th>&nbsp;Melodic score (down)&nbsp;</th><th>&nbsp;Harmonic score&nbsp;</th><th></th></tr>";
								echo "<th></th><th>Select</th><th style=\"text-align:right;\">weight:&nbsp;</th><td>".$weight_melodic_up."</td><td>".$weight_melodic_down."</td><td>".$weight_harmonic."</td><th>Weighted total</th></tr>";
								}
							else echo "<tr><th></th><th>Select then</th><th><input style=\"background-color:yellow;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE AGAIN\"></th><th>&nbsp;Melodic score (up)&nbsp;</th><th>&nbsp;Melodic score (down)&nbsp;</th><th>&nbsp;Harmonic score&nbsp;</th><th>Score</th></tr>";
							$found_scale = FALSE; $display_ok = TRUE;
							foreach($total_score as $scale => $total) {
								if($total == 0) continue;
								$i_line2++;
								if($batch_processing AND $tonal_scale == '' AND ($i_rank > 2 OR $i_line2 > 10)) $display_ok = FALSE;
								$same_rank = FALSE;
								if($old_total <> round($total/$lcm)) $i_rank++;
								else $same_rank = TRUE;
								if($display_ok) {
									echo "<tr>";
									if($same_rank) echo "<td style=\"background-color:Gold; text-align:left;\">".$i_rank."</td>";
									else echo "<th>".$i_rank."</th>";
									echo "<td>";
									}
								$rank[$i_item][$scale] = $i_rank;
								if(!isset($this_scale_score[$scale])) $this_scale_score[$scale] = 0;
								if($i_rank == 1) $this_scale_score[$scale]++;
								if($scale == $tonal_scale OR $tonal_scale == '') $found_scale = TRUE;
								$old_total = round($total/$lcm);
								if($display_ok) {
									$display_result[$i_item][$scale] = isset($_POST['display_result_'.$i_item."_".$scale]);
									echo "<input type=\"checkbox\" name=\"display_result_".$i_item."_".$scale."\"";
									if($display_result[$i_item][$scale]) echo " checked";
									echo ">";
									if($scale == $tonal_scale) echo "<a title=\"This scale is declared in data\">&nbsp;&nbsp;<small>💛</small>&nbsp;&nbsp;</a>";
									echo "</td>";
									$clean_name_of_file = str_replace("#","_",$scale);
									$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
									$scale_link = $dir_scale_images.$clean_name_of_file.".png";
									echo "<td>";
									if(file_exists($scale_link))
										echo "<a onclick=\"window.open('".$scale_link."','".$clean_name_of_file."_image','width=800,height=657,left=100'); return false;\" href=\"".$scale_link."\">".$scale."</a>";
									else echo $scale;
									echo "</td><td>".round($evaluate['melodic_up'][$scale]/$lcm,0)."</td><td>".round($weight_melodic_down * $evaluate['melodic_down'][$scale]/$lcm,0)."</td><td>".round($evaluate['harmonic'][$scale]/$lcm,0)."</td><td>".$old_total."</td>";
									echo "</tr>";
									}
								if($batch_processing AND $found_scale AND $i_rank > 2) $display_ok = FALSE;
								}
							if(!$batch_processing) echo "<tr><td colspan=\"6\">&nbsp;<font color=\"red\"><b>↑</b></font>&nbsp;&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" formaction=\"".$url_this_page."#tonalanalysis\" title=\"Analyze tonal intervals\" name=\"analyze_tonal\" value=\"ANALYZE AGAIN\"> for a graphic display of results on selected scales</td></tr>";
							echo "</table></center><br />";
							}
						else echo "<p style=\"text-align:center;\"><font color=\"red\">No matching tonal scale was found.</font><br />";
						}
					if($found_scale AND isset($display_result[$i_item])) {
						foreach($display_result[$i_item] as $tonal_scale => $this_result) {
							if(!$this_result) continue;
							$mode = "harmonic";
							$direction = "both";
							$result = show_relations_on_image($i_item,$matching_list,$mode,$direction,$tonal_scale,$note_convention,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$value_match,FALSE);
							$mode = "melodic";
							$direction = "both";
							$result = show_relations_on_image($i_item,$matching_list,$mode,$direction,$tonal_scale,$note_convention,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$value_match,FALSE);
							echo "<br />";
							$image_shown = TRUE;
							}
						}
					}
				if(!$found_scale AND $image_shown) {
					echo "<p style=\"text-align:center;\"><font color=\"red\">No definition of tonal scale was found.</font><br />";
					echo "You need to <a target=\"_blank\" href=\"csound.php?file=".urlencode($csound_resources.SLASH.$csound_file)."\">open</a> the ‘<font color=\"blue\">".$csound_file."</font>’ Csound resource file to use its tonal scale definitions.</p><br />";
					}
				}
			else {
				$value_match = array();
				$mode = "harmonic";
				$direction = "both";
				$result = show_relations_on_image($i_item,$matching_list,$mode,$direction,$tonal_scale,$note_convention,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$value_match,TRUE);
				$mode = "melodic";
				$direction = "both";
				$result = show_relations_on_image($i_item,$matching_list,$mode,$direction,$tonal_scale,$note_convention,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$value_match,TRUE);
				$scalename = $result['scalename'];
				$resource_name = $result['resource_name'];
				if($scalename == '' OR $resource_name == '')
					echo "<div style=\"padding:12px; text-align:center;\">No tonal scale specified.<br />Images display<br />equal-tempered scale.</div><br />";
				else 
					echo "<div style=\"padding:12px; text-align:center;\">Tonal scale<br />‘<font color=\"blue\">".$scalename."</font>’<br />was found in<br />a temporary folder<br />of ‘<font color=\"blue\">".$resource_name."</font>’</div>";
				}
			echo "<hr>";
			}
		if($handle <> NULL) {
			fwrite($handle,"<table><tr>\n");
			$download_link = "<a href=\"".$batch_filename."\" download=\"".$batch_filename."\"><input style=\"background-color:azure; border-radius: 10px;\" type=\"submit\" value=\"DOWNLOAD\"></a>";
			fwrite($handle,"<th>".$download_link."</th>\n");
			$best_score = 0;
			for($j_batch = 0; $j_batch < count($column_name); $j_batch++) {
				if($this_scale_score[$column_name[$j_batch]] > $best_score) {
					$best_score = $this_scale_score[$column_name[$j_batch]];
					}
				}
			for($j_batch = 0; $j_batch < count($column_name); $j_batch++) {
				$column_text = $column_name[$j_batch];
				if($this_scale_score[$column_text] == $best_score) $column_text = "<font color=\"red\">".$column_text."</font>";
				fwrite($handle,"<th style=\"writing-mode:vertical-lr; text-orientation:mixed; text-align:right;\">".$column_text."</th>\n");
				}
			fwrite($handle,"</tr>\n");
			for($i_batch = 0; $i_batch < count($batch_item); $i_batch++) {
				fwrite($handle,"<tr>\n");
				if($batch_item_name[$i_batch] <> '') $line_title = $batch_item_name[$i_batch];
				else $line_title = "#".$batch_item[$i_batch];
				fwrite($handle,"<td style=\"white-space:nowrap; color:blue;\">".$line_title."</td>");
				for($j_batch = 0; $j_batch < count($column_name); $j_batch++) {
					if(isset($rank[$batch_item[$i_batch]][$column_name[$j_batch]])) {
						$this_score = $rank[$batch_item[$i_batch]][$column_name[$j_batch]];
						if($this_score == 1) $this_score = "<font color=\"red\"><b>".$this_score."</b></font>";
						fwrite($handle,"<td>".$this_score."</td>\n");
						}
					else fwrite($handle,"<th></th>\n");
					}
				fwrite($handle,"</tr>\n");
				}
			fwrite($handle,"<tr>\n");
			fwrite($handle,"<td style=\"white-space:nowrap; color:red;\">Matched (times)</td>");
			for($j_batch = 0; $j_batch < count($column_name); $j_batch++) {
				$column_text = $this_scale_score[$column_name[$j_batch]];
				if($column_text == $best_score) $column_text = "<font color=\"red\"><b>".$column_text."</b></font>";
				fwrite($handle,"<td style=\"\">".$column_text."</td>\n");
				}
			fwrite($handle,"</tr>\n");
			fwrite($handle,"</table>\n");
			fwrite($handle,"</body></html>\n");
			fclose($handle);
			echo "<p style=\"text-align:center;\"><input class=\"shadow\" style=\"background-color:Azure; font-size:large;\" onclick=\"window.open('".$batch_link."','batch','width=1200,height=500,left=0'); return false;\" type=\"submit\" name=\"produce\" value=\"SHOW ALL RESULTS\"></p>";
			}
		echo "<p style=\"text-align:center;\">Check documentation: <a target=\"_blank\" href=\"https://bolprocessor.org/tonal-analysis/\">https://bolprocessor.org/tonal-analysis/</a></p>";
		$duration_process = time() - $time_start;
		if($duration_process > 2) echo "<p style=\"text-align:center; color:red;\"><small>All data processed in ".$duration_process." seconds</small></p>";
		echo "<br /></div>";
		}
	return;
	}


function list_events($slice,$poly,$max_poly,$level_init,$i_token_init,$p_tempo,$q_tempo,$p_abs_time_init,$q_abs_time_init,$i_layer,$current_legato) {
	set_time_limit(1000);
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
			$p_tempo = $p_newtempo;
			$q_tempo = $q_newtempo;
			$simplify = simplify($p_tempo * $p_tempo_deft."/".$q_tempo * $q_tempo_deft,$max_term_in_fraction);
			$p_tempo = $simplify['p'];
			$q_tempo = $simplify['q'];
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
		$poly[$i_poly]['grade'][$j_token] = $grade;
		$poly[$i_poly]['octave'][$j_token] = $octave;
		$j_token++;
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
			$table[$i]['grade'] = $poly[$i_poly]['grade'][$j_token];
			$table[$i]['octave'] = $poly[$i_poly]['octave'][$j_token];
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

function match_notes($table_events,$mode,$direction,$min_duration,$max_distance,$max_gap,$ratio_melodic,$test_intervals,$lcm) {
	$matching_notes = $match = array();
	$i_match = 0;
	$min_d = $min_duration * $lcm / 1000;
	$max_g = $max_gap * $lcm / 1000;
	if($test_intervals) echo "Dates in seconds:<br />";
	for($i_event = 0; $i_event < (count($table_events) - 1); $i_event++) {
		$start1 = $table_events[$i_event]['start'];
		$end1 = $table_events[$i_event]['end'];
		$grade1 = $table_events[$i_event]['grade'];
		$octave1 = $table_events[$i_event]['octave'];
		$position1 = $grade1 + 12 * $octave1;
		for($j_event = ($i_event + 1); $j_event < count($table_events); $j_event++) {
			$found = FALSE;
			$start2 = $table_events[$j_event]['start'];
			$end2 = $table_events[$j_event]['end'];
			$grade2 = $table_events[$j_event]['grade'];
			$octave2 = $table_events[$j_event]['octave'];
			$position2 = $grade2 + 12 * $octave2;
			if($position2 == $position1) continue;
			if($mode == "melodic") {
				if($direction == "up" AND $position2 < $position1) continue;
				if($direction == "down" AND $position1 < $position2) continue;
				if(abs($position1 - $position2) > $max_distance) continue;
				}
			if(matching_intervals($start1,$end1,$start2,$end2,$min_d,$max_g,$ratio_melodic,$mode,$direction,$lcm)) {
				$token1 = preg_replace("/([a-z A-Z #]+)[0-9]*/u","$1",$table_events[$i_event]['token']);
				$token2 = preg_replace("/([a-z A-Z #]+)[0-9]*/u","$1",$table_events[$j_event]['token']);
				if($token1 == $token2) continue;
				if(!isset($match[$token1][$token2]) AND !isset($match[$token2][$token1])) {
					$match[$token1][$token2] = $found = TRUE;
					$matching_notes[$i_match][0] = $token1;
					$matching_notes[$i_match][1] = $token2;
					if($mode == "melodic") $matching_notes[$i_match]['score'] = $end2 - $start1;
					else $matching_notes[$i_match]['score'] = $end1 - $start2;
					$i_match++;
					}
				else {
					for($j_match = 0; $j_match < $i_match; $j_match++) {
						if(($matching_notes[$j_match][0] == $token1) AND ($matching_notes[$j_match][1] == $token2)) {
							if($mode == "melodic") $matching_notes[$j_match]['score'] += $end2 - $start1;
							else $matching_notes[$j_match]['score'] += $end1 - $start2;
							$match[$token1][$token2] = $found = TRUE;
							}
						if(($matching_notes[$j_match][0] == $token2) AND ($matching_notes[$j_match][1] == $token1)) {
							if($mode == "melodic") $matching_notes[$j_match]['score'] += $end2 - $start1;
							else $matching_notes[$j_match]['score'] += $end1 - $start2;
							$match[$token2][$token1] = $found = TRUE;
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

function matching_intervals($start1,$end1,$start2,$end2,$min_d,$max_g,$ratio_melodic,$mode,$direction,$lcm) {
	// Because of the sorting of events, $start2 >= $start1
	$duration1 = $end1 - $start1;
	$duration2 = $end2 - $start2;
	$overlap = $end1 - $start2;
	$smallest_duration = $duration1;
	if($duration2 < $duration1) $smallest_duration = $duration2;
	if($mode == "harmonic") {
		if($smallest_duration < $min_d) return FALSE;
		if($start1 + ($duration1 / 2.) < $start2) return FALSE;
		if($overlap < ((1 - $ratio_melodic) * $smallest_duration)) return FALSE;
		// Here we discard slurs (generally 20% when importing MusicXML files)
		}
	else { // "melodic"
		if($start2 > ($end1 + $max_g)) return FALSE;
		if($start1 + ($duration1 / 2.) >= $start2) return FALSE;
		if($overlap >= ($ratio_melodic * $smallest_duration)) return FALSE;
		}
	return TRUE;
	}


function show_relations_on_image($i_item,$matching_list,$mode,$direction,$scalename,$note_convention,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$value_match,$float) {
	global $dir_scale_images,$temp_dir,$temp_folder,$dir_scale_images;
	global $Englishnote,$Frenchnote,$Indiannote;
	global $harmonic_third,$pythagorean_third,$wolf_fifth,$wolf_fourth,$perfect_fifth,$perfect_fourth;
	$clean_name_of_file = str_replace("#","_",$scalename);
	$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
	$save_codes_dir = $temp_dir.$temp_folder.SLASH.$clean_name_of_file."_codes_".$mode."_".$i_item.SLASH;
	$thick_max = 8; $thick = array();
	if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
	if($mode == "harmonic") {
		$direction = "both";
		$matching_notes[$direction] = $matching_list[$i_item][$mode][$direction];
		for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
			if($matching_notes[$direction][$i_match]['percent'] < 6) $thick[$mode][$direction][$i_match] = 0;
			else $thick[$mode][$direction][$i_match] = 6 + round((($matching_notes[$direction][$i_match]['percent'] * $thick_max) / 100));
			if(isset($value_match[$i_item][$scalename][$mode][$direction][$i_match]) AND $value_match[$i_item][$scalename][$mode][$direction][$i_match] < 1) $thick[$mode][$direction][$i_match] = 0;
			$position[$direction][$i_match][0] = note_position($matching_notes[$direction][$i_match][0]);
			$position[$direction][$i_match][1] = note_position($matching_notes[$direction][$i_match][1]);
			// We'll use note names of the score:
			$note_name[$position[$direction][$i_match][0]] = $matching_notes[$direction][$i_match][0];
			$note_name[$position[$direction][$i_match][1]] = $matching_notes[$direction][$i_match][1];
			}
		}
	else { // melodic
		$direction = "up";
		$matching_notes[$direction] = $matching_list[$i_item][$mode][$direction];
		for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
			if($matching_notes[$direction][$i_match]['percent'] < 6) $thick[$mode][$direction][$i_match] = 0;
			else $thick[$mode][$direction][$i_match] = 6 + round((($matching_notes[$direction][$i_match]['percent'] * $thick_max) / 100));
			if(isset($value_match[$i_item][$scalename][$mode][$direction][$i_match]) AND $value_match[$i_item][$scalename][$mode][$direction][$i_match] < 1) $thick[$mode][$direction][$i_match] = 0;
			$position[$direction][$i_match][0] = note_position($matching_notes[$direction][$i_match][0]);
			$position[$direction][$i_match][1] = note_position($matching_notes[$direction][$i_match][1]);
			// We'll use note names of the score:
			$note_name[$position[$direction][$i_match][0]] = $matching_notes[$direction][$i_match][0];
			$note_name[$position[$direction][$i_match][1]] = $matching_notes[$direction][$i_match][1];
			}
		$direction = "down";
		$matching_notes[$direction] = $matching_list[$i_item][$mode][$direction];
		for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
			if($matching_notes[$direction][$i_match]['percent'] < 6) $thick[$mode][$direction][$i_match] = 0;
			else $thick[$mode][$direction][$i_match] = 6 + round((($matching_notes[$direction][$i_match]['percent'] * $thick_max) / 100));
			if(isset($value_match[$i_item][$scalename][$mode][$direction][$i_match]) AND $value_match[$i_item][$scalename][$mode][$direction][$i_match] < 1) $thick[$mode][$direction][$i_match] = 0;
			$position[$direction][$i_match][0] = note_position($matching_notes[$direction][$i_match][0]);
			$position[$direction][$i_match][1] = note_position($matching_notes[$direction][$i_match][1]);
			// We'll use note names of the score:
			$note_name[$position[$direction][$i_match][0]] = $matching_notes[$direction][$i_match][0];
			$note_name[$position[$direction][$i_match][1]] = $matching_notes[$direction][$i_match][1];
			}
		}
	for($i_mark = 0; $i_mark < count($position_melodic_mark_up); $i_mark++) {
		if(intval($position_melodic_mark_up[$i_mark]['p']) < 1) continue; 
		if($position_melodic_mark_up[$i_mark]['q'] == 0) {
			if($mode == "harmonic") echo "<p style=\"text-align:center; color:red;\">Error marking additional position at ratio ".$position_melodic_mark_up[$i_mark]['p']."/".$position_melodic_mark_up[$i_mark]['q']."</p>";
			continue;
			}
		$cent_mark_melodic_up[$i_mark] = round(cents($position_melodic_mark_up[$i_mark]['p'] / $position_melodic_mark_up[$i_mark]['q']));
		}
	for($i_mark = 0; $i_mark < count($position_melodic_mark_down); $i_mark++) {
		if(intval($position_melodic_mark_down[$i_mark]['p']) < 1) continue; 
		if($position_melodic_mark_down[$i_mark]['q'] == 0) {
			if($mode == "harmonic") echo "<p style=\"text-align:center; color:red;\">Error marking additional position at ratio ".$position_melodic_mark_down[$i_mark]['p']."/".$position_melodic_mark_down[$i_mark]['q']."</p>";
			continue;
			}
		$cent_mark_melodic_down[$i_mark] = round(cents($position_melodic_mark_down[$i_mark]['p'] / $position_melodic_mark_down[$i_mark]['q']));
		}
	for($i_mark = 0; $i_mark < count($position_harmonic_mark); $i_mark++) {
		if(intval($position_harmonic_mark[$i_mark]['p']) < 1) continue; 
		if($position_harmonic_mark[$i_mark]['q'] == 0) {
			if($mode == "harmonic") echo "<p style=\"text-align:center; color:red;\">Error marking additional position at ratio ".$position_harmonic_mark[$i_mark]['p']."/".$position_harmonic_mark[$i_mark]['q']."</p>";
			continue;
			}
		$cent_mark_harmonic[$i_mark] = round(cents($position_harmonic_mark[$i_mark]['p'] / $position_harmonic_mark[$i_mark]['q']));
		}
	$found = FALSE;
	if($scalename <> '') {
		$dircontent = scandir($temp_dir);
		foreach($dircontent as $resource_file) {
			if(!is_dir($temp_dir.$resource_file)) continue;
			if(is_integer($pos=strpos($resource_file,"-cs")) AND $pos == 0) {
				$scale_textfile = $temp_dir.$resource_file.SLASH."scales".SLASH.$clean_name_of_file.".txt";
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
	$ratio = array();
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if(is_integer($pos=strpos($line,"f")) AND $pos == 0) {
			$table2 = explode(" ",$line);
			if(count($table2) < 21) {
				echo "<font color=\"red\">Definition of tonal scale</font> ‘<font color=\"blue\">".$scalename."</font>’ <font color=\"red\">is not compliant.</font><br />";
				echo "➡ Check ‘<font color=\"blue\">".$scale_textfile."</font>’ in the opened Csound resource.<br />";
				break;
				}
			$numgrades = $table2[4];
			if($numgrades <> 12) break;
			else {
				for($grade = 0; $grade < 13; $grade++)
					if(!isset($ratio[$grade])) $ratio[$grade] = $table2[8 + $grade];
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
				if($q[$grade] > 0) $ratio[$grade] = $p[$grade] / $q[$grade];
				}
			}
		if(is_integer($pos=strpos($line,"/")) AND $pos == 0) {
			$line = str_replace(SLASH,'',$line);
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
		$add_mark = array();
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
		$content .= "§harmonic_third = \"".$harmonic_third."\";\n";
		$content .= "§pythagorean_third = \"".$pythagorean_third."\";\n";
		$content .= "§wolf_fifth = \"".$wolf_fifth."\";\n";
		$content .= "§wolf_fourth = \"".$wolf_fourth."\";\n";
		$content .= "§perfect_fifth = \"".$perfect_fifth."\";\n";
		for($j = 0; $j < 12; $j++) {
			for($k = 0; $k < 12; $k++) {
				if($j == $k) continue;
				$pos = round(cents($ratio[$k] / $ratio[$j]));
				while($pos < 0) $pos += 1200;
				while($pos >= 1200) $pos -= 1200;
				$dist = abs($pos - $harmonic_third);
				if($dist > 600) $dist = 1200 - $dist;
				if($dist < 10) $content .= "§harmthird[".$j."] = \"".$k."\";\n";
				$dist = abs($pos - $pythagorean_third);
				if($dist > 600) $dist = 1200 - $dist;
				if($dist < 10) $content .= "§pyththird[".$j."] = \"".$k."\";\n";
				$dist = abs($pos - $perfect_fifth);
				if($dist > 600) $dist = 1200 - $dist;
				if($dist < 10) $content .= "§fifth[".$j."] = \"".$k."\";\n";
				$dist = abs($pos - $wolf_fifth);
				if($dist > 600) $dist = 1200 - $dist;
				if($dist < 15) $content .= "§wolffifth[".$j."] = \"".$k."\";\n";
				$dist = abs($pos - $wolf_fourth);
				if($dist > 600) $dist = 1200 - $dist;
				if($dist < 15) $content .= "§wolffourth[".$j."] = \"".$k."\";\n";
				}
			}
		$j_mark = 0;
		if($mode == "melodic") {
			$done = array();
			for($i_mark = 0; $i_mark < count($position_melodic_mark_up); $i_mark++) {
				if(intval($position_melodic_mark_up[$i_mark]['p']) < 1) continue; 
				for($j = 0; $j < 12; $j++) {
					for($k = 0; $k < 12; $k++) {
						if($j == $k) continue;
						$pos = round(cents($ratio[$k] / $ratio[$j]));
						while($pos < 0) $pos += 1200;
						while($pos >= 1200) $pos -= 1200;
						if(special_position($pos) > 0) continue;
						$done[$j][$k] = TRUE;
						$dist = abs($pos - $cent_mark_melodic_up[$i_mark]);
						if($dist < 10) {
							$content .= "§mark[".$j_mark."]['j'] = \"".$j."\";\n";
							$content .= "§mark[".$j_mark."]['k'] = \"".$k."\";\n";
							$add_mark[$pos] = TRUE;
							$j_mark++;
							}
						}
					}
				}
			for($i_mark = 0; $i_mark < count($position_melodic_mark_down); $i_mark++) {
				if(intval($position_melodic_mark_down[$i_mark]['p']) < 1) continue; 
				for($j = 0; $j < 12; $j++) {
					for($k = 0; $k < 12; $k++) {
						if($j == $k) continue;
						if(isset($done[$j][$k])) continue;
						$pos = round(cents($ratio[$k] / $ratio[$j]));
						while($pos < 0) $pos += 1200;
						while($pos >= 1200) $pos -= 1200;
						if(special_position($pos) > 0) continue;
						$done[$j][$k] = TRUE;
						$dist = abs($pos - $cent_mark_melodic_down[$i_mark]);
						if($dist < 10) {
							$content .= "§mark[".$j_mark."]['j'] = \"".$j."\";\n";
							$content .= "§mark[".$j_mark."]['k'] = \"".$k."\";\n";
							$add_mark[$pos] = TRUE;
							$j_mark++;
							}
						}
					}
				}
			}
		else { // harmonic
			for($i_mark = 0; $i_mark < count($position_harmonic_mark); $i_mark++) {
				if(intval($position_harmonic_mark[$i_mark]['p']) < 1) continue; 
				for($j = 0; $j < 12; $j++) {
					for($k = 0; $k < 12; $k++) {
						if($j == $k) continue;
						$pos = round(cents($ratio[$k] / $ratio[$j]));
						while($pos < 0) $pos += 1200;
						while($pos >= 1200) $pos -= 1200;
						if(special_position($pos) > 0) continue;
						$dist = abs($pos - $cent_mark_harmonic[$i_mark]);
						if($dist < 10) {
							$content .= "§mark[".$j_mark."]['j'] = \"".$j."\";\n";
							$content .= "§mark[".$j_mark."]['k'] = \"".$k."\";\n";
							$add_mark[$pos] = TRUE;
							$j_mark++;
							}
						}
					}
				}
			}
		// Create yellow links between matching notes
		$h = 0;
		if($mode == "harmonic") {
			$direction = "both";
			for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
				$w = $thick[$mode][$direction][$i_match];
				if($w < 1) continue;
				$j = $position[$direction][$i_match][0];
				$k = $position[$direction][$i_match][1];
				$content .= "§hilitej[".$h."] = \"".$j."\";\n";
				$content .= "§hilitek[".$h."] = \"".$k."\";\n";
				$content .= "§hilitewidth[".$h."] = \"".$w."\";\n";
				$h++;
				}
			}
		else { // melodic
			$direction = "up";
			for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
				$w = $thick[$mode][$direction][$i_match];
				if($w < 1) continue;
				$j = $position[$direction][$i_match][0];
				$k = $position[$direction][$i_match][1];
				$content .= "§hilitej[".$h."] = \"".$j."\";\n";
				$content .= "§hilitek[".$h."] = \"".$k."\";\n";
				$content .= "§hilitewidth[".$h."] = \"".$w."\";\n";
				$h++;
				}
			$direction = "down";
			for($i_match = 0; $i_match < count($matching_notes[$direction]); $i_match++) {
				$w = $thick[$mode][$direction][$i_match];
				if($w < 1) continue;
				$j = $position[$direction][$i_match][0];
				$k = $position[$direction][$i_match][1];
				$content .= "§hilitej[".$h."] = \"".$j."\";\n";
				$content .= "§hilitek[".$h."] = \"".$k."\";\n";
				$content .= "§hilitewidth[".$h."] = \"".$w."\";\n";
				$h++;
				}
			}
		$image_name = $i_item."_".clean_folder_name($scalename)."_image_".$mode;
		$image_name_full = $image_name."_full";
		$image_name_reduced = $image_name."_reduced";
		$image_name_only = $image_name."_only";
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
		echo "<div class=\"shadow\" style=\"border:2px solid gray; background-color:azure; width:15em; padding:8px; text-align:center; border-radius: 6px;";
		if($float OR $mode == "harmonic") echo " float:".$side.";";
		echo "\">SHOW IMAGE (".$mode.")<br />";
		if($scalename <> '') echo "‘<font color=\"red\">".$scalename."</font>’<br />";
		echo "<a onclick=\"window.open('".$link_full."','".$image_name_full."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_full."\">full</a>";
		echo "&nbsp;-&nbsp;<a onclick=\"window.open('".$link_only."','".$image_name_only."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_only."\">only scale</a>";
		echo "&nbsp;-&nbsp;<a onclick=\"window.open('".$link_reduced."','".$image_name_reduced."','width=".$image_width.",height=".$image_height.",left=".$left_position."'); return false;\" href=\"".$link_reduced."\">only links</a>";
		$said = FALSE;
		// $done = array();
		$h = 0;
		if($mode == "melodic") {
			for($i_mark = 0; $i_mark < count($position_melodic_mark_up)/2; $i_mark++) {
				if(intval($position_melodic_mark_up[$i_mark]['p']) < 1) continue;
				$pos = round(cents($position_melodic_mark_up[$i_mark]['p'] / $position_melodic_mark_up[$i_mark]['q']));
				if(isset($done[$pos])) continue;
				if(special_position($pos) > 0) continue;
				$dist = abs($pos - $cent_mark_melodic_up[$i_mark]);
				if(!isset($add_mark[$pos]) AND $dist > 10) continue;
				$done[$pos] = TRUE;
				if(!$said) echo "<br />Add positions (black):";
				else echo " -";
				$said = TRUE;
				echo " ".$position_melodic_mark_up[$i_mark]['p']."/".$position_melodic_mark_up[$i_mark]['q'];
				$content .= "§p_addpos[".$h."] = \"".$position_melodic_mark_up[$i_mark]['p']."\";\n";
				$content .= "§q_addpos[".$h."] = \"".$position_melodic_mark_up[$i_mark]['q']."\";\n";
				$h++;
				}
			for($i_mark = 0; $i_mark < count($position_melodic_mark_down)/2; $i_mark++) {
				if(intval($position_melodic_mark_down[$i_mark]['p']) < 1) continue;
				$pos = round(cents($position_melodic_mark_down[$i_mark]['p'] / $position_melodic_mark_down[$i_mark]['q']));
				if(isset($done[$pos])) continue;
				if(special_position($pos) > 0) continue;
				$dist = abs($pos - $cent_mark_melodic_down[$i_mark]);
				if(!isset($add_mark[$pos]) AND $dist > 10) continue;
				$done[$pos] = TRUE;
				if(!$said) echo "<br />Add positions (black):";
				else echo " -";
				$said = TRUE;
				echo " ".$position_melodic_mark_down[$i_mark]['p']."/".$position_melodic_mark_down[$i_mark]['q'];
				$content .= "§p_addpos[".$h."] = \"".$position_melodic_mark_down[$i_mark]['p']."\";\n";
				$content .= "§q_addpos[".$h."] = \"".$position_melodic_mark_down[$i_mark]['q']."\";\n";
				$h++;
				}
			if(!$said) echo "<br />&nbsp;";
			}
		else { // harmonic
			for($i_mark = 0; $i_mark < count($position_harmonic_mark)/2; $i_mark++) {
				if(intval($position_harmonic_mark[$i_mark]['p']) < 1) continue;
				$pos = round(cents($position_harmonic_mark[$i_mark]['p'] / $position_harmonic_mark[$i_mark]['q']));
				if(isset($done[$pos])) continue;
				if(special_position($pos) > 0) continue;
				$dist = abs($pos - $cent_mark_harmonic[$i_mark]);
				if(!isset($add_mark[$pos]) AND $dist > 10) continue;
				$done[$pos] = TRUE;
				if(!$said) echo "<br />Add positions (black):";
				else echo " -";
				$said = TRUE;
				echo " ".$position_harmonic_mark[$i_mark]['p']."/".$position_harmonic_mark[$i_mark]['q'];
				$content .= "§p_addpos[".$h."] = \"".$position_harmonic_mark[$i_mark]['p']."\";\n";
				$content .= "§q_addpos[".$h."] = \"".$position_harmonic_mark[$i_mark]['q']."\";\n";
				$h++;
				}
			if(!$said) echo "<br />&nbsp;";
			}
		echo "</div>";
		$content = str_replace('§','$',$content);
		fwrite($handle,$content);
		$line = "§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	$result['scalename'] = $scalename;
	$table = explode('_',$resource_file);
	$resource_name = $table[0];
	$result['resource_name'] = $resource_name;
	return $result;
	}

function special_position($pos) {
	global $harmonic_third,$pythagorean_third,$wolf_fifth,$wolf_fourth,$perfect_fifth,$perfect_fourth;
	// The order is important because close positions must be identified first
	while($pos < 0) $pos += 1200;
	while($pos >= 1200) $pos -= 1200;
	$dist = abs($pos - $harmonic_third);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 10) return $harmonic_third;
	$dist = abs($pos - $pythagorean_third);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 10) return $pythagorean_third;
	$dist = abs($pos - $perfect_fifth);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 10) return $perfect_fifth;
	$dist = abs($pos - $perfect_fourth);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 10) return $perfect_fourth;
	$dist = abs($pos - $wolf_fifth);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 15) return $wolf_fifth;
	$dist = abs($pos - $wolf_fourth);
	while($dist >= 1200) $dist -= 1200;
	if($dist > 600) $dist = 1200 - $dist;
	if($dist < 15) return $wolf_fourth;
	return 0;
	}

function evaluate_scale($i_item,$scale_name,$mode,$direction,$ratio,$matching_list,$position_melodic_mark_up,$position_melodic_mark_down,$position_harmonic_mark,$weight_melodic_mark_up,$width_melodic_mark_up,$weight_melodic_mark_down,$width_melodic_mark_down,$weight_harmonic_mark,$width_harmonic_mark,$trace_critical_intervals) {
	global $harmonic_third,$pythagorean_third,$wolf_fifth,$wolf_fourth,$perfect_fifth,$perfect_fourth,$Englishnote,$dir_scale_images;
//	echo "count = ".count($position_harmonic_mark)."<br />";
	$score = 0;
	$value = $value_match = array();
	$clean_name_of_file = str_replace("#","_",$scale_name);
	$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
	$scale_link = $dir_scale_images.$clean_name_of_file.".png";
	$scale_link = "<a onclick=\"window.open('".$scale_link."','".$clean_name_of_file."_image','width=800,height=657,left=100'); return false;\" href=\"".$scale_link."\">".$scale_name."</a>";
	for($i_mark = 0; $i_mark < count($position_melodic_mark_up); $i_mark++) {
		if(intval($position_melodic_mark_up[$i_mark]['p']) < 1) continue;
		$cent_mark_melodic_up[$i_mark] = round(cents($position_melodic_mark_up[$i_mark]['p'] / $position_melodic_mark_up[$i_mark]['q']));
		while($cent_mark_melodic_up[$i_mark] < 0) $cent_mark_melodic_up[$i_mark] += 1200;
		}
	for($i_mark = 0; $i_mark < count($position_melodic_mark_down); $i_mark++) {
		if(intval($position_melodic_mark_down[$i_mark]['p']) < 1) continue;
		$cent_mark_melodic_down[$i_mark] = round(cents($position_melodic_mark_down[$i_mark]['p'] / $position_melodic_mark_down[$i_mark]['q']));
		while($cent_mark_melodic_down[$i_mark] < 0) $cent_mark_melodic_down[$i_mark] += 1200;
		}
	for($i_mark = 0; $i_mark < count($position_harmonic_mark); $i_mark++) {
		if(intval($position_harmonic_mark[$i_mark]['p']) < 1) continue;
		$cent_mark_harmonic[$i_mark] = round(cents($position_harmonic_mark[$i_mark]['p'] / $position_harmonic_mark[$i_mark]['q']));
		while($cent_mark_harmonic[$i_mark] < 0) $cent_mark_harmonic[$i_mark] += 1200;
		}
	$distance = array();
	if($mode == "melodic") {
		if($direction == "up") {
			for($i_mark = 0; $i_mark < count($position_melodic_mark_up); $i_mark++) {
				if(intval($position_melodic_mark_up[$i_mark]['p']) < 1) continue; 
				for($j = 0; $j < 12; $j++) {
					for($k = 0; $k < 12; $k++) {
						if($j == $k) continue;
						$pos = round(cents($ratio[$k] / $ratio[$j]));
						while($pos < 0) $pos += 1200;
						while($pos >= 1200) $pos -= 1200;
						if(special_position($pos) > 0) $pos = special_position($pos);
						$dist = round(abs($pos - $cent_mark_melodic_up[$i_mark]),0);
						while($dist >= 1200) $dist -= 1200;
						if($dist > 600) $dist = 1200 - $dist;
				//		if($scale_name == "Abmin" OR $scale_name == "Emaj") echo $scale_name." ".$j." ".$k." ".$position_melodic_mark_up[$i_mark]['p']."/".$position_melodic_mark_up[$i_mark]['q']." pos = ".$pos." cent_mark_melodic_up = ".$cent_mark_melodic_up[$i_mark]." dist = ".$dist."<br />";
						if($dist > $width_melodic_mark_up[$i_mark]) continue;
				//		if($scale_name == "Abmin" OR $scale_name == "Emaj") echo $scale_name." ok! ".$j." ".$k." matched, pos = ".$pos."<br />";
						if($trace_critical_intervals AND isset($distance[$j][$k]) AND round($distance[$j][$k]) < round($dist)) {
							echo "Ignored ascending interval matching ‘".$scale_link."’: ".$position_melodic_mark_up[$i_mark]['p']."/".$position_melodic_mark_up[$i_mark]['q']." ".$Englishnote[$j]." to ".$Englishnote[$k]." as ".$distance[$j][$k]."¢ < ".$dist."¢<br />";
							continue;
							}
						$distance[$j][$k] = $dist;
						$value[$j][$k] = $weight_melodic_mark_up[$i_mark];
			/*			if($value[$j][$k] <= 0)
							echo "Negative weight ascending in ‘".$scale_link."’: ".$Englishnote[$j]." to ".$Englishnote[$k]." within ".round($dist)."¢ weight = ".$value[$j][$k];
							echo "<br />";
							 */
						}
					}
				}
			}
		$distance = array();
		if($direction == "down") {
			for($i_mark = 0; $i_mark < count($position_melodic_mark_down); $i_mark++) {
				if(intval($position_melodic_mark_down[$i_mark]['p']) < 1) continue; 
				for($j = 0; $j < 12; $j++) {
					for($k = 0; $k < 12; $k++) {
						if($j == $k) continue;
						$pos = round(cents($ratio[$k] / $ratio[$j]));
						while($pos < 0) $pos += 1200;
						while($pos >= 1200) $pos -= 1200;
						if(special_position($pos) > 0) $pos = special_position($pos);
						$dist = round(abs($pos - $cent_mark_melodic_up[$i_mark]),0);
						while($dist >= 1200) $dist -= 1200;
						if($dist > 600) $dist = 1200 - $dist;
						if($dist > $width_melodic_mark_down[$i_mark]) continue;
						if($trace_critical_intervals AND isset($distance[$j][$k]) AND round($distance[$j][$k]) < round($dist)) {
							echo "Ignored descending interval matching ‘".$scale_link."’: ".$position_melodic_mark_down[$i_mark]['p']."/".$position_melodic_mark_down[$i_mark]['q']." ".$Englishnote[$j]." to ".$Englishnote[$k]." as ".$distance[$j][$k]."¢ < ".$dist."¢<br />";
							continue;
							}
						$distance[$j][$k] = $dist;
						$value[$j][$k] = $weight_melodic_mark_down[$i_mark];
				/*		if($value[$j][$k] <= 0)
							echo "Negative weight descending in ‘".$scale_link."’: ".$Englishnote[$j]." to ".$Englishnote[$k]." within ".round($dist)."¢ weight = ".$value[$j][$k]."<br />"; */
						}
					}
				}
			}
		}
	else { // harmonic
		for($i_mark = 0; $i_mark < count($position_harmonic_mark); $i_mark++) {
			if(intval($position_harmonic_mark[$i_mark]['p']) < 1) continue; 
			for($j = 0; $j < 12; $j++) {
				for($k = 0; $k < 12; $k++) {
					if($j == $k) continue;
					$pos = round(cents($ratio[$k] / $ratio[$j]));
					while($pos < 0) $pos += 1200;
					while($pos >= 1200) $pos -= 1200;
					if(special_position($pos) > 0) $pos = special_position($pos);
					$dist = round(abs($pos - $cent_mark_harmonic[$i_mark]),0);
					while($dist >= 1200) $dist -= 1200;
					if($dist > 600) $dist = 1200 - $dist;
			//		if($scale_name == "meantone_rameau_en_do") echo $scale_name." ".$j." ".$k." ".$position_harmonic_mark[$i_mark]['p']."/".$position_harmonic_mark[$i_mark]['q']." pos = ".$pos." cent_harmonic = ".$cent_mark_harmonic[$i_mark]." dist = ".$dist."<br />";
					if($dist > $width_harmonic_mark[$i_mark]) continue;
			//		if($scale_name == "meantone_rameau_en_do") echo $scale_name." ok! ".$j." ".$k." matched, ".$position_harmonic_mark[$i_mark]['p']."/".$position_harmonic_mark[$i_mark]['q']." i_mark = ".$i_mark." pos = ".$pos."<br />";
					if($trace_critical_intervals AND isset($distance[$j][$k]) AND round($distance[$j][$k]) < round($dist)) {
						echo "Ignored harmonic interval matching ‘".$scale_link."’: ".$position_harmonic_mark[$i_mark]['p']."/".$position_harmonic_mark[$i_mark]['q']." ".$Englishnote[$j]." to ".$Englishnote[$k]." as ".$distance[$j][$k]."¢ < ".$dist."¢<br />";
						continue;
						}
					$distance[$j][$k] = $dist;
					$value[$j][$k] = $weight_harmonic_mark[$i_mark];
			/*		if($value[$j][$k] <= 0)
						echo "Negative weight harmonic in ‘".$scale_link."’: ".$Englishnote[$j]." to ".$Englishnote[$k]." within ".round($dist)."¢ weight = ".$value[$j][$k]."<br />"; */
					}
				}
			}
		}
	$matching_notes = $matching_list[$i_item][$mode][$direction];
	$n = 1;
	for($i_match = 0; $i_match < count($matching_notes); $i_match++) {
		$j = note_position($matching_notes[$i_match][0]);
		$k = note_position($matching_notes[$i_match][1]);
		if(isset($value[$j][$k])) {
			$score += $value[$j][$k] * $matching_notes[$i_match]['score'];
			$value_match[$i_match] = abs($value[$j][$k]);
			$pos = round(cents($ratio[$k] / $ratio[$j]));
			while($pos < 0) $pos += 1200;
			while($pos >= 1200) $pos -= 1200;
	//		if(($scale_name == "meantone_rameau_en_do") AND $mode == "harmonic" AND $direction == "both") echo $n++."] ".$j." ".$k." ".$scale_name." ".$matching_notes[$i_match][0]."-".$matching_notes[$i_match][1]."  value_match[".$i_item."][".$i_match."][".$mode."][".$direction."] = value[".$j."][".$k."] = ".$value[$j][$k]." => ".round($pos)." cents<br />";
			}
		}
	$result['score'] = $score;
	$result['value_match'] = $value_match;
	return $result;
	}
?>
