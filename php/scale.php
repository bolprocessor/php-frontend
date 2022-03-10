<?php
require_once("_basic_tasks.php");

// $test = TRUE;

if(isset($_POST['dir_scales']))
	$dir_scales = $_POST['dir_scales'];
else {
	echo "=> Csound resource file is not known. First open the ‘-cs’ file!"; die();
	}
if(isset($_GET['scalefilename']))
	$filename = urldecode($_GET['scalefilename']);
else {
	echo "Scale name is not known. Call it from the ‘-cs’ file!"; die();
	}
$this_title = $filename;
$url_this_page = "scale.php?".$_SERVER["QUERY_STRING"];
require_once("_header.php");

$csound_source = $_POST['csound_source'];
// echo $dir_scales." @@@<br />";
$file_link = $dir_scales.$filename.".txt";
if(!file_exists($file_link)) {
	echo "File may have been mistakenly deleted: ".$file_link;
	echo "<br />Return to the ‘-cs’ file to restore it!"; die();
	}

$save_codes_dir = $dir_scales.$filename."_codes";
if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
$image_file = $save_codes_dir.SLASH."image.php";
$h_image = fopen($image_file,"w");
fwrite($h_image,"<?php\n");
store($h_image,"filename",$filename);

$basekey = 60;
$baseoctave = 4;
$transposition_mode = '';
$p_raise = $q_raise = $p_raised_note = $q_raised_note = $cents_raised_note = $raised_note = $resetbase_note = $name_sensitive_note = '';
$scale_choice = $selected_grades = $names_notes_fifths = $names_notes_meantone = '';
$selected_grade_name = array();
$p_comma = 81; $q_comma = 80;
$syntonic_comma = cents($p_comma/$q_comma);
$p_major_whole_tone = 9;
$q_major_whole_tone = 8;
$list_sensitive_notes = $list_wolffifth_notes = $list_wolffourth_notes = '';
$pythagorean_third = cents(81/64);
$pythagorean_minor_sixth = 1200 - $pythagorean_third;
$perfect_fifth = cents(3/2);
$perfect_fourth = cents(4/3);
$series = array();
$link_edit = "scale.php";
$done = TRUE;

$clean_name_of_file = str_replace("#","_",$filename);
$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
$handle = fopen($dir_scale_images.$clean_name_of_file."-source.txt","w");
fwrite($handle,$csound_source."\n");
fclose($handle);

if(isset($_POST['scale_comment'])) $scale_comment = $_POST['scale_comment'];
else $scale_comment = '';

if(!isset($_SESSION['scroll'])) $_SESSION['scroll'] = 0;
if(isset($_POST['scroll'])) {
	$_SESSION['scroll'] = 1 - $_SESSION['scroll'];
	}

$error_raise_note ='';
if(isset($_POST['interpolate']) OR isset($_POST['savethisfile']) OR isset($_POST['fixkeynumbers']) OR isset($_POST['modifynote']) OR isset($_POST['alignscale']) OR isset($_POST['adjustscale']) OR isset($_POST['create_meantone']) OR isset($_POST['equalize']) OR isset($_POST['add_fifths_up']) OR isset($_POST['add_fifths_down']) OR isset($_POST['modifynames']) OR isset($_POST['use_convention']) OR isset($_POST['resetbase'])) {
	if(isset($_POST['scale_name'])) $new_scale_name = trim($_POST['scale_name']);
	else $new_scale_name = '';
	if($new_scale_name == '') $new_scale_name = $filename;
	$result1 = check_duplicate_name($dir_scales,$new_scale_name.".txt");
	$result2 = check_duplicate_name($dir_scales,$new_scale_name.".old");
	if($new_scale_name <> $filename AND ($result1 OR $result2)) {
		echo "<p><font color=\"red\">WARNING</font>: This name <font color=\"blue\">‘".$new_scale_name."’</font> already exists</p>";
		$scale_name = $filename;
		}
	else {
		rename($dir_scales.$filename.".txt",$dir_scales.$new_scale_name.".txt");
		$_GET['scalefilename'] = $filename = $scale_name = $new_scale_name;
		$file_link = $dir_scales.$filename.".txt";
		}
	$numgrades_fullscale = $_POST['numgrades'];
	$interval = trim($_POST['interval']);
	if($interval == '') $interval = 2;
	$cents = round(cents($interval),1);
	if(isset($_POST['interval_cents'])) {
		$new_cents = round($_POST['interval_cents'],1);
		if($new_cents > 1 AND $new_cents <> $cents)
			$interval = exp($new_cents / 1200 * log(2));
		}
	$basefreq = $_POST['basefreq'];
	$baseoctave = intval($_POST['baseoctave']);
	if($baseoctave <= 0 OR $baseoctave > 14) $baseoctave = 4;
	for($i = 0; $i <= $numgrades_fullscale; $i++) {
		if(!isset($_POST['p_'.$i]) OR $_POST['p_'.$i] == '') $p[$i] = 0;
		else $p[$i] = intval($_POST['p_'.$i]);
		if(!isset($_POST['p_'.$i]) OR $_POST['q_'.$i] == '') $q[$i] = 0;
		else $q[$i] = intval($_POST['q_'.$i]);
		
		if(!isset($_POST['ratio_'.$i]) OR $_POST['ratio_'.$i] == '') $ratio[$i] = 0;
		else $ratio[$i] = trim($_POST['ratio_'.$i]);
		if($ratio[$i] == '') $ratio[$i] = 0;
		
		if(!isset($_POST['series_'.$i])) $series[$i] = '';
		else $series[$i] = trim($_POST['series_'.$i]);
		if($series[$i] <> 'h' AND $series[$i] <> 'p') $series[$i] = '';
		
		if(!isset($_POST['key_'.$i])) $key[$i] = 0;
		else $key[$i] = trim($_POST['key_'.$i]);
		if($key[$i] == '') $key[$i] = 0;
		
		if(isset($_POST['modifynames']) AND isset($_POST['new_name_'.$i]) AND $_POST['new_name_'.$i] <> '')
			$_POST['name_'.$i] = $_POST['new_name_'.$i];
		if(!isset($_POST['name_'.$i])) $name[$i] = "•";
		else $name[$i] = trim($_POST['name_'.$i]);
		// Slash is reserved for beginning and end of scale_note_names
		$name[$i] = str_replace("/",'',$name[$i]);
		if($name[$i] == '') $name[$i] = "•";
		}
	$new_basekey = abs(intval($_POST['basekey']));
	if($new_basekey > 0) $basekey = $new_basekey;
	if($key[0] <> $basekey OR $key[0] == 0 OR $key[$numgrades_fullscale] == 0 OR isset($_POST['fixkeynumbers'])) {
		$this_key = $basekey;
		echo "<p><font color=\"red\">➡</font> Keys have been renumbered following ‘basekey’</p>";
		for($i = 0; $i <= $numgrades_fullscale; $i++) {
			if($name[$i] <> '' AND $name[$i] <> '•')
				$key[$i] = $this_key++;
			else $key[$i] = 0;
			}
		}
/*	if($key[0] <> $basekey) {
		echo "<p><font color=\"red\">WARNING</font>: the first key has been set to <font color=\"blue\">‘".$basekey."’</font> which is the value of <b>basekey</b></p>";
		$key[0] = $basekey;
		} */
	$name[$numgrades_fullscale] = $name[0];
	if(isset($_POST['modifynote'])) {
		$p_raised_note = abs(intval($_POST['p_raised_note']));
		$q_raised_note = abs(intval($_POST['q_raised_note']));
		$cents_raised_note = trim($_POST['cents_raised_note']);
		$raised_note = trim($_POST['raised_note']);
		if(($p_raised_note * $q_raised_note) == 0 AND $cents_raised_note == 0)
			$error_raise_note .= "<font color=\"red\"> ➡ ERROR: Ratio or cent correction for raising note has not been entered</font><br />";
		if($raised_note == '')
			$error_raise_note .= "<font color=\"red\"> ➡ ERROR: Note asked for raising has not been entered</font><br />";
		else {
			$j_raise = -1;
			for($j = 0; $j < $numgrades_fullscale; $j++) {
				if($name[$j] == $raised_note) {
					$j_raise = $j;
					if(($p_raised_note * $q_raised_note * $p[$j] * $q[$j]) <> 0) {
						$p[$j] = $p[$j] * $p_raised_note;
						$q[$j] = $q[$j] * $q_raised_note;
						$fraction = simplify_fraction_eliminate_schisma($p[$j],$q[$j]);
						if($fraction['p'] <> $p[$j]) {
							$p[$j] = $fraction['p'];
							$q[$j] = $fraction['q'];
							}
						$ratio[$j] = $p[$j] / $q[$j];
						}
					else {
						$ratio[$j] = $ratio[$j] * exp(($cents_raised_note) / 1200 * log(2));
						$p[$j] = $q[$j] = 0;
						}
					}
				}
			if($j_raise == -1)
				$error_raise_note .= "<font color=\"red\"> ➡ ERROR: Note ‘".$raised_note."’ asked for raising does not belong to scale</font><br />";
			else $scale_comment .= "Note ‘".$raised_note."’ modified by ".$cents_raised_note." cents (".date('Y-m-d H:i:s').")";
			}
		}
	if($p[0] == 0 OR $q[0] == 0) {
		$pmax = intval($ratio[0] * 10000);
		$qmax = 10000;
		$gcd = gcd($pmax,$qmax);
		$pmax = $pmax / $gcd;
		$qmax = $qmax / $gcd;
		$p[0] = $pmax;
		$q[0] = $qmax;
		}
	if($p[$numgrades_fullscale] == 0 OR $q[$numgrades_fullscale] == 0) {
		$pmax = intval($interval * 10000);
		$qmax = 10000;
		$gcd = gcd($pmax,$qmax);
		$pmax = $pmax / $gcd;
		$qmax = $qmax / $gcd;
		$p[$numgrades_fullscale] = $pmax;
		$q[$numgrades_fullscale] = $qmax;
		}
	
	if(isset($_POST['alignscale']) AND ($ratio[0] <> 0 OR (($p[0] * $q[0]) <> 0))) {
		if(($p[0] * $q[0]) <> 0) {
			$p_align = $q[0];
			$q_align = $p[0];
			$ratio_align = $p_align / $q_align;
			}
		else {
			$p_align = $q_align = 0;
			$ratio_align = 1 / $ratio[0];
			}
		for($j = 0; $j <= $numgrades_fullscale; $j++) {
			if(($p_align * $q_align * $p[$j] * $q[$j]) <> 0) {
				$p[$j] = $p[$j] * $p_align;
				$q[$j] = $q[$j] * $q_align;
				$fraction = simplify_fraction_eliminate_schisma($p[$j],$q[$j]);
				if($fraction['p'] <> $p[$j]) {
					$p[$j] = $fraction['p'];
					$q[$j] = $fraction['q'];
					}
				$ratio[$j] = $p[$j] / $q[$j];
				}
			else {
				$ratio[$j] = $ratio[$j] * $ratio_align;
				$p[$j] = $q[$j] = 0;
				}
			}
		$scale_comment .= "Scale aligned ratio ".$ratio_align." (".date('Y-m-d H:i:s').")";
		}
	
	if(isset($_POST['adjustscale'])) {
		$p_adjust = $_POST['p_adjust'];
		$q_adjust = $_POST['q_adjust'];
		$cents_adjust = $_POST['cents_adjust'];
		if(($p_adjust * $q_adjust) <> 0) {
			$ratio_adjust = $p_adjust / $q_adjust;
			}
		else {
			$ratio_adjust = exp($cents_adjust / 1200 * log(2));
			}
		$p_new = $q_new = $ratio_new = 1;
		for($j = 0; $j <= $numgrades_fullscale; $j++) {
			if($name[$j] == '' OR $name[$j] == '•') {
				if(($p_new <> $p[$j] OR $q_new <> $q[$j]) AND $ratio_new <> round($ratio[$j],3))
					continue;
				// Don't create 2 positions with identical ratios
				$name[$j] = $name[$j-1];
				$name[$j-1] = '';
				$p[$j-1] = $p_last;
				$q[$j-1] = $q_last;
				$ratio[$j-1] = $ratio_last;
				continue;
				}
			$p_last = $p[$j];
			$q_last = $q[$j];
			$ratio_last = round($ratio[$j],3);
			if(($p_adjust * $q_adjust * $p[$j] * $q[$j]) <> 0) {
				$p[$j] = $p[$j] * $p_adjust;
				$q[$j] = $q[$j] * $q_adjust;
				$fraction = simplify_fraction_eliminate_schisma($p[$j],$q[$j]);
				if($fraction['p'] <> $p[$j]) {
					$p[$j] = $fraction['p'];
					$q[$j] = $fraction['q'];
					}
				$ratio[$j] = $p[$j] / $q[$j];
				}
			else {
				$ratio[$j] = $ratio[$j] * $ratio_adjust;
				$p[$j] = $q[$j] = 0;
				}
			$p_new = $p[$j];
			$q_new = $q[$j];
			$ratio_new = round($ratio[$j],3);
			}
		$scale_comment .= "Scale adjusted ratio ".$ratio_adjust." (".date('Y-m-d H:i:s').")";
		}
	}

$number_steps = $p_start_fifths = $q_start_fifths = $error_fifths = '';
if(isset($_POST['add_fifths_up']) OR isset($_POST['add_fifths_down'])) {
	$going_up = isset($_POST['add_fifths_up']);
	$p_start_fifths = intval($_POST['p_start_fifths']);
	$q_start_fifths = intval($_POST['q_start_fifths']);
	$names_notes_fifths = trim($_POST['names_notes_fifths']);
	$names_notes_fifths = str_replace(' ','',$names_notes_fifths);
	$names_notes_fifths = str_replace(';',',',$names_notes_fifths);
	$names_notes_fifths = str_replace('/','=',$names_notes_fifths);
	$names_notes_fifths = str_replace('•','=',$names_notes_fifths);
	$table_names = array();
	if($names_notes_fifths <> '') $table_names = explode(",",$names_notes_fifths);
	$number_steps = count($table_names) - 1;
	if($number_steps == 0)
		$error_fifths .= "<br />Enter the names of new positions!";
	if(($p_start_fifths * $q_start_fifths) == 0)
		$error_fifths .= "<br />Incorrect fraction ".$p_start_fifths."/".$q_start_fifths." to start creating fifths";
	if($error_fifths == '') {
		if($going_up) $thedirection = "up";
		else $thedirection = "down";
		echo "<p><font color=\"red\">➡</font> Creating ".$number_steps." perfect fifths ".$thedirection." starting from fraction ".$p_start_fifths."/".$q_start_fifths."</p>";
		$scale_comment .= "Added fifths ".$thedirection.": “".$names_notes_fifths."” starting fraction ".$p_start_fifths."/".$q_start_fifths." (".date('Y-m-d H:i:s').")";
		$ignore_unlabeled = isset($_POST['ignore_unlabeled']);
		$new_p = $new_q = $new_ratio = $new_name = array();
		for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
			if($ratio[$j] == '' OR $ratio[$j] == 0) continue;
			if(($name[$j] == '' OR $name[$j] == '•') AND round($ratio[$j],3) <> 1  AND round($ratio[$j],3) <> 2) {
				if($ignore_unlabeled) continue;
				}
			$new_p[$jj] = $p[$j];
			$new_q[$jj] = $q[$j];
			$new_ratio[$jj] = $ratio[$j];
			$new_name[$jj] = $name[$j];
			$jj++;
			}
		$p_current = $p_start_fifths;
		$q_current = $q_start_fifths;
		for($k = 0; $k <= $number_steps; $k++) {
			while(($p_current / $q_current) > $interval) $q_current = $q_current * $interval;
			while(($p_current / $q_current) < 1) $p_current = $p_current * $interval;
			$fraction = simplify_fraction_eliminate_schisma($p_current,$q_current);
			if($fraction['p'] <> $p_current) {
				$p_current = $fraction['p'];
				$q_current = $fraction['q'];
				}
			$new_p[$jj] = $p_current;
			$new_q[$jj] = $q_current;
			$new_ratio[$jj] = $p_current / $q_current;
			$new_name[$jj] = '•';
			if(isset($table_names[$k]) AND $table_names[$k] <> '') $new_name[$jj] = $table_names[$k];
			echo "‘".$new_name[$jj]."’ (".$p_current."/".$q_current.") ";
			if($going_up) $p_current = $p_current * 3;
			else $q_current = $q_current * 3;
			$jj++;
			}
		if($ignore_unlabeled) echo "<p>Unlabeled positions have been deleted</p>";
		asort($new_ratio);
		echo "<br />";
	/*	foreach($new_ratio as $j => $val) {
			echo round($new_ratio[$j],3)." ‘".$new_name[$j]."’ fraction = ".$new_p[$j]."/".$new_q[$j]."<br />";
			}
		echo "<br />"; */
		$p_current = $q_current = $ratio_current = 0;
		$jj = 0;
		$p = $q = $ratio = $name = $series = $key = array();
		foreach($new_ratio as $j => $val) {
			if(($p_current <> 0 AND $new_p[$j] == $p_current AND $new_q[$j] == $q_current) OR round($new_ratio[$j],3) == round($ratio_current,3)) {
				if($jj > 0 AND $new_name[$j] <> '•') {
					$name[$jj-1] = merge_names($new_name[$j],$name[$jj-1]);
					}
		//		echo "  (".$j.") jj = ".$jj." name = ".$new_name[$j]." the_new_name = ".$name[$jj-1]."<br />";
				continue;
				}
			$p_current = $p[$jj] = $new_p[$j];
			$q_current = $q[$jj] = $new_q[$j];
			$ratio_current = $ratio[$jj] = $new_ratio[$j];
			$name[$jj] = $new_name[$j];
			$series[$jj] = '';
			$key[$jj] = $basekey + $jj;
			$jj++;
			}
		$numgrades_fullscale = $jj - 1;
	/*	if($name[0] <> '•') $name[$numgrades_fullscale] = merge_names($name[0],$name[$numgrades_fullscale]);
		else $name[0] = $name[$numgrades_fullscale]; */
		$name[$numgrades_fullscale] = merge_names($name[0],$name[$numgrades_fullscale]);
		$name[0] = $name[$numgrades_fullscale];
		}
	}

