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
	$file_header = $top_header."\n// Csound resource file saved as \"".$CsoundInstruments_filename."\". Date: ".gmdate('Y-m-d H:i:s')." GMT";
	fwrite($handle,csound_data_to_json($file_header,cs_new_data())); // 2026-11-12 JSON format with default values
	fclose($handle);
	chmod($dir.$CsoundInstruments_filename,$permissions);
	$path = str_replace($bp_application_path,'',$dir);
	$url = "csound.php?file=".urlencode($path.$CsoundInstruments_filename);
	header("Location: ".$url); 
	}

require_once("_header.php");
display_darklight();

$url = "index.php?path=".urlencode($current_directory);
echo "<p>Workspace = <input title=\"List this workspace\" class=\"edit big\" name=\"workspace\" type=\"submit\" onclick=\"window.open('".$url."','_self');\" value=\"".$current_directory."\">";
echo "  <span id='message1' style=\"margin-bottom:1em;\"></span>";
echo "</p>";
echo link_to_help();

echo "<h2>Object prototypes “".$filename."”</h2>";
save_settings("last_name",$filename);

if($test) echo "dir = ".$dir."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
// 2026-11-10 Form actions are ignored if the temporary folder of the page is lost, and the page is rebuilt from the file on disk
if(stale_temp_folder($temp_folder)) {
	stale_page_message($filename);
	$_POST = array();
	}
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	chmod($temp_dir.$temp_folder,$permissions);
	}

if(isset($_POST['create_object'])) {
	$new_object = trim($_POST['new_object']);
	$new_object = str_replace(' ','-',$new_object);
	$new_object = str_replace(SLASH,'-',$new_object);
	$new_object = str_replace('#','-',$new_object);
	$new_object = str_replace('"','',$new_object);
	if($new_object <> '') {
		$new_object_file = $temp_dir.$temp_folder.SLASH.$new_object.".txt";
		$handle = fopen($new_object_file,"w");
		$file_header = $top_header."\n// Object prototype saved as \"".$new_object."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$filename."\n");
		// 2026-11-12 A new prototype gets the default values of so_prototype_fields()
		$new_prototype = so_new_prototype();
		foreach(so_prototype_to_lines($new_prototype) as $line) fwrite($handle,$line."\n");
		fwrite($handle,"<HTML>".recode_tags($new_prototype["Comment"])."</HTML>\n");
		fclose($handle);
		chmod($new_object_file,$permissions);
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
	$copy_object = str_replace('_','-',$copy_object);
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
	else echo "<p><span class=\"red-text\">Cannot create</span> <span class=\"green-text\"><big>“".$copy_object."”</big></span> <span class=\"red-text\">because an object with that name already exists</span></p>";
	}

if(isset($_POST['delete_object'])) {
	$object = $_POST['object_name'];
	echo "<p><span class=\"red-text\">Deleted </span><span class=\"green-text\"><big>“".$object."”</big></span>…</p>";
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
	$new_object = str_replace('_','-',$new_object);
	$new_object = str_replace('"','',$new_object);
	if($new_object <> '') {
		echo "<p><span class=\"red-text\">Renamed </span><span class=\"green-text\"><big>“".$object."”</big></span> as <span class=\"green-text\"><big>“".$new_object."”</big></span>…</p>";
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
	echo "<p><span class=\"red-text\">Restoring: </span>";
	// echo "<span class=\"green-text\">".$object."</span> </p>";
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		echo "<span class=\"green-text\">".str_replace(".txt",'',$thisfile)."</span> ";
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
			echo "<p><span class=\"red-text\">Maximum time (5 seconds) spent waiting for the sound-object prototypes file to be unlocked:</span> <span class=\"green-text\">".$dir.$filename."</span></p>";
			$bad = TRUE;
			break;
			}
		}			
	if(!$bad) {
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saved “".$filename."” file…</span>";
		$result = SaveObjectPrototypes(FALSE,$dir,$filename,$temp_folder,TRUE);
		if($result === "error") echo "<p class=\"red-text\">➡ CANNOT SAVE: the data of a prototype could not be converted (see the PHP error log). The file was not changed.</p>";
		}
	}

