# Database Design — Social Media Manager

> **Versão:** 1.0.0
> **Data:** 2026-02-15
> **SGBD:** PostgreSQL 16+ com pgvector

---

## Índice

| # | Documento | Tabelas |
|---|-----------|---------|
| 01 | [Identity & Social Account](01-identity-social-account.md) | users, refresh_tokens, password_reset_tokens, login_histories, audit_logs, social_accounts |
| 02 | [Campaign & Media](02-campaign-media.md) | campaigns, contents, content_network_overrides, content_media, media |
| 03 | [Publishing & Analytics](03-publishing-analytics.md) | scheduled_posts, content_metrics, content_metric_snapshots, account_metrics, report_exports |
| 04 | [Engagement & Automation](04-engagement-automation.md) | comments, automation_rules, automation_rule_conditions, automation_executions, webhook_endpoints, webhook_deliveries |
| 05 | [Índices & Performance](05-indexes-performance.md) | Estratégia de índices, particionamento, views materializadas |

---

## Convenções

### Naming

| Elemento | Convenção | Exemplo |
|----------|-----------|---------|
| Tabelas | snake_case, plural | `social_accounts` |
| Colunas | snake_case, singular | `created_at` |
| Primary keys | `id` (UUID) | `id UUID PRIMARY KEY` |
| Foreign keys | `{tabela_singular}_id` | `user_id`, `campaign_id` |
| Índices | `idx_{tabela}_{colunas}` | `idx_campaigns_user_id_status` |
| Unique constraints | `uq_{tabela}_{colunas}` | `uq_users_email` |
| Check constraints | `ck_{tabela}_{regra}` | `ck_campaigns_dates` |
| Enums (type) | `{nome}_type` | `campaign_status_type` |

### Colunas padrão

Toda tabela possui:

```sql
id          UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
created_at  TIMESTAMPTZ NOT NULL    DEFAULT NOW(),
updated_at  TIMESTAMPTZ NOT NULL    DEFAULT NOW()
```

Tabelas com soft delete adicionam:

```sql
deleted_at  TIMESTAMPTZ NULL,
purge_at    TIMESTAMPTZ NULL
```

### Tipos de dados

| Uso | Tipo PostgreSQL | Justificativa |
|-----|----------------|---------------|
| Identificadores | `UUID` | Não sequencial, seguro para exposição em API |
| Timestamps | `TIMESTAMPTZ` | Sempre com timezone (armazenado em UTC) |
| Texto curto | `VARCHAR(N)` | Com limite explícito |
| Texto longo | `TEXT` | Descrições, conteúdo de posts |
| JSON flexível | `JSONB` | Metadados, overrides, condições |
| Dinheiro | `DECIMAL(10,4)` | Custos de IA |
| Contadores | `INTEGER` | Métricas, tentativas |
| Contadores grandes | `BIGINT` | Views, impressions |
| Booleanos | `BOOLEAN` | Flags |
| Enums | `CREATE TYPE ... AS ENUM` | Status, providers |
| Vetores IA | `VECTOR(1536)` | Embeddings (pgvector) |
| Dados criptografados | `TEXT` | Tokens criptografados (base64) |

### Soft Delete

Tabelas com soft delete usam **índice parcial** para excluir registros deletados das queries normais:

```sql
CREATE INDEX idx_campaigns_active
    ON campaigns (user_id, created_at DESC)
    WHERE deleted_at IS NULL;
```

---

## Diagrama ER (Visão Geral)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          IDENTITY & ACCESS                               │
│                                                                          │
│  ┌──────────┐    ┌─────────────────┐    ┌──────────────────────┐        │
│  │  users   │───▶│ refresh_tokens  │    │ password_reset_tokens│        │
│  │          │───▶│                 │    │                      │        │
│  │          │───▶│ login_histories │    └──────────────────────┘        │
│  │          │───▶│                 │                                     │
│  │          │───▶│ audit_logs      │                                     │
│  └────┬─────┘    └─────────────────┘                                    │
│       │                                                                  │
└───────┼──────────────────────────────────────────────────────────────────┘
        │
        │  1:N
        ▼
┌───────────────────────────────────────────┐
│          SOCIAL ACCOUNT                    │
│                                           │
│  ┌──────────────────┐                     │
│  │ social_accounts  │                     │
│  └────────┬─────────┘                     │
│           │                               │
└───────────┼───────────────────────────────┘
            │
    ┌───────┼──────────────────────────────────────┐
    │       │              │                        │
    ▼       ▼              ▼                        ▼
