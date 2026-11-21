<?php
// Real-time SuperCollider (2026-11-20)
// The console, with option "--scrt port", sends each note as an OSC message "/s_new bp<n> ..." to a SuperCollider server (scsynth)
// listening to this UDP port (see CsoundRealTime.c). These functions find SuperCollider, start, check and stop the server,
// and load the SynthDefs. They are used by produce.php, the project pages, scrt_status.php and scrt_quit.php.
// - If a server is already listening to SC_RT_PORT (57110, the port of the server booted by the SuperCollider IDE), it is used.
//   Otherwise the Bol Processor starts one: scsynth -u 57110 -i 0 -o 2 -R 0
//   Only a server started by the Bol Processor is quit by the QUIT button, or after SC_RT_IDLE seconds without a performance.
// - SynthDefs are written in files "*.scd" of "supercollider_resources" (fixed, e.g. "0-default.scd") and "supercollider_resources_private"
//   (the user's). sclang compiles each folder into its subfolder "synthdefs" when a file has changed (scrt_compile()).
//   They are loaded into the server (/d_loadDir) when they have changed or the server has been restarted (scrt_load_synthdefs()):
//   private ones after fixed ones, so that a private SynthDef replaces a fixed one of the same name.

if(!defined("SC_RT_PORT")) {
	define("SC_RT_PORT",57110);
	define("SC_RT_IDLE",900); // seconds
	define("SC_RT_TOKEN_BUS",16383); // control bus holding a token, to know whether the server has been restarted since the SynthDefs were loaded
	define("SC_RT_COMPILE_TIMEOUT",60); // seconds
	}
if(!defined("SC_DEFAULT_RESOURCE")) define("SC_DEFAULT_RESOURCE","-sc.0-default"); // Instruments used when a project names no '-sc' file
if(!defined("SC_RT_SAMPLE_TABLES")) {
	define("SC_RT_SAMPLE_TABLES",1024); // buffer of the table of sample set n: SC_RT_SAMPLE_TABLES + n (n = 0 to 99)
	define("SC_RT_SAMPLE_BUFFERS",1124); // first buffer of the recordings
	define("SC_RT_BUFFERS",16384); // number of buffers of a server started by the Bol Processor (option -b)
	}

function scrt_programs() {
	// Paths of scsynth and sclang, or FALSE if SuperCollider is not found. sclang may be missing ('') if the SynthDefs are compiled.
	static $programs = NULL;
	if($programs !== NULL) return $programs;
	$candidates = array();
	if(stripos(PHP_OS,"WIN") === 0) {
		foreach(array_merge(glob("C:/Program Files/SuperCollider*",GLOB_ONLYDIR) ?: array(),glob("C:/Program Files (x86)/SuperCollider*",GLOB_ONLYDIR) ?: array()) as $dir)
			$candidates[] = array($dir."/scsynth.exe",$dir."/sclang.exe");
		}
	else if(stripos(PHP_OS,"Linux") === 0) {
		foreach(array("/usr/local/bin","/usr/bin") as $dir) $candidates[] = array($dir."/scsynth",$dir."/sclang");
		}
	else {
		$home = getenv("HOME");
		foreach(array("/Applications",($home ? $home."/Applications" : '')) as $dir) {
			if($dir == '') continue;
			$candidates[] = array($dir."/SuperCollider.app/Contents/Resources/scsynth",$dir."/SuperCollider.app/Contents/MacOS/sclang");
			$candidates[] = array($dir."/SuperCollider/SuperCollider.app/Contents/Resources/scsynth",$dir."/SuperCollider/SuperCollider.app/Contents/MacOS/sclang");
			}
		}
	$programs = FALSE;
	foreach($candidates as $pair) {
		if(!is_file($pair[0])) continue;
		$programs = array('scsynth' => $pair[0],'sclang' => is_file($pair[1]) ? $pair[1] : '');
		break;
		}
	return $programs;
	}

function scrt_ready() {
	// TRUE if real-time SuperCollider can be offered: scsynth is installed, or a server is listening (e.g. SuperCollider on another installation)
	return (scrt_programs() !== FALSE OR scrt_ping(0.2));
	}

function scrt_dirs() {
	// Folders of SynthDef sources: fixed, then private. Paths are absolute, since they are sent to the server (/d_loadDir) and to sclang.
	$root = dirname(__DIR__).DIRECTORY_SEPARATOR;
	$dirs = array('fixed' => $root."supercollider_resources".DIRECTORY_SEPARATOR,'private' => $root."supercollider_resources_private".DIRECTORY_SEPARATOR);
	foreach($dirs as $dir) if(!is_dir($dir)) @mkdir($dir,0775);
	return $dirs;
	}

function scrt_files() {
	// Files of the server in the temp_bolprocessor folder
	$dir = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor");
	if($dir === FALSE) $dir = dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor";
	$dir .= DIRECTORY_SEPARATOR;
	return array('state' => $dir."scrt_server.json",'log' => $dir."scrt_log.txt",'compile' => $dir."scrt_compile.scd",'home' => $dir."scrt_home");
	}

