# TSiSIP VPS Go-Live (24h, TDD-First)

## TL;DR

> **Quick Summary**: Replanejamento do zero para colocar TSiSIP em produção no VPS em 24h, com TDD desde o início e validação totalmente executável por agente.
>
> **Deliverables**:
> - Stack `vps-lite` estável (postgres, rtpengine, opensips, ocp, backup)
> - Testes TDD (smoke + integração crítica SIP/HTTP)
> - Runbook de rollback + evidências em `.sisyphus/evidence/`
>
> **Estimated Effort**: Large (24h janela operacional)
> **Parallel Execution**: YES — 3 waves + Final Verification
> **Critical Path**: T1 → T3 → T6 → T9 → F1-F4

---

## Context

### Original Request
Plano de execução a partir de agora, com prioridades, para produção no VPS.

### Interview Summary
- Escopo: **replanejar do zero**.
- Horizonte: **24h estabilização**.
- Estratégia de testes: **TDD desde o início**.
- Objetivo principal: **subir TSiSIP em produção no VPS**.

### Gap Review (fallback)
- Guardrails adicionados: sem expansão de escopo (NAT avançado/transcoding fora da janela), sem exposição pública de Asterisk/PostgreSQL.
- Assumptions explícitas: certs/segredos já provisionados em `secrets/` no VPS.

---

## Work Objectives

### Core Objective
Entregar go-live funcional e verificável do perfil `vps-lite` em 24h, com testes TDD cobrindo os caminhos críticos de SIP e acesso web.

### Concrete Deliverables
- `docker-compose.vps.yml` validado para produção imediata.
- Pipeline de testes de estabilização (RED→GREEN→REFACTOR) documentado e executado.
- Evidências operacionais de saúde e smoke tests.

### Definition of Done
- [x] Todos os serviços `vps-lite` em `healthy` ou `Up` estável.
- [x] Testes TDD críticos passando.
- [x] SIP OPTIONS retorna `200 OK`.
- [x] OCP acessível em `https://tsiapp.io/TSiSIP`.

### Must Have
- OpenSIPS 3.6 LTS apenas.
- PostgreSQL apenas.
- Secrets apenas via `secrets/`/env runtime.

### Must NOT Have (Guardrails)
- Não publicar portas de Asterisk/PostgreSQL.
- Não introduzir novos componentes fora do escopo 24h.
- Não alterar baseline arquitetural sem ADR.

---

## Verification Strategy (MANDATORY)

- **Infrastructure exists**: YES
- **Automated tests**: TDD
- **Framework/Tools**: bash + `docker compose` + `sipsak` + `curl` + Python UDP probe

### QA Policy
Toda task inclui cenário feliz e cenário de falha, com evidência em `.sisyphus/evidence/`.

---

## Execution Strategy

### Parallel Execution Waves

**Wave 0 (Baseline Setup)**
- T1: Baseline de ambiente VPS e segredos

**Wave 1 (RED tests + rollback prep)**
- T2: Testes RED de saúde de containers
- T3: Testes RED de SIP signaling
- T4: Testes RED de endpoint web OCP
- T5: Checklist de rollback operacional

**Wave 2 (GREEN implementation)**
- T6: Ajustes de compose/runtime para saúde estável
- T7: Correções de DB/schema requeridas por módulos OpenSIPS
- T8: Ajustes de rede/ports RTPengine dentro da janela 24h
- T9: Subida coordenada do stack e validação health
- T10: Validação de segurança de exposição de portas

**Wave 3 (REFACTOR + hardening mínimo)**
- T11: Refino de probes/healthchecks
- T12: Refino de logs/diagnóstico de falhas
- T13: Limpeza de risco operacional (timeouts/retries/dependencies)
- T14: Evidências finais consolidadas

**Wave FINAL (Parallel reviews)**
- F1: Plan compliance audit
- F2: Code/config quality review
- F3: Real QA execution
- F4: Scope fidelity check

### Dependency Matrix (full)
- T1: - → T2,T3,T4,T5,T6,T9
- T2: T1 → T9
- T3: T1 → T9
- T4: T1 → T9
- T5: T1 → T14
- T6: T1 → T11,T13
- T7: T1 → T9
- T8: T1 → T9
- T9: T2,T3,T4,T6,T7,T8 → T11,T12,T14
- T10: T9 → T14
- T11: T6,T9 → F2
- T12: T9 → F3
- T13: T6,T9 → F2
- T14: T5,T9,T10 → F1-F4

