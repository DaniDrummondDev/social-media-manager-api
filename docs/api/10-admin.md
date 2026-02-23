# 10 — Platform Administration

[← Voltar ao índice](00-index.md)

> **Nota:** Todos os endpoints nesta seção requerem autenticação de Platform Admin (`/api/v1/admin/*` com middleware dedicado). Toda ação é registrada automaticamente no audit log.

---

## GET /api/v1/admin/dashboard

Retorna métricas globais da plataforma.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Response — 200 OK

```json
{
  "data": {
    "type": "platform_dashboard",
    "attributes": {
      "overview": {
        "total_organizations": 1250,
        "active_organizations": 1100,
        "suspended_organizations": 5,
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
        "arr_cents": 54000000,
        "churn_rate_percent": 3.2
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
      },
      "generated_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Notas

- Dados cacheados por 5 minutos para performance.
- `providers_status` reflete circuit breaker status dos adapters.

---

## GET /api/v1/admin/organizations

Lista todas as organizações da plataforma.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `active`, `suspended`, `deleted` |
| `plan` | string | — | Slug do plano: `free`, `creator`, `professional`, `agency` |
| `search` | string | — | Busca por nome da org ou email do owner |
| `from` | datetime | — | Criada a partir de |
| `to` | datetime | — | Criada até |
| `sort` | string | `-created_at` | `created_at`, `name`, `members_count` |
| `per_page` | integer | 20 | Itens por página (máx: 100) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "dd0e8400-e29b-41d4-a716-446655440000",
      "type": "organization",
      "attributes": {
        "name": "Agência Digital XYZ",
        "status": "active",
        "plan": "pro",
        "members_count": 3,
        "social_accounts_count": 7,
        "owner": {
          "id": "ee0e8400-...",
          "name": "Rafael Santos",
          "email": "rafael@agencia.com"
        },
        "subscription_status": "active",
        "created_at": "2026-01-10T08:00:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo..."
  }
}
```

---

## GET /api/v1/admin/organizations/{id}

Retorna detalhes completos de uma organização.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Response — 200 OK

```json
{
  "data": {
    "id": "dd0e8400-e29b-41d4-a716-446655440000",
    "type": "organization",
    "attributes": {
      "name": "Agência Digital XYZ",
      "status": "active",
      "created_at": "2026-01-10T08:00:00Z",
      "members": [
        {
          "user_id": "ee0e8400-...",
          "name": "Rafael Santos",
          "email": "rafael@agencia.com",
          "role": "owner",
          "joined_at": "2026-01-10T08:00:00Z"
        },
        {
          "user_id": "ff0e8400-...",
          "name": "Marina Silva",
          "email": "marina@agencia.com",
          "role": "admin",
          "joined_at": "2026-01-12T10:00:00Z"
        }
      ],
      "subscription": {
        "plan": "pro",
        "status": "active",
        "billing_cycle": "monthly",
        "current_period_end": "2026-03-01T00:00:00Z"
      },
      "usage": {
        "publications": { "used": 87, "limit": 300 },
        "ai_generations": { "used": 234, "limit": 500 },
        "storage_bytes": { "used": 2147483648, "limit": 10737418240 },
        "social_accounts": { "used": 4, "limit": 10 }
      },
      "social_accounts": [
        {
          "id": "aa0e8400-...",
          "provider": "instagram",
          "username": "@agenciaxyz",
          "status": "connected"
        }
      ]
    }
  }
}
```

---

## POST /api/v1/admin/organizations/{id}/suspend

Suspende uma organização.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Request

