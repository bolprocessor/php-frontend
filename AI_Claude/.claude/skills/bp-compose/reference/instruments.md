# Instruments: MIDI, Csound, SuperCollider, OSC

The same score can be played in several **output formats**, chosen by the user on the project page:

| Format | What it needs | What notes become |
|---|---|---|
| MIDI file, real-time MIDI | nothing (a synthesiser or the browser's player) | MIDI events on channels `_chan(1..16)`; General MIDI sounds with `_script(MIDI program n)` |
| Csound score / real-time Csound | Csound 7 installed; a `-cs` file (default `-cs.0-default`) | score lines `i<instr> start duration pitch ...` for the Csound orchestra named in the `-cs` file |
| Real-time SuperCollider | SuperCollider (scsynth) installed; a `-sc` file (default `-sc.0-default`) | OSC `/s_new` messages for SynthDefs named like the instruments |
| Real-time OSC | a program listening to OSC (Max, Pd, a Python program, a robot) | messages `/bp/note`, `/bp/off`, `/bp/env` |

A project written for MIDI plays in the other formats with default instruments, and vice versa: `_ins()` is ignored in MIDI, and `_chan()` matters in the other formats only through the channel-to-instrument table of the `-cs` file.

## Instruments in the score

- `_ins(n)` or `_ins(name)` chooses the instrument of the following notes, by its index or its name in the `-cs`/`-sc` file. Notes without `_ins()` are played by instrument 1, unless the `-cs` file assigns an instrument to their MIDI channel (`_chan()`).
- Continuous controls (`_volume`, `_pan`, `_mod`, `_press`, `_pitchbend` and their `cont` forms) are sent as fields of the score line when the instrument declares them (start and end values), so a note can change during its duration.
- **Additional parameters**: named fields of an instrument, set by `_value(name,x)` in the mode `_cont(name)` (continuous), `_step(name)` or `_fixed(name)`. Example of `-cs.Vina` (parameter `slide`), `-gr.vina3`:

```
_ins(Vina) _cont(slide) _value(slide,0) sa4 _ _value(slide,30) _ _value(slide,0)
```

- A `_value()` placed between the prolongations of a note bends its course (several points per note, sent as tables in Csound, envelopes in SuperCollider, `/bp/env` messages in OSC).
- **A parameter name belongs to a single instrument**: if two instruments declare a parameter of the same name, `_value(name,...)` switches the notes to the last instrument declaring it. Give each instrument its own parameter names (`-cs.Robot`: parameter `torso` for instrument `torso`, `head` for `head`...).

More: https://bolprocessor.org/csound-instrument-files/, https://bolprocessor.org/continuous-parameters-csound/, https://bolprocessor.org/real-time-csound/.

## Fixed instrument files

Usable by name in any project (`-cs.NAME` or `-sc.NAME` on top). Index = name (additional parameters):

| File | Orchestra / SynthDefs | Instruments |
|---|---|---|
| `-cs.0-default`, `-sc.0-default` | `0-default.orc`, `0-default.scd` | 1 piano (samples), 2 plucked_chorus, 3 synth_brass, 4 bowed_string (splash), 5 plucked_string, 6 flute (samples), 10 sine |
| `-cs.Beethoven-fugue-b-flat-major`, `-sc.` same | `Beethoven.orc`, `Beethoven.scd` | 1 violin, 2 viola, 3 cello, 4 violin_2 (samples) |
| `-cs.Vina`, `-sc.Vina`, `-cs.raga`, `-cs.tryShruti` | `new-vina.orc`, `Vina.scd` | 1 Vina (slide): plucked string of the vina with slides |
| `-cs.Robot` | `0-default.orc` (not for sound) | 1 torso, 2 head, 3 leftarm, 4 leftforearm, 5 rightarm, 6 rightforearm: joints of the robot puppet, each with a parameter of its name (angle in degrees) |
| `-cs.Mozart` | `0-default.orc` | 1 piano |
| `-cs.tryCsound`, `-cs.checkAllCsound` | `tryCsound.orc`, `0-default.orc` | 1 The_default, 2 Flute (blurb), 3 Harpsichord, 4 Splashmachine (splash) |
| `-cs.tryTunings`, `-cs.Nadaka`, `-cs.trial.mohanam` | `0-default.orc` | plucked strings, synth brass, sine |

For SuperCollider, instrument NAME is played by SynthDef NAME; without a `-sc` file, instrument n by SynthDef `bp<n>` (`0-default.scd` defines `bp1`…`bp10`).

### Scales of the fixed tonality files

For `_scale(name,key)` with `-to.NAME` on top:

| File | Scales |
|---|---|
| `-to.12_scales`, `-to.tryHarmony` | just-intonation major and minor scales of every key (`Cmaj`, `Amin`, `F#maj`...); `-to.12_scales` also Indian `Sa01`…, `Ma01`…, `Ma_grama` |
| `-to.Mozart`, `-to.Bach_1st_prelude`, `-to.tryTunings` | the major/minor scales, and historical temperaments: `meantone_classic`, `bach_temperament`, `Zarlino_temp`, `pure_minor-third_meantone`, `piano` (equal)..., `-to.tryTunings` many French baroque temperaments (`d_alembert_rousseau`, `corrette`, `rameau_en_sib`...) and `equal_tempered` |
| `-to.raga` | scales of the thaats of North Indian music in just intonation, several versions each: `bilaval1`…`3`, `kalyan1`…`3` (Yaman), `khamaj1`…, `kaphi1`…, `asavari1`…, `bhairavi1`…`4`, `bhairao1`…, `todi1`…`4`, `purvi1`…, `marva1`…, `lalit1`…, and the murcchanas of the Ma-grama (`Sa_murcchana`, `Re1_murcchana`...) |
| `-to.Vina`, `-to.Nadaka`, `-to.trial.mohanam`, `-to.tryOneScale` | `just intonation` (`-to.Vina` also `rameau_en_sib`) |
| `-to.tryScales`, `-to.tryMPE`, `-to.tryShruti`, `-to.tryWait` | `Bohlen-Pierce`, `grama`, `Zarlino_natural`, `stretched_octave`, `twelve_fifths`, `zest24-supergoya17plus3_Db`, `meantone...`, `equal tempered`... |

List the exact names before using one, e.g. `python3 -c "import json,sys; print([s['Name of this scale']['value'] for s in json.load(open(sys.argv[1]))['Tonal scales']['value']])" ../tonality_resources/-to.raga`.

## Csound and SuperCollider files (-cs, -sc): JSON

Same format for both. **Don't create or change one unless asked**: the interface has pages to edit them (`php/csound.php`, `php/supercollider.php`), and fixed files can't be changed. When needed, copy a fixed file to `../csound_resources_private/` (or `../supercollider_resources_private/`) under a new name, and copy complete instrument objects within it. Main keys: `Csound orchestra file` (`CsoundOrchestraName`: the `.orc` or `.scd` file), `WhichCsoundInstrumentForChannel[n]` (instrument for MIDI channel n), and `Csound instruments` (`CsInstruments`), a list of instruments with `CsInstrumentName`, `CsInstrumentIndex`, `iargmax` (number of fields), indices of the fields for pitch (`CsPitchIndex`, format `CsPitchFormat` OPPC/OPD/CPS), volume, pitchbend, pressure, modulation, panoramic (start/end), and additional parameters (`ipmax`, `paramlist[k].name`, `.startindex`, `.endindex`, `.table`, `.defaultvalue`, `.GENtype`, `.combinationtype`).

## Real-time OSC and robots

In real-time OSC, each note sends (UDP, default 127.0.0.1:7400):

```
/bp/note id instrument name key freq duration velocity channel [name value]...
/bp/off  id instrument name key channel                 (at the end of the note)
/bp/env  id instrument name parameter time value ...    (course of a parameter during a note)
```

framed by `/bp/start` and `/bp/end`. The named values are the start and end values of the instrument's controls and parameters (`vol`/`vol2`, `pan`/`pan2`, `NAME`/`NAME2`...). Instrument names come from the `-sc` file if the project names one, else the `-cs` file, else `bp<n>`. Description: `../osc_resources/ReadMe.txt`, https://bolprocessor.org/osc-connection/.

**Robots.** A line `-py.NAME` on top of a project names the Python program `NAME.py` (in `../osc_resources/` or `../osc_resources_private/`) receiving its OSC; the interface shows START THE ROBOT. `dancer.py` and `dancer-with-flags.py` draw a puppet whose six joints are the instruments of `-cs.Robot`. Examples: `-gr.robot_dance` (notes and `_value()`), `-gr.robot_objects` (gestures as time-objects), `-da.robot_semaphore` (flag semaphore). Angles: torso and head lean left if positive; arms hang at 0, horizontal at 90, raised at 180 (limits -90…200); forearms straight at 0, bending upwards if positive. Each note moves a joint from its start value to its end value; with `_cont(joint)` the `_value()`s are the positions at given times. A new robot program can be written in Python (standard library) from `dancer.py`, saved in `../osc_resources_private/`. See https://bolprocessor.org/robot-puppet/.
