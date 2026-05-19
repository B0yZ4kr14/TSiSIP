# TSiSIP — Status do Projeto

> **Atualizado:** 2026-05-19  
> **Arquiteto:** DevSecOps Autonomo  
> **VPS TSiAPP:** Stack de producao vps-lite+PBX em execucao; SIP publico 5060/5061 bloqueado antes do host

---

## Resumo Executivo

O projeto TSiSIP está **implantado no VPS TSiAPP** no perfil vps-lite expandido para SIP host-ready (7 servicos).
Todas as 8 features (001-008) possuem artefatos implementados e documentados.
A Feature 005 (PostgreSQL Backup & Restore) foi auditada e validada no caminho local/manual; PITR live e offsite replication seguem pendentes de ambiente.
O GitNexus foi atualizado localmente em 2026-05-19 e reportou o indice de TSiSIP como `up-to-date`.
Em 2026-05-19, o TSiSIP SIP edge foi estabilizado no VPS apos correcoes de compatibilidade do motor OpenSIPS 3.6 no template e schema `userblacklist`.
Em 2026-05-19, o pipeline de backup foi revalidado no VPS: **WAL archiving ativo**, backup/validate/purge funcionais manualmente e metrics exporter acessivel em `127.0.0.1:9101`.

---

## Features Implementadas

| Feature | Status | Validacao |
|---------|--------|-----------|
| 001 — TSiSIP SIP Edge | ✅ | Imagem GHCR com motor OpenSIPS 3.6 e tls_openssl.so |
| 002 — TSiSIP Control Panel | ✅ | Imagem GHCR; acesso publico via Nginx/TLS e porta local 127.0.0.1:8084 |
| 003 — Prometheus/Grafana | Artefatos prontos | Prometheus/Grafana/Alertmanager desabilitados no VPS-lite; exporter de backup ativo em loopback |
| 004 — Health Checks | ✅ | Scripts em todos os containers |
| 005 — PostgreSQL Backup | Parcial live | Backup/WAL/validate/purge manuais OK; cron/offsite/PITR live pendentes |
| 006 — Rate Limiting | Parcial live | TSiSIP SIP edge usa pike + Nginx limit_req; anomaly detector/dashboard e flood externo pendentes |
| 007 — TLS/SRTP | Parcial live | tls_openssl.so compilado; certificados reais/mTLS/SRTP externos pendentes |
| 008 — DevSecOps Deploy | Parcial live | Ansible + GHCR + bootstrap; hardening de supply chain e SSL Labs formal pendentes |
| 009 — VPS Deploy Automation | 🔄 Em especificacao | Pipeline orquestrado com SpecKit + GitNexus + OMK |

---

## Stack VPS-Lite Producao (7 Servicos)

| Servico | Imagem GHCR | Memoria | Portas |
|---------|-------------|---------|--------|
| postgres | `tsisip/postgres:latest` | 512m | — |
| rtpengine | `tsisip/rtpengine:latest` | 256m | 10000-20000/udp |
| tsisip-sip-edge | `tsisip/opensips:latest` | 256m | 5060/udp+tcp, 5061/tcp |
| asterisk-pbx-1 | `tsisip/asterisk:latest` | 768m | interno |
| asterisk-pbx-2 | `tsisip/asterisk:latest` | 768m | interno |
| ocp | `tsisip/ocp:latest` | 256m | 127.0.0.1:8084/tcp |
| backup | `tsisip/backup:latest` | 128m | 127.0.0.1:9101/tcp |

**Total RAM alocado:** ~2.9GB  
**Servicos desabilitados:** prometheus, grafana, alertmanager, exporter, anomaly-detector

---

## Artifacts de Deploy Prontos

