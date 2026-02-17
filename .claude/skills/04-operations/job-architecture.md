# Job Architecture — Social Media Manager API

## Objetivo

Definir a arquitetura de jobs e processamento assíncrono, incluindo filas, prioridades, retry e Dead Letter Queue.

> Referência: ADR-013 (Queue Publishing Strategy)

---

## Princípio Central

> Jobs **nunca** contêm lógica de negócio. Eles orquestram ou chamam Use Cases da Application Layer.

---

## Princípios Obrigatórios

- Jobs são **idempotentes** — executar 2x = mesmo resultado.
- Jobs sempre carregam **contexto**: `organization_id`, `user_id`, `correlation_id`, `trace_id`.
- Falhas são **esperadas e tratadas** (retry, DLQ, notificação).
- Re-execução deve ser **segura** em qualquer momento.

---

## Tipos de Jobs

### Jobs de Publicação (Alta Prioridade)

| Job | Fila | Descrição |
|-----|------|-----------|
| `ProcessScheduledPostJob` | `publishing` | Publica conteúdo na rede social |
| `RetryPublishJob` | `publishing` | Retenta publicação falhada |

### Jobs de Sincronização

| Job | Fila | Descrição |
|-----|------|-----------|
| `SyncPostMetricsJob` | `analytics-sync` | Sincroniza métricas de um post |
| `SyncAccountMetricsJob` | `analytics-sync` | Sincroniza métricas da conta |
| `CaptureCommentsJob` | `default` | Captura comentários de posts |

### Jobs de Mídia

| Job | Fila | Descrição |
|-----|------|-----------|
| `ScanMediaJob` | `media-processing` | Executa scan de segurança |
| `GenerateThumbnailJob` | `media-processing` | Gera thumbnail |

### Jobs de IA

| Job | Fila | Descrição |
|-----|------|-----------|
| `GenerateEmbeddingJob` | `low` | Gera embedding pgvector |

### Jobs de Billing

| Job | Fila | Descrição |
|-----|------|-----------|
| `ProcessStripeWebhookJob` | `high` | Processa evento do Stripe |
| `CheckExpiredSubscriptionsJob` | `default` | Verifica subscriptions past_due expiradas |
| `DowngradeToFreePlanJob` | `default` | Rebaixa org para plano Free |
| `SyncUsageRecordsJob` | `low` | Consolida registros de uso do período |

### Jobs de Administração

| Job | Fila | Descrição |
|-----|------|-----------|
| `PauseOrgScheduledPostsJob` | `default` | Pausa agendamentos de org suspensa |
| `InvalidateUserSessionsJob` | `high` | Invalida sessões de user banido |
| `CleanupSuspendedOrgsJob` | `low` | Marca orgs suspensas >30d para exclusão |

### Jobs de Manutenção

| Job | Fila | Descrição |
|-----|------|-----------|
| `RefreshExpiringTokensJob` | `high` | Renova tokens próximos de expirar |
| `PurgeExpiredRecordsJob` | `low` | Remove dados expirados |
| `DeliverWebhookJob` | `default` | Entrega webhook |
| `GenerateReportJob` | `low` | Gera relatório PDF/CSV |
| `ProcessAccountDeletionJob` | `default` | Anonimiza conta após grace period |

### Jobs de Notificação

| Job | Fila | Descrição |
|-----|------|-----------|
| `SendVerificationEmailJob` | `notifications` | Email de verificação |
| `SendPasswordResetJob` | `notifications` | Email de reset |
| `NotifyPublishResultJob` | `notifications` | Notifica resultado de publicação |

---

## Filas e Prioridades

| Fila | Prioridade | Workers | Timeout |
|------|-----------|---------|---------|
| `publishing` | Alta | 3 | 120s |
| `high` | Alta | 2 | 60s |
| `default` | Normal | 3 | 90s |
| `analytics-sync` | Normal | 2 | 120s |
| `media-processing` | Normal | 2 | 300s |
| `notifications` | Normal | 2 | 30s |
| `low` | Baixa | 1 | 600s |

Configuração via **Laravel Horizon**.

---

## Posicionamento na Arquitetura

```
Controller → Use Case → dispatch Job (se necessário)
                              ↓
Event Listener → dispatch Job (reação a evento)
                              ↓
                    Infrastructure Layer
                              ↓
               Job::handle() → chama Use Case
```

- **Controllers nunca criam jobs diretamente** (delegam a Use Cases).
- **Use Cases despacham jobs** quando necessário.
- **Event Listeners despacham jobs** para processamento assíncrono.
- **Jobs chamam Use Cases** — nunca contêm lógica de negócio.

---

## Contexto Obrigatório

Todo job deve carregar:

```php
class ProcessScheduledPostJob implements ShouldQueue
{
    public function __construct(
        public readonly string $scheduledPostId,
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $correlationId,
        public readonly string $traceId,
    ) {}
}
```

- `organizationId`: identifica a organização (tenant).
- `userId`: identifica quem iniciou a operação.
- `correlationId`: rastreia a cadeia de operações.
- `traceId`: identificador único desta execução.

---

## Idempotência

### Estratégias

1. **Verificação de estado**: antes de executar, verificar se já foi processado.
   ```php
   $post = $repo->find($this->scheduledPostId);
   if ($post->status !== 'dispatched') return; // já processado
   ```

2. **Lock lógico**: Redis lock com TTL para evitar execução concorrente.
   ```php
   Cache::lock("publish:{$this->scheduledPostId}", 120)->get(function () {
       // execução segura
   });
   ```

3. **Idempotency key**: chave única por operação, verificada antes de executar.

---

## Retry e Backoff

### Configuração padrão

```php
public $tries = 3;
public $backoff = [60, 300, 900]; // 1min, 5min, 15min
```

### Por tipo de job

| Job | Max Tries | Backoff |
|-----|-----------|---------|
| Publishing | 3 | 60s, 300s, 900s |
| Analytics sync | 3 | 120s, 600s, 1800s |
| Webhook delivery | 4 | 60s, 300s, 1800s, — |
| Media scan | 2 | 60s, 300s |
| Notifications | 3 | 30s, 60s, 300s |

### Após todas as tentativas falharem

- Job é movido para Dead Letter Queue.
- Evento `JobFailed` é emitido.
- Log de erro com contexto completo.
- Alerta para monitoramento.

---

## Observabilidade

Métricas por job:
- `job_processing_duration_seconds` (histogram)
- `job_attempts_total` (counter, por status: success, failed, retried)
- `job_queue_wait_seconds` (histogram)
- `jobs_in_dlq_total` (gauge)

Logs:
- Start: `job.started { jobClass, organizationId, userId, correlationId }`
- Success: `job.completed { jobClass, organizationId, userId, duration_ms }`
- Failure: `job.failed { jobClass, organizationId, userId, attempt, error }`
- DLQ: `job.dead_letter { jobClass, organizationId, userId, totalAttempts, lastError }`

---

## Anti-Patterns

- Lógica de negócio dentro do job (jobs são orquestradores).
- Job sem `organizationId` ou `userId` (impossibilita rastreamento e isolamento).
- Job sem idempotência (execução dupla causa efeito duplicado).
- Retry sem backoff (sobrecarrega sistemas).
- Retry infinito (nunca desiste).
- Jobs silenciosos (sem logging ou métricas).
- Chamadas diretas a APIs externas sem circuit breaker.
- Secrets no payload do job.
