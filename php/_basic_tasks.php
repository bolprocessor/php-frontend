<?php
session_start();
require('midi.class.php');
// Source: https://github.com/robbie-cao/midi-class-php

define('MAXFILESIZE',1000000);
define('SLASH',DIRECTORY_SEPARATOR);
// define('SLASH',"/");

$test = FALSE;
// $test = TRUE;

$bp_application_path = "..".SLASH;
if(!isset($csound_path) OR $csound_path == '') $csound_path = "/usr/local/bin/";
if(!isset($csound_resources) OR $csound_resources == '') $csound_resources = "csound_resources";
save_settings("csound_resources",$csound_resources);
if(!isset($trash_folder) OR $trash_folder == '') $trash_folder = "trash_bolprocessor";
save_settings("trash_folder",$trash_folder);
$max_sleep_time_after_bp_command = 15; // seconds. Maximum time allowed to the console
$default_output_format = "midi";

$temp_dir = $bp_application_path."temp_bolprocessor";
if(!file_exists($temp_dir)) {
	mkdir($temp_dir);
	}
$temp_dir .= SLASH;

if(!file_exists($bp_application_path.$csound_resources)) {
	mkdir($bp_application_path.$csound_resources);
	}
$dir_csound_resources = $bp_application_path.$csound_resources.SLASH;

if(!file_exists($bp_application_path.$trash_folder)) {
	mkdir($bp_application_path.$trash_folder);
	}
$dir_trash_folder = $bp_application_path.$trash_folder.SLASH;

if(isset($_POST['csound_path'])) {
	$new_csound_path = trim($_POST['csound_path']);
	if($new_csound_path <> '') {
		if($new_csound_path[0] <> '/')
			$new_csound_path = "/".$new_csound_path;
		if($new_csound_path[strlen($new_csound_path) - 1] <> '/')
			$new_csound_path .= "/";
		save_settings("csound_path",$new_csound_path);
		$csound_path = $new_csound_path;
		}
	}

// Delete old temp directories and trace files
$dircontent = scandir($temp_dir);
$now = time();
$yesterday = $now - (24 * 3600);
foreach($dircontent as $thisfile) {
	if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
	$time_saved = filemtime($temp_dir.$thisfile);
	if($time_saved < $yesterday) $old = TRUE;
	else $old = FALSE;
	if(is_dir($temp_dir.$thisfile)) {
		$table = explode('_',$thisfile);
		$extension = end($table);
		if($extension == "temp" AND count($table) > 2) {
			$id = $table[count($table) - 2];
			if($old) {
				if($id <> session_id()) {
					my_rmdir($temp_dir.$thisfile);
					continue;
					}
				}
			}
		}
	$table = explode(".",$thisfile);
	$extension = end($table);
	if($old) {
		$table = explode('_',$thisfile);
		$prefix = $table[0];
		if($prefix == "trace" OR $prefix == "temp") {
			$id = $table[1];
			if(($extension == "txt" OR $extension == "html" OR $extension == "bpda") AND $id <> session_id()) {
				unlink($temp_dir.$thisfile);
				continue;
				}
			}
		}
	}

if(isset($_GET['path'])) $path = urldecode($_GET['path']);
else $path = '';

$text_help_file = $bp_application_path."BP2_help.txt";

if($test) {
	echo "<small>";
	echo "operating system = ".getOS()."<br />";
	echo "path = ".$path."<br />";
	echo "bp_application_path = ".$bp_application_path."<br />";
	echo "temp_dir = ".$temp_dir."<br />";
	echo "text_help_file = ".$text_help_file."<br />";
	echo "</small><hr>";
	}

$html_help_file = "BP2_help.html";
$help = compile_help($text_help_file,$html_help_file);
$tracefile = $temp_dir."trace_".session_id().".txt";
$top_header = "// Bol Processor BP3 compatible with version BP2.9.8";

$KeyString = "key#";
$Englishnote = array("C","C#","D","D#","E","F","F#","G","G#","A","A#","B","C");
$Frenchnote = array("do","do#","re","re#","mi","fa","fa#","sol","sol#","la","la#","si","do");
$Indiannote = array("sa","rek","re","gak","ga","ma","ma#","pa","dhak","dha","nik","ni","sa");
$AltEnglishnote = array("B#","Db","D","Eb","Fb","E#","Gb","G","Ab","A","Bb","Cb","B#");
$AltFrenchnote = array("si#","reb","re","mib","fab","mi#","solb","sol","lab","la","sib","dob","si#");
$AltIndiannote = array("ni#","sa#","re","re#","mak","ga#","pak","pa","pa#","dha","dha#","sak","ni#");

// --------- FUNCTIONS ------------

function extract_data($compact,$content) {
	$content = trim($content);
	$content = str_replace(chr(13).chr(10),chr(10),$content);
	$content = str_replace(chr(13),chr(10),$content);
	$content = str_replace(chr(9),' ',$content); // Remove tabulations
	$content = clean_up_encoding(TRUE,TRUE,$content);
	if($compact) {
		do $content = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$content,$count);
		while($count > 0);
		}
	$table = explode(chr(10),$content);
	$table_out = $extract_data = array();
	$start = TRUE;
	$extract_data['metronome'] = $extract_data['time_structure'] = $extract_data['headers'] = $extract_data['alphabet'] = $extract_data['objects'] = $extract_data['csound'] = $extract_data['settings'] = $extract_data['data'] = $extract_data['orchestra'] = $extract_data['timebase'] = $extract_data['interaction'] = $extract_data['midisetup'] = $extract_data['timebase'] = $extract_data['keyboard'] = $extract_data['glossary'] = $extract_data['cstables'] = '';
	$extract_data['templates'] = FALSE;
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		$line = preg_replace("/\s/u",' ',$line);
	//	echo $i." “".$table[$i]."”<br />";
		if($i == 0)
			$line = preg_replace("/.*(\/\/.*)/u","$1",$line);
		if($start AND is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			if($i > 1) $table_out[] = $line;
			else {
				if($extract_data['headers'] <> '')
					$extract_data['headers'] .= "<br />";
				$extract_data['headers'] .= $line;
				}
			continue;
			}
		$table_out[] = $line;
		if(is_integer($pos=strpos($line,"TEMPLATES:")) AND $pos == 0) {
			$extract_data['templates'] = TRUE;
			}
		if(is_integer($pos=strpos($line,"_mm")) AND $pos == 0) {
			$metronome = preg_replace("/.+\((.+)\).+/u","$1",$line);
			$extract_data['metronome'] = $metronome;
			$time_structure = preg_replace("/.+\)\s+_(.+)$/u","$1",$line);
			if($time_structure == "striated" OR $time_structure == "smooth")
				$extract_data['time_structure'] = $time_structure;
			}
		if(is_integer($pos=strpos($line,"-ho")) AND $pos == 0)
			$extract_data['alphabet'] = fix_file_name($line,"ho");
		else if(is_integer($pos=strpos($line,"-mi")) AND $pos == 0)
			$extract_data['objects'] = fix_file_name($line,"mi");
		else if(is_integer($pos=strpos($line,"-cs")) AND $pos == 0)
			$extract_data['csound'] = fix_file_name($line,"cs");
		else if(is_integer($pos=strpos($line,"-se")) AND $pos == 0)
			$extract_data['settings'] = fix_file_name($line,"se");
		else if(is_integer($pos=strpos($line,"-da")) AND $pos == 0)
			$extract_data['data'] = fix_file_name($line,"da");
		else if(is_integer($pos=strpos($line,"-or")) AND $pos == 0)
			$extract_data['orchestra'] = fix_file_name($line,"or");
		else if(is_integer($pos=strpos($line,"-tb")) AND $pos == 0)
			$extract_data['timebase'] = fix_file_name($line,"tb");
		else if(is_integer($pos=strpos($line,"-in")) AND $pos == 0)
			$extract_data['interaction'] = fix_file_name($line,"in");
		else if(is_integer($pos=strpos($line,"-md")) AND $pos == 0)
			$extract_data['midisetup'] = fix_file_name($line,"md");
		else if(is_integer($pos=strpos($line,"-tb")) AND $pos == 0)
			$extract_data['timebase'] = fix_file_name($line,"tb");
		else if(is_integer($pos=strpos($line,"-kb")) AND $pos == 0)
			$extract_data['keyboard'] = fix_file_name($line,"kb");
		else if(is_integer($pos=strpos($line,"-gl")) AND $pos == 0)
			$extract_data['glossary'] = fix_file_name($line,"gl");
		else if($line <> '') $start = FALSE;
		}
	$extract_data['content'] = implode(chr(10),$table_out);
	return $extract_data;
	}

function fix_file_name($line,$type) {
	// Detect for instance: "-se.:somefile.bpse"
	$goodline = $line;
	if(is_integer($pos=strpos($line,"<"))) return '';
	if(is_integer($pos=strpos($line,":")) AND $pos == 4) {
		$line = substr($line,5,strlen($line) - 5);
		$extension = "bp".$type;
		$goodline = str_replace(".".$extension,'',$line);
		$goodline = str_replace(".","_",$goodline);
		$goodline .= ".".$extension;
		if($goodline <> $line)
			echo "<p style=\"color:red;\">ERROR: incorrect file name ‘-".$type.".:".$line."’, it should be ‘-".$type.".:".$goodline."’</p>";
		}
	return $goodline;
	}

function window_name($text) {
	$text = str_replace('-','_',$text);
	$text = str_replace(' ','_',$text);
	$text = str_replace('"','_',$text);
	$text = str_replace("'",'_',$text);
	return $text;
	}
	
