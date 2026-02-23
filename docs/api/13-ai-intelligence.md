# 13 — AI Intelligence

[← Voltar ao índice](00-index.md)

> **Fase:** 2 (Sprints 10-11) e 3 (Sprints 12-13)

---

## Best Time to Post (Sprint 10)

### GET /api/v1/ai-intelligence/best-times

Retorna horários ótimos de publicação da organização.

**Autenticação:** Bearer token

#### Query Params

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `provider` | string | Não | Filtrar por rede (instagram, tiktok, youtube) |
| `social_account_id` | UUID | Não | Filtrar por conta específica |

#### Response — 200 OK

```json
{
  "data": {
    "top_slots": [
      {
        "day": 2,
        "day_name": "Tuesday",
        "hour": 19,
        "avg_engagement_rate": 0.054,
        "sample_size": 23
      },
      {
        "day": 4,
        "day_name": "Thursday",
        "hour": 12,
        "avg_engagement_rate": 0.048,
        "sample_size": 18
      }
    ],
    "worst_slots": [
      {
        "day": 0,
        "day_name": "Sunday",
        "hour": 3,
        "avg_engagement_rate": 0.002,
        "sample_size": 5
      }
    ],
    "confidence_level": "medium",
    "sample_size": 87,
    "calculated_at": "2026-02-20T06:00:00Z",
    "expires_at": "2026-02-27T06:00:00Z"
  }
}
```

---

### GET /api/v1/ai-intelligence/best-times/heatmap

Retorna heatmap completo 7 dias × 24 horas.

**Autenticação:** Bearer token

#### Query Params

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `provider` | string | Não | Filtrar por rede |
| `social_account_id` | UUID | Não | Filtrar por conta |

#### Response — 200 OK

```json
{
  "data": {
    "heatmap": [
      {
        "day": 0,
        "day_name": "Sunday",
        "hours": [
          {"hour": 0, "score": 12},
          {"hour": 1, "score": 8},
          {"hour": 8, "score": 45},
          {"hour": 12, "score": 72},
          {"hour": 19, "score": 85}
        ]
      }
    ],
    "provider": "instagram",
    "confidence_level": "high",
    "sample_size": 142,
    "calculated_at": "2026-02-20T06:00:00Z"
  }
}
```

---

### POST /api/v1/ai-intelligence/best-times/recalculate

Força recálculo dos horários ótimos.

**Autenticação:** Bearer token

#### Response — 202 Accepted

```json
{
  "data": {
    "message": "Recalculation queued. Results will be available shortly.",
    "estimated_completion": "2026-02-23T15:05:00Z"
  }
}
```

---

## Brand Safety & Compliance (Sprint 10)

### POST /api/v1/contents/{id}/safety-check

Executa verificação de Brand Safety em conteúdo.

**Autenticação:** Bearer token

#### Request

```json
{
  "providers": ["instagram", "tiktok"]
}
```

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `providers` | string[] | Não | Redes para verificação específica. Se omitido, verifica geral |

#### Response — 202 Accepted

```json
{
  "data": {
    "check_id": "aa1e8400-...",
    "content_id": "bb2e8400-...",
    "status": "pending",
    "message": "Safety check queued. Results will be available shortly."
  }
}
```

---

### GET /api/v1/contents/{id}/safety-checks

Retorna resultados de verificação de Brand Safety.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "aa1e8400-...",
      "content_id": "bb2e8400-...",
      "provider": null,
      "overall_status": "warning",
      "overall_score": 72,
      "checks": [
        {
          "category": "lgpd_compliance",
          "status": "passed",
          "message": null,
          "severity": null
        },
        {
          "category": "advertising_disclosure",
          "status": "warning",
          "message": "Conteúdo menciona produto sem disclosure de parceria. Considere adicionar #publi ou #ad.",
          "severity": "warning"
        },
        {
          "category": "platform_policy",
          "status": "passed",
          "message": null,
          "severity": null
        },
        {
          "category": "sensitivity",
          "status": "passed",
          "message": null,
          "severity": null
        },
        {
          "category": "profanity",
          "status": "passed",
          "message": null,
          "severity": null
        }
      ],
      "checked_at": "2026-02-23T14:30:00Z"
    }
  ]
}
```

---

### GET /api/v1/brand-safety/rules

Lista regras de Brand Safety da organização.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "cc3e8400-...",
      "rule_type": "blocked_word",
      "rule_config": {
        "words": ["grátis", "promoção fake"],
        "match_mode": "contains"
      },
      "severity": "block",
      "is_active": true,
      "created_at": "2026-02-10T10:00:00Z"
    },
    {
      "id": "dd4e8400-...",
      "rule_type": "required_disclosure",
      "rule_config": {
        "keywords": ["parceria", "patrocinado", "recebidos"],
        "disclosure_text": "#publi"
      },
      "severity": "warning",
      "is_active": true,
      "created_at": "2026-02-10T10:00:00Z"
    }
  ]
}
```

