# 05 — Bounded Contexts (DDD)

[← Voltar ao índice](00-index.md)

---

## 5.1 Context Map

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           Social Media Manager                               │
│                                                                              │
│  ┌──────────────┐    ┌──────────────────┐    ┌──────────────────┐            │
│  │   Identity   │    │  Social Account  │    │    Campaign      │            │
│  │   & Access   │───▶│   Management     │    │   Management     │            │
│  └──────┬───────┘    └────────┬─────────┘    └────────┬─────────┘            │
│         │                     │                       │                      │
│         │                     ▼                       ▼                      │
│         │            ┌──────────────────┐    ┌──────────────────┐            │
│         │            │   Publishing     │◀───│   Content AI     │            │
│         │            │                  │    │                  │            │
│         │            └────────┬─────────┘    └────────┬─────────┘            │
│         │                     │                       │                      │
│         │           ┌─────────┼──────────┐            │                      │
│         │           ▼                    ▼            │                      │
│         │   ┌──────────────────┐ ┌──────────────────┐ │                      │
│         │   │   Analytics      │ │   Engagement     │ │                      │
│         │   │                  │ │   & Automation   │ │                      │
│         │   └────────┬─────────┘ └────────┬─────────┘ │                      │
│         │            │                    │           │                      │
│         │            │    ┌───────────────┼───────────┘                      │
│         │            │    │               │                                  │
│         │            ▼    ▼               ▼                                  │
│         │   ┌──────────────────────────────────────┐                         │
│         │   │         AI Intelligence              │ (Fase 2-3)              │
│         │   │  DNA · Prediction · Best Time ·      │                         │
│         │   │  Feedback · Safety · Gap Analysis    │                         │
│         │   └──────────────────────────────────────┘                         │
│         │                                                                    │
│         │   ┌──────────────────┐    ┌──────────────────┐                     │
│         └──▶│   Media          │    │ Social Listening │ (Fase 2)            │
│             │   Management     │    │                  │                     │
│             └──────────────────┘    └──────────────────┘                     │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Relações entre contextos

| Upstream | Downstream | Tipo de relação | Descrição |
|----------|-----------|-----------------|-----------|
| Identity & Access | Todos | **Authentication Gateway** | Todos os contextos dependem da autenticação |
| Social Account | Publishing | **Conformist** | Publishing consome tokens e dados do Social Account sem modificá-los |
| Social Account | Analytics | **Conformist** | Analytics usa credenciais para buscar métricas |
| Social Account | Engagement | **Conformist** | Engagement usa credenciais para buscar e publicar comentários |
| Social Account | Social Listening | **Conformist** | Listening usa credenciais para buscar menções (Fase 3) |
| Campaign | Publishing | **Customer-Supplier** | Campaign fornece conteúdos, Publishing agenda e publica |
| Campaign | Analytics | **Customer-Supplier** | Campaign fornece referência de conteúdos para métricas |
| Campaign | Client Financial Mgmt | **Customer-Supplier** | Campanhas são base para alocação de custos por cliente (Fase 2) |
| Content AI | Campaign | **Conformist** | Campaign consome conteúdos gerados pelo Content AI |
| Publishing | Analytics | **Published Language** | Publishing emite eventos que Analytics consome |
| Publishing | Engagement | **Published Language** | Publishing emite eventos que Engagement consome |
| Media | Campaign | **Shared Kernel** | Mídias são referenciadas pelas peças de conteúdo |
| Analytics | Client Financial Mgmt | **Customer-Supplier** | Analytics fornece dados de uso para alocação de custos (Fase 2) |
| Engagement | Social Listening | **Shared Kernel** | Reutiliza modelo de sentimento e patterns de captura (Fase 2) |
| Billing | Client Financial Mgmt | **Shared Kernel** | Reutiliza Money VO e patterns financeiros (Fase 2) |
| Analytics | AI Intelligence | **Customer-Supplier** | Fornece métricas de engajamento e séries temporais para análise (Fase 2-3) |
| Engagement | AI Intelligence | **Customer-Supplier** | Fornece comentários com sentimento e embeddings para Feedback Loop (Fase 3) |
| Social Listening | AI Intelligence | **Customer-Supplier** | Fornece menções de concorrentes para Gap Analysis (Fase 3) |
| Content AI | AI Intelligence | **Conformist** | AI Intelligence consome dados de gerações para análise de padrões (Fase 2-3) |
| AI Intelligence | Content AI | **Published Language** | Insights e contexto de audiência injetados em prompts de geração (Fase 3) |
| AI Intelligence | Publishing | **Published Language** | Prediction scores e safety checks consultados pré-publicação (Fase 2-3) |

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
├── organization_id: OrganizationId (tenant — a conta pertence à organização, não ao usuário)
├── connected_by_user_id: UserId (quem realizou o OAuth — audit trail)
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

