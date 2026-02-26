# ADR-021: Arquitetura Multi-Agent com LangGraph

[← Voltar ao índice](00-index.md)

---

- **Status:** Accepted
- **Data:** 2026-02-26
- **Decisores:** Equipe de arquitetura
- **Complementa:** ADR-009 (Laravel AI SDK), ADR-016 (Multi-Provider AI), ADR-017 (AI Learning & Feedback Loop)

## Contexto

O sistema utiliza chamadas single-shot para todas as tarefas de IA — geração de texto, brand safety, análise de sentimento, performance prediction. Cada Use Case chama um LLM uma vez e retorna o resultado. Essa abordagem é suficiente para operações simples, mas **subutiliza o potencial da IA** em fluxos complexos onde raciocínio em múltiplas etapas, reflexão e especialização por tarefa produziriam resultados significativamente superiores.

### Limitações do modelo single-shot

| Fluxo atual | Limitação | Impacto |
|-------------|-----------|---------|
| Geração de conteúdo | Uma chamada gera tudo (título, corpo, hashtags, CTA) | Qualidade inconsistente; sem revisão ou otimização |
| Content DNA Profiling | Análise em passo único | Perfis superficiais; perde padrões sutis |
| Social Listening Response | Classificação + resposta em uma chamada | Respostas genéricas; sem análise contextual profunda |
| Adaptação visual cross-network | Crop mecânico (center/smart crop) | Corta sujeitos, perde composição, texto ilegível |
| Brand Safety | Checagem pontual | Sem reasoning chain para casos ambíguos |

### Oportunidade

Nenhum concorrente brasileiro (mLabs, Etus, Reportei) ou global (Hootsuite, Buffer, Sprout Social, Later) utiliza **pipelines multi-agente** para geração de conteúdo. A adoção de LangGraph como orquestrador de agentes especializados cria um **moat tecnológico** em qualidade de output e diferencial de produto.

### Timing estratégico

A implementação é planejada para **depois da Fase 5 (Sprint 19)**, quando:

1. A infraestrutura de IA está completa (embeddings, RAG, predictions — Sprints 12-14)
2. O AI Learning Loop está operacional (feedback, prompt optimization, style learning — Sprint 14)
3. CRM Intelligence está fluindo dados de conversão (Sprint 16)
4. Dados de performance de ads estão disponíveis (Sprint 18)
5. A base de dados acumulada justifica pipelines complexos

---

## Decisão

Implementar um **microserviço Python com LangGraph** para orquestrar pipelines multi-agente, comunicando-se com a aplicação Laravel via HTTP assíncrono (request → queue → callback). O microserviço roda como container Docker no mesmo stack, compartilhando rede e infraestrutura.

### Abordagem híbrida

```
Laravel (Prism) → Operações simples de IA
                  Geração de títulos/hashtags, adaptação cross-network,
                  brand safety pontual, sentiment analysis
                  ~90% das chamadas de IA

Python (LangGraph) → Pipelines multi-agente complexos
                     Content Creation Pipeline, Content DNA Deep Analysis,
                     Social Listening Intelligence, Visual Adaptation
                     ~10% das chamadas, mas as de maior valor
```

### Princípio: Contratos como ponto de extensão

A arquitetura Clean Architecture já preparou o terreno. Os `Contracts` na Application Layer funcionam como **portas**. Trocar a implementação de "chamada direta ao LLM" para "chamada ao microserviço LangGraph" é apenas uma nova implementação do contrato na Infrastructure Layer — **zero mudança no domínio ou application layer**.

```
Application Layer:  TextGeneratorInterface (contrato existente)
                          │
Infrastructure:     PrismTextGenerator (implementação atual, single-shot)
                          │
                    LangGraphTextGenerator (nova implementação, multi-agent)
                          │
                          ▼
                    POST http://ai-agents:8000/api/v1/generate
                          │
                          ▼
                    Python LangGraph (Planner → Writer → Reviewer → Optimizer)
```

### Critérios de decisão: single-shot vs multi-agent

