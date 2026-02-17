# Idempotency Strategy — Social Media Manager API

## Objetivo

Definir a estratégia de idempotência para operações críticas, garantindo que re-execução de operações produza o mesmo resultado.

---

## Princípio

> Toda operação que modifica estado deve ser segura para re-execução. Executar 2x = mesmo resultado que executar 1x.

---

## Por que Idempotência é Crítica

No contexto do Social Media Manager:

- Jobs de publicação podem ser retentados automaticamente.
- Webhooks podem ser entregues mais de uma vez.
- Requests podem ser duplicados por retry do cliente.
- Eventos podem ser processados mais de uma vez.

Sem idempotência, uma publicação pode ser feita em duplicata, uma automação pode responder múltiplas vezes, ou um webhook pode ser entregue repetidamente.

---

## Estratégias de Idempotência

### 1. Verificação de Estado (State Check)

Antes de executar, verificar se a operação já foi realizada.

```php
// Publicação: verificar status antes de publicar
$post = $repo->find($scheduledPostId);
if ($post->status !== 'dispatched') {
    return; // já publicado, falhado, ou cancelado
}
```

**Usar quando**: operação tem estado explícito (publicação, agendamento).

### 2. Lock Distribuído (Redis Lock)

Previne execução concorrente da mesma operação.

```php
$lock = Cache::lock("publish:{$scheduledPostId}", ttl: 120);
if ($lock->get()) {
    try {
        // executar operação
    } finally {
        $lock->release();
    }
}
```

**Usar quando**: operação pode ser disparada concorrentemente (mesmo job em retry + novo dispatch).

### 3. Idempotency Key (Header)

Cliente envia chave única que identifica a operação.

```
Header: Idempotency-Key: <uuid>
```

- Servidor armazena resultado da primeira execução.
- Requests subsequentes com mesma key retornam resultado cacheado.
- TTL: 24 horas.
- Armazenamento: Redis com `idempotency:{key}`.

**Usar quando**: endpoints de criação que o cliente pode retentar (upload, schedule).

### 4. Unique Constraints (Database)

Constraints do banco previnem duplicatas.

```sql
-- Mesmo conteúdo não pode ser agendado 2x para mesma rede
CREATE UNIQUE INDEX idx_unique_content_provider
ON scheduled_posts (content_id, social_account_id)
WHERE status NOT IN ('cancelled', 'failed');
```

**Usar quando**: regra de negócio garante unicidade.

---

## Operações que Exigem Idempotência

### Publicação

| Operação | Estratégia | Detalhes |
|----------|-----------|---------|
| Publicar post | State check + Lock | Verificar status + Redis lock |
| Retry publicação | State check | Só retenta se `failed` |
| Cancelar agendamento | State check | Só cancela se `pending` |

### Engajamento

| Operação | Estratégia | Detalhes |
|----------|-----------|---------|
| Responder comentário | State check | Verificar se já respondido |
| Executar automação | Lock + State check | Lock por comment+rule |
| Entregar webhook | Idempotency key | Delivery ID como key |

### Mídia

| Operação | Estratégia | Detalhes |
|----------|-----------|---------|
| Scan de mídia | State check | Verificar scan_status |
| Gerar thumbnail | State check | Verificar se existe |

### Analytics

| Operação | Estratégia | Detalhes |
|----------|-----------|---------|
| Sync métricas | Upsert | INSERT ON CONFLICT UPDATE |
| Gerar relatório | State check + Lock | Verificar status do export |

---

## Idempotência em Eventos

Event handlers devem ser idempotentes:

```php
class SyncMetricsOnPostPublished
{
    public function handle(PostPublished $event): void
    {
        // Idempotente: se métricas já existem, upsert
        $this->metricsRepo->upsert(
            contentId: $event->contentId,
            provider: $event->provider,
            metrics: $this->fetchMetrics($event->externalPostId)
        );
    }
}
```

---

## Implementação de Idempotency Key (API)

### Middleware

```php
class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) return $next($request);

        $cached = Cache::get("idempotency:{$key}");
        if ($cached) return response()->json($cached['body'], $cached['status']);

        $response = $next($request);

        Cache::put("idempotency:{$key}", [
            'body' => $response->getData(),
            'status' => $response->getStatusCode(),
        ], now()->addHours(24));

        return $response;
    }
}
```

### Endpoints que aceitam Idempotency-Key

- `POST /api/v1/media` (upload)
- `POST /api/v1/contents/{id}/schedule`
- `POST /api/v1/contents/{id}/publish-now`
- `POST /api/v1/comments/{id}/reply`

---

## Anti-Patterns

- Operação sem verificação de estado prévio.
- Lock sem TTL (pode travar para sempre).
- Idempotency key sem TTL (cresce indefinidamente).
- Assumir que jobs executam apenas uma vez.
- Assumir que eventos são processados apenas uma vez.
- INSERT sem ON CONFLICT para upsert de métricas.
- Responder a comentário sem verificar se já foi respondido.
