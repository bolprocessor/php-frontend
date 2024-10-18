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
save_settings("last_directory",$current_directory);

require_once("_header.php");
echo "<p>Workspace = <a href=\"index.php?path=".urlencode($current_directory)."\">".$current_directory;
echo "</a>   <span id='message3' style=\"margin-bottom:1em;\"></span>";
echo "</p>";
echo link_to_help();

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
$dir_scales = $temp_dir.$temp_folder.SLASH."scales".SLASH;
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
            $scala_error = "<p><font color=\"red\">➡ Please select a SCALA file!</font></p>";
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
		echo "<h2>Contents of $file1Name:</h2>";
		echo "<pre>".htmlspecialchars($file1Content)."</pre>";
		$scala_error .= create_from_scl($new_filename1,$scala_filename,$file1Content);
		if($scala_error <> '') $scala_error = "<p><font color=\"red\">➡ Invalid SCALA file:</font> ".$scala_error."</p>";
		if($ok_kbm AND $scala_error == '') {
	/*		echo "<h2>Contents of $file2Name:</h2>";
			echo "<pre>".htmlspecialchars($file2Content)."</pre>"; */
			$scala_error = update_scale_with_kbm($new_filename1,'',$file2Content);
			if($scala_error <> '') {
				$scala_error = "<p><font color=\"red\">➡ Invalid KBM file:</font> ".$scala_error."</p>";
				@unlink($dir_scales."temp_scale_file.txt");
				}
			else {
				$scl_name = preg_replace("/\s+/u",' ',$new_filename1);
				$scl_name = str_replace("#","_",$scl_name);
				$scl_name = str_replace("/","_",$scl_name);
				$scale_file = $dir_scales.$scl_name.".txt";
				@unlink($new_filename1);
				rename($dir_scales."temp_scale_file.txt",$new_filename1);
				}
			}
		@unlink($file_lock);
        }
    }

echo "<h3>Tonality resource “".$filename."”</h3>";
save_settings("last_name",$filename);

if($test) echo "dir = ".$dir."<br />";

if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
if(!file_exists($temp_dir.$temp_folder.SLASH."scales")) {
	mkdir($temp_dir.$temp_folder.SLASH."scales");
	}

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
		$result1 = check_duplicate_name($dir_scale_images,$clean_name_of_file.".png");
		$result2 = check_duplicate_name($dir_scale_images,$clean_name_of_file."-source.txt");
		$result3 = check_duplicate_name($dir_scales,$new_scale_file);
		$result4 = check_duplicate_name($dir_scales,$old_scale_file);
		if($result1 OR $result2 OR $result3 OR $result4) {
			$error_create = "<p><font color=\"red\">ERROR: This name</font> <font color=\"blue\">‘".$new_scale_name."’</font> <font color=\"red\">already exists";
			$source_image = $dir_scale_images.$clean_name_of_file."-source.txt";
			if(file_exists($source_image)) {
				$content_source = trim(@file_get_contents($source_image,TRUE));
				$error_create .= "in <font color=\"blue\">‘<a target=\"_blank\" href=\"tonality.php?file=".urlencode($tonality_resources.SLASH.$content_source)."\">".$content_source."</a>’/font> ";
				}
			echo "</font></p>";
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
		@unlink($dir_scale_images.$clean_name_of_file.".png");
		@unlink($dir_scale_images.$clean_name_of_file."-source.txt");
		$need_to_save = TRUE;
		}
	}
if(isset($_GET['scalefilename'])) $scalefilename = urldecode($_GET['scalefilename']);

