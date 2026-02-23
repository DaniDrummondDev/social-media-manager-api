# ADR-016: Arquitetura Multi-Provider para IA

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-23
- **Decisores:** Equipe de arquitetura
- **Complementa:** ADR-006 (Adapter Pattern), ADR-009 (Laravel AI SDK)

## Contexto

O sistema atualmente utiliza OpenAI como **único provider** para todas as tarefas de IA (geração de texto, embeddings, classificação de sentimento). O ADR-009 adotou o Laravel AI SDK (Prism) como camada de abstração, que suporta múltiplos providers de texto (OpenAI, Anthropic, Ollama), mas na prática apenas OpenAI está configurado.

Com a evolução do produto, o sistema precisará de **diferentes tipos de IA** para diferentes capacidades:

| Capacidade | Exemplo de uso | Providers candidatos |
|-----------|----------------|---------------------|
| **Texto** | Gerar títulos, descrições, hashtags, full content | OpenAI, Anthropic, Ollama |
| **Imagem** | Gerar imagens para posts e thumbnails | DALL-E 3, Nano Banana, Stability AI, Midjourney |
| **Vídeo** | Gerar vídeos curtos para Reels/TikTok/Shorts | Sora, Runway, Kling, Luma |
| **Embedding** | Busca semântica, Content DNA, similarity | OpenAI, Cohere, Voyage AI |
| **Classificação** | Sentimento, brand safety, tópicos | OpenAI, Anthropic, Ollama |

Cada provider tem API, pricing, rate limits e capabilities diferentes. Precisamos de uma arquitetura que:

1. Permita usar o **melhor provider para cada tipo de tarefa**
2. Permita **trocar providers sem alterar regras de negócio**
3. Suporte **fallback automático** quando um provider falha
4. Permita **configuração por organização** (org A usa Nano Banana para imagens, org B usa DALL-E)
5. Unifique **cost tracking** entre todos os providers
6. Aplique **circuit breaker por provider** (já existe para redes sociais)

## Decisão

Adotar o **AI Adapter Pattern** — mesma estratégia arquitetural do ADR-006 (redes sociais) — com **5 interfaces de capability** definidas na camada de domínio e implementações por provider na infraestrutura.

### Arquitetura geral

```
┌───────────────────────────────────────────────────────────────┐
│                     Application Layer                          │
│  Use Cases (GenerateTitle, PredictPerformance, GenerateImage) │
└──────────────────────────┬────────────────────────────────────┘
                           │ depende de
┌──────────────────────────▼────────────────────────────────────┐
│                     Domain Layer                               │
│                                                                │
│  ┌──────────────────┐  ┌──────────────────┐                   │
│  │ TextGenerator    │  │ ImageGenerator   │                   │
│  │ Interface        │  │ Interface        │                   │
│  └──────────────────┘  └──────────────────┘                   │
│  ┌──────────────────┐  ┌──────────────────┐                   │
│  │ VideoGenerator   │  │ Embedding        │                   │
│  │ Interface        │  │ Generator Iface  │                   │
│  └──────────────────┘  └──────────────────┘                   │
│  ┌──────────────────┐                                         │
│  │ Classifier       │                                         │
│  │ Interface        │                                         │
│  └──────────────────┘                                         │
└──────────────────────────┬────────────────────────────────────┘
                           │ implementado por
┌──────────────────────────▼────────────────────────────────────┐
│                   Infrastructure Layer                          │
│                                                                │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │ AIProviderFactory                                        │  │
│  │   resolve provider via AIProviderRegistry                │  │
│  └─────────────────────────────┬───────────────────────────┘  │
│                                │                               │
│    ┌──────────┬──────────┬─────┴────┬──────────┐              │
│    ▼          ▼          ▼          ▼          ▼              │
│  Text/     Image/     Video/    Embedding/  Classifier/       │
│  ├─Prism   ├─DallE    ├─Sora    ├─OpenAI    ├─Prism         │
│  ├─Ollama  ├─NanoBan  ├─Runway  ├─Cohere    └─Ollama        │
│  │         ├─Stabil.  ├─Kling   └─Voyage                    │
│  │         └─Midjour  └─Luma                                 │
│  │                                                            │
│  └──── Shared/ (CircuitBreaker, RateLimiter, FallbackChain)  │
└───────────────────────────────────────────────────────────────┘
```

### 5 Interfaces de capability (Domain Layer)

#### 1. TextGeneratorInterface

Bridge para Prism SDK (ADR-009). Prism já abstrai múltiplos providers de texto.

