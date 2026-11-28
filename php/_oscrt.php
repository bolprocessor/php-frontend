<?php
// Real-time OSC (2026-11-21)
// The console, with option "--osc port[@host]", sends notes as generic OSC messages (/bp/start, /bp/note, /bp/off, /bp/stop, /bp/end)
// at their dates to a program such as Max or Pd listening to this UDP port, see CsoundRealTime.c. Unlike real-time Csound and SuperCollider,
// no server is started: the receiving program is run by the user. Its address is set for each machine with the form of the project pages
// (oscrt_settings.php), stored in _settings.php ($oscrt_host, $oscrt_port). Example receivers are in "osc_resources".
// 2026-11-24 A grammar or data may name a Python program receiving the messages, e.g. the robot "osc_resources/dancer.py", with a line "-py.dancer"
// on top (the console skips it). Project pages show it with buttons to change it and to start it (oscrt_robot.php), once Python is found (python_status()).
// OSC resources are listed by index.php?path=osc_resources: "osc_resources" (fixed) and "osc_resources_private" (the user's), see resource_dirs("osc") - by Claude

if(!defined("OSC_RT_DEFAULT_PORT")) {
	define("OSC_RT_DEFAULT_PORT",7400);
	define("OSC_RT_DEFAULT_HOST","127.0.0.1");
	}

function oscrt_destination() {
	// Host and port of the receiving program, as set for this machine, or the defaults
	global $oscrt_host, $oscrt_port;
	$host = (isset($oscrt_host) AND trim($oscrt_host) <> '') ? trim($oscrt_host) : OSC_RT_DEFAULT_HOST;
	$port = (isset($oscrt_port) AND intval($oscrt_port) > 0) ? intval($oscrt_port) : OSC_RT_DEFAULT_PORT;
	return array('host' => $host,'port' => $port);
	}

function oscrt_valid($host,$port) {
	// '' if this destination is acceptable, otherwise the reason
	if(!ctype_digit((string)$port) OR intval($port) < 1 OR intval($port) > 65535) return "The port must be a number from 1 to 65535";
	if(!preg_match('/^[A-Za-z0-9.:_-]{1,255}$/',$host)) return "The host must be a name or an IP address, e.g. 127.0.0.1 for this computer";
	return '';
	}

function oscrt_option() {
	// Option of the console
	$d = oscrt_destination();
	return " --osc ".$d['port'].($d['host'] <> "127.0.0.1" ? "@".$d['host'] : '');
	}

function oscrt_panel($sc_file,$csound_file,$python_file = '') {
	// Displayed on project pages instead of the name of the output file, when the output is real-time OSC
	// The destination and the messages are shown by the "settings" button of oscrt_format_notes() (2026-11-21)
	// 2026-11-24 $python_file: the program named by the line "-py.NAME" of the project ("NAME.py"), see oscrt_program_line()
	oscrt_program_line($python_file);
	echo "<p>";
	$kind = '';
	if($sc_file <> '' AND file_exists(resource_path("supercollider",$sc_file))) { $kind = "supercollider"; $file = $sc_file; }
	else if($csound_file <> '' AND file_exists(csound_resource_path($csound_file))) { $kind = "csound"; $file = $csound_file; }
	if($kind <> '') {
		echo "Instrument names and parameters are defined in ".resource_file_link($kind,$file).".";
		change_instruments_form("osc",$file); // 2026-11-21 '-sc' and '-cs' files in the same menu
		// 2026-11-21 The list of instruments is shown by the "Details" button (no reload), as in real-time SuperCollider (scrt_panel())
		$instruments = list_of_instruments(resource_path($kind,$file));
		$names = array();
		foreach($instruments['list'] as $i => $name) $names[intval($instruments['index'][$i])] = array($name,$instruments['param'][$i]);
		ksort($names);
		if(count($names) > 0) {
			echo "<button type=\"button\" class=\"edit\" style=\"font-size:small; margin-top:4px;\" onclick=\"var b = document.getElementById('oscrt_details'); b.style.display = (b.style.display == 'none') ? '' : 'none';\">Show/hide details</button><br />";
			echo "<div id=\"oscrt_details\" style=\"display:none;\">Instruments sent in <i>/bp/note</i> messages:<ul style=\"margin-top:0px; margin-bottom:0px\">";
			foreach($names as $number => $item) {
				echo "<li><b>_ins(</b><span class=\"turquoise-text\">".htmlspecialchars($item[0])."</span><b>)</b> = <b>_ins(".$number.")</b>";
				if(count($item[1]) > 0) echo " ➡ parameter(s) ".implode(" ",array_map(function($p) { return "“<span class=\"turquoise-text\">".htmlspecialchars($p)."</span>”"; },$item[1]));
				echo "</li>";
				}
			echo "</ul><br /></div>";
			}
		else echo "<br />";
		}
	else {
		echo "Instruments: no ‘-sc’ or ‘-cs’ file, so instrument <i>n</i> is named ‘bp<i>n</i>’.";
		change_instruments_form("osc");
		echo "<br />";
		}
	echo "The MIDI ports below are used for interaction,<br />e.g. <i>_script(wait for C3 channel 1)</i>, once saved.";
	echo "</p>";
	return;
	}

