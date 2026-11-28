#!/bin/sh
# Bol Processor watchdog (2026-11-22)
# Restarts the web server (Apache of MAMP on MacOS, of XAMPP on Linux) if it crashed or is stuck.
# Started by watchdog_start() (_basic_tasks.php) when a page is loaded and no watchdog is running.
# Every minute it loads watchdog_ping.php, which answers "ok":
#  - no answer and Apache's pid file removed: Apache was stopped on purpose, the watchdog quits;
#  - the process of the pid file gone (even if its workers still answer): Apache crashed, it is restarted at once;
#  - no answer from a running Apache not listening on the port of the URL (MAMP): it is not the server of the URL, the watchdog quits;
#  - no answer 3 times in a row from a running Apache: it is stuck, it is stopped (killed if needed) and restarted.
# It is not started by the standalone application (PHP Desktop), which has its own server and PHP.
# It also quits when this file changes (update) or when the state folder disappears.
# Events are written to <state folder>/watchdog_log.txt, and <state folder>/watchdog.txt is touched at every check.
# Usage: /bin/sh watchdog.sh mamp|xampp <url of watchdog_ping.php> <state folder>
# On Linux, restarting XAMPP needs root: watchdog_allow.sh (one of the Linux scripts, run by the installation and update scripts) allows user 'daemon' to run
# "/opt/lampp/lampp stopapache" and "/opt/lampp/lampp startapache" with sudo.

SERVER="$1"
URL="$2"
DIR="$3"
SCRIPT="$0"
PATH=/usr/bin:/bin:/usr/sbin:/sbin
export PATH
# XAMPP's libraries would be used by curl
unset LD_LIBRARY_PATH DYLD_LIBRARY_PATH

INTERVAL=60		# seconds between checks
TIMEOUT=30		# seconds to wait for the answer of watchdog_ping.php
MAXFAIL=3		# failed checks in a row before restarting a running server
LOCK="$DIR/watchdog.lock"
BEAT="$DIR/watchdog.txt"
LOG="$DIR/watchdog_log.txt"
NEEDS_SUDO="$DIR/watchdog_needs_sudo"	# Linux: the sudo rule is missing, pages tell how to create it

case "$SERVER" in
	mamp)
		ROOT=/Applications/MAMP
		PIDFILE="$ROOT/Library/logs/httpd.pid"
		;;
	xampp)
		ROOT=/opt/lampp
		PIDFILE="$ROOT/logs/httpd.pid"
		;;
	*)
		exit 1
		;;
esac
[ -n "$URL" ] && [ -d "$DIR" ] || exit 1
PORT=$(echo "$URL" | sed -n 's|^http://[^/:]*:\([0-9][0-9]*\)/.*|\1|p')
[ -n "$PORT" ] || PORT=80

# Descriptors inherited from the web server
exec 3>&- 4>&- 5>&- 6>&- 7>&- 8>&- 9>&-

log() {
	echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >> "$LOG"
	}

# Only one watchdog: the lock folder holds the pid of the running one
if ! mkdir "$LOCK" 2>/dev/null; then
	OTHER=$(cat "$LOCK/pid" 2>/dev/null)
	if [ -n "$OTHER" ] && ps -p "$OTHER" -o command= 2>/dev/null | grep -q "watchdog.sh"; then
		exit 0
	fi
	rm -rf "$LOCK"
	mkdir "$LOCK" 2>/dev/null || exit 0
fi
echo $$ > "$LOCK/pid"
trap 'rm -rf "$LOCK" "$BEAT"' EXIT		# A page loaded afterwards starts a new watchdog at once
trap 'log "Stopped by signal INT: quitting"; exit 0' INT
trap 'log "Stopped by signal TERM (e.g. MAMP or XAMPP stopping its processes, log-out or shutdown): quitting"; exit 0' TERM
trap '' HUP

# The server is started with the environment of the user's session, not that of PHP (almost empty)
USER_NAME=$(id -un)
HOME_DIR=$(eval echo "~$USER_NAME")
TMP_DIR=$(getconf DARWIN_USER_TEMP_DIR 2>/dev/null)
[ -n "$TMP_DIR" ] || TMP_DIR=/tmp/

in_session() {
	(cd / && env -i HOME="$HOME_DIR" USER="$USER_NAME" LOGNAME="$USER_NAME" PATH="$PATH" TMPDIR="$TMP_DIR" SHELL=/bin/sh "$@")
	}

# On Linux, "lampp stopapache" or "startapache" might wait for a frozen Apache: they are given a time limit,
# so that the watchdog is not frozen with it ('timeout' stops sudo, which passes the signal on to lampp).
# apachectl (MAMP) only sends a signal and returns.
STOP_LIMIT=30
START_LIMIT=60

server_stop() {
	if [ "$SERVER" = mamp ]; then
		in_session "$ROOT/Library/bin/apachectl" stop
	else
		in_session timeout -k 5 $STOP_LIMIT sudo -n "$ROOT/lampp" stopapache
		case $? in 124|137) log "'lampp stopapache' did not end within $STOP_LIMIT seconds";; esac
	fi
	return 0
	}

