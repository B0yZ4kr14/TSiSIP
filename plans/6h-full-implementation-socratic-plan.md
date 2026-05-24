# Plano de Implementação Completa — 6 Horas Ininterruptas
## TSiSIP VPS Production Stabilization
### Modo: Socrático-Popperiano | Ferramentas: Speckit + GitNexus + OMK

---

## TL;DR

Sessão de 6 horas para **consolidar, corrigir, validar e documentar** o estado de produção do TSiSIP no VPS TSiAPP. Não é expansão de escopo — é **falsificação de premissas** sobre o que achamos que está funcionando vs. o que realmente está.

**Pergunta central (Socrática)**: *Se o stack está "funcionando", por que ainda temos pendências operacionais, stubs não validados e documentação desatualizada?*

**Hipótese a falsificar**: *"O TSiSIP está pronto para produção."*

---

## Estado Atual (Baseline)

### O que está operacional (evidência verificável)
| Componente | Status | Evidência |
|---|---|---|
| PostgreSQL | ✅ Up | Container `tsisip-postgres-1` healthy |
| OCP Web | ✅ Acessível | `https://tsiapp.io/TSiSIP` responde 200 |
| OCP Auth | ✅ Funcional | Login `Admin` funciona, bcrypt OK |
| OCP Admin Tools | ✅ Implementado | subscribers, cdr-viewer, dispatcher CRUD real |
| Backup | ✅ Configurado | `backup.sh` + `replicate.sh` template pronto |
| Nginx proxy | ✅ Operacional | `/TSiSIP/` → OCP container bridge IP (userland-proxy=false) or `127.0.0.1:8084` (userland-proxy=true) |

### O que está INCOMPLETO ou NÃO VALIDADO (gaps críticos)
| Componente | Status | Risco |
|---|---|---|
| OpenSIPS | ⚠️ Stub/Template | `opensips.cfg.tpl` não renderizado em produção |
| RTPengine | ⚠️ Container existe | Não validado se está relayando RTP real |
| Asterisk PBX | ⚠️ Container existe | Não validado se registra/registra |
| Dispatcher | ⚠️ DB tem dados | OpenSIPS não está usando (sem OpenSIPS rodando) |
| TLS/SRTP | ❌ Não implementado | Spec 007 parcial — certs existem mas não configurados |
| Observability | ❌ Não implementado | Spec 003 — Prometheus/Grafana não deployados |
| Rate Limiting | ❌ Não implementado | Spec 006 — `limit_req` nginx básico apenas |
| CDR real | ⚠️ Tabela existe | Sem tráfego SIP real, CDR vazio |

### Premissas a serem falsificadas
1. **Premissa P1**: "OCP funcionando = stack funcional." → *Falsificável*: OCP não depende de OpenSIPS/RTPengine para responder HTTP 200.
2. **Premissa P2**: "Containers `Up` = serviço saudável." → *Falsificável*: OpenSIPS pode estar em restart loop ou com config inválida.
3. **Premissa P3**: "Spec implementada = feature validada." → *Falsificável*: Feature 012 foi implementada mas não passou por teste de carga/segurança.
4. **Premissa P4**: "Backup configurado = recuperável." → *Falsificável*: Nunca foi feito restore teste.

---

## Arquitetura do Plano (6 Horas)

```
Hora 0.0 ─┬─ FASE 1: Diagnóstico Popperiano (45min)
          │   └─ Questionar premissas, coletar evidências brutas
          │
Hora 0.75 ├─ FASE 2: Análise de Impacto (45min)
          │   └─ GitNexus + Speckit: mapear dependências e riscos
          │
Hora 1.5 ─┼─ FASE 3: Correções Críticas (2h)
          │   └─ Consolidar configs, corrigir gaps, estabilizar runtime
          │
Hora 3.5 ─┼─ FASE 4: Validação TDD (1.5h)
          │   └─ RED→GREEN→REFACTOR com evidências executáveis
          │
Hora 5.0 ─┴─ FASE 5: Consolidação e Documentação (1h)
              └─ Evidências finais, relatório, commit, tag
```

---

## FASE 1: Diagnóstico Popperiano (0:00–0:45)

### Objetivo
Coletar evidências brutas sobre o estado REAL do stack, sem filtros. Cada verificação deve produzir um artefato em `evidence/phase1/`.

### Checklist Socrático

#### S1.1 — O que sabemos vs. o que podemos provar?
```bash
# Executar no VPS via SSH
ssh root@TSIAPP_TAILSCALE_IP "cd /opt/tsisip && docker compose ps"
```
**Artefato**: `evidence/phase1/s1.1-container-status.txt`
**Pergunta Popperiana**: *Se algum container estiver `restarting`, qual premissa sobre estabilidade é falsificada?*