| Critério | Single-shot (Prism) | Multi-agent (LangGraph) |
|----------|-------------------|----------------------|
| Latência aceitável | < 3 segundos | < 30 segundos (assíncrono) |
| Qualidade necessária | Suficiente com prompt engineering | Requer múltiplas perspectivas |
| Custo por execução | < $0.01 | < $0.10 (múltiplas chamadas LLM) |
| Frequência | Alta (100+ req/dia) | Baixa-média (10-50 req/dia) |
| Exemplos | Títulos, hashtags, adaptação texto | Full content, DNA profiling, resposta inteligente, adaptação visual |

---

## Arquitetura do Microserviço

### Docker: Container ai-agents

O microserviço é um container Docker adicional no `docker-compose.yml`, integrado à mesma rede e infraestrutura existentes.

```yaml
# docker-compose.yml (novo serviço)
ai-agents:
  build:
    context: ./ai-agents
    dockerfile: Dockerfile
    args:
      UID: ${UID:-1000}
      GID: ${GID:-1000}
  container_name: smm-ai-agents
  restart: unless-stopped
  environment:
    - OPENAI_API_KEY=${OPENAI_API_KEY}
    - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
    - DATABASE_URL=postgresql://${DB_USERNAME}:${DB_PASSWORD}@postgres:5432/${DB_DATABASE}
    - REDIS_URL=redis://redis:6379/4
    - CALLBACK_BASE_URL=http://nginx:80/api/v1/internal
    - LOG_LEVEL=info
    - WORKERS=2
  ports:
    - "${AI_AGENTS_PORT:-8001}:8000"
  depends_on:
    postgres:
      condition: service_healthy
    redis:
      condition: service_healthy
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:8000/health"]
    interval: 30s
    timeout: 5s
    retries: 3
    start_period: 30s
  networks:
    - social-media-net
  volumes:
    - ./ai-agents:/app
```

### Dockerfile do microserviço

```dockerfile
# ai-agents/Dockerfile
FROM python:3.12-slim AS base

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} appuser && \
    useradd -u ${UID} -g appuser -m -s /bin/bash appuser

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

RUN chown -R appuser:appuser /app

USER appuser

EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "2"]
```

### Estrutura do projeto Python

```
ai-agents/
├── Dockerfile
├── requirements.txt
├── pyproject.toml
├── app/
│   ├── main.py                          # FastAPI application
│   ├── config.py                        # Settings (env vars)
│   ├── api/
│   │   ├── __init__.py
│   │   ├── routes.py                    # HTTP endpoints
│   │   └── schemas.py                   # Pydantic request/response models
│   ├── agents/
│   │   ├── __init__.py
│   │   ├── content_creation/
│   │   │   ├── __init__.py
│   │   │   ├── graph.py                 # LangGraph StateGraph definition
│   │   │   ├── planner.py              # Planner agent node
│   │   │   ├── writer.py               # Writer agent node
│   │   │   ├── reviewer.py             # Reviewer agent node
│   │   │   └── optimizer.py            # Optimizer agent node
│   │   ├── content_dna/
│   │   │   ├── __init__.py
│   │   │   ├── graph.py
│   │   │   ├── style_analyzer.py
│   │   │   ├── engagement_analyzer.py
│   │   │   └── synthesizer.py
│   │   ├── social_listening/
│   │   │   ├── __init__.py
│   │   │   ├── graph.py
│   │   │   ├── classifier.py
│   │   │   ├── sentiment_analyzer.py
│   │   │   ├── response_strategist.py
│   │   │   └── safety_checker.py
│   │   └── visual_adaptation/
│   │       ├── __init__.py
│   │       ├── graph.py
│   │       ├── vision_analyzer.py
│   │       ├── crop_strategist.py
│   │       ├── network_adapters.py
│   │       └── quality_checker.py
│   ├── services/
│   │   ├── __init__.py
│   │   ├── llm_factory.py              # LangChain LLM wrappers
│   │   ├── callback_service.py          # POST resultados de volta ao Laravel
│   │   └── embedding_service.py         # Acesso a pgvector
│   └── shared/
│       ├── __init__.py
│       ├── state.py                     # State classes para LangGraph
│       └── models.py                    # Modelos compartilhados
└── tests/
    ├── __init__.py
    ├── test_content_creation.py
    ├── test_content_dna.py
    └── test_social_listening.py
```

