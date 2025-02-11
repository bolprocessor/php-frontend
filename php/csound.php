<?php
require_once("_basic_tasks.php");
$autosave = TRUE;
// $autosave = FALSE;
$verbose = TRUE;
$verbose = FALSE;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "csound.php?file=".urlencode($file);
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
display_darklight();

$url = "index.php?path=".urlencode($current_directory);
echo "<p>Workspace = <input title=\"List this workspace\" class=\"edit\" name=\"workspace\" type=\"submit\" onclick=\"window.open('".$url."','_self');\" value=\"".$current_directory."\">";
echo "</a>   <span id='message2' style=\"margin-bottom:1em;\"></span>";
echo "</p>";
echo link_to_help();

echo "<h2>Csound resource “".$filename."”</h2>";
save_settings("last_name",$filename);

if($test) echo "dir = ".$dir."<br />";

$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
if(!file_exists($temp_dir.$temp_folder)) {
	mkdir($temp_dir.$temp_folder);
	}
if(!file_exists($temp_dir.$temp_folder.SLASH."scales")) {
	mkdir($temp_dir.$temp_folder.SLASH."scales");
	}
$dir_scales = $temp_dir.$temp_folder.SLASH."scales".SLASH;
$need_to_save = FALSE;

if(isset($_POST['max_scales'])) $max_scales = $_POST['max_scales'];
else $max_scales = 0;