// ------------ OSC ------------

function osc_string($s) {
	// A string is followed by 1 to 4 null bytes, so that its length is a multiple of 4
	$s .= "\0";
	return $s.str_repeat("\0",(4 - strlen($s) % 4) % 4);
	}

function osc_message($address,$args = array()) {
	// $args: PHP integers are sent as 'i', floats as 'f', strings as 's'
	$tags = ",";
	$data = '';
	foreach($args as $arg) {
		if(is_int($arg)) {
			$tags .= "i";
			$data .= pack("N",$arg & 0xFFFFFFFF);
			}
		else if(is_float($arg)) {
			$tags .= "f";
			$data .= strrev(pack("g",$arg)); // big-endian float
			}
		else {
			$tags .= "s";
			$data .= osc_string((string)$arg);
			}
		}
	return osc_string($address).osc_string($tags).$data;
	}

function osc_bundle($messages,$delay = -1.) {
	// A bundle executed after $delay seconds, or at once if $delay < 0
	if($delay < 0.) $tag = pack("NN",0,1);
	else {
		$t = microtime(TRUE) + $delay + 2208988800.; // NTP time: seconds since 1900
		$seconds = floor($t);
		$tag = pack("NN",(int)$seconds,(int)(($t - $seconds) * 4294967296.));
		}
	$bundle = osc_string("#bundle").$tag;
	foreach($messages as $message) $bundle .= pack("N",strlen($message)).$message;
	return $bundle;
	}

function osc_decode($data) {
	// Address and arguments of a message received, or FALSE
	$read_string = function($data,&$i) {
		$end = strpos($data,"\0",$i);
		if($end === FALSE) return FALSE;
		$s = substr($data,$i,$end - $i);
		$i = ($end + 4) & ~3;
		return $s;
		};
	$i = 0;
	$address = $read_string($data,$i);
	if($address === FALSE OR $address == '' OR $address[0] <> '/') return FALSE;
	$args = array();
	$tags = $read_string($data,$i);
	if($tags === FALSE) return array('address' => $address,'args' => $args);
	for($k = 1; $k < strlen($tags); $k++) {
		switch($tags[$k]) {
			case 'i':
				$v = unpack("N",substr($data,$i,4));
				$v = $v[1];
				if($v >= 0x80000000) $v -= 0x100000000;
				$args[] = $v;
				$i += 4;
				break;
			case 'f':
				$v = unpack("g",strrev(substr($data,$i,4)));
				$args[] = $v[1];
				$i += 4;
				break;
			case 's':
				$args[] = $read_string($data,$i);
				break;
			default:
				return array('address' => $address,'args' => $args);
			}
		}
	return array('address' => $address,'args' => $args);
	}

function scrt_exchange($packets,$expect = '',$timeout = 1.,$port = SC_RT_PORT) {
	// Send packets to the server. If $expect is a reply address (e.g. "/status.reply"), wait for it at most $timeout seconds.
	// Return the replies received (decoded), FALSE if nothing could be sent, or an empty array if the expected reply did not come.
	$socket = @stream_socket_client("udp://127.0.0.1:".$port,$errno,$errstr);
	if($socket === FALSE) return FALSE;
	foreach($packets as $packet) {
		if(@fwrite($socket,$packet) === FALSE) {
			fclose($socket);
			return FALSE;
			}
		}
	$replies = array();
	if($expect <> '') {
		stream_set_blocking($socket,FALSE);
		$end = microtime(TRUE) + $timeout;
		$found = FALSE;
		while(!$found AND ($left = $end - microtime(TRUE)) > 0) {
			$read = array($socket);
			$write = $except = NULL;
			if(@stream_select($read,$write,$except,0,(int)($left * 1000000)) < 1) break;
			$data = @fread($socket,65536);
			if($data === FALSE OR $data === '') {
				usleep(20000); // "connection refused" when nothing listens
				continue;
				}
			$reply = osc_decode($data);
			if($reply === FALSE) continue;
			$replies[] = $reply;
			if($reply['address'] == $expect) $found = TRUE;
			}
		if(!$found) $replies = array();
		}
	fclose($socket);
	return $replies;
	}

function scrt_ping($timeout = 1.,$port = SC_RT_PORT) {
	// TRUE if a SuperCollider server answers /status
	$replies = scrt_exchange(array(osc_message("/status")),"/status.reply",$timeout,$port);
	return (is_array($replies) AND count($replies) > 0);
	}

