<?php
$save_codes_dir = urldecode($_GET['save_codes_dir']);
$dir_scale_images = urldecode($_GET['dir_scale_images']);
if(isset($_GET['csound_source'])) $csound_source = urldecode($_GET['csound_source']);
else $csound_source = '';
$image_file = $save_codes_dir."/image.php";
$margin_left = 15;
$width = 900;
$top = 160;
$image_width = $width + 100;
$image_height = 820;
// if($image_width < 1000) $image_width = 1000;
require_once($image_file);
header("title: ".$filename); // Does not work!
header("Content-Type: image/png; charset=utf-8");
if(isset($_GET['no_marks'])) $no_marks = $_GET['no_marks'];
else $no_marks = 0;
if(isset($_GET['no_cents'])) $no_cents = $_GET['no_cents'];
else $no_cents = 0;
if(isset($_GET['no_intervals'])) $no_intervals = $_GET['no_intervals'];
else $no_intervals = 0;
if(isset($_GET['no_hilite'])) $no_hilite = $_GET['no_hilite'];
else $no_hilite = 0;

$im = @imagecreatetruecolor($image_width,$image_height)
      or die('Cannot Initialize new GD image stream');
$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$grey = imagecolorallocate($im, 128, 128, 128);
$red = imagecolorallocate($im,233,14,91);
$olive = imagecolorallocate($im,220,210,60);
$yellow = imagecolorallocate($im,255,255,5);
$gold = imagecolorallocate($im,255,209,2);
$blue = imagecolorallocate($im,1,13,245);
$azure = imagecolorallocate($im,240,255,255);
$green = imagecolorallocate($im,0,255,127);
$brown = imagecolorallocate($im,202,110,101);
$purple = imagecolorallocate($im,219,125,214);
$lemonchiffon = imagecolorallocate($im,255,250,205);
$lightcyan = imagecolorallocate($im,224,255,255);
$papayawhip = imagecolorallocate($im,255,239,213);

imagefilledrectangle($im,0,0,$image_width,$image_height,$white);

$text = "Scale \"".$filename."\"";
imagestring($im,10,$margin_left,10,$text,$black);
if(isset($syntonic_comma)) {
	$text = "Comma = ".round($syntonic_comma,1)." cents";
	imagestring($im,10,$margin_left,30,$text,$black);
	}
if(isset($csound_source) AND $csound_source <> '') {
	$text = "Source: ".$csound_source;
	imagestring($im,10,$margin_left,50,$text,$black);
	}
$text = "Saved: ".gmdate('Y-m-d')."";
imagestring($im,10,$margin_left,70,$text,$black);

$radius = 220;
$x_center = $image_width /  2;
$y_center = $radius + $top;
$crown_thickness = 30;

if(!$no_intervals AND isset($p_addpos)) {
	// List additional intervals (lines in black)
	$text = "Added: ";
	for($i = 0; $i < count($p_addpos); $i++) {
		if($i > 0) $text .= "  ";
		$text .= $p_addpos[$i]."/".$q_addpos[$i];
		}
	$x1 = $x_center + $radius / 3;
	$x1 = $image_width - 10 * strlen($text) - 80;
	$x2 = $x1 + 60;
	$y1 = 20;
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$black,2);
	$x1 = $x2 + 15;
	if($i > 0) imagestring($im,10,$x1,$y_text,$text,$black);
	}

for($j = $numgrades_with_labels = 0; $j < $numgrades_fullscale; $j++) {
	if($name[$j] == '') continue;
	$numgrades_with_labels++;
	}

circle($im,$x_center,$y_center,$radius,$black);
circle($im,$x_center,$y_center,$radius + $crown_thickness,$black);

imagefilltoborder($im,$x_center + $radius + 4,$y_center,$black,$papayawhip);

// Draw intervals highlighted by tonal analysis
if($no_intervals) $color_hilite = $gold;
else $color_hilite = $gold;
if(isset($hilitewidth) AND !$no_hilite) foreach($hilitewidth as $i_match => $thiswidth) {
	if(!isset($hilitej[$i_match]) OR !isset($hilitek[$i_match])) continue;
	$j = $hilitej[$i_match];
	$k = $hilitek[$i_match];
	connect($im,$j,$k,$radius - 1,$color_hilite,$thiswidth);
	}

