# TSiSIP Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.0.x   | ✓ Active |

## Reporting Vulnerabilities

Please report security vulnerabilities to:
- Email: security@tsiapp.io
- Do not open public issues for security bugs

## Security Features

### Authentication
- bcrypt password hashing
- SIP Digest with HA1
- Session-based auth
- Brute force protection (5 attempts → 15min lock)

### Authorization
- Role-based access control
- Admin, devops, operator, readonly
- Audit logging for all actions

### Session Security
- HttpOnly cookies
- Secure flag (HTTPS only)
- SameSite=Strict
- Strict mode
- Regenerate ID on login

### CSRF Protection
- Token validation on POST
- Double-submit cookie pattern

### Data Protection
- No plaintext passwords
- HA1 hashes only
- Secrets in Docker secrets
- .gitignore for sensitive files

### Network Security
- Internal Docker networks
- No exposed database ports
- Topology hiding
- Firewall rules

## Best Practices

1. Use strong passwords (12+ chars)
2. Enable HTTPS
3. Regular backups
4. Monitor audit logs
5. Keep containers updated
6. Use secrets management
7. Restrict SSH access

## Incident Response

1. Isolate affected systems
2. Collect logs
3. Assess impact
4. Apply fixes
5. Verify resolution
6. Document lessons learned
