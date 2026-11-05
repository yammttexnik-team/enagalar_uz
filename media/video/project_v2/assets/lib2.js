/* v2 helpers: hard-cut scene timeline, slide-up text (no blur), QA bounds check */
const V = (() => {
  const { el, tf, rnd, clamp, p } = E;
  const H = window.__H || 1920;
  const F45 = H === 1350;
  if (F45) document.body.classList.add('f45');
  const SAFE = F45 ? { l: 50, r: 1030, t: 40, b: 1310 } : { l: 80, r: 960, t: 250, b: 1560 };
  const background = (stage, grain = !F45) => {
    const bg = el('div', 'layer', stage, { height: H + 'px' }); bg.id = 'bg';
    el('div', 'base', bg);
    const b1 = el('div', 'blob', bg, { width: '900px', height: '900px', background: '#0b9aa0', left: '-300px', top: '120px', opacity: .38 });
    const b2 = el('div', 'blob', bg, { width: '820px', height: '820px', background: '#c55205', left: '560px', top: (H - 870) + 'px', opacity: .24 });
    const b3 = el('div', 'blob', bg, { width: '640px', height: '640px', background: '#3eb5ba', left: '620px', top: '-160px', opacity: .2 });
    const dust = [];
    for (let i = 0; i < 34; i++) {
      const s = 3 + rnd(i) * 5;
      const d = el('div', 'abs', bg, { width: s + 'px', height: s + 'px', borderRadius: '50%', background: rnd(i + 1) > .65 ? '#feb38e' : '#b5e8e8' });
      dust.push({ d, x: rnd(i + 2) * 1080, y: rnd(i + 3) * H, v: 8 + rnd(i + 4) * 20, a: .12 + rnd(i + 5) * .35, ph: rnd(i + 6) * 6.28 });
    }
    el('div', 'vig', bg);
    const gr = grain ? el('div', 'grain', bg) : null;
    return {
      update(t) {
        tf(b1, { x: Math.sin(t * .23) * 90, y: Math.cos(t * .19) * 70 });
        tf(b2, { x: Math.cos(t * .17) * 110, y: Math.sin(t * .21) * 80 });
        tf(b3, { x: Math.sin(t * .13 + 1) * 70, y: Math.cos(t * .16) * 50 });
        dust.forEach(q => { let y = (q.y - t * q.v) % H; if (y < 0) y += H; tf(q.d, { x: (q.x + Math.sin(t * .6 + q.ph) * 18 + 1080) % 1080, y, o: q.a * (.6 + .4 * Math.sin(t * 1.3 + q.ph)) }); });
        if (gr) { const f = Math.floor(t * 24); gr.style.transform = `translate(${(rnd(f) * 200) | 0}px,${(rnd(f + 99) * 200) | 0}px)`; }
      }
    };
  };
  /* text line: centered in the 120..960 box (or custom), class qa for bounds check */
  const T = (par, html, y, size, kind = 'H', st = {}) => {
    const e = el('div', 'T qa ' + kind, par, Object.assign({ top: y + 'px', fontSize: size + 'px' }, st), html);
    const sw = e.scrollWidth, cw = e.clientWidth;
    if (cw && sw > cw) e.style.fontSize = Math.floor(size * cw / sw * .98) + 'px';
    return e;
  };
  /* slide up + fade, fully readable after ~0.25 s, no blur */
  const up = (e, lt, t0, dy = 44, d = .32, extra = {}) => { const q = p(lt, t0, d, 'outExpo'); tf(e, Object.assign({ y: (1 - q) * dy, o: clamp((lt - t0) / .12) }, extra)); return q; };
  const pop = (e, lt, t0, d = .4, extra = {}) => { const q = p(lt, t0, d, 'outBack'); tf(e, Object.assign({ s: .6 + .4 * q, o: clamp((lt - t0) / .1) }, extra)); return q; };
  const press = (lt, tc) => lt > tc - .05 && lt < tc + .2 ? Math.sin(Math.PI * (lt - tc + .05) / .25) : 0;
  /* scene timeline with hard cuts */
  const timeline = (dur, list) => {
    const scene = { duration: dur, fps: 30 };
    let bg;
    scene.build = (stage) => {
      stage.style.height = H + 'px';
      bg = background(stage);
      list.forEach(s => {
        s.layer = el('div', 'layer', stage, { height: H + 'px' });
        const cue = (lt, name, g = 1, pan = 0, o) => E.cue(Math.max(0, s.start + lt), name, g, pan, o);
        s.obj = s.make(s.layer, cue, s.end - s.start);
      });
      B.fixApos(stage);
    };
    scene.update = (t) => {
      bg.update(t);
      list.forEach(s => { const v = t >= s.start && t < s.end; s.layer.style.display = v ? '' : 'none'; if (v) s.obj(t - s.start); });
    };
    return scene;
  };
  const qa = () => {
    const out = [];
    document.querySelectorAll('.qa').forEach(e => {
      if (!e.offsetParent && getComputedStyle(e).position !== 'fixed') return;
      let n = e, op = 1; while (n && n.style) { if (n.style.display === 'none') return; op *= +(getComputedStyle(n).opacity); n = n.parentElement; }
      if (op < .5) return;
      const r = e.getBoundingClientRect();
      if (r.left < SAFE.l - 1 || r.right > SAFE.r + 1 || r.top < SAFE.t - 1 || r.bottom > SAFE.b + 1) out.push(`${(e.textContent || e.className).slice(0, 40)} [${r.left | 0},${r.top | 0},${r.right | 0},${r.bottom | 0}]`);
      if (e.scrollWidth > e.clientWidth + 2 && e.clientWidth > 0) out.push('OVERFLOW ' + (e.textContent || '').slice(0, 40) + ` ${e.scrollWidth}>${e.clientWidth}`);
    });
    return out;
  };
  window.__qa = qa;
  const phone = (par, x, y, w, h) => { const ph = el('div', 'phone qa', par, { left: x + 'px', top: y + 'px', width: w + 'px', height: h + 'px' }); const sc = el('div', 'screen', ph); return { ph, sc }; };
  const arrow = (s = 48) => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
  const start = (scene) => B.loadFonts().then(() => E.run(scene));
  return { H, F45, SAFE, T, up, pop, press, timeline, phone, arrow, start, background };
})();
