# 04 — Engagement & Automation

[← Voltar ao índice](00-index.md)

---

## Tabela: `comments`

Comentários capturados das redes sociais.

```sql
CREATE TABLE comments (
    id                      UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    content_id              UUID                    NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    social_account_id       UUID                    NOT NULL REFERENCES social_accounts(id) ON DELETE CASCADE,
    user_id                 UUID                    NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    external_comment_id     VARCHAR(255)            NOT NULL,
    author_name             VARCHAR(255)            NOT NULL,
    author_external_id      VARCHAR(255)            NOT NULL,
    author_profile_url      VARCHAR(1000)           NULL,
    text                    TEXT                    NOT NULL,
    sentiment               sentiment_type          NULL,      -- classificado via IA
    sentiment_score         DECIMAL(5,4)            NULL,      -- 0.0000 - 1.0000
    is_read                 BOOLEAN                 NOT NULL DEFAULT FALSE,
    is_from_owner           BOOLEAN                 NOT NULL DEFAULT FALSE,  -- comentário do próprio dono da conta
    replied_at              TIMESTAMPTZ             NULL,
    replied_by              UUID                    NULL REFERENCES users(id) ON DELETE SET NULL,
    replied_by_automation   BOOLEAN                 NOT NULL DEFAULT FALSE,
    reply_text              TEXT                    NULL,
    reply_external_id       VARCHAR(255)            NULL,
    commented_at            TIMESTAMPTZ             NOT NULL,  -- data original na rede
    captured_at             TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    created_at              TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    -- Embedding para busca semântica e agrupamento
    embedding               VECTOR(1536)            NULL
);

-- Deduplicação: um comentário externo por conta social
CREATE UNIQUE INDEX uq_comments_external
    ON comments (social_account_id, external_comment_id);

-- Listagem por usuário (inbox unificado)
CREATE INDEX idx_comments_user_inbox
    ON comments (user_id, captured_at DESC);

-- Filtro por conteúdo
CREATE INDEX idx_comments_content
    ON comments (content_id, captured_at DESC);

-- Filtro por sentimento
CREATE INDEX idx_comments_sentiment
    ON comments (user_id, sentiment, captured_at DESC)
    WHERE sentiment IS NOT NULL;

-- Filtro por status de leitura
CREATE INDEX idx_comments_unread
    ON comments (user_id, captured_at DESC)
    WHERE is_read = FALSE;

-- Filtro por status de resposta
CREATE INDEX idx_comments_unreplied
    ON comments (user_id, captured_at DESC)
    WHERE replied_at IS NULL AND is_from_owner = FALSE;

-- Busca textual (full-text search)
CREATE INDEX idx_comments_text_search
    ON comments USING GIN (to_tsvector('portuguese', text));

-- Busca vetorial
CREATE INDEX idx_comments_embedding
    ON comments USING ivfflat (embedding vector_cosine_ops)
    WITH (lists = 200)
    WHERE embedding IS NOT NULL;

-- Para sync job: buscar novos comentários
CREATE INDEX idx_comments_account_captured
    ON comments (social_account_id, captured_at DESC);
```

### Relacionamentos
- `N:1` → `contents`
- `N:1` → `social_accounts`
- `N:1` → `users`

### Notas
- `external_comment_id` + `social_account_id` garante deduplicação.
- `is_from_owner` filtra comentários do próprio dono (não processados pela automação).
- `sentiment_score` armazena o score bruto da IA; `sentiment` é a classificação derivada.
- Full-text search em português para busca textual nos comentários.
- `embedding` permite busca semântica e agrupamento de comentários similares.

---

## Tabela: `automation_rules`

Regras de automação para processamento de comentários.

```sql
CREATE TABLE automation_rules (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID                    NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name                VARCHAR(100)            NOT NULL,
    priority            SMALLINT                NOT NULL DEFAULT 0,  -- menor = maior prioridade
    action_type         automation_action_type  NOT NULL,
    response_template   TEXT                    NULL,      -- para reply_fixed e reply_template
    webhook_id          UUID                    NULL REFERENCES webhook_endpoints(id) ON DELETE SET NULL,
    delay_seconds       INTEGER                 NOT NULL DEFAULT 120,
    daily_limit         INTEGER                 NOT NULL DEFAULT 100,
    is_active           BOOLEAN                 NOT NULL DEFAULT TRUE,
    applies_to_networks social_provider_type[]  NULL,      -- NULL = todas as redes
    applies_to_campaigns UUID[]                 NULL,      -- NULL = todas as campanhas
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ             NULL,
    purge_at            TIMESTAMPTZ             NULL,

    CONSTRAINT ck_automation_rules_delay CHECK (delay_seconds >= 30 AND delay_seconds <= 3600),
    CONSTRAINT ck_automation_rules_limit CHECK (daily_limit >= 10 AND daily_limit <= 1000),
    CONSTRAINT ck_automation_rules_template CHECK (
        action_type NOT IN ('reply_fixed', 'reply_template')
        OR response_template IS NOT NULL
    ),
    CONSTRAINT ck_automation_rules_webhook CHECK (
        action_type != 'send_webhook' OR webhook_id IS NOT NULL
    )
);

-- Prioridade única por usuário (entre ativos)
CREATE UNIQUE INDEX uq_automation_rules_priority
    ON automation_rules (user_id, priority)
    WHERE is_active = TRUE AND deleted_at IS NULL;

-- Índices
CREATE INDEX idx_automation_rules_user
    ON automation_rules (user_id, priority)
    WHERE is_active = TRUE AND deleted_at IS NULL;

CREATE INDEX idx_automation_rules_purge
    ON automation_rules (purge_at)
    WHERE purge_at IS NOT NULL;
```

