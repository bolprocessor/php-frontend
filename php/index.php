<?php
require_once("_basic_tasks.php");
// 2026-11-19 "csound_resources_private" is listed together with "csound_resources", and "tonality_resources_private" with "tonality_resources" - by Claude
if($path == $csound_resources_private) $path = $csound_resources;
if($path == $tonality_resources_private) $path = $tonality_resources;
if($path == $supercollider_resources_private) $path = $supercollider_resources; // 2026-11-20
$csound_folder = ($path == $csound_resources);
$tonality_folder = ($path == $tonality_resources);
$supercollider_folder = ($path == $supercollider_resources);
$resource_kind = $csound_folder ? "csound" : ($tonality_folder ? "tonality" : ($supercollider_folder ? "supercollider" : ''));
$resource_folder = ($resource_kind <> '');
if($path <> '') $filename = $path;
else $filename = "Bol Processor";
if(isset($_POST['change_skin']) AND $_POST['change_skin'] >= 0) {
	$skin = $_POST['change_skin'];
	save_settings("skin",$skin);
	}
if($path == '' AND isset($_POST['change_midi_player']) AND in_array($_POST['change_midi_player'],$midi_players)) {
	$midi_player = $_POST['change_midi_player'];
	save_settings("midi_player",$midi_player);
	}
require_once("_header.php");
$url_this_page = $this_page = "index.php";
$error_opening = FALSE;

