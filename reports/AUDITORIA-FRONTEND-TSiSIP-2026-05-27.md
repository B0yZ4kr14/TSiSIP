# Plano de Auditoria Cirurgica — Frontend TSiSIP

**Data**: 2026-05-27
**Branch**: main, tag v1.0.0
**Container**: tsisip-ocp-1
**Auditor**: Arquiteto de Software Senior

---

## 1. Inventario do Frontend (As-Is)

### 1.1 Arquivos PHP Mapeados

| Categoria | Quantidade | Observacao |
|-----------|-----------|------------|
| Paginas raiz (web/*.php) | 83 | Entry points e modulos |
| API REST (web/api/**/*.php) | 8 | Handlers v1 + debug + index |
| CLI (web/cli/*.php) | 3 | Purge scripts |
| Common (web/common/*.php) | 27 | Shared components |
| Wiki (web/wiki/**/*.php) | 3 | Wiki engine + header/footer |
| **Total** | **132** | Inclui subdiretorios |

### 1.2 Estrutura de Navegacao

| Secao | Paginas Declaradas | Roles Visiveis | Status |
|-------|-------------------|----------------|--------|
| SIP Users | subscribers, aliases, groups | Todos | OK |
| System | dashboard, addresses, call-center, cdr-viewer, clusterer, config-table, dialog, dialplan, dispatcher, domains, dynamic-routing, keepalived, load-balancer, mi-commands, monit, rtpengine, rtpproxy, siptrace, smpp-gateway, sockets-management, tviewer, statistics, status-report, tls-management, uac-registrant | Todos (mi-commands restrito) | WARNING |
| Runtime | memory-status, processes, tcp-connections, usrloc, blacklists, timers, version | Todos (tcp-connections/timers restrito) | OK |
| Security | pike-monitor, ratelimit | Apenas admin/devops | OK |
| NAT & Presence | nat-helper, topology-hiding, presence | Todos (presence restrito) | OK |
| Advanced | hash-tables, avp-inspector | Todos | OK |
| Trunking | trunk-providers, trunk-dids, trunk-status | admin/devops/dentist/assist | OK |
| Administration | tenants, header-routing, users, api-keys, audit-log, system-events | Apenas admin/devops | OK |
| Documentation (Wiki) | Wiki Home + 6 role-scoped pages | Todos | BLOCKER |
| Account | change-password, logout | Todos | OK |

### 1.3 Divergencias Repo × Container

