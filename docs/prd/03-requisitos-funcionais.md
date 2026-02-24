# 03 — Requisitos Funcionais

[← Voltar ao índice](00-index.md)

---

## 3.1 Módulo: Identity & Access Management

### RF-001: Registro de usuário
- **Endpoint:** `POST /api/v1/auth/register`
- Campos: name, email, password, password_confirmation
- Validação de email único
- Hash de senha com bcrypt (cost 12)
- Envio de email de verificação com token (expira em 24h)
- Resposta: 201 Created com mensagem de verificação pendente

### RF-002: Verificação de email
- **Endpoint:** `POST /api/v1/auth/verify-email`
- Token de verificação (SHA-256, single-use)
- Após verificação, conta é ativada
- Token expirado retorna 410 Gone

### RF-003: Login
- **Endpoint:** `POST /api/v1/auth/login`
- Campos: email, password, (otp_code se 2FA ativo)
- Retorna: access_token (JWT, 15min), refresh_token (opaque, 7d)
- Registra: ip, user_agent, logged_at
- Rate limiting: 5 tentativas / minuto por IP+email

### RF-004: Refresh token
- **Endpoint:** `POST /api/v1/auth/refresh`
- Recebe refresh_token, retorna novo par de tokens
- Refresh token antigo é invalidado (rotation)
- Refresh token expirado retorna 401

### RF-005: Logout
- **Endpoint:** `POST /api/v1/auth/logout`
- Invalida access_token e refresh_token
- Opção de logout de todas as sessões

### RF-006: Recuperação de senha
- **Endpoint:** `POST /api/v1/auth/forgot-password`
- Envio de email com link de redefinição (token expira em 1h)
- **Endpoint:** `POST /api/v1/auth/reset-password`
- Token single-use + nova senha
- Rate limiting: 3 solicitações / hora por email

### RF-007: Autenticação de dois fatores (2FA)
- **Endpoint:** `POST /api/v1/auth/2fa/enable`
- Gera secret TOTP + QR code
- **Endpoint:** `POST /api/v1/auth/2fa/confirm`
- Confirma ativação com código TOTP
- Retorna 10 recovery codes
- **Endpoint:** `POST /api/v1/auth/2fa/disable`
- Requer confirmação de senha

### RF-008: Gerenciamento de perfil
- **Endpoint:** `GET /api/v1/profile`
- **Endpoint:** `PUT /api/v1/profile`
- Campos editáveis: name, phone, timezone, avatar
- **Endpoint:** `PUT /api/v1/profile/email`
- Novo email requer re-verificação
- **Endpoint:** `PUT /api/v1/profile/password`
- Requer senha atual

---

## 3.2 Módulo: Social Account Management

### RF-010: Listar redes disponíveis
- **Endpoint:** `GET /api/v1/social-networks`
- Retorna redes suportadas com: nome, ícone, status (disponível/manutenção), permissões necessárias

### RF-011: Iniciar conexão OAuth
- **Endpoint:** `GET /api/v1/social-accounts/{provider}/redirect`
- Gera state parameter (CSRF protection)
- Redireciona para provider OAuth
- Providers suportados: instagram, tiktok, youtube

### RF-012: Callback OAuth
- **Endpoint:** `GET /api/v1/social-accounts/{provider}/callback`
- Valida state parameter
- Troca authorization code por tokens
- Armazena tokens criptografados (AES-256-GCM)
- Busca e armazena dados do perfil social
- Cria registro de SocialAccount

### RF-013: Listar contas conectadas
- **Endpoint:** `GET /api/v1/social-accounts`
- Retorna: provider, username, profile_picture, status, connected_at, expires_at

### RF-014: Desconectar conta social
- **Endpoint:** `DELETE /api/v1/social-accounts/{id}`
- Revoga token no provider (quando suportado)
- Cancela agendamentos pendentes para esta conta
- Soft delete do registro

### RF-015: Refresh de tokens sociais
- **Job agendado:** a cada 12 horas
- Verifica tokens próximos de expirar (< 7 dias)
- Renova automaticamente
- Em caso de falha, notifica o usuário
- Registra log de renovação

### RF-016: Health check de contas
- **Job agendado:** a cada 6 horas
- Valida que tokens ainda são válidos com chamada leve à API
- Atualiza status: connected, expired, revoked, error
- Notifica usuário sobre problemas

---

## 3.3 Módulo: Campaign Management

### RF-020: CRUD de campanhas
- **Endpoint:** `POST /api/v1/campaigns`
- **Endpoint:** `GET /api/v1/campaigns`
- **Endpoint:** `GET /api/v1/campaigns/{id}`
- **Endpoint:** `PUT /api/v1/campaigns/{id}`
- **Endpoint:** `DELETE /api/v1/campaigns/{id}` (soft delete)
- Campos: name, description, starts_at, ends_at, status, tags
- Status: draft, active, paused, completed
- Filtros: status, period, name (ILIKE), tags
- Ordenação: created_at, name, starts_at
- Paginação: cursor-based

