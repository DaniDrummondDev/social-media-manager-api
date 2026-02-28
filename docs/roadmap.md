# Roadmap de Implementacao — Social Media Manager API

> **Versao:** 1.2.0\
> **Data:** 2026-02-26\
> **Status:** Em desenvolvimento — Fase 5 completa, Sprint 18 completo

---

## Status Atual

| Fase | Sprints | Status |
|------|---------|--------|
| **Fase 1 — Core (v1.0)** | Sprint 0-7 | ✅ Completa |
| **Fase 2 — Expansao (v2.0)** | Sprint 8-11 | ✅ Completa (2 integration tests pendentes no Sprint 9-10) |
| **Fase 3 — IA Avancada (v3.0)** | Sprint 12-14 | ✅ Completa (pendencias integration tests + expansao geracao) |
| **Fase 4 — CRM (v4.0)** | Sprint 15-16 | ✅ Completa |
| **Fase 5 — Ads (v5.0)** | Sprint 17-18 | ✅ Completa |
| **Fase 6 — AI Agents (v6.0)** | Sprint 19 | ⏳ Em progresso (19.1-19.4 completos) |
| **Fase 7 — Consolidacao (v7.0)** | Sprint 20-21 | ⏳ Nao iniciada |

### Progresso detalhado

| Sprint | Nome | Domain | Application | Infrastructure | Testes | Status |
|--------|------|--------|-------------|----------------|--------|--------|
| 0 | Scaffolding & Infra | — | — | ✅ | ✅ | ✅ Completo |
| 1 | Identity & Access + Org | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 2 | Social Account + Media | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 3 | Campaign + Content AI | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 4 | Publishing | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 5 | Analytics + Engagement | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 6 | Billing & Subscription | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 7 | Platform Administration | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 8 | Client Finance | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 9 | Social Listening | ✅ | ✅ | ✅ | ⚠️ 2 integration pendentes | ✅ Completo* |
| 10 | Best Time + Brand Safety | ✅ | ✅ | ✅ | ⚠️ 2 integration pendentes | ✅ Completo* |
| 11 | Cross-Network + Calendar | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 12 | Content DNA + Prediction | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 13 | Feedback Loop + Gap | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 14 | AI Learning Loop | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 15 | CRM Connectors Fase 1 | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 16 | CRM Fase 2 + Intelligence | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 17 | Paid Advertising Core | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 18 | AI Learning from Ads | ✅ | ✅ | ✅ | ✅ | ✅ Completo |
| 19 | Multi-Agent AI (LangGraph) | ✅ | ✅ | ✅ | ✅ | ⏳ 19.1-19.4 completos |
| 20 | Geracao Enriquecida | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 21 | Feature Gates + Integration Tests | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |

> \* Sprints 9 e 10 possuem 2 testes de integracao cada com TODO stub (listening adapters mock, mention partitioning, best times calculation, safety check via LLM mock). Funcionalidade completa, testes de integracao pendentes de implementacao real dos mocks.

### Seguranca — Row-Level Security (RLS)

- [x] Migration RLS: `ENABLE ROW LEVEL SECURITY` + `FORCE ROW LEVEL SECURITY` em 36 tabelas multi-tenant + audit_logs
- [x] Middleware `SetTenantContext` (alias `tenant.rls`): executa `SET LOCAL app.current_org_id` com org_id do JWT
- [x] Bypass policy para jobs, admin, migrations (quando `app.current_org_id` nao esta definido)
- [x] Rotas atualizadas: `tenant.rls` adicionado a todos os grupos com `org.context` (12 arquivos)
- [x] ADR-019 atualizado com detalhes da implementacao

> **Referencia:** ADR-019 (Nivel 2 implementado antecipadamente como defesa em profundidade)

### Proximo passo

**Sprint 19.5 — Visual Adaptation Pipeline**: Proximo passo. Sprint 19.4 (Social Listening) completo — 4 agentes LangGraph, fluxo linear com prompts condicionais para crise, 35 testes pytest (3 pipelines). 2510 testes Laravel passando.

### Security Audit — Hardening Completo

Auditoria de seguranca abrangendo OWASP API Security Top 10, performance, bugs de logica e best practices. Todas as correcoes validadas com 2510 testes passando.

**Critical (9 itens):**
- [x] C1: Exception handler generico (catch-all \Throwable → 500 sem leak de detalhes)
- [x] C2: Idempotencia Stripe webhooks (`createIfNotExists` atomico)
- [x] C3: Validacao `STRIPE_WEBHOOK_SECRET` obrigatoria
- [x] C4: IDOR em endpoints CRM (`organization_id` scope)
- [x] C5: SQL injection em `SyncUsageRecordsJob` (`whereRaw` → Eloquent builder)
- [x] C6: Idempotencia `RecordUsageUseCase` (`incrementOrCreate` atomico)
- [x] C7: 2FA secret em `$hidden` no `UserModel`
- [x] C8: CRM tokens encryption (AES-256-GCM via `CrmTokenEncrypter`)
- [x] C9: `SyncUsageRecordsJob` refactor (chunkById + RESOURCE_MAP)

**High (26 itens — 9 batches):**
- [x] B1: `$hidden` em 4 models (SocialAccount, CrmConnection, Webhook, AdAccount)
- [x] B2: SPL `DomainException` → `App\Domain\Shared\Exceptions\DomainException` (6 arquivos)
- [x] B3: Input validation (2 FormRequests novos, limit cap 100, 2FA secret)
- [x] B4: Domain bugs (sentiment fix, Subscription::upgrade guard, Content::update guard, CrmConnection::refreshTokens guard, Stripe empty ID validation)
- [x] B5: Race conditions (AcceptInvite TOCTOU via `createIfNotExists`, retryable posts `lockForUpdate`)
- [x] B6: Transactions (`TransactionManagerInterface`, DuplicateCampaign wrap, ProcessScheduledPost null check, CommentCaptured event data)
- [x] B7: Security middleware (SecurityHeaders, SSRF protection em webhooks)
- [x] B8: Job hardening (`$timeout/$tries/$backoff` em 23 jobs, `chunkById` em 3 fan-outs)
- [x] B9: BOLA fix `ListContentsUseCase` (organization ownership verification)

---

## Visao Geral

O roadmap esta dividido em **22 sprints** organizados por dependencia entre bounded contexts. Os Sprints 0-7 cobrem a **Fase 1 (v1.0)**, os Sprints 8-11 cobrem a **Fase 2 (v2.0)**, os Sprints 12-14 cobrem a **Fase 3 (v3.0)**, os Sprints 15-16 cobrem a **Fase 4 (v4.0)**, os Sprints 17-18 cobrem a **Fase 5 (v5.0)**, o Sprint 19 cobre a **Fase 6 (v6.0)** e os Sprints 20-21 cobrem a **Fase 7 (v7.0)**. Cada sprint entrega valor incremental e pode ser testado isoladamente.

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

                           Fase 5 (v5.0)
            Sprint 17 ─────────────────→ Sprint 18
            (Paid Advertising             (AI Learning
             Core — ADR-020)              from Ads Data)

                           Fase 7 (v7.0)
            Sprint 20 ─────────────────→ Sprint 21
            (Geracao Enriquecida:         (Feature Gates +
             RAG + Style + Audience       Integration Tests
             + Template)                  Pendentes)
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

- [x] `ScheduledPost` entity
- [x] Value Objects: `PublishingStatus`, `ScheduleTime`, `PublishError`
- [x] Domain Events: `PostScheduled`, `PostDispatched`, `PostPublished`, `PostFailed`, `PostCancelled`
- [x] Repository interface

### 4.2 Application Layer

- [x] Use Cases:
  - `SchedulePostUseCase`, `PublishNowUseCase`
  - `CancelScheduleUseCase`, `RescheduleUseCase`
  - `ListScheduledPostsUseCase`, `GetCalendarUseCase`
  - `ProcessScheduledPostUseCase`, `RetryPublishUseCase`

### 4.3 Infrastructure Layer

- [x] Migration: `scheduled_posts`
- [x] Adapters de publicacao (stubs — implementacao real nas integracoes):
  - `InstagramPublisher`, `TikTokPublisher`, `YouTubePublisher`
- [x] Jobs: `ProcessScheduledPostJob`, `DispatchScheduledPostsJob`
- [x] Circuit breaker por provider (Redis-based via Cache)
- [x] Scheduler: verificar posts pendentes (a cada minuto)
- [x] Idempotencia via `idempotency_key` no scheduled_post
- [x] Controllers: `PublishingController`, `ScheduledPostController`

### 4.4 Testes

- [x] Unit: ScheduledPost entity, status transitions, ScheduleTime validation
- [x] Unit: Use Cases (schedule, publish, retry logic)
- [x] Integration: Publisher adapters (factory, stubs, circuit breaker)
- [x] Feature: Agendar, cancelar, reagendar
- [x] Feature: Publicacao imediata
- [x] Feature: Retry com backoff
- [x] Feature: Calendario de publicacoes

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

- [x] Domain: `ContentMetric`, `ContentMetricSnapshot`, `AccountMetric`, `ReportExport`
- [x] Value Objects: `MetricPeriod`, `ExportFormat`, `ReportType`, `ExportStatus`
- [x] Use Cases: `GetOverviewUseCase`, `GetNetworkAnalyticsUseCase`, `GetContentAnalyticsUseCase`, `ExportReportUseCase`, `GenerateReportUseCase`, `SyncPostMetricsUseCase`, `SyncAccountMetricsUseCase`, `GetExportUseCase`, `ListExportsUseCase`
- [x] Migrations: `content_metrics`, `content_metric_snapshots` (particionada por mes), `account_metrics` (particionada), `report_exports`
- [x] Adapters: `InstagramAnalytics`, `TikTokAnalytics`, `YouTubeAnalytics` (stubs)
- [x] Jobs: `SyncPostMetricsJob`, `SyncAccountMetricsJob`, `SyncSingleAccountMetricsJob`, `GenerateReportJob`
- [x] Controllers: `AnalyticsController` (6 actions: overview, network, content, export, showExport, listExports)
- [x] Scheduler: sync metricas de conta a cada 6h

### 5.2 Engagement

- [x] Domain: `Comment`, `AutomationRule`, `AutomationExecution`, `BlacklistWord`, `WebhookEndpoint`, `WebhookDelivery`
- [x] Value Objects: `Sentiment`, `ActionType`, `ConditionOperator`, `RuleCondition`, `WebhookSecret`
- [x] Domain Service: `AutomationEngine` (avalia regras por prioridade, AND conditions, blacklist, daily limit, network filters)
- [x] Domain Events: `CommentCaptured`, `CommentReplied`, `AutomationTriggered`, `AutomationExecuted`, `WebhookEndpointCreated`, `WebhookDelivered`
- [x] Use Cases (22): `ListCommentsUseCase`, `MarkCommentAsReadUseCase`, `ReplyCommentUseCase`, `SuggestReplyUseCase`, `CaptureCommentsUseCase`, CRUD `AutomationRule`, `EvaluateAutomationUseCase`, `ExecuteAutomationUseCase`, `ListExecutionsUseCase`, CRUD `BlacklistWord`, CRUD `WebhookEndpoint`, `TestWebhookUseCase`, `DeliverWebhookUseCase`, `ListDeliveriesUseCase`
- [x] Contracts: `SocialEngagementFactoryInterface`, `AiSuggestionInterface`, `WebhookHttpClientInterface`
- [x] Migrations (7): `comments`, `automation_rules`, `automation_rule_conditions`, `automation_executions`, `automation_blacklist_words`, `webhook_endpoints`, `webhook_deliveries`
- [x] Adapters: `InstagramEngagement`, `TikTokEngagement`, `YouTubeEngagement` (stubs)
- [x] Services: `SocialEngagementFactory`, `StubAiSuggestion`, `LaravelWebhookHttpClient`, `WebhookSigner`
- [x] Jobs (5): `CaptureCommentsJob`, `CaptureSingleAccountCommentsJob`, `ExecuteAutomationJob`, `DeliverWebhookJob`, `RetryWebhookDeliveriesJob`
- [x] Listeners: `EvaluateAutomationOnCommentCaptured`, `DispatchWebhooksOnCommentCaptured`, `DispatchWebhooksOnCommentReplied`
- [x] Controllers: `CommentController`, `AutomationRuleController`, `BlacklistController`, `WebhookController`
- [x] Routes: 18 endpoints (comments, automation-rules, automation-blacklist, webhooks)
- [x] Scheduler: captura comentarios (30min), retry webhook deliveries (5min)

### 5.3 Testes

