# ENAGA KERAK! · 1-qism «10 kun» — Assembly / Edit Plan

> Timeline: 1080×1920, 30 fps, total **1:18** (78 s). Tools: CapCut or DaVinci Resolve.
> Sources: Veo clips `ep01_C01…C12` (see `ep01_shots.md`), screen-recording inserts `S01…S06` (ours, real UI on test accounts), end card `E01` (graphics), ElevenLabs VO files, SFX/music.
> Rule: **Veo audio is never used for speech.** Keep Veo ambience only when it is clean; otherwise replace it with the ambience beds listed in §5.

---

## 1. Safe zones & subtitle style
- Keep faces/action in the upper 70 % of the frame. Instagram UI covers the bottom ~20 % and the right edge (icons).
- **Dialogue subtitles:** white, 56–60 px, bold sans (Inter / Montserrat), black 60 % rounded box, centered, baseline at **72 %** of frame height; max 2 lines, max ~38 characters per line.
- **BIG QUOTE style (Mahbuba's two lines):** 72 px, white on turquoise #018186 box, centered at 60 % height, with Uzbek quotation marks « ».
- **Titles/overlays (time stamps, "10 kun qoldi…"):** upper third, 64–80 px, white with soft shadow, no box.
- Brand colors appear only on the end card and the BIG QUOTE box (turquoise #018186; apricot #c55205 for the end-card warning triangle).

## 2. Voices (ElevenLabs) — cast & processing
| Tag | Character | Voice direction | Processing |
|---|---|---|---|
| V-NIL | Nilufar | Female 25–32, Tashkent Uzbek, quick, slight Russian loanwords kept as written ("uje", "normalno"); inner-voice lines whispered | Inner voice: −6 dB, light short room reverb, low-pass 8 kHz |
| V-MAH | Mahbuba opa | Female 55–60, slow, firm, teacher's diction, no Russian words | Dry, +2 dB presence (she must cut through) |
| V-SAR | Sardor | Male 30, low energy, mumbling; night line half-asleep | Night line: −8 dB, muffled (low-pass 4 kHz) |
| V-AKM | Akmal aka (boss, phone) | Male 45+, formal, fast | Phone EQ: band-pass 300–3400 Hz, light distortion, −4 dB |
| V-DIL | Dilnoza (voice message) | Female 30, fast, cheerful, out of breath | Phone EQ 300–3400 Hz + café noise bed under her (−18 dB), start/stop with Telegram voice-message click |

Generate every line as a separate file named `ep01_<TC>_<TAG>.mp3` (e.g. `ep01_0155_V-MAH.mp3`). Record 2 variants of each Mahbuba line (one drier, one more amused) and pick in the edit.

## 3. Frame-accurate timeline

| TC in | TC out | Source (part) | Picture note | VO (voice) | Subtitle / overlay | SFX | Music |
|---|---|---|---|---|---|---|---|
| 0:00.0 | 0:00.5 | black | — | — | — | — | silence |
| 0:00.5 | 0:04.0 | **S01 INSERT** Telegram message, zoom-in 110 %→130 % | Message fills frame | — | 0:02–0:04 overlay (big): `10 kun qoldi. Enaga yoʻq.` | 0:00.5 Telegram "pop" | silence (hook stays raw) |
| 0:04.0 | 0:09.5 | **C01** (0.0–5.5 s) | Walk toward camera, phone at ear, stops | V-AKM 0:04–0:08: «Nilufar, dekret tugadi, dushanbadan kutamiz. Loyiha sizsiz turib qoldi.» · V-NIL 0:08–0:09.5: «Xoʻp, Akmal aka, boʻladi. Dushanba.» | 0:04–0:07 title (upper): `Dekret tugadi. Dushanbadan ishga. 10 kun.` · subtitles for both lines | Office bed | **M1 tension pulse** fades in at 0:04 (−20 dB) |
| 0:09.5 | 0:12.0 | **C02** (2.0–4.5 s) | Looks at phone, then window | V-NIL (low) 0:09.5–0:12: «Dushanba… Imronni kimga qoldiraman?» | `Dushanba… Imronni kimga qoldiraman?` | Office bed; phone "call ended" beep at 0:09.5 | M1 continues |
| 0:12.0 | 0:15.5 | **C03** (1.0–4.5 s) | Kitchen wide; Mahbuba freezes with bowl | V-NIL 0:12–0:15.5: «Oyi, men dushanbadan ishga chiqaman. Enaga olishimiz kerak.» | `Oyi, dushanbadan ishga chiqaman. Enaga olishimiz kerak.` | Kitchen bed (spoon, clock, fridge) | M1 drops to −26 dB on the cut |
| 0:15.5 | 0:19.5 | **C04** (0.5–4.5 s) | Mahbuba CU, bowl in hands | V-MAH 0:15.5–0:19.5: «Enaga? Begona ayol mening uyimga kirib, nevaramni qarasinmi?» | **BIG QUOTE** `«Begona ayol mening uyimga kirib, nevaramni qarasinmi?»` | Kitchen bed; spoon stops | **M1 STOPS dead at 0:15.5** (silence under the meme line) |
| 0:19.5 | 0:21.5 | **C05** (1.0–3.0 s) | Nilufar + Imron (back of head) | V-NIL 0:19.5–0:21.5: «Oyi, hamma oladi uje. Normalno bu.» | `Oyi, hamma oladi uje. Normalno bu.` | Kitchen bed, toddler babble | — |
| 0:21.5 | 0:25.0 | **C04** (4.5–8.0 s) | Counting on fingers, head shake | V-MAH 0:21.5–0:25: «Hamma — hamma. Men uch bola oʻstirganman, bittasini ham enagaga bermaganman.» | `Men uch bola oʻstirganman, bittasini ham enagaga bermaganman.` | Kitchen bed | — |
| 0:25.0 | 0:27.5 | **C06** (1.5–4.0 s) | Sardor mumbles, hands up | V-SAR 0:25–0:27: «Oyi, oʻshanda siz ishlamagansiz-da…» | `Oyi, oʻshanda siz ishlamagansiz-da…` | Kitchen bed; phone scroll tick | **M2 light comedic pizzicato**, 2 notes only, at 0:26.5 |
| 0:27.5 | 0:29.5 | **C07** (0.0–2.0 s) | Sharp cut gesture → turns to Nilufar | V-MAH 0:27.5–0:28.3: «Sen oʻchir.» · 0:28.3–0:29.5: «Men oʻzim qarayman.» | `Sen oʻchir.` / `Men oʻzim qarayman.` | Kitchen bed | — |
| 0:29.5 | 0:31.5 | **C05** (5.0–7.0 s) | Nilufar glances at baby, careful line | V-NIL 0:29.5–0:31.5: «Oyi, sizning tizzangiz… Doktor 8 soat bola koʻtarmang degan.» | `Oyi, tizzangiz… Doktor «8 soat bola koʻtarmang» degan.` | Kitchen bed | — |
| 0:31.5 | 0:34.0 | **C07** (4.0–6.5 s) | Bowl down, glasses up, nod | V-MAH (slow) 0:31.5–0:34: «…Topsang ham, men tekshiraman. Har bir tishini.» | **BIG QUOTE** `«Topsang ham, men tekshiraman. Har bir tishini.»` | Bowl set on cloth (0:31.8) | **M3 warm low pad** enters softly at 0:33 |
| 0:34.0 | 0:38.0 | **C08** (0.0–4.0 s) | Night, face lit, endless scroll | V-NIL inner (whisper) 0:34–0:38: «Toʻrt yuz ellik post… bittasida ham rasm yoʻq, ismi yoʻq…» | 0:34–0:36 overlay top-right: `23:40` · subtitle: `450 post… bittasida ham rasm yoʻq, ismi yoʻq…` | Night bed; scroll ticks | M3 → **M4 night minimal** (single sustained note, −24 dB) |
| 0:38.0 | 0:42.0 | **S02 INSERT** Telegram channel scroll (1.3×) | Full-screen recording (or corner-pinned into C09 plate if it tracks) | V-NIL inner 0:39–0:42: «Bu "Nodira" ikki kanalda ikki xil raqam bilan…» | 0:38–0:40 title: `23:40. 450-post. Hammasi bir xil.` · 0:40–0:42 subtitle: `Bu «Nodira» — ikki kanalda, ikki xil raqam.` | Scroll ticks (fast) | M4 |
| 0:42.0 | 0:45.0 | **C09** (3.0–6.0 s) | Over-shoulder; thumb stops at 0:44.0 | — | — (let it breathe) | Scroll ticks stop at 0:44.0; one low piano note at 0:44.2 | M4 |
| 0:45.0 | 0:48.0 | **S03 INSERT** "Zulfiya" post → tap → typing | 0:45–0:46.5 slow zoom on the post; 0:46.5–0:48 keyboard, first words typed | V-NIL (whisper) starts 0:47: «Yunusobod. Uch million.» | Subtitle 0:47–0:48.5: `Yunusobod. Uch million.` | Keyboard taps | M4 + faint hopeful upper note at 0:46 |
| 0:48.0 | 0:50.0 | **C08** (6.0–8.0 s) | Hope flicker, typing | V-NIL (whisper) 0:48.5–0:50: «Assalomu alaykum, e'loningizni koʻrdim…» | `«Assalomu alaykum, e'loningizni koʻrdim…»` | Keyboard taps | M4 |
| 0:50.0 | 0:52.0 | **S04 INSERT** Dilnoza voice message arrives, play | Bubble "0:14", waveform animates | — | — | Telegram "ting" 0:50.0; voice-message start click 0:51.8 | M4 drops −6 dB for the voice |
| 0:52.0 | 0:59.0 | **C10** (0.5–7.5 s) | Half-listening, eyes on chat | V-DIL 0:50.5–0:57.5 (starts under S04, continues over C10): «Nilu, eshitdim, chiqyapsan ekan! Enaga haqida: Telegramdan qidirmay, oʻzing azob chekasan. enagalar.uz degan sayt bor, pasporti tekshirilganlar bor, chat ichida… men opamga oʻshandan topdim. Keyin gaplashamiz, yugurdim!» · V-NIL (flat) 0:57.5–0:59: «Ha, ha, keyin koʻraman…» | 0:50.5–0:54: `Telegramdan qidirmay, oʻzing azob chekasan.` · 0:54–0:57.5: `«…enagalar.uz, pasporti tekshirilganlar bor…»` · 0:57.5–0:59: `Ha, ha, keyin koʻraman… (Nilufar eshitmadi)` | Café noise bed under Dilnoza; voice-message end click 0:57.5 | M4 |
| 0:59.0 | 1:05.0 | **S05 INSERT** "Zulfiya" chat (1.2×) | Bubbles arrive one by one; hold 2 frames on "500 000" | — (reading time) | Native chat text is the subtitle; overlay top-right at 1:04: `23:58` | Telegram pops per bubble; "typing…" tick | **M5 tension pulse** returns, slow build from −24 to −16 dB |
| 1:05.0 | 1:08.0 | **C11** (2.0–5.0 s) | Looks at Imron, whispers to Sardor's back | V-NIL (whisper) 1:05–1:06.3: «Toʻrt oila… dushanba… Sardor, uxlayapsanmi?» · V-SAR (muffled) 1:06.3–1:08: «Mm. Oʻzing bil.» | `Toʻrt oila… dushanba… Sardor, uxlayapsanmi?` / `Mm. Oʻzing bil.` | Night bed; fabric rustle | M5 |
| 1:08.0 | 1:10.0 | **S06 INSERT** Click: card number (masked) typed, amount `500 000` | Real Click UI | — | — | Keypad taps | M5 |
| 1:10.0 | 1:13.0 | **C12** (2.0–5.0 s) with Click screen composited (or practical shot) | Thumb trembling over «Oʻtkazish» | — | — | **Heartbeat ×2** at 1:11.0 and 1:12.2; M5 cuts out at 1:10 | silence except heartbeat |
| 1:13.0 | 1:15.0 | **C12 FREEZE** (frame at 1:13.0), 2 s | Frozen | — | 1:13–1:15 big: `Oʻtkazadimi?` · small bottom: `2-qism — juma kuni` | Low sub "thud" on freeze | — |
| 1:15.0 | 1:18.0 | **E01 END CARD** | White bg, turquoise mark | — | see §6 | Soft UI "whoosh" in | **M6 short sting**, 2 s, resolves |

Total = 78 s. If the cut runs long after VO placement, trim first: C09 (−1 s), S05 (−1 s), C01 (−0.5 s).

## 4. Where the phone screens are INSERTS (not Veo)
| ID | Content | Prepared by us | Notes |
|---|---|---|---|
| S01 | Telegram message "Joy band boʻlmasligi uchun 500 000 oldindan…" | Test account "Zulfiya enaga", generated avatar | Zoom + "pop" in edit |
| S02 | Telegram channel endless scroll | Test channel with 15–20 fake posts (UZ/RU) | 1.3× speed |
| S03 | "Zulfiya" post + typing first message | Same test account | Generated photo, not a real person |
| S04 | Dilnoza voice message bubble, play | Test chat "Dilnoza 💼" | 0:14 bubble |
| S05 | "Zulfiya" chat, 5 bubbles | Scripted chat on two test accounts | Card number masked `8600 **** **** 4471` |
| S06 | Click app transfer screen, amount 500 000 | Real app, our own card, masked, NO real transfer | Stop before the button |
| C12 overlay | Click confirm screen «500 000 soʻm · Oʻtkazish» | Screenshot of S06 state | Corner-pin onto Veo blank screen, or shoot practically |

**enagalar.uz on screen in Ep1: NONE** (script rule — the product is only in Dilnoza's voice; the site's first screen appearance is Ep2 scene 6, 12–15 s screencast). Do not add a site screenshot to Ep1.

## 5. Ambience beds & SFX list
| Name | Use | Source suggestion |
|---|---|---|
| Office bed | 0:04–0:12 | Quiet open-office hum, distant keyboard, AC (Pixabay "office ambience") |
| Kitchen bed | 0:12–0:34 | Fridge hum + wall clock + occasional spoon/ceramic; add a 1-s toddler babble sample under C05 (library sample, not a real child recording from the family) |
| Night bed | 0:34–1:13 | Deep room tone + one distant car at 0:41 and 1:02 |
| Café noise | under V-DIL only | Low café chatter, cups, −18 dB |
| Telegram pop / ting | S01, S04, S05 | Record from a real phone or library "message pop" |
| Scroll ticks | C08/S02/C09 | Short haptic tick, repeat with the thumb; stop at 0:44.0 |
| Keyboard taps | S03, C08-B, S06 | Soft glass taps |
| Heartbeat ×2 | 1:11.0, 1:12.2 | Deep, slow, −6 dB |
| Freeze thud | 1:13.0 | Low sub hit |
| Bowl on cloth | 0:31.8 | Foley |
| "Call ended" beep | 0:09.5 | Phone UI |

## 6. Music cues (Instagram library / royalty-free; moods, not tracks)
| Cue | TC | Mood | Level |
|---|---|---|---|
| M1 | 0:04–0:15.5 | Minimal tension pulse (synth/pizzicato), 90 bpm | −20 dB → −26 dB under dialogue; hard stop at 0:15.5 |
| M2 | 0:26.5 | Two comedic pizzicato notes under Sardor | −18 dB |
| M3 | 0:33–0:34 | Warm low pad, one chord | −24 dB |
| M4 | 0:34–0:59 | Night minimal: single sustained note + sparse piano | −24 dB; −30 dB under Dilnoza |
| M5 | 0:59–1:10 | Tension pulse returns, builds | −24 → −16 dB; hard cut at 1:10 |
| M6 | 1:15–1:17 | Short brand sting | −14 dB |
Silence is deliberate at: 0:00–0:04 (hook), 0:15.5–0:26 (Mahbuba's line), 1:10–1:15 (heartbeat only).

## 7. Overlays & end card text (exact)
- 0:02–0:04 — `10 kun qoldi. Enaga yoʻq.`
- 0:04–0:07 — `Dekret tugadi. Dushanbadan ishga. 10 kun.`
- 0:34–0:36 — `23:40` (corner)
- 0:38–0:40 — `23:40. 450-post. Hammasi bir xil.`
- 1:04–1:05 — `23:58` (corner)
- 1:13–1:15 — big `Oʻtkazadimi?` + small `2-qism — juma kuni`
- **E01 end card (1:15–1:18), white background, turquoise #018186 mark left:**
  ```
  ENAGA KERAK! · 1-qism
  Davomi — jumada. «2-qism» deb yozing, eslatamiz.
  ⚠️ Enagaga oldindan pul oʻtkazmang. Sinov kunidan boshlang.
  ```
  (No site name on the Ep1 end card. Link lives in the pinned comment.)

## 8. Exports
| File | Spec | Use |
|---|---|---|
| `ENAGA_KERAK_01_10kun.mp4` | 1080×1920, 30 fps, H.264 12–16 Mbps, AAC 192 kbps, −14 LUFS | Instagram Reels / TikTok / Shorts |
| `ENAGA_KERAK_01_cover.jpg` | 1080×1920, frame from C04 at 0:17 (Mahbuba mid-line), big yellow-white text `ENAGA KERAK!` + `1-QISM` | Reels cover |
| `ep01_cut_begona_ayol.mp4` | 8 s: C04 use-A with BIG QUOTE subtitle + V-MAH, end frame `ENAGA KERAK! · 1-qism — profilda` | Wednesday Stories / teaser Reel |
| `ENAGA_KERAK_01_telegram.mp4` | Same cut, published 1 day later on @enagalar_uz | Telegram |

## 9. QC before publishing
- [ ] No Veo-generated speech or gibberish audible anywhere.
- [ ] No Veo text/captions visible in any frame (scrub C01–C12 at 2× speed).
- [ ] Imron's face never visible (C03, C05, C08, C11).
- [ ] Card number masked in S01, S05, S06; no real transfer made.
- [ ] Site name appears exactly once (Dilnoza, 0:54) and never on screen.
- [ ] Subtitles sit at 72 % height, never behind the Reels UI; all Uzbek apostrophes are `ʻ` / `'` consistently.
- [ ] Freeze frame = 2.0 s; end card = 3.0 s; total ≤ 1:20.
- [ ] Caption and pinned comment from `../concept.md` §8 ready; `?c=REELS` campaign code created in admin (Kampaniyalar → `REELS_S1`).
