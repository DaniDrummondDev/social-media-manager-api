# 11 — Client Financial Management

[← Voltar ao índice](00-index.md)

> **Fase:** 2 (Sprint 8)
>
> Gestão financeira que agências e gestores fazem com seus **próprios clientes**. Diferente do Billing & Subscription, que trata da cobrança do SaaS à organização. Nesta versão, o módulo é **tracking interno** — sem geração de PDF, envio de email ou processamento de pagamento.

---

## POST /api/v1/clients

Cadastra um novo cliente da organização.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "name": "Loja Moda Online",
  "email": "financeiro@modaonline.com",
  "phone": "+5511999887766",
  "company_name": "Moda Online Ltda",
  "tax_id": "12.345.678/0001-99",
  "billing_address": {
    "street": "Rua das Flores",
    "number": "123",
    "complement": "Sala 4",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01001-000",
    "country": "BR"
  },
  "notes": "Pagamento via boleto, vencimento dia 10"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 2-200 caracteres |
| `email` | string | Não | Email válido |
| `phone` | string | Não | Formato E.164 |
| `company_name` | string | Não | 2-200 caracteres |
| `tax_id` | string | Não | CPF ou CNPJ válido |
| `billing_address` | object | Não | Endereço completo |
| `notes` | string | Não | Máx 1000 caracteres |

### Response — 201 Created

```json
{
  "data": {
    "id": "cc0e8400-e29b-41d4-a716-446655440000",
    "type": "client",
    "attributes": {
      "name": "Loja Moda Online",
      "email": "financeiro@modaonline.com",
      "phone": "+5511999887766",
      "company_name": "Moda Online Ltda",
      "tax_id": "12.345.678/0001-99",
      "status": "active",
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 422 | VALIDATION_ERROR | Dados inválidos ou CPF/CNPJ inválido |

---

## GET /api/v1/clients

Lista clientes da organização.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `active`, `inactive`, `archived` |
| `search` | string | — | Busca por nome, email ou empresa |
| `sort` | string | `-created_at` | `created_at`, `name`, `company_name` |
| `per_page` | integer | 20 | Itens por página (máx: 100) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "cc0e8400-...",
      "type": "client",
      "attributes": {
        "name": "Loja Moda Online",
        "company_name": "Moda Online Ltda",
        "email": "financeiro@modaonline.com",
        "status": "active",
        "active_contracts": 1,
        "total_revenue_cents": 1500000,
        "open_invoices_count": 1,
        "created_at": "2026-02-23T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": false,
    "next_cursor": null
  }
}
```

---

## GET /api/v1/clients/{id}

Retorna detalhes de um cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Response — 200 OK

```json
{
  "data": {
    "id": "cc0e8400-...",
    "type": "client",
    "attributes": {
      "name": "Loja Moda Online",
      "email": "financeiro@modaonline.com",
      "phone": "+5511999887766",
      "company_name": "Moda Online Ltda",
      "tax_id": "12.345.678/0001-99",
      "billing_address": {
        "street": "Rua das Flores",
        "number": "123",
        "complement": "Sala 4",
        "neighborhood": "Centro",
        "city": "São Paulo",
        "state": "SP",
        "zip_code": "01001-000",
        "country": "BR"
      },
      "notes": "Pagamento via boleto, vencimento dia 10",
      "status": "active",
      "financial_summary": {
        "total_revenue_cents": 1500000,
        "total_costs_cents": 450000,
        "profit_cents": 1050000,
        "profit_margin_percent": 70.0,
        "open_invoices_cents": 500000,
        "overdue_invoices_cents": 0
      },
      "active_contracts": [
        {
          "id": "dd0e8400-...",
          "name": "Gestão de Redes Sociais",
          "type": "fixed_monthly",
          "value_cents": 500000,
          "status": "active"
        }
      ],
      "social_accounts": [
        {
          "id": "ee0e8400-...",
          "provider": "instagram",
          "username": "@modaonline"
        }
      ],
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## PATCH /api/v1/clients/{id}

Atualiza dados de um cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "email": "novo-email@modaonline.com",
  "notes": "Pagamento via PIX agora"
}
```

### Response — 200 OK

Retorna o cliente atualizado (mesmo formato do GET).

---

## POST /api/v1/clients/{id}/archive

