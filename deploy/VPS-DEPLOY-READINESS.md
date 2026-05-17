# TSiSIP VPS Deploy Readiness Checklist

> **Status:** Pronto para deploy  
> **VPS:** TSiAPP (upgrade de hardware em andamento)  
> **Perfil:** vps-lite (5 servicos, <2GB RAM)  
> **Imagem OpenSIPS:** `ghcr.io/b0yz4kr14/tsisip/opensips:latest` (com tls_openssl.so)  
> **Imagem Backup:** `ghcr.io/b0yz4kr14/tsisip/backup:latest` (correcoes speckit-analyze aplicadas)

---

## Pre-Deploy (na VPS, apos upgrade de hardware)

- [ ] VPS online e acessivel via SSH (Tailscale 100.111.74.69 ou publico)
- [ ] Docker 24.0+ instalado e daemon rodando
- [ ] Docker Compose plugin instalado
- [ ] Git instalado
- [ ] Nginx instalado e configurado (tsiapp-https site)
- [ ] UFW configurado (portas 22, 80, 443, 5060/udp+tcp, 5061/tcp, 10000-20000/udp)
- [ ] fail2ban ativo
- [ ] Espaco em disco >= 10GB livre
- [ ] RAM >= 4GB (ideal 8GB para stack completo futuro)

## Deploy (bootstrap automatico)

```bash
# Opcao 1: Bootstrap completo
sudo bash /tmp/vps-bootstrap.sh

# Opcao 2: Deploy manual passo a passo
cd /opt/tsisip
./deploy/scripts/vps-deploy.sh      # 3 waves com health checks
sudo ./deploy/scripts/vps-nginx-setup.sh  # Configura /TSiSIP/ no nginx
```

## Post-Deploy Validation

- [ ] `docker compose -f docker-compose.vps.yml ps` mostra 5 containers UP
- [ ] PostgreSQL healthy (`docker compose ps postgres | grep healthy`)
- [ ] OpenSIPS healthy (`docker compose ps opensips | grep healthy`)
- [ ] OCP responde em `http://localhost:8084/login.php`
- [ ] Nginx roteia `/TSiSIP/` corretamente (`curl -f https://tsiapp.io/TSiSIP/`)
- [ ] TLS modules carregados (`docker compose exec opensips ls /usr/local/lib64/opensips/modules/ | grep tls`)
- [ ] Auth secret tem 32 bytes (`wc -c secrets/auth_secret`)
- [ ] Backup container executa cron (`docker compose logs backup | grep cron`)
- [ ] Metricas Prometheus acessiveis (`curl localhost:9101/metrics`)

## Funcionalidades a Validar

- [ ] Registro SIP via OpenSIPS (5060/udp)
- [ ] TLS socket sobe quando certificados reais sao configurados
- [ ] RTPengine responde em porta 10000-20000/udp
- [ ] OCP painel de controle acessivel via Nginx
- [ ] Backup diario executa as 02:00 UTC
- [ ] WAL archive funciona (`ls /backup/wal/`)
- [ ] Validacao diaria executa as 04:00 UTC
- [ ] Purge executa as 03:00 UTC

## Rollback (se necessario)

```bash
# Parar stack
docker compose -f docker-compose.vps.yml down

# Restaurar backup PostgreSQL (se necessario)
docker compose -f docker-compose.vps.yml exec postgres pg_restore ...

# Reverter para imagem anterior (se build quebrado)
docker pull ghcr.io/b0yz4kr14/tsisip/opensips:<tag-anterior>
```

## Fase 2 (apos estabilizacao do vps-lite)

- [ ] Adicionar Prometheus + Grafana (docker-compose.prod.yml)
- [ ] Adicionar Asterisk (se necessario)
- [ ] Adicionar opensips-exporter + anomaly-detector
- [ ] Configurar Alertmanager com webhook real (Slack/email)
- [ ] Substituir certificados TLS dummy por certificados reais
- [ ] Configurar rclone com credenciais reais do MinIO

---

**Ultima atualizacao:** 2026-05-17  
**Responsavel:** Arquiteto DevSecOps TSiSIP