### RF-021: Duplicar campanha
- **Endpoint:** `POST /api/v1/campaigns/{id}/duplicate`
- Copia campanha + peças de conteúdo
- Peças ficam como draft
- Agendamentos não são copiados

### RF-022: CRUD de peças de conteúdo
- **Endpoint:** `POST /api/v1/campaigns/{campaignId}/contents`
- **Endpoint:** `GET /api/v1/campaigns/{campaignId}/contents`
- **Endpoint:** `GET /api/v1/contents/{id}`
- **Endpoint:** `PUT /api/v1/contents/{id}`
- **Endpoint:** `DELETE /api/v1/contents/{id}` (soft delete)
- Campos: title, body, hashtags, media_ids[], social_network_overrides{}
- social_network_overrides permite customizar título/descrição/hashtags por rede
- Status: draft, scheduled, publishing, published, failed, cancelled

### RF-023: Contadores da campanha
- **Endpoint:** `GET /api/v1/campaigns/{id}/stats`
- Retorna: total_contents, draft_count, scheduled_count, published_count, failed_count

---

## 3.4 Módulo: Content AI

### RF-030: Gerar título
- **Endpoint:** `POST /api/v1/ai/generate-title`
- Input: topic, social_network, tone, language
- Output: suggestions[] (3-5 títulos)
- Modelo: GPT-4o via Laravel AI SDK
- Tokens utilizados são registrados para billing

### RF-031: Gerar descrição
- **Endpoint:** `POST /api/v1/ai/generate-description`
- Input: topic, social_network, tone, keywords[], language
- Output: description (texto otimizado para a rede)
- Respeita limites de caracteres da rede

### RF-032: Gerar hashtags
- **Endpoint:** `POST /api/v1/ai/generate-hashtags`
- Input: topic, niche, social_network
- Output: hashtags[] com classificação (high/medium/low competition)
- Quantidade respeitando limites da rede

### RF-033: Gerar conteúdo completo
- **Endpoint:** `POST /api/v1/ai/generate-content`
- Input: topic, social_networks[], tone, keywords[]
- Output: por rede → { title, description, hashtags }
- Permite follow-up para refinamento

### RF-034: Configurar tom de voz
- **Endpoint:** `PUT /api/v1/ai/settings`
- Campos: default_tone (enum), custom_tone_description, language
- Tones: professional, casual, fun, informative, inspirational, custom

### RF-035: Histórico de gerações
- **Endpoint:** `GET /api/v1/ai/history`
- Registro de todas as gerações: input, output, tokens_used, created_at
- Filtros: tipo (title/description/hashtag/full), período

---

## 3.5 Módulo: Publishing

### RF-040: Agendar publicação
- **Endpoint:** `POST /api/v1/contents/{id}/schedule`
- Input: scheduled_at (datetime), social_account_ids[]
- Validações:
  - scheduled_at >= now + 5 minutos
  - Conteúdo compatível com cada rede (formato de mídia, duração de vídeo, tamanho)
  - Conta social está conectada e ativa
- Cria um ScheduledPost por rede
- Status: pending

### RF-041: Publicação imediata
- **Endpoint:** `POST /api/v1/contents/{id}/publish-now`
- Input: social_account_ids[]
- Enfileira com prioridade alta
- Retorna status por rede em tempo real (via polling ou futuro WebSocket)

### RF-042: Cancelar agendamento
- **Endpoint:** `DELETE /api/v1/scheduled-posts/{id}`
- Permitido até 1 minuto antes do horário
- Peça volta para status draft

### RF-043: Reagendar
- **Endpoint:** `PUT /api/v1/scheduled-posts/{id}`
- Input: scheduled_at (novo horário)
- Mesmas validações do agendamento

### RF-044: Listar agendamentos
- **Endpoint:** `GET /api/v1/scheduled-posts`
- Filtros: status, social_network, campaign_id, period
- Ordenação: scheduled_at
- Inclui dados da peça e da rede

### RF-045: Calendário de publicações
- **Endpoint:** `GET /api/v1/scheduled-posts/calendar`
- Query params: month, year (ou start_date, end_date)
- Retorna agrupado por dia: [{ date, posts[] }]

### RF-046: Processamento da fila de publicação
- **Job:** ProcessScheduledPostJob (dispatched por scheduler)
- Verifica posts com scheduled_at <= now e status = pending
- Executa publicação via adapter da rede social
- Atualiza status: publishing → published | failed
- Em caso de falha: registra erro, agenda retry (se elegível)

### RF-047: Retry de publicação
- Retry automático com backoff exponencial: 1min, 5min, 15min
- Máximo 3 tentativas
- Erros permanentes (403, 400) não fazem retry
- Erros transitórios (429, 500, 502, 503, timeout) fazem retry
- Após 3 falhas, status = failed, notifica usuário

