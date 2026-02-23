# 10 — AI Intelligence

[← Voltar ao índice](00-index.md)

> **Fase:** 2 (Sprints 10-11), 3 (Sprints 12-13) e Learning Loop (Sprint 14)

---

## Tipos ENUM (AI Intelligence)

```sql
CREATE TYPE safety_check_status_type AS ENUM ('pending', 'passed', 'warning', 'blocked');

-- Adicionar ao generation_type existente:
ALTER TYPE generation_type ADD VALUE 'cross_network_adaptation';
ALTER TYPE generation_type ADD VALUE 'calendar_planning';
```

---

## Tabela: `embedding_jobs`

Tracking de geração de embeddings para conteúdos e comentários.

```sql
CREATE TABLE embedding_jobs (
    id              UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    entity_type     VARCHAR(50)     NOT NULL,       -- 'content', 'comment'
    entity_id       UUID            NOT NULL,
    status          VARCHAR(20)     NOT NULL DEFAULT 'pending',  -- pending, processing, completed, failed
    model_used      VARCHAR(50)     NULL,           -- 'text-embedding-3-small'
    tokens_used     INTEGER         NULL,
    error_message   TEXT            NULL,
    created_at      TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    completed_at    TIMESTAMPTZ     NULL,

    CONSTRAINT uq_embedding_jobs_entity UNIQUE (entity_type, entity_id)
);

-- Jobs pendentes (para processamento)
CREATE INDEX idx_embedding_jobs_pending
    ON embedding_jobs (created_at)
    WHERE status = 'pending';

-- Por organização
CREATE INDEX idx_embedding_jobs_org
    ON embedding_jobs (organization_id, entity_type, status);
```

### Notas
- `entity_type` + `entity_id` é unique — um entity tem no máximo 1 job ativo.
- Status `failed` permite retry pelo `BackfillEmbeddingsJob`.
- `model_used` e `tokens_used` preenchidos após conclusão (para cost tracking).

---

## Tabela: `content_profiles`

Perfil de conteúdo da organização (Content DNA Profiling).

```sql
CREATE TABLE content_profiles (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    social_account_id       UUID            NULL REFERENCES social_accounts(id) ON DELETE SET NULL,
    provider                social_provider_type NULL,   -- null = todas as redes
    total_contents_analyzed INTEGER         NOT NULL DEFAULT 0,
    top_themes              JSONB           NOT NULL DEFAULT '[]',   -- [{theme, score, content_count}]
    engagement_patterns     JSONB           NOT NULL DEFAULT '{}',   -- {avg_likes, avg_comments, avg_shares, best_content_types}
    content_fingerprint     JSONB           NOT NULL DEFAULT '{}',   -- {avg_length, hashtag_patterns, tone_distribution, posting_frequency}
    high_performer_traits   JSONB           NOT NULL DEFAULT '{}',   -- traits dos top 20% por engagement
    centroid_embedding      VECTOR(1536)    NULL,                    -- média dos embeddings de high-performers
    generated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    expires_at              TIMESTAMPTZ     NOT NULL,                -- TTL 7 dias
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_content_profiles_org_provider
        UNIQUE (organization_id, social_account_id, provider)
);

-- Por organização
CREATE INDEX idx_content_profiles_org
    ON content_profiles (organization_id, generated_at DESC);

-- Similarity search no centroid
CREATE INDEX idx_content_profiles_centroid
    ON content_profiles USING ivfflat (centroid_embedding vector_cosine_ops)
    WITH (lists = 50)
    WHERE centroid_embedding IS NOT NULL;
```

### Notas
- Perfil único por (organization, social_account, provider).
- `centroid_embedding` permite busca por similaridade entre perfis de diferentes contas.
- `expires_at` garante recálculo periódico (stale profiles são ignorados).
- IVFFlat index com 50 lists — adequado para volume esperado (<10K profiles).

---

## Tabela: `performance_predictions`

Predições de performance pré-publicação.

