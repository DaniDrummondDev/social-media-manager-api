# Platform Administration — Social Media Manager API

## Objetivo

Definir as regras de domínio para **administração da plataforma**, incluindo gerenciamento de organizações, usuários, planos, métricas globais, moderação e configurações do sistema.

---

## Conceitos

### PlatformAdmin (Entity)

Usuário com privilégios administrativos sobre a plataforma. Separado logicamente dos usuários regulares — um PlatformAdmin pode ou não ter conta de usuário regular.

### SystemConfig (Entity)

Configurações globais da plataforma (feature flags, limites default, manutenção).

### PlatformMetric (Entity)

Métricas agregadas da plataforma para dashboard administrativo.

### AuditEntry (Entity)

Registro de ações administrativas para compliance e rastreabilidade.

---

## PlatformAdmin

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `user_id` | UUID | Referência ao User (autenticação) |
| `role` | enum | super_admin, admin, support |
| `permissions` | JSON | Permissões granulares |
| `is_active` | boolean | Se o admin está ativo |
| `last_login_at` | datetime | Último login no painel admin |
| `created_at` | datetime | Timestamp |

### Roles de Admin

| Role | Descrição | Acesso |
|------|-----------|--------|
| `super_admin` | Controle total da plataforma | Tudo |
| `admin` | Gerenciamento operacional | Orgs, users, planos, métricas |
| `support` | Suporte ao cliente | Visualização de orgs/users, ações de suporte |

### Regras

- **RN-ADM-01**: PlatformAdmin é autenticado via mesmo JWT mas com claim `platform_role`.
- **RN-ADM-02**: Endpoints admin são separados (`/api/v1/admin/*`) com middleware dedicado.
- **RN-ADM-03**: Toda ação admin é registrada no audit log com IP, user-agent e contexto.
- **RN-ADM-04**: Super admin não pode ser removido se for o último.

---

## Gerenciamento de Organizações

### Endpoints Admin

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /api/v1/admin/organizations` | GET | Listar todas as orgs (paginado, filtrável) |
| `GET /api/v1/admin/organizations/{id}` | GET | Detalhes da org (members, subscription, usage) |
| `PATCH /api/v1/admin/organizations/{id}` | PATCH | Atualizar status da org |
| `POST /api/v1/admin/organizations/{id}/suspend` | POST | Suspender org |
| `POST /api/v1/admin/organizations/{id}/unsuspend` | POST | Reativar org suspensa |
| `DELETE /api/v1/admin/organizations/{id}` | DELETE | Excluir org (force delete) |

### Suspensão de Organização

```
active → suspended (admin suspende)
suspended → active (admin reativa)
suspended → deleted (admin exclui, após 30 dias automaticamente)
```

### Regras

- **RN-ADM-05**: Organização suspensa perde acesso a todas as funcionalidades.
- **RN-ADM-06**: Agendamentos pendentes de org suspensa são pausados (não cancelados).
- **RN-ADM-07**: Suspensão notifica owner da org por email com motivo.
- **RN-ADM-08**: Org suspensa por mais de 30 dias é marcada para exclusão.

---

## Gerenciamento de Usuários

### Endpoints Admin

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /api/v1/admin/users` | GET | Listar todos os users (paginado, filtrável) |
| `GET /api/v1/admin/users/{id}` | GET | Detalhes do user (orgs, login history) |
| `POST /api/v1/admin/users/{id}/ban` | POST | Banir user da plataforma |
| `POST /api/v1/admin/users/{id}/unban` | POST | Desbanir user |
| `POST /api/v1/admin/users/{id}/force-verify` | POST | Forçar verificação de email |
| `POST /api/v1/admin/users/{id}/reset-password` | POST | Enviar reset de senha forçado |

### Regras

- **RN-ADM-09**: Banir user remove acesso a todas as orgs.
- **RN-ADM-10**: User banido tem sessões invalidadas imediatamente (blacklist de tokens).
- **RN-ADM-11**: Banimento registra motivo obrigatório no audit log.

---

## Gerenciamento de Planos

