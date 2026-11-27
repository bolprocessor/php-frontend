# Writing grammars

A grammar rewrites a starting symbol, `S`, by applying rules until no rule applies. The result is an **item**: a score following scores.md. Theory: https://bolprocessor.org/bol-processor-grammars/ and https://bolprocessor.org/modelling-music-with-grammars/.

## Layout of a grammar file

```
// Comments start with //
-se.tryTimePatterns
RND
_mm(120)
_smooth
gram#1[1] S --> {10,t1 t2,Part1 Part2}
gram#1[2] Part1 --> {t1 t3 t4, C4 D4 E4 F4 - A4}
gram#1[3] Part2 --> {t3 t1, B4 C5 _ E5}
-----
TIMEPATTERNS:
t1=1/1 t2=3/2 t3=4/3 t4=1/2
```

- The first lines name the files the grammar needs, one per line: settings `-se.NAME`, alphabet `-al.NAME`, prototypes `-so.NAME`, tonality `-to.NAME`, instruments `-cs.NAME` or `-sc.NAME`, time base `-tb.NAME`, Python program for real-time OSC `-py.NAME` (files.md).
- The grammar is divided into **subgrammars**, separated by a line of dashes (`-----`). They are applied in order: when no rule of a subgrammar applies any more, the next one starts.
- Each subgrammar starts with its **mode** (`RND`, `ORD`...; `RND` if none), possibly followed by procedures such as `_mm(120)` or `_smooth`.
- Each rule is labelled `gram#i[j]`: rule j of subgrammar i. Write the labels (the console accepts rules without them, but every example has them, and errors are reported with them).
- Sections `TIMEPATTERNS:` and `TEMPLATES:` may follow the last subgrammar.

## Rules

A rule has a left argument, an arrow and a right argument: `A --> {E1, A#1 F#1} {Tr2 D}`.

- **Variables** are the symbols that are rewritten: they start with an uppercase letter (`S`, `Part1`, `Theme32`), or are written between bars (`|my variable|`). Everything else is a terminal: notes, silences, controls, symbols of the alphabet.
- The left argument may contain several symbols: `'1' '1' --> '2' '1'`.
- A variable that no rule rewrites stays in the item, where it is silent: check the items produced (the test script lists possible leftovers).
- An empty right argument is written `lambda` (or `nil`, `empty`, `null`): `D --> lambda` erases D.

| Arrow | Use |
|---|---|
| `-->` | production only (generating items) |
| `<--` | analysis only (parsing items) |
| `<->` | both production and analysis |

Analysis: https://bolprocessor.org/parsing-items/.

## Subgrammar modes

| Mode | How rules are chosen and applied |
|---|---|
| `RND` | a rule is chosen at random among the applicable ones, according to their weights |
| `ORD` | the applicable rules are tried in the order they are written |
| `LIN` | the rule is applied at the leftmost (or rightmost) position where a candidate rule matches |
| `POSLONG` | the leftmost position, and there the longest left argument, wins |
| `SUB` | substitution: all occurrences are rewritten in parallel, repeatedly |
| `SUB1` | substitution in a single pass, often used to replace variables by their values |
| `TEM` | templates, used for analysis |

A rule may start with `LEFT` or `RIGHT`, to rewrite the leftmost or rightmost occurrence of its left argument: `gram#2[1] LEFT /steps-1/ '1' --> '1' '1'`. See https://bolprocessor.org/derivation-modes/ and `-gr.tryDerivationModes`.

**Typical design:** a first `ORD` subgrammar builds the form (`S --> Intro Theme Variation Theme Coda`), an `RND` subgrammar chooses among alternatives (`Variation --> ...` several times), and a last `ORD` or `SUB1` subgrammar replaces the remaining variables by notes. Random choices make each production different: use `--seed` (or the `Seed` setting) to repeat one.

## Rule weights

A weight between angle brackets, after the label, sets the probability of a rule in RND mode:

| Weight | Meaning |
|---|---|
| `<12>` | weight 12, relative to the weights of the other candidate rules |
| `<0>` | the rule is never chosen |
| `<inf>` | infinite weight: the rule is chosen whenever it applies |
| `<100-10>` | decreasing weight: 100, reduced by 10 each time the rule is used |
| `<K1=1>`, `<K1>` | weight given by parameter K1, with an initial value; it can be changed in real time by a MIDI controller |

```
gram#2[1] <3> Figure --> Wave
gram#2[2] <1> Figure --> Clap
```

