# 08 — Mídia

[← Voltar ao índice](00-index.md)

---

## POST /api/v1/media

Faz upload de uma mídia (imagem ou vídeo).

**Autenticação:** Bearer token
**Content-Type:** `multipart/form-data`
**Rate Limit:** 20 req/min

### Request

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `file` | file | Sim | Formatos: JPG, PNG, WEBP, GIF, MP4, MOV |
| | | | Tamanho: imagem ≤ 10MB, vídeo ≤ 500MB |

### Response — 201 Created

```json
{
  "data": {
    "id": "bb0e8400-e29b-41d4-a716-446655440000",
    "type": "media",
    "attributes": {
      "file_name": "bb0e8400.jpg",
      "original_name": "promo-black-friday.jpg",
      "mime_type": "image/jpeg",
      "file_size": 245000,
      "width": 1080,
      "height": 1080,
      "duration_seconds": null,
      "thumbnail_url": "https://storage.example.com/thumbnails/bb0e8400.jpg",
      "url": "https://storage.example.com/media/bb0e8400.jpg",
      "scan_status": "pending",
      "compatibility": {
        "instagram_feed": true,
        "instagram_story": true,
        "instagram_reel": false,
        "tiktok": false,
        "youtube": false,
        "youtube_thumbnail": true
      },
      "created_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

### Response — 201 Created (vídeo)

```json
{
  "data": {
    "id": "dd0e8400-e29b-41d4-a716-446655440000",
    "type": "media",
    "attributes": {
      "file_name": "dd0e8400.mp4",
      "original_name": "reel-promo.mp4",
      "mime_type": "video/mp4",
      "file_size": 15000000,
      "width": 1080,
      "height": 1920,
      "duration_seconds": 28,
      "thumbnail_url": "https://storage.example.com/thumbnails/dd0e8400.jpg",
      "url": "https://storage.example.com/media/dd0e8400.mp4",
      "scan_status": "pending",
      "compatibility": {
        "instagram_feed": true,
        "instagram_story": true,
        "instagram_reel": true,
        "tiktok": true,
        "youtube": true,
        "youtube_short": true
      },
      "created_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

### Notas

- `scan_status` começa como `pending`. Mídia só pode ser usada em publicação quando `clean`.
- `compatibility` é calculado com base em tipo, dimensões e duração vs limites de cada rede.
- O upload gera thumbnail automaticamente.

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | VALIDATION_ERROR | Formato não suportado |
| 400 | VALIDATION_ERROR | Tamanho excede limite |
| 413 | VALIDATION_ERROR | Payload muito grande |
| 429 | RATE_LIMIT_EXCEEDED | Mais de 20 uploads/min |

---

## GET /api/v1/media

Lista mídias do usuário.

**Autenticação:** Bearer token

### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `type` | string | — | `image` ou `video` |
| `mime_type` | string | — | Ex: `image/jpeg`, `video/mp4` |
| `search` | string | — | Busca por nome original |
| `compatible_with` | string | — | Provider: retorna apenas mídias compatíveis (ex: `tiktok`) |
| `from` | datetime | — | Data início |
| `to` | datetime | — | Data fim |
| `sort` | string | `-created_at` | Ordenação: `created_at`, `file_name`, `file_size` |
| `per_page` | integer | 20 | Itens por página (máx: 50 para mídia) |
| `cursor` | string | — | Cursor |

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "bb0e8400-...",
      "type": "media",
      "attributes": {
        "original_name": "promo-black-friday.jpg",
        "mime_type": "image/jpeg",
        "file_size": 245000,
        "width": 1080,
        "height": 1080,
        "duration_seconds": null,
        "thumbnail_url": "https://storage.example.com/thumbnails/bb0e8400.jpg",
        "scan_status": "clean",
        "usage_count": 2,
        "created_at": "2026-02-15T10:30:00Z"
      }
    },
    {
      "id": "dd0e8400-...",
      "type": "media",
      "attributes": {
        "original_name": "reel-promo.mp4",
        "mime_type": "video/mp4",
        "file_size": 15000000,
        "width": 1080,
        "height": 1920,
        "duration_seconds": 28,
        "thumbnail_url": "https://storage.example.com/thumbnails/dd0e8400.jpg",
        "scan_status": "clean",
        "usage_count": 1,
        "created_at": "2026-02-15T10:30:00Z"
      }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": false,
    "next_cursor": null,
    "storage_used": {
      "total_bytes": 15245000,
      "images_bytes": 245000,
      "videos_bytes": 15000000,
      "total_files": 2
    }
  }
}
```

---

## GET /api/v1/media/{id}

Retorna detalhes de uma mídia.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "id": "bb0e8400-...",
    "type": "media",
    "attributes": {
      "file_name": "bb0e8400.jpg",
      "original_name": "promo-black-friday.jpg",
      "mime_type": "image/jpeg",
      "file_size": 245000,
      "width": 1080,
      "height": 1080,
      "duration_seconds": null,
      "thumbnail_url": "https://storage.example.com/thumbnails/bb0e8400.jpg",
      "url": "https://storage.example.com/media/bb0e8400.jpg",
      "scan_status": "clean",
      "scanned_at": "2026-02-15T10:30:30Z",
      "checksum": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
      "compatibility": {
        "instagram_feed": true,
        "instagram_story": true,
        "instagram_reel": false,
        "tiktok": false,
        "youtube": false,
        "youtube_thumbnail": true
      },
      "used_in_contents": [
        {
          "content_id": "cc0e8400-...",
          "content_title": "Mega promoção!",
          "campaign_name": "Black Friday",
          "status": "draft"
        }
      ],
      "created_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

## DELETE /api/v1/media/{id}

Exclui uma mídia (soft delete).

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "message": "Mídia excluída com sucesso.",
    "purge_at": "2026-03-17T10:30:00Z"
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 409 | RESOURCE_CONFLICT | Mídia vinculada a conteúdo agendado |

---

## POST /api/v1/media/{id}/restore

Restaura uma mídia excluída (dentro do período de carência).

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "message": "Mídia restaurada com sucesso."
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 404 | RESOURCE_NOT_FOUND | Mídia não encontrada ou já purgada |
| 410 | RESOURCE_NOT_FOUND | Período de carência expirado |
