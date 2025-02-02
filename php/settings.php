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

$token = get_settings_tokens();

$warning_bottom = $saved_warning_bottom = '';
if(isset($_POST['saveparameters'])) {
	$saved_warning_top = "<p id=\"timespan2\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	$saved_warning_bottom = "<p id=\"timespan3\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	echo $saved_warning_top;
	$settings = array();
	$file_header = $top_header."\n// Settings file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	$settings['header'] = str_replace('"',"'",$file_header);
	$warning = '';
	foreach($_POST as $key => $param) {
    	if(preg_match('/^param_(\d+)$/', $key, $matches)) {
        	$i = $matches[1]; // Extract the numeric index
			$name = trim($_POST['name_'.$i]);
			$boolean = trim($_POST['boolean_'.$i]);
			if($boolean) {
				$value = isset($_POST['parameter_'.$i]);
				if($value) $value = 1;
				else $value = 0;
				}
			else $value = trim($_POST['parameter_'.$i]);
			$unit = trim($_POST['unit_'.$i]);
			switch($param) {
				case "Time_res":
				case "MIDIsyncDelay":
				case "Quantization":
				case "Pclock":
				case "Qclock":
				case "GraphicScaleP":
				case "GraphicScaleQ":
				case "DeftBufferSize":
				case "MaxConsoleTime":
				case "Seed":
				case "NoteConvention":
				case "C4key":
				case "DeftVolume":
				case "VolumeController":
				case "DeftVelocity":
				case "DeftPanoramic":
				case "PanoramicController":
				case "SamplingRate":
				case "DefaultBlockKey":
				case "MinPeriod":
					$value = abs(intval($value));
					break;
				}
			if($param == "Quantization") $quantization = $value;
			if($param == "Quantize" AND $value == 0 AND $quantization > 0) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">Quantize has been turned on because Quantization > 0</span></p>";
				$value = $newvalue;
				}
			if($param == "Quantize" AND $value == 1 AND $quantization == 0) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">Quantize has been turned off because Quantization = 0</span></p>";
				$value = $newvalue;
				}
			if($param == "MinPeriod" AND $quantization > 0 AND $value < (2 * $quantization)) {
				$newvalue = 2 * $quantization;
				$warning .= "<p>👉 <span class=\"red-text\">The minimum period for captured MIDI events should be at least 2 times the Quantization. It has been set to ".$newvalue." ms.</span></p>";
				$value = $newvalue;
				}
			if($param == "Time_res" AND $value < 1) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">Time resolution must be strictly positive. It has been set to 1 ms</span></p>";
				$value = $newvalue;
				}
			if(($param == "Pclock" OR $param == "Qclock") AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">Values of Pclock and Qclock must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
				$value = $newvalue;
				}
			if($param == "Improvize") $improvize = $value;
			if($param == "MaxItemsProduce") { // Max items produced
				if($improvize) $value = abs(intval($value));
				if($improvize AND $value < 1) {
					$warning .= "<p>👉 <span class=\"red-text\">Max items produced must be a  positive integer. It has been set to 20</span></p>";
					$value = 20;
					}
				}
			if($param == "AllItems") { // Produce all items
				if($improvize AND $value) {
					$warning .= "<p>👉 <span class=\"red-text\">You cannot produce all items in the Improvize mode</span></p>";
					$value = 0;
					}
				}
			if($param == "ShowGraphic") $showgraphics = $value;
			if($param == "DeftBufferSize") { // Default buffer size
				$newvalue = intval($value);
				if($newvalue < 100) $newvalue = 1000;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Default buffer size must be a (not too small) positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "MaxConsoleTime") { // Max computation time
				$newvalue = intval($value);
				if($newvalue < 1) $newvalue = 15;
				if($newvalue > 3600) $newvalue = 3600;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Max computation time must be a positive integer, max 3600 seconds: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "Seed") { // Seed for randomization
				$newvalue = intval($value);
				if($newvalue < 0) $newvalue = - $newvalue;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Seed for randomization must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "NoteConvention" AND $value > 4) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">Note convention must be an integer from 0 to 4: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "GraphicScaleP" OR $param == "GraphicScaleQ") {
				$value = intval($value);
				if($value < 1) {
					$newvalue = 1;
					$warning .= "<p>👉 <span class=\"red-text\">GraphicScaleP and GraphicScaleQ must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
					$value = $newvalue;
					}
				}
			if($param == "C4key" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 60;
				$warning .= "<p>👉 <span class=\"red-text\">C4 key number must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "A4freq" AND (!is_numeric($value) OR $value < 10)) {
				$newvalue = 440;
				$warning .= "<p>👉 <span class=\"red-text\">A4 frequency (diapason) must be a floating-point number greater than 10&nbsp;Hz: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "DeftVolume" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 90;
				$warning .= "<p>👉 <span class=\"red-text\">Default volume must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "VolumeController" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 7;
				$warning .= "<p>👉 <span class=\"red-text\">Volume controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "DeftVelocity" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 64;
				$warning .= "<p>👉 <span class=\"red-text\">Default velocity must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "DeftPanoramic" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 64;
				$warning .= "<p>👉 <span class=\"red-text\">Default panoramic must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($param == "PanoramicController" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 10;
				$warning .= "<p>👉 <span class=\"red-text\">Panoramic controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if(($param == "SamplingRate") AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
				$newvalue = 50;
				$warning .= "<p>👉 <span class=\"red-text\">Sampling rate must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if(($param == "DefaultBlockKey") AND (!is_numeric($value) OR intval($value) <> $value OR $value <= 10 OR $value > 127)) {
				$newvalue = 60;
				$warning .= "<p>👉 <span class=\"red-text\">“Default block key” must be an integer from 10 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">” (C4)</span></p>";
				$value = $newvalue;
				}
			if($param == "ComputeWhilePlay") $ComputeWhilePlay = $value;
			if($param == "AdvanceTime" AND $value < 0) {
				$newvalue = abs($value);
				$warning .= "<p>👉 <span class=\"red-text\">Advance time cannot be negative: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with </span><span class=\"green-text\">".$newvalue."</span></p>";
				$value = $newvalue;
				}
			if($param == "ShowObjectGraph") $showObjectGraph = $value;

			if(($param == "ShowObjectGraph") AND !$showgraphics AND $value == 1) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is not set, Show ObjectGraph has been turned off</span></p>";
				$value = $newvalue;
				$showObjectGraph = 0;
				}
			if(($param == "ShowPianoRoll") AND $showgraphics AND !$showObjectGraph AND $value == 0) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is set, at least Show PianoRoll has been turned on</span></p>";
				$value = $newvalue;
				}
			if(($param == "ShowPianoRoll") AND !$showgraphics AND $value == 1) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is not set, Show PianoRoll has been turned off</span></p>";
				$value = $newvalue;
				}
			if(strlen($value) == 0) $value = ' ';
			$settings[$param]['name'] = $name;
			$settings[$param]['value'] = $value;
			$settings[$param]['unit'] = $unit;
			$settings[$param]['boolean'] = $boolean;
			}
		}
	$settings = recursive_strval($settings);
	$jsonData = json_encode($settings,JSON_PRETTY_PRINT);
    my_file_put_contents($this_file,$jsonData);
	chmod($this_file,$permissions);
	echo $warning;
	$warning_bottom = $warning;
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$content = mb_convert_encoding($content,'UTF-8','UTF-8');
if(trim($content) == '') {
	$template = "settings_template";
	$content = @file_get_contents($template);
	}
