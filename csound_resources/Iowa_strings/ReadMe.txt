Iowa_strings: violin, viola and cello samples for Csound
=========================================================

Used by "Beethoven.orc" (Csound resource "-cs.Beethoven-fugue-b-flat-major").
The orchestra finds these files relative to its own folder, so this folder must
stay next to it in "csound_resources".

Source
------
University of Iowa Electronic Music Studios, Musical Instrument Samples (MIS),
violin, viola and cello, arco, 2012 series, mono, 16 bit / 44.1 kHz:
    https://theremin.music.uiowa.edu/MIS.html
The site states that the recordings "may be downloaded and used for any
projects, without restrictions".

Content
-------
violin/   G3 to E7  (MIDI keys 55 to 100)
viola/    C3 to A6  (MIDI keys 48 to 93)
cello/    C2 to A5  (MIDI keys 36 to 81)
Each semitone at three dynamics: pp, mf, ff. File names give the instrument,
dynamic, MIDI key and string, e.g. "cello_mf_057_A.flac" = A3 on the A string.
A few keys have no recording of their own at some dynamic; the orchestra then
uses the nearest recording (map at the end of Beethoven.orc).

The recordings are played without vibrato; the orchestra adds it.

Processing (2026-11-12)
-----------------------
The original files contain chromatic scales, one file per string and dynamic.
They were processed as follows:
- The viola files labelled 44.1 kHz actually contain 96 kHz audio (pitches were
  13.5 semitones too low). They were read at 96 kHz and resampled to 44.1 kHz.
- Infrasonic rumble, often stronger than the music, was removed with a 4th-order
  high-pass filter at 100 Hz (violin), 70 Hz (viola), 45 Hz (cello).
- The scales were split into notes. The pitch of each note was measured and its
  octave checked against its neighbours and against its harmonics, since some
  notes were mislabelled or faulty; faulty notes were discarded.
- For each key, the note played on the highest string that reaches it in a low
  position was preferred.
- Each note was cut at its onset, kept for at most 3 seconds, and normalised to
  an RMS level of -20 dBFS on its steady part (lowered when the peak would
  exceed -0.5 dBFS; the orchestra restores the level with giSmpGain).
- For each note the orchestra map stores its measured pitch (the players deviate
  from equal temperament by up to 50 cents, so each note is tuned individually),
  its attack time, and loop points: a steady stretch whose crossfade (in
  flooper2) is phase-aligned, so that notes longer than the recording are
  sustained without pulsing.