try_create_new_file($this_file,$filename);
// 2026-11-12 Sound-object prototypes are stored in JSON format. A file in the former line format is converted when it is opened (the original is copied to the trash as NAME_old_format)
$converted = convert_prototypes_to_json($this_file,$dir_trash_folder);
if($converted === TRUE) echo "<p class=\"red-text\">➡ This file has been converted to JSON format. The original is in the trash as ‘".$filename."_old_format’</p>";
else if($converted !== FALSE) {
	echo "<p class=\"red-text\">➡ ".$converted."</p>";
	die();
	}
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
if(strlen(trim($content)) == 0) $content = so_data_to_json($top_header,so_new_data()); // 2026-11-12 An empty file gets default values
$objects_file = $grammar_file = $csound_file = $tonality_file = $alphabet_file = $settings_file = $orchestra_file = $interaction_file = $midisetup_file = $timebase_file = $keyboard_file = $glossary_file = '';
$prototypes_data = so_read_resource($content);
if(!is_array($prototypes_data)) {
	echo "<p class=\"red-text\">➡ ".$prototypes_data."</p>";
	die();
	}
echo "<p class=\"green-text\">".str_replace("\n","<br />",cs_get($prototypes_data,'header',''))."</p>";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
// Values are read by their keys. To add a value, also save it in so_data_to_json() (_basic_tasks.php)
$PrototypeTickKey = intval(cs_get($prototypes_data,"PrototypeTickKey",84));
echo "PrototypeTickKey = <input type=\"text\" name=\"PrototypeTickKey\" size=\"4\" value=\"".$PrototypeTickKey."\"><br />";
$PrototypeTickChannel = intval(cs_get($prototypes_data,"PrototypeTickChannel",1));
echo "PrototypeTickChannel = <input type=\"text\" name=\"PrototypeTickChannel\" size=\"4\" value=\"".$PrototypeTickChannel."\"><br />";
$PrototypeTickVelocity = intval(cs_get($prototypes_data,"PrototypeTickVelocity",64));
echo "PrototypeTickVelocity = <input type=\"text\" name=\"PrototypeTickVelocity\" size=\"4\" value=\"".$PrototypeTickVelocity."\"><br />";
$CsoundInstruments_filename = trim(cs_get($prototypes_data,"CsoundInstrumentsFile",''));
$CsoundInstruments_filename = str_replace($csound_resources."/",'',$CsoundInstruments_filename);
if($CsoundInstruments_filename <> '' AND !is_integer(strpos($CsoundInstruments_filename,"-cs.")) AND !is_integer(strpos($CsoundInstruments_filename,".bpcs")))
	$CsoundInstruments_filename .= ".bpcs";
echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
echo "CsoundInstruments filename = <input type=\"text\" name=\"CsoundInstruments_filename\" size=\"20\" value=\"".$CsoundInstruments_filename."\">";
if($CsoundInstruments_filename <> '') { 
	$CsoundInstruments_file = $dir_csound_resources.$CsoundInstruments_filename;
	if(!file_exists($CsoundInstruments_file)) {
		echo "&nbsp;➡&nbsp;";
		echo "File not found: <input class=\"save\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"createcsoundinstruments\" value=\"CREATE ‘".$CsoundInstruments_filename."’\">";
		}
	}
echo "<br />";
$comment_on_file = recode_tags(cs_get($prototypes_data,"PrototypesComment",''));
echo "Comment on this file = <input type=\"text\" name=\"comment_on_file\" size=\"80\" value=\"".$comment_on_file."\"><br />";

