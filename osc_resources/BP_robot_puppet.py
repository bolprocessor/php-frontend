#!/usr/bin/env python3
# Bol Processor: a simulated robot puppet played in real-time OSC
# See https://bolprocessor.org/usage-in-robotics/ and ReadMe.txt in this folder.
#
# Run:   python3 BP_robot_puppet.py            (window, listening to UDP port 7400 on this computer)
#        python3 BP_robot_puppet.py --help     (options)
# Then, in the Bol Processor, select the output format "Real-time OSC" (host 127.0.0.1, port 7400)
# and produce "-gr.robot_dance", which names the instruments of "-cs.Robot".
#
# Only the standard library of Python 3 is used (tkinter for the window).
#
# Each joint of the puppet is an instrument: torso, head, leftarm, leftforearm, rightarm, rightforearm.
# A note of instrument X is a movement of joint X: its angle goes from the value of parameter X at the beginning of the note
# (/bp/note ... X start X2 end) to its value at the end, over the duration of the note. A /bp/env message for this note
# gives the intermediate positions (time, value...) when the parameter follows a curve.
# Without the parameter (e.g. a project without "-cs.Robot"), instruments 1 to 6 (bp1...bp6) move the joints in the order above,
# and the angle is given by the key: (key - 60) x 10 degrees, so that C4 = 0, D4 = 20, C5 = 120...
#
# Like a real robot, a joint cannot move faster than --max-speed degrees per second: when a note starts at an angle
# far from the current one, the joint catches up as fast as it can.
# /bp/stop (STOP, PAUSE or PANIC) freezes the puppet where it is.
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

JOINTS = ["torso", "head", "leftarm", "leftforearm", "rightarm", "rightforearm"]
LIMITS = {  # degrees, as a robot would have
	"torso": (-45, 45),
	"head": (-60, 60),
	"leftarm": (-90, 200),  # negative: across the body, e.g. letters O and W of "-da.robot_semaphore"
	"leftforearm": (-30, 160),
	"rightarm": (-90, 200),
	"rightforearm": (-30, 160),
	}


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
			step = max(-max_speed * dt, min(max_speed * dt, step))
		self.angle += step

	def freeze(self, now):
		self.keys = [(now, self.angle)]
		self.note = -1


class Puppet:
	def __init__(self, max_speed):
		self.joints = {name: Joint(name) for name in JOINTS}
		self.max_speed = max_speed
		self.status = "Waiting for the Bol Processor..."
		self.notes = 0

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
		send_to_hardware({name: joint.angle for name, joint in self.joints.items()})


def send_to_hardware(angles):
	# Called about 60 times per second with the angle of each joint in degrees, e.g. angles["leftarm"].
	# Example with servos on a Pololu Maestro or an Arduino reading lines on a serial port (pyserial):
	#   port.write(("%d %d %d %d %d %d\n" % tuple(int(90 + angles[j]) for j in JOINTS)).encode())
	pass


# ---------- Drawing ----------

def point(x, y, length, degrees):
	# Screen coordinates: y goes down, angles are counterclockwise from the right
	r = math.radians(degrees)
	return x + length * math.cos(r), y - length * math.sin(r)


