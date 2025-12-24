#!/bin/bash
set -e

if [ -z "$DEPLOY_USER" ] || [ -z "$DEPLOY_PATH" ] || [ -z "$SSH_PORT" ]; then
  echo "❌ Variables manquantes."
  echo "   Exemple : DEPLOY_USER=debian DEPLOY_PATH=/home/debian/app SSH_PORT=51845 ./setup-server.sh"
  exit 1
fi

echo "🚀 Setup VPS Debian 13 – Docker / FrankenPHP / Caddy"
echo "==================================================="

[ "$EUID" -eq 0 ] || { echo "❌ Lance ce script en root"; exit 1; }

apt update && apt upgrade -y

systemctl disable --now nftables 2>/dev/null || true

apt install -y iptables iptables-persistent netfilter-persistent

if ! command -v docker >/dev/null; then
  apt install -y ca-certificates curl gnupg lsb-release
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  chmod a+r /etc/apt/keyrings/docker.gpg
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" \
    > /etc/apt/sources.list.d/docker.list
  apt update
  apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

systemctl enable docker
systemctl start docker

modprobe br_netfilter || true

cat >/etc/sysctl.d/99-docker.conf <<EOF
net.ipv4.ip_forward=1
net.bridge.bridge-nf-call-iptables=1
net.bridge.bridge-nf-call-ip6tables=1
EOF

cat >/etc/sysctl.d/99-disable-ipv6.conf <<'EOF'
net.ipv6.conf.all.disable_ipv6=1
net.ipv6.conf.default.disable_ipv6=1
net.ipv6.conf.lo.disable_ipv6=1
EOF

sysctl --system

id "$DEPLOY_USER" >/dev/null 2>&1 || useradd -m -s /bin/bash "$DEPLOY_USER"
usermod -aG docker "$DEPLOY_USER"

mkdir -p "$DEPLOY_PATH"
chown -R "$DEPLOY_USER:$DEPLOY_USER" "$DEPLOY_PATH"

systemctl stop docker || true

iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X

iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

iptables -A INPUT -i lo -j ACCEPT
iptables -A INPUT -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

iptables -A INPUT -p tcp --dport "$SSH_PORT" -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -j ACCEPT
iptables -A INPUT -p udp --dport 443 -j ACCEPT

iptables -A FORWARD -m conntrack --ctstate ESTABLISHED,RELATED -j ACCEPT

systemctl start docker

iptables -N DOCKER-USER 2>/dev/null || true
iptables -F DOCKER-USER
iptables -A DOCKER-USER -j RETURN

iptables -I FORWARD 1 -j DOCKER-USER

iptables -A FORWARD -i docker0 -j ACCEPT
iptables -A FORWARD -o docker0 -j ACCEPT
iptables -A FORWARD -i br+ -j ACCEPT
iptables -A FORWARD -o br+ -j ACCEPT

netfilter-persistent save

apt install -y fail2ban

cat >/etc/fail2ban/jail.local <<EOF
[sshd]
enabled = true
port = $SSH_PORT
backend = systemd
EOF

systemctl enable fail2ban
systemctl restart fail2ban

echo ""
echo "✅ VPS PRÊT POUR LE DÉPLOIEMENT"
