# Brag Plan: Smart Cashier Queuing System (Batangas Eastern Colleges)

## What is this app?
A real, deployed queuing system for the Batangas Eastern Colleges cashier — front desk
issues a prefixed ticket with a QR code, a TV board calls numbers out loud, and the
student watches their position and ETA live on their phone. Built as a capstone, and the
last two commits are literally `THESIS DEFENDED`.

## The angle
This is not an absurd joke project, so the video must not be a parody. The brag is that a
student capstone is a **complete operational system**: three roles, five service lanes, a
thermal printer, a talking TV board, and fairness logic thoughtful enough that tickets are
deliberately *not* pre-assigned to a window so nobody gets skipped because a cashier is
still busy. The angle: *follow one ticket, T-014, all the way through.* One number, three
screens, twenty seconds. The video earns its specificity by showing the actual
maroon-and-gold BEC interface, the real service prefixes, and the real copy.

## Hook (first 2-3 seconds)
The project's own headline, in its own maroon, at full scale on white:
**"Less waiting. More serving. Smarter queues."**
Then the quiet setup line underneath — *Batangas Eastern Colleges* — so the viewer knows in
two seconds this is a real institution, not a demo. No logo yet. The headline is the
strongest copy the project has; it does the work.

## Key moments (the middle)
- **The ticket is born.** Front desk picks `Tuition Payment` from the real category list,
  types a client name, hits Create Queue — and `T-014` appears with its QR code beside
  "Scan for status updates". The prefix is real (`T` = Tuition, `C` = Clearance,
  `E` = Enrollment, `I` = Inquiries, `O` = Others).
- **The board calls it.** The maroon TV header with the live clock, three window cards, and
  `T-014` slamming into the Window 2 card as NOW SERVING — colored by priority tier, with
  the Up Next panel and the real legend (Senior Citizen / Student / Parent-Visitor) beside
  it. Then the line the system actually speaks aloud:
  *"Now serving, Queue number T-014. Please proceed to Window 2."*
- **The phone already knows.** The QR-scanned tracker on a phone frame: `3rd` position,
  `~9 min` estimated wait, `2 waiting + 1 currently serving`, updating every 2 seconds.
  This is the payoff — the student never had to stand in the hallway.

## Outro / punchline
The system title and the school, then the one line that only this project gets to say. Not
a tagline — a status. `Capstone project. Defended.` The brag is that it shipped.

## User flow worth showing
Three beats, and they are the centerpiece of the video:
1. **Entry** — front desk selects a service category and creates a queue, producing `T-014`
   plus its QR code.
2. **Key action** — the cashier calls next; the TV board flips `T-014` to NOW SERVING at
   Window 2 and announces it out loud.
3. **Result** — the student's phone, opened from the QR, shows position `3rd` and `~9 min`,
   refreshing on its own.

The admin module, reports, chatbot, and monitoring page exist but stay out — they dilute the
single-ticket throughline.

## Tone
- Preset: `polished`
- Creative direction: a quiet institutional product film — the calm confidence of software
  that is already running at a real school counter.
- Interpretation: restraint is the flex. Type is medium-weight Figtree with generous
  tracking, motion is short and settled (0.35-0.5s in, then a long hold), transitions are
  soft 0.5s crossfades. No zooms, no flashes, no caps-lock. Five scenes instead of the
  preset's 3-4, because the three-screen flow is the whole point and each screen needs its
  own hold — but each scene stays slow and uncluttered to keep the polished posture.

## Format: landscape — 1920x1080
## Duration: 23s

## Visual identity (from the project)
- Background: `#ffffff` (the board and landing are white-first)
- Primary brand / header: `#800000` (maroon), pressed state `#600000`
- Accent: `#FFC107` (gold), hover `#FFB300`, pale gold fill `#fff4cc`
- Text: `#4b5563` (gray-600) for body, `#800000` for headings, `#ffffff` on maroon
- Subtitle-on-maroon: `#fef08a` (yellow-200) — used for "Batangas Eastern Colleges" and
  "Current Time" on the real board
- Priority tiers: `#1d4ed8` blue-700 (Senior Citizen / High Priority), `#800000` maroon
  (Student), `#ea580c` orange-600 (Parent / Visitor)
- Card borders: `4px solid #800000` for window cards, `4px solid #FFC107` for Up Next,
  `#e8d0d0` hairline for list rows
