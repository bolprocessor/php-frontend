<?php
require_once("_basic_tasks.php");

$url_this_page = "csinstrument.php";
// $test = TRUE;

if(isset($_POST['instrument_name'])) {
	$instrument_name = $_POST['instrument_name'];
	$temp_folder = $_POST['temp_folder'];
	$instrument_file = $_POST['instrument_file'];
	$instrument_index = $_POST['instrument_index'];
	}
else {
	echo "Csound instrument's name is not known. First open the ‘-cs’ file!"; die();
	}

$this_title = $instrument_name;
$instrument_name = str_replace(' ','_',$instrument_name);
require_once("_header.php");
display_darklight();

$instrument_folder_name = str_replace('-','_',$instrument_name);

if($test) echo "instrument_folder_name = ".$instrument_folder_name."<br />";
if($test) echo "temp_dir = ".$temp_dir."<br />";
if($test) echo "temp_folder = ".$temp_folder."<br />";

$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$instrument_folder_name;
if($test) echo "folder_this_instrument = ".$folder_this_instrument."<br />";

// echo "&nbsp;Instrument file: <span class=\"green-text\">".$instrument_file."</span>";
echo link_to_help();
echo "<h2>Csound instrument <big><span class=\"green-text\">_ins(".$instrument_index.")</span> <span class=\"red-text\">".$instrument_name."</span></big></h2>";

$argmax_file = $folder_this_instrument.SLASH."argmax.php";
		
$exists_name = array();
$dircontent = scandir($folder_this_instrument);
foreach($dircontent as $oldfile) {
	if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
	$table = explode(".",$oldfile);
	$extension = end($table);
	if($extension <> "old" AND $extension <> "txt") continue;
	$thisfile = str_replace(".old",'',$oldfile);
	$name = str_replace(".txt",'',$thisfile);
	$exists_name[$name] = TRUE;
	}
		
