/* nannies scenes (v2). Layout for 9:16 (H=1920) and 4:5 (H=1350). */
const { p, win, kf, tf, el, clamp, lerp, rnd, icon } = E;
const NX = {};
const ac = s => `<span class="acc">${s}</span>`;
const Y = (a, b) => V.F45 ? b : a;
const fmt = n => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

/* POSTER: complete on frame 0 */
NX.poster = (Lr, cue, dur, X) => {
  const logo = el('div', 'T qa', Lr, { top: Y(290, 50) + 'px', display: 'flex', justifyContent: 'center', alignItems: 'center', gap: '18px' });
  const mk = B.mark(logo, Y(96, 84), 0, 0); mk.w.style.position = 'relative'; mk.set(1, 1, 1);
  el('div', '', logo, { font: `900 ${Y(64, 60)}px var(--head)`, color: '#fff', letterSpacing: '-1px' }, 'enagalar<span style="color:var(--s400)">.uz</span>');
  const h1 = V.T(Lr, X.p1, Y(450, 180), 112), h2 = V.T(Lr, ac(X.p2), Y(570, 300), 112);
  const free = V.T(Lr, X.p3, Y(760, 470), Y(84, 96));
  const bon = el('div', 'chip qa', Lr, { left: '50%', top: Y(880, 600) + 'px', font: `900 ${Y(60, 64)}px var(--head)`, color: 'var(--a300)', borderColor: 'rgba(248,137,80,.6)', background: 'rgba(248,137,80,.14)', padding: '20px 36px' }, `${B.ic.gift(Y(58, 62), '#feb38e')}<span>${X.p4}</span>`);
  const bt = el('div', 'btn apr qa', Lr, { left: '140px', top: Y(1060, 780) + 'px', width: '800px', height: '144px', borderRadius: '72px', fontSize: '62px', transformOrigin: '50% 50%' }, `<span>${X.cta}</span>${V.arrow(62)}`);
  const sub = V.F45 ? V.T(Lr, X.p5, 990, 50, 'Bd') : V.T(Lr, X.p5, 1260, 48, 'Bd');
  cue(0, 'whoosh', .35); cue(.02, 'tap', .4); cue(.6, 'shimmer', .35); cue(1.0, 'pop_soft', .35);
  return lt => {
    bon.style.transform = `translateX(-50%) scale(${(1 + .03 * Math.sin(lt * 4)).toFixed(3)})`;
    tf(bt, { s: 1 + .02 * Math.sin(lt * 5) });
    tf(mk.w, { r: Math.sin(lt * 2) * 4 });
  };
};