function check_supercollider() {
	// 2026-11-21 Box displayed next to that of Csound (check_csound()) on project pages and resource lists: SuperCollider is installed and responding,
	// a server is running, link to the SuperCollider resource folder. If SuperCollider is not found, a message is displayed only when it is needed.
	global $path, $supercollider_resources, $file_format;
	$installed = (scrt_programs() !== FALSE);
	$server = scrt_ping(0.05);
	$sc_folder = (isset($path) AND isset($supercollider_resources) AND $path == $supercollider_resources);
	$logo = "<img src=\"pict/logo_supercollider.png\" width=\"24px;\" style=\"vertical-align:middle;\" />&nbsp;";
	if(!$installed AND !$server) {
		if($sc_folder OR (isset($file_format) AND $file_format == "scrt"))
			echo "<p>".$logo."<b>SuperCollider</b> is not installed<br /><span class=\"red-text\">➡</span>&nbsp;<a target=\"_blank\" class=\"linkdotted\" href=\"https://supercollider.github.io/downloads\">Follow this link</a> to install it</p>";
		return FALSE;
		}
	echo "<p class=\"thinborder\" style=\"font-size:small; vertical-align:middle; background-color:azure; color:black; padding:4px 4px 8px 4px; border-radius:6px;\">".$logo;
	if(!$installed) echo "<b>SuperCollider</b> server is responding<br />(port ".SC_RT_PORT.")<br />";
	else {
		echo "<b>SuperCollider</b> is responding<br />";
		if($server) echo "Its server is running (port ".SC_RT_PORT.")<br />";
		}
	if(!$sc_folder) echo "&nbsp;👉&nbsp;&nbsp;<a class=\"darkblue-text\" target=\"_blank\" href=\"index.php?path=supercollider_resources\">SUPERCOLLIDER resource folder</a><br />";
	echo "</p>";
	return TRUE;
	}

// ------------ Server ------------

function scrt_state() {
	$files = scrt_files();
	$state = @json_decode((string)@file_get_contents($files['state']),TRUE);
	return is_array($state) ? $state : array();
	}

function scrt_save_state($state) {
	$files = scrt_files();
	@file_put_contents($files['state'],json_encode($state));
	return;
	}

function scrt_pid_alive($pid) {
	// TRUE if process $pid is running
	$pid = intval($pid);
	if($pid <= 0) return FALSE;
	if(stripos(PHP_OS,"WIN") === 0) {
		$output = array();
		exec("tasklist /FI \"PID eq ".$pid."\" /NH 2>NUL",$output);
		return (stripos(implode(" ",$output),"scsynth") !== FALSE);
		}
	$output = array();
	exec("ps -p ".$pid." -o comm= 2>/dev/null",$output);
	return (stripos(implode(" ",$output),"scsynth") !== FALSE);
	}

function scrt_owned() {
	// TRUE if the server listening to SC_RT_PORT was started by the Bol Processor (its process is still running)
	$state = scrt_state();
	return (isset($state['pid']) AND scrt_pid_alive($state['pid']));
	}

function scrt_running() {
	// Quick check for the project pages
	return scrt_ping(0.3);
	}

function scrt_quit($port = SC_RT_PORT) {
	// Quit the server if the Bol Processor started it. Return TRUE if it is no longer running.
	if(!scrt_owned()) return !scrt_ping(0.3,$port);
	scrt_exchange(array(osc_message("/quit")),'',0.,$port);
	for($i = 0; $i < 30 AND csoundrt_port_in_use($port); $i++) usleep(100000);
	$state = scrt_state();
	unset($state['pid'],$state['token']);
	scrt_save_state($state);
	return !csoundrt_port_in_use($port);
	}

function scrt_log_tail($lines = 12) {
	$files = scrt_files();
	$content = @file_get_contents($files['log']);
	if($content === FALSE OR trim($content) == '') return '';
	$table = explode("\n",str_replace("\r",'',trim($content)));
	return implode("\n",array_slice($table,-$lines));
	}

