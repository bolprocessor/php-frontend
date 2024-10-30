<?php
require_once("_basic_tasks.php");
$url_this_page = "preview_musicxml.php";
if(isset($_GET['title'])) $this_title = urldecode($_GET['title']);
else $this_title = "MusicXML file";
require_once("_header.php");

$application_path = $bp_application_path;

$filter = ''; $no_filter = $print_this = TRUE;
if(isset($_GET['filter'])) {
	$filter = $_GET['filter']; $no_filter = $print_this = FALSE;
	}

if(isset($_GET['music_xml_file'])) $music_xml_file = urldecode($_GET['music_xml_file']);
else $music_xml_file = '';

$defaults = $credit = $part_list = $print = $notations = $system = $staff = FALSE;
$couleur = $this_measure = '';
$forget_words = array("staff","accidental","stem","staves","words","/words","notations","slur","/measure","print","/print","pitch","/pitch","offset");
$file = fopen($music_xml_file,"r");
$arrow = "<span class=\"red-text\">➡</span>&nbsp;";
if($no_filter) echo "<span class=\"orange-text\">";
while(!feof($file)) {
	$line = fgets($file);
	$line_print = htmlentities($line);
	if($no_filter AND is_integer($pos=strpos($line,"<identification"))) echo "</span><span class=\"green-text\">";
	if($no_filter AND is_integer($pos=strpos($line,"</identification"))) {
		if($no_filter) echo "</span>";
		continue;
		}
	if(is_integer($pos=strpos($line,"<part "))) {
		$part = trim(preg_replace("/.*id=\"([^\"]+)\".*/u","$1",$line));
		$line_print = "<br /><b>== Part ".$part." ==</b><br />";
		echo $line_print;
		continue;
		}
	if(is_integer($pos=strpos($line,"<!DOCTYPE"))) continue;
	if(is_integer($pos=strpos($line,"<measure "))) {
		$number = trim(preg_replace("/.*number=\"([^\"]+)\".*/u","$1",$line));
		$this_measure = "<br /><b><span class=\"green-text\">== Measure #".$number." ==</span></b><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"</measure"))) {
		if($print_this) echo $this_measure;
		$this_measure = '';
		if(!$no_filter) $print_this = FALSE;
		continue;
		}
	$couleur = '';
	if(is_integer($pos=strpos($line,"<pedal"))) {
		if($filter == "pedal") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"trill-mark"))) {
		if($filter == "trill") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"slur"))) {
		if($filter == "slur") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"mordent"))) {
		if($filter == "mordent") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"breath-mark"))) {
		if($filter == "breath") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"<turn"))) {
		if($filter == "turn") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"<fermata"))) {
		if($filter == "fermata") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}
	if(is_integer($pos=strpos($line,"<arpeggiate"))) {
		if($filter == "arpeggio") {
			$print_this = TRUE; $couleur = "green-text";
			$this_measure .= $arrow;
			}
		$this_measure .= "<span class=\"".$couleur."\">".$line_print."</span><br />";
		continue;
		}

	if(is_integer($pos=strpos($line,"</defaults"))) {
		$defaults = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<defaults"))) $defaults = TRUE;
	if($defaults) continue;
	if(is_integer($pos=strpos($line,"</system"))) {
		$system = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<system"))) $system = TRUE;
	if($system) continue;
	if(is_integer($pos=strpos($line,"</staff"))) {
		$staff = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<staff"))) $staff = TRUE;
	if($staff) continue;
	if(is_integer($pos=strpos($line,"</credit"))) {
		$credit = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<credit"))) $credit = TRUE;
	if($credit) continue;
	if(is_integer($pos=strpos($line,"</part-list"))) {
		$part_list = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<part-list"))) $part_list = TRUE;
	if($part_list) continue;
	if(is_integer($pos=strpos($line,"</notations"))) {
		$notations = FALSE; continue;
		}
	if(is_integer($pos=strpos($line,"<notations"))) $notations = TRUE;
	if($notations) continue;

	if(is_integer($pos=strpos($line,"<direction"))) continue;
	if(is_integer($pos=strpos($line,"</direction"))) continue;
	$found = FALSE;
	for($i = 0; $i < count($forget_words); $i++)
		if($found = is_integer($pos=strpos($line,"<".$forget_words[$i]))) break;
	if($found) continue;
	$couleur = '';
	if(is_integer(strpos($line,"<step>")) OR is_integer(strpos($line,"<octave>")) OR is_integer(strpos($line,"<alter>")) OR is_integer(strpos($line,"<rest/>"))) $couleur = "red-text";
	if(is_integer(strpos($line,"<duration>"))OR is_integer(strpos($line,"<grace/>"))) $couleur = "blue-text";
	if($couleur <> '') $line_print = "<span class=\"".$couleur."\">".$line_print."</span>";
//	$couleur = '';
	if($no_filter AND $this_measure == '') echo $line_print."<br />";
	else $this_measure .= $line_print."<br />";
	}
?>
