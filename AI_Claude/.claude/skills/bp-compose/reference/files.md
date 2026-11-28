# Project files and their formats

BP identifies files by prefix: `-gr` grammar, `-da` data, `-se` settings, `-al` alphabet, `-so` sound-object prototypes, `-to` tonality (scales), `-cs` Csound instruments, `-sc` SuperCollider instruments, `-tb` time base, `-wg` rule weights, `+sc` script. Older files may use suffixes instead (`NAME.bpgr`, `NAME.bpda`, `NAME.bpse`...).

## Header lines of a project

The first lines of a grammar or data project name the files it uses, one per line (after optional `//` comments):

```
// A short description of the piece
-se.MyPiece
-al.MyPiece
-so.MyPiece
-to.Vina
-cs.Vina
-tb.onebeat
-py.dancer
```

Where the interface finds them:

| Line | File looked for in |
|---|---|
| `-se.NAME`, `-al.NAME`, `-so.NAME` | the folder of the project |
| `-tb.NAME` | the top folder of the workspace (e.g. `ctests/`), even for a project in a subfolder |
| `-to.NAME` | `../tonality_resources_private/`, then `../tonality_resources/` |
| `-cs.NAME` | `../csound_resources_private/`, then `../csound_resources/` |
| `-sc.NAME` | `../supercollider_resources_private/`, then `../supercollider_resources/` |
| `-py.NAME` | program `NAME.py` of `../osc_resources_private/` or `../osc_resources/` (real-time OSC only, instruments.md) |

- An older form `-se.:NAME` names file `NAME` (no prefix).
- `-gr.NAME` in a data project names a grammar for analysis; `-da.NAME` in a grammar names data for analysis or learning weights.
- A project without `-se` gets default settings (60 beats per minute, English convention...). The interface then shows CHOOSE / CREATE buttons for settings: create a `-se` file for every new project, so that its tempo and note convention are explicit.
- Without `-cs` in Csound formats, the interface uses `-cs.0-default`; without `-sc` in SuperCollider, `-sc.0-default` (instruments.md).

## Settings (-se): JSON

Every field has the form `"Key": {"name": "...", "value": "...", "unit": "...", "boolean": "0|1"}`, values written as strings. The interface's settings page (`php/settings.php?file=...`) shows every field with its explanation.

**Create or change settings with the script `bpsettings.sh`**, run from `AI_Claude`. It writes a complete file, with its header naming it, and changes only the values given:

```sh
bash .claude/skills/bp-compose/scripts/bpsettings.sh ./-se.MyPiece --tempo 120
bash .claude/skills/bp-compose/scripts/bpsettings.sh ./-se.MyPiece --tempo 72.5 --convention french Seed=0
bash .claude/skills/bp-compose/scripts/bpsettings.sh ./-se.MyPiece --from ../ctests/-se.Mozart MaxItemsProduce=10
```

Without `--from`, an existing file is changed, and a new one is made from a complete model at 60 beats per minute, English note names, seed 1 and the pianoroll. `--tempo BPM` sets `Pclock`/`Qclock`; `--convention` is `english`, `french`, `indian` or `keys`; `KEY=VALUE` sets any field of the table below. Use `--from` to start from the settings of an example (e.g. its tonality options or MIDI values).

| Key | Meaning |
|---|---|
| `NoteConvention` | 0 English (`C4`), 1 Italian/French (`do3`), 2 Indian (`sa4`), 3 key numbers, 4 only names of tonal scales |
| `Pclock`, `Qclock` | metronome: **Pclock/Qclock is the period of a beat in seconds**. 60 beats/min: 1/1; 90: 2/3; 120: 1/2; 100: 3/5 |
| `Nature_of_time` | 1 striated (regular beats), 0 smooth |
| `Seed` | seed of the random generator (positive), 0 to shuffle at each run |
| `Improvize` | "Non-stop improvize": 1 to produce items again and again (grammars.md) |
| `MaxItemsProduce` | maximum number of items produced in a file (default 20) |
| `AllItems` | 1 to produce all items |
| `MaxConsoleTime` | time limit of computation, in seconds |
| `Quantization`, `Quantize` | dates rounded to this many ms (default 10), if `Quantize` is 1 |
| `DeftVelocity`, `DeftVolume`, `DeftPanoramic` | default MIDI values (1..127) |
| `DeftPitchbendRange` | default range of `_pitchbend()` in cents (0 = raw values 0..16383) |
| `C4key`, `A4freq` | key number of C4 (60) and diapason (440 Hz) |
| `DefaultBlockKey` | block key of scales (60) |
| `EndFadeOut` | seconds added at the end of MIDI files and Csound scores |
| `ShowGraphic`, `ShowPianoRoll`, `ShowObjectGraph` | graphics shown by the interface: pianoroll for notes, object graph for sound-objects |
| `ResetWeights`, `ResetFlags`, `ResetControllers` | reset rule weights, flags, MIDI controllers before each item |

`_mm()` in a grammar and the console options (`--seed`, `--english`...) take precedence over the settings.

## Alphabet (-al): text

```
// Bol Processor BP3
// Alphabet file saved as "-al.MyPiece". Date: ...

-so.MyPiece

// Comment
armsup armsdown clap wave
```

- The terminals, separated by spaces or newlines. A line `-so.NAME` names the prototype file of these terminals.
- Homomorphisms: a label line (e.g. `*`), followed by mappings `dha --> ta`, one per line; unmapped terminals are listed alone (`-al.dhadhatite`). The label is then used in grammars: `* (: A)` (grammars.md).
- Older alphabets have lines `-mi.NAME` or `-ho.NAME`: leave them.
- Terminals must not be notes of the current convention, nor start with an uppercase letter unless they are declared here (else they are variables). Single letters are fine (`a b c`, `A`…`Z` in `-al.robot_semaphore`).

