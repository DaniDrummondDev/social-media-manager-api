# AI Content Generation — Social Media Manager API

## Objetivo

Definir as regras de domínio para **geração de conteúdo com IA**, incluindo tipos de geração, configurações do usuário, controle de uso e histórico.

---

## Conceitos

### AIGeneration (Entity)

Registro de cada geração de conteúdo por IA, incluindo input, output, modelo usado e custo.

### AISettings (Entity)

Configurações de IA da organização (tom padrão, idioma, limites).

---

## Tipos de Geração

### Generate Title

- **Input**: tópico (10-500 chars), rede social (opcional), tom, idioma.
- **Output**: 3 sugestões de título com character count.
- **Modelo**: GPT-4o.
- **Rate limit**: 10 req/min.

### Generate Description

- **Input**: tópico, rede social, tom, keywords (máx 10), idioma.
- **Output**: descrição otimizada para a rede com character count.
- **Modelo**: GPT-4o.
- **Limites de caracteres respeitados**: Instagram (2200), TikTok (300), YouTube (5000).

### Generate Hashtags

- **Input**: tópico, nicho (3-100 chars), rede social.
- **Output**: lista de hashtags com nível de competição (high/medium/low).
- **Modelo**: GPT-4o-mini (tarefa mais simples).
- **Limite por rede**: Instagram (30), TikTok (variable), YouTube (15).

### Generate Full Content

- **Input**: tópico, redes sociais (1-5), tom, keywords, idioma.
- **Output**: conteúdo adaptado por rede (título + descrição + hashtags).
- **Modelo**: GPT-4o.
- **Consideração**: mais caro, conta como 1 geração por rede solicitada.

### Suggest Reply

- **Input**: texto do comentário, contexto do conteúdo original.
- **Output**: 2 sugestões (tom professional + casual).
- **Modelo**: GPT-4o.
- **Nota**: sugestão, não envio automático. Usuário confirma antes de publicar.

---

## Regras de Negócio

### Geração

- **RN-AI-01**: Toda geração requer autenticação e email verificado.
- **RN-AI-02**: Rate limit de 10 gerações por minuto por organização.
- **RN-AI-03**: Limite mensal de gerações por organização (padrão: 500/mês).
- **RN-AI-04**: Ao atingir limite mensal, retornar 429 com mensagem clara.
- **RN-AI-05**: Tom de voz segue configuração do usuário se não especificado no request.
- **RN-AI-06**: Idioma segue configuração do usuário se não especificado.

### Tons de Voz

| Tom | Descrição |
|-----|-----------|
| `professional` | Formal, empresarial |
| `casual` | Descontraído, informal |
| `fun` | Divertido, com emojis |
| `informative` | Educativo, didático |
| `inspirational` | Motivacional |
| `custom` | Personalizado pelo usuário |

- **RN-AI-07**: Tom `custom` requer `custom_tone_description` nas settings.
- **RN-AI-08**: Tom personalizado é enviado como system prompt para a IA.

### Idiomas

| Idioma | Código |
|--------|--------|
| Português (Brasil) | `pt_BR` |
| Inglês (EUA) | `en_US` |
| Espanhol (Espanha) | `es_ES` |

### Configurações da Organização

- **RN-AI-09**: Cada organização tem AISettings com defaults.
- **RN-AI-10**: Settings criadas automaticamente ao criar a organização (defaults: professional, pt_BR).
- **RN-AI-11**: Limite mensal definido pelo plano da organização (ver `billing-subscription.md`).

---

## Controle de Uso e Custo

### Tracking por Geração

| Campo | Descrição |
|-------|-----------|
| `tokens_input` | Tokens consumidos no prompt |
| `tokens_output` | Tokens gerados na resposta |
| `model` | Modelo usado (gpt-4o, gpt-4o-mini) |
| `cost_estimate_usd` | Custo estimado em USD |

### Endpoint de Uso

`GET /api/v1/ai/settings` retorna:

```json
{
  "usage_this_month": {
    "generations": 127,
    "tokens_input": 15000,
    "tokens_output": 12000,
    "estimated_cost_usd": 0.42
  }
}
```

### Cálculo de Custo

Baseado em pricing do modelo:

| Modelo | Input (per 1M tokens) | Output (per 1M tokens) |
|--------|----------------------|----------------------|
| GPT-4o | $2.50 | $10.00 |
| GPT-4o-mini | $0.15 | $0.60 |

---

## Histórico de Gerações

### Campos

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | UUID | Identificador |
| `organization_id` | UUID | Organização (tenant) |
| `user_id` | UUID | Quem solicitou |
| `generation_type` | enum | title, description, hashtags, full, suggest_reply |
| `input` | JSON | Parâmetros de entrada |
| `output_preview` | text | Preview da geração (primeiros 200 chars) |
| `model` | string | Modelo usado |
| `tokens_input` | integer | Tokens de input |
| `tokens_output` | integer | Tokens de output |
| `cost_estimate_usd` | decimal | Custo estimado |
| `created_at` | datetime | Timestamp |

### Regras

- **RN-AI-12**: Histórico mantido por 6 meses.
- **RN-AI-13**: Usuário pode consultar via `GET /api/v1/ai/history`.
- **RN-AI-14**: Filtrável por tipo, data e paginado por cursor.

---

## Integração com Conteúdo

A geração de IA produz **sugestões** que o usuário pode:
1. Aceitar e usar diretamente no conteúdo.
2. Editar antes de usar.
3. Rejeitar e pedir nova geração.

A IA **nunca**:
- Cria conteúdo automaticamente sem ação do usuário.
- Publica conteúdo gerado sem confirmação.
- Modifica conteúdo existente sem solicitação.

---

## Domain Events

| Evento | Quando | Dados |
|--------|--------|-------|
| `ContentGenerated` | Geração concluída | generation_id, type, model, tokens |
| `AISettingsUpdated` | Settings alteradas | organization_id, changed_fields |
| `AILimitReached` | Limite mensal atingido | organization_id, limit |

---

## Tratamento de Falhas

- **Provider indisponível**: retornar HTTP 503 com `AI_GENERATION_ERROR`.
- **Rate limit do provider**: retornar HTTP 429 com `AI_RATE_LIMIT`.
- **Timeout (30s)**: retornar HTTP 503.
- **Não usar retry automático** (custo duplicado, experiência ruim).
- **IA é non-critical**: falha na IA não impede uso de nenhuma outra funcionalidade.

---

## Anti-Patterns

- IA como requisito para publicação (deve ser opcional).
- Geração sem registro de uso/custo.
- Cache de gerações entre organizações (privacidade).
- Envio de dados pessoais para a API de IA.
- Retry automático em falha de geração (duplica custo).
- Prompts hardcoded (devem ser templates configuráveis).
- Ignorar limites mensais da organização.