```sql
CREATE TABLE performance_predictions (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    content_id          UUID                    NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    provider            social_provider_type    NOT NULL,
    overall_score       SMALLINT                NOT NULL,       -- 0-100
    breakdown           JSONB                   NOT NULL,       -- {content_similarity, timing, hashtags, length, media_type}
    similar_content_ids UUID[]                  NULL,            -- top 5 similares
    recommendations     JSONB                   NOT NULL DEFAULT '[]',  -- [{type, message, impact_estimate}]
    model_version       VARCHAR(20)             NOT NULL DEFAULT 'v1',
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_predictions_score CHECK (overall_score >= 0 AND overall_score <= 100)
);

-- Por conteúdo + provider
CREATE INDEX idx_predictions_content
    ON performance_predictions (content_id, provider);

-- Por organização
CREATE INDEX idx_predictions_org
    ON performance_predictions (organization_id, created_at DESC);
```

### Notas
- Predictions são imutáveis (para atualizar, gerar nova).
- `breakdown` JSONB com scores individuais por fator (cada um 0-100).
- `similar_content_ids` permite ao frontend mostrar os conteúdos de referência.
- `model_version` para tracking de evolução do modelo de predição.

---

## Tabela: `posting_time_recommendations`

Horários ótimos de publicação por organização/rede/dia.

```sql
CREATE TABLE posting_time_recommendations (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    social_account_id   UUID                    NULL REFERENCES social_accounts(id) ON DELETE SET NULL,
    provider            social_provider_type    NULL,       -- null = agregado
    day_of_week         SMALLINT                NULL,       -- 0=Sunday...6=Saturday, null = todos
    heatmap             JSONB                   NOT NULL,   -- [{hour: 0, score: 45}, {hour: 1, score: 12}, ...]
    top_slots           JSONB                   NOT NULL,   -- [{day, hour, avg_engagement_rate, sample_size}]
    worst_slots         JSONB                   NOT NULL DEFAULT '[]',
    sample_size         INTEGER                 NOT NULL,   -- posts analisados
    confidence_level    VARCHAR(10)             NOT NULL DEFAULT 'low',  -- low, medium, high
    calculated_at       TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    expires_at          TIMESTAMPTZ             NOT NULL,   -- TTL 7 dias
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_posting_times_org_account_provider_day
        UNIQUE (organization_id, social_account_id, provider, day_of_week),
    CONSTRAINT ck_posting_times_day CHECK (day_of_week IS NULL OR (day_of_week >= 0 AND day_of_week <= 6)),
    CONSTRAINT ck_posting_times_confidence CHECK (confidence_level IN ('low', 'medium', 'high'))
);

-- Por organização + provider
CREATE INDEX idx_posting_times_org
    ON posting_time_recommendations (organization_id, provider);
```

### Notas
- Unique constraint garante 1 recomendação por (org, conta, rede, dia).
- `confidence_level`: low (<10 posts), medium (10-50), high (>50).
- `heatmap` JSONB com 24 entries (1 por hora) para visualização de heatmap.
- Não depende de pgvector — modelo puramente estatístico.

---

## Tabela: `audience_insights`

Insights extraídos de comentários da audiência.

```sql
CREATE TABLE audience_insights (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    social_account_id       UUID            NULL REFERENCES social_accounts(id) ON DELETE SET NULL,
    insight_type            VARCHAR(50)     NOT NULL,   -- preferred_topics, sentiment_trends, engagement_drivers, audience_preferences
    insight_data            JSONB           NOT NULL,   -- dados estruturados do insight
    source_comment_count    INTEGER         NOT NULL DEFAULT 0,
    period_start            TIMESTAMPTZ     NOT NULL,
    period_end              TIMESTAMPTZ     NOT NULL,
    confidence_score        DECIMAL(5,4)    NULL,       -- 0.0-1.0
    generated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    expires_at              TIMESTAMPTZ     NOT NULL,   -- TTL 7 dias
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

-- Por organização + tipo
CREATE INDEX idx_audience_insights_org
    ON audience_insights (organization_id, insight_type, generated_at DESC);
```

