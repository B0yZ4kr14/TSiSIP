# TSiSIP VPS Deploy — Guia Rápido

> **Perfil:** vps-lite+PBX (7 serviços, ~2.9GB RAM alocado)  
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
| asterisk-pbx-1 | interno | 768m | Essencial para validacao SIP fim-a-fim |
| asterisk-pbx-2 | interno | 768m | Essencial para failover/dispatcher |
| backup | 127.0.0.1:9101/tcp | 128m | Essencial |

**Serviços desabilitados no VPS-lite:** prometheus, grafana, alertmanager, opensips-exporter, anomaly-detector. O exporter de backup fica ativo em loopback (`127.0.0.1:9101`) para métricas locais.

## Operações

```bash
# Status
docker compose -f docker-compose.vps.yml ps

# Logs
docker compose -f docker-compose.vps.yml logs -f opensips

# Restart stack
sudo systemctl restart tsisip-lite

# Backup manual
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/backup.sh

# Validar ultimo backup
docker compose -f docker-compose.vps.yml exec backup /usr/local/bin/validate.sh

# Ver metricas locais de backup/RPO
curl http://127.0.0.1:9101/metrics

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
| SIP externo 5060/5061 filtrado | Bloqueio upstream antes do host | Confirmar com `tcpdump` no VPS e abrir ACL/NAT no provedor/edge |
| RPO alerta em banco ocioso | Monitor antigo baseado apenas em timestamp | Usar imagem atual: compara `current_wal` com `last_archived_wal` |

## Decisão Arquitetural

Ver `docs/architecture/ADR-001-vps-lite-profile.md`.

Resumo: A VPS com ~4GB RAM não suporta o stack completo (13 serviços). O perfil vps-lite+PBX mantém SIP edge, media relay, DB, OCP, backup e dois PBXs internos, deixando observabilidade completa para fase posterior.