$duplicated_scale = FALSE;
if(isset($_POST['copy_this_scale'])) {
	if(isset($_POST['file_choice']) OR (isset($_POST['duplicate_name']) AND ($new_scale_name = trim($_POST['duplicate_name']) <> ''))) {
		if(isset($_POST['file_choice'])) $destination = $_POST['file_choice'];
		else $destination = $filename;
		echo "<hr>";
		if($destination == $filename) {
			$new_scale_name = trim($_POST['duplicate_name']);
			$new_scale_name = preg_replace("/\s+/u",' ',$new_scale_name);
			$clean_name_of_file = str_replace("#","_",$new_scale_name);
			$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
			$new_scale_file = $clean_name_of_file.".txt";
			$old_scale_file = $clean_name_of_file.".old";
			$result1 = check_duplicate_name($dir_scales,$new_scale_file);
			$result2 = check_duplicate_name($dir_scales,$old_scale_file);
			if($result1 OR $result2) {
				echo "<p><font color=\"red\">WARNING</font>: This name <font color=\"blue\">‘".$new_scale_name."’</font> already exists</p>";
				}
			else {
				echo "<p>Copied <font color=\"blue\">‘".$scalefilename."’</font> ";
				echo "to: <font color=\"blue\">‘".$new_scale_file."’</font></p>";
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
					$file_lock = $dir.$filename."_lock3";
					$handle_lock = fopen($file_lock,"w");
					fwrite($handle_lock,"lock\n");
					if($handle) fclose($handle_lock);
					echo "<p><font color=\"red\">Please SAVE THIS PAGE</font> to refresh the display!</p>";
					/* $need_to_save = */ $duplicated_scale = TRUE;
					}
				else echo "<p><font color=\"red\">ERROR: couldn't open</font><font color=\"blue\">".$dir_scales.$new_scale_file."</font></p>";
				}
			}
		else {
			echo "<p><font color=\"red\">Copying</font> <font color=\"blue\">‘".$scalefilename."’</font> <font color=\"red\">to </font><font color=\"blue\">‘".$destination."’</font></p>";
			echo "<p><font color=\"red\">➡</font> <a target=\"_blank\" href=\"tonality.php?file=".urlencode($tonality_resources.SLASH.$destination)."\">Edit ‘".$destination."’</a></p>";
			$file_lock = $dir.$destination."_lock";
			$time_start = time();
			$time_end = $time_start + 10;
			while(TRUE) {
				if(!file_exists($file_lock)) break;
				if(time() > $time_end) {
					echo "<p><font color=\"red\">For an unknown reason the destination file is blocked by a trace file</font> <font color=\"blue\">‘".$file_lock."’</font>. You should delete it by hand!</p>";
					break;
					}
				sleep(1);
				}
			$content = file_get_contents($dir.$destination,TRUE);
			if(!$content) echo "<p><font color=\"red\">For an unknown reason the destination file </font> <font color=\"blue\">‘".$file_lock."’</font> is empty</p>";
			else {
				$file_lock3 = $dir.$destination."_lock3";
				$handle = fopen($file_lock3,"w");
				if($handle) fclose($handle);
				$table = explode("\n",$content);
				$handle = fopen($dir.$destination,"w");
			//	echo "dest = ".$dir.$destination.".txt<br />";
				$found_scale = FALSE; $can_copy = TRUE;
				for($i = 0; $i < count($table); $i++) {
					$line = trim($table[$i]);
					if($found_scale AND $line <> '' AND $line[0] == "\"") {
						$some_scale = trim(str_replace("\"",'',$line));
						if($some_scale == $scalefilename) $can_copy = FALSE;
						}
					if($line == "_begin tables") $found_scale = TRUE;
					if($line == "_end tables") {
						if($can_copy) {
							$folder_scales = $temp_dir.$temp_folder.SLASH."scales";
					//		echo $folder_scales."<br />";
							$dir_scale = scandir($folder_scales);
							foreach($dir_scale as $this_scale) {
						//		echo "this_scale = ".$this_scale."<br />";
								if($this_scale == '.' OR $this_scale == ".." OR $this_scale == ".DS_Store") continue;
								$table2 = explode(".",$this_scale);
								$extension = end($table2);
								if($extension <> "txt") continue;
								$this_scale_name = str_replace(".txt",'',$this_scale);
								if($this_scale_name == $scalefilename) {
									$content_scale = file_get_contents($folder_scales.SLASH.$this_scale,TRUE);
									$table3 = explode(chr(10),$content_scale);
									for($j = 0; $j < count($table3); $j++) {
										$line3 = trim($table3[$j]);
							//			echo $line3."<br />";
										if($line3 <> '') fwrite($handle,$line3."\n");
										}
									}
								}
							}
						}
					fwrite($handle,$line."\n");
			//		echo $line."<br />";
					}
				fclose($handle);
			//	unlink($file_lock3);
				if(!$can_copy) {
					echo "<p><font color=\"red\">A scale with the same name</font> <font color=\"blue\">‘".$scalefilename."’</font> <font color=\"red\">already exists in</font> <font color=\"blue\">‘".$destination."’</font><font color=\"red\">. You need to delete it before copying this version</font></p>";
					}
				}
			}
		echo "<hr>";
		}
	}
echo "<input type=\"hidden\" name=\"max_scales\" value=\"".$max_scales."\">";
for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
	if(isset($_POST['copy_scale_'.$i_scale])) {
		echo "<p><font color=\"red\">➡ The destination file should be closed to make sure that the export takes place</font></p>";
		echo "<form method=\"post\" action=\"".$url_this_page."&scalefilename=".urlencode($scalefilename)."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; font-size:large;\" name=\"\" onclick=\"this.form.target='_self';return true;\" value=\"Click to copy scale ‘".$scalefilename."’ to:\"><br />";
		echo "<blockquote>";
		echo "<input type=\"hidden\" name=\"copy_this_scale\" value=\"1\">";
		$dircontent = scandir($dir);
		$folder = str_replace($bp_application_path,'',$dir);
		foreach($dircontent as $thisfile) {
			$prefix = substr($thisfile,0,3);
			$table = explode(".",$thisfile);
			$extension = end($table);
			if(($prefix <> "-to" AND $extension <> "bpto") OR is_integer(strpos($thisfile,"_lock"))) continue;
			echo "<input type=\"radio\" name=\"file_choice\" value=\"".$thisfile."\">".$thisfile;
			if($thisfile == $filename) {
				echo " <font color=\"red\">(self)</font> <input type=\"submit\" style=\"background-color:aquamarine; \" name=\"\" onclick=\"this.form.target='_self';return true;\" value=\"DUPLICATE ‘".$scalefilename."’\"> under name: <input type=\"text\" name=\"duplicate_name\" size=\"40\" value=\"\">";
				}
			echo "<br />";
			}
		echo "</blockquote>";
		echo "</form>";
		echo "<hr>";
		}
	}