Arquiva um cliente (soft deactivation).

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "client",
    "attributes": {
      "id": "cc0e8400-...",
      "status": "archived"
    }
  },
  "meta": {
    "message": "Cliente arquivado. Contratos ativos foram pausados."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Cliente tem faturas em aberto |

---

## POST /api/v1/clients/{id}/contracts

Cria um contrato para o cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "name": "Gestão de Redes Sociais — 2026",
  "type": "fixed_monthly",
  "value_cents": 500000,
  "currency": "BRL",
  "starts_at": "2026-03-01",
  "ends_at": "2026-12-31",
  "social_account_ids": ["ee0e8400-...", "ee0e8401-..."]
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 2-200 caracteres |
| `type` | string | Sim | `fixed_monthly`, `per_campaign`, `per_post`, `hourly` |
| `value_cents` | integer | Sim | > 0 |
| `currency` | string | Sim | `BRL`, `USD`, `EUR` |
| `starts_at` | date | Sim | Formato YYYY-MM-DD |
| `ends_at` | date | Não | Deve ser posterior a `starts_at` |
| `social_account_ids` | uuid[] | Não | Contas sociais vinculadas ao contrato |

### Response — 201 Created

```json
{
  "data": {
    "id": "dd0e8400-e29b-41d4-a716-446655440000",
    "type": "client_contract",
    "attributes": {
      "name": "Gestão de Redes Sociais — 2026",
      "type": "fixed_monthly",
      "value_cents": 500000,
      "currency": "BRL",
      "starts_at": "2026-03-01",
      "ends_at": "2026-12-31",
      "status": "active",
      "social_accounts": [
        { "id": "ee0e8400-...", "provider": "instagram", "username": "@modaonline" }
      ],
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## GET /api/v1/clients/{id}/contracts

Lista contratos de um cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `active`, `paused`, `completed`, `cancelled` |
| `sort` | string | `-created_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

Formato padrão de listagem paginada.

---

## PATCH /api/v1/clients/{clientId}/contracts/{id}

Atualiza um contrato.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "value_cents": 600000,
  "ends_at": "2027-06-30"
}
```

### Response — 200 OK

Retorna contrato atualizado.

---

## POST /api/v1/clients/{id}/invoices

Cria uma fatura (registro de tracking) para o cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "contract_id": "dd0e8400-...",
  "reference_month": "2026-03",
  "due_date": "2026-03-10",
  "items": [
    {
      "description": "Gestão de redes sociais — Março/2026",
      "quantity": 1,
      "unit_price_cents": 500000
    },
    {
      "description": "Campanha extra — Dia das Mães",
      "quantity": 1,
      "unit_price_cents": 150000
    }
  ],
  "discount_cents": 0,
  "notes": "Vencimento dia 10/03"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `contract_id` | uuid | Não | Contrato associado |
| `reference_month` | string | Sim | Formato YYYY-MM |
| `due_date` | date | Sim | Formato YYYY-MM-DD, deve ser futura |
| `items` | array | Sim | Mín 1 item |
| `items[].description` | string | Sim | 2-500 caracteres |
| `items[].quantity` | integer | Sim | > 0 |
| `items[].unit_price_cents` | integer | Sim | > 0 |
| `discount_cents` | integer | Não | ≥ 0 |
| `notes` | string | Não | Máx 1000 caracteres |

### Response — 201 Created

```json
{
  "data": {
    "id": "ff0e8400-e29b-41d4-a716-446655440000",
    "type": "client_invoice",
    "attributes": {
      "reference_month": "2026-03",
      "status": "draft",
      "items": [
        {
          "description": "Gestão de redes sociais — Março/2026",
          "quantity": 1,
          "unit_price_cents": 500000,
          "total_cents": 500000
        },
        {
          "description": "Campanha extra — Dia das Mães",
          "quantity": 1,
          "unit_price_cents": 150000,
          "total_cents": 150000
        }
      ],
      "subtotal_cents": 650000,
      "discount_cents": 0,
      "total_cents": 650000,
      "currency": "BRL",
      "due_date": "2026-03-10",
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## GET /api/v1/clients/{id}/invoices

Lista faturas de um cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `draft`, `sent`, `paid`, `overdue`, `cancelled` |
| `reference_month` | string | — | `2026-03` |
| `from` | date | — | Due date a partir de |
| `to` | date | — | Due date até |
| `sort` | string | `-due_date` | `due_date`, `created_at`, `total_cents` |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

Formato padrão de listagem paginada.

---

## PATCH /api/v1/clients/{clientId}/invoices/{id}

Atualiza uma fatura.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "items": [
    {
      "description": "Gestão de redes sociais — Março/2026",
      "quantity": 1,
      "unit_price_cents": 550000
    }
  ],
  "discount_cents": 50000
}
```

### Notas

- Apenas faturas com status `draft` podem ser editadas.
- Faturas `sent`, `paid` ou `cancelled` são imutáveis.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Fatura não está em status draft |

---

## POST /api/v1/clients/{clientId}/invoices/{id}/mark-sent

Marca fatura como enviada.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "client_invoice",
    "attributes": {
      "id": "ff0e8400-...",
      "status": "sent",
      "sent_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## POST /api/v1/clients/{clientId}/invoices/{id}/mark-paid

Registra pagamento da fatura (manual).

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "paid_at": "2026-03-10T14:00:00Z",
  "payment_method": "pix",
  "notes": "Recebido via PIX"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `paid_at` | datetime | Não | Default: agora |
| `payment_method` | string | Não | `pix`, `boleto`, `transfer`, `credit_card`, `other` |
| `notes` | string | Não | Máx 500 caracteres |

### Response — 200 OK

```json
{
  "data": {
    "type": "client_invoice",
    "attributes": {
      "id": "ff0e8400-...",
      "status": "paid",
      "paid_at": "2026-03-10T14:00:00Z"
    }
  }
}
```

---

## POST /api/v1/clients/{clientId}/invoices/{id}/cancel

Cancela uma fatura.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "client_invoice",
    "attributes": {
      "id": "ff0e8400-...",
      "status": "cancelled"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Fatura já está paga |

---

## POST /api/v1/cost-allocations

Registra uma alocação de custo para um cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "client_id": "cc0e8400-...",
  "resource_type": "campaign",
  "resource_id": "aa0e8400-...",
  "description": "Campanha Black Friday — produção de conteúdo",
  "cost_cents": 25000,
  "currency": "BRL"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `client_id` | uuid | Sim | Cliente ativo da org |
| `resource_type` | string | Sim | `campaign`, `ai_generation`, `media_storage`, `publication` |
| `resource_id` | uuid | Não | Referência ao recurso específico |
| `description` | string | Sim | 2-500 caracteres |
| `cost_cents` | integer | Sim | > 0 |
| `currency` | string | Sim | `BRL`, `USD`, `EUR` |

### Response — 201 Created

```json
{
  "data": {
    "id": "gg0e8400-...",
    "type": "cost_allocation",
    "attributes": {
      "client_id": "cc0e8400-...",
      "resource_type": "campaign",
      "description": "Campanha Black Friday — produção de conteúdo",
      "cost_cents": 25000,
      "currency": "BRL",
      "allocated_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## GET /api/v1/cost-allocations

Lista alocações de custo.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `client_id` | uuid | — | Filtrar por cliente |
| `resource_type` | string | — | `campaign`, `ai_generation`, `media_storage`, `publication` |
| `from` | date | — | Alocado a partir de |
| `to` | date | — | Alocado até |
| `sort` | string | `-allocated_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

Formato padrão de listagem paginada.

---

## GET /api/v1/client-reports/overview

Relatório financeiro geral de todos os clientes.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `period` | string | `current_month` | `current_month`, `last_month`, `last_3_months`, `last_6_months`, `custom` |
| `from` | date | — | Quando `period=custom` |
| `to` | date | — | Quando `period=custom` |

### Response — 200 OK

```json
{
  "data": {
    "type": "financial_overview",
    "attributes": {
      "period": {
        "from": "2026-02-01",
        "to": "2026-02-28"
      },
      "summary": {
        "total_revenue_cents": 3500000,
        "total_costs_cents": 980000,
        "total_profit_cents": 2520000,
        "profit_margin_percent": 72.0,
        "total_clients": 8,
        "active_contracts": 10,
        "open_invoices_cents": 1200000,
        "overdue_invoices_cents": 0
      },
      "by_client": [
        {
          "client_id": "cc0e8400-...",
          "client_name": "Loja Moda Online",
          "revenue_cents": 500000,
          "costs_cents": 120000,
          "profit_cents": 380000,
          "profit_margin_percent": 76.0
        }
      ],
      "by_resource_type": {
        "campaign": 450000,
        "ai_generation": 80000,
        "media_storage": 200000,
        "publication": 250000
      }
    }
  }
}
```

---

## GET /api/v1/client-reports/profitability

Relatório de lucratividade por cliente.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `client_id` | uuid | — | Filtrar por cliente específico |
| `period` | string | `last_6_months` | Período de análise |
| `from` | date | — | Quando `period=custom` |
| `to` | date | — | Quando `period=custom` |

### Response — 200 OK

```json
{
  "data": {
    "type": "profitability_report",
    "attributes": {
      "client": {
        "id": "cc0e8400-...",
        "name": "Loja Moda Online"
      },
      "period": {
        "from": "2025-09-01",
        "to": "2026-02-28"
      },
      "monthly_breakdown": [
        {
          "month": "2026-02",
          "revenue_cents": 500000,
          "costs_cents": 120000,
          "profit_cents": 380000,
          "campaigns_count": 3,
          "publications_count": 45
        },
        {
          "month": "2026-01",
          "revenue_cents": 500000,
          "costs_cents": 95000,
          "profit_cents": 405000,
          "campaigns_count": 2,
          "publications_count": 38
        }
      ],
      "totals": {
        "total_revenue_cents": 3000000,
        "total_costs_cents": 650000,
        "total_profit_cents": 2350000,
        "avg_profit_margin_percent": 78.3
      }
    }
  }
}
```
