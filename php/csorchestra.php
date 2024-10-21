<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "csorchestra.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
display_darklight();

echo "<p>Current directory = ".$dir."</p>";
echo link_to_help();
	
echo "<h2>Csound orchestra file <big>“<font color=\"MediumTurquoise\">".$filename."</font>”</big></h2>";

if(isset($_POST['savethisfile'])) {
	echo "<p id=\"timespan\" style=\"color:red;\">Saved file…</p>";
	$content = $_POST['thistext'];
	$handle = fopen($this_file,"w");
	fwrite($handle,$content);
	fclose($handle);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$extract_data = extract_data(TRUE,$content);
$content = $extract_data['content'];
$textarea_rows = 15;
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax;
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"".$textarea_rows."\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";
?>