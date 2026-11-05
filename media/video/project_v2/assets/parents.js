/* parents scenes (v2): hard cuts, slide-up text, big UI. Texts from TX[LANG]. */
const { p, win, kf, tf, el, clamp, lerp, rnd, icon } = E;
const PX = {};
const ac = s => `<span class="acc">${s}</span>`;

/* HOOK: big question on frame 0, chaos bubbles, sweep -> "one site" (sweep at o.sw) */
PX.hook = (Lr, cue, dur, X, o = {}) => {
  const sw = o.sw ?? 1.5;
  const h1 = V.T(Lr, X.h1a, 650, 112), h2 = V.T(Lr, X.h1b, 772, 112);
  const s1 = V.T(Lr, X.sub1, 930, 54, 'Bd'), s2 = V.T(Lr, X.sub2, 998, 54, 'Bd');
  const bub = X.bubs.map(([tx, x, y, r], i) => ({ e: el('div', 'bub qa', Lr, { left: x + 'px', top: y + 'px' }, tx), r, i }));
  const mk = B.mark(Lr, 170, 455, 330);
  const o1 = V.T(Lr, X.one1, 560, 56, 'Bd'), o2 = V.T(Lr, X.one2, 636, 56, 'Bd'), o3 = V.T(Lr, ac(X.one3), 730, 128);
  const rows = X.regions.map((row, ri) => {
    const r = el('div', 'T', Lr, { top: (930 + ri * 112) + 'px', display: 'flex', justifyContent: 'center', gap: '18px' });
    return row.map(nm => el('div', 'chip qa', r, { position: 'relative' }, `${B.ic.pin(34, '#81d3d5')}${nm}`));
  }).flat();
  cue(0, 'whoosh', .35); cue(0.02, 'tap', .4);
  bub.forEach(({ i }) => cue(.3 + i * .08, 'bubble', .3, i % 2 ? .4 : -.4));
  cue(.8, 'tap', .4);
  cue(sw, 'swish', .55); cue(sw + .1, 'pop', .6); cue(sw + .15, 'sparkle', .4); cue(sw + .25, 'tap', .4); cue(sw + .45, 'shimmer', .45);
  rows.forEach((_, i) => cue(sw + .5 + i * .07, 'pop_soft', .25, (i % 3 - 1) * .4));
  return lt => {
    const out = p(lt, sw, .22, 'inCubic');
    const s0 = 1;
    [h1, h2].forEach(e => tf(e, { s: s0, y: -out * 60, o: 1 - out }));
    [s1, s2].forEach((e, i) => { const q = p(lt, .8 + i * .06, .3, 'outExpo'); tf(e, { y: (1 - q) * 40 - out * 60, o: clamp((lt - .8 - i * .06) / .12) * (1 - out) }); });
    bub.forEach(({ e, r, i }) => {
      const q = p(lt, .3 + i * .08, .4, 'outBack'), fly = p(lt, sw + i * .02, .35, 'inCubic'), dir = e.offsetLeft + 160 < 540 ? -1 : 1;
      tf(e, { x: dir * fly * 800, y: Math.sin(lt * 1.6 + i) * 6, s: .6 + .4 * q, r: r + Math.sin(lt + i) * 1.5, o: clamp((lt - .3 - i * .08) / .1) * (1 - fly) });
    });
    const t2 = sw + .08;
    const mq = p(lt, t2, .45, 'outBack'); tf(mk.w, { s: mq, o: clamp((lt - t2) / .1), y: Math.sin(lt * 2) * 5 }); mk.set(p(lt, t2, .4), p(lt, t2 + .1, .3, 'outBack'), p(lt, t2 + .2, .3, 'outBack'));
    V.up(o1, lt, t2 + .1); V.up(o2, lt, t2 + .18); V.up(o3, lt, t2 + .3, 50);
    rows.forEach((c, i) => V.pop(c, lt, sw + .5 + i * .07));
  };
};

