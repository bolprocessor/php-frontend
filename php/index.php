<?php
require_once("_basic_tasks.php");
if($path <> '') $filename = $path;
else $filename = "Bol Processor";
require_once("_header.php");
require_once("_settings.php");
$url_this_page = $this_page = "index.php";

echo "<table style=\"background-color:snow;\"><tr>";
echo "<td style=\"padding:4px; vertical-align:middle;\"><img src=\"pict/BP3-logo.png\" width=\"120px;\"/></td><td style=\"padding:4px; vertical-align:middle; white-space:nowrap;\">";

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

$folder = str_replace($bp_application_path,'',$dir);
if(isset($last_page) AND isset($last_name) AND $last_name <> '') {
	echo "<div style=\"float:right; background-color:white; padding:6px;\"><big>Last page visited:<br /><font color=\"red\">➡</font> <a target=\"_blank\" href=\"".$last_page."\">".$last_name."</a>";
	if($folder <> $last_directory) echo "<br />in workspace</font> <a href=\"index.php?path=".$last_directory."\">".$last_directory."</a>";
	echo "</big></div><br /><br />";
	}

echo link_to_help();

if($test) echo "dir = ".$dir."<br />";
if($test) echo "url_this_page = ".$url_this_page."<br />";

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
	echo "<h3>Content of folder <font color=\"red\">".$folder."</font></h3>";
$table = explode('_',$folder);
$extension = end($table);

$link_list = "file_list.php?dir=".$dir;
echo " <input style=\"float:right; color:DarkBlue; background-color:Azure;\" onclick=\"window.open('".$link_list."','listfiles','width=300,height=600,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"copy list of files\">";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\">";
echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"\" value=\"RELOAD THIS PAGE\">";
if($folder <> '') echo "&nbsp;&nbsp;<big><a href=\"".$upper_link."\">↑&nbsp;move to upper folder&nbsp;↑</a></big>";
echo "</p>";
echo "</form>";

