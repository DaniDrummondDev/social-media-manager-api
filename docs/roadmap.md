# Roadmap de Implementacao — Social Media Manager API

> **Versao:** 1.0.0
> **Data:** 2026-02-16
> **Status:** Draft

---

## Visao Geral

O roadmap esta dividido em **8 sprints** organizados por dependencia entre bounded contexts. Cada sprint entrega valor incremental e pode ser testado isoladamente.

```
Sprint 0 ─→ Sprint 1 ─→ Sprint 2 ─→ Sprint 3 ─→ Sprint 4
(Infra)     (Auth)      (Social)    (Content)    (Publish)
                                        ↓
            Sprint 7 ←─ Sprint 6 ←─ Sprint 5
            (Admin)     (Billing)   (Analytics
                                    + Engage)
```

---

## Sprint 0 — Scaffolding & Infraestrutura

**Objetivo:** Ambiente de desenvolvimento funcional com Docker, Laravel configurado, DDD folder structure, testes de arquitetura rodando e CI pronto.

### 0.1 Docker & Container

- [ ] `Dockerfile` multi-stage (PHP 8.4-FPM + Nginx)
- [ ] `docker-compose.yml` com servicos:
  - `app` — PHP 8.4-FPM (Laravel)
  - `nginx` — Reverse proxy
  - `postgres` — PostgreSQL 17 com extensao pgvector
  - `redis` — Cache, filas, rate limiting, tokens
  - `mailpit` — SMTP local para teste de emails
  - `horizon` — Laravel Horizon (dashboard de filas)
- [ ] `.env.example` com todas as variaveis documentadas
- [ ] Volumes para persistencia de dados (postgres, redis)
- [ ] Network interna entre containers

### 0.2 setup.sh

Script de bootstrap que automatiza o ambiente:

```bash
#!/bin/bash
# setup.sh — Bootstrap do ambiente de desenvolvimento

# 1. Copiar .env
cp .env.example .env

# 2. Build dos containers
docker compose build

# 3. Subir containers
docker compose up -d

# 4. Instalar dependencias PHP
docker compose exec app composer install

# 5. Gerar chaves
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:generate-keys  # RS256 keypair

# 6. Executar migrations
docker compose exec app php artisan migrate

# 7. Executar seeds (planos default, admin, configs)
docker compose exec app php artisan db:seed

# 8. Instalar Horizon
docker compose exec app php artisan horizon:install

# 9. Rodar testes de arquitetura (validar setup)
docker compose exec app php artisan test --filter=Architecture

# 10. Health check
curl -s http://localhost:8080/api/health | jq .
```

### 0.3 Laravel Project

- [ ] `composer create-project laravel/laravel` com PHP 8.4
- [ ] Configurar `composer.json` com autoload PSR-4 para namespaces DDD:
  - `App\\Domain\\` → `app/Domain/`
  - `App\\Application\\` → `app/Application/`
  - `App\\Infrastructure\\` → `app/Infrastructure/`
- [ ] Remover scaffolding default desnecessario (controllers, models, views)
- [ ] Configurar `config/database.php` para PostgreSQL
- [ ] Configurar `config/cache.php` e `config/queue.php` para Redis (databases 0-3)
- [ ] Instalar dependencias core:
  - `php-open-source-saver/jwt-auth` (JWT RS256)
  - `echolabsdev/prism` (Laravel AI SDK)
  - `pestphp/pest` + `pestphp/pest-plugin-arch` (testes)
  - `laravel/horizon` (filas)
  - `pgvector/pgvector` (embeddings)
  - `phpstan/phpstan` (analise estatica)
  - `laravel/pint` (code style)

### 0.4 Folder Structure (DDD)

Criar estrutura de diretorios conforme `folder-structure.md`:

- [ ] `app/Domain/` — Shared kernel (DomainEvent, Uuid, DomainException)
- [ ] `app/Application/` — Base vazia por contexto
- [ ] `app/Infrastructure/Shared/` — Middleware, Resources, Encryption
- [ ] `routes/api/v1/` — Arquivos de rota por contexto
- [ ] `tests/Architecture/ArchitectureTest.php` — Testes iniciais de camada