### Notas
- `insight_type` define a estrutura de `insight_data`:
  - `preferred_topics`: `{topics: [{name, score, comment_count}]}`
  - `sentiment_trends`: `{trend: [{period, positive_pct, neutral_pct, negative_pct}]}`
  - `engagement_drivers`: `{drivers: [{type, description, correlation_score}]}`
  - `audience_preferences`: `{preferences: [{category, value, confidence}]}`
- TTL de 7 dias — insights recalculados semanalmente.

---

## Tabela: `ai_generation_context`

Cache compacto de contexto para injeção em prompts de geração.

```sql
CREATE TABLE ai_generation_context (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    context_type        VARCHAR(50)     NOT NULL,   -- audience_preferences, content_dna, brand_voice
    context_data        JSONB           NOT NULL,   -- dados resumidos para injeção no prompt
    max_tokens          INTEGER         NOT NULL DEFAULT 500,    -- budget de tokens para este contexto
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_ai_context_org_type
        UNIQUE (organization_id, context_type)
);
```

### Notas
- Tabela compacta — 1 registro por (organization, context_type).
- `max_tokens` limita o tamanho do contexto injetado no prompt (evita custo excessivo).
- Atualizado pelo `UpdateAIGenerationContextJob` após refresh de insights.
- Consultado por Use Cases de geração (RF-030 a RF-033) para enriquecer prompts.

---

## Tabela: `brand_safety_checks`

Resultados de verificação de Brand Safety.

```sql
CREATE TABLE brand_safety_checks (
    id                  UUID                        PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                        NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    content_id          UUID                        NOT NULL REFERENCES contents(id) ON DELETE CASCADE,
    provider            social_provider_type        NULL,       -- null = verificação geral
    overall_status      safety_check_status_type    NOT NULL DEFAULT 'pending',
    overall_score       SMALLINT                    NULL,       -- 0-100 (100 = totalmente seguro)
    checks              JSONB                       NOT NULL DEFAULT '[]',   -- [{category, status, message, severity}]
    model_used          VARCHAR(50)                 NULL,
    tokens_input        INTEGER                     NULL,
    tokens_output       INTEGER                     NULL,
    checked_at          TIMESTAMPTZ                 NULL,
    created_at          TIMESTAMPTZ                 NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_safety_score CHECK (overall_score IS NULL OR (overall_score >= 0 AND overall_score <= 100))
);

-- Por conteúdo + provider
CREATE INDEX idx_safety_checks_content
    ON brand_safety_checks (content_id, provider);

-- Por organização
CREATE INDEX idx_safety_checks_org
    ON brand_safety_checks (organization_id, created_at DESC);

-- Para PublishJob consultar status antes de publicar
CREATE INDEX idx_safety_checks_content_status
    ON brand_safety_checks (content_id, overall_status)
    WHERE overall_status IN ('warning', 'blocked');
```

### Notas
- `checks` JSONB com array de resultados por categoria.
- Exemplo: `[{"category": "lgpd_compliance", "status": "passed", "message": null, "severity": null}]`
- `ProcessScheduledPostJob` consulta `brand_safety_checks` antes de publicar.
- Tokens rastreados para cost tracking (verificação consome LLM).

---

## Tabela: `brand_safety_rules`

Regras customizáveis de Brand Safety por organização.

```sql
CREATE TABLE brand_safety_rules (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    rule_type           VARCHAR(50)     NOT NULL,   -- blocked_word, required_disclosure, custom_check
    rule_config         JSONB           NOT NULL,   -- {words: [], pattern: "", message: ""}
    severity            VARCHAR(20)     NOT NULL DEFAULT 'warning',  -- warning, block
    is_active           BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_safety_rules_severity CHECK (severity IN ('warning', 'block'))
);

-- Regras ativas por organização
CREATE INDEX idx_safety_rules_org
    ON brand_safety_rules (organization_id)
    WHERE is_active = TRUE;
```