### Dependências Python (requirements.txt)

```
langgraph>=0.2.0
langchain>=0.3.0
langchain-openai>=0.2.0
langchain-anthropic>=0.2.0
fastapi>=0.115.0
uvicorn>=0.32.0
pydantic>=2.0
httpx>=0.27.0
asyncpg>=0.30.0
redis>=5.0
Pillow>=11.0
structlog>=24.0
```

---

## Pipelines Multi-Agente

### Pipeline 1: Content Creation (maior impacto)

```
                    ┌──────────────┐
                    │   REQUEST    │
                    │  (topic,     │
                    │   provider,  │
                    │   style)     │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   PLANNER    │
                    │              │
                    │ Define:      │
                    │ - Tom/voz    │
                    │ - Estrutura  │
                    │ - Público    │
                    │ - CTA style  │
                    │ - Constraints│
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   WRITER     │
                    │              │
                    │ Gera conteúdo│
                    │ seguindo o   │
                    │ briefing do  │
                    │ Planner      │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  REVIEWER    │
                    │              │
                    │ Verifica:    │
                    │ - Brand safe │
                    │ - Tom correto│
                    │ - Guidelines │
                    │ - Qualidade  │
                    └──────┬───────┘
                           │
                  ┌────────┴────────┐
                  │                 │
          Aprovado?           Reprovado
                  │                 │
                  ▼                 ▼
          ┌──────────────┐  ┌──────────────┐
          │  OPTIMIZER   │  │   WRITER     │
          │              │  │  (retry com  │
          │ Otimiza por  │  │   feedback)  │
          │ rede social: │  └──────────────┘
          │ hashtags, CTA│
          │ tamanho, mídia│
          └──────┬───────┘
                 │
          ┌──────▼───────┐
          │   RESPONSE   │
          │ (callback    │
          │  ao Laravel) │
          └──────────────┘
```

**State do LangGraph:**

```python
from typing import TypedDict, Annotated
from langgraph.graph import StateGraph

class ContentCreationState(TypedDict):
    # Input
    organization_id: str
    topic: str
    provider: str
    style_profile: dict | None
    rag_examples: list[dict]
    # Planning
    brief: dict | None
    # Writing
    draft: str | None
    # Review
    review_result: dict | None
    review_passed: bool
    retry_count: int
    # Optimization
    final_content: dict | None
    # Meta
    callback_url: str
    correlation_id: str
```

**Contexto injetado (vindo do Laravel via request):**

- `style_profile`: Perfil de estilo da organização (ADR-017 N5)
- `rag_examples`: Top performers similares (ADR-017 N2)
- `provider`: Rede social alvo (para otimização de formato)
- Org-specific brand guidelines (se configurado)

### Pipeline 2: Content DNA Deep Analysis

```
Content Collector → Style Analyzer → Engagement Analyzer → Profile Synthesizer
                         │                  │                     │
                    Analisa tom,       Correlaciona         Combina todas
                    vocabulário,       métricas com         as análises em
                    estrutura,         padrões de           perfil unificado
                    padrões            conteúdo             com scores
```

**Diferencial vs single-shot:** Cada agente é especialista em uma dimensão. O Synthesizer combina as análises em um perfil multidimensional muito mais rico do que uma análise monolítica.

### Pipeline 3: Social Listening Intelligence

```
Mention Classifier → Sentiment Analyzer → Response Strategist → Safety Checker
       │                    │                    │                    │
  Categoriza menção    Análise profunda      Sugere resposta    Verifica brand
  (elogio, reclamação, de sentimento com     contextualizada    safety antes
  pergunta, crise,     contexto cultural     com tom adequado   de retornar
  spam)                e ironia              à marca
```

**Diferencial vs single-shot:** A cadeia de agentes permite tratamento diferenciado por categoria de menção. Uma crise recebe processamento profundo; um elogio recebe resposta rápida. O Safety Checker garante que nenhuma resposta automática viole brand guidelines.