---

## 5.10 Bounded Context: Client Financial Management (Fase 2)

> **Nota:** Este contexto será implementado na Fase 2 (Sprint 8). Trata da gestão financeira que **agências e gestores** fazem com seus **próprios clientes** — diferente do Billing & Subscription (Sprint 6), que trata da cobrança do SaaS à organização.

### Responsabilidades
- Cadastro e gestão de clientes da agência
- Alocação de custos por cliente (campanhas, IA, mídia, publicações)
- Geração de faturas para clientes
- Controle de pagamentos recebidos
- Relatórios financeiros (receita, lucratividade, custos)

### Agregados

#### Client (Aggregate Root)
```
Client
├── id: ClientId (UUID)
├── organization_id: OrganizationId
├── name: string
├── email: ?Email (Value Object)
├── phone: ?Phone (Value Object)
├── company_name: ?string
├── tax_id: ?TaxId (Value Object — CPF/CNPJ)
├── billing_address: ?Address (Value Object)
├── notes: ?string
├── status: ClientStatus (Enum: active, inactive, archived)
├── created_at: DateTimeImmutable
├── updated_at: DateTimeImmutable
└── deleted_at: ?DateTimeImmutable
```

#### ClientContract (Aggregate Root)
```
ClientContract
├── id: ContractId (UUID)
├── client_id: ClientId
├── organization_id: OrganizationId
├── name: string
├── type: ContractType (Enum: fixed_monthly, per_campaign, per_post, hourly)
├── value_cents: int
├── currency: Currency (Value Object)
├── starts_at: DateTimeImmutable
├── ends_at: ?DateTimeImmutable
├── social_account_ids: SocialAccountId[] (contas vinculadas ao contrato)
├── status: ContractStatus (Enum: active, paused, completed, cancelled)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### ClientInvoice (Aggregate Root)
```
ClientInvoice
├── id: InvoiceId (UUID)
├── client_id: ClientId
├── contract_id: ?ContractId
├── organization_id: OrganizationId
├── reference_month: YearMonth (Value Object)
├── items: InvoiceItem[] (Entity)
│   ├── description: string
│   ├── quantity: int
│   ├── unit_price_cents: int
│   └── total_cents: int
├── subtotal_cents: int
├── discount_cents: int
├── total_cents: int
├── currency: Currency (Value Object)
├── status: InvoiceStatus (Enum: draft, sent, paid, overdue, cancelled)
├── due_date: DateOnly
├── paid_at: ?DateTimeImmutable
├── sent_at: ?DateTimeImmutable
├── notes: ?string
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### CostAllocation (Entity)
```
CostAllocation
├── id: AllocationId (UUID)
├── client_id: ClientId
├── organization_id: OrganizationId
├── resource_type: ResourceType (Enum: campaign, ai_generation, media_storage, publication)
├── resource_id: UUID (referência ao recurso específico)
├── description: string
├── cost_cents: int
├── currency: Currency (Value Object)
├── allocated_at: DateTimeImmutable
```

### Value Objects
- **ClientId** — UUID wrapper
- **ContractId** — UUID wrapper
- **InvoiceId** — UUID wrapper
- **TaxId** — Validação de CPF/CNPJ
- **Address** — Rua, número, complemento, cidade, estado, CEP
- **Currency** — Enum: BRL, USD, EUR
- **YearMonth** — Ano-mês (ex: 2026-03)
- **ContractType** — Enum com regras de cálculo por tipo
- **InvoiceStatus** — Enum com transições válidas

### Domain Events
- `ClientCreated { clientId, organizationId, name, createdAt }`
- `ClientUpdated { clientId, changes, updatedAt }`
- `ClientArchived { clientId, archivedAt }`
- `ContractCreated { contractId, clientId, type, valueCents, createdAt }`
- `ContractCompleted { contractId, completedAt }`
- `InvoiceGenerated { invoiceId, clientId, totalCents, referenceMonth }`
- `InvoiceSent { invoiceId, sentAt }`
- `InvoiceMarkedPaid { invoiceId, paidAt }`
- `InvoiceOverdue { invoiceId, dueDate }`
- `CostAllocated { allocationId, clientId, resourceType, costCents }`

---

## 5.11 Bounded Context: Social Listening (Fase 3)

> **Nota:** Este contexto será implementado na Fase 3 (Sprint 9). Permite monitorar menções à marca, keywords, hashtags e concorrentes **fora do conteúdo próprio da organização** nas redes sociais.

