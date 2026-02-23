# 09 — Social Listening

[← Voltar ao índice](00-index.md)

> **Fase:** 3 (Sprint 9)

---

## Tipos ENUM (Social Listening)

```sql
CREATE TYPE listening_query_type     AS ENUM ('keyword', 'hashtag', 'mention', 'competitor');
CREATE TYPE alert_condition_type     AS ENUM ('volume_spike', 'negative_sentiment_spike', 'keyword_detected', 'influencer_mention');
CREATE TYPE notification_channel_type AS ENUM ('email', 'webhook', 'in_app');
CREATE TYPE listening_report_status_type AS ENUM ('processing', 'ready', 'expired');
```

---

## Tabela: `listening_queries`

Queries de monitoramento configuradas pela organização.

```sql
CREATE TABLE listening_queries (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                VARCHAR(200)            NOT NULL,
    type                listening_query_type    NOT NULL,
    value               VARCHAR(200)            NOT NULL,   -- keyword, #hashtag, @mention, @competitor
    platforms           social_provider_type[]  NOT NULL,    -- redes a monitorar
    language_filter     language_type           NULL,        -- null = todos os idiomas
    is_active           BOOLEAN                 NOT NULL DEFAULT TRUE,
    last_fetched_at     TIMESTAMPTZ             NULL,
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW()
);

-- Listagem por organização
CREATE INDEX idx_listening_queries_org
    ON listening_queries (organization_id, created_at DESC);

-- Queries ativas (para job de fetch)
CREATE INDEX idx_listening_queries_active
    ON listening_queries (last_fetched_at NULLS FIRST)
    WHERE is_active = TRUE;

-- Evitar duplicatas
CREATE UNIQUE INDEX uq_listening_queries_org_type_value
    ON listening_queries (organization_id, type, LOWER(value));
```

### Notas
- `platforms` usa array de enum para filtrar redes a monitorar.
- `last_fetched_at` usado pelo job scheduler para round-robin (queries mais antigas primeiro).
- Unique index case-insensitive evita queries duplicadas na mesma org.

---

## Tabela: `mentions`

Menções capturadas pelas queries de listening. **Particionada por mês** para performance.

```sql
CREATE TABLE mentions (
    id                      UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    query_id                UUID                NOT NULL REFERENCES listening_queries(id) ON DELETE CASCADE,
    organization_id         UUID                NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    platform                social_provider_type NOT NULL,
    external_id             VARCHAR(255)        NOT NULL,
    author_name             VARCHAR(255)        NOT NULL,
    author_username         VARCHAR(255)        NOT NULL,
    author_external_id      VARCHAR(255)        NOT NULL,
    author_followers_count  INTEGER             NULL,
    content_text            TEXT                NOT NULL,
    content_url             VARCHAR(2000)       NULL,
    media_urls              TEXT[]              NULL,
    sentiment               sentiment_type      NULL,
    sentiment_score         DECIMAL(5,4)        NULL,
    reach_estimate          INTEGER             NULL,
    engagement_count        INTEGER             NULL,       -- likes + comments + shares
    mentioned_at            TIMESTAMPTZ         NOT NULL,
    captured_at             TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    is_read                 BOOLEAN             NOT NULL DEFAULT FALSE,
    flagged                 BOOLEAN             NOT NULL DEFAULT FALSE,
    created_at              TIMESTAMPTZ         NOT NULL DEFAULT NOW()
) PARTITION BY RANGE (mentioned_at);

-- Partições mensais (criadas automaticamente por job ou manualmente)
CREATE TABLE mentions_2026_01 PARTITION OF mentions
    FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');
CREATE TABLE mentions_2026_02 PARTITION OF mentions
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');
CREATE TABLE mentions_2026_03 PARTITION OF mentions
    FOR VALUES FROM ('2026-03-01') TO ('2026-04-01');
-- ... partições criadas mensalmente pelo CreateMonthlyPartitionsJob

-- Deduplicação
CREATE UNIQUE INDEX uq_mentions_external
    ON mentions (platform, external_id);

-- Listagem por organização (inbox)
CREATE INDEX idx_mentions_org_inbox
    ON mentions (organization_id, mentioned_at DESC);

-- Filtro por query
CREATE INDEX idx_mentions_query
    ON mentions (query_id, mentioned_at DESC);

-- Filtro por sentimento
CREATE INDEX idx_mentions_org_sentiment
    ON mentions (organization_id, sentiment, mentioned_at DESC)
    WHERE sentiment IS NOT NULL;

-- Não lidas
CREATE INDEX idx_mentions_org_unread
    ON mentions (organization_id, mentioned_at DESC)
    WHERE is_read = FALSE;

-- Flagged (destaques)
CREATE INDEX idx_mentions_org_flagged
    ON mentions (organization_id, mentioned_at DESC)
    WHERE flagged = TRUE;

-- Para dashboard: contagem por período
CREATE INDEX idx_mentions_org_platform_date
    ON mentions (organization_id, platform, date_trunc('day', mentioned_at));

-- Busca textual
CREATE INDEX idx_mentions_text_search
    ON mentions USING GIN (to_tsvector('portuguese', content_text));

-- Para job de análise de sentimento
CREATE INDEX idx_mentions_pending_sentiment
    ON mentions (captured_at)
    WHERE sentiment IS NULL;
```

