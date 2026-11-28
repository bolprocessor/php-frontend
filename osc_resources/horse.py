#!/usr/bin/env python3
# Bol Processor: a simulated galloping horse played in real-time OSC
# See https://bolprocessor.org/usage-in-robotics/ and ReadMe.txt in this folder.
#
# Run:   python3 horse.py            (window, listening to UDP port 7400 on this computer)
#        python3 horse.py --help     (options)
# or click START THE ROBOT on the page of a grammar or data naming it with a line "-py.horse" on top (output "Real-time OSC").
# Then, in the Bol Processor, select the output format "Real-time OSC" (host 127.0.0.1, port 7400)
# and produce "-gr.robot_horse", which names the instruments of "-cs.Horse".
#
# Only the standard library of Python 3 is used (tkinter for the window).
#
# Each joint of the horse is an instrument (see JOINTS below): the pitch of the body, the neck, the head, the tail,
# the upper part and the knee (front legs) or hock (hind legs) of each leg, the lift of the body and the pace of the ground.
# A note of instrument X is a movement of joint X: its value goes from the value of parameter X at the beginning of the note
# (/bp/note ... X start X2 end) to its value at the end, over the duration of the note. A /bp/env message for this note
# gives the intermediate positions (time, value...) when the parameter follows a curve.
# Without the parameter (e.g. a project without "-cs.Horse"), instruments 1 to 14 (bp1...bp14) move the joints in the order
# of JOINTS, and the value is given by the key: (key - 60) x 10, so that C4 = 0, D4 = 20, C5 = 120...
#
# The horse obeys gravity: its lowest hoof stays on the ground, unless "lift" raises it (the moments in the air of a trot,
# canter or gallop, and jumps). The ground passes under the horse at the speed given by "pace" (pixels per second).
#
# Like a real robot, a joint cannot move faster than --max-speed degrees per second: when a note starts at an angle
# far from the current one, the joint catches up as fast as it can.
# /bp/stop (STOP, PAUSE or PANIC) freezes the horse where it is.
#
# To drive real servos, write the angles in send_to_hardware() below.

import argparse
import math
import queue
import socket
import sys
import struct
import threading
import time

TITLE = "Bol Processor galloping horse"

JOINTS = ["body", "neck", "head", "tail", "foreleft", "foreleftknee", "foreright", "forerightknee",
	"hindleft", "hindlefthock", "hindright", "hindrighthock", "lift", "pace"]
LIMITS = {  # degrees (pixels for lift, pixels per second for pace), as a robot would have
	"body": (-30, 60),  # positive: front raised (rearing)
	"neck": (-60, 60),  # positive: raised
	"head": (-60, 60),  # positive: nose up
	"tail": (-20, 100),  # positive: raised
	"foreleft": (-70, 90),  # upper part of the legs: positive = forward
	"foreright": (-70, 90),
	"hindleft": (-80, 70),
	"hindright": (-80, 70),
	"foreleftknee": (-10, 150),  # flexion: the hoof folds backward
	"forerightknee": (-10, 150),
	"hindlefthock": (-10, 150),  # flexion: the hoof folds forward
	"hindrighthock": (-10, 150),
	"lift": (0, 200),
	"pace": (0, 1200),
	}
SPEED_FACTOR = {"pace": 1.}  # pace changes by at most --max-speed pixels per second, each second


# ---------- OSC decoding ----------

def osc_string(data, i):
	end = data.index(b"\0", i)
	s = data[i:end].decode("utf-8", "replace")
	return s, (end + 4) & ~3


def osc_message(data):
	# Returns (address, [arguments]), or None for a bundle (see osc_packets())
	address, i = osc_string(data, 0)
	if i >= len(data):
		return address, []
	tags, i = osc_string(data, i)
	args = []
	for t in tags[1:]:
		if t == "i":
			args.append(struct.unpack(">i", data[i:i + 4])[0]); i += 4
		elif t == "f":
			args.append(struct.unpack(">f", data[i:i + 4])[0]); i += 4
		elif t == "d":
			args.append(struct.unpack(">d", data[i:i + 8])[0]); i += 8
		elif t == "h":
			args.append(struct.unpack(">q", data[i:i + 8])[0]); i += 8
		elif t == "s":
			s, i = osc_string(data, i); args.append(s)
		elif t == "T":
			args.append(True)
		elif t == "F":
			args.append(False)
		# '[', ']', 'N' carry no data
	return address, args


