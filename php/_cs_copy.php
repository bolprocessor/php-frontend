<?php
// 2026-11-19 Copying a Csound instrument from a '-cs' file to another one (button "COPY TO ANOTHER FILE" of csound.php) - by Claude
// The instrument is added to the target '-cs' file, and the code it needs is added to the orchestra of that file:
// its "instr" block, the user-defined opcodes it calls, the global variables it uses (with the header lines that set them),
// macros, and the instruments reading the global audio variables it writes (effects, recognised because they reset them to 0),
// with the header lines that start them ("schedule", "event_i"). Code already present in the target orchestra is not copied again.

function orc_code_part($line,&$in_comment) {
	// Returns the code of an orchestra line without its comments (';', '//' and '/* */'), ignoring these inside strings
	$code = '';
	$n = strlen($line);
	$in_string = FALSE;
	for($i = 0; $i < $n; $i++) {
		$c = $line[$i];
		$next = ($i + 1 < $n) ? $line[$i + 1] : '';
		if($in_comment) {
			if($c == '*' AND $next == '/') {
				$in_comment = FALSE;
				$i++;
				}
			continue;
			}
		if($in_string) {
			$code .= $c;
			if($c == '\\' AND $next <> '') {
				$code .= $next;
				$i++;
				}
			else if($c == '"') $in_string = FALSE;
			continue;
			}
		if($c == '"') {
			$in_string = TRUE;
			$code .= $c;
			continue;
			}
		if($c == ';') break;
		if($c == '/' AND $next == '/') break;
		if($c == '/' AND $next == '*') {
			$in_comment = TRUE;
			$i++;
			continue;
			}
		$code .= $c;
		}
	return rtrim($code);
	}

function orc_parse($text) {
	// Splits an orchestra into units: 'instr', 'opcode', 'define', 'include', and 'header' statements outside instruments
	// (a control block such as "if ... endif" or "while ... od" is one statement). Each unit has its first line ('start'),
	// the first line of the comments just above it ('lead'), its last line ('end'), and the analysis of orc_analyse().
	$lines = explode("\n",str_replace("\r",'',$text));
	$units = array();
	$in_comment = FALSE;
	$lead = -1;
	$unit = NULL;
	$depth = 0;
	foreach($lines as $i => $line) {
		$code = trim(orc_code_part($line,$in_comment));
		if($unit === NULL) {
			if($code == '') {
				if(trim($line) == '') $lead = -1;
				else if($lead < 0) $lead = $i;
				continue;
				}
			$unit = array('type' => "header",'ids' => array(),'start' => $i,'lead' => ($lead >= 0 ? $lead : $i),'end' => $i,'code' => array());
			$lead = -1;
			$depth = 0;
			if(preg_match('/^instr\s+(.+)$/',$code,$m)) {
				$unit['type'] = "instr";
				foreach(explode(',',$m[1]) as $id) $unit['ids'][] = trim($id);
				}
			else if(preg_match('/^opcode\s+(\w+)\s*,/',$code,$m)) {
				$unit['type'] = "opcode";
				$unit['ids'][] = $m[1];
				}
			else if(preg_match('/^#define\s+(\w+)/',$code,$m)) {
				$unit['type'] = "define";
				$unit['ids'][] = $m[1];
				}
			else if(preg_match('/^#include\b/',$code)) $unit['type'] = "include";
			}
		$unit['end'] = $i;
		if($code <> '') $unit['code'][] = $code;
		$close = FALSE;
		switch($unit['type']) {
			case "instr":
				$close = preg_match('/^endin\b/',$code);
				break;
			case "opcode":
				$close = preg_match('/^endop\b/',$code);
				break;
			case "define":
				// The body of a macro is between two '#'
				$body = preg_replace('/^#define\s+\w+(\([^)]*\))?/','',implode("\n",$unit['code']));
				$close = (substr_count($body,'#') >= 2);
				break;
			case "include":
				$close = TRUE;
				break;
			default:
				if(preg_match('/^if\b.*\bthen$/',$code) OR preg_match('/^(while|until)\b.*\bdo$/',$code)) $depth++;
				else if(preg_match('/^(endif|fi|od)\b/',$code)) $depth--;
				$close = ($depth <= 0);
				break;
			}
		if($close) {
			orc_analyse($unit);
			$units[] = $unit;
			$unit = NULL;
			}
		}
	if($unit !== NULL) {
		orc_analyse($unit);
		$units[] = $unit;
		}
	return array('lines' => $lines,'units' => $units);
	}

