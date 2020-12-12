<?php
header("Content-type: image/png");
$save_codes_dir = urldecode($_GET['save_codes_dir']);
$image_file = $save_codes_dir."/image.php";
require_once($image_file);

$margin_left = 50;
$width = 600;
$height = 100;

$image_width = $width + 100;
if($image_width < 800) $image_width = 800;
$im = @imagecreatetruecolor($image_width,800)
      or die('Cannot Initialize new GD image stream');
$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$grey = imagecolorallocate($im, 128, 128, 128);
$red = imagecolorallocate($im,233,14,91);
$orange = imagecolorallocate($im,220,210,60);
$yellow = imagecolorallocate($im,255,255,5);
$blue = imagecolorallocate($im,0,255,255);
$green = imagecolorallocate($im,174,240,217);

imagefilledrectangle($im,0,0,$image_width,800,$white);

$text = "Scale \"".$filename."\"";
imagestring($im,10,$margin_left,30,$text,$black);

$radius = 250;
// https://www.php.net/manual/en/function.imagearc.php
// imagearc(resource $image , int $cx , int $cy , int $width , int $height , int $start , int $end , int $color)
imagearc($im,$image_width/2,$radius + $height,2 * $radius,2 * $radius,0,360,$black);

for($j = 0; $j <= $numgrades_fullscale; $j++) {
	$cents = cents($ratio[$j]);
	$angle = 2 * M_PI * $cents / 1200 + (M_PI / 2);
	$x_note = $radius * cos($angle) + $image_width/2;
	$y_note = $radius * sin($angle) + $radius + $height;
//	imagearc($im,$x_note,$y_note,10,10,0,360,$red);
	
	$x1 = $x_note;
	$y1 = $y_note;
	$x2 = $x1 + 20 * cos($angle);
	$y2 = $y1 + 20 * sin($angle);
	imageline($im,$x1,$y1,$x2,$y2,$black);
	
	$text = $name[$j];
	$length_text = imagefontwidth(10) * strlen($text);
	$height_text = imagefontheight(10);
	$x_text = $x2 + 20 * cos($angle) - $length_text /  2;
	$y_text = $y2 + 20 * sin($angle) - $height_text / 2;
	imagestring($im,10,$x_text,$y_text,$text,$black);
	
	if($j < $numgrades_fullscale) {
		if(($p[$j] * $q[$j]) > 0) {
			$fraction = $p[$j]."/".$q[$j];
			$text = $fraction;
			}
		else $text = $ratio[$j];
		$length_text = imagefontwidth(10) * strlen($text);
		$height_text = imagefontheight(10);
		$x_text = $x2 + (50 + $length_text) * cos($angle) - $length_text/2;
		$y_text = $y2 + (50 + $height_text/2) * sin($angle) - $height_text/2;
		imagestring($im,10,$x_text,$y_text,$text,$black);
		}
	}

imagepng($im);
imagedestroy($im);

// ------- functions ---------

function arrow($im,$x1,$y1,$x2,$y2,$alength,$awidth,$relocate,$color) {
    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
    $dy = $y2 + ($y1 - $y2) * $alength / $distance;
    $k = $awidth / $alength;
    $x2o = $x2 - $dx;
    $y2o = $dy - $y2;
    $x3 = $y2o * $k + $dx;
    $y3 = $x2o * $k + $dy;
    $x4 = $dx - $y2o * $k;
    $y4 = $dy - $x2o * $k;
    if(!$relocate) imagefilledrectangle($im,$x1-1,$y1,$x1+1,$y2-2,$color);
    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
	}

function cents($ratio) {
	$cents = 1200 * log($ratio) / log(2);
	return $cents;
	}
?>