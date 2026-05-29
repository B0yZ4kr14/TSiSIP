# Relatório de Gaps do Frontend TSiSIP/OCP

**Data**: 2026-05-29
**Base**: Brownfield scan + Análise de specs 025-037

---

## Gaps Identificados

### 1. CSS Base Não-Responsivo (MÉDIO)
**Arquivo**: `web/css/main.css`
**Problema**: ZERO media queries. Todo o comportamento responsivo está em `tsisip-theme.css` (17 queries).
**Risco**: Se o tema TSiSIP falhar ao carregar, o site fica completamente quebrado em mobile.
**Ação**: Adicionar media queries mínimas em `main.css` para garantir funcionamento sem o tema.

### 2. Cobertura de Testes Baixa (ALTO)
**Arquivos de teste existentes**: 5
- `tests/accessibility-audit.test.js`
- `tests/d3-jquery-coexistence.test.js`
- `tests/unit/test_totp.php`
- `tests/integration/test-requirement-id-format.js`
- `web/api/test.php`

**Páginas PHP**: 84+
**Cobertura**: ~6%
**Ação**: Criar testes de integração para páginas críticas (login, dashboard, subscribers, dispatcher).

### 3. Performance Benchmarking Ausente (BAIXO)
**Spec**: 033-performance-benchmarking
**Status**: Sem scripts dedicados no repositório.
**Ação**: Criar scripts de benchmark para MI calls e queries PostgreSQL.

### 4. Dashboard Não-Customizável (BAIXO)
**Spec**: 028-custom-dashboard
**Status**: Widgets fixos. Usuário não pode reordenar/remover/adicionar widgets.
**Ação**: Implementar drag-and-drop de widgets com persistência em DB.

---

## Recomendação de Prioridade

| # | Tarefa | Impacto | Esforço | Prioridade |
|---|--------|---------|---------|------------|
| 1 | Testes de integração para páginas críticas | Alto | Médio | P0 |
| 2 | Media queries em main.css | Médio | Baixo | P1 |
| 3 | Scripts de performance benchmark | Baixo | Médio | P2 |
| 4 | Dashboard customizável | Baixo | Alto | P2 |
