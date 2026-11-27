<?php
// 2026-11-27 AI agent: Claude Code working in the folder "AI_Claude" with the knowledge package (docs-developer/BP3-assistant-project.md) - by Claude
// The user needs Claude Code and a Claude subscription. The interface only finds Claude Code, checks that it is signed in, and opens a terminal
// running it: the dialogue takes place in that terminal (MacOS: Terminal, Windows: PowerShell), never in the interface. On Linux, the web server
// (user 'daemon' of XAMPP) can't open windows on the user's desktop, so the command is given to be typed in a terminal.
// Page: ai_agent.php (INSTALL AI AGENT on the home page; Ask my AI agent on grammar and data pages).

define("AI_AGENT_FOLDER","AI_Claude");
define("AI_AGENT_PAGE","https://bolprocessor.org/ai-agent/");
define("AI_AGENT_CHECK_DELAY",86400); // Claude Code is looked for again after a day
define("AI_AGENT_MAX_REQUEST",4000);
// Terminal window opened by Ask my AI agent: font size (MacOS; Terminal's default is 11) and size in characters
define("AI_TERMINAL_FONT_SIZE",15);
define("AI_TERMINAL_COLUMNS",120);
define("AI_TERMINAL_ROWS",40);

function ai_root() {
	// Root of the installation (folder containing "php"), absolute
	return dirname(__DIR__);
	}

function ai_temp_dir() {
	$dir = ai_root().DIRECTORY_SEPARATOR."temp_bolprocessor".DIRECTORY_SEPARATOR."ai_agent";
	if(!is_dir($dir)) @mkdir($dir,0775,TRUE);
	return $dir;
	}

function ai_status_file() {
	return ai_root().DIRECTORY_SEPARATOR."temp_bolprocessor".DIRECTORY_SEPARATOR."ai_agent.json";
	}

function ai_agent_folder() {
	return ai_root().DIRECTORY_SEPARATOR.AI_AGENT_FOLDER;
	}

function ai_home() {
	// Home folder of the user running the web server (MAMP, XAMPP on Windows and BolProcessor.app run as the user)
	if(osc_server_windows()) {
		$home = getenv("USERPROFILE");
		if($home === FALSE OR $home == '') {
			$user = getenv("USERNAME");
			$home = ($user !== FALSE AND $user <> '') ? "C:\\Users\\".$user : '';
			}
		return $home;
		}
	$home = getenv("HOME");
	if(($home === FALSE OR $home == '') AND function_exists("posix_getpwuid") AND function_exists("posix_geteuid")) {
		$info = @posix_getpwuid(posix_geteuid());
		if(is_array($info) AND isset($info['dir'])) $home = $info['dir'];
		}
	return ($home === FALSE) ? '' : $home;
	}

function ai_environment($claude = '') {
	// Environment of Claude Code: the web server's may lack HOME (where Claude Code keeps its sign-in) and the folders of the PATH
	$environment = array();
	$home = ai_home();
	if(osc_server_windows()) {
		if($home <> '') $environment['USERPROFILE'] = $home;
		}
	else {
		if($home <> '') $environment['HOME'] = $home;
		$path = getenv("PATH");
		$folders = array("/usr/local/bin","/opt/homebrew/bin","/usr/bin","/bin","/usr/sbin","/sbin");
		if($home <> '') array_unshift($folders,$home."/.local/bin");
		if($claude <> '' AND strpbrk($claude,"/") !== FALSE) array_unshift($folders,dirname($claude));
		$environment['PATH'] = implode(":",array_unique(array_merge($folders,($path === FALSE OR $path == '') ? array() : explode(":",$path))));
		if(osc_server_linux()) $environment['LD_LIBRARY_PATH'] = ''; // XAMPP's libraries must not replace those of the system (see oscrt_robot_start())
		}
	return $environment;
	}

