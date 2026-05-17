# TSiSIP VPS Deploy — Guia Rápido

> **Perfil:** vps-lite (5 serviços, <2GB RAM)  
> **Destino:** VPS TSiAPP (Ubuntu 24.04, 3.8GB RAM)  
> **Registry:** GHCR (`ghcr.io/b0yz4kr14/tsisip/*`)

## Pré-requisitos na VPS

- Ubuntu 22.04+ ou Debian 12+
- Docker 24.0+ com Docker Compose plugin
- Acesso root ou sudo
- Portas disponíveis: 5060/udp+tcp, 5061/tcp, 8084/tcp, 10000-20000/udp

## Deploy Rápido (via bootstrap)

```bash
# 1. Copiar o script para a VPS
scp deploy/scripts/vps-bootstrap.sh tsi@100.111.74.69:/tmp/

# 2. Executar na VPS (como root)
ssh -t tsi@100.111.74.69 "sudo bash /tmp/vps-bootstrap.sh"
```

O bootstrap faz automaticamente:
- Instala Docker e dependências
- Configura UFW (5060, 5061, 10000-20000/udp)
- Habilita fail2ban
- Clona o repo em `/opt/tsisip`
- Gera secrets (auth_secret 32 bytes, db_password, etc.)
- Gera certificados TLS dummy (substituir em produção!)
- Faz deploy em 3 waves com health checks
- Configura Nginx location `/TSiSIP/`
- Cria systemd service `tsisip-lite`

## Deploy Manual (controle total)

```bash
cd /opt/tsisip

# 1. Preparar secrets
mkdir -p secrets
openssl rand -base64 24 | head -c 32 > secrets/auth_secret
openssl rand -base64 24 | head -c 16 > secrets/db_password
# ... (ver vps-bootstrap.sh para lista completa)

# 2. Configurar .env
cp .env.example .env  # ou criar manualmente

# 3. Login GHCR
echo "$GITHUB_TOKEN" | docker login ghcr.io -u b0yz4kr14 --password-stdin

# 4. Deploy
./deploy/scripts/vps-deploy.sh

# 5. Nginx
sudo ./deploy/scripts/vps-nginx-setup.sh
```

## Serviços do Perfil vps-lite

| Serviço | Porta | Memória | Status |
|---------|-------|---------|--------|
| postgres | — | 512m | Essencial |
| rtpengine | 10000-20000/udp | 256m | Essencial |
| opensips | 5060/udp+tcp, 5061/tcp | 256m | Essencial |
| ocp | 8084/tcp | 256m | Essencial |
| backup | — | 128m | Essencial |

**Serviços desabilitados:** prometheus, grafana, alertmanager, asterisk, opensips-exporter, anomaly-detector.

## Operações

```bash
# Status
docker compose -f docker-compose.vps.yml ps

# Logs
docker compose -f docker-compose.vps.yml logs -f opensips

# Restart stack
sudo systemctl restart tsisip-lite

# Backup manual
docker compose -f docker-compose.vps.yml exec backup /backup/backup.sh

# Acessar OCP
# Local: http://localhost:8084
# Via Nginx: https://tsiapp.io/TSiSIP/
```

## Troubleshooting

| Sintoma | Causa provável | Solução |
|---------|---------------|---------|
| OOM crash | RAM insuficiente | Parar containers não-críticos; usar vps-lite |
| OpenSIPS não sobe | auth_secret != 32 bytes | `./deploy/scripts/vps-deploy.sh` corrige automaticamente |
| RTPengine bad fd | IP hardcoded antigo | Verificar `.env`: `RTPENGINE_INTERNAL_IP=172.21.0.1` |
| TLS não funciona | Certificados dummy | Substituir secrets/ca.crt, server.crt, server.key |
| 502 no Nginx | OCP não responde | Verificar `docker compose ps` e logs do OCP |

## Decisão Arquitetural

Ver `docs/architecture/ADR-001-vps-lite-profile.md`.

Resumo: A VPS com 3.8GB RAM não suporta o stack completo (13 serviços). O perfil vps-lite aloca ~1.4GB para TSiSIP, deixando margem para containers legados existentes.