### Pipeline 4: Visual Adaptation Cross-Network

Adapta uma imagem original para múltiplas redes sociais usando **LLMs multimodais com visão** (GPT-4o, Claude) para entender semanticamente a composição e gerar crops inteligentes.

#### Requisitos por rede

| Rede | Formato preferido | Aspect Ratio | Resolução | Particularidades |
|------|------------------|-------------|-----------|-----------------|
| Instagram Feed | Quadrado/Retrato | 1:1, 4:5 | 1080×1080, 1080×1350 | Carrossel até 10 imagens |
| Instagram Stories/Reels | Vertical | 9:16 | 1080×1920 | Safe zones para texto/stickers |
| TikTok | Vertical | 9:16 | 1080×1920 | Zona inferior reservada para UI |
| YouTube Thumbnail | Landscape | 16:9 | 1280×720 | Precisa de texto grande legível |
| YouTube Shorts | Vertical | 9:16 | 1080×1920 | Thumbnail automática |

#### Graph de execução

```
                    ┌──────────────────┐
                    │  IMAGEM ORIGINAL  │
                    │  + redes alvo     │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  VISION ANALYZER  │
                    │                   │
                    │ LLM multimodal    │
                    │ (GPT-4o, Claude)  │
                    │ Analisa:          │
                    │ - Sujeito/foco    │
                    │ - Composição      │
                    │ - Texto na imagem │
                    │ - Cores/branding  │
                    │ - Safe zones      │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  CROP STRATEGIST  │
                    │                   │
                    │ Decide por rede:  │
                    │ - Ponto de corte  │
                    │ - Aspect ratio    │
                    │ - O que preservar │
                    │ - O que remover   │
                    └────────┬─────────┘
                             │
               ┌─────────────┼─────────────┐
               │             │             │
        ┌──────▼──────┐ ┌───▼────┐ ┌──────▼──────┐
        │  INSTAGRAM   │ │ TIKTOK │ │  YOUTUBE    │
        │  ADAPTER     │ │ ADAPTER│ │  ADAPTER    │
        │              │ │        │ │             │
        │ Gera versões:│ │ 9:16   │ │ 16:9 thumb  │
        │ 1:1 feed     │ │ safe   │ │ com texto   │
        │ 4:5 feed     │ │ zones  │ │ grande e    │
        │ 9:16 stories │ │        │ │ legível     │
        └──────┬───────┘ └───┬────┘ └──────┬──────┘
               │             │             │
               └─────────────┼─────────────┘
                             │
                    ┌────────▼─────────┐
                    │  QUALITY CHECKER  │
                    │                   │
                    │ LLM multimodal    │
                    │ Valida cada       │
                    │ adaptação:        │
                    │ - Sujeito visível │
                    │ - Texto legível   │
                    │ - Brand safety    │
                    │ - Qualidade final │
                    └──────────────────┘
```

#### Agentes especializados

1. **Vision Analyzer** — LLM multimodal analisa semanticamente a imagem: identifica sujeito principal, composição, texto overlay, logos, safe zones. Retorna mapa semântico estruturado (`{subject_position: "left_third", text_regions: [...], brand_elements: [...]}`)

2. **Crop Strategist** — Com base no mapa semântico, define estratégia de corte por rede alvo. Para cada formato (1:1, 4:5, 9:16, 16:9), determina coordenadas de crop que preservam o sujeito e elementos importantes. Considera safe zones de cada rede (UI overlay do TikTok, stickers do Stories)

3. **Network Adapters** (paralelo) — Executam o crop/resize com as instruções do Strategist. Para YouTube thumbnails, podem adicionar texto overlay otimizado para legibilidade. Executam via Pillow/Sharp (processamento de imagem nativo, não LLM)

4. **Quality Checker** — LLM multimodal recebe cada versão adaptada e valida: sujeito principal visível e centralizado, texto legível se presente, composição equilibrada, brand elements preservados. Rejeita adaptações com problemas (triggers re-crop pelo Strategist)

