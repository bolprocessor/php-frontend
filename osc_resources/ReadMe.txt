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
    velocity    (float)  MIDI velocity (0-127); for the lines of a sound-object's Csound score, the velocity of the object (_vel())
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

/bp/env  id instrument name parameter  time value  time value...
    Sent after the /bp/note of note 'id', at the same date, for each parameter following a curve during the note
    (e.g. values given by _value() or _volume() inside a prolonged note): its values at times in seconds from the start
    of the note, from 0 to the duration of the note. 'parameter' is the name used in /bp/note (vol, bend, press, mod, pan,
    or the name of an additional parameter). A parameter going straight from its start value to its end value gets no /bp/env.

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

Robot puppet
------------
BP_robot_puppet.py is a small Python 3 program (standard library only) that draws a puppet in a window. It listens to the
OSC messages of the Bol Processor and moves the puppet's joints. The Bol Processor itself needs nothing more: it sends OSC
whether or not a program listens.
  1. Get a suitable Python (once). The window needs Python's tkinter with Tk 8.6 or later.
     - MacOS: the 'python3' of Apple's Command Line Tools (/usr/bin/python3) has Tk 8.5, whose window hangs, so the program
       refuses it. Install Python from https://www.python.org/downloads/ (the installer includes Tk 8.6). It is then
       /usr/local/bin/python3, but typing 'python3' may still run Apple's: give the full path, as below.
       Homebrew's Python needs 'brew install python-tk'.
     - Windows: the installer of https://www.python.org includes tkinter.
     - Linux: sudo apt install python3-tk
     Without a suitable Python, run the program with --no-window: it prints the angles instead of drawing the puppet.
  2. Start the puppet in a Terminal (Command Prompt or PowerShell on Windows), from the osc_resources folder,
     and leave it running. The paths are those of a standard installation:
       MacOS with MAMP:    cd /Applications/MAMP/htdocs/bolprocessor/osc_resources
                           /usr/local/bin/python3 BP_robot_puppet.py
       MacOS with XAMPP:   cd /Applications/XAMPP/xamppfiles/htdocs/bolprocessor/osc_resources
                           /usr/local/bin/python3 BP_robot_puppet.py
       Windows with MAMP:  cd C:\MAMP\htdocs\bolprocessor\osc_resources
                           py BP_robot_puppet.py
       Windows with XAMPP: cd C:\xampp\htdocs\bolprocessor\osc_resources
                           py BP_robot_puppet.py
       Linux with XAMPP:   cd /opt/lampp/htdocs/bolprocessor/osc_resources
                           python3 BP_robot_puppet.py
     (add --help for the options). It waits for messages on port 7400.
  3. In the Bol Processor, open one of the two dances of the ctests folder, choose the output "Real-time OSC"
     (host 127.0.0.1, port 7400) and click PRODUCE ITEM(S). The puppet dances.
       -gr.robot_dance:   movements written as notes, with _cont() and _value(); four figures chosen at random.
       -gr.robot_objects: gestures as time-objects (sound-objects) of -so.robot_objects, named in -al.robot_objects:
                          a clap whose impact falls on the beat at any tempo, a cyclic wave, a nod with limited speed.
                          Each prototype contains a Csound score whose lines are movements of the joints,
                          e.g. "i5 0 0.5 261.626 0 150 0" raises the right arm from 0 to 150 degrees in 0.5 s.
Each joint is an instrument of -cs.Robot (torso, head, leftarm, leftforearm, rightarm, rightforearm), whose angle in degrees
is the parameter named like it: a note of 'leftarm' moves the left arm from the value of _value(leftarm,...) at its beginning
to its value at its end, following /bp/env if any. See https://bolprocessor.org/robot-puppet/
and https://bolprocessor.org/usage-in-robotics/

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
