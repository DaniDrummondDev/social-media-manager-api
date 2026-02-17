# Event-Driven Architecture — Social Media Manager API

## Objetivo

Definir a arquitetura de eventos do sistema, incluindo Domain Events, Integration Events e regras de propagação.

> Referência: ADR-007 (Domain Events)

---

## Princípios Não Negociáveis

- Eventos são **imutáveis** — representam fatos passados, nunca são alterados.
- Eventos **não contêm lógica** — são dados, não comportamento.
- Eventos **não substituem Use Cases** — Use Cases tomam decisões, eventos notificam.
- Publicar evento **não é obrigatório para sucesso** — a operação principal não falha se o evento falhar.

---

## Tipos de Eventos

### Domain Events (Domínio)

Representam mudanças de estado no domínio:

| Bounded Context | Eventos |
|----------------|---------|
| Identity | `UserRegistered`, `UserVerified`, `PasswordChanged`, `AccountDeletionRequested` |
| Organization | `OrganizationCreated`, `MemberInvited`, `MemberRemoved`, `MemberRoleChanged` |
| SocialAccount | `SocialAccountConnected`, `SocialAccountDisconnected`, `TokenRefreshed`, `TokenExpired` |
| Campaign | `CampaignCreated`, `CampaignUpdated`, `CampaignDeleted`, `ContentCreated`, `ContentUpdated`, `ContentDeleted` |
| ContentAI | `ContentGenerated`, `AISettingsUpdated` |
| Publishing | `PostScheduled`, `PostDispatched`, `PostPublished`, `PostFailed`, `PostCancelled` |
| Analytics | `MetricsSynced`, `ReportGenerated`, `ReportExpired` |
| Engagement | `CommentCaptured`, `CommentReplied`, `AutomationTriggered`, `AutomationExecuted`, `WebhookDelivered` |
| Media | `MediaUploaded`, `MediaScanned`, `MediaDeleted` |
| Billing | `SubscriptionCreated`, `SubscriptionUpgraded`, `SubscriptionDowngraded`, `SubscriptionCanceled`, `SubscriptionExpired`, `PaymentFailed`, `PaymentSucceeded`, `PlanLimitReached` |
| PlatformAdmin | `OrganizationSuspended`, `OrganizationUnsuspended`, `UserBanned`, `UserUnbanned`, `PlanCreated`, `PlanUpdated`, `SystemConfigUpdated`, `MaintenanceModeEnabled` |

### Integration Events (Integração)

Originados de sistemas externos:

- `SocialProviderTokenRefreshed`
- `SocialProviderPostConfirmed`
- `ExternalWebhookReceived`

### System Events (Sistema)

Eventos técnicos:

- `JobFailed`
- `CircuitBreakerOpened`
- `CircuitBreakerClosed`
- `ProviderRateLimitReached`

---

## Modos de Processamento

### Síncrono (Mesmo Request)

Processado imediatamente, no mesmo ciclo de vida do request.

| Evento | Listener | Justificativa |
|--------|----------|---------------|
| `ContentCreated` | ValidateMediaCompatibility | Resposta imediata ao usuário |
| `PostScheduled` | ValidateScheduleConflict | Validação síncrona |
| `MediaUploaded` | CalculateCompatibility | Resultado imediato |

### Assíncrono (Via Queue)

Processado em background, sem impacto no response time.

| Evento | Listener | Fila |
|--------|----------|------|
| `UserRegistered` | SendVerificationEmail | `notifications` |
| `PostPublished` | SyncPostMetrics | `analytics-sync` |
| `PostPublished` | NotifyUser | `notifications` |
| `PostFailed` | NotifyUserOfFailure | `notifications` |
| `CommentCaptured` | EvaluateAutomationRules | `default` |
| `CommentCaptured` | AnalyzeSentiment | `default` |
| `AutomationTriggered` | ExecuteAutomation | `default` |
| `MediaUploaded` | ScanMedia | `media-processing` |
| `ContentGenerated` | TrackAIUsage | `low` |
| `TokenExpired` | NotifyReconnection | `notifications` |
| `SubscriptionExpired` | DowngradeToFreePlan | `default` |
| `PaymentFailed` | NotifyPaymentFailure | `notifications` |
| `SubscriptionCanceled` | NotifySubscriptionCanceled | `notifications` |
| `PlanLimitReached` | NotifyPlanLimitReached | `notifications` |
| `OrganizationSuspended` | PauseScheduledPosts | `default` |
| `OrganizationSuspended` | NotifyOrgSuspended | `notifications` |
| `UserBanned` | InvalidateUserSessions | `high` |

---

## Estrutura de um Evento

```php
abstract class DomainEvent
{
    public readonly string $eventId;          // UUID
    public readonly string $organizationId;   // Tenant (organização)
    public readonly string $userId;           // Quem causou o evento
    public readonly string $occurredAt;       // ISO 8601
    public readonly string $correlationId;    // Para tracing
}

class PostPublished extends DomainEvent
{
    public function __construct(
        public readonly string $scheduledPostId,
        public readonly string $contentId,
        public readonly string $provider,
        public readonly string $externalPostId,
        public readonly string $externalPostUrl,
        string $organizationId,
        string $userId,
        string $correlationId,
    ) {
        parent::__construct($organizationId, $userId, $correlationId);
    }
}
```

### Campos Obrigatórios

- `eventId`: identificador único do evento (UUID v4).
- `organizationId`: organização onde o evento ocorreu (tenant).
- `userId`: quem causou o evento.
- `occurredAt`: quando aconteceu (ISO 8601 UTC).
- `correlationId`: para rastrear cadeia de eventos.

---

## Posicionamento nas Camadas

| Camada | Responsabilidade |
|--------|-----------------|
| **Domain** | Define os eventos (classes) |
| **Application** | Publica eventos e reage via listeners |
| **Infrastructure** | Implementa event bus (Laravel Events + Queue) |

- Domain events são **definidos** no Domain Layer.
- Domain events são **publicados** pelo Application Layer (Use Cases).
- Domain events são **processados** por Listeners no Application Layer.
- Infrastructure Layer implementa o dispatch (Laravel `event()`, queue dispatch).

---

## Event Handlers (Listeners)

### Regras

- Um handler = uma responsabilidade.
- Handlers não contêm lógica de domínio.
- Handlers podem: despachar jobs, chamar Use Cases, integrar adapters.
- Handlers devem ser **idempotentes** (processar mesmo evento 2x = mesmo resultado).
- Handlers assíncronos devem tratar falhas graciosamente.

---

## Observabilidade

Métricas por evento:
- `events_published_total` (counter)
- `events_consumed_total` (counter)
- `events_failed_total` (counter)
- `event_processing_duration_seconds` (histogram)

Logs:
- Publicação de evento: `event.published { eventId, type, organizationId, userId }`
- Processamento: `event.processed { eventId, type, listener, duration_ms }`
- Falha: `event.failed { eventId, type, listener, error }`

---

## Anti-Patterns

- Usar eventos como comandos (evento = fato passado, comando = intenção futura).
- Lógica de negócio em event handlers (handlers orquestram, não decidem).
- Eventos síncronos bloqueantes para operações pesadas.
- Eventos sem `organizationId` ou `userId` (impossibilita isolamento e rastreamento).
- Eventos mutáveis (alterar evento após publicação).
- Eventos não auditados (toda publicação deve ser logada).
- Handler que depende de ordem de execução de outros handlers.
