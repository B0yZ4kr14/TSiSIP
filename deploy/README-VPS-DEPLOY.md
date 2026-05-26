# TSiSIP VPS Deploy — Guia Rápido

> **Perfil:** vps-lite+PBX (7 serviços essenciais, ~2.9GB RAM) / full-stack (13+ serviços, ~8GB RAM)  
> **Destino:** VPS TSiAPP (Ubuntu 24.04, 32GB RAM, 116GB disk)  
> **Registry:** GHCR (`ghcr.io/b0yz4kr14/tsisip/*`) — com fallback build-on-target  
> **Pipeline:** Feature 009 — `deploy/scripts/orchestrate-deploy.sh`

## Pré-requisitos na VPS

- Ubuntu 22.04+ ou Debian 12+
- Docker 24.0+ com Docker Compose plugin
- Acesso root ou sudo
- Portas disponíveis: 5060/udp+tcp, 5061/tcp, 8084/tcp, 10000-20000/udp

## Deploy via Pipeline Orquestrado (Recomendado)

O script `orchestrate-deploy.sh` implementa o pipeline completo com gates validados:

```bash
# Full pipeline: validação → build → push → deploy → verificação
./deploy/scripts/orchestrate-deploy.sh

# Dry-run: valida todos os gates sem mutar estado
./deploy/scripts/orchestrate-deploy.sh --dry-run

# Live-test: verificação pós-deploy apenas (após deploy manual)
./deploy/scripts/orchestrate-deploy.sh --live-test

# Forçar deploy apesar de HIGH risk no impact analysis
FORCE_DEPLOY=1 ./deploy/scripts/orchestrate-deploy.sh
```

### Build-on-Target Fallback

Quando o push para GHCR falha (ex: `permission_denied: write_package`), o pipeline ativa automaticamente o modo fallback:

```bash
# O script detecta falha no pusher() e seta FALLBACK_BUILD_ON_TARGET=1
# O deployer() então executa na VPS:
#   docker compose -f docker-compose.prod.yml -f docker-compose.build.yml build --parallel
```

O arquivo `docker-compose.build.yml` deve estar presente no repo e define os contextos de build corretos para cada serviço. Sem este arquivo, o fallback falhará com erros de `COPY`.

### Stages do Pipeline (gated)

| Gate | Nome | Descrição | Falha = halt? |
|------|------|-----------|---------------|
| 0 | Pre-flight | Disco, registry, sintaxe OpenSIPS, secrets scan, compose | Sim |
| 1 | Impact Analysis | Git diff + heurística de risco em core configs | Sim (override com `FORCE_DEPLOY=1`) |
| 2 | Build | Builder agent: build apenas imagens modificadas | Sim |
| 3 | Push | Pusher agent: tag + push GHCR; fallback build-on-target | Não (warn) |
| 4 | Deploy | Deployer agent: snapshot rollback, SSH, git pull, compose up | Sim |
| 5 | Verify | Verifier agent: health, HTTP, SIP OPTIONS, backup metrics | Sim → trigger rollback |

### Rollback Automático

Se o gate 5 (Verify) falhar, o pipeline restaura automaticamente as imagens pré-deploy a partir do snapshot capturado no gate 4. O snapshot é salvo em `.deploy-rollback/<run-id>-digests.txt`.

**Rollback manual (emergência)**:
```bash
# Na VPS
sudo docker compose -f docker-compose.prod.yml down
sudo docker compose -f docker-compose.prod.yml up -d postgres  # database first
sleep 10
sudo docker compose -f docker-compose.prod.yml up -d opensips rtpengine ocp
sudo docker compose -f docker-compose.prod.yml up -d  # remaining services
```

### Agentes OMK (funções shell)

Cada stage é implementado como uma função shell documentada no próprio script:

- **`builder()`** — OMK Builder Agent: detecta Dockerfiles modificados via `git diff`, builda apenas imagens afetadas.
- **`pusher()`** — OMK Pusher Agent: login GHCR, tag e push. Se credenciais ausentes, ativa `FALLBACK_BUILD_ON_TARGET`.
- **`deployer()`** — OMK Deployer Agent: SSH para target, captura snapshot de digests, sync de código, `docker compose pull && up`.
- **`verifier()`** — OMK Verifier Agent: health de containers, probe HTTP na OCP, probe SIP OPTIONS, métricas de backup.

## Deploy Rápido (via bootstrap — primeira instalação)

```bash
# 1. Copiar o script para a VPS
scp deploy/scripts/vps-bootstrap.sh tsi@179.190.15.116:/tmp/

# 2. Executar na VPS (como root)
ssh -t tsi@179.190.15.116 "sudo bash /tmp/vps-bootstrap.sh"

# Alternativa via Tailscale (requer tailscaled no cliente)
# scp deploy/scripts/vps-bootstrap.sh tsi@100.111.74.69:/tmp/
# ssh -t tsi@100.111.74.69 "sudo bash /tmp/vps-bootstrap.sh"
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
| asterisk_pbx_1 | interno | 768m | Essencial para validacao SIP fim-a-fim |
| asterisk_pbx_2 | interno | 768m | Essencial para failover/dispatcher |
| backup | internal (`metrics_host` network) | 128m | Essencial |

**Serviços desabilitados no VPS-lite:** prometheus, grafana, alertmanager, opensips_exporter, anomaly_detector. O exporter de backup fica ativo internamente na rede Docker `metrics_host` (`backup:9101`) devido a `userland-proxy=false` necessário para o RTPengine.

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
docker run --rm --network tsisip_metrics_host alpine wget -qO- http://backup:9101/metrics

# Acessar OCP
# Local: http://localhost:8084
# Via Nginx: https://tsiapp.io/TSiSIP/
```

### Backup Offsite (rclone/S3)

Para habilitar replicação offsite, configure o `.env` com as credenciais S3:

```bash
RCLONE_S3_ENDPOINT=https://s3.tsiapp.io
RCLONE_S3_PROVIDER=AWS
RCLONE_S3_REGION=us-east-1
RCLONE_BW_LIMIT=10M
```

Coloque as credenciais em `secrets/` (nunca commitar):

```bash
echo "SEU_ACCESS_KEY" > secrets/rclone_s3_access_key
echo "SEU_SECRET_KEY" > secrets/rclone_s3_secret_key
```

Reinicie o container backup para aplicar:

```bash
docker compose -f docker-compose.vps.yml up -d backup
```

Verifique a conectividade:

```bash
docker compose -f docker-compose.vps.yml exec backup \
  rclone ls --config /etc/rclone/rclone.conf remote:tsisip-backups
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
| Load average >100 | Múltiplos `docker compose up` concorrentes + memory pressure | Matar processos docker pendentes: `sudo pkill -f "docker compose up"`; aguardar load <10; reiniciar serviços |
| SSH "Broken pipe" durante deploy | VPS sobrecarregada, SSH timeout | Usar artifact transfer mode (`.tar.gz` via pipe) em vez de build-on-target |
| GHCR `permission_denied` | `GITHUB_TOKEN` sem scope para package write | Usar PAT com `write:packages` scope; ou habilitar fallback build-on-target |
| Certbot/Tailscale restart loop | Configuração incompleta ou dependência ausente | Verificar logs: `docker compose logs -f certbot tailscale_cert`; corrigir env vars |
| RTPengine `exec: "--interface=...": no such file` | ENTRYPOINT/CMD mal configurado no Dockerfile | Verificar que Dockerfile usa `ENTRYPOINT ["rtpengine"]` + `CMD ["--foreground", "--log-stderr"]` |

## Decisão Arquitetural

Ver `docs/architecture/ADR-001-vps-lite-profile.md`.

Resumo: A VPS com ~4GB RAM não suporta o stack completo (13 serviços). O perfil vps-lite+PBX mantém SIP edge, media relay, DB, OCP, backup e dois PBXs internos, deixando observabilidade completa para fase posterior.