### Notas
- Check constraints garantem consistência entre `action_type` e campos dependentes.
- `applies_to_networks` e `applies_to_campaigns` usam arrays para filtro seletivo; NULL = aplica a tudo.
- Prioridade única entre regras ativas garante ordem determinística de avaliação.

---

## Tabela: `automation_rule_conditions`

Condições individuais de uma regra de automação.

```sql
CREATE TABLE automation_rule_conditions (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    automation_rule_id  UUID            NOT NULL REFERENCES automation_rules(id) ON DELETE CASCADE,
    field               VARCHAR(50)     NOT NULL,  -- 'keyword', 'sentiment', 'author_name'
    operator            VARCHAR(20)     NOT NULL,  -- 'contains', 'equals', 'in', 'not_contains'
    value               TEXT            NOT NULL,  -- valor ou JSON array para 'in'
    is_case_sensitive   BOOLEAN         NOT NULL DEFAULT FALSE,
    position            SMALLINT        NOT NULL DEFAULT 0,  -- ordem de avaliação
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Índice
CREATE INDEX idx_rule_conditions_rule
    ON automation_rule_conditions (automation_rule_id, position);
```

### Campos suportados

| Field | Operators | Exemplo |
|-------|-----------|---------|
| `keyword` | `contains`, `not_contains`, `equals` | `contains: "preço"` |
| `sentiment` | `equals`, `in` | `equals: "negative"` |
| `author_name` | `contains`, `equals` | `equals: "bot_spam"` |

### Notas
- Todas as condições de uma regra são avaliadas com **AND** (todas devem ser verdadeiras).
- `position` define a ordem de avaliação (otimização: condições mais baratas primeiro).

---

## Tabela: `automation_executions`

Log de execuções do motor de automação.

```sql
CREATE TABLE automation_executions (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    automation_rule_id  UUID            NOT NULL REFERENCES automation_rules(id) ON DELETE CASCADE,
    comment_id          UUID            NOT NULL REFERENCES comments(id) ON DELETE CASCADE,
    user_id             UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action_type         automation_action_type NOT NULL,
    response_text       TEXT            NULL,      -- resposta enviada (se aplicável)
    success             BOOLEAN         NOT NULL DEFAULT TRUE,
    error_message       TEXT            NULL,
    delay_applied       INTEGER         NOT NULL DEFAULT 0,  -- delay real aplicado (seconds)
    executed_at         TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_automation_executions_rule
    ON automation_executions (automation_rule_id, executed_at DESC);

CREATE INDEX idx_automation_executions_user
    ON automation_executions (user_id, executed_at DESC);

CREATE INDEX idx_automation_executions_comment
    ON automation_executions (comment_id);

-- Para contagem de limite diário
CREATE INDEX idx_automation_executions_daily
    ON automation_executions (user_id, date_trunc('day', executed_at))
    WHERE success = TRUE;
```

### Notas
- Cada execução de automação é registrada, mesmo em caso de falha.
- `idx_automation_executions_daily` é usado para verificar o limite diário de respostas automáticas.
- Retenção: 6 meses (job de limpeza).

---

## Tabela: `automation_blacklist_words`

Palavras que bloqueiam resposta automática.

```sql
CREATE TABLE automation_blacklist_words (
    id          UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id     UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    word        VARCHAR(100)    NOT NULL,
    is_regex    BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_blacklist_user_word UNIQUE (user_id, LOWER(word))
);

-- Índice
CREATE INDEX idx_blacklist_user ON automation_blacklist_words (user_id);
```

### Notas
- `is_regex` permite expressões regulares para matching avançado.
- Unique constraint case-insensitive evita duplicatas.

---

## Tabela: `webhook_endpoints`

Endpoints de webhook configurados pelo usuário para integração com CRM.

