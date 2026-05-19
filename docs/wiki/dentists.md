# Dentist Clinical Operator Guide

> What dental practitioners need to know about the TSiSIP SIP edge platform.

## What the SIP Edge Means for Your Practice

The TSiSIP SIP edge is the secure gateway between your dental practice and the public telephone network. It handles authentication, call routing, and media relay so your voice traffic reaches patients without exposing internal systems.

Key points for clinical staff:

- Your desk phone or softphone registers with the TSiSIP edge, not directly with the PBX.
- All voice media (RTP) passes through a relay; your local network IP is never exposed to the caller.
- Authentication is handled by the edge proxy. You use the SIP credentials provided by your administrator.
- If the edge is unreachable, your phone cannot register or place calls, even if the internal PBX is healthy.

## Verifying Your Endpoint Is Registered

### On a Desk Phone (Yealink, Poly, Grandstream)

1. Look at the phone screen for a **Registered** or **Ready** indicator.
2. If the display shows **Registration Failed** or **No Service**:
   - Check that the network cable is connected.
   - Verify the phone has an IP address (usually under **Status > Network**).
   - Confirm the SIP server address matches the value provided by your administrator.
3. If the issue persists after power-cycling the phone, escalate to the front desk or administrator.

### On a Softphone (Zoiper, Linphone, MicroSIP)

1. Open the softphone and check the account status indicator.
2. A green check or **Online** status means registered.
3. A red X or **Offline** status means the registration failed.
4. Re-enter credentials only if instructed by an administrator. Do not share credentials with patients or external vendors.

### SIP Registration Check via OCP

Administrators and authorized operators can verify registration state in the OCP:

```bash
cd /opt/tsisip
docker compose -f docker-compose.vps.yml exec opensips \
  opensipsctl fifo ul_dump | grep <extension>
```

If your extension appears in the output with a valid contact address, the edge recognizes your endpoint.

## Call Quality Indicators

| Indicator | Healthy | Degraded | Failed |
|---|---|---|---|
| One-way audio | Both parties hear each other | One party cannot hear | Neither party hears audio |
| Latency | Under 150 ms | 150–300 ms | Over 300 ms or timeout |
| Packet loss | 0% | 1–3% | Over 3% or choppy audio |
| Jitter | Under 30 ms | 30–50 ms | Over 50 ms or robotic sound |

### Quick Diagnostics on the Phone

Most SIP phones expose a **Call Statistics** or **Network Statistics** screen during or after a call. Look for:

- **Round-trip time (RTT)** or **Delay**
- **Packet loss** percentage
- **Jitter** in milliseconds

If any degraded or failed values appear consistently across multiple calls, collect the date, time, affected extension, and the statistic values, then escalate.

### RTP Stream Check (Administrator Level)

```bash
cd /opt/tsisip
docker compose -f docker-compose.vps.yml logs --tail=200 rtpengine | grep -E "offer|answer|delete"
```

Active RTP sessions produce `offer` and `answer` log lines. A missing `answer` after an `offer` often indicates a routing or firewall issue between the edge and the PBX.

## Escalation Path When Calls Fail

Follow this order. Do not skip steps unless the entire practice is down.

| Step | Action | Who |
|---|---|---|
| 1 | Power-cycle the affected endpoint and test a call to a known working number. | Dentist or assistant |
| 2 | Check if other extensions in the office can register and call. | Front desk |
| 3 | Verify OCP is reachable at the published HTTPS URL and services show healthy. | Administrator |
| 4 | If multiple tenants or extensions fail, follow the [Runbooks and Troubleshooting](runbooks-troubleshooting.md) guide. | DevOps |

Collect the following before escalating to DevOps:

- Affected extension number(s)
- Time the issue started
- Whether registration shows as failed or the call drops after connect
- Whether the issue is one-way audio, no audio, or no signaling at all
- Any recent network or firewall changes in the office

## Common Endpoint Configurations

### Standard Desk Phone Settings

| Parameter | Value |
|---|---|
| SIP Server / Registrar | Provided by administrator |
| Transport | UDP (default) or TCP |
| Local SIP port | 5060 |
| NAT / STUN | Disabled; topology hiding is handled by the edge |
| Codecs | G.711u (ulaw), G.711a (alaw), G.722 |
| DTMF mode | RFC 4733 or Inband |
| Registration expiry | 3600 seconds |

### Softphone Settings

| Parameter | Value |
|---|---|
| SIP Server / Domain | Provided by administrator |
| Username | Extension number |
| Authentication user | Same as username, unless told otherwise |
| Transport | UDP or TCP |
| Register interval | 3600 seconds |
| Keep-alive | Enabled (if available) |

### Network Requirements for the Office

- Outbound UDP and TCP to port 5060 must be allowed to the TSiSIP edge public address.
- Outbound UDP to the RTP port range (10000–10999 in the VPS-lite profile) must be allowed.
- No inbound port forwarding is required on the office firewall.

## Working Hours vs After-Hours Routing

The TSiSIP edge routes calls based on time-of-day rules configured in the backend PBX. Dentists should know the following:

| Period | Expected Behavior |
|---|---|
| Working hours | Inbound calls ring the front desk and clinical extensions. Overflow goes to voicemail or an auto-attendant. |
| Lunch closures | May route to voicemail or an alternate operator extension. Confirm the schedule with your administrator. |
| After hours | Calls typically go to an emergency line or voicemail box. The edge still authenticates and routes the call; the PBX decides the destination. |

If you need to change routing for a holiday or emergency closure, contact your administrator at least 24 hours in advance. The edge proxy does not store schedules; routing logic lives in the Asterisk dialplan.

## Endpoint Restart Checklist

When restarting a desk phone or softphone:

1. Confirm the device obtained an IP address.
2. Verify the SIP server field matches the administrator-provided value.
3. Wait up to 60 seconds for registration.
4. Place a test call to another internal extension.
5. If internal calls work, place a test call to an external number.
6. If both succeed, the endpoint is fully operational.

## Related Documentation

- [System Overview](system-overview.md) — Platform architecture and readiness
- [Operator and User Guide](operators-users.md) — OCP usage and escalation signals
- [Administrator Guide](administrators.md) — Routine checks and service access
- [Runbooks and Troubleshooting](runbooks-troubleshooting.md) — Failure recovery and command paths

---

Last Updated: 2026-05-19
