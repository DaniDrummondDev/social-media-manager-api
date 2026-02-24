# Roadmap de Implementacao — Social Media Manager API

> **Versao:** 1.1.0\
> **Data:** 2026-02-23\
> **Status:** Draft

---

## Visao Geral

O roadmap esta dividido em **17 sprints** organizados por dependencia entre bounded contexts. Os Sprints 0-7 cobrem a **Fase 1 (v1.0)**, os Sprints 8-11 cobrem a **Fase 2 (v2.0)**, os Sprints 12-14 cobrem a **Fase 3 (v3.0)** e os Sprints 15-16 cobrem a **Fase 4 (v4.0)**. Cada sprint entrega valor incremental e pode ser testado isoladamente.

```
                           Fase 1 (v1.0)
Sprint 0 ─→ Sprint 1 ─→ Sprint 2 ─→ Sprint 3 ─→ Sprint 4
(Infra)     (Auth)      (Social)    (Content)    (Publish)
                                        ↓
            Sprint 7 ←─ Sprint 6 ←─ Sprint 5
            (Admin)     (Billing)   (Analytics
                                    + Engage)

                           Fase 2 (v2.0)
Sprint 8 ─────────→ Sprint 9            Sprint 10 ──→ Sprint 11
(Client Finance)    (Social Listening)   (Best Time     (Cross-Network
                                         + Safety)      + Calendar)

                           Fase 3 (v3.0)
            Sprint 12 ─────────→ Sprint 13 ─────────→ Sprint 14
            (Content DNA         (Feedback Loop        (AI Learning
             + Prediction)        + Gap Analysis)       Loop — ADR-017)

                           Fase 4 (v4.0)
            Sprint 15 ─────────────────→ Sprint 16
            (CRM Connectors              (CRM Fase 2 +
             Fase 1 — ADR-018)            CRM Intelligence N6)
```

---

## Sprint 0 — Scaffolding & Infraestrutura

**Objetivo:** Ambiente de desenvolvimento funcional com Docker, Laravel configurado, DDD folder structure, testes de arquitetura rodando e CI pronto.

### 0.1 Docker & Container

- [x] `Dockerfile` multi-stage (PHP 8.4-FPM + dynamic user via UID/GID)
- [x] `docker-compose.yml` com 9 servicos:
  - `app` — PHP 8.4-FPM (Laravel)
  - `nginx` — Reverse proxy (:8080)
  - `postgres` — PostgreSQL 17 com extensao pgvector (pgvector/pgvector:pg17)
  - `pgbouncer` — Connection pooling (:6432, transaction mode, pool_size=20)
  - `redis` — Cache (DB0), filas (DB1), rate limiting (DB2), sessions (DB3)
  - `horizon` — Laravel Horizon (7 filas, 15 workers)
  - `scheduler` — `php artisan schedule:work`
  - `minio` — S3-compatible storage (:9000 API, :9001 console)
  - `mailpit` — SMTP local para teste de emails (:8025 UI, :1025 SMTP)
- [x] `.env.example` com ~60 variaveis documentadas por secao
- [x] Volumes nomeados para persistencia (postgres_data, redis_data, minio_data)
- [x] Network interna `social-media-net` (bridge)
- [x] `docker/nginx/default.conf` com security headers e gzip
- [x] `docker/php/php.ini` e `docker/php/php-fpm.conf`
- [x] `docker/postgres/init.sql` (pgvector, uuid-ossp, pg_trgm)
- [x] `docker/pgbouncer/pgbouncer.ini` e `userlist.txt`
- [x] `.dockerignore`

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

- [x] `composer create-project laravel/laravel` com PHP 8.4
- [x] Configurar `composer.json` com autoload PSR-4 para namespaces DDD:
  - `App\\Domain\\` → `app/Domain/`
  - `App\\Application\\` → `app/Application/`
  - `App\\Infrastructure\\` → `app/Infrastructure/`
- [x] Remover scaffolding default desnecessario (controllers, models, views, sail)
- [x] Configurar `config/database.php` para PostgreSQL (+ `pgsql_direct` para migrations)
- [x] Configurar `config/cache.php`, `config/queue.php` e `config/session.php` para Redis (databases 0-3)
- [x] Instalar dependencias core:
  - [x] `php-open-source-saver/jwt-auth` ^2.8 (JWT RS256)
  - [x] `echolabsdev/prism` ^0.99.19 (Laravel AI SDK)
  - [x] `pestphp/pest` + `pestphp/pest-plugin-arch` (testes)
  - [x] `laravel/horizon` (filas)
  - [x] `pgvector/pgvector` ^0.2.2 (embeddings)
  - [x] `phpstan/phpstan` (analise estatica)
  - [x] `laravel/pint` (code style — ja incluso no Laravel 12)

### 0.4 Folder Structure (DDD)

Criar estrutura de diretorios conforme `folder-structure.md`:

- [x] `app/Domain/` — 12 bounded contexts + Shared kernel
- [x] `app/Application/` — 12 contextos com UseCases/, DTOs/, Listeners/
- [x] `app/Infrastructure/` — 12 contextos com Models/, Repositories/, Controllers/, Providers/
- [x] `app/Infrastructure/External/` — Instagram/, TikTok/, YouTube/, OpenAI/
- [x] `routes/api/v1/` — Arquivos de rota por contexto
- [x] `tests/` — Architecture/, Unit/, Integration/, Feature/ com subpastas por contexto

### 0.5 Base Infrastructure

- [x] `DomainEvent` abstract class (base para todos os eventos)
- [x] `Uuid` value object (shared kernel)
- [x] `DateRange` value object (shared kernel)
- [x] `DomainException` base exception
- [x] API response format padronizado (`ApiResponse` — `data`, `meta`, `errors`)
- [x] Exception handler customizado (DomainException, Auth, Validation, NotFound)
- [x] Middleware base: `ForceJsonResponse`, `SetCorrelationId`
- [x] Health check endpoint (`GET /api/v1/health` — DB + Redis)
- [x] `config/social-media.php` — Configuracoes de providers (3 providers, limites, circuit breaker, retry, encryption)

### 0.6 Testes de Arquitetura

- [x] Domain nao depende de Application ou Infrastructure (3 testes)
- [x] Application nao depende de Infrastructure
- [x] Controllers estao na Infrastructure (no controllers in Domain/Application)
- [x] Value Objects sao `final` e `readonly`
- [x] Middleware sao `final`
- [x] Entities sao `final` e `readonly` (11 contextos)
- [x] Jobs nao contem logica de negocio (7 contextos — `not->toUse('App\Domain')`)

### 0.7 CI/CD (GitHub Actions)

- [x] Workflow: lint (Pint) + static analysis (PHPStan) + tests (Pest)
- [x] Cache de dependencias Composer
- [x] PostgreSQL (pgvector/pgvector:pg17) e Redis como services no CI

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

- [x] `User` entity (id, name, email, password, status, 2FA)
- [x] `Organization` entity (id, name, slug, status)
- [x] `OrganizationMember` entity (user_id, org_id, role)
- [x] Value Objects: `Email`, `HashedPassword`, `TwoFactorSecret`, `OrganizationRole`
- [x] Domain Events: `UserRegistered`, `UserVerified`, `PasswordChanged`, `OrganizationCreated`, `MemberInvited`, `MemberRemoved`, `MemberRoleChanged`
- [x] Repository interfaces: `UserRepositoryInterface`, `OrganizationRepositoryInterface`, `OrganizationMemberRepositoryInterface`
- [x] Domain Services: `PasswordPolicyService`

### 1.2 Application Layer

- [x] Use Cases Identity:
  - `RegisterUserUseCase`
  - `VerifyEmailUseCase`
  - `LoginUseCase`
  - `Verify2FALoginUseCase`
  - `RefreshTokenUseCase`
  - `LogoutUseCase`
  - `ForgotPasswordUseCase`
  - `ResetPasswordUseCase`
  - `Enable2FAUseCase`, `Confirm2FAUseCase`, `Disable2FAUseCase`
  - `UpdateProfileUseCase`, `ChangeEmailUseCase`, `ChangePasswordUseCase`
- [x] Use Cases Organization:
  - `CreateOrganizationUseCase`
  - `UpdateOrganizationUseCase`
  - `InviteMemberUseCase`
  - `AcceptInviteUseCase`
  - `RemoveMemberUseCase`
  - `ChangeMemberRoleUseCase`
  - `SwitchOrganizationUseCase`
  - `ListOrganizationsUseCase`
- [x] DTOs para input/output de cada use case
- [x] Application Contracts (EventDispatcher, HashService, AuthToken, RefreshTokenRepo, EmailVerification, PasswordReset, TwoFactor)
- [x] Application Exceptions (Authentication, EmailAlreadyInUse, InvalidToken, AccountNotVerified, Authorization)

### 1.3 Infrastructure Layer

- [x] Migrations: `users`, `organizations`, `organization_members`, `organization_invites`, `refresh_tokens`, `password_resets`, `audit_logs`
- [x] Eloquent Models + Repositories
- [x] JWT Service (RS256 keypair, access/refresh tokens, blacklist)
- [x] Middleware: `Authenticate`, `Resolve OrganizationContext`, `CheckRole`
- [x] Controllers: `AuthController`, `ProfileController`, `OrganizationController`, `MemberController`
- [x] Form Requests para validacao
- [x] API Resources para response
- [x] Email notifications: verificacao, reset, convite

### 1.4 Testes

- [x] Unit: User entity, Email VO, PasswordPolicy, OrganizationRole
- [x] Unit: Todos os Use Cases (com mocks de repository)
- [x] Architecture: 21 regras de arquitetura (camadas, DTOs, Use Cases, Contracts)
- [x] Integration: Eloquent repositories
- [x] Feature: Todos os endpoints de auth (register, login, refresh, logout, 2FA)
- [x] Feature: CRUD de organizacoes e membros
- [x] Feature: Isolamento cross-organization (acesso negado = 403)

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

- [x] `SocialAccount` entity
- [x] `Media` entity + `MediaUpload` entity (chunked upload sessions)
- [x] Value Objects: `SocialProvider`, `EncryptedToken`, `OAuthCredentials`, `ConnectionStatus`, `MediaType`, `MimeType`, `FileSize`, `Dimensions`, `ScanStatus`, `UploadStatus`, `Compatibility`
- [x] Domain Events: `SocialAccountConnected`, `SocialAccountDisconnected`, `TokenRefreshed`, `TokenExpired`, `MediaUploaded`, `MediaScanned`, `MediaDeleted`, `MediaRestored`
- [x] Repository interfaces
- [x] Contracts: `SocialAuthenticatorInterface`, `SocialPublisherInterface`, `SocialAnalyticsInterface`, `SocialEngagementInterface`

### 2.2 Application Layer

- [x] Use Cases SocialAccount:
  - `InitiateOAuthUseCase`
  - `HandleOAuthCallbackUseCase`
  - `ListSocialAccountsUseCase`
  - `DisconnectSocialAccountUseCase`
  - `RefreshSocialTokenUseCase`
  - `CheckAccountHealthUseCase`