┌────────────────┐  ┌──────────────┐  ┌──────────────────────┐
│   PUBLISHING   │  │  ANALYTICS   │  │    ENGAGEMENT        │
│                │  │              │  │                      │
│ ┌────────────┐ │  │ ┌──────────┐ │  │ ┌──────────┐        │
│ │ scheduled  │ │  │ │ content  │ │  │ │ comments │        │
│ │ _posts     │ │  │ │ _metrics │ │  │ └────┬─────┘        │
│ └─────┬──────┘ │  │ ├──────────┤ │  │      │              │
│       │        │  │ │ account  │ │  │      ▼              │
│       │        │  │ │ _metrics │ │  │ ┌──────────────┐    │
│       │        │  │ ├──────────┤ │  │ │ automation   │    │
│       │        │  │ │ report   │ │  │ │ _rules       │    │
│       │        │  │ │ _exports │ │  │ ├──────────────┤    │
│       │        │  │ └──────────┘ │  │ │ webhook      │    │
│       │        │  │              │  │ │ _endpoints   │    │
└───────┼────────┘  └──────────────┘  │ ├──────────────┤    │
        │                              │ │ webhook      │    │
        │                              │ │ _deliveries  │    │
        ▼                              └──────────────────────┘
┌────────────────────────────────────────────┐
│          CAMPAIGN & MEDIA                   │
│                                            │
│  ┌────────────┐     ┌───────────┐          │
│  │ campaigns  │────▶│ contents  │          │
│  └────────────┘     └─────┬─────┘          │
│                           │                │
│                    ┌──────┼──────┐          │
│                    ▼      │      ▼          │
│  ┌──────────────────┐  ┌──────────────┐    │
│  │content_network   │  │content_media │    │
│  │_overrides        │  │(pivot)       │    │
│  └──────────────────┘  └──────┬───────┘    │
│                               │            │
│                               ▼            │
│                        ┌──────────┐        │
│                        │  media   │        │
│                        └──────────┘        │
└────────────────────────────────────────────┘
```

---

## Extensões necessárias

```sql
-- UUIDs nativos
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Busca vetorial (IA)
CREATE EXTENSION IF NOT EXISTS "vector";

-- Trigram para busca parcial (ILIKE otimizado)
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
```

---

## Tipos ENUM

```sql
CREATE TYPE user_status_type       AS ENUM ('active', 'inactive', 'suspended');
CREATE TYPE social_provider_type   AS ENUM ('instagram', 'tiktok', 'youtube');
CREATE TYPE connection_status_type AS ENUM ('connected', 'expired', 'revoked', 'error');
CREATE TYPE campaign_status_type   AS ENUM ('draft', 'active', 'paused', 'completed');
CREATE TYPE content_status_type    AS ENUM ('draft', 'scheduled', 'publishing', 'published', 'failed', 'cancelled');
CREATE TYPE post_status_type       AS ENUM ('pending', 'dispatched', 'publishing', 'published', 'failed', 'cancelled');
CREATE TYPE generation_type        AS ENUM ('title', 'description', 'hashtags', 'full');
CREATE TYPE tone_type              AS ENUM ('professional', 'casual', 'fun', 'informative', 'inspirational', 'custom');
CREATE TYPE language_type          AS ENUM ('pt_BR', 'en_US', 'es_ES');
CREATE TYPE sentiment_type         AS ENUM ('positive', 'neutral', 'negative');
CREATE TYPE automation_action_type AS ENUM ('reply_fixed', 'reply_template', 'reply_ai', 'send_webhook');
CREATE TYPE export_format_type     AS ENUM ('pdf', 'csv');
CREATE TYPE export_status_type     AS ENUM ('processing', 'ready', 'expired');
CREATE TYPE scan_status_type       AS ENUM ('pending', 'clean', 'infected');
CREATE TYPE report_type            AS ENUM ('overview', 'network', 'content');
```

---

## Resumo de tabelas

| Bounded Context | Tabela | Estimativa de volume |
|----------------|--------|---------------------|
| Identity | `users` | ~100K registros |
| Identity | `refresh_tokens` | ~500K (múltiplas sessões) |
| Identity | `password_reset_tokens` | ~10K (efêmero) |
| Identity | `login_histories` | ~5M/ano |
| Identity | `audit_logs` | ~10M/ano |
| Social Account | `social_accounts` | ~300K (3 redes × users) |
| Campaign | `campaigns` | ~500K |
| Campaign | `contents` | ~2M |
| Campaign | `content_network_overrides` | ~5M |
| Campaign | `content_media` | ~3M (pivot) |
| Media | `media` | ~2M |
| Content AI | `ai_generations` | ~10M/ano |
| Content AI | `ai_settings` | ~100K (1 por user) |
| Publishing | `scheduled_posts` | ~5M/ano |
| Analytics | `content_metrics` | ~5M |
| Analytics | `content_metric_snapshots` | ~50M/ano (particionada) |
| Analytics | `account_metrics` | ~10M/ano (particionada) |
| Analytics | `report_exports` | ~100K |
| Engagement | `comments` | ~50M/ano |
| Engagement | `automation_rules` | ~200K |
| Engagement | `automation_rule_conditions` | ~500K |
| Engagement | `automation_executions` | ~20M/ano |
| Engagement | `webhook_endpoints` | ~50K |
| Engagement | `webhook_deliveries` | ~10M/ano |
