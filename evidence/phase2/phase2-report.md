# Fase 2: Análise de Impacto — Relatório
## Data: 2026-05-20 06:50–07:00 UTC

## A2.1 — GitNexus Impact Analysis

### Index Status
- Nodes: 2.817 | Edges: 3.050 | Clusters: 14 | Flows: 3
- Reindexed em 1.9s

### Recent Changes (detect-changes)
- 29 files changed, 5 symbols
- Affected processes: 0
- Risk level: LOW
- Changed symbols: AGENTS.md, CLAUDE.md, docs/TSiSIP-CANONICAL-SPEC.md

### Critical Symbols Context

#### authenticateUser
- File: web/common/config.php:63-122
- Calls: getDb, logLoginAttempt
- Process: AuthenticateUser → GetDb (3 steps)
- Risk: LOW (isolated auth flow)

#### getDb
- File: web/common/config.php:39-56
- Impacted: 3 upstream callers
- Risk: LOW
- Blast radius: 1 module, 1 process

## A2.2 — Speckit Cross-Artifact

### Spec 012 Status
- spec.md: exists
- plan.md: exists  
- tasks.md: exists but OUTDATED (many implemented tasks still unchecked)

### Inconsistências Encontradas
1. tasks.md da spec 012 não foi atualizado após implementação
2. docker-compose.vps.yml não tem serviço ocp definido (container manual)
3. Backup volume não montado no host

## A2.3 — OMK Memory

### Status
- Backend: local_graph
- Nodes: 1 (Project only)
- Memory nearly empty — não há contexto histórico carregado

## Riscos Classificados

| Risco | Nível | Descrição | Ação F3 |
|---|---|---|---|
| OCP fora do compose | HIGH | Container manual, não gerenciado | Adicionar ao compose |
| Backup sem host mount | HIGH | Perde dados se container morrer | Configurar volume |
| opensipsctl ausente | MEDIUM | Não dá para MI status facilmente | Documentar workaround |
| tasks.md desatualizado | LOW | Documentação interna desalinhada | Atualizar checkboxes |
| Schema weight varchar | LOW | Código converte int, DB aceita | OK, PDO faz cast |

## Recomendações para Fase 3

1. **Prioridade CRITICAL**: Integrar OCP ao docker-compose.vps.yml
2. **Prioridade HIGH**: Configurar volume de backup no host
3. **Prioridade MEDIUM**: Criar script de healthcheck completo
4. **Prioridade LOW**: Atualizar tasks.md das specs
