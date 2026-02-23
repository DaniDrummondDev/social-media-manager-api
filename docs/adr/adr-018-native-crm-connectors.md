# ADR-018: Native CRM Connectors Strategy

> **Status:** Accepted\
> **Data:** 2026-02-23\
> **Complementa:** ADR-006 (Adapter Pattern), ADR-007 (Domain Events), ADR-017 (AI Learning & Feedback Loop)

---

## Contexto

O sistema atualmente oferece integração com CRMs **exclusivamente via webhooks genéricos** (RF-066), o que exige que o cliente configure manualmente a URL de destino, mapeie os campos e implemente a recepção do payload. Essa abordagem é flexível mas cria duas barreiras significativas:

1. **Barreira técnica**: Clientes precisam de conhecimento técnico (ou desenvolvedor) para configurar webhooks.
2. **Barreira de conversão**: Concorrentes que oferecem conectores nativos com CRMs populares (Hootsuite + Salesforce, Sprout Social + HubSpot) capturam clientes que buscam integração "plug-and-play".

### Análise de mercado

- **HubSpot**: 228k+ clientes globais, CRM gratuito mais popular para PMEs e agências de marketing digital.
- **RD Station**: Dominante no mercado brasileiro para automação de marketing + CRM (PMEs e agências).
- **Pipedrive**: 100k+ empresas em 175 países, foco em vendas para SMEs/startups, muito forte no Brasil.
- **Salesforce**: #1 global (IDC 2025), dominante em enterprise e LATAM.
- **ActiveCampaign**: Forte em automação de marketing + CRM, altamente usado por agências digitais no Brasil.

### O problema com webhooks genéricos

| Aspecto | Webhooks Genéricos | Conectores Nativos |
|---------|-------------------|-------------------|
| Setup time | 30-60 min (técnico) | 2-5 min (OAuth) |
| Mapeamento de campos | Manual | Automático |
| Bidirecional | Requer desenvolvimento | Nativo |
| Suporte ao erro | Debug manual | Logs + retry + status |
| Persona-friendly | Apenas desenvolvedores | Qualquer usuário |

---

## Decisão

Implementar **conectores nativos** para os CRMs mais utilizados pelo público-alvo, usando o **Adapter Pattern** (ADR-006) — a mesma estratégia usada para redes sociais. Os webhooks genéricos continuam disponíveis como fallback universal.

### Arquitetura

```
CRM Connector Architecture (extends Adapter Pattern — ADR-006)

Interface: CrmConnectorInterface
├── authenticate(code, state): CrmTokenResponse
├── refreshToken(refreshToken): CrmTokenResponse
├── revokeToken(accessToken): bool
├── createContact(accessToken, contactData): CrmContactResult
├── updateContact(accessToken, contactId, data): CrmContactResult
├── createDeal(accessToken, dealData): CrmDealResult
├── updateDeal(accessToken, dealId, data): CrmDealResult
├── logActivity(accessToken, entityId, activityData): CrmActivityResult
├── searchContacts(accessToken, query): CrmContactCollection
├── getConnectionStatus(accessToken): CrmConnectionStatus

Implementações:
├── HubSpotConnector implements CrmConnectorInterface
├── RDStationConnector implements CrmConnectorInterface
├── PipedriveConnector implements CrmConnectorInterface
├── SalesforceConnector implements CrmConnectorInterface
└── ActiveCampaignConnector implements CrmConnectorInterface

Factory: CrmConnectorFactory
├── resolve(provider: CrmProvider): CrmConnectorInterface
└── Registrado no ServiceProvider
```

### Fases de Implementação

**Fase 1 — Sprint 15 (v4.0):**
- HubSpot (maior base, melhor API, plano free atrai mesmo público Creator)
- RD Station (domina mercado brasileiro, diferencial enorme vs. concorrentes globais)
- Pipedrive (muito popular entre agências brasileiras de pequeno/médio porte)