- [x] Use Cases Media:
  - `InitiateUploadUseCase` (cria sessao de upload, retorna upload_id)
  - `UploadChunkUseCase` (recebe chunk individual com offset)
  - `CompleteUploadUseCase` (finaliza upload, inicia pos-processamento)
  - `AbortUploadUseCase` (cancela upload em andamento)
  - `GetUploadStatusUseCase` (status de upload em andamento)
  - `UploadSmallMediaUseCase` (upload simples para arquivos <= 10MB)
  - `ListMediaUseCase`
  - `DeleteMediaUseCase`
  - `ScanMediaUseCase`
  - `CalculateCompatibilityUseCase`

### 2.3 Infrastructure Layer

- [x] Migrations: `social_accounts`, `media`, `media_uploads` (sessoes de upload em andamento)
- [x] `SocialTokenEncrypter` (AES-256-GCM com chave dedicada `SOCIAL_TOKEN_KEY`)
- [x] Adapters (implementacao inicial — pode usar stubs):
  - `InstagramAuthenticator`, `TikTokAuthenticator`, `YouTubeAuthenticator`
- [x] `SocialAccountAdapterFactory` (resolve adapter por provider)
- [x] Media storage service (S3-compatible / local em dev)
- [x] Chunked upload service (S3 Multipart Upload / tus protocol) — ver secao 2.5
- [x] Jobs: `RefreshExpiringTokensJob`, `ScanMediaJob`, `GenerateThumbnailJob`, `CleanupAbandonedUploadsJob`
- [x] Controllers: `SocialAccountController`, `MediaController`, `MediaUploadController`
- [x] Scheduler: token refresh (12h), health check (6h), cleanup uploads abandonados (1h)

### 2.4 Testes

- [x] Unit: EncryptedToken VO, SocialProvider enum, Media entity, FileSize/MimeType validation
- [x] Unit: ChunkValidation (offset, size, sequence), UploadSession lifecycle
- [x] Integration: SocialTokenEncrypter (encrypt/decrypt roundtrip)
- [x] Integration: Media storage (upload simples, chunked upload, delete)
- [x] Integration: S3 Multipart Upload (initiate, upload parts, complete, abort)
- [x] Feature: OAuth flow (mock de providers)
- [x] Feature: Upload simples (imagens e videos pequenos)
- [x] Feature: Upload chunked (videos grandes, resume apos falha)
- [x] Feature: Upload/list/delete de midia
- [x] Feature: Isolamento por organization_id

### 2.5 Chunked Upload para Videos Grandes

Videos para YouTube (tutoriais, lives, conteudo longo) podem ultrapassar 1GB. Upload em um unico request HTTP e inviavel:

- **Timeout**: requests de minutos sao frageis — qualquer instabilidade de rede mata o upload
- **Memoria**: servidor precisaria manter o arquivo inteiro em memoria
- **UX**: sem progress bar, sem resume em caso de falha
- **Infraestrutura**: ALB/NLB tem limites de body size e timeout de conexao

#### Estrategia: Dual-Mode Upload

```
Arquivo <= 10MB  ─→  Upload simples (POST /api/v1/media)
                     Multipart/form-data em um unico request

Arquivo > 10MB   ─→  Upload chunked (3-step flow)
                     1. Initiate → recebe upload_id
                     2. Upload chunks → envia partes de 5-10MB
                     3. Complete → finaliza e pos-processa
```

#### Fluxo Chunked Upload

```
Cliente                         API                              S3
  │                              │                               │
  │ POST /media/uploads          │                               │
  │ { file_name, file_size,      │                               │
  │   mime_type, total_chunks }  │                               │
  │─────────────────────────────→│                               │
  │                              │ CreateMultipartUpload          │
  │                              │──────────────────────────────→│
  │                              │◀─── upload_id + s3_upload_id  │
  │◀── { upload_id, chunk_size,  │                               │
  │      upload_urls[] }         │                               │
  │                              │                               │
  │ PATCH /media/uploads/{id}    │                               │
  │ Content-Range: bytes 0-5MB   │                               │
  │ [chunk binary data]          │                               │
  │─────────────────────────────→│ UploadPart(part=1)            │
  │                              │──────────────────────────────→│
  │                              │◀──────── ETag                 │
  │◀── { chunk: 1, received: ok }│                               │
  │                              │                               │
  │ PATCH /media/uploads/{id}    │                               │
  │ Content-Range: bytes 5MB-10MB│                               │
  │ [chunk binary data]          │                               │
  │─────────────────────────────→│ UploadPart(part=2)            │
  │                              │──────────────────────────────→│
  │                              │◀──────── ETag                 │
  │◀── { chunk: 2, received: ok }│                               │
  │                              │                               │
  │ ... (repete para N chunks)   │                               │
  │                              │                               │
  │ POST /media/uploads/{id}/    │                               │
  │      complete                │                               │
  │─────────────────────────────→│ CompleteMultipartUpload        │
  │                              │──────────────────────────────→│
  │                              │◀──────── final URL            │
  │                              │                               │
  │                              │ Dispatch: ScanMediaJob         │
  │                              │ Dispatch: GenerateThumbnailJob │
  │                              │ Dispatch: CalcCompatibilityJob │
  │◀── { media_id, status:       │                               │
  │      processing }            │                               │
```

#### Endpoints de Upload

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `POST /api/v1/media` | POST | Upload simples (arquivos <= 10MB) |
| `POST /api/v1/media/uploads` | POST | Iniciar sessao de upload chunked |
| `PATCH /api/v1/media/uploads/{id}` | PATCH | Enviar chunk individual (Content-Range header) |
| `GET /api/v1/media/uploads/{id}` | GET | Status do upload (chunks recebidos, progresso) |
| `POST /api/v1/media/uploads/{id}/complete` | POST | Finalizar upload e iniciar pos-processamento |
| `DELETE /api/v1/media/uploads/{id}` | DELETE | Cancelar upload em andamento |

#### MediaUpload Entity

```
MediaUpload
├── id: UploadId (UUID)
├── organization_id: OrganizationId
├── user_id: UserId
├── file_name: string (nome original)
├── mime_type: MimeType (Value Object)
├── total_bytes: int
├── chunk_size_bytes: int (default: 5MB)
├── total_chunks: int
├── received_chunks: int[] (indices dos chunks recebidos)
├── s3_upload_id: string (S3 Multipart Upload ID)
├── s3_parts: array (part_number → ETag)
├── status: UploadStatus (Enum: initiated, uploading, completing, completed, aborted, expired)
├── checksum: ?string (SHA-256, calculado incrementalmente)
├── expires_at: DateTimeImmutable (sessao expira em 24h)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### Regras de Negocio

- **RN-MED-20**: Chunk size padrao: 5MB. Minimo: 1MB. Maximo: 10MB. Configuravel por request.
- **RN-MED-21**: Sessao de upload expira em 24h. `CleanupAbandonedUploadsJob` aborta uploads expirados no S3.
- **RN-MED-22**: Chunks podem ser enviados fora de ordem (S3 Multipart suporta).
- **RN-MED-23**: Se um chunk falhar, o cliente reenvia apenas aquele chunk (resume).
- **RN-MED-24**: Checksum SHA-256 calculado incrementalmente conforme chunks chegam. Validado no `complete`.
- **RN-MED-25**: Limite de tamanho total por upload: definido pelo plano (Free: 500MB, Creator: 2GB, Professional: 5GB, Agency: 10GB).
- **RN-MED-26**: Upload simples (POST /media) continua disponivel para arquivos <= 10MB (imagens, videos curtos).
- **RN-MED-27**: Progresso do upload disponivel via `GET /media/uploads/{id}` para feedback ao cliente.

#### Integracao com AWS S3

O chunked upload mapeia diretamente para o **S3 Multipart Upload API**:

| Nossa API | S3 Multipart API |
|-----------|-----------------|
| `POST /media/uploads` (initiate) | `CreateMultipartUpload` |
| `PATCH /media/uploads/{id}` (chunk) | `UploadPart` |
| `POST /media/uploads/{id}/complete` | `CompleteMultipartUpload` |
| `DELETE /media/uploads/{id}` (abort) | `AbortMultipartUpload` |

**Alternativa**: Presigned URLs — em vez de rotear chunks pelo nosso backend, gerar presigned URLs para o cliente fazer upload direto ao S3. Reduz carga no servidor mas exige mais logica no cliente. Pode ser implementado como otimizacao futura.

#### Fluxo de Publicacao para YouTube (Sprint 4)

O YouTube Data API v3 ja suporta **resumable uploads**. O fluxo completo:

```
1. Video ja esta no S3 (upload chunked pelo usuario concluido)
2. PublishToYouTubeJob inicia:
   a. Obtem URL do video no S3
   b. Inicia resumable upload session no YouTube API
   c. Faz streaming do S3 → YouTube em chunks de 5MB
   d. YouTube processa e retorna video_id