function display_more_buttons($content,$url_this_page,$dir,$objects_file,$csound_file,$alphabet_file,$settings_file,$orchestra_file,$interaction_file,$midisetup_file,$timebase_file,$keyboard_file,$glossary_file) {
	global $bp_application_path, $csound_resources, $output_file, $file_format, $test;
	$page_type = str_replace(".php",'',$url_this_page);
	$page_type = preg_replace("/\.php.*/u",'',$url_this_page);
	
	$dir = str_replace($bp_application_path,'',$dir);
	if($test) echo "dir = ".$dir."<br />";
	
	if($page_type == "grammar" OR $page_type == "alphabet" OR $page_type == "glossary" OR $page_type == "interaction") {
		if(isset($_POST['show_help_entries'])) {
			$entries = display_help_entries($content);
			echo $entries."<br />";
			}
		else {
			echo "<div style=\"float:right; margin-top:6px;\">";
			echo "<form method=\"post\" action=\"".$url_this_page."#help_entries\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"output_file\" value=\"".$output_file."\">";
			echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
			echo "<input style=\"background-color:azure;\" type=\"submit\" name=\"show_help_entries\" value=\"SHOW HELP ENTRIES\">";
			echo "</form></div>";
			}
		}
	echo "<table style=\"padding:0px; background-color:white; border-spacing: 2px;\" cellpadding=\"0px;\"><tr>";
	if($alphabet_file <> '') {
		$url_this_page = "alphabet.php?file=".urlencode($dir.$alphabet_file);
		
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" name=\"openobjects\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$alphabet_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($objects_file <> '') {
		$url_this_page = "objects.php?file=".urlencode($dir.$objects_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$objects_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($csound_file <> '') {
		$url_this_page = "csound.php?file=".urlencode($csound_resources.SLASH.$csound_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\" onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$csound_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($settings_file <> '') {
		$url_this_page = "settings.php?file=".urlencode($dir.$settings_file);
		
		if($test) echo "url_this_page = ".$url_this_page."<br />";
		
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$settings_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($orchestra_file <> '') {
		$url_this_page = "orchestra.php?file=".urlencode($dir.$orchestra_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$orchestra_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($interaction_file <> '') {
		$url_this_page = "interaction.php?file=".urlencode($dir.$interaction_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$interaction_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($midisetup_file <> '') {
		$url_this_page = "midisetup.php?file=".urlencode($dir.$midisetup_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$midisetup_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($timebase_file <> '') {
		$url_this_page = "timebase.php?file=".urlencode($dir.$timebase_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$timebase_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($keyboard_file <> '') {
		$url_this_page = "keyboard.php?file=".urlencode($dir.$keyboard_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$keyboard_file."’\">&nbsp;";
		echo "</td></form>";
		}
	if($glossary_file <> '') {
		$url_this_page = "glossary.php?file=".urlencode($dir.$glossary_file);
		echo "<td><form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input style=\"background-color:yellow;\" type=\"submit\"  onclick=\"this.form.target='_blank';return true;\" value=\"EDIT ‘".$glossary_file."’\">&nbsp;";
		echo "</td></form>";
		}
	echo "</tr></table>";
	return;
	}

function ask_create_new_file($url_this_page,$filename) {
	echo "File ‘".$filename."’ not found. Do you wish to create a new one under that name?";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p style=\"text-align:left;\"><input style=\"background-color:yellow;\" type=\"submit\" name=\"createfile\" value=\"YES\">";
	echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" name=\"dontcreate\" value=\"NO\"></p>";
	echo "</form>";
	die();
	}

function try_create_new_file($file,$filename) {
	if(isset($_POST['dontcreate'])) {
		echo "<p style=\"color:red;\">No file created. You can close this tab…</p>";
		die();
		}
	if(isset($_POST['createfile'])) {
		echo "<p style=\"color:red;\">Creating ‘".$filename."’…</p>";
		$handle = fopen($file,"w");
		fclose($handle);
		}
	}

function compile_help($text_help_file,$html_help_file) {
//	echo "text_help_file = ".$text_help_file."<br />";
//	echo "html_help_file = ".$html_help_file."<br />";
	$help = array();
	$help[0] = '';
	$no_entry = array("ON","OFF","vel");
	if(!file_exists($text_help_file)) {
		echo "<p style=\"color:green;\">Warning: “BP2_help.html” has not been reconstructed.</p>";
		return '';
		}
	$content = @file_get_contents($text_help_file,TRUE);
	if($content) {
		$file_header = "<!DOCTYPE HTML>\n";
		$file_header .= "<html lang=\"en\">";
		$file_header .= "<head>";
		$file_header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
		$file_header .= "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
		$file_header .= "<script>\n";
		$file_header .= "function unhide(divID) {
	    var x = document.getElementById(divID);
	    if(x) {
	      x.className=(x.className=='hidden')?'unhidden':'hidden'; }
	  }\n";
		$file_header .= "</script>\n";
		$file_header .= "</head>";
		$file_header .= "<body>\n";
		$content = str_replace("<","&lt;",$content);
		$content = str_replace(">","&gt;",$content);
		$content = str_replace(chr(10),"<br />",$content);
		$content = str_replace("  ","&nbsp;&nbsp;",$content); // Remove tabulations
		$table = explode("###",$content);
		$handle = fopen($html_help_file,"w");
		$file_header .= "<p style=\"color:green;\">".$table[0]."</p>";
		$im = count($table);
		for($i = 1; $i < $im; $i++) {
			$table2 = explode("<br />",$table[$i]);
			$thetitle = trim($table2[0]);
			if($thetitle == "END OF BP2 help") {
			//	$im--;
				break;
				}
			$title[$i] = $thetitle;
			$item[$i] = '';
			for($j = 1; $j < count($table2); $j++)
				$item[$i] .= $table2[$j]."<br />";
			}
		fwrite($handle,$file_header."\n");
		$table_of_contents = "<table style=\"border-spacing: 2px;\" cellpadding=\"2px;\"><tr>";
		$col = 1;
		for($i = 1; $i < $im; $i++) {
			if($col > 2) {
				$col = 1;
				$table_of_contents .= "</tr><tr>";
				}
			if(isset($title[$i]) AND $title[$i] <> '') {
				$table_of_contents .= "<td><small><a href=\"#".$i."\">".$title[$i]."</a></small></td>";
				$col++;
				$token = preg_replace("/\s?\[.*$/u",'',$title[$i]);
				$token = preg_replace("/\s?\(.*$/u",'',$token);
		//		$token = preg_replace("/\s?:.*$/u",'',$token);
				if(!in_array($token,$no_entry))
					$help[$i] = $title[$i];
				else $help[$i] = '';
				}
			}
		$table_of_contents .= "</tr></table>";
		$table_header = "<h4 id=\"toc\" style=\"color:red;\">►&nbsp; Table of contents <a  href=\"javascript:unhide('up');unhide('up2');unhide('down');\"><span id=\"down\" class=\"unhidden\">[Show list…]</span></a>&nbsp;<a  href=\"javascript:unhide('up');unhide('up2');unhide('down');\"><span id=\"up2\" class=\"hidden\">[Hide list…]</span></a></h4>";
		$table_header  .= "<div id=\"up\" class=\"hidden\">";
		$table_header  .= $table_of_contents;
		$table_header  .= "<p style=\"text-align:center;\">[<a class=\"triangle\" href=\"javascript:unhide('up');unhide('up2');unhide('down');\">Hide list…</a>]</p></div>";
		fwrite($handle,$table_header."\n");
		for($i = 1; $i < $im; $i++) {
			if(!isset($title[$i])) continue;
			fwrite($handle,"<h4 style=\"color:green;\" id=\"".$i."\"><a href=\"#toc\">⇪</a> ".$title[$i]."</h4>\n");
			fwrite($handle,$item[$i]."\n");
			}
		fwrite($handle,"</body>");
		fclose($handle);
		}
	return $help;
	}

function link_to_help() {
	global $html_help_file;
	$console_link = "produce.php?instruction=help";
	$link = "<p>➡ <a onclick=\"window.open('".$html_help_file."','Help','width=800,height=500'); return false;\" href=\"".$html_help_file."\">Display complete help file</a> or the console's <a href=\"".$console_link."\" onclick=\"window.open('".$console_link."','help','width=800,height=800,left=200'); return false;\">help file</a></p>";
	return $link;
	}

function display_help_entries($content) {
	$table = explode("\n",$content);
	$ignore = FALSE;
	$entries = "<br /><table id=\"help_entries\" style=\"border-spacing: 2px;\"><tr><td style=\"padding:1em; background-color:azure;\">";
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		$last_one = FALSE;
		if(is_integer(strpos($line,"COMMENT:")) OR is_integer(strpos($line,"DATA:"))) $last_one = TRUE;
		$line = preg_replace("/\/\/.*$/u",'',$line);
		if(strlen($line) < 2) continue;
		if(!$ignore) $line = add_help_links($line);
		if($last_one) $ignore = TRUE;
		$entries .= $line."<br />";
		}
	$entries .= "</td></tr></table>";
	return $entries;
	}

function add_help_links($line) {
	global $help, $html_help_file;
	if(is_integer($pos=strpos($line,"-")) AND $pos == 0) return $line;
	$done = array();
	for($i = count($help) - 1; $i > 0; $i--) {
		if(!isset($help[$i])) continue;
		$token = preg_replace("/\s?\[.*$/u",'',$help[$i]);
		$token = preg_replace("/\s?\(.*$/u",'',$token);
		$token = preg_replace("/\s?«.*$/u",'',$token);
		$token = preg_replace("/\s?:.*$/u",'',$token);
		$token_length = strlen($token);
		if($token_length < 2) continue;
		$start = 0;
		$start_max = strlen($line) - $token_length;
		do {
			if(is_integer($pos=strpos($line,$token,$start))) {
				if(isset($done[$pos])) $start = $pos + strlen($token);
				else {
					$pos1 = $pos;
					$pos2 = $pos + strlen($token);
					$l1 = substr($line,0,$pos1);
					$l2 = substr($line,$pos1,strlen($token));
					$l3 = substr($line,$pos2,strlen($line) - $pos2);
					$insert = "<a onclick=\"window.open('".$html_help_file."#".$i."','show_".$i."','width=800,height=300'); return false;\" href=\"".$html_help_file."#".$i."\">";
					$line = $l1.$insert.$l2."</a>".$l3;
					$posdone = $pos1 + strlen($insert);
					$done[$posdone] = TRUE;
					// We should not insert another help link if a shorter token has been found at the same position
					// For instance: “_velcont _vel”
					break;
					}
				}
			else break;
			}
		while($start <= $start_max);
		}
	return $line;
	}

function gcd ($a, $b) {
    return $b ? gcd($b, $a % $b) : $a;
	}

function gcd_array($array,$a = 0) {
    $b = array_pop($array);
    return($b === null) ?
        (int)$a :
        gcd_array($array, gcd($a,$b));
	}

function clean_up_encoding($create_bullets,$convert,$text) {
	if($convert) $text = mb_convert_encoding($text, "UTF-8", mb_detect_encoding($text, "UTF-8, ISO-8859-1, ISO-8859-15", true));
	$text = str_replace("¥","•",$text);
	$text = str_replace("Ô","‘",$text);
	$text = str_replace("Õ","’",$text);
	$text = str_replace("Ò","“",$text);
	$text = str_replace("Ó","”",$text);
	$text = str_replace("É","…",$text);
	$text = str_replace("Â","¬",$text);
	$text = str_replace("¤","•",$text);
	$text = str_replace("â¢","•",$text);
	if($create_bullets) $text = preg_replace("/\s\\.$/u"," •",$text);
	if($create_bullets) $text = preg_replace("/\s\\.([^0-9])/u"," •$1",$text);
	$text = str_replace("²","≤",$text);
	$text = str_replace("³","≥",$text);
	return $text;
	}

function recode_tags($text) {
	$text = str_replace("<","&lt;",$text);
	$text = str_replace(">","&gt;",$text);
	$text = str_replace('"',"&quot;",$text);
	return $text;
	}

function recode_entities($text) {
	$text = preg_replace("/\s*•$/u"," .",$text);
	$text = preg_replace("/\s*•[ ]*/u"," . ",$text);
	// $text = str_replace("•"," . ",$text);
	$text = str_replace(" … "," _rest ",$text);
	return $text;
	}

function clean_up_file_to_html($file) {
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$file_html = str_replace(".txt",".html",$file);
	$file_html = str_replace(".bpda",".html",$file_html);
	$text = @file_get_contents($file,TRUE);
	$text = str_replace(chr(13).chr(10),chr(10),$text);
	$text = str_replace(chr(13),chr(10),$text);
	$text = str_replace(chr(9),' ',$text);
	$text = trim($text);
	$text = recode_tags($text);
	$text = clean_up_encoding(FALSE,TRUE,$text);
	$text = str_replace("¬",'',$text);
	do $text = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$text,$count);
	while($count > 0);
	$text = str_replace(chr(10),"<br />",$text);
	$handle = fopen($file_html,"w");
	$header = "<head>\n";
	$header .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />\n";
	$header .= "</head><body>\n";
	fwrite($handle,$header."\n");
	fwrite($handle,$text."\n");
	fwrite($handle,"</body>\n");
	fclose($handle);
	return $file_html;
	}

function clean_up_file($file) { // NOT USED
	if(!file_exists($file)) {
	//	echo "<p style=\"color:red;\">ERROR file not found: ".$file."</p>";
		return '';
		}
	$text = @file_get_contents($file,TRUE);
	$text = str_replace(chr(13).chr(10),chr(10),$text);
	$text = str_replace(chr(13),chr(10),$text);
	$text = str_replace(chr(9),' ',$text);
	$text = trim($text);
	$text = recode_tags($text);
	$text = clean_up_encoding(FALSE,TRUE,$text);
	do $text = str_replace(chr(10).chr(10).chr(10),chr(10).chr(10),$text,$count);
	while($count > 0);
	$handle = fopen($file,"w");
	fwrite($handle,$text."\n");
	fclose($handle);
	return $file;
	}

function get_setting($parameter,$settings_file) {
	global $dir;
	$bp_parameter_names = @file_get_contents("settings_names.txt",TRUE);
	if($bp_parameter_names == FALSE) return "error reading settings_names.txt";
	$table = explode(chr(10),$bp_parameter_names);
	$imax = count($table);
	$imax_parameters = 0;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == "-- end --") break;
		$imax_parameters++;
		$table2 = explode(chr(9),$line);
		$r = str_replace(chr(9),".",$line);
		if(count($table2) < 3) echo "ERR: ".$table2[0]."<br />";
		$parameter_name[$i] = $table2[0];
		$parameter_unit[$i] = $table2[1];
		$parameter_edit[$i] = $table2[2];
		if(count($table2) > 3 AND $table2[3] > 0)
			$parameter_yesno[$i] = TRUE;
		else $parameter_yesno[$i] = FALSE;
		}
	$content = @file_get_contents($dir.$settings_file,TRUE);
	if($content == FALSE) return "error reading ".$dir.$settings_file;
	$extract_data = extract_data(TRUE,$content);
	$content = $extract_data['content'];
	$table = explode(chr(10),$content);
	$i = -1;
	if($parameter == "note_convention") $i = 47;
	if($parameter == "show_production") $i = 14;
	if($parameter == "trace_production") $i = 17;
	if($parameter == "produce_all_items") $i = 13;
	if($parameter == "random_seed") $i = 45;
	if($parameter == "non_stop_improvize") $i = 10;
	if($parameter == "striated_time") $i = 6;
	if($parameter == "p_clock") $i = 7;
	if($parameter == "q_clock") $i = 8;
	if($parameter == "max_time_computing") $i = 44;
	if($parameter == "diapason") $i = 63;
	if($parameter == "C4key") $i = 62;
	if($parameter == "csound_default_orchestra") $i = 1;
	if($i <> -1) return $table[$i];
	else return '';
	}

function note_convention($i) {
	switch($i) {
		case 0: $c = "english"; break;
		case 1: $c = "italian/Spanish/French"; break;
		case 2: $c = "indian"; break;
		case 3: $c = "keys"; break;
		case 4: $c = "custom"; break;
		}
	return $c;
	}

function my_rmdir($src) {
    $dir = opendir($src);
    while(FALSE !== ($file = readdir($dir))) {
        if(($file <> '.' ) && ($file <> '..')) {
            $full = $src.'/'.$file;
            if(is_dir($full)) my_rmdir($full);
            else unlink($full);
            }
        }
    closedir($dir);
    rmdir($src);
    return;
	}

function SaveObjectPrototypes($verbose,$dir,$filename,$temp_folder) {
	global $top_header, $test, $temp_dir;
	$file_lock = $filename."_lock";
	$time_start = time();
	$time_end = $time_start + 3;
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) unlink($file_lock);
		sleep(1);
		}
	$handle = fopen($dir.$file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	$handle = fopen($dir.$filename,"w");
	$file_header = $top_header."\n// Object prototypes file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	$PrototypeTickKey = $_POST['PrototypeTickKey'];
	fwrite($handle,$PrototypeTickKey."\n");
	$PrototypeTickChannel = $_POST['PrototypeTickChannel'];
	fwrite($handle,$PrototypeTickChannel."\n");
	$PrototypeTickVelocity = $_POST['PrototypeTickVelocity'];
	fwrite($handle,$PrototypeTickVelocity."\n");
	$CsoundInstruments_filename = $_POST['CsoundInstruments_filename'];
	fwrite($handle,$CsoundInstruments_filename."\n");
	$maxsounds = $_POST['maxsounds'];
	fwrite($handle,$maxsounds."\n");
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		$table = explode(".",$thisfile);
		$extension = end($table);
		if($extension <> "txt") continue;
		$object_label = str_replace(".".$extension,'',$thisfile);
		if($verbose) echo $object_label." ";
		$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile,TRUE);
		$extract_data = extract_data(TRUE,$content);
		$headers = $extract_data['headers'];
		if(!is_integer($pos=strpos($headers,"//"))) continue;
		$content = $extract_data['content'];
		$table = explode(chr(10),$content);
		$line = "<HTML>".$object_label."</HTML>";
		fwrite($handle,$line."\n");
		$object_foldername = clean_folder_name($object_label);
		$save_codes_dir = $temp_dir.$temp_folder.SLASH.$object_foldername."_codes";
		$midi_bytes = $save_codes_dir."/midibytes.txt";
		$comment_this_prototype = '';
		for($i = 1; $i < count($table); $i++) {
			if($i > 10 AND trim($table[$i]) == '') break;
			$line = $table[$i];
			if(is_integer($pos=strpos($line,"<HTML>"))) {
				$comment_this_prototype = $line;
				$comment_this_prototype = str_replace("<HTML>",'',$comment_this_prototype);
				$comment_this_prototype = trim(str_replace("</HTML>",'',$comment_this_prototype));
				}
			else fwrite($handle,$line."\n");
			}
		fwrite($handle,"_beginCsoundScore_\n");
		$csound_file = $save_codes_dir."/csound.txt";
		$csound_score = @file_get_contents($csound_file,TRUE);
		$table2 = explode(chr(10),$csound_score);
		$csound_score = "<HTML>";
		for($k = 0; $k < count($table2); $k++) {
			$line = trim($table2[$k]);
			if($line <> '') $csound_score .= $line."<BR>";
			}
		$csound_score .= "</HTML>";
		
		fwrite($handle,$csound_score."\n");
		fwrite($handle,"_endCsoundScore_\n");
		// We fetch MIDI codes from a separate "midibytes.txt" file
		$all_bytes = @file_get_contents($midi_bytes,TRUE);
		$table_bytes = explode(chr(10),$all_bytes);
		$found = FALSE;
		for($j = 0; $j < count($table_bytes); $j++) {
			$byte = trim($table_bytes[$j]);
			if($byte <> '') {
				fwrite($handle,$byte."\n");
				$found = TRUE;
				}
			}
		if(!$found) fwrite($handle,"0\n"); // This is required because "midibytes.txt" might be empty when there is no MIDI code. The first number in the prototypes file is the number of MIDI codes that follow.
		$comment_this_prototype = "<HTML>".$comment_this_prototype."</HTML>";
		fwrite($handle,$comment_this_prototype."\n");
		}
	fwrite($handle,"DATA:\n");
	$comment_on_file = $_POST['comment_on_file'];
	$comment_on_file = recode_tags($comment_on_file);
	fwrite($handle,"<HTML>".$comment_on_file."</HTML>\n");
	fwrite($handle,"_endSoundObjectFile_\n");
	fclose($handle);
	if($verbose) echo "</font></p><hr>";
//	sleep(1);
	unlink($dir.$file_lock);
	return;
	}

function SaveCsoundInstruments($verbose,$dir,$filename,$temp_folder) {
	global $top_header, $test, $temp_dir;
//	$verbose = TRUE;
	if($verbose) echo "dir = ".$dir."<br />";
	if($verbose) echo "filename = ".$filename."<br />";
	if($verbose) echo "temp_folder = ".$temp_folder."<br />";
	$file_lock2 = $dir.$filename."_lock2";
	if(file_exists($file_lock2)) return "locked";
	$file_lock = $filename."_lock";
	$time_start = time();
	$time_end = $time_start + 3;
	while(TRUE) {
		if(!file_exists($file_lock)) break;
		if(time() > $time_end) unlink($file_lock);
		sleep(1);
		}
	$handle = fopen($dir.$file_lock,"w");
	fwrite($handle,"lock\n");
	fclose($handle);
	unlink($dir.$filename);
	$handle = fopen($dir.$filename,"w");
	$file_header = $top_header."\n// Csound resource file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	$number_channels = $_POST['number_channels'];
	fwrite($handle,$number_channels."\n");
	for($ch = 0; $ch < $number_channels; $ch++) {
		$arg = "whichCsoundInstrument_".$ch;
		$whichCsoundInstrument = convert_empty($_POST[$arg]);
		fwrite($handle,$whichCsoundInstrument."\n");
		}
	$CsoundOrchestraName = $_POST['CsoundOrchestraName'];
	$warn_not_empty = FALSE;
	if($CsoundOrchestraName == '') {
		$CsoundOrchestraName = "default.orc";
		$warn_not_empty = TRUE;
		}
	fwrite($handle,$CsoundOrchestraName."\n");
	$number_instruments = $_POST['number_instruments'];
	fwrite($handle,$number_instruments."\n");
	$dircontent = scandir($temp_dir.$temp_folder);
	foreach($dircontent as $thisfile) {
		if($thisfile == '.' OR $thisfile == ".." OR $thisfile == ".DS_Store") continue;
		$table = explode(".",$thisfile);
		$extension = end($table);
		if($extension <> "txt") continue;
		$instrument_label = str_replace(".".$extension,'',$thisfile);
		if($verbose) echo $instrument_label." ";
		$content = file_get_contents($temp_dir.$temp_folder.SLASH.$thisfile,TRUE);
		$extract_data = extract_data(FALSE,$content);
		$headers = $extract_data['headers'];
		if(!is_integer($pos=strpos($headers,"//"))) continue;
		$content = $extract_data['content'];
		$table = explode(chr(10),$content);
		fwrite($handle,$instrument_label."\n");
		for($i = 1; $i < count($table); $i++) {
			$line = $table[$i];
			fwrite($handle,$line."\n");
			}
		$number_param = 0;
		$instrument_folder_name = str_replace(' ','_',$instrument_label);
		$instrument_folder_name = str_replace('-','_',$instrument_folder_name);
		$folder_this_instrument = $temp_dir.$temp_folder.SLASH.$instrument_folder_name;
		if(!is_dir($folder_this_instrument)) mkdir($folder_this_instrument);
		$dir_instrument = scandir($folder_this_instrument);
		foreach($dir_instrument as $thisparameter) {
			if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store") continue;
			$table = explode(".",$thisparameter);
			$extension = end($table);
			if($extension <> "txt") continue;
			$number_param++;
			}
		fwrite($handle,$number_param."\n");
		foreach($dir_instrument as $thisparameter) {
			if($thisparameter == '.' OR $thisparameter == ".." OR $thisparameter == ".DS_Store") continue;
			$table = explode(".",$thisparameter);
			$extension = end($table);
			if($extension <> "txt") continue;
			$content_parameter = file_get_contents($folder_this_instrument.SLASH.$thisparameter,TRUE);
			$table = explode(chr(10),$content_parameter);
			for($i = 0; $i < count($table); $i++) {
				$line = trim($table[$i]);
				if($line <> '' OR $i < 2) fwrite($handle,$line."\n");
				}
			}
		}
	$begin_tables = $_POST['begin_tables'];
	fwrite($handle,$begin_tables."\n");
	if($verbose) echo "<br />".$begin_tables."<br />";
	if(isset($_POST['cstables'])) $cstables = $_POST['cstables'];
	else {
		if($verbose) echo "Used 'the_tables'<br />";
		$cstables = $_POST['the_tables']; // We need a different POST used by autosave
		}
	$table = explode(chr(10),$cstables);
	for($i = 0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if($verbose) echo $line."<br />";
		if($line == '') continue;
		fwrite($handle,$line."\n");
		}
	$folder_scales = $temp_dir.$temp_folder.SLASH."scales";
	$dir_scale = scandir($folder_scales);
	foreach($dir_scale as $this_scale) {
		if($this_scale == '.' OR $this_scale == ".." OR $this_scale == ".DS_Store") continue;
		$table = explode(".",$this_scale);
		$extension = end($table);
		if($extension <> "txt") continue;
		$content_scale = file_get_contents($folder_scales.SLASH.$this_scale,TRUE);
		$table = explode(chr(10),$content_scale);
		for($i = 0; $i < count($table); $i++) {
			$line = trim($table[$i]);
			if($line <> '') fwrite($handle,$line."\n");
			}
		}
	fwrite($handle,"_end tables\n");
	fclose($handle);
//	sleep(1);
	unlink($dir.$file_lock);
	return $warn_not_empty;
	}
	
function reformat_grammar($verbose,$grammar_file) {
	if(!file_exists($grammar_file)) return;
	$content = @file_get_contents($grammar_file,TRUE);
	$new_content = $content;
	$i_gram = $irul = 1;
	$section_headers = array("RND","ORD","LIN","SUB","SUB1","TEM","POSLONG","LEFT","RIGHT","INIT:","TIMEPATTERNS:","DATA:","COMMENTS:");
	$table = explode(chr(10),$new_content);
	$ignore_all = FALSE;
	$i_line_max = count($table);
	for($i_line = 0; $i_line < $i_line_max; $i_line++) {
		$line = trim($table[$i_line]);
		$line_no_brackets = preg_replace("/\s*?\[.*\]/u",'',$line);
		$ignore = FALSE;
		if($line_no_brackets == '') $ignore = TRUE;
		if(!is_integer(strpos($line,"-->")) AND !is_integer(strpos($line,"<->")) AND !is_integer(strpos($line,"<--"))) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"--")) AND $pos == 0) {
			$i_gram++; $irul = 1;
			$ignore = TRUE;
			}
		if(is_integer($pos=strpos($line,"-")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"_")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=strpos($line,"[")) AND $pos == 0) $ignore = TRUE;
		if(is_integer($pos=stripos($line,"gram#")) AND $pos == 0) {
			$ignore = TRUE;
			$line = preg_replace("/^GRAM#/u","gram#",$line);
			$line = preg_replace("/^gram#([0-9]+)\[([0-9]+)\]/u","gram#".$i_gram."[".$irul."]",$line);
			$irul++;
			}
		if(in_array($line_no_brackets,$section_headers)) $ignore = TRUE;
		if($line_no_brackets == "TIMEPATTERNS:") {
			if($verbose) echo $line."<br />";
			$i_line++;
			do {
				$line = trim($table[$i_line]);
				$table[$i_line] = $line;
				if($verbose) echo $line."<br />";
				$i_line++;
				}
			while(!is_integer($pos=strpos($line,"--")) AND $i_line < $i_line_max);
			continue;
			}
		if($line_no_brackets == "DATA:" OR $line_no_brackets == "COMMENTS:") $ignore_all = TRUE;
		if(!$ignore AND !$ignore_all) {
			$line = "gram#".$i_gram."[".$irul."] ".$line;
			$irul++;
			}
	//	$line = str_replace(" . "," • ",$line);
		if($verbose) echo $line."<br />";
		$table[$i_line] = $line;
		}
	$new_content = implode(chr(10),$table);
	// $grammar_file = "-gr._test";
	$handle = fopen($grammar_file,"w");
	fwrite($handle,$new_content);
	fclose($handle);
	return;
	}

function clean_folder_name($name) {
	// It shouldn't create trouble when part of PHP, Javascript or command-line arguments
	$name = str_replace('_','-',$name);
	$name = str_replace(' ','-',$name);
	$name = str_replace("'",'-',$name);
	$name = str_replace('"','-',$name);
	return $name;
	}

function convert_mf2t_to_bytes($verbose,$midi_import,$midi,$midi_file) {
//	$verbose = TRUE;
	// midi_file contains the code in MIDI format
	$midi->importMid($midi_file);
	$midi_bytes = array();
	$jcode = 5;
	$tt = 0; // We ask for absolute time stamps
	$old_tempo = $tempo = 1000000; // Default value
	$text = $midi->getTxt($tt);
	$handle = fopen($midi_import,"w");
	$table = explode(chr(10),$text);
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(" ",$line);
		if(isset($table2[3]) AND $table2[0] == "MFile") {
			$old_division = intval($table2[3]);
			}
		if(isset($table2[2]) AND $table2[1] == "Tempo" AND $table2[0] == "0") {
			$tempo = intval($table2[2]);
			break;
			}
		}
	$division = $tempo / 1000; 
	$alpha = $division / $old_division;
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
	//	echo $line."<br />";
		$table2 = explode(" ",$line);
		if(isset($table2[3]) AND $table2[0] == "MFile") {
			$table2[3] = $division;
			$line = implode(' ',$table2);
			fwrite($handle,$line."\n");
			continue;
			}
		if(isset($table2[2]) AND $table2[1] == "Tempo" AND $table2[0] == "0") {
			$table2[2] = $tempo;
			$line = implode(' ',$table2);
			fwrite($handle,$line."\n");
			continue;
			}
		if(count($table2) > 3 OR (isset($table2[2]) AND ($table2[2] == "TrkEnd" OR $table2[1] == "PrCh"))) {
			$time = round($table2[0] * $alpha);
			$table2[0] = $time;
			$line = implode(' ',$table2);
			}
		fwrite($handle,$line."\n");
		if(count($table2) < 4) continue;
		$chan = str_replace("ch=",'',$table2[2]);
		$code[0] = $code[1] = $code[2] = $code[3] = -1;
		if(isset($table2[4]) AND $table2[1] == "TimeSig" AND $table2[0] == "0") {
			$midi_bytes[2] = $table2[2];
			$midi_bytes[3] = $table2[3];
			$midi_bytes[4] = $table2[4];
			}
		else if(isset($table2[1]) AND $table2[1] == "ChPr") {
			$val = str_replace("v=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Channel pressure ".$val."<br />";
			$code[0] = 208 + $chan - 1;
			$code[1] = $val;
			}
		else if(isset($table2[1]) AND $table2[1] == "Pb") {
			$val = str_replace("v=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Pitchbend ".$val."<br />";
			$code[0] = 224 + $chan - 1;
			$code[1] = $val % 256;
			$code[2] = ($val - $code[1]) / 256;
			}
		else if(isset($table2[1]) AND $table2[1] == "PrCh") {
			$prog = str_replace("p=",'',$table2[3]);
			if($verbose) echo $time." (ch ".$chan.") Prog change ".$prog."<br />";
			$code[0] = 192 + $chan - 1;
			$code[1] = $prog;
			}
		else if(isset($table2[1]) AND $table2[1] == "On") {
			$key = str_replace("n=",'',$table2[3]);
			$vel = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") NoteOn ".$key." ".$vel."<br />";
			$code[0] = 144 + $chan - 1;
			$code[1] = $key;
			$code[2] = $vel;
			}
		else if(isset($table2[1]) AND $table2[1] == "Off") {
			$key = str_replace("n=",'',$table2[3]);
			$vel = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") NoteOff key ".$key." ".$vel."<br />";
			$code[0] = 128 + $chan - 1;
			$code[1] = $key;
			$code[2] = $vel;
			}
		else if(isset($table2[1]) AND $table2[1] == "Par") {
			$ctrl = str_replace("c=",'',$table2[3]);
			$val = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") Parameter ctrl ".$ctrl." ".$val."<br />";
			$code[0] = 176 + $chan - 1;
			$code[1] = $ctrl;
			$code[2] = $val;
			}
		else if(isset($table2[1]) AND $table2[1] == "PoPr") {
			$key = str_replace("n=",'',$table2[3]);
			$val = str_replace("v=",'',$table2[4]);
			if($verbose) echo $time." (ch ".$chan.") Poly pressure key ".$key." ".$val."<br />";
			$code[0] = 160 + $chan - 1;
			$code[1] = $key;
			$code[2] = $val;
			}
		$time_signature = 256 * $time;
		for($j = 0; $j < 4; $j++) {
			if($code[$j] >= 0) {
				$byte = $time_signature + $code[$j];
				$midi_bytes[$jcode++] = $byte;
				}
			}
		}
	$midi_bytes[0] = $division;
	$midi_bytes[1] = $tempo;
	fclose($handle);
//	echo "ok3"; die();
	return $midi_bytes;
	}

function fix_mf2t_file($file,$tracknames) {
	$said = $bad = FALSE;
	$header = "<br /><span style=\"color:red;\">Fixed imported MIDI file:</span><ul>";
	$message = '';
	$said = $content = FALSE;
	if(file_exists($file)) $content = @file_get_contents($file,TRUE);
	if(!$content) {
		$message .= "<br /><font color=\"red\">Cannot find or open:</font> <font color=\"blue\">".$file."</font>";
		return $message;
		}
	$handle = fopen($file,"w");
	$table = explode(chr(10),$content);
	$i0 = 0;
	if(!is_integer(strpos($content," Tempo "))) {
		$i0 = 1;
		$line = trim($table[0]);
		$table2 = explode(' ',$line);
		$new_track_number = intval($table2[2]) + 1;
		$table2[2] = $new_track_number;
		$newline = implode(' ',$table2);
		fwrite($handle,$newline."\n");
		if(!$said) $message .= $header;
		$said = TRUE;
		$bad = TRUE;
		$message .= "<li>Adding header:<ul><li>".$newline."</li>";
		$line = "MTrk\n0 Meta TrkName \"header\"\n0 TimeSig 1/4 24 8\n0 Tempo 1000000\n0 KeySig 0 major\n0 Meta TrkEnd\nTrkEnd";
		fwrite($handle,$line."\n");
		$message .= "<li>".str_replace("\n","<br />",$line)."</li></ul></li>";
		}
	$track_nr = 1;
	for($i = $i0; $i < count($table); $i++) {
		$line = trim($table[$i]);
		if($line == "TrkEnd") {
			$line2 = trim($table[$i - 1]);
			if(!is_integer(strpos($line2,"TrkEnd"))) {
				if(!$said) $message .= $header;
				$said = TRUE;
				$bad = TRUE;
				$table2 = explode(' ',$line2);
				$time = intval($table2[0]);
				$newline = $time." Meta TrkEnd";
				fwrite($handle,$newline."\n");
				$message .= "<li>Added: ".$newline."</li>";
				}
			}
		fwrite($handle,$line."\n");
		if($line == "MTrk") {
			$line2 = trim($table[$i + 1]);
			$track_nr++;
			if(!is_integer(strpos($line2,"TrkName"))) {
				if(!$said) $message .= $header;
				$said = TRUE;
				$bad = TRUE;
				$newline = "0 Meta TrkName \"".$tracknames.$track_nr."\"";
				fwrite($handle,$newline."\n");
				$message .= "<li>Added: ".$newline."</li>";
				}
			}
		}
	if($bad) $message .= "</ul>";
	fclose($handle);
	return $message;
	}

function fix_number_bytes($midi_bytes) {
	$content = @file_get_contents($midi_bytes,TRUE);
	if($content) {
		$table = explode(chr(10),$content);
		$newtable = array();
		for($i = $j = 0; $i < count($table); $i++) {
			$line = trim($table[$i]);
			if($line == '') continue;
			$newtable[$j++] = $line;
			}
		$newtable[0] = $j - 1;
		$content = implode(chr(10),$newtable);
		$handle = fopen($midi_bytes,"w");
		fwrite($handle,$content);
		fclose($handle);
		}
	return;
	}
	
function duration_of_midifile($mf2t_content) {
	$duration = 0;
	$table = explode(chr(10),$mf2t_content);
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(' ',$line);
		if(count($table2) < 5) continue;
		if($table2[1] == "TimeSig" OR $table2[1] == "Tempo") continue;
		$time = intval($table2[0]);
		if($time <> $table2[0]) continue;
		if($time > $duration) $duration = $time;
		}
	return $duration;
	}
	