### 0.5 Base Infrastructure

- [ ] `DomainEvent` abstract class (base para todos os eventos)
- [ ] `Uuid` value object (shared kernel)
- [ ] `DomainException` base exception
- [ ] API response format padronizado (`data`, `meta`, `errors`)
- [ ] Exception handler customizado (error codes padronizados)
- [ ] Middleware base: `ForceJsonResponse`, `SetCorrelationId`
- [ ] Health check endpoint (`GET /api/health`)
- [ ] `config/social-media.php` — Configuracoes de providers

### 0.6 Testes de Arquitetura

- [ ] Domain nao depende de Application ou Infrastructure
- [ ] Application nao depende de Infrastructure
- [ ] Controllers estao na Infrastructure
- [ ] Entities sao `final` e `readonly`
- [ ] Jobs nao contem logica de negocio

### 0.7 CI/CD (GitHub Actions)

- [ ] Workflow: lint (Pint) + static analysis (PHPStan) + tests (Pest)
- [ ] Cache de dependencias Composer
- [ ] PostgreSQL e Redis como services no CI

### Entregaveis Sprint 0

- `docker compose up` funcional
- `setup.sh` roda sem erros
- Health check retorna 200
- Testes de arquitetura passam (verde)
- CI/CD rodando no GitHub

---

## Sprint 1 — Identity & Access + Organization Management

**Objetivo:** Registro, login, JWT RS256, 2FA, organizacoes e convites. Base de autenticacao e multi-tenancy.

**Bounded Contexts:** Identity, Organization

### 1.1 Domain Layer

- [ ] `User` entity (id, name, email, password, status, 2FA)
- [ ] `Organization` entity (id, name, slug, status)
- [ ] `OrganizationMember` entity (user_id, org_id, role)
- [ ] Value Objects: `Email`, `HashedPassword`, `TwoFactorSecret`, `OrganizationRole`
- [ ] Domain Events: `UserRegistered`, `UserVerified`, `PasswordChanged`, `OrganizationCreated`, `MemberInvited`, `MemberRemoved`, `MemberRoleChanged`
- [ ] Repository interfaces: `UserRepositoryInterface`, `OrganizationRepositoryInterface`, `OrganizationMemberRepositoryInterface`
- [ ] Domain Services: `PasswordPolicyService`

### 1.2 Application Layer

- [ ] Use Cases Identity:
  - `RegisterUserUseCase`
  - `VerifyEmailUseCase`
  - `LoginUseCase`
  - `RefreshTokenUseCase`
  - `LogoutUseCase`
  - `ForgotPasswordUseCase`
  - `ResetPasswordUseCase`
  - `Enable2FAUseCase`, `Confirm2FAUseCase`, `Disable2FAUseCase`
  - `UpdateProfileUseCase`, `ChangeEmailUseCase`, `ChangePasswordUseCase`
- [ ] Use Cases Organization:
  - `CreateOrganizationUseCase`
  - `UpdateOrganizationUseCase`
  - `InviteMemberUseCase`
  - `AcceptInviteUseCase`
  - `RemoveMemberUseCase`
  - `ChangeMemberRoleUseCase`
  - `SwitchOrganizationUseCase`
  - `ListOrganizationsUseCase`
- [ ] DTOs para input/output de cada use case

### 1.3 Infrastructure Layer

- [ ] Migrations: `users`, `organizations`, `organization_members`, `organization_invites`, `refresh_tokens`, `password_resets`, `audit_logs`
- [ ] Eloquent Models + Repositories
- [ ] JWT Service (RS256 keypair, access/refresh tokens, blacklist)
- [ ] Middleware: `Authenticate`, `Resolve OrganizationContext`, `CheckRole`
- [ ] Controllers: `AuthController`, `ProfileController`, `OrganizationController`, `MemberController`
- [ ] Form Requests para validacao
- [ ] API Resources para response
- [ ] Email notifications: verificacao, reset, convite

### 1.4 Testes

