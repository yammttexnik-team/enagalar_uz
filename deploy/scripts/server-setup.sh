#!/usr/bin/env bash
# Ubuntu 24.04 serverni birinchi marta sozlash. Idempotent: qayta ishga tushirsa bo'ladi.
# Ishlatish (root sifatida): sudo bash server-setup.sh [deploy_user] [ssh_public_key]
set -euo pipefail

DEPLOY_USER="${1:-deploy}"
SSH_KEY="${2:-}"
SWAP_SIZE="2G"

[ "$(id -u)" -eq 0 ] || { echo "root sifatida ishga tushiring (sudo)"; exit 1; }
export DEBIAN_FRONTEND=noninteractive

echo "==> apt yangilash"
apt-get update -y
apt-get upgrade -y
apt-get install -y ca-certificates curl gnupg git ufw fail2ban unattended-upgrades rclone

echo "==> Docker (rasmiy repo)"
if ! command -v docker >/dev/null 2>&1; then
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  chmod a+r /etc/apt/keyrings/docker.asc
  # shellcheck disable=SC1091
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
    > /etc/apt/sources.list.d/docker.list
  apt-get update -y
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi
systemctl enable --now docker

echo "==> Foydalanuvchi: $DEPLOY_USER"
if ! id "$DEPLOY_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$DEPLOY_USER"
fi
usermod -aG docker "$DEPLOY_USER"
if [ -n "$SSH_KEY" ]; then
  install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
  touch "/home/$DEPLOY_USER/.ssh/authorized_keys"
  grep -qxF "$SSH_KEY" "/home/$DEPLOY_USER/.ssh/authorized_keys" || echo "$SSH_KEY" >> "/home/$DEPLOY_USER/.ssh/authorized_keys"
  chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
  chown "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh/authorized_keys"
elif [ -f /root/.ssh/authorized_keys ] && [ ! -f "/home/$DEPLOY_USER/.ssh/authorized_keys" ]; then
  # root'ning kalitlarini nusxalash (kirish yo'qolmasligi uchun)
  install -d -m 700 -o "$DEPLOY_USER" -g "$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
  cp /root/.ssh/authorized_keys "/home/$DEPLOY_USER/.ssh/authorized_keys"
  chown "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh/authorized_keys"
  chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys"
fi

echo "==> Firewall (ufw): 22, 80, 443"
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
ufw --force enable

echo "==> fail2ban"
if [ ! -f /etc/fail2ban/jail.local ]; then
  printf '[sshd]\nenabled = true\nmaxretry = 5\nbantime = 1h\nfindtime = 10m\n' > /etc/fail2ban/jail.local
fi
systemctl enable --now fail2ban
systemctl restart fail2ban

echo "==> Avtomatik xavfsizlik yangilanishlari"
printf 'APT::Periodic::Update-Package-Lists "1";\nAPT::Periodic::Unattended-Upgrade "1";\n' > /etc/apt/apt.conf.d/20auto-upgrades
systemctl enable --now unattended-upgrades

echo "==> Swap $SWAP_SIZE"
if ! swapon --show | grep -q '/swapfile'; then
  if [ ! -f /swapfile ]; then
    fallocate -l "$SWAP_SIZE" /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
  fi
  swapon /swapfile
fi
grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf
sysctl -p /etc/sysctl.d/99-swappiness.conf >/dev/null

echo "==> Tayyor. Endi '$DEPLOY_USER' bilan kiring: ssh $DEPLOY_USER@<server-ip>"
echo "    (SSH kirishni tekshirmasdan turib root/parol kirishini o'chirmang.)"