```json
{
  "reason": "Violação dos termos de uso — conteúdo impróprio"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `reason` | string | Sim | 10-500 caracteres |

### Response — 200 OK

```json
{
  "data": {
    "type": "organization",
    "attributes": {
      "id": "dd0e8400-...",
      "status": "suspended",
      "suspended_at": "2026-02-23T10:30:00Z"
    }
  },
  "meta": {
    "message": "Organização suspensa. Owner notificado por email."
  }
}
```

### Notas

- Organização suspensa perde acesso a todas as funcionalidades.
- Agendamentos pendentes são pausados (não cancelados).
- Owner é notificado por email com o motivo.
- Org suspensa por mais de 30 dias é marcada para exclusão.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Organização já está suspensa |

---

## POST /api/v1/admin/organizations/{id}/unsuspend

Reativa uma organização suspensa.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "organization",
    "attributes": {
      "id": "dd0e8400-...",
      "status": "active",
      "unsuspended_at": "2026-02-23T11:00:00Z"
    }
  },
  "meta": {
    "message": "Organização reativada. Agendamentos pausados serão retomados."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Organização não está suspensa |

---

## DELETE /api/v1/admin/organizations/{id}

Exclui uma organização (force delete).

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin

### Request

```json
{
  "reason": "Solicitação do cliente — encerramento de conta",
  "confirm": true
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `reason` | string | Sim | 10-500 caracteres |
| `confirm` | boolean | Sim | Deve ser `true` |

### Response — 200 OK

```json
{
  "data": {
    "message": "Organização marcada para exclusão.",
    "purge_at": "2026-03-25T10:30:00Z"
  }
}
```

### Notas

- Soft delete com período de carência de 30 dias.
- Todos os dados da organização serão purgados após o período.
- Apenas `super_admin` pode excluir organizações.

---

## GET /api/v1/admin/users

Lista todos os usuários da plataforma.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `active`, `inactive`, `suspended` |
| `search` | string | — | Busca por nome ou email |
| `email_verified` | boolean | — | Filtrar por email verificado |
| `two_factor` | boolean | — | Filtrar por 2FA habilitado |
| `from` | datetime | — | Registrado a partir de |
| `to` | datetime | — | Registrado até |
| `sort` | string | `-created_at` | `created_at`, `name`, `email`, `last_login_at` |
| `per_page` | integer | 20 | Itens por página (máx: 100) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "ee0e8400-e29b-41d4-a716-446655440000",
      "type": "user",
      "attributes": {
        "name": "Marina Silva",
        "email": "marina@exemplo.com",
        "status": "active",
        "email_verified": true,
        "two_factor_enabled": true,
        "organizations_count": 3,
        "last_login_at": "2026-02-23T08:00:00Z",
        "created_at": "2026-01-05T10:00:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo..."
  }
}
```

---

## GET /api/v1/admin/users/{id}

Retorna detalhes completos de um usuário.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Response — 200 OK

```json
{
  "data": {
    "id": "ee0e8400-e29b-41d4-a716-446655440000",
    "type": "user",
    "attributes": {
      "name": "Marina Silva",
      "email": "marina@exemplo.com",
      "status": "active",
      "email_verified": true,
      "email_verified_at": "2026-01-05T10:05:00Z",
      "two_factor_enabled": true,
      "timezone": "America/Sao_Paulo",
      "last_login_at": "2026-02-23T08:00:00Z",
      "last_login_ip": "189.44.xxx.xxx",
      "created_at": "2026-01-05T10:00:00Z",
      "organizations": [
        {
          "id": "dd0e8400-...",
          "name": "Cliente A",
          "role": "owner",
          "joined_at": "2026-01-05T10:00:00Z"
        },
        {
          "id": "dd0e8401-...",
          "name": "Cliente B",
          "role": "admin",
          "joined_at": "2026-01-12T10:00:00Z"
        }
      ],
      "recent_logins": [
        {
          "ip_address": "189.44.xxx.xxx",
          "user_agent": "Mozilla/5.0 ...",
          "logged_in_at": "2026-02-23T08:00:00Z"
        }
      ]
    }
  }
}
```

---

## POST /api/v1/admin/users/{id}/ban

Bane um usuário da plataforma.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Request

```json
{
  "reason": "Spam e uso abusivo da plataforma"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `reason` | string | Sim | 10-500 caracteres |

### Response — 200 OK

```json
{
  "data": {
    "type": "user",
    "attributes": {
      "id": "ee0e8400-...",
      "status": "suspended",
      "banned_at": "2026-02-23T10:30:00Z"
    }
  },
  "meta": {
    "message": "Usuário banido. Todas as sessões foram invalidadas."
  }
}
```

### Notas

- Banimento remove acesso a todas as organizações.
- Sessões são invalidadas imediatamente (blacklist de tokens).
- Motivo é registrado no audit log.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Usuário já está banido |
| 403 | AUTHORIZATION_ERROR | Não pode banir outro Platform Admin |

---

## POST /api/v1/admin/users/{id}/unban

Desbane um usuário.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "user",
    "attributes": {
      "id": "ee0e8400-...",
      "status": "active"
    }
  },
  "meta": {
    "message": "Usuário desbanido. Acesso restaurado."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Usuário não está banido |

---

## POST /api/v1/admin/users/{id}/force-verify

Força a verificação do email de um usuário.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Response — 200 OK

```json
{
  "data": {
    "type": "user",
    "attributes": {
      "id": "ee0e8400-...",
      "email_verified": true,
      "email_verified_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## POST /api/v1/admin/users/{id}/reset-password

Envia email de reset de senha forçado para o usuário.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin, support

### Response — 200 OK

```json
{
  "data": {
    "message": "Email de redefinição de senha enviado para marina@exemplo.com"
  }
}
```

---

## GET /api/v1/admin/plans

Lista todos os planos (incluindo inativos).

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "aa0e8400-...",
      "type": "plan",
      "attributes": {
        "name": "Free",
        "slug": "free",
        "price_monthly_cents": 0,
        "price_yearly_cents": 0,
        "currency": "BRL",
        "is_active": true,
        "subscribers_count": 800,
        "limits": { "..." },
        "features": { "..." },
        "sort_order": 1,
        "created_at": "2026-01-01T00:00:00Z"
      }
    }
  ]
}
```

---

## POST /api/v1/admin/plans

Cria um novo plano.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin

### Request

```json
{
  "name": "Starter",
  "slug": "starter",
  "description": "Para quem precisa de mais que o Free.",
  "price_monthly_cents": 4900,
  "price_yearly_cents": 49900,
  "currency": "BRL",
  "limits": {
    "members": 2,
    "social_accounts": 5,
    "publications_month": 100,
    "ai_generations_month": 200,
    "storage_gb": 5,
    "active_campaigns": 10,
    "automations": 5,
    "webhooks": 1,
    "reports_month": 20,
    "analytics_retention_days": 90
  },
  "features": {
    "ai_generation": true,
    "automations": true,
    "webhooks": true,
    "export_pdf": false,
    "export_csv": true,
    "priority_publishing": false
  },
  "sort_order": 2
}
```

### Response — 201 Created

```json
{
  "data": {
    "id": "aa0e8401-...",
    "type": "plan",
    "attributes": {
      "name": "Starter",
      "slug": "starter",
      "is_active": true,
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas super_admin pode criar planos |
| 422 | VALIDATION_ERROR | Slug já existe ou dados inválidos |

---

## PATCH /api/v1/admin/plans/{id}

Atualiza um plano existente.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin

### Request

```json
{
  "limits": {
    "ai_generations_month": 600
  }
}
```

### Response — 200 OK

```json
{
  "data": {
    "id": "aa0e8400-...",
    "type": "plan",
    "attributes": {
      "name": "Pro",
      "slug": "pro",
      "updated_at": "2026-02-23T10:30:00Z"
    }
  },
  "meta": {
    "message": "Plano atualizado. Alteração de limites afeta 350 organizações assinantes."
  }
}
```

### Notas

- Alteração de preço só afeta novas assinaturas (grandfather clause).
- Alteração de limites afeta todas as organizações assinantes.

---

## POST /api/v1/admin/plans/{id}/deactivate

Desativa um plano (não aceita novas assinaturas).

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin

### Response — 200 OK

```json
{
  "data": {
    "type": "plan",
    "attributes": {
      "id": "aa0e8400-...",
      "is_active": false
    }
  },
  "meta": {
    "message": "Plano desativado. 350 assinantes existentes mantêm acesso."
  }
}
```

---

## GET /api/v1/admin/plans/{id}/subscribers

Lista organizações assinantes de um plano.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `subscription_status` | string | — | `active`, `trialing`, `past_due`, `canceled` |
| `sort` | string | `-created_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "dd0e8400-...",
      "type": "organization",
      "attributes": {
        "name": "Agência Digital XYZ",
        "owner_email": "rafael@agencia.com",
        "subscription_status": "active",
        "billing_cycle": "monthly",
        "subscribed_at": "2026-01-15T10:00:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo...",
    "total_subscribers": 350
  }
}
```

---

## GET /api/v1/admin/config

Retorna configurações do sistema.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Response — 200 OK

```json
{
  "data": [
    {
      "key": "maintenance_mode",
      "type": "boolean",
      "value": false,
      "description": "Ativa modo de manutenção para todos os usuários",
      "updated_by": "admin@platform.com",
      "updated_at": "2026-02-20T10:00:00Z"
    },
    {
      "key": "registration_enabled",
      "type": "boolean",
      "value": true,
      "description": "Permite novos registros na plataforma",
      "updated_by": null,
      "updated_at": null
    },
    {
      "key": "default_trial_days",
      "type": "integer",
      "value": 14,
      "description": "Dias de trial para planos pagos",
      "updated_by": null,
      "updated_at": null
    },
    {
      "key": "max_orgs_per_user",
      "type": "integer",
      "value": 5,
      "description": "Máximo de organizações por usuário",
      "updated_by": null,
      "updated_at": null
    },
    {
      "key": "ai_global_enabled",
      "type": "boolean",
      "value": true,
      "description": "Habilita/desabilita IA globalmente",
      "updated_by": null,
      "updated_at": null
    },
    {
      "key": "publishing_global_enabled",
      "type": "boolean",
      "value": true,
      "description": "Habilita/desabilita publicação globalmente",
      "updated_by": null,
      "updated_at": null
    }
  ]
}
```

### Notas

- Secrets (como `stripe_webhook_secret`) não são retornados nesta listagem.
- `updated_by` é `null` para configurações que nunca foram alteradas (usam default).

---

## PATCH /api/v1/admin/config

Atualiza configurações do sistema.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin

### Request

```json
{
  "configs": [
    { "key": "maintenance_mode", "value": true },
    { "key": "default_trial_days", "value": 7 }
  ]
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `configs` | array | Sim | 1-10 configurações por request |
| `configs[].key` | string | Sim | Chave deve existir |
| `configs[].value` | mixed | Sim | Tipo deve corresponder ao esperado |

### Response — 200 OK

```json
{
  "data": {
    "updated": ["maintenance_mode", "default_trial_days"],
    "updated_at": "2026-02-23T10:30:00Z"
  },
  "meta": {
    "message": "2 configurações atualizadas."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas super_admin pode alterar configurações |
| 422 | VALIDATION_ERROR | Chave não existe ou tipo incorreto |

---

## GET /api/v1/admin/audit-log

Retorna o audit log de ações administrativas.

**Autenticação:** Bearer token (Platform Admin)
**Roles:** super_admin, admin

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `action` | string | — | Ação: `organization.suspended`, `user.banned`, etc. |
| `admin_id` | uuid | — | Filtrar por admin que executou |
| `resource_type` | string | — | `organization`, `user`, `plan`, `config` |
| `resource_id` | uuid | — | ID do recurso afetado |
| `from` | datetime | — | Data início |
| `to` | datetime | — | Data fim |
| `sort` | string | `-created_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página (máx: 100) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "ff0e8400-e29b-41d4-a716-446655440000",
      "type": "audit_entry",
      "attributes": {
        "action": "organization.suspended",
        "admin": {
          "id": "aa0e8400-...",
          "name": "Admin User",
          "email": "admin@platform.com"
        },
        "resource_type": "organization",
        "resource_id": "dd0e8400-...",
        "context": {
          "reason": "Violação dos termos de uso",
          "organization_name": "Org Problemática"
        },
        "ip_address": "10.0.0.1",
        "created_at": "2026-02-23T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo..."
  }
}
```
