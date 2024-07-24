<?php
require_once("_basic_tasks.php");
if($path <> '') $filename = $path;
else $filename = "Bol Processor";
require_once("_header.php");
$url_this_page = $this_page = "index.php";

display_console_state();

echo "<table style=\"background-color:snow;\"><tr>";
echo "<td style=\"padding:1em; vertical-align:middle; border-radius:1em;\"><img src=\"pict/BP3-logo.png\" width=\"120px;\"/></td><td style=\"padding:1em; vertical-align:middle; white-space:nowrap; border-radius:1em;\">";

$test = FALSE;
if($path <> '') {
	echo "<h2 style=\"text-align:center;\">Bol Processor ‘BP3’</h2>";
	$url_this_page .= "?path=".urlencode($path);
	$dir = $bp_application_path.$path;
	if($test) echo "path = ".$path."<br />";
	$table = explode(SLASH,$path);
	if(($n = count($table)) > 1) {
		$table[$n - 1] = '';
		$upper_dir = implode(SLASH,$table);
		$upper_dir = substr($upper_dir,0,-1);
		}
	else $upper_dir = '';
	if($test) echo "upper_dir = ".$upper_dir."<br />";
	if($upper_dir == '') $upper_link = $this_page;
	else $upper_link = $this_page."?path=".urlencode($upper_dir);
	if($test) echo "link = ".$upper_link."<br />";
	echo "</tr></table>";
	}
else {
	echo "<h2 style=\"text-align:center;\">Welcome to Bol Processor ‘BP3’</h2>";
	echo "</td>";
	echo "<td style=\"padding:1em; vertical-align:middle;\">";
	echo "<p>This is a PHP interface running<br />the ‘<font color=\"blue\"><b>".$console."</b></font>’ multi-platform console.</p>";
	echo "</td>";
	echo "</tr></table>";
	$dir = $bp_application_path;
	}

$folder = str_replace($bp_application_path,'',$dir);

echo link_to_help();

if($test) echo "dir = ".$dir."<br />";
if($test) echo "url_this_page = ".$url_this_page."<br />";

// $name = "-ho.trial.mohanam ";
// echo "name = ".$name.", newname = ".new_name($name)."<br />";

$new_file = '';
if(isset($_POST['create_folder'])) {
	$foldername = trim($_POST['foldername']);
	$foldername = fix_new_name($foldername);
	if($test) echo "foldername = ".$foldername."<br />";
	if($foldername <> '') {
		if($test) echo "new folder = ".$new_file."<br />";
		if(file_exists($dir.SLASH.$foldername)) {
			echo "<p><font color=\"red\">This folder already exists:</font> <font color=\"red\">".$foldername."</font></p>";
			unset($_POST['create_folder']);
			}
		else {
			mkdir($dir.SLASH.$foldername);
			chmod($dir.SLASH.$foldername,0777);
			$new_file = $foldername;
			}
		}
	}

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
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "data_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
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
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
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

$delete_files = isset($_POST['delete_files']);
$rename_files = isset($_POST['rename_files']);
$move_files = isset($_POST['move_files']);

$show_dependencies = isset($_POST['show_dependencies']);
if($folder <> '')
	echo "<h3>Content of workspace: <font color=\"red\">".$folder."</font></h3>";
$table = explode('_',$folder);
$extension = end($table);

$link_list = "file_list.php?dir=".$dir;
echo " <input style=\"float:right; color:DarkBlue; background-color:Azure;\" onclick=\"window.open('".$link_list."','listfiles','width=300,height=600,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"copy list of files\">";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\">";
echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"\" value=\"RELOAD THIS PAGE\">";
if($folder <> '') echo "&nbsp;&nbsp;<big><font color=\"red\">↑</font>&nbsp;<a href=\"".$upper_link."\">UPPER FOLDER</a>&nbsp;<font color=\"red\">↑</font></big>";
echo "</p>";
echo "</form>";

echo "<script>\n";
echo "window.onload = function() {
    settogglecreate(); 
	};\n";
echo "</script>\n";

if($path == $trash_folder AND isset($_POST['empty_trash'])) {
//	echo $bp_application_path.$trash_folder."<br />";
	if(emptydirectory($bp_application_path.$trash_folder)) echo "<p id=\"refresh\"><font color=\"red\">Trash is empty!</font></p>";
	}

