# 05 — Bounded Contexts (DDD)

[← Voltar ao índice](00-index.md)

---

## 5.1 Context Map

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Social Media Manager                         │
│                                                                     │
│  ┌──────────────┐    ┌──────────────────┐    ┌──────────────────┐  │
│  │   Identity    │    │  Social Account  │    │    Campaign      │  │
│  │   & Access    │───▶│   Management     │    │   Management     │  │
│  │              │    │                  │    │                  │  │
│  └──────┬───────┘    └────────┬─────────┘    └────────┬─────────┘  │
│         │                     │                       │            │
│         │                     ▼                       ▼            │
│         │            ┌──────────────────┐    ┌──────────────────┐  │
│         │            │   Publishing     │◀───│   Content AI     │  │
│         │            │                  │    │                  │  │
│         │            └────────┬─────────┘    └──────────────────┘  │
│         │                     │                                    │
│         │           ┌─────────┼──────────┐                        │
│         │           ▼                    ▼                         │
│         │   ┌──────────────────┐ ┌──────────────────┐             │
│         │   │   Analytics      │ │   Engagement     │             │
│         │   │                  │ │   & Automation   │             │
│         │   └──────────────────┘ └──────────────────┘             │
│         │                                                         │
│         │   ┌──────────────────┐                                  │
│         └──▶│   Media          │                                  │
│             │   Management     │                                  │
│             └──────────────────┘                                  │
└─────────────────────────────────────────────────────────────────────┘
```

### Relações entre contextos

| Upstream | Downstream | Tipo de relação | Descrição |
|----------|-----------|-----------------|-----------|
| Identity & Access | Todos | **Authentication Gateway** | Todos os contextos dependem da autenticação |
| Social Account | Publishing | **Conformist** | Publishing consome tokens e dados do Social Account sem modificá-los |
| Social Account | Analytics | **Conformist** | Analytics usa credenciais para buscar métricas |
| Social Account | Engagement | **Conformist** | Engagement usa credenciais para buscar e publicar comentários |
| Campaign | Publishing | **Customer-Supplier** | Campaign fornece conteúdos, Publishing agenda e publica |
| Campaign | Analytics | **Customer-Supplier** | Campaign fornece referência de conteúdos para métricas |
| Content AI | Campaign | **Conformist** | Campaign consome conteúdos gerados pelo Content AI |
| Publishing | Analytics | **Published Language** | Publishing emite eventos que Analytics consome |
| Publishing | Engagement | **Published Language** | Publishing emite eventos que Engagement consome |
| Media | Campaign | **Shared Kernel** | Mídias são referenciadas pelas peças de conteúdo |

---

## 5.2 Bounded Context: Identity & Access

### Responsabilidades
- Registro e autenticação de usuários
- Gerenciamento de perfil
- Autorização e políticas de acesso
- Sessões e tokens
- 2FA

### Agregados

#### User (Aggregate Root)
```
User
├── id: UserId (UUID)
├── name: Name (Value Object)
├── email: Email (Value Object)
├── password: HashedPassword (Value Object)
├── phone: ?Phone (Value Object)
├── timezone: Timezone (Value Object)
├── avatar_path: ?string
├── email_verified_at: ?DateTimeImmutable
├── two_factor_enabled: bool
├── two_factor_secret: ?EncryptedString
├── recovery_codes: ?EncryptedString
├── status: UserStatus (Enum: active, inactive, suspended)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### RefreshToken (Entity)
```
RefreshToken
├── id: RefreshTokenId (UUID)
├── user_id: UserId
├── token_hash: string
├── ip_address: IpAddress (Value Object)
├── user_agent: string
├── expires_at: DateTimeImmutable
├── revoked_at: ?DateTimeImmutable
└── created_at: DateTimeImmutable
```

### Value Objects
- **UserId** — UUID wrapper
- **Email** — Validação de formato, normalização (lowercase)
- **Name** — Min 2 chars, max 100 chars
- **HashedPassword** — Encapsula hash bcrypt
- **Phone** — Validação de formato internacional
- **Timezone** — Validação contra lista IANA
- **IpAddress** — IPv4/IPv6

### Domain Events
- `UserRegistered { userId, email, registeredAt }`
- `UserEmailVerified { userId, verifiedAt }`
- `UserLoggedIn { userId, ip, userAgent, loggedInAt }`
- `UserPasswordChanged { userId, changedAt }`
- `TwoFactorEnabled { userId, enabledAt }`
- `TwoFactorDisabled { userId, disabledAt }`

---

