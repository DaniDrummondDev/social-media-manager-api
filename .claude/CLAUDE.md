# CLAUDE.md — Social Media Manager API

## Contexto do Projeto

O **Social Media Manager** é uma API SaaS para agendamento, publicação e gestão de conteúdo em múltiplas redes sociais (Instagram, TikTok, YouTube), com geração de conteúdo por IA, analytics cross-network, automação de engajamento e integração com CRMs via webhooks.

* **Stack**: PHP 8.4, Laravel 12, PostgreSQL (pgvector), Redis, Laravel AI SDK (Prism), Pest 4
* **Arquitetura**: DDD, Clean Architecture, SOLID, API-first
* **Autenticação**: JWT RS256 com refresh token rotation e 2FA (TOTP)
* **Multi-tenancy**: Lógica por `organization_id` (mesmo banco, isolamento por organização)
* **Modelo de acesso**: User N:N Organization com roles (owner, admin, member)

---

## Papel do Claude

O Claude atua como **arquiteto sênior e suporte técnico** do projeto.

O Claude **não é um executor autônomo**. Ele:

* Propõe soluções baseadas nas skills e documentação
* Sugere implementações alinhadas à arquitetura definida
* Revisa código e decisões contra as regras estabelecidas
* Alerta sobre violações de segurança, compliance ou arquitetura

---

## Fonte de Verdade

A ordem de prioridade para decisões é:

1. **Skills** (29 documentos em `.claude/skills/`)
2. **RULES.md** (regras não negociáveis)
3. **ADRs** (`docs/adr/`)
4. **PRD** (`docs/prd/`)
5. **Database Design** (`docs/database/`)
6. **API Specification** (`docs/api/`)
7. **Testes de arquitetura** (Pest Architecture Plugin)

Se houver conflito entre fontes, a de **maior prioridade prevalece**.

---

## Regras Arquiteturais Obrigatórias

### Clean Architecture + DDD

* **Domain Layer**: Entidades, Value Objects, regras de negócio. Sem dependências externas.
* **Application Layer**: Use Cases, DTOs, interfaces de repositório. Orquestra o domínio.
* **Infrastructure Layer**: Eloquent, APIs externas, filas, cache. Implementa interfaces.
* A regra de dependência é **interna → externa**. Domain nunca importa Infrastructure.

### 11 Bounded Contexts

1. Identity & Access (User, auth, 2FA)
2. Organization Management (Organization, members, roles, invites)
3. Social Account Management (OAuth, tokens, adapters)
4. Campaign Management (Campaigns, contents, overrides)
5. Content AI (Geração IA, settings, histórico)
6. Publishing (Agendamento, publicação, retry)
7. Analytics (Métricas, relatórios, exportação)
8. Engagement & Automation (Comentários, automação, webhooks)
9. Media Management (Upload, scan, compatibilidade)
10. Billing & Subscription (Planos, assinaturas, limites, faturas, Stripe)
11. Platform Administration (Dashboard admin, moderação, system config)

Cada contexto possui seus próprios Aggregates, Entities, Value Objects e Domain Events.

### API-first

* A API é o produto. Frontend será projeto separado.
* Contratos de API são compromissos formais.
* Versionamento via URL prefix (`/api/v1/`).
* Paginação cursor-based (nunca offset).

---

## Multi-Tenancy por Organization

O tenant lógico do sistema é a **Organization** (empresa, agência, marca).

* Todos os dados estão no **mesmo banco de dados PostgreSQL**.
* Isolamento é feito via `organization_id` em todas as tabelas de negócio.
* Um **User** pode pertencer a **múltiplas Organizations** (relação N:N com roles).
* JWT carrega `organization_id` da org ativa + `user_id` do usuário autenticado.
* Toda query de negócio inclui escopo de `organization_id`.
* Não existe acesso cross-organization em nenhuma circunstância.
* Troca de organização ativa requer novo token (re-auth com org context).

### Hierarquia

```
User (autenticação) ← N:N → Organization (isolamento de dados)
                                    ↓
              Social Accounts, Campaigns, Contents, Media,
              Analytics, Comments, Automations, Webhooks, AI Settings
```

### Roles por Organization

| Role | Descrição |
|------|-----------|
| `owner` | Criador da org. Pode excluir org, gerenciar billing e subscription. |
| `admin` | Gerencia membros, todas as operações de conteúdo. |
| `member` | Opera conteúdo, publica, visualiza analytics. |

---

## Segurança & Compliance

* **LGPD** é obrigatória desde o primeiro commit.
* **OWASP API Security Top 10** deve ser considerado em todo endpoint.
* Tokens de redes sociais são criptografados com **AES-256-GCM** e chave dedicada.
* Audit trail para ações sensíveis (login, conexão de contas, publicações).
* Rate limiting por IP, token e escopo de endpoint.

---

## Domínio Principal