def osc_packets(data):
	# Messages of a packet, unpacking bundles
	if data.startswith(b"#bundle\0"):
		i = 16
		while i + 4 <= len(data):
			size = struct.unpack(">i", data[i:i + 4])[0]
			yield from osc_packets(data[i + 4:i + 4 + size])
			i += 4 + size
	else:
		yield osc_message(data)


def receive(sock, messages, verbose):
	while True:
		data, _ = sock.recvfrom(65536)
		now = time.monotonic()
		try:
			for address, args in osc_packets(data):
				if verbose:
					print(address, " ".join(str(round(a, 3)) if isinstance(a, float) else str(a) for a in args), flush=True)
				messages.put((now, address, args))
		except (ValueError, struct.error):
			print("Incorrect OSC message ignored", flush=True)


# ---------- The joints ----------

class Joint:
	def __init__(self, name):
		self.name = name
		self.angle = 0.  # actual position
		self.keys = [(0., 0.)]  # commanded movement: (time, angle) from time.monotonic()
		self.note = -1  # id of the note being played

	def move(self, start, keys, note):
		lo, hi = LIMITS[self.name]
		self.keys = [(start + t, min(hi, max(lo, a))) for t, a in keys]
		self.note = note

	def command(self, now):
		keys = self.keys
		if now <= keys[0][0]:
			return keys[0][1]
		for (t1, a1), (t2, a2) in zip(keys, keys[1:]):
			if now <= t2:
				return a1 if t2 <= t1 else a1 + (a2 - a1) * (now - t1) / (t2 - t1)
		return keys[-1][1]

	def update(self, now, dt, max_speed):
		target = self.command(now)
		step = target - self.angle
		if max_speed > 0.:
			limit = max_speed * SPEED_FACTOR.get(self.name, 1.) * dt
			step = max(-limit, min(limit, step))
		self.angle += step

	def freeze(self, now):
		self.keys = [(now, self.angle)]
		self.note = -1


class Horse:
	def __init__(self, max_speed):
		self.joints = {name: Joint(name) for name in JOINTS}
		self.max_speed = max_speed
		self.status = "Waiting for the Bol Processor..."
		self.notes = 0
		self.distance = 0.  # pixels travelled, for the scrolling of the ground

	def joint_of(self, number, name):
		if name in self.joints:
			return self.joints[name]
		if 1 <= number <= len(JOINTS):
			return self.joints[JOINTS[number - 1]]
		return None

	def handle(self, now, address, args):
		if address == "/bp/note" and len(args) >= 8:
			note, number, name, key, freq, duration = args[:6]
			joint = self.joint_of(number, name)
			if joint is None:
				return
			values = dict(zip(args[8::2], args[9::2]))
			for p in (joint.name, "angle"):
				if p in values:
					start = values[p]
					end = values.get(p + "2", start)
					break
			else:
				start = end = (key - 60) * 10.
			joint.move(now, [(0., start), (max(duration, 0.), end)], note)
			self.notes += 1
			self.status = "Playing: %d movements" % self.notes
		elif address == "/bp/env" and len(args) >= 6:
			note, number, name, param = args[:4]
			joint = self.joint_of(number, name)
			if joint is None or joint.note != note or param not in (joint.name, "angle"):
				return
			points = args[4:]
			start = joint.keys[0][0]
			joint.move(start, list(zip(points[0::2], points[1::2])), note)
		elif address == "/bp/start":
			self.notes = 0
			self.status = "Playing"
		elif address == "/bp/stop":
			for joint in self.joints.values():
				joint.freeze(now)
			self.status = "Stopped"
		elif address == "/bp/end":
			self.status = "Performance over: %d movements" % self.notes

	def update(self, now, dt):
		for joint in self.joints.values():
			joint.update(now, dt, self.max_speed)
		self.distance += self.joints["pace"].angle * dt
		send_to_hardware(self.angles())

	def angles(self):
		return {name: joint.angle for name, joint in self.joints.items()}


def send_to_hardware(angles):
	# Called about 60 times per second with the value of each joint, e.g. angles["foreleft"] in degrees.
	# Example with servos on a Pololu Maestro or an Arduino reading lines on a serial port (pyserial):
	#   port.write((" ".join("%d" % (90 + angles[j]) for j in JOINTS[:12]) + "\n").encode())
	pass


# ---------- Drawing ----------
# horse_shapes() describes the picture as a list of shapes, drawn by the window (or by any other program, e.g. to make a film).
# Coordinates of the horse: x forward (to the right), y up, origin at the centre of the body.

