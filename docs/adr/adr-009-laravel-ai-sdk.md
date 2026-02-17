# ADR-009: Laravel AI SDK para Integração com IA

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-15
- **Decisores:** Equipe de arquitetura

## Contexto

O sistema utiliza IA para:
- Gerar títulos, descrições e hashtags para conteúdos
- Classificar sentimento de comentários
- Sugerir respostas para comentários
- Gerar conteúdo adaptado por rede social

Precisamos de uma integração com provedores de IA (principalmente OpenAI/ChatGPT) que seja:
- Abstrata o suficiente para trocar de provedor se necessário
- Integrada ao ecossistema Laravel
- Com suporte a structured output para respostas previsíveis
- Com controle de custos (tracking de tokens)

## Decisão

Adotar o **Laravel AI SDK (Prism)** como camada de abstração para integração com
provedores de IA, com OpenAI (GPT-4o) como provedor padrão.

### Arquitetura de integração

```
┌─────────────────┐     ┌───────────────────┐     ┌──────────────┐
│  Content AI     │     │  AI Service       │     │  Laravel AI  │
│  Use Cases      │────▶│  (Domain Service) │────▶│  SDK (Prism) │
│                 │     │                   │     │              │
│  GenerateTitle  │     │  Interface na     │     │  OpenAI      │
│  GenerateDesc   │     │  camada Domain    │     │  Anthropic   │
│  GenerateHash   │     │                   │     │  Ollama      │
│  ClassifySent.  │     │  Implementação    │     │  ...         │
│                 │     │  na Infra         │     │              │
└─────────────────┘     └───────────────────┘     └──────────────┘
```

### Interface no domínio

```php
// src/Domain/ContentAI/Contracts/ContentGenerator.php
interface ContentGenerator
{
    public function generateTitle(GenerateTitleInput $input): TitleSuggestions;
    public function generateDescription(GenerateDescriptionInput $input): Description;
    public function generateHashtags(GenerateHashtagsInput $input): HashtagCollection;
    public function generateFullContent(GenerateContentInput $input): FullContentResult;
}

// src/Domain/Engagement/Contracts/SentimentClassifier.php
interface SentimentClassifier
{
    public function classify(string $text): SentimentResult;
}

// src/Domain/Engagement/Contracts/ReplyGenerator.php
interface ReplyGenerator
{
    public function suggest(CommentContext $context): ReplySuggestions;
}
```

### Implementação com Prism

```php
// src/Infrastructure/AI/PrismContentGenerator.php
class PrismContentGenerator implements ContentGenerator
{
    public function generateTitle(GenerateTitleInput $input): TitleSuggestions
    {
        $response = Prism::text()
            ->using('openai', 'gpt-4o')
            ->withSystemPrompt($this->buildTitleSystemPrompt($input))
            ->withPrompt($this->buildTitleUserPrompt($input))
            ->asStructured(TitleSuggestionsSchema::class)
            ->generate();

        $this->trackUsage($response->usage);

        return TitleSuggestions::fromArray($response->structured);
    }
}
```

### Estratégia de modelos

| Funcionalidade | Modelo primário | Modelo fallback | Justificativa |
|----------------|----------------|-----------------|---------------|
| Gerar título | GPT-4o | GPT-4o-mini | Criatividade requer modelo mais capaz |
| Gerar descrição | GPT-4o | GPT-4o-mini | Qualidade de texto longo |
| Gerar hashtags | GPT-4o-mini | GPT-4o-mini | Tarefa mais simples, custo menor |
| Conteúdo completo | GPT-4o | GPT-4o-mini | Qualidade em geração multi-rede |
| Classificar sentimento | GPT-4o-mini | GPT-4o-mini | Classificação simples |
| Sugerir resposta | GPT-4o | GPT-4o-mini | Contexto e tom de voz |

### Structured output

Todas as gerações usam structured output (JSON schema) para garantir:
- Respostas no formato esperado
- Parsing seguro sem regex ou string manipulation
- Validação automática da resposta

```php
// Exemplo de schema para títulos
class TitleSuggestionsSchema
{
    /** @var TitleSuggestion[] */
    public array $suggestions;
}

class TitleSuggestion
{
    public string $title;
    public int $character_count;
    public string $tone;
}
```

### Controle de custos

- Cada chamada registra: `model`, `tokens_input`, `tokens_output`, `cost_estimate`
- Custo estimado calculado com tabela de preços por modelo
- Rate limiting: 10 gerações/min e 500 gerações/dia por usuário
- Alerta quando consumo mensal ultrapassa threshold (configurável)
- Dashboard de consumo disponível via endpoint `GET /api/v1/ai/usage`

### Tratamento de erros

| Cenário | Ação |
|---------|------|
| Timeout (30s) | Retry com mesmo modelo (1 tentativa) |
| Rate limit do provider (429) | Retry após `Retry-After` header |
| Modelo indisponível | Fallback para modelo alternativo |
| Response inválida (schema) | Retry com temperature ajustado |
| Provider completamente down | Retornar erro 503 com mensagem clara |

## Alternativas consideradas

### 1. OpenAI PHP SDK diretamente
- **Prós:** Acesso direto a todas as features da OpenAI, sem abstração intermediária
- **Contras:** Acoplamento total à OpenAI, sem possibilidade de troca de provedor, mais código de infraestrutura
- **Por que descartado:** Prism abstrai o provedor, permitindo trocar para Anthropic ou modelo local (Ollama) sem alterar a aplicação

### 2. LangChain (via API ou bridge PHP)
- **Prós:** Ecossistema rico, chains, agents, RAG
- **Contras:** Primariamente Python, bridge PHP imatura, overhead desnecessário
- **Por que descartado:** Não precisamos de chains/agents complexos; Prism atende nossas necessidades

### 3. Implementação própria com HTTP client
- **Prós:** Controle total, sem dependências
- **Contras:** Reinventar a roda, manutenção de compatibility com API changes
- **Por que descartado:** Prism já resolve abstração de provedores, retry, streaming e structured output

## Consequências

### Positivas
- Troca de provedor de IA sem alterar regras de negócio
- Structured output garante respostas previsíveis
- Integração nativa com Laravel (config, logging, queue)
- Suporte a múltiplos provedores (OpenAI, Anthropic, Ollama)
- Controle granular de custos

### Negativas
- Dependência de pacote terceiro (Prism) — risco de abandono
- Abstração pode limitar acesso a features específicas de um provedor
- Latência adicional da camada de abstração (marginal)

### Riscos
- OpenAI mudar a API breaking — mitigado pelo Prism que absorve mudanças
- Custos de IA escalar inesperadamente — mitigado por rate limiting e limites configuráveis
- Prism descontinuado — interfaces no domínio permitem reimplementar com outro SDK