function mf2t_no_header($mf2t_content) {
	$result = array();
	$table = explode(chr(10),$mf2t_content);
	$found_MTrk = 0;
	for($i = 0; $i < count($table); $i++) {
		$line = $table[$i];
		$table2 = explode(' ',$line);
		if($table2[0] == "MTrk") {
			$found_MTrk++;
			}
		if($found_MTrk > 1) {
			$result[] = $line;
		//	echo $line."<br />";
			}
		else {
			if(count($table2) > 1) $x = $table2[1];
			else $x = '';
			if($x == "Par" OR $x == "On" OR $x == "Off" OR $x == "ChPr" OR $x == "PrCh") {
				$found_MTrk++;
				$result[] = "MTrk";
				$result[] = $line;
				}
			}
		}
	return $result;
	}

function metronome($p,$q) {
	$mm = round(($p * 60) / $q, 3);
	return $mm;
	}
	
function rcopy($src,$dst) {
	if(file_exists($dst)) my_rmdir($dst);
	if(is_dir($src)) {
		mkdir($dst);
		$files = scandir($src);
		foreach($files as $file)
			if($file <> "." AND $file <> "..") rcopy("$src/$file","$dst/$file");
		}
	else if(file_exists($src)) copy($src,$dst);
	return;
	}

function store($handle,$varname,$var) {
	$line = "$".$varname." = \"".$var."\";\n";
	// echo $varname."<br />";
	fwrite($handle,$line);
	return;
	}

