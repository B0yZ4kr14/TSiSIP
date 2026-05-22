# Fase 1: Diagnóstico Popperiano — Relatório
## Data: 2026-05-20 06:43–06:50 UTC

## S1.1 — Status dos Containers

**NOTA CRÍTICA**: O container tsisip-ocp-1 NÃO aparece no docker compose ps. 
Está rodando como container manual, não gerenciado pelo compose. Risco operacional.

**Premissa P2**: PARCIALMENTE FALSIFICADA — todos healthy, mas OCP fora do compose.

## S1.2 — OpenSIPS Config e SIP

- opensips -c: config file ok ✅
- sipsak OPTIONS: SIP/2.0 200 OK em 0.423ms ✅
- opensipsctl não disponível no PATH ⚠️

**Premissa P1**: FALSIFICADA — OCP e OpenSIPS são independentes.

## S1.3 — Segurança OCP

- Acesso não-autenticado: 302 redirect para login ✅
- POST sem CSRF: "Invalid CSRF token." ✅
- SQL injection: requer retry com encoding correto

**Premissa P3**: PARCIAL — funciona mas sem testes de carga/fuzzing.

## S1.4 — Backup

- Host /opt/tsisip/backups/: NÃO EXISTE
- Container /backup/daily/: 2 backups encriptados

**Premissa P4**: PARCIAL — backups no container apenas, sem mount no host.

## S1.5 — Schema DB

- 15 tabelas presentes ✅
- subscriber: sem created_at (código já corrigido) ✅
- cdr: schema stock alinhado ✅
- dispatcher: weight é varchar(64) no schema stock ✅

## Resumo

| Premissa | Resultado | Ação F3 |
|---|---|---|
| P1: OCP = stack funcional | FALSIFICADA | Documentar desacoplamento |
| P2: Containers Up = saudável | PARCIAL | Mover OCP para compose |
| P3: Spec = validada | PARCIAL | Adicionar testes |
| P4: Backup = recuperável | PARCIAL | Mount volume + restore test |