if(!$no_intervals) {
	// Draw classical intervals
	if(isset($wolffifth)) foreach($wolffifth as $j => $k) {
		connect($im,$j,$k,$radius - 1,$red,3);
		}
	if(isset($wolffourth)) foreach($wolffourth as $j => $k) {
		connect($im,$j,$k,$radius - 1,$purple,3);
		}
	if(isset($pyththird)) foreach($pyththird as $j => $k) {
		connect($im,$j,$k,$radius - 1,$brown,1);
		}
	if(isset($harmthird)) foreach($harmthird as $j => $k) {
		connect($im,$j,$k,$radius - 1,$green,3);
		}
	if(isset($fifth)) foreach($fifth as $j => $k) {
		connect($im,$j,$k,$radius - 1,$blue,3);
		}
	if(isset($mark)) foreach($mark as $the_mark) {
		connect($im,$the_mark['j'],$the_mark['k'],$radius - 1,$black,2);
		}
	}

if(isset($p_comma) AND isset($q_comma) AND ($p_comma * $q_comma) > 0)
	$comma_ratio = $p_comma / $q_comma;
else 
	if(isset($syntonic_comma)) $comma_ratio = exp($syntonic_comma/1200. * log(2));

if($interval_cents <> 1200 AND $no_marks == 0) $no_marks = 1;
if($no_marks < 1) {
	$mark_ratio = 1;
	for($i = 0; $i < 7; $i++) {
		mark($im,$mark_ratio,$blue);
		if($i > 0 AND isset($comma_ratio)) mark($im,$mark_ratio / $comma_ratio,$green);
		$mark_ratio = $mark_ratio * 3;
		}
	$mark_ratio = 1;
	for($i = 0; $i < 7; $i++) {
		mark($im,$mark_ratio,$blue);
		if($i > 0 AND isset($comma_ratio)) mark($im,$mark_ratio * $comma_ratio,$green);
		$mark_ratio = $mark_ratio / 3;
		}
	}

$shift_name = $oldcents = 0;
for($j = 0; $j <= $numgrades_fullscale; $j++) {
	if(!isset($ratio[$j]) OR $ratio[$j] == '') $ratio[$j] = 0;
	if($ratio[$j] == 0) continue;
	$angle = 2 * M_PI * cents($ratio[$j]) / $interval_cents + (M_PI / 2);
	$coord = set_point($radius + 2,$ratio[$j]);
	$x_note = $coord['x'];
	$y_note = $coord['y'];
	$x1 = $x_note;
	$y1 = $y_note;
	$coord = set_point($radius + $crown_thickness + 12,$ratio[$j]);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
	$color = $black;
	if($series[$j] == 'p') $color = $blue;
	if($series[$j] == 'h') $color = $green;
	imagesmoothline($im,$x1,$y1,$x2,$y2,$color);
	
	// Print names
	
		$text = $name[$j];
		$length_text = imagefontwidth(10) * strlen($text);
		$height_text = imagefontheight(10);
		$x_text = $x2 + 20 * cos($angle) - $length_text /  2;
		$y_text = $y2 + 20 * sin($angle) - $height_text / 2;
		if(abs($cents[$j] - $oldcents) > 50) $shift_name = 0;
		if(($cents[$j] > 550 AND $cents[$j] < 650) OR $cents[$j] > 1100 OR $cents[$j] < 100) {
			$shiftarea = TRUE;
			$y_text += $shift_name;
			}
		else $shiftarea = FALSE;
		$length_text_name = imagefontwidth(10) * strlen($text);
		if($text <> '' AND $j < $numgrades_fullscale) {
			imagefilledrectangle($im,$x_text - 5,$y_text,$x_text + $length_text_name + 5,$y_text + imagefontheight(10),$white);	
			imagestring($im,10,$x_text,$y_text,$text,$red);
			}
		$oldcents = $cents[$j];
		$height_text = imagefontheight(10);
		$coord = set_point(50 + $height_text / 2,$ratio[$j]);
		$y_text = $y2 - $y_center + $coord['y'] - $height_text / 2;
		if($shiftarea) $y_text += $shift_name;
		
		if(($p[$j] * $q[$j]) > 0) {
			$fraction = $p[$j]."/".$q[$j];
			$text = $fraction;
			}
		else $text = round($ratio[$j],3);
		$length_text = imagefontwidth(10) * strlen($text);
		$coord = set_point(50 + $length_text,$ratio[$j]);
		$x_text = $x2 - $x_center + $coord['x'] - $length_text / 2;
		
		if($shiftarea AND ($cents[$j] > 550 AND $cents[$j] < 650)) {
			if($shift_name < 0) $shift_name = 0;
			else if($shift_name == 0) $shift_name = imagefontheight(10) + 2;
			else $shift_name = - imagefontheight(10) - 2;
			}
		else if($shiftarea AND ($cents[$j] > 1100 OR $cents[$j] < 100)) {
			if($shift_name > 0) $shift_name = 0;
			else if($shift_name == 0) $shift_name = - imagefontheight(10) - 2;
			else $shift_name = + imagefontheight(10) + 2;
			}
		else $shift_name = 0;
		
		// Print cents
		if($no_cents) continue;
		$text2 = round($cents[$j]);
		if($text2 <> '') $text2 .= 'c';
		$y_text2 = $y_text + imagefontheight(10) + 2;
		$length_text_cents = imagefontwidth(10) * strlen($text2);
		if($numgrades_fullscale > 8 AND ($ratio[$j] < 1.126 OR ($ratio[$j] > 1.281 AND $ratio[$j] < 1.414))) $x_text -= $length_text_cents;
		if($numgrades_fullscale > 8 AND ($ratio[$j] > 1.77 OR ($ratio[$j] >= 1.414 AND $ratio[$j] < 1.57))) $x_text += $length_text_cents;
		if(isset($cents[$j]) AND $cents[$j] <> 0 AND $name[$j] <> '') {
			imagefilledrectangle($im,$x_text - 5,$y_text2,$x_text + $length_text_cents + 5,$y_text2 + imagefontheight(10),$white);
			imagestring($im,10,$x_text,$y_text2,$text2,$black);
			}
	}

