<?php
// Real-time Csound (2026-11-16)
// The console, with option "--csoundrt port", sends the lines of the Csound score to a Csound server listening to this UDP port.
// These functions start, check and stop that server. They are used by produce.php, the project pages and csoundrt_quit.php.
// The server runs: csound -odac -d -m0 --port=CSOUND_RT_PORT --orc <orchestra>
// It keeps running between performances, so that they start at once. It is restarted when the orchestra changes,
// and it quits after CSOUND_RT_IDLE seconds without a performance.

if(!defined("CSOUND_RT_PORT")) {
	define("CSOUND_RT_PORT",10789);
	define("CSOUND_RT_IDLE",900); // seconds
	}

function csoundrt_files() {
	// Files of the server in the temp_bolprocessor folder (absolute paths, since Csound needs them)
	$dir = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor");
	if($dir === FALSE) $dir = dirname(__DIR__).DIRECTORY_SEPARATOR."temp_bolprocessor";
	$dir .= DIRECTORY_SEPARATOR;
	return array('state' => $dir."csoundrt_server.json", 'log' => $dir."csoundrt_log.txt", 'ping' => $dir."csoundrt_ping.txt");
	}

function csoundrt_send($message,$port = CSOUND_RT_PORT) {
	// Send a message to the server: orchestra code, or score lines starting with '$'
	$socket = @stream_socket_client("udp://127.0.0.1:".$port,$errno,$errstr);
	if($socket === FALSE) return FALSE;
	$result = @fwrite($socket,$message);
	fclose($socket);
	return ($result !== FALSE);
	}

function csoundrt_port_in_use($port = CSOUND_RT_PORT) {
	// TRUE if a program, normally Csound, listens to this UDP port
	$context = stream_context_create(['socket' => ['so_reuseaddr' => FALSE]]);
	$socket = @stream_socket_server("udp://0.0.0.0:".$port,$errno,$errstr,STREAM_SERVER_BIND,$context);
	if($socket === FALSE) return TRUE;
	fclose($socket);
	return FALSE;
	}

function csoundrt_ping($timeout = 1.0,$port = CSOUND_RT_PORT) {
	// TRUE if Csound responds: it is asked to append a random token to the "ping" file.
	// The same file is used every time because Csound keeps it open, which prevents deleting it on Windows.
	$files = csoundrt_files();
	$token = "BP_ALIVE_".mt_rand(100000,999999);
	// Csound strings accept '/' on all systems, and "\n" is a Csound escape sequence
	if(!csoundrt_send('fprints "'.str_replace('\\','/',$files['ping']).'", "'.$token.'\n"',$port)) return FALSE;
	$end = microtime(TRUE) + $timeout;
	do {
		usleep(50000);
		clearstatcache();
		$content = @file_get_contents($files['ping']);
		if($content !== FALSE AND strpos($content,$token) !== FALSE) return TRUE;
		}
	while(microtime(TRUE) < $end);
	return FALSE;
	}

function csoundrt_state() {
	// Orchestra, Csound and port of the running server, as saved by csoundrt_start()
	$files = csoundrt_files();
	$state = @json_decode((string)@file_get_contents($files['state']),TRUE);
	return is_array($state) ? $state : array();
	}

function csoundrt_running() {
	// Quick check (no ping): the port is in use and the server was started by the Bol Processor
	return (csoundrt_state() <> array() AND csoundrt_port_in_use());
	}

function csoundrt_quit($port = CSOUND_RT_PORT) {
	// Ask the server to quit, and wait until the port is free. Return TRUE if it is.
	$files = csoundrt_files();
	csoundrt_send("exitnow",$port);
	for($i = 0; $i < 30 AND csoundrt_port_in_use($port); $i++) usleep(100000);
	@unlink($files['state']);
	return !csoundrt_port_in_use($port);
	}

function csoundrt_log_tail($lines = 12) {
	// Last lines of the server's messages, to explain a failure
	$files = csoundrt_files();
	$content = @file_get_contents($files['log']);
	if($content === FALSE OR trim($content) == '') return '';
	$content = preg_replace('/\x1b\[[0-9;]*m/','',$content); // Colour codes
	$table = explode("\n",str_replace("\r",'',trim($content)));
	return implode("\n",array_slice($table,-$lines));
	}