/* FILTER: phone, one continuous take, cards slide up at o.cards and hold */
PX.filter = (Lr, cue, dur, X) => {
  const c1 = V.T(Lr, X.f1a, 270, 84), c2 = V.T(Lr, X.f1b, 370, 84);
  const d1 = V.T(Lr, X.f2a, 268, 50, 'Bd'), d2 = V.T(Lr, X.f2b, 334, 50, 'Bd'), d3 = V.T(Lr, ac(X.f2c), 400, 64);
  const { ph, sc } = V.phone(Lr, 130, 510, 820, 1030);
  el('div', 'abs', sc, { left: '316px', top: '18px', width: '160px', height: '40px', borderRadius: '20px', background: '#0b1214' });
  el('div', 'abs', sc, { left: '40px', top: '74px', font: '900 56px var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, X.ph);
  const fld = (lab, y) => { el('div', 'lab', sc, { left: '42px', top: y + 'px' }, lab); const f = el('div', 'fld', sc, { left: '40px', top: (y + 56) + 'px', width: '712px' }); const v = el('span', '', f, {}, X.choose); el('span', '', f, {}, icon.chevron(40, '#777169')); return { f, v }; };
  const F1 = fld(X.lv, 166), F2 = fld(X.lt, 356);
  el('div', 'lab', sc, { left: '42px', top: '546px' }, X.lw);
  const seg = el('div', 'abs', sc, { left: '40px', top: '602px', width: '712px', height: '112px', borderRadius: '26px', background: 'var(--s100)', border: '3px solid var(--s200)' });
  const on = el('div', 'abs', seg, { left: '6px', top: '6px', width: '346px', height: '94px', borderRadius: '20px', background: 'var(--t500)', transformOrigin: '50% 50%' });
  const oA = el('div', 'abs', seg, { left: '6px', top: '6px', width: '346px', height: '94px', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '10px', font: '700 44px var(--ui)', color: 'var(--s700)', whiteSpace: 'nowrap' }, X.oA);
  el('div', 'abs', seg, { left: '358px', top: '6px', width: '346px', height: '94px', display: 'flex', alignItems: 'center', justifyContent: 'center', font: '700 44px var(--ui)', color: 'var(--s700)', whiteSpace: 'nowrap' }, X.oB);
  const btn = el('div', 'btn', sc, { left: '40px', top: '752px', width: '712px', height: '112px', transformOrigin: '50% 50%' }, `${icon.search(46, '#fff')}<span>${X.show}</span>`);
  const res = el('div', 'abs', sc, { left: 0, top: '140px', width: '792px', height: '900px', background: 'var(--s50)' });
  const cards = X.people.map(([n, ini, col, sub, ver], i) => {
    const c = el('div', 'card', res, { left: '40px', top: (14 + i * 272) + 'px', width: '712px', height: '250px' });
    el('div', 'av', c, { left: '28px', top: '30px', width: '130px', height: '130px', background: col }, ini);
    el('div', 'abs', c, { left: '186px', top: '30px', font: '900 54px var(--head)', color: 'var(--s900)' }, n);
    el('div', 'abs', c, { left: '186px', top: '98px', font: '500 44px var(--ui)', color: 'var(--s500)', whiteSpace: 'nowrap' }, sub);
    el('div', 'abs', c, { left: '186px', top: '162px' }, ver ? `<span class="badge">${icon.check(34, '#018186', 3.4)}${X.ver}</span>` : `<span class="badge" style="background:var(--a50);border-color:#fed4be;color:#963700">${X.newb}</span>`);
    return c;
  });
  const cur = E.mkCursor(Lr);
  const SX = 144, SY = 524; // screen origin in layer
  const T = { v: .55, t: .95, s: 1.4, b: 1.85, r: 2.0, cap: 1.45 };
  cue(0, 'whoosh_s', .4); cue(.05, 'tap', .4);
  cue(T.v, 'click', .8); cue(T.v + .05, 'select', .5); cue(T.t, 'click', .8); cue(T.t + .05, 'select', .5);
  cue(T.s, 'click', .85); cue(T.s + .05, 'pop_soft', .5); cue(T.cap, 'tap', .4); cue(T.cap + .3, 'shimmer', .35);
  cue(T.b, 'click', .85); cue(T.b + .05, 'confirm', .5); cue(T.r, 'whoosh_s', .4); cards.forEach((_, i) => cue(T.r + .1 + i * .12, 'pop_soft', .35, (i - 1) * .3));
  return lt => {
    const q = V.up(ph, lt, 0, 120, .4);
    const cap1 = 1 - p(lt, T.cap - .15, .15, 'inCubic');
    V.up(c1, lt, 0); V.up(c2, lt, .06); c1.style.opacity *= cap1; c2.style.opacity *= cap1;
    V.up(d1, lt, T.cap); V.up(d2, lt, T.cap + .08); V.up(d3, lt, T.cap + .18);
    const setF = (Fd, tc, val) => { const ok = lt >= tc; Fd.v.textContent = ok ? val : X.choose; Fd.v.style.color = ok ? 'var(--s900)' : 'var(--s400)'; Fd.f.classList.toggle('on', lt >= tc - .1 && lt < tc + .5); };
    setF(F1, T.v + .05, X.vv); setF(F2, T.t + .05, X.tv);
    const sq = p(lt, T.s + .05, .3, 'outBack'); tf(on, { o: clamp((lt - T.s) / .08), s: .85 + .15 * sq }); oA.style.color = lt >= T.s + .05 ? '#fff' : 'var(--s700)';
    tf(btn, { s: 1 - .05 * V.press(lt, T.b) });
    const rq = p(lt, T.r, .45, 'outExpo'); tf(res, { y: (1 - rq) * 950, o: clamp((lt - T.r) / .06) });
    cards.forEach((c, i) => { const k = p(lt, T.r + .1 + i * .12, .45, 'outBack'); tf(c, { y: (1 - k) * 70, o: clamp((lt - T.r - .1 - i * .12) / .1) }); });
    cur.update(lt, [[.15, 990, 1500], [.5, SX + 560, SY + 274, 'outCubic'], [.6, SX + 560, SY + 274], [.9, SX + 560, SY + 464, 'outCubic'], [1.05, SX + 560, SY + 464], [1.35, SX + 220, SY + 660, 'outCubic'], [1.5, SX + 220, SY + 660], [1.8, SX + 396, SY + 808, 'outCubic'], [1.95, SX + 396, SY + 808], [2.4, 990, 1500, 'inCubic']], [T.v, T.t, T.s, T.b], [.15, 2.35]);
  };
};

/* VERIFY: big profile card + badge pop */
PX.verify = (Lr, cue, dur, X) => {
  const a = V.T(Lr, X.v1, 280, 56, 'Bd'), b = V.T(Lr, X.v2, 352, 56, 'Bd');
  const card = el('div', 'card qa', Lr, { left: '120px', top: '480px', width: '840px', height: '640px' });
  el('div', 'av', card, { left: '310px', top: '44px', width: '220px', height: '220px', background: 'linear-gradient(135deg,#3eb5ba,#018186)', fontSize: '96px' }, X.ini);
  el('div', 'abs', card, { left: 0, top: '290px', width: '840px', textAlign: 'center', font: '900 80px var(--head)', color: 'var(--s900)' }, X.name);
  el('div', 'abs', card, { left: 0, top: '392px', width: '840px', textAlign: 'center', font: '500 46px var(--ui)', color: 'var(--s500)' }, X.vsub);
  const bd = el('div', 'abs', card, { left: '120px', top: '480px', width: '600px', height: '120px', borderRadius: '60px', background: 'var(--t500)', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: '18px', font: '900 60px var(--head)', color: '#fff', boxShadow: '0 16px 40px rgba(1,129,134,.45)', transformOrigin: '50% 50%', whiteSpace: 'nowrap' }, `${icon.check(64, '#fff', 3.6)}<span>${X.ver}</span>`);
  const ring = el('div', 'abs', Lr, { left: '240px', top: '960px', width: '600px', height: '120px', borderRadius: '60px', border: '5px solid #81d3d5', transformOrigin: '50% 50%' });
  const bu = B.burst(Lr, 18, 7);
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4); cue(.1, 'tap', .35); cue(.5, 'pop', .8); cue(.55, 'success', .5); cue(.55, 'sparkle', .5);
  return lt => {
    V.up(a, lt, 0); V.up(b, lt, .06); V.up(card, lt, .05, 90, .4);
    const q = p(lt, .5, .45, 'outBack'); tf(bd, { s: lerp(1.6, 1, q), o: clamp((lt - .5) / .08) });
    const r = clamp((lt - .6) / .7); tf(ring, { s: 1 + .3 * E.ease.outCubic(r), o: r > 0 && r < 1 ? (1 - r) * .9 : 0 });
    B.updBurst(bu, lt, .55, 540, 1020, 7, false);
  };
};

/* CHAT: pasted message, reply, full-width warning */
PX.chat = (Lr, cue, dur, X, o = {}) => {
  const tw = o.warn ?? 1.6;
  const a = V.T(Lr, X.c1, 270, 96), b = V.T(Lr, ac(X.c2), 372, 96);
  const card = el('div', 'card qa', Lr, { left: '120px', top: '510px', width: '840px', height: '700px', overflow: 'hidden' });
  el('div', 'av', card, { left: '30px', top: '24px', width: '84px', height: '84px', background: 'linear-gradient(135deg,#3eb5ba,#018186)', fontSize: '40px' }, X.ini);
  el('div', 'abs', card, { left: '136px', top: '20px', font: '900 48px var(--head)', color: 'var(--s900)' }, X.name);
  el('div', 'abs', card, { left: '136px', top: '74px', font: '500 44px var(--ui)', color: 'var(--s500)', display: 'flex', alignItems: 'center', gap: '8px', whiteSpace: 'nowrap' }, `${B.ic.lock(38, '#777169')}${X.inner}`);
  el('div', 'abs', card, { left: 0, top: '140px', width: '840px', height: '3px', background: 'var(--s200)' });
  const m1 = el('div', 'msg', card, { right: '30px', left: 'auto', top: '172px', maxWidth: '700px', background: 'var(--t500)', color: '#fff', borderBottomRightRadius: '10px', transformOrigin: '100% 100%' }, X.m1);
  const m2 = el('div', 'msg', card, { left: '30px', top: '400px', maxWidth: '700px', background: 'var(--s100)', color: 'var(--s900)', borderBottomLeftRadius: '10px', transformOrigin: '0 100%' }, X.m2);
  const inb = el('div', 'abs', card, { left: '30px', top: '584px', width: '660px', height: '96px', borderRadius: '48px', background: 'var(--s100)', border: '3px solid var(--s200)', font: '400 44px var(--ui)', color: 'var(--s400)', padding: '18px 34px', whiteSpace: 'nowrap' }, X.phc);
  el('div', 'abs', card, { left: '712px', top: '584px', width: '96px', height: '96px', borderRadius: '50%', background: 'var(--t500)', display: 'flex', alignItems: 'center', justifyContent: 'center' }, B.ic.send(44));
  const wr = el('div', 'warn qa', Lr, { left: '120px', top: '1250px', width: '840px', height: '160px', justifyContent: 'center', padding: '0 24px', transformOrigin: '50% 50%' }, `${B.ic.warn(64, '#563501')}<span>${X.warn}</span>`);
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4); cue(.35, 'send', .6); cue(.38, 'pop_soft', .45); cue(1.0, 'bubble', .5); cue(tw, 'error', .35); cue(tw + .02, 'pop', .6);
  return lt => {
    V.up(a, lt, 0); V.up(b, lt, .07); V.up(card, lt, .05, 90, .4);
    V.pop(m1, lt, .35); V.pop(m2, lt, 1.0);
    const w = p(lt, tw, .4, 'outBack'); tf(wr, { s: .7 + .3 * w, o: clamp((lt - tw) / .08), r: lt > tw + .4 && lt < tw + .9 ? Math.sin((lt - tw - .4) * 25) * 1.2 * (1 - (lt - tw - .4) / .5) : 0 });
  };
};