---

### POST /api/v1/brand-safety/rules

Cria regra de Brand Safety.

**Autenticação:** Bearer token

#### Request

```json
{
  "rule_type": "blocked_word",
  "rule_config": {
    "words": ["spam", "clickbait"],
    "match_mode": "contains"
  },
  "severity": "block"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `rule_type` | string | Sim | `blocked_word`, `required_disclosure`, `custom_check` |
| `rule_config` | object | Sim | Estrutura depende do rule_type |
| `severity` | string | Sim | `warning`, `block` |

#### Response — 201 Created

```json
{
  "data": {
    "id": "ee5e8400-...",
    "rule_type": "blocked_word",
    "rule_config": {"words": ["spam", "clickbait"], "match_mode": "contains"},
    "severity": "block",
    "is_active": true,
    "created_at": "2026-02-23T14:00:00Z"
  }
}
```

---

### PUT /api/v1/brand-safety/rules/{id}

Atualiza regra de Brand Safety.

### DELETE /api/v1/brand-safety/rules/{id}

Exclui regra de Brand Safety.

---

## Cross-Network Content Adaptation (Sprint 11)

### POST /api/v1/ai/adapt-content

Adapta conteúdo de uma rede para outras redes.

**Autenticação:** Bearer token
**Rate Limit:** 10 req/min

#### Request

```json
{
  "content_id": "ff6e8400-...",
  "source_network": "instagram",
  "target_networks": ["tiktok", "youtube"],
  "preserve_tone": true,
  "auto_apply_overrides": false
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `content_id` | UUID | Sim | Conteúdo existente da organização |
| `source_network` | string | Sim | Provider válido |
| `target_networks` | string[] | Sim | 1-5 providers válidos |
| `preserve_tone` | boolean | Não | Default: true |
| `auto_apply_overrides` | boolean | Não | Default: false. Se true, aplica resultado em content_network_overrides |

#### Response — 200 OK

```json
{
  "data": {
    "generation_id": "gg7e8400-...",
    "source_network": "instagram",
    "adaptations": {
      "tiktok": {
        "title": "POV: você descobre que marketing de conteúdo FUNCIONA 🔥",
        "description": "3 dicas que mudaram meu jogo no marketing digital #marketingdigital #dicasdenegocio #empreendedorismo",
        "hashtags": ["#marketingdigital", "#dicasdenegocio", "#empreendedorismo", "#tiktokbrasil"],
        "character_count": {"title": 58, "description": 98},
        "changes_summary": "Adaptado para estilo informal/POV do TikTok, hashtags populares na plataforma"
      },
      "youtube": {
        "title": "3 Estratégias de Marketing de Conteúdo que FUNCIONAM em 2026",
        "description": "Neste vídeo, compartilho as 3 estratégias de marketing de conteúdo que mais geraram resultados...",
        "hashtags": ["#marketingdigital", "#marketingdeconteudo", "#estrategiasdemarketing"],
        "character_count": {"title": 62, "description": 120},
        "changes_summary": "Título descritivo para YouTube SEO, descrição expandida com contexto"
      }
    },
    "usage": {
      "tokens_input": 350,
      "tokens_output": 280,
      "model": "gpt-4o"
    }
  }
}
```

---

## AI Content Calendar Planning (Sprint 11)

### POST /api/v1/ai-intelligence/calendar/suggest

Gera sugestões de calendário editorial.

**Autenticação:** Bearer token

#### Request

```json
{
  "period_start": "2026-03-01",
  "period_end": "2026-03-07",
  "target_networks": ["instagram", "tiktok"],
  "posts_per_week": 5
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `period_start` | date | Sim | >= hoje |
| `period_end` | date | Sim | > period_start, máximo 30 dias |
| `target_networks` | string[] | Não | Se omitido, usa todas as contas conectadas |
| `posts_per_week` | integer | Não | 1-14, default: 5 |

#### Response — 202 Accepted

```json
{
  "data": {
    "suggestion_id": "hh8e8400-...",
    "status": "generating",
    "message": "Calendar suggestions are being generated. Check back shortly."
  }
}
```

---

### GET /api/v1/ai-intelligence/calendar/suggestions

Lista sugestões de calendário geradas.

**Autenticação:** Bearer token
**Paginação:** cursor-based

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "hh8e8400-...",
      "period_start": "2026-03-01",
      "period_end": "2026-03-07",
      "status": "generated",
      "item_count": 5,
      "generated_at": "2026-02-23T14:30:00Z",
      "expires_at": "2026-03-02T14:30:00Z"
    }
  ],
  "meta": {
    "next_cursor": "eyJ..."
  }
}
```

---

### GET /api/v1/ai-intelligence/calendar/suggestions/{id}

Retorna detalhes de uma sugestão de calendário.

#### Response — 200 OK

```json
{
  "data": {
    "id": "hh8e8400-...",
    "period_start": "2026-03-01",
    "period_end": "2026-03-07",
    "status": "generated",
    "suggestions": [
      {
        "date": "2026-03-01",
        "topics": ["dicas de marketing digital"],
        "content_type": "carousel",
        "target_networks": ["instagram"],
        "reasoning": "Carrosséis educativos geraram +40% engagement nos últimos 30 dias",
        "priority": 1
      },
      {
        "date": "2026-03-03",
        "topics": ["bastidores da empresa"],
        "content_type": "reel",
        "target_networks": ["instagram", "tiktok"],
        "reasoning": "Conteúdo de bastidores tem alto engagement e não foi publicado há 2 semanas",
        "priority": 2
      }
    ],
    "based_on": {
      "top_performers_analyzed": 50,
      "schedule_gaps_found": 3,
      "existing_scheduled": 2
    },
    "generated_at": "2026-02-23T14:30:00Z",
    "expires_at": "2026-03-02T14:30:00Z"
  }
}
```

---

### POST /api/v1/ai-intelligence/calendar/suggestions/{id}/accept

Aceita itens de uma sugestão de calendário.

#### Request

```json
{
  "accepted_indexes": [0, 2, 4]
}
```

#### Response — 200 OK

```json
{
  "data": {
    "id": "hh8e8400-...",
    "status": "accepted",
    "accepted_count": 3,
    "total_count": 5
  }
}
```

---

## Content DNA Profiling (Sprint 12)

### POST /api/v1/ai-intelligence/content-profile/generate

Gera perfil de conteúdo da organização (assíncrono).

**Autenticação:** Bearer token

#### Request

```json
{
  "provider": "instagram",
  "social_account_id": "ii9e8400-..."
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `provider` | string | Não | Se omitido, analisa todas as redes |
| `social_account_id` | UUID | Não | Se omitido, analisa todas as contas |

#### Response — 202 Accepted

```json
{
  "data": {
    "message": "Content profile generation queued.",
    "estimated_completion": "2026-02-23T15:10:00Z"
  }
}
```

#### Error — 422 Unprocessable Entity

```json
{
  "error": {
    "code": "INSUFFICIENT_DATA",
    "message": "At least 10 published contents with embeddings are required. Currently: 7."
  }
}
```

---

### GET /api/v1/ai-intelligence/content-profile

Retorna perfil de conteúdo atual da organização.

#### Response — 200 OK

```json
{
  "data": {
    "id": "jj0e8400-...",
    "provider": "instagram",
    "total_contents_analyzed": 127,
    "top_themes": [
      {"theme": "marketing digital", "score": 0.85, "content_count": 34},
      {"theme": "dicas de negócios", "score": 0.72, "content_count": 28},
      {"theme": "bastidores", "score": 0.65, "content_count": 19}
    ],
    "engagement_patterns": {
      "avg_likes": 245,
      "avg_comments": 18,
      "avg_shares": 12,
      "best_content_types": ["carousel", "reel"]
    },
    "content_fingerprint": {
      "avg_length": 180,
      "hashtag_patterns": ["#marketingdigital", "#empreendedorismo"],
      "tone_distribution": {"casual": 0.45, "informative": 0.35, "fun": 0.20},
      "posting_frequency": 4.2
    },
    "high_performer_traits": {
      "avg_length_range": [150, 250],
      "hashtag_count_range": [5, 12],
      "includes_question": true,
      "includes_cta": true
    },
    "generated_at": "2026-02-20T06:00:00Z",
    "expires_at": "2026-02-27T06:00:00Z"
  }
}
```

---

### POST /api/v1/ai-intelligence/content-profile/recommend

Retorna recomendações de conteúdo baseadas no DNA.

#### Request

```json
{
  "topic": "lançamento de produto",
  "limit": 5
}
```

#### Response — 200 OK

```json
{
  "data": {
    "recommendations": [
      {
        "topic": "lançamento de produto com bastidores",
        "similarity_score": 0.89,
        "reasoning": "Conteúdos de bastidores + lançamento geraram 2x mais engagement que lançamentos diretos",
        "suggested_format": "reel",
        "reference_content_ids": ["kk1e8400-...", "ll2e8400-..."]
      }
    ]
  }
}
```

---

## Performance Prediction (Sprint 12)

### POST /api/v1/contents/{id}/predict-performance

Gera predição de performance para conteúdo.

**Autenticação:** Bearer token

#### Request

```json
{
  "providers": ["instagram", "tiktok"],
  "detailed": false
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `providers` | string[] | Sim | 1-5 providers válidos |
| `detailed` | boolean | Não | Default: false. Se true, inclui análise LLM (mais caro) |

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "mm3e8400-...",
      "content_id": "nn4e8400-...",
      "provider": "instagram",
      "overall_score": 78,
      "breakdown": {
        "content_similarity": 85,
        "timing": 70,
        "hashtags": 65,
        "length": 90,
        "media_type": 80
      },
      "similar_content_ids": ["oo5e8400-...", "pp6e8400-..."],
      "recommendations": [
        {
          "type": "timing",
          "message": "Considere publicar entre 19h-21h para maior engajamento nesta rede.",
          "impact_estimate": "+15% engagement"
        },
        {
          "type": "hashtags",
          "message": "Substitua #marketing por #marketingdigital2026 (tendência atual).",
          "impact_estimate": "+8% reach"
        }
      ],
      "model_version": "v1",
      "created_at": "2026-02-23T14:35:00Z"
    }
  ]
}
```

---

### GET /api/v1/contents/{id}/predictions

Lista predições existentes de um conteúdo.

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "mm3e8400-...",
      "provider": "instagram",
      "overall_score": 78,
      "created_at": "2026-02-23T14:35:00Z"
    },
    {
      "id": "qq7e8400-...",
      "provider": "tiktok",
      "overall_score": 62,
      "created_at": "2026-02-23T14:35:00Z"
    }
  ]
}
```