### Responsabilidades
- Configuração de queries de monitoramento (keywords, hashtags, menções, concorrentes)
- Captura e indexação de menções externas
- Análise de sentimento de menções (reutiliza infraestrutura do Engagement)
- Alertas configuráveis por condição (spike de volume, sentimento negativo)
- Dashboards de listening e relatórios de tendências
- Monitoramento de concorrentes

### Agregados

#### ListeningQuery (Aggregate Root)
```
ListeningQuery
├── id: QueryId (UUID)
├── organization_id: OrganizationId
├── name: string
├── type: QueryType (Enum: keyword, hashtag, mention, competitor)
├── value: string (ex: "social media manager", "#marketingdigital", "@concorrente")
├── platforms: SocialProvider[] (redes a monitorar)
├── language_filter: ?Language (Value Object)
├── is_active: bool
├── last_fetched_at: ?DateTimeImmutable
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### Mention (Aggregate Root)
```
Mention
├── id: MentionId (UUID)
├── query_id: QueryId
├── organization_id: OrganizationId
├── platform: SocialProvider
├── external_id: string (ID na rede social)
├── author_name: string
├── author_username: string
├── author_external_id: string
├── author_followers_count: ?int
├── content_text: string
├── content_url: ?string
├── media_urls: string[]
├── sentiment: Sentiment (Enum: positive, neutral, negative)
├── reach_estimate: ?int
├── engagement_count: ?int (likes + comments + shares)
├── mentioned_at: DateTimeImmutable
├── captured_at: DateTimeImmutable
├── is_read: bool
└── flagged: bool (destaque manual pelo usuário)
```

#### ListeningAlert (Aggregate Root)
```
ListeningAlert
├── id: AlertId (UUID)
├── organization_id: OrganizationId
├── name: string
├── query_ids: QueryId[] (queries monitoradas por este alerta)
├── condition: AlertCondition (Value Object)
│   ├── type: ConditionType (Enum: volume_spike, negative_sentiment_spike, keyword_detected, influencer_mention)
│   ├── threshold: int (ex: 50 menções/hora, sentimento < 0.3)
│   └── window_minutes: int (janela de avaliação)
├── notification_channels: NotificationChannel[] (Value Object)
│   ├── type: ChannelType (Enum: email, webhook, in_app)
│   └── target: string (email, URL, ou user_id)
├── is_active: bool
├── last_triggered_at: ?DateTimeImmutable
├── cooldown_minutes: int (evitar alertas repetidos)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### ListeningReport (Entity)
```
ListeningReport
├── id: ReportId (UUID)
├── organization_id: OrganizationId
├── query_ids: QueryId[]
├── period_start: DateTimeImmutable
├── period_end: DateTimeImmutable
├── total_mentions: int
├── sentiment_breakdown: SentimentBreakdown (Value Object)
│   ├── positive_count: int
│   ├── neutral_count: int
│   └── negative_count: int
├── top_authors: array
├── platform_breakdown: array
├── trend_data: array (série temporal de menções)
├── format: ExportFormat (Enum: pdf, csv)
├── file_path: ?string
├── status: ExportStatus (Enum: processing, ready, expired)
├── created_at: DateTimeImmutable
```

### Value Objects
- **QueryId** — UUID wrapper
- **MentionId** — UUID wrapper
- **AlertId** — UUID wrapper
- **QueryType** — Enum com regras de busca por tipo
- **AlertCondition** — Condição composta (tipo + threshold + janela)
- **NotificationChannel** — Canal + target de notificação
- **SentimentBreakdown** — Contagem agregada por sentimento

### Domain Events
- `ListeningQueryCreated { queryId, organizationId, type, value, createdAt }`
- `ListeningQueryPaused { queryId, pausedAt }`
- `ListeningQueryResumed { queryId, resumedAt }`
- `MentionDetected { mentionId, queryId, platform, sentiment, mentionedAt }`
- `MentionFlagged { mentionId, flaggedBy, flaggedAt }`
- `ListeningAlertTriggered { alertId, conditionType, value, triggeredAt }`
- `ListeningReportGenerated { reportId, organizationId, totalMentions, generatedAt }`
- `SentimentSpikeDetected { organizationId, queryId, sentiment, count, windowMinutes }`

---

## 5.12 Bounded Context: AI Intelligence (Fase 2-3)

> **Nota:** Este contexto será implementado nas Fases 2 (Sprints 10-11) e 3 (Sprints 12-13). Abrange funcionalidades de **análise inteligente e insights** que consomem dados de Analytics, Engagement e Social Listening para produzir recomendações acionáveis — diferente do Content AI (Sprint 3), que gera conteúdo textual sob demanda.