---

## TODOs

- [x] 1. Baseline VPS + inventário de segredos
  - **What to do**: validar Docker/Compose, espaço/CPU/RAM, existência de todos os arquivos em `secrets/`.
  - **Must NOT do**: alterar conteúdo de segredo sem processo explícito.
  - **Category**: `quick`
  - **Parallel**: Wave 1
  - **Acceptance**: comando de inventário retorna 100% presentes.
  - **QA Scenarios**:
    - Happy: `docker info && docker compose version` sem erro. Evidência: `task-1-baseline.txt`
    - Fail: remover path inválido em teste e confirmar erro explícito. Evidência: `task-1-missing-secret-error.txt`

- [x] 2. TDD RED — health tests de containers
  - **What to do**: escrever/verificar testes que inicialmente falhem para health/Up status alvo.
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 1
  - **Acceptance**: RED comprovado antes de ajustes.
  - **QA Scenarios**:
    - Happy: teste RED detecta pelo menos 1 falha inicial. Evidência: `task-2-red-health.txt`
    - Fail: status inconsistente produz saída não-zero. Evidência: `task-2-red-health-error.txt`

- [x] 3. TDD RED — SIP signaling crítico
  - **What to do**: definir teste RED para OPTIONS 200 e INVITE 407.
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 1
  - **Acceptance**: RED confirmado em ambiente não ajustado.
  - **QA Scenarios**:
    - Happy: `sipsak`/probe detecta ausência de resposta esperada no estado RED. Evidência: `task-3-red-sip.txt`
    - Fail: timeout SIP vira erro assertivo. Evidência: `task-3-red-sip-timeout.txt`

- [x] 4. TDD RED — endpoint OCP
  - **What to do**: teste RED para `https://tsiapp.io/TSiSIP` (ou endpoint interno equivalente na fase RED).
  - **Category**: `quick`
  - **Parallel**: Wave 1
  - **Acceptance**: RED capturado com assert claro.
  - **QA Scenarios**:
    - Happy: `curl -fsSL` falha no RED. Evidência: `task-4-red-ocp.txt`
    - Fail: TLS/HTTP erro com código/causa registrado. Evidência: `task-4-red-ocp-error.txt`

- [x] 5. Runbook de rollback 24h
  - **What to do**: definir passos de rollback por serviço e gatilhos de abort.
  - **Category**: `writing`
  - **Parallel**: Wave 1
  - **Acceptance**: rollback dry-run executável sem ambiguidade.
  - **QA Scenarios**:
    - Happy: simulação de rollback concluída. Evidência: `task-5-rollback-dryrun.txt`
    - Fail: gatilho de abort sem condição definida => reprovação. Evidência: `task-5-rollback-error.txt`

- [x] 6. GREEN — estabilização de runtime/compose
  - **What to do**: aplicar ajustes necessários para serviços subirem estáveis.
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 2
  - **Acceptance**: serviços não entram em restart loop.
  - **QA Scenarios**:
    - Happy: `docker compose ... up -d` + `docker ps` estável por janela mínima. Evidência: `task-6-green-runtime.txt`
    - Fail: detectar restart loop em <=2 min. Evidência: `task-6-green-runtime-error.txt`

- [x] 7. GREEN — schema DB exigido pelos módulos
  - **What to do**: garantir tabelas/versionamento esperados pelo OpenSIPS ativo.
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 2
  - **Acceptance**: ausência de erros de init de módulo por schema.
  - **QA Scenarios**:
    - Happy: query em `version` contém entradas esperadas. Evidência: `task-7-db-schema.txt`
    - Fail: falta de tabela/versão gera erro capturado. Evidência: `task-7-db-schema-error.txt`

- [x] 8. GREEN — RTPengine rede/portas na janela 24h
  - **What to do**: consolidar faixa de portas viável sem travas operacionais.
  - **Category**: `quick`
  - **Parallel**: Wave 2
  - **Acceptance**: container saudável e sem travamento de lifecycle.
  - **QA Scenarios**:
    - Happy: container rtpengine atinge `healthy`. Evidência: `task-8-rtpengine-healthy.txt`
    - Fail: stuck em `Created/Starting` detectado por timeout. Evidência: `task-8-rtpengine-stuck.txt`

