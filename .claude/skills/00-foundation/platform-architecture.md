# Arquitetura da Plataforma SaaS

## Objetivo

Definir a arquitetura da plataforma SaaS API-first do Social Media Manager, incluindo componentes de infraestrutura, padrões de comunicação, isolamento de dados e responsabilidades de cada camada do sistema.

---

## Visao Geral

O Social Media Manager e uma **API SaaS** para agendamento, publicacao e gestao de conteudo em multiplas redes sociais (Instagram, TikTok, YouTube), com geracao de conteudo por IA, analytics cross-network e automacao de engajamento.

A plataforma serve **organizacoes** (multi-tenancy logica por `organization_id`). Todos os dados residem em um unico banco de dados PostgreSQL, isolados por `organization_id`. Usuarios podem pertencer a multiplas organizacoes (N:N com roles).

---

## Separacao: Infraestrutura da Plataforma vs Dominio do Usuario

### Infraestrutura da Plataforma (Platform Layer)

Responsavel por tudo que **nao e regra de negocio**:

- **Autenticacao e Autorizacao**: JWT RS256, refresh token rotation, 2FA (TOTP)
- **Seguranca**: Rate limiting, CORS, headers de seguranca, validacao de input
- **Observabilidade**: Logs estruturados (JSON), metricas, health checks, tracing
- **API Gateway**: Roteamento, versionamento (`/api/v1/`), middleware pipeline
- **Rate Limiting**: Por IP, por token, por escopo de endpoint
- **Filas e Workers**: Processamento assincrono de jobs
- **Cache**: Gerenciamento de cache distribuido via Redis
- **Criptografia**: AES-256-GCM para tokens de redes sociais, bcrypt para senhas

### Dominio da Organizacao (Organization Domain)

Tudo que pertence ao contexto de negocio de uma organizacao:

- **Contas sociais**: Conexoes OAuth2 com Instagram, TikTok, YouTube
- **Campanhas**: Organizacao de conteudo por objetivos
- **Conteudos**: Pecas de conteudo com overrides por rede social
- **Midias**: Upload, validacao, scan de seguranca
- **Publicacao**: Agendamento e publicacao via APIs externas
- **Analytics**: Metricas sincronizadas das redes sociais
- **Engajamento**: Comentarios, automacao de respostas, webhooks
- **IA**: Geracao de conteudo com controle de uso e custos
- **Billing**: Planos, assinaturas, limites de uso, faturas

### Dominio da Plataforma (Platform Domain)

Tudo que pertence ao controle administrativo da plataforma como um todo:

- **Administracao**: Dashboard de metricas globais, gerenciamento de orgs e users
- **Planos**: Criacao e gerenciamento de planos disponiveis
- **Moderacao**: Suspensao de orgs, banimento de users
- **Configuracoes**: Feature flags, modo manutencao, limites globais

---

## Multi-Tenancy por Organization

### Hierarquia

```
User (autenticacao) ←─ N:N com roles ─→ Organization (tenant logico)
                                              ↓
                  Social Accounts, Campaigns, Contents, Media,
                  Analytics, Comments, Automations, Webhooks, AI Settings,
                  Subscription (billing)

Platform Admin ─→ Organizations, Users, Plans, System Config, Metrics
```

### Regras absolutas

- **Um banco de dados**: PostgreSQL unico para todas as organizacoes
- **Escopo por `organization_id`**: Toda query de negocio inclui filtro de `organization_id`
- **Sem acesso cross-organization**: Nenhum endpoint retorna dados de outra org
- **Tokens isolados**: Tokens de redes sociais criptografados por organizacao
- **Embeddings isolados**: Vetores pgvector filtrados por `organization_id`
- **Jobs com contexto**: Todo job carrega `organization_id`, `user_id`, `correlation_id`, `trace_id`
- **Logs rastreaveis**: Logs incluem `organization_id` + `user_id`
- **Roles contextuais**: Permissoes do user dependem do role na org ativa

### Como funciona na pratica

```
Request HTTP → JWT decode → resolve organization_id + user_id → injeta no contexto
                                                                ↓
                              Use Case recebe organization_id + user_id como parametros
                                                                ↓
                              Repository filtra por organization_id em toda query
```

---

## Componentes da Plataforma

### API Gateway (Laravel HTTP Layer)

- Recebe todas as requisicoes HTTP
- Pipeline de middleware: autenticacao, rate limiting, validacao, logging
- Roteamento versionado (`/api/v1/`)
- Transformacao de response via API Resources
- Formato de resposta padronizado (`data`, `meta`, `errors`)
- Paginacao cursor-based (nunca offset)

### Auth Service

- Emissao e validacao de JWT RS256
- Access token: 15 minutos de duracao
- Refresh token: rotacionado a cada uso, armazenado com hash
- Blacklist de tokens via Redis (database 3) com TTL
- 2FA (TOTP) para operacoes sensiveis
- Auditoria completa de login (IP, user-agent, timestamp, sucesso/falha)