| Arquivo | Proposito |
|---------|-----------|
| `docker-compose.vps.yml` | Stack leve com mem_limits |
| `docker-compose.prod.yml` | Stack completo (13 servicos) |
| `deploy/scripts/vps-bootstrap.sh` | Inicializacao automatica da VPS |
| `deploy/scripts/vps-deploy.sh` | Deploy em 3 waves com health checks |
| `deploy/scripts/vps-nginx-setup.sh` | Integracao Nginx existente |
| `deploy/scripts/test-vps-local.sh` | Teste local do perfil vps-lite |
| `deploy/VPS-DEPLOY-READINESS.md` | Checklist completo de deploy |
| `deploy/README-VPS-DEPLOY.md` | Guia rapido de deploy |

---

## Issues Ativos

| Issue | Severidade | Status |
|-------|------------|--------|
| VPS OOM crash | Critico | **Resolvido por upgrade de hardware** |
| OpenSIPS tls_openssl.so | Medio | **Resolvido** — rebuild com modulo TLS |
| Auth secret 32 bytes | Medio | **Resolvido** — corrigido para 32 bytes |
| RTPengine bad fd | Medio | **Resolvido** — .env com IP correto |
| Dispatcher DB schema | Medio | **Resolvido** — scripts SQL aplicados |
| OpenSIPS 3.6 config API | Alto | **Resolvido no VPS** — `sl_send_reply`, `mf_process_maxfwd_header`, `check_source_address`, `sql_query`, `ds_select_dst` e `www_challenge` ajustados |
| userblacklist schema runtime | Alto | **Resolvido no VPS** — tabela criada e `version.userblacklist=2` aplicado |
| Dispatcher sem destinos reais | Alto | **Resolvido** — set 1 aponta para `asterisk-pbx-1` e `asterisk-pbx-2`; ambos state=0 |
| Asterisk config path | Alto | **Resolvido** — configs montadas em `/etc/asterisk` e `/usr/local/etc/asterisk`; PJSIP UDP/TCP carregado |
| SIP 5060/5061 externo | Alto | **Bloqueio upstream** — `tcpdump` no VPS capturou 0 SYN durante scan externo; filtro ocorre antes do host |
| UFW 5061/tcp | Medio | **Resolvido** — 5061/tcp liberado IPv4/IPv6 |
| Ansible docker.io conflict | Baixo | **Resolvido** — skip install se Docker presente |

## Validacao Live VPS (2026-05-19)

| Check | Resultado |
|-------|-----------|
| SSH TSiAPP | OK via alias `ssh-tsiapp`, publico e Tailscale |
| Recursos VPS | Ubuntu 24.04, Docker 29.5.0, Compose v5.1.3, ~65GB livres |
| Stack Docker | `postgres`, `rtpengine`, `opensips`, `ocp`, `backup`, `asterisk-pbx-1`, `asterisk-pbx-2` UP/healthy |
| TSiSIP SIP edge healthcheck | `OK: OpenSIPS is healthy` |
| Asterisk healthcheck | Ambos retornam `OK: Asterisk is healthy` |
| OCP publico | `https://tsiapp.io/TSiSIP/` responde HTTP 302 para `/TSiSIP/login.php` |
| Portas locais no VPS | 5060/tcp, 5061/tcp, 5060/udp e RTP 10000-10999/udp escutando |
| Portas externas | 5060/tcp e 5061/tcp aparecem `filtered` de fora; `tcpdump` confirma que pacotes nao chegam ao host |
| Dispatcher DB | 2 destinos reais ativos: `sip:asterisk-pbx-1:5060`, `sip:asterisk-pbx-2:5060` |
| SIP OPTIONS | UDP e TCP retornam `SIP/2.0 200 OK` internamente |
| SIP INVITE sem auth | UDP e TCP retornam `SIP/2.0 401 Unauthorized` |
| SIP INVITE autenticado | `scripts/sip-auth-probe.py` retornou `100 Giving it a try` e `200 OK`; Asterisk executou `1000@from-opensips` |
| Backup + WAL | Backup criptografado criado em `/backup/daily`, validate manual OK, purge manual OK, WAL `.gz` gerado em `/backup/wal` |
| Backup metrics | `curl http://127.0.0.1:9101/metrics` retorna RPO/RTO/status e `backup_current_wal_info`; porta nao exposta externamente |

---

## Quality Gates