#### S1.2 — OpenSIPS está realmente processando SIP?
```bash
# Teste de sinalização SIP direto
ssh root@TSIAPP_TAILSCALE_IP "docker compose exec opensips opensipsctl mi ps"
ssh root@TSIAPP_TAILSCALE_IP "docker compose exec opensips opensips -c -f /etc/opensips/opensips.cfg"
```
**Artefato**: `evidence/phase1/s1.2-opensips-config-valid.txt`
**Pergunta Popperiana**: *Se `opensips -c` falhar, a premissa de que temos um proxy SIP funcional é falsificada?*

#### S1.3 — OCP admin tools são realmente seguros?
```bash
# Verificar se há SQL injection possível
# Tentar bypass de auth com credenciais comuns
# Verificar se CSRF token é único por sessão
```
**Artefato**: `evidence/phase1/s1.3-ocp-security-smoke.txt`
**Pergunta Popperiana**: *Se conseguirmos acessar subscribers.php sem autenticação, a premissa de segurança baseada em role é falsificada?*

#### S1.4 — Backup é realmente recuperável?
```bash
ssh root@TSIAPP_TAILSCALE_IP "ls -la /opt/tsisip/backups/ && ls -la /opt/tsisip/secrets/"
```
**Artefato**: `evidence/phase1/s1.4-backup-inventory.txt`
**Pergunta Popperiana**: *Se não houver backup dos últimos 7 dias, a premissa de DR é falsificada?*

#### S1.5 — Schema DB está alinhado com código?
```bash
ssh root@TSIAPP_TAILSCALE_IP "docker exec tsisip-postgres-1 psql -U opensips -d opensips -c '\dt'"
```
**Artefato**: `evidence/phase1/s1.5-db-schema.txt`
**Pergunta Popperiana**: *Se o código espera colunas que não existem (como `created_at` em `subscriber`), a premissa de alinhamento schema/código é falsificada?*

### Deliverável Fase 1
- Diretório `evidence/phase1/` com 5+ artefatos de evidência
- `phase1-report.md`: lista de premissas falsificadas e não-falsificadas

---

## FASE 2: Análise de Impacto com GitNexus + Speckit (0:45–1:30)

### Objetivo
Usar ferramentas de análise para mapear dependências, identificar código morto, e validar consistência cross-artifact.

### A2.1 — GitNexus Impact Analysis
```bash
# Reindexar com código atual
cd /home/b0yz4kr14/Projects/TSiSIP
npx gitnexus analyze

# Impacto das mudanças recentes
npx gitnexus detect-changes --repo TSiSIP

# Contexto de símbolos críticos
npx gitnexus context --repo TSiSIP "authenticateUser"
npx gitnexus context --repo TSiSIP "getDb"
```
**Artefato**: `evidence/phase2/a2.1-gitnexus-impact-report.md`

### A2.2 — Speckit Cross-Artifact Analysis
```bash
# Analisar consistência entre spec.md, plan.md, tasks.md para specs críticas
specify analyze specs/012-ocp-admin-tools-restoration/
specify analyze specs/009-vps-deploy-automation/
```
**Artefato**: `evidence/phase2/a2.2-speckit-consistency-report.md`

### A2.3 — OMK Memory Check
```bash
# Verificar estado da memória do projeto
omk_memory_status
omk_memory_mindmap --query "TSiSIP production deployment"
```
**Artefato**: `evidence/phase2/a2.3-omk-memory-snapshot.json`

### A2.4 — Socratic Review
Perguntas a serem respondidas com base nos relatórios:
1. *Qual é o símbolo com maior blast radius se alterado?*
2. *Quais specs têm tasks.md desatualizado (implementado mas não marcado)?*
3. *Existe código morto que pode ser removido?*
4. *Quais dependências não estão documentadas?*

### Deliverável Fase 2
- Relatório de impacto com classificação de risco (CRITICAL/HIGH/MEDIUM/LOW)
- Lista de inconsistências spec/plan/tasks
- Recomendações de priorização para Fase 3

---

## FASE 3: Correções Críticas (1:30–3:30)

### Objetivo
Aplicar correções mínimas e necessárias baseadas nas evidências das Fases 1-2. **Regra**: nenhuma mudança sem teste de regressão.

### C3.1 — Alinhamento Schema/Código (se S1.5 falsificou)
**Se** `subscriber` não tem `created_at` mas código espera:
- Opção A: Adicionar coluna via migration
- Opção B: Remover referência do código
- **Critério de decisão**: menor blast radius

**Se** `cdr` usa `start_time` não `call_start`:
- Já corrigido em Feature 012 → verificar se há outros arquivos com schema antigo

### C3.2 — OpenSIPS Config Stabilization
**Se** S1.2 mostrar config inválido:
- Corrigir `opensips.cfg.tpl` para usar variáveis de ambiente corretas
- Validar com `opensips -c` antes de deploy
- **Impacto**: sem OpenSIPS funcional, o resto do stack é inútil para SIP

