# Frequently Used Commands

Command mirror maintained alongside the local graph memory.

## Docker / OpenSIPS Build

```bash
# Build the OpenSIPS image from source
docker build -t tsisip-opensips:latest .

# Build the RTPengine image from source
docker build -t tsisip/rtpengine:latest -f docker/rtpengine/Dockerfile .

# Validate rendered Compose configuration
docker compose config

# Validate OpenSIPS config syntax inside the built image
docker run --rm \
  -e DB_HOST=postgres -e DB_NAME=opensips -e DB_USER=opensips \
  -e HOST_PUBLIC_IP=127.0.0.1 -e OPENSIPS_LISTEN_IP=0.0.0.0 \
  -e RTPENGINE_HOST=rtpengine \
  -v $(pwd)/secrets/db_password:/run/secrets/db_password:ro \
  -v $(pwd)/secrets/auth_secret:/run/secrets/auth_secret:ro \
  -v $(pwd)/secrets/topology_secret:/run/secrets/topology_secret:ro \
  tsisip-opensips:latest \
  /entrypoint.sh /usr/local/sbin/opensips -c -f /etc/opensips/opensips.cfg
```

## Database

```bash
# Start the database and verify schema initialization
docker compose up -d postgres
docker compose exec postgres psql -U opensips -d opensips -c "\dt"
```

## Full Stack

```bash
# Build all services
docker compose build

# Start the full stack
docker compose up -d
```

## Runtime SIP Validation

```bash
# T4.4 — OPTIONS 200 OK
docker run --rm --network tsisip_sip_edge alpine \
  sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && \
         sipsak -s sip:opensips:5060 -vv"

# T4.5 — INVITE 407 Proxy-Authenticate
python3 -c "
import socket
msg = b'INVITE sip:test@opensips:5060 SIP/2.0\r\n' \
      b'Via: SIP/2.0/UDP 172.22.0.1:5061;branch=z9hG4bK-invite123\r\n' \
      b'From: <sip:test@172.22.0.1>;tag=invitetag\r\n' \
      b'To: <sip:test@opensips:5060>\r\n' \
      b'Call-ID: test-invite-001@172.22.0.1\r\n' \
      b'CSeq: 1 INVITE\r\nMax-Forwards: 70\r\n' \
      b'Contact: <sip:test@172.22.0.1:5061>\r\n' \
      b'Content-Length: 0\r\n\r\n'
sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
sock.settimeout(5)
sock.sendto(msg, ('127.0.0.1', 5060))
data, _ = sock.recvfrom(4096)
print(data.decode())
"
```

## OCP Rebranding (Feature 002)

```bash
# Full orchestrated build
./scripts/build-ocp-theme.sh

# Individual steps
node build/generate-css-variables.js
node build/generate-manifest.js
msgfmt web/tsisip/locale/tsisip-en.po -o web/tsisip/locale/en_US/LC_MESSAGES/tsisip.mo
node tests/d3-jquery-coexistence.test.js
node tests/accessibility-audit.test.js
grep -c '!important' web/tsisip/css/tsisip-theme.css
docker build -t tsisip/ocp:latest -f docker/ocp/Dockerfile .
```

## Repository Checks

```bash
# List all tracked files (excluding .git and node_modules)
rg --files -uuu -g '!**/.git/**' -g '!**/node_modules/**'

# Search for canonical keywords across documentation
rg -n "OpenSIPS|PostgreSQL|RTPengine|Asterisk|db_postgres|sanity" docs .github AGENTS.md CLAUDE.md .mcp.json
```

## GitNexus

```bash
# Check index freshness
npx gitnexus status

# Re-analyze after major changes
npx gitnexus analyze
```
