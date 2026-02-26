# Architecture Decision Records (ADR)

> **Projeto:** Social Media Manager\
> **Data de criação:** 2026-02-15

## O que são ADRs?

Architecture Decision Records documentam decisões arquiteturais significativas tomadas
durante o desenvolvimento do projeto. Cada ADR segue o formato:

- **Status:** Proposed → Accepted → Deprecated → Superseded
- **Context:** O problema ou situação que motivou a decisão
- **Decision:** A decisão tomada e o raciocínio
- **Consequences:** Impactos positivos, negativos e riscos

## Índice

### Fundação & Infraestrutura

| # | Título | Status |
|---|--------|--------|
| [ADR-001](adr-001-clean-architecture-ddd.md) | Clean Architecture com DDD | Accepted |
| [ADR-002](adr-002-laravel-framework.md) | Laravel 12 como framework | Accepted |
| [ADR-003](adr-003-postgresql-pgvector.md) | PostgreSQL com pgvector | Accepted |
| [ADR-004](adr-004-redis-cache-queues.md) | Redis para cache e filas | Accepted |
| [ADR-005](adr-005-jwt-authentication.md) | Autenticação JWT com RS256 | Accepted |
| [ADR-019](adr-019-multi-tenancy-strategy.md) | Multi-Tenancy Strategy — Shared Database | Accepted |

### Design Patterns & Integrações

| # | Título | Status |
|---|--------|--------|
| [ADR-006](adr-006-adapter-pattern-social-media.md) | Adapter Pattern para redes sociais | Accepted |
| [ADR-007](adr-007-domain-events.md) | Arquitetura orientada a eventos de domínio | Accepted |
| [ADR-008](adr-008-api-versioning.md) | Versionamento de API via URL prefix | Accepted |
| [ADR-009](adr-009-laravel-ai-sdk.md) | Laravel AI SDK para integração com IA | Accepted |
| [ADR-010](adr-010-pest-testing-strategy.md) | Pest 4 como framework de testes | Accepted |

### Estratégias & Convenções

| # | Título | Status |
|---|--------|--------|
| [ADR-011](adr-011-folder-structure.md) | Estrutura de pastas por bounded context | Accepted |
| [ADR-012](adr-012-encryption-strategy.md) | Estratégia de criptografia para tokens sociais | Accepted |
| [ADR-013](adr-013-queue-publishing-strategy.md) | Estratégia de filas para publicação | Accepted |
| [ADR-014](adr-014-cursor-pagination.md) | Cursor-based pagination | Accepted |
| [ADR-015](adr-015-soft-delete-strategy.md) | Estratégia de soft delete | Accepted |

### IA & Machine Learning

| # | Título | Status |
|---|--------|--------|
| [ADR-016](adr-016-multi-provider-ai.md) | Arquitetura Multi-Provider AI | Accepted |
| [ADR-017](adr-017-ai-learning-feedback-loop.md) | AI Learning & Feedback Loop | Accepted |
| [ADR-021](adr-021-multi-agent-ai-langgraph.md) | Arquitetura Multi-Agent com LangGraph | Accepted |

### Integrações Externas

| # | Título | Status |
|---|--------|--------|
| [ADR-018](adr-018-native-crm-connectors.md) | Native CRM Connectors Strategy | Accepted |

### Futuro (Fase 5+)

| # | Título | Status |
|---|--------|--------|
| ADR-020 | Paid Advertising Strategy — Marketing APIs & Audience Targeting | Planned |

> **Nota:** ADR-020 será criado quando a implementação da Fase 5 (Sprints 17-18) iniciar. Cobrirá: Adapter Pattern para Marketing APIs (Meta Ads, TikTok Ads, Google Ads), estratégia de OAuth separado para contas de anúncios, normalização de targeting spec cross-platform, tratamento de billing/transferência monetária, e integração com o AI Learning Loop (ADR-017) para aprendizado a partir de dados de tráfego pago.

> **Nota:** ADR-021 documenta a arquitetura Multi-Agent com LangGraph (microserviço Python), planejada para o Sprint 19 (Fase 6). Complementa ADR-016 e ADR-017 com pipelines multi-agente para Content Creation, Content DNA e Social Listening.