### C3.3 — Segurança OCP (se S1.3 falsificou)
**Se** encontrar vulnerabilidades:
- Adicionar rate limiting no nível PHP (não só nginx)
- Validar CSRF em TODAS as actions POST
- Verificar SQL injection em todas as queries dinâmicas

### C3.4 — Docker Compose Consolidação
- Sincronizar `docker-compose.yml` e `docker-compose.vps.yml`
- Garantir que healthchecks estejam configurados em TODOS os serviços
- Verificar dependências (`depends_on` com `condition`)

### C3.5 — Secrets e Environment
- Validar que todos os secrets necessários existem em `secrets/`
- Verificar que `.env` não está committed (security)
- Garantir que `entrypoint.sh` copia secrets com permissões corretas

### Deliverável Fase 3
- Commits incrementais (um por correção crítica)
- `evidence/phase3/` com before/after de cada correção
- Regressão zero: nenhum teste existente quebrou

---

## FASE 4: Validação TDD (3:30–5:00)

### Objetivo
Executar testes RED→GREEN→REFACTOR para todas as correções da Fase 3.

### T4.1 — Testes de Infraestrutura (Smoke)
```bash
# Container health
docker compose ps
# Esperado: todos healthy ou up estável por 5min

# Port exposure policy
ss -tlnp | grep -E "5060|5432|8084"
# Esperado: 5060/udp, 5060/tcp, 8084/tcp apenas
```

### T4.2 — Testes de Sinalização SIP
```bash
# OPTIONS probe
sipsak -s sip:opensips:5060 -vv
# Esperado: SIP/2.0 200 OK

# INVITE probe (deve retornar 407 para não-autenticado)
python3 tests/sip_probe.py --method INVITE --expect 407
```

### T4.3 — Testes OCP E2E
```bash
# Login flow
curl -fsSL -c /tmp/cookies.txt -b /tmp/cookies.txt \
  -d "username=Admin&pass=REDACTED" \
  https://tsiapp.io/TSiSIP/login.php
# Esperado: redirect para dashboard

# CRUD subscribers (com CSRF)
# Listar
curl -fsSL -b /tmp/cookies.txt https://tsiapp.io/TSiSIP/subscribers.php | grep "Subscriber Management"
# Esperado: contém "Subscriber Management"
```

### T4.4 — Testes de Segurança
```bash
# Tentativa de SQL injection
curl -fsSL "https://tsiapp.io/TSiSIP/cdr-viewer.php?from=2024-01-01'; DROP TABLE cdr; --"
# Esperado: erro tratado, tabela intacta

# Tentativa de CSRF sem token
curl -fsSL -X POST -d "action=delete&id=1" \
  https://tsiapp.io/TSiSIP/subscribers.php
# Esperado: erro 403 ou redirect com mensagem de token inválido

# Acesso não-autenticado a página admin
curl -fsSL https://tsiapp.io/TSiSIP/subscribers.php
# Esperado: redirect para login.php
```

### T4.5 — Testes de Backup/Restore
```bash
# Backup existe e é válido
ls -la /opt/tsisip/backups/
# Esperado: arquivo .sql.gz dos últimos 7 dias

# Restore dry-run (não aplicar, só verificar)
gunzip -t /opt/tsisip/backups/latest.sql.gz
# Esperado: OK
```

### Deliverável Fase 4
- `evidence/phase4/` com output de todos os testes
- `test-report.md`: matriz de testes (pass/fail/skip) com evidências
- Falhas documentadas com tickets de follow-up se não resolvidas na janela

---

## FASE 5: Consolidação e Documentação (5:00–6:00)

### Objetivo
Consolidar todas as evidências, atualizar documentação, e criar relatório final de readiness.

### D5.1 — Evidências Consolidadas
```bash
# Criar bundle de evidências
tar czf evidence-$(date +%Y%m%d-%H%M).tar.gz evidence/
```

### D5.2 — Documentação Atualizada
- `AGENTS.md`: atualizar seção de build/test com novos comandos
- `docs/TSiSIP-OPERATOR-RUNBOOK.md`: adicionar troubleshooting baseado nas falhas encontradas
- `docs/TSiSIP-CANONICAL-SPEC.md`: marcar specs implementadas vs. pendentes