$error_resetbase = '';
if(isset($_POST['resetbase'])) {
	$resetbase_note = trim($_POST['resetbase_note']);
	if($resetbase_note == '')
		$error_resetbase .= "<br />New base note has not been entered";
	if($error_resetbase == '') {
		$j_reset = -1;
		for($j = 0; $j < $numgrades_fullscale; $j++)
			if($name[$j] == $resetbase_note) $j_reset = $j;
		if($j_reset < 0) $error_resetbase .= "<br />Note ‘".$resetbase_note."’ does not belong to this scale";
		else {
			$msg = "Base note reset to ‘".$resetbase_note."’";
			$msg .= " (".date('Y-m-d H:i:s').")";
			echo "<p><font color=\"red\">➡</font> ".$msg."</p>";
			$scale_comment .= $msg;
			$new_p = $new_q = $new_ratio = $new_name = $new_series = array();
			$p_change = $p[$j_reset] * $q[0];
			$q_change = $q[$j_reset] * $p[0];
			$ratio_change = $ratio[$j_reset] / $ratio[0];
		//	echo $p_change."/".$q_change." ".$ratio_change."<br />";
			for($j = 0; $j < $numgrades_fullscale; $j++) {
				$new_j = $j - $j_reset;
				$new_j = modulo($new_j,$numgrades_fullscale);
				$new_p[$new_j] = $p[$j] * $q_change;
				$new_q[$new_j] = $q[$j] * $p_change;
				if(($new_p[$new_j] * $new_q[$new_j]) <> 0) {
					while($new_p[$new_j]/$new_q[$new_j] < $ratio[0]) $new_p[$new_j] = $new_p[$new_j] * $interval;
					while($new_p[$new_j]/$new_q[$new_j] > ($interval * $ratio[0])) $new_q[$new_j] = $new_q[$new_j] * $interval;
					$gcd = gcd($new_p[$new_j],$new_q[$new_j]);
					$new_p[$new_j] = $new_p[$new_j] / $gcd;
					$new_q[$new_j] = $new_q[$new_j] / $gcd;
					$new_ratio[$new_j] = $new_p[$new_j]/$new_q[$new_j];
					}
				else $new_ratio[$new_j] = $ratio[$j] / $ratio_change;
				while($new_ratio[$new_j] < $ratio[0]) $new_ratio[$new_j] = $new_ratio[$new_j] * $interval;
				while($new_ratio[$new_j] > ($interval * $ratio[0])) $new_ratio[$new_j] = $new_ratio[$new_j] / $interval;
				if($new_j == 0) {
					$new_ratio[$new_j] = $ratio[0];
					$new_p[$new_j] = $p[0];
					$new_q[$new_j] = $q[0];
					}
				$new_name[$new_j] = $name[$j];
				$new_series[$new_j] = $series[$j];
		//		echo "(".$new_j.") ".$new_name[$new_j]." ".$new_ratio[$new_j]." ".$new_p[$new_j]."/".$new_q[$new_j]." ".$new_series[$new_j]."<br />";
				}
			for($j = 0; $j < $numgrades_fullscale; $j++) {
				$name[$j] = $new_name[$j];
				$series[$j] = $new_series[$j];
				$p[$j] = $new_p[$j];
				$q[$j] = $new_q[$j];
				$ratio[$j] = $new_ratio[$j];
				}
			$name[$numgrades_fullscale] = $name[0];
			$p[$numgrades_fullscale] = $p[0] * $interval;
			$q[$numgrades_fullscale] = $q[0];
			$ratio[$numgrades_fullscale] = $ratio[0] * $interval;
			$series[$numgrades_fullscale] = $series[0];
			}
		}
	}
	
if(isset($_POST['use_convention'])) {
	$found = FALSE;
	for($i = $j = 0; $i <= $numgrades_fullscale; $i++) {
		if($name[$i] == '') continue;
		$found = TRUE;
		$this_note = $name[$i];
		if(($kfound = array_search($this_note,$Indiannote)) !== FALSE) $k = $kfound;
		else if(($kfound = array_search($this_note,$AltIndiannote)) !== FALSE) $k = $kfound;
		else if(($kfound = array_search($this_note,$Englishnote)) !== FALSE) $k = $kfound;
		else if(($kfound = array_search($this_note,$AltEnglishnote)) !== FALSE) $k = $kfound;
		else if(($kfound = array_search($this_note,$Frenchnote)) !== FALSE) $k = $kfound;
		else if(($kfound = array_search($this_note,$AltFrenchnote)) !== FALSE) $k = $kfound;
		else $k = $j;
		if(!isset($_POST['new_note_'.$k]))
			$name[$i] = $_POST['new_note_0'];
		else $name[$i] = $_POST['new_note_'.$k];
		$j++;
		}
	if(!$found) {
		for($i = 0; $i < $numgrades_fullscale; $i++) $name[$i] = $_POST['new_note_'.$i];
	//	$name[$numgrades_fullscale] = $_POST['new_note_0'];
		}
	$name[$numgrades_fullscale] = $name[0];
	}


$note_start_meantone = '';
$p_step_meantone = $q_step_meantone = $p_fraction_comma = $q_fraction_comma = 0;
$error_meantone = '';
if(isset($_POST['note_start_meantone'])) $note_start_meantone = trim($_POST['note_start_meantone']);
if(isset($_POST['p_step_meantone'])) $p_step_meantone = intval($_POST['p_step_meantone']);
if(isset($_POST['q_step_meantone'])) $q_step_meantone = intval($_POST['q_step_meantone']);
if(isset($_POST['p_fraction_comma'])) $p_fraction_comma = intval($_POST['p_fraction_comma']);
if(isset($_POST['q_fraction_comma'])) $q_fraction_comma = intval($_POST['q_fraction_comma']);
if(isset($_POST['create_meantone'])) {
	$jstart = -1;
	for($j = 0; $j <= $numgrades_fullscale; $j++)
		if($note_start_meantone <> '' AND $note_start_meantone == $name[$j]) $jstart = $j;
	if($jstart < 0)
		$error_meantone .= "<br />Missing or incorrect note ‘".$note_start_meantone."’ to start meantone";
	if($p_step_meantone < 1 OR $q_step_meantone < 1)
		$error_meantone .= "<br />Incorrect fraction ‘".$p_step_meantone."/".$q_step_meantone."’";
	if($p_fraction_comma == 0) $q_fraction_comma = 1; // User didn't pay attention…
	if(abs($q_fraction_comma) < 1)
		$error_meantone .= "<br />Incorrect fraction of comma ‘".$p_fraction_comma."/".$q_fraction_comma."’";
	if(!isset($_POST['meantone_direction']))
		$error_meantone .= "<br />Missing selection ‘up’ or ‘down’";
	else $meantone_direction = $_POST['meantone_direction'];
		$thedirection = $_POST['meantone_direction'];
	$names_notes_meantone = trim($_POST['names_notes_meantone']);
	$ignore_unlabeled = isset($_POST['ignore_unlabeled']);
	$names_notes_meantone = str_replace(' ','',$names_notes_meantone);
	$names_notes_meantone = str_replace(';',',',$names_notes_meantone);
	$names_notes_meantone = str_replace('/','=',$names_notes_meantone);
	$names_notes_meantone = str_replace('•','=',$names_notes_meantone);
	$table_names = array();
	if($names_notes_meantone <> '') $table_names = explode(",",$names_notes_meantone);
	$number_steps = count($table_names) - 1;
	if($note_start_meantone <> $table_names[0])
		$error_meantone .= "<br />Note ‘".$note_start_meantone."’ should be the first one in the series";
	if($error_meantone == '') {
		$msg = "Created meantone ".$meantone_direction."ward notes “".$names_notes_meantone."” fraction ".$p_step_meantone."/".$q_step_meantone;
		if($p_fraction_comma <> 0) $msg .= " adjusted ".$p_fraction_comma."/".$q_fraction_comma." comma";
		$msg .= " (".date('Y-m-d H:i:s').")";
		echo "<p><font color=\"red\">➡</font> ".$msg."</p>";
		$scale_comment .= $msg;
		if($meantone_direction == "up") {
			$step_cents = cents($p_step_meantone/$q_step_meantone);
			$step_cents += $syntonic_comma * $p_fraction_comma/$q_fraction_comma;
			}
		else {
			$step_cents = - cents($p_step_meantone/$q_step_meantone);
			$step_cents -= $syntonic_comma * $p_fraction_comma/$q_fraction_comma;
			}
		$ratio_multiply = exp($step_cents / 1200 * log(2));
		$new_p = $new_q = $new_ratio = $new_name = array();
		for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
			if($ratio[$j] == '' OR $ratio[$j] == 0) continue;
			if(($name[$j] == '' OR $name[$j] == '•') AND round($ratio[$j],3) <> 1  AND round($ratio[$j],3) <> 2) {
				if($ignore_unlabeled) continue;
				}
			$new_p[$jj] = $p[$j];
			$new_q[$jj] = $q[$j];
			$new_ratio[$jj] = $ratio[$j];
			$new_name[$jj] = $name[$j];
			$jj++;
			}
		$ratio_current = $ratio[$jstart];
		for($k = 0; $k <= $number_steps; $k++) {
			while($ratio_current > $interval) $ratio_current = $ratio_current / $interval;
			while($ratio_current < 1) $ratio_current = $ratio_current * $interval;
			$new_ratio[$jj] = $ratio_current;
			$new_name[$jj] = '•';
			if(isset($table_names[$k]) AND $table_names[$k] <> '' AND $table_names[$k] <> '•')
				$new_name[$jj] = $table_names[$k];
			echo "‘".$new_name[$jj]."’ (".round($ratio_current,3).") ";
			$ratio_current = $ratio_current * $ratio_multiply;
			$jj++;
			}
		echo "<br />";
		if($ignore_unlabeled) echo "<p>Unlabeled positions have been deleted</p>";
		asort($new_ratio);
		echo "<br />";
	/*	foreach($new_ratio as $j => $val) {
			echo round($new_ratio[$j],3)." ‘".$new_name[$j]."’<br />";
			}
		echo "<br />"; */
		$ratio_current = 0;		$jj = 0;
		$p = $q = $ratio = $name = $series = $key = array();
		foreach($new_ratio as $j => $val) {
			if(($ratio_current <> 0 AND $new_ratio[$j] == $ratio_current) OR round($new_ratio[$j],3) == round($ratio_current,3)) {
				if($jj > 0 AND $new_name[$j] <> '•') {
					$name[$jj-1] = merge_names($new_name[$j],$name[$jj-1]);
	//				echo "  (".$j.") jj = ".$jj." name = ".$new_name[$j]." the_new_name = ".$name[$jj-1]."<br />";
					}
				continue;
				}
			$p[$jj] = 0;
			$q[$jj] = 0;
			$ratio_current = $ratio[$jj] = $new_ratio[$j];
			if(round($ratio[$jj],3) == 1) $p[$jj] = $q[$jj] = 1;
			if(round($ratio[$jj],3) == 2) {
				$p[$jj] = 2;
				$q[$jj] = 1;
				}
			$name[$jj] = $new_name[$j];
			$series[$jj] = '';
			$key[$jj] = $basekey + $jj;
			$jj++;
			}
		$numgrades_fullscale = $jj - 1;
	/*	if($name[0] <> '•') $name[$numgrades_fullscale] = $name[0];
		else $name[0] = $name[$numgrades_fullscale]; */
		$name[$numgrades_fullscale] = merge_names($name[0],$name[$numgrades_fullscale]);
		$name[0] = $name[$numgrades_fullscale];
		}
	}

