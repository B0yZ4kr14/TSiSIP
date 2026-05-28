#!/bin/bash
# TSiSIP External Blocker Resolution Playbook
# Run this on the VPS host as root or with sudo.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

info()  { echo "[INFO] $*"; }
warn()  { echo "[WARN] $*"; }

setup_dns() {
    echo ""
    info "=== DNS A Record ==="
    VPS_IP="${VPS_IP:-$(curl -s -4 ifconfig.me || echo 'UNKNOWN')}"
    info "Detected public IP: $VPS_IP"
    info "Required action: Create/update A record for tsiapp.io pointing to $VPS_IP"
    info "If using Cloudflare or Route53, ensure CDN proxy is DISABLED for SIP"
    info "SIP requires direct IP reachability; CDN/proxy breaks UDP 5060"
    read -p "Have you updated the A record? (y/N) " confirm
    if [[ "$confirm" =~ ^[Yy]$ ]]; then
        info "Verifying DNS propagation..."
        dig +short tsiapp.io || true
        nslookup tsiapp.io || true
    else
        warn "DNS A record update deferred. SIP clients cannot resolve tsiapp.io until this is done."
    fi
}

setup_firewall() {
    echo ""
    info "=== Firewall ACL for SIP 5060 ==="
    if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
        info "UFW detected. Adding rules..."
        ufw allow 5060/udp comment 'TSiSIP SIP signaling UDP'
        ufw allow 5060/tcp comment 'TSiSIP SIP signaling TCP'
        ufw allow 5061/tcp comment 'TSiSIP SIP signaling TLS'
        ufw allow 10000:20000/udp comment 'TSiSIP RTP media'
        ufw reload
        info "UFW rules added."
    elif command -v iptables >/dev/null 2>&1; then
        info "iptables detected. Adding rules..."
        iptables -C INPUT -p udp --dport 5060 -j ACCEPT 2>/dev/null || iptables -I INPUT -p udp --dport 5060 -j ACCEPT
        iptables -C INPUT -p tcp --dport 5060 -j ACCEPT 2>/dev/null || iptables -I INPUT -p tcp --dport 5060 -j ACCEPT
        iptables -C INPUT -p tcp --dport 5061 -j ACCEPT 2>/dev/null || iptables -I INPUT -p tcp --dport 5061 -j ACCEPT
        iptables -C INPUT -p udp --dport 10000:20000 -j ACCEPT 2>/dev/null || iptables -I INPUT -p udp --dport 10000:20000 -j ACCEPT
        info "iptables rules added (not persisted; use iptables-persistent or firewalld)"
    elif command -v firewall-cmd >/dev/null 2>&1; then
        info "firewalld detected. Adding rules..."
        firewall-cmd --permanent --add-port=5060/udp
        firewall-cmd --permanent --add-port=5060/tcp
        firewall-cmd --permanent --add-port=5061/tcp
        firewall-cmd --permanent --add-port=10000-20000/udp
        firewall-cmd --reload
        info "firewalld rules added."
    else
        warn "No supported firewall detected."
        warn "Ensure ports 5060/udp, 5060/tcp, 5061/tcp, and 10000-20000/udp are open upstream."
    fi
    if command -v tailscale >/dev/null 2>&1; then
        info "Tailscale detected. If using Tailscale for admin access, ensure:"
        info "  - The VPS advertises route 0.0.0.0/0 or specific subnets"
        info "  - ACL policy does NOT block UDP 5060 for SIP clients"
        info "  - Tailscale is NOT required for public SIP (only for admin/OCP)"
    fi
}

setup_backup_storage() {
    echo ""
    info "=== Backup Storage Configuration ==="
    STORAGE_ENV="$PROJECT_ROOT/secrets/s3_env"
    if [ -f "$STORAGE_ENV" ]; then
        info "Backup storage config already present at $STORAGE_ENV"
        return 0
    fi
    warn "Backup storage env file not found. Offsite replication requires this."
    info "Create $STORAGE_ENV following docs/backup-storage-setup.md"
    info "Then restart the backup service: docker compose up -d backup"
}

setup_swap() {
    echo ""
    info "=== Host Swap Tuning ==="
    MEM_GB=$(free -g | awk '/^Mem:/{print $2}')
    SWAP_GB=$(free -g | awk '/^Swap:/{print $2}')
    info "Detected RAM: ${MEM_GB}GB, Swap: ${SWAP_GB}GB"
    RECOMMENDED_SWAP=32
    if [ "${SWAP_GB:-0}" -ge "$RECOMMENDED_SWAP" ]; then
        info "Swap is already adequate (${SWAP_GB}GB >= ${RECOMMENDED_SWAP}GB recommended)"
    else
        warn "Swap is below recommended ${RECOMMENDED_SWAP}GB for 62GB RAM host."
        warn "PostgreSQL + OpenSIPS + RTPengine can spike memory during reloads."
        info "Run deploy/scripts/tune-swap.sh to add swap."
    fi
    CURRENT_SWAPPINESS=$(cat /proc/sys/vm/swappiness 2>/dev/null || echo '60')
    info "Current vm.swappiness: $CURRENT_SWAPPINESS"
    if [ "$CURRENT_SWAPPINESS" -gt 10 ]; then
        warn "vm.swappiness=$CURRENT_SWAPPINESS is high. For database workloads, recommend 10."
        info "Apply: sudo sysctl vm.swappiness=10"
        info "Persist: echo 'vm.swappiness=10' | sudo tee -a /etc/sysctl.conf"
    fi
}

main() {
    echo "========================================"
    echo "TSiSIP External Blocker Resolution"
    echo "========================================"
    setup_dns
    setup_firewall
    setup_backup_storage
    setup_swap
    echo ""
    echo "========================================"
    info "External blocker playbook complete."
    warn "Items marked WARN above require manual action."
    echo "========================================"
}

main "$@"
