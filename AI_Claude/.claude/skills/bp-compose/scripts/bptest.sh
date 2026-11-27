#!/bin/bash
# Test a Bol Processor grammar or data project with the console, as the interface would run it,
# without playing anything in real time. Part of the AI agent package (folder AI_Claude).
#
# Usage: bptest.sh PROJECT [options]
#   PROJECT          path of a grammar (-gr.NAME, NAME.bpgr) or data project (-da.NAME, NAME.bpda)
#   --item N         data only: test item N alone (numbered from 1, as in the interface)
#   --osc            also write the messages of real-time OSC to a file
#   --scrt           also write the messages of real-time SuperCollider to a file
#   --notes [N]      list the notes of the MIDI file (date and duration in seconds, name, key, channel,
#                    velocity; also program changes), at most N lines (default 200) per item
#   --seed N         seed of the random generator (default 1; 0 = the one of the settings)
#   --time S         time limit of each run in seconds (default 60, needs 'timeout' or 'gtimeout')
#   -- OPTIONS       further options given to the console as they are, e.g. -- --english
#
# Output folder: temp_bolprocessor/AI_tests/NAME/ of the installation. For a grammar, or each item
# of a data project (BASE = NAME, or NAME.N for item N):
#   BASE.mid    MIDI file; for a grammar, the items produced are in BASE.txt (log BASE.log)
#   BASE.sco    Csound score: a readable list of the notes, one "i" line per note: instrument,
#               start, duration (in beats of the "t" line), pitch... (log BASE.cs.log)
#   BASE.osc    (--osc) OSC messages, one per line preceded by its date in seconds
#   BASE.scrt   (--scrt) the same for SuperCollider
# then prints a report. Exit code: 0 if everything compiled and produced notes, 1 otherwise.
#
# The resource files named in the header of the project (-se, -al, -so, -to, -cs, -sc, -tb) are looked
# for in the folder of the project, the top folder of its workspace, then the resource folders (the
# user's "_private" folder first). A line -py.NAME (Python program for real-time OSC) is ignored.
#
# Data projects are played as data.php does: each item (paragraph separated by blank lines) alone,
# after removing Csound score lines and [comments] (except one at the start of a line), as data.php
# does (consoles compiled before 2026-11-27 stopped reading a line at a [comment] after its start).

die() { echo "bptest: $*" >&2; exit 2; }

[ $# -ge 1 ] || die "usage: bptest.sh PROJECT [--item N] [--notes [N]] [--osc] [--scrt] [--seed N] [--time S] [-- OPTIONS]"
PROJECT=$1; shift
OSC=0; SCRT=0; SEED=1; LIMIT=60; ITEM=""; EXTRA=(); NOTES=0
while [ $# -gt 0 ]; do
  case $1 in
    --osc)  OSC=1 ;;
    --scrt) SCRT=1 ;;
    --item) ITEM=$2; shift ;;
    --notes) NOTES=200; [[ $2 =~ ^[0-9]+$ ]] && { NOTES=$2; shift; } ;;
    --seed) SEED=$2; shift ;;
    --time) LIMIT=$2; shift ;;
    --)     shift; EXTRA=("$@"); break ;;
    *)      die "unknown option $1" ;;
  esac
  shift
done

[ -f "$PROJECT" ] || die "file not found: $PROJECT"
PROJECT=$(cd "$(dirname "$PROJECT")" && pwd)/$(basename "$PROJECT")
PDIR=$(dirname "$PROJECT")
NAME=$(basename "$PROJECT")

# Root of the installation: the first folder above this script, or above the project, containing php/_basic_tasks.php
find_root() {
  local d=$1
  while [ -n "$d" ] && [ "$d" != "/" ] && [ "$d" != "." ]; do
    [ -f "$d/php/_basic_tasks.php" ] && { echo "$d"; return; }
    d=$(dirname "$d")
  done
}
ROOT=$(find_root "$(cd "$(dirname "$0")" && pwd)")
[ -n "$ROOT" ] || ROOT=$(find_root "$PDIR")
[ -n "$ROOT" ] || die "cannot find the Bol Processor installation (folder containing php/_basic_tasks.php)"

