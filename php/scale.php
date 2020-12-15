<?php
require_once("_basic_tasks.php");

// $test = TRUE;

if(isset($_POST['dir_scales'])) {
	$dir_scales = $_POST['dir_scales'];
	}
else {
	echo "=> Csound resource file is not known. First open the ‘-cs’ file!"; die();
	}
if(isset($_GET['scalefilename'])) {
	$filename = urldecode($_GET['scalefilename']);
	}
else {
	echo "Scale name is not known. Call it from the ‘-cs’ file!"; die();
	}
$this_title = $filename;
$url_this_page = "scale.php?".$_SERVER["QUERY_STRING"];
require_once("_header.php");

$csound_source = $_POST['csound_source'];

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

$key_start = $key_step = $p_step = $q_step = $p_cents = $q_cents = '';
$error_meantone = '';
$basekey = 60;
$baseoctave = 4;
$transposition_mode = '';
$p_raise = $q_raise = '';
$scale_choice = $selected_grades = '';
$selected_grade_name = array();
$p_comma = 81; $q_comma = 80;
$syntonic_comma = cents($p_comma/$q_comma);
$list_sensitive_notes = $list_wolffifth_notes = '';
$pythagorean_third = cents(81/64);
$perfect_fifth = cents(3/2);
$perfect_fourth = cents(4/3);
$series = array();

/* $number =  1.406;
$serie = "h";
$the_fraction = get_fraction($number,$serie);
echo $the_fraction['p']."/".$the_fraction['q']."<br />";
$serie = "p";
$the_fraction = get_fraction($number,$serie);
echo $the_fraction['p']."/".$the_fraction['q']."<br />"; */

if(isset($_POST['scroll'])) {
	if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) $_SESSION['scroll'] = 0;
	else $_SESSION['scroll'] = 1;
	}

