<?php
header('Content-Type: image/png; charset=utf-8');
$save_codes_dir = urldecode($_GET['save_codes_dir']);
$image_file = $save_codes_dir."/image.php";
require_once($image_file);

$margin_left = 50;
$width = 600;
$height = 130;

$image_width = $width + 100;
if($image_width < 800) $image_width = 800;
$im = @imagecreatetruecolor($image_width,800)
      or die('Cannot Initialize new GD image stream');
$white = imagecolorallocate($im,255,255,255);
$black = imagecolorallocate($im,0,0,0);
$grey = imagecolorallocate($im, 128, 128, 128);
$red = imagecolorallocate($im,233,14,91);
$olive = imagecolorallocate($im,220,210,60);
$yellow = imagecolorallocate($im,255,255,5);
$blue = imagecolorallocate($im,1,13,245);
$green = imagecolorallocate($im,0,255,127);
$brown = imagecolorallocate($im,202,110,101);
$purple = imagecolorallocate($im,219,125,214);

imagefilledrectangle($im,0,0,$image_width,800,$white);

$text = "Scale \"".$filename."\"";
imagestring($im,10,$margin_left,30,$text,$black);

$radius = 230;
$x_center = $image_width /  2;
$y_center = $radius + $height;
$crown_thickness = 15;

circle($im,$x_center,$y_center,$radius,$black);
circle($im,$x_center,$y_center,$radius + $crown_thickness,$black);

for($j = 0; $j <= $numgrades_fullscale; $j++) {
	$angle = 2 * M_PI * cents($ratio[$j]) / 1200 + (M_PI / 2);
	
	$coord = set_point($radius,$ratio[$j]);
	$x_note = $coord['x'];
	$y_note = $coord['y'];
	$x1 = $x_note;
	$y1 = $y_note;
	$coord = set_point($radius + $crown_thickness + 10,$ratio[$j]);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
//	imageline($im,$x1,$y1,$x2,$y2,$black);
	imagesmoothline($im,$x1,$y1,$x2,$y2,$black);
	
	$text = $name[$j];
	$length_text = imagefontwidth(10) * strlen($text);
	$height_text = imagefontheight(10);
	$x_text = $x2 + 20 * cos($angle) - $length_text /  2;
	$y_text = $y2 + 20 * sin($angle) - $height_text / 2;
	imagestring($im,10,$x_text,$y_text,$text,$red);
	
	if($j < $numgrades_fullscale) {
		
		// Print fraction or ratio
		if(($p[$j] * $q[$j]) > 0) {
			$fraction = $p[$j]."/".$q[$j];
			$text = $fraction;
			}
		else $text = round($ratio[$j],3);
		$length_text = imagefontwidth(10) * strlen($text);
		$height_text = imagefontheight(10);
		$coord = set_point(50 + $length_text,$ratio[$j]);
		$x_text = $x2 - $x_center + $coord['x'] - $length_text / 2;
		$coord = set_point(50 + $height_text / 2,$ratio[$j]);
		$y_text = $y2 - $y_center + $coord['y'] - $height_text / 2;
		imagestring($im,10,$x_text,$y_text,$text,$black);
		
		// Print cents
		$y_text += imagefontheight(10) + 2;
		$text = $cents[$j];
		if($text <> '') $text .= 'c'; // &#162;
		if(isset($cents[$j]) AND $cents[$j] > 0) imagestring($im,10,$x_text,$y_text,$text,$blue);
		}
	}

if(isset($fifth)) foreach($fifth as $j => $k) {
	connect($im,$j,$k,$radius,$blue,3);
	}

if(isset($wolthfifth)) foreach($wolthfifth as $j => $k) {
	connect($im,$j,$k,$radius,$red,1);
	}

if(isset($harmthird)) foreach($harmthird as $j => $k) {
	connect($im,$j,$k,$radius,$green,3);
	}

if(isset($pyththird)) foreach($pyththird as $j => $k) {
	connect($im,$j,$k,$radius,$olive,1);
	}
	
// mark($im,1.25,$black);
if(isset($p_comma) AND isset($q_comma) AND ($p_comma * $q_comma) > 0)
	$comma_ratio = $p_comma / $q_comma;
else 
	if(isset($syntonic_comma)) $comma_ratio = exp($syntonic_comma/1200. * log(2));
	
$mark_ratio = 1;
for($i = 0; $i < 7; $i++) {
	mark($im,$mark_ratio,$red);
	if($i > 0 AND isset($comma_ratio)) mark($im,$mark_ratio / $comma_ratio,$green);
	$mark_ratio = $mark_ratio * 3;
	}
$mark_ratio = 1;
for($i = 0; $i < 7; $i++) {
	mark($im,$mark_ratio,$red);
	if($i > 0 AND isset($comma_ratio)) mark($im,$mark_ratio * $comma_ratio,$green);
	$mark_ratio = $mark_ratio / 3;
	}
		
imagepng($im);
imagedestroy($im);

// ------- functions ---------

function set_point($radius,$ratio) {
	global $x_center,$y_center;
	$cents = cents($ratio);
	$angle = 2 * M_PI * $cents / 1200 + (M_PI / 2);
	$coord['x'] = $radius * cos($angle) + $x_center;
	$coord['y'] = $radius * sin($angle) + $y_center;
	return $coord;
	}