- [x] Unit: MetricPeriod, ExportStatus, ContentMetric, ContentMetricSnapshot, AccountMetric, ReportExport, GetOverviewUseCase, ExportReportUseCase, SyncPostMetricsUseCase
- [x] Integration: EloquentContentMetricRepository, EloquentReportExportRepository
- [x] Feature: Dashboard analytics (overview, network, content), exportacao (create, list, show)
- [x] Unit Domain: RuleCondition, WebhookSecret, Comment, AutomationRule, BlacklistWord, WebhookEndpoint, WebhookDelivery, AutomationEngine
- [x] Unit Application: ReplyCommentUseCase, EvaluateAutomationUseCase, CreateWebhookUseCase, DeliverWebhookUseCase
- [x] Integration: EloquentCommentRepository, EloquentAutomationRuleRepository, EloquentWebhookEndpointRepository
- [x] Feature: Comments (list, filter, mark-read), AutomationRules (CRUD, executions, priority conflict), Blacklist (CRUD), Webhooks (CRUD, deliveries)

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

- [x] `Plan`, `Subscription`, `UsageRecord`, `Invoice` entities
- [x] Value Objects: `BillingCycle`, `SubscriptionStatus`, `PlanLimits`, `Money`
- [x] Domain Events: `SubscriptionCreated`, `SubscriptionUpgraded`, `SubscriptionCanceled`, `SubscriptionExpired`, `PaymentFailed`, `PaymentSucceeded`, `PlanLimitReached`
- [x] `PaymentGatewayInterface` contract

### 6.2 Application Layer

- [x] Use Cases:
  - `GetSubscriptionUseCase`, `GetUsageUseCase`, `ListInvoicesUseCase`
  - `CreateCheckoutSessionUseCase`, `CreatePortalSessionUseCase`
  - `ProcessStripeWebhookUseCase`
  - `CheckPlanLimitUseCase`, `RecordUsageUseCase`
  - `DowngradeToFreePlanUseCase`
  - `ListPlansUseCase`

### 6.3 Infrastructure Layer

- [x] Migrations: `plans`, `subscriptions`, `usage_records`, `invoices`
- [x] Seeds: planos default (Free, Creator, Professional, Agency)
- [x] `StripePaymentGateway` (implementa `PaymentGatewayInterface`)
- [x] Middleware: `CheckPlanLimit` (verifica limites antes de acoes)
- [x] Jobs: `ProcessStripeWebhookJob`, `CheckExpiredSubscriptionsJob`, `DowngradeToFreePlanJob`, `SyncUsageRecordsJob`
- [x] Controllers: `BillingController`, `PlanController`
- [x] Webhook endpoint: `POST /api/v1/webhooks/stripe` (signature validation)
- [x] Scheduler: verificar subscriptions expiradas (diario)

### 6.4 Testes

- [x] Unit: Subscription status transitions, PlanLimits, Money VO
- [x] Unit: Use Cases (checkout, webhook processing, limit check)
- [x] Integration: Stripe API (mock via Stripe test mode)
- [x] Feature: Listar planos, ver subscription, ver uso
- [x] Feature: Checkout flow (upgrade)
- [x] Feature: Webhook processing (subscription events, payment events)
- [x] Feature: Enforcement de limites (402 quando atingido)
- [x] Feature: Downgrade automatico apos expiracao

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

- [x] `PlatformAdmin`, `SystemConfig`, `AdminAuditEntry` entities
- [x] Value Objects: `PlatformRole`
- [x] Domain Events: `OrganizationSuspended`, `OrganizationUnsuspended`, `UserBanned`, `UserUnbanned`, `PlanCreated`, `PlanUpdated`, `SystemConfigUpdated`, `MaintenanceModeEnabled`

### 7.2 Application Layer

- [x] Use Cases:
  - `GetDashboardUseCase` (metricas globais: MRR, ARR, churn, uso)
  - `ListOrganizationsAdminUseCase`, `SuspendOrganizationUseCase`, `UnsuspendOrganizationUseCase`, `DeleteOrganizationUseCase`
  - `ListUsersAdminUseCase`, `BanUserUseCase`, `UnbanUserUseCase`, `ForceVerifyUseCase`
  - `CreatePlanUseCase`, `UpdatePlanUseCase`, `DeactivatePlanUseCase`
  - `GetSystemConfigUseCase`, `UpdateSystemConfigUseCase`

### 7.3 Infrastructure Layer

- [x] Migrations: `platform_admins`, `system_configs`, `admin_audit_entries`
- [x] Seeds: super_admin default, system configs default
- [x] Middleware: `PlatformAdminMiddleware` (valida `platform_role` no JWT)
- [x] Jobs: `PauseOrgScheduledPostsJob`, `InvalidateUserSessionsJob`, `CleanupSuspendedOrgsJob`
- [x] Controllers: `AdminDashboardController`, `AdminOrganizationController`, `AdminUserController`, `AdminPlanController`, `AdminConfigController`
- [x] Scheduler: cleanup de orgs suspensas > 30 dias

### 7.4 Testes

