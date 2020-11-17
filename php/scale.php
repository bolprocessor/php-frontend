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
	$filename = $_GET['scalefilename'];
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

if(isset($_POST['savethisfile'])) {
	echo "<p id=\"timespan\"><font color=\"red\">Saving this scale...</font></p>";
	$scale_name = $_POST['scale_name'];
	$numgrades = $_POST['numgrades'];
	$interval = $_POST['interval'];
	$basefreq = $_POST['basefreq'];
	$basekey = $_POST['basekey'];
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
	$bad = FALSE;
	for($i = 0; $i <= $numgrades; $i++) {
		if(!isset($_POST['ratio_'.$i])) $ratio[$i] = 0;
		else $ratio[$i] = trim($_POST['ratio_'.$i]);
		if($ratio[$i] == '') {
			$ratio[$i] = 0;
		//	$bad = TRUE;
			}
		if(!isset($_POST['name_'.$i])) $name[$i] = "???";
		else $name[$i] = trim($_POST['name_'.$i]);
		if($name[$i] == '') {
			$name[$i] = "???";
		//	$bad = TRUE;
			}
		}
	for($i = 0; $i <= $numgrades; $i++) {
		if(!isset($_POST['p_'.$i])) $p[$i] = 0;
		else $p[$i] =intval($_POST['p_'.$i]);
		if(!isset($_POST['p_'.$i])) $q[$i] = 0;
		else $q[$i] =intval($_POST['q_'.$i]);
		}
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
	if($bad) echo "<p><font color=\"red\">WARNING:</font> A few boxes are empty</p>";
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
	echo "<p>Function table: <font color=\"blue\">".$line."</font></p>";
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
$table = explode(' ',$scale_note_names);
for($i = 0; $i <= $numgrades; $i++) {
	if(isset($table[$i]) AND $table[$i] <> "???") $name[$i] = trim($table[$i]);
	else $name[$i] = '';
	if(!isset($p[$i])) $p[$i] = 0;
	if(!isset($q[$i])) $q[$i] = 0;
	if($p[$i] > 0 AND $q[$i] > 0)
		$ratio[$i] = $p[$i] / $q[$i];
//	echo $i." ".$name[$i]."<br />";
	}
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";

echo "<p>Name of this tonal scale: ";
echo "<input type=\"text\" name=\"scale_name\" size=\"20\" value=\"".$scale_name."\">";
if(is_integer(strpos($scale_name,' '))) echo " ➡ avoiding spaces is prefered";
echo "</p>";
echo "<p><font color=\"red\">numgrades</font> = <input type=\"text\" name=\"numgrades\" size=\"5\" value=\"".$numgrades."\"></p>";
echo "<p><font color=\"red\">interval</font> = <input type=\"text\" name=\"interval\" size=\"5\" value=\"".$interval."\"></p>";
echo "<p><font color=\"red\">basefreq</font> = <input type=\"text\" name=\"basefreq\" size=\"5\" value=\"".$basefreq."\"> (not used by BP3)</p>";
echo "<p><font color=\"red\">basekey</font> = <input type=\"text\" name=\"basekey\" size=\"5\" value=\"".$basekey."\"></p>";
echo "<h3>Ratios and names of this tonal scale:</h3>";
echo "<table style=\"background-color:white;\">";
echo "<tr><th>fraction</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"white-space:nowrap;\">";
	if($p[$i] == 0 OR $q[$i] == 0)
		$p_txt = $q_txt = '';
	else {
		$p_txt = $p[$i];
		$q_txt = $q[$i];
		}
	echo "<input type=\"text\" name=\"p_".$i."\" size=\"2\" value=\"".$p_txt."\">&nbsp;/&nbsp;<input type=\"text\" name=\"q_".$i."\" size=\"2\" value=\"".$q_txt."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th>ratio</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center;\">";
	echo "<input type=\"text\" name=\"ratio_".$i."\" size=\"6\" value=\"".$ratio[$i]."\">";
	echo "</td>";
	}
echo "</tr>";
echo "<tr><th>name</th>";
for($i = 0; $i <= $numgrades; $i++) {
	echo "<td style=\"text-align:center;\">";
	echo "<input type=\"text\" name=\"name_".$i."\" size=\"6\" value=\"".$name[$i]."\">";
	echo "</td>";
	}
echo "</table>";

$text = html_to_text($scale_comment,"textarea");
echo "<h3>Comment:</h3>";
echo "<textarea name=\"scale_comment\" rows=\"5\" style=\"width:700px;\">".$text."</textarea>";

echo "<p><input style=\"background-color:Aquamarine;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</form>";
?>