server_start() {
	if [ "$SERVER" = mamp ]; then
		in_session "$ROOT/Library/bin/apachectl" start
	else
		in_session timeout -k 5 $START_LIMIT sudo -n "$ROOT/lampp" startapache
		case $? in 124|137) log "'lampp startapache' did not end within $START_LIMIT seconds";; esac
	fi
	return 0
	}

server_pid() {
	# Prints the pid of Apache's main process if its pid file names a running process
	P=$(cat "$PIDFILE" 2>/dev/null)
	[ -n "$P" ] && ps -p "$P" > /dev/null 2>&1 && echo "$P"
	}

can_restart() {
	# Linux: TRUE if sudo allows 'daemon' to stop and start Apache without a password (rule created by watchdog_allow.sh)
	# Otherwise $NEEDS_SUDO is created, and watchdog_notice() (_basic_tasks.php) tells the user to run watchdog_allow.sh
	[ "$SERVER" = mamp ] && return 0
	if sudo -n -l "$ROOT/lampp" stopapache > /dev/null 2>&1 && sudo -n -l "$ROOT/lampp" startapache > /dev/null 2>&1; then
		rm -f "$NEEDS_SUDO"
		return 0
	fi
	if [ ! -f "$NEEDS_SUDO" ]; then
		log "Not allowed to restart Apache: run 'sudo /home/watchdog_allow.sh' (Linux scripts)"
		touch "$NEEDS_SUDO"
	fi
	return 1
	}

answers() {
	[ "$(curl -s -m "$TIMEOUT" "$URL" 2>/dev/null)" = "ok" ]
	}

trim_log() {
	if [ -f "$LOG" ] && [ "$(wc -c < "$LOG")" -gt 100000 ]; then
		tail -n 300 "$LOG" > "$LOG.tmp" && mv "$LOG.tmp" "$LOG"
	fi
	}

RESTART_FAILS=0
restart() {
	can_restart || return
	log "$1: restarting Apache"
	P=$(server_pid)
	server_stop >> "$LOG" 2>&1
	N=0
	while [ -n "$P" ] && ps -p "$P" > /dev/null 2>&1 && [ $N -lt 20 ]; do
		sleep 1
		N=$((N + 1))
	done
	if [ "$SERVER" = mamp ]; then
		# Processes of a stuck server still running after 20 seconds
		if pgrep -f "^$ROOT/(Library/bin/(httpd|fcgi-)|bin/php/.*/php-cgi)" > /dev/null; then
			log "Killing the remaining processes of Apache and PHP"
			pkill -9 -f "^$ROOT/(Library/bin/(httpd|fcgi-)|bin/php/.*/php-cgi)"
			sleep 1
		fi
		rm -f "$PIDFILE"
	elif [ -n "$(server_pid)" ]; then
		log "Apache is still running after 'lampp stopapache'"
	else
		# Workers left by a main process that crashed: they belong to 'daemon', like the watchdog, and would keep port 80
		if pgrep -u "$(id -u)" -f "^$ROOT/bin/httpd" > /dev/null; then
			log "Killing the remaining processes of Apache"
			pkill -9 -u "$(id -u)" -f "^$ROOT/bin/httpd"
			sleep 1
		fi
		rm -f "$PIDFILE"
	fi
	server_start >> "$LOG" 2>&1
	sleep 5
	if answers; then
		log "Apache restarted"
		RESTART_FAILS=0
	else
		RESTART_FAILS=$((RESTART_FAILS + 1))
		log "Apache is not answering after restart"
		if [ $RESTART_FAILS -ge 3 ]; then
			log "Giving up after 3 restarts"
			exit 1
		fi
	fi
	}

trim_log
# Its content, not its date, tells that this file was changed: a file unpacked from a zip made in another time zone may have a date in the future
SCRIPT_SUM=$(cksum < "$SCRIPT")
log "Started (pid $$), checking $URL"
FAIL=0
while :; do
	[ -d "$DIR" ] || exit 0
	if [ "$(cksum < "$SCRIPT" 2>/dev/null)" != "$SCRIPT_SUM" ]; then
		log "watchdog.sh was changed: quitting"
		exit 0
	fi
	touch "$BEAT"
	can_restart
	if [ -f "$PIDFILE" ] && [ -z "$(server_pid)" ]; then
		# Checked first: the workers of a killed main process may still answer for a while, but they are no longer replaced
		FAIL=0
		restart "Apache crashed"
	elif answers; then
		FAIL=0
	elif [ ! -f "$PIDFILE" ]; then
		log "Apache was stopped: quitting"
		exit 0
	elif [ "$SERVER" = mamp ] && ! lsof -nP -a -p "$(server_pid)" -iTCP:"$PORT" -sTCP:LISTEN > /dev/null 2>&1; then
		# The URL is not served by MAMP's Apache (another server, or MAMP's port was changed): never restart MAMP for it
		log "Apache is not listening on port $PORT: quitting"
		exit 0
	else
		FAIL=$((FAIL + 1))
		log "No answer from Apache ($FAIL/$MAXFAIL)"
		if [ $FAIL -ge $MAXFAIL ]; then
			FAIL=0
			restart "Apache is stuck"
		fi
	fi
	touch "$BEAT"
	sleep $INTERVAL
done
