# Testing a project

Always test a project before telling the user it is ready. You can't hear the result, but the console tells you whether it compiles, what it produced, and the date, duration, pitch and instrument of every note.

## The test script

From the folder `AI_Claude` (where you were started):

```sh
bash .claude/skills/bp-compose/scripts/bptest.sh ./-gr.MyPiece
bash .claude/skills/bp-compose/scripts/bptest.sh ./-da.MyScores            # every item, one by one
bash .claude/skills/bp-compose/scripts/bptest.sh ./-da.MyScores --item 3   # item #3 alone, with its notes
bash .claude/skills/bp-compose/scripts/bptest.sh ./-gr.MyPiece --notes      # also the list of notes
bash .claude/skills/bp-compose/scripts/bptest.sh ../ctests/-gr.robot_dance --osc   # also the OSC messages
```

It finds the files named on the first lines of the project (as the interface does), runs the console from `../php` as the interface does, and plays nothing in real time. For a grammar or each data item it makes:

- a MIDI file and, for a grammar, the items produced (`.txt`);
- a Csound score (`.sco`), which lists every note: `i<instrument> <start> <duration> <pitch> ... ; <note name>`;
- with `--osc` (or `--scrt`), the messages that real-time OSC (or SuperCollider) would send, each preceded by its date in seconds.

Then it prints a report: compilation errors, messages starting with `=>`, the items produced, possibly undefined variables left in them, the number of NoteOns in the MIDI file and of notes in the Csound score, the first notes. For data, one line per item. Exit code 0 when everything compiled and sounded.

**`--notes [N]`** lists the notes of the MIDI file in order of date: start and duration in seconds, name, key number, channel and velocity, and program changes (at most N lines per item, 200 by default). Use it to check rhythms, chords, entries and lengths: there is no need to decode MIDI files or sort score lines yourself.

Options: `--seed N` (default 1, so that random productions can be repeated; 0 to use the settings' seed), `--time S` (time limit, default 60 s), and `-- OPTIONS` passed to the console, e.g. `-- --english` to force a note convention. All files are in `../temp_bolprocessor/AI_tests/NAME/`.

On Windows, the script needs the Bash of Git for Windows (which Claude Code uses); the console is `bp.exe`.

## Reading the results

- **Compilation failed**: the log quotes the faulty rule (`??? gram#1[2] A --> C4 D4 zork`) and the reason (`Can't make sense of "zork"`: unknown terminal, wrong note convention, or a variable written in lowercase).
- **Items produced** (grammars): check that no variable is left (a word starting with uppercase that is not a note or a terminal) and that the structure is as intended. Several lines = several items (setting `Improvize`).
- **Notes**: in the Csound score, start and duration are in beats of the `t` line (`t 0 60`: 60 beats per minute, so beats = seconds; with `t 0 90`, a beat is 2/3 s). The pitch is in octave.pitch-class (`8.00` = C4, `8.09` = A4, `7.07` = G3) or in Hz (`261.626`), depending on the instrument. Fields after the pitch are those of the instrument (velocity, volume, parameters: start and end values). The comment at the end gives the note name.
- Check what was asked: number of notes, total duration (the `e` line includes the fade-out of the settings, 2 s by default), instruments (`i1`, `i2`...), simultaneous notes (same start), the course of parameters (start/end values).
- **Messages starting with `=>`** are warnings or errors; read them. `Instrument "X" was not found` means `_ins(X)` names an instrument absent from the `-cs`/`-sc` file.
- **NOTHING AUDIBLE**: no NoteOn and no Csound note. Normal only for grammars of silent symbols (alphabet without prototypes); otherwise look for an error.
- **TIMEOUT**: the grammar may loop forever (a recursive rule without a stop condition): add a flag or change weights.
- `Conflicting field duration`: a polymetric expression whose fields have incompatible explicit durations.

## Running the console directly

For special checks, run the console yourself from `../php` (always from there):

```sh
cd ../php && ../bp produce -gr ../ctests/-gr.MyPiece -se ../ctests/-se.MyPiece --seed 1 --trace-production -o ../temp_bolprocessor/AI_tests/x.txt
```

(`../bp3` on Linux, `../bp.exe` on Windows.) Useful actions and options (`../bp --help` lists them all):

| Use | Command |
|---|---|
| show the work string at each step of a derivation | `produce ... --show-production`, or `--trace-production` (also the rule chosen) |
| see how a polymetric expression is read | `expand -da FILE -o OUT.txt` (one item per file; `C4 D4 . E4 F4 G4` gives `/1 C4 D4 *2/3 E4 F4 G4`) |
| compile only | `compile -gr FILE ...` |
| all items of a grammar | `produce-all -gr FILE ...` |
| event list (CSV of every event) | `--eventlistout FILE.csv` |

Only one output format works per run (a MIDI file and a Csound score need two runs: the script does it). Never use `--rtmidi`, nor `--csoundrt`, `--scrt` or `--osc` without `--rt-dump FILE`.

## What you can't check

The sound itself (timbre, balance, whether it is musical), the graphics of the interface, and real-time behaviour (interaction with MIDI input, scripts waiting for events). Say so in your report, and suggest what the user should listen for.
