; New Csound instruments for BP3's default Csound score output.
; They follow the specifications handled by BP3 version 3.0.0 and above.
;
; This version uses only opcodes built into the Csound core.
; Effects routing is done with global audio variables instead of the
; signalflowgraph plugin (connect / alwayson / inleta / outleta), so it
; runs on MacOS, Windows and Linux even if optional plugins fail to load.

; Instruments:
;        1    Grand piano (outputs to GlobalReverb)
;        2    Plucked string (outputs to StereoChorus)
;        3    Synth brass (outputs to GlobalReverb)
;        4    Bowed string, cello-like, with vibrato controlled by the "splash"
;             parameter of instrument "Splashmachine" (outputs to GlobalReverb)
;        5    Plucked string (outputs to StereoDelay)
;        6    Flute (outputs to GlobalReverb)
;        10    Sine wave (outputs to GlobalReverb)
;
; Effects:
;        StereoDelay (outputs to GlobalReverb)
;        StereoChorus (outputs to GlobalReverb)
;        GlobalReverb (outputs to file/speakers)

; Default instrument parameters used by BP3:
;        p2    start time
;        p3    duration
;        p4    pitch in either octave point pitch-class or cps format
;
;    Volume (range 0-127) is supplied by performance control _volume() as
;        p5, p6    beginning & end volumes
;        p7        a table number containing the volume function (or 0)
;    When p7 is positive, the table is used instead of the beginning & end values
;
;    Pitchbend is supplied in cents as
;        p8,p9    beginning & end pitchbend values
;        p10        a table number containing the pitchbend function (or 0)
;    When p10 is positive, the table is used instead of the beginning & end values

; Instruments 1 (piano) and 6 (flute) take further parameters, supplied by the
; Csound resource file "-cs.0-default" (attached by the interface when a project
; names no '-cs' file), as in "Beethoven.orc":
;        p11       attack velocity (_vel(), range 1-127)
;        p12, p13  beginning & end modulation (_mod(), range 0-16383), used by the flute
;        p14       a table number containing the modulation function (or 0)
;        p15, p16  beginning & end panoramic (_pan(), range 0-127: 0 = left, 64 = centre, 127 = right)
;        p17       a table number containing the panoramic function (or 0)
;    When a table number is positive, the table is used instead of the beginning & end values.
;    Without these parameters (fewer than 11, 14 or 17 fields), the velocity is taken from
;    the beginning volume (p5), the flute has its normal vibrato, and the position is the centre.
;
; Piano (instrument 1, the default instrument: notes without _ins() are played by it):
;    Salamander Grand Piano V3 by Alexander Holm (Yamaha C5, CC-BY 3.0), see Salamander_piano/ReadMe.txt.
;    Every minor third from A0 to C8, at 6 of its 16 velocity layers. Each recording is tuned
;    individually to the requested pitch, and played from the disk (diskin2) with its natural decay.
;    The velocity selects the nearest layer, and the level follows the velocity smoothly between layers.
;    The volume is a further gain (volume / 90)^2, so that the default volume 90 leaves the level unchanged.
;    At the end of a note, the dampers stop the sound (faster in the treble); keys from F6 have no dampers.
;    The stereo recording keeps its width at the centre; _pan() moves it like a balance control.
; Flute (instrument 6):
;    University of Iowa Musical Instrument Samples (MIS), flute without vibrato, B3 to Db7,
;    pp, mf and ff, see Iowa_flute/ReadMe.txt. Each recording is tuned individually, and looped
;    with a crossfade when the note is longer than the recording.
;    The velocity selects the recording (pp below 50, mf from 50 to 95, ff from 96), sets the level
;    in proportion (as on the piano: 6 dB softer at half velocity), and shapes the attack: crisp at
;    high velocity, soft at low velocity.
;    The volume is a further gain (volume / 90)^2.
;    Modulation (_mod) sets the depth of the vibrato (pitch and amplitude), which starts after 150 ms:
;    8192 is the normal depth, 16383 twice as deep, 1 no vibrato. Modulation 0, which BP3 sends
;    when the score contains no _mod(), also gives the normal depth.

; This file looks best with a tab size = 4 spaces.

sr = 44100
kr = 4410
ksmps = 10
nchnls = 2
0dbfs = 1.0

; Maximum amplitude of individual notes (adjust if samples overflow)
; Value 1/6 should allow at least 6 simultaneous notes at full volume.
giMaxNoteAmp = 2.0/3.0

; -- WAVE TABLES --

; A fixed table number is required: "ftgen 0" would give number 101, which BP3 uses
; for the first table of its scores (101, 102, ...), so a score would replace the sine.
giSine    ftgen  99999, 0, 32769, 10, 1    ; sine wave

; -- SETTINGS OF INSTRUMENTS 1 (PIANO) AND 6 (FLUTE) --

; Overall levels (adjust if a piano or flute is too soft or too loud)
giPnoAmp  = 1.0
giFluAmp  = 0.35

; Piano velocity layers: the velocity each one represents, and its measured loudness (dB)
giPnoVel[]  fillarray 16, 32, 56, 80, 104, 127
giPnoLoud[] fillarray -34.46, -30.09, -26.81, -24.24, -21.17, -17.12

; Flute vibrato: depth (cents), depth of the amplitude variation (0 to 1), rate (Hz)
giFluVibDepth = 14
giFluVibAmp   = 0.10
giFluVibRate  = 5.1

; Sample maps, filled at the end of this file.
; Flute recordings are loaded when first played, as tables 31000 to 31383 (see instr 6),
; or when the orchestra starts in real time (see the end of this file).
gSPnoFile[]       init 768
giPnoPitch[]      init 768
gSFluFile[]       init 384
giFluPitch[]      init 384
giFluLoopStart[]  init 384
giFluLoopEnd[]    init 384
giFluXfade[]      init 384
giFluAttack[]     init 384

; -- EFFECTS BUSES (global audio variables) --
; Instruments add their output to a bus; each effect reads its bus,
; then clears it. Effects are named instruments defined after all
; numbered instruments, so Csound runs them after the notes in each cycle.

gaDelay     init    0
gaChorusL   init    0
gaChorusR   init    0
gaReverbL   init    0
gaReverbR   init    0

; -- CREATE EFFECTS (held for the whole performance) --

schedule "StereoDelay",  0, -1, 0.05, 0.08, 0.25
schedule "StereoChorus", 0, -1, 0.4, 0.5, 0.333, 0.80
schedule "GlobalReverb", 0, -1, 0.7, 16000, 0.3

; -- USER DEFINED OPCODES --

; An opcode to convert pitch parameters to cycles per second (cps).
; Parameters less than 15.0 are interpreted as octave.pitchclass format,
; otherwise they are assumed to be frequencies already in cps.
opcode bp_pitch, i, i
ipitchparm    xin

if (ipitchparm < 15.0) then
icps = cpspch(ipitchparm)
else
icps = ipitchparm
endif

xout    icps
endop

; This opcode takes the default BP parameters p3 thru p10 and
; returns two k-rate signals for the instrument's pitch and volume.
opcode bp_control, kk, iiiiiiii
idur, ip4, ip5, ip6, ip7, ip8, ip9, ip10  xin

ik1    = 1.0 / 127.0        ; scale Midi-range volumes (0-127)
ik2    = log(2.) / 1200.    ; constant to convert cents to frequency ratios
icps  bp_pitch  ip4
ifvol = ip7                ; volume table number
ifcents = ip10            ; pitchbend table number

kvol line ip5, idur, ip6
if (ifvol <= 0) goto volumelin
ilenvol = ftlen(ifvol)
kndxvol line 0, idur, ilenvol
kvol tablei kndxvol, ifvol

volumelin:
kcents line ip8, idur, ip9
if (ifcents <= 0) goto pitchbendlin
ilencents = ftlen(ifcents)
kndxcents line 0, idur, ilencents
kcents tablei kndxcents, ifcents

pitchbendlin:
kpitch = icps * exp(kcents * ik2)

xout    kpitch, kvol * ik1
endop

; A parameter given as beginning & end values, or as a table if its number is positive
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

; Stereo position (0 = left, 1 = right) from the panoramic parameters (range 0-127):
; beginning & end values, or a table if its number is positive
opcode bp_pan, k, iiii
idur, ipan1, ipan2, ipantab  xin
kpan   bp_param  idur, ipan1 / 127, ipan2 / 127, ipantab
if (ipantab > 0) then
kpan   = kpan / 127
endif
kpan   limit  kpan, 0, 1
xout    kpan
endop

; -- INSTRUMENTS --

; Plucked string instrument with delay
instr 5
idur     = p3
icps      bp_pitch  p4

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

kdclick    linseg    1.0, idur - 0.05, 1.0, 0.05, 0        ; declick envelope
kamp    =        kvol * kdclick * giMaxNoteAmp

a1        pluck    kamp, kpitch, icps, 0, 1

gaDelay = gaDelay + a1
endin

; Plucked string instrument with chorus
instr 2
idur     = p3
icps      bp_pitch  p4

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

kdclick    linseg    1.0, idur - 0.05, 1.0, 0.05, 0        ; declick envelope
kamp    =        kvol * kdclick * giMaxNoteAmp

a1        pluck    kamp, kpitch, icps, 0, 1

gaChorusL = gaChorusL + a1
gaChorusR = gaChorusR + a1
endin

; Synth Brass instrument
instr 3
idur     = p3
iStereo = 0.7                                                ; stereo separation (0 to 1)
iDetune = 0.25                                                ; detune amount in Hz

; BP's default score parameters don't include "attack velocity",
; but with a custom setup we could plug it in here to make the shape
; and max amplitude of the amplitude envelope dynamic.
iAttVel        = 127                                            ; Midi Attack Velocity
iAttDelta    = 1.0 - (iAttVel/127)                            ; diff btw max attack and this note's
iamp        = iAttVel/127

