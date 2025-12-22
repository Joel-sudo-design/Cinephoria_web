#!/bin/bash
set -e

# =============================
# CONFIGURATION REQUISE
# =============================
if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_PATH" ] || [ -z "$SSH_PORT" ]; then
  echo "❌ Variables manquantes."
  echo "   Exemple : DEPLOY_USER=debian DEPLOY_PATH=/home/debian/app SSH_PORT=51845 ./setup-server.sh"
  exit 1
fi

echo "🚀 Setup VPS Debian 13 – Docker / FrankenPHP / Caddy"
echo "==================================================="

# Root check
[ "$EUID" -eq 0 ] || { echo "❌ Lance ce script en root"; exit 1; }

# =============================
# 1. UPDATE SYSTEM
# =============================
apt update && apt upgrade -y

# =============================
# 2. FIREWALL BASE : IPTABLES
# =============================
# On désactive
systemctl disable --now nftables 2>/dev/null || true

apt install -y iptables iptables-persistent netfilter-persistent

# =============================
# 3. DOCKER INSTALL
# =============================
if ! command -v docker >/dev/null; then
  apt install -y ca-certificates curl gnupg lsb-release
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" \
    > /etc/apt/sources.list.d/docker.list
  apt update
  apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

systemctl enable docker
systemctl start docker

# =============================
# 4. DOCKER NETWORKING
# =============================
modprobe br_netfilter || true

cat >/etc/sysctl.d/99-docker.conf <<EOF
net.ipv4.ip_forward=1
net.bridge.bridge-nf-call-iptables=1
net.bridge.bridge-nf-call-ip6tables=1
EOF

sysctl --system

# =============================
# 5. USER DEPLOY
# =============================
id "$DEPLOY_USER" >/dev/null 2>&1 || useradd -m -s /bin/bash "$DEPLOY_USER"
usermod -aG docker "$DEPLOY_USER"

mkdir -p "$DEPLOY_PATH"
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$DEPLOY_PATH"

# =============================
# 6. FIREWALL RULES
# =============================
systemctl stop docker || true

iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X

iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# Loopback
iptables -A INPUT -i lo -j ACCEPT

# Connexions existantes
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

# SSH / HTTP / HTTPS
iptables -A INPUT -p tcp --dport "$SSH_PORT" -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p udp --dport 443 -j ACCEPT

# Forward established
iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

systemctl start docker

# Docker chains
iptables -N DOCKER-USER 2>/dev/null || true
iptables -F DOCKER-USER
iptables -A DOCKER-USER -j RETURN

iptables -I FORWARD 1 -j DOCKER-USER

iptables -A FORWARD -i docker0 -j ACCEPT
iptables -A FORWARD -o docker0 -j ACCEPT
iptables -A FORWARD -i br+ -j ACCEPT
iptables -A FORWARD -o br+ -j ACCEPT

netfilter-persistent save

# =============================
# 7. FAIL2BAN
# =============================
apt install -y fail2ban

cat >/etc/fail2ban/jail.local <<EOF
[sshd]
enabled = true
port = $SSH_PORT
EOF

systemctl enable fail2ban
systemctl restart fail2ban

# =============================
# DONE
# =============================
echo ""
echo "✅ VPS PRÊT POUR LE DÉPLOIEMENT"
