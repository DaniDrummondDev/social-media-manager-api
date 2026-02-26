# Roadmap de Implementacao — Social Media Manager API

> **Versao:** 1.2.0\
> **Data:** 2026-02-26\
> **Status:** Em desenvolvimento — Sprint 12 em andamento

---

## Status Atual

| Fase | Sprints | Status |
|------|---------|--------|
| **Fase 1 — Core (v1.0)** | Sprint 0-7 | ✅ Completa |
| **Fase 2 — Expansao (v2.0)** | Sprint 8-11 | ✅ Completa (2 integration tests pendentes no Sprint 9-10) |
| **Fase 3 — IA Avancada (v3.0)** | Sprint 12-14 | 🔧 Em andamento — Sprint 12 ✅ Completo |
| **Fase 4 — CRM (v4.0)** | Sprint 15-16 | ⏳ Nao iniciada |
| **Fase 5 — Ads (v5.0)** | Sprint 17-18 | ⏳ Nao iniciada |
| **Fase 6 — AI Agents (v6.0)** | Sprint 19 | ⏳ Nao iniciada (ADR-021 documentada) |

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
| 13 | Feedback Loop + Gap | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 14 | AI Learning Loop | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 15 | CRM Connectors Fase 1 | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 16 | CRM Fase 2 + Intelligence | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 17 | Paid Advertising Core | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 18 | AI Learning from Ads | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |
| 19 | Multi-Agent AI (LangGraph) | ⏳ | ⏳ | ⏳ | ⏳ | ⏳ Nao iniciado |

> \* Sprints 9 e 10 possuem 2 testes de integracao cada com TODO stub (listening adapters mock, mention partitioning, best times calculation, safety check via LLM mock). Funcionalidade completa, testes de integracao pendentes de implementacao real dos mocks.

### Seguranca — Row-Level Security (RLS)

- [x] Migration RLS: `ENABLE ROW LEVEL SECURITY` + `FORCE ROW LEVEL SECURITY` em 36 tabelas multi-tenant + audit_logs
- [x] Middleware `SetTenantContext` (alias `tenant.rls`): executa `SET LOCAL app.current_org_id` com org_id do JWT
- [x] Bypass policy para jobs, admin, migrations (quando `app.current_org_id` nao esta definido)
- [x] Rotas atualizadas: `tenant.rls` adicionado a todos os grupos com `org.context` (12 arquivos)
- [x] ADR-019 atualizado com detalhes da implementacao

> **Referencia:** ADR-019 (Nivel 2 implementado antecipadamente como defesa em profundidade)

### Proximo passo

**Sprint 13 — Feedback Loop + Gap Analysis**: Proximo sprint a ser implementado.

---

## Visao Geral

O roadmap esta dividido em **19 sprints** organizados por dependencia entre bounded contexts. Os Sprints 0-7 cobrem a **Fase 1 (v1.0)**, os Sprints 8-11 cobrem a **Fase 2 (v2.0)**, os Sprints 12-14 cobrem a **Fase 3 (v3.0)**, os Sprints 15-16 cobrem a **Fase 4 (v4.0)** e os Sprints 17-18 cobrem a **Fase 5 (v5.0)**. Cada sprint entrega valor incremental e pode ser testado isoladamente.

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

## Fase 5 — Paid Advertising / Trafego Pago (v5.0)

Os sprints 17 e 18 adicionam a capacidade de impulsionar conteudo publicado via trafego pago nas plataformas de anuncios (Meta Ads, TikTok Ads, Google Ads), com audience targeting granular e aprendizado da IA a partir dos dados de performance de anuncios.

> **Nota importante:** Este modulo envolve transferencia de valores monetarios reais para as plataformas de anuncios. A implementacao requer Business Verification, App Review e contas de anuncios verificadas em cada plataforma. Posts organicos **nao suportam** audience targeting — targeting e exclusivo de conteudo pago via Marketing APIs.

---

## Sprint 17 — Paid Advertising Core

**Objetivo:** Conectar contas de anuncios, criar audiencias de targeting, impulsionar posts publicados e monitorar performance de anuncios.

**Bounded Context:** Paid Advertising (novo)

> **Referencia:** ADR-020 (a ser criado), RF-110 a RF-114, RF-116

### 17.1 Domain Layer

