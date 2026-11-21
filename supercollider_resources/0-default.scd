// Bol Processor BP3: default SynthDefs for real-time SuperCollider (2026-11-20)
// They play the instruments described by "-sc.0-default", under the same names: the piano and the flute play recordings
// (sample sets 1 and 2 below, the recordings used by "0-default.orc"), the others are synthesised.
// These instruments have the numbers and parameters of those of the Csound orchestra "0-default.orc":
//    piano           _ins(1)  (notes without _ins()): recordings of sample set 1, Salamander Grand Piano
//    plucked_chorus  _ins(2)  plucked string with chorus
//    synth_brass     _ins(3)
//    bowed_string    _ins(4)  cello-like, vibrato depth set by parameter "splash" (|splash| / 10 cents)
//    plucked_string  _ins(5)  plucked string with echo
//    flute           _ins(6)  recordings of sample set 2 (University of Iowa), vibrato set by _mod()
//    sine            _ins(10)
//    bp_fx           reverb, started by the Bol Processor after the group of notes
// Each one is also defined as "bp<n>" (e.g. bp1 = piano), the name used when no '-sc' file is given to the console.
//
// The Bol Processor sends each note as "/s_new <SynthDef> ..." with named arguments (see CsoundRealTime.c):
//    sustain       duration (seconds)
//    freq          pitch (Hz), including the tonality (scales)
//    vol, vol2     beginning and end volume (_volume(), 0-127, default 90)
//    vel           attack velocity (_vel(), 1-127), if the instrument has an attack velocity field
//    pan, pan2     beginning and end panoramic (_pan(), 0-127, 64 = centre), if it has panoramic fields
//    mod, mod2     beginning and end modulation (_mod(), 0-16383), if it has modulation fields
//    bend, bend2   beginning and end pitchbend (cents)
//    NAME, NAME2   beginning and end values of the additional parameters of the instrument, e.g. splash, splash2
//    p4, p5...     every field of the score line
//    volenv, bendenv, pressenv, modenv, panenv, NAMEenv (e.g. slideenv)   (2026-11-21)
//                  the same parameters as envelopes over the duration of the note, read with EnvGen.kr(\volenv.kr(...), timeScale: sustain):
//                  they follow the function tables of the score (e.g. _pitchcont, _cont(slide)), otherwise go straight from the
//                  beginning value to the end value. An envelope has up to 16 segments: declare it with 4 + 4 * 16 values (see Vina.scd).
// A SynthDef ignores the arguments it does not declare. It must end the note after 'sustain' (doneAction: 2),
// and release it when 'gate' is set to 0: this is how the Bol Processor stops a performance.
//
// Sample sets: a line "// BP_SAMPLES <number> <map>" declares that the SynthDefs of this file play the recordings of a map
// (JSON, see Salamander_piano.json). The Bol Processor loads them into the server before playing a project whose '-sc' file names
// this SynthDef file. ~bpSample.(number, channels, key, layer, freq, allow) plays the recording of a key and layer, tuned to freq
// and looped if the map gives loop points (see scrt_helpers() in php/_scrt.php). Numbers 1 to 9 are used by the distribution:
// use 10 to 99 for your own sets.
// BP_SAMPLES 1 Salamander_piano.json
// BP_SAMPLES 2 Iowa_flute.json
//
// SynthDef names are shared by all projects: the files of "supercollider_resources" are loaded first, then those of
// "supercollider_resources_private", where a SynthDef of the same name replaces the one of the distribution.