### Relacionamentos
- `N:1` → `listening_queries`
- `N:1` → `organizations`

### Notas
- **Particionada por mês** em `mentioned_at` para performance e retenção (high volume).
- `external_id` + `platform` garante deduplicação cross-query.
- `sentiment` é preenchido assincronamente pelo `AnalyzeMentionSentimentJob`.
- `reach_estimate` é o número de seguidores do autor (proxy para alcance potencial).
- Retenção por plano: Free 30d, Creator 90d, Professional 180d, Agency 730d (partições antigas são dropadas).

---

## Tabela: `listening_alerts`

Alertas configuráveis para condições específicas.

```sql
CREATE TABLE listening_alerts (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name                    VARCHAR(200)    NOT NULL,
    query_ids               UUID[]          NOT NULL,       -- queries monitoradas
    condition_type          alert_condition_type NOT NULL,
    condition_threshold     INTEGER         NOT NULL,
    condition_window_minutes INTEGER        NOT NULL DEFAULT 60,
    notification_channels   JSONB           NOT NULL DEFAULT '[]',  -- [{type, target}]
    is_active               BOOLEAN         NOT NULL DEFAULT TRUE,
    last_triggered_at       TIMESTAMPTZ     NULL,
    cooldown_minutes        INTEGER         NOT NULL DEFAULT 60,
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_alerts_threshold CHECK (condition_threshold > 0),
    CONSTRAINT ck_alerts_window CHECK (condition_window_minutes >= 15 AND condition_window_minutes <= 1440),
    CONSTRAINT ck_alerts_cooldown CHECK (cooldown_minutes >= 30 AND cooldown_minutes <= 1440)
);

-- Por organização
CREATE INDEX idx_listening_alerts_org
    ON listening_alerts (organization_id, created_at DESC);

-- Alertas ativos (para job de avaliação)
CREATE INDEX idx_listening_alerts_active
    ON listening_alerts (is_active)
    WHERE is_active = TRUE;
```

### Notas
- `query_ids` como array para vincular múltiplas queries a um alerta.
- `notification_channels` JSONB: `[{"type": "email", "target": "..."}, {"type": "webhook", "target": "..."}]`.
- `cooldown_minutes` evita alertas repetidos (mín 30 min entre triggers).
- Avaliação de condições feita pelo `EvaluateListeningAlertsJob` (cron: a cada 5 min).

---

## Tabela: `listening_alert_triggers`

Histórico de triggers de alertas.