**Fase 2 — Sprint 16 (v4.0):**
- Salesforce (captura enterprise e agências maiores)
- ActiveCampaign (forte em automação, complementa engagement module)

### Fluxo de Dados (Bidirecional)

```
Social Media Manager → CRM (Outbound)
──────────────────────────────────────
Comentário positivo       → Cria/atualiza contato no CRM
Lead identificado         → Cria oportunidade/deal no CRM
Post publicado            → Registra atividade no contato
Automação executada       → Atualiza custom field no CRM
Engagement metrics        → Enriquece dados do contato

CRM → Social Media Manager (Inbound — via webhook do CRM)
──────────────────────────────────────────────────────────
Novo contato/deal criado  → Tag para segmentação de conteúdo
Deal fechado              → Trigger de campanha de conteúdo
Contato atualizado        → Sincroniza dados de audiência
Stage mudou               → Ajusta automação de engajamento
```

### Posicionamento nas Camadas

| Camada | Responsabilidade |
|--------|-----------------|
| **Domain** | `CrmProvider` enum, `CrmConnection` entity, `CrmMapping` value object, `CrmFieldMapping` value object, Domain Events |
| **Application** | Use Cases (ConnectCrm, SyncContact, CreateDeal, MapFields), DTOs, interfaces de repositório |
| **Infrastructure** | Implementações dos conectores (HubSpot, RD Station, etc.), HTTP clients, OAuth flows |

### Autenticação por CRM

| CRM | Auth | Token Lifetime | Refresh |
|-----|------|---------------|---------|
| HubSpot | OAuth 2.0 | 30 min (access) | Sim (refresh token) |
| RD Station | OAuth 2.0 | 24h (access) | Sim (refresh token) |
| Pipedrive | OAuth 2.0 | 60 min (access) | Sim (refresh token) |
| Salesforce | OAuth 2.0 | 2h (access) | Sim (refresh token) |
| ActiveCampaign | API Key | Não expira | N/A |

### Mapeamento de Campos

Cada conector possui um mapeamento padrão (default mapping) que pode ser customizado pelo usuário:

| Campo SMM | HubSpot | RD Station | Pipedrive | Salesforce | ActiveCampaign |
|----------|---------|------------|-----------|------------|----------------|
| Nome do autor | `firstname` + `lastname` | `name` | `name` | `Name` | `firstName` + `lastName` |
| External ID | `hs_additional_id` | `cf_social_id` | Custom field | `Social_ID__c` | Custom field |
| Rede social | `hs_content_source` | `cf_social_network` | Custom field | `Social_Network__c` | Custom field |
| Sentimento | Custom property | Custom field | Custom field | Custom field | Custom field |
| Campanha | `hs_campaign` | `cf_campaign` | Custom field | `Campaign` | Tag |

### Domain Events

| Evento | Trigger |
|--------|---------|
| `CrmConnected` | Conexão OAuth bem-sucedida |
| `CrmDisconnected` | Desconexão pelo usuário |
| `CrmContactSynced` | Contato criado/atualizado no CRM |
| `CrmDealCreated` | Deal/oportunidade criada no CRM |
| `CrmActivityLogged` | Atividade registrada no CRM |
| `CrmSyncFailed` | Falha na sincronização com CRM |
| `CrmTokenExpired` | Token do CRM expirou |
| `CrmFieldMappingUpdated` | Mapeamento de campos alterado |

### Jobs

| Job | Fila | Descrição |
|-----|------|-----------|
| `SyncContactToCrmJob` | `default` | Sincroniza contato para CRM |
| `CreateCrmDealJob` | `default` | Cria deal/oportunidade no CRM |
| `LogCrmActivityJob` | `low` | Registra atividade no CRM |
| `RefreshCrmTokenJob` | `high` | Renova tokens próximos de expirar |
| `ProcessCrmWebhookJob` | `default` | Processa webhook recebido do CRM |
| `BackfillCrmContactsJob` | `low` | Sincroniza contatos existentes após conexão |

### Tabelas

