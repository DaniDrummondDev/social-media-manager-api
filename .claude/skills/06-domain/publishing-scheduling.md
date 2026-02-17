# Publishing & Scheduling — Social Media Manager API

## Objetivo

Definir as regras de domínio para **agendamento** e **publicação** de conteúdo em redes sociais, incluindo o fluxo assíncrono, retry e estados.

---

## Conceito Principal

### ScheduledPost (Aggregate Root)

Representa uma publicação agendada de um conteúdo em uma rede social específica. Um conteúdo pode gerar múltiplos ScheduledPosts (1 por rede).

---

## ScheduledPost

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `content_id` | UUID | Conteúdo a publicar |
| `social_account_id` | UUID | Conta social destino |
| `provider` | enum | instagram, tiktok, youtube |
| `scheduled_at` | datetime | Quando publicar (null = imediato) |
| `published_at` | datetime | Quando foi publicado |
| `status` | enum | Estado atual |
| `external_post_id` | string | ID do post na rede social |
| `external_post_url` | string | URL do post na rede |
| `attempts` | integer | Tentativas de publicação |
| `max_attempts` | integer | Máximo de tentativas (3) |
| `last_error` | JSON | Último erro registrado |

### Ciclo de Vida

```
pending → dispatched → publishing → published (sucesso)
                                  → failed (falha)
                                       ↓
                            retry → dispatched → ...
                                       ↓
                            (max attempts) → failed (permanente)

pending → cancelled (usuário cancelou)
```

### Transições Válidas

| De | Para | Trigger |
|----|------|---------|
| `pending` | `dispatched` | Scheduler detecta hora de publicar |
| `pending` | `cancelled` | Usuário cancela |
| `dispatched` | `publishing` | Worker inicia publicação |
| `publishing` | `published` | API da rede confirma sucesso |
| `publishing` | `failed` | API retorna erro |
| `failed` | `dispatched` | Retry automático ou manual |
| `cancelled` | — | Estado final |
| `published` | — | Estado final |

---

## Regras de Negócio

### Agendamento

- **RN-PUB-01**: `scheduled_at` deve ser no mínimo 5 minutos no futuro.
- **RN-PUB-02**: Conteúdo deve ter mídia compatível com a rede destino.
- **RN-PUB-03**: Conta social deve estar com status `active`.
- **RN-PUB-04**: Um conteúdo não pode ser agendado 2x para a mesma rede.
- **RN-PUB-05**: Validar limites diários por rede (Instagram: 25 posts/dia).

### Publicação

- **RN-PUB-06**: Publicação é assíncrona via fila `publishing`.
- **RN-PUB-07**: O job de publicação é idempotente (verifica status antes de agir).
- **RN-PUB-08**: Máximo de 3 tentativas automáticas com exponential backoff.
- **RN-PUB-09**: Após 3 tentativas falhadas: status `failed` permanente.
- **RN-PUB-10**: Publicação imediata (publish-now) cria ScheduledPost com `scheduled_at = null`.

### Cancelamento

- **RN-PUB-11**: Agendamento com menos de 1 minuto para publicação é locked (não cancelável).
- **RN-PUB-12**: Status `publishing` ou `dispatched` não pode ser cancelado.
- **RN-PUB-13**: Cancelar agendamento retorna conteúdo para status `draft`.

### Reagendamento

- **RN-PUB-14**: Apenas agendamentos com status `pending` podem ser reagendados.
- **RN-PUB-15**: Nova data deve ser no mínimo 5 minutos no futuro.

### Retry

- **RN-PUB-16**: Retry manual só é permitido para status `failed`.
- **RN-PUB-17**: Erros permanentes (conta desconectada, conteúdo deletado) não são retentáveis.
- **RN-PUB-18**: Retry reseta `attempts` para 0 e muda status para `dispatched`.

---

## Fluxo de Publicação

```
1. Scheduler (a cada minuto):
   - SELECT scheduled_posts WHERE status = 'pending' AND scheduled_at <= NOW()
   - Para cada: dispatch ProcessScheduledPostJob

2. ProcessScheduledPostJob:
   a. Lock distribuído (Redis) por scheduled_post_id
   b. Verificar status == 'dispatched' (idempotência)
   c. Marcar como 'publishing'
   d. Carregar conteúdo + override + mídia
   e. Chamar SocialPublisherInterface::publish()
   f. Se sucesso: marcar como 'published', salvar external_post_id/url
   g. Se falha transiente: incrementar attempts, agendar retry
   h. Se falha permanente: marcar como 'failed'
   i. Emitir evento (PostPublished ou PostFailed)
   j. Liberar lock
```

---

## Validation Warnings

Na resposta do agendamento, avisos sobre compatibilidade:

```json
{
  "validation_warnings": [
    {
      "provider": "tiktok",
      "message": "O TikTok aceita apenas vídeos. A imagem não será publicada nesta rede."
    }
  ]
}
```

- Warnings não impedem o agendamento.
- Conteúdo sem mídia compatível para um provider específico: não cria ScheduledPost para esse provider.

---

## Calendário

Endpoint `GET /api/v1/scheduled-posts/calendar` retorna agendamentos agrupados por dia:

- Filtro por mês/ano ou date range.
- Filtro por provider e campanha.
- Usado pelo frontend para visualização de calendário.

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `PostScheduled` | Agendamento criado | scheduled_post_id, content_id, provider, scheduled_at |
| `PostDispatched` | Job despachado | scheduled_post_id |
| `PostPublished` | Publicado com sucesso | scheduled_post_id, external_post_id, external_url |
| `PostFailed` | Falha na publicação | scheduled_post_id, error, attempts, is_permanent |
| `PostCancelled` | Cancelado pelo user | scheduled_post_id |

---

## Anti-Patterns

- Publicação síncrona dentro do request HTTP.
- Job sem lock distribuído (publicação duplicada).
- Retry para erros permanentes (conta desconectada, 403).
- Cancelar agendamento em status `publishing`.
- Ignorar `validation_warnings` (devem ser retornados ao user).
- Criar ScheduledPost para rede sem mídia compatível.