/* BONUS */
PX.bonus = (Lr, cue, dur, X) => {
  const a = V.T(Lr, X.b1, 270, 104), b = V.T(Lr, ac(X.b2), 384, 132);
  const coin = el('div', 'abs', Lr, { left: '450px', top: '590px', width: '180px', height: '180px', borderRadius: '50%', background: 'radial-gradient(circle at 35% 30%,#feb38e,#f88950 45%,#c55205)', boxShadow: '0 0 60px rgba(248,137,80,.55)', display: 'flex', alignItems: 'center', justifyContent: 'center' }, B.ic.gift(96, '#fff'));
  const c = V.T(Lr, X.b3, 820, 54, 'Bd'), d = V.T(Lr, ac(X.b4), 896, 84), e = V.T(Lr, X.b5, 1010, 46, 'Bd', { color: 'var(--s200)' });
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .45); cue(.12, 'shimmer', .4); cue(.3, 'pop', .6); cue(.5, 'chime', .5); cue(.55, 'tap', .35);
  return lt => {
    V.up(a, lt, 0); V.up(b, lt, .08, 50);
    V.pop(coin, lt, .3, .5, { y: Math.sin(lt * 2) * 6, r: Math.sin(lt * 1.5) * 5 });
    V.up(c, lt, .45); V.up(d, lt, .52); V.up(e, lt, .6);
  };
};