function oscrt_format_notes() {
	// Displayed below the SAVE format button when the output is real-time OSC: the messages, the destination (saved for this machine by oscrt_settings.php), the examples
	$d = oscrt_destination();
	// 2026-11-21 The examples are always visible, below the settings. The destination, the messages and the form are shown by the "settings" button (no reload).
	// A <span> rather than a <div>, because data.php displays this in a paragraph.
	echo "<br /><br /><button type=\"button\" class=\"edit\" style=\"font-size:small;\" title=\"Destination of the OSC messages on this computer\" onclick=\"oscrtToggleSettings();\">Show/hide settings</button>";
	echo "<span id=\"oscrt_settings\" style=\"display:none;\"><br /><br /><small>Notes are sent as OSC messages to UDP port <b><span class=\"oscrt_port_text\">".$d['port']."</span></b> of <b><span id=\"oscrt_host_text\">".htmlspecialchars($d['host'])."</span></b> (a program such as Max or Pd must listen to it, e.g. <i>[udpreceive <span class=\"oscrt_port_text\">".$d['port']."</span>]</i> in Max).<br /><br />";
	echo "Each note is sent at its date as:<br /><i>/bp/note id instrument name key freq duration velocity channel [name value]…</i><br />";
	echo "<i>/bp/off id instrument name key channel</i> at its end<br />";
	echo "and <i>/bp/start</i>, <i>/bp/stop</i> (silence!), <i>/bp/end</i>.</small>";
	echo "<br /><span>Send to host <input type=\"text\" id=\"oscrt_host\" size=\"12\" value=\"".htmlspecialchars($d['host'])."\"> port <input type=\"text\" id=\"oscrt_port\" size=\"5\" value=\"".$d['port']."\">";
	echo "&nbsp;<button type=\"button\" class=\"edit\" style=\"font-size:small;\" onclick=\"oscrtSaveSettings();\">SAVE</button></span>";
	echo "<br /><span id=\"oscrt_message\" class=\"red-text\"></span></span>";
	echo "<br /><small>Examples receiving these messages: <a class=\"linkdotted\" href=\"../osc_resources/BP_OSC_receiver.maxpat\" download>Max</a>, ";
	echo "<a class=\"linkdotted\" href=\"../osc_resources/BP_OSC_receiver.pd\" download>Pd</a>, <a class=\"linkdotted\" href=\"../osc_resources/BP_OSC_to_Live.amxd\" download>Ableton Live</a> (Max for Live), <a class=\"linkdotted\" target=\"_blank\" href=\"../osc_resources/ReadMe.txt\">ReadMe</a></small>";
	// The fields have no 'name', so that they are not sent with the form of the page
	echo "<script>
	function oscrtToggleSettings() {
		var box = document.getElementById('oscrt_settings');
		box.style.display = (box.style.display == 'none') ? '' : 'none';
		}
	function oscrtSaveSettings() {
		var data = new FormData();
		data.append('host',document.getElementById('oscrt_host').value);
		data.append('port',document.getElementById('oscrt_port').value);
		var message = document.getElementById('oscrt_message');
		fetch('oscrt_settings.php',{method:'POST',body:data,cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {
			message.textContent = d.error ? d.error : 'Saved for this computer.';
			if(!d.error) {
				document.getElementById('oscrt_host').value = d.host; document.getElementById('oscrt_port').value = d.port;
				document.getElementById('oscrt_host_text').textContent = d.host;
				document.querySelectorAll('.oscrt_port_text').forEach(function(e) {e.textContent = d.port;});
				}
			}).catch(function() {message.textContent = 'These values could not be saved.';});
		}
	</script>";
	return;
	}
// ------------ Programs receiving OSC: Python robots (2026-11-24, by Claude) ------------

// 2026-11-24 System of the server, which runs Python. windows_system(), mac_system() and linux_system() read the browser's User-Agent instead.
function osc_server_windows() { return PHP_OS_FAMILY == "Windows"; }
function osc_server_mac() { return PHP_OS_FAMILY == "Darwin"; }
function osc_server_linux() { return PHP_OS_FAMILY == "Linux"; }

function oscrt_python_programs() {
	// Names of the Python programs ('*.py') of both OSC resource folders
	$list = array();
	foreach(resource_files("osc") as $thisfile => $dir) {
		if(!str_ends_with($thisfile,".py") OR !is_file($dir.$thisfile)) continue;
		if(str_ends_with($thisfile,"_bak") OR preg_match("/_lock[0-9]*$/u",$thisfile)) continue;
		$list[] = $thisfile;
		}
	return $list;
	}

function oscrt_local_destination() {
	// TRUE if the OSC messages are sent to this computer, where a robot can be started
	$d = oscrt_destination();
	return in_array(strtolower($d['host']),array("127.0.0.1","localhost","::1"));
	}

function oscrt_program_line($python_file) {
	// The Python program named by the project ("-py.NAME"), with buttons to start it and to choose another one. Must be called inside the form of the page.
	global $url_this_page;
	$programs = oscrt_python_programs();
	echo "<p>";
	if($python_file <> '') {
		$found = file_exists(resource_path("osc",$python_file));
		if($found) echo "Receiving program: ".oscrt_resource_link($python_file);
		else echo "Receiving program: <span class=\"red-text\">‘".htmlspecialchars($python_file)."’ was not found</span> in the <a class=\"linkdotted\" target=\"_blank\" href=\"index.php?path=osc_resources\">OSC resource folder</a>";
		if(count($programs) > 0) echo " <button type=\"button\" class=\"save\" style=\"font-size:small;\" onclick=\"if(checksaved()) { var d = document.getElementById('change_python'); d.style.display = (d.style.display == 'none') ? 'inline' : 'none'; }\">Change</button>";
		if($found) {
			$python = python_status();
			$running = oscrt_robot_running();
			echo "<br />";
			if(!$python['ok']) echo "<span class=\"red-text\">➡</span> It needs Python (see the box on the right)";
			else if(!oscrt_local_destination()) {
				$d = oscrt_destination();
				echo "<span class=\"red-text\">➡</span> Messages go to ".htmlspecialchars($d['host']).": start the program on that computer";
				}
			else {
				echo "<button type=\"button\" class=\"produce\" onclick=\"return oscrtRobot('start',".htmlspecialchars(json_encode($python_file)).");\">START THE ROBOT</button>";
				echo " <button type=\"button\" class=\"edit\" id=\"oscrt_robot_stop\" style=\"font-size:small;".($running === '' ? " display:none;" : '')."\" onclick=\"return oscrtRobot('stop','');\">STOP</button>";
				echo " <span id=\"oscrt_robot_message\" style=\"font-size:small;\">".($running <> '' ? "‘".htmlspecialchars($running)."’ is running" : '')."</span>";
				}
			}
		}
	else {
		echo "Receiving program: Max, Pd… or a Python robot of the <a class=\"linkdotted\" target=\"_blank\" href=\"index.php?path=osc_resources\">OSC resource folder</a>";
		if(count($programs) > 0) echo " <button type=\"button\" class=\"save\" style=\"font-size:small;\" onclick=\"if(checksaved()) { var d = document.getElementById('change_python'); d.style.display = (d.style.display == 'none') ? 'inline' : 'none'; }\">Choose a Python program</button>";
		}
	if(count($programs) > 0) {
		echo "<span id=\"change_python\" style=\"display:none;\"><br /><select name=\"new_python_file\">";
		echo "<option value=\"-\">(none)</option>";
		foreach($programs as $thisfile) echo "<option value=\"".htmlspecialchars($thisfile)."\"".($thisfile == $python_file ? " selected" : '').">".htmlspecialchars($thisfile)."</option>";
		echo "</select> <input class=\"save\" type=\"submit\" onclick=\"this.form.target='_self'; return true;\" name=\"set_python_file\" formaction=\"".$url_this_page."#topmidiports\" value=\"USE THIS PROGRAM\"></span>";
		}
	echo "</p>";
	echo "<script>
	function oscrtRobot(action,file) {
		var data = new FormData();
		data.append('action',action);
		data.append('file',file);
		var message = document.getElementById('oscrt_robot_message');
		var stop = document.getElementById('oscrt_robot_stop');
		message.textContent = (action == 'start') ? 'Starting…' : 'Stopping…';
		fetch('oscrt_robot.php',{method:'POST',body:data,cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {
			message.innerHTML = d.message;
			stop.style.display = d.running ? '' : 'none';
			}).catch(function() {message.textContent = 'No answer from the interface.';});
		return false;
		}
	</script>";
	return;
	}

function oscrt_record_program($project_path) {
	// 2026-11-24 Record in _settings.php the Python program named by a grammar or data played in real-time OSC ('-py.NAME' line),
	// e.g. $grammar_python_file["-gr.robot_dance"] = "dancer.py", or $data_python_file["-da.robot_semaphore"] = "dancer-with-flags.py".
	// The line is read in the project file (as saved: the program is saved at once when chosen), not taken from the link, which may come from a page
	// loaded before a change. A project naming no program gets "" only if it was recorded before. Nothing is recorded if the file can't be read. - by Claude
	global $grammar_python_file,$data_python_file;
	$project = basename(str_replace(SLASH,'/',$project_path));
	if($project == '') return;
	if(preg_match("/^-gr\./u",$project) OR preg_match("/\.bpgr$/u",$project)) $variable = "grammar_python_file";
	else if(preg_match("/^-da\./u",$project) OR preg_match("/\.bpda$/u",$project)) $variable = "data_python_file";
	else return;
	$content = @file_get_contents($project_path);
	if($content === FALSE) return;
	$python_file = '';
	foreach(preg_split("/\r\n|\n|\r/u",$content) as $line) {
		$line = trim($line);
		if(str_starts_with($line,"-py.") AND !is_integer(strpos($line,"<"))) { // As in extract_data()
			$python_file = substr($line,4).".py";
			break;
			}
		}
	$recorded = ($variable == "grammar_python_file") ? $grammar_python_file : $data_python_file;
	if(isset($recorded[$project])) {
		if($recorded[$project] == $python_file) return;
		}
	else if($python_file == '') return;
	save_settings2($variable,$project,$python_file);
	if($variable == "grammar_python_file") $grammar_python_file[$project] = $python_file;
	else $data_python_file[$project] = $python_file;
	return;
	}

function oscrt_resource_link($name) {
	// Name of an OSC resource with a link to its page
	return "<a class=\"linkdotted\" target=\"_blank\" href=\"oscresource.php?file=".urlencode(resource_folder("osc",$name).SLASH.$name)."\">‘".htmlspecialchars($name)."’</a>";
	}

function check_osc($python_needed = TRUE) {
	// Box displayed next to that of Csound on project pages in real-time OSC, and on the OSC resource folder: Python and the link to the folder
	global $path, $osc_resources;
	$osc_folder = (isset($path) AND $path == $osc_resources);
	echo "<p class=\"thinborder\" style=\"font-size:small; vertical-align:middle; background-color:azure; color:black; padding:4px 4px 8px 4px; border-radius:6px; line-height:1.6;\">";
	if($python_needed OR $osc_folder) {
		$python = python_status();
		if($python['ok']) echo "&nbsp;🐍&nbsp;&nbsp;<b>Python</b> ".htmlspecialchars($python['version'])." (Tk ".htmlspecialchars($python['tk']).") is ready<br />";
		else {
			echo "&nbsp;🐍&nbsp;&nbsp;<span class=\"red-text\"><b>Python</b> is needed for the robots</span><br />";
			if($python['reason'] <> '') echo "&nbsp;<small>".$python['reason']."</small><br />";
			// 2026-11-24 On Linux, python.org only offers the source code: Python and tkinter come from the package manager of the system
			if(osc_server_linux()) echo "&nbsp;<small>Install it in a terminal: ".oscrt_command_html("sudo apt install python3 python3-tk")."<br />&nbsp;(Fedora: ".oscrt_command_html("sudo dnf install python3 python3-tkinter").")</small><br />&nbsp;";
			else echo "&nbsp;<a class=\"linkdotted\" target=\"_blank\" href=\"".python_download_url()."\"><button type=\"button\" class=\"produce\" style=\"font-size:small;\">INSTALL PYTHON</button></a> ";
			echo "<button type=\"button\" class=\"edit\" style=\"font-size:small;\" onclick=\"var b = this; b.textContent = 'Checking…'; var data = new FormData(); data.append('action','check'); fetch('oscrt_robot.php',{method:'POST',body:data,cache:'no-store',credentials:'same-origin'}).then(function() {location.reload();});\">CHECK AGAIN</button><br />";
			// 2026-11-24 Python installed elsewhere: its path can be typed, as for Csound
			$example = osc_server_windows() ? "py" : (osc_server_mac() ? "/usr/local/bin/python3" : "/usr/bin/python3");
			echo "&nbsp;<small>Installed elsewhere? Type the path of Python, or the command that starts it in a terminal (e.g. <span class=\"green-text\">".htmlspecialchars($example)."</span>):</small><br />&nbsp;<input type=\"text\" id=\"python_path\" size=\"36\" style=\"background-color:white; color:black;\" value=\"\" onkeydown=\"if(event.key == 'Enter') {event.preventDefault(); this.nextElementSibling.click();}\"> ";
			echo "<button type=\"button\" class=\"save\" style=\"font-size:small;\" onclick=\"var data = new FormData(); data.append('action','path'); data.append('path',document.getElementById('python_path').value); fetch('oscrt_robot.php',{method:'POST',body:data,cache:'no-store',credentials:'same-origin'}).then(function(r) {return r.json();}).then(function(d) {if(d.ok) location.reload(); else document.getElementById('python_path_message').innerHTML = d.message;});\">TRY</button><br />";
			echo "&nbsp;<small><span id=\"python_path_message\" class=\"red-text\"></span></small>";
			}
		}
	if(!$osc_folder) echo "&nbsp;👉&nbsp;&nbsp;<a class=\"darkblue-text\" target=\"_blank\" href=\"index.php?path=osc_resources\">OSC resource folder</a><br />";
	echo "</p>";
	return;
	}

function python_download_url() {
	if(osc_server_windows()) return "https://www.python.org/downloads/windows/";
	if(osc_server_mac()) return "https://www.python.org/downloads/macos/";
	return "https://www.python.org/downloads/";
	}

function python_candidates() {
	// Python 3 interpreters that may be installed: [path, arguments]. On MacOS, the Python of www.python.org comes first, since Apple's has Tk 8.5.
	// /usr/bin/python3 of MacOS is not tried: without the Command Line Tools, it would open a window offering to install them.
	// 2026-11-24 A path typed by the user (check_osc()) comes first
	global $python_path;
	$list = array();
	if(isset($python_path) AND trim($python_path) <> '') $list[] = array(trim($python_path),'');
	if(osc_server_windows()) {
		// 2026-11-24 Python may be installed in many ways: the installer of python.org (AppData\Local\Programs\Python of the user, or Program Files),
		// the Python install manager (AppData\Local\Python\pythoncore-3.X-64, with "py" as an alias), the Microsoft Store (aliases in
		// AppData\Local\Microsoft\WindowsApps), Anaconda... So the places recorded in the registry are tried (PEP 514), then the usual folders
		// of all users (the web server may not have the variables of the user's session, such as LOCALAPPDATA), and finally the commands
		// "py", "python" and "python3" as a terminal would find them, which is how the user would check that Python works.
		$found = python_windows_registry();
		$patterns = array("C:/Users/*/AppData/Local/Programs/Python/Python3*/python.exe","C:/Users/*/AppData/Local/Python/pythoncore-3*/python.exe","C:/Program Files/Python3*/python.exe","C:/Python3*/python.exe");
		$local = getenv("LOCALAPPDATA");
		if($local) {
			$local = str_replace("\\","/",$local);
			array_unshift($patterns,$local."/Programs/Python/Python3*/python.exe",$local."/Python/pythoncore-3*/python.exe");
			}
		$globbed = array();
		foreach($patterns as $pattern) $globbed = array_merge($globbed,glob($pattern) ?: array());
		usort($globbed,function($a,$b) { return strnatcasecmp(basename(dirname($b)),basename(dirname($a))); }); // Newest first
		$found = array_merge($found,$globbed);
		$seen = array();
		foreach($found as $p) {
			$key = strtolower(str_replace("\\","/",$p));
			if(isset($seen[$key])) continue;
			$seen[$key] = TRUE;
			$list[] = array($p,'');
			}
		// Python launchers, for all users or for one user
		foreach(array_merge(array("C:/Windows/py.exe"),glob("C:/Users/*/AppData/Local/Programs/Python/Launcher/py.exe") ?: array()) as $p) $list[] = array($p,"-3");
		// Commands found in the PATH of the web server, as in a terminal (aliases of the install manager and of the Microsoft Store included)
		$list[] = array("py","-3");
		$list[] = array("python",'');
		$list[] = array("python3",'');
		foreach(array("py","python3","python") as $name) {
			foreach(glob("C:/Users/*/AppData/Local/Microsoft/WindowsApps/".$name.".exe") ?: array() as $p) $list[] = array($p,($name == "py") ? "-3" : '');
			}
		}
	else if(osc_server_linux()) {
		foreach(array("/usr/bin/python3","/usr/local/bin/python3") as $p) $list[] = array($p,'');
		}
	else {
		foreach(array("/Library/Frameworks/Python.framework/Versions/Current/bin/python3","/usr/local/bin/python3","/opt/homebrew/bin/python3","/usr/local/opt/python3/bin/python3","/Library/Developer/CommandLineTools/usr/bin/python3") as $p) $list[] = array($p,'');
		}
	return $list;
	}

function python_is_command($path) {
	// A command found in the PATH ("py"), or an alias of the Microsoft Store or the install manager (WindowsApps), which is_file() may not see
	return (strpbrk($path,"/\\") === FALSE OR stripos($path,"WindowsApps") !== FALSE);
	}

function python_windows_registry() {
	// 2026-11-24 Interpreters recorded in the registry by the installers (PEP 514): ExecutablePath, or python.exe in InstallPath
	$list = array();
	foreach(array("HKCU\\Software\\Python","HKLM\\Software\\Python","HKLM\\Software\\WOW6432Node\\Python") as $root) {
		$output = scrt_run(array("reg","query",$root,"/s"),5);
		$key = '';
		foreach(preg_split("/\R/",$output) as $line) {
			if(preg_match("/^HKEY_/i",$line)) {
				$key = trim($line);
				continue;
				}
			if(!preg_match("/^\s+(\S.*?)\s+REG_(?:EXPAND_)?SZ\s+(.+)$/",$line,$match)) continue;
			$value = trim($match[2]);
			if(strcasecmp($match[1],"ExecutablePath") == 0) $exe = $value;
			else if(preg_match("/\\\\InstallPath$/i",$key) AND preg_match("/^\(/",$match[1])) $exe = rtrim($value,"\\")."\\python.exe";
			else continue;
			if(preg_match("/python\.exe$/i",$exe) AND is_file($exe)) $list[] = $exe;
			}
		}
	return $list;
	}

function python_status_file() {
	$dir = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor");
	if($dir === FALSE) $dir = dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor";
	return $dir.DIRECTORY_SEPARATOR."python_status.json";
	}

function python_probe($path,$arguments) {
	// Version of Python, its executable and the version of its Tk ('' if tkinter is missing), or FALSE if it does not answer.
	// 2026-11-24 The command is an array, run without a shell: on Windows, cmd.exe removed the first and last quotes of a command starting with a quote,
	// so that Python was never found. The launcher (py.exe) gives the actual interpreter (sys.executable), used to start the robots.
	$code = "import sys; print(sys.version.split()[0]); print(sys.executable); import tkinter; print(tkinter.TkVersion)";
	$command = array($path);
	if(osc_server_linux()) $command = array("env","-u","LD_LIBRARY_PATH",$path); // Same environment as the robot (see oscrt_robot_start())
	if($arguments <> '') $command[] = $arguments;
	$command[] = "-c";
	$command[] = $code;
	$output = scrt_run($command,15);
	$lines = preg_split("/\R/",trim($output));
	if(!isset($lines[0]) OR !preg_match("/^3\.[0-9]+/",trim($lines[0]))) return FALSE;
	$executable = (isset($lines[1]) AND is_file(trim($lines[1]))) ? trim($lines[1]) : '';
	$tk = (isset($lines[2]) AND preg_match("/^[0-9]+\.[0-9]+$/",trim($lines[2]))) ? trim($lines[2]) : '';
	return array('version' => trim($lines[0]),'executable' => $executable,'tk' => $tk);
	}

function python_status($refresh = FALSE) {
	// A Python 3 able to open the window of the robots (tkinter, with Tk 8.6 or later on MacOS): 'ok', 'path', 'arguments', 'version', 'tk',
	// and 'reason' telling what is wrong when it is not 'ok'. The interpreter found is remembered (python_status.json) as long as its file is unchanged.
	static $status = NULL;
	if($status !== NULL AND !$refresh) return $status;
	$file = python_status_file();
	if(!$refresh) {
		$saved = @json_decode((string)@file_get_contents($file),TRUE);
		if(is_array($saved) AND isset($saved['path']) AND $saved['path'] <> '' AND (python_is_command($saved['path']) OR (is_file($saved['path']) AND @filemtime($saved['path']) == ($saved['mtime'] ?? -1)))) {
			$status = $saved;
			$status['ok'] = TRUE;
			$status['reason'] = '';
			return $status;
			}
		}
	@unlink($file);
	$status = array('ok' => FALSE,'path' => '','arguments' => '','version' => '','tk' => '','reason' => '');
	$reason = '';
	$tried = 0;
	foreach(python_candidates() as $candidate) {
		list($path,$arguments) = $candidate;
		if(!python_is_command($path) AND !is_file($path)) continue;
		$tried++;
		$info = python_probe($path,$arguments);
		if($info === FALSE) continue;
		$name = "Python ".$info['version'];
		if($info['tk'] == '') {
			if($reason == '') $reason = $name." (".htmlspecialchars($path).") has no tkinter for the window";
			continue;
			}
		if(osc_server_mac() AND version_compare($info['tk'],"8.6","<")) {
			if($reason == '') $reason = $name." (".htmlspecialchars($path).") has Tk ".$info['tk'].", whose window doesn't work on recent versions of MacOS";
			continue;
			}
		if($info['executable'] <> '') { // The interpreter itself, rather than a launcher or a link
			$path = $info['executable'];
			$arguments = '';
			}
		$status = array('ok' => TRUE,'path' => $path,'arguments' => $arguments,'version' => $info['version'],'tk' => $info['tk'],'reason' => '','mtime' => @filemtime($path));
		@file_put_contents($file,json_encode($status));
		return $status;
		}
	if($reason == '') $reason = ($tried > 0) ? "Python was found but did not answer" : "No Python 3 was found in the usual places";
	$status['reason'] = $reason;
	return $status;
	}

function oscrt_robot_file() {
	return dirname(python_status_file()).DIRECTORY_SEPARATOR."osc_robot.json";
	}

function oscrt_robot_state() {
	$state = @json_decode((string)@file_get_contents(oscrt_robot_file()),TRUE);
	return is_array($state) ? $state : array();
	}

function oscrt_pid_alive($pid) {
	// TRUE if process $pid is running Python
	$pid = intval($pid);
	if($pid <= 0) return FALSE;
	$output = array();
	if(osc_server_windows()) exec("tasklist /FI \"PID eq ".$pid."\" /NH 2>NUL",$output);
	else exec("ps -p ".$pid." -o comm= 2>/dev/null",$output);
	return (stripos(implode(" ",$output),"python") !== FALSE);
	}

function oscrt_robot_running() {
	// Name of the program started by the Bol Processor which is still running, or ''
	$state = oscrt_robot_state();
	if(isset($state['pid']) AND oscrt_pid_alive($state['pid'])) return $state['file'] ?? '';
	return '';
	}

function oscrt_robot_stop() {
	$state = oscrt_robot_state();
	if(isset($state['pid']) AND oscrt_pid_alive($state['pid'])) {
		if(osc_server_windows()) exec("taskkill /PID ".intval($state['pid'])." /F");
		else exec("kill ".intval($state['pid']));
		for($i = 0; $i < 20 AND oscrt_pid_alive($state['pid']); $i++) usleep(100000);
		}
	@unlink(oscrt_robot_file());
	return;
	}

function oscrt_linux_display() {
	// 2026-11-24 Display of the desktop for a web server that has none: the first X server running (socket /tmp/.X11-unix/X0, X1...), otherwise :0
	$sockets = glob("/tmp/.X11-unix/X*") ?: array();
	natsort($sockets);
	foreach($sockets as $socket) {
		if(preg_match("/X([0-9]+)$/",$socket,$match)) return ":".$match[1];
		}
	return ":0";
	}

function oscrt_command_html($command) {
	// 2026-11-24 A terminal command, with a small button copying it to the clipboard (also works in a message inserted with innerHTML)
	$copy = "var b = this, t = b.getAttribute('data-command'); function done() {b.textContent = 'Copied'; setTimeout(function() {b.textContent = 'Copy';},1500);} if(navigator.clipboard &amp;&amp; window.isSecureContext) navigator.clipboard.writeText(t).then(done); else {var a = document.createElement('textarea'); a.value = t; document.body.appendChild(a); a.select(); document.execCommand('copy'); document.body.removeChild(a); done();}";
	return "<span class=\"green-text\" style=\"font-family:monospace;\">".htmlspecialchars($command)."</span> <button type=\"button\" class=\"edit\" style=\"font-size:x-small; padding:1px 4px;\" data-command=\"".htmlspecialchars($command)."\" onclick=\"".$copy."\">Copy</button>";
	}

function oscrt_server_user() {
	// Name of the user running the web server ('daemon' for XAMPP on Linux)
	if(function_exists("posix_geteuid") AND function_exists("posix_getpwuid")) {
		$info = @posix_getpwuid(posix_geteuid());
		if(is_array($info) AND isset($info['name'])) return $info['name'];
		}
	return trim((string)@exec("id -un"));
	}

function oscrt_robot_start($python_file,&$message) {
	// Start the Python program $python_file of an OSC resource folder, listening to the port of real-time OSC. A program started before is stopped.
	// Its window opens on the desktop of the user running the web server (MAMP and XAMPP on MacOS and Windows, and BolProcessor.app, whose server runs
	// as the user: checked on MacOS 2026-11-24). Paths are relative to the folder of the interface, so no server is assumed. Closing the window stops it.
	$python_file = basename($python_file);
	if(!in_array($python_file,oscrt_python_programs())) {
		$message = "‘".htmlspecialchars($python_file)."’ was not found";
		return FALSE;
		}
	$python = python_status();
	if(!$python['ok']) {
		$message = "Python was not found";
		return FALSE;
		}
	if(!oscrt_local_destination()) {
		$message = "Messages are sent to another computer: start the program there";
		return FALSE;
		}
	oscrt_robot_stop();
	$d = oscrt_destination();
	$script = realpath(resource_path("osc",$python_file));
	$dir = dirname($script);
	$log = dirname(python_status_file()).DIRECTORY_SEPARATOR."osc_robot_log.txt";
	@unlink($log);
	@unlink($log.".err");
	$pid = 0;
	$output = array();
	if(osc_server_windows()) {
		// pythonw.exe (or pyw.exe, the launcher) runs without a console window. Not -WindowStyle Hidden: Windows would apply it to the first window shown, that of the robot.
		$exe = str_replace("/","\\",$python['path']);
		if(python_is_command($exe)) $exe = preg_replace("/(python|py)(3?)(\.exe)?$/i","$1w$3",$exe); // pyw, pythonw: Start-Process finds them in the PATH
		else {
			$windowed = preg_replace("/(python|py)\.exe$/i","$1w.exe",$exe);
			if(is_file($windowed)) $exe = $windowed;
			}
		$arguments = ($python['arguments'] <> '' ? $python['arguments']." " : '')."\"".$script."\" --port ".$d['port'];
		$command = "powershell -NoProfile -Command \"(Start-Process -FilePath '".$exe."' -ArgumentList '".str_replace("'","''",$arguments)."' -WorkingDirectory '".$dir."' -RedirectStandardOutput '".$log."' -RedirectStandardError '".$log.".err' -PassThru).Id\"";
		exec($command,$output);
		}
	else {
		$environment = '';
		if(osc_server_linux()) {
			// 2026-11-24 XAMPP's Apache sets LD_LIBRARY_PATH=/opt/lampp/lib, whose libraries replaced those of the system: tkinter could not be imported
			$environment = "env -u LD_LIBRARY_PATH ";
			if(getenv("DISPLAY") === FALSE) $environment .= "DISPLAY=".oscrt_linux_display()." "; // The web server may have no display
			}
		// 2026-11-28 On MacOS, nohup may fail with "can't detach from console: No such process" (Apache started by MAMP after a stop): the robot is started in its own session, as the watchdog is (watchdog_start())
		$detach = osc_server_mac() ? "/usr/bin/perl -MPOSIX -e 'POSIX::setsid(); exec @ARGV' " : "nohup ";
		exec("cd ".escapeshellarg($dir)."; ".$detach.$environment.escapeshellarg($python['path'])." ".escapeshellarg($script)." --port ".intval($d['port'])." > ".escapeshellarg($log)." 2>&1 < /dev/null & echo $!",$output);
		}
	$pid = intval(trim(implode('',$output)));
	// It fails at once if the port is used or the window can't be opened
	$alive = FALSE;
	for($i = 0; $i < 8; $i++) {
		usleep(250000);
		$alive = ($pid > 0 AND oscrt_pid_alive($pid));
		if(!$alive) break;
		}
	if(!$alive) {
		$said = trim((string)@file_get_contents($log)."\n".(string)@file_get_contents($log.".err"));
		$message = "‘".htmlspecialchars($python_file)."’ did not start";
		$report = ($said <> '') ? nl2br(htmlspecialchars(implode("\n",array_slice(preg_split("/\R/",$said),-4)))) : '';
		// On Linux, the web server (user 'daemon' in XAMPP) may not open a window on the desktop of the user unless the user allows it (xhost)
		$server_user = osc_server_linux() ? oscrt_server_user() : '';
		$display_refused = ($server_user <> '' AND preg_match("/display|Authorization required/i",$said));
		// 2026-11-24 When the cause is known, the explanation comes first and Python's report is hidden under "Technical details"
		if($report <> '' AND !$display_refused) $message .= ": <span class=\"red-text\">".$report."</span>";
		if(osc_server_linux()) {
			if($display_refused) {
				$message .= ". The web server (user ‘".htmlspecialchars($server_user)."’) is not allowed to open windows on your desktop. To allow it at every login, type once in a terminal (without sudo):<br />".oscrt_command_html("mkdir -p ~/.config/autostart && printf '[Desktop Entry]\\nType=Application\\nName=Bol Processor robots\\nExec=xhost +SI:localuser:".$server_user."\\n' > ~/.config/autostart/bolprocessor-xhost.desktop");
				$message .= "<br />then log out and in again (or type ".oscrt_command_html("xhost +SI:localuser:".$server_user)." to allow it right away, until you log out), and click START THE ROBOT again.";
				$message .= "<br />Or start it";
				}
			else $message .= "<br />Start it";
			$message .= " in a terminal: ".oscrt_command_html("cd ".$dir."; python3 ".$python_file);
			if($display_refused AND $report <> '') $message .= "<details style=\"font-size:small; color:gray;\"><summary style=\"cursor:pointer;\">Technical details</summary>".$report."</details>";
			}
		return FALSE;
		}
	@file_put_contents(oscrt_robot_file(),json_encode(array('pid' => $pid,'file' => $python_file,'port' => $d['port'])));
	$message = "‘".htmlspecialchars($python_file)."’ is running: its window listens to port ".$d['port'].". Close it to stop the robot.";
	return TRUE;
	}
?>
