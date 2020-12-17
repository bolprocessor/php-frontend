<?php
require_once("_basic_tasks.php");
if($path <> '') $filename = $path;
else $filename = "Bol Processor";
require_once("_header.php");
require_once("_settings.php");
$url_this_page = $this_page = "index.php";

echo "<table style=\"background-color:snow;\"><tr>";
echo "<td style=\"padding:4px; vertical-align:middle;\"><img src=\"pict/BP3-logo.png\" width=\"120px;\"/></td><td style=\"padding:4px; vertical-align:middle; white-space:nowrap;\">";


if($path <> '') {
	echo "<h2 style=\"text-align:center;\">Bol Processor ‘BP3’</h2>";
	$url_this_page .= "?path=".urlencode($path);
	$dir = $bp_application_path.$path;
	$table = explode(SLASH,$path);
	if(($n = count($table)) > 1) {
		$upper_dir = $table[$n - 2];
		}
	else $upper_dir = '';
	if($test) echo "upper_dir = ".$upper_dir."<br />";
	if($upper_dir == '') $link = $this_page;
	else $link = $this_page."?path=".urlencode($upper_dir);
	if($test) echo "link = ".$link."<br />";
	echo "<h3 style=\"text-align:center;\">[<a href=\"".$link."\">move to upper folder</a>]</h3></td>";
	echo "</tr></table>";
	}
else {
	echo "<h2 style=\"text-align:center;\">Welcome to Bol Processor ‘BP3’</h2>";
	echo "</td>";
	echo "<td style=\"padding-left:2em; vertical-align:middle;\">";
	echo "<p><i>This is a evaluation version of the interface<br />running the ‘bp’ multi-platform console.</i></p>";
	echo "</td>";
	echo "</tr></table>";
	$dir = $bp_application_path;
	$command = $dir."bp --help";
	$o = send_to_console($command);
	$n_messages = count($o);
	$no_error = FALSE;
	for($i = 0; $i < $n_messages; $i++) {
		$mssg = $o[$i];
		if(is_integer($pos=strpos($mssg,"Bol Processor")) AND $pos == 0) {
			$no_error = TRUE; break;
			}
		}
	$console = $dir."bp";
	$console_exe = $dir."bp.exe";
	if(!$no_error OR (!file_exists($console) AND !file_exists($console_exe))) {
		echo "<p>The console application ‘bp’ is not working or missing or misplaced… You can't run the application.</p>";
		$source = $dir."source";
		if(file_exists($source))
			echo "<p>Source files have been found. You can try to recompile ‘bp’, then reload this page.<br />➡ <a href=\"".$dir."compile.php\">Run the compiler</a></p>";
		else
			echo "<p>Source files have not been found. Return to <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a> and check your installation!</p>";
		die();
		}
	}

echo link_to_help();

if($test) echo "dir = ".$dir."<br />";
if($test) echo "url_this_page = ".$url_this_page."<br />";

