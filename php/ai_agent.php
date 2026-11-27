<?php
// 2026-11-27 AI agent (see _ai_agent.php) - by Claude
// Without 'ask': installation page (INSTALL AI AGENT on the home page): Claude Code found?, signed in?, first start in a terminal.
// With 'ask=PROJECT' (path from the root, e.g. ctests/-gr.Mozart): request box of the Ask my AI agent button of grammar and data pages.
// POST 'action': "check" (look for Claude Code again), "path" (with 'path' typed by the user), "start" (open Claude Code in a terminal, to sign in
// and accept the folder), "manual" (Linux: Claude Code works in a terminal), "ask" (with 'request': open Claude Code with the request).
require_once("_basic_tasks.php");
$this_title = "AI agent";
require_once("_header.php");

$project = ai_project_path((string)($_GET['ask'] ?? ''));
$action = (string)($_POST['action'] ?? '');
$message = '';
$url_this_page = "ai_agent.php".($project <> '' ? "?ask=".urlencode($project) : '');

if($action == "check") ai_agent_status(TRUE);
else if($action == "path") {
	$path = trim(str_replace('"','',(string)($_POST['path'] ?? '')));
	if($path <> '' AND ai_claude_probe($path) !== FALSE) {
		save_settings("ai_claude_path",$path);
		$ai_claude_path = $path;
		ai_agent_status(TRUE);
		}
	else $message = "<span class=\"red-text\">‘".htmlspecialchars($path)."’ did not answer as Claude Code</span>";
	}
else if($action == "manual") {
	$status = ai_agent_status();
	$status['manual'] = TRUE;
	@file_put_contents(ai_status_file(),json_encode($status));
	}
else if($action == "start") ai_open_terminal('',$message);
else if($action == "ask" AND $project <> '') ai_open_terminal(ai_prompt((string)($_POST['request'] ?? ''),$project),$message);

$status = ai_agent_status();

echo "<h2>🤖 AI agent for the Bol Processor</h2>";
if($message <> '') echo "<p class=\"thinborder\" style=\"padding:0.5em;\">".$message."</p>";

if($project <> '') {
	// Request box of Ask my AI agent
	echo "<p>Project <span class=\"green-text\"><b>".htmlspecialchars(basename($project))."</b></span> (folder ‘".htmlspecialchars(dirname($project))."’)</p>";
	if(!$status['found'] AND !$status['manual']) {
		echo "<p class=\"red-text\">Claude Code was not found: <a href=\"ai_agent.php\">install the AI agent</a> first.</p>";
		}
	else if($action <> "ask") {
		echo "<form method=\"post\" action=\"".$url_this_page."\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"ask\">";
		echo "<p>Type your request in your own words, then click START. For example:<br />“Explain how this grammar works”<br />“Make the ending fade out”<br />“Add a flute part”<br />“Why does it play nothing?”</p>";
		echo "<textarea name=\"request\" rows=\"6\" style=\"width:95%;\" maxlength=\"".AI_AGENT_MAX_REQUEST."\" autofocus></textarea>";
		echo "<p><input class=\"save big\" type=\"submit\" value=\"START\"></p>";
		echo "</form>";
		echo "<p><small>The agent answers in a new window of the terminal, where you can go on talking with it. Before changing a file, it keeps its former version as ‘_bak’.</small></p>";
		}
	echo "</body></html>";
	die();
	}

// Installation page
echo "<p>The AI agent is <b>Claude Code</b>, the program of Anthropic, working in the folder ‘".AI_AGENT_FOLDER."’ with a knowledge package about the Bol Processor. It creates, improves, explains and fixes projects, and tests them with the console. It needs a <b>Claude subscription</b> (Pro or Max), paid to Anthropic. Read <a class=\"linkdotted\" target=\"_blank\" href=\"".AI_AGENT_PAGE."\">An AI agent for the Bol Processor</a>.</p>";

// 1. Claude Code
echo "<h3>1. Claude Code</h3>";
if($status['found']) {
	echo "<p>✅ Claude Code ".htmlspecialchars($status['version'])." was found: <span style=\"font-family:monospace;\">".htmlspecialchars($status['path'])."</span></p>";
	}
