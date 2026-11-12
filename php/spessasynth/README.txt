Off-line MIDI file player for the Bol Processor interface

Selected by $midi_player = "SpessaSynth" in php/_basic_tasks.php.
bp_midi_player.js defines BPMidi.play(url), pause(), resume(), stop().

Contents (installed 2026-11-12):
- spessasynth_lib.js            npm spessasynth_lib 4.4.8, dist/index.js
- spessasynth_processor.min.js  npm spessasynth_lib 4.4.8, dist/spessasynth_processor.min.js
- spessasynth_core.js           npm spessasynth_core 4.4.10, dist/index.js
- fflate.js                     npm fflate 0.8.3, esm/browser.js
- stb-vorbis.js                 npm stb-vorbis 0.0.7, dist/index.js
- GeneralUserGS.sf3             GeneralUser GS sound bank (SF3 version from
                                https://github.com/spessasus/SpessaSynth/tree/master/soundfonts)
- LICENSE-*.txt                 licences of the above (Apache-2.0, MIT, GeneralUser GS licence)

To update without a bundler, copy the same files from the npm packages and
change the imports to relative paths:
  spessasynth_lib.js:  from "spessasynth_core"  ->  from "./spessasynth_core.js"
  spessasynth_core.js: from "fflate"            ->  from "./fflate.js"
                       from "stb-vorbis"        ->  from "./stb-vorbis.js"
The "//# sourceMappingURL=" lines were removed because the .map files are not included.

The page must be served over http (MAMP/XAMPP), not opened as file://,
because the player uses JavaScript modules and an AudioWorklet.
