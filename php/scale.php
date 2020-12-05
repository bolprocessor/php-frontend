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

$key_start = $key_step = $p_step = $q_step = $p_cents = $q_cents = '';
$error_meantone = '';
$basekey = 60;
$baseoctave = 4;
$transposition_mode = '';
$p_raise = $q_raise = '';

if(isset($_POST['scroll'])) {
	if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) $_SESSION['scroll'] = 0;
	else $_SESSION['scroll'] = 1;
	}

if(isset($_POST['interpolate']) OR isset($_POST['savethisfile']) OR isset($_POST['create_meantone'])) {
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
	$cents = round(1200 * log($interval) / log(2));
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
		if($ratio[$i] == '') {
			$ratio[$i] = 0;
			}
		if(!isset($_POST['name_'.$i])) $name[$i] = "•";
		else $name[$i] = trim($_POST['name_'.$i]);
		// Slash is reserved for beginning and end of scale_note_names
		$name[$i] = str_replace("/",'',$name[$i]);
		if($name[$i] == '') $name[$i] = "•";
		}
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
			$key = $key_meantone;
			$k = $ratio_meantone = $ratio_meantone * $p_step / $q_step * $cent_ratio;
			$key_meantone = $key_meantone % $numgrades_fullscale;
			$oldinterval = $interval;
			while($k > $oldinterval) $k = $k / $oldinterval;
		//	echo $key." = ".$key_meantone." => ".$k."<br />";
			if($key == $numgrades_fullscale) {
				$interval = $ratio_meantone;
				while(($interval / $oldinterval) > $oldinterval) $interval = $interval / $oldinterval;
				$cents = round(1200 * log($interval) / log(2));
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
			$x = $ratio1;
			for($i = $i1 + 1; $i < $i2; $i ++) {
				$x = $x * $step;
				$ratio[$i] = round($x,3);
				}
			}
		$i1 = $i2;
		}
	}

