# Engagement & Automation — Social Media Manager API

## Objetivo

Definir as regras de domínio para **captura de comentários**, **automação de respostas**, **blacklist** e **integração via webhooks**.

---

## Comentários

### Comment (Aggregate Root)

Representa um comentário capturado de uma rede social.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador interno |
| `organization_id` | UUID | Organização (tenant) |
| `content_id` | UUID | Conteúdo onde comentaram |
| `provider` | enum | Rede social |
| `external_comment_id` | string | ID na rede social |
| `author_name` | string | Autor do comentário |
| `author_external_id` | string | ID do autor na rede |
| `text` | text | Texto do comentário |
| `sentiment` | enum | positive, neutral, negative |
| `sentiment_score` | decimal | 0.0 a 1.0 |
| `is_read` | boolean | Lido pelo user |
| `is_from_owner` | boolean | Comentário do próprio dono |
| `reply_text` | text | Resposta (se respondido) |
| `replied_by` | enum | user, automation |
| `replied_at` | datetime | Quando respondido |
| `commented_at` | datetime | Data original na rede |
| `captured_at` | datetime | Quando capturado pelo sistema |

### Regras de Negócio

- **RN-ENG-01**: Comentários são capturados periodicamente via `CaptureCommentsJob`.
- **RN-ENG-02**: Sentimento é analisado automaticamente na captura.
- **RN-ENG-03**: Comentário só pode ser respondido uma vez.
- **RN-ENG-04**: Resposta é publicada na rede social via `SocialEngagementInterface`.
- **RN-ENG-05**: Comentários do próprio owner são marcados (`is_from_owner = true`).
- **RN-ENG-06**: Sugestão de resposta via IA não publica automaticamente (requer confirmação).

---

## Automação

### AutomationRule (Aggregate Root)

Regra de automação que reage a comentários capturados.

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `name` | string | 3-100 chars |
| `priority` | integer | Único entre regras ativas da organização |
| `conditions` | JSON[] | Array de condições |
| `action_type` | enum | reply_fixed, reply_template, reply_ai, send_webhook |
| `response_template` | text | Template de resposta (para reply_fixed/template) |
| `webhook_id` | UUID | Webhook destino (para send_webhook) |
| `delay_seconds` | integer | 30-3600, padrão 120 |
| `daily_limit` | integer | 10-1000, padrão 100 |
| `executions_today` | integer | Contador diário |
| `is_active` | boolean | Ativo/inativo |
| `applies_to_networks` | string[] | null = todas |
| `applies_to_campaigns` | UUID[] | null = todas |

### Condições

```json
[
  { "field": "keyword", "operator": "contains", "value": "preço" },
  { "field": "sentiment", "operator": "equals", "value": "positive" }
]
```

| Field | Operators |
|-------|----------|
| `keyword` | contains, not_contains, equals |
| `sentiment` | equals, in |
| `author_name` | contains, equals |

### Tipos de Ação

| Action Type | Descrição | Requer confirmação? |
|------------|-----------|-------------------|
| `reply_fixed` | Resposta fixa | Não (automático) |
| `reply_template` | Template com variáveis (`{author_name}`) | Não (automático) |
| `reply_ai` | Resposta gerada por IA | Não (automático com delay) |
| `send_webhook` | Envia dados para webhook | Não (automático) |

### Fluxo de Automação

```
CommentCaptured event
       ↓
EvaluateAutomationRulesListener
       ↓
Ordena regras ativas por prioridade
       ↓
Para cada regra (em ordem):
  1. Verificar condições (todas devem ser true = AND)
  2. Verificar blacklist (se match → pular)
  3. Verificar limite diário (se atingido → pular)
  4. Verificar applies_to_networks/campaigns
  5. Se match: executar ação com delay configurado
  6. PARAR (primeira regra que match é executada)
```

### Regras de Negócio

- **RN-AUT-01**: Regras são avaliadas por prioridade (menor número = maior prioridade).
- **RN-AUT-02**: Primeira regra que faz match é executada (sem acúmulo).
- **RN-AUT-03**: Delay mínimo de 30 segundos entre captura e resposta automática.
- **RN-AUT-04**: Limite diário por regra reseta à meia-noite UTC.
- **RN-AUT-05**: Comentários que contêm palavras da blacklist são ignorados.
- **RN-AUT-06**: Prioridade é única entre regras ativas da mesma organização.
- **RN-AUT-07**: Automação não responde a comentários do próprio owner.
- **RN-AUT-08**: Automação não responde a comentários já respondidos.
- **RN-AUT-09**: Template variables: `{author_name}`, `{content_title}`, `{campaign_name}`.

---

## Blacklist

### BlacklistWord (Entity)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `word` | string | Palavra ou padrão |
| `is_regex` | boolean | Se é expressão regular |

### Regras

- **RN-BL-01**: Comentário que contém palavra da blacklist não dispara automação.
- **RN-BL-02**: Regex é validado na criação (regex inválido = 400).
- **RN-BL-03**: Verificação é case-insensitive.

---

## Execuções de Automação

### AutomationExecution (Entity)

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `rule_id` | UUID | Regra que executou |
| `comment_id` | UUID | Comentário que disparou |
| `action_type` | enum | Tipo de ação executada |
| `response_text` | text | Texto da resposta |
| `success` | boolean | Se executou com sucesso |
| `error_message` | text | Erro (se falhou) |
| `delay_applied` | integer | Delay em segundos |
| `executed_at` | datetime | Quando executou |

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `CommentCaptured` | Novo comentário | comment_id, content_id, provider |
| `CommentReplied` | Resposta enviada | comment_id, replied_by |
| `AutomationTriggered` | Regra matchou | rule_id, comment_id |
| `AutomationExecuted` | Ação executada | execution_id, success |
| `WebhookDelivered` | Webhook entregue | webhook_id, event, status |

---

## Anti-Patterns

- Automação sem delay (spam detection pelos providers).
- Automação sem limite diário (excessivo pode banir conta).
- Responder comentário já respondido (duplicata).
- Avaliar todas as regras ao invés de parar na primeira match.
- Blacklist sem case-insensitive (bypass trivial).
- Automação respondendo ao próprio dono.
- Templates com variáveis não resolvidas na resposta final.
