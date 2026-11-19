<?php
require_once("_basic_tasks.php");
$autosave = TRUE;
// $autosave = FALSE;
$verbose = TRUE;
$verbose = FALSE;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "tonality.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$warn_not_empty = FALSE;
$max_scales = 0;
$current_directory = str_replace(SLASH.$filename,'',$file);
// 2026-11-19 A tonality resource is in "my_tonality_resources" or (fixed) in "tonality_resources". Fixed files can only be viewed, unless $i_am_designer - by Claude
if($current_directory == $tonality_resources OR $current_directory == $my_tonality_resources) {
	$dir = tonality_resource_dir($filename);
	$this_file = $dir.$filename;
	$current_directory = $tonality_resources;
	}
$read_only = read_only_resource_folder($dir);
save_settings("last_directory",$current_directory);

require_once("_header.php");
display_darklight();

echo "<p>";
$url = "index.php?path=".urlencode($current_directory);
echo "&nbsp;Workspace = <input title=\"List this workspace\" class=\"edit big\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\"> ";
echo "  <span id='message3' style=\"margin-bottom:1em;\"></span>";

echo link_to_help();

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
$dir_scales = $temp_dir.$temp_folder.SLASH."scales".SLASH;
// 2026-11-10 Form actions are ignored if the temporary folder of the page is lost, and the page is rebuilt from the file on disk
if(stale_temp_folder($temp_folder)) {
	stale_page_message($filename);
	$_POST = array();
	}
if($read_only) {
	foreach(array_keys($_POST) as $ro_post_key) {
		if(in_array($ro_post_key,array('import_files','create_scale','undelete_scales','empty_trash','restore','savealldata','use_convention','record_new_keys','align_scales','delete_these_scales','change_convention','reassign_keys','delete_scales')) OR preg_match("/^delete_scale_[0-9]+$/",$ro_post_key)) unset($_POST[$ro_post_key]);
		}
	if(isset($_POST['copy_this_scale']) AND (!isset($_POST['file_choice']) OR $_POST['file_choice'] == $filename)) unset($_POST['copy_this_scale']);
	$autosave = FALSE;
	}
$keep = $read_only ? " read-only-keep" : ''; // Class of the fields and buttons used to copy or export scales to other files
$need_to_save = FALSE;
$scala_error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' AND isset($_POST['import_files'])) {
    // Check if files were uploaded
	$error = $ok_kbm = FALSE;
    if(isset($_FILES['file1'])) {
        // Check for any upload errors
        if($_FILES['file1']['error'] === UPLOAD_ERR_OK) {
            // Read the SCALA file
            $file1Content = file_get_contents($_FILES['file1']['tmp_name']);
            $file1Name = $scala_filename = $_FILES['file1']['name'];
			if(isset($_POST['scale_name']) AND strlen(trim($_POST['scale_name'])) > 0) {
				$new_filename1 = trim($_POST['scale_name']);
				}
			else $new_filename1 = str_replace(".scl",'',$file1Name);
  			}
		else {
            $scala_error = "<p><span class=\"red-text\">➡ Please select a SCALA file!</span></p>";
			$error = TRUE;
			}
		}
	if(!$error AND isset($_FILES['file2']))
        if($_FILES['file2']['error'] === UPLOAD_ERR_OK) {
            // Read the second file
            $file2Content = file_get_contents($_FILES['file2']['tmp_name']);
            $file2Name = $_FILES['file2']['name'];
			$new_filename2 = str_replace(".kbm",'',$file2Name);
			$ok_kbm = TRUE;
			}
	if(!$error) {
		$file_lock = $dir.$filename."_lock";
	//	echo "file_lock = ".$file_lock."<br />";
		$time_start = time();
		$time_end = $time_start + 3;
		while(TRUE) {
			if(!file_exists($file_lock)) break;
			if(time() > $time_end) @unlink($file_lock);
			sleep(1);
			}
		$handle_lock = fopen($file_lock,"w");
		fwrite($handle_lock,"lock\n");
		if($handle_lock) fclose($handle_lock);
		// Display the contents of both files
		echo "<h3>Contents of $file1Name:</h3>";
		echo "<pre>".htmlspecialchars($file1Content)."</pre>";
		$scala_error .= create_from_scl($new_filename1,$scala_filename,$file1Content);
		if($scala_error <> '') $scala_error = "<p><span class=\"red-text\">➡ Invalid SCALA file:</span> ".$scala_error."</p>";
		if($ok_kbm AND $scala_error == '') {
	/*		echo "<h2>Contents of $file2Name:</h2>";
			echo "<pre>".htmlspecialchars($file2Content)."</pre>"; */
			$scala_error = update_scale_with_kbm($new_filename1,'',$file2Content);
			if($scala_error <> '') {
				$scala_error = "<p><span class=\"red-text\">➡ Invalid KBM file:</span> ".$scala_error."</p>";
				@unlink($dir_scales."temp_scale_file.txt");
				}
			else {
				$scl_name = preg_replace("/\s+/u",' ',$new_filename1);
				$scl_name = str_replace("#","_",$scl_name);
				$scl_name = str_replace("/","_",$scl_name);
				$scale_file = $dir_scales.$scl_name.".txt";
				// 2026-11-18 The scale was renamed to a file in the 'php' folder, so the KBM file was ignored
				@unlink($scale_file);
				rename($dir_scales."temp_scale_file.txt",$scale_file);
				}
			}
		@unlink($file_lock);
        }
    }
echo "<h2>Tonality resource “".$filename."”".(same_folder($dir,$dir_tonality_resources) ? " (fixed)" : '')."</h2>";
if($read_only) {
	read_only_message($filename,"tonality resource",$tonality_resources);
	read_only_page_script();
	}
save_settings("last_name",$filename);

if($test) echo "dir = ".$dir."<br />";

if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
if(!file_exists($temp_dir.$temp_folder.SLASH."scales")) {
	mkdir($temp_dir.$temp_folder.SLASH."scales");
	}
// 2026-11-18 Tells scale.php which file to save when a scale is changed
$source_file = $temp_dir.$temp_folder.SLASH."_source";
if(@file_get_contents($source_file) !== $dir."\n".$filename."\n") file_put_contents($source_file,$dir."\n".$filename."\n");

if(isset($_POST['max_scales'])) $max_scales = $_POST['max_scales'];
else $max_scales = 0;

$error_create = '';
if(isset($_POST['create_scale'])) {
	$new_scale_name = trim($_POST['scale_name']);
	$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
	if($new_scale_name <> '') {
		$clean_name_of_file = str_replace("#","_",$new_scale_name);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$new_scale_file = $clean_name_of_file.".txt";
		$old_scale_file = $clean_name_of_file.".old";
		$result1 = check_duplicate_name($dir_scales,$clean_name_of_file.".png");
		$result2 = check_duplicate_name($dir_scales,$clean_name_of_file."-source.txt");
		$result3 = check_duplicate_name($dir_scales,$new_scale_file);
		$result4 = check_duplicate_name($dir_scales,$old_scale_file);
		if($result1 OR $result2 OR $result3 OR $result4) {
			$error_create = "<p><span class=\"red-text\">ERROR: This name</span> <span class=\"green-text\">‘".$new_scale_name."’</span> <span class=\"red-text\">already exists";
			$source = $dir_scales.$clean_name_of_file."-source.txt";
			if(file_exists($source)) {
				$content_source = trim(@file_get_contents($source));
				$error_create .= "in </span><span class=\"green-text\">‘<a target=\"_blank\" href=\"tonality.php?file=".urlencode(tonality_resource_folder($content_source).SLASH.$content_source)."\">".$content_source."</a>’</span> ";
				}
			echo "</span></p>";
			}
		else {
			$handle = fopen($dir_scales.$new_scale_file,"w");
			fwrite($handle,"\"".$new_scale_name."\"\n");
			$any_names = "/• • • • • • • • • • • • •/";
			fwrite($handle,$any_names."\n");
			$any_fractions = "[1 1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 2 1]";
			fwrite($handle,$any_fractions."\n");
			$any_scale = "f2 0 128 -51 12 2 261.63 60 1 1.059 1.122 1.189 1.26 1.335 1.414 1.498 1.587 1.682 1.782 1.888 2";
			fwrite($handle,$any_scale."\n");
			$any_comment = "<html>This is an equal-tempered scale for BP3.<br />Created ".date('Y-m-d H:i:s')."<br /></html>";
			fwrite($handle,$any_comment."\n");
			fclose($handle);
			chmod($dir_scales.$new_scale_file,$permissions);
			$need_to_save = TRUE;
			}
		}
	}
	