$message = '';
if(isset($_POST['savethisfile']) OR isset($_POST['interpolate']) OR isset($_POST['create_meantone'])) {
	$message = "&nbsp;<span id=\"timespan\"><font color=\"red\">... Saving this scale ...</font></span>";
	$scale_comment = $_POST['scale_comment'];
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
	$scale_note_names = '';
	$scale_fractions = '';
	for($i = 0; $i <= $numgrades_fullscale; $i++) {
		$line_table .= " ".$ratio[$i];
		$scale_note_names .= $name[$i]." ";
		$scale_fractions .= $p[$i]." ".$q[$i]." ";
		}
	$scale_note_names = trim($scale_note_names);
	$scale_fractions = trim($scale_fractions);
	if($scale_note_names <> '')
		fwrite($handle,"/".$scale_note_names."/\n");
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
$scale_name = $scale_table = $scale_fraction = $scale_note_names = $scale_comment = '';
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
echo "</div>";
echo "<p>➡ <a target=\"_blank\" href=\"https://www.csounds.com/manual/html/GEN51.html\">Read the documentation</a></p>";
$numgrades_fullscale = $table2[4];
$interval = $table2[5];
$basefreq = $table2[6];
$basekey = $table2[7];
for($j = 8; $j < ($numgrades_fullscale + 9); $j++) {
	if(!isset($table2[$j])) {
		echo "<p><font color=\"red\">WARNING:</font> the number of ratios is smaller than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>"; die();
		}
	$ratio[$j - 8] = $table2[$j];
	}
if(($j - 9) > $numgrades_fullscale) {
	echo "<p><font color=\"red\">WARNING:</font> the number of ratios is larger than <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
	}
//	}
$table = array();
if($scale_note_names <> '') {
	$table = explode(' ',$scale_note_names);
	$imax = count($table);
	if($imax <> ($numgrades_fullscale + 1)) {
		echo "<p><font color=\"red\">WARNING:</font> the number of note names is not <font color=\"red\">numgrades</font> (".$numgrades_fullscale.").</p>";
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

$table = explode(' ',$scale_note_names);
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if(isset($table[$i]) AND $table[$i] <> "•") $name[$i] = trim($table[$i]);
	else $name[$i] = '';
	if(!isset($p[$i])) $p[$i] = 0;
	if(!isset($q[$i])) $q[$i] = 0;
	if($p[$i] > 0 AND $q[$i] > 0)
		$ratio[$i] = round($p[$i] / $q[$i],3);
	}

for($j = $numgrades_with_labels = 0; $j < $numgrades_fullscale; $j++) {
	if($name[$j] == '') continue;
	$numgrades_with_labels++;
	}
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<input type=\"hidden\" name=\"csound_source\" value=\"".$csound_source."\">";

echo "<p>Name of this tonal scale: ";
echo "<input type=\"text\" name=\"scale_name\" size=\"20\" value=\"".$scale_name."\">";
if(is_integer(strpos($scale_name,' '))) echo " ➡ avoiding spaces is prefered";
echo "</p>";
echo "<p><font color=\"blue\">numgrades</font> = <input type=\"text\" name=\"numgrades\" size=\"5\" value=\"".$numgrades_fullscale."\"></p>";
echo "<p><font color=\"blue\">interval</font> = <input type=\"text\" name=\"interval\" size=\"5\" value=\"".$interval."\">";
$cents = round(1200 * log($interval) / log(2));
echo " or <input type=\"text\" name=\"interval_cents\" size=\"5\" value=\"".$cents."\"> cents (typically 1200)";
echo "</p>";
echo "<p><font color=\"blue\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"5\" value=\"".$basefreq."\"> Hz. This is the frequency for ratio 1/1, assuming a 440 Hz diapason.";
if($ratio[0] <> 1 AND $name[0] <> '') echo " Here, the frequency of <font color=\"blue\">‘".$name[0]."’</font> would be <font color=\"red\">".round(($basefreq * $ratio[0]),2)."</font> Hz if it is the <i>block key</i>.";
echo "</p>";
echo "<p><font color=\"blue\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\">&nbsp;&nbsp;<font color=\"blue\">baseoctave</font> = <input type=\"text\" name=\"baseoctave\" size=\"5\" value=\"".$baseoctave."\">&nbsp;&nbsp;&nbsp;&nbsp;<input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savethisfile\" onclick=\"this.form.target='_self';return true;\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" value=\"SAVE “".$filename."”\"></p>";

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

if($numgrades_with_labels == 12 AND isset($_POST['use_convention'])) {
	for($i = $j = 0; $i <= $numgrades_fullscale; $i++) {
		if($name[$i] == '') continue;
		if(!isset($_POST['new_note_'.$j]))
			$name[$i] = $_POST['new_note_0'];
		else $name[$i] = $_POST['new_note_'.$j];
		$j++;
		}
	}

echo "<tr><th style=\"background-color:azure; padding:4px;\">fraction</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	echo "<td style=\"white-space:nowrap; background-color:cornsilk; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
	if($p[$i] == 0 OR $q[$i] == 0)
		$p_txt = $q_txt = '';
	else {
		$p_txt = $p[$i];
		$q_txt = $q[$i];
		}
	echo "<input type=\"text\" style=\"border:none; text-align:right;\" name=\"p_".$i."\" size=\"5\" value=\"".$p_txt."\"><b>/</b><input type=\"text\" style=\"border:none;\" name=\"q_".$i."\" size=\"5\" value=\"".$q_txt."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px;\">ratio</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if($ratio[$i] == 0) $show = '';
	else $show = $ratio[$i];
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"ratio_".$i."\" size=\"6\" value=\"".$show."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px;\">name</th>";
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:red; font-weight:bold;\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px;\">cents</th>";
$key = $basekey;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	if($ratio[$i] == 0) $cents = '';
	else $cents = round(1200 * log($ratio[$i]) / log(2));
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:azure;\" colspan=\"2\">";
	echo "<b>".$cents."</b>";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px;\">interval</th><td style=\"padding:0px;\"></td>";
for($i = 0; $i < $numgrades_fullscale; $i++) {
	if(($ratio[$i] * $ratio[$i + 1]) == 0) $cents = '';
	else $cents = round(1200 * log($ratio[$i + 1] / $ratio[$i]) / log(2));
	echo "<td style=\"white-space:nowrap; text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;\" colspan=\"2\">";
	echo "<font color=\"blue\">«—&nbsp;".$cents."¢&nbsp;—»</font>";
	echo "</td>";
	}
	
echo "<tr><th style=\"background-color:azure; padding:4px;\">delta</th><td style=\"padding:0px;\"></td>";
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
		echo "<small>".round(($ratio[$i + 1] / $ratio[$i]),3)."</small>";
		}
	echo "</td>";
	}
