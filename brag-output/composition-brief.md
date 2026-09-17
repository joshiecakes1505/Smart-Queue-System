# Hyperframes Composition Brief: Smart Cashier Queuing System (Batangas Eastern Colleges)

## Objective
Create a short launch-style brag video for the Smart Cashier Queuing System — a capstone
queuing platform that is actually running at the Batangas Eastern Colleges cashier. Follow
one ticket, `T-014`, from the front desk through the TV board to the student's phone.

## Output
- Composition directory: `brag-output/composition/`
- Rendered video: `brag-output/brag.mp4`
- Format: landscape — 1920x1080
- Duration: 23 seconds

## Source Material
- Project root: `C:\projects\smart-queue-system`
- Primary files read:
  - `resources/js/Pages/Landing.vue` — headline, subtitle, button colors
  - `resources/js/Pages/Display/Board.vue` — the TV board (hero visual), priority legend, clock
  - `resources/js/Pages/FrontDesk/CreateQueue.vue` — the create-queue form and ticket/QR success state
  - `resources/js/Pages/Public/QueueStatus.vue` — the QR-opened phone tracker
  - `resources/js/Services/QueueAnnouncement.js` — the exact spoken announcement string
  - `app/Services/QueueService.php` — queue-number format (`<prefix>-<3 digits>`)
  - `database/seeders/SampleDataSeeder.php` — real service categories, prefixes, window names
  - `README.md` — feature list, module names, stack
  - `public/images/school-logo.png` — the real school logo (available to reference)
- Product name: Smart Cashier Queuing System
- Tagline / strongest claim: **"Less waiting. More serving. Smarter queues."**
- Key UI or visual moment to recreate: the **Display Board** — maroon `#800000` header with
  the school logo, system title, `Batangas Eastern Colleges` in yellow-200, and a live clock
  at the right; below it three white window cards with `4px` maroon borders, each with a
  `NOW SERVING` label over a large queue number on a colored fill; a gold-bordered `Up Next`
  panel on the right with the three-tier priority legend.

- Copy that must appear verbatim:
  - `Less waiting. More serving. Smarter queues.`
  - `Batangas Eastern Colleges`
  - `Create New Queue`
  - `Tuition Payment`
  - `Create Queue`
  - `Queue Created!`
  - `T-014`
  - `Scan for status updates`
  - `Smart Cashier Queuing System`
  - `Now Serving`
  - `NOW SERVING`
  - `Up Next`
  - `Window 1` / `Window 2` / `Window 3`
  - `Senior Citizen / High Priority` · `Student` · `Parent / Visitor`
  - `Now serving, Queue number T-014. Please proceed to Window 2.`
  - `Queue Status Tracker`
  - `Live updates every 2 seconds`
  - `Your Queue Number`
  - `Position` / `3rd`
  - `Estimated Wait` / `~9 min`
  - `2 waiting + 1 currently serving`
  - `Capstone project. Defended.`

## Creative Direction
- Tone preset: `polished`
- Creative direction: a quiet institutional product film — the calm confidence of software
  that is already running at a real school counter.
- Interpretation: restraint is the flex. Medium-weight type with generous tracking, short
  settled motion (0.35-0.5s in, then a long hold), soft 0.5s crossfades between scenes. No
  zooms, no flashes, no all-caps headlines, no parody. The product is real; play it straight.
- Angle: This is not an absurd joke project, so the video must not be a parody. The brag is
  that a student capstone is a complete operational system — three roles, five service lanes,
  a thermal printer, a talking TV board, and fairness logic thoughtful enough that tickets are
  deliberately not pre-assigned to a window so nobody gets skipped because a cashier is still
  busy. Follow one ticket, `T-014`, all the way through: one number, three screens, twenty
  seconds. Specificity comes from the actual maroon-and-gold BEC interface, the real service
  prefixes, and the real copy.
- Hook: the project's own headline in its own maroon on white — `Less waiting. More serving.
  Smarter queues.` — then a thin gold rule and `Batangas Eastern Colleges` beneath it.
- Outro / punchline: the system title and school on maroon, then `Capstone project. Defended.`
  in gold. A status, not a tagline.
- Avoid:
  - Generic SaaS language ("streamline your workflow", "supercharge", "next-generation")
  - Abstract filler visuals — no color washes, floating orbs, particles, or generic gradients
  - Unrelated visual redesign — do not invent a new brand; use the project's maroon/gold
  - Parody framing, startup-launch framing, or fake metrics
  - Equalizer bars, waveforms, or any visualizer graphic

## Visual Identity
- Background: `#ffffff` (the board and landing are white-first)
- Text: `#4b5563` (gray-600) body, `#800000` headings, `#ffffff` on maroon,
  `#fef08a` (yellow-200) for subtitles on maroon