if(isset($_POST['interpolate']) OR isset($_POST['savethisfile']) OR isset($_POST['create_meantone']) OR isset($_POST['modifynames'])) {
	$new_scale_name = trim($_POST['scale_name']);
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
//	$cents = round(1200 * log($interval) / log(2));
	$cents = round(cents($interval));
	if(isset($_POST['interval_cents'])) {
		$new_cents = round($_POST['interval_cents']);
		if($new_cents > 1 AND $new_cents <> $cents)
			$interval = round(exp($new_cents / 1200 * log(2)),4);
		}
	$basefreq = $_POST['basefreq'];
	$basekey = intval($_POST['basekey']);
	$baseoctave = intval($_POST['baseoctave']);
	if($baseoctave <= 0 OR $baseoctave > 14) $baseoctave = 4;
	for($i = 0; $i <= $numgrades_fullscale; $i++) {
		if(!isset($_POST['p_'.$i])) $p[$i] = 0;
		else $p[$i] = intval($_POST['p_'.$i]);
		if(!isset($_POST['p_'.$i])) $q[$i] = 0;
		else $q[$i] = intval($_POST['q_'.$i]);
		
		if(!isset($_POST['ratio_'.$i])) $ratio[$i] = 0;
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
	if($key[0] <> $basekey) {
		echo "<p><font color=\"red\">WARNING</font>: the first key has been set to <font color=\"blue\">‘".$basekey."’</font> which is the value of <b>basekey</b></p>";
		$key[0] = $basekey;
		}
	$name[$numgrades_fullscale] = $name[0];
	if($p[0] == 0 OR $q[0] == 0) {
		$pmax = intval($ratio[0] * 1000);
		$qmax = 1000;
		$gcd = gcd($pmax,$qmax);
		$pmax = $pmax / $gcd;
		$qmax = $qmax / $gcd;
		$p[0] = $pmax;
		$q[0] = $qmax;
		}
	$key_start = intval($_POST['key_start']);
	$key_step = intval($_POST['key_step']);
	$p_step = intval($_POST['p_step']);
	$q_step = intval($_POST['q_step']);
	$p_cents = intval($_POST['p_cents']);
	$q_cents = intval($_POST['q_cents']);
	}

if(isset($_POST['create_meantone'])) {
	if($key_start < 0 OR $key_start > 127)
		$error_meantone .= "<br />Incorrect key value ‘".$key_start."’ to start (should be in range 0…127)";
	if($key_step < 1 OR $key_step > 127)
		$error_meantone .= "<br />Incorrect key step value ‘".$key_step."’ (should be in range 1…127)";
	if($p_step < 1 OR $q_step < 1)
		$error_meantone .= "<br />Incorrect integer ratio ‘".$p_step."/".$q_step."’";
	if(abs($q_cents) < 1)
		$error_meantone .= "<br />Incorrect cent value ‘".$p_cents."/".$q_cents."’";
	if($error_meantone == '') {
		$cent_ratio = exp($p_cents/$q_cents/1200. * log(2));
		$key_start_meantone = $key_start % $numgrades_fullscale;
		$key_meantone = $key_start_meantone;
		$ratio_meantone = $ratio[$key_start_meantone];
		while(TRUE) {
			$key_meantone += $key_step;
			$this_key = $key_meantone;
			$k = $ratio_meantone = $ratio_meantone * $p_step / $q_step * $cent_ratio;
			$key_meantone = $key_meantone % $numgrades_fullscale;
			$oldinterval = $interval;
			while($k > $oldinterval) $k = $k / $oldinterval;
		//	echo $this_key." = ".$key_meantone." => ".$k."<br />";
			if($this_key == $numgrades_fullscale) {
				$interval = $ratio_meantone;
				while(($interval / $oldinterval) > $oldinterval) $interval = $interval / $oldinterval;
				// $cents = round(1200 * log($interval) / log(2));
				$cents = round(cents($interval));
				$interval = round($interval,4);
				}
			$ratio[$key_meantone] = round($k,4);
			if($key_meantone == $key_start_meantone) break;
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
		$i1 = $i2;
		}
	}

$message = '';
if(isset($_POST['savethisfile']) OR isset($_POST['interpolate']) OR isset($_POST['create_meantone']) OR isset($_POST['modifynames'])) {
	$message = "&nbsp;<span id=\"timespan\"><font color=\"red\">... Saving this scale ...</font></span>";
	$scale_comment = $_POST['scale_comment'];
	if(isset($_POST['syntonic_comma'])) $syntonic_comma = $_POST['syntonic_comma'];
	if(isset($_POST['p_comma']) AND isset($_POST['q_comma'])) {
		$p_comma = $_POST['p_comma'];
		$q_comma = $_POST['q_comma'];
		if(($p_comma * $q_comma) > 0) $syntonic_comma = cents($p_comma/$q_comma);
		}
	else $p_comma = $q_comma = 0;
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
		$line_table .= " ".$ratio[$i];
		$scale_note_names .= $name[$i]." ";
		if($series[$i] <> '')
			$scale_series .= $series[$i]." ";
		else
			$scale_series .= "• ";
		$scale_fractions .= $p[$i]." ".$q[$i]." ";
		if($key[$i] > 0)
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
$link = "scale_image.php?save_codes_dir=".urlencode($save_codes_dir);

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
echo "<div class=\"shadow\" style=\"border:2px solid gray; background-color:azure; width:13em;  padding:8px; text-align:center; border-radius: 6px;\"><a onclick=\"window.open('".$link."','".$image_name."','width=1100,height=800,left=100'); return false;\" href=\"".$link."\">IMAGE</a></div>";

echo "</div>";
echo "<p>➡ <a target=\"_blank\" href=\"https://www.csounds.com/manual/html/GEN51.html\">Read the documentation</a></p>";
$numgrades_fullscale = $table2[4];
$interval = $table2[5];
$basefreq = $table2[6];
$basekey = $table2[7];
for($j = 8; $j < ($numgrades_fullscale + 9); $j++) {
	if(!isset($table2[$j])) {
		echo "<p><font color=\"red\">WARNING:</font> the number of ratios is smaller than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
		}
	else $ratio[$j - 8] = $table2[$j];
	}
if(($j - 9) > $numgrades_fullscale) {
	echo "<p><font color=\"red\">WARNING:</font> the number of ratios is larger than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
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

$pmax = intval($interval * 1000);
$qmax = 1000;
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

for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(!isset($series[$i])) $series[$i] = '';
	if(($p[$i] * $q[$i]) <> 0) {
		$p_simple = $p[$i]; $q_simple = $q[$i];
		while(modulo($p_simple,2) == 0) $p_simple = $p_simple / 2;
		while(modulo($q_simple,2) == 0) $q_simple = $q_simple / 2;
		while(modulo($p_simple,3) == 0) $p_simple = $p_simple / 3;
		while(modulo($q_simple,3) == 0) $q_simple = $q_simple / 3;
		if($p_simple == 1 AND $q_simple == 1) {
			$series[$i] = "p";
			}
		else {
			while(modulo($p_simple,5) == 0) $p_simple = $p_simple / 5;
			while(modulo($q_simple,5) == 0) $q_simple = $q_simple / 5;
			if($p_simple == 1 AND $q_simple == 1) {
				$series[$i] = "h";
				}
			}
		}
	store2($h_image,"series",$i,$series[$i]);
	}
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<input type=\"hidden\" name=\"csound_source\" value=\"".$csound_source."\">";

// echo "<p style=\"font-size:large;\">Name of this tonal scale: ";
echo "<h3>Name of this tonal scale: ";
echo "<input type=\"text\" style=\"font-size:large;\" name=\"scale_name\" size=\"20\" value=\"".$scale_name."\">";
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
for($j = 0; $j < $numgrades_fullscale; $j++) {
	if($name[$j] == '') continue;
	echo "<td>";
	echo "<input style=\"text-align:center;\" type=\"text\" name=\"new_name_".$j."\" size=\"5\" value=\"".$name[$j]."\">";
	echo "</td>";
	}
echo "</tr>"; 
echo "</table>";
echo "</td>";
echo "</tr><tr>";
echo "<td style=\"white-space:nowrap; padding:6px; vertical-align:middle;\"><font color=\"blue\">interval</font> = <input type=\"text\" name=\"interval\" size=\"5\" value=\"".$interval."\">";
$cents = round(cents($interval));
echo " or <input type=\"text\" name=\"interval_cents\" size=\"5\" value=\"".$cents."\"> cents (typically 1200)";
echo "</td>";
echo "</tr><tr>";
echo "<td style=\"white-space:nowrap; padding:6px; vertical-align:middle;\"><font color=\"blue\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\">";
echo "&nbsp;&nbsp;<font color=\"blue\">baseoctave</font> = <input type=\"text\" name=\"baseoctave\" size=\"5\" value=\"".$baseoctave."\"></td>";
echo "<td style=\"padding:6px; vertical-align:middle;\"><font color=\"blue\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"5\" value=\"".$basefreq."\"> Hz.<br />This is the frequency for ratio 1/1, assuming a 440 Hz diapason.";
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

store($h_image,"syntonic_comma",$syntonic_comma);
store($h_image,"p_comma",$p_comma);
store($h_image,"q_comma",$q_comma);

// $list_wolffifth_notes = $_POST['list_wolffifth_notes'];
// echo $list_wolffifth_notes."@@@<br />";

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
	if($list_sensitive_notes <> '') {
		$table_sensitive_notes = explode(' ',$list_sensitive_notes);
	/*	echo "<p>Sensitive notes: ";
		for($i = 0; $i < count($table_sensitive_notes); $i++) echo $name[$table_sensitive_notes[$i]]." ";
		echo "</p>"; */
		}
	if($list_wolffifth_notes <> '') {
		$table_wolffifth_notes = explode(' ',$list_wolffifth_notes);
	/*	echo "<p>Wolffifth notes: ";
		for($i = 0; $i < count($table_sensitive_notes); $i++) echo $name[$table_wolffifth_notes[$i]]." ";
		echo "</p>"; */
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
	}
else echo "<table style=\"background-color:white; table-layout:fixed; width:100%;\">";

echo "<tr><td style=\"padding-top:4px; padding-bottom:4px;\" colspan=\"5\">";
if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1)
	$scroll_value = "DO NOT SCROLL THIS TABLE";
else $scroll_value = "SCROLL THIS TABLE";
echo "<input type=\"submit\" style=\"background-color:yellow; \" name=\"scroll\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."#toptable\" value=\"".$scroll_value."\">";
echo "</td></tr>";


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
		$name[$numgrades_fullscale] = $_POST['new_note_0'];
		}
	}

