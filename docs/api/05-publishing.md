# 05 — Agendamento & Publicação

[← Voltar ao índice](00-index.md)

---

## POST /api/v1/contents/{contentId}/schedule

Agenda publicação de um conteúdo em uma ou mais redes.

**Autenticação:** Bearer token

### Request

```json
{
  "scheduled_at": "2026-11-20T10:00:00Z",
  "social_account_ids": [
    "770e8400-e29b-41d4-a716-446655440000",
    "880e8400-e29b-41d4-a716-446655440000"
  ]
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `scheduled_at` | datetime | Sim | Mín. 5 min no futuro |
| `social_account_ids` | uuid[] | Sim | Contas conectadas e ativas |

### Response — 201 Created

```json
{
  "data": {
    "content_id": "cc0e8400-...",
    "scheduled_posts": [
      {
        "id": "sp-001-...",
        "social_account_id": "770e8400-...",
        "provider": "instagram",
        "username": "@marina.social",
        "scheduled_at": "2026-11-20T10:00:00Z",
        "status": "pending"
      },
      {
        "id": "sp-002-...",
        "social_account_id": "880e8400-...",
        "provider": "tiktok",
        "username": "@marina_tiktok",
        "scheduled_at": "2026-11-20T10:00:00Z",
        "status": "pending"
      }
    ],
    "validation_warnings": [
      {
        "provider": "tiktok",
        "message": "O TikTok aceita apenas vídeos. A imagem não será publicada nesta rede."
      }
    ]
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | PUBLISHING_ERROR | scheduled_at é no passado |
| 400 | PUBLISHING_ERROR | Conteúdo sem mídia compatível com a rede |
| 404 | RESOURCE_NOT_FOUND | Conteúdo ou conta social não encontrado |
| 409 | RESOURCE_CONFLICT | Conteúdo já agendado para a mesma rede |
| 422 | VALIDATION_ERROR | Limite diário da rede será excedido |

---

## POST /api/v1/contents/{contentId}/publish-now

Publica imediatamente em uma ou mais redes.

**Autenticação:** Bearer token

### Request

```json
{
  "social_account_ids": [
    "770e8400-e29b-41d4-a716-446655440000"
  ]
}
```

### Response — 202 Accepted

```json
{
  "data": {
    "content_id": "cc0e8400-...",
    "scheduled_posts": [
      {
        "id": "sp-003-...",
        "social_account_id": "770e8400-...",
        "provider": "instagram",
        "scheduled_at": null,
        "status": "dispatched",
        "message": "Publicação enviada para processamento."
      }
    ]
  }
}
```

> Status `202 Accepted` indica que a publicação foi enfileirada.
> Use o endpoint de status para acompanhar.

---

## GET /api/v1/scheduled-posts/{id}

Retorna detalhes de um agendamento.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "id": "sp-001-...",
    "type": "scheduled_post",
    "attributes": {
      "content_id": "cc0e8400-...",
      "social_account_id": "770e8400-...",
      "provider": "instagram",
      "username": "@marina.social",
      "scheduled_at": "2026-11-20T10:00:00Z",
      "published_at": null,
      "status": "pending",
      "external_post_id": null,
      "external_post_url": null,
      "attempts": 0,
      "max_attempts": 3,
      "last_error": null,
      "content": {
        "title": "Mega promoção Black Friday!",
        "body": "Aproveite descontos de até 70%...",
        "media_count": 1
      },
      "created_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

## GET /api/v1/scheduled-posts

Lista agendamentos do usuário.

**Autenticação:** Bearer token

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | `pending`, `dispatched`, `publishing`, `published`, `failed`, `cancelled` |
| `provider` | string | — | `instagram`, `tiktok`, `youtube` |
| `campaign_id` | uuid | — | Filtro por campanha |
| `from` | datetime | — | Agendamentos a partir de |
| `to` | datetime | — | Agendamentos até |
| `sort` | string | `scheduled_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "sp-001-...",
      "type": "scheduled_post",
      "attributes": {
        "content_id": "cc0e8400-...",
        "provider": "instagram",
        "username": "@marina.social",
        "scheduled_at": "2026-11-20T10:00:00Z",
        "status": "pending",
        "content_title": "Mega promoção Black Friday!",
        "campaign_name": "Black Friday 2026"
      }
    }
  ],
  "meta": { "..." }
}
```

---

## GET /api/v1/scheduled-posts/calendar

Retorna agendamentos agrupados por dia (visualização de calendário).

**Autenticação:** Bearer token

### Query Parameters

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `month` | integer | Sim* | Mês (1-12) |
| `year` | integer | Sim* | Ano |
| `start_date` | date | Sim* | Alternativa a month/year |
| `end_date` | date | Sim* | Alternativa a month/year |
| `provider` | string | Não | Filtro por rede |
| `campaign_id` | uuid | Não | Filtro por campanha |

> *Enviar `month`+`year` OU `start_date`+`end_date`.

### Response — 200 OK

```json
{
  "data": {
    "period": {
      "start": "2026-11-01",
      "end": "2026-11-30"
    },
    "days": [
      {
        "date": "2026-11-20",
        "posts": [
          {
            "id": "sp-001-...",
            "scheduled_at": "2026-11-20T10:00:00Z",
            "provider": "instagram",
            "status": "pending",
            "content_title": "Mega promoção!"
          },
          {
            "id": "sp-002-...",
            "scheduled_at": "2026-11-20T10:00:00Z",
            "provider": "tiktok",
            "status": "pending",
            "content_title": "Mega promoção!"
          }
        ],
        "count": 2
      },
      {
        "date": "2026-11-25",
        "posts": [ "..." ],
        "count": 1
      }
    ],
    "total_posts": 3
  }
}
```

---

## DELETE /api/v1/scheduled-posts/{id}

Cancela um agendamento.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "message": "Agendamento cancelado com sucesso.",
    "content_id": "cc0e8400-...",
    "content_new_status": "draft"
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Agendamento locked (< 1 min para publicação) |
| 409 | RESOURCE_CONFLICT | Status `publishing` (em processamento) |

---

## PUT /api/v1/scheduled-posts/{id}

Reagenda uma publicação.

**Autenticação:** Bearer token

### Request

```json
{
  "scheduled_at": "2026-11-21T14:00:00Z"
}
```

### Response — 200 OK

```json
{
  "data": {
    "id": "sp-001-...",
    "scheduled_at": "2026-11-21T14:00:00Z",
    "status": "pending",
    "message": "Reagendado com sucesso."
  }
}
```

---

## POST /api/v1/scheduled-posts/{id}/retry

Retenta publicação de um agendamento que falhou.

**Autenticação:** Bearer token

### Response — 202 Accepted

```json
{
  "data": {
    "id": "sp-001-...",
    "status": "dispatched",
    "message": "Retentativa enviada para processamento."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | PUBLISHING_ERROR | Status não é `failed` |
| 400 | PUBLISHING_ERROR | Erro permanente (não retentável) |