### Notas
- `rule_config` estrutura depende do `rule_type`:
  - `blocked_word`: `{"words": ["spam", "grátis"], "match_mode": "contains"}`
  - `required_disclosure`: `{"keywords": ["parceria", "publi"], "disclosure_text": "#publi"}`
  - `custom_check`: `{"prompt": "Verifique se o conteúdo menciona preços..."}`
- Regras aplicadas pelo `RunBrandSafetyCheckJob` junto com verificação LLM.

---

## Tabela: `calendar_suggestions`

Sugestões de calendário editorial geradas por IA.

```sql
CREATE TABLE calendar_suggestions (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    period_start        DATE            NOT NULL,
    period_end          DATE            NOT NULL,
    suggestions         JSONB           NOT NULL,   -- [{date, topics, content_type, target_networks, reasoning, priority}]
    based_on            JSONB           NOT NULL DEFAULT '{}',  -- {top_performers, gaps, trends, existing_schedule}
    status              VARCHAR(20)     NOT NULL DEFAULT 'generated',  -- generated, reviewed, accepted, expired
    accepted_items      JSONB           NULL,       -- itens aceitos pelo usuário
    generated_at        TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    expires_at          TIMESTAMPTZ     NOT NULL,   -- TTL 7 dias
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_calendar_period CHECK (period_end > period_start),
    CONSTRAINT ck_calendar_status CHECK (status IN ('generated', 'reviewed', 'accepted', 'expired'))
);

-- Por organização
CREATE INDEX idx_calendar_suggestions_org
    ON calendar_suggestions (organization_id, period_start DESC);

-- Para cleanup de expirados
CREATE INDEX idx_calendar_suggestions_expired
    ON calendar_suggestions (expires_at)
    WHERE status != 'expired';
```

### Notas
- `suggestions` JSONB com array de itens sugeridos.
- Exemplo de item: `{"date": "2026-03-15", "topics": ["marketing digital"], "content_type": "reel", "target_networks": ["instagram", "tiktok"], "reasoning": "Tema com +40% engagement nos últimos 30 dias", "priority": 1}`
- `accepted_items` é subset de `suggestions` que o usuário aceitou.
- Aceitar itens NÃO cria conteúdo — apenas marca para referência.

---

## Tabela: `content_gap_analyses`

Análises de lacunas de conteúdo vs concorrentes.

```sql
CREATE TABLE content_gap_analyses (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    competitor_query_ids    UUID[]          NOT NULL,   -- listening_queries de tipo 'competitor'
    analysis_period_start   TIMESTAMPTZ     NOT NULL,
    analysis_period_end     TIMESTAMPTZ     NOT NULL,
    our_topics              JSONB           NOT NULL DEFAULT '[]',   -- [{topic, frequency, avg_engagement}]
    competitor_topics       JSONB           NOT NULL DEFAULT '[]',   -- [{topic, source_competitor, frequency, avg_engagement}]
    gaps                    JSONB           NOT NULL DEFAULT '[]',   -- [{topic, opportunity_score, competitor_count, recommendation}]
    opportunities           JSONB           NOT NULL DEFAULT '[]',   -- [{topic, reason, suggested_content_type, estimated_impact}]
    generated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    expires_at              TIMESTAMPTZ     NOT NULL,   -- TTL 7 dias
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_gap_analysis_period CHECK (analysis_period_end > analysis_period_start)
);

-- Por organização
CREATE INDEX idx_gap_analyses_org
    ON content_gap_analyses (organization_id, generated_at DESC);
```

### Notas
- `competitor_query_ids` referencia queries de Social Listening tipo `competitor`.
- `gaps` são tópicos que concorrentes cobrem mas a organização não.
- `opportunities` são gaps filtrados com opportunity_score > 50 e sugestões acionáveis.
- Análise expira após 7 dias — dados de menções mudam frequentemente.

---

## Tabela: `generation_feedback`

Registro de feedback do usuário sobre cada geração de IA (aceitar, editar, rejeitar).