if($path <> '' AND $_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetDirectory = $bp_application_path.$path;
	if($resource_folder) $targetDirectory = rtrim(resource_dirs($resource_kind)['my'],"/\\"); // 2026-11-19 Imported resources belong to the user
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
				// 2026-11-19 Only resources of the right kind are imported to a resource folder, and they may not replace or hide a file with the same name in either folder - by Claude
				else if($resource_folder AND resource_kind_of_type($type_of_file['type']) <> $resource_kind) {
					echo "<span class=\"red-text\">➡</span> File '".htmlspecialchars($fileName)."' ignored (not a ".($resource_kind == "csound" ? "Csound" : ($resource_kind == "supercollider" ? "SuperCollider" : "tonality"))." resource)<br>";
					unlink($tmpName);
					}
				else if($resource_folder AND file_exists(resource_path($resource_kind,$fileName))) {
					echo "<span class=\"red-text\">➡</span> File '".htmlspecialchars($fileName)."' not imported: there is already a file with this name. Rename the file before importing it.<br>";
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
	echo "<p style=\"text-align:center;\"><a target=\"_blank\" class=\"linkdotted\" href=\"credits.php\">Credits</a> — <a target=\"_blank\" class=\"linkdotted\" href=\"credits.php?file=csound\">Csound resources</a></p>"; // 2026-11-20
	echo "<span class=\"night\"><img title=\"Asad Ali Khan (binkar)\" src=\"pict/Asad-Ali-binkar.png\" width=\"120px;\"/></span>";
	echo "<span class=\"day\"><img src=\"pict/playing-piano.png\" width=\"120px;\"/></span>";
	echo "</td>";
	echo "<td class=\"big\" style=\"padding:1em; border-radius:1em; vertical-align:middle;\">";
	if(isset($_SERVER["PHPDESKTOP_VERSION"])) {
		echo "<p style=\"text-align:center;\">Installation in <a target=\"_blank\" href=\"https://github.com/cztomczak/phpdesktop\">PHP Desktop</a> v".$_SERVER["PHPDESKTOP_VERSION"]."</p>";
		}
	echo "<p style=\"text-align:center;\">This interface is running<br />the Bol Processor console</p><p style=\"text-align:center;\"><a target=\"_blank\" class=\"linkdotted\" href=\"https://bp3.tech\">https://bp3.tech</a></p>";
	echo "<p style=\"text-align:center;\">👉&nbsp;Read the <a class=\"linkdotted\" class=\"linkdotted\" href=\"https://raw.githubusercontent.com/bolprocessor/bolprocessor/graphics-for-BP3/BP3-changes.txt\" target=\"_blank\">history of changes</a></p>";
	echo "<small><p style=\"text-align:center;\">Architecture: ".$architecture;
	if($architecture == "i386" OR $architecture == "armv7l")
		echo "<br />⚠️&nbsp;<span class=\"red-text\">not suitable for this installation!</span></p>";
	else {
		echo "<br />for <a target=\"_blank\" href=\"phpinfo.php\">PHP ".phpversion()."</a>";
		if(MB_CONVERT_OK) echo "<br />with UTF multibyte conversion";
			else echo "<br />⚠️&nbsp;<span class=\"red-text\">without UTF multibyte conversion!</span>";
		if(!MULTIBYTE_EREG_OK) echo "<br />⚠️&nbsp;<span class=\"red-text\">without UTF multibyte replacing!</span>";
		if(!GD_AVAILABLE) echo "<br />⚠️&nbsp;<span class=\"red-text\">without GD graphics!</span>";
		echo "</p>";
		}
	echo "</small></td>";
	echo "</tr>";
	echo "</table>";
	$dir = $bp_application_path;
	}

$folder = str_replace($bp_application_path,'',$dir);

echo link_to_help();
if($path == '') {
	select_a_skin($url_this_page,$path,$skin);
	select_a_midi_player($url_this_page,$midi_player);
 	}

if($test) echo "dir = ".$dir."<br />";
if($test) echo "url_this_page = ".$url_this_page."<br />";

$new_file = '';
if(isset($_POST['create_folder'])) {
	$foldername = trim($_POST['foldername']);
	$foldername = fix_new_name($foldername,TRUE);
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
			fwrite($handle,"*\n\n"); // 2026-11-12 Content of the former "alphabet_template"
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
if(isset($_POST['create_keyboard'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_keyboard']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
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
			$file_header = $top_header."\n// Object prototypes file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
			fwrite($handle,so_data_to_json($file_header,so_new_data())); // 2026-11-12 JSON format with default values
			fclose($handle);
			chmod($dir.SLASH.$filename,$permissions);
			}
		}
	}
// 2026-11-19 Duplicate button of the Csound and tonality resource lists - by Claude
if($resource_folder AND isset($_POST['duplicate_resource']) AND isset($_POST['duplicate_name'])) {
	echo duplicate_resource($resource_kind,basename(trim($_POST['duplicate_resource'])),trim($_POST['duplicate_name']));
	}
// 2026-11-19 'delete' button of the Csound and tonality resource lists: the file goes to the trash, from which MOVE brings it back to the user folder - by Claude
if($resource_folder AND isset($_POST['trash_resource'])) {
	$thisfile = basename(trim($_POST['trash_resource']));
	$thistype = type_of_file($thisfile)['type'];
	$source = resource_path($resource_kind,$thisfile);
	if($thisfile == '' OR $thisfile[0] == '.' OR !is_file($source) OR resource_kind_of_type($thistype) <> $resource_kind)
		echo "<p><span class=\"red-text\">➡ Can't delete</span> ‘".htmlspecialchars($thisfile)."’</p>";
	else if(read_only_resource_folder(resource_dir($resource_kind,$thisfile)))
		echo "<p><span class=\"red-text\">➡ Can't delete fixed file</span> ‘<span class=\"green-text\">".$thisfile."</span>’</p>";
	else {
		rename($source,$dir_trash_folder.$thisfile);
		delete_settings($thisfile);
		echo "<p><span class=\"red-text\">➡</span> Deleted ‘<span class=\"green-text\">".$thisfile."</span>’ (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
		}
	}
// 2026-11-20 'delete' button of the files and folders of "csound_resources_private" that are not resources (samples...), see csound_private_dependencies().
// They are deleted at once, not moved to the trash, since the purpose is to free space - by Claude
if($resource_kind == "csound" AND isset($_POST['delete_dependency'])) {
	$dependency_name = basename(trim($_POST['delete_dependency']));
	$dependency_items = csound_private_dependencies();
	if($dependency_name == '' OR !isset($dependency_items[$dependency_name]))
		echo "<p><span class=\"red-text\">➡ Can't delete</span> ‘".htmlspecialchars($dependency_name)."’</p>";
	else {
		$dependency_path = $dir_csound_resources_private.$dependency_name;
		if($dependency_items[$dependency_name]['folder']) my_rmdir($dependency_path);
		else @unlink($dependency_path);
		if(file_exists($dependency_path)) echo "<p><span class=\"red-text\">➡ ‘".$dependency_name."’ could not be deleted</span></p>";
		else echo "<p><span class=\"red-text\">➡</span> Deleted ‘<span class=\"green-text\">".$dependency_name."</span>’ from ‘".$csound_resources_private."’ (".($dependency_items[$dependency_name]['size'] >= 1048576 ? round($dependency_items[$dependency_name]['size'] / 1048576)." MB" : ceil($dependency_items[$dependency_name]['size'] / 1024)." kB")." freed)</p>";
		}
	}
if(isset($_POST['create_csound'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		$new_file = $filename;
		// 2026-11-19 New Csound resources are created in "csound_resources_private", or in "csound_resources" by the designer if requested. The name may not be that of a file in either folder - by Claude
		$create_dir = new_csound_resource_folder();
		if(file_exists($dir_csound_resources.$filename) OR file_exists($dir_csound_resources_private.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_csound']);
			}
		else {
			$handle = fopen($create_dir.$filename,"w");
			$file_header = $top_header."\n// Csound resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
			fwrite($handle,csound_data_to_json($file_header,cs_new_data())); // 2026-11-12 JSON format with default values
			fclose($handle);
			chmod($create_dir.$filename,$permissions);
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
		// 2026-11-19 New tonality resources are created in "tonality_resources_private", or in "tonality_resources" by the designer if requested. The name may not be that of a file in either folder - by Claude
		$create_dir = new_resource_folder("tonality");
		if(file_exists($dir_tonality_resources.$filename) OR file_exists($dir_tonality_resources_private.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			unset($_POST['create_tonality']);
			}
		else {
			$handle = fopen($create_dir.$filename,"w");
			$template = "tonality_template";
			$template_content = @file_get_contents($template);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			chmod($create_dir.$filename,$permissions);
			}
		}
	}
// 2026-11-20 New SuperCollider resources ('-sc' files) and SynthDef files ('*.scd'), in "supercollider_resources_private" (or "supercollider_resources" by the designer) - by Claude
if(isset($_POST['create_supercollider']) OR isset($_POST['create_scsynthdef'])) {
	$filename = trim(fix_new_name(trim($_POST['filename']),TRUE));
	$filename = preg_replace(array('/^-sc\./','/\.scd$/'),'',$filename);
	if($filename <> '' AND $filename[0] <> '.') {
		$scd_name = $filename.".scd";
		$filename = isset($_POST['create_supercollider']) ? "-sc.".$filename : $scd_name;
		$new_file = $filename;
		$create_dir = new_resource_folder("supercollider");
		if(file_exists($dir_supercollider_resources.$filename) OR file_exists($dir_supercollider_resources_private.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			}
		else {
			if(isset($_POST['create_supercollider'])) {
				// Format of a '-cs' file, naming the file of SynthDefs with the same name
				$data = cs_new_data();
				$data["orchestra"] = $scd_name;
				$data["tables"] = array(); // Csound tables are not used
				$content = csound_data_to_json($top_header."\n// SuperCollider resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT",$data);
				}
			else $content = "// SynthDefs for the Bol Processor (real-time SuperCollider). Each one plays the instrument of the same name in '-sc' files.\n// See supercollider_resources/0-default.scd for the arguments sent by the Bol Processor.\n\n(\n)\n";
			file_put_contents($create_dir.$filename,$content);
			chmod($create_dir.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_csound_orchestra'])) {
	$filename = trim($_POST['filename']);
	$filename = str_replace(".orc",'',$filename);
	if($filename <> '') {
		$filename = $filename.".orc";
		$new_file = $filename;
		$create_dir = new_csound_resource_folder(); // 2026-11-19 See create_csound
		if(file_exists($dir_csound_resources.$filename) OR file_exists($dir_csound_resources_private.$filename)) {
			echo "<p><span class=\"red-text\">This file already exists:</span> <span class=\"red-text\">".$filename."</span></p>";
			}
		else {
			$handle = fopen($create_dir.$filename,"w");
			fclose($handle);
			chmod($create_dir.$filename,$permissions);
			}
		}
	}
if(isset($_POST['create_script'])) {
	$type = $_POST['type'];
	$name_mode = $_POST['name_mode'];
	$filename = trim($_POST['filename']);
	if($filename <> '') {
		$filename = good_name($type,$filename,$name_mode);
		if(str_starts_with($filename,"-sc.")) $filename = "+sc.".substr($filename,4); // 2026-11-20 '-sc' is the prefix of SuperCollider resources
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

// if(MB_CONVERT_OK) echo "<p>MB_CONVERT_OK</p>";

if($folder <> '')
	echo "<h3>Content of workspace: <span class=\"red-text\"><input title=\"List this workspace\" class=\"edit big\" name=\"workspace\" type=\"submit\" value=\"".$folder."\"></h3>";
$table = explode('_',$folder);
$extension = end($table);

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\">";
if($path <> '') echo "<input class=\"edit\" type=\"submit\" name=\"\" value=\"REFRESH THIS PAGE\">";
if(!$resource_folder) { // 2026-11-19 No UPPER FOLDER in the lists of Csound and tonality resources
	if($folder <> '') echo "&nbsp;&nbsp;<big><span class=\"red-text\">↑</span>&nbsp;<a href=\"".nice_url($upper_link)."\">UPPER FOLDER</a>&nbsp;<span class=\"red-text\">↑</span></big>";
	}
echo "</p>";

$link_list = "file_list.php?dir=".$dir;
if($path <> '' AND $path <> $trash_folder) {
	echo "<p><input class=\"edit\" onclick=\"window.open('".nice_url($link_list)."','listfiles','width=300,height=600,left=100'); return false;\" type=\"submit\" name=\"\" value=\"COPY the list of files\">&nbsp;";
	if(countBakFiles($dir) > 0 OR ($resource_folder AND countBakFiles(rtrim(resource_dirs($resource_kind)['my'],"/\\")) > 0)) echo "<input class=\"trash\" type=\"submit\" name=\"trash_backups\" title=\"Delete '_bak' files\" value=\"MOVE '_bak' files to TRASH\"></p>";
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
	if($path <> '') check_supercollider(); // 2026-11-21
	watchdog_notice(); // 2026-11-22
	if(!is_integer(strpos($path,$tonality_resources)) AND $path <> '') link_to_tonality();
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
	if($path <> $csound_resources AND $path <> $tonality_resources AND $path <> $supercollider_resources) {
		if($path <> '') echo "<br /><button class=\"save big\" onclick=\"togglecreate(); return false;\">CREATE FILES OR FOLDERS</button>";
		else echo "<br /><button class=\"save big\" onclick=\"togglecreate(); return false;\">CREATE FOLDERS</button>";
		echo "<div id=\"create\" style=\"background-color:transparent;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_folder\" value=\"CREATE NEW FOLDER IN THIS WORKSPACE\"><br />named:&nbsp;";
		echo "<input type=\"text\" name=\"foldername\" size=\"15\" style=\"background-color:CornSilk;\" value=\"\">";
		echo "</p>";
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
			$type = "al";
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
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘+".$type."’";
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
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"create_keyboard\" value=\"CREATE NEW KEYBOARD MAPPING\"><br />named:&nbsp;";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			$type = "kb";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
			echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
			echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
			echo "</p>";
			echo "</form>";

			}
		echo "</div>";
		}
	else if($csound_folder) {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_csound\" value=\"CREATE NEW CSOUND RESOURCE FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "cs";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		if($i_am_designer) echo "<br /><input type=\"checkbox\" name=\"in_fixed\">in ‘".$csound_resources."’ (fixed)";
		echo "</p>";
		echo "</form>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_csound_orchestra\" value=\"CREATE NEW CSOUND ORCHESTRA FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">.orc";
		if($i_am_designer) echo "<br /><input type=\"checkbox\" name=\"in_fixed\">in ‘".$csound_resources."’ (fixed)";
		echo "</p>";
		echo "</form>";
		}
	else if($supercollider_folder) { // 2026-11-20
		foreach(array("create_supercollider" => "CREATE NEW SUPERCOLLIDER RESOURCE FILE","create_scsynthdef" => "CREATE NEW SYNTHDEF FILE") as $create_name => $create_label) {
			echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<p style=\"text-align:left;\">";
			echo "<input class=\"save\" type=\"submit\" name=\"".$create_name."\" value=\"".$create_label."\"><br />named:&nbsp";
			if($create_name == "create_supercollider") echo "-sc.";
			echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
			if($create_name == "create_scsynthdef") echo ".scd";
			if($i_am_designer) echo "<br /><input type=\"checkbox\" name=\"in_fixed\">in ‘".$supercollider_resources."’ (fixed)";
			echo "</p>";
			echo "</form>";
			}
		}
	else if($tonality_folder) { // 2026-11-19 Below IMPORT FILES, as in the Csound resource folder
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<p style=\"text-align:left;\">";
		echo "<input class=\"save\" type=\"submit\" name=\"create_tonality\" value=\"CREATE NEW TONALITY RESOURCE FILE\"><br />named:&nbsp";
		echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"\">";
		$type = "to";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"prefix\" checked>with prefix ‘-".$type."’";
		echo "<br /><input type=\"radio\" name=\"name_mode\" value=\"extension\">with extension ‘bp".$type."’";
		echo "<input type=\"hidden\" name=\"type\" value=\"".$type."\">";
		if($i_am_designer) echo "<br /><input type=\"checkbox\" name=\"in_fixed\">in ‘".$tonality_resources."’ (fixed)";
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
	else if($path == $trash_folder) // 2026-11-19 Resources still go back to their folders
		echo "<p id=\"refresh\"><span class=\"red-text\">➡ No destination folder was selected:</span> only Csound and tonality resources are moved back to their folders</p>";
	else {
		echo "<p><span class=\"red-text\">➡ No destination folder was selected, move canceled</span></p>";
		$move_checked_files = FALSE;
		}
	$move_files = FALSE;
	}

if($move_files) {
	$list_folders = array();
	$list_folders = folder_list($bp_application_path,$list_folders,'');
	if($path == $trash_folder) array_unshift($list_folders,$csound_resources_private,$tonality_resources_private); // 2026-11-19
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

if(!$delete_files AND !$rename_files AND !$move_files AND !$download_files AND $path <> $trash_folder AND !$resource_folder) { // 2026-11-19 The resource lists have their own buttons
	if($path <> '') echo "<input class=\"save\" style=\"margin-top:1em;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE\">";
	else  echo "<input class=\"save\" style=\"margin-top:1em;\" title=\"Delete folders or files\" type=\"submit\" name=\"delete_files\" value=\"DELETE FOLDERS\"><br /><br />";
	}
if($path <> '') {
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files AND !$resource_folder) {
		echo "&nbsp;<input class=\"save\"style=\"margin-top:1em;\" title=\"Rename folders or files\" type=\"submit\" name=\"rename_files\" value=\"RENAME OR COPY\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files AND !$resource_folder) {
		echo "&nbsp;<input class=\"save\" style=\"margin-top:1em;\" title=\"Move folders or files\" type=\"submit\" name=\"move_files\" value=\"MOVE\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files) {
		echo "&nbsp;<input class=\"save\" style=\"margin-top:1em;\" title=\"Download files\" type=\"submit\" name=\"download_files\" value=\"DOWNLOAD FILES\">";
		}
	if(!$rename_files AND !$delete_files AND !$move_files AND !$download_files AND $path == $trash_folder) {
		echo "<br /><br />🗑&nbsp;<input style=\"background-color:red; color:white;\" title=\"Empty trash\" type=\"submit\" name=\"empty_trash\" value=\"EMPTY THIS TRASH\"> 👉 can't be reversed!";
		}
	if(!$show_dependencies AND !$delete_files AND !$move_files AND !$download_files AND $path <> $trash_folder AND !is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources")) AND !is_integer(strpos($path,"supercollider_resources"))) echo "<br /><input class=\"edit\" title=\"Show dependencies of files (links to other files)\" type=\"submit\" name=\"show_dependencies\" value=\"SHOW DEPENDENCIES\"> (links between files)";
	else if($show_dependencies) echo "<br /><br /><input class=\"cancel\" type=\"submit\" value=\"HIDE DEPENDENCIES\">";
	echo "<br /><br />";
	}

// 2026-11-20 Files and folders of "csound_resources_private" that are not resources (samples, wave tables...), with a 'delete' button - by Claude
if($resource_kind == "csound") {
	$dependency_items = csound_private_dependencies();
	if(count($dependency_items) > 0) {
		// Same presentation as the list of files below it (display_directory())
		echo "<table class=\"thinborder\"><tr><td style=\"padding-bottom:6px;\">";
		echo "<b>Other files of ‘".$csound_resources_private."’</b><br /><small>Samples, wave tables… copied with your Csound resources.<br />Delete those you no longer need to free space.</small><br /><br />";
		echo "<script>
		function deleteDependency(name,message) {
			if(!confirm(message)) return false;
			var f = document.createElement('form'); f.method = 'post'; f.action = window.location.href;
			var i = document.createElement('input'); i.type = 'hidden'; i.name = 'delete_dependency'; i.value = name; f.appendChild(i);
			document.body.appendChild(f); f.submit(); return false;
			}
		</script>";
		foreach($dependency_items as $dependency_name => $dependency_item) {
			$dependency_users = array_keys($dependency_item['users']);
			$dependency_message = "Delete ".($dependency_item['folder'] ? "folder" : "file")." ".$dependency_name." for good?";
			if(count($dependency_users) > 0) {
				$dependency_message .= "\nIt is used by ".implode(", ",$dependency_users).".";
				$dependency_message .= $dependency_item['fixed'] ? "\nCsound will find the one of csound_resources as long as the distribution has it." : "\nThey will not work without it.";
				}
			echo "<span class=\"green-text\">".htmlspecialchars($dependency_name)."</span>&nbsp;&nbsp;";
			echo "<small>".($dependency_item['size'] >= 1048576 ? round($dependency_item['size'] / 1048576)." MB" : ceil($dependency_item['size'] / 1024)." kB");
			echo ", ".(count($dependency_users) > 0 ? "used by ".implode(", ",$dependency_users) : "not used").($dependency_item['fixed'] ? " (also in ‘".$csound_resources."’)" : "")."</small>&nbsp;&nbsp;";
			echo "<button type=\"button\" class=\"edit\" style=\"font-size:x-small; padding:1px 6px;\" onclick=\"return deleteDependency(".htmlspecialchars(json_encode($dependency_name)).",".htmlspecialchars(json_encode($dependency_message)).");\">delete</button><br />";
			}
		echo "</td></tr></table><br />";
		}
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

if(!is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources")) AND !is_integer(strpos($path,"supercollider_resources"))) {
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
		$n3 = display_directory(TRUE,$dir,"keyboard");
		echo "<h4>Keyboard mappings</h4>";
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
		if($n3 > 0) display_directory(FALSE,$dir,"keyboard");
		echo "</td>";
		echo"</tr>";
		if($path == $trash_folder) { // 2026-11-19 Deleted resources - by Claude
			echo "<tr>";
			echo "<th>";
			$n4 = display_directory(TRUE,$dir,"tonality");
			echo "<h4>Tonal resources</h4>";
			echo "</th>";
			echo "<th>";
			$n5 = display_directory(TRUE,$dir,"csresources");
			echo "<h4>Csound resources</h4>";
			echo "</th>";
			echo "<th></th>";
			echo"</tr>";
			echo "<tr>";
			echo "<td style=\"padding-bottom:6px;\">";
			if($n4 > 0) display_directory(FALSE,$dir,"tonality");
			echo "</td>";
			echo "<td style=\"padding-bottom:6px;\">";
			if($n5 > 0) display_directory(FALSE,$dir,"csresources");
			echo "</td>";
			echo "<td></td>";
			echo"</tr>";
			}
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
	if(!is_integer(strpos($path,"csound_resources")) AND !is_integer(strpos($path,"tonality_resources")) AND !is_integer(strpos($path,"supercollider_resources"))) {
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

	global $absolute_application_path, $error_opening;
	global $csound_folder,$csound_resources_private,$dir_csound_resources_private,$i_am_designer,$resource_folder,$resource_kind,$tonality_resources_private,$dir_tonality_resources_private;
	
	$dir = preg_replace('/'.preg_quote(SLASH,'/').'+$/','',$dir);
	$target_path = $dir;
	if(is_link($dir)) { // Dealing with a symbolic link
		$dir = str_replace($bp_application_path,$absolute_application_path,$dir);
		$target_path = readlink($dir);
		}
	if($target_path <> '') $dircontent = @scandir($target_path);
	else $dircontent = array();
	// 2026-11-19 The Csound resource folder shows the files of "csound_resources" (fixed) and "csound_resources_private" - by Claude
	$file_dir = array();
	if($resource_folder AND $dircontent) {
		$file_dir = resource_files($resource_kind);
		$dircontent = array_keys($file_dir);
		}
	$dir0 = $dir;
	if(!$dircontent) {
		if(!$error_opening) {
			if(file_exists($target_path)) echo "<p><span class=\"red-text\">Error opening a workspace</span> (<span class=\"green-text\">".$target_path."</span>) because of access limitations</p>";
			else echo "<p><span class=\"red-text\">Error opening a workspace</span> (<span class=\"green-text\">".$target_path."</span>) that does not exist</p>";
			$error_opening = TRUE;
			}
		return;
		}
	natcasesort($dircontent);
	$i_file = $files_shown = 0;
	$new_versions = 0;
	static $duplicate_script = FALSE;
	if($resource_folder AND !$test AND !$duplicate_script) {
		$duplicate_script = TRUE;
		// 2026-11-19 Script of the DUPLICATE buttons: asks for a name not used in either folder, then posts it - by Claude
		echo "<script>\nvar csNames = ".json_encode(array_values($dircontent)).";\n";
		echo "function duplicateResource(file) {\n\tvar m, pre = '', ext = '';\n\tif(m = file.match(/^-(cs|to|sc)\\./)) pre = m[0];\n\telse if(m = file.match(/\\.(orc|bpcs|bpto|scd)$/)) ext = m[0];\n\tvar name = prompt('Duplicate ' + file + ' to ".resource_dirs($resource_kind)['my_name']." as:', '');\n\tif(name === null) return false;\n\tname = name.trim().replace(/[ +$:\\/\\\\]/g,'_');\n\tname = name.replace(/^-(cs|to|sc)\\./,'').replace(/\\.(orc|bpcs|bpto|scd)$/,'');\n\tif(name == '' || name[0] == '.') { alert('Invalid name'); return false; }\n\tname = pre + name + ext;\n\tif(csNames.indexOf(name) >= 0) { alert(name + ' already exists'); return duplicateResource(file); }\n";
		echo "\tvar f = document.createElement('form'); f.method = 'post'; f.action = window.location.href;\n\t[['duplicate_resource',file],['duplicate_name',name]].forEach(function(p) { var i = document.createElement('input'); i.type = 'hidden'; i.name = p[0]; i.value = p[1]; f.appendChild(i); });\n\tdocument.body.appendChild(f); f.submit(); return false;\n\t}\n";
		echo "function trashResource(file) {\n\tif(!confirm('Move ' + file + ' to the trash?')) return false;\n\tvar f = document.createElement('form'); f.method = 'post'; f.action = window.location.href;\n\tvar i = document.createElement('input'); i.type = 'hidden'; i.name = 'trash_resource'; i.value = file; f.appendChild(i);\n\tdocument.body.appendChild(f); f.submit(); return false;\n\t}\n</script>\n";
		}
	foreach($dircontent as $thisfile) { 
		$i_file++;
		$dir = isset($file_dir[$thisfile]) ? rtrim($file_dir[$thisfile],"/\\") : $dir0;
		$link_folder = $path;
		$fixed = $locked = FALSE;
		if(isset($file_dir[$thisfile])) {
			$fixed = same_folder($dir,resource_dirs($resource_kind)['fixed']);
			$locked = ($fixed AND !$i_am_designer);
			if(!$fixed) $link_folder = resource_dirs($resource_kind)['my_name'];
			}
		if($thisfile[0] == '.' OR $thisfile[0] == '_') continue;
		if(is_integer($pos=strpos($thisfile,"BP2")) AND $pos == 0) continue;
		if(is_integer($pos=strpos($thisfile,"License")) AND $pos == 0) continue;
		if(is_integer($pos=stripos($thisfile,"ReadMe")) AND $pos == 0) continue;
		if(is_integer($pos=stripos($thisfile,"linux-scripts")) AND $pos == 0) continue; 
		if($thisfile == "resources" OR $thisfile == "source" OR $thisfile == "temp_bolprocessor" OR $thisfile == "Makefile" OR $thisfile == "my_output" OR $thisfile == "php" OR $thisfile == "www" OR $thisfile == "License.txt" OR $thisfile == "BP3-To-Do.txt" OR $thisfile == "Credits.txt" OR $thisfile == "bp_compile_result.txt" OR $thisfile == "test.php") continue;
		if($test) {
			if(($new_name = new_name($thisfile,"file")) <> $thisfile) rename($dir.SLASH.$thisfile,$dir.SLASH.$new_name);
			}
		if($move_files AND !$locked) $check_box = "<input type=\"checkbox\" name=\"move_".$i_file."\"> ";
		else $check_box = '';
		$this_file_moved = FALSE;
		$absolute_path = str_replace($bp_application_path,$absolute_application_path,$dir.SLASH.$thisfile);
		$this_is_a_link = is_link($absolute_path);
		$this_is_an_alias = FALSE;
		if(mac_system() AND !$this_is_a_link) $this_is_an_alias = is_macos_alias($absolute_path);
		if($this_is_an_alias) $this_is_a_link = TRUE;

	//	echo $dir.SLASH.$thisfile."<br>";

		if($trash_backups AND is_integer($pos=strpos($thisfile,"_bak"))) {
			$source_file = $dir.SLASH.$thisfile;
			rename($source_file,$dir_trash_folder.$thisfile);
			delete_settings($thisfile);
			continue;
			}
		if(!$test AND $move_checked_files AND isset($_POST['move_'.$i_file]) AND !$locked) {
			unset($_POST['move_'.$i_file]);
			$source_file = $dir.SLASH.$thisfile;
			// 2026-11-19 From the trash, Csound resources go back to "csound_resources_private" and tonality resources to "tonality_resources_private", whatever the folder chosen. Only these resources go to these folders - by Claude
			$this_dest = $dest_folder;
			$move_type = is_dir($source_file) ? "directory" : type_of_file($thisfile)['type'];
			$move_kind = resource_kind_of_type($move_type);
			if($path == $trash_folder AND $move_kind <> '') $this_dest = resource_dirs($move_kind)['my_name'];
			$dest_kind = ($this_dest == $csound_resources_private) ? "csound" : (($this_dest == $tonality_resources_private) ? "tonality" : '');
			$destination_file = $bp_application_path.$this_dest.SLASH.$thisfile;
			if($this_dest == '') {
				// No destination folder was chosen: only resources are moved
				}
			else if($dest_kind <> '' AND $move_kind <> $dest_kind) {
				echo "<p><span class=\"red-text\">➡ Only ".($dest_kind == "csound" ? "Csound" : "tonality")." resources can be moved to</span> ‘<span class=\"green-text\">".$this_dest."</span>’: ‘<span class=\"green-text\">".$thisfile."</span>’ was not moved</p>";
				}
			else if($dest_kind <> '' AND file_exists(resource_dirs($dest_kind)['fixed'].$thisfile)) {
				echo "<p><span class=\"red-text\">➡ There is already</span> a fixed file named ‘<span class=\"green-text\">".$thisfile."</span>’ in ‘<span class=\"green-text\">".resource_dirs($dest_kind)['fixed_name']."</span>’: rename the file in the trash before moving it</p>";
				}
			else if(is_integer($pos=strpos($dest_folder,$thisfile)) AND $pos == 0) {
				echo "<p><span class=\"red-text\">➡ Cannot move a folder to its own content</span></p>";
				}
			else if(file_exists($destination_file)) {
				if($this_dest <> '') $the_dest = "‘<span class=\"green-text\">".$this_dest."</span>’";
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
					if($move_type == "grammar" OR $move_type == "data") // 2026-11-20
						copy_project_files($destination_file,$dir,$bp_application_path.$this_dest);
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
		if(is_dir($dir.SLASH.$thisfile) OR $this_is_a_link) {
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
		if($resource_folder AND $type == "directory") continue; // 2026-11-19 Folders of "csound_resources" (samples...) and "tonality_resources" (scale_images) are not listed
		$is_csound_type = ($type == "csound" OR $type == "csorchestra");
		if(isset($done[$thisfile]) OR ($filter <> '' AND $filter <> $type AND !($filter == "csresources" AND $is_csound_type))) {
			continue;
			}
		if(!$test) $done[$thisfile] = TRUE;
		if($test AND isset($seen[$thisfile])) continue;
		if($test AND ($filter == '' OR $filter == $type OR ($filter == "csresources" AND $is_csound_type))) $seen[$thisfile] = TRUE;
	
		if(!ok_output_location($thisfile,FALSE)) continue;
		$new_version = ($fixed AND is_new_version_name($thisfile)); // 2026-11-19
		if($new_version AND !$test) $new_versions++;
		if(!$test AND (($new_file == $thisfile AND $filter <> "directory") OR $new_version)) echo "<span class=\"red-text\">➡</span> ";
		if(!$test) echo $check_box;

		if(!$test AND $path <> $csound_resources AND $path <> $trash_folder AND ($type == "csound" OR $type == "csorchestra")) {
			// 2026-11-19 To "csound_resources_private", unless a file with the same name is in a Csound resource folder
			echo "Moved ‘<span class=\"green-text\">".$dir.SLASH.$thisfile."</span>’ to ‘<span class=\"green-text\">".$dir_csound_resources_private.$thisfile."</span>’<br />";
			if(file_exists(csound_resource_path($thisfile))) @unlink($dir.SLASH.$thisfile);
			else rename($dir.SLASH.$thisfile,$dir_csound_resources_private.$thisfile);
			}
		else {
			if(!$test AND $path <> $tonality_resources AND $path <> $trash_folder AND $type == "tonality") {
				// 2026-11-19 To "tonality_resources_private", unless a file with the same name is in a tonality resource folder
				echo "Moved ‘<span class=\"green-text\">".$dir.SLASH.$thisfile."</span>’ to ‘<span class=\"green-text\">".$dir_tonality_resources_private.$thisfile."</span>’<br />";
				if(file_exists(tonality_resource_path($thisfile))) @unlink($dir.SLASH.$thisfile);
				else rename($dir.SLASH.$thisfile,$dir_tonality_resources_private.$thisfile);
				}
			else {
				$renamed = FALSE;
				if(!$test AND $delete_checked_files AND isset($_POST['delete_'.$i_file]) AND !$locked) {
					echo "<p><span class=\"red-text\">➡</span> Deleted ‘<span class=\"green-text\">".$thisfile."</span>’ (moved to <a target=\"_blank\" href=\"index.php?path=".$trash_folder."\">trash folder</a>)</p>";
					rename($dir.SLASH.$thisfile,$dir_trash_folder.$thisfile);
					delete_settings($thisfile);
					continue;
					}
				$new_name = '';
				if(!$test AND $rename_checked_files AND $type <> '' AND isset($_POST['new_name_'.$i_file]) AND trim($_POST['new_name_'.$i_file]) <> '') {
					$new_name = trim($_POST['new_name_'.$i_file]);
					$make_copy = (isset($_POST['copy_'.$i_file]) OR $locked); // A fixed file can only be copied
					$new_name = fix_new_name($new_name,TRUE);
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
							// 2026-11-19 ".orc" is the only form of the name of a Csound orchestra (no "-or." or ".bporc")
							if($type == "csorchestra") $new_name = preg_replace("/\\.orc$/",'',$new_name).".orc";
							else $new_name = good_name($short_type,$new_name,$name_mode);
							}
						$old_name = $thisfile;
						// 2026-11-19 In the Csound resource folder, the new name may not be that of a file in either folder. A copy of a fixed file goes to "csound_resources_private", unless the designer makes it.
						$copy_dir = $dir;
						if($resource_folder AND $make_copy AND !($fixed AND $i_am_designer)) $copy_dir = rtrim(resource_dirs($resource_kind)['my'],"/\\");
						if(file_exists($dir.SLASH.$new_name) OR ($resource_folder AND file_exists(resource_path($resource_kind,$new_name)))) {
							echo "<span class=\"red-text\">➡</span> Can't rename to existing ‘".$new_name."’: ";
							}
						else {
							if($make_copy) {
								if($type <> "directory") copy($dir.SLASH.$old_name,$copy_dir.SLASH.$new_name);
								else rcopy($dir.SLASH.$old_name,$copy_dir.SLASH.$new_name);
								if($resource_folder) $link = $type.".php?file=".urlencode(resource_folder($resource_kind,$new_name).SLASH.$new_name);
								else $link = $type.".php?file=".urlencode($path.SLASH.$new_name);
								echo "‘<span class=\"green-text\">".$old_name."</span>’ <span class=\"red-text\">➡</span> copied to <a target=\"_blank\" href=\"".nice_url($link)."\">".$new_name."</a><br />";
								// 2026-11-20 A copy of a Csound resource in "csound_resources_private" gets its own orchestra, samples and tonality, see private_csound_resource_independent()
								if($resource_folder AND $resource_kind == "csound" AND same_folder($copy_dir,resource_dirs("csound")['my']))
									echo private_csound_resource_independent($type,$new_name);
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
				if(!$test AND ($this_is_directory OR $this_is_a_link)) {
					if(hidden_directory($thisfile)) continue;
					echo "▶︎ ";
					}
				else if($thisfile == "bp" OR $thisfile == "bp.exe" OR $thisfile == "bp3") continue;
				if(!$test AND $delete_files AND $type <> '' AND !do_not_delete($thisfile) AND !$locked) echo "<input type=\"checkbox\" name=\"delete_".$i_file."\"> ";
				$link = $dir.SLASH.$thisfile;
				if(!$test AND $download_files AND $type <> '' AND !$this_is_directory) echo "<a href=\"".$link."\" title=\"Download this file\" download=\"".$thisfile."\">⬇️</a> ";
				if($type <> '') {
					$files_shown++;
					}
				if(!$test AND $type <> '' AND !$this_file_moved) {
					if($this_is_directory OR $this_is_a_link) {
						$table = explode('_',$thisfile);
						$extension = end($table);
						if($path == '') $link = $this_page."?path=".urlencode($thisfile);
						else $link = $this_page."?path=".urlencode($link_folder.SLASH.$thisfile);
						if($extension == "temp") $link = '';
						$this_is_directory = TRUE;
						}
					else $link = $type.".php?file=".urlencode($link_folder.SLASH.$thisfile);
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
					if($this_is_an_alias) echo "(alias)";
					else if($this_is_a_link) echo "(link)";
					if($fixed) echo "(fixed)&nbsp;";
					if($renamed) echo "(<span class=\"red-text\">renamed</span>)&nbsp;";
					if($rename_files) {
						echo "&nbsp;➡&nbsp;&nbsp;<input type=\"text\" style=\"border:2px; solid #dadada; border-bottom-style: groove; text-align:left;\" name=\"new_name_".$i_file."\" size=\"30\" value=\"\">";
						if($locked) echo "<input type=\"hidden\" name=\"copy_".$i_file."\" value=\"1\">&nbsp;➡&nbsp;copy only";
						else echo "<input type=\"checkbox\" name=\"copy_".$i_file."\">&nbsp;➡&nbsp;copy";
						}
					else if(!$this_is_directory AND !$this_is_a_link) {
						$time_saved = filemtime($dir.SLASH.$thisfile);
						echo "&nbsp;<small>&nbsp;".gmdate('Y-m-d H\hi',$time_saved)."</small>";
						if($resource_folder AND resource_kind_of_type($type) == $resource_kind) echo "&nbsp;&nbsp;<button type=\"button\" class=\"save\" style=\"font-size:x-small; padding:1px 6px;\" onclick=\"return duplicateResource(".htmlspecialchars(json_encode($thisfile)).");\">Duplicate</button>";
						if($resource_folder AND !$locked AND resource_kind_of_type($type) == $resource_kind) echo "&nbsp;<button type=\"button\" class=\"edit\" style=\"font-size:x-small; padding:1px 6px;\" onclick=\"return trashResource(".htmlspecialchars(json_encode($thisfile)).");\">delete</button>";
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
	if(!$test AND $new_versions > 0) echo "<p><span class=\"red-text\">➡</span> Files marked with a red arrow are new versions of fixed files, which an update renamed with “_new” because you have a file with the same name in ‘".resource_dirs($resource_kind)['my_name']."’. Compare them with yours, and delete or rename yours if the new version should replace it.</p>";
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
	if(!is_array($list)) $list = [];
	if(!is_dir($dir)) return $list;
	if(mac_system() AND is_macos_alias($dir)) return $list;
	$dircontent = @scandir($dir);
	if($dircontent === false) return $list;
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
	global $bp_application_path, $absolute_application_path, $error_opening;
    $count = 0;
	$target_path = $directory;
	if(is_link($directory)) { // Dealing with a symbolic link
		$directory = str_replace($bp_application_path,$absolute_application_path,$directory);
		$target_path = readlink($directory);
		}
	if($handle = @opendir($target_path)) {
		while(false !== ($file = readdir($handle))) {
			if (is_file($target_path . '/' . $file) && preg_match('/_bak$/', $file)) {
				$count++;
				}
			}
		closedir($handle);
		}
	else {
		if(!$error_opening) {
			if(file_exists($target_path)) echo "<p><span class=\"red-text\">Error opening a workspace</span> (<span class=\"green-text\">".$target_path."</span>) because of access limitations</p>";
			else echo "<p><span class=\"red-text\">Error opening a workspace</span> (<span class=\"green-text\">".$target_path."</span>) that does not exist</p>";
			$error_opening = TRUE;
			}
		}
    return $count;
	}
?>