function orc_analyse(&$unit) {
	// Names used and defined by a unit
	$code = implode("\n",$unit['code']);
	$string_pattern = '/"((?:[^"\\\\]|\\\\.)*)"/';
	preg_match_all($string_pattern,$code,$m);
	$unit['strings'] = array_flip($m[1]);
	$bare = preg_replace($string_pattern,'""',$code);
	preg_match_all('/(?<![\w$.])[A-Za-z_]\w*/',$bare,$m);
	$unit['idents'] = array_flip($m[0]);
	preg_match_all('/\$(\w+)/',$bare,$m);
	$unit['macros'] = array_flip($m[1]);
	$unit['globals'] = $unit['resets'] = $unit['refs'] = array();
	foreach(explode("\n",$bare) as $k => $line) {
		$outputs = '';
		$list = '([A-Za-z_]\w*(?::\w+)?(?:\[[^\]]*\])*(?:\s*,\s*[A-Za-z_]\w*(?::\w+)?(?:\[[^\]]*\])*)*)';
		if(preg_match('/^'.$list.'\s*[-+*\/]?=(?!=)/',$line,$m)) $outputs = $m[1];
		else if(preg_match('/^'.$list.'\s+[A-Za-z_]\w*\b/',$line,$m)) $outputs = $m[1];
		foreach(explode(',',$outputs) as $name) {
			$name = preg_replace('/[:\[].*$/','',trim($name));
			if(preg_match('/^g\w+$/',$name)) $unit['globals'][$name] = TRUE;
			}
		// Global variables reset to 0: the instrument reads a bus
		if(preg_match('/^(g\w+)\s*=\s*0(\.0*)?$/',$line,$m)) $unit['resets'][$m[1]] = TRUE;
		if(preg_match('/^clear\s+(.+)$/',$line,$m)) {
			foreach(explode(',',$m[1]) as $name) $unit['resets'][trim($name)] = TRUE;
			}
		}
	// Instruments started by this unit
	foreach($unit['code'] as $line) {
		$ref = '';
		if(preg_match('/\bschedulek?\s*\(?\s*("[^"]*"|[\w.]+)/',$line,$m)) $ref = $m[1];
		else if(preg_match('/\bevent(?:_i)?\s*\(?\s*"[^"]*"\s*,\s*("[^"]*"|[\w.]+)/',$line,$m)) $ref = $m[1];
		if($ref == '') continue;
		$ref = trim($ref,'"');
		if(is_numeric($ref)) $ref = (string) intval($ref);
		$unit['refs'][$ref] = TRUE;
		}
	// Text without comments and spacing, to find the same code in another orchestra.
	// For instruments, the "instr" line is ignored because the number may be different.
	$norm = array();
	foreach($unit['code'] as $k => $line) {
		if($k == 0 AND $unit['type'] == "instr") continue;
		$norm[] = preg_replace('/\s+/',' ',$line);
		}
	$unit['norm'] = implode("\n",$norm);
	return;
	}

function orc_setting($units,$name) {
	// Value of a header setting such as "0dbfs" or "nchnls", or ''
	foreach($units as $unit) {
		if($unit['type'] <> "header") continue;
		foreach($unit['code'] as $line)
			if(preg_match('/^'.preg_quote($name,'/').'\s*=\s*(.+)$/',$line,$m)) return trim($m[1]);
		}
	return '';
	}