function good_name($type,$filename,$name_mode) {
	$filename = fix_new_name($filename);
	$filename = trim($filename);
//	echo "filename = ".$filename."<br />";
	$filename = str_replace("-".$type.".",'',$filename);
	$filename = str_replace(".bp".$type,'',$filename);
//	$filename = str_replace(" ","_",$filename);
	if($name_mode == "extension")
		$filename .= ".bp".$type;
	else
		$filename = "-".$type.".".$filename;
	return $filename;
	}

function fix_new_name($name) {
	$name = str_replace('+','_',$name);
	$name = str_replace(' ','_',$name);
	$name = str_replace('/','_',$name);
	$name = str_replace('"',"'",$name);
	return $name;
	}

function MIDIparameter_argument($i,$parameter,$StartIndex,$EndIndex,$TableIndex,$param_value,$IsLogX,$IsLogY,$GEN) {
	$r = "<table>";
	$r .= "<tr>";
	$r .= "<td>";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "start";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "end";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "table";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td style=\"padding: 5px;\">";
	$r .= "<font color=\"red\">".$parameter."</font> continuous arguments";
	$r .= "</td>";
	$r .= "<td>";
	$x = $StartIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"StartIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $EndIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"EndIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $TableIndex;
	if($x < 0) $x = '';
	$r .= "<input type=\"text\" name=\"TableIndex_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "</table><br />";
	$r .= "<table>";
	$r .= "<tr>";
	$r .= "<td colspan=\"6\">";
	$r .= "▷ <font color=\"red\">".$parameter."</font> mapping of variations";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td rowspan = \"2\" style=\"padding:4px; vertical-align:middle; text-align:center;\"><small>MIDI<br /><font color=\"red\">▼</font><br />Csound</small></td>";
	$r .= "<td style=\"padding: 5px;\">";
	$x = $param_value[0];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_0_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[1];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_1_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[2];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_2_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "<input type=\"checkbox\" name=\"IsLogX_".$i."\"";
    if($IsLogX > 0) $r .= " checked";
    $r .= ">log";
	$r .= "</td>";
	$r .= "<td style=\"vertical-align:middle;\" rowspan=\"2\">";
	$r .= "GEN ";
	$x = intval($GEN);
	if($x < 10) $x = "0".$x;
	$r .= "<input type=\"text\" name=\"GEN_".$i."\" size=\"4\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "</tr>";
	$r .= "<tr>";
	$r .= "<td style=\"padding: 5px;\">";
	$x = $param_value[3];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_3_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[4];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_4_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$x = $param_value[5];
	if($x > 1000000) $x = '';
	$r .= "<input type=\"text\" name=\"paramvalue_5_".$i."\" size=\"8\" value=\"".$x."\">";
	$r .= "</td>";
	$r .= "<td>";
	$r .= "<input type=\"checkbox\" name=\"IsLogY_".$i."\"";
    if($IsLogY > 0) $r .= " checked";
    $r .= ">log";
	$r .= "</td>";
	$r .= "</table>";
	return $r;
	}

