# Pitfalls

Traps met while making projects with the Bol Processor. Most are caught by the test script; knowing them saves iterations.

## Testing

- **The console exits with code 0 even when compilation fails** or nothing is produced. Judge from the log (`Compilation failed`, `Can't make sense of`) and the outputs: the test script does it.
- **Run the console from `../php`**, as the interface does: paths of reserved folders are relative to it.
- **One output format per run**: with `--csoundout`, the MIDI file of the same run is empty. The script makes separate runs.
- Random productions differ from one run to the next unless a seed is given (`--seed 1` in the script; setting `Seed`, 0 meaning a new random sequence each time).

## Data projects

- `[comments]` inside a line of a data item are fine: the interface removes them before playing, and the console skips them (consoles compiled before 2026-11-27 stopped reading the line at such a comment, and could cut a long line containing non-ASCII characters such as `—`).
- **A paragraph starting with `-` or `//` is not an item**: `- C4 D4` (starting with a silence) is skipped by the interface, taken for a header line. Start such an item with another form of silence (`1 C4 D4`, `{1} C4 D4`) or a control (`_vel(64) - C4 D4`).
- Items are separated by blank lines; a title `[...]` may be on the line above the score, without a blank line between them.
- Data items are numbered from 1 in the interface (#1 is the first item).

## Grammars

- **A variable that no rule rewrites stays in the item and is silent**, without any error. Check the items (the script lists words that look like leftover variables).
- **Variables start with an uppercase letter**; a lowercase word that is neither a note of the current convention nor a terminal of the alphabet is an error (`Can't make sense of "zork"`).
- **Note names must follow the note convention** of the settings: `C4` in English, `do3` in French, `sa4` in Indian. With French settings, `C4` compiles **without error** as a variable (uppercase) and is silent, while a lowercase name of another convention fails to compile. Compare the number of notes in the test with the number expected. The items are written in the convention of the settings.
- A recursive rule needs a way out (a flag counting down, weights that decrease, a subgrammar ending with `ORD` rules that remove the variable), otherwise the derivation never ends and stops at the time limit (`MaxConsoleTime`) with nothing produced. Some grammars loop only with some seeds.
- In `RND` subgrammars, a variable rewritten by several rules gets a different choice at each occurrence. To repeat the same choice, use a master/slave `(= X) (: X)` (grammars.md).

## Time

- **The first field of a polymetric expression sets its duration.** `{C3 G3, C4 D4 E4 F4}` lasts 2 beats (the melody is compressed); write the melody first, or give the duration: `{4, C3 G3, C4 D4 E4 F4}`.
- `_tempo()` inside a field applies until the end of that field only.
- The metronome is `Pclock/Qclock` = period of a beat in seconds (not beats per minute): 90 beats per minute is Pclock 2, Qclock 3. `_mm(90)` in a grammar is simpler.
- `_rndtime(x)` has no effect when x is smaller than the quantization (10 ms by default).
- Time base files (`-tb`) are found in the top folder of the workspace (e.g. `AI_Claude/`), not in a subfolder.

## Sound-objects

- **A cyclic object is repeated only if its rescale mode is not `OK_RESCALE`**: with `OK_RESCALE` it is stretched instead. Use `NEVER_RESCALE` (or dilation limits) for an object whose cyclic part should repeat (`wave` in `-so.robot_objects`).
- A terminal of the alphabet without a prototype is silent: the alphabet must name the `-so` file (`-so.NAME` line), and the prototype `Name` must be the terminal.
- A `-so` file is JSON: check it parses (`python3 -m json.tool FILE > /dev/null`) after writing it.
- Sound-objects with a Csound score only produce nothing in MIDI, and those with MIDI codes only produce nothing in Csound.

## Instruments

- **An additional parameter name is attached to a single instrument**: with the same parameter name in several instruments, `_value(name,...)` switches every note to the last instrument declaring it. Give each instrument its own names (`-cs.Robot`).
- `_ins(name)` with a name absent from the `-cs`/`-sc` file gives `=> Instrument "name" was not found`, and the notes go to instrument 1.
- In MIDI formats `_ins()` is ignored; in Csound, SuperCollider and OSC formats `_chan()` only selects an instrument through the channel table of the `-cs` file.
- Notes of duration 0 (out-time `<<C4>>`) are not sent to SuperCollider and send no `/bp/off` in OSC.

## Files

- File names starting with `-` look like options: `./-gr.NAME` in shell commands.
- Text files are UTF-8; LF or CRLF line endings (the interface writes CRLF). Never CR alone.
- JSON files (`-se`, `-so`, `-to`, `-tb`, `-cs`, `-sc`): write valid JSON, keep the structure of the file you copied, and the `header` field naming the file.
- The interface keeps the former version of a file it saves as `NAME_bak`; it doesn't list `_bak` files.