- [ ] Unit: User entity, Email VO, PasswordPolicy, OrganizationRole
- [ ] Unit: Todos os Use Cases (com mocks de repository)
- [ ] Integration: Eloquent repositories
- [ ] Feature: Todos os endpoints de auth (register, login, refresh, logout, 2FA)
- [ ] Feature: CRUD de organizacoes e membros
- [ ] Feature: Isolamento cross-organization (acesso negado = 404)

### Entregaveis Sprint 1

- Registro + login + JWT RS256 funcionando
- 2FA (TOTP) habilitavel
- CRUD de organizacoes com convites e roles
- Switch de organizacao ativa
- Middleware de multi-tenancy ativo
- Audit log de acoes de auth

---

## Sprint 2 — Social Account Management + Media Management

**Objetivo:** OAuth com Instagram/TikTok/YouTube, upload de midia com validacao e scan.

**Bounded Contexts:** SocialAccount, Media

### 2.1 Domain Layer

- [ ] `SocialAccount` entity
- [ ] `Media` entity
- [ ] Value Objects: `SocialProvider`, `EncryptedToken`, `OAuthCredentials`, `MediaType`, `MimeType`, `FileSize`, `Dimensions`, `Compatibility`
- [ ] Domain Events: `SocialAccountConnected`, `SocialAccountDisconnected`, `TokenRefreshed`, `TokenExpired`, `MediaUploaded`, `MediaScanned`, `MediaDeleted`
- [ ] Repository interfaces
- [ ] Contracts: `SocialAuthenticatorInterface`, `SocialPublisherInterface`, `SocialAnalyticsInterface`, `SocialEngagementInterface`

### 2.2 Application Layer

- [ ] Use Cases SocialAccount:
  - `InitiateOAuthUseCase`
  - `HandleOAuthCallbackUseCase`
  - `ListSocialAccountsUseCase`
  - `DisconnectSocialAccountUseCase`
  - `RefreshTokenUseCase`
  - `CheckAccountHealthUseCase`
- [ ] Use Cases Media:
  - `UploadMediaUseCase`
  - `ListMediaUseCase`
  - `DeleteMediaUseCase`
  - `ScanMediaUseCase`
  - `CalculateCompatibilityUseCase`

### 2.3 Infrastructure Layer

- [ ] Migrations: `social_accounts`, `media`
- [ ] `SocialTokenEncrypter` (AES-256-GCM com chave dedicada `SOCIAL_TOKEN_KEY`)
- [ ] Adapters (implementacao inicial — pode usar stubs):
  - `InstagramAuthenticator`, `TikTokAuthenticator`, `YouTubeAuthenticator`
- [ ] `SocialAccountAdapterFactory` (resolve adapter por provider)
- [ ] Media storage service (S3-compatible / local em dev)
- [ ] Jobs: `RefreshExpiringTokensJob`, `ScanMediaJob`, `GenerateThumbnailJob`
- [ ] Controllers: `SocialAccountController`, `MediaController`
- [ ] Scheduler: token refresh (12h), health check (6h)

### 2.4 Testes

- [ ] Unit: EncryptedToken VO, SocialProvider enum, Media entity, FileSize/MimeType validation
- [ ] Integration: SocialTokenEncrypter (encrypt/decrypt roundtrip)
- [ ] Integration: Media storage (upload, delete)
- [ ] Feature: OAuth flow (mock de providers)
- [ ] Feature: Upload/list/delete de midia
- [ ] Feature: Isolamento por organization_id

### Entregaveis Sprint 2

- OAuth flow com 3 providers (Instagram, TikTok, YouTube)
- Tokens criptografados com AES-256-GCM
- Upload de midia com validacao e thumbnail
- Scan de seguranca assincrono
- Calculo de compatibilidade por rede
- Refresh automatico de tokens

---

## Sprint 3 — Campaign Management + Content AI

**Objetivo:** CRUD de campanhas e conteudos, overrides por rede, geracao de conteudo com IA.

**Bounded Contexts:** Campaign, ContentAI

### 3.1 Domain Layer

