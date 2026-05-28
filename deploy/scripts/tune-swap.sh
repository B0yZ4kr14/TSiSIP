#!/bin/bash
# TSiSIP Host Swap Tuning Script
# Creates and configures swap for database workloads.
# Run as root on the VPS host.
set -euo pipefail

SWAP_GB="${1:-32}"
SWAP_FILE="/swapfile"

echo "=== TSiSIP Host Swap Tuning ==="
echo "Target swap size: ${SWAP_GB}GB"

if [ -f "$SWAP_FILE" ]; then
    echo "Swap file already exists at $SWAP_FILE"
    swapon --show=NAME,SIZE | grep "$SWAP_FILE" || true
else
    echo "Creating ${SWAP_GB}GB swap file..."
    fallocate -l "${SWAP_GB}G" "$SWAP_FILE"
    chmod 600 "$SWAP_FILE"
    mkswap "$SWAP_FILE"
    swapon "$SWAP_FILE"
    echo "$SWAP_FILE none swap sw 0 0" >> /etc/fstab
    echo "Swap file created and activated."
fi

echo "Setting vm.swappiness=10..."
sysctl vm.swappiness=10
grep -q "^vm.swappiness" /etc/sysctl.conf && \
    sed -i 's/^vm.swappiness.*/vm.swappiness=10/' /etc/sysctl.conf || \
    echo "vm.swappiness=10" >> /etc/sysctl.conf

echo "Setting vm.vfs_cache_pressure=50..."
sysctl vm.vfs_cache_pressure=50
grep -q "^vm.vfs_cache_pressure" /etc/sysctl.conf && \
    sed -i 's/^vm.vfs_cache_pressure.*/vm.vfs_cache_pressure=50/' /etc/sysctl.conf || \
    echo "vm.vfs_cache_pressure=50" >> /etc/sysctl.conf

echo ""
echo "Current memory status:"
free -h
echo ""
echo "Swap tuning complete."
