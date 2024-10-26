<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$dir_scales = $_POST['dir_scales'];
$url_this_page = "compare_scales.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$warn_not_empty = FALSE;
$max_scales = 0;
$num_grades = 12;
$current_directory = str_replace(SLASH.$filename,'',$file);

require_once("_header.php");
display_darklight();

echo "<p><small>Current directory = <a class=\"linkdotted\" href=\"index.php?path=".urlencode($current_directory)."\">".$dir."</a></small></p>";
echo link_to_help();

echo "<h2>Comparison of scales selected in tonality resource file “".$filename."”</h2>";
$temp_folder = str_replace(' ','_',$filename)."_".my_session_id()."_temp";
$dir_scales = $temp_dir.$temp_folder.SLASH."scales".SLASH;

$dircontent = scandir($dir_scales);
$i_scale = $k_image = 0;
foreach($dircontent as $scale_file) {
	$scale_name = str_replace(".txt",'',$scale_file);
	if(isset($_POST['compare_'.str_replace(' ','_',$scale_name)])) {
		$name[$i_scale] = $scale_name;
		$content = file_get_contents($dir_scales.$scale_file,TRUE);
		$table = explode(chr(10),$content);
		$imax = count($table);
		$dir_image = $dir_scale_images.$scale_name.".png";
		echo "<span class=\"green-text\">".$scale_name."</span>";
		if(file_exists($dir_image)) {
			$k_image++; if($k_image > 10) $k_image = 0;
			echo " ➡&nbsp;".popup_link($scale_name,"image",500,410,(100 * $k_image),$dir_image);
			}
		echo "<br />";
		$ignore = FALSE;
		for($i = 0; $i < $imax; $i++) {
			$line = trim($table[$i]);
		//	echo $line."<br />";
			if(is_integer($pos=strpos($line,"/")) AND $pos == 0) {
				$line = str_replace("/",'',$line);
				$table2 = explode(' ',$line);
				for($grade = 0; $grade < count($table2); $grade++)  {
					$note[$i_scale][$grade] = $table2[$grade];
					}
				continue;
				}
			if(is_integer($pos=strpos($line,"f")) AND $pos == 0) {
				// Line of description in Csound format
				$table2 = explode(' ',$line);
				$this_num_grades = $table2[4];
				if($this_num_grades <> $num_grades) {
					echo "<font color=\"red\">➡</font> Ignored because it has ".$this_num_grades." grades. Only ".$num_grades." allowed.<br />";
					$ignore = TRUE;
					}
				else {
					if(count($table2) < ($num_grades + 9)) {
						echo "<font color=\"red\">➡</font> Ignored because this line is incomplete: ".$line."<br />";
						$ignore = TRUE;
						}
					else {
						echo "positions = ";
						for($grade = 0; $grade < $num_grades; $grade++) {
							$ratio[$i_scale][$grade] = $table2[$grade + 8];
							echo $ratio[$i_scale][$grade]." / ";
							}
						echo "<br />positions (cents) = ";
						for($grade = 0; $grade < $num_grades; $grade++) {
							$ratio[$i_scale][$grade] = $table2[$grade + 8];
							echo round(1200 * log($ratio[$i_scale][$grade]) / log(2),1)." ¢ / ";
							}
						echo "<br />fifths = ";
						// Calculate fifths
						for($low = 0; $low < $num_grades; $low++) {
							$high = $low + 7; // This should be changed if num_grades ≠ 12
							if($high >= $num_grades) {
								$high -= $num_grades;
								$up_ratio = 2. * $ratio[$i_scale][$high];
								}
							else $up_ratio = $ratio[$i_scale][$high];
							$fifth_ratio = $up_ratio / $ratio[$i_scale][$low];
							$fifth_cents[$i_scale][$low] = 1200. * log($fifth_ratio) / log(2);
							echo round($fifth_cents[$i_scale][$low],1)." ¢ / ";
							}
						echo "<br />";
						$name[$i_scale] = $scale_name;
						$i_scale++;
						}
					}
				}
			if($ignore) break;
			}
		echo "<br />";
		}
	}
$num_scales = $i_scale; echo $num_scales." scales<br />";

echo "<h3>Average difference of fifths:</h3>";
echo "<table class=\"thicktable\">";
echo "<tr><td></td>";
for($i = 0; $i < $num_scales; $i++) echo "<td><span class=\"green-text\">".$name[$i]."</span></td>";
echo "</tr>";
for($j = 0; $j < $num_scales; $j++) {
	echo "<tr>";
	echo "<td><span class=\"green-text\">".$name[$j]."</span></td>";
	for($i = 0; $i < $num_scales; $i++) {
		if($i < $j) echo "<td></td>";
		else echo "<td>".round(distance($fifth_cents,$num_grades,$i,$j,0),1)." ¢</td>";
		}
	echo "</tr>";
	}
echo "</table><br />";

echo "<h3>Smallest average difference of fifths (trying transposition to every grade):</h3>";
echo "<table class=\"thicktable\">";
echo "<tr><td></td>";
for($i = 0; $i < $num_scales; $i++) echo "<td><span class=\"green-text\">".$name[$i]."</span></td>";
echo "</tr>";
for($j = 0; $j < $num_scales; $j++) {
	echo "<tr>";
	echo "<td><span class=\"green-text\">".$name[$j]."</span></td>";
	for($i = 0; $i < $num_scales; $i++) {
		if($i < $j) echo "<td></td>";
		else {
			$best_match = best_match($fifth_cents,$num_grades,$i,$j);
			$number_fifth_shift = $best_match['shift'];
			$grade_shift = 7 * $number_fifth_shift;
			$grade_shift = $number_fifth_shift;
			while($grade_shift >= $num_grades) $grade_shift -= $num_grades;
			if($number_fifth_shift > ($num_grades / 2)) $number_fifth_shift -= $num_grades;
			if($number_fifth_shift >= 0) $number_fifth_shift = "+".$number_fifth_shift;
	//		echo "<td>".round($best_match['dist'],1)." c <font color=\"red\">".$note[$i][$grade_shift]."</font> (".$number_fifth_shift.")</td>";
			echo "<td>".round($best_match['dist'],1)." ¢ <font color=\"red\">".$note[$i][$grade_shift]."</font></td>";
			}
		}
	echo "</tr>";
	}
echo "</table>";
echo "</body></html>";

function best_match($fifth,$num_grades,$scale_1,$scale_2) {
	$min = 1200; $shift_min = 0;
	for($shift = 0; $shift < $num_grades; $shift++) {
		$distance = distance($fifth,$num_grades,$scale_1,$scale_2,$shift);
		if($distance < $min) {
			$min = $distance;
			$shift_min = $shift;
			}
		}
	$best_match['dist'] = $min;
	$best_match['shift'] = $shift_min;
	return $best_match;
	}

function distance($fifth,$num_grades,$scale_1,$scale_2,$shift) {
	$sum_squares = 0.;
	for($i = 0; $i < $num_grades; $i++) {
		$j = $i + $shift;
		while($j >= $num_grades) $j -= $num_grades;
		$d = ($fifth[$scale_2][$i] - $fifth[$scale_1][$j]);
		$sum_squares += $d * $d;
		}
	$distance = sqrt($sum_squares / $num_grades);
	return $distance;
	}
?>
