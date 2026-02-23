# 08 — Client Financial Management

[← Voltar ao índice](00-index.md)

> **Fase:** 2 (Sprint 8)

---

## Tipos ENUM (Client Finance)

```sql
CREATE TYPE client_status_type       AS ENUM ('active', 'inactive', 'archived');
CREATE TYPE contract_type            AS ENUM ('fixed_monthly', 'per_campaign', 'per_post', 'hourly');
CREATE TYPE contract_status_type     AS ENUM ('active', 'paused', 'completed', 'cancelled');
CREATE TYPE client_invoice_status_type AS ENUM ('draft', 'sent', 'paid', 'overdue', 'cancelled');
CREATE TYPE cost_resource_type       AS ENUM ('campaign', 'ai_generation', 'media_storage', 'publication');
CREATE TYPE payment_method_type      AS ENUM ('pix', 'boleto', 'transfer', 'credit_card', 'other');
CREATE TYPE currency_type            AS ENUM ('BRL', 'USD', 'EUR');
```

---

## Tabela: `clients`

Clientes da agência/organização (pessoas/empresas que contratam os serviços).

```sql
CREATE TABLE clients (
    id                  UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(200)        NOT NULL,
    email               VARCHAR(255)        NULL,
    phone               VARCHAR(20)         NULL,
    company_name        VARCHAR(200)        NULL,
    tax_id              VARCHAR(18)         NULL,      -- CPF (11) ou CNPJ (14), armazenado limpo
    billing_address     JSONB               NULL,      -- {street, number, complement, neighborhood, city, state, zip_code, country}
    notes               TEXT                NULL,
    status              client_status_type  NOT NULL DEFAULT 'active',
    created_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ         NULL,
    purge_at            TIMESTAMPTZ         NULL
);

-- Listagem por organização
CREATE INDEX idx_clients_org
    ON clients (organization_id, created_at DESC)
    WHERE deleted_at IS NULL;

-- Filtro por status
CREATE INDEX idx_clients_org_status
    ON clients (organization_id, status)
    WHERE deleted_at IS NULL;

-- Busca por nome/empresa
CREATE INDEX idx_clients_search
    ON clients USING GIN (
        to_tsvector('portuguese', COALESCE(name, '') || ' ' || COALESCE(company_name, '') || ' ' || COALESCE(email, ''))
    )
    WHERE deleted_at IS NULL;

-- Tax ID único por organização
CREATE UNIQUE INDEX uq_clients_org_tax_id
    ON clients (organization_id, tax_id)
    WHERE tax_id IS NOT NULL AND deleted_at IS NULL;

-- Purge
CREATE INDEX idx_clients_purge
    ON clients (purge_at)
    WHERE purge_at IS NOT NULL;
```

### Relacionamentos
- `N:1` → `organizations`
- `1:N` → `client_contracts`
- `1:N` → `client_invoices`
- `1:N` → `cost_allocations`

### Notas
- `tax_id` armazenado sem formatação (apenas dígitos) para consistência.
- Validação de CPF/CNPJ na camada de aplicação (Value Object TaxId).
- `billing_address` como JSONB para flexibilidade (endereços internacionais).
- Full-text search em português para busca por nome, empresa e email.
- Tax ID único por organização (evita cadastro duplicado do mesmo cliente).

---

## Tabela: `client_contracts`

Contratos de prestação de serviço entre a organização e seus clientes.

```sql
CREATE TABLE client_contracts (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id           UUID                    NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(200)            NOT NULL,
    type                contract_type           NOT NULL,
    value_cents         INTEGER                 NOT NULL,
    currency            currency_type           NOT NULL DEFAULT 'BRL',
    starts_at           DATE                    NOT NULL,
    ends_at             DATE                    NULL,
    social_account_ids  UUID[]                  NULL,      -- contas sociais vinculadas
    status              contract_status_type    NOT NULL DEFAULT 'active',
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_contracts_value CHECK (value_cents > 0),
    CONSTRAINT ck_contracts_dates CHECK (ends_at IS NULL OR ends_at > starts_at)
);

-- Listagem por cliente
CREATE INDEX idx_contracts_client
    ON client_contracts (client_id, starts_at DESC);

-- Listagem por organização
CREATE INDEX idx_contracts_org
    ON client_contracts (organization_id, status, starts_at DESC);

-- Contratos ativos (para cálculo de receita)
CREATE INDEX idx_contracts_active
    ON client_contracts (organization_id, starts_at, ends_at)
    WHERE status = 'active';
```

### Notas
- `social_account_ids` como array UUID permite vincular contas sociais ao contrato sem tabela pivot.
- `value_cents` é o valor por unidade do tipo de contrato (mensal, por campanha, por post, por hora).
- Contratos não são excluídos (soft delete via status `cancelled`).

---

## Tabela: `client_invoices`

Faturas de tracking emitidas pela organização para seus clientes.

