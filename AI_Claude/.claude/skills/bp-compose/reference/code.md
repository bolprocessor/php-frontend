# Map of the code

Only for changes to the program asked by the user. First read the rules in `AI_Claude/CLAUDE.md` (warning about updates, record in `my_changes/`).

The installation has two parts: `../source/BP3/` (C console `bp`, `bp3` on Linux, `bp.exe` on Windows) and `../php/` (web interface, served by MAMP, XAMPP or the BolProcessor app, which builds the console command lines and shows the results).

## Building the console

From the root folder: `make` (`mingw32-make` on Windows); `make clean` first when switching modes; `make WARN=1` shows compiler warnings. Requires gcc (Xcode Command Line Tools on MacOS, MinGW on Windows) and libcurl, plus CoreMIDI on MacOS, ALSA on Linux. The interface's `compile.php` runs `make clean && make` and writes errors to `bp_compile_result.txt`. The Makefile takes every `source/BP3/*.c`.

## Production pipeline (C)

`main()` in `ConsoleMain.c` loads the input files, then dispatches on the action:

1. **Compile**: `CompileCheck()` (`Interface2.c`) calls `CompileGrammar()`; `Encode.c` turns text into arrays of `tokenbyte`.
2. **Derive**: `ProduceItems()` (`ProduceItems.c`) calls `Compute()` (`Compute.c`), which applies the rules through the subgrammars.
3. **Expand**: `Zouleb()` (`Zouleb.c`): serial tools (`_retro`, `_rndseq`, `_rotate`...), at the end of each item.
4. **Play**: `PlayBuffer()`/`PlayBuffer1()` (`PlayThings.c`) call `PolyMake()` (`Polymetric.c`, normalises polymetric expressions), `TimeSet()` (`TimeSet.c`: `FillPhaseDiagram()`, `SetTimeObjects()`), then `MakeSound()` (`MakeSound.c`), which sends events to real-time MIDI (`MIDIdriver.c`), MIDI files (`MIDIfiles.c`), Csound scores (`Csound*.c`), real-time Csound/SuperCollider/OSC (`CsoundRealTime.c`) or event lists (`EventListfiles.c`).

The `play` action encodes the data and enters at step 4. Command-line options are parsed in `ParsePostInitArgs()` (`ConsoleMain.c`); `../bp --help` lists them.

## Conventions of the C code (from its Classic Mac origins)

- Memory: `Handle`s from `GiveSpace()`, `MySetHandleSize()`, `MyDisposeHandle()` (`ConsoleMemory.c`); hence `tokenbyte ***pp_a` and `(*p_a)[i]`. Handles move on resize: never keep derived pointers across a resize.
- Input text lives in text handles such as `TEH[wGrammar]`, `TEH[wData]` (`CTextHandles.c`); there are no real windows. `ConsoleStubs.c` stubs out GUI calls; code under `BP_CARBON_GUI_FORGET_THIS` is disabled.
- Token scans rely on finding `TEND TEND` at the end of a work string: never start a scan past the end.
- Return codes: `OK` (1), `MISSED` (0), negative codes such as `ABORT`, `EXIT` (`-BP3.h`); callers must propagate `ABORT` and `EXIT`.
- Globals are defined in `-BP3main.h` (included only by `ConsoleMain.c`), each with an `extern` in `-BP3decl.h`; prototypes in `-BP3.proto.h`.
- Messages: `BPPrintMessage(0, odInfo|odError|odWarning|..., ...)`, not `printf`; error messages start with `=>`.
- Style: `f() {` on one line, body indented by one tab, closing `}` aligned with the body.
- Token encoding (`T0`, `T3`...) is described in `../docs-developer/BP3-info.txt` if present (old: check against the code).

## Coupling between C and PHP

- `php/produce.php` builds the console's command line: a new console option needs a change there to be used by the interface.
- Settings (`-se`) fields are read in `SaveLoads1.c` and defined for the interface in `php/settings_names.tab`: a new setting touches both.
- The console and the browser communicate through files in `../temp_bolprocessor/`: with `--traceout X.txt`, the console creates `X_stop`, `X_done`; it polls `messages/_pause`, `_continue`, `_panic`. Graphics are produced only with `--traceout`.
- Reserved paths (`../temp_bolprocessor/...`, `../csound_resources/...`) assume the working directory is `php/`.
- Real-time Csound, SuperCollider and OSC: `php/_csoundrt.php`, `php/_scrt.php`, `php/_oscrt.php`; console side `CsoundRealTime.c`.

## PHP interface

| File | Role |
|---|---|
| `_basic_tasks.php` | functions shared by every page (included first), settings of the installation in `_settings.php` (rewritten by the interface: never edit by hand) |
| `index.php` | home page and folder lists |
| `grammar.php`, `data.php` | project pages; `data.php` splits items (`create_parts()`) |
| `produce.php` | runs the console and shows the results (window opened by PRODUCE/PLAY) |
| `settings.php`, `alphabet.php`, `prototype.php`, `timebase.php`, `tonality.php`, `scale.php`, `csound.php` | editors of the resource files |
| `compile.php` | recompiles the console |

Resource folders: always resolve names with the helpers of `_basic_tasks.php` (`resource_path($kind, $name)`, `csound_resource_path()`, `tonality_resource_path()`), which look in the user's `_private` folder first. Pages call `checksaved()` before actions that reload them. Variables at page level must not reuse generic names such as `$key` (they collide with included code).

## Testing a change

Run the projects concerned with the test script before and after the change, and compare (`diff` of the `.sco` files, number of notes). For PHP changes, reload the page in the browser and ask the user to check it; PHP errors appear in the page or in the server's log (MAMP: `/Applications/MAMP/logs/php_error.log`).