function mark($im,$ratio,$color) {
	global $radius,$crown_thickness;
	$coord = set_point($radius,$ratio);
	$x1 = $coord['x'];
	$y1 = $coord['y'];
	$coord = set_point($radius + $crown_thickness,$ratio);
	$x2 = $coord['x'];
	$y2 = $coord['y'];
	imagesmoothline($im,$x1,$y1,$x2,$y2,$color);
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
//	else imagelinedotted($im, $x1, $y1, $x2, $y2,2 ,$color);
	else imagesmoothline($im,$x1,$y1,$x2,$y2,$color);
	}

function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick) {
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

function imagelinedotted ($im, $x1, $y1, $x2, $y2, $dist, $col) {
    $transp = imagecolortransparent($im);
   	$style = array ($col);
   	for ($i=0; $i<$dist; $i++) {
        array_push($style, $transp); // Generate style array - loop needed for customisable distance between the dots
    	}
   	imagesetstyle ($im, $style);
    return (integer) imageline ($im, $x1, $y1, $x2, $y2, IMG_COLOR_STYLED);
    imagesetstyle ($im, array($col)); // Reset style - just in case...
	}


function circle($im,$x_center,$y_center,$radius,$color) {
// https://www.php.net/manual/en/function.imagearc.php
	imagearcthick($im,$x_center,$y_center,2 * $radius,2 * $radius,0,360,$color,2);
	return;
	}

function imagearcthick($image, $x, $y, $w, $h, $s, $e, $color, $thick = 1)
{
    if($thick == 1)
    {
        return imagearc($image, $x, $y, $w, $h, $s, $e, $color);
    }
    for($i = 1;$i<($thick+1);$i++)
    {
        imagearc($image, $x, $y, $w-($i/5), $h-($i/5),$s,$e,$color);
        imagearc($image, $x, $y, $w+($i/5), $h+($i/5), $s, $e, $color);
    }
}

function cents($ratio) {
	$cents = 1200 * log($ratio) / log(2);
	return $cents;
	}

function imagesmoothline($image,$x1,$y1,$x2,$y2,$color) {
  $colors = imagecolorsforindex($image,$color);
  if ( $x1 == $x2 ) {
   imageline ( $image , $x1 , $y1 , $x2 , $y2 , $color ); // Vertical line
  }
  else
  {
   $m = ( $y2 - $y1 ) / ( $x2 - $x1 );
   $b = $y1 - $m * $x1;
   if ( abs ( $m ) <= 1 )
   {
    $x = min ( $x1 , $x2 );
    $endx = max ( $x1 , $x2 );
    while ( $x <= $endx )
    {
     $y = $m * $x + $b;
     $y == floor ( $y ) ? $ya = 1 : $ya = $y - floor ( $y );
     $yb = ceil ( $y ) - $y;
     $tempcolors = imagecolorsforindex ( $image , imagecolorat ( $image , $x , floor ( $y ) ) );
     $tempcolors['red'] = $tempcolors['red'] * $ya + $colors['red'] * $yb;
     $tempcolors['green'] = $tempcolors['green'] * $ya + $colors['green'] * $yb;
     $tempcolors['blue'] = $tempcolors['blue'] * $ya + $colors['blue'] * $yb;
     if ( imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) == -1 ) imagecolorallocate ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] );
     imagesetpixel ( $image , $x , floor ( $y ) , imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) );
     $tempcolors = imagecolorsforindex ( $image , imagecolorat ( $image , $x , ceil ( $y ) ) );
     $tempcolors['red'] = $tempcolors['red'] * $yb + $colors['red'] * $ya;
      $tempcolors['green'] = $tempcolors['green'] * $yb + $colors['green'] * $ya;
     $tempcolors['blue'] = $tempcolors['blue'] * $yb + $colors['blue'] * $ya;
     if ( imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) == -1 ) imagecolorallocate ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] );
     imagesetpixel ( $image , $x , ceil ( $y ) , imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) );
     $x ++;
    }
   }
   else
   {
    $y = min ( $y1 , $y2 );
    $endy = max ( $y1 , $y2 );
    while ( $y <= $endy )
    {
     $x = ( $y - $b ) / $m;
     $x == floor ( $x ) ? $xa = 1 : $xa = $x - floor ( $x );
     $xb = ceil ( $x ) - $x;
     $tempcolors = imagecolorsforindex ( $image , imagecolorat ( $image , floor ( $x ) , $y ) );
     $tempcolors['red'] = $tempcolors['red'] * $xa + $colors['red'] * $xb;
     $tempcolors['green'] = $tempcolors['green'] * $xa + $colors['green'] * $xb;
     $tempcolors['blue'] = $tempcolors['blue'] * $xa + $colors['blue'] * $xb;
     if ( imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) == -1 ) imagecolorallocate ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] );
     imagesetpixel ( $image , floor ( $x ) , $y , imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) );
     $tempcolors = imagecolorsforindex ( $image , imagecolorat ( $image , ceil ( $x ) , $y ) );
     $tempcolors['red'] = $tempcolors['red'] * $xb + $colors['red'] * $xa;
     $tempcolors['green'] = $tempcolors['green'] * $xb + $colors['green'] * $xa;
     $tempcolors['blue'] = $tempcolors['blue'] * $xb + $colors['blue'] * $xa;
     if ( imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) == -1 ) imagecolorallocate ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] );
     imagesetpixel ( $image , ceil ( $x ) , $y , imagecolorexact ( $image , $tempcolors['red'] , $tempcolors['green'] , $tempcolors['blue'] ) );
     $y ++;
    }
   }
  }
}
?>