- Display font: Figtree 600 in the app (loaded from fonts.bunny.net). **The video uses a
  system sans stack instead** — render-time font fetches are non-deterministic and a named
  family without a shipped `@font-face` fails Hyperframes lint. Figtree's character is
  approximated with weight and letter-spacing.
- Body font: same system sans stack at 400/500
- Strongest visual element: the Display Board — maroon header with the live clock, three
  white window cards with 4px maroon borders, and a giant queue number on a colored fill.

## Share copy (draft)
Built a smart cashier queuing system for Batangas Eastern Colleges — QR tickets, a talking
TV board, live wait times, and silent thermal receipt printing. Capstone defended.

## Audio direction
- Role: warm, low, steady bed with sparse professional accents. Present but never in front
  of the visuals.
- Music: `happy-beats-business-moves-vol-12-by-ende-dot-app.mp3` — steady and clean, the
  documented `polished` pick.
- Music treatment: start at 0s, volume 0.30, 0.4s fade-in, fade out over the last 1.2s of
  the outro. No ducking gymnastics.
- Music cue guidance: preset read from
  `assets/music/cues/happy-beats-business-moves-vol-12-by-ende-dot-app.music-cues.json`
  (109.96 BPM, ~0.545s beat). Three strong-cue locks for the three biggest moments:
  **8.74s** (the `T-014` ticket + QR landing — see the as-built note; the ticket actually
  needed to land at the 6.00s beat to get its 3s hold), **17.47s** (the phone tracker
  completing), **19.66s** (the system title arriving in the outro), with the final line
  resolving near **22.37s**. Sequential reveals use the beat grid at *every other* beat
  (~1.09s apart) so text clears the reading floor — the three window cards in Scene 3 at
  9.83 / 10.93 / 12.02, and the phone's two stat tiles in Scene 4 at 16.38 / 17.47.
  **As built (readability wins over the cue sheet):** the Scene 3 cards land on three
  consecutive beats (9.83 / 10.37 / 10.93, the last on the 0.97 strong cue) and then hold
  for the rest of the scene, which frees 12.02 -> 15.0 (2.98s) for the 10-word spoken-
  announcement line. In Scene 4 the tracker fills top-to-bottom — position 16.38, estimated
  wait 16.93, queues-ahead block on the 17.47 strong cue — so the phone screen completes on
  the strongest beat instead of holding a visibly reserved gap for 1.6s. The outro's final
  line moved from 22.37 to the 21.28 beat so its three words hold 1.72s rather than 0.63s.
  Strong-cue locks as shipped: **10.93**, **17.47**, **19.66**.
- Audio-reactive treatment: subtle; let music RMS give the maroon header and the phone frame
  a barely-there presence swell, and the outro title a soft glow on the strong cue. No
  waveform, no equalizer bars, no text scaling.
- SFX posture: sparse. **As shipped: 7 clips** across 23s (button click, ticket landing,
  first + last window card, NOW SERVING flip, tracker completion, outro bell), volumes
  0.34-0.64, never more than one in flight. Motion-matched only.
- Audio-coupled moments: the Create Queue button press (simulated click), the ticket + QR
  landing (soft reveal), the three window cards arriving one by one (gentle drops, accent the
  first and last only), `T-014` flipping to NOW SERVING (one soft announcement hit), the
  outro title (one restrained bell).
- Restraint rule: no sound during the spoken-announcement caption in Scene 3 other than the
  bed — that line must read in silence. Nothing percussive, nothing bright, nothing above
  0.70. Never more than one SFX in flight.

## Storyboard

### Scene 1 — Hook: the headline — 4.2s
White field. `Less waiting. More serving. Smarter queues.` in Figtree 600 maroon `#800000`,
centered, large. Rises 16px and fades in over 0.5s, then holds. At 1.6s, a thin gold
`#FFC107` rule draws out beneath it (0.4s) and `Batangas Eastern Colleges` fades up in
gray-600 under it. Both hold together until the cut. Reading budget: the six-word headline
gets ~2.6s settled, the subtitle ~2.0s — both well past the floor.
Sequential/interaction: none — two elements, staged, then still.
Audio intent: the bed simply begins. Nothing announces itself.
Audio-coupled idea: none. Let the music entrance carry it.
Music: steady, low, fading in from 0.
Transition mood: soft 0.5s crossfade → Scene 2