- [ ] `Campaign` entity, `Content` entity, `ContentNetworkOverride` entity
- [ ] `AIGeneration` entity, `AISettings` entity
- [ ] Value Objects: `CampaignStatus`, `ContentStatus`, `Hashtag`, `NetworkOverride`, `Tone`, `Language`, `AIUsage`
- [ ] Domain Events: `CampaignCreated`, `ContentCreated`, `ContentUpdated`, `ContentDeleted`, `ContentGenerated`, `AISettingsUpdated`
- [ ] Repository interfaces

### 3.2 Application Layer

- [ ] Use Cases Campaign:
  - `CreateCampaignUseCase`, `UpdateCampaignUseCase`, `DeleteCampaignUseCase`
  - `ListCampaignsUseCase`, `GetCampaignUseCase`, `DuplicateCampaignUseCase`
  - `CreateContentUseCase`, `UpdateContentUseCase`, `DeleteContentUseCase`
  - `ListContentsUseCase`, `GetContentUseCase`
  - `GetCampaignStatsUseCase`
- [ ] Use Cases ContentAI:
  - `GenerateTitleUseCase`, `GenerateDescriptionUseCase`
  - `GenerateHashtagsUseCase`, `GenerateFullContentUseCase`
  - `UpdateAISettingsUseCase`, `GetAISettingsUseCase`
  - `ListAIHistoryUseCase`

### 3.3 Infrastructure Layer

- [ ] Migrations: `campaigns`, `contents`, `content_network_overrides`, `ai_settings`, `ai_generations`
- [ ] Integracao com Prism (Laravel AI SDK) via `PrismAIService`
- [ ] Prompts por tipo de geracao (title, description, hashtags, full)
- [ ] Cost tracking por geracao (tokens input/output, modelo, custo estimado)
- [ ] Controllers: `CampaignController`, `ContentController`, `AIController`
- [ ] Jobs: `GenerateEmbeddingJob`

### 3.4 Testes

- [ ] Unit: Campaign/Content entities, status transitions, Hashtag VO, CampaignName validation
- [ ] Unit: Use Cases com mocks
- [ ] Integration: AI service (mock de Prism)
- [ ] Feature: CRUD campanhas e conteudos
- [ ] Feature: Geracao de conteudo IA (mock)
- [ ] Feature: Network overrides

### Entregaveis Sprint 3

- CRUD completo de campanhas e conteudos
- Overrides de conteudo por rede social
- Geracao de titulo, descricao, hashtags e conteudo completo via IA
- Configuracao de tom de voz por organizacao
- Historico de geracoes com cost tracking
- Duplicacao de campanhas

---

## Sprint 4 — Publishing

**Objetivo:** Agendamento, publicacao assincrona, retry com backoff, circuit breaker, calendario.

**Bounded Context:** Publishing

### 4.1 Domain Layer

- [ ] `ScheduledPost` entity
- [ ] Value Objects: `PublishingStatus`, `ScheduleTime`, `PublishError`
- [ ] Domain Events: `PostScheduled`, `PostDispatched`, `PostPublished`, `PostFailed`, `PostCancelled`
- [ ] Repository interface

### 4.2 Application Layer

- [ ] Use Cases:
  - `SchedulePostUseCase`, `PublishNowUseCase`
  - `CancelScheduleUseCase`, `RescheduleUseCase`
  - `ListScheduledPostsUseCase`, `GetCalendarUseCase`
  - `ProcessScheduledPostUseCase`, `RetryPublishUseCase`

### 4.3 Infrastructure Layer

- [ ] Migration: `scheduled_posts`
- [ ] Adapters de publicacao (implementacao real):
  - `InstagramPublisher`, `TikTokPublisher`, `YouTubePublisher`
- [ ] Jobs: `ProcessScheduledPostJob`, `RetryPublishJob`
- [ ] Circuit breaker por provider (Redis-based)
- [ ] Scheduler: verificar posts pendentes (a cada minuto)
- [ ] Idempotencia via `idempotency_key` no scheduled_post
- [ ] Controllers: `PublishingController`, `ScheduledPostController`

