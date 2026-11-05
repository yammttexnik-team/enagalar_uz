#!/usr/bin/env python3
"""Render an expert-reels scene HTML to MP4 frames deterministically.
usage:
  render.py scene.html --stills 0.5 3 12        -> PNG stills to ./stills
  render.py scene.html --cues cues.json          -> dump SFX cue list + meta
  render.py scene.html --out video.mp4 [--from F --to F]  -> silent H.264 (chunkable)
"""
import argparse, json, subprocess, sys, os
from pathlib import Path
from playwright.sync_api import sync_playwright

ap = argparse.ArgumentParser()
ap.add_argument('html'); ap.add_argument('--stills', nargs='*', type=float)
ap.add_argument('--cues'); ap.add_argument('--out'); ap.add_argument('--safe', action='store_true')
ap.add_argument('--from', dest='f0', type=int, default=0); ap.add_argument('--to', dest='f1', type=int, default=-1)
a = ap.parse_args()
html = Path(a.html).resolve()

with sync_playwright() as pw:
    br = pw.chromium.launch(args=['--allow-file-access-from-files', '--disable-web-security', '--force-color-profile=srgb', '--hide-scrollbars'])
    pg = br.new_page(viewport={'width': 1080, 'height': 1920}, device_scale_factor=1)
    pg.goto(html.as_uri())
    pg.wait_for_function('window.__ready === true', timeout=60000)
    meta = pg.evaluate('window.__meta'); fps = meta['fps']; dur = meta['duration']
    H = pg.evaluate('window.__H || 1920'); pg.set_viewport_size({'width': 1080, 'height': H})
    if a.safe:
        pg.evaluate("""()=>{const s=document.createElement('div');s.style.cssText='position:absolute;inset:0;z-index:9999;pointer-events:none';
        s.innerHTML='<div style="position:absolute;left:0;top:0;width:1080px;height:250px;background:rgba(255,0,0,.25)"></div><div style="position:absolute;left:0;top:1480px;width:1080px;height:440px;background:rgba(255,0,0,.25)"></div><div style="position:absolute;left:940px;top:250px;width:140px;height:1230px;background:rgba(255,120,0,.18)"></div><div style="position:absolute;left:0;top:240px;width:1080px;height:1440px;outline:3px dashed #0f0"></div>';document.body.appendChild(s)}""")
    if a.cues:
        cues = pg.evaluate('window.__cues')
        json.dump({'meta': meta, 'cues': cues}, open(a.cues, 'w'), indent=1)
        print('cues', len(cues), 'dur', dur)
    if a.stills:
        os.makedirs('stills', exist_ok=True)
        for t in a.stills:
            pg.evaluate(f'window.__render({t})')
            pg.screenshot(path=f'stills/t{t:06.2f}.png')
            q = pg.evaluate('window.__qa ? window.__qa() : []')
            for x in q: print('QA', t, x)
        print('stills done')
    if a.out:
        n = int(round(dur * fps)); f1 = n if a.f1 < 0 else min(a.f1, n)
        ff = subprocess.Popen(['ffmpeg', '-y', '-loglevel', 'error', '-f', 'image2pipe', '-framerate', str(fps), '-c:v', 'mjpeg', '-i', '-',
                               '-c:v', 'libx264', '-preset', 'medium', '-crf', '20', '-pix_fmt', 'yuv420p', '-r', str(fps), a.out], stdin=subprocess.PIPE)
        for f in range(a.f0, f1):
            pg.evaluate(f'window.__render({f / fps})')
            ff.stdin.write(pg.screenshot(type='jpeg', quality=94))
            if f % 60 == 0: print('frame', f, flush=True)
        ff.stdin.close(); ff.wait(); print('done', a.out)
    br.close()
