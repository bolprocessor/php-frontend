<?php
require_once("_basic_tasks.php");
if($path <> '') $filename = $path;
else $filename = "Bol Processor";
if(isset($_POST['change_skin']) AND $_POST['change_skin'] >= 0) {
	$skin = $_POST['change_skin'];
	save_settings("skin",$skin);
	}
require_once("_header.php");
$url_this_page = $this_page = "index.php";

if($path <> '' AND $_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDirectory = $bp_application_path.$path;
    if(isset($_FILES['files'])) {
        foreach($_FILES['files']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['files']['name'][$key]);
                $destination = $targetDirectory.SLASH.$fileName;
				$type_of_file = type_of_file($fileName);
				if($type_of_file['type'] == '') {
					echo "File '$fileName' ignored (not for Bol Processor)<br>";
					unlink($tmpName);
					}
				else {
					if(move_uploaded_file($tmpName,$destination)) {
						chmod($destination,$permissions);
						echo "File '$fileName' imported successfully.<br>";
						}
					else echo "Failed to import file '$fileName'.<br>";
					}
				}
			}
		}
    // Process uploaded folders NOT IMPLEMENTED
    if(isset($_FILES['folders'])) {
        foreach($_FILES['folders']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['folders']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = basename($_FILES['folders']['name'][$key]);
                $destination = $targetDirectory.SLASH.$fileName;
                if(move_uploaded_file($tmpName,$destination))
                    echo "Folder '$fileName' imported successfully.<br>";
               	else
                    echo "Failed to import folder '$fileName'.<br>";
				}
			}
		}	
	}

$test = FALSE;
if($path <> '') {
	display_darklight();
	select_a_skin($url_this_page,$path,$skin);
	echo "<h2>Bol Processor ‘BP3’</h2>";
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
	}
else {
	display_console_state();
	echo "<table class=\"thinborder\"><tr>";
	echo "<td style=\"padding:1em; white-space:nowrap; text-align:center; vertical-align:middle; border-radius:1em;\">";
	echo "<h2><i>Welcome to</i></h2><h2>Bol Processor ‘BP3’</h2>";
	echo "<span class=\"night\"><img title=\"Asad Ali Khan (binkar)\" src=\"pict/Asad-Ali-binkar.png\" width=\"120px;\"/></span>";
	// echo "<span class=\"day\"><a style=\"border-bottom:none;\" target=\"blank\" title=\"Sunbeam Vectors by Vecteezy\" href=\"https://www.vecteezy.com/free-vector/sunbeam\"><img src=\"pict/sun.png\" width=\"100px;\"/></a></span>";
	echo "<span class=\"day\"><img src=\"pict/playing-piano.png\" width=\"120px;\"/></span>";
	echo "</td>";
	echo "<td class=\"big\" style=\"padding:1em; border-radius:1em; vertical-align:middle;\">";
	echo "<p style=\"text-align:center;\">This interface is running<br />the multi-platform console.</p><p style=\"text-align:center;\"><a target=\"_blank\" class=\"linkdotted\" href=\"https://bp3.tech\">https://bp3.tech</a></p>";
	echo "<p style=\"text-align:center;\">👉&nbsp;Read the <a class=\"linkdotted\" class=\"linkdotted\" href=\"https://raw.githubusercontent.com/bolprocessor/bolprocessor/graphics-for-BP3/BP3-changes.txt\" target=\"_blank\">history of changes</a></p>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	$dir = $bp_application_path;
	}

$folder = str_replace($bp_application_path,'',$dir);

echo link_to_help();
if($path == '') {
	select_a_skin($url_this_page,$path,$skin);
 	}


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
			echo "<p><span class=\"red-text\">This folder already exists:</span> <span class=\"red-text\">".$foldername."</span></p>";
			unset($_POST['create_folder']);
			}
		else {
			mkdir($dir.SLASH.$foldername);
			chmod($dir.SLASH.$foldername,0775);
			$new_file = $foldername;
			}
		}
	}

