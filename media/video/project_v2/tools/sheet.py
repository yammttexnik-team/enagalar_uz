import sys,glob
from PIL import Image
fs=sorted(glob.glob(sys.argv[1]+'/stills/*.png')); cols=int(sys.argv[3]) if len(sys.argv)>3 else 5
im0=Image.open(fs[0]); w=270; h=int(im0.height*w/im0.width)
rows=(len(fs)+cols-1)//cols; s=Image.new('RGB',(w*cols,h*rows))
for i,f in enumerate(fs): s.paste(Image.open(f).convert('RGB').resize((w,h)),((i%cols)*w,(i//cols)*h))
s.save(sys.argv[2])
