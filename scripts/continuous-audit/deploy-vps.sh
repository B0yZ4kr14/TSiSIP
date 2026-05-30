#!/bin/bash
set -euo pipefail

VPS_HOST="179.190.15.116"
VPS_USER="tsi"
VPS_KEY="${HOME}/.ssh/TSiHomeLab"
PROJECT_ROOT="/opt/tsisip"

echo "=== Deploying TSiSIP Continuous Audit to VPS ==="

# Ensure VPS repo is up to date
echo ">> Syncing code to VPS..."
rsync -avz --exclude='.venv' --exclude='.ansible-venv' --exclude='node_modules' \
  --exclude='.git/objects' --exclude='*.log' \
  -e "ssh -i ${VPS_KEY}" \
  /home/b0yz4kr14/Projects/TSiSIP/ \
  ${VPS_USER}@${VPS_HOST}:${PROJECT_ROOT}/

# Create log directory on VPS
echo ">> Creating log directory..."
ssh -i "${VPS_KEY}" ${VPS_USER}@${VPS_HOST} "sudo mkdir -p ${PROJECT_ROOT}/logs/continuous-audit && sudo chown -R ${VPS_USER}:${VPS_USER} ${PROJECT_ROOT}/logs"

# Install systemd files
echo ">> Installing systemd service and timer..."
scp -i "${VPS_KEY}" /home/b0yz4kr14/Projects/TSiSIP/deploy/systemd/tsisip-continuous-audit.service \
  ${VPS_USER}@${VPS_HOST}:/tmp/tsisip-continuous-audit.service
scp -i "${VPS_KEY}" /home/b0yz4kr14/Projects/TSiSIP/deploy/systemd/tsisip-continuous-audit.timer \
  ${VPS_USER}@${VPS_HOST}:/tmp/tsisip-continuous-audit.timer

ssh -i "${VPS_KEY}" ${VPS_USER}@${VPS_HOST} "
  sudo mv /tmp/tsisip-continuous-audit.service /etc/systemd/system/
  sudo mv /tmp/tsisip-continuous-audit.timer /etc/systemd/system/
  sudo systemctl daemon-reload
  sudo systemctl enable tsisip-continuous-audit.timer
  sudo systemctl start tsisip-continuous-audit.timer
"

echo ">> Starting first cycle immediately..."
ssh -i "${VPS_KEY}" ${VPS_USER}@${VPS_HOST} "
  cd ${PROJECT_ROOT} && sudo systemctl start tsisip-continuous-audit.service
"

echo "=== Deploy complete ==="
echo "Check status with: ssh -i ${VPS_KEY} ${VPS_USER}@${VPS_HOST} 'systemctl status tsisip-continuous-audit.timer'"
echo "View logs with: ssh -i ${VPS_KEY} ${VPS_USER}@${VPS_HOST} 'tail -f ${PROJECT_ROOT}/logs/continuous-audit/master.log'"
