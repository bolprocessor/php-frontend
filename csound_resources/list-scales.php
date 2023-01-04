<?php
echo "<html>";
echo "<header><title>Tuning schemes on Bol Processor</title></header>";
echo "<body>";
echo "<h1>Scales</h1>";
echo "<p><i>All tuning schemes implemented in</i> ‘<font color=\"blue\">-cs.tryTunings</font>’<i>. Click to display image!</i></p>";
$dir = "scale_images";
$dircontent = scandir($dir); $left = 0;
foreach($dircontent as $file) {
	if($file == '.' OR $file == ".." OR $file == ".DS_Store") continue;
	$table = explode(".",$file);
	$extension = end($table);
    if($extension <> "png") continue;
    $name = str_replace(".".$extension,'',$file);
    $name = str_replace("F_","F#",$name);
    $link = $dir."/".$file;
    echo "<a onclick=\"window.open('".$link."','".$name."_image','width=800,height=625,left=".$left."'); return false;\" href=\"\">".$name."</a><br >";
    $left += 50; if($left > 500) $left = 0;
    }
echo "</body>";
echo "</html>"; 
?>