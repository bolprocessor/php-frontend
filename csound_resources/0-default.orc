; New Csound instruments for BP3's default Csound score output.
; They follow the specifications handled by BP3 version 3.0.0 and above.

; Instruments:
;		1	Plucked string (outputs to StereoDelay)
;		10	Sine wave (outputs to GlobalReverb)
;
; Effects:
;		StereoDelay (outputs to GlobalReverb)
;		GlobalReverb (outputs to file/speakers)

; Default instrument parameters used by BP3:
;		p2	start time
;		p3	duration
;		p4	pitch in either octave point pitch-class or cps format
;
;	Volume (range 0-127) is supplied by performance control _volume() as
;		p5,p6	beginning & end volumes
;		p7		a table number containing the volume function (or 0)
;	When p7 is positive, the table is used instead of the beginning & end values
;
;	Pitchbend is supplied in cents as
;		p8,p9	beginning & end pitchbend values
;		p10		a table number containing the pitchbend function (or 0)
;	When p10 is positive, the table is used instead of the beginning & end values

sr = 44100
kr = 4410
ksmps = 10
nchnls = 2
0dbfs = 1.0

; Maximum amplitude of individual notes (adjust if samples overflow)
; Default value 1/12 should allow at least 12 simultaneous notes.
giMaxAmp = 1.0/12.0

; -- WAVE TABLES --

giSine	ftgen  0, 0, 32769, 10, 1	; sine wave

; -- EFFECTS ROUTING --

connect	 "1", "delaySend", "StereoDelay", "delayin" 
connect	 "10", "reverbSendL", "GlobalReverb", "reverbinL" 
connect	 "10", "reverbSendR", "GlobalReverb", "reverbinR" 

connect	 "StereoDelay", "reverbSendL", "GlobalReverb", "reverbinL" 
connect	 "StereoDelay", "reverbSendR", "GlobalReverb", "reverbinR" 

; -- CREATE EFFECTS --

alwayson "StereoDelay", 0.05, 0.08, 0.25
alwayson "GlobalReverb", 0.7, 16000, 0.3

; -- USER DEFINED OPCODES --

; An opcode to convert pitch parameters to cycles per second (cps).
; Parameters less than 15.0 are interpreted as octave.pitchclass format, 
; otherwise they are assumed to be frequencies already in cps.
opcode bp_pitch, i, i
	ipitchparm	xin
	
	if (p4 < 15.0) then
		icps = cpspch(p4)
	else
		icps = p4
	endif

	xout	icps
endop

; This opcode takes the default BP parameters p3 thru p10 and 
; returns two k-rate signals for the instrument's pitch and volume.
opcode bp_control, kk, iiiiiiii
	idur, ip4, ip5, ip6, ip7, ip8, ip9, ip10  xin
	
	ik1	= 1.0 / 127.0		; scale Midi-range volumes (0-127)
	ik2	= log(2.) / 1200.	; constant to convert cents to frequency ratios
	icps  bp_pitch  ip4
	ifvol = ip7				; volume table number
	ifcents = ip10			; pitchbend table number

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
	
	xout	kpitch, kvol * ik1
endop

; -- INSTRUMENTS --

; Simple plucked string instrument
instr 1
	idur 	= p3
	icps  	bp_pitch  p4
	
	kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10
	
	kdclick	linseg	1.0, idur - 0.05, 1.0, 0.05, 0		; declick envelope
	kamp	=		kvol * kdclick * giMaxAmp

	a1		pluck	kamp, kpitch, icps, 0, 1
		
			; outs	a1, a1
			outleta	"delaySend", a1
endin

; Simple sine wave instrument
instr 10
	idur = p3

	kpitch, kvol  bp_control  idur, p4, p5, p6, p7, p8, p9, p10
	
	kdclick	 linseg  0, 0.02, 1.0, idur - 0.07, 1.0, 0.05, 0	; declick envelope
	kamp = kvol * kdclick * giMaxAmp

	a1	oscili	kamp, kpitch, giSine
		; outs	a1, a1
		outleta	"reverbSendL", a1
		outleta	"reverbSendR", a1
endin

; Stereo delay effect (mono input)
;	p4	left delay time (in seconds)
;	p5	right delay time (in seconds)
;	p6	wet/dry mix (range 0-1)
instr StereoDelay
	idelay1 = p4
	idelay2 = p5
	iwet1 = p6
	iwet2 = p6 - 0.05
	idry = 1.0 - iwet1
	if (iwet2 < 0) then
		iwet2 = 0
	endif

	ain		inleta	"delayin"

	adel1	delay	ain, idelay1
	adel2	delay	ain, idelay2
	aleft	=		ain*idry + adel1*iwet1
	aright	=		ain*idry + adel2*iwet2
	
			outleta	"reverbSendL", aleft
			outleta	"reverbSendR", aright
endin

; Global reverb effect
;	p4	feedback level (range 0-1)
; 	p5	filter cutoff frequency
; 	p6	wet/dry mix (range 0-1)
instr GlobalReverb
	ifeedbk = p4
	icutoff = p5
	iwet = p6
	idry = 1.0 - iwet
	
	ainl	inleta	"reverbinL"
	ainr	inleta	"reverbinR"
	
	aoutl, aoutr  reverbsc  ainl, ainr, ifeedbk, icutoff
	
			outs	idry*ainl + iwet*aoutl, idry*ainr + iwet*aoutr
endin
