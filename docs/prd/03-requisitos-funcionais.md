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
- Tamanho máximo: imagens 10MB, vídeos 500MB
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