- [ ] `AdAccount` entity (id, organization_id, provider, credentials, status, metadata)
- [ ] `Audience` entity (id, organization_id, name, targeting_spec, provider_audience_ids)
- [ ] `AdBoost` entity (id, organization_id, scheduled_post_id, audience_id, budget, duration, objective, status, external_ids)
- [ ] `AdMetricSnapshot` entity (id, boost_id, period, impressions, reach, clicks, spend, conversions)
- [ ] Value Objects: `AdProvider` (enum: meta, tiktok, google), `AdStatus` (enum: draft, pending_review, active, paused, completed, rejected), `AdObjective` (enum: reach, engagement, traffic, conversions), `AdBudget`, `TargetingSpec`, `DemographicFilter`, `LocationFilter`, `InterestFilter`
- [ ] Domain Events: `AdAccountConnected`, `AdAccountDisconnected`, `AudienceCreated`, `AudienceUpdated`, `BoostCreated`, `BoostActivated`, `BoostCompleted`, `BoostRejected`, `BoostCancelled`, `AdMetricsSynced`
- [ ] Contracts: `AdPlatformInterface` (connect, createCampaign, createAdSet, createAd, getAdStatus, getMetrics, searchInterests, deleteAd)
- [ ] Repository interfaces: `AdAccountRepositoryInterface`, `AudienceRepositoryInterface`, `AdBoostRepositoryInterface`, `AdMetricSnapshotRepositoryInterface`
- [ ] Exceptions: `AdAccountNotFoundException`, `AudienceNotFoundException`, `BoostNotAllowedException`, `InsufficientBudgetException`, `AdPlatformException`

### 17.2 Application Layer

- [ ] Use Cases Ad Account:
  - `ConnectAdAccountUseCase`
  - `HandleAdAccountCallbackUseCase`
  - `ListAdAccountsUseCase`
  - `GetAdAccountStatusUseCase`
  - `DisconnectAdAccountUseCase`
  - `TestAdAccountConnectionUseCase`
- [ ] Use Cases Audience:
  - `CreateAudienceUseCase`
  - `UpdateAudienceUseCase`
  - `ListAudiencesUseCase`
  - `GetAudienceUseCase`
  - `DeleteAudienceUseCase`
  - `SearchInterestsUseCase` (busca interesses por provider)
- [ ] Use Cases Boost:
  - `CreateBoostUseCase`
  - `ListBoostsUseCase`
  - `GetBoostUseCase`
  - `CancelBoostUseCase`
  - `GetBoostMetricsUseCase`
- [ ] Use Cases Analytics/Reports:
  - `GetAdAnalyticsOverviewUseCase`
  - `GetSpendingHistoryUseCase`
  - `ExportSpendingReportUseCase`
- [ ] DTOs para input/output de cada use case
- [ ] Listeners:
  - `BoostCreated` → `CreateAdBoostJob`
  - `AdMetricsSynced` → `AggregateAdPerformanceJob` (para Sprint 18)

### 17.3 Infrastructure Layer

- [ ] Migrations: `ad_accounts`, `audiences`, `ad_boosts`, `ad_metric_snapshots`
- [ ] Eloquent Models + Repositories
- [ ] **Meta Ads Adapter** (implementa `AdPlatformInterface`):
  - SDK: `facebook/php-business-sdk`
  - OAuth 2.0 (System User tokens para server-to-server)
  - Cria Campaign → Ad Set (com targeting_spec) → Ad Creative (referencia post existente)
  - Sincroniza metricas via Insights API
  - Interest search via `/search?type=adinterest`
  - Rate limiting: rolling 1-hour window per ad account
- [ ] **TikTok Ads Adapter** (implementa `AdPlatformInterface`):
  - SDK: `promopult/tiktok-marketing-api` ou HTTP direto
  - OAuth 2.0 via TikTok Marketing API
  - Cria Campaign → Ad Group (com targeting) → Ad Creative
  - Sincroniza metricas via Reporting API
  - Rate limiting: 1-minute sliding window
- [ ] **Google Ads Adapter** (implementa `AdPlatformInterface`):
  - SDK: `googleads/google-ads-php`
  - OAuth 2.0 + developer token
  - Cria Campaign → Ad Group (com CampaignCriterion/AdGroupCriterion) → Ad
  - Sincroniza metricas via Google Ads Reporting
  - Rate limiting: tiered developer token access
- [ ] `AdPlatformFactory` — resolve adapter por provider
- [ ] Jobs:
  - `CreateAdBoostJob` (queue: high, retry: 3, backoff: 60s/300s/900s)
  - `SyncAdStatusJob` (scheduler: a cada 30min para boosts ativos)
  - `SyncAdMetricsJob` (scheduler: a cada 1h ativos, a cada 6h finalizados)
  - `RefreshAdAccountTokenJob` (scheduler: antes da expiracao)
  - `ExportSpendingReportJob` (queue: low)
- [ ] Controllers: `AdAccountController`, `AudienceController`, `AdBoostController`, `AdAnalyticsController`
- [ ] Feature gate middleware: Professional+ para Meta Ads, Agency para TikTok/Google Ads
- [ ] Scheduler: sync status (30min), sync metricas (1h/6h), refresh tokens (12h)

### 17.4 Testes

