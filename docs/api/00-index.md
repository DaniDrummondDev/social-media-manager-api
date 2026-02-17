# API Specification — Social Media Manager

> **Versão:** v1
> **Base URL:** `https://api.socialmediamanager.com/api/v1`
> **Data:** 2026-02-15
> **Formato:** JSON (application/json)

---

## Índice

| # | Módulo | Endpoints |
|---|--------|-----------|
| 01 | [Autenticação](01-auth.md) | Register, Login, Refresh, Logout, Password Reset, 2FA, Profile |
| 02 | [Contas Sociais](02-social-accounts.md) | Connect, Callback, List, Disconnect, Status |
| 03 | [Campanhas & Conteúdos](03-campaigns.md) | CRUD Campaigns, CRUD Contents, Duplicate |
| 04 | [Geração com IA](04-ai.md) | Generate Title, Description, Hashtags, Full Content, Settings |
| 05 | [Publicação](05-publishing.md) | Schedule, Publish Now, Cancel, Reschedule, Calendar |
| 06 | [Analytics](06-analytics.md) | Overview, By Network, By Content, Export |
| 07 | [Engagement](07-engagement.md) | Comments, Automation Rules, Webhooks |
| 08 | [Mídia](08-media.md) | Upload, List, Delete |

---

## Autenticação

Todas as requisições autenticadas devem incluir o header:

```http
Authorization: Bearer <access_token>
```

O `access_token` é um JWT (RS256) com validade de 15 minutos. Use o endpoint de refresh para obter um novo par de tokens.

### Endpoints públicos (sem autenticação)

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/forgot-password`
- `POST /api/v1/auth/reset-password`
- `POST /api/v1/auth/verify-email`
- `GET  /api/health`

---

## Formato de resposta padrão

### Sucesso (recurso único)

```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "campaign",
    "attributes": {
      "name": "Black Friday 2026",
      "status": "active"
    }
  }
}
```

### Sucesso (coleção com paginação)

```json
{
  "data": [
    {
      "id": "550e8400-...",
      "type": "campaign",
      "attributes": { "..." }
    }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "eyJjcmVhdGVkX2F0Ijo...",
    "prev_cursor": null
  }
}
```

### Sucesso (sem conteúdo)

```http
HTTP/1.1 204 No Content
```

### Erro

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Os dados fornecidos são inválidos.",
    "details": [
      {
        "field": "email",
        "message": "O campo email já está sendo utilizado."
      },
      {
        "field": "password",
        "message": "A senha deve ter no mínimo 8 caracteres."
      }
    ]
  }
}
```

---

## Códigos de erro

### HTTP Status Codes

| Código | Significado | Quando |
|--------|-------------|--------|
| `200` | OK | Requisição bem-sucedida |
| `201` | Created | Recurso criado com sucesso |
| `204` | No Content | Operação bem-sucedida sem corpo |
| `400` | Bad Request | Requisição malformada |
| `401` | Unauthorized | Token ausente, inválido ou expirado |
| `403` | Forbidden | Sem permissão para o recurso |
| `404` | Not Found | Recurso não encontrado |
| `409` | Conflict | Conflito de estado (ex: mídia vinculada) |
| `422` | Unprocessable Entity | Erros de validação |
| `429` | Too Many Requests | Rate limit excedido |
| `500` | Internal Server Error | Erro inesperado no servidor |
| `503` | Service Unavailable | Serviço temporariamente indisponível |

### Códigos de erro da aplicação

| Código | Descrição |
|--------|-----------|
| `VALIDATION_ERROR` | Erros de validação nos campos |
| `AUTHENTICATION_ERROR` | Falha na autenticação |
| `AUTHORIZATION_ERROR` | Sem permissão |
| `RESOURCE_NOT_FOUND` | Recurso não encontrado |
| `RESOURCE_CONFLICT` | Conflito de estado |
| `RATE_LIMIT_EXCEEDED` | Limite de requisições excedido |
| `SOCIAL_ACCOUNT_ERROR` | Erro na comunicação com rede social |
| `SOCIAL_TOKEN_EXPIRED` | Token da rede social expirado |
| `AI_GENERATION_ERROR` | Erro na geração de conteúdo com IA |
| `AI_RATE_LIMIT` | Limite de gerações de IA excedido |
| `PUBLISHING_ERROR` | Erro na publicação |
| `MEDIA_SCAN_FAILED` | Falha no scan de malware da mídia |
| `EXPORT_ERROR` | Erro na geração de relatório |

---

## Headers padrão

### Request

| Header | Obrigatório | Descrição |
|--------|-------------|-----------|
| `Authorization` | Sim* | `Bearer <access_token>` (*exceto endpoints públicos) |
| `Content-Type` | Sim | `application/json` (ou `multipart/form-data` para upload) |
| `Accept` | Recomendado | `application/json` |
| `Accept-Language` | Opcional | `pt-BR`, `en-US` (para mensagens de erro localizadas) |
| `X-Timezone` | Opcional | IANA timezone (ex: `America/Sao_Paulo`). Padrão: timezone do usuário |

### Response

| Header | Descrição |
|--------|-----------|
| `Content-Type` | `application/json` |
| `X-Request-Id` | UUID único da requisição (para tracing) |
| `X-API-Version` | `v1` |
| `X-RateLimit-Limit` | Limite de requisições por janela |
| `X-RateLimit-Remaining` | Requisições restantes na janela |
| `X-RateLimit-Reset` | Timestamp (Unix) de quando o limite reseta |
| `Retry-After` | Segundos para aguardar (quando 429) |

---

## Rate Limiting

| Escopo | Limite | Janela | Identificador |
|--------|--------|--------|---------------|
| Global (por IP) | 60 req | 1 minuto | IP address |
| Autenticado (por user) | 120 req | 1 minuto | User ID |
| Auth endpoints | 10 req | 1 minuto | IP + email |
| AI generation | 10 req | 1 minuto | User ID |
| Upload de mídia | 20 req | 1 minuto | User ID |

Quando o limite é excedido:

```http
HTTP/1.1 429 Too Many Requests
Retry-After: 45
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1708000060

{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Limite de requisições excedido. Tente novamente em 45 segundos."
  }
}
```

---

## Paginação

Cursor-based pagination em todos os endpoints de listagem.

### Parâmetros

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `per_page` | integer | 20 | Itens por página (mín: 1, máx: 100) |
| `cursor` | string | null | Cursor opaco para próxima página |
| `sort` | string | varia | Campo de ordenação. Prefixo `-` = descendente |

### Exemplo

```http
GET /api/v1/campaigns?per_page=10&sort=-created_at

GET /api/v1/campaigns?per_page=10&sort=-created_at&cursor=eyJjcmVhdGVkX2F0Ijo...
```

---

## Filtros

Filtros são passados como query parameters:

```http
GET /api/v1/campaigns?status=active&sort=-created_at
GET /api/v1/comments?sentiment=positive&network=instagram&is_read=false
```

### Operadores de filtro (quando suportado)

| Operador | Uso | Exemplo |
|----------|-----|---------|
| `=` (implícito) | Igualdade | `?status=active` |
| `search` | Busca parcial (ILIKE) | `?search=black+friday` |
| `from` / `to` | Range de datas | `?from=2026-01-01&to=2026-01-31` |
| `,` | Múltiplos valores (OR) | `?status=active,paused` |

---

## Formato de datas

- Todas as datas são em **ISO 8601** com timezone: `2026-02-15T10:30:00Z`
- Datas de input podem omitir timezone (assume UTC ou timezone do header `X-Timezone`)
- Datas de output sempre incluem `Z` (UTC)