/*
if(isset($_POST['create_link'])) {
	unset($_POST['create_link']);
	$targetFolder = $_POST['folderPath'];
    $linkName = $_POST['linkName'];
    if(empty($targetFolder) || empty($linkName))
        echo "<p>Error: Please select a folder and enter a link name</p>";
	else {
//		$linkName = escapeshellarg($linkName);
		echo "linkName = ".$linkName."<br >";
		echo "targetFolder = ".$targetFolder."<br >";
		$workspace = getcwd();
		$linkPath = realpath($workspace.SLASH.$linkName);
		$targetPath = realpath($workspace.SLASH.$targetFolder);
		echo "linkPath = ".$linkPath."<br >";
		echo "targetPath = ".$targetPath."<br >";

		// Check if the target folder exists
		if (!$targetPath || !is_dir($targetPath)) {
    		echo "<p>Error: Target folder '$targetFolder' does not exist or is invalid</p>";
			}
		if (is_link($linkPath) || file_exists($linkPath)) {
			unlink($linkPath);
			}

		// Detect OS
		if (windows_system()) {
			// Windows (Requires Admin Privileges)
			$command = "mklink /D $linkPath $targetPath";
			$cmd = "cmd.exe /c \"$command\"";
			}
		else {
			// macOS / Linux
			$command = "ln -s $targetPath $linkPath";
			$cmd = "/bin/bash -c \"$command\"";
			}
		$output = shell_exec($cmd);
		// Check success
		if(is_link($linkPath) || file_exists($linkPath))
			echo "<p>Symlink created successfully: $linkPath -> $target</p>";
		else echo "<p>Error creating symlink. Ensure you have the necessary permissions</p>";
		}
	} */

if(isset($_POST['create_grammar'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if($test) echo "newfile = ".$new_file."<br />";
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_grammar']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "grammar_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_data'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_data']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "data_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_alphabet'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_alphabet']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "alphabet_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_timebase'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_timebase']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "timebase_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_prototypes'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_prototypes']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "prototypes_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
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
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_csound']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "csound_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_tonality'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_tonality']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "tonality_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
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
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_script'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_script']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}

$delete_files = isset($_POST['delete_files']);
$rename_files = isset($_POST['rename_files']);
$move_files = isset($_POST['move_files']);
$download_files = isset($_POST['download_files']);

$show_dependencies = isset($_POST['show_dependencies']);
$trash_backups = isset($_POST['trash_backups']);
if($folder <> '')
	echo "<h3>Content of workspace: <span class=\"red-text\">".str_replace(SLASH,'/',$folder)."</span></h3>";
$table = explode('_',$folder);
$extension = end($table);

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\">";
if($path <> '') echo "<input class=\"edit\" type=\"submit\" name=\"\" value=\"REFRESH THIS PAGE\">";
if($folder <> '') echo "&nbsp;&nbsp;<big><span class=\"red-text\">↑</span>&nbsp;<a href=\"".nice_url($upper_link)."\">UPPER FOLDER</a>&nbsp;<span class=\"red-text\">↑</span></big>";
echo "</p>";

$link_list = "file_list.php?dir=".$dir;
if($path <> '' AND $path <> $trash_folder) {
	echo "<p><input class=\"edit\" onclick=\"window.open('".nice_url($link_list)."','listfiles','width=300,height=600,left=100'); return false;\" type=\"submit\" name=\"\" value=\"COPY list of files\">&nbsp;";
	if(countBakFiles($dir) > 0) echo "<input class=\"trash\" type=\"submit\" name=\"trash_backups\" title=\"Delete '_bak' files\" value=\"MOVE '_bak' files to TRASH\"></p>";
	if($download_files) echo "<input class=\"cancel\" type=\"submit\" name=\"\" value=\"CANCEL DOWNLOAD\">";
	}
echo "</form>";

echo "<script>\n";
echo "window.onload = function() {
    settogglecreate();
    settoggleimport(); 
	};\n";
echo "</script>\n";

if($path == $trash_folder AND isset($_POST['empty_trash'])) {
	if(emptydirectory($bp_application_path.$trash_folder)) echo "<p id=\"refresh\"><span class=\"red-text\">Trash is empty!</span></p>";
	}

