<?php
require_once("_basic_tasks.php");

$autosave = TRUE;
// $autosave = FALSE;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "objects.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
save_settings("last_directory",$current_directory);

if(isset($_POST['createcsoundinstruments'])) {
	$CsoundInstruments_filename = $_POST['CsoundInstruments_filename'];
	$handle = fopen($dir.$CsoundInstruments_filename,"w");
	$template = "csound_template";
	$template_content = @file_get_contents($template,TRUE);
	fwrite($handle,$template_content."\n");
	fclose($handle);
	$path = str_replace($bp_application_path,'',$dir);
	$url = "csound.php?file=".urlencode($path.$CsoundInstruments_filename);
	header("Location: ".$url); 
	}

require_once("_header.php");
echo "<p>Workspace = <a href=\"index.php?path=".urlencode($current_directory)."\">".$current_directory;
echo "</a>   <span id='message1' style=\"margin-bottom:1em;\"></span>";
echo "</p>";
echo link_to_help();

echo "<h3>Object prototypes file “".$filename."”</h3>";
save_settings("last_name",$filename);

if($test) echo "dir = ".$dir."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}

if(isset($_POST['create_object'])) {
	$new_object = trim($_POST['new_object']);
	$new_object = str_replace(' ','-',$new_object);
	$new_object = str_replace(SLASH,'-',$new_object);
	$new_object = str_replace('#','-',$new_object);
	$new_object = str_replace('"','',$new_object);
	if($new_object <> '') {
		$template = "object_template";
		$template_content = @file_get_contents($template,TRUE);
		$new_object_file = $temp_dir.$temp_folder.SLASH.$new_object.".txt";
		$handle = fopen($new_object_file,"w");
		$file_header = $top_header."\n// Object prototype saved as \"".$new_object."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$filename."\n");
		fwrite($handle,$template_content."\n");
		fclose($handle);
		$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
		fclose($handle);
		}
	}

if(isset($_POST['duplicate_object'])) {
	$object = $_POST['object_name'];
	$copy_object = trim($_POST['copy_object']);
	$copy_object = str_replace(' ','-',$copy_object);
	$copy_object = str_replace('/','-',$copy_object);
	$copy_object = str_replace('#','-',$copy_object);
	$copy_object = str_replace('"','',$copy_object);
	$this_object_file = $temp_dir.$temp_folder.SLASH.$object.".txt";
	$copy_object_file = $temp_dir.$temp_folder.SLASH.$copy_object.".txt";
	if(!file_exists($copy_object_file)) {
		copy($this_object_file,$copy_object_file);
		@unlink($temp_dir.$temp_folder.SLASH.$copy_object.".txt.old");
		$this_object_codes = $temp_dir.$temp_folder.SLASH.$object."_codes";
		$copy_object_codes = $temp_dir.$temp_folder.SLASH.$copy_object."_codes";
		rcopy($this_object_codes,$copy_object_codes);
		}
	else echo "<p><font color=\"red\">Cannot create</font> <font color=\"blue\"><big>“".$copy_object."”</big></font> <font color=\"red\">because an object with that name already exists</font></p>";
	}

if(isset($_POST['delete_object'])) {
	$object = $_POST['object_name'];
	echo "<p><font color=\"red\">Deleted </font><font color=\"blue\"><big>“".$object."”</big></font>…</p>";
	$this_object_file = $temp_dir.$temp_folder.SLASH.$object.".txt";
//	echo $this_object_file."<br />";
	rename($this_object_file,$this_object_file.".old");
	$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
	fclose($handle);
	}

if(isset($_POST['rename_object'])) {
	$object = $_POST['object_name'];
	$new_object = trim($_POST['object_new_name']);
	$new_object = str_replace(' ','-',$new_object);
	$new_object = str_replace(SLASH,'-',$new_object);
	$new_object = str_replace('#','-',$new_object);
	$new_object = str_replace('"','',$new_object);
	if($new_object <> '') {
		echo "<p><font color=\"red\">Renamed </font><font color=\"blue\"><big>“".$object."”</big></font> as <font color=\"blue\"><big>“".$new_object."”</big></font>…</p>";
		$this_object_file = $temp_dir.$temp_folder.SLASH.$object.".txt";
		$new_object_file = $temp_dir.$temp_folder.SLASH.$new_object.".txt";
	//	echo $new_object_file."<br />";
		if(file_exists($this_object_file)) {
			rename($this_object_file,$new_object_file);
		//	unlink($this_object_file);
			$this_object_codes = $temp_dir.$temp_folder.SLASH.$object."_codes";
			$new_object_codes = $temp_dir.$temp_folder.SLASH.$new_object."_codes";
			rcopy($this_object_codes,$new_object_codes);
			$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
			fclose($handle);
			}
		}
	}