if(isset($_POST['restore'])) {
	echo "<p><font color=\"red\">Restoring: </font>";
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
			echo "“<font color=\"blue\">".str_replace(".txt",'',$thisfile)."</font>” ";
			}
		else echo "“<font color=\"blue\"><del>".str_replace(".txt",'',$thisfile)."</del></font>” ";
		}
	$_POST['number_instruments'] = $number_instruments;
	echo "</p>";
	}

$lock3 = $dir.$filename."_lock3";
if($need_to_save OR isset($_POST['savealldata'])) {
	if(isset($_POST['duplicated_scale'])) $duplicated_scale = $_POST['duplicated_scale'];
	else $duplicated_scale = FALSE;
	if(file_exists($lock3)) {
		unlink($lock3);
		if(!$duplicated_scale) {
			echo "<p><font color=\"red\">Saving file</font> <font color=\"blue\">".$filename."</font> <font color=\"red\">was not possible this time because it has been modified by an external procedure…<br />Probably importing microtonal scales from another resource.<br />➡</font> Now the page has been refreshed and it can be saved</p>";
			}
		else $warn_not_empty = SaveTonality(FALSE,$dir,$filename,$temp_dir.$temp_folder,TRUE);
		}
	else {
		echo "<p id=\"timespan\"><font color=\"red\">Saving file:</font> <font color=\"blue\">".$filename."</font></p>";
		$warn_not_empty = SaveTonality(FALSE,$dir,$filename,$temp_dir.$temp_folder,TRUE);
	//	sleep(1);
		}
	}
else if(!$duplicated_scale) @unlink($lock3);

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(strlen(trim($content)) == 0) {
	$template = "tonality_template";
	$content = @file_get_contents($template,TRUE);
	}