if($dir <> $bp_application_path."php" AND $path <> $trash_folder AND $extension <> "temp" AND !$delete_files AND !$rename_files AND !$move_files AND !$download_files) {
	echo "<div style=\"float:right; padding:6px; background-color:transparent;\">";
	if($path <> '') check_csound();
	if(!is_integer(strpos($path,$tonality_resources)) AND $path <> '') link_to_tonality();
	if($path == $tonality_resources) {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_tonality\" value=\"CREATE NEW TONALITY FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "to";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		}
	if($path <> '') {
		echo "<button class=\"save big\" onclick=\"toggleimport(); return false;\">IMPORT FILES</button><br />";
		echo "<div id=\"import\" style=\"padding:6px; background-color:transparent;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"file\" name=\"files[]\" id=\"files\" multiple>";
    //    echo "<input type=\"file\" name=\"folders[]\" id=\"folders\" webkitdirectory directory>";
		echo "<input class=\"save\" type=\"submit\" value=\"<-- IMPORT\"><br />";
		echo "</form>";
		echo "</div>";
		}
	if($path <> $csound_resources AND $path <> $tonality_resources) {
		if($path <> '') echo "<br /><button class=\"save big\" onclick=\"togglecreate(); return false;\">CREATE FILES OR FOLDERS</button>";
		else echo "<br /><button class=\"save big\" onclick=\"togglecreate(); return false;\">CREATE FOLDERS</button>";
		echo "<div id=\"create\" style=\"background-color:transparent;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_folder\" value=\"CREATE NEW FOLDER IN THIS WORKSPACE\"><br />named:&nbsp;";
		echo "<input type=\"text\" name=\"foldername\" size=\"15\" style=\"background-color:CornSilk;\" value=\"\">";
		echo "</p>";
	/*	echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" onclick=\"selectFolder()\" type=\"submit\" name=\"create_link\" value=\"CREATE LINK TO FOLDER OUTSIDE THIS WORKSPACE\"><br />";
		echo "<label for=\"folderPath\">Selected folder: </label>";
        echo "<input type=\"text\" id=\"folderPath\" name=\"folderPath\" readonly required placeholder=\"Selected folder path will appear here\">";
	//	echo "<input type=\"file\" id=\"folderInput\" webkitdirectory directory onchange=\"getFolderPath()\">";
        echo "<br /><label for=\"linkName\">Symbolic link name: </label>";
        echo "<input type=\"text\" name=\"linkName\" size=\"15\" required>";
		echo "</p>"; */
		echo "</form>";
		if($path <> '') {
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_grammar\" value=\"CREATE NEW GRAMMAR FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "gr";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_data\" value=\"CREATE NEW DATA FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "da";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_alphabet\" value=\"CREATE NEW ALPHABET FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "ho";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_prototypes\" value=\"CREATE NEW SOUND-OBJECT PROTOTYPE FILE\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "so";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_script\" value=\"CREATE NEW SCRIPT\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "sc";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_timebase\" value=\"CREATE NEW TIMEBASE\"><br />named:&nbsp;";
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
	else if($path <> $tonality_resources) {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_csound\" value=\"CREATE NEW CSOUND RESOURCE FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "cs";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_csound_orchestra\" value=\"CREATE NEW CSOUND ORCHESTRA FILE\"><br />named:&nbsp";
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
	echo "<p id=\"refresh\"><span class=\"red-text\">Renamed or copied checked files</span></p>";
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
		echo "<p id=\"refresh\"><span class=\"red-text\">➡ Moving selected files/folders</span> to ‘<span class=\"green-text\">".$dest_folder."</span>’</p>";
		}
	else {
		echo "<p><span class=\"red-text\">➡ No destination folder was selected, move canceled</span></p>";
		$move_checked_files = FALSE;
		}
	$move_files = FALSE;
	}

if($move_files) {
	$list_folders = array();
	$list_folders = folder_list($bp_application_path,$list_folders,'');
	$imax = count($list_folders);
	if($imax > 0) {
		echo "<div style=\"float:right; padding:6px; border-radius:12px;\">";
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
		echo "<p style=\"margin-left:6px;\"><input class=\"save\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input class=\"cancel\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
		echo "</div>";
		}
	else {
		echo "<h4><span class=\"red-text\">ERROR: no folder found!</span></h4>";
		$move_files = FALSE;
		}
	}

if(!$delete_files AND !$rename_files AND !$move_files AND !$download_files AND $path <> $trash_folder) {
	if($path <> '') echo "<input class=\"save\" style=\"margin-top:1em;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE\">";
	else  echo "<input class=\"save\" style=\"margin-top:1em;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE FOLDERS\"><br /><br />";
	}
if($path <> '') {
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files) {
		echo "&nbsp;<input class=\"save\"style=\"margin-top:1em;\" title=\"Rename folders or files\" type=\"submit\" name=\"rename_files\" value=\"RENAME OR COPY\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files) {
		echo "&nbsp;<input class=\"save\" style=\"margin-top:1em;\" title=\"Move folders or files\" type=\"submit\" name=\"move_files\" value=\"MOVE\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files) {
		echo "&nbsp;<input class=\"save\" style=\"margin-top:1em;\" title=\"Download files\" type=\"submit\" name=\"download_files\" value=\"DOWNLOAD FILES\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files AND $path == $trash_folder) {
		echo "<br /><br />🗑&nbsp;<input style=\"background-color:red; color:white;\" title=\"Empty trash\" type=\"submit\" name=\"empty_trash\" value=\"EMPTY THIS TRASH\"> 👉 can't be reversed!";
		}
	if(!$show_dependencies AND !$delete_files AND !$move_files AND !$download_files AND $path <> $trash_folder AND !is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources"))) echo "<br /><input class=\"edit\" title=\"Show dependencies of files (links to other files)\" type=\"submit\" name=\"show_dependencies\" value=\"SHOW DEPENDENCIES\"> (links between files)";
	else if($show_dependencies) echo "<br /><br /><input class=\"cancel\" type=\"submit\" value=\"HIDE DEPENDENCIES\">";
	echo "<br /><br />";
	}

display_directory(FALSE,$dir,"directory");
if($path <> $trash_folder) echo "▶︎&nbsp;🗑&nbsp;<a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">TRASH</a><br />";
echo "<br />";

echo "<table class=\"thinborder\">";
$show_grammar = isset($last_grammar_page) AND isset($last_grammar_name) AND file_exists("..".SLASH.$last_grammar_directory.SLASH.$last_grammar_name);
$show_data = isset($last_data_page) AND isset($last_data_name) AND $last_data_name <> '' AND file_exists("..".SLASH.$last_data_directory.SLASH.$last_data_name);
if(is_integer(strpos($path,"scale_images"))) {
	echo "<tr>";
	echo "<td>";
	display_directory(TRUE,$dir,"images");
	echo "</td>";
	echo "</tr>";
	}

if(!is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources"))) {
	if($path <> '' OR $show_grammar OR $show_data) {
		echo "<tr>";
		echo "<th>";
		if(($n1 = display_directory(TRUE,$dir,"grammar")) > 0 OR $show_grammar) {
			echo "<h4>Grammar project(s)</h4>";
			if($show_grammar) {
				echo "<p style=\"background-color:snow; color:black; padding:6px; border-radius: 0.5em;\">Last visited: <a class=\"darkblue-text\" target=\"_blank\" href=\"".nice_url($last_grammar_page)."\">".$last_grammar_name."</a>";
				if(isset($last_grammar_directory) AND $folder <> $last_grammar_directory) echo "<br />in workspace</font> <a class=\"darkblue-text\" href=\"index.php?path=".$last_grammar_directory."\">".$last_grammar_directory."</a></p>";
				}
			}
		echo "</th>";
		echo "<th>";
		if(($n2 = display_directory(TRUE,$dir,"data")) > 0 OR $show_data) {
			echo "<h4>Data project(s)</h4>";
			if($show_data) {
				echo "<p style=\"background-color:snow; color:black; padding:6px; border-radius: 0.5em;\">Last visited: <a class=\"darkblue-text\" target=\"_blank\" href=\"".nice_url($last_data_page)."\">".$last_data_name."</a>";
				if(isset($last_data_directory) AND $folder <> $last_data_directory) echo "<br />in workspace</font> <a class=\"darkblue-text\" href=\"index.php?path=".$last_data_directory."\">".$last_data_directory."</a></p>";
				}
			}
		echo "</th>";
		if($path <> '') {
			echo "<th>";
			$n3 = display_directory(TRUE,$dir,"script");
			echo "<h4>Script project(s)</h4>";
			echo "</th>";
			}
		echo"</tr>";
		}
	if($path <> '') {
		echo "<tr>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n1 > 0) display_directory(FALSE,$dir,"grammar");
		echo "</td>";
		echo "<td>";
		if($n2 > 0) display_directory(FALSE,$dir,"data");
		echo "</td>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n3 > 0) display_directory(FALSE,$dir,"script");
		echo "</td>";
		echo"</tr>";
		echo "<tr>";
		echo "<th>";
		$n1 = display_directory(TRUE,$dir,"timebase");
		echo "<h4>Time base(s)</h4>";
		echo "</th>";
		echo "<th>";
		$n2 = display_directory(TRUE,$dir,"objects");
		echo "<h4>Sound objects</h4>";
		echo "</th>";
		echo "<th>";
		$n3 = display_directory(TRUE,$dir,"glossary");
		echo "<h4>Glossaries</h4>";
		echo "</th>";
		echo"</tr>";
		echo "<tr>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n1 > 0) display_directory(FALSE,$dir,"timebase");
		echo "</td>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n2 > 0) display_directory(FALSE,$dir,"objects");
		echo "</td>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n3 > 0) display_directory(FALSE,$dir,"glossary");
		echo "</td>";
		echo"</tr>";
		echo "<tr>";
		echo "<th>";
		$n1 = display_directory(TRUE,$dir,"settings");
		echo "<h4>Settings</h4>";
		echo "</th>";
		echo "<th>";
		$n2 = display_directory(TRUE,$dir,"alphabet");
		echo "<h4>Alphabet(s)</h4>";
		echo "</th>";
		echo "<th>";
		echo "<h4>More</h4>";
		echo "</th>";
		echo "</tr>";
		}
	}
if($path <> '') {
	$n3 = display_directory(TRUE,$dir,'');
	echo "<tr>";
	if(!is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources"))) {
		echo "<td style=\"padding-bottom:6px;\">";
		if($n1 > 0) display_directory(FALSE,$dir,"settings");
		echo "</td>";
		echo "<td style=\"padding-bottom:6px;\">";
		if($n2 > 0) display_directory(FALSE,$dir,"alphabet");
		echo "</td>";
		}
	echo "<td style=\"padding-bottom:6px;\">";
	if($n3 > 0) display_directory(FALSE,$dir,'');
	echo "</td>";
	echo"</tr>";
	}
echo "</table>";
echo "</form>";

function display_directory($test,$dir,$filter) {
	global $path,$move_files,$move_checked_files,$new_file,$csound_resources,$tonality_resources,$delete_checked_files,$rename_checked_files,$delete_files,$download_files,$rename_files,$show_dependencies,$trash_backups,$trash_folder,$this_page,$url_this_page,$dir_trash_folder,$bp_application_path,$dest_folder,$done,$seen,$dir_csound_resources,$dir_tonality_resources,$last_grammar_name,$last_data_name;

	$dircontent = scandir($dir);
	$i_file = $files_shown = 0;
	foreach($dircontent as $thisfile) { 
		$i_file++;
		if($thisfile[0] == '.' OR $thisfile[0] == '_') continue;
		if(is_integer($pos=strpos($thisfile,"BP2")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($thisfile,"License")) AND $pos == 0) continue;
		if(is_integer($pos=stripos($thisfile,"ReadMe")) AND $pos == 0) continue;
		if(is_integer($pos=stripos($thisfile,"linux-scripts")) AND $pos == 0) continue; 
		if($thisfile == "resources" OR $thisfile == "source" OR $thisfile == "temp_bolprocessor" OR $thisfile == "Makefile" OR $thisfile == "my_output" OR $thisfile == "php" OR $thisfile == "www" OR $thisfile == "License.txt" OR $thisfile == "BP3-To-Do.txt" OR $thisfile == "Credits.txt" OR $thisfile == "bp_compile_result.txt" OR $thisfile == "test.php") continue;
		if($test) {
			if(($new_name = new_name($thisfile,"file")) <> $thisfile) rename($dir.SLASH.$thisfile,$dir.SLASH.$new_name);
			}
		if($move_files) $check_box = "<input type=\"checkbox\" name=\"move_".$i_file."\"> ";
		else $check_box = '';
		$this_file_moved = FALSE;
		if($trash_backups AND is_integer($pos=strpos($thisfile,"_bak"))) {
			$source_file = $bp_application_path.$path.SLASH.$thisfile;
			rename($source_file,$dir_trash_folder.$thisfile);
			delete_settings($thisfile);
			continue;
			}
		if(!$test AND $move_checked_files AND isset($_POST['move_'.$i_file])) {
			unset($_POST['move_'.$i_file]);
			$source_file = $bp_application_path.$path.SLASH.$thisfile;
			$destination_file = $bp_application_path.$dest_folder.SLASH.$thisfile;
			if(is_integer($pos=strpos($dest_folder,$thisfile)) AND $pos == 0) {
				echo "<p><span class=\"red-text\">➡ Cannot move a folder to its own content</span></p>";
				}
			else if(file_exists($destination_file)) {
				if($dest_folder <> '') $the_dest = "‘<span class=\"green-text\">".$dest_folder."</span>’";
				else $the_dest = "the root folder";
				echo "<p><span class=\"red-text\">➡ There is already</span> a file or folder named ‘<span class=\"green-text\">".$thisfile."</span>’ in ".$the_dest."</p>";
				}
			else {
				if(is_dir($dir.SLASH.$thisfile)) {
					if(is_integer($pos=strpos($destination_file,$source_file)))
						echo "<p><span class=\"red-text\">➡ Cannot move</span> folder ‘<span class=\"green-text\">".$thisfile."</span>’ into itself!</p>";
					else {
						rename($source_file,$destination_file);
						$this_file_moved = TRUE;
						}
					}
				else {
					rename($source_file,$destination_file);
					$this_file_moved = TRUE;
					if(isset($last_grammar_name) AND $last_grammar_name == $thisfile) {
						delete_settings_entry("last_grammar_page");
						delete_settings_entry("last_grammar_name");
						}
					if(isset($last_data_name) AND $last_data_name == $thisfile) {
						delete_settings_entry("last_data_name");
						delete_settings_entry("last_data_page");
						}
					}
				}
			}
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
		if(!$test AND $new_file == $thisfile AND $filter <> "directory") echo "<span class=\"red-text\">➡</span> ";
		if(!$test) echo $check_box;

		if(!$test AND $path <> $csound_resources AND $path <> $trash_folder AND ($type == "csound" OR $type == "csorchestra")) {
			echo "Moved ‘<span class=\"green-text\">".$dir.SLASH.$thisfile."</span>’ to ‘<span class=\"green-text\">".$dir_csound_resources.$thisfile."</span>’<br />";
			if(file_exists($dir_csound_resources.$thisfile)) @unlink($dir.SLASH.$thisfile);
			else rename($dir.SLASH.$thisfile,$dir_csound_resources.$thisfile);
			}
		else {
			if(!$test AND $path <> $tonality_resources AND $path <> $trash_folder AND $type == "tonality") {
				echo "Moved ‘<span class=\"green-text\">".$dir.SLASH.$thisfile."</span>’ to ‘<span class=\"green-text\">".$dir_tonality_resources.$thisfile."</span>’<br />";
				if(file_exists($dir_tonality_resources.$thisfile)) @unlink($dir.SLASH.$thisfile);
				else rename($dir.SLASH.$thisfile,$dir_tonality_resources.$thisfile);
				}
			else {
				$renamed = FALSE;
				if(!$test AND $delete_checked_files AND isset($_POST['delete_'.$i_file])) {
					echo "<p><span class=\"red-text\">➡</span> Deleted ‘<span class=\"green-text\">".$thisfile."</span>’ (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
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
							echo "<span class=\"red-text\">➡</span> Can't rename to existing ‘".$new_name."’: ";
							}
						else {
							if($make_copy) {
								if($type <> "directory") copy($dir.SLASH.$old_name,$dir.SLASH.$new_name);
								else rcopy($dir.SLASH.$old_name,$dir.SLASH.$new_name);
								$link = $type.".php?file=".urlencode($path.SLASH.$new_name);
								echo "‘<span class=\"green-text\">".$old_name."</span>’ <span class=\"red-text\">➡</span> copied to <a target=\"_blank\" href=\"".nice_url($link)."\">".$new_name."</a><br />";
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
				else if($thisfile == "bp" OR $thisfile == "bp.exe" OR $thisfile == "bp3") continue;

		//		if(!$test) echo $check_box;
				
				if(!$test AND $delete_files AND $type <> '' AND !do_not_delete($thisfile)) echo "<input type=\"checkbox\" name=\"delete_".$i_file."\"> ";
				$link = $dir.SLASH.$thisfile;
				if(!$test AND $download_files AND $type <> '' AND !$this_is_directory) echo "<a href=\"".$link."\" title=\"Download this file\" download=\"".$thisfile."\">⬇️</a> ";
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
					if($type == "image") $link = $dir.SLASH.$thisfile;
					if($link <> '') {
						if($this_is_directory) echo "<a href=\"".nice_url($link)."\">";
						else echo "<a target=\"_blank\" href=\"".nice_url($link)."\">";
						}
					if($this_is_directory) echo "<b>";
					echo $thisfile;
					if($link <> '') echo "</a>";
					if($this_is_directory) echo "</b>";
					echo "&nbsp;";
					if($renamed) echo "(<span class=\"red-text\">renamed</span>)&nbsp;";
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
		}
	if($files_shown == 0) return $files_shown;
	if(!$test AND $move_files)
		echo "<p style=\"margin-left:6px;\"><span class=\"red-text\"><big>↑</big></span>&nbsp;<input class=\"save\" type=\"submit\" name=\"move_checked_files\" value=\"MOVE CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input class=\"cancel\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	if(!$test AND $delete_files)
		echo "<p style=\"margin-left:6px;\"><span class=\"red-text\"><big>↑</big></span>&nbsp;<input class=\"save\" type=\"submit\" name=\"delete_checked_files\" value=\"DELETE CHECKED FILES/FOLDERS\"><br /><span class=\"red-text\">➡</span> can be reversed <input class=\"cancel\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
	if(!$test AND $rename_files)
		echo "<p style=\"margin-left:6px;\"><span class=\"red-text\"><big>↑</big></span>&nbsp;<input class=\"save\" type=\"submit\" name=\"rename_checked_files\" value=\"RENAME OR COPY CHECKED FILES/FOLDERS\">&nbsp;&nbsp;<input class=\"cancel\" type=\"submit\" name=\"cancel\" value=\"CANCEL\"></p>";
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
		//	echo $thisfile." ?<br />";
			if(!ok_output_location($thisfile,FALSE)) continue;
			$new_path = $thisfile;
			}
		$list[] = $new_path;
		$list = folder_list($dir.$thisfile.SLASH,$list,$new_path);
		}
	return $list;
	}

function do_not_delete($thisfile) {
	if($thisfile == "scale_images") return TRUE;
	if($thisfile == "ctests") return TRUE;
	if($thisfile == "macos-scripts") return TRUE;
	if($thisfile == "windows-scripts") return TRUE;
	return FALSE;
	}

function countBakFiles($directory = '.') {
    $count = 0;
    if (is_dir($directory)) {
        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                if (is_file($directory . '/' . $file) && preg_match('/_bak$/', $file)) {
                    $count++;
					}
				}
            closedir($handle);
			}
		}
    return $count;
	}
?>