### Endpoints Admin

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /api/v1/admin/plans` | GET | Listar todos os planos |
| `POST /api/v1/admin/plans` | POST | Criar novo plano |
| `PATCH /api/v1/admin/plans/{id}` | PATCH | Atualizar plano |
| `POST /api/v1/admin/plans/{id}/deactivate` | POST | Desativar plano |
| `GET /api/v1/admin/plans/{id}/subscribers` | GET | Listar orgs assinantes |

### Regras

- **RN-ADM-12**: Alteração de preço só afeta novas assinaturas (grandfather clause).
- **RN-ADM-13**: Alteração de limites afeta todas as orgs assinantes.
- **RN-ADM-14**: Plano desativado mantém assinantes existentes.

---

## Dashboard e Métricas da Plataforma

### Endpoint

`GET /api/v1/admin/dashboard` retorna:

```json
{
  "overview": {
    "total_organizations": 1250,
    "active_organizations": 1100,
    "total_users": 3200,
    "active_users_30d": 2800
  },
  "subscriptions": {
    "free": 800,
    "pro": 350,
    "enterprise": 50,
    "trialing": 30,
    "past_due": 20,
    "mrr_cents": 4500000,
    "arr_cents": 54000000
  },
  "usage": {
    "publications_today": 1200,
    "ai_generations_today": 3400,
    "storage_used_gb": 450,
    "active_social_accounts": 5600
  },
  "health": {
    "publishing_success_rate_24h": 98.5,
    "avg_publishing_latency_ms": 1200,
    "providers_status": {
      "instagram": "operational",
      "tiktok": "operational",
      "youtube": "degraded"
    }
  }
}
```

### Métricas Calculadas

| Métrica | Cálculo | Frequência |
|---------|---------|------------|
| MRR (Monthly Recurring Revenue) | Soma das subscriptions ativas * preço mensal | Tempo real |
| ARR (Annual Recurring Revenue) | MRR * 12 | Tempo real |
| Churn rate | Orgs canceladas / total orgs ativas no período | Mensal |
| ARPU (Avg Revenue Per User) | MRR / total orgs ativas | Mensal |
| Publishing success rate | Posts published / total posts attempted | Rolling 24h |

---

## Configurações do Sistema

### SystemConfig

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `key` | string | Chave da configuração (unique) |
| `value` | JSON | Valor da configuração |
| `description` | text | Descrição da configuração |
| `updated_by` | UUID | Último admin que alterou |
| `updated_at` | datetime | Timestamp |

### Configurações Disponíveis

| Chave | Tipo | Default | Descrição |
|-------|------|---------|-----------|
| `maintenance_mode` | boolean | false | Ativa modo manutenção |
| `registration_enabled` | boolean | true | Permite novos registros |
| `default_trial_days` | integer | 14 | Dias de trial default |
| `max_orgs_per_user` | integer | 5 | Máximo de orgs por user |
| `ai_global_enabled` | boolean | true | Habilita/desabilita IA globalmente |
| `publishing_global_enabled` | boolean | true | Habilita/desabilita publicação |
| `stripe_webhook_secret` | string | — | Stripe webhook signing secret |

### Regras

- **RN-ADM-15**: `maintenance_mode` retorna HTTP 503 para todas as requests não-admin.
- **RN-ADM-16**: `registration_enabled = false` retorna HTTP 403 no endpoint de registro.
- **RN-ADM-17**: Alterações de SystemConfig são auditadas e emitem evento.
- **RN-ADM-18**: Secrets em SystemConfig são criptografados em repouso.

---

## Audit Trail Administrativo

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `admin_id` | UUID | PlatformAdmin que executou |
| `action` | string | Ação executada |
| `resource_type` | string | Tipo do recurso afetado |
| `resource_id` | UUID | ID do recurso afetado |
| `context` | JSON | Dados adicionais (old_value, new_value, reason) |
| `ip_address` | string | IP do admin |
| `user_agent` | string | User-agent |
| `created_at` | datetime | Timestamp |

### Ações Auditadas

| Ação | Quando |
|------|--------|
| `organization.suspended` | Admin suspende org |
| `organization.unsuspended` | Admin reativa org |
| `organization.deleted` | Admin exclui org |
| `user.banned` | Admin bane user |
| `user.unbanned` | Admin desbane user |
| `user.force_verified` | Admin força verificação |
| `plan.created` | Admin cria plano |
| `plan.updated` | Admin atualiza plano |
| `plan.deactivated` | Admin desativa plano |
| `config.updated` | Admin altera config do sistema |
| `subscription.overridden` | Admin altera subscription manualmente |

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `OrganizationSuspended` | Org suspensa | organization_id, admin_id, reason |
| `OrganizationUnsuspended` | Org reativada | organization_id, admin_id |
| `UserBanned` | User banido | user_id, admin_id, reason |
| `UserUnbanned` | User desbanido | user_id, admin_id |
| `PlanCreated` | Plano criado | plan_id, name, price |
| `PlanUpdated` | Plano atualizado | plan_id, changed_fields |
| `PlanDeactivated` | Plano desativado | plan_id |
| `SystemConfigUpdated` | Config alterada | key, admin_id |
| `MaintenanceModeEnabled` | Manutenção ativada | admin_id |
| `MaintenanceModeDisabled` | Manutenção desativada | admin_id |

---

## Tratamento de Falhas

- **Admin action falha**: rollback + log detalhado.
- **Stripe sync falha**: retry assíncrono, admin notificado.
- **Dashboard timeout**: cache de métricas com TTL 5 min (graceful degradation).
- **Audit log falha**: a ação admin ainda é executada mas alerta é disparado (audit não pode bloquear operação).

---

## Anti-Patterns

- Endpoints admin acessíveis sem middleware de autenticação admin.
- Ações admin sem audit trail (compliance obrigatório).
- Excluir org sem período de carência (dados irrecuperáveis).
- Dashboard com queries em tempo real sem cache (performance).
- Armazenar secrets de terceiros em plain text no SystemConfig.
- PlatformAdmin com acesso ao banco de dados de produção diretamente.
- Suspensão de org sem notificação ao owner.
- Alteração de preço de plano afetando assinantes existentes sem aviso.

---

## Dependências

- `06-domain/billing-subscription.md` (gerenciamento de planos e subscriptions)
- `01-security/auth-architecture.md` (autenticação admin via JWT)
- `01-security/audit-logging.md` (audit trail)
- `01-security/api-security.md` (middleware admin, RBAC)
