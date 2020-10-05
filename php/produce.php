<?php
require_once("_basic_tasks.php");
$url_this_page = "produce.php";
$this_title = "BP console";
require_once("_header.php");

// $application_path = $bp_application_path.SLASH;
$application_path = "..".SLASH;

/* foreach($_POST as $key => $val) {
	$table = explode('_',$key);
	if($table[0] == "startup") {
		$startup = $table[1];
		echo "<p>Producing item with startup ".$startup."</p>";
		die();
		break;
		}
	} */
if(isset($_GET['startup'])) $startup = $_GET['startup'];
else $startup = '';

if(isset($_GET['instruction'])) $instruction = $_GET['instruction'];
else $instruction = '';
if($instruction == '') {
	echo "ERROR: No instruction has been sent";
	die();
	}
if(isset($_GET['here'])) $here = urldecode($_GET['here']);
else $here = '???';
if($instruction == "help")
	$command = $application_path."bp --help";
else {
	if(isset($_GET['grammar'])) $grammar_path = urldecode($_GET['grammar']);
	else $grammar_path = '';
	if($grammar_path == '') die();
	if(isset($_GET['settings_file'])) $settings_file = $_GET['settings_file'];
	else $settings_file = '';
	if(isset($_GET['note_convention'])) $note_convention = $_GET['note_convention'];
	else $note_convention = '';
	if(isset($_GET['alphabet'])) $alphabet_file = urldecode($_GET['alphabet']);
	else $alphabet_file = '';
	if(isset($_GET['format'])) $file_format = $_GET['format'];
	else $file_format = '';
	if($file_format <> '' AND isset($_GET['output'])) $output = urldecode($_GET['output']);
	else $output = '';
	if(isset($_GET['show_production'])) $show_production = TRUE;
	else $show_production = FALSE;
	if(isset($_GET['trace_production'])) $trace_production = TRUE;
	else $trace_production = FALSE;
	if(isset($_GET['random_seed'])) $random_seed = $_GET['random_seed'];
	else $random_seed = 0;

	$table = explode('/',$grammar_path);
	$grammar_name = $table[count($table) - 1];
	$dir = str_replace($grammar_name,'',$grammar_path);

	if($output <> '') @unlink($output);
	if($tracefile <> '') @unlink($tracefile);

	$thisgrammar = $grammar_path;
	if(is_integer(strpos($thisgrammar,' ')))
		$thisgrammar = '"'.$thisgrammar.'"';
	// $command = $application_path."bp ".$instruction." -gr ".$thisgrammar;
	$command = "../bp ".$instruction." -gr ".$thisgrammar;

	$thisalphabet = $alphabet_file;
	if(is_integer(strpos($thisalphabet,' ')))
		$thisalphabet = '"'.$thisalphabet.'"';
	$thisalphabet = $dir.$thisalphabet;

	if($alphabet_file <> '') $command .= " -ho ".$thisalphabet;

	if($note_convention <> '') $command .= " --".$note_convention;
	if($settings_file <> '') $command .= " -se ".$dir.$settings_file;
	
	if($startup <> '') $command .= " --start ".$startup;
	if($instruction == "produce" OR $instruction == "produce-all") {
		switch($file_format) {
			case "data":
				$command .= " -d -o ".$output;
				break;
			case "midi":
				$command .= " -d --midiout ".$output;
				break;
			case "csound":
				$command .= " -d --csoundout ".$output;
				break;
			default:
				$command .= " -d --rtmidi";
				break;
			}
		}
	if($tracefile <> '') $command .= " --traceout ".$tracefile;
	if($show_production) $command .= " --show-production";
	if($trace_production) $command .= " --trace-production";
	$command .= " --seed ".$random_seed;
	}

echo "<p style=\"color:red;\"><small>command = ".$command."</small></p>";

exec($command,$o);
$n_messages = count($o);
$no_error = FALSE;
for($i=0; $i < $n_messages; $i++) {
	$mssg = $o[$i];
	if(is_integer($pos=strpos($mssg,"Errors: 0")) AND $pos == 0) $no_error = TRUE;
	}
echo "<hr>";

// $this_data_folder = str_replace($bp_application_path.SLASH,'',$here);

if($instruction <> "help") {
	$tracefile_html = clean_up_file($tracefile);
	$trace_link = $tracefile_html;
	$output_link = $output;
	
if($test) echo "output = ".$output."<br />";
if($test) echo "tracefile_html = ".$tracefile_html."<br />";
if($test) echo "dir = ".$dir."<br />";
if($test) echo "trace_link = ".$trace_link."<br />";
if($test) echo "output_link = ".$output_link."<br />";

	if(!$no_error) {
		echo "<p><font color=\"red\">Errors found… Check the </font> <a onclick=\"window.open('".$trace_link."','errors','width=800,height=800,left=400'); return false;\" href=\"".$trace_link."\">error trace</a> file!</p>";
		}
	else {
		echo "<p>";
		if($output <> '' AND $file_format <> "midi") echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$output_link."','".$file_format."','width=800,height=800,left=300'); return false;\" href=\"".$output_link."\">output file</a><br />";
		if($trace_production OR $instruction == "templates" OR $show_production OR $trace_production) echo "<font color=\"red\">➡</font> Read the <a onclick=\"window.open('".$trace_link."','trace','width=800,height=800,left=400'); return false;\" href=\"".$trace_link."\">trace file</a>";
		echo "</p>";
		}
	}

for($i=0; $i < $n_messages; $i++) {
	$mssg = $o[$i];
	$mssg = clean_up_encoding(TRUE,$mssg);
	echo $mssg."<br />";
	}
if($n_messages == 0) echo "No message produced…";
?>
