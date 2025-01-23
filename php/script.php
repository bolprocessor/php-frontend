<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$file = str_replace(' ','+',$file); // (compatibility with BP2)
$url_this_page = "script.php?file=".$file;
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
if($filename[0] == ' ') $filename[0] = "+"; // (compatibility with BP2)
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
save_settings("last_directory",$current_directory);

require_once("_header.php");
display_console_state();

echo "<p>";
$url = "index.php?path=".urlencode($current_directory);
echo "&nbsp;Workspace = <input title=\"List this workspace\" class=\"edit\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\">";

echo link_to_help();

$need_to_save = FALSE;
$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) mkdir($temp_dir.$temp_folder);

$script_variables = $temp_dir.$temp_folder.SLASH."script_variables.php";
// echo "script_variables = ".$script_variables."<br />";
$dirPath = realpath("..")."/temp_bolprocessor/".$temp_folder;
if(!file_exists($dirPath)) {
    mkdir($dirPath, 0775, true);
    }
create_variables($script_variables);
require_once($script_variables);

echo "<h2>Script <big>“<span class=\"turquoise-text\">".$filename."</span>”</big></h2>";
save_settings("last_name",$filename); 

if(isset($_POST['addinstruction'])) {
	$index = $_POST['i'];
	$i = 0;
	foreach($script_status as $instruction => $status) {
		if($status <> 1) continue;
		if($i == $index) {
			$entry = $instruction." ".$script_more[$instruction];
			$content = @file_get_contents($this_file);
			$content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
	
if($need_to_save OR isset($_POST['savethisfile']) OR isset($_POST['checkscript']) OR isset($_POST['addinstruction'])) {
	if(isset($_POST['savethisfile']))
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saved “".$this_file."” file…</span>";
	$content = $_POST['thistext'];

	$content = recode_entities($content);
	$content = preg_replace("/ +/u",' ',$content);
	save($this_file,$filename,$top_header,$content);


/*	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Script saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle); */
	}

if(isset($_POST['checkscript'])) {
	echo "<p><b>Checked script:</b></p>";
	$content = @file_get_contents($this_file);
	$content = mb_convert_encoding($content,'UTF-8','UTF-8');
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
		if($instruction == "Run script" OR $instruction == "Load grammar" OR $instruction == "Settings") {
			$search_file = $dir.str_replace($instruction." ",'',$line);
			if(!file_exists($search_file))
				$remark = "<span class=\"red-text\"> ➡ file not found</span>";
			else
				$remark = "<span class=\"green-text\"> ➡ file found</span>";
			}
		switch($status) {
			case '0': $tag = "<span class=\"red-text\">(no)</span>"; break;
			case '1': $tag = "<span class=\"turquoise-text\">✓</span>"; break;
			case '2': $tag = "<span class=\"green-text\">(soon)</span>"; break;
			}
		$recoded_line = recode_tags($line);
		echo "&nbsp;&nbsp;&nbsp;".$tag." [<span class=\"turquoise-text\">".$instruction.$more."</span>] ".$recoded_line.$remark."<br />";
		}
	}

if(!isset($_POST['running'])) {
	try_create_new_file($this_file,$filename);
	$content = @file_get_contents($this_file);
	if($content === FALSE) ask_create_new_file($url_this_page,$filename);
	$extract_data = extract_data(TRUE,$content);
	echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
	$content = $extract_data['content'];
	$content = preg_replace("/[\x20]+/u",' ',$content);
	}
else {
	// We don't reload the file if the alert "This project needs to be saved" was seen
	$content = $_POST['thistext'];
	}

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p><button class=\"edit big\" onclick=\"togglesearch(); return false;\">SEARCH & REPLACE</button></p>";
echo "<table border=\"0\"><tr>";
echo "<td>";
find_replace_form();

echo "<table><tr>";
echo "<td>";
$link = "script_exec.php?dir=".urlencode($dir);
$link .= "&file=".urlencode($filename);
$link .= "&temp_folder=".urlencode($temp_folder);
$window_name = window_name($filename);
echo "<p style=\"text-align:left;\"><input class=\"save big\" type=\"submit\" onclick=\"clearsave();\" id=\"here\"  name=\"savethisfile\" value=\"SAVE ‘".$filename."’\">&nbsp;";
echo "<input class=\"save big\" type=\"submit\" name=\"checkscript\" onmouseover=\"checksaved();\" value=\"CHECK THIS SCRIPT\">&nbsp;";
echo "<input class=\"produce big\" onclick=\"if(checksaved()) {window.open('".$link."','".$window_name."','width=800,height=800,left=150,toolbar=yes'); return false;}\" type=\"submit\" name=\"running\" value=\"RUN THIS SCRIPT\"></p>";

$content = do_replace($content);
echo "<textarea name=\"thistext\" onchange=\"tellsave()\" rows=\"30\" style=\"width:700px;\">".$content."</textarea>";
echo "<p style=\"text-align:left;\"><input class=\"edit\" type=\"submit\" name=\"listinstructions\" value=\"LIST ALL SCRIPT INSTRUCTIONS\"> ➡ including obsolete ones</p>";
echo "</form>";
echo "</td>";
echo "<td>";
echo "<h3>Add script instruction:</h3>";
echo "<table>";
$i = 0;
foreach($script_status as $instruction => $status) {
	if($status <> 1) continue;
	echo "<tr>";
	echo "<td>";
	$entry = $instruction." ".$script_more[$instruction];
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"i\" value=\"".$i."\">";
	echo "<input class=\"edit\" onclick=\"tellsave()\" type=\"submit\" name=\"addinstruction\" value=\"".$entry."\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	$i++;
	}
echo "</tr></table>";
echo "</td></tr></table>";

if(isset($_POST['listinstructions'])) {
	list_script_instructions($script_status,$script_more);
	}

echo "<script>\n";
echo "window.onload = function() {
	settogglesearch();
	};\n";
echo "</script>\n";
echo "</body>";
echo "</html>";

// ==== FUNCTIONS =====
function list_script_instructions($script_status,$script_more) {
	foreach($script_status as $instruction => $status) {
		switch($status) {
			case '0': $tag = "<span class=\"red-text\">(obsolete)</span>"; break;
			case '1': $tag = "<span class=\"turquoise-text\">✓</span>"; break;
			case '2': $tag = "<span class=\"green-text\">(soon)</span>"; break;
			}
		$more = $script_more[$instruction];
		echo $tag." ".$instruction." ".$more."<br />";
		}
	return;
	}

function save($this_file,$filename,$top_header,$save_content) {
	if(trim($save_content) == '') return;
	$handle = fopen($this_file, "w");
	if($handle) {
		$file_header = $top_header . "\n// Script saved as \"" . $filename . "\". Date: " . gmdate('Y-m-d H:i:s');
		fwrite($handle, $file_header . "\n");
		fwrite($handle, $save_content);
		fclose($handle);
		}
	return;
	}
?>