function orc_add_to_instrument_list($lines,$number,$description) {
	// Adds an instrument to the "; Instruments:" block of the comments at the top of an orchestra, if there is one (see orchestra_instruments())
	$in_block = FALSE;
	$last = -1;
	$model = NULL;
	foreach($lines as $i => $line) {
		$trimmed = trim($line);
		if(!$in_block) {
			if(preg_match("/^;\s*Instruments\s*:\s*$/i",$trimmed)) $in_block = TRUE;
			continue;
			}
		if(!str_starts_with($trimmed,";")) break;
		$text = trim(substr($trimmed,1));
		if($text == '') {
			if($last >= 0) break;
			continue;
			}
		if(preg_match('/^(\s*;\s*)([0-9]+)(\s+)/',$line,$m)) {
			$model = $m;
			$last = $i;
			}
		else if($model !== NULL AND preg_match('/^\s*;\s{2,}/',$line)) $last = $i; // Continuation
		else break;
		}
	if($model === NULL) return $lines;
	array_splice($lines,$last + 1,0,array($model[1].$number.$model[3].$description));
	return $lines;
	}

function cs_copy_unique_name($name,$suffix,$taken) {
	$new_name = $name."_".$suffix;
	for($k = 2; isset($taken[$new_name]); $k++) $new_name = $name."_".$suffix."_".$k;
	return $new_name;
	}

function cs_copy_find_instrument($json,$instrument) {
	// Returns array(label of the list of instruments, index in this list), with index -1 if the instrument is not found
	$label = NULL;
	foreach($json as $this_label => $item) {
		if(is_array($item) AND isset($item['key']) AND $item['key'] == "CsInstruments") $label = $this_label;
		}
	if($label === NULL OR !is_array($json[$label]['value'])) return array($label,-1);
	foreach($json[$label]['value'] as $j => $object) {
		$name = str_replace(' ','_',cs_get(cs_values($object),"CsInstrumentName",''));
		if(strcasecmp($name,$instrument) == 0) return array($label,$j);
		}
	return array($label,-1);
	}