$end_x = $end_y = $old_x = $old_y = 0;
for($j = 0; $j <= $numgrades_fullscale; $j++) {
	if($ratio[$j] == 0) continue;
	$angle = 2 * M_PI * cents($ratio[$j]) / $interval_cents + (M_PI / 2);
	$coord = set_point($radius + 2,$ratio[$j]);
	$x_note = $coord['x'];
	$y_note = $coord['y'];
	$x1 = $x_note;
	$y1 = $y_note;
	$coord = set_point($radius + $crown_thickness + 10,$ratio[$j]);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
	if($j < $numgrades_fullscale OR round($ratio[$j],3) <> (2 * round($ratio[0],3))) {
		$height_text = imagefontheight(10);
		$coord = set_point(50 + $height_text / 2,$ratio[$j]);
		$y_text = $y2 - $y_center + $coord['y'] - $height_text / 2;
		if($cents[$j] > 550 AND $cents[$j] < 650)
			$y_text -= imagefontheight(10);
		if(($p[$j] * $q[$j]) > 0) {
			$fraction = $p[$j]."/".$q[$j];
			$text_ratio[$j] = $fraction;
			}
		else $text_ratio[$j] = round($ratio[$j],3);
		if($j > 0 AND $text_ratio[$j] == $text_ratio[$j-1]) continue;
		$length_text = imagefontwidth(10) * strlen($text_ratio[$j]);
		$coord = set_point(50 + $length_text,$ratio[$j]);
		$x_text = $x2 - $x_center + $coord['x'] - $length_text / 2;
		
		// Print fractions or ratios
		if($y_text <= $end_y AND $y_text >= ($end_y - 2 * imagefontheight(10)) AND $x_text <= $end_x)
			$y_text += imagefontheight(10) + 2;
		$end_x = $x_text + $length_text + 5;
		if($y_text <= $old_y AND $y_text >= ($old_y + imagefontheight(10)) AND $end_x >= $old_x)
			$y_text += imagefontheight(10) + 2;
		if($y_text > $old_y AND $y_text <= ($old_y + imagefontheight(10)) AND $end_x >= $old_x AND $x_text < $old_x) 
			$y_text += imagefontheight(10) + 2;
		$end_y = $y_text + imagefontheight(10);
		
		$old_x = $x_text;
		$old_y = $y_text;
		if($name[$j] <> '') {
			imagefilledrectangle($im,$x_text - 5,$y_text,$end_x,$end_y,$azure);
			imagestring($im,10,$x_text,$y_text,$text_ratio[$j],$black);
			}
		}
	}
	
$x1 = $margin_left;
$x2 = 60;
$x_text = $x2 + 15;
$y1 = 2 * $radius + 270;
$y_text = $y1 - imagefontheight(10) / 2;