#### State do LangGraph

```python
class VisualAdaptationState(TypedDict):
    # Input
    organization_id: str
    image_url: str
    target_networks: list[str]  # ["instagram_feed", "instagram_stories", "tiktok", "youtube_thumb"]
    brand_guidelines: dict | None
    # Vision Analysis
    semantic_map: dict | None  # {subject_position, text_regions, brand_elements, dominant_colors}
    # Crop Strategy
    crop_plans: dict | None  # {network: {x, y, width, height, aspect_ratio, preserve_regions}}
    # Adapted versions
    adapted_images: dict | None  # {network: {url, width, height, format}}
    # Quality check
    quality_results: dict | None  # {network: {passed, issues, score}}
    rejected_networks: list[str]
    retry_count: int
    # Meta
    callback_url: str
    correlation_id: str
```

**Diferencial vs crop mecânico:** Smart crop tradicional (saliency-based) não entende contexto — corta rostos, perde texto, ignora composição intencional. O Vision Analyzer + Crop Strategist **entende** que "o produto está na mão esquerda da modelo" e preserva esse contexto em todos os formatos. O Quality Checker garante que nenhuma adaptação degradada chegue ao usuário.

**Dependência de infraestrutura:** Requer `ImageGeneratorInterface` (ADR-016) para processamento de imagem e storage via MinIO (já existente). O pipeline usa LLMs multimodais para análise/decisão e bibliotecas de processamento de imagem (Pillow) para execução de crops.

---

## Protocolo de Comunicação

### Fluxo Request → Callback

```
Laravel                           ai-agents (Python)
  │                                     │
  ├─ POST /api/v1/generate ────────────►│
  │  {                                  │
  │    correlation_id: "uuid",          │
  │    pipeline: "content_creation",    │
  │    input: {...},                    │
  │    callback_url: "/api/v1/internal/ │
  │      agent-callback"               │
  │  }                                  │
  │                                     │
  │◄── 202 Accepted ──────────────────  │
  │  { job_id: "uuid" }                │
  │                                     │
  │  ... LangGraph processa ...         │
  │                                     │
  │◄── POST /api/v1/internal/ ─────────│
  │    agent-callback                   │
  │  {                                  │
  │    correlation_id: "uuid",          │
  │    job_id: "uuid",                  │
  │    status: "completed",             │
  │    result: {...},                   │
  │    metadata: {                      │
  │      total_tokens: 4500,            │
  │      total_cost: 0.045,             │
  │      agents_used: ["planner",       │
  │        "writer", "reviewer",        │
  │        "optimizer"],                │
  │      duration_ms: 12500             │
  │    }                                │
  │  }                                  │
```

### Endpoint interno no Laravel (callback)

```php
// routes/api/v1/internal.php
Route::post('/agent-callback', [AgentCallbackController::class, 'handle'])
    ->middleware(['internal-only']); // Aceita apenas requests da rede Docker interna
```

### Autenticação entre serviços

- Comunicação interna via rede Docker (`social-media-net`)
- Middleware `internal-only` valida IP de origem (rede Docker interna)
- Header `X-Internal-Secret` com shared secret via variável de ambiente
- **Sem JWT** — comunicação interna não precisa de auth de usuário

### Fallback: LangGraph → Prism

Se o microserviço LangGraph falhar (timeout, erro, circuit open), o sistema faz **fallback automático** para o Prism single-shot:

```php
// Infrastructure/AIIntelligence/Services/LangGraphTextGenerator.php
final class LangGraphTextGenerator implements TextGeneratorInterface
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly PrismTextGenerator $fallback,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    public function generateText(TextPrompt $prompt): TextResult
    {
        if ($this->circuitBreaker->isOpen('ai-agents')) {
            return $this->fallback->generateText($prompt);
        }

        try {
            $response = $this->httpClient->post('http://ai-agents:8000/api/v1/generate', [...]);
            $this->circuitBreaker->recordSuccess('ai-agents');
            return TextResult::fromArray($response);
        } catch (Throwable $e) {
            $this->circuitBreaker->recordFailure('ai-agents');
            // Fallback para single-shot
            return $this->fallback->generateText($prompt);
        }
    }
}
```

