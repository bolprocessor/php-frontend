; Orchestra for the Csound resource file "-cs.tryCsound" and the data "-da.tryCsound"
; Bol Processor BP3, 2026-11-15
;
; Its instruments are adapted from those of "0-default.orc" (and "bp_param" from "Beethoven.orc").
; Each instrument reads its arguments in the order set by "-cs.tryCsound":
;
;        1    The_default      synth brass (instr 3 of 0-default.orc)
;        2    Flute            sine-based flute with breath noise and vibrato
;        3    Harpsichord      two plucked strings, with modulation (vibrato) and panoramic
;        4    Splashmachine    bowed string, cello-like (instr 4 of 0-default.orc)
;
; Effect:
;        GlobalReverb    reverb and peak limiter (outputs to file/speakers)
;
; Arguments common to all instruments:
;        p2    start time
;        p3    duration
;        p4    pitch, in octave point pitch-class format (instruments 1 and 4)
;              or in cps (instruments 2 and 3). Values less than 15 are read as pitch-class.
;
; Volume is in the MIDI range 0-127. Pitchbend is in cents, except for "Harpsichord" (see below).
; When a table number is positive, the table is read over the duration of the note
; instead of the beginning & end values.
;
; Wave tables have fixed numbers above those of BP3's scores (f101, f102, ...),
; and f1, f2 of the score (from "-cs.tryCsound") are not used.
;
; This file looks best with a tab size = 4 spaces.

sr = 44100
kr = 4410
ksmps = 10
nchnls = 2
0dbfs = 1.0

; Maximum amplitude of individual notes (adjust if samples overflow)
giMaxNoteAmp = 2.0/3.0

; -- WAVE TABLES --

giSine    ftgen  99999, 0, 32769, 10, 1                        ; sine wave
giFlute   ftgen  99998, 0, 32769, 10, 1, 0.12, 0.05, 0.02     ; flute: weak upper harmonics

; -- EFFECTS BUS (global audio variables) --

gaReverbL   init    0
gaReverbR   init    0

; -- CREATE EFFECT (held for the whole performance) --

schedule "GlobalReverb", 0, -1, 0.7, 16000, 0.3

; -- USER DEFINED OPCODES --

; Converts a pitch parameter to cycles per second (cps).
; Parameters less than 15.0 are read as octave.pitchclass, others as cps.
opcode bp_pitch, i, i
ipitchparm    xin
if (ipitchparm < 15.0) then
icps = cpspch(ipitchparm)
else
icps = ipitchparm
endif
xout    icps
endop

; Returns the value of a parameter: the line from istart to iend over idur,
; or the table itable read over idur if itable is positive.
opcode bp_param, k, iiii
idur, istart, iend, itable  xin
kval line istart, idur, iend
if (itable > 0) then
ilen = ftlen(itable)
kndx line 0, idur, ilen
kval tablei kndx, itable
endif
xout    kval
endop

; Takes the default BP parameters p3 thru p10 (volume p5-p7, pitchbend in cents p8-p10)
; and returns k-rate signals for the pitch (cps) and the volume (range 0-1).
opcode bp_control, kk, iiiiiiii
idur, ip4, ip5, ip6, ip7, ip8, ip9, ip10  xin
ik1    = 1.0 / 127.0
ik2    = log(2.) / 1200.
icps  bp_pitch  ip4
kvol    bp_param  idur, ip5, ip6, ip7
kcents  bp_param  idur, ip8, ip9, ip10
kpitch = icps * exp(kcents * ik2)
xout    kpitch, kvol * ik1
endop

; -- INSTRUMENTS --

; "The_default": synth brass (instr 3 of 0-default.orc)
;        p4        pitch (octave point pitch-class)
;        p5, p6    beginning & end volumes, p7 volume table (or 0)
;        p8, p9    beginning & end pitchbend (cents), p10 pitchbend table (or 0)
instr 1
idur     = p3
iStereo = 0.7                                                ; stereo separation (0 to 1)
iDetune = 0.25                                                ; detune amount in Hz

iAttTime    = 0.1                                            ; envelope attack time
iDecTime    = 0.15                                            ; envelope decay time
iRelTime    = 0.05                                            ; envelope release time
iSusLevel    = 0.7

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

kaenv    xadsr    iAttTime, iDecTime, iSusLevel, iRelTime        ; amplitude envelope
kdclick    linseg    1.0, idur - 0.05, 1.0, 0.05, 0                ; declick envelope
kamp     =        kvol * kaenv * kdclick * giMaxNoteAmp

; a pair of detuned sawtooth oscillators
a1        vco2    kamp, kpitch + iDetune, 0                    ; 0 = sawtooth wave
a2        vco2    kamp, kpitch - iDetune, 0
a1        butterlp  a1, 5000
a2        butterlp  a2, 5000

imain     =        0.5 * (iStereo + 1.0)
icross    =        1.0 - imain
gaReverbL = gaReverbL + imain*a1 + icross*a2
gaReverbR = gaReverbR + imain*a2 + icross*a1
endin

; "Flute"
;        p4        pitch (cps)
;        p5        volume (0-127)
;        p6, p7    beginning & end values of "blurb", p8 "blurb" table (or 0)
;    "blurb" is a pitch deviation in cents, added to the pitch (e.g. 100 = one semitone up),
;    like the "slide" of new-vina.orc.
instr 2
idur     = p3
ik2      = log(2.) / 1200.
icps     bp_pitch  p4
iamp     = p5 / 127.0

kblurb   bp_param  idur, p6, p7, p8

; vibrato fading in after the attack, rate slightly unsteady
kvibenv  linseg  0, 0.25 * idur, 1
kvibrnd  randi   0.3, 2
kvib     oscili  12 * kvibenv, 5 + kvibrnd, giSine            ; +/- 12 cents
kfreq    =       icps * exp((kblurb + kvib) * ik2)

