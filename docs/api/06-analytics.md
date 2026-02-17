# 06 — Analytics & Relatórios

[← Voltar ao índice](00-index.md)

---

## GET /api/v1/analytics/overview

Dashboard geral com métricas consolidadas de todas as redes.

**Autenticação:** Bearer token

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `period` | string | `30d` | `7d`, `30d`, `90d`, `custom` |
| `from` | date | — | Obrigatório se period=custom |
| `to` | date | — | Obrigatório se period=custom |

### Response — 200 OK

```json
{
  "data": {
    "period": {
      "from": "2026-01-16",
      "to": "2026-02-15"
    },
    "summary": {
      "total_posts": 42,
      "total_reach": 125000,
      "total_impressions": 310000,
      "total_engagement": 8500,
      "engagement_rate": 6.8,
      "total_comments": 1200,
      "total_likes": 5800,
      "total_shares": 900,
      "total_saves": 600
    },
    "comparison": {
      "period": {
        "from": "2025-12-17",
        "to": "2026-01-15"
      },
      "total_posts_change": 16.7,
      "total_reach_change": 23.4,
      "total_impressions_change": 18.2,
      "total_engagement_change": 31.5,
      "engagement_rate_change": 2.1
    },
    "by_network": [
      {
        "provider": "instagram",
        "posts": 20,
        "reach": 80000,
        "impressions": 200000,
        "engagement": 5500,
        "engagement_rate": 6.9
      },
      {
        "provider": "tiktok",
        "posts": 15,
        "reach": 35000,
        "views": 95000,
        "engagement": 2200,
        "engagement_rate": 6.3
      },
      {
        "provider": "youtube",
        "posts": 7,
        "views": 15000,
        "watch_time_hours": 450,
        "engagement": 800,
        "engagement_rate": 5.3
      }
    ],
    "trend": [
      { "date": "2026-01-16", "reach": 4200, "engagement": 280 },
      { "date": "2026-01-17", "reach": 3800, "engagement": 310 },
      "..."
    ],
    "top_contents": [
      {
        "content_id": "cc0e8400-...",
        "title": "Mega promoção!",
        "campaign_name": "Black Friday",
        "total_engagement": 1200,
        "best_network": "instagram"
      }
    ]
  }
}
```

---

## GET /api/v1/analytics/networks/{provider}

Métricas detalhadas de uma rede social específica.

**Autenticação:** Bearer token

### Path Parameters

| Parâmetro | Tipo | Valores |
|-----------|------|---------|
| `provider` | string | `instagram`, `tiktok`, `youtube` |

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `period` | string | `30d` | `7d`, `30d`, `90d`, `custom` |
| `from` | date | — | Obrigatório se period=custom |
| `to` | date | — | Obrigatório se period=custom |

### Response — 200 OK (Instagram)

```json
{
  "data": {
    "provider": "instagram",
    "period": { "from": "2026-01-16", "to": "2026-02-15" },
    "account": {
      "username": "@marina.social",
      "followers_count": 15200,
      "followers_gained": 340,
      "followers_lost": 45,
      "net_followers_change": 295,
      "profile_views": 2100
    },
    "content_metrics": {
      "total_posts": 20,
      "total_reach": 80000,
      "total_impressions": 200000,
      "total_likes": 3500,
      "total_comments": 800,
      "total_shares": 500,
      "total_saves": 700,
      "engagement_rate": 6.9
    },
    "comparison": {
      "reach_change": 15.2,
      "engagement_change": 22.3,
      "followers_change": 8.5
    },
    "top_5_contents": [
      {
        "content_id": "cc0e8400-...",
        "title": "Mega promoção!",
        "external_post_url": "https://instagram.com/p/...",
        "likes": 450,
        "comments": 120,
        "shares": 80,
        "saves": 95,
        "reach": 12000,
        "engagement_rate": 6.2,
        "published_at": "2026-02-10T10:00:00Z"
      }
    ],
    "best_posting_times": [
      { "day": "tuesday", "hour": 10, "avg_engagement": 8.2 },
      { "day": "thursday", "hour": 19, "avg_engagement": 7.8 },
      { "day": "saturday", "hour": 11, "avg_engagement": 7.5 }
    ],
    "followers_trend": [
      { "date": "2026-01-16", "count": 14905, "gained": 12, "lost": 2 },
      { "date": "2026-01-17", "count": 14918, "gained": 15, "lost": 2 },
      "..."
    ]
  }
}
```