/* END card */
PX.end = (Lr, cue, dur, X0, o = {}) => {
  const X = o.short ? Object.assign({}, X0, { e1: X0.e1s, e2: X0.e2s, chips: X0.chipsS }) : X0;
  const mk = B.mark(Lr, 170, 455, 290);
  const h = V.T(Lr, X.e1, 500, 104);
  const bt = el('div', 'btn apr qa', Lr, { left: '150px', top: '680px', width: '780px', height: '144px', borderRadius: '72px', fontSize: '60px', transformOrigin: '50% 50%' }, `<span>${X.e2}</span>${V.arrow(60)}`);
  const url = V.T(Lr, 'enagalar<span style="color:var(--s400)">.uz</span>', 880, 124);
  const ch = X.chips.map((tx, i) => el('div', 'chip qa', Lr, { left: '50%', top: (1070 + i * 112) + 'px', fontSize: '42px' }, `${i === 0 ? B.ic.plane(40, '#81d3d5') : icon.check(40, '#81d3d5', 3.4)}<span>${tx}</span>`));
  const bu = B.burst(Lr, 24, 3); const cur = E.mkCursor(Lr);
  const tc = Math.min(1.4, dur - 1.6);
  cue(0, 'whoosh', .45); cue(.05, 'pop', .6); cue(.1, 'magic', .45); cue(.2, 'tap', .45); cue(.35, 'pop', .55); cue(.5, 'reveal', .45);
  ch.forEach((_, i) => cue(.65 + i * .1, 'pop_soft', .35));
  cue(tc, 'click', .9); cue(tc + .05, 'success', .7); cue(tc + .08, 'sparkle', .5);
  return lt => {
    const mq = p(lt, 0, .45, 'outBack'); tf(mk.w, { s: mq, y: Math.sin(lt * 2) * 5 }); mk.set(p(lt, 0, .4), p(lt, .1, .3, 'outBack'), p(lt, .2, .3, 'outBack'));
    V.up(h, lt, .15);
    const q = p(lt, .35, .45, 'outBack'); tf(bt, { s: (.7 + .3 * q) * (1 - .05 * V.press(lt, tc)) * (1 + .015 * Math.sin(lt * 5) * (lt > 1 ? 1 : 0)), o: clamp((lt - .35) / .08) });
    V.up(url, lt, .5);
    ch.forEach((c, i) => { const k = p(lt, .65 + i * .1, .4, 'outBack'); c.style.transform = `translateX(-50%) scale(${(.7 + .3 * k).toFixed(3)})`; c.style.opacity = clamp((lt - .65 - i * .1) / .1).toFixed(3); });
    B.updBurst(bu, lt, tc + .05, 540, 752, 3);
    cur.update(lt, [[tc - .6, 1000, 1450], [tc - .05, 640, 770, 'outCubic'], [tc + .5, 640, 770], [tc + 1.1, 990, 1450, 'inCubic']], [tc], [tc - .6, tc + 1.05]);
  };
};

