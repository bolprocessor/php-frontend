<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "interaction.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
save_settings("last_directory",$current_directory);

require_once("_header.php");
display_darklight();

echo "<p>";
$url = "index.php?path=".urlencode($current_directory);
echo "&nbsp;Workspace = <input class=\"edit\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\">";
echo link_to_help();

echo "<h2>Interaction file “".$filename."”</h2>";
save_settings("last_name",$filename);

if(isset($_POST['savethisfile'])) {
	$content = $_POST['thistext'];
	if(trim($content) <> '') {
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saved “".$this_file."” file…</span>";
		$handle = fopen($this_file,"w");
		$file_header = $top_header."\n// MIDI interaction file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$content);
		fclose($handle);
		}
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$grammar_file = $objects_file = $csound_file = $tonality_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";

echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" onclick=\"clearsave();\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" onchange=\"tellsave()\" rows=\"40\" style=\"width:700px;\">".$content."</textarea>";
echo "</form>";

display_more_buttons(FALSE,$content,$url_this_page,$dir,$grammar_file,$objects_file,$csound_file,$tonality_file,$alphabet_file,$settings_file,$orchestra_file,'',$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
?>
