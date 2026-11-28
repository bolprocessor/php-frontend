---
name: bp-compose
description: Create, improve, explain or debug Bol Processor (BP3) projects - grammars (-gr), data/scores (-da), settings (-se), alphabets (-al), sound-objects (-so), tonality (-to), Csound/SuperCollider instruments (-cs, -sc), time bases (-tb) - and test them with the console. Use for any musical request about the Bol Processor.
---

# Composing with the Bol Processor

The Bol Processor produces music from **grammars** (rules rewriting symbols, `-gr` files) or **scores** typed in **data** projects (`-da` files). A score is a line of text: notes, silences, polymetric expressions `{...}` and performance controls `_vel(...)`. The same score can be played in MIDI, written to a MIDI file, a Csound score, or sent in real time to Csound, SuperCollider or OSC programs (e.g. robots).

## Reference files (read what the task needs)

| File | Content |
|---|---|
| [reference/scores.md](reference/scores.md) | notes, silences, sequences, polymetric expressions, tempo, performance controls, serial tools |
| [reference/grammars.md](reference/grammars.md) | layout of a grammar, rules, subgrammar modes, weights, flags, contexts, repetitions, procedures |
| [reference/data.md](reference/data.md) | data projects: items, comments, how the interface plays them |
| [reference/files.md](reference/files.md) | header lines and the formats of `-se`, `-al`, `-so`, `-to`, `-tb` files; how to create them |
| [reference/instruments.md](reference/instruments.md) | Csound, SuperCollider and OSC: `-cs`/`-sc` files, `_ins()`, additional parameters, robots |
| [reference/examples.md](reference/examples.md) | catalogue of the examples of `../ctests` by technique: **start here to find a model** |
| [reference/verify.md](reference/verify.md) | how to test with the console (script `scripts/bptest.sh`) and read the results |
| [reference/pitfalls.md](reference/pitfalls.md) | traps learned from experience |
| [reference/code.md](reference/code.md) | map of the PHP and C code, only for changes to the program asked by the user |

The pages of https://bolprocessor.org explain each topic in depth (links in the reference files); fetch them when a detail is missing. The C source in `../source/BP3/` is the final reference when documentation and behaviour disagree.

## Workflow

1. **Understand the request in musical terms.** Restate it briefly if it is ambiguous. Ask only questions whose answer changes the result (instruments or output format, length, style, tuning); choose sensible defaults for the rest and say which.
2. **Read before writing.** For an improvement, read the project and every file named on its first lines. For a new project, find the closest examples in [reference/examples.md](reference/examples.md) and read them: adapting a working example is faster and safer than starting from nothing.
   - **For a new piece, look first at the user's own library**: their projects and imported scores are the best guide to their taste (harmony, instruments, forms, tempo, notation). List the projects of `../ctests/`, `../ctests/Imported_MusicXML/` and the other folders of the root holding `-gr.`/`-da.` files (Glob), and set aside those listed by name in [reference/examples.md](reference/examples.md), which come with the distribution; keep all the scores of `Imported_MusicXML/`, distributed or imported by the user, which are models of real harmony and part writing. Among these, read those whose name, first comments or titles suggest the style, instrument or form asked (reading the first lines is enough to choose; read a long imported score only in part, e.g. a few measures). Take from them what fits the request (chord voicings, progressions, rhythms, settings, instruments) without copying them, and say in your report which ones inspired you. If the user names a piece to follow ("in the style of -da.X"), read it first. Skip this step when the request is self-contained (a scale, a technical test, an explanation).