function csoundrt_start($csound,$orchestra,&$message) {
	// Make sure that a server is running with this orchestra, and that it responds. $csound is the path to the Csound program.
	// On Linux, csound_environment() must have been called to set the library paths.
	// $message tells what was done: "running", "restarted", "started", or why it failed.
	$port = CSOUND_RT_PORT;
	$files = csoundrt_files();
	$orchestra_path = realpath($orchestra);
	if($orchestra_path === FALSE) {
		$message = "The orchestra file was not found: ".$orchestra;
		return FALSE;
		}
	$signature = array('orchestra' => $orchestra_path,'mtime' => filemtime($orchestra_path),'csound' => $csound,'port' => $port);
	$state = csoundrt_state();
	$restart = FALSE;
	if(csoundrt_ping(0.5)) {
		if(array_intersect_key($state,$signature) == $signature) {
			$message = "running";
			return TRUE;
			}
		// Another orchestra, or the orchestra has been modified
		$restart = TRUE;
		if(!csoundrt_quit($port)) {
			$message = "The Csound server running on UDP port ".$port." did not quit";
			return FALSE;
			}
		}
	else if(csoundrt_port_in_use($port)) {
		// Csound is not responding, or another program uses the port
		csoundrt_quit($port);
		if(csoundrt_port_in_use($port)) {
			$message = "UDP port ".$port." is used by another program";
			return FALSE;
			}
		}
	// First try with the sample rate of the orchestra. If the audio device does not accept it
	// (e.g. 44100 Hz on a Windows device working at 48000 Hz), try again with the sample rate of the device.
	// Csound then adjusts kr so that ksmps remains the same.
	foreach(array('',' --use-system-sr') as $option) {
		@unlink($files['ping']);
		@unlink($files['state']);
		$command = "\"".$csound."\" -odac".$option." -d -m0 -+msg_color=0 --port=".$port." --orc \"".$orchestra_path."\"";
		if(stripos(PHP_OS,"WIN") === 0) {
			// "start /B" returns at once, leaving Csound running in the background
			pclose(popen("start \"BP3 Csound\" /B ".$command." > \"".$files['log']."\" 2>&1","r"));
			}
		else exec("nohup ".$command." > \"".$files['log']."\" 2>&1 < /dev/null &");
		// Csound compiles the orchestra and opens the audio output before responding
		$end = microtime(TRUE) + 10.;
		$stopped = FALSE;
		while(microtime(TRUE) < $end) {
			if(csoundrt_ping(0.5)) {
				file_put_contents($files['state'],json_encode($signature));
				$message = $restart ? "restarted" : "started";
				if($option <> '') $message .= " at the sample rate of the audio device, which does not accept the one of the orchestra,";
				return TRUE;
				}
			// No need to wait if Csound has given up
			$log = (string)@file_get_contents($files['log']);
			if(preg_match('/UDP server on port [0-9]+ stopped|Failed to initialise real time audio output/i',$log)) {
				$stopped = TRUE;
				break;
				}
			}
		csoundrt_quit($port);
		if(!$stopped) {
			$message = "Csound did not respond after 10 seconds";
			return FALSE;
			}
		if(!preg_match('/Failed to initialise real time audio output|Invalid sample rate/i',$log)) break; // Not a problem of sample rate
		}
	$message = "Csound could not start";
	return FALSE;
	}

function csound_errors_summary($text) {
	// Errors in Csound's messages showing that notes were not played as expected (2026-11-16)
	// Return an array: 'notes' is the number of notes deleted, 'messages' the distinct errors (numbers ignored) with their count
	$summary = array('notes' => 0,'messages' => array());
	foreach(preg_split('/\R/',(string)$text) as $line) {
		$line = trim(preg_replace('/\x1b\[[0-9;]*m/','',$line));
		if($line == '') continue;
		if(preg_match('/note deleted/i',$line)) {
			$summary['notes']++;
			continue;
			}
		if(!preg_match('/(INIT|PERF) ERROR|error:|not defined|undefined|Invalid ftable|not found/i',$line)) continue;
		$key = preg_replace('/-?[0-9]+(\.[0-9]+)?/','#',$line);
		if(!isset($summary['messages'][$key])) $summary['messages'][$key] = array('example' => $line,'count' => 0);
		$summary['messages'][$key]['count']++;
		}
	return $summary;
	}

function csound_errors_report($summary,$csound_file,$orchestra) {
	// Lines (HTML) explaining the errors found by csound_errors_summary(), or an empty array
	$report = array();
	if($summary['notes'] == 0 AND count($summary['messages']) == 0) return $report;
	if($summary['notes'] > 0) $report[] = "Csound did not play ".$summary['notes']." note".($summary['notes'] > 1 ? "s" : "")." because of errors:";
	else $report[] = "Csound reported errors:";
	$k = 0;
	foreach($summary['messages'] as $message) {
		if(++$k > 5) {
			$report[] = "… and more";
			break;
			}
		$report[] = htmlspecialchars($message['example']).($message['count'] > 1 ? " (".$message['count']." times)" : "");
		}
	$which = ($csound_file <> '') ? "the instruments of ‘".htmlspecialchars($csound_file)."’" : "the default instrument";
	$report[] = "Maybe ".$which." do not match orchestra ‘".htmlspecialchars($orchestra)."’: check that the arguments (p-fields) of each instrument are the ones expected by the orchestra.";
	return $report;
	}

function csoundrt_log_size() {
	// Size of the server's log, to read the messages of the next performance
	$files = csoundrt_files();
	clearstatcache();
	$size = @filesize($files['log']);
	return ($size === FALSE) ? 0 : $size;
	}

function csoundrt_log_since($offset) {
	// Messages of the server written after $offset
	$files = csoundrt_files();
	$content = @file_get_contents($files['log'],FALSE,NULL,$offset);
	return ($content === FALSE) ? '' : $content;
	}

function csoundrt_begin_performance() {
	// Cancel the scheduled quitting of the server (see csoundrt_end_performance)
	return csoundrt_send("instr BP_idle_quit\n exitnow\nendin\ninstr BP_cancel_idle\n turnoff3 nstrnum(\"BP_idle_quit\")\n turnoff\nendin\nschedule \"BP_cancel_idle\", 0, 0.1");
	}

function csoundrt_end_performance() {
	// The server will quit after CSOUND_RT_IDLE seconds unless another performance begins
	return csoundrt_send("schedule \"BP_idle_quit\", ".CSOUND_RT_IDLE.", 0.1");
	}
?>
