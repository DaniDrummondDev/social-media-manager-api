# Social Media Manager — PRD (Product Requirements Document)

> **Versão:** 1.0.0
> **Data:** 2026-02-15
> **Status:** Draft

## Sobre este documento

Este PRD define o escopo completo do **Social Media Manager**, um SaaS para agendamento,
publicação e gestão de conteúdos em múltiplas redes sociais com inteligência artificial
e automação de engajamento.

## Índice

| # | Documento | Descrição |
|---|-----------|-----------|
| 01 | [Visão do Produto](01-visao-produto.md) | Visão, problema, proposta de valor, escopo e fases |
| 02 | [Personas & User Stories](02-personas-user-stories.md) | Personas-alvo, épicos e user stories com critérios de aceite |
| 03 | [Requisitos Funcionais](03-requisitos-funcionais.md) | Especificação detalhada das funcionalidades por módulo |
| 04 | [Requisitos Não-Funcionais](04-requisitos-nao-funcionais.md) | Performance, segurança, escalabilidade, LGPD, observabilidade |
| 05 | [Bounded Contexts (DDD)](05-bounded-contexts.md) | Contextos delimitados, agregados, entidades, eventos de domínio |
| 06 | [Integrações](06-integracoes.md) | APIs de redes sociais, OpenAI, CRM, OAuth, webhooks |
| 07 | [Regras de Negócio](07-regras-negocio.md) | Regras de agendamento, publicação, automação e limites |
| 08 | [Glossário](08-glossario.md) | Linguagem ubíqua do domínio |

## Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| Linguagem | PHP 8.4 |
| Framework | Laravel 12 |
| Banco de dados | PostgreSQL (pgvector) |
| Cache / Filas | Redis |
| IA | Laravel AI SDK (OpenAI / ChatGPT) |
| Testes | Pest 4 (com plugin de arquitetura) |
| Arquitetura | DDD, Clean Architecture, SOLID |

## Convenções deste documento

- **MUST** — Requisito obrigatório
- **SHOULD** — Requisito recomendado
- **MAY** — Requisito opcional
- **RF-XXX** — Requisito Funcional
- **RNF-XXX** — Requisito Não-Funcional
- **RN-XXX** — Regra de Negócio
- **US-XXX** — User Story
