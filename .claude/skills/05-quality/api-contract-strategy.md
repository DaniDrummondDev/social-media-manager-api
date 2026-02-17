# API Contract Strategy — Social Media Manager API

## Objetivo

Definir a estratégia de contratos de API, garantindo que endpoints sejam estáveis, versionados e tratados como compromissos formais.

> Referência: ADR-008 (API Versioning)

---

## Princípio

> Contratos de API são **compromissos formais** com o consumidor. Mudanças breaking exigem nova versão.

---

## Versionamento

### Estratégia

- URL prefix: `/api/v1/`
- Uma versão ativa por vez (MVP).
- Deprecação com prazo mínimo de 6 meses.

### O que é Breaking Change

| Tipo | Breaking? | Exemplo |
|------|----------|---------|
| Remover endpoint | Sim | `DELETE /api/v1/campaigns/{id}/archive` |
| Remover campo de response | Sim | Remover `engagement_rate` |
| Mudar tipo de campo | Sim | `file_size`: string → integer |
| Renomear campo | Sim | `total_reach` → `reach_total` |
| Mudar status code | Sim | 200 → 204 |
| Adicionar campo obrigatório | Sim | Novo campo required no body |
| Adicionar campo opcional no request | Não | Novo campo opcional |
| Adicionar campo no response | Não | Novo campo no JSON |
| Adicionar novo endpoint | Não | Novo `GET /api/v1/...` |
| Adicionar novo error code | Não | Novo código de erro |

---

## Formato de Response

### Recurso Único

```json
{
  "data": {
    "id": "uuid",
    "type": "resource_type",
    "attributes": { ... }
  }
}
```

### Coleção

```json
{
  "data": [
    { "id": "uuid", "type": "resource_type", "attributes": { ... } }
  ],
  "meta": {
    "per_page": 20,
    "has_more": true,
    "next_cursor": "..."
  }
}
```

### Erro

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Descrição legível",
    "details": [
      { "field": "name", "message": "O campo nome é obrigatório." }
    ]
  }
}
```

---

## Códigos de Erro da Aplicação

| Código | HTTP Status | Cenário |
|--------|------------|---------|
| `VALIDATION_ERROR` | 400, 422 | Input inválido |
| `AUTHENTICATION_ERROR` | 401 | Token inválido/ausente |
| `AUTHORIZATION_ERROR` | 403 | Sem permissão |
| `RESOURCE_NOT_FOUND` | 404 | Recurso não encontrado |
| `RESOURCE_CONFLICT` | 409 | Conflito de estado |
| `RATE_LIMIT_EXCEEDED` | 429 | Rate limit atingido |
| `PUBLISHING_ERROR` | 400 | Erro de publicação |
| `SOCIAL_ACCOUNT_ERROR` | 400 | Erro com conta social |
| `AI_RATE_LIMIT` | 429 | Limite de IA atingido |
| `AI_GENERATION_ERROR` | 503 | IA indisponível |
| `EXPORT_ERROR` | 500 | Erro na exportação |
| `INTERNAL_ERROR` | 500 | Erro interno |
| `SERVICE_UNAVAILABLE` | 503 | Serviço indisponível |

---

## Paginação

### Cursor-based (Obrigatória)

```
GET /api/v1/campaigns?per_page=20&cursor=eyJpZCI6MTB9
```

- Nunca offset-based (performance degrada com volume).
- `per_page`: máximo 50.
- `cursor`: string opaca (base64-encoded).
- Response inclui `meta.next_cursor` e `meta.has_more`.

---

## Headers Padronizados

### Request

| Header | Obrigatório | Descrição |
|--------|------------|-----------|
| `Authorization` | Sim* | `Bearer <token>` |
| `Content-Type` | Sim (POST/PUT) | `application/json` ou `multipart/form-data` |
| `Accept` | Recomendado | `application/json` |
| `Idempotency-Key` | Opcional | UUID para operações idempotentes |

### Response

| Header | Sempre | Descrição |
|--------|--------|-----------|
| `Content-Type` | Sim | `application/json` |
| `X-Request-Id` | Sim | Correlation ID do request |
| `X-RateLimit-Limit` | Sim | Limite do endpoint |
| `X-RateLimit-Remaining` | Sim | Requests restantes |
| `X-RateLimit-Reset` | Sim | Timestamp de reset |
| `Retry-After` | Quando 429 | Segundos até próxima tentativa |

---

## Testes de Contrato

### Obrigatórios para cada endpoint

```php
test('GET /api/v1/campaigns returns correct structure', function () {
    $user = User::factory()->create();
    Campaign::factory()->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/campaigns');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'attributes' => [
                        'name', 'status', 'created_at'
                    ]
                ]
            ],
            'meta' => ['per_page', 'has_more']
        ]);
});

test('POST /api/v1/campaigns validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/campaigns', []);

    $response->assertStatus(422)
        ->assertJsonStructure([
            'error' => ['code', 'message', 'details']
        ]);
});
```

### Checklist por Endpoint

- [ ] Resposta com estrutura correta (`data`, `meta`, `error`).
- [ ] Status codes corretos para cada cenário.
- [ ] Campos obrigatórios presentes na resposta.
- [ ] Tipos de campos corretos (string, integer, UUID).
- [ ] Paginação funcional (cursor, per_page).
- [ ] Erros de validação retornam detalhes.
- [ ] Autenticação obrigatória (401 sem token).
- [ ] Ownership validado (404 para recurso de outro user).

---

## Anti-Patterns

- Mudar formato de response sem versionamento.
- Remover campos sem deprecation notice.
- Status codes inconsistentes entre endpoints.
- Erros genéricos sem código de aplicação.
- Paginação offset-based.
- Testes de contrato que verificam implementação interna.
- Contratos não documentados na API spec.