function ai_claude_candidates() {
	// Places where the installers of Claude Code put it, then the commands of the PATH. A path typed by the user comes first.
	global $ai_claude_path;
	$list = array();
	if(isset($ai_claude_path) AND $ai_claude_path <> '') $list[] = $ai_claude_path;
	$home = ai_home();
	if(osc_server_windows()) {
		if($home <> '') {
			$list[] = $home."\\.local\\bin\\claude.exe";
			$list[] = $home."\\AppData\\Roaming\\npm\\claude.cmd";
			}
		foreach(glob("C:/Users/*/.local/bin/claude.exe") ?: array() as $p) $list[] = str_replace("/","\\",$p);
		foreach(glob("C:/Users/*/AppData/Roaming/npm/claude.cmd") ?: array() as $p) $list[] = str_replace("/","\\",$p);
		$list[] = "claude";
		}
	else {
		if($home <> '') {
			$list[] = $home."/.local/bin/claude";
			$list[] = $home."/.claude/local/claude";
			$list[] = $home."/.npm-global/bin/claude";
			}
		foreach(array("/usr/local/bin/claude","/opt/homebrew/bin/claude") as $p) $list[] = $p;
		if(osc_server_linux()) foreach(glob("/home/*/.local/bin/claude") ?: array() as $p) $list[] = $p;
		$list[] = "claude";
		}
	return array_values(array_unique($list));
	}

function ai_claude_probe($path) {
	// Version of Claude Code at $path (a file, or a command of the PATH), or FALSE
	if(strpbrk($path,"/\\") !== FALSE AND !is_file($path)) return FALSE;
	$output = scrt_run(array($path,"--version"),20,ai_environment($path));
	if(preg_match("/([0-9]+\.[0-9]+\.[0-9]+)\s*\(Claude Code\)/",$output,$match)) return $match[1];
	return FALSE;
	}

function ai_claude_auth($path) {
	// Sign-in of Claude Code: 'yes', 'no' or 'unknown' (e.g. the web server can't read the user's credentials), with the plan if known
	$output = scrt_run(array($path,"auth","status"),20,ai_environment($path));
	$json = @json_decode(trim(substr($output,(int)strpos($output,"{"))),TRUE);
	if(!is_array($json) OR !isset($json['loggedIn'])) return array('state' => 'unknown','plan' => '','method' => '');
	// On MacOS the sign-in is kept in the Keychain, which the web server can't read: Claude Code then answers that it is not signed in
	// even when it is (checked with MAMP 2026-11-27). Its credentials file is used on Windows and Linux.
	if(!$json['loggedIn'] AND osc_server_mac()) return array('state' => 'unknown','plan' => '','method' => '');
	// On Linux, XAMPP's Apache runs as 'daemon': a Claude Code found in /home/USER is then asked with the home of 'daemon', whose answer says
	// nothing about the user's sign-in
	if(!$json['loggedIn'] AND ai_user_home($path) <> ai_home()) return array('state' => 'unknown','plan' => '','method' => '');
	return array('state' => $json['loggedIn'] ? 'yes' : 'no','plan' => (string)($json['subscriptionType'] ?? ''),'method' => (string)($json['authMethod'] ?? ''));
	}

function ai_agent_status($refresh = FALSE) {
	// State of the AI agent, remembered in temp_bolprocessor/ai_agent.json: 'found' (Claude Code), 'path', 'version', 'auth', 'plan', 'date',
	// 'manual' (Linux: the user says Claude Code works in a terminal). Looked for again when $refresh or after AI_AGENT_CHECK_DELAY.
	$file = ai_status_file();
	$saved = @json_decode((string)@file_get_contents($file),TRUE);
	if(!$refresh AND is_array($saved) AND isset($saved['date']) AND (time() - intval($saved['date'])) < AI_AGENT_CHECK_DELAY) return $saved;
	$status = array('found' => FALSE,'path' => '','version' => '','auth' => 'unknown','plan' => '','date' => time(),'manual' => FALSE);
	foreach(ai_claude_candidates() as $path) {
		$version = ai_claude_probe($path);
		if($version === FALSE) continue;
		$auth = ai_claude_auth($path);
		$status = array('found' => TRUE,'path' => $path,'version' => $version,'auth' => $auth['state'],'plan' => $auth['plan'],'date' => time(),'manual' => FALSE);
		break;
		}
	if(!$status['found'] AND is_array($saved) AND !empty($saved['manual'])) $status['manual'] = TRUE; // Linux: kept until Claude Code is found
	@file_put_contents($file,json_encode($status));
	return $status;
	}

