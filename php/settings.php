<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "settings.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = "..".SLASH.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
echo "<p>Current directory = ".$dir."</p>";
echo link_to_help();

echo "<h3>Settings file “".$filename."”</h3>";

$bp_parameter_names = @file_get_contents("bp_parameter_names.txt",TRUE);
if($bp_parameter_names === FALSE) echo "ERROR reading ‘bp_parameter_names.txt’";
$table = explode(chr(10),$bp_parameter_names);
$imax = count($table);
$imax_parameters = 0;
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	if($line == "-- end --") break;
	$imax_parameters++;
	$table2 = explode(chr(9),$line);
	$x = str_replace(chr(9),".",$line);
//	echo $x."<br />";
	if(count($table2) < 3) echo "ERR: ".$table2[0]."<br />";
	$parameter_name[$i] = $table2[0];
	$parameter_unit[$i] = $table2[1];
	$parameter_edit[$i] = $table2[2];
	if(count($table2) > 3 AND $table2[3] > 0)
		$parameter_yesno[$i] = TRUE;
	else $parameter_yesno[$i] = FALSE;
	}

if(isset($_POST['saveparameters'])) {
	echo "<p id=\"timespan\" style=\"color:red;\">Saved ".$imax_parameters." parameters…</p>";
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
		if($i == 52 AND $value == '')
			$value = "<no input device>";
		if($i == 53 AND $value == '')
			$value = "<no output device>";
		if(strlen($value) == 0) $value = ' ';
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
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"saveparameters\" value=\"SAVE PARAMETERS TO ‘".$filename."’\"></p>";
echo "<table style=\"border-spacing: 2px;\" cellpadding=\"2px;\">";

$table = explode(chr(10),$content);
$imax_file = count($table);
if($imax_file <> $imax_parameters) {
	echo "<p style=\"color:red;\">WARNING: imax_parameters = ".$imax_parameters.", imax_file = ".$imax_file."</p>";
	}
else echo "<p style=\"color:blue;\">".$imax_file." parameters</p>";
// echo "<input type=\"hidden\" name=\"imax_parameters\" value=\"".$imax_parameters."\">";

$imax = $imax_file;
if($imax_file < $imax_parameters) $imax = $imax_parameters;
for($i = 0; $i < $imax; $i++) {
	if(!isset($parameter_edit[$i]) OR !$parameter_edit[$i]) {
		if(isset($table[$i])) $value = $table[$i];
		else $value = '';
		echo "<input type=\"hidden\" name=\"parameter_".$i."\" value=\"".$value."\">"; // $$$$
		}
	else { // $$$
		echo "<tr style=\"background-color:white;\">";
		echo "<td>";
		echo $i.") ";
		if(isset($parameter_name[$i])) echo $parameter_name[$i];
		echo "</td>";
		echo "<td>";
		if(isset($parameter_edit[$i]) AND $parameter_edit[$i]) {
			if($parameter_yesno[$i]) {
				echo "<input type=\"checkbox\" name=\"parameter_".$i."\"";
         		if(isset($table[$i]) AND $table[$i] > 0) echo " checked";
         		echo ">";
				}
			else {
				echo "<input type=\"text\" name=\"parameter_".$i."\" size=\"20\" style=\"background-color:CornSilk; border: none;\" value=\"";
				if(isset($table[$i])) echo $table[$i];
				echo "\">";
				}
			}
		else if(isset($table[$i])) echo $table[$i];
		echo "</td>";
		echo "<td>";
		if(isset($parameter_unit[$i])) {
			echo $parameter_unit[$i];
			if($i == 8) {
				$Pclock = intval($table[$i - 1]);
				if($Pclock == 0) {
					}
				else {
					$Qclock = $table[$i];
					$metronome = $Qclock * 60 / $Pclock;
					if(intval($metronome) <> $metronome)
						$metronome = sprintf("%.4f",$Qclock * 60 / $Pclock);
					echo "Metronome = <font color=\"red\">".$metronome."</font> <font color=\"blue\">beats/minute</font>";
					}
				}
			}
		echo "</td>";
		echo "</tr>";
		} // $$$
	}
echo "</table>";
echo "</form>";

/*
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"10\" style=\"width:700px; background-color:Cornsilk;\">".$content."</textarea>";
echo "</form>"; */
?>