## Sound-object prototypes (-so): JSON

A prototype gives a terminal of the alphabet a content (MIDI codes, a Csound score, or both) and timing properties. Without a prototype, a terminal is silent. Explanations: https://bolprocessor.org/sound-objects/, https://bolprocessor.org/time-setting-of-sound-objects/, https://bolprocessor.org/csound-objects/.

**To create prototypes, copy a complete prototype of an existing file and change it** (read the whole file first):
- Csound content only (time-objects moving a robot, notes of a Csound instrument): `../ctests/-so.robot_objects` (with `-al.robot_objects`), `../ctests/-so.robot_semaphore`.
- MIDI codes: `../ctests/-so.abc1`, `../ctests/-so.tryObjects`, `../ctests/-so.EkDoTin`.

The file has a `header`, global keys (`Prototype tick key/channel/velocity`, `"Csound resource file"` naming the `-cs` file used by the Csound scores, e.g. `"csound_resources/-cs.Robot"`, `Comment on this file`) and `"Prototypes"`, a list with one object per terminal. Each field is `"Label": {"key": "Key", "value": ...}`. The important keys:

| Key | Meaning |
|---|---|
| `Name` | the terminal (must be in the alphabet) |
| `Type` | `MIDI_TYPE`, `CSOUND_TYPE`, `MIDI_CSOUND_TYPE` or `SILENT` |
| `Tref` | reference duration in ms: the duration of content that fills one beat. A terminal takes its place in the score like a note (one beat, more with `_`), and its content is scaled by (beats taken × period of the beat) / Tref, so with Tref equal to the duration of its content it fills its place exactly. Unless it is never rescaled (below) |
| `PivType`, `PivPos`, `PivMode` | pivot, the point of the object placed on its date: `PIVBEG` (beginning, default), `PIVEND`, `PIVMIDDLE`, `PIVBEGON`, `PIVENDOFF`, `PIVMIDDLEONOFF`, or `SETPIVOT` at `PivPos` ms (`FIXVALUE`) or percent (`PERCENT`) |
| `RescaleMode`, `FixScale`, `OkExpand`, `OkCompress`, `AlphaMin`, `AlphaMax` | `OK_RESCALE`: the object is stretched to the duration it gets, within `AlphaMin`…`AlphaMax` (0 = no limit); `NEVER_RESCALE` (with `FixScale` 1): it keeps its own duration (e.g. a clap at any tempo) |
| `CyclicMode`, `CyclicAfter`, `ForceIntegerCycles` | a cyclic object repeats its part after `CyclicAfter` (ms or percent) to fill a longer duration. **Repeated only if its rescale mode is not `OK_RESCALE`** (pitfalls.md) |
| `OkTransp`, `OkArticul`, `OkVolume`, `OkPan`, `OkMap`, `OkVelocity` | whether the object accepts transposition, articulation, volume, panoramic, key mapping, velocity changes (1/0) |
| `ContBeg`, `ContEnd`, `CoverBeg`, `CoverEnd`, `TruncBeg`, `TruncEnd`, `BreakTempo`, `DelayMode`, `ForwardMode` | time-setting constraints (continuity, covering by the next object, truncation, delay, break of tempo) |
| `Csound score` | a string `"<HTML>t 0 60<BR>i4 0.000 0.150 261.626 40 90 0 ; comment<BR>...</HTML>"`: score lines separated by `<BR>`, dates in beats of the `t` line, instruments and fields of the `-cs` file named on top |
| `MIDIcodesize`, `byte_1`, `time_1`, ... | MIDI content: the bytes in order, each with its date in ms (`144 60 72` = NoteOn key 60 velocity 72 on channel 1; NoteOff = NoteOn with velocity 0) |
| `Comment` | description of the object |

Fields left out take default values in the console, but keep them all: the interface's pages (`prototype.php`) expect them. After writing a `-so` file, check it with `python3 -m json.tool FILE > /dev/null` and test the project.

## Tonality (-to): JSON

Microtonal scales used by `_scale(name,blockkey)` (scores.md). **Prefer the scales of the fixed tonality files**, listed in instruments.md; the interface's tonality pages (`php/tonality.php`, `php/scale.php`) show them. Each scale has: `Name of this scale`, `Note names` (grades, last = octave), `Ratios as fractions` or `Ratios`, `Number of grades`, `Interval (ratio of the period)` (usually 2), `Base key`, `Base frequency`, `Comma`, and Csound table fields. To make a new scale, copy one from a fixed file into a new `-to` file in `../tonality_resources_private/` and change it, or ask the user to use the scale tools of the interface (import from SCALA files, temperaments). See https://bolprocessor.org/microtonality/.

## Time base (-tb): JSON

Ticks (e.g. a tala, a metronome click) superposed to each item in MIDI files and real-time MIDI. A metronome `Pclock` ticks in `Qclock` beats, and `Tracks`, each with `TickKey`, `TickChannel` (10 for General MIDI percussion: keys 76/77 wood blocks, 37 side stick), `TickVelocity`, `TickCycle` (beats per cycle), speed ratio `Ptick`/`Qtick`, `TickDuration` (ms) and `Ticks` (1 = tick, 0 = none, for the positions of the cycle). Copy one of `../ctests/-tb.*` (`-tb.onebeat`: one tick per beat; `-tb.tintal`, `-tb.rupak`... talas) into the top folder of the workspace, or let the user design it on the interface's time base page (`php/timebase.php`). Technical details: https://bolprocessor.org/tech-analysis-of-timebase/. Only the last `-tb` line of a project counts.

## Rule weights (-wg)

Created by the interface when learning weights from data. Not written by hand.