---

## 3.6 Módulo: Analytics

### RF-050: Dashboard geral
- **Endpoint:** `GET /api/v1/analytics/overview`
- Query params: period (7d, 30d, 90d, custom), start_date, end_date
- Métricas agregadas:
  - total_posts, total_reach, total_impressions
  - total_engagement (likes + comments + shares + saves)
  - engagement_rate
  - follower_growth (por rede e total)
- Comparativo com período anterior (% de variação)

### RF-051: Analytics por rede
- **Endpoint:** `GET /api/v1/analytics/networks/{provider}`
- Métricas específicas da rede
- Instagram: reach, impressions, likes, comments, saves, shares, profile_views
- TikTok: views, likes, comments, shares, watch_time
- YouTube: views, likes, comments, watch_time, subscribers_gained
- Top 5 conteúdos por engajamento
- Melhores horários de publicação

### RF-052: Analytics por conteúdo
- **Endpoint:** `GET /api/v1/analytics/contents/{id}`
- Métricas do conteúdo em cada rede onde foi publicado
- Evolução temporal (24h, 48h, 7d, 30d)
- Comparativo entre redes

### RF-053: Exportação de relatórios
- **Endpoint:** `POST /api/v1/analytics/export`
- Input: type (overview/network/content), format (pdf/csv), filters
- Processamento assíncrono (job)
- **Endpoint:** `GET /api/v1/analytics/exports/{id}`
- Status: processing, ready, expired
- Download URL válida por 24h

### RF-054: Sincronização de métricas
- **Job agendado:**
  - A cada 6h: métricas gerais e de conteúdos com mais de 48h
  - A cada 1h: métricas de conteúdos publicados nas últimas 48h
- Armazena snapshot de métricas para séries temporais
- Respeita rate limits das APIs

---

## 3.7 Módulo: Engagement

### RF-060: Captura de comentários
- **Job agendado:** a cada 30 minutos (configurável)
- Busca novos comentários de conteúdos publicados
- Armazena: author_name, author_id, text, network, content_id, commented_at
- Deduplicação por external_id
- Classificação de sentimento via IA (positive, neutral, negative)
- Webhook entregue quando disponível na plataforma

### RF-061: Listar comentários
- **Endpoint:** `GET /api/v1/comments`
- Filtros: network, campaign_id, content_id, sentiment, status (read/unread, replied/unreplied), period
- Busca textual no comentário
- Paginação cursor-based

### RF-062: Responder comentário
- **Endpoint:** `POST /api/v1/comments/{id}/reply`
- Input: text
- Publica resposta na rede de origem via API
- Registra: replied_by, replied_at, reply_text

### RF-063: Sugestão de resposta via IA
- **Endpoint:** `POST /api/v1/comments/{id}/suggest-reply`
- Analisa o comentário e contexto do conteúdo
- Gera 1-3 sugestões de resposta
- Tom de voz baseado nas configurações do usuário

### RF-064: CRUD de regras de automação
- **Endpoint:** `POST /api/v1/automation-rules`
- **Endpoint:** `GET /api/v1/automation-rules`
- **Endpoint:** `PUT /api/v1/automation-rules/{id}`
- **Endpoint:** `DELETE /api/v1/automation-rules/{id}`
- Campos: name, conditions[], action, response_template, delay_seconds, is_active
- Conditions: keyword_contains, keyword_exact, sentiment, network, campaign_id
- Actions: reply_fixed, reply_template, reply_ai, send_webhook

### RF-065: Motor de automação
- Quando novo comentário é capturado, avaliar contra regras ativas
- Regras são avaliadas em ordem de prioridade
- Primeira regra que casa é executada (stop on first match)
- Delay antes de responder (configurável, mínimo 30 segundos)
- Limite diário de respostas automáticas (padrão: 100/dia, configurável)
- Log de todas as ações automáticas

### RF-066: Integração webhook com CRM
- **Endpoint:** `POST /api/v1/webhooks`
- **Endpoint:** `GET /api/v1/webhooks`
- **Endpoint:** `PUT /api/v1/webhooks/{id}`
- **Endpoint:** `DELETE /api/v1/webhooks/{id}`
- Campos: name, url, secret (para assinatura HMAC), events[], headers{}, is_active
- Eventos: comment.created, comment.replied, lead.identified, automation.triggered
- Payload assinado com HMAC-SHA256
- Retry: 3 tentativas com backoff exponencial
- Log de entregas com status

---

## 3.8 Módulo: Media

### RF-070: Upload de mídia
- **Endpoint:** `POST /api/v1/media`
- Upload multipart/form-data
- Tipos aceitos: image/jpeg, image/png, image/webp, image/gif, video/mp4, video/quicktime
- Tamanho máximo: imagens 10MB, vídeos 500MB (saber max upload size YouTube)
- Validações: dimensões mínimas, duração máxima de vídeo por rede
- Geração de thumbnail automática
- Armazenamento em object storage (S3-compatible)
- Scan antivírus/malware antes de persistir