if(isset($_POST['names_notes_equalize'])) $names_notes_equalize = trim($_POST['names_notes_equalize']);
else $names_notes_equalize = '';
if(isset($_POST['p_equalize'])) $p_equalize = abs(intval($_POST['p_equalize']));
else $p_equalize = '';
if(isset($_POST['q_equalize'])) $q_equalize = abs(intval($_POST['q_equalize']));
else $q_equalize = '';
$error_equalize = '';
if(isset($_POST['equalize'])) {
	$names_notes_equalize = str_replace(' ','',$names_notes_equalize);
	$names_notes_equalize = str_replace(';',',',$names_notes_equalize);
	$names_notes_equalize = str_replace('/','=',$names_notes_equalize);
	$names_notes_equalize = str_replace('•','=',$names_notes_equalize);
	$table_names = explode(",",$names_notes_equalize);
	$num_notes_equalize = count($table_names);
	if($num_notes_equalize < 3)
		$error_equalize .= "<br>List should contain minimum 3 notes separated by commas";
	if(($p_equalize * $q_equalize) == 0)
		$error_equalize .= "<br>Fraction is incorrect. It should be a positive integer ratio";
	$name_start = $table_names[0];
	$name_end = $table_names[$num_notes_equalize - 1];
	$jstart = $jend = -1;
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($ratio[$j] == '' OR $ratio[$j] == 0) continue;
		if($name[$j] == $name_start) $jstart = $j;
		if($name[$j] == $name_end) $jend = $j;
		}
	if($jstart == -1) $error_equalize .= "<br>Note ‘".$name_start."’ not found in this scale";
	if($jend == -1) $error_equalize .= "<br>Note ‘".$name_end."’ not found in this scale";
	if($error_equalize == '' AND $jstart >= 0 AND $jend >= 0) {
		$number_steps = $num_notes_equalize - 1;
		$total_ratio = $ratio[$jstart];
		for($k = 1; $k < $num_notes_equalize; $k++) {
			$total_ratio = $total_ratio * $p_equalize / $q_equalize;
			$position = $total_ratio;
			while($position < 1) $position = $position * $interval;
			while($position > $interval) $position = $position/$interval;
			$this_note_name = $table_names[$k];
		//	echo $this_note_name." -> ".$position." total ratio = ".$total_ratio." ";
			$this_j = -1;
			for($j = 0; $j < $numgrades_fullscale; $j++) {
				if($name[$j] == $this_note_name) $this_j = $j;
				}
			if($this_j >= 0) {
				if($ratio[$this_j] > 0) {
					$equalize_correction = $ratio[$this_j] / $position;
					$dif = abs(cents($equalize_correction));
		//			echo "ratio[".$this_j."] = ".$ratio[$this_j]." equalize_correction = ".$equalize_correction." dif = ".$dif;
					if($dif > 50) {
						$error_equalize .= "<br>Note ‘".$this_note_name."’ has position ".round($ratio[$this_j],3)."  too different from expected value ".round($position,3);
						}
					}
				}
		//	echo "<br />";
			}
		if($error_equalize == '') {
			$equalize_ratio = $total_ratio / $ratio[$jstart] * $equalize_correction;
			$step_ratio = $equalize_ratio ** (1. / $number_steps);
			$adjust_cents = round(cents($step_ratio) - cents($p_equalize/$q_equalize),1);
			$msg = "Equalized intervals over series “".$names_notes_equalize."” approx fraction ".$p_equalize."/".$q_equalize." adjusted ".$adjust_cents." cents to ratio = ".round($step_ratio,3);
			$msg .= " (".date('Y-m-d H:i:s').")";
			echo "<p><font color=\"red\">➡</font> ".$msg."</p>";
			$scale_comment .= $msg;
			$ignore_unlabeled = isset($_POST['ignore_unlabeled']);
			if($ignore_unlabeled) echo "<p>Unlabeled positions have been deleted</p>";
			$new_p = $new_q = $new_ratio = $new_name = array();
			for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
				if($ratio[$j] == '' OR $ratio[$j] == 0) continue;
				if(($name[$j] == '' OR $name[$j] == '•') AND round($ratio[$j],3) <> 1  AND round($ratio[$j],3) <> 2) {
					if($ignore_unlabeled) continue;
					}
				$new_ratio[$jj] = $ratio[$j];
				$new_name[$jj] = $name[$j];
				$jj++;
				}
			$total_ratio = $ratio[$jstart];
			for($k = 0; $k <= $number_steps; $k++) {
				$position = $total_ratio;
				while($position < 1) $position = $position * $interval;
				while($position > $interval) $position = $position/$interval;
				$new_ratio[$jj] = $position;
				$new_name[$jj] = '•';
				if(isset($table_names[$k]) AND $table_names[$k] <> '') $new_name[$jj] = $table_names[$k];
				echo "‘".$new_name[$jj]."’ (".round($position,3).") ";
				$total_ratio = $total_ratio * $step_ratio;
				$jj++;
				}
			asort($new_ratio);
			echo "<br />";
		/*	foreach($new_ratio as $j => $val) {
				echo round($new_ratio[$j],3)." ‘".$new_name[$j]."’<br />";
				}
			echo "<br />"; */
			$ratio_current = 0;
			$jj = 0;
			$p = $q = $ratio = $name = $series = $key = array();
			foreach($new_ratio as $j => $val) {
				if(round($new_ratio[$j],3) == round($ratio_current,3)) {
					if($jj > 0 AND $new_name[$j] <> '•') {
						$name[$jj-1] = merge_names($new_name[$j],$name[$jj-1]);
						}
			//		echo "  (".$j.") jj = ".$jj." name = ".$new_name[$j]." the_new_name = ".$name[$jj-1]."<br />";
					continue;
					}
				$ratio_current = $ratio[$jj] = $new_ratio[$j];
				$name[$jj] = $new_name[$j];
				$p[$jj] = $q[$jj] = 0;
				$series[$jj] = '';
				$key[$jj] = $basekey + $jj;
				$jj++;
				}
			$numgrades_fullscale = $jj - 1;
		/*	if($name[0] <> '•') $name[$numgrades_fullscale] = $name[0];
			else $name[0] = $name[$numgrades_fullscale]; */
			$name[$numgrades_fullscale] = merge_names($name[0],$name[$numgrades_fullscale]);
			$name[0] = $name[$numgrades_fullscale];
			}
		}
	}

if(isset($_POST['interpolate'])) {
	$i1 = $i2 = 0;
	while(TRUE) {
		$found = FALSE;
		while(TRUE) {
			$i2++;
			if($i2 > $numgrades_fullscale) break;
			if($p[$i2] > 0 AND $q[$i2] > 0) {
				$found = TRUE; break;
				}
			}
		if(!$found) break;
		if(($i2 - $i1) > 1) {
			$ratio1 = $p[$i1] / $q[$i1];
			$ratio2 = $p[$i2] / $q[$i2];
			$step = exp(log($ratio2/$ratio1) / ($i2 - $i1));
			$new_ratio = $ratio1;
			for($i = $i1 + 1; $i < $i2; $i ++) {
				$new_ratio = $new_ratio * $step;
				$ratio[$i] = round($new_ratio,3);
				}
			}
		$scale_comment .= "Interpolated equal intervals between grade ".$i1." and grade ".$i2." (".date('Y-m-d H:i:s').")";
		$i1 = $i2;
		}
	}

$message = '';
if(isset($_POST['savethisfile']) OR isset($_POST['fixkeynumbers']) OR isset($_POST['interpolate']) OR isset($_POST['modifynote']) OR isset($_POST['alignscale'])  OR isset($_POST['adjustscale']) OR isset($_POST['create_meantone']) OR isset($_POST['equalize']) OR isset($_POST['add_fifths_up']) OR isset($_POST['add_fifths_down']) OR isset($_POST['modifynames']) OR isset($_POST['use_convention']) OR isset($_POST['resetbase'])) {
	$message = "&nbsp;<span id=\"timespan\"><font color=\"red\">... Saving this scale ...</font></span>";
	if(isset($_POST['syntonic_comma'])) $syntonic_comma = $_POST['syntonic_comma'];
	if(isset($_POST['p_comma']) AND isset($_POST['q_comma'])) {
		$p_comma = $_POST['p_comma'];
		$q_comma = $_POST['q_comma'];
		if(($p_comma * $q_comma) > 0) $syntonic_comma = cents($p_comma/$q_comma);
		}
	else $p_comma = $q_comma = 0;
	if(round($syntonic_comma,3) == 21.506 AND ($p_comma * $q_comma) == 0) {
		$p_comma = 81; $q_comma = 80;
		}
	$table = explode(chr(10),$scale_comment);
	$imax = count($table); $empty = TRUE;
	$scale_comment = "<html>";
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		else $empty = FALSE;
		$scale_comment .= $line."<br />";
		}
	$scale_comment .= "</html>";
	if($empty) $scale_comment = '';
	$handle = fopen($file_link,"w");
	fwrite($handle,"\"".$scale_name."\"\n");
	$line_table = "f2 0 128 -51 ".$numgrades_fullscale." ".$interval." ".$basefreq." ".$basekey;
	$scale_note_names = $scale_fractions = $scale_keys = $scale_series = '';
	for($i = 0; $i <= $numgrades_fullscale; $i++) {
	//	echo $ratio[$i]." = ".$p[$i]."/".$q[$i]."<br />";
		if(!isset($ratio[$i]) OR $ratio[$i] < 0.5) continue;
		$line_table .= " ".round($ratio[$i],3);
		$scale_note_names .= $name[$i]." ";
		if(isset($series[$i]) AND $series[$i] <> '')
			$scale_series .= $series[$i]." ";
		else
			$scale_series .= "• ";
		if(!isset($p[$i])) $p[$i] = 0;
		if(!isset($q[$i])) $q[$i] = 0;
		$scale_fractions .= $p[$i]." ".$q[$i]." ";
		if(isset($key[$i]) AND $key[$i] > 0)
			$scale_keys .= ($key[$i]- $basekey)." ";
		else
			$scale_keys .= "0 ";
		}
	$scale_note_names = trim($scale_note_names);
	$scale_fractions = trim($scale_fractions);
	$scale_series = trim($scale_series);
	$scale_keys = trim($scale_keys);
	$comma_line = $syntonic_comma;
	if(($p_comma * $q_comma) > 0) $comma_line .= " ".$p_comma." ".$q_comma;
	fwrite($handle,"c".$comma_line."c\n");
	if($scale_note_names <> '')
		fwrite($handle,"/".$scale_note_names."/\n");
	if($scale_keys <> '')
		fwrite($handle,"k".$scale_keys."k\n");
	if($scale_series <> '')
		fwrite($handle,"s".$scale_series."s\n");
	fwrite($handle,"[".$scale_fractions."]\n");
	fwrite($handle,"|".$baseoctave."|\n");
	fwrite($handle,$line_table."\n");
	if($scale_comment <> '')
		fwrite($handle,$scale_comment);
	fclose($handle);
	$file_changed = $dir_scales."_changed";
	$handle = fopen($file_changed,"w");
	fclose($handle);
	}

$content = file_get_contents($file_link,TRUE);
$table = explode(chr(10),$content);
$imax = count($table);
$scale_name = $scale_table = $scale_fraction = $scale_series = $comma_line = $scale_note_names = $scale_keys = $scale_comment = '';
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	if($line == '') continue;
	if($line[0] == '"') {
		$scale_name = str_replace('"','',$line);
		continue;
		}
	if($line[0] == '/') {
		$scale_note_names = str_replace('/','',$line);
		continue;
		}
	if($line[0] == 'k') {
		$scale_keys = str_replace('k','',$line);
		continue;
		}
	if($line[0] == 's') {
		$scale_series = str_replace('s','',$line);
		continue;
		}
	if($line[0] == 'c') {
		$comma_line = str_replace('c','',$line);
		continue;
		}
	if($line[0] == '|') {
		$baseoctave = str_replace('|','',$line);
		continue;
		}
	if($line[0] == '<') {
		$scale_comment = $line;
		continue;
		}
	if($line[0] == '[') {
		$scale_fraction = str_replace('[','',$line);
		$scale_fraction = trim(str_replace(']','',$scale_fraction));
		continue;
		}
	$scale_table = $line;
	$table2 = explode(' ',$line);
	$ratio = array();
	if(abs(intval($table2[3])) <> 51) {
		echo "<p>This function table is not a microtonal scale:<br />".$line;
		die();
		}
	}
echo "Csound function table: <font color=\"blue\">".$scale_table."</font>";
if($message <> '') echo $message;
echo "<div style=\"float:right; margin-top:1em; background-color:white; padding:1em; border-radius:5%;\"><h1>Scale “".$filename."”</h1><h3>This version is stored in <font color=\"blue\">‘".$csound_source."’</font></h3>";

$link = "scale_image.php?save_codes_dir=".urlencode($save_codes_dir)."&dir_scale_images=".urlencode($dir_scale_images)."&csound_source=".urlencode($csound_source);
$link_no_marks = $link."&no_marks=1";
$link_no_cents = $link."&no_cents=1";
$link_no_intervals = $link_no_marks."&no_intervals=1";

if(isset($_POST['new_p_comma']) AND isset($_POST['new_q_comma']) AND $_POST['new_p_comma'] > 0  AND $_POST['new_q_comma'] > 0) {
	// We need these values immediately to update the image if the syntonic comma has been modified
	$new_p_comma = $_POST['new_p_comma'];
	$new_q_comma = $_POST['new_q_comma'];
	$gcd = gcd($new_p_comma,$new_q_comma);
	$new_p_comma = $new_p_comma / $gcd;
	$new_q_comma = $new_q_comma / $gcd;
	$new_comma = cents($new_p_comma/$new_q_comma);
	$more = "_new";
	}
else if(isset($_POST['new_comma']) AND is_numeric($_POST['new_comma'])) {
	$new_comma = trim($_POST['new_comma']);
	$new_p_comma = $new_q_comma = 0;
	$more = "_new";
	}
else {
	$new_comma = $syntonic_comma;
	$new_p_comma = $p_comma;
	$new_q_comma = $q_comma;
	$more = '';
	}
	
$image_name = clean_folder_name($filename)."_".round(10 * $new_comma).$more."_image";
// echo $image_name."<br />";
$image_height = 820;
echo "<div class=\"shadow\" style=\"border:2px solid gray; background-color:azure; width:20em;  padding:8px; text-align:center; border-radius: 6px;\">IMAGE:<br /><a onclick=\"window.open('".$link."','".$image_name."','width=1000,height=".$image_height.",left=100'); return false;\" href=\"".$link."\">full</a> - <a onclick=\"window.open('".$link_no_marks."','".$image_name."','width=1000,height=".$image_height.",left=100'); return false;\" href=\"".$link_no_marks."\">no marks</a> - <a onclick=\"window.open('".$link_no_cents."','".$image_name."','width=1000,height=".$image_height.",left=100'); return false;\" href=\"".$link_no_cents."\">no cents</a> - <a onclick=\"window.open('".$link_no_intervals."','".$image_name."','width=1000,height=".$image_height.",left=100'); return false;\" href=\"".$link_no_intervals."\">no intervals</a></div>";

echo "</div>";
echo "<p>➡ <a target=\"_blank\" href=\"https://www.csounds.com/manual/html/GEN51.html\">Read the documentation</a></p>";
$numgrades_fullscale = $table2[4];
$interval = $table2[5];
$basefreq = $table2[6];
$basekey = $table2[7];
$warned_ratios = FALSE;
for($j = 8; $j < ($numgrades_fullscale + 9); $j++) {
	if(!isset($table2[$j])) {
		if(!$warned_ratios)
			echo "<p><font color=\"red\">WARNING:</font> the number of ratios is smaller than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
		$warned_ratios = TRUE;
		}
	else $ratio[$j - 8] = $table2[$j];
	}
if(($j - 9) > $numgrades_fullscale) {
	if(!$warned_ratios) echo "<p><font color=\"red\">WARNING:</font> the number of ratios is larger than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
	$warned_ratios = TRUE;
	}
$table = array();
if($scale_note_names <> '') {
	$table = explode(' ',$scale_note_names);
	$imax = count($table);
	if($imax <> ($numgrades_fullscale + 1)) {
		echo "<p><font color=\"red\">WARNING:</font> the number of note names (".($imax - 1).") is not <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
		}
	}
if($comma_line <> '') {
	$table = explode(' ',$comma_line);
	if($table[0] > 0) $syntonic_comma = $table[0];
	if(isset($table[1]) AND isset($table[2])) {
		$p_comma = intval($table[1]); $q_comma = intval($table[2]);
		if(($p_comma * $q_comma) > 0) $syntonic_comma = cents($p_comma/$q_comma);
		}
	else $p_comma = $q_comma = 0;
	}
if(round($syntonic_comma,3) == 21.506 AND ($p_comma * $q_comma) == 0) {
	$p_comma = 81; $q_comma = 80;
	}
	
if($scale_series <> '') {
	$table = explode(' ',$scale_series);
	$imax = count($table);
	if($imax <> ($numgrades_fullscale + 1)) {
		echo "<p><font color=\"red\">WARNING:</font> the number of series markers (".($imax - 1).") is not <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
		}
	}
	
if($scale_keys <> '') {
	$table = explode(' ',$scale_keys);
	$imax = count($table);
	if($imax <> ($numgrades_fullscale + 1)) {
		echo "<p><font color=\"red\">WARNING:</font> the number of keys (".($imax - 1).") is not <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
		}
	}
if($scale_fraction <> '') {
	$table = explode(' ',$scale_fraction);
	$imax = count($table);
	for($i = 0; $i < $imax; $i += 2) {
		$p[$i / 2] = $table[$i];
		$q[$i / 2] = $table[$i+1];
		}
	}

