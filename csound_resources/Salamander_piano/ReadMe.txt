Salamander_piano: grand piano samples for Csound
================================================

Used by instrument 1 of "0-default.orc", the default instrument: notes without _ins(),
or with _ins(1) or _ins(piano) with "-cs.0-default".
The orchestra finds these files relative to its own folder, so this folder must
stay next to it in "csound_resources".

Source
------
Salamander Grand Piano V3 (version V3+20161209, 44.1 kHz / 16 bit),
by Alexander Holm: a Yamaha C5 recorded with two AKG C414 in AB position
about 12 cm above the strings, 16 velocity layers, sampled in minor thirds.
    https://freepats.zenvoid.org/Piano/acoustic-grand-piano.html
Licence: Creative Commons Attribution 3.0 (CC-BY 3.0)
    https://creativecommons.org/licenses/by/3.0/
The files of this folder are derived from the original recordings (see below).

Content
-------
Every minor third from A0 to C8 (MIDI keys 21, 24, ... 108), at 6 of the 16
velocity layers: v2, v4, v7, v10, v13 and v16, stereo, 44.1 kHz, 16 bit, FLAC.
File names give the layer and MIDI key, e.g. "piano_v07_060.flac" = C4, layer v7.
The orchestra plays each key from the nearest recording (at most one semitone away).

Processing (2026-11-18)
-----------------------
- Each recording starts 2 ms before its onset.
- It ends when its level has fallen 60 dB below its peak, or after 12 seconds,
  with a fade-out of 0.3 seconds.
- The levels are not changed: the layers keep the natural dynamics of the piano.
  The orchestra stores the mean loudness of each layer (giPnoLoud), so that the
  level follows the velocity smoothly from one layer to the next.
- The pitch of each recording was measured (the amplitude-weighted centre of
  the strongest peak near the fundamental, or near the 2nd or 3rd partial in
  the bass). The piano has a stretched tuning (bass up to 24 cents flat, treble
  up to 40 cents sharp, C8 nearly a semitone sharp). The orchestra map stores
  the measured pitches (giPnoPitch), so that each note is played at the pitch
  requested by the score, as needed by the tunings of the Bol Processor.
