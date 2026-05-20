# Evidence: B11 — Certificate Generation Documentation IP

## Finding
- **ID**: B11
- **Severity**: LOW
- **Category**: Technical Debt
- **File**: `docker/ca-tool/cert-gen.sh:17`
- **Finding**: Example uses RFC1918 IP (`192.168.1.1`) as SAN example

## Fix Applied
Changed the example IP from `192.168.1.1` (RFC1918 private address) to `192.0.2.1` (TEST-NET-1 documentation block per RFC 5737).

### Before
```bash
echo "    Example: $0 server --cn tsiapp.io --san 'DNS:tsiapp.io,IP:192.168.1.1'"
```

### After
```bash
echo "    Example: $0 server --cn tsiapp.io --san 'DNS:tsiapp.io,IP:192.0.2.1'"
```

## Rationale
RFC 5737 reserves `192.0.2.0/24` (TEST-NET-1), `198.51.100.0/24` (TEST-NET-2), and `203.0.113.0/24` (TEST-NET-3) for documentation and examples. Using RFC1918 addresses in examples can accidentally be copy-pasted into real configurations, causing routing issues or confusion.

## Impact
- **Risk**: None — this is a usage message string only
- **Benefit**: Follows best practice for documentation examples
