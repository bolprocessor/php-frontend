Bol Processor: real-time OSC
============================

With the output format "Real-time OSC", the Bol Processor sends each note as an OSC message
(Open Sound Control) over UDP to a program such as Max, Pd, Max for Live, TouchDesigner...
which plays it. Messages are sent at the date of each event (not in advance), so the receiving
program acts on them as they arrive.

The destination (host and port, 127.0.0.1 and 7400 by default) is set on the project page,
below the output format. The console option is: --osc port[@host]

Messages
--------
/bp/start
    A performance begins.

/bp/note  id instrument name key freq duration velocity channel  [name value]...
    A note begins.
    id          (int)    number of the note, also sent by /bp/off
    instrument  (int)    instrument number (_ins())
    name        (string) instrument name in the '-sc' or '-cs' file of the project, or "bp<n>"
    key         (int)    MIDI key (0-127)
    freq        (float)  pitch in Hz, including the tuning of scales (_scale())
    duration    (float)  seconds; 0 for out-time notes such as <<C4>>, used as signals
    velocity    (float)  MIDI velocity (0-127)
    channel     (int)    MIDI channel (1-16)
    then pairs of a name and a value (float), depending on the instrument:
        vol, vol2       beginning and end volume (_volume(), 0-127)
        vel             attack velocity field (_vel())
        pan, pan2       beginning and end panoramic (_pan(), 0-127, 64 = centre)
        mod, mod2       beginning and end modulation (_mod(), 0-16383)
        press, press2   beginning and end pressure
        bend, bend2     beginning and end pitchbend (cents)
        NAME, NAME2     additional parameters of the instrument (e.g. _value(splash,250))

/bp/off  id instrument name key channel
    The note 'id' ends (not sent for notes of duration 0).

/bp/stop
    The performance is stopped or paused, or PANIC: silence all notes.

/bp/end
    The performance is over.

Examples
--------
BP_OSC_receiver.maxpat (Max) and BP_OSC_receiver.pd (Pd) print the messages and play each
note with a sine wave, one note at a time. For polyphony, send /bp/note to the voices of a
[poly~] (Max) or [clone] (Pd), using 'id' to find the voice of a /bp/off.

In Max: [udpreceive 7400] -> [route /bp/note /bp/off /bp/stop ...]
In Pd:  [netreceive -u -b 7400] -> [oscparse] -> [list trim] -> [route bp] -> [route note off stop ...]

Ableton Live
------------
Live plays MIDI, so the simplest connection is real-time MIDI (no OSC):
  1. On MacOS, open Audio MIDI Setup > Window > Show MIDI Studio > IAC Driver, check "Device is online"
     and use a bus that no other program listens to (e.g. "Bus 2").
  2. In the Bol Processor, choose "Real-time MIDI" and send to this IAC bus (MIDI output of the project page).
  3. In Live, Settings > Link, Tempo & MIDI > MIDI Ports: set "Track" to On for the input of this bus.
  4. Create a MIDI track: MIDI From = the IAC bus, Monitor = In, then load an instrument.
     For several instruments, give each track an input channel (Ch. 1, Ch. 2...) and use _chan() in BP.
  5. Arm the track and record: the production becomes a clip.
  Tunings: Live 12 loads SCALA files (.scl) as tuning systems. Use "Export to SCALA" on a tonality page.
  On Windows, a virtual MIDI cable such as loopMIDI replaces the IAC Driver.

With real-time OSC, Live needs Max for Live (included in Live Suite, or as an add-on of Live Standard).
BP_OSC_to_Live.amxd is a MIDI effect that plays the messages above on the instrument of its track:
  1. Drag BP_OSC_to_Live.amxd from the Finder to a MIDI track, before its instrument.
  2. Choose "Real-time OSC" in the Bol Processor, host 127.0.0.1 and port 7400 (or the port of the device).
  3. Controls of the device:
       Receive OSC   the device listens to the UDP port. With several devices, keep it on in ONE device only:
                     it shares the messages with the others (otherwise notes may be doubled).
       Port          UDP port (7400 by default).
       Channel       plays only the notes of this MIDI channel (_chan()), 0 = all.
       Instrument    plays only the notes of this instrument (_ins()), 0 = all.
  4. For several instruments, put one device on each track and set its Channel or Instrument.
  The device plays the MIDI key and velocity of each note (not 'freq': for microtonal scales, load the
  SCALA export in Live's tuning systems). It sends vol as CC 7, pan as CC 10 and mod as CC 1 (mod/128)
  at the beginning of each note. Notes of duration 0 are played as a note on followed by a note off.
  /bp/stop turns off all keys. MIDI from Live passes through the device.
  To control other parameters, open the device in the Max editor: [zl iter 2] -> [route vol pan mod] gives
  the named values of each note, which can be sent, for example, to [live.remote~] or to Live's API.
  This device has not been tested yet.
