<?php
require_once("_basic_tasks.php");

$url_this_page = "prototype.php";

if(isset($_POST['object_name'])) {
	$object_name = $_POST['object_name'];
	$temp_folder = $_POST['temp_folder'];
	$object_file = $_POST['object_file'];
	$prototypes_file = $_POST['prototypes_file'];
	$prototypes_name = $_POST['prototypes_name'];
	$CsoundInstruments_file = $_POST['CsoundInstruments_file'];
	}
else {
	"Sound-object prototype's name is not known. First open the ‘-mi’ file!";
	die();
	}
$this_title = $expression = $object_name;

require_once("_header.php");
display_darklight();

echo "<script src=\"https://cdn.jsdelivr.net/combine/npm/tone@14.7.58,npm/@magenta/music@1.23.1/es6/core.js,npm/focus-visible@5,npm/html-midi-player@1.4.0\"></script>";

$object_foldername = clean_folder_name($object_name);

if($test) echo "prototypes_name = ".$prototypes_name."<br />";
if($test) echo "prototypes_file = ".$prototypes_file."<br />";
if($test) echo "object_foldername = ".$object_foldername."<br />";
if($test) echo "CsoundInstruments_file = ".$CsoundInstruments_file."<br />";

$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
$deleted_object = $temp_dir.$temp_folder.SLASH.$object_name.".txt.old";

if($test) echo "dir = ".$temp_dir."<br />";
if($test) echo "temp_folder = ".$temp_folder."<br />";
if($test) echo "save_codes_dir = ".$save_codes_dir."<br />";

if(file_exists($deleted_object)) {
	echo "<p><span class=\"red-text\">Sound-object</span> <span class=\"green-text\">“".$object_name."”</span> <span class=\"red-text\">has been deleted.<br />Close this tab and return to the “-mi” file in which it has been deleted.<br />Then click the <b>RESTORE ALL DELETED OBJECTS</b> button.</span></p>";
	die();
	}
if(!is_dir($save_codes_dir)) mkdir($save_codes_dir);
$image_file = $save_codes_dir.SLASH."image.php";
$midi_file = $save_codes_dir.SLASH."midicodes.mid";
$midi_text = $save_codes_dir.SLASH."midicodes.txt";
$midi_bytes = $save_codes_dir.SLASH."midibytes.txt";
$midi_import = $save_codes_dir.SLASH."midi_import.txt";
$csound_file = $save_codes_dir.SLASH."csound.txt";
$mf2t = $save_codes_dir.SLASH."mf2t.txt";
$midi_text_bytes = array();
if(isset($_FILES['mid_upload']) AND $_FILES['mid_upload']['tmp_name'] <> '') {
	$upload_filename = $_FILES['mid_upload']['name'];
	if($_FILES["mid_upload"]["size"] > MAXFILESIZE) {
		echo "<h3><span class=\"red-text\">Uploading failed:</span> <span class=\"green-text\">".$upload_filename."</span> <span class=\"red-text\">is larger than ".MAXFILESIZE." bytes</span></h3>";
		}
	else {
		$tmpFile = $_FILES['mid_upload']['tmp_name'];
		move_uploaded_file($tmpFile,$midi_file) or die('Problem uploading this MIDI file');
		@chmod($midi_file,0666);
		$table = explode('.',$upload_filename);
		$extension = end($table);
		if($extension <> "mid" and $extension <> "midi") {
			echo "<h4><span class=\"red-text\">Uploading failed:</span> <span class=\"green-text\">".$upload_filename."</span> <span class=\"red-text\">does not have the extension of a MIDI file!</span></h4>";
			@unlink($midi_file);
			}
		else {
			$MIDIfiletype = MIDIfiletype($midi_file);
			echo "MIDI file type = ".$MIDIfiletype."<br />";
			if($MIDIfiletype < 0) {
				echo "<p><span class=\"red-text\">File </span>“<span class=\"green-text\">".$upload_filename."”</span> <span class=\"red-text\">is unreadable as a MIDI file.</span></p>";
				$upload_filename = '';
				@unlink($midi_file);
				}
			else if($MIDIfiletype > 1) {
				echo "<h4><span class=\"red-text\">MIDI file </span>“<span class=\"green-text\">".$upload_filename."</span>” <span class=\"red-text\"> of </span><span class=\"green-text\">type ".$MIDIfiletype." </span><span class=\"red-text\">is not accepted. Only types 0 and 1 are compliant.</span></h4>";
				$upload_filename = '';
				@unlink($midi_file);
				}
			else {
				@unlink($midi_text);
				$midi = new Midi();
				$midi_text_bytes = convert_mf2t_to_bytes(FALSE,$midi_import,$midi,$midi_file);
				$division = $_POST['division'] = $midi_text_bytes[0];
			//	$division = $_POST['division'] = 1000;
				$tempo = $_POST['tempo'] = $midi_text_bytes[1];
			//	$tempo = $_POST['tempo'] = 1000000;
				$timesig = $_POST['timesig'] = "0 TimeSig ".$midi_text_bytes[2]." ".$midi_text_bytes[3]." ".$midi_text_bytes[4];
				$temp_bytes = array();
				for($i = 5; $i < count($midi_text_bytes); $i++) {
					$temp_bytes[] = $midi_text_bytes[$i];
				//	echo $midi_text_bytes[$i]."<br />";
					}
				$midi_text_bytes = $temp_bytes;
				$message = fix_mf2t_file($midi_import,"unnamed_");
				echo "<h3 id=\"timespan\"><span class=\"red-text\">Converted MIDI file:</span> <span class=\"green-text\">".$upload_filename."</span></h3>";
				if($message <> '') echo $message;
				}
			}
		}
	}
// else echo "<p>Object file: <span class=\"green-text\">".$object_file."</span>";
unset($_FILES['mid_upload']);

if(isset($_POST['division']) AND $_POST['division'] > 0) $division = $_POST['division'];
else $division = 1000;
if(isset($_POST['tempo']) AND $_POST['tempo'] > 0) $tempo = $_POST['tempo'];
else $tempo = 1000000;
/* if(isset($_POST['old_tempo']) AND $_POST['old_tempo'] > 0) $old_tempo = $_POST['old_tempo'];
else $old_tempo = 1000000; */
if(isset($_POST['timesig']) AND $_POST['timesig'] <> '') $timesig = $_POST['timesig'];
else $timesig = "0 TimeSig 4/4 24 8";

if(isset($_POST['savecsound'])) {
	$handle = fopen($csound_file,"w");
	$csound_score = $_POST['csound_score'];
	fwrite($handle,$csound_score."\n");
	fclose($handle);
	$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
	fclose($handle);
	}