if(isset($_POST['undelete_scales'])) {
	$dircontent = scandir($dir_scales);
	foreach($dircontent as $some_scale) {
		if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
		$table = explode(".",$some_scale);
		$extension = end($table);
		if($extension == "old") {
			$some_scale = str_replace(".old",'',$some_scale);
			$file_link = $dir_scales.$some_scale.".old";
			$new_file_link = $dir_scales.$some_scale.".txt";
			rename($file_link,$new_file_link);
			$need_to_save = TRUE;
			}
		}
	}
	
if(isset($_POST['empty_trash'])) {
	$dircontent = scandir($dir_scales);
	foreach($dircontent as $some_scale) {
		if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
		$table = explode(".",$some_scale);
		$extension = end($table);
		if($extension == "old") unlink($dir_scales.$some_scale);
		}
	}

for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
	if(isset($_POST['delete_scale_'.$i_scale])) {
		$scalefilename = urldecode($_GET['scalefilename']);
		$clean_name_of_file = str_replace("#","_",$scalefilename);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$file_link = $dir_scales.$clean_name_of_file.".txt";
		$new_file_link = $dir_scales.$clean_name_of_file.".old";
	/*	echo "@".$file_link."@<br />";
		echo "@".$new_file_link."@<br />"; */
		@unlink($new_file_link);
		rename($file_link,$new_file_link);
		@unlink($dir_scales.$clean_name_of_file.".png");
		@unlink($dir_scales.$clean_name_of_file."-source.txt");
		$need_to_save = TRUE;
		}
	}
if(isset($_GET['scalefilename'])) $scalefilename = urldecode($_GET['scalefilename']);

$duplicated_scale = FALSE;
if(isset($_POST['copy_this_scale'])) {
	if(isset($_POST['file_choice']) OR (isset($_POST['duplicate_name']) AND trim($_POST['duplicate_name']) <> '')) {
		if(isset($_POST['file_choice'])) $destination = $_POST['file_choice'];
		else $destination = $filename;
		echo "<hr>";
		if($destination == $filename) {
			$new_scale_name = isset($_POST['duplicate_name']) ? trim($_POST['duplicate_name']) : '';
			$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
			$clean_name_of_file = str_replace("#","_",$new_scale_name);
			$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
			$new_scale_file = $clean_name_of_file.".txt";
			$old_scale_file = $clean_name_of_file.".old";
			$result1 = check_duplicate_name($dir_scales,$new_scale_file);
			$result2 = check_duplicate_name($dir_scales,$old_scale_file);
			// 2026-11-19 A scale with an empty name was created when "(self)" was selected without a name
			if($new_scale_name == '') {
				echo "<p><span class=\"red-text\">WARNING</span>: A name is needed to duplicate <span class=\"green-text\">‘".$scalefilename."’</span> in the same file</p>";
				}
			else if($result1 OR $result2) {
				echo "<p><span class=\"red-text\">WARNING</span>: This name <span class=\"green-text\">‘".$new_scale_name."’</span> already exists</p>";
				}
			else {
				echo "<p>Copied <span class=\"green-text\">‘".$scalefilename."’</span> ";
				echo "to: <span class=\"green-text\">‘".$new_scale_file."’</span></p>";
				$content = file_get_contents($dir_scales.$scalefilename.".txt",TRUE);
				$table = explode("\n",$content);
				$im = count($table);
				$handle = fopen($dir_scales.$new_scale_file,"w");
				if($handle) {
					fwrite($handle,"\"".$new_scale_name."\"\n");
					for($i = 1; $i < $im; $i++) {
						$line = trim($table[$i]);
						if($line == '') continue;
						if($line[0] == "\"") continue;
						fwrite($handle,$line."\n");
						}
					fclose($handle);
					chmod($dir_scales.$new_scale_file,$permissions);
					// 2026-11-18 Saved at once. Before, the copy was saved only by the next click on SAVE
					$need_to_save = TRUE;
					}
				else echo "<p><span class=\"red-text\">ERROR: couldn't open</span><span class=\"green-text\">".$dir_scales.$new_scale_file."</span></p>";
				}
			}
		else if(read_only_resource_folder(tonality_resource_dir($destination))) // 2026-11-19 Checked before creating locks
			echo "<p><span class=\"red-text\">‘".htmlspecialchars($destination)."’ is a fixed file that can't be changed</span></p>";
		else {
			echo "<p><span class=\"red-text\">Copying</span> <span class=\"green-text\">‘".$scalefilename."’</span> <span class=\"red-text\">to </span><span class=\"green-text\">‘".$destination."’</span></p>";
			echo "<p><span class=\"red-text\">➡</span> <a target=\"_blank\" href=\"tonality.php?file=".urlencode(tonality_resource_folder($destination).SLASH.$destination)."\">Edit ‘".$destination."’</a></p>";
			$dir_destination = tonality_resource_dir($destination); // 2026-11-19
			$file_lock = $dir_destination.$destination."_lock";
			$time_start = time();
			$time_end = $time_start + 10;
			while(TRUE) {
				if(!file_exists($file_lock)) break;
				if(time() > $time_end) {
					echo "<p><span class=\"red-text\">For an unknown reason the destination file is blocked by a trace file</span> <span class=\"green-text\">‘".$file_lock."’</span>. You should delete it by hand!</p>";
					break;
					}
				sleep(1);
				}
			// 2026-11-12 The scale is added to the JSON data of the destination file
			$file_lock3 = $dir_destination.$destination."_lock3";
			$handle = fopen($file_lock3,"w");
			if($handle) fclose($handle);
			$result = to_add_scales_to_file($dir_destination.$destination,array($temp_dir.$temp_folder.SLASH."scales".SLASH.to_scale_file_name($scalefilename)));
			if(!is_array($result)) echo "<p><span class=\"red-text\">".$result."</span></p>";
			else if(isset($result[$scalefilename]) AND !$result[$scalefilename]) {
				echo "<p><span class=\"red-text\">A scale with the same name</span> <span class=\"green-text\">‘".$scalefilename."’</span> <span class=\"red-text\">already exists in</span> <span class=\"green-text\">‘".$destination."’</span><span class=\"red-text\">. You need to delete it before copying this version</span></p>";
				}
			}
		echo "<hr>";
		}
	}