$pmax = intval($interval * 10000);
$qmax = 10000;
$gcd = gcd($pmax,$qmax);
$pmax = $pmax / $gcd;
$qmax = $qmax / $gcd;

$table = explode(' ',$scale_series);
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(isset($table[$i]) AND $table[$i] <> "•") $series[$i] = trim($table[$i]);
	else $series[$i] = '';
	}
	
$table = explode(' ',$scale_note_names);
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(isset($table[$i]) AND $table[$i] <> "•") $name[$i] = trim($table[$i]);
	else $name[$i] = '';
	if(!isset($p[$i])) $p[$i] = 0;
	if(!isset($q[$i])) $q[$i] = 0;
	if($p[$i] > 0 AND $q[$i] > 0)
		$ratio[$i] = round($p[$i] / $q[$i],3);
	}
if($scale_keys <> '') {
	$table = explode(' ',$scale_keys);
	for($i = 0; $i <= $numgrades_fullscale; $i++) {
		if(isset($table[$i]) AND $table[$i] <> '') {
			$key[$i] = intval($table[$i]);
		//	echo $key[$i]." ";
			if($key[$i] > 0 AND $key[$i] < $basekey) $key[$i] += $basekey;
			}
		else $key[$i] = 0;
		}
	$key[0] = $basekey;
	}
else $key = assign_default_keys($name,$basekey,$numgrades_fullscale);

for($j = $numgrades_with_labels = 0; $j < $numgrades_fullscale; $j++) {
	if($name[$j] == '') continue;
	$numgrades_with_labels++;
	}
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<input type=\"hidden\" name=\"csound_source\" value=\"".$csound_source."\">";

echo "<h3>Name of this tonal scale: ";
$the_width = strlen($scale_name) + 2;
if($the_width < 10) $the_width = 10;
echo "<input type=\"text\" style=\"font-size:large;\" name=\"scale_name\" size=\"".$the_width."\" value=\"".$scale_name."\">";
if(is_integer(strpos($scale_name,' '))) echo " <font color=\"red\">➡</font> avoiding spaces is prefered";
echo "</h3>";
echo "<table style=\"background-color:cornsilk;\">";
echo "<tr>";
echo "<td style=\"white-space:nowrap; padding:6px; vertical-align:middle;\"><font color=\"blue\">numgrades</font> = <input type=\"text\" name=\"numgrades\" size=\"5\" value=\"".$numgrades_fullscale."\"></td>";
echo "<td rowspan=\"2\" style=\"white-space:nowrap; padding:6px; vertical-align:middle;\">";
echo "<table style=\"background-color:white;\">";
echo "<tr>";
echo "<td colspan=\"".$numgrades_with_labels."\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"modifynames\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" value=\"SAVE NEW NAMES\">&nbsp;Modify the names of these notes:</td>";
echo "</tr>";
echo "<tr>";
for($j = $j_col = 0; $j < $numgrades_fullscale; $j++) {
	if($name[$j] == '') continue;
	if($j_col >= 12) {
		$j_col = 0;
		echo "</tr><tr>";
		}
	$j_col++;
	echo "<td style=\"text-align:center;\">";
	if($key[$j] > 0) echo "<font color=\"MediumTurquoise\"><b>".$key[$j]."</b></font><br />";
	$the_width = strlen($name[$j]);
	if($the_width < 5) $the_width = 5;
	echo "<input style=\"text-align:center;\" type=\"text\" name=\"new_name_".$j."\" size=\"".$the_width."\" value=\"".$name[$j]."\">";
	echo "</td>";
	}
echo "</tr>"; 
echo "</table>";
echo "</td>";
echo "</tr><tr>";
echo "<td style=\"white-space:nowrap; padding:6px; vertical-align:middle;\"><font color=\"blue\">interval</font> = <input type=\"text\" name=\"interval\" size=\"6\" value=\"".$interval."\">";
$cents = round(cents($interval),1);
echo " or <input type=\"text\" name=\"interval_cents\" size=\"6\" value=\"".$cents."\"> cents (typically 1200)";
store($h_image,"interval_cents",$cents);
echo "</td>";
echo "</tr><tr>";
echo "<td style=\"white-space:nowrap; padding:6px; vertical-align:middle;\"><font color=\"blue\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\">";
echo "&nbsp;&nbsp;<font color=\"blue\">baseoctave</font> = <input type=\"text\" name=\"baseoctave\" size=\"5\" value=\"".$baseoctave."\"></td>";
echo "<td style=\"padding:6px; vertical-align:middle;\"><font color=\"blue\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"7\" value=\"".$basefreq."\"> Hz.<br />This is the frequency for fraction 1/1, assuming a 440 Hz diapason.";
if($ratio[0] <> 1 AND $name[0] <> '') echo "<br />Here, the frequency of <font color=\"blue\">‘".$name[0]."’</font> would be <font color=\"red\">".round(($basefreq * $ratio[0]),2)."</font> Hz if it is the <i>block key</i>.";
echo "</td>";
echo "</tr></table>";

echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savethisfile\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" value=\"SAVE “".$filename."”\"></p>";

if(isset($_POST['p_comma']) AND isset($_POST['q_comma'])) {
	$p_comma = $_POST['p_comma'];
	$q_comma = $_POST['q_comma'];
	if(($p_comma * $q_comma) > 0) $syntonic_comma = cents($p_comma/$q_comma);
	}
else if(isset($_POST['syntonic_comma'])) $syntonic_comma = $_POST['syntonic_comma'];
if(round($syntonic_comma,3) == 21.506 AND ($p_comma * $q_comma) == 0) {
	$p_comma = 81; $q_comma = 80;
	}

store($h_image,"syntonic_comma",$syntonic_comma);
store($h_image,"p_comma",$p_comma);
store($h_image,"q_comma",$q_comma);
	
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(!isset($series[$i])) $series[$i] = '';
	$series[$i] = update_series($p[$i],$q[$i],$series[$i]);
	}

echo "<div id=\"topcomma\"></div>";
if(isset($_POST['change_comma']) AND isset($_POST['list_sensitive_notes']) AND $_POST['list_sensitive_notes'] <> '') {
	if(isset($_POST['new_p_comma']) AND isset($_POST['new_q_comma']) AND $_POST['new_p_comma'] > 0  AND $_POST['new_q_comma'] > 0) {
		$new_p_comma = $_POST['new_p_comma'];
		$new_q_comma = $_POST['new_q_comma'];
		$gcd = gcd($new_p_comma,$new_q_comma);
		$new_p_comma = $new_p_comma / $gcd;
		$new_q_comma = $new_q_comma / $gcd;
		$new_comma = cents($new_p_comma/$new_q_comma);
		}
	else if(isset($_POST['new_comma']) AND is_numeric($_POST['new_comma'])) {
		$new_comma = trim($_POST['new_comma']);
		$new_p_comma = $new_q_comma = 0;
		}
	else $new_comma = $syntonic_comma;
	
	$list_sensitive_notes = $_POST['list_sensitive_notes'];
	$list_wolffifth_notes = $_POST['list_wolffifth_notes'];
	$list_wolffourth_notes = $_POST['list_wolffourth_notes'];
	if($list_sensitive_notes <> '') {
		$table_sensitive_notes = explode(' ',$list_sensitive_notes);
		echo "<p>Sensitive notes: ";
		for($i = 0; $i < count($table_sensitive_notes); $i++) echo "<font color=\"blue\">".$name[$table_sensitive_notes[$i]]."</font> ";
		echo "<br />";
		}
	if($list_wolffifth_notes <> '') {
		$table_wolffifth_notes = explode(' ',$list_wolffifth_notes);
		echo "Wolffifth notes: ";
		for($i = 0; $i < count($table_sensitive_notes); $i++) echo "<font color=\"blue\">".$name[$table_wolffifth_notes[$i]]."</font> ";
		echo "</p>"; 
		}
	if($list_wolffourth_notes <> '') {
		$table_wolffourth_notes = explode(' ',$list_wolffourth_notes);
		echo "Wolffourth notes: ";
		for($i = 0; $i < count($table_sensitive_notes); $i++) echo "<font color=\"blue\">".$name[$table_wolffourth_notes[$i]]."</font> ";
		echo "</p>"; 
		}
	if($new_comma < 0 OR $new_comma > 56.8) echo "<p>➡ Cannot set syntonic comma to: <font color=\"red\">".$new_comma."</font> cents because it should stay in range 0 ... 56.8 cents</p>";
	else {
		if(round($new_comma,1) <> round($syntonic_comma,1)) {
			if($new_comma < $syntonic_comma) $change_str = "lowering";
			else $change_str = "raising";
			if(($new_q_comma * $new_p_comma * $q_comma * $p_comma) > 0)
				$comma_ratio = $new_p_comma * $q_comma / $new_q_comma / $p_comma;
			else $comma_ratio = exp(($new_comma - $syntonic_comma) / 1200 * log(2));
			$changed_ratio = array();
			if($list_wolffifth_notes <> '') {
				echo "<p>➡ Changed value of comma to: <b><font color=\"red\">".round($new_comma,1)."</font></b> cents by ".$change_str." notes (ratio ".round($comma_ratio,4)."):<br />";
				for($i = 0; $i < count($table_wolffifth_notes); $i++) {
					$wolffifth_note = $table_wolffifth_notes[$i];
					change_ratio_in_harmonic_cycle_of_fifths($wolffifth_note,$comma_ratio,$numgrades_fullscale);
					}
				echo "</p>";
				}
				
			if($change_str == "lowering") $change_str = "raising";
			else $change_str = "lowering";
			$comma_ratio = 1./ $comma_ratio;
			if($list_sensitive_notes <> '') {
				if($list_wolffifth_notes == '')
					echo "<p>➡ Changed value of comma to: <b><font color=\"red\">".round($new_comma,1)."</font></b> cents by ".$change_str." notes (ratio ".round($comma_ratio,4)."):<br />";
				else echo "<p>and ".$change_str." notes (ratio ".round($comma_ratio,4)."):<br />";
				for($i = 0; $i < count($table_sensitive_notes); $i++) {
					$sensitive_note = $table_sensitive_notes[$i];
					change_ratio_in_harmonic_cycle_of_fifths($sensitive_note,$comma_ratio,$numgrades_fullscale);
					}
				echo "</p>";
				}
			if(($new_p_comma * $new_q_comma) > 0) {
				$p_comma = $new_p_comma;
				$q_comma = $new_q_comma;
				$syntonic_comma = cents($p_comma/$q_comma);
				}
			else {
				$p_comma = $q_comma = 0;
				$syntonic_comma = $new_comma;
				}
			store($h_image,"syntonic_comma",$syntonic_comma);
			store($h_image,"p_comma",$p_comma);
			store($h_image,"q_comma",$q_comma);
			}
		}
	}

echo "<h2 id=\"toptable\">Ratios and names of tonal scale <font color=\"blue\">“".$scale_name."”</font></h2>";

if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) {
	echo "<div  style=\"overflow-x:scroll;\">";
	echo "<table style=\"background-color:white;\">";
	$somespace = "<td style=\"width:3em;\">&nbsp;</td>";
	}
else {
	echo "<table style=\"background-color:white; table-layout:fixed; width:100%;\">";
	$somespace = "<td style=\"width:1em;\">&nbsp;</td>";
	}

echo "<tr><td style=\"padding-top:4px; padding-bottom:4px;\" colspan=\"5\">";
if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1)
	$scroll_value = "DO NOT SCROLL THIS TABLE";
else $scroll_value = "SCROLL THIS TABLE";
echo "<input type=\"submit\" style=\"background-color:yellow; \" name=\"scroll\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."#toptable\" value=\"".$scroll_value."\">";
echo "</td></tr>";

store($h_image,"numgrades_fullscale",$numgrades_fullscale);
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">fraction</th>".$somespace;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	$p[$i] = abs($p[$i]);
	$q[$i] = abs($q[$i]);
	if(($p[$i] * $q[$i]) == 0 AND $series[$i] <> '') {
		// Try to guess closest fraction
		$the_fraction = get_fraction($ratio[$i],$series[$i]);
		if($the_fraction['found']) {
			$p[$i] = $the_fraction['p'];
			$q[$i] = $the_fraction['q'];
			$ratio[$i] = $p[$i] / $q[$i];
			}
		}
	echo "<td style=\"white-space:nowrap; background-color:cornsilk; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
	if($p[$i] == 0 OR $q[$i] == 0)
		$p_txt = $q_txt = '';
	else {
		$fraction = simplify_fraction_eliminate_schisma($p[$i],$q[$i]);
		if($fraction['p'] <> $p[$i]) {
			$p[$i] = $fraction['p'];
			$q[$i] = $fraction['q'];
			}
		$p_txt = $p[$i];
		$q_txt = $q[$i];
		}
	echo "<input type=\"text\" style=\"border:none; text-align:right;\" name=\"p_".$i."\" size=\"5\" value=\"".$p_txt."\"><b>/</b><input type=\"text\" style=\"border:none;\" name=\"q_".$i."\" size=\"5\" value=\"".$q_txt."\">";
	echo "</td>";
	store2($h_image,"p",$i,$p[$i]);
	store2($h_image,"q",$i,$q[$i]);
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">ratio<br /><small>pyth/harm</small></th>".$somespace;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(!isset($ratio[$i])) continue;
	if(($p[$i] * $q[$i]) <> 0) $ratio[$i] = $p[$i] / $q[$i];
	if($ratio[$i] == 0) $show = '';
	else $show = round($ratio[$i],3);
	store2($h_image,"ratio",$i,$show);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"ratio_".$i."\" size=\"6\" value=\"".$show."\"><br /><small>";
	if(!isset($series[$i])) $series[$i] = '';
	$series[$i] = update_series($p[$i],$q[$i],$series[$i]);
	if(($p[$i] * $q[$i]) == 0) echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"series_".$i."\" size=\"1\" value=\"".$series[$i]."\">";
	else {
		echo $series[$i];
		echo "<input type=\"hidden\" name=\"series_".$i."\" value=\"".$series[$i]."\">";
		}
	echo "</small></td>";
	store2($h_image,"series",$i,$series[$i]);
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">name</th>".$somespace;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	store2($h_image,"name",$i,$name[$i]);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:red; font-weight:bold;\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">cents</th>".$somespace;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(!isset($ratio[$i])) continue;
	if($ratio[$i] == 0) $cents = '';
	else {
		$cents = cents($ratio[$i]);
		if($cents <> '') $cents = round($cents,1);
		}
	store2($h_image,"cents",$i,$cents);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:azure;\" colspan=\"2\">";
	echo "<b>".$cents."</b>";
	echo "</td>";
	}
echo "</tr>";
if(!$warned_ratios) {
	echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">interval</th>".$somespace."<td style=\"padding:0px;\"></td>";
	for($i = 0; $i < $numgrades_fullscale; $i++) {
		if(!isset($ratio[$i+1])) continue;
		if(($ratio[$i] * $ratio[$i + 1]) == 0) $cents = '';
		else {
			$cents = cents($ratio[$i + 1] / $ratio[$i]);
			if($cents <> '') $cents = round($cents,1);
			$cents = "«—&nbsp;".$cents."c&nbsp;—»";
			}
		echo "<td style=\"white-space:nowrap; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
		echo "<font color=\"blue\">".$cents."</font>";
		echo "</td>";
		}
	echo "</tr>";
	echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">delta</th>".$somespace."<td style=\"padding:0px;\"></td>";
	for($i = 0; $i < $numgrades_fullscale; $i++) {
		echo "<td style=\"white-space:nowrap; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
		if(($p[$i+1] * $q[$i] * $q[$i+1] * $p[$i]) > 0) {
			$p_int = $p[$i+1] * $q[$i];
			$q_int = $q[$i+1] * $p[$i];
			$fraction = simplify_fraction_eliminate_schisma($p_int,$q_int);
			if($fraction['p'] <> $p_int) {
				$p_int = $fraction['p'];
				$q_int = $fraction['q'];
				}			
			echo "<small>".$p_int."/".$q_int."</small>";
			}
		else {
			if(($ratio[$i + 1] * $ratio[$i]) > 0)
				echo "<small>".round(($ratio[$i + 1] / $ratio[$i]),3)."</small>";
			}
		echo "</td>";
		}
	echo "</tr>";
	}

echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">key&nbsp;<input title=\"Reset key numbers of labeled notes starting with basekey\" style=\"background-color:yellow;\" type=\"submit\" name=\"fixkeynumbers\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" value=\"fix\"></th>".$somespace;
$this_key = $basekey;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:cornsilk;\" colspan=\"2\">";
	if(isset($key[$i]) AND $key[$i] > 0)
		$this_key = $thekey = $key[$i];
	else {
		if(!isset($key[$i])) $thekey = $this_key;
		else $thekey = '';
		$this_key++;
		}
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:MediumTurquoise; font-weight:bold; font-size:large;\" name=\"key_".$i."\" size=\"6\" value=\"".$thekey ."\">";
	echo "</td>";
	}
$key[0] = $basekey;
echo "</tr>";
echo "</table>";
if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) echo "</div>";

echo "<table style=\"background-color:white;\" id=\"topconvention\">";
echo "<tr>";
echo "<td>";

$new_scale_name = $transpose_scale_name = $error_create = $error_transpose = $transpose_from_note = $transpose_to_note = '';

$list_of_limits = list_of_good_positions($interval,$p_comma,$q_comma,$syntonic_comma);
sort($list_of_limits);
// for($i = 0; $i < count($list_of_limits); $i++) echo round($list_of_limits[$i])."<br />";

$cents_interval = cents($interval);
$high_once = $low_once = FALSE;
if(!$warned_ratios) {
	echo "<p>";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($ratio[$j] == '' OR $ratio[$j] < 1 OR $ratio[$j] > $interval) continue;
		$position = round(cents($ratio[$j]));
		while($position < 0) $position += $cents_interval;
		while($position > $cents_interval) $position -= $cents_interval;
		for($i = 0; $i < count($list_of_limits); $i++) {
			if($list_of_limits[$i] > $position) {
				$range_interval = $list_of_limits[$i] - $list_of_limits[$i-1];
				$high_interval = abs($list_of_limits[$i] - $position);
				$low_interval = abs($position - $list_of_limits[$i-1]);
				$gap = 0;
				if($range_interval > ($syntonic_comma + 4) AND $low_interval > 4 AND $high_interval > 4) {
					if($low_interval > $high_interval) {
						$high = FALSE; $low_once = TRUE; $gap = $high_interval;
						}
					else {
						$high = $high_once = TRUE; $gap = $low_interval;
						}
					echo "<font color=\"red\">➡</font>&nbsp;Position ";
					if($name[$j] <> '') echo "<font color=\"blue\">‘".$name[$j]."’</font> ";
					if(($p[$j] * $q[$j]) <> 0) echo $p[$j]."/".$q[$j];
					else echo round($ratio[$j],3);
					echo " outside Pyth/harm cycles of fifths (too ";
					if($high) echo "high";
					else echo "low";
					echo " by ".round($gap,1)." cents)<br />";
					}
				break;
				}
			}
		}
	echo "</p>";
	if($low_once AND $high_once)
		echo "<p><font color=\"red\">➡</font> This scale cannot be aligned to Pythagorean/harmonic cycles of fifths because it contains both too high and too low positions</p>";
	else {
		if($low_once OR $high_once) {
			echo "<p><font color=\"red\">➡</font>&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptable\" name=\"adjustscale\" value=\"ADJUST SCALE\"> to Pyth/harm series ";
			if($high_once) {
				echo "lowering";
				$p_adjust = $q_comma;
				$q_adjust = $p_comma;
				$cents_adjust = - $syntonic_comma;
				}
			else {
				echo "raising";
				$p_adjust = $p_comma;
				$q_adjust = $q_comma;
				$cents_adjust = $syntonic_comma;
				}
			echo " labeled positions by ".round($syntonic_comma,1)." cents</p>";
			echo "<input type=\"hidden\" name=\"p_adjust\" value=\"".$p_adjust."\">";
			echo "<input type=\"hidden\" name=\"q_adjust\" value=\"".$q_adjust."\">";
			echo "<input type=\"hidden\" name=\"cents_adjust\" value=\"".$cents_adjust."\">";
			}
		}
	}

if(isset($_POST['change_convention']) AND isset($_POST['new_convention'])) {
	$new_convention = $_POST['new_convention'];
	$done = FALSE;
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
		case '3':
			$this_key = $basekey;
			for($i = 0; $i <= 13; $i++) {
				$standard_note[$i] = $KeyString.($this_key++);
				}
			break;
		}
	if($new_convention == 3) {
		echo "<font color=\"red\">";
		for($i = 0; $i <= 12; $i++) {
			echo "<input type=\"hidden\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\">";
			echo $standard_note[$i]." ";
			}
		echo "</font><br />";
		}
	else {
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
		}
	echo "&nbsp;<input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
	echo "&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"use_convention\" value=\"USE THIS CONVENTION\">";
	echo "<hr>";
	}
	