### 4.4 Testes

- [ ] Unit: ScheduledPost entity, status transitions, ScheduleTime validation
- [ ] Unit: Use Cases (schedule, publish, retry logic)
- [ ] Integration: Publisher adapters (mock de APIs externas)
- [ ] Feature: Agendar, cancelar, reagendar
- [ ] Feature: Publicacao imediata
- [ ] Feature: Retry com backoff
- [ ] Feature: Calendario de publicacoes

### Entregaveis Sprint 4

- Agendamento e publicacao em Instagram, TikTok, YouTube
- Publicacao imediata (fila prioritaria)
- Retry com exponential backoff (60s, 300s, 900s)
- Circuit breaker por provider
- Calendario de publicacoes
- Idempotencia garantida

---

## Sprint 5 — Analytics + Engagement & Automation

**Objetivo:** Metricas sincronizadas, relatorios, captura de comentarios, automacao de respostas, webhooks.

**Bounded Contexts:** Analytics, Engagement

### 5.1 Analytics

- [ ] Domain: `ContentMetric`, `ContentMetricSnapshot`, `AccountMetric`, `ReportExport`
- [ ] Value Objects: `MetricPeriod`, `ExportFormat`
- [ ] Use Cases: `GetOverviewUseCase`, `GetNetworkAnalyticsUseCase`, `GetContentAnalyticsUseCase`, `ExportReportUseCase`, `SyncMetricsUseCase`
- [ ] Migrations: `content_metrics`, `content_metric_snapshots` (particionada por mes), `account_metrics` (particionada), `report_exports`
- [ ] Adapters: `InstagramAnalytics`, `TikTokAnalytics`, `YouTubeAnalytics`
- [ ] Jobs: `SyncPostMetricsJob`, `SyncAccountMetricsJob`, `GenerateReportJob`
- [ ] Controllers: `AnalyticsController`
- [ ] Scheduler: sync metricas (1h para recentes, 6h para antigos)

### 5.2 Engagement

- [ ] Domain: `Comment`, `AutomationRule`, `AutomationExecution`, `BlacklistWord`, `WebhookEndpoint`, `WebhookDelivery`
- [ ] Value Objects: `Sentiment`, `ActionType`, `ConditionOperator`, `WebhookSecret`
- [ ] Use Cases: `ListCommentsUseCase`, `ReplyCommentUseCase`, `SuggestReplyUseCase`, CRUD de `AutomationRule`, CRUD de `WebhookEndpoint`
- [ ] `AutomationEngine` domain service (avalia regras, executa acoes)
- [ ] Migrations: `comments`, `automation_rules`, `automation_executions`, `blacklist_words`, `webhook_endpoints`, `webhook_deliveries`
- [ ] Adapters: `InstagramEngagement`, `TikTokEngagement`, `YouTubeEngagement`
- [ ] Jobs: `CaptureCommentsJob`, `DeliverWebhookJob`
- [ ] Controllers: `CommentController`, `AutomationRuleController`, `WebhookController`
- [ ] Scheduler: captura comentarios (30min)

### 5.3 Testes

- [ ] Unit: MetricPeriod, Sentiment, AutomationEngine (regras, prioridade, stop-on-match)
- [ ] Integration: Analytics adapters, webhook delivery (HMAC-SHA256)
- [ ] Feature: Dashboard analytics, exportacao
- [ ] Feature: CRUD comentarios, reply, sugestao IA
- [ ] Feature: CRUD automacao, motor de execucao
- [ ] Feature: Webhooks (criacao, delivery, retry)

### Entregaveis Sprint 5

- Dashboard de analytics com metricas agregadas
- Analytics por rede e por conteudo
- Exportacao assincrona (PDF, CSV)
- Captura automatica de comentarios
- Classificacao de sentimento via IA
- Motor de automacao com regras, prioridades e limites
- Sugestao de resposta via IA
- Webhooks com HMAC-SHA256 para integracao com CRMs

---

## Sprint 6 — Billing & Subscription

**Objetivo:** Planos, assinaturas, enforcement de limites, integracao Stripe.