- [x] Unit: PlatformRole, SystemConfig, Dashboard metrics calculation
- [x] Feature: Dashboard admin (metricas globais)
- [x] Feature: Suspender/reativar organizacao
- [x] Feature: Banir/desbanir user (invalida sessoes)
- [x] Feature: CRUD de planos
- [x] Feature: Alterar system config (maintenance mode, registration)
- [x] Feature: Audit trail de acoes admin
- [x] Feature: User regular acessando /admin/* retorna 403

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

- [x] `Client` entity (id, organization_id, name, email, company_name, tax_id, status)
- [x] `ClientContract` entity (id, client_id, type, value, period, social_accounts vinculadas)
- [x] `ClientInvoice` entity (id, client_id, contract_id, items, totals, status, due_date)
- [x] `CostAllocation` entity (id, client_id, resource_type, resource_id, cost)
- [x] Value Objects: `ClientId`, `TaxId` (CPF/CNPJ), `Address`, `Currency`, `YearMonth`, `ContractType`, `InvoiceStatus`, `ContractStatus`
- [x] Domain Events: `ClientCreated`, `ClientArchived`, `ContractCreated`, `ContractCompleted`, `InvoiceGenerated`, `InvoiceSent`, `InvoiceMarkedPaid`, `InvoiceOverdue`, `CostAllocated`
- [x] Repository interfaces: `ClientRepositoryInterface`, `ClientContractRepositoryInterface`, `ClientInvoiceRepositoryInterface`, `CostAllocationRepositoryInterface`
- [x] Domain Service: `InvoiceCalculationService` (calcula totais com base nos items e tipo de contrato)

### 8.2 Application Layer

- [x] Use Cases Client:
  - `CreateClientUseCase`
  - `UpdateClientUseCase`
  - `ListClientsUseCase`
  - `GetClientUseCase`
  - `ArchiveClientUseCase`
- [x] Use Cases Contract:
  - `CreateContractUseCase`
  - `UpdateContractUseCase`
  - `ListContractsUseCase`
  - `PauseContractUseCase`
  - `CompleteContractUseCase`
- [x] Use Cases Invoice:
  - `GenerateInvoiceUseCase` (manual, com items customizados)
  - `GenerateMonthlyInvoicesUseCase` (batch, baseado em contratos ativos)
  - `ListInvoicesUseCase`
  - `GetInvoiceUseCase`
  - `SendInvoiceUseCase` (envia por email)
  - `MarkInvoicePaidUseCase`
  - `CancelInvoiceUseCase`
- [x] Use Cases Cost:
  - `AllocateCostUseCase`
  - `GetCostBreakdownUseCase` (custos por cliente, periodo)
  - `GetProfitabilityReportUseCase` (receita vs custos por cliente)
- [x] Use Cases Report:
  - `GetFinancialDashboardUseCase` (receita total, inadimplencia, top clientes)
  - `ExportFinancialReportUseCase` (PDF, CSV)
- [x] DTOs para input/output de cada use case

### 8.3 Infrastructure Layer

- [x] Migrations: `clients`, `client_contracts`, `client_invoices`, `client_invoice_items`, `cost_allocations`
- [x] Eloquent Models + Repositories
- [x] `InvoicePdfGenerator` service (gera PDF da fatura)
- [x] Email notifications: fatura enviada, fatura vencida, lembrete de pagamento
- [x] Jobs: `GenerateMonthlyInvoicesJob`, `CheckOverdueInvoicesJob`, `ExportFinancialReportJob`, `SendInvoiceReminderJob`
- [x] Controllers: `ClientController`, `ClientContractController`, `ClientInvoiceController`, `FinancialReportController`
- [x] Scheduler: verificar faturas vencidas (diario), gerar faturas mensais (dia 1 de cada mes)

### 8.4 Testes

- [x] Unit: Client entity, TaxId VO (CPF/CNPJ validation), Address VO, Currency, InvoiceCalculationService
- [x] Unit: Todos os Use Cases (com mocks de repository)
- [x] Unit: InvoiceStatus transitions, ContractStatus transitions
- [x] Integration: Eloquent repositories
- [x] Integration: InvoicePdfGenerator
- [x] Feature: CRUD de clientes
- [x] Feature: CRUD de contratos
- [x] Feature: Geracao e envio de faturas
- [x] Feature: Alocacao de custos e relatorios de lucratividade
- [x] Feature: Dashboard financeiro
- [x] Feature: Isolamento por organization_id

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

- [x] `ListeningQuery` entity (id, organization_id, name, type, value, platforms, is_active)
- [x] `Mention` entity (id, query_id, platform, author, content, sentiment, reach, engagement)
- [x] `ListeningAlert` entity (id, organization_id, query_ids, condition, notification_channels, cooldown)
- [x] `ListeningReport` entity (id, organization_id, query_ids, period, metrics, sentiment_breakdown)
- [x] Value Objects: `QueryId`, `MentionId`, `AlertId`, `QueryType` (keyword, hashtag, mention, competitor), `AlertCondition`, `ConditionType` (volume_spike, negative_sentiment_spike, keyword_detected, influencer_mention), `NotificationChannel`, `SentimentBreakdown`, `MentionSource`
- [x] Domain Events: `ListeningQueryCreated`, `ListeningQueryPaused`, `ListeningQueryResumed`, `MentionDetected`, `MentionFlagged`, `ListeningAlertTriggered`, `ListeningReportGenerated`, `SentimentSpikeDetected`
- [x] Repository interfaces: `ListeningQueryRepositoryInterface`, `MentionRepositoryInterface`, `ListeningAlertRepositoryInterface`, `ListeningReportRepositoryInterface`
- [x] Domain Service: `AlertEvaluationService` (avalia condicoes de alerta contra mencoes recentes)

### 9.2 Application Layer

- [x] Use Cases Query:
  - `CreateListeningQueryUseCase`
  - `UpdateListeningQueryUseCase`
  - `ListListeningQueriesUseCase`
  - `PauseListeningQueryUseCase`
  - `ResumeListeningQueryUseCase`
  - `DeleteListeningQueryUseCase`
- [x] Use Cases Mention:
  - `ListMentionsUseCase` (com filtros: query, platform, sentiment, periodo)
  - `GetMentionDetailsUseCase`
  - `FlagMentionUseCase` (destaque manual)
  - `MarkMentionsReadUseCase`
  - `ProcessMentionsBatchUseCase` (chamado pelo job de captura)
- [x] Use Cases Alert:
  - `CreateAlertUseCase`
  - `UpdateAlertUseCase`
  - `ListAlertsUseCase`
  - `DeleteAlertUseCase`
  - `EvaluateAlertsUseCase` (chamado pelo job de avaliacao)
- [x] Use Cases Dashboard/Report:
  - `GetListeningDashboardUseCase` (total mencoes, sentimento, tendencias, top autores)
  - `GetSentimentTrendUseCase` (serie temporal de sentimento) — coberto inline no Dashboard
  - `GetPlatformBreakdownUseCase` (distribuicao por rede) — coberto inline no Dashboard
  - `GenerateListeningReportUseCase`
  - `ExportListeningReportUseCase` (PDF, CSV)
- [x] DTOs para input/output de cada use case

### 9.3 Infrastructure Layer

- [x] Migrations: `listening_queries`, `mentions` (particionada por mes), `listening_alerts`, `listening_alert_notifications`, `listening_reports`
- [x] Adapters de listening (implementam `SocialListeningInterface`):
  - `InstagramListeningAdapter` (Instagram Graph API — hashtag search, mention endpoint)
  - `TikTokListeningAdapter` (TikTok Research API — keyword search)
  - `YouTubeListeningAdapter` (YouTube Data API — search endpoint)
- [x] `SocialListeningAdapterFactory` (resolve adapter por provider)
- [x] Reutilizacao do `SentimentAnalysisService` do Engagement context
- [x] Jobs:
  - `FetchMentionsJob` (captura mencoes por query, com deduplicacao por external_id)
  - `AnalyzeMentionSentimentJob` (analise de sentimento via IA)
  - `EvaluateListeningAlertsJob` (verifica condicoes de alerta)
  - `GenerateListeningReportJob`
  - `CleanupOldMentionsJob` (retention policy)
- [x] Controllers: `ListeningQueryController`, `MentionController`, `ListeningAlertController`, `ListeningDashboardController`, `ListeningReportController`
- [x] Scheduler:
  - Captura de mencoes: a cada 15 min para queries ativas
  - Avaliacao de alertas: a cada 5 min
  - Relatorio diario: 1x/dia (06:00 UTC)
  - Cleanup: mencoes > retention period do plano

### 9.4 Testes

- [x] Unit: ListeningQuery entity, QueryType enum, AlertCondition VO, AlertEvaluationService
- [x] Unit: Mention entity, sentiment assignment, deduplication logic
- [x] Unit: Todos os Use Cases (com mocks de repository e adapters)
- [ ] Integration: Listening adapters (mock de APIs de busca)
- [ ] Integration: Mention partitioning (inserir e consultar em particoes diferentes)
- [x] Integration: Repositories (CRUD + queries especificas)
- [x] Feature: CRUD de queries de listening
- [x] Feature: Listagem de mencoes com filtros
- [x] Feature: CRUD de alertas e avaliacao de condicoes
- [x] Feature: Dashboard de listening (total, sentimento, tendencias)
- [x] Feature: Geracao e exportacao de relatorios
- [x] Feature: Isolamento por organization_id

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

- [x] `PostingTimeRecommendation` entity (heatmap, top/worst slots, confidence)
- [x] `BrandSafetyCheck` entity (status, score, checks por categoria)
- [x] `BrandSafetyRule` entity (regras customizaveis por org)
- [x] Value Objects: `PredictionScore`, `TimeSlotScore`, `ConfidenceLevel`, `SafetyStatus`, `SafetyRuleType`, `RuleSeverity`, `SafetyCategory`, `TopSlot`, `SafetyCheckResult`
- [x] Domain Events: `PostingTimesUpdated`, `BrandSafetyChecked`, `BrandSafetyBlocked`
- [x] Repository interfaces
- [x] Exceptions: `InvalidTimeSlotException`, `InvalidPredictionScoreException`, `InvalidSafetyRuleConfigException`, `RecommendationExpiredException`, `SafetyCheckAlreadyCompletedException`

### 10.2 Application Layer

- [x] Use Cases Best Time:
  - `GetBestTimesUseCase`
  - `GetBestTimesHeatmapUseCase`
  - `GetBestTimesByProviderUseCase` (coberto por parâmetro `provider` no `GetBestTimesUseCase`)
  - `RecalculateBestTimesUseCase`
- [x] Use Cases Brand Safety:
  - `RunSafetyCheckUseCase`
  - `GetSafetyChecksUseCase`
  - `CreateSafetyRuleUseCase`
  - `UpdateSafetyRuleUseCase`
  - `DeleteSafetyRuleUseCase`
  - `ListSafetyRulesUseCase`
- [x] DTOs para input/output de cada use case
- [x] Contracts: `BrandSafetyAnalyzerInterface`
- [x] Exceptions: `RecommendationNotFoundException`, `SafetyCheckNotFoundException`, `SafetyRuleNotFoundException`, `InsufficientDataException`

### 10.3 Infrastructure Layer

- [x] Migrations: `posting_time_recommendations`, `brand_safety_checks`, `brand_safety_rules`
- [x] Models: `PostingTimeRecommendationModel`, `BrandSafetyCheckModel`, `BrandSafetyRuleModel`
- [x] Repositories: `EloquentPostingTimeRecommendationRepository`, `EloquentBrandSafetyCheckRepository`, `EloquentBrandSafetyRuleRepository`
- [x] Jobs: `CalculateBestPostingTimesJob`, `RunBrandSafetyCheckJob`
- [x] Controllers: `BestTimesController`, `BrandSafetyController`, `BrandSafetyRuleController`
- [x] Requests: `GetBestTimesRequest`, `RecalculateBestTimesRequest`, `RunSafetyCheckRequest`, `CreateSafetyRuleRequest`, `UpdateSafetyRuleRequest`, `ListSafetyRulesRequest`
- [x] Resources: `BestTimesResource`, `BestTimesHeatmapResource`, `SafetyCheckResource`, `SafetyRuleResource`
- [x] Services: `StubBrandSafetyAnalyzer` (implements `BrandSafetyAnalyzerInterface`)
- [x] Provider: `AIIntelligenceServiceProvider`
- [x] Routes: `routes/api/v1/ai-intelligence.php` (9 endpoints)
- [x] Scheduler: TODO comment para recalculo semanal (requer `RecalculateAllBestTimesJob`)
- [ ] Integracao com `ProcessScheduledPostJob` (consultar safety check antes de publicar)

### 10.4 Testes

- [x] Unit: PostingTimeRecommendation entity, ConfidenceLevel, TimeSlotScore
- [x] Unit: BrandSafetyCheck entity, SafetyStatus transitions
- [x] Unit: BrandSafetyRule entity, matches(), validation
- [x] Unit: Value Objects (PredictionScore, TopSlot, SafetyCheckResult, SafetyStatus)
- [x] Unit: Todos os Use Cases (9 use cases)
- [ ] Integration: Calculo de best times a partir de content_metric_snapshots
- [ ] Integration: Safety check via LLM (mock de Prism)
- [x] Feature: Endpoints de best times (heatmap, top slots, recalculate)
- [x] Feature: Safety check flow (create check, list checks)
- [x] Feature: CRUD de safety rules
- [x] Feature: Isolamento por organization_id
- [x] Architecture: Regras AI Intelligence (9 novas regras)

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

- [x] `CalendarSuggestion` entity (sugestoes, based_on, status, accepted_items)
- [x] Value Objects: `SuggestionStatus`, `CalendarItem`
- [x] Domain Events: `CalendarSuggestionGenerated`, `CalendarItemsAccepted`
- [x] Expansao de `GenerationType` enum com `cross_network_adaptation` e `calendar_planning`
- [x] Exceptions: `CalendarSuggestionExpiredException`, `InvalidSuggestionStatusTransitionException`, `InvalidCalendarItemException`
- [x] Repository interfaces: `CalendarSuggestionRepositoryInterface`

### 11.2 Application Layer

- [x] Use Cases Cross-Network:
  - `AdaptContentUseCase` (adapta conteudo entre redes via LLM)
- [x] Use Cases Calendar:
  - `GenerateCalendarSuggestionsUseCase`
  - `ListCalendarSuggestionsUseCase`
  - `GetCalendarSuggestionUseCase`
  - `AcceptCalendarItemsUseCase`

### 11.3 Infrastructure Layer

- [x] Migration: `calendar_suggestions`
- [x] Alteracao de ENUM: `generation_type` += `cross_network_adaptation`
- [x] Jobs: `GenerateCalendarSuggestionsJob`
- [x] Prompts especializados para adaptacao cross-network (respeitar limites/convencoes por rede)
- [x] Controllers: `ContentAdaptationController`, `CalendarSuggestionController`

### 11.4 Testes

- [x] Unit: CalendarSuggestion entity, SuggestionStatus transitions
- [x] Unit: Todos os Use Cases
- [x] Integration: Adaptacao cross-network via LLM (mock)
- [x] Integration: Geracao de calendario via LLM (mock)
- [x] Feature: Endpoint de adapt-content (request → adaptacoes por rede)
- [x] Feature: CRUD de calendar suggestions (generate, list, accept)
- [x] Feature: Isolamento por organization_id

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

- [x] `ContentProfile` entity (top_themes, engagement_patterns, centroid_embedding)
- [x] `PerformancePrediction` entity (score, breakdown, recommendations)
- [x] Value Objects: `EngagementPattern`, `ContentFingerprint`, `PredictionBreakdown`
- [x] Domain Events: `EmbeddingGenerated`, `ContentProfileGenerated`, `PredictionCalculated`
- [x] Contracts: `EmbeddingGeneratorInterface`, `SimilaritySearchInterface`
- [x] Repository interfaces

### 12.2 Application Layer

- [x] Use Cases Embedding Pipeline:
  - `GenerateEmbeddingUseCase`
  - `BackfillEmbeddingsUseCase`
- [x] Use Cases Content DNA:
  - `GenerateContentProfileUseCase`
  - `GetContentProfileUseCase`
  - `GetContentThemesUseCase`
  - `GetContentRecommendationsUseCase`
- [x] Use Cases Prediction:
  - `PredictPerformanceUseCase`
  - `GetPredictionsUseCase`

### 12.3 Infrastructure Layer

- [x] Migrations: `embedding_jobs`, `content_profiles`, `performance_predictions`
- [x] `StubEmbeddingGenerator` (implementa `EmbeddingGeneratorInterface`) — stub para testes; OpenAI real em sprint futuro
- [x] `StubSimilaritySearch` (implementa `SimilaritySearchInterface`) — stub para testes; PgVector real em sprint futuro
- [x] Models: `EmbeddingJobModel`, `ContentProfileModel`, `PerformancePredictionModel`
- [x] Repositories: `EloquentContentProfileRepository`, `EloquentPerformancePredictionRepository`
- [x] Jobs: `GenerateContentProfileJob`
- [x] Controllers: `ContentProfileController`, `PerformancePredictionController`
- [x] Requests: `GenerateContentProfileRequest`, `GetContentProfileRequest`, `GetContentThemesRequest`, `GetContentRecommendationsRequest`, `PredictPerformanceRequest`
- [x] Resources: `ContentProfileResource`, `ContentThemesResource`, `PredictionResource`, `PredictionSummaryResource`
- [x] ServiceProvider: bindings de repos + stubs
- [x] Routes: 6 rotas (content-profile CRUD + predict-performance + predictions)
- [x] Scheduler: TODO para recalculacao semanal de profiles

> **Nota:** Listeners de `ContentCreated`/`ContentUpdated`, embedding-specific jobs (backfill, per-content, per-comment), e integracao real com OpenAI/PgVector serao implementados quando o pipeline de embeddings for ativado em producao.

### 12.4 Testes

- [x] Unit Domain: ContentProfile entity (create, reconstitute, complete, expire, isExpired)
- [x] Unit Domain: PerformancePrediction entity (create, reconstitute, events)
- [x] Unit Domain: EngagementPattern, ContentFingerprint, PredictionBreakdown, PredictionRecommendation VOs
- [x] Unit Domain: ProfileStatus enum (transitions, isFinal)
- [x] Unit Application: Todos os 8 Use Cases (GenerateEmbedding, Backfill, GenerateContentProfile, GetContentProfile, GetContentThemes, GetContentRecommendations, PredictPerformance, GetPredictions)
- [x] Feature: Content DNA (generate profile 202, get profile, get themes, recommendations)
- [x] Feature: Performance Prediction (predict → score + breakdown, get predictions list)
- [x] Feature: Prediction com dados insuficientes (InsufficientDataException → 422)
- [x] Feature: Isolamento por organization_id em content profiles e predictions
- [x] Feature: Autenticacao 401 em todos os endpoints

> **Nota:** Testes de integracao para OpenAI Embedding Service e PgVector similarity search serao adicionados junto com as implementacoes reais.

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

- [x] `AudienceInsight` entity (insight_type, insight_data, confidence_score)
- [x] `ContentGapAnalysis` entity (our_topics, competitor_topics, gaps, opportunities)
- [x] Value Objects: `InsightType`, `GapAnalysisStatus`
- [x] Domain Events: `AudienceInsightsRefreshed`, `ContentGapsIdentified`
- [x] Domain Exceptions: `AudienceInsightExpiredException`, `GapAnalysisExpiredException`, `InvalidGapAnalysisStatusTransitionException`
- [x] Repository interfaces

### 13.2 Application Layer

- [x] Use Cases Feedback Loop:
  - `ListAudienceInsightsUseCase` (combina Get + GetByType com filtro opcional)
  - `RefreshAudienceInsightsUseCase` (validacao, job dispatch no controller)
- [x] Use Cases Gap Analysis:
  - `GenerateGapAnalysisUseCase` (cria entity Generating + eventos)
  - `ListGapAnalysesUseCase` (cursor-based)
  - `GetGapAnalysisUseCase`
  - `GetGapAnalysisOpportunitiesUseCase`
- [x] DTOs: 6 Inputs + 5 Outputs + 2 Results
- [x] Contracts: `AudienceInsightAnalyzerInterface`, `ContentGapAnalyzerInterface`
- [x] Exceptions: `AudienceInsightNotFoundException`, `GapAnalysisNotFoundException`
- [ ] Expansao dos Use Cases de geracao (RF-030 a RF-033) para injetar contexto de audiencia

### 13.3 Infrastructure Layer

- [x] Migrations: `audience_insights`, `ai_generation_context`, `content_gap_analyses` + RLS
- [x] Models: `AudienceInsightModel`, `AIGenerationContextModel`, `ContentGapAnalysisModel`
- [x] Repositories: `EloquentAudienceInsightRepository`, `EloquentContentGapAnalysisRepository`
- [x] Jobs: `RefreshAudienceInsightsJob`, `UpdateAIGenerationContextJob`, `GenerateContentGapAnalysisJob`
- [x] Stubs: `StubAudienceInsightAnalyzer`, `StubContentGapAnalyzer`
- [x] Controllers: `AudienceInsightsController`, `ContentGapAnalysisController`
- [x] Requests: `ListAudienceInsightsRequest`, `GenerateGapAnalysisRequest`, `ListGapAnalysesRequest`
- [x] Resources: `AudienceInsightResource`, `GapAnalysisListResource`, `GapAnalysisResource`, `GapAnalysisOpportunitiesResource`
- [x] Routes: 6 endpoints (audience-insights + gap-analysis)
- [x] Service Provider: bindings para repos e stubs
- [x] Scheduler: TODOs para refresh semanal de insights e gap analysis mensal
- [ ] Integracao com prompts de geracao (injecao de audience context) — pendente Sprint 14

### 13.4 Testes

- [x] Unit: AudienceInsight entity (8 testes), ContentGapAnalysis entity (11 testes)
- [x] Unit: InsightType (3 testes), GapAnalysisStatus (8 testes)
- [x] Unit: ListAudienceInsightsUseCase (4 testes)
- [x] Unit: GenerateGapAnalysisUseCase (3 testes)
- [x] Unit: GetGapAnalysisUseCase (3 testes)
- [x] Unit: GetGapAnalysisOpportunitiesUseCase (3 testes)
- [x] Feature: Audience insights — get, filter by type, refresh, 401, isolation, expired (7 testes)
- [x] Feature: Gap analysis — generate, list, show, opportunities, 422 errors, 401, isolation (10 testes)
- [ ] Integration: Aggregacao de insights de comentarios via LLM (mock) — pendente implementacao real dos jobs
- [ ] Integration: Gap analysis com mencoes de Social Listening (mock) — pendente implementacao real dos jobs
- [ ] Integration: Injecao de contexto nos prompts de geracao — pendente Sprint 14
- [ ] Feature: Campo `audience_context_used` nas respostas de geracao — pendente Sprint 14
- [ ] Feature: Desativar audience context via AI settings — pendente Sprint 14

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

- [x] `GenerationFeedback` entity (action, original_output, edited_output, diff_summary)
- [x] `PromptTemplate` aggregate root (system_prompt, user_prompt_template, performance_score, counters)
- [x] `PromptExperiment` entity (A/B test entre 2 templates, z-test, confidence_level)
- [x] `PredictionValidation` entity (predicted_score vs actual_normalized_score, accuracy)
- [x] `OrgStyleProfile` aggregate root (tone, length, vocabulary, structure, hashtag preferences)
- [x] Value Objects: `FeedbackAction`, `DiffSummary`, `PerformanceScore`, `ExperimentStatus`, `StylePreferences`, `PredictionAccuracy`
- [x] Domain Events: `GenerationFeedbackRecorded`, `GenerationEdited`, `PromptTemplateCreated`, `PromptPerformanceCalculated`, `PromptExperimentStarted`, `PromptExperimentCompleted`, `PredictionValidated`, `OrgStyleProfileGenerated`, `LearningContextUpdated`
- [x] Contracts: `PromptTemplateResolverInterface`, `RAGContextProviderInterface`, `StyleProfileAnalyzerInterface`, `PredictionValidatorInterface` — implementados em 14.2 (Application Layer contracts)
- [x] Repository interfaces (5: GenerationFeedback, PromptTemplate, PromptExperiment, PredictionValidation, OrgStyleProfile)
- [x] Domain exceptions: `InvalidExperimentStatusTransitionException`, `InvalidFeedbackException`, `PredictionNotYetPublishedException`, `OrgStyleProfileExpiredException`, `InsufficientEditDataException`

### 14.2 Application Layer

- [x] Use Cases Feedback (Nivel 1):
  - `RecordGenerationFeedbackUseCase`
- [x] Use Cases RAG (Nivel 2):
  - `RetrieveSimilarContentUseCase`
- [x] Use Cases Prompt Optimization (Nivel 3):
  - `ResolvePromptTemplateUseCase`
  - `CreatePromptTemplateUseCase`
  - `CreatePromptExperimentUseCase`
  - `EvaluateExperimentUseCase`
  - `CalculatePromptPerformanceUseCase`
- [x] Use Cases Prediction Accuracy (Nivel 4):
  - `ValidatePredictionUseCase`
  - `GetPredictionAccuracyUseCase`
- [x] Use Cases Style Learning (Nivel 5):
  - `GenerateStyleProfileUseCase`
  - `UpdateLearningContextUseCase`
- [ ] Expansao dos Use Cases de geracao (Sprint 3) para integrar RAG + style + template resolution — adiado para 14.3 (requer implementacao das contracts na Infrastructure)
- [x] DTOs para input/output de cada use case (21 DTOs: 11 inputs, 7 outputs, 3 results internos)
- [x] Contracts: `PromptTemplateResolverInterface`, `RAGContextProviderInterface`, `StyleProfileAnalyzerInterface`, `PredictionValidatorInterface`

### 14.3 Infrastructure Layer

- [x] Migrations: `generation_feedback`, `prompt_templates`, `prompt_experiments`, `prediction_validations`, `org_style_profiles`
- [x] ALTER TABLE `ai_generations`: add `prompt_template_id`, `experiment_id`, `rag_context_used`, `style_context_used`
- [ ] Seeds: prompt templates globais default por generation_type — adiado para 14.4 (depende de feature tests)
- [x] Eloquent Models: `GenerationFeedbackModel`, `PromptTemplateModel`, `PromptExperimentModel`, `PredictionValidationModel`, `OrgStyleProfileModel`
- [x] Repository Implementations: Eloquent repos para os 5 novos aggregates
- [x] Service Stubs: `StubPromptTemplateResolver`, `StubRAGContextProvider`, `StubStyleProfileAnalyzer`, `StubPredictionValidator`
- [x] Resources: `GenerationFeedbackResource`, `PromptTemplateResource`, `PromptExperimentResource`, `PredictionValidationResource`, `PredictionAccuracyResource`, `StyleProfileResource`
- [x] Form Requests: `RecordGenerationFeedbackRequest`, `CreatePromptTemplateRequest`, `CreatePromptExperimentRequest`, `ValidatePredictionRequest`, `GenerateStyleProfileRequest`
- [x] Jobs:
  - `TrackGenerationFeedbackJob` (N1 — a cada feedback)
  - `CalculateDiffSummaryJob` (N1 — a cada edicao)
  - `RetrieveSimilarContentJob` (N2 — pre-geracao)
  - `CalculatePromptPerformanceJob` (N3 — semanal)
  - `EvaluatePromptExperimentJob` (N3 — pos-feedback)
  - `ValidatePredictionAccuracyJob` (N4 — 7d pos-publicacao)
  - `GenerateOrgStyleProfileJob` (N5 — semanal, min 10 edits)
  - `UpdateLearningContextJob` (N2+N5 — pos-atualizacao)
  - `CleanupExpiredLearningDataJob` (todos — semanal)
- [ ] Async Listeners: `PostPublished` → schedule validation, `MetricsSynced` → validate prediction, `PromptExperimentCompleted` → activate winner, `OrgStyleProfileGenerated` → update context — adiado para 14.4 (requer feature tests para validar integração)
- [x] Controllers: `GenerationFeedbackController`, `PromptTemplateController`, `PromptExperimentController`, `PredictionAccuracyController`, `StyleProfileController`
- [x] Scheduler: performance recalc semanal, cleanup semanal
- [x] Service Providers: `ContentAIServiceProvider`, `AIIntelligenceServiceProvider` atualizados com novos bindings
- [x] Routes: endpoints em `ai.php` (feedback, templates, experiments) e `ai-intelligence.php` (prediction validation, accuracy, style profile)

### 14.4 Testes

- [x] Refatoracao: `CalculateDiffSummaryJob` → `CalculateDiffSummaryUseCase` + DTO (arch violation fix)
- [x] Arch tests: regras ContentAI + AIIntelligence faltantes adicionadas (129 arch tests)
- [x] Unit: FeedbackAction VO (4 testes), DiffSummary VO (10 testes), ExperimentStatus VO (5 testes), PerformanceScore VO (10 testes)
- [x] Unit: PredictionAccuracy VO (10 testes), StylePreferences VO (4 testes)
- [x] Unit: GenerationFeedback entity (8 testes), PromptTemplate entity (11 testes), PromptExperiment entity (16 testes)
- [x] Unit: PredictionValidation entity (6 testes), OrgStyleProfile entity (11 testes)
- [x] Unit: 12 Use Cases com mocks de repository (31 testes)
- [x] Feature: Feedback endpoint accept/edit/reject (5 testes)
- [x] Feature: Prompt templates CRUD (3 testes)
- [x] Feature: A/B experiment lifecycle create + evaluate (4 testes)
- [x] Feature: Prediction validation + accuracy (4 testes)
- [x] Feature: Style profile generation (3 testes)
- [ ] Integration: RAG via pgvector (cosine similarity + engagement filter) — adiado para sprint dedicado
- [ ] Integration: Style profile generation via LLM (mock de Prism) — adiado para sprint dedicado
- [ ] Integration: Diff calculation (Levenshtein) — adiado para sprint dedicado
- [ ] Feature: Geracao enriquecida (template + RAG + style + audience context) — adiado (requer expansao use cases)
- [ ] Feature: Feature gates por plano (RAG: Creator+, Style: Professional+, A/B: Agency) — adiado para Sprint 15+
- [ ] Feature: Graceful degradation (cada nivel falha silenciosamente) — adiado para Sprint 15+
- [ ] Feature: Isolamento por organization_id em todos os niveis — coberto parcialmente nos feature tests

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

### 15.1 Domain Layer ✅

- [x] `CrmProvider` enum (hubspot, rdstation, pipedrive, salesforce, activecampaign) + `label()`, `supportsDeals()`, `supportsActivities()`
- [x] `CrmConnectionStatus` enum (connected, token_expired, revoked, error) + state machine `canTransitionTo()`
- [x] `CrmSyncDirection` enum (outbound, inbound), `CrmEntityType` enum (contact, deal, activity), `CrmSyncStatus` enum (success, failed, partial)
- [x] `CrmConnection` entity — aggregate root com state machine, OAuth tokens, domain events
- [x] `CrmSyncLog` entity — sync audit trail (sem domain events, padrao AutomationExecution)
- [x] `CrmFieldMapping` value object (smm_field, crm_field, transform, position) — padrao RuleCondition
- [x] `CrmSyncResult` value object — private ctor + named factories (success/failed/partial)
- [x] Domain Events (8): `CrmConnected`, `CrmDisconnected`, `CrmContactSynced`, `CrmDealCreated`, `CrmActivityLogged`, `CrmSyncFailed`, `CrmTokenExpired`, `CrmFieldMappingUpdated`
- [x] Contracts: `CrmConnectorInterface` (getAuthorizationUrl, authenticate, refreshToken, revokeToken, createContact, updateContact, createDeal, logActivity, searchContacts, getConnectionStatus)
- [x] Repository interfaces: `CrmConnectionRepositoryInterface`, `CrmFieldMappingRepositoryInterface`, `CrmSyncLogRepositoryInterface`
- [x] Exceptions (3): `CrmConnectionAlreadyExistsException`, `InvalidCrmConnectionStatusTransitionException`, `CrmTokenExpiredException`
- [x] Arch tests: 130 passed (5 enums, 2 VOs, 3 repos, 1 contract rule)

### 15.2 Application Layer ✅

- [x] Use Cases CRM Connection (7):
  - `ConnectCrmUseCase` (valida unicidade, gera OAuth state, retorna authorizationUrl)
  - `HandleCrmCallbackUseCase` (valida state, authenticate, cria CrmConnection, dispatch events)
  - `DisconnectCrmUseCase` (revoke best-effort + disconnect entity + dispatch events)
  - `TestCrmConnectionUseCase` (verifica status via CRM API, markError se unhealthy)
  - `ListCrmConnectionsUseCase` (lista conexoes da org)
  - `GetCrmConnectionStatusUseCase` (retorna conexao unica com org check)
  - `RefreshCrmTokenUseCase` (renova tokens, markTokenExpired em falha)
- [x] Use Cases CRM Sync — Outbound (3):
  - `SyncContactToCrmUseCase` (search+create/update, field mappings, sync log, events)
  - `CreateCrmDealUseCase` (cria deal, sync log, events)
  - `LogCrmActivityUseCase` (registra atividade, sync log, events)
- [x] Use Cases CRM Sync — Inbound (1):
  - `ProcessCrmWebhookUseCase` (log inbound webhook, detecta entityType por eventType)
- [x] Use Cases Field Mapping (3):
  - `GetCrmFieldMappingsUseCase` (retorna custom ou defaults do provider)
  - `UpdateCrmFieldMappingsUseCase` (bulk replace, dispatch CrmFieldMappingUpdated)
  - `ResetCrmFieldMappingsToDefaultUseCase` (reset + dispatch event)
- [x] Use Cases Monitoring (1):
  - `ListCrmSyncLogsUseCase` (paginacao cursor-based com org check)
- [x] DTOs (12): ConnectCrmInput/Output, HandleCrmCallbackInput, CrmConnectionOutput, SyncContactToCrmInput, CreateCrmDealInput, LogCrmActivityInput, CrmSyncResultOutput, UpdateCrmFieldMappingsInput, CrmFieldMappingOutput, ListCrmSyncLogsInput, CrmSyncLogOutput
- [x] Contracts (2): `CrmConnectorFactoryInterface`, `CrmOAuthStateServiceInterface`
- [x] Exceptions (2): `CrmConnectionNotFoundException`, `CrmOAuthStateInvalidException`
- [x] Listeners (implemented in 15.3):
  - `CommentCaptured` → `SyncCommentAuthorToCrm` → `SyncContactToCrmJob` (se CRM conectado)
  - `CrmTokenExpired` → `ScheduleCrmTokenRefresh` → `RefreshCrmTokenJob`
- [ ] Listeners (deferred — requires additional domain events):
  - `AutomationTriggered` (lead) → `CreateCrmDealJob` (se CRM conectado)
  - `PostPublished` → `LogCrmActivityJob` (se CRM conectado)
- [ ] Use Cases Maintenance (deferred — requires job dispatch):
  - `ForceCrmSyncUseCase` (sincronizacao manual)
  - `BackfillCrmContactsUseCase` (backfill apos conexao)

### 15.3 Infrastructure Layer ✅

- [x] Migration: `crm_connections` table (UUID PK, org FK, provider, tokens, status, settings)
- [x] Migration: `crm_field_mappings` table (UUID PK, connection FK, smm_field, crm_field, transform, position)
- [x] Migration: `crm_sync_logs` table (UUID PK, org FK, connection FK, direction, entity_type, action, status, payload)
- [x] Eloquent Models: CrmConnectionModel, CrmFieldMappingModel, CrmSyncLogModel
- [x] Repository implementations (Eloquent): EloquentCrmConnectionRepository, EloquentCrmFieldMappingRepository, EloquentCrmSyncLogRepository
- [x] `StubCrmConnector` — stub para todos os providers (real connectors deferred)
- [x] `CrmConnectorFactory` — resolve connector por provider (retorna StubCrmConnector)
- [x] `CrmOAuthStateService` — Redis-based single-use OAuth state tokens com TTL
- [x] Jobs (5):
  - `SyncContactToCrmJob` (queue: default, retry: 3, backoff: 60s/300s/900s)
  - `CreateCrmDealJob` (queue: default, retry: 3, backoff: 60s/300s/900s)
  - `LogCrmActivityJob` (queue: low, retry: 3, backoff: 60s/300s/900s)
  - `RefreshCrmTokenJob` (queue: high, retry: 2, backoff: 60s/300s)
  - `ProcessCrmWebhookJob` (queue: default, retry: 3, backoff: 60s/300s/900s)
- [x] Controllers: CrmConnectionController (6 actions), CrmFieldMappingController (3 actions), CrmSyncController (1 action)
- [x] Requests: ConnectCrmRequest, HandleCrmCallbackRequest, UpdateCrmFieldMappingsRequest
- [x] Resources: CrmConnectionResource, CrmFieldMappingResource, CrmSyncLogResource
- [x] Routes: 10 endpoints sob `/api/v1/crm/` com middleware auth.jwt, org.context, tenant.rls
- [x] Listeners: SyncCommentAuthorToCrm (CommentCaptured), ScheduleCrmTokenRefresh (CrmTokenExpired)
- [x] Service Provider bindings: 5 interfaces (3 repos + factory + OAuth state) + 2 event listeners
- [x] Arch tests: 130 passed, full suite: 2056 passed
- [ ] Real CRM connectors (deferred — HubSpot, RD Station, Pipedrive HTTP implementations)
- [ ] Feature gate middleware: Professional+ para CRM connectors
- [ ] BackfillCrmContactsJob (deferred — requires BackfillCrmContactsUseCase)

### 15.4 Testes ✅

- [x] Unit tests: CrmConnection entity (18 tests), CrmSyncLog entity (5 tests)
- [x] Unit tests: CrmFieldMapping VO (5 tests), CrmSyncResult VO (4 tests)
- [x] Unit tests: CrmProvider enum (5 tests), CrmConnectionStatus enum (5 tests)
- [x] Unit tests: Todos os 15 Use Cases com mock de dependencias (45 tests)
  - ConnectCrmUseCase, HandleCrmCallbackUseCase, DisconnectCrmUseCase, TestCrmConnectionUseCase
  - ListCrmConnectionsUseCase, GetCrmConnectionStatusUseCase, RefreshCrmTokenUseCase
  - SyncContactToCrmUseCase, CreateCrmDealUseCase, LogCrmActivityUseCase, ProcessCrmWebhookUseCase
  - GetCrmFieldMappingsUseCase, UpdateCrmFieldMappingsUseCase, ResetCrmFieldMappingsToDefaultUseCase
  - ListCrmSyncLogsUseCase
- [x] Feature tests: CrmConnection endpoints (9 tests — connect, list, show, test, delete, duplicata, validacao, 401)
- [x] Feature tests: CrmFieldMapping endpoints (6 tests — list defaults, update, validate, reset, 422)
- [x] Feature tests: CrmSyncLog endpoints (5 tests — list, empty, pagination, 422, 401)
- [x] Architecture tests: 130 passed (CRM classes incluidos nos rules existentes)
- [x] Full suite: 2165 passed (6461 assertions)
- [ ] Integration tests: HubSpot/RD Station/Pipedrive connectors (deferred — requires real HTTP implementations)
- [ ] Feature tests: Feature gate (deferred — middleware not yet implemented)
- [ ] Feature tests: Fluxo completo outbound/inbound (deferred — requires listener chain + real connectors)

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

### 16.1 Domain Layer ✅

- [x] Nenhuma alteracao — infraestrutura ja existe do Sprint 15.

### 16.2 Application Layer ✅

- [x] Nenhuma alteracao significativa — mesmos Use Cases reutilizados.
- [x] `ConnectCrmWithApiKeyInput` DTO — input para conexao CRM via API Key (ActiveCampaign)
- [x] `ConnectCrmWithApiKeyUseCase` — valida API key via `getConnectionStatus()`, cria conexao sem OAuth flow
- [x] `CrmApiKeyInvalidException` — exception para API key invalida

### 16.3 Infrastructure Layer ✅

- [x] `config/crm.php` — config para Salesforce (OAuth, API version, scopes) e ActiveCampaign (API URL, rate limit)
- [x] **Salesforce Connector** (`SalesforceConnector.php`):
  - `getAuthorizationUrl()` funcional (login.salesforce.com/services/oauth2/authorize)
  - `getConnectionStatus()` funcional (stub true)
  - Estrutura completa para REST API v58.0: SObject Contact, Opportunity, Task, SOQL search
  - Default field mappings com sufixo `__c` (Social_Media_Id__c, Social_Network__c, Sentiment__c)
  - Metodos HTTP pendentes de integracao (RuntimeException pattern, como InstagramAuthenticator)
- [x] **ActiveCampaign Connector** (`ActiveCampaignConnector.php`):
  - API Key auth (OAuth methods lancam RuntimeException)
  - `getConnectionStatus()` funcional (stub true)
  - `logActivity()` lanca RuntimeException (AC nao suporta activities)
  - Estrutura completa para API v3: contacts, deals, search
  - Default field mappings com fieldValues.* para custom fields
  - Metodos HTTP pendentes de integracao (RuntimeException pattern)
- [x] `CrmConnectorFactory` atualizado: Salesforce → SalesforceConnector, ActiveCampaign → ActiveCampaignConnector
- [x] `ConnectCrmWithApiKeyRequest` — FormRequest para conexao API Key
- [x] `CrmConnectionController::connectWithApiKey()` — novo endpoint
- [x] Rota `POST crm/connect-api-key` adicionada
- [ ] Feature gate: Salesforce e ActiveCampaign somente Agency (sera implementado com Billing integration)

### 16.4 Testes ✅

- [x] Integration tests: `CrmConnectorFactoryTest` — factory resolve SF/AC/stub, SF authorization URL, AC RuntimeException methods (14 tests)
- [x] Integration tests: `SalesforceConnectorTest` — authorization URL params, getConnectionStatus, RuntimeException para metodos HTTP (10 tests)
- [x] Feature tests: `CrmSalesforceFlowTest` — OAuth connect, list/show/test/delete, field mappings com `__c`, duplicate prevention (7 tests)
- [x] Feature tests: `CrmActiveCampaignFlowTest` — connect-api-key 201, validacao, list/show/test/delete, field mappings com `fieldValues.*`, duplicate prevention, 401 (12 tests)
- [x] Unit tests: `ConnectCrmWithApiKeyUseCaseTest` — creates connection, throws on invalid key, throws on duplicate (3 tests)
- [ ] Feature gate: Salesforce e ActiveCampaign somente Agency (sera implementado com Billing integration)

### 16.5 CRM Intelligence (ADR-017 Nivel 6) ✅

#### Domain Layer ✅

- [x] `AttributionType` enum (direct_engagement, lead_capture, deal_closed) com `hasMonetaryValue()`, `label()`
- [x] `CrmConversionAttribution` entity (AI Intelligence BC) com `create()`, `reconstitute()`, `hasMonetaryValue()`
- [x] `CrmConversionAttributed` domain event
- [x] `CrmAIContextEnriched` domain event
- [x] `CrmIntelligenceProviderInterface` — `getConversionBoost()`, `getConversionSummary()`, `getAudienceSegments()`
- [x] `CrmConversionAttributionRepositoryInterface` — CRUD + contagem por tipo + soma de valores

#### Application Layer ✅

- [x] `AttributeCrmConversionInput` DTO
- [x] `CrmConversionAttributionOutput` DTO com `fromEntity()` factory
- [x] `AttributeCrmConversionUseCase` — valida conexao CRM, cria attribution, dispatcha events
- [x] `EnrichAIContextFromCrmUseCase` — agrega dados de conversao e segmentos, dispatcha CrmAIContextEnriched

#### Infrastructure Layer ✅

- [x] Migration: `crm_conversion_attributions` table (indexes: org+type, content+type, org+attributed_at)
- [x] Migration: `ALTER TABLE prediction_validations ADD COLUMN conversion_count, conversion_value`
- [x] `CrmConversionAttributionModel` — Eloquent model
- [x] `EloquentCrmConversionAttributionRepository` — implementacao com cursor pagination
- [x] `StubCrmIntelligenceProvider` — retorna 0.0 boost, arrays vazios (substituido por implementacao real quando RAG for expandido)
- [x] `AttributeCrmConversionJob` — queue ai-intelligence, triggered por events
- [x] `EnrichAIContextFromCrmJob` — queue ai-intelligence, batch semanal
- [ ] RAG boost logic real: sera implementado quando RAGContextProvider for substituido pelo real
- [ ] CRM Intelligence feature gate: sera implementado com Billing integration

#### Listeners ✅

- [x] `AttributeCrmConversionOnDealCreated` — CrmDealCreated → dispatch AttributeCrmConversionJob
- [x] `AttributeCrmConversionOnContactSynced` — CrmContactSynced → dispatch AttributeCrmConversionJob

#### Service Provider ✅

- [x] `AIIntelligenceServiceProvider` — bindings para CrmConversionAttributionRepositoryInterface e CrmIntelligenceProviderInterface
- [x] Event listeners: CrmDealCreated → AttributeCrmConversionOnDealCreated, CrmContactSynced → AttributeCrmConversionOnContactSynced

#### Architecture Tests ✅

- [x] `AttributionType` adicionado a lista de enums
- [x] `CrmConversionAttributionRepositoryInterface` adicionado a lista de repository interfaces
- [x] `ai intelligence domain contracts are interfaces` test adicionado

#### Testes ✅

- [x] Unit tests: `AttributionTypeTest` — 3 cases, hasMonetaryValue, labels, from string (5 tests)
- [x] Unit tests: `CrmConversionAttributionTest` — create com event, reconstitute sem events, hasMonetaryValue cenarios (6 tests)
- [x] Unit tests: `AttributeCrmConversionUseCaseTest` — creates attribution, throws on missing connection, lead capture sem valor (3 tests)
- [x] Unit tests: `EnrichAIContextFromCrmUseCaseTest` — enriches context com data, dispatcha com empty types (2 tests)

### Entregaveis Sprint 16 ✅

- 2 novos conectores nativos: Salesforce (OAuth), ActiveCampaign (API Key)
- Salesforce: `getAuthorizationUrl()` funcional, estrutura OAuth 2.0, default field mappings com `__c`
- ActiveCampaign: API Key auth, `connect-api-key` endpoint, default field mappings com `fieldValues.*`
- CrmConnectorFactory resolve 5 providers: HubSpot, RD Station, Pipedrive (stub) + Salesforce, ActiveCampaign (real)
- **CRM Intelligence (N6):** `CrmConversionAttribution` entity no AI Intelligence BC
- **crm_conversion_attributions:** Rastreia conteudo → lead → deal → receita
- **Jobs + Listeners:** CrmDealCreated/CrmContactSynced → AttributeCrmConversionJob → attribution
- **StubCrmIntelligenceProvider:** Retorna valores neutros (sera substituido por implementacao real)
- **2226 testes passando** (61 novos neste sprint: 46 Sprint 16.4 + 16 Sprint 16.5)
- Feature gates (Billing integration) e RAG boost real pendentes para implementacao futura

---

## Fase 5 — Paid Advertising / Trafego Pago (v5.0)

Os sprints 17 e 18 adicionam a capacidade de impulsionar conteudo publicado via trafego pago nas plataformas de anuncios (Meta Ads, TikTok Ads, Google Ads), com audience targeting granular e aprendizado da IA a partir dos dados de performance de anuncios.

> **Nota importante:** Este modulo envolve transferencia de valores monetarios reais para as plataformas de anuncios. A implementacao requer Business Verification, App Review e contas de anuncios verificadas em cada plataforma. Posts organicos **nao suportam** audience targeting — targeting e exclusivo de conteudo pago via Marketing APIs.

---

## Sprint 17 — Paid Advertising Core

**Objetivo:** Conectar contas de anuncios, criar audiencias de targeting, impulsionar posts publicados e monitorar performance de anuncios.

**Bounded Context:** Paid Advertising (novo)

> **Referencia:** ADR-020 (a ser criado), RF-110 a RF-114, RF-116

### 17.1 Domain Layer

- [x] `AdAccount` entity (id, organizationId, connectedBy, provider, providerAccountId, providerAccountName, credentials, status, metadata, connectedAt). Methods: create, reconstitute, disconnect, refreshCredentials, markTokenExpired, suspend, reactivate, releaseEvents
- [x] `Audience` entity (id, organizationId, name, targetingSpec, providerAudienceIds). Methods: create, reconstitute, update, setProviderAudienceId, getProviderAudienceId, releaseEvents
- [x] `AdBoost` entity (id, organizationId, scheduledPostId, adAccountId, audienceId, budget, durationDays, objective, status, externalIds, rejectionReason, startedAt, completedAt, createdBy). Methods: create, reconstitute, submitForReview, activate, pause, resume, complete, reject, cancel, releaseEvents
- [x] `AdMetricSnapshot` entity (id, boostId, period, impressions, reach, clicks, spendCents, spendCurrency, conversions, ctr, cpc, cpm, costPerConversion, capturedAt). Metricas derivadas auto-calculadas no create()
- [x] Value Objects — 6 enums: `AdProvider` (meta, tiktok, google), `AdAccountStatus` (active, token_expired, suspended, disconnected), `AdStatus` (draft, pending_review, active, paused, completed, rejected, cancelled), `AdObjective` (reach, engagement, traffic, conversions), `BudgetType` (daily, lifetime), `MetricPeriod` (hourly, daily, weekly, lifetime). 6 classes: `AdBudget` (amountCents, currency, type + validateForProvider), `DemographicFilter`, `LocationFilter`, `InterestFilter`, `TargetingSpec` (composite), `AdAccountCredentials` (encrypted tokens + isExpired/willExpireSoon)
- [x] Domain Events (10): `AdAccountConnected`, `AdAccountDisconnected`, `AudienceCreated`, `AudienceUpdated`, `BoostCreated`, `BoostActivated`, `BoostCompleted`, `BoostRejected`, `BoostCancelled`, `AdMetricsSynced`
- [x] Contracts: `AdPlatformInterface` (9 metodos: connect, handleCallback, createCampaign, createAdSet, createAd, getAdStatus, getMetrics, searchInterests, deleteAd)
- [x] Repository interfaces (4): `AdAccountRepositoryInterface`, `AudienceRepositoryInterface`, `AdBoostRepositoryInterface`, `AdMetricSnapshotRepositoryInterface`
- [x] Exceptions (6): `AdAccountNotFoundException`, `AudienceNotFoundException`, `BoostNotAllowedException`, `InsufficientBudgetException`, `AdPlatformException`, `InvalidAdStatusTransitionException`

### 17.2 Application Layer

- [x] Use Cases Ad Account:
  - `ConnectAdAccountUseCase`
  - `HandleAdAccountCallbackUseCase`
  - `ListAdAccountsUseCase`
  - `GetAdAccountStatusUseCase`
  - `DisconnectAdAccountUseCase`
  - `TestAdAccountConnectionUseCase`
- [x] Use Cases Audience:
  - `CreateAudienceUseCase`
  - `UpdateAudienceUseCase`
  - `ListAudiencesUseCase`
  - `GetAudienceUseCase`
  - `DeleteAudienceUseCase`
  - `SearchInterestsUseCase` (busca interesses por provider)
- [x] Use Cases Boost:
  - `CreateBoostUseCase`
  - `ListBoostsUseCase`
  - `GetBoostUseCase`
  - `CancelBoostUseCase`
  - `GetBoostMetricsUseCase`
  - `SubmitBoostToPlatformUseCase` (job-triggered)
  - `SyncAdMetricsUseCase` (job-triggered)
- [x] Use Cases Analytics/Reports:
  - `GetAdAnalyticsOverviewUseCase`
  - `GetSpendingHistoryUseCase`
  - `ExportSpendingReportUseCase`
- [x] DTOs para input/output de cada use case (22 inputs + 10 outputs)
- [x] Application Contracts: `AdPlatformFactoryInterface`, `AdOAuthStateServiceInterface`, `AdTokenEncryptorInterface`, `AdReportExporterInterface`
- [x] Application Exceptions: `AdAccountAuthorizationException`, `BoostNotFoundException`, `AdOAuthStateInvalidException`, `DuplicateAudienceNameException`, `AdAccountNotOperationalException`
- [x] Listeners:
  - `BoostCreated` → `DispatchBoostSubmission` → `CreateAdBoostJob`
  - `AdMetricsSynced` → `ScheduleAdPerformanceAggregation` → `AggregateAdPerformanceJob` (para Sprint 18)

### 17.3 Infrastructure Layer

- [x] Config: `config/ads.php` (providers Meta/TikTok/Google, encryption key, OAuth state TTL)
- [x] Migrations: `ad_accounts`, `audiences`, `ad_boosts`, `ad_metric_snapshots`
- [x] Eloquent Models: `AdAccountModel`, `AudienceModel`, `AdBoostModel`, `AdMetricSnapshotModel`
- [x] Repositories: `EloquentAdAccountRepository`, `EloquentAudienceRepository`, `EloquentAdBoostRepository`, `EloquentAdMetricSnapshotRepository`
- [x] `AdTokenEncrypter` (AES-256-GCM, chave dedicada `AD_TOKEN_ENCRYPTION_KEY`)
- [x] `AdOAuthStateService` (cache-based, prefix `ad_oauth_state:`)
- [x] `AdPlatformFactory` — resolve adapter por provider
- [x] **Stub Meta Ads Adapter** (implementa `AdPlatformInterface`): 9 metodos com dados Meta realistas
- [x] **Stub TikTok Ads Adapter** (implementa `AdPlatformInterface`): 9 metodos com dados TikTok realistas
- [x] **Stub Google Ads Adapter** (implementa `AdPlatformInterface`): 9 metodos com dados Google realistas
- [x] `StubAdReportExporter` (implementa `AdReportExporterInterface`)
- [x] FormRequests: 10 validadores (Connect, Callback, Test, CreateAudience, UpdateAudience, SearchInterests, CreateBoost, CancelBoost, GetBoostMetrics, ExportSpendingReport)
- [x] Resources: 6 API resources (AdAccount, Audience, Boost, BoostMetrics, AdAnalyticsOverview, SpendingHistory)
- [x] Controllers: `AdAccountController`, `AudienceController`, `AdBoostController`, `AdAnalyticsController`
- [x] Jobs: `CreateAdBoostJob`, `SyncAdStatusJob`, `SyncAdMetricsJob`, `RefreshAdAccountTokenJob`, `ExportSpendingReportJob`
- [x] Listeners: `DispatchBoostSubmission` (BoostCreated), `ScheduleAdPerformanceAggregation` (AdMetricsSynced)
- [x] `PaidAdvertisingServiceProvider` (4 repos + 4 contracts + 2 event listeners)
- [x] Routes: `routes/api/v1/ads.php` (20 rotas com `plan.limit:paid_advertising`)
- [x] Scheduler: sync status (30min), sync metricas (1h), refresh tokens (2x/dia)
- [x] Architecture tests: models final, controllers final, resources final readonly, requests final

### 17.4 Testes

- [x] Unit: SimpleEnumsTest (AdProvider, AdObjective, BudgetType, MetricPeriod, AdAccountStatus, AdStatus) — 22 testes
- [x] Unit: AdBudgetTest (criacao, validacao provider, toDecimal, equals) — 11 testes
- [x] Unit: AdAccountCredentialsTest (isExpired, willExpireSoon, hasRefreshToken) — 6 testes
- [x] Unit: TargetingFiltersTest (DemographicFilter, LocationFilter, InterestFilter) — 18 testes
- [x] Unit: TargetingSpecTest (fromArray, toArray, isEmpty, equals) — 5 testes
- [x] Unit: AdAccountTest (create, disconnect, refresh, suspend, reactivate) — 13 testes
- [x] Unit: AudienceTest (create, update, providerIds, releaseEvents) — 7 testes
- [x] Unit: AdBoostTest (state machine completo, 8 transicoes validas, 4 invalidas) — 25 testes
- [x] Unit: AdMetricSnapshotTest (auto-calculo CTR/CPC/CPM, divisao por zero) — 7 testes
- [x] Unit: 22 Use Case tests (ConnectAdAccount, HandleCallback, List, Get, Test, Disconnect, CRUD Audience, Search, CRUD Boost, Submit, Cancel, Metrics, Analytics, Export) — 65 testes
- [x] Integration: StubMetaAdPlatformTest (8 metodos do adapter) — 8 testes
- [x] Integration: StubTikTokAdPlatformTest — 8 testes
- [x] Integration: StubGoogleAdPlatformTest — 8 testes
- [x] Integration: AdPlatformFactoryTest (resolve 3 providers) — 4 testes
- [x] Integration: AdTokenEncrypterTest (encrypt/decrypt roundtrip, corrupcao) — 5 testes
- [x] Feature: AdAccountFlowTest (connect, list, show, test, disconnect) — 6 testes
- [x] Feature: AudienceFlowTest (CRUD, duplicate name, search interests) — 7 testes
- [x] Feature: AdBoostFlowTest (create, list, show, cancel) — 4 testes
- [x] Feature: AdAnalyticsFlowTest (overview, spending, export) — 3 testes

**Total Sprint 17.4: 37 arquivos de teste, 232 testes**

### Entregaveis Sprint 17

- Conexao com contas de anuncios em 3 plataformas (Meta, TikTok, Google)
- CRUD de audiencias com targeting granular (demografia, localizacao, interesses, comportamento)
- Boost de posts publicados com budget e objetivo configuravel
- Monitoramento de status do anuncio (pending_review → active → completed)
- Dashboard de metricas de anuncios (impressions, reach, clicks, ctr, cpc, spend)
- Comparativo organico vs pago
- Historico e exportacao de gastos
- Feature gates por plano integrados ao billing

---

## Sprint 18 — AI Learning from Ads Data

**Objetivo:** Integrar dados de performance de anuncios ao pipeline de aprendizado da IA, permitindo que a IA aprenda quais audiencias, tons e horarios geram melhor performance paga.

**Bounded Contexts:** AI Intelligence (extensao), Paid Advertising (extensao)

> **Referencia:** ADR-017 (extensao), ADR-020, RF-115

### 18.1 Domain Layer

- [x] `AdPerformanceInsight` entity (AI Intelligence BC) — TTL 7d, refresh, confidence via ConfidenceLevel
- [x] Value Objects: `AdInsightType` enum (best_audiences, best_content_for_ads, organic_vs_paid_correlation)
- [x] Domain Events: `AdPerformanceAggregated`, `AdAIContextEnriched`, `AdTargetingSuggested`
- [x] Contracts: `AdIntelligenceProviderInterface` (5 metodos)
- [x] Repository: `AdPerformanceInsightRepositoryInterface` (save, findById, findByOrgAndType, findActive, findExpired)
- [x] Exception: `AdPerformanceInsightExpiredException`
- [x] Architecture tests atualizados (enum + repository interface)
- [x] Unit tests: AdInsightTypeTest (4) + AdPerformanceInsightTest (14) = 18 testes

### 18.2 Application Layer

- [x] Use Cases:
  - `AggregateAdPerformanceUseCase` — agrega metricas de ads por audiencia, conteudo, horario
  - `EnrichAIContextFromAdsUseCase` — injeta dados de ads no ai_generation_context
  - `GetAdTargetingSuggestionsUseCase` — sugere targeting baseado em performance historica
  - `GetAdPerformanceInsightsUseCase` — retorna insights de ads para o usuario
- [x] DTOs: AggregateAdPerformanceInput, GetAdTargetingSuggestionsInput, GetAdPerformanceInsightsInput, AdPerformanceInsightOutput, AdTargetingSuggestionsOutput
- [x] Exception: InsufficientAdDataException
- [x] Unit tests: 17 testes, 82 assertions

### 18.3 Infrastructure Layer

- [x] Migration: `ad_performance_insights` table (0001_01_01_000077)
- [x] Model: `AdPerformanceInsightModel` (HasUuids, casts)
- [x] Repository: `EloquentAdPerformanceInsightRepository` (save, findById, findByOrgAndType, findActive, findExpired)
- [x] Stub: `StubAdIntelligenceProvider` (implements AdIntelligenceProviderInterface)
- [x] Jobs:
  - `AggregateAdPerformanceJob` (semanal — agrupa metricas por audiencia/conteudo)
  - `EnrichAIContextFromAdsJob` (pos-agregacao — atualiza ai_generation_context)
  - `GenerateAdTargetingSuggestionsJob` (on-demand — ao criar novo boost)
- [x] Listeners:
  - `AdPerformanceAggregated` → `EnrichAIContextOnAdPerformanceAggregated`
  - `BoostCreated` → `GenerateTargetingSuggestionsOnBoostCreated`
  - `ScheduleAdPerformanceAggregation` — dispatch `AggregateAdPerformanceJob` para cada AdInsightType
- [x] Request: `ListAdPerformanceInsightsRequest` (type filter validation)
- [x] Resource: `AdPerformanceInsightResource` (JSON:API format)
- [x] Controller: `AdIntelligenceController` (insights + targetingSuggestions)
- [x] Endpoints:
  - `GET /api/v1/ads/intelligence/insights` — insights de performance de ads
  - `GET /api/v1/ads/intelligence/targeting-suggestions` — sugestoes de targeting para novo boost
- [x] Service Provider: bindings + event listeners registrados
- [x] Feature gate: `plan.limit:paid_advertising` (Professional+)

### 18.4 Testes

- [x] Unit: AdPerformanceInsight entity (14), AdInsightType VO (4) = 18 testes
- [x] Unit: Todos os Use Cases — 17 testes (82 assertions)
- [x] Feature: Insights de ads — 200 com dados, 200 vazio, filtro por type, 401, isolamento org, exclui expirados
- [x] Feature: Sugestoes de targeting — 200 com sugestoes, 401
- [x] Feature: Isolamento por organization_id
- [x] Total Sprint 18: 43 novos testes (2510 total, 0 failures)

### Entregaveis Sprint 18

- Pipeline de agregacao de metricas de anuncios para IA
- Insights de performance: quais audiencias, tons e horarios geram melhor resultado pago
- Sugestoes de targeting ao criar novo boost (baseado em historico)
- Conteudos com alta performance paga ganham boost no ranking RAG
- Injecao de contexto de ads em geracao de conteudo (transparente ao usuario)
- Correlacao organico vs pago: "conteudo que performa bem organicamente tambem performa pago?"
- Feature gates integrados ao billing e estado da conta de anuncios

---

## Fase 6 — AI Agents Platform (v6.0)

O Sprint 19 introduz um microservico Python com LangGraph para orquestracao de pipelines multi-agente, elevando a qualidade dos outputs de IA em fluxos complexos. O microservico roda como container Docker no mesmo stack, comunicando-se com Laravel via HTTP assincrono.

> **Referencia:** ADR-021 (Multi-Agent AI Architecture com LangGraph)

---

## Sprint 19 — Multi-Agent AI Pipelines (LangGraph)

**Objetivo:** Implementar microservico Python com LangGraph para 3 pipelines multi-agente: Content Creation, Content DNA Deep Analysis e Social Listening Intelligence.

**Bounded Contexts:** Content AI (extensao), AI Intelligence (extensao)

> **Nota:** Este sprint requer que toda a infraestrutura de IA esteja completa (Sprints 12-14) e dados acumulados de Social Listening (Sprint 9), CRM (Sprint 16) e Ads (Sprint 18). O microservico comunica-se via HTTP assincrono com callback. Fallback automatico para Prism single-shot via circuit breaker.

### 19.1 Microservico Python — Setup

- [x] Criar diretorio `ai-agents/` com estrutura do projeto Python
- [x] `Dockerfile` multi-stage (Python 3.12-slim, appuser com UID/GID dinamico)
- [x] `requirements.txt` (langgraph, langchain, fastapi, uvicorn, httpx, asyncpg, redis, structlog)
- [x] `docker-compose.yml`: adicionar servico `ai-agents` na rede `social-media-net`
- [x] FastAPI app com health check (`/health`, `/ready`)
- [x] Configuracao via env vars (API keys, DATABASE_URL, REDIS_URL, CALLBACK_BASE_URL)
- [x] Redis DB 4 para checkpoints e job status do LangGraph
- [x] Logs estruturados em JSON (compativel com padrao Laravel)
- [x] Testes: health check, conectividade Redis/PostgreSQL (5 testes pytest)

### 19.2 Pipeline: Content Creation

- [x] LangGraph StateGraph: `ContentCreationState` (TypedDict com Annotated reducer)
- [x] Agente `Planner` — define tom, estrutura, publico, CTA, constraints (structured output ContentBrief)
- [x] Agente `Writer` — gera conteudo seguindo briefing do Planner (incorpora feedback em retry)
- [x] Agente `Reviewer` — verifica brand safety, tom, guidelines, qualidade (structured output ReviewResult)
- [x] Agente `Optimizer` — otimiza por rede social (hashtags, CTA, tamanho, midia) — specs: instagram, tiktok, youtube
- [x] Conditional edge: Reviewer reprovado → Writer retry (max 2 retries, force forward apos max)
- [x] Injecao de contexto: style_profile (ADR-017 N5), rag_examples (ADR-017 N2)
- [x] Endpoint: `POST /api/v1/pipelines/content-creation` (202 Accepted + job_id + background task)
- [x] Callback ao Laravel com resultado final + metadata (tokens, custo, duracao) via httpx
- [x] Testes: 11 testes (graph completo, retry loop, max retries, agentes individuais, endpoint, validacao)

### 19.3 Pipeline: Content DNA Deep Analysis

- [x] LangGraph StateGraph: `ContentDNAState` (TypedDict com append reducer)
- [x] Agente `StyleAnalyzer` — analisa tom, vocabulario, estrutura, padroes (structured output StylePatterns)
- [x] Agente `EngagementAnalyzer` — correlaciona metricas com padroes de conteudo (structured output EngagementCorrelations)
- [x] Agente `ProfileSynthesizer` — combina analises em perfil multidimensional (structured output DNAProfile com confidence)
- [x] Injecao de dados: conteudos publicados, metricas, style profile existente (via request body)
- [x] Endpoint: `POST /api/v1/pipelines/content-dna` (202 Accepted + job_id + background task)
- [x] Callback ao Laravel com perfil enriquecido via httpx
- [x] Testes: 8 testes (graph completo, 3 agentes individuais, dados insuficientes, endpoint, validacao, health)

### 19.4 Pipeline: Social Listening Intelligence

- [x] LangGraph StateGraph: `SocialListeningState` (TypedDict com append reducer para agents_executed)
- [x] Agente `MentionClassifier` — categoriza mencao em 5 categorias (praise, complaint, question, crisis, spam) + urgency (temp=0.2)
- [x] Agente `SentimentAnalyzer` — analise profunda com contexto cultural, deteccao de ironia, blocos condicionais crise/padrao (temp=0.3)
- [x] Agente `ResponseStrategist` — sugere resposta contextualizada com tom adequado, estrategia por categoria (temp=0.5)
- [x] Agente `SafetyChecker` — verifica brand safety, blacklist, promessas, risco legal, tom, dados pessoais (temp=0.1)
- [x] Tratamento diferenciado por categoria via prompts condicionais (crise = processamento profundo, sem conditional edges)
- [x] Endpoint: `POST /api/v1/pipelines/social-listening` (202 Accepted + job_id + background task)
- [x] Callback ao Laravel com classificacao, sentimento, resposta sugerida e resultado safety via httpx
- [x] Fix bug callback.py: `pipeline` parametrizado (era hardcoded `"content_creation"`)
- [x] Testes: 11 testes (3 graph flows, 5 agentes individuais, endpoint 202, validacao input, health 3 pipelines)

### 19.5 Pipeline: Visual Adaptation Cross-Network

- [ ] LangGraph StateGraph: `VisualAdaptationState`
- [ ] Agente `VisionAnalyzer` — LLM multimodal analisa sujeito, composicao, texto, safe zones
- [ ] Agente `CropStrategist` — define estrategia de corte por rede (1:1, 4:5, 9:16, 16:9)
- [ ] Agentes `NetworkAdapters` (paralelo) — executa crop/resize via Pillow por rede alvo
- [ ] Agente `QualityChecker` — LLM multimodal valida sujeito visivel, texto legivel, composicao
- [ ] Conditional edge: QualityChecker reprovado → CropStrategist retry (max 2)
- [ ] Specs por rede: Instagram (1:1, 4:5, 9:16), TikTok (9:16 + safe zones), YouTube (16:9 thumb)
- [ ] Endpoint: `POST /api/v1/pipelines/visual-adaptation`
- [ ] Callback ao Laravel com URLs das imagens adaptadas + quality scores
- [ ] Testes: graph completo, cada formato de rede, quality rejection + retry

### 19.6 Integracao Laravel

- [ ] Novo adapter: `LangGraphTextGenerator implements TextGeneratorInterface`
- [ ] Novo adapter: `LangGraphContentProfiler implements ContentProfileAnalyzerInterface`
- [ ] Novo adapter: `LangGraphMentionAnalyzer implements MentionAnalyzerInterface`
- [ ] Novo adapter: `LangGraphVisualAdapter implements VisualAdapterInterface`
- [ ] Circuit breaker por pipeline (`circuit:ai_agents:{pipeline}` em Redis)
- [ ] Fallback automatico para Prism single-shot quando circuit open
- [ ] Rota interna: `POST /api/v1/internal/agent-callback` (middleware `internal-only`)
- [ ] Middleware `internal-only`: valida IP rede Docker + header `X-Internal-Secret`
- [ ] Feature gate: Content Creation + Visual Adaptation = Professional+ (3-5/dia) / Agency (ilimitado)
- [ ] Feature gate: Content DNA Deep + Social Listening = Agency only
- [ ] Cost tracking: metadata de tokens/custo registrado em `ai_generations`
- [ ] Provider config: novo provider `langgraph` em `ai_settings.provider_config`

### 19.7 Testes

- [ ] Unit: LangGraphTextGenerator (mock de HTTP)
- [ ] Unit: LangGraphVisualAdapter (mock de HTTP)
- [ ] Unit: Circuit breaker behavior (open → fallback → half-open → close)
- [ ] Unit: Callback processing e validacao
- [ ] Integration: Content Creation pipeline end-to-end (mock de LLM)
- [ ] Integration: Content DNA pipeline end-to-end (mock de LLM)
- [ ] Integration: Social Listening pipeline end-to-end (mock de LLM)
- [ ] Integration: Visual Adaptation pipeline end-to-end (mock de LLM multimodal)
- [ ] Integration: Fallback para Prism quando ai-agents indisponivel
- [ ] Feature: Content Creation via API (request → 202 → callback → resultado)
- [ ] Feature: Visual Adaptation via API (imagem → versoes por rede)
- [ ] Feature: Feature gate (Professional vs Agency)
- [ ] Feature: Cost tracking (metadata de agentes em ai_generations)
- [ ] Feature: Isolamento por organization_id
- [ ] Python: Testes unitarios de cada agente (pytest)
- [ ] Python: Testes de integracao dos graphs (pytest + mock LLM)

### Entregaveis Sprint 19

- Microservico Python (`ai-agents`) rodando como container Docker no stack existente
- 4 pipelines multi-agente funcionais: Content Creation, Content DNA, Social Listening, Visual Adaptation
- Qualidade de conteudo gerado significativamente superior ao single-shot
- Adaptacao visual inteligente: crop semantico por rede via LLM multimodal (vs crop mecanico)
- Fallback automatico para Prism via circuit breaker (zero downtime)
- Comunicacao assincrona via HTTP + callback (nao bloqueia Laravel)
- Feature gates integrados ao billing
- Cost tracking unificado (tokens + custo de todos os agentes)
- Logs estruturados compativeis com stack existente
- Health checks e monitoramento integrados

---

## Fase 7 — Consolidacao e Enriquecimento (v7.0)

A Fase 7 consolida pendencias cross-cutting identificadas ao longo das Fases 1-4: integracao dos stubs de IA no pipeline de geracao (RAG, style, audience, template), aplicacao de feature gates nas rotas CRM/AI, e implementacao dos testes de integracao adiados.

---

## Sprint 20 — Geracao Enriquecida (Integracao RAG + Style + Audience + Template)

**Objetivo:** Substituir os 6 stubs de IA por implementacoes reais e integra-los ao pipeline de geracao via `PrismTextGeneratorService`, ativar listeners assincronos deferidos no Sprint 14, e adicionar testes de integracao para o fluxo completo.

**Bounded Contexts:** Content AI (extensao), AI Intelligence (extensao)

> **Nota:** Este sprint consolida pendencias dos Sprints 3, 12, 13 e 14 relacionadas a geracao enriquecida. Os stubs foram criados intencionalmente para permitir desenvolvimento incremental; agora sao substituidos por implementacoes que consultam dados reais (embeddings, style profiles, audience insights, prompt templates).

### 20.1 Implementacao Real dos Providers

- [ ] `StubRAGContextProvider` → `EloquentRAGContextProvider`: consulta `content_embeddings` via pgvector para buscar conteudos similares como exemplos de referencia
- [ ] `StubPromptTemplateResolver` → `EloquentPromptTemplateResolver`: resolve templates de `prompt_templates` com variaveis dinamicas (network, tone, audience, content_type)
- [ ] `StubStyleProfileAnalyzer` → `EloquentStyleProfileAnalyzer`: consulta `organization_style_profiles` para injetar tom, vocabulario e padroes da marca
- [ ] `StubAudienceInsightAnalyzer` → `EloquentAudienceInsightAnalyzer`: consulta `audience_insights` para adaptar conteudo ao publico-alvo
- [ ] `StubEmbeddingGenerator` → `PrismEmbeddingGenerator`: gera embeddings via AI provider (text-embedding-3-small ou equivalente)
- [ ] `StubSentimentAnalyzer` → `PrismSentimentAnalyzer`: analise de sentimento via AI provider

### 20.2 Integracao no Pipeline de Geracao

- [ ] Modificar `PrismTextGeneratorService`: antes de `callAI()`, injetar contexto de RAG, style, audience e template
- [ ] Fluxo enriquecido: `resolveTemplate() → fetchRAGExamples() → getStyleProfile() → getAudienceInsights() → buildEnrichedPrompt() → callAI()`
- [ ] Os 5 use cases de geracao (`GenerateTitle`, `GenerateDescription`, `GenerateHashtags`, `GenerateFullContent`, `AdaptContent`) passam automaticamente a usar o pipeline enriquecido via `TextGeneratorInterface`
- [ ] Fallback gracioso: se um provider falhar (ex: sem embeddings), continua geracao sem aquele contexto (nao bloqueia)

### 20.3 Listeners Assincronos (Deferidos do Sprint 14)

- [ ] `PostPublished` → agendar `ValidatePredictionJob` (validacao de predicao apos publicacao)
- [ ] `MetricsSynced` → disparar `ValidatePredictionJob` com metricas reais
- [ ] `PromptExperimentCompleted` → ativar template vencedor automaticamente
- [ ] `OrgStyleProfileGenerated` → atualizar contexto de geracao com novo perfil

### 20.4 Testes

- [ ] Integration: RAGContextProvider retorna embeddings similares (pgvector + fixture)
- [ ] Integration: PromptTemplateResolver resolve variaveis corretamente
- [ ] Integration: StyleProfileAnalyzer retorna perfil da organizacao
- [ ] Integration: AudienceInsightAnalyzer retorna insights do publico
- [ ] Integration: PrismEmbeddingGenerator gera embeddings (mock de AI provider)
- [ ] Integration: PrismSentimentAnalyzer classifica sentimento (mock de AI provider)
- [ ] Integration: Pipeline completo de geracao enriquecida (template + RAG + style + audience → prompt enriquecido)
- [ ] Unit: Fallback quando provider individual falha
- [ ] Unit: Listeners agendam jobs corretos com dados corretos
- [ ] Feature: `POST /generate-title` retorna titulo com contexto enriquecido

### Entregaveis Sprint 20

- 6 stubs substituidos por implementacoes reais (RAG, template, style, audience, embedding, sentiment)
- Pipeline de geracao enriquecido: toda geracao de conteudo passa por contexto RAG + style + audience + template
- Qualidade de geracao significativamente superior (contexto relevante injetado automaticamente)
- Listeners assincronos do Sprint 14 ativados (prediction validation, style profile updates)
- Fallback gracioso: geracao nunca falha por falta de contexto opcional
- ~15 testes novos (integration + unit + feature)

---

## Sprint 21 — Feature Gates + Testes de Integracao Pendentes

**Objetivo:** Aplicar middleware `CheckPlanLimit` nas rotas CRM e AI Intelligence, e implementar os testes de integracao adiados nos Sprints 9, 10, 13 e 15.

**Bounded Contexts:** Billing (extensao), Engagement (extensao), Social Listening (extensao), AI Intelligence (extensao)

> **Nota:** Este sprint consolida duas categorias de pendencias: (1) feature gates que nao foram aplicados porque o middleware `CheckPlanLimit` ainda nao existia quando as rotas CRM/AI foram criadas, e (2) testes de integracao que requerem mocks reais de APIs externas (listening adapters, best times, brand safety LLM, CRM connectors).

### 21.1 Feature Gates — CRM Routes

- [ ] Aplicar `CheckPlanLimit` nas rotas CRM: `crm.connections.*` (Professional+ pode SF/AC, Agency pode todos)
- [ ] Aplicar `CheckPlanLimit` nas rotas de CRM field mappings
- [ ] Aplicar `CheckPlanLimit` nas rotas de CRM sync
- [ ] Feature gate config: `crm_connections` com limites por plano (Free=0, Creator=0, Professional=2, Agency=unlimited)

### 21.2 Feature Gates — AI Intelligence Routes

- [ ] Aplicar `CheckPlanLimit` nas rotas AI Intelligence: predictions, content DNA, gap analysis
- [ ] Aplicar `CheckPlanLimit` nas rotas de geracao enriquecida (Sprint 20)
- [ ] Feature gate config: `ai_generations_per_month` com limites por plano (Free=10, Creator=50, Professional=200, Agency=unlimited)
- [ ] Feature gate config: `ai_advanced_features` (content DNA, prediction, gap analysis) — Professional+ only

### 21.3 Testes de Integracao — Social Listening (Sprint 9)

- [ ] Integration: Listening adapter mock para Instagram mentions
- [ ] Integration: Mention partitioning por mes (tabela particionada)

### 21.4 Testes de Integracao — Best Time + Brand Safety (Sprint 10)

- [ ] Integration: Best times calculation com dados reais de metricas
- [ ] Integration: Brand safety check via LLM mock (analise de conteudo por AI)

### 21.5 Testes de Integracao — Feedback Loop (Sprint 13)

- [ ] Integration: Embedding diff calculation (comparacao de embeddings antes/depois)

### 21.6 Testes de Integracao — CRM Connectors (Sprint 15)

- [ ] Integration: HubSpot connector (mock de API HubSpot)
- [ ] Integration: RD Station connector (mock de API RD Station)
- [ ] Integration: Pipedrive connector (mock de API Pipedrive)

### 21.7 Testes

- [ ] Feature: CRM routes bloqueadas para Free/Creator (403 com PLAN_LIMIT_EXCEEDED)
- [ ] Feature: CRM routes permitidas para Professional/Agency
- [ ] Feature: AI routes com limite por plano (header `X-RateLimit-Remaining`)
- [ ] Feature: AI advanced features bloqueados para Free/Creator
- [ ] Unit: CheckPlanLimit middleware com diferentes planos e features
- [ ] Integration: Todos os integration tests listados em 21.3-21.6

### Entregaveis Sprint 21

- Feature gates aplicados em todas as rotas CRM e AI Intelligence
- Limites de uso por plano enforced via middleware `CheckPlanLimit`
- 7 testes de integracao pendentes implementados (listening, best time, safety, embedding, CRM connectors)
- ~15 testes novos (feature + unit + integration)
- Todas as pendencias de testes dos Sprints 9, 10, 13 e 15 resolvidas

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
| 17 | 4, 5, 6 | Paid Advertising (Core — ADR-020) | 5 |
| 18 | 14, 17 | AI Intelligence (Ad Learning — ADR-017+020) | 5 |
| 19 | 9, 14, 16, 18 | Content AI, AI Intelligence (Multi-Agent Pipelines — ADR-021) | 6 |
| 20 | 3, 12, 13, 14 | Content AI (extensao), AI Intelligence (Geracao Enriquecida — RAG + Style + Audience + Template) | 7 |
| 21 | 6, 9, 10, 13, 15, 20 | Billing (extensao), Engagement, Social Listening, AI Intelligence (Feature Gates + Integration Tests) | 7 |

> **Nota:** Sprint 6 (Billing) depende apenas do Sprint 1, podendo ser iniciado em paralelo com Sprints 3-5 se houver capacidade.

> **Nota:** Sprints 10-11 (AI Intelligence Alpha) podem rodar em paralelo com Sprints 8-9, pois dependem apenas dos Sprints 3-5 da Fase 1.

> **Nota:** Sprint 13 (Feedback Loop + Gap Analysis) depende do Sprint 9 (Social Listening) para dados de concorrentes e do Sprint 12 para o pipeline de embeddings.

> **Nota:** Sprint 14 (AI Learning Loop) depende do Sprint 3 (Content AI base), Sprint 5 (Analytics para metricas), Sprint 12 (embeddings para RAG) e Sprint 13 (audience insights para contexto).

> **Nota:** Sprint 15 (CRM Connectors Fase 1) depende do Sprint 2 (Social Account — OAuth patterns), Sprint 5 (Engagement & Automation — webhooks/comentarios) e Sprint 6 (Billing — feature gates). Pode rodar em paralelo com Sprints 12-14 se houver capacidade.

> **Nota:** Sprint 16 (CRM Fase 2 + CRM Intelligence) depende do Sprint 15 (infraestrutura CRM) e Sprint 14 (Learning Loop) para conectar dados de conversao CRM ao pipeline de IA.

> **Nota:** Sprint 17 (Paid Advertising Core) depende do Sprint 4 (Publishing — posts publicados para boost), Sprint 5 (Analytics — metricas para comparativo) e Sprint 6 (Billing — feature gates por plano). Pode rodar em paralelo com Sprints 15-16.

> **Nota:** Sprint 18 (AI Learning from Ads) depende do Sprint 14 (Learning Loop — pipeline de aprendizado) e Sprint 17 (Paid Advertising — dados de ads).

> **Nota:** Sprint 19 (Multi-Agent AI Pipelines) depende de toda a infraestrutura de IA (Sprints 12-14), Social Listening (Sprint 9), CRM Intelligence (Sprint 16) e Ad Learning (Sprint 18). E o culminar de todos os pipelines de IA em agentes especializados.

> **Nota:** Sprint 20 (Geracao Enriquecida) depende do Sprint 3 (Content AI base), Sprint 12 (embeddings), Sprint 13 (audience insights) e Sprint 14 (learning loop — stubs criados). Substitui stubs por implementacoes reais e integra ao pipeline de geracao.

> **Nota:** Sprint 21 (Feature Gates + Integration Tests) depende do Sprint 6 (Billing — CheckPlanLimit middleware), Sprints 9/10/13/15 (testes de integracao pendentes) e Sprint 20 (rotas de geracao enriquecida para aplicar gates). Pode rodar em paralelo com Fases 5-6 se houver capacidade.

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

### Fase 5 (v5.0) — Sprints 17-18

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 17 | 4 | ~15 | ~20 | 5 | ~70 |
| 18 | 2 | ~4 | ~6 | 3 | ~40 |
| **Subtotal Fase 5** | **6** | **~19** | **~26** | **8** | **~110** |

### Fase 6 (v6.0) — Sprint 19

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 19 | 0 (Python) | ~5 (interno + 3 pipelines) | ~3 (adapters) | 0 | ~50 (PHP + Python) |
| **Subtotal Fase 6** | **0** | **~5** | **~3** | **0** | **~50** |

> **Nota:** Sprint 19 nao cria migrations PHP — o microservico Python usa tabelas existentes. Os "endpoints" sao 3 pipelines no FastAPI + rota interna de callback no Laravel. Os "use cases" sao 3 novos adapters na Infrastructure Layer que implementam contratos existentes. Testes incluem PHP (adapters, circuit breaker, callback) e Python (pytest para agentes e graphs).

### Fase 7 (v7.0) — Sprints 20-21

| Sprint | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--------|-----------|-----------|-----------|------|---------------|
| 20 | 0 | 0 | 0 (modifica existentes) | 4 (listeners) | ~15 |
| 21 | 0 | 0 | 0 (middleware) | 0 | ~15 |
| **Subtotal Fase 7** | **0** | **0** | **0** | **4** | **~30** |

> **Nota:** Sprint 20 nao cria novos endpoints ou use cases — substitui 6 stubs por implementacoes reais e modifica `PrismTextGeneratorService` para integrar contexto enriquecido. Sprint 21 aplica middleware existente (`CheckPlanLimit`) a rotas existentes e implementa testes de integracao adiados.

| | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--|-----------|-----------|-----------|------|---------------|
| **Total Geral** | **67** | **~203** | **~230** | **68** | **~1110** |

---

## Apos o Roadmap — Features Futuras

Itens para considerar apos a v6.0:

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