```sql
CREATE TABLE generation_feedback (
    id                  UUID                 PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                 NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id             UUID                 NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ai_generation_id    UUID                 NOT NULL,
    action              feedback_action_type NOT NULL,
    original_output     JSONB                NOT NULL,
    edited_output       JSONB                NULL,
    diff_summary        JSONB                NULL,
    content_id          UUID                 NULL,
    generation_type     VARCHAR(50)          NOT NULL,
    time_to_decision_ms INTEGER              NULL,
    created_at          TIMESTAMPTZ          NOT NULL DEFAULT NOW()
);

-- Por organização + tipo + ação
CREATE INDEX idx_gen_feedback_org_type
    ON generation_feedback (organization_id, generation_type, action, created_at DESC);

-- Por geração (lookup)
CREATE INDEX idx_gen_feedback_generation
    ON generation_feedback (ai_generation_id);

-- Edições por organização (para Style Learning)
CREATE INDEX idx_gen_feedback_edited
    ON generation_feedback (organization_id, created_at DESC)
    WHERE action = 'edited';
```

### Notas
- `diff_summary`: JSONB com `{changes: [{field, before, after}], change_ratio: 0.0-1.0}`. Ratio = Levenshtein distance / tamanho original.
- `time_to_decision_ms`: Tempo entre geração e feedback (aceite rápido = alta qualidade).
- `content_id`: NULL se rejeitado; referência ao conteúdo onde o output foi usado se aceito/editado.
- Feedback é upsert por `ai_generation_id` — duplicatas substituem o anterior.
- Dados retidos por 12 meses (alinhado com `ai_generations`).

---

## Tabela: `prompt_templates`

Templates versionados de prompt para geração de conteúdo, com métricas de performance.

```sql
CREATE TABLE prompt_templates (
    id                   UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id      UUID            NULL REFERENCES organizations(id) ON DELETE CASCADE,
    generation_type      VARCHAR(50)     NOT NULL,
    version              VARCHAR(20)     NOT NULL,
    name                 VARCHAR(200)    NOT NULL,
    system_prompt        TEXT            NOT NULL,
    user_prompt_template TEXT            NOT NULL,
    variables            JSONB           NOT NULL DEFAULT '[]',
    is_active            BOOLEAN         NOT NULL DEFAULT TRUE,
    is_default           BOOLEAN         NOT NULL DEFAULT FALSE,
    performance_score    DECIMAL(5,2)    NULL,
    total_uses           INTEGER         NOT NULL DEFAULT 0,
    total_accepted       INTEGER         NOT NULL DEFAULT 0,
    total_edited         INTEGER         NOT NULL DEFAULT 0,
    total_rejected       INTEGER         NOT NULL DEFAULT 0,
    created_by           UUID            NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at           TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_prompt_templates_org_type_version
        UNIQUE (organization_id, generation_type, version)
);

-- Templates ativos ordenados por performance
CREATE INDEX idx_prompt_templates_active
    ON prompt_templates (organization_id, generation_type, is_active, performance_score DESC NULLS LAST)
    WHERE is_active = TRUE;
```

### Notas
- `organization_id = NULL` → template global do sistema (seedado no deploy).
- `performance_score = (accepted + edited × 0.7) / total_uses × 100`. Recalculado semanalmente por `CalculatePromptPerformanceJob`.
- Auto-seleção: template com maior `performance_score` e mínimo 20 uses.
- Versões são imutáveis — editar cria nova versão.
- `variables` JSONB lista placeholders aceitos (ex: `["topic", "tone", "language"]`).

---

## Tabela: `prompt_experiments`

A/B test entre duas variantes de prompt template.

