import sys,subprocess,json,os,re
V2=os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VO={
 'parents':[('p1',.1),('p2',6.6),('p3',11.5),('p4',15.2),('p5',18.7),('p6',21.6)],
 'parents_short':[('ps1',.1),('ps2',1.7),('ps3',4.7),('ps4',7.5)],
 'parents_ru':[('rp1',.1),('rp2',7.2),('rp3',12.1),('rp4',15.7),('rp5',19.4),('rp6',22.9)],
 'nannies':[('n1',.1),('n2',3.0),('n3',5.05),('n4',7.6),('n5',10.6),('n6',15.4)],
 'nannies_short':[('ns1',.1),('ns2',4.7),('ns3',7.1)],
 'nannies_ru':[('rn1',.1),('rn2',2.6),('rn3',5.15),('rn4',7.3),('rn5',10.8),('rn6',14.8)],
}
VO.update({
 'parents':[('p1',.1),('p2',7.3),('p3',12.75),('p4',16.45),('p5',20.2),('p6',23.2)],
 'parents_short':[('ps1',.1),('ps2',1.5),('ps3',4.9),('ps4',7.8)],
 'nannies':[('n1',.1),('n2',3.7),('n3',6.0),('n4',8.3),('n5',11.3),('n6',15.5)],
 'nannies_short':[('ns1',.1),('ns2',4.9),('ns3',7.5)],
})
VO['nannies_4x5']=VO['nannies']
def run(a): return subprocess.run(a,capture_output=True,text=True)
name=sys.argv[1]; W=f'{V2}/{name}'; os.chdir(W)
open('parts.txt','w').write(''.join(f"file '{p}'\n" for p in sorted(os.listdir('parts')) if p.endswith('.mp4')).replace("file '","file 'parts/"))
run(['ffmpeg','-y','-v','error','-f','concat','-safe','0','-i','parts.txt','-c','copy','video_silent.mp4'])
run(['python3',f'{V2}/tools/sfx.py','cues.json','sfx.wav'])
dur=float(run(['ffprobe','-v','error','-show_entries','format=duration','-of','csv=p=0','video_silent.mp4']).stdout)
segs=VO[name]; ins=[]; fl=[]
for i,(k,t) in enumerate(segs):
    ins+=['-i',f'{V2}/vo/'+('wav' if k.startswith('r') else 'v3/wav')+f'/{k}.wav']; d=int(t*1000); fl.append(f'[{i}:a]adelay={d}|{d},apad[v{i}]')
fl.append(''.join(f'[v{i}]' for i in range(len(segs)))+f'amix=inputs={len(segs)}:normalize=0:duration=longest,atrim=0:{dur},aformat=channel_layouts=stereo[vo]')
run(['ffmpeg','-y','-v','error',*ins,'-filter_complex',';'.join(fl),'-map','[vo]','-ar','48000','vo.wav'])
# duck SFX under VO, sum, two-pass loudnorm to -14 LUFS / -1.5 dBTP
pre='[0:a]volume=0.7,aformat=channel_layouts=stereo[s];[1:a]asplit[v1][v2];[s][v1]sidechaincompress=threshold=0.02:ratio=4:attack=8:release=280:makeup=1[sd];[sd][v2]amix=inputs=2:normalize=0:weights=1 1.15,atrim=0:%s' % dur
r=run(['ffmpeg','-y','-i','sfx.wav','-i','vo.wav','-filter_complex',pre+',loudnorm=I=-14:TP=-2.0:LRA=11:print_format=json','-f','null','-'])
m=json.loads(re.search(r'\{[^{}]*"input_i"[^{}]*\}',r.stderr).group(0))
ln=f"loudnorm=I=-14:TP=-2.0:LRA=11:measured_I={m['input_i']}:measured_TP={m['input_tp']}:measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}:linear=true"
r=run(['ffmpeg','-y','-v','error','-i','sfx.wav','-i','vo.wav','-filter_complex',pre+','+ln+',aresample=48000','-ar','48000','mix.wav'])
out=f'{V2}/../{name}.mp4'
r=run(['ffmpeg','-y','-v','error','-i','video_silent.mp4','-i','mix.wav','-map','0:v','-map','1:a','-c:v','copy','-c:a','aac','-b:a','192k','-shortest','-movflags','+faststart',out])
print(name, 'err' if r.returncode else 'ok', r.stderr[-300:])
r=run(['ffmpeg','-i',out,'-af','ebur128=peak=true','-f','null','-'])
s=r.stderr[r.stderr.rfind('Summary'):]
I=re.search(r'I:\s+(-?[\d.]+) LUFS',s).group(1); TP=re.search(r'Peak:\s+(-?[\d.]+) dBFS',s).group(1)
sz=os.path.getsize(out)/1e6
print(f'RESULT {name} dur={dur:.2f}s I={I} LUFS TP={TP} dBTP size={sz:.1f}MB')