$extract_data = extract_data(FALSE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
// echo $content."<br />";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" value=\"SAVE ‘".$filename."’\"></p>";

echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";

if($autosave) {
	echo "<p><font color=\"red\">➡</font> All data is <font color=\"red\">autosaved</font> every 30 seconds if changes occurred.<br />Keep this page open as long as you are editing instruments or scales!</p>";
	echo "<script type=\"text/javascript\" src=\"autosaveTonality.js\"></script>";
	}

echo "<input type=\"hidden\" name=\"tonality_source\" value=\"".$filename."\">";
echo "<input type=\"hidden\" name=\"duplicated_scale\" value=\"".$duplicated_scale."\">";

$content_no_br = str_replace("<br>",chr(10),$content);
$table = explode(chr(10),$content_no_br);
$imax_file = count($table);

$begin_tables = $table[0];
if($verbose) echo "<br /><b>begin tables = “".$begin_tables."”</b><br />";
echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
// echo "<input type=\"hidden\" name=\"index_max\" value=\"".$index_max."\">";

echo "<table style=\"background-color:white;\"><tr><td>";

echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
echo "<div id=\"topscales\"></div>";
echo "<h2>Tonal scales</h2>";
// if($verbose) echo $dir_scales."<br />";
$dircontent = scandir($dir_scales);
$deleted_scales = 0;
foreach($dircontent as $some_scale) {
	if($some_scale == '.' OR $some_scale == ".." OR $some_scale == ".DS_Store") continue;
	if($verbose) echo $some_scale."<br />";
	$table_scale = explode(".",$some_scale);
	$extension = end($table_scale);
	if($extension == "old") {
		if($deleted_scales == 0) echo "<p>Deleted scale(s): <font color=\"MediumTurquoise\"><b>";
		$deleted_scales++;
		echo str_replace(".old",'',$some_scale)." ";
		}
	}
if($deleted_scales > 0) {
	echo "</b></font>&nbsp;<input style=\"background-color:azure;\" type=\"submit\" name=\"undelete_scales\" onclick=\"this.form.target='_self';return true;\" value=\"UNDELETE all scales\">&nbsp;";
	echo "<input style=\"background-color:red; color:white;\" type=\"submit\" name=\"empty_trash\" onclick=\"this.form.target='_self';return true;\" value=\"TRASH deleted scales\">";
	echo "</p>";
	}
echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"create_scale\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"CREATE A NEW TONAL SCALE\">&nbsp;with name <input type=\"text\" name=\"scale_name\" size=\"30\" value=\"\"></p>";
echo $scala_error;
echo "<p><label for=\"file1\">SCALA file:</label>&nbsp;";
echo "<input type=\"file\" name=\"file1\" id=\"file1\" accept=\".scl\">&nbsp;";
echo "➡&nbsp;<input style=\"background-color:azure;\" type=\"submit\" formaction=\"".$url_this_page."\" name=\"import_files\" onclick=\"this.form.target='_self';return true;\" value=\"CREATE A TONAL SCALE using SCALA and KBM files\"><br />";
echo "<label for=\"file2\">KBM file (optional):</label>&nbsp;";
echo "<input type=\"file\" name=\"file2\" id=\"file2\" accept=\".kbm\">&nbsp;";

echo "</p>";
if($error_create <> '') echo $error_create;

// echo "<textarea name=\"cstables\" rows=\"5\" style=\"width:400px;\">";
$cstables = '';
$handle = FALSE; $i_scale = 0;
$done_table = TRUE;
$scale_name = $scale_table = $scale_fraction = $scale_series = $comma_line = $scale_note_names = $scale_keys = $scale_comment = $baseoctave = array();
for($i = 1; $i < $imax_file; $i++) {
	$line = trim($table[$i]);
//	if($verbose) echo "   ".$line."<br />";
	if($line == '') continue;
	if($line == "_end tables") break;
	if($line[0] == '"') {
		$i_scale++;
		$scale_name[$i_scale] = str_replace('"','',$line);
		$clean_name_of_file = str_replace("#","_",$scale_name[$i_scale]);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$table_name = $dir_scales.$clean_name_of_file.".txt";
	//	echo "table_name = ".$table_name."<br />";
		if(!file_exists($table_name)) {
			$handle = fopen($table_name,"w");
			fclose($handle);
			}
		$handle = fopen($table_name,"w");
		fwrite($handle,$line."\n");
		$done_table = FALSE;
		continue;
		}
	$clean_line = preg_replace("/<\/?html>/u",'',$line);
	$clean_line = relocate_function_table($dir,$clean_line);
	if($line[0] == '/') {
		if(!$done_table) $scale_note_names[$i_scale] = $line;
		else $scale_note_names[$i_scale + 1] = $line;
		continue;
		}
	if($line[0] == '<') {
		if($done_table) {
			fwrite($handle,$line."\n");
			fclose($handle);
			$scale_comment[$i_scale] = $line;
			}
		continue;
		}
	if($line[0] == '[') {
		fwrite($handle,$line."\n");
		$scale_fraction[$i_scale] = $line;
		continue;
		}
	if($line[0] == 'k') {
		fwrite($handle,$line."\n");
		$scale_keys[$i_scale] = $line;
		continue;
		}
	if($line[0] == 's') {
		fwrite($handle,$line."\n");
		$scale_series[$i_scale] = $line;
		continue;
		}
	if($line[0] == 'c') {
		fwrite($handle,$line."\n");
		$comma_line[$i_scale] = $line;
		continue;
		}
	if($line[0] == '|') {
		fwrite($handle,$line."\n");
		$baseoctave[$i_scale] = $line;
		continue;
		}
	$table2 = explode(' ',$line);
	if(count($table2) < 5) continue;
	$p3 = abs(intval($table2[3]));
	if(abs(intval($p3)) == 51) {
		if($done_table) {
			$i_scale++;
			$scale_name[$i_scale] = "scale_".$i_scale;
			}
		$clean_name_of_file = str_replace("#","_",$scale_name[$i_scale]);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$table_name = $dir_scales.$clean_name_of_file.".txt";
		if(!file_exists($table_name)) {
			$handle = fopen($table_name,"w");
			fclose($handle);
			}
		$handle = fopen($table_name,"w");
		fwrite($handle,"\"".$scale_name[$i_scale]."\"\n");
		if(isset($scale_note_names[$i_scale]))
			fwrite($handle,$scale_note_names[$i_scale]."\n");
		if(isset($comma_line[$i_scale]))
			fwrite($handle,$comma_line[$i_scale]."\n");
		if(isset($scale_keys[$i_scale]))
			fwrite($handle,$scale_keys[$i_scale]."\n");
		if(isset($scale_fraction[$i_scale]))
			fwrite($handle,$scale_fraction[$i_scale]."\n");
		if(isset($scale_series[$i_scale]))
			fwrite($handle,$scale_series[$i_scale]."\n");
		if(isset($baseoctave[$i_scale]))
			fwrite($handle,$baseoctave[$i_scale]."\n");
		$scale_table[$i_scale] = $line;
		fwrite($handle,$line."\n");
		$done_table = TRUE;
		}
	else {
		echo $clean_line."\n";
		$cstables .= $line."\n";
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
		echo "<p>New note names: <font color=\"red\">".$scale_notes_string."</font></p><font color=\"MediumTurquoise\"><b>";
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
			}
		echo "</font></b><br />";
		echo "<p><font color=\"red\">➡ Click SAVE ‘".$filename."’ to display fixed scales</font></p>";
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
							$this_key = $newkey[$k];
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
				}
			}
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
				echo "Aligning <font color=\"blue\">".str_replace(".txt",'',$some_scale)."</font> ratio = ".$ratio_align." = ".$p_align."/".$q_align."<br />";
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
				}
			}
		unlink($file_lock);
		if($need_to_save) echo "<p><font color=\"red\">➡ Now click the SAVE ‘".$filename."’ button to refresh the display</font> </p>";
		}	
		
	if(isset($_POST['reassign_keys'])) {
		$done = FALSE;
		echo "<p><font color=\"red\">➡</font> <input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:yellow; font-size:large;\" name=\"record_new_keys\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"REASSIGN:\"> a key to each note (<b>basekey</b> = 60):</p>";
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
			echo "<p>(Will be adjusted to base key)</p><p><font color=\"red\">";
			for($i = 0; $i <= 12; $i++) {
				echo "<input type=\"hidden\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\">";
				echo $standard_note[$i]." ";
				}
			echo "</font></p>";
			}
		else {
			echo "<table style=\"background-color:white;\">";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$standard_note[$i]."\" checked><br /><b><font color=\"red\">".$standard_note[$i];
				echo "</font></b></td>";
				}
			echo "</tr>";
			echo "<tr>";
			for($i = 0; $i < 12; $i++) {
				echo "<td>";
				if($alt_note[$i] <> $standard_note[$i]) {
					echo "<input type=\"radio\" name=\"new_note_".$i."\" value=\"".$alt_note[$i]."\"><br /><b><font color=\"red\">".$alt_note[$i];
					echo "</font></b>";
					}
				echo "</td>";
				}
			echo "</tr>";
			echo "</table>";
			}
		echo "<p><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input style=\"background-color:Aquamarine;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"use_convention\" value=\"USE THIS CONVENTION IN ALL SCALES\"></p>";
		echo "<hr>";
		}
	if($done) {
		echo "<table style=\"background-color:white;\">";
		echo "<tr>";
		echo "<td style=\"vertical-align:middle; white-space:nowrap;\"><input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"change_convention\" value=\"CHANGE NOTE CONVENTION IN ALL SCALES\"> ➡</td>";
		echo "<td style=\"vertical-align:middle; white-space:nowrap;\">";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"0\">English<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"1\">Italian/Spanish/French<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"2\">Indian<br />";
		echo "<input type=\"radio\" name=\"new_convention\" value=\"3\">Key numbers<br />";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		}
	if($done) {
		echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"export_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"EXPORT TONAL SCALES\">";
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"delete_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"DELETE SEVERAL SCALES\">";
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"compare_scales\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"COMPARE TONAL SCALES\">";
		}
	echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"reassign_keys\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"REASSIGN KEYS\">";
	echo "</p>";
	echo "<ol>";
	$table_names = $p_interval = $q_interval = $cent_position = $ratio_interval = array();
	for($i_scale = 1, $k_image = 0; $i_scale <= $max_scales; $i_scale++) {
		$link_edit = "scale.php";
		echo "<li id=\"".$i_scale."\"><font color=\"MediumTurquoise\"><b>".$scale_name[$i_scale]."</b></font> ";
		echo "➡ <input type=\"submit\" style=\"background-color:Aquamarine;\" name=\"edit_scale\" formaction=\"".$link_edit."?scalefilename=".urlencode($scale_name[$i_scale])."\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:yellow;\" name=\"delete_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."\" onclick=\"this.form.target='_self';return true;\" value=\"DELETE scale (can be reversed)\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:yellow;\" name=\"copy_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."\" onclick=\"this.form.target='_self';return true;\" value=\"COPY/DUPLICATE scale\">";
		$scala_file = $dir_scales.$scale_name[$i_scale].".scl";

		$clean_name_of_file = str_replace("#","_",$scale_name[$i_scale]);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$dir_image = $dir_scale_images.$clean_name_of_file.".png";
		if(file_exists($dir_image)) {
			$k_image++; if($k_image > 10) $k_image = 0;
			echo "➡&nbsp;".popup_link($clean_name_of_file,"image",500,410,(100 * $k_image),$dir_image);
			}
		echo "&nbsp;<input type=\"submit\" style=\"background-color:azure;\" name=\"export_scale_".$i_scale."\" formaction=\"".$url_this_page."&scalefilename=".urlencode($scale_name[$i_scale])."#".$i_scale."\" onclick=\"this.form.target='_self';return true;\" value=\"Export to SCALA\">";
		if(file_exists($scala_file) OR isset($_POST['export_scale_'.$i_scale])) echo "<span style=\"background-color:azure; padding-right:1em;\">➡&nbsp;<a href=\"".$scala_file."\" download=\"".$scale_name[$i_scale].".scl\">Download</a></span>";
		if(isset($scale_table[$i_scale])) echo "<br /><small><font color=\"blue\">".$scale_table[$i_scale]."</font></small>";
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
			$p_interval[$i_scale] = $q_interval[$i_scale] = $ratio_interval[$i_scale] = array();
			$p_old = $q_old = 0;
			$oldratio = 1;
			$note_name[$i_scale][0] = $table_names[$i_scale][0];
			for($i_fraction = $k = 0; $i_fraction < (count($table_fraction) - 1); $i_fraction += 2) {
				if(!isset($table_names[$i_scale][$i_fraction / 2]) OR $table_names[$i_scale][$i_fraction / 2] == "•") continue;
				$note_name[$i_scale][$k + 1] = $table_names[$i_scale][$i_fraction / 2];
				$ratio[$i_scale][$k] = $table_tonality_line[($i_fraction / 2) + 8];
				$ratio_interval[$i_scale][$k] = 1;
				$p_interval[$i_scale][$k] = $q_interval[$i_scale][$k] = 0;
				$p = intval($table_fraction[$i_fraction]);
				$q = intval($table_fraction[$i_fraction + 1]);
				$p_position[$i_scale][$k] = $p;
				$q_position[$i_scale][$k] = $q;
				if(($p * $q) > 0) {
				//	echo $p."/".$q." ";
					if($i_fraction  == 0)
						$cent_position[$i_scale] = 1200 * log($p/$q) /log(2);
					if($i_fraction > 1) {
						$p_this_interval = $p * $q_old;
						$q_this_interval = $q * $p_old;
						$simple_fraction = simplify_fraction_eliminate_schisma($p_this_interval,$q_this_interval);
						if($simple_fraction['p'] <> $p_this_interval) {
							$p_this_interval = $simple_fraction['p'];
							$q_this_interval = $simple_fraction['q'];
							}
						$p_interval[$i_scale][$k] = $p_this_interval;
						$q_interval[$i_scale][$k] = $q_this_interval;
						$k++;
						}
					$p_old = $p;
					$q_old = $q;
					}
				else {
				//	echo " <small>".round($ratio[$i_scale][$k],4)." </small>";
					if($i_fraction > 1) {
						$ratio_interval[$i_scale][$k] = $ratio[$i_scale][$k] / $oldratio;
						}
					$oldratio = $ratio[$i_scale][$k];
					$p_interval[$i_scale][$k] = $q_interval[$i_scale][$k] = 0;
					$k++;
					}
				}
			}
	//	echo "<br />";
		if(isset($scale_note_names[$i_scale])) echo "<br /><font color=\"red\">".str_replace('/','',$scale_note_names[$i_scale])."</font>";
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
		if($handle) fclose($handle_lock);
		$found_one = FALSE;
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			if(isset($_POST['delete_scale_'.$i_scale])) {
				$scalefile = $dir_scales.$scale_name[$i_scale].".txt";
				echo "Deleting <font color=\"MediumTurquoise\"><b>‘".$scale_name[$i_scale]."’</b></font><br />";
				unlink($scalefile);
				$found_one = TRUE;
				}
			}
		if($found_one) echo "<p><font color=\"red\">➡ Click SAVE ‘".$filename."’ to update the list</font></p>";
		unlink($file_lock);
		}
	
	if(isset($_POST['delete_scales'])) {
		echo "<p><input style=\"background-color:cornsilk;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"\" value=\"CANCEL\">";
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_these_scales\" formaction=\"".$url_this_page."#export\" value=\"DELETE THE FOLLOWING SCALES:\"> (cannot be undone)</p>";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" name=\"delete_scale_".$i_scale."\"><font color=\"blue\">".$scale_name[$i_scale]."</font><br />";
			}
		echo "<hr>";
		die();
		}
	
	if(isset($_POST['export_to']) AND isset($_POST['file_choice'])) {
		$destination = $_POST['file_choice'];
		echo "<h3>Exporting to <font color=\"blue\">‘".$destination."’</font>:</h3>";
		$file_lock = $dir.$destination."_lock";
		$time_start = time();
		$time_end = $time_start + 10;
		while(TRUE) {
			if(!file_exists($file_lock)) break;
			if(time() > $time_end) {
				echo "<p><font color=\"red\">For an unknown reason the destination file is blocked by a trace file</font> <font color=\"blue\">‘".$file_lock."’</font>. You should delete it by hand!</p>";
				break;
				}
			sleep(1);
			}
		$content = file_get_contents($dir.$destination,TRUE);
		if(!$content) echo "<p><font color=\"red\">For an unknown reason the destination file </font> <font color=\"blue\">‘".$file_lock."’</font> is empty</p>";
		else {
			echo "<ul>";
			$table = explode("\n",$content);
			$found_scale = FALSE;
			$some_scale = array();
			$handle = fopen($dir.$destination,"w");
			for($i = $k = 0; $i < count($table); $i++) {
				$line = trim($table[$i]);
				if($found_scale AND $line <> '' AND $line[0] == "\"") {
					$some_scale[$k++] = trim(str_replace("\"",'',$line));
					}
				if($line == "_begin tables") $found_scale = TRUE;
				if($line == "_end tables") {
					for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
						if(isset($_POST['export_'.$i_scale])) {
							$scalefilename = $scale_name[$i_scale];
							echo "<li><font color=\"MediumTurquoise\"><b>".$scalefilename."</b></font>";
							if(in_array($scalefilename,$some_scale)) {
								echo "<br />&nbsp;&nbsp;<font color=\"red\">➡ A scale with the same name</font> <font color=\"blue\">‘".$scalefilename."’</font> <font color=\"red\">already exists in</font> <font color=\"blue\">‘".$destination."’</font><font color=\"red\">. You need to delete it before copying this version</font>";
								}
							else {
								$content2 = file_get_contents($dir_scales.$scalefilename.".txt",TRUE);
								$table2 = explode("\n",$content2);
								$jm = count($table2);
								fwrite($handle,"\"".$scalefilename."\"\n");
								for($j = 1; $j < $jm; $j++) {
									$line2 = trim($table2[$j]);
									if($line2 == '') continue;
									if($line2[0] == "\"") continue;
									fwrite($handle,$line2."\n");
								//	echo $line2."<br />";
									}
								}
							echo "</li>";
							}
						}
					}
				fwrite($handle,$line."\n");
			//	echo $line."<br />";
				}
			fclose($handle);
			$file_lock3 = $dir.$destination."_lock3";
			$handle_lock3 = fopen($file_lock3,"w");
			fwrite($handle_lock3,"lock\n");
			if($handle_lock3) fclose($handle_lock3);
			echo "</ul>";
			}
		}
		
	if(isset($_POST['export_scales'])) {
		echo "<form method=\"post\" action=\"".$url_this_page."#export\" enctype=\"multipart/form-data\">";
		echo "<hr><table style=\"background-color:white;\">";
		echo "<tr>";
		echo "<td>";
		echo "<input type=\"submit\" style=\"background-color:azure; \" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"Cancel\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; \" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#export\" value=\"EXPORT:\"><br /><br />";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" name=\"export_".$i_scale."\"><font color=\"blue\">".$scale_name[$i_scale]."</font><br />";
			}
		echo "</td>";
		echo "<td>";
		echo "<h3>TO:</h3>";