// Each prototype is copied to temporary files edited by prototype.php: NAME.txt in the former line format (see so_temp_lines()), NAME_codes/midibytes.txt and NAME_codes/csound.txt
$iobj = -1;
foreach(cs_get($prototypes_data,'Prototypes',array()) as $p) {
	$name = trim(cs_get($p,"Name",''));
	if($name == '') continue;
	$iobj++;
	$object_name[$iobj] = $name;
	$object_da[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".bpda";
	$handle_da = fopen($object_da[$iobj],"w");
	fwrite($handle_da,$object_name[$iobj]."\n");
	fclose($handle_da);
	chmod($object_da[$iobj],$permissions);
	$object_file[$iobj] = $temp_dir.$temp_folder.SLASH.$object_name[$iobj].".txt";
	$object_foldername = clean_folder_name($object_name[$iobj]);
	$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
	if(!is_dir($save_codes_dir)) {
		mkdir($save_codes_dir);
		chmod($save_codes_dir,$permissions);
		}
	$handle_object = fopen($object_file[$iobj],"w");
	$file_header = $top_header."\n// Data saved as \"".$object_name[$iobj]."\". Date: ".gmdate('Y-m-d H:i:s');
	$file_header .= "\n".$filename;
	fwrite($handle_object,$file_header."\n");
	foreach(so_prototype_to_lines($p) as $line) fwrite($handle_object,$line."\n");
	$object_comment[$iobj] = recode_tags(cs_get($p,"Comment",''));
	fwrite($handle_object,"<HTML>".$object_comment[$iobj]."</HTML>\n");
	fclose($handle_object);
	echo "<input type=\"hidden\" name=\"object_name_".$iobj."\" value=\"".$object_name[$iobj]."\">";
	// MIDI codes are sent to a separate file "midibytes.txt"
	$codes = cs_get($p,'MIDIcodes',array());
	$midi_bytes = $save_codes_dir."/midibytes.txt";
	file_put_contents($midi_bytes,so_codes_to_text($codes));
	chmod($midi_bytes,$permissions);
	$has_midi[$iobj] = (count($codes) > 0);
	$csound_score = so_score_to_text(cs_get($p,"Csound score",''));
	$csound_file_this_object = $save_codes_dir."/csound.txt";
	file_put_contents($csound_file_this_object,$csound_score."\n");
	chmod($csound_file_this_object,$permissions);
	$has_csound[$iobj] = (preg_match("/i[0-9]\s/u",$csound_score) == 1);
	}
$maxsounds = $iobj + 1;
echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		
echo "<p class=\"green-text\">".$comment_on_file."</p>";
echo "<p style=\"text-align:left;\">";
echo "<input class=\"save big\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’ INCLUDING ALL CHANGES TO PROTOTYPES\"><br />";
echo "<span class=\"red-text\">➡</span> Changes in prototypes are <span class=\"red-text\">autosaved</span> every 30 seconds if changes occurred.<br />Keep this page open as long as you are editing sound-object prototypes!</p>";
if($autosave) echo "<script type=\"text/javascript\" src=\"autosaveObjects.js\"></script>";
echo "<p><input class=\"save\" type=\"submit\" name=\"create_object\" value=\"CREATE A NEW OBJECT\"> named <input type=\"text\" name=\"new_object\" size=\"20\" value=\"\"></p>";
if($deleted_objects <> '') echo "<p><input class=\"save\" type=\"submit\" name=\"restore\" value=\"RESTORE ALL DELETED OBJECTS\"> = <span class=\"green-text\"><big>".$deleted_objects."</big></span></p>";
echo "</form>";

if($CsoundInstruments_filename <> '') {
	$CsoundInstruments_file = $dir_csound_resources.$CsoundInstruments_filename;
	if($CsoundInstruments_filename <> '' AND file_exists($CsoundInstruments_file)) {
		$url_csound_page = "csound.php?file=".urlencode($csound_resources.SLASH.$CsoundInstruments_file);
		echo "<td><form method=\"post\" action=\"".$url_csound_page."\" enctype=\"multipart/form-data\">";
		echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$CsoundInstruments_file."’\">&nbsp;";
		echo "</td></form>";
		}
	}
else $CsoundInstruments_file = '';

if($iobj >= 0) {
//	echo "<hr>";
	echo "<h3 style=\"margin-left:1em;\"><span class=\"red-text\"><big>↓</big></span>&nbsp;&nbsp;Click any of these ".($iobj + 1)." object prototypes to edit it</h3>";
	$temp_alphabet_file = $temp_dir.$temp_folder.SLASH."temp.bpho";
	$handle = fopen($temp_alphabet_file,"w");
	$file_header = $top_header."\n// Alphabet saved as \"temp.bpho\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$filename."\n");
//	fwrite($handle,"*\n");
	echo "<table class=\"thicktable\">";
	for($i = 0; $i <= $iobj; $i++) {
		echo "<tr>";
		echo "<form method=\"post\" action=\"prototype.php?temp_folder=".urlencode($temp_folder)."&object_file=".urlencode($object_file[$i])."&prototypes_file=".urlencode($dir.$filename)."&prototypes_name=".urlencode($filename)."&CsoundInstruments_file=".urlencode($CsoundInstruments_file)."&object_name=".urlencode($object_name[$i])."\" enctype=\"multipart/form-data\">";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"object_file\" value=\"".$object_file[$i]."\">";
		echo "<input type=\"hidden\" name=\"prototypes_file\" value=\"".$dir.$filename."\">";
		echo "<input type=\"hidden\" name=\"prototypes_name\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_file\" value=\"".$CsoundInstruments_file."\">";
		echo "<input class=\"edit big\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"object_name\" value=\"".$object_name[$i]."\">";
		fwrite($handle,$object_name[$i]."\n");
		echo "</td>";
		echo "</form>";
		echo "<td style=\"vertical-align:middle;\">";
		echo $object_comment[$i];
		echo "</td>";
		echo "<td style=\"vertical-align:middle;\">";
		if($has_csound[$i]) echo "Csound";
		if($has_midi[$i]) {
			if($has_csound[$i]) echo "<br />";
			echo "MIDI";
			}
		echo "</td>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
		echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickKey\" value=\"".$PrototypeTickKey."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickChannel\" value=\"".$PrototypeTickChannel."\">";
		echo "<input type=\"hidden\" name=\"PrototypeTickVelocity\" value=\"".$PrototypeTickVelocity."\">";
		echo "<input type=\"hidden\" name=\"CsoundInstruments_filename\" value=\"".$CsoundInstruments_filename."\">";
		echo "<input type=\"hidden\" name=\"comment_on_file\" value=\"".$comment_on_file."\">";
		echo "<input type=\"hidden\" name=\"maxsounds\" value=\"".$maxsounds."\">";
		echo "<input type=\"hidden\" name=\"object_name\" value=\"".$object_name[$i]."\">";
		echo "<input class=\"save\" type=\"submit\" name=\"delete_object\" value=\"DELETE\">";
		echo "</td>";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input class=\"edit\" type=\"submit\" name=\"rename_object\" value=\"RENAME AS\">: <input type=\"text\" name=\"object_new_name\" size=\"15\" value=\"\">";
		echo "</td>";
		echo "<td style=\"padding:4px; vertical-align:middle;\">";
		echo "<input class=\"edit\" type=\"submit\" name=\"duplicate_object\" value=\"DUPLICATE AS\">: <input type=\"text\" name=\"copy_object\" size=\"15\" value=\"\">";
		echo "</td>";
		echo "</tr>";
		echo "</form>";
		}
	echo "</table>";
	fclose($handle);
	chmod($temp_alphabet_file,$permissions);
	}
$data_file = $weights_file = '';
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
display_more_buttons(FALSE,$content,$url_this_page,$dir,$grammar_file,'',$csound_file,$tonality_file,$alphabet_file,$data_file,$weights_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file);
echo "</form>";

?>
