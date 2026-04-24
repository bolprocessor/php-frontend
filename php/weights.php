<?php
require_once("_basic_tasks.php");

if(isset($_GET['file'])) $file = urldecode($_GET['file']);
else $file = '';
if(isset($_GET['grammar_file'])) $grammar_file = urldecode($_GET['grammar_file']);
else if(isset($_POST['grammar_file'])) $grammar_file = $_POST['grammar_file'];
else $grammar_file = '';
if(isset($_GET['grammarWindow'])) $grammarWindow = urldecode($_GET['grammarWindow']);
else $grammarWindow = '';
$url_this_page = "weights.php?file=".urlencode($file);
$table = explode(SLASH,$file);
$current_filename = end($table);
$this_file = $bp_application_path.$file;
$dir = str_replace($current_filename,'',$this_file);
$current_directory = str_replace(SLASH.$current_filename,'',$file);

require_once("_header.php");
// display_darklight();

/*
echo '<head>';
echo '    <meta charset="UTF-8">';
echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '    <title>Weights</title>';
echo '    <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
            }
            button {
                padding: 8px 14px;
                margin-right: 10px;
                cursor: pointer;
            }
            textarea {
                width: 100%;
                height: 300px;
                margin-top: 15px;
                font-family: monospace;
                font-size: 14px;
            }
        </style>';
echo '</head>';
echo "<body>"; */
echo "<p>";
$url = "index.php?path=".urlencode($current_directory);
// echo "&nbsp;Workspace = <input title=\"List this workspace\" class=\"edit\" name=\"workspace\" type=\"submit\" onmouseover=\"checksaved();\" onclick=\"if(checksaved()) window.open('".$url."','_self');\" value=\"".$current_directory."\"></p>";

// echo link_to_help();

echo "<h2>Weights “".$current_filename."”</h2>";

$grammar_page_url = "grammar.php?file=".urlencode($current_directory.SLASH.$grammar_file);

$temp_weights_file = $temp_dir."trace_".my_session_id()."_".$grammar_file."_weights.json";

reformat_grammar(FALSE,$dir.$grammar_file);

if(isset($_POST['grammarWindow'])) $grammarWindow = $_POST['grammarWindow'];

$file_header = $top_header."\n// Weights file saved from ‘".$grammar_file."’ as ‘".$current_filename."’. Date: ".gmdate('Y-m-d H:i:s');

if(isset($_POST['savethisfile'])) {
	$content = file_get_contents($temp_weights_file);
	if(trim($content) <> '') {
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saved “".$this_file."” file…</span>";
		$handle = @fopen($this_file,"w");
		if($handle) {
			fwrite($handle,$file_header."\n");
			fwrite($handle,$content);
			fclose($handle);
			@chmod($this_file,$permissions);
			}
		else echo "<p>➡ This file cannot be modified because it is write-protected</p>";
		$file_path = $temp_dir.$tracelive_folder.SLASH."_saved_weights";
		file_put_contents($file_path,$this_file);
		@chmod($file_path,$permissions);
		}
	}

if(isset($_POST['resetthisfile_127']) OR isset($_POST['resetthisfile_0'])) {
	echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Resetted “".$this_file."” file…</span>";
	$json = $_POST['apply_these_weights'];
	$weight_table = json_decode($json,true);
	if(isset($_POST['resetthisfile_127'])) $new_weight = 127;
	else $new_weight = 0;
	$handle = @fopen($this_file,"w");
	if($handle) {
		fwrite($handle,$file_header."\n");
		foreach($weight_table AS $row) {
			$this_data['igram'] = $row['igram'];
			$this_data['irul'] = $row['irul'];
			if(is_numeric($row['weight'])) $this_data['weight'] = $new_weight;
			else $this_data['weight'] = $row['weight'];
			$json = json_encode($this_data,JSON_UNESCAPED_SLASHES);
			fwrite($handle,$json."\n");
			}
		fclose($handle);
		@chmod($this_file,$permissions);
		}
	else echo "<p>➡ This file cannot be modified because it is write-protected</p>";
	$file_path = $temp_dir.$tracelive_folder.SLASH."_saved_weights";
	file_put_contents($file_path,$this_file);
	@chmod($file_path,$permissions);
	}

if(isset($_POST["copy_from_file"])AND isset($_POST["wgfile"])AND is_file($_POST["wgfile"])){
	echo "Copying from file ".$_POST["wgfile"]."<br />";
	$content = file_get_contents($_POST["wgfile"]);
	if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
	$extract_data = extract_data(FALSE,TRUE,$content);
	echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
	$content = $extract_data['content'];
	$json = '['.preg_replace('/}\s*{/', '},{', trim($content)).']';
	$_POST['save_these_weights'] = TRUE;
	$_POST['new_name'] = $current_filename;
	}
else {
	if(file_exists($this_file)) {
		$content = @file_get_contents($this_file);
		if($content === FALSE) die();
		if(MB_CONVERT_OK) $content = mb_convert_encoding($content,'UTF-8','UTF-8');
		$extract_data = extract_data(FALSE,TRUE,$content);
		echo "<p class=\"green-text\">".$extract_data['headers']."</p>";
		$content = $extract_data['content'];
		$json = '['.preg_replace('/}\s*{/', '},{', trim($content)).']';
		}
	else $content = '';
	}

