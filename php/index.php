<?php
require_once("_basic_tasks.php");
require_once("_header.php");
$url_this_page = $this_page = "index.php";

echo "<table style=\"background-color:snow;\"><tr>";
echo "<td style=\"padding:4px; vertical-align:middle;\"><img src=\"pict/BP3-logo.png\" width=\"120px;\"/></td><td style=\"padding:4px; vertical-align:middle;\">";
if($path <> '') {
	echo "<h2 style=\"text-align:center;\">Bol Processor ‘BP3’</h2>";
	$url_this_page .= "?path=".urlencode($path);
	$dir = $bp_application_path.$path;
	$table = explode(SLASH,$path);
	if(($n = count($table)) > 1) {
		$upper_dir = $table[$n - 2];
		}
	else $upper_dir = '';
	if($test) echo "upper_dir = ".$upper_dir."<br />";
	if($upper_dir == '') $link = $this_page;
	else $link = $this_page."?path=".urlencode($upper_dir);
	if($test) echo "link = ".$link."<br />";
	echo "<h3 style=\"text-align:center;\">[<a href=\"".$link."\">move up</a>]</h3></td>";
	echo "</tr></table>";
	}
else {
	echo "<h2 style=\"text-align:center;\">Welcome to Bol Processor ‘BP3’</h2>";
	echo "</td></tr></table>";
	$dir = $bp_application_path;
	$command = $dir."bp --help";
	$o = send_to_console($command);
	$n_messages = count($o);
	$no_error = FALSE;
	for($i = 0; $i < $n_messages; $i++) {
		$mssg = $o[$i];
		if(is_integer($pos=strpos($mssg,"Bol Processor")) AND $pos == 0) {
			$no_error = TRUE; break;
			}
		}
	$console = $dir."bp";
	$console_exe = $dir."bp.exe";
	if(!$no_error OR (!file_exists($console) AND !file_exists($console_exe))) {
		echo "<p>The console application ‘bp’ is not working or missing or misplaced… You can't run the application.</p>";
		$source = $dir."source";
		if(file_exists($source))
			echo "<p>Source files have been found. You can try to recompile ‘bp’, then reload this page.<br />➡ <a href=\"".$dir."compile.php\">Run the compiler</a></p>";
		else
			echo "<p>Source files have not been found. Return to <a target=\"_blank\" href=\"https://bolprocessor.org/check-bp3/#install\">https://bolprocessor.org/check-bp3/</a> and check your installation!</p>";
		die();
		}
	}

echo link_to_help();

if($test) echo "dir = ".$dir."<br />";
if($test) echo "url_this_page = ".$url_this_page."<br />";

$new_file = '';
if(isset($_POST['create_grammar'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("gr",$filename);
	if($test) echo "filename = ".$filename."<br />";
	if($filename <> '') {
		$new_file = $filename;
		if($test) echo "newfile = ".$new_file."<br />";
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_grammar']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_data'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("da",$filename);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_data']);
			}
		else {
//			echo "<p style=\"color:red;\" id=\"timespan\">Creating ‘".$filename."’…</p>";
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_alphabet'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("ho",$filename);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_alphabet']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "alphabet_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_timebase'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("tb",$filename);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_timebase']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "timebase_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_prototypes'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("mi",$filename);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_prototypes']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "prototypes_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_csound'])) {
	$filename = trim($_POST['filename']);
	$filename = good_name("cs",$filename);
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_csound']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			$template = "csound_template";
			$template_content = @file_get_contents($template,TRUE);
			fwrite($handle,$template_content."\n");
			fclose($handle);
			}
		}
	}
if(isset($_POST['create_script'])) {
	$filename = trim($_POST['filename']);
	echo $filename."<br />";
	$filename = good_name("sc",$filename);
	echo $filename."<br />";
	if($filename <> '') {
		$new_file = $filename;
		if(file_exists($dir.SLASH.$filename)) {
			echo "<p><font color=\"red\">This file already exists:</font> <font color=\"red\">".$filename."</font></p>";
			unset($_POST['create_script']);
			}
		else {
			$handle = fopen($dir.SLASH.$filename,"w");
			fclose($handle);
			}
		}
	}