function ai_agent_ready() {
	// TRUE if Ask my AI agent can be offered: Claude Code was found (or declared working on Linux) and the package is installed. Reads only the saved state.
	if(!is_file(ai_agent_folder().DIRECTORY_SEPARATOR."CLAUDE.md")) return FALSE;
	$saved = @json_decode((string)@file_get_contents(ai_status_file()),TRUE);
	return (is_array($saved) AND (!empty($saved['found']) OR !empty($saved['manual'])));
	}

function ai_user_home($claude = '') {
	// Home folder of the user of Claude Code: that of the web server, except on Linux for a Claude Code found in /home/USER (XAMPP's Apache
	// runs as 'daemon'). $claude: its path, by default the one found.
	if(!osc_server_linux()) return ai_home();
	if($claude == '') {
		$saved = @json_decode((string)@file_get_contents(ai_status_file()),TRUE);
		$claude = is_array($saved) ? (string)($saved['path'] ?? '') : '';
		}
	if(preg_match("#^(/home/[^/]+)/#",$claude,$match)) return $match[1];
	return ai_home();
	}

function ai_folder_trusted() {
	// TRUE if Claude Code trusts the folder AI_Claude (answer "Yes, I trust this folder" at its first start, recorded in ~/.claude.json for this
	// folder or a folder above it), FALSE if not, NULL if its configuration can't be read (e.g. by 'daemon' on Linux). Read only.
	$home = ai_user_home();
	if($home == '') return NULL;
	$config = @json_decode((string)@file_get_contents($home.DIRECTORY_SEPARATOR.".claude.json"),TRUE);
	if(!is_array($config)) return NULL;
	$normal = function($path) {
		$path = rtrim(str_replace("\\","/",$path),"/");
		return osc_server_windows() ? strtolower($path) : $path;
		};
	$folder = $normal(realpath(ai_agent_folder()) ?: ai_agent_folder());
	foreach((array)($config['projects'] ?? array()) as $path => $settings) {
		if(empty($settings['hasTrustDialogAccepted'])) continue;
		$path = $normal($path);
		if($path <> '' AND ($folder == $path OR strpos($folder,$path."/") === 0)) return TRUE;
		}
	return FALSE;
	}

function ai_install_command() {
	// Official installation command of Claude Code for the system of the browser (https://code.claude.com/docs/en/setup)
	if(windows_system()) return "irm https://claude.ai/install.ps1 | iex";
	return "curl -fsSL https://claude.ai/install.sh | bash";
	}

function ai_project_path($project) {
	// Path of a grammar or data project relative to the root ("ctests/-gr.Mozart"), checked, or ''
	$project = str_replace("\\","/",trim((string)$project));
	if($project == '' OR strpos($project,"..") !== FALSE OR $project[0] == "/") return '';
	if(!preg_match("/(^|\/)(-gr\.|-da\.)[^\/]+$|\.(bpgr|bpda)$/",$project)) return '';
	if(!is_file(ai_root().DIRECTORY_SEPARATOR.str_replace("/",DIRECTORY_SEPARATOR,$project))) return '';
	return $project;
	}

function ai_prompt($request,$project) {
	// First message given to Claude Code, as typed by the user, followed by the project of the page (relative to AI_Claude)
	$request = trim(preg_replace("/\s*\R\s*/"," ",(string)$request));
	if(mb_strlen($request) > AI_AGENT_MAX_REQUEST) $request = mb_substr($request,0,AI_AGENT_MAX_REQUEST);
	if($project <> '') {
		if($request == '') $request = "Read this project and wait for my request.";
		$request .= "  [Project: ../".$project."]";
		}
	return $request;
	}

function ai_shell_quote($text) {
	// Single quotes for bash and zsh
	return "'".str_replace("'","'\\''",$text)."'";
	}

// The request comes after "--": "--add-dir" accepts several folders, and took the request for one of them (2026-11-27)
function ai_terminal_command($prompt) {
	// The command typed in a terminal (Linux, or when no terminal could be opened)
	$folder = ai_agent_folder();
	if(osc_server_windows()) {
		$status = ai_agent_status();
		$claude = ($status['found'] AND strpbrk($status['path'],"\\/") !== FALSE) ? "& '".str_replace("'","''",$status['path'])."'" : "claude";
		$command = "cd \"".$folder."\"; ".$claude." --add-dir ..";
		if($prompt <> '') $command .= " -- '".str_replace("'","''",str_replace("\"","\\\"",$prompt))."'";
		return $command;
		}
	$command = "cd ".ai_shell_quote($folder)." && claude --add-dir ..";
	if($prompt <> '') $command .= " -- ".ai_shell_quote($prompt);
	return $command;
	}