echo $dir."<br />";
		$dircontent = scandir($dir);
		$folder = str_replace($bp_application_path,'',$dir);
		foreach($dircontent as $thisfile) {
			$prefix = substr($thisfile,0,3);
			$table = explode(".",$thisfile);
			$extension = end($table);
			if($thisfile == $filename) continue;
			if(($prefix <> "-to" AND $extension <> "bpto") OR is_integer(strpos($thisfile,"_lock"))) continue;
			echo "<input type=\"radio\" name=\"file_choice\" value=\"".$thisfile."\">".$thisfile;
			echo "<br />";
			}
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		}
	
	if(isset($_POST['compare_scales'])) {
		echo "<form method=\"post\" action=\"".$url_this_page."#export\" enctype=\"multipart/form-data\">";
		echo "<hr><table style=\"background-color:white;\">";
		echo "<tr>";
		echo "<td>";
		$url_classification = "compare_scales.php?file=".urlencode($current_directory.SLASH.$filename);
		echo "<input type=\"submit\" style=\"background-color:azure; \" name=\"export_to\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."\" value=\"Cancel\">";
		echo "&nbsp;<input type=\"submit\" style=\"background-color:aquamarine; \" name=\"export_to\" onclick=\"this.form.target='_blank';return true;\" formaction=\"".$url_classification."\" value=\"COMPARE selected scales:\"><br /><br />";
		for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
			echo "<input type=\"checkbox\" name=\"compare_".$scale_name[$i_scale]."\"><font color=\"blue\">".$scale_name[$i_scale]."</font><br />";
			}
		echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
		}
		
	echo "<h3>Scale intervals (only labeled notes)</h3>";
	echo "<ol>";
	for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
		if(isset($scale_fraction[$i_scale]) AND isset($table_names[$i_scale])) {
			$kmaxi = count($p_interval[$i_scale]);
			if($kmaxi == 0) continue;
			echo "<li><font color=\"blue\">".$scale_name[$i_scale]."</font> = ";
			$p_prod = $q_prod = 1;
			for($i_fraction = $k = 0; $k < $kmaxi; $i_fraction += 2) {
				if($table_names[$i_scale][$i_fraction / 2] == "•") continue;
				echo "<font color=\"red\">".$note_name[$i_scale][$k]."</font> ";
				if(($p_interval[$i_scale][$k] * $q_interval[$i_scale][$k]) > 0) {
					echo "<small>".$p_interval[$i_scale][$k]."/".$q_interval[$i_scale][$k]."</small> ";
					$p_prod = $p_prod * $p_interval[$i_scale][$k]; // Only required for checking
					$q_prod = $q_prod * $q_interval[$i_scale][$k]; // Only required for checking
					$k++;
					}
				else {
					$cents = round(1200 * log($ratio_interval[$i_scale][$k]) / log(2));
					echo "<small>".$cents."c</small> ";
					$k++;
					}
				}
			for($j_scale = 1; $j_scale <= $max_scales; $j_scale++) {
				if($i_scale == $j_scale) continue;
				if(!isset($p_interval[$j_scale])) continue;	// Scale incorrecctly saved
				$kmaxj = count($p_interval[$i_scale]);
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
					echo "<br >&nbsp;&nbsp;=> this scale is identical to <font color=\"blue\">".$scale_name[$j_scale]."</font>";
					if($cent_drift > 0) echo " <font color=\"MediumTurquoise\">➡ raised by ".$cent_drift." cents</font>";
					if($cent_drift < 0) echo " <font color=\"MediumTurquoise\">➡ lowered by ".(-$cent_drift)." cents</font>";
					}
				}
			echo "</li>";
			}
		}
	echo "</ol>";
	}
