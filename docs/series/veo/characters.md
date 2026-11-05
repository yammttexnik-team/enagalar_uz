# ENAGA KERAK! — Veo 3 Character Bible & World Bible

> Purpose: fixed English paragraphs to paste **verbatim** into every Veo 3 prompt so faces, clothes, props and rooms stay consistent across clips and episodes. Do not paraphrase them between clips — Veo drifts when wording changes.
>
> Pipeline reminder: Veo 3 → 8-second clips, 9:16 where available (Flow / Gemini app; otherwise 16:9 composed center-safe and cropped in edit). All speech is added later (ElevenLabs, Uzbek) + burned-in subtitles. Every prompt ends with the AUDIO/NO-TEXT clause below.

---

## 0. How to use this bible in Google Flow (do this once, before clip generation)

1. **Reference stills first.** In Flow, generate 3 stills per recurring character (front portrait, 3/4 portrait, full body standing in the kitchen) using the exact character paragraph + the STYLE block. Pick the best set, save them to the project. Reuse them as *Ingredients* (image references) for every clip of that character. This matters more than any prompt wording.
2. **Frames to Video for continuity.** For consecutive clips in the same room (e.g. kitchen C03→C07), take the last frame of an approved clip as the *start frame* of the next one when the camera position is the same.
3. **Two takes minimum per clip.** Veo 3 sometimes invents speech or captions even when told not to. Keep the take with no on-screen text; if speech was generated, mute the clip's audio and use the ambience bed from `ep01_edit.md`.
4. **Never show readable screens.** Every phone in frame uses the PHONE PLATE clause (blank glowing screen). Real UI (Telegram, Click, enagalar.uz) is always a separate screen recording we produce ourselves (`INSERT`).
5. **Aspect ratio.** Choose 9:16. If only 16:9 is offered, keep the subject centered and add "composed for a vertical center crop" — the prompts below already say this.

---

## 1. Shared blocks (paste verbatim)

### STYLE (every prompt starts with this)
```
Realistic Uzbek family web-series, cinematic but naturalistic, like a high-quality Instagram mini-drama shot in Tashkent. Handheld camera with subtle natural shake, 35mm look, shallow depth of field, warm natural light, true Central Asian skin tones, soft contrast, faint film grain, muted warm-amber and soft-teal color grade. Vertical 9:16 composition, subject centered, clean uncluttered lower third of the frame.
```

### PHONE PLATE (whenever a phone screen is visible)
```
The phone screen shows only a plain soft glowing light-blue blank screen, slightly out of focus, with no readable content, no icons, no text.
```

### AUDIO / NO-TEXT CLAUSE (every prompt ends with this; replace the bracket)
```
Audio: natural room ambience only — [specific sounds]. No spoken dialogue, no voice-over, no music, no subtitles, no captions, no on-screen text, no logos, no watermarks.
```

### LIP-MOVEMENT RULE
- When a character "speaks" in a shot, write: `speaks a short calm sentence with natural, understated mouth movement` — never ask Veo for Uzbek speech.
- Prefer putting our Uzbek VO over **listener reaction shots, 3/4 profiles, over-shoulder and back-of-head framings**; full-frontal close-ups of a speaking mouth are reserved for the one or two lines per scene where the character's face is the point (e.g. Mahbuba's "Begona ayol…").

---

## 2. Characters

### 2.1 NILUFAR (29) — the mother, lead
**Base paragraph (verbatim):**
```
Nilufar: Uzbek woman, 29, from Tashkent, Central Asian features, light-olive skin, oval face, dark almond-shaped eyes, straight dark eyebrows, a small beauty mark above the left eyebrow, long straight black hair, slim build about 165 cm, light natural makeup, tiny gold stud earrings, no headscarf, a tired but warm expressive face. Her phone is a black smartphone in a dark-green matte case.
```
**Outfit variants — Episode 1:**
```
EP1 OFFICE: She wears a beige single-button blazer over a white blouse, black straight trousers, a white office badge on a blue lanyard, a tan leather tote bag on her shoulder; hair in a low ponytail.
```
```
EP1 HOME (kitchen dinner): She wears an oversized light-grey knit cardigan over a white t-shirt and dark leggings; hair in a loose messy bun with strands falling out.
```
```
EP1 NIGHT (bedroom): She wears a pale-blue long-sleeve cotton pajama top; hair down and loose; no makeup; face lit only by the phone screen.
```
**Outfits in later episodes (for continuity, one new top per episode):**
| Ep | Outfit |
|---|---|
| 2 | Day: mustard-yellow long cardigan over white t-shirt. Night (sofa): same pale-blue pajama top. |
| 3 | Dark-green long-sleeve knit dress, hair in low ponytail (she goes to work in the afternoon). |
| 4 | Morning: pale-blue pajama top (06:40); day: cream turtleneck, hair down. |
| 5 | Morning (yard bench): denim jacket over white t-shirt. Monday (first work day): EP1 OFFICE outfit again (beige blazer, badge, tote). |

