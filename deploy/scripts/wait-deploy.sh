#!/usr/bin/env bash
# Push'dan keyin CI va Deploy tugashini kutadi (GitHub API, token shart emas: repo ochiq).
# Ishlatish: ./deploy/scripts/wait-deploy.sh [commit]   (standart: HEAD)
set -euo pipefail

REPO="yammttexnik-team/enagalar_uz"
SHA="$(git rev-parse "${1:-HEAD}")"
API="https://api.github.com/repos/$REPO/actions/runs?per_page=20"

# workflow_run deploy default branch nomida ishlaydi, lekin CI shu commit uchun bo'ladi:
# avval CI run'ni topamiz, keyin undan keyin boshlangan Deploy run'ni.
status() {
  curl -fsS "$API" | node -e '
    let s = ""; process.stdin.on("data", d => s += d).on("end", () => {
      const runs = JSON.parse(s).workflow_runs, sha = process.argv[1];
      const ci = runs.find(r => r.name === "CI" && r.head_sha === sha);
      if (!ci) return console.log("ci:wait");
      if (ci.status !== "completed") return console.log("ci:running");
      if (ci.conclusion !== "success") return console.log("ci:" + ci.conclusion);
      // runs yangidan eskiga tartiblangan: CI tugagandan keyin yaratilgan eng birinchi Deploy
      const dep = runs.filter(r => r.name === "Deploy" && new Date(r.created_at) >= new Date(ci.updated_at) - 60000).pop();
      if (!dep) return console.log("deploy:wait");
      console.log(dep.status !== "completed" ? "deploy:running" : "deploy:" + dep.conclusion);
    });' "$SHA"
}

echo "Kutilmoqda: ${SHA:0:7}"
for _ in $(seq 1 90); do
  s="$(status || echo api:error)"
  printf '%s %s\n' "$(date +%H:%M:%S)" "$s"
  case "$s" in
    deploy:success) echo "✅ Deploy tayyor"; exit 0 ;;
    ci:failure|ci:cancelled|deploy:failure|deploy:cancelled) echo "❌ $s"; exit 1 ;;
  esac
  sleep 15
done
echo "Vaqt tugadi"; exit 1
