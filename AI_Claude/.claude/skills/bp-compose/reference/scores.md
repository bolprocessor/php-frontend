# Writing scores

A Bol Processor score is a line of text: notes, silences, structures and performance controls. It is typed in data projects, produced by grammars (each result is an **item**), or imported from MIDI or MusicXML. The syntax doesn't depend on the output (MIDI, Csound, SuperCollider, OSC). Online: https://bolprocessor.org/bol-processor-syntax/ (tutorial with links to each topic).

## Notes

A simple note is a pitch name followed by an octave number. The **note convention** is set in the settings (`NoteConvention`, see files.md) or by the console options `--english`, `--french`, `--indian`, `--keys`:

| Convention | Note names | Middle C (key 60) |
|---|---|---|
| English (0) | `C C# D D# E F F# G G# A A# B`, also `Db Eb Gb Ab Bb Cb Fb B# E#` | `C4` |
| Italian/French (1) | `do do# re re# mi fa fa# sol sol# la la# si`, also `reb mib solb lab sib`... | `do3` |
| Indian (2) | `sa rek re gak ga ma ma# pa dhak dha nik ni` | `sa4` |
| Key numbers (3) | `key#0` to `key#127` | `key#60` |

- In Italian/French, octave numbers are one less than in English: `do3` = `C4`. The lowest octave is written `00`: `C00` (key 0), `do000`.
- `key#60` is always accepted, whatever the convention.
- Pitches can be changed by a microtonal scale (`_scale()`, see Performance controls).
- Which notes are produced depends on the convention: a grammar in English convention produces `C4`, and when the settings say French, the items show `do3`.

## Sound-objects and terminal symbols

Besides notes, a score can contain **terminal symbols** declared in an alphabet file (`-al`), such as the bols of tabla `dha ti ge na`. When a prototype file (`-so`) gives them MIDI codes or Csound score lines, they become **sound-objects** (or time-objects: they can also move a robot), with timing properties of their own (pivot, dilation limits, cyclic part...). Without a prototype, a terminal is **silent**: it takes time like a note but plays nothing. See files.md and https://bolprocessor.org/sound-objects/.

- Symbols that could be confused with other elements are written between single quotes: `'1' '2'`.
- When the alphabet makes it unambiguous, terminals may be written without spaces: `abc` = `a b c` if the alphabet has `a`, `b` and `c`.

## Sequences, beats and silences

Each note or sound-object lasts **one beat**, whose duration is set by the metronome of the project (settings `Pclock`/`Qclock`, or `_mm()` in a grammar; 60 beats per minute by default).

| Symbol | Meaning | Example |
|---|---|---|
| `_` | prolongation: the preceding note lasts one more beat | `C4 _ _ D4` (C4 lasts 3 beats) |
| `-` | silence of one beat | `C4 - D4` |
| integer or fraction | silence of that number of beats | `C4 1/2 D4`, `C4 3/2 D4` |
| `.` | beat separator: each group between periods lasts one beat | `C4 D4 . E4 F4 G4` |
| `_rest` or `…` | undetermined rest: a silence whose duration the Bol Processor calculates | `C5 .D5 .G5 . _rest F5 G5 C5` |
| `&` | tie between two notes of the same pitch | `C4& &C4` |
| `<< >>` | out-time object: played at its date with no duration | `<<C4>>` |

- `C4 D4 . E4 F4 G4`: two notes of half a beat, then three notes of a third of a beat. It is the same as `{C4 D4 . E4 F4 G4}`.
- Ties also work across the fields of polymetric expressions: `{C4 D4& E4 F4, C3 D3 &D4 F3}`. A tie with no matching note is ignored. See https://bolprocessor.org/tied-notes/.
- Out-time objects are used as synchronisation signals or for percussive sounds.

## Polymetric expressions

The basic model of time. Between braces, fields separated by commas are played **at the same time**:

```
{C4 D4 B3, A#3 G3}
```

- The **first field** (leftmost) sets the duration of the expression; the other fields are stretched or compressed to the same duration. Above, `C4 D4 B3` lasts 3 beats, so `A#3` and `G3` last 1.5 beats each.
- The order of the fields matters: `{A#3 G3, C4 D4 B3}` lasts 2 beats, its second field has three notes of 2/3 beat.
- A first field made of a number sets the duration: `{3, C4 D4 E4 F4}` plays four notes in 3 beats. `{4}` alone is a silence of 4 beats.
- Expressions can be nested at any depth: `{{C4 D4, E4 F4 G4}, E5}`.
- At the top level of a line, braces may be omitted: `C4 D4, E4 F4 G4` = `{C4 D4, E4 F4 G4}`.
- Chords: `{C4, E4, G4}` (three fields of one note). With a duration: `{2, C4, E4, G4}`.
- A melody against a pulse: `{C4 D4 E4 F4 G4 A4, C3 G3}` (6 notes against 2).
- Fields of different lengths make polyrhythms: `{4, C4 E4 G4, C3 G3 C3 G3 C3}` (3 against 5 in 4 beats).

Expressions are expanded before time-setting; "Expand selection" in the interface shows how one is read. See https://bolprocessor.org/polymetric-structure/.

## Tempo and time