```sql
CREATE TABLE prompt_experiments (
    id                  UUID                    PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID                    NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    generation_type     VARCHAR(50)             NOT NULL,
    name                VARCHAR(200)            NOT NULL,
    status              experiment_status_type  NOT NULL DEFAULT 'draft',
    variant_a_id        UUID                    NOT NULL REFERENCES prompt_templates(id),
    variant_b_id        UUID                    NOT NULL REFERENCES prompt_templates(id),
    traffic_split       DECIMAL(3,2)            NOT NULL DEFAULT 0.50,
    min_sample_size     INTEGER                 NOT NULL DEFAULT 50,
    variant_a_uses      INTEGER                 NOT NULL DEFAULT 0,
    variant_a_accepted  INTEGER                 NOT NULL DEFAULT 0,
    variant_b_uses      INTEGER                 NOT NULL DEFAULT 0,
    variant_b_accepted  INTEGER                 NOT NULL DEFAULT 0,
    winner_id           UUID                    NULL REFERENCES prompt_templates(id),
    confidence_level    DECIMAL(5,4)            NULL,
    started_at          TIMESTAMPTZ             NULL,
    completed_at        TIMESTAMPTZ             NULL,
    created_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ             NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_experiments_variants CHECK (variant_a_id != variant_b_id),
    CONSTRAINT ck_experiments_split CHECK (traffic_split > 0 AND traffic_split < 1)
);

-- Apenas 1 experimento ativo por (org, generation_type)
CREATE INDEX idx_experiments_active
    ON prompt_experiments (organization_id, generation_type)
    WHERE status = 'running';
```

### Notas
- Máximo 1 experimento `running` por (organization_id, generation_type) — enforced por application logic.
- Mínimo `min_sample_size` gerações por variante antes de concluir (default: 50).
- Significância estatística via two-proportion z-test. Vencedor declarado com `confidence_level >= 0.95`.
- Ao completar, template vencedor é ativado como default; perdedor desativado.

---

## Tabela: `prediction_validations`

Comparação entre score predito pelo Performance Prediction e métricas reais pós-publicação.

```sql
CREATE TABLE prediction_validations (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    prediction_id           UUID            NOT NULL REFERENCES performance_predictions(id) ON DELETE CASCADE,
    content_id              UUID            NOT NULL,
    provider                VARCHAR(30)     NOT NULL,
    predicted_score         SMALLINT        NOT NULL,
    actual_engagement_rate  DECIMAL(8,4)    NULL,
    actual_normalized_score SMALLINT        NULL,
    absolute_error          SMALLINT        NULL,
    prediction_accuracy     DECIMAL(5,2)    NULL,
    metrics_snapshot        JSONB           NOT NULL DEFAULT '{}',
    validated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    metrics_captured_at     TIMESTAMPTZ     NOT NULL,
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT ck_validation_scores CHECK (
        (actual_normalized_score IS NULL OR (actual_normalized_score >= 0 AND actual_normalized_score <= 100))
        AND (prediction_accuracy IS NULL OR (prediction_accuracy >= 0 AND prediction_accuracy <= 100))
    ),
    CONSTRAINT uq_prediction_validation UNIQUE (prediction_id)
);

-- Por organização
CREATE INDEX idx_prediction_validations_org
    ON prediction_validations (organization_id, validated_at DESC);

-- Para métricas de acurácia
CREATE INDEX idx_prediction_validations_accuracy
    ON prediction_validations (organization_id, prediction_accuracy)
    WHERE prediction_accuracy IS NOT NULL;
```

### Notas
- 1 validação por predição (UNIQUE on `prediction_id`).
- Trigger: evento `MetricsSynced` quando métricas de 7 dias estão disponíveis.
- `actual_normalized_score`: engagement rate normalizado como percentile rank × 100 na distribuição histórica da própria organização.
- `prediction_accuracy = 100 - |predicted_score - actual_normalized_score|`.
- Mínimo 10 validações para exibir métricas de acurácia (MAE, correlação).

---

## Tabela: `org_style_profiles`

Perfil de preferências de estilo da organização, aprendido a partir de padrões de edição do usuário sobre gerações de IA.