$new_file = '';
if(isset($_POST['create_grammar'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	$filename = good_name($type,$filename,$name_mode);
	if($test) echo "filename = ".$filename."<br />";
	if($filename <> '') {
		$new_file = $filename;
		if($test) echo "newfile = ".$new_file."<br />";
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_grammar']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "grammar_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_data'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	$filename = good_name($type,$filename,$name_mode);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_data']);
			}
		else {
//			echo "<p style=\"color:red;\" id=\"timespan\">Creating ‘".$filename."’…</p>";
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_alphabet'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	$filename = good_name($type,$filename,$name_mode);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_alphabet']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "alphabet_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_timebase'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	$filename = good_name($type,$filename,$name_mode);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_timebase']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "timebase_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_prototypes'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	$filename = good_name($type,$filename,$name_mode);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_prototypes']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "prototypes_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_csound'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_csound']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "csound_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_csound_orchestra'])) {
	$filename = trim($_POST['filename']);
	$filename = str_replace(".orc",'',$filename);
	if($filename <> '') {
		$filename = $filename.".orc";
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
	//		unset($_POST['create_csound_orchestra']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
		/*	$template = "csound_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n"); */
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_script'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	echo $filename."<br />";
	$filename = good_name($type,$filename,$name_mode);
	echo $filename."<br />";
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_script']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			}
		}
	}

if(isset($_POST['delete_files'])) $delete_files = TRUE;
else $delete_files = FALSE;
$folder = str_replace($bp_application_path,'',$dir);
if($folder <> '') {
	echo "<h3>Content of folder <font color=\"red\">".$folder."</font>";
	if(!$delete_files AND $path <> $trash_folder) {
		echo "<br /><br /><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_files\" value=\"DELETE SOME FILES\">";
		echo "</form>";
		}
	echo "</h3>";
	}
// echo "dir = ".$dir."<br />";
$table = explode('_',$folder);
$extension = end($table);
if($dir <> $bp_application_path."php" AND $path <> $trash_folder AND $extension <> "temp" AND !$delete_files) {
	echo "<div style=\"float:right; background-color:white; padding:6px;\">";
	check_csound();
	if($path <> $csound_resources AND $path <> '') {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_grammar\" value=\"CREATE NEW GRAMMAR FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "gr";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_data\" value=\"CREATE NEW DATA FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "da";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_alphabet\" value=\"CREATE NEW ALPHABET FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "ho";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_prototypes\" value=\"CREATE NEW SOUND-OBJECT PROTOTYPE FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "mi";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_script\" value=\"CREATE NEW SCRIPT IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "sc";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_timebase\" value=\"CREATE NEW TIMEBASE IN THIS FOLDER\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "tb";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		}
	else if($path <> '') {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_csound\" value=\"CREATE NEW CSOUND RESOURCE FILE\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "cs";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_csound_orchestra\" value=\"CREATE NEW CSOUND ORCHESTRA FILE\">&nbsp;➡&nbsp;";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">.orc";
		echo "</p>";
		echo "</form>";
		}
	echo "</div>";
	}

if(isset($_POST['delete_checked_files'])) {
	$delete_files = FALSE;
	$delete_checked_files = TRUE;
	}
else $delete_checked_files = FALSE;
	
if($delete_files OR $delete_checked_files)
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if($delete_files) {
	echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES\"> <font color=\"red\">➡</font> cannot be reversed! <input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	}

$dircontent = scandir($dir);
$i_file = 0;
foreach($dircontent as $thisfile) {
	if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store" OR $thisfile == "php") continue;
	if(is_dir($dir.SLASH.$thisfile)) {
		$table = explode('_',$thisfile);
		$extension = end($table);
		if($path == '') $link = $this_page."?path=".urlencode($thisfile);
		else $link = $this_page."?path=".urlencode($path.SLASH.$thisfile);
		if($extension <> "temp")
			echo "<b><a href=\"".$link."\">".$thisfile."</a></b><br />";
		continue;
		}
	$table = explode(".",$thisfile);
	$extension = end($table);
	$table = explode("_",$thisfile);
	$prefix = $table[0];
	if($prefix == "trace") continue;
	$prefix = substr($thisfile,0,3);
	switch($prefix) {
		case '-gr':
			$type = "grammar"; break;
		case '-da':
			$type = "data"; break;
		case '-ho':
			$type = "alphabet"; break;
		case '-se':
			$type = "settings"; break;
		case '-cs':
			$type = "csound"; break;
		case '-mi':
			$type = "objects"; break;
		case '-or':
			$type = "orchestra"; break;
		case '-in':
			$type = "interaction"; break;
		case '-md':
			$type = "midisetup"; break;
		case '-tb':
			$type = "timebase"; break;
		case '-kb':
			$type = "keyboard"; break;
		case '-gl':
			$type = "glossary"; break;
		case '-sc':
			$type = "script"; break;
		default:
			$type = ''; break;
		}
	switch($extension) {
		case "bpgr": $type = "grammar"; break;
		case "bpda": $type = "data"; break;
		case "bpho": $type = "alphabet"; break;
		case "bpse": $type = "settings"; break;
		case "bpcs": $type = "csound"; break;
		case "bpmi": $type = "objects"; break;
		case "bpor": $type = "orchestra"; break;
		case "bpin": $type = "interaction"; break;
		case "bpmd": $type = "midisetup"; break;
		case "bptb": $type = "timebase"; break;
		case "bpkb": $type = "keyboard"; break;
		case "bpgl": $type = "glossary"; break;
		case "bpsc": $type = "script"; break;
		case "orc": $type = "csorchestra"; break;
		}
	if($path <> $csound_resources AND $path <> $trash_folder AND ($type == "csound" OR $type == "csorchestra")) {
		echo "Moved <font color=\"blue\">‘".$dir.SLASH.$thisfile."’</font> to <font color=\"blue\">‘".$dir_csound_resources.$thisfile."’</font><br />";
		rename($dir.SLASH.$thisfile,$dir_csound_resources.$thisfile);
		}
	else {
		$i_file++;
		if($delete_checked_files AND isset($_POST['delete_'.$i_file])) {
			echo "<p><font color=\"red\">➡</font> Deleted <font color=\"blue\">‘".$thisfile."’</font> (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
			rename($dir.SLASH.$thisfile,$dir_trash_folder.$thisfile);
			delete_settings($thisfile);
			continue;
			}
		if($delete_files) echo "<input type=\"checkbox\" name=\"delete_".$i_file."\"> ";
		if($type <> '') {
			$link = $type.".php?file=".urlencode($path.SLASH.$thisfile);
			if($new_file == $thisfile) echo "<font color=\"red\">➡</font> ";
			echo "<a target=\"_blank\" href=\"".$link."\">";
			echo $thisfile."</a> ";
			if($type == "grammar") echo "<font color=\"red\">";
			else if($type == "data") echo "<font color=\"gold\">";
			else if($type == "script") echo "<font color=\"blue\">";
			else if($type <> "settings") echo "<font color=\"lightgreen\">";
			echo $type."</font>";
			$time_saved = filemtime($dir.SLASH.$thisfile);
			echo " <small>➡ ".gmdate('Y-m-d H\hi',$time_saved)."</small>";
			echo "<br />";
			}
		else {
			echo $thisfile."<br />";
			}
		}
	}
if($delete_files)
	echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES\"> <font color=\"red\">➡</font> cannot be reversed! <input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
if($delete_files OR $delete_checked_files) echo "</form>";

$os_platform = getOS();
if(PHP_OS <> "WINNT" AND !is_integer(strpos($os_platform,"Windows")) AND $path <> $csound_resources) {
	echo "<hr>";
	echo "<p style=\"text-align:center;\"><a href=\"".$bp_application_path."compile.php\">Recompile BP</a> (be careful!)</p>";
	}
?>
