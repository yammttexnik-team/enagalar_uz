import json,sys,os,subprocess,urllib.request
cfg=json.load(open(sys.argv[1])); voice=cfg['voice']; used=0
for k,txt in cfg['segs'].items():
    out=f'{k}.mp3'
    if os.path.exists(out) and os.path.getsize(out)>1000: continue
    body=json.dumps({"text":txt,"model_id":"eleven_v3","voice_settings":{"stability":0.5}}).encode()
    r=subprocess.run(['curl','-s','-o',out,'-w','%{http_code}',f'https://api.elevenlabs.io/v1/text-to-speech/{voice}?output_format=mp3_44100_128','-H','xi-api-key: '+os.environ.get('ELEVENLABS_API_KEY','proxy-injected'),'-H','Content-Type: application/json','-d',body.decode()],capture_output=True,text=True)
    used+=len(txt); print(k,r.stdout,len(txt))
print('chars this run',used)
for k in cfg['segs']:
    d=subprocess.run(['ffprobe','-v','error','-show_entries','format=duration','-of','csv=p=0',f'{k}.mp3'],capture_output=True,text=True).stdout.strip(); print(k,d)
