/* expert-reels motion engine — deterministic, frame-accurate.
   A scene defines build() (create DOM + register SFX cues) and update(t) (pure function of time).
   render.py calls window.__render(t) per frame and reads window.__cues for the SFX mixer. */
const E = (() => {
  const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
  const lerp = (a, b, t) => a + (b - a) * t;
  const ease = {
    linear: t => t,
    inCubic: t => t * t * t,
    outCubic: t => 1 - Math.pow(1 - t, 3),
    inOutCubic: t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2,
    outQuart: t => 1 - Math.pow(1 - t, 4),
    outQuint: t => 1 - Math.pow(1 - t, 5),
    inQuint: t => t * t * t * t * t,
    outExpo: t => t >= 1 ? 1 : 1 - Math.pow(2, -10 * t),
    inExpo: t => t <= 0 ? 0 : Math.pow(2, 10 * t - 10),
    inOutExpo: t => t <= 0 ? 0 : t >= 1 ? 1 : t < .5 ? Math.pow(2, 20 * t - 10) / 2 : (2 - Math.pow(2, -20 * t + 10)) / 2,
    inOutQuint: t => t < .5 ? 16 * t ** 5 : 1 - Math.pow(-2 * t + 2, 5) / 2,
    outBack: t => { const s = 1.70158; return 1 + (s + 1) * Math.pow(t - 1, 3) + s * Math.pow(t - 1, 2); },
    outBackSoft: t => { const s = 1.1; return 1 + (s + 1) * Math.pow(t - 1, 3) + s * Math.pow(t - 1, 2); },
    inBack: t => 2.70158 * t * t * t - 1.70158 * t * t,
    inOutSine: t => -(Math.cos(Math.PI * t) - 1) / 2,
    spring: t => t >= 1 ? 1 : 1 - Math.exp(-6.5 * t) * Math.cos(11 * t),
  };
  const E_ = k => typeof k === 'function' ? k : ease[k || 'outCubic'];
  /** progress of an animation that starts at t0 and lasts d seconds */
  const p = (t, t0, d, e = 'outCubic') => E_(e)(clamp((t - t0) / d));
  /** window: 0 before tIn, ramps to 1, holds, ramps down to 0 at tOut */
  const win = (t, tIn, tOut, dIn = .4, dOut = .35, eIn = 'outCubic', eOut = 'inCubic') =>
    t < tIn + dIn ? p(t, tIn, dIn, eIn) : 1 - p(t, tOut, dOut, eOut);
  /** keyframes: [[time, value, easeIntoThisKey], ...] */
  const kf = (t, keys) => {
    if (t <= keys[0][0]) return keys[0][1];
    for (let i = 1; i < keys.length; i++) {
      if (t <= keys[i][0]) {
        const [a, va] = keys[i - 1], [b, vb, e] = keys[i];
        return lerp(va, vb, E_(e || 'inOutCubic')((t - a) / (b - a)));
      }
    }
    return keys[keys.length - 1][1];
  };
  /** smooth deterministic noise in [-1,1] */
  const noise = (t, seed = 0) => {
    const f = (x) => Math.sin(x * 1.7 + seed * 12.9) * .5 + Math.sin(x * 2.9 + seed * 4.1) * .3 + Math.sin(x * 5.3 + seed * 7.7) * .2;
    return f(t);
  };
  const rnd = (seed) => { const x = Math.sin(seed * 9301.17 + 49297.3) * 233280; return x - Math.floor(x); };

  /** transform setter */
  const tf = (el, o = {}) => {
    const { x = 0, y = 0, s = 1, sx, sy, r = 0, o: op, blur, rx = 0, ry = 0 } = o;
    el.style.transform = `translate3d(${x.toFixed(2)}px,${y.toFixed(2)}px,0) rotate(${r.toFixed(3)}deg)` +
      (rx || ry ? ` rotateX(${rx}deg) rotateY(${ry}deg)` : '') + ` scale(${(sx ?? s).toFixed(4)},${(sy ?? s).toFixed(4)})`;
    if (op !== undefined) { el.style.opacity = clamp(op).toFixed(3); el.style.visibility = op <= 0.001 ? 'hidden' : 'visible'; }
    if (blur !== undefined) el.style.filter = blur > 0.08 ? `blur(${blur.toFixed(2)}px)` : 'none';
  };
  const el = (tag, cls, parent, style = {}, html) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    Object.assign(e.style, style);
    if (html !== undefined) e.innerHTML = html;
    if (parent) parent.appendChild(e);
    return e;
  };
  const show = (e, v) => { e.style.display = v ? '' : 'none'; };

  /* ---------- text: split into word spans, preserving inline styling wrappers ---------- */
  const splitWords = (root) => {
    const words = [];
    const walk = (node) => {
      [...node.childNodes].forEach(ch => {
        if (ch.nodeType === 3) {
          const parts = ch.textContent.split(/(\s+)/).filter(s => s.length);
          const frag = document.createDocumentFragment();
          parts.forEach(ptxt => {
            if (/^\s+$/.test(ptxt)) { frag.appendChild(document.createTextNode(' ')); return; }
            const s = document.createElement('span'); s.className = 'w'; s.textContent = ptxt; frag.appendChild(s); words.push(s);
          });
          node.replaceChild(frag, ch);
        } else if (ch.nodeType === 1 && !ch.classList.contains('w')) walk(ch);
      });
    };
    walk(root);
    return words;
  };
  /** caption block: lines = [{cls:'l-sans', html:'...' , style:{}}...] */
  const caption = (parent, lines, top, extra = {}) => {
    const box = el('div', 'cap', parent, Object.assign({ top: top + 'px' }, extra));
    const lineEls = lines.map(L => {
      const d = el('div', 'line ' + (L.cls || ''), box, L.style || {}, L.html);
      return d;
    });
    const perLine = lineEls.map(d => splitWords(d));
    return { box, lines: lineEls, perLine, words: perLine.flat() };
  };
  /** reveal words (in) and optional exit (out). Words animate up from blur. */
  const animWords = (cap, t, tIn, tOut, o = {}) => {
    const { stagger = .075, dur = .62, dy = 46, blur = 16, sc = .94, lineGap = 0, outDur = .38, outDy = -34, lineStarts } = o;
    let i = 0;
    cap.perLine.forEach((ws, li) => {
      const base = lineStarts ? lineStarts[li] : tIn + li * lineGap;
      ws.forEach((w, wi) => {
        const st = (lineStarts ? base + wi * stagger : tIn + i * stagger + li * lineGap);
        const q = p(t, st, dur, 'outExpo');
        let x = q, yy = (1 - q) * dy, bl = (1 - q) * blur, s = sc + (1 - sc) * q;
        if (tOut !== undefined && t > tOut) {
          const qo = p(t, tOut + i * .025, outDur, 'inCubic');
          x = x * (1 - qo); yy += qo * outDy; bl += qo * 12; s *= 1 - .04 * qo;
        }
        tf(w, { y: yy, s, o: x, blur: bl });
        i++;
      });
    });
    cap.box.style.visibility = (t < tIn - .05 || (tOut !== undefined && t > tOut + outDur + cap.words.length * .025 + .05)) ? 'hidden' : 'visible';
  };

  /* ---------- sound cue registry (read by sfx.py) ---------- */
  const cues = [];
  const cue = (t, name, gain = 1, pan = 0, opt = {}) => cues.push(Object.assign({ t: +t.toFixed(4), name, gain, pan }, opt));

  /* ---------- typing helper ---------- */
  const typed = (text, t, t0, cps) => text.slice(0, Math.max(0, Math.min(text.length, Math.floor((t - t0) * cps))));
  /** one keystroke sound per typed character. name 'mech' = mechanical keyboard (space bar gets its own deeper sound) */
  const cueTyping = (t0, text, cps, gain = .5, name = 'key') => {
    for (let i = 0; i < text.length; i++) {
      const sp = text[i] === ' ';
      if (name === 'key' && sp && rnd(i) < .5) continue;
      const jit = name === 'mech' ? (rnd(i + 21) - .5) * .018 : 0;
      cue(t0 + i / cps + jit + .012, name, gain * (sp ? 1.05 : .8 + rnd(i + 3) * .35), (rnd(i + 7) - .5) * .5, { v: sp && name === 'mech' ? 6 : Math.floor(rnd(i + 11) * 6) });
    }
  };

  /* ---------- icons ---------- */
  const icon = {
    spark: (size = 100, color = '#D97757') => {
      const L = [45, 37, 44, 39, 46, 36, 43, 40, 45, 38, 44, 37];
      let r = '';
      for (let i = 0; i < 12; i++) {
        const a = i / 12 * Math.PI * 2 + .21, x1 = 50 + Math.cos(a) * 7, y1 = 50 + Math.sin(a) * 7, x2 = 50 + Math.cos(a) * L[i], y2 = 50 + Math.sin(a) * L[i];
        r += `<line x1="${x1.toFixed(1)}" y1="${y1.toFixed(1)}" x2="${x2.toFixed(1)}" y2="${y2.toFixed(1)}" stroke="${color}" stroke-width="${i % 2 ? 7 : 8.5}" stroke-linecap="round"/>`;
      }
      return `<svg viewBox="0 0 100 100" width="${size}" height="${size}">${r}</svg>`;
    },
    star4: (size = 40, color = '#fff') => `<svg viewBox="0 0 40 40" width="${size}" height="${size}"><path d="M20 0 C21.5 13 27 18.5 40 20 C27 21.5 21.5 27 20 40 C18.5 27 13 21.5 0 20 C13 18.5 18.5 13 20 0Z" fill="${color}"/></svg>`,
    cursor: () => `<svg viewBox="0 0 54 64" width="54" height="64"><path d="M6 4 L6 50 L17.5 39 L25.5 57.5 L33 54.5 L25 36.5 L41 36.5 Z" fill="#fff" stroke="#0b1026" stroke-width="3.2" stroke-linejoin="round"/></svg>`,
    check: (size = 28, color = '#fff', sw = 3.6) => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M5 12.5 L10 17.5 L19.5 7" fill="none" stroke="${color}" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    cross: (size = 28, color = '#fff', sw = 3.4) => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M6 6 L18 18 M18 6 L6 18" fill="none" stroke="${color}" stroke-width="${sw}" stroke-linecap="round"/></svg>`,
    play: (size = 40, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M8 5.5 L19 12 L8 18.5 Z" fill="${color}" stroke="${color}" stroke-width="1.5" stroke-linejoin="round"/></svg>`,
    chevron: (size = 22, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M6 9 L12 15 L18 9" fill="none" stroke="${color}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    arrowUp: (size = 28, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M12 19 L12 5 M6 11 L12 5 L18 11" fill="none" stroke="${color}" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    plus: (size = 26, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M12 5 V19 M5 12 H19" fill="none" stroke="${color}" stroke-width="2.6" stroke-linecap="round"/></svg>`,
    clip: (size = 26, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="M20 11.5 L12.2 19.3 a5 5 0 0 1 -7.1 -7.1 L13 4.3 a3.3 3.3 0 0 1 4.7 4.7 L9.9 16.8 a1.7 1.7 0 0 1 -2.4 -2.4 L14.6 7.3" fill="none" stroke="${color}" stroke-width="2.1" stroke-linecap="round"/></svg>`,
    search: (size = 26, color = '#fff') => `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="${color}" stroke-width="2.4"/><path d="M15.5 15.5 L20 20" stroke="${color}" stroke-width="2.6" stroke-linecap="round"/></svg>`,
    verified: (size = 40) => {
      let d = ''; const n = 12;
      for (let i = 0; i < n * 2; i++) { const a = i / (n * 2) * Math.PI * 2 - Math.PI / 2, r = i % 2 ? 10.3 : 12; d += (i ? 'L' : 'M') + (12 + Math.cos(a) * r).toFixed(2) + ' ' + (12 + Math.sin(a) * r).toFixed(2); }
      return `<svg viewBox="0 0 24 24" width="${size}" height="${size}"><path d="${d}Z" fill="#3b95f6" stroke="#3b95f6" stroke-width="1.6" stroke-linejoin="round"/><path d="M7.6 12.3 L10.6 15.2 L16.4 9.2" fill="none" stroke="#fff" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    },
  };

  /* ---------- background (navy premium, reference-style hairlines + sparkles) ---------- */
  const background = (stage) => {
    const bg = el('div', 'layer', stage); bg.id = 'bg';
    el('div', 'base', bg);
    const b1 = el('div', 'blob', bg, { width: '900px', height: '900px', background: '#1f45c8', left: '-250px', top: '150px' });
    const b2 = el('div', 'blob', bg, { width: '800px', height: '800px', background: '#3b2a9e', left: '520px', top: '980px', opacity: .38 });
    const b3 = el('div', 'blob', bg, { width: '600px', height: '600px', background: '#0e7bd6', left: '600px', top: '-120px', opacity: .22 });
    const warm = el('div', 'blob', bg, { width: '820px', height: '820px', background: '#e0a53a', left: '130px', top: '560px', opacity: 0 });
    const svg = `<svg width="1080" height="1920" viewBox="0 0 1080 1920" fill="none">
      <path d="M-20 214 Q540 176 1100 214" stroke="rgba(200,215,255,.16)" stroke-width="1.6"/>
      <path d="M-20 1552 Q540 1590 1100 1552" stroke="rgba(200,215,255,.16)" stroke-width="1.6"/>
      <path d="M58 -20 Q44 960 58 1940" stroke="rgba(200,215,255,.09)" stroke-width="1.4"/>
      <path d="M1022 -20 Q1036 960 1022 1940" stroke="rgba(200,215,255,.09)" stroke-width="1.4"/>
      <path d="M170 1668 L910 1668" stroke="rgba(230,236,255,.28)" stroke-width="2.2"/></svg>`;
    el('div', '', bg, { position: 'absolute', left: 0, top: 0 }, svg);
    const orn = [[170, 22], [245, 44], [540, 30], [835, 44], [910, 22]].map(([x, s]) =>
      el('div', 'abs', bg, { left: (x - s) + 'px', top: (1668 - s) + 'px', opacity: .55 }, icon.star4(s * 2, 'rgba(235,240,255,.85)')));
    // dust particles
    const dust = [];
    for (let i = 0; i < 46; i++) {
      const d = el('div', 'abs', bg, { width: (2 + rnd(i) * 4) + 'px', height: (2 + rnd(i) * 4) + 'px', borderRadius: '50%', background: rnd(i + 1) > .7 ? '#ffd978' : '#b9ccff' });
      dust.push({ d, x: rnd(i + 2) * 1080, y: rnd(i + 3) * 1920, v: 8 + rnd(i + 4) * 22, a: .15 + rnd(i + 5) * .45, ph: rnd(i + 6) * 6.28 });
    }
    el('div', 'vig', bg);
    const grain = el('div', 'grain', bg);
    return {
      bg, warm,
      update(t, camx = 0, camy = 0) {
        tf(b1, { x: Math.sin(t * .23) * 90 - camx * .08, y: Math.cos(t * .19) * 70 - camy * .05 });
        tf(b2, { x: Math.cos(t * .17) * 110 - camx * .06, y: Math.sin(t * .21) * 80 - camy * .04 });
        tf(b3, { x: Math.sin(t * .13 + 1) * 70 - camx * .1, y: Math.cos(t * .16) * 50 });
        orn.forEach((o, i) => tf(o, { s: 1 + .12 * Math.sin(t * 2.1 + i), r: t * 8 * (i % 2 ? 1 : -1), o: .45 + .25 * Math.sin(t * 1.7 + i * 1.3) }));
        dust.forEach((q, i) => {
          let y = (q.y - t * q.v - camy * .15) % 1920; if (y < 0) y += 1920;
          let x = (q.x + Math.sin(t * .6 + q.ph) * 18 - camx * .15) % 1080; if (x < 0) x += 1080;
          tf(q.d, { x, y, o: q.a * (.6 + .4 * Math.sin(t * 1.3 + q.ph)) });
        });
        const f = Math.floor(t * 24);
        grain.style.transform = `translate(${(rnd(f) * 200) | 0}px,${(rnd(f + 99) * 200) | 0}px)`;
      }
    };
  };

  /* ---------- cursor ---------- */
  const mkCursor = (parent) => {
    const c = el('div', 'abs', parent, { zIndex: 90 }, icon.cursor());
    c.id = 'cursor';
    const rip = el('div', 'ripple', parent, { width: '70px', height: '70px', zIndex: 89 });
    return {
      c, rip,
      /** path: [[t,x,y,ease]], clicks: [t...] ; tip of arrow at (x,y) */
      update(t, path, clicks = [], vis = [0, 999]) {
        const x = kf(t, path.map(k => [k[0], k[1], k[3]])), y = kf(t, path.map(k => [k[0], k[2], k[3]]));
        let press = 0;
        clicks.forEach(ct => { if (t >= ct - .06 && t < ct + .22) press = Math.max(press, Math.sin(clamp((t - ct + .06) / .28) * Math.PI)); });
        const o = win(t, vis[0], vis[1], .25, .25);
        tf(c, { x: x - 6, y: y - 4, s: 1 - .14 * press, o });
        let rq = -1; clicks.forEach(ct => { if (t >= ct && t < ct + .5) rq = (t - ct) / .5; });
        if (rq >= 0) tf(rip, { x: x - 35, y: y - 35, s: .3 + rq * 1.2, o: (1 - rq) * .9 }); else tf(rip, { o: 0 });
      }
    };
  };

  /* ---------- run ---------- */
  const run = async (scene) => {
    const stage = document.getElementById('stage');
    scene.build(stage);
    const imgs = [...document.images].map(i => i.complete ? Promise.resolve() : new Promise(r => { i.onload = i.onerror = r; }));
    await Promise.all(imgs);
    await document.fonts.ready;
    // force-load every declared face that the scene uses
    await Promise.all(['400', '600', '700', '800'].flatMap(w => [
      document.fonts.load(`${w} 40px "Playfair Display"`, 'Aʻoʼ'), document.fonts.load(`italic ${w} 40px "Playfair Display"`, 'Aʻoʼ'),
      document.fonts.load(`${w === '800' ? 700 : w} 40px Montserrat`, 'Aʻ'), document.fonts.load(`${w === '800' ? 700 : w} 40px Inter`, 'Aʻ')]));
    await document.fonts.load('400 30px "JetBrains Mono"', 'A');
    window.__cues = cues;
    window.__meta = { duration: scene.duration, fps: scene.fps || 30, vo: scene.vo || [] };
    window.__render = (t) => { scene.update(t); return true; };
    scene.update(0);
    window.__ready = true;
    // live preview in a normal browser: ?play
    if (location.search.includes('play')) { const t0 = performance.now(); const loop = () => { scene.update(((performance.now() - t0) / 1000) % scene.duration); requestAnimationFrame(loop); }; loop(); }
    const m = location.search.match(/t=([\d.]+)/); if (m) scene.update(+m[1]);
  };

  return { clamp, lerp, ease, p, win, kf, noise, rnd, tf, el, show, splitWords, caption, animWords, cue, cues, typed, cueTyping, icon, background, mkCursor, run };
})();