function max_argument($argmax_file) {
	include($argmax_file);
//	echo "argmax_file = ".$argmax_file."<br />";
	$max = 0;
	if(isset($last_argument)) {
		foreach($last_argument as $value) {
			if($value > $max) $max = $value;
			}
		}
	return $max;
	}
	
function set_argmax_argument($argmax_file,$name,$arg) {
	include($argmax_file);
	$text = "<xxxphp\n";
	$found = FALSE;
	if(isset($last_argument)) {
		foreach($last_argument as $key => $value) {
		//	echo "<br />key = ".$key."<br />";
			if($key == $name) {
				$value = $arg;
				$found = TRUE;
				}
			$text .= "yyylast_argument[\"".$key."\"] = ".$value.";\n";
			}
		}
	if(!$found) $text .= "yyylast_argument[\"".$name."\"] = ".$arg.";\n";
	$text .= "xxx>\n";
	$text = str_replace("xxx","?",$text);
	$text = str_replace("yyy","$",$text);
	$handle = fopen($argmax_file,"w");
	fwrite($handle,$text);
	fclose($handle);
	return;
	}

function convert_empty($value) {
	if(trim($value) == '') $value = -1;
	return($value);
	}
	
function convert2_empty($value) {
	if(trim($value) == '') $value = 2147483647; // 2^31 - 1 (Mersenne)
	return($value);
	}