- [ ] Unit: AdAccount entity, AdStatus transitions, AdBudget VO, TargetingSpec VO
- [ ] Unit: Audience entity, DemographicFilter, LocationFilter, InterestFilter
- [ ] Unit: AdBoost entity, status transitions, budget validation
- [ ] Unit: Todos os Use Cases (com mocks de AdPlatformInterface)
- [ ] Integration: Meta Ads adapter (HTTP mock)
- [ ] Integration: TikTok Ads adapter (HTTP mock)
- [ ] Integration: Google Ads adapter (HTTP mock)
- [ ] Integration: AdPlatformFactory
- [ ] Feature: Conexao de conta de anuncios (OAuth flow)
- [ ] Feature: CRUD de audiencias com targeting spec
- [ ] Feature: Criar e cancelar boost
- [ ] Feature: Metricas de anuncios
- [ ] Feature: Historico de gastos e exportacao
- [ ] Feature: Feature gate (Professional vs Agency)
- [ ] Feature: Isolamento por organization_id

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

- [ ] `AdPerformanceInsight` entity (AI Intelligence BC)
- [ ] Value Objects: `AdInsightType` (best_audiences, best_content_for_ads, organic_vs_paid_correlation)
- [ ] Domain Events: `AdPerformanceAggregated`, `AdAIContextEnriched`, `AdTargetingSuggested`
- [ ] Contracts: `AdIntelligenceProviderInterface`

### 18.2 Application Layer

- [ ] Use Cases:
  - `AggregateAdPerformanceUseCase` — agrega metricas de ads por audiencia, conteudo, horario
  - `EnrichAIContextFromAdsUseCase` — injeta dados de ads no ai_generation_context
  - `GetAdTargetingSuggestionsUseCase` — sugere targeting baseado em performance historica
  - `GetAdPerformanceInsightsUseCase` — retorna insights de ads para o usuario
- [ ] Expansao dos Use Cases de geracao (Sprint 3) para injetar contexto de ads
- [ ] DTOs para input/output

### 18.3 Infrastructure Layer

- [ ] Migration: `ad_performance_insights` table
- [ ] Migration: `ALTER TABLE ai_generation_context ADD context_type 'ad_performance'`
- [ ] Atualizar `RAGContextProvider` com ad performance boost logic
- [ ] Atualizar `UpdateLearningContextJob` para incluir dados de ads
- [ ] Jobs:
  - `AggregateAdPerformanceJob` (semanal — agrupa metricas por audiencia/conteudo)
  - `EnrichAIContextFromAdsJob` (pos-agregacao — atualiza ai_generation_context)
  - `GenerateAdTargetingSuggestionsJob` (on-demand — ao criar novo boost)
- [ ] Listeners:
  - `AdMetricsSynced` → schedule aggregation
  - `AdPerformanceAggregated` → enrich AI context
  - `BoostCreated` → generate targeting suggestions
- [ ] Controllers: `AdIntelligenceController`
- [ ] Endpoints:
  - `GET /api/v1/ads/intelligence/insights` — insights de performance de ads
  - `GET /api/v1/ads/intelligence/targeting-suggestions` — sugestoes de targeting para novo boost
- [ ] Feature gate: Exclusivo organizacoes com conta de anuncios conectada + Professional+

### 18.4 Testes

- [ ] Unit: AdPerformanceInsight entity, AdInsightType VO
- [ ] Unit: Todos os Use Cases (com mocks)
- [ ] Integration: Agregacao de metricas de ads
- [ ] Integration: Injecao de contexto de ads nos prompts de geracao
- [ ] Feature: Insights de ads (endpoint)
- [ ] Feature: Sugestoes de targeting (endpoint)
- [ ] Feature: Geracao enriquecida com contexto de ads (campo `ad_context_used`)
- [ ] Feature: Feature gate (somente com ads account conectado)
- [ ] Feature: Graceful degradation (sem dados de ads, skip silencioso)
- [ ] Feature: Isolamento por organization_id

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

- [ ] Criar diretorio `ai-agents/` com estrutura do projeto Python
- [ ] `Dockerfile` multi-stage (Python 3.12-slim, appuser com UID/GID dinamico)
- [ ] `requirements.txt` (langgraph, langchain, fastapi, uvicorn, httpx, asyncpg, redis, structlog)
- [ ] `docker-compose.yml`: adicionar servico `ai-agents` na rede `social-media-net`
- [ ] FastAPI app com health check (`/health`, `/ready`)
- [ ] Configuracao via env vars (API keys, DATABASE_URL, REDIS_URL, CALLBACK_BASE_URL)
- [ ] Redis DB 4 para checkpoints e job status do LangGraph
- [ ] Logs estruturados em JSON (compativel com padrao Laravel)
- [ ] Testes: health check, conectividade Redis/PostgreSQL

