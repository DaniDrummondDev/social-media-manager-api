# 03 — Publishing & Analytics

[← Voltar ao índice](00-index.md)

---

## Tabela: `scheduled_posts`

Agendamentos de publicação — um registro por conteúdo × rede social.

```sql
CREATE TABLE scheduled_posts (
    id                  UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    content_id          UUID                NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    social_account_id   UUID                NOT NULL REFERENCES social_accounts(id) ON DELETE CASCADE,
    user_id             UUID                NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    scheduled_at        TIMESTAMPTZ         NOT NULL,
    published_at        TIMESTAMPTZ         NULL,
    status              post_status_type    NOT NULL DEFAULT 'pending',
    external_post_id    VARCHAR(255)        NULL,  -- ID do post na rede social
    external_post_url   VARCHAR(1000)       NULL,
    attempts            SMALLINT            NOT NULL DEFAULT 0,
    max_attempts        SMALLINT            NOT NULL DEFAULT 3,
    last_attempted_at   TIMESTAMPTZ         NULL,
    last_error_code     VARCHAR(50)         NULL,
    last_error_message  TEXT                NULL,
    last_error_is_permanent BOOLEAN         NULL,
    next_retry_at       TIMESTAMPTZ         NULL,
    dispatched_at       TIMESTAMPTZ         NULL,  -- quando foi enviado para a fila
    created_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_scheduled_posts_attempts CHECK (attempts >= 0 AND attempts <= max_attempts),
    CONSTRAINT ck_scheduled_posts_published CHECK (
        (status != 'published') OR (published_at IS NOT NULL AND external_post_id IS NOT NULL)
    )
);

-- Índice principal: buscar posts pendentes para dispatch
CREATE INDEX idx_scheduled_posts_due
    ON scheduled_posts (scheduled_at)
    WHERE status = 'pending';

-- Índice para retries pendentes
CREATE INDEX idx_scheduled_posts_retry
    ON scheduled_posts (next_retry_at)
    WHERE status = 'failed' AND next_retry_at IS NOT NULL
    AND last_error_is_permanent = FALSE;

-- Listagem por usuário
CREATE INDEX idx_scheduled_posts_user
    ON scheduled_posts (user_id, scheduled_at DESC);

-- Listagem por conteúdo
CREATE INDEX idx_scheduled_posts_content
    ON scheduled_posts (content_id, status);

-- Listagem por conta social
CREATE INDEX idx_scheduled_posts_account
    ON scheduled_posts (social_account_id, scheduled_at DESC);

-- Calendário: busca por período
CREATE INDEX idx_scheduled_posts_calendar
    ON scheduled_posts (user_id, scheduled_at)
    WHERE status IN ('pending', 'dispatched', 'publishing', 'published');

-- Para verificar limite diário por conta
CREATE INDEX idx_scheduled_posts_daily
    ON scheduled_posts (social_account_id, date_trunc('day', scheduled_at))
    WHERE status IN ('pending', 'dispatched', 'publishing', 'published');

-- Lock de publicação (evitar duplicação)
CREATE UNIQUE INDEX uq_scheduled_posts_publishing
    ON scheduled_posts (id)
    WHERE status = 'publishing';
```

### Relacionamentos
- `N:1` → `contents`
- `N:1` → `social_accounts`
- `N:1` → `users`

### Notas
- Um conteúdo agendado para 3 redes gera 3 registros em `scheduled_posts`.
- `dispatched_at` marca quando o job foi enviado à fila (evita re-dispatch).
- `status = 'publishing'` + lock Redis evita publicação duplicada.
- Check constraint `ck_scheduled_posts_published` garante consistência de dados quando publicado.

### Status transitions

```
pending → dispatched → publishing → published
pending → cancelled
dispatched → publishing → failed → (retry) → publishing
```

---

## Tabela: `content_metrics`

Métricas de conteúdo por rede social (dados mais recentes).

