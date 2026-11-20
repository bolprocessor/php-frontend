<?php
// 2026-11-20 Displays "Credits.txt" (root folder) or, with "?file=csound", "csound_resources/Credits.md", as plain text with clickable URLs - by Claude
// Only these two files can be displayed.

if(isset($_GET['file']) AND $_GET['file'] == "csound") {
	$credits_path = dirname(__DIR__).DIRECTORY_SEPARATOR."csound_resources".DIRECTORY_SEPARATOR."Credits.md";
	$credits_title = "Credits for Csound resources";
	}
else {
	$credits_path = dirname(__DIR__).DIRECTORY_SEPARATOR."Credits.txt";
	$credits_title = "Credits";
	}
$credits_text = @file_get_contents($credits_path);

function credits_h($value) {
	return htmlspecialchars((string)$value,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
	}

// Markdown links "[text](url)" and bare URLs become links; the rest is displayed as it is
function credits_linkify($text) {
	$out = '';
	$pattern = "/\[([^\]]*)\]\((https?:\/\/[^\s)]+)\)|(https?:\/\/[^\s<>\"]+)/u";
	$last = 0;
	preg_match_all($pattern,$text,$matches,PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
	foreach($matches as $match) {
		$out .= credits_h(substr($text,$last,$match[0][1] - $last));
		if(isset($match[3]) AND $match[3][0] <> '') {
			$url = $match[3][0];
			$trail = '';
			while($url <> '' AND strpos(".,;:)!?'",substr($url,-1)) !== FALSE) { // Punctuation after a URL is not part of it
				if(substr($url,-1) == ")" AND substr_count($url,"(") >= substr_count($url,")")) break;
				$trail = substr($url,-1).$trail;
				$url = substr($url,0,-1);
				}
			$out .= "<a target=\"_blank\" href=\"".credits_h($url)."\">".credits_h($url)."</a>".credits_h($trail);
			}
		else {
			$label = ($match[1][0] <> '') ? $match[1][0] : $match[2][0];
			$out .= "<a target=\"_blank\" href=\"".credits_h($match[2][0])."\">".credits_h($label)."</a>";
			}
		$last = $match[0][1] + strlen($match[0][0]);
		}
	$out .= credits_h(substr($text,$last));
	return $out;
	}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo credits_h($credits_title); ?></title>
<style>
:root { --bg: #ffffff; --fg: #1a1a1a; --link: #0645ad; }
@media (prefers-color-scheme: dark) { :root { --bg: #1e1e1e; --fg: #e0e0e0; --link: #8ab4f8; } }
body { background: var(--bg); color: var(--fg); margin: 20px; }
pre { font-family: Menlo, Consolas, "DejaVu Sans Mono", monospace; font-size: 14px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; max-width: 60em; }
a { color: var(--link); }
</style>
</head>
<body>
<?php
if($credits_text === FALSE) echo "<p>File not found: ".credits_h($credits_path)."</p>";
else {
	$credits_text = str_replace(chr(13).chr(10),chr(10),$credits_text);
	echo "<pre>".credits_linkify($credits_text)."</pre>";
	}
?>
</body>
</html>
