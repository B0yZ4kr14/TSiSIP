# Relatório de Auditoria Popperiana — TSiSIP OCP Frontend vs OpenSIPS Control Panel v9

**Data:** 2026-05-20  
**Auditor:** Orquestração Multi-Agente (Socrático/Popperiano)  
**Escopo:** Verificar se o TSiSIP OCP reflete as configurações e administração do OpenSIPS, se deveria ter sido alterado somente o tema, e validar consistência das referências "OpenSIPS" ↔ "TSiSIP".

---

## 1. Premissa Central a Ser Falsificada

> **"O TSiSIP OCP é um rebrand (mudança de tema) do OpenSIPS Control Panel v9, mantendo as funcionalidades administrativas do OpenSIPS."**

---

## 2. Falsificação: Gap Funcional Massivo

### 2.1 O que o OCP v9 Original Entrega

Segundo a documentação oficial (opensips.org, controlpanel.opensips.org), o OCP v9 possui **18+ ferramentas administrativas** organizadas em três classes:

**Admin Class:**
- Add Admin — criação de administradores do painel
- List Admin — permissões de acesso por ferramenta

**SIP Users Class:**
- User Management — CRUD de subscribers SIP
- Alias Management — aliases de subscribers
- ACL Management — permissões/ACLs por subscriber

**System Class (18 ferramentas):**
- CDR Viewer — visualização de CDRs
- Call Center — gestão de filas e agentes
- Clusterer — gestão de cluster OpenSIPS
- Dialog — chamadas em andamento
- Dialplan — regras de dialplan
- Dispatcher — destinos de dispatching (com monitoramento real)
- Domains — gestão de domínios SIP
- Dynamic Routing — LCR, carriers, gateways
- Homer — integração SIPCapture
- Load Balancer — provisionamento de LB
- MI Commands — interface de management MI
- Monit — integração Monit
- Permissions — permissões baseadas em IP
- RTPProxy / RTPengine — gestão de media relays
- SIPtrace — captura SIP
- Statistics Monitor (smonitor) — estatísticas OpenSIPS com D3.js
- TViewer — viewer genérico de tabelas SQL
- TLS Management — certificados TLS
- SMPP, UAC Registrant, Sockets Mgmt, Keepalived, Status Report

### 2.2 O que o TSiSIP OCP Atualmente Entrega

O TSiSIP OCP possui **8 páginas PHP**:

| Página | Funcionalidade Real | Status |
|---|---|---|
| `index.php` | Redirect para login/dashboard | ✅ Funcional |
| `login.php` | Autenticação PDO PostgreSQL | ✅ Funcional |
| `logout.php` | Destruição de sessão | ✅ Funcional |
| `change-password.php` | Troca de senha com complexidade | ✅ Funcional |
| `dashboard.php` | Landing page com links de navegação | ⚠️ Apenas links |
| `wiki.php` | Renderizador de markdown da docs/wiki/ | ✅ Funcional |
| `dispatcher.php` | **Stub** — tabela hard-coded com 2 linhas estáticas | ❌ **NÃO administrável** |
| `rtpengine.php` | **Stub** — tabela hard-coded com 1 linha estática | ❌ **NÃO administrável** |

### 2.3 Veredicto Popperiano

**A premissa é FALSIFICADA.**

O TSiSIP OCP **não é um rebrand** do OCP v9. É uma reimplementação drásticamente reduzida que:
- Removeu **100% das ferramentas Admin Class** (não há gestão de administradores do painel)
- Removeu **100% das ferramentas SIP Users Class** (não há gestão de subscribers, aliases, ACLs)
- Removeu **~90% das ferramentas System Class** (sobraram apenas stubs de Dispatcher e RTPengine)
- Não possui integração MI (Management Interface) com o OpenSIPS
- Não possui monitoramento de estatísticas reais do OpenSIPS
- Não possui CDR viewer real
- Não possui gestão de dialplan, domains, dynamic routing, load balancer, clusterer

**Consequência:** Um administrador do TSiSIP **não pode** provisionar subscribers, configurar dialplan, gerenciar domínios, visualizar CDRs, ou executar comandos MI através do OCP. Todas essas operações requerem acesso direto ao banco PostgreSQL ou CLI.

---

## 3. Análise das Referências "OpenSIPS" vs "TSiSIP"

### 3.1 Referências Corretas (Devem Permanecer)

| Local | Texto | Avaliação |
|---|---|---|
| `web/dashboard.php:88` | `"OpenSIPS SIP Proxy"` | ✅ **CORRETO** — O proxy SIP continua sendo OpenSIPS 3.6 LTS. Chamar de "TSiSIP SIP Proxy" seria impreciso tecnicamente. |

### 3.2 Referências Redundantes ou Inadequadas

