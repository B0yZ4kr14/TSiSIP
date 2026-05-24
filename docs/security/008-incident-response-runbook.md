# Security Incident Response Runbook — TSiSIP

**Date**: 2026-05-23
**Primary**: admin@tsiapp.io
**Escalation**: B0yZ4kr14

---

## P0 Incidents (Immediate Response)

### Unauthorized PostgreSQL/Asterisk Access

1. **Isolate**: `docker compose -f docker-compose.vps.yml stop postgres asterisk`
2. **Preserve**: `docker logs <container> > /tmp/incident-$(date +%s).log`
3. **Investigate**: Check auth_audit_log for unauthorized queries
4. **Notify**: Send alert to admin@tsiapp.io with incident ID
5. **Recover**: Restore from latest encrypted backup if data compromised

### Plaintext Password in Logs/Commits

1. **Rotate**: Change affected passwords immediately
2. **Purge**: `git filter-repo --path <file> --invert-paths` (if committed)
3. **Scan**: `grep -r "password\|secret" .sisyphus/evidence/`
4. **Document**: Add to incident log

### TLS Certificate Expiry

1. **Check**: `openssl x509 -in /etc/letsencrypt/live/tsiapp.io/cert.pem -noout -dates`
2. **Force renew**: `docker compose exec certbot certbot renew --force-renewal`
3. **Verify**: `docker compose exec opensips opensipsctl mi tls_reload`
4. **Monitor**: Set Alertmanager rule for < 30 days expiry

## P1 Incidents (24h Response)

### SSL Labs Grade Below B

1. **Diagnose**: Run SSL Labs scan, identify failing checks
2. **Remediate**: Update TLS configuration in OpenSIPS/nginx
3. **Re-scan**: Verify grade improvement
4. **Document**: Save report to `docs/security/evidence/`

### HIGH/CRITICAL CVE Detected

1. **Assess**: Review Trivy scan output for exploitability
2. **Patch**: Rebuild affected image with updated base image
3. **Deploy**: Rolling update via `docker compose up -d --build <service>`
4. **Verify**: Re-run Trivy scan confirming fix

## Evidence Preservation

For ALL incidents:
- Capture container logs: `docker logs <container>`
- Snapshot containers: `docker commit <container> incident-<id>`
- Freeze audit tables: `pg_dump -t auth_audit_log`
- Save to: `docs/security/evidence/incidents/<YYYY-MM-DD>-<id>/`
