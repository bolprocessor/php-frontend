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
echo "<p><i>This temporary layout will stay unchanged until relevant parameters have been finalised.</i></p>";

$warning_bottom = $saved_warning_bottom = '';
if(isset($_POST['saveparameters'])) {
	$saved_warning_top = "<p id=\"timespan2\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	$saved_warning_bottom = "<p id=\"timespan3\"><span class=\"red-text\">➡</span> Saved parameters… <span class=\"red-text\">Don't forget to save again related grammar or data!</span></p>";
	echo $saved_warning_top;
	$settings = array();
	$file_header = $top_header."\n// Settings file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	$settings['header'] = str_replace('"',"'",$file_header);
	$warning = '';
	// for($i = 0; $i < $imax_parameters; $i++) {

	foreach($_POST as $key => $name) {
    	if(preg_match('/^name_(\d+)$/', $key, $matches)) {
        	$i = $matches[1]; // Extract the numeric index
		//	$name = $_POST['name_'.$i];
			$boolean = trim($_POST['boolean_'.$i]);
			if($boolean) $value = isset($_POST['parameter_'.$i]);
			else $value = trim($_POST['parameter_'.$i]);
			$unit = trim($_POST['unit_'.$i]);
			if($name == "Quantization") $quantization = $value;
			if($name == "Quantize" AND $value == 0 AND $quantization > 0) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">Quantize has been turned on because Quantization > 0</span></p>";
				$value = $newvalue;
				}
			if($name == "Quantize" AND $value == 1 AND $quantization == 0) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">Quantize has been turned off because Quantization = 0</span></p>";
				$value = $newvalue;
				}
			if(($name == "Pclock" OR $name == "Qclock") AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">Values of Pclock and Qclock must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”.</span></p>";
				$value = $newvalue;
				}
		//	if($i == 9) $value = 28; // Jbutt
			if($name == "Non-stop_improvize") $improvize = $value;
			if($name == "Max_items_produced") { // Max items produced
				$newvalue = intval($value);
				if($newvalue < 1) {
					$warning .= "<p>👉 <span class=\"red-text\">Max items produced must be a  positive integer. It has been set to 20</span></p>";
					$newvalue = 20;
					}
				$value = $newvalue;
				}
			if($name == "Produce_all_items") { // Produce all items
				if($improvize AND $value) {
					$warning .= "<p>👉 <span class=\"red-text\">You cannot produce all items in the Improvize mode</span></p>";
					$value = 0;
					}
				}
			if($name == "Show_graphics") $showgraphics = $value;
			if($name == "Default_buffer_size") { // Default buffer size
				$newvalue = intval($value);
				if($newvalue < 100) $newvalue = 1000;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Default buffer size must be a (not too small) positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
		//	if($i == 43) $value = 0; // Use buffer limit
			
			if($name == "Max_computation_time") { // Max computation time
				$newvalue = intval($value);
				if($newvalue < 1) $newvalue = 15;
				if($newvalue > 3600) $newvalue = 3600;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Max computation time must be a positive integer, max 3600 seconds: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Seed_for_randomization") { // Seed for randomization
				$newvalue = intval($value);
				if($newvalue < 0) $newvalue = - $newvalue;
				if(strcmp($newvalue,$value) <> 0)
					$warning .= "<p>👉 <span class=\"red-text\">Seed for randomization must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
		//	if($i == 46) $value = 0; // Use buffer limit
			if($name == "Note_convention" AND (!is_numeric($value) OR $value < 0 OR $value > 4 OR intval($value) <> $value)) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">Note convention must be an integer from 0 to 4: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
		//	if($i == 48 OR $i == 49) $value = 0; // StartFromOne and SmartCursor
			if($name == "GraphicScaleP" OR $name == "GraphicScaleQ") {
				$value = intval($value);
				if($value < 1) {
					$newvalue = 1;
					$warning .= "<p>👉 <span class=\"red-text\">GraphicScaleP and GraphicScaleQ must be positive integers: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
					$value = $newvalue;
					}
				}
		/*	if($i == 52 AND $value == '')
				$value = "<no input device>";
			if($i == 53 AND $value == '')
				$value = "<no output device>";
			if($i == 54) $value = 0; // Display bullets
			if($i == 58) $value = 1; // MIDI file format */
			if($name == "C4_(middle_C)_key_number" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 60;
				$warning .= "<p>👉 <span class=\"red-text\">C4 key number must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "A4_frequency_(diapason)" AND (!is_numeric($value) OR $value < 10)) {
				$newvalue = 440;
				$warning .= "<p>👉 <span class=\"red-text\">A4 frequency (diapason) must be a floating-point number greater than 10&nbsp;Hz: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Default_volume" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 90;
				$warning .= "<p>👉 <span class=\"red-text\">Default volume must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Volume_controller" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 7;
				$warning .= "<p>👉 <span class=\"red-text\">Volume controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Default_velocity" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 64;
				$warning .= "<p>👉 <span class=\"red-text\">Default velocity must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Default_panoramic" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 64;
				$warning .= "<p>👉 <span class=\"red-text\">Default panoramic must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if($name == "Panoramic_controller" AND (!is_numeric($value) OR $value < 0 OR $value > 127 OR intval($value) <> $value)) {
				$newvalue = 10;
				$warning .= "<p>👉 <span class=\"red-text\">Panoramic controller must be an integer from 0 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
			if(($name == "SamplingRate" OR $name == "Sampling_rate") AND (!is_numeric($value) OR $value < 1 OR intval($value) <> $value)) {
				$newvalue = 50;
				$warning .= "<p>👉 <span class=\"red-text\">Sampling rate must be a positive integer: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">”</span></p>";
				$value = $newvalue;
				}
		//	if($i == 71) $value = 39;
			// if($name == "Default_block_key_for_scale_in_Csound") $name = "Default_block_key";
			if(($name == "Default_block_key" OR $name == "Default_block_key_for_scale_in_Csound") AND (!is_numeric($value) OR intval($value) <> $value OR $value <= 10 OR $value > 127)) {
				$newvalue = 60;
				$warning .= "<p>👉 <span class=\"red-text\">“Default block key” must be an integer from 10 to 127: “</span><span class=\"green-text\">".$value."</span><span class=\"red-text\">” has been replaced with “</span><span class=\"green-text\">".$newvalue."</span><span class=\"red-text\">” (C4)</span></p>";
				$value = $newvalue;
				}
		//	if($i > 71 AND $i < 110) $value = 10;
			if($name == "ShowObjectGraph" OR $name == "Show_object_graph") $showObjectGraph = $value;
			if(($name == "ShowObjectGraph" OR $name == "Show_object_graph") AND !$showgraphics AND $value == 1) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is not set, Show ObjectGraph has been turned off</span></p>";
				$value = $newvalue;
				$showObjectGraph = 0;
				}
			if(($name == "ShowPianoRoll" OR $name == "Show_pianoroll") AND $showgraphics AND !$showObjectGraph AND $value == 0) {
				$newvalue = 1;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is set, at least Show PianoRoll has been turned on</span></p>";
				$value = $newvalue;
				}
			if(($name == "ShowPianoRoll" OR $name == "Show_pianoroll") AND !$showgraphics AND $value == 1) {
				$newvalue = 0;
				$warning .= "<p>👉 <span class=\"red-text\">As Show Graphics is not set, Show PianoRoll has been turned off</span></p>";
				$value = $newvalue;
				}
			if(strlen($value) == 0) $value = ' ';
		//	if(isset($parameter_edit[$i]) AND $parameter_edit[$i]) {
				$settings[$name]['value'] = $value;
				$settings[$name]['unit'] = $unit;
				$settings[$name]['boolean'] = $boolean;
		//		}
			}
		}
	$settings = recursive_strval($settings);
	$jsonData = json_encode($settings,JSON_PRETTY_PRINT);
    file_put_contents($this_file,$jsonData);
	echo $warning;
	$warning_bottom = $warning;
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(trim($content) == '') {
	$template = "settings_template";
	$content = @file_get_contents($template,TRUE);
	}
/* echo "dir = ".$dir."<br />";
echo "filename = ".$filename."<br />"; */
if($content[0] <> '{') { // Old format (not JSON)
	convert_to_json($dir,$filename);
	$content = @file_get_contents($this_file,TRUE);
	}

$settings = json_decode($content,TRUE);
echo "<p class=\"green-text\">".str_replace("\n","<br />",$settings['header'])."</p>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"saveparameters\" value=\"SAVE TO ‘".$filename."’\"></p>";
echo "<table class=\"thinborder\" style=\"border-spacing: 2px;\" cellpadding=\"2px;\">";
$new = TRUE; $unit = '';  $i = 0;
foreach($settings as $param => $value) {
//	for($i = 1; $i < count($parameter_name); $i++) {
//		$param = str_replace(' ','_',$parameter_name[$i]);
	if(!isset($settings[$param]) OR $param == "header") continue;
//	$table[$i] = $value = htmlspecialchars($settings[$param]);
	$boolean = 0; $i++;
//	$name = $parameter_name[$i];
//	$name = str_replace(' ','_',$param);
	if(isset($settings[$param]['value'])) {
		$table[$i] = $value = $settings[$param]['value'];
		$boolean = $settings[$param]['boolean'];
		$unit = $settings[$param]['unit'];
		}
	else {
		$table[$i] = $value = htmlspecialchars($settings[$param]);
		if($new) echo "<p style=\"text-align:left; color:red;\">👉 Converting old JSON format</p>";
//		$boolean = $parameter_yesno[$i];
		$boolean = $settings[$param]['boolean'];
//		$unit = $parameter_unit[$i];
		$unit = $settings[$param]['unit'];
		$new = FALSE;
		}
	echo "<tr>";
	echo "<td>";
	echo "<small>".$i."</small></td><td>";
	if($param == "Default_block_key_for_scale_in_Csound") $param = "Default_block_key";
	echo "<input type=\"hidden\" name=\"name_".$i."\" value=\"".$param."\">";
	echo "<input type=\"hidden\" name=\"boolean_".$i."\" value=\"".$boolean."\">";
	echo "<input type=\"hidden\" name=\"unit_".$i."\" value=\"".$unit."\">";
	$name = str_replace('_',' ',$param); 
	if($name == "ShowObjectGraph") $name = "Show object graph";
	if($name == "ShowPianoRoll") $name = "Show pianoroll";
	echo $name;
	echo "</td>";
	echo "<td>";
	if($boolean) {
		echo "<input type=\"checkbox\" name=\"parameter_".$i."\"";
		if($value > 0) echo " checked";
		echo ">";
		}
	else {
		echo "<input type=\"text\" name=\"parameter_".$i."\" size=\"20\" style=\"background-color:CornSilk; border: none;\" value=\"";
		echo $value;
		echo "\">";
		}
	echo "</td>";
	echo "<td>";
	echo $unit;
	if($name == "Qclock") {
		$Qclock = $table[$i];
		$Pclock = intval($table[$i - 1]);
		if($Pclock <> 0) {
			$metronome = $Qclock * 60 / $Pclock;
			if(intval($metronome) <> $metronome)
				$metronome = sprintf("%.4f",$Qclock * 60 / $Pclock);
			echo "👉 Metronome = <span class=\"red-text\">".$metronome."</span> <span class=\"green-text\">beats/minute</span>";
			}
		}
	if($name == "GraphicScaleQ") {
		$GraphicScaleP = intval($table[$i-1]);
		$GraphicScaleQ = intval($table[$i]);
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