if($done AND !$warned_ratios) {
	echo "<hr><table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"change_convention\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#topconvention\" value=\"CHANGE NOTE CONVENTION\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"3\">Key numbers<br />";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<hr><table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"interpolate\" value=\"INTERPOLATE\"></td><td>➡ Replace missing ratio values with equal intervals (local temperament)</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"equalize\" value=\"EQUALIZE INTERVALS\"></td><td>Over note sequence e.g. “C, G, E” etc. separated by commas:<br />(New notes may be created)<br /><input type=\"text\" name=\"names_notes_equalize\" size=\"60\" value=\"".$names_notes_equalize."\"><br />";
	echo "<input type=\"checkbox\" name=\"ignore_unlabeled\">Hide unlabeled positions<br />";
	echo "Approximate fraction of each step = <input type=\"text\" name=\"p_equalize\" size=\"6\" value=\"".$p_equalize."\">/<input type=\"text\" name=\"q_equalize\" size=\"6\" value=\"".$q_equalize."\">";
	if($error_equalize <> '') echo "<font color=\"red\">".$error_equalize."</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	}

if($done AND $numgrades_with_labels > 2 AND !$warned_ratios) {
	if(isset($_POST['reduce']) AND isset($_POST['scale_choice']) AND isset($_POST['reduce_scale_name']) AND trim($_POST['reduce_scale_name']) <> '') {
		$scale_choice = $_POST['scale_choice'];
		$name_sensitive_note = '';
		if($scale_choice == "full_scale") $full_scale = TRUE;
		else $full_scale =  FALSE;
		$preserve_numbers = isset($_POST['preserve_numbers']);
		if($full_scale) $numgrades = $numgrades_fullscale;
		else {
			$selected_grades = trim($_POST['selected_grades']);
			$selected_grades = preg_replace("/\s+/u",' ',$selected_grades);
			$selected_grade_name = explode(' ',$selected_grades);
			$numgrades = count($selected_grade_name) - 1;
			$done_grade = $new_selected_grade_name = array();
			for($i = 0; $i <= $numgrades; $i++) {
				$some_name = $selected_grade_name[$i];
				if($some_name == '-') $some_name = $selected_grade_name[$i] = '•';
				if($some_name == '•' OR !isset($done_grade[$some_name])) {
					if($i > 0) $done_grade[$some_name] = TRUE;
					$new_selected_grade_name[] = $some_name;
					$found = FALSE;
					for($j = 0; $j < $numgrades_fullscale; $j++) {
						if(($name[$j] == $some_name) OR $some_name == '•') {
							$found = TRUE; break;
							}
						}
					if(!$found) $error_create .= "<br /><font color=\"red\"> ➡ ERROR: This note</font> <font color=\"blue\">‘".$some_name."’</font> <font color=\"red\">does not belong to the current scale</font>";
					}
			//	else $numgrades--;
				}
			$selected_grade_name = $new_selected_grade_name;
			$numgrades = count($selected_grade_name) - 1;
			}
		$new_scale_name = trim($_POST['reduce_scale_name']);
		$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
		$new_scale_file = $new_scale_name.".txt";
		$old_scale_file = $new_scale_name.".old";
		$result1 = check_duplicate_name($dir_scales,$new_scale_file);
		$result2 = check_duplicate_name($dir_scales,$old_scale_file);
		if($result1 OR $result2) {
			$error_create = "<br /><font color=\"red\"> ➡ ERROR: This name</font> <font color=\"blue\">‘".$new_scale_name."’</font> <font color=\"red\">already exists</font>";
			}
		if($error_create == '') {
			if(isset($_POST['major_minor'])) {
				$new_scale_mode = $_POST['major_minor'];
				$name_sensitive_note = trim($_POST['name_sensitive_note']);
				if($name_sensitive_note <> '' AND $new_scale_mode == "none") {
					$error_create = "<br /><font color=\"red\"> ➡ Select option for raising/lowering sensitive note</font> <font color=\"blue\">‘".$name_sensitive_note."’</font>";
					}
				if($new_scale_mode <> "none") {
					if($name_sensitive_note == '') {
						$error_create = "<br /><font color=\"red\"> ➡ A sensitive note should be specified for the major/minor adjustment</font>";
						}
					else {
						$j_sensitive = -1;
						for($j = 0; $j < $numgrades_fullscale; $j++)
							if($name[$j] == $name_sensitive_note) $j_sensitive = $j;
						if($j_sensitive < 0)
							$error_create = "<br /><font color=\"red\"> ➡ Name of sensitive note is unknown</font>";
						else {
							$p_align = $q_align = $ratio_align = 1;
							// The tonic is a minor wholetone lower than the sensitive note
							$ratio_tonic = $ratio[$j_sensitive] * $q_major_whole_tone / $p_major_whole_tone * exp($syntonic_comma / 1200 * log(2));
							if($ratio_tonic < $ratio[0]) $ratio_tonic = $ratio_tonic * $interval;
							$cents_tonic = cents($ratio_tonic);
					//		echo "ratio_tonic = ".$ratio_tonic."<br />";
					//		echo "cents_tonic = ".$cents_tonic."<br />";
							$j_tonic = -1;
							for($j = 0; $j < $numgrades_fullscale; $j++) {
								if($name[$j] == '' OR $name[$j] == '•') continue;
								if(abs($cents_tonic - cents($ratio[$j])) < 10) $j_tonic = $j;
								}
					//		echo "j_tonic = ".$j_tonic."<br />";
							if(!$full_scale AND $j_tonic >= 0 AND $new_scale_mode == "major") {
								if($series[$j_tonic] == 'h') {
									echo "<p><font color=\"red\">➡</font>&nbsp;Labeled positions have been raised by 1 comma to put the tonic <font color=\"blue\">‘".$name[$j_tonic]."’</font> into Pythagorean series of fiths</p>";
									$p_align = $p_comma;
									$q_align = $q_comma;
									if(($p_comma * $q_comma) == 0) {
										$p_align = 1000 * exp($syntonic_comma / 1200 * log(2));
										$q_align = 1000;
										}
									$ratio_align = $p_align / $q_align;
									}
								}
							if(!$full_scale AND $j_tonic >= 0 AND $new_scale_mode == "minor") {
								if($series[$j_tonic] == 'p') {
									echo "<p><font color=\"red\">➡</font>&nbsp;Labeled positions have been lowered by 1 comma to put the tonic <font color=\"blue\">‘".$name[$j_tonic]."’</font> into harmonic series of fiths</p>";
									$p_align = $q_comma;
									$q_align = $p_comma;
									if(($p_comma * $q_comma) == 0) {
										$p_align = 1000 * exp((-$syntonic_comma) / 1200 * log(2));
										$q_align = 1000;
										}
									$ratio_align = $p_align / $q_align;
									}
								}
							$p_adjust = $p_comma;
							$q_adjust = $q_comma;
							if(($p_comma * $q_comma) == 0) {
								$p_adjust = 1000 * exp($syntonic_comma / 1200 * log(2));
								$q_adjust = 1000;
								}
							for($j = 0; $j < $numgrades_fullscale; $j++) {
								if($j == $j_sensitive) {
									if($new_scale_mode == "major") {
										$p_transpose = $p[$j] * $p_adjust;
										$q_transpose = $q[$j] * $q_adjust;
										$ratio_transpose = round($ratio[$j] * $p_adjust / $q_adjust,3);
										}
									else {
										$p_transpose = $p[$j] * $q_adjust;
										$q_transpose = $q[$j] * $p_adjust;
										$ratio_transpose = round($ratio[$j] * $q_adjust / $p_adjust,3);
										}
									break;
									}
								}
							if($j >= $numgrades_fullscale)
								$error_create = "<br /><font color=\"red\"> ➡ ERROR: Sensitive note <font color=\"blue\">‘".$name_sensitive_note."’</font> <font color=\"red\">was not found in this scale</font>";
							else {
								if(($p_transpose * $q_transpose) <> 0) {
									$gcd = gcd($p_transpose,$q_transpose);
									$p_transpose = $p_transpose / $gcd;
									$q_transpose = $q_transpose / $gcd;
									}
								$fraction = simplify_fraction_eliminate_schisma($p_transpose,$q_transpose);
								if($fraction['p'] <> $p_transpose) {
									echo "=> ".$p_transpose."/".$q_transpose." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
									$p_transpose = $fraction['p'];
									$q_transpose = $fraction['q'];
									$ratio_transpose = round($p_transpose/$q_transpose,3);
									}
								$found = FALSE;
								$j_transpose = -1;
								for($j = 0; $j <= $numgrades_fullscale; $j++) {  // $$$$$   REVISE???
									if($name[$j] <> '' AND $name[$j] <> '•') {
										$p[$j] = $p[$j] * $p_align;
										$q[$j] = $q[$j] * $q_align;
										$ratio[$j] = $ratio[$j] * $ratio_align;
										if(($p[$j] * $q[$j]) <> 0) {
											$gcd = gcd($p[$j],$q[$j]);
											$p[$j] = $p[$j] / $gcd;
											$q[$j] = $q[$j] / $gcd;
											}
										$fraction = simplify_fraction_eliminate_schisma($p[$j],$q[$j]);
										if($fraction['p'] <> $p[$j]) {
											echo "=> ".$p[$j]."/".$q[$j]." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
											$p[$j] = $fraction['p'];
											$q[$j] = $fraction['q'];
											}
										}
									if($name[$j] == $name_sensitive_note) $name[$j] = '•';
									if($found) continue;
									if((round($ratio[$j],3) >= $ratio_transpose) OR ($ratio[$j] < 1 AND $ratio_transpose == 1.0)) {
										if($j > 0 AND $name[$j] <> '' AND $name[$j] <> '•') $j--;
										if($name[$j] <> '') {
											$p[$j] = $p_transpose * $p_align;
											$q[$j] = $q_transpose * $q_align;
											$ratio[$j] = $ratio_transpose * $ratio_align;
											}
										else {
											$p[$j] = $p_transpose;
											$q[$j] = $q_transpose;
											$ratio[$j] = $ratio_transpose;
											}
										$name[$j] = $name_sensitive_note;
										$j_transpose = $j;
										$found = TRUE;
										}
									}
								if($j_transpose == 0) {
								//	echo "@@@ ".$p[0]." ".$p_transpose."<br />";
									$p_transpose = $interval * $p_transpose;
									if(($p_transpose * $q_transpose) <> 0) {
										$gcd = gcd($p_transpose,$q_transpose);
										$p_transpose = $p_transpose / $gcd;
										$q_transpose = $q_transpose / $gcd;
										}
									$fraction = simplify_fraction_eliminate_schisma($p_transpose,$q_transpose);
									if($fraction['p'] <> $p_transpose) {
										echo "=> ".$p_transpose."/".$q_transpose." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
										$p_transpose = $fraction['p'];
										$q_transpose = $fraction['q'];
										}
									$p[$numgrades_fullscale] = $p_transpose * $p_align;
									$q[$numgrades_fullscale] = $q_transpose * $q_align;
									$ratio[$numgrades_fullscale] = $interval * $ratio_transpose * $ratio_align;
									$name[$numgrades_fullscale] = $name_sensitive_note;
									}
								}
							}
						}
					}
				if($error_create == '') {
					echo "<p><font color=\"red\">Exported to</font> <font color=\"blue\">‘".$new_scale_name."’</font> <input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"edit_new_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($new_scale_name)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$new_scale_name."’\"></p>";
					$handle = fopen($dir_scales.$new_scale_file,"w");
					fwrite($handle,"\"".$new_scale_name."\"\n");
					$comma_line = $syntonic_comma;
					if(($p_comma * $q_comma) > 0) $comma_line .= " ".$p_comma." ".$q_comma;
					fwrite($handle,"c".$comma_line."c\n");
					$the_notes = $the_fractions = $the_ratios = $the_numbers = '';
					for($j = $k = 0; $j <= $numgrades_fullscale; $j++) {
						if(!$full_scale) {
							if(!isset($selected_grade_name[$k])) continue;
							if($preserve_numbers AND ($selected_grade_name[$k] == '•' OR $name[$j] == $selected_grade_name[$k])) {
								if($selected_grade_name[$k] <> '•')
									$the_numbers .= ($key[$j] - $basekey)." ";
								else $the_numbers .= "0 ";
								}
							if($selected_grade_name[$k] == '•') {
								$the_notes .= "• ";
								$the_fractions .= "0 0 ";
								$j--;
								$k++;
								continue;
								}
							if($name[$j] <> $selected_grade_name[$k]) continue;
							else $k++;
							}
						else if($preserve_numbers) {
							if($name[$j] <> '')
								$the_numbers .= ($key[$j] - $basekey)." ";
							else $the_numbers .= "0 ";
							}
						if($name[$j] <> '') {
							$the_notes .= $name[$j]." ";
							}
						else $the_notes .= "• ";
						$the_fractions .= $p[$j]." ".$q[$j]." ";
						}
					$the_notes = "/".trim($the_notes)."/";
					$the_fractions = "[".trim($the_fractions)."]";
					fwrite($handle,$the_notes."\n");
					if($preserve_numbers) {
						$the_numbers = "k".trim($the_numbers)."k";
						fwrite($handle,$the_numbers."\n");
						}
					fwrite($handle,$the_fractions."\n");
					fwrite($handle,"|".$baseoctave."|\n");
					$the_scale = "f2 0 128 -51 ";
					$the_scale .= $numgrades." ".$interval." ".$basefreq." ".$basekey." ";
					for($j = $k = 0; $j <= $numgrades_fullscale; $j++) {
						if(!$full_scale) {
							if(!isset($selected_grade_name[$k])) continue;
							if($selected_grade_name[$k] == '•') {
								$the_scale .= "1.0 ";
								$j--;
								$k++; continue;
								}
							if($name[$j] <> $selected_grade_name[$k]) continue;
							else $k++;
							}
						if(($p[$j] * $q[$j]) > 0)
							$the_scale .= round($p[$j]/$q[$j],3)." ";
						else
							$the_scale .= $ratio[$j]." ";
						}
					fwrite($handle,$the_scale."\n");
					if($full_scale)
						$some_comment = "<html>This is a derivation of scale \"".$filename."\" (".$numgrades_fullscale." grades) in ‘".$csound_source."’";
					else
						$some_comment = "<html>This is a reduction to ".$numgrades." grades of scale \"".$filename."\" (".$numgrades_fullscale." grades) in ‘".$csound_source."’";
					if($new_scale_mode == "major")
						$some_comment .= " in major tonality";
					else if($new_scale_mode == "minor")
						$some_comment .= " in relative minor tonality";
					if($name_sensitive_note <> '') $some_comment .= "<br />Sensitive note = '".$name_sensitive_note."'";
					$some_comment .= "<br />Created ".date('Y-m-d H:i:s')."</html>";
					fwrite($handle,$some_comment."\n");
					fclose($handle);
					}
				}
			else $error_create .= "<br /><font color=\"red\"> ➡ ERROR: unknown ‘sensitive note’ option</font>";
			}
		}

	echo "<table><tr id=\"toptranspose\">";
	echo "<td colspan=\"2\" style=\"vertical-align:middle; padding:4px; white-space:nowrap;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptable\" name=\"reduce\" value=\"REDUCE or ADJUST\"> to create a  scale named <input type=\"text\" name=\"reduce_scale_name\" size=\"20\" value=\"".$new_scale_name."\">";
	
	echo "</td></tr><tr><td colspan=\"2\" style=\"vertical-align:middle; padding:6px; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"scale_choice\" value=\"full_scale\"";
	if($scale_choice == "full_scale") echo " checked";
	echo ">with all ".$numgrades_fullscale." grades<br />";
	if($selected_grades == '' AND $scale_choice <> "full_scale") {
		for($j = 0; $j < $numgrades_fullscale; $j++) {
			if($name[$j] == '') continue;
			$selected_grades .= $name[$j]." ";
			}
		$selected_grades .= $name[0];
		}
	$size = 5 + strlen($selected_grades);
	echo "<input type=\"radio\" name=\"scale_choice\" value=\"small_scale\"";
	if($scale_choice == "small_scale") echo " checked";
	echo ">with grades:<br /><textarea rows=\"3\" cols=\"60\" name=\"selected_grades\">";
	echo $selected_grades;
	echo "</textarea>";
	echo "<br />➡ Unnamed grades can be inserted as hyphens between spaces ‘ - ’<br />";
	echo "<input type=\"checkbox\" name=\"preserve_numbers\">Preserve key numbers";
	echo "</td>";
	if($error_create <> '') echo $error_create;
	echo "</td></tr><tr>";
	echo "<td style=\"vertical-align:middle; padding:6px; white-space:nowrap;\"><input type=\"radio\" name=\"major_minor\" value=\"none\" checked>don’t change ratios<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"major\">raise to relative major<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"minor\">lower to relative minor</td>";
	echo "<td style=\"text-align:center; vertical-align:middle; padding:4px;\"><b>Sensitive note (1 comma)</b><br /><br />➡ adjust by <font color=\"red\">";
	if(($p_comma * $q_comma) > 0) echo $p_comma."/".$q_comma."</font> (or reverse)";
	else echo round($syntonic_comma,1)."</font> cents";
	echo " this note: <input type=\"text\" name=\"name_sensitive_note\" size=\"6\" value=\"".$name_sensitive_note."\"></td>";
	echo "</tr></table><br />";
	if(isset($_POST['transpose'])) {
		$transpose_from_note = trim($_POST['transpose_from_note']);
		$transpose_to_note = trim($_POST['transpose_to_note']);
		$transposition_mode = '';
		if(isset($_POST['transposition_mode']))
			$transposition_mode = $_POST['transposition_mode'];
		else
			$error_transpose .= "<font color=\"red\"> ➡ ERROR: Transposition mode has not been selected</font><br />";
		$new_scale_name = trim($_POST['transpose_scale_name']);
		if($new_scale_name == '')
			$error_transpose .= "<font color=\"red\"> ➡ ERROR: Name of new scale has not been entered</font><br />";
		$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
		$new_scale_file = $new_scale_name.".txt";
		$old_scale_file = $new_scale_name.".old";
		$result1 = check_duplicate_name($dir_scales,$new_scale_file);
		$result2 = check_duplicate_name($dir_scales,$old_scale_file);
		if($result1 OR $result2) {
			$error_transpose .= "<font color=\"red\"> ➡ ERROR: This name</font> <font color=\"blue\">‘".$new_scale_name."’</font> <font color=\"red\">already exists</font><br />";
			}
		$p_raise = abs(intval($_POST['p_raise']));
		$q_raise = abs(intval($_POST['q_raise']));
		if($transposition_mode == "ratio" AND ($p_raise * $q_raise) == 0)
			$error_transpose .= "<font color=\"red\"> ➡ ERROR: Ratio for raising notes has not been entered</font><br />";
		if($error_transpose == '') {
			if($transposition_mode == "murcchana") {
				for($j = $jj = 0, $j_transpose_from = $j_transpose_to = -1; $j <= $numgrades_fullscale; $j++) {
					if($name[$j] == '') continue;
					if($name[$j] == $transpose_from_note) {
						$j_transpose_from = $j;
						$grade_transpose_from = $jj;
						}
					if($name[$j] == $transpose_to_note) {
						$j_transpose_to = $j;
						$grade_transpose_to = $jj;
						}
					$p_this_grade[$jj] = $p[$j];
					$q_this_grade[$jj] = $q[$j];
					$ratio_this_grade[$jj] = $ratio[$j];
					$name_this_grade[$jj] = $name[$j];
					$jj++;
					}
				if($j_transpose_from < 0)
					$error_transpose .= "<font color=\"red\"> ➡ ERROR: Transpose from note <font color=\"blue\">‘".$transpose_from_note."’</font> <font color=\"red\">was not found in this scale</font><br />";
				if($j_transpose_to < 0 AND $transposition_mode == "murcchana")
					$error_transpose .= "<font color=\"red\"> ➡ ERROR: Transpose to note <font color=\"blue\">‘".$transpose_to_note."’</font> <font color=\"red\">was not found in this scale</font><br />";
				if($error_transpose == '') {
					$p_transpose_from = $p[$j_transpose_from];
					$q_transpose_from = $q[$j_transpose_from];
					if(($q_transpose_from * $q_transpose_from) <> 0) {
						$gcd = gcd($p_transpose_from,$q_transpose_from);
						$q_transpose_from = $q_transpose_from / $gcd;
						$q_transpose_from = $q_transpose_from / $gcd;
						}
					$ratio_transpose_from = $ratio[$j_transpose_from];
					echo "<p><font color=\"MediumTurquoise\">Transposition from</font> <font color=\"blue\">‘".$transpose_from_note."’</font> fraction ".$p_transpose_from."/".$q_transpose_from." (".$grade_transpose_from."th position) ";
					$p_transpose_to = $p[$j_transpose_to];
					$q_transpose_to = $q[$j_transpose_to];
					if(($p_transpose_to * $q_transpose_to) <> 0) {
						$gcd = gcd($p_transpose_to,$q_transpose_to);
						$p_transpose_to = $p_transpose_to / $gcd;
						$q_transpose_to = $q_transpose_to / $gcd;
						}
					$ratio_transpose_to = $ratio[$j_transpose_to];
					echo "<font color=\"MediumTurquoise\">to</font> <font color=\"blue\">‘".$transpose_to_note."’</font> fraction ".$p_transpose_to."/".$q_transpose_to." (".$grade_transpose_to."th position)</p>";
					$p_transpose = $p_transpose_to * $q_transpose_from;
					$q_transpose = $q_transpose_to * $p_transpose_from;
					if(($p_transpose * $q_transpose) <> 0) {
						$gcd = gcd($p_transpose,$q_transpose);
						$p_transpose = $p_transpose / $gcd;
						$q_transpose = $q_transpose / $gcd;
						}
					$fraction = simplify_fraction_eliminate_schisma($p_transpose,$q_transpose);
					if($fraction['p'] <> $p_transpose) {
						echo "=> ".$p_transpose."/".$q_transpose." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
						$p_transpose = $fraction['p'];
						$q_transpose = $fraction['q'];
						}
					$ratio_tranpose = $ratio_transpose_to / $ratio_transpose_from;
					$grade_transpose = $grade_transpose_from - $grade_transpose_to;
					for($jj = 0; $jj <= $numgrades_with_labels; $jj++) {
						$new_j = modulo($jj + $grade_transpose,$numgrades_with_labels);
						$p_new_j = $p_this_grade[$new_j];
						$q_new_j = $q_this_grade[$new_j];
						$ratio_new_j = $ratio_this_grade[$new_j];
						$p_new = $p_new_j * $p_transpose;
						$q_new = $q_new_j * $q_transpose;
						$ratio_new = $ratio_new_j * $ratio_tranpose;
						
						if(($p_new * $q_new) > 0)
							$this_ratio = $p_new / $q_new;
						else $this_ratio = $ratio_new;
						$cents_this_grade[$jj] = cents($ratio_this_grade[$jj]);
						$cents_new = cents($this_ratio);
						if(($cents_new - $cents_this_grade[$jj]) > 50) {
							$q_new = 2 * $q_new;
							$this_ratio = $this_ratio / 2.0;
							$cents_new = cents($this_ratio);
							}
						if(($cents_this_grade[$jj] - $cents_new) > 50) {
							$p_new = 2 * $p_new;
							$this_ratio = $this_ratio * 2.0;
							}
						if(($p_new * $q_new) > 0) {
							$gcd = gcd($p_new,$q_new);
							$p_new = $p_new / $gcd;
							$q_new = $q_new / $gcd;
							$this_ratio = $p_new/$q_new;
							}
						$fraction = simplify_fraction_eliminate_schisma($p_new,$q_new);
						if($fraction['p'] <> $p_new) {
							echo "=> ".$p_new."/".$q_new." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
							$p_new = $fraction['p'];
							$q_new = $fraction['q'];
							$this_ratio = $p_new/$q_new;
							}
						$cents_new = cents($this_ratio);
						echo "<font color=\"blue\">".$name_this_grade[$jj]."</font> ratio ";
						if(($p_new * $q_new) > 0) echo $p_new."/".$q_new." = ";
						echo round($this_ratio,3);
						echo " = ".round($cents_new)." cents";
						if(($cents_new - $cents_this_grade[$jj]) > 5)
							echo " <font color=\"red\">raised by</font> ".round($cents_new - $cents_this_grade[$jj])." cents";
						if(($cents_new - $cents_this_grade[$jj]) < -5)
							echo " <font color=\"red\">lowered by</font> ".round($cents_this_grade[$jj] - $cents_new)." cents";
						if(round($this_ratio,3) <> round($ratio_this_grade[$jj],3) AND abs($cents_new - $cents_this_grade[$jj]) < 4) {
							$p_new = $p_this_grade[$jj];
							$q_new = $q_this_grade[$jj];
							$this_ratio = $ratio_this_grade[$jj];
							echo " <font color=\"MediumTurquoise\">approximated to</font> ";
							if(($p_new * $q_new) > 0) echo $p_new."/".$q_new." = ";
							echo round($this_ratio,3);
							}
						$p_new_this_grade[$jj] = $p_new;
						$q_new_this_grade[$jj] = $q_new;
						$cents_new_this_grade[$jj] = $cents_new;
						$ratio_this_grade[$jj] = $this_ratio;
						echo "<br />";
						}
						
					// Reassign positions of notes
					$new_name = array();
				//	$new_key = array();
					echo "<br />";
					for($j = 0; $j < $numgrades_fullscale; $j++) {
						$new_name[$j] = '';
					//	$new_key[$j] = $key[$j];
						$cents = cents($ratio[$j]);
					//	echo "‘".$name[$j]."’ = ".$p[$j]."/".$q[$j]." ".round($cents)." cents<br />";
						for($jj = 0; $jj <= $numgrades_with_labels; $jj++) {
							if(abs($cents - $cents_new_this_grade[$jj]) < 2) {
								if($name[$j] <> $name_this_grade[$jj]) {
									echo "➡ Note <font color=\"blue\">‘".$name_this_grade[$jj]."’</font> relocated to position ".$j."<br />";
									}
								$new_name[$j] = $name_this_grade[$jj];
								$p[$j] = $p_new_this_grade[$jj];
								$q[$j] = $q_new_this_grade[$jj];
								$ratio[$j] = $ratio_this_grade[$jj];
							//	$new_key[$j] = $key[$jj];
								break;
								}
							}
						}
					// Assign notes outside grama locations
					$jold = 0;
					for($jj = 0; $jj <= $numgrades_with_labels; $jj++) {
						$search_name = $name_this_grade[$jj];
						$found = FALSE;
						for($j = $jold; $j < $numgrades_fullscale; $j++) {
							if($new_name[$j] == $search_name) {
								$found = TRUE; break;
								}
							}
						if(!$found) {
							$minimum_dist = 1200;
							$closest_j = -1;
							$search_cents = round($cents_new_this_grade[$jj]);
							for($j = $jold; $j <= $numgrades_fullscale; $j++) {
							//	$cents = 1200 * log($ratio[$j]) / log(2);
								$cents = cents($ratio[$j]);
								$dist = abs($cents - $search_cents);
								if($dist < $minimum_dist) {
									$minimum_dist = $dist;
									$closest_j = $j;
									}
								}
							if($closest_j >= 0) {
								$new_name[$closest_j] = $search_name;
							//	$new_key[$closest_j] = $key[$jj];
								$p[$closest_j] = $p_new_this_grade[$jj];
								$q[$closest_j] = $q_new_this_grade[$jj];
								$old_ratio = round($ratio_this_grade[$jj],3);
								$ratio[$closest_j] = $ratio_this_grade[$jj];
								echo "➡ Assigned location of ‘".$search_name."’ to ".$closest_j."th position";
								if(round($ratio[$closest_j],3) <> $old_ratio)
									echo " with fraction ".$p[$closest_j]."/".$q[$closest_j]." = ".round($ratio[$closest_j],3);
								echo "<br />";
								$jold = $closest_j + 1;
								}
							}
						}
					for($j = 0; $j < $numgrades_fullscale; $j++) {
						$name[$j] = $new_name[$j];
						// $key[$j] = $new_key[$j];
						}
					}
				}
			else {
				// Transpose by raising all notes
				$this_ratio = $p_raise / $q_raise;
				echo "<p><font color=\"MediumTurquoise\">Transposition</font> by raising all notes, fraction = ".$p_raise."/".$q_raise." = ".round($this_ratio,3)."</p>";
				for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
				//	if($name[$j] == '') continue;
					$new_ratio = $this_ratio * $ratio[$j];
					if(($p[$j] * $q[$j]) <> 0) {
						$p_new = $p[$j] * $p_raise;
						$q_new  = $q[$j] * $q_raise;
						if(($p_new * $q_new) > 0) {
							$gcd = gcd($p_new,$q_new);
							$p_new = $p_new / $gcd;
							$q_new = $q_new / $gcd;
							}
						$fraction = simplify_fraction_eliminate_schisma($p_new,$q_new);
						if($fraction['p'] <> $p_new) {
							echo "=> ".$p_new."/".$q_new." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
							$p_new = $fraction['p'];
							$q_new = $fraction['q'];
							}
						$new_ratio = $p_new / $q_new;
						while($new_ratio >= $interval AND !($j == $numgrades_fullscale AND $new_ratio == $interval)) {
							$q_new = $q_new * $interval;
							$gcd = gcd($p_new,$q_new);
							$p_new = $p_new / $gcd;
							$q_new = $q_new / $gcd;
							$new_ratio = $new_ratio / $interval;
							}
						while($new_ratio < (1. / $interval) AND !($j == 0 AND $new_ratio == 1/$interval)) {
							$p_new = $p_new * $interval;
							$gcd = gcd($p_new,$q_new);
							$p_new = $p_new / $gcd;
							$q_new = $q_new / $gcd;
							$new_ratio = $new_ratio * $interval;
							}
						$p[$j] = $p_new;
						$q[$j] = $q_new;
						}
					else {
						while($new_ratio >= $interval AND !($j == $numgrades_fullscale AND $new_ratio == $interval))
							$new_ratio = $new_ratio / $interval;
						while($new_ratio < (1. / $interval) AND !($j == 0 AND $new_ratio == 1/$interval))
							$new_ratio = $new_ratio * $interval;
						}
					$ratio[$j] = $new_ratio;
					echo "<font color=\"blue\">‘".$name[$j]."’</font> new ratio = ".round($new_ratio,3);
					if(($p[$j] * $q[$j]) <> 0) echo " = ".$p_new."/".$q_new;
					echo "<br />";
					}
				}
			
			// Now save to exported file	
			echo "<br />";
			echo "<font color=\"MediumTurquoise\">Saved to new scale</font> <font color=\"blue\">‘".$new_scale_name."’</font>&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"edit_new_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($new_scale_name)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$new_scale_name."’\"></p>";
			$transpose_scale_name = $new_scale_name;
			$handle = fopen($dir_scales.$new_scale_file,"w");
			fwrite($handle,"\"".$new_scale_name."\"\n");
			$comma_line = $syntonic_comma;
			if(($p_comma * $q_comma) > 0) $comma_line .= " ".$p_comma." ".$q_comma;
			fwrite($handle,"c".$comma_line."c\n");
			$the_notes = $the_fractions = $the_ratios = '';
			for($j = 0; $j <= $numgrades_fullscale; $j++) {
				if($name[$j] <> '') {
					$the_notes .= $name[$j]." ";
					}
				else {
					$the_notes .= "• ";
					}
				$the_fractions .= $p[$j]." ".$q[$j]." ";
				}
			$the_notes = "/".trim($the_notes)."/";
			$the_fractions = "[".trim($the_fractions)."]";
			fwrite($handle,$the_notes."\n");
			fwrite($handle,$the_fractions."\n");
			fwrite($handle,"|".$baseoctave."|\n");
			$the_scale = "f2 0 128 -51 ";
			$the_scale .= $numgrades_fullscale." ".$interval." ".$basefreq." ".$basekey." ";
			for($j = 0; $j <= $numgrades_fullscale; $j++) {
				if(($p[$j] * $q[$j]) > 0)
					$the_scale .= round($p[$j]/$q[$j],3)." ";
				else
					$the_scale .= $ratio[$j]." ";
				}
			fwrite($handle,$the_scale."\n");
			$some_comment = "<html>This is a transposition of scale \"".$filename."\" (".$numgrades_fullscale." grades).<br />";
			if($transposition_mode == "murcchana")
				$some_comment .= "From ‘".$transpose_from_note."’ to ‘".$transpose_to_note."’.<br />";
			else // ratio
				$some_comment .= "All positions raised by fraction ".$p_raise."/".$q_raise.".<br />";
			$some_comment .= "Created ".date('Y-m-d H:i:s')."</html>";
			fwrite($handle,$some_comment."\n");
			fclose($handle);
			$transpose_from_note = $transpose_to_note  = '';
			}
		}
	echo "<table><tr>";
	echo "<td style=\"vertical-align:middle; padding:4px;\">";
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptranspose\" name=\"transpose\" value=\"TRANSPOSITION\"> to create a  scale named <input type=\"text\" name=\"transpose_scale_name\" size=\"20\" value=\"\"><br />";
	echo "</td></tr><tr><td style=\"vertical-align:middle; padding:4px;\">";
	echo "<input type=\"radio\" name=\"transposition_mode\" value=\"murcchana\"";
	if($transposition_mode == "murcchana") echo " checked";
	echo "><b>&nbsp;Murcchana</b><br />";
	echo "Move note <input type=\"text\" name=\"transpose_from_note\" size=\"4\" value=\"".$transpose_from_note."\"> to note <input type=\"text\" name=\"transpose_to_note\" size=\"4\" value=\"".$transpose_to_note."\"> of this basic scale (<i>grama</i>)<br /><i>Example: On a Ma-grama scale model, move ‘C’ to ‘Eb’ (32/27)<br />to create the minor chromatic scale of same tonality<br />or one perfect fifth down to create the next chromatic scale in the series</i>";
	echo "</td></tr><tr><td style=\"vertical-align:middle; padding:4px;\">";
	echo "<input type=\"radio\" name=\"transposition_mode\" value=\"ratio\"";
	if($transposition_mode == "ratio") echo " checked";
	if($p_raise == 0) $p_raise = $q_raise = '';
	echo "><b>&nbsp;Raise all positions</b> by fraction <input type=\"text\" name=\"p_raise\" size=\"6\" value=\"".$p_raise."\"><b> / </b><input type=\"text\" name=\"q_raise\" size=\"6\" value=\"".$q_raise."\">";
	if($error_transpose <> '') echo "<br /><br />".$error_transpose;
	echo "</td>";
	echo "</tr></table><br />";
	
	echo "<table><tr><td style=\"vertical-align:middle; padding:4px;\">";
	
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptranspose\" name=\"modifynote\" value=\"MODIFY NOTE\">&nbsp;";
	echo "<input type=\"text\" name=\"raised_note\" size=\"5\" value=\"".$raised_note."\"> by fraction <input type=\"text\" name=\"p_raised_note\" size=\"4\" value=\"".$p_raised_note."\"><b> / </b><input type=\"text\" name=\"q_raised_note\" size=\"3\" value=\"".$q_raised_note."\"> or <input type=\"text\" name=\"cents_raised_note\" size=\"6\" value=\"".$cents_raised_note."\"> cents";
	if($error_raise_note <> '') echo "<br />".$error_raise_note;
	
	echo "</td></tr><tr><td style=\"vertical-align:middle; padding:4px;\">";
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptranspose\" name=\"resetbase\" value=\"RESET BASE OF SCALE\"> to note <input type=\"text\" name=\"resetbase_note\" size=\"6\" value=\"".$q_raise."\">";
	if($error_resetbase <> '') echo "<font color=\"red\">".$error_resetbase."</font>";
	
	if($ratio[0] <> 1.0) {
		echo "</td></tr><tr><td style=\"vertical-align:middle; padding:4px;\">";
		echo "<font color=\"red\">➡</font>&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptranspose\" name=\"alignscale\" value=\"ALIGN SCALE\">&nbsp;to the position of “".$name[0]."” &nbsp;";
		if($ratio[0] > 1) echo "lowering&nbsp;";
		else echo "raising&nbsp;";
		echo "all notes by <font color=\"red\">".abs(round(cents($ratio[0]),1))." cents</font>";
		}
	echo "</td>";
	echo "</tr></table>";
	}

