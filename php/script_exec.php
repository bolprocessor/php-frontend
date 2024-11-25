<?php
require_once("_basic_tasks.php");
$url_this_page = "script_exec.php";
$this_title = "Script console";

require_once("_header.php");
display_darklight();

set_time_limit(0);

$application_path = $bp_application_path;

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else {
	$file = '';
	echo "No script file has been specified…";
	die();
	}
if($file[0] == ' ') $file[0] = "+"; // (compatibility with BP2)
$dir = urldecode($_GET['dir']);
$temp_folder = urldecode($_GET['temp_folder']);
$temp_folder = str_replace(' ','+',$temp_folder); // (compatibility with BP2)
// $script_variables = $temp_dir.$temp_folder.SLASH."script_variables.php";
$script_variables = realpath("..") ."/temp_bolprocessor/".$temp_folder."/script_variables.php";
$dirPath = realpath("..")."/temp_bolprocessor/".$temp_folder;
if (!file_exists($dirPath)) {
    mkdir($dirPath, 0775, true);
    }
create_variables($script_variables);

echo "<h2>Running ".$file."</h2>";

$note_convention = $grammar = $output_format = '';
run_script($dir,$dirPath,$file,$script_variables,$note_convention,$grammar,$output_format);

echo "</body>";
echo "</html>";

function run_script($dir,$dirPath,$this_script_file,$script_variables,$note_convention,$grammar,$output_format) {
	global $temp_dir,$bp_application_path,$top_header,$panicfile;
//	echo "script_variables = ".$script_variables."<br />"; 
	require_once($script_variables);
	require_once("_settings.php");
	$temp_folder = urldecode($_GET['temp_folder']);
	$content = @file_get_contents($dir.$this_script_file,TRUE);
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$imax = count($table);
	$settings = '';
	
	for($i = 0; $i < $imax; $i++) {
		@unlink($panicfile);
		$line = trim($table[$i]);
		$line = preg_replace("/^\/\/.*/u",'',$line);
		if($line == '') continue;
		$instruction =  get_instruction($line);
		$recoded_line = recode_tags($line);
	//	echo "<p class=\"green-text\">line = ".$recoded_line."<br />";
		$status = 0; $more = '';
		if(isset($script_status[$instruction])) {
			$status = $script_status[$instruction];
			if($script_more[$instruction] <> '') $more = " ".$script_more[$instruction];
			else $more = '';
			}
	//	echo "instruction = “".$instruction."” status = ".$status." more = ".$more."</p>";
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
		if($instruction == "Load settings") {
			$file = trim(str_replace($instruction." ",'',$line));
			$settings = $dir.$file;
		//	echo "Found settings: ".$settings."<br />";
			continue;
			}
		if($instruction == "Play") {
			$line = trim(str_replace($instruction." ",'',$line));
			$recoded_line = recode_tags($line);
			echo "Play ".$recoded_line;
			$data = $temp_dir."temp_".my_session_id()."_outdata.bpda";
			$trace_done_file = $temp_dir."temp_".my_session_id()."_outdata_done";
			$handle = fopen($data,"w");
			$file_header = $top_header."\n// Data saved as \"outdata.bpda\". Date: ".gmdate('Y-m-d H:i:s');
			fwrite($handle,$file_header."\n");
			fwrite($handle,$line."\n");
			fclose($handle);
			$command = $bp_application_path."bp play";
			if($settings <> '') $command .= " -se ".$settings;
			$command .= " -da ".$data;
			$tracefile = str_replace(".bpda",".txt",$data);
			if($note_convention <> '') $command .= " --".strtolower($note_convention);
			$command .= " --rtmidi ";
			$command .= " --traceout ".$tracefile;
			echo "<p style=\"color:red;\">".$command."</p>";
			echo str_repeat(' ', 1024);  // send extra spaces to fill browser buffer
			if(ob_get_level() > 0) ob_flush();
			flush();
			// session_abort();
			$o = send_to_console($command);
			// session_reset();
			$n_messages = count($o);
			$no_error = FALSE;
			if($n_messages > 0) {
				for($k=0; $k < $n_messages; $k++) {
					$mssg[$k] = $o[$k];
					$mssg[$k] = clean_up_encoding(FALSE,TRUE,$mssg[$k]);
					if(is_integer($pos=strpos($mssg[$k],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
					}
				}
			$message = '';
			if(!$no_error) {
				$message .= "<p><span class=\"red-text\">➡ </span>This process:<br /><small>";
				for($k=0; $k < $n_messages; $k++) {
					$message .= "&nbsp;&nbsp;&nbsp;".$mssg[$k]."<br />";
					}
				$message .= "</small></p>";
				}
			echo $message;
			@unlink($tracefile);
			@unlink($trace_done_file);
			continue;
			}
		if($instruction == "Produce items" AND $grammar <> '') {
				$table = explode(SLASH,$grammar);
				$grammar_name = end($table);
				$trace_done_file = $temp_dir."temp_".my_session_id()."_".$grammar_name."_done";
				$trace_file = $temp_dir."temp_".my_session_id()."_".$grammar_name.".txt";
				$command = $bp_application_path."bp produce";
				if($settings <> '') $command .= " -se ".$settings;
				$command .= " -gr ".$grammar;
				if($note_convention <> '') $command .= " --".strtolower($note_convention);
			//	$command .= " -d";
				if($output_format == "csound")
					$command .= " --csoundout ".$output_file;
				else $command .= " --rtmidi ";
				$command .= " --traceout ".$trace_file;
				echo "<p style=\"color:red;\">".$command."</p>";
				echo str_repeat(' ', 1024);  // send extra spaces to fill browser buffer
				if(ob_get_level() > 0) ob_flush();
				flush();
				// session_abort();
				$o = send_to_console($command);
				// session_reset();
				$n_messages = count($o);
				$no_error = FALSE;
				if($n_messages > 0) {
					for($k=0; $k < $n_messages; $k++) {
						$mssg[$k] = $o[$k];
						$mssg[$k] = clean_up_encoding(FALSE,TRUE,$mssg[$k]);
						if(is_integer($pos=strpos($mssg[$k],"Errors: 0")) AND $pos == 0) $no_error = TRUE;
						}
					}
				@unlink($trace_file);
				@unlink($trace_done_file);
				if($no_error AND $output_format == "csound") {
					echo "<p><span class=\"red-text\">➡ </span> Read the <a onclick=\"window.open('".nice_url($output_file)."','".$output_format."','width=600,height=400,left=300'); return false;\" href=\"".nice_url($output_file)."\">output file</a></p>";
					}
				$message = '';
				if(!$no_error) {
					$message .= "<p><span class=\"red-text\">➡ </span>This process:<br /><small>";
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
			$new_script_variables = str_replace($this_script_file,$newfile,$script_variables);
			$newdirPath = str_replace($this_script_file,$newfile,$dirPath);
			if(!file_exists($newdirPath)) mkdir($newdirPath, 0775, true); 
            create_variables($new_script_variables);
			run_script($dir,$newdirPath,$newfile,$new_script_variables,$note_convention,$grammar,$output_format);
			echo "<p><b>Back to script ".$this_script_file."</b></p>";
			continue;
			}
		if($instruction == "Load grammar") {
			$file = trim(str_replace($instruction." ",'',$line));
			echo "<p>Grammar = ".$dir.$file."</p>";
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
		}
	return;
	}

?>