if($dir <> $bp_application_path."php" AND $path <> $trash_folder AND $extension <> "temp" AND !$delete_files AND !$rename_files AND !$move_files) {
	echo "<div style=\"float:right; background-color:white; padding:6px; border-radius: 15px;\">";
	check_csound();
	if($path <> $csound_resources) {
//	if($path <> $csound_resources AND $path <> '') {
		echo "<button style=\"float:right; background-color:azure; border-radius: 6px; font-size:large;\" onclick=\"togglecreate(); return false;\">CREATE FILES AND  FOLDERS</button>";
		echo "<div id=\"create\"  style=\"padding-top:36px;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_folder\" value=\"CREATE NEW FOLDER IN THIS WORKSPACE\"><br />named:&nbsp;";
		echo "<input type=\"text\" name=\"foldername\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		echo "</p>";
		echo "</form>";
		if($path <> '') {
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_grammar\" value=\"CREATE NEW GRAMMAR FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "gr";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_data\" value=\"CREATE NEW DATA FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "da";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_alphabet\" value=\"CREATE NEW ALPHABET FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "ho";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_prototypes\" value=\"CREATE NEW SOUND-OBJECT PROTOTYPE FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "mi";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_script\" value=\"CREATE NEW SCRIPT\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "sc";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_timebase\" value=\"CREATE NEW TIMEBASE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "tb";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			}
		echo "</div>";
		}
//	else if($path <> '') {
	else {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_csound\" value=\"CREATE NEW CSOUND RESOURCE FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "cs";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_csound_orchestra\" value=\"CREATE NEW CSOUND ORCHESTRA FILE\"><br />named:&nbsp";
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
if(isset($_POST['rename_checked_files'])) {
	$rename_files = FALSE;
	$rename_checked_files = TRUE;
	echo "<p id=\"refresh\"><font color=\"red\">Renamed or copied checked files</font></p>";
	}
else $rename_checked_files = FALSE;
if(isset($_POST['move_checked_files'])) {
	$move_files = FALSE;
	$move_checked_files = TRUE;
	}
else $move_checked_files = FALSE;

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$done = $seen = array();

$dest_folder = '';
if(isset($_POST['move_checked_files'])) {
	if(isset($_POST['folder_choice']) AND ($dest_folder = $_POST['folder_choice']) <> '') {
		echo "<p id=\"refresh\"><font color=\"red\">➡ Moving selected files/folders</font> to ‘<font color=\"blue\">".$dest_folder."</font>’</p>";
		}
	else {
		echo "<p><font color=\"red\">➡ No destination folder was selected, move canceled</font></p>";
		$move_checked_files = FALSE;
		}
	$move_files = FALSE;
	}

if($move_files) {
	$list_folders = array();
	$list_folders = folder_list($bp_application_path,$list_folders,'');
	$imax = count($list_folders);
	if($imax > 0) {
		echo "<div style=\"float:right; background-color:white; padding:6px; border-radius: 15px;\">";
		echo "<h3>Move all selected files to folder:</h3>";
		for($i = 0; $i < $imax; $i++) {
			$thisfolder = $list_folders[$i];
			if($thisfolder == $path) {
				$txt = "No move (cancel)";
				$thisfolder = '';
				}
			else $txt = $thisfolder;
			echo "<input type=\"radio\" name=\"folder_choice\" value=\"".$thisfolder."\">".$txt."<br />";
			}
		echo "<p style=\"margin-left:6px;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
		echo "</div>";
		}
	else {
		echo "<h3><font color=\"red\">ERROR: no folder found!</font></h3>";
		$move_files = FALSE;
		}
	}

if(!is_integer(strpos($path,"csound_resources"))) {
	echo "<div style=\"background-color:Cornsilk; padding-left:1em;  padding-right:1em; width:30%;\">";
	if(!$delete_files AND !$rename_files AND !$move_files AND $path <> $trash_folder) {
		echo "<input style=\"background-color:yellow; margin-top:1em;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files) {
		echo "&nbsp;<input style=\"background-color:yellow; margin-top:1em;\" title=\"Rename folders or files\" type=\"submit\" name=\"rename_files\" value=\"RENAME OR COPY\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files) {
		echo "&nbsp;<input style=\"background-color:yellow; margin-top:1em;\" title=\"Move folders or files\" type=\"submit\" name=\"move_files\" value=\"MOVE\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND $path == $trash_folder) {
		echo "<br /><br /><input style=\"background-color:red; color:white;\" title=\"Empty trash\" type=\"submit\" name=\"empty_trash\" value=\"EMPTY THIS TRASH\"> 👉 can't be reversed!<br /><br />";
		}
	if(!$show_dependencies AND !$delete_files AND !$move_files AND $path <> $trash_folder) echo "<br /><br /><input style=\"background-color:azure;\" title=\"Show dependencies of files (links to other files)\" type=\"submit\" name=\"show_dependencies\" value=\"SHOW DEPENDENCIES\"> (links between files)<br /><br />";
	else if($show_dependencies) echo "<br /><br /><input style=\"background-color:azure;\" type=\"submit\" value=\"HIDE DEPENDENCIES\"><br /><br />";
	echo "</div>";
	}

display_directory(FALSE,$dir,"directory");
if($path <> $trash_folder) echo "▶︎ <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">TRASH FOLDER</a><br />";
echo "<br />";

echo "<table style=\"background-color: Cornsilk;\">";
$show_grammar = isset($last_grammar_page) AND isset($last_grammar_name) AND file_exists("..".SLASH.$last_grammar_directory.SLASH.$last_grammar_name);
$show_alphabet = isset($last_data_page) AND isset($last_data_name) AND file_exists("..".SLASH.$last_data_directory.SLASH.$last_data_name);
if(!is_integer(strpos($path,"csound_resources"))) {
	if($path <> '' OR $show_grammar OR $show_alphabet) {
		echo "<tr>";
		echo "<th>";
		if(($n1 = display_directory(TRUE,$dir,"grammar")) > 0 OR $show_grammar) {
			echo "<h3>Grammar project(s)</h3>";
			if($show_grammar) {
				echo "<p style=\"background-color:snow; padding:6px; border-radius: 0.5em;\">Last visited: <a target=\"_blank\" href=\"".$last_grammar_page."\">".$last_grammar_name."</a>";
				if(isset($last_grammar_directory) AND $folder <> $last_grammar_directory) echo "<br />in workspace</font> <a href=\"index.php?path=".$last_grammar_directory."\">".$last_grammar_directory."</a></p>";
				}
			}
		echo "</th>";
		echo "<th>";
		if(($n2 = display_directory(TRUE,$dir,"data")) > 0 OR $show_alphabet) {
			echo "<h3>Data project(s)</h3>";
			if($show_alphabet) {
				echo "<p style=\"background-color:snow; padding:6px; border-radius: 0.5em;\">Last visited: <a target=\"_blank\" href=\"".$last_data_page."\">".$last_data_name."</a>";
				if(isset($last_data_directory) AND $folder <> $last_data_directory) echo "<br />in workspace</font> <a href=\"index.php?path=".$last_data_directory."\">".$last_data_directory."</a></p>";
				}
			}
		echo "</th>";
		if($path <> '') {
			echo "<th>";
			$n3 = display_directory(TRUE,$dir,"script");
			echo "<h3>Script project(s)</h3>";
			echo "</th>";
			}
		echo"</tr>";
		}
	if($path <> '') {
		echo "<tr>";
		echo "<td>";
		if($n1 > 0) display_directory(FALSE,$dir,"grammar");
		echo "</td>";
		echo "<td>";
		if($n2 > 0) display_directory(FALSE,$dir,"data");
		echo "</td>";
		echo "<td>";
		if($n3 > 0) display_directory(FALSE,$dir,"script");
		echo "</td>";
		echo"</tr>";
		echo "<tr>";
		echo "<th>";
		$n1 = display_directory(TRUE,$dir,"timebase");
		echo "<h3>Time base(s)</h3>";
		echo "</th>";
		echo "<th>";
		$n2 = display_directory(TRUE,$dir,"objects");
		echo "<h3>Sound objects</h3>";
		echo "</th>";
		echo "<th>";
		$n3 = display_directory(TRUE,$dir,"glossary");
		echo "<h3>Glossaries</h3>";
		echo "</th>";
		echo"</tr>";
		echo "<tr>";
		echo "<td>";
		if($n1 > 0) display_directory(FALSE,$dir,"timebase");
		echo "</td>";
		echo "<td>";
		if($n2 > 0) display_directory(FALSE,$dir,"objects");
		echo "</td>";
		echo "<td>";
		if($n3 > 0) display_directory(FALSE,$dir,"glossary");
		echo "</td>";
		echo"</tr>";
		echo "<tr>";
		echo "<th>";
		$n1 = display_directory(TRUE,$dir,"settings");
		echo "<h3>Settings</h3>";
		echo "</th>";
		echo "<th>";
		$n2 = display_directory(TRUE,$dir,"alphabet");
		echo "<h3>Alphabet(s)</h3>";
		echo "</th>";
		echo "<th>";
		echo "<h3>More</h3>";
		echo "</th>";
		echo "</tr>";
		}
	}
if($path <> '') {
	$n3 = display_directory(TRUE,$dir,'');
	echo "<tr>";
	if(!is_integer(strpos($path,"csound_resources"))) {
		echo "<td>";
		if($n1 > 0) display_directory(FALSE,$dir,"settings");
		echo "</td>";
		echo "<td>";
		if($n2 > 0) display_directory(FALSE,$dir,"alphabet");
		echo "</td>";
		}
	echo "<td>";
	if($n3 > 0) display_directory(FALSE,$dir,'');
	echo "</td>";
	echo"</tr>";
	}
echo "</table>";
echo "</form>";

function display_directory($test,$dir,$filter) {
	global $path,$move_files,$move_checked_files,$new_file,$csound_resources,$delete_checked_files,$rename_checked_files,$delete_files,$rename_files,$show_dependencies,$trash_folder,$this_page,$url_this_page,$dir_trash_folder,$bp_application_path,$dest_folder,$done,$seen,$dir_csound_resources;

//	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	$dircontent = scandir($dir);
	$i_file = $files_shown = 0;
	foreach($dircontent as $thisfile) { 
		$i_file++;
		if($thisfile[0] == '.' OR $thisfile[0] == '_') continue;
		if(is_integer($pos=strpos($thisfile,"BP2")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($thisfile,"License")) AND $pos == 0) continue;
		if(is_integer($pos=stripos($thisfile,"ReadMe")) AND $pos == 0) continue; 
		if($thisfile == "DerivedData" OR $thisfile == "resources" OR $thisfile == "scripts" OR $thisfile == "source" OR $thisfile == "temp_bolprocessor" OR $thisfile == "Makefile" OR $thisfile == "my_output" OR $thisfile == "php" OR $thisfile == "-se.startup" OR $thisfile == "LICENSE" OR $thisfile == "HowToBuild.txt" OR $thisfile == "BP3-To-Do.txt" OR $thisfile == "Bugs.txt" OR $thisfile == "ChangeLog" OR $thisfile == "Credits.txt" OR $thisfile == "HowToMakeARelease.txt" OR $thisfile == "y2k" OR $thisfile == "bp_compile_result.txt" OR $thisfile == "test.php") continue;
		if($test) {
			if(($new_name = new_name($thisfile)) <> $thisfile) rename($dir.SLASH.$thisfile,$dir.SLASH.$new_name);
			}
		if($move_files) $check_box = "<input type=\"checkbox\" name=\"move_".$i_file."\"> ";
		else $check_box = '';
		$this_file_moved = FALSE;
		if(!$test AND $move_checked_files AND isset($_POST['move_'.$i_file])) {
			unset($_POST['move_'.$i_file]);
			$source_file = $bp_application_path.$path.SLASH.$thisfile;
			$destination_file = $bp_application_path.$dest_folder.SLASH.$thisfile;
			if(is_integer($pos=strpos($dest_folder,$thisfile)) AND $pos == 0) {
				echo "<p><font color=\"red\">➡ Cannot move a folder to its own content</font></p>";
				}
			else if(file_exists($destination_file)) {
				if($dest_folder <> '') $the_dest = "‘<font color=\"blue\">".$dest_folder."</font>’";
				else $the_dest = "the root folder";
				echo "<p><font color=\"red\">➡ There is already</font> a file or folder named ‘<font color=\"blue\">".$thisfile."</font>’ in ".$the_dest."</p>";
				}
			else {
				if(is_dir($dir.SLASH.$thisfile)) {
					if(is_integer($pos=strpos($destination_file,$source_file)))
						echo "<p><font color=\"red\">➡ Cannot move</font> folder ‘<font color=\"blue\">".$thisfile."</font>’ into itself!</p>";
					else {
						rename($source_file,$destination_file);
						$this_file_moved = TRUE;
						}
					}
				else {
					rename($source_file,$destination_file);
					$this_file_moved = TRUE;
					}
				}
			}
		if(!$test AND $new_file == $thisfile) echo "<font color=\"red\">➡</font> ";
		$table = explode("_",$thisfile);
		$prefix = $table[0];
		if($prefix == "trace") continue;
		if(is_dir($dir.SLASH.$thisfile)) {
			$type = "directory";
			$name_mode = $prefix = $extension = '';
			}
		else {
			$type_of_file = type_of_file($thisfile);
			$type = $type_of_file['type'];
			$name_mode = $type_of_file['name_mode'];
			$prefix = $type_of_file['prefix'];
			$extension = $type_of_file['extension'];
			if($extension == "mid" OR  $extension == "txt" OR $extension == "mp3" OR $extension == "zip" OR $extension == "aif" OR $extension == "pdf" OR $extension == "php") continue;
			}
		if(isset($done[$thisfile]) OR ($filter <> '' AND $filter <> $type)) {
			continue;
			}
		if(!$test) $done[$thisfile] = TRUE;
		if($test AND isset($seen[$thisfile])) continue;
		if($test AND ($filter == '' OR $filter == $type)) $seen[$thisfile] = TRUE;
	
		if(!ok_output_location($thisfile,FALSE)) continue;
		if(!$test) echo $check_box;

		if(!$test AND $path <> $csound_resources AND $path <> $trash_folder AND ($type == "csound" OR $type == "csorchestra")) {
			echo "Moved ‘<font color=\"blue\">".$dir.SLASH.$thisfile."</font>’ to ‘<font color=\"blue\">".$dir_csound_resources.$thisfile."</font>’<br />";
			if(file_exists($dir_csound_resources.$thisfile)) @unlink($dir.SLASH.$thisfile);
			else rename($dir.SLASH.$thisfile,$dir_csound_resources.$thisfile);
			}
		else {
			$renamed = FALSE;
			if(!$test AND $delete_checked_files AND isset($_POST['delete_'.$i_file])) {
				echo "<p><font color=\"red\">➡</font> Deleted ‘<font color=\"blue\">".$thisfile."</font>’ (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
				rename($dir.SLASH.$thisfile,$dir_trash_folder.$thisfile);
				delete_settings($thisfile);
				continue;
				}
			$new_name = '';
			if(!$test AND $rename_checked_files AND $type <> '' AND isset($_POST['new_name_'.$i_file]) AND trim($_POST['new_name_'.$i_file]) <> '') {
				$new_name = trim($_POST['new_name_'.$i_file]);
				$make_copy = isset($_POST['copy_'.$i_file]);
				$new_name = fix_new_name($new_name);
				if($new_name <> '') {
					if($type <> "directory") {
						$table2 = explode(".",$new_name);
						$new_prefix = $table2[0];
						if(strlen($new_prefix) <> 3 OR !is_integer($pos=strpos($new_prefix,"-")) OR $pos <> 0)
						$new_prefix = '';
						$new_extension = end($table2);
						if($new_prefix.".".$new_extension == $new_name) $new_extension = '';
						if($extension <> '')
							$short_type = str_replace("bp",'',$extension);
						if($prefix <> '')
							$short_type = str_replace("-",'',$prefix);
						if($new_extension <> '')
							$new_short_type = str_replace("bp",'',$new_extension);
						if($new_prefix <> '')
							$new_short_type = str_replace("-",'',$new_prefix);
						if($new_extension <> '' AND $new_short_type == $short_type)
							$name_mode = "extension";
						if($new_prefix <> '' AND $new_short_type == $short_type)
							$name_mode = "prefix";
						$new_name = good_name($short_type,$new_name,$name_mode);
						}
					$old_name = $thisfile;
					if(file_exists($dir.SLASH.$new_name)) {
						echo "<font color=\"red\">➡</font> Can't rename to existing ‘".$new_name."’: ";
						}
					else {
						if($make_copy) {
							if($type <> "directory") copy($dir.SLASH.$old_name,$dir.SLASH.$new_name);
							else rcopy($dir.SLASH.$old_name,$dir.SLASH.$new_name);
							$link = $type.".php?file=".urlencode($path.SLASH.$new_name);
							echo "‘<font color=\"green\">".$old_name."</font>’ <font color=\"red\">➡</font> copied to <a target=\"_blank\" href=\"".$link."\">".$new_name."</a><br />";
							}
						else {
							rename($dir.SLASH.$old_name,$dir.SLASH.$new_name);
							if($type <> "directory") change_occurrences_name_in_files($dir,$old_name,$new_name);
							$thisfile = $new_name;
							$renamed = TRUE;
							}
						}
					}
				}
			$this_is_directory = is_dir($dir.SLASH.$thisfile);
			if(!$test AND $this_is_directory) {
				if(hidden_directory($thisfile)) continue;
				echo "▶︎ ";
				}
			else if($thisfile == "bp") continue;

	//		if(!$test) echo $check_box;

			if(!$test AND $delete_files) echo "<input type=\"checkbox\" name=\"delete_".$i_file."\"> ";
			if($type <> '') {
				$files_shown++;
				}
			if(!$test AND $type <> '' AND !$this_file_moved) {
				if($this_is_directory) {
					$table = explode('_',$thisfile);
					$extension = end($table);
					if($path == '') $link = $this_page."?path=".urlencode($thisfile);
					else $link = $this_page."?path=".urlencode($path.SLASH.$thisfile);
					if($extension == "temp") $link = '';
					$this_is_directory = TRUE;
					}
				else $link = $type.".php?file=".urlencode($path.SLASH.$thisfile);
				if($link <> '') {
					if($this_is_directory) echo "<a href=\"".$link."\">";
					else echo "<a target=\"_blank\" href=\"".$link."\">";
					}
				if($this_is_directory) echo "<b>";
				echo $thisfile;
				if($link <> '') echo "</a>";
				if($this_is_directory) echo "</b>";
				echo "&nbsp;";
				if($renamed) echo "(<font color=\"red\">renamed</font>)&nbsp;";
				if($rename_files) {
					echo "&nbsp;➡&nbsp;&nbsp;<input type=\"text\" style=\"border:2px; solid #dadada; border-bottom-style: groove; text-align:left;\" name=\"new_name_".$i_file."\" size=\"30\" value=\"\">";
					echo "<input type=\"checkbox\" name=\"copy_".$i_file."\">&nbsp;➡&nbsp;make a copy";
					}
				else if(!$this_is_directory) {
					$time_saved = filemtime($dir.SLASH.$thisfile);
					echo "&nbsp;<small>&nbsp;".gmdate('Y-m-d H\hi',$time_saved)."</small>";
					}
				echo "<br />";
				if($show_dependencies) {
					$dependencies = find_dependencies($dir,$thisfile);
					if(count($dependencies) > 0) {
						for($i = 0; $i < count($dependencies); $i++)
							echo "<small>&nbsp;&nbsp;▷&nbsp;".$dependencies[$i]."</small><br />";
						}
					}
				}
			}
		}
	if($files_shown == 0) return $files_shown;
	if(!$test AND $move_files)
		echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-
		color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	if(!$test AND $delete_files)
		echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES/FOLDERS\"> <font color=\"red\">➡</font> can be reversed <input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	if(!$test AND $rename_files)
		echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"rename_checked_files\" value=\"RENAME OR COPY CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	return $files_shown;
	}

function folder_list($dir,$list,$path) {
	global $bp_application_path;
	$dircontent = scandir($dir);
	foreach($dircontent as $thisfile) {
		if($thisfile[0] == '.') continue;
		if(!is_dir($dir.$thisfile)) continue;
		$new_path = $path.SLASH.$thisfile;
		if($dir == $bp_application_path) {
			if(!ok_output_location($thisfile,FALSE)) continue;
			$new_path = $thisfile;
			}
		$list[] = $new_path;
		$list = folder_list($dir.$thisfile.SLASH,$list,$new_path);
		}
	return $list;
	}
?>