**Bounded Context:** Billing

### 6.1 Domain Layer

- [ ] `Plan`, `Subscription`, `UsageRecord`, `Invoice` entities
- [ ] Value Objects: `BillingCycle`, `SubscriptionStatus`, `PlanLimits`, `Money`
- [ ] Domain Events: `SubscriptionCreated`, `SubscriptionUpgraded`, `SubscriptionCanceled`, `SubscriptionExpired`, `PaymentFailed`, `PaymentSucceeded`, `PlanLimitReached`
- [ ] `PaymentGatewayInterface` contract

### 6.2 Application Layer

- [ ] Use Cases:
  - `GetSubscriptionUseCase`, `GetUsageUseCase`, `ListInvoicesUseCase`
  - `CreateCheckoutSessionUseCase`, `CreatePortalSessionUseCase`
  - `ProcessStripeWebhookUseCase`
  - `CheckPlanLimitUseCase`, `RecordUsageUseCase`
  - `DowngradeToFreePlanUseCase`
  - `ListPlansUseCase`

### 6.3 Infrastructure Layer

- [ ] Migrations: `plans`, `subscriptions`, `usage_records`, `invoices`
- [ ] Seeds: planos default (Free, Pro, Enterprise)
- [ ] `StripePaymentGateway` (implementa `PaymentGatewayInterface`)
- [ ] Middleware: `CheckPlanLimit` (verifica limites antes de acoes)
- [ ] Jobs: `ProcessStripeWebhookJob`, `CheckExpiredSubscriptionsJob`, `DowngradeToFreePlanJob`, `SyncUsageRecordsJob`
- [ ] Controllers: `BillingController`, `PlanController`
- [ ] Webhook endpoint: `POST /api/v1/webhooks/stripe` (signature validation)
- [ ] Scheduler: verificar subscriptions expiradas (diario)

### 6.4 Testes

- [ ] Unit: Subscription status transitions, PlanLimits, Money VO
- [ ] Unit: Use Cases (checkout, webhook processing, limit check)
- [ ] Integration: Stripe API (mock via Stripe test mode)
- [ ] Feature: Listar planos, ver subscription, ver uso
- [ ] Feature: Checkout flow (upgrade)
- [ ] Feature: Webhook processing (subscription events, payment events)
- [ ] Feature: Enforcement de limites (402 quando atingido)
- [ ] Feature: Downgrade automatico apos expiracao

### Entregaveis Sprint 6

- 3 planos (Free, Pro, Enterprise) com limites definidos
- Subscription por organizacao com ciclo de vida completo
- Checkout via Stripe Checkout Session
- Customer Portal via Stripe
- Enforcement de limites em todos os recursos
- Webhooks do Stripe processados (pagamento, cancelamento, falha)
- Faturas e historico de pagamentos
- Downgrade automatico para Free ao expirar

---

## Sprint 7 — Platform Administration

**Objetivo:** Painel admin para gerenciar plataforma, orgs, users, planos e configuracoes.

**Bounded Context:** PlatformAdmin

### 7.1 Domain Layer

- [ ] `PlatformAdmin`, `SystemConfig`, `AdminAuditEntry` entities
- [ ] Value Objects: `PlatformRole`
- [ ] Domain Events: `OrganizationSuspended`, `OrganizationUnsuspended`, `UserBanned`, `UserUnbanned`, `PlanCreated`, `PlanUpdated`, `SystemConfigUpdated`, `MaintenanceModeEnabled`

### 7.2 Application Layer

- [ ] Use Cases:
  - `GetDashboardUseCase` (metricas globais: MRR, ARR, churn, uso)
  - `ListOrganizationsAdminUseCase`, `SuspendOrganizationUseCase`, `UnsuspendOrganizationUseCase`, `DeleteOrganizationUseCase`
  - `ListUsersAdminUseCase`, `BanUserUseCase`, `UnbanUserUseCase`, `ForceVerifyUseCase`
  - `CreatePlanUseCase`, `UpdatePlanUseCase`, `DeactivatePlanUseCase`
  - `GetSystemConfigUseCase`, `UpdateSystemConfigUseCase`

