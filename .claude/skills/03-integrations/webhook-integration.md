# Webhook Integration — Social Media Manager API

## Objetivo

Definir a arquitetura de webhooks para integração com CRMs e sistemas externos, incluindo entrega confiável, assinatura de payloads e retry.

---

## Visão Geral

Webhooks permitem que o usuário integre o Social Media Manager com sistemas externos (CRMs, automações, dashboards) recebendo notificações em tempo real sobre eventos.

---

## Eventos Disponíveis

| Evento | Descrição | Payload principal |
|--------|-----------|-------------------|
| `comment.created` | Novo comentário capturado | comment_id, text, author, sentiment |
| `comment.replied` | Comentário respondido | comment_id, reply_text, replied_by |
| `post.published` | Post publicado com sucesso | content_id, provider, external_url |
| `post.failed` | Publicação falhou | content_id, provider, error |
| `lead.identified` | Lead identificado por automação | comment_id, author, matched_rule |
| `automation.executed` | Automação executada | rule_id, comment_id, action_type |
| `media.scanned` | Mídia escaneada | media_id, scan_status |

---

## Criação de Webhook

### Request

```json
{
  "name": "CRM Integration",
  "url": "https://meu-crm.com/api/webhooks/social-media",
  "events": ["comment.created", "lead.identified"],
  "headers": { "X-Custom-Header": "valor" }
}
```

### Validações

- URL: **HTTPS obrigatório** (HTTP rejeitado).
- URL: não pode apontar para IPs privados (SSRF protection).
- Events: pelo menos 1 evento válido.
- Máximo de 10 webhooks por usuário.

### Secret

- Gerado automaticamente na criação (`whsec_` + 32 bytes random).
- Retornado **apenas** na resposta de criação.
- Usado para assinar payloads (HMAC-SHA256).
- Armazenado como hash no banco.

---

## Assinatura de Payloads

### Headers da Entrega

```
X-Webhook-Signature: sha256=<hmac>
X-Webhook-Timestamp: <unix_timestamp>
X-Webhook-Event: <event_type>
X-Webhook-Delivery-Id: <uuid>
```

### Cálculo da Assinatura

```
payload = timestamp + "." + json_body
signature = HMAC-SHA256(secret, payload)
```

O receptor deve:
1. Extrair timestamp e body.
2. Recalcular HMAC com o secret conhecido.
3. Comparar assinaturas (timing-safe comparison).
4. Rejeitar se timestamp > 5 minutos de diferença (replay attack protection).

---

## Entrega de Webhooks

### Fluxo

```
Evento ocorre → WebhookDispatcher verifica endpoints inscritos
                          ↓
              Para cada endpoint: enfileira DeliverWebhookJob
                          ↓
              Job executa POST para a URL com payload assinado
                          ↓
              Registra resultado em webhook_deliveries
```

### Timeout

- **10 segundos** para resposta do endpoint.
- Qualquer resposta 2xx é considerada sucesso.
- Respostas 4xx/5xx ou timeout: marcada como falha.

### Retry Strategy

| Tentativa | Delay | Descrição |
|-----------|-------|-----------|
| 1ª | Imediata | Primeira tentativa |
| 2ª | 1 minuto | Primeira retry |
| 3ª | 5 minutos | Segunda retry |
| 4ª | 30 minutos | Última retry |

- Após 4 tentativas falhadas: entrega marcada como `failed`.
- Não retenta para respostas `4xx` (erro do receptor, não transiente).
- Retenta apenas para `5xx` e timeouts.

### Desativação Automática

- Se 10 entregas consecutivas falharem: webhook marcado como `is_active = false`.
- Usuário notificado para verificar a URL.
- Reativação manual pelo usuário.

---

## Teste de Webhook

### POST /api/v1/webhooks/{id}/test

- Envia evento de teste com payload de exemplo.
- Retorna: `response_status`, `response_time_ms`, `success`.
- Rate limited: 5 testes por 15 minutos.

---

## Histórico de Entregas

### GET /api/v1/webhooks/{id}/deliveries

Cada delivery registra:
- `event`: tipo do evento.
- `response_status`: HTTP status code recebido.
- `response_time_ms`: tempo de resposta.
- `attempts`: número de tentativas.
- `delivered_at` ou `failed_at`.
- `next_retry_at` (se pendente).

---

## Anti-Patterns

- Webhook sem assinatura HMAC (payload pode ser forjado).
- URL HTTP (sem TLS, dados expostos).
- Retry infinito (consumo de recursos sem resultado).
- Entrega síncrona (bloquear request do usuário).
- Payload contendo tokens ou dados sensíveis.
- Seguir redirects na entrega (SSRF risk).
- Logar payload completo em texto claro.

---

## Dependências

- `01-security/api-security.md` (SSRF protection)
- `04-operations/failure-handling.md` (retry strategy)
- `01-security/audit-logging.md` (registro de deliveries)
