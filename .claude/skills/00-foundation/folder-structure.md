# Folder Structure — Social Media Manager API

## Objetivo

Definir a estrutura de diretórios do projeto seguindo DDD + Clean Architecture, conforme ADR-011.

> Referência: ADR-011 (Folder Structure)

---

## Estrutura Completa

```
app/
├── Domain/
│   ├── Identity/
│   │   ├── Entities/
│   │   │   └── User.php
│   │   ├── ValueObjects/
│   │   │   ├── Email.php
│   │   │   ├── HashedPassword.php
│   │   │   └── TwoFactorSecret.php
│   │   ├── Events/
│   │   │   ├── UserRegistered.php
│   │   │   ├── UserVerified.php
│   │   │   ├── PasswordChanged.php
│   │   │   └── AccountDeletionRequested.php
│   │   ├── Repositories/
│   │   │   └── UserRepositoryInterface.php
│   │   ├── Services/
│   │   │   └── PasswordPolicyService.php
│   │   └── Exceptions/
│   │       ├── InvalidEmailException.php
│   │       └── UserAlreadyExistsException.php
│   │
│   ├── Organization/
│   │   ├── Entities/
│   │   │   ├── Organization.php
│   │   │   └── OrganizationMember.php
│   │   ├── ValueObjects/
│   │   │   └── OrganizationRole.php
│   │   ├── Events/
│   │   │   ├── OrganizationCreated.php
│   │   │   ├── MemberInvited.php
│   │   │   ├── MemberRemoved.php
│   │   │   └── MemberRoleChanged.php
│   │   ├── Repositories/
│   │   │   ├── OrganizationRepositoryInterface.php
│   │   │   └── OrganizationMemberRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── OrganizationLimitExceededException.php
│   │
│   ├── SocialAccount/
│   │   ├── Entities/
│   │   │   └── SocialAccount.php
│   │   ├── ValueObjects/
│   │   │   ├── SocialProvider.php
│   │   │   ├── EncryptedToken.php
│   │   │   └── OAuthCredentials.php
│   │   ├── Events/
│   │   │   ├── SocialAccountConnected.php
│   │   │   ├── SocialAccountDisconnected.php
│   │   │   └── TokenRefreshed.php
│   │   ├── Repositories/
│   │   │   └── SocialAccountRepositoryInterface.php
│   │   └── Contracts/
│   │       ├── SocialAuthenticatorInterface.php
│   │       ├── SocialPublisherInterface.php
│   │       ├── SocialAnalyticsInterface.php
│   │       └── SocialEngagementInterface.php
│   │
│   ├── Campaign/
│   │   ├── Entities/
│   │   │   ├── Campaign.php
│   │   │   ├── Content.php
│   │   │   └── ContentNetworkOverride.php
│   │   ├── ValueObjects/
│   │   │   ├── CampaignStatus.php
│   │   │   ├── ContentStatus.php
│   │   │   ├── Hashtag.php
│   │   │   └── NetworkOverride.php
│   │   ├── Events/
│   │   │   ├── CampaignCreated.php
│   │   │   ├── ContentCreated.php
│   │   │   ├── ContentUpdated.php
│   │   │   └── ContentDeleted.php
│   │   ├── Repositories/
│   │   │   ├── CampaignRepositoryInterface.php
│   │   │   └── ContentRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── CampaignLimitExceededException.php
│   │
│   ├── ContentAI/
│   │   ├── Entities/
│   │   │   ├── AIGeneration.php
│   │   │   └── AISettings.php
│   │   ├── ValueObjects/
│   │   │   ├── Tone.php
│   │   │   ├── Language.php
│   │   │   └── AIUsage.php
│   │   ├── Events/
│   │   │   └── ContentGenerated.php
│   │   └── Repositories/
│   │       ├── AIGenerationRepositoryInterface.php
│   │       └── AISettingsRepositoryInterface.php
│   │
│   ├── Publishing/
│   │   ├── Entities/
│   │   │   └── ScheduledPost.php
│   │   ├── ValueObjects/
│   │   │   ├── PublishingStatus.php
│   │   │   ├── ScheduleTime.php
│   │   │   └── PublishError.php
│   │   ├── Events/
│   │   │   ├── PostScheduled.php
│   │   │   ├── PostDispatched.php
│   │   │   ├── PostPublished.php
│   │   │   ├── PostFailed.php
│   │   │   └── PostCancelled.php
│   │   ├── Repositories/
│   │   │   └── ScheduledPostRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── PublishingLockedException.php
│   │
│   ├── Analytics/
│   │   ├── Entities/
│   │   │   ├── ContentMetric.php
│   │   │   ├── ContentMetricSnapshot.php
│   │   │   ├── AccountMetric.php
│   │   │   └── ReportExport.php
│   │   ├── ValueObjects/
│   │   │   ├── MetricPeriod.php
│   │   │   └── ExportFormat.php
│   │   ├── Events/
│   │   │   ├── MetricsSynced.php
│   │   │   └── ReportGenerated.php
│   │   └── Repositories/
│   │       ├── ContentMetricRepositoryInterface.php
│   │       ├── AccountMetricRepositoryInterface.php
│   │       └── ReportExportRepositoryInterface.php
│   │
│   ├── Engagement/
│   │   ├── Entities/
│   │   │   ├── Comment.php
│   │   │   ├── AutomationRule.php
│   │   │   ├── AutomationExecution.php
│   │   │   ├── BlacklistWord.php
│   │   │   ├── WebhookEndpoint.php
│   │   │   └── WebhookDelivery.php
│   │   ├── ValueObjects/
│   │   │   ├── Sentiment.php
│   │   │   ├── ActionType.php
│   │   │   ├── ConditionOperator.php
│   │   │   └── WebhookSecret.php
│   │   ├── Events/
│   │   │   ├── CommentCaptured.php
│   │   │   ├── CommentReplied.php
│   │   │   ├── AutomationTriggered.php
│   │   │   └── WebhookDelivered.php
│   │   ├── Repositories/
│   │   │   ├── CommentRepositoryInterface.php
│   │   │   ├── AutomationRuleRepositoryInterface.php
│   │   │   └── WebhookEndpointRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── CommentAlreadyRepliedException.php
│   │
│   ├── Media/
│   │   ├── Entities/
│   │   │   └── Media.php
│   │   ├── ValueObjects/
│   │   │   ├── MediaType.php
│   │   │   ├── MimeType.php
│   │   │   ├── FileSize.php
│   │   │   ├── Dimensions.php
│   │   │   └── Compatibility.php
│   │   ├── Events/
│   │   │   ├── MediaUploaded.php
│   │   │   ├── MediaScanned.php
│   │   │   └── MediaDeleted.php
│   │   ├── Repositories/
│   │   │   └── MediaRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── MediaInUseException.php
│   │
│   ├── Billing/
│   │   ├── Entities/
│   │   │   ├── Plan.php
│   │   │   ├── Subscription.php
│   │   │   ├── UsageRecord.php
│   │   │   └── Invoice.php
│   │   ├── ValueObjects/
│   │   │   ├── BillingCycle.php
│   │   │   ├── SubscriptionStatus.php
│   │   │   ├── PlanLimits.php
│   │   │   └── Money.php
│   │   ├── Events/
│   │   │   ├── SubscriptionCreated.php
│   │   │   ├── SubscriptionUpgraded.php
│   │   │   ├── SubscriptionCanceled.php
│   │   │   ├── SubscriptionExpired.php
│   │   │   ├── PaymentFailed.php
│   │   │   ├── PaymentSucceeded.php
│   │   │   └── PlanLimitReached.php
│   │   ├── Repositories/
│   │   │   ├── PlanRepositoryInterface.php
│   │   │   ├── SubscriptionRepositoryInterface.php
│   │   │   └── InvoiceRepositoryInterface.php
│   │   ├── Contracts/
│   │   │   └── PaymentGatewayInterface.php
│   │   └── Exceptions/
│   │       ├── PlanLimitExceededException.php
│   │       └── SubscriptionRequiredException.php
│   │
│   ├── PlatformAdmin/
│   │   ├── Entities/
│   │   │   ├── PlatformAdmin.php
│   │   │   ├── SystemConfig.php
│   │   │   └── AdminAuditEntry.php
│   │   ├── ValueObjects/
│   │   │   └── PlatformRole.php
│   │   ├── Events/
│   │   │   ├── OrganizationSuspended.php
│   │   │   ├── OrganizationUnsuspended.php
│   │   │   ├── UserBanned.php
│   │   │   ├── UserUnbanned.php
│   │   │   ├── PlanCreated.php
│   │   │   ├── PlanUpdated.php
│   │   │   ├── SystemConfigUpdated.php
│   │   │   └── MaintenanceModeEnabled.php
│   │   ├── Repositories/
│   │   │   ├── PlatformAdminRepositoryInterface.php
│   │   │   └── SystemConfigRepositoryInterface.php
│   │   └── Exceptions/
│   │       └── InsufficientAdminPrivilegesException.php
│   │
│   └── Shared/
│       ├── ValueObjects/
│       │   ├── Uuid.php
│       │   └── DateRange.php
│       ├── Events/
│       │   └── DomainEvent.php
│       └── Exceptions/
│           └── DomainException.php
│
├── Application/
│   ├── Identity/
│   │   ├── UseCases/
│   │   ├── DTOs/
│   │   └── Listeners/
│   ├── Organization/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── SocialAccount/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Campaign/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── ContentAI/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Publishing/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Analytics/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Engagement/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Media/
│   │   ├── UseCases/
│   │   └── DTOs/
│   ├── Billing/
│   │   ├── UseCases/
│   │   └── DTOs/
│   └── PlatformAdmin/
│       ├── UseCases/
│       └── DTOs/
│
├── Infrastructure/
│   ├── Identity/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── Organization/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── SocialAccount/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── Campaign/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── ContentAI/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── Publishing/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   ├── Jobs/
│   │   └── Providers/
│   ├── Analytics/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   ├── Jobs/
│   │   └── Providers/
│   ├── Engagement/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   ├── Jobs/
│   │   └── Providers/
│   ├── Media/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   ├── Jobs/
│   │   └── Providers/
│   ├── Billing/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   ├── Jobs/
│   │   └── Providers/
│   ├── PlatformAdmin/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Controllers/
│   │   └── Providers/
│   ├── Shared/
│   │   ├── Encryption/
│   │   │   └── SocialTokenEncrypter.php
│   │   ├── Http/
│   │   │   ├── Middleware/
│   │   │   └── Resources/
│   │   ├── Queue/
│   │   └── Cache/
│   └── External/
│       ├── Instagram/
│       │   ├── InstagramAuthenticator.php
│       │   ├── InstagramPublisher.php
│       │   ├── InstagramAnalytics.php
│       │   └── InstagramEngagement.php
│       ├── TikTok/
│       │   ├── TikTokAuthenticator.php
│       │   ├── TikTokPublisher.php
│       │   ├── TikTokAnalytics.php
│       │   └── TikTokEngagement.php
│       ├── YouTube/
│       │   ├── YouTubeAuthenticator.php
│       │   ├── YouTubePublisher.php
│       │   ├── YouTubeAnalytics.php
│       │   └── YouTubeEngagement.php
│       └── OpenAI/
│           └── PrismAIService.php
│
routes/
├── api/
│   └── v1/
│       ├── auth.php
│       ├── organizations.php
│       ├── social-accounts.php
│       ├── campaigns.php
│       ├── contents.php
│       ├── ai.php
│       ├── publishing.php
│       ├── analytics.php
│       ├── engagement.php
│       ├── media.php
│       ├── billing.php
│       ├── admin.php
│       └── webhooks.php
│
tests/
├── Unit/
│   ├── Domain/
│   │   ├── Identity/
│   │   ├── Organization/
│   │   ├── SocialAccount/
│   │   ├── Campaign/
│   │   ├── ContentAI/
│   │   ├── Publishing/
│   │   ├── Analytics/
│   │   ├── Engagement/
│   │   ├── Media/
│   │   ├── Billing/
│   │   └── PlatformAdmin/
│   └── Application/
│       ├── Identity/
│       ├── Campaign/
│       ├── Publishing/
│       └── ...
├── Integration/
│   ├── Repositories/
│   ├── External/
│   └── Jobs/
├── Feature/
│   ├── Auth/
│   ├── SocialAccounts/
│   ├── Campaigns/
│   ├── AI/
│   ├── Publishing/
│   ├── Analytics/
│   ├── Engagement/
│   ├── Media/
│   ├── Billing/
│   └── Admin/
└── Architecture/
    └── ArchitectureTest.php
```