function scrt_start(&$message,$sc_file = '') {
	// Make sure that a server is running and has the SynthDefs. $message tells what was done: "running", "started", or why it failed.
	$port = SC_RT_PORT;
	$files = scrt_files();
	// A server started by the Bol Processor with fewer buffers (before sample sets, 2026-11-20) is started again
	$state = scrt_state();
	if(scrt_ping(0.5) AND scrt_owned() AND intval($state['buffers'] ?? 0) < SC_RT_BUFFERS) scrt_quit($port);
	if(scrt_ping(0.5)) $message = "running";
	else if(csoundrt_port_in_use($port)) {
		$message = "UDP port ".$port." is used by a program which is not a SuperCollider server";
		return FALSE;
		}
	else {
		$programs = scrt_programs();
		if($programs === FALSE) {
			$message = "SuperCollider was not found on this computer";
			return FALSE;
			}
		// No audio input (it would require the permission to use the microphone), two outputs, no Rendezvous (Bonjour)
		// Many buffers for the recordings of sample sets (see scrt_load_samples())
		$arguments = "-u ".$port." -i 0 -o 2 -R 0 -D 0 -b ".SC_RT_BUFFERS; // -D 0: SynthDefs are loaded by the Bol Processor
		$pid = 0;
		if(stripos(PHP_OS,"WIN") === 0) {
			$command = "powershell -NoProfile -Command \"(Start-Process -FilePath '".$programs['scsynth']."' -ArgumentList '".$arguments."' -WorkingDirectory '".dirname($programs['scsynth'])."' -RedirectStandardOutput '".$files['log']."' -RedirectStandardError '".$files['log'].".err' -WindowStyle Hidden -PassThru).Id\"";
			$output = array();
			exec($command,$output);
			$pid = intval(trim(implode('',$output)));
			}
		else {
			$output = array();
			// "cd ...;" and not "cd ... &&": the whole list would be run in the background by a shell keeping the output of exec() open
			exec("cd ".escapeshellarg(dirname($programs['scsynth']))."; nohup ".escapeshellarg($programs['scsynth'])." ".$arguments." > ".escapeshellarg($files['log'])." 2>&1 < /dev/null & echo $!",$output);
			$pid = intval(trim(implode('',$output)));
			}
		$end = microtime(TRUE) + 10.;
		$ok = FALSE;
		while(microtime(TRUE) < $end) {
			if(scrt_ping(0.3)) {
				$ok = TRUE;
				break;
				}
			if($pid > 0 AND !scrt_pid_alive($pid)) break; // scsynth has given up
			}
		if(!$ok) {
			if($pid > 0 AND scrt_pid_alive($pid)) {
				if(stripos(PHP_OS,"WIN") === 0) exec("taskkill /PID ".$pid." /F");
				else exec("kill ".$pid);
				}
			$message = "The SuperCollider server (scsynth) did not start";
			$tail = scrt_log_tail();
			if($tail <> '') $message .= "<br />It said:<br /><small><span class=\"green-text\">".nl2br(htmlspecialchars($tail))."</span></small>";
			return FALSE;
			}
		$state = scrt_state();
		$state['pid'] = $pid;
		$state['buffers'] = SC_RT_BUFFERS;
		unset($state['token']);
		scrt_save_state($state);
		$message = "started";
		}
	$load_message = '';
	if(!scrt_load_synthdefs($load_message)) {
		$message = $load_message;
		return FALSE;
		}
	if($load_message <> '') $message .= "|".$load_message;
	// 2026-11-20 Recordings used by the SynthDef file of the '-sc' file of the project
	$samples_message = '';
	$ok = scrt_load_samples(scrt_scd_of_resource($sc_file),$samples_message);
	if($samples_message <> '') $message .= (strpos($message,"|") === FALSE ? "|" : "<br />").$samples_message;
	return TRUE;
	}

function scrt_begin_performance() {
	// A server started by the Bol Processor: cancel its scheduled quitting (see scrt_end_performance())
	if(scrt_owned()) scrt_exchange(array(osc_message("/clearSched")));
	return;
	}

function scrt_end_performance() {
	// A server started by the Bol Processor quits after SC_RT_IDLE seconds unless another performance begins
	if(scrt_owned()) scrt_exchange(array(osc_bundle(array(osc_message("/quit")),SC_RT_IDLE)));
	return;
	}

function scrt_log_size() {
	$files = scrt_files();
	clearstatcache();
	$size = @filesize($files['log']);
	return ($size === FALSE) ? 0 : $size;
	}

function scrt_errors_report($offset) {
	// Lines (HTML) explaining the errors of the server during the performance. Its messages are read only if the Bol Processor started it:
	// otherwise they are displayed in the "Post" window of SuperCollider.
	$report = array();
	if(!scrt_owned()) return $report;
	$files = scrt_files();
	$content = @file_get_contents($files['log'],FALSE,NULL,$offset);
	if($content === FALSE OR trim($content) == '') return $report;
	$count = array();
	foreach(preg_split('/\R/',$content) as $line) {
		$line = trim($line);
		if(!preg_match('/FAILURE|ERROR|not found/i',$line)) continue;
		if(!isset($count[$line])) $count[$line] = 0;
		$count[$line]++;
		}
	if(count($count) == 0) return $report;
	$report[] = "SuperCollider reported errors:";
	$k = 0;
	foreach($count as $line => $n) {
		if(++$k > 5) {
			$report[] = "… and more";
			break;
			}
		$report[] = htmlspecialchars($line).($n > 1 ? " (".$n." times)" : "");
		}
	$report[] = "Instrument n is played by SynthDef ‘bpn’: check that it is defined in ‘supercollider_resources’ or ‘supercollider_resources_private’.";
	return $report;
	}

// ------------ SynthDefs ------------

