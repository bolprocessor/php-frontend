import { BasicMIDI, BasicSoundBank, DEFAULT_CHANNEL_MIDI_PARAMETERS, DEFAULT_CHANNEL_SYSTEM_PARAMETERS, DEFAULT_GLOBAL_MIDI_PARAMETERS, DEFAULT_GLOBAL_SYSTEM_PARAMETERS, DefaultXGReverb, MIDIControllers, MIDIMessageTypes, MIDITrack, MIDIUtils, SPESSA_BUFSIZE, SoundBankLoader, SpessaLog, SpessaSynthCoreUtils, SpessaSynthProcessor, SpessaSynthSequencer, audioToWav } from "./spessasynth_core.js";
// src/utils/other.ts
const ConsoleColors = SpessaSynthCoreUtils.ConsoleColors;
// src/external_midi/midi_handler.ts
/**
* Midi_handler.js
* purpose: handles the connection between MIDI devices and synthesizer/sequencer via Web MIDI API
*/
/**
* Wrapper around the Web MIDI API's `MIDIPort`.
*
* @group Web MIDI
*/
var LibMIDIPort = class {
	/**
	* The actual Web MIDI API port.
	*/
	port;
	constructor(port) {
		this.port = port;
	}
	/**
	* The `id` read-only property of the MIDIPort interface returns the unique ID of the port.
	*/
	get id() {
		return this.port.id;
	}
	/**
	* The `name` read-only property of the MIDIPort interface returns the system name of the port.
	*/
	get name() {
		return this.port.name;
	}
	/**
	* The `manufacturer` read-only property of the MIDIPort interface returns the manufacturer of the port.
	*/
	get manufacturer() {
		return this.port.manufacturer;
	}
	/**
	* The `version` read-only property of the MIDIPort interface returns the version of the port.
	*/
	get version() {
		return this.port.version;
	}
};
/**
* Wrapper around the Web MIDI API's `MIDIInput`.
*
* @group Web MIDI
*/
var LibMIDIInput = class extends LibMIDIPort {
	/**
	* The actual Web MIDI API port.
	*/
	port;
	connectedSynths = /* @__PURE__ */ new Set();
	/**
	* @internal
	* @param input
	*/
	constructor(input) {
		super(input);
		this.port = input;
		input.onmidimessage = (e) => {
			for (const s of this.connectedSynths) if (e.data) s.sendMessage(e.data);
		};
	}
	/**
	* Connects the input to a given synth, listening for all incoming events.
	* @param synth The synth to connect to.
	*/
	connect(synth) {
		this.connectedSynths.add(synth);
	}
	/**
	* Disconnects the input from a given synth.
	* @param synth The synth to disconnect from.
	*/
	disconnect(synth) {
		this.connectedSynths.delete(synth);
	}
};
/**
* Wrapper around the Web MIDI API's `MIDIOutput`.
*
* @group Web MIDI
*/
var LibMIDIOutput = class extends LibMIDIPort {
	/**
	* The actual Web MIDI API port.
	*/
	port;
	/**
	* @internal
	* @param output
	*/
	constructor(output) {
		super(output);
		this.port = output;
	}
	/**
	* Connects a given sequencer to the output, playing back the MIDI file to it.
	* @param seq The sequencer to connect.
	* @param channelOffset The channel offset of this output for multi-port files.
	* For example 0 means the first port, 16 means the second port and so on.
	*/
	connect(seq, channelOffset = 0) {
		seq.connectMIDIOutput(this.port, channelOffset);
	}
	/**
	* Disconnects a sequencer from the output.
	* @param seq The sequencer to disconnect.
	*/
	disconnect(seq) {
		seq.disconnectMIDIOutput(this.port);
	}
};
/**
* Provides an easy way to connect physical MIDI devices
* to the synthesizer and sequencer using the Web MIDI API.
*
* @group Web MIDI
*/
var MIDIDeviceHandler = class MIDIDeviceHandler {
	/**
	* The available MIDI inputs. ID maps to the input.
	*/
	inputs = /* @__PURE__ */ new Map();
	/**
	* The available MIDI outputs. ID maps to the output.
	*/
	outputs = /* @__PURE__ */ new Map();
	/**
	* Called after a device was connected or disconnected, once {@link MIDIDeviceHandler.inputs} and {@link MIDIDeviceHandler.outputs} are updated.
	*
	* > **Note**
	* >
	* > A device that gets reconnected gets a new {@link LibMIDIInput} or {@link LibMIDIOutput} wrapper,
	* > so it needs to be connected again. The previous wrapper stops working.
	*/
	onDeviceChange;
	constructor(access) {
		for (const [key, value] of access.inputs.entries()) this.inputs.set(key, new LibMIDIInput(value));
		for (const [key, value] of access.outputs.entries()) this.outputs.set(key, new LibMIDIOutput(value));
		access.onstatechange = (e) => {
			const port = e.port;
			if (!port) return;
			const map = port.type === "input" ? this.inputs : this.outputs;
			if (port.state === "disconnected") {
				if (!map.delete(port.id)) return;
			} else if (map.has(port.id)) return;
			else if (port.type === "input") this.inputs.set(port.id, new LibMIDIInput(port));
			else this.outputs.set(port.id, new LibMIDIOutput(port));
			this.onDeviceChange?.();
		};
	}
	/**
	* Initializes the connection to physical MIDI devices.
	* @param requestSysEx If the System Exclusive access should be requested.
	* If it is denied, the access without System Exclusive messages is requested instead.
	* @returns The MIDI device handler.
	* @throws Error If the MIDI devices fail to initialize or the Web MIDI API is not supported.
	*/
	static async createMIDIDeviceHandler(requestSysEx = true) {
		if (!navigator.requestMIDIAccess) throw new Error("Web MIDI API is not supported.");
		let access;
		try {
			access = await navigator.requestMIDIAccess({
				sysex: requestSysEx,
				software: true
			});
		} catch (error) {
			if (!requestSysEx) throw error;
			SpessaLog.warn("System Exclusive MIDI access denied, retrying without it:", error);
			access = await navigator.requestMIDIAccess({ software: true });
		}
		SpessaLog.info("%cMIDI handler created!", ConsoleColors.recognized);
		return new MIDIDeviceHandler(access);
	}
};
// src/external_midi/web_midi_link.ts
/**
* This class adds [Web MIDI Link](https://www.g200kg.com/en/docs/webmidilink/) support to the synthesizer.
*
* Web MIDI Link allows other web pages (for example, online MIDI editors) to control the synthesizer by sending MIDI messages to its window.
*
* @group Web MIDI
*/
var WebMIDILinkHandler = class {
	listener;
	/**
	* Initializes support for [Web MIDI Link](https://www.g200kg.com/en/docs/webmidilink/).
	*
	* Once created, the handler listens for `postMessage` events from the parent window.
	* When it receives a MIDI message in the format `"midi,xx,yy,zz"` (hex bytes), it forwards it to the synthesizer.
	* @param synth The synthesizer instance to connect the link to.
	*/
	constructor(synth) {
		this.listener = (msg) => {
			if (typeof msg.data !== "string") return;
			const data = msg.data.split(",");
			if (data[0] !== "midi" || data.length < 2) return;
			const midiData = new Array(data.length - 1);
			for (let i = 1; i < data.length; i++) {
				const byte = Number.parseInt(data[i], 16);
				if (!(byte >= 0 && byte <= 255)) {
					SpessaLog.warn(`Invalid Web MIDI Link message: ${msg.data}`);
					return;
				}
				midiData[i - 1] = byte;
			}
			synth.sendMessage(midiData);
		};
		window.addEventListener("message", this.listener);
		SpessaLog.info("%cWeb MIDI Link handler created!", ConsoleColors.recognized);
	}
	/**
	* Stops listening for Web MIDI Link messages.
	*/
	close() {
		window.removeEventListener("message", this.listener);
	}
};
// src/sequencer/midi_data.ts
/**
* A simplified version of the {@link MIDITrack}, accessible at all times from the {@link Sequencer}.
* This data does not contain any events.
*
* @group Sequencer.MIDI Data
*/
var MIDIDataTrack = class extends MIDITrack {
	/**
	* THIS DATA WILL BE EMPTY! USE {@link Sequencer.getMIDI} TO GET THE ACTUAL DATA!
	*/
	events = [];
	constructor(track) {
		super();
		super.copyFrom(track);
		this.events = [];
	}
};
/**
* A simplified version of {@link BasicMIDI}, accessible at all times from the {@link Sequencer}.
* Use {@link Sequencer.getMIDI} to get the actual sequence.
* This class contains all properties that {@link BasicMIDI} does, except for tracks, timeline and the embedded sound bank.
*
* @group Sequencer.MIDI Data
*/
var MIDIData = class MIDIData extends BasicMIDI {
	tracks;
	/**
	* THIS DATA WILL BE EMPTY! USE {@link Sequencer.getMIDI} TO GET THE ACTUAL DATA!
	*/
	timeline = [];
	/**
	* THIS DATA WILL BE EMPTY! USE {@link Sequencer.getMIDI} TO GET THE ACTUAL DATA!
	*/
	embeddedSoundBank = void 0;
	/**
	* The byte length of the sound bank if it exists.
	*/
	embeddedSoundBankSize;
	constructor(mid) {
		super();
		super.copyMetadataFrom(mid);
		this.tracks = mid.tracks.map((t) => new MIDIDataTrack(t));
		this.embeddedSoundBankSize = mid instanceof MIDIData ? mid.embeddedSoundBankSize : mid?.embeddedSoundBank?.byteLength;
	}
};
// src/sequencer/default_sequencer_options.ts
/**
* The default {@link SequencerOptions} used by the {@link Sequencer}.
*
* @group Sequencer
*/
const DEFAULT_SEQUENCER_OPTIONS = {
	skipToFirstNoteOn: true,
	initialPlaybackRate: 1
};
// src/utils/basic_event_handler.ts
/**
* The shared event handling of {@link SynthEventHandler} and {@link SeqEventHandler}.
*/
var BasicEventHandler = class {
	/**
	* The time delay before an event is called, in seconds.
	* Set to 0 to disable it.
	*/
	timeDelay = 0;
	/**
	* The callbacks for each event type, by their ID.
	*/
	events = /* @__PURE__ */ new Map();
	/**
	* Adds a new event listener.
	* @param event The event type to listen for.
	* @param id The unique identifier for the event. It can be used to overwrite an existing callback with the same ID.
	* @param callback The callback for the event.
	*/
	addEvent(event, id, callback) {
		let callbacks = this.events.get(event);
		if (!callbacks) {
			callbacks = /* @__PURE__ */ new Map();
			this.events.set(event, callbacks);
		}
		callbacks.set(id, callback);
	}
	/**
	* Removes an event listener.
	* @param event The event type to remove a listener from.
	* @param id The unique identifier for the event to remove.
	*/
	removeEvent(event, id) {
		this.events.get(event)?.delete(id);
	}
	/**
	* Calls the given event.
	* INTERNAL USE ONLY!
	* @internal
	*/
	callEventInternal(name, eventData) {
		const callbacks = this.events.get(name);
		if (!callbacks) return;
		const callAll = () => {
			for (const callback of callbacks.values()) try {
				callback(eventData);
			} catch (error) {
				reportError(error);
			}
		};
		if (this.timeDelay > 0) setTimeout(callAll, this.timeDelay * 1e3);
		else callAll();
	}
};
// src/sequencer/seq_event_handler.ts
/**
* The {@link Sequencer} supports event handling.
* For example, the lyrics window in the SpessaSynth web app uses it to show lyrics in real time.
*
* It is accessible via the {@link Sequencer.eventHandler} property.
*
* Event types can be found in {@link LibSequencerEvent}.
* @group Sequencer.Events
*/
var SeqEventHandler = class extends BasicEventHandler {};
// src/utils/fill_with_defaults.ts
/**
* Fills the object with default values.
* @param obj object to fill.
* @param defObj object to fill with.
*/
function fillWithDefaults(obj, defObj) {
	return {
		...defObj,
		...obj
	};
}
// src/synthesizer/reverb/reverb_generator.ts
function generateReverbImpulse(audioContext, duration = .5, decay = 3) {
	const sampleRate = audioContext.sampleRate;
	const length = Math.floor(sampleRate * duration);
	const impulse = audioContext.createBuffer(2, length, sampleRate);
	for (let ch = 0; ch < 2; ch++) {
		const data = impulse.getChannelData(ch);
		for (let i = 0; i < length; i++) {
			const t = i / length;
			data[i] = t < Math.random() ? Math.exp(-decay * t) * (Math.random() - .5) * .5 : 0;
		}
	}
	return impulse;
}
// src/synthesizer/basic/lib_midi_channel.ts
/**
* Represents a single MIDI channel within a {@link BasicSynthesizer}.
*
* @group Synthesizer.Basic
*/
var LibMIDIChannel = class {
	/**
	* This channel's number.
	*/
	channel;
	synth;
	_systemParameters = { ...DEFAULT_CHANNEL_SYSTEM_PARAMETERS };
	lockedMIDIParameters = Object.fromEntries(Object.keys(DEFAULT_CHANNEL_MIDI_PARAMETERS).map((k) => [k, false]));
	/**
	* @internal
	* @param channel
	* @param synth
	*/
	constructor(channel, synth) {
		this.channel = channel;
		this.synth = synth;
	}
	_patch = {
		bankMSB: 0,
		bankLSB: 0,
		program: 0,
		isDrum: false,
		isGMGSDrum: false,
		name: ""
	};
	/**
	* The currently selected MIDI patch of the channel.
	* Note that the exact matching preset may not be available, but this represents exactly what MIDI asks for.
	*/
	get patch() {
		return this._patch;
	}
	/**
	* @internal
	* @param patch
	*/
	set patch(patch) {
		this._patch = patch;
	}
	_midiParameters = { ...DEFAULT_CHANNEL_MIDI_PARAMETERS };
	/**
	* The {@link ChannelMIDIParameter}s of this channel.
	* These are only editable via MIDI messages.
	*/
	get midiParameters() {
		return this._midiParameters;
	}
	/**
	* The {@link ChannelSystemParameter}s of this channel.
	* These are only editable via the API.
	*/
	get systemParameters() {
		return this._systemParameters;
	}
	_voiceCount = 0;
	/**
	* The channel's current voice count.
	*/
	get voiceCount() {
		return this._voiceCount;
	}
	/**
	* @internal
	* @param value
	*/
	set voiceCount(value) {
		this._voiceCount = value;
	}
	/**
	* Locks or unlocks a given {@link ChannelMIDIParameter}.
	* This prevents any changes to it until it's unlocked.
	* @param parameter The channel MIDI parameter to lock.
	* @param isLocked If the parameter should be locked.
	*/
	lockMIDIParameter(parameter, isLocked) {
		this.lockedMIDIParameters[parameter] = isLocked;
		this.synth.post({
			type: "lockChannelMIDIParameter",
			channelNumber: this.channel,
			data: {
				parameter,
				isLocked
			}
		});
	}
	/**
	* Sets a {@link ChannelSystemParameter} of the channel.
	* @param parameter The type of the parameter to set.
	* @param value The value to set for the parameter.
	*/
	setSystemParameter(parameter, value) {
		this._systemParameters[parameter] = value;
		this.synth.post({
			type: "setChannelSystemParameter",
			channelNumber: this.channel,
			data: {
				parameter,
				value
			}
		});
	}
	/**
	* Causes this MIDI channel to ignore controller messages for the given controller number.
	* @param controller 0-127 MIDI CC number.
	* @param isLocked True if locked, false if unlocked.
	*/
	lockController(controller, isLocked) {
		this.synth.post({
			channelNumber: this.channel,
			type: "lockController",
			data: {
				controller,
				isLocked
			}
		});
	}
	/**
	* Toggles drums on this channel.
	* @param isDrum If the channel should be drums.
	*/
	setDrums(isDrum) {
		this.synth.post({
			channelNumber: this.channel,
			type: "setDrums",
			data: isDrum
		});
	}
	/**
	* @internal
	* @param parameter
	* @param value
	*/
	setMIDIParameter(parameter, value) {
		this._midiParameters[parameter] = value;
	}
	/**
	* @internal
	*/
	reset() {
		for (const [key, value] of Object.entries(DEFAULT_CHANNEL_MIDI_PARAMETERS)) if (!this.lockedMIDIParameters[key]) this._midiParameters[key] = value;
	}
};
// src/synthesizer/basic/synth_config.ts
/**
* The default {@link SynthConfig} used by the synthesizers.
*
* @group Synthesizer.Basic
*/
const DEFAULT_SYNTH_CONFIG = {
	eventsEnabled: true,
	convolverMode: false,
	decodedSampleBudget: 1e400,
	audioNodeCreators: void 0
};
// src/synthesizer/basic/sound_bank_manager.ts
/**
* The sound bank manager allows for handling multiple sound banks with a single synthesizer instance.
*
* It is accessible via the {@link BasicSynthesizer.soundBankManager} property.
*
* Every operation sends a new {@link LibSynthesizerEvent.presetListChange `presetListChange`} event.
* @group Synthesizer.Basic
*/
var SoundBankManager = class {
	/**
	* All the sound banks, ordered from the most important to the least.
	* @internal
	*/
	soundBankList;
	synth;
	/**
	* Creates a new instance of the sound bank manager.
	* @internal
	*/
	constructor(synth) {
		this.soundBankList = [];
		this.synth = synth;
	}
	/**
	* The IDs of the sound banks in the current order (from the most important to the least).
	* This can be used to set or retrieve the current order.
	* Presets in the first bank override the second bank if they have the same MIDI patch and so on.
	*/
	get priorityOrder() {
		return this.soundBankList.map((s) => s.id);
	}
	/**
	* Rearranges the sound banks in a given order.
	* @param newList The order of sound banks, a list of identifiers. The first one overrides the second.
	*/
	set priorityOrder(newList) {
		this.awaitResponse().catch(() => void 0);
		this.sendToWorklet("rearrangeSoundBanks", newList);
		const rank = (id) => {
			const index = newList.indexOf(id);
			return index === -1 ? newList.length : index;
		};
		this.soundBankList.sort((a, b) => rank(a.id) - rank(b.id));
	}
	/**
	* Adds a new sound bank with a given ID to the list,
	* or replaces an existing one.
	*
	* > **Warning**
	* >
	* > This method detaches the provided `ArrayBuffer` by transferring it to the synthesizer.
	* > It can't be used after passing it to the object!
	*
	* @param soundBankBuffer The new sound bank to add, the binary data of the file.
	* @param id The sound bank's unique identifier. If it already exists, it will be replaced.
	* @param bankOffset The sound bank's bank MSB offset. Default is 0.
	* @throws Error If the sound bank could not be loaded. The {@link LibSynthesizerEvent.soundBankError `soundBankError`} event is also emitted.
	*/
	async addSoundBank(soundBankBuffer, id, bankOffset = 0) {
		const responsePromise = this.awaitResponse();
		this.sendToWorklet("addSoundBank", {
			soundBankBuffer,
			bankOffset,
			id
		}, [soundBankBuffer]);
		await responsePromise;
		const found = this.soundBankList.find((s) => s.id === id);
		if (found === void 0) this.soundBankList.push({
			id,
			bankOffset
		});
		else found.bankOffset = bankOffset;
	}
	/**
	* Removes a sound bank with a given ID from the sound bank list.
	*
	* > **Note**
	* >
	* > The last remaining sound bank cannot be removed. The call is ignored with a warning instead.
	* @param id The ID of the sound bank to remove.
	*/
	async deleteSoundBank(id) {
		if (this.soundBankList.length < 2) {
			SpessaLog.warn("1 sound bank left. Aborting!");
			return;
		}
		if (this.soundBankList.every((s) => s.id !== id)) {
			SpessaLog.warn(`No sound banks with id of "${id}" found. Aborting!`);
			return;
		}
		const responsePromise = this.awaitResponse();
		this.sendToWorklet("deleteSoundBank", id);
		await responsePromise;
		this.soundBankList = this.soundBankList.filter((s) => s.id !== id);
	}
	awaitResponse() {
		return this.synth.awaitCoreResponse("soundBankManager");
	}
	sendToWorklet(type, data, transferable = []) {
		const msg = {
			type: "soundBankManager",
			channelNumber: -1,
			data: {
				type,
				data
			}
		};
		this.synth.post(msg, transferable);
	}
};
// src/synthesizer/basic/synth_event_handler.ts
/**
* The synthesizer supports event handling.
* For example, the MIDI keyboard in the SpessaSynth web app uses it to visualize key presses.
*
* It is accessible via the {@link BasicSynthesizer.eventHandler} property.
*
* Event types can be found in {@link LibSynthesizerEvent}.
* @group Synthesizer.Events
*/
var SynthEventHandler = class extends BasicEventHandler {};
// src/synthesizer/basic/basic_synthesizer.ts
const DEFAULT_SYNTH_METHOD_OPTIONS = { time: 0 };
/**
* This abstract class contains shared methods between {@link WorkletSynthesizer} and {@link WorkerSynthesizer}.
*
* > **Warning**
* >
* > The synthesizer internally sends commands to the {@link BasicSynthesizerCore} where all the processing happens. (This can be a worklet or a worker depending on your synthesizer of choice.)
* > Keep that in mind as not all methods will immediately report values!
* > (E.g. {@link BasicSynthesizer.noteOn `noteOn`} won't instantly increase the voice count in {@link BasicSynthesizer.midiChannels `midiChannels`})
*
* ### Features
*
* The synthesizer uses `spessasynth_core`'s synthesizer as the core audio engine, providing extensive support for all supported audio formats and various MIDI extensions.
*
* [MIDI implementation chart can be found here](https://spessasus.github.io/spessasynth_core/extra/midi-implementation/).
*
* [Comparison of both synthesizers can be found here.](../../../docs/extra/comparing-synthesizers.md)
* @group Synthesizer.Basic
*/
var BasicSynthesizer = class {
	/**
	* The synthesizer's sound bank manager.
	* It allows managing the sound bank list.
	*/
	soundBankManager = new SoundBankManager(this);
	/**
	* The synthesizer's event handler.
	* It allows setting up custom event listeners for the synthesizer.
	*/
	eventHandler = new SynthEventHandler();
	/**
	* Synthesizer's parent audio context instance.
	*/
	context;
	/**
	* The synthesizer's (virtual) MIDI channels.
	*
	* > **Note**
	* >
	* > The real channels live in {@link BasicSynthesizerCore}.
	*/
	midiChannels = [];
	/**
	* The current preset list of the synthesizer,
	* including all sound banks with their offsets set through the {@link SoundBankManager}.
	*
	* > **Tip**
	* >
	* > It is still recommended to use the {@link LibSynthesizerEvent.presetListChange `presetListChange`} event as the data may not be immediately available.
	*/
	presetList = [];
	/**
	* INTERNAL USE ONLY!
	* @internal
	* All sequencer callbacks
	*/
	sequencers = new Array();
	/**
	* A promise that gets resolved when the synthesizer gets fully initialized.
	*
	* > **Warning**
	* >
	* > Remember to wait for this promise before playing anything or rendering audio!
	*/
	isReady;
	/**
	* INTERNAL USE ONLY!
	* @internal
	*/
	post;
	/**
	* The `AudioNode` that processes the synthesizer's captured reverb tail through a Web Audio `ConvolverNode`.
	*
	* > **Warning**
	* >
	* > This property is only defined when {@link SynthConfig.convolverMode} is enabled.
	*/
	convolverNode;
	worklet;
	/**
	* The `spessasynth_core` system parameters.
	* @internal
	*/
	_systemParameters = { ...DEFAULT_GLOBAL_SYSTEM_PARAMETERS };
	lockedMIDIParameters = Object.fromEntries(Object.keys(DEFAULT_GLOBAL_MIDI_PARAMETERS).map((k) => [k, false]));
	/**
	* Pending core responses for each type, in the order they were requested.
	* The core answers requests of the same type in order, so the first waiter always gets the next response.
	* @internal
	*/
	coreResponseWaiters = /* @__PURE__ */ new Map();
	/**
	* Set once the synthesizer is destroyed.
	* @internal
	*/
	destroyed = false;
	renderingProgressTracker = /* @__PURE__ */ new Map();
	/**
	* Creates a new instance of a synthesizer.
	* @param context The audio context.
	* @param workletName The worklet processor name to use.
	* @param synthConfig The configuration for the synthesizer.
	* @param postFunction The internal post function. Leave undefined to use the worklet's `postMessage`.
	* @internal
	*/
	constructor(context, workletName, synthConfig, postFunction) {
		SpessaLog.info("%cInitializing SpessaSynth synthesizer...", ConsoleColors.info);
		this.context = context;
		try {
			const workletConstructor = synthConfig?.audioNodeCreators?.worklet ?? ((context, name, options) => {
				return new AudioWorkletNode(context, name, options);
			});
			this.worklet = workletConstructor(context, workletName, {
				outputChannelCount: new Array(18).fill(2),
				numberOfOutputs: 18,
				processorOptions: {
					convolverMode: synthConfig.convolverMode,
					sampleRate: context.sampleRate,
					initialTime: context.currentTime,
					processorConfig: {
						eventsEnabled: synthConfig.eventsEnabled,
						decodedSampleBudget: synthConfig.decodedSampleBudget
					}
				}
			});
		} catch (error) {
			throw new Error("Could not create the AudioWorkletNode. Did you forget to addModule()?", { cause: error });
		}
		if (synthConfig.convolverMode) {
			this.convolverNode = context.createConvolver();
			this.worklet.connect(this.convolverNode, 1);
			this.convolverNode.buffer = generateReverbImpulse(context);
		}
		this.post = postFunction ?? ((data, transfer = []) => {
			this.worklet.port.postMessage(data, transfer);
		});
		this.isReady = this.awaitCoreResponse("sf3Decoder");
		this.worklet.port.onmessage = (e) => this.handleMessages(e.data);
		for (let i = 0; i < 16; i++) this.addNewChannelInternal();
		this._systemParameters.eventsEnabled = synthConfig.eventsEnabled;
	}
	/**
	* The current voice count.
	* @internal
	*/
	_voiceCount = 0;
	/**
	* The current amount of voices (notes) being synthesized. A real-time value.
	*/
	get voiceCount() {
		return this._voiceCount;
	}
	_midiParameters = { ...DEFAULT_GLOBAL_MIDI_PARAMETERS };
	/**
	* The {@link GlobalMIDIParameter}s of the synthesizer.
	* These are only editable via MIDI messages.
	*/
	get midiParameters() {
		return this._midiParameters;
	}
	/**
	* The connected `BaseAudioContext`'s time.
	*/
	get currentTime() {
		return this.context.currentTime;
	}
	/**
	* The {@link GlobalSystemParameter}s of the synthesizer.
	* These are only editable via the API.
	*/
	get systemParameters() {
		return this._systemParameters;
	}
	/**
	* Connects the synthesizer to a given AudioNode.
	* @param destinationNode The node to connect to.
	* @returns The destination node.
	*/
	connect(destinationNode) {
		this.worklet.connect(destinationNode, 0);
		this.convolverNode?.connect(destinationNode);
		return destinationNode;
	}
	/**
	* Disconnects the synthesizer from a given AudioNode.
	* @param destinationNode The node to disconnect from.
	* @returns The destination node.
	*/
	disconnect(destinationNode) {
		this.worklet.disconnect(destinationNode, 0);
		this.convolverNode?.disconnect(destinationNode);
		return destinationNode;
	}
	/**
	* Properly disposes of the synthesizer along with its audio nodes and the core.
	* All pending operations (such as loading a sound bank) are rejected.
	*
	* > **Warning**
	* >
	* > Remember, you **MUST** call this method after you're done with the synthesizer!
	* > Otherwise it will keep processing and the performance will greatly suffer.
	*
	* > **Note**
	* >
	* > For {@link WorkerSynthesizer}, the `Worker` itself is owned by you.
	* > Terminate it after calling this method if it is no longer needed.
	*/
	destroy() {
		if (this.destroyed) return;
		this.post({
			channelNumber: -1,
			type: "destroyWorklet",
			data: null
		});
		this.destroyed = true;
		this.worklet.disconnect();
		this.convolverNode?.disconnect();
		const error = /* @__PURE__ */ new Error("The synthesizer has been destroyed.");
		for (const waiters of this.coreResponseWaiters.values()) for (const waiter of waiters) waiter.reject(error);
		this.coreResponseWaiters.clear();
		this.renderingProgressTracker.clear();
	}
	/**
	* Sets `spessasynth_core`'s log level in the processor.
	* @example
	* ```js
	* // Enable all logs
	* synth.setLogLevel(true, true, true);
	* ```
	*
	* @param enableInfo Enable info (verbose)
	* @param enableWarning Enable warnings (unrecognized messages)
	* @param enableGroup Enable groups (to group a lot of logs)
	*/
	setLogLevel(enableInfo, enableWarning, enableGroup) {
		this.post({
			channelNumber: -1,
			type: "setLogLevel",
			data: {
				enableInfo,
				enableWarning,
				enableGroup
			}
		});
	}
	/**
	* Locks or unlocks a given {@link GlobalMIDIParameter}.
	* This prevents any changes to it until it's unlocked.
	* @example
	* ```js
	* // Lock the MIDI system to GS
	* synth.lockMIDIParameter("system", "gs");
	* ```
	*
	* @param parameter The Global MIDI Parameter to lock.
	* @param isLocked If the parameter should be locked.
	*/
	lockMIDIParameter(parameter, isLocked) {
		this.post({
			type: "lockGlobalMIDIParameter",
			data: {
				parameter,
				isLocked
			},
			channelNumber: -1
		});
		this.lockedMIDIParameters[parameter] = isLocked;
	}
	/**
	* Sets a {@link GlobalSystemParameter} to a given value.
	* @example
	* ```js
	* // Set the master gain to 200%
	* synth.setSystemParameter("gain", 2);
	* ```
	*
	* @param parameter The parameter to set.
	* @param value The value to set.
	*/
	setSystemParameter(parameter, value) {
		this._systemParameters[parameter] = value;
		this.post({
			type: "setGlobalSystemParameter",
			channelNumber: -1,
			data: {
				parameter,
				value
			}
		});
	}
	/**
	* Gets the current {@link SynthesizerSnapshot} of the synthesizer.
	*/
	async getSnapshot() {
		const responsePromise = this.awaitCoreResponse("synthesizerSnapshot");
		this.post({
			type: "requestSynthesizerSnapshot",
			data: null,
			channelNumber: -1
		});
		return await responsePromise;
	}
	/**
	* Adds a new channel to the synthesizer.
	* The channel appears in {@link BasicSynthesizer.midiChannels} once the core confirms it with a
	* {@link LibSynthesizerEvent.channelAdded `channelAdded`} event.
	*/
	addNewChannel() {
		this.post({
			channelNumber: -1,
			type: "addNewChannel",
			data: null
		});
	}
	/**
	* Connects a given channel output to the given audio node.
	* Note that this output is only meant for visualization and may be silent when an insertion effect for this channel is enabled.
	* @example
	* ```js
	* // Create an analyzer for channel 1
	* const analyzer = context.createAnalyser();
	* synth.connectChannel(analyzer, 0);
	* ```
	*
	* @param targetNode The node to connect to.
	* @param channelNumber The channel number to connect to, will be rolled over if value is greater than 15.
	* @returns The target node.
	*/
	connectChannel(targetNode, channelNumber) {
		this.worklet.connect(targetNode, channelNumber % 16 + 2);
		return targetNode;
	}
	/**
	* Disconnects a given channel output from the given audio node.
	* @example
	* ```js
	* // Disconnect the analyzer from earlier
	* synth.disconnectChannel(analyzer, 0);
	* ```
	*
	* @param targetNode The node to disconnect from.
	* @param channelNumber The channel number to disconnect, will be rolled over if value is greater than 15.
	*/
	disconnectChannel(targetNode, channelNumber) {
		this.worklet.disconnect(targetNode, channelNumber % 16 + 2);
	}
	/**
	* Sends a raw MIDI message to the synthesizer.
	* @example
	* ```js
	* // Send a MIDI note on message for channel 2 with note 61 (C#) and velocity 120
	* synth.sendMessage([0x92, 0x3d, 0x78]);
	* ```
	*
	* @param message  The MIDI message to process.
	* @param channelOffset Adds to the channel number of the message. It defaults to 0.
	* @param eventOptions Additional options for this command.
	*/
	sendMessage(message, channelOffset = 0, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.post({
			type: "midiMessage",
			channelNumber: -1,
			data: {
				messageData: new Uint8Array(message),
				channelOffset,
				options: fillWithDefaults(eventOptions, DEFAULT_SYNTH_METHOD_OPTIONS)
			}
		});
	}
	/**
	* Starts playing a note.
	*
	* > **Note**
	* >
	* > A velocity of 0 is treated like a Note Off message.
	* @example
	* ```js
	* // Start the note 64 (E) on channel 0 with velocity of 120
	* synth.noteOn(0, 64, 120);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param midiNote The MIDI note number to play. Ranges from 0 to 127.
	* @param velocity Controls how loud the note is. Ranges from 0 to 127, where 127 is the loudest and 1 is the quietest.
	* @param eventOptions Additional options for this command.
	*/
	noteOn(channel, midiNote, velocity, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.noteOn, channel, [midiNote % 128, velocity % 128], eventOptions);
	}
	/**
	* Stops playing a note.
	* @example
	* ```js
	* // Stop the note 77 (F) on channel 15
	* synth.noteOff(15, 77);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param midiNote The MIDI note number to stop. Ranges from 0 to 127.
	* @param eventOptions Additional options for this command.
	*/
	noteOff(channel, midiNote, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.noteOff, channel, [midiNote % 128], eventOptions);
	}
	/**
	* Stop all notes. Equivalent of MIDI "panic".
	* @param force If the notes should immediately be stopped, defaults to false.
	*/
	stopAll(force = false) {
		this.post({
			channelNumber: -1,
			type: "stopAll",
			data: force ? 1 : 0
		});
	}
	/**
	* Set a given MIDI controller to a given value.
	*
	* > **Tip**
	* >
	* > Refer to [this table](https://spessasus.github.io/spessasynth_core/extra/midi-implementation#default-supported-controllers)
	* > for the list of controllers supported by default.
	* @example
	* ```js
	* // Set controller 10 (Channel Pan) on channel 2 to 127 (hard right)
	* synth.controllerChange(2, 10, 127);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param controller The MIDI CC number of the controller to change.
	* @param value The value to set the given controller to. Ranges from 0 to 127.
	* @param eventOptions Additional options for this command.
	*/
	controllerChange(channel, controller, value, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		if (controller > 127 || controller < 0) throw new Error(`Invalid controller number: ${controller}`);
		this.sendChannelMessage(MIDIMessageTypes.controllerChange, channel, [Math.floor(controller), Math.floor(value) % 128], eventOptions);
	}
	/**
	* Fully resets the synthesizer to GS mode.
	*/
	reset() {
		this.post({
			channelNumber: -1,
			type: "ccReset",
			data: null
		});
	}
	/**
	* Apply pressure to the given channel. It usually controls the vibrato amount.
	* @example
	* ```js
	* // Set channel 1 pressure to 64 (middle)
	* synth.channelPressure(1, 64);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param pressure The pressure to apply. Ranges from 0 to 127. 0 means no pressure, 127 means max.
	* @param eventOptions Additional options for this command.
	*/
	channelPressure(channel, pressure, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.channelPressure, channel, [pressure % 128], eventOptions);
	}
	/**
	* Apply pressure to the given note on a given channel.
	* It may be bound to specific parameters with system exclusive messages to allow for
	* extra controls, such as per-note pitch wheel.
	* @example
	* ```js
	* // Set channel 11 pressure on note 60 (C) to 127 (max)
	* synth.polyPressure(11, 60, 127);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param midiNote The MIDI note number to apply pressure to. Ranges from 0 to 127.
	* @param pressure The pressure to apply. Ranges from 0 to 127.
	* @param eventOptions Additional options for this command.
	*/
	polyPressure(channel, midiNote, pressure, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.polyPressure, channel, [midiNote % 128, pressure % 128], eventOptions);
	}
	/**
	* Change the channel's pitch, including the currently playing notes.
	* @example
	* ```js
	* // Set pitch bend on channel 1 to middle (no change)
	* synth.pitchWheel(1, 8192);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param value The 14-bit pitch value. Ranges from 0 to 16383 where 8192 is no pitch change.
	* @param eventOptions Additional options for this command.
	*/
	pitchWheel(channel, value, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.pitchWheel, channel, [value & 127, value >> 7 & 127], eventOptions);
	}
	/**
	* Change the channel's pitch bend range in semitones.
	* It uses Registered Parameter Number controllers internally.
	*
	* > **Tip**
	* >
	* > The pitch bend range can be decimal, for example, 0.5 means +- half a semitone.
	* > The fractional part is sent through the Data Entry LSB with a precision of 1/128 of a semitone.
	*
	* @example
	* ```js
	* // Set the pitch bend range on channel 0 to +-12 semitones (one octave)
	* synth.pitchWheelRange(0, 12);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param range The bend range in semitones.
	* @param eventOptions Additional options for this command.
	*/
	pitchWheelRange(channel, range, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.controllerChange(channel, MIDIControllers.registeredParameterMSB, 0, eventOptions);
		this.controllerChange(channel, MIDIControllers.registeredParameterLSB, 0, eventOptions);
		const semitones = Math.floor(range);
		this.controllerChange(channel, MIDIControllers.dataEntryMSB, semitones, eventOptions);
		this.controllerChange(channel, MIDIControllers.dataEntryLSB, Math.round((range - semitones) * 128), eventOptions);
		this.controllerChange(channel, MIDIControllers.registeredParameterMSB, 127, eventOptions);
		this.controllerChange(channel, MIDIControllers.registeredParameterLSB, 127, eventOptions);
		this.controllerChange(channel, MIDIControllers.dataEntryMSB, 0, eventOptions);
	}
	/**
	* Changes the program for the given channel.
	* @example
	* ```js
	* // Change the program on channel 0 to 16 (drawbar organ)
	* synth.programChange(0, 16);
	* ```
	*
	* @param channel The MIDI channel to use. It usually ranges from 0 to 15, but it depends on the channel count.
	* @param programNumber The MIDI program number to use. Ranges from 0 to 127.
	* @param eventOptions Additional options for this command.
	*/
	programChange(channel, programNumber, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendChannelMessage(MIDIMessageTypes.programChange, channel, [programNumber % 128], eventOptions);
	}
	/**
	* Sends a MIDI Sysex message to the synthesizer.
	*
	* > **Tip**
	* >
	* > Refer to the
	* > [MIDI Implementation](https://spessasus.github.io/spessasynth_core/extra/midi-implementation/#system-exclusives)
	* > for the list of supported System Exclusives.
	*
	* @example
	* ```js
	* // Send a GS DT1 Use Drums On Channel 10 (turn channel 10 into a drum channel)
	* synth.systemExclusive([
	*     0x41, 0x10, 0x42, 0x12, 0x40, 0x1a, 0x15, 0x01, 0x10, 0xf7
	* ]);
	*
	* // Send the same message with a channel offset of 10 (turn channel 20 into a drum channel)
	* synth.systemExclusive(
	*     [0x41, 0x10, 0x42, 0x12, 0x40, 0x1a, 0x15, 0x01, 0x10, 0xf7],
	*     10
	* );
	* ```
	*
	* @param messageData The message's data, excluding the F0 byte, but including the F7 at the end.
	* @param channelOffset The channel offset for the message as they usually can only address the first 16 channels.
	*   For example, to send a system exclusive on channel 16 (0-based),
	*   send a system exclusive for channel 0 and specify the channel offset to be 16.
	* @param eventOptions Additional options for this command.
	*/
	systemExclusive(messageData, channelOffset = 0, eventOptions = DEFAULT_SYNTH_METHOD_OPTIONS) {
		this.sendMessage([MIDIMessageTypes.systemExclusive, ...messageData], channelOffset, eventOptions);
	}
	/**
	* Tunes individual MIDI key numbers on a given program using the MIDI Tuning Standard.
	* Think of it as a pitch wheel but for individual notes.
	*
	* > **Warning**
	* >
	* > This tuning is not applied per channel, but per MIDI program number.
	*
	* @param program The MIDI program to tune. Ranges from 0 to 127.
	* @param tunings An array of tunings, each containing two properties:
	* - `sourceKey` - The MIDI note number to tune.
	* - `targetPitch` - The MIDI note number of the target pitch.
	*   Fractional values are allowed, for example, 60.5 is C plus 50 cents.
	*   A `targetPitch` of `-1` restores the default tuning.
	*/
	tuneKeys(program, tunings) {
		if (tunings.length > 127) throw new Error("Too many tunings. Maximum allowed is 127.");
		const systemExclusive = [
			127,
			16,
			8,
			2,
			program,
			tunings.length
		];
		for (const tuning of tunings) {
			systemExclusive.push(tuning.sourceKey);
			if (tuning.targetPitch === -1) systemExclusive.push(127, 127, 127);
			else {
				const midiNote = Math.floor(tuning.targetPitch);
				const fraction = Math.min(16383, Math.round((tuning.targetPitch - midiNote) * 16384));
				systemExclusive.push(midiNote, fraction >> 7 & 127, fraction & 127);
			}
		}
		systemExclusive.push(247);
		this.systemExclusive(systemExclusive);
	}
	/**
	* Yes please!
	*
	* Cranks the reverb up to the max and returns a string that says: `That's the spirit!`
	*/
	reverbateEverythingBecauseWhyNot() {
		for (let i = 0; i < this.midiChannels.length; i++) {
			this.controllerChange(i, MIDIControllers.reverbDepth, 127);
			this.midiChannels[i].lockController(MIDIControllers.reverbDepth, true);
		}
		return "That's the spirit!";
	}
	/**
	* INTERNAL USE ONLY!
	* Registers a promise that resolves with the next core
	* response of the given type, or rejects if the core reports that the operation failed.
	*
	* !! Call this before posting the request to avoid missing the response. !!
	* Every request of this type must get exactly one response, otherwise the waiters get out of order!
	* @param type INTERNAL USE ONLY!
	* @internal
	*/
	awaitCoreResponse(type) {
		return new Promise((resolve, reject) => {
			if (this.destroyed) {
				reject(/* @__PURE__ */ new Error("The synthesizer has been destroyed."));
				return;
			}
			let waiters = this.coreResponseWaiters.get(type);
			if (!waiters) {
				waiters = [];
				this.coreResponseWaiters.set(type, waiters);
			}
			waiters.push({
				resolve,
				reject
			});
		});
	}
	/**
	* INTERNAL USE ONLY!
	* @param callback the sequencer callback
	* @internal
	*/
	assignNewSequencer(callback) {
		this.post({
			channelNumber: -1,
			type: "requestNewSequencer",
			data: null
		});
		this.sequencers.push(callback);
		return this.sequencers.length - 1;
	}
	/**
	* INTERNAL USE ONLY!
	* Runs the given task with progress reporting for the given type.
	* Assigns the tracker before running and always revokes it afterward.
	* @param type INTERNAL USE ONLY!
	* @param progressFunction INTERNAL USE ONLY!
	* @param task INTERNAL USE ONLY!
	* @internal
	*/
	async withProgress(type, progressFunction, task) {
		if (this.renderingProgressTracker.has(type)) throw new Error(`Something is already being tracked for: ${type}!`);
		this.renderingProgressTracker.set(type, progressFunction);
		try {
			return await task();
		} finally {
			this.renderingProgressTracker.delete(type);
		}
	}
	/**
	* Handles the messages received from the core.
	* @internal
	*/
	handleMessages(messages) {
		for (const m of messages) try {
			switch (m.type) {
				case "eventCall":
					this.receiveEventCallback(m.data);
					if (this._systemParameters.eventsEnabled) this.eventHandler.callEventInternal(m.data.type, m.data.data);
					break;
				case "sequencerReturn":
					this.sequencers[m.data.id]?.(m.data);
					break;
				case "voiceCountChange": {
					let total = 0;
					for (let i = 0; i < m.data.length; i++) {
						this.midiChannels[i].voiceCount = m.data[i];
						total += m.data[i];
					}
					this._voiceCount = total;
					break;
				}
				case "isFullyInitialized":
					this.coreResponseWaiters.get(m.data.type)?.shift()?.resolve(m.data.data);
					break;
				case "coreResponseError":
					this.coreResponseWaiters.get(m.data.type)?.shift()?.reject(m.data.error);
					break;
				case "soundBankError":
					SpessaLog.warn(m.data);
					this.eventHandler.callEventInternal("soundBankError", m.data);
					break;
				case "renderingProgress": this.renderingProgressTracker.get(m.data.type)?.(m.data.data);
			}
		} catch (error) {
			reportError(error);
		}
	}
	addNewChannelInternal() {
		this.midiChannels.push(new LibMIDIChannel(this.midiChannels.length, this));
	}
	/**
	* Sends a channel voice message.
	* @param statusByte The status byte without the channel.
	* @param channel The channel number, which may be above 15.
	* @param data The data bytes.
	* @param eventOptions Additional options for this command.
	*/
	sendChannelMessage(statusByte, channel, data, eventOptions) {
		const ch = channel % 16;
		this.sendMessage([statusByte | ch, ...data], channel - ch, eventOptions);
	}
	/**
	* Handles the received event from core.
	* @param event The event received from the core.
	*/
	receiveEventCallback(event) {
		switch (event.type) {
			case "channelAdded":
				this.addNewChannelInternal();
				break;
			case "presetListChange":
				this.presetList = [...event.data];
				break;
			case "globalParamChange": {
				const { parameter, value } = event.data;
				this._midiParameters[parameter] = value;
				break;
			}
			case "channelParamChange": {
				const { channel, parameter, value } = event.data;
				this.midiChannels[channel].setMIDIParameter(parameter, value);
				break;
			}
			case "programChange":
				this.midiChannels[event.data.channel].patch = { ...event.data };
				break;
			case "reset":
				for (const c of this.midiChannels) c.reset();
				for (const [key, value] of Object.entries(DEFAULT_GLOBAL_MIDI_PARAMETERS)) if (!this.lockedMIDIParameters[key]) this._midiParameters[key] = value;
		}
	}
};
// src/sequencer/sequencer.ts
/**
* The sequencer plays MIDI sequences using a {@link WorkletSynthesizer} or {@link WorkerSynthesizer}.
*
* @group Sequencer
*/
var Sequencer = class {
	/**
	* The data of all the sequences, stored like the {@link Sequencer.midiData} property, but for all songs.
	* Allows creating playlists with the decoded titles and metadata.
	*/
	songListData = [];
	/**
	* The sequencer's event handler.
	* It allows setting up custom event listeners for the sequencer.
	*/
	eventHandler = new SeqEventHandler();
	/**
	* Indicates whether the sequencer has finished playing a sequence.
	*/
	isFinished = false;
	/**
	* The synthesizer attached to this sequencer.
	*/
	synth;
	/**
	* The data of the current sequence.
	* Undefined if the data is currently loading or if no song is playing.
	*
	* The {@link BasicMIDI.embeddedSoundBank} and {@link BasicMIDI.timeline} properties
	* and {@link MIDITrack.events} in the tracks are all empty to avoid copying the entire file between threads.
	*
	* > **Tip**
	* >
	* > To get the actual MIDI data, use the {@link Sequencer.getMIDI} method.
	*
	* > **Danger**
	* >
	* > The sequencer doesn't instantly get the new MIDI information.
	* > Make sure to listen for the {@link LibSequencerEvent.songChange `songChange`} event instead of waiting or assuming that the data is available instantly.
	* > Also keep in mind that the sequencer _preloads_ the samples for the MIDI.
	* > This can be disabled with `spessasynth_core`'s {@link SpessaSynthSequencer.preload}, accessible through {@link BasicSynthesizerCore.sequencers}.
	*/
	midiData;
	/**
	* The MIDI ports to play to.
	* Key is channel offset, value is the port.
	*/
	midiOutputs = /* @__PURE__ */ new Map();
	isLoading = false;
	/**
	* Indicates if the sequencer is paused.
	* Paused if a number, undefined if playing.
	*/
	pausedTime = 0;
	/**
	* Pending {@link Sequencer.getMIDI} calls, in the order they were requested.
	*/
	pendingMIDIRequests = new Array();
	highResTimeOffset = 0;
	/**
	* Absolute playback start time, based on the synth's time.
	*/
	absoluteStartTime;
	/**
	* For sending the messages to the correct `SpessaSynthSequencer` in core.
	*/
	sequencerID;
	/**
	* Creates a new MIDI sequencer for playing back MIDI files.
	*
	* > **Tip**
	* >
	* > As of `v4.1.0` you can connect more than 1 {@link Sequencer} to a synthesizer!
	*
	* @param synth The synthesizer instance to play back to.
	* @param options Optional configuration for the sequencer.
	*/
	constructor(synth, options = DEFAULT_SEQUENCER_OPTIONS) {
		this.synth = synth;
		this.absoluteStartTime = this.synth.currentTime;
		this.sequencerID = this.synth.assignNewSequencer(this.handleMessage.bind(this));
		const opts = fillWithDefaults(options, DEFAULT_SEQUENCER_OPTIONS);
		this._skipToFirstNoteOn = opts.skipToFirstNoteOn;
		if (opts.initialPlaybackRate !== 1) this.playbackRate = opts.initialPlaybackRate;
		if (!opts.skipToFirstNoteOn) this.sendMessage("setSkipToFirstNote", false);
	}
	_shuffledSongIndexes = [];
	/**
	* The shuffled song indexes, pointing to songs in {@link Sequencer.songListData}.
	* This is used when {@link Sequencer.shuffleSongs} is enabled.
	*/
	get shuffledSongIndexes() {
		return this._shuffledSongIndexes;
	}
	/**
	* Internal loop count marker (`Infinity` loops forever).
	*/
	_loopCount = 0;
	/**
	* The number of loops remaining until the loop is disabled.
	*/
	get loopCount() {
		return this._loopCount;
	}
	/**
	* The number of loops remaining until the loop is disabled.
	* Set to `Infinity` to loop forever.
	* It will automatically decrease by one every loop.
	* Set to 0 to disable loops.
	* @param val The new loop count.
	*/
	set loopCount(val) {
		this._loopCount = val;
		this.sendMessage("setLoopCount", val);
	}
	_songIndex = 0;
	/**
	* The current song number in the playlist.
	* If shuffle mode is enabled, this is the index of the shuffled song list.
	*/
	get songIndex() {
		return this._songIndex;
	}
	/**
	* Sets the current song number in the playlist.
	* If shuffle mode is enabled, this is the index of the shuffled song list.
	* @param value The new song index to set.
	*/
	set songIndex(value) {
		const count = this._songCount;
		if (count === 0) return;
		const index = (value % count + count) % count;
		if (index === this._songIndex) return;
		this.isLoading = true;
		this.midiData = void 0;
		this.sendMessage("changeSong", {
			changeType: "index",
			data: index
		});
	}
	_currentTempo = 120;
	/**
	* The current song's tempo in BPM.
	*/
	get currentTempo() {
		return this._currentTempo;
	}
	/**
	* The current song's length, in seconds.
	* 0 if no track is currently loaded.
	*/
	get duration() {
		return this.midiData?.duration ?? 0;
	}
	_songCount = 0;
	/**
	* The number of songs in the playlist.
	*/
	get songCount() {
		return this._songCount;
	}
	_skipToFirstNoteOn;
	/**
	* Indicates if the sequencer should skip to the first note-on event when the time is set before it.
	*/
	get skipToFirstNoteOn() {
		return this._skipToFirstNoteOn;
	}
	/**
	* Indicates if the sequencer should skip to the first note-on event when the time is set before it.
	* @param val The new value for this parameter.
	*/
	set skipToFirstNoteOn(val) {
		this._skipToFirstNoteOn = val;
		this.sendMessage("setSkipToFirstNote", this._skipToFirstNoteOn);
	}
	/**
	* Controls the playback's rate.
	*/
	_playbackRate = 1;
	/**
	* Controls how fast the song plays (1 is normal, 0.5 is half speed, etc.).
	*/
	get playbackRate() {
		return this._playbackRate;
	}
	/**
	* Controls how fast the song plays (1 is normal, 0.5 is half speed, etc.).
	* @param value The new playback rate.
	*/
	set playbackRate(value) {
		const t = this.currentTime;
		this.sendMessage("setPlaybackRate", value);
		this._playbackRate = value;
		this.recalculateStartTime(t);
	}
	_shuffleSongs = false;
	/**
	* Controls if the sequencer should shuffle the songs in the song list.
	* If true, the sequencer will play the songs in a random order.
	*
	* Songs are shuffled on a {@link Sequencer.loadNewSongList} call.
	*/
	get shuffleSongs() {
		return this._shuffleSongs;
	}
	/**
	* Controls if the sequencer should shuffle the songs in the song list.
	* If true, the sequencer will play the songs in a random order.
	*
	* Songs are shuffled on a {@link Sequencer.loadNewSongList} call.
	* @param value The new value for this parameter.
	*/
	set shuffleSongs(value) {
		this._shuffleSongs = value;
		if (value) this.sendMessage("changeSong", { changeType: "shuffleOn" });
		else this.sendMessage("changeSong", { changeType: "shuffleOff" });
	}
	_externalMIDIPlayback = false;
	/**
	* Enables or disables sending MIDI messages to the attached MIDI ports.
	*
	* > **Tip**
	* >
	* > Also see {@link MIDIDeviceHandler}.
	*/
	get externalMIDIPlayback() {
		return this._externalMIDIPlayback;
	}
	/**
	* Enables or disables sending MIDI messages to the attached MIDI ports.
	*
	* > **Tip**
	* >
	* > Also see {@link MIDIDeviceHandler}.
	* @param value The new value for this parameter.
	*/
	set externalMIDIPlayback(value) {
		this._externalMIDIPlayback = value;
		this.sendMessage("changeMIDIMessageSending", value);
	}
	/**
	* The current playback time of the song in seconds.
	*/
	get currentTime() {
		if (this.isLoading) return 0;
		if (this.pausedTime !== void 0) return this.pausedTime;
		return (this.synth.currentTime - this.absoluteStartTime) * this._playbackRate;
	}
	/**
	* Seeks to the specified time.
	* @param time The new time in seconds.
	*/
	set currentTime(time) {
		this.sendMessage("setTime", time);
	}
	/**
	* A smoothed version of {@link Sequencer.currentTime}.
	* Use for visualization as it's not affected by the `AudioContext` stutter.
	*/
	get currentHighResolutionTime() {
		if (this.pausedTime !== void 0) return this.pausedTime;
		const highResTimeOffset = this.highResTimeOffset;
		const absoluteStartTime = this.absoluteStartTime;
		const performanceElapsedTime = (performance.now() / 1e3 - absoluteStartTime) * this._playbackRate;
		let currentPerformanceTime = highResTimeOffset + performanceElapsedTime;
		const currentAudioTime = this.currentTime;
		const smoothingFactor = .01 * this._playbackRate;
		const timeDifference = currentAudioTime - currentPerformanceTime;
		this.highResTimeOffset += timeDifference * smoothingFactor;
		currentPerformanceTime = this.highResTimeOffset + performanceElapsedTime;
		return currentPerformanceTime;
	}
	/**
	* True if paused or stopped (including before the first {@link Sequencer.play} call and after the song ends), false if playing.
	*/
	get paused() {
		return this.pausedTime !== void 0;
	}
	/**
	* Gets the actual {@link BasicMIDI} sequence, complete with track data.
	* @throws Error If no MIDI is loaded.
	*/
	async getMIDI() {
		return new Promise((resolve, reject) => {
			this.pendingMIDIRequests.push({
				resolve,
				reject
			});
			this.sendMessage("getMIDI", null);
		});
	}
	/**
	* Loads a new song list.
	* Note that this does not start playing the songs automatically.
	* @param midiBuffers The MIDI files to play.
	*/
	loadNewSongList(midiBuffers) {
		this.isLoading = true;
		this.midiData = void 0;
		this.sendMessage("loadNewSongList", midiBuffers);
		this._songIndex = 0;
		this._songCount = midiBuffers.length;
	}
	/**
	* Connects a given MIDI output port and plays the sequence to it.
	*
	* > **Note**
	* >
	* > You can also use the {@link MIDIDeviceHandler}.
	*
	* > **Warning**
	* >
	* > Remember to enable {@link Sequencer.externalMIDIPlayback}!
	*
	* @param output The output to connect.
	* @param channelOffset The channel offset of this output for multi-port files. For example 0 means the first port, 16 means the second port and so on.
	*/
	connectMIDIOutput(output, channelOffset = 0) {
		if (!output) throw new Error("No MIDI output provided. Use disconnectMIDIOutput to disconnect an output.");
		this.resetMIDIOutput();
		if (this.midiOutputs.size === 0) window.addEventListener("beforeunload", this.resetOnUnload);
		this.midiOutputs.set(channelOffset, output);
	}
	/**
	* Disconnects a given MIDI output port from the sequencer.
	*
	* > **Warning**
	* >
	* > Remember to disable {@link Sequencer.externalMIDIPlayback}
	* > if you want to use the synthesizer for playback.
	*
	* @param output The output to disconnect.
	*/
	disconnectMIDIOutput(output) {
		for (const [key, value] of this.midiOutputs) if (value === output) {
			this.midiOutputs.delete(key);
			if (this.midiOutputs.size === 0) window.removeEventListener("beforeunload", this.resetOnUnload);
			return;
		}
	}
	/**
	* Pauses the playback of the sequence.
	*/
	pause() {
		if (this.paused) return;
		this.pausedTime = this.currentTime;
		this.sendMessage("pause", null);
	}
	/**
	* Starts playing or resumes the sequence.
	*/
	play() {
		if (this.pausedTime !== void 0) {
			this.recalculateStartTime(this.pausedTime);
			this.pausedTime = void 0;
		}
		this.isFinished = false;
		this.sendMessage("play", null);
	}
	/**
	* Resets the connected MIDI outputs when the page is closed.
	*/
	resetOnUnload = () => this.resetMIDIOutput();
	handleMessage(m) {
		switch (m.type) {
			case "midiMessage": {
				const midiEvent = m.data;
				const midiEventData = midiEvent.message;
				if (this.midiOutputs.size > 0 && midiEventData[0] >= 128) {
					(this.midiOutputs.get(midiEvent.channelOffset) ?? this.midiOutputs.values().next().value).send(midiEventData);
					return;
				}
				break;
			}
			case "songChange": {
				this._songIndex = m.data.songIndex;
				const idx = this._shuffleSongs ? this._shuffledSongIndexes[this._songIndex] : this._songIndex;
				const songChangeData = this.songListData[idx];
				this.midiData = songChangeData;
				this.isLoading = false;
				this.absoluteStartTime = 0;
				this.callEventInternal("songChange", {
					songIndex: m.data.songIndex,
					midiData: songChangeData
				});
				break;
			}
			case "sync":
				if (Math.abs(m.data - this.currentTime) > .05) this.recalculateStartTime(m.data);
				break;
			case "timeChange": {
				const time = m.data.newTime;
				this.recalculateStartTime(time);
				this.callEventInternal("timeChange", m.data);
				break;
			}
			case "songEnded":
				this.pausedTime = this.currentTime;
				this.isFinished = true;
				this.callEventInternal("songEnded", m.data);
				break;
			case "midiError":
				this.callEventInternal("midiError", m.data);
				break;
			case "getMIDI":
				this.pendingMIDIRequests.shift()?.resolve(BasicMIDI.copyFrom(m.data));
				break;
			case "getMIDIError":
				this.pendingMIDIRequests.shift()?.reject(m.data);
				break;
			case "metaEvent": {
				const event = m.data.event;
				switch (event.statusByte) {
					case MIDIMessageTypes.setTempo:
						this._currentTempo = 6e7 / SpessaSynthCoreUtils.readBigEndian(event.data, 3);
						break;
					case MIDIMessageTypes.text:
					case MIDIMessageTypes.lyric:
					case MIDIMessageTypes.copyright:
					case MIDIMessageTypes.trackName:
					case MIDIMessageTypes.marker:
					case MIDIMessageTypes.cuePoint:
					case MIDIMessageTypes.instrumentName:
					case MIDIMessageTypes.programName: {
						if (!this.midiData) break;
						const lyricsIndex = event.statusByte === MIDIMessageTypes.lyric || this.midiData.isKaraokeFile && event.statusByte === MIDIMessageTypes.text ? this.midiData.lyrics.findIndex((l) => l.ticks === event.ticks) : -1;
						this.callEventInternal("textEvent", {
							event,
							lyricsIndex
						});
						break;
					}
				}
				this.callEventInternal("metaEvent", m.data);
				break;
			}
			case "loopCountChange":
				this._loopCount = m.data.newCount;
				this.callEventInternal("loopCountChange", m.data);
				break;
			case "songListChange":
				this.songListData = m.data.newSongList.map((m) => new MIDIData(m));
				this._shuffledSongIndexes = m.data.shuffledSongIndexes;
		}
	}
	callEventInternal(type, data) {
		this.eventHandler.callEventInternal(type, data);
	}
	resetMIDIOutput() {
		for (const output of this.midiOutputs.values()) {
			for (let i = 0; i < 16; i++) {
				output.send([
					MIDIMessageTypes.controllerChange | i,
					MIDIControllers.allNotesOff,
					0
				]);
				output.send([
					MIDIMessageTypes.controllerChange | i,
					MIDIControllers.resetAllControllers,
					0
				]);
			}
			output.send([MIDIMessageTypes.systemExclusive, ...MIDIUtils.gs(64, 0, 127, [0])]);
		}
	}
	recalculateStartTime(time) {
		this.absoluteStartTime = this.synth.currentTime - time / this._playbackRate;
		this.highResTimeOffset = (this.synth.currentTime - performance.now() / 1e3) * this._playbackRate;
		if (this.paused) this.pausedTime = time;
	}
	sendMessage(messageType, messageData) {
		this.synth.post({
			channelNumber: -1,
			type: "sequencerSpecific",
			data: {
				type: messageType,
				data: messageData,
				id: this.sequencerID
			}
		});
	}
};
// src/synthesizer/basic/reverb_passthrough.ts
/**
* Captures the reverb input of the GS reverb processor (convolver mode).
*/
var GSReverbCapture = class {
	character = 0;
	delayFeedback = 0;
	level = 64;
	preDelayTime = 0;
	preLowpass = 0;
	time = 0;
	capture;
	constructor(capture) {
		this.capture = capture;
	}
	getSnapshot() {
		return {
			character: this.character,
			delayFeedback: this.delayFeedback,
			level: this.level,
			preDelayTime: this.preDelayTime,
			preLowpass: this.preLowpass,
			time: this.time
		};
	}
	applySnapshot(snapshot) {
		this.character = snapshot.character;
		this.delayFeedback = snapshot.delayFeedback;
		this.level = snapshot.level;
		this.preDelayTime = snapshot.preDelayTime;
		this.preLowpass = snapshot.preLowpass;
		this.time = snapshot.time;
	}
	process(input, _outputLeft, _outputRight, _startIndex, sampleCount) {
		const gain = this.level / 64;
		const { capturedLeft, capturedRight } = this.capture;
		for (let i = 0; i < sampleCount; i++) {
			const sample = input[i] * gain;
			capturedLeft[i] = sample;
			capturedRight[i] = sample;
		}
	}
};
/**
* Captures the stereo reverb input of the XG reverb block (convolver mode).
*/
var XGReverbCapture = class {
	capture;
	parameters;
	constructor(capture, sampleRate) {
		this.capture = capture;
		this.parameters = new DefaultXGReverb(sampleRate, SPESSA_BUFSIZE);
	}
	get return() {
		return this.parameters.return;
	}
	set return(value) {
		this.parameters.return = value;
	}
	get pan() {
		return this.parameters.pan;
	}
	set pan(value) {
		this.parameters.pan = value;
	}
	setType(type) {
		this.parameters.setType(type);
	}
	getType() {
		return this.parameters.getType();
	}
	reset() {
		this.parameters.reset();
	}
	setParameter(param, value) {
		this.parameters.setParameter(param, value);
	}
	getSnapshot() {
		return this.parameters.getSnapshot();
	}
	applySnapshot(snapshot) {
		this.parameters.applySnapshot(snapshot);
	}
	process(inputLeft, inputRight, _outputLeft, _outputRight, _startIndex, sampleCount) {
		const gain = this.parameters.return / 64;
		const pan = Math.min(127, Math.max(1, this.parameters.pan));
		const gainLeft = gain * Math.min(1, (127 - pan) / 63);
		const gainRight = gain * Math.min(1, (pan - 1) / 63);
		const { capturedLeft, capturedRight } = this.capture;
		for (let i = 0; i < sampleCount; i++) {
			capturedLeft[i] = inputLeft[i] * gainLeft;
			capturedRight[i] = inputRight[i] * gainRight;
		}
	}
};
/**
* Passthrough for capturing reverb (convolver mode).
* Pass {@link gsReverb} and {@link xgReverb} to the synthesizer.
* Both write into the same stereo capture, as only one of them is active at a time.
*/
var ReverbCapture = class {
	capturedLeft = new Float32Array(SPESSA_BUFSIZE);
	capturedRight = new Float32Array(SPESSA_BUFSIZE);
	gsReverb;
	xgReverb;
	constructor(sampleRate) {
		this.gsReverb = new GSReverbCapture(this);
		this.xgReverb = new XGReverbCapture(this, sampleRate);
	}
	/**
	* Copies the captured data into the output and clears the capture.
	* Clearing ensures that the last block does not repeat when the reverb is not processed (e.g. effects are disabled).
	* @param outputLeft The left output buffer.
	* @param outputRight The right output buffer.
	* @param startIndex The index to start writing at in the output buffers.
	* @param sampleCount The amount of samples to move.
	*/
	moveCapturedData(outputLeft, outputRight, startIndex, sampleCount) {
		const { capturedLeft, capturedRight } = this;
		for (let i = 0; i < sampleCount; i++) {
			const outIndex = startIndex + i;
			outputLeft[outIndex] = capturedLeft[i];
			outputRight[outIndex] = capturedRight[i];
			capturedLeft[i] = 0;
			capturedRight[i] = 0;
		}
	}
};
// src/synthesizer/basic/basic_synthesizer_core.ts
const SEQUENCER_SYNC_INTERVAL = 1;
const VOICE_COUNT_SYNC_INTERVAL = 1 / 30;
/**
* The events that the main thread needs to mirror the synthesizer state.
* They are sent even if the event system is disabled.
*/
const STATE_SYNC_EVENTS = /* @__PURE__ */ new Set([
	"channelAdded",
	"presetListChange",
	"programChange",
	"globalParamChange",
	"channelParamChange",
	"reset"
]);
/**
* The interface for the audio processing code that uses `spessasynth_core` and runs on a separate thread.
* It runs in a Web Worker for {@link WorkerSynthesizer} and in an AudioWorklet for {@link WorkletSynthesizer}.
*
* It provides raw access to `spessasynth_core` {@link SpessaSynthProcessor} and {@link SpessaSynthSequencer} instances.
* @group Synthesizer.Basic
*/
var BasicSynthesizerCore = class {
	/**
	* The synthesizer instance.
	* This property allows for direct access to the audio engine.
	*/
	synthesizer;
	/**
	* The array of sequencers to use with the synthesizer.
	* This property allows for direct access to the sequencers and their MIDI data.
	*/
	sequencers = new Array();
	postInternal;
	/**
	* For syncing voice counts, implemented separately in the `process()` method.
	* @internal
	*/
	voiceCounts = new Array(16).fill(0);
	/**
	* If the events not needed for the state sync should be sent to the main thread.
	* The processor's own event system always stays enabled.
	* @internal
	*/
	eventsEnabled;
	/**
	* In this mode, the reverb is captured and sent to the main thread for a `ConvolverNode` to process.
	* @internal
	*/
	convolverMode;
	reverbCapture;
	/**
	* Set once the core is destroyed. Messages are ignored from then on.
	* @internal
	*/
	destroyed = false;
	lastSequencerSync = 0;
	lastVoiceCountSync = 0;
	/**
	* A message queue for sending many messages in bulk as one.
	* @internal
	*/
	messageQueue = new Array();
	/**
	* The transferable part of the queue.
	* @internal
	*/
	messageQueueTransferable = new Array();
	/**
	* If the queue is active, all messages will be queued. If not, they will be sent immediately.
	* @internal
	*/
	messageQueueActive = false;
	constructor(synthCoreConfig, postMessage) {
		this.reverbCapture = synthCoreConfig.convolverMode ? new ReverbCapture(synthCoreConfig.sampleRate) : void 0;
		this.synthesizer = new SpessaSynthProcessor(synthCoreConfig.sampleRate, {
			...synthCoreConfig.processorConfig,
			eventsEnabled: true,
			initialTime: synthCoreConfig.initialTime,
			gsReverbProcessor: this.reverbCapture?.gsReverb,
			xgReverbBlock: this.reverbCapture?.xgReverb
		});
		this.eventsEnabled = synthCoreConfig.processorConfig.eventsEnabled ?? true;
		this.convolverMode = synthCoreConfig.convolverMode;
		this.postInternal = postMessage;
		this.synthesizer.onEventCall = (event) => {
			if (event.type === "channelAdded") {
				const l = this.synthesizer.midiChannels.length;
				for (let i = this.voiceCounts.length; i < l; i++) this.voiceCounts.push(0);
			}
			if (!this.eventsEnabled && !STATE_SYNC_EVENTS.has(event.type)) return;
			this.post({
				type: "eventCall",
				data: event,
				currentTime: this.synthesizer.currentTime
			});
		};
	}
	/**
	* Starts processing an audio block: queues the messages and advances the sequencers.
	* Call before rendering the block.
	*/
	startBlock() {
		this.messageQueueActive = true;
		for (const seq of this.sequencers) seq.processTick();
	}
	/**
	* Finishes processing an audio block: syncs the sequencers and voice counts, then sends the queued messages.
	* Call after rendering the block.
	*/
	finishBlock() {
		const t = this.synthesizer.currentTime;
		if (t - this.lastSequencerSync > SEQUENCER_SYNC_INTERVAL) {
			for (let id = 0; id < this.sequencers.length; id++) this.post({
				type: "sequencerReturn",
				data: {
					type: "sync",
					data: this.sequencers[id].currentTime,
					id
				},
				currentTime: t
			});
			this.lastSequencerSync = t;
		}
		if (t - this.lastVoiceCountSync >= VOICE_COUNT_SYNC_INTERVAL) {
			this.lastVoiceCountSync = t;
			const c = this.synthesizer.midiChannels;
			const cv = this.voiceCounts;
			let updateChannels = false;
			for (let i = 0; i < c.length; i++) {
				updateChannels ||= c[i].voiceCount !== cv[i];
				cv[i] = c[i].voiceCount;
			}
			if (updateChannels) this.post({
				type: "voiceCountChange",
				currentTime: t,
				data: cv
			});
		}
		this.flushMessageQueue();
	}
	post(data, transfer) {
		if (this.messageQueueActive) {
			this.messageQueue.push(data);
			if (transfer) this.messageQueueTransferable.push(...transfer);
		} else this.postInternal([data], transfer);
	}
	createNewSequencer() {
		const sequencer = new SpessaSynthSequencer(this.synthesizer);
		const sequencerID = this.sequencers.length;
		this.sequencers.push(sequencer);
		sequencer.onEventCall = (e) => {
			if (e.type === "songListChange") {
				const midiDatas = e.data.newSongList.map((s) => {
					return new MIDIData(s);
				});
				this.post({
					type: "sequencerReturn",
					data: {
						type: e.type,
						data: {
							newSongList: midiDatas,
							shuffledSongIndexes: sequencer.shuffledSongIndexes
						},
						id: sequencerID
					},
					currentTime: this.synthesizer.currentTime
				});
				return;
			}
			this.post({
				type: "sequencerReturn",
				data: {
					...e,
					id: sequencerID
				},
				currentTime: this.synthesizer.currentTime
			});
		};
	}
	postReady(type, data, transferable = []) {
		this.post({
			type: "isFullyInitialized",
			data: {
				type,
				data
			},
			currentTime: this.synthesizer.currentTime
		}, transferable);
	}
	/**
	* Rejects the main thread promise waiting for the given response type.
	* @param type The response type that will not arrive.
	* @param error The reason of the failure.
	*/
	postError(type, error) {
		this.post({
			type: "coreResponseError",
			data: {
				type,
				error
			},
			currentTime: this.synthesizer.currentTime
		});
	}
	postProgress(type, data) {
		this.post({
			type: "renderingProgress",
			data: {
				type,
				data
			},
			currentTime: this.synthesizer.currentTime
		});
	}
	destroy() {
		this.destroyed = true;
		this.synthesizer.destroy();
		this.sequencers.length = 0;
	}
	handleMessage(m) {
		if (this.destroyed) return;
		const wasActive = this.messageQueueActive;
		this.messageQueueActive = true;
		try {
			this.processMessage(m);
		} finally {
			if (!wasActive) this.flushMessageQueue();
		}
	}
	flushMessageQueue() {
		this.messageQueueActive = false;
		if (this.messageQueue.length > 0) this.postInternal(this.messageQueue, this.messageQueueTransferable);
		this.messageQueue.length = 0;
		this.messageQueueTransferable.length = 0;
	}
	processMessage(m) {
		const channel = m.channelNumber;
		let channelObject;
		if (channel >= 0) {
			channelObject = this.synthesizer.midiChannels[channel];
			if (channelObject === void 0) {
				SpessaLog.warn(`Trying to access channel ${channel} which does not exist... ignoring!`);
				return;
			}
		}
		switch (m.type) {
			case "midiMessage":
				this.synthesizer.processMessage(m.data.messageData, m.data.channelOffset, m.data.options);
				break;
			case "ccReset":
				this.synthesizer.reset();
				break;
			case "stopAll":
				if (channel === -1) this.synthesizer.stopAll(m.data === 1);
				else channelObject?.stopAllNotes(m.data === 1);
				break;
			case "addNewChannel":
				this.synthesizer.createMIDIChannel();
				break;
			case "lockGlobalMIDIParameter":
				this.synthesizer.lockMIDIParameter(m.data.parameter, m.data.isLocked);
				break;
			case "setGlobalSystemParameter":
				if (m.data.parameter === "eventsEnabled") {
					this.eventsEnabled = m.data.value;
					break;
				}
				this.synthesizer.setSystemParameter(m.data.parameter, m.data.value);
				break;
			case "lockChannelMIDIParameter":
				channelObject?.lockMIDIParameter(m.data.parameter, m.data.isLocked);
				break;
			case "setChannelSystemParameter":
				channelObject?.setSystemParameter(m.data.parameter, m.data.value);
				break;
			case "setDrums":
				channelObject?.setDrums(m.data);
				break;
			case "lockController":
				channelObject?.lockController(m.data.controller, m.data.isLocked);
				break;
			case "sequencerSpecific": {
				const seq = this.sequencers[m.data.id];
				if (!seq) return;
				const seqMsg = m.data;
				switch (seqMsg.type) {
					case "loadNewSongList":
						try {
							const songMap = seqMsg.data.map((s) => "duration" in s ? BasicMIDI.copyFrom(s) : BasicMIDI.fromArrayBuffer(s.binary, s.fileName));
							seq.loadNewSongList(songMap);
						} catch (error) {
							SpessaLog.warn(error);
							this.post({
								type: "sequencerReturn",
								data: {
									type: "midiError",
									data: error,
									id: m.data.id
								},
								currentTime: this.synthesizer.currentTime
							});
						}
						break;
					case "pause":
						seq.pause();
						break;
					case "play":
						seq.play();
						break;
					case "setTime":
						seq.currentTime = seqMsg.data;
						break;
					case "changeMIDIMessageSending":
						seq.externalMIDIPlayback = seqMsg.data;
						break;
					case "setPlaybackRate":
						seq.playbackRate = seqMsg.data;
						break;
					case "setLoopCount":
						seq.loopCount = seqMsg.data;
						break;
					case "changeSong":
						switch (seqMsg.data.changeType) {
							case "shuffleOff":
								seq.shuffleMode = false;
								break;
							case "shuffleOn":
								seq.shuffleMode = true;
								break;
							case "index": if (seqMsg.data.data !== void 0) seq.songIndex = seqMsg.data.data;
						}
						break;
					case "getMIDI":
						if (!seq.midiData) {
							this.post({
								type: "sequencerReturn",
								data: {
									type: "getMIDIError",
									data: /* @__PURE__ */ new Error("No MIDI is loaded!"),
									id: m.data.id
								},
								currentTime: this.synthesizer.currentTime
							});
							break;
						}
						this.post({
							type: "sequencerReturn",
							data: {
								type: "getMIDI",
								data: seq.midiData,
								id: m.data.id
							},
							currentTime: this.synthesizer.currentTime
						});
						break;
					case "setSkipToFirstNote": seq.skipToFirstNoteOn = seqMsg.data;
				}
				break;
			}
			case "soundBankManager":
				try {
					const sfManager = this.synthesizer.soundBankManager;
					const sfManMsg = m.data;
					switch (sfManMsg.type) {
						case "addSoundBank": {
							const font = SoundBankLoader.fromArrayBuffer(sfManMsg.data.soundBankBuffer);
							sfManager.addSoundBank(font, sfManMsg.data.id, sfManMsg.data.bankOffset);
							this.postReady("soundBankManager", null);
							break;
						}
						case "deleteSoundBank":
							sfManager.deleteSoundBank(sfManMsg.data);
							this.postReady("soundBankManager", null);
							break;
						case "rearrangeSoundBanks":
							sfManager.priorityOrder = sfManMsg.data;
							this.postReady("soundBankManager", null);
					}
				} catch (error) {
					this.post({
						type: "soundBankError",
						data: error,
						currentTime: this.synthesizer.currentTime
					});
					this.postError("soundBankManager", error);
				}
				break;
			case "requestSynthesizerSnapshot": {
				const snapshot = this.synthesizer.getSnapshot();
				this.postReady("synthesizerSnapshot", snapshot);
				break;
			}
			case "requestNewSequencer":
				this.createNewSequencer();
				break;
			case "setLogLevel":
				SpessaLog.setLogLevel(m.data.enableInfo, m.data.enableWarning, m.data.enableGroup);
				break;
			case "destroyWorklet":
				this.destroy();
				break;
			default: SpessaLog.warn("Unrecognized event!", m);
		}
	}
};
// src/synthesizer/worker/playback_worklet.ts
const PLAYBACK_WORKLET_PROCESSOR_NAME = `spessasynth-playback-worklet-processor`;
function getPlaybackWorkletURL(maxQueuedChunks) {
	const PLAYBACK_WORKLET_CODE = `
const BLOCK_SIZE = 128;

const MAX_QUEUED = ${maxQueuedChunks};

/**
 * An AudioWorkletProcessor that plays back dynamic streams of stereo audio.
 */
class PlaybackProcessor extends AudioWorkletProcessor
{
    /** 
     * @type {Float32Array[]}
     */
    data = [];
    
    /**
     * Played buffers, returned to the worker with the next request to be reused.
     * @type {Float32Array[]}
     */
    free = [];
    
    updateRequested = false;
    
    alive = true;
    
    /**
     * @type {MessagePort}
     */
    sentPort;
    
    constructor()
    {
        super();
        
        /**
         * @param e {MessageEvent}
         */
        this.port.onmessage = (e) =>
        {
            if (e.ports.length)
            {
                const sentPort = e.ports[0];
                this.sentPort = sentPort;
                sentPort.onmessage = (e) =>
                {
                    if (e.data === null)
                    {
                        // the worker synthesizer is dead
                        this.alive = false;
                        return;
                    }
                    this.data.push(e.data);
                    this.updateRequested = false;
                    // if we need more, request immediately
                    if (this.data.length < MAX_QUEUED)
                    {
                        this.requestData();
                    }
                };
                
            }
        };
    }
    
    /**
     * Requests a new chunk from the worker, returning a played buffer if there is one.
     */
    requestData()
    {
        const buffer = this.free.pop();
        if (buffer)
        {
            this.sentPort.postMessage(buffer, [buffer.buffer]);
        }
        else
        {
            this.sentPort.postMessage(null);
        }
    }
    
    // noinspection JSUnusedGlobalSymbols
    /**
     * @param _inputs {[Float32Array, Float32Array][]}
     * @param outputs {[Float32Array, Float32Array][]}
     * @returns {boolean}
     */
    process(_inputs, outputs)
    {
        const data = this.data.shift();
        if (!data)
        {
            return this.alive;
        }

        const stereoPairs = Math.floor(data.length / (BLOCK_SIZE * 2));
        const channels = Math.min(outputs.length, stereoPairs);

        // copy without subarray() to avoid allocating on the audio thread
        let offset = 0;
        for (let i = 0; i < channels; i++)
        {
            const left = outputs[i][0];
            const right = outputs[i][1];
            for (let j = 0; j < BLOCK_SIZE; j++)
            {
                left[j] = data[offset + j];
                right[j] = data[offset + BLOCK_SIZE + j];
            }
            offset += BLOCK_SIZE * 2;
        }
        this.free.push(data);
        
        // if it has already been requested, we need to wait
        if (!this.updateRequested)
        {
            this.requestData();
            this.updateRequested = true;
        }
        
        // keep it online
        return this.alive;
    }
}
registerProcessor("${PLAYBACK_WORKLET_PROCESSOR_NAME}", PlaybackProcessor);
    `;
	const blob = new Blob([PLAYBACK_WORKLET_CODE], { type: "application/javascript" });
	return URL.createObjectURL(blob);
}
// src/synthesizer/worker/render_audio_worker.ts
const DEFAULT_WORKER_RENDER_AUDIO_OPTIONS = {
	extraTime: 2,
	separateChannels: false,
	loopCount: 0,
	progressCallback: void 0,
	preserveSynthParams: true,
	enableEffects: true,
	sequencerID: 0
};
const RENDER_BLOCKS_PER_PROGRESS = 64;
const BLOCK_SIZE$1 = 128;
function renderAudioWorker(sampleRate, options) {
	const reverbCapture = this.convolverMode ? new ReverbCapture(sampleRate) : void 0;
	const rendererSynth = new SpessaSynthProcessor(sampleRate, {
		eventsEnabled: false,
		gsReverbProcessor: reverbCapture?.gsReverb,
		xgReverbBlock: reverbCapture?.xgReverb
	});
	for (const entry of this.synthesizer.soundBankManager.soundBankList) rendererSynth.soundBankManager.addSoundBank(entry.soundBank, entry.id, entry.bankOffset);
	rendererSynth.soundBankManager.priorityOrder = this.synthesizer.soundBankManager.priorityOrder;
	const seq = this.sequencers[options.sequencerID];
	const parsedMid = seq?.midiData;
	if (!parsedMid) throw new Error("No MIDI is loaded!");
	const playbackRate = options.preserveSynthParams ? seq.playbackRate : 1;
	const loopStartAbsolute = parsedMid.midiTicksToSeconds(parsedMid.loop.start) / playbackRate;
	const loopDuration = parsedMid.midiTicksToSeconds(parsedMid.loop.end) / playbackRate - loopStartAbsolute;
	const duration = parsedMid.duration / playbackRate + options.extraTime + loopDuration * options.loopCount;
	const sampleDuration = Math.ceil(sampleRate * duration);
	const rendererSeq = new SpessaSynthSequencer(rendererSynth);
	rendererSeq.loopCount = options.loopCount;
	rendererSeq.playbackRate = playbackRate;
	if (options.preserveSynthParams) {
		const snapshot = this.synthesizer.getSnapshot();
		rendererSynth.applySnapshot(snapshot);
	}
	rendererSynth.setSystemParameter("autoAllocateVoices", true);
	rendererSynth.setSystemParameter("effectsEnabled", options.enableEffects);
	rendererSeq.loadNewSongList([parsedMid]);
	rendererSeq.play();
	const outL = new Float32Array(sampleDuration);
	const outR = new Float32Array(sampleDuration);
	const returnedChunks = {
		output: [outL, outR],
		visual: []
	};
	let convolver = void 0;
	if (reverbCapture) {
		convolver = [new Float32Array(sampleDuration), new Float32Array(sampleDuration)];
		returnedChunks.convolver = convolver;
	}
	const outputCount = options.separateChannels ? 16 : 1;
	for (let i = 0; i < outputCount; i++) {
		const d = [new Float32Array(sampleDuration), new Float32Array(sampleDuration)];
		returnedChunks.visual.push(d);
	}
	let index = 0;
	while (true) {
		for (let i = 0; i < RENDER_BLOCKS_PER_PROGRESS; i++) {
			rendererSeq.processTick();
			const sampleCount = Math.min(index + BLOCK_SIZE$1, sampleDuration) - index;
			rendererSynth.process(outL, outR, index, sampleCount, returnedChunks.visual);
			if (convolver) reverbCapture.moveCapturedData(convolver[0], convolver[1], index, sampleCount);
			index += sampleCount;
			if (index >= sampleDuration) return returnedChunks;
		}
		this.postProgress("renderAudio", index / sampleDuration);
	}
}
// src/utils/resample_audio_buffer.ts
/**
* Resamples an AudioBuffer to a new sample rate
* @param audioBuffer - the original buffer
* @param sampleRate - target rate in Hz
* @returns the resampled buffer
*/
async function resampleAudioBuffer(audioBuffer, sampleRate) {
	if (audioBuffer.sampleRate === sampleRate) return audioBuffer;
	const targetLength = Math.ceil(audioBuffer.duration * sampleRate);
	const offlineCtx = new OfflineAudioContext(audioBuffer.numberOfChannels, targetLength, sampleRate);
	const source = offlineCtx.createBufferSource();
	source.buffer = audioBuffer;
	source.connect(offlineCtx.destination);
	source.start(0);
	return await offlineCtx.startRendering();
}
// src/synthesizer/worker/worker_synthesizer.ts
const DEFAULT_BANK_WRITE_OPTIONS = {
	trim: true,
	bankID: "",
	writeEmbeddedSoundBank: true,
	sequencerID: 0
};
const DEFAULT_SF2_WRITE_OPTIONS = {
	...DEFAULT_BANK_WRITE_OPTIONS,
	writeDefaultModulators: true,
	writeExtendedLimits: true,
	compressionAction: "keep",
	compressionQuality: 1,
	software: "SpessaSynth"
};
const DEFAULT_RMIDI_WRITE_OPTIONS = {
	applySnapshot: false,
	bankOffset: 0,
	correctBankOffset: true,
	metadata: {},
	format: "sf2",
	...DEFAULT_SF2_WRITE_OPTIONS
};
const DEFAULT_DLS_WRITE_OPTIONS = {
	...DEFAULT_BANK_WRITE_OPTIONS,
	software: "SpessaSynth"
};
function makeAudioBuffer(pair, sampleRate) {
	const buffer = new AudioBuffer({
		sampleRate,
		numberOfChannels: 2,
		length: pair[0].length
	});
	buffer.copyToChannel(pair[0], 0);
	buffer.copyToChannel(pair[1], 1);
	return buffer;
}
function mergeStereoAudioBuffer(into, from) {
	const channelCount = Math.min(into.numberOfChannels, from.numberOfChannels);
	for (let ch = 0; ch < channelCount; ch++) {
		const data = into.getChannelData(ch);
		const src = from.getChannelData(ch);
		for (let i = 0; i < data.length; i++) data[i] += src[i];
	}
}
/**
* This synthesizer uses a Worker communicating with an AudioWorklet to provide real-time playback along with methods to export the data in various formats.
*
* > **Tip**
* >
* > A comparison of both synthesizers [can be found here](../../../docs/extra/comparing-synthesizers.md).
*
* > **Note**
* >
* > An example demonstrating the capabilities of this synthesizer [can be found here](../../../docs/getting-started/worker-synth-example.md).
*
* @group Synthesizer.Worker
*/
var WorkerSynthesizer = class extends BasicSynthesizer {
	/**
	* Time offset for syncing with the synth.
	*/
	timeOffset = 0;
	/**
	* Creates a new instance of a Worker-based synthesizer.
	*
	* > **Warning**
	* >
	* > Make sure to {@link WorkerSynthesizer.registerPlaybackWorklet}!
	*
	* > **Important**
	* >
	* > Also see {@link WorkerSynthesizerCore} for initializing the worker side of the synthesizer.
	*
	* @example
	* Below is a simple example of creating a new synthesizer.
	* Note that the two snippets are two files, one for the worker and one in the main thread.
	*
	* ```js
	* // Worker
	* let workerSynthCore;
	* // Wait for the first message with parameters
	* onmessage = (e) => {
	*     if (e.ports[0]) {
	*         // Initialize
	*         workerSynthCore = new WorkerSynthesizerCore(
	*             e.data,
	*             e.ports[0],
	*             postMessage.bind(globalThis)
	*         );
	*     } else {
	*         // Handle all other messages
	*         void workerSynthCore.handleMessage(e.data);
	*     }
	* };
	* ```
	*
	* ```ts
	* // Main thread
	* // Create the audio context
	* const context = new AudioContext({
	*     sampleRate: 44100
	* });
	* // Register the worklet
	* await WorkerSynthesizer.registerPlaybackWorklet(context);
	* // Create the worker
	* const worker = new Worker(
	*     // Make sure that your path is correct
	*     new URL("worker.js", import.meta.url)
	* );
	* // Create the synthesizer and bind it to the worker
	* const synth = new WorkerSynthesizer(context, worker.postMessage.bind(worker));
	* worker.onmessage = (e) => synth.handleWorkerMessage(e.data);
	* ```
	*
	* @param context The audio context for the synthesizer to use.
	* @param workerPostMessage The `postMessage` function of the Worker that the synthesizer will use.
	* The raw `Worker.postMessage` can be passed here.
	* This can be used for intercepting messages.
	* @param config Optional configuration for the synthesizer.
	*/
	constructor(context, workerPostMessage, config = DEFAULT_SYNTH_CONFIG) {
		const synthConfig = fillWithDefaults(config, DEFAULT_SYNTH_CONFIG);
		super(context, PLAYBACK_WORKLET_PROCESSOR_NAME, synthConfig, workerPostMessage);
		const messageChannel = new MessageChannel();
		const workerPort = messageChannel.port1;
		const workletPort = messageChannel.port2;
		this.worklet.port.postMessage(null, [workletPort]);
		workerPostMessage({
			initialTime: this.context.currentTime,
			sampleRate: this.context.sampleRate,
			convolverMode: synthConfig.convolverMode,
			processorConfig: {
				eventsEnabled: synthConfig.eventsEnabled,
				decodedSampleBudget: synthConfig.decodedSampleBudget
			}
		}, [workerPort]);
	}
	/**
	* Returns the adjusted time, in sync with the worker's internal time which may differ from the `AudioContext` time.
	*/
	get currentTime() {
		return this.context.currentTime + this.timeOffset;
	}
	/**
	* Registers an audio worklet for the WorkerSynthesizer.
	* @param context The context to register the worklet for.
	* @param maxQueueSize The maximum number of 128-sample chunks to store in the worklet.
	* Higher values result in fewer dropouts but higher latency.
	*/
	static async registerPlaybackWorklet(context, maxQueueSize = 20) {
		if (!context?.audioWorklet.addModule) throw new Error("Audio worklet is not supported.");
		return context.audioWorklet.addModule(getPlaybackWorkletURL(maxQueueSize));
	}
	/**
	* Handles a return message from the worker.
	*
	* Usually you're going to do
	* ```ts
	* yourWorker.onmessage = (e) => synth.handleWorkerMessage(e.data);
	* ```
	* but this can also be used to intercept return messages if needed.
	* @param events The events received from the Worker.
	*/
	handleWorkerMessage(events) {
		if (events.length > 0) this.timeOffset = events[0].currentTime - this.context.currentTime;
		this.handleMessages(events);
	}
	/**
	* Writes a DLS file directly in the worker.
	* This pauses the playback if it is playing.
	*
	* @param options Optional configuration for writing the DLS file.
	* @returns The file array buffer and its suggested name.
	*/
	async writeDLS(options = DEFAULT_DLS_WRITE_OPTIONS) {
		return this.writeFile("writeDLS", fillWithDefaults(options, DEFAULT_DLS_WRITE_OPTIONS));
	}
	/**
	* Writes an SF2/SF3 file directly in the worker.
	* This pauses the playback if it is playing.
	*
	* @param options Optional configuration for writing the SF2 file.
	* @returns The file array buffer and its suggested name.
	*/
	async writeSF2(options = DEFAULT_SF2_WRITE_OPTIONS) {
		return this.writeFile("writeSF2", fillWithDefaults(options, DEFAULT_SF2_WRITE_OPTIONS));
	}
	/**
	* Writes an embedded MIDI (RMIDI) file directly in the worker.
	* This pauses the playback if it is playing.
	*
	* @param options Optional configuration for writing the RMIDI file.
	* @returns The file array buffer.
	*/
	async writeRMIDI(options = DEFAULT_RMIDI_WRITE_OPTIONS) {
		return (await this.writeFile("writeRMIDI", fillWithDefaults(options, DEFAULT_RMIDI_WRITE_OPTIONS))).binary;
	}
	/**
	* Renders the current song in the connected sequencer to a single stereo `AudioBuffer`.
	* This pauses the playback if it is playing.
	* @param sampleRate The sample rate to use, in Hertz.
	* @param renderOptions Options for rendering the audio data.
	* @returns The complete stereo output, including the effects and convolver.
	*/
	async renderAudio(sampleRate, renderOptions = DEFAULT_WORKER_RENDER_AUDIO_OPTIONS) {
		const options = {
			...fillWithDefaults(renderOptions, DEFAULT_WORKER_RENDER_AUDIO_OPTIONS),
			separateChannels: false
		};
		return (await this.renderAudioInternal(sampleRate, options)).output;
	}
	/**
	* Renders the current song in the connected sequencer to the complete stereo output plus separate channel buffers.
	* This pauses the playback if it is playing.
	* @param sampleRate The sample rate to use, in Hertz.
	* @param renderOptions Options for rendering the audio data.
	* @returns The complete stereo output and the separate visualization channels.
	*/
	async renderAudioSplit(sampleRate, renderOptions = DEFAULT_WORKER_RENDER_AUDIO_OPTIONS) {
		const options = {
			...fillWithDefaults(renderOptions, DEFAULT_WORKER_RENDER_AUDIO_OPTIONS),
			separateChannels: true
		};
		return this.renderAudioInternal(sampleRate, options);
	}
	/**
	* Writes a file in the worker, reporting the progress.
	* @param type The message type of the file to write.
	* @param options The complete write options, including the progress function.
	*/
	async writeFile(type, options) {
		const { progressFunction, ...data } = options;
		return this.withProgress("workerSynthWriteFile", (p) => {
			progressFunction?.(p);
		}, async () => {
			const responsePromise = this.awaitCoreResponse("workerSynthWriteFile");
			this.post({
				type,
				data,
				channelNumber: -1
			});
			return await responsePromise;
		});
	}
	async renderAudioInternal(sampleRate, options) {
		return this.withProgress("renderAudio", (p) => {
			options.progressCallback?.(p, 0);
		}, async () => {
			const responsePromise = this.awaitCoreResponse("renderAudio");
			const strippedOptions = {
				...options,
				progressCallback: void 0
			};
			this.post({
				type: "renderAudio",
				data: {
					sampleRate,
					options: strippedOptions
				},
				channelNumber: -1
			});
			const data = await responsePromise;
			const convolverData = data.convolver;
			const visual = data.visual.map((dryPair) => makeAudioBuffer(dryPair, sampleRate));
			const output = makeAudioBuffer(data.output, sampleRate);
			if (convolverData && options.enableEffects && this.convolverNode?.buffer) mergeStereoAudioBuffer(output, await this.renderConvolverBuffer(convolverData, sampleRate));
			return {
				visual,
				output
			};
		});
	}
	/**
	* Renders the convolver buffer with the current impulse response.
	*/
	async renderConvolverBuffer(convolverData, sampleRate) {
		const convolverSource = makeAudioBuffer(convolverData, sampleRate);
		const offline = new OfflineAudioContext({
			numberOfChannels: 2,
			length: convolverData[0].length,
			sampleRate
		});
		const source = offline.createBufferSource();
		source.buffer = convolverSource;
		const convolver = offline.createConvolver();
		let impulseResponse = this.convolverNode.buffer;
		if (impulseResponse.sampleRate !== sampleRate) impulseResponse = await resampleAudioBuffer(impulseResponse, sampleRate);
		convolver.buffer = impulseResponse;
		source.connect(convolver);
		convolver.connect(offline.destination);
		source.start(0);
		return offline.startRendering();
	}
};
// src/synthesizer/worker/write_sf_worker.ts
/**
* Gets the bank to write, trimmed if requested.
* @param opts The bank write options.
* @param modifiesSamples If the samples will be modified, the bank is copied so the synthesizer's bank stays intact.
*/
function getBankToWrite(opts, modifiesSamples) {
	const sf = this.getBank(opts);
	if (!modifiesSamples && !opts.trim) return sf;
	const sfCopy = BasicSoundBank.copyFrom(sf);
	if (opts.trim) {
		const midi = this.sequencers[opts.sequencerID]?.midiData;
		if (!midi) throw new Error("Sound bank MIDI trimming is enabled but no MIDI is loaded!");
		sfCopy.trim(midi.getUsedProgramsAndKeys(sfCopy));
	}
	return sfCopy;
}
async function writeSF2Worker(opts) {
	const sf = getBankToWrite.call(this, opts, opts.compressionAction !== "keep");
	let compressionFunction;
	if (this.compressionFunction !== void 0) compressionFunction = (audioData, sampleRate) => this.compressionFunction(audioData, sampleRate, opts.compressionQuality);
	switch (opts.compressionAction) {
		case "keep":
		default: break;
		case "compress":
			if (!compressionFunction) {
				const e = /* @__PURE__ */ new Error(`Compression enabled but no compression function has been provided to WorkerSynthesizerCore.`);
				this.post({
					type: "soundBankError",
					data: e,
					currentTime: this.synthesizer.currentTime
				});
				throw e;
			}
			await sf.setSampleFormat({
				compressionFunction,
				format: "compressed",
				progressFunction: (progress) => {
					this.postProgress("workerSynthWriteFile", progress);
					return Promise.resolve();
				}
			});
			break;
		case "decompress": await sf.setSampleFormat({
			format: "pcm",
			progressFunction: (progress) => {
				this.postProgress("workerSynthWriteFile", progress);
				return Promise.resolve();
			}
		});
	}
	return {
		binary: sf.writeSF2({
			...opts,
			progressFunction: (progress) => {
				this.postProgress("workerSynthWriteFile", progress);
				return Promise.resolve();
			}
		}),
		bank: sf
	};
}
function writeDLSWorker(opts) {
	const sf = getBankToWrite.call(this, opts, false);
	return {
		binary: sf.writeDLS({
			...opts,
			progressFunction: (progress) => {
				this.postProgress("workerSynthWriteFile", progress);
				return Promise.resolve();
			}
		}),
		bank: sf
	};
}
// src/synthesizer/worker/write_rmi_worker.ts
async function writeRMIDIWorker(opts) {
	const midi = this.sequencers[opts.sequencerID]?.midiData;
	if (!midi) throw new Error("No MIDI is loaded!");
	const { binary, bank } = opts.format === "sf2" ? await writeSF2Worker.call(this, opts) : writeDLSWorker.call(this, opts);
	const mid = BasicMIDI.copyFrom(midi);
	if (opts.applySnapshot) mid.applySnapshot(this.synthesizer.getSnapshot());
	return mid.writeRMIDI(binary, {
		soundBank: bank,
		...opts
	});
}
// src/synthesizer/worker/worker_synthesizer_core.ts
const BLOCK_SIZE = 128;
/**
* Creates the views into the render data, in the order described in {@link WorkerSynthesizerCore.renderData}.
* @param renderData The render data.
*/
function createRenderViews(renderData) {
	let offset = 0;
	const next = () => {
		const view = renderData.subarray(offset, offset + BLOCK_SIZE);
		offset += BLOCK_SIZE;
		return view;
	};
	const outL = next();
	const outR = next();
	const convolverL = next();
	const convolverR = next();
	const visual = new Array();
	for (let i = 0; i < 16; i++) visual.push([next(), next()]);
	return {
		outL,
		outR,
		convolverL,
		convolverR,
		visual
	};
}
/**
* The core audio engine for the worker synthesizer.
*
* This part of the synthesizer runs in the Web Worker and contains the actual audio engine.
*
* @group Synthesizer.Worker
*/
var WorkerSynthesizerCore = class extends BasicSynthesizerCore {
	/**
	* The message port to the playback audio worklet.
	* @internal
	*/
	workletMessagePort;
	compressionFunction;
	/**
	* If the audio loop is running. It is stopped while rendering or writing files.
	*/
	audioLoopRunning = false;
	/**
	* The rendered block, encoded into a single array as follows, each part being `BLOCK_SIZE` long:
	* - OutLeft, OutRight
	* - ConvolverL, ConvolverR
	* - Dry1L, Dry1R ... Dry16L, Dry16R
	*
	* It is copied into a transferable buffer after rendering.
	*/
	renderData = /* @__PURE__ */ new Float32Array(4608);
	/**
	* The views into {@link renderData} that the synthesizer renders to.
	*/
	renderViews = createRenderViews(this.renderData);
	/**
	* Buffers returned by the playback worklet, ready to be reused.
	*/
	freeBuffers = new Array();
	/**
	* Creates a new worker synthesizer core: the synthesizer that runs in the worker.
	* Most parameters here are provided with the first message that is posted to the worker by the {@link WorkerSynthesizer}.
	*
	* > **Tip**
	* >
	* > For an example of setting up a worker synthesizer, see the [Worker Synthesizer Example](../../../docs/getting-started/worker-synth-example.md).
	*
	* @param synthCoreConfig The data from the first message sent from {@link WorkerSynthesizer}.
	* Listen for the first event and use its data to initialize this class.
	* @param workletMessagePort The first port from the first message sent from {@link WorkerSynthesizer}.
	* @param mainThreadCallback The function to post a message back to the main thread. Usually `postMessage`.
	* @param compressionFunction Optional function for compressing SF3 banks.
	*/
	constructor(synthCoreConfig, workletMessagePort, mainThreadCallback, compressionFunction) {
		super(synthCoreConfig, mainThreadCallback);
		this.workletMessagePort = workletMessagePort;
		this.workletMessagePort.onmessage = (e) => {
			if (e.data instanceof Float32Array) this.freeBuffers.push(e.data);
			this.process();
		};
		this.compressionFunction = compressionFunction;
		this.synthesizer.ready.then(() => {
			this.postReady("sf3Decoder", null);
			this.startAudioLoop();
		});
	}
	/**
	* Handles a message received from the main thread.
	*
	* > **Tip**
	* >
	* > For an example of setting up a worker synthesizer, see the [Worker Synthesizer Example](../../../docs/getting-started/worker-synth-example.md).
	*
	* @param m The message received.
	*/
	handleMessage(m) {
		if (this.destroyed) return;
		switch (m.type) {
			case "renderAudio": {
				const { sampleRate, options } = m.data;
				this.runOffline("renderAudio", () => {
					const rendered = renderAudioWorker.call(this, sampleRate, options);
					const transferable = Array.from(rendered.output, (r) => r.buffer);
					for (const d of rendered.visual) transferable.push(...d.map((c) => c.buffer));
					if (rendered.convolver) for (const c of rendered.convolver) transferable.push(c.buffer);
					this.postReady("renderAudio", rendered, transferable);
				});
				break;
			}
			case "writeRMIDI": {
				const opts = m.data;
				this.runOffline("workerSynthWriteFile", async () => {
					const data = await writeRMIDIWorker.call(this, opts);
					this.postReady("workerSynthWriteFile", {
						binary: data,
						fileName: ""
					}, [data]);
				});
				break;
			}
			case "writeSF2": {
				const opts = m.data;
				this.runOffline("workerSynthWriteFile", async () => {
					const data = await writeSF2Worker.call(this, opts);
					this.postReady("workerSynthWriteFile", {
						binary: data.binary,
						fileName: data.bank.soundBankInfo.name + (data.bank.soundBankInfo.version.major === 3 ? ".sf3" : ".sf2")
					}, [data.binary]);
				});
				break;
			}
			case "writeDLS": {
				const opts = m.data;
				this.runOffline("workerSynthWriteFile", () => {
					const data = writeDLSWorker.call(this, opts);
					this.postReady("workerSynthWriteFile", {
						binary: data.binary,
						fileName: data.bank.soundBankInfo.name + ".dls"
					}, [data.binary]);
				});
				break;
			}
			default: super.handleMessage(m);
		}
	}
	getBank(opts) {
		const embedded = this.sequencers[opts.sequencerID]?.midiData?.embeddedSoundBank;
		const sf = embedded && opts.writeEmbeddedSoundBank ? SoundBankLoader.fromArrayBuffer(embedded) : this.synthesizer.soundBankManager.soundBankList.find((b) => b.id === opts.bankID)?.soundBank;
		if (!sf) {
			const e = /* @__PURE__ */ new Error(`${opts.bankID} does not exist in the sound bank list!`);
			this.post({
				type: "soundBankError",
				data: e,
				currentTime: this.synthesizer.currentTime
			});
			throw e;
		}
		return sf;
	}
	/**
	* Runs an offline operation with the audio loop stopped.
	* The loop is always restarted, and a failure rejects the main thread promise waiting for the response.
	* @param responseType The response type the main thread is waiting for.
	* @param operation The operation, which posts the response itself on success.
	*/
	async runOffline(responseType, operation) {
		this.stopAudioLoop();
		try {
			await operation();
		} catch (error) {
			SpessaLog.warn(error);
			this.postError(responseType, error);
		} finally {
			this.startAudioLoop();
		}
	}
	stopAudioLoop() {
		this.synthesizer.stopAll(true);
		for (const seq of this.sequencers) seq.pause();
		this.audioLoopRunning = false;
	}
	startAudioLoop() {
		if (this.destroyed) return;
		this.audioLoopRunning = true;
		this.process();
	}
	destroy() {
		this.workletMessagePort.postMessage(null);
		this.stopAudioLoop();
		super.destroy();
	}
	process() {
		if (!this.audioLoopRunning) return;
		this.startBlock();
		this.renderData.fill(0);
		const { outL, outR, convolverL, convolverR, visual } = this.renderViews;
		this.synthesizer.process(outL, outR, void 0, void 0, visual);
		this.reverbCapture?.moveCapturedData(convolverL, convolverR, 0, BLOCK_SIZE);
		const data = this.freeBuffers.pop() ?? new Float32Array(this.renderData.length);
		data.set(this.renderData);
		this.workletMessagePort.postMessage(data, [data.buffer]);
		this.finishBlock();
	}
};
// src/synthesizer/worklet/worklet_processor_name.ts
/**
* The name of the bundled `AudioWorklet` processor to register.
*/
const WORKLET_PROCESSOR_NAME = "spessasynth-worklet-processor";
// src/synthesizer/worklet/worklet_synthesizer.ts
/**
* This synthesizer uses an AudioWorklet node to provide real-time playback.
*
* > **Tip**
* >
* > A comparison of both synthesizers [can be found here](../../../docs/extra/comparing-synthesizers.md).
*
* > **Note**
* >
* > An example demonstrating the capabilities of this synthesizer [can be found here](../../../docs/getting-started/advanced-example.md).
*
* @group Synthesizer.Worklet
*/
var WorkletSynthesizer = class extends BasicSynthesizer {
	/**
	* Creates a new instance of an AudioWorklet-based synthesizer.
	*
	* > **Warning**
	* >
	* > Avoid using multiple synthesizer instances.
	* > The {@link SoundBankManager} and one instance should be sufficient, especially when using more than 16 channels.
	* > See [this comment for more info](https://github.com/spessasus/SpessaSynth/issues/74#issuecomment-2452600985).
	*
	* @param context The audio context.
	* @param config Optional configuration for the synthesizer.
	*/
	constructor(context, config = DEFAULT_SYNTH_CONFIG) {
		super(context, WORKLET_PROCESSOR_NAME, fillWithDefaults(config, DEFAULT_SYNTH_CONFIG));
	}
	/**
	* Starts an offline audio render.
	*
	* > **Warning**
	* >
	* > Call this method immediately after you've set up the synthesizer.
	* > Do NOT call any other methods after initializing before this one.
	* > Chromium seems to ignore worklet messages for `OfflineAudioContext`.
	*
	* @param config The configuration to use for rendering.
	*/
	async startOfflineRender(config) {
		if (config.snapshot?.convolverImpulseResponse) {
			if (!this.convolverNode) throw new Error("Snapshot has impulse response provided but convolverMode is disabled!");
			this.convolverNode.buffer = config.snapshot.convolverImpulseResponse;
		}
		const configToSend = {
			...config,
			snapshot: this.stripConvolverFromSnapshot(config.snapshot)
		};
		const responsePromise = this.awaitCoreResponse("startOfflineRender");
		this.post({
			type: "startOfflineRender",
			data: configToSend,
			channelNumber: -1
		}, config.soundBankList.map((b) => b.soundBankBuffer));
		await responsePromise;
	}
	/**
	* Returns a `ChannelMergerNode` with all 16 channel outputs merged into a single one, with multiple channels, layered as follows:
	*
	* - Channel1L, Channel1R
	* - ...
	* - Channel16L, Channel16R
	*
	* This is intended for offline audio rendering via `OfflineAudioContext` to allow extraction of separate channels.
	*
	* > **Note**
	* >
	* > The main output is not included in the merged output,
	* > as Web Audio caps the number of channels at 32 (16 stereo pairs).
	*
	* > **Warning**
	* >
	* > Make sure the `OfflineAudioContext` is created with 32 channels, otherwise the channels will be downmixed.
	*
	* > **Tip**
	* >
	* > An example demonstrating this [can be found here](../../../docs/getting-started/render-split-example.md).
	*/
	getMergedOutput() {
		const merger = this.context.createChannelMerger(32);
		for (let i = 0; i < 16; i++) {
			const splitter = this.context.createChannelSplitter(2);
			const output = i + 2;
			this.worklet.connect(splitter, output);
			splitter.connect(merger, 0, i * 2);
			splitter.connect(merger, 1, i * 2 + 1);
		}
		return merger;
	}
	stripConvolverFromSnapshot(snapshot) {
		if (!snapshot) return void 0;
		const snapshotCopy = { ...snapshot };
		delete snapshotCopy.convolverImpulseResponse;
		return snapshotCopy;
	}
};
// src/utils/buffer_to_wav.ts
/**
* Converts an `AudioBuffer` into a fully valid WAVE (.wav) file.
*
* > **Important**
* >
* > The metadata uses the `INFO` chunk to write the information. It is encoded with `utf-8`.
*
* @param audioBuffer The buffer to write. Multiple channels are allowed.
* @param options Additional options for writing the file.
* @returns The binary file.
*
* @group Writing WAV
*/
function audioBufferToWav(audioBuffer, options) {
	const channels = [];
	const channelOffset = options?.channelOffset ?? 0;
	const channelCount = options?.channelCount ?? audioBuffer.numberOfChannels;
	for (let i = channelOffset; i < audioBuffer.numberOfChannels; i++) {
		channels.push(audioBuffer.getChannelData(i));
		if (channels.length >= channelCount) break;
	}
	return new Blob([audioToWav(channels, audioBuffer.sampleRate, options)], { type: "audio/wav" });
}
export { BasicSynthesizer, BasicSynthesizerCore, DEFAULT_SEQUENCER_OPTIONS, DEFAULT_SYNTH_CONFIG, LibMIDIChannel, LibMIDIInput, LibMIDIOutput, LibMIDIPort, MIDIData, MIDIDataTrack, MIDIDeviceHandler, SeqEventHandler, Sequencer, SoundBankManager, SynthEventHandler, WebMIDILinkHandler, WorkerSynthesizer, WorkerSynthesizerCore, WorkletSynthesizer, audioBufferToWav };