### Circuit breaker para ai-agents

| Aspecto | Configuração |
|---------|-------------|
| Threshold | 3 falhas consecutivas → open |
| Reset timeout | 120 segundos → half-open |
| Half-open test | 1 request teste → fecha ou reabre |
| Storage | Redis key: `circuit:ai_agents:{pipeline}` |
| Fallback | Prism single-shot (degradação graceful) |

---

## Redis: Compartilhamento

O microserviço usa **Redis DB 4** (databases 0-3 já estão alocados):

| DB | Uso |
|----|-----|
| 0 | Cache (Laravel) |
| 1 | Queues (Laravel/Horizon) |
| 2 | Rate-limiting (Laravel) |
| 3 | Sessions (Laravel) |
| **4** | **AI Agents (LangGraph checkpoints, job status)** |

---

## Monitoramento e Observabilidade

### Logs estruturados

O microserviço emite logs JSON compatíveis com o padrão do Laravel:

```json
{
  "timestamp": "2026-03-15T10:30:00Z",
  "level": "info",
  "service": "ai-agents",
  "pipeline": "content_creation",
  "correlation_id": "uuid",
  "organization_id": "uuid",
  "agent": "reviewer",
  "message": "Content review passed",
  "duration_ms": 2300,
  "tokens_used": 1200
}
```

### Health check

```
GET http://ai-agents:8000/health
→ { "status": "healthy", "pipelines": ["content_creation", "content_dna", "social_listening"] }

GET http://ai-agents:8000/ready
→ { "ready": true, "redis": "ok", "postgres": "ok" }
```

### Métricas

| Métrica | Descrição |
|---------|-----------|
| `ai_agents_requests_total` | Total de requests por pipeline |
| `ai_agents_duration_seconds` | Duração por pipeline (histogram) |
| `ai_agents_tokens_total` | Tokens consumidos por pipeline |
| `ai_agents_cost_total` | Custo estimado por pipeline |
| `ai_agents_retries_total` | Retries do Reviewer (content creation) |
| `ai_agents_fallback_total` | Fallbacks para Prism |

---

## Custo Estimado por Pipeline

| Pipeline | Agentes | Chamadas LLM | Tokens (média) | Custo/execução | Frequência |
|----------|---------|-------------|----------------|---------------|------------|
| Content Creation | 4 | 4-6 (retry possível) | ~4.000-6.000 | ~$0.04-0.06 | 10-30/dia |
| Content DNA | 3 | 3 | ~3.000 | ~$0.03 | 1-4/semana |
| Social Listening | 4 | 4 | ~2.000 | ~$0.02 | 5-20/dia |
| Visual Adaptation | 4 | 2-4 (vision + quality) | ~2.000-4.000 | ~$0.03-0.05 | 5-15/dia |

**Custo adicional mensal estimado por organização (plano Agency):** ~$20-40

**Comparativo:** Conteúdo gerado por multi-agent tem acceptance rate estimado 40-60% superior ao single-shot, reduzindo retrabalho e edições manuais. O ROI compensa o custo adicional.

---

## Mapeamento por Plano de Assinatura

| Feature | Free | Creator | Professional | Agency |
|---------|------|---------|-------------|--------|
| Geração single-shot (Prism) | ✅ | ✅ | ✅ | ✅ |
| Content Creation Pipeline | ❌ | ❌ | ✅ (3/dia) | ✅ (ilimitado) |
| Visual Adaptation Pipeline | ❌ | ❌ | ✅ (5/dia) | ✅ (ilimitado) |
| Content DNA Deep Analysis | ❌ | ❌ | ❌ | ✅ |
| Social Listening Intelligence | ❌ | ❌ | ❌ | ✅ |

> **Nota:** Planos Free e Creator continuam usando Prism single-shot. Multi-agent é premium por custo e valor.

---

## Alternativas Consideradas

### 1. Agentes nativos em PHP com Prism Tool Use