3. Nunca carrega video inteiro em memoria — stream entre S3 e YouTube
```

Isto e, o video **nunca precisa estar inteiro em memoria do servidor**. O fluxo e:
- Cliente → chunks → S3 (via nosso backend ou presigned)
- S3 → stream → YouTube API (no momento de publicar)

### Entregaveis Sprint 2

- OAuth flow com 3 providers (Instagram, TikTok, YouTube)
- Tokens criptografados com AES-256-GCM
- Upload simples para arquivos ate 10MB (imagens, videos curtos)
- Upload chunked para videos grandes (ate 10GB por plano Agency)
- Resume de upload em caso de falha (reenvia apenas chunks faltantes)
- Progress tracking de uploads em andamento
- Upload de midia com validacao e thumbnail
- Scan de seguranca assincrono
- Calculo de compatibilidade por rede
- Refresh automatico de tokens

---

## Sprint 3 — Campaign Management + Content AI

**Objetivo:** CRUD de campanhas e conteudos, overrides por rede, geracao de conteudo com IA.

**Bounded Contexts:** Campaign, ContentAI

### 3.1 Domain Layer

- [x] `Campaign` entity, `Content` entity, `ContentNetworkOverride` entity
- [x] `AIGeneration` entity, `AISettings` entity
- [x] Value Objects: `CampaignStatus`, `ContentStatus`, `Hashtag`, `NetworkOverride`, `Tone`, `Language`, `AIUsage`
- [x] Domain Events: `CampaignCreated`, `ContentCreated`, `ContentUpdated`, `ContentDeleted`, `ContentGenerated`, `AISettingsUpdated`
- [x] Repository interfaces

### 3.2 Application Layer

- [x] Use Cases Campaign:
  - `CreateCampaignUseCase`, `UpdateCampaignUseCase`, `DeleteCampaignUseCase`
  - `ListCampaignsUseCase`, `GetCampaignUseCase`, `DuplicateCampaignUseCase`
  - `CreateContentUseCase`, `UpdateContentUseCase`, `DeleteContentUseCase`
  - `ListContentsUseCase`, `GetContentUseCase`
  - `GetCampaignStatsUseCase`
- [x] Use Cases ContentAI:
  - `GenerateTitleUseCase`, `GenerateDescriptionUseCase`
  - `GenerateHashtagsUseCase`, `GenerateFullContentUseCase`
  - `UpdateAISettingsUseCase`, `GetAISettingsUseCase`
  - `ListAIHistoryUseCase`

### 3.3 Infrastructure Layer

- [x] Migrations: `campaigns`, `contents`, `content_network_overrides`, `ai_settings`, `ai_generations`
- [x] Integracao com Prism (Laravel AI SDK) via `PrismAIService`
- [x] Prompts por tipo de geracao (title, description, hashtags, full)
- [x] Cost tracking por geracao (tokens input/output, modelo, custo estimado)
- [x] Controllers: `CampaignController`, `ContentController`, `AIController`
- [x] Jobs: `GenerateEmbeddingJob`

### 3.4 Testes

- [x] Unit: Campaign/Content entities, status transitions, Hashtag VO, CampaignName validation
- [x] Unit: Use Cases com mocks
- [x] Integration: AI service (mock de Prism)
- [x] Feature: CRUD campanhas e conteudos
- [x] Feature: Geracao de conteudo IA (mock)
- [x] Feature: Network overrides

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
- [ ] Seeds: planos default (Free, Creator, Professional, Agency)
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

- 4 planos (Free, Creator, Professional, Agency) com limites definidos
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

---

## Fase 2 — Expansao de Valor (v2.0)

Os sprints 8 e 9 expandem o produto para alem do core, adicionando capacidades que diferenciam o SaaS no mercado e atendem necessidades avancadas de agencias e marcas.

---

## Sprint 8 — Client Financial Management

**Objetivo:** Permitir que agencias e gestores de social media faturem seus proprios clientes, alocando custos por campanha/servico e gerando faturas.

**Bounded Context:** ClientFinancialManagement

> **Importante:** Este contexto e distinto do Billing & Subscription (Sprint 6). O Billing trata da cobranca do SaaS a organizacao. O Client Financial Management trata da **gestao financeira que a agencia faz com seus clientes finais**.

### 8.1 Domain Layer

- [ ] `Client` entity (id, organization_id, name, email, company_name, tax_id, status)
- [ ] `ClientContract` entity (id, client_id, type, value, period, social_accounts vinculadas)
- [ ] `ClientInvoice` entity (id, client_id, contract_id, items, totals, status, due_date)
- [ ] `CostAllocation` entity (id, client_id, resource_type, resource_id, cost)
- [ ] Value Objects: `ClientId`, `TaxId` (CPF/CNPJ), `Address`, `Currency`, `YearMonth`, `ContractType`, `InvoiceStatus`, `ContractStatus`
- [ ] Domain Events: `ClientCreated`, `ClientArchived`, `ContractCreated`, `ContractCompleted`, `InvoiceGenerated`, `InvoiceSent`, `InvoiceMarkedPaid`, `InvoiceOverdue`, `CostAllocated`
- [ ] Repository interfaces: `ClientRepositoryInterface`, `ClientContractRepositoryInterface`, `ClientInvoiceRepositoryInterface`, `CostAllocationRepositoryInterface`
- [ ] Domain Service: `InvoiceCalculationService` (calcula totais com base nos items e tipo de contrato)

### 8.2 Application Layer

- [ ] Use Cases Client:
  - `CreateClientUseCase`
  - `UpdateClientUseCase`
  - `ListClientsUseCase`
  - `GetClientUseCase`
  - `ArchiveClientUseCase`
- [ ] Use Cases Contract:
  - `CreateContractUseCase`
  - `UpdateContractUseCase`
  - `ListContractsUseCase`
  - `PauseContractUseCase`
  - `CompleteContractUseCase`
- [ ] Use Cases Invoice:
  - `GenerateInvoiceUseCase` (manual, com items customizados)
  - `GenerateMonthlyInvoicesUseCase` (batch, baseado em contratos ativos)
  - `ListInvoicesUseCase`
  - `GetInvoiceUseCase`
  - `SendInvoiceUseCase` (envia por email)
  - `MarkInvoicePaidUseCase`
  - `CancelInvoiceUseCase`
- [ ] Use Cases Cost:
  - `AllocateCostUseCase`
  - `GetCostBreakdownUseCase` (custos por cliente, periodo)
  - `GetProfitabilityReportUseCase` (receita vs custos por cliente)
- [ ] Use Cases Report:
  - `GetFinancialDashboardUseCase` (receita total, inadimplencia, top clientes)
  - `ExportFinancialReportUseCase` (PDF, CSV)
- [ ] DTOs para input/output de cada use case

### 8.3 Infrastructure Layer

- [ ] Migrations: `clients`, `client_contracts`, `client_invoices`, `client_invoice_items`, `cost_allocations`
- [ ] Eloquent Models + Repositories
- [ ] `InvoicePdfGenerator` service (gera PDF da fatura)
- [ ] Email notifications: fatura enviada, fatura vencida, lembrete de pagamento
- [ ] Jobs: `GenerateMonthlyInvoicesJob`, `CheckOverdueInvoicesJob`, `ExportFinancialReportJob`, `SendInvoiceReminderJob`
- [ ] Controllers: `ClientController`, `ClientContractController`, `ClientInvoiceController`, `FinancialReportController`
- [ ] Scheduler: verificar faturas vencidas (diario), gerar faturas mensais (dia 1 de cada mes)

### 8.4 Testes

- [ ] Unit: Client entity, TaxId VO (CPF/CNPJ validation), Address VO, Currency, InvoiceCalculationService
- [ ] Unit: Todos os Use Cases (com mocks de repository)
- [ ] Unit: InvoiceStatus transitions, ContractStatus transitions
- [ ] Integration: Eloquent repositories
- [ ] Integration: InvoicePdfGenerator
- [ ] Feature: CRUD de clientes
- [ ] Feature: CRUD de contratos
- [ ] Feature: Geracao e envio de faturas
- [ ] Feature: Alocacao de custos e relatorios de lucratividade
- [ ] Feature: Dashboard financeiro
- [ ] Feature: Isolamento por organization_id

### Entregaveis Sprint 8

- CRUD completo de clientes com dados fiscais (CPF/CNPJ)
- Contratos por tipo (mensal fixo, por campanha, por post, por hora)
- Geracao manual e automatica de faturas
- Envio de faturas por email com PDF
- Alocacao de custos por campanha, geracao IA, midia e publicacao
- Relatorio de lucratividade por cliente
- Dashboard financeiro (receita, inadimplencia, top clientes)
- Exportacao de relatorios financeiros (PDF, CSV)

---

## Sprint 9 — Social Listening

**Objetivo:** Monitoramento de mencoes, keywords, hashtags e concorrentes nas redes sociais, com analise de sentimento, alertas e relatorios de tendencias.

**Bounded Context:** SocialListening

> **Nota:** Social Listening monitora mencoes **externas** — ou seja, publicacoes de terceiros que mencionam a marca, keywords ou concorrentes. E diferente do Engagement (Sprint 5), que trata de comentarios nos posts proprios da organizacao.

### 9.1 Domain Layer

- [ ] `ListeningQuery` entity (id, organization_id, name, type, value, platforms, is_active)
- [ ] `Mention` entity (id, query_id, platform, author, content, sentiment, reach, engagement)
- [ ] `ListeningAlert` entity (id, organization_id, query_ids, condition, notification_channels, cooldown)
- [ ] `ListeningReport` entity (id, organization_id, query_ids, period, metrics, sentiment_breakdown)
- [ ] Value Objects: `QueryId`, `MentionId`, `AlertId`, `QueryType` (keyword, hashtag, mention, competitor), `AlertCondition`, `ConditionType` (volume_spike, negative_sentiment_spike, keyword_detected, influencer_mention), `NotificationChannel`, `SentimentBreakdown`, `MentionSource`
- [ ] Domain Events: `ListeningQueryCreated`, `ListeningQueryPaused`, `ListeningQueryResumed`, `MentionDetected`, `MentionFlagged`, `ListeningAlertTriggered`, `ListeningReportGenerated`, `SentimentSpikeDetected`
- [ ] Repository interfaces: `ListeningQueryRepositoryInterface`, `MentionRepositoryInterface`, `ListeningAlertRepositoryInterface`, `ListeningReportRepositoryInterface`
- [ ] Domain Service: `AlertEvaluationService` (avalia condicoes de alerta contra mencoes recentes)

### 9.2 Application Layer

- [ ] Use Cases Query:
  - `CreateListeningQueryUseCase`
  - `UpdateListeningQueryUseCase`
  - `ListListeningQueriesUseCase`
  - `PauseListeningQueryUseCase`
  - `ResumeListeningQueryUseCase`
  - `DeleteListeningQueryUseCase`
- [ ] Use Cases Mention:
  - `ListMentionsUseCase` (com filtros: query, platform, sentiment, periodo)
  - `GetMentionDetailsUseCase`
  - `FlagMentionUseCase` (destaque manual)
  - `MarkMentionsReadUseCase`
  - `ProcessMentionsBatchUseCase` (chamado pelo job de captura)
- [ ] Use Cases Alert:
  - `CreateAlertUseCase`
  - `UpdateAlertUseCase`
  - `ListAlertsUseCase`
  - `DeleteAlertUseCase`
  - `EvaluateAlertsUseCase` (chamado pelo job de avaliacao)
- [ ] Use Cases Dashboard/Report:
  - `GetListeningDashboardUseCase` (total mencoes, sentimento, tendencias, top autores)
  - `GetSentimentTrendUseCase` (serie temporal de sentimento)
  - `GetPlatformBreakdownUseCase` (distribuicao por rede)
  - `GenerateListeningReportUseCase`
  - `ExportListeningReportUseCase` (PDF, CSV)
- [ ] DTOs para input/output de cada use case

### 9.3 Infrastructure Layer

- [ ] Migrations: `listening_queries`, `mentions` (particionada por mes), `listening_alerts`, `listening_alert_notifications`, `listening_reports`
- [ ] Adapters de listening (implementam `SocialListeningInterface`):
  - `InstagramListeningAdapter` (Instagram Graph API — hashtag search, mention endpoint)
  - `TikTokListeningAdapter` (TikTok Research API — keyword search)
  - `YouTubeListeningAdapter` (YouTube Data API — search endpoint)
- [ ] `SocialListeningAdapterFactory` (resolve adapter por provider)
- [ ] Reutilizacao do `SentimentAnalysisService` do Engagement context
- [ ] Jobs:
  - `FetchMentionsJob` (captura mencoes por query, com deduplicacao por external_id)
  - `AnalyzeMentionSentimentJob` (analise de sentimento via IA)
  - `EvaluateListeningAlertsJob` (verifica condicoes de alerta)
  - `GenerateListeningReportJob`
  - `CleanupOldMentionsJob` (retention policy)
- [ ] Controllers: `ListeningQueryController`, `MentionController`, `ListeningAlertController`, `ListeningDashboardController`, `ListeningReportController`
- [ ] Scheduler:
  - Captura de mencoes: a cada 15 min para queries ativas
  - Avaliacao de alertas: a cada 5 min
  - Relatorio diario: 1x/dia (06:00 UTC)
  - Cleanup: mencoes > retention period do plano

### 9.4 Testes

- [ ] Unit: ListeningQuery entity, QueryType enum, AlertCondition VO, AlertEvaluationService
- [ ] Unit: Mention entity, sentiment assignment, deduplication logic
- [ ] Unit: Todos os Use Cases (com mocks de repository e adapters)
- [ ] Integration: Listening adapters (mock de APIs de busca)
- [ ] Integration: Mention partitioning (inserir e consultar em particoes diferentes)
- [ ] Feature: CRUD de queries de listening
- [ ] Feature: Listagem de mencoes com filtros
- [ ] Feature: CRUD de alertas e avaliacao de condicoes
- [ ] Feature: Dashboard de listening (total, sentimento, tendencias)
- [ ] Feature: Geracao e exportacao de relatorios
- [ ] Feature: Isolamento por organization_id

### 9.5 Consideracoes Tecnicas

#### APIs de Busca/Mencoes por Rede

| Rede | API | Limitacoes |
|------|-----|------------|
| Instagram | Hashtag Search + Mention endpoint | Requer Instagram Business Account. Hashtag search limitado a 30 hashtags/7 dias. Mention endpoint requer @menção direta. |
| TikTok | Research API (keyword search) | Acesso restrito, requer aprovação. Dados disponiveis com 48h de atraso. Rate limits rigorosos. |
| YouTube | Data API v3 (search.list) | Quota diaria de 10.000 unidades. Search custa 100 unidades. Sem endpoint de mention direta. |

> **Nota:** As limitacoes de API devem ser mapeadas em detalhe durante a implementacao. Algumas redes podem exigir niveis de acesso especiais ou parcerias comerciais para social listening em escala.

#### Volume e Performance

- Mencoes podem gerar **alto volume de dados**. A tabela `mentions` deve ser particionada por mes.
- Deduplicacao por `external_id + platform` para evitar mencoes duplicadas entre execucoes.
- Cache de resultados do dashboard (TTL 5min) para evitar queries pesadas em cada request.
- Limites de queries ativas por plano (Free: 0, Creator: 0, Professional: 0, Agency: 10) — enforcement via `CheckPlanLimit` middleware.

### Entregaveis Sprint 9

- CRUD de queries de listening (keyword, hashtag, menção, concorrente)
- Captura automatica de mencoes nas 3 redes (Instagram, TikTok, YouTube)
- Analise de sentimento de mencoes (reutiliza IA do Engagement)
- Dashboard de listening (volume, sentimento, tendencias, top autores)
- Alertas configuraveis (spike de volume, sentimento negativo, influenciador)
- Notificacoes por email e webhook
- Relatorios de listening exportaveis (PDF, CSV)
- Monitoramento de concorrentes
- Limites por plano integrados ao billing

---

## Sprint 10 — Best Time to Post + Brand Safety & Compliance

**Objetivo:** Horarios otimos de publicacao personalizados e verificacao de seguranca de marca pre-publicacao.

**Bounded Context:** AI Intelligence

> **Nota:** Sprint 10 pode rodar em paralelo com Sprint 8 (Client Finance). Nao depende de pgvector — Best Time to Post e modelo estatistico puro.

### 10.1 Domain Layer

- [ ] `PostingTimeRecommendation` entity (heatmap, top/worst slots, confidence)
- [ ] `BrandSafetyCheck` entity (status, score, checks por categoria)
- [ ] `BrandSafetyRule` entity (regras customizaveis por org)
- [ ] Value Objects: `PredictionScore`, `TimeSlotScore`, `ConfidenceLevel`, `SafetyStatus`, `SafetyRuleType`, `RuleSeverity`
- [ ] Domain Events: `PostingTimesUpdated`, `BrandSafetyChecked`, `BrandSafetyBlocked`
- [ ] Repository interfaces

### 10.2 Application Layer

- [ ] Use Cases Best Time:
  - `GetBestTimesUseCase`
  - `GetBestTimesHeatmapUseCase`
  - `GetBestTimesByProviderUseCase`
  - `RecalculateBestTimesUseCase`
- [ ] Use Cases Brand Safety:
  - `RunSafetyCheckUseCase`
  - `GetSafetyChecksUseCase`
  - `CreateSafetyRuleUseCase`
  - `UpdateSafetyRuleUseCase`
  - `DeleteSafetyRuleUseCase`
  - `ListSafetyRulesUseCase`

### 10.3 Infrastructure Layer

- [ ] Migrations: `posting_time_recommendations`, `brand_safety_checks`, `brand_safety_rules`
- [ ] Jobs: `CalculateBestPostingTimesJob`, `RunBrandSafetyCheckJob`
- [ ] Integracao com `ProcessScheduledPostJob` (consultar safety check antes de publicar)
- [ ] Controllers: `BestTimesController`, `BrandSafetyController`, `BrandSafetyRuleController`
- [ ] Scheduler: recalculo semanal de best times

### 10.4 Testes

- [ ] Unit: PostingTimeRecommendation entity, ConfidenceLevel, TimeSlotScore
- [ ] Unit: BrandSafetyCheck entity, SafetyStatus transitions
- [ ] Unit: Todos os Use Cases
- [ ] Integration: Calculo de best times a partir de content_metric_snapshots
- [ ] Integration: Safety check via LLM (mock de Prism)
- [ ] Feature: Endpoints de best times (heatmap, top slots)
- [ ] Feature: Safety check flow (check → publish com warning/block)
- [ ] Feature: CRUD de safety rules
- [ ] Feature: Isolamento por organization_id

### Entregaveis Sprint 10

- Horarios otimos de publicacao por org/rede/dia com heatmap
- Nivel de confianca baseado em volume de dados
- Recalculo semanal automatico + manual
- Verificacao de Brand Safety pre-publicacao (LGPD, disclosures, policies, sensitivity)
- Regras customizaveis de safety por organizacao (blocked words, required disclosures)
- Integracao com pipeline de publicacao (block/warn)

---

## Sprint 11 — Cross-Network Content Adaptation + AI Content Calendar

**Objetivo:** Adaptar conteudo entre redes e gerar sugestoes de calendario editorial com IA.

**Bounded Contexts:** Content AI (expandido), AI Intelligence

> **Nota:** Sprint 11 pode rodar em paralelo com Sprint 9 (Social Listening).

### 11.1 Domain Layer

- [ ] `CalendarSuggestion` entity (sugestoes, based_on, status, accepted_items)
- [ ] Value Objects: `SuggestionStatus`, `CalendarItem`
- [ ] Domain Events: `CalendarSuggestionGenerated`, `CalendarItemsAccepted`
- [ ] Expansao de `GenerationType` enum com `cross_network_adaptation`
- [ ] Repository interfaces

### 11.2 Application Layer

- [ ] Use Cases Cross-Network:
  - `AdaptContentUseCase` (adapta conteudo entre redes via LLM)
- [ ] Use Cases Calendar:
  - `GenerateCalendarSuggestionsUseCase`
  - `ListCalendarSuggestionsUseCase`
  - `GetCalendarSuggestionUseCase`
  - `AcceptCalendarItemsUseCase`

### 11.3 Infrastructure Layer

- [ ] Migration: `calendar_suggestions`
- [ ] Alteracao de ENUM: `generation_type` += `cross_network_adaptation`
- [ ] Jobs: `GenerateCalendarSuggestionsJob`
- [ ] Prompts especializados para adaptacao cross-network (respeitar limites/convencoes por rede)
- [ ] Controllers: `ContentAdaptationController`, `CalendarSuggestionController`

### 11.4 Testes

- [ ] Unit: CalendarSuggestion entity, SuggestionStatus transitions
- [ ] Unit: Todos os Use Cases
- [ ] Integration: Adaptacao cross-network via LLM (mock)
- [ ] Integration: Geracao de calendario via LLM (mock)
- [ ] Feature: Endpoint de adapt-content (request → adaptacoes por rede)
- [ ] Feature: CRUD de calendar suggestions (generate, list, accept)
- [ ] Feature: Isolamento por organization_id

### Entregaveis Sprint 11

- Adaptacao automatica de conteudo entre redes (respeitando limites e convencoes)
- Aplicacao opcional em content_network_overrides
- Sugestoes de calendario editorial para 7-30 dias
- Baseado em performance historica, lacunas no cronograma e posts existentes
- Aceitacao individual de itens pelo usuario
- Sugestoes expiram apos 7 dias

---

---

## Fase 3 — Inteligencia Avancada (v3.0)

Os sprints 12, 13 e 14 implementam as features mais avancadas de IA, dependentes do pipeline de embeddings (pgvector), dados acumulados de Social Listening e o AI Learning & Feedback Loop (ADR-017).

---

## Sprint 12 — Content DNA Profiling + Performance Prediction

**Objetivo:** Pipeline de embeddings, perfil de conteudo da organizacao via pgvector e predicao de performance pre-publicacao.

**Bounded Context:** AI Intelligence

> **Nota:** Este sprint implementa a infraestrutura de embeddings compartilhada que sera usada tambem pelo Sprint 13.

### 12.1 Domain Layer

- [ ] `ContentProfile` entity (top_themes, engagement_patterns, centroid_embedding)
- [ ] `PerformancePrediction` entity (score, breakdown, recommendations)
- [ ] Value Objects: `EngagementPattern`, `ContentFingerprint`, `PredictionBreakdown`
- [ ] Domain Events: `EmbeddingGenerated`, `ContentProfileGenerated`, `PredictionCalculated`
- [ ] Contracts: `EmbeddingGeneratorInterface`, `SimilaritySearchInterface`
- [ ] Repository interfaces

### 12.2 Application Layer

- [ ] Use Cases Embedding Pipeline:
  - `GenerateEmbeddingUseCase`
  - `BackfillEmbeddingsUseCase`
- [ ] Use Cases Content DNA:
  - `GenerateContentProfileUseCase`
  - `GetContentProfileUseCase`
  - `GetContentThemesUseCase`
  - `GetContentRecommendationsUseCase`
- [ ] Use Cases Prediction:
  - `PredictPerformanceUseCase`
  - `GetPredictionsUseCase`

### 12.3 Infrastructure Layer

- [ ] Migrations: `embedding_jobs`, `content_profiles`, `performance_predictions`
- [ ] `OpenAIEmbeddingService` (implementa `EmbeddingGeneratorInterface`)
- [ ] `PgVectorSimilarityService` (implementa `SimilaritySearchInterface`)
- [ ] Jobs: `GenerateContentEmbeddingJob`, `GenerateCommentEmbeddingJob`, `BackfillEmbeddingsJob`, `GenerateContentProfileJob`, `CalculatePerformancePredictionJob`
- [ ] Listeners: `ContentCreated` → dispatch embedding job, `ContentUpdated` → dispatch embedding job
- [ ] Controllers: `ContentProfileController`, `PerformancePredictionController`
- [ ] Scheduler: backfill semanal, profile generation semanal

### 12.4 Testes

- [ ] Unit: ContentProfile entity, PredictionScore VO, EngagementPattern
- [ ] Unit: Todos os Use Cases
- [ ] Integration: OpenAI Embedding Service (mock de API)
- [ ] Integration: PgVector similarity search (roundtrip: insert → search)
- [ ] Integration: Centroid calculation
- [ ] Feature: Embedding pipeline (create content → embedding generated)
- [ ] Feature: Content DNA (generate profile, get themes, recommendations)
- [ ] Feature: Performance Prediction (predict → score + breakdown)
- [ ] Feature: Prediction com dados insuficientes (InsufficientDataException)
- [ ] Feature: Isolamento por organization_id em similarity search

### Entregaveis Sprint 12

- Pipeline de embeddings para conteudos e comentarios (event-driven + backfill)
- Perfil DNA de conteudo com temas dominantes, padroes de engajamento e traits de alta performance
- Centroid embedding dos top 20% por engagement
- Recomendacoes de conteudo baseadas em similaridade
- Predicao de performance 0-100 com breakdown por fator
- Abordagem hibrida: estatistico (rapido) + LLM opcional (detalhado)
- Recomendacoes acionaveis (timing, hashtags, formato)

---

## Sprint 13 — Audience Feedback Loop + Competitive Content Gap Analysis

**Objetivo:** Insights de audiencia injetados em geracao de conteudo e analise de lacunas vs concorrentes.

**Bounded Context:** AI Intelligence

> **Nota:** Gap Analysis depende de Social Listening (Sprint 9) com dados acumulados. Feedback Loop depende do pipeline de embeddings (Sprint 12) nos comentarios.

### 13.1 Domain Layer

- [ ] `AudienceInsight` entity (insight_type, insight_data, confidence_score)
- [ ] `ContentGapAnalysis` entity (our_topics, competitor_topics, gaps, opportunities)
- [ ] Value Objects: `InsightType`, `GapCategory`
- [ ] Domain Events: `AudienceInsightsRefreshed`, `ContentGapsIdentified`
- [ ] Repository interfaces

### 13.2 Application Layer

- [ ] Use Cases Feedback Loop:
  - `GetAudienceInsightsUseCase`
  - `GetInsightsByTypeUseCase`
  - `RefreshAudienceInsightsUseCase`
- [ ] Use Cases Gap Analysis:
  - `GenerateGapAnalysisUseCase`
  - `ListGapAnalysesUseCase`
  - `GetGapAnalysisUseCase`
  - `GetOpportunitiesUseCase`
- [ ] Expansao dos Use Cases de geracao (RF-030 a RF-033) para injetar contexto de audiencia

### 13.3 Infrastructure Layer

- [ ] Migrations: `audience_insights`, `ai_generation_context`, `content_gap_analyses`
- [ ] Jobs: `RefreshAudienceInsightsJob`, `UpdateAIGenerationContextJob`, `GenerateContentGapAnalysisJob`
- [ ] Integracao com prompts de geracao (injecao de audience context)
- [ ] Controllers: `AudienceInsightsController`, `ContentGapAnalysisController`
- [ ] Scheduler: refresh semanal de insights, gap analysis mensal

### 13.4 Testes

- [ ] Unit: AudienceInsight entity, InsightType, ContentGapAnalysis
- [ ] Unit: Todos os Use Cases
- [ ] Integration: Aggregacao de insights de comentarios via LLM (mock)
- [ ] Integration: Gap analysis com mencoes de Social Listening (mock)
- [ ] Integration: Injecao de contexto nos prompts de geracao
- [ ] Feature: Audience insights (get, refresh)
- [ ] Feature: Campo `audience_context_used` nas respostas de geracao
- [ ] Feature: Desativar audience context via AI settings
- [ ] Feature: Gap analysis (generate, list, opportunities)
- [ ] Feature: Erro quando nao ha queries competitor configuradas
- [ ] Feature: Isolamento por organization_id

### Entregaveis Sprint 13

- Insights de audiencia extraidos de comentarios (topicos preferidos, tendencias de sentimento, drivers de engajamento)
- Injecao automatica de contexto de audiencia nos prompts de geracao de conteudo
- Transparencia: usuario ve qual contexto foi utilizado na geracao
- Controle: audience context desativavel por organizacao
- Analise de gaps de conteudo vs concorrentes monitorados via Social Listening
- Oportunidades acionaveis com score de oportunidade e sugestoes de conteudo
- Gap analysis on-demand + mensal automatica

---

## Sprint 14 — AI Learning & Feedback Loop

**Objetivo:** Implementar o loop de aprendizado da IA em 5 dos 6 niveis ativos — feedback tracking, RAG, prompt optimization, prediction accuracy e style learning — transformando a IA numa ferramenta que melhora com o uso. O Nivel 6 (CRM Intelligence) e implementado no Sprint 16.

**Bounded Contexts:** Content AI (expandido), AI Intelligence (expandido)

> **Referencia:** ADR-017 (AI Learning & Feedback Loop), Skill `06-domain/ai-learning-loop.md`

### 14.1 Domain Layer

- [ ] `GenerationFeedback` entity (action, original_output, edited_output, diff_summary)
- [ ] `PromptTemplate` aggregate root (system_prompt, user_prompt_template, performance_score, counters)
- [ ] `PromptExperiment` entity (A/B test entre 2 templates, z-test, confidence_level)
- [ ] `PredictionValidation` entity (predicted_score vs actual_normalized_score, accuracy)
- [ ] `OrgStyleProfile` aggregate root (tone, length, vocabulary, structure, hashtag preferences)
- [ ] Value Objects: `FeedbackAction`, `DiffSummary`, `PerformanceScore`, `StylePreferences`, `PredictionAccuracy`
- [ ] Domain Events: `GenerationFeedbackRecorded`, `GenerationEdited`, `PromptTemplateCreated`, `PromptPerformanceCalculated`, `PromptExperimentStarted`, `PromptExperimentCompleted`, `PredictionValidated`, `OrgStyleProfileGenerated`, `LearningContextUpdated`
- [ ] Contracts: `PromptTemplateResolverInterface`, `RAGContextProviderInterface`, `StyleProfileAnalyzerInterface`, `PredictionValidatorInterface`
- [ ] Repository interfaces

### 14.2 Application Layer

- [ ] Use Cases Feedback (Nivel 1):
  - `RecordGenerationFeedbackUseCase`
- [ ] Use Cases RAG (Nivel 2):
  - `RetrieveSimilarContentUseCase`
- [ ] Use Cases Prompt Optimization (Nivel 3):
  - `ResolvePromptTemplateUseCase`
  - `CreatePromptTemplateUseCase`
  - `CreatePromptExperimentUseCase`
  - `EvaluateExperimentUseCase`
  - `CalculatePromptPerformanceUseCase`
- [ ] Use Cases Prediction Accuracy (Nivel 4):
  - `ValidatePredictionUseCase`
  - `GetPredictionAccuracyUseCase`
- [ ] Use Cases Style Learning (Nivel 5):
  - `GenerateStyleProfileUseCase`
  - `UpdateLearningContextUseCase`
- [ ] Expansao dos Use Cases de geracao (Sprint 3) para integrar RAG + style + template resolution
- [ ] DTOs para input/output de cada use case

### 14.3 Infrastructure Layer

- [ ] Migrations: `generation_feedback`, `prompt_templates`, `prompt_experiments`, `prediction_validations`, `org_style_profiles`
- [ ] ALTER TABLE `ai_generations`: add `prompt_template_id`, `experiment_id`, `rag_context_used`, `style_context_used`
- [ ] Seeds: prompt templates globais default por generation_type
- [ ] Jobs:
  - `TrackGenerationFeedbackJob` (N1 — a cada feedback)
  - `CalculateDiffSummaryJob` (N1 — a cada edicao)
  - `RetrieveSimilarContentJob` (N2 — pre-geracao)
  - `CalculatePromptPerformanceJob` (N3 — semanal)
  - `EvaluatePromptExperimentJob` (N3 — pos-feedback)
  - `ValidatePredictionAccuracyJob` (N4 — 7d pos-publicacao)
  - `GenerateOrgStyleProfileJob` (N5 — semanal, min 10 edits)
  - `UpdateLearningContextJob` (N2+N5 — pos-atualizacao)
  - `CleanupExpiredLearningDataJob` (todos — semanal)
- [ ] Async Listeners: `PostPublished` → schedule validation, `MetricsSynced` → validate prediction, `PromptExperimentCompleted` → activate winner, `OrgStyleProfileGenerated` → update context
- [ ] Controllers: `GenerationFeedbackController`, `PromptTemplateController`, `PromptExperimentController`, `PredictionAccuracyController`, `StyleProfileController`
- [ ] Scheduler: performance recalc semanal, style profile semanal, cleanup semanal

### 14.4 Testes

- [ ] Unit: GenerationFeedback entity, FeedbackAction VO, DiffSummary VO, PerformanceScore VO
- [ ] Unit: PromptTemplate entity, version immutability, performance_score calculation
- [ ] Unit: PromptExperiment entity, z-test, confidence threshold, status transitions
- [ ] Unit: PredictionValidation entity, accuracy calculation, normalization
- [ ] Unit: OrgStyleProfile entity, confidence levels, TTL
- [ ] Unit: Todos os Use Cases (com mocks de repository)
- [ ] Integration: RAG via pgvector (cosine similarity + engagement filter)
- [ ] Integration: Style profile generation via LLM (mock de Prism)
- [ ] Integration: Diff calculation (Levenshtein)
- [ ] Feature: Feedback endpoint (accept/edit/reject)
- [ ] Feature: CRUD prompt templates (custom + system)
- [ ] Feature: A/B experiment lifecycle (create → run → complete)
- [ ] Feature: Prediction accuracy (validate → get metrics)
- [ ] Feature: Style learning (generate profile → inject in prompt)
- [ ] Feature: Geracao enriquecida (template + RAG + style + audience context)
- [ ] Feature: Feature gates por plano (RAG: Creator+, Style: Professional+, A/B: Agency)
- [ ] Feature: Graceful degradation (cada nivel falha silenciosamente)
- [ ] Feature: Isolamento por organization_id em todos os niveis

### Entregaveis Sprint 14

- Feedback tracking de todas as geracoes (accept/edit/reject) com diff estruturado
- RAG automatico: geracao enriquecida com top performers similares via pgvector
- Prompt templates versionados com auto-selecao por performance
- A/B testing de prompts com significancia estatistica (z-test, p < 0.05)
- Validacao de predicoes vs metricas reais 7 dias apos publicacao
- Perfil de estilo por organizacao aprendido de padroes de edicao
- Injecao automatica de contexto (RAG + style + audience) em geracao
- Feature gates por plano integrados ao billing
- Graceful degradation: nenhum nivel do Learning Loop e critical path

---

## Fase 4 — Integracoes CRM Nativas (v4.0)

Os sprints 15 e 16 implementam conectores nativos com os CRMs mais populares do mercado-alvo (Brasil + global), usando o Adapter Pattern (ADR-006) e a estrategia definida no ADR-018.

---

## Sprint 15 — CRM Connectors Fase 1 (HubSpot, RD Station, Pipedrive)

**Objetivo:** Conectores nativos plug-and-play com os 3 CRMs mais relevantes para o publico-alvo brasileiro (PMEs, agencias, freelancers), com sincronizacao bidirecional e mapeamento de campos.

**Bounded Context:** Engagement & Automation (extensao)

> **Nota:** Este sprint implementa a infraestrutura de CRM connectors (interface, factory, tabelas, jobs) que sera reutilizada pelo Sprint 16.

### 15.1 Domain Layer

- [ ] `CrmProvider` enum (hubspot, rdstation, pipedrive, salesforce, activecampaign)
- [ ] `CrmConnection` entity (provider, tokens, status, settings)
- [ ] `CrmFieldMapping` value object (smm_field, crm_field, transform)
- [ ] `CrmSyncResult` value object (direction, entity_type, action, status)
- [ ] Domain Events: `CrmConnected`, `CrmDisconnected`, `CrmContactSynced`, `CrmDealCreated`, `CrmActivityLogged`, `CrmSyncFailed`, `CrmTokenExpired`, `CrmFieldMappingUpdated`
- [ ] Contracts: `CrmConnectorInterface` (authenticate, createContact, createDeal, logActivity, searchContacts, etc.)
- [ ] Repository interfaces: `CrmConnectionRepositoryInterface`, `CrmFieldMappingRepositoryInterface`, `CrmSyncLogRepositoryInterface`

### 15.2 Application Layer

- [ ] Use Cases CRM Connection:
  - `ConnectCrmUseCase` (inicia OAuth flow)
  - `HandleCrmCallbackUseCase` (processa callback OAuth)
  - `DisconnectCrmUseCase` (revoke + soft delete)
  - `TestCrmConnectionUseCase` (verifica status)
  - `ListCrmConnectionsUseCase`
  - `GetCrmConnectionStatusUseCase`
- [ ] Use Cases CRM Sync (Outbound):
  - `SyncContactToCrmUseCase` (cria/atualiza contato)
  - `CreateCrmDealUseCase` (cria oportunidade)
  - `LogCrmActivityUseCase` (registra atividade)
- [ ] Use Cases CRM Sync (Inbound):
  - `ProcessCrmWebhookUseCase` (processa webhook do CRM)
- [ ] Use Cases Field Mapping:
  - `GetCrmFieldMappingsUseCase`
  - `UpdateCrmFieldMappingsUseCase`
  - `ResetCrmFieldMappingsToDefaultUseCase`
- [ ] Use Cases Maintenance:
  - `ForceCrmSyncUseCase` (sincronizacao manual)
  - `BackfillCrmContactsUseCase` (backfill apos conexao)
- [ ] DTOs: `CrmConnectionDTO`, `CrmMappingDTO`, `CrmSyncLogDTO`
- [ ] Listeners:
  - `CommentCaptured` → `SyncContactToCrmJob` (se CRM conectado)
  - `AutomationTriggered` (lead) → `CreateCrmDealJob` (se CRM conectado)
  - `PostPublished` → `LogCrmActivityJob` (se CRM conectado)
  - `CrmTokenExpired` → `RefreshCrmTokenJob`

### 15.3 Infrastructure Layer

- [ ] Migration: `crm_connections` table
- [ ] Migration: `crm_field_mappings` table
- [ ] Migration: `crm_sync_logs` table
- [ ] Migration: `crm_provider_type` enum
- [ ] Eloquent Models: CrmConnection, CrmFieldMapping, CrmSyncLog
- [ ] Repository implementations (Eloquent)
- [ ] **HubSpot Connector:**
  - OAuth 2.0 flow (access token 30min, refresh token)
  - Create/update contacts via HubSpot CRM API v3
  - Create deals via HubSpot Deals API
  - Log activities via HubSpot Engagements API
  - Search contacts via HubSpot Search API
  - Rate limiting: 150 req/10s (token bucket)
  - Default field mappings
- [ ] **RD Station Connector:**
  - OAuth 2.0 flow (access token 24h, refresh token)
  - Create/update contacts via RD Station CRM API
  - Create deals via RD Station Deals API
  - Rate limiting: 120 req/min
  - Default field mappings
- [ ] **Pipedrive Connector:**
  - OAuth 2.0 flow (access token 60min, refresh token)
  - Create/update persons via Pipedrive Persons API
  - Create deals via Pipedrive Deals API
  - Log activities via Pipedrive Activities API
  - Rate limiting: 80 req/2s
  - Default field mappings
- [ ] `CrmConnectorFactory` — resolve connector por provider
- [ ] Jobs:
  - `SyncContactToCrmJob` (queue: default, retry: 3, backoff: 60s/300s/900s)
  - `CreateCrmDealJob` (queue: default, retry: 3)
  - `LogCrmActivityJob` (queue: low, retry: 3)
  - `RefreshCrmTokenJob` (queue: high, retry: 2)
  - `ProcessCrmWebhookJob` (queue: default, retry: 3)
  - `BackfillCrmContactsJob` (queue: low, retry: 1)
- [ ] Controllers: CrmConnectionController, CrmMappingController, CrmSyncController
- [ ] Routes: 11 endpoints sob `/api/v1/crm/`
- [ ] Feature gate middleware: Professional+ para CRM connectors

### 15.4 Testes

- [ ] Unit tests: CrmConnection entity, CrmFieldMapping VO, CrmProvider enum
- [ ] Unit tests: Todos os Use Cases (mock de CrmConnectorInterface)
- [ ] Integration tests: HubSpot connector (HTTP mock)
- [ ] Integration tests: RD Station connector (HTTP mock)
- [ ] Integration tests: Pipedrive connector (HTTP mock)
- [ ] Integration tests: CrmConnectorFactory
- [ ] Feature tests: Endpoints de conexao, mapeamento, sync, logs
- [ ] Feature tests: Feature gate (Free/Creator bloqueado, Professional/Agency permitido)
- [ ] Feature tests: Fluxo completo outbound (comentario → contato no CRM)
- [ ] Feature tests: Fluxo completo inbound (webhook CRM → tag no SMM)
- [ ] Architecture tests: CrmConnectorInterface no Domain, implementacoes no Infrastructure

### Entregaveis Sprint 15

- Infraestrutura de CRM connectors (interface, factory, tabelas, jobs)
- 3 conectores nativos funcionais: HubSpot, RD Station, Pipedrive
- OAuth flow completo para cada CRM
- Sincronizacao outbound: comentarios → contatos, leads → deals, publicacoes → atividades
- Sincronizacao inbound: webhooks do CRM processados
- Mapeamento de campos customizavel com defaults sensiveis
- Logs de sincronizacao com filtros e paginacao
- Backfill de contatos existentes apos conexao
- Feature gates: Professional = 2 conexoes, Agency = 5 conexoes

---

## Sprint 16 — CRM Connectors Fase 2 + CRM Intelligence

**Objetivo:** Expandir conectores CRM com Salesforce (enterprise) e ActiveCampaign (automacao), e implementar a ponte CRM→IA (Nivel 6 do ADR-017) que conecta dados de conversao a geracao de conteudo.

**Bounded Context:** Engagement & Automation (extensao), AI Intelligence (extensao)

> **Nota:** Este sprint reutiliza toda a infraestrutura criada no Sprint 15. Os conectores novos sao implementacoes adicionais. A CRM Intelligence (N6) conecta os dados de CRM ao pipeline de aprendizado da IA (ADR-017).

### 16.1 Domain Layer

- [ ] Nenhuma alteracao — infraestrutura ja existe do Sprint 15.

### 16.2 Application Layer

- [ ] Nenhuma alteracao significativa — mesmos Use Cases reutilizados.
- [ ] Ajustes em DTOs se necessario para campos especificos de Salesforce/ActiveCampaign.

### 16.3 Infrastructure Layer

- [ ] **Salesforce Connector:**
  - OAuth 2.0 flow (access token 2h, refresh token)
  - Create/update contacts via Salesforce REST API (SObject)
  - Create opportunities via Salesforce Opportunities API
  - Log activities via Salesforce Tasks API
  - Search contacts via SOQL query
  - Rate limiting: 15.000 req/dia (standard), Bulk API para backfill
  - Default field mappings (custom fields com sufixo `__c`)
- [ ] **ActiveCampaign Connector:**
  - API Key authentication (nao expira)
  - Create/update contacts via ActiveCampaign API v3
  - Create deals via ActiveCampaign Deals API
  - Add tags e custom fields
  - Rate limiting: 5 req/s
  - Default field mappings
- [ ] Atualizar `CrmConnectorFactory` com novos providers
- [ ] Feature gate: Salesforce e ActiveCampaign somente Agency

### 16.4 Testes

- [ ] Integration tests: Salesforce connector (HTTP mock)
- [ ] Integration tests: ActiveCampaign connector (HTTP mock)
- [ ] Feature tests: OAuth flow Salesforce
- [ ] Feature tests: API Key flow ActiveCampaign
- [ ] Feature tests: Feature gate (Professional bloqueado para SF/AC, Agency permitido)
- [ ] Feature tests: Fluxo completo outbound/inbound para cada novo provider

### 16.5 CRM Intelligence (ADR-017 Nivel 6)

#### Domain Layer

- [ ] `CrmConversionAttribution` entity (AI Intelligence BC)
- [ ] `AttributionType` value object (direct_engagement, lead_capture, deal_closed)
- [ ] `CrmConversionAttributed` domain event
- [ ] `CrmAIContextEnriched` domain event
- [ ] `CrmIntelligenceProviderInterface`

#### Application Layer

- [ ] `AttributeCrmConversionUseCase` — atribui conversao CRM ao conteudo social de origem
- [ ] `EnrichAIContextFromCrmUseCase` — agrega dados de conversao e segmentos CRM para ai_generation_context

#### Infrastructure Layer

- [ ] Migration: `crm_conversion_attributions` table
- [ ] Migration: `ALTER TABLE prediction_validations ADD COLUMN conversion_count, conversion_value`
- [ ] `CrmIntelligenceProvider` implements `CrmIntelligenceProviderInterface`
- [ ] Atualizar `RAGContextProvider` com conversion boost logic
- [ ] Atualizar `UpdateLearningContextJob` para incluir CRM data
- [ ] `AttributeCrmConversionJob` — triggered por CrmDealCreated/CrmContactSynced
- [ ] `EnrichAIContextFromCrmJob` — batch semanal

#### Listeners

- [ ] `CrmDealCreated` → `AttributeCrmConversion`
- [ ] `CrmContactSynced` → `AttributeCrmConversion`
- [ ] `CrmConversionAttributed` → `UpdateLearningContext`

#### Testes

- [ ] Unit tests: AttributionType value object, conversion boost calculation
- [ ] Unit tests: CrmConversionAttribution entity rules
- [ ] Integration tests: AttributeCrmConversionUseCase
- [ ] Integration tests: EnrichAIContextFromCrmUseCase
- [ ] Feature tests: CRM Intelligence end-to-end (deal closed → attribution → RAG boost)
- [ ] Feature tests: Feature gate (Agency only)
- [ ] Feature tests: Graceful degradation (sem CRM conectado, sem interaction_data)

### Entregaveis Sprint 16

- 2 novos conectores nativos: Salesforce, ActiveCampaign
- Salesforce: OAuth completo, SOQL search, Bulk API para backfill
- ActiveCampaign: API Key auth, tags automaticas, custom fields
- Feature gate: Salesforce e ActiveCampaign exclusivos para Agency
- Total de 5 CRMs nativos disponiveis no sistema
- **CRM Intelligence (N6):** Dados de conversao CRM retroalimentam a IA
- **crm_conversion_attributions:** Rastreia conteudo → lead → deal → receita
- **RAG boost:** Conteudo que gera vendas e priorizado nas geracoes futuras
- **Feature gate:** CRM Intelligence exclusivo Agency

---

## Matriz de Dependencias

| Sprint | Depende de | Bounded Contexts | Fase |
|--------|-----------|-----------------|------|
| 0 | — | Infraestrutura | 1 |
| 1 | 0 | Identity, Organization | 1 |
| 2 | 1 | SocialAccount, Media | 1 |
| 3 | 1, 2 | Campaign, ContentAI | 1 |
| 4 | 2, 3 | Publishing | 1 |
| 5 | 4 | Analytics, Engagement | 1 |
| 6 | 1 | Billing | 1 |
| 7 | 1, 6 | PlatformAdmin | 1 |
| 8 | 1, 3, 5, 6 | ClientFinancialManagement | 2 |
| 9 | 2, 5 | SocialListening | 2 |
| 10 | 4, 5 | AI Intelligence (Best Time + Safety) | 2 |
| 11 | 3, 5 | AI Intelligence (Adaptation + Calendar), Content AI | 2 |
| 12 | 3, 5 | AI Intelligence (DNA + Prediction) | 3 |
| 13 | 5, 9, 12 | AI Intelligence (Feedback Loop + Gap Analysis) | 3 |
| 14 | 3, 5, 12, 13 | AI Intelligence (Learning Loop — ADR-017) | 3 |
| 15 | 2, 5, 6 | Engagement & Automation (CRM Connectors Fase 1 — ADR-018) | 4 |
| 16 | 14, 15 | Engagement & Automation (CRM Fase 2), AI Intelligence (CRM Intelligence N6 — ADR-017+018) | 4 |

> **Nota:** Sprint 6 (Billing) depende apenas do Sprint 1, podendo ser iniciado em paralelo com Sprints 3-5 se houver capacidade.

> **Nota:** Sprints 10-11 (AI Intelligence Alpha) podem rodar em paralelo com Sprints 8-9, pois dependem apenas dos Sprints 3-5 da Fase 1.

> **Nota:** Sprint 13 (Feedback Loop + Gap Analysis) depende do Sprint 9 (Social Listening) para dados de concorrentes e do Sprint 12 para o pipeline de embeddings.

> **Nota:** Sprint 14 (AI Learning Loop) depende do Sprint 3 (Content AI base), Sprint 5 (Analytics para metricas), Sprint 12 (embeddings para RAG) e Sprint 13 (audience insights para contexto).

> **Nota:** Sprint 15 (CRM Connectors Fase 1) depende do Sprint 2 (Social Account — OAuth patterns), Sprint 5 (Engagement & Automation — webhooks/comentarios) e Sprint 6 (Billing — feature gates). Pode rodar em paralelo com Sprints 12-14 se houver capacidade.

> **Nota:** Sprint 16 (CRM Fase 2 + CRM Intelligence) depende do Sprint 15 (infraestrutura CRM) e Sprint 14 (Learning Loop) para conectar dados de conversao CRM ao pipeline de IA.

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

### Fase 1 (v1.0) — Sprints 0-7

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
| **Subtotal Fase 1** | **30** | **~91** | **~95** | **17** | **~405** |

### Fase 2 (v2.0) — Sprints 8-11

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 8 | 5 | ~15 | ~18 | 4 | ~65 |
| 9 | 5 | ~16 | ~20 | 5 | ~70 |
| 10 | 3 | ~10 | ~10 | 3 | ~50 |
| 11 | 1 | ~6 | ~8 | 2 | ~40 |
| **Subtotal Fase 2** | **14** | **~47** | **~56** | **14** | **~225** |

### Fase 3 (v3.0) — Sprints 12-14

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 12 | 3 | ~8 | ~10 | 5 | ~55 |
| 13 | 3 | ~10 | ~10 | 3 | ~55 |
| 14 | 5 | ~12 | ~12 | 9 | ~65 |
| **Subtotal Fase 3** | **11** | **~30** | **~32** | **17** | **~175** |

### Fase 4 (v4.0) — Sprints 15-16

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 15 | 4 | ~11 | ~14 | 6 | ~70 |
| 16 | 2 | 0 | ~4 | 2 | ~45 |
| **Subtotal Fase 4** | **6** | **~11** | **~18** | **8** | **~115** |

| | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--|-----------|-----------|-----------|------|---------------|
| **Total Geral** | **61** | **~179** | **~201** | **56** | **~920** |

---

## Apos o Roadmap — Features Futuras

Itens para considerar apos a v4.0:

- **Notificacoes in-app** (WebSocket ou Pusher)
- **Threads/Twitter** como nova rede social
- **Pinterest** como nova rede social
- **Template library** de conteudo
- **A/B testing** de publicacoes
- **Team collaboration** (comentarios internos, aprovacoes)
- **White-label** (branding customizavel por organizacao)
- **API publica** (para integracao de terceiros)
- **Mobile app** (React Native ou Flutter)
- **Geracao de midia com IA** (descricao detalhada abaixo)

---

## Feature Futura: Geracao de Midia com IA (Agentes de IA)

> **Status:** Proposta para Fase 3+\
> **Personas beneficiadas:** Persona 3 (Carla — Empreendedora) e Persona 4 (Lucas — Criador de Conteudo)\
> **Extensao do Bounded Context:** Content AI (Sprint 3)

### Motivacao

As Personas 3 e 4 compartilham uma dor critica: **nao possuem tempo, recursos ou habilidade tecnica para produzir midia visual de qualidade** (imagens para posts e videos para Reels/TikTok/Shorts). Atualmente, o Content AI gera apenas **conteudo textual** (titulos, descricoes, hashtags). A geracao de midia visual via agentes de IA eliminaria a maior barreira de entrada para esses usuarios, permitindo que criem conteudo completo (texto + visual) sem sair da plataforma.

- **Carla (Persona 3)**: "Nao tem tempo nem criatividade para criar posts" — com IA gerando imagens de produtos e videos promocionais a partir de uma descricao simples, ela pode manter consistencia nas redes sem depender de designer ou videomaker.
- **Lucas (Persona 4)**: "Se eu pudesse automatizar o operacional, focaria so em criar conteudo" — com IA gerando thumbnails, capas e cortes de video, ele pode focar na criatividade estrategica e delegar a producao operacional visual.

### Visao Geral do Processo

```
Usuario cria prompt ─→ Sistema enriquece prompt ─→ Agente IA gera midia
         │                      │                           │
         │                      ▼                           ▼
         │              Adiciona specs tecnicas     Polling de status
         │              (resolucao, aspect ratio,   (geracao assincrona)
         │               estilo da marca)                   │
         │                                                  ▼
         │                                          Pos-processamento
         │                                          (resize, formato,
         │                                           thumbnail, scan)
         │                                                  │
         │                                                  ▼
         └────────────── Preview + Confirmacao ◄── Media na biblioteca
                         (usuario aprova antes           │
                          de usar)                       ▼
                                                  Vincula ao conteudo
                                                  (campanha/peca)
