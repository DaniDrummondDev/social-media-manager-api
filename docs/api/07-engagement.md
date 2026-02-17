# 07 — Engagement: Comentários, Automação & Webhooks

[← Voltar ao índice](00-index.md)

---

## Comentários

### GET /api/v1/comments

Lista comentários (inbox unificado).

**Autenticação:** Bearer token

#### Query Parameters

| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| `provider` | string | — | `instagram`, `tiktok`, `youtube` |
| `campaign_id` | uuid | — | Filtro por campanha |
| `content_id` | uuid | — | Filtro por conteúdo |
| `sentiment` | string | — | `positive`, `neutral`, `negative` |
| `is_read` | boolean | — | `true` ou `false` |
| `is_replied` | boolean | — | `true` ou `false` |
| `search` | string | — | Busca textual no comentário |
| `from` | datetime | — | Data início |
| `to` | datetime | — | Data fim |
| `sort` | string | `-captured_at` | Ordenação |
| `per_page` | integer | 20 | Itens por página |
| `cursor` | string | — | Cursor |

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "cm-001-...",
      "type": "comment",
      "attributes": {
        "content_id": "cc0e8400-...",
        "content_title": "Mega promoção Black Friday!",
        "campaign_name": "Black Friday 2026",
        "provider": "instagram",
        "author": {
          "name": "João Silva",
          "external_id": "12345",
          "profile_url": "https://instagram.com/joao.silva"
        },
        "text": "Que promoção incrível! Quanto custa o vestido preto?",
        "sentiment": "positive",
        "sentiment_score": 0.85,
        "is_read": false,
        "is_from_owner": false,
        "reply": null,
        "commented_at": "2026-02-15T10:45:00Z",
        "captured_at": "2026-02-15T11:00:00Z"
      }
    },
    {
      "id": "cm-002-...",
      "type": "comment",
      "attributes": {
        "content_id": "cc0e8400-...",
        "content_title": "Mega promoção Black Friday!",
        "campaign_name": "Black Friday 2026",
        "provider": "instagram",
        "author": {
          "name": "Ana Costa",
          "external_id": "67890",
          "profile_url": "https://instagram.com/ana.costa"
        },
        "text": "Vocês entregam para todo o Brasil?",
        "sentiment": "neutral",
        "sentiment_score": 0.52,
        "is_read": true,
        "is_from_owner": false,
        "reply": {
          "text": "Sim, Ana! Entregamos para todo o Brasil com frete grátis acima de R$199 🚚",
          "replied_by": "automation",
          "replied_at": "2026-02-15T11:05:00Z"
        },
        "commented_at": "2026-02-15T10:30:00Z",
        "captured_at": "2026-02-15T11:00:00Z"
      }
    }
  ],
  "meta": { "..." }
}
```

---

### PUT /api/v1/comments/{id}/read

Marca comentário como lido.

**Autenticação:** Bearer token

#### Response — 204 No Content

---

### PUT /api/v1/comments/read

Marca múltiplos comentários como lidos.

**Autenticação:** Bearer token

#### Request

```json
{
  "comment_ids": ["cm-001-...", "cm-002-..."]
}
```

#### Response — 200 OK

```json
{
  "data": {
    "marked_count": 2
  }
}
```

---

### POST /api/v1/comments/{id}/reply

Responde a um comentário manualmente.

**Autenticação:** Bearer token

#### Request

```json
{
  "text": "Olá João! O vestido preto está por R$199 com 50% de desconto. Corre que é por tempo limitado! 🖤"
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `text` | string | Sim | 1-2000 chars |

#### Response — 201 Created

```json
{
  "data": {
    "comment_id": "cm-001-...",
    "reply": {
      "text": "Olá João! O vestido preto está por R$199...",
      "replied_by": "user",
      "reply_external_id": "reply-12345",
      "replied_at": "2026-02-15T11:10:00Z"
    }
  }
}
```

#### Erros

| Status | Código | Cenário |
|--------|--------|---------|
| 400 | SOCIAL_ACCOUNT_ERROR | Falha ao publicar resposta na rede |
| 409 | RESOURCE_CONFLICT | Comentário já respondido |

---

### POST /api/v1/comments/{id}/suggest-reply

Gera sugestão de resposta via IA.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": {
    "suggestions": [
      {
        "text": "Olá João! O vestido preto está com 50% de desconto, saindo por R$199. Aproveite enquanto temos estoque! 🖤",
        "tone": "professional"
      },
      {
        "text": "Oi João! Tá por R$199 com desconto de Black Friday! Corre que acaba rápido 🔥",
        "tone": "casual"
      }
    ],
    "usage": {
      "tokens_input": 100,
      "tokens_output": 80,
      "model": "gpt-4o"
    }
  }
}
```

---

## Regras de Automação

### POST /api/v1/automation-rules

Cria uma regra de automação.

**Autenticação:** Bearer token

#### Request

```json
{
  "name": "Responder perguntas de preço",
  "priority": 1,
  "conditions": [
    {
      "field": "keyword",
      "operator": "contains",
      "value": "preço"
    },
    {
      "field": "keyword",
      "operator": "contains",
      "value": "quanto"
    }
  ],
  "action_type": "reply_template",
  "response_template": "Olá {author_name}! Os preços estão no nosso site. Acesse o link na bio para conferir! 😊",
  "delay_seconds": 120,
  "daily_limit": 50,
  "applies_to_networks": ["instagram", "tiktok"],
  "applies_to_campaigns": null
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 3-100 chars |
| `priority` | integer | Sim | Único entre regras ativas |
| `conditions` | object[] | Sim | Mín. 1 condição |
| `conditions[].field` | string | Sim | `keyword`, `sentiment`, `author_name` |
| `conditions[].operator` | string | Sim | `contains`, `equals`, `in`, `not_contains` |
| `conditions[].value` | string | Sim | Valor de comparação |
| `action_type` | string | Sim | `reply_fixed`, `reply_template`, `reply_ai`, `send_webhook` |
| `response_template` | string | Condicional | Obrigatório para reply_fixed e reply_template |
| `webhook_id` | uuid | Condicional | Obrigatório para send_webhook |
| `delay_seconds` | integer | Não | 30-3600 (padrão: 120) |
| `daily_limit` | integer | Não | 10-1000 (padrão: 100) |
| `applies_to_networks` | string[] | Não | null = todas |
| `applies_to_campaigns` | uuid[] | Não | null = todas |

#### Response — 201 Created

```json
{
  "data": {
    "id": "ar-001-...",
    "type": "automation_rule",
    "attributes": {
      "name": "Responder perguntas de preço",
      "priority": 1,
      "conditions": [
        { "field": "keyword", "operator": "contains", "value": "preço" },
        { "field": "keyword", "operator": "contains", "value": "quanto" }
      ],
      "action_type": "reply_template",
      "response_template": "Olá {author_name}! Os preços estão no nosso site...",
      "delay_seconds": 120,
      "daily_limit": 50,
      "executions_today": 0,
      "is_active": true,
      "applies_to_networks": ["instagram", "tiktok"],
      "applies_to_campaigns": null,
      "created_at": "2026-02-15T10:30:00Z"
    }
  }
}
```

---

### GET /api/v1/automation-rules

Lista regras de automação.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "ar-001-...",
      "type": "automation_rule",
      "attributes": {
        "name": "Responder perguntas de preço",
        "priority": 1,
        "action_type": "reply_template",
        "is_active": true,
        "executions_today": 12,
        "daily_limit": 50,
        "created_at": "2026-02-15T10:30:00Z"
      }
    }
  ]
}
```

---

### PUT /api/v1/automation-rules/{id}

Atualiza uma regra de automação.

**Autenticação:** Bearer token

---

### DELETE /api/v1/automation-rules/{id}

Exclui uma regra de automação (soft delete).

**Autenticação:** Bearer token

#### Response — 204 No Content

---

### GET /api/v1/automation-rules/{id}/executions

Lista execuções de uma regra de automação.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "ae-001-...",
      "type": "automation_execution",
      "attributes": {
        "rule_name": "Responder perguntas de preço",
        "comment_text": "Quanto custa o vestido?",
        "comment_author": "João Silva",
        "action_type": "reply_template",
        "response_text": "Olá João Silva! Os preços estão no nosso site...",
        "success": true,
        "delay_applied": 120,
        "executed_at": "2026-02-15T11:05:00Z"
      }
    }
  ],
  "meta": { "..." }
}
```