if(!$no_intervals) {
	imagestring($im,10,$x1,$y_text,"For this value of the comma:",$black);
	
	$y1 += 2 * imagefontheight(10);
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$blue,3);
	$text = "Perfect fifth (".round($perfect_fifth)." cents)";
	imagestring($im,10,$x_text,$y_text,$text,$black);
	
	$y1 += 1 * imagefontheight(10);
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$red,3);
	$text = "Wolf fifth (".round($wolf_fifth)." cents)";
	imagestring($im,10,$x_text,$y_text,$text,$red);

	$y1 += 1 * imagefontheight(10);
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$purple,3);
	$text = "Wolf fourth (".round($wolf_fourth)." cents)";
	imagestring($im,10,$x_text,$y_text,$text,$purple);
	
	$y1 += 1 * imagefontheight(10);
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$green,3);
	$text = "Harmonic major third (".round($harmonic_third)." cents)";
	imagestring($im,10,$x_text,$y_text,$text,$green);
	
	$y1 += 1 * imagefontheight(10);
	$y_text = $y1 - imagefontheight(10) / 2;
	imagelinethick($im,$x1,$y1,$x2,$y1,$brown,2);
	$text = "Pythagorean major third (".round($pythagorean_third)." cents)";
	imagestring($im,10,$x_text,$y_text,$text,$brown);
	}
	
$x1 = $image_width - 230;
$y1 = 2 * $radius + 260;
if(!$no_marks) {
	$x_text = $x1 + 10;
	$x2 = $x1;
	$y2 = $y1 + 25;
	$y_text = $y1 + 5;
	imagelinethick($im,$x1,$y1,$x2,$y2,$blue,3);
	$text = "Pythagorean position";
	imagestring($im,10,$x_text,$y_text,$text,$blue);
	$y1 = $y2 + 25;
	$y2 = $y1 + 25;
	$y_text = $y1 + 5;
	imagelinethick($im,$x1,$y1,$x2,$y2,$green,3);
	$text = "Harmonic position";
	imagestring($im,10,$x_text,$y_text,$text,$green);
	$y1 += 40;
	}
else $y1 += 95;
$x1 -= 50;
$text = "- Created by the Bol Processor -";
imagestring($im,10,$x1,$y1,$text,$black);

imagepng($im);
$clean_name_of_file = str_replace("#","_",$filename);
$clean_name_of_file = str_replace("/","_",$clean_name_of_file);
imagepng($im,$dir_scale_images.$clean_name_of_file.".png");
imagedestroy($im);

// ======================= FUNCTIONS =======================

function set_point($radius,$ratio) {
	global $x_center,$y_center,$interval_cents;
	$cents = cents($ratio);
	$angle = 2 * M_PI * $cents / $interval_cents + (M_PI / 2);
	$coord['x'] = $radius * cos($angle) + $x_center;
	$coord['y'] = $radius * sin($angle) + $y_center;
	return $coord;
	}

function mark($im,$ratio,$color) {
	global $radius,$crown_thickness;
	$coord = set_point($radius + 1,$ratio);
	$x1 = $coord['x'];
	$y1 = $coord['y'];
	$coord = set_point($radius + $crown_thickness - 2,$ratio);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
//	imagesmoothline($im,$x1,$y1,$x2,$y2,$color);
	imagelinethick($im,$x1,$y1,$x2,$y2,$color,3);
	return;
	}

function connect($im,$j,$k,$radius,$color,$thick) {
	global $ratio;
	$coord = set_point($radius,$ratio[$j]);
	$x1 = $coord['x'];
	$y1 = $coord['y'];
	$coord = set_point($radius,$ratio[$k]);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
	if($thick > 1) imagelinethick($im,$x1,$y1,$x2,$y2,$color,$thick);
//	else imagelinedotted($im, $x1, $y1, $x2, $y2,2,$color);
	else imagesmoothline($im,$x1,$y1,$x2,$y2,$color);
	}

function imagelinethick($image,$x1,$y1,$x2,$y2,$color,$thick) {
    /* this way it works well only for orthogonal lines
    imagesetthickness($image, $thick);
    return imageline($image, $x1, $y1, $x2, $y2, $color);
    */
    if($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    	}
    $t = $thick / 2 - 0.5;
    if($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    	}
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    	);
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
	}