- Accent: `#FFC107` (gold); `#FFB300` hover, `#fff4cc` pale gold fill, `#600000` deep maroon
- Priority tiers: `#1d4ed8` (Senior Citizen / High Priority), `#800000` (Student),
  `#ea580c` (Parent / Visitor)
- Display font: Figtree 600 — the project loads it from fonts.bunny.net. Network fetches are
  banned at render time, so either ship a local `@font-face` file or use a system sans stack.
  **Decision: use a system sans stack** (`system-ui, -apple-system, "Segoe UI", sans-serif`)
  with no named `font-family` that lacks an `@font-face` — this avoids the
  `font_family_without_font_face` lint error and renders deterministically. Figtree's
  character is approximated with weight and letter-spacing, not substituted by download.
- Body font: same system sans stack at 400/500.
- Visual references from the project:
  - Board window card: white, `4px solid #800000`, rounded, maroon title bar at top,
    `NOW SERVING` label, giant number on a `#FFC107` fill when idle / tier-colored fill when
    called, then primary + secondary status lines beneath.
  - `Up Next` panel: white, `4px solid #FFC107`, uppercase `Up Next` label, big tier-colored
    number, service category, client type.
  - Create-queue success card: white, shadow, green check in a `#dcfce7` circle,
    `Queue Created!` in green, queue number in a pale blue block, QR image in a gray block.
  - Phone tracker: maroon header card, gold-bordered `#fff4cc` queue-number block, 2-up
    gray stat tiles with tiny gray labels and maroon values.

## Storyboard
Use the storyboard in `brag-output/brag-plan.md` as the creative contract.

Scene summary:
1. **Hook: the headline** — 4.2s — read `Less waiting. More serving. Smarter queues.` in
   maroon on white, then a gold rule draws and `Batangas Eastern Colleges` appears beneath.
2. **Front desk: the ticket is born** — 5.0s — see the `Create New Queue` form with
   `Tuition Payment` selected, a cursor press the maroon `Create Queue` button, then the card
   becomes `Queue Created!` with `T-014` at display scale and the QR block reading
   `Scan for status updates`.
3. **The board calls it** — 5.0s — see the full Display Board: maroon header with logo, title,
   school and live clock; three window cards arriving one by one; `T-014` flipping into
   Window 2's `NOW SERVING` fill; the `Up Next` panel and the three-tier legend. Then read the
   caption `Now serving, Queue number T-014. Please proceed to Window 2.`
4. **The phone already knows** — 4.4s — see the phone tracker: `Queue Status Tracker`,
   `Live updates every 2 seconds`, `T-014` in the gold block, then `Position 3rd` and
   `Estimated Wait ~9 min` settling in, with `2 waiting + 1 currently serving` beneath; the
   ETA ticks `~9 min` → `~8 min` once before the cut.
5. **Outro** — 4.4s — maroon field, school logo, `Smart Cashier Queuing System`,
   `Batangas Eastern Colleges`, a gold rule, then `Capstone project. Defended.` Hold still.

Reading-time floor to respect: short labels ≥0.8s fully settled; sentences ≥0.3s/word. The
hook headline gets ~2.6s settled, the spoken-announcement caption ~1.6s, each phone stat tile
~1.4s. Entrances stay snappy (0.35-0.5s) and then hold — never fast-in, fast-out.

## Audio
- Audio role: warm bed with sparse professional accents. Present, never in front.
- Audio arc: bed fades in over 0.4s and runs the whole 23s at 0.30; one small interaction
  sound at the button press; a soft landing when the ticket appears; two gentle card drops
  and one soft announcement hit on the board; a deliberate near-silent pocket under the
  spoken-announcement caption; two very light stat drops on the phone; one restrained bell on
  the outro title; music fades out over the final 1.2s.
- Music: `happy-beats-business-moves-vol-12-by-ende-dot-app.mp3` (the documented `polished`
  pick — steady and clean, 117.36s, 109.96 BPM)
- Music treatment: `data-volume` 0.30, tween `volume` up from 0 over the first 0.4s and down
  to 0 across the last 1.2s. No ducking gymnastics; the bed simply sits under everything.
- Music cue guidance: bundled preset —
  `brag-output/composition/assets/music/cues/happy-beats-business-moves-vol-12-by-ende-dot-app.music-cues.json`
  (copy it alongside the track). 109.96 BPM, beat ≈ 0.545s.
  - Strong-cue locks (3, within ±0.15s): **8.74s** ticket + QR landing, **17.47s** phone
    position/ETA row settled, **19.66s** outro title arrival. Mark each `// beat-locked`.
  - Also available near the end: **22.37s** / **22.93s** for the final `Capstone project.
    Defended.` line.
  - Beat-grid (±0.10s), every *other* beat so text clears the reading floor: board window
    cards at **9.83 / 10.93 / 12.02**; phone stat tiles at **16.38 / 17.47**. Mark
    `// beat-grid`.
  - Ignore any cue that would rush a line off screen before it can be read.
