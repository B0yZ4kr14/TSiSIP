# Security Constraints — Feature 022: VPS Go-Live Stabilization

## Evidence Hygiene
- No sensitive values (passwords, API keys, tokens) may be stored in .sisyphus/evidence/
- Evidence files must be grep-clean for patterns: password=, secret=, api_key, token=
- .gitignore must exclude .env, secrets/, and any runtime credential files

## Network Isolation
- Asterisk and PostgreSQL must have zero host-published ports
- MI HTTP must bind to sip_internal network only (port 8888)
- RTPengine control socket (--listen-ng) must bind to sip_internal only

## Runtime Security
- OpenSIPS must use topology_hiding("C") for all forwarded dialogs
- auth_db must use calculate_ha1=0 and password_column=ha1
- All containers must declare cap_drop: [ALL] and security_opt: ["no-new-privileges:true"]
- Image tags must use :?must be set (no :latest fallbacks)

## TLS/Certificate
- TLS certificates must be valid and not expire within 30 days
- tls_reload must succeed without error
- CA bundle must be present and valid
