// Bol Processor BP3: SynthDefs of "-sc.Beethoven-fugue-b-flat-major" for real-time SuperCollider (2026-11-20)
// String quartet playing the recordings of "Beethoven.orc" (University of Iowa Musical Instrument Samples, see
// csound_resources/Iowa_strings/ReadMe.txt): violin (part 1), violin_2 (part 2, the violin recordings), viola (part 3), cello (part 4).
// As in Beethoven.orc:
//    - the dynamic (pp, mf, ff recordings) is chosen by the velocity (_vel()), or the beginning volume if there is none
//    - each recording is tuned to its measured pitch and looped with a crossfade when the note is longer
//    - beyond the range of its recordings, an instrument borrows those of its neighbour (in the sample maps)
//    - slow bow attacks of the recordings are shortened for short notes
//    - a vibrato is added, its depth set by _mod() (normal without modulation), with a slow intonation drift
//    - pan, pan2: _pan() (0-127, 64 = centre)
// See "0-default.scd" for the arguments sent by the Bol Processor.
// BP_SAMPLES 3 Iowa_violin.json
// BP_SAMPLES 4 Iowa_viola.json
// BP_SAMPLES 5 Iowa_cello.json

(
var fxbus = 1020; // reverb bus of bp_fx (0-default.scd)

// One string instrument: sample set, vibrato depth (cents) and rate (Hz), release (seconds)
var string = { |set, vibdepth, vibrate, release|
	{ |freq = 440, sustain = 1, vol = 90, vol2 = 90, vel = 0, pan = 64, pan2 = 64, mod = 0, mod2 = 0, bend = 0, bend2 = 0, gate = 1|
		var hasvel = vel > 0;
		var dyn = Select.kr(hasvel, [vol, vel]).clip(1, 127);
		var gain = Select.kr(hasvel, [1, (vel / 127).squared]);
		var volgain = (Line.kr(vol, vol2, sustain) / Select.kr(hasvel, [127, 90])).squared;
		var layer = (dyn >= 50) + (dyn >= 96); // pp, mf, ff
		var key = freq.cpsmidi.round;
		var allow = 0.07 + (0.12 * sustain.min(2)); // the attack may last 70 ms for short notes, up to 310 ms for notes of 2 seconds or more
		var modf = Select.kr((mod.abs + mod2.abs) > 0, [1, Line.kr(mod, mod2, sustain) / 8192]);
		var depth = vibdepth * (0.85 + (0.3 * dyn / 127)); // a little wider when louder
		var vdep = depth * (1 + LFNoise1.kr(1.3, 0.2)) * EnvGen.kr(Env([0, 0, 1], [0.06, 0.35])) * modf;
		var vib = SinOsc.kr(vibrate + LFNoise1.kr(1.7, 0.35), Rand(0, 2pi));
		var f = freq * (Line.kr(bend, bend2, sustain) / 100).midiratio * (((vib * vdep) + LFNoise1.kr(0.7, 2)) / 100).midiratio;
		var env = EnvGen.kr(Env([0, 1, 1, 0], [0.006, max(sustain - 0.006, 0.001), release], [0, 0, -3]), doneAction: 2);
		var sig = ~bpSample.(set, 1, key, layer, f, allow) * env * (1 + (0.004 * vib * vdep)) * gain * volgain * 0.7;
		sig = sig * EnvGen.kr(Env.asr(0, 1, 0.08), gate, doneAction: 2); // released when the Bol Processor stops
		sig = Pan2.ar(sig, (Line.kr(pan, pan2, sustain) / 63.5 - 1).clip(-1, 1), 2.sqrt);
		Out.ar(0, sig);
		Out.ar(fxbus, sig * 0.35);
		}
	};

SynthDef(\violin, string.(3, 16, 5.9, 0.10)).add;
SynthDef(\violin_2, string.(3, 15, 5.7, 0.10)).add;
SynthDef(\viola, string.(4, 15, 5.6, 0.12)).add;
SynthDef(\cello, string.(5, 13, 5.3, 0.16)).add;
)
