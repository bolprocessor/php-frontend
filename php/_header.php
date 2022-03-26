<?php
echo "<!DOCTYPE HTML>";
echo "<html lang=\"en\">";
echo "<head>";
echo "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
echo "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
if(isset($filename)) echo "<title>".$filename."</title>\n";
else if(isset($this_title)) echo "<title>".$this_title."</title>\n";
echo "<link rel=\"icon\" href=\"pict/bp3_logo.ico\" type =\"image/x-icon\" />";
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

echo "<script>\n";
echo "$(window).keydown(function(evt) {
    var key = String.fromCharCode(evt.keyCode);
    if (key.toLowerCase() === \"s\" && evt.metaKey) {
    	alert(\"Soon this key will be programmed to save data...\");
        evt.preventDefault(true);
        return false;
    }
    return true;
});";
echo "</script>\n";

echo "<script>\n";
echo "$(document).ready(function() {
  $(\"#parent1\").click(function() {
    $(\".child1\").prop(\"checked\", this.checked);
  });

  $('.child1').click(function() {
    if ($('.child1:checked').length == $('.child1').length) {
      $('#parent1').prop('checked', true);
    } else {
      $('#parent1').prop('checked', false);
    }
  });
});";
echo "</script>\n";

echo "<script>\n";
echo "function settoggledisplay() {
		var x = document.getElementById(\"showhide\");
	    if(x) {
	      x.className='hidden'; }}\n";
echo "function toggledisplay() {
	    var x = document.getElementById(\"showhide\");
	    if(x) {
	      x.className=(x.className=='hidden')?'unhidden':'hidden'; }
	  }\n";
echo "</script>\n";

echo "<script>\n";
echo "function tellsave() {\n";
echo "localStorage.setItem('data','dirty');\n";
echo "}</script>\n";

echo "<script>\n";
echo "function checksaved() {\n";
echo "if(localStorage.getItem('data') == 'dirty') {\n";
echo "alert('Data or grammar needs to be saved');\n";
echo "disableButton();\n";
echo "return false; }\n";
echo "else return true;\n";
echo "}</script>\n";

echo "<script>\n";
echo "function clearsave() {\n";
echo "localStorage.removeItem('data');\n";
echo "}</script>\n";

echo "<script>\n";
// Button SHOW HELP ENTRIES displayed on the 'Grammar' page
  // https://masteringjs.io/tutorials/fundamentals/disable-button
echo "function disableButton() {
  document.querySelector('#thisone').addEventListener('submit', function(ev) {
  ev.preventDefault(); });
  document.querySelector('#thisone button').disabled = true;
}";
echo "</script>\n";

echo "<script>\n";  // Not used: delaying the display of an element
echo "$(document).ready(function() {\n
  $('#doucement').hide().delay(3000).fadeIn('slow');
});\n";
echo "</script>\n";

echo "</head>";
echo "<body onload=\"settoggledisplay()\">\n";
?>