; breathy attack, sustain, short release
iatt     =       (idur * 0.3 < 0.08 ? idur * 0.3 : 0.08)
irel     =       (idur * 0.3 < 0.12 ? idur * 0.3 : 0.12)
kenv     linseg  0, iatt, 1, idur - iatt - irel, 0.9, irel, 0
kamp     =       iamp * kenv * giMaxNoteAmp

atone    oscili  1, kfreq, giFlute
anoise   rand    1
anoise   butterbp anoise, kfreq * 2, kfreq                     ; breath noise around the 2nd harmonic
asig     =       (atone + 0.08 * anoise) * kamp

gaReverbL = gaReverbL + 0.6 * asig
gaReverbR = gaReverbR + 0.8 * asig
endin

; "Harpsichord": two plucked strings (two choirs), slightly detuned
;        p4          pitch (cps)
;        p5, p6      beginning & end pitchbend, MIDI range 0-16383 (8192 = no bend, +/- 200 cents)
;        p7, p8      beginning & end volumes (0-127)
;        p9, p10     beginning & end modulation (0-4000): vibrato depth = modulation / 100 cents
;        p11, p12    beginning & end panoramic (-90 = left, 0 = centre, 90 = right), p13 panoramic table (or 0)
instr 3
idur     = p3
ik2      = log(2.) / 1200.
icps     bp_pitch  p4

kbend    line    p5, idur, p6
kcents   =       (kbend - 8192) * 200 / 8192
kvol     line    p7, idur, p8
kmod     line    p9, idur, p10
kvib     oscili  kmod / 100, 5.5, giSine
kpitch   =       icps * exp((kcents + kvib) * ik2)

kdclick  linseg  1.0, idur - 0.03, 1.0, 0.03, 0                ; declick envelope
kamp     =       kvol / 127.0 * kdclick * giMaxNoteAmp

a1       pluck   kamp, kpitch, icps, 0, 1
a2       pluck   kamp, kpitch * 1.0015, icps, 0, 1
asig     =       0.5 * (a1 + a2)
asig     butterhp asig, 60

; panoramic: missing arguments are set to 0 by Csound, which means centre
kpan     bp_param  idur, p11, p12, p13
kpan     limit   kpan, -90, 90
kpan     =       (kpan + 90) / 180 * $M_PI_2
gaReverbL = gaReverbL + asig * cos(kpan)
gaReverbR = gaReverbR + asig * sin(kpan)
endin

; "Splashmachine": bowed string, cello-like (instr 4 of 0-default.orc)
;        p4          pitch (octave point pitch-class)
;        p5, p6      beginning & end volumes, p7 volume table (or 0)
;        p8, p9      beginning & end pitchbend (cents), p10 pitchbend table (or 0)
;        p11, p12    beginning & end values of "splash", p13 "splash" table (or 0)
;    "splash" sets the depth of the vibrato: |splash| / 10 cents
;    (e.g. 120 gives +/- 12 cents, 0 gives no vibrato).
instr 4
idur     = p3
ik2      = log(2.) / 1200.

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10
ksplash  bp_param  idur, p11, p12, p13

kvibdepth =      abs(ksplash) * 0.1                        ; depth in cents
kvibenv  linseg  0, 0.3 * idur, 1
kvibrnd  randi   0.4, 3
kvib     oscili  kvibdepth * kvibenv, 5.5 + kvibrnd, giSine
kfreq    =       kpitch * exp(kvib * ik2)

iatt     =       (idur * 0.3 < 0.15 ? idur * 0.3 : 0.15)
irel     =       (idur * 0.3 < 0.2 ? idur * 0.3 : 0.2)
kenv     linseg  0, iatt, 1, idur - iatt - irel, 0.85, irel, 0
kamp     =       kvol * kenv * giMaxNoteAmp

asaw     vco2    1, kfreq, 0
abody    butterlp asaw, 3000
af1      butterbp abody, 280, 120                          ; wood resonance
af2      butterbp abody, 1000, 400
af3      butterbp abody, 2500, 800
anoise   rand    0.02                                      ; bow noise
anoise   butterbp anoise, 3000, 2000
asig     =       0.5 * abody + 1.5 * af1 + 0.8 * af2 + 0.4 * af3
asig     balance asig, abody
asig     =       (asig + anoise) * kamp

gaReverbL = gaReverbL + 0.8 * asig
gaReverbR = gaReverbR + 0.6 * asig
endin

; -- EFFECT INSTRUMENT --

; Global reverb and peak limiter (as in 0-default.orc)
;    p4    feedback level (range 0-1)
;    p5    filter cutoff frequency
;    p6    wet/dry mix (range 0-1)
instr GlobalReverb
ifeedbk = p4
icutoff = p5
iwet = p6
idry = 1.0 - iwet

ainl    =        gaReverbL
ainr    =        gaReverbR
gaReverbL =      0
gaReverbR =      0

arevl, arevr  reverbsc  ainl, ainr, ifeedbk, icutoff

aoutl    dcblock2    idry*ainl + iwet*arevl
aoutr    dcblock2    idry*ainr + iwet*arevr

; signals below -6 dBFS pass unchanged, louder peaks are squeezed under 0 dBFS
adetect  max        abs(aoutl), abs(aoutr)
aoutl    compress2  aoutl, adetect, -90, -6, -3, 1000, 0.0002, 0.2, 0.05
aoutr    compress2  aoutr, adetect, -90, -6, -3, 1000, 0.0002, 0.2, 0.05
aoutl    limit      aoutl, -0.99, 0.99
aoutr    limit      aoutr, -0.99, 0.99

out         aoutl, aoutr
endin