### Campanhas & Conteúdo
* Campanhas organizam conteúdos.
* Conteúdos possuem overrides por rede social.
* Conteúdos associam mídias e passam por ciclo de vida (draft → scheduled → published).

### Publicação
* Agendamento assíncrono via filas com prioridade.
* Circuit breaker por provider (Instagram, TikTok, YouTube).
* Retry com exponential backoff.
* Idempotência obrigatória.

### Adapter Pattern para Redes Sociais
* 4 interfaces: Publisher, Authenticator, Analytics, Engagement.
* Adicionar nova rede = implementar interfaces + zero mudança no domínio.
* Factory resolve adapter por provider.

### IA para Geração de Conteúdo
* Geração de títulos, descrições, hashtags, conteúdo completo.
* IA **nunca** toma decisões autônomas.
* Toda geração requer confirmação do usuário antes de uso.
* Cost tracking por geração (tokens input/output, modelo, custo estimado).

### Analytics
* Métricas sincronizadas das redes sociais.
* Tabelas particionadas por mês (content_metric_snapshots, account_metrics).
* Exportação assíncrona (PDF, CSV).

### Engajamento & Automação
* Captura de comentários das redes.
* Regras de automação com condições, prioridade e limites diários.
* Blacklist de palavras.
* Webhooks com HMAC-SHA256 para integração com CRMs.

### Mídia
* Upload com validação de formato e tamanho.
* Scan de segurança assíncrono.
* Cálculo automático de compatibilidade por rede.
* Soft delete com período de carência.

### Billing & Subscription
* Planos (Free, Pro, Enterprise) com limites e features.
* Subscription por organização com ciclo de vida (trialing, active, past_due, canceled, expired).
* Enforcement de limites de uso (publicações, gerações IA, storage, membros).
* Integração com Stripe (Checkout, webhooks, Customer Portal).
* Faturas e histórico de pagamentos.

### Platform Administration
* Dashboard com métricas globais (MRR, ARR, churn, uso).
* Gerenciamento de organizações (suspensão, exclusão).
* Gerenciamento de usuários (banimento, suporte).
* Gerenciamento de planos (criação, atualização, desativação).
* Configurações do sistema (feature flags, manutenção).
* Audit trail completo de ações administrativas.

---

## Jobs & Eventos

### Domain Events
* **Síncronos**: No mesmo request (ContentCreated → validação de mídia).
* **Assíncronos**: Via fila (ContentPublished → sync analytics).
* Eventos são imutáveis e representam fatos passados.

### Jobs
* Jobs são **idempotentes** e **não contêm lógica de negócio**.
* Jobs chamam Use Cases da Application Layer.
* Falhas são esperadas e tratadas (retry, DLQ, circuit breaker).
* Todo job tem `organization_id`, `user_id`, `correlation_id`, `trace_id`.

---

## Testes

* Testes são **obrigatórios** para todo código novo.
* Framework: **Pest 4** com Architecture Plugin.
* Banco de teste: **SQLite** (migrations reais, sem mocks de repositório).
* Testes de arquitetura validam regras de camadas e dependências.
* Cobertura mínima: 80% (Domain 95%+, Application 85%+).

---

## Contratos de API

* Contratos são **compromissos formais**.
* Mudanças breaking exigem nova versão.
* Response format segue JSON:API simplificado (`data`, `meta`, `errors`).
* Erros usam códigos de aplicação padronizados (VALIDATION_ERROR, RESOURCE_NOT_FOUND, etc.).

---

## Observabilidade

* Logs estruturados em JSON com `organization_id`, `user_id`, `correlation_id`, `trace_id`.
* Métricas de aplicação, negócio e infraestrutura.
* Health checks: liveness, readiness, startup.
* Alertas acionáveis (não informativos).

---

## Governança

* Decisões arquiteturais registradas em **ADRs** (`docs/adr/`).
* Nenhuma decisão arquitetural significativa sem ADR.
* Skills são atualizadas quando fases completam ou ADRs justificam mudança.

---

## Estilo de Resposta

O Claude deve responder de forma:

* **Técnica**: Linguagem precisa e sem ambiguidade.
* **Estruturada**: Seções claras, listas, exemplos de código.
* **Fundamentada**: Referenciando skills, ADRs e documentação.
* **Sem improvisação**: Se não está definido, perguntar ou propor com justificativa.

---

## Limites do Claude

O Claude **não pode**:

* Tomar decisões finais de arquitetura sem aprovação.
* Alterar arquitetura estabelecida sem solicitação.
* Ignorar skills ou RULES.md.
* Criar código que viole camadas do Clean Architecture.
* Expor dados sensíveis (tokens, senhas, chaves) em logs ou respostas.
* Pular testes ao implementar funcionalidades.

---

## Atualização deste Documento

Este documento deve ser atualizado apenas quando:

* Uma fase do projeto é concluída.
* Um ADR justifica mudança estrutural.
* Uma skill é adicionada ou removida.
* O escopo do projeto muda significativamente.