function octave($convention,$key) {
	switch($convention) {
		case "English":
			$octave = intdiv($key,12) - 1;
			break;
		case "Italian/Spanish/French":
		case "Indian":
			$octave = intdiv($key,12) - 2;
			break;
		}
	return $octave;
	}

function key_to_note($convention,$key) {
	$name["English"] = array("C","Db","D","Eb","E","F","F#","G","Ab","A","Bb","B");
	$name["Italian/Spanish/French"] = array("do","reb","re","mib","mi","fa","fa#","sol","lab","la","sib","si");
	$name["Indian"] = array("sa","rek","re","gak","ga","ma","ma#","pa","dhak","dha","nik","ni");
	$octave = octave($convention,$key);
	$class = $key - (12 * intdiv($key,12));
//	echo $key." ".$octave." ".$class."<br />";
	return $name[$convention][$class].$octave;
	}

function polymetric_expression($mute,$TickKey,$TickCycle,$TickChannel,$TickVelocity,$Ptick,$Qtick,$TickDuration,$ThisTick,$p_clock,$q_clock) {
	$period = $q_clock / $p_clock;
	$p = "{";
	$imax = count($TickKey);
	$lcm = 1;
	for($i = 0; $i < $imax; $i++) {
		if($mute[$i]) continue;
		$x = gcd($TickCycle[$i] * $Qtick[$i],$Ptick[$i]);
		$y = ($TickCycle[$i] * $Qtick[$i]) / $x;
		$lcm = ($lcm * $y) / gcd($lcm,$y);
		}
	$first = TRUE;
	for($i = 0; $i < $imax; $i++) {
		if($mute[$i]) continue;
		$repeat = ($lcm * $Ptick[$i]) / ($TickCycle[$i] * $Qtick[$i]);
		if(($repeat) > 50) return "Expression is too complex!";
		$tick_period = (1000 * $period * $Qtick[$i]) / $Ptick[$i];
		$staccato = intval(100 * ($tick_period - $TickDuration[$i]) / $tick_period);
		if(!$first) $p .= ", ";
		else {
			$gcd = gcd($Ptick[$i],$Qtick[$i]);
			$pmin = $Ptick[$i] / $gcd;
			$qmin = $Qtick[$i] / $gcd;
			if($qmin > 1)
				$p .= "_tempo(".$pmin."/".$qmin.") ";
			else
				if($pmin > 1) $p .= "_tempo(".$pmin.") ";
			}
		$p .= "_chan(".$TickChannel[$i].") ";
		$p .= "_vel(".$TickVelocity[$i].") ";
		$p .= "_staccato(".$staccato.") ";
		$first = FALSE;
		for($r = 0; $r < $repeat; $r++) {
			for($j = 0; $j < $TickCycle[$i]; $j++) {
				if($ThisTick[$i][$j]) $p .= key_to_note("English",$TickKey[$i])." ";
				else $p .= "- ";
				}
			}
		}
	$p .=  "}";
	return $p;
	}

function is_variable($note_convention,$word) {
	$word = str_replace(":",'',$word);
	if($word == '') return $word;
//	echo "«".$word."»<br />";
	if($word == "S") return ''; // We take only non-startup variables
	if($word == "RND") return '';
	if($word == "ORD") return '';
	if($word == "LIN") return '';
	if($word == "SUB") return '';
	if($word == "SUB1") return '';
	if($word == "DATA") return '';
	if($word == "LEFT") return '';
	if($word == "RIGHT") return '';
	if($word == "TEMPLATES") return '';
	if($word == "COMMENT") return '';
	if($word == '') return $word;
	$word = str_replace(')','',$word);
	$word = str_replace('(','',$word);
	if($word == '') return $word;
	if($word[0] == '|' AND $word[count($word) - 1] == '|') {
		$word = str_replace('|','',$word);
		return $word;
		}
	if(!ctype_upper($word[0])) return '';
	$word = str_replace('_',' ',$word);
	$word = trim($word);
	$test = preg_replace("/.*•.*/u",'',$word);
	if($test == '') return '';
	$table = explode(' ',$word);
	if(count($table) > 1) {
		$word = $table[0];
	//	echo "“".$word."”<br />";
		}
	if($note_convention == 0) { // English convention
		$test = preg_replace("/[A-G]#?b?[0-9]/u",'',$word);
		if($test == '') return '';
		}
	return trim($word);
	}

