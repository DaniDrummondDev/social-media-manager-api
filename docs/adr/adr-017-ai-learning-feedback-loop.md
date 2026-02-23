# ADR-017: AI Learning & Feedback Loop

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-23
- **Decisores:** Equipe de arquitetura
- **Complementa:** ADR-009 (Laravel AI SDK), ADR-016 (Multi-Provider AI), ADR-018 (Native CRM Connectors)

## Contexto

O sistema possui peças individuais de inteligência artificial — Content DNA (embeddings + centroid profiling), Performance Prediction (score 0-100), Audience Feedback Loop (insights de comentários injetados em prompts), analytics pipeline (métricas pós-publicação). Contudo, essas peças operam de forma **isolada**, sem uma camada de orquestração que as conecte em um loop de auto-aperfeiçoamento.

### Gaps identificados

| O que falta | Impacto |
|------------|---------|
| `ai_generations` registra input/output mas **não** a ação do usuário (aceitar/editar/rejeitar) | Não sabemos se a IA está gerando conteúdo útil |
| `contents.embedding` existe mas **não** é usado durante geração | Geração ignora o histórico de sucesso da organização |
| Prompts não são versionados, sem A/B testing | Sem mecanismo de melhoria contínua de prompts |
| `performance_predictions` geradas mas **nunca** validadas contra métricas reais | Modelo de predição não é calibrado |
| Sem conceito de aprender com edições do usuário | IA não se adapta ao estilo da organização |

### Oportunidade

Nenhum concorrente — brasileiro (mLabs, Etus, Reportei) ou global (Hootsuite, Buffer, Sprout Social, Later) — oferece IA que aprende com o comportamento do usuário. Isso é um **diferencial competitivo real** e um **moat** para retenção.

---

## Decisão