echo "<input type=\"hidden\" name=\"max_scales\" value=\"".$max_scales."\">";
for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
	if(isset($_POST['copy_scale_'.$i_scale])) {
		echo "<p><span class=\"red-text\">➡ The destination file should be closed to make sure that the export takes place</span></p>";
		echo "<form method=\"post\" action=\"".$url_this_page."&scalefilename=".urlencode($scalefilename)."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; font-size:large;\" name=\"\" onclick=\"this.form.target='_self';return true;\" value=\"Click to copy scale ‘".$scalefilename."’ to:\"><br />";
		echo "<blockquote>";
		echo "<input type=\"hidden\" name=\"copy_this_scale\" value=\"1\">";
		// 2026-11-19 Files of both tonality resource folders, except fixed ones
		foreach(list_of_tonality_resources() as $thisfile) {
			if($thisfile == $filename AND $read_only) continue;
			if($thisfile <> $filename AND read_only_resource_folder(tonality_resource_dir($thisfile))) continue;
			echo "<input type=\"radio\" class=\"read-only-keep\" name=\"file_choice\" value=\"".$thisfile."\">".$thisfile;
			if($thisfile == $filename) {
				echo " <span class=\"red-text\">(self)</span> <input type=\"submit\" style=\"background-color:aquamarine; \" name=\"\" onclick=\"this.form.target='_self';return true;\" value=\"DUPLICATE ‘".$scalefilename."’\"> under name: <input type=\"text\" name=\"duplicate_name\" size=\"40\" value=\"\">";
				}
			echo "<br />";
			}
		echo "</blockquote>";
		echo "</form>";
		echo "<hr>";
		}
	}

if(isset($_POST['restore'])) {
	echo "<p><span class=\"red-text\">Restoring: </span>";
	$dircontent = scandir($temp_dir.$temp_folder);
	$number_instruments = 0;
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension == "old" OR $extension == "txt") $number_instruments++;
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		$this_instrument_file = $temp_dir.$temp_folder.SLASH.$oldfile;
		$new_name = str_replace(".old",'',$this_instrument_file);
		if(!file_exists($new_name)) {
			rename($this_instrument_file,$new_name);
			echo "“<span class=\"green-text\">".str_replace(".txt",'',$thisfile)."</span>” ";
			}
		else echo "“<span class=\"green-text\"><del>".str_replace(".txt",'',$thisfile)."</del></span>” ";
		}
	$_POST['number_instruments'] = $number_instruments;
	echo "</p>";
	}

$lock3 = $dir.$filename."_lock3";
if($need_to_save OR isset($_POST['savealldata']) OR file_exists($dir_scales."_changed")) {
	if(isset($_POST['duplicated_scale'])) $duplicated_scale = $_POST['duplicated_scale'];
	else $duplicated_scale = FALSE;
	if(file_exists($lock3)) {
		unlink($lock3);
		if(!$duplicated_scale) {
			echo "<p><span class=\"red-text\">Saving file</span> <span class=\"green-text\">".$filename."</span> <span class=\"red-text\">was not possible this time because it has been modified by an external procedure…<br />Probably importing microtonal scales from another resource.<br />➡</span> Now the page has been refreshed and it can be saved</p>";
			}
		else $warn_not_empty = SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE);
		}
	else {
		echo "<p id=\"timespan\"><span class=\"red-text\">Saving file:</span> <span class=\"green-text\">".$filename."</span></p>";
		$warn_not_empty = SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE);
	//	sleep(1);
		}
	}
else if(!$duplicated_scale) @unlink($lock3);

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(strlen(trim($content)) == 0) {
	$template = "tonality_template";
	$content = @file_get_contents($template);
	}
// 2026-11-12 Tonality resources are read in JSON format, by the keys of their values. A file in the former line format is converted first.
$tonality_data = to_read_resource($content);
if(!is_array($tonality_data)) {
	echo "<p class=\"red-text\">➡ ".$tonality_data."</p>";
	die();
	}
echo "<p class=\"green-text\">".str_replace("\n","<br />",$tonality_data['header'])."</p>";
// echo $content."<br />";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save big\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" value=\"SAVE ‘".$filename."’\"></p>";

echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";

if($autosave) {
	echo "<p><span class=\"red-text\">➡</span> All data is <span class=\"red-text\">autosaved</span> every 30 seconds if changes occurred.<br />Keep this page open as long as you are editing instruments or scales!</p>";
	echo "<script type=\"text/javascript\" src=\"autosaveTonality.js\"></script>";
	}

echo "<input type=\"hidden\" name=\"tonality_source\" value=\"".$filename."\">";
echo "<input type=\"hidden\" name=\"duplicated_scale\" value=\"".$duplicated_scale."\">";

$begin_tables = "_begin tables";
if($verbose) echo "<br /><b>begin tables = “".$begin_tables."”</b><br />";
echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
// echo "<input type=\"hidden\" name=\"index_max\" value=\"".$index_max."\">";

echo "<table><tr><td>";

echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<div id=\"topscales\"></div>";
echo "<h3>Tonal scales</h3>";
// if($verbose) echo $dir_scales."<br />";
$dircontent = scandir($dir_scales);
$deleted_scales = 0;
foreach($dircontent as $some_scale) {
	if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
	if($verbose) echo $some_scale."<br />";
	$table_scale = explode(".",$some_scale);
	$extension = end($table_scale);
	if($extension == "old") {
		if($deleted_scales == 0) echo "<p>Deleted scale(s): <span class=\"turquoise-text\"><b>";
		$deleted_scales++;
		echo str_replace(".old",'',$some_scale)." ";
		}
	}
if($deleted_scales > 0) {
	echo "</b></span>&nbsp;<input class=\"edit\" type=\"submit\" name=\"undelete_scales\" onclick=\"this.form.target='_self';return true;\" value=\"UNDELETE all scales\">&nbsp;";
	echo "<input style=\"background-color:red; color:white;\" type=\"submit\" name=\"empty_trash\" onclick=\"this.form.target='_self';return true;\" value=\"TRASH deleted scales\">";
	echo "</p>";
	}
echo "<p><input class=\"save\" type=\"submit\" name=\"create_scale\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"CREATE A NEW TONAL SCALE\">&nbsp;with name <input type=\"text\" name=\"scale_name\" size=\"30\" value=\"\"></p>";
echo $scala_error;
echo "<p><label for=\"file1\">SCALA file:</label>&nbsp;";
echo "<input type=\"file\" name=\"file1\" id=\"file1\" accept=\".scl\">&nbsp;";
echo "➡&nbsp;<input class=\"edit\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"import_files\" onclick=\"this.form.target='_self';return true;\" value=\"CREATE A TONAL SCALE using SCALA and KBM files\"><br />";
echo "<label for=\"file2\">KBM file (optional):</label>&nbsp;";
echo "<input type=\"file\" name=\"file2\" id=\"file2\" accept=\".kbm\">&nbsp;";

echo "</p>";
if($error_create <> '') echo $error_create;

// echo "<textarea name=\"cstables\" rows=\"5\" style=\"width:400px;\">";
$cstables = '';
$i_scale = 0;
$scale_name = $scale_table = $scale_fraction = $scale_series = $comma_line = $scale_note_names = $scale_keys = $scale_comment = $baseoctave = array();
// Each scale is copied to a temporary file in the former line format, edited by scale.php. Its lines are also used for the display
foreach($tonality_data['scales'] as $scale) {
	if(!isset($scale["ScaleName"])) continue;
	$i_scale++;
	$scale_name[$i_scale] = $scale["ScaleName"];
	$scale_lines = to_scale_lines($scale);
	$table_name = $dir_scales.to_scale_file_name($scale_name[$i_scale]);
	file_put_contents($table_name,implode("\n",$scale_lines)."\n");
	chmod($table_name,$permissions);
	foreach($scale_lines as $line) {
		switch($line[0]) {
			case '/': $scale_note_names[$i_scale] = $line; break;
			case '<': $scale_comment[$i_scale] = $line; break;
			case '[': $scale_fraction[$i_scale] = $line; break;
			case 'k': $scale_keys[$i_scale] = $line; break;
			case 's': $scale_series[$i_scale] = $line; break;
			case 'c': $comma_line[$i_scale] = $line; break;
			case '|': $baseoctave[$i_scale] = $line; break;
			case 'f': $scale_table[$i_scale] = $line; break;
			}
		}
	}