else {
	convert_to_json($dir,$filename);
	$content = @file_get_contents($this_file);
	}
$settings = json_decode($content,TRUE);
if($bad_settings) die();
echo "<p class=\"green-text\">".str_replace("\n","<br />",$settings['header'])."</p>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"saveparameters\" value=\"SAVE TO ‘".$filename."’\"></p>";
echo "<table class=\"thinborder\" style=\"border-spacing: 2px;\" cellpadding=\"2px;\">";
$bp_parameter_names = @file_get_contents("settings_names.tab");
if($bp_parameter_names === FALSE) {
	echo "<p style=\"color:red;\">ERROR reading ‘settings_names.tab’</p>";
	die();
	}
$table = explode(chr(10),$bp_parameter_names);
$imax = count($table); $first_line = TRUE;
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$line = str_replace('"','',$line);
	if($line == "-- end --") break;
	$table2 = explode(chr(9),$line);
	if(count($table2) == 1 OR trim($table2[1]) == '') {
		if(!$first_line) echo "<tr><td colspan=\"3\">&nbsp;</td></tr>";
		$first_line = FALSE;
		echo "<tr><td colspan=\"2\" style=\"color:magenta;\"><b>".$line."</b><td></tr>";
		continue;
		}
	$param = $table2[0];
	$name = $table2[1];
	$unit = $table2[2];
	if(!isset($settings[$param])) {
	//	$name = $table2[1];
		$boolean = $table2[3];
		$table3[$i] = $value = $table2[4];
		}
	else {
		if(isset($settings[$param]['value'])) {
		//	$name = $settings[$param]['name'];
		//	echo $settings[$param]['value']."<br />";
			$table3[$i] = $value = $settings[$param]['value'];
			$boolean = $settings[$param]['boolean'];
			}
		else {
			if($new) echo "<p style=\"text-align:left; color:red;\">👉 Converting old JSON format</p>";
		//	$name = $param;
			$table3[$i] = $value = $settings[$param];
			$boolean = $settings[$param]['boolean'];
			if($param == "ComputeWhilePlay") $value = 1;
			$new = FALSE;
			}
		}
	if($name == "Default_block_key_for_scale_in_Csound") $name = "Default_block_key";
	echo "<tr>";
	echo "<td style=\"vertical-align:top;\">";
	if($param == "ShowObjectGraph") $name = "Show object graph";
	if($param == "ShowPianoRoll") $name = "Show pianoroll";
	if($boolean == '') $boolean = 0;
	if($param == "ComputeWhilePlay" AND trim($value) == '') $value = 1;
	echo "<input type=\"hidden\" name=\"param_".$i."\" value=\"".$param."\">";
	echo "<input type=\"hidden\" name=\"name_".$i."\" value=\"".$name."\">";
	echo "<input type=\"hidden\" name=\"boolean_".$i."\" value=\"".$boolean."\">";
	echo "<input type=\"hidden\" name=\"unit_".$i."\" value=\"".$unit."\">";
	echo str_replace('_',' ',$name);
	echo "</td>";
	echo "<td style=\"vertical-align:top;\">";
	if($boolean) {
		echo "<input type=\"checkbox\" name=\"parameter_".$i."\"";
		if($value > 0) echo " checked";
		echo ">";
		}
	else {
		echo "<input type=\"text\" name=\"parameter_".$i."\" size=\"15\" style=\"background-color:CornSilk; border: none;\" value=\"";
		echo $value;
		echo "\">";
		}
	echo "</td>";
	echo "<td style=\"vertical-align:top;\">";
	if($param == "Nature_of_time") {
		if($value > 0) echo "Time is <span class=\"red-text\">STRIATED</span>";
		else echo "Time is <span class=\"red-text\">SMOOTH</span>";
		}
	else echo $unit;
	if($param == "Qclock") {
		$Qclock = $table3[$i];
		$Pclock = intval($table3[$i - 1]);
		if($Pclock <> 0) {
			$metronome = $Qclock * 60 / $Pclock;
			if(intval($metronome) <> $metronome)
				$metronome = sprintf("%.4f",$Qclock * 60 / $Pclock);
			echo "👉 Metronome = <span class=\"red-text\">".$metronome."</span> <span class=\"green-text\">beats/minute</span>";
			}
		}
	if($param == "GraphicScaleQ") {
		$GraphicScaleP = intval($table3[$i-1]);
		$GraphicScaleQ = intval($table3[$i]);
		echo "👉 Graphic scale is P/Q = <span class=\"red-text\">".$GraphicScaleP."/".$GraphicScaleQ."</span>";
		}
	echo "</td>";
	echo "</tr>";
	}
echo "</table>";
echo "<p id=\"bottom\" style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"saveparameters\" formaction=\"".$url_this_page."#bottom\" value=\"SAVE TO ‘".$filename."’\"></p>";
echo $saved_warning_bottom;
echo $warning_bottom;
echo "</form>";
echo "</body></html>";
?>
