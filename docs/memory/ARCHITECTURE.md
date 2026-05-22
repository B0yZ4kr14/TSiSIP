# Architecture — TSiSIP

## System Shape

```text
Internet / SIP clients
        |
        | 5060/udp, 5060/tcp
        v
+-----------------------------+
| OpenSIPS Docker image       |
| TSiSIP edge proxy           |
| - auth (PostgreSQL-backed)  |
| - header routing            |
| - topology hiding           |
| - dispatcher failover       |
+-------------+---------------+
              |
              | internal SIP control
              v
+-----------------------------+
| Asterisk PBX backends       |
| private Docker network only |
+-----------------------------+

Internet / RTP clients
        |
        | 10000-20000/udp
        v
+-----------------------------+
| RTPengine media relay       |
| public RTP, internal control|
+-----------------------------+
```

## Docker Networks

| Network | Members | External Access | Purpose |
|---|---|---|---|
| sip_edge | OpenSIPS, RTPengine | Yes | Public SIP and RTP ingress |
| sip_internal | OpenSIPS, RTPengine, Asterisk | No | Internal SIP forwarding |
| db_internal | OpenSIPS, PostgreSQL | No | Database access only |

## Module Boundaries

- **SIP Proxy Layer**: OpenSIPS — auth, routing, topology hiding
- **Media Layer**: RTPengine — SDP rewriting, RTP relay
- **Application Layer**: Asterisk — IVR, voicemail, conferencing
- **Data Layer**: PostgreSQL — subscribers, dispatcher, tenants, audit
- **Control Plane**: OCP (web) — admin tools, CDR viewer, audit log
- **Build Layer**: Node.js scripts — CSS variables, manifest, i18n

## Key Integrations

- OpenSIPS ↔ PostgreSQL via `db_postgres` module
- OpenSIPS ↔ RTPengine via `rtpengine` module (control socket on sip_internal)
- OCP ↔ PostgreSQL via PDO (PHP)
- Prometheus ↔ OpenSIPS/RTPengine/Asterisk via exporters
- Grafana ↔ Prometheus for observability
- Let's Encrypt ↔ Nginx for TLS termination
