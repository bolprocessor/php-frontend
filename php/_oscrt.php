<?php
// Real-time OSC (2026-11-21)
// The console, with option "--osc port[@host]", sends notes as generic OSC messages (/bp/start, /bp/note, /bp/off, /bp/stop, /bp/end)
// at their dates to a program such as Max or Pd listening to this UDP port, see CsoundRealTime.c. Unlike real-time Csound and SuperCollider,
// no server is started: the receiving program is run by the user. Its address is set for each machine with the form of the project pages
// (oscrt_settings.php), stored in _settings.php ($oscrt_host, $oscrt_port). Example receivers are in "osc_resources".

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

function oscrt_panel($sc_file,$csound_file) {
	// Displayed on project pages instead of the name of the output file, when the output is real-time OSC
	// The destination and the messages are shown by the "settings" button of oscrt_format_notes() (2026-11-21)
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
?>