if($content <> '' AND isset($_POST['save_these_weights'])) {
	$new_name = trim($_POST['new_name']);
	$new_name = str_replace("-wg.",'',$new_name);
	$new_name = good_name("wg",$new_name,"prefix");
	if($new_name <> '') {
		echo "<span id=\"timespan\" style=\"color:red; float:right; background-color:white; padding:6px; border-radius:6px;\">&nbsp;Saving to “".$new_name."” file…</span>";
		$file_path = $dir.$new_name;
		$new_content = $file_header."\n";
		$new_content .= $content;
		file_put_contents($file_path,$new_content);
		@chmod($file_path,$permissions);
		}
	}

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<p style=\"text-align:left;\"><input class=\"save\" type=\"submit\" name=\"savethisfile\" value=\"COPY current rule weights\"> from grammar <span class=\"green-text\">‘".$grammar_file."’</span> to <span class=\"green-text\">‘".$current_filename."’</span></p>";
echo "<input type=\"hidden\" name=\"grammar_file\" value=\"".$grammar_file."\">";
if($grammarWindow <> '') echo "<input type=\"hidden\" name=\"grammarWindow\" value=\"".$grammarWindow."\">";

$all_files = glob($dir."*-wg.*");
echo "<input class=\"save\" type=\"submit\" name=\"copy_from_file\" value=\"COPY weights from this file:\">&nbsp;";
echo "<select name=\"wgfile\">&nbsp;to <span class=\"green-text\">‘".$current_filename."’</span>";
foreach($all_files as $some_file) {
	$some_name = basename($some_file);
	echo "<option value=\"".htmlspecialchars($some_file,ENT_QUOTES)."\">".htmlspecialchars($some_name,ENT_QUOTES)."</option>";
	}
echo "</select>&nbsp;to <span class=\"green-text\">‘".$current_filename."’</span>";

if($content <> '') {
	echo "<input type=\"hidden\" name=\"apply_these_weights\" value=\"".htmlspecialchars($json, ENT_QUOTES,'UTF-8')."\">";
	echo "<p><input class=\"save\" type=\"submit\" name=\"save_these_weights\" value=\"SAVE a copy to:\">&nbsp;<span class=\"green-text\">-wg.</span>&nbsp;<input type=\"text\" name=\"new_name\" size=\"30\" value=\"enter_a_name\"></p>";
	echo "<p><input class=\"save\" type=\"submit\" name=\"resetthisfile_127\" value=\"SET rule weights to 127\"> in <span class=\"green-text\">‘".$current_filename."’</span> (except variable ones)</p>";
	echo "<p><input class=\"save\" type=\"submit\" name=\"resetthisfile_0\" value=\"RESET rule weights to 0\"> in <span class=\"green-text\">‘".$current_filename."’</span> (except variable ones)</p>";
	}
echo "</form>";

if($content <> '') {
	$data = json_decode($json,true);
	if($data === null) {
		echo "JSON error: " . json_last_error_msg();
		echo "<pre>$json</pre>";
		} 
	else {
		$old_igram = 1;
		foreach ($data as $row) {
			$weight = htmlspecialchars($row['weight'], ENT_QUOTES, 'UTF-8');
			$igram = $row['igram'];
			if($igram > $old_igram) {
				echo "-----<br />";
				$old_igram = $igram;
				}
			echo "gram#{$row['igram']}[{$row['irul']}] &lt;{$weight}&gt;<br>";
			}
		}
	// echo $grammar_page_url."<br />";
	// echo $grammarWindow."<br />";
		
	echo "<br /><form id=\"return_to_grammar\" method=\"post\" action=\"".$grammar_page_url."#topedit\" onsubmit=\"return sendBackToGrammar();\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"apply_these_weights\" value=\"".htmlspecialchars($json, ENT_QUOTES,'UTF-8')."\">";
	if($grammarWindow <> '') echo "<input type=\"hidden\" name=\"grammarWindow\" value=\"".$grammarWindow."\">";
	echo "<input class=\"save\" type=\"submit\" value=\"COPY BACK rule weights\"> in <span class=\"green-text\">‘".$current_filename."’</span> (shown above) to <span class=\"green-text\">‘".$grammar_file."’</span> grammar";
	echo "<br />👉 then, save the grammar if these weights are correct…";
	echo "</form>";
	}
// We need the following function because "target" is not properly handled by some browwsers
// The target is the grammar window from which these weights originated.

echo "<script>
function sendBackToGrammar() {
    var targetName = ".json_encode($grammarWindow).";
    if (!targetName) {
        alert('No grammar window name found');
        return false;
    }
    var w = window.open('', targetName);
    if (!w) {
        alert('Could not find grammar window: ' + targetName);
        return false;
    }
    var form = document.getElementById('return_to_grammar');
    form.target = targetName;

	// Bring the related grammar window to front
    try { w.focus(); } catch(e) {}

    // Close this window shortly after submit
    setTimeout(function() {
    try {
        window.close();
    } catch(e) {
        window.open('', '_self');
        window.close();
		}
	}, 600);

    return true;
}
</script>";
?>