/* SHORT: price hook */
PX.priceHook = (Lr, cue, dur, X) => {
  const h1 = V.T(Lr, X.q1, 640, 120), h2 = V.T(Lr, ac(X.q2), 774, 150);
  const bub = X.qb.map(([tx, x, y, r], i) => ({ e: el('div', 'bub qa', Lr, { left: x + 'px', top: y + 'px' }, tx), r, i }));
  cue(0, 'whoosh', .35); cue(.02, 'tap', .4); bub.forEach(({ i }) => cue(.25 + i * .1, 'bubble', .3, i % 2 ? .4 : -.4));
  return lt => {
    const s0 = 1; [h1, h2].forEach(e => tf(e, { s: s0 }));
    bub.forEach(({ e, r, i }) => V.pop(e, lt, .25 + i * .1, .4, { y: Math.sin(lt * 1.6 + i) * 6, r }));
  };
};
/* SHORT: price page mock */
PX.prices = (Lr, cue, dur, X) => {
  const a = V.T(Lr, X.pr1, 270, 96), b = V.T(Lr, ac(X.pr2), 380, 64);
  const { ph, sc } = V.phone(Lr, 130, 510, 820, 1030);
  el('div', 'abs', sc, { left: '40px', top: '28px', width: '712px', height: '84px', borderRadius: '42px', background: 'var(--s100)', display: 'flex', alignItems: 'center', gap: '12px', padding: '0 28px', font: '600 44px var(--ui)', color: 'var(--s700)', whiteSpace: 'nowrap' }, `${B.ic.lock(36, '#018186')}enagalar.uz/narxlar`);
  el('div', 'abs', sc, { left: '40px', top: '146px', font: '900 58px var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, X.prT);
  const seg = el('div', 'abs', sc, { left: '40px', top: '240px', width: '712px', height: '100px', borderRadius: '24px', background: 'var(--s100)', border: '3px solid var(--s200)', display: 'flex', padding: '6px' });
  X.units.forEach((u, i) => el('div', '', seg, { flex: 1, borderRadius: '18px', display: 'flex', alignItems: 'center', justifyContent: 'center', font: '700 44px var(--ui)', background: i === 0 ? 'var(--t500)' : 'transparent', color: i === 0 ? '#fff' : 'var(--s700)' }, u));
  const cards = X.sides.map((s, i) => {
    const c = el('div', 'card', sc, { left: '40px', top: (380 + i * 290) + 'px', width: '712px', height: '260px' });
    el('div', 'abs', c, { left: '34px', top: '30px', font: '600 44px var(--ui)', color: 'var(--s500)', whiteSpace: 'nowrap' }, s);
    el('div', 'abs', c, { left: '34px', top: '110px', width: '380px', height: '84px', borderRadius: '20px', background: i ? 'linear-gradient(90deg,#fed4be,#ffebe0)' : 'linear-gradient(90deg,#b5e8e8,#d9f4f4)' });
    el('div', 'abs', c, { left: '440px', top: '112px', font: '900 64px var(--head)', color: 'var(--s900)' }, X.som);
    return c;
  });
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4); cue(.1, 'tap', .35); cards.forEach((_, i) => cue(.45 + i * .15, 'pop_soft', .4));
  return lt => {
    V.up(a, lt, 0); V.up(b, lt, .08); V.up(ph, lt, 0, 120, .4);
    cards.forEach((c, i) => { V.up(c, lt, .45 + i * .15, 60); });
    cards.forEach((c, i) => c.children[1].style.backgroundPosition = '0 0');
  };
};
/* SHORT: two feature cards */
PX.features = (Lr, cue, dur, X) => {
  const mkCard = (y, ic, l1, l2, extra) => {
    const c = el('div', 'card qa', Lr, { left: '120px', top: y + 'px', width: '840px', height: '340px' });
    el('div', 'av', c, { left: '34px', top: '34px', width: '120px', height: '120px', background: 'var(--t500)' }, ic);
    el('div', 'abs', c, { left: '184px', top: '36px', font: '900 60px/1.06 var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, l1 + '<br>' + l2);
    el('div', 'abs', c, { left: '34px', top: '210px', width: '772px' }, extra);
    return c;
  };
  const c1 = mkCard(360, B.ic.pin(70, '#fff'), X.ft1a, X.ft1b, `<div class="fld" style="position:relative;width:772px">${X.ftv}${icon.chevron(40, '#777169')}</div>`);
  const c2 = mkCard(760, B.ic.shield(70, '#fff'), X.ft2a, X.ft2b, `<span class="badge" style="height:96px;font-size:50px;padding:0 30px">${icon.check(50, '#018186', 3.4)}${X.ver}</span>`);
  cue(0, 'whoosh_s', .4); cue(.05, 'pop', .55); cue(.6, 'pop', .55); cue(.75, 'success', .45);
  return lt => { V.up(c1, lt, .0, 80, .4); V.up(c2, lt, .6, 80, .4); };
};