3. **Write the files.**
   - New projects go in `../ctests/`, the folder of the examples, where the user finds them on the home page of the interface. Name them after the piece: `-gr.NAME` or `-da.NAME`, with `-se.NAME` (settings) and, when needed, `-al.NAME`, `-so.NAME`, `-to.NAME`, `-tb.NAME`. The header lines of the project name them (see [reference/files.md](reference/files.md)).
   - **Never overwrite a file of `../ctests/` when creating a project**: before writing, look for the names you chose (Glob `../ctests/*.NAME` and `../ctests/*.NAME_bak`). If any file of the project would take an existing name, choose another NAME for all its files (e.g. `NAME2`, or a more specific name) and tell the user. Prefer specific names (`-gr.RainyMorningRaga` rather than `-gr.Raga`), which are unlikely to be taken by an example added later by an update. Instruments (`-cs`, `-sc`) and scales (`-to`) can also be taken from the resource folders by name.
   - Start each file with a comment saying what it is, and comment the rules or items so that the user can understand and change them.
   - Before changing an existing file, say what will change, and keep the former version as `NAME_bak` next to it (e.g. `-gr.Mozart_bak`), as the interface does when it saves a file; the interface doesn't list `_bak` files. Do this once per conversation, before your first change of that file, so that the user can come back to the version they had. Never change the fixed resource folders (`../*_resources/` without `_private`).
4. **Test** with `scripts/bptest.sh` (see [reference/verify.md](reference/verify.md)) until it compiles, produces, and the notes match the request (durations, pitches, instruments, number of items). Read the Csound score lines of the test: they list every note with its date and duration. Fix and test again.
5. **Report** to the user, who is a musician reading your answer in a terminal window: write plainly, without file paths, URLs, code names of the program or details of your tests unless asked. Keep it short enough to read without scrolling much; the user can ask for more.
   - the files created or changed, and what each part does (in musical terms);
   - how to listen, in the words of the interface:
     - a project the user is working on (named in `[Project: ...]`, its page is open in the browser): "reload the page of the project in your browser";
     - a new project: "on the home page of the Bol Processor, click the folder ctests, then the file -gr.NAME";
     - then the output format to choose on that page (MIDI file, real-time MIDI, Csound, SuperCollider, OSC) and the button (PRODUCE ITEM(S) for a grammar, PLAY on an item of a data project);
   - for a question (an explanation, no change), just answer it: no listening instructions unless they help;
   - what you could not check (you can't hear the result) and, when useful, a few suggestions for variations.

## Rules

- **Never play in real time** (no `--rtmidi`, no `--csoundrt`, `--scrt` or `--osc` without `--rt-dump`), never start Csound or SuperCollider servers or Python programs. The user plays the result in the interface.
- Keep the user's style: when improving a project, change what was asked and keep the rest, including comments and the layout.
- Prefer simple, readable solutions: a grammar a musician can read and modify is better than a clever one.
- Files are UTF-8. File names starting with `-` must be written `./-gr.NAME` in shell commands.
- **Commands**: the user is asked to approve every command that the package doesn't allow, which is tedious for a musician. So read and search files with your own tools (Read, Grep, Glob), not with `cat`, `sed` or `python3`; write files with your own tools; create settings with `scripts/bpsettings.sh` ([reference/files.md](reference/files.md)); check notes with `bptest.sh --notes`. Run one command at a time from `AI_Claude`, with relative paths (`../ctests/...`), without `cd`, pipes, loops or scripts of your own: e.g. test several seeds with one `bptest.sh` command each. Keep the former version of a file (`NAME_bak`) by reading it and writing the copy.
- **Large tasks: ask first.** The user's usage counts in the limits of their Claude plan, and may be charged as extra usage beyond them; you can't see their account, so never give a price. Before a task likely to be large, say so in plain words and wait for their agreement, e.g. "This is a long task: I'll write a grammar of about 50 rules and test it several times. It will use a noticeable part of your allowance. Shall I go on?" Large means: a long piece (several minutes of music, or many rules or items), several versions to compare, reading long imported scores, many test runs, or any change to the program (PHP or C). Small requests (an explanation, a short fix, a short piece) go ahead without asking. If a task turns out larger than announced (e.g. tests keep failing), stop, say where you are, and ask before going on.
- Numbers of data items are counted from 1, as in the interface (item #1 is the first).
- If something in these files proves wrong, tell the user, so that it can be reported to the author of the Bol Processor.