### Queue Workers

- Redis (database 1) como backend de filas
- 4 filas por prioridade: `high`, `default`, `low`, `notifications`
- Workers dedicados por tipo de carga
- Jobs sao idempotentes e chamam Use Cases da Application Layer
- Retry com exponential backoff (60s, 300s, 900s)
- Dead Letter Queue para falhas permanentes

### Payment Gateway (Stripe)

- Integracao com Stripe para cobranca de assinaturas
- Checkout Session para upgrade de plano
- Customer Portal para gerenciamento de metodo de pagamento
- Webhooks do Stripe processados via endpoint dedicado (signature validada)
- Nenhum dado de cartao armazenado localmente (PCI compliance via Stripe)

### Scheduler (Laravel Task Scheduling)

- Dispara jobs periodicos: sync de metricas, refresh de tokens, cleanup
- Verificacao de publicacoes agendadas (a cada minuto)
- Purge de dados expirados (soft deletes apos 30 dias)
- Health checks periodicos de conexoes sociais
- Verificacao de subscriptions expiradas e past_due

### Analytics Sync

- Workers dedicados para sincronizacao de metricas das redes sociais
- Tabelas particionadas por mes (`content_metric_snapshots`, `account_metrics`)
- Respeita rate limits dos providers externos
- Circuit breaker por provider

---

## Infraestrutura de Dados

### PostgreSQL (Banco Principal)

- Banco unico para todos os usuarios
- Extensao pgvector para embeddings de IA
- Tabelas particionadas para dados de metricas (por mes)
- Indexes otimizados com `user_id` como prefixo
- Soft delete com `deleted_at` e periodo de carencia
- Migrations reversiveis e versionadas

### Redis (Cache Distribuido)

| Database | Uso | Justificativa |
|----------|-----|---------------|
| `0` | Cache da aplicacao | Dados volateis, pode ser flushado |
| `1` | Filas (queues) | Isolamento de jobs |
| `2` | Rate limiting e contadores | TTLs independentes |
| `3` | Sessoes e tokens temporarios | Blacklist de JWT |

---

## Padroes de Comunicacao

### Sincrono (HTTP)

- Request/Response padrao para operacoes CRUD
- Validacao e resposta imediata
- Timeout configuravel por endpoint
- Sem chamadas sincronas para APIs externas em endpoints de usuario

### Assincrono (Filas e Eventos)

- **Domain Events sincronos**: Processados no mesmo request (ex: `ContentCreated` → validacao de midia)
- **Domain Events assincronos**: Despachados via fila (ex: `PostPublished` → sync analytics)
- **Jobs de integracao**: Publicacao, sync de metricas, envio de webhooks
- **Eventos sao imutaveis**: Representam fatos passados, nunca sao alterados

---

## Principios da Plataforma

### API-first

- A API e o produto. Nao existe frontend acoplado.
- Contratos de API sao compromissos formais.
- Mudancas breaking exigem nova versao.
- Documentacao de API e a interface publica do sistema.

### Stateless

- Nenhum estado de sessao no servidor.
- Toda informacao necessaria esta no JWT ou no request.
- Workers podem ser escalados horizontalmente sem coordenacao.
- Cache e otimizacao, nao dependencia.

### Secure by Default

- Autenticacao obrigatoria em todos os endpoints (exceto registro e login).
- Rate limiting ativo por padrao.
- CORS restritivo.
- Headers de seguranca em toda resposta.
- Dados sensiveis nunca em logs ou respostas.

### Observable

- Logs estruturados em JSON com contexto (`organization_id`, `user_id`, `correlation_id`, `trace_id`).
- Health checks: liveness, readiness, startup.
- Metricas de aplicacao, negocio e infraestrutura.
- Alertas acionaveis (nao apenas informativos).

---

## Anti-patterns

- **NAO** fazer chamadas sincronas para APIs externas dentro de endpoints HTTP do usuario
- **NAO** armazenar estado de sessao em memoria do servidor
- **NAO** permitir acesso a dados sem filtro de `organization_id`
- **NAO** criar endpoints que retornem dados de multiplas organizacoes
- **NAO** colocar logica de negocio em middleware ou controllers
- **NAO** usar cache como fonte primaria de dados (apenas otimizacao)
- **NAO** ignorar rate limits dos providers externos (Instagram, TikTok, YouTube)
- **NAO** processar publicacoes de forma sincrona

---

## Fora de Escopo deste Documento

- Regras de negocio de campanhas, conteudo e publicacao → ver skills em `06-domain/`
- Detalhes de autenticacao e seguranca → ver skills em `01-security/`
- Integracoes com redes sociais e IA → ver skills em `03-integrations/`
- Estrategia de testes → ver skills em `05-quality/`
- Observabilidade e operacoes → ver skills em `04-operations/`
