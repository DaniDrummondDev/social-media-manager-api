# ADR-019: Multi-Tenancy Strategy — Shared Database com Escalabilidade Progressiva

[← Voltar ao índice](00-index.md)

---

> **Status:** Accepted\
> **Data:** 2026-02-23\
> **Complementa:** ADR-003 (PostgreSQL + pgvector), ADR-014 (Cursor Pagination)

---

## Contexto

O Social Media Manager é um SaaS multi-tenant onde o tenant lógico é a **Organization** (empresa, agência, marca). A decisão de como isolar dados entre tenants afeta diretamente:

- **Performance**: Queries devem escalar com o crescimento da base de clientes.
- **Segurança**: Nenhum tenant pode acessar dados de outro (LGPD, compliance).
- **Operações**: Migrações, backups, monitoramento e manutenção.
- **Custo**: Infraestrutura e complexidade operacional.

### Volume estimado (todos os tenants combinados)

| Tabela | Volume/ano | Particionada? |
|--------|-----------|---------------|
| `mentions` (Social Listening) | 100M | Sim (mês) |
| `content_metric_snapshots` | 50M | Sim (mês) |
| `comments` | 50M | Candidata |
| `automation_executions` | 20M | Candidata |
| `crm_sync_logs` | 20M | Não |
| `ai_generations` | 10M | Não |
| `generation_feedback` | 10M | Não |
| `account_metrics` | 10M | Sim (mês) |
| `audit_logs` | 10M | Candidata |

### Volume estimado por tenant (pior caso — plano Agency, 50 contas)

| Dado | Cálculo | Volume/ano |
|------|---------|-----------|
| Posts publicados | 50 contas × 2 posts/dia | ~36K |
| Comentários capturados | 50 contas × 50/dia | ~912K |
| Metric snapshots | 100 posts/dia × 3 snapshots | ~110K |
| AI generations | ~500/mês | ~6K |
| Automation executions | ~100/dia | ~36K |

Uma agência Agency gera **~1.1M registros/ano** — volume trivial para PostgreSQL com índices adequados.

### Projeção de crescimento

| Marco | Organizations | Agency (5%) | Registros/ano estimados |
|-------|--------------|-------------|------------------------|
| Ano 1 | 5K | 250 | ~500M |
| Ano 2 | 20K | 1K | ~2B |
| Ano 3 | 50K | 2.5K | ~5B |
| Ano 5 | 200K | 10K | ~20B |

---

## Decisão

Adotar **shared database** (banco compartilhado) com isolamento via `organization_id` e **estratégia de escalabilidade progressiva em 3 níveis**, ativados conforme o volume justificar.

### Modelo de Isolamento

```
┌─────────────────────────────────────────────────────┐
│                   PostgreSQL 16+                     │
│                                                     │
│  ┌────────────────────────────────────────────────┐ │
│  │              Schema: public                    │ │
│  │                                                │ │
│  │  Org A ──┐                                     │ │
│  │  Org B ──┤── Mesmas tabelas, isoladas por      │ │
│  │  Org C ──┤   organization_id em toda query     │ │
│  │  Org D ──┘                                     │ │
│  │                                                │ │
│  │  ┌─────────────────────────────────────────┐   │ │
│  │  │ WHERE organization_id = :orgId          │   │ │
│  │  │ (aplicado em Application Layer +        │   │ │
│  │  │  reforçado por RLS no Nível 2)          │   │ │
│  │  └─────────────────────────────────────────┘   │ │
│  └────────────────────────────────────────────────┘ │
│                                                     │
│  PgBouncer (transaction pooling)                    │
│  Read replicas (analytics, admin dashboard)         │
└─────────────────────────────────────────────────────┘
```

### Regras de isolamento