function scrt_helpers() {
	// 2026-11-20 SuperCollider code run before the SynthDef files of each folder are compiled (see scrt_compile()).
	// ~bpSample plays a recording of a sample set loaded by scrt_load_samples(), declared in a SynthDef file by a line
	// "// BP_SAMPLES <number> <map>" (see 0-default.scd). Its table (buffer SC_RT_SAMPLE_TABLES + number) holds, for each layer
	// and MIDI key, 8 values: buffer of the recording, its measured pitch (MIDI key), loop start, loop end, crossfade, attack (seconds), gain, 0.
	//   ~bpSample.(set, channels, key, layer, freq, allow)
	//   set: number of the sample set; channels: 1 or 2, as in its recordings; key, layer: choice of the recording (fixed during the note);
	//   freq: pitch played (Hz, may vary); allow: if >= 0, the attack of the recording is shortened to 'allow' seconds.
	// The recording is looped with a crossfade between its loop points if it has some (loop end > loop start), otherwise it is played to its end.
	// It is a statement of the block of the compiling script (a file run by sclang must be a single block)
	return "~bpSample = { |set, channels, key, layer, freq, allow = -1|\n"
		."\tvar table = ".SC_RT_SAMPLE_TABLES." + set;\n"
		."\tvar layers = (BufFrames.kr(table) / 128).max(1);\n"
		."\tvar e = BufRd.kr(8, table, (layer.clip(0, layers - 1) * 128) + key.clip(0, 127), 0, 1);\n"
		."\tvar buf = e[0], sr = BufSampleRate.kr(e[0]);\n"
		."\tvar ls = e[2] * sr, le = e[3] * sr, loop = le > (ls + 1);\n"
		."\tvar len = (le - ls).max(1), xf = (e[4] * sr).min(len).min(ls).max(1);\n"
		."\tvar start = if(allow.isNumber and: { allow < 0 }) { 0 } { (e[5] - allow).max(0) * sr };\n"
		."\tvar p = start + Sweep.ar(0, sr * freq / e[1].midicps);\n"
		."\tvar inloop = (p >= ls) * loop;\n"
		."\tvar q = (p - ls).wrap(0, len);\n"
		."\tvar a = Select.ar(inloop, [p, ls + q]);\n"
		."\tvar w = inloop * ((q - (len - xf)) / xf).clip(0, 1);\n"
		."\tvar sig = (BufRd.ar(channels, buf, a, 0, 4) * (1 - w).sqrt) + (BufRd.ar(channels, buf, (a - len).max(0), 0, 4) * w.sqrt);\n"
		."\tsig * ((BufFrames.kr(buf) - a) / (0.03 * sr)).clip(0, 1) * e[6];\n"
		."\t};\n";
	}

function scrt_sources($dir) {
	// SynthDef source files of a folder, sorted by name
	$list = glob($dir."*.scd") ?: array();
	sort($list);
	return $list;
	}

function scrt_compiled_dir($dir) {
	return $dir."synthdefs".DIRECTORY_SEPARATOR;
	}

function scrt_needs_compiling($dir) {
	// TRUE if a source file is more recent than the compiled SynthDefs, or a source file has been deleted
	$compiled = scrt_compiled_dir($dir);
	$stamp = $compiled."sources.json";
	$sources = scrt_sources($dir);
	$signature = array();
	foreach($sources as $file) $signature[basename($file)] = filemtime($file);
	$signature['_helpers'] = md5(scrt_helpers()); // A change of the helpers requires compiling again
	$saved = @json_decode((string)@file_get_contents($stamp),TRUE);
	return !(is_array($saved) AND $saved == $signature);
	}

function scrt_compile($dir,&$message) {
	// Compile the SynthDefs of the "*.scd" files of $dir with sclang into its "synthdefs" folder. Return FALSE with the reason in $message.
	// sclang runs with a HOME of its own, so that it does not execute the user's startup file nor load the user's extensions.
	$message = '';
	if(!scrt_needs_compiling($dir)) return TRUE;
	$programs = scrt_programs();
	$compiled = scrt_compiled_dir($dir);
	$sources = scrt_sources($dir);
	if(!is_dir($compiled)) @mkdir($compiled,0775);
	if($programs === FALSE OR $programs['sclang'] == '') {
		if(count(glob($compiled."*.scsyndef") ?: array()) > 0) return TRUE; // Compiled files will be used
		$message = "sclang (SuperCollider) is required to compile the SynthDefs of ‘".basename(rtrim($dir,"/\\"))."’";
		return (count($sources) == 0);
		}
	$files = scrt_files();
	if(!is_dir($files['home'])) @mkdir($files['home'],0775);
	$slash = function($path) { return str_replace("\\","/",$path); };
	$script = "(\nvar dir = \"".$slash($compiled)."\", n = 0;\n".scrt_helpers()."SynthDescLib.global.synthDescs.clear;\n";
	foreach($sources as $file) {
		$script .= "\"BP_FILE ".basename($file)."\".postln;\n";
		$script .= "try { thisProcess.interpreter.executeFile(\"".$slash($file)."\") } { |e| (\"BP_ERROR \" ++ e.errorString).postln };\n";
		}
	$script .= "File.mkdir(dir);\n";
	$script .= "(dir ++ \"*.scsyndef\").pathMatch.do { |f| File.delete(f) };\n";
	$script .= "SynthDescLib.global.synthDescs.do { |d| if(d.def.notNil) { d.def.writeDefFile(dir); n = n + 1 } };\n";
	$script .= "(\"BP_DONE \" ++ n).postln;\n0.exit;\n)\n";
	file_put_contents($files['compile'],$script);
	$output = scrt_run(escapeshellarg($programs['sclang'])." ".escapeshellarg($files['compile']),SC_RT_COMPILE_TIMEOUT,array('HOME' => $files['home']));
	if(!preg_match('/BP_DONE ([0-9]+)/',$output,$match)) {
		$message = "sclang could not compile the SynthDefs of ‘".basename(rtrim($dir,"/\\"))."’";
		return FALSE;
		}
	// Messages of sclang after loading its classes: errors in the source files
	$position = strpos($output,"BP_FILE");
	$messages = ($position === FALSE) ? '' : substr($output,$position);
	$messages = preg_replace('/BP_DONE.*$/s','',$messages);
	if(preg_match('/ERROR|Error:|BP_ERROR/',$messages)) {
		$message = "Errors in the SynthDefs of ‘".basename(rtrim($dir,"/\\"))."’:<br /><small><span class=\"green-text\">".nl2br(htmlspecialchars(trim(preg_replace('/^BP_FILE (.*)$/m',"— $1:",$messages))))."</span></small>";
		}
	$signature = array();
	foreach($sources as $file) $signature[basename($file)] = filemtime($file);
	$signature['_helpers'] = md5(scrt_helpers());
	// Files with errors will be compiled again once corrected
	if($message == '') file_put_contents($compiled."sources.json",json_encode($signature));
	return TRUE;
	}