; parameters for the ADSR envelope controlling amplitude
iAttTime    = 0.1 + 0.2 * iAttDelta                            ; envelope attack time
iDecTime    = 1.5 * iAttTime                                ; envelope decay time
iRelTime    = 0.1 * iAttDelta                                ; envelope release time
iSusTime    = idur - (iAttTime + iDecTime)                    ; envelope sustain time
iSusLevel    = 0.7

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

kaenv    xadsr    iAttTime, iDecTime, iSusLevel, iRelTime        ; amplitude envelope
kdclick    linseg    1.0, idur - 0.05, 1.0, 0.05, 0                ; declick envelope
kamp     =        iamp * kvol * kaenv * kdclick * giMaxNoteAmp

; a pair of detuned sawtooth oscillators
a1        vco2    kamp, kpitch + iDetune, 0                    ; 0 = sawtooth wave
a2        vco2    kamp, kpitch - iDetune, 0                    ; 0 = sawtooth wave

; mix the oscillators based on stereo separation amount
imain     =        0.5 * (iStereo + 1.0)
icross    =        1.0 - imain
aleft     =        imain*a1 + icross*a2
aright     =        imain*a2 + icross*a1

gaReverbL = gaReverbL + aleft
gaReverbR = gaReverbR + aright
endin

; Bowed string instrument (cello-like), used by "Splashmachine"
;    In addition to the default parameters p2 to p10, it takes the "splash" parameter as
;        p11, p12    beginning & end values of "splash"
;        p13         a table number containing the "splash" function (or 0)
;    When p13 is positive, the table is used instead of the beginning & end values.
;    "splash" sets the depth of the vibrato: |splash| / 10 cents
;    (e.g. 120 gives +/- 12 cents, 0 gives no vibrato).
instr 4
idur     = p3
ik2      = log(2.) / 1200.                                ; cents to frequency ratio

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

; "splash" parameter, read like volume and pitchbend in bp_control
ifsplash = p13
ksplash  line    p11, idur, p12
if (ifsplash <= 0) goto splashlin
ilensplash = ftlen(ifsplash)
kndxsplash line  0, idur, ilensplash
ksplash  tablei  kndxsplash, ifsplash

splashlin:
; vibrato: depth from "splash", fading in after the attack, rate slightly unsteady
kvibdepth =      abs(ksplash) * 0.1                        ; depth in cents
kvibenv  linseg  0, 0.3 * idur, 1                         ; holds 1 after the fade-in
kvibrnd  randi   0.4, 3                                   ; +/- 0.4 Hz around 5.5 Hz
kvib     oscili  kvibdepth * kvibenv, 5.5 + kvibrnd, giSine
kfreq    =       kpitch * exp(kvib * ik2)

; bowed envelope: soft attack, sustain, short release
iatt     =       (idur * 0.3 < 0.15 ? idur * 0.3 : 0.15)
irel     =       (idur * 0.3 < 0.2 ? idur * 0.3 : 0.2)
kenv     linseg  0, iatt, 1, idur - iatt - irel, 0.85, irel, 0
kamp     =       kvol * kenv * giMaxNoteAmp

; sawtooth (bowed string) shaped by a lowpass filter and body resonances
asaw     vco2    1, kfreq, 0                               ; 0 = sawtooth wave
abody    butterlp asaw, 3000
af1      butterbp abody, 280, 120                          ; wood resonance
af2      butterbp abody, 1000, 400
af3      butterbp abody, 2500, 800
anoise   rand    0.02                                      ; bow noise
anoise   butterbp anoise, 3000, 2000
asig     =       0.5 * abody + 1.5 * af1 + 0.8 * af2 + 0.4 * af3
asig     balance asig, abody                               ; keep the level of the filtered sawtooth
asig     =       (asig + anoise) * kamp

gaReverbL = gaReverbL + 0.8 * asig
gaReverbR = gaReverbR + 0.6 * asig
endin

; Grand piano (Salamander Grand Piano), see the header of this file
instr 1
idur     = p3
if (idur < 0.01) then           ; BP3 may write notes of duration 0
turnoff
endif
ipc      = pcount()
icps     bp_pitch  p4
kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

; dynamics: the velocity selects the nearest layer, and the level is interpolated
; between the measured loudness of the layers, so that it follows the velocity smoothly
ivel     = (ipc >= 11 ? p(11) : 0)                      ; attack velocity,
ivel     = (ivel > 0 ? ivel : p5)                       ; or the beginning volume if not supplied
ivel     limit  ivel, 1, 127
if (ipc >= 11 && p(11) > 0) then
kvolgain = (kvol * 127 / 90) ^ 2
else
kvolgain = (kvol * 127 / ivel) ^ 2  ; the beginning volume is the velocity: only its changes matter
endif
ilayer   = 0
ii       = 1
while (ii < 6) do
if (abs(ivel - giPnoVel[ii]) < abs(ivel - giPnoVel[ilayer])) then
ilayer   = ii
endif
ii       += 1
od
if (ivel <= giPnoVel[0]) then
itarget  = giPnoLoud[0] + 20 * log10(ivel / giPnoVel[0])
else
ii       = 1
while (ii < 5 && ivel > giPnoVel[ii]) do
ii       += 1
od
ifrac    = (ivel - giPnoVel[ii - 1]) / (giPnoVel[ii] - giPnoVel[ii - 1])
itarget  = giPnoLoud[ii - 1] + ifrac * (giPnoLoud[ii] - giPnoLoud[ii - 1])
endif
igain    = ampdb(itarget - giPnoLoud[ilayer])

; the recording nearest to the pitch (every minor third from A0), tuned to the pitch
ikey     = round(69 + 12 * log(icps / 440) / log(2))
isk      = 21 + 3 * round((ikey - 21) / 3)
isk      limit  isk, 21, 108
idx      = ilayer * 128 + isk
irefcps  = 440 * 2 ^ ((giPnoPitch[idx] - 69) / 12)
aL, aR   diskin2  gSPnoFile[idx], kpitch / irefcps, 0, 0, 0, 8

; release: the dampers stop the sound, faster in the treble; keys from F6 have no dampers
if (ikey < 89) then
irel     = 0.45 - 0.3 * (ikey - 21) / 68
else
irel     = 1.5
endif
p3       = idur + irel
kenv     transeg  0, 0.002, 0, 1, idur - 0.002, 0, 1, irel, -4, 0
kamp     = kenv * igain * kvolgain * giPnoAmp

; stereo position: at the centre the recording keeps its width; towards one side,
; the other channel is mixed into it (like a balance control that keeps all the sound)
ipan1    = (ipc >= 17 ? p(15) : 64)                     ; the centre if not supplied
ipan2    = (ipc >= 17 ? p(16) : 64)
ipant    = (ipc >= 17 ? p(17) : 0)
kpan     bp_pan  idur, ipan1, ipan2, ipant
kgl      limit  sqrt(2) * cos(kpan * $M_PI_2), 0, 1
kgr      limit  sqrt(2) * sin(kpan * $M_PI_2), 0, 1
gaReverbL = gaReverbL + kamp * (kgl * aL + (1 - kgr) * aR)
gaReverbR = gaReverbR + kamp * (kgr * aR + (1 - kgl) * aL)
endin

; Flute (University of Iowa samples), see the header of this file
instr 6
idur     = p3
if (idur < 0.01) then           ; BP3 may write notes of duration 0
turnoff
endif
irel     = 0.09
p3       = idur + irel
ipc      = pcount()
ik2      = log(2.) / 1200.                              ; cents to frequency ratio
icps     bp_pitch  p4
kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

; dynamics: choice of the recording and level
ivel     = (ipc >= 11 ? p(11) : 0)                      ; attack velocity,
ivel     = (ivel > 0 ? ivel : p5)                       ; or the beginning volume if not supplied
ivel     limit  ivel, 1, 127
if (ipc >= 11 && p(11) > 0) then
kvolgain = (kvol * 127 / 90) ^ 2
else
kvolgain = (kvol * 127 / ivel) ^ 2  ; the beginning volume is the velocity: only its changes matter
endif
igain    = ivel / 127                                   ; as on the piano: 6 dB softer at half velocity
ilayer   = (ivel < 50 ? 0 : (ivel < 96 ? 1 : 2))

; the recording nearest to the pitch
ikey     = round(69 + 12 * log(icps / 440) / log(2))
ikey     limit  ikey, 59, 97
idx      = ilayer * 128 + ikey
ifn      ftgenonce  31000 + idx, 0, 0, 1, gSFluFile[idx], 0, 0, 1
irefcps  = 440 * 2 ^ ((giFluPitch[idx] - 69) / 12)    ; measured pitch of the recording

; attack: the velocity sets how fast the note starts (5 ms at velocity 127, about 100 ms at
; velocity 1). Slow onsets of the recordings are shortened, more for short notes and high velocities.
iallow   = (0.05 + 0.1 * (idur < 2 ? idur : 2)) * (1.5 - ivel / 127)
istart   = giFluAttack[idx] - iallow
istart   = (istart > 0 ? istart : 0)
ifadein  = 0.005 + 0.1 * (1 - ivel / 127) ^ 2
ifadein  = (ifadein < idur / 3 ? ifadein : idur / 3)

; vibrato, its depth set by the modulation
imod1    = (ipc >= 12 ? p(12) : 0)
imod2    = (ipc >= 13 ? p(13) : 0)
imodt    = (ipc >= 14 ? p(14) : 0)
kmodf    = 1
if (imod1 != 0 || imod2 != 0 || imodt > 0) then
kmod     bp_param  idur, imod1, imod2, imodt
kmodf    = kmod / 8192
endif
iphase   random  0, 1                                   ; random values differ for each note,
iseed1   random  0, 1                                   ; but are the same each time the score is played
iseed2   random  0, 1
iseed3   random  0, 1
kvibenv  linseg  0, 0.15, 0, 0.45, 1                    ; holds 1 after the rise
kvrate   = giFluVibRate + randi:k(0.3, 1.5, iseed1)
kvdep    = kvibenv * kmodf * (1 + randi:k(0.15, 1.1, iseed2))
kvib     oscili  1, kvrate, giSine, iphase
kdrift   randi   1.5, 0.6, iseed3                       ; slow intonation drift (cents)
kratio   = kpitch * exp((kvib * kvdep * giFluVibDepth + kdrift) * ik2) / irefcps
kam      = 1 + giFluVibAmp * kvib * kvdep