## 5.3 Bounded Context: Social Account Management

### Responsabilidades
- Fluxo OAuth2 com redes sociais
- Armazenamento seguro de tokens
- Renovação automática de tokens
- Health check de conexões
- Dados do perfil social

### Agregados

#### SocialAccount (Aggregate Root)
```
SocialAccount
├── id: SocialAccountId (UUID)
├── user_id: UserId
├── provider: SocialProvider (Enum: instagram, tiktok, youtube)
├── provider_user_id: string
├── username: string
├── display_name: string
├── profile_picture_url: ?string
├── access_token: EncryptedToken (Value Object)
├── refresh_token: ?EncryptedToken (Value Object)
├── token_expires_at: ?DateTimeImmutable
├── scopes: string[]
├── status: ConnectionStatus (Enum: connected, expired, revoked, error)
├── last_synced_at: ?DateTimeImmutable
├── connected_at: DateTimeImmutable
├── disconnected_at: ?DateTimeImmutable
└── metadata: array (dados extras do provider)
```

### Value Objects
- **SocialAccountId** — UUID wrapper
- **SocialProvider** — Enum com regras específicas por rede
- **EncryptedToken** — Token criptografado com AES-256-GCM
- **ConnectionStatus** — Enum com transições válidas

### Domain Events
- `SocialAccountConnected { socialAccountId, userId, provider, connectedAt }`
- `SocialAccountDisconnected { socialAccountId, userId, provider, disconnectedAt }`
- `SocialAccountTokenRefreshed { socialAccountId, refreshedAt }`
- `SocialAccountTokenExpired { socialAccountId, expiredAt }`
- `SocialAccountConnectionFailed { socialAccountId, error, failedAt }`

---

## 5.4 Bounded Context: Campaign Management

### Responsabilidades
- CRUD de campanhas
- CRUD de peças de conteúdo
- Organização e status tracking
- Duplicação de campanhas

### Agregados

#### Campaign (Aggregate Root)
```
Campaign
├── id: CampaignId (UUID)
├── user_id: UserId
├── name: CampaignName (Value Object)
├── description: ?string
├── starts_at: ?DateTimeImmutable
├── ends_at: ?DateTimeImmutable
├── status: CampaignStatus (Enum: draft, active, paused, completed)
├── tags: Tag[] (Value Object)
├── created_at: DateTimeImmutable
├── updated_at: DateTimeImmutable
└── deleted_at: ?DateTimeImmutable
```

#### Content (Aggregate Root)
```
Content
├── id: ContentId (UUID)
├── campaign_id: CampaignId
├── user_id: UserId
├── title: string
├── body: string
├── hashtags: Hashtag[] (Value Object)
├── media_ids: MediaId[]
├── network_overrides: NetworkOverride[] (Value Object)
│   ├── provider: SocialProvider
│   ├── title: ?string
│   ├── body: ?string
│   └── hashtags: ?Hashtag[]
├── status: ContentStatus (Enum: draft, scheduled, publishing, published, failed, cancelled)
├── ai_generation_id: ?string (referência ao histórico de IA)
├── created_at: DateTimeImmutable
├── updated_at: DateTimeImmutable
└── deleted_at: ?DateTimeImmutable
```