function scrt_run($command,$timeout,$environment = array()) {
	// Run a command, return its output (stdout and stderr). It is killed after $timeout seconds.
	$env = getenv();
	if(!is_array($env)) $env = array();
	foreach($environment as $name => $value) $env[$name] = $value;
	$process = @proc_open($command,array(0 => array("pipe","r"),1 => array("pipe","w"),2 => array("redirect",1)),$pipes,NULL,$env);
	if(!is_resource($process)) return '';
	fclose($pipes[0]);
	stream_set_blocking($pipes[1],FALSE);
	$output = '';
	$end = microtime(TRUE) + $timeout;
	while(TRUE) {
		$chunk = fread($pipes[1],65536);
		if($chunk !== FALSE AND $chunk !== '') $output .= $chunk;
		$status = proc_get_status($process);
		if(!$status['running']) {
			$output .= stream_get_contents($pipes[1]);
			break;
			}
		if(microtime(TRUE) > $end) {
			proc_terminate($process,9);
			$output .= "\n(stopped after ".$timeout." seconds)";
			break;
			}
		usleep(50000);
		}
	fclose($pipes[1]);
	proc_close($process);
	return $output;
	}

function scrt_load_synthdefs(&$message,$port = SC_RT_PORT) {
	// Compile the SynthDefs if needed, and load them into the server if they have changed or the server has been restarted.
	// After loading, a random token is written to control bus SC_RT_TOKEN_BUS: if the bus no longer holds it, the server has been restarted.
	// Errors in the sources are reported in $message without failing: the SynthDefs compiled are used.
	$message = '';
	$errors = array();
	$signature = array();
	foreach(scrt_dirs() as $kind => $dir) {
		$compile_message = '';
		if(!scrt_compile($dir,$compile_message)) {
			$message = $compile_message;
			return FALSE;
			}
		if($compile_message <> '') $errors[] = $compile_message;
		foreach(glob(scrt_compiled_dir($dir)."*.scsyndef") ?: array() as $file) $signature[$file] = filemtime($file);
		}
	$state = scrt_state();
	$token = isset($state['token']) ? floatval($state['token']) : 0.;
	// The server has been restarted if it doesn't hold the token: SynthDefs and samples must be loaded again
	$same_server = FALSE;
	if($token > 0.) {
		$replies = scrt_exchange(array(osc_message("/c_get",array(SC_RT_TOKEN_BUS))),"/c_set",0.5,$port);
		foreach((array)$replies as $reply)
			if($reply['address'] == "/c_set" AND count($reply['args']) >= 2 AND abs($reply['args'][1] - $token) < 0.5) $same_server = TRUE;
		}
	if(!$same_server) {
		unset($state['samples'],$state['files'],$state['next_buffer']);
		$token = (float) mt_rand(1,8000000); // exact in a 32-bit float
		}
	$loaded = ($same_server AND isset($state['synthdefs']) AND $state['synthdefs'] == $signature);
	if(!$loaded) {
		$packets = array();
		foreach(scrt_dirs() as $dir) {
			if(count(glob(scrt_compiled_dir($dir)."*.scsyndef") ?: array()) == 0) continue;
			$packets[] = osc_message("/d_loadDir",array(str_replace("\\","/",scrt_compiled_dir($dir))));
			}
		$packets[] = osc_message("/sync",array(1));
		$replies = scrt_exchange($packets,"/synced",5.,$port);
		if(!is_array($replies) OR count($replies) == 0) {
			$message = "The SuperCollider server did not load the SynthDefs";
			return FALSE;
			}
		scrt_exchange(array(osc_message("/c_set",array(SC_RT_TOKEN_BUS,$token))),'',0.,$port);
		$state['token'] = $token;
		$state['synthdefs'] = $signature;
		scrt_save_state($state);
		$message = "SynthDefs loaded";
		}
	if(count($errors) > 0) $message .= ($message <> '' ? "<br />" : '').implode("<br />",$errors);
	return TRUE;
	}