### Responsabilidades
- Cálculo de horários ótimos de publicação (Best Time to Post)
- Verificação de segurança de marca antes de publicar (Brand Safety)
- Geração de perfil de conteúdo da organização via embeddings (Content DNA)
- Predição de performance pré-publicação (Performance Prediction)
- Análise de feedback da audiência para enriquecer geração de conteúdo (Feedback Loop)
- Identificação de lacunas de conteúdo vs concorrentes (Gap Analysis)
- Pipeline de embeddings para conteúdos e comentários

### Agregados

#### ContentProfile (Aggregate Root)
```
ContentProfile
├── id: ProfileId (UUID)
├── organization_id: OrganizationId
├── social_account_id: ?SocialAccountId
├── provider: ?SocialProvider (null = todas as redes)
├── total_contents_analyzed: int
├── top_themes: ThemeScore[] (Value Object)
│   ├── theme: string
│   ├── score: float
│   └── content_count: int
├── engagement_patterns: EngagementPattern (Value Object)
│   ├── avg_likes: float
│   ├── avg_comments: float
│   ├── avg_shares: float
│   └── best_content_types: string[]
├── content_fingerprint: ContentFingerprint (Value Object)
│   ├── avg_length: int
│   ├── hashtag_patterns: string[]
│   ├── tone_distribution: array
│   └── posting_frequency: float
├── high_performer_traits: array
├── centroid_embedding: ?Vector (VECTOR(1536))
├── generated_at: DateTimeImmutable
├── expires_at: DateTimeImmutable (TTL 7 dias)
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### PerformancePrediction (Aggregate Root)
```
PerformancePrediction
├── id: PredictionId (UUID)
├── organization_id: OrganizationId
├── content_id: ContentId
├── provider: SocialProvider
├── overall_score: PredictionScore (Value Object, 0-100)
├── breakdown: PredictionBreakdown (Value Object)
│   ├── content_similarity: int
│   ├── timing: int
│   ├── hashtags: int
│   ├── length: int
│   └── media_type: int
├── similar_content_ids: ContentId[] (top 5 referências)
├── recommendations: Recommendation[] (Value Object)
│   ├── type: string
│   ├── message: string
│   └── impact_estimate: string
├── model_version: string
└── created_at: DateTimeImmutable
```

#### PostingTimeRecommendation (Aggregate Root)
```
PostingTimeRecommendation
├── id: RecommendationId (UUID)
├── organization_id: OrganizationId
├── social_account_id: ?SocialAccountId
├── provider: ?SocialProvider
├── day_of_week: ?int (0=Sunday...6=Saturday, null = todos)
├── heatmap: TimeSlotScore[] (Value Object)
│   ├── hour: int (0-23)
│   └── score: int (0-100)
├── top_slots: TopSlot[] (Value Object)
│   ├── day: int
│   ├── hour: int
│   ├── avg_engagement_rate: float
│   └── sample_size: int
├── worst_slots: TopSlot[]
├── sample_size: int
├── confidence_level: ConfidenceLevel (Enum: low, medium, high)
├── calculated_at: DateTimeImmutable
├── expires_at: DateTimeImmutable (TTL 7 dias)
└── created_at: DateTimeImmutable
```

#### AudienceInsight (Aggregate Root)
```
AudienceInsight
├── id: InsightId (UUID)
├── organization_id: OrganizationId
├── social_account_id: ?SocialAccountId
├── insight_type: InsightType (Enum: preferred_topics, sentiment_trends, engagement_drivers, audience_preferences)
├── insight_data: array (structured insight data)
├── source_comment_count: int
├── period_start: DateTimeImmutable
├── period_end: DateTimeImmutable
├── confidence_score: ?float (0.0-1.0)
├── generated_at: DateTimeImmutable
├── expires_at: DateTimeImmutable (TTL 7 dias)
└── created_at: DateTimeImmutable
```

#### BrandSafetyCheck (Aggregate Root)
```
BrandSafetyCheck
├── id: CheckId (UUID)
├── organization_id: OrganizationId
├── content_id: ContentId
├── provider: ?SocialProvider (null = verificação geral)
├── overall_status: SafetyStatus (Enum: pending, passed, warning, blocked)
├── overall_score: ?int (0-100, 100 = totalmente seguro)
├── checks: SafetyCheckResult[] (Value Object)
│   ├── category: string (lgpd_compliance, advertising_disclosure, platform_policy, sensitivity, profanity)
│   ├── status: SafetyStatus
│   ├── message: string
│   └── severity: string
├── model_used: ?string
├── tokens_input: ?int
├── tokens_output: ?int
├── checked_at: ?DateTimeImmutable
└── created_at: DateTimeImmutable
```

#### BrandSafetyRule (Aggregate Root)
```
BrandSafetyRule
├── id: RuleId (UUID)
├── organization_id: OrganizationId
├── rule_type: SafetyRuleType (Enum: blocked_word, required_disclosure, custom_check)
├── rule_config: array (words[], pattern, message)
├── severity: RuleSeverity (Enum: warning, block)
├── is_active: bool
├── created_at: DateTimeImmutable
└── updated_at: DateTimeImmutable
```

#### CalendarSuggestion (Aggregate Root)
```
CalendarSuggestion
├── id: SuggestionId (UUID)
├── organization_id: OrganizationId
├── period_start: DateOnly
├── period_end: DateOnly
├── suggestions: CalendarItem[] (Value Object)
│   ├── date: DateOnly
│   ├── topics: string[]
│   ├── content_type: string
│   ├── target_networks: SocialProvider[]
│   ├── reasoning: string
│   └── priority: int
├── based_on: array (top_performers, gaps, trends, existing_schedule)
├── status: SuggestionStatus (Enum: generated, reviewed, accepted, expired)
├── accepted_items: ?array
├── generated_at: DateTimeImmutable
├── expires_at: DateTimeImmutable (TTL 7 dias)
└── created_at: DateTimeImmutable
```

#### ContentGapAnalysis (Aggregate Root)
```
ContentGapAnalysis
├── id: AnalysisId (UUID)
├── organization_id: OrganizationId
├── competitor_query_ids: QueryId[] (listening_queries tipo competitor)
├── analysis_period_start: DateTimeImmutable
├── analysis_period_end: DateTimeImmutable
├── our_topics: TopicAnalysis[] (Value Object)
│   ├── topic: string
│   ├── frequency: int
│   └── avg_engagement: float
├── competitor_topics: CompetitorTopic[] (Value Object)
│   ├── topic: string
│   ├── source_competitor: string
│   ├── frequency: int
│   └── avg_engagement: float
├── gaps: ContentGap[] (Value Object)
│   ├── topic: string
│   ├── opportunity_score: int
│   ├── competitor_count: int
│   └── recommendation: string
├── opportunities: Opportunity[] (Value Object)
│   ├── topic: string
│   ├── reason: string
│   ├── suggested_content_type: string
│   └── estimated_impact: string
├── generated_at: DateTimeImmutable
├── expires_at: DateTimeImmutable (TTL 7 dias)
└── created_at: DateTimeImmutable
```

### Value Objects
- **ProfileId**, **PredictionId**, **RecommendationId**, **InsightId**, **CheckId**, **SuggestionId**, **AnalysisId** — UUID wrappers
- **PredictionScore** — Int 0-100 com validação
- **EngagementPattern** — Padrão de engajamento agregado
- **ContentFingerprint** — Características estatísticas do conteúdo
- **TimeSlotScore** — Hora + score de engajamento
- **TopSlot** — Slot com dados de performance
- **ConfidenceLevel** — Enum: low (<10 posts), medium (10-50), high (>50)
- **InsightType** — Enum: preferred_topics, sentiment_trends, engagement_drivers, audience_preferences
- **SafetyStatus** — Enum: pending, passed, warning, blocked
- **SafetyRuleType** — Enum: blocked_word, required_disclosure, custom_check
- **RuleSeverity** — Enum: warning, block
- **SuggestionStatus** — Enum: generated, reviewed, accepted, expired
- **GapCategory** — Categorização de lacunas de conteúdo

### Domain Events
- `ContentProfileGenerated { profileId, organizationId, totalAnalyzed, generatedAt }`
- `PredictionCalculated { predictionId, contentId, provider, score, calculatedAt }`
- `PostingTimesUpdated { recommendationId, organizationId, provider, calculatedAt }`
- `AudienceInsightsRefreshed { insightId, organizationId, insightType, commentCount, refreshedAt }`
- `BrandSafetyChecked { checkId, contentId, overallStatus, score, checkedAt }`
- `BrandSafetyBlocked { checkId, contentId, blockedCategories, checkedAt }`
- `CalendarSuggestionGenerated { suggestionId, organizationId, periodStart, periodEnd, itemCount }`
- `CalendarItemsAccepted { suggestionId, acceptedCount, acceptedAt }`
- `ContentGapsIdentified { analysisId, organizationId, gapCount, opportunityCount, generatedAt }`
- `EmbeddingGenerated { entityType, entityId, model, tokensUsed, generatedAt }`