**1. `crm_connections`**
```sql
CREATE TABLE crm_connections (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    provider            crm_provider_type NOT NULL, -- hubspot, rdstation, pipedrive, salesforce, activecampaign
    access_token        TEXT NOT NULL,               -- Criptografado AES-256-GCM
    refresh_token       TEXT NULL,                    -- Criptografado AES-256-GCM
    token_expires_at    TIMESTAMPTZ NULL,
    external_account_id VARCHAR(255) NULL,            -- ID da conta no CRM
    account_name        VARCHAR(255) NULL,
    connection_status   VARCHAR(20) NOT NULL DEFAULT 'connected', -- connected, expired, revoked, error
    settings            JSONB NOT NULL DEFAULT '{}',  -- Configurações específicas do provider
    connected_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    connected_by        UUID NOT NULL REFERENCES users(id),
    last_sync_at        TIMESTAMPTZ NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMPTZ NULL,
    UNIQUE(organization_id, provider)                 -- 1 conexão por CRM por org
);
```

**2. `crm_field_mappings`**
```sql
CREATE TABLE crm_field_mappings (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    crm_connection_id   UUID NOT NULL REFERENCES crm_connections(id) ON DELETE CASCADE,
    entity_type         VARCHAR(50) NOT NULL,        -- contact, deal, activity
    smm_field           VARCHAR(100) NOT NULL,       -- Campo no Social Media Manager
    crm_field           VARCHAR(100) NOT NULL,       -- Campo no CRM
    transform           VARCHAR(50) NULL,            -- Transformação: uppercase, lowercase, prefix, etc.
    is_default          BOOLEAN NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(crm_connection_id, entity_type, smm_field)
);
```

**3. `crm_sync_logs`**
```sql
CREATE TABLE crm_sync_logs (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id     UUID NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    crm_connection_id   UUID NOT NULL REFERENCES crm_connections(id) ON DELETE CASCADE,
    direction           VARCHAR(10) NOT NULL,        -- outbound, inbound
    entity_type         VARCHAR(50) NOT NULL,        -- contact, deal, activity
    smm_entity_id       UUID NULL,                   -- ID da entidade no SMM
    crm_entity_id       VARCHAR(255) NULL,           -- ID da entidade no CRM
    action              VARCHAR(20) NOT NULL,        -- create, update, delete
    status              VARCHAR(20) NOT NULL,        -- success, failed, skipped
    payload             JSONB NULL,                  -- Dados enviados/recebidos
    error_message       TEXT NULL,
    duration_ms         INTEGER NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_crm_sync_logs_org_conn ON crm_sync_logs(organization_id, crm_connection_id, created_at DESC);
CREATE INDEX idx_crm_sync_logs_status ON crm_sync_logs(status) WHERE status = 'failed';
```

### Novo ENUM

```sql
CREATE TYPE crm_provider_type AS ENUM ('hubspot', 'rdstation', 'pipedrive', 'salesforce', 'activecampaign');
```

### Data Enrichment para IA (ADR-017 Nível 6)

Dados de CRM fluem de volta para o pipeline de aprendizado da IA, conectando **resultado de negócio a conteúdo social**:

```
CRM → AI Learning Loop (Data Enrichment)
─────────────────────────────────────────

Deal fechado no CRM
  → Identifica conteúdo social de origem
  → Cria crm_conversion_attribution (ADR-017 N6)
  → Conteúdo ganha boost no RAG ranking
  → IA passa a priorizar conteúdo que VENDE, não só que engaja

Contato criado via interação social
  → Lead atribuído ao conteúdo de origem
  → Enriquece ai_generation_context com dados de conversão

Segmentos/tags do CRM
  → Injetados em ai_generation_context como crm_audience_segments
  → IA personaliza geração por perfil de audiência do CRM

Deal stage mudou
  → Atualiza atribuição e stage
  → Se won/closed, registra valor monetário
```

**Tabela de atribuição:** `crm_conversion_attributions` (definida no ADR-017 Nível 6).