```sql
CREATE TABLE org_style_profiles (
    id                      UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id         UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    generation_type         VARCHAR(50)     NOT NULL,
    sample_size             INTEGER         NOT NULL DEFAULT 0,
    tone_preferences        JSONB           NOT NULL DEFAULT '{}',
    length_preferences      JSONB           NOT NULL DEFAULT '{}',
    vocabulary_preferences  JSONB           NOT NULL DEFAULT '{}',
    structure_preferences   JSONB           NOT NULL DEFAULT '{}',
    hashtag_preferences     JSONB           NOT NULL DEFAULT '{}',
    style_summary           TEXT            NULL,
    style_embedding         VECTOR(1536)    NULL,
    confidence_level        VARCHAR(10)     NOT NULL DEFAULT 'low',
    generated_at            TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    expires_at              TIMESTAMPTZ     NOT NULL,
    created_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ     NOT NULL DEFAULT NOW(),

    CONSTRAINT uq_style_profiles_org_type
        UNIQUE (organization_id, generation_type),
    CONSTRAINT ck_style_confidence CHECK (confidence_level IN ('low', 'medium', 'high'))
);

-- Perfis válidos por organização
CREATE INDEX idx_style_profiles_org
    ON org_style_profiles (organization_id, generation_type)
    WHERE expires_at > NOW();
```

### Notas
- Perfil único por (organization_id, generation_type).
- Mínimo 10 edições (`action = 'edited'`) para gerar perfil.
- Confidence: low (<10 edits), medium (10-50), high (>50+).
- `style_summary`: resumo em linguagem natural gerado por GPT-4o-mini (max 200 tokens).
- TTL 14 dias (recalculado semanalmente, 7 dias de folga antes de expirar).
- Injetado no prompt como "Preferências de estilo da organização".
- Desativável via `PUT /api/v1/ai/settings` (`style_learning_enabled: false`).
- `style_embedding` permite busca por organizações com estilo similar (futuro).

**Exemplos de JSONB:**

```json
// tone_preferences
{"preferred": "casual", "avoids": "formal", "detected_patterns": ["usa gírias", "evita jargão técnico"]}

// length_preferences
{"avg_preferred_length": 150, "shortens_by_pct": 0.15, "extends_by_pct": 0.0}

// vocabulary_preferences
{"added_words": ["transformar", "jornada"], "removed_words": ["otimizar", "alavancar"], "preferred_phrases": ["na prática"]}

// structure_preferences
{"uses_emojis": true, "uses_questions": true, "preferred_cta_style": "pergunta retórica"}

// hashtag_preferences
{"avg_count": 8, "preferred_tags": ["#marketing", "#dicas"], "avoided_tags": ["#followme"], "style": "branded"}
```

---

## Alterações em Tabelas Existentes (Learning Loop)

### `ai_generations` (doc 02)

4 novas colunas para rastreabilidade do Learning Loop:

```sql
ALTER TABLE ai_generations ADD COLUMN prompt_template_id UUID NULL;
ALTER TABLE ai_generations ADD COLUMN experiment_id UUID NULL;
ALTER TABLE ai_generations ADD COLUMN rag_context_used JSONB NULL;
ALTER TABLE ai_generations ADD COLUMN style_context_used BOOLEAN NOT NULL DEFAULT FALSE;
```

- `prompt_template_id`: qual template gerou este output.
- `experiment_id`: se fazia parte de A/B test.
- `rag_context_used`: `{similar_content_ids: [uuid], context_tokens: int}`.
- `style_context_used`: se style profile foi injetado.

### `ai_generation_context` (doc 10)

Novos valores de `context_type` (sem mudança no schema):

- `'rag_examples'` — cache de top performers por tópico frequente.
- `'org_style'` — resumo do style profile para injeção no prompt.

---

## ER — AI Intelligence