| Notation | Meaning |
|---|---|
| `_tempo(x)` | multiplies the speed of what follows by `x` (integer, decimal or fraction): `_tempo(2)`, `_tempo(0.7)`, `_tempo(2/3)`. Inside a polymetric expression, it applies until the end of its field |
| `/n` | absolute speed marker (older notation): `/3 {C4 D4 B3, A#3 G3}` is played at speed 3. Prefer `_tempo()` |
| `_mm(x)` | metronome in beats per minute, at the start of a grammar: `_mm(120)` |
| `_striated` | striated time: regular beats set by the metronome (the default) |
| `_smooth` | smooth time: no regular beats; durations may follow time patterns (grammars.md) |
| `_breath` | marks the next silence as a breath, which also pauses the ticks of a time base |

## Performance controls

They start with an underscore and modify the notes and sound-objects that follow, until another value is given or until the end of the enclosing field or expression.

```
_chan(1) _vel(50) _volumecont _volume(80) C4 D4 E4 F4 _volume(30)
```

| Control | Effect |
|---|---|
| `_chan(n)` | MIDI channel 1 to 16 |
| `_ins(n)`, `_ins(name)` | Csound, SuperCollider or OSC instrument, by number or name (instruments.md) |
| `_part(n)` | part of a score imported from MusicXML |
| `_vel(x)` | velocity, 0 to 127 |
| `_volume(x)` | volume (MIDI controller 7 by default, `_volumecontrol(n)` to change it) |
| `_pan(x)` | panoramic (controller 10 by default, `_pancontrol(n)`) |
| `_mod(x)` | modulation |
| `_press(x)` | channel pressure (aftertouch) |
| `_pitchbend(x)` | pitch bend, in cents if `_pitchrange(cents)` was set: `_pitchrange(200) _pitchbend(-200)` |
| `_legato(x)`, `_staccato(x)` | lengthens or shortens the notes by x percent; `_legato(0)` restores them |
| `_transpose(x)` | transposition in semitones (may be fractional) |
| `_scale(name,key)` | plays the following notes in a microtonal scale of the tonality file (`-to`), with its block key: `_scale(just intonation,0)`, `_scale(Cmaj,0)`. The block key is the key whose pitch is kept when changing scales (0 means 60, i.e. C4; a note name of the current convention is also accepted). See https://bolprocessor.org/microtonality/ |
| `_switchon(n,c)`, `_switchoff(n,c)` | MIDI switch n (64 to 95) on channel c, e.g. sustain pedal `_switchon(64,1)` |
| `_rndvel(x)` | random variation of velocities, 0 to 64 |
| `_rndtime(x)` | random shift of dates, in milliseconds (no effect below the quantization, 10 ms by default) |
| `_srand(n)` | seed of the random generator, for repeatable results |
| `_script(...)` | instruction executed during the performance: `_script(MIDI program 5)` (General MIDI instrument on the current channel), `_script(wait for C3 channel 1)`, `_script(MIDI send Start)` |
| `_capture(n)` | captures incoming MIDI events |

**Stepwise, continuous and fixed values.** By default a value changes at the next note (step mode). The `cont` forms make changes continuous between two values: `_velcont`, `_volumecont`, `_pancont`, `_modcont`, `_presscont`, `_pitchcont`, `_articulcont`, `_transposecont`. The `step` and `fixed` forms (`_volumestep`, `_volumefixed`...) restore stepwise changes or keep the value fixed. The rate forms (`_volumerate(n)`, `_pitchrate(n)`...) set how often continuous MIDI changes are sent.

```
_velcont _vel(10) Bb4 A5 Bb4 A5 Bb4 A5 Bb4 A5 _vel(80)
```

**Additional parameters** of Csound, SuperCollider or OSC instruments are named in the instrument file and set with `_value(name,x)`, in the mode given by `_cont(name)`, `_step(name)` or `_fixed(name)`. Values may be placed between the prolongations of a note, so that they change during it:

```
_ins(Flute) _cont(blurb) _value(blurb,544.5) C4__ _value(blurb,120) __ _value(blurb,-37)___ _value(blurb,0)
```

Values are carried from one item to the next only as explained on https://bolprocessor.org/carrying-over-controls/. Script instructions: https://bolprocessor.org/interactions-in-midi-and-csound/.

## Serial tools

They transform the expression that follows them, once the item has been produced:

| Tool | Effect |
|---|---|
| `_retro` | plays the expression backwards |
| `_rndseq` | plays its elements in a random order |
| `_ordseq` | sorts its elements by pitch |
| `_randomize` | resets the random sequence |
| `_rotate(n)` | rotates the sequence by n positions (negative to the left) |
| `_keyxpand(key,x)` | multiplies intervals around a pivot key; `_keyxpand(C3,-1)` mirrors the melody around C3 |
| `_keymap(...)` | remaps a range of keys onto another |

```
_retro {A4 B4, C4 D4 E4}
_transpose(-6) _rotate(-2) {{M1 M2}, {_keyxpand(C3,-1) M1}}
```

Examples: `-da.trySerialTools`, `-gr.tryKeyXpand`, `-gr.tryKeyMap`, `-gr.Visser.Shapes`. See https://bolprocessor.org/serial-tools/.

## Comments

- A line starting with `//` is a comment.
- Text between square brackets is a comment: `[good] C4 D4& E4 &D4 F4`. In data projects, a bracketed comment at the start of an item, or on its own line above it, is its title; other comments may be placed anywhere in the item (e.g. measure numbers `[—2—]` in imported scores). In a grammar rule, a comment ends the rule: nothing after it is read.
