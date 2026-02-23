# 06 — Billing & Subscription

[← Voltar ao índice](00-index.md)

---

## Tipos ENUM (Billing)

```sql
CREATE TYPE billing_cycle_type       AS ENUM ('monthly', 'yearly');
CREATE TYPE subscription_status_type AS ENUM ('trialing', 'active', 'past_due', 'canceled', 'expired');
CREATE TYPE invoice_status_type      AS ENUM ('paid', 'open', 'void', 'uncollectible');
CREATE TYPE usage_resource_type      AS ENUM (
    'publications', 'ai_generations', 'storage_bytes',
    'members', 'social_accounts', 'campaigns',
    'automations', 'webhooks', 'reports'
);
```

---

## Tabela: `plans`

Planos disponíveis na plataforma (Free, Pro, Enterprise).

```sql
CREATE TABLE plans (
    id                      UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    name                    VARCHAR(100)        NOT NULL,
    slug                    VARCHAR(50)         NOT NULL,
    description             TEXT                NULL,
    price_monthly_cents     INTEGER             NOT NULL DEFAULT 0,
    price_yearly_cents      INTEGER             NOT NULL DEFAULT 0,
    currency                VARCHAR(3)          NOT NULL DEFAULT 'BRL',
    limits                  JSONB               NOT NULL DEFAULT '{}',
    features                JSONB               NOT NULL DEFAULT '{}',
    is_active               BOOLEAN             NOT NULL DEFAULT TRUE,
    sort_order              SMALLINT            NOT NULL DEFAULT 0,
    stripe_price_monthly_id VARCHAR(255)        NULL,      -- Stripe Price ID (mensal)
    stripe_price_yearly_id  VARCHAR(255)        NULL,      -- Stripe Price ID (anual)
    created_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_plans_slug UNIQUE (slug),
    CONSTRAINT ck_plans_price_monthly CHECK (price_monthly_cents >= 0),
    CONSTRAINT ck_plans_price_yearly CHECK (price_yearly_cents >= 0)
);

-- Índices
CREATE INDEX idx_plans_active ON plans (sort_order) WHERE is_active = TRUE;
```

### Notas
- `limits` JSONB contém os limites do plano (members, social_accounts, publications_month, etc.).
- `features` JSONB contém flags de features habilitadas.
- Limite `-1` em JSONB significa ilimitado.
- `stripe_price_monthly_id` e `stripe_price_yearly_id` mapeiam para Stripe Price objects.
- Planos são gerenciados exclusivamente por Platform Admins.

---

## Tabela: `subscriptions`

Assinatura ativa de uma organização a um plano.

```sql
CREATE TABLE subscriptions (
    id                          UUID                        PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id             UUID                        NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    plan_id                     UUID                        NOT NULL REFERENCES plans(id) ON DELETE RESTRICT,
    status                      subscription_status_type    NOT NULL DEFAULT 'active',
    billing_cycle               billing_cycle_type          NOT NULL DEFAULT 'monthly',
    current_period_start        TIMESTAMPTZ                 NOT NULL,
    current_period_end          TIMESTAMPTZ                 NOT NULL,
    trial_ends_at               TIMESTAMPTZ                 NULL,
    canceled_at                 TIMESTAMPTZ                 NULL,
    cancel_at_period_end        BOOLEAN                     NOT NULL DEFAULT FALSE,
    cancel_reason               TEXT                        NULL,
    cancel_feedback             VARCHAR(50)                 NULL,
    external_subscription_id    VARCHAR(255)                NULL,      -- Stripe Subscription ID
    external_customer_id        VARCHAR(255)                NULL,      -- Stripe Customer ID
    created_at                  TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_subscriptions_period CHECK (current_period_end > current_period_start)
);

-- Uma subscription ativa por organização
CREATE UNIQUE INDEX uq_subscriptions_org_active
    ON subscriptions (organization_id)
    WHERE status IN ('trialing', 'active', 'past_due');

-- Busca por Stripe IDs
CREATE UNIQUE INDEX uq_subscriptions_external
    ON subscriptions (external_subscription_id)
    WHERE external_subscription_id IS NOT NULL;

-- Busca por status (para jobs de expiração)
CREATE INDEX idx_subscriptions_status
    ON subscriptions (status, current_period_end);

-- Trial expirando (para job de notificação)
CREATE INDEX idx_subscriptions_trial
    ON subscriptions (trial_ends_at)
    WHERE status = 'trialing' AND trial_ends_at IS NOT NULL;

-- Past due (para job de expiração após 7 dias)
CREATE INDEX idx_subscriptions_past_due
    ON subscriptions (updated_at)
    WHERE status = 'past_due';
```

### Relacionamentos
- `N:1` → `organizations`
- `N:1` → `plans`
- `1:N` → `invoices`

### Notas
- Unique index parcial garante exatamente 1 subscription ativa por organização.
- `external_subscription_id` é preenchido após checkout no Stripe.
- Orgs no plano Free têm subscription sem `external_subscription_id`.
- `cancel_feedback` usa enum de motivos para analytics de churn.

---

## Tabela: `invoices`