asig     flooper2  1, kratio, giFluLoopStart[idx], giFluLoopEnd[idx], giFluXfade[idx], ifn, istart
isus     = idur - ifadein
isus     = (isus > 0.001 ? isus : 0.001)
kenv     transeg  0, ifadein, 0, 1, isus, 0, 1, irel, -3, 0
asig     = asig * kenv * kam * igain * kvolgain * giFluAmp

ipan1    = (ipc >= 17 ? p(15) : 64)                     ; the centre if not supplied
ipan2    = (ipc >= 17 ? p(16) : 64)
ipant    = (ipc >= 17 ? p(17) : 0)
kpan     bp_pan  idur, ipan1, ipan2, ipant
gaReverbL = gaReverbL + asig * sqrt(2) * cos(kpan * $M_PI_2)
gaReverbR = gaReverbR + asig * sqrt(2) * sin(kpan * $M_PI_2)
endin

; Simple sine wave instrument
instr 10
idur = p3

kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10

kdclick     linseg  0, 0.02, 1.0, idur - 0.07, 1.0, 0.05, 0    ; declick envelope
kamp = kvol * kdclick * giMaxNoteAmp

a1    oscili    kamp, kpitch, giSine
gaReverbL = gaReverbL + a1
gaReverbR = gaReverbR + a1
endin

; -- EFFECTS INSTRUMENTS --

; Stereo delay effect (mono input)
;    p4    left delay time (in seconds)
;    p5    right delay time (in seconds)
;    p6    wet/dry mix (range 0-1)
instr StereoDelay
idelay1 = p4
idelay2 = p5
iwet1 = p6
iwet2 = p6 - 0.05
idry = 1.0 - iwet1
if (iwet2 < 0) then
iwet2 = 0
endif

ain        =        gaDelay
gaDelay    =        0

adel1    delay    ain, idelay1
adel2    delay    ain, idelay2
aleft    =        ain*idry + adel1*iwet1
aright    =        ain*idry + adel2*iwet2

gaReverbL = gaReverbL + aleft
gaReverbR = gaReverbR + aright
endin

; Dual stereo chorus effect
;    p4    chorus depth (range 0-1)
;    p5    chorus rate 1 (in Hz)
;    p6    chorus rate 2 (in Hz)
;    p7    wet/dry mix (range 0-1)
instr StereoChorus
idepth = p4
iLFOrate1 = p5
iLFOrate2 = p6
iwet = p7
idry = 1.0 - iwet

ainl    =        gaChorusL
ainr    =        gaChorusR
gaChorusL =      0
gaChorusR =      0

; delay times and max variability (in milliseconds)
ideltime1 = 37
idelvar1  = 5
ideltime2 = 23
idelvar2  = 1.75

; LFOs (low-frequency oscillators) modulate the delay times
alfo1    oscili    idepth*idelvar1, iLFOrate1, giSine
alfo2    oscili    idepth*idelvar2, iLFOrate2, giSine

; left channel delays
aldel1    vdelay    ainl, ideltime1+alfo1, ideltime1+idelvar1
aldel2    vdelay    ainl, ideltime2-alfo2, ideltime2+idelvar2

; right channel delays
ardel1    vdelay    ainr, ideltime1-alfo1, ideltime1+idelvar1
ardel2    vdelay    ainr, ideltime2+alfo2, ideltime2+idelvar2

aleft    =        ainl*idry + (aldel1+aldel2)*iwet*0.5
aright    =        ainr*idry + (ardel1+ardel2)*iwet*0.5

gaReverbL = gaReverbL + aleft
gaReverbR = gaReverbR + aright
endin

; Global reverb effect
;    p4    feedback level (range 0-1)
;     p5    filter cutoff frequency
;     p6    wet/dry mix (range 0-1)
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

; Peak limiter: signals below -6 dBFS (amplitude 0.5) pass unchanged, louder
; peaks are squeezed under 0 dBFS instead of clipping. Both channels share one
; detector so the stereo image does not move during peaks.
; The 50 ms look-ahead delays the whole output by 50 ms.
adetect  max        abs(aoutl), abs(aoutr)
aoutl    compress2  aoutl, adetect, -90, -6, -3, 1000, 0.0002, 0.2, 0.05
aoutr    compress2  aoutr, adetect, -90, -6, -3, 1000, 0.0002, 0.2, 0.05
aoutl    limit      aoutl, -0.99, 0.99    ; safety net for any residual overshoot
aoutr    limit      aoutr, -0.99, 0.99

