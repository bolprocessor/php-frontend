<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "script.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);

require_once("_header.php");
echo "<p>Current directory = ".$dir."</p>";
echo link_to_help();

$temp_folder = str_replace(' ','_',$filename)."_".session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}

$script_variables = $temp_dir.$temp_folder.SLASH."script_variables.php";
$h_variables = fopen($script_variables,"w");
fwrite($h_variables,"<?php\n");
store($h_variables,"truc","3");

$script_status = $script_more = array();
$content = @file_get_contents("script-instructions.txt",TRUE);
if($content) {
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		$table2 = explode(chr(9),$line);
		$instruction = $table2[0];
		$status = $table2[1];
		if(isset($table2[2])) $more = $table2[2];
		else $more = '';
		$script_status[$instruction] = $status;
		store($h_variables,"script_status[\"".$instruction."\"]",$status);
		$script_more[$instruction] = $more;
		store($h_variables,"script_more[\"".$instruction."\"]",$more);
		}
	ksort($script_status);
	ksort($script_more);
	}

echo "<h2>Script <big>“<font color=\"green\">".$filename."</font>”</big></h2>";

if(isset($_POST['addinstruction'])) {
	$index = $_POST['i'];
	$i = 0;
	foreach($script_status as $instruction => $status) {
		if($status <> 1) continue;
		if($i == $index) {
			$entry = $instruction." ".$script_more[$instruction];
			$content = @file_get_contents($this_file,TRUE);
			$extract_data = extract_data(TRUE,$content);
			$content = $extract_data['content'];
			$content .= "\n".$entry;
			$_POST['thistext'] = $content;
		//	$_POST['savethisfile'] = TRUE;
			break;
			}
		$i++;
		}
	}
	
if(isset($_POST['savethisfile']) OR isset($_POST['checkscript']) OR isset($_POST['addinstruction'])) {
	if(isset($_POST['savethisfile']))
		echo "<p id=\"timespan\" style=\"color:red;\">Saved file…</p>";
	$content = $_POST['thistext'];
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Script saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle);
	}

if(isset($_POST['checkscript'])) {
	echo "<p><b>Checked script:</b></p>";
	$content = @file_get_contents($this_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$imax = count($table);
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$line = preg_replace("/^\/\/.*/u",'',$line);
		if($line == '') continue;
		$instruction =  get_instruction($line);
	//	$table2 = explode(' ',$line);
	//	$name = $table2[0];
		$status = 0; $more = $remark = '';
		if(isset($script_status[$instruction])) {
			$status = $script_status[$instruction];
			if($script_more[$instruction] <> '') $more = " ".$script_more[$instruction];
			else $more = '';
			}
		if($instruction == "Run script" OR $instruction == "Load project") {
			$search_file = $dir.str_replace($instruction." ",'',$line);
			if(!file_exists($search_file))
				$remark = "<font color=\"red\"> ➡ file not found</font>";
			else
				$remark = "<font color=\"blue\"> ➡ file found</font>";
			}
		switch($status) {
			case '0': $tag = "<font color=\"red\">(no)</font>"; break;
			case '1': $tag = "<font color=\"green\">✓</font>"; break;
			case '2': $tag = "<font color=\"blue\">(soon)</font>"; break;
			}
		$recoded_line = recode_tags($line);
		echo "&nbsp;&nbsp;&nbsp;".$tag." [<font color=\"green\">".$instruction.$more."</font>] ".$recoded_line.$remark."<br />";
		}
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$content = preg_replace("/[\x20]+/u",' ',$content);
echo "<table style=\"background-color:white;\"><tr>";
echo "<td>";
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$link = "script_exec.php?dir=".urlencode($dir);
$link .= "&file=".urlencode($filename);
$link .= "&temp_folder=".urlencode($temp_folder);
$window_name = window_name($filename);
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\">&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"checkscript\" value=\"CHECK THIS SCRIPT\">&nbsp;<input style=\"color:DarkBlue; background-color:Aquamarine;\" onclick=\"window.open('".$link."','".$window_name."','width=800,height=800,left=200'); return false;\" type=\"submit\" name=\"script_".$filename."\" value=\"RUN THIS SCRIPT\"></p>";

echo "<textarea name=\"thistext\" rows=\"30\" style=\"width:700px;\">".$content."</textarea>";
echo "<p style=\"text-align:left;\"><input style=\"background-color:azure;\" type=\"submit\" name=\"listinstructions\" value=\"LIST ALL SCRIPT INSTRUCTIONS\"> ➡ including obsolete ones</p>";
echo "</form>";
echo "</td>";
echo "<td>";
echo "<h3>Add script instruction:</h3>";
echo "<table style=\"background-color:white;\">";
$i = 0;
foreach($script_status as $instruction => $status) {
	if($status <> 1) continue;
	echo "<tr>";
	echo "<td>";
	$entry = $instruction." ".$script_more[$instruction];
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"i\" value=\"".$i."\">";
	echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"addinstruction\" value=\"".$entry."\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	$i++;
	}
echo "</tr></table>";
echo "</td></tr></table>";

$line = "§>\n";
$line = str_replace('§','?',$line);
fwrite($h_variables,$line);
fclose($h_variables);

if(isset($_POST['listinstructions'])) {
	list_script_instructions($script_status,$script_more);
	}

echo "</body>";
echo "</html>";

// ==== FUNCTIONS =====
function list_script_instructions($script_status,$script_more) {
	foreach($script_status as $instruction => $status) {
		switch($status) {
			case '0': $tag = "<font color=\"red\">(obsolete)</font>"; break;
			case '1': $tag = "<font color=\"green\">✓</font>"; break;
			case '2': $tag = "<font color=\"blue\">(soon)</font>"; break;
			}
		$more = $script_more[$instruction];
		echo $tag." ".$instruction." ".$more."<br />";
		}
	return;
	}
?>