if(isset($_POST['savethisprototype']) OR isset($_POST['suppress_pressure']) OR isset($_POST['suppress_pitchbend']) OR isset($_POST['suppress_polyphonic_pressure']) OR isset($_POST['suppress_volume']) OR isset($_POST['suppress_modulation']) OR isset($_POST['suppress_panoramic']) OR isset($_POST['suppress_program']) OR isset($_POST['adjust_duration']) OR isset($_POST['adjust_beats']) OR isset($_POST['adjust_duration']) OR isset($_POST['silence_before']) OR isset($_POST['silence_after']) OR isset($_POST['add_allnotes_off']) OR isset($_POST['suppress_allnotes_off']) OR isset($_POST['quantize_NoteOn']) OR isset($_POST['delete_midi']) OR isset($_POST['cancel'])) {
	// if($test) echo "<br />object_file = ".$object_file."<br />";
	echo "<span id=\"timespan\">&nbsp;&nbsp;<span class=\"red-text\">➡ Saving this file...</span></span>";
	$prototype_file = $object_file;
	$handle = fopen($prototype_file,"w");
	$source_file = $_POST['source_file'];
	$file_header = $top_header."\n// Object prototype saved as \"".$object_name."\". Date: ".gmdate('Y-m-d H:i:s');
	$file_header .= "\n".$source_file;
	fwrite($handle,$file_header."\n");
	$object_type = 0;
	if(isset($_POST['object_type1'])) $object_type += 1;
	if(isset($_POST['object_type4'])) $object_type += 4;
	fwrite($handle,$object_type."\n");
	$j = 1;
	$resolution = $_POST["object_param_".$j++];
	fwrite($handle,$resolution."\n");
	
	$channel_force = $_POST['channel_force'];
	if($channel_force <> 1) $MIDIchannel = $channel_force;
	else $MIDIchannel = trim($_POST['MIDIchannel']);
	if($MIDIchannel == '') $MIDIchannel = 0;
	$MIDIchannel = intval($MIDIchannel);
	
//	$default_channel = $_POST["object_param_".$j++];
	fwrite($handle,$MIDIchannel."\n");
	$j++;
	
	$Tref = intval($_POST['Tref']);
//	$Trefc = $_POST['Tref'] / $resolution;
//	fwrite($handle,$Trefc."\n");
	fwrite($handle,$Tref."\n");
	$j++;
	$quantization = $_POST["object_param_".$j++];
	fwrite($handle,$quantization."\n"); // Quantization
	if(isset($_POST['Pivot_mode']) AND $Tref > 0)
		$pivot_mode = $_POST['Pivot_mode'];
	else $pivot_mode = $_POST['Pivot_mode'] = -1;
	if($pivot_mode == -1 AND $Tref > 0) $pivot_mode = 0;
	$string = "00000000000000000000";
	if($pivot_mode >= 0) $string[$pivot_mode] = "1";
//	echo "<p>".$string."</p>";
	$k = 6;
	$okrescale = $FixScale = $OkExpand = $OkCompress = 0;
	switch($_POST['Rescale']) {
		case "okrescale":
			$okrescale = 1;
			break;
		case "neverrescale":
			$FixScale = 1;
			break;
		case "dilationrange":
			$okrescale = 0;
			break;
		}
	
	if($FixScale == 0 AND isset($_POST['OkExpand'])) $OkExpand = 1;
	if($FixScale == 0 AND isset($_POST['OkCompress'])) $OkCompress = 1;
	
	$string[$k++] = $okrescale;
	$string[$k++] = $FixScale;
	$string[$k++] = $OkExpand;
	$string[$k++] = $OkCompress;
	
	$BreakTempo = $_POST['BreakTempo'];
	$OkRelocate = $_POST['OkRelocate'];
	
	$string[$k++] = $OkRelocate;
	$string[$k++] = $BreakTempo;
	
	$ContBeg = $_POST['ContBeg'];
	$ContEnd = $_POST['ContEnd'];
	
	$string[$k++] = $ContBeg;
	$string[$k++] = $ContEnd;
	
	$CoverBeg = $_POST['CoverBeg'];
	$CoverEnd = $_POST['CoverEnd'];
	$string[$k++] = $CoverBeg;
	$string[$k++] = $CoverEnd;
	
	$TruncBeg = $_POST['TruncBeg'];
	$TruncEnd = $_POST['TruncEnd'];
	$string[$k++] = $TruncBeg;
	$string[$k++] = $TruncEnd;
	
	$pivspec = 0;
	if($_POST['Pivot_mode'] == 18) $pivspec = 1;
	$string[$k++] = $pivspec;
	
	if(isset($_POST['AlphaCtrl'])) $AlphaCtrl = 1;
	else $AlphaCtrl = 0;
	$string[$k++] = $AlphaCtrl;

	fwrite($handle,$string."\n");
	$j++;
	
	$RescaleMode = $_POST['RescaleMode']; // ???
	fwrite($handle,$RescaleMode."\n");
	
	$AlphaMin = $_POST['AlphaMin']; if($AlphaMin == '') $AlphaMin = "0.0000";
	fwrite($handle,$AlphaMin."\n");
	$AlphaMax = $_POST['AlphaMax']; if($AlphaMax == '') $AlphaMax = "0.0000";
	fwrite($handle,$AlphaMax."\n");
	
	if(isset($_POST['DelayMode'])) $DelayMode = $_POST['DelayMode'];
	else $DelayMode = 1;
	$MaxDelay = '';
	if($DelayMode == -1) $MaxDelay = $_POST['MaxDelay1'];
	if($DelayMode == 0) $MaxDelay = $_POST['MaxDelay2'];
	if($OkRelocate OR $MaxDelay == '') {
		$MaxDelay = 0;
		$DelayMode = 1;
		}
	fwrite($handle,$DelayMode."\n");
	fwrite($handle,$MaxDelay."\n");
	
	if(isset($_POST['ForwardMode'])) $ForwardMode = $_POST['ForwardMode'];
	else $ForwardMode = 1;
	$MaxForward = '';
	if($ForwardMode == -1) $MaxForward = $_POST['MaxForward1'];
	if($ForwardMode == 0) $MaxForward = $_POST['MaxForward2'];
	if($OkRelocate OR $MaxForward == '') {
		$MaxForward = 0;
		$ForwardMode = 1;
		}
	fwrite($handle,$ForwardMode."\n");
	fwrite($handle,$MaxForward."\n");
	
	$BreakTempoMode = $_POST['BreakTempoMode'];
	fwrite($handle,$BreakTempoMode."\n");
	fwrite($handle,$division."\n");
	
	if(isset($_POST['ContBegMode'])) $ContBegMode = $_POST['ContBegMode'];
	else $ContBegMode = 1;
	$MaxBegGap = '';
	if($ContBegMode == -1) $MaxBegGap = $_POST['MaxBegGap1'];
	if($ContBegMode == 0) $MaxBegGap = $_POST['MaxBegGap2'];
	if(!$ContBeg OR $MaxBegGap == '') {
		$MaxBegGap = 0;
		$ContBegMode = 1;
		}
	fwrite($handle,$ContBegMode."\n");
	fwrite($handle,$MaxBegGap."\n");
	
	if(isset($_POST['ContEndMode'])) $ContEndMode = $_POST['ContEndMode'];
	else $ContEndMode = 1;
	$MaxEndGap = '';
	if($ContEndMode == -1) $MaxEndGap = $_POST['MaxEndGap1'];
	if($ContEndMode == 0) $MaxEndGap = $_POST['MaxEndGap2'];
	if(!$ContEnd OR $MaxEndGap == '') {
		$MaxEndGap = 0;
		$ContEndMode = 1;
		}
	fwrite($handle,$ContEndMode."\n");
	fwrite($handle,$MaxEndGap."\n");
	
	if(isset($_POST['CoverBegMode'])) $CoverBegMode = $_POST['CoverBegMode'];
	else $CoverBegMode = 0;
	$MaxCoverBeg = '';
	if($CoverBegMode == -1) $MaxCoverBeg = $_POST['MaxCoverBeg1'];
	if($CoverBegMode == 0) $MaxCoverBeg = $_POST['MaxCoverBeg2'];
	if($CoverBeg) {
		$CoverBegMode = 0;
		$MaxCoverBeg = 100;
		}
	fwrite($handle,$CoverBegMode."\n");
	fwrite($handle,$MaxCoverBeg."\n");
	
	if(isset($_POST['CoverEndMode'])) $CoverEndMode = $_POST['CoverEndMode'];
	else $CoverEndMode = 0;
	$MaxCoverEnd = 0;
	if($CoverEndMode == -1) $MaxCoverEnd = $_POST['MaxCoverEnd1'];
	if($CoverEndMode == 0) $MaxCoverEnd = $_POST['MaxCoverEnd2'];
	if($CoverEnd) {
		$CoverEndMode = 0;
		$MaxCoverEnd = 100;
		}
	fwrite($handle,$CoverEndMode."\n");
	fwrite($handle,$MaxCoverEnd."\n");
	
	if(isset($_POST['TruncBegMode'])) $TruncBegMode = $_POST['TruncBegMode'];
	else $TruncBegMode = 1;
	$MaxTruncBeg = '';
	if($TruncBegMode == -1) $MaxTruncBeg = $_POST['MaxTruncBeg1'];
	if($TruncBegMode == 0) $MaxTruncBeg = $_POST['MaxTruncBeg2'];
	if($TruncBeg OR $MaxTruncBeg == '') {
		$MaxTruncBeg = 0;
		$TruncBegMode = 1;
		}
	fwrite($handle,$TruncBegMode."\n");
	fwrite($handle,$MaxTruncBeg."\n");
	
	if(isset($_POST['TruncEndMode'])) $TruncEndMode = $_POST['TruncEndMode'];
	else $TruncEndMode = 1;
	$MaxTruncEnd = '';
	if($TruncEndMode == -1) $MaxTruncEnd = $_POST['MaxTruncEnd1'];
	if($TruncEndMode == 0) $MaxTruncEnd = $_POST['MaxTruncEnd2'];
	if($TruncEnd OR $MaxTruncEnd == '') {
		$MaxTruncEnd = 0;
		$TruncEndMode = 1;
		}
	fwrite($handle,$TruncEndMode."\n");
	fwrite($handle,$MaxTruncEnd."\n");
	
	if(isset($_POST['PivMode'])) $PivMode = $_POST['PivMode'];
	else $PivMode = -1;
	$PivPos = '';
	if($PivMode == -1) $PivPos = $_POST['PivPos1'];
	if($PivMode == 0) $PivPos = $_POST['PivPos2'];
	if($PivPos == '') {
		$PivPos = "0.0000";
		}
	fwrite($handle,$PivMode."\n");
	fwrite($handle,$PivPos."\n");
	
	$AlphaCtrlNr = $_POST['AlphaCtrlNr'];
	if($AlphaCtrlNr == '') $AlphaCtrlNr = -1;
	$AlphaCtrlChan = $_POST['AlphaCtrlChan'];
	if($AlphaCtrlChan == '') $AlphaCtrlChan = 0;
	if(!$AlphaCtrl) {
		$AlphaCtrlNr = -1;
		$AlphaCtrlChan = 0;
		}
	fwrite($handle,$AlphaCtrlNr."\n");
	fwrite($handle,$AlphaCtrlChan."\n");

	if(isset($_POST['OkTransp'])) $OkTransp = 1;
	else $OkTransp = 0;
	if(isset($_POST['OkArticul'])) $OkArticul = 1;
	else $OkArticul = 0;
	if(isset($_POST['OkVolume'])) $OkVolume = 1;
	else $OkVolume = 0;
	if(isset($_POST['OkPan'])) $OkPan = 1;
	else $OkPan = 0;
	if(isset($_POST['OkMap'])) $OkMap = 1;
	else $OkMap = 0;
	if(isset($_POST['OkVelocity'])) $OkVelocity = 1;
	else $OkVelocity = 0;
	
	fwrite($handle,$OkTransp."\n");
	fwrite($handle,$OkArticul."\n");
	fwrite($handle,$OkVolume."\n");
	fwrite($handle,$OkPan."\n");
	fwrite($handle,$OkMap."\n");
	fwrite($handle,$OkVelocity."\n");
	
	$PreRoll = '';
	if(isset($_POST['PreRollMode'])) {
		$PreRollMode = $_POST['PreRollMode'];
		if($PreRollMode == -1) $PreRoll = $_POST['PreRoll1'];
		if($PreRollMode == 0) $PreRoll = $_POST['PreRoll2'];
		}
	if($PreRoll == '') {
		$PreRoll = 0;
		$PreRollMode = -1;
		}
	$PostRoll = '';
	if(isset($_POST['PostRollMode'])) {
		$PostRollMode = $_POST['PostRollMode'];
		if($PostRollMode == -1) $PostRoll = $_POST['PostRoll1'];
		if($PostRollMode == 0) $PostRoll = $_POST['PostRoll2'];
		}
	if($PostRoll == '') {
		$PostRoll = 0;
		$PostRollMode = -1;
		}
	fwrite($handle,$PreRoll."\n");
	fwrite($handle,$PostRoll."\n");
	fwrite($handle,$PreRollMode."\n");
	fwrite($handle,$PostRollMode."\n");
	
	$BeforePeriod = '';
	if(isset($_POST['PeriodMode'])) {
		$PeriodMode = $_POST['PeriodMode'];
		if($PeriodMode == -1) $BeforePeriod = $_POST['BeforePeriod1'];
		if($PeriodMode == 0) $BeforePeriod = $_POST['BeforePeriod2'];
		}
	if($BeforePeriod == '') {
		$BeforePeriod = "0";
		$PeriodMode = -2;
		}
	fwrite($handle,$PeriodMode."\n");
	fwrite($handle,$BeforePeriod."\n");
	if(isset($_POST['ForceIntegerPeriod'])) $ForceIntegerPeriod = 1;
	else $ForceIntegerPeriod = 0;
	if(isset($_POST['DiscardNoteOffs'])) $DiscardNoteOffs = 1;
	else $DiscardNoteOffs = 0;
	fwrite($handle,$ForceIntegerPeriod."\n");
	fwrite($handle,$DiscardNoteOffs."\n");
	
	if(isset($_POST['StrikeAgain']))
		$StrikeAgain = $_POST['StrikeAgain'];
	else $StrikeAgain = 1;
	fwrite($handle,$StrikeAgain."\n");
	
	$CsoundInstr = '';
	if(isset($_POST['CsoundAssignedInstr'])) {
		$CsoundAssignedInstr = $_POST['CsoundAssignedInstr'];
		$CsoundInstr = $_POST['CsoundInstr'];
		}
	else $CsoundAssignedInstr = -1;
	if($CsoundInstr == '') $CsoundInstr = -1;
	fwrite($handle,$CsoundAssignedInstr."\n");
	fwrite($handle,$CsoundInstr."\n");
	if(isset($_POST['tempo']) AND $_POST['tempo'] > 0)
		$tempo = $_POST['tempo'];
	else $tempo = 1000000;
	fwrite($handle,$tempo."\n");
	
	fwrite($handle,"65535\n");
	fwrite($handle,"65535\n");
	fwrite($handle,"65535\n");
	$object_comment = $_POST['object_comment'];
	$object_comment = recode_tags($object_comment);
	$line = "<HTML>".$object_comment."</HTML>\n";
	fwrite($handle,$line."\n");
	fclose($handle);
	$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
	fclose($handle);
	}

echo "</p>";
echo link_to_help();

echo "<h2>Object prototype <big><span class=\"red-text\">".$object_name."</span></big></h2>";
// echo "<div style=\"float:right; background-color:white; padding-right:6px; padding-left:6px; border-radius: 12px;\">";
echo "<div class=\"thinborder\" style=\"float:right; padding-right:6px; padding-left:6px;\">";
echo "<p><span class=\"red-text\">➡</span> Don’t close the “<span class=\"green-text\">".$prototypes_name."</span>” page while editing this prototype!</p>";
echo "</div>";

$content = @file_get_contents($object_file,TRUE);
if(trim($content) == '') {
	exit("This prototype no longer exists.");
	}
$content = mb_convert_encoding($content,'UTF-8','UTF-8');
$extract_data = extract_data(TRUE,$content);
$source_file = $extract_data['objects'];
echo "<p class=\"green-text\">".$extract_data['headers']."<br />// Source: ".$source_file."</p>";
$content = $extract_data['content'];

$table = explode(chr(10),$content);
$object_param = array();
$i = 0; $imax = count($table) - 1;
$j = 0; // $cscore = FALSE;
do {
	$i++; $line = $table[$i];
	if($i == $imax) break;
	if(is_integer($pos=stripos($line,"<HTML>"))) break;
	$object_param[$j++] = $line;
	continue;
	}
