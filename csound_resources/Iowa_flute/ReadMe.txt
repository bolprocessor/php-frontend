Iowa_flute: flute samples for Csound
====================================

Used by instrument 6 of "0-default.orc" (_ins(6) or _ins(flute) with "-cs.0-default").
The orchestra finds these files relative to its own folder, so this folder must
stay next to it in "csound_resources".

Source
------
University of Iowa Electronic Music Studios, Musical Instrument Samples (MIS),
flute without vibrato ("Flute.nonvib"), pp, mf and ff, mono, 16 bit / 44.1 kHz:
    https://theremin.music.uiowa.edu/MISflute.html
The site states that the recordings "may be downloaded and used for any
projects, without restrictions".

Content
-------
pp    B3 to Bb6 (MIDI keys 59 to 94), without B5 (83)
mf    B3 to C7  (MIDI keys 59 to 96)
ff    B3 to Db7 (MIDI keys 59 to 97)
File names give the dynamic and MIDI key, e.g. "flute_mf_069.flac" = A4, mf.
Keys without a recording at some dynamic use the nearest recording of the same
dynamic (map at the end of 0-default.orc).

The recordings have no vibrato: the orchestra adds it, with a depth set by the
modulation parameter (_mod).

Processing (2026-11-18)
-----------------------
The original files contain chromatic scales, one file per register and dynamic.
They were processed as follows:
- Infrasonic and low-frequency noise was removed with a high-pass filter at
  100 Hz (the lowest note, B3, is at 247 Hz).
- The scales were split into notes at the silences between them. The number
  of notes and the pitch of each one were checked against the expected scale.
- Each note starts 5 ms before its onset (the first level within 30 dB of its
  steady level).
- The slow level changes of the sustained part (over 100 ms) were flattened,
  keeping the faster fluctuations of the breath, so that loops do not pulse.
- Each note was normalised to an RMS level of -20 dBFS on its steady part.
- For each note the orchestra map stores its measured pitch (giFluPitch; the
  player deviates from equal temperament by up to 45 cents, so each note is
  tuned individually), its attack time, and loop points. flooper2 crossfades
  the 50 ms before the loop end with the 50 ms after the loop start, so these
  two stretches were chosen to match (correlation of the harmonic content,
  below 6 times the fundamental), and notes longer than the recording are
  sustained without pulsing.
