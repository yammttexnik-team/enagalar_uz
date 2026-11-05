#!/bin/bash
# Concatenate rendered parts, synthesize SFX from cues.json, mux the final MP4.
# usage: finalize.sh <workdir> <out_name> [voiceover_audio] [vo_start_seconds=0] [sfx_gain_with_vo=0.7]
#   Without a voiceover: <out_name>.mp4 (video + SFX) and <out_name>_sfx.wav (stem).
#   With a voiceover file (wav/mp3/m4a): the voice is placed at vo_start and mixed on top; SFX are lowered to sfx_gain.
W=$(realpath "$1"); O="$2"; VO="$3"; VS="${4:-0}"; G="${5:-0.7}"; S=$(dirname "$(realpath "$0")"); cd "$W" || exit 1
ls parts/*.mp4 | sort | sed "s/^/file '/;s/$/'/" > parts.txt
ffmpeg -y -loglevel error -f concat -safe 0 -i parts.txt -c copy video_silent.mp4
python3 "$S/sfx.py" cues.json "${O}_sfx.wav"
if [ -n "$VO" ]; then
  D=$(python3 -c "print(int(float('$VS')*1000))")
  ffmpeg -y -loglevel error -i video_silent.mp4 -i "${O}_sfx.wav" -i "$VO" -filter_complex \
   "[1:a]volume=$G[s];[2:a]aresample=48000,adelay=${D}|${D},loudnorm=I=-15:TP=-1.5[v];[s][v]amix=inputs=2:duration=first:normalize=0,alimiter=limit=0.95[a]" \
   -map 0:v -map "[a]" -c:v copy -c:a aac -b:a 256k -ar 48000 -shortest -movflags +faststart "${O}.mp4"
else
  ffmpeg -y -loglevel error -i video_silent.mp4 -i "${O}_sfx.wav" -c:v copy -c:a aac -b:a 256k -ar 48000 -shortest -movflags +faststart "${O}.mp4"
fi
ffprobe -v error -show_entries format=duration:stream=codec_name,width,height -of compact "${O}.mp4"