store($h_image,"numgrades_fullscale",$numgrades_fullscale);
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">fraction</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
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
		echo "<input type=\"text\" style=\"border:none; text-align:right;\" name=\"p_".$i."\" size=\"5\" value=\"".$p_txt."\"><b>/</b><input type=\"text\" style=\"border:none;\" name=\"q_".$i."\" size=\"5\" value=\"".$q_txt."\">";
		echo "</td>";
		}
	store2($h_image,"p",$i,$p[$i]);
	store2($h_image,"q",$i,$q[$i]);
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">ratio<br /><small>pyth/harm</small></th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	store2($h_image,"ratio",$i,$ratio[$i]);
	if($ratio[$i] == 0) $show = '';
	else $show = round($ratio[$i],3);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"ratio_".$i."\" size=\"6\" value=\"".$show."\"><br /><small>";
	if(($p[$i] * $p[$i]) == 0) echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"series_".$i."\" size=\"1\" value=\"".$series[$i]."\">";
	else {
		echo $series[$i];
		echo "<input type=\"hidden\" name=\"series_".$i."\" value=\"".$series[$i]."\">";
		}
	echo "</small></td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">name</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	store2($h_image,"name",$i,$name[$i]);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:red; font-weight:bold;\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">cents</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if($ratio[$i] == 0) $cents = '';
	else $cents = round(cents($ratio[$i]));
	store2($h_image,"cents",$i,$cents);
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:azure;\" colspan=\"2\">";
	echo "<b>".$cents."</b>";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">interval</th><td style=\"padding:0px;\"></td>";
