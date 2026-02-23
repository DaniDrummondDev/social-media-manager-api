# 12 — Social Listening

[← Voltar ao índice](00-index.md)

> **Fase:** 3 (Sprint 9)
>
> Monitoramento de menções à marca, keywords, hashtags e concorrentes **fora do conteúdo próprio** da organização nas redes sociais. Usa adapter pattern flexível — APIs oficiais + provedores terceiros via interface unificada.

---

## POST /api/v1/listening/queries

Cria uma query de monitoramento.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "name": "Menções à marca",
  "type": "mention",
  "value": "@modaonline",
  "platforms": ["instagram", "tiktok", "youtube"],
  "language_filter": "pt_BR"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 2-200 caracteres |
| `type` | string | Sim | `keyword`, `hashtag`, `mention`, `competitor` |
| `value` | string | Sim | 2-200 caracteres. Para hashtag, incluir `#` |
| `platforms` | string[] | Sim | Mín 1 plataforma: `instagram`, `tiktok`, `youtube` |
| `language_filter` | string | Não | `pt_BR`, `en_US`, `es_ES`. Null = todos os idiomas |

### Response — 201 Created

```json
{
  "data": {
    "id": "aa0e8400-e29b-41d4-a716-446655440000",
    "type": "listening_query",
    "attributes": {
      "name": "Menções à marca",
      "type": "mention",
      "value": "@modaonline",
      "platforms": ["instagram", "tiktok", "youtube"],
      "language_filter": "pt_BR",
      "is_active": true,
      "last_fetched_at": null,
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 402 | PLAN_LIMIT_REACHED | Limite de queries de listening do plano atingido |
| 422 | VALIDATION_ERROR | Dados inválidos |

---

## GET /api/v1/listening/queries

Lista queries de monitoramento da organização.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `type` | string | — | `keyword`, `hashtag`, `mention`, `competitor` |
| `is_active` | boolean | — | Filtrar por status ativo/inativo |
| `platform` | string | — | Filtrar por plataforma |
| `sort` | string | `-created_at` | `created_at`, `name`, `last_fetched_at` |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "aa0e8400-...",
      "type": "listening_query",
      "attributes": {
        "name": "Menções à marca",
        "type": "mention",
        "value": "@modaonline",
        "platforms": ["instagram", "tiktok", "youtube"],
        "is_active": true,
        "mentions_count_24h": 12,
        "mentions_count_7d": 87,
        "sentiment_summary_7d": {
          "positive": 52,
          "neutral": 28,
          "negative": 7
        },
        "last_fetched_at": "2026-02-23T10:00:00Z",
        "created_at": "2026-02-20T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": false,
    "next_cursor": null
  }
}
```

---

## PATCH /api/v1/listening/queries/{id}

Atualiza uma query de monitoramento.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "platforms": ["instagram", "tiktok"],
  "language_filter": null
}
```

### Response — 200 OK

Retorna query atualizada.

---

## POST /api/v1/listening/queries/{id}/pause

Pausa uma query (para de buscar menções).

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "listening_query",
    "attributes": {
      "id": "aa0e8400-...",
      "is_active": false
    }
  }
}
```

---

## POST /api/v1/listening/queries/{id}/resume

Retoma uma query pausada.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

```json
{
  "data": {
    "type": "listening_query",
    "attributes": {
      "id": "aa0e8400-...",
      "is_active": true
    }
  }
}
```

---

## DELETE /api/v1/listening/queries/{id}

Exclui uma query e todas as menções associadas.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 204 No Content

### Notas

- Menções associadas são excluídas em cascata (hard delete).
- Alertas vinculados a esta query são atualizados.

---

## GET /api/v1/listening/mentions

