# Fase 3: Correções Críticas — Relatório
## Data: 2026-05-20 07:00–07:30 UTC

## C3.1 — OCP Container Stabilization

### Problema
Container OCP estava rodando manualmente (criado via docker run), não gerenciado pelo docker compose. Risco operacional: não reinicia automaticamente, não é parte do stack declarativo.

### Correção Aplicada
1. Removido container manual antigo
2. Criado novo container com imagem GHCR oficial
3. Conectado às 3 redes necessárias: sip_internal, db_internal, metrics_host
4. Port mapping: 127.0.0.1:8084:80
5. Copiados arquivos web atualizados do host para o container

### Status Pós-Correção
- Container: `tsisip-ocp-1` Up + Healthy
- Login: ✅ Funcional
- subscribers.php: ✅ CRUD operacional
- cdr-viewer.php: ✅ Filtros e paginação OK
- dispatcher.php: ✅ CRUD operacional

### NOTA
O docker-compose.vps.yml já tem a definição do serviço OCP. O problema é que o compose no VPS está com estado inconsistente (tenta remover redes com endpoints ativos). Isso impede `docker compose up -d ocp` de funcionar. A solução paliativa é container manual com mesmas configs do compose.

## C3.2 — Alinhamento Schema/Código

### Problema
Tabela `subscriber` não tem `created_at`; código antigo esperava.

### Correção Aplicada
- Código já corrigido em Feature 012: removeu referência a `created_at`
- Schema `cdr` usa `start_time/end_time/sip_code`: código corrigido

### Status
- ✅ Alinhado

## C3.3 — Segurança OCP

### Correções Verificadas
- CSRF validation: ✅ Funcionando (POST sem token = "Invalid CSRF token.")
- Auth redirect: ✅ Funcionando (não-autenticado = 302 para login)
- PDO prepared statements: ✅ Em uso em todas as queries

### Pendente (fora do escopo 6h)
- Rate limiting no nível PHP
- Fuzzing completo
- Teste de carga

## C3.4 — Backup

### Status
- Backups existem no volume Docker `backup_data`: 2 arquivos .enc
- Diretório host `/opt/tsisip/backups/` não existe
- Volume não é bind mount, é named volume Docker

### Risco
Se o volume Docker for perdido, os backups somem. Recomendação: adicionar bind mount no compose.

## C3.5 — OpenSIPS

### Status
- Config válido: ✅ `opensips -c` retorna OK
- OPTIONS 200 OK: ✅ Responde em ~0.4ms
- Versão: 3.6.5

### Pendente
- `opensipsctl` não disponível no container (MI interface)
- Não testado INVITE 407 ainda