function cs_copy_instrument($dir,$source,$instrument,$target) {
	// Copies instrument $instrument of '-cs' file $source (in $dir) to '-cs' file $target, with the code it needs in the orchestra of $target.
	// 2026-11-19 $target and the orchestras are in "my_csound_resources" or "csound_resources" (see csound_resource_dir()). A fixed file that can't be changed is not modified - by Claude
	// Returns an HTML report. Nothing is changed if the copy is not possible.
	global $dir_csound_resources,$temp_dir,$dir_trash_folder,$trash_folder,$csound_path,$csound_name,$permissions;
	$report = "<p><span class=\"red-text\">Copying instrument</span> <span class=\"green-text\">‘".$instrument."’</span> <span class=\"red-text\">to</span> <span class=\"green-text\">‘".$target."’</span></p>";
	$fail = function($message) use($report) {
		return $report."<p><span class=\"red-text\">➡ ".$message."<br />Nothing has been changed.</span></p>";
		};
	if($target == $source) return $fail("Choose another file. To copy an instrument in the same file, use DUPLICATE AS.");
	$tdir = csound_resource_dir($target);
	if(read_only_resource_folder($tdir)) return $fail("‘".$target."’ is a fixed file of the distribution, which can't be changed.");
	// Unsaved changes of the target file in an open page are saved first. Then the temporary folder of this page is deleted, so that it doesn't save the file without the new instrument; the page will ask to be reloaded.
	$target_temp_folder = $temp_dir.str_replace(' ','_',$target)."_".my_session_id()."_temp";
	if(is_dir($target_temp_folder)) {
		if(file_exists($target_temp_folder.SLASH."_changed")) {
			$page = csound_page_values_from_file($tdir.$target);
			if($page !== FALSE) SaveCsoundInstruments(FALSE,$tdir,$target,basename($target_temp_folder),FALSE,$page);
			}
		}
	if(file_exists($tdir.$target."_lock2")) return $fail("‘".$target."’ is being changed. Try again later.");
	// The instrument in the source file
	$source_json = csound_read_resource((string) @file_get_contents($dir.$source));
	if(!is_array($source_json)) return $fail("‘".$source."’ could not be read.");
	list($source_label,$j) = cs_copy_find_instrument($source_json,$instrument);
	if($j < 0) return $fail("Instrument ‘".$instrument."’ was not found in ‘".$source."’. Is it saved?");
	$object = $source_json[$source_label]['value'][$j];
	$values = cs_values($object);
	$instrument = str_replace(' ','_',cs_get($values,"CsInstrumentName",$instrument));
	$source_index = intval(cs_get($values,"CsInstrumentIndex",0));
	// The target file
	$target_content = @file_get_contents($tdir.$target);
	if($target_content === FALSE) return $fail("‘".$target."’ could not be read.");
	if(trim($target_content) == '') $target_content = csound_data_to_json($GLOBALS['top_header'],cs_new_data());
	$target_json = csound_read_resource($target_content);
	if(!is_array($target_json)) return $fail("‘".$target."’ could not be read: ".$target_json);
	list($target_label,$k) = cs_copy_find_instrument($target_json,$instrument);
	if($k >= 0) return $fail("An instrument named ‘".$instrument."’ already exists in ‘".$target."’. Delete or rename it before copying this one.");
	$target_indexes = array();
	if($target_label !== NULL AND is_array($target_json[$target_label]['value'])) {
		foreach($target_json[$target_label]['value'] as $other) $target_indexes[intval(cs_get(cs_values($other),"CsInstrumentIndex",0))] = TRUE;
		}
	$source_orchestra = cs_get(cs_values($source_json),"CsoundOrchestraName",'');
	if($source_orchestra == '') $source_orchestra = "0-default.orc";
	$target_orchestra = cs_get(cs_values($target_json),"CsoundOrchestraName",'');
	if($target_orchestra == '') $target_orchestra = "0-default.orc";
	$notes = array();
	$new_index = $source_index;
	$new_orchestra = NULL;
	if($target_orchestra == $source_orchestra) {
		$notes[] = "Both files use orchestra ‘".$target_orchestra."’, which is not changed.";
		if(isset($target_indexes[$source_index])) $notes[] = "Another instrument of ‘".$target."’ also has index ".$source_index.": both play instrument ".$source_index." of the orchestra.";
		}
	else {
		$source_text = @file_get_contents(csound_resource_path($source_orchestra));
		if($source_text === FALSE) return $fail("The orchestra of ‘".$source."’, ‘".$source_orchestra."’, was not found in ‘csound_resources’.");
		$target_text = @file_get_contents(csound_resource_path($target_orchestra));
		if($target_text === FALSE) return $fail("The orchestra of ‘".$target."’, ‘".$target_orchestra."’, was not found in ‘csound_resources’. Create it, or choose an existing one on the page of ‘".$target."’.");
		$src = orc_parse($source_text);
		$dst = orc_parse($target_text);
		// The instrument in the source orchestra
		$main = -1;
		foreach($src['units'] as $u => $unit)
			if($unit['type'] == "instr" AND in_array((string) $source_index,$unit['ids'],TRUE)) $main = $u;
		if($main < 0) return $fail("Instrument ".$source_index." (the index of ‘".$instrument."’) was not found in orchestra ‘".$source_orchestra."’.");
		foreach($src['units'] as $unit) {
			if($unit['type'] == "include") $notes[] = "‘".$source_orchestra."’ includes other files, which are not copied: ".htmlspecialchars($unit['code'][0]);
			}
		// Names defined in the source orchestra
		$src_globals = $udos = $named = $macros = array();
		foreach($src['units'] as $unit) {
			$src_globals += $unit['globals'];
			foreach($unit['ids'] as $id) {
				if($unit['type'] == "opcode") $udos[$id] = TRUE;
				if($unit['type'] == "define") $macros[$id] = TRUE;
				if($unit['type'] == "instr" AND !is_numeric($id)) $named[$id] = TRUE;
				}
			}
		// Units needed by the instrument, found step by step
		$included = array($main => TRUE);
		do {
			$need_globals = $need_udos = $need_named = $need_macros = $included_ids = array();
			foreach($included as $u => $x) {
				$unit = $src['units'][$u];
				$need_globals += array_intersect_key($unit['idents'],$src_globals);
				$need_udos += array_intersect_key($unit['idents'],$udos);
				$need_named += array_intersect_key($unit['strings'],$named);
				$need_macros += array_intersect_key($unit['macros'],$macros);
				if($unit['type'] == "instr") foreach($unit['ids'] as $id) $included_ids[$id] = TRUE;
				}
			$changed = FALSE;
			foreach($src['units'] as $u => $unit) {
				if(isset($included[$u])) continue;
				$ids = array_flip($unit['ids']);
				switch($unit['type']) {
					case "instr":
						$add = (count(array_intersect_key($ids,$need_named)) > 0 || count(array_intersect_key($unit['resets'],$need_globals)) > 0);
						break;
					case "opcode":
						$add = count(array_intersect_key($ids,$need_udos)) > 0;
						break;
					case "define":
						$add = count(array_intersect_key($ids,$need_macros)) > 0;
						break;
					case "header":
						$add = (count(array_intersect_key($unit['globals'],$need_globals)) > 0 || count(array_intersect_key($unit['refs'],$included_ids)) > 0);
						break;
					default:
						$add = FALSE;
					}
				if($add) {
					$included[$u] = TRUE;
					$changed = TRUE;
					}
				}
			}
		while($changed);
		ksort($included);
		// Names defined in the target orchestra, and code it already contains
		$dst_globals = $dst_names = $dst_numbers = $dst_norms = $dst_resets = array();
		$reuse = NULL;
		foreach($dst['units'] as $unit) {
			$dst_globals += $unit['globals'];
			$dst_resets += $unit['resets'];
			$dst_norms[$unit['type']."\n".$unit['norm']] = TRUE;
			foreach($unit['ids'] as $id) {
				$dst_names[$unit['type']][$id] = $unit['norm'];
				if($unit['type'] == "instr" AND is_numeric($id)) {
					$dst_numbers[intval($id)] = TRUE;
					if($reuse === NULL AND $unit['norm'] == $src['units'][$main]['norm']) $reuse = intval($id);
					}
				}
			}
		// Number of the instrument in the target orchestra
		if($reuse !== NULL) {
			$new_index = $reuse;
			$notes[] = "Orchestra ‘".$target_orchestra."’ already contains this instrument, as instrument ".$reuse.".";
			}
		else if(isset($dst_numbers[$source_index]) OR isset($target_indexes[$source_index])) {
			for($new_index = 1; isset($dst_numbers[$new_index]) OR isset($target_indexes[$new_index]); $new_index++);
			$notes[] = "Number ".$source_index." is already used in ‘".$target_orchestra."’ or ‘".$target."’, so the instrument becomes number ".$new_index.".";
			}
		// Units already in the target orchestra are skipped
		$skipped = array();
		foreach($included as $u => $x) {
			$unit = $src['units'][$u];
			if($u == $main) {
				if($reuse !== NULL) $skipped[$u] = TRUE;
				continue;
				}
			if(isset($dst_norms[$unit['type']."\n".$unit['norm']])) $skipped[$u] = TRUE;
			}
		// An instrument copied only because it reads a bus (an effect) is skipped if an instrument of the target orchestra already reads this bus, declared in the same way: the copied instrument will be sent to the effect of the target orchestra
		$called = array(); // Instruments named by the copied code, except by the header lines that start them
		foreach($included as $u => $x) {
			$unit = $src['units'][$u];
			if($unit['type'] == "header" AND count($unit['refs']) > 0) continue;
			$called += array_intersect_key($unit['strings'],$named);
			}
		foreach($included as $u => $x) {
			$unit = $src['units'][$u];
			if(isset($skipped[$u]) OR $u == $main OR $unit['type'] <> "instr" OR count($unit['resets']) == 0) continue;
			if(count(array_intersect_key(array_flip($unit['ids']),$called)) > 0) continue;
			$shared = TRUE;
			foreach($unit['resets'] as $name => $y) {
				if(!isset($dst_resets[$name])) $shared = FALSE;
				foreach($included as $v => $z) {
					if($src['units'][$v]['type'] == "header" AND isset($src['units'][$v]['globals'][$name]) AND !isset($skipped[$v])) $shared = FALSE;
					}
				}
			if($shared) {
				$skipped[$u] = TRUE;
				$notes[] = "Instrument ‘".implode(", ",$unit['ids'])."’ is not copied, because ‘".$target_orchestra."’ already has an instrument reading ‘".implode("’, ‘",array_keys($unit['resets']))."’.";
				}
			}
		// A unit starting instruments that are all skipped is skipped too: the target orchestra already starts them
		foreach($included as $u => $x) {
			$unit = $src['units'][$u];
			if(isset($skipped[$u]) OR $unit['type'] <> "header" OR count($unit['refs']) == 0) continue;
			$all_skipped = TRUE;
			foreach($unit['refs'] as $ref => $y) {
				$found = FALSE;
				foreach($included as $v => $z) {
					if($src['units'][$v]['type'] == "instr" AND in_array((string) $ref,$src['units'][$v]['ids'],TRUE)) {
						$found = TRUE;
						if(!isset($skipped[$v])) $all_skipped = FALSE;
						}
					}
				if(!$found) $all_skipped = FALSE;
				}
			if($all_skipped) $skipped[$u] = TRUE;
			}
		// Names of the copied code that are defined differently in the target orchestra: they are renamed, unless code that is not copied (already in the target) uses them
		$renames = $conflicts = array();
		$taken = $dst_globals;
		foreach($dst_names as $type => $list) $taken += $list;
		foreach($src['units'] as $unit) $taken += array_flip($unit['ids']) + $unit['globals'];
		$used_by_skipped = function($name) use($included,$skipped,$src) {
			foreach($skipped as $u => $x) {
				$unit = $src['units'][$u];
				if(isset($unit['idents'][$name]) OR isset($unit['strings'][$name]) OR isset($unit['macros'][$name])) return TRUE;
				}
			return FALSE;
			};
		foreach($included as $u => $x) {
			if(isset($skipped[$u])) continue;
			$unit = $src['units'][$u];
			$names = array();
			if($unit['type'] == "header") $names = array_keys(array_intersect_key($unit['globals'],$dst_globals));
			else if($u <> $main) {
				foreach($unit['ids'] as $id) if(isset($dst_names[$unit['type']][$id])) $names[] = $id;
				}
			foreach($names as $name) {
				if(isset($renames[$name])) continue;
				$what = ($unit['type'] == "header") ? "Global variable" : (($unit['type'] == "opcode") ? "Opcode" : (($unit['type'] == "define") ? "Macro" : "Instrument"));
				if($unit['type'] == "define" OR is_numeric($name) OR $used_by_skipped($name)) $conflicts[] = $what." ‘".$name."’ is defined differently in ‘".$target_orchestra."’.";
				else {
					$renames[$name] = cs_copy_unique_name($name,$new_index,$taken);
					$taken[$renames[$name]] = TRUE;
					$notes[] = $what." ‘".$name."’ is defined differently in ‘".$target_orchestra."’, so the copy is named ‘".$renames[$name]."’.";
					}
				}
			}
		// Two instruments reading the same bus: one of them would get nothing
		foreach($included as $u => $x) {
			if(isset($skipped[$u])) continue;
			foreach($src['units'][$u]['resets'] as $name => $y) {
				if(isset($dst_resets[$name]) AND !isset($renames[$name])) $conflicts[] = "Global variable ‘".$name."’ is already read and reset by an instrument of ‘".$target_orchestra."’.";
				}
			}
		if(count($conflicts) > 0) return $fail("The code of this instrument cannot be added to orchestra ‘".$target_orchestra."’:<br />".implode("<br />",array_unique($conflicts)));
		// Notes on the copied code
		$main_unit = $src['units'][$main];
		foreach($main_unit['code'] as $line) {
			if(preg_match('/\b(schedulek?|event(_i)?|subinstr(init)?|turnoff2|nstrnum)\b.*\b[0-9]+\b/',$line)) {
				$notes[] = "Instrument ".$source_index." may refer to other instruments by their number, which are not copied: ".htmlspecialchars($line);
				break;
				}
			}
		$need_globals = array();
		foreach($included as $u => $x) $need_globals += array_intersect_key($src['units'][$u]['idents'],$src_globals);
		foreach($need_globals as $name => $x) {
			$declared = FALSE;
			foreach($included as $u => $y) if(isset($src['units'][$u]['globals'][$name]) AND $src['units'][$u]['type'] <> "instr") $declared = TRUE;
			foreach($src['units'] as $unit) if($unit['type'] == "header" AND isset($unit['globals'][$name])) $declared = TRUE;
			if(!$declared AND !isset($dst_globals[$name])) $notes[] = "Global variable ‘".$name."’ is set by instruments that are not copied.";
			}
		foreach(array("sr","ksmps","nchnls","0dbfs") as $setting) {
			$a = orc_setting($src['units'],$setting);
			$b = orc_setting($dst['units'],$setting);
			if($a <> '' AND $b <> '' AND $a <> $b) $notes[] = "<span class=\"red-text\">‘".$setting."’ is ".$a." in ‘".$source_orchestra."’ but ".$b." in ‘".$target_orchestra."’: the instrument may sound different.</span>";
			}
		// The code to be added
		$added = array();
		$block = array();
		$previous_end = -2;
		foreach($included as $u => $x) {
			if(isset($skipped[$u])) continue;
			$unit = $src['units'][$u];
			$lines = array_slice($src['lines'],$unit['lead'],$unit['end'] - $unit['lead'] + 1);
			if($u == $main) {
				$lines[$unit['start'] - $unit['lead']] = preg_replace('/^(\s*instr\s+)[^;\/]*?(\s*(;.*|\/\/.*)?)$/','${1}'.$new_index.'${2}',$lines[$unit['start'] - $unit['lead']]);
				}
			foreach($lines as $n => $line) {
				foreach($renames as $old => $new) $line = preg_replace('/(?<![\w$])'.preg_quote($old,'/').'(?!\w)/',$new,$line);
				if($u <> $main AND $new_index <> $source_index AND isset($unit['refs'][(string) $source_index]))
					$line = preg_replace('/\b(schedulek?\s*\(?\s*|event(?:_i)?\s*\(?\s*"[^"]*"\s*,\s*)'.$source_index.'\b/','${1}'.$new_index,$line);
				$lines[$n] = $line;
				}
			if($unit['lead'] > $previous_end + 1 AND count($block) > 0) $block[] = '';
			$block = array_merge($block,$lines);
			$previous_end = $unit['end'];
			$label = ($u == $main) ? "instr ".$new_index : (($unit['type'] == "header") ? '' : $unit['type']." ".implode(", ",$unit['ids']));
			if($label <> '') $added[] = $label;
			else $added['header'] = isset($added['header']) ? $added['header'] + 1 : 1;
			}
		if(count($block) > 0) {
			if($target_orchestra <> "0-default.orc" AND read_only_resource_folder(csound_resource_dir($target_orchestra))) return $fail("‘".$target_orchestra."’ is a fixed orchestra of the distribution, which can't be changed. To add instruments, give ‘".$target."’ another orchestra (e.g. a copy of ‘".$target_orchestra."’ under a new name).");
			if($target_orchestra == "0-default.orc") return $fail("‘0-default.orc’ is the default orchestra of the Bol Processor, which is replaced by updates. To add instruments, give ‘".$target."’ another orchestra (e.g. a copy of ‘0-default.orc’ under a new name).");
			$eol = is_integer(strpos($target_text,"\r\n")) ? "\r\n" : "\n";
			$date = gmdate('Y-m-d H:i:s');
			$new_lines = $dst['lines'];
			while(count($new_lines) > 0 AND trim(end($new_lines)) == '') array_pop($new_lines);
			if($reuse === NULL) {
				$list = orchestra_instruments(csound_resource_path($source_orchestra));
				$description = (isset($list[$source_index]) AND $list[$source_index] <> '') ? $list[$source_index] : $instrument;
				$new_lines = orc_add_to_instrument_list($new_lines,$new_index,$description." (copied from ‘".$source_orchestra."’)");
				}
			$new_lines[] = '';
			$new_lines[] = "; ---- Instrument ‘".$instrument."’ copied from ‘".$source."’ (instrument ".$source_index." of ‘".$source_orchestra."’) as instrument ".$new_index.". Date: ".$date." GMT ----";
			$new_lines[] = '';
			$new_lines = array_merge($new_lines,$block);
			$new_lines[] = '';
			$new_lines[] = "; ---- End of the copy of ‘".$instrument."’ ----";
			$new_orchestra = implode($eol,$new_lines).$eol;
			// Syntax check by Csound, if it is installed
			$csound = $csound_path.SLASH.$csound_name;
			if($csound_path <> '' AND file_exists($csound)) {
				$check_file = $temp_dir."_check_".my_session_id().".orc";
				file_put_contents($check_file,$new_orchestra);
				$output = array();
				exec(escapeshellarg($csound)." --syntax-check-only --orc ".escapeshellarg($check_file)." 2>&1",$output,$result);
				@unlink($check_file);
				if($result <> 0) {
					$errors = array();
					foreach($output as $line) if(preg_match('/error|unexpected|undefined|not found|used before/i',$line)) $errors[] = htmlspecialchars($line);
					return $fail("Csound found errors in ‘".$target_orchestra."’ with the copied code:<br /><small>".implode("<br />",array_slice($errors,0,10))."</small>");
					}
				$notes[] = "Csound has checked the syntax of the new orchestra.";
				}
			else $notes[] = "<span class=\"red-text\">Csound was not found, so the syntax of the new orchestra could not be checked.</span>";
			}
		}
	// The instrument is added to the target file
	foreach($object as $label => $item) {
		if(is_array($item) AND isset($item['key']) AND $item['key'] == "CsInstrumentIndex") $object[$label]['value'] = $new_index;
		}
	if($target_label === NULL) {
		$target_label = "Csound instruments";
		$target_json[$target_label] = cs_item("CsInstruments",array());
		}
	if(!is_array($target_json[$target_label]['value'])) $target_json[$target_label]['value'] = array();
	$target_json[$target_label]['value'][] = $object;
	$json = json_encode($target_json,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)."\n";
	if($json === FALSE) return $fail("The new content of ‘".$target."’ could not be encoded.");
	$file_lock = $tdir.$target."_lock";
	$time_end = time() + 3;
	while(file_exists($file_lock) AND time() <= $time_end) sleep(1);
	@unlink($file_lock);
	file_put_contents($file_lock,"lock\n");
	if($new_orchestra !== NULL) {
		$orchestra_path = csound_resource_path($target_orchestra);
		$backup = $target_orchestra."_".gmdate('Ymd-His');
		@copy($orchestra_path,$dir_trash_folder.$backup);
		if(@file_put_contents($orchestra_path,$new_orchestra) === FALSE) {
			unlink($file_lock);
			return $fail("‘".$target_orchestra."’ could not be written.");
			}
		@chmod($orchestra_path,$permissions);
		}
	$temp_file = $tdir.".copying".$target; // Replaces the file in a single step
	if(@file_put_contents($temp_file,$json) === FALSE OR !@rename($temp_file,$tdir.$target)) {
		@unlink($temp_file);
		if($new_orchestra !== NULL) @file_put_contents($orchestra_path,$target_text);
		unlink($file_lock);
		return $fail("‘".$target."’ could not be written.");
		}
	@chmod($tdir.$target,$permissions);
	unlink($file_lock);
	if(is_dir($target_temp_folder)) my_rmdir($target_temp_folder);
	// Report
	$report .= "<ul>";
	$report .= "<li>Instrument <span class=\"green-text\">‘".$instrument."’</span> has been added to <span class=\"green-text\">‘".$target."’</span> with index <b>".$new_index."</b>.</li>";
	if($new_orchestra !== NULL) {
		$list = array();
		foreach($added as $key => $label) {
			if($key === 'header') $list[] = $label." line(s) of global settings";
			else $list[] = $label;
			}
		$report .= "<li>Added to orchestra <span class=\"green-text\">‘".$target_orchestra."’</span>: ".implode(", ",$list).". The former version is in the trash as <span class=\"green-text\">‘".$trash_folder.SLASH.$backup."’</span>.</li>";
		$users = array();
		foreach(list_of_csound_resources() as $thisfile) {
			if($thisfile == $target) continue;
			$other = csound_read_resource((string) @file_get_contents(csound_resource_path($thisfile)));
			if(is_array($other) AND cs_get(cs_values($other),"CsoundOrchestraName",'') == $target_orchestra) $users[] = $thisfile;
			}
		if(count($users) > 0) $report .= "<li>This orchestra is also used by: ".implode(", ",$users)."</li>";
		}
	foreach($notes as $note) $report .= "<li>".$note."</li>";
	$report .= "</ul>";
	$report .= "<p><span class=\"red-text\">➡</span> <a target=\"_blank\" href=\"csound.php?file=".urlencode(csound_resource_folder($target).SLASH.$target)."\">Edit ‘".$target."’</a> (reload its page if it is open)</p>";
	return $report;
	}
?>
