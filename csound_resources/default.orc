// Bol Processor BP3 compatible with version BP2.9.8
// Orchestra saved as "default.orc". Date: 2020-11-09 11:03:14
; This is a simple orchestra file for Bol Processor's default Csound score output

; It contains one single instrument: an oscillator using wave-table 1
; Argument p2 is the start date, p3 the duration (as per the standard)
; Argument p4 is the pitch in octave point pitch-class format
; Volume (range 0..127) is supplied by performance control _volume()
; to arguments p5 (beginning value) and p6 (end value), or via the table
; whose index is supplied by p7.
; Pitchbend is supplied in cents to arguments p8 (beginning value)
; and p9 (end value), or via the table whose index is supplied by p10.

sr = 22050
kr = 2205
ksmps = 10
nchnls = 1

instr 1

ik1 = 32767. / 127.
ik2 = log(2.) / 1200.

if (p4 < 15.0) then
icps = cpspch(p4)
else icps = p4
endif

ifvol = p7
ifcents = p10

kvol line p5, p3, p6
if (ifvol <= 0) goto volumelin
ilenvol = ftlen(ifvol)
kndxvol line 0, p3, ilenvol
kvol tablei kndxvol, ifvol

volumelin: kcents line p8, p3, p9
if (ifcents <= 0) goto pitchbendlin
ilencents = ftlen(ifcents)
kndxcents line 0, p3, ilencents
kcents tablei kndxcents, ifcents

pitchbendlin: kpitch = icps * exp(kcents * ik2)
kamp = kvol * ik1

a1 oscil kamp, kpitch, 1
out a1 * 0.1
endin