```
embedding_jobs
├── id (PK)
├── organization_id (FK → organizations)
├── entity_type, entity_id (UNIQUE)
├── status, model_used, tokens_used
└── error_message

content_profiles
├── id (PK)
├── organization_id (FK → organizations)
├── social_account_id (FK → social_accounts, nullable)
├── provider, total_contents_analyzed
├── top_themes, engagement_patterns (JSONB)
├── content_fingerprint, high_performer_traits (JSONB)
├── centroid_embedding (VECTOR(1536))
└── generated_at, expires_at

performance_predictions
├── id (PK)
├── organization_id (FK → organizations)
├── content_id (FK → contents)
├── provider, overall_score (0-100)
├── breakdown, recommendations (JSONB)
├── similar_content_ids (UUID[])
└── model_version

posting_time_recommendations
├── id (PK)
├── organization_id (FK → organizations)
├── social_account_id (FK → social_accounts, nullable)
├── provider, day_of_week
├── heatmap, top_slots, worst_slots (JSONB)
├── sample_size, confidence_level
└── calculated_at, expires_at

audience_insights
├── id (PK)
├── organization_id (FK → organizations)
├── social_account_id (FK → social_accounts, nullable)
├── insight_type, insight_data (JSONB)
├── source_comment_count, confidence_score
└── period_start, period_end, expires_at

ai_generation_context
├── id (PK)
├── organization_id (FK → organizations)
├── context_type (UNIQUE per org)
├── context_data (JSONB)
└── max_tokens

brand_safety_checks
├── id (PK)
├── organization_id (FK → organizations)
├── content_id (FK → contents)
├── provider, overall_status, overall_score
├── checks (JSONB)
└── model_used, tokens_input, tokens_output

brand_safety_rules
├── id (PK)
├── organization_id (FK → organizations)
├── rule_type, rule_config (JSONB)
├── severity, is_active
└── created_at, updated_at

calendar_suggestions
├── id (PK)
├── organization_id (FK → organizations)
├── period_start, period_end
├── suggestions (JSONB), based_on (JSONB)
├── status, accepted_items (JSONB)
└── generated_at, expires_at

content_gap_analyses
├── id (PK)
├── organization_id (FK → organizations)
├── competitor_query_ids (UUID[])
├── analysis_period_start, analysis_period_end
├── our_topics, competitor_topics (JSONB)
├── gaps, opportunities (JSONB)
└── generated_at, expires_at

--- AI Learning & Feedback Loop (ADR-017) ---

generation_feedback
├── id (PK)
├── organization_id (FK → organizations)
├── user_id (FK → users)
├── ai_generation_id
├── action (accepted/edited/rejected)
├── original_output, edited_output, diff_summary (JSONB)
├── content_id, generation_type
└── time_to_decision_ms

prompt_templates
├── id (PK)
├── organization_id (FK → organizations, nullable = system-wide)
├── generation_type, version (UNIQUE per org+type)
├── name, system_prompt, user_prompt_template
├── variables (JSONB), is_active, is_default
├── performance_score (0-100)
├── total_uses, total_accepted, total_edited, total_rejected
└── created_by (FK → users)

prompt_experiments
├── id (PK)
├── organization_id (FK → organizations)
├── generation_type, name, status
├── variant_a_id, variant_b_id (FK → prompt_templates)
├── traffic_split, min_sample_size
├── variant_a_uses, variant_a_accepted
├── variant_b_uses, variant_b_accepted
├── winner_id (FK → prompt_templates)
└── confidence_level, started_at, completed_at

prediction_validations
├── id (PK)
├── organization_id (FK → organizations)
├── prediction_id (FK → performance_predictions, UNIQUE)
├── content_id, provider
├── predicted_score, actual_engagement_rate
├── actual_normalized_score, absolute_error
├── prediction_accuracy (0-100)
├── metrics_snapshot (JSONB)
└── validated_at, metrics_captured_at

org_style_profiles
├── id (PK)
├── organization_id (FK → organizations)
├── generation_type (UNIQUE per org)
├── sample_size, confidence_level
├── tone_preferences, length_preferences (JSONB)
├── vocabulary_preferences, structure_preferences (JSONB)
├── hashtag_preferences (JSONB)
├── style_summary (TEXT), style_embedding (VECTOR(1536))
└── generated_at, expires_at
```