function ai_cleanup() {
	// Scripts and requests of former sessions (more than a day old)
	foreach(glob(ai_temp_dir().DIRECTORY_SEPARATOR."ask_*") ?: array() as $file)
		if(time() - (int)@filemtime($file) > 86400) @unlink($file);
	}

function ai_open_terminal($prompt,&$message) {
	// Open a terminal running Claude Code in AI_Claude with the first message $prompt ('' for none, e.g. to sign in). Returns TRUE if done.
	// The message is read from a UTF-8 file by the script, so that it needs no quoting.
	$status = ai_agent_status();
	$folder = ai_agent_folder();
	if(!is_dir($folder)) {
		$message = "The folder ‘".AI_AGENT_FOLDER."’ was not found: install the latest version of the interface";
		return FALSE;
		}
	if(osc_server_linux() OR !$status['found']) {
		$message = "Open a terminal, paste this command (button Copy) and press Return:<br />".oscrt_command_html(ai_terminal_command($prompt))."<br />The agent answers in the terminal.";
		return FALSE;
		}
	ai_cleanup();
	$script = ai_write_script($status['path'],$prompt,$message);
	if($script === FALSE) return FALSE;
	$output = array();
	$result = 1;
	if(osc_server_windows()) {
		// The command starting the window is given encoded (UTF-16LE, base64), so that no quoting of paths is needed on the command line
		$q = function($text) { return "'".str_replace("'","''",$text)."'"; };
		$start = "Start-Process powershell -ArgumentList ('-NoExit -NoProfile -ExecutionPolicy Bypass -File \"' + ".$q($script)." + '\"') -WorkingDirectory ".$q($folder);
		$encoded = base64_encode(mb_convert_encoding($start,"UTF-16LE","UTF-8"));
		exec("powershell -NoProfile -EncodedCommand ".$encoded." 2>&1",$output,$result);
		}
	// MacOS: a ".command" script opened in Terminal by LaunchServices ('open'), which needs no permission to control Terminal (osascript would)
	else exec("open -a Terminal ".escapeshellarg($script)." 2>&1",$output,$result);
	if($result <> 0) {
		$message = "The terminal window could not be opened. Open a terminal yourself, paste this command (button Copy) and press Return:<br />".oscrt_command_html(ai_terminal_command($prompt));
		$message .= "<details style=\"font-size:small; color:gray;\"><summary style=\"cursor:pointer;\">Technical details</summary>".htmlspecialchars(trim(implode(" ",$output)))."</details>";
		return FALSE;
		}
	$window = osc_server_windows() ? "PowerShell" : "Terminal";
	if($prompt == '') $message = "<b>Claude Code has opened in a new ".$window." window</b> (it may be behind this one). Follow the steps listed below in that window, then come back here and click CHECK AGAIN.";
	else $message = ai_terminal_help($window);
	$message .= "<details style=\"font-size:small;\"><summary style=\"cursor:pointer;\">No window appeared?</summary>Open a terminal yourself, paste this command (button Copy) and press Return:<br />".oscrt_command_html(ai_terminal_command($prompt))."</details>";
	return TRUE;
	}