for($i = 0; $i < $numgrades_fullscale; $i++) {
	if(($ratio[$i] * $ratio[$i + 1]) == 0) $cents = '';
	else $cents = "«—&nbsp;".round(cents($ratio[$i + 1] / $ratio[$i]))."c&nbsp;—»";
	echo "<td style=\"white-space:nowrap; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
	echo "<font color=\"blue\">".$cents."</font>";
	echo "</td>";
	}
	
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">delta</th><td style=\"padding:0px;\"></td>";
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

echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px; position: absolute;\">key</th>";
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
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:green; font-weight:bold; font-size:large;\" name=\"key_".$i."\" size=\"6\" value=\"".$thekey ."\">";
	echo "</td>";
	}
$key[0] = $basekey;
echo "</tr>";
echo "</table>";
if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) echo "</div>";

echo "<table style=\"background-color:white;\">";
echo "<tr>";
echo "<td>";

$new_scale_name = $transpose_scale_name = $error_create = $error_transpose = $transpose_from_note = $transpose_to_note = '';
$done = TRUE;
	
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
if($done) {
	echo "<table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"change_convention\" value=\"CHANGE NOTE CONVENTION\"> ➡</td>";
	echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
	echo "<input type=\"radio\" name=\"new_convention\" value=\"3\">Key numbers<br />";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	}

if($done) {
	echo "<table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"interpolate\" value=\"INTERPOLATE\"></td><td>➡ Replace missing ratio values with equal intervals (local temperament)</td>";
	echo "</tr>";
	echo "</table>";
	}