echo "<input type=\"hidden\" name=\"max_scales\" value=\"".$max_scales."\">";

echo "<p><input style=\"background-color:yellow; font-size:larger;\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#topscales\" value=\"SAVE ‘".$filename."’\"></p>";
echo "</td>";

echo "</tr></table>";

for($i_scale = 1; $i_scale <= $max_scales; $i_scale++) {
	if(isset($scale_fraction[$i_scale]) AND isset($table_names[$i_scale])) {
		if(isset($_POST['export_scale_'.$i_scale])) {
			$olddir = getcwd();
			chdir($dir_scales);
			$file = $scale_name[$i_scale].".scl";
			$text = "! ".$file."\n";
			$text .= "! Scala file, ref. https://www.huygens-fokker.org/scala/scl_format.html\n";
			if(isset($scale_comment[$i_scale])) {
				$this_comment = html_to_text($scale_comment[$i_scale],'txt');
				$this_comment = substr($this_comment, 0, strpos($this_comment, "<br />"));
				$this_comment = str_replace("-cs.","-to.",$this_comment);
				$text .= $this_comment."\n";
				}
			else $text .= "This scale is called '".$scale_name[$i_scale]."'\n";
			$text .= "! Created by the Bol Processor\n";
			$kmaxi = count($p_interval[$i_scale]);
			$text .= $kmaxi."\n";
			for($i_fraction = $k = 0; $k < $kmaxi; $i_fraction += 2) {
				if($table_names[$i_scale][$i_fraction / 2] == "•") continue;
				if(($p_interval[$i_scale][$k] * $q_interval[$i_scale][$k]) > 0) {
					$text .= $p_position[$i_scale][$k]."/".$q_position[$i_scale][$k]." ".$note_name[$i_scale][$k + 1]."\n";
					$k++;
					}
				else {
					$cents = round(1200 * log($ratio[$i_scale][$k]) / log(2));
					$text .= $cents." cents ".$note_name[$i_scale][$k + 1]."\n";
					$k++;
					}
				}
			$handle = fopen($file,"w");
			if($handle) {
				fwrite($handle,$text);
				fclose($handle);
				}
			chdir($olddir);
			break;
			}
		}
	}