function ai_write_script($claude,$prompt,&$message) {
	// Script run in the terminal: Claude Code ($claude) in AI_Claude with the first message $prompt, read from a UTF-8 file so that it needs no quoting.
	// Returns its path (".command" on MacOS, ".ps1" on Windows), or FALSE.
	$folder = ai_agent_folder();
	$id = "ask_".date("Ymd_His")."_".mt_rand(1000,9999);
	$temp = ai_temp_dir();
	$request_file = $temp.DIRECTORY_SEPARATOR.$id.".txt";
	if(@file_put_contents($request_file,$prompt) === FALSE) {
		$message = "Could not write in ‘temp_bolprocessor’";
		return FALSE;
		}
	if(osc_server_windows()) {
		// PowerShell window (-NoExit: it stays open when Claude Code ends). Windows PowerShell 5.1 and PowerShell 7.0-7.2 pass arguments to programs
		// the legacy way, which removes the double quotes inside an argument unless they are escaped with a backslash.
		$script = $temp.DIRECTORY_SEPARATOR.$id.".ps1";
		$q = function($text) { return "'".str_replace("'","''",$text)."'"; };
		$template = <<<'PS1'
$host.UI.RawUI.WindowTitle = 'Bol Processor - AI agent (Claude Code)'
try {
	$ui = $host.UI.RawUI
	$max = $ui.MaxPhysicalWindowSize
	$buffer = $ui.BufferSize
	if($buffer.Width -lt COLUMNS) { $buffer.Width = COLUMNS; $ui.BufferSize = $buffer }
	$size = $ui.WindowSize
	$size.Width = [Math]::Min(COLUMNS,$max.Width)
	$size.Height = [Math]::Min(ROWS,$max.Height)
	$ui.WindowSize = $size
	} catch {}
Set-Location -LiteralPath FOLDER
Write-Host ''
Write-Host ' Bol Processor - AI agent (Claude Code)' -ForegroundColor Cyan
Write-Host ' - Click in this window before typing.' -ForegroundColor Cyan
Write-Host ' - To answer a question of Claude Code: choose with the arrow keys (up, down), then press Enter.' -ForegroundColor Cyan
Write-Host ' - The first time, it asks whether you trust this folder: press the down arrow to choose "Yes, I trust this folder", then Enter.' -ForegroundColor Cyan
Write-Host ' - When you have finished, type /exit and press Enter, then reload the page of the project in the browser.' -ForegroundColor Cyan
Write-Host ''
$prompt = [System.IO.File]::ReadAllText(REQUEST,[System.Text.Encoding]::UTF8)
$claude = CLAUDE
if(($prompt -ne '') -and (($PSNativeCommandArgumentPassing -eq $null) -or ($PSNativeCommandArgumentPassing -eq 'Legacy') -or ($claude -like '*.cmd'))) {
	$prompt = $prompt -replace '(\\*)"','$1$1\"'
	}
if($prompt -ne '') { & $claude --add-dir .. -- $prompt } else { & $claude --add-dir .. }
PS1;
		$text = str_replace(array("FOLDER","REQUEST","CLAUDE","COLUMNS","ROWS"),array($q($folder),$q($request_file),$q($claude),AI_TERMINAL_COLUMNS,AI_TERMINAL_ROWS),$template);
		// UTF-8 with a byte order mark, so that Windows PowerShell 5.1 reads paths with accents correctly
		if(@file_put_contents($script,"\xEF\xBB\xBF".str_replace("\n","\r\n",$text)."\r\n") === FALSE) {
			$message = "Could not write in ‘temp_bolprocessor’";
			return FALSE;
			}
		return $script;
		}
	else {
		$script = $temp.DIRECTORY_SEPARATOR.$id.".command";
		$lines = array("#!/bin/bash");
		// No environment is set: Terminal gives that of the user, which the commands run by Claude Code need (the web server's PATH is minimal)
		$lines[] = "cd ".ai_shell_quote($folder)." || exit 1";
		// Larger text (font size of this window only, found by its tty; nothing happens if MacOS refuses) and a larger window (escape sequence)
		$lines[] = "osascript -e 'set myTTY to \"'\"$(tty)\"'\"' -e 'tell application \"Terminal\"' -e 'repeat with w in windows' -e 'repeat with t in tabs of w' -e 'if tty of t is myTTY then set font size of t to ".AI_TERMINAL_FONT_SIZE."' -e 'end repeat' -e 'end repeat' -e 'end tell' > /dev/null 2>&1";
		$lines[] = "printf '\\033[8;".AI_TERMINAL_ROWS.";".AI_TERMINAL_COLUMNS."t'";
		$lines[] = "clear";
		$lines[] = "printf '\\033]0;Bol Processor - AI agent (Claude Code)\\007'";
		$lines[] = "printf '\\n\\033[36m Bol Processor - AI agent (Claude Code)\\n - To answer a question of Claude Code: choose with the arrow keys (up, down), then press Return.\\n - The first time, it may ask whether you trust this folder: press the down arrow to choose \"Yes, I trust this folder\", then Return.\\n - When you have finished, type /exit and press Return, then reload the page of the project in the browser.\\033[0m\\n\\n'";
		$lines[] = "prompt=$(cat ".ai_shell_quote($request_file).")";
		$lines[] = "if [ -n \"\$prompt\" ]; then ".ai_shell_quote($claude)." --add-dir .. -- \"\$prompt\"; else ".ai_shell_quote($claude)." --add-dir ..; fi";
		if(@file_put_contents($script,implode("\n",$lines)."\n") === FALSE) {
			$message = "Could not write in ‘temp_bolprocessor’";
			return FALSE;
			}
		@chmod($script,0755);
		return $script;
		}
	}

