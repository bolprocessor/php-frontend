# Examples by technique

The examples of the folder `../ctests` (distributed and updated with the Bol Processor) cover almost every feature. Read the closest ones before writing a project. Each project names its resource files on its first lines; comments at the top often explain it. Online explanations are on https://bolprocessor.org (linked in the other reference files).

## Small grammars to start from

| Project | What it shows |
|---|---|
| `-gr.livecode1`, `-gr.livecode2` | one-rule grammars: a polymetric expression on two MIDI channels, with chords (used for live coding) |
| `-gr.tryTimePatterns` | a short grammar in smooth time with time patterns |
| `-gr.NotReich` | a short RND grammar layering patterns: each variable is a polymetric expression built on the previous ones; continuous volume |
| `-gr.tryMajorMinor` | `_scale()` changing between major scales (French convention) |
| `-gr.tryDerivationModes` | the derivation modes ORD, RND, LIN, SUB... on an alphabet of letters |
| `-gr.tryFlags` | flags counting and controlling rules |
| `-gr.tryrepeat`, `-gr.tryAllItems`, `-gr.tryAllItems1` | `_repeat()`, `_failed()`, `_goto()`; producing all items |
| `-gr.checkNegativeContext` | negative contexts `#X` |
| `-gr.tryPatternGrammar` | pattern rules with wildcards |
| `-gr.look-and-say` | the look-and-say sequence with SUB rules |
| `-gr.tryGraphics` | `_legato`/`_staccato` and the graphics |

## Complete pieces

| Project | What it shows |
|---|---|
| `-gr.Mozart` | Mozart's musical dice game: a form of measures (ORD), each chosen at random among numbered variants with weights `<K1>`…`<K9>` following the probabilities of two dice; `_scale()` gives just intonation according to the harmony of each measure; `-tb.Mozart` ticks. A model for **structured random music** |
| `-gr.koto3` | a kind of cellular automaton (SUB rules with wildcards and contexts) on sound-objects of `-so.abc1`, a weight `<K9>` controllable in real time |
| `-gr.765432`, `-gr.ShapesInRhythm` | large pieces for dance (Cronos production), polyrhythms, MIDI switches and scripts |
| `-gr.Alan`, `-gr.Beatrix` | large grammars (LIN, ORD, SUB1) with scripts; `-da.Alan`/`-da.Beatrix` show two performers interacting in real time |
| `-gr.Visser3`, `-gr.acceleration` | Harm Visser's pieces: a complex time structure and tonal structure built by transpositions of a single note |
| `-gr.Visser5`, `-gr.Visser.Shapes`, `-gr.Visser.Waves` | Harm Visser's pieces with serial tools `_rotate`, `_keyxpand` |
| `-gr.kss2` | a piece with General MIDI programs (`_script(MIDI program n)`) |
| `-gr.Ruwet` | theme and variations after Nicolas Ruwet's analysis (LIN, ORD) |
| `-gr.bells`, `-gr.cloches1` | bell-ringing patterns ("airs de sonneurs") |
| `-gr.12345678` | a Kathak tihai with performance controls on sound-objects |
| `-gr.asymmetric1` | African asymmetric rhythms (Chemillier), `_goto()` |
| `-gr.Ames` | a time structure with an undetermined rest |
| `-gr.Watch_What_Happens` | a grammar made from an imported MusicXML score |
| `-gr.Nadaka`, `-gr.trial.mohanam` | Carnatic improvisation in just intonation, Csound instruments |

## Indian music

| Project | What it shows |
|---|---|
| `-gr.dhadhatite`, `-gr.dhati`, `-gr.dhati2`, `-gr.dhin--` (with their `-da`) | qa'ida of tabla: bols as terminals (`-al`), homomorphisms for closed bols, templates, analysis of items, learning weights (`-wg.dhadhatite`) |
| `-da.kathak` | Kathak dance material (tintal, 16 beats) |
| `-gr.vina`, `-gr.vina2`, `-gr.vina3` | Carnatic songs on the vina: `_scale(just intonation,...)`, slides as a continuous parameter (`_cont(slide)`, `_value(slide,...)`), `-cs.Vina`, `-sc.Vina` |
| `-gr.tryRagas` | ragas with `-to.raga`, pitch bends for shrutis |
| `-gr.tryShruti` | the sequence of shrutis (syntonic comma) |
| `-tb.tintal`, `-tb.rupak`, `-tb.ektal`, `-tb.chautal`, `-tb.dhamar`, `-tb.kaharva` | time bases of talas (ticks) |

## Tunings and scales