---

## Audience Feedback Loop (Sprint 13)

### GET /api/v1/ai-intelligence/audience-insights

Lista insights de audiência da organização.

**Autenticação:** Bearer token

#### Query Params

| Param | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `type` | string | Não | Filtrar por tipo de insight |
| `social_account_id` | UUID | Não | Filtrar por conta |

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "rr8e8400-...",
      "insight_type": "preferred_topics",
      "insight_data": {
        "topics": [
          {"name": "dicas práticas", "score": 0.92, "comment_count": 145},
          {"name": "bastidores", "score": 0.78, "comment_count": 89},
          {"name": "tutoriais", "score": 0.71, "comment_count": 67}
        ]
      },
      "source_comment_count": 847,
      "confidence_score": 0.85,
      "period_start": "2026-01-23T00:00:00Z",
      "period_end": "2026-02-23T00:00:00Z",
      "generated_at": "2026-02-20T06:00:00Z",
      "expires_at": "2026-02-27T06:00:00Z"
    },
    {
      "id": "ss9e8400-...",
      "insight_type": "audience_preferences",
      "insight_data": {
        "preferences": [
          {"category": "content_length", "value": "Preferem posts concisos (100-200 chars)", "confidence": 0.82},
          {"category": "media_type", "value": "Vídeos curtos (15-30s) geram mais comentários positivos", "confidence": 0.75},
          {"category": "cta_style", "value": "Perguntas diretas geram 3x mais comentários", "confidence": 0.91}
        ]
      },
      "source_comment_count": 847,
      "confidence_score": 0.82,
      "generated_at": "2026-02-20T06:00:00Z"
    }
  ]
}
```

---

### POST /api/v1/ai-intelligence/audience-insights/refresh

Força refresh dos insights de audiência.

#### Response — 202 Accepted

```json
{
  "data": {
    "message": "Audience insights refresh queued.",
    "estimated_completion": "2026-02-23T15:15:00Z"
  }
}
```

---

## Competitive Content Gap Analysis (Sprint 13)

### POST /api/v1/ai-intelligence/gap-analysis/generate

Gera análise de lacunas de conteúdo vs concorrentes.

**Autenticação:** Bearer token

#### Request

```json
{
  "competitor_query_ids": ["tt0e8400-...", "uu1e8400-..."],
  "period_days": 30
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `competitor_query_ids` | UUID[] | Sim | Queries de listening tipo `competitor` |
| `period_days` | integer | Não | 7-90, default: 30 |

#### Response — 202 Accepted

```json
{
  "data": {
    "analysis_id": "vv2e8400-...",
    "status": "generating",
    "message": "Gap analysis is being generated."
  }
}
```

---

### GET /api/v1/ai-intelligence/gap-analyses

Lista análises de lacunas geradas.

**Paginação:** cursor-based

---

### GET /api/v1/ai-intelligence/gap-analyses/{id}

Retorna detalhes de uma análise de lacunas.

#### Response — 200 OK

```json
{
  "data": {
    "id": "vv2e8400-...",
    "analysis_period": {
      "start": "2026-01-23T00:00:00Z",
      "end": "2026-02-23T00:00:00Z"
    },
    "our_topics": [
      {"topic": "marketing digital", "frequency": 12, "avg_engagement": 3.2},
      {"topic": "dicas de negócio", "frequency": 8, "avg_engagement": 2.8}
    ],
    "competitor_topics": [
      {"topic": "tendências 2026", "source_competitor": "@concorrente1", "frequency": 15, "avg_engagement": 4.1},
      {"topic": "ferramentas de IA", "source_competitor": "@concorrente2", "frequency": 10, "avg_engagement": 3.5}
    ],
    "gaps": [
      {
        "topic": "tendências 2026",
        "opportunity_score": 85,
        "competitor_count": 2,
        "recommendation": "Concorrentes publicam frequentemente sobre tendências do ano. Oportunidade de criar série de conteúdo."
      },
      {
        "topic": "ferramentas de IA",
        "opportunity_score": 72,
        "competitor_count": 1,
        "recommendation": "Tema com alto engagement no concorrente. Considere criar conteúdo comparativo ou tutorial."
      }
    ],
    "generated_at": "2026-02-23T14:45:00Z",
    "expires_at": "2026-03-02T14:45:00Z"
  }
}
```

---

### GET /api/v1/ai-intelligence/gap-analyses/{id}/opportunities

Retorna apenas oportunidades acionáveis (gaps com score > 50).

#### Response — 200 OK

```json
{
  "data": {
    "opportunities": [
      {
        "topic": "tendências 2026",
        "reason": "2 concorrentes publicam sobre este tema com engagement 28% acima da média deles",
        "suggested_content_type": "carousel",
        "estimated_impact": "Alto potencial — tema trending com baixa competição direta"
      }
    ],
    "total_gaps": 5,
    "actionable_opportunities": 3
  }
}
```

---

## Erros Comuns

| Código HTTP | Error Code | Descrição |
|-------------|-----------|-----------|
| 422 | `INSUFFICIENT_DATA` | Dados insuficientes para gerar análise (mínimos não atingidos) |
| 404 | `PROFILE_NOT_FOUND` | Content DNA Profile não existe para esta org/rede |
| 404 | `RESOURCE_NOT_FOUND` | Recurso não encontrado |
| 422 | `NO_COMPETITOR_QUERIES` | Nenhuma query de listening tipo `competitor` configurada |
| 503 | `AI_GENERATION_ERROR` | Provider de IA indisponível |
| 429 | `AI_RATE_LIMIT` | Rate limit de IA atingido |
| 403 | `BRAND_SAFETY_BLOCKED` | Conteúdo bloqueado por verificação de Brand Safety |
