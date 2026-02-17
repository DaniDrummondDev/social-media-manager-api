# 02 — Campaign, Content & Media

[← Voltar ao índice](00-index.md)

---

## Tabela: `campaigns`

Campanhas que agrupam peças de conteúdo.

```sql
CREATE TABLE campaigns (
    id              UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID                    NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(100)            NOT NULL,
    description     TEXT                    NULL,
    starts_at       TIMESTAMPTZ             NULL,
    ends_at         TIMESTAMPTZ             NULL,
    status          campaign_status_type    NOT NULL DEFAULT 'draft',
    tags            TEXT[]                  NOT NULL DEFAULT '{}',
    created_at      TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ             NULL,
    purge_at        TIMESTAMPTZ             NULL,

    CONSTRAINT ck_campaigns_dates CHECK (
        starts_at IS NULL OR ends_at IS NULL OR ends_at > starts_at
    )
);

-- Nome único por usuário (apenas entre não-deletados)
CREATE UNIQUE INDEX uq_campaigns_user_name
    ON campaigns (user_id, LOWER(name))
    WHERE deleted_at IS NULL;

-- Índices
CREATE INDEX idx_campaigns_user_status
    ON campaigns (user_id, status, created_at DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_campaigns_user_dates
    ON campaigns (user_id, starts_at, ends_at)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_campaigns_tags
    ON campaigns USING GIN (tags)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_campaigns_purge
    ON campaigns (purge_at)
    WHERE purge_at IS NOT NULL;
```

### Relacionamentos
- `N:1` → `users`
- `1:N` → `contents`

### Notas
- `LOWER(name)` no unique index garante unicidade case-insensitive.
- `tags` usa array nativo + GIN index para busca eficiente por tags.
- Check constraint `ck_campaigns_dates` valida `ends_at > starts_at` quando ambos são informados.

---

## Tabela: `contents`

Peças de conteúdo pertencentes a uma campanha.

```sql
CREATE TABLE contents (
    id                  UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    campaign_id         UUID                NOT NULL REFERENCES campaigns(id) ON DELETE CASCADE,
    user_id             UUID                NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title               VARCHAR(500)        NULL,
    body                TEXT                NULL,
    hashtags            TEXT[]              NOT NULL DEFAULT '{}',
    status              content_status_type NOT NULL DEFAULT 'draft',
    ai_generation_id    UUID                NULL,  -- ref para ai_generations (sem FK rígida)
    created_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ         NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ         NULL,
    purge_at            TIMESTAMPTZ         NULL,

    -- Embedding para busca semântica (pgvector)
    embedding           VECTOR(1536)        NULL
);

-- Índices
CREATE INDEX idx_contents_campaign
    ON contents (campaign_id, status, created_at DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_contents_user
    ON contents (user_id, created_at DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_contents_status
    ON contents (status)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_contents_hashtags
    ON contents USING GIN (hashtags)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_contents_purge
    ON contents (purge_at)
    WHERE purge_at IS NOT NULL;

-- Índice vetorial para busca semântica
CREATE INDEX idx_contents_embedding
    ON contents USING ivfflat (embedding vector_cosine_ops)
    WITH (lists = 100)
    WHERE embedding IS NOT NULL AND deleted_at IS NULL;
```

### Relacionamentos
- `N:1` → `campaigns`
- `N:1` → `users`
- `1:N` → `content_network_overrides`
- `N:N` → `media` (via `content_media`)
- `1:N` → `scheduled_posts`
- `1:N` → `content_metrics`
- `1:N` → `comments`

### Notas
- `embedding` é preenchido via OpenAI embeddings quando o conteúdo é criado/atualizado.
- `ai_generation_id` é uma referência lógica (não FK rígida) para permitir exclusão independente de gerações.
- `hashtags` é um array de strings sem o caractere `#` (adicionado na apresentação).

---

## Tabela: `content_network_overrides`

Customizações de conteúdo por rede social.

```sql
CREATE TABLE content_network_overrides (
    id              UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    content_id      UUID                    NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    provider        social_provider_type    NOT NULL,
    title           VARCHAR(500)            NULL,
    body            TEXT                    NULL,
    hashtags        TEXT[]                  NULL,
    metadata        JSONB                   NULL,  -- dados extras por rede (ex: cover image para YouTube)
    created_at      TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    -- Um override por rede por conteúdo
    CONSTRAINT uq_content_overrides_content_provider
        UNIQUE (content_id, provider)
);

-- Índice
CREATE INDEX idx_content_overrides_content
    ON content_network_overrides (content_id);
```

### Notas
- Se não houver override para uma rede, o sistema usa o título/body/hashtags padrão do `content`.
- `metadata` permite dados específicos da rede (ex: YouTube category_id, Instagram location_id).

---

## Tabela: `content_media` (Pivot)

Relacionamento N:N entre conteúdos e mídias, com ordenação.

```sql
CREATE TABLE content_media (
    id              UUID    PRIMARY KEY DEFAULT gen_random_uuid(),
    content_id      UUID    NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    media_id        UUID    NOT NULL REFERENCES media(id) ON DELETE RESTRICT,
    position        SMALLINT NOT NULL DEFAULT 0,  -- ordem da mídia (carrossel)
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    -- Cada mídia aparece uma vez por conteúdo
    CONSTRAINT uq_content_media UNIQUE (content_id, media_id),
    -- Posição única por conteúdo
    CONSTRAINT uq_content_media_position UNIQUE (content_id, position)
);

-- Índice
CREATE INDEX idx_content_media_content ON content_media (content_id, position);
CREATE INDEX idx_content_media_media   ON content_media (media_id);
```

### Notas
- `position` define a ordem no carrossel (Instagram) — 0, 1, 2, ...
- `ON DELETE RESTRICT` em `media_id` impede exclusão de mídia vinculada a conteúdo.

