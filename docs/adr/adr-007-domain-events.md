# ADR-007: Arquitetura Orientada a Eventos de Domínio

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema possui operações que desencadeiam múltiplos efeitos colaterais:

- Publicar um post → atualizar status do conteúdo → iniciar sync de métricas → notificar usuário
- Capturar um comentário → classificar sentimento → avaliar regras de automação → enviar webhook para CRM
- Conectar rede social → buscar perfil → agendar health check → registrar no audit log

Se cada operação executar todos os efeitos diretamente, teremos:
- Acoplamento alto entre bounded contexts
- Operações lentas (síncrono em cadeia)
- Dificuldade em adicionar novos comportamentos

## Decisão

Adotar **Domain Events** como mecanismo principal de comunicação entre bounded contexts
e para desacoplamento de efeitos colaterais.

### Tipos de eventos

| Tipo | Despacho | Uso |
|------|----------|-----|
| **Domain Event** | Síncrono (mesma request) | Efeitos imediatos dentro do mesmo bounded context |
| **Application Event** | Assíncrono (via queue) | Efeitos entre bounded contexts, operações lentas |

### Fluxo

```
┌──────────────┐     ┌─────────────────┐     ┌──────────────────┐
│  Use Case    │────▶│  Domain Entity   │────▶│  Domain Event    │
│  (publish)   │     │  (ScheduledPost) │     │  (PostPublished) │
└──────────────┘     └─────────────────┘     └────────┬─────────┘
                                                       │
                              ┌─────────────────────────┤
                              │                         │
                              ▼                         ▼
                     ┌─────────────────┐    ┌──────────────────────┐
                     │  Sync Listener  │    │  Async Listener      │
                     │  (update status)│    │  (sync metrics,      │
                     │                 │    │   notify user,       │
                     └─────────────────┘    │   send webhook)      │
                                            └──────────────────────┘
```

### Estrutura de um Domain Event

```php
// src/Domain/Publishing/Events/PostPublished.php
final readonly class PostPublished
{
    public function __construct(
        public ScheduledPostId $scheduledPostId,
        public ContentId $contentId,
        public SocialAccountId $socialAccountId,
        public string $externalPostId,
        public DateTimeImmutable $publishedAt,
    ) {}
}
```

### Eventos mapeados por bounded context

**Publishing →**
| Evento | Listeners |
|--------|-----------|
| `PostPublished` | UpdateContentStatus, StartMetricsSync, NotifyUser, SendWebhook |
| `PostFailed` | UpdateContentStatus, NotifyUser, ScheduleRetry, SendWebhook |
| `PostScheduled` | UpdateContentStatus, RegisterAuditLog |
| `PostCancelled` | UpdateContentStatus, RegisterAuditLog |

**Engagement →**
| Evento | Listeners |
|--------|-----------|
| `CommentCaptured` | ClassifySentiment, EvaluateAutomationRules, SendWebhookIfConfigured |
| `CommentReplied` | UpdateCommentStatus, RegisterAuditLog, SendWebhook |
| `AutomationRuleTriggered` | ExecuteAutomationAction, RegisterAuditLog, IncrementDailyCounter |

**Social Account →**
| Evento | Listeners |
|--------|-----------|
| `SocialAccountConnected` | FetchSocialProfile, ScheduleHealthCheck, RegisterAuditLog |
| `SocialAccountDisconnected` | CancelPendingSchedules, RevokeTokenAtProvider, RegisterAuditLog |
| `SocialAccountTokenExpired` | NotifyUser, MarkSchedulesAtRisk |

**Identity →**
| Evento | Listeners |
|--------|-----------|
| `UserRegistered` | SendVerificationEmail, CreateDefaultAISettings |
| `UserLoggedIn` | RegisterLoginHistory |

### Implementação com Laravel

```php
// Sync listeners (mesmo request) — Laravel Event
// Para efeitos imediatos dentro do bounded context
Event::listen(PostPublished::class, UpdateContentStatusListener::class);

// Async listeners (via queue) — Laravel Event com ShouldQueue
// Para efeitos entre bounded contexts e operações lentas
class StartMetricsSyncListener implements ShouldQueue
{
    public string $queue = 'low';

    public function handle(PostPublished $event): void
    {
        // Dispatch job de sync de métricas
    }
}
```

## Alternativas consideradas

### 1. Chamadas diretas entre use cases
- **Prós:** Simples, explícito, fácil de rastrear
- **Contras:** Acoplamento alto, operações sequenciais lentas, difícil de estender
- **Por que descartada:** Tornaria a adição de novos comportamentos (ex: novo webhook) uma alteração em código existente

### 2. Message broker externo (RabbitMQ / Kafka)
- **Prós:** Desacoplamento total, replay de eventos, event sourcing
- **Contras:** Complexidade operacional, outro serviço, overkill para o MVP
- **Por que descartado:** Redis queues do Laravel são suficientes. Migrar para Kafka/RabbitMQ quando volume justificar

### 3. Observer Pattern no Eloquent
- **Prós:** Nativo do Laravel, automático
- **Contras:** Acopla ao Eloquent (camada de infraestrutura), difícil de testar, eventos implícitos
- **Por que descartado:** Viola Clean Architecture — domínio não pode depender do Eloquent

## Consequências

### Positivas
- Bounded contexts desacoplados — comunicação via eventos
- Fácil adicionar novos listeners sem modificar código existente (OCP)
- Operações pesadas executam em background (async)
- Auditoria natural — cada evento é um registro do que aconteceu
- Testável — mock listeners em testes unitários

### Negativas
- Fluxo menos explícito — precisa rastrear listeners para entender efeitos
- Eventual consistency entre bounded contexts (async)
- Eventos podem falhar silenciosamente se listeners falharem — requer monitoramento
- Ordem de execução de listeners pode ser relevante

### Riscos
- Listeners assíncronos podem processar eventos fora de ordem — design listeners para serem idempotentes
- Cascata de eventos pode ser difícil de debugar — distributed tracing (ADR correlacionado: RNF-053)