// echo "</textarea><br />";

$max_scales = $i_scale; // Beware that we count scales from 1 because 0 is the default equal-tempered scale
if($max_scales > 0) {
	$done = TRUE;
	if(isset($_POST['use_convention'])) {
		$new_convention = $_POST['new_convention'];
		$scale_notes_string = "/";
		for($i = 0; $i <= 12; $i++) {
			if(!isset($_POST['new_note_'.$i]))
				$scale_notes_string .= $_POST['new_note_0']." ";
			else $scale_notes_string .= $_POST['new_note_'.$i]." ";
			}
		$scale_notes_string = trim($scale_notes_string)."/";
		echo "<p>New note names: <span class=\"red-text\">".$scale_notes_string."</span></p><span class=\"turquoise-text\"><b>";
		$dircontent = scandir($dir_scales);
		foreach($dircontent as $this_file) {
			if($this_file == '.' OR $this_file == ".." OR $this_file == ".DS_Store") continue;
			$table = explode(".",$this_file);
			$extension = end($table);
			if($extension <> "txt") continue;
			$this_filename = str_replace(".txt",'',$this_file);
			$content_scale = file_get_contents($dir_scales.$this_file,TRUE);
			$table = explode(chr(10),$content_scale);
			$num_grades_this_scale = 0;
			for($i = 0; $i < count($table); $i++) {
				$line = trim($table[$i]);
				if($line == '') continue;
				if($line[0] == 'f') {
					$line = preg_replace("/\s+/u",' ',$line);
					$table2 = explode(' ',$line);
					$num_grades_this_scale = intval($table2[4]);
					$basekey = intval($table2[7]);
					break;
					}
				}
			echo $this_filename." ";
			$handle = fopen($dir_scales.$this_file,"w");
			for($i = 0; $i < count($table); $i++) {
				$line = trim($table[$i]);
				if($line == '') continue;
				if($line[0] == '/') {
					$newline = "/";
					$all_notes = trim(str_replace("/",'',$line));
					$all_notes = preg_replace("/\s+/u",' ',$all_notes);
					$table2 = explode(' ',$all_notes);
					$im2 = count($table2);
					$bad = FALSE;
					for($j = $k = 0; $j < $im2 ; $j++) {
						$this_note = $table2[$j];
						if($this_note == '•') {
							$newline .= $this_note." ";
							continue;
							}
						if($k <= 12 OR $new_convention == 3) {
							if($new_convention == 3) {
								$new_note = $KeyString.($k + $basekey);
								}
							else {
								if(!isset($_POST['new_note_'.$k]))
									$new_note = $_POST['new_note_0'];
								else {
									if(($kfound = array_search($this_note,$Indiannote)) !== FALSE) $k = $kfound;
									else if(($kfound = array_search($this_note,$AltIndiannote)) !== FALSE) $k = $kfound;
									else if(($kfound = array_search($this_note,$Englishnote)) !== FALSE) $k = $kfound;
									else if(($kfound = array_search($this_note,$AltEnglishnote)) !== FALSE) $k = $kfound;
									else if(($kfound = array_search($this_note,$Frenchnote)) !== FALSE) $k = $kfound;
									else if(($kfound = array_search($this_note,$AltFrenchnote)) !== FALSE) $k = $kfound;
									$new_note = $_POST['new_note_'.$k];
									}
								}
							$newline .= $new_note." ";
							$k++;
							continue;
							}
						$bad = TRUE;
						break;
						}
					if(!$bad) {
						$newline = trim($newline)."/";
						$line = $newline;
						}
					}
				fwrite($handle,$line."\n");
				}
			fclose($handle);
			chmod($dir_scales.$this_file,$permissions);
			}
		echo "</span></b><br />";
		SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE); // 2026-11-18 Saved at once, otherwise the change was lost if the page was reloaded
		echo "<p><span class=\"red-text\">➡ Saved. Click SAVE ‘".$filename."’ to display fixed scales</span></p>";
		}
	
	if(isset($_POST['record_new_keys'])) {
		for($i = 0; $i <= 12; $i++) {
			$newkey[$i] = $_POST['new_key_'.$i] - 60;
			}
		echo "<br />";
		$dircontent = scandir($dir_scales);
		foreach($dircontent as $some_scale) {
			if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
			$table = explode(".",$some_scale);
			$extension = end($table);
			if($extension == "old" OR $extension == "txt") {
				$content_scale = file_get_contents($dir_scales.$some_scale,TRUE);
				$table = explode(chr(10),$content_scale);
				$new_keys_line = ''; $found = FALSE;
				$handle = fopen($dir_scales.$some_scale,"w");
				for($i = 0; $i < count($table); $i++) {
					$line = trim($table[$i]);
					if($line == '') continue;
					if($line[0] == '/') {
						$line_content = str_replace("/",'',$line);
						$line_content = preg_replace("/\s+/u",' ',$line_content);
						$table2 = explode(' ',$line_content);
						$k = -1;
						for($j = 0; $j < count($table2); $j++) {
							$this_note = $table2[$j];
							if(($kfound = array_search($this_note,$Indiannote)) !== FALSE) $k = $kfound;
							else if(($kfound = array_search($this_note,$AltIndiannote)) !== FALSE) $k = $kfound;
							else if(($kfound = array_search($this_note,$Englishnote)) !== FALSE) $k = $kfound;
							else if(($kfound = array_search($this_note,$AltEnglishnote)) !== FALSE) $k = $kfound;
							else if(($kfound = array_search($this_note,$Frenchnote)) !== FALSE) $k = $kfound;
							else if(($kfound = array_search($this_note,$AltFrenchnote)) !== FALSE) $k = $kfound;
							if($j == (count($table2) - 1)) $k = 12;
							$this_key = ($k >= 0) ? $newkey[$k] : "0";
							if($this_note == '•') $this_key = "0";
							$new_keys_line .= $this_key." ";
							}
						fwrite($handle,$line."\n");
						continue;
						}
					if($line[0] == 'k') {
						$new_keys_line = "k".trim($new_keys_line)."k";
						fwrite($handle,$new_keys_line."\n");
						$found = TRUE;
						continue;
						}
					if($line[0] == '<' AND !$found) {
						$new_keys_line = "k".trim($new_keys_line)."k";
						fwrite($handle,$new_keys_line."\n");
						$found = TRUE;
						}
					fwrite($handle,$line."\n");
					}
				fclose($handle);
				chmod($dir_scales.$some_scale,$permissions);
				}
			}
		SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE); // 2026-11-18 Saved at once, otherwise the change was lost if the page was reloaded
		echo "<p><span class=\"red-text\">➡ Saved. Click SAVE ‘".$filename."’ to display the new keys</span></p>";
		}
	
	if(isset($_POST['align_scales'])) {
		$file_lock = $dir.$filename."_lock";
		$time_start = time();
		$time_end = $time_start + 3;
		while(TRUE) {
			if(!file_exists($file_lock)) break;
			if(time() > $time_end) unlink($file_lock);
			sleep(1);
			}
		$handle_lock = fopen($file_lock,"w");
		fwrite($handle_lock,"lock\n");
		fclose($handle_lock);
		$need_to_save = FALSE;
		$dircontent = scandir($dir_scales);
		foreach($dircontent as $some_scale) {
			if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
			$table = explode(".",$some_scale);
			$extension = end($table);
			if($extension == "txt") {
				$file_link = $dir_scales.$some_scale;
				$content = file_get_contents($dir_scales.$some_scale,TRUE);
				$table2 = explode("\n",$content);
				$im = count($table2);
				$ratio_align = 0;
				$p_align = $q_align = 0;
				for($i = 0; $i < $im; $i++) {
					$line = trim($table2[$i]);
					if($line == '') continue;
					if($line[0] == 'f') {
						$line = preg_replace("/\s+/u",' ',$line);
						$table3 = explode(' ',$line);
						$ratio_tonic = $table3[8];
						if(abs($ratio_tonic - 1) > 0.01)
							$ratio_align = 1 / $ratio_tonic;
						}
					if($line[0] == '[') {
						$line = trim(str_replace('[','',$line));
						$line = preg_replace("/\s+/u",' ',$line);
						$table3 = explode(' ',$line);
						$p_tonic = intval($table3[0]);
						$q_tonic = intval($table3[1]);
						if(($p_tonic * $q_tonic) <> 0 AND ($p_tonic / $q_tonic) <> 1) {
							$p_align = $q_tonic;
							$q_align = $p_tonic;
							$ratio_align = $p_align / $q_align;
							}
						}
					}
				if($ratio_align == 0) continue;
				echo "Aligning <span class=\"green-text\">".str_replace(".txt",'',$some_scale)."</span> ratio = ".$ratio_align." = ".$p_align."/".$q_align."<br />";
				$need_to_save = TRUE;
				$handle = fopen($dir_scales.$some_scale,"w");
				for($i = 0; $i < $im; $i++) {
					$line = trim($table2[$i]);
					if($line == '') continue;
					$ratio = array();
					if($line[0] == '[') {
						$new_line = '';
						$line = trim(str_replace('[','',$line));
						$line = trim(str_replace(']','',$line));
						$line = preg_replace("/\s+/u",' ',$line);
						$table3 = explode(' ',$line);
						for($j = 0; $j < count($table3); $j += 2) {
							$p = $table3[$j];
							$q = $table3[$j + 1];
							if($p_align <> 0) {
								$p = $p * $p_align;
								$q = $q * $q_align;
								$fraction = simplify_fraction_eliminate_schisma($p,$q);
								if($fraction['p'] <> $p) {
									$p = $fraction['p'];
									$q = $fraction['q'];
									}
								$ratio[$j / 2] = $p / $q;
								}
							else {
								$p = $q = 0;
								}
							$new_line .= $p." ".$q." ";
							}
						$line = "[".trim($new_line)."]";
						}
					else if($line[0] == 'f') {
						$new_line = '';
						$line = preg_replace("/\s+/u",' ',$line);
						$table3 = explode(' ',$line);
						for($j = 0; $j < count($table3); $j++) {
							$k = $table3[$j];
							if($j < 8) $new_line .= $k." ";
							else {
								if(isset($ratio[$j - 8])) $k = $k * $ratio[$j - 8];
								else $k = $k * $ratio_align;
								$new_line .= round($k,3)." ";
								}
							}
						$line = trim($new_line);
						}
					fwrite($handle,$line."\n");
					}
				fclose($handle);
				chmod($dir_scales.$some_scale,$permissions);
				}
			}
		unlink($file_lock);
		if($need_to_save) {
			SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE); // 2026-11-18 Saved at once, otherwise the change was lost if the page was reloaded
			echo "<p><span class=\"red-text\">➡ Saved. Now click the SAVE ‘".$filename."’ button to refresh the display</span> </p>";
			}
		}	
		
	if(isset($_POST['reassign_keys'])) {
		$done = FALSE;
		echo "<p><span class=\"red-text\">➡</span> <input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input type=\"submit\" class=\"save big\" name=\"record_new_keys\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"REASSIGN:\"> a key to each note (<b>basekey</b> = 60):</p>";
		echo "<table>";
		echo "<tr>";
		for($i = 0; $i <= 12; $i++) {
			echo "<td style=\"text-align:center; padding:6px; vertical-align:middle;\">";
			echo $Englishnote[$i]."<br />".$Frenchnote[$i]."<br />".$Indiannote[$i];
			echo "</td>";
			}
		echo "</tr>";
		echo "<tr>";
		for($i = 0; $i <= 12; $i++) {
			echo "<td style=\"text-align:center; padding:6px; vertical-align:middle;\">";
			if($i == 0) {
				echo "60";
				echo "<input type=\"hidden\" name=\"new_key_0\" value=\"60\">";
				}
			else
				echo "<input type=\"text\" style=\"text-align:center;\" name=\"new_key_".$i."\" size=\"4\" value=\"".($i + 60)."\">";
			echo "</td>";
			}
		echo "</tr>";
		echo "</table>";
		}
	
	if(isset($_POST['change_convention']) AND isset($_POST['new_convention'])) {
		$new_convention = $_POST['new_convention'];
		echo "<input type=\"hidden\" name=\"new_convention\" value=\"".$new_convention."\">";
		$done = FALSE;
		echo "<hr>";
		switch($new_convention) {
			case '0':
				$standard_note = $Englishnote;
				$alt_note = $AltEnglishnote;
				break;
			case '1':
				$standard_note = $Frenchnote;
				$alt_note = $AltFrenchnote;
				break;
			case '2':
				$standard_note = $Indiannote;
				$alt_note = $AltIndiannote;
				break;
			case '3':
				$key = 60;
				for($i = 0; $i <= 13; $i++) {
					$standard_note[$i] = $KeyString.($key++);
					}
				break;
			}
		if($new_convention == 3) {
			echo "<p>(Will be adjusted to base key)</p><p><span class=\"red-text\">";
			for($i = 0; $i <= 12; $i++) {
				echo "<input type=\"hidden\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\">";
				echo $standard_note[$i]." ";
				}
			echo "</span></p>";
			}
		else {
			echo "<table>";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\" checked><br /><b><span class=\"red-text\">".$standard_note[$i];
				echo "</span></b></td>";
				}
			echo "</tr>";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				if($alt_note[$i] <> $standard_note[$i]) {
					echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$alt_note[$i]."\"><br /><b><span class=\"red-text\">".$alt_note[$i];
					echo "</span></b>";
					}
				echo "</td>";
				}
			echo "</tr>";
			echo "</table>";
			}
		echo "<p><input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"use_convention\" value=\"USE THIS CONVENTION IN ALL SCALES\"></p>";
		echo "<hr>";
		}
	if($done) {
		echo "<table class=\"thinborder\">";
		echo "<tr>";
		echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"change_convention\" value=\"CHANGE NOTE CONVENTION IN ALL SCALES\"> ➡</td>";
		echo "<td style=\"vertical-align:middle; white-space:nowrap; padding-bottom:6px;\">";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"3\">Key numbers<br />";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		}
	if($done) {
		echo "<p><input class=\"save".$keep."\" type=\"submit\" name=\"export_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"EXPORT TONAL SCALES\">";
		echo "&nbsp;<input class=\"save\" type=\"submit\" name=\"delete_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"DELETE SEVERAL SCALES\">";
		echo "&nbsp;<input class=\"save".$keep."\" type=\"submit\" name=\"compare_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"COMPARE TONAL SCALES\">";
		}
	echo "&nbsp;<input class=\"save\" type=\"submit\" name=\"reassign_keys\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"REASSIGN KEYS\">";
	echo "</p>";
	echo "<ol style=\"padding-top:6px; padding-bottom:6px; padding-right:6px;\">";
	$table_names = $p_interval = $q_interval = $cent_position = $ratio_interval = array();
	for($i_scale = 1, $k = 0; $i_scale <= $max_scales; $i_scale++) {
		$link_edit = "scale.php";
		echo "<li class=\"morespace\" id=\"".$i_scale."\"><span class=\"turquoise-text\"><b>".$scale_name[$i_scale]."</b></span> ";
		echo "➡ <input type=\"submit\" class=\"edit\" name=\"edit_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($scale_name[$i_scale])."&dir_scales=".urlencode($dir_scales)."&tonality_source=".urlencode($filename)."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT\">"; // 2025-02-07
		echo "&nbsp;<input type=\"submit\" class=\"save\" name=\"delete_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."\" onclick=\"this.form.target='_self';return true;\" value=\"DELETE scale (can be reversed)\">";
		echo "&nbsp;<input type=\"submit\" class=\"save".$keep."\" name=\"copy_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."\" onclick=\"this.form.target='_self';return true;\" value=\"".($read_only ? "COPY scale to another file" : "COPY/DUPLICATE scale")."\">";
		$scala_file = $dir_scales.$scale_name[$i_scale].".scl";
		$clean_name_of_file = str_replace("#","_",$scale_name[$i_scale]);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
	//	$dir = $dir_scales.$clean_name_of_file.".png";
		$image_file = $dir_scale_images.$clean_name_of_file.".png"; // 2026-11-12 Was $dir, which is the folder of the file used later by the export of scales
		if(file_exists($image_file)) {
			$k++; if($k > 10) $k = 0;
			echo "➡&nbsp;".popup_link($clean_name_of_file,"image",500,410,(100 * $k),$image_file);
			}
		echo "&nbsp;<input type=\"submit\" class=\"edit\" name=\"export_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."#".$i_scale."\" onclick=\"this.form.target='_self';return true;\" value=\"Export to SCALA\">";
		if(file_exists($scala_file) OR isset($_POST['export_scale_'.$i_scale])) echo "<span style=\"padding-right:1em;\">➡&nbsp;<a href=\"".nice_url($scala_file)."\" download=\"".$scale_name[$i_scale].".scl\">Download</a></span>";
		if(isset($scale_table[$i_scale])) echo "<br /><small><span class=\"green-text\">".$scale_table[$i_scale]."</span></small>";
		else {
			echo "<br />???";
			continue;
			}
		if(isset($scale_fraction[$i_scale])) {
			$fraction_string = str_replace('[','',str_replace(']','',$scale_fraction[$i_scale]));
			$fraction_string = preg_replace("/\s+/u",' ',$fraction_string);
			$table_fraction = explode(' ',$fraction_string);
			$tonality_line = $scale_table[$i_scale];
			$tonality_line = preg_replace("/\s+/u",' ',$tonality_line);
			$table_tonality_line = explode(' ',$tonality_line);
			$names_string = trim(str_replace('/','',$scale_note_names[$i_scale]));
			$names_string = preg_replace("/\s+/u",' ',$names_string);
			$table_names[$i_scale] = explode(' ',$names_string);
			$p_interval[$i_scale] = $q_interval[$i_scale] = $ratio_interval[$i_scale] = $note_name[$i_scale] = array();
			// 2026-11-19 Interval k is the step between named grades k and k + 1. It is a fraction only if both grades have one, otherwise it is computed from their ratios. Before, a fraction following grades without fractions was divided by the last fraction found (e.g. "2/1" instead of "20c"), and the reverse step used an outdated ratio
			$named = array();
			for($i = 0; ($i + 8) < count($table_tonality_line); $i++) {
				if(!isset($table_names[$i_scale][$i]) OR $table_names[$i_scale][$i] == "•") continue;
				$p = isset($table_fraction[2 * $i]) ? intval($table_fraction[2 * $i]) : 0;
				$q = isset($table_fraction[(2 * $i) + 1]) ? intval($table_fraction[(2 * $i) + 1]) : 0;
				$this_ratio = floatval($table_tonality_line[$i + 8]);
				if(($p * $q) > 0) $this_ratio = $p / $q;
				$named[] = array('name' => $table_names[$i_scale][$i],'p' => $p,'q' => $q,'ratio' => $this_ratio);
				}
			if(count($named) > 0 AND $named[0]['ratio'] > 0) $cent_position[$i_scale] = 1200 * log($named[0]['ratio']) / log(2);
			else $cent_position[$i_scale] = 0;
			for($k = 0; $k < count($named); $k++) $note_name[$i_scale][$k] = $named[$k]['name'];
			for($k = 0; $k < (count($named) - 1); $k++) {
				$a = $named[$k]; $b = $named[$k + 1];
				$p_interval[$i_scale][$k] = $q_interval[$i_scale][$k] = 0;
				$ratio_interval[$i_scale][$k] = ($a['ratio'] > 0 AND $b['ratio'] > 0) ? $b['ratio'] / $a['ratio'] : 1;
				if(($a['p'] * $a['q'] * $b['p'] * $b['q']) > 0) {
					$simple_fraction = simplify_fraction_eliminate_schisma($b['p'] * $a['q'],$b['q'] * $a['p']);
					$p_interval[$i_scale][$k] = $simple_fraction['p'];
					$q_interval[$i_scale][$k] = $simple_fraction['q'];
					}
				}
			}
	//	echo "<br />";
		if(isset($scale_note_names[$i_scale])) echo "<br /><span class=\"tonalnotes\">".str_replace('/','',$scale_note_names[$i_scale])."</span>";
		if(isset($scale_comment[$i_scale])) {
			$this_comment = html_to_text($scale_comment[$i_scale],'txt');
			$this_comment = substr($this_comment, 0, strpos($this_comment, "<br />"));
			echo "<br /><i>".$this_comment."</i>";
			}
		echo "</li>";
		}
	echo "</ol>";
	
	echo "<div id=\"export\"></div>";
	
	if(isset($_POST['delete_these_scales'])) {
		$file_lock = $dir.$filename."_lock3";
		$handle_lock = fopen($file_lock,"w");
		fwrite($handle_lock,"lock\n");
		if($handle_lock) fclose($handle_lock);
		$found_one = FALSE;
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			if(isset($_POST['delete_scale_'.$i_scale])) {
				$scalefile = $dir_scales.to_scale_file_name($scale_name[$i_scale]);
				echo "Deleting <span class=\"turquoise-text\"><b>‘".$scale_name[$i_scale]."’</b></span><br />";
				@unlink($scalefile);
				$found_one = TRUE;
				}
			}
		unlink($file_lock);
		if($found_one) {
			SaveTonality(FALSE,$dir,$filename,$temp_folder,TRUE); // 2026-11-18 Saved at once, otherwise the change was lost if the page was reloaded
			echo "<p><span class=\"red-text\">➡ Saved. Click SAVE ‘".$filename."’ to update the list</span></p>";
			}
		}
	
	if(isset($_POST['delete_scales'])) {
		echo "<p><input class=\"cancel\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_these_scales\" formaction=\"".$url_this_page."#export\" value=\"DELETE THE FOLLOWING SCALES:\"> (cannot be undone)</p>";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" name=\"delete_scale_".$i_scale."\"><span class=\"green-text\">".$scale_name[$i_scale]."</span><br />";
			}
		echo "<hr>";
		die();
		}
	
	if(isset($_POST['export_to']) AND isset($_POST['file_choice']) AND read_only_resource_folder(tonality_resource_dir($_POST['file_choice']))) { // 2026-11-19 Checked before creating locks
		echo "<p><span class=\"red-text\">‘".htmlspecialchars($_POST['file_choice'])."’ is a fixed file that can't be changed</span></p>";
		unset($_POST['export_to']);
		}
	if(isset($_POST['export_to']) AND isset($_POST['file_choice'])) {
		$destination = $_POST['file_choice'];
		echo "<h3>Exporting to <span class=\"green-text\">‘".$destination."’</span>:</h3>";
		$dir_destination = tonality_resource_dir($destination); // 2026-11-19
		$file_lock = $dir_destination.$destination."_lock";
		$time_start = time();
		$time_end = $time_start + 10;
		while(TRUE) {
			if(!file_exists($file_lock)) break;
			if(time() > $time_end) {
				echo "<p><span class=\"red-text\">For an unknown reason the destination file is blocked by a trace file</span> <span class=\"green-text\">‘".$file_lock."’</span>. You should delete it by hand!</p>";
				break;
				}
			sleep(1);
			}
		// 2026-11-12 The selected scales are added to the JSON data of the destination file
		echo "<ul>";
		$scale_files = $exported = array();
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			if(!isset($_POST['export_'.$i_scale])) continue;
			$exported[] = $scale_name[$i_scale];
			$scale_files[] = $dir_scales.to_scale_file_name($scale_name[$i_scale]);
			}
		$result = to_add_scales_to_file($dir_destination.$destination,$scale_files);
		if(!is_array($result)) echo "<li><span class=\"red-text\">".$result."</span></li>";
		else foreach($exported as $scalefilename) {
			echo "<li class=\"morespace\"><span class=\"turquoise-text\"><b>".$scalefilename."</b></span>";
			if(isset($result[$scalefilename]) AND !$result[$scalefilename]) {
				echo "<br />&nbsp;&nbsp;<span class=\"red-text\">➡ A scale with the same name</span> <span class=\"green-text\">‘".$scalefilename."’</span> <span class=\"red-text\">already exists in</span> <span class=\"green-text\">‘".$destination."’</span><span class=\"red-text\">. You need to delete it before copying this version</span>";
				}
			echo "</li>";
			}
		$file_lock3 = $dir_destination.$destination."_lock3";
		$handle_lock3 = fopen($file_lock3,"w");
		fwrite($handle_lock3,"lock\n");
		if($handle_lock3) fclose($handle_lock3);
		echo "</ul>";
		}
		
	if(isset($_POST['export_scales'])) {
		echo "<form method=\"post\" action=\"".$url_this_page."#export\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<hr><table class=\"thicktable\">";
		echo "<tr>";
		echo "<td>";
		echo "<input type=\"submit\" class=\"cancel\" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"CANCEL\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; \" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"EXPORT:\"><br /><br />";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" class=\"read-only-keep\" name=\"export_".$i_scale."\"><span class=\"green-text\">".$scale_name[$i_scale]."</span><br />";
			}
		echo "</td>";
		echo "<td>";
		echo "<h3>TO:</h3>";
		// 2026-11-19 Files of both tonality resource folders, except fixed ones
		foreach(list_of_tonality_resources() as $thisfile) {
			if($thisfile == $filename) continue;
			if(read_only_resource_folder(tonality_resource_dir($thisfile))) continue;
			echo "<input type=\"radio\" class=\"read-only-keep\" name=\"file_choice\" value=\"".$thisfile."\">".$thisfile;
			echo "<br />";
			}
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		}
	
	if(isset($_POST['compare_scales'])) {
		echo "<form method=\"post\" action=\"".$url_this_page."#export\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<hr><table class=\"thicktable\">";
		echo "<tr>";
		echo "<td style=\"padding-bottom:6px;\">";
		$url_classification = "compare_scales.php?file=".urlencode($current_directory.SLASH.$filename);
		echo "<input type=\"submit\" class=\"cancel\" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"CANCEL\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; \" name=\"export_to\" onclick=\"this.form.target='_blank';return true;\" formaction=\"".$url_classification."\" value=\"COMPARE selected scales:\"><br /><br />";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" class=\"read-only-keep\" name=\"compare_".str_replace(' ','_',$scale_name[$i_scale])."\"><span class=\"green-text\">".$scale_name[$i_scale]."</span><br />";
			}
		echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		}
		
	echo "<h3>Scale intervals (only labeled notes)</h3>";
	echo "<ol style=\"padding-top:6px; padding-bottom:6px; padding-right:6px;\">";
	for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
		if(isset($scale_fraction[$i_scale]) AND isset($table_names[$i_scale])) {
			$kmaxi = count($p_interval[$i_scale]);
			if($kmaxi == 0) continue;
			echo "<li class=\"morespace\"><span class=\"green-text\">".$scale_name[$i_scale]."</span> = ";
			$p_prod = $q_prod = 1;
			for($k = 0; $k < $kmaxi; $k++) {
				echo "<span class=\"tonalnotes\">".$note_name[$i_scale][$k]."</span> ";
				if(($p_interval[$i_scale][$k] * $q_interval[$i_scale][$k]) > 0) {
					echo "<small>".$p_interval[$i_scale][$k]."/".$q_interval[$i_scale][$k]."</small> ";
					$p_prod = $p_prod * $p_interval[$i_scale][$k]; // Only required for checking
					$q_prod = $q_prod * $q_interval[$i_scale][$k]; // Only required for checking
					}
				else {
					$cents = round(1200 * log($ratio_interval[$i_scale][$k]) / log(2));
					echo "<small>".$cents."c</small> ";
					}
				}
			for($j_scale = 1; $j_scale <= $max_scales; $j_scale++) {
				if($i_scale == $j_scale) continue;
				if(!isset($p_interval[$j_scale])) continue;	// Scale incorrecctly saved
				$kmaxj = count($p_interval[$j_scale]); // 2026-11-19 Was $i_scale
				if(($kmaxj <> $kmaxi) OR $kmaxj == 0) continue;
				if(count($p_interval[$j_scale]) == 0) continue;
				for($k = 0; $k < $kmaxi; $k++) {
					if(($p_interval[$i_scale][$k] * $p_interval[$j_scale][$k]) == 0) {
						$cents_i = round(1200 * log($ratio_interval[$i_scale][$k]) / log(2));
						$cents_j = round(1200 * log($ratio_interval[$j_scale][$k]) / log(2));
						if($cents_i <> $cents_j) break;
						}
					else {
						if($p_interval[$i_scale][$k] <> $p_interval[$j_scale][$k]) break;
						if($q_interval[$i_scale][$k] <> $q_interval[$j_scale][$k]) break;
						}
					}
				if($k == $kmaxi) {
					$cent_drift = round($cent_position[$i_scale]) - round($cent_position[$j_scale]);
					echo "<br >&nbsp;&nbsp;=> this scale is identical to <span class=\"green-text\">".$scale_name[$j_scale]."</span>";
					if($cent_drift > 0) echo " <span class=\"turquoise-text\">➡ raised by ".$cent_drift." cents</span>";
					if($cent_drift < 0) echo " <span class=\"turquoise-text\">➡ lowered by ".(-$cent_drift)." cents</span>";
					}
				}
			echo "</li>";
			}
		}
	echo "</ol>";
	}
