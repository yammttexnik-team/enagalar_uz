#!/usr/bin/env python3
"""expert-reels SFX: synthesizes a premium UI sound palette (no samples, no music)
and mixes it from the scene's cue list.
usage: sfx.py cues.json out.wav [--library libdir]"""
import json, sys, os, numpy as np
from scipy.signal import butter, sosfilt, fftconvolve

SR = 48000
rng = np.random.default_rng(7)
T = lambda d: np.arange(int(d * SR)) / SR

def env(n, a=.003, d=.2, curve=6.0):
    t = np.arange(n) / SR
    at = np.clip(t / max(a, 1e-4), 0, 1)
    return at * np.exp(-np.maximum(t - a, 0) * curve / max(d, 1e-3))
def filt(x, kind, f, order=2):
    if kind == 'bp': sos = butter(order, [max(20, f[0]), min(SR / 2 - 100, f[1])], 'bandpass', fs=SR, output='sos')
    else: sos = butter(order, min(f, SR / 2 - 100), kind, fs=SR, output='sos')
    return sosfilt(sos, x)
def sweep_filter(x, f0, f1, q=1.5, block=256, shape=None):
    """time-varying bandpass via overlapping blocks"""
    n = len(x); out = np.zeros(n); hop = block
    for i in range(0, n, hop):
        pr = i / n if shape is None else shape(i / n)
        fc = f0 * (f1 / f0) ** pr
        bw = fc / q
        seg = x[max(0, i - block):i + hop]
        y = filt(seg, 'bp', (fc - bw / 2, fc + bw / 2))
        out[i:i + hop] = y[-min(hop, n - i):] if len(y) >= min(hop, n - i) else 0
    return out
def noise(d): return rng.standard_normal(int(d * SR))
def sine(f, d, ph=0):
    t = T(d)
    if callable(f): return np.sin(2 * np.pi * np.cumsum(f(t)) / SR + ph)
    return np.sin(2 * np.pi * f * t + ph)
def norm(x, peak=.9): m = np.max(np.abs(x)) + 1e-9; return x / m * peak

_ir = None
def reverb(x, wet=.25, dec=1.2):
    global _ir
    if _ir is None:
        n = int(2.2 * SR); t = np.arange(n) / SR
        irL = rng.standard_normal(n) * np.exp(-t * 6.9 / 1.4); irR = rng.standard_normal(n) * np.exp(-t * 6.9 / 1.4)
        irL = filt(irL, 'lowpass', 6500); irR = filt(irR, 'lowpass', 6500)
        _ir = (irL / np.sqrt(np.sum(irL ** 2)), irR / np.sqrt(np.sum(irR ** 2)))
    x = np.concatenate([x, np.zeros(int(dec * SR))])
    L = fftconvolve(x, _ir[0])[:len(x)]; R = fftconvolve(x, _ir[1])[:len(x)]
    return np.stack([x * (1 - wet) + L * wet * 2.2, x * (1 - wet) + R * wet * 2.2], 1)
def st(x): return np.stack([x, x], 1) if x.ndim == 1 else x
def pad(x, d): return np.concatenate([x, np.zeros((int(d * SR),) + x.shape[1:])])

# ---------------- sound palette ----------------
def s_tap():      # soft felt tap for text reveals
    d = .12; x = sine(lambda t: 230 * np.exp(-t * 18) + 150, d) * env(int(d * SR), .002, .05)
    n = filt(noise(d), 'bp', (1800, 5000)) * env(int(d * SR), .0005, .012) * .25
    return reverb(norm(x + n, .8), .12)
def s_key(v=0):   # keyboard click, 6 variants
    d = .06; f = [3200, 2800, 3600, 3000, 2600, 3400][v]
    n = filt(noise(d), 'bp', (f * .6, f * 1.4)) * env(int(d * SR), .0004, .008)
    th = sine(170 + v * 12, d) * env(int(d * SR), .001, .02) * .5
    return reverb(norm(n + th, .7), .06)