- Audio-reactive treatment: subtle. Extract per-frame audio data with the
  `hyperframes-creative` audio-reactive workflow (that skill owns its extraction helper —
  let it locate its own script; Python 3.14 and `uv` are available on this machine). Wire
  overall amplitude / bass to at most: (a) a barely-there presence swell on the maroon board
  header and the phone frame shadow, and (b) a soft glow on the outro title around its strong
  cue. Keep text scale variation at or under ~3% so nothing hurts readability. If extraction
  fails, document it and skip audio-reactive rather than blocking the render.
  **No waveform, equalizer, spectrum, particles, or musical-note graphics.**
- Audio-coupled moments:
  - Scene 2, cursor press on `Create Queue` — simulated interaction (a real cursor moves in
    and the button depresses 2px); sound lands with the press.
  - Scene 2, `T-014` + QR landing — soft reveal, beat-locked to 8.74s.
  - Scene 3, three window cards — sequential reveal on the beat grid; accent the **first and
    last only**, not all three.
  - Scene 3, `T-014` flips to `NOW SERVING` — one soft announcement hit near 13.11s.
  - Scene 3, spoken-announcement caption — **no SFX**; bed only, so the line reads in quiet.
  - Scene 4, two stat tiles — one soft drop each, second locked to 17.47s; nothing on the ETA
    tick.
  - Scene 5, outro title — one restrained bell on 19.66s; nothing under the final line.
- SFX selection guidance: 5 cues total is the target; never more than one in flight. Match the
  gesture — a click for the button press, a soft drop for cards and tiles, a soft/bell impact
  for the two payoffs. `interface/click_*`, `interface/drop_00*`, `interface/bong_001`,
  `impact/impactSoft_medium_*`, `impact/impactBell_heavy_000` are all appropriate families for
  `polished`. Volumes 0.55-0.70. Nothing percussive, bright, or aggressive.
- SFX analysis guidance: read
  `C:\Users\PMA-USER\.claude\skills\brag\assets\sfx\sfx-analysis.md` before choosing files and
  prefer **low or medium high-frequency risk** entries — this is a polished tone with repeated
  drop sounds, so avoid the HF-risky picks entirely.
- Exact SFX choice: Hyperframes chooses filenames, timestamps, density, and volume based on
  the implemented animation, after the visual motion exists.
- Audio files: copy the chosen music, its cue JSON, and any selected SFX into
  `brag-output/composition/assets/` and reference them with paths relative to
  `composition/` (e.g. `assets/music/...`, `assets/sfx/interface/...`). Never absolute paths.

## Hyperframes Instructions
Load the composition-building Hyperframes domain skills — `hyperframes-core` (composition
contract + `data-*` timing), `hyperframes-animation` (motion), `hyperframes-creative` (design
spec, beats, audio-reactive), `hyperframes-keyframes` (seek-safe keyframes), and
`hyperframes-cli` (lint/check/render). `/brag` is its own workflow: do not enter the
`hyperframes` entry-point intent interview and do not route into its generic promo /
launch-video workflow. Prefer native Hyperframes conventions over anything in `/brag`.

Requirements:
- Show at least one real UI, copy, or visual element from the source project. (Three screens
  are recreated here; the Display Board is the hero.)
- Keep all text readable in the final render — respect the reading-time floor above.
- Keep the video within 15-25 seconds. Target 23s.
- Include the planned music/SFX layer.
- Treat `/brag` audio notes as guidance, not a fixed cue sheet. Choose SFX after the visual
  animation exists.
- Treat music cue metadata as optional timing hints; ignore cues that hurt readability,
  pacing, or the product story. Use only the 3 strong-cue locks listed above.
- Honor the music fade-in / fade-out treatment.
- Consider the audio-reactive workflow as scoped above (subtle, two or three targets max).
- Use local assets for audio and any runtime dependency. Pin GSAP locally if a CDN fetch at
  render time would be non-deterministic; otherwise use the version the current Hyperframes
  scaffold ships with.
- Environment notes for this machine:
  - FFmpeg/FFprobe are **not** installed system-wide. They are staged at
    `C:\Users\PMA-USER\AppData\Local\Temp\claude\c--projects-smart-queue-system\7c4c5ab2-703f-40b0-a91c-a886a2f98367\scratchpad\bin`
    and must be prepended to `PATH` for `check`, `beats`, and `render`.
  - Available memory is low (~0.8 GB free of 7.7 GB). Render with reduced worker count and be
    prepared to retry at lower quality if the encode is killed.
- Run `npx hyperframes check` before render — it is `/brag`'s single gate (0 findings).
