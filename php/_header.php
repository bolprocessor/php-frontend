<?php
echo "<!DOCTYPE HTML>";
echo "<html lang=\"en\">";
echo "<head>";
echo "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
echo "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
if(isset($filename)) echo "<title>".$filename."</title>\n";
else if(isset($this_title)) echo "<title>".$this_title."</title>\n";
echo "<link rel=\"shortcut icon\" href=\"pict/bp3_logo_ico\" />";

echo "<script>\n";
// The following might be used later
echo "function copyToClipboard(text) {
    var input = document.createElement('input');
    input.setAttribute('value', text);
    document.body.appendChild(input);
    input.select();
    var result = document.execCommand('copy');
    document.body.removeChild(input);
    alert('You copied: “'+text+'”');
    return result;
 }\n";
echo "</script>\n";

// https://www.midijs.net/
echo "<script type='text/javascript' src='https://www.midijs.net/lib/midi.js'></script>";

echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js\"></script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan').fadeOut('fast');
	}, 3000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan2').fadeOut('fast');
	}, 8000);\n";
echo "</script>\n";

echo "</head>";
echo "<body>\n";
?>