echo "<input type=\"hidden\" name=\"max_scales\" value=\"".$max_scales."\">";

echo "<p><input class=\"save big\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</td>";

echo "</tr></table>";

for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
	if(isset($scale_fraction[$i_scale]) AND isset($table_names[$i_scale])) {
		if(isset($_POST['export_scale_'.$i_scale])) {
			export_scala($dir_scales,$scale_name[$i_scale]); // 2026-11-18 Same export as scale.php, including unnamed grades
			break;
			}
		}
	}

if($verbose) {
	echo "<hr>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	// echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
	echo "<textarea name=\"thistext\" rows=\"20\" style=\"width:700px;\">".$content."</textarea>";
	echo "</form>";
	}
echo "</body></html>";

// ============ FUNCTIONS ============

function create_from_scl($scale_name,$scala_filename,$content) {
	global $dir_scales, $dir_scales,$tonality_resources,$need_to_save,$permissions;
	$scala_error = '';
	$new_scale_name = preg_replace("/\s+/u",' ',$scale_name);
	$clean_name_of_file = str_replace("#","_",$new_scale_name);
	$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
	$new_scale_file = $clean_name_of_file.".txt";
	$old_scale_file = $clean_name_of_file.".old";
	$result1 = check_duplicate_name($dir_scales,$clean_name_of_file.".png");
	$result2 = check_duplicate_name($dir_scales,$clean_name_of_file."-source.txt");
	$result3 = check_duplicate_name($dir_scales,$new_scale_file);
	$result4 = check_duplicate_name($dir_scales,$old_scale_file);
//	if($result1 OR $result2 OR $result3 OR $result4) {
	if($result3 OR $result4) {
		$scala_error = "This name <span class=\"green-text\">‘".$new_scale_name."’</span> already exists";
		$source = $dir_scales.$clean_name_of_file."-source.txt";
	//	$scala_error .= "@ source = ".$source."<br />";
		if(file_exists($source)) {
			$content_source = trim(@file_get_contents($source));
			$scala_error .= " in </font><span class=\"green-text\">‘<a target=\"_blank\" href=\"tonality.php?file=".urlencode(tonality_resource_folder($content_source).SLASH.$content_source)."\">".$content_source."</a>’</span>";
			}
		return $scala_error;
		}
	else {
		$need_to_save = TRUE;
	//	echo "<p>§§§ ".$dir_scales.$new_scale_file."</p>";
		$handle = fopen($dir_scales.$new_scale_file,"w");
		fwrite($handle,"\"".$new_scale_name."\"\n");
		$comment = ''; $comment_done = $numgrades_ok = FALSE;
		$basefreq = 261.63; // This will be modified by the KBM file
		$basekey = 60; // This will be modified by the KBM file
		$table = explode(chr(10),$content);
		$imax = count($table);
		$frac = $note = array();
		$jfrac = 0; $jratio = 0;
		$frac[$jfrac++] = 1;
		$frac[$jfrac++] = 1;
		$ratio[$jratio++] = 1;
		$note[0] = $first_note = '';
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if($comment_done AND $line == '') continue;
			if(is_integer($pos=strpos($line,"!")) AND $pos == 0) continue;
			if(!$comment_done) {
				$comment = $line;
				$comment_done = TRUE;
				continue;
				}
			// echo "<p>@".$line."@</p>";
			if(!$numgrades_ok) {
				if(!ctype_digit($line) OR intval($line) < 2) {
					return "Number of grades (".$line.") is not a valid integer<br />";
					}
				else {
					$numgrades_fullscale = intval($line);
					$numgrades_ok = TRUE;
					}
				}
			$j = 1; $jj = 0;
			while(TRUE) {
				if(!isset($table[$i + $j])) break;
				$line = trim($table[$i + $j]);
				// echo "<p>@@".$line."@@</p>";
				if($line == '') break;
				$j++;
				if(is_integer($pos=strpos($line,"!")) AND $pos == 0) {
					// 2026-11-18 Name of the first note, in the comment "! 1/1 name" written by scale.php before the first grade
					if($jj == 0 AND preg_match("/^!\s*1\/1\s+(\S+)\s*$/u",$line,$matches)) $first_note = $matches[1];
					continue;
					}
				$jj++;
				$line = str_replace('\t',' ',$line);
				$line = preg_replace("/\s+/u",' ',$line);
				$table2 = explode(' ',$line);
				$pattern = '/^([1-9][0-9]*)\/([1-9][0-9]*)$/';
				if(preg_match($pattern,$table2[0],$matches) AND $matches[1] >  0 AND $matches[2] > 0) {
					$frac[$jfrac++] = $matches[1];
					$frac[$jfrac++] = $matches[2];
					$value = number_format($matches[1] / $matches[2],3,'.','');
					}
				else {
					$frac[$jfrac++] = 0;
					$frac[$jfrac++] = 0;
					if(!is_numeric($table2[0]) OR $table2[0] < 0) {
						return "Value ‘".$table2[0]."’ is neither a positive number nor a fraction<br />";
						}
					$value = number_format(pow(2,$table2[0] / 1200),3,'.','');
					}
				$ratio[$jratio++] = $value;
				if(count($table2) > 1) {
					if(is_integer($pos=strpos($table2[1],"c")) AND $pos == 0) {
					// Found "cents" or at least 'c'
						if(count($table2) > 2) $note[$jj] = $table2[2];
						else $note[$jj] = '';
						}
					else {
						if(count($table2) > 1) $note[$jj] = $table2[1];
						else $note[$jj] = '';
						}
					}
				else $note[$jj] = '';
				}
			if($numgrades_fullscale <> ($jj)) {
				return "Number of grades (".$numgrades_fullscale.") does not match number of lines (".($jj).")<br />";
				}
			else {
				if(isset($table[$i + $j]) AND trim($table[$i + $j]) <> '') {
					return "An extra non-empty line was found: <b>".$table[$i + $j]."</b><br />";
					}
				}
			break;
			}
		$names = "/";
	//	$note[0] = $note[$numgrades_fullscale];
		if($first_note <> '') $note[0] = $first_note;
		else $note[0] = "key#".$basekey;
		$convention = '';
		if(is_integer($pos=strpos($note[0],"do"))) $convention = "fr"; 
		for($j = 0; $j <= $numgrades_fullscale; $j++) {
			if($note[$j] <> '') $names .= $note[$j]." ";
			else $names .= "• ";
			}
		$names = trim($names)."/";
		fwrite($handle,$names."\n");
		$fractions = "[";
		for($j = 0; $j <= $numgrades_fullscale; $j++) {
			$fractions .= $frac[2 * $j]." ";
			$fractions .= $frac[(2 * $j) + 1]." ";
			}
		$fractions = trim($fractions)."]";
		fwrite($handle,$fractions."\n");
		if($convention == "fr") $baseoctave = 3;
		else $baseoctave = 4; // OK for English and Indian conventions
		fwrite($handle,"|".$baseoctave."|\n");
		$interval = $ratio[$numgrades_fullscale];
		$line_table = "f2 0 128 -51 ".$numgrades_fullscale." ".$interval." ".$basefreq." ".$basekey;
		for($j = 0; $j <= $numgrades_fullscale; $j++) $line_table .= " ".$ratio[$j];
		fwrite($handle,$line_table."\n");
		$full_comment = "<html>".$comment."<br />This scale has been imported from a SCALA file ‘".$scala_filename."’<br />Created ".date('Y-m-d H:i:s')."<br /></html>";
		fwrite($handle,$full_comment."\n");
		fclose($handle);
		chmod($dir_scales.$new_scale_file,$permissions);
		$file_changed = $dir_scales."_changed";
		$handle = fopen($file_changed,"w");
		if($handle) fclose($handle);
		}
	return $scala_error;
	}
?>
