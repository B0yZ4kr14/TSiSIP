# ADR-001: Perfil VPS-Lite para TSiAPP

## Status
Aceito — implementado em 2026-05-17

## Contexto
A VPS TSiAPP possui 3.8GB RAM e já hospeda 6 containers legados (OrthoPlus, TsiView, TsiMusic, Landpages, Smith). O stack TSiSIP completo com 13 serviços requer ~6-8GB RAM para startup simultâneo. Dois crashes por OOM (Out Of Memory) confirmaram que o deploy completo é inviável na infraestrutura atual sem upgrade.

## Decisão
Adotar um **perfil "vps-lite"** com apenas 5 serviços essenciais, cada um com `mem_limit` explícito:

| Serviço | Mem Limit | Função |
|---------|-----------|--------|
| postgres | 512m | Banco de dados SIP |
| rtpengine | 256m | Media relay |
| opensips | 256m | SIP proxy/edge |
| ocp | 256m | Painel de controle |
| backup | 128m | Backup PostgreSQL |

**Total alocado: ~1.4GB RAM** (reserva para kernel + containers legados).

Serviços **desabilitados** no vps-lite:
- prometheus, grafana, alertmanager (monitoring)
- asterisk-pbx-1/2 (backend media — não crítico para MVP SIP)
- opensips-exporter, anomaly-detector (observability)

## Consequências

### Positivas
- Elimina risco de OOM na VPS atual
- Deploy em 3 waves com health checks entre cada wave
- Stack funcional para SIP signaling + media relay + OCP
- Backup operacional desde o primeiro deploy

### Negativas
- Sem monitoring em tempo real (prometheus/grafana)
- Sem Asterisk (sem voicemail, IVR, conferência)
- Sem métricas de SIP detalhadas (opensips-exporter)
- Detectores de anomalias offline

## Mitigações
- Monitoring pode ser adicionado em fase 2 via `docker-compose.prod.yml` completo
- Asterisk pode ser introduzido quando houver RAM disponível
- Alertas de OOM via `docker events` + script cron simples
- Logs centralizados via `docker logs` + rotatividade

## Alternativas Rejeitadas
1. **Upgrade de RAM para 8GB**: Custo adicional recorrente. Decisão de negócio adiar até validação de tráfego.
2. **Swap em disco**: Degrada performance de SIP (latência de media relay). Não aceitável para produção.
3. **Deploy bare-metal**: Viola regra Docker-first do projeto.

## Referências
- `docker-compose.vps.yml`
- `deploy/scripts/vps-deploy.sh`
- `deploy/scripts/vps-nginx-setup.sh`