def s_mech(v=0):
    """mechanical keyboard: tactile click + plate ping + bottom-out thock + softer release clack; v=6 is the space bar"""
    d = .16; n = int(d * SR); t = T(d); space = v == 6
    r = np.random.default_rng(100 + v)
    click = filt(r.standard_normal(n), 'bp', (2600, 7500)) * env(n, .0003, .0035) * (.5 if space else .9)
    f_ping = [4300, 4700, 4050, 5100, 4500, 3900, 2600][v]
    ping = sine(f_ping, d) * env(n, .0005, .012) * .22
    f_body = [260, 300, 240, 330, 280, 250, 150][v]
    thock = sine(lambda tt: f_body * (1 + .6 * np.exp(-tt * 90)), d) * env(n, .0015, .03 if not space else .05) * (1.0 if not space else 1.3)
    body = filt(r.standard_normal(n), 'bp', (500, 1800)) * env(n, .0008, .012) * .5
    x = click + ping + thock + body
    o = int((.055 + .01 * (v % 3)) * SR)     # key release (upstroke)
    rel = filt(r.standard_normal(n - o), 'bp', (1800, 5000)) * env(n - o, .0003, .006) * .28
    x[o:] += rel
    if space:  # stabiliser rattle
        o2 = int(.004 * SR); x[o2:] += filt(r.standard_normal(n - o2), 'bp', (900, 2500)) * env(n - o2, .001, .02) * .25
    return reverb(norm(x, .8), .07)
def s_click():
    d = .09; a = filt(noise(d), 'hp', 2500) * env(int(d * SR), .0003, .005)
    b = sine(2100, d) * env(int(d * SR), .0005, .025) * .6 + sine(900, d) * env(int(d * SR), .0005, .03) * .4
    return reverb(norm(a + b, .85), .1)
def s_tick(f=2600):
    d = .07; x = sine(f, d) * env(int(d * SR), .0008, .018) + sine(f * 1.5, d) * env(int(d * SR), .0008, .01) * .3
    return reverb(norm(x, .6), .15)
def s_count(): return s_tick(3100)
def s_pop(pitch=1.0):
    d = .22; x = sine(lambda t: (820 * np.exp(-t * 28) + 240) * pitch, d) * env(int(d * SR), .001, .09)
    c = filt(noise(d), 'bp', (2000, 7000)) * env(int(d * SR), .0003, .006) * .4
    return reverb(norm(x + c, .9), .18)
def s_pop_soft(): return s_pop(1.35) * .8
def s_bubble(): return s_pop(1.9 + rng.random() * .6) * .6
def s_whoosh(d=.7, f0=350, f1=4200, pan=True):
    x = sweep_filter(noise(d), f0, f1, q=1.2, shape=lambda u: np.sin(u * np.pi / 2))
    e = np.sin(np.pi * np.clip(T(d) / d, 0, 1)) ** 2.2
    x = norm(x * e, .85)
    if pan:
        g = np.linspace(0, 1, len(x)); r = reverb(x, .2); gg = np.concatenate([g, np.ones(len(r) - len(g))])
        return np.stack([r[:, 0] * (1 - gg * .6), r[:, 1] * (.4 + gg * .6)], 1) * 1.2
    return reverb(x, .2)
def s_whoosh_s(): return s_whoosh(.38, 600, 5500)
def s_swish(): return s_whoosh(.3, 1500, 7000) * .7
def s_glare(): return s_whoosh(.6, 2500, 9000) * .6
def s_transition():
    w = s_whoosh(.85, 200, 6500)
    lo = sine(lambda t: 60 + 40 * t, .85) * np.sin(np.pi * T(.85) / .85) ** 2 * .35
    w[:len(lo)] += st(lo); return w
def s_sub():
    d = 1.0; x = sine(lambda t: 38 + 45 * np.exp(-t * 7), d) * env(int(d * SR), .005, .45, 5)
    return reverb(norm(x, .9), .08)
def s_shimmer(d=.9):
    out = np.zeros(int(d * SR))
    for i in range(14):
        f = 2400 * 2 ** (rng.integers(0, 16) / 12) ; st_ = int(rng.random() * .45 * SR)
        n = int(.35 * SR); tone = sine(f, .35) * env(n, .003, .18) * (.4 + rng.random() * .6)
        out[st_:st_ + n] += tone[:len(out) - st_]
    return reverb(norm(out, .6), .45)