1. **Toda tabela de negócio** possui coluna `organization_id` (FK para `organizations`).
2. **Toda query de negócio** inclui `WHERE organization_id = :orgId` — sem exceção.
3. **Índices compostos** usam `organization_id` como coluna líder.
4. **JWT** carrega `organization_id` da organização ativa.
5. **Acesso cross-organization** é proibido — exceto para Platform Administration (Bounded Context #11).
6. **Tabelas globais** (plans, system_configs, platform_admins, prompt_templates system-wide) não possuem `organization_id`.

### Nível 1 — Base (suficiente até ~50K organizations)

Já documentado e planejado:

| Técnica | Onde | Referência |
|---------|------|-----------|
| Particionamento temporal (RANGE por mês) | content_metric_snapshots, account_metrics, mentions | ADR-003, doc 05 |
| Índices compostos com `organization_id` líder | Todas as tabelas de negócio | doc 05 |
| Índices parciais para soft delete | Tabelas com `deleted_at` | doc 05 |
| PgBouncer (transaction pooling) | Camada de conexão | ADR-003 |
| Cursor-based pagination | Todos os endpoints de listagem | ADR-014 |
| Políticas de retenção | Cleanup jobs mensais/semanais | doc 05, seção 5.6 |
| Read replicas | Queries pesadas (analytics, admin dashboard, relatórios) | — |

**Performance esperada com Nível 1:**

| Query | Estimativa | Justificativa |
|-------|-----------|---------------|
| Listagem paginada (cursor) | < 5ms | Índice composto + cursor elimina OFFSET |
| Inbox de comentários | < 10ms | Índice parcial (is_read = FALSE) |
| Dashboard analytics | < 50ms | Índice + read replica |
| Busca semântica (pgvector) | < 50ms | IVFFlat index + filtro por org |
| Posts pendentes para dispatch | < 1ms | Índice parcial (status = pending) |

### Nível 2 — Reforço (implementado antecipadamente como defesa em profundidade)

> **Status:** Implementado em 2026-02-26. Antecipado do trigger de 50K orgs para garantir isolamento de dados desde o início.

| Técnica | Benefício | Status |
|---------|-----------|--------|
| **Row-Level Security (RLS)** | Isolamento enforcement no banco, não só na aplicação | ✅ Implementado — 36 tabelas com policies |
| Particionamento adicional | Reduz scan em tabelas que ultrapassaram trigger de volume | Pendente (trigger: volume) |
| Materialized views para agregações | Dashboard admin rápido | Pendente (platform_metrics_cache) |
| Connection pooling agressivo | Mais tenants concorrentes | Pendente (trigger: conexões) |

**Implementação RLS:**

- **Migration:** `0001_01_01_000057_enable_row_level_security.php`
- **Middleware:** `SetTenantContext` (alias `tenant.rls`) — executa `SET LOCAL app.current_org_id = ?` com org_id do JWT
- **Policies por tabela:** 2 policies cada — `tenant_isolation` (filtra por org) + `bypass_rls` (permite acesso quando contexto não definido)
- **Bypass:** Rotas admin, jobs em background, migrations e artisan commands não setam `app.current_org_id` — a policy `bypass_rls` permite acesso livre quando a variável é NULL
- **Caso especial:** `audit_logs` tem `organization_id` nullable — policy aceita NULL ou match com contexto
- **SQLite:** Migration é no-op em SQLite (testes); middleware ignora quando driver não é `pgsql`

```sql
-- Aplicado em 36 tabelas multi-tenant:
ALTER TABLE {table} ENABLE ROW LEVEL SECURITY;
ALTER TABLE {table} FORCE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON {table}
    USING (organization_id = current_setting('app.current_org_id', true)::uuid)
    WITH CHECK (organization_id = current_setting('app.current_org_id', true)::uuid);

CREATE POLICY bypass_rls ON {table}
    USING (current_setting('app.current_org_id', true) IS NULL)
    WITH CHECK (current_setting('app.current_org_id', true) IS NULL);
```

RLS funciona como **segunda camada de defesa** — mesmo que a Application Layer tenha um bug e esqueça o filtro `organization_id`, o PostgreSQL bloqueia o acesso. Não substitui o filtro na aplicação, complementa.

### Nível 3 — Escala extrema (se ultrapassar ~200K organizations ou tenant outlier)

| Técnica | Cenário | Implementação |
|---------|---------|---------------|
| **Citus (distributed PostgreSQL)** | Sharding horizontal por `organization_id` | Extensão nativa, transparente para a aplicação |
| **Isolamento seletivo** | Agência enterprise com volume excepcional | Mover para instância PostgreSQL dedicada (routing no middleware) |
| **Archive de dados frios** | Reduzir tamanho das tabelas ativas | Mover dados > 2 anos para tabelas de archive ou S3 |

**Citus sharding (conceito):**

```sql
-- Distribuir tabelas por organization_id
SELECT create_distributed_table('campaigns', 'organization_id');
SELECT create_distributed_table('contents', 'organization_id');
SELECT create_distributed_table('comments', 'organization_id');

-- Queries continuam idênticas — Citus roteia automaticamente
SELECT * FROM campaigns
WHERE organization_id = :orgId AND status = 'active';
-- → Executado apenas no shard que contém :orgId
```

Citus é **transparente para a aplicação** — não requer mudança no Eloquent, apenas na configuração do PostgreSQL.

---

## Alternativas Consideradas

### 1. Database-per-Tenant

Cada organização recebe seu próprio banco de dados PostgreSQL.

- **Prós**: Isolamento físico total, backup/restore por tenant, sem risco de data leak, performance previsível por tenant.
- **Contras**:
  - **Operações**: 100K organizations = 100K bancos. Cada migration, backup e monitoramento multiplica por N.
  - **Custo**: Overhead de memória por banco (~5-10MB mínimo). 100K bancos = 500MB-1GB só de overhead.
  - **Connection pooling**: PgBouncer precisa rotear para N bancos. Complexidade exponencial.
  - **Laravel**: Requer pacote externo (stancl/tenancy). Jobs, queues e scheduled tasks ficam complexos.
  - **Admin Dashboard**: Queries cross-organization (MRR, churn, uso global) exigem agregar de todos os bancos.
  - **IA cross-org**: prompt_templates system-wide, RAG global, fine-tuning pipeline (N7) perdem acesso cross-tenant.
  - **Migrations**: Falha em 1 banco de 100K = estado inconsistente difícil de detectar e corrigir.
- **Decisão**: Rejeitado. A complexidade operacional e o custo são desproporcionais ao benefício para o volume projetado.

### 2. Schema-per-Tenant

Cada organização recebe seu próprio schema PostgreSQL no mesmo banco.

- **Prós**: Isolamento lógico mais forte que shared table, backup/restore por schema possível, search_path resolve roteamento.
- **Contras**:
  - **Migrations**: Cada schema precisa da mesma migration — mais simples que N bancos, mas ainda N execuções.
  - **Catálogo**: PostgreSQL sofre com > 10K schemas (pg_catalog fica lento).
  - **Admin Dashboard**: Mesma complexidade que DB-per-tenant para queries cross-org.
  - **IA cross-org**: Mesma limitação — dados separados por schema.
  - **PgBouncer**: Requer `search_path` dinâmico por conexão.
- **Decisão**: Rejeitado. O catálogo PostgreSQL não escala bem com milhares de schemas, e as queries cross-org são essenciais.

### 3. Shared Database com organization_id + Escalabilidade Progressiva (escolhido)

- **Prós**:
  - Operações simples: 1 banco, 1 migration, 1 backup.
  - Custo previsível: escala com o tamanho dos dados, não com o número de tenants.
  - Admin Dashboard e IA cross-org: queries diretas sem agregação.
  - Laravel: funciona nativamente com Eloquent (global scopes).
  - Escalabilidade comprovada: Basecamp, Shopify (antes de migrar), GitHub usam shared DB.
  - PostgreSQL suporta bilhões de registros com particionamento + índices.
  - Citus como escape hatch se volume extremo exigir sharding.
- **Contras**:
  - Risco de data leak se filtro `organization_id` for esquecido (mitigado por RLS no Nível 2).
  - Tenant barulhento (noisy neighbor) pode impactar outros (mitigado por read replicas + isolamento seletivo no Nível 3).
  - Backup/restore por tenant individual é mais complexo (requer `pg_dump` com filtro).
- **Decisão**: Aceito. Melhor trade-off entre simplicidade, custo e escalabilidade para o projeto.

---

## Implementação no Laravel

### Global Scope para Tenant Isolation

```php
// app/Models/Scopes/OrganizationScope.php
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = auth()->user()?->currentOrganizationId();

        if ($organizationId) {
            $builder->where(
                $model->getTable() . '.organization_id',
                $organizationId
            );
        }
    }
}
```

### Trait para Models multi-tenant

```php
// app/Models/Concerns/BelongsToOrganization.php
trait BelongsToOrganization
{
    protected static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function (Model $model) {
            if (! $model->organization_id) {
                $model->organization_id = auth()->user()?->currentOrganizationId();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
```

### Middleware para RLS (Nível 2)

```php
// app/Http/Middleware/SetTenantContext.php
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = auth()->user()?->currentOrganizationId();

        if ($organizationId) {
            DB::statement(
                "SET LOCAL app.current_org_id = ?",
                [$organizationId]
            );
        }

        return $next($request);
    }
}
```

---

## Monitoramento

### Métricas para triggers de escalabilidade

| Métrica | Trigger Nível 2 | Trigger Nível 3 |
|---------|-----------------|-----------------|
| Total de organizations | > 50K | > 200K |
| Maior tabela não-particionada | > 100M registros | > 500M registros |
| Query p95 (endpoints de leitura) | > 100ms | > 200ms |
| Query p95 (dashboard analytics) | > 200ms | > 500ms |
| Connection pool utilization | > 70% | > 85% |
| Tenant com mais registros | > 5M | > 20M |

### Alertas

| Condição | Ação |
|----------|------|
| Query sem filtro `organization_id` em tabela multi-tenant | Alerta CRITICAL + block deploy |
| Tabela candidata ultrapassou trigger de volume | Alerta INFO → planejar particionamento |
| Tenant outlier (> 5× média de registros) | Alerta WARNING → avaliar isolamento |
| Connection pool > 85% utilization | Alerta WARNING → escalar PgBouncer |

---

## Consequências

### Positivas

1. **Simplicidade operacional**: 1 banco, 1 backup, 1 migration, 1 monitoramento.
2. **Custo previsível**: Escala linear com dados, não com número de tenants.
3. **Admin Dashboard funcional**: Queries cross-organization são triviais.
4. **IA cross-org viável**: prompt_templates globais, RAG cross-tenant, fine-tuning por vertical.
5. **Laravel nativo**: Eloquent global scopes sem pacotes externos.
6. **Escalabilidade progressiva**: 3 níveis permitem investir em complexidade apenas quando necessário.
7. **Citus como escape hatch**: Se Nível 2 não for suficiente, Citus oferece sharding transparente.

### Negativas

1. **Risco de data leak**: Filtro `organization_id` esquecido em query = acesso cross-tenant. Mitigado por RLS (Nível 2) e testes de arquitetura.
2. **Noisy neighbor**: Tenant com volume excepcional pode degradar performance para outros. Mitigado por read replicas e isolamento seletivo (Nível 3).
3. **Backup por tenant**: Mais complexo que DB-per-tenant — requer `pg_dump` com filtro ou backup lógico.

### Riscos

| Risco | Probabilidade | Impacto | Mitigação |
|-------|--------------|---------|-----------|
| Query sem `organization_id` | Média | Alto (data leak) | Global scope obrigatório, teste de arquitetura, RLS (Nível 2) |
| Noisy neighbor degrada performance | Baixa | Médio | Read replicas, monitoramento por tenant, isolamento seletivo |
| Volume ultrapassa capacidade single-node | Baixa (< 5 anos) | Alto | Nível 3: Citus sharding ou isolamento seletivo |
| Partição não criada a tempo | Baixa | Médio | Job de pré-criação (3 meses à frente), alerta |

---

## Referências

- [ADR-003](adr-003-postgresql-pgvector.md) — PostgreSQL com pgvector (connection pooling, particionamento)
- [ADR-014](adr-014-cursor-pagination.md) — Cursor-based pagination (elimina OFFSET)
- [doc 05](../database/05-indexes-performance.md) — Índices, particionamento, queries críticas
- [doc 00](../database/00-index.md) — Estimativas de volume por tabela
- [RNF-020/021](../prd/04-requisitos-nao-funcionais.md) — Requisitos de escalabilidade