if(isset($_POST['create_parameter'])) {
	$new_parameter = trim($_POST['new_parameter']);
	$new_parameter = str_replace(' ','_',$new_parameter);
	$new_parameter = str_replace('-','_',$new_parameter);
	$new_parameter = str_replace('"','',$new_parameter);
	if(isset($exists_name[$new_parameter])) {
		echo "<p><span class=\"red-text\">A parameter with the same name “<span class=\"green-text\">".$new_parameter."</span>” already exists!</span></p>";
		}
	else {
		if($new_parameter <> '') {
			$new_parameter_file = $folder_this_instrument.SLASH.$new_parameter.".txt";
			$template = "parameter_template";
			$template_content = @file_get_contents($template,TRUE);
			$template_content = str_replace("[name]",$new_parameter,$template_content);
			$handle = fopen($new_parameter_file,"w");
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}

if(isset($_POST['delete_parameter'])) {
	$parameter_name = $_POST['parameter_name'];
	echo "<p><span class=\"red-text\">Deleted </span><span class=\"green-text\"><big>“".$parameter_name."”</big></span>…</p>";
	$this_parameter_file = $folder_this_instrument.SLASH.$parameter_name.".txt";
//	echo $this_parameter_file."<br />";
	rename($this_parameter_file,$this_parameter_file.".old");
	}

if(isset($_POST['restore'])) {
	echo "<p><span class=\"red-text\">Restoring: </span>";
	$dircontent = scandir($folder_this_instrument);
	foreach($dircontent as $oldfile) {
		if($oldfile == '.' OR $oldfile == ".." OR $oldfile == ".DS_Store") continue;
		$table = explode(".",$oldfile);
		$extension = end($table);
		if($extension <> "old") continue;
		$thisfile = str_replace(".old",'',$oldfile);
		echo "“<span class=\"green-text\">".str_replace(".txt",'',$thisfile)."</span>” ";
		$this_parameter_file = $folder_this_instrument.SLASH.$oldfile;
		rename($this_parameter_file,str_replace(".old",'',$this_parameter_file));
		}
	echo "</p>";
	}

if(isset($_POST['saveinstrument'])) {
	$csfilename = $_POST['csfilename'];
	echo "<p id=\"timespan\"><span class=\"red-text\">Saving this instrument…</span>";
	echo $instrument_file."<br />";
	$handle = fopen($instrument_file,"w");
	$file_header = $top_header."\n// Csound instrument saved as \"".$instrument_name."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$csfilename."\n");
	$comment = trim(recode_tags($_POST['comment']));
	if($comment == '') $comment = "no comment";
	fwrite($handle,$comment."\n");
	$argmax = $_POST['argmax'];
	$instrument_argmax = max_argument($argmax_file);
	if($instrument_argmax > $argmax) $argmax = $instrument_argmax;
	
	fwrite($handle,$argmax."\n");
	$argmax = 0;
	$instrument_index = $_POST['instrument_index'];
	fwrite($handle,$instrument_index."\n");
	$InstrumentDilationRatioIndex = convert_empty($_POST['InstrumentDilationRatioIndex']);
	fwrite($handle,$InstrumentDilationRatioIndex."\n");
	$InstrumentAttackVelocityIndex = convert_empty($_POST['InstrumentAttackVelocityIndex']);
	fwrite($handle,$InstrumentAttackVelocityIndex."\n");
	$InstrumentReleaseVelocityIndex = convert_empty($_POST['InstrumentReleaseVelocityIndex']);
	fwrite($handle,$InstrumentReleaseVelocityIndex."\n");
	$InstrumentPitchIndex = convert_empty($_POST['InstrumentPitchIndex']);
	if($InstrumentPitchIndex > $argmax) $argmax = $InstrumentPitchIndex;
	fwrite($handle,$InstrumentPitchIndex."\n");
	$InstrumentPitchFormat = $_POST['InstrumentPitchFormat'];
	fwrite($handle,$InstrumentPitchFormat."\n");
	$InstrumentPitchBendRange = trim($_POST['InstrumentPitchBendRange']); 
	if($InstrumentPitchBendRange == '') $InstrumentPitchBendRange = 200; // Fixed by BB 2021-02-14
	fwrite($handle,$InstrumentPitchBendRange."\n");
	
	if(isset($_POST['IsLogX_0'])) $InstrumentPitchBendIsLogX = 1;
	else $InstrumentPitchBendIsLogX = 0;
	fwrite($handle,$InstrumentPitchBendIsLogX."\n");
	if(isset($_POST['IsLogY_0'])) $InstrumentPitchBendIsLogY = 1;
	else $InstrumentPitchBendIsLogY = 0;
	fwrite($handle,$InstrumentPitchBendIsLogY."\n");
	
	if(isset($_POST['IsLogX_1'])) $InstrumentVolumeIsLogX = 1;
	else $InstrumentVolumeIsLogX = 0;
	fwrite($handle,$InstrumentVolumeIsLogX."\n");
	if(isset($_POST['IsLogY_1'])) $InstrumentVolumeIsLogY = 1;
	else $InstrumentVolumeIsLogY = 0;
	fwrite($handle,$InstrumentVolumeIsLogY."\n");
	
	if(isset($_POST['IsLogX_2'])) $InstrumentPressureIsLogX = 1;
	else $InstrumentPressureIsLogX = 0;
	fwrite($handle,$InstrumentPressureIsLogX."\n");
	if(isset($_POST['IsLogY_2'])) $InstrumentPressureIsLogY = 1;
	else $InstrumentPressureIsLogY = 0;
	fwrite($handle,$InstrumentPressureIsLogY."\n");
	
	if(isset($_POST['IsLogX_3'])) $InstrumentModulationIsLogX = 1;
	else $InstrumentModulationIsLogX = 0;
	fwrite($handle,$InstrumentModulationIsLogX."\n");
	if(isset($_POST['IsLogY_3'])) $InstrumentModulationIsLogY = 1;
	else $InstrumentModulationIsLogY = 0;
	fwrite($handle,$InstrumentModulationIsLogY."\n");
	
	if(isset($_POST['IsLogX_4'])) $InstrumentPanoramicIsLogX = 1;
	else $InstrumentPanoramicIsLogX = 0;
	fwrite($handle,$InstrumentPanoramicIsLogX."\n");
	if(isset($_POST['IsLogY_4'])) $InstrumentPanoramicIsLogY = 1;
	else $InstrumentPanoramicIsLogY = 0;
	fwrite($handle,$InstrumentPanoramicIsLogY."\n");
	
	$InstrumentPitchbendStartIndex = convert_empty($_POST['StartIndex_0']);
	if($InstrumentPitchbendStartIndex > $argmax) $argmax = $InstrumentPitchbendStartIndex;
	fwrite($handle,$InstrumentPitchbendStartIndex."\n");
	$InstrumentVolumeStartIndex = convert_empty($_POST['StartIndex_1']);
	if($InstrumentVolumeStartIndex > $argmax) $argmax = $InstrumentVolumeStartIndex;
	fwrite($handle,$InstrumentVolumeStartIndex."\n");
	$InstrumentPressureStartIndex = convert_empty($_POST['StartIndex_2']);
	if($InstrumentPressureStartIndex > $argmax) $argmax = $InstrumentPressureStartIndex;
	fwrite($handle,$InstrumentPressureStartIndex."\n");
	$InstrumentModulationStartIndex = convert_empty($_POST['StartIndex_3']);
	if($InstrumentModulationStartIndex > $argmax) $argmax = $InstrumentModulationStartIndex;
	fwrite($handle,$InstrumentModulationStartIndex."\n");
	$InstrumentPanoramicStartIndex = convert_empty($_POST['StartIndex_4']);
	if($InstrumentPanoramicStartIndex > $argmax) $argmax = $InstrumentPanoramicStartIndex;
	fwrite($handle,$InstrumentPanoramicStartIndex."\n");
	
	$InstrumentPitchbendEndIndex = convert_empty($_POST['EndIndex_0']);
	if($InstrumentPitchbendEndIndex > $argmax) $argmax = $InstrumentPitchbendEndIndex;
	fwrite($handle,$InstrumentPitchbendEndIndex."\n");
	$InstrumentVolumeEndIndex = convert_empty($_POST['EndIndex_1']);
	if($InstrumentVolumeEndIndex > $argmax) $argmax = $InstrumentVolumeEndIndex;
	fwrite($handle,$InstrumentVolumeEndIndex."\n");
	$InstrumentPressureEndIndex = convert_empty($_POST['EndIndex_2']);
	if($InstrumentPressureEndIndex > $argmax) $argmax = $InstrumentPressureEndIndex;
	fwrite($handle,$InstrumentPressureEndIndex."\n");
	$InstrumentModulationEndIndex = convert_empty($_POST['EndIndex_3']);
	if($InstrumentModulationEndIndex > $argmax) $argmax = $InstrumentModulationEndIndex;
	fwrite($handle,$InstrumentModulationEndIndex."\n");
	$InstrumentPanoramicEndIndex = convert_empty($_POST['EndIndex_4']);
	if($InstrumentPanoramicEndIndex > $argmax) $argmax = $InstrumentPanoramicEndIndex;
	fwrite($handle,$InstrumentPanoramicEndIndex."\n");
	
	$InstrumentPitchbendTableIndex = convert_empty($_POST['TableIndex_0']);
	if($InstrumentPitchbendTableIndex > $argmax) $argmax = $InstrumentPitchbendTableIndex;
	fwrite($handle,$InstrumentPitchbendTableIndex."\n");
	$InstrumentVolumeTableIndex = convert_empty($_POST['TableIndex_1']);
	if($InstrumentVolumeTableIndex > $argmax) $argmax = $InstrumentVolumeTableIndex;
	fwrite($handle,$InstrumentVolumeTableIndex."\n");
	$InstrumentPressureTableIndex = convert_empty($_POST['TableIndex_2']);
	if($InstrumentPressureTableIndex > $argmax) $argmax = $InstrumentPressureTableIndex;
	fwrite($handle,$InstrumentPressureTableIndex."\n");
	$InstrumentModulationTableIndex = convert_empty($_POST['TableIndex_3']);
	if($InstrumentModulationTableIndex > $argmax) $argmax = $InstrumentModulationTableIndex;
	fwrite($handle,$InstrumentModulationTableIndex."\n");
	$InstrumentPanoramicTableIndex = convert_empty($_POST['TableIndex_4']);
	if($InstrumentPanoramicTableIndex > $argmax) $argmax = $InstrumentPanoramicTableIndex;
	fwrite($handle,$InstrumentPanoramicTableIndex."\n");
	
	$InstrumentPitchbendGEN = convert_empty($_POST['GEN_0']);
	fwrite($handle,$InstrumentPitchbendGEN."\n");
	$InstrumentVolumeGEN = convert_empty($_POST['GEN_1']);
	fwrite($handle,$InstrumentVolumeGEN."\n");
	$InstrumentPressureGEN = convert_empty($_POST['GEN_2']);
	fwrite($handle,$InstrumentPressureGEN."\n");
	$InstrumentModulationGEN = convert_empty($_POST['GEN_3']);
	fwrite($handle,$InstrumentModulationGEN."\n");
	$InstrumentPanoramicGEN = convert_empty($_POST['GEN_4']);
	fwrite($handle,$InstrumentPanoramicGEN."\n");
	for($i = 0; $i < 6; $i++) {
		$param = convert2_empty($_POST['paramvalue_'.$i.'_0']);
		fwrite($handle,$param."\n");
		$param = convert2_empty($_POST['paramvalue_'.$i.'_1']);
		fwrite($handle,$param."\n");
		$param = convert2_empty($_POST['paramvalue_'.$i.'_2']);
		fwrite($handle,$param."\n");
		$param = convert2_empty($_POST['paramvalue_'.$i.'_3']);
		fwrite($handle,$param."\n");
		$param = convert2_empty($_POST['paramvalue_'.$i.'_4']);
		fwrite($handle,$param."\n");
		}
	fclose($handle);
	set_argmax_argument($argmax_file,$instrument_name,$argmax);
	$file_changed = $temp_dir.$temp_folder.SLASH."_changed";
	$handle = fopen($file_changed,"w");
	if($handle) fclose($handle);
	}

$content = file_get_contents($instrument_file,TRUE);
$extract_data = extract_data(TRUE,$content);
$content = $extract_data['content'];

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$instrument_name."\">";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"instrument_file\" value=\"".$instrument_file."\">";
$table = explode(chr(10),$content);
$csfilename = $table[0];
echo "<input type=\"hidden\" name=\"csfilename\" value=\"".$csfilename."\">";
echo "<h3>Part of file “<span class=\"green-text\">".$csfilename."</span>”</h3>";
$verbose = TRUE;
$verbose = FALSE;
$i = 1;
$argmax = $table[++$i];
$instrument_argmax = max_argument($argmax_file);
if($instrument_argmax > $argmax) $argmax = $instrument_argmax;
if($verbose) echo "argmax = ".$argmax."<br />";
echo "<input type=\"hidden\" name=\"argmax\" value=\"".$argmax."\">";
$instrument_index = $table[++$i];
if(isset($_POST['instrument_index'])) $instrument_index = $_POST['instrument_index'];
// echo "<input type=\"hidden\" name=\"instrument_index\" value=\"".$instrument_index."\">";
if($verbose) echo "Instrument index = ".$instrument_index."<br />";
$InstrumentDilationRatioIndex = $table[++$i];
if($verbose) echo "Instrument dilation ratio argument = ".$InstrumentDilationRatioIndex."<br />";
$InstrumentAttackVelocityIndex = $table[++$i];
if($verbose) echo "Instrument attack velocity argument = ".$InstrumentAttackVelocityIndex."<br />";
$InstrumentReleaseVelocityIndex = $table[++$i];
if($verbose) echo "Instrument release velocity argument = ".$InstrumentReleaseVelocityIndex."<br />";
$InstrumentPitchIndex = $table[++$i];
if($verbose) echo "Instrument pitch argument = ".$InstrumentPitchIndex."<br />";
$InstrumentPitchFormat = $table[++$i];
if($verbose) echo "Instrument pitch format = ".$InstrumentPitchFormat."<br />";
$InstrumentPitchBendRange = $table[++$i];
if($verbose) echo "Instrument pichbend range = ".$InstrumentPitchBendRange."<br />";
$InstrumentPitchBendIsLogX = $table[++$i];
if($verbose) echo "Instrument pichbend islogx = ".$InstrumentPitchBendIsLogX."<br />";
$InstrumentPitchBendIsLogY = $table[++$i];
if($verbose) echo "Instrument pichbend islogy = ".$InstrumentPitchBendIsLogY."<br />";
$InstrumentVolumeIsLogX = $table[++$i];
if($verbose) echo "Instrument volume islogx = ".$InstrumentVolumeIsLogX."<br />";
$InstrumentVolumeIsLogY = $table[++$i];
if($verbose) echo "Instrument volume islogy = ".$InstrumentVolumeIsLogY."<br />";
$InstrumentPressureIsLogX = $table[++$i];
if($verbose) echo "Instrument pressure islogx = ".$InstrumentPressureIsLogX."<br />";
$InstrumentPressureIsLogY = $table[++$i];
if($verbose) echo "Instrument pressure islogy = ".$InstrumentPressureIsLogY."<br />";
$InstrumentModulationIsLogX = $table[++$i];
if($verbose) echo "Instrument modulation islogx = ".$InstrumentModulationIsLogX."<br />";
$InstrumentModulationIsLogY = $table[++$i];
if($verbose) echo "Instrument modulation islogy = ".$InstrumentModulationIsLogY."<br />";
$InstrumentPanoramicIsLogX = $table[++$i];
if($verbose) echo "Instrument panoramic islogx = ".$InstrumentPanoramicIsLogX."<br />";
$InstrumentPanoramicIsLogY = $table[++$i];
if($verbose) echo "Instrument panoramic islogy = ".$InstrumentPanoramicIsLogY."<br />";
$InstrumentPitchbendStartIndex = $table[++$i];
if($verbose) echo "Instrument pitchbend start argument = ".$InstrumentPitchbendStartIndex."<br />";
$InstrumentVolumeStartIndex = $table[++$i];
if($verbose) echo "Instrument volume start argument = ".$InstrumentVolumeStartIndex."<br />";
$InstrumentPressureStartIndex = $table[++$i];
if($verbose) echo "Instrument pressure start argument = ".$InstrumentPressureStartIndex."<br />";
$InstrumentModulationStartIndex = $table[++$i];
if($verbose) echo "Instrument modulation start argument = ".$InstrumentModulationStartIndex."<br />";
$InstrumentPanoramicStartIndex = $table[++$i];
if($verbose) echo "Instrument panoramic start argument = ".$InstrumentPanoramicStartIndex."<br />";
$InstrumentPitchbendEndIndex = $table[++$i];
if($verbose) echo "Instrument pitchbend end argument = ".$InstrumentPitchbendEndIndex."<br />";
$InstrumentVolumeEndIndex = $table[++$i];
if($verbose) echo "Instrument volume end argument = ".$InstrumentVolumeEndIndex."<br />";
$InstrumentPressureEndIndex = $table[++$i];
if($verbose) echo "Instrument pressure end argument = ".$InstrumentPressureEndIndex."<br />";
$InstrumentModulationEndIndex = $table[++$i];
if($verbose) echo "Instrument modulation end argument = ".$InstrumentModulationEndIndex."<br />";
$InstrumentPanoramicEndIndex = $table[++$i];
if($verbose) echo "Instrument panoramic end argument = ".$InstrumentPanoramicEndIndex."<br />";
$InstrumentPitchbendTableIndex = $table[++$i];
if($verbose) echo "Instrument pitchbend table argument = ".$InstrumentPitchbendTableIndex."<br />";
$InstrumentVolumeTableIndex = $table[++$i];
if($verbose) echo "Instrument volume table argument = ".$InstrumentVolumeTableIndex."<br />";
$InstrumentPressureTableIndex = $table[++$i];
if($verbose) echo "Instrument pressure table argument = ".$InstrumentPressureTableIndex."<br />";
$InstrumentModulationTableIndex = $table[++$i];
if($verbose) echo "Instrument modulation table argument = ".$InstrumentModulationTableIndex."<br />";
$InstrumentPanoramicTableIndex = $table[++$i];
if($verbose) echo "Instrument panoramic table argument = ".$InstrumentPanoramicTableIndex."<br />";
$InstrumentPitchbendGEN = $table[++$i];
if($verbose) echo "Instrument pitchbendGEN = ".$InstrumentPitchbendGEN."<br />";
$InstrumentVolumeGEN = $table[++$i];
if($verbose) echo "Instrument volumeGEN = ".$InstrumentVolumeGEN."<br />";
$InstrumentPressureGEN = $table[++$i];
if($verbose) echo "Instrument pressureGEN = ".$InstrumentPressureGEN."<br />";
$InstrumentModulationGEN = $table[++$i];
if($verbose) echo "Instrument modulationGEN = ".$InstrumentModulationGEN."<br />";
$InstrumentPanoramicGEN = $table[++$i];
if($verbose) echo "Instrument panoramicGEN = ".$InstrumentPanoramicGEN."<br />";
for($ii = 0; $ii < 6; $ii++) {
	$InstrumentPitchbend[$ii] = $table[++$i];
	if($verbose) echo "Instrument Pitchbend[".$ii."] = ".$InstrumentPitchbend[$ii]."<br />";
	$InstrumentVolume[$ii] = $table[++$i];
	if($verbose) echo "Instrument Volume[".$ii."] = ".$InstrumentVolume[$ii]."<br />";
	$InstrumentPressure[$ii] = $table[++$i];
	if($verbose) echo "Instrument Pressure[".$ii."] = ".$InstrumentPressure[$ii]."<br />";
	$InstrumentModulation[$ii] = $table[++$i];
	if($verbose) echo "Instrument Modulation[".$ii."] = ".$InstrumentModulation[$ii]."<br />";
	$InstrumentPanoramic[$ii] = $table[++$i];
	if($verbose) echo "Instrument Panoramic[".$ii."] = ".$InstrumentPanoramic[$ii]."<br />";
	}

echo "<p style=\"text-align:left;\"><input class=\"save big\" type=\"submit\" name=\"saveinstrument\" value=\"SAVE THIS INSTRUMENT\"></p>";
$comment = recode_tags($table[1]);
echo "<p>Comment: <input type=\"text\" name=\"comment\" size=\"90\" value=\"".$comment."\"></p>";
echo "<table>";
echo "<tr>";
echo "<td>";
echo "<p style=\"text-align:right;\">Index of this instrument: <input type=\"text\" name=\"instrument_index\" size=\"4\" value=\"".$instrument_index."\"><br/>";
// echo "<p style=\"text-align:right;\">Index of this instrument: <input type=\"text\" name=\"InstrumentIndex\" size=\"4\" value=\"".$instrument_index."\"><br/>";
$x = $InstrumentDilationRatioIndex;
if($x == -1) $x = '';
echo "Dilation ratio argument: <input type=\"text\" name=\"InstrumentDilationRatioIndex\" size=\"4\" value=\"".$x."\"><br/>";
$x = $InstrumentAttackVelocityIndex;
if($x == -1) $x = '';
echo "Attack velocity argument: <input type=\"text\" name=\"InstrumentAttackVelocityIndex\" size=\"4\" value=\"".$x."\"><br />";
$x = $InstrumentReleaseVelocityIndex;
if($x == -1) $x = '';
echo "Release velocity argument: <input type=\"text\" name=\"InstrumentReleaseVelocityIndex\" size=\"4\" value=\"".$x."\"></p>";
echo "</td>";
echo "<td>";
echo "<table>";
echo "<tr>";
echo "<td>Instrument pitch argument:</td>";
echo "<td style=\"padding: 5px;\"><input type=\"text\" name=\"InstrumentPitchIndex\" size=\"4\" value=\"".$InstrumentPitchIndex."\"></td>";
echo "</tr><tr>";
echo "<td style=\"text-align:right;\">Pitch format:</td>";
echo "<td style=\"padding: 5px;\">";
echo "<input type=\"radio\" name=\"InstrumentPitchFormat\" value=\"0\"";
if($InstrumentPitchFormat == 0) echo " checked";
echo ">octave point pitch-class<br />";
echo "<input type=\"radio\" name=\"InstrumentPitchFormat\" value=\"1\"";
if($InstrumentPitchFormat == 1) echo " checked";
echo ">octave point decimal<br />";
echo "<input type=\"radio\" name=\"InstrumentPitchFormat\" value=\"2\"";
if($InstrumentPitchFormat == 2) echo " checked";
echo ">cps (Hz)";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<table class=\"thicktable\">";
echo "<tr>";
echo "<td style=\"padding-left:1em; padding-top:1em;\">";
echo "<h3>Parameters associated with MIDI controllers</h3>";
$x = intval($InstrumentPitchBendRange);
if($x == -1) $x = '';
echo "Pichbender range: +/- <input type=\"text\" name=\"InstrumentPitchBendRange\" size=\"8\" value=\"".$x."\"> cents";
echo "</td>";
echo "<td>";
echo MIDIparameter_argument(0,"Pitchbend",$InstrumentPitchbendStartIndex,$InstrumentPitchbendEndIndex,$InstrumentPitchbendTableIndex,$InstrumentPitchbend,$InstrumentPitchBendIsLogX,$InstrumentPitchBendIsLogY,$InstrumentPitchbendGEN);
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>";
echo MIDIparameter_argument(1,"Volume",$InstrumentVolumeStartIndex,$InstrumentVolumeEndIndex,$InstrumentVolumeTableIndex,$InstrumentVolume,$InstrumentVolumeIsLogX,$InstrumentVolumeIsLogY,$InstrumentVolumeGEN);
echo "</td>";
echo "<td>";
echo MIDIparameter_argument(2,"Pressure",$InstrumentPressureStartIndex,$InstrumentPressureEndIndex,$InstrumentPressureTableIndex,$InstrumentPressure,$InstrumentPressureIsLogX,$InstrumentPressureIsLogY,$InstrumentPressureGEN);
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td>";
echo MIDIparameter_argument(3,"Modulation",$InstrumentModulationStartIndex,$InstrumentModulationEndIndex,$InstrumentModulationTableIndex,$InstrumentModulation,$InstrumentModulationIsLogX,$InstrumentModulationIsLogY,$InstrumentModulationGEN);
echo "</td>";
echo "<td>";
echo MIDIparameter_argument(4,"Panoramic",$InstrumentPanoramicStartIndex,$InstrumentPanoramicEndIndex,$InstrumentPanoramicTableIndex,$InstrumentPanoramic,$InstrumentPanoramicIsLogX,$InstrumentPanoramicIsLogY,$InstrumentPanoramicGEN);
echo "</td>";
echo "</tr>";
echo "<table>";

if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
echo "<h3>Other (MIDI unrelated) parameters</h3>";
$dir_instrument = scandir($folder_this_instrument);
$deleted_parameters = '';
foreach($dir_instrument as $thisparameter) {
	if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store" OR $thisparameter == '') continue;
	$table = explode(".",$thisparameter);
	$extension = end($table);
	if($extension <> "old") continue;
	$parameter_name = str_replace(".txt.old",'',$thisparameter);
	$deleted_parameters .= "“".$parameter_name."” ";
	}
if($deleted_parameters <> '') echo "<p><input class=\"save\" type=\"submit\" name=\"restore\" value=\"RESTORE ALL DELETED PARAMETERS\"> = <span class=\"green-text\"><big>".$deleted_parameters."</big></span></p>";
echo "<p><input class=\"save\" type=\"submit\" name=\"create_parameter\" value=\"CREATE A NEW PARAMETER\"> named <input type=\"text\" name=\"new_parameter\" size=\"20\" value=\"\"></p>";

echo "</form>";
$n = 0;
foreach($dir_instrument as $thisparameter) {
	if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store" OR $thisparameter == '') continue;
	$table = explode(".",$thisparameter);
	$extension = end($table);
	if($extension <> "txt") continue;
	$n++;
	}
if($n > 0) {
	echo "<table class=\"thicktable\">";
	echo "<tr>";
	echo "<td colspan=\"3\"><span class=\"red-text\">➡</span> Click the blue button to edit…</td>";
	echo "</tr>";
	foreach($dir_instrument as $thisparameter) {
		if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store" OR $thisparameter == '') continue;
		$table = explode(".",$thisparameter);
		$extension = end($table);
		if($extension <> "txt") continue;
		$parameter_name = str_replace(".txt",'',$thisparameter);
		$content = file_get_contents($folder_this_instrument.SLASH.$thisparameter,TRUE);
		$table = explode(chr(10),$content);
		$comment = $table[1];
		echo "<tr>";
		echo "<form method=\"post\" action=\"csparameter.php\" enctype=\"multipart/form-data\">";
		echo "<td class=\"middle\" style=\"padding:5px;\">";
		echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$instrument_name."\">";
		echo "<input type=\"hidden\" name=\"instrument_index\" value=\"".$instrument_index."\">";
		echo "<input type=\"hidden\" name=\"csfilename\" value=\"".$csfilename."\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"folder_this_instrument\" value=\"".$folder_this_instrument."\">";
		echo "<input class=\"edit big\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" name=\"parameter_name\" value=\"".$parameter_name."\">";
		echo "</td>";
		echo "</form>";
		echo "<td class=\"middle\" style=\"padding:5px;\">";
		echo $comment."<br />";
		echo "</td>";
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<td class=\"middle\" style=\"padding:5px;\">";
		echo "<input type=\"hidden\" name=\"instrument_name\" value=\"".$instrument_name."\">";
		echo "<input type=\"hidden\" name=\"instrument_index\" value=\"".$instrument_index."\">";
		echo "<input type=\"hidden\" name=\"parameter_name\" value=\"".$parameter_name."\">";
		echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
		echo "<input type=\"hidden\" name=\"instrument_file\" value=\"".$instrument_file."\">";
		echo "<input class=\"save\" type=\"submit\" name=\"delete_parameter\" value=\"DELETE\">";
		echo "</td>";
		echo "</form>";
		echo "</tr>";
		}
	echo "</table>";
	}
// echo str_replace("\n","<br />",$content);
?>