**Novos eventos CRM→IA:**

| Evento | Trigger | Efeito na IA |
|--------|---------|-------------|
| `CrmDealCreated` | Deal criado no CRM | `AttributeCrmConversionJob` → atribui ao conteúdo de origem |
| `CrmContactSynced` | Contato criado via social | `AttributeCrmConversionJob` → registra lead_capture |
| `CrmConversionAttributed` | Atribuição criada | `UpdateLearningContextJob` → atualiza RAG boost |
| `CrmAIContextEnriched` | Contexto semanal atualizado | Próxima geração usa dados de conversão |

**Feature gate:** CRM Intelligence é exclusivo do plano **Agency** (requer CRM conector ativo).

---

## Consequências

### Positivas

1. **Reduz barreira de integração**: De 30-60 min (webhook manual) para 2-5 min (OAuth click-to-connect).
2. **Diferencial competitivo no Brasil**: Nenhum concorrente brasileiro (mLabs, Etus, Reportei) oferece conectores nativos com CRM.
3. **Bidirecionalidade nativa**: Permite fluxos CRM→SMM que webhooks genéricos não suportam facilmente.
4. **Upsell natural**: CRM connectors como feature de planos Professional e Agency justifica o preço premium.
5. **Reuso do Adapter Pattern**: Mesma arquitetura provada para redes sociais (ADR-006), equipe já familiar.
6. **Lock-in positivo**: Dados sincronizados entre SMM e CRM criam dependência de valor.

### Negativas

1. **Manutenção de 5 conectores**: Cada CRM tem API própria com breaking changes — custo operacional.
2. **Rate limits externos**: Cada CRM impõe limites de API (HubSpot: 150 req/10s, Pipedrive: 80 req/2s).
3. **Complexidade de OAuth**: 4 dos 5 CRMs usam OAuth 2.0 com fluxos ligeiramente diferentes.
4. **Mapeamento de campos**: Customização pode gerar edge cases complexos.

### Mitigações

| Risco | Mitigação |
|-------|----------|
| Breaking changes na API do CRM | Versionamento dos conectores, testes de contrato automatizados |
| Rate limits | Throttling por provider (mesmo padrão das redes sociais), fila dedicada |
| Falhas de sincronização | Retry com backoff, sync logs detalhados, dashboard de status |
| Complexidade de mapeamento | Default mappings sensatos, UI de mapeamento visual, validação |

---

## Alternativas Consideradas

### 1. Apenas Webhooks Genéricos (status quo)
- **Prós**: Zero manutenção, flexível para qualquer CRM.
- **Contras**: Barreira técnica alta, sem bidirecionalidade, sem diferencial competitivo.
- **Decisão**: Mantido como fallback, mas insuficiente como estratégia principal.

### 2. Integração via Zapier/Make
- **Prós**: Conecta com centenas de CRMs sem desenvolvimento.
- **Contras**: Custo adicional para o cliente ($20-50/mês), latência, dependência de terceiro, sem controle de UX.
- **Decisão**: Rejeitado como estratégia principal. Pode ser complementar futuramente.

### 3. Conectores Nativos (escolhido)
- **Prós**: UX integrada, zero custo extra para o cliente, bidirecional, diferencial competitivo.
- **Contras**: Custo de desenvolvimento e manutenção.
- **Decisão**: O valor de negócio e o diferencial competitivo justificam o investimento.

---

## Referências

- [ADR-006](adr-006-adapter-pattern-social-media.md) — Adapter Pattern para Redes Sociais
- [ADR-007](adr-007-domain-events.md) — Domain Events
- [ADR-012](adr-012-encryption-strategy.md) — Estratégia de Criptografia (tokens CRM)
- [ADR-013](adr-013-queue-publishing-strategy.md) — Estratégia de Filas
- [ADR-017](adr-017-ai-learning-feedback-loop.md) — AI Learning & Feedback Loop (Nível 6: CRM Intelligence)