```sql
CREATE TABLE client_invoices (
    id                  UUID                        PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id           UUID                        NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    contract_id         UUID                        NULL REFERENCES client_contracts(id) ON DELETE SET NULL,
    organization_id     UUID                        NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    reference_month     VARCHAR(7)                  NOT NULL,   -- YYYY-MM
    subtotal_cents      INTEGER                     NOT NULL DEFAULT 0,
    discount_cents      INTEGER                     NOT NULL DEFAULT 0,
    total_cents         INTEGER                     NOT NULL DEFAULT 0,
    currency            currency_type               NOT NULL DEFAULT 'BRL',
    status              client_invoice_status_type  NOT NULL DEFAULT 'draft',
    due_date            DATE                        NOT NULL,
    paid_at             TIMESTAMPTZ                 NULL,
    payment_method      payment_method_type         NULL,
    payment_notes       TEXT                        NULL,
    sent_at             TIMESTAMPTZ                 NULL,
    notes               TEXT                        NULL,
    created_at          TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_client_invoices_subtotal CHECK (subtotal_cents >= 0),
    CONSTRAINT ck_client_invoices_discount CHECK (discount_cents >= 0),
    CONSTRAINT ck_client_invoices_total CHECK (total_cents >= 0)
);

-- Listagem por cliente
CREATE INDEX idx_client_invoices_client
    ON client_invoices (client_id, due_date DESC);

-- Listagem por organização
CREATE INDEX idx_client_invoices_org
    ON client_invoices (organization_id, due_date DESC);

-- Filtro por status
CREATE INDEX idx_client_invoices_org_status
    ON client_invoices (organization_id, status, due_date DESC);

-- Faturas vencidas (para job de marcação como overdue)
CREATE INDEX idx_client_invoices_overdue
    ON client_invoices (due_date)
    WHERE status = 'sent' AND paid_at IS NULL;

-- Referência por mês
CREATE INDEX idx_client_invoices_month
    ON client_invoices (organization_id, reference_month);
```

### Notas
- `reference_month` formato `YYYY-MM` para agrupamento por período.
- Status transitions: `draft` → `sent` → `paid` | `overdue` → `paid` | `cancelled`.
- `payment_method` e `payment_notes` preenchidos ao marcar como pago (registro manual).
- Faturas `draft` são editáveis; `sent`/`paid`/`cancelled` são imutáveis.

---

## Tabela: `client_invoice_items`

Itens individuais de uma fatura.

```sql
CREATE TABLE client_invoice_items (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    client_invoice_id   UUID            NOT NULL REFERENCES client_invoices(id) ON DELETE CASCADE,
    description         VARCHAR(500)    NOT NULL,
    quantity            INTEGER         NOT NULL DEFAULT 1,
    unit_price_cents    INTEGER         NOT NULL,
    total_cents         INTEGER         NOT NULL,   -- quantity × unit_price_cents
    position            SMALLINT        NOT NULL DEFAULT 0,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_invoice_items_quantity CHECK (quantity > 0),
    CONSTRAINT ck_invoice_items_price CHECK (unit_price_cents > 0),
    CONSTRAINT ck_invoice_items_total CHECK (total_cents > 0)
);

-- Itens por fatura (ordenados)
CREATE INDEX idx_invoice_items_invoice
    ON client_invoice_items (client_invoice_id, position);
```

### Notas
- `total_cents` é calculado na aplicação (`quantity × unit_price_cents`) e armazenado para consistência.
- `position` define a ordem dos itens na fatura.

---

## Tabela: `cost_allocations`

Alocação de custos a clientes (para cálculo de lucratividade).

```sql
CREATE TABLE cost_allocations (
    id                  UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    client_id           UUID                NOT NULL REFERENCES clients(id) ON DELETE CASCADE,
    organization_id     UUID                NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    resource_type       cost_resource_type  NOT NULL,
    resource_id         UUID                NULL,       -- FK genérica para o recurso (campaign, media, etc.)
    description         VARCHAR(500)        NOT NULL,
    cost_cents          INTEGER             NOT NULL,
    currency            currency_type       NOT NULL DEFAULT 'BRL',
    allocated_at        TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_cost_allocations_cost CHECK (cost_cents > 0)
);

-- Por cliente
CREATE INDEX idx_cost_allocations_client
    ON cost_allocations (client_id, allocated_at DESC);

-- Por organização
CREATE INDEX idx_cost_allocations_org
    ON cost_allocations (organization_id, allocated_at DESC);

-- Por tipo de recurso
CREATE INDEX idx_cost_allocations_org_type
    ON cost_allocations (organization_id, resource_type, allocated_at DESC);

-- Por período (para relatórios mensais)
CREATE INDEX idx_cost_allocations_period
    ON cost_allocations (organization_id, date_trunc('month', allocated_at));
```

### Notas
- `resource_id` é uma FK genérica — a integridade referencial é gerenciada pela aplicação.
- Alocações são imutáveis (append-only). Para corrigir, criar nova alocação negativa ou excluir.
- Agregação por `resource_type` permite breakdown de custos no relatório de lucratividade.

---

## ER — Client Financial Management

```
clients
├── id (PK)
├── organization_id (FK → organizations)
├── name, email, phone
├── company_name, tax_id (UNIQUE per org)
├── billing_address (JSONB)
├── status, notes
│
├──── client_contracts
│     ├── id (PK)
│     ├── client_id (FK → clients)
│     ├── organization_id (FK → organizations)
│     ├── name, type, value_cents, currency
│     ├── starts_at, ends_at
│     ├── social_account_ids (UUID[])
│     └── status
│
├──── client_invoices
│     ├── id (PK)
│     ├── client_id (FK → clients)
│     ├── contract_id (FK → client_contracts, nullable)
│     ├── organization_id (FK → organizations)
│     ├── reference_month, due_date
│     ├── subtotal_cents, discount_cents, total_cents
│     ├── status, paid_at, payment_method
│     │
│     └──── client_invoice_items
│           ├── id (PK)
│           ├── client_invoice_id (FK)
│           ├── description, quantity
│           ├── unit_price_cents, total_cents
│           └── position
│
└──── cost_allocations
      ├── id (PK)
      ├── client_id (FK → clients)
      ├── organization_id (FK → organizations)
      ├── resource_type, resource_id
      ├── description, cost_cents, currency
      └── allocated_at
```