### 7.3 Infrastructure Layer

- [ ] Migrations: `platform_admins`, `system_configs`, `admin_audit_entries`
- [ ] Seeds: super_admin default, system configs default
- [ ] Middleware: `PlatformAdminMiddleware` (valida `platform_role` no JWT)
- [ ] Jobs: `PauseOrgScheduledPostsJob`, `InvalidateUserSessionsJob`, `CleanupSuspendedOrgsJob`
- [ ] Controllers: `AdminDashboardController`, `AdminOrganizationController`, `AdminUserController`, `AdminPlanController`, `AdminConfigController`
- [ ] Scheduler: cleanup de orgs suspensas > 30 dias

### 7.4 Testes

- [ ] Unit: PlatformRole, SystemConfig, Dashboard metrics calculation
- [ ] Feature: Dashboard admin (metricas globais)
- [ ] Feature: Suspender/reativar organizacao
- [ ] Feature: Banir/desbanir user (invalida sessoes)
- [ ] Feature: CRUD de planos
- [ ] Feature: Alterar system config (maintenance mode, registration)
- [ ] Feature: Audit trail de acoes admin
- [ ] Feature: User regular acessando /admin/* retorna 403

### Entregaveis Sprint 7

- Dashboard com MRR, ARR, churn, uso global
- Gerenciamento de organizacoes (suspensao, exclusao)
- Gerenciamento de users (banimento, suporte)
- Gerenciamento de planos (CRUD + desativacao)
- Configuracoes do sistema (feature flags, manutencao)
- Audit trail completo de acoes administrativas
- Middleware dedicado com roles (super_admin, admin, support)

---

## Matriz de Dependencias

| Sprint | Depende de | Bounded Contexts |
|--------|-----------|-----------------|
| 0 | — | Infraestrutura |
| 1 | 0 | Identity, Organization |
| 2 | 1 | SocialAccount, Media |
| 3 | 1, 2 | Campaign, ContentAI |
| 4 | 2, 3 | Publishing |
| 5 | 4 | Analytics, Engagement |
| 6 | 1 | Billing |
| 7 | 1, 6 | PlatformAdmin |

> **Nota:** Sprint 6 (Billing) depende apenas do Sprint 1, podendo ser iniciado em paralelo com Sprints 3-5 se houver capacidade.

---

## Criterios de Conclusao por Sprint

Cada sprint so e considerado concluido quando:

1. Todos os testes passam (unit, integration, feature, architecture)
2. Cobertura minima: Domain 95%+, Application 85%+, total 80%+
3. PHPStan nivel 8 sem erros
4. Pint sem violacoes de estilo
5. CI verde no GitHub Actions
6. Endpoints documentados e testados manualmente
7. Audit log funcional para acoes sensiveis
8. Isolamento por `organization_id` validado em testes

---

## Estimativa de Escopo

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 0 | 0 | 1 (health) | 0 | 0 | ~10 (arch) |
| 1 | 7 | ~20 | ~20 | 0 | ~80 |
| 2 | 2 | ~10 | ~11 | 3 | ~50 |
| 3 | 5 | ~15 | ~17 | 1 | ~60 |
| 4 | 1 | ~8 | ~8 | 2 | ~40 |
| 5 | 8 | ~18 | ~15 | 4 | ~70 |
| 6 | 4 | ~7 | ~11 | 4 | ~50 |
| 7 | 3 | ~12 | ~13 | 3 | ~45 |
| **Total** | **30** | **~91** | **~95** | **17** | **~405** |

---

## Apos o Roadmap (Futuro)

Itens para considerar apos a v1.0:

- **Notificacoes in-app** (WebSocket ou Pusher)
- **Threads/Twitter** como nova rede social
- **Pinterest** como nova rede social
- **Template library** de conteudo
- **A/B testing** de publicacoes
- **Team collaboration** (comentarios internos, aprovacoes)
- **White-label** (branding customizavel por organizacao)
- **API publica** (para integracao de terceiros)
- **Mobile app** (React Native ou Flutter)