// ------------ Samples ------------

function scrt_scd_of_resource($sc_file) {
	// Path of the SynthDef file named by '-sc' file $sc_file (its "orchestra"), or ''
	$dirs = scrt_dirs();
	$path = '';
	foreach(array($dirs['private'],$dirs['fixed']) as $dir) if($sc_file <> '' AND is_file($dir.$sc_file)) { $path = $dir.$sc_file; break; }
	if($path == '') return '';
	$json = @json_decode((string)@file_get_contents($path),TRUE);
	if(!is_array($json)) return '';
	$scd = '';
	foreach($json as $item) if(is_array($item) AND isset($item['key']) AND $item['key'] == "CsoundOrchestraName") $scd = trim((string)$item['value']);
	if($scd == '') return '';
	foreach(array($dirs['private'],$dirs['fixed']) as $dir) if(is_file($dir.$scd)) return $dir.$scd;
	return '';
	}

function scrt_sample_path($name,$first_dir = '') {
	// Absolute path of a map or recording named by a relative path, looked for in the folder of the SynthDef file, then in the SuperCollider
	// and Csound resource folders (private ones first), where the recordings of the distribution are (e.g. "Salamander_piano/...")
	$root = dirname(__DIR__).DIRECTORY_SEPARATOR;
	$dirs = array($first_dir,$root."supercollider_resources_private".DIRECTORY_SEPARATOR,$root."supercollider_resources".DIRECTORY_SEPARATOR,
		$root."csound_resources_private".DIRECTORY_SEPARATOR,$root."csound_resources".DIRECTORY_SEPARATOR);
	foreach($dirs as $dir) {
		if($dir == '') continue;
		$path = realpath($dir.str_replace("/",DIRECTORY_SEPARATOR,$name));
		if($path !== FALSE AND is_file($path)) return $path;
		}
	return '';
	}

function scrt_sample_sets($scd_path) {
	// Sample sets declared in a SynthDef file by lines "// BP_SAMPLES <number> <map>": number => map
	$sets = array();
	if($scd_path == '' OR !is_file($scd_path)) return $sets;
	foreach(file($scd_path,FILE_IGNORE_NEW_LINES) as $line)
		if(preg_match('#^\s*//\s*BP_SAMPLES\s+([0-9]+)\s+(\S+)#',$line,$m) AND intval($m[1]) < 100) $sets[intval($m[1])] = $m[2];
	return $sets;
	}