---

## GET /api/v1/analytics/contents/{contentId}

Métricas detalhadas de um conteúdo específico.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "content_id": "cc0e8400-...",
    "title": "Mega promoção Black Friday!",
    "campaign_name": "Black Friday 2026",
    "published_at": "2026-11-20T10:00:00Z",
    "networks": [
      {
        "provider": "instagram",
        "external_post_url": "https://instagram.com/p/...",
        "published_at": "2026-11-20T10:00:12Z",
        "metrics": {
          "impressions": 15000,
          "reach": 12000,
          "likes": 450,
          "comments": 120,
          "shares": 80,
          "saves": 95,
          "engagement_rate": 6.2
        },
        "evolution": {
          "24h": { "reach": 8000, "engagement": 520 },
          "48h": { "reach": 10500, "engagement": 680 },
          "7d": { "reach": 12000, "engagement": 745 }
        }
      },
      {
        "provider": "tiktok",
        "external_post_url": "https://tiktok.com/@marina/video/...",
        "published_at": "2026-11-20T10:00:18Z",
        "metrics": {
          "views": 25000,
          "likes": 1200,
          "comments": 300,
          "shares": 150,
          "watch_time_seconds": 45000,
          "engagement_rate": 6.6
        },
        "evolution": {
          "24h": { "views": 15000, "engagement": 1100 },
          "48h": { "views": 22000, "engagement": 1500 },
          "7d": { "views": 25000, "engagement": 1650 }
        }
      }
    ],
    "ai_generation": {
      "used": true,
      "generation_id": "dd0e8400-...",
      "tone": "fun",
      "model": "gpt-4o"
    },
    "last_synced_at": "2026-02-15T09:00:00Z"
  }
}
```

---

## POST /api/v1/analytics/export

Solicita exportação de relatório.

**Autenticação:** Bearer token

### Request

```json
{
  "type": "overview",
  "format": "pdf",
  "period": "30d",
  "filters": {
    "provider": "instagram"
  }
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `type` | string | Sim | `overview`, `network`, `content` |
| `format` | string | Sim | `pdf`, `csv` |
| `period` | string | Não | `7d`, `30d`, `90d`, `custom` |
| `from` | date | Condicional | Obrigatório se period=custom |
| `to` | date | Condicional | Obrigatório se period=custom |
| `filters.provider` | string | Não | Filtro por rede |
| `filters.campaign_id` | uuid | Não | Filtro por campanha |
| `filters.content_id` | uuid | Não | Para type=content |

### Response — 202 Accepted

```json
{
  "data": {
    "export_id": "ex-001-...",
    "type": "overview",
    "format": "pdf",
    "status": "processing",
    "message": "Relatório sendo gerado. Você será notificado quando estiver pronto."
  }
}
```

---

## GET /api/v1/analytics/exports/{exportId}

Verifica status e obtém link de download de um relatório.

**Autenticação:** Bearer token

### Response — 200 OK (processando)

```json
{
  "data": {
    "export_id": "ex-001-...",
    "status": "processing",
    "created_at": "2026-02-15T10:30:00Z"
  }
}
```

### Response — 200 OK (pronto)

```json
{
  "data": {
    "export_id": "ex-001-...",
    "status": "ready",
    "format": "pdf",
    "file_size": 245000,
    "download_url": "https://storage.example.com/exports/ex-001-....pdf?token=...",
    "expires_at": "2026-02-16T10:30:00Z",
    "created_at": "2026-02-15T10:30:00Z",
    "completed_at": "2026-02-15T10:31:15Z"
  }
}
```

---

## GET /api/v1/analytics/exports

Lista exportações do usuário.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": [
    {
      "export_id": "ex-001-...",
      "type": "overview",
      "format": "pdf",
      "status": "ready",
      "expires_at": "2026-02-16T10:30:00Z",
      "created_at": "2026-02-15T10:30:00Z"
    }
  ]
}
```
