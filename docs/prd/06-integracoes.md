# 06 — Integrações

[← Voltar ao índice](00-index.md)

---

## 6.1 Visão Geral das Integrações

```
┌────────────────────────────────────────────────────────────┐
│                  Social Media Manager API                    │
│                                                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐ │
│  │  OAuth2      │  │  Publishing │  │  Data Sync          │ │
│  │  Manager     │  │  Adapters   │  │  (Analytics +       │ │
│  │              │  │             │  │   Comments)         │ │
│  └──────┬───────┘  └──────┬──────┘  └──────────┬──────────┘ │
└─────────┼─────────────────┼─────────────────────┼────────────┘
          │                 │                     │
     ┌────┴────┐      ┌────┴────┐           ┌────┴────┐
     │ Social  │      │ Social  │           │ Social  │
     │ APIs    │      │ APIs    │           │ APIs    │
     │ (Auth)  │      │ (Post)  │           │ (Read)  │
     └─────────┘      └─────────┘           └─────────┘
```

---

## 6.2 Instagram (via Facebook Graph API)

### Autenticação
- **Tipo:** OAuth 2.0 (Authorization Code Flow)
- **URL de autorização:** `https://www.facebook.com/v21.0/dialog/oauth`
- **URL de token:** `https://graph.facebook.com/v21.0/oauth/access_token`
- **Scopes necessários:**
  - `instagram_basic` — Acesso básico ao perfil
  - `instagram_content_publish` — Publicar conteúdo
  - `instagram_manage_comments` — Ler e responder comentários
  - `instagram_manage_insights` — Métricas e analytics
  - `pages_show_list` — Listar páginas do Facebook
  - `pages_read_engagement` — Leitura de engajamento

### Tokens
- **Short-lived token:** ~1 hora
- **Long-lived token:** ~60 dias
- **Refresh:** Trocar short-lived por long-lived via endpoint `/oauth/access_token`
- **Renovação:** Long-lived tokens podem ser renovados antes de expirar

### Publicação
| Tipo | Endpoint | Método |
|------|----------|--------|
| Imagem (Feed) | `POST /{ig-user-id}/media` + `POST /{ig-user-id}/media_publish` | 2 steps |
| Carrossel | `POST /{ig-user-id}/media` (cada item) + container + publish | 3 steps |
| Reels | `POST /{ig-user-id}/media` (video) + `POST /{ig-user-id}/media_publish` | 2 steps |
| Stories | `POST /{ig-user-id}/media` + `POST /{ig-user-id}/media_publish` | 2 steps |

### Limites
- **Publicação:** 25 posts por dia por conta
- **API calls:** 200 calls por hora por user token
- **Upload de vídeo:** Até 1GB, duração 3s-90min (Reels)

### Métricas disponíveis
- **Conta:** followers_count, media_count, profile_views, reach, impressions
- **Post:** impressions, reach, engagement, saved, likes, comments, shares
- **Story:** impressions, reach, replies, exits
- **Reels:** plays, reach, likes, comments, saves, shares

### Comentários
- **Listar:** `GET /{media-id}/comments`
- **Responder:** `POST /{comment-id}/replies`
- **Webhook:** Disponível via Facebook Webhooks (comments field)

---

## 6.3 TikTok (Content Posting API)

### Autenticação
- **Tipo:** OAuth 2.0 (Authorization Code Flow)
- **URL de autorização:** `https://www.tiktok.com/v2/auth/authorize/`
- **URL de token:** `https://open.tiktokapis.com/v2/oauth/token/`
- **Scopes necessários:**
  - `video.upload` — Upload de vídeos
  - `video.publish` — Publicar vídeos
  - `video.list` — Listar vídeos
  - `comment.list` — Listar comentários
  - `comment.list.manage` — Gerenciar comentários
  - `user.info.basic` — Informações do perfil

### Tokens
- **Access token:** 24 horas
- **Refresh token:** 365 dias
- **Refresh:** `POST /v2/oauth/token/` com `grant_type=refresh_token`

### Publicação
| Passo | Endpoint | Descrição |
|-------|----------|-----------|
| 1 | `POST /v2/post/publish/video/init/` | Iniciar upload |
| 2 | `PUT {upload_url}` | Upload do vídeo (chunked) |
| 3 | `POST /v2/post/publish/status/fetch/` | Verificar status |

