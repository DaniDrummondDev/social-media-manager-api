# Audit Logging — Social Media Manager API

## Objetivo

Definir a estratégia de auditoria para rastrear ações sensíveis e mudanças de estado no sistema, garantindo compliance com LGPD e segurança.

---

## Princípios

- Toda ação sensível é registrada.
- Logs de auditoria são **imutáveis** (append-only, sem UPDATE/DELETE).
- Dados sensíveis são **mascarados** nos logs.
- Auditoria é **infraestrutura**, não domínio.

---

## Eventos Auditados (Obrigatório)

### Autenticação

| Evento | Dados Registrados |
|--------|-------------------|
| Login sucesso | user_id, ip, user_agent |
| Login falha | email tentado, ip, motivo |
| Logout | user_id, ip |
| Token refresh | user_id, ip |
| 2FA habilitado | user_id |
| 2FA desabilitado | user_id |
| Refresh token reutilizado | user_id, ip (alerta de segurança) |

### Conta do Usuário

| Evento | Dados Registrados |
|--------|-------------------|
| Perfil atualizado | user_id, campos alterados (old → new) |
| Email alterado | user_id, email antigo (mascarado), email novo (mascarado) |
| Senha alterada | user_id (nunca a senha) |
| Conta excluída | user_id, motivo |
| Exportação de dados | user_id |

### Contas Sociais

| Evento | Dados Registrados |
|--------|-------------------|
| Conta conectada | organization_id, user_id, provider, username |
| Conta desconectada | organization_id, user_id, provider, username |
| Conta reconectada | organization_id, user_id, provider, username |
| Token refreshed | organization_id, provider (automático) |
| Token expirado | organization_id, provider |

### Conteúdo & Publicação

| Evento | Dados Registrados |
|--------|-------------------|
| Conteúdo criado | organization_id, user_id, content_id, campaign_id |
| Conteúdo atualizado | organization_id, user_id, content_id, campos alterados |
| Conteúdo excluído | organization_id, user_id, content_id |
| Publicação agendada | organization_id, user_id, content_id, providers, scheduled_at |
| Publicação executada | organization_id, user_id, content_id, provider, sucesso/falha |
| Publicação cancelada | organization_id, user_id, scheduled_post_id |

### Mídia

| Evento | Dados Registrados |
|--------|-------------------|
| Upload | user_id, media_id, mime_type, file_size |
| Scan resultado | media_id, scan_status |
| Exclusão | user_id, media_id |

### IA

| Evento | Dados Registrados |
|--------|-------------------|
| Geração solicitada | user_id, type, model, tokens_input, tokens_output |
| Custo estimado | user_id, cost_usd |

### Automação & Webhooks

| Evento | Dados Registrados |
|--------|-------------------|
| Regra criada | user_id, rule_id, action_type |
| Regra atualizada | user_id, rule_id, campos alterados |
| Regra excluída | user_id, rule_id |
| Automação executada | user_id, rule_id, comment_id, success |
| Webhook criado | user_id, webhook_id, url (mascarada) |
| Webhook excluído | user_id, webhook_id |
| Webhook delivery | webhook_id, event, status_code, success |

---

## Estrutura do Registro de Auditoria

### Tabela `audit_logs`

```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID,
    user_id UUID,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NOT NULL,
    resource_id UUID,
    old_values JSONB,
    new_values JSONB,
    metadata JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### Campos

| Campo | Descrição |
|-------|-----------|
| `organization_id` | Organização onde a ação ocorreu (nullable para ações de auth) |
| `user_id` | Quem executou (nullable para ações de sistema) |
| `action` | O que foi feito: `created`, `updated`, `deleted`, `login`, `logout`, etc. |
| `resource_type` | Tipo do recurso: `user`, `campaign`, `content`, `social_account`, etc. |
| `resource_id` | ID do recurso afetado |
| `old_values` | Estado anterior (para updates) — dados mascarados |
| `new_values` | Estado novo (para creates/updates) — dados mascarados |
| `metadata` | Contexto adicional: `correlation_id`, `trigger` (user/system/automation) |
| `ip_address` | IP do request |
| `user_agent` | User-agent do cliente |

---

## Mascaramento de Dados

| Dado | Mascaramento |
|------|-------------|
| Token OAuth | `"***"` (nunca logado, nem criptografado) |
| Senha | Nunca logada |
| Email | `j***@example.com` |
| Webhook URL | `https://***crm.com/api/***` |
| Webhook secret | `"***"` |
| IP | Mantido completo (necessário para segurança) |

---

## Retenção

| Tipo de Evento | Retenção |
|----------------|----------|
| Eventos de segurança (login, 2FA, token reuse) | 2 anos |
| Eventos operacionais (CRUD de recursos) | 1 ano |
| Eventos de sistema (jobs, sync) | 6 meses |

---

## Implementação

### Escrita

- Eventos de segurança (login, 2FA): escrita **síncrona** (no mesmo request).
- Eventos operacionais (CRUD): escrita **assíncrona** via queue (job dedicado).
- Eventos de sistema: escrita assíncrona.

### Queries comuns

- Por `organization_id` + `user_id` + período → histórico do usuário na org.
- Por `organization_id` + `action` + período → monitoramento de padrões na org.
- Por `resource_type` + `resource_id` → timeline de um recurso.
- Por `ip_address` → detecção de atividade suspeita.

### Indexes

```sql
CREATE INDEX idx_audit_org_user_created ON audit_logs (organization_id, user_id, created_at DESC);
CREATE INDEX idx_audit_resource ON audit_logs (resource_type, resource_id, created_at DESC);
CREATE INDEX idx_audit_action ON audit_logs (action, created_at DESC);
```

---

## Anti-Patterns

- Audit logging no Domain Layer (é infraestrutura).
- Logs de auditoria mutáveis (UPDATE/DELETE na tabela).
- Dados sensíveis em plain text nos logs.
- Logar apenas sucessos (falhas são igualmente importantes).
- Audit log sem `user_id` (impossibilita rastreamento).
- Audit log sem timestamp (impossibilita timeline).
- Auditoria síncrona para eventos não-críticos (impacto em performance).

---

## Dependências

Esta skill complementa:
- `01-security/auth-architecture.md` (login audit)
- `02-compliance/lgpd-compliance.md` (direitos do titular)
- `02-compliance/data-retention-policy.md` (retenção de logs)