BODY_COLOR = "#8c5a35"
FAR_COLOR = "#6b4226"
DARK = "#3b2a1e"
HOOF = "#2b2b2b"
FORE_UPPER, FORE_LOWER = 62, 64
HIND_UPPER, HIND_LOWER = 64, 66
SHOULDER, HIP = (78, -14), (-86, -8)
WITHERS, RUMP = (92, 18), (-116, 14)


def ray(p, length, degrees):
	r = math.radians(degrees)
	return p[0] + length * math.cos(r), p[1] + length * math.sin(r)


def legs(a):
	# Joints of each leg in the coordinates of the horse (before the pitch of the body): {leg: (top, knee, hoof)}
	out = {}
	for side in ("left", "right"):
		upper = -90 + a["fore" + side]
		knee = ray(SHOULDER, FORE_UPPER, upper)
		out["fore" + side] = (SHOULDER, knee, ray(knee, FORE_LOWER, upper - a["fore" + side + "knee"]))
		upper = -98 + a["hind" + side]
		hock = ray(HIP, HIND_UPPER, upper)
		out["hind" + side] = (HIP, hock, ray(hock, HIND_LOWER, upper + 16 + a["hind" + side + "hock"]))
	return out


def horse_shapes(a, distance, w, h):
	# a: values of the joints. Returns [(kind, points, options)], kind = "line", "polygon", "oval" or "text"
	s = min(w / 760., h / 520.)
	ground = h - 70 * s
	pitch = a["body"]
	cp, sp = math.cos(math.radians(pitch)), math.sin(math.radians(pitch))
	leg = legs(a)

	def turn(p):  # pitch of the body around its centre
		return p[0] * cp - p[1] * sp, p[0] * sp + p[1] * cp

	lowest = min(turn(points[2])[1] for points in leg.values())
	cx, cy = w / 2 - 20 * s, ground + (lowest - a["lift"]) * s  # screen y of the centre: the lowest hoof on the ground

	def screen(p):
		q = turn(p)
		return cx + q[0] * s, cy - q[1] * s

	shapes = []
	# Scenery: fence posts far away (half speed) and tufts of grass on the ground
	far = (distance * 0.5) % (160 * s)
	x = -far
	while x < w + 160 * s:
		shapes.append(("line", [(x, ground - 95 * s), (x, ground - 15 * s)], dict(width=6 * s, fill="#d8ccb4")))
		shapes.append(("line", [(x - 80 * s, ground - 80 * s), (x + 80 * s, ground - 80 * s)], dict(width=4 * s, fill="#d8ccb4")))
		shapes.append(("line", [(x - 80 * s, ground - 50 * s), (x + 80 * s, ground - 50 * s)], dict(width=4 * s, fill="#d8ccb4")))
		x += 160 * s
	shapes.append(("polygon", [(0, ground), (w, ground), (w, h), (0, h)], dict(fill="#c9d6a3")))
	shapes.append(("line", [(0, ground), (w, ground)], dict(width=2, fill="#8a9a5b")))
	near = distance % (90 * s)
	x = -near
	while x < w + 90 * s:
		for dx, dy in ((0, 0), (37 * s, 22 * s), (61 * s, 9 * s)):
			y = ground + 12 * s + dy
			shapes.append(("line", [(x + dx, y), (x + dx - 4 * s, y - 8 * s)], dict(width=2, fill="#7d8f4e")))
			shapes.append(("line", [(x + dx, y), (x + dx + 4 * s, y - 8 * s)], dict(width=2, fill="#7d8f4e")))
		x += 90 * s
	# Shadow
	shadow = max(0.3, 1. - a["lift"] / 150.)
	shapes.append(("oval", [(cx - 130 * s * shadow, ground - 6 * s), (cx + 130 * s * shadow, ground + 6 * s)], dict(fill="#a9b885", outline="")))

	def draw_leg(name, color):
		top, joint, hoof = (screen(p) for p in leg[name])
		fore = name.startswith("fore")
		shapes.append(("line", [top, joint], dict(width=(17 if fore else 21) * s, fill=color, capstyle="round")))
		shapes.append(("line", [joint, hoof], dict(width=10 * s, fill=color, capstyle="round")))
		shapes.append(("oval", [(joint[0] - 6 * s, joint[1] - 6 * s), (joint[0] + 6 * s, joint[1] + 6 * s)], dict(fill=color, outline="")))
		direction = math.atan2(hoof[1] - joint[1], hoof[0] - joint[0])
		tip = (hoof[0] + 8 * s * math.cos(direction), hoof[1] + 8 * s * math.sin(direction))
		shapes.append(("line", [hoof, tip], dict(width=13 * s, fill=HOOF, capstyle="butt")))
		if tip[1] >= ground - 3 * s and a["pace"] > 1.:  # dust under a hoof on the ground
			for k in (-1, 1):
				shapes.append(("line", [(tip[0], ground), (tip[0] - 10 * s + k * 4 * s, ground - 7 * s)], dict(width=2, fill="#b7a98c")))

	# Far legs, behind the body
	draw_leg("foreright", FAR_COLOR)
	draw_leg("hindright", FAR_COLOR)
	# Tail
	root = screen(RUMP)
	tail = -118 - a["tail"]
	for k, (spread, length) in enumerate(((-8, 78), (0, 92), (8, 80))):
		mid = ray((0, 0), length * 0.5, tail + spread * 0.3 + pitch)
		end = ray((0, 0), length, tail + spread + pitch - 10)
		shapes.append(("line", [root, (root[0] + mid[0] * s, root[1] - mid[1] * s), (root[0] + end[0] * s, root[1] - end[1] * s)],
			dict(width=(9 - abs(spread) * 0.5) * s, fill=DARK, capstyle="round", smooth=True)))
	# Body
	outline = []
	for k in range(40):
		t = 2 * math.pi * k / 40
		x, y = 128 * math.cos(t), 42 * math.sin(t)
		if x > 60 and y < 0:  # chest
			y -= 8 * (x - 60) / 68
		outline.append(screen((x, y)))
	shapes.append(("polygon", outline, dict(fill=BODY_COLOR, outline=DARK, width=2)))
	# Neck and head
	neck_dir = 58 + pitch + a["neck"]
	base = screen(WITHERS)
	poll = ray(WITHERS, 92, neck_dir - pitch)  # in the coordinates of the horse
	top = screen(poll)
	throat = screen(ray(WITHERS, 40, neck_dir - pitch - 90))
	chest_side = screen(ray(SHOULDER, 20, neck_dir - pitch - 80))
	shapes.append(("polygon", [screen(ray(WITHERS, 30, neck_dir - pitch + 140)), base, top,
		screen(ray(poll, 26, neck_dir - pitch - 100)), throat, chest_side], dict(fill=BODY_COLOR, outline=DARK, width=2)))
	head_dir = neck_dir - 118 + a["head"]
	r = math.radians(head_dir)
	hx, hy = math.cos(r), -math.sin(r)  # screen direction of the head
	nx, ny = -hy, hx  # its normal
	m = (top[0] + 62 * s * hx, top[1] + 62 * s * hy)
	shapes.append(("polygon", [(top[0] + 16 * s * nx, top[1] + 16 * s * ny), (m[0] + 9 * s * nx, m[1] + 9 * s * ny),
		(m[0] + 4 * s * hx, m[1] + 4 * s * hy), (m[0] - 9 * s * nx, m[1] - 9 * s * ny), (top[0] - 14 * s * nx, top[1] - 14 * s * ny)],
		dict(fill=BODY_COLOR, outline=DARK, width=2, smooth=True)))
	eye = (top[0] + 14 * s * hx - 4 * s * nx, top[1] + 14 * s * hy - 4 * s * ny)
	shapes.append(("oval", [(eye[0] - 3.5 * s, eye[1] - 3.5 * s), (eye[0] + 3.5 * s, eye[1] + 3.5 * s)], dict(fill=DARK, outline="")))
	nostril = (m[0] - 2 * s * hx - 3 * s * nx, m[1] - 2 * s * hy - 3 * s * ny)
	shapes.append(("oval", [(nostril[0] - 2 * s, nostril[1] - 2 * s), (nostril[0] + 2 * s, nostril[1] + 2 * s)], dict(fill=DARK, outline="")))
	ear = (top[0] - 4 * s * hx - 14 * s * nx, top[1] - 4 * s * hy - 14 * s * ny)  # on the side of the mane
	er = math.radians(neck_dir + 10)
	shapes.append(("polygon", [(ear[0] - 4 * s * hx, ear[1] - 4 * s * hy), (ear[0] + 20 * s * math.cos(er), ear[1] - 20 * s * math.sin(er)),
		(ear[0] + 6 * s * hx, ear[1] + 6 * s * hy)], dict(fill=BODY_COLOR, outline=DARK, width=2)))
	# Mane along the neck
	for k in range(7):
		p = screen(ray(WITHERS, 12 + k * 12, neck_dir - pitch))
		q = (p[0] - 16 * s * math.cos(math.radians(neck_dir - 60)), p[1] + 16 * s * math.sin(math.radians(neck_dir - 60)))
		shapes.append(("line", [p, q], dict(width=5 * s, fill=DARK, capstyle="round")))
	# Near legs, in front of the body
	draw_leg("hindleft", BODY_COLOR)
	draw_leg("foreleft", BODY_COLOR)
	return shapes


