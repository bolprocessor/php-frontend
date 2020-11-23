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
	$numgrades = $_POST['numgrades'];
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
	for($i = 0; $i <= $numgrades; $i++) {
		if(!isset($_POST['p_'.$i])) $p[$i] = 0;
		else $p[$i] = intval($_POST['p_'.$i]);
		if(!isset($_POST['p_'.$i])) $q[$i] = 0;
		else $q[$i] = intval($_POST['q_'.$i]);
		
		if(!isset($_POST['ratio_'.$i])) $ratio[$i] = 1;
		else $ratio[$i] = trim($_POST['ratio_'.$i]);
		if($ratio[$i] == '') {
			$ratio[$i] = 1;
			}
		if(!isset($_POST['name_'.$i])) $name[$i] = "•";
		else $name[$i] = trim($_POST['name_'.$i]);
		if($name[$i] == '') {
			$name[$i] = "•";
			}
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
		$key_start_meantone = $key_start % $numgrades;
		$key_meantone = $key_start_meantone;
		$ratio_meantone = $ratio[$key_start_meantone];
		while(TRUE) {
			$key_meantone += $key_step;
			$key = $key_meantone;
			$k = $ratio_meantone = $ratio_meantone * $p_step / $q_step * $cent_ratio;
			$key_meantone = $key_meantone % $numgrades;
			$oldinterval = $interval;
			while($k > $oldinterval) $k = $k / $oldinterval;
		//	echo $key." = ".$key_meantone." => ".$k."<br />";
			if($key == $numgrades) {
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
			if($i2 > $numgrades) break;
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
	$line_table = "f2 0 128 -51 ".$numgrades." ".$interval." ".$basefreq." ".$basekey;
	$scale_note_names = '';
	$scale_fractions = '';
	for($i = 0; $i <= $numgrades; $i++) {
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
echo "<div style=\"float:right; margin-top:1em;\"><h1>Scale “".$filename."”</h1><h3>This version is stored in <font color=\"blue\">‘".$csound_source."’</font></h3></div>";
echo "<p>➡ <a target=\"_blank\" href=\"https://www.csounds.com/manual/html/GEN51.html\">Read the documentation</a></p>";
$numgrades = $table2[4];
$interval = $table2[5];
$basefreq = $table2[6];
$basekey = $table2[7];
for($j = 8; $j < ($numgrades + 9); $j++) {
	if(!isset($table2[$j])) {
		echo "<p><font color=\"red\">WARNING:</font> the number of ratios is smaller than <font color=\"red\">numgrades</font> (".$numgrades.").</p>"; die();
		}
	$ratio[$j - 8] = $table2[$j];
	}
if(($j - 9) > $numgrades) {
	echo "<p><font color=\"red\">WARNING:</font> the number of ratios is larger than <font color=\"red\">numgrades</font> (".$numgrades.").</p>";
	}
//	}
$table = array();
if($scale_note_names <> '') {
	$table = explode(' ',$scale_note_names);
	$imax = count($table);
	if($imax <> ($numgrades + 1)) {
		echo "<p><font color=\"red\">WARNING:</font> the number of note names is not <font color=\"red\">numgrades</font> (".$numgrades.").</p>";
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
$p[$numgrades] = $pmax;
$q[$numgrades] = $qmax;

$table = explode(' ',$scale_note_names);
for($i = 0; $i <= $numgrades; $i++) {
	if(isset($table[$i]) AND $table[$i] <> "•") $name[$i] = trim($table[$i]);
	else $name[$i] = '';
	if(!isset($p[$i])) $p[$i] = 0;
	if(!isset($q[$i])) $q[$i] = 0;
	if($p[$i] > 0 AND $q[$i] > 0)
		$ratio[$i] = round($p[$i] / $q[$i],3);
//	echo $i." ".$name[$i]."<br />";
	}
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<input type=\"hidden\" name=\"csound_source\" value=\"".$csound_source."\">";

echo "<p>Name of this tonal scale: ";
echo "<input type=\"text\" name=\"scale_name\" size=\"20\" value=\"".$scale_name."\">";
if(is_integer(strpos($scale_name,' '))) echo " ➡ avoiding spaces is prefered";
echo "</p>";
echo "<p><font color=\"blue\">numgrades</font> = <input type=\"text\" name=\"numgrades\" size=\"5\" value=\"".$numgrades."\"></p>";
echo "<p><font color=\"blue\">interval</font> = <input type=\"text\" name=\"interval\" size=\"5\" value=\"".$interval."\">";
$cents = round(1200 * log($interval) / log(2));
echo " or <input type=\"text\" name=\"interval_cents\" size=\"5\" value=\"".$cents."\"> cents (typically 1200)";
echo "</p>";
echo "<p><font color=\"blue\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"5\" value=\"".$basefreq."\"></p>";
echo "<p><font color=\"blue\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\">&nbsp;&nbsp;<font color=\"blue\">baseoctave</font> = <input type=\"text\" name=\"baseoctave\" size=\"5\" value=\"".$baseoctave."\">&nbsp;&nbsp;&nbsp;&nbsp;<input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savethisfile\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" value=\"SAVE “".$filename."”\"></p>";
echo "<h3>Ratios and names of this tonal scale:</h3>";
echo "<table style=\"background-color:white; table-layout:fixed ; width:100%;\">";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">fraction</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"white-space:nowrap; background-color:gold;\" colspan=\"2\">";
	if($p[$i] == 0 OR $q[$i] == 0)
		$p_txt = $q_txt = '';
	else {
		$p_txt = $p[$i];
		$q_txt = $q[$i];
		}
	echo "<input type=\"text\" style=\"border:none; text-align:right;\" name=\"p_".$i."\" size=\"3\" value=\"".$p_txt."\">&nbsp;<b>/</b>&nbsp;<input type=\"text\" style=\"border:none;\" name=\"q_".$i."\" size=\"3\" value=\"".$q_txt."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">ratio</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center;\" name=\"ratio_".$i."\" size=\"6\" value=\"".$ratio[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">name</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center; background-color:gold;\" colspan=\"2\">";
	echo "<input type=\"text\" style=\"border:none; text-align:center; color:red; font-weight:bold;\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">cents</th>";
$key = $basekey;
for($i = 0; $i <= $numgrades; $i++) {
	$cents = round(1200 * log($ratio[$i]) / log(2));
	echo "<td style=\"text-align:center; background-color:azure;\" colspan=\"2\">";
	echo "<b>".$cents."</b>";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">interval</th><td></td>";
for($i = 0; $i < $numgrades; $i++) {
	echo "<td style=\"text-align:center;\" colspan=\"2\">";
	$cents = round(1200 * log($ratio[$i + 1] / $ratio[$i]) / log(2));
	echo "<font color=\"blue\">«—&nbsp;".$cents."&nbsp;—»</font>";
	echo "</td>";
	}
echo "<td></td></tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">key</th>";
$key = $basekey;
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center; background-color:cornsilk;\" colspan=\"2\">";
	echo "<font color=\"green\"><b>".($key++)."</b></font>";
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"interpolate\" value=\"INTERPOLATE\"> ➡ Replace no-ratio values with equal intervals (local temperament)</p>";

echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"create_meantone\" value=\"CREATE\"> a meantone temperament scale (<a target=\"_blank\" href=\"https://en.wikipedia.org/wiki/Meantone_temperament\">follow this link</a>) with the following data:";
if($error_meantone <> '') echo "<font color=\"red\">".$error_meantone."</font>";
echo "</p>";
echo "<ul>";
echo "<li>Start from key: <input type=\"text\" name=\"key_start\" size=\"4\" value=\"".$key_start."\"> (typically 60)</li>";
echo "<li>Step by <input type=\"text\" name=\"key_step\" size=\"4\" value=\"".$key_step."\"> keys (typically 7 for cycles of fifths)</li>";
echo "<li>Integer ratio of each step <input type=\"text\" name=\"p_step\" size=\"3\" value=\"".$p_step."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_step\" size=\"3\" value=\"".$q_step."\"> (typically 3/2)</li>";
echo "<li>Add <input type=\"text\" name=\"p_cents\" size=\"3\" value=\"".$p_cents."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_cents\" size=\"3\" value=\"".$q_cents."\"> cent to each step (can be negative, typically -1/3)</li>";
echo "</ul>";

$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"5\" style=\"width:700px;\">".$text."</textarea>";

echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" formaction=\"scale.php?scalefilename=".urlencode($filename)."\" name=\"savethisfile\" value=\"SAVE “".$filename."”\"></p>";
echo "</form>";
?>