```

### Tipos de Geracao

#### 1. Geracao de Imagens

| Aspecto | Detalhe |
|---------|---------|
| **Input do usuario** | Prompt textual descrevendo a imagem desejada (ex: "foto de produto de roupa feminina em fundo minimalista pastel") |
| **Estilos disponiveis** | Fotorrealista, ilustracao, 3D render, flat design, watercolor, collage, minimal |
| **Formatos de saida** | PNG (alta qualidade), JPEG (otimizado para web), WebP |
| **Resolucoes** | Automaticas por rede destino (ver tabela abaixo) |
| **Variantes** | Gera 2-4 variantes para o usuario escolher |
| **Providers potenciais** | DALL-E 3 (OpenAI), Stable Diffusion XL (Stability AI), Midjourney API (quando disponivel), Ideogram |

**Resolucoes por rede social:**

| Rede | Tipo | Aspect Ratio | Resolucao sugerida |
|------|------|-------------|-------------------|
| Instagram | Feed Post | 1:1 | 1080x1080 |
| Instagram | Story/Reel | 9:16 | 1080x1920 |
| Instagram | Landscape | 1.91:1 | 1080x566 |
| TikTok | Video Cover | 9:16 | 1080x1920 |
| YouTube | Thumbnail | 16:9 | 1280x720 |
| YouTube | Shorts | 9:16 | 1080x1920 |

#### 2. Geracao de Videos

| Aspecto | Detalhe |
|---------|---------|
| **Input do usuario** | Prompt textual + imagem de referencia (opcional) + duracao desejada |
| **Tipos** | Video curto (5-15s para Reels/TikTok), video medio (15-60s), slideshow animado, video com texto animado |
| **Estilos** | Cinematico, produto em movimento, timelapse, animacao, motion graphics |
| **Audio** | Sem audio (usuario adiciona depois), musica de fundo generica (royalty-free), narração via TTS |
| **Providers potenciais** | OpenAI Sora, Runway ML Gen-3, Kling AI, Luma Dream Machine, Pika Labs |

### Fluxo Detalhado de Implementacao

#### Etapa 1: Request do Usuario

```json
POST /api/v1/ai/generate-media
{
  "type": "image",                          // "image" | "video"
  "prompt": "Foto de produto: vestido floral em cabide de madeira, fundo branco clean, luz natural suave",
  "style": "photorealistic",                // estilo visual
  "target_networks": ["instagram", "tiktok"], // define resolucoes automaticas
  "aspect_ratios": ["1:1", "9:16"],          // override manual (opcional)
  "brand_preset_id": null,                   // preset de marca (opcional)
  "reference_media_id": null,                // imagem de referencia (opcional, para video)
  "video_duration_seconds": null,            // apenas para video
  "variants_count": 3                        // quantas variantes gerar
}
```

#### Etapa 2: Enriquecimento de Prompt (Agente IA)

O sistema utiliza um **agente de IA orquestrador** para transformar o prompt do usuario em um prompt tecnico otimizado para o provider de geracao visual:

1. **Analise do prompt** — O agente interpreta a intencao do usuario.
2. **Injecao de specs tecnicas** — Adiciona resolucao, aspect ratio, qualidade, lighting.
3. **Aplicacao de brand guidelines** — Se a organizacao configurou preset de marca (cores, tipografia, estilo), o agente injeta essas diretrizes no prompt.
4. **Negative prompts** — Adiciona automaticamente restricoes de seguranca e qualidade (ex: "no text, no watermarks, no distorted faces, high quality").
5. **Otimizacao por provider** — Cada provider tem um formato ideal de prompt; o agente adapta.

```
Prompt do usuario:
"Foto de produto: vestido floral em cabide de madeira"

