# SIP External Access — Hosting Provider Request Runbook

**Date**: 2026-05-24
**VPS**: tsiapp.io (179.190.15.116)
**Blocked Ports**: 5060/udp, 5060/tcp, 5061/tcp
**Status**: DNS configured (`sip.tsiapp.io` → 179.190.15.116, non-proxied)

---

## Problem Statement

The TSiSIP SIP edge proxy (OpenSIPS) is deployed and healthy on the VPS, but
**zero SIP packets arrive from the public internet**. `tcpdump` on the host
captures no SYN/SIP traffic on ports 5060/5061, confirming the block occurs
upstream of the host — likely at the hosting provider's network edge or
hypervisor-level firewall.

## Evidence Collected

### 1. DNS Resolution (READY)
`sip.tsiapp.io` resolves directly to VPS IP (non-proxied).

### 2. Port Binding on Host (READY)
OpenSIPS listens on 0.0.0.0:5060 and 0.0.0.0:5061.

### 3. tcpdump Evidence (BLOCKED)
Zero packets from external sources after 5-minute capture.

### 4. UFW Status (PASS)
Host-level firewall allows 5060/udp, 5060/tcp, 5061/tcp.
Block is upstream.

### 5. External Scan Confirmation
nmap from external host shows ports 5060/5061 as closed.

## Required Action from Hosting Provider

Whitelist the following ports at network edge for IP 179.190.15.116:

| Port | Protocol | Purpose |
|------|----------|---------|
| 5060 | UDP | SIP signaling |
| 5060 | TCP | SIP signaling (TCP fallback) |
| 5061 | TCP | SIP over TLS |

## Contact Template

**Subject**: Port whitelist request for SIP VoIP service — VPS [ID]

**Body**: Request to open 5060/udp, 5060/tcp, 5061/tcp at network edge for
IP 179.190.15.116. Host-level firewall already configured. External scans
confirm upstream block.

## Post-Whitelist Verification

```bash
nmap -sU -p 5060 sip.tsiapp.io
nmap -sT -p 5060,5061 sip.tsiapp.io
sipsak -s sip:sip.tsiapp.io:5060 -vv
```

Expected: SIP/2.0 200 OK with Server: OpenSIPS header.

## Rollback

If issues occur after whitelist:
```bash
sudo ufw deny 5060/udp 5060/tcp 5061/tcp
sudo ufw reload
```