- **Prós:** Zero dependência nova, mesma stack, Prism suporta tool use
- **Contras:** Orquestração manual de agentes, sem state management, sem checkpointing, sem visualização de graph
- **Por que descartada:** Reimplementar LangGraph em PHP seria complexo e sem benefício. Prism é excelente para single-shot mas não foi desenhado para orquestração multi-agente

### 2. CrewAI como alternativa ao LangGraph

- **Prós:** API mais simples, menos boilerplate, conceito de "crew" intuitivo
- **Contras:** Menos controle sobre o fluxo, sem conditional edges, menos maduro, sem checkpointing nativo
- **Por que descartada:** LangGraph oferece controle granular sobre o graph de execução (conditional edges, cycles, human-in-the-loop), essencial para o Reviewer loop do Content Creation Pipeline

### 3. Microsserviço Node.js com LangGraph.js

- **Prós:** JavaScript, potencial sharing de types com futuro frontend
- **Contras:** LangGraph.js menos maduro que Python, ecossistema de ML/NLP menor
- **Por que descartada:** Python tem o ecossistema mais maduro para AI/ML, e LangGraph Python é a implementação de referência

### 4. Tudo em LangGraph (substituir Prism)

- **Prós:** Stack de IA unificado
- **Contras:** Overhead gigante para operações simples, latência desnecessária, custo multiplicado, dependência total do microserviço Python
- **Por que descartada:** 90% das chamadas de IA são simples o suficiente para single-shot. Usar LangGraph para gerar um título seria over-engineering extremo

---

## Consequências

### Positivas

- **Qualidade de conteúdo drasticamente superior** — múltiplas perspectivas e revisão automatizada
- **Diferencial competitivo inédito** — nenhum concorrente usa pipelines multi-agente
- **Fallback automático** — degradação graceful para Prism se o microserviço falhar
- **Zero impacto no domínio** — implementação é uma nova classe na Infrastructure Layer
- **Escalabilidade independente** — container Python escala separado do Laravel
- **Reutiliza infraestrutura existente** — Docker, PostgreSQL, Redis, rede
- **LangGraph checkpointing** — state persistido, possibilidade de human-in-the-loop futuro

### Negativas

- **Stack dual (PHP + Python)** — duas linguagens para manter
- **Complexidade operacional** — mais um container para monitorar, deploy, debug
- **Latência adicional** — comunicação HTTP entre containers (~5-15ms overhead)
- **Custo por execução maior** — 4-6 chamadas LLM vs 1 chamada
- **Cold start** — container Python leva ~5-10s para iniciar vs PHP-FPM

### Riscos

| Risco | Probabilidade | Impacto | Mitigação |
|-------|-------------|---------|-----------|
| Microserviço Python indisponível | Baixa | Alto | Circuit breaker + fallback automático para Prism |
| Custo de LLM escala inesperadamente | Média | Médio | Limites diários por organização + cost tracking em tempo real |
| Latência multi-agente > 30s | Baixa | Médio | Timeout configurável + fallback para single-shot |
| Manutenção de duas stacks | Alta | Baixo | Python é restrito ao microserviço; toda regra de negócio permanece em PHP |
| LangGraph breaking changes | Baixa | Médio | Pin de versão + testes automatizados |
| Reviewer loop infinito | Baixa | Baixo | `max_retries: 2` — após 2 reviews reprovados, retorna melhor versão disponível |

---

## Referências

- [ADR-009](adr-009-laravel-ai-sdk.md) — Laravel AI SDK (Prism) — base para operações single-shot
- [ADR-016](adr-016-multi-provider-ai.md) — Multi-Provider AI — factory e registry de providers
- [ADR-017](adr-017-ai-learning-feedback-loop.md) — AI Learning & Feedback Loop — dados que alimentam os agentes
- [LangGraph Documentation](https://langchain-ai.github.io/langgraph/) — Framework de orquestração multi-agente
- `.claude/skills/06-domain/ai-intelligence.md` — Content DNA, Performance Prediction
- `.claude/skills/03-integrations/ai-integration.md` — Arquitetura de integração IA
