# Security and Compliance

## Security Model

TSiSIP uses defense in depth:

- The TSiSIP SIP edge service is the only public SIP signaling edge.
- Asterisk and PostgreSQL stay on internal Docker networks.
- Runtime secrets are file-backed and gitignored.
- Backup artifacts are encrypted and protected with HMAC.
- Backup metrics are bound to `127.0.0.1` on the VPS host.
- Containers use memory limits and reduced privilege settings where configured.

## Secret Handling

Never commit:

- `.env`
- `secrets/*`
- private keys
- generated TLS private keys
- access tokens

Operational checks should report redacted status only:

```bash
find secrets -maxdepth 1 -type f -printf '%f %m %s bytes\n'
```

## Backup Security

The backup job fails closed when:

- `PGPASSWORD_FILE` is missing or empty.
- `ENCRYPTION_KEY_FILE` is missing or empty.

Unencrypted backups require an explicit development override:

```bash
ALLOW_UNENCRYPTED_BACKUPS=true
```

Do not use that override in production.

## Network Boundaries

| Boundary | Required State |
|---|---|
| Asterisk | no published host ports |
| PostgreSQL | no published host ports |
| Backup metrics | host loopback only |
| RTPengine control | internal Docker network only |
| SIP public edge | TSiSIP SIP edge service only |

## Open Risks

- External SIP exposure depends on upstream provider/NAT/edge ACL changes.
- Real TLS certificates must replace dummy certificates.
- Real offsite rclone/MinIO credentials must be configured and validated.
- PITR live restore still requires evidence.
- Automatic backup cron windows need observed production evidence.