if($done AND !$warned_ratios) {
	echo "<hr>";
	echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"create_meantone\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptable\" value=\"CREATE MEANTONE\"> temperament scale (<a target=\"_blank\" href=\"https://en.wikipedia.org/wiki/Meantone_temperament\">follow this link</a>) with the following data:";
	if($error_meantone <> '') echo "<font color=\"red\">".$error_meantone."</font>";
	echo "</p>";
	echo "<ul>";
	echo "<li>Start from note with name: <input type=\"text\" name=\"note_start_meantone\" size=\"6\" value=\"".$note_start_meantone."\">";
	if($name[0] <> '' AND $name[0] <> '•') echo " (typically ‘".$name[0]."’)";
	echo "</li>";
	echo "<li>List of note names separated by commas, including the starting note, e.g. “C, G, E” etc.:<br /><input type=\"text\" name=\"names_notes_meantone\" size=\"80\" value=\"".$names_notes_meantone."\"></li>";
	echo "<li><input type=\"checkbox\" name=\"ignore_unlabeled\">Hide unlabeled positions</li>";
	echo "<li>Fraction of each step <input type=\"text\" name=\"p_step_meantone\" size=\"3\" value=\"".$p_step_meantone."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_step_meantone\" size=\"3\" value=\"".$q_step_meantone."\"> (typically 3/2 for fifths or 5/4 for thirds)</li>";
	echo "<li>Add <input type=\"text\" name=\"p_fraction_comma\" size=\"3\" value=\"".$p_fraction_comma."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_fraction_comma\" size=\"3\" value=\"".$q_fraction_comma."\"> comma to each step (can be negative, for instance -2/7)</li>";
	echo "<li><input type=\"radio\" name=\"meantone_direction\" value=\"up\">Up (ascending steps)<br />";
	echo "<input type=\"radio\" name=\"meantone_direction\" value=\"down\">Down (descending steps)</li>";
	echo "</ul>";
	echo "<hr>";
	
	echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"add_fifths_up\" value=\"ADD SERIES\"> of <b>ASCENDING</b> fifths<br /><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"add_fifths_down\" value=\"ADD SERIES\"> of <b>DESCENDING</b> fifths";
	if($error_fifths <> '') echo "<font color=\"red\">".$error_fifths."</font>";
	echo "</p><ul>";
	echo "<li>Start from fraction <input type=\"text\" style=\"text-align:right;\" name=\"p_start_fifths\" size=\"6\" value=\"".$p_start_fifths."\">/<input type=\"text\" name=\"q_start_fifths\" size=\"6\" value=\"".$q_start_fifths."\"></li>";
	echo "<li>List of note names separated by commas, including the starting note, e.g. “C, G, E” etc.:<br /><input type=\"text\" name=\"names_notes_fifths\" size=\"80\" value=\"\"></li>";
	echo "<li><input type=\"checkbox\" name=\"ignore_unlabeled\">Hide unlabeled positions</li>";
	echo "</ul>";
	echo "<hr>";
	}

echo "</td>";

// Analyze scale
$harmonic_third = $pythagorean_third - $syntonic_comma;
$harmonic_minor_sixth = 1200 - $harmonic_third;
$pythagorean_minor_sixth = 1200 - $pythagorean_third;
$wolf_fifth = $perfect_fifth - $syntonic_comma;
$wolf_fourth = $perfect_fourth - $syntonic_comma;
store($h_image,"harmonic_third",$harmonic_third);
store($h_image,"pythagorean_third",$pythagorean_third);
store($h_image,"wolf_fifth",$wolf_fifth);
store($h_image,"perfect_fifth",$perfect_fifth);
store($h_image,"wolf_fourth",$wolf_fourth);

