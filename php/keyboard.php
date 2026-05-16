<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if($file == '') die();
$url_this_page = "keyboard.php?file=".urlencode($file);
save_settings("last_page",$url_this_page);
$table = explode(SLASH,$file);
$filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($filename,'',$this_file);
$current_directory = str_replace(SLASH.$filename,'',$file);
save_settings("last_directory",$current_directory);

require_once("_header.php");
display_darklight();

$url = "index.php?path=".urlencode($current_directory);

echo "<h2>Keyboard file “".$filename."”</h2>";
save_settings("last_name",$filename); 
$add_space = FALSE;

if(isset($_POST["copy_from_file"])AND isset($_POST["kbfile"])AND is_file($_POST["kbfile"])){
    if($_POST["kbfile"] <> $this_file."_bak") copy($this_file, $this_file."_bak");
	echo "Copying from file ".$_POST["kbfile"]."<br />";
	$content = file_get_contents($_POST["kbfile"]);
	$extract_data = extract_data(FALSE,TRUE,$content);
	$content = $extract_data['content'];
    $_POST['mapping'] = jsonToMappingArray($content);
	$_POST['savethisfile'] = TRUE;
	}

if(isset($_POST['savethisfile']) AND isset($_POST['mapping'])) {
	echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saved “".$this_file."” file…</span>";
    if(isset($_POST['add_space'])) $add_space = TRUE;
    $mapping = $_POST['mapping'];
    foreach($mapping AS $key => $thisword) {
        $thisword = trim($thisword);
        if($add_space AND ($thisword <> '')) $mapping[$key] = $thisword." ";
        else $mapping[$key] = $thisword;
        }
	$content = mappingFormToJson($mapping);
	$handle = fopen($this_file,"w");
	$file_header = $top_header."\n// Keyboard file saved as \"".$filename."\". Date: ".gmdate('Y-m-d H:i:s');
	fwrite($handle,$file_header."\n");
	fwrite($handle,$content);
	fclose($handle);
	}

try_create_new_file($this_file,$filename);
$content = @file_get_contents($this_file);
if($content === FALSE) ask_create_new_file($url_this_page,$filename);
$extract_data = extract_data(FALSE,TRUE,$content);
echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
echo "<p style=\"width:500px;\">This is a shorthand mapping of keys to words used in grammars and data on the Bol Processor. ";
echo "You can activate and deactivate the mapping by pressing the 'escape' button. Unmapped keys will remain ‘silent’.</p>";
$content = $extract_data['content'];
$mapping = jsonToMappingArray($content);
foreach($mapping AS $key => $thisword) {
    if(str_ends_with($thisword," ")) $add_space = TRUE;
    if($add_space) {
        $thisword = trim($thisword);
        if($thisword <> '') $mapping[$key] = $thisword." ";
        }
    }
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p><input type=\"checkbox\" name=\"add_space\" style=\"vertical-align:middle;\"";
if($add_space) echo " checked";
echo ">&nbsp;Add a space after each word</p>";
echo "<table style=\"border-collapse:collapse;\">";
for($i = 0; $i < 13; $i++) {
	$letter1 = chr(ord('A') + $i);
	$letter2 = chr(ord('A') + $i + 13);
	$value1 = htmlspecialchars($mapping[$letter1] ?? '',ENT_QUOTES);
	$value2 = htmlspecialchars($mapping[$letter2] ?? '',ENT_QUOTES);
	echo "<tr>";
	echo "<td style=\"padding:4px 6px; text-align:right; font-weight:bold;\">".$letter1."&nbsp;&nbsp;➡</td>";
	echo "<td style=\"padding:4px 18px 4px 0;\"><input class=\"edit\" type=\"text\" name=\"mapping[".$letter1."]\" value=\"".$value1."\" style=\"width:180px;\"></td>";
	echo "<td style=\"padding:4px 6px; text-align:right; font-weight:bold;\">".$letter2."&nbsp;&nbsp;➡</td>";
	echo "<td style=\"padding:4px 0;\"><input class=\"edit\" type=\"text\" name=\"mapping[".$letter2."]\" value=\"".$value2."\" style=\"width:180px;\"></td>";
	echo "</tr>";
	}
echo "</table>";
echo "<p style=\"text-align:right;\"><input class=\"save big\" type=\"submit\" name=\"savethisfile\" value=\"SAVE ‘".$filename."’\"></p>";

$all_files = array_merge(
    glob($dir . "*-kb.*"),
    glob($dir . "*.bpkb")
    );
echo "<input class=\"save\" type=\"submit\" name=\"copy_from_file\" value=\"COPY data from this file:\">&nbsp;";
echo "<select name=\"kbfile\">&nbsp;to <span class=\"green-text\">‘".$filename."’</span>";
foreach($all_files as $some_file) {
	$some_name = basename($some_file);
    if($some_name == $filename) continue;
	echo "<option value=\"".htmlspecialchars($some_file,ENT_QUOTES)."\">".htmlspecialchars($some_name,ENT_QUOTES)."</option>";
	}
echo "</select>&nbsp;to <span class=\"green-text\">‘".$filename."’</span>";

echo "</form>";

function mappingFormToJson(array $form): string {
    $map = [];
    for($i = 0; $i < 26; $i++) {
        $key = chr(ord('A') + $i);
        $map[$key] = $form[$key] ?? '';
        }
    return json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

function jsonToMappingArray(string $json): array {
    $map = json_decode($json, true);
    if (!is_array($map)) {
        $map = [];
        }
    $output = [];
    for($i = 0; $i < 26; $i++) {
        $key = chr(ord('A') + $i);
        $output[$key] = isset($map[$key]) ? $map[$key] : '';
        }
    return $output;
    }

/* function mappingListToJson(string $text): string {
    $map = [];
    foreach (preg_split('/\R/', $text) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '//')) {
            continue;
        	}
        if (preg_match('/^(.+?)\s*=\s*(.*?)\s*$/', $line, $m)) {
            $key = trim($m[1]);
            $value = trim($m[2]);
            $map[$key] = $value;
			}
		}
    return json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
	}

function jsonToMappingList(string $json): string {
    $map = json_decode($json, true);
    if (!is_array($map)) {
        return '';
    	}
    $lines = [];
    foreach ($map as $key => $value) {
        $lines[] = $key . ' = ' . $value;
    	}
    return implode("\n", $lines);
	} */
?>