Weights can be learnt from examples: https://bolprocessor.org/learning-rule-weights/. With `<100-10>` style weights, the setting "Reset rule weights" decides whether they start again at each item.

## Flags

Numeric variables between slashes. On the left of a rule they are conditions; on the right they change values:

```
gram#1[1] S --> A A2 A3 A1 A A /times=5/
gram#1[2] /times > 0/ #A1 #A2 #A3 A A --> #A1 #A2 A A #A3 /times-1/
```

- Conditions: `/flag/` (not zero), `/flag > 0/`, `/flag = 3/`, `/flag ≥ 1/`, also comparisons between flags: `/Num_a > Num_b/`.
- Changes: `/flag=5/` (assignment), `/flag+1/`, `/flag-1/`, several on one rule: `/Num_b + 1/ /Num_a - 1/`.
- A flag counting repetitions is the usual way to stop a recursive rule (`-gr.tryFlags`). See https://bolprocessor.org/flags/.

## Contexts and wildcards

A rule can require, or forbid, symbols around the part it rewrites:

- `#X` is a negative context: the rule applies only if X is **not** found at that place.
- A group between parentheses on the left side is a remote context, which may be found anywhere on that side. Preceded by `#`, it must not be found: `#(?1 ?3 ?2 ?4)`.
- Wildcards `?1`, `?2`... match any single symbol. The same wildcard on the right side copies the symbol it matched:

```
gram#2[2] #(?1 ?3 ?2 ?4) ?1 ?2 ?3 ?4 X1234 --> ?1 ?2 ?3 ?4 New ?1 ?3 ?2 ?4 X23
```

Pattern grammars: https://bolprocessor.org/pattern-grammars/ (`-gr.tryPatternGrammar`).

## Repetitions and homomorphisms

A **master** expression `(= X)` is copied into its **slaves** `(: X)`. Whatever X becomes during the derivation, its slaves become the same:

```
S --> _pitchrange(200) (= Tihai) _transpose(-2) (: Tihai) _pitchcont _pitchbend(-200) _transpose(-2) (: Tihai) _pitchbend(+200)
```

A **homomorphism** is a mapping of terminals declared in the alphabet file under a label, e.g. `*` mapping the open bols of tabla to closed ones (`dha --> ta`). Placed before a master or a slave, the label transforms its copy: `(= A) * (: A)` plays A, then A with dha replaced by ta. This is how the closed sections of qa'ida are modelled in `-gr.dhadhatite` (https://bolprocessor.org/qavaid/).

## Grammar procedures

Written on a rule, or after the mode of a subgrammar:

| Procedure | Effect |
|---|---|
| `_goto(g,r)` | after applying the rule, continue at rule r of subgrammar g |
| `_failed(g,r)` | if the rule can't be applied, continue at rule r of subgrammar g |
| `_repeat(n)` | applies the rule up to n times (or a K parameter: `_repeat(K1)`) |
| `_stop` | stops the derivation |
| `_destru` | removes the structural marks (masters, slaves, + marks) from the item |
| `_randomize`, `_srand(n)` | resets the random sequence, or sets its seed |
| `_mm(x)`, `_striated`, `_smooth` | metronome and nature of time |
| `_print`, `_printOn`, `_printOff`, `_stepOn`, `_stepOff`, `_traceOn`, `_traceOff` | display or trace the derivation, to check a grammar |

See https://bolprocessor.org/grammar-control/.

## Time patterns and templates

`TIMEPATTERNS:` defines durations as ratios, used in smooth time: `t1=1/1 t2=3/2 t3=4/3 t4=1/2`, placed in the first field of a polymetric expression (`{10, t1 t2, Part1 Part2}`). See https://bolprocessor.org/time-patterns-smooth-time/.

`TEMPLATES:` lists the structures of the items a grammar can produce. It is created by the "Produce templates" action and used to analyse items.

## Items produced

- A production gives one item (one line of the output), unless the setting "Non-stop improvize" (`Improvize`) is on: the grammar is then applied again and again, giving a new item each time (different if it has random choices), up to `MaxItemsProduce` items in a file (endlessly in real-time MIDI). Use it for an endless improvisation; leave it off for a piece.
- "Produce all items" (`AllItems`) produces every possible item of a grammar (https://bolprocessor.org/produce-all-items/, `-gr.tryAllItems0`).
- `MaxConsoleTime` (settings, seconds) stops a computation that takes too long: a grammar whose recursion never ends produces nothing within that time.