### RF-071: Listar mídias
- **Endpoint:** `GET /api/v1/media`
- Filtros: type (image/video), campaign_id, period
- Ordenação: created_at, file_name, file_size
- Retorna: id, file_name, type, size, dimensions, thumbnail_url, created_at

### RF-072: Deletar mídia
- **Endpoint:** `DELETE /api/v1/media/{id}`
- Bloqueia se mídia está vinculada a peça agendada
- Soft delete (30 dias para restauração)
- **Job agendado:** exclusão física do storage após 30 dias

---

## 3.9 Módulo: AI Intelligence

> **Fase:** 2 (Sprints 10-11) e 3 (Sprints 12-13)

### RF-080: Best Time to Post — Horários ótimos
- **Endpoint:** `GET /api/v1/ai-intelligence/best-times`
- **Endpoint:** `GET /api/v1/ai-intelligence/best-times/heatmap`
- **Endpoint:** `GET /api/v1/ai-intelligence/best-times/{provider}`
- **Endpoint:** `POST /api/v1/ai-intelligence/best-times/recalculate`
- Calcula horários ótimos por organização/rede/dia da semana
- Baseado em: engagement rate histórico de `content_metric_snapshots` + `scheduled_posts`
- Heatmap 7 dias × 24 horas com scores por slot
- Nível de confiança: low (<10 posts), medium (10-50), high (>50)
- **Job:** `CalculateBestPostingTimesJob` — semanal por organização
- Resultado cacheado com TTL de 7 dias

### RF-081: Brand Safety — Verificação de conteúdo
- **Endpoint:** `POST /api/v1/contents/{id}/safety-check`
- **Endpoint:** `GET /api/v1/contents/{id}/safety-checks`
- Verifica conteúdo antes de publicar via LLM
- Categorias: lgpd_compliance, advertising_disclosure, platform_policy, sensitivity, profanity
- Status: pending → passed | warning | blocked
- Score geral 0-100 (100 = totalmente seguro)
- Check automático assíncrono ao agendar (não bloqueia o agendamento)
- Na publicação: `blocked` impede publicação; `warning` publica + notifica
- **Job:** `RunBrandSafetyCheckJob` — on-demand + pré-publicação

### RF-082: Brand Safety — Regras customizáveis
- **Endpoint:** `GET /api/v1/brand-safety/rules`
- **Endpoint:** `POST /api/v1/brand-safety/rules`
- **Endpoint:** `PUT /api/v1/brand-safety/rules/{id}`
- **Endpoint:** `DELETE /api/v1/brand-safety/rules/{id}`
- Tipos: blocked_word, required_disclosure, custom_check
- Severidade: warning, block
- Regras da organização aplicadas junto com verificação LLM
- Organização configura se `blocked` realmente previne publicação

### RF-083: Cross-Network Content Adaptation
- **Endpoint:** `POST /api/v1/ai/adapt-content`
- Input: content_id, source_network, target_networks[], preserve_tone
- Adapta conteúdo que performou bem em uma rede para formato/estilo de outras
- Respeita limites de caracteres, convenções de hashtag, specs de mídia por rede
- Resultado pode auto-preencher `content_network_overrides` (com confirmação do usuário)
- Usa modelo GPT-4o; registrado em `ai_generations` com tipo `cross_network_adaptation`
- Tokens e custo rastreados no histórico de gerações

### RF-084: AI Content Calendar Planning
- **Endpoint:** `POST /api/v1/ai-intelligence/calendar/suggest`
- **Endpoint:** `GET /api/v1/ai-intelligence/calendar/suggestions`
- **Endpoint:** `GET /api/v1/ai-intelligence/calendar/suggestions/{id}`
- **Endpoint:** `POST /api/v1/ai-intelligence/calendar/suggestions/{id}/accept`
- Input: period_start, period_end, target_networks[]
- Gera sugestões de calendário editorial para período selecionado (semanal/mensal)
- Baseado em: top performers históricos, lacunas no cronograma, posts agendados existentes
- Cada sugestão: data, tópicos, tipo de conteúdo, redes-alvo, prioridade, justificativa
- Usuário pode aceitar itens individuais (não aceita automaticamente)
- **Job:** `GenerateCalendarSuggestionsJob` — assíncrono, on-demand
- Sugestões expiram após 7 dias

