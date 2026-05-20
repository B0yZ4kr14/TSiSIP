# Plano de Remediação — Brownfield Scan 2026-05-20
## TSiSIP Auto-Approved Remediation Loop
### Modo: Socrático-Popperiano | Loop: 5x sem interrupção

---

## Premissa do Loop
**Hipótese a falsificar**: "Os 13 findings do brownfield scan podem ser corrigidos em 5 ciclos iterativos sem regressão."

**Se qualquer ciclo introduzir regressão**: o loop deve parar, evidenciar, e documentar.

---

## Arquitetura do Loop (5x)

```
Ciclo 1: CRITICAL (B1) + HIGH (B2, B3, B4)
Ciclo 2: MEDIUM (B5, B6, B7)
Ciclo 3: MEDIUM (B8, B9) + LOW (B10, B11)
Ciclo 4: LOW (B12, B13) + Documentação
Ciclo 5: Validação final + Tag
```

---

## Ciclo 1: CRITICAL + HIGH (B1-B4)

### B1 — Remover senha default plaintext
**Arquivo**: `db/init/03-seed-data.sql`
**Ação**: Remover comentário com senha; manter hash bcrypt
**Evidência**: `git diff` + `grep -n` confirmando ausência de plaintext

### B2 — Fixar RTPengine --listen-ng
**Arquivo**: `docker-compose.vps.yml`, `docker-compose.yml`, `docker-compose.prod.yml`
**Ação**: Substituir `${RTPENGINE_INTERNAL_IP:-0.0.0.0}` por `${RTPENGINE_INTERNAL_IP}`
**Evidência**: `opensips -c` após restart + scan de portas

### B3 — Completar .env.example
**Arquivo**: `.env.example`
**Ação**: Adicionar HOST_PUBLIC_IP, OPENSIPS_LISTEN_IP, RTPENGINE_INTERNAL_IP, RTPENGINE_PRIVATE_IP
**Evidência**: Diff + validação de variáveis no compose

### B4 — Auth contract migration T5.3
**Arquivo**: `opensips/opensips.cfg.tpl`
**Ação**: Adicionar `proxy_authorize` para non-REGISTER; manter `www_authorize` para REGISTER
**Evidência**: `opensips -c` valida + teste SIP com sipsak/INVITE esperando 407

---

## Ciclo 2: MEDIUM (B5-B7)

### B5 — Parameterizar IP em orchestrate-deploy.sh
**Arquivo**: `deploy/scripts/orchestrate-deploy.sh`
**Ação**: Substituir sed fixo 127.0.0.1 por leitura de env var
**Evidência**: Script executa sem erros em dry-run

### B6 — Parameterizar IP em teste E2E
**Arquivo**: `tests/integration/test_end_to_end_call.py`
**Ação**: Usar env var `TARGET_HOST` com fallback 127.0.0.1
**Evidência**: Teste passa com host customizado

### B7 — Documentar OCP manual container
**Arquivo**: `docs/TSiSIP-OPERATOR-RUNBOOK.md`
**Ação**: Adicionar seção de troubleshooting para compose network state
**Evidência**: Relatório atualizado

---

## Ciclo 3: MEDIUM + LOW (B8-B11)

### B8 — Remover opt-out de encriptação de backup
**Arquivo**: `docker/backup/backup.sh`
**Ação**: Falhar hard se ENCRYPTION_KEY_FILE não existir; remover path unencrypted
**Evidência**: Backup teste falha sem key, passa com key

### B9 — Decidir observability stack
**Arquivo**: `docker-compose.prod.yml`
**Ação**: Remover serviços comentados ou habilitar com healthchecks
**Evidência**: Compose validado com `docker compose config`

### B10 — Documentar OPENSIPS_HOST explícito
**Arquivo**: `docker-compose*.yml`
**Ação**: Adicionar env var explícita em todos os serviços opensips
**Evidência**: Healthcheck continua passando

### B11 — Usar IP de documentação em cert-gen.sh
**Arquivo**: `docker/ca-tool/cert-gen.sh`
**Ação**: Substituir 192.168.1.1 por 203.0.113.1 (TEST-NET-3)
**Evidência**: Script gera cert com IP de doc

---

## Ciclo 4: LOW (B12-B13) + Documentação

### B12 — Renomear "sanity" em comentário
**Arquivo**: `docker/backup/replicate.sh`
**Ação**: "credential sanity check" → "credential validation check"
**Evidência**: grep não encontra mais "sanity"

### B13 — Documentar pinning de imagens
**Arquivo**: `AGENTS.md`, `.env.example`
**Ação**: Adicionar nota sobre TSISIP_IMAGE_TAG obrigatório em produção
**Evidência**: Documentação atualizada

### Documentação final
- Atualizar `AGENTS.md` com todas as correções
- Atualizar `docs/TSiSIP-CANONICAL-SPEC.md` se necessário
- Criar `CHANGELOG.md` entry

---

## Ciclo 5: Validação Final

### Checklist
- [ ] Todos os 13 findings resolvidos ou documentados
- [ ] `opensips -c` valida sem erros
- [ ] SIP OPTIONS 200 OK
- [ ] OCP E2E 14/14 passa
- [ ] Backup encriptado funciona
- [ ] Sem regressão em containers
- [ ] Commit + tag

### Socratic Final Question
"O que ainda não sabemos que poderia quebrar em produção?"

---

## Loop Auto-Approved Rules

1. **Nenhuma chamada para aprovação do usuário**
2. **Se regressão detectada**: documentar, não corrigir no loop (evita cascata)
3. **Cada ciclo deve produzir evidência** em `evidence/remediation/ciclo-N/`
4. **Git commit por ciclo** com mensagem convencional
5. **Tag apenas no ciclo 5** se 100% dos checks passarem