```php
// src/Domain/ContentAI/Contracts/TextGeneratorInterface.php
interface TextGeneratorInterface
{
    public function generateText(TextPrompt $prompt): TextResult;
    public function generateStructured(TextPrompt $prompt, string $schemaClass): StructuredResult;
    public function estimateCost(TextPrompt $prompt): CostEstimate;
}
```

**Implementação:** `PrismTextGenerator` usa Prism internamente. Provider resolvido via config do Prism.

#### 2. ImageGeneratorInterface

Nova interface para geração de imagens (já desenhada na Feature Futura do roadmap).

```php
// src/Domain/ContentAI/Contracts/ImageGeneratorInterface.php
interface ImageGeneratorInterface
{
    public function generateImage(ImagePrompt $prompt): GenerationTicket;
    public function getStatus(string $ticketId): GenerationStatus;
    public function getResult(string $ticketId): ImageResult;
    public function cancelGeneration(string $ticketId): void;
    public function estimateCost(ImagePrompt $prompt): CostEstimate;
}
```

**Implementações planejadas:** `DallEImageGenerator`, `NanoBananaImageGenerator`, `StabilityImageGenerator`, `MidjourneyImageGenerator`.

#### 3. VideoGeneratorInterface

Nova interface para geração de vídeos (já desenhada na Feature Futura do roadmap).

```php
// src/Domain/ContentAI/Contracts/VideoGeneratorInterface.php
interface VideoGeneratorInterface
{
    public function generateVideo(VideoPrompt $prompt): GenerationTicket;
    public function getStatus(string $ticketId): GenerationStatus;
    public function getResult(string $ticketId): VideoResult;
    public function cancelGeneration(string $ticketId): void;
    public function estimateCost(VideoPrompt $prompt): CostEstimate;
}
```

**Implementações planejadas:** `SoraVideoGenerator`, `RunwayVideoGenerator`, `KlingVideoGenerator`, `LumaVideoGenerator`.

#### 4. EmbeddingGeneratorInterface

Já definida na skill `ai-intelligence.md`. Sem mudanças.

```php
// src/Domain/AIIntelligence/Contracts/EmbeddingGeneratorInterface.php
interface EmbeddingGeneratorInterface
{
    public function generate(string $text): Vector;
    public function generateBatch(array $texts): array;
    public function getModel(): string;
    public function estimateCost(int $tokenCount): float;
}
```

**Implementações planejadas:** `OpenAIEmbeddingGenerator`, `CohereEmbeddingGenerator`, `VoyageEmbeddingGenerator`.

#### 5. ClassifierInterface

Nova interface que unifica tarefas de classificação (sentimento, brand safety, tópicos).

```php
// src/Domain/AIIntelligence/Contracts/ClassifierInterface.php
interface ClassifierInterface
{
    public function classifySentiment(string $text): SentimentResult;
    public function classifyBrandSafety(string $content, array $rules): SafetyResult;
    public function classifyTopics(string $text): TopicResult;
    public function estimateCost(string $task, int $tokenCount): float;
}
```

**Implementações:** `PrismClassifier` (bridge para Prism), `OllamaClassifier` (modelos locais).

### AI Provider Factory

```php
// src/Infrastructure/AI/AIProviderFactory.php
class AIProviderFactory
{
    public function __construct(
        private AIProviderRegistry $registry,
    ) {}

    public function makeTextGenerator(?AIProvider $provider = null): TextGeneratorInterface
    {
        $provider ??= $this->registry->getProvider(AICapability::Text);
        return match ($provider) {
            AIProvider::OpenAI, AIProvider::Anthropic => app(PrismTextGenerator::class),
            AIProvider::Ollama => app(OllamaTextGenerator::class),
        };
    }

    public function makeImageGenerator(?AIProvider $provider = null): ImageGeneratorInterface
    {
        $provider ??= $this->registry->getProvider(AICapability::Image);
        return match ($provider) {
            AIProvider::OpenAI => app(DallEImageGenerator::class),
            AIProvider::NanoBanana => app(NanoBananaImageGenerator::class),
            AIProvider::StabilityAI => app(StabilityImageGenerator::class),
            AIProvider::Midjourney => app(MidjourneyImageGenerator::class),
        };
    }

    public function makeVideoGenerator(?AIProvider $provider = null): VideoGeneratorInterface;
    public function makeEmbeddingGenerator(?AIProvider $provider = null): EmbeddingGeneratorInterface;
    public function makeClassifier(?AIProvider $provider = null): ClassifierInterface;
}
```

### AI Provider Registry

Resolve qual provider usar baseado em: configuração da organização → default do sistema.

