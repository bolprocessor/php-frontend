// Bol Processor BP3: SynthDef of "-sc.Vina" for real-time SuperCollider (2026-11-21)
// The vina of "new-vina.orc" (Csound resource "-cs.Vina"): a plucked string (Karplus-Strong), its delay line filled
// at the attack with one period of "vina-wave-table.aiff" (the 256 values of table f1, below).
// The pitch follows the pitchbend (_pitchbend()) and the parameter "slide" (_value(slide,...)), both in cents and added,
// which simulates sliding from one fret to another while the string vibrates. Like the volume (_volume()), they follow the
// function tables of the score: the Bol Processor sends them as envelopes "bendenv", "slideenv" and "volenv"
// (see "0-default.scd"), read over the duration of the note.

(
// An envelope argument, constant by default: an array of 4 + 4 * 16 values (up to 16 segments)
var env = { |value| Env([value, value], [1]).asArray ++ (0 ! 60) };
var table = [
	-0.0850, -0.0849, -0.0877, -0.0849, -0.0816, -0.0789, -0.0740, -0.0742, -0.0688, -0.0630, -0.0562, -0.0524, -0.0472, 
	-0.0418, -0.0343, -0.0303, -0.0288, -0.0277, -0.0284, -0.0316, -0.0404, -0.0480, -0.0534, -0.0633, -0.0669, -0.0683, 
	-0.0732, -0.0775, -0.0811, -0.0824, -0.0868, -0.0863, -0.0936, -0.1180, -0.1477, -0.1961, -0.2349, -0.2542, -0.2832, 
	-0.3209, -0.2901, -0.1598, -0.0029, 0.0951, 0.1444, 0.2200, 0.3516, 0.4750, 0.5087, 0.4742, 0.4280, 0.3795, 0.3223, 
	0.2932, 0.2943, 0.3020, 0.3013, 0.2954, 0.3047, 0.3587, 0.4143, 0.3860, 0.2926, 0.1849, 0.0792, -0.0381, -0.1736, 
	-0.3320, -0.4862, -0.6087, -0.6750, -0.6973, -0.7023, -0.6880, -0.6480, -0.5910, -0.5536, -0.5513, -0.5531, -0.5477, 
	-0.5334, -0.5378, -0.6015, -0.7041, -0.7950, -0.8722, -0.9527, -1.0000, -0.9625, -0.8661, -0.7494, -0.6676, -0.6258, 
	-0.5936, -0.5448, -0.4766, -0.4150, -0.3541, -0.3091, -0.2786, -0.2436, -0.1731, -0.0679, 0.0677, 0.2341, 0.4091, 
	0.5617, 0.6654, 0.7219, 0.7477, 0.7421, 0.6843, 0.5464, 0.3546, 0.1580, -0.0113, -0.1371, -0.2397, -0.3106, -0.3583, 
	-0.3899, -0.4144, -0.4355, -0.4533, -0.4767, -0.5137, -0.5629, -0.6210, -0.6568, -0.6554, -0.6193, -0.5355, -0.4084, 
	-0.2407, -0.0450, 0.1491, 0.3272, 0.4749, 0.5917, 0.6745, 0.7247, 0.7580, 0.7699, 0.7473, 0.6898, 0.6132, 0.5288, 
	0.4391, 0.3657, 0.3095, 0.2776, 0.2475, 0.2185, 0.1989, 0.1792, 0.1526, 0.1287, 0.0994, 0.0731, 0.0451, 0.0076, 
	-0.0392, -0.0593, -0.0503, -0.0047, 0.0715, 0.1439, 0.2019, 0.2548, 0.3070, 0.3510, 0.3774, 0.3766, 0.3541, 0.3091, 
	0.2352, 0.1396, 0.0450, -0.0344, -0.1068, -0.1774, -0.2344, -0.2781, -0.3333, -0.3887, -0.4231, -0.4514, -0.4864, 
	-0.5158, -0.5081, -0.4695, -0.4104, -0.3413, -0.2376, -0.1058, 0.0310, 0.1615, 0.2817, 0.3744, 0.4278, 0.4548, 0.4847, 
	0.5124, 0.5291, 0.5422, 0.5685, 0.6024, 0.6245, 0.6082, 0.5541, 0.4594, 0.3217, 0.1412, -0.0547, -0.2439, -0.4092, 
	-0.5148, -0.5731, -0.6058, -0.6214, -0.6224, -0.6432, -0.6669, -0.7005, -0.7644, -0.8362, -0.8914, -0.9252, -0.9355, 
	-0.8870, -0.8002, -0.7052, -0.5935, -0.4631, -0.3285, -0.2081, -0.1236, -0.0792, -0.0371, -0.0021, -0.0230, -0.0942, 
	-0.1331, -0.1160, -0.1154, -0.1739, -0.2644, -0.3130, -0.2882, -0.2459, -0.2391, -0.2208, -0.1338, -0.0058, 0.0653, 
	0.0507
	];

SynthDef(\Vina, { |freq = 440, sustain = 1, gate = 1|
	var vol = EnvGen.kr(\volenv.kr(env.(90)), timeScale: sustain);
	var cents = EnvGen.kr(\bendenv.kr(env.(0)), timeScale: sustain) + EnvGen.kr(\slideenv.kr(env.(0)), timeScale: sustain);
	var f = freq * (cents / 100).midiratio;
	var buf = LocalBuf.newFrom(table);
	// One period of the table at the pitch of the attack, fed into the delay line when the note starts
	var excitation = BufRd.ar(1, buf, Phasor.ar(0, BufFrames.ir(buf) * freq * SampleDur.ir, 0, BufFrames.ir(buf)), 1, 4);
	// Csound's pluck (method 5, weights 0.916 and 0.083) filters the loop as the coefficient 0.083 does;
	// the decay time was set by comparing the two renderings of "-gr.vina3"
	var sig = Pluck.ar(excitation, Impulse.kr(0), 1 / 20, f.reciprocal, 4000 / freq, 0.083);
	sig = sig * (vol / 127) * 0.5;
	sig = sig * EnvGen.kr(Env([1, 1, 0], [sustain, 0.02]), doneAction: 2); // the note ends after its duration, as in Csound
	sig = sig * EnvGen.kr(Env.asr(0, 1, 0.05), gate, doneAction: 2); // released when the Bol Processor stops
	Out.ar(0, sig ! 2);
	}).add;
)