```sql
CREATE TABLE content_metrics (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    content_id          UUID                    NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    social_account_id   UUID                    NOT NULL REFERENCES social_accounts(id) ON DELETE CASCADE,
    provider            social_provider_type    NOT NULL,
    external_post_id    VARCHAR(255)            NOT NULL,
    impressions         BIGINT                  NOT NULL DEFAULT 0,
    reach               BIGINT                  NOT NULL DEFAULT 0,
    likes               INTEGER                 NOT NULL DEFAULT 0,
    comments            INTEGER                 NOT NULL DEFAULT 0,
    shares              INTEGER                 NOT NULL DEFAULT 0,
    saves               INTEGER                 NOT NULL DEFAULT 0,
    clicks              INTEGER                 NOT NULL DEFAULT 0,
    views               BIGINT                  NULL,      -- vídeo
    watch_time_seconds  BIGINT                  NULL,      -- vídeo
    engagement_rate     DECIMAL(8,4)            NOT NULL DEFAULT 0,
    synced_at           TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    -- Um registro de métricas por conteúdo por conta
    CONSTRAINT uq_content_metrics_content_account
        UNIQUE (content_id, social_account_id)
);

-- Índices
CREATE INDEX idx_content_metrics_content
    ON content_metrics (content_id);

CREATE INDEX idx_content_metrics_account
    ON content_metrics (social_account_id, synced_at DESC);

CREATE INDEX idx_content_metrics_provider
    ON content_metrics (provider, synced_at DESC);

-- Para top conteúdos por engajamento
CREATE INDEX idx_content_metrics_engagement
    ON content_metrics (social_account_id, engagement_rate DESC);

-- Para sync job: buscar métricas que precisam atualizar
CREATE INDEX idx_content_metrics_sync
    ON content_metrics (synced_at)
    WHERE synced_at < NOW() - INTERVAL '1 hour';
```

### Notas
- Contém os valores **mais recentes** de métricas.
- Snapshots históricos ficam em `content_metric_snapshots`.
- `engagement_rate` é pré-calculado na sincronização para evitar cálculo em queries.

---

## Tabela: `content_metric_snapshots`

Séries temporais de métricas para análise de evolução.

```sql
CREATE TABLE content_metric_snapshots (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    content_metrics_id  UUID                    NOT NULL REFERENCES content_metrics(id) ON DELETE CASCADE,
    impressions         BIGINT                  NOT NULL DEFAULT 0,
    reach               BIGINT                  NOT NULL DEFAULT 0,
    likes               INTEGER                 NOT NULL DEFAULT 0,
    comments            INTEGER                 NOT NULL DEFAULT 0,
    shares              INTEGER                 NOT NULL DEFAULT 0,
    saves               INTEGER                 NOT NULL DEFAULT 0,
    clicks              INTEGER                 NOT NULL DEFAULT 0,
    views               BIGINT                  NULL,
    watch_time_seconds  BIGINT                  NULL,
    engagement_rate     DECIMAL(8,4)            NOT NULL DEFAULT 0,
    captured_at         TIMESTAMPTZ             NOT NULL DEFAULT NOW()
) PARTITION BY RANGE (captured_at);

-- Partições mensais (criar automaticamente via job)
CREATE TABLE content_metric_snapshots_2026_01
    PARTITION OF content_metric_snapshots
    FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');

CREATE TABLE content_metric_snapshots_2026_02
    PARTITION OF content_metric_snapshots
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');

-- Índices (criados em cada partição automaticamente)
CREATE INDEX idx_metric_snapshots_metrics
    ON content_metric_snapshots (content_metrics_id, captured_at DESC);

CREATE INDEX idx_metric_snapshots_captured
    ON content_metric_snapshots (captured_at DESC);
```

### Notas
- **Particionada por mês** para performance com alto volume (estimativa: 50M registros/ano).
- Cada sync cria um novo snapshot, permitindo construir gráficos de evolução.
- Partições antigas (> 2 anos) podem ser arquivadas ou excluídas.
- Job mensal cria partições futuras automaticamente (3 meses à frente).