class Window:
	def __init__(self, horse, messages, port):
		import tkinter
		self.horse = horse
		self.messages = messages
		self.root = tkinter.Tk()
		self.root.title("%s (OSC port %d)" % (TITLE, port))
		self.canvas = tkinter.Canvas(self.root, width=760, height=520, background="#f4f1ea", highlightthickness=0)
		self.canvas.pack(fill="both", expand=True)
		self.last = time.monotonic()
		self.root.after(15, self.tick)

	def tick(self):
		now = time.monotonic()
		while True:
			try:
				date, address, args = self.messages.get_nowait()
			except queue.Empty:
				break
			self.horse.handle(date, address, args)
		self.horse.update(now, now - self.last)
		self.last = now
		self.draw()
		self.root.after(15, self.tick)

	def draw(self):
		c = self.canvas
		c.delete("all")
		w, h = c.winfo_width(), c.winfo_height()
		a = self.horse.angles()
		for kind, points, options in horse_shapes(a, self.horse.distance, w, h):
			flat = [v for p in points for v in p]
			if kind == "line":
				c.create_line(*flat, **options)
			elif kind == "polygon":
				c.create_polygon(*flat, **options)
			elif kind == "oval":
				c.create_oval(*flat, **options)
		y = 16
		for name in JOINTS:
			unit = "px/s" if name == "pace" else "px" if name == "lift" else "°"
			c.create_text(12, y, anchor="w", text="%-14s %7.1f%s" % (name, a[name], unit), font=("Courier", 11), fill="#3d4a5c")
			y += 16
		c.create_text(w - 12, 16, anchor="e", text=self.horse.status, font=("Helvetica", 13), fill="#3d4a5c")

	def run(self):
		self.root.mainloop()


