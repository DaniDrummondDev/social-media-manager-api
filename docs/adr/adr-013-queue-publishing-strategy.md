# ADR-013: Estratégia de Filas para Publicação

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

A publicação de conteúdo em redes sociais é a operação mais crítica do sistema:
- Depende de APIs externas (latência variável, rate limits, falhas)
- Precisa de retry inteligente (erros transitórios vs permanentes)
- Publicações agendadas devem ser processadas no horário correto
- Uma falha em uma rede não deve afetar publicação em outra
- O volume pode ser alto (centenas de publicações por minuto em horários de pico)

## Decisão

Implementar uma **estratégia de filas multi-nível** com isolamento por rede social,
retry inteligente e circuit breaker.

### Arquitetura de filas

```
┌─────────────────┐     ┌──────────────────────────────────────────┐
│   Scheduler     │     │              Redis Queues                │
│   (cada minuto) │     │                                          │
│                 │     │  ┌──────────┐  ┌───────────┐  ┌───────┐ │
│  Busca posts    │────▶│  │  high    │  │  default  │  │  low  │ │
│  com            │     │  │(imediato)│  │(agendado) │  │(sync) │ │
│  scheduled_at   │     │  └────┬─────┘  └─────┬─────┘  └───┬───┘ │
│  <= now         │     │       │              │             │     │
│                 │     └───────┼──────────────┼─────────────┼─────┘
└─────────────────┘             │              │             │
                                ▼              ▼             ▼
                    ┌───────────────────────────────────────────┐
                    │            Worker Pool                     │
                    │                                           │
                    │  ┌─────────────────────────────────────┐  │
                    │  │      ProcessScheduledPostJob        │  │
                    │  │                                     │  │
                    │  │  1. Resolve adapter (by provider)   │  │
                    │  │  2. Check circuit breaker           │  │
                    │  │  3. Decrypt token                   │  │
                    │  │  4. Publish via adapter             │  │
                    │  │  5. Update status                   │  │
                    │  │  6. Dispatch PostPublished event    │  │
                    │  └─────────────────────────────────────┘  │
                    └───────────────────────────────────────────┘
```

### Job: ProcessScheduledPostJob

```php
class ProcessScheduledPostJob implements ShouldQueue
{
    public string $queue = 'default';
    public int $tries = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min
    public int $timeout = 120; // 2 minutos max por publicação

    public function __construct(
        private readonly string $scheduledPostId,
    ) {}

    public function handle(
        ScheduledPostRepository $repository,
        SocialMediaAdapterFactory $factory,
        TokenEncryptionService $encryption,
    ): void {
        $scheduledPost = $repository->findOrFail($this->scheduledPostId);
        $socialAccount = $scheduledPost->socialAccount();

        // 1. Verificar circuit breaker
        $circuitBreaker = CircuitBreaker::for($socialAccount->provider());
        if ($circuitBreaker->isOpen()) {
            $this->release($circuitBreaker->retryAfter());
            return;
        }

        // 2. Publicar
        $publisher = $factory->makePublisher($socialAccount->provider());
        $token = $encryption->decrypt($socialAccount->accessToken());

        try {
            $result = $publisher->publish($token, $scheduledPost->content());
            $scheduledPost->markAsPublished($result->externalPostId);
            $circuitBreaker->recordSuccess();
        } catch (PermanentPublishException $e) {
            $scheduledPost->markAsFailed($e->toPublishError());
            $this->fail($e); // Não faz retry
        } catch (TransientPublishException $e) {
            $circuitBreaker->recordFailure();
            throw $e; // Laravel faz retry automaticamente
        }

        $repository->save($scheduledPost);
    }

    public function failed(Throwable $exception): void
    {
        // Após todas as tentativas falharem
        // Notifica usuário, atualiza status para failed
    }
}
```

### Isolamento por rede social

Cada `ScheduledPost` é um job independente. Se um conteúdo é agendado para 3 redes:

```
Content "Black Friday Post"
├── ScheduledPost #1 → Instagram → Job independente
├── ScheduledPost #2 → TikTok   → Job independente
└── ScheduledPost #3 → YouTube  → Job independente
```

- Falha no Instagram não impede TikTok e YouTube
- Retry acontece individualmente por rede
- Rate limit de uma rede não afeta outra

### Circuit Breaker

```
States:
  CLOSED ──(falhas >= threshold)──▶ OPEN ──(timeout)──▶ HALF-OPEN
    ▲                                                       │
    └──────────────(sucesso)────────────────────────────────┘
    └──────────────(falha)──────────────▶ OPEN

Configuração por provider:
  failure_threshold: 5 falhas consecutivas
  open_timeout: 5 minutos
  half_open_max_attempts: 2
```

Armazenado no Redis com TTL:
```
circuit_breaker:instagram → {state: "open", failures: 5, opened_at: timestamp}
```

### Scheduler (publicações agendadas)

```php
// app/Console/Kernel.php
$schedule->command('publishing:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

O command `publishing:dispatch-due`:
1. Busca todos os `ScheduledPost` com `scheduled_at <= now()` e `status = pending`
2. Despacha um `ProcessScheduledPostJob` para cada
3. Atualiza status para `dispatched` (evita despacho duplicado)
4. Executa em < 5 segundos (apenas queries e dispatch)

### Priorização

| Cenário | Fila | Prioridade |
|---------|------|-----------|
| Publicação imediata (publish now) | `high` | Workers processam primeiro |
| Publicação agendada | `default` | Fluxo normal |
| Retry de publicação falha | `default` | Mesmo fluxo, backoff via `release()` |
| Sync de métricas | `low` | Nunca compete com publicações |
| Export de relatórios | `low` | Background |

### Idempotência

- Cada job verifica o status atual antes de publicar:
  - Se já `published` → skip (idempotente)
  - Se `cancelled` → skip
  - Se `publishing` por outro worker → skip (lock via Redis)
- Lock distribuído via Redis para evitar publicação duplicada:
  ```
  publishing:lock:{scheduledPostId} → TTL 120s
  ```

## Alternativas consideradas

### 1. Publicação síncrona (na request HTTP)
- **Por que descartada:** APIs sociais são lentas (2-30s). Request HTTP ficaria bloqueada, timeout para o cliente, sem retry

### 2. Fila única para tudo
- **Por que descartada:** Publicações seriam atrasadas por jobs de sync/export. Sem priorização

### 3. Filas separadas por provider (queue:instagram, queue:tiktok)
- **Prós:** Isolamento total por rede
- **Contras:** Workers ociosos quando uma rede tem pouco volume, complexidade de gerenciamento
- **Por que descartada:** O circuit breaker + isolamento por job alcança o mesmo resultado sem overhead de filas separadas

## Consequências

### Positivas
- Publicações nunca bloqueiam requests HTTP
- Falha em uma rede não afeta outra (jobs independentes)
- Retry inteligente com backoff exponencial
- Circuit breaker previne cascade failure quando API está fora
- Priorização garante que publicações imediatas não esperem
- Idempotência previne publicações duplicadas

### Negativas
- Complexidade do sistema de filas (circuit breaker, locks, retry)
- Eventual delay entre "publish now" e publicação efetiva (~5-30s)
- Monitoramento de filas é essencial (Laravel Horizon)

### Riscos
- Redis down = filas paradas — fallback para database queue (degradação)
- Circuit breaker aberto por muito tempo = publicações atrasadas — alerta + half-open recovery