---

## Tabela: `media`

Arquivos de mídia (imagens e vídeos).

```sql
CREATE TABLE media (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    file_name           VARCHAR(255)    NOT NULL,  -- nome gerado (UUID.ext)
    original_name       VARCHAR(255)    NOT NULL,  -- nome original do upload
    mime_type           VARCHAR(100)    NOT NULL,
    file_size           BIGINT          NOT NULL,  -- bytes
    width               INTEGER         NULL,      -- pixels (imagem e vídeo)
    height              INTEGER         NULL,      -- pixels (imagem e vídeo)
    duration_seconds    INTEGER         NULL,      -- duração (vídeo)
    storage_path        VARCHAR(1000)   NOT NULL,
    thumbnail_path      VARCHAR(1000)   NULL,
    disk                VARCHAR(50)     NOT NULL DEFAULT 'spaces', -- storage disk
    checksum            VARCHAR(64)     NOT NULL,  -- SHA-256
    scan_status         scan_status_type NOT NULL DEFAULT 'pending',
    scanned_at          TIMESTAMPTZ     NULL,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ     NULL,
    purge_at            TIMESTAMPTZ     NULL,

    CONSTRAINT ck_media_file_size CHECK (file_size > 0),
    CONSTRAINT ck_media_dimensions CHECK (
        (width IS NULL AND height IS NULL) OR (width > 0 AND height > 0)
    )
);

-- Índices
CREATE INDEX idx_media_user
    ON media (user_id, created_at DESC)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_media_user_mime
    ON media (user_id, mime_type)
    WHERE deleted_at IS NULL;

CREATE INDEX idx_media_scan
    ON media (scan_status)
    WHERE scan_status = 'pending';

CREATE INDEX idx_media_purge
    ON media (purge_at)
    WHERE purge_at IS NOT NULL;

CREATE INDEX idx_media_checksum
    ON media (user_id, checksum)
    WHERE deleted_at IS NULL;
```

### Relacionamentos
- `N:1` → `users`
- `N:N` → `contents` (via `content_media`)

### Notas
- `checksum` permite detectar uploads duplicados do mesmo arquivo.
- `scan_status` controla o ciclo de vida do malware scan — mídias `pending` não podem ser usadas em publicação.
- `ON DELETE RESTRICT` no pivot impede exclusão de mídia vinculada a conteúdo ativo.

---

## Tabela: `ai_generations`

Histórico de gerações de conteúdo via IA.

```sql
CREATE TABLE ai_generations (
    id              UUID                PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID                NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type            generation_type     NOT NULL,
    input           JSONB               NOT NULL,  -- parâmetros de entrada
    output          JSONB               NOT NULL,  -- resultado da geração
    model_used      VARCHAR(50)         NOT NULL,  -- 'gpt-4o', 'gpt-4o-mini'
    tokens_input    INTEGER             NOT NULL DEFAULT 0,
    tokens_output   INTEGER             NOT NULL DEFAULT 0,
    cost_estimate   DECIMAL(10,6)       NOT NULL DEFAULT 0,  -- USD
    duration_ms     INTEGER             NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ         NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_ai_generations_user
    ON ai_generations (user_id, created_at DESC);

CREATE INDEX idx_ai_generations_user_type
    ON ai_generations (user_id, type, created_at DESC);

-- Para cálculo de consumo mensal
CREATE INDEX idx_ai_generations_user_month
    ON ai_generations (user_id, date_trunc('month', created_at));
```

### Notas
- `input` e `output` usam JSONB para flexibilidade — cada `type` tem estrutura diferente.
- `cost_estimate` é calculado no momento da geração com base na tabela de preços.
- Não possui soft delete — dados são limpos pelo job de retenção (1 ano).

---

## Tabela: `ai_settings`

Configurações de IA por usuário.

```sql
CREATE TABLE ai_settings (
    user_id                     UUID        PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    default_tone                tone_type   NOT NULL DEFAULT 'professional',
    custom_tone_description     TEXT        NULL,
    default_language            language_type NOT NULL DEFAULT 'pt_BR',
    monthly_generation_limit    INTEGER     NOT NULL DEFAULT 500,
    created_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at                  TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_ai_settings_custom_tone CHECK (
        default_tone != 'custom' OR custom_tone_description IS NOT NULL
    )
);
```

### Notas
- `user_id` é PK e FK (relação 1:1 com users).
- Check constraint garante que `custom_tone_description` é obrigatório quando `default_tone = 'custom'`.

---

## ER — Campaign, Content & Media

```
campaigns
├── id (PK)
├── user_id (FK → users)
├── name (UNIQUE per user)
├── status
│
└──── contents
      ├── id (PK)
      ├── campaign_id (FK → campaigns)
      ├── user_id (FK → users)
      ├── title, body, hashtags
      ├── embedding (vector)
      ├── status
      │
      ├──── content_network_overrides
      │     ├── id (PK)
      │     ├── content_id (FK → contents)
      │     ├── provider
      │     └── title, body, hashtags (override)
      │
      └──── content_media (pivot)
            ├── content_id (FK → contents)
            ├── media_id (FK → media)
            └── position

media
├── id (PK)
├── user_id (FK → users)
├── file_name, original_name
├── mime_type, file_size
├── width, height, duration_seconds
├── storage_path, thumbnail_path
├── checksum (SHA-256)
└── scan_status

ai_generations
├── id (PK)
├── user_id (FK → users)
├── type, input (JSONB), output (JSONB)
├── model_used, tokens_input, tokens_output
└── cost_estimate

ai_settings
├── user_id (PK, FK → users)  -- 1:1
├── default_tone
└── default_language
```