### Value Objects
- **CampaignId** — UUID wrapper
- **ContentId** — UUID wrapper
- **CampaignName** — Min 3 chars, max 100 chars, unique por user
- **Tag** — String normalizada (lowercase, trim)
- **Hashtag** — Validação de formato (#palavra)
- **NetworkOverride** — Customização por rede

### Domain Events
- `CampaignCreated { campaignId, userId, name, createdAt }`
- `CampaignUpdated { campaignId, changes, updatedAt }`
- `CampaignDeleted { campaignId, deletedAt }`
- `CampaignDuplicated { originalId, newId, duplicatedAt }`
- `ContentCreated { contentId, campaignId, createdAt }`
- `ContentUpdated { contentId, changes, updatedAt }`
- `ContentDeleted { contentId, deletedAt }`

---

## 5.5 Bounded Context: Content AI

### Responsabilidades
- Geração de títulos, descrições e hashtags
- Configuração de tom de voz
- Histórico de gerações
- Controle de uso de tokens

### Agregados

#### AIGeneration (Aggregate Root)
```
AIGeneration
├── id: GenerationId (UUID)
├── user_id: UserId
├── type: GenerationType (Enum: title, description, hashtags, full)
├── input: GenerationInput (Value Object)
│   ├── topic: string
│   ├── social_networks: SocialProvider[]
│   ├── tone: ToneOfVoice (Value Object)
│   ├── keywords: string[]
│   └── language: Language (Value Object)
├── output: GenerationOutput (Value Object)
│   ├── suggestions: array
│   └── per_network: array (quando type = full)
├── model_used: string
├── tokens_input: int
├── tokens_output: int
├── duration_ms: int
├── created_at: DateTimeImmutable
```

#### AISettings (Aggregate Root)
```
AISettings
├── user_id: UserId
├── default_tone: ToneOfVoice (Value Object)
├── custom_tone_description: ?string
├── default_language: Language (Value Object)
└── updated_at: DateTimeImmutable
```

### Value Objects
- **GenerationId** — UUID wrapper
- **GenerationType** — Enum
- **ToneOfVoice** — Enum: professional, casual, fun, informative, inspirational, custom
- **Language** — Enum: pt_BR, en_US, es_ES
- **GenerationInput** — Parâmetros de entrada imutáveis
- **GenerationOutput** — Resultado da geração imutável

### Domain Events
- `ContentGenerated { generationId, userId, type, tokensUsed, generatedAt }`
- `AISettingsUpdated { userId, changes, updatedAt }`

---

## 5.6 Bounded Context: Publishing

### Responsabilidades
- Agendamento de publicações
- Fila de processamento
- Publicação via APIs das redes sociais
- Retry e tratamento de falhas
- Calendário de publicações

### Agregados

#### ScheduledPost (Aggregate Root)
```
ScheduledPost
├── id: ScheduledPostId (UUID)
├── content_id: ContentId
├── social_account_id: SocialAccountId
├── user_id: UserId
├── scheduled_at: DateTimeImmutable
├── published_at: ?DateTimeImmutable
├── status: PostStatus (Enum: pending, publishing, published, failed, cancelled)
├── external_post_id: ?string (ID do post na rede social)
├── external_post_url: ?string
├── attempts: int
├── last_error: ?PublishError (Value Object)
│   ├── code: string
│   ├── message: string
│   ├── is_permanent: bool
│   └── occurred_at: DateTimeImmutable
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

### Value Objects
- **ScheduledPostId** — UUID wrapper
- **PostStatus** — Enum com transições válidas:
  - pending → publishing → published
  - pending → cancelled
  - publishing → failed
  - failed → publishing (retry)
- **PublishError** — Erro imutável com classificação

### Domain Events
- `PostScheduled { scheduledPostId, contentId, socialAccountId, scheduledAt }`
- `PostPublishing { scheduledPostId, startedAt }`
- `PostPublished { scheduledPostId, externalPostId, publishedAt }`
- `PostFailed { scheduledPostId, error, attempt, failedAt }`
- `PostCancelled { scheduledPostId, cancelledBy, cancelledAt }`
- `PostRescheduled { scheduledPostId, oldScheduledAt, newScheduledAt }`

---

## 5.7 Bounded Context: Analytics

### Responsabilidades
- Sincronização de métricas das redes sociais
- Armazenamento de séries temporais
- Cálculos de performance
- Geração de relatórios
- Exportação

### Agregados

#### ContentMetrics (Aggregate Root)
```
ContentMetrics
├── id: MetricsId (UUID)
├── content_id: ContentId
├── social_account_id: SocialAccountId
├── provider: SocialProvider
├── external_post_id: string
├── impressions: int
├── reach: int
├── likes: int
├── comments: int
├── shares: int
├── saves: int
├── clicks: int
├── watch_time_seconds: ?int (vídeo)
├── views: ?int (vídeo)
├── engagement_rate: float
├── synced_at: DateTimeImmutable
├── snapshots: MetricSnapshot[] (Entity)
│   ├── captured_at: DateTimeImmutable
│   └── values: array (snapshot dos valores no momento)
```

#### AccountMetrics (Aggregate Root)
```
AccountMetrics
├── id: AccountMetricsId (UUID)
├── social_account_id: SocialAccountId
├── date: DateOnly
├── followers_count: int
├── followers_gained: int
├── followers_lost: int
├── profile_views: ?int
├── reach: ?int
├── impressions: ?int
├── synced_at: DateTimeImmutable
```

#### ReportExport (Entity)
```
ReportExport
├── id: ExportId (UUID)
├── user_id: UserId
├── type: ReportType (Enum: overview, network, content)
├── format: ExportFormat (Enum: pdf, csv)
├── filters: array
├── status: ExportStatus (Enum: processing, ready, expired)
├── file_path: ?string
├── expires_at: ?DateTimeImmutable
├── created_at: DateTimeImmutable
```

### Domain Events
- `MetricsSynced { contentMetricsId, socialAccountId, syncedAt }`
- `ReportExportRequested { exportId, userId, type, format }`
- `ReportExportReady { exportId, filePath, readyAt }`

---

## 5.8 Bounded Context: Engagement & Automation

### Responsabilidades
- Captura de comentários
- Classificação de sentimento
- Respostas manuais e automáticas
- Regras de automação
- Integração com CRM via webhooks

### Agregados

#### Comment (Aggregate Root)
```
Comment
├── id: CommentId (UUID)
├── content_id: ContentId
├── social_account_id: SocialAccountId
├── external_comment_id: string
├── author_name: string
├── author_external_id: string
├── text: string
├── sentiment: Sentiment (Enum: positive, neutral, negative)
├── is_read: bool
├── replied_at: ?DateTimeImmutable
├── replied_by: ?UserId | 'automation'
├── reply_text: ?string
├── commented_at: DateTimeImmutable
├── captured_at: DateTimeImmutable
```

#### AutomationRule (Aggregate Root)
```
AutomationRule
├── id: RuleId (UUID)
├── user_id: UserId
├── name: string
├── priority: int
├── conditions: RuleCondition[] (Value Object)
│   ├── field: string (keyword, sentiment, network, campaign)
│   ├── operator: string (contains, equals, in)
│   └── value: mixed
├── action: RuleAction (Value Object)
│   ├── type: ActionType (Enum: reply_fixed, reply_template, reply_ai, send_webhook)
│   ├── response_template: ?string
│   └── webhook_id: ?WebhookId
├── delay_seconds: int (mínimo 30)
├── daily_limit: int (padrão 100)
├── executions_today: int
├── is_active: bool
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### WebhookEndpoint (Aggregate Root)
```
WebhookEndpoint
├── id: WebhookId (UUID)
├── user_id: UserId
├── name: string
├── url: Url (Value Object)
├── secret: EncryptedString
├── events: WebhookEvent[] (Enum)
├── headers: array
├── is_active: bool
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### WebhookDelivery (Entity)
```
WebhookDelivery
├── id: DeliveryId (UUID)
├── webhook_id: WebhookId
├── event: WebhookEvent
├── payload: array
├── response_status: ?int
├── response_body: ?string
├── attempts: int
├── delivered_at: ?DateTimeImmutable
├── failed_at: ?DateTimeImmutable
├── created_at: DateTimeImmutable
```

### Domain Events
- `CommentCaptured { commentId, contentId, socialAccountId, capturedAt }`
- `CommentReplied { commentId, repliedBy, replyText, repliedAt }`
- `AutomationRuleTriggered { ruleId, commentId, action, triggeredAt }`
- `WebhookDelivered { deliveryId, webhookId, event, deliveredAt }`
- `WebhookDeliveryFailed { deliveryId, webhookId, error, failedAt }`

---

## 5.9 Bounded Context: Media Management

### Responsabilidades
- Upload e validação de mídias
- Armazenamento em object storage
- Geração de thumbnails
- Scan de segurança
- Gerenciamento de ciclo de vida

### Agregados

#### Media (Aggregate Root)
```
Media
├── id: MediaId (UUID)
├── user_id: UserId
├── file_name: string
├── original_name: string
├── mime_type: MimeType (Value Object)
├── file_size: FileSize (Value Object)
├── dimensions: ?Dimensions (Value Object)
│   ├── width: int
│   └── height: int
├── duration_seconds: ?int (vídeo)
├── storage_path: string
├── thumbnail_path: ?string
├── disk: string (storage disk name)
├── checksum: string (SHA-256)
├── scanned_at: ?DateTimeImmutable
├── scan_status: ScanStatus (Enum: pending, clean, infected)
├── created_at: DateTimeImmutable
├── deleted_at: ?DateTimeImmutable
└── purge_at: ?DateTimeImmutable (30 dias após soft delete)
```

### Value Objects
- **MediaId** — UUID wrapper
- **MimeType** — Validação contra whitelist
- **FileSize** — Bytes com validação de limite
- **Dimensions** — Width x Height com validação mínima

### Domain Events
- `MediaUploaded { mediaId, userId, mimeType, fileSize, uploadedAt }`
- `MediaScanned { mediaId, scanStatus, scannedAt }`
- `MediaDeleted { mediaId, deletedAt }`
- `MediaPurged { mediaId, purgedAt }`