### RF-085: Content DNA Profiling
- **Endpoint:** `POST /api/v1/ai-intelligence/content-profile/generate`
- **Endpoint:** `GET /api/v1/ai-intelligence/content-profile`
- **Endpoint:** `GET /api/v1/ai-intelligence/content-profile/themes`
- **Endpoint:** `POST /api/v1/ai-intelligence/content-profile/recommend`
- Analisa conteúdo publicado histórico da organização via pgvector
- Gera "DNA de conteúdo": temas dominantes, padrões de engajamento, traits de alto desempenho
- Centroid embedding dos top 20% de conteúdos (VECTOR(1536))
- Recomendações de temas baseadas em similaridade com conteúdos de alta performance
- **Job:** `GenerateContentProfileJob` — semanal ou on-demand
- Perfil cacheado com TTL de 7 dias
- Requer pipeline de embeddings (RF-090)

### RF-086: Pre-publish Performance Prediction
- **Endpoint:** `POST /api/v1/contents/{id}/predict-performance`
- **Endpoint:** `GET /api/v1/contents/{id}/predictions`
- **Endpoint:** `GET /api/v1/contents/{id}/predictions/{provider}`
- Score 0-100 prevendo engajamento antes de publicar
- Breakdown: content_similarity, timing, hashtags, length, media_type
- Top 5 conteúdos similares usados como referência
- Recomendações acionáveis (ex: "horário sub-ótimo", "hashtags de alta competição")
- Abordagem híbrida: Layer 1 estatístico (pgvector + SQL), Layer 2 LLM opcional (insights ricos)
- **Job:** `CalculatePerformancePredictionJob` — on-demand
- Requer pipeline de embeddings (RF-090) e Content DNA Profile (RF-085)

### RF-087: Audience Feedback Loop
- **Endpoint:** `GET /api/v1/ai-intelligence/audience-insights`
- **Endpoint:** `GET /api/v1/ai-intelligence/audience-insights/{type}`
- **Endpoint:** `POST /api/v1/ai-intelligence/audience-insights/refresh`
- Tipos de insight: preferred_topics, sentiment_trends, engagement_drivers, audience_preferences
- Analisa embeddings e sentimento de comentários para extrair preferências da audiência
- Insights compilados em cache (`ai_generation_context`) para injeção em prompts
- Geração de conteúdo (RF-030 a RF-033) automaticamente inclui contexto da audiência
- Usuário vê qual contexto foi usado; pode desativar via `PUT /api/v1/ai/settings`
- IA **nunca** age autonomamente — insights são apenas contexto para geração manual
- **Jobs:** `RefreshAudienceInsightsJob` (semanal), `UpdateAIGenerationContextJob` (pós-refresh)
- Requer pipeline de embeddings em comentários (RF-090)

### RF-088: Competitive Content Gap Analysis
- **Endpoint:** `POST /api/v1/ai-intelligence/gap-analysis/generate`
- **Endpoint:** `GET /api/v1/ai-intelligence/gap-analyses`
- **Endpoint:** `GET /api/v1/ai-intelligence/gap-analyses/{id}`
- **Endpoint:** `GET /api/v1/ai-intelligence/gap-analyses/{id}/opportunities`
- Depende de Social Listening (RF-060+) com queries tipo `competitor`
- Compara tópicos de conteúdo próprio vs menções de concorrentes
- Identifica lacunas: "concorrentes publicam sobre X mas você não"
- Oportunidades acionáveis com score de oportunidade e sugestão de tipo de conteúdo
- **Job:** `GenerateContentGapAnalysisJob` — on-demand + mensal
- Análise expira após 7 dias

### RF-090: Pipeline de Embeddings
- Infraestrutura compartilhada para features RF-085, RF-086, RF-087, RF-091
- Gera embeddings para conteúdos e comentários usando OpenAI `text-embedding-3-small` (1536 dim)
- Event-driven: `ContentCreated`/`ContentUpdated` → `GenerateContentEmbeddingJob` (async)
- Batch: `CommentCaptured` → agrupado em batches de 50 por job
- Backfill: `BackfillEmbeddingsJob` semanal para catch-up de entidades sem embedding
- Tracking via tabela `embedding_jobs` (status, tokens, erros)
- Colunas `contents.embedding` e `comments.embedding` já existem (VECTOR(1536))
- **Jobs:** `GenerateContentEmbeddingJob`, `GenerateCommentEmbeddingJob`, `BackfillEmbeddingsJob`

---

## 3.11 AI Learning & Feedback Loop (ADR-017)

> **Fase 3 — Sprint 14.** Referência completa: Skill `06-domain/ai-learning-loop.md`, ADR-017.

### RF-091: Generation Feedback Tracking (Nível 1)
- **Endpoint:** `POST /api/v1/ai/generations/{id}/feedback`
- Registrar ação do usuário: `accepted`, `edited` ou `rejected`
- Quando `edited`: `original_output` e `edited_output` obrigatórios, diff calculado assincronamente
- `time_to_decision_ms` calculado automaticamente (timestamp da geração → timestamp do feedback)
- Acceptance rate por (organization_id, generation_type)
- Feedback **nunca** bloqueia o fluxo do usuário (processado via `TrackGenerationFeedbackJob`)
- **Jobs:** `TrackGenerationFeedbackJob`, `CalculateDiffSummaryJob`