def s_sparkle(): return s_shimmer(.7) * .9
def s_magic():
    d = 1.1; sw = sine(lambda t: 300 + 1600 * (t / d) ** 2, d) * (T(d) / d) ** 2 * .25
    a = reverb(norm(sw, .5), .35); b = s_shimmer(.9) * .9; n = max(len(a), len(b) + int(.25 * SR))
    out = np.zeros((n, 2)); out[:len(a)] += a; o = int(.25 * SR); out[o:o + len(b)] += b; return out
def fm_bell(f, d=1.4, idx=3.0, ratio=3.5):
    t = T(d); e = np.exp(-t * 3.2)
    return np.sin(2 * np.pi * f * t + idx * e * np.sin(2 * np.pi * f * ratio * t)) * env(len(t), .002, .9, 4)
def s_chime():
    x = np.zeros(int(1.3 * SR)); a = fm_bell(1318, 1.2, 2.2); b = fm_bell(1760, 1.1, 2.0)
    x[:len(a)] += a; o = int(.12 * SR); x[o:o + len(b)] += .6 * b[:len(x) - o]
    return reverb(norm(x, .55), .35)
def s_confirm():
    a = fm_bell(988, .6, 1.6); b = fm_bell(1480, .6, 1.4)
    x = np.zeros(int(.8 * SR)); x[:len(a)] += a; o = int(.08 * SR); x[o:o + len(b)] += b[:len(x) - o]
    return reverb(norm(x, .55), .3)
def s_success():
    x = np.zeros(int(1.4 * SR))
    for i, f in enumerate([784, 988, 1318, 1568]):
        b = fm_bell(f, 1.1, 1.8); o = int(i * .075 * SR); x[o:o + len(b)] += b[:len(x) - o] * (1 - i * .12)
    return reverb(norm(x, .6), .38)
def s_open():
    d = .25; x = sine(lambda t: 500 + 900 * t / d, d) * env(int(d * SR), .005, .12) * .5
    return reverb(norm(x + filt(noise(d), 'bp', (1500, 4000)) * env(int(d * SR), .002, .05) * .3, .55), .2)
def s_close():
    d = .2; x = sine(lambda t: 1200 - 800 * t / d, d) * env(int(d * SR), .003, .08)
    return reverb(norm(x, .45), .15)
def s_select():
    a = s_tick(1900); b = s_tick(2850); out = np.zeros((len(a) + int(.06 * SR), 2)); out[:len(a)] += a; out[int(.06 * SR):int(.06 * SR) + len(b)] += b[:len(out) - int(.06 * SR)]; return out
def s_drop():
    d = .35; x = sine(lambda t: 180 * np.exp(-t * 9) + 90, d) * env(int(d * SR), .002, .12)
    n = filt(noise(d), 'lowpass', 1500) * env(int(d * SR), .001, .02) * .5
    a = reverb(norm(x + n, .85), .15); b = s_pop(1.7) * .4; m = min(len(a), len(b)); a[:m] += b[:m]; return a
def s_progress():
    d = .85; x = np.zeros(int(d * SR))
    for i in range(8):
        f = 1400 * 2 ** (i / 12 * 1.5); b = sine(f, .05) * env(int(.05 * SR), .001, .015); o = int(i * .1 * SR); x[o:o + len(b)] += b * .6
    return reverb(x, .2)
def s_send():
    w = s_whoosh(.45, 900, 8000) * .8; z = sine(lambda t: 600 + 2200 * t / .2, .2) * env(int(.2 * SR), .005, .1) * .25
    w[:len(z)] += st(z); return w
def s_riser(d=.65):
    n = noise(d); x = sweep_filter(n, 300, 6000, q=2, shape=lambda u: u ** 1.5) * (T(d) / d) ** 2
    tone = sine(lambda t: 220 + 660 * (t / d) ** 2, d) * (T(d) / d) ** 2 * .15
    return reverb(norm(x + tone, .7), .3)
def s_riser_soft(): return s_riser(1.1) * .6
def s_reveal():
    a = s_sub() * .7; b = s_shimmer(.8) * .8; n = max(len(a), len(b)) + int(.3 * SR)
    out = np.zeros((n, 2)); out[:len(a)] += a; o = int(.05 * SR); out[o:o + len(b)] += b; return out