| Local | Texto | Avaliação |
|---|---|---|
| `web/common/header.php:44` | `"TSiSIP — TSiSIP Control Panel"` | ⚠️ **REDUNDANTE** — "TSiSIP" repetido duas vezes. Deveria ser `"TSiSIP — Control Panel"` ou `"TSiSIP Operator Console"`. |
| `web/login.php:61` | `"TSiSIP — Sign In"` | ✅ Aceitável |
| `web/login.php:74` | `"TSiSIP Control Panel"` | ✅ Aceitável |

### 3.3 Omissões Críticas

- **Não há nenhuma menção** no frontend de que o sistema SIP por trás é OpenSIPS 3.6 LTS
- **Não há crédito/licença** ao projeto original OpenSIPS Control Panel (GPL v2)
- **Não há disclaimer** de que funcionalidades administrativas foram removidas

---

## 4. Testes de Falsificação Executados

### T1: Verificar se dispatcher.php lê do banco
**Teste:** Inspecionar código fonte de `dispatcher.php`
**Resultado:** FALSO — dados são hard-coded HTML, não há `SELECT * FROM dispatcher`
**Evidência:** Linhas 26-44 contêm `<td>sip:pbx1.internal:5060</td>` estático

### T2: Verificar se rtpengine.php lê do banco
**Teste:** Inspecionar código fonte de `rtpengine.php`
**Resultado:** FALSO — dados são hard-coded HTML
**Evidência:** Linha 25 contém `<td>rtpengine-01</td>` estático

### T3: Verificar se existe gestão de subscribers
**Teste:** Listar arquivos PHP em `web/`
**Resultado:** FALSO — não há `subscribers.php`, `users.php`, `aliases.php`
**Evidência:** Apenas 8 arquivos PHP existem

### T4: Verificar se existe integração MI
**Teste:** Grep por "MI" ou "jsonrpc" ou "fifo" no código PHP
**Resultado:** FALSO — nenhuma referência a Management Interface

### T5: Verificar se existe CDR viewer
**Teste:** Grep por "cdr" em `web/*.php`
**Resultado:** FALSO — não há página de visualização de CDRs

---

## 5. Recomendações

### 5.1 Imediata (Documentação)

1. **Adicionar disclaimer claro** no `README.md` e no `dashboard.php`:
   > "O TSiSIP OCP é uma implementação lightweight focada em documentação wiki e monitoramento básico. Não substitui o OpenSIPS Control Panel v9 completo. Para operações administrativas avançadas (gestão de subscribers, dialplan, CDRs, MI), use CLI ou acesso direto ao PostgreSQL."

2. **Corrigir título redundante** em `header.php`:
   > `"TSiSIP — Control Panel"` (remover duplicação)

3. **Manter "OpenSIPS"** no status do dashboard — está correto tecnicamente

### 5.2 Médio Prazo (Funcionalidade)

Se o objetivo é manter o TSiSIP OCP como **painel administrativo real**, reimplementar as ferramentas críticas:
- **Subscriber Management** — CRUD na tabela `subscriber`
- **CDR Viewer** — SELECT na tabela `cdr` com filtros
- **Dispatcher Management** — CRUD real na tabela `dispatcher`
- **MI Commands** — Interface para enviar comandos MI ao OpenSIPS
- **Statistics Monitor** — Consumir estatísticas do módulo `statistics` via MI

Se o objetivo for **apenas wiki + dashboard**, renomear explicitamente para:
> "TSiSIP Wiki & Status Dashboard" (evitar confusão com "Control Panel")

### 5.3 Licenciamento

O OCP v9 original é GPL v2. Se o TSiSIP OCP foi derivado do código original (estrutura de pastas, convenções, ou snippets), deve-se:
- Incluir `LICENSE` GPL v2 no diretório `web/`
- Incluir header de copyright nos arquivos derivados
- Documentar a origem no `README.md`

---

## 6. Conclusão

| Questão | Resposta |
|---|---|
| O TSiSIP OCP reflete as configurações do OpenSIPS? | **NÃO** — Removeu 90%+ das ferramentas administrativas |
| Deveria ter sido alterado somente o tema? | **NÃO** — O que foi feito vai além do tema; é uma reimplementação funcionalmente reduzida |
| As referências "OpenSIPS" → "TSiSIP" estão corretas? | **PARCIALMENTE** — A maioria está correta, mas há redundância no título e falta de disclaimer sobre funcionalidades removidas |

**Veredicto final:** A premissa de que o TSiSIP OCP é um "rebrand do tema" é **falsificada**. É um projeto derivado funcionalmente incompleto que requer documentação explícita de suas limitações ou reimplementação das ferramentas administrativas críticas.

---

*Relatório gerado via método Socrático/Popperiano com validação contra fontes canônicas opensips.org e controlpanel.opensips.org.*
