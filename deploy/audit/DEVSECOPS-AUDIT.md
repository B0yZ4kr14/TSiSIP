# TSiSIP DevSecOps Deployment Audit

## Socratic Self-Analysis

### Q1: Por que expor via subdiretorio `/TSiSIP` em vez de subdominio `tsisip.tsiapp.io`?

**Resposta**: O subdiretorio foi escolhido para:
- **Simplicidade de certificado SSL**: Um unico certificado Wildcard ou SAN cobre `tsiapp.io`; subdominios exigiriam `*.tsiapp.io` ou certificados adicionais.
- **Menor custo operacional**: Nao e necessario configurar DNS A/AAAA records adicionais nem gerenciar lifecycle de multiplos certificados.
- **Coerencia com arquitetura atual**: A VPS TSiAPP ja hospeda outros servicos; o subdiretorio funciona como path-based routing dentro do mesmo dominio.

**Contra-argumento Socratico**: Subdominios oferecem melhor isolamento de cookies, CORS, e permitem deploy independente. Se TSiSIP crescer para multiplos ambientes (staging, dev), o subdiretorio pode gerar confusao de path.

**Conclusao**: Para o estado atual (unico ambiente de producao), o subdiretorio e justificavel. Se escalar para >2 ambientes, migrar para subdominio.

### Q2: As permissoes do usuario `tsi` sao as de menor privilegio possivel?

**Resposta**: O usuario `tsi` e membro do grupo `docker`, o que concede privilegios efetivos de root via containers. Isso **viola** o principio de menor privilegio.

**Mitigacao proposta**:
1. Criar um usuario dedicado `tsisip-deploy` sem acesso ao grupo `docker`
2. Usar `docker rootless mode` ou um socket Docker restrito via `docker-socket-proxy`
3. O Ansible playbook deve usar `become: true` com sudo limitado a comandos Docker especificos
4. O acesso SSH deve ser restrito a chaves Ed25519, sem senha, com `Command` forced no `authorized_keys`

### Q3: A chave SSH `TSiHomeLab` do vault e realmente necessaria?

**Resposta**: Sim, se o vault contem credenciais de infraestrutura compartilhadas (ex: chaves de criptografia de backup). No entanto, para o deploy do OCP, apenas o token GitHub, host VPS e chave SSH sao necessarios. A chave `TSiHomeLab` deve ser usada apenas para operacoes de backup/restore (Feature 005), nao para deploy.

**Conclusao**: Separar scopes de secretos: deploy secrets (GitHub, SSH) vs. operational secrets (TSiHomeLab vault).

---

## Falsificacao Popper — Pontos Unicos de Falha (SPoF)

### SPoF 1: Falha na leitura do `~/.env`

**Hipotese**: Se o `~/.env` nao existir ou estiver malformado, o script de discovery falha e todo o pipeline de deploy para.

**Teste de Falsificacao**:
```bash
# Simular ~/.env ausente
mv ~/.env ~/.env.bak
./deploy/scripts/discover-and-secrets.sh
# Esperado: exit code 1 com mensagem clara de qual secret falta
```

**Fallback implementado**:
- Script valida cada secret individualmente e lista os faltantes antes de falhar
- Variaveis podem ser injetadas via environment (`export GITHUB_TOKEN=...`) sem depender do arquivo
- O script aceita `VAULT_FILE`, `ENV_FILE`, `SSH_DIR` como parametros de environment

### SPoF 2: Queda do container Docker do OCP

**Hipotese**: Se o container `tsisip/ocp` cair, o endpoint `/TSiSIP` retorna 502 e o operador nao tem visibilidade ate o proximo health check manual.

**Teste de Falsificacao**:
```bash
# Simular queda do container
docker stop tsisip-ocp-1
# Verificar Nginx retorna 502
curl -I https://tsiapp.io/TSiSIP/
# Esperado: HTTP 502 Bad Gateway
```

**Fallback implementado**:
- Docker Compose `restart: unless-stopped` no servico OCP
- Nginx `proxy_connect_timeout 30s` evita hanging connections
- Health check do Ansible: `retries: 10 delay: 5` detecta e reinicia automaticamente
- Feature 004 (Health Checks & Auto-Healing) adicionara circuit breaker e restart exponencial

### SPoF 3: Vazamento do token GitHub

**Hipotese**: Se o `GITHUB_TOKEN` for logado em texto claro ou commitado, um atacante obtem acesso total ao repositorio.

**Teste de Falsificacao**:
```bash
# Verificar que o token nunca aparece em logs
grep -r "$GITHUB_TOKEN" /var/log/ 2>/dev/null | wc -l
# Esperado: 0
```

**Mitigacoes implementadas**:
- Script `discover-and-secrets.sh` nunca ecoa o token; apenas confirma "found (redacted)"
- Token e escrito em arquivo temporario com `chmod 600` e instrucao explicita de delecao
- `.gitignore` ja exclui `secrets/`, `.env*`, e arquivos temporarios
- O playbook Ansible nao loga variaveis de ambiente (`no_log: true` pode ser adicionado)

### SPoF 4: Comprometimento da chave SSH

**Hipotese**: Se a chave privada SSH (`~/.ssh/tsiapp_key`) for comprometida, o atacante obtem acesso root-equivalente na VPS.

**Mitigacoes**:
- Chave restrita a Ed25519 (mais segura que RSA)
- Recomenda-se `ForceCommand` no `authorized_keys` do usuario `tsi`
- Chave deve ser protegida por passphrase e gerenciada via `ssh-agent`
- Rotacao de chaves a cada 90 dias

### SPoF 5: Falha do Nginx reverse proxy

**Hipotese**: Se o Nginx cair, todo o acesso ao TSiSIP e perdido, mesmo que o OCP container esteja saudavel.

**Mitigacoes**:
- Nginx deve ser supervisonado por `systemd` com `Restart=always`
- Health check separado do Nginx: `curl -f http://localhost/TSiSIP/health`
- Considerar HAProxy ou Traefik como alternativa se redundancia for necessaria

---

## Resiliencia — Matriz de Testes Automatizados

| Cenario | Teste | Frequencia | Responsavel |
|---|---|---|---|
| Secret discovery falha | `discover-and-secrets.sh --check-only` | Pre-deploy | CI/CD |
| Deploy Ansible falha | `ansible-playbook -i inventory.yml playbook-deploy.yml --check` | Pre-deploy | CI/CD |
| Container OCP cai | Docker health check + auto-restart | Continuo | Docker Compose |
| Nginx 502 | `curl -f https://tsiapp.io/TSiSIP/health` | A cada 30s | Monitoramento |
| Token expirado | `github-init-repo.sh --dry-run` | Semanal | DevOps |
| Backup SSH key | Tentativa de login com chave antiga | A cada rotacao | Seguranca |

---

## Recomendacoes de Hardening

1. **Rootless Docker**: Execute containers como usuario nao-root
2. **AppArmor/SELinux**: Profiles para containers OpenSIPS e OCP
3. **Network Policies**: `sip_internal` e `db_internal` como `internal: true` (ja implementado)
4. **Secrets Management**: Migrar de `~/.env` para HashiCorp Vault ou Docker Secrets nativo
5. **Audit Logging**: Toda acao de deploy deve gerar log imutavel (Feature 005)

---

*Auditoria gerada em: 2026-05-17*
*Arquiteto: DevSecOps Agent Autonomo*