Lista menções capturadas.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `query_id` | uuid | — | Filtrar por query de origem |
| `platform` | string | — | `instagram`, `tiktok`, `youtube` |
| `sentiment` | string | — | `positive`, `neutral`, `negative` |
| `is_read` | boolean | — | Lida/não lida |
| `flagged` | boolean | — | Apenas marcadas |
| `search` | string | — | Busca textual no conteúdo da menção |
| `from` | datetime | — | Mencionada a partir de |
| `to` | datetime | — | Mencionada até |
| `sort` | string | `-mentioned_at` | `mentioned_at`, `reach_estimate`, `engagement_count` |
| `per_page` | integer | 20 | Itens por página (máx: 50) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "bb0e8400-e29b-41d4-a716-446655440000",
      "type": "mention",
      "attributes": {
        "query": {
          "id": "aa0e8400-...",
          "name": "Menções à marca",
          "type": "mention"
        },
        "platform": "instagram",
        "author": {
          "name": "Julia Santos",
          "username": "@juliasantos",
          "external_id": "12345678",
          "followers_count": 15000
        },
        "content_text": "Adorei a coleção nova da @modaonline! Super recomendo 🔥",
        "content_url": "https://instagram.com/p/xxx",
        "media_urls": ["https://instagram.com/p/xxx/media/1"],
        "sentiment": "positive",
        "reach_estimate": 15000,
        "engagement_count": 234,
        "is_read": false,
        "flagged": false,
        "mentioned_at": "2026-02-23T09:15:00Z",
        "captured_at": "2026-02-23T09:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJtZW50aW9uZWRfYXQiOi..."
  }
}
```

---

## GET /api/v1/listening/mentions/{id}

Retorna detalhes de uma menção.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Response — 200 OK

Retorna menção completa (mesmo formato da listagem, com todos os campos).

---

## POST /api/v1/listening/mentions/{id}/read

Marca menção como lida.

**Autenticação:** Bearer token

### Response — 204 No Content

---

## POST /api/v1/listening/mentions/{id}/flag

Marca/desmarca menção como destaque.

**Autenticação:** Bearer token

### Request

```json
{
  "flagged": true
}
```

### Response — 204 No Content

---

## POST /api/v1/listening/alerts

Cria um alerta de listening.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "name": "Alerta de crise — sentimento negativo",
  "query_ids": ["aa0e8400-..."],
  "condition": {
    "type": "negative_sentiment_spike",
    "threshold": 10,
    "window_minutes": 60
  },
  "notification_channels": [
    {
      "type": "email",
      "target": "marketing@agencia.com"
    },
    {
      "type": "webhook",
      "target": "https://hooks.slack.com/xxx"
    }
  ],
  "cooldown_minutes": 120
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 2-200 caracteres |
| `query_ids` | uuid[] | Sim | Mín 1 query ativa |
| `condition.type` | string | Sim | `volume_spike`, `negative_sentiment_spike`, `keyword_detected`, `influencer_mention` |
| `condition.threshold` | integer | Sim | > 0 |
| `condition.window_minutes` | integer | Sim | 15-1440 (15 min a 24h) |
| `notification_channels` | array | Sim | Mín 1 canal |
| `notification_channels[].type` | string | Sim | `email`, `webhook`, `in_app` |
| `notification_channels[].target` | string | Sim | Email válido, URL (webhook) ou user_id (in_app) |
| `cooldown_minutes` | integer | Não | 30-1440. Default: 60 |

### Response — 201 Created

```json
{
  "data": {
    "id": "cc0e8400-e29b-41d4-a716-446655440000",
    "type": "listening_alert",
    "attributes": {
      "name": "Alerta de crise — sentimento negativo",
      "condition": {
        "type": "negative_sentiment_spike",
        "threshold": 10,
        "window_minutes": 60
      },
      "notification_channels": [
        { "type": "email", "target": "marketing@agencia.com" },
        { "type": "webhook", "target": "https://hooks.slack.com/xxx" }
      ],
      "is_active": true,
      "cooldown_minutes": 120,
      "last_triggered_at": null,
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

---

## GET /api/v1/listening/alerts

Lista alertas de listening.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `is_active` | boolean | — | Filtrar por status |
| `sort` | string | `-created_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

Formato padrão de listagem paginada.

---

## PATCH /api/v1/listening/alerts/{id}

Atualiza um alerta.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 200 OK

Retorna alerta atualizado.

---

## DELETE /api/v1/listening/alerts/{id}

Exclui um alerta.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Response — 204 No Content

---

## GET /api/v1/listening/dashboard

Dashboard consolidado de social listening.

**Autenticação:** Bearer token
**Roles:** owner, admin, member (read-only)

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `query_ids` | uuid[] | — | Filtrar por queries (vírgula-separated). Omitir = todas |
| `period` | string | `7d` | `24h`, `7d`, `30d`, `custom` |
| `from` | datetime | — | Quando `period=custom` |
| `to` | datetime | — | Quando `period=custom` |

### Response — 200 OK

```json
{
  "data": {
    "type": "listening_dashboard",
    "attributes": {
      "period": {
        "from": "2026-02-16T00:00:00Z",
        "to": "2026-02-23T00:00:00Z"
      },
      "overview": {
        "total_mentions": 87,
        "mentions_change_percent": 12.5,
        "total_reach_estimate": 450000,
        "avg_sentiment_score": 0.72
      },
      "sentiment_breakdown": {
        "positive": 52,
        "neutral": 28,
        "negative": 7
      },
      "platform_breakdown": {
        "instagram": 45,
        "tiktok": 32,
        "youtube": 10
      },
      "trend_data": [
        { "date": "2026-02-16", "mentions": 10, "sentiment_avg": 0.75 },
        { "date": "2026-02-17", "mentions": 15, "sentiment_avg": 0.68 },
        { "date": "2026-02-18", "mentions": 8, "sentiment_avg": 0.80 }
      ],
      "top_mentions": [
        {
          "id": "bb0e8400-...",
          "platform": "instagram",
          "author_username": "@influencer_grande",
          "author_followers_count": 500000,
          "content_text": "...",
          "sentiment": "positive",
          "reach_estimate": 500000,
          "mentioned_at": "2026-02-22T15:00:00Z"
        }
      ],
      "recent_alerts": [
        {
          "alert_id": "cc0e8400-...",
          "alert_name": "Alerta de crise",
          "triggered_at": "2026-02-21T14:00:00Z",
          "condition_type": "negative_sentiment_spike"
        }
      ]
    }
  }
}
```

---

## POST /api/v1/listening/reports/export

Gera relatório exportável de social listening.

**Autenticação:** Bearer token
**Roles:** owner, admin

### Request

```json
{
  "query_ids": ["aa0e8400-..."],
  "period_start": "2026-02-01T00:00:00Z",
  "period_end": "2026-02-28T23:59:59Z",
  "format": "pdf"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `query_ids` | uuid[] | Sim | Mín 1 query |
| `period_start` | datetime | Sim | Início do período |
| `period_end` | datetime | Sim | Fim do período, posterior ao início |
| `format` | string | Sim | `pdf`, `csv` |

### Response — 202 Accepted

```json
{
  "data": {
    "id": "dd0e8400-e29b-41d4-a716-446655440000",
    "type": "listening_report",
    "attributes": {
      "status": "processing",
      "format": "pdf"
    }
  },
  "meta": {
    "message": "Relatório sendo gerado. Você será notificado quando estiver pronto."
  }
}
```

---

## GET /api/v1/listening/reports/{id}

Verifica status e baixa relatório gerado.

**Autenticação:** Bearer token

### Response — 200 OK (processing)

```json
{
  "data": {
    "id": "dd0e8400-...",
    "type": "listening_report",
    "attributes": {
      "status": "processing",
      "format": "pdf",
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Response — 200 OK (ready)

```json
{
  "data": {
    "id": "dd0e8400-...",
    "type": "listening_report",
    "attributes": {
      "status": "ready",
      "format": "pdf",
      "download_url": "https://storage.example.com/reports/dd0e8400.pdf?token=xxx",
      "download_expires_at": "2026-02-24T10:30:00Z",
      "total_mentions": 87,
      "sentiment_breakdown": {
        "positive": 52,
        "neutral": 28,
        "negative": 7
      },
      "created_at": "2026-02-23T10:30:00Z"
    }
  }
}
```

### Notas

- Link de download válido por 24 horas.
- Geração é assíncrona (job em fila).
- Relatórios expiram e são removidos após 7 dias.