### Limites
- **Publicação:** Limites dinâmicos baseados no app approval
- **API calls:** Rate limits por endpoint (varia)
- **Upload:** Vídeos até 4GB, duração 1s-10min

### Métricas disponíveis
- **Vídeo:** view_count, like_count, comment_count, share_count
- **Perfil:** follower_count, following_count, likes_count, video_count

### Comentários
- **Listar:** `POST /v2/comment/list/`
- **Responder:** `POST /v2/comment/reply/`
- **Webhook:** Não disponível (polling obrigatório)

---

## 6.4 YouTube (Data API v3)

### Autenticação
- **Tipo:** OAuth 2.0 (Authorization Code Flow)
- **URL de autorização:** `https://accounts.google.com/o/oauth2/v2/auth`
- **URL de token:** `https://oauth2.googleapis.com/token`
- **Scopes necessários:**
  - `https://www.googleapis.com/auth/youtube.upload` — Upload de vídeos
  - `https://www.googleapis.com/auth/youtube.readonly` — Leitura de dados
  - `https://www.googleapis.com/auth/youtube.force-ssl` — Gerenciar comentários
  - `https://www.googleapis.com/auth/yt-analytics.readonly` — Analytics

### Tokens
- **Access token:** 1 hora
- **Refresh token:** Não expira (exceto se revogado)
- **Refresh:** `POST https://oauth2.googleapis.com/token` com `grant_type=refresh_token`