| Arquivo | No Repo | No Container | Acao |
|---------|---------|--------------|------|
| web/*.php (83 arquivos) | Sim | Sim | Nenhuma divergencia |
| web/common/*.php (27 arquivos) | Sim | Sim | Nenhuma divergencia |
| web/api/**/*.php | Sim | Sim | Nenhuma divergencia |
| web/cli/*.php | Sim | Sim | Nenhuma divergencia |
| web/wiki/**/*.php | Sim | Sim | Nenhuma divergencia |
| web/ocp/*.php (3 arquivos) | Sim | Sim | WARNING: Duplicatas legadas |

**Veredito**: O deployment esta sincronizado com o repositorio.

---

## 2. Gap Analysis — TSiSIP vs OCP v9.3.6

### 2.1 Modulos Presentes e Validados

**Finding critico**: O documento docs/OCP-CROSS-ANALYSIS.md (2026-05-19) esta severamente desatualizado. Ele lista a maioria dos modulos como "Missing", mas na verdade todos existem no filesystem:

| Modulo | Arquivo | Linhas | DB/MI Calls | Status Real |
|--------|---------|--------|-------------|-------------|
| Subscribers | subscribers.php | ~200 | Sim | Implementado |
| Aliases | aliases.php | 192 | 4 | Implementado |
| Groups | groups.php | 198 | 5 | Implementado |
| Addresses | address.php | 159 | 4 | Implementado |
| Call Center | call-center.php | 442 | 10 | Implementado |
| Clusterer | clusterer.php | 347 | 6 | Implementado |
| Config Table | config-table.php | 224 | 6 | Implementado |
| Dialog | dialog.php | 225 | 4 | Implementado |
| Dialplan | dialplan.php | 311 | 4 | Implementado |
| Dispatcher | dispatcher.php | ~300 | 4 | Implementado |
| Domains | domains.php | 224 | 4 | Implementado |
| Dynamic Routing | dynamic-routing.php | 427 | 10 | Implementado |
| Keepalived | keepalived.php | 229 | 5 | Implementado |
| Load Balancer | load-balancer.php | 317 | 7 | Implementado |
| MI Commands | mi-commands.php | 422 | MI HTTP | Implementado |
| Monit | monit.php | 221 | 5 | Implementado |
| RTPEngine | rtpengine.php | ~200 | MI HTTP | Implementado |
| RTPProxy | rtpproxy.php | 172 | 4 | Implementado |
| SIPtrace | siptrace.php | 240 | 2 | Implementado |
| Statistics | statistics.php | 398 | MI HTTP | Implementado |
| Status Report | status-report.php | 156 | 2 | Implementado |
| Sockets Mgmt | sockets-management.php | 258 | 5 | Implementado |
| TLS Management | tls-management.php | 156 | MI HTTP | Implementado |
| UAC Registrant | uac-registrant.php | 291 | 5 | Implementado |
| SMPP Gateway | smpp-gateway.php | 231 | 5 | Implementado |
| CDR Viewer | cdr-viewer.php | ~200 | 3 | Implementado |
| Trunk Providers | trunk-providers.php | 420 | 4 | Implementado |
| Trunk DIDs | trunk-dids.php | 344 | 4 | Implementado |
| Trunk Status | trunk-status.php | 97 | 0 | Stub leve |

**Cobertura real do OCP**: ~90% implementado (vs. 16% reportado no documento antigo).

### 2.2 Modulos Ausentes (Justificados)

| Modulo | Racional |
|--------|----------|
| TViewer | Framework generico nao essencial para edge proxy |
| Multi-box Systems | TSiSIP e single-tenant Docker Compose |

### 2.3 Links Quebrados Detectados

| Link no Menu | Arquivo Esperado | Arquivo Real | Impacto |
|--------------|------------------|--------------|---------|
| addresses | addresses.php | address.php (singular) | **404 ao clicar** |

### 2.4 Paginas Orfas (Existentes mas Nao Linkadas no Menu)

| Pagina | Proposito | Recomendacao |
|--------|-----------|--------------|
| about.php | Sobre o TSiSIP | Adicionar no footer |
| help.php | Ajuda contextual | Consolidar com wiki |
| notes.php | Notas pessoais | Considerar remocao |
| alert-history.php | Historico de alertas | Adicionar em Administration |
| api-docs.php | Documentacao da API | Adicionar em Administration |
| audit-export.php | Exportacao de audit | Acessivel via audit-log.php |
| cache-manager.php | Gerenciamento de cache | Adicionar em System |
| feedback.php / feedback-list.php | Feedback de usuarios | Adicionar em Account ou remover |
| gateway-health.php | Saude de gateways | Adicionar em Trunking |
| health.php / healthcheck-audit.php / system-health.php | Health checks | Consolidar ou adicionar em Administration |
| profile.php | Perfil do usuario | Acessivel via header |
| reports.php | Relatorios | Adicionar em Administration |
| rtpengine-status.php | Status RTPEngine | Consolidar com rtpengine.php |
| scheduled-tasks.php | Tarefas agendadas | Adicionar em Administration |
| search.php | Busca global | Acessivel via header |
| subscriber-stats.php | Estatisticas de subscribers | Adicionar em SIP Users |
| system-config.php | Configuracao do sistema | Adicionar em Administration |
| system-logs.php | Logs do sistema | Adicionar em Administration |
| system-events.php | Eventos do sistema | Ja no menu |
| topology.php | Topologia | Consolidar com topology-hiding.php |
| user-delete.php / user-edit.php | Acoes de usuario | Acessiveis via users.php |
| userblacklist.php | Blacklist por usuario | Considerar integracao com Blacklists |

### 2.5 Duplicatas Detectadas

| Localizacao | Arquivo | Observacao |
|-------------|---------|------------|
| web/ocp/trunk-providers.php (23381 bytes) | vs. web/trunk-providers.php (420 linhas) | Versao legada maior no subdir ocp/ |
| web/ocp/trunk-dids.php (19269 bytes) | vs. web/trunk-dids.php (344 linhas) | Versao legada maior no subdir ocp/ |
| web/ocp/trunk-status.php (4228 bytes) | vs. web/trunk-status.php (97 linhas) | Versao legada maior no subdir ocp/ |

---

## 3. Proposta de Correcao do Wiki

### 3.1 Estado Atual (Problema)

O wiki ocupa uma **secao inteira do sidebar** ("Documentation") com ~10 itens:
- Wiki Home
- System Overview
- Administrators (admin only)
- DevOps SIP (admin/devops)
- Runbooks & Troubleshooting (admin/devops)
- Security & Compliance (admin/devops)
- Developers (admin only)
- Operators & Users (all roles)
- Dentists (dentist)
- Assistants (assistant)

**Impactos negativos**:
1. Polui a navegacao primaria com conteudo estatico
2. Consome ~10 itens no sidebar
3. Mistura documentacao com ferramentas administrativas
4. Usuario pode confundir Wiki Home com dashboard

### 3.2 Estado Desejado

- Sidebar limpo: apenas ferramentas operacionais
- Acesso ao wiki preservado via botao discreto no header
- Role-scoping mantido no wiki.php

### 3.3 Arquivos a Modificar

| Arquivo | Linhas Aprox. | Alteracao |
|---------|---------------|-----------|
| web/common/role-nav.php | 287-307 | Remover bloco "Wiki / Documentation" |
| web/common/header.php | ~95 | Adicionar botao de acesso ao wiki |

### 3.4 Especificacao do Botao de Acesso

| Propriedade | Valor |
|-------------|-------|
| Localizacao | web/common/header.php, apos bookmark-btn |
| Elemento | a (link semantico) |
| Classe CSS | tsisip-btn tsisip-btn-icon |
| Icone | livro/documento |
| Label | Docs ou Wiki (curto) |
| href | wiki/ |
| title | Open Documentation Wiki |
| Acessibilidade | aria-label, role="link" |
| Visibilidade | Apenas usuarios autenticados |

**HTML sugerido**:
```php
<a href="wiki/"
   class="tsisip-btn tsisip-btn-icon"
   title="<?php echo _('Open Documentation Wiki'); ?>"
   aria-label="<?php echo _('Documentation Wiki'); ?>"
   role="link"
   style="background:none;border:none;cursor:pointer;font-size:1.2rem;text-decoration:none;">
    &#128214;
</a>
```

### 3.5 Preservacao de Funcionalidade

- web/wiki.php permanece intacto
- docs/wiki/ permanece intacto
- web/wiki/ (engine) permanece intacto
- Role-based filtering continua funcionando

---

## 4. Plano de Implementacao (Roadmap)

### Fase 1: Correcoes Criticas (Prioridade Alta)

1. **Fix link quebrado addresses**
   - Arquivo: web/common/role-nav.php
   - Alteracao: addresses -> address no array de paginas

2. **Remover wiki do sidebar**
   - Arquivo: web/common/role-nav.php
   - Remover bloco Wiki / Documentation

3. **Adicionar botao de wiki no header**
   - Arquivo: web/common/header.php
   - Inserir apos bookmark-btn

4. **Atualizar docs/OCP-CROSS-ANALYSIS.md**
   - Corrigir status de modulos implementados
   - Atualizar cobertura de 16% para ~90%

### Fase 2: Consolidacao de Orfas (Prioridade Media)

1. Decidir destino das paginas orfas:
   - Adicionar cache-manager.php ao menu System
   - Adicionar scheduled-tasks.php ao menu Administration
   - Adicionar reports.php ao menu Administration
   - Adicionar system-config.php ao menu Administration
   - Adicionar system-logs.php ao menu Administration
   - Adicionar api-docs.php ao menu Administration
   - Consolidar topology.php com topology-hiding.php
   - Mover about.php e help.php para footer
   - Avaliar remocao de notes.php e feedback.php

2. Avaliar duplicatas ocp/
   - Comparar funcionalidade de web/ocp/trunk-*.php vs. web/trunk-*.php
   - Migrar funcionalidade extra ou remover versoes legadas

### Fase 3: Testes de Regressao (Prioridade Media)

1. Testar navegacao por role (admin, devops, dentist, readonly)
2. Testar wiki via botao do header
3. Testar link fixo de addresses
4. Executar testes OCP: 17/17 PASS

---

## 5. Checklist de Validacao

- [ ] Wiki removido do sidebar
- [ ] Botao de acesso ao wiki funcional
- [ ] Roles preservadas corretamente
- [ ] Link addresses no menu aponta para arquivo existente
- [ ] Nenhum link quebrado no menu
- [ ] i18n intacto (todas as labels usam gettext)
- [ ] Acessibilidade mantida
- [ ] docs/OCP-CROSS-ANALYSIS.md atualizado
- [ ] Testes OCP: 17/17 PASS
- [ ] Container OCP recriado e sincronizado

---

## 6. Findings Adicionais

| # | Finding | Severidade |
|---|---------|------------|
| 1 | docs/OCP-CROSS-ANALYSIS.md desatualizado (16% vs ~90% cobertura) | Media |
| 2 | Link addresses quebrado (menu aponta para arquivo inexistente) | Alta |
| 3 | Wiki como secao inteira no sidebar polui navegacao | Media |
| 4 | 24 paginas orfas nao linkadas no menu | Media |
| 5 | Subdiretorio web/ocp/ contem versoes legadas de trunk pages | Baixa |
| 6 | trunk-status.php tem 0 DB/MI calls (stub leve) | Baixa |
| 7 | dashboard.php tem 0 DB calls (widgets estaticos) | Info |
| 8 | users.php tem 0 DB calls (delega para includes) | Info |

---

Auditoria concluida. Sistema pronto para implementacao.