if(isset($_POST['delete_instrument'])) {
	$instrument = $_POST['instrument_name'];
	$number_channels = $_POST['number_channels'];
	echo "<p><span class=\"red-text\">Deleted </span><span class=\"green-text\"><big>“".$instrument."”</big></span>…</p>";
	$this_instrument_file = $temp_dir.$temp_folder.SLASH.$instrument.".txt";
	rename($this_instrument_file,$this_instrument_file.".old");
	$number_instruments = $_POST['number_instruments'] - 1;
	$_POST['number_instruments'] = $number_instruments;
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

$index_max = 0;
$deleted_instruments = '';
$exists_name = array();
$dircontent = scandir($temp_dir.$temp_folder);
foreach($dircontent as $oldfile) {
	if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
	$table = explode(".",$oldfile);
	$extension = end($table);
	if($extension == "old" OR $extension == "txt") {
		$content = @file_get_contents($temp_dir.$temp_folder.SLASH.$oldfile);
		$table = explode(chr(10),$content);
		$index_this_instrument = $table[5];
		if($index_this_instrument > $index_max) $index_max = $index_this_instrument;
		}
	$thisfile = str_replace(".old",'',$oldfile);
	$this_instrument = str_replace(".txt",'',$thisfile);
	$exists_name[$this_instrument] = TRUE;
	if($extension == "old")
		$deleted_instruments .= "“".$this_instrument."” ";
	}

if(isset($_POST['create_instrument'])) {
	$new_index = $_POST['index_max'] + 1;
	$new_instrument = trim($_POST['new_instrument']);
	$new_instrument = str_replace(' ','_',$new_instrument);
	$new_instrument = str_replace('"','',$new_instrument);
	if(isset($exists_name[$new_instrument])) {
		echo "<p><span class=\"red-text\">An instrument named “<span class=\"green-text\">".$new_instrument."</span>” already exists!</span></p>";
		}
	else if($new_instrument <> '') {
		$new_instrument_file = $temp_dir.$temp_folder.SLASH.$new_instrument.".txt";
		$template = "instrument_template";
		$template_content = @file_get_contents($template);
		$template_content = str_replace("[[index]]",$new_index,$template_content);
		$handle = fopen($new_instrument_file,"w");
		$file_header = $top_header."\n// Object prototype saved as \"".$new_instrument."\". Date: ".gmdate('Y-m-d H:i:s');
		fwrite($handle,$file_header."\n");
		fwrite($handle,$filename."\n");
		fwrite($handle,$template_content."\n");
		fclose($handle);
		$number_instruments = $_POST['number_instruments'] + 1;
		$_POST['number_instruments'] = $number_instruments;
		}
	}

if(isset($_POST['duplicate_instrument'])) {
	$instrument = $_POST['instrument_name'];
	$copy_instrument = trim($_POST['copy_instrument']);
	$copy_instrument = str_replace(' ','_',$copy_instrument);
	$copy_instrument = str_replace('"','',$copy_instrument);
	if($copy_instrument <> '') {
		if(isset($exists_name[$copy_instrument])) {
			echo "<p><span class=\"red-text\">Cannot create</span> <span class=\"green-text\"><big>“".$copy_instrument."”</big></span> <span class=\"red-text\">because an instrument with the same name already exists</span></p>";
			}
		else {
			$file_lock = $dir.$filename."_lock2";
			$handle_lock = fopen($file_lock,"w");
			fwrite($handle_lock,"lock\n");
			fclose($handle_lock);
			$copy_instrument_file = $temp_dir.$temp_folder.SLASH.$copy_instrument.".txt";
			$this_instrument_file = $temp_dir.$temp_folder.SLASH.$instrument.".txt";
			$this_instrument_dir = $temp_dir.$temp_folder.SLASH.$instrument;
			$copy_instrument_dir = $temp_dir.$temp_folder.SLASH.$copy_instrument;
			@unlink($temp_dir.$temp_folder.SLASH.$copy_instrument.".txt.old");
			$number_instruments = $_POST['number_instruments'];
			$new_index = $number_instruments + 1;
			$number_instruments++;
			$_POST['number_instruments'] = $number_instruments;
			$content = @file_get_contents($this_instrument_file);
		//	echo "this_instrument_file = ".$this_instrument_file."<br />";
		//	echo "copy_instrument_file = ".$copy_instrument_file."<br />";
			rcopy($this_instrument_dir,$copy_instrument_dir);
			$table = explode(chr(10),$content);
			$im = count($table);
			$handle = fopen($copy_instrument_file,"w");
			for($i = 0; $i < $im; $i++) {
				$line = trim($table[$i]);
				if($i == 1) $line = preg_replace("/\".+\"/u","'".$copy_instrument."'",$line);
				if($i == 5) $line = $new_index;
				fwrite($handle,$line."\n");
				}
		//	echo "this_instrument_dir = ".$this_instrument_dir."<br />";
	/*		if(is_dir($this_instrument_dir)) {
				echo "this_instrument_dir = ".$this_instrument_dir."<br />";
				$files = scandir($this_instrument_dir);
				$files = array_diff($files,array('.','..'));
				foreach($files as $parameter_file) {
					if(pathinfo($file,PATHINFO_EXTENSION) === 'txt') {
						echo "parameter_file = ".$parameter_file."<br />";
					//	$parameter = str_replace(".txt",'',$parameter_file);
						$content_parameter = @file_get_contents($parameter_file);
						echo $content_parameter."<br />";
						$table_parameter = explode(chr(10),$content_parameter);
						$im = count($table_parameter);
						for($i = 0; $i < $im; $i++) {
							$line = trim($table_parameter[$i]);
							fwrite($handle,$line."\n");
							}
						}
					}
				} */
			fclose($handle);
			unlink($file_lock);
			$need_to_save = TRUE;
			}
		}
	}

$lock2 = $dir.$filename."_lock2";
if($need_to_save OR isset($_POST['savealldata']) OR isset($_POST['delete_instrument']) OR isset($_POST['restore']) OR isset($_POST['create_instrument'])) {
		echo "<p id=\"timespan\"><span class=\"red-text\">Saving file:</span> <span class=\"green-text\">".$filename."</span></p>";
		$warn_not_empty = SaveCsoundInstruments(FALSE,$dir,$filename,$temp_dir.$temp_folder,TRUE);
		// We also save tonality data separately
/*		$tonality_filename = str_replace("-cs.","-to.",$filename);
		$tonality_filename = str_replace(".bpcs",".bpto",$tonality_filename);
		$dir_tonality = $dir_tonality_resources;
		$temp_tonality_folder = str_replace(' ','_',$tonality_filename)."_".my_session_id()."_temp";
		if(!file_exists($temp_dir.$temp_tonality_folder)) {
			mkdir($temp_dir.$temp_tonality_folder);
			}
		if(!file_exists($temp_dir.$temp_tonality_folder.SLASH."scales")) {
			mkdir($temp_dir.$temp_tonality_folder.SLASH."scales");
			}
		$dir_tonality_scales = $temp_dir.$temp_tonality_folder.SLASH."scales".SLASH;
		copyDirectory($dir_scales,$dir_tonality_scales);
		SaveTonality(FALSE,$dir_tonality,$tonality_filename,$temp_dir.$temp_folder,TRUE);
		} */
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
if(strlen(trim($content)) == 0) {
	$template = "csound_template";
	$content = @file_get_contents($template);
	}
$extract_data = extract_data(FALSE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
$content = $extract_data['content'];

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save big\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" value=\"SAVE ‘".$filename."’\"></p>";

if($autosave) {
	echo "<p><span class=\"red-text\">➡</span> All data is <span class=\"red-text\">autosaved</span> every 30 seconds if changes occurred.<br />Keep this page open as long as you are editing instruments or scales!</p>";
	echo "<script type=\"text/javascript\" src=\"autosaveInstruments.js\"></script>";
	}

echo "<input type=\"hidden\" name=\"csound_source\" value=\"".$filename."\">";
// echo "<input type=\"hidden\" name=\"duplicated_scale\" value=\"".$duplicated_scale."\">";

$tonality_filename = str_replace("-cs.","-to.",$filename);
$tonality_filename = str_replace(".bpcs",".bpto",$tonality_filename);

$content_no_br = str_replace("<br>",chr(10),$content);
$table = explode(chr(10),$content_no_br);
$imax_file = count($table);
if($verbose) echo "imax_file = ".$imax_file."<br />";
$number_channels = $table[0];
if($verbose) echo "number_channels = ".$number_channels."<br />";
echo "<input type=\"hidden\" name=\"number_channels\" value=\"".$number_channels."\">";

for($j = 0; $j < $number_channels; $j++) {
	$which = $table[$j + 1];
	$ch = $j + 1;
	$whichCsoundInstrument[$j] = $which;
	if($verbose) echo "MIDI channel #".$ch." => instrument [index ".$whichCsoundInstrument[$j]."]<br />";
	}
$CsoundOrchestraName = preg_replace("/<\/?html>/u",'',$table[++$j]);
if($CsoundOrchestraName == '') {
	$CsoundOrchestraName = "default.orc";
	$warn_not_empty = TRUE;
	}
echo "Csound orchestra file = <input type=\"text\" name=\"CsoundOrchestraName\" size=\"30\" value=\"".$CsoundOrchestraName."\">";
$link = "file_list.php?dir=".urlencode($dir_csound_resources)."&extension=orc";
echo "<input class=\"save\" type=\"submit\" name=\"savealldata\" onclick=\"this.form.target='_self';return true;\" value=\"SAVE\">";
echo " <input class=\"edit\" onclick=\"window.open('".$link."','listorchestra','width=200,height=300,left=100'); return false;\" type=\"submit\" name=\"produce\" value=\"which one?\">";
	echo " ➡ ";
if($warn_not_empty)
	echo "<span class=\"red-text\">WARNING: this field should’nt be empty. By default it has been set to ‘default.orc’. </span>";
$orchestra_filename = $dir_csound_resources.$CsoundOrchestraName;
if(file_exists($dir.$CsoundOrchestraName)) {
	rename($dir.$CsoundOrchestraName,$orchestra_filename);
	sleep(1);
	}
$path = str_replace($bp_application_path,'',$dir_csound_resources);
if(file_exists($orchestra_filename)) {
	echo "<a class=\"linkdotted\" target=\"_blank\" href=\"csorchestra.php?file=".urlencode($path.$CsoundOrchestraName)."\">Edit this file</a>";
	}
else {
	echo "File not found: <a target=\"_blank\" href=\"csorchestra.php?file=".urlencode($path.$CsoundOrchestraName)."\">create it!</a>";
	}
echo "<br />";
$number_instruments = $table[++$j];
if($verbose) echo "number_instruments = ".$number_instruments."<br />";
echo "<input type=\"hidden\" name=\"number_instruments\" value=\"".$number_instruments."\">";
$i = $j;
$handle_instrument = FALSE;
$name_index = $index_name = array();
for($j = 0; $j < $number_instruments; $j++) {
	$CsoundInstrumentName[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
	$CsoundInstrumentName[$j] = str_replace(' ','_',$CsoundInstrumentName[$j]);
	if($verbose) echo "<br /><b>Instrument name = “".$CsoundInstrumentName[$j]."”</b><br />";
	// Create temporary file and folder for this instrument
	$filename_this_instrument = $CsoundInstrumentName[$j];
	$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$filename_this_instrument;
	if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
	$instrument_file[$j] = $folder_this_instrument.".txt";
	$argmax_file = $folder_this_instrument.SLASH."argmax.php";
	if($handle_instrument) fclose($handle_instrument);
	$handle_instrument = fopen($instrument_file[$j],"w");
	$file_header = $top_header."\n// Csound resources (instruments and scales) saved as \"".$CsoundInstrumentName[$j]."\". Date: ".gmdate('Y-m-d H:i:s');
	$file_header .= "\n".$filename;
	fwrite($handle_instrument,$file_header."\n");
	$InstrumentComment[$j] = preg_replace("/<\/?html>/u",'',$table[++$i]);
	if($verbose) echo "Instrument comment = “".$InstrumentComment[$j]."”<br />";
	fwrite($handle_instrument,$InstrumentComment[$j]."\n");
	$argmax[$j] = $table[++$i];
	$argmax_file = $folder_this_instrument.SLASH."argmax.php";
	if(!file_exists($argmax_file)) {
		$handle = fopen($argmax_file,"w");
		$text = "<xxxphp\n";
		$text .= "yyylast_argument[\"".$CsoundInstrumentName[$j]."\"] = ".$argmax[$j].";\n";
		$text .= "xxx>\n";
		$text = str_replace("xxx","?",$text);
		$text = str_replace("yyy","$",$text);
		fwrite($handle,$text);
		fclose($handle);
		}
	else {
		$instrument_argmax = max_argument($argmax_file);
		/* if($instrument_argmax > $argmax[$j]) */ $argmax[$j] = $instrument_argmax;
		}
	if($verbose) echo "argmax = ".$argmax[$j]."<br />";
	fwrite($handle_instrument,$argmax[$j]."\n");
	$argmax[$j] = 0;
	$InstrumentIndex[$j] = $table[++$i];
	if($InstrumentIndex[$j] > $index_max) $index_max = $InstrumentIndex[$j];
	$name_index[$CsoundInstrumentName[$j]] = $InstrumentIndex[$j];
	$index_name[$InstrumentIndex[$j]] = $CsoundInstrumentName[$j];
	if($verbose) echo "Instrument index = ".$InstrumentIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentIndex[$j]."\n");
	$InstrumentDilationRatioIndex[$j] = $table[++$i];
	if($InstrumentDilationRatioIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentDilationRatioIndex[$j];
	if($verbose) echo "Instrument dilation ratio argument = ".$InstrumentDilationRatioIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentDilationRatioIndex[$j]."\n");
	$InstrumentAttackVelocityIndex[$j] = $table[++$i];
	if($InstrumentAttackVelocityIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentAttackVelocityIndex[$j];
	if($verbose) echo "Instrument attack velocity argument = ".$InstrumentAttackVelocityIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentAttackVelocityIndex[$j]."\n");
	$InstrumentReleaseVelocityIndex[$j] = $table[++$i];
	if($InstrumentReleaseVelocityIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentReleaseVelocityIndex[$j];
	if($verbose) echo "Instrument release velocity argument = ".$InstrumentReleaseVelocityIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentReleaseVelocityIndex[$j]."\n");
	$InstrumentPitchIndex[$j] = $table[++$i];
	if($InstrumentPitchIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchIndex[$j];
	if($verbose) echo "Instrument pitch argument = ".$InstrumentPitchIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchIndex[$j]."\n");
	$InstrumentPitchFormat[$j] = $table[++$i];
	if($verbose) echo "Instrument pitch format = ".$InstrumentPitchFormat[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchFormat[$j]."\n");
	$InstrumentPitchBendRange[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend range = ".$InstrumentPitchBendRange[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendRange[$j]."\n");
	$InstrumentPitchBendIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend islogx = ".$InstrumentPitchBendIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendIsLogX[$j]."\n");
	$InstrumentPitchBendIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument pichbend islogy = ".$InstrumentPitchBendIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchBendIsLogY[$j]."\n");
	$InstrumentVolumeIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument volume islogx = ".$InstrumentVolumeIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeIsLogX[$j]."\n");
	$InstrumentVolumeIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument volume islogy = ".$InstrumentVolumeIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeIsLogY[$j]."\n");
	$InstrumentPressureIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument pressure islogx = ".$InstrumentPressureIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureIsLogX[$j]."\n");
	$InstrumentPressureIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument pressure islogy = ".$InstrumentPressureIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureIsLogY[$j]."\n");
	$InstrumentModulationIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument modulation islogx = ".$InstrumentModulationIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationIsLogX[$j]."\n");
	$InstrumentModulationIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument modulation islogy = ".$InstrumentModulationIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationIsLogY[$j]."\n");
	$InstrumentPanoramicIsLogX[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramic islogx = ".$InstrumentPanoramicIsLogX[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicIsLogX[$j]."\n");
	$InstrumentPanoramicIsLogY[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramic islogy = ".$InstrumentPanoramicIsLogY[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicIsLogY[$j]."\n");
	$InstrumentPitchbendStartIndex[$j] = $table[++$i];
	if($InstrumentPitchbendStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendStartIndex[$j];
	if($verbose) echo "Instrument pitchbend start argument = ".$InstrumentPitchbendStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendStartIndex[$j]."\n");
	$InstrumentVolumeStartIndex[$j] = $table[++$i];
	if($InstrumentVolumeStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeStartIndex[$j];
	if($verbose) echo "Instrument volume start argument = ".$InstrumentVolumeStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeStartIndex[$j]."\n");
	$InstrumentPressureStartIndex[$j] = $table[++$i];
	if($InstrumentPressureStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureStartIndex[$j];
	if($verbose) echo "Instrument pressure start argument = ".$InstrumentPressureStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureStartIndex[$j]."\n");
	$InstrumentModulationStartIndex[$j] = $table[++$i];
	if($InstrumentModulationStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationStartIndex[$j];
	if($verbose) echo "Instrument modulation start argument = ".$InstrumentModulationStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationStartIndex[$j]."\n");
	$InstrumentPanoramicStartIndex[$j] = $table[++$i];
	if($InstrumentPanoramicStartIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicStartIndex[$j];
	if($verbose) echo "Instrument panoramic start argument = ".$InstrumentPanoramicStartIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicStartIndex[$j]."\n");
	$InstrumentPitchbendEndIndex[$j] = $table[++$i];
	if($InstrumentPitchbendEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendEndIndex[$j];
	if($verbose) echo "Instrument pitchbend end argument = ".$InstrumentPitchbendEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendEndIndex[$j]."\n");
	$InstrumentVolumeEndIndex[$j] = $table[++$i];
	if($InstrumentVolumeEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeEndIndex[$j];
	if($verbose) echo "Instrument volume end argument = ".$InstrumentVolumeEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeEndIndex[$j]."\n");
	$InstrumentPressureEndIndex[$j] = $table[++$i];
	if($InstrumentPressureEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureEndIndex[$j];
	if($verbose) echo "Instrument pressure end argument = ".$InstrumentPressureEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureEndIndex[$j]."\n");
	$InstrumentModulationEndIndex[$j] = $table[++$i];
	if($InstrumentModulationEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationEndIndex[$j];
	if($verbose) echo "Instrument modulation end argument = ".$InstrumentModulationEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationEndIndex[$j]."\n");
	$InstrumentPanoramicEndIndex[$j] = $table[++$i];
	if($InstrumentPanoramicEndIndex[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicEndIndex[$j];
	if($verbose) echo "Instrument panoramic end argument = ".$InstrumentPanoramicEndIndex[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicEndIndex[$j]."\n");
	$InstrumentPitchbendTable[$j] = $table[++$i];
	if($InstrumentPitchbendTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPitchbendTable[$j];
	if($verbose) echo "Instrument pitchbend table = ".$InstrumentPitchbendTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendTable[$j]."\n");
	$InstrumentVolumeTable[$j] = $table[++$i];
	if($InstrumentVolumeTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentVolumeTable[$j];
	if($verbose) echo "Instrument volume table = ".$InstrumentVolumeTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeTable[$j]."\n");
	$InstrumentPressureTable[$j] = $table[++$i];
	if($InstrumentPressureTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPressureTable[$j];
	if($verbose) echo "Instrument pressure table = ".$InstrumentPressureTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureTable[$j]."\n");
	$InstrumentModulationTable[$j] = $table[++$i];
	if($InstrumentModulationTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentModulationTable[$j];
	if($verbose) echo "Instrument modulation table = ".$InstrumentModulationTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationTable[$j]."\n");
	$InstrumentPanoramicTable[$j] = $table[++$i];
	if($InstrumentPanoramicTable[$j] > $argmax[$j]) $argmax[$j] = $InstrumentPanoramicTable[$j];
	if($verbose) echo "Instrument panoramic table = ".$InstrumentPanoramicTable[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicTable[$j]."\n");
	$InstrumentPitchbendGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument pitchbendGEN = ".$InstrumentPitchbendGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPitchbendGEN[$j]."\n");
	$InstrumentVolumeGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument volumeGEN = ".$InstrumentVolumeGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentVolumeGEN[$j]."\n");
	$InstrumentPressureGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument pressureGEN = ".$InstrumentPressureGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPressureGEN[$j]."\n");
	$InstrumentModulationGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument modulationGEN = ".$InstrumentModulationGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentModulationGEN[$j]."\n");
	$InstrumentPanoramicGEN[$j] = $table[++$i];
	if($verbose) echo "Instrument panoramicGEN = ".$InstrumentPanoramicGEN[$j]."<br />";
	fwrite($handle_instrument,$InstrumentPanoramicGEN[$j]."\n");
	for($ii = 0; $ii < $number_midi_parameters_csound_instrument; $ii++) {
		$InstrumentPitchbend[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Pitchbend[".$ii."] = ".$InstrumentPitchbend[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPitchbend[$j][$ii]."\n");
		$InstrumentVolume[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Volume[".$ii."] = ".$InstrumentVolume[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentVolume[$j][$ii]."\n");
		$InstrumentPressure[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Pressure[".$ii."] = ".$InstrumentPressure[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPressure[$j][$ii]."\n");
		$InstrumentModulation[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Modulation[".$ii."] = ".$InstrumentModulation[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentModulation[$j][$ii]."\n");
		$InstrumentPanoramic[$j][$ii] = $table[++$i];
		if($verbose) echo "Instrument Panoramic[".$ii."] = ".$InstrumentPanoramic[$j][$ii]."<br />";
		fwrite($handle_instrument,$InstrumentPanoramic[$j][$ii]."\n");
		}
	set_argmax_argument($argmax_file,$CsoundInstrumentName[$j],$argmax[$j]);
	
	// Line 655
	$Instrument_ipmax = $table[++$i];
	if($verbose) echo "Instrument ipmax = ".$Instrument_ipmax."<br />";
	for($ip = 0; $ip < $Instrument_ipmax; $ip++) {
		$empty_parameter = FALSE;
		// $folder_this_instrument
		$Instrument_paramlist_name = preg_replace("/<\/?html>/u",'',$table[++$i]);
		$Instrument_paramlist_name = str_replace(' ','_',$Instrument_paramlist_name);
		$handle_parameter = NULL;
		// Old ‘-cs’ files contain up to 6 parameters with empty names. We'll ignore them.
		$argmax_parameter = 0;
		if($Instrument_paramlist_name == '') $empty_parameter = TRUE;
		else {
			$filename_this_parameter = $Instrument_paramlist_name;
			$parameter_file = $folder_this_instrument."/".$filename_this_parameter.".txt";
			$handle_parameter = fopen($parameter_file,"w");
			}
		if($verbose) echo "<br /><span class=\"darkblue-text\">Instrument paramlist name = “".$Instrument_paramlist_name."”<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_name."\n");
		// Line 687
		$Instrument_paramlist_comment = preg_replace("/<\/?html>/u",'',$table[++$i]);
		if($verbose) echo "Instrument paramlist comment = “".$Instrument_paramlist_comment."”</span><br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_comment."\n");
		$Instrument_paramlist_startindex = $table[++$i];
		if($Instrument_paramlist_startindex > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_startindex;
		if($verbose) echo "start argument = ".$Instrument_paramlist_startindex."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_startindex."\n");
		$Instrument_paramlist_endindex = $table[++$i];
		if($Instrument_paramlist_endindex > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_endindex;
		if($verbose) echo "end argument = ".$Instrument_paramlist_endindex."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_endindex."\n");
		// line 701
		$Instrument_paramlist_table = $table[++$i];
		if($Instrument_paramlist_table > $argmax_parameter) $argmax_parameter = $Instrument_paramlist_table;
		if($verbose) echo "table argument = ".$Instrument_paramlist_table."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_table."\n");
		$Instrument_paramlist_defaultvalue = $table[++$i];
		if($verbose) echo "default value = ".$Instrument_paramlist_defaultvalue."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_defaultvalue."\n");
		$Instrument_paramlist_GENtype = $table[++$i];
		if($verbose) echo "GEN type = ".$Instrument_paramlist_GENtype."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_GENtype."\n");
		$Instrument_paramlist_combinationtype = $table[++$i];
		if($verbose) echo "combination type = ".$Instrument_paramlist_combinationtype."<br />";
		if(!$empty_parameter) fwrite($handle_parameter,$Instrument_paramlist_combinationtype."\n");
		if(!$empty_parameter) {
			fclose($handle_parameter);
			set_argmax_argument($argmax_file,$Instrument_paramlist_name,$argmax_parameter);
			}
		}
	// line 724
	}
if($handle_instrument) fclose($handle_instrument);
$begin_tables = $table[++$i];
if($verbose) echo "<br /><b>begin tables = “".$begin_tables."”</b><br />";
echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
echo "<input type=\"hidden\" name=\"index_max\" value=\"".$index_max."\">";
echo "<table><tr><td>";
echo "<h3>Tables</h3>";
echo "<p><i>These will be put on top of Csound scores</i></p>";
echo "<textarea name=\"cstables\" rows=\"5\" style=\"width:400px; background-color:cornsilk; color:black;\">";
$cstables = '';
$handle = FALSE; $i_scale = 0;
$done_table = TRUE;

// Let's now try to read the set of associated tonal scales
// This is only used for old versions of '-cs' files
// because they are now stored separately on '-to' files
// We read them together with Csound tables that should not be deleted
$scale_name = $scale_table = $scale_fraction = $scale_series = $comma_line = $scale_note_names = $scale_keys = $scale_comment = $baseoctave = array();
for($i = $i + 1; $i < $imax_file; $i++) {
	$line = trim($table[$i]);
//	if($verbose) echo $line."<br />";
	if($line == '') continue;
	if($line == "_end tables") {
		$done_table = FALSE;
//		if($handle) fclose($handle);
		if($i < ($imax_file - 1)) {
			// Now we get the name of the associated tonality file here
			$line = trim($table[$i + 1]);
			if((is_integer($pos=strpos($line,"-to")) AND $pos == 0) OR (is_integer(strpos($line,".bpto"))))
				$tonality_filename = $line;
			else if($i_scale ==  0) $tonality_filename = "No associated tonality file";
			}
		else if($i_scale ==  0) $tonality_filename = "No associated tonality file";
		break;
		}
	if($line[0] == '"') {
		$i_scale++;
		$scale_name[$i_scale] = str_replace('"','',$line);
		$clean_name_of_file = str_replace("#","_",$scale_name[$i_scale]);
		$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
		$clean_name_of_file = str_replace(SLASH,"_",$clean_name_of_file);
		$table_name = $dir_scales.$clean_name_of_file.".txt";
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
		if($handle AND $done_table) {
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
		//	echo "➡ Creating scale_".$i_scale."<br />";
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
echo "</textarea><br />";

echo "<h3>Associated tonality file</h3>";
echo "<input type=\"hidden\" name=\"tonality\" value=\"".$tonality_filename."\">";  // For autosave
echo "<textarea name=\"tonality_filename\" rows=\"1\" style=\"width:400px; background-color:cornsilk; color:black;\">";
echo $tonality_filename;
echo "</textarea><br />";
echo "<input type=\"hidden\" name=\"the_tables\" value='".$cstables."'>";  // For autosave
echo "<input type=\"hidden\" name=\"dir_scales\" value=\"".$dir_scales."\">";

if(is_integer(strpos($tonality_filename,"-to.")) OR is_integer(strpos($tonality_filename,".bpto"))) {
	$url_tonality = "tonality.php?file=".urlencode($tonality_resources.SLASH.$tonality_filename);
	echo "<input class=\"edit\" type=\"submit\" formaction=\"".$url_tonality."\" target=\"_blank\" name=\"opentonality\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$tonality_filename."’\">&nbsp;";
	}
$max_scales = $i_scale; // Beware that we count scales from 1 because 0 is the default equal-tempered scale
	
if($max_scales > 1) {
	echo "<p><span class=\"red-text\">➡</span> This old version of Csound instruments file contained ".$max_scales." scale definitions. These will be deleted and transfered to a tonality file</p>";
	// This is an old version of "-cs" file still containing scale definitions
	$dir_tonality = $dir_tonality_resources;
	// Save scale definitions to a "-to" file
	SaveTonality(FALSE,$dir_tonality,$tonality_filename,$temp_dir.$temp_folder,TRUE);
	echo "<p><span class=\"red-text\">➡</span> Tonality file ‘<span class=\"green-text\">".$tonality_filename."</span>’ has been created or updated</p>";
	}

echo "</td>";
echo "<td>";
if($number_instruments > 0) {
	echo "<h3>MIDI channel association of instruments:</h3>";
	echo "<table class=\"thicktable\">";
	echo "<tr>";
	echo "<td style=\"padding: 5px; vertical-align:middle;\">MIDI<br />channel</td><td>Instrument index</td>";
	echo "</tr>";
	for($ch = 0; $ch < 16; $ch++) {
		echo "<tr>";
		echo "<td>".($ch + 1)."</td>";
		echo "<td style=\"padding: 5px; vertical-align:middle;\">";
		$arg = "whichCsoundInstrument_".$ch;
		$x = $whichCsoundInstrument[$ch];
		$name = '';
		if($x < 0) $x = '';
		else if(isset($index_name[$x])) $name = $index_name[$x];
		echo "<input type=\"text\" name=\"".$arg."\" size=\"4\" value=\"".$x."\"> <i>".$name."</i>";
		echo "</td></tr>";
		}
	echo "</table>";
	}
else {
	for($ch = 0; $ch < 16; $ch++) {
		$arg = "whichCsoundInstrument_".$ch;
		$x = $whichCsoundInstrument[$ch];
		if($x < 0) $x = '';
		echo "<input type=\"hidden\" name=\"".$arg."\" value=\"".$x."\">";
		}
	}

echo "</tr><tr><td colspan=\"2\">";
echo "<h2 id=\"instruments\">Instruments</h2>";
if($deleted_instruments <> '') echo "<p><input class=\"edit\" type=\"submit\" name=\"restore\" onclick=\"this.form.target='_self';return true;\" formaction=\"".$url_this_page."#instruments\" value=\"RESTORE ALL DELETED INSTRUMENTS\">&nbsp;<span class=\"green-text\"><big>".$deleted_instruments."</big></span></p>";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";

echo "<p><input class=\"save big\" type=\"submit\" name=\"create_instrument\" onclick=\"this.form.target='_self';return true;\" value=\"CREATE A NEW INSTRUMENT\">&nbsp;named: <input type=\"text\" name=\"new_instrument\" size=\"20\" value=\"\"></p>";
echo "</form>";

if($number_instruments > 0) {
	echo "<h3>Click Csound instruments below to edit them:</h3>";
	echo "<table  class=\"thicktable\">";
	$done_index = array();
	for($j = 0; $j < $number_instruments; $j++) {
		echo "<tr class=\"middle\">";
		echo "<td class=\"middle\" style=\"white-space:nowrap\">";
		$this_index = $name_index[$CsoundInstrumentName[$j]];
		if(isset($done_index[$this_index])) {
			$this_index = $number_instruments;
			while(isset($done_index[$this_index])) $this_index++;
			}
		$done_index[$this_index] = TRUE;
		echo "<form method=\"post\" action=\"csinstrument.php?instrument_name=".urlencode($CsoundInstrumentName[$j])."&tonality_filename=".urlencode($tonality_filename)."&instrument_index=".urlencode($this_index)."&instrument_file=".urlencode($instrument_file[$j])."&temp_folder=".urlencode($temp_folder)."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"tonality_filename\" value=\"".$tonality_filename."\">";
		echo "<input type=\"hidden\" name=\"instrument_index\" value=\"".$this_index."\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"instrument_file\" value=\"".$instrument_file[$j]."\">";
		echo "<p><big>_ins(".$this_index.")</big> ";
		echo "<input class=\"edit big\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"instrument_name\" value=\"".$CsoundInstrumentName[$j]."\">";
		$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$CsoundInstrumentName[$j];
		$argmax_file = $folder_this_instrument.SLASH."argmax.php";
		$argmax_all = max_argument($argmax_file);
		echo "&nbsp;(".$argmax_all."&nbsp;args)</p>";
		echo "</td>";
		echo "</form>";
		echo "<td class=\"middle\">";
		echo "<small>".$InstrumentComment[$j]."</small>";
		echo "</td>";
		echo "<form method=\"post\" action=\"".$url_this_page."#instruments\" enctype=\"multipart/form-data\">";
		echo "<td class=\"middle\">";
		echo "<input type=\"hidden\" name=\"tonality_filename\" value=\"".$tonality_filename."\">";
		echo "<input type=\"hidden\" name=\"dir\" value=\"".$dir."\">";
		echo "<input type=\"hidden\" name=\"filename\" value=\"".$filename."\">";
		echo "<input type=\"hidden\" name=\"InstrumentIndex\" value=\"".$this_index."\">";
		echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$CsoundInstrumentName[$j]."\">";
		echo "<input type=\"hidden\" name=\"number_channels\" value=\"".$number_channels."\">";
		echo "<input type=\"hidden\" name=\"CsoundOrchestraName\" value=\"".$CsoundOrchestraName."\">";
		echo "<input type=\"hidden\" name=\"number_instruments\" value=\"".$number_instruments."\">";
		echo "<input type=\"hidden\" name=\"begin_tables\" value=\"".$begin_tables."\">";
		echo "<input type=\"hidden\" name=\"the_tables\" value='".$cstables."'>"; // For autosave
		for($ch = 0; $ch < $number_channels; $ch++) {
			$arg = "whichCsoundInstrument_".$ch;
			echo "<input type=\"hidden\" name=\"".$arg."\" value=\"".$whichCsoundInstrument[$ch]."\">";
			}
		echo "<input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"delete_instrument\" value=\"DELETE\">";
		echo "</td>";
		echo "<td class=\"middle\" style=\"text-align:right; padding:5px;\">";
		echo "<input class=\"edit\" type=\"submit\" onclick=\"this.form.target='_self';return true;\" name=\"duplicate_instrument\" value=\"DUPLICATE AS\">: <input type=\"text\" name=\"copy_instrument\" size=\"15\" value=\"\">";
		echo "</td>";
		echo "</form>";
		echo "</tr>";
		}
	echo "</table>";
	}
echo "</td>";
echo "</tr></table>";

// $verbose = TRUE;

if($verbose) {
	echo "<hr>";
	echo "<textarea name=\"thistext\" rows=\"20\" style=\"width:700px;\">".$content."</textarea>";
	}
echo "</body></html>";
?>
