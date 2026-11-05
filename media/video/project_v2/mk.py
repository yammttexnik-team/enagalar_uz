import os,sys,json
V2=os.path.dirname(os.path.abspath(__file__))
def page(name, H, scripts, body_js):
    d=f'{V2}/{name}'; os.makedirs(d,exist_ok=True)
    s=f'''<!DOCTYPE html><html><head><meta charset="utf-8">
<link rel="stylesheet" href="../assets/fonts.css"><link rel="stylesheet" href="../assets/base.css"><link rel="stylesheet" href="../assets/brand2.css">
</head><body><div id="stage"></div><script>window.__H={H};</script>
<script src="../assets/engine.js"></script><script src="../assets/brand.js"></script><script src="../assets/lib2.js"></script>
{''.join(f'<script src="../assets/{x}"></script>' for x in scripts)}
<script>
{body_js}
</script></body></html>'''
    open(f'{d}/index.html','w').write(s)
def tl(scenes, lang, tx):
    # scenes: [(factory, start, end, opts_json)]
    end=scenes[-1][2]
    items=',\n'.join(f"{{start:{a},end:{b},make:(L,c,d)=>{f}(L,c,d,{tx}.{lang},{o or '{}'})}}" for f,a,b,o in scenes)
    return f"const scene = V.timeline({end}, [\n{items}\n]);\nV.start(scene);"
cfg=json.load(open(sys.argv[1]))
for v in cfg:
    page(v['name'], v.get('H',1920), v['scripts'], tl([tuple(s) for s in v['scenes']], v['lang'], v['tx']))
    print('made', v['name'])
