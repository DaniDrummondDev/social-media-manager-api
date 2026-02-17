# Observability Strategy — Social Media Manager API

## Objetivo

Definir a estratégia de observabilidade (logs, métricas, traces, health checks) para garantir visibilidade completa sobre o comportamento do sistema.

---

## Os 3 Pilares

### 1. Logs Estruturados

**Formato**: JSON (nunca plain text em produção).

**Campos obrigatórios em todo log**:

```json
{
  "timestamp": "2026-02-15T10:30:00.123Z",
  "level": "info",
  "message": "Post published successfully",
  "context": {
    "organization_id": "org-uuid-...",
    "user_id": "550e8400-...",
    "correlation_id": "req-abc123",
    "trace_id": "trace-xyz789",
    "service": "social-media-manager",
    "environment": "production"
  }
}
```

**Níveis de log**:

| Nível | Uso |
|-------|-----|
| `emergency` | Sistema inutilizável |
| `error` | Erro que requer ação, falha de publicação permanente |
| `warning` | Situação anormal mas tratada (retry, circuit breaker open) |
| `info` | Operação bem-sucedida, mudança de estado |
| `debug` | Detalhes para troubleshooting (desabilitado em produção) |

**Regras**:
- Dados sensíveis **nunca** em logs (tokens, senhas, PII).
- `organization_id` e `user_id` presentes em todo log de operação.
- `correlation_id` propaga entre requests e jobs.
- Logs de auditoria separados de logs operacionais.

### 2. Métricas

**Tipos de métricas**:

#### Métricas de Aplicação
- `http_request_duration_seconds` (histogram, por endpoint)
- `http_requests_total` (counter, por status code)
- `queue_job_duration_seconds` (histogram, por job class)
- `queue_jobs_total` (counter, por status: success/failed)
- `queue_size` (gauge, por fila)
- `cache_hits_total` / `cache_misses_total` (counter)

#### Métricas de Negócio
- `posts_published_total` (counter, por provider)
- `posts_failed_total` (counter, por provider, por tipo de erro)
- `ai_generations_total` (counter, por tipo)
- `ai_tokens_used_total` (counter, input/output)
- `comments_captured_total` (counter, por provider)
- `automations_executed_total` (counter, por action_type)
- `webhooks_delivered_total` (counter, por status)
- `media_uploaded_total` (counter, por mime_type)

#### Métricas de Infraestrutura
- `db_query_duration_seconds` (histogram)
- `db_connections_active` (gauge)
- `redis_memory_used_bytes` (gauge)
- `external_api_duration_seconds` (histogram, por provider)
- `external_api_errors_total` (counter, por provider)
- `circuit_breaker_state` (gauge, por service: 0=closed, 1=open, 0.5=half-open)

### 3. Tracing Distribuído

- `correlation_id`: gerado no início do request HTTP, propagado para jobs e eventos.
- `trace_id`: identificador único da operação específica.
- Headers de propagação: `X-Correlation-ID`, `X-Trace-ID`.
- Jobs carregam `correlation_id` do request que os originou.
- Permite rastrear: request → job → evento → notificação.

---

## Health Checks

### Endpoints

| Endpoint | Tipo | Descrição |
|----------|------|-----------|
| `GET /health/live` | Liveness | App está rodando |
| `GET /health/ready` | Readiness | App pode aceitar requests |
| `GET /health/startup` | Startup | App inicializou corretamente |

### Liveness (`/health/live`)
- Retorna 200 se o processo está vivo.
- Sem verificação de dependências.
- Usado por load balancer e orchestrator.

### Readiness (`/health/ready`)
- Verifica: PostgreSQL, Redis, Queue workers.
- Retorna 200 apenas se todas as dependências estão OK.
- Usado para routing de traffic.

```json
{
  "status": "healthy",
  "checks": {
    "database": { "status": "up", "latency_ms": 2 },
    "redis": { "status": "up", "latency_ms": 1 },
    "queue": { "status": "up", "workers": 10 }
  }
}
```

### Startup (`/health/startup`)
- Verifica que migrations rodaram, config está carregada.
- Executado uma vez no boot.

---

## SLIs e SLOs

### Service Level Indicators

| SLI | Medição |
|-----|---------|
| Availability | % de requests bem-sucedidos (non-5xx) |
| Latency (p95) | 95th percentile do response time |
| Publishing success rate | % de publicações bem-sucedidas |
| Error rate | % de requests com erro |

### Service Level Objectives

| SLO | Target |
|-----|--------|
| API availability | 99.9% (mensal) |
| API latency p95 | < 500ms (endpoints CRUD) |
| Publishing success rate | > 95% |
| Analytics sync freshness | < 6 horas |

---

## Alertas

### Princípio

> Alertas são **acionáveis**. Se não requer ação humana, é métrica, não alerta.

### Alertas Críticos

| Alerta | Condição | Ação |
|--------|----------|------|
| API down | 0 requests em 5min | Investigar imediatamente |
| DB connection pool exhausted | connections > 90% | Escalar ou investigar queries |
| Circuit breaker open | Qualquer provider | Verificar status do provider |
| DLQ growing | > 10 jobs em 1h | Investigar falhas |
| Publishing failure spike | > 20% em 15min | Verificar providers |

### Alertas de Warning

| Alerta | Condição | Ação |
|--------|----------|------|
| Latency p95 high | > 1s por 10min | Investigar bottleneck |
| Queue backlog | > 100 jobs pendentes | Escalar workers |
| Disk usage high | > 80% | Cleanup ou expandir |
| Token refresh failures | > 5 em 1h | Verificar providers |

---

## Anti-Patterns

- Logs em plain text (difícil de processar e buscar).
- Dados sensíveis em logs ou métricas.
- Alertas informativos que não requerem ação (alert fatigue).
- Métricas sem labels de contexto (impossível filtrar).
- Health checks que verificam dependências externas no liveness.
- Logs sem `organization_id`, `user_id` ou `correlation_id`.
- Tracing que não propaga entre requests e jobs.