---

## Blacklist

### GET /api/v1/automation-blacklist

Lista palavras da blacklist.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    { "id": "bl-001-...", "word": "spam", "is_regex": false },
    { "id": "bl-002-...", "word": "compre agora", "is_regex": false },
    { "id": "bl-003-...", "word": "\\b(http|www)\\b", "is_regex": true }
  ]
}
```

### POST /api/v1/automation-blacklist

Adiciona palavras à blacklist.

#### Request

```json
{
  "words": [
    { "word": "link suspeito", "is_regex": false },
    { "word": "\\d{4,}", "is_regex": true }
  ]
}
```

### DELETE /api/v1/automation-blacklist/{id}

Remove palavra da blacklist.

#### Response — 204 No Content

---

## Webhooks

### POST /api/v1/webhooks

Cria um endpoint de webhook.

**Autenticação:** Bearer token

#### Request

```json
{
  "name": "CRM Integration",
  "url": "https://meu-crm.com/api/webhooks/social-media",
  "events": ["comment.created", "lead.identified"],
  "headers": {
    "X-Custom-Header": "valor"
  }
}
```

| Campo | Tipo | Obrigatório | Validação |
|-------|------|-------------|-----------|
| `name` | string | Sim | 3-100 chars |
| `url` | string | Sim | URL HTTPS válida |
| `events` | string[] | Sim | Pelo menos 1 evento válido |
| `headers` | object | Não | Headers customizados |

#### Response — 201 Created

```json
{
  "data": {
    "id": "wh-001-...",
    "type": "webhook_endpoint",
    "attributes": {
      "name": "CRM Integration",
      "url": "https://meu-crm.com/api/webhooks/social-media",
      "events": ["comment.created", "lead.identified"],
      "headers": { "X-Custom-Header": "valor" },
      "secret": "whsec_a1b2c3d4e5f6...",
      "is_active": true,
      "created_at": "2026-02-15T10:30:00Z"
    }
  },
  "meta": {
    "message": "Webhook criado. Use o 'secret' para validar assinaturas HMAC-SHA256. Este valor não será exibido novamente."
  }
}
```

> O `secret` é retornado apenas na criação. Armazene-o com segurança.

---

### GET /api/v1/webhooks

Lista webhooks configurados.

**Autenticação:** Bearer token

---

### PUT /api/v1/webhooks/{id}

Atualiza um webhook.

**Autenticação:** Bearer token

---

### DELETE /api/v1/webhooks/{id}

Exclui um webhook.

**Autenticação:** Bearer token

#### Response — 204 No Content

---

### POST /api/v1/webhooks/{id}/test

Envia um evento de teste para o webhook.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": {
    "success": true,
    "response_status": 200,
    "response_time_ms": 245,
    "message": "Webhook de teste enviado com sucesso."
  }
}
```

---

### GET /api/v1/webhooks/{id}/deliveries

Lista entregas do webhook.

**Autenticação:** Bearer token

#### Response — 200 OK

```json
{
  "data": [
    {
      "id": "wd-001-...",
      "type": "webhook_delivery",
      "attributes": {
        "event": "comment.created",
        "response_status": 200,
        "response_time_ms": 120,
        "attempts": 1,
        "delivered_at": "2026-02-15T11:00:00Z",
        "created_at": "2026-02-15T11:00:00Z"
      }
    },
    {
      "id": "wd-002-...",
      "type": "webhook_delivery",
      "attributes": {
        "event": "comment.created",
        "response_status": 500,
        "response_time_ms": 5000,
        "attempts": 3,
        "failed_at": "2026-02-15T11:35:00Z",
        "next_retry_at": null,
        "created_at": "2026-02-15T11:00:00Z"
      }
    }
  ],
  "meta": { "..." }
}
```