BIN=""
for b in bp bp3 bp.exe; do [ -f "$ROOT/$b" ] && { BIN=$ROOT/$b; break; }; done
[ -n "$BIN" ] || die "console not found in $ROOT (bp, bp3 or bp.exe): install or compile it with the interface"

case $NAME in
  -gr.*|*.bpgr) KIND=grammar; ACTION=produce; FLAG=-gr ;;
  -da.*|*.bpda) KIND=data; ACTION=play; FLAG=-da ;;
  *) die "not a grammar (-gr.NAME, NAME.bpgr) or data project (-da.NAME, NAME.bpda): $NAME" ;;
esac

# Top folder of the workspace (e.g. ctests or AI_Claude): first folder under ROOT on the path of the project
case $PDIR in
  "$ROOT"/*) rel=${PDIR#"$ROOT"/}; TOP=$ROOT/${rel%%/*} ;;
  *) TOP=$PDIR ;;
esac

# Find resource file $1 (e.g. "-to.Vina"); print its path, or nothing
find_resource() {
  local f=$1 d
  case $f in -???:*) f=${f:5} ;; esac   # older form "-se.:NAME" names file NAME
  for d in "$PDIR" "$TOP" \
           "$ROOT/tonality_resources_private" "$ROOT/tonality_resources" \
           "$ROOT/csound_resources_private" "$ROOT/csound_resources" \
           "$ROOT/supercollider_resources_private" "$ROOT/supercollider_resources"; do
    [ -f "$d/$f" ] && { echo "$d/$f"; return; }
  done
}

ARGS=(); CSFILE=""; SCFILE=""; MISSING=()
while IFS= read -r line; do
  line=${line%"${line##*[![:space:]]}"}
  path=$(find_resource "$line")
  if [ -z "$path" ]; then MISSING+=("$line"); continue; fi
  case ${line:0:3} in
    -cs) CSFILE=$path ;;
    -sc) SCFILE=$path ;;
    *)   ARGS+=("${line:0:3}" "$path") ;;
  esac
done < <(tr -d '\r' < "$PROJECT" | grep -E -- '^(-(se|al|so|to|cs|sc)\.:?[A-Za-z0-9_.+-]+|-tb\.:?[A-Za-z0-9_.+ -]*[A-Za-z0-9_.+-])[[:space:]]*$' | sort -u)

OUT=$ROOT/temp_bolprocessor/AI_tests/${NAME#-}
mkdir -p "$OUT" || die "cannot create $OUT"
rm -f "$OUT"/*

TO=$(command -v timeout || command -v gtimeout)
SEEDARG=(); [ "$SEED" != "0" ] && SEEDARG=(--seed "$SEED")
CSARG=(); [ -n "$CSFILE" ] && CSARG=(-cs "$CSFILE")

# Run the console from php/, as the interface does (reserved paths are relative to it).
# $1 = input file, $2 = log, then the options of this run. Prints a problem, if any.
run() {
  local input=$1 log=$2; shift 2
  ( cd "$ROOT/php" && $TO ${TO:+$LIMIT} "$BIN" "$ACTION" "$FLAG" "$input" "${ARGS[@]}" "${SEEDARG[@]}" "$@" "${EXTRA[@]}" ) > "$log" 2>&1
  local rc=$?
  case $rc in
    0) ;;
    124) echo "TIMEOUT after $LIMIT s (see $log)" ;;
    *)   echo "CRASH: console exited with code $rc (see $log)" ;;
  esac
}

# All runs of input $1, outputs BASE = $2
run_all() {
  local input=$1 base=$2 txt=()
  [ $KIND = grammar ] && txt=(-o "$base.txt")
  run "$input" "$base.log" "${CSARG[@]}" "${txt[@]}" --midiout "$base.mid"
  run "$input" "$base.cs.log" "${CSARG[@]}" --csoundout "$base.sco"
  if [ $OSC = 1 ]; then
    local rt=(); if [ -n "$SCFILE" ]; then rt=(-sc "$SCFILE"); else rt=("${CSARG[@]}"); fi
    run "$input" "$base.osc.log" "${rt[@]}" --osc 7400 --rt-dump "$base.osc"
  fi
  if [ $SCRT = 1 ]; then
    run "$input" "$base.scrt.log" -sc "${SCFILE:-$ROOT/supercollider_resources/-sc.0-default}" --scrt 57110 --rt-dump "$base.scrt"
  fi
}

# Problems found in the logs of BASE $1, one line, or nothing
problems() {
  local base=$1
  if grep -q "Compilation failed\|Problem compiling\|cannot be loaded" "$base.log" 2>/dev/null; then
    echo "COMPILATION FAILED: $(grep -m1 -E "Can't make sense|^Error code|cannot be loaded" "$base.log")"
    return
  fi
  cat "$base".*log 2>/dev/null | grep -E "^=>|^Conflicting|^Error|rror:|Can't make sense" | grep -v "_stop' file\|no Trace path\|^Errors: 0" | sort -u | head -3 | tr '\n' ' '
}

notes() { local n=0; [ -f "$1.sco" ] && n=$(grep -c '^i' "$1.sco"); echo "${n:-0}"; }
# NoteOn events (velocity > 0) of the MIDI file $1.mid, and their channels: "count channels"
# This is what is played in MIDI, sound-objects with MIDI codes included
midi_notes() {
  [ -f "$1.mid" ] || { echo "0"; return; }
  perl -e '
    open(my $h, "<:raw", $ARGV[0]) or die; local $/; my $d = <$h>; close $h;
    my ($n, %ch) = (0); my $p = 14;
    while ($p + 8 <= length $d) {
      my ($id, $len) = unpack("a4 N", substr($d, $p, 8)); $p += 8;
      my $end = $p + $len; if ($id ne "MTrk") { $p = $end; next; }
      my $rs = 0;
      while ($p < $end) {
        while ($p < $end and ord(substr($d, $p++, 1)) & 0x80) {}     # delta time
        my $b = ord(substr($d, $p, 1));
        if ($b == 0xFF) { $p += 2; my $l = 0; my $c; do { $c = ord(substr($d, $p++, 1)); $l = ($l << 7) | ($c & 0x7F) } while ($c & 0x80); $p += $l; next; }
        if ($b == 0xF0 or $b == 0xF7) { $p++; my $l = 0; my $c; do { $c = ord(substr($d, $p++, 1)); $l = ($l << 7) | ($c & 0x7F) } while ($c & 0x80); $p += $l; next; }
        if ($b & 0x80) { $rs = $b; $p++; }
        my $t = $rs & 0xF0;
        my $k = ($t == 0xC0 or $t == 0xD0) ? 1 : 2;
        if ($t == 0x90 and ord(substr($d, $p + 1, 1)) > 0) { $n++; $ch{($rs & 0x0F) + 1}++; }
        $p += $k;
      }
      $p = $end;
    }
    print $n, ($n ? " (channel" . (keys %ch > 1 ? "s " : " ") . join(",", sort { $a <=> $b } keys %ch) . ")" : ""), "\n";
  ' "$1.mid"
}
score_end() { [ -f "$1.sco" ] && grep -m1 '^e ' "$1.sco" | cut -c3-; }
# List of the notes of the MIDI file $1.mid, in order of date (seconds), at most $2 lines
midi_list() {
  [ -f "$1.mid" ] || { echo "(no MIDI file)"; return; }
  perl -e '
    open(my $h, "<:raw", $ARGV[0]) or die; local $/; my $d = <$h>; close $h;
    my $max = $ARGV[1]; my $div = unpack("n", substr($d, 12, 2)) || 480;
    my (@ev, @tempo); my $p = 14; my $track = 0;
    while ($p + 8 <= length $d) {
      my ($id, $len) = unpack("a4 N", substr($d, $p, 8)); $p += 8;
      my $end = $p + $len; if ($id ne "MTrk") { $p = $end; next; }
      my ($rs, $tick) = (0, 0); $track++;
      while ($p < $end) {
        my $dt = 0; my $c; do { $c = ord(substr($d, $p++, 1)); $dt = ($dt << 7) | ($c & 0x7F) } while ($c & 0x80); $tick += $dt;
        my $b = ord(substr($d, $p, 1));
        if ($b == 0xFF) { my $type = ord(substr($d, $p + 1, 1)); $p += 2; my $l = 0; do { $c = ord(substr($d, $p++, 1)); $l = ($l << 7) | ($c & 0x7F) } while ($c & 0x80);
          push @tempo, [$tick, unpack("N", "\0" . substr($d, $p, 3))] if $type == 0x51; $p += $l; next; }
        if ($b == 0xF0 or $b == 0xF7) { $p++; my $l = 0; do { $c = ord(substr($d, $p++, 1)); $l = ($l << 7) | ($c & 0x7F) } while ($c & 0x80); $p += $l; next; }
        if ($b & 0x80) { $rs = $b; $p++; }
        my $t = $rs & 0xF0; my $ch = ($rs & 0x0F) + 1;
        my $k = ($t == 0xC0 or $t == 0xD0) ? 1 : 2;
        my ($a1, $a2) = (ord(substr($d, $p, 1)), ord(substr($d, $p + 1, 1)));
        push @ev, [$tick, $t == 0x90 && $a2 > 0 ? "on" : "off", $ch, $a1, $a2] if $t == 0x90 or $t == 0x80;
        push @ev, [$tick, "program", $ch, $a1] if $t == 0xC0;
        $p += $k;
      }
      $p = $end;
    }
    @tempo = sort { $a->[0] <=> $b->[0] } @tempo;
    unshift(@tempo, [0, 500000]) unless @tempo and $tempo[0][0] == 0;
    sub sec { my $tk = shift; my ($s, $last, $us) = (0, 0, 500000);
      for my $x (@tempo) { last if $x->[0] > $tk; $s += ($x->[0] - $last) * $us / $div / 1e6; ($last, $us) = @$x; }
      return $s + ($tk - $last) * $us / $div / 1e6; }
    my @names = qw(C C# D D# E F F# G G# A A# B);
    my (%open, @notes);
    for my $e (sort { $a->[0] <=> $b->[0] or ($a->[1] eq "on") <=> ($b->[1] eq "on") } @ev) {
      if ($e->[1] eq "program") { push @notes, [sec($e->[0]), sprintf("program %d (channel %d)", $e->[3] + 1, $e->[2])]; next; }
      my $key = "$e->[2] $e->[3]";
      if ($e->[1] eq "on") { push @{$open{$key}}, [$e->[0], $e->[4], scalar @notes]; push @notes, undef; next; }
      my $o = shift @{$open{$key} || []} or next;
      my ($start, $dur) = (sec($o->[0]), sec($e->[0]) - sec($o->[0]));
      $notes[$o->[2]] = [$start, sprintf("%-4s key %3d  dur %7.3f  chan %2d  vel %3d", $names[$e->[3] % 12] . (int($e->[3] / 12) - 1), $e->[3], $dur, $e->[2], $o->[1])];
    }
    for my $k (keys %open) { for my $o (@{$open{$k}}) { my ($ch, $key) = split / /, $k;
      $notes[$o->[2]] = [sec($o->[0]), sprintf("%-4s key %3d  (no end)  chan %2d  vel %3d", $names[$key % 12] . (int($key / 12) - 1), $key, $ch, $o->[1])]; } }
    @notes = sort { $a->[0] <=> $b->[0] } grep { defined } @notes;
    my $n = 0;
    for my $x (@notes) { if (++$n > $max) { print "... (", scalar(@notes) - $max, " more lines)\n"; last; } printf "%8.3f  %s\n", @$x; }
    print "(no notes)\n" unless @notes;
  ' "$1.mid" "$2"
}

FAILED=0
echo "Project: $PROJECT ($KIND)"
[ ${#MISSING[@]} -gt 0 ] && { echo "Resource files NOT FOUND (the interface would not find them either): ${MISSING[*]}"; FAILED=1; }
[ ${#ARGS[@]} -gt 0 ] && echo "Resources: ${ARGS[*]}"
[ -n "$CSFILE" ] && echo "Csound instruments: $CSFILE"
[ -n "$SCFILE" ] && echo "SuperCollider instruments: $SCFILE"

if [ $KIND = grammar ]; then
  BASE=$OUT/${NAME#-}
  run_all "$PROJECT" "$BASE"
  echo
  echo "== Compilation"
  if grep -q "Compilation failed\|Problem compiling\|cannot be loaded" "$BASE.log"; then
    FAILED=1
    echo "FAILED:"
    grep -E -B1 -A2 "^Error code|Can't make sense|cannot be loaded" "$BASE.log" | grep -v '^--$' | head -40
  else
    echo "ok"
  fi
  echo
  echo "== Messages starting with => (warnings or errors)"
  cat "$BASE".*log 2>/dev/null | grep '^=>' | grep -v "_stop' file\|no Trace path\|Compilation failed\|Problem compiling" | sort | uniq -c | head -20
  echo
  echo "== Items produced ($BASE.txt)"
  if [ -s "$BASE.txt" ]; then
    echo "$(grep -c . "$BASE.txt") line(s). First 600 characters:"
    head -c 600 "$BASE.txt"; echo
    # Variables left in the items: words starting with an uppercase letter that are not English note names
    left=$(tr -s ' {},' '\n' < "$BASE.txt" | grep -E '^[A-Z][A-Za-z0-9_]*$' | grep -vE '^[A-G](#|b)?(00|[0-9]+)$' | sort -u | head -20 | tr '\n' ' ')
    [ -n "$left" ] && echo "Possibly undefined variables left in the items (no rule rewrote them): $left"
  else
    echo "EMPTY: nothing was produced"; FAILED=1
  fi
  echo
  echo "== Notes ($BASE.sco)"
  echo "MIDI file: $(midi_notes "$BASE") NoteOn(s)"
  if [ "$(notes "$BASE")" -gt 0 ]; then
    echo "$(notes "$BASE") Csound note(s), instruments: $(grep -oE '^i[^ ]+' "$BASE.sco" | sort | uniq -c | tr -s ' \n' ' ')"
    grep -m1 '^t ' "$BASE.sco" | sed 's/^/tempo line: /'
    echo "end of the score: $(score_end "$BASE") beats of the tempo line (fade-out included)"
    echo "First notes:"; grep '^i' "$BASE.sco" | head -8
  else
    echo "No Csound notes (normal if all sound-objects have MIDI codes only)"
  fi
  if [ "$NOTES" -gt 0 ]; then
    echo; echo "== Notes of the MIDI file (seconds; all items one after the other)"; midi_list "$BASE" "$NOTES"
  fi
  m=$(midi_notes "$BASE"); [ "${m%% *}" -gt 0 ] || [ "$(notes "$BASE")" -gt 0 ] || { echo "NOTHING AUDIBLE: no NoteOn and no Csound note (normal only if the terminals are silent symbols, e.g. an alphabet without -so prototypes)"; FAILED=1; }
  for f in "$BASE.osc" "$BASE.scrt"; do
    [ -f "$f" ] || continue
    echo; echo "== Messages ($f)"
    echo "$(grep -c . "$f") message(s); first ones:"; head -12 "$f"
  done
else
  # Split the data into items, as data.php does: N.bpda in the output folder for item N (from 1)
  perl -e '
    my ($in, $dir) = @ARGV;
    open(my $h, "<:raw", $in) or die "Cannot open $in\n"; local $/; my $t = <$h>; close $h;
    $t =~ s/\r\n?/\n/g; $t =~ s/\t/ /g;
    $t = join("\n", map { s/^\s+|\s+$//gr } split(/\n/, $t, -1));
    my $n = 0; my $started = 0;
    for my $par (split /\n{2,}/, $t) {
      $par =~ s/^\s+|\s+$//g;
      next if $par eq "";
      last if $par =~ /^<\?xml/;
      next if $par =~ m{^//} or $par =~ /^-/;
      my @lines;
      for my $l (split /\n/, $par) {
        next if $l =~ /^[if]\s*-?[0-9]/ or $l =~ /^[tse](\s|$)/ or $l =~ m{^//};   # Csound score lines, comments
        my $lead = ($l =~ s/^(\[[^\]]*\])//) ? $1 : "";
        $l =~ s/\[[^\]]*\]//g;
        $l =~ s/\xE2\x80\xA2/ ./g; $l =~ s/\xE2\x80\xA6/_rest/g;
        $l = $lead . $l;
        push @lines, $l if $l =~ /\S/;
      }
      next unless @lines;
      next unless grep { !/^\[[^\]]*\]\s*$/ } @lines;   # only comments: not an item
      $n++;
      open(my $o, ">:raw", "$dir/$n.bpda") or die; print $o join("\n", @lines), "\n"; close $o;
    }
    print "$n\n";
  ' "$PROJECT" "$OUT" > "$OUT/count" || die "cannot split $PROJECT into items"
  COUNT=$(cat "$OUT/count")
  echo "$COUNT item(s)"
  [ "$COUNT" -gt 0 ] || { echo "NO ITEMS"; exit 1; }
  if [ -n "$ITEM" ]; then FIRST=$ITEM; LAST=$ITEM; else FIRST=1; LAST=$COUNT; fi
  [ "$FIRST" -ge 1 ] && [ "$LAST" -le "$COUNT" ] || die "item $ITEM does not exist"
  echo
  echo "== Items (NoteOns in the MIDI file; notes in the Csound score; end of the Csound score in beats of its tempo line; problems)"
  TOTAL=0
  for ((i = FIRST; i <= LAST; i++)); do
    BASE=$OUT/item$i
    run_all "$OUT/$i.bpda" "$BASE"
    o=$(midi_notes "$BASE"); n=$(notes "$BASE"); TOTAL=$((TOTAL + ${o%% *}))
    p=$(problems "$BASE")
    title=$(head -c 50 "$OUT/$i.bpda" | head -1)
    [ "${o%% *}" -gt 0 ] || [ "$n" -gt 0 ] || { FAILED=1; p="NOTHING AUDIBLE. $p"; }
    printf "#%-3s MIDI %-18s Csound %5s  end %-8s %s\n     %s\n" "$i" "$o" "$n" "$(score_end "$BASE")" "$p" "$title"
    [ "$NOTES" -gt 0 ] && { midi_list "$BASE" "$NOTES" | sed 's/^/     /'; echo; }
  done
  echo
  echo "Total: $TOTAL NoteOn(s) in the MIDI files"
  if [ -n "$ITEM" ]; then
    echo; echo "Notes of item $ITEM:"; grep '^i' "$OUT/item$ITEM.sco" | head -20
    for f in "$OUT/item$ITEM.osc" "$OUT/item$ITEM.scrt"; do
      [ -f "$f" ] && { echo; echo "Messages ($f), first ones:"; head -12 "$f"; }
    done
  fi
fi

echo
echo "Output folder: $OUT"
exit $FAILED