### 19.2 Pipeline: Content Creation

- [ ] LangGraph StateGraph: `ContentCreationState`
- [ ] Agente `Planner` — define tom, estrutura, publico, CTA, constraints
- [ ] Agente `Writer` — gera conteudo seguindo briefing do Planner
- [ ] Agente `Reviewer` — verifica brand safety, tom, guidelines, qualidade
- [ ] Agente `Optimizer` — otimiza por rede social (hashtags, CTA, tamanho, midia)
- [ ] Conditional edge: Reviewer reprovado → Writer retry (max 2 retries)
- [ ] Injecao de contexto: style_profile (ADR-017 N5), rag_examples (ADR-017 N2)
- [ ] Endpoint: `POST /api/v1/pipelines/content-creation`
- [ ] Callback ao Laravel com resultado final + metadata (tokens, custo, duracao)
- [ ] Testes: graph completo, retry loop, fallback

### 19.3 Pipeline: Content DNA Deep Analysis

- [ ] LangGraph StateGraph: `ContentDNAState`
- [ ] Agente `StyleAnalyzer` — analisa tom, vocabulario, estrutura, padroes
- [ ] Agente `EngagementAnalyzer` — correlaciona metricas com padroes de conteudo
- [ ] Agente `ProfileSynthesizer` — combina analises em perfil multidimensional
- [ ] Injecao de dados: conteudos publicados, metricas, embeddings da organizacao
- [ ] Endpoint: `POST /api/v1/pipelines/content-dna`
- [ ] Callback ao Laravel com perfil enriquecido
- [ ] Testes: graph completo, dados insuficientes

### 19.4 Pipeline: Social Listening Intelligence

- [ ] LangGraph StateGraph: `SocialListeningState`
- [ ] Agente `MentionClassifier` — categoriza mencao (elogio, reclamacao, pergunta, crise, spam)
- [ ] Agente `SentimentAnalyzer` — analise profunda com contexto cultural e ironia
- [ ] Agente `ResponseStrategist` — sugere resposta contextualizada com tom adequado
- [ ] Agente `SafetyChecker` — verifica brand safety antes de retornar
- [ ] Tratamento diferenciado por categoria (crise = processamento profundo)
- [ ] Endpoint: `POST /api/v1/pipelines/social-listening`
- [ ] Callback ao Laravel com classificacao, sentimento e resposta sugerida
- [ ] Testes: graph completo, cada categoria de mencao

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

> **Nota:** Sprint 6 (Billing) depende apenas do Sprint 1, podendo ser iniciado em paralelo com Sprints 3-5 se houver capacidade.

> **Nota:** Sprints 10-11 (AI Intelligence Alpha) podem rodar em paralelo com Sprints 8-9, pois dependem apenas dos Sprints 3-5 da Fase 1.

> **Nota:** Sprint 13 (Feedback Loop + Gap Analysis) depende do Sprint 9 (Social Listening) para dados de concorrentes e do Sprint 12 para o pipeline de embeddings.

> **Nota:** Sprint 14 (AI Learning Loop) depende do Sprint 3 (Content AI base), Sprint 5 (Analytics para metricas), Sprint 12 (embeddings para RAG) e Sprint 13 (audience insights para contexto).

> **Nota:** Sprint 15 (CRM Connectors Fase 1) depende do Sprint 2 (Social Account — OAuth patterns), Sprint 5 (Engagement & Automation — webhooks/comentarios) e Sprint 6 (Billing — feature gates). Pode rodar em paralelo com Sprints 12-14 se houver capacidade.

> **Nota:** Sprint 16 (CRM Fase 2 + CRM Intelligence) depende do Sprint 15 (infraestrutura CRM) e Sprint 14 (Learning Loop) para conectar dados de conversao CRM ao pipeline de IA.

> **Nota:** Sprint 17 (Paid Advertising Core) depende do Sprint 4 (Publishing — posts publicados para boost), Sprint 5 (Analytics — metricas para comparativo) e Sprint 6 (Billing — feature gates por plano). Pode rodar em paralelo com Sprints 15-16.

> **Nota:** Sprint 18 (AI Learning from Ads) depende do Sprint 14 (Learning Loop — pipeline de aprendizado) e Sprint 17 (Paid Advertising — dados de ads).

> **Nota:** Sprint 19 (Multi-Agent AI Pipelines) depende de toda a infraestrutura de IA (Sprints 12-14), Social Listening (Sprint 9), CRM Intelligence (Sprint 16) e Ad Learning (Sprint 18). E o culminar de todos os pipelines de IA em agentes especializados.

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

| | Migrations | Endpoints | Use Cases | Jobs | Testes (aprox) |
|--|-----------|-----------|-----------|------|---------------|
| **Total Geral** | **67** | **~203** | **~230** | **64** | **~1080** |

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