**Props:** black phone in dark-green case (always the same), tan tote bag, office badge, a glass of water on the night table.
**Acting notes for prompts:** moves fast, keeps phone in hand, bites lower lip when anxious, rubs eyes at night.

---

### 2.2 MAHBUBA OPA (58) — the mother-in-law (qaynona)
**Base paragraph (verbatim):**
```
Mahbuba opa: Uzbek woman, 58, a retired primary-school teacher, Central Asian features, round soft face with warm brown skin and fine wrinkles, sharp dark eyes behind thin gold-rimmed reading glasses, grey-streaked black hair fully tucked under a house headscarf tied at the nape of the neck, stout build, medium height, small gold hoop earrings, upright proud posture.
```
**Outfit — Episode 1:**
```
EP1: Burgundy cotton house headscarf with small white flowers; a dark teal-green ankle-length house dress with a tiny paisley print, a grey knitted sleeveless vest over it, a cream apron with faded flowers, soft brown house slippers.
```
**Outfits in later episodes:**
| Ep | Headscarf | Dress |
|---|---|---|
| 2 | Navy with small white dots | Plum-colored house dress, same grey vest |
| 3 | Dark green plain | Brown house dress with cream pattern; holds a handwritten paper list; glasses on a cord |
| 4 | Burgundy (as Ep1) | Dark grey dress, grey wool shawl over shoulders (night scene) |
| 5 | Cream with light pattern | Dark teal dress (as Ep1) + a soft wool shawl on shoulders for the yard; house slippers outside |

**Props:** blue-and-white "pakhta" (cotton-boll) teapot and piyola bowls, turquoise Rishton lagan with plov, kapgir (ladle), reading glasses, in Ep3 a paper list and a plain white medicine box (no brand).
**Acting notes:** stops mid-motion when displeased, lowers glasses to inspect, speaks with the whole hand, never rushes.

---

### 2.3 GULNORA OPA (42) — the nanny (first appears Ep3; bible fixed now)
**Base paragraph (verbatim):**
```
Gulnora opa: Uzbek woman, 42, from Fergana, Central Asian features, calm oval face with high cheekbones, sun-touched tan skin, steady dark eyes with faint lines, no makeup, hair fully covered by a neatly tied light grey-beige cotton headscarf with no strands visible, medium build, modest and composed posture, quiet attentive expression.
```
**Outfits:**
| Ep | Outfit |
|---|---|
| 3 | Navy ankle-length modest dress with a tiny white floral print, dark-grey cardigan, flat black shoes, a large brown faux-leather tote bag (THE bag) worn on the shoulder. |
| 4 | Dark-grey modest dress, same dark-grey cardigan, same brown tote bag; morning on the yard bench. |
| 5 | Dark-green modest dress, same cardigan and bag. |

**Props:** the large brown tote bag (always identical), a plain white medicine box with blister packs (Ep3–4), a small purse. In Ep2 she exists only as chat text — no Veo needed.
**Acting notes:** minimal movement, holds hands on knees when seated, slight nod instead of smiling, first real smile only at "Hamma ozgʻin edi".

---

### 2.4 SARDOR (31) — the husband
**Base paragraph (verbatim):**
```
Sardor: Uzbek man, 31, Central Asian features, medium height, slightly stocky, short black hair with faded sides, neat short dark stubble, kind tired eyes, light tan skin, relaxed slouching posture.
```
**Outfit — Episode 1:**
```
EP1 HOME (dinner): He wears a navy quarter-zip fleece pullover over a white t-shirt, grey sweatpants, house slippers, and holds a black smartphone in a clear case.
```
```
EP1 NIGHT: He is asleep under a quilted bedspread in a plain grey t-shirt, back half-turned to camera.
```
**Later episodes:** Ep2 — dark-grey hoodie (dinner). Ep3 — dark suit, white shirt, no tie, leaving for work. Ep4 — suit trousers, white shirt with loosened dark tie, sleeves rolled. Ep5 — black polo, paper and pen (budget scene).
**Acting notes:** eyes on his phone, shrugs, the family's quiet peacemaker.

---

### 2.5 IMRON (1.5) — the baby. FACE NEVER SHOWN (brand rule)
**Base paragraph (verbatim):**
```
Imron: a 1.5-year-old Uzbek toddler boy with soft dark hair and chubby small hands, seen ONLY from behind or from the neck down, face always turned away from the camera and never visible.
```
**Outfit — Episode 1:**
```
EP1 DINNER: mustard-yellow cotton romper and tiny grey-and-white striped socks.
```
```
EP1 NIGHT: white footed sleep suit, asleep on his side facing away from camera.
```
**Later:** Ep2 — light-green romper; Ep3 — blue romper (building blocks scene); Ep5 — same mustard-yellow romper as Ep1 (visual rhyme for the finale).
**Prompt safety:** always include the words "face never visible". If a take shows the face, reject it.