---

## Tabela: `account_metrics`

Métricas de conta por dia (seguidores, alcance, impressões).

```sql
CREATE TABLE account_metrics (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    social_account_id   UUID                    NOT NULL REFERENCES social_accounts(id) ON DELETE CASCADE,
    date                DATE                    NOT NULL,
    followers_count     INTEGER                 NOT NULL DEFAULT 0,
    followers_gained    INTEGER                 NOT NULL DEFAULT 0,
    followers_lost      INTEGER                 NOT NULL DEFAULT 0,
    profile_views       INTEGER                 NULL,
    reach               BIGINT                  NULL,
    impressions         BIGINT                  NULL,
    synced_at           TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    -- Um registro por conta por dia
    CONSTRAINT uq_account_metrics_account_date
        UNIQUE (social_account_id, date)
) PARTITION BY RANGE (date);

-- Partições mensais
CREATE TABLE account_metrics_2026_01
    PARTITION OF account_metrics
    FOR VALUES FROM ('2026-01-01') TO ('2026-02-01');

CREATE TABLE account_metrics_2026_02
    PARTITION OF account_metrics
    FOR VALUES FROM ('2026-02-01') TO ('2026-03-01');

-- Índices
CREATE INDEX idx_account_metrics_account
    ON account_metrics (social_account_id, date DESC);

CREATE INDEX idx_account_metrics_date
    ON account_metrics (date DESC);
```

### Notas
- **Particionada por mês** (estimativa: 10M registros/ano).
- Um registro por conta por dia — cria ou atualiza (upsert).
- `followers_gained` e `followers_lost` são calculados comparando com o dia anterior.

---

## Tabela: `report_exports`

Solicitações de exportação de relatórios.

```sql
CREATE TABLE report_exports (
    id              UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID                NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type            report_type         NOT NULL,
    format          export_format_type  NOT NULL,
    filters         JSONB               NOT NULL DEFAULT '{}',
    status          export_status_type  NOT NULL DEFAULT 'processing',
    file_path       VARCHAR(1000)       NULL,
    file_size       BIGINT              NULL,
    error_message   TEXT                NULL,
    expires_at      TIMESTAMPTZ         NULL,  -- link válido por 24h
    completed_at    TIMESTAMPTZ         NULL,
    created_at      TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ         NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_report_exports_user
    ON report_exports (user_id, created_at DESC);

CREATE INDEX idx_report_exports_status
    ON report_exports (status)
    WHERE status = 'processing';

CREATE INDEX idx_report_exports_expires
    ON report_exports (expires_at)
    WHERE expires_at IS NOT NULL AND status = 'ready';
```

### Notas
- `filters` armazena os filtros usados (período, rede, campanha) em JSONB.
- `expires_at` define quando o link de download expira (24h após geração).
- Job de limpeza remove arquivos expirados do storage.

---

## ER — Publishing & Analytics

```
scheduled_posts
├── id (PK)
├── content_id (FK → contents)
├── social_account_id (FK → social_accounts)
├── user_id (FK → users)
├── scheduled_at
├── published_at
├── status
├── external_post_id
├── attempts, max_attempts
└── last_error_code, last_error_message

content_metrics
├── id (PK)
├── content_id (FK → contents)
├── social_account_id (FK → social_accounts)
├── provider
├── impressions, reach, likes, comments, shares, saves
├── engagement_rate
│
└──── content_metric_snapshots (PARTITIONED by month)
      ├── id (PK)
      ├── content_metrics_id (FK → content_metrics)
      ├── [same metric columns]
      └── captured_at (partition key)

account_metrics (PARTITIONED by month)
├── id (PK)
├── social_account_id (FK → social_accounts)
├── date (partition key)
├── followers_count, followers_gained, followers_lost
└── profile_views, reach, impressions

report_exports
├── id (PK)
├── user_id (FK → users)
├── type, format, filters (JSONB)
├── status, file_path
└── expires_at
```