### Publicação
| Tipo | Endpoint | Método |
|------|----------|--------|
| Vídeo | `POST /youtube/v3/videos` | Resumable upload |
| Short | `POST /youtube/v3/videos` (com flag #Shorts no título) | Resumable upload |

### Limites
- **Quota diária:** 10.000 unidades por dia por projeto
- **Upload:** 1.600 unidades (6 vídeos/dia aproximadamente)
- **Leitura:** 1 unidade por request
- **Upload de vídeo:** Até 256GB, duração até 12 horas

### Métricas disponíveis
- **Canal:** subscribers, views, estimatedMinutesWatched, averageViewDuration
- **Vídeo:** views, likes, dislikes, comments, shares, estimatedMinutesWatched, averageViewDuration, subscribersGained
- **Breakdown:** por dia, país, dispositivo

### Comentários
- **Listar:** `GET /youtube/v3/commentThreads`
- **Responder:** `POST /youtube/v3/comments` (com parentId)
- **Webhook:** Disponível via YouTube Push Notifications (PubSubHubbub)

---

## 6.5 IA Multi-Provider (via Laravel AI SDK — Prism)

> **Referências:** ADR-009 (Laravel AI SDK), ADR-016 (Multi-Provider AI), ADR-017 (AI Learning & Feedback Loop)

### Arquitetura Multi-Provider

O sistema utiliza **5 capabilities de IA**, cada uma com interface própria no Domain e múltiplas implementações por provider na Infrastructure:

| Capability | Interface | Providers (Fase 1) | Providers (Futuro) |
|-----------|-----------|-------------------|-------------------|
| Texto | `TextGeneratorInterface` | OpenAI (GPT-4o, GPT-4o-mini) | Anthropic (Claude), Ollama |
| Imagem | `ImageGeneratorInterface` | — | DALL-E 3, Stability AI, Midjourney |
| Vídeo | `VideoGeneratorInterface` | — | Sora, Runway, Kling, Luma |
| Embedding | `EmbeddingGeneratorInterface` | OpenAI (text-embedding-3-small) | Cohere, Voyage AI |
| Classificação | `ClassifierInterface` | OpenAI (GPT-4o-mini) | Anthropic (Claude Haiku), Ollama |

### SDK e Factory

- **SDK:** Laravel AI SDK (Prism) para capabilities de texto e classificação.
- **Factory:** `AIProviderFactory` resolve provider por capability + configuração da organização.
- **Registry:** `AIProviderRegistry` com defaults do sistema e override por organização.
- **Fallback Chain:** Provider primário → fallback da org → default do sistema → HTTP 503.
- **Circuit Breaker:** 5 falhas consecutivas → circuit open, 60s reset, Redis-based.

### Funcionalidades por modelo

| Funcionalidade | Modelo Default | Fallback |
|----------------|---------------|----------|
| Gerar título | GPT-4o | GPT-4o-mini |
| Gerar descrição | GPT-4o | GPT-4o-mini |
| Gerar hashtags | GPT-4o-mini | GPT-4o-mini |
| Gerar conteúdo completo | GPT-4o | GPT-4o-mini |
| Classificar sentimento | GPT-4o-mini | GPT-4o-mini |
| Sugerir resposta | GPT-4o | GPT-4o-mini |
| Brand Safety | GPT-4o-mini | GPT-4o-mini |
| Embeddings | text-embedding-3-small | — |

### AI Learning & Feedback Loop (ADR-017)

O sistema implementa um loop de aprendizado em 6 níveis ativos + 1 futuro:

1. **Generation Feedback Tracking** — Registra aceitar/editar/rejeitar de cada geração IA
2. **RAG (Retrieval-Augmented Generation)** — Busca top performers similares via pgvector para enriquecer gerações
3. **Prompt Optimization Engine** — Templates versionados com A/B testing e auto-seleção por performance
4. **Prediction Accuracy Feedback** — Valida predições vs métricas reais 7 dias após publicação
5. **Organization Style Learning** — Aprende estilo da org a partir de padrões de edição
6. **CRM Intelligence Feedback** — Conecta dados de conversão CRM à geração de conteúdo, priorizando conteúdo que gera vendas (ADR-017 N6 + ADR-018)

### Configuração por organização

Cada organização pode configurar seu provider preferido por capability via `PUT /api/v1/ai/settings`.

### Controle de custos
- Registro de tokens consumidos por geração (tabela `ai_generations`)
- Limite mensal por plano (Free: 50, Creator: 200, Professional: 500, Agency: 5.000)
- Tabela `ai_provider_pricing` com preços por modelo administrável via admin
- Rate limiting: máximo 10 gerações por minuto por usuário

### Tratamento de erros
- Timeout texto: 30 segundos
- Timeout imagem: 120 segundos (assíncrono via job)
- Timeout vídeo: 300 segundos (assíncrono via job + polling)
- Fallback chain: provider primário → fallback → default do sistema
- Circuit breaker por provider (5 falhas → open, 60s reset)
- Erro claro para o usuário quando todos os providers indisponíveis (HTTP 503)

---

## 6.6 CRM — Conectores Nativos + Webhooks

> **Referência:** ADR-018 (Native CRM Connectors Strategy)

### Arquitetura

O sistema oferece **duas camadas** de integração com CRMs:

1. **Conectores nativos** (plug-and-play): Integração direta com os CRMs mais populares via OAuth, com mapeamento automático de campos e sincronização bidirecional.
2. **Webhooks genéricos** (fallback universal): Sistema de webhooks configurável para qualquer CRM ou ferramenta não suportada nativamente.

```
┌──────────────────────────────────────────────────────────┐
│                  Social Media Manager API                  │
│                                                            │
│  ┌──────────────────┐        ┌──────────────────────────┐ │
│  │  CRM Connectors  │        │  Webhooks Genéricos      │ │
│  │  (Nativos)       │        │  (Fallback universal)    │ │
│  │                  │        │                          │ │
│  │  CrmConnector    │        │  POST → URL + HMAC       │ │
│  │  Interface       │        │                          │ │
│  └────────┬─────────┘        └─────────┬────────────────┘ │
└───────────┼────────────────────────────┼──────────────────┘
            │                            │
    ┌───────┴───────┐            ┌───────┴────────┐
    │ CRM APIs      │            │ Qualquer URL   │
    │ (OAuth 2.0)   │            │ (HMAC-SHA256)  │
    └───────────────┘            └────────────────┘
```

---

### 6.6.1 Conectores Nativos

#### CRMs Suportados

**Fase 1 (Sprint 15):**

| CRM | Mercado | Público-alvo | Auth |
|-----|---------|-------------|------|
| **HubSpot** | Global (228k+ clientes) | PMEs, agências de marketing | OAuth 2.0 |
| **RD Station** | Brasil (dominante) | PMEs, agências digitais | OAuth 2.0 |
| **Pipedrive** | Global (100k+ em 175 países) | SMEs, startups, agências BR | OAuth 2.0 |

**Fase 2 (Sprint 16):**

| CRM | Mercado | Público-alvo | Auth |
|-----|---------|-------------|------|
| **Salesforce** | Global (#1 IDC 2025) | Enterprise, agências grandes | OAuth 2.0 |
| **ActiveCampaign** | Global + Brasil forte | Agências digitais, automação | API Key |

#### Interface CrmConnector (Adapter Pattern — ADR-006)

```
Interface: CrmConnectorInterface
├── authenticate(code, state): CrmTokenResponse
├── refreshToken(refreshToken): CrmTokenResponse
├── revokeToken(accessToken): bool
├── createContact(accessToken, contactData): CrmContactResult
├── updateContact(accessToken, contactId, data): CrmContactResult
├── createDeal(accessToken, dealData): CrmDealResult
├── updateDeal(accessToken, dealId, data): CrmDealResult
├── logActivity(accessToken, entityId, activityData): CrmActivityResult
├── searchContacts(accessToken, query): CrmContactCollection
├── getConnectionStatus(accessToken): CrmConnectionStatus
```

#### Autenticação

| CRM | Método | Access Token TTL | Refresh |
|-----|--------|-----------------|---------|
| HubSpot | OAuth 2.0 | 30 min | Sim |
| RD Station | OAuth 2.0 | 24h | Sim |
| Pipedrive | OAuth 2.0 | 60 min | Sim |
| Salesforce | OAuth 2.0 | 2h | Sim |
| ActiveCampaign | API Key | Não expira | N/A |

- Tokens armazenados com **AES-256-GCM** e chave dedicada (mesma estratégia de redes sociais — ADR-012).
- Refresh automático antes da expiração via `RefreshCrmTokenJob`.
- Máximo **1 conexão CRM por provider por organização**.

#### Fluxo de Dados — Outbound (SMM → CRM)

| Trigger no SMM | Ação no CRM | Exemplo |
|---------------|------------|---------|
| Comentário positivo capturado | Cria/atualiza contato | Autor do comentário vira lead |
| Lead identificado por automação | Cria oportunidade/deal | "Comentou pedindo preço" → deal |
| Post publicado com sucesso | Registra atividade no contato | Histórico de publicações |
| Automação executada | Atualiza custom field | Tags, status, engagement score |
| Métricas de engagement | Enriquece dados do contato | Engagement rate, alcance |

#### Fluxo de Dados — Inbound (CRM → SMM)

| Trigger no CRM | Ação no SMM | Exemplo |
|---------------|------------|---------|
| Novo contato/deal criado | Tag para segmentação | Personaliza conteúdo por segmento |
| Deal fechado (won) | Trigger de campanha | Campanha "pós-venda" automática |
| Contato atualizado | Sincroniza dados de audiência | Enriquece perfil do público |
| Stage do deal mudou | Ajusta automação de engajamento | Altera tom das respostas automáticas |

#### Mapeamento de Campos

Cada conector possui **default mapping** que o usuário pode customizar:

| Campo SMM | HubSpot | RD Station | Pipedrive | Salesforce | ActiveCampaign |
|----------|---------|------------|-----------|------------|----------------|
| Nome do autor | `firstname` + `lastname` | `name` | `name` | `Name` | `firstName` + `lastName` |
| External ID | `hs_additional_id` | `cf_social_id` | Custom field | `Social_ID__c` | Custom field |
| Rede social | `hs_content_source` | `cf_social_network` | Custom field | `Social_Network__c` | Custom field |
| Sentimento | Custom property | Custom field | Custom field | Custom field | Custom field |
| Campanha | `hs_campaign` | `cf_campaign` | Custom field | `Campaign` | Tag |

#### Rate Limits por CRM

| CRM | Limite | Estratégia |
|-----|--------|-----------|
| HubSpot | 150 req/10s (OAuth) | Token bucket, throttle por org |
| RD Station | 120 req/min | Token bucket, fila dedicada |
| Pipedrive | 80 req/2s | Throttle agressivo, batch quando possível |
| Salesforce | 15.000 req/dia (standard) | Contagem diária, Bulk API para backfill |
| ActiveCampaign | 5 req/s | Throttle por org |

#### Endpoints da API

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/v1/crm/providers` | Lista CRMs disponíveis |
| `POST` | `/api/v1/crm/connect/{provider}` | Inicia OAuth flow |
| `GET` | `/api/v1/crm/callback/{provider}` | Callback OAuth |
| `DELETE` | `/api/v1/crm/connections/{id}` | Desconecta CRM |
| `GET` | `/api/v1/crm/connections` | Lista conexões da org |
| `GET` | `/api/v1/crm/connections/{id}/status` | Status da conexão |
| `GET` | `/api/v1/crm/connections/{id}/mappings` | Lista mapeamento de campos |
| `PUT` | `/api/v1/crm/connections/{id}/mappings` | Atualiza mapeamento |
| `GET` | `/api/v1/crm/connections/{id}/logs` | Logs de sincronização |
| `POST` | `/api/v1/crm/connections/{id}/sync` | Força sincronização manual |
| `POST` | `/api/v1/crm/connections/{id}/test` | Testa conexão |

---

### 6.6.2 Webhooks Genéricos (Fallback)

Sistema de webhooks configurável para qualquer CRM ou ferramenta externa não suportada nativamente.

#### Payload padrão

```json
{
  "event": "comment.created",
  "timestamp": "2026-02-15T10:30:00Z",
  "data": {
    "comment_id": "uuid",
    "content_id": "uuid",
    "social_network": "instagram",
    "author": {
      "name": "João Silva",
      "external_id": "12345"
    },
    "text": "Texto do comentário",
    "sentiment": "positive",
    "post_title": "Título do post",
    "campaign_name": "Campanha X"
  }
}
```

#### Segurança dos webhooks
- Assinatura HMAC-SHA256 no header `X-Webhook-Signature`
- Secret único por webhook configurado
- Timestamp no header `X-Webhook-Timestamp` para prevenção de replay attacks
- Validação: rejeitar requests com timestamp > 5 minutos

#### Eventos disponíveis
| Evento | Descrição | Trigger |
|--------|-----------|---------|
| `comment.created` | Novo comentário capturado | Sync de comentários |
| `comment.replied` | Comentário respondido | Resposta manual ou automática |
| `lead.identified` | Lead identificado por regra | Regra de automação com ação de lead |
| `automation.triggered` | Automação executada | Motor de automação |
| `post.published` | Post publicado com sucesso | Publishing pipeline |
| `post.failed` | Falha na publicação | Publishing pipeline |

#### Retry policy
- 3 tentativas: 1 minuto, 5 minutos, 30 minutos
- Após 3 falhas: webhook marcado como failed, notificação ao usuário
- Dashboard com log de entregas e status

---

## 6.7 Abstração de Providers (Adapter Pattern)

Para garantir extensibilidade e manutenibilidade, todas as integrações com redes
sociais seguem o **Adapter Pattern**:

```
Interface: SocialMediaAdapter
├── authenticate(code, state): TokenResponse
├── refreshToken(refreshToken): TokenResponse
├── revokeToken(accessToken): bool
├── getProfile(accessToken): SocialProfile
├── publishContent(accessToken, content): PublishResult
├── getMetrics(accessToken, postId): MetricsData
├── getComments(accessToken, postId, cursor): CommentCollection
├── replyToComment(accessToken, commentId, text): ReplyResult
```

### Implementações
- `InstagramAdapter implements SocialMediaAdapter`
- `TikTokAdapter implements SocialMediaAdapter`
- `YouTubeAdapter implements SocialMediaAdapter`

### Adicionar nova rede
1. Criar novo Adapter implementando a interface
2. Registrar no ServiceProvider
3. Adicionar enum no SocialProvider
4. Configurar credenciais OAuth no .env
5. Sem alteração em regras de negócio

---

## 6.8 Considerações de Rate Limiting

### Estratégia por provider

| Provider | Limite | Estratégia |
|----------|--------|-----------|
| Instagram | 200 calls/hora | Queue com throttle, distribuir calls ao longo da hora |
| TikTok | Variável | Respeitar headers X-RateLimit-*, backoff quando próximo do limite |
| YouTube | 10.000 units/dia | Contagem de quota, priorizar publicação sobre leitura |
| OpenAI | RPM/TPM por tier | Token bucket, fallback para modelo menor |

### Implementação
- Middleware de rate limiting por provider
- Contadores em Redis com TTL
- Headers de resposta indicando limite restante
- Quando limite atingido: enfileirar para próxima janela
- Dashboard de consumo de API por provider