echo "</tr>";

echo "</tr>";
echo "<tr><th style=\"background-color:azure; padding:4px;\">key</th>";
$key = $basekey;
for($i = 0; $i <= $numgrades_fullscale; $i++) {
	echo "<td style=\"text-align:center; padding-top:4px; padding-bottom:4px; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px; background-color:cornsilk;\" colspan=\"2\">";
	echo "<font color=\"green\"><b>".($key++)."</b></font>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
if(!isset($_SESSION['scroll']) OR $_SESSION['scroll'] == 1) echo "</div>";

echo "<table style=\"background-color:white;\">";
echo "<tr>";
echo "<td>";

$new_scale_name = $transpose_scale_name = $error_create = $error_transpose = $sensitive_note = $transpose_from_note = $transpose_to_note = '';
$done = TRUE;
	
if($numgrades_with_labels == 12) {
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
				$key = $basekey;
				for($i = 0; $i <= 13; $i++) {
					$standard_note[$i] = $KeyString.($key++);
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
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
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
	}

if($done) {
	echo "<table style=\"background-color:white;\">";
	echo "<tr>";
	echo "<td><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"interpolate\" value=\"INTERPOLATE\"></td><td>➡ Replace missing ratio values with equal intervals (local temperament)</td>";
	echo "</tr>";
	echo "</table>";
	}

if($done AND $numgrades_with_labels > 2) {
	if(isset($_POST['reduce']) AND isset($_POST['reduce_scale_name']) AND trim($_POST['reduce_scale_name']) <> '') {
		if($_POST['scale_choice'] == "full_scale") $full_scale = TRUE;
		else $full_scale =  FALSE;
		if($full_scale) $numgrades = $numgrades_fullscale;
		else $numgrades = $numgrades_with_labels;
		$new_scale_name = trim($_POST['reduce_scale_name']);
		$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
		$new_scale_file = $new_scale_name.".txt";
		$old_scale_file = $new_scale_name.".old";
		$result1 = check_duplicate_name($dir_scales,$new_scale_file);
		$result2 = check_duplicate_name($dir_scales,$old_scale_file);
		if($result1 OR $result2) {
			$error_create = "<br /><font color=\"red\"> ➡ ERROR: This name</font> <font color=\"blue\">‘".$new_scale_name."’</font> <font color=\"red\">already exists</font>";
			}
		else {
			$link_edit = "scale.php";
			echo "<p><font color=\"red\">Exported to</font> <font color=\"blue\">‘".$new_scale_name."’</font> <input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"edit_new_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($new_scale_name)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$new_scale_name."’\"></p>";
			$new_scale_mode = $_POST['major_minor'];
			if($new_scale_mode <> "none") {
				$sensitive_note = trim($_POST['sensitive_note']);
				if($sensitive_note == '') {
					$error_create = "<br /><font color=\"red\"> ➡ A sensitive note should be specified for the major/minor adjustment</font>";
					}
				else {
					for($j = 0; $j < $numgrades_fullscale; $j++) {
						if($name[$j] == $sensitive_note) break;
						}
					if($j >= $numgrades_fullscale)
						$error_create = "<br /><font color=\"red\"> ➡ ERROR: Sensitive note <font color=\"blue\">‘".$sensitive_note."’</font> <font color=\"red\">was not found in this scale</font>";
				
					}
				}
			if($error_create == '') {
				$handle = fopen($dir_scales.$new_scale_file,"w");
				fwrite($handle,"\"".$new_scale_name."\"\n");
				$the_notes = $the_fractions = $the_ratios = '';
				for($j = 0; $j <= $numgrades_fullscale; $j++) {
					if(!$full_scale AND $name[$j] == '') continue;
					if($name[$j] <> '') $the_notes .= $name[$j]." ";
					else $the_notes .= "• ";
					$newp[$j] = $p[$j];
					$newq[$j] = $q[$j];
					$newratio[$j] = $ratio[$j];
					if($name[$j] == $sensitive_note) {
						if($new_scale_mode == "major") {
							$newp[$j] = $newp[$j] * 81;
							$newq[$j] = $newq[$j] * 80;
							$newratio[$j] = $newratio[$j] * 81.0 / 80.0;
							}
						if($new_scale_mode == "minor") {
							$newp[$j] = $newp[$j] * 80;
							$newq[$j] = $newq[$j] * 81;
							$newratio[$j] = $newratio[$j] * 80.0 / 81.0;
							}
						if(($newp[$j] * $newq[$j]) > 0) {
							$gcd = gcd($newp[$j],$newq[$j]);
							$newp[$j] = $newp[$j] / $gcd;
							$newq[$j] = $newq[$j] / $gcd;
							}
						}
					$the_fractions .= $newp[$j]." ".$newq[$j]." ";
					}
				$the_notes = "/".trim($the_notes)."/";
				$the_fractions = "[".trim($the_fractions)."]";
				fwrite($handle,$the_notes."\n");
				fwrite($handle,$the_fractions."\n");
				fwrite($handle,"|".$baseoctave."|\n");
				$the_scale = "f2 0 128 -51 ";
				$the_scale .= $numgrades." ".$interval." ".$basefreq." ".$basekey." ";
				for($j = 0; $j <= $numgrades_fullscale; $j++) {
					if(!$full_scale AND $name[$j] == '') continue;
					if(($newp[$j] * $newq[$j]) > 0)
						$the_scale .= round($newp[$j]/$newq[$j],3)." ";
					else
						$the_scale .= $newratio[$j]." ";
					}
				fwrite($handle,$the_scale."\n");
				if($full_scale)
					$some_comment = "<html>This is a derivation of scale \"".$filename."\" (".$numgrades_fullscale." grades)";
				else
					$some_comment = "<html>This is a reduction of scale \"".$filename."\" (".$numgrades_fullscale." grades)";
				if($new_scale_mode == "major")
					$some_comment .= " in major tonality.";
				else if($new_scale_mode == "minor")
					$some_comment .= " in relative minor tonality";
				if($sensitive_note <> '') $some_comment .= "<br />Sensitive note = '".$sensitive_note."'";
				$some_comment .= "<br />Created ".date('Y-m-d H:i:s')."</html>";
				fwrite($handle,$some_comment."\n");
				fclose($handle);
				$new_scale_name = $sensitive_note = '';
				}
			}
		}

	echo "<table><tr id=\"toptranspose\">";
	echo "<td style=\"vertical-align:middle; padding:4px;\"><input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"reduce\" value=\"REDUCE or ADJUST\"> to create scale named <input type=\"text\" name=\"reduce_scale_name\" size=\"8\" value=\"".$new_scale_name."\"><br />";
	
	echo "<input type=\"radio\" name=\"scale_choice\" value=\"full_scale\" checked>with ".$numgrades_fullscale." grades<br />";
	if($numgrades_with_labels < $numgrades_fullscale)
		echo "<input type=\"radio\" name=\"scale_choice\" value=\"small_scale\">with ".$numgrades_with_labels." grades</td>";
	
	
	if($error_create <> '') echo $error_create;
	$error_create = '';
	echo "</td>";
	echo "<td style=\"vertical-align:middle; padding:4px;\"><input type=\"radio\" name=\"major_minor\" value=\"none\" checked>don’t change ratios<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"major\">raise to relative major<br />";
	echo "<input type=\"radio\" name=\"major_minor\" value=\"minor\">lower to relative minor</td>";
	echo "<td style=\"vertical-align:middle; padding:4px;\"><b>Sensitive note</b><br />(major/minor enharmony)<br />Raise/lower note by 1 comma: <input type=\"text\" name=\"sensitive_note\" size=\"4\" value=\"".$sensitive_note."\"></td>";
	echo "</tr></table><br />";
		
	if(isset($_POST['transpose']) AND trim($_POST['transpose_scale_name']) <> '') {
		$transpose_from_note = trim($_POST['transpose_from_note']);
		$transpose_to_note = trim($_POST['transpose_to_note']);
		$transposition_mode = $_POST['transposition_mode'];
		$new_scale_name = trim($_POST['transpose_scale_name']);
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
		else {
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
						$cents_this_grade[$jj] = 1200 * log($ratio_this_grade[$jj]) / log(2);
						$cents_new = 1200 * log($this_ratio) / log(2);
						if(($cents_new - $cents_this_grade[$jj]) > 50) {
							$q_new = 2 * $q_new;
							$this_ratio = $this_ratio / 2.0;
							$cents_new = 1200 * log($this_ratio) / log(2);
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
						$cents_new = 1200 * log($this_ratio) / log(2);
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
					echo "<br />";
					for($j = 0; $j < $numgrades_fullscale; $j++) {
						$new_name[$j] = '';
						$cents = 1200 * log($ratio[$j]) / log(2);
					//	echo "‘".$name[$j]."’ = ".$p[$j]."/".$q[$j]." ".round($cents)." cents<br />";
						for($jj = 0; $jj <= $numgrades_with_labels; $jj++) {
							if(abs($cents - $cents_new_this_grade[$jj]) < 4) {
								if($name[$j] <> $name_this_grade[$jj]) {
									echo "➡ Note <font color=\"blue\">‘".$name_this_grade[$jj]."’</font> relocated to position ".$j."<br />";
									
								
								
									}
								$new_name[$j] = $name_this_grade[$jj];
								$p[$j] = $p_new_this_grade[$jj];
								$q[$j] = $q_new_this_grade[$jj];
								$ratio[$j] = $ratio_this_grade[$jj];
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
								$cents = 1200 * log($ratio[$j]) / log(2);
								$dist = abs($cents - $search_cents);
								if($dist < $minimum_dist) {
									$minimum_dist = $dist;
									$closest_j = $j;
									}
								}
							if($closest_j >= 0) {
								$new_name[$closest_j] = $search_name;
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
					for($j = 0; $j < $numgrades_fullscale; $j++)
						$name[$j] = $new_name[$j];
					}
				}
			else {
				// Transpose by raising all notes
				$this_ratio = $p_raise / $q_raise;
				echo "<p><font color=\"green\">Transposition</font> by raising all notes, ratio = ".$p_raise."/".$q_raise." = ".round($this_ratio,3)."</p>";
				for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
					if($name[$j] == '') continue;
					$p_new = $p[$j] * $p_raise;
					$q_new  = $q[$j] * $q_raise;
					$new_ratio = $ratio[$j];
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
					if($new_ratio > 2) {
						$q_new = $q_new * 2;
						$gcd = gcd($p_new,$q_new);
						$p_new = $p_new / $gcd;
						$q_new = $q_new / $gcd;
						$new_ratio = $new_ratio / 2;
						}
					if($new_ratio < 1) {
						$p_new = $p_new * 2;
						$gcd = gcd($p_new,$q_new);
						$p_new = $p_new / $gcd;
						$q_new = $q_new / $gcd;
						$new_ratio = $new_ratio * 2;
						}
					$p_new_this_grade[$jj] = $p_new;
					$q_new_this_grade[$jj] = $q_new;
					$name_this_grade[$jj] = $name[$j];
					$ratio_this_grade[$jj] = $new_ratio;
					$cents_new_this_grade[$jj] = 1200 * log($new_ratio) / log(2);
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
						$cents = 1200 * log($ratio[$j]) / log(2);
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
			$the_notes = $the_fractions = $the_ratios = '';
			for($j = 0; $j <= $numgrades_fullscale; $j++) {
				if($name[$j] <> '') $the_notes .= $name[$j]." ";
				else $the_notes .= "• ";
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
	echo "<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$link_edit."?scalefilename=".urlencode($filename)."#toptranspose\" name=\"transpose\" value=\"TRANSPOSITION\"><br />";
	echo "Save the new scale under name <input type=\"text\" name=\"transpose_scale_name\" size=\"8\" value=\"\"><br />";
	echo "<input type=\"radio\" name=\"transposition_mode\" value=\"murcchana\"";
	if($transposition_mode == "murcchana") echo " checked";
	echo "><b>Murcchana</b><br />";
	
	echo "Move note <input type=\"text\" name=\"transpose_from_note\" size=\"4\" value=\"".$transpose_from_note."\"> to note <input type=\"text\" name=\"transpose_to_note\" size=\"4\" value=\"".$transpose_to_note."\"> of this basic scale (<i>grama</i>)<br /><i>Example: On a Ma-grama scale model, move ‘C’ to ‘Eb’ (32/27)<br />to create the minor chromatic scale of same tonality<br />or one perfect fifth down to create the next chromatic scale in the series</i><br />";
	
	echo "<input type=\"radio\" name=\"transposition_mode\" value=\"ratio\"";
	if($transposition_mode == "ratio") echo " checked";
	if($p_raise == 0) $p_raise = $q_raise = '';
	echo "><b>Raise all notes</b> by (integer) ratio <input type=\"text\" name=\"p_raise\" size=\"3\" value=\"".$p_raise."\"><b> / </b><input type=\"text\" name=\"q_raise\" size=\"3\" value=\"".$q_raise."\">";
	
	if($error_transpose <> '') echo "<br /><br />".$error_transpose;
	$error_transpose = '';
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
if($numgrades_with_labels > 2) {
	echo "<td>";
	if($transpose_scale_name == '')
		echo "<h3 style=\"text-align:center;\">Harmonic structure of this tonal scale</h3>";
	else
		echo "<h3 style=\"text-align:center;\">Structure of transposed tonal scale <font color=\"blue\">‘".$transpose_scale_name."’</font></h3>";
	echo "<table>";
	echo "<tr><td></td>";
	$num = $sum = array();
	for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
		if($name[$j] == '') continue;
		for($k = $kk = 0; $k <= $numgrades_fullscale; $k++) {
			if($name[$k] == '') continue;
			$class = modulo(($kk - $jj),$numgrades_with_labels);
			if(($p[$j] * $p[$k] * $q[$j] * $q[$k]) > 0) {
				if($k < $j) $a = 2 * $p[$k] * $q[$j] / $q[$k] / $p[$j];
				else $a = $p[$k] * $q[$j] / $q[$k] / $p[$j];
				}
			else {
				if($k < $j) $a = 2 * $ratio[$k] / $ratio[$j];
				else $a = $ratio[$k] / $ratio[$j];
				}
			$x[$j][$k] = 1200 * log($a) / log(2);
			if(!isset($num[$class])) {
				$num[$class] = $sum[$class] = 0;
				}
			$num[$class]++;
			$sum[$class] += $x[$j][$k];
			$kk++;
			}
		echo "<td style=\"background-color:azure;\"><b>".$name[$j]."</b></td>";
		$jj++;
		}
	echo "</tr>";
	
	foreach($sum as $var => $class) {
		$moy[$var] = $sum[$var] / $num[$var];
		}
		
	for($j = $jj = 0; $j <= $numgrades_fullscale; $j++) {
		if($name[$j] == '') continue;
		echo "<tr>";
		echo "<td style=\"background-color:azure;\"><b>".$name[$j]."</b></td>";
		for($k = $kk = 0; $k <= $numgrades_fullscale; $k++) {
			if($name[$k] == '') continue;
			$class = modulo(($kk - $jj),$numgrades_with_labels);
			$class = round($x[$j][$k] / 100);
		//	if($jj == 0) $val_ref[$class] = $x[$j][$k];
			$color = "black";
			if($class == 7) $color = "blue";
			if($class == 4) $color = "green";
			if(($class == 7) AND (abs($x[$j][$k] - 702) > 15)) $color = "red";
			if(($class == 4) AND (abs($x[$j][$k] - 386) > 15)) $color = "brown";
			$show = "<font color=\"".$color."\">".round($x[$j][$k])."</font>";
			if($class == 7 OR $class == 4) $show = "<b>".$show."</b>";
			if(round($x[$j][$k]) == 0) $show = '';
			$kk++;
			echo "<td>".$show."</td>";
			}
		$jj++;
		echo "</tr>";
		}
	echo "</table>";
	echo "<p style=\"text-align:center;\"><b>Colors: <font color=\"blue\">Perfect fifth</font> / <font color=\"red\">Wolf fifth</font> — <font color=\"green\">Harmonic major third</font> / <font color=\"brown\">Pythagorean major third</font></b><br /><i>Wolf fifths point at sensitive notes</i>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";

$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"5\" style=\"width:700px;\">".$text."</textarea>";

echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" formaction=\"scale.php?scalefilename=".urlencode($filename)."#toptable\" onclick=\"this.form.target='_self';return true;\" name=\"savethisfile\" value=\"SAVE “".$filename."”\"></p>";
echo "</form>";
?>