$folder = str_replace($bp_application_path,'',$dir);
if($folder <> '') echo "<h3>Content of folder <font color=\"red\">".$folder."</font></h3>";
// echo "dir = ".$dir."<br />";
$table = explode('_',$folder);
$extension = end($table);
if($dir <> $bp_application_path."php" AND $extension <> "temp") {
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_grammar\" value=\"CREATE NEW GRAMMAR FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpgr\"></p>";
	echo "</form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_data\" value=\"CREATE NEW DATA FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpda\"></p>";
	echo "</form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_alphabet\" value=\"CREATE NEW ALPHABET FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpho\"></p>";
	echo "</form>";
	
	
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_prototypes\" value=\"CREATE NEW SOUND-OBJECT PROTOOTYPE FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpmi\"></p>";
	echo "</form>";
	
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_csound\" value=\"CREATE NEW CSOUND INSTRUMENT FILE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpcs\"></p>";
	echo "</form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_script\" value=\"CREATE NEW SCRIPT IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bpsc\"></p>";
	echo "</form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\">";
	echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"create_timebase\" value=\"CREATE NEW TIMEBASE IN THIS FOLDER\">&nbsp;➡&nbsp;";
	echo "<font color=\"blue\">".$folder.SLASH."</font>";
	echo "<input type=\"text\" name=\"filename\" size=\"20\" style=\"background-color:CornSilk;\" value=\"name.bptb\"></p>";
	echo "</form>";
	}

$dircontent = scandir($dir);
foreach($dircontent as $thisfile) {
	if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
	if(is_dir($dir.SLASH.$thisfile)) {
		$table = explode('_',$thisfile);
		$extension = end($table);
		if($path == '') $link = $this_page."?path=".urlencode($thisfile);
		else $link = $this_page."?path=".urlencode($path.SLASH.$thisfile);
		if($extension <> "temp")
			echo "<b><a href=\"".$link."\">".$thisfile."</a></b><br />";
		continue;
		}
	$table = explode(".",$thisfile);
	$extension = end($table);
	$table = explode("_",$thisfile);
	$prefix = $table[0];
	if($prefix == "trace") continue;
	$prefix = substr($thisfile,0,3);
	switch($prefix) {
		case '-gr':
			$type = "grammar"; break;
		case '-da':
			$type = "data"; break;
		case '-ho':
			$type = "alphabet"; break;
		case '-se':
			$type = "settings"; break;
		case '-cs':
			$type = "csound"; break;
		case '-mi':
			$type = "objects"; break;
		case '-or':
			$type = "orchestra"; break;
		case '-in':
			$type = "interaction"; break;
		case '-md':
			$type = "midisetup"; break;
		case '-tb':
			$type = "timebase"; break;
		case '-kb':
			$type = "keyboard"; break;
		case '-gl':
			$type = "glossary"; break;
		case '-sc':
			$type = "script"; break;
		default:
			$type = ''; break;
		}
	switch($extension) {
		case "bpgr": $type = "grammar"; break;
		case "bpda": $type = "data"; break;
		case "bpho": $type = "alphabet"; break;
		case "bpse": $type = "settings"; break;
		case "bpcs": $type = "csound"; break;
		case "bpmi": $type = "objects"; break;
		case "bpor": $type = "orchestra"; break;
		case "bpin": $type = "interaction"; break;
		case "bpmd": $type = "midisetup"; break;
		case "bptb": $type = "timebase"; break;
		case "bpkb": $type = "keyboard"; break;
		case "bpgl": $type = "glossary"; break;
		case "bpsc": $type = "script"; break;
		case "orc": $type = "csorchestra"; break;
		}
	if($type <> '') {
		$link = $type.".php?file=".urlencode($path.SLASH.$thisfile);
		if($new_file == $thisfile) echo "<font color=\"red\">➡</font> ";
		echo "<a target=\"_blank\" href=\"".$link."\">";
		echo $thisfile."</a> ";
		if($type == "grammar") echo "<font color=\"red\">";
		else if($type == "data") echo "<font color=\"gold\">";
		else if($type == "script") echo "<font color=\"blue\">";
		else if($type <> "settings") echo "<font color=\"lightgreen\">";
		echo $type."</font>";
		$time_saved = filemtime($dir.SLASH.$thisfile);
		echo " <small>➡ ".gmdate('Y-m-d H\hi',$time_saved)."</small>";
		echo "<br />";
		}
	else echo $thisfile."<br />";
	}

echo "<hr>";
$os_platform = getOS();
if(PHP_OS <> "WINNT" AND !is_integer(strpos($os_platform,"Windows")))
	echo "<p style=\"text-align:center;\"><a href=\"".$bp_application_path."compile.php\">Recompile BP</a> (be careful!)</p>";
?>
