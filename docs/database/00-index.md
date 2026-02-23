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
| 04 | [Engagement & Automation](04-engagement-automation.md) | comments, automation_rules, automation_rule_conditions, automation_executions, webhook_endpoints, webhook_deliveries, crm_connections, crm_field_mappings, crm_sync_logs *(Fase 4)* |
| 05 | [Índices & Performance](05-indexes-performance.md) | Estratégia de índices, particionamento, views materializadas |
| 06 | [Billing & Subscription](06-billing-subscription.md) | plans, subscriptions, invoices, usage_records, stripe_webhook_events |
| 07 | [Platform Administration](07-platform-administration.md) | platform_admins, system_configs, admin_audit_entries, platform_metrics_cache |
| 08 | [Client Financial Management](08-client-financial-management.md) | clients, client_contracts, client_invoices, client_invoice_items, cost_allocations *(Fase 2)* |
| 09 | [Social Listening](09-social-listening.md) | listening_queries, mentions (partitioned), listening_alerts, listening_alert_triggers, listening_reports *(Fase 2)* |
| 10 | [AI Intelligence](10-ai-intelligence.md) | embedding_jobs, content_profiles, performance_predictions, posting_time_recommendations, audience_insights, ai_generation_context, brand_safety_checks, brand_safety_rules, calendar_suggestions, content_gap_analyses, generation_feedback, prompt_templates, prompt_experiments, prediction_validations, org_style_profiles, crm_conversion_attributions *(Fase 2-3-4 + Learning Loop + CRM Intelligence)* |

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

-- Billing & Subscription (doc 06)
CREATE TYPE billing_cycle_type       AS ENUM ('monthly', 'yearly');
CREATE TYPE subscription_status_type AS ENUM ('trialing', 'active', 'past_due', 'canceled', 'expired');
CREATE TYPE invoice_status_type      AS ENUM ('paid', 'open', 'void', 'uncollectible');
CREATE TYPE usage_resource_type      AS ENUM ('publications', 'ai_generations', 'storage_bytes', 'members', 'social_accounts', 'campaigns', 'automations', 'webhooks', 'reports');

-- Platform Administration (doc 07)
CREATE TYPE platform_admin_role_type AS ENUM ('super_admin', 'admin', 'support');

-- Client Financial Management (doc 08 — Fase 2)
CREATE TYPE client_status_type           AS ENUM ('active', 'inactive', 'archived');
CREATE TYPE contract_type                AS ENUM ('fixed_monthly', 'per_campaign', 'per_post', 'hourly');
CREATE TYPE contract_status_type         AS ENUM ('active', 'paused', 'completed', 'cancelled');
CREATE TYPE client_invoice_status_type   AS ENUM ('draft', 'sent', 'paid', 'overdue', 'cancelled');
CREATE TYPE cost_resource_type           AS ENUM ('campaign', 'ai_generation', 'media_storage', 'publication');
CREATE TYPE payment_method_type          AS ENUM ('pix', 'boleto', 'transfer', 'credit_card', 'other');
CREATE TYPE currency_type                AS ENUM ('BRL', 'USD', 'EUR');

-- Social Listening (doc 09 — Fase 2)
CREATE TYPE listening_query_type          AS ENUM ('keyword', 'hashtag', 'mention', 'competitor');
CREATE TYPE alert_condition_type          AS ENUM ('volume_spike', 'negative_sentiment_spike', 'keyword_detected', 'influencer_mention');
CREATE TYPE notification_channel_type     AS ENUM ('email', 'webhook', 'in_app');
CREATE TYPE listening_report_status_type  AS ENUM ('processing', 'ready', 'expired');

-- AI Intelligence (doc 10 — Fase 2-3)
CREATE TYPE safety_check_status_type      AS ENUM ('pending', 'passed', 'warning', 'blocked');

-- AI Learning & Feedback Loop (doc 10 — ADR-017)
CREATE TYPE feedback_action_type          AS ENUM ('accepted', 'edited', 'rejected');
CREATE TYPE experiment_status_type        AS ENUM ('draft', 'running', 'completed', 'canceled');

-- CRM Connectors (doc 04 — ADR-018)
CREATE TYPE crm_provider_type             AS ENUM ('hubspot', 'rdstation', 'pipedrive', 'salesforce', 'activecampaign');

-- Alteração em ENUM existente (doc 10)
ALTER TYPE generation_type ADD VALUE 'cross_network_adaptation';
ALTER TYPE generation_type ADD VALUE 'calendar_planning';
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
| Billing | `plans` | ~10 (estático) |
| Billing | `subscriptions` | ~100K (1 por org) |
| Billing | `invoices` | ~1M/ano |
| Billing | `usage_records` | ~1M/ano |
| Billing | `stripe_webhook_events` | ~2M/ano |
| Administration | `platform_admins` | ~50 (estático) |
| Administration | `system_configs` | ~20 (estático) |
| Administration | `admin_audit_entries` | ~100K/ano |
| Administration | `platform_metrics_cache` | ~10 (estático) |
| Client Finance | `clients` | ~500K *(Fase 2)* |
| Client Finance | `client_contracts` | ~1M *(Fase 2)* |
| Client Finance | `client_invoices` | ~5M/ano *(Fase 2)* |
| Client Finance | `client_invoice_items` | ~15M/ano *(Fase 2)* |
| Client Finance | `cost_allocations` | ~10M/ano *(Fase 2)* |
| Social Listening | `listening_queries` | ~200K *(Fase 2)* |
| Social Listening | `mentions` | ~100M/ano (particionada) *(Fase 2)* |
| Social Listening | `listening_alerts` | ~100K *(Fase 2)* |
| Social Listening | `listening_alert_triggers` | ~500K/ano *(Fase 2)* |
| Social Listening | `listening_reports` | ~50K *(Fase 2)* |
| AI Intelligence | `embedding_jobs` | ~10M/ano *(Fase 2-3)* |
| AI Intelligence | `content_profiles` | ~300K *(Fase 3)* |
| AI Intelligence | `performance_predictions` | ~5M/ano *(Fase 3)* |
| AI Intelligence | `posting_time_recommendations` | ~200K *(Fase 2)* |
| AI Intelligence | `audience_insights` | ~500K *(Fase 3)* |
| AI Intelligence | `ai_generation_context` | ~100K *(Fase 3)* |
| AI Intelligence | `brand_safety_checks` | ~5M/ano *(Fase 2)* |
| AI Intelligence | `brand_safety_rules` | ~50K *(Fase 2)* |
| AI Intelligence | `calendar_suggestions` | ~200K *(Fase 2)* |
| AI Intelligence | `content_gap_analyses` | ~100K *(Fase 3)* |
| AI Intelligence | `generation_feedback` | ~10M/ano *(Learning Loop)* |
| AI Intelligence | `prompt_templates` | ~10K *(Learning Loop)* |
| AI Intelligence | `prompt_experiments` | ~5K *(Learning Loop)* |
| AI Intelligence | `prediction_validations` | ~5M/ano *(Learning Loop)* |
| AI Intelligence | `org_style_profiles` | ~100K *(Learning Loop)* |
| AI Intelligence | `crm_conversion_attributions` | ~2M/ano *(CRM Intelligence N6)* |
| Engagement | `crm_connections` | ~200K *(Fase 4)* |
| Engagement | `crm_field_mappings` | ~1M *(Fase 4)* |
| Engagement | `crm_sync_logs` | ~20M/ano *(Fase 4)* |