function ai_terminal_help($window) {
	// What to do in the terminal window, for musicians
	$help = "<b>The agent is working in a new ".$window." window.</b> Look at that window (it may be behind this one):<ul>";
	if(osc_server_windows()) $help .= "<li><b>click in that window</b> before typing: Windows may open it without the keyboard;</li>";
	$help .= "<li>the agent writes its answers and what it is doing there; when it has finished, <b>scroll up</b> in that window to read its whole answer;</li>";
	$help .= "<li>to enlarge or reduce the text: ".(osc_server_windows() ? "<b>Ctrl</b> and the mouse wheel" : "<b>⌘ +</b> and <b>⌘ −</b>").";</li>";
	$help .= "<li>when it asks a question or for your permission, choose the answer with the <b>arrow keys</b> (↑ ↓) and press ".(osc_server_windows() ? "Enter" : "Return")."; to a question in words, type your answer;</li>";
	$help .= "<li>you can go on asking there, in your own words;</li>";
	$help .= "<li>when you have finished, type <b>/exit</b> and press Return, then close the window.</li></ul>";
	$help .= "Then <b>reload the page of the project</b> in the browser to see and play the changes. Reload it <i>before</i> clicking its SAVE button, otherwise SAVE would put back the former version.";
	$help .= "<br /><small>Lines such as offers of Anthropic or messages about settings at the top of the window come from Claude Code itself: ignore them.</small>";
	return $help;
	}

function ai_folder_has_projects() {
	// TRUE if the folder AI_Claude holds projects (files of the Bol Processor, or folders), not only the knowledge package of the agent
	foreach(scandir(ai_agent_folder()) ?: array() as $name) {
		if($name[0] == "." OR $name == "CLAUDE.md" OR $name == "CLAUDE.local.md" OR $name == "my_changes") continue;
		return TRUE;
		}
	return FALSE;
	}

function ai_ask_button($file) {
	// Ask my AI agent button of a grammar or data page ($file relative to the root, as in the URL of the page), shown once the agent is ready
	if(!ai_agent_ready()) return;
	$project = ai_project_path(str_replace(SLASH,"/",$file));
	if($project == '') return;
	$link = "ai_agent.php?ask=".urlencode($project);
	echo "<button class=\"edit big\" title=\"Ask the AI agent (Claude Code) to change or explain this project\" onclick=\"if(checksaved()) window.open('".$link."','ai_agent','width=700,height=600,left=150'); return false;\">🤖 Ask my AI agent</button>";
	return;
	}

function ai_home_box() {
	// Home page: link to the page of the website and INSTALL AI AGENT (or the state of the agent once it is installed)
	echo "<p>🤖 <a class=\"linkdotted\" target=\"_blank\" href=\"".AI_AGENT_PAGE."\">An AI agent for the Bol Processor</a>";
	if(!is_dir(ai_agent_folder())) {
		echo " (the folder ‘".AI_AGENT_FOLDER."’ is missing: install the latest version of the interface)</p>";
		return;
		}
	$status = ai_agent_status();
	if($status['found'] OR $status['manual']) {
		echo " is ready. To use it, open a grammar or data project: a button <span style=\"white-space:nowrap;\"><b>🤖 Ask my AI agent</b></span> is shown below its title.";
		echo "&nbsp;<button class=\"edit\" title=\"Claude Code ".htmlspecialchars($status['version'])."\" onclick=\"window.open('ai_agent.php','ai_agent','width=700,height=650,left=150'); return false;\">SET UP</button>";
		}
	else echo "&nbsp;<button class=\"save big\" onclick=\"window.open('ai_agent.php','ai_agent','width=700,height=650,left=150'); return false;\">INSTALL AI AGENT</button>";
	echo "</p>";
	return;
	}
?>