### RF-092: RAG para Content Generation (Nível 2)
- Antes da geração de texto, buscar até 5 conteúdos publicados com maior similaridade via pgvector
- Filtro: publicados, engagement rate acima da mediana da org, mesma organização
- Exemplos injetados no prompt como "Conteúdo de alta performance similar"
- Desativável via `PUT /api/v1/ai/settings` (`rag_enabled: false`)
- Requer mínimo 5 conteúdos publicados com embedding. Abaixo disso, skip silencioso
- Response inclui `rag_context_used` com IDs dos conteúdos usados
- Feature gate: Creator+ (3 exemplos), Professional+ (5 exemplos)
- **Job:** `RetrieveSimilarContentJob`

### RF-093: Prompt Optimization Engine (Nível 3)
- **Endpoint:** `GET /api/v1/ai/prompt-templates`
- **Endpoint:** `POST /api/v1/ai/prompt-templates` (Professional+)
- **Endpoint:** `POST /api/v1/ai/prompt-experiments` (Agency only)
- **Endpoint:** `GET /api/v1/ai/prompt-experiments/{id}`
- Templates versionados com system prompt + user prompt template + variables
- `performance_score = (accepted + edited × 0.7) / total_uses × 100` — recalculado semanalmente
- Auto-seleção do melhor template por performance (mínimo 20 uses)
- A/B testing: 2 variantes, split configurável, mínimo 50 gerações por variante
- Vencedor declarado por z-test com confidence ≥ 0.95
- Templates globais (system) seedados no deploy; templates custom por organização (Professional+)
- **Jobs:** `CalculatePromptPerformanceJob`, `EvaluatePromptExperimentJob`

### RF-094: Prediction Accuracy Feedback (Nível 4)
- **Endpoint:** `GET /api/v1/ai/intelligence/prediction-accuracy`
- 7 dias após publicação, comparar score predito vs métricas reais
- `actual_normalized_score` = percentile rank × 100 na distribuição da própria org
- `prediction_accuracy = 100 - |predicted_score - actual_normalized_score|`
- Métricas expostas: MAE, correlação, tendência das últimas 12 semanas
- Mínimo 10 predições validadas para exibir métricas
- Feature gate: Agency only
- **Job:** `ValidatePredictionAccuracyJob` (triggered por `MetricsSynced`)

### RF-095: Organization Style Learning (Nível 5)
- **Endpoint:** `GET /api/v1/ai/style-profile`
- Perfil gerado a partir de padrões de edição (mínimo 10 edições)
- Analisa: tom, tamanho, vocabulário, estrutura, hashtags
- `style_summary` gerado por LLM (GPT-4o-mini, max 200 tokens)
- Injetado no prompt como "Preferências de estilo da organização"
- Desativável via `PUT /api/v1/ai/settings` (`style_learning_enabled: false`)
- TTL 14 dias, recalculado semanalmente
- Feature gate: Professional+
- **Job:** `GenerateOrgStyleProfileJob`, `UpdateLearningContextJob`

---

## 3.12 Módulo: CRM Connectors (Conectores Nativos)

> **Referência:** ADR-018 (Native CRM Connectors Strategy)

### RF-096: Conexão com CRM via OAuth
- **Endpoint:** `POST /api/v1/crm/connect/{provider}`
- **Endpoint:** `GET /api/v1/crm/callback/{provider}`
- Providers suportados: `hubspot`, `rdstation`, `pipedrive` (Fase 1); `salesforce`, `activecampaign` (Fase 2)
- Fluxo OAuth 2.0 com PKCE quando disponível
- Tokens armazenados com AES-256-GCM (mesma estratégia de redes sociais — ADR-012)
- Máximo 1 conexão por CRM por organização
- Refresh automático antes da expiração via `RefreshCrmTokenJob`
- Feature gate: Professional+ (exceto webhooks genéricos, disponíveis a partir do Professional)

### RF-097: Gerenciamento de conexões CRM
- **Endpoint:** `GET /api/v1/crm/providers` — Lista CRMs disponíveis
- **Endpoint:** `GET /api/v1/crm/connections` — Lista conexões da org
- **Endpoint:** `GET /api/v1/crm/connections/{id}/status` — Status da conexão
- **Endpoint:** `DELETE /api/v1/crm/connections/{id}` — Desconecta CRM
- **Endpoint:** `POST /api/v1/crm/connections/{id}/test` — Testa conexão
- Status: `connected`, `expired`, `revoked`, `error`
- Dashboard com status de todas as conexões CRM da organização

