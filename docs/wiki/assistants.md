# Front-Desk Operator Guide

> Daily operations and monitoring for dental assistants using the TSiSIP platform.

## Daily Platform Health Check

Every morning, or at the start of each shift, confirm the following items before patient calls begin arriving.

### OCP Dashboard Quick Scan

1. Open the OCP panel in your browser using the administrator-provided HTTPS URL.
2. Look at the service status summary on the main page.
3. Verify that the following services show **healthy** or **up**:
   - `opensips`
   - `rtpengine`
   - `postgres`
   - `asterisk-pbx-1`
   - `asterisk-pbx-2`
4. If any service shows **down** or **unhealthy**, note the service name and time, then escalate according to the table in the Escalation section below.

### Physical Endpoint Check

Walk through the office and verify:

- All desk phones display a **Registered** or **Ready** indicator.
- The front-desk phone can place a test call to a clinical extension.
- Voicemail or auto-attendant answers when dialed from an internal phone.

If a phone is not registered, power-cycle it and wait 60 seconds. If it still fails, escalate to the administrator.

## Checking if the SIP Trunk Is Online

The SIP trunk is the connection between the TSiSIP edge and the public telephone network. When the trunk is online, patients can reach the practice and the practice can dial out.

| Check | What to Do |
|---|---|
| Visual | Confirm at least one desk phone is registered and shows a green indicator. |
| Outbound | Place a call to a known external number (for example, a mobile phone). If it rings, the trunk is online. |
| Inbound | Ask a colleague to call the practice number from a mobile phone. If the front-desk phone rings, inbound routing is working. |
| OCP | Look for `opensips` status in the OCP service list. If it is healthy, the signaling edge is running. |

If both inbound and outbound tests fail for multiple extensions, the trunk may be down. Escalate immediately.

## Patient Call Routing Basics

Understanding how calls move through the system helps you answer patient questions and spot problems early.

| Call Type | Path |
|---|---|
| Inbound patient call | Public network → TSiSIP edge → Asterisk PBX → Front desk or auto-attendant |
| Internal extension call | Desk phone → TSiSIP edge → Asterisk PBX → Destination extension |
| Outbound call | Desk phone → TSiSIP edge → Asterisk PBX → Public network |
| After-hours call | Public network → TSiSIP edge → Asterisk PBX → Voicemail or emergency line |

What you control at the front desk:

- Call transfers between extensions.
- Do-not-disturb or forward settings on your own phone.
- Voicemail retrieval on your extension.

What you do not control:

- Time-of-day routing rules (managed by the administrator).
- SIP credentials or server addresses (managed by the administrator).
- Trunk provider settings (managed by DevOps).

## Schedule Integration Notes

If the practice uses appointment reminders through a third-party service or integrated software:

- Appointment reminders are usually sent from the PBX or an external service, not directly from the TSiSIP edge.
- The edge handles SIP registration and call routing; reminder scheduling logic lives in the practice management system or Asterisk dialplan.
- If patients report they are not receiving reminder calls, verify:
  1. The practice management system shows reminders as scheduled.
  2. The SIP trunk is online (see above).
  3. Outbound calls to mobile numbers are completing.
- If all three are true but reminders still fail, escalate to the administrator to check Asterisk logs.

## Monitoring: What Green, Yellow, and Red Mean

The OCP dashboard and routine checks use a simple status model.

| Status | Meaning | Action |
|---|---|---|
| **Green** | All services healthy, endpoints registered, calls completing normally. | No action required. Continue routine checks. |
| **Yellow** | One service degraded, intermittent registration loss, or one extension not working. | Note the issue, power-cycle the affected endpoint, and monitor for 10 minutes. If it persists, escalate to administrator. |
| **Red** | Multiple services down, no inbound or outbound calls, or widespread registration failures. | Escalate to administrator immediately. If the administrator is unavailable, follow the [Runbooks and Troubleshooting](runbooks-troubleshooting.md) emergency path. |

## When to Escalate to Admin vs DevOps

Use this table to route issues to the correct team.

| Issue | Escalate To | Reason |
|---|---|---|
| Single phone not registering after power cycle | Administrator | Likely endpoint config or local network issue. |
| Voicemail password reset | Administrator | User account management. |
| Time-of-day routing changes | Administrator | Dialplan and schedule configuration. |
| OCP login failure or 5xx error | DevOps | Platform or reverse-proxy issue. |
| Multiple extensions down at once | DevOps | Likely edge or PBX service failure. |
| Backup metric alerts | DevOps | Infrastructure monitoring concern. |
| No inbound or outbound calls practice-wide | DevOps | Trunk or edge failure. |

### Information to Include When Escalating

Always provide:

- Time the issue was first noticed.
- Which extensions or services are affected.
- Whether the issue is constant or intermittent.
- Any error messages displayed on the phone or OCP.
- Whether the issue started after a network change, power outage, or office move.

## End-of-Shift Handoff

Before leaving for the day:

1. Confirm all critical extensions (front desk, emergency line) are registered.
2. Check the OCP dashboard one final time for any new alerts.
3. Note any yellow or red issues in the shift log.
4. If an issue was escalated, confirm whether it was resolved or is still pending.

## Related Documentation

- [System Overview](system-overview.md) — Platform architecture and readiness
- [Operator and User Guide](operators-users.md) — OCP usage and escalation signals
- [Administrator Guide](administrators.md) — Routine checks and service access
- [Runbooks and Troubleshooting](runbooks-troubleshooting.md) — Failure recovery and command paths
- [Dentist Clinical Operator Guide](dentists.md) — Endpoint registration and call quality for clinical staff

---

Last Updated: 2026-05-19
