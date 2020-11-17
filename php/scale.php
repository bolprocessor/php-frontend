<?php
require_once("_basic_tasks.php");

// $test = TRUE;

if(isset($_POST['dir_scales'])) {
	$dir_scales = $_POST['dir_scales'];
	}
else {
	echo "Csound instrument file is not known. First open the ‘-cs’ file!"; die();
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

// echo $url_this_page."<br />";

$file_link = $dir_scales.$filename.".txt";
if(!file_exists($file_link)) {
	echo "File may have been mistakenly deleted: ".$file_link;
	echo "<br />Return to the ‘-cs’ file to restore it!"; die();
	}

if(isset($_POST['interpolate']) OR isset($_POST['savethisfile'])) {
	$new_scale_name = trim($_POST['scale_name']);
	if($new_scale_name == '') $new_scale_name = $filename;
	$result1 = check_duplicate_name($dir_scales,$new_scale_name.".txt");
	$result2 = check_duplicate_name($dir_scales,$new_scale_name.".old");
	if($new_scale_name <> $filename AND ($result1 OR $result2)) {
		echo "<p><font color=\"red\">WARNING</font>: This name <font color=\"blue\">‘".$new_scale_name."’</font> already exists</p>";
		$scale_name = $filename;
		}
	else $scale_name = $new_scale_name;
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
	$basekey = $_POST['basekey'];
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
if(isset($_POST['savethisfile']) OR isset($_POST['interpolate'])) {
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
	echo "Function table: <font color=\"blue\">".$line."</font>";
	if($message <> '') echo $message;
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
	}
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

echo "<p>Name of this tonal scale: ";
echo "<input type=\"text\" name=\"scale_name\" size=\"20\" value=\"".$scale_name."\">";
if(is_integer(strpos($scale_name,' '))) echo " ➡ avoiding spaces is prefered";
echo "</p>";
echo "<p><font color=\"blue\">numgrades</font> = <input type=\"text\" name=\"numgrades\" size=\"5\" value=\"".$numgrades."\"></p>";
echo "<p><font color=\"blue\">interval</font> = <input type=\"text\" name=\"interval\" size=\"5\" value=\"".$interval."\">";
$cents = round(1200 * log($interval) / log(2));
echo " or <input type=\"text\" name=\"interval_cents\" size=\"5\" value=\"".$cents."\"> cents (typically 1200)";
echo "</p>";
echo "<p><font color=\"blue\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"5\" value=\"".$basefreq."\"> (not used by BP3)</p>";
echo "<p><font color=\"blue\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\"></p>";
echo "<h3>Ratios and names of this tonal scale:</h3>";
echo "<table style=\"background-color:white; table-layout:fixed ; width:100%;\">";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">fraction</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"white-space:nowrap;\" colspan=\"2\">";
	if($p[$i] == 0 OR $q[$i] == 0)
		$p_txt = $q_txt = '';
	else {
		$p_txt = $p[$i];
		$q_txt = $q[$i];
		}
	echo "<input type=\"text\" name=\"p_".$i."\" size=\"3\" value=\"".$p_txt."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_".$i."\" size=\"3\" value=\"".$q_txt."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">ratio</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center;\" colspan=\"2\">";
	echo "<input type=\"text\" name=\"ratio_".$i."\" size=\"6\" value=\"".$ratio[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">name</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center;\" colspan=\"2\">";
	echo "<input type=\"text\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</tr>";


echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">cents</th><td></td>";
for($i = 0; $i < $numgrades; $i++) {
	echo "<td style=\"text-align:center;\" colspan=\"2\">";
	$cents = round(1200 * log($ratio[$i + 1] / $ratio[$i]) / log(2));
	echo "<- ".$cents." ->";
	echo "</td>";
	}
echo "<td></td></tr>";
	
	
echo "<tr><th style=\"width:7%; background-color:azure; padding:4px;\">key</th>";
$key = $basekey;
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center;\" colspan=\"2\">";
	echo $key++;
	echo "</td>";
	}
echo "</tr>";
echo "</table>";
echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"interpolate\" value=\"INTERPOLATE\"> ➡ Replace no-ratio values with interpolated intervals (local temperament)</p>";

$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"5\" style=\"width:700px;\">".$text."</textarea>";

echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</form>";
?>