def s_impact():
    d = 1.4; lo = sine(lambda t: 34 + 70 * np.exp(-t * 10), d) * env(int(d * SR), .002, .6, 5)
    hit = filt(noise(d), 'lowpass', 2500) * env(int(d * SR), .001, .05) * .6
    b = fm_bell(523, d, 1.2) * .25
    return reverb(norm(lo + hit + b, .95), .35)
def s_thud():
    d = .5; x = sine(lambda t: 70 + 90 * np.exp(-t * 15), d) * env(int(d * SR), .002, .18)
    return reverb(norm(x + filt(noise(d), 'lowpass', 900) * env(int(d * SR), .001, .03) * .6, .9), .15)
def s_paper():
    d = .7; n = noise(d); grains = (rng.random(len(n)) > .9993).astype(float)
    cr = filt(np.convolve(grains, np.hanning(60), 'same') * rng.standard_normal(len(n)), 'hp', 1500)
    body = sweep_filter(n, 900, 3500, q=1.5) * np.sin(np.pi * T(d) / d) ** 1.5
    return reverb(norm(body * .7 + cr * 1.2, .7), .15)
def s_strike():
    d = .28; x = sweep_filter(noise(d), 2000, 7000, q=2) * np.sin(np.pi * T(d) / d) ** 2
    return reverb(norm(x, .6), .12)
def s_error():
    x = np.zeros(int(.45 * SR))
    for i, f in enumerate([440, 370]):
        b = sine(f, .16) * env(int(.16 * SR), .004, .1) * .5; o = int(i * .13 * SR); x[o:o + len(b)] += b
    return reverb(filt(x, 'lowpass', 2500), .2)
def s_steam():
    d = 2.6; x = filt(noise(d), 'bp', (1800, 6500)) * np.sin(np.pi * T(d) / d) ** 2 * (1 + .3 * np.sin(2 * np.pi * 1.3 * T(d)))
    return st(norm(x, .35))

LIB = {k[2:]: v for k, v in globals().items() if k.startswith('s_')}
_cache = {}
def get(name, v=0):
    key = (name, v)
    if key not in _cache:
        x = LIB[name](v) if name in ('key', 'mech') else LIB[name]()
        _cache[key] = st(np.asarray(x, dtype=np.float64))
    return _cache[key]

def mix(cues, dur):
    out = np.zeros((int((dur + 3) * SR), 2))
    for c in cues:
        name = c['name']
        if name not in LIB: print('missing', name); continue
        x = get(name, c.get('v', 0)) * c.get('gain', 1)
        pan = c.get('pan', 0); gl, gr = np.cos((pan + 1) * np.pi / 4), np.sin((pan + 1) * np.pi / 4)
        x = x * np.array([gl, gr]) * 1.41
        i = int(c['t'] * SR); n = min(len(x), len(out) - i)
        if n > 0: out[i:i + n] += x[:n]
    out = out[:int(dur * SR)]
    out = filt(out.T, 'hp', 30).T
    out = np.tanh(out * 1.1) / np.tanh(1.1)          # soft limiter
    out = out / (np.max(np.abs(out)) + 1e-9) * 0.79   # ~ -2 dBFS peak, VO sits on top
    fade = int(.5 * SR); out[-fade:] *= np.linspace(1, 0, fade)[:, None]
    return out

def write_wav(path, x):
    import wave
    y = (np.clip(x, -1, 1) * 32767).astype('<i2')
    with wave.open(path, 'wb') as w:
        w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR); w.writeframes(y.tobytes())

if __name__ == '__main__':
    d = json.load(open(sys.argv[1]))
    out = mix(d['cues'], d['meta']['duration'])
    write_wav(sys.argv[2], out)
    if '--library' in sys.argv:
        lib = sys.argv[sys.argv.index('--library') + 1]; os.makedirs(lib, exist_ok=True)
        for k in LIB:
            write_wav(os.path.join(lib, k + '.wav'), np.clip(get(k) * .9, -1, 1))
    print('ok', out.shape[0] / SR, 's')
