# TSiSIP Image Version Manifest

**Generated**: 2026-05-24
**Environment**: VPS tsiapp.io
**Policy**: All production images must use immutable digests or versioned tags.

---

## VPS-Lite Production Images (10 services)

| Service | Repository | Tag | Digest | Base Image |
|---------|------------|-----|--------|------------|
| admin-api | ghcr.io/b0yz4kr14/tsisip/admin-api | latest | sha256:80893e83e7b1531e571a57dac9bd3be1c1d35b6d7f9977ccccb29afd4077597e | — |
| asterisk-pbx | ghcr.io/b0yz4kr14/tsisip/asterisk | latest | sha256:c5681c4bba40ab6efaa5f9d80916c7cbce838399c7c71db95f040260add2c527 | — |
| backup | ghcr.io/b0yz4kr14/tsisip/backup | latest | sha256:afaea76c2f7f8a9137ae1c89f3de248066dd266b077d299329779e00339551ae | postgres:16@sha256:b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86 |
| certbot | ghcr.io/b0yz4kr14/tsisip/certbot | latest | sha256:e9f6565d07b8a9197cbd7ed328dde8779612d6081a526d082b75842ee8fde0f8 | certbot/certbot:latest |
| certbot-exporter | ghcr.io/b0yz4kr14/tsisip/certbot-exporter | latest | sha256:462574bd1578b502cb16afb228dded103ce45afbe19d06c8e5979cb750e65aba | python:3.11-slim |
| ocp | ghcr.io/b0yz4kr14/tsisip/ocp | latest | sha256:742ba513438e7261514c6f4ed3aa647c343f0ee3d4d85441eb4b645cfd5dcf17 | php:8.2-apache |
| opensips | ghcr.io/b0yz4kr14/tsisip/opensips | latest | sha256:d3dc9b9e6ce6c39acaffd826fc5eddb8dfb14bf7cfff2b29583e16b00f16be65 | debian:bookworm-slim |
| postgres | ghcr.io/b0yz4kr14/tsisip/postgres | latest | sha256:27f62a8d7028748e862489641a8604bead0700852ed22919edad01962ba28578 | postgres:16@sha256:b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86 |
| rtpengine | ghcr.io/b0yz4kr14/tsisip/rtpengine | latest | sha256:ca2b9fc7d613480234385662c4d29548121f6606fb46a21b5076a45178b5b31e | debian:bookworm-slim |

## Disabled Services (not running on VPS-lite)

| Service | Repository | Tag | Digest |
|---------|------------|-----|--------|
| anomaly-detector | ghcr.io/b0yz4kr14/tsisip/anomaly-detector | latest | sha256:23d96a1705b8000b8fa888a187d9db01f29f0e66e77f9e28f40dd73d1cc5775f |
| grafana | ghcr.io/b0yz4kr14/tsisip/grafana | latest | sha256:2b92266c0f9112e489785beae6a4d37165945677e1ee746231f9bae64e4f6bd4 |
| opensips-exporter | ghcr.io/b0yz4kr14/tsisip/opensips-exporter | latest | sha256:0c51f371717bb39d348d69622765160d3c5a6568e9fbdd7ffa98a457aeab9628 |
| prometheus | ghcr.io/b0yz4kr14/tsisip/prometheus | latest | sha256:9196af20ea63ec881443bbfd06e532c2711ae4cc910cfa30578662b12812e996 |

## Base Images with Pinning

| Base Image | Pinned Digest | Used By |
|------------|---------------|---------|
| postgres:16 | sha256:b6ccf02e9b47eac0d67b5eaa0ef56fd59163bffa5506f64e96ceb5053130ec86 | postgres, backup |
| debian:bookworm-slim | — | opensips, rtpengine |
| php:8.2-apache | — | ocp |
| python:3.11-slim | — | certbot-exporter |
| certbot/certbot:latest | — | certbot |

## Rollback Reference

To rollback any service to a known good image:

```bash
# Example: rollback opensips to previous digest
PREVIOUS_DIGEST="sha256:PREVIOUS"
docker pull ghcr.io/b0yz4kr14/tsisip/opensips@${PREVIOUS_DIGEST}
docker tag ghcr.io/b0yz4kr14/tsisip/opensips@${PREVIOUS_DIGEST} ghcr.io/b0yz4kr14/tsisip/opensips:latest
docker compose -f docker-compose.vps.yml up -d --force-recreate --no-deps opensips
```

## Notes

- `:latest` tags are used in development for convenience.
- Production deployments should reference digests directly in `docker-compose.vps.yml`.
- The `TSISIP_IMAGE_TAG` env var controls the tag used by compose; set to a versioned tag (e.g., `v1.2.3`) for deterministic deploys.