```sql
CREATE TABLE listening_alert_triggers (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    alert_id            UUID            NOT NULL REFERENCES listening_alerts(id) ON DELETE CASCADE,
    organization_id     UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    condition_type      alert_condition_type NOT NULL,
    condition_value     INTEGER         NOT NULL,       -- valor que triggerou (ex: 15 menções negativas)
    condition_threshold INTEGER         NOT NULL,       -- threshold configurado no alerta
    notifications_sent  JSONB           NOT NULL DEFAULT '[]',  -- [{channel, target, success}]
    triggered_at        TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Histórico por alerta
CREATE INDEX idx_alert_triggers_alert
    ON listening_alert_triggers (alert_id, triggered_at DESC);

-- Histórico por organização
CREATE INDEX idx_alert_triggers_org
    ON listening_alert_triggers (organization_id, triggered_at DESC);
```

### Notas
- Registro de cada trigger para auditoria e debug.
- `notifications_sent` registra quais canais foram notificados e se tiveram sucesso.
- Retenção: 6 meses.

---

## Tabela: `listening_reports`

Relatórios exportados de social listening.

```sql
CREATE TABLE listening_reports (
    id                  UUID                        PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                        NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    query_ids           UUID[]                      NOT NULL,
    period_start        TIMESTAMPTZ                 NOT NULL,
    period_end          TIMESTAMPTZ                 NOT NULL,
    total_mentions      INTEGER                     NOT NULL DEFAULT 0,
    sentiment_breakdown JSONB                       NOT NULL DEFAULT '{}',  -- {positive, neutral, negative}
    top_authors         JSONB                       NOT NULL DEFAULT '[]',
    platform_breakdown  JSONB                       NOT NULL DEFAULT '{}',
    trend_data          JSONB                       NOT NULL DEFAULT '[]',
    format              export_format_type          NOT NULL,
    file_path           VARCHAR(500)                NULL,
    status              listening_report_status_type NOT NULL DEFAULT 'processing',
    created_at          TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),
    expires_at          TIMESTAMPTZ                 NULL,

    CONSTRAINT ck_listening_reports_period CHECK (period_end > period_start)
);

-- Por organização
CREATE INDEX idx_listening_reports_org
    ON listening_reports (organization_id, created_at DESC);

-- Para cleanup job
CREATE INDEX idx_listening_reports_expired
    ON listening_reports (expires_at)
    WHERE status = 'ready' AND expires_at IS NOT NULL;
```

### Notas
- Geração assíncrona via `GenerateListeningReportJob`.
- `file_path` preenchido quando o relatório está pronto (presigned URL gerado on-demand).
- Relatórios expiram após 7 dias (`expires_at`).
- Dados de resumo (sentiment_breakdown, top_authors) armazenados em JSONB para acesso rápido sem recalcular.

---

## ER — Social Listening

```
listening_queries
├── id (PK)
├── organization_id (FK → organizations)
├── name, type, value
├── platforms (social_provider_type[])
├── language_filter, is_active
├── last_fetched_at
│
├──── mentions (PARTITIONED BY RANGE mentioned_at)
│     ├── id (PK)
│     ├── query_id (FK → listening_queries)
│     ├── organization_id (FK → organizations)
│     ├── platform, external_id (UNIQUE per platform)
│     ├── author_name, author_username, author_followers_count
│     ├── content_text, content_url, media_urls
│     ├── sentiment, sentiment_score
│     ├── reach_estimate, engagement_count
│     ├── is_read, flagged
│     └── mentioned_at, captured_at
│
└──── (referenced by listening_alerts.query_ids[])

listening_alerts
├── id (PK)
├── organization_id (FK → organizations)
├── name, query_ids (UUID[])
├── condition_type, condition_threshold, condition_window_minutes
├── notification_channels (JSONB)
├── is_active, cooldown_minutes
├── last_triggered_at
│
└──── listening_alert_triggers
      ├── id (PK)
      ├── alert_id (FK → listening_alerts)
      ├── organization_id (FK → organizations)
      ├── condition_type, condition_value, condition_threshold
      ├── notifications_sent (JSONB)
      └── triggered_at

listening_reports
├── id (PK)
├── organization_id (FK → organizations)
├── query_ids (UUID[])
├── period_start, period_end
├── total_mentions, sentiment_breakdown (JSONB)
├── top_authors, platform_breakdown, trend_data
├── format, file_path, status
└── expires_at
```
