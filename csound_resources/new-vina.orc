sr = 22050
kr = 2205
ksmps = 10
nchnls = 1

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;   instrument 1   "Vina"
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; p1 = 1
; p2 = start time
; p3 = duration
; p4 = pitch in octave.pitchclass notation (or Hz if >= 15)
; p5 = volume start value
; p6 = volume end value
; p7 = volume function table number
; p8 = continuous pitchbend start value (cents)
; p9 = continuous pitchbend end value (cents)
; p10 = continuous pitchbend function table number
; p11 = "slide" start value (cents, ADDval)
; p12 = "slide" end value (cents, ADDval)
; p13 = "slide" function table number
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; If a function table number is 0 or negative, the
; corresponding parameter is interpolated linearly
; between its start and end values.
; Otherwise the table (e.g. GEN -7, not rescaled) is
; read from beginning to end over the note duration,
; and its values are used as they are.
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

instr 1
; Scaling factors
ivolscale = 32767.0 / 127.0
ipitchscale = log(2.0) / 1200.0

; sound duration
idur = p3

; interpret p4 as frequency or octave point pitch-class
if (p4 < 15.0) then
  icpspitch = cpspch(p4)
else
  icpspitch = p4
endif

; function table numbers
ifvol    = p7
ifcontpb = p10
ifslide  = p13

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; volume block
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
if (ifvol > 0) then
  kvolindex line 0, idur, 1
  kvol tablei kvolindex, ifvol, 1
else
  kvol line p5, idur, p6
endif

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; continuous pitch bend block
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
if (ifcontpb > 0) then
  kcontpbindex line 0, idur, 1
  kcontpb tablei kcontpbindex, ifcontpb, 1
else
  kcontpb line p8, idur, p9
endif

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; "slide" block (ADDval, in cents)
; Smooth interpolation: NO quantisation
; to semitone steps.
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
if (ifslide > 0) then
  kslideindex line 0, idur, 1
  kslide tablei kslideindex, ifslide, 1
else
  kslide line p11, idur, p12
endif

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; Main section
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; ADDval: pitch bend and slide (cents) are added,
; then applied to the base frequency
kcpspitch = icpspitch * exp(ipitchscale * (kcontpb + kslide))
kamp = kvol * ivolscale

; display kslide, idur
; display kcpspitch, idur

asignal pluck kamp, kcpspitch, icpspitch, 1, 5, 0.916, 0.083

out asignal * 0.5

endin
