# Operator and User Guide

## What Operators Use

Operators use the TSiSIP OCP web panel to inspect and manage SIP-facing operational data. The production URL is:

```text
https://tsiapp.io/TSiSIP/
```

## Expected Platform Behavior

- SIP clients authenticate at the TSiSIP SIP edge.
- Backend PBX servers are not directly exposed.
- Media is relayed through RTPengine.
- OCP is served behind HTTPS through Nginx.
- Backup metrics are not public; they are only available on the VPS loopback interface.

## What Is Currently Pending

Operators should know these are infrastructure items, not user mistakes:

- Public SIP 5060/5061 is still filtered upstream of the VPS.
- Full Prometheus/Grafana dashboards are not active in the VPS-lite profile.
- Offsite backup replication requires real rclone/MinIO credentials.
- First automatic backup cron cycle still needs live observation after deployment.

## Escalation Signals

Escalate to DevOps when:

- OCP returns 5xx or login page does not load.
- SIP registration/calls fail for multiple tenants.
- Metrics show `backup_validation_status` other than `1`.
- External SIP tests still show `filtered` after provider/edge ACL changes.
