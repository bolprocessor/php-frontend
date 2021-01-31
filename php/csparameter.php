<?php
require_once("_basic_tasks.php");

$url_this_page = "csparameter.php";
// $test = TRUE;

if(isset($_POST['parameter_name'])) {
	$parameter_name = $_POST['parameter_name'];
	$temp_folder = $_POST['temp_folder'];
	$folder_this_instrument = $_POST['folder_this_instrument'];
	$instrument_name = $_POST['instrument_name'];
	$instrument_index = $_POST['instrument_index'];
	$csfilename = $_POST['csfilename'];
	}
else {
	"Csound parameter's name is not known. First open the ‘-cs’ file!"; die();
	}
$this_title = $parameter_name;
require_once("_header.php");

if($test) echo "folder_this_instrument = ".$folder_this_instrument."<br />";
if($test) echo "temp_dir = ".$temp_dir."<br />";
if($test) echo "temp_folder = ".$temp_folder."<br />";
$parameter_file = $folder_this_instrument.SLASH.$parameter_name.".txt";
if($test) echo "parameter_name = ".$parameter_name."<br />";

echo "<p>Folder of this instrument: <font color=\"blue\">".$folder_this_instrument."</font>";
echo link_to_help();
echo "<h2>Csound parameter <big>“<font color=\"MediumTurquoise\">".$parameter_name."</font>”</big></h2>";
echo "<p>This parameter belongs to <big>_ins(".$instrument_index.") “<font color=\"blue\">".$instrument_name."</font>”</big> in file “<font color=\"blue\">".$csfilename."</font>”</p>";

if(isset($_POST['saveparameter'])) {
//	echo "parameter_name = ".$parameter_name."<br />";
	echo "<p id=\"timespan\"><font color=\"red\">Saving this parameter…</font>";
	$parameter_file = $folder_this_instrument.SLASH.$parameter_name.".txt";
	$handle = fopen($parameter_file,"w");
//	$handle = fopen("essai.txt","w");
	fwrite($handle,$parameter_name."\n");
	$comment = recode_tags($_POST['comment']);
	fwrite($handle,$comment."\n");
	$argmax = 0;
	$start_index = convert_empty($_POST['start_index']);
	if($start_index > $argmax) $argmax = $start_index;
	fwrite($handle,$start_index."\n");
	$end_index = convert_empty($_POST['end_index']);
	if($end_index > $argmax) $argmax = $end_index;
	fwrite($handle,$end_index."\n");
	$table_index = convert_empty($_POST['table_index']);
	if($table_index > $argmax) $argmax = $table_index;
	fwrite($handle,$table_index."\n");
	$default_value = $_POST['default_value'];
	fwrite($handle,$default_value."\n");
	$GEN = $_POST['GEN'];
	fwrite($handle,$GEN."\n");
	$mode = $_POST['mode'];
	fwrite($handle,$mode."\n");
	fclose($handle);
	$argmax_file = $folder_this_instrument.SLASH."argmax.php";
	set_argmax_argument($argmax_file,$parameter_name,$argmax);
	}

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"parameter_name\" value=\"".$parameter_name."\">";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"folder_this_instrument\" value=\"".$folder_this_instrument."\">";
echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$instrument_name."\">";
echo "<input type=\"hidden\" name=\"instrument_index\" value=\"".$instrument_index."\">";
echo "<input type=\"hidden\" name=\"csfilename\" value=\"".$csfilename."\">";

echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"saveparameter\" value=\"SAVE THIS PARAMETER\"></p>";

$content = file_get_contents($parameter_file,TRUE);
$table = explode(chr(10),$content);
$comment = $table[1];
echo "<p>Comment: <input type=\"text\" name=\"comment\" size=\"90\" value=\"".$comment."\"></p>";
echo "<table>";
echo "<tr>";
echo "<td></td>";
echo "<td>start</td>";
echo "<td>end</td>";
echo "<td>table</td>";
echo "</tr>";
echo "<tr>";
echo "<td style=\"text-align:right;\">Arguments:</td>";
echo "<td style=\"padding: 5px;\">";
$start_index = $table[2];
if($start_index == -1) $start_index = '';
echo "<input type=\"text\" name=\"start_index\" size=\"4\" value=\"".$start_index."\">";
echo "</td>";
echo "<td>";
$end_index = $table[3];
if($end_index == -1) $end_index = '';
echo "<input type=\"text\" name=\"end_index\" size=\"4\" value=\"".$end_index."\">";
echo "</td>";
echo "<td>";
$table_index = $table[4];
if($table_index == -1) $table_index = '';
echo "<input type=\"text\" name=\"table_index\" size=\"4\" value=\"".$table_index."\">";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan=\"2\" style=\"padding: 5px;\">";
$default_value = $table[5];
echo "Default value = <input type=\"text\" name=\"default_value\" size=\"12\" value=\"".$default_value."\">";
echo "</td>";
echo "<td colspan=\"2\" style=\"padding: 6px; vertical-align:middle; text-align:right;\">";
$GEN = intval($table[6]);
if($GEN < 10) $GEN = "0".$GEN;
echo "GEN <input type=\"text\" name=\"GEN\" size=\"4\" value=\"".$GEN."\">";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td style=\"padding: 6px; vertical-align:middle; text-align:right;\">Combination mode:</td>";
echo "<td colspan=\"3\" style=\"padding: 5px; vertical-align:middle;\">";
$mode = intval($table[7]);
echo "<input type=\"radio\" name=\"mode\" value=\"0\"";
if($mode == 0) echo " checked";
echo ">MULTval<br />";
echo "<input type=\"radio\" name=\"mode\" value=\"1\"";
if($mode == 1) echo " checked";
echo ">ADDval";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
?>