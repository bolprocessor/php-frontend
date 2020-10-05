<?php
require_once("_basic_tasks.php");
$url_this_page = "script_exec.php";
$this_title = "Script console";
require_once("_header.php");

$application_path = "..".SLASH;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else {
	$file = '';
	echo "No script file has been specified…";
	die();
	}
$dir = urldecode($_GET['dir']);
$temp_folder = urldecode($_GET['temp_folder']);
$script_variables = $temp_dir.$temp_folder.SLASH."script_variables.php";

echo "<p>Current directory = ".$dir."</p>";
echo "<h3>Running ".$file."</h3>";

$note_convention = $grammar = $output_format = '';

run_script($dir,$file,$script_variables,$note_convention,$grammar,$output_format);

echo "</body>";
echo "</html>";

function run_script($dir,$file,$script_variables,$note_convention,$grammar,$output_format) {
	global $temp_dir,$bp_application_path,$top_header;
	require($script_variables);
	require("_settings.php");
	$temp_folder = urldecode($_GET['temp_folder']);
	$content = @file_get_contents($dir.$file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$imax = count($table);
//	echo $truc."<br />";
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		$line = preg_replace("/^\/\/.*/u",'',$line);
		if($line == '') continue;
		$instruction =  get_instruction($line);
		$recoded_line = recode_tags($line);
	//	echo "<p style=\"color:blue;\">line = ".$recoded_line."<br />";
	//	echo "instruction = “".$instruction."”</p>";
		$status = 0; $more = '';
		if(isset($script_status[$instruction])) {
			$status = $script_status[$instruction];
			if($script_more[$instruction] <> '') $more = " ".$script_more[$instruction];
			else $more = '';
			}
		if($status <> 1) continue;
		if($instruction == "Type") {
			$line = str_replace($instruction." ",'',$line);
			if($line == "<return>") echo "<br />";
			else {
				$recoded_line = recode_tags($line);
				echo $line;
				}
			continue;
			}
		if($instruction == "Play") {
			$line = trim(str_replace($instruction." ",'',$line));
			$recoded_line = recode_tags($line);
			echo "Play ".$recoded_line;
			$data = $temp_dir."temp_".session_id()."outdata.bpda";
			$handle = fopen($data,"w");
			$file_header = $top_header."\n// Data saved as \"outdata.bpda\". Date: ".gmdate('Y-m-d H:i:s');
			fwrite($handle,$file_header."\n");
			fwrite($handle,$line."\n");
			fclose($handle);
			$command = $bp_application_path."bp play";
			$command .= " -da ".$data;
			if($note_convention <> '') $command .= " --".strtolower($note_convention);
			$command .= " -d --rtmidi ";
			echo "<p style=\"color:red;\">".$command."</p>";
			exec($command,$o);
			$n_messages = count($o);
			$no_error = FALSE;
			if($n_messages > 0) {
				for($k=0; $k < $n_messages; $k++) {
					$mssg[$k] = $o[$k];
					$mssg[$k] = clean_up_encoding(TRUE,$mssg[$k]);
					if(is_integer($pos=strpos($mssg[$k],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
					}
				}
			$message = '';
			if(!$no_error) {
				$message .= "<p><font color=\"red\">➡ </font>This process:<br /><small>";
				for($k=0; $k < $n_messages; $k++) {
					$message .= "&nbsp;&nbsp;&nbsp;".$mssg[$k]."<br />";
					}
				$message .= "</small></p>";
				}
			echo $message;
			continue;
			}
		if($instruction == "Note convention") {
			$note_convention = trim(str_replace($instruction." = ",'',$line));
			echo "<p>Note convention = ".$note_convention."</p>";
			continue;
			}
		if($instruction == "Run script") {
			$newfile = trim(str_replace($instruction." ",'',$line));
			echo "<p><b>Running script ".$newfile."</b></p>";
			run_script($dir,$newfile,$script_variables,$note_convention,$grammar,$output_format);
			echo "<p><b>Back to script ".$file."</b></p>";
			continue;
			}
		if($instruction == "Load project") {
			$file = trim(str_replace($instruction." ",'',$line));
		//	echo "<p>Grammar = ".$dir.$file."</p>";
			$grammar = $dir.$file;
			continue;
			}
		if($instruction == "Csound score") {
			$value = trim(str_replace($instruction." ",'',$line));
			echo "<p>Output_format: ".$line."</p>";
			if($value == "ON") {
				$output_format = "csound";
				$output_file = $temp_dir.$temp_folder.SLASH."out.sco";
				}
			else $output_format = '';
			continue;
			}
		if($instruction == "Produce items" AND $grammar <> '') {
			$command = $bp_application_path."bp produce";
			$command .= " -gr ".$grammar;
			if($note_convention <> '') $command .= " --".strtolower($note_convention);
			$command .= " -d";
			if($output_format == "csound")
				$command .= " --csoundout ".$output_file;
			else $command .= " --rtmidi ";
			echo "<p style=\"color:red;\">".$command."</p>";
			exec($command,$o);
			$n_messages = count($o);
			$no_error = FALSE;
			if($n_messages > 0) {
				for($k=0; $k < $n_messages; $k++) {
					$mssg[$k] = $o[$k];
					$mssg[$k] = clean_up_encoding(TRUE,$mssg[$k]);
					if(is_integer($pos=strpos($mssg[$k],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
					}
				}
			if($no_error AND $output_format == "csound") {
				echo "<p><font color=\"red\">➡ </font> Read the <a onclick=\"window.open('".$output_file."','".$output_format."','width=600,height=400,left=300'); return false;\" href=\"".$output_file."\">output file</a></p>";
				}
			$message = '';
			if(!$no_error) {
				$message .= "<p><font color=\"red\">➡ </font>This process:<br /><small>";
				for($k=0; $k < $n_messages; $k++) {
					$message .= "&nbsp;&nbsp;&nbsp;".$mssg[$k]."<br />";
					}
				$message .= "</small></p>";
				}
			echo $message;
			continue;
			}
		}
	return;
	}

?>
