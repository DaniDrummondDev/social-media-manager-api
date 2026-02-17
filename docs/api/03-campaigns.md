# 03 — Campanhas & Conteúdos

[← Voltar ao índice](00-index.md)

---

## Campanhas

### POST /api/v1/campaigns

Cria uma nova campanha.

**Autenticação:** Bearer token

#### Request

```json
{
  "name": "Black Friday 2026",
  "description": "Campanha de Black Friday com foco em promoções",
  "starts_at": "2026-11-20T00:00:00Z",
  "ends_at": "2026-11-30T23:59:59Z",
  "tags": ["black-friday", "promoção", "novembro"]
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 3-100 chars, único por usuário |
| `description` | string | Não | Máx 2000 chars |
| `starts_at` | datetime | Não | ISO 8601 |
| `ends_at` | datetime | Não | ISO 8601, posterior a starts_at |
| `tags` | string[] | Não | Máx 20 tags, cada uma 1-50 chars |

#### Response — 201 Created

```json
{
  "data": {
    "id": "990e8400-e29b-41d4-a716-446655440000",
    "type": "campaign",
    "attributes": {
      "name": "Black Friday 2026",
      "description": "Campanha de Black Friday com foco em promoções",
      "starts_at": "2026-11-20T00:00:00Z",
      "ends_at": "2026-11-30T23:59:59Z",
      "status": "draft",
      "tags": ["black-friday", "promoção", "novembro"],
      "stats": {
        "total_contents": 0,
        "draft": 0,
        "scheduled": 0,
        "published": 0,
        "failed": 0
      },
      "created_at": "2026-02-15T10:30:00Z",
      "updated_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

### GET /api/v1/campaigns

Lista campanhas do usuário.

**Autenticação:** Bearer token

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | Filtro: `draft`, `active`, `paused`, `completed` (aceita múltiplos: `active,paused`) |
| `search` | string | — | Busca por nome (ILIKE) |
| `tag` | string | — | Filtro por tag |
| `from` | date | — | Data início do período |
| `to` | date | — | Data fim do período |
| `sort` | string | `-created_at` | Ordenação: `created_at`, `name`, `starts_at` |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor de paginação |

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "990e8400-...",
      "type": "campaign",
      "attributes": {
        "name": "Black Friday 2026",
        "description": "Campanha de Black Friday...",
        "starts_at": "2026-11-20T00:00:00Z",
        "ends_at": "2026-11-30T23:59:59Z",
        "status": "draft",
        "tags": ["black-friday", "promoção"],
        "stats": {
          "total_contents": 5,
          "draft": 3,
          "scheduled": 2,
          "published": 0,
          "failed": 0
        },
        "created_at": "2026-02-15T10:30:00Z",
        "updated_at": "2026-02-15T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo..."
  }
}
```

---

### GET /api/v1/campaigns/{id}

Retorna detalhes de uma campanha.

**Autenticação:** Bearer token

#### Response — 200 OK

Mesmo formato do item individual da listagem, com `stats` detalhados.

---

### PUT /api/v1/campaigns/{id}

Atualiza uma campanha.

**Autenticação:** Bearer token

#### Request

```json
{
  "name": "Black Friday 2026 — Atualizada",
  "status": "active"
}
```

> Apenas os campos enviados são atualizados (partial update).

#### Response — 200 OK

Retorna a campanha atualizada.

---

### DELETE /api/v1/campaigns/{id}

Exclui uma campanha (soft delete).

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": {
    "message": "Campanha excluída com sucesso.",
    "cancelled_schedules": 2,
    "purge_at": "2026-03-17T10:30:00Z"
  }
}
```

---

### POST /api/v1/campaigns/{id}/duplicate

Duplica uma campanha com todas as peças.

**Autenticação:** Bearer token

#### Request

```json
{
  "name": "Black Friday 2026 (Cópia)"
}
```

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| `name` | string | Não | Nome da cópia. Padrão: "{original} (Cópia)" |

#### Response — 201 Created

```json
{
  "data": {
    "id": "aa0e8400-...",
    "type": "campaign",
    "attributes": {
      "name": "Black Friday 2026 (Cópia)",
      "status": "draft",
      "stats": {
        "total_contents": 5,
        "draft": 5,
        "scheduled": 0,
        "published": 0,
        "failed": 0
      },
      "..."
    }
  },
  "meta": {
    "duplicated_contents": 5
  }
}
```

---

### POST /api/v1/campaigns/{id}/restore

Restaura uma campanha excluída (dentro do período de carência).

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": {
    "message": "Campanha restaurada com sucesso.",
    "restored_contents": 3
  }
}
```

---

## Conteúdos

### POST /api/v1/campaigns/{campaignId}/contents

Cria uma peça de conteúdo em uma campanha.

**Autenticação:** Bearer token

#### Request

```json
{
  "title": "Mega promoção Black Friday!",
  "body": "Aproveite descontos de até 70% em toda a loja. Oferta válida enquanto durarem os estoques!",
  "hashtags": ["blackfriday", "promoção", "desconto", "oferta"],
  "media_ids": [
    "bb0e8400-e29b-41d4-a716-446655440000"
  ],
  "network_overrides": [
    {
      "provider": "instagram",
      "body": "🔥 Mega promoção Black Friday! Até 70% OFF em toda a loja!\n\nLink na bio 👆",
      "hashtags": ["blackfriday", "promoção", "desconto", "oferta", "blackfriday2026", "loja"]
    },
    {
      "provider": "tiktok",
      "title": "BLACK FRIDAY: até 70% OFF 🤯",
      "body": "Os melhores preços do ano estão aqui!"
    }
  ]
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `title` | string | Não | Máx 500 chars |
| `body` | string | Não | Máx 5000 chars |
| `hashtags` | string[] | Não | Sem `#`, máx 30 items |
| `media_ids` | uuid[] | Não | IDs de mídias existentes, mídia deve estar com scan_status=clean |
| `network_overrides` | object[] | Não | Override por rede |
| `network_overrides[].provider` | string | Sim | Provider válido |
| `network_overrides[].title` | string | Não | Máx chars conforme rede |
| `network_overrides[].body` | string | Não | Máx chars conforme rede |
| `network_overrides[].hashtags` | string[] | Não | Máx conforme rede |

#### Response — 201 Created

```json
{
  "data": {
    "id": "cc0e8400-...",
    "type": "content",
    "attributes": {
      "title": "Mega promoção Black Friday!",
      "body": "Aproveite descontos de até 70%...",
      "hashtags": ["blackfriday", "promoção", "desconto", "oferta"],
      "status": "draft",
      "campaign_id": "990e8400-...",
      "media": [
        {
          "id": "bb0e8400-...",
          "type": "image",
          "thumbnail_url": "https://...",
          "position": 0
        }
      ],
      "network_overrides": [
        {
          "provider": "instagram",
          "title": null,
          "body": "🔥 Mega promoção Black Friday!...",
          "hashtags": ["blackfriday", "promoção", "desconto", "oferta", "blackfriday2026", "loja"]
        },
        {
          "provider": "tiktok",
          "title": "BLACK FRIDAY: até 70% OFF 🤯",
          "body": "Os melhores preços do ano estão aqui!",
          "hashtags": null
        }
      ],
      "created_at": "2026-02-15T10:30:00Z",
      "updated_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

### GET /api/v1/campaigns/{campaignId}/contents

Lista conteúdos de uma campanha.

**Autenticação:** Bearer token

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `status` | string | — | Filtro por status |
| `sort` | string | `-created_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "cc0e8400-...",
      "type": "content",
      "attributes": {
        "title": "Mega promoção Black Friday!",
        "body": "Aproveite descontos de até 70%...",
        "hashtags": ["blackfriday", "promoção"],
        "status": "draft",
        "campaign_id": "990e8400-...",
        "media_count": 1,
        "has_overrides": true,
        "created_at": "2026-02-15T10:30:00Z"
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

### GET /api/v1/contents/{id}

Retorna detalhes de um conteúdo com overrides e mídias.

**Autenticação:** Bearer token

#### Response — 200 OK

Mesmo formato do POST response com todos os campos.

---

### PUT /api/v1/contents/{id}

Atualiza um conteúdo.

**Autenticação:** Bearer token

> Apenas conteúdos com status `draft` podem ser totalmente editados.
> Conteúdos `scheduled` podem ser editados, mas precisam cancelar e reagendar.

#### Request

```json
{
  "title": "Novo título",
  "hashtags": ["nova", "hashtag"]
}
```

#### Response — 200 OK

---

### DELETE /api/v1/contents/{id}

Exclui um conteúdo (soft delete).

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": {
    "message": "Conteúdo excluído com sucesso.",
    "cancelled_schedules": 1
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Conteúdo com status `publishing` (em processamento) |