| Project | What it shows |
|---|---|
| `-gr.tryOneScale`, `-gr.tryScales`, `-gr.tryTunings`, `-gr.tryHarmony` | `_scale()` with just intonation, temperaments, Bohlen-Pierce, stretched octaves |
| `-da.tryBohlen-Pierce`, `-da.try.zest24-supergoya17plus3_Db` | non-octave and large scales |
| `-da.tryMPE` | how microtonality works in Csound and real-time MIDI (pitch bends), https://bolprocessor.org/check-midi-microtonality/ |
| `-da.checkNoteOff` | notes and scales, NoteOn/NoteOff control |

## Scores (data projects) by feature

| Project | What it shows |
|---|---|
| `-da.checkPoly` | **everything about polymetric expressions** (116 items): periods, nesting, undetermined rests, tempo markers, sound-objects |
| `-da.tryRests` | undetermined rests `_rest` |
| `-da.trySpeeds` | `_tempo()` inside expressions, with a time base |
| `-da.checkTies`, `-da.checkBadDurations` | ties `&` |
| `-da.checkControls` | every MIDI control: `_vel`, `_volume`, `_pan`, `_mod`, `_press`, `_pitchbend`, `_switchon`, `_rndvel`, `_rndtime`, rates |
| `-da.checkContControl` | continuous controls (`_volumecont`...) |
| `-da.checkArticulation` | `_legato`, `_staccato` on sound-objects |
| `-da.checkCarryOver` | how controls carry over from one item to the next |
| `-da.trySerialTools` | `_retro`, `_rndseq`, `_ordseq`, `_rotate`, `_keyxpand`, `_keymap`, `_randomize` |
| `-da.tryRandomTime` | `_rndtime()` |
| `-da.tryMinimise` | minimising polymetric structures |
| `-da.tryPart` | `_part()` |
| `-da.tryWait`, `-da.tryStopContinue`, `-da.Alan`, `-da.Beatrix`, `-da.trySend`, `-da.tryReceive`, `-da.tryCapture` | scripts and interaction with MIDI input (real time: can't be tested here) |
| `Imported_MusicXML/-da.*` | scores imported from MusicXML (Beethoven, Bach, Liszt, Couperin...), with their settings |

## Sound-objects (time-objects)

| Project | What it shows |
|---|---|
| `-da.tryMiscObjects` (`-so.tryObjects`) | many examples of sound-objects: pivots, cyclic objects, truncation, covering, rescaling |
| `-da.checkPoly` (`-al.abc1`, `-so.abc1`) | sound-objects with MIDI codes in polymetric structures |
| `-gr.tryKeyMap` (`-so.tryKeyMap`), `-gr.tryKeyXpand` | key mapping and expansion of objects |
| `-gr.tryCsoundObjects`, `-da.tryCsoundObjects` (`-so.tryCsoundObjects`, `-cs.tryCsoundObjects`) | objects with Csound scores, additional parameters |
| `-da.trySilentSoundObjects` | silent sound-objects |
| `-gr.robot_objects` (`-al`, `-so.robot_objects`) | gestures as time-objects: a clap with its pivot on the impact, never rescaled; a cyclic wave; a nod with dilation limits |

## Csound, SuperCollider, OSC, robots

| Project | What it shows |
|---|---|
| `-da.tryCsound`, `-da.checkAllCsound`, `-gr.checkAllCsound` | Csound instruments, `_ins()`, additional parameters, pitch bend and volume tables |
| `-gr.vina3` | a piece for real-time Csound or SuperCollider (`-cs.Vina`, `-sc.Vina`) with slides |
| `-gr.Visser5`, `-gr.Visser.Shapes` | pieces meant for Csound or SuperCollider (`-cs.0-default`, `-sc.0-default`) |
| `Imported_MusicXML/-da.Beethoven-fugue-b-flat-major` | a string quartet with sampled instruments (`-cs`/`-sc.Beethoven-fugue-b-flat-major`) |
| `-gr.robot_dance` | **a robot choreography** in real-time OSC: joints as instruments of `-cs.Robot`, angles as `_value()`, random figures (RND), `-py.dancer` |
| `-gr.robot_objects` | the same robot with gestures as time-objects |
| `-da.robot_semaphore` | spelling sentences in the flag semaphore alphabet: one time-object per letter, `-py.dancer-with-flags` |

## Time bases

`-tb.onebeat` (a tick per beat), `-tb.niceticks`, `-tb.Mozart`, `-tb.Waves`, `-tb.koto3`, `-tb.slowshift`, `-tb.trySpeeds`, the talas above. Projects naming one: `-gr.Mozart`, `-gr.Visser.Waves`, `-gr.Visser5`, `-gr.Ruwet`, `-da.trySpeeds`.

## Finding more

```sh
grep -l '_keyxpand' ../ctests/-*        # projects using a tool
grep -h '^//' ../ctests/-gr.Mozart       # the comments of a project
```