if($done AND $numgrades_with_labels > 2) {
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
			$done = $new_selected_grade_name = array();
			for($i = 0; $i <= $numgrades; $i++) {
				$some_name = $selected_grade_name[$i];
				if($some_name == '-') $some_name = $selected_grade_name[$i] = '•';
				if($some_name == '•' OR !isset($done[$some_name])) {
					if($i > 0) $done[$some_name] = TRUE;
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
			$link_edit = "scale.php";
			if(isset($_POST['major_minor'])) {
				echo "<p><font color=\"red\">Exported to</font> <font color=\"blue\">‘".$new_scale_name."’</font> <input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"edit_new_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($new_scale_name)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$new_scale_name."’\"></p>";
				$new_scale_mode = $_POST['major_minor'];
				if($new_scale_mode <> "none") {
					$name_sensitive_note = trim($_POST['name_sensitive_note']);
					if($name_sensitive_note == '') {
						$error_create = "<br /><font color=\"red\"> ➡ A sensitive note should be specified for the major/minor adjustment</font>";
						}
					else {
						$p_adjust = $p_comma;
						$q_adjust = $q_comma;
						if(($p_comma * $q_comma) == 0) {
							$p_adjust = 1000 * exp($syntonic_comma / 1200 * log(2));
							$q_adjust = 1000;
							}
						for($j = 0; $j < $numgrades_fullscale; $j++) {
							if($name[$j] == $name_sensitive_note) {
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
						//	echo $p_transpose."/".$q_transpose." = ".$ratio_transpose."<br />";
							for($j = 0; $j <= $numgrades_fullscale; $j++) {  // $$$$$   REVISE!
								if($name[$j] == $name_sensitive_note) $name[$j] = '';
								if($found) continue;
								if((round($ratio[$j],3) >= $ratio_transpose) OR ($ratio[$j] < 1 AND $ratio_transpose == 1.0)) {
									if($j > 0 AND $name[$j] <> '') $j--;
									$p[$j] = $p_transpose;
									$q[$j] = $q_transpose;
									$ratio[$j] = $ratio_transpose;
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
								$p[$numgrades_fullscale] = $p_transpose;
								$q[$numgrades_fullscale] = $q_transpose;
								$ratio[$numgrades_fullscale] = $interval * $ratio_transpose;
								$name[$numgrades_fullscale] = $name_sensitive_note;
								}
							}
						}
					}
				if($error_create == '') {
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
						$some_comment .= " in major tonality.";
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
	$link_edit = "scale.php";
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
	echo ">with grades:&nbsp;<input type=\"text\" name=\"selected_grades\" size=\"".$size."\" value=\"".$selected_grades."\">";
	echo "<br />➡ Unnamed grades can be inserted as hyphens between spaces ‘ - ’<br />";
	echo "<input type=\"checkbox\" name=\"preserve_numbers\">Preserve key numbers";
	echo "</td>";
	if($error_create <> '') echo $error_create;
	echo "</td></tr><tr>";
	echo "<td style=\"vertical-align:middle; padding:6px; white-space:nowrap;\"><input type=\"radio\" name=\"major_minor\" value=\"none\" checked>don’t change ratios<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"major\">raise to relative major<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"minor\">lower to relative minor</td>";
	echo "<td style=\"text-align:center; vertical-align:middle; padding:4px;\"><b>Sensitive note (1 comma)</b><br /><br />➡ adjust note by ";
	if(($p_comma * $q_comma) > 0) echo $p_comma."/".$q_comma." (or reverse)";
	else {
		$syntonic_comma." cents";
		}
	echo ": <input type=\"text\" name=\"name_sensitive_note\" size=\"6\" value=\"\"></td>";
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
					
					echo "<p><font color=\"green\">Transposition from</font> <font color=\"blue\">‘".$transpose_from_note."’</font> ratio ".$p_transpose_from."/".$q_transpose_from." (".$grade_transpose_from."th position) ";
					$p_transpose_to = $p[$j_transpose_to];
					$q_transpose_to = $q[$j_transpose_to];
					if(($p_transpose_to * $q_transpose_to) <> 0) {
						$gcd = gcd($p_transpose_to,$q_transpose_to);
						$p_transpose_to = $p_transpose_to / $gcd;
						$q_transpose_to = $q_transpose_to / $gcd;
						}
					$ratio_transpose_to = $ratio[$j_transpose_to];
					echo "<font color=\"green\">to</font> <font color=\"blue\">‘".$transpose_to_note."’</font> ratio ".$p_transpose_to."/".$q_transpose_to." (".$grade_transpose_to."th position)</p>";
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
							echo " <font color=\"green\">approximated to</font> ";
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
									echo " with ratio ".$p[$closest_j]."/".$q[$closest_j]." = ".round($ratio[$closest_j],3);
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
				echo "<p><font color=\"green\">Transposition</font> by raising all notes, ratio = ".$p_raise."/".$q_raise." = ".round($this_ratio,3)."</p>";
				for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
					if($name[$j] == '') continue;
					if($j == $numgrades_fullscale) {
						$p_new = $p_new_this_grade[0] * $interval;
						$q_new  = $q_new_this_grade[0];
						$new_ratio = $ratio_this_grade[0] * $interval;
						}
					else {
						$p_new = $p[$j] * $p_raise;
						$q_new  = $q[$j] * $q_raise;
						$new_ratio = $this_ratio * $ratio[$j];
						}
					if(($p_new * $q_new) > 0) {
						$gcd = gcd($p_new,$q_new);
						$p_new = $p_new / $gcd;
						$q_new = $q_new / $gcd;
						$new_ratio = $p_new / $q_new;
						}
					$fraction = simplify_fraction_eliminate_schisma($p_new,$q_new);
					if($fraction['p'] <> $p_new) {
						echo "=> ".$p_new."/".$q_new." simplified to ".$fraction['p']."/".$fraction['q']."<br />";
						$p_new = $fraction['p'];
						$q_new = $fraction['q'];
						$new_ratio = $p_new / $q_new;
						}
					if($new_ratio > $interval AND $j <> $numgrades_fullscale) {
						$q_new = $q_new * $interval;
						$gcd = gcd($p_new,$q_new);
						$p_new = $p_new / $gcd;
						$q_new = $q_new / $gcd;
						$new_ratio = $new_ratio / $interval;
						}
					if($new_ratio < 1) {
						$p_new = $p_new * $interval;
						$gcd = gcd($p_new,$q_new);
						$p_new = $p_new / $gcd;
						$q_new = $q_new / $gcd;
						$new_ratio = $new_ratio * $interval;
						}
					$p_new_this_grade[$jj] = $p_new;
					$q_new_this_grade[$jj] = $q_new;
					$name_this_grade[$jj] = $name[$j];
					$ratio_this_grade[$jj] = $new_ratio;
					$cents_new_this_grade[$jj] = cents($new_ratio);
					echo "<font color=\"blue\">‘".$name[$j]."’</font> new ratio = ".$p_new."/".$q_new." = ".round($new_ratio,3)."<br />";
					$jj++;
					}
				// Check notes against grama locations
				for($j = 0; $j <= $numgrades_fullscale; $j++) {
					$new_name[$j] = '';
					}
				$jold = -1;
				for($jj = 0; $jj <= $numgrades_with_labels; $jj++) {
					$minimum_dist = 1200;
					$closest_j = -1;
					$search_cents = round($cents_new_this_grade[$jj]);
					for($j = 0; $j <= $numgrades_fullscale; $j++) {
						if($j == $jold) continue;
						$cents = cents($ratio[$j]);
						$dist = abs($cents - $search_cents);
						if($dist < $minimum_dist) {
							$minimum_dist = $dist;
							$closest_j = $j;
							}
						}
					if($closest_j >= 0) {
						$new_name[$closest_j] = $name_this_grade[$jj];
						$p[$closest_j] = $p_new_this_grade[$jj];
						$q[$closest_j] = $q_new_this_grade[$jj];
						$old_ratio = round($ratio[$closest_j],3);
						$ratio[$closest_j] = $ratio_this_grade[$jj];
						if($new_name[$closest_j] <> $name[$closest_j]) {
							echo "➡ Assigned location of <font color=\"blue\">‘".$name_this_grade[$jj]."’</font> to ".$closest_j."th position";
							if(round($ratio[$closest_j],3) <> $old_ratio)
								echo " with ratio ".$p[$closest_j]."/".$q[$closest_j]." = ".round($ratio[$closest_j],3);
							echo "<br />";
							}
						$jold = $closest_j;
						}
					}
				for($j = 0; $j <= $numgrades_fullscale; $j++)
					$name[$j] = $new_name[$j];
				if($name[0] == '') $name[0] = $name[$numgrades_fullscale];
				if($name[$numgrades_fullscale] == '') $name[$numgrades_fullscale] = $name[0];
				}
			
			// Now save to file	
			echo "<br />";
			$link_edit = "scale.php";
			echo "<font color=\"green\">Saved to new scale</font> <font color=\"blue\">‘".$new_scale_name."’</font>&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"edit_new_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($new_scale_name)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$new_scale_name."’\"></p>";
			$transpose_scale_name = $new_scale_name;
			$handle = fopen($dir_scales.$new_scale_file,"w");
			fwrite($handle,"\"".$new_scale_name."\"\n");
			$comma_line = $syntonic_comma;
			if(($p_comma * $q_comma) > 0) $comma_line .= " ".$p_comma." ".$q_comma;
			fwrite($handle,"c".$comma_line."c\n");
			$the_notes = $the_fractions = $the_ratios = '';
			// $the_numbers = '';
			for($j = 0; $j <= $numgrades_fullscale; $j++) {
				if($name[$j] <> '') {
					$the_notes .= $name[$j]." ";
				//	$the_numbers .= $key[$j]." ";
					}
				else {
					$the_notes .= "• ";
				//	$the_numbers .= "0 ";
					}
				$the_fractions .= $p[$j]." ".$q[$j]." ";
				}
			$the_notes = "/".trim($the_notes)."/";
			$the_fractions = "[".trim($the_fractions)."]";
		//	$the_numbers = "k".trim($the_numbers)."k";
			fwrite($handle,$the_notes."\n");
		//	fwrite($handle,$the_numbers."\n");
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
				$some_comment .= "All notes raised by ratio ".$p_raise."/".$q_raise.".<br />";
			$some_comment .= "Created ".date('Y-m-d H:i:s')."</html>";
			fwrite($handle,$some_comment."\n");
			fclose($handle);
			$transpose_from_note = $transpose_to_note  = '';
			}
		}
	echo "<table><tr>";
	$link_edit = "scale.php";
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
	echo "><b>&nbsp;Raise all notes</b> by (integer) ratio <input type=\"text\" name=\"p_raise\" size=\"3\" value=\"".$p_raise."\"><b> / </b><input type=\"text\" name=\"q_raise\" size=\"3\" value=\"".$q_raise."\"> (needs revision)";
	
	if($error_transpose <> '') echo "<br /><br />".$error_transpose;
	
	echo "</td>";
	echo "</tr></table>";
	}

if($done) {
	echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"create_meantone\" value=\"CREATE\"> a meantone temperament scale (<a target=\"_blank\" href=\"https://en.wikipedia.org/wiki/Meantone_temperament\">follow this link</a>) with the following data:";
	if($error_meantone <> '') echo "<font color=\"red\">".$error_meantone."</font>";
	echo "</p>";
	echo "<ul>";
	echo "<li>Start from key: <input type=\"text\" name=\"key_start\" size=\"4\" value=\"".$key_start."\"> (typically 60)</li>";
	echo "<li>Step by <input type=\"text\" name=\"key_step\" size=\"4\" value=\"".$key_step."\"> keys (typically 7 for cycles of fifths)</li>";
	echo "<li>Integer ratio of each step <input type=\"text\" name=\"p_step\" size=\"3\" value=\"".$p_step."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_step\" size=\"3\" value=\"".$q_step."\"> (typically 3/2)</li>";
	echo "<li>Add <input type=\"text\" name=\"p_cents\" size=\"3\" value=\"".$p_cents."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_cents\" size=\"3\" value=\"".$q_cents."\"> cent to each step (can be negative, typically -1/3)</li>";
	echo "</ul>";
	}

echo "</td>";

// Analyze scale
$harmonic_third = $pythagorean_third - $syntonic_comma;
$harmonic_sixth = 1200 - $harmonic_third;
$pythagorean_sixth = 1200 - $pythagorean_third;
$wolf_fifth = $perfect_fifth - $syntonic_comma;
store($h_image,"harmonic_third",$harmonic_third);
store($h_image,"pythagorean_third",$pythagorean_third);
store($h_image,"wolf_fifth",$wolf_fifth);
store($h_image,"perfect_fifth",$perfect_fifth);

if($numgrades_with_labels > 2 AND $error_transpose == '' AND $error_create == '') {
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
	$list_sensitive_notes = $list_wolffifth_notes =  '';
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
			if($class == 7 AND (abs($perfect_fifth - $x[$j][$k])) < 4) $color = "blue";
		//	if($class == 5 AND (abs($perfect_fourth - $x[$j][$k])) < 4) $color = "blue";
			if($class == 4 AND (abs($harmonic_third - $x[$j][$k])) < 4) $color = "green";
		//	if($class == 8 AND (abs($harmonic_sixth - $x[$j][$k])) < 4) $color = "green";
			if(($class == 7) AND (abs($wolf_fifth - $x[$j][$k])) < 4) {
				// Wolf fifth
				$list_sensitive_notes .= $k." ";
				$list_wolffifth_notes .= $j." ";
				$color = "red";
				}
		//	if(($class == 5) AND (abs((1200 - $wolf_fifth) - $x[$j][$k])) < 4) $color = "red";
			if(($class == 4) AND (abs($pythagorean_third - $x[$j][$k])) < 4) $color = "brown";
		//	if(($class == 8) AND (abs($pythagorean_sixth - $x[$j][$k])) < 4) $color = "brown";
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
	echo "<p style=\"\"><b>Colors: <font color=\"blue\">Perfect fifth</font> / <font color=\"red\">Wolf fifth</font> — <font color=\"green\">Harmonic major third</font> / <font color=\"brown\">Pythagorean major third</font></b><br />➡ <i>Wolf fifths indicate ‘sensitive notes’";
	if($list_sensitive_notes <> '') echo ", here: </i><b><font color=\"red\">".trim($string_sensitive_notes)."</font></b><i>";
	echo "</i></p>";
	echo "<input type=\"hidden\" name=\"list_sensitive_notes\" value=\"".$list_sensitive_notes."\">";
	echo "<input type=\"hidden\" name=\"list_wolffifth_notes\" value=\"".$list_wolffifth_notes."\">";
	
	$fifth = $wolffifth = $harmthird = $pyththird = array();
	$nr_wolf = $sum_comma = 0;
	echo "<table>";
	echo "<tr><td style=\"vertical-align:middle; padding:4px;\"><b>Perfect 5th</b></td><td><b>Wolf 5th</b></td><td><b>Harm. maj. 3d</b></td><td><b>Pyth. maj. 3d</b></td></tr>";
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
			if(abs($dist) < 10 AND !isset($fifth[$j])) {
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
				echo "<font color=\"green\">".$name[$j]." ".$name[$k]."</font><small>".$deviation."</small><br />";
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
			if(abs($dist) < 10 AND !isset($harmthird[$j])) {
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
		$cycle[$j] = cycle_of_intervals($fifth,$cycle[$j],$j);
		if(count($cycle[$j]) > $max_length) {
			$max_length = count($cycle[$j]);
			$j_max_length = $j;
			}
		}
		
	echo "<h3>Tuning scheme:</h3>";	
	$levelmax = 3; // Number of allowed successive jumps. We'll increase it until all notes appear on the scheme
	while(TRUE) {
		$table = array();
		for($i = 0; $i < $numgrades_fullscale; $i++) $done_note[$i] = FALSE;
		for($i = 0; $i < 2 * $numgrades_fullscale; $i++) {
			for($j = 0; $j < 2 * $numgrades_fullscale; $j++) {
				$table[$i][$j] = -1;
				}
			}
		$i = $j = $numgrades_fullscale;
	//	$startnote = $cycle[$j_max_length][0];
		$startnote = 0;
		$level = 0;
		$table = find_neighbours($table,0,$ratio,$name,$i,$j,$numgrades_fullscale,$level,$levelmax);
		for($i = $number_done = 0; $i < $numgrades_fullscale; $i++) {
			if($done_note[$i]) $number_done++;
			}
		if($number_done >= $numgrades_with_labels) break;
		$levelmax++;
		if($levelmax > $numgrades_fullscale) {
			echo "<p><font color=\"red\">For an unknown reason, only ".$number_done." notes out of ".$numgrades_with_labels." appear on the scheme.</font></p>";
			break;
			}
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
	echo "➡ <i>ignoring syntonic comma in major thirds and fifths</i><br /><br />";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "<input type=\"hidden\" name=\"syntonic_comma\" value=\"".$syntonic_comma."\">";
echo "<input type=\"hidden\" name=\"p_comma\" value=\"".$p_comma."\">";
echo "<input type=\"hidden\" name=\"q_comma\" value=\"".$q_comma."\">";
		
$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"5\" style=\"width:700px;\">".$text."</textarea>";
echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" formaction=\"scale.php?scalefilename=".urlencode($filename)."#toptable\" onclick=\"this.form.target='_self';return true;\" name=\"savethisfile\" value=\"SAVE “".$filename."”\"></p>";
echo "</form>";
$line = "§>\n";
$line = str_replace('§','?',$line);
fwrite($h_image,$line);
fclose($h_image);
echo "</body>";
echo "</html>";

/* ===========  FUNCTIONS ============== */

function find_neighbours($table,$note,$ratio,$name,$i,$j,$numgrades_fullscale,$level,$levelmax) {
	global $done_note,$perfect_fifth,$perfect_fourth,$harmonic_third,$harmonic_sixth;
	$nmax = (2 * $numgrades_fullscale) - 2;
	$table[$i][$j] = $note;
	if($ratio[$note] == 0) return $table;
	for($k = 0; $k < $numgrades_fullscale; $k++) {
		if($note == $k OR $name[$k] == '') continue;
		$pos = cents($ratio[$k] / $ratio[$note]);
		if($pos < 0) $pos += 1200;
		$dist = abs($pos - $perfect_fifth);
		if($level < $levelmax AND $dist < 10 AND $i < $nmax) {
			if($table[$i+1][$j] == -1 AND !$done_note[$k]) {
				$table = find_neighbours($table,$k,$ratio,$name,$i+1,$j,$numgrades_fullscale,$level+1,$levelmax);
				}
			continue;
			}
		$dist = abs($pos - $perfect_fourth);
		if($level < $levelmax AND $dist < 10 AND $i > 0) {
			if($table[$i-1][$j] == -1 AND !$done_note[$k]) {
				$table = find_neighbours($table,$k,$ratio,$name,$i-1,$j,$numgrades_fullscale,$level+1,$levelmax);
				}
			continue;
			}
		$dist = abs($pos - $harmonic_third);
		if($level < $levelmax AND $dist < 10) {
			if($table[$i][$j+1] == -1 AND !$done_note[$k]) {
				$table = find_neighbours($table,$k,$ratio,$name,$i,$j+1,$numgrades_fullscale,$level+1,$levelmax);
				}
			continue;
			}
		$dist = abs($pos - $harmonic_sixth);
		if($level < $levelmax AND $dist < 10) {
			if($table[$i][$j-1] == -1 AND !$done_note[$k]) {
				$table = find_neighbours($table,$k,$ratio,$name,$i,$j-1,$numgrades_fullscale,$level+1,$levelmax);
				}
			continue;
			}
		$done_note[$note] = TRUE;
		}
	return $table;
	}

function cycle_of_intervals($interval,$cycle,$j) {
	if(isset($interval[$j])) {
		$cycle[] = $interval[$j];
		$cycle = cycle_of_intervals($interval,$cycle,$interval[$j]);
		}
	return $cycle;
	}

function change_ratio_in_harmonic_cycle_of_fifths($this_note,$change_ratio,$numgrades_fullscale) {
	global $name,$p,$q,$ratio,$changed_ratio,$perfect_fifth,$series;
//	echo "this_note = ".$this_note."<br />";
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
	return;
	}
?>