```php
// src/Infrastructure/AI/AIProviderRegistry.php
class AIProviderRegistry
{
    public function getProvider(AICapability $capability, ?OrganizationId $orgId = null): AIProvider;
    public function getModel(AICapability $capability, ?OrganizationId $orgId = null): string;
    public function getFallback(AIProvider $provider, AICapability $capability): ?AIProvider;
    public function getConfig(OrganizationId $orgId): AIProviderConfig;
    public function setConfig(OrganizationId $orgId, AIProviderConfig $config): void;
}
```

**Configuração por organização** (campo JSONB em `ai_settings`):

```json
{
  "provider_config": {
    "text": {
      "provider": "openai",
      "model": "gpt-4o",
      "fallback_provider": "anthropic",
      "fallback_model": "claude-sonnet-4-6"
    },
    "image": {
      "provider": "nano_banana",
      "fallback_provider": "openai"
    },
    "video": {
      "provider": "runway",
      "model": "gen-3",
      "fallback_provider": "sora"
    },
    "embedding": {
      "provider": "openai",
      "model": "text-embedding-3-small"
    },
    "classifier": {
      "provider": "openai",
      "model": "gpt-4o-mini"
    }
  }
}
```

**Defaults do sistema** (quando organização não configura):

| Capability | Provider Default | Modelo Default | Fallback |
|-----------|-----------------|---------------|----------|
| text | openai | gpt-4o | gpt-4o-mini |
| image | openai | dall-e-3 | stability_ai |
| video | openai | sora | runway |
| embedding | openai | text-embedding-3-small | — |
| classifier | openai | gpt-4o-mini | — |

### Enums

```php
enum AIProvider: string
{
    // Text providers
    case OpenAI = 'openai';
    case Anthropic = 'anthropic';
    case Ollama = 'ollama';

    // Image providers
    case NanoBanana = 'nano_banana';
    case StabilityAI = 'stability_ai';
    case Midjourney = 'midjourney';

    // Video providers
    case Runway = 'runway';
    case Kling = 'kling';
    case Luma = 'luma';

    // Embedding providers
    case Cohere = 'cohere';
    case VoyageAI = 'voyage_ai';
}

enum AICapability: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Embedding = 'embedding';
    case Classifier = 'classifier';
}
```

### Estrutura de diretórios

```
src/Infrastructure/AI/
├── AIProviderFactory.php
├── AIProviderRegistry.php
├── UnifiedCostTracker.php
├── Text/
│   ├── PrismTextGenerator.php          (implements TextGeneratorInterface — bridge para Prism)
│   └── OllamaTextGenerator.php         (implements TextGeneratorInterface — local)
├── Image/
│   ├── DallEImageGenerator.php         (implements ImageGeneratorInterface)
│   ├── NanoBananaImageGenerator.php    (implements ImageGeneratorInterface)
│   ├── StabilityImageGenerator.php     (implements ImageGeneratorInterface)
│   └── MidjourneyImageGenerator.php    (implements ImageGeneratorInterface)
├── Video/
│   ├── SoraVideoGenerator.php          (implements VideoGeneratorInterface)
│   ├── RunwayVideoGenerator.php        (implements VideoGeneratorInterface)
│   ├── KlingVideoGenerator.php         (implements VideoGeneratorInterface)
│   └── LumaVideoGenerator.php          (implements VideoGeneratorInterface)
├── Embedding/
│   ├── OpenAIEmbeddingGenerator.php    (implements EmbeddingGeneratorInterface)
│   ├── CohereEmbeddingGenerator.php    (implements EmbeddingGeneratorInterface)
│   └── VoyageEmbeddingGenerator.php    (implements EmbeddingGeneratorInterface)
├── Classifier/
│   ├── PrismClassifier.php             (implements ClassifierInterface — bridge para Prism)
│   └── OllamaClassifier.php            (implements ClassifierInterface — local)
└── Shared/
    ├── AICircuitBreaker.php            (por provider, Redis-backed)
    ├── AIRateLimiter.php               (por provider, Redis-backed)
    └── FallbackChain.php               (resolve fallback automaticamente)
```

### Cost tracking unificado

Expandir tabela `ai_generations` para incluir provider e capability:

```sql
ALTER TABLE ai_generations ADD COLUMN provider VARCHAR(50) NOT NULL DEFAULT 'openai';
ALTER TABLE ai_generations ADD COLUMN capability VARCHAR(20) NOT NULL DEFAULT 'text';
-- capability: text, image, video, embedding, classifier
```

Tabela de preços por provider/modelo (administrável):