function scrt_load_samples($scd_path,&$message,$port = SC_RT_PORT) {
	// 2026-11-20 Load the sample sets declared in SynthDef file $scd_path by lines "// BP_SAMPLES <number> <map>", unless they are loaded.
	// A map (JSON) gives the recordings ("samples": file, pitch, loopstart, loopend, xfade, attack, gain) and, in "keys", the recording
	// played by each MIDI key in each layer (-1: the nearest one). Without "keys", each sample gives its "layer" and "key".
	// Each recording is loaded once into a buffer (from SC_RT_SAMPLE_BUFFERS), only its first "maxduration" seconds if the map says so.
	// The table of set n (buffer SC_RT_SAMPLE_TABLES + n, 8 channels, layers * 128 frames) is read by ~bpSample (see scrt_helpers()).
	$message = '';
	if($scd_path == '' OR !is_file($scd_path)) return TRUE;
	$sets = scrt_sample_sets($scd_path);
	if(count($sets) == 0) return TRUE;
	$state = scrt_state();
	if(!isset($state['files'])) $state['files'] = array();
	if(!isset($state['samples'])) $state['samples'] = array();
	if(!isset($state['next_buffer'])) $state['next_buffer'] = SC_RT_SAMPLE_BUFFERS;
	$done = array();
	$time_start = microtime(TRUE);
	foreach($sets as $number => $map_name) {
		$map_path = scrt_sample_path($map_name,dirname($scd_path).DIRECTORY_SEPARATOR);
		if($map_path == '') {
			$message .= "Sample map ‘".htmlspecialchars($map_name)."’ (set ".$number.") was not found<br />";
			continue;
			}
		$signature = $map_path."|".filemtime($map_path);
		if(isset($state['samples'][$number]) AND $state['samples'][$number] == $signature) continue; // Already loaded
		$map = @json_decode((string)@file_get_contents($map_path),TRUE);
		if(!is_array($map) OR !isset($map['samples']) OR !is_array($map['samples'])) {
			$message .= "Sample map ‘".htmlspecialchars($map_name)."’ is not valid JSON with a list of \"samples\"<br />";
			continue;
			}
		$frames = isset($map['maxduration']) ? (int) round(floatval($map['maxduration']) * 44100) : 0;
		$channels = isset($map['channels']) ? intval($map['channels']) : 1;
		// Recordings to load
		$packets = array();
		$buffers = array();
		$missing = 0;
		foreach($map['samples'] as $i => $sample) {
			$path = scrt_sample_path((string)($sample['file'] ?? ''),dirname($map_path).DIRECTORY_SEPARATOR);
			if($path == '') {
				$missing++;
				$buffers[$i] = -1;
				continue;
				}
			$id = $path."|".$frames;
			if(!isset($state['files'][$id])) {
				$state['files'][$id] = $state['next_buffer']++;
				$packets[] = osc_message("/b_allocRead",array((int)$state['files'][$id],str_replace("\\","/",$path),0,$frames));
				}
			$buffers[$i] = $state['files'][$id];
			}
		if($missing > 0) $message .= $missing." recording(s) of ‘".htmlspecialchars($map_name)."’ were not found<br />";
		// The table: for each layer and key, the recording given by "keys", or the nearest one in the same layer, or in any layer
		$layers = max(1,intval($map['layers'] ?? 1));
		$keys = $map['keys'] ?? NULL;
		$grid = array_fill(0,$layers,array_fill(0,128,-1));
		if(is_array($keys)) {
			for($l = 0; $l < $layers; $l++) for($k = 0; $k < 128; $k++) $grid[$l][$k] = intval($keys[$l][$k] ?? -1);
			}
		else foreach($map['samples'] as $i => $sample) {
			$l = intval($sample['layer'] ?? 0);
			$k = intval($sample['key'] ?? round(floatval($sample['pitch'] ?? 60)));
			if($l >= 0 AND $l < $layers AND $k >= 0 AND $k < 128) $grid[$l][$k] = $i;
			}
		$values = array();
		for($l = 0; $l < $layers; $l++) {
			for($k = 0; $k < 128; $k++) {
				$best = -1;
				$distance = 1000;
				foreach(array_merge(array($l),range(0,$layers - 1)) as $ll) {
					for($kk = 0; $kk < 128; $kk++) {
						$i = $grid[$ll][$kk];
						if($i < 0 OR !isset($buffers[$i]) OR $buffers[$i] < 0) continue;
						$d = abs($kk - $k) + ($ll == $l ? 0 : 200);
						if($d < $distance) { $distance = $d; $best = $i; }
						}
					if($best >= 0) break;
					}
				if($best < 0) { array_push($values,0.,60.,0.,0.,0.,0.,0.,0.); continue; }
				$s = $map['samples'][$best];
				array_push($values,(float)$buffers[$best],floatval($s['pitch'] ?? $k),floatval($s['loopstart'] ?? 0),floatval($s['loopend'] ?? 0),
					floatval($s['xfade'] ?? 0),floatval($s['attack'] ?? 0),floatval($s['gain'] ?? 1),0.);
				}
			}
		$table = SC_RT_SAMPLE_TABLES + $number;
		$packets[] = osc_message("/b_free",array($table));
		$packets[] = osc_message("/b_alloc",array($table,$layers * 128,8));
		$packets[] = osc_message("/sync",array(2));
		$replies = scrt_exchange($packets,"/synced",120.,$port);
		$fails = array();
		foreach((array)$replies as $reply) if($reply['address'] == "/fail") $fails[] = implode(" ",$reply['args']);
		if(!is_array($replies) OR count($replies) == 0) {
			$message .= "The SuperCollider server did not load the recordings of ‘".htmlspecialchars($map_name)."’<br />";
			scrt_save_state($state);
			return FALSE;
			}
		if(count($fails) > 0) {
			$message .= "Loading ‘".htmlspecialchars($map_name)."’, the server said: ".htmlspecialchars(implode("; ",array_slice(array_unique($fails),0,3)))."<br />";
			if(preg_grep('/range|buffer/i',$fails)) $message .= "The server has too few buffers. In SuperCollider, run <i>s.options.numBuffers = ".SC_RT_BUFFERS."; s.reboot;</i> or quit its server, so that the Bol Processor starts its own.<br />";
			scrt_save_state($state);
			return FALSE;
			}
		$packets = array();
		for($start = 0; $start < count($values); $start += 1024) {
			$chunk = array_slice($values,$start,1024);
			$packets[] = osc_message("/b_setn",array_merge(array($table,$start,count($chunk)),$chunk));
			}
		$packets[] = osc_message("/sync",array(3));
		scrt_exchange($packets,"/synced",5.,$port);
		$state['samples'][$number] = $signature;
		$done[] = basename($map_name,".json")." (".count(array_unique($buffers)).")";
		scrt_save_state($state);
		}
	if(count($done) > 0) $message .= "Samples loaded: ".htmlspecialchars(implode(", ",$done))." in ".round(microtime(TRUE) - $time_start,1)." s";
	return TRUE;
	}

function scrt_synthdef_names() {
	// Names of the SynthDefs compiled, with the folder of each one ('fixed' or 'private'), private ones replacing fixed ones
	$names = array();
	foreach(scrt_dirs() as $kind => $dir)
		foreach(glob(scrt_compiled_dir($dir)."*.scsyndef") ?: array() as $file) $names[basename($file,".scsyndef")] = $kind;
	ksort($names,SORT_NATURAL);
	return $names;
	}
?>
