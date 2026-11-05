#!/bin/bash
cd "/tmp/claude-0/-home-user-enagalar-uz/42c19754-17c1-555c-92bd-9b772c6df68c/scratchpad/video/v2/nannies"
python3 "/tmp/claude-0/-home-user-enagalar-uz/42c19754-17c1-555c-92bd-9b772c6df68c/scratchpad/video/v2/tools/render.py" "index.html" --cues "/tmp/claude-0/-home-user-enagalar-uz/42c19754-17c1-555c-92bd-9b772c6df68c/scratchpad/video/v2/nannies/cues.json" > cues.log
N=$(python3 -c "import json;m=json.load(open('cues.json'))['meta'];print(int(round(m['duration']*m['fps'])))")
for s in $(seq 0 150 $((N-1))); do f=parts/p$(printf %05d $s).mp4; [ -s $f ] && continue
  python3 "/tmp/claude-0/-home-user-enagalar-uz/42c19754-17c1-555c-92bd-9b772c6df68c/scratchpad/video/v2/tools/render.py" "index.html" --out $f --from $s --to $((s+150)) >> render.log 2>&1; done
echo ALLDONE >> render.log