```sql
CREATE TABLE webhook_endpoints (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID            NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(100)    NOT NULL,
    url             VARCHAR(2000)   NOT NULL,
    secret          TEXT            NOT NULL,  -- criptografado (AES-256-GCM), usado para HMAC
    events          TEXT[]          NOT NULL,  -- ['comment.created', 'comment.replied', ...]
    headers         JSONB           NOT NULL DEFAULT '{}',  -- headers customizados
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    last_delivery_at TIMESTAMPTZ    NULL,
    last_delivery_status INTEGER    NULL,     -- HTTP status da última entrega
    failure_count   SMALLINT        NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    deleted_at      TIMESTAMPTZ     NULL,
    purge_at        TIMESTAMPTZ     NULL
);

-- Índices
CREATE INDEX idx_webhook_endpoints_user
    ON webhook_endpoints (user_id)
    WHERE is_active = TRUE AND deleted_at IS NULL;

CREATE INDEX idx_webhook_endpoints_events
    ON webhook_endpoints USING GIN (events)
    WHERE is_active = TRUE AND deleted_at IS NULL;
```

### Eventos suportados

| Evento | Trigger |
|--------|---------|
| `comment.created` | Novo comentário capturado |
| `comment.replied` | Comentário respondido (manual ou automação) |
| `lead.identified` | Lead identificado por regra |
| `automation.triggered` | Automação executada |
| `post.published` | Post publicado com sucesso |
| `post.failed` | Falha na publicação |

### Notas
- `secret` é criptografado no banco e descriptografado apenas para gerar assinatura HMAC.
- `events` usa array + GIN index para busca eficiente por evento.
- `failure_count` é incrementado em cada falha e resetado em sucesso — webhook é desativado após 10 falhas consecutivas.

---

## Tabela: `webhook_deliveries`

Log de entregas de webhook.

```sql
CREATE TABLE webhook_deliveries (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    webhook_endpoint_id UUID            NOT NULL REFERENCES webhook_endpoints(id) ON DELETE CASCADE,
    event               VARCHAR(100)    NOT NULL,
    payload             JSONB           NOT NULL,
    response_status     SMALLINT        NULL,
    response_body       TEXT            NULL,     -- truncado a 1000 chars
    response_time_ms    INTEGER         NULL,
    attempts            SMALLINT        NOT NULL DEFAULT 1,
    max_attempts        SMALLINT        NOT NULL DEFAULT 3,
    next_retry_at       TIMESTAMPTZ     NULL,
    delivered_at        TIMESTAMPTZ     NULL,
    failed_at           TIMESTAMPTZ     NULL,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Índices
CREATE INDEX idx_webhook_deliveries_endpoint
    ON webhook_deliveries (webhook_endpoint_id, created_at DESC);

CREATE INDEX idx_webhook_deliveries_retry
    ON webhook_deliveries (next_retry_at)
    WHERE failed_at IS NULL AND next_retry_at IS NOT NULL;

CREATE INDEX idx_webhook_deliveries_event
    ON webhook_deliveries (event, created_at DESC);
```

### Notas
- `response_body` é truncado a 1000 caracteres para não consumir espaço excessivo.
- `next_retry_at` é usado pelo job de retry para buscar entregas pendentes.
- Retenção: 30 dias (job de limpeza).

---

## ER — Engagement & Automation

```
comments
├── id (PK)
├── content_id (FK → contents)
├── social_account_id (FK → social_accounts)
├── user_id (FK → users)
├── external_comment_id (UNIQUE per account)
├── author_name, author_external_id
├── text
├── sentiment, sentiment_score
├── is_read, is_from_owner
├── replied_at, replied_by, reply_text
├── embedding (vector)
└── commented_at, captured_at

automation_rules
├── id (PK)
├── user_id (FK → users)
├── name, priority
├── action_type, response_template
├── webhook_id (FK → webhook_endpoints)
├── delay_seconds, daily_limit
├── is_active
│
├──── automation_rule_conditions
│     ├── id (PK)
│     ├── automation_rule_id (FK)
│     ├── field, operator, value
│     └── position
│
└──── automation_executions
      ├── id (PK)
      ├── automation_rule_id (FK)
      ├── comment_id (FK → comments)
      ├── action_type, response_text
      ├── success, error_message
      └── executed_at

automation_blacklist_words
├── id (PK)
├── user_id (FK → users)
├── word (UNIQUE per user)
└── is_regex

webhook_endpoints
├── id (PK)
├── user_id (FK → users)
├── name, url
├── secret (encrypted)
├── events[] (GIN indexed)
├── headers (JSONB)
├── is_active
│
└──── webhook_deliveries
      ├── id (PK)
      ├── webhook_endpoint_id (FK)
      ├── event, payload (JSONB)
      ├── response_status, response_body
      ├── attempts, next_retry_at
      └── delivered_at / failed_at
```