if(isset($_POST['restore'])) {
	echo "<p><font color=\"red\">Restoring: </font>";
	// echo "<font color=\"blue\">".$object."</font> </p>";
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		echo "<font color=\"blue\">".str_replace(".txt",'',$thisfile)."</font> ";
		$this_object_file = $temp_dir.$temp_folder.SLASH.$oldfile;
		rename($this_object_file,str_replace(".old",'',$this_object_file));
		}
	echo "</p>";
	$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
	fclose($handle);
	}

$deleted_objects = '';
$dircontent = scandir($temp_dir.$temp_folder);
foreach($dircontent as $oldfile) {
	if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
	$table = explode(".",$oldfile);
	$extension = end($table);
	if($extension <> "old") continue;
	$thisfile = str_replace(".old",'',$oldfile);
	$this_object = str_replace(".txt",'',$thisfile);
	$deleted_objects .= "“".$this_object."” ";
	}

if(isset($_POST['savethisfile']) OR isset($_POST['create_object']) OR isset($_POST['delete_object']) OR isset($_POST['rename_object']) OR isset($_POST['restore']) OR isset($_POST['duplicate_object'])) {
	$maxsounds = $_POST['maxsounds'];
	if($test) echo "SaveObjectPrototypes() dir = ".$dir."<br />";
	if($test) echo "filename = ".$filename."<br />";
	if($test) echo "temp_folder = ".$temp_folder."<br />";
	if($test) echo "maxsounds = ".$maxsounds."<br />";
	$lock_file = $dir.$filename."_lock";
	$time_start = time();
	$time_end = $time_start + 5;
	$bad = FALSE;
	while(TRUE) {
		if(!file_exists($lock_file)) break;
		if(time() > $time_end) {
			echo "<p><font color=\"red\">Maximum time (5 seconds) spent waiting for the sound-object prototypes file to be unlocked:</font> <font color=\"blue\">".$dir.$filename."</font></p>";
			$bad = TRUE;
			break;
			}
		}			
	if(!$bad) {
		echo "<p id=\"timespan\"><font color=\"red\">Saved file:</font> <font color=\"blue\">";
		SaveObjectPrototypes(TRUE,$dir,$filename,$temp_dir.$temp_folder,TRUE);
		}
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file,TRUE);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(strlen(trim($content)) == 0) {
	$template = "prototypes_template";
	$content = @file_get_contents($template,TRUE);
	}
$objects_file = $grammar_file = $csound_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$extract_data = extract_data(TRUE,$content);
echo "<p style=\"color:blue;\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];
$csound_file = $extract_data['csound'];