/* AI: paste -> tap AI -> fields fill. o = {paste, tap, form, fills:[...], capB, single} */
NX.ai = (Lr, cue, dur, X, o = {}) => {
  const P = Object.assign({ paste: .6, tap: 1.8, form: 2.1, fills: [2.4, 2.75, 3.1, 3.45], capB: 2.1 }, o);
  let capA, capB;
  if (P.single) {
    capA = [V.T(Lr, X.s1, Y(262, 36), Y(54, 52), 'Bd'), V.T(Lr, X.s2, Y(328, 104), Y(96, 100)), V.T(Lr, ac(X.s3), Y(428, 206), Y(96, 100))]; capB = [];
  } else {
    capA = [V.T(Lr, X.a1, Y(280, 40), Y(56, 52), 'Bd'), V.T(Lr, ac(X.a2), Y(350, 110), Y(112, 104))];
    capB = [V.T(Lr, X.a3, Y(270, 30), Y(104, 100)), V.T(Lr, ac(X.a4) + ` ${E.icon.spark(Y(84, 84), '#f88950')}`, Y(380, 136), Y(104, 100))];
  }
  const top = P.single ? Y(560, 330) : Y(520, 270);
  const { ph, sc } = V.phone(Lr, 135, top, 810, Y(1540 - top, 1300));
  el('div', 'abs', sc, { left: '311px', top: '18px', width: '160px', height: '40px', borderRadius: '20px', background: '#0b1214' });
  el('div', 'abs', sc, { left: '36px', top: '76px', font: '900 56px var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, X.ph);
  const sub = el('div', 'abs', sc, { left: '36px', top: '146px', font: '600 44px var(--ui)', color: 'var(--a500)', display: 'flex', alignItems: 'center', gap: '10px', whiteSpace: 'nowrap' }, `${E.icon.spark(40, '#c55205')}<span>${X.aiL}</span>`);
  /* view A: paste */
  const vA = el('div', 'abs', sc, { left: 0, top: 0, width: '782px', height: '1300px' });
  el('div', 'lab', vA, { left: '38px', top: '220px' }, X.lab);
  const ta = el('div', 'abs', vA, { left: '36px', top: '280px', width: '710px', height: '400px', borderRadius: '26px', background: '#fff', border: '3px solid var(--s200)' });
  const taT = el('div', 'abs', ta, { left: '26px', top: '20px', width: '652px', font: '400 48px/1.3 var(--ui)', color: 'var(--s900)', whiteSpace: 'normal' }, X.ad);
  const taP = el('div', 'abs', ta, { left: '26px', top: '24px', font: '400 46px var(--ui)', color: 'var(--s400)', whiteSpace: 'nowrap' }, X.place);
  const tip = el('div', 'abs', vA, { left: '300px', top: '366px', padding: '14px 26px', borderRadius: '16px', background: '#27221b', color: '#fff', font: '600 44px var(--ui)', whiteSpace: 'nowrap', transformOrigin: '20% 100%' }, X.pasteL);
  const aib = el('div', 'btn apr', vA, { left: '36px', top: '716px', width: '710px', height: '124px', fontSize: '52px', transformOrigin: '50% 50%', overflow: 'hidden' }, `${E.icon.spark(52, '#fff')}<span>${X.aiB}</span>`);
  const sheen = el('div', 'abs', aib, { left: '-160px', top: 0, width: '130px', height: '124px', background: 'linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.55),rgba(255,255,255,0))' });
  /* view B: fields */
  const vB = el('div', 'abs', sc, { left: 0, top: 0, width: '782px', height: '1300px', background: 'var(--s50)' });
  el('div', 'abs', vB, { left: '36px', top: '76px', font: '900 56px var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, X.ph);
  el('div', 'abs', vB, { left: '36px', top: '146px', font: '600 44px var(--ui)', color: 'var(--t500)', display: 'flex', alignItems: 'center', gap: '10px', whiteSpace: 'nowrap' }, `${icon.check(42, '#018186', 3.4)}<span>${X.aiDone}</span>`);
  const rows = X.rows.map(([k, v], i) => {
    const r = el('div', 'abs', vB, { left: '36px', top: (230 + i * 168) + 'px', width: '710px', height: '150px', borderBottom: '3px solid var(--s200)' });
    el('div', 'lab', r, { left: 0, top: '8px' }, k);
    const sk = el('div', 'abs', r, { left: 0, top: '74px', width: '360px', height: '50px', borderRadius: '14px', background: '#ece8e2' });
    const vv = el('div', 'abs', r, { left: 0, top: '66px', font: '700 50px var(--ui)', color: 'var(--s900)', whiteSpace: 'nowrap' }, v);
    const ck = el('div', 'abs', r, { left: '634px', top: '50px', width: '72px', height: '72px', borderRadius: '50%', background: 'var(--t500)', display: 'flex', alignItems: 'center', justifyContent: 'center', transformOrigin: '50% 50%' }, icon.check(44, '#fff', 3.6));
    return { sk, vv, ck };
  });
  const spk = [...Array(12)].map((_, i) => el('div', 'abs', Lr, {}, E.icon.spark(34 + (i % 3) * 12, i % 2 ? '#f88950' : '#3eb5ba')));
  const cur = E.mkCursor(Lr);
  const SX = 149, SY = top + 14;
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4);
  cue(P.paste - .3, 'click', .7); cue(P.paste - .25, 'pop_soft', .35); cue(P.paste, 'click', .8); cue(P.paste + .02, 'paper', .5); cue(P.paste + .05, 'shimmer', .3);
  cue(P.tap, 'click', .9); cue(P.tap + .03, 'magic', .7); cue(P.tap + .08, 'sparkle', .5); cue(P.form, 'whoosh_s', .35);
  if (!P.single) { cue(P.capB, 'tap', .4); }
  P.fills.forEach(t => { cue(t, 'tick', .55); cue(t + .02, 'pop_soft', .35); }); cue(P.fills[3] + .35, 'success', .5);
  const T0 = P.single ? -1 : 0;
  return lt => {
    V.up(ph, lt, T0, 120, .4);
    const outA = p(lt, P.capB - .15, .15, 'inCubic');
    capA.forEach((e, i) => { V.up(e, lt, T0 + i * .07); if (!P.single) e.style.opacity *= 1 - outA; });
    capB.forEach((e, i) => V.up(e, lt, P.capB + i * .07));
    const pasted = lt >= P.paste;
    tf(taT, { o: pasted ? 1 : 0 }); tf(taP, { o: pasted ? 0 : 1 });
    ta.style.borderColor = lt >= P.paste - .3 && lt < P.tap ? '#3eb5ba' : 'var(--s200)';
    ta.style.background = pasted && lt < P.paste + .35 ? '#edfbfa' : '#fff';
    const tq = win(lt, P.paste - .28, P.paste + .02, .15, .1); tf(tip, { s: .8 + .2 * tq, o: tq });
    tf(aib, { s: 1 - .05 * V.press(lt, P.tap) });
    sheen.style.transform = `translateX(${kf(lt, [[P.tap, 0], [P.tap + .5, 1000, 'inOutCubic']]).toFixed(0)}px) skewX(-20deg)`;
    const fq = p(lt, P.form, .35, 'outExpo'); tf(vB, { y: (1 - fq) * 1300, o: clamp((lt - P.form) / .05) });
    rows.forEach(({ sk, vv, ck }, i) => {
      const t0 = P.fills[i], q = p(lt, t0, .4, 'outBack');
      tf(sk, { o: lt < t0 ? .6 + .4 * Math.sin(lt * 8 + i) : 0 }); tf(vv, { o: clamp((lt - t0) / .08), x: (1 - q) * 30 }); tf(ck, { s: q, o: clamp((lt - t0) / .05) });
    });
    spk.forEach((s, i) => { const q = clamp((lt - P.tap - .05 - rnd(i + 70) * .25) / .8), a = rnd(i + 71) * 6.283, rr = 120 + 300 * rnd(i + 72) * E.ease.outCubic(q);
      tf(s, { x: 540 + Math.cos(a) * rr - 25, y: SY + 778 + Math.sin(a) * rr - 25, s: .4 + .8 * Math.sin(Math.PI * q), r: q * 120, o: q > 0 && q < 1 ? Math.sin(Math.PI * q) : 0 }); });
    cur.update(lt, [[P.paste - .7, 990, Y(1500, 1300)], [P.paste - .35, SX + 380, SY + 470, 'outCubic'], [P.paste - .05, SX + 380, SY + 470], [P.tap - .15, SX + 470, SY + 778, 'outCubic'], [P.tap + .2, SX + 470, SY + 778], [P.tap + .7, 990, Y(1500, 1300), 'inCubic']], [P.paste - .3, P.paste, P.tap], [P.paste - .7, P.tap + .6]);
  };
};

/* BONUS: counter + held disclaimer */
NX.bonus = (Lr, cue, dur, X, o = {}) => {
  const lead = V.T(Lr, X.b0, Y(290, 40), Y(56, 52), 'Bd');
  const coin = el('div', 'abs', Lr, { left: '440px', top: Y(400, 130) + 'px', width: '200px', height: '200px', borderRadius: '50%', background: 'radial-gradient(circle at 35% 30%,#feb38e,#f88950 45%,#c55205)', boxShadow: '0 0 70px rgba(248,137,80,.55)', display: 'flex', alignItems: 'center', justifyContent: 'center' }, B.ic.gift(108, '#fff'));
  const num = V.T(Lr, '+30 000', Y(660, 360), 160);
  const unit = V.T(Lr, ac(X.b1), Y(840, 540), Y(100, 100));
  const disc = el('div', 'T qa Bd', Lr, { top: Y(1010, 700) + 'px', fontSize: Y(46, 50) + 'px', lineHeight: 1.3, padding: '22px 0', border: '3px solid rgba(129,211,213,.4)', borderRadius: '28px', background: 'rgba(2,36,40,.55)', color: '#fcfaf7', whiteSpace: 'normal' }, X.b2);
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4); cue(.1, 'pop', .6); for (let i = 0; i < 12; i++) cue(.2 + i * .065, 'count', .22 + i * .01); cue(1.0, 'chime', .55); cue(1.05, 'sparkle', .45);
  return lt => {
    V.up(lead, lt, 0); V.pop(coin, lt, .1, .5, { y: Math.sin(lt * 2) * 6, r: Math.sin(lt * 1.5) * 5 });
    const q = p(lt, .2, .8, 'outCubic'); num.textContent = '+' + fmt(Math.round(q * 30) * 1000); tf(num, { s: 1 + .05 * win(lt, 1.0, 1.25, .1, .25) });
    V.up(unit, lt, .15);
    tf(disc, { o: 1 }); // held for the whole shot
  };
};

/* FAMILIES: notifications + safety line */
NX.fam = (Lr, cue, dur, X, o = {}) => {
  const tw = o.warn ?? 1.8;
  const a = V.T(Lr, X.f1, Y(270, 36), Y(84, 96)), b = V.T(Lr, ac(X.f2), Y(370, 146), Y(84, 96));
  const nt = X.notes.map(([ic, col, h, s], i) => {
    const n = el('div', 'card qa', Lr, { left: '120px', top: (Y(520, 300) + i * 200) + 'px', width: '840px', height: '176px' });
    el('div', 'av', n, { left: '30px', top: '33px', width: '110px', height: '110px', background: col }, B.ic[ic](56, '#fff'));
    el('div', 'abs', n, { left: '170px', top: '30px', font: '900 52px var(--head)', color: 'var(--s900)', whiteSpace: 'nowrap' }, h);
    el('div', 'abs', n, { left: '170px', top: '98px', font: `500 ${Y(44, 48)}px var(--ui)`, color: 'var(--s500)', whiteSpace: 'nowrap' }, s);
    return n;
  });
  const wr = el('div', 'warn qa', Lr, { left: '120px', top: Y(980, 720) + 'px', width: '840px', height: '200px', justifyContent: 'center', padding: '0 30px', textAlign: 'center', transformOrigin: '50% 50%' }, `${B.ic.warn(70, '#563501')}<span>${X.warn}</span>`);
  cue(0, 'whoosh_s', .4); cue(.02, 'tap', .4); cue(.1, 'tap', .35);
  nt.forEach((_, i) => { cue(.35 + i * .4, 'open', .45, (i - .5) * .4); cue(.37 + i * .4, 'bubble', .35); });
  cue(tw, 'error', .3); cue(tw + .02, 'pop', .6);
  return lt => {
    V.up(a, lt, 0); V.up(b, lt, .07);
    nt.forEach((n, i) => { const q = p(lt, .35 + i * .4, .45, 'outBack'); tf(n, { x: (1 - q) * 140, o: clamp((lt - .35 - i * .4) / .08), y: Math.sin(lt * 1.4 + i) * 4 }); });
    const w = p(lt, tw, .4, 'outBack'); tf(wr, { s: .75 + .25 * w, o: clamp((lt - tw) / .08) });
  };
};

/* END */
NX.end = (Lr, cue, dur, X0, o = {}) => {
  const X = o.short ? Object.assign({}, X0, X0.endS) : X0;
  const mk = B.mark(Lr, Y(160, 120), Y(460, 480), Y(270, 40));
  const h1 = V.T(Lr, X.e1, Y(470, 190), Y(104, 104)), h2 = V.T(Lr, ac(X.e2), Y(584, 300), Y(104, 104));
  const bt = el('div', 'btn apr qa', Lr, { left: '140px', top: Y(770, 450) + 'px', width: '800px', height: '144px', borderRadius: '72px', fontSize: '60px', transformOrigin: '50% 50%' }, `<span>${X.cta}</span>${V.arrow(60)}`);
  const url = V.T(Lr, 'enagalar<span style="color:var(--s400)">.uz</span>', Y(966, 640), 124);
  const ch = X.chips.map((tx, i) => el('div', 'chip qa', Lr, { left: '50%', top: (Y(1160, 820) + i * Y(112, 118)) + 'px', fontSize: Y(42, 48) + 'px' }, `${i === 0 ? B.ic.plane(44, '#81d3d5') : B.ic.lock(42, '#81d3d5')}<span>${tx}</span>`));
  const bu = B.burst(Lr, 24, 3); const cur = E.mkCursor(Lr);
  const tc = Math.min(1.5, dur - 1.6), by = Y(842, 522);
  cue(0, 'whoosh', .45); cue(.05, 'pop', .6); cue(.1, 'magic', .45); cue(.2, 'tap', .45); cue(.35, 'pop', .55); cue(.5, 'reveal', .45);
  ch.forEach((_, i) => cue(.65 + i * .1, 'pop_soft', .35));
  cue(tc, 'click', .9); cue(tc + .05, 'success', .7); cue(tc + .08, 'sparkle', .5);
  return lt => {
    const mq = p(lt, 0, .45, 'outBack'); tf(mk.w, { s: mq, y: Math.sin(lt * 2) * 5 }); mk.set(p(lt, 0, .4), p(lt, .1, .3, 'outBack'), p(lt, .2, .3, 'outBack'));
    V.up(h1, lt, .12); V.up(h2, lt, .2);
    const q = p(lt, .35, .45, 'outBack'); tf(bt, { s: (.7 + .3 * q) * (1 - .05 * V.press(lt, tc)) * (1 + .015 * Math.sin(lt * 5) * (lt > 1 ? 1 : 0)), o: clamp((lt - .35) / .08) });
    V.up(url, lt, .5);
    ch.forEach((c, i) => { const k = p(lt, .65 + i * .1, .4, 'outBack'); c.style.transform = `translateX(-50%) scale(${(.7 + .3 * k).toFixed(3)})`; c.style.opacity = clamp((lt - .65 - i * .1) / .1).toFixed(3); });
    B.updBurst(bu, lt, tc + .05, 540, by, 3);
    cur.update(lt, [[tc - .6, 1000, Y(1450, 1250)], [tc - .05, 640, by + 18, 'outCubic'], [tc + .5, 640, by + 18], [tc + 1.1, 990, Y(1450, 1250), 'inCubic']], [tc], [tc - .6, tc + 1.05]);
  };
};
