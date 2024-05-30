<?php
echo "<!DOCTYPE HTML>";
echo "<html lang=\"en\">";
echo "<head>";
echo "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\" />";
echo "<link rel=\"stylesheet\" href=\"bp.css\" />\n";
if(isset($filename)) {
  echo "<title>".$filename."</title>\n";

  }
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

if(!is_connected()) echo "<p style=\"color:red;\">➡ Cannot find “midijs.net”… Are you connected to Internet?</p>";

echo "<script type='text/javascript' src='https://www.midijs.net/lib/midi.js'></script>";
echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan').fadeOut('fast');
	}, 3000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan2').fadeOut('fast');
	}, 4000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan3').fadeOut('fast');
	}, 4000);\n";
echo "</script>\n";

echo "<script>\n";
// Capture Command S and call the save() function defined at the bottom of data.php
// This doesn't work yet. It should be implemented to save grammars, alphabets, etc. as well.
echo "document.addEventListener('keydown', function(event) {
  // Check if Command (metaKey) and S are pressed
    var key = String.fromCharCode(event.keyCode);
    if (key.toLowerCase() === \"s\" && event.metaKey) {
      alert(\"Soon this key will be programmed to save data...\");
        event.preventDefault(true);  // Prevent the default action to avoid triggering browser's save dialog
        save();  // Call the save function
      }
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
		var y = document.getElementById(\"hideshow\");
		var z = document.getElementById(\"showhide2\");
	    if(x) {
	      x.className='hidden'; }
      if(y) {
        y.className='unhidden'; }
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function toggledisplay() {
	    var x = document.getElementById(\"showhide\");
      var y = document.getElementById(\"hideshow\");
	    var z = document.getElementById(\"showhide2\");
	    if(x) {
	      x.className=(x.className=='hidden')?'unhidden':'hidden'; }
      if(y) {
        y.className=(y.className=='hidden')?'unhidden':'hidden'; }
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
	  }\n";
echo "function settogglesearch() {
      var z = document.getElementById(\"search\");
      if(z) {
        z.className='hidden'; }
      }\n";
echo "function togglesearch() {
      var z = document.getElementById(\"search\");
      if(z) {
        z.className=(z.className=='hidden')?'unhidden':'hidden'; }
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
/* echo "var x = document.getElementById(\"hideifnotsaved\");\n
	    if(x) {
	      x.className='hidden'; }\n"; */
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

echo "<script>";
echo "function createFile(pathToFile) {
    $.ajax({
        url: '_createfile.php',
        data: { path_to_file: pathToFile },
        success: function(response) {
            document.getElementById('message').innerHTML = response;
        },
        error: function() {
            document.getElementById('message').innerHTML = \"Error creating the file.\";
        }
    });\n";
echo "}
</script>";

echo "</head>";
echo "<body onload=\"settoggledisplay(); settogglesearch();\">\n";

function is_connected() {
  $connected = @fsockopen("www.midijs.net",80);
  if($connected) {
    fclose($connected);
    return TRUE;
    }
  else return FALSE;
  }

if(isset($filename)  AND $filename <> "Compilation" AND $filename <> "Produce") echo "<div style=\"float:right; \"><button type=\"button\" class=\"bouton\" onclick=\"createFile('".$panicfile."');\">PANIC!</button></div>\n";
?>