Prompt enriquecido (enviado ao provider):
"Professional product photography of a floral summer dress hanging on a
 wooden hanger, clean white background, soft natural daylight from left,
 minimal composition, high-end e-commerce style, 8k resolution,
 sharp focus on fabric texture --ar 1:1 --quality 2"
```

#### Etapa 3: Despacho para Provider (Assincrono)

```
                    ┌─────────────────┐
                    │ MediaGeneration  │
                    │ AgentOrchestrator│
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
     ┌────────────┐  ┌────────────┐  ┌────────────┐
     │  DALL-E 3  │  │ Stability  │  │  Runway ML │
     │  Adapter   │  │  Adapter   │  │  Adapter   │
     └────────────┘  └────────────┘  └────────────┘
```

- Geracao de imagem/video e **assincrona** (pode levar 10s a 5min dependendo do tipo).
- O sistema cria um `MediaGenerationRequest` com status `processing` e retorna ao usuario um ID para polling.
- Um **job** (`PollMediaGenerationJob`) verifica o status no provider ate completar ou falhar.
- Circuit breaker por provider (mesma estrategia do Publishing — Sprint 4).
- Se o provider primario falhar, **fallback** para provider secundario (ex: DALL-E falhou → tenta Stability AI).

#### Etapa 4: Pos-Processamento

Quando o provider retorna a midia gerada:

1. **Download e armazenamento temporario** — Midia baixada do provider e salva em storage temporario.
2. **Resize automatico** — Gera versoes em todas as resolucoes necessarias (baseado nas redes destino selecionadas).
3. **Conversao de formato** — Converte para formatos otimizados (ex: WebP para imagens, MP4 H.264 para videos).
4. **Geracao de thumbnails** — Thumbnail padrao para preview na biblioteca.
5. **Scan de seguranca** — Reutiliza `ScanMediaJob` do Media Management (Sprint 2).
6. **Validacao de qualidade** — Verifica se a midia atende criterios minimos (resolucao, file size, duracao para videos).
7. **Calculo de compatibilidade** — Reutiliza `CalculateCompatibilityUseCase` do Media Management.

#### Etapa 5: Preview e Confirmacao do Usuario

A midia gerada **nunca e usada automaticamente**. O usuario:

1. Ve preview das variantes geradas (2-4 opcoes).
2. Pode **selecionar** uma ou mais variantes.
3. Pode **editar o prompt** e regenerar.
4. Pode **refinar** via prompt de follow-up (ex: "mais claro", "sem fundo", "angulo diferente").
5. Ao confirmar, a midia selecionada e **salva na biblioteca de midias** (Media Management).
6. A midia pode ser diretamente vinculada a uma peca de conteudo.

> **Regra fundamental (herdada do Content AI):** A IA **nunca** toma decisoes autonomas. Toda midia gerada requer confirmacao explicita do usuario antes de ser usada.

### Entidades de Dominio (Extensao do Content AI)

#### MediaGenerationRequest (Aggregate Root)
```
MediaGenerationRequest
├── id: GenerationRequestId (UUID)
├── organization_id: OrganizationId
├── user_id: UserId
├── type: MediaGenerationType (Enum: image, video)
├── original_prompt: string (prompt do usuario)
├── enriched_prompt: string (prompt enriquecido pelo agente)
├── style: GenerationStyle (Value Object)
├── target_networks: SocialProvider[]
├── aspect_ratios: AspectRatio[] (Value Object)
├── reference_media_id: ?MediaId
├── video_duration_seconds: ?int
├── brand_preset_id: ?BrandPresetId
├── variants_requested: int
├── provider: AIMediaProvider (Enum: dalle3, stability_xl, runway_gen3, sora, kling)
├── provider_request_id: ?string (ID externo no provider)
├── status: GenerationStatus (Enum: pending, processing, completed, failed, cancelled)
├── variants: GeneratedVariant[] (Entity)
│   ├── id: VariantId (UUID)
│   ├── file_path: string (storage temporario)
│   ├── resolution: Dimensions (Value Object)
│   ├── format: string (png, jpeg, mp4)
│   ├── file_size_bytes: int
│   ├── duration_seconds: ?int (video)
│   ├── selected: bool (usuario selecionou?)
│   └── media_id: ?MediaId (apos salvar na biblioteca)
├── cost_estimate_usd: ?decimal
├── tokens_input: ?int (para prompt enrichment)
├── tokens_output: ?int
├── provider_cost_usd: ?decimal (custo direto do provider de geracao)
├── started_at: ?DateTimeImmutable
├── completed_at: ?DateTimeImmutable
├── error: ?GenerationError (Value Object)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### BrandPreset (Aggregate Root)
```
BrandPreset
├── id: BrandPresetId (UUID)
├── organization_id: OrganizationId
├── name: string
├── primary_colors: string[] (hex codes)
├── secondary_colors: string[]
├── typography_style: ?string (descricao do estilo tipografico)
├── visual_style: string (ex: "minimalist", "bold and colorful", "elegant")
├── brand_elements: ?string (descricao de elementos visuais recorrentes)
├── example_media_ids: MediaId[] (midias de referencia da marca)
├── is_default: bool
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

### Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `MediaGenerationRequested` | Usuario solicitou geracao | request_id, type, provider, user_id |
| `MediaGenerationProcessing` | Provider iniciou processamento | request_id, provider_request_id |
| `MediaGenerationCompleted` | Provider retornou resultado | request_id, variants_count, cost |
| `MediaGenerationFailed` | Geracao falhou | request_id, error, provider |
| `MediaVariantSelected` | Usuario selecionou variante | request_id, variant_id |
| `MediaVariantSaved` | Variante salva na biblioteca | request_id, variant_id, media_id |
| `BrandPresetCreated` | Preset de marca criado | preset_id, organization_id |
| `BrandPresetUpdated` | Preset de marca atualizado | preset_id, changes |

### Use Cases

| Use Case | Descricao |
|----------|-----------|
| `GenerateImageUseCase` | Solicita geracao de imagem com prompt e parametros |
| `GenerateVideoUseCase` | Solicita geracao de video com prompt e parametros |
| `GetGenerationStatusUseCase` | Polling de status de geracao em andamento |
| `ListGenerationHistoryUseCase` | Historico de geracoes de midia |
| `SelectVariantUseCase` | Usuario seleciona variante para usar |
| `SaveVariantToLibraryUseCase` | Salva variante na biblioteca de midias |
| `RegenerateMediaUseCase` | Regenera com prompt ajustado |
| `CancelGenerationUseCase` | Cancela geracao em andamento |
| `CreateBrandPresetUseCase` | Cria preset de marca da organizacao |
| `UpdateBrandPresetUseCase` | Atualiza preset de marca |
| `ListBrandPresetsUseCase` | Lista presets da organizacao |
| `EstimateCostUseCase` | Estima custo antes de gerar (preview de preco) |

### Endpoints Previstos

| Endpoint | Metodo | Descricao |
|----------|--------|-----------|
| `/api/v1/ai/generate-media` | POST | Solicitar geracao de imagem ou video |
| `/api/v1/ai/generations/{id}` | GET | Status e resultado de uma geracao |
| `/api/v1/ai/generations/{id}/select` | POST | Selecionar variante(s) |
| `/api/v1/ai/generations/{id}/save` | POST | Salvar variante(s) na biblioteca |
| `/api/v1/ai/generations/{id}/regenerate` | POST | Regenerar com prompt ajustado |
| `/api/v1/ai/generations/{id}/cancel` | POST | Cancelar geracao em andamento |
| `/api/v1/ai/media-history` | GET | Historico de geracoes de midia |
| `/api/v1/ai/estimate-cost` | POST | Estimar custo de uma geracao |
| `/api/v1/ai/brand-presets` | GET/POST | Listar/criar presets de marca |
| `/api/v1/ai/brand-presets/{id}` | GET/PUT/DELETE | CRUD de preset individual |

### Gestao de Custos

Geracao visual e **significativamente mais cara** que geracao textual. O sistema precisa de controle rigoroso:

| Provider | Tipo | Custo estimado por geracao |
|----------|------|--------------------------|
| DALL-E 3 | Imagem (1024x1024) | ~$0.04-0.08 USD |
| DALL-E 3 | Imagem (1024x1792) | ~$0.08-0.12 USD |
| Stability AI XL | Imagem | ~$0.02-0.06 USD |
| Sora (OpenAI) | Video (5s) | ~$0.10-0.50 USD (estimado) |
| Runway Gen-3 | Video (5s) | ~$0.05-0.25 USD |

**Limites por plano (sugestao):**

| Recurso | Free | Creator | Professional | Agency |
|---------|------|---------|-------------|--------|
| Geracoes de imagem/mes | 5 | 50 | 200 | 1000 |
| Geracoes de video/mes | 0 | 10 | 50 | 200 |
| Brand presets | 0 | 1 | 5 | 20 |

**Regras de custo:**
- Custo estimado exibido ao usuario **antes** da geracao (confirmacao explicita).
- Custo real registrado apos conclusao (pode variar do estimado).
- Geracoes de midia tem contadores separados das geracoes de texto.
- Organizacao pode definir budget mensal maximo para geracao visual.
- Alerta ao atingir 80% do limite mensal.

### Infraestrutura (Jobs e Scheduler)

| Job | Descricao | Frequencia |
|-----|-----------|------------|
| `DispatchMediaGenerationJob` | Envia request ao provider | On-demand (fila) |
| `PollMediaGenerationJob` | Verifica status no provider | A cada 5s enquanto processing |
| `PostProcessGeneratedMediaJob` | Resize, formato, thumbnail, scan | On-demand (apos conclusao) |
| `CleanupUnselectedVariantsJob` | Remove variantes nao selecionadas apos 24h | Diario |
| `SyncMediaProviderUsageJob` | Sincroniza uso/creditos com provider | A cada 6h |

### Adapter Pattern (Extensao)

Mesma estrategia do Social Media Adapters (ADR-006). Adicionar nova interface:

```
AIMediaGeneratorInterface
├── generateImage(prompt, options): GenerationTicket
├── generateVideo(prompt, options): GenerationTicket
├── getStatus(ticketId): GenerationStatus
├── getResult(ticketId): GenerationResult
├── cancelGeneration(ticketId): void
└── estimateCost(type, options): CostEstimate
```

Cada provider implementa a interface. Factory resolve por configuracao da organizacao ou fallback chain.

### Dependencias

| Depende de | Razao |
|-----------|-------|
| Sprint 2 (Media Management) | Storage, scan, thumbnails, validacao, compatibilidade |
| Sprint 3 (Content AI) | AISettings, historico, cost tracking, Prism SDK |
| Sprint 6 (Billing) | Limites por plano, contadores de uso, enforcement |
| Sprint 8 (Client Financial Mgmt) | Alocacao de custo de geracao visual por cliente (opcional) |

### Riscos e Mitigacoes

| Risco | Impacto | Mitigacao |
|-------|---------|-----------|
| APIs de video IA ainda imaturas | Qualidade inconsistente, mudancas de pricing | Adapter pattern permite trocar provider sem mudanca no dominio. Comecar com imagens (mais estavel). |
| Custo alto de geracao | Pode inviabilizar plano Free | Limites conservadores no Free, budget cap por org, estimativa pre-geracao. |
| Tempo de geracao longo (videos) | UX ruim, timeout | Processamento 100% assincrono, notificacao quando pronto, webhook opcional. |
| Conteudo inapropriado gerado | Risco reputacional | Safety filters do provider + scan proprio + flag/report do usuario. |
| Rate limits dos providers | Geracao bloqueada em picos | Fila com prioridade, circuit breaker por provider, fallback chain. |
| Dependencia de providers externos | Lock-in, mudanca de pricing | Interface abstraida, multiplos providers, possibilidade de self-hosted (Stability AI). |

### Ordem de Implementacao Sugerida

1. **Fase A — Imagens com 1 provider** (menor complexidade)
   - Integracao com DALL-E 3 (via OpenAI API, ja integrado para texto)
   - Geracao basica de imagens com prompt
   - Resize automatico por rede social
   - Integracao com biblioteca de midias
   - Limites e cost tracking

2. **Fase B — Brand presets + multiplos providers de imagem**
   - BrandPreset entity e configuracao
   - Adicionar Stability AI como segundo provider
   - Enriquecimento de prompt com brand guidelines
   - Fallback chain entre providers

3. **Fase C — Geracao de video**
   - Integracao com provider de video (Sora ou Runway)
   - Pos-processamento de video (formato, duracao, thumbnail)
   - Geracao de video a partir de imagem de referencia
   - Audio/TTS opcional

> **Nota de implementacao:** Esta feature e candidata a ter seu proprio ADR devido a complexidade arquitetural (agentes de IA, orquestracao assincrona, fallback chain, gestao de custos multiplos providers). Recomenda-se criar o ADR antes de iniciar a implementacao.
