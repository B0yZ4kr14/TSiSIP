# TSiSIP VPS Deploy Readiness Checklist

> **Status:** Deploy vps-lite+PBX executado; SIP publico bloqueado por filtro upstream  
> **VPS:** TSiAPP online  
> **Perfil:** vps-lite+PBX (7 servicos, <3GB RAM)  
> **Imagem OpenSIPS:** `ghcr.io/b0yz4kr14/tsisip/opensips:latest` (com tls_openssl.so)  
> **Imagem Backup:** `ghcr.io/b0yz4kr14/tsisip/backup:latest` (correcoes speckit-analyze aplicadas)

---

## Pre-Deploy (validado em 2026-05-19)

- [x] VPS online e acessivel via SSH (publico 179.190.15.116 e Tailscale 100.111.74.69)
- [x] Chave TSiHomeLab distribuida para usuarios `root` e `tsi`
- [x] Docker 24.0+ instalado e daemon rodando (Docker 29.5.0)
- [x] Docker Compose plugin instalado (v5.1.3)
- [x] Git instalado
- [x] Nginx instalado e configurado (tsiapp-https site)
- [x] UFW configurado integralmente: 5060/tcp+udp, 5061/tcp e RTP estao liberados
- [x] fail2ban ativo
- [x] Espaco em disco >= 10GB livre
- [x] RAM >= 4GB

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

- [x] `docker compose -f docker-compose.vps.yml ps` mostra 7 containers UP/healthy
- [x] PostgreSQL healthy (`tsisip-postgres-1`)
- [x] OpenSIPS healthy (`docker exec tsisip-opensips-1 /usr/local/bin/healthcheck.sh`)
- [x] OCP responde via Nginx publico: `https://tsiapp.io/TSiSIP/` retorna 302 para `/TSiSIP/login.php`
- [x] Nginx roteia `/TSiSIP/` corretamente
- [x] TLS modules carregados e socket 5061/tcp escutando localmente
- [x] Auth secret corrigido anteriormente para 32 bytes
- [x] Backup container esta UP/healthy
- [x] Asterisk PBX 1 e 2 estao UP/healthy
- [x] Dispatcher set 1 contem `asterisk-pbx-1` e `asterisk-pbx-2` com `state=0`
- [x] Metricas de backup acessiveis em loopback (`curl 127.0.0.1:9101/metrics`) *(validado em 2026-05-19; nao exposto externamente)*

## Funcionalidades a Validar

- [x] Registro/roteamento SIP interno via OpenSIPS validado com digest INVITE ate Asterisk
- [x] TLS socket sobe localmente em 5061/tcp
- [x] RTPengine publica faixa 10000-10999/udp no perfil vps-lite
- [x] OCP painel de controle acessivel via Nginx
- [x] Backup manual funcional (artefato criptografado criado em 2026-05-19)
- [x] WAL archive funcional (`/backup/wal/*.gz` criado apos `pg_switch_wal()` em 2026-05-19)
- [x] Validacao manual funcional (execucao manual validada em 2026-05-19)
- [x] Purge manual funcional (execucao manual validada em 2026-05-19)
- [ ] Primeira janela automatica observada: backup 02:00 UTC, purge 03:00 UTC, validate 04:00 UTC

## Pendencias Live

- [ ] Liberar 5060/5061 externamente fora do VPS: scans externos reportaram `filtered` e `tcpdump` no VPS capturou 0 SYN, apesar de Docker e UFW estarem corretos.
- [x] Adicionar regra UFW para 5061/tcp se SIP TLS precisar ser publicado.
- [x] Inserir destinos PBX reais na tabela `dispatcher`.
- [x] Revalidar SIP OPTIONS e INVITE autenticado dentro do VPS.

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
- [x] Adicionar Asterisk interno ao perfil vps-lite
- [ ] Adicionar opensips-exporter + anomaly-detector
- [ ] Configurar Alertmanager com webhook real (Slack/email)
- [ ] Substituir certificados TLS dummy por certificados reais
- [ ] Configurar rclone com credenciais reais do MinIO

---

**Ultima atualizacao:** 2026-05-19  
**Responsavel:** Arquiteto DevSecOps TSiSIP
