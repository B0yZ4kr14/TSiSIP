# Memoria Persistente Spec Kit - TSiSIP

Gerado em: 20260518-141826
Atualizado em: 2026-05-19
Origem inicial: /home/b0yz4kr14/Projects/Tunning/SpecKit/scripts/bootstrap-spec-kit-projects.sh
Atualizacao atual: auditoria local TSiSIP + GitNexus analyze + validacao live VPS TSiAPP em /home/b0yz4kr14/Projects/TSiSIP


## Finalidade

Este projeto usa Spec Kit como camada de governanca para especificacoes, planos, tarefas e analise antes de implementacao.

## Padrao Profissional

- CLI oficial: `specify-cli v0.8.11` instalado via `git+https://github.com/github/spec-kit.git`.
- Integracao preferencial para Codex: `codex`, scripts `sh`.
- Nao instalar extensoes comunitarias sem revisao de codigo, fonte, permissao e plano de rollback.
- Nao usar `specify init --here --force` sem backup previo de `.specify/`, arquivos de agente e memoria.
- Para upgrade de projeto, preservar `.specify/memory/constitution.md`; a documentacao oficial alerta que `--force` pode sobrescrever a constituicao.

## Workflow Base

1. `$speckit-specify` para declarar o que e por que.
2. `$speckit-clarify` para reduzir ambiguidades.
3. `$speckit-checklist` para testar qualidade dos requisitos.
4. `$speckit-plan` para definir abordagem tecnica.
5. `$speckit-tasks` para gerar tarefas pequenas e ordenadas.
6. `$speckit-analyze` antes de executar.
7. `$speckit-implement` somente quando os gates estiverem verdes.

## Regras Contra Entropia

- Uma feature por objetivo.
- Uma mudanca material por lote.
- Specs antigas devem ser arquivadas ou atualizadas; nao duplicar nomes com semantica conflitante.
- O arquivo `persistent-context.md` deve conter politica duravel, nao diario de execucao.
- Evidencias, logs e relatorios ficam fora da memoria quando forem volumosos.

## Estado Operacional Atual

- Projeto alvo: `/home/b0yz4kr14/Projects/TSiSIP`.
- GitNexus: repositorio indexado em 2026-05-19; `npx gitnexus status` reportou `Status: up-to-date` no commit `6feeb58`.
- Escopo funcional: Features 001-008 estao implementadas em specs e tarefas, com artefatos Docker, Compose, deploy, testes e runbooks presentes.
- Perfil de deploy atual: `vps-lite+PBX` com 7 servicos essenciais (`postgres`, `rtpengine`, `opensips`, `ocp`, `backup`, `asterisk-pbx-1`, `asterisk-pbx-2`). O stack completo com Prometheus/Grafana continua separado para evitar conflitos com OrthoPlus e port ranges grandes.
- Estado live VPS TSiAPP em 2026-05-19: stack `vps-lite+PBX` executando em `/opt/tsisip`; todos os 7 servicos estao `healthy`.
- OpenSIPS live foi estabilizado com correcoes de compatibilidade OpenSIPS 3.6 no template: inteiros sem aspas em funcoes core, `sql_query(query, avps)`, `ds_select_dst(set, 4, "f")`, `www_challenge("", "auth")` e schema `userblacklist` versao 2.
- Asterisk live foi estabilizado montando configs em `/etc/asterisk` e `/usr/local/etc/asterisk`; a build source-based le configs em `/usr/local/etc/asterisk`.
- Dispatcher runtime tem 2 destinos reais ativos em set 1: `sip:asterisk-pbx-1:5060` e `sip:asterisk-pbx-2:5060`.
- Validacao SIP interna: OPTIONS UDP/TCP retorna `200 OK`; INVITE sem auth retorna `401 Unauthorized`; `scripts/sip-auth-probe.py` com `devuser/devpass` recebe `100 Giving it a try` e `200 OK`, com Asterisk executando `1000@from-opensips`.
- OCP publico responde via Cloudflare/Nginx em `https://tsiapp.io/TSiSIP/` com redirect esperado para `/TSiSIP/login.php`.
- Guardrails constitucionais seguem ativos: Docker-first, PostgreSQL-only, OpenSIPS 3.6 LTS, isolamento de Asterisk/PostgreSQL, HA1 sem senhas plaintext, RTPengine como relay publico unico.

## Pendencias Recuperadas

- `deploy/VPS-DEPLOY-READINESS.md`: checks basicos de infraestrutura, containers, dispatcher e SIP autenticado foram executados na VPS TSiAPP.
- SIP externo: 5060/5061 escutam localmente no VPS e UFW permite 5060/tcp, 5060/udp, 5061/tcp e RTP; scans externos em 2026-05-19 reportaram 5060/tcp e 5061/tcp como `filtered` no IP publico `179.190.15.116` e no Tailscale `100.111.74.69`; `tcpdump` no VPS capturou 0 SYN durante o scan, indicando bloqueio upstream fora do host.
- `specs/002-tsisip-ocp-rebrand/plan.md`: process gate de aprovacao por 3 representantes ainda esta aberto; nao e bloqueio tecnico de build, mas continua pendente de governanca.
- `specs/001-opensips-docker-edge-proxy/checklists/infra-quality.md`: checklist historico fechado em 2026-05-19 como PASS com deferrals escopados; itens fora da feature sao performance em escala, over-capacity e validacao Asterisk fora do vps-lite.
- `reports/remediation-summary.md`: pendencias aceitas/deferidas restantes sao B3 doc-only `apt.opensips.org`, B13 comentarios em config, B14 sleeps sem polling, M8 fallback de kernel table do RTPengine.
- `deploy/VPS-DEPLOY-READINESS.md`: fase 2 pos-estabilizacao ainda inclui Prometheus/Grafana, Asterisk, exporter/anomaly-detector, Alertmanager real, certificados TLS reais e rclone/MinIO reais.
- Feature 001 T4.4/T4.5 estava inconsistente entre spec/plan/tasks; a spec ja marcava resolvido. Plan/tasks foram reconciliados em 2026-05-19.
- Feature 001 spec foi reforcada com FR-002A, health timing, Max-Forwards 483, runtime DB/RTPengine fallback delegado para Feature 004, e risco de performance single-instance.

## Capacidades Locais Recomendadas

- GitNexus: usar `npx gitnexus status` apos mudancas e `npx gitnexus analyze` quando o indice ficar stale; o MCP expôs rotas do `docker/anomaly-detector/detector.py` (`/health`, `/metrics`, `/api/v1/event`, `/api/v1/status`).
- Spec Kit: usar as skills/prompts `speckit-*` locais para novas features; nao rodar `specify init --here --force` sem backup.
- Agentes: preferir os agentes Speckit em `.github/agents/` para fluxo spec/plan/tasks/analyze/implement; tratar `.claude-flow/`, `.swarm/` e `.sisyphus/` como estado/config de orquestracao, nao como runtime da aplicacao.
- Validacao local barata: `npx gitnexus status`, `bash scripts/ci-scan.sh`, `cd deploy && bash validate.sh`, `docker compose config`.
- Segredos runtime podem existir localmente em `secrets/` desde que continuem ignorados pelo Git; scans devem falhar por arquivos rastreados, nao pela simples presenca local.