### RF-098: Mapeamento de campos CRM
- **Endpoint:** `GET /api/v1/crm/connections/{id}/mappings` — Lista mapeamento
- **Endpoint:** `PUT /api/v1/crm/connections/{id}/mappings` — Atualiza mapeamento
- Cada conector possui default mapping pré-configurado
- Usuário pode customizar mapeamento por tipo de entidade (contact, deal, activity)
- Campos suportados: nome, external_id, rede social, sentimento, campanha, engagement metrics
- Transformações opcionais: uppercase, lowercase, prefix, template string

### RF-099: Sincronização outbound (SMM → CRM)
- Comentário positivo capturado → cria/atualiza contato no CRM
- Lead identificado por automação → cria oportunidade/deal no CRM
- Post publicado com sucesso → registra atividade no contato
- Automação executada → atualiza custom field no CRM
- Métricas de engagement → enriquece dados do contato
- Processamento assíncrono via jobs: `SyncContactToCrmJob`, `CreateCrmDealJob`, `LogCrmActivityJob`
- Retry: 3 tentativas com backoff exponencial (60s, 300s, 900s)

### RF-100: Sincronização inbound (CRM → SMM)
- Recebe webhooks do CRM via `POST /api/v1/crm/connections/{id}/webhook`
- Novo contato/deal criado → tag para segmentação de conteúdo
- Deal fechado → trigger de campanha de conteúdo
- Contato atualizado → sincroniza dados de audiência
- Validação de assinatura por provider (HMAC para HubSpot, token para RD Station, etc.)
- **Job:** `ProcessCrmWebhookJob`

### RF-101: Logs de sincronização CRM
- **Endpoint:** `GET /api/v1/crm/connections/{id}/logs`
- Log detalhado de cada sincronização (outbound e inbound)
- Campos: direction, entity_type, action, status, payload, error_message, duration_ms
- Filtros: por direção, tipo, status, período
- Paginação cursor-based
- Retenção: 90 dias (Professional), 180 dias (Agency)

### RF-102: Backfill e sincronização manual
- **Endpoint:** `POST /api/v1/crm/connections/{id}/sync`
- Sincronização manual on-demand de todos os contatos pendentes
- Backfill automático de contatos existentes após primeira conexão
- **Job:** `BackfillCrmContactsJob`
- Limite: 1 backfill por dia por conexão
- Progresso rastreável via endpoint de status

### RF-103: CRM Intelligence — Atribuição de conversão (ADR-017 N6)
- Quando um deal é criado/fechado ou contato é sincronizado com `interaction_data` rastreável, o sistema atribui automaticamente a conversão ao conteúdo social de origem
- Conteúdos com atribuições de conversão ganham boost no ranking RAG (N2)
- Dados de conversão são agregados semanalmente e injetados no contexto de geração IA
- **Jobs:** `AttributeCrmConversionJob`, `EnrichAIContextFromCrmJob`
- **Feature gate:** Exclusivo plano Agency com CRM conector ativo
- Tabela: `crm_conversion_attributions` (AI Intelligence BC)

---

## 3.13 Módulo: Paid Advertising (Tráfego Pago)

> **Fase:** 5 (Futuro — pós v4.0)\
> **Status:** Planejado\
> **Bounded Context:** Paid Advertising (novo)\
> **Planos:** Professional e Agency (Creator em avaliação)\
> **Referência:** ADR-020 (a ser criado)

> **Nota importante:** Este módulo envolve transferência de valores monetários reais para as plataformas de anúncios (Meta Ads, TikTok Ads, Google Ads). A implementação requer integração com Marketing APIs específicas de cada plataforma, contas de anúncio verificadas e tratamento rigoroso de billing. Posts orgânicos **não suportam** audience targeting em nenhuma das 3 plataformas — targeting é exclusivo de conteúdo pago.

### RF-110: Conectar conta de anúncios

- **Endpoint:** `POST /api/v1/ads/accounts/{provider}/connect`
- **Endpoint:** `GET /api/v1/ads/accounts/{provider}/callback`
- Providers suportados: `meta` (Instagram/Facebook Ads), `tiktok` (TikTok Ads), `google` (Google Ads/YouTube)
- OAuth separado das contas sociais orgânicas (escopos de Marketing API):
  - Meta: `ads_management`, `ads_read`, `business_management`
  - TikTok: TikTok Marketing API access
  - Google: `https://www.googleapis.com/auth/adwords` + developer token
- Tokens armazenados com AES-256-GCM (mesma estratégia ADR-012)
- Máximo 1 conta de anúncios por provider por organização
- Validação de Business Verification e App Review por plataforma
- Feature gate: Professional+ (Meta apenas), Agency (todas as plataformas)

### RF-111: Gerenciamento de contas de anúncios