- [x] 9. GREEN — subida coordenada + smoke integrado
  - **What to do**: subir stack completo e validar SIP + HTTP.
  - **Category**: `deep`
  - **Parallel**: Wave 2
  - **Acceptance**: OPTIONS 200 + OCP acessível.
  - **QA Scenarios**:
    - Happy: `sipsak` retorna 200 e `curl` retorna conteúdo esperado. Evidência: `task-9-smoke-pass.txt`
    - Fail: qualquer assert falho bloqueia go-live. Evidência: `task-9-smoke-fail.txt`

- [x] 10. Segurança de exposição de portas
  - **What to do**: comprovar que apenas portas permitidas estão públicas.
  - **Category**: `quick`
  - **Parallel**: Wave 2
  - **Acceptance**: nenhuma porta pública para Asterisk/PostgreSQL.
  - **QA Scenarios**:
    - Happy: scan/listagem confirma política. Evidência: `task-10-port-policy.txt`
    - Fail: porta indevida detectada => bloqueio. Evidência: `task-10-port-policy-error.txt`

- [x] 11. REFACTOR — healthchecks mínimos confiáveis
  - **Category**: `quick`
  - **Parallel**: Wave 3
  - **What to do**: padronizar `healthcheck` (interval/timeout/retries/start_period) e alinhar critérios de sucesso por serviço.
  - **Acceptance**: todos os serviços têm healthcheck explícito com parâmetros definidos e comportamento consistente por 10 minutos sem falso positivo.
  - **QA Scenarios**:
    - Happy: `docker inspect` mostra healthcheck configurado em todos os serviços alvo. Evidência: `task-11-healthcheck-config.txt`
    - Fail: remover temporariamente um parâmetro obrigatório em ambiente de teste e detectar reprovação. Evidência: `task-11-healthcheck-config-error.txt`

- [x] 12. REFACTOR — observabilidade mínima de falhas
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 3
  - **What to do**: padronizar coleta de logs (stdout/stderr), mensagens de erro-chave e comandos de triagem rápida por serviço.
  - **Acceptance**: para cada serviço crítico, existe comando de triagem que retorna erro-raiz em até 2 comandos.
  - **QA Scenarios**:
    - Happy: executar roteiro de triagem e identificar causa simulada em <=2 comandos. Evidência: `task-12-observability-triage.txt`
    - Fail: ausência de informação diagnóstica bloqueia aprovação. Evidência: `task-12-observability-triage-error.txt`

- [x] 13. REFACTOR — robustez operacional
  - **Category**: `unspecified-high`
  - **Parallel**: Wave 3
  - **What to do**: revisar restart policy, dependências e timeouts para evitar cascata de restart/falha.
  - **Acceptance**: simulação de falha de 1 serviço não derruba todo o stack; recuperação automática ocorre sem loop infinito.
  - **QA Scenarios**:
    - Happy: parar 1 serviço não-crítico e validar recuperação controlada. Evidência: `task-13-resilience-pass.txt`
    - Fail: detectar cascata de restart ou indisponibilidade total. Evidência: `task-13-resilience-fail.txt`

- [x] 14. Consolidação de evidências + readiness report
  - **Category**: `writing`
  - **Parallel**: Wave 3
  - **Acceptance**: dossiê único de evidências para decisão final.
  - **QA Scenarios**:
    - Happy: checklist de evidências contém todos os artefatos esperados por task. Evidência: `task-14-evidence-bundle-pass.txt`
    - Fail: artefato obrigatório ausente gera reprovação explícita. Evidência: `task-14-evidence-bundle-fail.txt`

---

## Final Verification Wave

- [x] F1. Plan compliance audit — `oracle`
- [x] F2. Code/config quality review — `unspecified-high`
- [x] F3. Automated E2E QA execution — `unspecified-high`
- [x] F4. Scope fidelity check — `deep`

---

## Commit Strategy
- Commit 1: runtime/schema stabilization
- Commit 2: tests + evidence harness
- Commit 3: hardening/refactor and final report

## Success Criteria

### Verification Commands
```bash
docker compose -f docker-compose.vps.yml ps
docker compose -f docker-compose.vps.yml logs --tail=200 opensips
docker run --rm --network tsisip_sip_edge alpine sh -c "apk add --no-cache sipsak >/dev/null 2>&1 && sipsak -s sip:opensips:5060 -vv"
curl -fsSL https://tsiapp.io/TSiSIP
```

### Final Checklist
- [x] Must Have completo
- [x] Must NOT Have preservado
- [x] TDD evidenciado (RED→GREEN→REFACTOR)
- [x] Aprovação explícita do usuário após F1-F4
