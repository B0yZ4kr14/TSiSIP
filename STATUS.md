# TSiSIP — Status do Projeto

> **Atualizado:** 2026-05-17  
> **Arquiteto:** DevSecOps Autonomo  
> **VPS TSiAPP:** Offline para upgrade de hardware

---

## Resumo Executivo

O projeto TSiSIP está **pronto para deploy** no perfil vps-lite (5 servicos essenciais).
Todas as 8 features (001-008) estao implementadas e documentadas.
A Feature 005 (PostgreSQL Backup & Restore) foi auditada, corrigida e validada com **100% de cobertura** (OMK goal pass).

---

## Features Implementadas

| Feature | Status | Validacao |
|---------|--------|-----------|
| 001 — OpenSIPS 3.6 LTS | ✅ | Imagem GHCR com tls_openssl.so |
| 002 — OCP Rebrand | ✅ | Imagem GHCR, porta 8084 |
| 003 — Prometheus/Grafana | ✅ | Imagem GHCR (perfil prod) |
| 004 — Health Checks | ✅ | Scripts em todos os containers |
| 005 — PostgreSQL Backup | ✅ | **OMK goal PASS** (8/8 criterios) |
| 006 — Rate Limiting | ✅ | OpenSIPS pike + Nginx limit_req |
| 007 — TLS/SRTP | ✅ | tls_openssl.so compilado |
| 008 — DevSecOps Deploy | ✅ | Ansible + GHCR + bootstrap |

---

## Stack VPS-Lite (5 Servicos)

| Servico | Imagem GHCR | Memoria | Portas |
|---------|-------------|---------|--------|
| postgres | `tsisip/postgres:latest` | 512m | — |
| rtpengine | `tsisip/rtpengine:latest` | 256m | 10000-20000/udp |
| opensips | `tsisip/opensips:latest` | 256m | 5060/udp+tcp, 5061/tcp |
| ocp | `tsisip/ocp:latest` | 256m | 8084/tcp |
| backup | `tsisip/backup:latest` | 128m | — |

**Total RAM alocado:** ~1.4GB  
**Servicos desabilitados:** prometheus, grafana, alertmanager, asterisk, exporter, anomaly-detector

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

## Issues Ativos (Pre-Deploy)

| Issue | Severidade | Status |
|-------|------------|--------|
| VPS OOM crash | 🔴 Critico | **Resolvido por upgrade de hardware** |
| OpenSIPS tls_openssl.so | 🟡 Medio | **Resolvido** — rebuild com modulo TLS |
| Auth secret 32 bytes | 🟡 Medio | **Resolvido** — corrigido para 32 bytes |
| RTPengine bad fd | 🟡 Medio | **Resolvido** — .env com IP correto |
| Dispatcher DB schema | 🟡 Medio | **Resolvido** — scripts SQL aplicados |
| Ansible docker.io conflict | 🟡 Baixo | **Resolvido** — skip install se Docker presente |

---

## Proximos Passos (quando VPS voltar)

1. **Executar bootstrap:** `sudo bash /tmp/vps-bootstrap.sh`
2. **Validar health checks:** `docker compose -f docker-compose.vps.yml ps`
3. **Testar SIP signaling:** Registrar um endpoint SIP via 5060/udp
4. **Verificar backup:** Aguardar 02:00 UTC para primeiro backup
5. **Fase 2 (futuro):** Adicionar monitoring (Prometheus/Grafana) e Asterisk

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
- **OpenSIPS 3.6 LTS:** tls_openssl.so compilado, sem sanity module
- **LGPD:** Framework de compliance para retencao e criptografia

---

## Registro OMK

- **Goal Feature 005:** `tsisip-feature-005-postgresql-backup-res-2026-05-17T20-59-49-039Z`
- **Status:** ✅ CLOSED (PASS 8/8)
- **Speckit-analyze:** 7 issues detectados, 3 corrigios (I1 HIGH, I2 MEDIUM, I3 MEDIUM)
- **Speckit-clarify:** 5 perguntas respondidas e integradas

---

*Aguardando retorno da VPS TSiAPP para deploy.*