- **Endpoint:** `GET /api/v1/ads/accounts` — Lista contas conectadas
- **Endpoint:** `GET /api/v1/ads/accounts/{id}/status` — Status e saldo
- **Endpoint:** `DELETE /api/v1/ads/accounts/{id}` — Desconecta conta
- **Endpoint:** `POST /api/v1/ads/accounts/{id}/test` — Testa conexão
- Status: `connected`, `expired`, `suspended`, `error`
- Exibe saldo/limite de gasto da conta de anúncios (quando disponível via API)

### RF-112: Criar audiência/segmento de targeting

- **Endpoint:** `POST /api/v1/ads/audiences`
- **Endpoint:** `GET /api/v1/ads/audiences`
- **Endpoint:** `GET /api/v1/ads/audiences/{id}`
- **Endpoint:** `PUT /api/v1/ads/audiences/{id}`
- **Endpoint:** `DELETE /api/v1/ads/audiences/{id}`
- Campos: name, description, targeting_spec
- Targeting spec (normalizado cross-platform):
  - `demographics`: age_min, age_max, genders
  - `locations`: countries[], regions[], cities[]
  - `interests`: interest_ids[] (resolvidos por plataforma)
  - `behaviors`: behavior_ids[] (resolvidos por plataforma)
  - `custom_audiences`: audience_ids[] (pixel, listas, engagement)
  - `lookalike`: source_audience_id, country, similarity_percentage
- Cada audiência é traduzida para o formato nativo da plataforma no momento do boost
- Pesquisa de interesses: `GET /api/v1/ads/interests/search?q={query}&provider={provider}`

### RF-113: Boost de conteúdo publicado (promoção paga)

- **Endpoint:** `POST /api/v1/ads/boosts`
- **Endpoint:** `GET /api/v1/ads/boosts`
- **Endpoint:** `GET /api/v1/ads/boosts/{id}`
- **Endpoint:** `DELETE /api/v1/ads/boosts/{id}` (cancela promoção)
- Input: scheduled_post_id (post já publicado), audience_id, budget, duration_days, objective
- Objectives: `reach`, `engagement`, `traffic`, `conversions`
- Budget: valor diário ou total, moeda da conta de anúncios
- Validações:
  - Post deve estar com status `published`
  - Conta de anúncios da plataforma deve estar conectada e ativa
  - Budget mínimo respeitando limites da plataforma
  - Audiência compatível com a plataforma do post
- Fluxo:
  1. Cria campaign + ad set (com targeting) + ad creative (referenciando post existente) via Marketing API
  2. Monitora status do anúncio (pending_review → active → completed/rejected)
  3. Sincroniza métricas de performance do anúncio
- **Job:** `CreateAdBoostJob` (queue: high, retry: 3)
- **Job:** `SyncAdStatusJob` (scheduler: a cada 30min para boosts ativos)

### RF-114: Métricas de anúncios

- **Endpoint:** `GET /api/v1/ads/boosts/{id}/metrics`
- **Endpoint:** `GET /api/v1/ads/analytics/overview`
- Métricas por boost: impressions, reach, clicks, ctr, cpc, cpm, spend, conversions
- Dashboard agregado: total_spend, total_reach, avg_ctr, avg_cpc, roas (se conversões rastreadas)
- Breakdown por: período, plataforma, audiência, objetivo
- Comparativo: performance orgânica vs paga do mesmo conteúdo
- **Job:** `SyncAdMetricsJob` (scheduler: a cada 1h para boosts ativos, a cada 6h para finalizados)

### RF-115: AI Learning com dados de tráfego pago (ADR-017 extensão)

- Dados de performance de anúncios retroalimentam o pipeline de IA:
  - **Audiências que convertem melhor** por tipo de conteúdo → contexto para geração
  - **Tom/linguagem ideal** por segmento demográfico → refinamento de prompts
  - **Melhores horários** por segmento → extensão do Best Time to Post (RF-080)
  - **Sugestões de targeting** baseadas em performance histórica → recomendações ao criar boost
- Dados agregados semanalmente e injetados em `ai_generation_context` com `context_type = 'ad_performance'`
- Conteúdos com alta performance paga ganham boost no ranking RAG (similar a RF-103)
- Response de geração inclui `ad_context_used` quando dados de ads influenciam a geração
- Desativável via `PUT /api/v1/ai/settings` (`ad_learning_enabled: false`)
- **Jobs:** `AggregateAdPerformanceJob` (semanal), `EnrichAIContextFromAdsJob` (pós-agregação)
- Feature gate: Exclusivo para organizações com conta de anúncios conectada e plano Professional+

### RF-116: Histórico e relatórios de gastos

- **Endpoint:** `GET /api/v1/ads/spending`
- **Endpoint:** `POST /api/v1/ads/spending/export`
- Histórico de gastos por período, plataforma, campanha
- Relatório exportável (PDF, CSV) para prestação de contas
- Integração com Client Financial Management (Sprint 8): gastos de ads alocáveis como custo por cliente
- Alerta de budget: notificação quando gasto atinge 80% e 100% do orçamento definido
