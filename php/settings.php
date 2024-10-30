<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "settings.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);

require_once("_header.php");
display_darklight();

echo link_to_help();

echo "<h2>Settings “".$filename."”</h2>";
echo "<p><i>This temporary layout will remain until the set of relevant parameters has been finalised.</i></p>";

$bp_parameter_names = @file_get_contents("settings_names.txt",TRUE);
if($bp_parameter_names === FALSE) echo "ERROR reading ‘settings_names.txt’";
$table = explode(chr(10),$bp_parameter_names);
$imax = count($table);
$imax_parameters = 0;
$saved_warning2 = '';
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	if($line == "-- end --") break;
	$imax_parameters++;
	$table2 = explode(chr(9),$line);
/*	$x = str_replace(chr(9),"_",$line);
	echo $x."<br />"; */
	if(count($table2) < 3) echo "ERROR: ".$table2[0]."<br />";
	$parameter_name[$i] = $table2[0];
	$parameter_unit[$i] = $table2[1];
	$parameter_edit[$i] = $table2[2];
	if(count($table2) > 3 AND $table2[3] > 0)
		$parameter_yesno[$i] = TRUE;
	else $parameter_yesno[$i] = FALSE;
	}

if(isset($_POST['saveparameters'])) {
	$saved_warning1 = "<p id=\"timespan2\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	$saved_warning2 = "<p id=\"timespan3\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	echo $saved_warning1;
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Settings file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	for($i = 0; $i < $imax_parameters; $i++) {
		$index = "parameter_".$i;
		$binary = '';
		if($parameter_yesno[$i]) {
			$binary = "(b) ";
			if(isset($_POST[$index])) $value = 1;
			else $value = 0;
			}
		else
			$value = trim($_POST[$index]);
		if(($i == 7 OR $i == 8) AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
			$newvalue = 1;
			echo "<p>👉 <span class=\"red-text\">Values of Pclock and Qclock must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 9) $value = 28; // Jbutt
		if($i == 10) $improvize = $value;
		if($i == 11) { // Max items produced
			$newvalue = intval($value);
			if($newvalue < 2) $newvalue = 20;
			$value = $newvalue;
			}
		if($i == 13) { // Produce all items
			if($improvize AND $value) {
				echo "<p>👉 <span class=\"red-text\">You cannot produce all items in Improvize mode.</span></p>";
				$value = 0;
				}
			}
		if($i == 41) { // Default buffer size
			$newvalue = intval($value);
			if($newvalue < 100) $newvalue = 1000;
			if(strcmp($newvalue,$value) <> 0)
				echo "<p>👉 <span class=\"red-text\">Default buffer size must be a (not too small) positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 43) $value = 0; // Use buffer limit
		
		if($i == 44) { // Max computation time
			$newvalue = intval($value);
			if($newvalue < 1) $newvalue = 15;
			if(strcmp($newvalue,$value) <> 0)
				echo "<p>👉 <span class=\"red-text\">Max computation time must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 45) { // Seed for randomization
			$newvalue = intval($value);
			if($newvalue < 0) $newvalue = - $newvalue;
			if(strcmp($newvalue,$value) <> 0)
				echo "<p>👉 <span class=\"red-text\">Seed for randomization must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 46) $value = 0; // Use buffer limit
		if($i == 47 AND (!is_numeric($value) OR $value < 0 OR $value > 4 OR intval($value) <> $value)) {
			$newvalue = 0;
			echo "<p>👉 <span class=\"red-text\">Note convention must be an integer from 0 to 4: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 48 OR $i == 49) $value = 0; // StartFromOne and SmartCursor
		if($i == 50 OR $i == 51) {
			$value = intval($value);
			if($value < 1) {
				$newvalue = 1;
				echo "<p>👉 <span class=\"red-text\">GraphicScaleP and GraphicScaleQ must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
				$value = $newvalue;
				}
			}
		if($i == 52 AND $value == '')
			$value = "<no input device>";
		if($i == 53 AND $value == '')
			$value = "<no output device>";
		if($i == 54) $value = 0; // Display bullets
		if($i == 58) $value = 1; // MIDI file format
		if($i == 62 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 60;
			echo "<p>👉 <span class=\"red-text\">C4 key number must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 65 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 90;
			echo "<p>👉 <span class=\"red-text\">Default volume must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 66 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 7;
			echo "<p>👉 <span class=\"red-text\">Volume controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 67 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 64;
			echo "<p>👉 <span class=\"red-text\">Default velocity must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 68 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 64;
			echo "<p>👉 <span class=\"red-text\">Default panoramic must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 69 AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
			$newvalue = 10;
			echo "<p>👉 <span class=\"red-text\">Panoramic controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 63 AND !is_numeric($value)) {
			$newvalue = 440;
			echo "<p>👉 <span class=\"red-text\">Metronome (A4 frequency) must be an integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 70 AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
			$newvalue = 50;
			echo "<p>👉 <span class=\"red-text\">Sampling rate must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
			$value = $newvalue;
			}
		if($i == 71) $value = 39;
		if($i == 110 AND (!is_numeric($value) OR intval($value) <> $value OR $value <= 10 OR $value > 127)) {
			$newvalue = 60;
			echo "<p>👉 <span class=\"red-text\">“Block frequency of key” must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">” (C4).</span></p>";
			$value = $newvalue;
			}
		if($i > 71 AND $i < 110) $value = 10;
		if(strlen($value) == 0) $value = ' ';
	//	echo "value = “".$value."”<br />";
		fwrite($handle,$value."\n");
		}
	fclose($handle);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(trim($content) == '') {
	$template = "settings_template";
	$content = @file_get_contents($template,TRUE);
	}
$extract_data = extract_data(TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"saveparameters\" value=\"SAVE TO ‘".$filename."’\"></p>";
echo "<table class=\"thinborder\" style=\"border-spacing: 2px;\" cellpadding=\"2px;\">";

$table = explode(chr(10),$content);
$imax_file = count($table);
if($imax_file <> $imax_parameters) {
	echo "<p style=\"color:red;\">WARNING: imax_parameters = ".$imax_parameters.", imax_file = ".$imax_file."</p>";
	}
else echo "<p class=\"green-text\">".$imax_file." parameters</p>";

$imax = $imax_file; $start = TRUE;
if($imax_file < $imax_parameters) $imax = $imax_parameters;
for($i = $j = 0; $i < $imax; $i++) {
	$value = trim($table[$i]);
//	echo "value = “".$value."”<br />";
	if($start AND !ctype_digit($value)) {
		echo "Skipping old header = “".$value."”<br />";
		continue; // Eliminate old versions of headers
		}
	$start = FALSE;
	if(!isset($parameter_edit[$j]) OR !$parameter_edit[$j]) {
		if(isset($table[$i])) $value = $table[$i];
		else $value = '';
		echo "<input type=\"hidden\" name=\"parameter_".$j."\" value=\"".$value."\">";
		}
	else {
		echo "<tr>";
		echo "<td>";
		echo $j.") ";
		if(isset($parameter_name[$j])) echo $parameter_name[$j];
		echo "</td>";
		echo "<td>";
		if(isset($parameter_edit[$j]) AND $parameter_edit[$j]) {
			if($parameter_yesno[$j]) {
				echo "<input type=\"checkbox\" name=\"parameter_".$j."\"";
         		if(isset($table[$i]) AND $table[$i] > 0) echo " checked";
         		echo ">";
				}
			else {
				echo "<input type=\"text\" name=\"parameter_".$j."\" size=\"20\" style=\"background-color:CornSilk; border: none;\" value=\"";
				if(isset($table[$i])) echo $table[$i];
				echo "\">";
				}
			}
		else if(isset($table[$i])) echo $table[$i];
		echo "</td>";
		echo "<td>";
		if(isset($parameter_unit[$j])) {
			echo $parameter_unit[$j];
			if($i == 8) {
				$Pclock = intval($table[$i - 1]);
				if($Pclock == 0) {
					}
				else {
					$Qclock = $table[$i];
					$metronome = $Qclock * 60 / $Pclock;
					if(intval($metronome) <> $metronome)
						$metronome = sprintf("%.4f",$Qclock * 60 / $Pclock);
					echo "👉 Metronome = <span class=\"red-text\">".$metronome."</span> <span class=\"green-text\">beats/minute</span>";
					}
				}
			if($i == 50) {
				$GraphicScaleP = intval($table[$i]);
				$GraphicScaleQ = intval($table[$i+1]);
				echo "👉 Graphic scale is P/Q = <span class=\"red-text\">".$GraphicScaleP."/".$GraphicScaleQ."</span>";
				}
				
			}
		echo "</td>";
		echo "</tr>";
		}
	$j++;
	}
echo "</table>";
echo "<p id=\"bottom\" style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"saveparameters\" formaction=\"".$url_this_page."#bottom\" value=\"SAVE TO ‘".$filename."’\"></p>";
echo $saved_warning2;
echo "</form>";
?>