if($dir <> $bp_application_path."php" AND $path <> $trash_folder AND $extension <> "temp" AND !$delete_files AND !$rename_files AND !$move_files) {
	echo "<div style=\"float:right; background-color:white; padding:6px; border-radius: 15px;\">";
	check_csound();
	if($path <> $csound_resources AND $path <> '') {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_folder\" value=\"CREATE NEW FOLDER IN THIS FOLDER\">&nbsp;named:&nbsp;";
		echo "<input type=\"text\" name=\"foldername\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		echo "</p>";
		echo "</form>";
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

if($folder <> '') {
	echo "<div style=\"background-color:white; padding:1em; border-radius: 15px;\">";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	if(!$delete_files AND !$rename_files AND !$move_files AND $path <> $trash_folder) {
		echo "<input style=\"background-color:yellow;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND $path <> $trash_folder) {
		echo "&nbsp;<input style=\"background-color:yellow;\" title=\"Rename folders or files\" type=\"submit\" name=\"rename_files\" value=\"RENAME OR COPY\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND $path <> $trash_folder) {
		echo "&nbsp;<input style=\"background-color:yellow;\" title=\"Move folders or files\" type=\"submit\" name=\"move_files\" value=\"MOVE\">";
		}
	if(!$show_dependencies AND !$delete_files AND !$move_files AND $path <> $trash_folder) echo "<br /><br /><input style=\"background-color:yellow;\" title=\"Show dependencies of files (links to other files)\" type=\"submit\" name=\"show_dependencies\" value=\"SHOW DEPENDENCIES\"> (links between files)";
	else if($show_dependencies) echo "<br /><br /><input style=\"background-color:azure;\" type=\"submit\" value=\"HIDE DEPENDENCIES\">";
	echo "</form></div>";
	}

if(isset($_POST['delete_checked_files'])) {
	$delete_files = FALSE;
	$delete_checked_files = TRUE;
	}
else $delete_checked_files = FALSE;
if(isset($_POST['rename_checked_files'])) {
	$rename_files = FALSE;
	$rename_checked_files = TRUE;
	}
else $rename_checked_files = FALSE;
if(isset($_POST['move_checked_files'])) {
	$move_files = FALSE;
	$move_checked_files = TRUE;
	}
else $move_checked_files = FALSE;
	
if($delete_files OR $delete_checked_files OR $rename_files OR $move_files OR $move_checked_files)
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if($delete_files) {
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↓</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES/FOLDERS\"> <font color=\"red\">➡</font> can be reversed <input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	}
if($rename_files) {
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↓</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"rename_checked_files\" value=\"RENAME OR COPY CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	}
if($move_files) {
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↓</big></font>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	}

$dest_folder = '';
if(isset($_POST['move_checked_files'])) {
	if(isset($_POST['folder_choice']) AND ($dest_folder = $_POST['folder_choice']) <> '') {
		echo "<p><font color=\"red\">➡</font> Moving selected files/folders to ‘<font color=\"blue\">".$dest_folder."</font>’</p>";
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

$dircontent = scandir($dir);
$i_file = 0;
foreach($dircontent as $thisfile) {
	if($thisfile[0] == '.' OR $thisfile[0] == '_' OR $thisfile == "php") continue;
	if($thisfile == "DerivedData" OR $thisfile == "resources" OR $thisfile == "scripts" OR $thisfile == "source" OR $thisfile == "temp_bolprocessor" OR $thisfile == "Makefile" OR $thisfile == "my_output") continue;
	$i_file++;
	if($move_files) echo "<input type=\"checkbox\" name=\"move_".$i_file."\"> ";
	$this_file_moved = FALSE;
	if($move_checked_files AND isset($_POST['move_'.$i_file])) {
		$source_file = $bp_application_path.$path.SLASH.$thisfile;
		$destination_file = $bp_application_path.$dest_folder.SLASH.$thisfile;
	//	echo $source_file." -> ".$destination_file."<br />";
		if(file_exists($destination_file))
			echo "<p><font color=\"red\">➡ There is already</font> a file or folder named ‘<font color=\"blue\">".$thisfile."</font>’ in ‘<font color=\"blue\">".$dest_folder."</font>’</p>";
		else {
			if(is_dir($dir.SLASH.$thisfile)) {
				if(is_integer($pos=strpos($destination_file,$source_file)))
					echo "<p><font color=\"red\">➡ Cannot move</font> folder ‘<font color=\"blue\">".$thisfile."</font>’ into itself!</p>";
				else {
					rename($source_file,$destination_file);
				//	unlink($source_file);
					$this_file_moved = TRUE;
					}
				}
			else {
				rename($source_file,$destination_file);
				$this_file_moved = TRUE;
				}
			if($this_file_moved) echo "<font color=\"red\">➡</font> Moved ";
			}
		}
	if($new_file == $thisfile) echo "<font color=\"red\">➡</font> ";
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
		}
	if($path <> $csound_resources AND $path <> $trash_folder AND ($type == "csound" OR $type == "csorchestra")) {
		echo "Moved ‘<font color=\"blue\">".$dir.SLASH.$thisfile."</font>’ to ‘<font color=\"blue\">".$dir_csound_resources.$thisfile."</font>’<br />";
		rename($dir.SLASH.$thisfile,$dir_csound_resources.$thisfile);
		}
	else {
		$renamed = FALSE;
		if($delete_checked_files AND isset($_POST['delete_'.$i_file])) {
			echo "<p><font color=\"red\">➡</font> Deleted ‘<font color=\"blue\">".$thisfile."</font>’ (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
			rename($dir.SLASH.$thisfile,$dir_trash_folder.$thisfile);
			delete_settings($thisfile);
			continue;
			}
		$new_name = '';
//		if($rename_checked_files AND $type <> '' AND (isset($_POST['rename_'.$i_file]) OR (isset($_POST['new_name_'.$i_file]) AND trim($_POST['new_name_'.$i_file]) <> ''))) {
		if($rename_checked_files AND $type <> '' AND isset($_POST['new_name_'.$i_file]) AND trim($_POST['new_name_'.$i_file]) <> '') {
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
				/*	echo "prefix = “".$prefix."”<br />";
					echo "new_prefix = “".$new_prefix."”<br />";
					echo "extension = “".$extension."”<br />";
					echo "new_extension = “".$new_extension."”<br />"; */
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
		if($this_is_directory) echo "▶︎ ";
		if($delete_files) echo "<input type=\"checkbox\" name=\"delete_".$i_file."\"> ";
		if($type <> '' AND !$this_file_moved) {
		//	if($rename_files) echo "<input type=\"checkbox\" name=\"rename_".$i_file."\"> ";
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
				if($type == "grammar") echo "<font color=\"red\">";
				else if($type == "data") echo "<font color=\"gold\">";
				else if($type == "script") echo "<font color=\"blue\">";
				else if($type <> "settings") echo "<font color=\"lightgreen\">";
				echo $type."</font>";
				$time_saved = filemtime($dir.SLASH.$thisfile);
				echo "&nbsp;<small>➡&nbsp;".gmdate('Y-m-d H\hi',$time_saved)."</small>";
				}
			echo "<br />";
			if($show_dependencies) {
				$dependencies = find_dependencies($dir,$thisfile);
				if(count($dependencies) > 0) {
					for($i = 0; $i < count($dependencies); $i++)
						echo "&nbsp;&nbsp;▹&nbsp;<small>".$dependencies[$i]."</small><br />";
					}
				}
			}
		else {
			if($this_file_moved) echo "‘<font color=\"blue\">".$thisfile."</font>’<br />";
			else  echo $thisfile."<br />";
			}
		}
	}
if($delete_files)
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES/FOLDERS\"> <font color=\"red\">➡</font> can be reversed <input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
if($rename_files)
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"rename_checked_files\" value=\"RENAME OR COPY CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
if($move_files)
	echo "<p style=\"margin-left:6px;\"><font color=\"red\"><big>↑</big>&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
if($delete_files OR $delete_checked_files OR $rename_files OR $move_files) echo "</form>";

$os_platform = getOS();
/* if(PHP_OS <> "WINNT" AND !is_integer(strpos($os_platform,"Windows")) AND $path <> $csound_resources) {
	echo "<hr>";
	echo "<p style=\"text-align:center;\"><a href=\"".$bp_application_path."compile.php\">Recompile BP</a> (be careful!)</p>";
	} */

function folder_list($dir,$list,$path) {
	global $bp_application_path;
	$dircontent = scandir($dir);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		if(!is_dir($dir.$thisfile)) continue;
		if($dir == $bp_application_path) {
			if($thisfile <> "ctests" AND $thisfile <> "data") continue;
			}
		if($path == '') $list[] = $nextpath = $thisfile;
		else $list[] = $nextpath = $path.SLASH.$thisfile;
		$list = folder_list($dir.$thisfile.SLASH,$list,$nextpath);
		}
	return $list;
	}
?>
