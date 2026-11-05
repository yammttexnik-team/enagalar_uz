#!/bin/bash
# Detached, chunked render (survives tool-call boundaries; plain `&` jobs get killed).
# usage: render_all.sh <workdir-with-scene-and-assets> <scene.html> [chunk=150]
# Poll <workdir>/render.log until it contains ALLDONE, then run finalize.sh
W=$(realpath "$1"); H="$2"; C=${3:-150}; S=$(dirname "$(realpath "$0")")
mkdir -p "$W/parts"; : > "$W/render.log"
cat > "$W/_run.sh" <<EOS
#!/bin/bash
cd "$W"
python3 "$S/render.py" "$H" --cues "$W/cues.json" > cues.log
N=\$(python3 -c "import json;m=json.load(open('cues.json'))['meta'];print(int(round(m['duration']*m['fps'])))")
for s in \$(seq 0 $C \$((N-1))); do f=parts/p\$(printf %05d \$s).mp4; [ -s \$f ] && continue
  python3 "$S/render.py" "$H" --out \$f --from \$s --to \$((s+$C)) >> render.log 2>&1; done
echo ALLDONE >> render.log
EOS
chmod +x "$W/_run.sh"; setsid nohup "$W/_run.sh" >/dev/null 2>&1 < /dev/null &
echo "started -> $W/render.log"