### Scene 2 — Front desk: the ticket is born — 5.0s
The Create Queue card, white on a pale field, recreated from the real form. The Service
Category select shows `Tuition Payment` chosen from the real five, Client Name reads a
plausible student name. At 0.8s a cursor moves to the maroon `Create Queue` button and
presses it (button depresses 2px). Then the card swaps to the success state — green check,
`Queue Created!`, and `T-014` at display scale, with the QR block and `Scan for status
updates` beneath it. The ticket state must be fully settled and held for ~2.4s; the number
is the thing to read.
Sequential/interaction: yes — simulate the cursor press on `Create Queue`, then the form to
ticket swap. The QR and the `T-014` number land together, not staggered.
Audio intent: one small, precise interaction sound, then a soft landing. Competence, not
celebration.
Audio-coupled idea: simulated click on the button (`interface/click_*`), then a gentle reveal
drop when `T-014` + QR land, locked to the **8.74s** strong cue.
Music: unchanged bed.
Transition mood: soft 0.5s crossfade → Scene 3

### Scene 3 — The board calls it — 5.0s
The Display Board, the hero visual. Maroon `#800000` header: school logo, `Smart Cashier
Queuing System`, `Batangas Eastern Colleges` in yellow-200, and the live clock at the right
ticking real seconds. Below on white: three window cards, 4px maroon borders, arriving one by
one. Window 1 and Window 3 carry other numbers; **Window 2's NOW SERVING fill flips from
gold to maroon and `T-014` slams into it in white** — the priority-tier color for Student.
The gold-bordered `Up Next` panel sits to the right with the real legend beneath the heading.
At ~3.3s a single restrained caption line appears low over the board, in the board's own
voice: `Now serving, Queue number T-014. Please proceed to Window 2.` — the exact string the
system speaks aloud. It holds ~1.6s in near-silence.
Sequential/interaction: yes — the three window cards arrive one by one on every-other-beat
(9.83 / 10.93 / 12.02), then `T-014` flips into Window 2 as a single decisive change.
Audio intent: the one moment the video allows itself a little weight. Then it gets quiet for
the spoken line, so the words land.
Audio-coupled idea: soft drops on the first and last window card only; one soft announcement
hit as `T-014` flips in, near the **13.11s** cue. Nothing under the caption.
Music: bed continues, unducked but sitting low.
Transition mood: soft 0.5s crossfade → Scene 4

### Scene 4 — The phone already knows — 4.4s
A clean phone frame, centered, showing the QR-opened Queue Status Tracker: the maroon header
with `Queue Status Tracker` and `Live updates every 2 seconds`, then the gold-filled `Your
Queue Number` block with `T-014`. Two stat tiles settle in beneath it — `Position 3rd` and
`Estimated Wait ~9 min` — followed by the small real sub-line `2 waiting + 1 currently
serving`. Each tile holds ~1.4s settled. A quiet detail: the `~9 min` value ticks once to
`~8 min` before the cut, showing it is live rather than painted.
Sequential/interaction: yes — position at 16.38, estimated wait at 16.93, then the
queues-ahead block on the 17.47 strong cue completes the screen; the ETA value then ticks
down once on its own. Both stat labels are two words and hold >2.4s.
Audio intent: light and reassuring. The smallest sounds in the video.
Audio-coupled idea: one soft drop per stat tile, the second locked to the **17.47s** strong
cue; nothing on the ETA tick.
Music: bed lifts very slightly into the outro.
Transition mood: soft 0.5s crossfade → Scene 5

### Scene 5 — Outro — 4.4s
Maroon `#800000` full field. The school logo fades in small and centered, then `Smart Cashier
Queuing System` in white Figtree 600 arrives on the **19.66s** cue, with `Batangas Eastern
Colleges` in yellow-200 beneath it. After a beat, a thin gold rule draws (20.85s) and one
last line settles on the **21.28s** beat in gold `#FFC107`: `Capstone project. Defended.`
Everything holds
still for the final ~0.8s while the bed fades. This frame is the poster.
Sequential/interaction: none — three staged arrivals, then stillness.
Audio intent: one restrained resolution, then let go.
Audio-coupled idea: a single soft bell as the title arrives on the cue; nothing on the last
line — the silence around it is the point.
Music: fade out over the final 1.2s.
Transition mood: hold to end.

**Scene durations:** 4.2 + 5.0 + 5.0 + 4.4 + 4.4 = **23.0s**

**Music mood for this video:** polished — steady, clean, institutional warmth.
**Audio summary:** A low steady bed runs the whole 23s under five sparse, motion-matched
cues — a button click, a ticket landing, a board announcement, two soft stat drops, and one
closing bell — with a deliberate pocket of near-silence under the spoken-announcement line
and a 1.2s fade to nothing at the end.
