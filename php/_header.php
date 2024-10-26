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

// echo "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan').fadeOut('fast');
	}, 3000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan2').fadeOut('fast');
	}, 3000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan3').fadeOut('fast');
	}, 3000);\n";
echo "</script>\n";

echo "<script>\n";
echo "setTimeout(function() {
    $('#timespan4').fadeOut('fast');
	}, 3000);\n";
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
echo "function checksaved() {\n";
echo "if(localStorage.getItem('data') == 'dirty') {\n";
echo "alert('This project needs to be saved');\n";
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
  document.querySelector('#thisone').addEventListener('submit',function(ev) {
  ev.preventDefault(); });
  document.querySelector('#thisone button').disabled = true;
}";
echo "</script>\n";

echo "<script>\n";  // Not used: delaying the display of an element
echo "$(document).ready(function() {\n
  $('#doucement').hide().delay(3000).fadeIn('slow');
});\n";
echo "</script>\n";

/* echo "<script>";
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
</script>"; */

/*
echo "<script>";
echo "function clearFields(inputId, nameId, commentId) {
    document.getElementsByName(inputId)[0].value = \"\";
    document.getElementsByName(nameId)[0].value = \"\";
    document.getElementsByName(commentId)[0].value = \"\";\n";
echo "}
</script>";
*/

echo "<script src=\"darkmode.js\"></script>"; 

echo "</head>";
echo "<body>\n";
?>