if($numgrades_with_labels > 2 AND $error_transpose == '' AND $error_create == '' AND !$warned_ratios) {
	echo "<td id=\"topstruct\">";
	if($transpose_scale_name == '' AND $new_scale_name == '')
		echo "<h3>Harmonic structure of this tonal scale (cents):</h3>";
	else {
		if($transpose_scale_name <> '') $this_name = $transpose_scale_name;
		else if($new_scale_name <> '') $this_name = $new_scale_name;
		echo "<h3>Structure of transposed tonal scale <font color=\"blue\">‘".$this_name."’</font> (cents):</h3>";
		}
	echo "<table>";
	echo "<tr><td></td>";
	$num = $sum = array();
	for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
		if($name[$j] == '') continue;
		if($scale_choice == "small_scale" AND $selected_grades <> '' AND !in_array($name[$j],$selected_grade_name)) continue;
		for($k = $kk = 0; $k <= $numgrades_fullscale; $k++) {
			if($name[$k] == '') continue;
			if($scale_choice == "small_scale" AND $selected_grades <> '' AND !in_array($name[$k],$selected_grade_name)) continue;
			$class = modulo(($kk - $jj),$numgrades_with_labels);
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0) {
				if($k < $j) $a = 2 * $p[$k] * $q[$j] / $q[$k] / $p[$j];
				else $a = $p[$k] * $q[$j] / $q[$k] / $p[$j];
				}
			else {
				if(($ratio[$k] * $ratio[$j]) <> 0) {
					if($k < $j) $a = 2 * $ratio[$k] / $ratio[$j];
					else $a = $ratio[$k] / $ratio[$j];
					}
				else $a = 1;
				}
			$x[$j][$k] = cents($a);
			if(!isset($num[$class])) {
				$num[$class] = $sum[$class] = 0;
				}
			$num[$class]++;
			$sum[$class] += $x[$j][$k];
			$kk++;
			}
		echo "<td style=\"background-color:azure; text-align:center; vertical-align:middle; padding:2px;\"><b>".$name[$j]."</b></td>";
		$jj++;
		}
	echo "</tr>";
	$list_sensitive_notes = $list_wolffifth_notes = $list_wolffourth_notes = '';
	foreach($sum as $var => $class) {
		$moy[$var] = $sum[$var] / $num[$var];
		}
	for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
		if($name[$j] == '') continue;
		if($scale_choice == "small_scale" AND $selected_grades <> '' AND !in_array($name[$j],$selected_grade_name)) continue;
		echo "<tr>";
		echo "<td style=\"background-color:azure; text-align:center; vertical-align:middle; padding:2px;\"><b>".$name[$j]."</b></td>";
		for($k = $kk = 0; $k <= $numgrades_fullscale; $k++) {
			if($name[$k] == '') continue;
			if($scale_choice == "small_scale" AND $selected_grades <> '' AND !in_array($name[$k],$selected_grade_name)) continue;
			$class = round($x[$j][$k] / 100);
			$color = "black";
			if(($class == 7) AND (abs($wolf_fifth - $x[$j][$k])) < 15) {
				// Wolf fifth
				$list_sensitive_notes .= $k." ";
				$list_wolffifth_notes .= $j." ";
				$color = "red";
				}
			if(($class == 5) AND ((abs($wolf_fourth - $x[$j][$k])) < 15)) {
				// Wolf fourth
				$list_wolffourth_notes .= $j." ";
				$color = "purple";
				}
			if(($class == 4) AND (abs($pythagorean_third - $x[$j][$k])) < 10) $color = "brown";
			if($class == 4 AND (abs($harmonic_third - $x[$j][$k])) < 10) $color = "MediumTurquoise";
			if($class == 7 AND (abs($perfect_fifth - $x[$j][$k])) < 10) $color = "blue";
			$show = "<font color=\"".$color."\">".round($x[$j][$k])."</font>";
			if($class == 7 OR $class == 5 OR $class == 4 OR $class == 8) $show = "<b>".$show."</b>";
			if(round($x[$j][$k]) == 0) $show = '';
			$kk++;
			echo "<td style=\"text-align:right; vertical-align:middle; padding:4px;\">".$show."</td>";
			}
		$jj++;
		echo "</tr>";
		}
	echo "</table>";
	$list_sensitive_notes = trim($list_sensitive_notes);
	$string_sensitive_notes = '';
	if($list_sensitive_notes <> '') {
		$table_sensitive_notes = explode(' ',$list_sensitive_notes);
		for($i = 0; $i < count($table_sensitive_notes); $i++) {
			$sensitive_note = $table_sensitive_notes[$i];
			$string_sensitive_notes .= $name[$sensitive_note]." ";
			}
		}
	echo "<p style=\"\"><b>Colors: <font color=\"blue\">Perfect fifth</font> — <font color=\"red\">Wolf fifth</font> — <font color=\"purple\">Wolf fourth</font> — <font color=\"MediumTurquoise\">Harmonic major third</font> — <font color=\"brown\">Pythagorean major third</font></b><br />➡ <i>Wolf fifths indicate ‘sensitive notes’";
	if($list_sensitive_notes <> '') echo ", here: </i><b><font color=\"red\">".trim($string_sensitive_notes)."</font></b><i>";
	echo "</i></p>";
	echo "<input type=\"hidden\" name=\"list_sensitive_notes\" value=\"".$list_sensitive_notes."\">";
	echo "<input type=\"hidden\" name=\"list_wolffifth_notes\" value=\"".$list_wolffifth_notes."\">";
	echo "<input type=\"hidden\" name=\"list_wolffourth_notes\" value=\"".$list_wolffourth_notes."\">";
	
	$fifth = $fourth = $wolffifth = $wolffourth = $harmthird = $pyththird = array();
	$nr_wolf = $sum_comma = 0;
	echo "<table>";
	echo "<tr><td style=\"vertical-align:middle; padding:4px;\"><b>Perfect 5th</b></td><td><b>Wolf 5th</b></td><td><b>Perfect 4th</b></td><td><b>Wolf 4th</b></td><td><b>Harm. maj. 3d</b></td><td><b>Pyth. maj. 3d</b></td></tr>";
	echo "<tr><td style=\"vertical-align:middle; text-align:center; padding:4px;\">";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $perfect_fifth;
			if(abs($dist) < 10) {
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"blue\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$fifth[$j] = $k;
				store2($h_image,"fifth",$j,$k);
				}
			}
		}
	echo "<td style=\"vertical-align:middle; text-align:center; padding:4px;\">";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $wolf_fifth;
			if(abs($dist) < 15 AND !isset($fifth[$j])) { // Added !isset by BB 2022-03-10
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"red\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$wolffifth[$j] = $k;
				store2($h_image,"wolffifth",$j,$k);
				$nr_wolf++;
				$sum_comma += $perfect_fifth - $pos;
				}
			}
		}
	echo "<td style=\"vertical-align:middle; text-align:center; padding:4px;\">";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $perfect_fourth;
			if(abs($dist) < 10) {
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"blue\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$fourth[$j] = $k;
			//	store2($h_image,"fourth",$j,$k);
				$sum_comma += $perfect_fourth - $pos;
				}
			}
		}
	echo "<td style=\"vertical-align:middle; text-align:center; padding:4px;\">";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $wolf_fourth;
			if(abs($dist) < 15 AND !isset($fourth[$j])) { // Added !isset by BB 2022-03-10
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"purple\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$wolffourth[$j] = $k;
				store2($h_image,"wolffourth",$j,$k);
				$nr_wolf++;
				$sum_comma += $perfect_fourth - $pos;
				}
			}
		}
	echo "</td><td style=\"vertical-align:middle; text-align:center; padding:4px;\">";	
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $harmonic_third;
			if(abs($dist) < 10) {
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"MediumTurquoise\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$harmthird[$j] = $k;
				store2($h_image,"harmthird",$j,$k);
				}
			}
		}
	echo "</td><td style=\"vertical-align:middle; text-align:center; padding:4px;\">";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($name[$j] == '' OR $ratio[$j] == 0) continue; // By security
		for($k = 0; $k < $numgrades_fullscale; $k++) {
			if($j == $k OR $name[$k] == '') continue;
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0)
				$pos = cents($p[$k] * $q[$j] / $q[$k] / $p[$j]);
			else $pos = cents($ratio[$k] / $ratio[$j]);
			if($pos < 0) $pos += 1200;
			$dist = $pos - $pythagorean_third;
			if(abs($dist) < 10 AND !isset($harmthird[$j])) { // Added !isset by BB 2022-03-10
				$deviation = '';
				if($dist > 1) {
					$deviation = " (+".round(abs($dist)).")";
					}
				if($dist < -1) {
					$deviation = " (-".round(abs($dist)).")";
					}
				echo "<font color=\"brown\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
				$pyththird[$j] = $k;
				store2($h_image,"pyththird",$j,$k);
				}
			}
		}
	echo "</td></tr>";
	echo "</table>";
	
	echo "<p><b>Syntonic comma = <font color=\"red\">".round($syntonic_comma,1)."c</font></b>";
	if(($p_comma * $q_comma) > 0) echo "<b> = <font color=\"red\">".$p_comma."/".$q_comma."</font></b>";
	echo "<br /><input style=\"background-color:aquamarine;\" type=\"submit\" name=\"change_comma\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topcomma\" value=\"CHANGE COMMA VALUE TO:\">&nbsp;ratio&nbsp;<input type=\"text\" name=\"new_p_comma\" size=\"3\" value=\"\"> / <input type=\"text\" name=\"new_q_comma\" size=\"3\" value=\"\">&nbsp;&nbsp;or&nbsp;<input type=\"text\" name=\"new_comma\" size=\"6\" value=\"\"> cents";
	echo "</p>";
		
//	Cycles of perfect fifths
	$max_length = $j_max_length = 0;
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		$cycle[$j] = array();
		$cycle[$j][] = $j;
		$cycle[$j] = cycle_of_intervals($fifth,$cycle[$j],$j,0);
		if(count($cycle[$j]) > $max_length) {
			$max_length = count($cycle[$j]);
			$j_max_length = $j;
			}
		}
		
	echo "<h3>Tuning scheme:</h3>";	
	$levelmax = ceil($numgrades_with_labels /  2); // Number of allowed successive jumps. We'll increase it until all notes appear on the scheme
	$levelmax = 6;
	$table = array();
	for($i = 0; $i < $numgrades_fullscale; $i++) $done_note[$i] = FALSE;
	for($i = 0; $i < 2 * $numgrades_fullscale; $i++) {
		for($j = 0; $j < 2 * $numgrades_fullscale; $j++) {
			$table[$i][$j] = -1;
			}
		}
	while(TRUE) {
		$i = $j = $numgrades_fullscale;
	//	$startnote = $cycle[$j_max_length][0];
		$level = 0;
		$startnote = 0;
		find_neighbours($syntonic_comma,$startnote,$ratio,$name,$i,$j,$numgrades_fullscale,$level,$levelmax);
		for($i = $number_done = 0; $i < $numgrades_fullscale; $i++) {
			if($done_note[$i]) $number_done++;
			}
		if($number_done >= $numgrades_with_labels) break;
		$levelmax++;
		if($levelmax > $numgrades_fullscale) break;
		}
	if($number_done < $numgrades_with_labels) {
		echo "<p><font color=\"red\"><i>Only ".$number_done." notes out of ".$numgrades_with_labels." appear on this scheme:</i></font></p>";
		}
	$lines = count($table);
	$imin = $jmin = 2 * $numgrades_fullscale;
	$imax = $jmax = -1;
	for($j = 0; $j < $lines; $j++) {
		$cols = count($table[$j]);
		for($i = 0; $i < $cols; $i++) {
			if($table[$i][$j] >= 0) {
				if($i < $imin) $imin = $i;
				if($i > $imax) $imax = $i;
				if($j < $jmin) $jmin = $j;
				if($j > $jmax) $jmax = $j;
				}
			}
		}
	echo "<table>";
	for($j = $jmin; $j <= $jmax; $j++) {
		echo "<tr>";
		for($i = $imin; $i <= $imax; $i++) {
				echo "<td style=\"text-align:center; vertical-align:middle; padding:6px;\">";
				if($table[$i][$j] >= 0) {
					echo "<b>".$name[$table[$i][$j]]."</b>";
					}
				echo "</td>";
			}
		echo "</tr>";
		}
	echo "</table>";
	echo "➡ <i>ignoring syntonic comma in major thirds</i><br /><br />";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"syntonic_comma\" value=\"".$syntonic_comma."\">";
echo "<input type=\"hidden\" name=\"p_comma\" value=\"".$p_comma."\">";
echo "<input type=\"hidden\" name=\"q_comma\" value=\"".$q_comma."\">";
		
$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"10\" style=\"width:1000px;\">".$text."</textarea>";
echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" formaction=\"scale.php?scalefilename=".urlencode($filename)."#toptable\" onclick=\"this.form.target='_self';return true;\" name=\"savethisfile\" value=\"SAVE “".$filename."”\"></p>";
echo "</form>";
$line = "§>\n";
$line = str_replace('§','?',$line);
fwrite($h_image,$line);
fclose($h_image);
echo "</body>";
echo "</html>";

/* ===========  FUNCTIONS ============== */

function find_neighbours($syntonic_comma,$note,$ratio,$name,$i,$j,$numgrades_fullscale,$level,$levelmax) {
	global $table,$done_note,$perfect_fifth,$perfect_fourth,$harmonic_third,$harmonic_minor_sixth,$pythagorean_third,$pythagorean_minor_sixth;
	$trace = FALSE;
	$dist_max = $syntonic_comma - 1; // cents
	if($ratio[$note] == 0) return;
//	if($table[$i][$j] == -1) $table[$i][$j] = $note;
	$table[$i][$j] = $note;
	if($trace) echo "<br />• ".$name[$note]." (".$level."/".$levelmax.") ".$i." ".$j.": ";
	for($k = 0; $k < $numgrades_fullscale; $k++) {
		if($note == $k OR $name[$k] == '') continue;
		$pos = cents($ratio[$k] / $ratio[$note]);
		if($pos < 0) $pos += 1200;
		if($trace) echo $name[$k]." ";
		$dist = abs($pos - $perfect_fifth);
		if($level < $levelmax AND $dist < $dist_max) {
			if($table[$i+1][$j] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i+1,$j,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		$dist = abs($pos - $perfect_fourth);
		if($level < $levelmax AND $dist < $dist_max AND $i > 0) {
			if($table[$i-1][$j] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i-1,$j,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		$dist = abs($pos - $harmonic_third);
		if($level < $levelmax AND $dist < $dist_max AND $levelmax >= ($numgrades_fullscale - 1)) {
			if($table[$i][$j+1] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i,$j+1,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		$dist = abs($pos - $harmonic_minor_sixth);
		if($level < $levelmax AND $dist < $dist_max AND $j > 0 AND $levelmax >= ($numgrades_fullscale - 1)) {
			if($table[$i][$j-1] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i,$j-1,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		$dist = abs($pos - $pythagorean_third);
		if($level < $levelmax AND $dist < $dist_max AND $levelmax == $numgrades_fullscale) {
			if($table[$i][$j+1] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i,$j+1,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		$dist = abs($pos - $pythagorean_minor_sixth);
		if($level < $levelmax AND $dist < $dist_max AND $j > 0 AND $levelmax == $numgrades_fullscale) {
			if($table[$i][$j-1] == -1 AND !$done_note[$k]) {
				find_neighbours($syntonic_comma,$k,$ratio,$name,$i,$j-1,$numgrades_fullscale,$level+1,$levelmax);
				}
			}
		}
	$done_note[$note] = TRUE;
	if($trace) echo "<br />";
	return;
	}

function cycle_of_intervals($interval,$cycle,$j,$level) {
	if($level > 100) return $cycle;
	if(isset($interval[$j])) {
		$cycle[] = $interval[$j];
		$cycle = cycle_of_intervals($interval,$cycle,$interval[$j],$level + 1);
		}
	return $cycle;
	}

function change_ratio_in_harmonic_cycle_of_fifths($this_note,$change_ratio,$numgrades_fullscale) {
	global $name,$p,$q,$ratio,$changed_ratio,$perfect_fifth,$series;
	// if($this_note == '' OR $this_note == 0 OR $this_note == $numgrades_fullscale) return;
	if($this_note == '') return;
	// if(($p[$this_note] * $q[$this_note]) == 0) return;
	if(isset($changed_ratio[$this_note])) return;
	$changed_ratio[$this_note] = TRUE;
	if($series[$this_note] <> 'h') return;
	echo "<font color=\"blue\">".$name[$this_note]."</font> ";
	for($j = 0; $j < $numgrades_fullscale; $j++) {
		if($j == $this_note OR $name[$j] == '') continue;
		if(($p[$j] * $p[$this_note] * $q[$j] * $q[$this_note]) > 0)
			$pos = cents($p[$this_note] * $q[$j] / $q[$this_note] / $p[$j]);
		else $pos = cents($ratio[$this_note] / $ratio[$j]);
		if($pos < 0) $pos += 1200;
		$dist1 = $pos - $perfect_fifth;
		if(abs($dist1) < 5) { // Must be accurate to limit cycle to either harmonic or phytagorean series
			change_ratio_in_harmonic_cycle_of_fifths($j,$change_ratio,$numgrades_fullscale);
			}
		$dist2 = $pos - 1200 + $perfect_fifth;
		if(abs($dist2) < 5) {
			change_ratio_in_harmonic_cycle_of_fifths($j,$change_ratio,$numgrades_fullscale);
			}
		}
	$p[$this_note] = $q[$this_note] = 0;
	$ratio[$this_note] = $ratio[$this_note] * $change_ratio;
//	echo "this_note = ".$this_note." = ".$name[$this_note]." ".$change_ratio."<br />";
	return;
	}
?>