// Off-line MIDI file player for Bol Processor, based on SpessaSynth
// (https://github.com/spessasus/spessasynth_lib, Apache-2.0)
// and the GeneralUser GS sound bank by S. Christian Collins.
// Load it with <script type="module" src=".../spessasynth/bp_midi_player.js"></script>
// It defines window.BPMidi with the same functions as MIDIjs:
// BPMidi.play(url), BPMidi.pause(), BPMidi.resume(), BPMidi.stop()
// Messages are displayed in elements of class "bp-midi-status", if any.

import { WorkletSynthesizer, Sequencer } from "./spessasynth_lib.js";

const base = new URL(".", import.meta.url);
const soundBankURL = new URL("GeneralUserGS.sf3", base).href;
const processorURL = new URL("spessasynth_processor.min.js", base).href;

let ctx = null;
let synth = null;
let seq = null;
let initPromise = null;

function status(text) {
	document.querySelectorAll(".bp-midi-status").forEach(el => { el.textContent = text; });
	}

async function init() {
	await ctx.audioWorklet.addModule(processorURL);
	synth = new WorkletSynthesizer(ctx);
	synth.connect(ctx.destination);
	status("Loading sounds…");
	const response = await fetch(soundBankURL);
	if(!response.ok) throw new Error("Cannot load " + soundBankURL);
	await synth.soundBankManager.addSoundBank(await response.arrayBuffer(), "main");
	await synth.isReady;
	seq = new Sequencer(synth);
	seq.eventHandler.addEvent("songEnded", "bp", () => status(""));
	seq.eventHandler.addEvent("midiError", "bp", e => status("MIDI file error: " + e));
	}

async function play(url) {
	try {
		// The AudioContext must be created and resumed during the click, before any await
		if(ctx === null) ctx = new AudioContext();
		ctx.resume();
		if(initPromise === null) initPromise = init();
		await initPromise;
		status("Loading MIDI file…");
		const response = await fetch(url, { cache: "no-store" });
		if(!response.ok) throw new Error("Cannot load " + url);
		const binary = await response.arrayBuffer();
		seq.pause();
		synth.stopAll(true);
		seq.loadNewSongList([{ binary: binary, fileName: url.split("/").pop() }]);
		seq.play();
		status("Playing");
		}
	catch(e) {
		initPromise = null;
		status("Cannot play: " + e.message);
		console.error(e);
		}
	}

function pause() {
	if(seq === null) return;
	seq.pause();
	status("Paused");
	}

function resume() {
	if(seq === null || seq.isFinished) return;
	ctx.resume();
	seq.play();
	status("Playing");
	}

function stop() {
	if(seq === null) return;
	seq.pause();
	seq.currentTime = 0;
	synth.stopAll(true);
	status("");
	}

window.BPMidi = { play, pause, resume, stop };