---

## Naming Conventions

| Tipo | Padrão | Exemplo |
|------|--------|---------|
| Entity | Singular, PascalCase | `Campaign.php` |
| Value Object | Singular, PascalCase | `Sentiment.php` |
| Use Case | Verbo + Substantivo + `UseCase` | `CreateCampaignUseCase.php` |
| DTO | Substantivo + `DTO` | `CreateCampaignDTO.php` |
| Repository Interface | Substantivo + `RepositoryInterface` | `CampaignRepositoryInterface.php` |
| Repository Impl | `Eloquent` + Substantivo + `Repository` | `EloquentCampaignRepository.php` |
| Eloquent Model | Substantivo + `Model` | `CampaignModel.php` |
| Job | Verbo + Substantivo + `Job` | `ProcessScheduledPostJob.php` |
| Domain Event | Substantivo + Verbo Passado | `ContentCreated.php` |
| Listener | Verbo + `On` + Evento | `SyncMetricsOnPostPublished.php` |
| Controller | Substantivo + `Controller` | `CampaignController.php` |
| Form Request | Verbo + Substantivo + `Request` | `CreateCampaignRequest.php` |
| API Resource | Substantivo + `Resource` | `CampaignResource.php` |

---

## Onde Cada Classe Pertence

| Tipo | Camada | Diretório |
|------|--------|-----------|
| Entity, Value Object | Domain | `app/Domain/{Context}/Entities/` ou `ValueObjects/` |
| Domain Event | Domain | `app/Domain/{Context}/Events/` |
| Repository Interface | Domain | `app/Domain/{Context}/Repositories/` |
| Adapter Interface | Domain | `app/Domain/{Context}/Contracts/` |
| Domain Exception | Domain | `app/Domain/{Context}/Exceptions/` |
| Use Case | Application | `app/Application/{Context}/UseCases/` |
| DTO | Application | `app/Application/{Context}/DTOs/` |
| Event Listener | Application | `app/Application/{Context}/Listeners/` |
| Eloquent Model | Infrastructure | `app/Infrastructure/{Context}/Models/` |
| Repository Impl | Infrastructure | `app/Infrastructure/{Context}/Repositories/` |
| Controller | Infrastructure | `app/Infrastructure/{Context}/Controllers/` |
| Job | Infrastructure | `app/Infrastructure/{Context}/Jobs/` |
| Service Provider | Infrastructure | `app/Infrastructure/{Context}/Providers/` |
| Social Media Adapter | Infrastructure | `app/Infrastructure/External/{Provider}/` |
| Middleware | Infrastructure | `app/Infrastructure/Shared/Http/Middleware/` |

---

## Anti-patterns

- Estrutura flat (`app/Models/`, `app/Http/Controllers/`) — usar bounded contexts.
- Diretório `app/Services/` genérico — usar Use Cases por context.
- Misturar Eloquent Models e Domain Entities no mesmo namespace.
- Controllers fora da Infrastructure Layer.
- Jobs na Application Layer (jobs são infraestrutura que chamam Use Cases).
- Testes sem espelhar a estrutura do código.