while(TRUE);
$clean_line = str_ireplace("<HTML>",'',$line);
$clean_line = str_ireplace("</HTML>",'',$clean_line);
$object_comment = $clean_line;

$no_midi = TRUE;
if(file_exists($midi_bytes)) {
	$all_bytes = @file_get_contents($midi_bytes);
	if(strlen(trim($all_bytes)) > 0) $no_midi = FALSE;
	}

$message_create_sound = '';
if(isset($_POST['createcsound'])) {
	$csound_score = trim($_POST['csound_score']);
	if($csound_score <> '') {
		$message_create_sound = "<p><span class=\"red-text\">➡ First delete the current Csound score to replace it with a new one!</span></p>";
		}
	else {
		$Duration = intval($_POST['Duration']);
		if($no_midi) {
			$message_create_sound .= "<p><span class=\"red-text\">➡ </span>Cannot create Csound score because MIDI sequence is empty…</p>";
			}
		else {
			$data = $temp_dir.$temp_folder.SLASH.$object_name.".bpda";
			$alphabet = $temp_dir.$temp_folder.SLASH."temp.bpho";
			$application_path = $bp_application_path;
			$command = $application_path."bp play";
			$command .= " -da ".$data;
			$command .= " -ho ".$alphabet;
			$command .= " -mi \"".$prototypes_file."\"";
			if($CsoundInstruments_file <> '') $command .= " -cs \"".$CsoundInstruments_file."\"";
		//	$command .= " -d --csoundout ".$csound_file;
			$command .= " --csoundout ".$csound_file;
			// $command .= " --traceout ".$tracefile;
			$message_create_sound = "<p id=\"timespan\"><span class=\"red-text\">➡ </span>MIDI codes to Csound conversion…<p>";
			$message_create_sound .= "<p style=\"color:red;\"><small>".$command."</small></p>";
			$no_error = FALSE;
			$o = send_to_console($command);
			$n_messages = count($o);
			if($n_messages > 0) {
				for($i=$j=0; $i < $n_messages; $i++) {
			//		echo $o[$i]."<br />";
					$mssg[$j] = $o[$i];
					$mssg[$j] = clean_up_encoding(FALSE,TRUE,$mssg[$j]);
					if(is_integer($pos=strpos($mssg[$j],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
					$j++;
					}
				}
			if(!$no_error) {
				$message_create_sound .= "<p><span class=\"red-text\">➡ </span>Errors in the conversion:<br /><small>";
				for($i=0; $i < count($mssg); $i++) {
					$message_create_sound .= "&nbsp;&nbsp;&nbsp;".$mssg[$i]."<br />";
					}
				$message_create_sound .= "</small></p>";
				}
			else sleep(5);
			}
		}
	}
	
if(isset($_POST['expression'])) $expression = trim($_POST['expression']);
if(isset($_POST['playexpression'])) {
	if($expression == '') {
		echo "<p id=\"timespan\"><span class=\"red-text\">➡ Cannot play empty expression!</span></p>";
		}
	else {
		echo "<p id=\"timespan\"><span class=\"red-text\">➡ Playing:</span> <span class=\"green-text\"><big>".$expression."</big></span></p>";
	//	echo "temp_folder = ".$temp_folder."<br />";
		$data = $temp_dir.$temp_folder.SLASH."expression.bpda";
		$alphabet = $temp_dir.$temp_folder.SLASH."temp.bpho";
		// $tracefile = $temp_folder."/trace.txt";
		$handle = fopen($data,"w");
		$file_header = $top_header."\n// Data saved as \"expression.bpda\". Date: ".gmdate('Y-m-d H:i:s');
	//	fwrite($handle,$file_header."\n");
		fwrite($handle,$expression."\n");
		fclose($handle);
		$application_path = $bp_application_path;
		$command = $application_path."bp play";
		$command .= " -da ".$data;
		$command .= " -al ".$alphabet;
		$command .= " -so \"".$prototypes_file."\"";
		// if($CsoundInstruments_file <> '') $command .= " -cs \"".$CsoundInstruments_file."\"";
		$command .= " --rtmidi ";
		// $command .= " --traceout ".$tracefile;
		echo "<p style=\"color:red;\"><small>".$command."</small></p>";
		$no_error = FALSE;
		$o = send_to_console($command);
		$n_messages = count($o);
		if($n_messages > 0) {
			for($i=0; $i < $n_messages; $i++) {
				$mssg = $o[$i];
				$mssg = clean_up_encoding(FALSE,TRUE,$mssg);
				if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
				}
			}
		}
	}

// ---------- EDIT THIS PROTOTYPE ------------

$h_image = fopen($image_file,"w");
fwrite($h_image,"<?php\n");

echo "<form method=\"post\" action=\"prototype.php\" enctype=\"multipart/form-data\">";

echo "<p style=\"text-align:left;\"><input class=\"save big\" type=\"submit\" name=\"savethisprototype\" value=\"SAVE THIS PROTOTYPE\"></p>";

/* echo "<p style=\"text-align:left;\"><input class=\"edit\" type=\"submit\" name=\"playexpression\" value=\"PLAY THIS EXPRESSION (real-time MIDI):\">&nbsp;➡&nbsp;<input type=\"text\" name=\"expression\" size=\"30\" value=\"".$expression."\"></p>"; */

echo "<input type=\"hidden\" name=\"object_name\" value=\"".$object_name."\">";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"object_file\" value=\"".$object_file."\">";
echo "<input type=\"hidden\" name=\"source_file\" value=\"".$source_file."\">";
echo "<input type=\"hidden\" name=\"prototypes_file\" value=\"".$prototypes_file."\">";
echo "<input type=\"hidden\" name=\"prototypes_name\" value=\"".$prototypes_name."\">";
echo "<input type=\"hidden\" name=\"CsoundInstruments_file\" value=\"".$CsoundInstruments_file."\">";
echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";

// echo "<input type=\"hidden\" name=\"old_tempo\" value=\"".$old_tempo."\">";

$object_comment = recode_tags($object_comment);
$size = strlen($object_comment);
echo "<p>Comment on this prototype = <input type=\"text\" name=\"object_comment\" size=\"".$size."\" value=\"".$object_comment."\"></p>";
echo "<p>OBJECT TYPE</p>";
$j = 0;
$object_type = $object_param[$j++];
echo "<input type=\"checkbox\" name=\"object_type1\"";
   if($object_type == 1 OR $object_type == 5) echo " checked";
   echo "> MIDI sequence<br />";
echo "<input type=\"checkbox\" name=\"object_type4\"";
   if($object_type > 3) echo " checked";
   echo "> Csound score";

$resolution = $object_param[$j];
if($resolution == '' OR $resolution == 0) $resolution = 1;
// $resolution = intval($resolution);
if($resolution == 0) $resolution = 1;
echo "<p>Resolution: 1 tick = <input type=\"text\" name=\"object_param_".($j++)."\" size=\"5\" value=\"".$resolution."\"> ms</p>";

$MIDIchannel = $object_param[$j++];
// echo "MIDIchannel j = ".($j -1)."<br />";
// echo "<p>Force to MIDI channel = <input type=\"text\" name=\"object_param_".($j++)."\" size=\"5\" value=\"".$MIDIchannel."\"> (zero means using current channel)</p>";
store($h_image,"MIDIchannel",$MIDIchannel);

echo "<p>MIDI CHANNEL</p>";

echo "<p><input type=\"radio\" name=\"channel_force\" value=\"0\"";
if($MIDIchannel == 0) echo " checked";
echo "> Force to current channel<br />";
echo "<input type=\"radio\" name=\"channel_force\" value=\"-1\"";
if($MIDIchannel == -1) echo " checked";
echo "> Do not change channels<br />";
echo "<input type=\"radio\" name=\"channel_force\" value=\"1\"";
if($MIDIchannel > 0) echo " checked";
echo "> Force to channel&nbsp;";
if($MIDIchannel > 0) $value = $MIDIchannel;
else $value = '';
echo "<input type=\"text\" name=\"MIDIchannel\" size=\"3\" value=\"". $value."\"></p>";

echo "<p>TIME REFERENCE</p>";

$Tref = $object_param[$j++];
// echo "object_param[j++] = ".$Tref."<br />";
// $Trefc = $object_param[$j++];
// $Tref = $Trefc * $object_param[1];
// echo "object_param[1] = ".$object_param[1]."<br />";
if(isset($_POST['Tref'])) $Tref = $_POST['Tref'];
// echo "Tref = ".$Tref."<br />";
if(isset($_FILES['mid_upload']) AND isset($_POST['tempo'])) $Tref = $tempo / 1000;
else $tempo = 1000 * $Tref;
// echo "Tref = ".$Tref."<br />";
echo "Tref = <input type=\"text\" name=\"Tref\" size=\"10\" value=\"".$Tref."\"> ms ➡ ";
if($Tref > 0) echo "this object is <span class=\"green-text\">striated</span> (it has a pivot) and Tref is the period of its reference metronome.<br /><i>Set this value to zero if the object is smooth (no pivot).</i><br />";
else echo "this object is <span class=\"green-text\">smooth</span> (it has no pivot)<br />";

$object_quantization = $object_param[$j];
if(intval($object_quantization) == $object_quantization) $object_quantization = intval($object_quantization);
echo "<p>Quantization = <input type=\"text\" name=\"object_param_".$j++."\" size=\"5\" value=\"".$object_quantization."\"> ms<br /><i>Zero means no quantization, i.e. the duration of this object may decrease without limit in fast movements.</i></p>";

$string = $object_param[$j++];
$k = 0;
// echo $string."<br />";
$pivbeg = $string[$k++];
$pivend = $string[$k++];
$pivbegon = $string[$k++];
$pivendoff = $string[$k++];
$pivcent = $string[$k++];
$pivcentonoff = $string[$k++];
echo "<p>PIVOT</p>";
if($Tref > 0) echo "<p>This object has a pivot — it is <i>striated</i> — because Tref > 0 (see above).</p>";
else echo "<p>This object has NO pivot — it is <i>smooth</i> — because Tref = 0<br /><i>This pivot setting is therefore irrelevant.</i></p>";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"0\"";
if($pivbeg == 1) echo " checked";
echo ">Beginning<br />";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"4\"";
if($pivcent == 1) echo " checked";
echo ">Middle<br />";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"1\"";
if($pivend == 1) echo " checked";
echo ">End<br />";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"2\"";
if($pivbegon == 1) echo " checked";
echo ">First NoteOn<br />";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"5\"";
if($pivcentonoff == 1) echo " checked";
echo ">Middle NoteOn/Off<br />";
echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"3\"";
if($pivendoff == 1) echo " checked";
echo ">Last NoteOff<br />";

store($h_image,"pivbeg",$pivbeg);
store($h_image,"pivcent",$pivcent);
store($h_image,"pivend",$pivend);
store($h_image,"pivbegon",$pivbegon);
store($h_image,"pivcentonoff",$pivcentonoff);
store($h_image,"pivendoff",$pivendoff);
	
$okrescale = $string[$k++];
$FixScale = $string[$k++];
$OkExpand = $string[$k++];
$OkCompress = $string[$k++];
$OkRelocate = $string[$k++];
store($h_image,"OkRelocate",$OkRelocate);
$BreakTempo = $string[$k++];

$ContBeg = $string[$k++];
$ContEnd = $string[$k++];
store($h_image,"ContBeg",$ContBeg);
store($h_image,"ContEnd",$ContEnd);

$CoverBeg = $string[$k++];
$CoverEnd = $string[$k++];
$TruncBeg = $string[$k++];
$TruncEnd = $string[$k++];
store($h_image,"CoverBeg",$CoverBeg);
store($h_image,"CoverEnd",$CoverEnd);
store($h_image,"TruncBeg",$TruncBeg);
store($h_image,"TruncEnd",$TruncEnd);

$pivspec = $string[$k++];
store($h_image,"pivspec",$pivspec);

if(isset($string[$k]))
	$AlphaCtrl = $string[$k];
else $AlphaCtrl = TRUE;
$k++;

$RescaleMode = $object_param[$j++];

$AlphaMin = $object_param[$j++];
$AlphaMax = $object_param[$j++];

$DelayMode = $object_param[$j++];
$MaxDelay = $object_param[$j++];
$ForwardMode = $object_param[$j++];
$MaxForward = $object_param[$j++];

$BreakTempoMode = $object_param[$j++];

$x = $object_param[$j++];
if(isset($_POST['division'])) $division = $_POST['division'];
else if($x > 1) {
	echo "division = x = ".$x." (j = ".$j.")<br />";
	$division = $x;
	}
else $division = 1000;
//echo "<input type=\"hidden\" name=\"division\" value=\"".$division."\">";
$ContBegMode = $object_param[$j++];
$MaxBegGap = $object_param[$j++];
$ContEndMode = $object_param[$j++];
$MaxEndGap = $object_param[$j++];

$CoverBegMode = $object_param[$j++];
$MaxCoverBeg = $object_param[$j++];
$CoverEndMode = $object_param[$j++];
$MaxCoverEnd = $object_param[$j++];
$TruncBegMode = $object_param[$j++];
$MaxTruncBeg = $object_param[$j++];
$TruncEndMode = $object_param[$j++];
$MaxTruncEnd = $object_param[$j++];

$PivMode = $object_param[$j++];
$PivPos = $object_param[$j++];
store($h_image,"PivMode",$PivMode);
store($h_image,"PivPos",$PivPos);

$AlphaCtrlNr = $object_param[$j++];
$AlphaCtrlChan = $object_param[$j++];

$OkTransp = $object_param[$j++];
$OkArticul = $object_param[$j++];
$OkVolume = $object_param[$j++];
$OkPan = $object_param[$j++];
$OkMap = $object_param[$j++];
$OkVelocity = $object_param[$j++];

$PreRoll = $object_param[$j++];
$PostRoll = $object_param[$j++];
$PreRollMode = $object_param[$j++];
$PostRollMode = $object_param[$j++];

$PeriodMode = $object_param[$j++];
$BeforePeriod = $object_param[$j++];
$ForceIntegerPeriod = $object_param[$j++];
$DiscardNoteOffs = $object_param[$j++];

$StrikeAgain = $object_param[$j++];

$CsoundAssignedInstr = $object_param[$j++];
$CsoundInstr = $object_param[$j++];

$j++;
if(!is_numeric($tempo)) {
	echo "<p style=\"color:red;\">WARNING: you are trying to edit an obsolete version of the ‘-mi’ file. Load and save it again in BP2.9.8!</p>";
	$j -= 4;
	}
if($tempo == 0) $tempo = 1000000;
$red = $green = $blue = 65535;
if($j >= (count($object_param) - 1)) {
	echo "<p style=\"color:red;\">WARNING: you are trying to edit an obsolete version of the ‘-mi’ file. Load and save it again in BP2.9.8!</p>";
	}
else {
/*	if(isset($object_param[$j++])) $red = $object_param[$j];
	if(isset($object_param[$j++])) $green = $object_param[$j];
	if(isset($object_param[$j++])) blue = $object_param[$j]; */
	}

$silence_before_warning = '';
if(isset($_POST['silence_before'])) {
	$PreRoll -= $_POST['SilenceBefore'];
	$silence_before_warning = "<span class=\"green-text\">Inserting a silence before the object amounts to adding a negative value to its pre-roll.<br />The duration remains unchanged but the pre-roll is now: ".$PreRoll." ms</span>";
	}

$silence_after_warning = '';
if(isset($_POST['silence_after'])) {
	$PostRoll += $_POST['SilenceAfter'];
	$silence_after_warning = "<span class=\"green-text\">Appending a silence after the object amounts to adding a positive value to its post-roll.<br />The duration remains unchanged but the post-roll is now: ".$PostRoll." ms</span>";
	}

echo "<input type=\"radio\" name=\"Pivot_mode\" value=\"18\"";
if($pivspec == 1) echo " checked";
echo ">Set pivot:<br />";
echo "&nbsp;<input type=\"radio\" name=\"PivMode\" value=\"-1\"";
if($pivspec == 1 AND $PivMode == -1) {
	echo " checked";
	$value = intval($PivPos);
	}
else $value = '';
echo ">&nbsp;<input type=\"text\" name=\"PivPos1\" size=\"5\" value=\"".$value."\"> ms from beginning<br />";
echo "&nbsp;<input type=\"radio\" name=\"PivMode\" value=\"0\"";
$value = '';
if($pivspec == 1 AND $PivMode == 0) {
	echo " checked";
	$value = intval($PivPos);
	}
echo ">&nbsp;<input type=\"text\" name=\"PivPos2\" size=\"5\" value=\"".$value."\"> % duration from beginning<br /><br />";

store($h_image,"pivendoff",$pivspec);
store($h_image,"pivendoff",$PivMode);
store($h_image,"pivendoff",$PivPos);

echo "<p>RESCALING</p>";
$value_min = $value_max = $dilation_controller = $dilation_channel = $value_controller = $value_channel = '';
$dilation_ok = FALSE;
if(!$FixScale AND !$OkExpand AND !$OkCompress) {
	$dilation_ok = TRUE;
	if($AlphaMin > 0) $value_min = intval($AlphaMin);
	if($AlphaMax > 0) $value_max = intval($AlphaMax);
	}
	
$scalable = FALSE;
if(($okrescale OR $OkExpand OR $OkCompress OR $dilation_ok) AND !$FixScale) $scalable = TRUE;

echo "<input type=\"radio\" name=\"Rescale\" value=\"okrescale\"";
if($okrescale OR $OkExpand OR $OkCompress) echo " checked";
echo ">OK rescale<br />";
echo "&nbsp;<input type=\"checkbox\" name=\"OkExpand\" value=\"OkExpand\"";
if($OkExpand) echo " checked";
echo ">Expand at will<br />";
echo "&nbsp;<input type=\"checkbox\" name=\"OkCompress\" value=\"OkCompress\"";
if($OkCompress) echo " checked";
echo ">Compress at will<br />";
echo "<input type=\"radio\" name=\"Rescale\" value=\"neverrescale\"";
if($FixScale) echo " checked";
echo ">Never rescale<br />";

echo "<input type=\"radio\" name=\"Rescale\" value=\"dilationrange\"";
if($dilation_ok) echo " checked";
echo ">Dilation ratio range from";
echo "&nbsp;<input type=\"text\" name=\"AlphaMin\" size=\"5\" value=\"".$value_min."\"> to <input type=\"text\" name=\"AlphaMax\" size=\"5\" value=\"".$value_max."\"> %<br />";

store($h_image,"OkExpand",$OkExpand);
store($h_image,"OkCompress",$OkCompress);
store($h_image,"FixScale",$FixScale);
if($dilation_ok) store($h_image,"dilation_mssg","Dilation ratio range from ".$value_min." to ".$value_max." %");


$alpha_controller = FALSE;
if($AlphaCtrl AND $AlphaCtrlNr > 0 AND $AlphaCtrlChan > 0) {
	$alpha_controller = TRUE;
	$value_controller = $AlphaCtrlNr;
	$value_channel = $AlphaCtrlChan;
	}
echo "<input type=\"checkbox\" name=\"AlphaCtrl\"";
if($alpha_controller) echo " checked";
echo ">Send dilation ratio to controller ";
echo "&nbsp;<input type=\"text\" name=\"AlphaCtrlNr\" size=\"5\" value=\"".$value_controller."\"> channel <input type=\"text\" name=\"AlphaCtrlChan\" size=\"5\" value=\"".$value_channel."\"><br />";

echo "<p>RescaleMode = <input type=\"text\" name=\"RescaleMode\" size=\"5\" value=\"".$RescaleMode."\"> ???</p>";

echo "<p>MIDI CHANGES</p>";

echo "<input type=\"checkbox\" name=\"OkTransp\"";
if($OkTransp) echo " checked";
echo "> Accept transposition<br />";
echo "<input type=\"checkbox\" name=\"OkArticul\"";
if($OkArticul) echo " checked";
echo "> Accept articulation<br />";
echo "<input type=\"checkbox\" name=\"OkVolume\"";
if($OkVolume) echo " checked";
echo "> Accept volume changes<br />";
echo "<input type=\"checkbox\" name=\"OkPan\"";
if($OkPan) echo " checked";
echo "> Accept panoramic changes<br />";
echo "<input type=\"checkbox\" name=\"OkMap\"";
if($OkMap) echo " checked";
echo "> Accept key changes<br />";
echo "<input type=\"checkbox\" name=\"OkVelocity\"";
if($OkVelocity) echo " checked";
echo "> Accept velocity changes<br />";

echo "<p>LOCATION</p>";
echo "<input type=\"radio\" name=\"OkRelocate\" value=\"1\"";
if($OkRelocate == 1) echo " checked";
echo ">Relocate at will<br />";
echo "<input type=\"radio\" name=\"OkRelocate\" value=\"0\"";
if($OkRelocate == 0) echo " checked";
echo ">Do not relocate at will<br /><br />";

echo "<input type=\"radio\" name=\"DelayMode\" value=\"-1\"";
if(!$OkRelocate AND $DelayMode == -1) {
	echo " checked";
	$value = $MaxDelay;
	}
else $value = '';
echo ">Allow delay";
echo "&nbsp;<input type=\"text\" name=\"MaxDelay1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"DelayMode\" value=\"0\"";
if(!$OkRelocate AND $DelayMode == 0) {
	echo " checked";
	$value = $MaxDelay;
	}
else $value = '';
echo ">Allow delay";
echo "&nbsp;<input type=\"text\" name=\"MaxDelay2\" size=\"5\" value=\"".$value."\"> % of duration<br />";

echo "<input type=\"radio\" name=\"ForwardMode\" value=\"-1\"";
if(!$OkRelocate AND $ForwardMode == -1) {
	echo " checked";
	$value = $MaxForward;
	}
else $value = '';
echo ">Allow forward";
echo "&nbsp;<input type=\"text\" name=\"MaxForward1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"ForwardMode\" value=\"0\"";
if(!$OkRelocate AND $ForwardMode == 0) {
	echo " checked";
	$value = $MaxForward;
	}
else $value = '';
echo ">Allow forward";
echo "&nbsp;<input type=\"text\" name=\"MaxForward2\" size=\"5\" value=\"".$value."\"> % of duration";

echo "<p>BREAK TEMPO (ORGANUM)</p>";
// echo "(BreakTempoMode = ".$BreakTempoMode." ???)<br />";
echo "<input type=\"hidden\" name=\"BreakTempoMode\" value=\"".$BreakTempoMode."\">";

echo "<input type=\"radio\" name=\"BreakTempo\" value=\"0\"";
if($BreakTempo == 0) echo " checked";
echo ">Never break after this object<br />";
echo "<input type=\"radio\" name=\"BreakTempo\" value=\"1\"";
if($BreakTempo == 1) echo " checked";
echo ">Break at will";
store($h_image,"BreakTempo",$BreakTempo);

echo "<p>FORCE CONTINUITY (BEGINNING)</p>";
echo "<input type=\"radio\" name=\"ContBeg\" value=\"0\"";
if($ContBeg == 0) echo " checked";
echo ">Do not force<br />";
echo "<input type=\"radio\" name=\"ContBeg\" value=\"1\"";
if($ContBeg == 1) echo " checked";
echo ">Force<br />";

echo "<input type=\"radio\" name=\"ContBegMode\" value=\"-1\"";
if($ContBeg AND $ContBegMode == -1) {
	echo " checked";
	$value = $MaxBegGap;
	}
else $value = '';
echo ">Allow gap";
echo "&nbsp;<input type=\"text\" name=\"MaxBegGap1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"ContBegMode\" value=\"0\"";
if($ContBeg AND $ContBegMode == 0) {
	echo " checked";
	$value = $MaxBegGap;
	}
else $value = '';
echo ">Allow gap";
echo "&nbsp;<input type=\"text\" name=\"MaxBegGap2\" size=\"5\" value=\"".$value."\"> % of duration";
store($h_image,"ContBegMode",$ContBegMode);
store($h_image,"MaxBegGap",$MaxBegGap);

echo "<p>FORCE CONTINUITY (END)</p>";
echo "<input type=\"radio\" name=\"ContEnd\" value=\"0\"";
if($ContEnd == 0) echo " checked";
echo ">Do not force<br />";
echo "<input type=\"radio\" name=\"ContEnd\" value=\"1\"";
if($ContEnd == 1) echo " checked";
echo ">Force<br />";

echo "<input type=\"radio\" name=\"ContEndMode\" value=\"-1\"";
if($ContEnd AND $ContEndMode == -1) {
	echo " checked";
	$value = $MaxEndGap;
	}
else $value = '';
echo ">Allow gap";
echo "&nbsp;<input type=\"text\" name=\"MaxEndGap1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"ContEndMode\" value=\"0\"";
if($ContEnd AND $ContEndMode == 0) {
	echo " checked";
	$value = $MaxEndGap;
	}
else $value = '';
echo ">Allow gap";
echo "&nbsp;<input type=\"text\" name=\"MaxEndGap2\" size=\"5\" value=\"".$value."\"> % of duration";
store($h_image,"ContEndMode",$ContEndMode);
store($h_image,"MaxEndGap",$MaxEndGap);

echo "<p>COVER BEGINNING</p>";
if(!$CoverBeg AND $MaxCoverBeg == '') $MaxCoverBeg = 0;
echo "<input type=\"radio\" name=\"CoverBeg\" value=\"1\"";
if($CoverBeg == 1) echo " checked";
echo ">Cover at will<br />";
echo "<input type=\"radio\" name=\"CoverBeg\" value=\"0\"";
if($CoverBeg == 0) echo " checked";
echo ">Never cover<br />";
echo "<input type=\"radio\" name=\"CoverBegMode\" value=\"-1\"";
if(!$CoverBeg AND $CoverBegMode == -1) {
	echo " checked";
	$value = $MaxCoverBeg;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxCoverBeg1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"CoverBegMode\" value=\"0\"";
if(!$CoverBeg AND $CoverBegMode == 0) {
	echo " checked";
	$value = $MaxCoverBeg;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxCoverBeg2\" size=\"5\" value=\"".$value."\"> % of duration<br />";
// echo "MaxCoverBeg = ".$MaxCoverBeg."<br />";
//if(!$CoverBeg) $CoverBegMode = $MaxCoverBeg = 0;
store($h_image,"CoverBeg",$CoverBeg);
store($h_image,"CoverBegMode",$CoverBegMode);
store($h_image,"MaxCoverBeg",$MaxCoverBeg);

echo "<p>COVER END</p>";
if(!$CoverEnd AND $MaxCoverEnd == '') $MaxCoverEnd = 0;
echo "<input type=\"radio\" name=\"CoverEnd\" value=\"1\"";
if($CoverEnd == 1) echo " checked";
echo ">Cover at will<br />";
echo "<input type=\"radio\" name=\"CoverEnd\" value=\"0\"";
if($CoverEnd == 0) echo " checked";
echo ">Never cover<br />";
echo "<input type=\"radio\" name=\"CoverEndMode\" value=\"-1\"";
if(!$CoverEnd AND $CoverEndMode == -1) {
	echo " checked";
	$value = $MaxCoverEnd;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxCoverEnd1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"CoverEndMode\" value=\"0\"";
if(!$CoverEnd AND $CoverEndMode == 0) {
	echo " checked";
	$value = $MaxCoverEnd;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxCoverEnd2\" size=\"5\" value=\"".$value."\"> % of duration";
store($h_image,"CoverEnd",$CoverEnd);
store($h_image,"CoverEndMode",$CoverEndMode);
store($h_image,"MaxCoverEnd",$MaxCoverEnd);
	
echo "<p>TRUNCATE BEGINNING</p>";
echo "<input type=\"radio\" name=\"TruncBeg\" value=\"1\"";
if($TruncBeg == 1) echo " checked";
echo ">Truncate at will<br />";
echo "<input type=\"radio\" name=\"TruncBeg\" value=\"0\"";
if($TruncBeg == 0) echo " checked";
echo ">Do not truncate<br />";

echo "<input type=\"radio\" name=\"TruncBegMode\" value=\"-1\"";
if(!$TruncBeg AND $TruncBegMode == -1) {
	echo " checked";
	$value = $MaxTruncBeg;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxTruncBeg1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"TruncBegMode\" value=\"0\"";
if(!$TruncBeg AND $TruncBegMode == 0) {
	echo " checked";
	$value = $MaxTruncBeg;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxTruncBeg2\" size=\"5\" value=\"".$value."\"> % of duration";
store($h_image,"TruncBegMode",$TruncBegMode);
store($h_image,"MaxTruncBeg",$MaxTruncBeg);

echo "<p>TRUNCATE END</p>";
echo "<input type=\"radio\" name=\"TruncEnd\" value=\"1\"";
if($TruncEnd == 1) echo " checked";
echo ">Truncate at will<br />";
echo "<input type=\"radio\" name=\"TruncEnd\" value=\"0\"";
if($TruncEnd == 0) echo " checked";
echo ">Do not truncate<br />";

echo "<input type=\"radio\" name=\"TruncEndMode\" value=\"-1\"";
if(!$TruncEnd AND $TruncEndMode == -1) {
	echo " checked";
	$value = $MaxTruncEnd;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxTruncEnd1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"TruncEndMode\" value=\"0\"";
if(!$TruncEnd AND $TruncEndMode == 0) {
	echo " checked";
	$value = $MaxTruncEnd;
	}
else $value = '';
echo ">Not more than";
echo "&nbsp;<input type=\"text\" name=\"MaxTruncEnd2\" size=\"5\" value=\"".$value."\"> % of duration";
store($h_image,"TruncEndMode",$TruncEndMode);
store($h_image,"MaxTruncEnd",$MaxTruncEnd);

echo "<p>PREROLL - POSTROLL</p>";
echo "<input type=\"radio\" name=\"PreRollMode\" value=\"-1\"";
if($PreRollMode == -1) {
	echo " checked";
	$value = $PreRoll;
	}
else $value = '';
echo ">Pre-roll";
echo "&nbsp;<input type=\"text\" name=\"PreRoll1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"PreRollMode\" value=\"0\"";
if($PreRollMode == 0) {
	echo " checked";
	$value = $PreRoll;
	}
else $value = '';
echo ">Pre-roll";
echo "&nbsp;<input type=\"text\" name=\"PreRoll2\" size=\"5\" value=\"".$value."\"> % of duration<br />";
	
echo "<input type=\"radio\" name=\"PostRollMode\" value=\"-1\"";
if($PostRollMode == -1) {
	echo " checked";
	$value = $PostRoll;
	}
else $value = '';
echo ">Post-roll";
echo "&nbsp;<input type=\"text\" name=\"PostRoll1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"PostRollMode\" value=\"0\"";
if($PostRollMode == 0) {
	echo " checked";
	$value = $PostRoll;
	}
else $value = '';
echo ">Post-roll";
echo "&nbsp;<input type=\"text\" name=\"PostRoll2\" size=\"5\" value=\"".$value."\"> % of duration";

echo "<p>CYCLIC</p>";
echo "<input type=\"radio\" name=\"PeriodMode\" value=\"-2\"";
if($PeriodMode == -2) {
	echo " checked";
	}
echo ">No period<br />";
echo "<input type=\"radio\" name=\"PeriodMode\" value=\"-1\"";
if($PeriodMode == -1) {
	echo " checked";
	$value = $BeforePeriod;
	}
else $value = '';
echo ">Periodical after";
echo "&nbsp;<input type=\"text\" name=\"BeforePeriod1\" size=\"5\" value=\"".$value."\"> ms<br />";
echo "<input type=\"radio\" name=\"PeriodMode\" value=\"0\"";
if($PeriodMode == 0) {
	echo " checked";
	$value = $BeforePeriod;
	}
else $value = '';
echo ">Periodical after";
echo "&nbsp;<input type=\"text\" name=\"BeforePeriod2\" size=\"5\" value=\"".$value."\"> % of duration<br />";
store($h_image,"PeriodMode",$PeriodMode);
store($h_image,"BeforePeriod",$BeforePeriod);

echo "<input type=\"checkbox\" name=\"ForceIntegerPeriod\"";
if($ForceIntegerPeriod) echo " checked";
echo ">Force integer number of periods<br />";
echo "<input type=\"checkbox\" name=\"DiscardNoteOffs\"";
if($DiscardNoteOffs) echo " checked";
echo ">Discard NoteOff’s except in last period";

echo "<p>STRIKE MODE</p>";
echo "<input type=\"radio\" name=\"StrikeAgain\" value=\"1\"";
if($StrikeAgain == 1) echo " checked";
echo ">Strike again NoteOn’s<br />";
echo "<input type=\"radio\" name=\"StrikeAgain\" value=\"0\"";
if($StrikeAgain == 0) echo " checked";
echo ">Don’t strike again NoteOn’s<br />";
echo "<input type=\"radio\" name=\"StrikeAgain\" value=\"-1\"";
if($StrikeAgain == -1) echo " checked";
echo ">Strike NoteOn’s according to default";

echo "<p>MIDI TO CSOUND CONVERSION</p>";
echo "<input type=\"radio\" name=\"CsoundAssignedInstr\" value=\"0\"";
if($CsoundAssignedInstr == 0) echo " checked";
echo ">Force to current instrument<br />";
echo "<input type=\"radio\" name=\"CsoundAssignedInstr\" value=\"-1\"";
if($CsoundAssignedInstr == -1 AND $CsoundInstr == -1) echo " checked";
echo ">Do not change instrument<br />";
echo "<input type=\"radio\" name=\"CsoundAssignedInstr\" value=\"-1\"";
if($CsoundAssignedInstr == -1 AND $CsoundInstr <> -1) {
	echo " checked";
	$value = $CsoundInstr;
	}
else $value = '';
echo ">Force to instrument";
echo "&nbsp;<input type=\"text\" name=\"CsoundInstr\" size=\"5\" value=\"".$value."\"><br />";
echo "<input type=\"hidden\" name=\"tempo\" value=\"".$tempo."\">";
	
$kmax = 0;
$time_max_midi = 0;
$no_midi = FALSE;
if(isset($_POST['delete_midi'])) {
	@unlink($midi_bytes);
	@unlink($midi_text);
	@unlink($mf2t);
	$no_midi = TRUE;
	}

if(isset($_POST['cancel'])) {
	if(file_exists($midi_bytes.".old")) copy($midi_bytes.".old",$midi_bytes);
	@unlink($midi_bytes.".old");
	if(file_exists($midi_text.".old")) copy($midi_text.".old",$midi_text);
	@unlink($midi_text.".old");
	if(file_exists($mf2t.".old")) copy($mf2t.".old",$mf2t);
	@unlink($mf2t.".old");
	}

$no_midi = TRUE;
if(file_exists($midi_bytes)) {
	$all_bytes = @file_get_contents($midi_bytes);
	if(strlen(trim($all_bytes)) > 0) $no_midi = FALSE;
	}

$new_midi = FALSE;
if(count($midi_text_bytes) > 0) {
	$new_midi = TRUE;
	$no_midi = FALSE;
	}
else if(!$no_midi) {
	$all_bytes = @file_get_contents($midi_bytes);
	$table_bytes = explode(chr(10),$all_bytes);
	$midi_text_bytes = array();
	for($k = 1; $k < count($table_bytes); $k++) {
		$byte = trim($table_bytes[$k]);
		if($byte == '') break;
		$midi_text_bytes[$k-1] = $byte;
		}
	}
$kmax = count($midi_text_bytes);
if($kmax == 0) $no_midi = TRUE;
// echo "kmax = ".$kmax."<br />";

if(isset($_POST['suppress_allnotes_off'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		if($code >= 176 AND $code < 192) {
			$ctrl = $midi_text_bytes[$k+1] % 256;
			if($ctrl == 123) $k += 2;
			else $new_midi_code[] = $byte;
			}
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if(isset($_POST['add_allnotes_off'])) {
	$new_midi_code = array();
	$time_max_midi = 0;
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($time > $time_max_midi) $time_max_midi = $time;
		$new_midi_code[$k] = $byte;
		}
	for($channel = 0; $channel < 16; $channel++) {
		$code = 176 + $channel;
		$byte = $code + (256 * $time_max_midi);
	//	echo $channel." -> ".$byte."<br />";
		$new_midi_code[$k++] = $byte;
		$code = 123;
		$byte = $code + (256 * $time_max_midi);
		$new_midi_code[$k++] = $byte;
		$code = 0;
		$byte = $code + (256 * $time_max_midi);
		$new_midi_code[$k++] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if(isset($_POST['quantize_NoteOn'])) {
	$test_quantize = FALSE;
	$NoteOnQuantize = intval($_POST['NoteOnQuantize']);
	if($NoteOnQuantize > 0) {
		$step = $Tref / $NoteOnQuantize;
		$new_midi_code = array();
		for($k = 0; $k < $kmax; $k++) {
			$byte = $midi_text_bytes[$k];
			$code = $byte % 256;
			$time = ($byte - $code) / 256;
			if($code >= 128 AND $code < 160) { // NoteOn or NoteOff
				$frames = intval($time / $step);
				if($test_quantize) echo $time."ms -> ".$frames." frames<br />";
				$time_this_event = round($frames * $step);
				$byte = $code + (256 * $time_this_event);
				if($test_quantize) echo "-> ".$time_this_event."ms -> ".$byte."<br />";
				$new_midi_code[] = $byte;
				$byte = $midi_text_bytes[++$k];
				$code = $byte % 256;
				$byte = $code + (256 * $time_this_event);
				if($test_quantize) echo "-> ".$time_this_event."ms -> ".$byte."<br />";
				$new_midi_code[] = $byte;
				$byte = $midi_text_bytes[++$k];
				$code = $byte % 256;
				$byte = $code + (256 * $time_this_event);
				if($test_quantize) echo "-> ".$time_this_event."ms -> ".$byte."<br />";
				$new_midi_code[] = $byte;
				}
			else {
				if($test_quantize) echo "-> ".$byte."<br />";
				$new_midi_code[] = $byte;
				}
			}
		$midi_text_bytes = array();
		$handle_bytes = fopen($midi_bytes,"w");
		fwrite($handle_bytes,$kmax."\n");
		for($k = 0; $k < $kmax; $k++) {
			$byte = $new_midi_code[$k];
			fwrite($handle_bytes,$byte."\n");
			$midi_text_bytes[$k] = $byte;
			}
		fclose($handle_bytes);
		}
	}

$flatten_all = FALSE;
if(isset($_POST['adjust_duration']) OR isset($_POST['adjust_beats'])) {
	$NewDuration = round($_POST['NewDuration']);
	$NewBeats = $_POST['NewBeats'];
	if(isset($_POST['adjust_duration']) AND $NewDuration == 0) $flatten_all = TRUE;
	if(isset($_POST['adjust_beats']) AND $NewBeats == 0) $flatten_all = TRUE;
	}
	
if($flatten_all OR isset($_POST['suppress_pressure'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		if($code >= 208 AND $code < 224) $k += 1;
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_pitchbend'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		if($code >= 224 AND $code < 240) $k += 2;
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_polyphonic_pressure'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		if($code >= 160 AND $code < 176) $k += 2;
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_volume'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($code >= 176 AND $code < 192) {
			$ctrl = $midi_text_bytes[$k + 1];
			$ctrl = $ctrl % 256;
			if($ctrl == 7 OR $ctrl == 39) $k += 2;
			else $new_midi_code[] = $byte;
			}
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_volume'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($code >= 176 AND $code < 192) {
			$ctrl = $midi_text_bytes[$k + 1];
			$ctrl = $ctrl % 256;
			if($ctrl == 7 OR $ctrl == 39) $k += 2;
			else $new_midi_code[] = $byte;
			}
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_modulation'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($code >= 176 AND $code < 192) {
			$ctrl = $midi_text_bytes[$k + 1];
			$ctrl = $ctrl % 256;
			if($ctrl == 1 OR $ctrl == 33) $k += 2;
			else $new_midi_code[] = $byte;
			}
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

if($flatten_all OR isset($_POST['suppress_program'])) {
	$new_midi_code = array();
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($code >= 192 AND $code < 208) $k += 1;
		else $new_midi_code[] = $byte;
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	}

$duration_warning = '';
$change_beats = FALSE;
if(isset($_POST['adjust_beats'])) {
	$NewBeats = $_POST['NewBeats'];
	$NewDuration = round($Tref * $NewBeats);
	$change_beats = TRUE;
	}
if($change_beats OR isset($_POST['adjust_duration'])) {
	if(!$change_beats) $NewDuration = $_POST['NewDuration'];
	$Duration = $_POST['Duration'];
	if($Duration > 0) {
		$alpha = $NewDuration / $Duration;
		$new_midi_code = array();
		for($k = 0; $k < $kmax; $k++) {
			$byte = $midi_text_bytes[$k];
			$code = $byte % 256;
			$time = ($byte - $code) / 256;
			$newtime = round($alpha * $time); 
			$new_midi_code[$k] = $code + (256 * $newtime);
			}
		}
	else {
		$duration_warning = "<p style=\"color:red;\">Check ‘explicit MIDI codes’ because the preceding duration was equal to zero.</p>";
		$kmax = count($midi_text_bytes);
		$number_notes = 0;
		for($k = 0; $k < $kmax; $k++) {
			$byte = $midi_text_bytes[$k];
			$code = $byte % 256;
			if($code >= 128 AND $code < 160) $number_notes++;
			// NoteOn or NoteOff
			}
		if($number_notes > 1)
			$step = $NewDuration / ($number_notes);
		else $step = 0;
		$new_midi_code = array();
		$newtime = 0;
		for($k = 0; $k < $kmax; $k++) {
			$byte = $midi_text_bytes[$k];
			$code = $byte % 256;
			if($code >= 128 AND $code < 160) $newtime += $step;
			$new_midi_code[$k] = $code + (256 * intval($newtime));
			}
		}
	$kmax = count($new_midi_code);
	$midi_text_bytes = array();
	$handle_bytes = fopen($midi_bytes,"w");
	fwrite($handle_bytes,$kmax."\n");
	for($k = 0; $k < $kmax; $k++) {
		$byte = $new_midi_code[$k];
		fwrite($handle_bytes,$byte."\n");
		$midi_text_bytes[$k] = $byte;
		}
	fclose($handle_bytes);
	$Duration = $NewDuration;
	}

if(isset($_POST['CroppedDuration']) AND trim($_POST['CroppedDuration']) <> '') {
	$CroppedDuration = round($_POST['CroppedDuration']);
	if($CroppedDuration > 0) {
		$new_midi_code = $OnKey = array();
		$lasttime = 0;
		for($k = 0; $k < $kmax; $k++) {
			$byte = $midi_text_bytes[$k];
			$code = $byte % 256;
			$time = ($byte - $code) / 256;
			if($time > $CroppedDuration) break;
			$new_midi_code[$k] = $byte;
			$lasttime = $time;
			if($code >= 144 AND $code < 160) { // NoteOn
				$channel = $code - 144 + 1;
				$byte = $midi_text_bytes[$k + 1];
				$key = $byte % 256;
				$byte = $midi_text_bytes[$k + 2];
				$velocity = $byte % 256;
				if($velocity > 0) $OnKey[$channel][$key] = TRUE;
				else if(isset($OnKey[$channel][$key])) unset($OnKey[$channel][$key]);
				}
			if($code >= 128 AND $code < 144) { // NoteOff
				$channel = $code - 128 + 1;
				$byte = $midi_text_bytes[$k + 1];
				$key = $byte % 256;
				if(isset($OnKey[$channel][$key])) unset($OnKey[$channel][$key]);
				}
			}
		for($channel = 1; $channel <= 16; $channel++) {
			if(!isset($OnKey[$channel])) continue;
			foreach($OnKey[$channel] as $key => $value) {
			//	echo "key = ".$key." value = ".$value."<br />";
				$code = 128 + $channel - 1;  // NoteOff
				$byte = $code + (256 * $CroppedDuration);
				$new_midi_code[$k++] = $byte;
				$byte = $key + (256 * $CroppedDuration);
				$new_midi_code[$k++] = $byte;
				$byte = 256 * $CroppedDuration;
				$new_midi_code[$k++] = $byte;
				$lasttime = $CroppedDuration;
				}
			}
		if($lasttime < $CroppedDuration) {
			$code = 208;
			$new_midi_code[$k++] = $code + (256 * $CroppedDuration);
			$new_midi_code[$k++] = 256 * $CroppedDuration;
			}
		$kmax = count($new_midi_code);
		$midi_text_bytes = array();
		$handle_bytes = fopen($midi_bytes,"w");
		fwrite($handle_bytes,$kmax."\n");
		for($k = 0; $k < $kmax; $k++) {
			$byte = $new_midi_code[$k];
			fwrite($handle_bytes,$byte."\n");
			$midi_text_bytes[$k] = $byte;
			}
		fclose($handle_bytes);
		$Duration = $CroppedDuration;
		}
	}

if(!$no_midi) {
	if($new_midi) {
		if(file_exists($midi_text)) copy($midi_text,$midi_text.".old");
		if(file_exists($midi_bytes)) copy($midi_bytes,$midi_bytes.".old");
		if(file_exists($mf2t)) copy($mf2t,$mf2t.".old");
		}
	$time_max_midi = $oldtime = 0;
	$number_of_tracks = 1;
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($time < $oldtime) $number_of_tracks++;
		$oldtime = $time;
		if($time > $time_max_midi) $time_max_midi = $time;
		}
	$trk = 1;
	$handle_mf2t = $handle_text = FALSE;
	$handle_text = fopen($midi_text,"w");
	$handle_mf2t = fopen($mf2t,"w");
	$number_of_tracks++;
//	$number_of_tracks = 2; // Fixed 1st Nov. 2020
	fwrite($handle_mf2t,"MFile 1 ".$number_of_tracks." ".$division."\n");
	fwrite($handle_mf2t,"MTrk\n");
	$track_name = $object_name."_track_".$trk++;
	fwrite($handle_mf2t,"0 Meta TrkName \"".$track_name."\"\n");
	fwrite($handle_mf2t,$timesig."\n");
	fwrite($handle_mf2t,"0 Tempo ".$tempo."\n");
	fwrite($handle_mf2t,"0 KeySig 0 major\n");
	fwrite($handle_mf2t,"0 Meta TrkEnd\n");
	fwrite($handle_mf2t,"TrkEnd\n");
	fwrite($handle_mf2t,"MTrk\n");
	$track_name = $object_name."_track_".$trk++;
	fwrite($handle_mf2t,"0 Meta TrkName \"".$track_name."\"\n");
	$handle_bytes = FALSE;
	$handle_bytes = fopen($midi_bytes,"w");
	$more = 0; $code_line = $mf2t_line = '';
	if($handle_bytes) fwrite($handle_bytes,$kmax."\n");
	$hide = 0; $oldtime = 0;
	$first_note_on = TRUE;
	$last_note_off = -1;
//	echo "kmax = ".$kmax."<br />";
	$kmax = count($midi_text_bytes);
	$kmax1 = $kmax - 1;
	$kmax2 = $kmax - 2;
	for($k = 0; $k < $kmax; $k++) {
		$byte = $midi_text_bytes[$k];
	//	echo $code."<br />";
		$code = $byte % 256;
		$time = ($byte - $code) / 256;
		if($time < $oldtime) {
			$trk++;
			if($handle_bytes AND $trk > 2) {
				fclose($handle_bytes);
				$handle_bytes = FALSE;
				}
		//	if($trk > 2) break;
			$track_name = $object_name."_track_".$trk;
			fwrite($handle_mf2t,$oldtime." Meta TrkEnd\n");
			fwrite($handle_mf2t,"TrkEnd\n");
			fwrite($handle_mf2t,"MTrk\n");
			fwrite($handle_mf2t,"0 Meta TrkName \"".$track_name."\"\n");
			}
		if($handle_bytes) {
		//	echo $byte."<br />";
			fwrite($handle_bytes,$byte."\n");
			}
		$oldtime = $time;
	//	echo "(".$time.") ".$code."<br />";
		if($code >= 144 AND $code < 160 AND $k < $kmax2) {
			store($h_image,"event_midi[]",$time);
			if($first_note_on) {
				store($h_image,"first_note_on",$time);
				$first_note_on = FALSE;
				}
			$channel = $code - 144 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$key = $byte % 256;
			$byte = $midi_text_bytes[$k + 2];
			$velocity = $byte % 256;
			$mf2t_line = $time." On ch=".$channel." n=".$key." v=".$velocity;
			fwrite($handle_mf2t,$mf2t_line."\n");
			if($velocity > 0)
				$code_line = $time." (ch ".$channel.") NoteOn ";
			else {
				$code_line = $time." (ch ".$channel.") NoteOff ";
				$last_note_off = $time;
				}
			$more = 2;
			}
		else if($code >= 128 AND $code < 144 AND $k < $kmax2) {
			store($h_image,"event_midi[]",$time);
			$last_note_off = $time;
			$channel = $code - 128 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$key = $byte % 256;
			$byte = $midi_text_bytes[$k + 2];
			$velocity = $byte % 256;
			$mf2t_line = $time." Off ch=".$channel." n=".$key." v=".$velocity;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") NoteOff ";
			$more = 2;
			}
		else if($code >= 160 AND $code < 176 AND $k < $kmax2) {
			store($h_image,"event_midi[]",$time);
			$channel = $code - 160 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$key = $byte % 256;
			$byte = $midi_text_bytes[$k + 2];
			$val = $byte % 256;
			$mf2t_line = $time." PoPr ch=".$channel." n=".$key." v=".$val;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") Poly pressure key ";
			$more = 2;
			}
		else if($code >= 176 AND $code < 192 AND $k < $kmax2) {
			store($h_image,"event_midi[]",$time);
			$channel = $code - 176 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$ctrl = $byte % 256;
			$byte = $midi_text_bytes[$k + 2];
			$val = $byte % 256;
			$mf2t_line = $time." Par ch=".$channel." c=".$ctrl." v=".$val;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") Parameter ";
			if($ctrl == 123) {
				$code_line = $time." (ch ".$channel.") AllNotesOff ";
				$hide = 2;
				}
			if($ctrl == 1) {
				$code_line = $time." (ch ".$channel.") Modulation wheel ";
				$hide = 1;
				}
			if($ctrl == 2) {
				$code_line = $time." (ch ".$channel.") Breath ";
				$hide = 1;
				}
			if($ctrl == 4) {
				$code_line = $time." (ch ".$channel.") Foot controller ";
				$hide = 1;
				}
			if($ctrl == 5) {
				$code_line = $time." (ch ".$channel.") Portamento time ";
				$hide = 1;
				}
			if($ctrl == 7) {
				$code_line = $time." (ch ".$channel.") Volume ";
				$hide = 1;
				}
			if($ctrl == 8) {
				$code_line = $time." (ch ".$channel.") Balance ";
				$hide = 1;
				}
			if($ctrl == 10) {
				$code_line = $time." (ch ".$channel.") Panoramic ";
				$hide = 1;
				}
			if($ctrl == 11) {
				$code_line = $time." (ch ".$channel.") Expression ";
				$hide = 1;
				}
			if($ctrl == 64) {
				$code_line = $time." (ch ".$channel.") Sustain ";
				$hide = 1;
				}
			if($ctrl == 65) {
				$code_line = $time." (ch ".$channel.") Portamento on/off ";
				$hide = 1;
				}
			if($ctrl == 66) {
				$code_line = $time." (ch ".$channel.") Sostenuto pedal ";
				$hide = 1;
				}
			if($ctrl == 67) {
				$code_line = $time." (ch ".$channel.") Soft pedal ";
				$hide = 1;
				}
			if($ctrl == 68) {
				$code_line = $time." (ch ".$channel.") Legato footswitch ";
				$hide = 1;
				}
			if($ctrl == 69) {
				$code_line = $time." (ch ".$channel.") Hold 2 ";
				$hide = 1;
				}
			if($ctrl == 70) {
				$code_line = $time." (ch ".$channel.") Sound variation ";
				$hide = 1;
				}
			if($ctrl == 71) {
				$code_line = $time." (ch ".$channel.") Timbre ";
				$hide = 1;
				}
			if($ctrl == 72) {
				$code_line = $time." (ch ".$channel.") Release time ";
				$hide = 1;
				}
			if($ctrl == 73) {
				$code_line = $time." (ch ".$channel.") Attack time ";
				$hide = 1;
				}
			if($ctrl == 74) {
				$code_line = $time." (ch ".$channel.") Brightness ";
				$hide = 1;
				}
			if($ctrl == 84) {
				$code_line = $time." (ch ".$channel.") Portamento control ";
				$hide = 1;
				}
			if($ctrl == 91) {
				$code_line = $time." (ch ".$channel.") External effects depth ";
				$hide = 1;
				}
			if($ctrl == 92) {
				$code_line = $time." (ch ".$channel.") Tremolo depth ";
				$hide = 1;
				}
			if($ctrl == 93) {
				$code_line = $time." (ch ".$channel.") Chorus depth ";
				$hide = 1;
				}
			if($ctrl == 94) {
				$code_line = $time." (ch ".$channel.") Detune depth ";
				$hide = 1;
				}
			if($ctrl == 95) {
				$code_line = $time." (ch ".$channel.") Phase depth ";
				$hide = 1;
				}
			$more = 2;
			}
		else if($code >= 208 AND $code < 224 AND $k < $kmax1) {
			store($h_image,"event_midi[]",$time);
			$channel = $code - 208 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$val = $byte % 256;
			$mf2t_line = $time." ChPr ch=".$channel." v=".$val;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") Channel pressure ";
			$more = 1;
			}
		else if($code >= 224 AND $code < 240 AND $k < $kmax2) {
			store($h_image,"event_midi[]",$time);
			$channel = $code - 224 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$val1 = $byte % 256;
			$byte = $midi_text_bytes[$k + 2];
			$val2 = $byte % 256;
			$val = $val1 + (256 * $val2);
			$mf2t_line = $time." Pb ch=".$channel." v=".$val;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") Pitchbend ";
			$more = 2;
			}
		else if($code >= 192 AND $code < 208 AND $k < $kmax1) {
			store($h_image,"event_midi[]",$time);
			$channel = $code - 192 + 1;
			$byte = $midi_text_bytes[$k + 1];
			$prog = $byte % 256;
			$mf2t_line = $time." PrCh ch=".$channel." p=".$prog;
			fwrite($handle_mf2t,$mf2t_line."\n");
			$code_line = $time." (ch ".$channel.") Prog change ";
			$more = 1;
			}
		else {
			$more--;
			if($more == 0) {
				if($hide == 0) $code_line .= $code." ";
				else $hide--;
				fwrite($handle_text,$code_line."\n");
			//	echo $code_line."<br />";
				}
			else {
				if($hide == 0) $code_line .= $code." ";
				else $hide--;
				}
			}
		}
	if($last_note_off >= 0) store($h_image,"last_note_off",$last_note_off);
	if($handle_text) fclose($handle_text);
	if($handle_mf2t) {
		fwrite($handle_mf2t,$time." Meta TrkEnd\n");
		fwrite($handle_mf2t,"TrkEnd\n");
		fclose($handle_mf2t);
		}
	if($handle_bytes) fclose($handle_bytes);
	fix_number_bytes($midi_bytes);
	}
$Duration = $time_max_midi;
// echo "time_max_midi = ".$time_max_midi."<br />";
store($h_image,"time_max_midi",$time_max_midi);

echo "<input type=\"hidden\" name=\"jmax\" value=\"".$j."\">";
if(!$no_midi) {
	echo "<p>MIDI CODES</p>";
	$mf2t_content = @file_get_contents($mf2t);
	if($mf2t_content) {
		$size_mf2t = strlen($mf2t_content);
		if($size_mf2t < 1000000) {
			echo "<p>Creating MIDI file for listening…</p>";
			$midi = new Midi();
			$midi->importTxt($mf2t_content);
			$midi->saveMidFile($midi_file);
			}
		else echo "<p>Size of mf2t_content = ".$size_mf2t." bytes</p>";
		}
	}
if(!$no_midi AND file_exists($midi_text)) {
	$text_link = $midi_text;
	$bytes_link = $midi_bytes;
	$mf2t_link = $mf2t;
	if($test) echo "midi_text = ".$midi_text."<br />";
	if($test) echo "midi_bytes = ".$midi_bytes."<br />";
	if($test) echo "text_link = ".$text_link."<br />";
	if($test) echo "bytes_link = ".$bytes_link."<br />";
	if($test) echo "midi_file = ".$midi_file."<br />";
	echo "<table class=\"thinborder\" id=\"midi\"><tr>";
	echo "<td><div style=\"border:2px solid gray; background-color:azure; color:black; width:10em; padding:2px; text-align:center; border-radius: 6px;\"><a class=\"linkdotted\" style=\"color: #007BFF;\" onclick=\"window.open('".nice_url($text_link)."','MIDItext','width=300,height=300'); return false;\" href=\"".nice_url($text_link)."\">EXPLICIT MIDI codes</a></div></td>";
	echo "<td><div style=\"border:2px solid gray; background-color:azure; color:black; width:13em;  padding:2px; text-align:center; border-radius: 6px;\"><a class=\"linkdotted\" style=\"color: #007BFF;\" onclick=\"window.open('".nice_url($bytes_link)."','MIDIbytes','width=300,height=500,left=400'); return false;\" href=\"".nice_url($bytes_link)."\">TIME-STAMPED MIDI bytes</a><br /><small>Top number is the number of bytes</small></div></td>";
	echo "<td style=\"white-space:nowrap;\"><div style=\"border:2px solid gray; background-color:azure; color:black; width:15em;  padding:2px; text-align:center; border-radius: 6px;\"><a class=\"linkdotted\" style=\"color: #007BFF;\" onclick=\"window.open('".nice_url($mf2t_link)."','MF2T','width=300,height=500,left=300'); return false;\" href=\"".nice_url($mf2t_link)."\">MF2T code</a><br />";
	echo "<small>division = <input type=\"text\" name=\"division\" size=\"5\" value=\"".$division."\"><br />";
	echo "<small>tempo = ".$tempo." µs<br />timesig = ".$timesig."</small>";
	echo "</div></td>";
	
	echo "<input type=\"hidden\" name=\"tempo\" value=\"".$tempo."\">";
	echo "<input type=\"hidden\" name=\"timesig\" value=\"".$timesig."\">";

	$midi_file_link = $midi_file;
	if(file_exists($midi_file_link)) {
		echo "</tr><tr>";
		echo "<td colspan=\"3\" style=\"padding-bottom:1em;\">";
		echo midifile_player($midi_file_link,'',25,0);
		echo "</td>";
		}
	echo "</tr></table>";
	if($new_midi) echo " ... <span class=\"red-text\">from the file you have just loaded</span><br /><br />";
echo "➡ <i>If changes are not visible on these pop-up windows, juste clear the cache!</i><br />";
	}
else echo "<p>No MIDI codes in this sound-object prototype</p>";

if($new_midi) echo "<p style=\"color:red;\">You should save this prototype to preserve uploaded MIDI codes! ➡ <input class=\"save\" type=\"submit\" name=\"savethisprototype\" formaction=\"".$url_this_page."#midi\" value=\"SAVE IT\">&nbsp;<input class=\"cancel\" type=\"submit\" formaction=\"".$url_this_page."#midi\" name=\"cancel\" formaction=\"".$url_this_page."#midi\" value=\"CANCEL\"></p>";

echo "<span class=\"red-text\">➡</span> Create or replace MIDI codes loading a MIDI file (*.mid): <input type=\"file\" name=\"mid_upload\">&nbsp;<input class=\"produce\" type=\"submit\" value=\" send \">";

echo "<p>DURATION OF MIDI SEQUENCE</p>";
$real_duration = $Duration - $PreRoll + $PostRoll;
store($h_image,"PreRoll",$PreRoll);
store($h_image,"PostRoll",$PostRoll);
echo "Real MIDI duration of this object will be:<br /><b>event duration - pre-roll + post-roll</b> = ".$Duration." - (".$PreRoll.") + (".$PostRoll.") = ".$real_duration." ms<br />for a metronome period Tref = ".$Tref." ms";
if($duration_warning <> '') echo $duration_warning;
echo "<input type=\"hidden\" name=\"Duration\" value=\"".$Duration."\">";
echo "<p><input class=\"edit\" type=\"submit\" name=\"adjust_duration\" formaction=\"".$url_this_page."#midi\" value=\"Adjust event time duration\"> to <input type=\"text\" name=\"NewDuration\" size=\"8\" value=\"".$Duration."\"> ms<br />";
if($Tref > 0) echo "<input class=\"edit\" type=\"submit\" name=\"adjust_beats\" formaction=\"".$url_this_page."#midi\" value=\"Adjust event beat duration\"> to <input type=\"text\" name=\"NewBeats\" size=\"8\" value=\"".round($Duration/($Tref),2)."\"> beats (striated object with Tref = ".($Tref / $resolution)." ticks of ".$resolution." ms, i.e. ".($Tref)." ms)";
echo "</p>";

echo "<p>";
echo "<p><input class=\"edit\" type=\"submit\" name=\"crop_duration\" formaction=\"".$url_this_page."#midi\" value=\"Crop event time duration\"> to <input type=\"text\" name=\"CroppedDuration\" size=\"8\" value=\"\"> ms (truncate the end of the MIDI sequence)<br />";
echo "</p>";

if($silence_before_warning <> '') echo "<span class=\"red-text\">➡</span> ".$silence_before_warning."<br />";
echo "<input class=\"edit\" type=\"submit\" name=\"silence_before\" formaction=\"".$url_this_page."#midi\" value=\"Insert silence before this object\"> = <input type=\"text\" name=\"SilenceBefore\" size=\"8\" value=\"\"> ms ➡ current pre-roll = ".$PreRoll." ms<br />";

if($silence_after_warning <> '') echo "<span class=\"red-text\">➡</span> ".$silence_after_warning."<br />";
echo "<input class=\"edit\" type=\"submit\" name=\"silence_after\" formaction=\"".$url_this_page."#midi\" value=\"Append silence after this object\"> = <input type=\"text\" name=\"SilenceAfter\" size=\"8\" value=\"\"> ms ➡ current post-roll = ".$PostRoll." ms<br /><br />";

if(!$new_midi AND !$no_midi) {
	echo "<p>CHANGE MIDI CONTROLS</p>";
	echo "<p style=\"text-align:left;\"><input class=\"edit\" type=\"submit\" name=\"suppress_pressure\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS channel pressure\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_polyphonic_pressure\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS polyphonic pressure\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_pitchbend\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS pitchbend\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_volume\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS volume control\"><br />";
	echo "<input class=\"edit\" type=\"submit\" name=\"suppress_modulation\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS modulation\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_program\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS program changes\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_panoramic\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS panoramic\"><br /><input class=\"edit\" type=\"submit\" name=\"add_allnotes_off\" formaction=\"".$url_this_page."#midi\" value=\"APPEND AllNotesOff (all channels)\">&nbsp;<input class=\"edit\" type=\"submit\" name=\"suppress_allnotes_off\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS AllNotesOff (all channels)\"><br /><input class=\"edit\" type=\"submit\" name=\"delete_midi\" formaction=\"".$url_this_page."#midi\" value=\"SUPPRESS all MIDI codes\"><br />";
	echo "<input class=\"edit\" type=\"submit\" name=\"quantize_NoteOn\" formaction=\"".$url_this_page."#midi\" value=\"QUANTIZE NoteOns\"> = 1 / <input type=\"text\" name=\"NoteOnQuantize\" size=\"4\" formaction=\"".$url_this_page."#midi\" value=\"64\"> beat</p>";
	}

store($h_image,"object_name",$object_name);
store($h_image,"Duration",$Duration);
store($h_image,"Tref",$Tref);

$link = "prototype_image.php?save_codes_dir=".urlencode($save_codes_dir);

if($Duration > 0 OR $object_type > 3)
	echo "<div class=\"shadow\" style=\"border:2px solid gray; background-color:azure; color:black; width:13em;  padding:8px; text-align:center; border-radius: 6px;\"><a class=\"linkdotted\" style=\"color: #007BFF;\" onclick=\"window.open('".$link."','".clean_folder_name($object_name)."_image','width=800,height=625,left=100'); return false;\" href=\"".nice_url($link)."\">IMAGE</a></div>";
else echo "<p><span class=\"red-text\">➡</span> NO IMAGE since duration = 0</p>";

echo "<p style=\"text-align:center;\"><input class=\"save big\" type=\"submit\" name=\"savethisprototype\" formaction=\"".$url_this_page."#midi\" value=\"SAVE THIS PROTOTYPE\">&nbsp;<big> = <b><span class=\"red-text\">".$object_name."</span></b></big></p>";
echo "</form>";

echo "<hr>";
echo "<p id=\"csound\">CSOUND</p>";
$csound_score = @file_get_contents($csound_file);
$csound_score = fix_csound_score($csound_score,$csound_file,$temp_dir,$temp_folder);
$csound_period = 0;
$time_max_csound = 0;
$table = explode(chr(10),$csound_score);
if(count($table) > 2) {
	$csound_instruction = $table[0];
	for($i = 0; $i < count($table); $i++) {
		$csound_instruction = trim($table[$i]);
		if($csound_instruction == '') continue;
	//	echo "@@@ ".$csound_instruction."<br />";
		do $csound_instruction = str_replace("  ",' ',$csound_instruction,$count);
		while($count > 0);
		$table2 = explode(' ',$csound_instruction);
		if($table2[0] == "t" AND count($table2) > 2) {
			$csound_tempo = $table2[2];
			if($csound_tempo > 0) {
				$csound_period = 60000 / $csound_tempo;
				}
			}
		else if($table2[0][0] == "i" AND count($table2) > 2) {
			$start = $table2[1] * $csound_period;
			$dur = $table2[2] * $csound_period;
			$end = $start + $dur;
			if($end > $time_max_csound)
				$time_max_csound = $end;
			store($h_image,"event_csound[]",$start);
			store($h_image,"event_csound[]",$end);
			}
		}
	store($h_image,"time_max_csound",$time_max_csound);
	echo "<p>Duration of Csound sequence: ".$time_max_csound." ms</p>";
	}
$line = "§>\n";
$line = str_replace('§','?',$line);
fwrite($h_image,$line);
fclose($h_image);

echo $message_create_sound;
echo "<form method=\"post\" action=\"prototype.php#csound\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"object_name\" value=\"".$object_name."\">";
echo "<input type=\"hidden\" name=\"temp_folder\" value=\"".$temp_folder."\">";
echo "<input type=\"hidden\" name=\"object_file\" value=\"".$object_file."\">";
echo "<input type=\"hidden\" name=\"source_file\" value=\"".$source_file."\">";
echo "<input type=\"hidden\" name=\"temp_dir\" value=\"".$temp_dir."\">";
echo "<input type=\"hidden\" name=\"prototypes_file\" value=\"".$prototypes_file."\">";
echo "<input type=\"hidden\" name=\"prototypes_name\" value=\"".$prototypes_name."\">";
echo "<input type=\"hidden\" name=\"CsoundInstruments_file\" value=\"".$CsoundInstruments_file."\">";
echo "<input type=\"hidden\" name=\"Duration\" value=\"".$Duration."\">";
echo "<input type=\"hidden\" name=\"division\" value=\"".$division."\">";
echo "<input type=\"hidden\" name=\"tempo\" value=\"".$tempo."\">";
echo "<input type=\"hidden\" name=\"timesig\" value=\"".$timesig."\">";
echo "<textarea name=\"csound_score\" onchange=\"tellsave()\" rows=\"20\" style=\"width:700px;\">".$csound_score."</textarea><br />";
echo "<p><input class=\"save\" type=\"submit\" name=\"savecsound\" value=\"SAVE THIS CODE\"></p><p><input class=\"save\" type=\"submit\" name=\"createcsound\" value=\"CREATE Csound CODE\"> from MIDI codes in “<span class=\"green-text\">".$object_name."</span>”</p>";
echo "</form>";

function fix_csound_score($csound_score,$csound_file,$temp_dir,$temp_folder) {
	$table = explode(chr(10),$csound_score);
	$changed = FALSE;
	$table2 = array();
	for($i = 0; $i < count($table); $i++) {
		$csound_instruction = trim($table[$i]);
		$bad = FALSE;
		if(is_integer($pos=strpos($csound_instruction,"e")) AND $pos == 0) $bad = TRUE;
		if(is_integer($pos=strpos($csound_instruction,"f")) AND $pos == 0) $bad = TRUE;
		if(is_integer($pos=strpos($csound_instruction,"s")) AND $pos == 0) $bad = TRUE;
		if(is_integer($pos=strpos($csound_instruction,"<void>"))) $bad = TRUE;
		if(is_integer($pos=strpos($csound_instruction,"silence"))) $bad = TRUE;
		if(!$bad) $table2[] = $csound_instruction;
		else $changed = TRUE;
		$score = implode(chr(10),$table2);
		}
	if($changed) {
		$handle = fopen($csound_file,"w");
		if($handle) {
			fwrite($handle,$score."\n");
			fclose($handle);
			$handle = fopen($temp_dir.$temp_folder.SLASH."_changed",'w');
			fclose($handle);
			}
		}
	return $score;
	}
?>