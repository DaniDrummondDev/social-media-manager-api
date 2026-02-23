# 09 — Billing & Subscription

[← Voltar ao índice](00-index.md)

---

## GET /api/v1/plans

Lista planos disponíveis na plataforma.

**Autenticação:** Nenhuma (endpoint público)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `currency` | string | `BRL` | Moeda dos preços: `BRL`, `USD` |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "aa0e8400-e29b-41d4-a716-446655440001",
      "type": "plan",
      "attributes": {
        "name": "Free",
        "slug": "free",
        "description": "Para quem está começando. Funcionalidades essenciais sem custo.",
        "price_monthly_cents": 0,
        "price_yearly_cents": 0,
        "currency": "BRL",
        "limits": {
          "members": 1,
          "social_accounts": 3,
          "publications_month": 30,
          "ai_generations_month": 50,
          "storage_gb": 1,
          "active_campaigns": 3,
          "automations": 0,
          "webhooks": 0,
          "reports_month": 5,
          "analytics_retention_days": 30
        },
        "features": {
          "ai_generation": true,
          "automations": false,
          "webhooks": false,
          "export_pdf": false,
          "export_csv": true,
          "priority_publishing": false
        },
        "sort_order": 1
      }
    },
    {
      "id": "aa0e8400-e29b-41d4-a716-446655440002",
      "type": "plan",
      "attributes": {
        "name": "Pro",
        "slug": "pro",
        "description": "Para profissionais e pequenas equipes. Recursos avançados.",
        "price_monthly_cents": 9900,
        "price_yearly_cents": 99900,
        "currency": "BRL",
        "limits": {
          "members": 5,
          "social_accounts": 10,
          "publications_month": 300,
          "ai_generations_month": 500,
          "storage_gb": 10,
          "active_campaigns": 20,
          "automations": 10,
          "webhooks": 3,
          "reports_month": 50,
          "analytics_retention_days": 180
        },
        "features": {
          "ai_generation": true,
          "automations": true,
          "webhooks": true,
          "export_pdf": true,
          "export_csv": true,
          "priority_publishing": true
        },
        "sort_order": 2
      }
    },
    {
      "id": "aa0e8400-e29b-41d4-a716-446655440003",
      "type": "plan",
      "attributes": {
        "name": "Enterprise",
        "slug": "enterprise",
        "description": "Para agências e grandes equipes. Sem limites.",
        "price_monthly_cents": 29900,
        "price_yearly_cents": 299900,
        "currency": "BRL",
        "limits": {
          "members": -1,
          "social_accounts": 50,
          "publications_month": -1,
          "ai_generations_month": 5000,
          "storage_gb": 100,
          "active_campaigns": -1,
          "automations": 100,
          "webhooks": 20,
          "reports_month": -1,
          "analytics_retention_days": 730
        },
        "features": {
          "ai_generation": true,
          "automations": true,
          "webhooks": true,
          "export_pdf": true,
          "export_csv": true,
          "priority_publishing": true
        },
        "sort_order": 3
      }
    }
  ]
}
```

### Notas

- Limite `-1` significa ilimitado.
- Preços em centavos para evitar problemas de ponto flutuante.
- `price_yearly_cents` reflete desconto anual (~16% sobre mensal × 12).

---

## GET /api/v1/billing/subscription

Retorna a subscription ativa da organização.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Response — 200 OK

```json
{
  "data": {
    "id": "bb0e8400-e29b-41d4-a716-446655440000",
    "type": "subscription",
    "attributes": {
      "plan": {
        "id": "aa0e8400-e29b-41d4-a716-446655440002",
        "name": "Pro",
        "slug": "pro"
      },
      "status": "active",
      "billing_cycle": "monthly",
      "current_period_start": "2026-02-01T00:00:00Z",
      "current_period_end": "2026-03-01T00:00:00Z",
      "trial_ends_at": null,
      "canceled_at": null,
      "cancel_at_period_end": false,
      "created_at": "2026-01-15T10:00:00Z"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 401 | AUTHENTICATION_ERROR | Token inválido ou expirado |

---

## GET /api/v1/billing/usage

Retorna uso atual da organização vs limites do plano.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Response — 200 OK

```json
{
  "data": {
    "type": "usage",
    "attributes": {
      "plan": "pro",
      "billing_cycle": "monthly",
      "current_period_end": "2026-03-01T00:00:00Z",
      "usage": {
        "publications": { "used": 87, "limit": 300, "percentage": 29 },
        "ai_generations": { "used": 234, "limit": 500, "percentage": 47 },
        "storage_bytes": { "used": 2147483648, "limit": 10737418240, "percentage": 20 },
        "social_accounts": { "used": 4, "limit": 10, "percentage": 40 },
        "members": { "used": 3, "limit": 5, "percentage": 60 },
        "active_campaigns": { "used": 8, "limit": 20, "percentage": 40 },
        "automations": { "used": 3, "limit": 10, "percentage": 30 },
        "webhooks": { "used": 1, "limit": 3, "percentage": 33 },
        "reports": { "used": 12, "limit": 50, "percentage": 24 }
      }
    }
  }
}
```

### Notas

- `limit: -1` indica recurso ilimitado (plano Enterprise).
- `percentage` é calculado no servidor (arredondado para inteiro).
- Dados de uso são cacheados em Redis com TTL 60s.

---

## GET /api/v1/billing/invoices

Lista faturas da organização.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `paid`, `open`, `void`, `uncollectible` |
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
      "id": "cc0e8400-e29b-41d4-a716-446655440000",
      "type": "invoice",
      "attributes": {
        "amount_cents": 9900,
        "currency": "BRL",
        "status": "paid",
        "invoice_url": "https://invoice.stripe.com/i/acct_xxx/inv_xxx",
        "period_start": "2026-01-01T00:00:00Z",
        "period_end": "2026-02-01T00:00:00Z",
        "paid_at": "2026-01-01T03:00:00Z",
        "created_at": "2026-01-01T00:00:00Z"
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

## POST /api/v1/billing/checkout

Cria uma Stripe Checkout Session para upgrade de plano.

**Autenticação:** Bearer token
**Roles:** owner

### Request

```json
{
  "plan_slug": "pro",
  "billing_cycle": "monthly",
  "success_url": "https://app.example.com/billing/success",
  "cancel_url": "https://app.example.com/billing/cancel"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `plan_slug` | string | Sim | Plano deve existir e estar ativo |
| `billing_cycle` | string | Sim | `monthly` ou `yearly` |
| `success_url` | string | Sim | URL válida (HTTPS) |
| `cancel_url` | string | Sim | URL válida (HTTPS) |

### Response — 200 OK

```json
{
  "data": {
    "type": "checkout_session",
    "attributes": {
      "checkout_url": "https://checkout.stripe.com/c/pay/cs_xxx",
      "session_id": "cs_xxx",
      "expires_at": "2026-02-23T11:30:00Z"
    }
  }
}
```

### Notas

- Checkout Session expira em 30 minutos.
- Se o checkout for abandonado, nenhuma alteração ocorre (stateless).
- Após pagamento, o Stripe webhook atualiza a subscription automaticamente.
- Não é possível fazer checkout para o plano atual ou para o plano Free.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas owner pode fazer upgrade |
| 409 | RESOURCE_CONFLICT | Já está no plano solicitado |
| 422 | VALIDATION_ERROR | Plano inválido ou inativo |

---

## POST /api/v1/billing/portal

Cria uma sessão do Stripe Customer Portal.

**Autenticação:** Bearer token
**Roles:** owner

### Request

```json
{
  "return_url": "https://app.example.com/billing"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `return_url` | string | Sim | URL válida (HTTPS) |

### Response — 200 OK

```json
{
  "data": {
    "type": "portal_session",
    "attributes": {
      "portal_url": "https://billing.stripe.com/p/session/xxx"
    }
  }
}
```

### Notas

- O Customer Portal permite ao owner: atualizar método de pagamento, ver faturas, cancelar subscription.
- Sessão expira em 5 minutos.
- Alterações feitas no portal são sincronizadas via webhooks do Stripe.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas owner pode acessar o portal |
| 404 | RESOURCE_NOT_FOUND | Organização não tem customer no Stripe (plano Free) |

---

## POST /api/v1/billing/cancel

Solicita cancelamento da subscription.

**Autenticação:** Bearer token
**Roles:** owner

### Request

```json
{
  "reason": "Não preciso mais do serviço",
  "feedback": "too_expensive"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `reason` | string | Não | Motivo livre (máx 500 chars) |
| `feedback` | string | Não | Enum: `too_expensive`, `missing_features`, `switched_service`, `too_complex`, `other` |

### Response — 200 OK

```json
{
  "data": {
    "type": "subscription",
    "attributes": {
      "status": "active",
      "cancel_at_period_end": true,
      "current_period_end": "2026-03-01T00:00:00Z"
    }
  },
  "meta": {
    "message": "Assinatura será cancelada em 2026-03-01. Você mantém acesso até essa data."
  }
}
```

### Notas

- Cancelamento mantém acesso até o fim do período pago (`cancel_at_period_end = true`).
- Após o período, subscription → `canceled` e org é rebaixada para plano Free.
- Owner pode reativar antes do fim do período via `POST /api/v1/billing/reactivate`.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas owner pode cancelar |
| 409 | RESOURCE_CONFLICT | Subscription já está cancelada ou é plano Free |

---

## POST /api/v1/billing/reactivate

Reativa uma subscription cancelada (antes do fim do período).

**Autenticação:** Bearer token
**Roles:** owner

### Response — 200 OK

```json
{
  "data": {
    "type": "subscription",
    "attributes": {
      "status": "active",
      "cancel_at_period_end": false,
      "current_period_end": "2026-03-01T00:00:00Z"
    }
  },
  "meta": {
    "message": "Assinatura reativada com sucesso."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 403 | AUTHORIZATION_ERROR | Apenas owner pode reativar |
| 409 | RESOURCE_CONFLICT | Subscription não está cancelada ou já expirou |

---

## POST /api/v1/webhooks/stripe

Recebe webhooks do Stripe para sincronizar estado de subscriptions e pagamentos.

**Autenticação:** Stripe-Signature header (HMAC validation)
**Nota:** Este endpoint NÃO usa Bearer token. Autenticação é via assinatura do Stripe.

### Eventos processados

| Stripe Event | Ação no sistema |
|-------------|-----------------|
| `customer.subscription.created` | Criar/atualizar Subscription |
| `customer.subscription.updated` | Atualizar status/plano |
| `customer.subscription.deleted` | Marcar subscription como canceled |
| `invoice.paid` | Registrar Invoice, confirmar período |
| `invoice.payment_failed` | Marcar subscription como past_due |
| `customer.subscription.trial_will_end` | Notificar organização (3 dias antes) |

### Response — 200 OK

```json
{
  "received": true
}
```

### Notas

- Webhook signature validada com `Stripe-Signature` header.
- Processamento idempotente via Stripe event ID (processado apenas 1x).
- Eventos não reconhecidos são ignorados com 200 OK (Stripe recomendação).
- Se validação de assinatura falhar, retorna 400.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | VALIDATION_ERROR | Assinatura do webhook inválida |