else {
	if($status['manual']) echo "<p>✅ You said that Claude Code works in a terminal.</p>";
	else echo "<p>❌ Claude Code was not found.</p>";
	echo "<p>To install it, type in a terminal".(windows_system() ? " (<b>PowerShell</b>)" : '').":<br />".oscrt_command_html(ai_install_command())."</p>";
	if(windows_system()) echo "<p><small>The installer may say that a folder ‘.local\\bin’ is not in your PATH, and that a temporary file could not be removed: ignore both, the Bol Processor finds Claude Code without them.</small></p>";
	echo "<p><small>See also <a class=\"linkdotted\" target=\"_blank\" href=\"https://code.claude.com/docs/en/setup\">Anthropic's instructions</a>.</small></p>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" style=\"display:inline;\"><input type=\"hidden\" name=\"action\" value=\"check\"><input class=\"edit\" type=\"submit\" value=\"CHECK AGAIN\"></form>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" style=\"margin-top:0.5em;\"><input type=\"hidden\" name=\"action\" value=\"path\"><small>Installed elsewhere? Its path:</small> <input type=\"text\" name=\"path\" size=\"40\"> <input class=\"edit\" type=\"submit\" value=\"TRY\"></form>";
	if(osc_server_linux() AND !$status['manual']) {
		echo "<p><small>On Linux, the web server may not see the programs of your account. If <span style=\"font-family:monospace;\">claude --version</span> works in your terminal, click:</small> ";
		echo "<form method=\"post\" action=\"".$url_this_page."\" style=\"display:inline;\"><input type=\"hidden\" name=\"action\" value=\"manual\"><input class=\"edit\" type=\"submit\" value=\"CLAUDE CODE WORKS IN MY TERMINAL\"></form></p>";
		}
	}

// 2. Sign-in and first start
if($status['found'] OR $status['manual']) {
	echo "<h3>2. Sign in and first start</h3>";
	if($status['auth'] == "yes") echo "<p>✅ Claude Code is signed in".($status['plan'] <> '' ? " (".htmlspecialchars(ucfirst($status['plan'])).")" : '').".</p>";
	else if($status['auth'] == "no") echo "<p>❌ Claude Code is not signed in.</p>";
	$trusted = ai_folder_trusted();
	if($trusted === TRUE) echo "<p>✅ Claude Code trusts the folder ‘".AI_AGENT_FOLDER."’: the first start is done.</p>";
	else if($trusted === FALSE) echo "<p>❌ The first start is not done yet: Claude Code doesn't trust the folder ‘".AI_AGENT_FOLDER."’.</p>";
	$enter = osc_server_windows() ? "Enter" : "Return";
	if($trusted === TRUE) echo "<details><summary style=\"cursor:pointer;\">Start Claude Code again (e.g. to sign in again)</summary>";
	echo "<p>Click the button below: Claude Code opens in a new ".(osc_server_windows() ? "PowerShell" : "Terminal")." window. Then, in that window:</p><ol>";
	if(osc_server_windows()) echo "<li><b>click in the window</b> before typing: Windows may open it without the keyboard;</li>";
	echo "<li>the first time, it asks you to choose a <b>text style</b>: press <b>".$enter."</b>;</li>";
	if($status['auth'] <> "yes") echo "<li>it asks you to <b>sign in</b>: choose your Claude account (subscription) with the arrow keys and press <b>".$enter."</b>; your browser opens Anthropic's page, where you sign in and accept; then come back to the window. The Bol Processor never sees your account or password;</li>";
	echo "<li>it asks whether you <b>trust this folder</b>: press the <b>↓</b> key to choose <b>Yes, I trust this folder</b>, then <b>".$enter."</b>. (".$enter." alone would choose “No, exit”, and Claude Code would quit: then click the button again.) This lets the agent use the tools prepared for it without asking you each time;</li>";
	echo "<li>when Claude Code waits for a request, type <b>/exit</b> and press <b>".$enter."</b>, then click CHECK AGAIN below.</li></ol>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" style=\"display:inline;\"><input type=\"hidden\" name=\"action\" value=\"start\"><input class=\"save big\" type=\"submit\" value=\"OPEN CLAUDE CODE IN A TERMINAL\"></form> ";
	echo "<form method=\"post\" action=\"".$url_this_page."\" style=\"display:inline;\"><input type=\"hidden\" name=\"action\" value=\"check\"><input class=\"edit\" type=\"submit\" value=\"CHECK AGAIN\"></form>";
	if($trusted === TRUE) echo "</details>";
	echo "<h3>3. Use</h3>";
	echo "<p>Open a grammar or data project: below its title, click the button <b>🤖 Ask my AI agent</b> and type your request. To ask for a new piece, do the same from any project and say so: the agent saves the projects it creates in the folder ‘".AI_AGENT_FOLDER."’, shown on the home page once it contains some.</p>";
	echo "<p><small>Changes of the program (PHP or C) asked of the agent are erased by the updates of the Bol Processor. The agent records them in ‘".AI_AGENT_FOLDER."/my_changes’: send your ideas to the author if they could be useful to everyone.</small></p>";
	}
echo "</body></html>";
?>