Implementar um **AI Learning & Feedback Loop** com 6 níveis ativos + 1 nível futuro, integrado aos bounded contexts existentes (Content AI #5, AI Intelligence #12 e Engagement & Automation #8), sem criar novo bounded context.

### Visão Geral dos 7 Níveis

```
Nível 1: Generation Feedback Tracking
  Registrar aceitar/editar/rejeitar de cada geração IA.
  Calcular diff estruturado quando usuário edita.
  Custo: $0 (puro database write).

Nível 2: RAG (Retrieval-Augmented Generation)
  Antes de gerar, buscar top performers similares via pgvector.
  Injetar exemplos reais no prompt como contexto.
  Custo: ~$0.0005/geração (tokens extras no prompt).

Nível 3: Prompt Optimization Engine
  Versionar prompt templates com A/B testing.
  Auto-selecionar melhor template por acceptance rate.
  Custo: $0 (contadores + z-test estatístico).

Nível 4: Prediction Accuracy Feedback
  7 dias após publicação, comparar score previsto vs métricas reais.
  Rastrear acurácia ao longo do tempo.
  Custo: $0 (comparação SQL).

Nível 5: Organization Style Learning
  Analisar padrões de edição do usuário.
  Construir perfil de estilo por organização.
  Custo: ~$0.0003/semana (LLM summary via GPT-4o-mini).

Nível 6: CRM Intelligence Feedback (ADR-018)
  Conectar dados de conversão do CRM ao conteúdo social.
  Atribuir receita/deals a conteúdos que geraram engajamento.
  Enriquecer RAG com dados de conversão (conteúdo que vende > conteúdo que engaja).
  Custo: $0 (comparação SQL + context injection).

Nível 7: Fine-tuning Pipeline (FUTURO)
  Usar pares (input, output editado) como dataset de treinamento.
  Fine-tune por vertical/nicho. Documentado mas não implementado.
```

### Fluxo Completo de Integração

```
Usuário solicita geração
    │
    ▼
┌──────────────────────────────────────────────────────┐
│  1. Resolve prompt template (N3)                     │
│     → melhor performer ou variante A/B               │
│  2. Busca top performers similares (N2 — RAG)        │
│     → pgvector cosine similarity                     │
│  3. Carrega style profile da org (N5)                │
│     → preferências de tom, tamanho, vocabulário      │
│  4. Carrega audience context (EXISTENTE)             │
│     → ai_generation_context table                    │
│  5. Chama TextGenerator (EXISTENTE — ADR-016)        │
│     → AIProviderFactory resolve provider             │
│  6. Registra em ai_generations                       │
│     → template_id, rag_context, style_context        │
└────────────────┬─────────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  Usuário aceita / edita / rejeita                    │
│                                                      │
│  7. Registra feedback (N1)                           │
│     → generation_feedback table                      │
│  8. Atualiza contadores do template (N3)             │
│  9. Avalia experimento se ativo (N3)                 │
│  10. Calcula diff se editado (N1 → alimenta N5)     │
└────────────────┬─────────────────────────────────────┘
                 │
    ... 7 dias após publicação ...
                 │
                 ▼
┌──────────────────────────────────────────────────────┐
│  11. MetricsSynced event (EXISTENTE)                 │
│      → Compara prediction vs métricas reais (N4)     │
│      → Cria prediction_validations record            │
└──────────────────────────────────────────────────────┘

    ... quando CRM reporta deal/conversão ...

┌──────────────────────────────────────────────────────┐
│  12. CrmDealCreated / CrmContactSynced (N6)          │
│      → Atribui conversão ao conteúdo social de       │
│        origem (crm_conversion_attributions)           │
│      → Conteúdo com conversão ganha boost no RAG     │
│      → Segmentos CRM enriquecem audience context     │
└────────────────┬─────────────────────────────────────┘
                 │
    ... batch semanal ...

┌──────────────────────────────────────────────────────┐
│  13. Recalcula performance_score dos templates (N3)  │
│  14. Regenera org_style_profile (N5)                 │
│  15. Atualiza ai_generation_context (N2+N5+N6)       │
│      → RAG examples + style data + CRM insights      │
│                                                      │
│  Próxima geração é melhor ←──────────────────────────│
└──────────────────────────────────────────────────────┘
```

---

### Nível 1 — Generation Feedback Tracking

#### Tabela: `generation_feedback`

```sql
CREATE TYPE feedback_action_type AS ENUM ('accepted', 'edited', 'rejected');

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

CREATE INDEX idx_gen_feedback_org_type
    ON generation_feedback (organization_id, generation_type, action, created_at DESC);

CREATE INDEX idx_gen_feedback_generation
    ON generation_feedback (ai_generation_id);

CREATE INDEX idx_gen_feedback_edited
    ON generation_feedback (organization_id, created_at DESC)
    WHERE action = 'edited';
```

**Campos-chave:**

- `diff_summary`: JSONB com `{changes: [{field, before, after}], change_ratio: 0.0-1.0}`. Ratio = Levenshtein distance / tamanho original.
- `time_to_decision_ms`: Tempo entre geração e feedback (aceite rápido = alta qualidade).
- `content_id`: NULL se rejeitado; referência ao conteúdo onde o output foi usado se aceito/editado.

---

### Nível 2 — RAG para Content Generation

Utiliza infraestrutura **existente** (pgvector, `contents.embedding`, `SimilaritySearchInterface`) para enriquecer gerações com exemplos reais de sucesso.

#### Fluxo RAG

```
Antes da geração:
1. Extrair tópico/keywords do request
2. Buscar via cosine similarity em contents.embedding
3. Filtrar: published, engagement_rate > mediana da org
4. Limitar: 5 resultados (Professional+) ou 3 (Creator)
5. Formatar como "Exemplos de sucesso" no prompt
6. Registrar IDs usados em ai_generations.rag_context_used
```

#### Cache via `ai_generation_context`

A tabela `ai_generation_context` (já existente) recebe novo `context_type = 'rag_examples'` — cache semanal dos top performers por tópico frequente da organização. Em tempo real, se cache não cobre o tópico, faz query pgvector direta.

---

### Nível 3 — Prompt Optimization Engine

#### Tabela: `prompt_templates`

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

CREATE INDEX idx_prompt_templates_active
    ON prompt_templates (organization_id, generation_type, is_active, performance_score DESC NULLS LAST)
    WHERE is_active = TRUE;
```

**Regras-chave:**

- `organization_id = NULL` → template global do sistema (seed no deploy).
- `performance_score = (accepted + edited × 0.7) / total_uses × 100`. Recalculado semanalmente.
- Auto-seleção: template com maior `performance_score` e mínimo 20 uses.
- Versões são imutáveis — editar cria nova versão.

#### Tabela: `prompt_experiments`

```sql
CREATE TYPE experiment_status_type AS ENUM ('draft', 'running', 'completed', 'canceled');

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

CREATE INDEX idx_experiments_active
    ON prompt_experiments (organization_id, generation_type)
    WHERE status = 'running';
```

**Regras-chave:**

- Máximo 1 experimento `running` por (organization_id, generation_type).
- Mínimo `min_sample_size` gerações por variante antes de concluir (default: 50).
- Significância estatística via two-proportion z-test. Vencedor declarado com `confidence_level >= 0.95`.
- Ao completar, template vencedor é ativado como default; perdedor desativado.

---

### Nível 4 — Prediction Accuracy Feedback

#### Tabela: `prediction_validations`

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

CREATE INDEX idx_prediction_validations_org
    ON prediction_validations (organization_id, validated_at DESC);

CREATE INDEX idx_prediction_validations_accuracy
    ON prediction_validations (organization_id, prediction_accuracy)
    WHERE prediction_accuracy IS NOT NULL;
```

**Regras-chave:**

- 1 validação por predição (UNIQUE on `prediction_id`).
- Trigger: evento `MetricsSynced` (existente) quando métricas de 7 dias estão disponíveis.
- `actual_normalized_score`: engagement rate normalizado como percentile rank × 100 na distribuição histórica da própria organização.
- `prediction_accuracy = 100 - |predicted_score - actual_normalized_score|`.
- Mínimo 10 validações para exibir métricas de acurácia (MAE, correlação).

---

### Nível 5 — Organization Style Learning

#### Tabela: `org_style_profiles`

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

CREATE INDEX idx_style_profiles_org
    ON org_style_profiles (organization_id, generation_type)
    WHERE expires_at > NOW();
```

**Campos JSONB detalhados:**

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

**Regras-chave:**

- Mínimo 10 edições (`action = 'edited'`) para gerar perfil.
- Confidence: low (<10 edits), medium (10-50), high (>50+).
- `style_summary`: resumo em linguagem natural gerado por GPT-4o-mini (max 200 tokens).
- TTL 14 dias (mais longo que Content DNA 7 dias — estilo muda menos).
- Injetado no prompt como "Preferências de estilo da organização".
- Desativável via `PUT /api/v1/ai/settings` (`style_learning_enabled: false`).

---

### Nível 6 — CRM Intelligence Feedback

Conecta dados de conversão dos CRMs nativos (ADR-018) ao pipeline de aprendizado da IA, permitindo que o sistema aprenda quais conteúdos **geram resultado de negócio** (leads, deals, receita) — não apenas engajamento.

#### Tabela: `crm_conversion_attributions`

```sql
CREATE TABLE crm_conversion_attributions (
    id                  UUID            PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID            NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    crm_connection_id   UUID            NOT NULL REFERENCES crm_connections(id) ON DELETE CASCADE,
    content_id          UUID            NOT NULL,
    crm_entity_type     VARCHAR(50)     NOT NULL,        -- deal, contact
    crm_entity_id       VARCHAR(255)    NOT NULL,
    attribution_type    VARCHAR(50)     NOT NULL,         -- direct_engagement, lead_capture, deal_closed
    attribution_value   DECIMAL(12,2)   NULL,             -- Valor monetário do deal (se aplicável)
    currency            VARCHAR(3)      NULL,             -- BRL, USD, etc.
    crm_stage           VARCHAR(100)    NULL,             -- Pipeline stage no CRM
    interaction_data    JSONB           NOT NULL DEFAULT '{}', -- Dados da interação social que originou
    attributed_at       TIMESTAMPTZ     NOT NULL DEFAULT NOW(),
    created_at          TIMESTAMPTZ     NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_crm_conv_attr_org_content
    ON crm_conversion_attributions (organization_id, content_id, created_at DESC);

CREATE INDEX idx_crm_conv_attr_org_type
    ON crm_conversion_attributions (organization_id, attribution_type, attributed_at DESC);
```

**Campos-chave:**

- `attribution_type`:
  - `direct_engagement` — comentário/interação social gerou contato no CRM.
  - `lead_capture` — conteúdo originou lead identificado no CRM.
  - `deal_closed` — deal associado ao conteúdo foi fechado (tem `attribution_value`).
- `interaction_data`: `{comment_id, post_external_id, provider, interaction_type, interaction_at}`.
- `attribution_value`: preenchido apenas para `deal_closed` — valor monetário do deal.

#### Fluxos CRM → IA

```
CRM → Social Media Manager (via webhook do CRM ou sync bidirecional)
──────────────────────────────────────────────────────────────────────

1. Deal fechado no CRM (CrmDealCreated)
   → Identifica conteúdo de origem via interaction_data
   → Cria crm_conversion_attribution (deal_closed)
   → Conteúdo ganha boost no RAG ranking (N2)
   → Atualiza prediction weights: conteúdo com conversão > alto engagement (N4)

2. Contato criado no CRM a partir de interação social (CrmContactSynced)
   → Cria crm_conversion_attribution (lead_capture)
   → Segmentos/tags do CRM injetados em ai_generation_context

3. Deal stage mudou no CRM (ProcessCrmWebhookJob)
   → Atualiza crm_stage na attribution
   → Se stage = won/closed, registra deal_closed com valor

4. Segmentos/tags atualizados no CRM
   → Atualiza ai_generation_context.crm_audience_segments
   → Próxima geração usa audiência mais refinada
```

#### Enriquecimento do RAG (N2)

Conteúdos com `crm_conversion_attributions` recebem **boost no ranking RAG**:

```
RAG Score Final = cosine_similarity × (1 + conversion_boost)

Onde:
  conversion_boost = 0.0 (sem atribuição)
  conversion_boost = 0.15 (lead_capture)
  conversion_boost = 0.30 (deal_closed)
  conversion_boost = 0.50 (deal_closed com attribution_value > mediana)
```

Isso faz com que a IA priorize conteúdos que **vendem**, não apenas que engajam.

#### Novos `context_type` em `ai_generation_context`

- `'crm_conversion_data'` — resumo de conversões por tipo de conteúdo. Cache semanal.
- `'crm_audience_segments'` — segmentos/tags do CRM para personalização de tom/audiência. Atualizado a cada sync inbound.

#### Enriquecimento do Prediction Accuracy (N4)

Validação de predição expandida para incluir métricas de conversão:

```sql
ALTER TABLE prediction_validations ADD COLUMN conversion_count INTEGER NULL;
ALTER TABLE prediction_validations ADD COLUMN conversion_value DECIMAL(12,2) NULL;
```

- `conversion_count`: quantas conversões CRM o conteúdo gerou nos 30 dias pós-publicação.
- `conversion_value`: valor monetário total atribuído ao conteúdo.
- Novo indicador: **Content-to-Revenue Attribution Rate** = conversions / impressions × 100.

---

### Nível 7 — Fine-tuning Pipeline (Futuro)

> **Nota:** Este nível é documentado conceitualmente. Não será implementado nas fases 1-4 do roadmap.

**Conceito:**

1. Exportar pares `(input, output_editado_pelo_usuario)` de `generation_feedback` onde `action = 'edited'`.
2. Enriquecer com dados de conversão CRM (N6): pares cujo conteúdo gerou deal_closed recebem peso maior no dataset.
3. Agrupar por vertical/nicho (não por organização individual — dataset muito pequeno).
4. Usar APIs de fine-tuning (OpenAI, Anthropic) para criar modelos especializados por vertical.
5. Registrar modelo fine-tuned no `AIProviderRegistry` como opção para a organização.

**Pré-requisitos:**

- Volume significativo de dados (>1.000 pares por vertical).
- Pipeline de anonimização (remover qualquer dado sensível dos pares de treino).
- Infraestrutura de avaliação (comparar modelo base vs fine-tuned).
- Dados de conversão CRM suficientes para ponderar dataset (opcional, mas melhora qualidade).

---

### Alterações em Tabelas Existentes

#### `ai_generations` (expandir)

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

#### `ai_generation_context` (novos context_types)

Novos valores de `context_type` (sem mudança no schema):

- `'rag_examples'` — cache de top performers por tópico frequente.
- `'org_style'` — resumo do style profile para injeção no prompt.
- `'crm_conversion_data'` — resumo de conversões CRM por tipo de conteúdo (N6).
- `'crm_audience_segments'` — segmentos/tags do CRM para personalização (N6).

---

### Novos Jobs

| Job | Queue | Frequência | Nível | Descrição |
|-----|-------|-----------|-------|-----------|
| `TrackGenerationFeedbackJob` | low | A cada feedback | N1 | Registra feedback, atualiza contadores do template |
| `CalculateDiffSummaryJob` | low | A cada edição | N1 | Computa diff estruturado entre original e editado |
| `RetrieveSimilarContentJob` | default | Pré-geração | N2 | Query pgvector para top performers similares |
| `CalculatePromptPerformanceJob` | low | Semanal | N3 | Recalcula performance_score de todos templates ativos |
| `EvaluatePromptExperimentJob` | low | Pós-feedback | N3 | Atualiza contadores, verifica significância, declara vencedor |
| `ValidatePredictionAccuracyJob` | low | 7d pós-publicação | N4 | Compara predição vs métricas reais |
| `GenerateOrgStyleProfileJob` | low | Semanal | N5 | Analisa padrões de edição, gera perfil + summary LLM |
| `UpdateLearningContextJob` | low | Pós-style/prompt update | N2+N5+N6 | Atualiza ai_generation_context com RAG + style + CRM |
| `AttributeCrmConversionJob` | low | A cada CrmDealCreated/CrmContactSynced | N6 | Atribui conversão CRM ao conteúdo social de origem |
| `EnrichAIContextFromCrmJob` | low | Semanal | N6 | Agrega dados de conversão e segmentos CRM para ai_generation_context |
| `CleanupExpiredLearningDataJob` | low | Semanal | Todos | Expira style profiles, marca experimentos stale |

Todos os jobs seguem padrões existentes: idempotentes, carregam `organization_id`/`user_id`/`correlation_id`/`trace_id`, chamam Use Cases da Application Layer.

### Novos Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `GenerationFeedbackRecorded` | Usuário aceita/edita/rejeita | feedback_id, generation_id, organization_id, user_id, action, generation_type |
| `GenerationEdited` | Usuário edita output (subconjunto do acima) | feedback_id, generation_id, organization_id, diff_summary, change_ratio |
| `PromptTemplateCreated` | Nova versão de template criada | template_id, organization_id, generation_type, version |
| `PromptPerformanceCalculated` | Recálculo semanal de performance | template_id, organization_id, performance_score, total_uses |
| `PromptExperimentStarted` | A/B test inicia | experiment_id, organization_id, generation_type, variant_a_id, variant_b_id |
| `PromptExperimentCompleted` | A/B test atinge significância | experiment_id, organization_id, winner_id, confidence_level |
| `PredictionValidated` | Métricas reais comparadas com predição | validation_id, prediction_id, organization_id, predicted_score, actual_score, absolute_error |
| `OrgStyleProfileGenerated` | Perfil de estilo criado/atualizado | profile_id, organization_id, generation_type, sample_size, confidence_level |
| `CrmConversionAttributed` | Conversão CRM atribuída a conteúdo | attribution_id, organization_id, content_id, crm_entity_type, attribution_type, attribution_value |
| `CrmAIContextEnriched` | Contexto IA enriquecido com dados CRM | organization_id, context_types_updated, conversion_count, segments_count |
| `LearningContextUpdated` | ai_generation_context atualizado | organization_id, context_types_updated |

### Novos Async Listeners

| Evento existente | Novo Listener | Descrição |
|-----------------|---------------|-----------|
| `PostPublished` | `SchedulePredictionValidation` | Agenda validação para 7 dias após publicação |
| `MetricsSynced` | `ValidatePredictionIfDue` | Executa validação se 7+ dias desde publicação |
| `PromptExperimentCompleted` | `ActivateWinningTemplate` | Ativa template vencedor como default |
| `OrgStyleProfileGenerated` | `UpdateLearningContext` | Atualiza cache de contexto |
| `CrmDealCreated` | `AttributeCrmConversion` | Atribui conversão ao conteúdo social de origem |
| `CrmContactSynced` | `AttributeCrmConversion` | Atribui lead capture ao conteúdo social de origem |
| `CrmConversionAttributed` | `UpdateLearningContext` | Atualiza RAG boost e contexto de conversão |

---

### Estrutura de Diretórios

```
src/Domain/ContentAI/
├── Entity/
│   ├── GenerationFeedback.php          (N1)
│   └── PromptTemplate.php              (N3 — Aggregate Root)
├── ValueObject/
│   ├── FeedbackAction.php              (accepted, edited, rejected)
│   ├── DiffSummary.php                 (changes, change_ratio)
│   └── PerformanceScore.php            (0-100)
├── Event/
│   ├── GenerationFeedbackRecorded.php
│   ├── GenerationEdited.php
│   ├── PromptTemplateCreated.php
│   ├── PromptPerformanceCalculated.php
│   ├── PromptExperimentStarted.php
│   └── PromptExperimentCompleted.php
└── Interface/
    ├── PromptTemplateResolverInterface.php
    └── RAGContextProviderInterface.php

src/Domain/AIIntelligence/
├── Entity/
│   ├── PredictionValidation.php        (N4)
│   ├── OrgStyleProfile.php             (N5 — Aggregate Root)
│   └── CrmConversionAttribution.php    (N6)
├── ValueObject/
│   ├── StylePreferences.php
│   ├── PredictionAccuracy.php
│   └── AttributionType.php             (direct_engagement, lead_capture, deal_closed)
├── Event/
│   ├── PredictionValidated.php
│   ├── OrgStyleProfileGenerated.php
│   ├── CrmConversionAttributed.php
│   ├── CrmAIContextEnriched.php
│   └── LearningContextUpdated.php
└── Interface/
    ├── StyleProfileAnalyzerInterface.php
    ├── PredictionValidatorInterface.php
    └── CrmIntelligenceProviderInterface.php

src/Application/ContentAI/UseCase/
├── RecordGenerationFeedbackUseCase.php
├── ResolvePromptTemplateUseCase.php
├── CreatePromptExperimentUseCase.php
├── EvaluateExperimentUseCase.php
└── CalculatePromptPerformanceUseCase.php

src/Application/AIIntelligence/UseCase/
├── RetrieveSimilarContentUseCase.php   (RAG)
├── ValidatePredictionUseCase.php
├── GenerateStyleProfileUseCase.php
├── AttributeCrmConversionUseCase.php   (N6)
├── EnrichAIContextFromCrmUseCase.php   (N6)
└── UpdateLearningContextUseCase.php
```

---

### Mapeamento por Plano de Assinatura

| Feature | Free | Creator | Professional | Agency |
|---------|------|---------|-------------|--------|
| Generation Feedback (coleta) | ✅ | ✅ | ✅ | ✅ |
| RAG (exemplos similares) | ❌ | ✅ Basic (3) | ✅ Full (5) | ✅ Full (5) |
| Prompt templates custom | ❌ | ❌ | ✅ | ✅ |
| Prompt A/B testing | ❌ | ❌ | ❌ | ✅ |
| Auto-otimização de prompts | ❌ | ❌ | ✅ | ✅ |
| Style Learning | ❌ | ❌ | ✅ | ✅ |
| Prediction Accuracy tracking | ❌ | ❌ | ❌ | ✅ |
| CRM Intelligence (conversão→IA) | ❌ | ❌ | ❌ | ✅ |
| Fine-tuning pipeline | ❌ | ❌ | ❌ | 🔜 Futuro |

> **Nota:** Generation Feedback (N1) é coletado para **todos** os planos porque tem custo zero e alimenta a melhoria dos templates globais do sistema.
> CRM Intelligence (N6) requer CRM conector ativo (ADR-018) e é exclusivo do plano Agency, pois conecta dados de conversão/receita ao pipeline de IA.

### Custo Mensal Estimado por Plano

| Operação | Modelo/Recurso | Custo unitário | Free | Creator | Professional | Agency |
|----------|---------------|---------------|------|---------|-------------|--------|
| Feedback recording | PostgreSQL | ~$0.0000 | $0 | $0 | $0 | $0 |
| RAG query | pgvector SQL | ~$0.0000 | $0 | $0 | $0 | $0 |
| RAG extra tokens | GPT-4o prompt | ~$0.0005/gen | $0 | $0.10 | $0.25 | $2.50 |
| Diff calculation | PHP Levenshtein | ~$0.0000 | $0 | $0 | $0 | $0 |
| Prompt performance | PostgreSQL | ~$0.0000 | $0 | $0 | $0 | $0 |
| Style profile LLM | GPT-4o-mini | ~$0.0003/semana | $0 | $0 | $0.005 | $0.005 |
| CRM attribution | PostgreSQL | ~$0.0000 | $0 | $0 | $0 | $0 |
| CRM context enrichment | PostgreSQL | ~$0.0000 | $0 | $0 | $0 | $0 |
| **Total mensal** | | | **$0** | **~$0.10** | **~$0.26** | **~$2.51** |

> Impacto nas margens: **negligível** (< 1% em todos os planos). O Learning Loop é um diferencial de alto valor a custo quase zero. CRM Intelligence (N6) tem custo zero adicional (puro database operations) e agrega valor substancial ao plano Agency.

---

## Alternativas Consideradas

### Alternativa 1: Machine Learning Pipeline Dedicado

Construir pipeline de ML com treinamento de modelos custom (scikit-learn, PyTorch) para predição e otimização.

**Rejeitada porque:**
- Complexidade de infraestrutura desproporcional ao benefício.
- Requer equipe de ML dedicada.
- Os 6 níveis propostos entregam ~80% do valor com ~20% da complexidade.

### Alternativa 2: Apenas RAG (Nível 2)

Implementar somente a busca de conteúdo similar, sem feedback tracking, prompt optimization ou style learning.

**Rejeitada porque:**
- RAG sem feedback loop não melhora ao longo do tempo.
- Perde o diferencial competitivo de "IA que aprende".
- Os outros níveis têm custo quase zero e adicionam valor significativo.

### Alternativa 3: Fine-tuning Desde o Início

Investir em fine-tuning de modelos por organização desde a primeira versão.

**Rejeitada porque:**
- Requer volume grande de dados (>1.000 pares por vertical).
- Custo de fine-tuning por organização é proibitivo.
- Os níveis 1-6 são pré-requisitos (geram os dados para fine-tuning).
- Será implementado como Nível 7 quando houver volume suficiente.

---

## Consequências

### Positivas

- **Diferencial competitivo único** — nenhum concorrente oferece IA que aprende com o usuário.
- **Retenção superior** — quanto mais o usuário usa, melhor a IA fica → lock-in positivo.
- **Custo quase zero** — a maioria dos níveis são operações de database, não chamadas de IA.
- **Reutiliza infraestrutura existente** — pgvector, ai_generation_context, analytics pipeline.
- **Melhoria contínua automática** — templates globais melhoram com uso de todos os usuários.
- **Ponte CRM↔IA inédita** — nenhum concorrente conecta dados de conversão CRM ao pipeline de geração de conteúdo. Conteúdo otimizado para venda, não apenas engajamento.

### Negativas

- **Cold start para novas organizações** — sem histórico, não há feedback/style/DNA para usar.
- **Complexidade adicional** — 6 tabelas, 11 jobs, 11 eventos.
- **Prompt templates requerem seed** — templates iniciais precisam ser criados e mantidos.
- **Latência potencial no RAG** — query pgvector antes da geração pode adicionar ~100-200ms.
- **CRM Intelligence depende de conexão ativa** — sem CRM conectado (ADR-018), N6 fica inativo.

### Riscos

| Risco | Probabilidade | Mitigação |
|-------|-------------|-----------|
| Cold start sem dados suficientes | Alta (novas orgs) | Graceful degradation: skip RAG/style se dados insuficientes, usar templates globais |
| Latência RAG impactando UX | Média | Cache via `ai_generation_context`, fallback para geração sem RAG se timeout |
| Style learning conflita com tom explícito do usuário | Baixa | Tom explícito no request sempre prevalece sobre style profile |
| Prompt experiment com amostra enviesada | Baixa | min_sample_size de 50, split 50/50, z-test com confidence 0.95 |
| Prediction normalization entre orgs heterogêneas | Média | Normalizar contra distribuição da **própria** org, nunca cross-org |
| Atribuição CRM incorreta (falso positivo) | Média | Atribuição conservadora: apenas conversões com interaction_data rastreável. Sem inferência. |
| CRM desconectado perde dados de conversão | Baixa | Graceful degradation: N6 desativado silenciosamente, outros níveis continuam normais |

---

## Referências

- ADR-009: Laravel AI SDK (Prism) — base para geração de texto
- ADR-016: Multi-Provider AI — factory e registry de providers
- ADR-018: Native CRM Connectors — conectores nativos que alimentam N6
- `.claude/skills/06-domain/ai-intelligence.md` — Content DNA, Performance Prediction, Audience Feedback Loop
- `.claude/skills/06-domain/ai-content-generation.md` — Tipos de geração, regras de negócio
- `.claude/skills/06-domain/ai-learning-loop.md` — Regras de negócio do Learning Loop
- `.claude/skills/03-integrations/ai-integration.md` — Arquitetura de integração IA