### D5.3 — Relatório Final Socrático-Popperiano
Estrutura:
```markdown
# Relatório de Validação — [Data]

## Premissas Testadas
| Premissa | Método de Falsificação | Resultado | Evidência |
|---|---|---|---|
| P1: OCP = stack funcional | Isolar OCP de OpenSIPS | FALSIFICADA | OCP responde 200 sem OpenSIPS |
| P2: Containers Up = saudável | Verificar restart count | NÃO FALSIFICADA | 0 restarts em 24h |
| ... | ... | ... | ... |

## Correções Aplicadas
| Issue | Causa Raiz | Fix | Teste de Regressão |
|---|---|---|---|
| ... | ... | ... | ... |

## Decisões Socráticas
| Questão | Opção A | Opção B | Escolha | Racional |
|---|---|---|---|---|
| Schema mismatch subscriber | Migration | Code fix | Code fix | Menor blast radius |

## Estado de Readiness
- [ ] SIP Proxy funcional
- [ ] OCP seguro e operacional
- [ ] Backup validado
- [ ] Documentação atualizada
```

### D5.4 — Commit e Tag
```bash
git add -A
git commit -m "chore(stabilization): 6h socratic consolidation

- Fix schema/code alignment (subscriber, cdr)
- Stabilize OpenSIPS config validation
- Harden OCP security (rate limiting, CSRF)
- Add comprehensive smoke tests
- Update documentation with evidence-based decisions

Evidence: evidence/2026MMDD-HHMM/"

git tag -a v0.6.0-stabilized -m "Post-6h socratic stabilization"
git push origin master --tags
```

### D5.5 — OMK Goal Closure
```bash
omk_goal_verify <goal-id>
omk_goal_close <goal-id>
```

### Deliverável Fase 5
- Relatório final em `reports/6h-stabilization-report-YYYYMMDD.md`
- Tag `v0.6.0-stabilized` no GitHub
- OMK goal fechado com evidências

---

## Ferramentas e Comandos por Fase

| Fase | Speckit | GitNexus | OMK |
|---|---|---|---|
| 1 | `specify checklist` para gaps | `detect-changes` para delta | `memory_mindmap` para estado |
| 2 | `analyze` cross-artifact | `impact`, `context` | `goal_create` com critérios |
| 3 | `implement` tasks críticos | `detect-changes` pós-fix | `evidence_add` por critério |
| 4 | `validate` tasks | — | `goal_verify` |
| 5 | — | `wiki` para docs | `goal_close` |

---

## Ritmo Socrático por Hora

| Hora | Ritual | Pergunta |
|---|---|---|
| 0:00 | **Socratic Kickoff** | "O que ACHAMOS que está funcionando?" |
| 0:45 | **Falsification Checkpoint** | "Quais premissas já foram falsificadas?" |
| 1:30 | **Impact Review** | "Se mudarmos X, o que mais quebra?" |
| 3:30 | **Regression Gate** | "Nada que funcionava antes pode quebrar agora." |
| 5:00 | **Evidence Audit** | "Temos evidência para cada claim?" |
| 6:00 | **Final Socratic** | "O que ainda NÃO sabemos?" |

---

## Guardrails (Must NOT Have)

1. **Não expandir escopo**: NAT avançado, transcoding, cluster multi-node → fora da janela 6h
2. **Não alterar baseline arquitetural**: sem ADR, não muda de PostgreSQL para outro DB
3. **Não commitar secrets**: `.env`, `secrets/`, credenciais nunca vão para git
4. **Não ignorar falhas**: se um teste falha, documenta e decide: fixa agora ou ticket de follow-up explícito
5. **Não trabalhar sem evidência**: toda claim deve ter artefato em `evidence/`

---

## Critérios de Sucesso

### Definition of Done (6h)
- [ ] Fase 1: 5+ evidências de diagnóstico coletadas
- [ ] Fase 2: Relatório de impacto com risco classificado
- [ ] Fase 3: Correções críticas aplicadas com commits
- [ ] Fase 4: Matriz de testes com >80% pass rate
- [ ] Fase 5: Relatório final, tag, documentação atualizada

### Definition of NOT Done (escopo cortado se necessário)
- [ ] Observability stack (Prometheus/Grafana) → Spec 003, fora da janela
- [ ] TLS/SRTP full config → Spec 007, parcial apenas
- [ ] Rate limiting avançado → Spec 006, nginx básico é suficiente por ora
- [ ] Multi-tenant routing complexo → fora da janela

---

## Próximo Passo

Para iniciar a execução deste plano, execute:

```bash
# 1. Criar OMK Goal
omk_goal_create \
  --rawPrompt "6h Socratic Stabilization of TSiSIP VPS production stack" \
  --title "TSiSIP 6h Stabilization" \
  --riskLevel high

# 2. Iniciar sessão OMK
omk_start_session --project_folder TSiSIP --message "6h socratic stabilization session"

# 3. Criar diretório de evidências
mkdir -p evidence/phase{1,2,3,4,5}

# 4. Iniciar Fase 1
./plans/scripts/start-phase1.sh
```

---

*Plano criado em modo Socrático-Popperiano.*
*Toda premissa é falsificável. Toda claim exige evidência.*
*"Não sei" é preferível a "acho que sim".*
