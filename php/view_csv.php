<?php
/* view_csv.php */

if(isset($_GET['file'])) $file_path = urldecode($_GET['file']);
else exit('CSV file not found.');

$handle = @fopen($file_path, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit('Cannot open CSV file.');
    }

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Event list</title>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; }
th, td { border: 1px solid #aaa; padding: 4px 8px; text-align: left; }
th { background: #e8e8e8; }
</style>
</head>
<body>
<table>
<?php
$row_number = 0;

while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
    if ($row === [null]) continue;  // ignore an empty line
    echo "<tr>\n";
    foreach ($row as $cell) {
        $tag = ($row_number === 0) ? 'th' : 'td';
        echo '<' . $tag . '>' . h($cell) . '</' . $tag . ">\n";
        }
    echo "</tr>\n";
    $row_number++;
    }
fclose($handle);
?>
</table>
</body>
</html>