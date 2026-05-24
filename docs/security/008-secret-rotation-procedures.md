# Secret Rotation Procedures — TSiSIP

**Date**: 2026-05-23

---

## Rotation Calendar

| Secret | Tier | Frequency | Last Rotated | Next Rotation |
|---|---|---|---|---|
| db_password | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| auth_secret | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| topology_secret | Runtime | Quarterly | 2026-05-23 | 2026-08-23 |
| server.key / server.crt | TLS | 90 days | 2026-05-23 | 2026-08-21 |
| ca.crt | TLS | 90 days | 2026-05-23 | 2026-08-21 |
| TRUNK_CRED_KEY | Trunk | Annual | 2026-05-23 | 2027-05-23 |
| backup_encryption_key | Backup | Annual | 2026-05-23 | 2027-05-23 |

## Rotation Procedure: Runtime Secrets

1. Generate new secret value
2. Update file in `secrets/` directory
3. Run `docker compose -f docker-compose.vps.yml up -d` to reload
4. Verify service health
5. Update rotation calendar

## Rotation Procedure: TLS Certificates

1. `docker compose exec certbot certbot renew --force-renewal`
2. Verify new certificate dates
3. `docker compose exec opensips opensipsctl mi tls_reload`
4. Run SSL Labs scan to verify

## Rotation Procedure: Trunk Credentials

1. Contact trunk provider for new credentials
2. Encrypt with `TRUNK_CRED_KEY`
3. Update `secrets/trunk_credentials`
4. Test trunk connectivity
5. Update rotation calendar

## Emergency Rotation

On compromise suspicion: Rotate ALL secrets immediately, then investigate.