function get_instruction($line) {
	$instruction =  preg_replace("/\s?\".*\"/u",'',$line);
	$instruction =  preg_replace("/\-\-.+$/u",'',$instruction);
	$instruction = str_replace(" ON",'',$instruction);
	$instruction = str_replace(" OFF",'',$instruction);
	$instruction =  preg_replace("/\s+=.+$/u",'',$instruction);
	$instruction =  preg_replace("/\s+[0-9]+$/u",'',$instruction);
	$instruction =  preg_replace("/Type\s.+$/u","Type",$instruction);
	$instruction =  preg_replace("/Activate\s.+$/u","Activate",$instruction);
	$instruction =  preg_replace("/Wait\sfor\s.+$/u","Wait+for",$instruction);
	$instruction =  preg_replace("/AE\s.+$/u","AE",$instruction);
	$instruction =  preg_replace("/Default\sbuffer\ssize\s.+$/u","Default buffer size",$instruction);
	$instruction =  preg_replace("/Display\sitems\s.+$/u","Display items",$instruction);
	$instruction =  preg_replace("/Display\stime\ssetting\s.+$/u","Display time setting",$instruction);
	$instruction =  preg_replace("/Expand\sselection\s?.*$/u","Expand selection",$instruction);
	$instruction =  preg_replace("/Graphic\sColor\s?.*$/u","Graphic Color",$instruction);
	$instruction =  preg_replace("/Graphic\sscale\s?.*$/u","Graphic scale",$instruction);
	$instruction =  preg_replace("/Hide\swindow\s?.*$/u","Hide window",$instruction);
	$instruction =  preg_replace("/Ignore\sconstraints\s?.*$/u","Ignore constraints",$instruction);
	$instruction =  preg_replace("/Load\ssettings\s?.*$/u","Load settings",$instruction);
	$instruction =  preg_replace("/Load\sproject\s?.*$/u","Load project",$instruction);
	$instruction =  preg_replace("/Maximum\sproduction\stime\s?.*$/u","Maximum production time",$instruction);
	$instruction =  preg_replace("/MIDI\sfile\s?.*$/u","MIDI file",$instruction);
	$instruction =  preg_replace("/MIDI\ssound\s?.*$/u","MIDI sound",$instruction);
	$instruction =  preg_replace("/Number\sstreaks\s?.*$/u","Number streaks",$instruction);
	$instruction =  preg_replace("/Object\sprototypes\s?.*$/u","Object prototypes",$instruction);
	$instruction =  preg_replace("/Open\sfile\s?.*$/u","Open file",$instruction);
	$instruction =  preg_replace("/Play\sselection\s?.*$/u","Play+selection",$instruction);
	$instruction =  preg_replace("/Play\s.+$/u","Play",$instruction);
	$instruction =  preg_replace("/Produce\sall\sitems\s?.*$/u","Produce all items",$instruction);
	$instruction =  preg_replace("/Produce\sand\splay\s.*$/u","Produce and play",$instruction);
	$instruction =  preg_replace("/Produce\stemplates$/u","Produce templates",$instruction);
	$instruction =  preg_replace("/Quantization\s.*$/u","Quantization",$instruction);
	$instruction =  preg_replace("/Reset\ssession\stime$/u","Reset session time",$instruction);
	$instruction =  preg_replace("/Reset\ssession\stime$/u","Reset session time",$instruction);
	$instruction =  preg_replace("/Select\sall\sin\swindow\s.*$/u","Select all in window",$instruction);
	$instruction =  preg_replace("/Set\soutput\sCsound\sfile\s.*$/u","Set output Csound file",$instruction);
	$instruction =  preg_replace("/Set\soutput\sMIDI\sfile\s.*$/u","Set output MIDI file",$instruction);
	$instruction =  preg_replace("/Set\srandom\sseed\s.*$/u","Set random seed",$instruction);
	$instruction =  preg_replace("/Set\sselection\s.*$/u","Set selection",$instruction);
	$instruction =  preg_replace("/Set\sVref\s.+$/u","Set Vref",$instruction);
	$instruction =  preg_replace("/Smooth\stime$/u","Smooth time",$instruction);
	$instruction =  preg_replace("/Striated\stime$/u","Striated time",$instruction);
	$instruction =  preg_replace("/Start\sstring\s.+$/u","Start string",$instruction);
	$instruction =  preg_replace("/Synchronize\sstart.*$/u","Synchronize start",$instruction);
	$instruction =  preg_replace("/Tell\s.*$/u","Tell",$instruction);
	$instruction =  preg_replace("/Tempo\s.*$/u","Tempo",$instruction);
	$instruction =  preg_replace("/Text\sColor\s.*$/u","Text Color",$instruction);
	$instruction =  preg_replace("/Tick\scycle\s.*$/u","Tick cycle",$instruction);
	$instruction =  preg_replace("/Time\sbase$/u","Time base",$instruction);
	$instruction =  preg_replace("/Time\sticks\s.*$/u","Time ticks",$instruction);
	$instruction =  preg_replace("/Time\sresolution\s.*$/u","Time resolution",$instruction);
	$instruction =  preg_replace("/Time\ssetting\sstep\s.*$/u","Time setting step",$instruction);
	$instruction =  preg_replace("/Use\sbuffer\slimit\s.*$/u","Use buffer limit",$instruction);
	$instruction =  preg_replace("/Use\seach\substitution.*$/u","Use each substitution",$instruction);
	$instruction =  preg_replace("/Wait\s.*$/u","Wait",$instruction);
	$instruction =  preg_replace("/Csound\sscore.*$/u","Csound score",$instruction);
	$instruction =  preg_replace("/Csound\strace.*$/u","Csound trace",$instruction);
	$instruction =  preg_replace("/Cyclic\splay.*$/u","Cyclic play",$instruction);
	$instruction =  preg_replace("/Freeze\swindows.*$/u","Freeze windows",$instruction);
	$instruction =  preg_replace("/Run\sscript\s.*$/u","Run script",$instruction);
	return $instruction;
	}

function getOS() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os_platform  = "Unknown OS Platform";
    $os_array     = array(
         '/windows nt 10/i'      =>  'Windows 10',
         '/windows nt 6.3/i'     =>  'Windows 8.1',
         '/windows nt 6.2/i'     =>  'Windows 8',
         '/windows nt 6.1/i'     =>  'Windows 7',
         '/windows nt 6.0/i'     =>  'Windows Vista',
         '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
         '/windows nt 5.1/i'     =>  'Windows XP',
         '/windows xp/i'         =>  'Windows XP',
         '/windows nt 5.0/i'     =>  'Windows 2000',
         '/windows me/i'         =>  'Windows ME',
         '/win98/i'              =>  'Windows 98',
         '/win95/i'              =>  'Windows 95',
         '/win16/i'              =>  'Windows 3.11',
         '/macintosh|mac os x/i' =>  'Mac OS X',
         '/mac_powerpc/i'        =>  'Mac OS 9',
         '/linux/i'              =>  'Linux',
         '/ubuntu/i'             =>  'Ubuntu',
         '/iphone/i'             =>  'iPhone',
         '/ipod/i'               =>  'iPod',
         '/ipad/i'               =>  'iPad',
         '/android/i'            =>  'Android',
         '/blackberry/i'         =>  'BlackBerry',
         '/webos/i'              =>  'Mobile'
          );
    foreach($os_array as $regex => $value)
        if(preg_match($regex,$user_agent))
            $os_platform = $value;
    return $os_platform;
	}

function windows_system() {
	$os_platform = getOS();
	if(PHP_OS == "WINNT" OR is_integer(strpos($os_platform,"Windows"))) return TRUE;
	return FALSE;
	}
	
function send_to_console($command) {
	global $test;
	$system = getOS();
	$exec = escapeshellcmd($command);
	$table = array();
	if(windows_system()) {
		$exec = str_replace("..".SLASH."bp ","bp ",$exec);
		$exec = str_replace("..".SLASH,dirname(getcwd()).SLASH,$exec);
	//	$exec = str_replace(DIRECTORY_SEPARATOR,"/",$exec);
		echo "<small>Windows: exec = <font color=\"red\">".$exec."</font></small><br />";
		}
	exec($exec,$table);
//	system($exec,$o);
//	passthru($exec,$o);
	return $table;
	}

function get_orchestra_filename($csound_file) {
	$csound_orchestra = '';
	$content = @file_get_contents($csound_file,TRUE);
	if($content != FALSE) {
		$extract_data = extract_data(FALSE,$content);
		$content = $extract_data['content'];
		$content_no_br = str_replace("<br>",chr(10),$content);
		$table = explode(chr(10),$content_no_br);
		$imax_file = count($table);
		$number_channels = $table[0];
		$csound_orchestra = $table[$number_channels + 1];
		}
	return $csound_orchestra;
	}

function get_name_mi_file($this_file) {
	$objects_file = '';
	$content = @file_get_contents($this_file,TRUE);
	if($content != FALSE) {
		$extract_data = extract_data(TRUE,$content);
		$objects_file = $extract_data['objects'];
		}
	return $objects_file;
	}

function MIDIfiletype($file) {
//	return 1;
	$test = TRUE;
	$MIDIfiletype = -1;	
	$fp = fopen($file,'rb');
	if(!$fp) return $MIDIfiletype;
	$i = 0;
	while(!feof($fp)) {
	    // Read the file in chunks of 16 bytes
	    $data = fread($fp,16);
	    $arr = unpack("C*",$data);
	    foreach($arr as $key => $value) {
	 	  	if($i == 9) {
	    		$MIDIfiletype = intval($value); // break;
	    		}
	    	if($test) {
		 		if($value > 63) $value = chr($value);
			  //  echo $i.") ".$key." = " .$value." = ". ord($value)."<br />";
		    	}
			$i++; 
	    	}
	    if($MIDIfiletype  >= 0) break;
		}
	fclose($fp);
	return $MIDIfiletype;
	}