---

### 2.6 Voice-only / never-on-screen characters (no Veo needed)
| Character | On screen? | Notes for VO (ElevenLabs) |
|---|---|---|
| **Dilnoza** (30, colleague) | Never — voice message + chat only | Fast, warm, slightly out of breath; apply phone-mic EQ (300–3400 Hz band-pass) + café noise bed |
| **Akmal aka** (boss) | Never — phone voice | Male, 45+, neutral-formal; phone EQ, slightly compressed |
| **"Zulfiya"** (scammer) | Never in Season 1 (chat only; Ep5 door shot from behind Nilufar's shoulder, neck-down only) | No voice in Ep1 |

---

## 3. World Bible

### 3.1 KITCHEN — Yunusobod panel apartment (paste verbatim)
```
Location: kitchen of a renovated Soviet-era panel apartment in Yunusobod, Tashkent. Light-oak laminate cabinets, white tile backsplash with a thin blue border, gas stove with a black kazan, a fridge with magnets, a round wall clock, a window with white tulle curtains and a potted red geranium, an old painted radiator under the window. A small kitchen table with a cotton dasturxon tablecloth in the blue-and-white Uzbek 'pakhta' cotton-boll pattern, a matching blue-and-white Uzbek teapot and small piyola bowls, a large turquoise Rishton ceramic lagan of plov, flatbread (non) in a cloth. Evening: warm 3200K ceiling light, deep dusk-blue window.
```

### 3.2 BEDROOM, NIGHT (paste verbatim)
```
Location: small bedroom in the same Yunusobod apartment at night. Double bed with a quilted Uzbek kurpa bedspread in muted red and gold, a dark-wood wardrobe, a bedside table with a lamp switched off and a glass of water, tulle curtains with faint orange street light. The only light is the cool blue-white glow of a phone screen on the face, plus a thin warm sliver of hallway light under the door. Very dark, intimate, late-night mood.
```

### 3.3 OFFICE CORRIDOR — Tashkent business center (paste verbatim)
```
Location: corridor of a modern Tashkent business center in daytime: glass-partitioned offices, grey carpet, white walls, tall windows with soft overcast daylight, a water cooler, a few blurred colleagues far in the background, framed posters out of focus with no readable text.
```

### 3.4 Other recurring locations (Ep2–5, for later packs)
- **HALLWAY / FRONT DOOR:** narrow entrance hall, beige wallpaper, shoe shelf with slippers, coat hooks (Gulnora's brown bag hangs here in Ep3), oval mirror, framed family photos, a steel front door with a peephole, warm bulb.
- **LIVING-ROOM SOFA (night):** brown fabric sofa, a folded patterned blanket, floor lamp with warm fabric shade, TV off, a child's toy basket.
- **YARD / PODYEZD BENCH (morning):** 4-storey grey-and-beige Soviet panel block, metal entrance door with a code lock, a green wooden bench, poplar and mulberry trees, parked white Chevrolet Cobalt, dusty golden morning light, a cat.

### 3.5 Light & grade cheat-sheet
| Scene type | Light | Grade |
|---|---|---|
| Office day | Soft overcast window light, cool-neutral | Slightly cooler, clean |
| Kitchen dinner | Warm 3200K tungsten + dusk-blue window | Warm amber skin, teal shadows |
| Bedroom night | Phone glow (cool blue-white) only + hallway sliver | Deep blacks, no lifted shadows |
| Yard morning | Low golden sun, long shadows | Warm, dusty, slight haze |

### 3.6 Camera & lens rules (write into prompts as "CAMERA:")
- Handheld with subtle natural shake; slow push-ins (never zooms); no drone, no crane, no whip-pans.
- Medium shots ≈35mm; close-ups ≈50–85mm, f/2 shallow depth of field.
- Vertical framing: eyes in the upper third; keep the lower 25% of frame simple (subtitles live there); keep the right edge free of key action (Instagram UI icons).
- Freeze-frame endings are made in the edit, not asked of Veo.

### 3.7 Things Veo must NOT generate (if seen, reject the take)
- Any readable text: subtitles, captions, phone UI, shop signs, Cyrillic/Latin/Arabic letters, brand logos.
- Imron's face.
- Stylized/HDR/anime looks, slow motion, lens flares, dramatic colored lighting.
- Extra family members or a second child.
- Headscarf styles that are not the described house headscarf (no hijab-style wrap for Mahbuba; Gulnora's is a neat full cover, not a loose shawl).