Faturas geradas pelo Stripe (registros locais sincronizados via webhook).

```sql
CREATE TABLE invoices (
    id                      UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID                NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    subscription_id         UUID                NOT NULL REFERENCES subscriptions(id) ON DELETE CASCADE,
    external_invoice_id     VARCHAR(255)        NOT NULL,       -- Stripe Invoice ID
    amount_cents            INTEGER             NOT NULL,
    currency                VARCHAR(3)          NOT NULL DEFAULT 'BRL',
    status                  invoice_status_type NOT NULL DEFAULT 'open',
    invoice_url             VARCHAR(2000)       NULL,           -- URL do PDF no Stripe
    period_start            TIMESTAMPTZ         NOT NULL,
    period_end              TIMESTAMPTZ         NOT NULL,
    paid_at                 TIMESTAMPTZ         NULL,
    created_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_invoices_amount CHECK (amount_cents >= 0)
);

-- Deduplicação por Stripe Invoice ID
CREATE UNIQUE INDEX uq_invoices_external
    ON invoices (external_invoice_id);

-- Listagem por organização
CREATE INDEX idx_invoices_org
    ON invoices (organization_id, created_at DESC);

-- Filtro por status
CREATE INDEX idx_invoices_org_status
    ON invoices (organization_id, status, created_at DESC);
```

### Notas
- Invoices são criadas via Stripe webhooks (`invoice.paid`, `invoice.payment_failed`).
- `invoice_url` aponta para o PDF da fatura no Stripe Hosted Invoice Page.
- Não armazenamos dados de cartão de crédito (Stripe gerencia PCI compliance).

---

## Tabela: `usage_records`

Registro de consumo de recursos limitados por plano.

```sql
CREATE TABLE usage_records (
    id                  UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    resource_type       usage_resource_type NOT NULL,
    quantity            INTEGER             NOT NULL DEFAULT 0,
    period_start        DATE                NOT NULL,
    period_end          DATE                NOT NULL,
    recorded_at         TIMESTAMPTZ         NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_usage_records_quantity CHECK (quantity >= 0),
    CONSTRAINT ck_usage_records_period CHECK (period_end >= period_start)
);

-- Um registro por recurso por período por organização
CREATE UNIQUE INDEX uq_usage_records_org_resource_period
    ON usage_records (organization_id, resource_type, period_start);

-- Para verificação de limites (query principal)
CREATE INDEX idx_usage_records_lookup
    ON usage_records (organization_id, resource_type, period_start DESC);
```

### Notas
- Contadores mensais (publications, ai_generations, reports) usam `period_start` do mês corrente.
- Contadores absolutos (members, social_accounts, campaigns) são verificados contra contagem real (COUNT query cacheada).
- Storage é contabilizado em tempo real — `usage_records` para storage é atualizado incrementalmente.
- Cache Redis (TTL 60s) evita queries repetitivas para enforcement de limites.

---

## Tabela: `stripe_webhook_events`

Deduplicação e tracking de eventos do Stripe.

```sql
CREATE TABLE stripe_webhook_events (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    stripe_event_id     VARCHAR(255)    NOT NULL,
    event_type          VARCHAR(100)    NOT NULL,
    processed           BOOLEAN         NOT NULL DEFAULT FALSE,
    payload             JSONB           NOT NULL,
    processed_at        TIMESTAMPTZ     NULL,
    error_message       TEXT            NULL,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_stripe_events UNIQUE (stripe_event_id)
);

-- Para cleanup de eventos antigos
CREATE INDEX idx_stripe_events_processed
    ON stripe_webhook_events (created_at)
    WHERE processed = TRUE;
```

### Notas
- Unique constraint em `stripe_event_id` garante idempotência do webhook.
- Eventos são mantidos por 90 dias para debugging.
- `payload` armazena o evento completo do Stripe para auditoria.

---

## ER — Billing & Subscription

```
plans
├── id (PK)
├── name, slug (UNIQUE)
├── price_monthly_cents, price_yearly_cents
├── currency
├── limits (JSONB), features (JSONB)
├── is_active, sort_order
├── stripe_price_monthly_id, stripe_price_yearly_id
│
└──── subscriptions
      ├── id (PK)
      ├── organization_id (FK → organizations, UNIQUE per active)
      ├── plan_id (FK → plans)
      ├── status, billing_cycle
      ├── current_period_start, current_period_end
      ├── trial_ends_at, canceled_at
      ├── external_subscription_id (Stripe)
      ├── external_customer_id (Stripe)
      │
      └──── invoices
            ├── id (PK)
            ├── organization_id (FK → organizations)
            ├── subscription_id (FK → subscriptions)
            ├── external_invoice_id (UNIQUE, Stripe)
            ├── amount_cents, currency
            ├── status, invoice_url
            └── period_start, period_end, paid_at

usage_records
├── id (PK)
├── organization_id (FK → organizations)
├── resource_type
├── quantity
├── period_start, period_end
└── (UNIQUE: org + resource + period)

stripe_webhook_events
├── id (PK)
├── stripe_event_id (UNIQUE)
├── event_type
├── processed, payload
└── processed_at
```