function copyemz($file1,$file2){
	$contentx = @file_get_contents($file1);
	$openedfile = fopen($file2, "w");
	fwrite($openedfile, $contentx);
	fclose($openedfile);
	if($contentx === FALSE) {
		$status = false;
		}
	else $status = true;
	return $status;
    }
    
 function save_settings($variable,$value) {
	$settings_file = "_settings.php";
//	echo "value = ".$value."<br />";
	$content = @file_get_contents($settings_file,TRUE);
	if($content != FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
		//	echo "line =  ".$line."<br />";
			if(is_integer($pos=strpos($line,$variable))) {
				$line = preg_replace("/=\s?\".+\"\s?;/u","= \"".$value."\";",$line);
		//		echo "line2 =  ".$line."<br />";
				$found = TRUE;
				}
			if(strlen($line) == 0) continue;
			$new_table[$i] = $line;
			}
		if(!$found) {
			$line = "§".$variable." = \"".$value."\";";
			$line = str_replace('§','$',$line);
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
	//	echo "content = ".$content."<br />";
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	else echo "<p><font color=\"red\">File ‘_settings.php’ could nor be opened!</p>";
 	return;
 	}
    
 function save_settings2($variable,$index,$value) {
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
	if($content != FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
	//		echo "line =  ".$line."<br />";
			if(is_integer($pos=strpos($line,$variable."[\"".$index."\"]"))) {
				$line = preg_replace("/=\s?\".+\"\s?;/u","= \"".$value."\";",$line);
		//		echo "line2 =  ".$line."<br />";
				$found = TRUE;
				}
			if(strlen($line) == 0) continue;
			$new_table[$i] = $line;
			}
		if(!$found) {
			$line = "§".$variable."[\"".$index."\"] = \"".$value."\";";
			$line = str_replace('§','$',$line);
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
	//	echo "content = ".$content."<br />";
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
		}
	else echo "<p><font color=\"red\">File ‘_settings.php’ could nor be opened!</p>";
 	return;
 	}
 	
 function delete_settings($file) {
	$settings_file = "_settings.php";
	$content = @file_get_contents($settings_file,TRUE);
 	if($content != FALSE) {
		$table = explode(chr(10),$content);
		$imax = count($table);
		$new_table = array();
		$found = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
			if(strlen($line) == 0) continue;
			if(is_integer($pos=strpos($line,"<")) AND $pos == 0) continue;
			if(is_integer($pos=strpos($line,">"))) continue;
			if(is_integer($pos=strpos($line,"[\"".$file."\"]"))) continue;
			$new_table[$i] = $line;
			}
		$content = implode("\n",$new_table);
		$handle = fopen($settings_file,"w");
		fwrite($handle,"<?php\n");
		fwrite($handle,$content);
		$line = "\n§>\n";
		$line = str_replace('§','?',$line);
		fwrite($handle,$line);
		fclose($handle);
 		}
 	return;
 	}
 
function check_csound() {
	global $csound_path, $csound_resources, $dir_csound_resources, $path, $url_this_page, $file_format;
	$command = $csound_path."csound --version";
	exec($command,$result_csound,$return_var);
	if($return_var <> 0) {
		echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		if(isset($file_format)) echo "<input type=\"hidden\" name=\"file_format\" value=\"".$file_format."\">";
		echo "<p><img src=\"pict/logo_csound.jpg\" width=\"90px;\" style=\"vertical-align:middle;\" />&nbsp;is not installed or its path (<font color= \"blue\">".$csound_path."</font>) is incorrect.<br/>";
		echo "Try this path: <input type=\"text\" name=\"csound_path\" size=\"30\" style=\"background-color:CornSilk;\" value=\"".$csound_path."\">";
		echo "&nbsp;<input style=\"background-color:yellow;\" type=\"submit\" value=\"TRY\">";
		echo "<br />";
		echo "<font color=\"red\">➡</font>&nbsp;<a target=\"_blank\" href=\"https://csound.com/download.html\">Follow this link</a> to install Csound and convert scores to sound files.</p>";
		echo "</form><p>";
		$result = FALSE;
		}
	else {
		echo "<p style=\"vertical-align:middle;\"><img src=\"pict/logo_csound.jpg\" width=\"90px;\" style=\"vertical-align:middle;\" />&nbsp;is installed and responsive.<br />";
		$result = TRUE;
		}
	if($path <> $csound_resources) echo "<font color=\"red\">➡</font>&nbsp;<a target=\"_blank\" href=\"index.php?path=csound_resources\">Visit Csound resource folder</a></p>";
	return $result;
	}

function relocate_function_table($dir,$line) {
	// Look for mention of external file in a ‘f1’ statement of Csound function table
	global $dir_csound_resources;
	$line = preg_replace("/\s+/u"," ",$line);
	$clean_line = preg_replace("/f\s([0-9])/u","f$1",$line);
	$table = explode(' ',$clean_line);
	if(count($table) < 5) return $clean_line;
	$p3 = abs(intval($table[3]));
	if($p3 <> 1 AND $p3 <> 23 AND $p3 <> 28 AND $p3 <> 49)
		// Doc: https://www.csounds.com/manual/html/ScoreGenRef.html
		return $clean_line;
	$filename = str_replace('"','',$table[4],$count);
	if($count > 0 AND file_exists($dir.$filename))
		rename($dir.$filename,$dir_csound_resources.$filename);
	return $clean_line;
	}

function check_function_tables($dir,$csound_file) {
	if($csound_file == '') return;
	$content = @file_get_contents($dir.$csound_file,TRUE);
	if($content == '') return;
	$table = explode(chr(10),$content);
	$imax = count($table);
	$found = FALSE;
	for($i = 0; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		if($line == "_begin tables") {
			$found = TRUE; continue;
			}
		if($found) relocate_function_table($dir,$line);
		}
	return;
	}
	
function html_to_text($text,$type) {
	if($type == "textarea") $return = "\n";
	else $return = "<br />";
	$text = preg_replace("/<\/?html>/u",'',$text);
	$text = preg_replace("/<br\s?\/?>/u","§§§",$text);
	$text = str_replace('<','',$text);
	$text = str_replace('>','',$text);
	$text = str_replace("§§§",$return,$text);
	return $text;
	}

function list_of_tonal_scales($csound_orchestra_file) {
	$list = array();
	if(!file_exists($csound_orchestra_file)) return $list;
	$content = @file_get_contents($csound_orchestra_file,TRUE);
	$table = explode(chr(10),$content);
	$imax = count($table);
	$found = FALSE;
	for($i = 0, $j = -1; $i < $imax; $i++) {
		$line = trim($table[$i]);
		if($line == '') continue;
		if($line == "_begin tables") $found = TRUE;
		if($line == "_end tables") break;
		if($found) {
			if($line[0] == "\"")
				$list[++$j] = "<font color=\"red\">".str_replace('"','',$line);
			if($line[0] == "/")
				$list[$j] .= "</font> = <font color=\"darkmagenta\"><b>".str_replace("/",'',$line)."</b></font>";
			if($line[0] == "|")
				$list[$j] .= " <font color=\"blue\">baseoctave</font> = <font color=\"red\">".str_replace("|",'',$line)."</font>";
			}
		}
	return $list;
	}

function check_duplicate_name($dir,$file) {
	$dircontent = scandir($dir);
	foreach($dircontent as $some_file) {
		if($some_file == $file) return TRUE;
	//	echo $some_file."<br />".$file."<br />";
		}
	return FALSE;
	}

function modulo($a, $b) {
	$c = $a % $b;
	if($c < 0) $c +=  $b;
	return $c;
	}

function create_fraction($ratio) {
	$p = 10000 * $ratio;
	$q = 10000;
	$gcd = gcd($p,$q);
	$result['p'] = $p / $gcd;
	$result['q'] = $q / $gcd;
	return $result;
	}
	
function simplify_fraction_eliminate_schisma($p,$q) {
	if(($p * $q) <> 0) {
		$gcd = gcd($p,$q);
		$p = $p / $gcd;
		$q = $q / $gcd;
		if($p == 1024 AND $q == 729) { // 1.404 p1
			$p = 45; // 1.406
			$q = 32;
			}
		if($p == 729 AND $q == 512) { // 1.4238 m4
			$p = 64; // 1.422
			$q = 45;
			}
		if($p == 81920 AND $q == 59049) { // 1.387 p1 - 1 syntonic comma
			$p = 325; // 1.388
			$q = 234;
			}
		if($p == 59049 AND $q == 40960) { // 1.441 m4 + 1 syntonic comma
			$p = 36; // 1.440
			$q = 25;
			}
		if($p == 2187 AND $q == 2048) { // 1.0678 limma + syntonic comma
			$p = 16; // 1.0666
			$q = 15;
			}
		if($p == 135 AND $q == 128) { // 1.05468 minor semitone + syntonic comma = limma
			$p = 256; // 1.0535 (this is the Pythagorean value)
			$q = 243;
			}
		if($p == 2048 AND $q == 2025) { // 1.0113 syntonic comma
			$p = 81; // 1.0125
			$q = 80;
			}
		if($p == 19683 AND $q == 16384) { // 1.2013 
			$p = 6; // 1.2
			$q = 5;
			}
		if($p == 4096 AND $q == 3645) { // 1.1237
			$p = 9; // 1.125
			$q = 8;
			}
		if($p == 512 AND $q == 405) { // 1.264
			$p = 81; // 1.126
			$q = 64;
			}
		if($p == 1215 AND $q == 1024) { // 1.1865
			$p = 32; // 1.1851
			$q = 27;
			}
		}
	$result['p'] = $p;
	$result['q'] = $q;
	return $result;
	}
?>
