# ADR-004: Redis para Cache e Filas

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema precisa de:
- **Cache distribuído:** Configurações de usuário, dados de perfil social, respostas de API
- **Sistema de filas:** Publicações agendadas, sync de métricas, envio de emails, exports
- **Rate limiting distribuído:** Controle de rate limit por provider, por usuário, por IP
- **Contadores atômicos:** Quota de API por provider, limite diário de automação
- **Sessões stateless:** Suportar múltiplos workers sem estado compartilhado em memória

Tudo isso precisa ser rápido (sub-millisecond), confiável e escalável horizontalmente.

## Decisão

Adotar **Redis** como solução unificada para cache, filas, rate limiting e contadores.

### Bancos lógicos (databases)

| Database | Uso | Justificativa |
|----------|-----|---------------|
| `0` | Cache da aplicação | Dados voláteis, pode ser flushado sem impacto |
| `1` | Filas (queues) | Isolamento de jobs para não conflitar com cache flush |
| `2` | Rate limiting e contadores | TTLs independentes, dados efêmeros |
| `3` | Sessões e tokens temporários | Dados de sessão isolados |

### Filas (Queues)

| Fila | Prioridade | Uso |
|------|-----------|-----|
| `high` | Máxima | Publicação imediata |
| `default` | Normal | Publicação agendada, respostas automáticas |
| `low` | Baixa | Sync de métricas, cleanup, exports |
| `notifications` | Normal | Emails, alertas |

### Workers recomendados

```bash
# Worker de alta prioridade (dedicado a publicações)
php artisan queue:work redis --queue=high,default --tries=3 --backoff=60,300,900

# Worker de baixa prioridade (analytics, exports)
php artisan queue:work redis --queue=low,notifications --tries=3

# Worker de notificações (se necessário separar)
php artisan queue:work redis --queue=notifications --tries=2
```

### Cache strategy

| Dado | TTL | Invalidação |
|------|-----|-------------|
| Configurações do usuário | 1 hora | On update |
| Perfil social account | 6 horas | On sync |
| Métricas de conteúdo | 1 hora (recente) / 6 horas (antigo) | On sync |
| Redes disponíveis | 24 horas | On deploy |
| Respostas de API (listagens) | 5 minutos | On write |

### Rate limiting

```php
// Exemplo: controle de quota do YouTube (10k units/dia)
Redis::throttle('youtube:quota:' . $userId)
    ->allow(10000)
    ->every(86400)
    ->then(fn () => $this->publishToYouTube($content));

// Exemplo: rate limit da API interna
Redis::throttle('api:' . $ip)
    ->allow(60)
    ->every(60)
    ->then(fn () => $next($request));
```

### Monitoramento

- **Laravel Horizon** para dashboard de filas em tempo real
- Métricas expostas: jobs processados, falhos, tempo de espera, throughput
- Alertas quando fila `high` tem jobs esperando > 1 minuto

## Alternativas consideradas

### 1. Database queues (Laravel)
- **Prós:** Sem dependência extra, persistência garantida
- **Contras:** Muito mais lento, polling constante, lock contention, não escala
- **Por que descartado:** Incompatível com requisito de latência < 5s para publicação

### 2. Amazon SQS / RabbitMQ
- **Prós:** Filas dedicadas, mais robustas, SQS é serverless
- **Contras:** Outro serviço para gerenciar, latência de rede, custo adicional, sem cache embutido
- **Por que descartado:** Redis já atende filas E cache. Adicionar SQS traz complexidade sem benefício claro para nosso volume

### 3. Redis + Memcached (cache separado)
- **Prós:** Memcached é mais eficiente para cache puro
- **Contras:** Dois serviços para gerenciar, sem structures avançadas no Memcached
- **Por que descartado:** Redis é suficiente para ambos e oferece estruturas de dados ricas (sorted sets, pub/sub)

## Consequências

### Positivas
- Solução unificada para cache, filas, rate limiting e contadores
- Sub-millisecond de latência para operações de cache
- Laravel Horizon oferece dashboard poderoso out-of-the-box
- Filas separadas por prioridade garantem que publicações não sejam atrasadas por sync de métricas
- Escalável horizontalmente via Redis Cluster se necessário

### Negativas
- Single point of failure se não configurado com replicação
- Dados em memória podem ser perdidos em crash (mitigado por persistência AOF)
- Limitado pela memória disponível
- Jobs de longa duração (export de PDF) podem bloquear workers

### Riscos
- Redis down = sistema degradado (sem cache, sem filas) — mitigado por fallback para database queues e circuit breaker
- Memória esgotada = eviction de cache inesperado — monitorar uso de memória, configurar maxmemory-policy