(
var fxbus = 1020; // stereo bus read by bp_fx: notes send part of their sound to it

// Common parts of the instruments
var level = { |vol, vol2, sustain| (Line.kr(vol, vol2, sustain) / 90).squared }; // gain (volume / 90)^2, as in 0-default.orc
var velocity = { |vel, vol| Select.kr(vel > 0, [vol, vel]).clip(1, 127) / 127 }; // without velocity, the volume is used
var pitch = { |freq, bend, bend2, sustain| freq * (Line.kr(bend, bend2, sustain) / 100).midiratio };
var position = { |pan, pan2, sustain| (Line.kr(pan, pan2, sustain) / 63.5 - 1).clip(-1, 1) };
var stop = { |gate| EnvGen.kr(Env.asr(0, 1, 0.08), gate, doneAction: 2) }; // release when the Bol Processor stops
var out = { |sig, send = 0.25| Out.ar(0, sig); Out.ar(fxbus, sig * send) };
var define = { |names, graph| names.do { |name| SynthDef(name, graph).add } };

define.([\piano, \bp1], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, vel = 0, pan = 64, pan2 = 64, bend = 0, bend2 = 0, gate = 1|
	// As instrument 1 of 0-default.orc: the velocity layer nearest to the velocity, at the loudness measured between layers
	var v = Select.kr(vel > 0, [vol, vel]).clip(1, 127); // without velocity, the beginning volume
	var volgain = Select.kr(vel > 0, [Line.kr(vol, vol2, sustain) / v, Line.kr(vol, vol2, sustain) / 90]).squared;
	var vels = [16, 32, 56, 80, 104, 127], loud = [-34.46, -30.09, -26.81, -24.24, -21.17, -17.12];
	var layer = [24, 44, 68, 92, 115.5].collect { |t| v > t }.sum;
	var target = Select.kr(v <= 16, [IEnvGen.kr(Env(loud, vels.differentiate.drop(1)), v - 16), loud[0] + (20 * (v / 16).log10)]);
	var gain = (target - Select.kr(layer, loud)).dbamp;
	var key = freq.cpsmidi.round;
	var f = pitch.(freq, bend, bend2, sustain);
	var rel = Select.kr(key < 89, [1.5, 0.45 - (0.3 * (key - 21) / 68)]); // the dampers stop the sound, faster in the treble; none from F6
	var env = EnvGen.kr(Env([0, 1, 1, 0], [0.002, max(sustain - 0.002, 0.001), rel], [0, 0, -4]), doneAction: 2);
	var sig = ~bpSample.(1, 2, key, layer, f) * env * gain * volgain * stop.(gate);
	// At the centre the recording keeps its width; towards one side, the other channel is mixed into it
	var p = (Line.kr(pan, pan2, sustain) / 127).clip(0, 1);
	var gl = (2.sqrt * cos(p * 0.5pi)).clip(0, 1), gr = (2.sqrt * sin(p * 0.5pi)).clip(0, 1);
	out.([(gl * sig[0]) + ((1 - gr) * sig[1]), (gr * sig[1]) + ((1 - gl) * sig[0])], 0.3);
	});

define.([\plucked_chorus, \bp2], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, bend = 0, bend2 = 0, gate = 1|
	var f = pitch.(freq, bend, bend2, sustain);
	var sig = [0.997, 1.003].collect { |d| Pluck.ar(WhiteNoise.ar(0.6), Impulse.kr(0), 0.05, 1 / (f * d).max(20), 4, 0.25) };
	sig = LPF.ar(sig, (freq * 6).min(18000));
	sig = sig * EnvGen.kr(Env([1, 1, 0], [sustain, 0.3]), doneAction: 2) * stop.(gate);
	out.(sig * 0.35 * level.(vol, vol2, sustain), 0.25);
	});

define.([\synth_brass, \bp3], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, bend = 0, bend2 = 0, gate = 1|
	var f = pitch.(freq, bend, bend2, sustain);
	var cutoff = EnvGen.kr(Env([2, 7, 4], [0.08, 0.3], -2)) * freq;
	var sig = Saw.ar(f * [1, 1.005]);
	sig = RLPF.ar(sig, cutoff.min(16000), 0.4);
	sig = sig * EnvGen.kr(Env([0, 1, 1, 0], [0.04, max(sustain - 0.04, 0), 0.2]), doneAction: 2) * stop.(gate);
	out.(sig * 0.12 * level.(vol, vol2, sustain), 0.3);
	});