outs        aoutl, aoutr
endin
; -- SAMPLE MAPS of instruments 1 (piano) and 6 (flute) --
; Generated from the recordings in "Salamander_piano/" and "Iowa_flute/" (see the ReadMe.txt files there)
;
; Piano: index = layer * 128 + MIDI key, layer 0 to 5 = Salamander velocity layers v2, v4, v7, v10, v13, v16,
;    keys every minor third from A0 (21) to C8 (108). giPnoPitch = measured pitch of the recording (MIDI key, fractional).
gSPnoFile[21] = "Salamander_piano/piano_v02_021.flac"
giPnoPitch[21] = 20.756
gSPnoFile[24] = "Salamander_piano/piano_v02_024.flac"
giPnoPitch[24] = 23.769
gSPnoFile[27] = "Salamander_piano/piano_v02_027.flac"
giPnoPitch[27] = 26.846
gSPnoFile[30] = "Salamander_piano/piano_v02_030.flac"
giPnoPitch[30] = 29.918
gSPnoFile[33] = "Salamander_piano/piano_v02_033.flac"
giPnoPitch[33] = 32.928
gSPnoFile[36] = "Salamander_piano/piano_v02_036.flac"
giPnoPitch[36] = 35.884
gSPnoFile[39] = "Salamander_piano/piano_v02_039.flac"
giPnoPitch[39] = 38.942
gSPnoFile[42] = "Salamander_piano/piano_v02_042.flac"
giPnoPitch[42] = 41.943
gSPnoFile[45] = "Salamander_piano/piano_v02_045.flac"
giPnoPitch[45] = 45.016
gSPnoFile[48] = "Salamander_piano/piano_v02_048.flac"
giPnoPitch[48] = 47.964
gSPnoFile[51] = "Salamander_piano/piano_v02_051.flac"
giPnoPitch[51] = 50.961
gSPnoFile[54] = "Salamander_piano/piano_v02_054.flac"
giPnoPitch[54] = 53.970
gSPnoFile[57] = "Salamander_piano/piano_v02_057.flac"
giPnoPitch[57] = 56.984
gSPnoFile[60] = "Salamander_piano/piano_v02_060.flac"
giPnoPitch[60] = 59.988
gSPnoFile[63] = "Salamander_piano/piano_v02_063.flac"
giPnoPitch[63] = 63.012
gSPnoFile[66] = "Salamander_piano/piano_v02_066.flac"
giPnoPitch[66] = 65.971
gSPnoFile[69] = "Salamander_piano/piano_v02_069.flac"
giPnoPitch[69] = 69.009
gSPnoFile[72] = "Salamander_piano/piano_v02_072.flac"
giPnoPitch[72] = 72.036
gSPnoFile[75] = "Salamander_piano/piano_v02_075.flac"
giPnoPitch[75] = 75.033
gSPnoFile[78] = "Salamander_piano/piano_v02_078.flac"
giPnoPitch[78] = 78.041
gSPnoFile[81] = "Salamander_piano/piano_v02_081.flac"
giPnoPitch[81] = 81.073
gSPnoFile[84] = "Salamander_piano/piano_v02_084.flac"
giPnoPitch[84] = 84.080
gSPnoFile[87] = "Salamander_piano/piano_v02_087.flac"
giPnoPitch[87] = 87.087
gSPnoFile[90] = "Salamander_piano/piano_v02_090.flac"
giPnoPitch[90] = 90.138
gSPnoFile[93] = "Salamander_piano/piano_v02_093.flac"
giPnoPitch[93] = 93.138
gSPnoFile[96] = "Salamander_piano/piano_v02_096.flac"
giPnoPitch[96] = 96.232
gSPnoFile[99] = "Salamander_piano/piano_v02_099.flac"
giPnoPitch[99] = 99.191
gSPnoFile[102] = "Salamander_piano/piano_v02_102.flac"
giPnoPitch[102] = 102.263
gSPnoFile[105] = "Salamander_piano/piano_v02_105.flac"
giPnoPitch[105] = 105.396
gSPnoFile[108] = "Salamander_piano/piano_v02_108.flac"
giPnoPitch[108] = 108.981
gSPnoFile[149] = "Salamander_piano/piano_v04_021.flac"
giPnoPitch[149] = 20.755
gSPnoFile[152] = "Salamander_piano/piano_v04_024.flac"
giPnoPitch[152] = 23.769
gSPnoFile[155] = "Salamander_piano/piano_v04_027.flac"
giPnoPitch[155] = 26.845
gSPnoFile[158] = "Salamander_piano/piano_v04_030.flac"
giPnoPitch[158] = 29.916
gSPnoFile[161] = "Salamander_piano/piano_v04_033.flac"
giPnoPitch[161] = 32.927
gSPnoFile[164] = "Salamander_piano/piano_v04_036.flac"
giPnoPitch[164] = 35.892
gSPnoFile[167] = "Salamander_piano/piano_v04_039.flac"
giPnoPitch[167] = 38.941
gSPnoFile[170] = "Salamander_piano/piano_v04_042.flac"
giPnoPitch[170] = 41.945
gSPnoFile[173] = "Salamander_piano/piano_v04_045.flac"
giPnoPitch[173] = 45.015
gSPnoFile[176] = "Salamander_piano/piano_v04_048.flac"
giPnoPitch[176] = 47.963
gSPnoFile[179] = "Salamander_piano/piano_v04_051.flac"
giPnoPitch[179] = 50.960
gSPnoFile[182] = "Salamander_piano/piano_v04_054.flac"
giPnoPitch[182] = 53.970
gSPnoFile[185] = "Salamander_piano/piano_v04_057.flac"
giPnoPitch[185] = 56.988
gSPnoFile[188] = "Salamander_piano/piano_v04_060.flac"
giPnoPitch[188] = 59.987
gSPnoFile[191] = "Salamander_piano/piano_v04_063.flac"
giPnoPitch[191] = 63.011
gSPnoFile[194] = "Salamander_piano/piano_v04_066.flac"
giPnoPitch[194] = 65.971
gSPnoFile[197] = "Salamander_piano/piano_v04_069.flac"
giPnoPitch[197] = 69.010
gSPnoFile[200] = "Salamander_piano/piano_v04_072.flac"
giPnoPitch[200] = 72.037
gSPnoFile[203] = "Salamander_piano/piano_v04_075.flac"
giPnoPitch[203] = 75.033
gSPnoFile[206] = "Salamander_piano/piano_v04_078.flac"
giPnoPitch[206] = 78.043
gSPnoFile[209] = "Salamander_piano/piano_v04_081.flac"
giPnoPitch[209] = 81.070
gSPnoFile[212] = "Salamander_piano/piano_v04_084.flac"
giPnoPitch[212] = 84.080
gSPnoFile[215] = "Salamander_piano/piano_v04_087.flac"
giPnoPitch[215] = 87.086
gSPnoFile[218] = "Salamander_piano/piano_v04_090.flac"
giPnoPitch[218] = 90.136
gSPnoFile[221] = "Salamander_piano/piano_v04_093.flac"
giPnoPitch[221] = 93.136
gSPnoFile[224] = "Salamander_piano/piano_v04_096.flac"
giPnoPitch[224] = 96.232
gSPnoFile[227] = "Salamander_piano/piano_v04_099.flac"
giPnoPitch[227] = 99.191
gSPnoFile[230] = "Salamander_piano/piano_v04_102.flac"
giPnoPitch[230] = 102.259
gSPnoFile[233] = "Salamander_piano/piano_v04_105.flac"
giPnoPitch[233] = 105.394
gSPnoFile[236] = "Salamander_piano/piano_v04_108.flac"
giPnoPitch[236] = 108.981
gSPnoFile[277] = "Salamander_piano/piano_v07_021.flac"
giPnoPitch[277] = 20.756
gSPnoFile[280] = "Salamander_piano/piano_v07_024.flac"
giPnoPitch[280] = 23.769
gSPnoFile[283] = "Salamander_piano/piano_v07_027.flac"
giPnoPitch[283] = 26.845
gSPnoFile[286] = "Salamander_piano/piano_v07_030.flac"
giPnoPitch[286] = 29.915
gSPnoFile[289] = "Salamander_piano/piano_v07_033.flac"
giPnoPitch[289] = 32.927
gSPnoFile[292] = "Salamander_piano/piano_v07_036.flac"
giPnoPitch[292] = 35.899
gSPnoFile[295] = "Salamander_piano/piano_v07_039.flac"
giPnoPitch[295] = 38.941
gSPnoFile[298] = "Salamander_piano/piano_v07_042.flac"
giPnoPitch[298] = 41.948
gSPnoFile[301] = "Salamander_piano/piano_v07_045.flac"
giPnoPitch[301] = 45.014
gSPnoFile[304] = "Salamander_piano/piano_v07_048.flac"
giPnoPitch[304] = 47.963
gSPnoFile[307] = "Salamander_piano/piano_v07_051.flac"
giPnoPitch[307] = 50.960
gSPnoFile[310] = "Salamander_piano/piano_v07_054.flac"
giPnoPitch[310] = 53.969
gSPnoFile[313] = "Salamander_piano/piano_v07_057.flac"
giPnoPitch[313] = 56.989
gSPnoFile[316] = "Salamander_piano/piano_v07_060.flac"
giPnoPitch[316] = 59.986
gSPnoFile[319] = "Salamander_piano/piano_v07_063.flac"
giPnoPitch[319] = 63.012
gSPnoFile[322] = "Salamander_piano/piano_v07_066.flac"
giPnoPitch[322] = 65.971
gSPnoFile[325] = "Salamander_piano/piano_v07_069.flac"
giPnoPitch[325] = 69.012
gSPnoFile[328] = "Salamander_piano/piano_v07_072.flac"
giPnoPitch[328] = 72.036
gSPnoFile[331] = "Salamander_piano/piano_v07_075.flac"
giPnoPitch[331] = 75.032
gSPnoFile[334] = "Salamander_piano/piano_v07_078.flac"
giPnoPitch[334] = 78.044
gSPnoFile[337] = "Salamander_piano/piano_v07_081.flac"
giPnoPitch[337] = 81.068
gSPnoFile[340] = "Salamander_piano/piano_v07_084.flac"
giPnoPitch[340] = 84.079
gSPnoFile[343] = "Salamander_piano/piano_v07_087.flac"
giPnoPitch[343] = 87.084
gSPnoFile[346] = "Salamander_piano/piano_v07_090.flac"
giPnoPitch[346] = 90.134
gSPnoFile[349] = "Salamander_piano/piano_v07_093.flac"
giPnoPitch[349] = 93.135
gSPnoFile[352] = "Salamander_piano/piano_v07_096.flac"
giPnoPitch[352] = 96.231
gSPnoFile[355] = "Salamander_piano/piano_v07_099.flac"
giPnoPitch[355] = 99.190
gSPnoFile[358] = "Salamander_piano/piano_v07_102.flac"
giPnoPitch[358] = 102.255
gSPnoFile[361] = "Salamander_piano/piano_v07_105.flac"
giPnoPitch[361] = 105.391
gSPnoFile[364] = "Salamander_piano/piano_v07_108.flac"
giPnoPitch[364] = 108.980
gSPnoFile[405] = "Salamander_piano/piano_v10_021.flac"
giPnoPitch[405] = 20.756
gSPnoFile[408] = "Salamander_piano/piano_v10_024.flac"
giPnoPitch[408] = 23.769
gSPnoFile[411] = "Salamander_piano/piano_v10_027.flac"
giPnoPitch[411] = 26.845
gSPnoFile[414] = "Salamander_piano/piano_v10_030.flac"
giPnoPitch[414] = 29.915
gSPnoFile[417] = "Salamander_piano/piano_v10_033.flac"
giPnoPitch[417] = 32.927
gSPnoFile[420] = "Salamander_piano/piano_v10_036.flac"
giPnoPitch[420] = 35.903
gSPnoFile[423] = "Salamander_piano/piano_v10_039.flac"
giPnoPitch[423] = 38.940
gSPnoFile[426] = "Salamander_piano/piano_v10_042.flac"
giPnoPitch[426] = 41.949
gSPnoFile[429] = "Salamander_piano/piano_v10_045.flac"
giPnoPitch[429] = 45.013
gSPnoFile[432] = "Salamander_piano/piano_v10_048.flac"
giPnoPitch[432] = 47.963
gSPnoFile[435] = "Salamander_piano/piano_v10_051.flac"
giPnoPitch[435] = 50.960
gSPnoFile[438] = "Salamander_piano/piano_v10_054.flac"
giPnoPitch[438] = 53.969
gSPnoFile[441] = "Salamander_piano/piano_v10_057.flac"
giPnoPitch[441] = 56.988
gSPnoFile[444] = "Salamander_piano/piano_v10_060.flac"
giPnoPitch[444] = 59.984
gSPnoFile[447] = "Salamander_piano/piano_v10_063.flac"
giPnoPitch[447] = 63.012
gSPnoFile[450] = "Salamander_piano/piano_v10_066.flac"
giPnoPitch[450] = 65.971
gSPnoFile[453] = "Salamander_piano/piano_v10_069.flac"
giPnoPitch[453] = 69.014
gSPnoFile[456] = "Salamander_piano/piano_v10_072.flac"
giPnoPitch[456] = 72.035
gSPnoFile[459] = "Salamander_piano/piano_v10_075.flac"
giPnoPitch[459] = 75.032
gSPnoFile[462] = "Salamander_piano/piano_v10_078.flac"
giPnoPitch[462] = 78.044
gSPnoFile[465] = "Salamander_piano/piano_v10_081.flac"
giPnoPitch[465] = 81.067
gSPnoFile[468] = "Salamander_piano/piano_v10_084.flac"
giPnoPitch[468] = 84.078
gSPnoFile[471] = "Salamander_piano/piano_v10_087.flac"
giPnoPitch[471] = 87.084
gSPnoFile[474] = "Salamander_piano/piano_v10_090.flac"
giPnoPitch[474] = 90.132
gSPnoFile[477] = "Salamander_piano/piano_v10_093.flac"
giPnoPitch[477] = 93.132
gSPnoFile[480] = "Salamander_piano/piano_v10_096.flac"
giPnoPitch[480] = 96.230
gSPnoFile[483] = "Salamander_piano/piano_v10_099.flac"
giPnoPitch[483] = 99.189
gSPnoFile[486] = "Salamander_piano/piano_v10_102.flac"
giPnoPitch[486] = 102.251
gSPnoFile[489] = "Salamander_piano/piano_v10_105.flac"
giPnoPitch[489] = 105.383
gSPnoFile[492] = "Salamander_piano/piano_v10_108.flac"
giPnoPitch[492] = 108.975
gSPnoFile[533] = "Salamander_piano/piano_v13_021.flac"
giPnoPitch[533] = 20.756
gSPnoFile[536] = "Salamander_piano/piano_v13_024.flac"
giPnoPitch[536] = 23.768
gSPnoFile[539] = "Salamander_piano/piano_v13_027.flac"
giPnoPitch[539] = 26.845
gSPnoFile[542] = "Salamander_piano/piano_v13_030.flac"
giPnoPitch[542] = 29.915
gSPnoFile[545] = "Salamander_piano/piano_v13_033.flac"
giPnoPitch[545] = 32.927
gSPnoFile[548] = "Salamander_piano/piano_v13_036.flac"
giPnoPitch[548] = 35.905
gSPnoFile[551] = "Salamander_piano/piano_v13_039.flac"
giPnoPitch[551] = 38.939
gSPnoFile[554] = "Salamander_piano/piano_v13_042.flac"
giPnoPitch[554] = 41.950
gSPnoFile[557] = "Salamander_piano/piano_v13_045.flac"
giPnoPitch[557] = 45.012
gSPnoFile[560] = "Salamander_piano/piano_v13_048.flac"
giPnoPitch[560] = 47.964
gSPnoFile[563] = "Salamander_piano/piano_v13_051.flac"
giPnoPitch[563] = 50.960
gSPnoFile[566] = "Salamander_piano/piano_v13_054.flac"
giPnoPitch[566] = 53.970
gSPnoFile[569] = "Salamander_piano/piano_v13_057.flac"
giPnoPitch[569] = 56.989
gSPnoFile[572] = "Salamander_piano/piano_v13_060.flac"
giPnoPitch[572] = 59.983
gSPnoFile[575] = "Salamander_piano/piano_v13_063.flac"
giPnoPitch[575] = 63.012
gSPnoFile[578] = "Salamander_piano/piano_v13_066.flac"
giPnoPitch[578] = 65.972
gSPnoFile[581] = "Salamander_piano/piano_v13_069.flac"
giPnoPitch[581] = 69.015
gSPnoFile[584] = "Salamander_piano/piano_v13_072.flac"
giPnoPitch[584] = 72.034
gSPnoFile[587] = "Salamander_piano/piano_v13_075.flac"
giPnoPitch[587] = 75.032
gSPnoFile[590] = "Salamander_piano/piano_v13_078.flac"
giPnoPitch[590] = 78.045
gSPnoFile[593] = "Salamander_piano/piano_v13_081.flac"
giPnoPitch[593] = 81.066
gSPnoFile[596] = "Salamander_piano/piano_v13_084.flac"
giPnoPitch[596] = 84.077
gSPnoFile[599] = "Salamander_piano/piano_v13_087.flac"
giPnoPitch[599] = 87.085
gSPnoFile[602] = "Salamander_piano/piano_v13_090.flac"
giPnoPitch[602] = 90.131
gSPnoFile[605] = "Salamander_piano/piano_v13_093.flac"
giPnoPitch[605] = 93.132
gSPnoFile[608] = "Salamander_piano/piano_v13_096.flac"
giPnoPitch[608] = 96.226
gSPnoFile[611] = "Salamander_piano/piano_v13_099.flac"
giPnoPitch[611] = 99.190
gSPnoFile[614] = "Salamander_piano/piano_v13_102.flac"
giPnoPitch[614] = 102.247
gSPnoFile[617] = "Salamander_piano/piano_v13_105.flac"
giPnoPitch[617] = 105.377
gSPnoFile[620] = "Salamander_piano/piano_v13_108.flac"
giPnoPitch[620] = 108.968
gSPnoFile[661] = "Salamander_piano/piano_v16_021.flac"
giPnoPitch[661] = 20.757
gSPnoFile[664] = "Salamander_piano/piano_v16_024.flac"
giPnoPitch[664] = 23.769
gSPnoFile[667] = "Salamander_piano/piano_v16_027.flac"
giPnoPitch[667] = 26.846
gSPnoFile[670] = "Salamander_piano/piano_v16_030.flac"
giPnoPitch[670] = 29.915
gSPnoFile[673] = "Salamander_piano/piano_v16_033.flac"
giPnoPitch[673] = 32.928
gSPnoFile[676] = "Salamander_piano/piano_v16_036.flac"
giPnoPitch[676] = 35.908
gSPnoFile[679] = "Salamander_piano/piano_v16_039.flac"
giPnoPitch[679] = 38.939
gSPnoFile[682] = "Salamander_piano/piano_v16_042.flac"
giPnoPitch[682] = 41.951
gSPnoFile[685] = "Salamander_piano/piano_v16_045.flac"
giPnoPitch[685] = 45.010
gSPnoFile[688] = "Salamander_piano/piano_v16_048.flac"
giPnoPitch[688] = 47.964
gSPnoFile[691] = "Salamander_piano/piano_v16_051.flac"
giPnoPitch[691] = 50.960
gSPnoFile[694] = "Salamander_piano/piano_v16_054.flac"
giPnoPitch[694] = 53.970
gSPnoFile[697] = "Salamander_piano/piano_v16_057.flac"
giPnoPitch[697] = 56.990
gSPnoFile[700] = "Salamander_piano/piano_v16_060.flac"
giPnoPitch[700] = 59.982
gSPnoFile[703] = "Salamander_piano/piano_v16_063.flac"
giPnoPitch[703] = 63.013
gSPnoFile[706] = "Salamander_piano/piano_v16_066.flac"
giPnoPitch[706] = 65.973
gSPnoFile[709] = "Salamander_piano/piano_v16_069.flac"
giPnoPitch[709] = 69.017
gSPnoFile[712] = "Salamander_piano/piano_v16_072.flac"
giPnoPitch[712] = 72.033
gSPnoFile[715] = "Salamander_piano/piano_v16_075.flac"
giPnoPitch[715] = 75.032
gSPnoFile[718] = "Salamander_piano/piano_v16_078.flac"
giPnoPitch[718] = 78.045
gSPnoFile[721] = "Salamander_piano/piano_v16_081.flac"
giPnoPitch[721] = 81.067
gSPnoFile[724] = "Salamander_piano/piano_v16_084.flac"
giPnoPitch[724] = 84.075
gSPnoFile[727] = "Salamander_piano/piano_v16_087.flac"
giPnoPitch[727] = 87.086
gSPnoFile[730] = "Salamander_piano/piano_v16_090.flac"
giPnoPitch[730] = 90.129
gSPnoFile[733] = "Salamander_piano/piano_v16_093.flac"
giPnoPitch[733] = 93.131
gSPnoFile[736] = "Salamander_piano/piano_v16_096.flac"
giPnoPitch[736] = 96.225
gSPnoFile[739] = "Salamander_piano/piano_v16_099.flac"
giPnoPitch[739] = 99.193
gSPnoFile[742] = "Salamander_piano/piano_v16_102.flac"
giPnoPitch[742] = 102.244
gSPnoFile[745] = "Salamander_piano/piano_v16_105.flac"
giPnoPitch[745] = 105.372
gSPnoFile[748] = "Salamander_piano/piano_v16_108.flac"
giPnoPitch[748] = 108.956
;
; Flute: index = layer * 128 + MIDI key, layer 0 = pp, 1 = mf, 2 = ff, keys B3 (59) to Db7 (97).
;    Keys without a recording at some dynamic use the nearest recording of the same dynamic.
;    giFluPitch = measured pitch (MIDI key, fractional), giFluLoopStart/End/Xfade (seconds),
;    giFluAttack = time (s) to reach the steady level
gSFluFile[59] = "Iowa_flute/flute_pp_059.flac"
giFluPitch[59] = 58.963
giFluLoopStart[59] = 0.66946
giFluLoopEnd[59] = 1.29977
giFluXfade[59] = 0.050
giFluAttack[59] = 0.294
gSFluFile[60] = "Iowa_flute/flute_pp_060.flac"
giFluPitch[60] = 59.909
giFluLoopStart[60] = 0.42814
giFluLoopEnd[60] = 1.25816
giFluXfade[60] = 0.050
giFluAttack[60] = 0.249
gSFluFile[61] = "Iowa_flute/flute_pp_061.flac"
giFluPitch[61] = 60.918
giFluLoopStart[61] = 0.61134
giFluLoopEnd[61] = 1.14717
giFluXfade[61] = 0.050
giFluAttack[61] = 0.190
gSFluFile[62] = "Iowa_flute/flute_pp_062.flac"
giFluPitch[62] = 61.773
giFluLoopStart[62] = 0.38059
giFluLoopEnd[62] = 1.05145
giFluXfade[62] = 0.050
giFluAttack[62] = 0.150
gSFluFile[63] = "Iowa_flute/flute_pp_063.flac"
giFluPitch[63] = 62.806
giFluLoopStart[63] = 0.38361
giFluLoopEnd[63] = 1.29819
giFluXfade[63] = 0.050
giFluAttack[63] = 0.155
gSFluFile[64] = "Iowa_flute/flute_pp_064.flac"
giFluPitch[64] = 63.895
giFluLoopStart[64] = 0.31546
giFluLoopEnd[64] = 1.15023
giFluXfade[64] = 0.050
giFluAttack[64] = 0.145
gSFluFile[65] = "Iowa_flute/flute_pp_065.flac"
giFluPitch[65] = 64.962
giFluLoopStart[65] = 0.57501
giFluLoopEnd[65] = 1.04714
giFluXfade[65] = 0.050
giFluAttack[65] = 0.309
gSFluFile[66] = "Iowa_flute/flute_pp_066.flac"
giFluPitch[66] = 65.959
giFluLoopStart[66] = 0.66678
giFluLoopEnd[66] = 1.23172
giFluXfade[66] = 0.050
giFluAttack[66] = 0.539
gSFluFile[67] = "Iowa_flute/flute_pp_067.flac"
giFluPitch[67] = 66.931
giFluLoopStart[67] = 0.61365
giFluLoopEnd[67] = 1.16565
giFluXfade[67] = 0.050
giFluAttack[67] = 0.135
gSFluFile[68] = "Iowa_flute/flute_pp_068.flac"
giFluPitch[68] = 67.900
giFluLoopStart[68] = 0.61934
giFluLoopEnd[68] = 1.17048
giFluXfade[68] = 0.050
giFluAttack[68] = 0.090
gSFluFile[69] = "Iowa_flute/flute_pp_069.flac"
giFluPitch[69] = 68.911
giFluLoopStart[69] = 0.60787
giFluLoopEnd[69] = 1.23356
giFluXfade[69] = 0.050
giFluAttack[69] = 0.110
gSFluFile[70] = "Iowa_flute/flute_pp_070.flac"
giFluPitch[70] = 69.929
giFluLoopStart[70] = 0.63116
giFluLoopEnd[70] = 1.46973
giFluXfade[70] = 0.050
giFluAttack[70] = 0.170
gSFluFile[71] = "Iowa_flute/flute_pp_071.flac"
giFluPitch[71] = 70.895
giFluLoopStart[71] = 0.64946
giFluLoopEnd[71] = 1.25955
giFluXfade[71] = 0.050
giFluAttack[71] = 0.489
gSFluFile[72] = "Iowa_flute/flute_pp_072.flac"
giFluPitch[72] = 71.934
giFluLoopStart[72] = 0.46034
giFluLoopEnd[72] = 1.44626
giFluXfade[72] = 0.050
giFluAttack[72] = 0.195
gSFluFile[73] = "Iowa_flute/flute_pp_073.flac"
giFluPitch[73] = 72.983
giFluLoopStart[73] = 0.55107
giFluLoopEnd[73] = 1.16814
giFluXfade[73] = 0.050
giFluAttack[73] = 0.125
gSFluFile[74] = "Iowa_flute/flute_pp_074.flac"
giFluPitch[74] = 73.889
giFluLoopStart[74] = 0.48698
giFluLoopEnd[74] = 1.00649
giFluXfade[74] = 0.050
giFluAttack[74] = 0.105
gSFluFile[75] = "Iowa_flute/flute_pp_075.flac"
giFluPitch[75] = 74.877
giFluLoopStart[75] = 0.82002
giFluLoopEnd[75] = 1.37483
giFluXfade[75] = 0.050
giFluAttack[75] = 0.145
gSFluFile[76] = "Iowa_flute/flute_pp_076.flac"
giFluPitch[76] = 75.847
giFluLoopStart[76] = 0.49054
giFluLoopEnd[76] = 1.25816
giFluXfade[76] = 0.050
giFluAttack[76] = 0.150
gSFluFile[77] = "Iowa_flute/flute_pp_077.flac"
giFluPitch[77] = 76.836
giFluLoopStart[77] = 0.94923
giFluLoopEnd[77] = 1.54104
giFluXfade[77] = 0.050
giFluAttack[77] = 0.170
gSFluFile[78] = "Iowa_flute/flute_pp_078.flac"
giFluPitch[78] = 77.893
giFluLoopStart[78] = 0.37796
giFluLoopEnd[78] = 1.18005
giFluXfade[78] = 0.050
giFluAttack[78] = 0.175
gSFluFile[79] = "Iowa_flute/flute_pp_079.flac"
giFluPitch[79] = 78.894
giFluLoopStart[79] = 0.37465
giFluLoopEnd[79] = 1.17653
giFluXfade[79] = 0.050
giFluAttack[79] = 0.080
gSFluFile[80] = "Iowa_flute/flute_pp_080.flac"
giFluPitch[80] = 79.778
giFluLoopStart[80] = 0.24068
giFluLoopEnd[80] = 0.86850
giFluXfade[80] = 0.050
giFluAttack[80] = 0.090
gSFluFile[81] = "Iowa_flute/flute_pp_081.flac"
giFluPitch[81] = 80.948
giFluLoopStart[81] = 0.82007
giFluLoopEnd[81] = 1.37619
giFluXfade[81] = 0.050
giFluAttack[81] = 0.070
gSFluFile[82] = "Iowa_flute/flute_pp_082.flac"
giFluPitch[82] = 81.860
giFluLoopStart[82] = 0.65023
giFluLoopEnd[82] = 1.20204
giFluXfade[82] = 0.050
giFluAttack[82] = 0.110
gSFluFile[83] = "Iowa_flute/flute_pp_082.flac"
giFluPitch[83] = 81.860
giFluLoopStart[83] = 0.65023
giFluLoopEnd[83] = 1.20204
giFluXfade[83] = 0.050
giFluAttack[83] = 0.110
gSFluFile[84] = "Iowa_flute/flute_pp_084.flac"
giFluPitch[84] = 83.929
giFluLoopStart[84] = 0.77519
giFluLoopEnd[84] = 1.40102
giFluXfade[84] = 0.050
giFluAttack[84] = 0.409
gSFluFile[85] = "Iowa_flute/flute_pp_085.flac"
giFluPitch[85] = 84.903
giFluLoopStart[85] = 0.23392
giFluLoopEnd[85] = 0.82104
giFluXfade[85] = 0.050
giFluAttack[85] = 0.080
gSFluFile[86] = "Iowa_flute/flute_pp_086.flac"
giFluPitch[86] = 85.894
giFluLoopStart[86] = 0.64642
giFluLoopEnd[86] = 1.20011
giFluXfade[86] = 0.050
giFluAttack[86] = 0.055
gSFluFile[87] = "Iowa_flute/flute_pp_087.flac"
giFluPitch[87] = 86.981
giFluLoopStart[87] = 0.93270
giFluLoopEnd[87] = 1.48249
giFluXfade[87] = 0.050
giFluAttack[87] = 0.514
gSFluFile[88] = "Iowa_flute/flute_pp_088.flac"
giFluPitch[88] = 88.090
giFluLoopStart[88] = 0.84814
giFluLoopEnd[88] = 1.38583
giFluXfade[88] = 0.050
giFluAttack[88] = 0.514
gSFluFile[89] = "Iowa_flute/flute_pp_089.flac"
giFluPitch[89] = 88.966
giFluLoopStart[89] = 0.83916
giFluLoopEnd[89] = 1.30397
giFluXfade[89] = 0.050
giFluAttack[89] = 0.644
gSFluFile[90] = "Iowa_flute/flute_pp_090.flac"
giFluPitch[90] = 89.940
giFluLoopStart[90] = 0.78120
giFluLoopEnd[90] = 1.30181
giFluXfade[90] = 0.050
giFluAttack[90] = 0.644
gSFluFile[91] = "Iowa_flute/flute_pp_091.flac"
giFluPitch[91] = 90.993
giFluLoopStart[91] = 0.75485
giFluLoopEnd[91] = 1.30510
giFluXfade[91] = 0.050
giFluAttack[91] = 0.075
gSFluFile[92] = "Iowa_flute/flute_pp_092.flac"
giFluPitch[92] = 92.011
giFluLoopStart[92] = 0.62787
giFluLoopEnd[92] = 1.03528
giFluXfade[92] = 0.050
giFluAttack[92] = 0.604
gSFluFile[93] = "Iowa_flute/flute_pp_093.flac"
giFluPitch[93] = 93.003
giFluLoopStart[93] = 0.53982
giFluLoopEnd[93] = 0.98009
giFluXfade[93] = 0.050
giFluAttack[93] = 0.444
gSFluFile[94] = "Iowa_flute/flute_pp_094.flac"
giFluPitch[94] = 93.979
giFluLoopStart[94] = 0.42463
giFluLoopEnd[94] = 0.85900
giFluXfade[94] = 0.050
giFluAttack[94] = 0.339
gSFluFile[95] = "Iowa_flute/flute_pp_094.flac"
giFluPitch[95] = 93.979
giFluLoopStart[95] = 0.42463
giFluLoopEnd[95] = 0.85900
giFluXfade[95] = 0.050
giFluAttack[95] = 0.339
gSFluFile[96] = "Iowa_flute/flute_pp_094.flac"
giFluPitch[96] = 93.979
giFluLoopStart[96] = 0.42463
giFluLoopEnd[96] = 0.85900
giFluXfade[96] = 0.050
giFluAttack[96] = 0.339
gSFluFile[97] = "Iowa_flute/flute_pp_094.flac"
giFluPitch[97] = 93.979
giFluLoopStart[97] = 0.42463
giFluLoopEnd[97] = 0.85900
giFluXfade[97] = 0.050
giFluAttack[97] = 0.339
gSFluFile[187] = "Iowa_flute/flute_mf_059.flac"
giFluPitch[187] = 59.046
giFluLoopStart[187] = 0.93957
giFluLoopEnd[187] = 1.49066
giFluXfade[187] = 0.050
giFluAttack[187] = 0.474
gSFluFile[188] = "Iowa_flute/flute_mf_060.flac"
giFluPitch[188] = 59.895
giFluLoopStart[188] = 1.19188
giFluLoopEnd[188] = 1.74569
giFluXfade[188] = 0.050
giFluAttack[188] = 0.319
gSFluFile[189] = "Iowa_flute/flute_mf_061.flac"
giFluPitch[189] = 60.882
giFluLoopStart[189] = 0.44288
giFluLoopEnd[189] = 0.94011
giFluXfade[189] = 0.050
giFluAttack[189] = 0.224
gSFluFile[190] = "Iowa_flute/flute_mf_062.flac"
giFluPitch[190] = 61.922
giFluLoopStart[190] = 1.18528
giFluLoopEnd[190] = 1.73583
giFluXfade[190] = 0.050
giFluAttack[190] = 0.185
gSFluFile[191] = "Iowa_flute/flute_mf_063.flac"
giFluPitch[191] = 62.963
giFluLoopStart[191] = 1.08796
giFluLoopEnd[191] = 1.64082
giFluXfade[191] = 0.050
giFluAttack[191] = 0.249
gSFluFile[192] = "Iowa_flute/flute_mf_064.flac"
giFluPitch[192] = 64.000
giFluLoopStart[192] = 1.27655
giFluLoopEnd[192] = 1.82880
giFluXfade[192] = 0.050
giFluAttack[192] = 0.234
gSFluFile[193] = "Iowa_flute/flute_mf_065.flac"
giFluPitch[193] = 64.977
giFluLoopStart[193] = 1.17687
giFluLoopEnd[193] = 1.77517
giFluXfade[193] = 0.050
giFluAttack[193] = 0.195
gSFluFile[194] = "Iowa_flute/flute_mf_066.flac"
giFluPitch[194] = 66.049
giFluLoopStart[194] = 1.25841
giFluLoopEnd[194] = 1.87993
giFluXfade[194] = 0.050
giFluAttack[194] = 0.215
gSFluFile[195] = "Iowa_flute/flute_mf_067.flac"
giFluPitch[195] = 67.059
giFluLoopStart[195] = 1.29553
giFluLoopEnd[195] = 1.87494
giFluXfade[195] = 0.050
giFluAttack[195] = 0.439
gSFluFile[196] = "Iowa_flute/flute_mf_068.flac"
giFluPitch[196] = 67.951
giFluLoopStart[196] = 1.19415
giFluLoopEnd[196] = 1.79524
giFluXfade[196] = 0.050
giFluAttack[196] = 0.220
gSFluFile[197] = "Iowa_flute/flute_mf_069.flac"
giFluPitch[197] = 69.076
giFluLoopStart[197] = 1.39016
giFluLoopEnd[197] = 1.94354
giFluXfade[197] = 0.050
giFluAttack[197] = 0.529
gSFluFile[198] = "Iowa_flute/flute_mf_070.flac"
giFluPitch[198] = 70.105
giFluLoopStart[198] = 1.52587
giFluLoopEnd[198] = 2.08322
giFluXfade[198] = 0.050
giFluAttack[198] = 0.249
gSFluFile[199] = "Iowa_flute/flute_mf_071.flac"
giFluPitch[199] = 71.111
giFluLoopStart[199] = 1.19336
giFluLoopEnd[199] = 1.76020
giFluXfade[199] = 0.050
giFluAttack[199] = 0.105
gSFluFile[200] = "Iowa_flute/flute_mf_072.flac"
giFluPitch[200] = 72.092
giFluLoopStart[200] = 1.32862
giFluLoopEnd[200] = 1.87993
giFluXfade[200] = 0.050
giFluAttack[200] = 0.195
gSFluFile[201] = "Iowa_flute/flute_mf_073.flac"
giFluPitch[201] = 73.100
giFluLoopStart[201] = 1.17045
giFluLoopEnd[201] = 1.72211
giFluXfade[201] = 0.050
giFluAttack[201] = 0.215
gSFluFile[202] = "Iowa_flute/flute_mf_074.flac"
giFluPitch[202] = 74.063
giFluLoopStart[202] = 0.81732
giFluLoopEnd[202] = 1.36814
giFluXfade[202] = 0.050
giFluAttack[202] = 0.070
gSFluFile[203] = "Iowa_flute/flute_mf_075.flac"
giFluPitch[203] = 75.062
giFluLoopStart[203] = 0.59025
giFluLoopEnd[203] = 1.14161
giFluXfade[203] = 0.050
giFluAttack[203] = 0.095
gSFluFile[204] = "Iowa_flute/flute_mf_076.flac"
giFluPitch[204] = 76.075
giFluLoopStart[204] = 0.52345
giFluLoopEnd[204] = 1.07481
giFluXfade[204] = 0.050
giFluAttack[204] = 0.075
gSFluFile[205] = "Iowa_flute/flute_mf_077.flac"
giFluPitch[205] = 77.029
giFluLoopStart[205] = 0.90898
giFluLoopEnd[205] = 1.52075
giFluXfade[205] = 0.050
giFluAttack[205] = 0.120
gSFluFile[206] = "Iowa_flute/flute_mf_078.flac"
giFluPitch[206] = 78.156
giFluLoopStart[206] = 0.71778
giFluLoopEnd[206] = 1.26848
giFluXfade[206] = 0.050
giFluAttack[206] = 0.105
gSFluFile[207] = "Iowa_flute/flute_mf_079.flac"
giFluPitch[207] = 79.057
giFluLoopStart[207] = 0.81975
giFluLoopEnd[207] = 1.62744
giFluXfade[207] = 0.050
giFluAttack[207] = 0.065
gSFluFile[208] = "Iowa_flute/flute_mf_080.flac"
giFluPitch[208] = 80.051
giFluLoopStart[208] = 1.50887
giFluLoopEnd[208] = 2.07506
giFluXfade[208] = 0.050
giFluAttack[208] = 0.145
gSFluFile[209] = "Iowa_flute/flute_mf_081.flac"
giFluPitch[209] = 81.123
giFluLoopStart[209] = 1.32576
giFluLoopEnd[209] = 2.00170
giFluXfade[209] = 0.050
giFluAttack[209] = 0.254
gSFluFile[210] = "Iowa_flute/flute_mf_082.flac"
giFluPitch[210] = 82.195
giFluLoopStart[210] = 1.26635
giFluLoopEnd[210] = 1.85499
giFluXfade[210] = 0.050
giFluAttack[210] = 0.130
gSFluFile[211] = "Iowa_flute/flute_mf_083.flac"
giFluPitch[211] = 83.179
giFluLoopStart[211] = 1.13236
giFluLoopEnd[211] = 1.73526
giFluXfade[211] = 0.050
giFluAttack[211] = 0.060
gSFluFile[212] = "Iowa_flute/flute_mf_084.flac"
giFluPitch[212] = 84.213
giFluLoopStart[212] = 0.99943
giFluLoopEnd[212] = 1.57744
giFluXfade[212] = 0.050
giFluAttack[212] = 0.055
gSFluFile[213] = "Iowa_flute/flute_mf_085.flac"
giFluPitch[213] = 85.220
giFluLoopStart[213] = 0.68544
giFluLoopEnd[213] = 1.75045
giFluXfade[213] = 0.050
giFluAttack[213] = 0.035
gSFluFile[214] = "Iowa_flute/flute_mf_086.flac"
giFluPitch[214] = 86.115
giFluLoopStart[214] = 0.99766
giFluLoopEnd[214] = 2.03832
giFluXfade[214] = 0.050
giFluAttack[214] = 0.050
gSFluFile[215] = "Iowa_flute/flute_mf_087.flac"
giFluPitch[215] = 87.197
giFluLoopStart[215] = 1.35306
giFluLoopEnd[215] = 1.96009
giFluXfade[215] = 0.050
giFluAttack[215] = 0.055
gSFluFile[216] = "Iowa_flute/flute_mf_088.flac"
giFluPitch[216] = 88.223
giFluLoopStart[216] = 0.95834
giFluLoopEnd[216] = 1.83673
giFluXfade[216] = 0.050
giFluAttack[216] = 0.269
gSFluFile[217] = "Iowa_flute/flute_mf_089.flac"
giFluPitch[217] = 89.179
giFluLoopStart[217] = 1.28887
giFluLoopEnd[217] = 1.92177
giFluXfade[217] = 0.050
giFluAttack[217] = 0.035
gSFluFile[218] = "Iowa_flute/flute_mf_090.flac"
giFluPitch[218] = 90.220
giFluLoopStart[218] = 1.13249
giFluLoopEnd[218] = 2.16984
giFluXfade[218] = 0.050
giFluAttack[218] = 0.659
gSFluFile[219] = "Iowa_flute/flute_mf_091.flac"
giFluPitch[219] = 91.184
giFluLoopStart[219] = 1.05478
giFluLoopEnd[219] = 1.78186
giFluXfade[219] = 0.050
giFluAttack[219] = 0.035
gSFluFile[220] = "Iowa_flute/flute_mf_092.flac"
giFluPitch[220] = 92.178
giFluLoopStart[220] = 1.38415
giFluLoopEnd[220] = 1.93537
giFluXfade[220] = 0.050
giFluAttack[220] = 0.070
gSFluFile[221] = "Iowa_flute/flute_mf_093.flac"
giFluPitch[221] = 93.107
giFluLoopStart[221] = 1.28256
giFluLoopEnd[221] = 1.90522
giFluXfade[221] = 0.050
giFluAttack[221] = 0.075
gSFluFile[222] = "Iowa_flute/flute_mf_094.flac"
giFluPitch[222] = 94.072
giFluLoopStart[222] = 1.41506
giFluLoopEnd[222] = 1.98481
giFluXfade[222] = 0.050
giFluAttack[222] = 0.155
gSFluFile[223] = "Iowa_flute/flute_mf_095.flac"
giFluPitch[223] = 95.184
giFluLoopStart[223] = 1.27036
giFluLoopEnd[223] = 1.82358
giFluXfade[223] = 0.050
giFluAttack[223] = 0.105
gSFluFile[224] = "Iowa_flute/flute_mf_096.flac"
giFluPitch[224] = 96.359
giFluLoopStart[224] = 1.40027
giFluLoopEnd[224] = 2.07959
giFluXfade[224] = 0.050
giFluAttack[224] = 0.249
gSFluFile[225] = "Iowa_flute/flute_mf_096.flac"
giFluPitch[225] = 96.359
giFluLoopStart[225] = 1.40027
giFluLoopEnd[225] = 2.07959
giFluXfade[225] = 0.050
giFluAttack[225] = 0.249
gSFluFile[315] = "Iowa_flute/flute_ff_059.flac"
giFluPitch[315] = 59.093
giFluLoopStart[315] = 1.23152
giFluLoopEnd[315] = 1.78515
giFluXfade[315] = 0.050
giFluAttack[315] = 0.733
gSFluFile[316] = "Iowa_flute/flute_ff_060.flac"
giFluPitch[316] = 59.953
giFluLoopStart[316] = 1.08841
giFluLoopEnd[316] = 1.64048
giFluXfade[316] = 0.050
giFluAttack[316] = 0.344
gSFluFile[317] = "Iowa_flute/flute_ff_061.flac"
giFluPitch[317] = 60.933
giFluLoopStart[317] = 0.95517
giFluLoopEnd[317] = 1.63549
giFluXfade[317] = 0.050
giFluAttack[317] = 0.309
gSFluFile[318] = "Iowa_flute/flute_ff_062.flac"
giFluPitch[318] = 61.942
giFluLoopStart[318] = 1.15918
giFluLoopEnd[318] = 1.71531
giFluXfade[318] = 0.050
giFluAttack[318] = 0.354
gSFluFile[319] = "Iowa_flute/flute_ff_063.flac"
giFluPitch[319] = 62.960
giFluLoopStart[319] = 1.52481
giFluLoopEnd[319] = 2.07483
giFluXfade[319] = 0.050
giFluAttack[319] = 0.155
gSFluFile[320] = "Iowa_flute/flute_ff_064.flac"
giFluPitch[320] = 64.013
giFluLoopStart[320] = 1.27270
giFluLoopEnd[320] = 1.82347
giFluXfade[320] = 0.050
giFluAttack[320] = 0.150
gSFluFile[321] = "Iowa_flute/flute_ff_065.flac"
giFluPitch[321] = 65.066
giFluLoopStart[321] = 1.58991
giFluLoopEnd[321] = 2.13991
giFluXfade[321] = 0.050
giFluAttack[321] = 0.095
gSFluFile[322] = "Iowa_flute/flute_ff_066.flac"
giFluPitch[322] = 66.101
giFluLoopStart[322] = 1.46649
giFluLoopEnd[322] = 2.02166
giFluXfade[322] = 0.050
giFluAttack[322] = 0.125
gSFluFile[323] = "Iowa_flute/flute_ff_067.flac"
giFluPitch[323] = 67.092
giFluLoopStart[323] = 1.27048
giFluLoopEnd[323] = 1.82052
giFluXfade[323] = 0.050
giFluAttack[323] = 0.105
gSFluFile[324] = "Iowa_flute/flute_ff_068.flac"
giFluPitch[324] = 68.100
giFluLoopStart[324] = 0.79721
giFluLoopEnd[324] = 1.38605
giFluXfade[324] = 0.050
giFluAttack[324] = 0.100
gSFluFile[325] = "Iowa_flute/flute_ff_069.flac"
giFluPitch[325] = 69.141
giFluLoopStart[325] = 1.02957
giFluLoopEnd[325] = 1.64263
giFluXfade[325] = 0.050
giFluAttack[325] = 0.210
gSFluFile[326] = "Iowa_flute/flute_ff_070.flac"
giFluPitch[326] = 70.188
giFluLoopStart[326] = 1.11426
giFluLoopEnd[326] = 1.66723
giFluXfade[326] = 0.050
giFluAttack[326] = 0.249
gSFluFile[327] = "Iowa_flute/flute_ff_071.flac"
giFluPitch[327] = 71.166
giFluLoopStart[327] = 1.17544
giFluLoopEnd[327] = 1.73220
giFluXfade[327] = 0.050
giFluAttack[327] = 0.105
gSFluFile[328] = "Iowa_flute/flute_ff_072.flac"
giFluPitch[328] = 72.154
giFluLoopStart[328] = 0.90070
giFluLoopEnd[328] = 1.79705
giFluXfade[328] = 0.050
giFluAttack[328] = 0.135
gSFluFile[329] = "Iowa_flute/flute_ff_073.flac"
giFluPitch[329] = 73.143
giFluLoopStart[329] = 0.80905
giFluLoopEnd[329] = 1.73707
giFluXfade[329] = 0.050
giFluAttack[329] = 0.175
gSFluFile[330] = "Iowa_flute/flute_ff_074.flac"
giFluPitch[330] = 74.127
giFluLoopStart[330] = 1.23556
giFluLoopEnd[330] = 2.00351
giFluXfade[330] = 0.050
giFluAttack[330] = 0.663
gSFluFile[331] = "Iowa_flute/flute_ff_075.flac"
giFluPitch[331] = 75.066
giFluLoopStart[331] = 1.39893
giFluLoopEnd[331] = 1.96859
giFluXfade[331] = 0.050
giFluAttack[331] = 0.414
gSFluFile[332] = "Iowa_flute/flute_ff_076.flac"
giFluPitch[332] = 76.080
giFluLoopStart[332] = 1.20982
giFluLoopEnd[332] = 1.76553
giFluXfade[332] = 0.050
giFluAttack[332] = 0.284
gSFluFile[333] = "Iowa_flute/flute_ff_077.flac"
giFluPitch[333] = 77.150
giFluLoopStart[333] = 1.46274
giFluLoopEnd[333] = 2.06304
giFluXfade[333] = 0.050
giFluAttack[333] = 0.145
gSFluFile[334] = "Iowa_flute/flute_ff_078.flac"
giFluPitch[334] = 78.245
giFluLoopStart[334] = 1.45714
giFluLoopEnd[334] = 2.13810
giFluXfade[334] = 0.050
giFluAttack[334] = 0.200
gSFluFile[335] = "Iowa_flute/flute_ff_079.flac"
giFluPitch[335] = 79.250
giFluLoopStart[335] = 0.99707
giFluLoopEnd[335] = 1.55102
giFluXfade[335] = 0.050
giFluAttack[335] = 0.050
gSFluFile[336] = "Iowa_flute/flute_ff_080.flac"
giFluPitch[336] = 80.172
giFluLoopStart[336] = 0.72617
giFluLoopEnd[336] = 1.45590
giFluXfade[336] = 0.050
giFluAttack[336] = 0.055
gSFluFile[337] = "Iowa_flute/flute_ff_081.flac"
giFluPitch[337] = 81.154
giFluLoopStart[337] = 0.91862
giFluLoopEnd[337] = 1.54286
giFluXfade[337] = 0.050
giFluAttack[337] = 0.055
gSFluFile[338] = "Iowa_flute/flute_ff_082.flac"
giFluPitch[338] = 82.220
giFluLoopStart[338] = 0.59966
giFluLoopEnd[338] = 1.88707
giFluXfade[338] = 0.050
giFluAttack[338] = 0.050
gSFluFile[339] = "Iowa_flute/flute_ff_083.flac"
giFluPitch[339] = 83.268
giFluLoopStart[339] = 0.91628
giFluLoopEnd[339] = 1.48639
giFluXfade[339] = 0.050
giFluAttack[339] = 0.429
gSFluFile[340] = "Iowa_flute/flute_ff_084.flac"
giFluPitch[340] = 84.250
giFluLoopStart[340] = 0.65626
giFluLoopEnd[340] = 1.35612
giFluXfade[340] = 0.050
giFluAttack[340] = 0.130
gSFluFile[341] = "Iowa_flute/flute_ff_085.flac"
giFluPitch[341] = 85.329
giFluLoopStart[341] = 0.60766
giFluLoopEnd[341] = 1.23639
giFluXfade[341] = 0.050
giFluAttack[341] = 0.030
gSFluFile[342] = "Iowa_flute/flute_ff_086.flac"
giFluPitch[342] = 86.207
giFluLoopStart[342] = 1.18397
giFluLoopEnd[342] = 1.86519
giFluXfade[342] = 0.050
giFluAttack[342] = 0.200
gSFluFile[343] = "Iowa_flute/flute_ff_087.flac"
giFluPitch[343] = 87.313
giFluLoopStart[343] = 0.75723
giFluLoopEnd[343] = 1.45102
giFluXfade[343] = 0.050
giFluAttack[343] = 0.015
gSFluFile[344] = "Iowa_flute/flute_ff_088.flac"
giFluPitch[344] = 88.417
giFluLoopStart[344] = 0.80170
giFluLoopEnd[344] = 1.72381
giFluXfade[344] = 0.050
giFluAttack[344] = 0.040
gSFluFile[345] = "Iowa_flute/flute_ff_089.flac"
giFluPitch[345] = 89.387
giFluLoopStart[345] = 0.57873
giFluLoopEnd[345] = 1.52132
giFluXfade[345] = 0.050
giFluAttack[345] = 0.030
gSFluFile[346] = "Iowa_flute/flute_ff_090.flac"
giFluPitch[346] = 90.360
giFluLoopStart[346] = 0.96961
giFluLoopEnd[346] = 1.57596
giFluXfade[346] = 0.050
giFluAttack[346] = 0.035
gSFluFile[347] = "Iowa_flute/flute_ff_091.flac"
giFluPitch[347] = 91.315
giFluLoopStart[347] = 0.70658
giFluLoopEnd[347] = 1.55907
giFluXfade[347] = 0.050
giFluAttack[347] = 0.025
gSFluFile[348] = "Iowa_flute/flute_ff_092.flac"
giFluPitch[348] = 92.317
giFluLoopStart[348] = 1.01490
giFluLoopEnd[348] = 1.63560
giFluXfade[348] = 0.050
giFluAttack[348] = 0.589
gSFluFile[349] = "Iowa_flute/flute_ff_093.flac"
giFluPitch[349] = 93.231
giFluLoopStart[349] = 1.37506
giFluLoopEnd[349] = 1.92540
giFluXfade[349] = 0.050
giFluAttack[349] = 0.040
gSFluFile[350] = "Iowa_flute/flute_ff_094.flac"
giFluPitch[350] = 94.251
giFluLoopStart[350] = 1.08512
giFluLoopEnd[350] = 1.87993
giFluXfade[350] = 0.050
giFluAttack[350] = 0.439
gSFluFile[351] = "Iowa_flute/flute_ff_095.flac"
giFluPitch[351] = 95.360
giFluLoopStart[351] = 1.12315
giFluLoopEnd[351] = 1.85000
giFluXfade[351] = 0.050
giFluAttack[351] = 0.060
gSFluFile[352] = "Iowa_flute/flute_ff_096.flac"
giFluPitch[352] = 96.432
giFluLoopStart[352] = 1.26927
giFluLoopEnd[352] = 1.86553
giFluXfade[352] = 0.050
giFluAttack[352] = 0.125
gSFluFile[353] = "Iowa_flute/flute_ff_097.flac"
giFluPitch[353] = 97.390
giFluLoopStart[353] = 0.26803
giFluLoopEnd[353] = 1.77517
giFluXfade[353] = 0.050
giFluAttack[353] = 0.055

; -- LOAD THE FLUTE RECORDINGS IN REAL TIME --
; Loading a file when a note starts may interrupt the real-time output (clicks, distortion)
; when several notes need new files together. In real time (e.g. output to "dac"), the flute
; recordings are loaded when the orchestra starts, before the Csound server accepts notes.
; The piano recordings are read from disk while playing (diskin2), so they need no loading.

if (strcmp(getcfg(4), "1") == 0) then
idx    = 0
while (idx < 384) do
if (strlen(gSFluFile[idx]) > 0) then
ifn    ftgenonce  31000 + idx, 0, 0, 1, gSFluFile[idx], 0, 0, 1
endif
idx    = idx + 1
od
endif