```sql
CREATE TABLE ai_provider_pricing (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    provider VARCHAR(50) NOT NULL,
    model VARCHAR(100) NOT NULL,
    capability VARCHAR(20) NOT NULL,
    input_cost_per_unit DECIMAL(10,6) NOT NULL,
    output_cost_per_unit DECIMAL(10,6) NOT NULL,
    unit_type VARCHAR(20) NOT NULL,    -- 'per_1m_tokens', 'per_image', 'per_second'
    effective_from DATE NOT NULL,
    effective_until DATE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(provider, model, effective_from)
);
```

Endpoints de admin para gerenciar preços:
- `GET /api/v1/admin/ai-pricing` — listar preços atuais
- `POST /api/v1/admin/ai-pricing` — cadastrar/atualizar preço

### Circuit breaker por provider AI

Mesma estratégia do Publishing (ADR-013 / Sprint 4):

| Aspecto | Configuração |
|---------|-------------|
| Threshold | 5 falhas consecutivas → open |
| Reset timeout | 60 segundos → half-open |
| Half-open test | 1 requisição teste → fecha ou reabre |
| Storage | Redis key: `ai_circuit:{provider}:{capability}` |
| Fallback | Quando open, tenta `fallback_provider` da config |

### Fallback chain

```
Request chega → Provider primário (config da org)
                    │
                    ├─ Sucesso → retorna resultado
                    │
                    └─ Falha (circuit open ou erro)
                         │
                         ▼
                    Provider fallback (config da org)
                         │
                         ├─ Sucesso → retorna resultado
                         │
                         └─ Falha
                              │
                              ▼
                         Provider default do sistema
                              │
                              ├─ Sucesso → retorna resultado
                              │
                              └─ Falha → retorna erro ao usuário
```

## Alternativas consideradas

### 1. Prism para tudo (apenas texto expandido)

- **Prós:** Já integrado, abstração pronta para texto
- **Contras:** Prism não suporta imagem, vídeo ou embedding nativamente. Seria necessário estender o Prism ou criar bridges
- **Por que descartada:** Prism é excelente para texto mas não foi desenhado para capabilities visuais. Forçar tudo pelo Prism adiciona complexidade sem benefício

### 2. Provider único com fallback apenas (sem configuração por org)

- **Prós:** Mais simples, menos configuração
- **Contras:** Não atende requisito de escolha de provider por tipo de tarefa. Lock-in em um único provider por capability
- **Por que descartada:** O requisito explícito é poder usar "ChatGPT para texto, Nano Banana para imagens"

### 3. Microsserviço dedicado para IA

- **Prós:** Isolamento total, escalabilidade independente
- **Contras:** Overhead operacional enorme (deploy, monitoring, comunicação), complexidade prematura
- **Por que descartada:** Monólito modular atende bem nesta fase. Adapter pattern permite extrair para microsserviço no futuro se necessário

## Consequências

### Positivas

- **Melhor ferramenta para cada tarefa** — texto com GPT-4o, imagens com Nano Banana, embeddings com modelos especializados
- **Zero lock-in** — trocar provider = implementar interface + registrar no factory
- **Resiliência** — fallback automático entre providers
- **Personalização** — cada organização pode escolher seus providers preferidos
- **Cost tracking unificado** — visibilidade total de custos cross-provider
- **Consistência arquitetural** — mesmo padrão das redes sociais (ADR-006)

### Negativas

- **Mais interfaces e classes** — boilerplate de adapters por provider
- **Complexidade de configuração** — mais opções para o usuário configurar
- **Testes de integração** — cada provider precisa de testes específicos
- **Manutenção de compatibilidade** — mudanças de API de cada provider precisam de atualização do adapter correspondente

### Riscos

- **APIs de imagem/vídeo ainda imaturas** — qualidade inconsistente, pricing volátil → mitigado por adapter pattern que isola mudanças
- **Custos variáveis entre providers** — mesmo request pode custar diferente em providers diferentes → mitigado por tabela de preços administrável + estimativa pré-geração
- **Dimensões de embeddings incompatíveis** — trocar provider de embedding invalida embeddings existentes → mitigado por recalcular via BackfillEmbeddingsJob
- **Prism atualizar e quebrar** — interface no domínio protege; reimplementar adapter sem impacto no negócio

---

## Referências

- ADR-009: Laravel AI SDK (Prism) — base para capabilities de texto e classificação
- ADR-017: AI Learning & Feedback Loop — complementa esta arquitetura com feedback tracking, RAG, prompt optimization, prediction accuracy e style learning
