# Failure Handling — Social Media Manager API

## Objetivo

Definir estratégias de tratamento de falhas para integrações externas, incluindo Circuit Breaker, Retry e Dead Letter Queue.

---

## Princípio

> Falhas em integrações externas são **normais**, não exceções. O sistema deve ser projetado para lidar com elas graciosamente.

---

## Circuit Breaker

### Conceito

Protege o sistema contra falhas em cascata ao interromper chamadas para um provider que está com problemas.

### Estados

```
CLOSED (normal) → falhas atingem threshold → OPEN (bloqueado)
       ↑                                          ↓
       └── sucesso na prova ←── HALF_OPEN (testando)
                                    ↑
                          timeout expira ←── OPEN
```

| Estado | Comportamento |
|--------|--------------|
| `CLOSED` | Chamadas normais, contando falhas |
| `OPEN` | Rejeita chamadas imediatamente, retorna fallback |
| `HALF_OPEN` | Permite 1 chamada de teste, decide próximo estado |

### Configuração por Provider

| Provider | Failure Threshold | Reset Timeout | Implementação |
|----------|------------------|---------------|---------------|
| Instagram | 5 falhas em 60s | 120s | Redis counter + state |
| TikTok | 5 falhas em 60s | 120s | Redis counter + state |
| YouTube | 5 falhas em 60s | 120s | Redis counter + state |
| OpenAI | 3 falhas em 60s | 180s | Redis counter + state |

### Implementação

```php
class CircuitBreaker
{
    public function call(string $service, callable $action, callable $fallback);
    public function getState(string $service): CircuitState;
    public function recordSuccess(string $service): void;
    public function recordFailure(string $service): void;
}
```

Armazenamento em Redis:
- `circuit:{service}:failures` — counter com TTL.
- `circuit:{service}:state` — estado atual.
- `circuit:{service}:last_failure` — timestamp da última falha.

### Eventos

- `CircuitBreakerOpened` → alerta + log.
- `CircuitBreakerClosed` → log de recuperação.
- `CircuitBreakerHalfOpen` → log de teste.

---

## Retry Strategy

### Exponential Backoff

```
Tentativa 1: imediata
Tentativa 2: após 60 segundos
Tentativa 3: após 300 segundos (5 min)
```

Fórmula: `delay = base_delay * 2^(attempt - 1)` com jitter.

### Jitter

Adiciona variação aleatória para evitar thundering herd:
```php
$delay = $baseDelay * pow(2, $attempt - 1);
$jitter = random_int(0, (int)($delay * 0.2)); // 20% jitter
$finalDelay = $delay + $jitter;
```

### Classificação de Erros

| Tipo | Retentável? | Exemplos |
|------|------------|----------|
| Transiente | Sim | Timeout, 500, 502, 503, 429 |
| Permanente | Não | 400, 401, 403, 404 |
| Rate Limit | Sim (com delay) | 429 com Retry-After |

### Regras

- Erros permanentes: **não retentar** (marcar como failed imediatamente).
- Rate limit 429: respeitar header `Retry-After` do provider.
- Timeout: retentar com backoff crescente.
- 5xx: retentar com backoff.
- Erro de rede: retentar com backoff.

---

## Dead Letter Queue (DLQ)

### Quando um job vai para DLQ

- Após esgotar todas as tentativas de retry.
- Erro classificado como não retentável mas que precisa de atenção.
- Job com payload inválido (desserialização falhou).

### Tratamento

1. Job movido para tabela/fila de DLQ.
2. Evento `JobFailed` emitido.
3. Log estruturado com contexto completo.
4. Alerta para time de operações.
5. **Reprocessamento manual** apenas (nunca automático).

### Informações no DLQ

- Job class e payload.
- Número de tentativas.
- Último erro e stack trace.
- Timestamps de todas as tentativas.
- `userId` e `correlationId`.

---

## Fallbacks

Quando circuit breaker está OPEN ou operação falha permanentemente:

| Operação | Fallback |
|----------|----------|
| Publicação | Marcar como `failed`, notificar usuário |
| Sync analytics | Usar última métrica cached, agendar retry |
| Captura de comentários | Agendar próxima tentativa |
| Refresh de token | Marcar conta como `token_expired` |
| Webhook delivery | Registrar falha, desativar após 10 falhas consecutivas |
| Geração IA | Retornar 503 ao usuário |

---

## Timeouts

| Operação | Timeout | Justificativa |
|----------|---------|---------------|
| API Instagram | 30s | Upload pode ser lento |
| API TikTok | 30s | Upload de vídeo |
| API YouTube | 60s | Upload resumable |
| API OpenAI | 30s | Geração de texto |
| Webhook delivery | 10s | Endpoint do cliente |
| Database query | 5s | Queries otimizadas |
| Redis operation | 2s | Operações O(1) |

---

## Anti-Patterns

- Retry infinito (sempre ter limite de tentativas).
- Retry sem backoff (sobrecarrega serviço com problemas).
- Retry para erros permanentes (400, 401, 403, 404).
- Circuit breaker global (deve ser por provider/serviço).
- Falha silenciosa (sem log, sem alerta, sem notificação).
- Fallback que esconde problemas permanentes.
- DLQ sem processo de reprocessamento definido.
- Ignorar `Retry-After` header dos providers.
