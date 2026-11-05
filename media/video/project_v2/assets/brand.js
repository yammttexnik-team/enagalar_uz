/* enagalar.uz helpers for motion-reels scenes (deterministic) */
const B = (() => {
  const { el, tf, rnd, clamp, lerp, p } = E;
  const ic = {
    pin: (s = 28, c = '#777169') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M12 21s-6.5-6.1-6.5-11A6.5 6.5 0 0 1 18.5 10c0 4.9-6.5 11-6.5 11z" fill="none" stroke="${c}" stroke-width="2.2"/><circle cx="12" cy="10" r="2.4" fill="${c}"/></svg>`,
    home: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M3.5 11 12 4l8.5 7M6 9.5V20h12V9.5" fill="none" stroke="${c}" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    shield: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M12 3 4.5 6v5.5c0 4.6 3.2 8 7.5 9.5 4.3-1.5 7.5-4.9 7.5-9.5V6z" fill="none" stroke="${c}" stroke-width="2.2" stroke-linejoin="round"/><path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="${c}" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
    chat: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M4 5.5h16v10H9l-5 4z" fill="none" stroke="${c}" stroke-width="2.2" stroke-linejoin="round"/></svg>`,
    lock: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><rect x="5" y="10.5" width="14" height="10" rx="2.5" fill="none" stroke="${c}" stroke-width="2.2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" fill="none" stroke="${c}" stroke-width="2.2"/></svg>`,
    send: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M4 12 20 4l-4 16-4.5-6.5z" fill="${c}"/></svg>`,
    warn: (s = 28, c = '#6a4402') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M12 3.5 21.5 20h-19z" fill="none" stroke="${c}" stroke-width="2.2" stroke-linejoin="round"/><path d="M12 10v4.5" stroke="${c}" stroke-width="2.4" stroke-linecap="round"/><circle cx="12" cy="17.3" r="1.3" fill="${c}"/></svg>`,
    clock: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><circle cx="12" cy="12" r="8.5" fill="none" stroke="${c}" stroke-width="2.2"/><path d="M12 7.5V12l3 2" fill="none" stroke="${c}" stroke-width="2.2" stroke-linecap="round"/></svg>`,
    bell: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15z M10 20.5a2 2 0 0 0 4 0" fill="none" stroke="${c}" stroke-width="2.2" stroke-linejoin="round"/></svg>`,
    paper: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M7 3.5h7l4 4v13H7z" fill="none" stroke="${c}" stroke-width="2.2" stroke-linejoin="round"/><path d="M9.5 12h6M9.5 15.5h6" stroke="${c}" stroke-width="2" stroke-linecap="round"/></svg>`,
    gift: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><rect x="4" y="9" width="16" height="11.5" rx="2" fill="none" stroke="${c}" stroke-width="2.2"/><path d="M3 9h18M12 9v11.5M12 9c-1.5-4-6-4-5.5-1.5S12 9 12 9zm0 0c1.5-4 6-4 5.5-1.5S12 9 12 9z" fill="none" stroke="${c}" stroke-width="2" stroke-linejoin="round"/></svg>`,
    plane: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><path d="M21 4 3 11.2l6 2.3L18 7l-7 8 .3 5.5 3-3.6 4.2 3.1z" fill="${c}"/></svg>`,
    family: (s = 28, c = '#fff') => `<svg viewBox="0 0 24 24" width="${s}" height="${s}"><circle cx="8" cy="6.5" r="2.6" fill="${c}"/><circle cx="16.5" cy="6.5" r="2.6" fill="${c}"/><circle cx="12.3" cy="13" r="2" fill="${c}"/><path d="M3.5 20v-5a4.5 4.5 0 0 1 9 0M12 20v-5a4.5 4.5 0 0 1 9 0" fill="none" stroke="${c}" stroke-width="2.1" stroke-linecap="round"/></svg>`,
  };
  /* Quchoq mark, animatable: arc draw + head + child dot */
  const mark = (parent, size, x, y, dark = true) => {
    const c1 = dark ? '#3eb5ba' : '#018186', c2 = dark ? '#feb38e' : '#c55205';
    const w = el('div', 'abs', parent, { left: x + 'px', top: y + 'px', width: size + 'px', height: size + 'px', transformOrigin: '50% 50%' },
      `<svg viewBox="0 0 64 64" width="${size}" height="${size}" style="overflow:visible"><path class="arc" d="M35.6 21.57A17.53 17.53 0 1 0 46.83 46.8" fill="none" stroke="${c1}" stroke-width="9.8" stroke-linecap="round"/><circle class="hd" cx="47.34" cy="10.73" r="7.73" fill="${c1}" style="transform-origin:47.34px 10.73px"/><circle class="kd" cx="31.36" cy="38.57" r="7.22" fill="${c2}" style="transform-origin:31.36px 38.57px"/></svg>`);
    const arc = w.querySelector('.arc'), hd = w.querySelector('.hd'), kd = w.querySelector('.kd');
    const L = arc.getTotalLength() + 1; arc.style.strokeDasharray = L;
    return {
      w, set(draw, head, kid) {
        arc.style.strokeDashoffset = (L * (1 - clamp(draw))).toFixed(2);
        arc.style.opacity = draw > 0.01 ? 1 : 0;
        hd.style.transform = `scale(${Math.max(0, head).toFixed(3)})`; kd.style.transform = `scale(${Math.max(0, kid).toFixed(3)})`;
      }
    };
  };
  /* brand background */
  const background = (stage) => {
    const bg = el('div', 'layer', stage); bg.id = 'bg';
    el('div', 'base', bg);
    const b1 = el('div', 'blob', bg, { width: '900px', height: '900px', background: '#0b9aa0', left: '-300px', top: '120px', opacity: .38 });
    const b2 = el('div', 'blob', bg, { width: '820px', height: '820px', background: '#c55205', left: '560px', top: '1050px', opacity: .26 });
    const b3 = el('div', 'blob', bg, { width: '640px', height: '640px', background: '#3eb5ba', left: '620px', top: '-160px', opacity: .2 });
    const warm = el('div', 'blob', bg, { width: '900px', height: '900px', background: '#f88950', left: '90px', top: '520px', opacity: 0 });
    el('div', '', bg, { position: 'absolute', left: 0, top: 0 }, `<svg width="1080" height="1920" viewBox="0 0 1080 1920" fill="none">
      <path d="M-20 214 Q540 176 1100 214" stroke="rgba(181,232,232,.14)" stroke-width="1.6"/>
      <path d="M-20 1552 Q540 1590 1100 1552" stroke="rgba(181,232,232,.14)" stroke-width="1.6"/>
      <path d="M58 -20 Q44 960 58 1940" stroke="rgba(181,232,232,.08)" stroke-width="1.4"/>
      <path d="M1022 -20 Q1036 960 1022 1940" stroke="rgba(181,232,232,.08)" stroke-width="1.4"/>
      <path d="M170 1668 L910 1668" stroke="rgba(181,232,232,.22)" stroke-width="2"/></svg>`);
    const orn = [[170, 7, '#81d3d5'], [300, 10, '#81d3d5'], [540, 13, '#feb38e'], [780, 10, '#81d3d5'], [910, 7, '#81d3d5']].map(([x, r, c]) =>
      el('div', 'abs', bg, { left: (x - r) + 'px', top: (1668 - r) + 'px', width: r * 2 + 'px', height: r * 2 + 'px', borderRadius: '50%', background: c, boxShadow: `0 0 16px ${c}` }));
    const dust = [];
    for (let i = 0; i < 44; i++) {
      const s = 2 + rnd(i) * 5;
      const d = el('div', 'abs', bg, { width: s + 'px', height: s + 'px', borderRadius: '50%', background: rnd(i + 1) > .65 ? '#feb38e' : '#b5e8e8' });
      dust.push({ d, x: rnd(i + 2) * 1080, y: rnd(i + 3) * 1920, v: 8 + rnd(i + 4) * 20, a: .12 + rnd(i + 5) * .4, ph: rnd(i + 6) * 6.28 });
    }
    el('div', 'vig', bg);
    const grain = el('div', 'grain', bg);
    return {
      bg, warm, update(t, cx = 0, cy = 0) {
        tf(b1, { x: Math.sin(t * .23) * 90 - cx * .06, y: Math.cos(t * .19) * 70 - cy * .04 });
        tf(b2, { x: Math.cos(t * .17) * 110 - cx * .05, y: Math.sin(t * .21) * 80 - cy * .03 });
        tf(b3, { x: Math.sin(t * .13 + 1) * 70 - cx * .08, y: Math.cos(t * .16) * 50 });
        orn.forEach((o, i) => tf(o, { s: 1 + .18 * Math.sin(t * 2.1 + i), o: .5 + .3 * Math.sin(t * 1.7 + i * 1.3) }));
        dust.forEach(q => {
          let y = (q.y - t * q.v - cy * .15) % 1920; if (y < 0) y += 1920;
          let x = (q.x + Math.sin(t * .6 + q.ph) * 18 - cx * .15) % 1080; if (x < 0) x += 1080;
          tf(q.d, { x, y, o: q.a * (.6 + .4 * Math.sin(t * 1.3 + q.ph)) });
        });
        const f = Math.floor(t * 24);
        grain.style.transform = `translate(${(rnd(f) * 200) | 0}px,${(rnd(f + 99) * 200) | 0}px)`;
      }
    };
  };
  /* camera over a world of scene layers */
  const camera = (pans, start) => (t) => {
    let cur = start;
    for (const [t0, t1, a, b] of pans) {
      if (t >= t1) { cur = b; continue; }
      if (t >= t0) { const q = (t - t0) / (t1 - t0), e = E.ease.inOutExpo(q); return { x: lerp(a[0], b[0], e), y: lerp(a[1], b[1], e), z: 1 - .07 * Math.sin(Math.PI * q), blur: 13 * Math.pow(Math.sin(Math.PI * q), 1.4) }; }
      break;
    }
    return { x: cur[0], y: cur[1], z: 1, blur: 0 };
  };
  /* particle burst */
  const burst = (parent, n, seed) => [...Array(n)].map((_, i) => el('div', 'abs', parent, {}, `<div style="width:${10 + (i % 3) * 4}px;height:${10 + (i % 3) * 4}px;border-radius:50%;background:${['#3eb5ba', '#f88950', '#feb38e', '#81d3d5', '#fcfaf7'][i % 5]}"></div>`));
  const updBurst = (list, t, t0, cx, cy, seed = 0, up = true) => list.forEach((b, i) => {
    const q = clamp((t - t0 - rnd(i + seed) * .08) / 1.1), a = (up ? -Math.PI / 2 : 0) + (rnd(i + 5 + seed) - .5) * (up ? 2.8 : 6.28), v = 300 + rnd(i + 2 + seed) * 420;
    tf(b, { x: cx + Math.cos(a) * v * q - 8, y: cy + Math.sin(a) * v * q + (up ? 480 * q * q : 0) - 8, s: 1 - .5 * q, o: q > 0 && q < 1 ? (1 - q) * 1.2 : 0 });
  });
  /* ring pulses */
  const rings = (parent, n, cx, cy, r, color) => [...Array(n)].map((_, i) => el('div', 'abs', parent, { left: (cx - r) + 'px', top: (cy - r) + 'px', width: r * 2 + 'px', height: r * 2 + 'px', borderRadius: '50%', border: (3 - i * .7) + 'px solid ' + color, boxShadow: `0 0 24px ${color}`, transformOrigin: '50% 50%' }));
  const updRings = (list, t, t0, spread = 2.3) => list.forEach((r, i) => { const q = clamp((t - t0 - i * .14) / .8); tf(r, { s: .2 + spread * E.ease.outCubic(q), o: q > 0 && q < 1 ? (1 - q) * .85 : 0 }); });
  const caret = (t, on = true) => `<span class="caret" style="opacity:${on && Math.floor(t * 2.4) % 2 === 0 ? 1 : 0}"></span>`;
  const loadFonts = () => Promise.all(['800', '900'].map(w => document.fonts.load(`${w} 40px Mulish`, 'Aʻoʼ Жяў')).concat(['400', '500', '600', '700'].map(w => document.fonts.load(`${w} 40px Inter`, 'Aʻoʼ Жяў'))));
  /* Mulish draws U+02BB with a very wide advance; swap to U+2018 (same turned-comma shape) in Mulish text only */
  const fixApos = (root) => {
    const tw = document.createTreeWalker(root, NodeFilter.SHOW_TEXT); const ns = [];
    while (tw.nextNode()) ns.push(tw.currentNode);
    ns.forEach(n => { if (/[\u02BB\u02BC]/.test(n.textContent) && getComputedStyle(n.parentElement).fontFamily.includes('Mulish')) n.textContent = n.textContent.replace(/\u02BB/g, '\u2018').replace(/\u02BC/g, '\u2019'); });
  };
  return { fixApos, ic, mark, background, camera, burst, updBurst, rings, updRings, caret, loadFonts };
})();