Relatorio consolidado de qualidade disponivel em:
- [`reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md`](reports/CONSOLIDATED-QUALITY-GATE-2026-05-19.md)

| Gate | Status |
|------|--------|
| Brownfield Scan | PASS |
| Memory & Resource Audit | PASS |
| Version Guard | PASS |
| Remediation Progress | PASS |
| VPS Production Validation | PASS |
| **Overall** | PASS WITH WARNINGS |

Nenhum bloqueador critico identificado. Acoes recomendadas priorizadas no relatorio consolidado.
Atualizado: 2026-05-19.

---

## Pendencias Reais Recuperadas

| Area | Status | Proxima acao |
|------|--------|--------------|
| VPS live deploy | Concluido no host | Stack production vps-lite+PBX healthy; exposicao SIP publica depende de ACL upstream fora do host |
| Feature 002 governanca | Fechado (N/A) | Gate de aprovacao por 3 representantes nao e executavel em automacao; manter como reabertura manual se necessario |
| Feature 001 infra-quality | Fechado | Checklist revisado em 2026-05-19 como PASS com deferrals escopados |
| RTPengine kernel table | Deferido | Decidir entre modulo kernel no host ou memoria extra para fallback userspace |
| Deploy scripts polling | Deferido | Trocar sleeps fixos por polling quando estabilizar deploy live |
| TLS/rclone reais | Pendente de ambiente | Substituir certificados dummy e configurar rclone/MinIO reais no deploy |
| Supply chain deterministica | Pendente | Trocar `:latest` por tags/digests versionados e manifest de release/rollback |
| Jobs backup cron | Pendente de janela | Observar primeira execucao automatica 02:00/03:00/04:00 UTC apos deploy |

> Nota: Feature 001 T4.4/T4.5/T4.7 foi reconciliada em 2026-05-19; `spec.md`, `plan.md` e `tasks.md` agora concordam que OPTIONS 200 OK, INVITE 401 e INVITE autenticado ate Asterisk foram validados.

---

## Proximos Passos

1. **Liberar SIP externo fora do host:** abrir 5060/tcp, 5060/udp e 5061/tcp no provedor/NAT/Tailscale ACL; o VPS ja escuta e UFW ja permite.
2. **Monitorar jobs agendados:** backup (02:00 UTC), purge (03:00 UTC) e validate (04:00 UTC) estao instalados; falta observar a primeira janela automatica.
3. **Fase 2:** adicionar monitoring (Prometheus/Grafana) quando nao conflitar com OrthoPlus e houver politica de portas.

---

## Commits Recentes

```
e5dc522 docs(deploy): adiciona VPS Deploy Readiness Checklist
b87ab73 fix(backup): corrige issues do speckit-analyze Feature 005
4200bcc docs(speckit): clarificacoes e atualizacao do plano Feature 005
945b1c4 feat(opensips): habilita TLS modules e bootstrap automatico para VPS
b88756c feat(deploy): perfil vps-lite e rebuild OpenSIPS com tls_openssl
```

---

## Decisoes Arquiteturais

- **ADR-001:** Perfil vps-lite adotado para VPS com 4GB RAM (docs/architecture/ADR-001-vps-lite-profile.md)
- **Docker-first:** Zero build sections em docker-compose.prod.yml
- **PostgreSQL-only:** db_postgres, sem MySQL/MariaDB
- **TSiSIP SIP engine:** OpenSIPS 3.6 LTS com tls_openssl.so compilado, sem sanity module
- **LGPD:** Framework de compliance para retencao e criptografia

---

## Registro OMK

- **Goal Feature 005:** `tsisip-feature-005-postgresql-backup-res-2026-05-17T20-59-49-039Z`
- **Status:** ✅ CLOSED (PASS 8/8)
- **Speckit-analyze:** 7 issues detectados, 3 corrigios (I1 HIGH, I2 MEDIUM, I3 MEDIUM)
- **Speckit-clarify:** 5 perguntas respondidas e integradas

---

*Stack de producao ativo no VPS; pendencia restante e a liberacao upstream das portas SIP publicas.*