class Window:
	def __init__(self, puppet, messages, port, flags=False):
		import tkinter
		self.puppet = puppet
		self.flags = flags
		self.messages = messages
		self.root = tkinter.Tk()
		self.root.title("Bol Processor robot puppet (OSC port %d)" % port)
		self.canvas = tkinter.Canvas(self.root, width=640, height=600, background="#f4f1ea", highlightthickness=0)
		self.canvas.pack(fill="both", expand=True)
		self.trails = {"left": [], "right": []}
		self.last = time.monotonic()
		self.root.after(15, self.tick)

	def tick(self):
		now = time.monotonic()
		while True:
			try:
				date, address, args = self.messages.get_nowait()
			except queue.Empty:
				break
			self.puppet.handle(date, address, args)
		self.puppet.update(now, now - self.last)
		self.last = now
		self.draw(now)
		self.root.after(15, self.tick)

	def draw(self, now):
		c = self.canvas
		c.delete("all")
		w, h = c.winfo_width(), c.winfo_height()
		s = min(w / 640., h / 600.)
		a = {name: joint.angle for name, joint in self.puppet.joints.items()}
		ground = h - 60 * s
		hips = (w / 2, ground - 150 * s)
		c.create_line(0, ground, w, ground, fill="#b9ad97", width=2)
		for side in (-1, 1):  # legs
			c.create_line(*hips, hips[0] + side * 40 * s, ground, width=10 * s, fill="#3d4a5c", capstyle="round")
		up = 90 + a["torso"]
		neck = point(*hips, 170 * s, up)
		c.create_line(*hips, *neck, width=16 * s, fill="#3d4a5c", capstyle="round")
		head = point(*neck, 45 * s, up + a["head"])
		c.create_oval(head[0] - 32 * s, head[1] - 32 * s, head[0] + 32 * s, head[1] + 32 * s, fill="#e9c46a", outline="#3d4a5c", width=3)
		for side in (-1, 1):  # eyes
			e = point(*head, 12 * s, up + a["head"] + side * 50)
			c.create_oval(e[0] - 4 * s, e[1] - 4 * s, e[0] + 4 * s, e[1] + 4 * s, fill="#3d4a5c", outline="")
		shoulders = {"left": point(*neck, 35 * s, up + 90), "right": point(*neck, 35 * s, up - 90)}
		c.create_line(*shoulders["left"], *shoulders["right"], width=12 * s, fill="#3d4a5c", capstyle="round")
		for side in ("left", "right"):
			shoulder = shoulders[side]
			arm = up - 180 - a[side + "arm"] if side == "left" else up - 180 + a["rightarm"]
			elbow = point(*shoulder, 90 * s, arm)
			fore = arm - a[side + "forearm"] if side == "left" else arm + a["rightforearm"]
			hand = point(*elbow, 80 * s, fore)
			trail = self.trails[side]
			trail.append((now, hand))
			while trail and now - trail[0][0] > 1.5:
				trail.pop(0)
			for (t1, p1), (t2, p2) in zip(trail, trail[1:]):
				fade = int(150 + 100 * (now - t2) / 1.5)
				c.create_line(*p1, *p2, fill="#%02x%02x%02x" % (min(fade, 244), min(fade + 30, 241), min(fade + 60, 234)), width=3)
			c.create_line(*shoulder, *elbow, width=12 * s, fill="#2a9d8f", capstyle="round")
			c.create_line(*elbow, *hand, width=10 * s, fill="#e76f51", capstyle="round")
			if self.flags:  # semaphore flag, red and yellow, on a stick extending the forearm
				tip = point(*hand, 75 * s, fore)
				mid = point(*hand, 35 * s, fore)
				out = 90 if side == "left" else -90  # the flag flies on the outer side of the stick
				corner = point(*tip, 40 * s, fore + out)
				edge = point(*mid, 40 * s, fore + out)
				c.create_polygon(*mid, *tip, *corner, fill="#e63946", outline="")
				c.create_polygon(*mid, *edge, *corner, fill="#ffd60a", outline="")
				c.create_line(*hand, *tip, width=3 * s, fill="#3d4a5c")
			for p in (shoulder, elbow):
				c.create_oval(p[0] - 6 * s, p[1] - 6 * s, p[0] + 6 * s, p[1] + 6 * s, fill="#3d4a5c", outline="")
		y = 16
		for name in JOINTS:
			c.create_text(12, y, anchor="w", text="%-13s %7.1f°" % (name, a[name]), font=("Courier", 12), fill="#3d4a5c")
			y += 18
		c.create_text(w - 12, 16, anchor="e", text=self.puppet.status, font=("Helvetica", 13), fill="#3d4a5c")

	def run(self):
		self.root.mainloop()


def main():
	parser = argparse.ArgumentParser(description="Robot puppet played by the Bol Processor in real-time OSC")
	parser.add_argument("--port", type=int, default=7400, help="UDP port (default 7400)")
	parser.add_argument("--host", default="127.0.0.1", help="address to listen to (default 127.0.0.1; 0.0.0.0 for another computer)")
	parser.add_argument("--max-speed", type=float, default=400., help="maximum speed of the joints in degrees per second (default 400, 0 = no limit)")
	parser.add_argument("--verbose", action="store_true", help="print the messages received")
	parser.add_argument("--flags", action="store_true", help="draw semaphore flags in the hands (for \"-da.robot_semaphore\")")
	parser.add_argument("--no-window", action="store_true", help="no window: print the messages and the angles every 0.5 s")
	options = parser.parse_args()

	sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	sock.bind((options.host, options.port))
	messages = queue.Queue()
	threading.Thread(target=receive, args=(sock, messages, options.verbose or options.no_window), daemon=True).start()
	puppet = Puppet(options.max_speed)
	print("Listening to OSC on %s:%d" % (options.host, options.port), flush=True)
	if not options.no_window:
		try:
			import tkinter
		except ImportError:
			sys.exit("This Python has no tkinter for the window. Install it (e.g. 'brew install python-tk' with Homebrew, "
				"'sudo apt install python3-tk' on Linux), use the Python of https://www.python.org, or run with --no-window")
		if sys.platform == "darwin" and tkinter.TkVersion < 8.6:
			# The Python of Apple's Command Line Tools comes with Tk 8.5, whose window hangs on recent versions of MacOS
			sys.exit("This Python uses Tk %s, whose window doesn't work on recent versions of MacOS. Use the Python of "
				"https://www.python.org (e.g. /Library/Frameworks/Python.framework/Versions/Current/bin/python3), "
				"or run with --no-window" % tkinter.TkVersion)
		Window(puppet, messages, options.port, options.flags).run()
		return
	last = shown = time.monotonic()
	try:
		while True:
			time.sleep(0.015)
			now = time.monotonic()
			while not messages.empty():
				date, address, args = messages.get()
				puppet.handle(date, address, args)
			puppet.update(now, now - last)
			last = now
			if now - shown >= 0.5:
				shown = now
				print("   " + "  ".join("%s %.1f" % (n, j.angle) for n, j in puppet.joints.items()), flush=True)
	except KeyboardInterrupt:
		pass


if __name__ == "__main__":
	main()
