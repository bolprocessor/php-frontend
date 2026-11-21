<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
// 2026-11-19 An orchestra of "csound_resources" or "csound_resources_private" is opened in the folder where it is, "csound_resources_private" first - by Claude
// 2026-11-20 Also used for SuperCollider SynthDef files ('*.scd') by scsynthdef.php, with $cs_kind = "supercollider"
$sc_page = ($cs_kind == "supercollider");
$kind_dirs = resource_dirs($cs_kind);
$table = explode(SLASH,$file);
if(count($table) == 2 AND ($table[0] == $kind_dirs['fixed_name'] OR $table[0] == $kind_dirs['my_name']))
	$file = csound_resource_folder($table[1]).SLASH.$table[1];
$url_this_page = ($sc_page ? "scsynthdef.php" : "csorchestra.php")."?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
$read_only = read_only_resource_folder($dir); // 2026-11-19 A fixed file of "csound_resources" can only be viewed
if($current_directory == $kind_dirs['my_name']) $current_directory = $kind_dirs['fixed_name']; // Both folders are listed together

require_once("_header.php");
display_darklight();

echo "<p><small>Current directory = <a class=\"linkdotted\" href=\"index.php?path=".urlencode($current_directory)."\">".$dir."</a></small></p>";
echo link_to_help();
	
echo "<h2>".($sc_page ? "SuperCollider SynthDef file" : "Csound orchestra file")." <big>“<span class=\"turquoise-text\">".$filename."</span>”</big>";
if(same_folder($dir,$kind_dirs['fixed'])) echo " <small>(fixed)</small>";
echo "</h2>";
if($sc_page) {
	echo "<p>SynthDefs of this file play the instruments of the same names in ‘-sc’ files. All files of ‘".$kind_dirs['fixed_name']."’ and ‘".$kind_dirs['my_name']."’ are compiled with sclang<br />and loaded into the SuperCollider server before a performance, those of ‘".$kind_dirs['my_name']."’ last: a SynthDef there replaces one of the same name.</p>";
	}
if($read_only) {
	read_only_message($filename,$sc_page ? "SuperCollider SynthDef file" : "Csound orchestra",$sc_page ? $kind_dirs["fixed_name"] : "csound_resources");
	read_only_page_script();
	unset($_POST['savethisfile'],$_POST['createfile']);
	}

if(isset($_POST['savethisfile'])) {
	$content = $_POST['thistext'];
	if($sc_page) $content = str_replace("\r\n","\n",$content);
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
if($sc_page) {
	// SuperCollider code is shown and saved as it is (extract_data() would remove its first comment lines and its tabulations)
	$content = str_replace("\r\n","\n",$content);
	$content = htmlspecialchars($content);
	}
else {
	$extract_data = extract_data(FALSE,TRUE,$content);
	$content = $extract_data['content'];
	}
$textarea_rows = 15;
$table = explode(chr(10),$content);
$imax = count($table);
if($imax > $textarea_rows) $textarea_rows = $imax;
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"".$textarea_rows."\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";
?>