$comment_on_file = '';
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
$table = explode(chr(10),$content);
$iobj = -1;
$handle_object = FALSE;
for($i = 0; $i < count($table); $i++) {
	$line = $table[$i];
	if($i == 0) {
		$PrototypeTickKey = $line;
		echo "PrototypeTickKey = <input type=\"text\" name=\"PrototypeTickKey\" size=\"4\" value=\"".$PrototypeTickKey."\"><br />";
		}
	if($i == 1) {
		$PrototypeTickChannel = $line;
		echo "PrototypeTickChannel = <input type=\"text\" name=\"PrototypeTickChannel\" size=\"4\" value=\"".$PrototypeTickChannel."\"><br />";
		}
	if($i == 2) {
		$PrototypeTickVelocity = $line;
		echo "PrototypeTickVelocity = <input type=\"text\" name=\"PrototypeTickVelocity\" size=\"4\" value=\"".$PrototypeTickVelocity."\"><br />";
		}
	if($i == 3) {
		$CsoundInstruments_filename = trim($line);
		$CsoundInstruments_filename = str_replace($csound_resources."/",'',$CsoundInstruments_filename);
		if($CsoundInstruments_filename <> '' AND !is_integer(strpos($CsoundInstruments_filename,"-cs.")) AND !is_integer(strpos($CsoundInstruments_filename,".bpcs")))
			$CsoundInstruments_filename .= ".bpcs";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
		echo "CsoundInstruments filename = <input type=\"text\" name=\"CsoundInstruments_filename\" size=\"20\" value=\"".$CsoundInstruments_filename."\">";
		if($CsoundInstruments_filename <> '') { 
			$CsoundInstruments_file = $dir_csound_resources.$CsoundInstruments_filename;
			if(!file_exists($CsoundInstruments_file)) {
				echo "&nbsp;➡&nbsp;";
				echo "File not found: <input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"createcsoundinstruments\" value=\"CREATE ‘".$CsoundInstruments_filename."’\">";
				}
			}
		echo "<br />";
		}
/*	if($i == 4) {
		$maxsounds = $line;
		echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		} */
	if($line == "TABLE:") break;
	if($line == "DATA:") {
		$comment_on_file = $table[$i+1];
		$comment_on_file = str_ireplace("<HTML>",'',$comment_on_file);
		$comment_on_file = str_ireplace("</HTML>",'',$comment_on_file);
		echo "Comment on this file = <input type=\"text\" name=\"comment_on_file\" size=\"80\" value=\"".$comment_on_file."\"><br />";
		break;
		}
	if(!is_integer($pos=stripos($line,"<HTML>"))) continue;
	else {
		$iobj++;
		$clean_line = str_ireplace("<HTML>",'',$line);
		$clean_line = str_ireplace("</HTML>",'',$clean_line);
		$object_name[$iobj] = trim($clean_line);
		
		$object_da[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".bpda";
		$handle_da = fopen($object_da[$iobj],"w");
		$file_header = $top_header."\n// Data saved as \"".$object_name[$iobj].".bpda\". Date: ".gmdate('Y-m-d H:i:s');
	//	fwrite($handle_da,$file_header."\n");
		fwrite($handle_da,$object_name[$iobj]."\n");
		fclose($handle_da);
		$object_file[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".txt";
		$object_foldername = clean_folder_name($object_name[$iobj]);
		$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
		if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
		if($handle_object) fclose($handle_object);
		$handle_object = fopen($object_file[$iobj],"w");
		$midi_bytes = $save_codes_dir."/midibytes.txt";
		$handle_bytes = fopen($midi_bytes,"w");
		
		$csound_file_this_object = $save_codes_dir."/csound.txt";
		$handle_csound = fopen($csound_file_this_object,"w");
		
		$file_header = $top_header."\n// Data saved as \"".$object_name[$iobj]."\". Date: ".gmdate('Y-m-d H:i:s');
		$file_header .= "\n".$filename;
		fwrite($handle_object,$file_header."\n");
		echo "<input type=\"hidden\" name=\"object_name_".$iobj."\" value=\"".$object_name[$iobj]."\">";
		$j = $i_start_midi = $n = 0; $first = TRUE;
		$has_csound[$iobj] = FALSE;
		do {
			$i++; $line = $table[$i];
			if(is_integer($pos=strpos($line,"_beginCsoundScore_"))) {
				$i++; $line = $table[$i];
				while(!is_integer($pos=strpos($line,"_endCsoundScore_"))) {
					$test_csound = preg_replace("/i[0-9]\s/u","•§§§•",$line);
					if(is_integer($pos=strpos($test_csound,"•§§§•")))
						$has_csound[$iobj] = TRUE;
				//	echo $test_csound."<br />";
					$score_line = str_ireplace("<HTML>",'',$line);
					$score_line = str_ireplace("</HTML>",'',$score_line);
					$score_line = str_ireplace("<BR>","\n",$score_line);
					$score_line = str_ireplace("_beginCsoundScore_","\n",$score_line);
					fwrite($handle_csound,$score_line."\n");
					$i++; $line = $table[$i];
					}
				$i_start_midi = $i;
				}
				
			// We send MIDI codes to separate file"midibytes.txt"
			$number_codes = FALSE;
			if($i_start_midi > 0 AND $i > $i_start_midi AND !is_integer(stripos($line,"<HTML>"))) {
				if($first) {
					$nmax = intval($line);
					$first = FALSE;
				//	echo $object_name[$iobj]." nmax = ".$nmax."<br />";
					$number_codes = TRUE;
					}
				if($n <= $nmax) fwrite($handle_bytes,$line."\n");
				$n++;
				}
			else if(!$number_codes AND !is_integer(strpos($line,"_endCsoundScore_")))
				fwrite($handle_object,$line."\n");
			if(is_integer($pos=stripos($line,"<HTML>"))) break;
			$j++;
			continue;
			}
		while(TRUE);
		$clean_line = str_ireplace("<HTML>",'',$line);
		$clean_line = str_ireplace("</HTML>",'',$clean_line);
		$object_comment[$iobj] = $clean_line;
		fclose($handle_bytes);
		fclose($handle_csound);
		}
	}
$maxsounds = $iobj + 1;
echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		
if($handle_object) fclose($handle_object);
echo "<p style=\"color:blue;\">".$comment_on_file."</p>";
echo "<p style=\"text-align:left;\">";
echo "<input style=\"background-color:yellow; font-size:large;\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’ INCLUDING ALL CHANGES TO PROTOTYPES\"><br />";
echo "<font color=\"red\">➡</font> Changes in prototypes are <font color=\"red\">autosaved</font> if changes occurred every 30 seconds. Keep this page open as long as you are editing sound-object prototypes!</p>";
if($autosave) echo "<script type=\"text/javascript\" src=\"autosaveObjects.js\"></script>";
echo "<p><input style=\"background-color:yellow; font-size:large;\" type=\"submit\" name=\"create_object\" value=\"CREATE A NEW OBJECT\"> named <input type=\"text\" name=\"new_object\" size=\"10\" value=\"\"></p>";
if($deleted_objects <> '') echo "<p><input style=\"background-color:yellow;\" type=\"submit\" name=\"restore\" value=\"RESTORE ALL DELETED OBJECTS\"> = <font color=\"blue\"><big>".$deleted_objects."</big></font></p>";
echo "</form>";

if($CsoundInstruments_filename <> '') {
	$CsoundInstruments_file = $dir_csound_resources.$CsoundInstruments_filename;
	if($CsoundInstruments_filename <> '' AND file_exists($CsoundInstruments_file)) {
		$url_csound_page = "csound.php?file=".urlencode($csound_resources.SLASH.$csound_file);
		echo "<td><form method=\"post\" action=\"".$url_csound_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$csound_file."’\">&nbsp;";
		echo "</td></form>";
		}
	}
else $CsoundInstruments_file = '';

if($iobj >= 0) {
	echo "<hr>";
	echo "<h3 style=\"margin-left:1em;\"><font color=\"red\"><big>↓</big></font>&nbsp;&nbsp;Click any of these ".($iobj + 1)." object prototypes to edit it</h3>";
	$temp_alphabet_file = $temp_dir.$temp_folder.SLASH."temp.bpho";
	$handle = fopen($temp_alphabet_file,"w");
	$file_header = $top_header."\n// Alphabet saved as \"temp.bpho\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$filename."\n");
//	fwrite($handle,"*\n");
	echo "<table style=\"background-color:lightgrey;\">";
	for($i = 0; $i <= $iobj; $i++) {
		echo "<tr><td style=\"padding:4px; vertical-align:middle;\">";
		echo "<form method=\"post\" action=\"prototype.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"object_file\" value=\"".$object_file[$i]."\">";
		echo "<input type=\"hidden\" name=\"prototypes_file\" value=\"".$dir.$filename."\">";
		echo "<input type=\"hidden\" name=\"prototypes_name\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_file\" value=\"".$CsoundInstruments_file."\">";
		echo "<input style=\"background-color:azure; font-size:larger;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"object_name\" value=\"".$object_name[$i]."\">";
		fwrite($handle,$object_name[$i]."\n");
		echo "</form>";
		echo "</td>";
		echo "<td style=\"vertical-align:middle;\">";
		echo $object_comment[$i];
		echo "</td>";
		echo "<td style=\"vertical-align:middle;\">";
		if($has_csound[$i]) echo "Csound";
		echo "</td>";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
		echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickKey\" value=\"".$PrototypeTickKey."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickChannel\" value=\"".$PrototypeTickChannel."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickVelocity\" value=\"".$PrototypeTickVelocity."\">";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
		echo "<input type=\"hidden\" name=\"comment_on_file\" value=\"".$comment_on_file."\">";
		echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		echo "<input type=\"hidden\" name=\"object_name\" value=\"".$object_name[$i]."\">";
		echo "<input style=\"background-color:yellow; \" type=\"submit\" name=\"delete_object\" value=\"DELETE\">";
		echo "</td>";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"rename_object\" value=\"RENAME AS\">: <input type=\"text\" name=\"object_new_name\" size=\"10\" value=\"\">";
		echo "</td>";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"duplicate_object\" value=\"DUPLICATE AS\">: <input type=\"text\" name=\"copy_object\" size=\"10\" value=\"\">";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
		}
	echo "</table>";
	fclose($handle);
	}

display_more_buttons(FALSE,$content,$url_this_page,$dir,$grammar_file,'',$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);

?>
