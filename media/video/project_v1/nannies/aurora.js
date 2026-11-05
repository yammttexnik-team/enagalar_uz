/* expert-reels STYLE PRESET "aurora" — components (requires engine.js). Reference: cinematic teal SaaS promo
   (aurora arc light, grid + diamonds, glossy extruded 4-point star mascot, perspective-tilted UI, camera fly-throughs). */
const AU = (() => {
  const { el, tf, p, win, kf, clamp, lerp, rnd } = E;
  let uid = 0;
  const STAR = 'M50 0 C53.5 36 64 46.5 100 50 C64 53.5 53.5 64 50 100 C46.5 64 36 53.5 0 50 C36 46.5 46.5 36 50 0Z';

  /** background: base + aurora arc + grid/diamonds + bokeh. update(t,{arcY,arcO,gridO,camx,camy}) */
  const background = (stage) => {
    const bg = el('div', 'layer', stage); bg.id = 'bg';
    el('div', 'au-bg', bg);
    const arc = el('div', 'au-arc', bg, { left: '-310px', top: '-1250px' });
    let g = '<svg width="1080" height="1920" viewBox="0 0 1080 1920"><defs><pattern id="aug" width="90" height="90" patternUnits="userSpaceOnUse"><path d="M90 0 L0 0 0 90" fill="none" stroke="rgba(120,190,255,.09)" stroke-width="1.4"/></pattern>' +
      '<radialGradient id="augm" cx=".5" cy=".5" r=".6"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient><mask id="augk"><rect width="1080" height="1920" fill="url(#augm)"/></mask></defs>' +
      '<g mask="url(#augk)"><rect width="1080" height="1920" fill="url(#aug)"/>';
    for (let i = 0; i < 22; i++) { const x = Math.floor(rnd(i + 40) * 12) * 90, y = Math.floor(rnd(i + 80) * 21) * 90; g += `<rect x="${x - 7}" y="${y - 7}" width="14" height="14" fill="rgba(70,130,255,.75)" transform="rotate(45 ${x} ${y})"/>`; }
    g += '</g></svg>';
    const grid = el('div', 'au-grid', bg, {}, g);
    const bokeh = [...Array(9)].map((_, i) => { const s = 30 + rnd(i + 3) * 50; return { d: el('div', 'au-bokeh', bg, { width: s + 'px', height: s + 'px' }), x: 100 + rnd(i + 5) * 880, y: 300 + rnd(i + 7) * 1250, ph: rnd(i) * 6 }; });
    const grain = el('div', 'grain', bg, { opacity: .04 });
    return {
      bg, arc, grid,
      update(t, o = {}) {
        const { arcX = 0, arcY = 0, arcS = 1, arcO = 1, gridO = 0, bokehO = .6, camx = 0, camy = 0 } = o;
        tf(arc, { x: arcX + Math.sin(t * .3) * 20, y: arcY, s: arcS * (1 + .015 * Math.sin(t * .8)), r: Math.sin(t * .2) * 4, o: arcO });
        grid.style.opacity = gridO.toFixed(3);
        tf(grid, { x: (-camx * .2) % 90, y: (-camy * .2) % 90 });
        bokeh.forEach((b, i) => tf(b.d, { x: b.x + Math.sin(t * .5 + b.ph) * 30 - camx * .1, y: b.y + Math.cos(t * .4 + b.ph) * 40 - camy * .1, o: bokehO * (.4 + .6 * Math.abs(Math.sin(t * .7 + b.ph))) }));
        const f = Math.floor(t * 24); grain.style.transform = `translate(${(rnd(f) * 200) | 0}px,${(rnd(f + 99) * 200) | 0}px)`;
      }
    };
  };

  /** glossy extruded teal star. returns {el, inner}; spin with spin(star, ry, rx, rz) */
  const star3d = (parent, size, x, y) => {
    const id = 'st' + (uid++);
    const box = el('div', 'abs', parent, { left: x - size / 2 + 'px', top: y - size / 2 + 'px', width: size + 'px', height: size + 'px', perspective: size * 6 + 'px' });
    const inner = el('div', 'p3d', box, { position: 'absolute', inset: 0, transformStyle: 'preserve-3d' });
    const depth = 9;
    for (let i = depth; i >= 0; i--) {
      const front = i === 0, back = i === depth;
      const fill = (front || back) ? `url(#${id}g)` : `hsl(176,${62 - i * 2}%,${30 + (depth - i) * 2.2}%)`;
      const defs = back ? `<defs><linearGradient id="${id}g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#b8fff6"/><stop offset=".35" stop-color="#2de2d4"/><stop offset=".6" stop-color="#0f8f8a"/><stop offset=".85" stop-color="#54e9dd"/><stop offset="1" stop-color="#0b5d63"/></linearGradient>
        <linearGradient id="${id}h" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".85"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
        <clipPath id="${id}c"><path d="${STAR}"/></clipPath></defs>` : '';
      const hl = (front || back) ? `<g clip-path="url(#${id}c)"><rect class="hl" x="-60" y="-10" width="40" height="120" fill="url(#${id}h)" transform="rotate(20 50 50)"/></g>` : '';
      el('div', '', inner, { position: 'absolute', inset: 0, transform: `translateZ(${(-i * size * .011).toFixed(2)}px)`, backfaceVisibility: 'visible' },
        `<svg viewBox="0 0 100 100" width="${size}" height="${size}" style="overflow:visible">${defs}<path d="${STAR}" fill="${fill}"/>${hl}</svg>`);
    }
    box.style.filter = 'drop-shadow(0 0 ' + (size * .12).toFixed(0) + 'px rgba(45,226,212,.45))';
    return { el: box, inner, hl: box.querySelector('.hl'), size };
  };
  const spin = (st, ry = 0, rx = 0, rz = 0) => {
    st.inner.style.transform = `rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) rotateZ(${rz.toFixed(2)}deg)`;
    if (st.hl) st.hl.setAttribute('x', (-60 + ((ry % 360 + 360) % 360) / 360 * 200).toFixed(1));
  };

  /** 3D camera: wrapper with perspective; world transform for tilt / fly-through */
  const cam3d = (parent, persp = 1600) => {
    const view = el('div', 'layer', parent, { perspective: persp + 'px', perspectiveOrigin: '50% 45%' });
    const world = el('div', 'layer p3d', view, { transformStyle: 'preserve-3d' });
    return { view, world, set(o = {}) { const { x = 0, y = 0, z = 0, rx = 0, ry = 0, rz = 0, s = 1 } = o;
      world.style.transform = `translate3d(${x}px,${y}px,${z}px) rotateX(${rx}deg) rotateY(${ry}deg) rotateZ(${rz}deg) scale(${s})`; } };
  };

  /** simple line icons for tool grids (no brand logos) */
  const ic = (name, size = 84, c = '#e8f1ff') => {
    const P = {
      video: '<rect x="8" y="16" width="34" height="28" rx="6"/><path d="M42 26 L56 18 V42 L42 34Z"/>',
      image: '<rect x="8" y="12" width="48" height="40" rx="7"/><circle cx="22" cy="26" r="5"/><path d="M8 46 L24 32 L36 42 L44 34 L56 46"/>',
      music: '<path d="M24 46 V14 L50 9 V40"/><circle cx="18" cy="46" r="6"/><circle cx="44" cy="40" r="6"/>',
      doc: '<path d="M16 6 H38 L50 18 V58 H16Z"/><path d="M38 6 V18 H50 M23 30 H43 M23 38 H43 M23 46 H36"/>',
      code: '<path d="M22 18 L8 32 L22 46 M42 18 L56 32 L42 46 M36 12 L28 52"/>',
      chart: '<path d="M8 56 H56"/><rect x="12" y="34" width="9" height="18" rx="2"/><rect x="28" y="22" width="9" height="30" rx="2"/><rect x="44" y="12" width="9" height="40" rx="2"/>',
      mic: '<rect x="23" y="6" width="18" height="32" rx="9"/><path d="M14 30 a18 18 0 0 0 36 0 M32 48 V58 M22 58 H42"/>',
      globe: '<circle cx="32" cy="32" r="24"/><path d="M8 32 H56 M32 8 C20 22 20 42 32 56 C44 42 44 22 32 8"/>',
      spark: '<path d="M32 6 C34 24 40 30 58 32 C40 34 34 40 32 58 C30 40 24 34 6 32 C24 30 30 24 32 6Z"/>',
      chat: '<path d="M8 14 H56 V42 H28 L16 52 V42 H8Z"/>',
      search: '<circle cx="28" cy="28" r="16"/><path d="M40 40 L54 54"/>',
      user: '<circle cx="32" cy="22" r="10"/><path d="M12 54 C14 40 50 40 52 54"/>',
    };
    return `<svg viewBox="0 0 64 64" width="${size}" height="${size}" fill="none" stroke="${c}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round">${P[name] || P.spark}</svg>`;
  };
  return { background, star3d, spin, cam3d, ic, STAR };
})();
