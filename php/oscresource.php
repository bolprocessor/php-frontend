<?php
// 2026-11-24 Page of a file of "osc_resources" or "osc_resources_private": a program receiving real-time OSC (Python robot, Max or Pd patch, Max for Live device).
// Fixed files (osc_resources) are shown read-only: their Duplicate button in the resource folder copies them to "osc_resources_private",
// where they can be changed. Text files are shown in an editor; others (e.g. '.amxd') can only be downloaded - by Claude
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$kind_dirs = resource_dirs("osc");
$table = explode(SLASH,str_replace("/",SLASH,$file));
if(count($table) <> 2 OR ($table[0] <> $kind_dirs['fixed_name'] AND $table[0] <> $kind_dirs['my_name']) OR resource_kind_of_type(type_of_file($table[1])['type']) <> "osc") die();
$filename = basename($table[1]);
$file = resource_folder("osc",$filename).SLASH.$filename;
$url_this_page = "oscresource.php?file=".urlencode($file);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$read_only = read_only_resource_folder($dir);
$is_text = !str_ends_with($filename,".amxd"); // A Max for Live device is a binary container

require_once("_header.php");
display_darklight();

echo "<p><small>Current directory = <a class=\"linkdotted\" href=\"index.php?path=".urlencode($kind_dirs['fixed_name'])."\">".$dir."</a></small></p>";
echo link_to_help();

$what = str_ends_with($filename,".py") ? "Python program" : (str_ends_with($filename,".pd") ? "Pd patch" : (str_ends_with($filename,".maxpat") ? "Max patch" : "Max for Live device"));
echo "<h2>".$what." <big>“<span class=\"turquoise-text\">".htmlspecialchars($filename)."</span>”</big>";
if(same_folder($dir,$kind_dirs['fixed'])) echo " <small>(fixed)</small>";
echo "</h2>";
if(str_ends_with($filename,".py")) {
	$python_name = preg_replace("/\.py$/",'',$filename);
	echo "<p>A grammar or data naming it with the line <span class=\"green-text\">-py.".htmlspecialchars($python_name)."</span> on top shows a button to start it, with the output “Real-time OSC”.<br />It can also be run in a terminal, from its folder: <span class=\"green-text\">python3 ".htmlspecialchars($filename)." --help</span></p>";
	}
echo "<p><a href=\"".$this_file."\" download=\"".htmlspecialchars($filename)."\"><button type=\"button\" class=\"save\">⬇️ DOWNLOAD ‘".htmlspecialchars($filename)."’</button></a></p>";
if($read_only) {
	read_only_message($filename,"OSC resource",$kind_dirs['fixed_name']);
	read_only_page_script();
	unset($_POST['savethisfile']);
	}

if(isset($_POST['savethisfile']) AND $is_text AND isset($_POST['thistext'])) {
	$content = str_replace("\r\n","\n",$_POST['thistext']);
	if(file_put_contents($this_file,$content) === FALSE) echo "<p><span class=\"red-text\">➡ ‘".htmlspecialchars($filename)."’ could not be saved</span></p>";
	else {
		@chmod($this_file,$permissions);
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">Saved “".htmlspecialchars($filename)."” file…</span>";
		}
	}

if(!$is_text) {
	echo "<p>This file can't be shown here. Download it, or open it in its program.</p>";
	die();
	}
$content = @file_get_contents($this_file);
if($content === FALSE) {
	echo "<p><span class=\"red-text\">➡ ‘".htmlspecialchars($filename)."’ was not found</span></p>";
	die();
	}
// Shown and saved as it is: tabulations and comments are kept
$content = str_replace("\r\n","\n",$content);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
$textarea_rows = min(40,max(15,substr_count($content,"\n") + 1));
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if(!$read_only) echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".htmlspecialchars($filename)."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"".$textarea_rows."\" style=\"width:900px; font-family:monospace; tab-size:4;\"".($read_only ? " readonly" : '').">".htmlspecialchars($content)."</textarea>";
echo "</form>";
?>