if($verbose) {
	echo "<hr>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	// echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";
	echo "<textarea name=\"thistext\" rows=\"20\" style=\"width:700px;\">".$content."</textarea>";
	echo "</form>";
	}
echo "</body></html>";

// ============ FUNCTIONS ============

function create_from_scl($scale_name,$scala_filename,$content) {
	global $dir_scales, $dir_scale_images,$tonality_resources,$need_to_save;
	$scala_error = '';
	$new_scale_name = preg_replace("/\s+/u",' ',$scale_name);
	$clean_name_of_file = str_replace("#","_",$new_scale_name);
	$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
	$new_scale_file = $clean_name_of_file.".txt";
	$old_scale_file = $clean_name_of_file.".old";
	$result1 = check_duplicate_name($dir_scale_images,$clean_name_of_file.".png");
	$result2 = check_duplicate_name($dir_scale_images,$clean_name_of_file."-source.txt");
	$result3 = check_duplicate_name($dir_scales,$new_scale_file);
	$result4 = check_duplicate_name($dir_scales,$old_scale_file);
//	if($result1 OR $result2 OR $result3 OR $result4) {
	if($result3 OR $result4) {
		$scala_error = "This name <font color=\"blue\">‘".$new_scale_name."’</font> already exists";
		$source_image = $dir_scale_images.$clean_name_of_file."-source.txt";
	//	$scala_error .= "@ source_image = ".$source_image."<br />";
		if(file_exists($source_image)) {
			$content_source = trim(@file_get_contents($source_image,TRUE));
			$scala_error .= " in </font><font color=\"blue\">‘<a target=\"_blank\" href=\"tonality.php?file=".urlencode($tonality_resources.SLASH.$content_source)."\">".$content_source."</a>’";
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
		$note[0] = '';
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
				if(is_integer($pos=strpos($line,"!")) AND $pos == 0) continue;
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
		$note[0] = "key#".$basekey;
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
		$file_changed = $dir_scales."_changed";
		$handle = fopen($file_changed,"w");
		if($handle) fclose($handle);
		}
	return $scala_error;
	}
?>
