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
$current_directory = str_replace(SLASH.$filename,'',$file);

require_once("_header.php");
display_darklight();

echo "<p><small>Current directory = <a class=\"linkdotted\" href=\"index.php?path=".urlencode($current_directory)."\">".$dir."</a></small></p>";
echo link_to_help();
	
echo "<h2>Csound orchestra file <big>“<span class=\"turquoise-text\">".$filename."</span>”</big></h2>";

if(isset($_POST['savethisfile'])) {
	$content = $_POST['thistext'];
	$handle = fopen($this_file,"w");
	fwrite($handle,$content);
	fclose($handle);
	chmod($this_file,$permissions);
	echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">Saved “".$this_file."” file…</span>";
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
$extract_data = extract_data(TRUE,$content);
$content = $extract_data['content'];
$textarea_rows = 15;
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax;
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"".$textarea_rows."\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";
?>