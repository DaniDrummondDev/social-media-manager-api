# AI Integration — Social Media Manager API

## Objetivo

Definir a arquitetura de integração com IA para geração de conteúdo, garantindo segurança, controle de custos e isolamento de dados.

> Referência: ADR-009 (Laravel AI SDK — Prism)

---

## Princípios Fundamentais

- IA **nunca** toma decisões autônomas — toda geração é uma sugestão.
- IA é **opcional** — o sistema funciona completamente sem ela.
- IA é **auditada** — toda interação registrada com input, output, modelo, tokens, custo.
- IA é **isolada por organização** — nenhum contexto compartilhado entre organizações.
- IA é **controlada por custo** — limites mensais por organização.

---

## Stack de IA

- **Laravel AI SDK (Prism)**: abstração para múltiplos providers.
- **Provider principal**: OpenAI (GPT-4o, GPT-4o-mini).
- **Embeddings**: pgvector no PostgreSQL (para busca semântica futura).

---

## Tipos de Geração

### Geração de Títulos
- Input: tópico, rede social (opcional), tom, idioma.
- Output: 3 sugestões com character count.
- Modelo: GPT-4o.

### Geração de Descrição/Legenda
- Input: tópico, rede social (opcional), tom, keywords, idioma.
- Output: descrição otimizada para a rede.
- Modelo: GPT-4o.
- Respeita limites de caracteres por rede.

### Geração de Hashtags
- Input: tópico, nicho, rede social.
- Output: lista de hashtags com nível de competição.
- Modelo: GPT-4o-mini (mais rápido, suficiente para hashtags).

### Geração de Conteúdo Completo
- Input: tópico, redes sociais (múltiplas), tom, keywords, idioma.
- Output: conteúdo adaptado por rede (título + descrição + hashtags).
- Modelo: GPT-4o.

### Sugestão de Resposta a Comentários
- Input: texto do comentário, contexto do conteúdo.
- Output: 2 sugestões (professional + casual).
- Modelo: GPT-4o.

---

## Estratégia de Modelos

| Tipo | Modelo | Justificativa |
|------|--------|---------------|
| Títulos | GPT-4o | Criatividade necessária |
| Descrições | GPT-4o | Qualidade e contexto |
| Hashtags | GPT-4o-mini | Tarefa simples, custo menor |
| Conteúdo completo | GPT-4o | Múltiplas redes, complexidade |
| Sugestão de resposta | GPT-4o | Contexto e tom |

---

## Controle de Custos

### Limites

| Plano | Gerações/mês | Implementação |
|-------|-------------|---------------|
| Default | 500 | Counter no banco (`ai_settings.monthly_generation_limit`) |

### Tracking

Cada geração registra na tabela `ai_generations`:
- `tokens_input`: tokens consumidos no prompt.
- `tokens_output`: tokens gerados na resposta.
- `model`: modelo utilizado.
- `cost_estimate_usd`: custo estimado.

### Endpoint de Uso
- `GET /api/v1/ai/settings`: retorna limites e uso atual do mês.

---

## Segurança de Dados

### O que é enviado para a API de IA

- Tópico descrito pelo usuário.
- Tom de voz configurado.
- Keywords fornecidas.
- Idioma.
- Rede social de destino.

### O que **nunca** é enviado

- Nome do usuário.
- Email do usuário.
- Tokens de redes sociais.
- Dados de analytics.
- Informações pessoais de terceiros (commenters).

### Embeddings (pgvector)

- Armazenados com `organization_id` para isolamento.
- Usados para busca semântica de conteúdos similares (futuro).
- Gerados em batch (não em real-time).
- Filtrados por `organization_id` em toda query de similaridade.

---

## Tratamento de Falhas

- IA é **non-critical** — falha não impede uso do sistema.
- Se provider indisponível: retornar HTTP 503 com mensagem clara.
- Rate limit atingido no provider: retornar HTTP 429.
- Timeout de geração: 30 segundos.
- Não usar retry automático para gerações de IA (custo duplicado).

---

## Prompt Engineering

### Estrutura Padrão

```
System: Você é um especialista em social media marketing.
        Gere conteúdo para {rede_social} com tom {tom}.
        Idioma: {idioma}. Limite: {max_chars} caracteres.

User: Tópico: {tópico}
      Keywords: {keywords}
```

### Regras de Prompts

- Prompts são versionados (armazenados como templates).
- Prompts não contêm dados pessoais.
- Output é validado estruturalmente antes de persistir (JSON schema).
- Output é sanitizado (remover possíveis injeções).

---

## Anti-Patterns

- IA tomando decisões sem confirmação do usuário.
- Enviar dados pessoais para a API de IA.
- Cache de resultados de IA entre usuários diferentes.
- Retry automático em falha de geração (custo duplicado).
- Prompts hardcoded nos Use Cases (devem ser templates configuráveis).
- IA como requisito para funcionalidade core (publicação, agendamento).
- Embeddings sem filtro de `organization_id` em queries de similaridade.

---

## Dependências

- `01-security/audit-logging.md` (auditoria de gerações)
- `06-domain/ai-content-generation.md` (regras de negócio)