def main():
	parser = argparse.ArgumentParser(description=TITLE + " played by the Bol Processor in real-time OSC")
	parser.add_argument("--port", type=int, default=7400, help="UDP port (default 7400)")
	parser.add_argument("--host", default="127.0.0.1", help="address to listen to (default 127.0.0.1; 0.0.0.0 for another computer)")
	parser.add_argument("--max-speed", type=float, default=1000., help="maximum speed of the joints in degrees per second (default 1000, 0 = no limit)")
	parser.add_argument("--verbose", action="store_true", help="print the messages received")
	parser.add_argument("--no-window", action="store_true", help="no window: print the messages and the angles every 0.5 s")
	options = parser.parse_args()

	sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	try:
		sock.bind((options.host, options.port))
	except OSError:
		sys.exit("UDP port %d is already used on %s, maybe by another robot. Close its window, or choose another port with --port" % (options.port, options.host))
	messages = queue.Queue()
	threading.Thread(target=receive, args=(sock, messages, options.verbose or options.no_window), daemon=True).start()
	horse = Horse(options.max_speed)
	print("Listening to OSC on %s:%d" % (options.host, options.port), flush=True)
	if not options.no_window:
		try:
			import tkinter
		except ImportError as error:
			sys.exit("This Python has no tkinter for the window (%s). Install it (e.g. 'brew install python-tk' with Homebrew, "
				"'sudo apt install python3-tk' on Linux), use the Python of https://www.python.org, or run with --no-window" % error)
		if sys.platform == "darwin" and tkinter.TkVersion < 8.6:
			# The Python of Apple's Command Line Tools comes with Tk 8.5, whose window hangs on recent versions of MacOS
			sys.exit("This Python uses Tk %s, whose window doesn't work on recent versions of MacOS. Use the Python of "
				"https://www.python.org (e.g. /Library/Frameworks/Python.framework/Versions/Current/bin/python3), "
				"or run with --no-window" % tkinter.TkVersion)
		Window(horse, messages, options.port).run()
		return
	last = shown = time.monotonic()
	try:
		while True:
			time.sleep(0.015)
			now = time.monotonic()
			while not messages.empty():
				date, address, args = messages.get()
				horse.handle(date, address, args)
			horse.update(now, now - last)
			last = now
			if now - shown >= 0.5:
				shown = now
				print("   " + "  ".join("%s %.1f" % (n, j.angle) for n, j in horse.joints.items()), flush=True)
	except KeyboardInterrupt:
		pass


if __name__ == "__main__":
	main()
