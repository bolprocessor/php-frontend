<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "data.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = "..".SLASH.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
echo "<p>Current directory = ".$dir."</p>";
echo link_to_help();

echo "<h3>Data file “".$filename."”</h3>";

if(isset($_POST['playitem']) OR isset($_POST['expanditem'])) {
	$i = $_POST['i'];
	$line = $_POST['line'];
	$line_recoded = recode_tags($line);
	echo "<p>Playing item: <font color=\"blue\">".$line_recoded."</font></p>";
	$data = $temp_dir."temp_".session_id()."outdata.bpda";
	$result_textfile = $temp_dir."temp_".session_id()."result.txt";
	if(file_exists($result_textfile)) unlink($result_textfile);
	$handle = fopen($data,"w");
	$file_header = $top_header."\n// Data saved as \"outdata.bpda\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$line."\n");
	fclose($handle);
	$alphabet = $settings = $objects = '';
	if($_POST['alphabet_file'] <> '') $alphabet = $dir.$_POST['alphabet_file'];
	if($_POST['settings_file'] <> '') $settings = $dir.$_POST['settings_file'];
	if($_POST['objects_file'] <> '') $objects = $dir.$_POST['objects_file'];
	$application_path = $bp_application_path;
	if(isset($_POST['playitem'])) $command = $application_path."bp play";
	if(isset($_POST['expanditem'])) $command = $application_path."bp expand-item";
	$command .= " -da ".$data;
	if($alphabet <> '') $command .= " -ho \"".$alphabet."\"";
	if(isset($_POST['playitem']) AND $objects <> '') $command .= " -mi \"".$objects."\"";
//	if($settings <> '') $command .= " -se \"".$settings."\"";
//	if(isset($_POST['playitem']) $command .= " -d --rtmidi ";
//	if(isset($_POST['playitem']) $command .= " -d --csoundout \"".$result_textfile."\"";
	if(isset($_POST['playitem'])) $command .= " -d --midiout ".$temp_dir."temp_".session_id()."check_play.mid";
	if(isset($_POST['expanditem'])) $command .= " -d -o ".$result_textfile;
//	$command .= " --traceout ".$tracefile;
	
	echo "<p style=\"color:black;\">".$command."</p>";
	$no_error = FALSE;
	exec($command,$o);
	$n_messages = count($o);
	if($n_messages > 0) {
		for($i=0; $i < $n_messages; $i++) {
			$mssg[$i] = $o[$i];
			$mssg[$i] = clean_up_encoding(TRUE,$mssg[$i]);
			if(is_integer($pos=strpos($mssg[$i],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
			}
		}
	$message = '';
	if(!$no_error) {
		$message .= "<p><font color=\"red\">➡ </font>This process:<br /><small>";
		for($i=0; $i < $n_messages; $i++) {
			$message .= "&nbsp;&nbsp;&nbsp;".$mssg[$i]."<br />";
			}
		$message .= "</small></p>";
		}
	echo $message;
	
	if(file_exists($result_textfile))  {
		echo "<p><font color=\"red\">➡ </font>Result:<br />";
		$content = @file_get_contents($result_textfile,TRUE);
		$table = explode(chr(10),$content);
		$imax = count($table);
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			$found = TRUE;
			$line = recode_tags($line);
			echo "&nbsp;&nbsp;&nbsp;".$line."<br />";
			}
		if(!$found) echo "&nbsp;&nbsp;&nbsp;No result…";
		echo "</p>";
		}
	}

if(isset($_POST['savethisfile'])) {
	echo "<p id=\"timespan\" style=\"color:red;\">Saved file…</p>";
	$content = $_POST['thistext'];
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Data saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$objects_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$objects_file = $extract_data['objects'];
$csound_file = $extract_data['csound'];
$alphabet_file = $extract_data['alphabet'];
$settings_file = $extract_data['settings'];
$orchestra_file = $extract_data['orchestra'];
$midisetup_file = $extract_data['midisetup'];
$timebase_file = $extract_data['timebase'];
$keyboard_file = $extract_data['keyboard'];
$glossary_file = $extract_data['glossary'];

echo "<table style=\"background-color:white;\"><tr>";
echo "<td>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";

echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
echo "<textarea name=\"thistext\" rows=\"40\" style=\"width:700px; background-color:Cornsilk;\">".$content."</textarea>";
echo "</form>";

display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
echo "</td><td>";
echo "<table style=\"background-color:azure;\">";
$table = explode(chr(10),$content);
$imax = count($table);
for($i = 0; $i < $imax; $i++) {
	$line = trim($table[$i]);
	$line = preg_replace("/\[.*\]/u",'',$line);
	if($line == '') continue;
	if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) continue;
	$line_recoded = recode_tags($line);
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"alphabet_file\" value=\"".$alphabet_file."\">";
	echo "<input type=\"hidden\" name=\"settings_file\" value=\"".$settings_file."\">";
	echo "<input type=\"hidden\" name=\"objects_file\" value=\"".$objects_file."\">";
	echo "<tr id=\"".$i."\"><td>";
	echo "<input type=\"hidden\" name=\"i\" value=\"".$i."\">";
	echo "<input type=\"hidden\" name=\"line\" value=\"".$line."\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"playitem\" value=\"PLAY\">&nbsp;";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"expanditem\" value=\"EXP\">&nbsp;";
	echo "<input type=\"hidden\" name=\"imax\" value=\"".$imax."\">";
	echo "</form>";
	echo $line_recoded;
	echo "</td></tr>";
	}
echo "</table>";
echo "</td></tr>";
echo "</table>";
?>
