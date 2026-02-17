# 02 — Contas Sociais

[← Voltar ao índice](00-index.md)

---

## GET /api/v1/social-networks

Lista redes sociais disponíveis para conexão.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": [
    {
      "provider": "instagram",
      "name": "Instagram",
      "icon_url": "https://assets.example.com/icons/instagram.svg",
      "status": "available",
      "required_account_type": "Business ou Creator",
      "scopes": [
        "instagram_basic",
        "instagram_content_publish",
        "instagram_manage_comments",
        "instagram_manage_insights"
      ],
      "supported_content_types": ["image", "video", "carousel", "reel", "story"],
      "limits": {
        "max_daily_posts": 25,
        "max_description_chars": 2200,
        "max_hashtags": 30,
        "max_image_size_mb": 8,
        "max_video_size_mb": 1024,
        "max_video_duration_seconds": 5400
      }
    },
    {
      "provider": "tiktok",
      "name": "TikTok",
      "icon_url": "https://assets.example.com/icons/tiktok.svg",
      "status": "available",
      "required_account_type": null,
      "scopes": ["video.upload", "video.publish", "video.list", "comment.list"],
      "supported_content_types": ["video"],
      "limits": {
        "max_daily_posts": null,
        "max_title_chars": 150,
        "max_description_chars": 4000,
        "max_hashtags": 5,
        "max_video_size_mb": 4096,
        "max_video_duration_seconds": 600
      }
    },
    {
      "provider": "youtube",
      "name": "YouTube",
      "icon_url": "https://assets.example.com/icons/youtube.svg",
      "status": "available",
      "required_account_type": null,
      "scopes": ["youtube.upload", "youtube.readonly", "youtube.force-ssl"],
      "supported_content_types": ["video", "short"],
      "limits": {
        "max_daily_posts": 6,
        "max_title_chars": 100,
        "max_description_chars": 5000,
        "max_hashtags": 15,
        "max_video_size_mb": 262144,
        "max_video_duration_seconds": 43200
      }
    }
  ]
}
```

---

## GET /api/v1/social-accounts/{provider}/redirect

Inicia o fluxo OAuth2 — redireciona para a rede social.

**Autenticação:** Bearer token

### Path Parameters

| Parâmetro | Tipo | Valores |
|-----------|------|---------|
| `provider` | string | `instagram`, `tiktok`, `youtube` |

### Response — 200 OK

```json
{
  "data": {
    "redirect_url": "https://www.facebook.com/v21.0/dialog/oauth?client_id=...&redirect_uri=...&state=...&scope=..."
  }
}
```

> O frontend deve redirecionar o usuário para a `redirect_url`.

---

## GET /api/v1/social-accounts/{provider}/callback

Callback do OAuth2 — troca o authorization code por tokens.

**Autenticação:** Bearer token (via state parameter)

### Query Parameters

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `code` | string | Authorization code do provider |
| `state` | string | State parameter para CSRF protection |

### Response — 201 Created

```json
{
  "data": {
    "id": "770e8400-e29b-41d4-a716-446655440000",
    "type": "social_account",
    "attributes": {
      "provider": "instagram",
      "username": "@marina.social",
      "display_name": "Marina Silva",
      "profile_picture_url": "https://instagram.com/...",
      "status": "connected",
      "scopes": ["instagram_basic", "instagram_content_publish", "..."],
      "connected_at": "2026-02-15T10:30:00Z",
      "token_expires_at": "2026-04-16T10:30:00Z"
    }
  }
}
```

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | SOCIAL_ACCOUNT_ERROR | Authorization code inválido |
| 400 | SOCIAL_ACCOUNT_ERROR | Conta Instagram não é Business/Creator |
| 409 | RESOURCE_CONFLICT | Já existe uma conta deste provider conectada |
| 422 | VALIDATION_ERROR | State parameter inválido (CSRF) |

---

## GET /api/v1/social-accounts

Lista contas sociais conectadas do usuário.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": [
    {
      "id": "770e8400-...",
      "type": "social_account",
      "attributes": {
        "provider": "instagram",
        "username": "@marina.social",
        "display_name": "Marina Silva",
        "profile_picture_url": "https://...",
        "status": "connected",
        "scopes": ["instagram_basic", "..."],
        "connected_at": "2026-02-15T10:30:00Z",
        "token_expires_at": "2026-04-16T10:30:00Z",
        "last_synced_at": "2026-02-15T09:00:00Z"
      }
    },
    {
      "id": "880e8400-...",
      "type": "social_account",
      "attributes": {
        "provider": "tiktok",
        "username": "@marina_tiktok",
        "display_name": "Marina",
        "profile_picture_url": "https://...",
        "status": "expired",
        "scopes": ["video.upload", "..."],
        "connected_at": "2026-01-10T08:00:00Z",
        "token_expires_at": "2026-02-14T08:00:00Z",
        "last_synced_at": "2026-02-14T07:00:00Z"
      }
    }
  ]
}
```

---

## GET /api/v1/social-accounts/{id}

Retorna detalhes de uma conta social específica.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "id": "770e8400-...",
    "type": "social_account",
    "attributes": {
      "provider": "instagram",
      "username": "@marina.social",
      "display_name": "Marina Silva",
      "profile_picture_url": "https://...",
      "status": "connected",
      "scopes": ["instagram_basic", "instagram_content_publish", "instagram_manage_comments", "instagram_manage_insights"],
      "connected_at": "2026-02-15T10:30:00Z",
      "token_expires_at": "2026-04-16T10:30:00Z",
      "last_synced_at": "2026-02-15T09:00:00Z",
      "metadata": {
        "facebook_page_id": "123456789",
        "instagram_account_id": "987654321",
        "account_type": "BUSINESS"
      }
    }
  }
}
```

---

## DELETE /api/v1/social-accounts/{id}

Desconecta uma conta social.

**Autenticação:** Bearer token

### Request

```json
{
  "confirm": true
}
```

### Response — 200 OK

```json
{
  "data": {
    "message": "Conta desconectada com sucesso.",
    "cancelled_schedules": 3
  }
}
```

### Side effects

- Token revogado no provider (quando suportado)
- Agendamentos pendentes cancelados
- Dados históricos (analytics, comentários) mantidos

### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | SOCIAL_ACCOUNT_ERROR | `confirm` não enviado |
| 404 | RESOURCE_NOT_FOUND | Conta não encontrada |

---

## POST /api/v1/social-accounts/{id}/reconnect

Inicia reconexão de uma conta com token expirado.

**Autenticação:** Bearer token

### Response — 200 OK

```json
{
  "data": {
    "redirect_url": "https://www.facebook.com/v21.0/dialog/oauth?..."
  }
}
```

> Mesmo fluxo do redirect, mas atualiza tokens da conta existente em vez de criar nova.
