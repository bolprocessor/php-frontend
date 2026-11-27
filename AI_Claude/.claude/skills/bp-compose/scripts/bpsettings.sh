#!/bin/bash
# Create or change a Bol Processor settings file (-se.NAME). Part of the AI agent package (folder AI_Claude).
#
# Usage: bpsettings.sh FILE [--from MODEL] [--tempo BPM] [--convention C] [KEY=VALUE ...]
#   FILE             the settings file to write, e.g. ./-se.MyPiece
#   --from MODEL     settings file to copy (e.g. ../ctests/-se.Mozart). Without it: FILE itself if it
#                    exists (it is changed), otherwise a complete model with 60 beats per minute,
#                    English note names and the pianoroll shown
#   --tempo BPM      metronome in beats per minute (sets Pclock and Qclock; e.g. 120, 90, 72.5)
#   --convention C   note names: english (C4), french (do3), indian (sa4), keys (key numbers)
#   KEY=VALUE        any field of the file, e.g. Seed=0 Improvize=1 MaxItemsProduce=10 EndFadeOut=0
#                    (fields are listed in reference/files.md; the value is written as a string)
#
# Only the "value" of each field named is changed: the layout of the file is kept, and its header
# names the new file, as when the interface saves it. Prints the values that were set.

die() { echo "bpsettings: $*" >&2; exit 2; }

[ $# -ge 1 ] || die "usage: bpsettings.sh FILE [--from MODEL] [--tempo BPM] [--convention C] [KEY=VALUE ...]"
FILE=$1; shift
case $(basename "$FILE") in -se.*|*.bpse) ;; *) die "not a settings file name (-se.NAME): $FILE" ;; esac
MODEL=""; SETS=(); DEFAULTS=()
while [ $# -gt 0 ]; do
  case $1 in
    --from) MODEL=$2; shift ;;
    --tempo)
      bpm=$2; shift
      [[ $bpm =~ ^[0-9]+(\.[0-9]+)?$ ]] && [ "$bpm" != 0 ] || die "--tempo needs a number of beats per minute"
      # Pclock/Qclock is the period of a beat in seconds = 60/BPM, as a reduced fraction
      pq=$(perl -e 'my $b = shift; my $s = 1; while ($b * $s != int($b * $s) and $s < 1000000) { $s *= 10 }
        my ($p, $q) = (60 * $s, int($b * $s + 0.5)); my ($a, $c) = ($p, $q); ($a, $c) = ($c, $a % $c) while $c; print $p / $a, " ", $q / $a' "$bpm")
      SETS+=("Pclock=${pq% *}" "Qclock=${pq#* }") ;;
    --convention)
      case $2 in
        english) c=0 ;; french|italian) c=1 ;; indian) c=2 ;; keys) c=3 ;;
        *) die "--convention: english, french, indian or keys" ;;
      esac
      SETS+=("NoteConvention=$c"); shift ;;
    *=*) SETS+=("$1") ;;
    *) die "unknown option $1" ;;
  esac
  shift
done

# Root of the installation (folder containing php/_basic_tasks.php), above this script
d=$(cd "$(dirname "$0")" && pwd)
while [ "$d" != "/" ] && [ ! -f "$d/php/_basic_tasks.php" ]; do d=$(dirname "$d"); done
ROOT=$d

if [ -z "$MODEL" ]; then
  if [ -f "$FILE" ]; then MODEL=$FILE
  else
    for m in "$ROOT/ctests/-se.robot_dance" "$ROOT/ctests_git/-se.robot_dance"; do [ -f "$m" ] && { MODEL=$m; break; }; done
    [ -n "$MODEL" ] || die "no model found: give one with --from"
    # The model's own choices replaced by plain defaults, before the values asked for
    DEFAULTS=("Pclock=1" "Qclock=1" "NoteConvention=0" "ShowPianoRoll=1" "ShowObjectGraph=0" "Seed=1" "Improvize=0")
  fi
fi
[ -f "$MODEL" ] || die "model not found: $MODEL"

tmp=$(mktemp) || die "cannot create a temporary file"
perl -e '
  use strict;
  my ($model, $name, @sets) = @ARGV;
  open(my $h, "<:raw", $model) or die "Cannot open $model\n"; local $/; my $t = <$h>; close $h;
  $t =~ /^\s*\{/ or die "$model is not a settings file in JSON format: open it once in the interface to convert it\n";
  my @d = localtime; my $date = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $d[5] + 1900, $d[4] + 1, $d[3], $d[2], $d[1], $d[0]);
  $t =~ s/("header":\s*")[^"\n]*(")/$1\\\/\\\/ Bol Processor BP3\\n\\\/\\\/ Settings file saved as \x27$name\x27. Date: $date$2/
    or die "No header in $model\n";
  my %done;
  for my $s (@sets) {
    my ($k, $v) = split /=/, $s, 2;
    $v =~ s/(["\\])/\\$1/g;
    $t =~ s/("\Q$k\E":\s*\{\s*"name":\s*"[^"]*",\s*"value":\s*")[^"]*(")/$1$v$2/
      or die "No field \"$k\" in $model (fields are case-sensitive, e.g. Pclock, NoteConvention)\n";
    $done{$k} = $v;
  }
  print STDERR join(" ", map { "$_=$done{$_}" } sort keys %done), "\n" if %done;
  print $t;
' "$MODEL" "$(basename "$FILE")" "${DEFAULTS[@]}" "${SETS[@]}" > "$tmp" 2> "$tmp.msg"
rc=$?
if [ $rc -ne 0 ]; then cat "$tmp.msg" >&2; rm -f "$tmp" "$tmp.msg"; exit 2; fi
mv "$tmp" "$FILE" || die "cannot write $FILE"
echo "Written: $FILE (model: $MODEL)"
[ -s "$tmp.msg" ] && echo "Values set: $(cat "$tmp.msg")"
rm -f "$tmp.msg"