define.([\bowed_string, \bp4], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, bend = 0, bend2 = 0, splash = 0, splash2 = 0, gate = 1|
	// Vibrato depth |splash| / 10 cents, as in 0-default.orc, plus a light vibrato fading in after the attack
	var depth = ((Line.kr(splash, splash2, sustain).abs / 10 + 7) / 1200 * 0.69); // cents -> ratio (approximately)
	var vibrato = SinOsc.kr(5.5 + LFNoise1.kr(1, 0.3), 0, depth * EnvGen.kr(Env([0, 0, 1], [0.2, 0.4])), 1);
	var f = pitch.(freq, bend, bend2, sustain) * vibrato;
	var sig = Saw.ar(f) + (Saw.ar(f * 1.002) * 0.6);
	sig = RLPF.ar(sig, (freq * 3).min(9000), 0.6) + BPF.ar(sig, 1200, 0.5, 0.4);
	sig = LPF.ar(sig, 5000);
	sig = sig * EnvGen.kr(Env([0, 1, 1, 0], [0.15, max(sustain - 0.15, 0), 0.3]), doneAction: 2) * stop.(gate);
	out.(Pan2.ar(sig * 0.12 * level.(vol, vol2, sustain)), 0.35);
	});

define.([\plucked_string, \bp5], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, bend = 0, bend2 = 0, gate = 1|
	var f = pitch.(freq, bend, bend2, sustain);
	var sig = Pluck.ar(WhiteNoise.ar(0.6), Impulse.kr(0), 0.05, 1 / f.max(20), 3, 0.3);
	sig = sig * EnvGen.kr(Env([1, 1, 0], [sustain, 0.2]));
	sig = [sig + CombL.ar(sig, 0.5, 0.375, 2.5, 0.4), sig + CombL.ar(sig, 0.5, 0.25, 2.5, 0.4)]; // echo
	sig = sig * EnvGen.kr(Env([1, 1, 0], [sustain + 2.5, 0.1]), doneAction: 2) * stop.(gate);
	out.(sig * 0.35 * level.(vol, vol2, sustain), 0.2);
	});

define.([\flute, \bp6], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, vel = 0, pan = 64, pan2 = 64, mod = 0, mod2 = 0, bend = 0, bend2 = 0, gate = 1|
	// As instrument 6 of 0-default.orc
	var v = Select.kr(vel > 0, [vol, vel]).clip(1, 127);
	var volgain = Select.kr(vel > 0, [Line.kr(vol, vol2, sustain) / v, Line.kr(vol, vol2, sustain) / 90]).squared;
	var layer = (v >= 50) + (v >= 96); // pp, mf, ff
	var key = freq.cpsmidi.round;
	// the velocity sets how fast the note starts; slow onsets of the recordings are shortened, more for short notes and high velocities
	var allow = (0.05 + (0.1 * sustain.min(2))) * (1.5 - (v / 127));
	var fadein = (0.005 + (0.1 * (1 - (v / 127)).squared)).min(sustain / 3);
	// vibrato (14 cents), its depth set by the modulation, normal without modulation
	var modf = Select.kr((mod.abs + mod2.abs) > 0, [1, Line.kr(mod, mod2, sustain) / 8192]);
	var vdep = EnvGen.kr(Env([0, 0, 1], [0.15, 0.45])) * modf * (1 + LFNoise1.kr(1.1, 0.15));
	var vib = SinOsc.kr(5.1 + LFNoise1.kr(1.5, 0.3), Rand(0, 2pi));
	var f = pitch.(freq, bend, bend2, sustain) * (((vib * vdep * 14) + LFNoise1.kr(0.6, 1.5)) / 100).midiratio;
	var env = EnvGen.kr(Env([0, 1, 1, 0], [fadein, max(sustain - fadein, 0.001), 0.09], [0, 0, -3]), doneAction: 2);
	var sig = ~bpSample.(2, 1, key, layer, f, allow) * env * (1 + (0.1 * vib * vdep)) * (v / 127) * volgain * 0.35 * stop.(gate);
	out.(Pan2.ar(sig, position.(pan, pan2, sustain), 2.sqrt), 0.3);
	});

define.([\sine, \bp10], { |freq = 440, sustain = 1, vol = 90, vol2 = 90, bend = 0, bend2 = 0, gate = 1|
	var f = pitch.(freq, bend, bend2, sustain);
	var sig = SinOsc.ar(f) * EnvGen.kr(Env([0, 1, 1, 0], [0.01, max(sustain - 0.01, 0), 0.05]), doneAction: 2) * stop.(gate);
	out.(Pan2.ar(sig * 0.2 * level.(vol, vol2, sustain)), 0.2);
	});

SynthDef(\bp_fx, {
	var in = In.ar(fxbus, 2);
	Out.ar(0, FreeVerb2.ar(in[0], in[1], 1, 0.8, 0.4));
	}).add;
)