function imagelinedotted($im, $x1, $y1, $x2, $y2, $dist, $col) {
    $transp = imagecolortransparent($im);
   	$style = array($col);
   	for ($i=0; $i<$dist; $i++) {
        array_push($style, $transp); // Generate style array - loop needed for customisable distance between the dots
    	}
   	imagesetstyle($im, $style);
    return (integer) imageline($im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
    imagesetstyle($im, array($col)); // Reset style - just in case...
	}


function circle($im,$x_center,$y_center,$radius,$color) {
// https://www.php.net/manual/en/function.imagearc.php
	imagearcthick($im,$x_center,$y_center,2 * $radius,2 * $radius,0,360,$color,5);
	return;
	}

function imagearcthick($image, $x, $y, $w, $h, $s, $e, $color, $thick) {
    if($thick == 1)
        return imagearc($image, $x, $y, $w, $h, $s, $e, $color);
    for($i = 1;$i<($thick+1);$i++) {
        imagearc($image, $x, $y, $w-($i/5), $h-($i/5),$s,$e,$color);
        imagearc($image, $x, $y, $w+($i/5), $h+($i/5), $s, $e, $color);
    	}
    return;
	}

function cents($ratio) {
	$cents = 1200 * log($ratio) / log(2);
	return $cents;
	}

function imagesmoothline($image,$x1,$y1,$x2,$y2,$color) {
  $colors = imagecolorsforindex($image,$color);
  if($x1 == $x2) {
   imageline($image, $x1, $y1, $x2, $y2, $color); // Vertical line
  }
  else
  {
   $m = ($y2 - $y1) / ($x2 - $x1);
   $b = $y1 - $m * $x1;
   if(abs($m) <= 1)
   {
    $x = min($x1, $x2);
    $endx = max($x1, $x2);
    while($x <= $endx)
    {
     $y = $m * $x + $b;
     $y == floor($y) ? $ya = 1 : $ya = $y - floor($y);
     $yb = ceil($y) - $y;
     $tempcolors = imagecolorsforindex($image, imagecolorat($image, $x, floor($y)));
     $tempcolors['red'] = $tempcolors['red'] * $ya + $colors['red'] * $yb;
     $tempcolors['green'] = $tempcolors['green'] * $ya + $colors['green'] * $yb;
     $tempcolors['blue'] = $tempcolors['blue'] * $ya + $colors['blue'] * $yb;
     if(imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
     imagesetpixel($image, $x, floor($y), imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
     $tempcolors = imagecolorsforindex($image, imagecolorat($image, $x, ceil($y)));
     $tempcolors['red'] = $tempcolors['red'] * $yb + $colors['red'] * $ya;
      $tempcolors['green'] = $tempcolors['green'] * $yb + $colors['green'] * $ya;
     $tempcolors['blue'] = $tempcolors['blue'] * $yb + $colors['blue'] * $ya;
     if(imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
     imagesetpixel($image, $x, ceil($y), imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
     $x ++;
    }
   }
   else
   {
    $y = min($y1, $y2);
    $endy = max($y1, $y2);
    while($y <= $endy)
    {
     $x = ($y - $b) / $m;
     $x == floor($x) ? $xa = 1 : $xa = $x - floor($x);
     $xb = ceil($x) - $x;
     $tempcolors = imagecolorsforindex($image, imagecolorat($image, floor($x), $y));
     $tempcolors['red'] = $tempcolors['red'] * $xa + $colors['red'] * $xb;
     $tempcolors['green'] = $tempcolors['green'] * $xa + $colors['green'] * $xb;
     $tempcolors['blue'] = $tempcolors['blue'] * $xa + $colors['blue'] * $xb;
     if(imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
     imagesetpixel($image, floor($x), $y, imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
     $tempcolors = imagecolorsforindex($image, imagecolorat($image, ceil($x), $y));
     $tempcolors['red'] = $tempcolors['red'] * $xb + $colors['red'] * $xa;
     $tempcolors['green'] = $tempcolors['green'] * $xb + $colors['green'] * $xa;
     $tempcolors['blue'] = $tempcolors['blue'] * $xb + $colors['blue'] * $xa;
     if(imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']) == -1) imagecolorallocate($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']);
     imagesetpixel($image, ceil($x), $y, imagecolorexact($image, $tempcolors['red'], $tempcolors['green'], $tempcolors['blue']));
     $y ++;
    }
   }
  }
}

function whitespaces_imagestring($image,$font,$x,$y,$string, $color) { // Not used
    $font_height = imagefontheight($font);
    $font_width = imagefontwidth($font);
    $image_height = imagesy($image);
    $image_width = imagesx($image);
    $max_characters = (int)($image_width - $x) / $font_width ;
    $next_offset_y = $y;

    for($i = 0, $exploded_string = explode("\n", $string), $i_count = count($exploded_string); $i < $i_count; $i++) {
        $exploded_wrapped_string = explode("\n", wordwrap(str_replace("\t", "    ", $exploded_string[$i]), $max_characters, "\n"));
        $j_count = count($exploded_wrapped_string);
        for($j = 0; $j < $j_count; $j++) {
            imagestring($image, $font, $x, $next_offset_y, $exploded_wrapped_string[$j], $color);
            $next_offset_y += $font_height;

            if($next_offset_y >= $image_height - $y) {
                return;
            }
        }
    }
}
?>